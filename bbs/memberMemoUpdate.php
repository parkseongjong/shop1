<?php
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;
include_once(G5_LIB_PATH.'/barry.lib.php');


if ($is_guest) {
    echo jsonResponseCustom(404,'회원만 이용하실 수 있습니다.');

}
else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_POST['target_id']) || !isset($_POST['me_memo'])) {
            echo jsonResponseCustom(404,'필수 값이 없습니다.');
        }
        else {
            $target_id = clean_xss_tags(trim($_POST['target_id'])); // 수신자, 아이디
            $me_memo = clean_xss_tags(trim($_POST['me_memo']));
            $msg_ip = $_SERVER['SERVER_ADDR'];
            $mr_id = clean_xss_tags(trim($_POST['mr_id']));

            //$recv_id = clean_xss_tags(trim($_POST['recv_id']));
            $mb_id = $member['mb_id'];  // 로그인한 회원
            $today = date('Y-m-d H:i:s');

            $sql = "select mr_id from g5_memo_room where me_recv_mb_id = '{$target_id}' and me_send_mb_id = '{$mb_id}'";
            $sql2 = "select mr_id from g5_memo_room where me_recv_mb_id =  '{$mb_id}' and me_send_mb_id = '{$target_id}'";
            $row = sql_fetch($sql);
            $row3 = sql_fetch($sql2);

            if (!$row && !$row3) {
                $sql_insert = "insert into g5_memo_room(me_recv_mb_id, me_send_mb_id, me_create_datetime, memo_time)values ('{$target_id}', '{$mb_id}', '{$today}','{$today}')";
                $row = sql_query($sql_insert);

                $sql = "select mr_id from g5_memo_room where me_recv_mb_id = '{$target_id}' and me_send_mb_id = '{$mb_id}'";
                $row2 = sql_fetch($sql);
                $mr_id = $row2['mr_id'];

            }
            else {
                if($row['mr_id']==null) {
                    $mr_id = $row3['mr_id'];
                }
                else {
                    $mr_id = $row['mr_id'];
                }
            }
            $sql_insert2 = "insert into g5_memo_new(mr_id, me_write_mb_id, me_recv_mb_id, me_write_datetime, me_memo, msg_ip, msg_check) values('{$mr_id}', '{$mb_id}','{$target_id}', '{$today}', '{$me_memo}','{$msg_ip}',0)";
            sql_query("update g5_memo_room set memo_time = '{$today}' where mr_id = '{$mr_id}'");
            sql_query($sql_insert2);

            //if ($row['mb_id'] === $member['mb_id']) $is_owner = true;

            echo jsonResponseMulti(200, array('data'=>$me_memo));
        }
    }
    else{
        echo jsonResponseCommon(404);
    }
}

exit();
?>
