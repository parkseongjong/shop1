<?php

namespace barry\payment;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\common\Filter as barryFilter;
use \barry\order\Util as barryOrderUtil;
use \barry\db\DriverApi as barryDb;
use \barry\encrypt\RsaApi as barryRsa;
use \barry\payment\DanalPayup as danalPayup;
use \InvalidArgumentException;
use \Exception;

class CreditCard{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    public function process(){

        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            $orderUtil = barryOrderUtil::singletonMethod();
            $barrydb = $db-> init();
            /*
                eTokenType 레거시에서는 mc,tp3로 eToken 타입이 날아옵니다.

            */
            $targetPostData = array(
                //'orderNumber' => 'stringNotEmpty',
                //'orderType' => 'stringNotEmpty',
                'orderId' =>'stringNotEmpty',
                'cardNumber' => 'stringNotEmpty',
                'expireMonth' => 'stringNotEmpty',
                'expireYear' => 'stringNotEmpty',
                'birthDayYear' => 'string',
                'birthDayMonth' => 'string',
                'birthDayDay' => 'string',
                'companyNumber' =>'string',
                'userName' => 'stringNotEmpty',
                'userMobileNumber' => 'string',
                'cardPw' => 'stringNotEmpty',
                'quota' => 'stringNotEmpty',
                'amount' => 'stringNotEmpty');
            $filterData = array();
            $filterData = $filter->postDataFilter($this->data,$targetPostData);
            $filterData['orderType'] = 'item';

            //셀렉트한뒤...
            $orderInfo = $barrydb->createQueryBuilder()
                ->select('od_number, io_id, wr_ct_price, wr_io_price, wr_6')
                ->from('g5_write_order')
                ->where('wr_id = ?')
                ->setParameter(0,$filterData['orderId'])
                ->execute()->fetch();
            if(!$orderInfo){
                $this->logger->error('fail! card payment order number');
                throw new Exception('주문 정보가 없습니다',9999);
            }

            //상품 금액 build
            if($orderInfo['io_id']){
                $amount = ($orderInfo['wr_ct_price'] + $orderInfo['io_price']) * $orderInfo['wr_6'];
            }
            else{
                $amount = $orderInfo['wr_ct_price'] * $orderInfo['wr_6'];
            }
            if($amount != $filterData['amount']){
                $this->logger->error('fail! card payment amount value');
                throw new Exception('잘못된 접근 입니다.',9999);
            }

            $filterData['orderNumber'] = $orderInfo['od_number'];
            $danalPayup = new danalPayup($filterData,$this->memberId,$this->logger);
            $creditReturn = $danalPayup->creditCard();

            $this->logger->alert('creditCard CLASS orderNumber:'.$orderInfo['od_number'].'/creditReturnCode:'.$creditReturn['code']);

            if($creditReturn['code'] == 200){
                //페이업에 요청 신호 쏴서 정상 처리 되었으면, g5_write_order에 결제 처리로 변경
                //update 문 작성
                $orderUpdate  = $barrydb->createQueryBuilder()
                    ->update('g5_write_order')
                    ->set('wr_10','?')
                    ->where('od_number = ?')
                    ->setParameter(0,'completePayment')
                    ->setParameter(1,$orderInfo['od_number'])
                    ->execute();
                //if문해서 200이면 complate 아니면 fail

            }
            else{
                /*
                이미 결제 완료 된 건 일 때 406을 던져주는데..., FE에서 중복 요청을 막았으나..
                가끔 유입이 되고 있음... 왜? ... FAIL PAYMENT 처리는.. cron에서 할 수 있게...
                $orderUpdate  = $barrydb->createQueryBuilder()
                    ->update('g5_write_order')
                    ->set('wr_10','?')
                    ->where('od_number = ?')
                    ->setParameter(0,'failPayment')
                    ->setParameter(1,$orderInfo['od_number'])
                    ->execute();
                */

                throw new Exception($creditReturn['msg'],$creditReturn['code']);
            }

            unset($this->data, $targetPostData);// Plain data와 targetPostData는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);


            return array('code' => 200);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('creditCard CLASS ERROR/ CODE:'.$e->getCode().'/MSG:'.$e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>