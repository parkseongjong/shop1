<?php

namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\common\Filter as barryFilter;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Order
{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $logger)
    {
        $this->data = $postData;
        $this->logger = $logger;
    }

    public function orderEtokenStatusList()
    {
        try {
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'page' => 'stringNotEmpty',
                'num_rows' => 'stringNotEmpty',
                'order_key' => 'string',
                'order_dir' => 'string',
                's_keyword' => 'string',
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
            $orderEtokenStatusInfoQueryBuilder = $barrydb->createQueryBuilder();
            $orderEtokenStatusInfoQueryBuilder
                ->select('*')
                ->from('barry_order_etoken_status');
            if (!empty($filterData['s_keyword'])) {
                $orderEtokenStatusInfoQueryBuilder
                    ->where('barry_order_etoken_status like ?')
                    ->orWhere('barry_order_etoken_status like ?')
                    ->orWhere('barry_order_etoken_status like ?')
                    ->setParameter(0, '%' . $filterData['s_keyword'] . '%')
                    ->setParameter(1, '%' . $filterData['s_keyword'] . '%')
                    ->setParameter(2, '%' . $filterData['s_keyword'] . '%');
            }
            if (!empty($filterData['order_key']) && !empty($filterData['order_dir'])) {
                $orderEtokenStatusInfoQueryBuilder
                    ->addOrderBy($filterData['order_key'], $filterData['order_dir']);
            }
            else{
                $orderEtokenStatusInfoQueryBuilder
                    ->orderBy('boes_id', 'desc');
            }

            //rows 제한 잡히기 전에 전체 rows 리턴
            $orderEtokenStatusInfoTotalCount = $orderEtokenStatusInfoQueryBuilder->execute()->rowCount();

            $orderEtokenStatusInfo = $orderEtokenStatusInfoQueryBuilder
                ->setFirstResult(($filterData['page'] - 1) * $filterData['num_rows'])
                ->setMaxResults($filterData['num_rows'])
                ->execute()->fetchAll();

            unset($orderEtokenStatusInfoQueryBuilder);

            if (!$orderEtokenStatusInfo) {
                $this->logger->error('orderEtokenStatusList select error');
                throw new Exception('e-coin 결제 리스트를 불러오지 못하였습니다.', 9999);
            }

            $returnArray = array(
                'count' => $orderEtokenStatusInfoTotalCount,
                'list' => $orderEtokenStatusInfo
            );
            $this->logger->alert('e-coin 결제 리스트를 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        } catch (InvalidArgumentException $e) {
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('orderEtokenStatusList variable valid error');
            $this->logger->error($e->getMessage());
            return array('code' => 9999, 'msg' => $e->getMessage());
        } catch (Exception $e) {
            //var_dump($e->getMessage());
            return array('code' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    public function setCancelStatus()
    {
        try{
            $filter = barryFilter::singletonMethod();
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'orderId' => 'integerNotEmpty'
            );

            //유입 데이터 필터
            $filterData = array();
            $filterData = $filter->postDataFilter($this->data,$targetPostData);
            unset($this->data,$filter,$targetPostData);

            $orderInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_order')
                ->where('wr_id = ?')
                ->andWhere('wr_10 = "completePayment"')
                ->andWhere('wr_status != "finish"')
                ->setParameter(0, $filterData['orderId'])
                ->execute()->fetch();
            if(!$orderInfo){
                $this->logger->error('item order id select error');
                throw new Exception('order가 유효하지 않습니다.',406);
            }
            if($orderInfo['wr_status'] == 'cancel'){
                $this->logger->error('this item order status cancel!');
                throw new Exception('이미 주문 취소 된 주문 건 입니다.',406);
            }
            if($orderInfo['wr_price_type'] == 'KRW'){
                $this->logger->error('this item order payment type KRW');
                throw new Exception('이 주문 건은 결제를 취소 할 수 없는 결제 건 입니다.',406);
            }

            //현금 취소, e-코인 취소 분기. 현금 취소가 아닐 때만 패널티 판매자에게 주기
            /*
             *
                판매자 가상 지갑으로 전송된 상태에서 판매자가 취소요청 할 경우
                전제조건 : 구매자가 [배송완료]를 누르기 전, 판매자가 [배송시작(배송중)]을 누르기 전
                   현재 상태 : 구매자 지갑 -100% / 판매자 가상지갑 +100%
                   취소 처리 : 구매자 지갑 +100% / 판매자 가상지갑 -100% / 판매자 지갑에서 판매금액의 5%를 차감
                E-Pay로 결제한 경우 : 잔액이 부족해도 -처리할 것
             *
             */
            //사이버트론 member 고유값을 보내줘야해서, select를 함..
            $sellerInfo = $barrydb->createQueryBuilder()
                ->select('mb_1, mb_2')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $orderInfo['wr_3'])
                ->execute()->fetch();
            if(!$sellerInfo || empty($orderInfo)){
                $this->logger->error('item order seller CTC member id not found');
                throw new Exception('판매자 사이버트론 member id를 찾지 못하였습니다.',9999);
            }

            $ordererInfo = $barrydb->createQueryBuilder()
                ->select('mb_2')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $orderInfo['wr_5'])
                ->execute()->fetch();
            if(!$ordererInfo || empty($ordererInfo)){
                $this->logger->error('item order orderer Info CTC member id not found');
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
            else{
                $unit = '';
            }

            $loadPostData = array(
                'ckey' => 'none',
                'kind' => 'cancel',
                'seller_user_id' => $sellerInfo['mb_2'],
                'seller_address' => $sellerInfo['mb_1'],
                'buyer_user_id' => $ordererInfo['mb_2'],
                'payment_no' => $orderInfo['od_etoken_log_id'],
                'coin_type' => $unit
            );
            unset($unit);

            $curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis.php',$loadPostData),true);
            
			//$curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis_test2.php',$loadPostData),true);

			if(!$curlReturn || $curlReturn['code'] != '00'){
                $TEMP = $TEMP2 = false;
                foreach ($loadPostData as $key => $value){
                    $TEMP .= '/'.$key.':'.$value.'';
                }
                foreach ($curlReturn as $key => $value){
                    $TEMP2 .= '/'.$key.':'.$value.'';
                }
                $this->logger->error('cybertron cancel API proc fail curlCode:'.$curlReturn['code'].'/order id:'.$orderInfo['wr_id'].$TEMP);
                $this->logger->error('cybertron cancel API proc fail RETURN/'.$curlReturn['code'].'/'.$curlReturn['msg']);
                $this->logger->error('cybertron cancel API proc fail RETURN/'.$TEMP2);
                unset($TEMP);
                throw new Exception('사이버트론 cancel API 처리를 실패 하였습니다.',9999);
            }
            unset($loadPostData);

            $actionProc = $barrydb->createQueryBuilder()
                ->update('g5_write_order')
                ->set('wr_status','"cancel"')
                ->where('wr_id = ?')
                ->setParameter(1, $orderInfo['wr_id'])
                ->execute();
            if(!$actionProc){
                $this->logger->error('order status order cancel fail/order wrid:'.$orderInfo['wr_id']);
                throw new Exception('오더 주문 취소 처리를 실패 하였습니다.',9999);
            }

            $this->logger->alert('item order status cancel success! order id:'.$orderInfo['wr_id']);

            $orderCode = 40;
            $orderMsg = '주문 취소로 변경 되었습니다.';

            return array('code' => 200, 'orderCode' => $orderCode, 'orderMsg' => $orderMsg);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('order cancel status variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }

    }
}
?>