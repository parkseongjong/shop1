<?php
include_once('./_common.php');

$ret = array('err'=>'fail');

if ($is_guest) {
    $ret['err'] = '회원만 이용하실 수 있습니다.';

} else {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        if (!isset($_POST['wr_id']) || !isset($_POST['bo_table'])) {
            $ret['err'] = '필수 값이 없습니다.';

        } else {

            $wr_id = trim($_POST['wr_id']);
            $bo_table = trim($_POST['bo_table']);

            $mb_id = $member['mb_id'];  // 로그인한 회원
            $today = date('Y-m-d H:i:s');

            $sql = "select ms_id from g5_scrap where mb_id = '{$mb_id}' and bo_table ='{$bo_table}' and wr_id = '{$wr_id}'";
            $row = sql_fetch($sql);
            if (!$row) {
                $sql_insert = "insert into g5_scrap values ('', '{$mb_id}', '{$bo_table}', '{$wr_id}', '{$today}')";
                $last_id = sql_query($sql_insert);
                $ret = array('success'=>'add');
            } else {
                $ms_id = $row['ms_id'];
                $sql_update = "delete from g5_scrap where ms_id = '{$ms_id}'";
                sql_query($sql_update);
                $ret = array('success'=>'remove');
            }
        }
    }
}

echo json_encode($ret);
