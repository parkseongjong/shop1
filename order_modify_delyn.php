<?php
exit();
/*
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

$response = array(
    'code' => '99',
    'msg' => 'Fail'
);

if (isset($_POST['ckey'])) {

    $ckey = trim($_POST['ckey']);
    $wr_id = $_POST['wr_id'];         // 상품 id
    $cate = $_POST['cate'];         //
    $del_yn = trim($_POST['del_yn']);

    ## 인증키 검사
    if ($ckey != 'ctctoken' || $cate != '') {
        $response['msg'] = "Auth Error";

    } else if ($del_yn != 'Y' && $del_yn != 'N') {{
        $response['msg'] = "Auth Error";

    } else {

        ## SQL

        $sql = "select del_yn, wr_subject from g5_write_Shop where id='".$wr_id."'";
        $row = sql_fetch($sql);

        if (empty($row) || !$row['del_yn']) {
            $response['msg'] = "Data reading error";
        } else {
            $response['msg'] = $row['wr_subject'];
        }

        $response = array(
            'code' => '00',
            'msg' => 'Success',
            'wr_subject' => $wr_subject
        );
    }

} else {
    $response['msg'] = "Missing parameters";
}

echo json_encode($response);
*/
