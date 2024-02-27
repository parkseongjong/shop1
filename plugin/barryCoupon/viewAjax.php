<?php
/*
*    베리베리스몰 주문완료 Ajax 페이지
*
*    viewAjax.php
*/
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/barryIntegration/config.php');
include_once(G5_LIB_PATH.'/barry.lib.php');
$request_body = file_get_contents('php://input');
$data = json_decode($request_body);
//application/x-www-form-urlencoded 으로 데이터를 받음.

$wr_id = filter_var($data->wr_id, FILTER_SANITIZE_SPECIAL_CHARS);
$target_bo_table = filter_var($data->barry_view_target_bo_table, FILTER_SANITIZE_SPECIAL_CHARS);
// 200 success
// 403 auth Fail
// 404 Fail 
// TO-DO 
try {
    
    if(empty($wr_id)){
        throw new Exception('올바른 접근이 필요 합니다.');
    }
    $targetBo_table_array = array('Shop','car','estate','market','used');
    //bo_table은 사용자가 서칭하게끔 하지 않는다.
    if(in_array($target_bo_table,$targetBo_table_array)) {
        switch ($target_bo_table) {
            case 'Shop':
                $boTableFilter = 'Shop';
                break;
            case 'car':
                $boTableFilter = 'car';
                break;
            case 'estate':
                $boTableFilter = 'estate';
                break;
            case 'market':
                $boTableFilter = 'market';
                break;
            case 'used':
                $boTableFilter = 'used';
                break;
        }
    }
    unset($targetBo_table,$targetBo_table_array);
    $ret = array();
    $dbObject = $db->prepare('
        SELECT A.wr_id, A.wr_datetime, A.wr_1, A.wr_2, A.wr_3, A.wr_4, A.wr_5, A.wr_6, A.wr_7, A.wr_8, A.wr_10, A.wr_11, A.wr_12, A.wr_status, A.wr_price_type, A.wr_ct_price, A.ct_option, A.wr_io_price, B.it_soldout, B.mb_id, B.wr_subject, B.wr_content, B.wr_seo_title, B.del_yn
        FROM g5_write_order as A
        INNER join g5_write_'.$boTableFilter.' as B
        WHERE B.wr_id = A.wr_1 AND A.mb_id = ? AND A.wr_id = ?
        ORDER BY A.wr_id DESC
    ');
    $dbObject->bindValue(1, $member['mb_id'], PDO::PARAM_STR);
    $dbObject->bindValue(2, $wr_id, PDO::PARAM_INT);
    $dbObject->execute();

    if($dbObject->rowCount() == 0){
        array_push($ret,jsonResponseCommon(404));
    }
    else{
        for ($i=0; $row=$dbObject->fetch(); $i++) {

            //도로명 R, 지번 J 구분 빌드
            if(substr($row['wr_7'], -1) == 'R'){

                $receiverAddress = substr_replace($row['wr_7'], '[도로명]',-1,1);
            }
            else if(substr($row['wr_7'], -1) == 'J'){
                $receiverAddress = substr_replace($row['wr_7'], '[지번]',-1,1);
            }
            else{
                $receiverAddress = $row['wr_7'];
            }

            //상품 주문 금액 합산
            $itemCartTotalPrice = (($row['wr_ct_price']+$row['wr_io_price'])*$row['wr_6']);
            
            //판매자 월렛 정보 빌드
            $sellerInfo['sellerWalletAddress'] = $row['wr_8'];
            
            //결제수단 치환 판매자 월렛 QR 삽입 ( DB에는 MC, TP3, KRW로 저장되고 있음.)
            if($row['wr_price_type'] == 'TP3'){
                $paymentType = 'e-TP3';
                $sellerInfo['sellerWalletQrAddress'] = 'https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl='.$sellerInfo['sellerWalletAddress'].'?amount='.$itemCartTotalPrice.'|etp3';
            } 
            else if($row['wr_price_type'] == 'MC'){
                $paymentType = 'e-MC';
                $sellerInfo['sellerWalletQrAddress'] = 'https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl='.$sellerInfo['sellerWalletAddress'].'?amount='.$itemCartTotalPrice.'|emc';
            }
            else{
                $paymentType = '원(현금)';
                $sellerInfo['sellerWalletQrAddress'] = false;
            }
            
            $thumb = get_list_thumbnail($boTableFilter, $row['wr_1'], 150, 150, false, true);
            if($thumb === false){
                $thumb['src'] = false;
            }
            $itemContent = utf8_strcut(strip_tags(conv_content($row['wr_content'],1)),50);
            //$itemContent = conv_content(cut_str($row['wr_seo_title'], 30),0);//seo 데이터로 보여주기
            
            array_push($ret,
                       array(
                            'msg'=>'success',
                            'code'=>200,
                            'id'=>$row['wr_id'],
                            'itemSubject'=>$row['wr_subject'],
                            'itemContent'=>$itemContent,
                            'orderDate'=>$row['wr_datetime'],
                            'sellerName'=>$row['wr_2'],
                            'sellerTelNumber'=>$row['wr_3'],
                            'buyerName'=>$row['wr_4'],
                            'buyerTelNumber'=>$row['wr_5'],
                            'imgSrc'=>$thumb['src'],
                            'receiverAddress'=>$receiverAddress,
                            'receiverName'=>$row['wr_11'],
                            'receiverTelNumber'=>$row['wr_12'],
                            'orderStatus'=>$row['wr_status'],
                            'paymentType'=>$paymentType,
                            'paymentRealType'=>$row['wr_price_type'],
                            'qty'=>$row['wr_6'],
                            'itemCartTotalPrice'=>$itemCartTotalPrice,
                            'itemCartPrice'=>$row['wr_ct_price'],
                            'itemSelectOption'=>$row['ct_option'],
                            'itemSelectOptionPrice'=>$row['wr_io_price'],
                            'sellerWalletAddress'=>$sellerInfo['sellerWalletAddress'],
                            'sellerWalletQrAddress'=>$sellerInfo['sellerWalletQrAddress'],
                            'paymentStatus'=>$row['wr_10'],
                            'itemUse'=>$row['del_yn'],
                            'itemSoldout'=>$row['it_soldout'],
                            ));
            }
    }

    echo(json_encode($ret));
}
catch(Exception $e){
    http_response_code(404);
}
    unset($ret,$request_body,$data,$dbObject,$name,$hp);
    exit();
?>