<?php
include_once('./_common.php');
try{
    $html_title = '링크 &gt; '.conv_subject($write['wr_subject'], 255);

    //짧은 주소를 사용 할 때 다시 한번 체크가 필요로 함.
    $refererSegment = parse_url($_SERVER['HTTP_REFERER']);
    $urlSegment = parse_url(get_pretty_url($board['bo_table'], ''));

    //클릭 어뷰징 방지를 위해 리퍼러 체크만 시도, queryString이 다양하게 들어와서 , bo_table 까지만 비교
    if($refererSegment['host'].$refererSegment['path'].$board['bo_table'] != $urlSegment['host'].$refererSegment['path'].$board['bo_table']){
        throw new Exception('비정상적인 접근 입니다.');
    }

    if (!($bo_table && $wr_id)){
        throw new Exception('값이 제데로 넘어오지 않았습니다.');
    }

    // SQL Injection 예방
    $row = sql_fetch(" select count(*) as cnt from {$g5['write_prefix']}{$board['bo_table']} ", FALSE);
    if (!$row['cnt']){
        throw new Exception('존재하는 게시판이 아닙니다.');
    }

    //레거시 상품이 등록 되는 table만 허용 한다.
    $targetBo_table_array = array('Shop','car','estate','market');
    if(!in_array($board['bo_table'],$targetBo_table_array)) {
        throw new Exception('허용 되지 않은 접근 입니다.');
    }

    //광고 노출 된 클릭 수를 카운팅 해야 하기 때문에, 굳이 중복 클릭을 막을 필요는 없을 것 같음.
    //$ss_name = 'ss_premium_link_'.$bo_table.'_'.$wr_id;

    $row = sql_fetch('
                            select bia_id
                            from barry_item_ads 
                            where bia_item_id = '.$write["wr_id"].'
                            and bia_type = "'.$board["bo_table"].'"
                            ', FALSE);
    if(!$row){
        throw new Exception('잘못된 데이터 입니다.');
    }

    if (empty($_SESSION[$ss_name])) {
        $gbCoupon->adItemLogInsert($row['bia_id'],'click');
        //set_session($ss_name, true);
    }
    goto_url(get_pretty_url($board['bo_table'], $write["wr_id"]));
}
catch (Exception $e){
    echo('<meta http-equiv="refresh" content="0; url='.G5_URL.'"');
    alert_close($e->getMessage());
    exit();
}
?>