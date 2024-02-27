<?php
include_once('./_common.php');
$html_title = '링크 &gt; '.conv_subject($write['wr_subject'], 255);

if (!($bo_table && $wr_id && $no))
    alert_close('값이 제대로 넘어오지 않았습니다.');

// SQL Injection 예방
$row = sql_fetch(" select count(*) as cnt from {$g5['write_prefix']}{$bo_table} ", FALSE);
if (!$row['cnt'])
    alert_close('존재하는 게시판이 아닙니다.');

if (!$write['wr_link'.$no])
    alert_close('링크가 없습니다.');

$ss_name = 'ss_link_'.$bo_table.'_'.$wr_id.'_'.$no;
if (empty($_SESSION[$ss_name]))
{
    $sql = " update {$g5['write_prefix']}{$bo_table} set wr_link{$no}_hit = wr_link{$no}_hit + 1 where wr_id = '{$wr_id}' ";
    sql_query($sql);

    //미디어 링크 클릭자 데이터 스택 쌓기
    $sql = " SELECT bmuc_id FROM barry_media_user_check WHERE bmuc_board_wr_id = {$wr_id} AND bmuc_mb_no = {$member['mb_no']} AND bmuc_board_table = '{$bo_table}' AND bmuc_link_hit = '{$no}'";
    $selectReturn = sql_query($sql);

    if($selectReturn->num_rows <= 0 ){
        $sql = " INSERT INTO barry_media_user_check SET bmuc_board_wr_id = {$wr_id}, bmuc_mb_no = {$member['mb_no']}, bmuc_board_table = '{$bo_table}', bmuc_link_hit = {$no}, bmuc_datetime = '".G5_TIME_YMDHIS."'";
        sql_query($sql);
    }
    set_session($ss_name, true);
}

goto_url(set_http($write['wr_link'.$no]));
?>