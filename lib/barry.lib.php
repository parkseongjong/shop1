<?php
if (!defined('_GNUBOARD_')) exit;

/*

    베리베리몰 함수 입니다.

*/

// 200 success
// 403 auth Fail
// 404 Fail 
function jsonResponseCommon($code){
    if($code == 200){
        $return = array(
            'code' => $code,
            'msg' => 'success'
        );
    }
    else if($code == 403 ){
        $return = array(
            'code' => $code,
            'msg' => 'auth fail'
        );
    }
    else{
        $return = array(
            'code' => $code,
            'msg' => 'fail'
        );
    }
    return json_encode($return);
}

function jsonResponseMulti($code, $array, $msg=''){
    if($code == 200){
        $msg = 'success';
    }
    else if($code == 403 ){
        $msg = 'auth fail';
    }
    else if($code == 404){
        $msg = 'fail';
    }

    $return = array(
        'code' => $code,
        'msg' => $msg
    );
    foreach($array as $key => $value){
        $return[$key] = $value;
    }
    return json_encode($return);
}

function jsonResponseCustom($code, $msg){
    $return = array(
        'code' => $code,
        'msg' => $msg
    );
    return json_encode($return);
}

//관리자가 재고 확인 디버깅을 위한 함수 입니다.
//view 에서만 사용 가능...
function adminGetStockInfo($bo_table){

    global $view, $member;

    $tempsql = " select SUM(wr_6) as sum_qty
               from g5_write_order
              where wr_1 = '".$view['wr_id']."'
                and wr_9 = '".$bo_table."'
                and io_id = ''
                and ct_stock_use = 0
                and wr_status in ('order', 'delivery')
                and wr_10 = 'completePayment' ";
    $temprow = sql_fetch($tempsql);

    $tempsql = " select SUM(wr_6) as sum_qty
               from g5_write_order
              where wr_1 = '".$view['wr_id']."'
                and wr_9 = '".$bo_table."'
                and ct_stock_use = 0
                and wr_status in ('order', 'delivery')
                and wr_10 = 'completePayment' ";
    $temprow2 = sql_fetch($tempsql);

    return array(
        '실재고(가재고) 창고재고수량 - 주문대기수량:' => get_it_stock_qty_barry($view['wr_id'],$bo_table),
        '실재고(가재고) 창고재고수량 - 주문대기수량(카테고리):' => get_list_option_stock_qty_barry($view['wr_id'],$bo_table),
        '단일 상품 주문 수' => $temprow['sum_qty'],
        '카테고리 상품 여부' => $view['it_option_subject'],
        '카테고리 상품 주문 수' => $temprow2['sum_qty'],
        '설정재고(창고재고)' => $view['it_stock_qty'],
        '내가 주문 한거' => getMemberItemQty($view['wr_id'],$bo_table,$member['mb_id']),
        '인당 제한 수량' => $view['it_limit_qty'],
        '제한 수량 사용 여부' => $view['it_limit']
    );
}

// 상품의 재고 (창고재고수량 - 주문대기수량)
//it_id는 wr_id 값,
//wr_6은 개수 입니다.
//wr_1은 item의 wr_id 입니다.
function get_it_stock_qty_barry($it_id, $table)
{
    global $g5;

    //테이블 명은 각 write에 유입 될 때 필터링 되니 이 곳에선 따로 필터링을 안합니다.

    $sql = " select it_stock_qty from g5_write_{$table} where wr_id = '$it_id' ";
    $row = sql_fetch($sql);
    $jaego = (int)$row['it_stock_qty'];

    // 재고에서 빼지 않았고 주문인것만
    $sql = " select SUM(wr_6) as sum_qty
               from g5_write_order
              where wr_1 = '$it_id'
                and wr_9 = '".$table."'
                and io_id = ''
                and ct_stock_use = 0
                and wr_status in ('order', 'delivery') 
                and wr_10 = 'completePayment' ";
    $row = sql_fetch($sql);
        $daegi = (int)$row['sum_qty'];

    return $jaego - $daegi;
}

function get_it_noti_qty_barry($it_id, $table)
{
    global $g5;

    //테이블 명은 각 write에 유입 될 때 필터링 되니 이 곳에선 따로 필터링을 안합니다.

    $sql = " select it_noti_qty from g5_write_{$table} where wr_id = '$it_id' ";
    $row = sql_fetch($sql);
    $noticeQty = (int)$row['it_noti_qty'];

    return $noticeQty;
}

//개인 주문 값 개수 조회
function getMemberItemQty($it_id, $table,$mb_id)
{
    global $g5;

    //테이블 명은 각 write에 유입 될 때 필터링 되니 이 곳에선 따로 필터링을 안합니다.

    // 재고에서 빼지 않았고 주문인것만
    $sql = " select SUM(wr_6) as sum_qty
               from g5_write_order
              where wr_1 = '$it_id'
                and wr_9 = '".$table."'
                and ct_stock_use = 0
                and wr_status in ('order', 'delivery') 
                and wr_10 = 'completePayment' 
                and mb_id = '$mb_id'";

    $row = sql_fetch($sql);

    return (int)$row['sum_qty'];
}

// 옵션의 재고 (창고재고수량 - 주문대기수량)
//it_id는 wr_id 값,
//$type은 사용하지 않습니다. 레거시 barry 에서는 추가 옵션 사용을 안하고 선택 옵션만 사용 합니다.
//wr_6은 개수 입니다.
//wr_1은 item의 wr_id 입니다.
function get_option_stock_qty_barry($it_id, $io_id, $table)
{
    global $g5;

    $sql = " select io_stock_qty
                from g5_shop_item_option
                where it_id = '$it_id' and io_id = '$io_id' and io_me_table = '$table'and io_type = '0'";
    $row = sql_fetch($sql);
    $jaego = (int)$row['io_stock_qty'];


    // 재고에서 빼지 않았고 주문인것만
    $sql = " select SUM(wr_6) as sum_qty
               from g5_write_order
              where wr_1 = '$it_id'
                and wr_9 = '$table'
                and io_id = '$io_id'
                and io_type = 0
                and ct_stock_use = 0
                and wr_status in ('order', 'delivery') 
                and wr_10 = 'completePayment' ";

    $row = sql_fetch($sql);
    $daegi = (int)$row['sum_qty'];


    return $jaego - $daegi;
}

function get_list_option_stock_qty_barry($it_id, $table)
{
    global $g5;

    $sql = " select io_stock_qty
                from g5_shop_item_option
                where it_id = '$it_id' and io_me_table = '$table' and io_type = '0'";
    $result = sql_query($sql);
    while($row = sql_fetch_array($result)) {
        $jaego += (int)$row['io_stock_qty'];
    }


    // 재고에서 빼지 않았고 주문인것만
    $sql = " select SUM(wr_6) as sum_qty
               from g5_write_order
              where wr_1 = '$it_id'
                and wr_9 = '$table'
                and io_type = 0
                and ct_stock_use = 0
                and wr_status in ('order', 'delivery') 
                and wr_10 = 'completePayment' ";

    $result = sql_query($sql);
    while($row = sql_fetch_array($result)) {
        $daegi += (int)$row['sum_qty'];
    }

    return $jaego - $daegi;
}

function get_option_noti_qty_barry($it_id, $io_id)
{
    global $g5;

    $sql = " select io_noti_qty
                from g5_shop_item_option
                where it_id = '$it_id' and io_id = '$io_id' and io_type = '0'";
    $row = sql_fetch($sql);
    $noticeQty = (int)$row['io_noti_qty'];

    return $noticeQty;
}

function get_list_option_noti_qty_barry($it_id)
{
    global $g5;

    $sql = " select io_noti_qty
                from g5_shop_item_option
                where it_id = '$it_id' and io_type = '0' and io_use = '1' ";
    $result = sql_query($sql);
    while($row = sql_fetch_array($result)){
        $noticeQty += (int)$row['io_noti_qty'];
    }
    return $noticeQty;
}

?>