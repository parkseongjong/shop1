<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

$response = array(
    'code' => '99',
    'msg' => 'Fail'
);

if (isset($_POST['ckey'])) {

    $ckey = trim($_POST['ckey']);
    $s_cate = trim($_POST['s_cate']);
    $wr_id = $_POST['wr_id'];         // 상품 id

    ## 인증키 검사
    if ($ckey != 'ctctoken') {
        $response['msg'] = "Auth Error";

    } else {

        if (empty($s_cate) || $s_cate=='shop1') $s_cate = 'Shop';  // shop1은 초기에 삭제한 상품을 이 테이블로 옮겨놓은 것들이 몇개 있음.

        ## SQL

        $sql = "select * from g5_write_".$s_cate." where wr_id = '".$wr_id."'";
        $result = sql_fetch($sql);

        if (!$result['wr_id']) {
            $response['msg'] = "Data not found";
        } else {
            $response = array(
                'code' => '00',
                'msg' => 'Success',
                'data' => $result
            );
        }
    }

} else {
    $response['msg'] = "Missing parameters";
}

echo json_encode($response);
