<?php
include_once('./_common.php');
if ($is_guest) {
    alert('로그인 되어 있지 않습니다.');
}
if (!isset($_GET['bo_table'])) {
    alert('필수 값이 없습니다.');
}
if (!isset($_GET['wr_id'])) {
    alert('필수 값이 없습니다.');
}
add_javascript('<script src="'.G5_BBS_URL.'/goodsDetail.js"></script>', 1);
$bo_table = $_GET['bo_table'];
$wr_id = $_GET['wr_id'];

$g5['title'] = '설정';
include_once(G5_PATH.'/head.sub.php');
?>
<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div id="hd_wrapper">

        <div id="logo" style="text-align:left; padding-left:50px; font-size:20px; padding-top:16px; font-weight:700">상품수정</div>
        <button type="button" id="gnb_back" class="gnb_back"><span class="sound_only"> 이전</span></button>

    </div>

</header>

<style type="text/css">
#hd {border-bottom:1px solid #ececce;}
#good_info {margin:20px}
#good_info ul {list-style-type:none; width:100%; border-bottom:1px dashed #bbb; padding-bottom:15px; margin-bottom:15px; overflow:hidden;}
#good_info li {float:left;}
#good_info li.thumb {width:15%;}
#good_info li.thumb .img_inner {width:50px; height:50px; margin:5px; border-radius:120px; background:#dadada; text-align:center}
#good_info li.thumb .img_inner img {width:50%; margin-top:12px;}
#good_info li.thumb .img_goods {width:50px; height:50px; border-radius:12px}
#good_info li.thumb .img_goods img {width:100%; margin-top:2px;}
#good_info li.desc {
    width:80%;
    margin-left:5%;
}
#good_info li.desc .count {font-size:12px; color:white;padding:0 6px; background:#f7115d; border-radius:20px; margin-right:8px}
#good_info li.desc .content {margin-top:12px;font-size:13px;line-height:19px;color:#888;}
#good_info li.desc .del_y {font-size:12px; color:white;padding:2px 7px; background:#f7115d; border-radius:20px; margin-right:8px}
#good_info li.desc .del_n {font-size:12px; color:white;padding:2px 7px; background:#11c711; border-radius:20px; margin-right:8px}

#good_info li.desc .dt {margin-top:5px;font-size:13px; color:#aaa}
#good_info .empty {padding-top:100px; text-align:center;}
#good_info .empty img {width:100px;}
#good_info .empty .desc {margin-top:20px;color:#bfbfbf;font-size:16px;}

ul.goodsDetailOption > li{
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    margin-bottom: 10px;
}

h3.stockOnlineAlertLink{
    font-size: 18px;
}
h3.stockOnlineAlertLink > a{
    color: red;
}

.sub_title {color:#7f7f7f; font-size:13px; background:#f1f1f1; padding:3px 9px;}
input.button {padding:3px 8px; background:#ddd; border:1px solid #bbb; border-radius:5px; resize:none; outline:none;}
</style>



<div id="good_info">

<?php
$sql = "select * from g5_write_{$bo_table} where wr_id='{$wr_id}'";
$row = sql_fetch($sql);

if (!$row) {
    alert('데이터가 존재하지 않습니다.');
}


$bo_gallery_width = 150;
$bo_gallery_height = 150;

$thumb = get_list_thumbnail($bo_table, $row['wr_id'], $bo_gallery_width, $bo_gallery_height, false, true);

if($thumb['src']) {
    $img_content = '<div class="img_goods"><img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" class="goods_img" /></div>';
} else {
    $img_content = '<div class="img_inner"><img src="'.BARRY_THEME_CUSTOM_MOBILE_IMG_URL.'/no_profile.png" alt="goods_image" /></div>';
}

//코인 타입 빌드
if($row['wr_price_type'] == 'TP3'){
    $row['wr_price_type'] = 'e-TP3';
    $bulid = number_format($row['wr_1']).' e-TP3';
}
else if($row['wr_price_type'] == 'MC') {
    $bulid = number_format($row['wr_2']).' e-MC';
}
else if($row['wr_price_type'] == 'TP3MC') {
    $bulid = number_format($row['wr_1']).' e-TP3'.'/'.number_format($row['wr_2']).' e-MC';
}
else if($row['wr_price_type'] == 'EKRW') {
    $bulid = number_format($row['wr_3']).' e-KRW';
}
else if($row['wr_price_type'] == 'ECTC') {
    $bulid = number_format($row['wr_4']).' e-CTC';
}
else if($row['wr_price_type'] == "KRW" ) {
	$bulid = "<p>".number_format($row['wr_10'])."<span class='unit'>원</span></p>";
}
else if ($row['wr_price_type'] == "CREDITCARD"){
	$bulid = "<p>".number_format($row['wr_10'])."<span class='unit'>원</span></p>";
}
?>
    <ul>
        <li class="thumb">
            <?=$img_content?>
        </li>
        <li class="desc">
            <div>
                <?=$row['ca_name']?> <span>(<?=$row['mb_id']?>)</span>
            </div>
            <div class="dt">
                <?=$row['wr_datetime']?>
            </div>
            <div class="content">
                <?php echo get_text($row['wr_subject'])?>
                <span class="bold"><?php echo $bulid ?></span>
            </div>
            <div class="status">
                <?php if ($row['it_publish']==1): ?>
                    <span class="badge badge-success">승인</span>
                <?php elseif($row['it_publish']==90): ?>
                    <span class="badge badge-warning">반려</span>
                <?php else: ?>
                    <span class="badge badge-danger">미승인</span>
                <?php endif; ?>
            </div>
            <div class="status">
                <?php if ($row['del_yn']=='Y'): ?>
                    <span class="badge badge-danger">판매중지(삭제됨)</span>
                <?php else: ?>
                    <span class="badge badge-success">판매중(정상)</span>
                <?php endif; ?>
            </div>
            <div class="status">
                <?php if ($row['it_soldout']==0): ?>
                    <span class="badge badge-success">정상</span>
                <?php else: ?>
                    <span class="badge badge-danger">품절</span>
                <?php endif; ?>
            </div>
        </li>

    </ul>

    <ul class="goodsDetailOption">
        <?php if ($row['it_publish'] == 0): ?>
            <li>
                <div class="alert alert-danger">
                    미승인 된 상품은 베리몰에서 판매 할 수 없습니다.
                    심사 관련 사항은 베리몰에 문의 해주세요.
                </div>
            </li>
        <?php endif; ?>
        <li>
    <?php if ($row['del_yn']=='Y') { ?>
            <p>이 상품은 삭제된 상품입니다. 아래의 버튼을 클릭하시면 상품이 정상 복구됩니다.</p>
            <div style="margin-top:16px;">
                <p class="btn btn-primary" onclick="recover('<?=$row['wr_id'];?>','<?=$row['it_me_table'];?>');" >복구하기</p>
            </div>
    <?php } else { ?>

            <p>상품을 삭제하시려면 '삭제하기' 버튼을 클릭하세요.</p>
            <div style="margin-top:16px;">
                <p class="btn btn-danger" onclick="remove('<?=$row['wr_id'];?>','<?=$row['it_me_table'];?>');" >삭제하기</p>
            </div>
    <?php } ?>
        </li>
        <li>
            <div>
                <a href="<?php echo G5_BBS_URL.'/write.php?w=u&bo_table='.$row['it_me_table'].'&wr_id='.$row['wr_id']; ?>" class="btn btn-warning btn-block">상품 수정</a>
            </div>
        </li>
        <li>
            <p class="btn btn-info" onclick="stockOnline('<?=$row['wr_id'];?>','<?=$row['it_me_table'];?>');">재고 있음으로 상태 변경(정상)</p>
            
            
        </li>
        <li>
            <p class="btn btn-danger" onclick="stockOffline('<?=$row['wr_id'];?>','<?=$row['it_me_table'];?>');">재고 없음으로 상태 변경(품절)</p>
        </li>

    </ul>
</div>
<div>

<input type="hidden" id="page" value="<?php echo $page ?>">
</div>


<?php
include_once(G5_PATH.'/tail.sub.php');
?>
