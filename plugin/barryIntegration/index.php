<?php
/*
*    베리베리스몰 주문완료 페이지
*      
*    index.php
*/
include_once('./_common.php');
$g5['title'] = '내가 주문한 내역 상세';
include_once('./_head.php');
include_once(G5_PLUGIN_PATH.'/barryIntegration/config.php');

try{
    
    if($member['mb_id'] == ''){
        throw new Exception('비정상적인 접근 입니다.');
        exit();
    }
    //상품 주문 완료 시 wr_id 값 날아옴
    $id = filter_input(INPUT_GET, 'wr_id', FILTER_SANITIZE_SPECIAL_CHARS);
    //bo_table이 나눠져 있어서 bo_table 값도 받아야함;;;
    $target_bo_table = filter_input(INPUT_GET, 'target_bo_table', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $dbObject = $db->prepare('SELECT * FROM g5_write_order  WHERE wr_id = ? AND mb_id = ?');
    //$dbObject->bindValue(1, $name, PDO::PARAM_STR);
    $dbObject->bindValue(1, $id, PDO::PARAM_INT);
    $dbObject->bindValue(2, $member['mb_id'], PDO::PARAM_STR);
    $dbObject->execute();

    if($dbObject->rowCount() == 0){
        throw new Exception('존재하지 않거나 만료되었습니다.');
    }
    else{
        $row = $dbObject->fetch();
        if(G5_IS_MOBILE){
            $integration_skin_page = "/view.mobile.skin.php";

        }
        else{
            $integration_skin_page = "/view.pc.skin.php";
        }
    }
   
}
catch(Exception $e){
    $errMsg = $e->getMessage();
    $integration_skin_page = "/error.mobile.skin.php";
} 
    include_once ($integration_skin_path.$integration_skin_page);
    unset($dbObject,$row,$id,$bo_table,$errMsg);

    echo PHP_EOL.'<!-- IntegrationSkin : '.$integration['skin'].' -->'.PHP_EOL;

include_once('./_tail.php');
?>