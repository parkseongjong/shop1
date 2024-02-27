<?php

namespace barry\order;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\order\Util as barryOrderUtil;
use \barry\order\Invoice as barryOrderInvoice;
use \barry\db\DriverApi as barryDb;
use \barry\encrypt\RsaApi as barryRsa;
use \InvalidArgumentException;
use \Exception;

class Status{
    
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
            $db = barryDb::singletonMethod();
            $orderUtil = barryOrderUtil::singletonMethod();
            $barrydb = $db-> init();
            $barryRsa = new barryRsa;


            //code는 etoken 고유 id 입니다.
            //action은 contorller 에서 넘어오는 action 값 입니다.
            $targetPostData = array(
                'orderId' => 'integer',
                'action' => 'stringNotEmpty',
                'code' => 'integer',
                'orderDeliveryInvoice' => 'string',
                'orderDeliveryCorp' => 'string'
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

            //order wr_id 조회 후 BARRY 세션 id 값으로 판매자 또는 구매자 것 이 맞는지 확인
            //order 상태 값을 바꿀 수 있는 권한은 order에 판매자와 구매자 입니다.
            //wr_status : order-주문미확인(판매자), delivery-배송중(판매자), finish-배송완료(구매자)
            /*
                barry g5_write_order table columns comment
                mb_id : orderer phone number,
                wr_3 : seller phone number,
            */
            $this->logger->info('order가 유효하니????');
            $orderInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_order')
                ->where('wr_id = ?')
                ->setParameter(0, $filterData['orderId'])
                ->execute()->fetch();
            if(!$orderInfo){
                $this->logger->error('order id select error');
                throw new Exception('order가 유효하지 않습니다..',9999);
            }
            if($orderInfo['wr_3'] != $this->memberId && $orderInfo['mb_id'] != $this->memberId){
                $this->logger->error('order auth fail');
                throw new Exception('order 권한이 유효하지 않습니다.',9998);
            }

            $this->logger->info('접근 회원 정보 조회');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select error');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }


            //요청 action마다 분기 order-주문미확인 (판매자), delivery-배송중(판매자), finish-배송완료(구매자)
            // code(etoken) 고유 id가 없는 경우는 auto 처리 패치 이전 데이터일 가능성이 있으니까, 캐시백 처리는 안하고 order 상태 값만 변경해주기.
            /*
             * 1. action 처리가 가능한 회원인지 확인
             * 2. order 정보가 수행 하려는 action이 이미 되어 있는지 확인
             * 3. 위 사항들을 통과 하였다면 action update 처리
             */
            if($filterData['action'] == 'delivery'){
                //배송중 처리 일 때 신규 선택 옵션이 생겼으니, 해당 부분 필터링 하는 부분 만들 것, => invoice에서 배송 관련 처리 후 order 상태값만 이곳에서 변경
                $this->logger->info('action delivery 처리');
                if($memberInfo['mb_id'] != $orderInfo['mb_id']){
                    if($orderInfo['wr_status'] != 'delivery' && $orderInfo['wr_status'] == 'order'){

                        //API 처리로 쓰려고 만든 class라 sweettrackerSearch에서 유효성 체크를 한번 더 하지만 , 크게 문제는 없을 것 같음.
                        $barryOrderInvoice = new barryOrderInvoice(array('orderId'=>$orderInfo['wr_id'],'orderDeliveryInvoice'=>$filterData['orderDeliveryInvoice'],'orderDeliveryCorp'=>$filterData['orderDeliveryCorp']),$memberInfo['mb_id'],$this->logger);
                        $barryOrderInvoiceReturn = $barryOrderInvoice->sweettrackerSearch();

                        if($barryOrderInvoiceReturn['code'] == 200){
                            //정상 일 때는 아무처리 안함..
                        }
                        else if($barryOrderInvoiceReturn['code'] == 104 || $barryOrderInvoiceReturn['code'] == 406){
                            throw new Exception($barryOrderInvoiceReturn['msg'],$barryOrderInvoiceReturn['code']);
                        }
                        else{
                            $this->logger->error('order status invoice proc fail/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                            throw new Exception('오더 배송중 송장 처리를 실패하였습니다.',9999);
                        }

                        $actionProc = $barrydb->createQueryBuilder()
                            ->update('g5_write_order')
                            ->set('wr_status', '?')
                            ->where('wr_id = ?')
                            ->setParameter(0, 'delivery')
                            ->setParameter(1, $orderInfo['wr_id'])
                            ->execute();
                        if(!$actionProc){
                            $this->logger->error('order status delivery complete fail/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                            throw new Exception('오더 배송중 처리를 실패 하였습니다.',9999);
                        }
                        $orderCode = 10;
                        $orderMsg = '배송 시작으로 변경 되었습니다.';
                    }
                    else{
                        $this->logger->error('this order delivery processed/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                        throw new Exception('이미 배송중 처리가 된 주문 입니다.',406);
                    }
                }
                else{
                    //var_dump($orderInfo['mb_id']);
                    //var_dump($memberInfo['mb_id']);
                    $this->logger->error('this order delivery modify proc at the only seller possible/'.$memberInfo['mb_id']);
                    throw new Exception('배송중 처리는 해당 주문 건의 판매자만 가능합니다.',403);
                }
            }
            else if($filterData['action'] == 'order'){
                $this->logger->info('action order 처리');
                if($memberInfo['mb_id'] != $orderInfo['mb_id']){
                    if($orderInfo['wr_status'] != 'order' && $orderInfo['wr_status'] == 'delivery'){
                        $actionProc = $barrydb->createQueryBuilder()
                            ->update('g5_write_order')
                            ->set('wr_status', '?')
                            ->where('wr_id = ?')
                            ->setParameter(0, 'order')
                            ->setParameter(1, $orderInfo['wr_id'])
                            ->execute();
                        if(!$actionProc){
                            $this->logger->error('order status order complete fail/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                            throw new Exception('오더 주문 미확인 처리를 실패 하였습니다.',9999);
                        }
                        $orderCode = 20;
                        $orderMsg = '주문 미확인으로 변경 되었습니다.';
                    }
                    else{
                        $this->logger->error('this order order processed/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                        throw new Exception('이미 주문 미확인 처리가 된 주문 입니다.',406);
                    }

                }
                else{
                    $this->logger->error('this order order modify proc at the only seller possible/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                    throw new Exception('주문 미확인 처리는 해당 주문 건의 판매자만 가능합니다.',403);
                }
            }
            else if($filterData['action'] == 'finish'){
                $this->logger->info('action finish 처리 시작');
                if($memberInfo['mb_id'] == $orderInfo['wr_5']){
                    //패치 이전 배송완료 처리와, 패치 이후 배송완료 처리 분기
                    //요청 값이 0 이거나 NULL 인경우, (DB에서는 데이터가 없을때 NULL 디폴트, js function에서 리턴 할 때는 int 0 을 리턴 합니다.)
                    if($filterData['code'] == 0 || $filterData['code'] == NULL){
                        if($orderInfo['wr_status'] != 'finish' && $orderInfo['wr_status'] == 'delivery'){
                            $this->logger->info('action finish 처리[레거시]');
                            $actionProc = $barrydb->createQueryBuilder()
                                ->update('g5_write_order')
                                ->set('wr_status', '?')
                                ->where('wr_id = ?')
                                ->setParameter(0, 'finish')
                                ->setParameter(1, $orderInfo['wr_id'])
                                ->execute();
                            if(!$actionProc){
                                $this->logger->error('order status finish complete fail/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                                throw new Exception('오더 주문 미확인 처리를 실패 하였습니다.',9999);
                            }
                            $this->logger->alert('order status finish success![legacy]/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                        }
                        else{
                            $this->logger->error('this order finish processed/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                            throw new Exception('이미 배송 완료 처리가 된 주문 입니다.',406);
                        }
                    }
                    else{
                        //사이버 트론에서 finish 작업 시 중복 체크를 하지 않아서 barry 단에서 확실히 체크 해야함..
                        // 결제 완료 되었을때 od_etoken_log_id, od_realwallet_transport_status, od_cashback_status, boes_id DB단에서 NULL 상태임.
                        if($orderInfo['wr_status'] != 'finish' && $orderInfo['wr_status'] == 'delivery' && $orderInfo['od_etoken_log_id'] != NULL && $orderInfo['od_realwallet_transport_status'] == 'wait' && $orderInfo['od_cashback_status'] == 'wait' && $orderInfo['boes_id'] != NULL ){
                            $this->logger->info('action finish 처리[최신]');

                            //finish 전에 사이버트론에서 처리 값 가져와야 함..
                            //사이버트론 member 고유값을 보내줘야해서, select를 함..
                            $sellerInfo = $barrydb->createQueryBuilder()
                                ->select(' mb_2')
                                ->from('g5_member')
                                ->where('mb_id = ?')
                                ->setParameter(0, $orderInfo['wr_3'])
                                ->execute()->fetch();
                            if(!$sellerInfo){
                                $this->logger->error('seller CTC member id not found');
                                throw new Exception('판매자 사이버트론 member id를 찾지 못하였습니다.',9999);
                            }

                            $ordererInfo = $barrydb->createQueryBuilder()
                                ->select('mb_2')
                                ->from('g5_member')
                                ->where('mb_id = ?')
                                ->setParameter(0, $orderInfo['wr_5'])
                                ->execute()->fetch();
                            if(!$ordererInfo){
                                $this->logger->error('orderer Info CTC member id not found');
                                throw new Exception('주문자 사이버트론 member id를 찾지 못하였습니다.',9999);
                            }

                            /*
                                legacy barry db value : TP3, MC , KRW
                                cybertron request value : E-TP3,e-TP3,E-MC,e-MC
                            */
                            if($orderInfo['wr_price_type'] == 'MC'){
                                $unit = 'E-MC';
                            }
                            else if($orderInfo['wr_price_type'] == 'TP3'){
                                $unit = 'E-TP3';
                            }
							else if($orderInfo['wr_price_type'] == 'EKRW'){
                                $unit = 'E-KRW';
                            }
                            else if($orderInfo['wr_price_type'] == 'ECTC'){
                                $unit = 'E-CTC';
                            }
                            else{
                                $unit = '';
                            }

                            /*
                             *  coin_type 추가설명
                             *   - 결제타입에 Coin이 추가될 경우, Coin과 E-Pay의 구분이 필요함
                             *      Coin의 경우 : TP3(대문자)만 허용
                             *      E_Pay의 경우 : E-TP3, e-TP3 허용
                             */
                            $loadPostData = array(
                                'ckey' => $memberInfo['mb_3'],
                                'kind' => 'finish',
                                'seller_user_id' => $sellerInfo['mb_2'],
                                'buyer_user_id' => $ordererInfo['mb_2'],
                                'payment_no' => $filterData['code'],
                                'coin_type' => $unit
                            );
                            unset($unit);
                            $curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis.php',$loadPostData),true);

                            //test
                            //$curlReturn = array();
                            //$curlReturn['code'] = '00';

                            if(!$curlReturn || $curlReturn['code'] != '00'){
                                $TEMP = false;
                                foreach ($loadPostData as $key => $value){
                                    $TEMP .= '/'.$key.':'.$value.'';
                                }
                                $this->logger->error('cybertron finish API proc fail/'.$memberInfo['mb_id'].$TEMP);
                                $this->logger->error('cybertron finish API proc fail RETURN/'.$curlReturn['code'].'/'.$curlReturn['msg']);
                                unset($TEMP);
                                throw new Exception('사이버트론 finish API 처리를 실패 하였습니다.',9999);
                            }

                            //order table과 etoken table에 리얼 지갑 입금 완료 처리와 캐시백 리턴 완료 처리 셋팅
                            $realwallet_transport_status = 'complete';
                            $cashback_status = 'complete';
                            $cashback_type = 'beePoint';

                            $actionProc = $barrydb->createQueryBuilder()
                                ->update('barry_order_etoken_status')
                                ->set('boes_real_wallet_trasport_status', '?')
                                ->set('boes_cashback_status', '?')
                                ->set('boes_cashback_type', '?')
                                ->set('boes_finish_datetime', '?')
                                ->where('boes_id = ?')
                                ->setParameter(0, $realwallet_transport_status)
                                ->setParameter(1, $cashback_status)
                                ->setParameter(2, $cashback_type)
                                ->setParameter(3, $util->getDateSql())
                                ->setParameter(4, $orderInfo['boes_id'])
                                ->execute();
                            if(!$actionProc){
                                $this->logger->error('order etoken table status proc fail/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                                throw new Exception('오더 etoken 테이블의 상태 처리를 실패 하였습니다.',9999);
                            }

                            $actionProc = $barrydb->createQueryBuilder()
                                ->update('g5_write_order')
                                ->set('wr_status', '?')
                                ->set('od_realwallet_transport_status', '?')
                                ->set('od_cashback_status', '?')
                                ->where('wr_id = ?')
                                ->setParameter(0, 'finish')
                                ->setParameter(1, $realwallet_transport_status)
                                ->setParameter(2, $cashback_status)
                                ->setParameter(3, $orderInfo['wr_id'])
                                ->execute();
                            if(!$actionProc){
                                //이노DB 타입 table 이 아니라 실패시 barry_order_etoken_status에 값을 원래로 되돌립니다.
                                $actionProc = $barrydb->createQueryBuilder()
                                    ->update('barry_order_etoken_status')
                                    ->set('boes_real_wallet_trasport_status', '?')
                                    ->set('boes_cashback_status', '?')
                                    ->set('boes_cashback_type', '?')
                                    ->set('boes_finish_datetime', '?')
                                    ->where('boes_id = ?')
                                    ->setParameter(0, 'wait')
                                    ->setParameter(1, 'wait')
                                    ->setParameter(2, 'none')
                                    ->setParameter(3, $util->getDateSqlDefault())
                                    ->setParameter(4, $orderInfo['boes_id'])
                                    ->execute();
                                if(!$actionProc){
                                    $this->logger->error('order etoken table status proc fail[2]/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                                    throw new Exception('오더 etoken 테이블의 상태 처리를 실패 하였습니다.[2]',9999);
                                }

                                $this->logger->error('order etoken status proc fail/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                                throw new Exception('오더 etoken 처리를 실패 하였습니다.',9999);
                            }

                            $this->logger->alert('order status finish success![new]/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);

                        }
                        else{
                            $this->logger->error('this order finish processed/'.$memberInfo['mb_id'].'/order wrid:'.$orderInfo['wr_id']);
                            throw new Exception('이미 배송 완료 처리가 되었거나 결제 상태가 비정상적인 주문 입니다.',406);
                        }

                        unset($realwallet_transport_status, $cashback_status, $cashback_type);//사용한 변수 언셋
                    }
                }
                else{
                    $this->logger->error('this order finish modify proc at the only (orderer) possible/'.$memberInfo['mb_id']);
                    throw new Exception('배송 완료 처리는 해당 주문 건의 주문자만 가능합니다.',403);
                }

                $orderCode = 30;
                $orderMsg = '배송 완료로 변경 되었습니다.';
            }
            else{
                $this->logger->error('not access action/'.$memberInfo['mb_id']);
                throw new Exception('허용 되지 않은 action 입니다.',9999);
            }

            /*$returnArray = array(
                'test' => $filterData
            );*/

            $this->logger->alert('order status final success!');

            return array('code' => 200, 'orderCode' => $orderCode, 'orderMsg' => $orderMsg);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            if($e->getCode() == 0){
                $this->logger->error('order status fail!/session memberId:'.$this->memberId);
                $this->logger->error('order status SQL fail!/'.$e->getMessage());
            }
            else if($e->getCode() == 9998){
                $this->logger->error('order status fail!/session memberId:'.$this->memberId);
            }
            else{
                $this->logger->error('order status fail!/not session memberId real memberId:'.$memberInfo['mb_id']);
            }

            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>