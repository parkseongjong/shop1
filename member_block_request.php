<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

if (isset($_POST['ckey']) && isset($_POST['mb_id']) && isset($_POST['blockdays'])) {

    $ckey = trim($_POST['ckey']);
    $mb_id = trim($_POST['mb_id']);
    $blockdays = trim($_POST['blockdays']);

    ## 인증키 검사
    if ($ckey != 'ctctoken') {
        echo "No auth";

    } if (empty($blockdays) && $blockdays!=='0') {
        echo "필수값 누락. (기간)";

    } else {

        ## 회원의 등급을 3으로 수정한다.

        $sql = "select * from g5_member where mb_id = '".$mb_id."'";
        $row = sql_fetch($sql);

        if (!$row || !$row['mb_no'] || !$row['mb_level']) {
            echo "No data";

        } else {

            $mb_no = $row['mb_no'];
            $blockdays = (int)$blockdays;
            $now = date('Y-m-d');

            if ($blockdays==0) {
                sql_query("update g5_member set mb_block_date = '0000-00-00' where mb_no = {$mb_no}");
            } else {
                $dist = date('Y-m-d', strtotime($now.'+'.$blockdays.'days'));
                sql_query("update g5_member set mb_block_date = '".$dist."' where mb_no = {$mb_no}");
            }

            echo "Success";
        }
    }

} else {
    echo "No parameters";
}
