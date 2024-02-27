<?php
if (!defined('_GNUBOARD_')) exit; // ���� ������ ���� �Ұ�
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css ����', ��¼���); ���ڰ� ���� ���� ���� ��µ�
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
$thumb_width = 500;
$thumb_height = 500;
?>

<?php
$count = count($list);
for ($i=0; $i<$count; $i++) {
$bo_subject = mb_substr($list[$i]['bo_subject'],0,8,"utf-8"); // �Խ��Ǹ� ���ڼ�
$thumb = get_list_thumbnail($list[$i]['bo_table'], $list[$i]['wr_id'], $thumb_width, $thumb_height);
if($thumb['src']) {
    $img = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" width="'.$thumb_width.'">';
}
?>
<div class="box01 lt01">
    <?php if ($thumb['src']) { ?> <a href="<?php echo $list[$i]['href'] ?>" class="lt_img"><?php echo $img; ?></a>  <?php } ?>
    
    <div class="lt_info">
        <span class="lt_name"><?php echo $list[$i]['ca_name'] ?></span>
        <a href="<?php echo $list[$i]['href']; ?>" class="lt_tit"><?php echo $list[$i]['subject']; ?></a>
        <div class="lt_detail"> <?php echo get_text(cut_str(strip_tags($list[$i]['wr_content']),  26), 1); ?></div>
        <a href="<?php echo $list[$i]['href']; ?>" class="lt_more">�ڼ�������</a>
    </div>

    <div class="lt_cate">
        <a href="<?php echo get_pretty_url($list[$i]['bo_table']); ?>" class="lt_cate_link <?php echo $list[$i]['bo_table'] ?>"><?php echo $bo_subject; ?></a>
        <span class="lt_date"><?php echo $list[$i]['wr_name'] ?> | <?php echo $list[$i]['datetime'] ?></span>
    </div>
</div>
<?php } ?>
<?php if ($i == 0) echo '<div class="empty_lt">�Խù��� �����ϴ�.</div>'; ?>



<style>
@media (min-width: 901px) {
.lt_detail.mo_none{display:none}
}
@media (max-width: 900px){

 .lt_detail.pc_none{display:none}
 .lt .lt_cate {padding:20px 20px 50px 0px;line-height:24px;border-top:1px solid #f2f2f2}
}

.latest_wr .box01 {width:23%; float:left; margin:0px 0.5% 15px ; height:630px}
.latest_wr .box01 img {width:100%; height:auto}
@media (max-width: 900px){
.latest_wr .box01 {width:48%; float:left;  margin:0 1% 10px ; height:auto; min-height:500px}
.latest_wr .box01 img {width:100%; height:auto;}
}
</style>