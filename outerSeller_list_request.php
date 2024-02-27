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
    $cate = trim($_POST['cate']);
    $page = trim($_POST['page']);
    $num_rows = trim($_POST['num_rows']);
    $order_key = trim($_POST['order_key']);
    $order_dir = trim($_POST['order_dir']);
    $s_keyword = trim($_POST['s_keyword']);
    $itemSearchType = $_POST['itemSearchType']; //상품 삭제 여부 타입
    $s_cate = trim($_POST['s_cate']);
    ## 인증키 검사
    if ($ckey != 'ctctoken') {
        $response['msg'] = "Auth Error";

    } else {

        $now = date('Y-m-d');
        
        ## SQL

        //상품 삭제 여부 Build
        if($itemSearchType == 'Y'){
            $itemSearchType = 'del_yn = "Y"';
        }
        else{
            $itemSearchType = 'del_yn = "N"';
        }
        
        $limit = " limit ".(($page-1)*$num_rows).",".$num_rows;
        $order_by = " order by wr_id desc";



        if (empty($cate)) $cate = 'Shop';

        if (!empty($order_key) && !empty($order_dir)) {
            if ($order_key=='wr_1') {
                $order_by = " order by cast({$order_key} as unsigned) {$order_dir}";
            } else {
                $order_by = " order by {$order_key} {$order_dir}";
            }
        } else {
            $order_by = " order by wr_updatetime desc, wr_id desc";
        }

        if (!empty($s_keyword)) {
            $where = " where (ca_name like '%".$s_keyword."%' or wr_subject like '%".$s_keyword."%' or mb_id like '%".$s_keyword."%' or
                        wr_name like '%".$s_keyword."%')AND ".$itemSearchType." ";
        }
        else{
            $where = ' where '.$itemSearchType;
        }
        //$where .= "AND (mb_id in('0260911125','0260911126','0260911128', '0260911127', '0260911129', '0260911131', '0260911132'))";
        $where .= "AND (mb_id in(".$corpList."))";

        //전체 카테고리 , (전체 테이블 ...)
        if($cate == 'allCategory'){
            //$table = array('Shop','car','estate','market');
            $table = BARRY_MAIN_TABLE_ARRAY;
            $count = 0;
            $query = ('
                SELECT * FROM g5_write_Shop
                '.$where.'
            ');
            foreach ($table as $key => $value){
                $sql = "SELECT count(*) AS cnt FROM g5_write_".$value.$where;
                $row = sql_fetch($sql);

                $count += $row['cnt'];

                //맨 처음 배열 테이블은 유니온 select 메인 query이므로 제외한다.
                if($key != 0){
                    $query .= ('
                        union all 
                                (
                                    SELECT *
                                    FROM g5_write_'.$value.'
                                    '.$where.'
                                )
                    ');
                }
            }

            $result = sql_query($query.$order_by.$limit);
        }
        else{

            $sql = "select count(*) as cnt from g5_write_".$cate.$where;
            $row = sql_fetch($sql);

            $count = (!$row || !$row['cnt']) ? 0 : $row['cnt'];

            $sql = "select * from g5_write_".$cate.$where.$order_by.$limit;
            $result = sql_query($sql);
        }
        $return = array();

        while ($row = sql_fetch_array($result)) {
            // 썸네일 파일 검사
            $sql2 = "select * from g5_board_file where bo_table='".$row['it_me_table']."' and wr_id='".$row['wr_id']."' order by bf_no asc";
            $result2 = sql_query($sql2);

            $url = 'https://www.barrybarries.kr/data/file/'.$row['it_me_table'].'/';
            $dir = 'data/file/'.$row['it_me_table'].'/';
            $thumb = array();
            while ($row2 = sql_fetch_array($result2)) {
                $f = explode('.', $row2['bf_file']);
                if (count($f) == 2) {
                    $fth = 'thumb-'.$f[0].'_150x100.';
                    if ($row2['bf_type']=='1') $fth .= 'gif';
                    else if ($row2['bf_type']=='2') $fth .= 'jpg';
                    else if ($row2['bf_type']=='3') $fth .= 'png';
                    else $fth .= $f[1];
                }
                if (file_exists($dir.$fth)) {
                    $thumb[] = array(
                        'bf_source' => $row2['bf_source'],
                        'bf_file' => $row2['bf_file'],
                        'thumb' => $fth,
                        'url' => $url,
                        'bf_filesize' => $row2['bf_filesize'],
                        'bf_type' => $row2['bf_type'],
                    );
                    break;
                }
            }
            if (empty($thumb)) {
                while ($row2 = sql_fetch_array($result2)) {
                    $fth = $row2['bf_file'];
                    if (file_exists($dir.$fth)) {
                        $thumb[] = array(
                            'bf_source' => $row2['bf_source'],
                            'bf_file' => $row2['bf_file'],
                            'thumb' => $fth,
                            'url' => $url,
                            'bf_filesize' => $row2['bf_filesize'],
                            'bf_type' => $row2['bf_type'],
                        );
                        break;
                    }
                }
            }
            $row['files'] = $thumb;

            // 징계관련 여부
            $sql3 = "select mb_block_date from g5_member where mb_id='".$row['mb_id']."'";
            $result3 = sql_fetch($sql3);

            $block_date = '';
            $now = date('Y-m-d');

            if (!empty($result3) && $result3['mb_block_date'] != '0000-00-00') {
                if ($result3['mb_block_date'] >= $now) {
                    $block_date = $result3['mb_block_date'];
                }
            }

            $row['block_date'] = $block_date;

            $return[] = $row;
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
