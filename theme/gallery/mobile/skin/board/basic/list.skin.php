<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 1);
add_javascript('<script src="'.G5_THEME_JS_URL.'/waterfall-light.js"></script>', 0);
?>

<script src="<?php echo G5_JS_URL; ?>/jquery.fancylist.js"></script>


<?php if ($is_category) { ?>
<nav id="bo_cate">
    <h2><?php echo ($board['bo_mobile_subject'] ? $board['bo_mobile_subject'] : $board['bo_subject']) ?> 카테고리</h2>
    <ul id="bo_cate_ul">
        <?php echo $category_option ?>
    </ul>
</nav>
<?php } ?>
<fieldset id="bo_sch">
    <legend>게시물 검색</legend>

    <form name="fsearch" method="get">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sop" value="and">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="wr_subject"<?php echo get_selected($sfl, 'wr_subject', true); ?>>제목</option>
        <option value="wr_content"<?php echo get_selected($sfl, 'wr_content'); ?>>내용</option>
        <option value="wr_subject||wr_content"<?php echo get_selected($sfl, 'wr_subject||wr_content'); ?>>제목+내용</option>
        <option value="mb_id,1"<?php echo get_selected($sfl, 'mb_id,1'); ?>>회원아이디</option>
        <option value="mb_id,0"<?php echo get_selected($sfl, 'mb_id,0'); ?>>회원아이디(코)</option>
        <option value="wr_name,1"<?php echo get_selected($sfl, 'wr_name,1'); ?>>글쓴이</option>
        <option value="wr_name,0"<?php echo get_selected($sfl, 'wr_name,0'); ?>>글쓴이(코)</option>
    </select>
    <input name="stx" value="<?php echo stripslashes($stx) ?>" placeholder="검색어(필수)" required id="stx" class="sch_input" size="15" maxlength="20">
    <button type="submit" value="검색" class="sch_btn"><i class="fa fa-search" aria-hidden="true"></i> <span class="sound_only">검색</span></button>
    </form>
</fieldset>

<!-- 게시판 목록 시작 -->
<div id="bo_gall">

    <div class="sound_only">
        <span>전체 <?php echo number_format($total_count) ?>건</span>
        <?php echo $page ?> 페이지
    </div>

    <form name="fboardlist"  id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">

    <?php if ($rss_href || $write_href) { ?>
    <ul class="btn_bo_user">
        <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn"><i class="fa fa-rss"></i><span class="sound_only">RSS</span></a></li><?php } ?>
        <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn"><i class="fa fa-user-circle"></i><span class="sound_only">관리자</span></a></li><?php } ?>
        <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b02 btn">글쓰기</a></li><?php } ?>

        <?php if ($is_checkbox) { ?>
        <li><button type="button" class="edit_op_btn view_op_btn_list"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">게시판 리스트수정</span></button>
            <ul class="btn_edit btn_list_op">
                <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value"><i class="fa fa-trash-o"></i>선택삭제</button></li>
                <li><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value"><i class="fa fa-files-o"></i>선택복사</button></li>
                <li><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value"><i class="fa fa-arrows"></i>선택이동</button></li>
            </ul>
        </li>
        <?php } ?>
    </ul>

    <?php } ?>
    <?php if ($is_checkbox) { ?>
    <script>
    $(function(){
        $(".view_op_btn_list").click(function(){
            $(this).next(".btn_list_op").toggle();
        });
        $(document).mouseup(function (e) {
            var container = $(".btn_list_op");
            if (!container.is(e.target) && container.has(e.target).length === 0){
            container.css("display","none");
            }	
        });
    });
    </script>
    <?php } ?>

    <?php if ($is_checkbox) { ?>
    <div id="gall_allchk">
        <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);">
        <label for="chkall">게시물 전체</label>
    </div>
    <?php } ?>

    <ul id="gall_ul">
        <?php for ($i=0; $i<count($list); $i++) {
        ?>
        <li class="gall_li box <?php if ($wr_id == $list[$i]['wr_id']) { ?>gall_now<?php } ?>">
           

            <a href="<?php echo $list[$i]['href'] ?>" class="gall_img">

            <?php {
                $thumb = get_list_thumbnail($board['bo_table'], $list[$i]['wr_id'], $board['bo_mobile_gallery_width'], $board['bo_mobile_gallery_height']);
                if($thumb['src']) {
                    $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" width="'.$board['bo_mobile_gallery_width'].'">';
                } else {
                    $img_content = '';
                }

                echo $img_content;
            }
            ?>
            </a>
            <div class="gall_text_href">
                <div class="gal_name"><?php echo $list[$i]['name'] ?></div>
                  
                <?php if ($is_checkbox) { ?>
                <span class="gall_li_chk">
                    <label for="chk_wr_id_<?php echo $i ?>" class="sound_only"><?php echo $list[$i]['subject'] ?></label>
                    <input type="checkbox" name="chk_wr_id[]" value="<?php echo $list[$i]['wr_id'] ?>" id="chk_wr_id_<?php echo $i ?>">
                </span>
                <?php } ?>
                
                <div class="bo_cate">
                    <?php if ($list[$i]['is_notice']) { ?><span class="bo_notice">공지</span><?php } ?> 

                    <?php
                    // echo $list[$i]['icon_reply']; 갤러리는 reply 를 사용 안 할 것 같습니다. - 지운아빠 2013-03-04
                    if ($is_category && $list[$i]['ca_name']) {
                    ?>
                    <a href="<?php echo $list[$i]['ca_name_href'] ?>" class="bo_cate_link"><?php echo $list[$i]['ca_name'] ?></a>
                    <?php } ?>
                </div>

                <a href="<?php echo $list[$i]['href'] ?>" class="gal_li_tit">
                    <?php echo $list[$i]['subject'] ?>

                    <?php
                    if (isset($list[$i]['icon_new'])) echo $list[$i]['icon_new'];
                    if (isset($list[$i]['icon_hot'])) echo $list[$i]['icon_hot'];
                    if (isset($list[$i]['icon_file'])) echo $list[$i]['icon_file'];
                    if (isset($list[$i]['icon_link'])) echo $list[$i]['icon_link'];
                    if (isset($list[$i]['icon_secret'])) echo $list[$i]['icon_secret'];
                    ?>
                </a>
                <div class="gal_detail"> <?php echo get_text(cut_str(strip_tags($list[$i]['wr_content']), 50), 1); ?></div>


            </div>
            <div class="gal_info">
                <span class="gal_if_sp"><span class="sound_only">작성일 </span><?php echo $list[$i]['datetime'] ?></span>
                <span class="gal_if_sp">조회<strong> <?php echo $list[$i]['wr_hit'] ?></strong> </span>
                <?php if ($list[$i]['comment_cnt']) { ?><span class="gal_if_sp">댓글 <?php echo $list[$i]['comment_cnt']; ?></span><?php } ?>

                <?php if ($is_good) { ?><span class="gal_if_sp">추천<strong> <?php echo $list[$i]['wr_good'] ?></strong></span><?php } ?>
            </div>
        </li>
        <?php } ?>
        <?php if (count($list) == 0) { echo "<li class=\"empty_list\">게시물이 없습니다.</li>"; } ?>
    </ul>



    </form>
</div>


<?php if($is_checkbox) { ?>
<noscript>
<p>자바스크립트를 사용하지 않는 경우<br>별도의 확인 절차 없이 바로 선택삭제 처리하므로 주의하시기 바랍니다.</p>
</noscript>
<?php } ?>

<!-- 페이지 -->
<?php echo $write_pages; ?>


<script>
 $('#gall_ul').show().waterfall({
     // top offset
    top : false, 

    // the container witdh
    w : false, 

    // the amount of columns
    col : false, 

    // the space bewteen boxes
    gap : 15,

    // breakpoints in px
    // 0-400: 1 column
    // 400-600: 2 columns
    // 600-800: 3 columns
    // 800-1000: 4 columns
    gridWidth : [0,600,790,970,],

    // the interval to check the screen
    refresh: 0,
    timer : false,

    // execute a function as the page is scrolled to the bottom
    scrollbottom : false
 });
</script>

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


</script>
<?php } ?>
<!-- 게시판 목록 끝 -->
