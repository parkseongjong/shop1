<?php


namespace barry\order;

use \ezyang\htmlpurifier;

use \barry\common\Util as barryUtil;
use \barry\common\Token as barryToken;
use \barry\common\Filter as barryFilter;
use \barry\order\Util as barryOrderUtil;
use \barry\db\DriverApi as barryDb;

use \InvalidArgumentException;
use \Exception;

class Upload{

    private $data = false;
    private $memberId = false;
    private $logger = false;
    private $session = false;
    private $filterData = array();

    public function __construct($postData, $memberId, $containerInfo){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $containerInfo->get('logger');
        $this->session = $containerInfo->get('session');
        unset($postData,$memberId,$containerInfo);
    }


    /*
     * new item order upload
     * 일반 회원 상품 주문
     *
     *
     */
    public function userItemUpload(){
        try{
            $util = barryUtil::singletonMethod();
            $orderUtil = barryOrderUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();
            $token = barryToken::singletonMethod();

            //gb member info build
            $memberInfo = $util->getGbMember($this->memberId);

            if($memberInfo['mb_level'] <= 1){
                $this->logger->error('item USER ORDER not auth');
                throw new Exception('비회원은 상품을 주문 할 수 없습니다.',403);
            }

            $targetPostData = array(
                'token' => 'stringNotEmpty',
                'optId' => 'singleChr30', //io_id 선택 옵션 id
                'cartPrice' => 'integerNotEmpty', //wr_ct_price 상품 주문 시 상품 금액
                'cartOptionPrice' => 'integer', //wr_io_price 선택 옵션 금액
                'itemId' => 'integerNotEmpty',
                'tableId' => 'stringNotEmpty', //table ID
                'qty' => 'integerNotEmpty',
                'recvName' => 'stringNotEmpty', //수령자 이름
                'recvPhone' => 'stringNotEmpty', // 수령자 전화번호
                'address' => 'stringNotEmpty',
                'zip' => 'stringNotEmpty',
                'addr1' => 'stringNotEmpty',
                'addr2' => 'stringNotEmpty',
                'addr3' => 'string',
                'jibun' => 'stringNotEmpty',
                'memberZip' => 'stringNotEmpty',
                'memberAddr1' => 'stringNotEmpty',
                'memberAddr2' => 'stringNotEmpty',
                'memberAddr3' => 'string',
                'memberJibun' => 'stringNotEmpty',
                'memberAddress' => 'stringNotEmpty',
                'memberAddressSave' => 'string',
                'addressSave' => 'string',
                'cartPriceType' => 'stringNotEmpty', //wr_price_type3 (db 컬럼 : wr_price_type ) 결제 방식
                'cartOption' => 'string', // ct_option 선택한 옵션명
                'odMemo' => 'string', // 주문 시 메모
            );

            //유입 데이터 필터
            $this->filterData = $filter->postDataFilter($this->data,$targetPostData);
            unset($this->data,$filter);

            /* token 확인 */
            if(!$this->filterData['token'] || !$token->validSessionToken($this->filterData['tableId'],$this->filterData['token'],$this->session,'ss_write_','_token')){
                $this->logger->error('seller item uplaod token error(2)');
                throw new Exception('올바른 방법으로 이용해 주십시오.(2)',9999);
            }
            $token->clearSessionToken($this->filterData['tableId'],$this->session,'ss_write_','_token');

            //결제 타입 유효성 확인
            //상품(item) 정보에 wr_price_type과 혼동하지 말 것, 주문 시에는 결제 타입이 따로 들어감.
			if(!in_array($this->filterData['cartPriceType'],array('e-TP3','e-MC','KRW','e-KRW','e-CTC','CREDITCARD'))){
                $this->logger->error('price type not vaild');
                throw new Exception('주문 결제 타입이 유효하지 않습니다.',403);
            }

            //gb board 정보 가져오기
            $boardInfo = $util->getGbBoard($this->filterData['tableId'],true);
            if(!$boardInfo){
                $this->logger->error('not found boardTable');
                throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
            }

            //상품 정보 build
            $itemInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_'.$boardInfo['bo_table'])
                ->where('wr_id = ?')
                ->setParameter(0, $this->filterData['itemId'])
                ->execute()->fetch();

            //유효한 상품인지?
            if(!$itemInfo){
                $this->logger->error('item select fail /'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                throw new Exception('유효하지 않은 상품 입니다.',9999);
            }

            //셀러 정보도 유효한지 확인을
            $sellerInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $itemInfo['mb_id'])
                ->execute()->fetch();
            if(!$sellerInfo){
                $this->logger->error('seller info error');
                throw new Exception('판매자 정보가 유효하지 않습니다.',9999);
            }

            //1. 미승인 된 상품인지? 반려:90 번 인 경우에도 주문 불가능
            if($itemInfo['it_publish'] == '0' || $itemInfo['it_publish'] == '90'){
                //삭제 된 아이템인지?
                $this->logger->error('this item not published /'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                throw new Exception('미 승인 된 상품 입니다.',9999);
            }
            //1-1. 삭제된 (판매 중지) 된 상품인지?
            if($itemInfo['del_yn'] == 'Y'){
                //삭제 된 아이템인지?
                $this->logger->error('this item disabled/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                throw new Exception('판매 중지된 상품 입니다.',9999);
            }

            //2. it_sold out 인 상품인지 ? 0 : 정상, 1 : 품절
            if($itemInfo['it_soldout'] == 1){
                $this->logger->error('this item soldout!!!!/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                throw new Exception('품절 된 상품 입니다.',9999);
            }

            //재고수량과 통보 수량 build
            if ($itemInfo['it_option_subject']){
                //선택 옵션이 있는경우. io_id 유효성 체크
                $selectOptionInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('g5_shop_item_option')
                    ->where('io_id = ?')
                    ->andWhere('io_me_table = ?')
                    ->andWhere('it_id = ?')
                    ->setParameter(0,$this->filterData['optId'])
                    ->setParameter(1,$itemInfo['it_me_table'])
                    ->setParameter(2,$itemInfo['wr_id'])
                    ->execute()->fetch();
                if(!$selectOptionInfo){
                    $this->logger->error('order upload not found select option');
                    throw new Exception('선택 옵션 id를 찾을 수 없습니다.',403);
                }

                $itStockQty = (int)$orderUtil->get_option_stock_qty_barry($itemInfo['wr_id'], $this->filterData['optId'],$itemInfo['it_me_table']);
                $itNotiStockQty = (int)$orderUtil->get_option_noti_qty_barry($itemInfo['wr_id'], $this->filterData['optId']);
            }
            else {
                //선택옵션이 아닌 경우 선택 옵션 변수들 처리
                $this->filterData['cartOption'] = false;
                $itStockQty = (int)$orderUtil->get_it_stock_qty_barry($itemInfo['wr_id'],$boardInfo['bo_table']);
                $itNotiStockQty = (int)$orderUtil->get_it_noti_qty_barry($itemInfo['wr_id'],$boardInfo['bo_table']);
            }

            //3. 통보 수량 보다, 주문 수량이 많은지 ??
            if($itStockQty <= $itNotiStockQty){
                $this->logger->error('notice stock qty fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                throw new Exception($itemInfo['wr_subject'].'에 통보된 재고수량이 부족 합니다. 현재고수량 : '.$itStockQty.' 개 통보 재고수량 : '.$itNotiStockQty.' 개',406);
            }

            //4. 상품 또는 선택 옵션 재고 검사
            // 주문 수량이 재고수량보다 많다면 오류
            if ($this->filterData['qty'] > $itStockQty){
                $this->logger->error('stock qty fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                throw new Exception($itemInfo['wr_subject'].'의 재고수량이 부족합니다. 현재고수량 : '.$itStockQty.' 개',406);
            }

            //5. 한정 판매 상품인 경우에는, 개인이 주문 할 수 있는 제한 주문 수량 보다 값을 넘길 수 없음.
            //한정 판매 상품 인 경우 처리
            if($itemInfo['it_limit'] == 1){
                //기간이 만료 되면 주문 안되게 분기 처리 (리스트에도 보여줘야 할지 고민을 ?? )

                //unix time으로 변환 후 비교,strtotime
                $thisUnixDateTime = time();
                $activationUnixDateTime = strtotime($itemInfo['it_limit_activativation_datetime']);
                $deActivationUnixDateTim = strtotime($itemInfo['it_limit_deactivativation_datetime']);

                //주문 일자가 한정 판매 시작 일자 보다 작으면 fail 처리
                if($thisUnixDateTime < $activationUnixDateTime){
                    $this->logger->error('activation date fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                    throw new Exception($itemInfo['wr_subject'].'는 주문 가능한 일자가 아닙니다.',406);
                }

                if($thisUnixDateTime > $deActivationUnixDateTim){
                    $this->logger->error('deactivation date fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                    throw new Exception($itemInfo['wr_subject'].'는 주문 가능한 일자가 만료 되었습니다.',406);
                }

                //주문자 전체 주문 수량 파악
                $memberOrderQty = $orderUtil->getMemberItemQty($itemInfo['wr_id'],$boardInfo['bo_table'],$memberInfo['mb_id']);

                //이미 주문한 수량 비교
                if($memberOrderQty > $itemInfo['it_limit_qty']){
                    $this->logger->error('limit global order qty fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                    throw new Exception($itemInfo['wr_subject'].'의 구매 가능한 제한 구매 수량을 초과 하였습니다. 1 인당 제한 : '.$itemInfo['it_limit_qty'].' 개, 내가 이미 주문한 수량:'.$memberOrderQty.' 개',406);
                }

                //현 주문 수량 비교
                if(($memberOrderQty + $this->filterData['qty']) > $itemInfo['it_limit_qty']){
                    $this->logger->error('limit this qty fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
                    throw new Exception($itemInfo['wr_subject'].'의 구매 가능한 제한 구매 수량을 초과 하였습니다. 1 인당 제한 : '.$itemInfo['it_limit_qty'].' 개, 내가 이미 주문한 수량:'.$memberOrderQty.' 개',406);
                }
            }

            //$itemPrice -> 주문 당시 상품 판매가 저장
            //만약 임의적으로 승인 된 상품의 판매 금액이 변경 되진 않겠지만, 환율에 의한 자동 계산 시간대에 주문을 한다면 금액이 변동 되기 때문에 주문을 막는다.
            //선택 옵션이 있는 상품이라면, 선택 옵션 금액도 build
            $uniqId = false;
            if($this->filterData['cartPriceType'] == 'e-TP3'){
                $itemPrice = $itemInfo['wr_1'];
                //레거시 DB에 TP3,MC,TP3MC 타입으로 들어갔기 때문에 주문 타입을 build 해준다. order view 페이지에서는 e-TP3, e-MC 등으로 가상 코인을 붙임.
                $buildCartPriceType = 'TP3';
                //각 타입마다 선택옵션 처리하는 부분이 들어갔는데.. 축약이 필요로 해보임.
                if(isset($selectOptionInfo)){
                    $itemSelectOptionPrice = $selectOptionInfo['io_price_etp3'];
                }
                else{
                    //선택 상품이 없는 경우, 선택 옵션 info false 처리 insert 에서 아무것도 안들어감.
                    $selectOptionInfo['io_id'] = $selectOptionInfo['io_type'] = false;
                    $itemSelectOptionPrice = 0;
                }
            }
            else if($this->filterData['cartPriceType'] == 'e-MC'){
                $itemPrice = $itemInfo['wr_2'];
                $buildCartPriceType = 'MC';
                if(isset($selectOptionInfo)){
                    $itemSelectOptionPrice = $selectOptionInfo['io_price_emc'];
                }
                else{
                    $selectOptionInfo['io_id'] = $selectOptionInfo['io_type'] = false;
                    $itemSelectOptionPrice = 0;
                }
            }
            else if($this->filterData['cartPriceType'] == 'KRW'){
                $itemPrice = $itemInfo['wr_10'];
                $buildCartPriceType = 'KRW';
                if(isset($selectOptionInfo)){
                    $itemSelectOptionPrice = $selectOptionInfo['io_price'];
                }
                else{
                    $selectOptionInfo['io_id'] = $selectOptionInfo['io_type'] = false;
                    $itemSelectOptionPrice = 0;
                }
            }
			else if($this->filterData['cartPriceType'] == 'e-KRW'){
                $itemPrice = $itemInfo['wr_3'];
                $buildCartPriceType = 'EKRW';
                if(isset($selectOptionInfo)){
                    $itemSelectOptionPrice = $selectOptionInfo['io_price'];
                }
                else{
                    $selectOptionInfo['io_id'] = $selectOptionInfo['io_type'] = false;
                    $itemSelectOptionPrice = 0;
                }
            }
            else if($this->filterData['cartPriceType'] == 'e-CTC'){
                $itemPrice = $itemInfo['wr_4'];
                $buildCartPriceType = 'ECTC';
                if(isset($selectOptionInfo)){
                    $itemSelectOptionPrice = $selectOptionInfo['io_price'];
                }
                else{
                    $selectOptionInfo['io_id'] = $selectOptionInfo['io_type'] = false;
                    $itemSelectOptionPrice = 0;
                }
            }
            else if($this->filterData['cartPriceType'] == 'CREDITCARD'){
                $itemPrice = $itemInfo['wr_10'];
                $buildCartPriceType = 'CREDITCARD';
                $uniqId = $util->getUniqId('IM');

                if(isset($selectOptionInfo)){
                    $itemSelectOptionPrice = $selectOptionInfo['io_price'];
                }
                else{
                    $selectOptionInfo['io_id'] = $selectOptionInfo['io_type'] = false;
                    $itemSelectOptionPrice = 0;
                }
            }
            else{
                //결제 타입이 잘못 된 경우 앞에서 필터링 하지만 혹시 모르니,.... 처리
                $this->logger->error('price type not vaild');
                throw new Exception('주문 결제 타입이 유효하지 않습니다.(2)',403);
            }

            //만약 임의적으로 승인 된 상품의 판매 금액이 변경 되진 않겠지만, 환율에 의한 자동 계산 시간대에 주문을 한다면 금액이 변동 되기 때문에 주문을 막는다.
            if($this->filterData['cartPrice'] != $itemPrice){
                $this->logger->error('cartPrice error');
                throw new Exception('주문 금액에 문제가 있는 것 같습니다. 다시 주문을 해주세요!',403);
            }

            if($selectOptionInfo['io_id'] !== false){
               if($itemSelectOptionPrice != $this->filterData['cartOptionPrice']){
                   $this->logger->error('cartOptionPrice error');
                   throw new Exception('선택 옵션 주문 금액에 문제가 있는 것 같습니다. 다시 주문을 해주세요!',403);
               }
            }

            //price type이 현금이면, 결제완료로 간주한다.
            //결제 완료 : completePayment, 결제 대기 : waitPayment, 결제 실패 : failPayment
            //프로토 타입에서는 비어 있으면 결제 대기 상태 였습니다. (데이터 일부가 그렇게 남아 있음.)
            if($this->filterData['cartPriceType'] == 'KRW'){
				//2021.10.11 By. 현금 결제 건 결제 완료가 아닌 주문 완료 처리
                $paymentStaus = 'deferredPayment';
                //$paymentStaus = 'completePayment';
            }
            else{
                $paymentStaus = 'waitPayment';
            }

            //레거시는 table이 분리 되어 있음... 작성 요청한 db table 명 build
            $writeTargetTable = 'g5_write_'.$boardInfo['bo_table'];

            //now get datetime
            $nowDateTimeSql = $util->getDateSql();
            //gb order number save
            $nextNumber = $util->getNextNum('g5_write_order');

            //new sql
            $insertProc = $barrydb->createQueryBuilder()
                ->insert('g5_write_order')
                ->setValue('wr_num','?')
                ->setValue('wr_subject','?')
                ->setValue('wr_content','?')
                ->setValue('mb_id','?')
                ->setValue('wr_name','?')
                ->setValue('wr_datetime','?')//5
                ->setValue('wr_last','?')
                ->setValue('wr_ip','?')
                ->setValue('wr_1','?')
                ->setValue('wr_2','?')
                ->setValue('wr_3','?')//10
                ->setValue('wr_4','?')
                ->setValue('wr_5','?')
                ->setValue('wr_6','?')
                ->setValue('wr_7','?')
                ->setValue('wr_8','?')//15
                ->setValue('wr_9','?')
                ->setValue('wr_10','?')
                ->setValue('wr_11','?')
                ->setValue('wr_12','?')
                ->setValue('wr_price_type','?')//20
                ->setValue('ct_option','?')
                ->setValue('wr_ct_price','?')
                ->setValue('io_id','?')
                ->setValue('io_type','?')
                ->setValue('wr_io_price','?')//25
                ->setValue('od_item_price','?')
                ->setValue('od_number', '?')
				->setValue('od_per_rate','?')
                ->setValue('od_rate','?')
                ->setValue('od_member_address','?')
                ->setValue('od_memo','?')
                ->setParameter(0,$nextNumber)
                ->setParameter(1,$memberInfo['mb_name'].'님('.$memberInfo['mb_id'].')의 주문 입니다. 수령인('.$this->filterData['recvPhone'].'):'.$this->filterData['recvName'])
                ->setParameter(2,1)//컨텐츠는 굳이 안넣어도 되는데,,, 구분용으로 숫자값만 삽입.
                ->setParameter(3,$memberInfo['mb_id'])
                ->setParameter(4,$memberInfo['mb_name'])
                ->setParameter(5,$nowDateTimeSql)
                ->setParameter(6,$nowDateTimeSql)
                ->setParameter(7,$_SERVER["REMOTE_ADDR"])
                ->setParameter(8,$itemInfo['wr_id'])
                ->setParameter(9,$sellerInfo['mb_name'])
                ->setParameter(10,$sellerInfo['mb_id'])
                ->setParameter(11,$memberInfo['mb_name'])
                ->setParameter(12,$memberInfo['mb_id'])
                ->setParameter(13,$this->filterData['qty'])
                ->setParameter(14,$this->filterData['address'])
                ->setParameter(15,$sellerInfo['mb_1'])
                ->setParameter(16,$boardInfo['bo_table'])
                ->setParameter(17,$paymentStaus)
                ->setParameter(18,$this->filterData['recvName'])
                ->setParameter(19,$this->filterData['recvPhone'])
                ->setParameter(20,$buildCartPriceType)
                ->setParameter(21,$this->filterData['cartOption'])
                ->setParameter(22,$itemPrice)
                ->setParameter(23,$selectOptionInfo['io_id'])
                ->setParameter(24,$selectOptionInfo['io_type'])
                ->setParameter(25,$itemSelectOptionPrice)
                ->setParameter(26,$itemPrice)
                ->setParameter(27,$uniqId)
                ->setParameter(28,(string)$itemInfo['it_per_rate'])
                ->setParameter(29,(string)$itemInfo['it_rate'])
                ->setParameter(30,$this->filterData['memberAddress'])
                ->setParameter(31,$this->filterData['odMemo'])
                ->execute();
            if(!$insertProc){
                $this->logger->error('goods(item) order upload error');
                throw new Exception('주문에 실패하였습니다.',406);
            }

            $orderId = $barrydb->lastInsertId();

            //GB parent 에 update (게시판 기능을 사용하는게 아니라.. 사실 사용 안해도 됨.. 하지만 레거시 DB 무결성을 위해...)
            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_order')
                ->set('wr_parent', '?')
                ->where('wr_id = ?')
                ->setParameter(0,$orderId)
                ->setParameter(1,$orderId)
                ->execute();
            if(!$updateProc){
                $this->logger->error('goods(item) order parent error');
                throw new Exception('알 수 없는 요청 입니다.',403);
            }

            $this->logger->alert('order/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table']);
            //정상 오더가 되었으면 주소를 멤버 테이블에 저장한다
            if($this->filterData['addressSave'] == 'on'){
                //3자리씩 zip 코드 분리
                $zipOper01 = substr($this->filterData['zip'], 0, 3);
                $zipOper02 = substr($this->filterData['zip'], 3, 3);
                $barrydb->createQueryBuilder()
                    ->update('g5_member')
                    ->set('mb_zip1','?')
                    ->set('mb_zip2','?')
                    ->set('mb_addr1','?')
                    ->set('mb_addr2','?')
                    ->set('mb_addr3','?')
                    ->set('mb_addr_jibeon','?')
                    ->where('mb_no = ?')
                    ->setParameter(0,$zipOper01)
                    ->setParameter(1,$zipOper02)
                    ->setParameter(2,$this->filterData['addr1'])
                    ->setParameter(3,$this->filterData['addr2'])
                    ->setParameter(4,$this->filterData['addr3'])
                    ->setParameter(5,$this->filterData['jibun'])
                    ->setParameter(6,$memberInfo['mb_no'])
                    ->execute();
            }

            //주문자 주소, 주소저장 체크 박스 클릭시
            if($this->filterData['memberAddressSave'] == 'on'){
                //3자리씩 zip 코드 분리
                $zipOper01 = substr($this->filterData['memberZip'], 0, 3);
                $zipOper02 = substr($this->filterData['memberZip'], 3, 3);
                $barrydb->createQueryBuilder()
                    ->update('g5_member')
                    ->set('mb_member_zip1','?')
                    ->set('mb_member_zip2','?')
                    ->set('mb_member_addr1','?')
                    ->set('mb_member_addr2','?')
                    ->set('mb_member_addr3','?')
                    ->set('mb_member_addr_jibeon','?')
                    ->where('mb_no = ?')
                    ->setParameter(0,$zipOper01)
                    ->setParameter(1,$zipOper02)
                    ->setParameter(2,$this->filterData['memberAddr1'])
                    ->setParameter(3,$this->filterData['memberAddr2'])
                    ->setParameter(4,$this->filterData['memberAddr3'])
                    ->setParameter(5,$this->filterData['memberJibun'])
                    ->setParameter(6,$memberInfo['mb_no'])
                    ->execute();
            }

            //5. 재고 확인이 완료 되고 주문이 들어간다면, 품절 상태를 확인하여, 재고 없거나 통보 재고 수량이 더 크거나 같으면 품절 처리를 한다. (단일 상품 품절 처리, 선택 옵션은 모든 값이 품절이면 품절 처리)
            //(현금 처리 입니다, e-coin 결제는 따로 API에서 확인 함.)
            if($itemInfo['it_soldout'] == 0){
                if ($itemInfo['it_option_subject']) {
                    $itStockQty = (int)$orderUtil->get_list_option_stock_qty_barry($itemInfo['wr_id'], $boardInfo['bo_table']);
                    $itNotiStockQty = (int)$orderUtil->get_list_option_noti_qty_barry($itemInfo['wr_id'], $boardInfo['bo_table']);
                }
                else {
                    $itStockQty = (int)$orderUtil->get_it_stock_qty_barry($itemInfo['wr_id'],$boardInfo['bo_table']);
                    $itNotiStockQty = (int)$orderUtil->get_it_noti_qty_barry($itemInfo['wr_id'],$boardInfo['bo_table']);
                }
                $this->logger->info('결제 완료 된 order 재고 값 재고:'.$itStockQty.'/통보:'.$itNotiStockQty);

                if($itStockQty <= 0 || $itStockQty <= $itNotiStockQty){
                    $soldoutProc = $barrydb->createQueryBuilder()
                        ->update($writeTargetTable)
                        ->set('it_soldout',1)
                        ->where('wr_id = ?')
                        ->setParameter(0, $itemInfo['wr_id'])
                        ->execute();
                    if(!$soldoutProc){
                        $this->logger->error('soldout status modify complete fail/'.$memberInfo['mb_id'].'/qty:'.$this->filterData['qty'].'/item wrid:'.$itemInfo['wr_id'].'/bo_table:'.$boardInfo['bo_table'].'/it_stock_qty:'.$itStockQty.'/it_noti_stock_qty:'.$itNotiStockQty);
                        throw new Exception('품절 처리 실패!',404);
                    }
                }
            }

            $this->logger->alert('goods(item) order upload!!'.$memberInfo['mb_id'].'/order ID:'.$orderId);
            return array('code' => 200, 'orderMsg' => '상품 주문을 완료 하였습니다.', 'orderId' => $orderId);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getCode().$e->getMessage());
            $this->logger->error('item user order upload fail!/'.$e->getCode().'/'.$e->getMessage().'/'.print_r($this->filterData,true));
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}