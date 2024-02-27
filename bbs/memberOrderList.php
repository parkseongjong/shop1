<?php
include_once('./_common.php');
if ($is_guest) {
    alert('로그인 되어 있지 않습니다.');
}
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;

$db = barryGbDb::singletonMethod();
$barrydb = $db-> init();

$g5['title'] = '주문내역';
include_once('./_head.php');
//include_once('./_head.sub.php');

//get type으로 판매자 리스트인지, 구매자 리스트 인지 분기
if(isset($_GET['type'])){
    $memberOrderListType = $_GET['type'];
    if($memberOrderListType == 'user'){
        $memberOrderListType = 'user';
    }
    else{
        $memberOrderListType = 'seller';
    }
}
else{
    $memberOrderListType = 'seller';
}

if (!isset($page) || (isset($page) && $page == 0)) $page = 1;
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)

$page_rows = 8;

//DB는 추후 driver 로 변경하는게 어떨지...?
if($memberOrderListType == 'user'){
    $sql = "select count(*) as cnt from g5_write_order where mb_id='{$member['mb_id']}' AND NOT wr_10 IN('waitPayment','failPayment')";
}
else{
    $sql = "select count(*) as cnt from g5_write_order where wr_3='{$member['mb_id']}' AND NOT wr_10 IN('waitPayment','failPayment') ";
}

$row = sql_fetch($sql);

$total_count = $row['cnt'];

$total_page  = ceil($total_count / $page_rows);  // 전체 페이지 계산

$from_record = ($page - 1) * $page_rows; // 시작 열을 구함
if($from_record < 0) $from_record = 0;

// 회원에게 들어온 주문 전체
if($memberOrderListType == 'user'){
    $sql = "select * from g5_write_order where mb_id='{$member['mb_id']}' AND NOT wr_10 IN('waitPayment','failPayment') order by wr_id desc limit {$from_record}, {$page_rows}";
}
else{
    $sql = "select * from g5_write_order where wr_3='{$member['mb_id']}' AND NOT wr_10 IN('waitPayment','failPayment') order by wr_id desc limit {$from_record}, {$page_rows}";
}

$result = sql_query($sql);

$write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, 'memberOrderList.php?page=','&type='.$memberOrderListType);

$bo_gallery_width = 150;
$bo_gallery_height = 150;

$i = 0;
$memberOrderList = array();
if ($result->num_rows > 0) {
    
     while($row = sql_fetch_array($result)){

        // wr_1 : 상품테입블의 index
        $memberOrderList[$i]['thumb'] = get_list_thumbnail($row['wr_9'], $row['wr_1'], $bo_gallery_width, $bo_gallery_height, false, true);

         //도로명 R, 지번 J 구분 빌드
         if(substr($row['wr_7'], -1) == 'R'){

             $memberOrderList[$i]['receiverAddress'] = substr_replace($row['wr_7'], '[도로명]',-1,1);
         }
         else if(substr($row['wr_7'], -1) == 'J'){
             $memberOrderList[$i]['receiverAddress'] = substr_replace($row['wr_7'], '[지번]',-1,1);
         }
         else{
             $memberOrderList[$i]['receiverAddress'] = $row['wr_7'];
         }

         //상품 주문 금액 합산
         $memberOrderList[$i]['itemCartTotalPrice'] = (($row['wr_ct_price']+$row['wr_io_price'])*$row['wr_6']);

         //코인 타입 빌드
        if($row['wr_price_type'] == 'TP3'){
            $memberOrderList[$i]['paymentType'] = 'e-TP3';
        }
        else if($row['wr_price_type'] == 'MC') {
            $memberOrderList[$i]['paymentType'] = 'e-MC';
        }
        else if($row['wr_price_type'] == 'KRW') {
            $memberOrderList[$i]['paymentType'] = '원(현금)';
        }
        else if($row['wr_price_type'] == 'EKRW') {
            $memberOrderList[$i]['paymentType'] = 'e-KRW';
        }
        else if($row['wr_price_type'] == 'ECTC') {
            $memberOrderList[$i]['paymentType'] = 'e-CTC';
        }
        else if($row['wr_price_type'] == 'creditCard'){
            $memberOrderList[$i]['paymentType'] = '원(카드)';
        }

        //상품 정보 build
        $memberOrderList[$i]['itemInfo'] = $barrydb->createQueryBuilder()
            ->select ('B.wr_subject, B.wr_id')
            ->from('g5_write_order','A')
            ->innerJoin('A', 'g5_write_'.$row['wr_9'],'B','A.wr_1 = B.wr_id')
            ->where('A.wr_id = ?')
            ->setParameter(0,$row['wr_id'])
            ->execute()->fetch();
        if(!$memberOrderList[$i]['itemInfo']){
            $memberOrderList[$i]['itemInfo'] = false;
        }

        //배송 정보 build (배송 정보는 취소 하고 다시 등록하면, 동일 송장이 아닐 때 기존 데이터는 삭제는 안하고 추가 됨,. 고유 id 기준으로 내림차순...)
        if($row['boi_id'] > 0){
            $memberOrderList[$i]['invoiceInfo'] = $barrydb->createQueryBuilder()
                ->select ('*')
                ->from('barry_order_invoice')
                ->where('boi_id = ?')
                ->setParameter(0,$row['boi_id'])
                ->orderBy('boi_id','DESC')
                ->execute()->fetch();
            if(!$memberOrderList[$i]['invoiceInfo']){
                $memberOrderList[$i]['invoiceInfo'] = false;
            }
        }
        else{
            $memberOrderList[$i]['invoiceInfo'] = false;
        }


        //SQL 결과 배열 저장
        foreach($row as $key2 => $value2){
//                var_dump($key2);
//                var_dump($value2);
            $memberOrderList[$i][$key2] = $value2;
        }
         $cardNameJsonType = $barrydb->createQueryBuilder()
             ->select('bpps_response')
             ->from('barry_pg_payment_status')
             ->where('bpps_order_id = ?')
			 ->andWhere('bpps_status = "complete"')
             ->setParameter(0,$memberOrderList[$i]['wr_id'])
             ->execute()->fetch();
         if(!$cardNameJsonType){
             $cardNameJsonType = false;
         }

         if($cardNameJsonType){
             $memberOrderList[$i]['cardInfo'] = json_decode($cardNameJsonType['bpps_response'],true);
         }
         else{
             $memberOrderList[$i]['cardInfo'] = false;
         }

        $i++;
    }
    unset($row);
    $memberOrderList['count'] = (count($memberOrderList));
}
else{
     $memberOrderList['count'] = (count($memberOrderList));
}

//기본 값 상태는 없음....
//$orderListFile = $member_skin_path.'/memberOrderList.skin.php';
//if (!file_exists($orderListFile)){
//    $member_skin_path   = G5_SKIN_PATH.'/member/basic';
//}
echo('<!-- orderlist START -->');
    if($memberOrderListType == 'user'){
        include_once($member_skin_path.'/user/memberOrderList.skin.php');
    }
    else{
        include_once($member_skin_path.'/seller/memberOrderList.skin.php');
    }

echo('<!-- orderlist END -->');

include_once('./_tail.php');
?>
