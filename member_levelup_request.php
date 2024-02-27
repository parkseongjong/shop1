<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

if (isset($_POST['ckey']) && isset($_POST['userid'])) {

    $ckey = trim($_POST['ckey']);
    $userid = trim($_POST['userid']);

    ## 인증키 검사
    if ($ckey != 'ctctoken') {
        echo "No auth";

    } else {

        ## 회원의 등급을 3으로 수정한다.

        $sql = "select * from g5_member where mb_id = '$userid'";
        $row = sql_fetch($sql);

        if (!$row || !$row['mb_no'] || !$row['mb_level']) {
            echo "No data";

        } else {
            $mb_no = $row['mb_no'];
            $mb_level = round($row['mb_level']);

            if ($mb_level < 3) {
                sql_query("update g5_member set mb_level = '3' where mb_no = {$mb_no}");
            }
            echo "Success";
        }
    }

} else {
    echo "No parameters";
}
