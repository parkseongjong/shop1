<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
    </div><!-- id=container END -->
</div><!-- id=wrapper END-->


<div id="ft">
    <div id="ft_copy">
        <div id="ft_company">
            <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=company">회사소개</a>
            <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=privacy">개인정보처리방침</a>
            <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=provision">서비스이용약관</a>
            <a href="<?php echo G5_URL; ?>/content/goodsInfo01">상품판매 등록 안내사항</a>

        </div>
        <div style="clear:both"></div>
        <div class="info" style="color:#c0c0c0; padding: 10px 40px 0 2px;">
            주식회사 한마음스마트 사업자등록번호 797-81-01586 <br />통신판매업신고번호 제 2020-서울금천-1528호 <br />대표이사 김명희 대표전화 1566-1783
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
<?php
include_once(G5_THEME_PATH."/tail.sub.php");
?>