<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

include_once(G5_PATH.'/API/controllers/common/Util.php');
use barry\common\Util as barryUtil;
// 선택옵션으로 인해 셀합치기가 가변적으로 변함
$colspan = 2;

if ($is_checkbox) $colspan++;

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="spt" value="<?php echo $spt ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="sw" value="">

<?php if ($rss_href || $write_href) { ?>
<ul class="<?php echo isset($list[$i]) ? 'view_is_list btn_top' : 'btn_top top btn_bo_user';?>">
	<?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">관리자</span></a></li><?php } ?>
    <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b03 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
    <?php if ($is_admin == 'super' || $is_auth) {  ?>
	<li>
		<button type="button" class="btn_more_opt btn_b03 btn is_list_btn" title="게시판 리스트 옵션"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">게시판 리스트 옵션</span></button>
		<?php if ($is_checkbox) { ?>	
        <ul class="more_opt is_list_btn">
            <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value"><i class="fa fa-trash-o" aria-hidden="true"></i> 선택삭제</button></li>
            <li><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value"><i class="fa fa-files-o" aria-hidden="true"></i> 선택복사</button></li>
            <li><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value"><i class="fa fa-arrows" aria-hidden="true"></i> 선택이동</button></li>
        </ul>
        <?php } ?>
	</li>
    <?php } ?>
	<?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="fix_btn btn btn-outline-dark" title="글쓰기"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sound_only">글쓰기</span></a></li><?php } ?>
</ul>
<?php } ?>
<!-- 게시판 목록 시작 -->
<div id="bo_list">

    <?php if ($is_category) { ?>
    <nav id="bo_cate">
        <h2><?php echo ($board['bo_mobile_subject'] ? $board['bo_mobile_subject'] : $board['bo_subject']) ?> 카테고리</h2>
        <ul id="bo_cate_ul">
            <?php echo $category_option ?>
        </ul>
    </nav>
    <?php } ?>

    <div class="mediaList">
        <?php if ($is_checkbox) { ?>
        <div class="all_chk chk_box">
            <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);" class="selec_chk">
            <label for="chkall">
            	<span></span>
            	<b class="sound_only">현재 페이지 게시물 </b> 전체선택
            </label>
        </div>
        <?php } ?>
        <ul>
            <?php
                $barryUtil = barryUtil::singletonMethod();
                for ($i=0; $i<count($list); $i++):
            ?>
            <li class="<?php if ($list[$i]['is_notice']) echo "bo_notice"; ?> card">
                <?php if ($is_checkbox) { ?>
                <div class="bo_chk chk_box">
                    <input type="checkbox" name="chk_wr_id[]" value="<?php echo $list[$i]['wr_id'] ?>" id="chk_wr_id_<?php echo $i ?>" class="selec_chk">
                    <label for="chk_wr_id_<?php echo $i ?>">
                    	<span></span>
                    	<b class="sound_only"><?php echo $list[$i]['subject'] ?></b>
                    </label>   	
                </div>
                <?php } ?>

                <div id="bo_v_con">
                    <section class="shopStyleWrap">
                        <?php if(isset($list[$i]['link']) && array_filter($list[$i]['link'])): ?>
                            <div id="viewDetailSlide-<?php echo $list[$i]['wr_id'] ?>" class="swiper-container">
                                <div class="swiper-wrapper">
                                    <?php
                                    // 링크
                                    $cnt = 0;
                                    for ($j=1; $j<=count($list[$i]['link']); $j++):
                                        if ($list[$i]['link'][$j]):
                                            $cnt++;
                                            $link = cut_str($list[$i]['link'][$j], 70);

                                            //link 이미지가 이미 있으면 내부 DB에 저장된 URL 주소를 가져온다.
                                            //$write_table
                                            $return = sql_fetch('SELECT wr_1 FROM '.$write_table.' WHERE wr_id ='.$list[$i]['wr_id']);
                                            if($return['wr_1']){
                                                $curlReturn = $return['wr_1'];
                                            }
                                            else{
                                                $curlReturn = $barryUtil -> getCurlGnuLinkImage($link);
                                                sql_query('UPDATE '.$write_table.' SET wr_1 = "'.$curlReturn.'" WHERE wr_id='.$list[$i]['wr_id']);
                                            }
                                    ?>
                                            <div class="swiper-slide">
                                                <img src="<?php echo $curlReturn; ?>">
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="swiper-pagination"></div>

                                <div class="swiper-button-prev"></div>
                                <div class="swiper-button-next"></div>
                            </div>
                        <?php endif; ?>
                        <div class="shopStyleInfo">
                            <h2 id="bo_v_title">
                                <span class="bo_v_tit"><?php echo cut_str(get_text($list[$i]['wr_subject']), 70);?></span>
                            </h2>
                            <?php if(isset($list[$i]['link']) && array_filter($list[$i]['link'])): ?>
                                <ul class="command">
                                    <?php
                                    // 링크
                                    $cnt = 0;
                                    for ($j=1; $j<=count($list[$i]['link']); $j++):
                                        if ($list[$i]['link'][$j]):
                                            $cnt++;
                                            $link = cut_str($list[$i]['link'][$j], 70);
                                            ?>
                                            <li>
                                                <a href="<?php echo $list[$i]['link_href'][$j] ?>" class="btn btn-info"><?php echo $j ?>번 링크 보기</a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <?php if($admin_href): ?>
                                        <li>
                                            <a href="<?php echo $list[$i]['href'] ?>" class="btn btn-primary">[관리자]게시글 보기</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="shopStyleContents">
                            <?php echo get_view_thumbnail($list[$i]['wr_content']); ?>
                        </div>
                        <div class="shopStyleWriterInfo">
                            <p><img src="<?php echo G5_URL; ?>/img/no_profile.gif" alt="profile_image" class="profile_image"></p>
                            <p><span class="seller_name"><?php echo $list[$i]['wr_name'] ?></span>/<span class="sellet_phone"><?php echo $list[$i]['mb_id'] ?></span></p>
                        </div>
                    </section>
                </div>
                <script>
                    $(function($) {
                        var viewDetailSlider = new Swiper('#viewDetailSlide-<?php echo $list[$i]["wr_id"] ?>', {
                            navigation: {
                                nextEl: '#viewDetailSlide-<?php echo $list[$i]["wr_id"] ?> .swiper-button-next',
                                prevEl: '#viewDetailSlide-<?php echo $list[$i]["wr_id"] ?> .swiper-button-prev'
                            },
                            pagination: {
                                el: '#viewDetailSlide-<?php echo $list[$i]["wr_id"] ?> .swiper-pagination',
                                type: 'bullets',
                                clickable: true
                            },
                            autoplay: false
                        });
                    });
                </script>
            </li>
            <?php endfor; ?>
            <?php if (count($list) == 0) { echo '<li class="empty_table">게시물이 없습니다.</li>'; } ?>
        </ul>
    </div>
</div>

</form>

<?php if($is_checkbox) { ?>
<noscript>
<p>자바스크립트를 사용하지 않는 경우<br>별도의 확인 절차 없이 바로 선택삭제 처리하므로 주의하시기 바랍니다.</p>
</noscript>
<?php } ?>

<!-- 페이지 -->
<?php echo $write_pages; ?>

<div id="bo_list_total">
    <span>전체 <?php echo number_format($total_count) ?>건</span>
    <?php echo $page ?> 페이지
</div>

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
    var f = document.fboardlist;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]")
            f.elements[i].checked = sw;
    }
}

function fboardlist_submit(f) {
    var chk_count = 0;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
            chk_count++;
    }

    if (!chk_count) {
        alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택복사") {
        select_copy("copy");
        return;
    }

    if(document.pressed == "선택이동") {
        select_copy("move");
        return;
    }

    if(document.pressed == "선택삭제") {
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
            return false;

        f.removeAttribute("target");
        f.action = g5_bbs_url+"/board_list_update.php";
    }

    return true;
}

// 선택한 게시물 복사 및 이동
function select_copy(sw) {
    var f = document.fboardlist;

    if (sw == 'copy')
        str = "복사";
    else
        str = "이동";

    var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");

    f.sw.value = sw;
    f.target = "move";
    f.action = g5_bbs_url+"/move.php";
    f.submit();
}

// 게시판 리스트 관리자 옵션
jQuery(function($){
    $(".btn_more_opt.is_list_btn").on("click", function(e) {
        e.stopPropagation();
        $(".more_opt.is_list_btn").toggle();
    });
    $(document).on("click", function (e) {
        if(!$(e.target).closest('.is_list_btn').length) {
            $(".more_opt.is_list_btn").hide();
        }
    });
});
</script>
<?php } ?>
<!-- 게시판 목록 끝 -->
