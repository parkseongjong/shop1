<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

$barryCorpCount = count(BARRY_CORP_ARRAY)-1;
foreach (BARRY_CORP_ARRAY as $key => $value){
    if($key < $barryCorpCount){
        $corpList .= "'".$value."',";
    }
    else{
        $corpList .= "'".$value."'";
    }
}

$response = array(
    'code' => '99',
    'msg' => 'Fail'
);

if (isset($_POST['ckey'])) {

    $ckey = trim($_POST['ckey']);
	
	//엑셀 다운로드가 아닌, list 확인 할 때만 사용 START
	$page = trim($_POST['page']);
    $num_rows = trim($_POST['num_rows']);
	//엑셀 다운로드가 아닌, list 확인 할 때만 사용 END
	
    $order_key = trim($_POST['order_key']);
    $order_dir = trim($_POST['order_dir']);
    $s_cate = trim($_POST['s_cate']);
    $wr_status = trim($_POST['wr_status']);

    $dateStart = trim($_POST['dateStart']);
    $dateEnd = trim($_POST['dateEnd']);

    $type = trim($_POST['type']);


    $s_keyword = trim($_POST['s_keyword']);
    $wr_id = $_POST['wr_id'];         // 상품 id

    ## 인증키 검사
    if ($ckey != 'ctctoken') {
        $response['msg'] = "Auth Error";
    }
    else {
        if ($s_cate=='' || $s_cate=='shop1') $s_cate = 'Shop';  // shop1 은 초기에 삭제한 상품을 이 테이블로 옮겨놓은 것들이 몇개 있음.

        ## SQL
        $where = " where A.wr_9 = '".$s_cate."' AND (A.wr_3 in(".$corpList."))"; // 오더,
		//엑셀 다운로드가 아닌, list 확인 할 때만 사용 START
		if($page && $num_rows){
			$limit = " limit ".(($page-1)*$num_rows).",".$num_rows;
		}
		//엑셀 다운로드가 아닌, list 확인 할 때만 사용 END
        $order_by = " order by A.wr_id desc";

        if ($wr_status!='') {
            $where .= " and wr_status='{$wr_status}' ";
        }

        if (!empty($order_key) && !empty($order_dir)) {
            if ($order_key=='wr_1') {
                $order_by = " order by cast(A.{$order_key} as unsigned) {$order_dir}";
            } else {
                $order_by = " order by A.{$order_key} {$order_dir}";
            }
        }

        if (!empty($s_keyword)) {
            $where .= " and (A.wr_name like '%".$s_keyword."%' or A.wr_2 like '%".$s_keyword."%' or A.wr_5 like '%".$s_keyword."%' or
                        A.wr_3 like '%".$s_keyword."%' or A.wr_7 like '%".$s_keyword."%' or A.wr_8 like '%".$s_keyword."%')";

            if (!empty($wr_id)) {
                $where .= " and A.wr_1 = '".$wr_id."' ";
            }
        } else {
            if (!empty($wr_id)) {
                $where .= " and A.wr_1 = '".$wr_id."' ";
            }
        }

        if(!empty($dateStart) && !empty($dateEnd)){
            $where .= " and A.wr_datetime >= '".$dateStart." 00:00:00' and A.wr_datetime <= '".$dateEnd." 23:59:59'";
        }

        if($type == 'completePayment'){
            $where .= "and A.wr_10 = 'completePayment'";
        }

        $sql = "select count(*) as cnt from g5_write_order A ".$where;
        $row = sql_fetch($sql);

        $count = (!$row || !$row['cnt']) ? 0 : $row['cnt'];

        // B.wr_price_type,
        $sql = "
        select A.*, B.wr_subject, B.wr_1 as tp3, B.wr_2 as mc, B.wr_10 as krw2, B.wr_retail_price, B.wr_subject
        from g5_write_order A 
        left join g5_write_".$s_cate." B on A.wr_1=B.wr_id ".$where.$order_by.$limit;
        $result = sql_query($sql);

        $return = array();

        //var_dump($sql);

        while ($row = sql_fetch_array($result)) {
            $return[] = $row;
        }

        $response = array(
            'code' => '00',
            'msg' => 'Success',
//            'qr' => $sql,
            'count' => $count,
            'list' => $return,
        );
    }

} else {
    $response['msg'] = "Missing parameters";
}

echo json_encode($response);
