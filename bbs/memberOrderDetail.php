<?php
/*
 *
 *
 *  내가 주문한 내역은 plugin\barryIntegration 에서 임시 vue로 동작중
 *
 *
 */
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;
try{
    if ($is_guest) {
        throw new Exception('로그인 되어 있지 않습니다.',9999);
    }

    //common 에서 int cast 되어서 필터 됨
    if (!isset($wr_id)) {
        throw new Exception('필수 값이 없습니다.',9999);
    }
    else{
        $filterData['wr_id'] = $wr_id;
    }
    $db = barryGbDb::singletonMethod();
    $barrydb = $db-> init();

    $g5['title'] = '상품 판매 내역 상세';
    include_once('./_head.php');

    //bo_table을 얻기 위해 temp select
    //gb에서 member mb_id 필터링 하니까.. 이곳에서 넣지 않겠음 추 후 API 화 할 때는 꼭 권한 체크 할 것
    $memberOrderDetailTemp = $barrydb->createQueryBuilder()
        ->select('wr_9 as meTable')
        ->from('g5_write_order')
        ->where('wr_id = ?')
        ->andWhere('wr_3 = ?')
        ->setParameter(0,$filterData['wr_id'])
        ->setParameter(1,$member['mb_id'])
        ->execute()->fetch();
    if(!$memberOrderDetailTemp){
        throw new Exception('비정상적인 접근 입니다.',9999);
    }

    $memberOrderDetailInfo = $barrydb->createQueryBuilder()
        ->select ('A.*, B.wr_subject as itemSubject, B.wr_id as itemId')
        ->from('g5_write_order','A')
        ->innerJoin('A', 'g5_write_'.$memberOrderDetailTemp['meTable'],'B','A.wr_1 = B.wr_id')
        ->where('A.wr_id = ?')
        ->setParameter(0,$filterData['wr_id'])
        ->execute()->fetch();
    unset($memberOrderDetailTemp);
    if(!$memberOrderDetailInfo){
        throw new Exception('조회 데이터가 없습니다.');
    }

    $memberOrderDetailInfo['thumb'] = get_list_thumbnail($memberOrderDetailInfo['wr_9'], $memberOrderDetailInfo['wr_1'], 150, 150, false, true);

     //도로명 R, 지번 J 구분 빌드
     if(substr($memberOrderDetailInfo['wr_7'], -1) == 'R'){

         $memberOrderDetailInfo['receiverAddress'] = substr_replace($memberOrderDetailInfo['wr_7'], '[도로명]',-1,1);
     }
     else if(substr($memberOrderDetailInfo['wr_7'], -1) == 'J'){
         $memberOrderDetailInfo['receiverAddress'] = substr_replace($memberOrderDetailInfo['wr_7'], '[지번]',-1,1);
     }
     else{
         $memberOrderDetailInfo['receiverAddress'] = $memberOrderDetailInfo['wr_7'];
     }

     //상품 주문 금액 합산
     $memberOrderDetailInfo['itemCartTotalPrice'] = (($memberOrderDetailInfo['wr_ct_price']+$memberOrderDetailInfo['wr_io_price'])*$memberOrderDetailInfo['wr_6']);

     //코인 타입 빌드
    if($memberOrderDetailInfo['wr_price_type'] == 'TP3'){
        $memberOrderDetailInfo['paymentType'] = 'e-TP3';
    }
    else if($memberOrderDetailInfo['wr_price_type'] == 'MC') {
        $memberOrderDetailInfo['paymentType'] = 'e-MC';
    }
    else if($memberOrderDetailInfo['wr_price_type'] == 'KRW') {
        $memberOrderDetailInfo['paymentType'] = '원(현금)';
    }
    else if($memberOrderDetailInfo['wr_price_type'] == 'EKRW') {
        $memberOrderDetailInfo['paymentType'] = 'e-KRW';
    }
    else if($memberOrderDetailInfo['wr_price_type'] == 'ECTC') {
        $memberOrderDetailInfo['paymentType'] = 'e-CTC';
    }
    else if($memberOrderDetailInfo['wr_price_type'] == 'creditCard'){
        $memberOrderDetailInfo['paymentType'] = '원(카드)';
    }

    if($memberOrderDetailInfo['boi_id'] > 0){
        $memberOrderDetailInfo['invoiceInfo'] = $barrydb->createQueryBuilder()
            ->select ('*')
            ->from('barry_order_invoice')
            ->where('boi_id = ?')
            ->setParameter(0,$memberOrderDetailInfo['boi_id'])
            ->orderBy('boi_id','DESC')
            ->execute()->fetch();
        if(!$memberOrderDetailInfo['invoiceInfo']){
            $memberOrderDetailInfo['invoiceInfo'] = false;
        }
    }
    else{
        $memberOrderDetailInfo['invoiceInfo'] = false;
    }

    $memberOrderDetailNumber = $barrydb->createQueryBuilder()
        ->select('bpps_auth_number','bpps_response')
        ->from('barry_pg_payment_status')
        ->where('bpps_order_id = ?')
		->andWhere('bpps_status = "complete"')
        ->setParameter(0,$memberOrderDetailInfo['wr_id'])
        ->execute()->fetch();
    if(!$memberOrderDetailNumber){
        $memberOrderDetailNumber = false;
    }

    $cardNameJsonType = json_decode($memberOrderDetailNumber['bpps_response'],true);

    //gb member을 안쓰고 굳이 선언한 이유는, gb 환경이 아닌 타 환경에서 사용하기 위함...
    $memberOrderDetailInfo['memberInfo'] = $member;

    echo('<!-- orderDetail START -->');
            include_once($member_skin_path.'/seller/memberOrderDetail.skin.php');
    echo('<!-- orderDetail END -->');

}
catch (Exception $e){
    if($e->getCode() == 9999){
        alert($e->getMessage());
    }
    else{
        alert('관리자에 문의해주세요.');
    }

    //var_dump($e->getMessage());
}

include_once('./_tail.php');
?>
