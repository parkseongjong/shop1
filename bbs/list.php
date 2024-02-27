<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;

$barryDb = barryGbDb::getInstance();
$barryDb = $barryDb->init();
// 분류 사용 여부
$is_category = false;
$category_option = '';

//price_type(GET) 필터링
if (isset($_REQUEST['price_type'])) {
    if($price_type == 'TP3'){
        $price_type = 'TP3';
    }
    elseif($price_type == 'MC'){
        $price_type = 'MC';
    }
    elseif($price_type == 'EKRW'){
        $price_type = 'EKRW';
    }
    elseif($price_type == 'ECTC'){
        $price_type = 'ECTC';
    }
    elseif($price_type == 'KRW'){
        $price_type = 'KRW';
    }
    else{
        $price_type = false;
    }
}
else {
    $price_type = false;
}

$sop = strtolower($sop);
if ($sop != 'and' && $sop != 'or')
    $sop = 'and';

// 분류 선택 또는 검색어가 있다면
$stx = trim($stx);
//검색인지 아닌지 구분하는 변수 초기화
$is_search_bbs = false;

if ($sca || $stx || $stx === '0') {     //검색이면
    $is_search_bbs = true;      //검색구분변수 true 지정
    $sql_search = get_sql_search($sca, $sfl, $stx, $sop);

    // 가장 작은 번호를 얻어서 변수에 저장 (하단의 페이징에서 사용)
    $sql = " select MIN(wr_num) as min_wr_num from {$write_table} ";
    $row = sql_fetch($sql);
    $min_spt = (int)$row['min_wr_num'];

    if (!$spt) $spt = $min_spt;

    //검색 결과인데, price 필터가 있는경우
    if($price_type == 'KRW'){  // and wr_price_type = 'KRW' and wr_price_type = 'CREDITCARD'
        //$sql_search .= " and del_yn = 'N' and (it_publish = 1 or it_publish = 99) and wr_price_type like '%".$price_type."%' ";
        $sql_search .= " and del_yn = 'N' and (it_publish = 1 or it_publish = 99) and wr_price_type in('KRW' , 'CREDITCARD') ";
    }
    else if($price_type){
        $sql_search .= " and del_yn = 'N' and (it_publish = 1 or it_publish = 99) and wr_price_type like '%".$price_type."%' ";
    }
    else{
        $sql_search .= " and del_yn = 'N' and (it_publish = 1 or it_publish = 99)";
    }
    $sql_search .= " and (wr_num between {$spt} and ({$spt} + {$config['cf_search_part']})) ";

    // 원글만 얻는다. (코멘트의 내용도 검색하기 위함)
    // 라엘님 제안 코드로 대체 http://sir.kr/g5_bug/2922
    $sql = " SELECT COUNT(DISTINCT `wr_parent`) AS `cnt` FROM {$write_table} WHERE {$sql_search} ";
    $row = sql_fetch($sql);
    $total_count = $row['cnt'];
    /*
    $sql = " select distinct wr_parent from {$write_table} where {$sql_search} ";
    $result = sql_query($sql);
    $total_count = sql_num_rows($result);
    */


}// 검색 조건 추가, 201015, YMJ ,,,, 검색 결과가 아니고, 일반 리스트에서 price 필터가 있는경우
else if ( $price_type ) {
    if($price_type == 'KRW'){
        $sql = " SELECT COUNT(*) AS `cnt` FROM {$write_table} WHERE wr_is_comment = 0 and del_yn = 'N' and (it_publish = 1 or it_publish = 99) and wr_price_type in('KRW' , 'CREDITCARD')";
    }
    else{
        $sql = " SELECT COUNT(*) AS `cnt` FROM {$write_table} WHERE wr_is_comment = 0 and del_yn = 'N' and (it_publish = 1 or it_publish = 99) and wr_price_type like '%".$price_type."%' ";
    }

    $row = sql_fetch($sql);
    $total_count = $row['cnt'];

}
else {
    $sql_search = " del_yn = 'N' and (it_publish = 1 or it_publish = 99)";

    //검색 상태가 아닐 때, 일반 보드 토탈 값을 구할 시 비활성화 상품은 보여주지 않는다. By. OJT
    //$total_count = $board['bo_count_write'];
    $sql = " SELECT COUNT(*) AS `cnt` FROM {$write_table} WHERE wr_is_comment = 0 and del_yn = 'N' and (it_publish = 1 or it_publish = 99)";
    $row = sql_fetch($sql);
    $total_count = $row['cnt'];


}

if(G5_IS_MOBILE) {
    $page_rows = $board['bo_mobile_page_rows'];
    $list_page_rows = $board['bo_mobile_page_rows'];
} else {
    $page_rows = $board['bo_page_rows'];
    $list_page_rows = $board['bo_page_rows'];
}

if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)

// 년도 2자리
$today2 = G5_TIME_YMD;

$list = array();
$i = 0;
$notice_count = 0;
$notice_array = array();

//본사 계정 최상위 처리 할 게시물 카운트
$firstCorp_count = 0;
//본사 계정 최상위 처리 카운트 할 배열
$firstCorp_array = array();

// 본사 계정 최상위 처리, it_publish 상태를 따르지 않는다.
if (!$is_search_bbs) {
    $arr_firstCorp = BARRY_CORP_ARRAY;
    $from_firstCorp_idx = ($page - 1) * $page_rows;
    if($from_firstCorp_idx < 0){
        $from_firstCorp_idx = 0;
    }
    $firstCorp_for_count = count($arr_firstCorp);
    for ($k=0; $k<$firstCorp_for_count; $k++) {
        if (trim($arr_firstCorp[$k]) == '') continue;

        $row = $barryDb->createQueryBuilder()
            ->select('*')
            ->from($write_table)
            ->where('mb_id = ?')
            ->andWhere('del_yn = "N"')
            ->orWhere('it_publish = 99')
            ->andWhere('it_publish = 1')
            ->orderBy('wr_updatetime','desc')
            ->addorderBy('wr_id','desc')
            ->setParameter(0,$arr_firstCorp[$k])
            ->execute()->fetchAll();

        if (!$row) continue;
        foreach ($row as $value){
            $firstCorp_array[] = $value['wr_id'];
        }

        /*
        $row = sql_fetch(" select * from {$write_table} where mb_id = '{$arr_firstCorp[$k]}' AND del_yn = 'N' and (it_publish = 1 or it_publish = 99)");
        if (!$row['wr_id']) continue;

        $firstCorp_array[] = $row['wr_id'];
        */



        if($k < $from_firstCorp_idx) continue;

        foreach ($row as $value) {
            $list[$i] = get_list($value, $board, $board_skin_url, G5_IS_MOBILE ? $board['bo_mobile_subject_len'] : $board['bo_subject_len']);
            $list[$i]['is_notice'] = false;

            $i++;
            $firstCorp_count++;
//            if($firstCorp_count >= $list_page_rows)
//                break;
        }
        /*
            $list[$i] = get_list($row, $board, $board_skin_url, G5_IS_MOBILE ? $board['bo_mobile_subject_len'] : $board['bo_subject_len']);
            $list[$i]['is_notice'] = false;

            $i++;
            $firstCorp_count++;
        */
        if($firstCorp_count >= $list_page_rows)
            break;
    }
}

// 공지 처리
if (!$is_search_bbs) {
    $arr_notice = explode(',', trim($board['bo_notice']));
    $from_notice_idx = ($page - 1) * $page_rows;
    if($from_notice_idx < 0)
        $from_notice_idx = 0;
    $board_notice_count = count($arr_notice);

    for ($k=0; $k<$board_notice_count; $k++) {
        if (trim($arr_notice[$k]) == '') continue;

        $row = sql_fetch(" select * from {$write_table} where wr_id = '{$arr_notice[$k]}' ");
        if (!$row['wr_id']) continue;

        $notice_array[] = $row['wr_id'];

        if($k < $from_notice_idx) continue;

        $list[$i] = get_list($row, $board, $board_skin_url, G5_IS_MOBILE ? $board['bo_mobile_subject_len'] : $board['bo_subject_len']);
        $list[$i]['is_notice'] = true;

        $i++;
        $notice_count++;

        if($notice_count >= $list_page_rows)
            break;
    }
}

$total_page  = ceil($total_count / $page_rows);  // 전체 페이지 계산
$from_record = ($page - 1) * $page_rows; // 시작 열을 구함
// 공지글이 있으면 변수에 반영
if(!empty($notice_array)) {
    $from_record -= count($notice_array);
    $from_record -= count($firstCorp_array);

    if($from_record < 0)
        $from_record = 0;

    if($notice_count > 0)
        $page_rows -= $notice_count;

    if($firstCorp_count > 0)
        $page_rows -= $firstCorp_count;

    if($page_rows < 0)
        $page_rows = $list_page_rows;
}

// 관리자라면 CheckBox 보임
$is_checkbox = false;
if ($is_member && ($is_admin == 'super' || $group['gr_admin'] == $member['mb_id'] || $board['bo_admin'] == $member['mb_id']))
    $is_checkbox = true;

// 정렬에 사용하는 QUERY_STRING
$qstr2 = 'bo_table='.$bo_table.'&amp;sop='.$sop;

// 0 으로 나눌시 오류를 방지하기 위하여 값이 없으면 1 로 설정
$bo_gallery_cols = $board['bo_gallery_cols'] ? $board['bo_gallery_cols'] : 1;
$td_width = (int)(100 / $bo_gallery_cols);

// 정렬
// 인덱스 필드가 아니면 정렬에 사용하지 않음
//if (!$sst || ($sst && !(strstr($sst, 'wr_id') || strstr($sst, "wr_datetime")))) {
if (!$sst) {
    if ($board['bo_sort_field']) {
        $sst = $board['bo_sort_field'];
    } else {
        $sst  = "wr_num, wr_reply";
        $sod = "";
    }
} else {
    $board_sort_fields = get_board_sort_fields($board, 1);
    if (!$sod && array_key_exists($sst, $board_sort_fields)) {
        $sst = $board_sort_fields[$sst];
    } else {
        // 게시물 리스트의 정렬 대상 필드가 아니라면 공백으로 (nasca 님 09.06.16)
        // 리스트에서 다른 필드로 정렬을 하려면 아래의 코드에 해당 필드를 추가하세요.
        // $sst = preg_match("/^(wr_subject|wr_datetime|wr_hit|wr_good|wr_nogood)$/i", $sst) ? $sst : "";
        $sst = preg_match("/^(wr_datetime|wr_hit|wr_good|wr_nogood)$/i", $sst) ? $sst : "";
    }
}

if(!$sst)
    $sst  = "wr_num, wr_reply";
if ($sst) {
    $sql_order = " order by wr_updatetime desc, wr_id desc, {$sst} {$sod} ";
}

if ($is_search_bbs) {
    //검색 결과인데, price 필터가 있는경우
    if($price_type){
        if($price_type == 'KRW'){
            $sql = " select distinct wr_parent from {$write_table} where {$sql_search} and wr_price_type in('KRW' , 'CREDITCARD') {$sql_order} limit {$from_record}, $page_rows ";
        }
        else{
            $sql = " select distinct wr_parent from {$write_table} where {$sql_search} and wr_price_type like '%".$price_type."%' {$sql_order} limit {$from_record}, $page_rows ";
        }
    }
    else{
        $sql = " select distinct wr_parent from {$write_table} where {$sql_search} {$sql_order} limit {$from_record}, $page_rows ";
    }

}
else {
    $sql = " select * from {$write_table} where wr_is_comment = 0 and del_yn = 'N' and (it_publish = 1 or it_publish = 99) ";
    if(!empty($notice_array)){
        $sql .= " and wr_id not in (".implode(', ', $notice_array).") ";
    }
    if(!empty($firstCorp_array)){
        $sql .= " and wr_id not in (".implode(', ', $firstCorp_array).") ";
    }

    // 검색 조건 추가, 201015, YMJ ,,,,, 검색 결과가 아니고, 일반 리스트에서 price 필터가 있는경우
    if ( $price_type ) {
        if($price_type == 'KRW'){
            $sql .= " and wr_price_type in('KRW' , 'CREDITCARD') ";
        }
        else{
            $sql .= " and wr_price_type like '%".$price_type."%' ";
        }

    }


    $sql .= " {$sql_order} limit {$from_record}, $page_rows ";
}
//echo $sql;
// 페이지의 공지개수가 목록수 보다 작을 때만 실행


if($page_rows > 0) {
    $result = sql_query($sql);

    $k = 0;

    while ($row = sql_fetch_array($result))
    {
        // 검색일 경우 wr_id만 얻었으므로 다시 한행을 얻는다
        if ($is_search_bbs)
            $row = sql_fetch(" select * from {$write_table} where wr_id = '{$row['wr_parent']}' ");

        $list[$i] = get_list($row, $board, $board_skin_url, G5_IS_MOBILE ? $board['bo_mobile_subject_len'] : $board['bo_subject_len']);
        if (strstr($sfl, 'subject')) {
            $list[$i]['subject'] = search_font($stx, $list[$i]['subject']);
        }
        $list[$i]['is_notice'] = false;
        $list_num = $total_count - ($page - 1) * $list_page_rows - $notice_count;
        $list[$i]['num'] = $list_num - $k;

        $i++;
        $k++;
    }
}
g5_latest_cache_data($board['bo_table'], $list);

$write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, get_pretty_url($bo_table, '', $qstr.'&amp;page='));

$list_href = '';
$prev_part_href = '';
$next_part_href = '';
if ($is_search_bbs) {
    $list_href = get_pretty_url($bo_table);

    $patterns = array('#&amp;page=[0-9]*#', '#&amp;spt=[0-9\-]*#');

    //if ($prev_spt >= $min_spt)
    $prev_spt = $spt - $config['cf_search_part'];
    if (isset($min_spt) && $prev_spt >= $min_spt) {
        $qstr1 = preg_replace($patterns, '', $qstr);
        $prev_part_href = get_pretty_url($bo_table,0,$qstr1.'&amp;spt='.$prev_spt.'&amp;page=1');
        $write_pages = page_insertbefore($write_pages, '<a href="'.$prev_part_href.'" class="pg_page pg_prev">이전검색</a>');
    }

    $next_spt = $spt + $config['cf_search_part'];
    if ($next_spt < 0) {
        $qstr1 = preg_replace($patterns, '', $qstr);
        $next_part_href = get_pretty_url($bo_table,0,$qstr1.'&amp;spt='.$next_spt.'&amp;page=1');
        $write_pages = page_insertafter($write_pages, '<a href="'.$next_part_href.'" class="pg_page pg_end">다음검색</a>');
    }
}

echo $board['mb_level'];
$write_href = '';
if ($member['mb_level'] >= $board['bo_write_level']) {
    $write_href = short_url_clean(G5_BBS_URL.'/write.php?bo_table='.$bo_table);
}

$nobr_begin = $nobr_end = "";
if (preg_match("/gecko|firefox/i", $_SERVER['HTTP_USER_AGENT'])) {
    $nobr_begin = '<nobr>';
    $nobr_end   = '</nobr>';
}

// RSS 보기 사용에 체크가 되어 있어야 RSS 보기 가능 061106
$rss_href = '';
if ($board['bo_use_rss_view']) {
    $rss_href = G5_BBS_URL.'/rss.php?bo_table='.$bo_table;
}

// $board_skin_path = /onefamily11/www/mobile/skin/board/20190409_basic_multi

$stx = get_text(stripslashes($stx));

include_once($board_skin_path.'/list.skin.php');
//include_once($board_skin_path.'/list.skin_20200520');
?>
