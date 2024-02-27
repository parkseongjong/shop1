<?php
namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \Doctrine\DBAL\Connection as DbalConnection;
use \InvalidArgumentException;
use \Exception;

class Cron
{
    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $logger)
    {
        $this->data = $postData;
        $this->logger = $logger;
    }

    //임시로 e-MC도 처리 되게 하였습니다....
    public function goodsEtp3PriceUpdate(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'serverParams' => 'isArray',
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    if($key == 'serverParams'){
                        $filterData[$purifier->purify($key)] = $value;
                    }
                    else{
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);

            //cron table 관리 테이블,
            //각 상품들이 update를 정상적으로 했는지 판별 하는 테이블,
            //IP 확인 부터...

            $this->logger->info('cron ip 비교');
            //ctc wallet 175.126.82.225
//            if($filterData['serverParams']['REMOTE_ADDR'] != '111.111.111.111'){
//                $this->logger->error('[CRON]ip auth error');
//                throw new Exception('[CRON]IP 비교를 실패 하였습니다.', 9999);
//            }

            //cron 요청 일시
            $cronDateTime = $util->getDateSql();
            $barrydb->createQueryBuilder()
                ->insert('barry_cron')
                ->setValue('bc_source','?')
                ->setValue('bc_source_type','?')
                ->setValue('bc_ip','?')
                ->setValue('bc_description','"barry goods(item) coin price update"')
                ->setValue('bc_datetime','?')
                ->setParameter(0,'cybertron')
                ->setParameter(1,'remote')
                ->setParameter(2,$filterData['serverParams']['REMOTE_ADDR'])
                ->setParameter(3,$cronDateTime)
                ->execute();

            $insertId = $barrydb->lastInsertId();

            $this->logger->info('cron remote cybertron 환율 정보 확인');
            //MC도 자동으로 변환으로 지정 되면, 불러올 때 두가지 코인다 불러오기...
			/*
            $loadPostData = array(
                'ckey' => 'tempToken', // ctc api에서 ckey는 아직 설정 안해서 아무런 key만 날려도 됨
                'kind' =>  'getprice2', // api 문서 참고
                'coin_type' =>  'e-TP3', // api 문서 참고
            );
			*/
            $loadPostData = array(
                'ckey' => 'tempToken', // ctc api에서 ckey는 아직 설정 안해서 아무런 key만 날려도 됨
                'kind' =>  'getpriceAll', // api 문서 참고
            );
            /*
                array(5) {
                    ["code"]=>
                    string(2) "00"
                    ["msg"]=>
                    string(2) "ok"
                    ["ex_rate"]=>
                    string(4) "17.6"
                    ["epay_per_coin"]=>
                    string(1) "1"
                    ["unit"]=>
                    string(5) "E-TP3"
                }
                eTP3El.val(Math.floor(krwValue / coinRate)); floor
             */
            $curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis.php',$loadPostData),true);
            if(!$curlReturn){
                $this->logger->error('cron coin rate value load fail');
                throw new Exception('코인 rate 값을 가져오지 못하였습니다.', 9999);
            }
			
			foreach ($curlReturn['data'][0]['data'] as $key => $value){
                if($value['value'] <= 0){
                    $this->logger->error('cron coin rate value load fail(2)');
                    throw new Exception('코인 rate 값을 가져오지 못하였습니다.(2)', 9999);
                }
            }
	
            $this->logger->info('cron 카테고리별 goods(item)정보 select 후 update');
            $targetTable = array('Shop','car','estate','market');
            $targetCoinType = array('TP3','TP3MC','EKRW','ECTC');
            //승인된 goods(item)만 update 처리 해준다.
            //주의 3중 foreach,
            foreach ($targetTable as $key => $value){
                $goodsInfo = $barrydb->createQueryBuilder()
                    ->select('wr_id, wr_price_type,it_publish, it_me_table, it_option_subject,it_cast_type, it_cast_price, wr_1, wr_2, wr_3, wr_4, wr_10')
                    ->from('g5_write_'.$value)
                    ->where('it_publish = ?')
                    ->andWhere('it_cast_type != ?')
                    ->andWhere('it_cast_price > ?')
                    ->andWhere('wr_price_type in (?)')
                    ->setParameter(0,1)
                    ->setParameter(1,'NONE')
                    ->setParameter(2,0)
                    ->setParameter(3, $targetCoinType,DbalConnection::PARAM_STR_ARRAY)
                    ->execute()->fetchAll();

                //response 할 데이터에 curl 리턴 값 포함.
                $responseArray = $curlReturn;
                $responseArray['cronId'] = $insertId;

                foreach ($goodsInfo as $key2 => $value2){
                    //value 초기화
                    $responseArray['oldEtp3Value'] = $responseArray['oldEmcValue'] = $responseArray['oldEctcValue'] = 0;

                    $updateProc = $barrydb->createQueryBuilder()
                        ->update('g5_write_'.$value);
                    //단일 상품 처리
                    if(in_array($value2['wr_price_type'],$targetCoinType)){
                        $updateParams = array();
                        if($value2['wr_price_type'] == 'TP3'){
                            //이전 데이터 삽입
                            $responseArray['oldEtp3Value'] = $value2['wr_1'];
                            $updateProc
                                ->set('wr_1', '?');
                            array_push($updateParams,floor($value2['it_cast_price']/floor($curlReturn['data'][0]['data'][0]['value'])));//실제로 반영 될 값.. 주의! [단일 상품]
                            $exRate = $curlReturn['data'][0]['data'][0]['value'];
                            $epayPerCoin = $curlReturn['data'][1]['data'][0]['value'];
                        }//추후 MC도 처리 할 때 같이 처리 할 것. 그땐 setparameters 로 array 처리
                        elseif($value2['wr_price_type'] == 'TP3MC'){
                            //이전 데이터 삽입
                            $responseArray['oldEtp3Value'] = $value2['wr_1'];
                            $responseArray['oldEmcValue'] = $value2['wr_2'];
                            $updateProc
                                ->set('wr_1', '?')
                                ->set('wr_2', '?');
                            array_push($updateParams,floor($value2['it_cast_price']/floor($curlReturn['data'][0]['data'][0]['value'])));//실제로 반영 될 값.. 주의! [단일 상품]
                            array_push($updateParams,floor($value2['it_cast_price']/floor($curlReturn['data'][0]['data'][1]['value'])));//실제로 반영 될 값.. 주의! [단일 상품]
                            $exRate = $curlReturn['data'][0]['data'][0]['value'];
                            $epayPerCoin = $curlReturn['data'][1]['data'][0]['value'];
                        }
                        elseif($value2['wr_price_type'] == 'ECTC'){
                            //이전 데이터 삽입
                            $responseArray['oldEctcValue'] = $value2['wr_4'];
                            $updateProc
                                ->set('wr_4', '?');
                            array_push($updateParams,floor($value2['it_cast_price']/floor($curlReturn['data'][0]['data'][2]['value'])));//실제로 반영 될 값.. 주의! [단일 상품]
                            $exRate = $curlReturn['data'][0]['data'][2]['value'];
                            $epayPerCoin = $curlReturn['data'][1]['data'][2]['value'];
                        }
                        $updateProc
                            ->set('it_per_rate','?')
                            ->set('it_rate','?')
                            ->where('wr_id = ? ');
                            array_push($updateParams,(string)$epayPerCoin);
                            array_push($updateParams,(string)$exRate);
                            array_push($updateParams,$value2['wr_id']);
                        $updateProc
                            ->setParameters($updateParams)
                            ->execute();
                    }

                    //선택옵션 처리
                    //var_dump($value2['it_option_subject']);
                    if(!empty($value2['it_option_subject'])){
                        //responseArray에 선택 옵션 관련 값 넘겨주기...
                        $responseArray['selectOption'] = array();
                        $selectOptionInfo = $barrydb->createQueryBuilder()
                            ->select('io_no, io_cast_price, io_price_etp3, io_price_emc, wr_price_type')
                            ->from('g5_shop_item_option')
                            ->where('it_id = ?')
                            ->andWhere('io_me_table = ?')
                            ->setParameter(0,$value2['wr_id'])
                            ->setParameter(1,$value2['it_me_table'])
                            ->orderBy('io_no','ASC')
                            ->execute()->fetchAll();
                       //$selectOptionInfo->

                        if($selectOptionInfo){
                            //단일 상품과 동일, 추후 MC도 처리 할 때 같이 처리 할 것. 그땐 setparameters 로 array 처리
                            foreach ($selectOptionInfo as $selectOptionInfoKey => $selectOptionInfoValue){
                                $updateParams = array();
                                $selectOptionInfoUpdateProc = $barrydb->createQueryBuilder()
                                    ->update('g5_shop_item_option');
                                    if($value2['wr_price_type'] == 'TP3') {
                                        $responseArray['selectOption'][$selectOptionInfoValue['io_no']]['oldEtp3Value'] = $selectOptionInfoValue['io_price_etp3'];
                                        $selectOptionInfoUpdateProc
                                            ->set('io_price_etp3','?');
                                            array_push($updateParams,floor($selectOptionInfoValue['io_cast_price']/floor($curlReturn['ex_rate'])));//실제로 반영 될 값, 주의! [선택옵션]
                                    }
                                    elseif($value2['wr_price_type'] == 'TP3MC'){
                                        $responseArray['selectOption'][$selectOptionInfoValue['io_no']]['oldEtp3Value'] = $selectOptionInfoValue['io_price_etp3'];
                                        $responseArray['selectOption'][$selectOptionInfoValue['io_no']]['oldEmcValue'] = $selectOptionInfoValue['io_price_etp3'];
                                        $selectOptionInfoUpdateProc
                                            ->set('io_price_etp3','?')
                                            ->set('io_price_emc','?');
                                            array_push($updateParams,floor($selectOptionInfoValue['io_cast_price']/floor($curlReturn['ex_rate'])));//실제로 반영 될 값, 주의! [선택옵션]
                                            array_push($updateParams,floor($selectOptionInfoValue['io_cast_price']/floor($curlReturn['ex_rate'])));//실제로 반영 될 값, 주의! [선택옵션]
                                    }
                                    //var_dump(floor($selectOptionInfoValue['io_cast_price']/floor($curlReturn['ex_rate'])));
                                $selectOptionInfoUpdateProc
                                    ->where('io_no = ?');
                                    array_push($updateParams,$selectOptionInfoValue['io_no']);
                                $selectOptionInfoUpdateProc
                                    ->setParameters($updateParams)
                                    ->execute();
                            }
                        }
                    }
                    $barrydb->createQueryBuilder()
                        ->insert('barry_cron_log')
                        ->setValue('bc_id', '?')
                        ->setValue('bcl_target_id','?')
                        ->setValue('bcl_target_table','?')
                        ->setValue('bcl_response_type','?')
                        ->setValue('bcl_response_message','?')
                        ->setValue('bcl_datetime','?')
                        ->setParameter(0,$insertId)
                        ->setParameter(1,$value2['wr_id'])
                        ->setParameter(2,'g5_write_'.$value2['it_me_table'])
                        ->setParameter(3,'json')
                        ->setParameter(4,json_encode($responseArray,JSON_UNESCAPED_UNICODE))
                        ->setParameter(5,$util->getDateSql())
                        ->execute();
                    //responseMsg엔 이전 coin값 안넘겨 줌
                    unset($responseArray['selectOption']);
                }
                //responseMsg엔 이전 coin값 안넘겨 줌
                unset($responseArray['oldEctcValue']);
                unset($responseArray['oldEtp3Value']);
                unset($responseArray['oldEmcValue']);
            }

            $responseArray['cronDatetime'] = $cronDateTime;

            $returnArray = array(
                'responseMsg' => json_encode($responseArray,JSON_UNESCAPED_UNICODE),
            );

            $this->logger->alert('Cron goodsEtp3PriceUpdate 을 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('cron goodsEtp3PriceUpdate variable valid error:'.$e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}
?>