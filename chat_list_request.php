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
    $page = trim($_POST['page']);
    $num_rows = trim($_POST['num_rows']);
    $order_key = trim($_POST['order_key']);
    $order_dir = trim($_POST['order_dir']);
    $s_keyword = trim($_POST['s_keyword']);

    ## 인증키 검사
    if ($ckey != 'ctctoken') {
        $response['msg'] = "Auth Error";

    } else {

        ## SQL

        $where = "";
        $limit = " limit ".(($page-1)*$num_rows).",".$num_rows;
        $order_by = " order by A.me_id desc";

        if (!empty($order_key) && !empty($order_dir)) {
            $order_by = " order by A.{$order_key} {$order_dir}";
        }

        if (!empty($s_keyword)) {
            $where = " where (A.me_memo like '%".$s_keyword."%' or A.me_write_mb_id like '%".$s_keyword."%') ";
        }

        $sql = "select count(*) as cnt from g5_memo_new A".$where;
        $row = sql_fetch($sql);

        $count = (!$row || !$row['cnt']) ? 0 : $row['cnt'];


        $sql = "select A.*, B.mb_name from g5_memo_new A left join g5_member B on A.me_write_mb_id=B.mb_id ".$where.$order_by.$limit;
        $result = sql_query($sql);

        $day_letter = array("일","월","화","수","목","금","토");

        $return = array();

        while ($row = sql_fetch_array($result)) {

            $wdate = str_replace('-', '.', substr($row['me_write_datetime'], 5, 5));
            if ($wdate[0]=='0') $wdate = substr($wdate, 1);

            $whour = round(substr($row['me_write_datetime'], 11, 2));
            $wmin = substr($row['me_write_datetime'], 14, 2);

            if ($whour>12) {
                $apm = '오후';
                $whour -= 12;
            } else {
                $apm = '오전';
            }

            $day_w = date('w', strtotime($row['me_write_datetime']));

            $return[] = array(
                'me_id' => $row['me_id'],
                'mb_name' => $row['mb_name'],
                'writer_id' => $row['me_write_mb_id'],
                'apm' => $apm,
                'whour' => $whour,
                'wmin' => $wmin,
                'wdate' => $wdate.' ('.$day_letter[$day_w].') '.$apm.' '.$whour.':'.$wmin,
                'me_memo' => $row['me_memo'],
            );
        }

        $response = array(
            'code' => '00',
            'msg' => 'Success',
            'count' => $count,
            'list' => $return
        );

    }

} else {
    $response['msg'] = "Missing parameters";
}

echo json_encode($response);
