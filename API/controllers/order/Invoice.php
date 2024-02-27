<?php

namespace barry\order;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Invoice{
    
    private $data = false;
    private $memberId = false;
    private $logger = false;
    
    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    public function getDeliveryList(){
        try{
            //$config = \HTMLPurifier_Config::createDefault();
            //$purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $this->logger->info('택배 회사 리스트 호출');
            $corpInfo = $barrydb->createQueryBuilder()
                ->select('bdc_code as corpCode, bdc_name as corpName, bdc_use as corpUse')
                ->from('barry_delivery_corp')
                ->orderBy('bdc_code','DESC');
            //레코드 row 조회
            $corpInfoCount = $corpInfo->execute()->rowCount();
            $corpInfo = $corpInfo->execute()->fetchAll();

            if(!$corpInfo){
                $this->logger->error('corpInfo select error');
                throw new Exception('택배 회사 목록 조회를 실패 하였습니다.',9999);
            }

            $returnArray = array(
                'list' => $corpInfo,
                'count' => $corpInfoCount
            );

            return array('code' => 200, 'data' => $returnArray);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('getDeliveryList fail!');
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }

    }
    
    public function sweettrackerSearch(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'orderId' => 'stringNotEmpty',
                'orderDeliveryInvoice' => 'string',
                'orderDeliveryCorp' => 'integer'
            );

            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if($targetPostData[$key] == 'integer'){
                        Assert::{$targetPostData[$key]}((int)$value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data, $targetPostData);// Plain data와 targetPostData는 unset 합니다.
            $this->logger->info('필터 데이터:', $filterData);

            $this->logger->info('세션 값 정보 빌드');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('mb_id')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select error');
                throw new Exception('정상적인 세션값이 아닙니다.',9999);
            }

            $this->logger->info('오더 값 정보 빌드');
            $orderInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_order')
                ->where('wr_id = ?')
                ->setParameter(0, $filterData['orderId'])
                ->execute()->fetch();
            if(!$orderInfo){
                $this->logger->error('order select error');
                throw new Exception('정상적인 오더 값이 아닙니다.',9999);
            }

            //요청자 정보와, 오더 소유자와 같은지 ?
            if($memberInfo['mb_id'] != $orderInfo['wr_3']){
                $this->logger->error('invoice auth error');
                throw new Exception('정상 요청이 아닙니다.',9999);
            }

            //택배 회사를 이용하는지, 아니면 하지 않는지?
            $invoiceCorpInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_delivery_corp')
                ->where('bdc_code = ?')
                ->andWhere('bdc_use = ?')
                ->setParameter(0, $filterData['orderDeliveryCorp'])
                ->setParameter(1, 1)
                ->execute()->fetch();
            if(!$invoiceCorpInfo){
                $this->logger->error('invoice corp fail');
                throw new Exception('택배 회사 정보가 유효하지 않습니다.',9999);
            }

            //barry_order_invoice에 에 이 넣은 송장 번호가 있으면 하루 기준으로 더 조회 못하게 차단 하기. (외부 API는 동일 송장 20회 제한이 있음.)
            $thisTime = time();
            $invoiceInfoCount = $barrydb->createQueryBuilder()
                ->select('boi_number, boi_datetime')
                ->from('barry_order_invoice')
                ->where('boi_order_id = ?')
                ->andWhere('boi_number = ?')
                ->andWhere('boi_datetime >= ?')
                ->andWhere('boi_datetime <= ?')
                ->setParameter(0,$orderInfo['wr_id'])
                ->setParameter(1,$filterData['orderDeliveryInvoice'])
                ->setParameter(2,date('Y-m-d', $thisTime).' 00:00:00')
                ->setParameter(3,date('Y-m-d', $thisTime).' 23:59:59')
                ->execute()->rowCount();
            if($invoiceInfoCount >= 20){
                $this->logger->error('invoice number duplicated!');
                throw new Exception('해당 운송장은 하루 조회 가능한 횟 수를 초과 하였습니다.',406);
            }

            if($invoiceCorpInfo['bdc_code'] == 9999){
                $invoiceProc = $barrydb->createQueryBuilder()
                    ->insert('barry_order_invoice')
                    ->setValue('boi_order_id', '?')
                    ->setValue('boi_corp', '?')
                    ->setValue('boi_number', '?')
                    ->setValue('boi_level', '?')
                    ->setValue('boi_datetime', '?')
                    ->setParameter(0,$orderInfo['wr_id'])
                    ->setParameter(1,$invoiceCorpInfo['bdc_name'])
                    ->setParameter(2,$filterData['orderDeliveryInvoice']) // 택배를 사용 안할 때는, 연락 가능한 연락처....!
                    ->setParameter(3,99)
                    ->setParameter(4,$util->getDateSql());
            }
            else{
                $loadPostData = array(
                    //'t_key' => 'XErsaioy9VDnGR3jNLRz4Q',//스마트 택배  free key
					't_key' => 'KtQCAMR9nnLbQMN6OMJyiQ', //스마트 택배 basic key (real)
                    't_code' => sprintf('%02d',$invoiceCorpInfo['bdc_code']),//택배사 코드, 자리수를 맞춰서..api 요청
                    't_invoice' => $filterData['orderDeliveryInvoice'],//운송장 번호
                );
                //var_dump($filterData);
                $curlReturn = json_decode($util -> getCurlApiTypeGet('https://info.sweettracker.co.kr/api/v1/trackingInfo',$loadPostData),true);
//                $curlReturn['result'] = 'Y';
//                $curlReturn['invoiceNo'] = '01050958112';
//                $curlReturn['level'] = '77';
                //var_dump($curlReturn);
                if(!$curlReturn || $curlReturn['result'] != 'Y') {
                    if($curlReturn['code'] == 104){
                        $this->logger->error('invalid number or code');
                        throw new Exception('유효하지 않은 운송장번호 이거나 택배사 코드 입니다.',104);
                    }
                    else if($curlReturn['result'] == 'N'){
                        $this->logger->error('invalid number or code');
                        throw new Exception('유효하지 않은 운송장번호 입니다.',406);
                    }
                    else{
                        $TEMP = false;
                        foreach ($curlReturn as $key => $value){
                            $TEMP .= '/'.$key.':'.$value.'';
                        }
                        $this->logger->error('sweettracker API fail:'.$TEMP);
                        unset($TEMP);
                        throw new Exception('sweettracker API fail',9999);
                    }
                }

                if($curlReturn['level'] >= 1){
                    $invoiceProc = $barrydb->createQueryBuilder()
                        ->insert('barry_order_invoice')
                        ->setValue('boi_order_id', '?')
                        ->setValue('boi_corp', '?')
                        ->setValue('boi_number', '?')
                        ->setValue('boi_level', '?')
                        ->setValue('boi_datetime', '?')
                        ->setValue('boi_response', '?')
                        ->setParameter(0,$orderInfo['wr_id'])
                        ->setParameter(1,$invoiceCorpInfo['bdc_name'])
                        ->setParameter(2,$curlReturn['invoiceNo'])
                        ->setParameter(3,$curlReturn['level'])
                        ->setParameter(4,$util->getDateSql())
                        ->setParameter(5,json_encode($curlReturn,JSON_UNESCAPED_UNICODE));
                }
                else{
                    $this->logger->error('invoice not not ready');
                    throw new Exception('송장 번호가 준비 되지 않았습니다.',9999);
                }
            }

            if(!$invoiceProc->execute()){
                $this->logger->error('invoice insert fail');
                throw new Exception('송장 번호 유효성 체크 생성을 실패 하였습니다.',9999);
            }
            $invoiceProcinertId = $barrydb->lastInsertId();

            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_order')
                ->set('boi_id', '?')
                ->where('wr_id = ?')
                ->setParameter(0,$invoiceProcinertId)
                ->setParameter(1,$orderInfo['wr_id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('invoice id value not update');
                throw new Exception('송장 번호 유효성 체크 고유 id를 주문 정보에 업데이트 하지 못하였습니다.',9999);
            }

            $this->logger->alert('invoice Search success!');
            return array('code' => 200);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('invoice Search fail!/not session memberId:'.$memberInfo['mb_id']);
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>