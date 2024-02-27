<?php
include_once('./_common.php');

if ($is_guest) {
    alert('로그인 되어 있지 않습니다.');
}

$g5['title'] = '설정';
include_once(G5_PATH.'/head.sub.php');
?>

<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div id="hd_wrapper">

        <div id="logo" style="text-align:left; padding-left:50px; font-size:20px; padding-top:16px; font-weight:700">찜한 상품</div>
        <button type="button" id="setting_go" class="gnb_back"><span class="sound_only"> 이전</span></button>

    </div>

</header>

<style type="text/css">
#hd {border-bottom:1px solid #ececce;}
#good_info {margin:20px}
#good_info ul {list-style-type:none; width:100%; border-bottom:1px solid #f3f3f3; padding-bottom:10px; margin-bottom:20px; overflow:hidden;}
#good_info li {float:left;}
#good_info li.thumb {width:26%;}
#good_info li.thumb img {width:100%}
#good_info li.desc {width:68%; margin-left:6%; font-size:15px;}
#good_info li.desc .price_line {margin-top:10px;overflow:hidden;}
#good_info li.desc .price_line span {float:left;}
#good_info li.desc .price_line span.symbol {
    float:right;
    width:16px;
    height:20px;
    background-image: url(../img/mobile/gnb_like_on.png);
    background-repeat: no-repeat;
    background-position:0 4px;
    background-size:100%;
}
#good_info li.desc .unit {font-size:14px; color:#999;}
#good_info li.desc .amount {font-size:16px; font-weight:700; color:#222; margin-left:10px;}
#good_info .empty {padding-top:100px; text-align:center;}
#good_info .empty img {width:100px;}
#good_info .empty .desc {margin-top:20px;color:#bfbfbf;font-size:16px;}
</style>

<div id="good_info">

<?php
$bo_table = 'Shop';

$sql = "select * from g5_scrap A left join g5_write_Shop B on A.wr_id=B.wr_id where A.mb_id = '{$member['mb_id']}' and bo_table = '{$bo_table}'";
$result = sql_query($sql);

if ($result->num_rows > 0) {
    $bo_gallery_width = 150;
    $bo_gallery_height = 150;

    while ($row = sql_fetch_array($result)) {

        $thumb = get_list_thumbnail($bo_table, $row['wr_id'], $bo_gallery_width, $bo_gallery_height, false, true);

        if($thumb['src']) {
            $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" class="base_img" />';
        } else {
            $img_content = '<!-- no image -->';
        }
?>
    <ul>
        <li class="thumb">
            <div><a href="/bbs/board.php?bo_table=<?=$bo_table?>&wr_id=<?=$row['wr_id']?>"><?=$img_content?></a></div>
        </li>
        <li class="desc">
            <a href="/bbs/board.php?bo_table=<?=$bo_table?>&wr_id=<?=$row['wr_id']?>">
                <?php echo get_text($row['wr_subject']) ?>
            </a>
            <div class="price_line">
                <span class="unit">판매금액</span><span class="amount"><?=number_format($row['wr_1']) ?> TP3</span>
                <span class="symbol" onclick="del_favorite('<?=$row['wr_id']?>')"></span>
            </div>
        </li>
    </ul>
<?php
    }
} else {
?>
    <div class="empty">
        <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/bbs_empty.png" />
        <div class="desc">
            앗! 찜한 상품이 아직 없어요!
        </div>
    </div>
<?php
}
?>
</div>

<script>
function del_favorite(wr_id) {
    if (confirm('해당 상품을 찜목록에서 제거할까요?')){
            $.ajax({
                url : '/bbs/favorite_add.php',
                type : 'POST',
                data : {'wr_id':wr_id, 'bo_table':'<?=$bo_table?>'},
                dataType : 'json',
                success : function(resp){
                    if (resp.err) {
                        alert(''+resp.err);
                    } else if (resp.success) {
                        if (resp.success=='add'){
                            document.location.reload();
                            //alert('찜목록에 추가되었습니다.');
                        } else if (resp.success=='remove'){
                            document.location.reload();
                            //alert('찜 목록에서 제거되었습니다.');
                        } else {
                            alert(''+resp.err);
                        }
                    } else {
                        alert('알수없는 오류');
                    }
                },
                error : function(resp){
                    alert('실패. 잠시후 다시 이용하시기 바랍니다.');
                }
            });
    }
}
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>
