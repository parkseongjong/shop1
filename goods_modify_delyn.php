<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

$response = array(
    'code' => '99',
    'msg' => 'Fail'
);

if (isset($_POST['ckey'])) {

    $ckey   = trim($_POST['ckey']);
    $wr_id  = $_POST['wr_id'];         // 상품 id
    $s_cate = $_POST['s_cate'];        //
    $del_yn = $_POST['del_yn'];

    ## 인증키 검사
    if ($ckey != 'ctctoken' || $cate != '') {
        $response['msg'] = "Auth Error";

    } else if ($del_yn != 'Y' && $del_yn != 'N') {
        $response['msg'] = "Auth Error";

    } else {

        if (empty($s_cate) || $s_cate=='shop1') $s_cate = 'Shop';  // shop1은 초기에 삭제한 상품을 이 테이블로 옮겨놓은 것들이 몇개 있음.

        ## SQL

        $sql = "select del_yn, wr_subject from g5_write_".$s_cate." where wr_id = '".$wr_id."'";
        $result = sql_fetch($sql);

        if (!$result['del_yn']) {
            $response['msg'] = "Data reading error";

        } else {

            if ($del_yn=='Y') {
                $now = date('Y-m-d H:i:s');
                sql_query("update g5_write_".$s_cate." set del_yn = '".$del_yn."', wr_updatetime = '".$now."' where wr_id = '".$wr_id."'");
            } else {
                sql_query("update g5_write_".$s_cate." set del_yn = '".$del_yn."' where wr_id = '".$wr_id."'");
            }

            $response = array(
                'code' => '00',
                'msg'  => 'success',
                'subject' => $result['wr_subject']
            );
        }
    }

} else {
    $response['msg'] = "Missing parameters";
}

echo json_encode($response);
