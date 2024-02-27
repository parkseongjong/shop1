<?php
include_once('./_common.php');

$ret = array('error'=>'fail');

if ($is_guest) {
    echo json_encode(array('error'=>'로그인 되어 있지 않습니다.'));
    exit();
}

if (!isset($_GET['code']) || !isset($_GET['bo_table']) || !isset($_GET['wr_id'])) {
    echo json_encode(array('error'=>'필수 값이 없습니다.'));
    exit();
}

$bo_table = $_GET['bo_table'];
$wr_id = $_GET['wr_id'];
$code = $_GET['code'];

$sql = "select * from g5_write_{$bo_table} where wr_id='{$wr_id}'";
$row = sql_fetch($sql);

if (!$row) {
    echo json_encode(array('error'=>'데이터가 존재하지 않습니다.'));
    exit();
}

if ($row['mb_id']!=$member['mb_id']) {
    echo json_encode(array('error'=>'본인의 데이터만 변경할 수 있습니다.'));
    exit();
}

if ($code=='recover') {
    sql_query("update g5_write_{$bo_table} set del_yn = 'N' where wr_id = '".$wr_id."'");

    echo json_encode(array('msg'=>'복구되었습니다.'));
    exit();

}
else if ($code=='remove') {
    sql_query("update g5_write_{$bo_table} set del_yn = 'Y' where wr_id = '".$wr_id."'");

    echo json_encode(array('msg'=>'삭제되었습니다.'));
    exit();

}
else if ($code=='stockOnline') {
    sql_query("update g5_write_{$bo_table} set it_soldout = 0 where wr_id = '".$wr_id."'");

    echo json_encode(array('msg'=>'재고 있음으로 상태가 변경 되었습니다.'));
    exit();

}
else if ($code=='stockOffline') {
    sql_query("update g5_write_{$bo_table} set it_soldout = 1 where wr_id = '".$wr_id."'");

    echo json_encode(array('msg'=>'재고 없음으로 상태가 변경 되었습니다.'));
    exit();

}
else {
    echo json_encode(array('error'=>'필수 값이 잘못되었습니다.'));
    exit();
}
