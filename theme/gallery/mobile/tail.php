<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
    </div>
</div>



<div id="ft">

    <div id="ft_copy">
        <div id="ft_company">
            <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=company">회사소개</a>
            <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=privacy">개인정보처리방침</a>
            <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=provision">서비스이용약관</a>

        </div>
        <div style="clear:both"></div>
        <div style="color:#c0c0c0; padding: 10px 40px 0 2px;">
            (주)한가족몰 사업자등록번호 849-88-01299 <br />통신판매업신고번호 제 2019-서울금천-1290호 <br />대표이사 김명희 대표전화 1566-1783
        </div>
        <div class="copy"> Copyright &copy; <b>Barrybarries.kr.</b> All rights reserved.</div>

    </div>
    <button type="button" id="top_btn"><i class="fa fa-arrow-up" aria-hidden="true"></i><span class="sound_only">상단으로</span></button>
    <?php
    if(G5_DEVICE_BUTTON_DISPLAY && G5_IS_MOBILE) { ?>
    <a href="<?php echo get_device_change_url(); ?>" id="device_change">PC 버전으로 보기</a>
    <?php
    }

    if ($config['cf_analytics']) {
        echo $config['cf_analytics'];
    }
    ?>
</div>



<!-- 하단 픽스 메뉴 
<div class="footer_menu">
    <ul>
        <li class="on f_menu01"><a href="<?php echo G5_BBS_URL ?>/board.php?bo_table=Shop"><span></span>홈</a></li>
        <li class="f_menu02"><a href="<?php echo G5_BBS_URL ?>/content.php?co_id=cate"><span></span>카테고리</a></li>
        <li class="f_menu03"><a href="<?php echo G5_BBS_URL; ?>/write.php?bo_table=Shop"><span></span>글쓰기</a></li>
        <li class="f_menu04"><a href="<?php echo G5_BBS_URL ?>/search.php"><span></span>검색</a></li>
        <li class="f_menu05"><a href="<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php"><span></span>설정</a></li>
    </ul>
</div>
-->
<div style="background:#262b37">
<br /><br /><br />
</div>


<script>
jQuery(function($) {

    $( document ).ready( function() {

        // 폰트 리사이즈 쿠키있으면 실행
        font_resize("container", get_cookie("ck_font_resize_rmv_class"), get_cookie("ck_font_resize_add_class"));
        
        //상단고정
        if( $(".top").length ){
            var jbOffset = $(".top").offset();
            $( window ).scroll( function() {
                if ( $( document ).scrollTop() > jbOffset.top ) {
                    $( '.top' ).addClass( 'fixed' );
                }
                else {
                    $( '.top' ).removeClass( 'fixed' );
                }
            });
        }

        //상단으로
        $("#top_btn").on("click", function() {
            $("html, body").animate({scrollTop:0}, '500');
            return false;
        });

    });
});
</script>

<?php
include_once(G5_THEME_PATH."/tail.sub.php");
?>