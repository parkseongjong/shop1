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
    $id = trim($_POST['id']);

    ## 인증키 검사
    if (empty($id) || $ckey != 'ctctoken') {
        $response['msg'] = "Auth Error";

    } else {

        $sql = "select * from g5_write_order where wr_id = '{$id}'";
        $row = sql_fetch($sql);

        if ($row) {
            $target_id = $row['wr_3'];    // 판매자 아이디
            $target_name = $row['wr_2'];
            $mb_id = $row['wr_5'];      // 구매자 회원
            $mb_name = $row['wr_name'];

            $sql = "select mr_id from g5_memo_room where me_recv_mb_id = '{$target_id}' and me_send_mb_id = '{$mb_id}'";
            $row = sql_fetch($sql);

            if ($row) {

                $day_letter = array("일","월","화","수","목","금","토");

                $sql = "select * from g5_memo_new where mr_id = '".$row['mr_id']."' order by me_id asc";
                $result = sql_query($sql);

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
                    'seller' => $target_id,
                    'seller_name' => $target_name,
                    'buyer' => $mb_id,
                    'buyer_name' => $mb_name,
                    'list' => $return
                );

            } else {

                $response = array(
                    'code' => '00',
                    'msg' => 'Success',
                    'seller' => $target_id,
                    'seller_name' => $target_name,
                    'buyer' => $mb_id,
                    'buyer_name' => $mb_name,
                    'list' => array()
                );

                //$response['msg'] = "No room";
            }

        } else {
            $response['msg'] = "Data not found";
        }
    }

} else {
    $response['msg'] = "Missing parameters";
}

echo json_encode($response);
