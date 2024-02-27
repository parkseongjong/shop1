<?php
namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class DanalPayup
{
    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $logger)
    {
        $this->data = $postData;
        $this->logger = $logger;
    }

    public function cardCancel(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'transactionId' => 'stringNotEmpty',
            );
            $filterData = array();
            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data, $targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:', $filterData);

            $this->logger->info('거래번호 조회');
            $paymentInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_pg_payment_status')
                ->where('bpps_transaction_id = ?')
                ->setParameter(0, $filterData['transactionId'])
                ->execute()->fetch();
            if (!$paymentInfo) {
                $this->logger->error('not found transactionId');
                throw new Exception('일치하는 거래번호를 찾을 수 없습니다.', 9999);
            }

            //위변조 방지 서명 해시 전문 순서는 다음과 같습니다...
            //가맹점아이디|거래번호|apikey
            $signature = hash('sha256',trim('oneheartsmart|'.$paymentInfo['bpps_transaction_id'].'|'.'095ec521316a4665b44d8abeb1b41e9a'),false);
            $loadPostData = array(
                'merchantId' => 'oneheartsmart', //가맹점아이디
                'transactionId' => $paymentInfo['bpps_transaction_id'], //거래번호
                'signature' => $signature,
            );

            $this->logger->info('[cybertron]다날 페이업 cardCancel2 요청');
            $curlReturn = json_decode($util -> getCurlApi('https://api.payup.co.kr/v2/api/payment/oneheartsmart/cancel2',$loadPostData),true);

            if($curlReturn['responseCode'] == '0000' ){
                //정상 취소 되면 ... 취소 update
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status','?')
                    ->set('bpps_cancel_request','?')
                    ->set('bpps_cancel_response','?')
                    ->set('bpps_cancel_datetime','?')
                    ->where('bpps_transaction_id = ?')
                    ->setParameter(0,$paymentInfo['cancel'])
                    ->setParameter(1,json_encode($loadPostData,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2,json_encode($curlReturn,JSON_UNESCAPED_UNICODE))
                    ->setParameter(3,$util->getDateSql())
                    ->setParameter(4,$paymentInfo['bpps_transaction_id'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('transactionId info update fail!');
                    throw new Exception('거래 번호 정보 업데이트를 실패 하였습니다.',9999);
                }
            }
            else{
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_cancel_request','?')
                    ->set('bpps_cancel_response','?')
                    ->set('bpps_cancel_datetime','?')
                    ->where('bpps_transaction_id = ?')
                    ->setParameter(0,json_encode($loadPostData,JSON_UNESCAPED_UNICODE))
                    ->setParameter(1,json_encode($curlReturn,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2,$util->getDateSql())
                    ->setParameter(3,$paymentInfo['bpps_transaction_id'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('transactionId info update fail!');
                    throw new Exception('거래 번호 정보 업데이트를 실패 하였습니다.',9999);
                }
                $this->logger->error('PG payment cancel request fail/'.$paymentInfo['bpps_transaction_id']);
            }

            $this->logger->alert('[cybertron]다날 페이업 cardCancel2 요청 완료');
            return array('code' => 200);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>