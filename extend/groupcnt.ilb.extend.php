<?php
if (!defined('_GNUBOARD_')) exit;

// �׷캰 ȸ�� �� ���� ī��Ʈ
/*
   param :
	   $gr_id : �׷���̵�
	   $mb_id : ȸ�����̵�
	   $limitDate : ��ȸ�Ⱓ 0 : ����, 1 : �Ϸ���. 2:��Ʋ��.. "" : �Ⱓ����.
	   $w : ���� 
	   $r : ���
	   $c : �ڸ�Ʈ 
	   $arrays : �迭�� �ޱ�
   use
      grouprowcnt('group', 'admin', '0', true, true, true)
*/
function grouprowcnt($gr_id, $mb_id, $limitDate=0, $w=true, $r=true, $c=true, $arrays=false)
{
	
	global $g5;

	$group_sql = "";
	$sql_and = "";

	if($limitDate !="") { //��ü�˻��� ��������..
		if($limitDate==0) //����
			$sql_and = "and date(wr_datetime) = date(now())";
		else //������ ���� ���ϱ���
			$sql_and = " and date(wr_datetime) between date(subdate(now(),INTERVAL {$limitDate} DAY)) and date(now()) ";
	}

	$sql_and  .= ($w && $r && $c)?"":"";
	$sql_and  .= ($w && $r && !$c)?" AND (wr_is_comment = 0 OR wr_reply != '') ":"";
	$sql_and  .= ($w && !$r && $c)?" AND (wr_is_comment = 0 AND wr_reply = '' OR wr_comment != '0') ":"";
	$sql_and  .= (!$w && $r && $c)?" AND (wr_reply != '' OR wr_comment != '0') ":"";
	$sql_and  .= ($w && !$r && !$c)?" AND (wr_is_comment = 0) ":"";
	$sql_and  .= (!$w && $r && !$c)?" AND (wr_reply != '') ":"";
	$sql_and  .= (!$w && !$r && $c)?" AND (wr_comment != '0') ":"";
	if(!$w && !$r && !$c){ //���� ��� ����.
		return 0;
		exit;
	}

	$sql = "select * from g5_board where gr_id = '{$gr_id}' ";
	$result = sql_query($sql);

	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$tmp_write_table[] = $g5['write_prefix'].$row['bo_table'];
		$tmp_bo_table[] = $row['bo_table'];
	}
	
	$group_sql = "select wr_subject,wr_id,bo_table from ( ";
	for($i=0; $i < count($tmp_write_table); $i++) {
		$group_sql .= " select wr_subject,wr_id, '{$tmp_bo_table[$i]}' as bo_table from {$tmp_write_table[$i]} as ".$g5['write_prefix'].$i." where mb_id='{$mb_id}' {$sql_and}  ";
		if($i != count($tmp_write_table)-1)
			 $group_sql .= " union all "; 
	}
	$group_sql .= ") as a";

	$result = @mysql_query($group_sql, $g5['connect_db']);
	
	if($arrays){
		while ($row = sql_fetch_array($result)) {
			$rows[] = $row;
		}
	}else{ 
		$rows = mysql_num_rows($result);
	}

	return $rows;

	
}

?>