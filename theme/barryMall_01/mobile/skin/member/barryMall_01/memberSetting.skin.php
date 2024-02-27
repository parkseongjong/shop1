<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
add_javascript('<script src="'.$member_skin_url.'/memberSetting.js"></script>', 1);

?>

<div id="profile_info">

    <div class="img_outer">
        <div class="img_inner">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/no_profile.png" alt='profile_image' />
        </div>
    </div>
    <div class="name"><?=$member['mb_nick']?></div>

</div>

<div id="quick_link">
    <ul>
        <li>
            <a href="/bbs/memberMemoList.php"><img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/myinfo_chat.png" /><br />채팅톡</a>
        </li>
        <li>
            <a href="/bbs/member_setting_scrap.php"><img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/myinfo_favorite.png" /><br />찜한 상품</a>
        </li>
        <li>
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/myinfo_review.png" /><br />리뷰
        </li>
    </ul>
</div>

<div id="mymenu_link">
    <ul onclick="goOrderList()">
        <li class="sub_title">
            <span>주문내역</span>
        </li>
        <li class="right_arrow">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
        </li>
    </ul>
    <ul onclick="goMyGoodsList()">
        <li class="sub_title">
            <span>내 상품관리</span>
        </li>
        <li class="right_arrow">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
        </li>
    </ul>
    <ul onclick="goMyVirtualWalletInOut()">
        <li class="sub_title">
            <span>가상지갑 입/출금내역</span>
        </li>
        <li class="right_arrow">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
        </li>
    </ul>
<!--
    <ul onclick="goMyInfo()">
        <li class="sub_title">
            <span>내 정보관리</span>
        </li>
        <li class="right_arrow">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
        </li>
    </ul>
-->
    <!--ul>
        <li class="sub_title">
            <span>주소록관리</span>
        </li>
        <li class="right_arrow">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
        </li>
    </ul-->
    <a href="<?php echo G5_PLUGIN_URL ?>/barryCoupon/#/CouponSelect">
        <ul>
            <li class="sub_title">
                <span>쿠폰 구매</span>
            </li>
            <li class="right_arrow">
                <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
            </li>
        </ul>
    </a>
    <a href="<?php echo G5_PLUGIN_URL ?>/barryCoupon/#/CouponList">
        <ul>
            <li class="sub_title">
                <span>구매 쿠폰 목록</span>
            </li>
            <li class="right_arrow">
                <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
            </li>
        </ul>
    </a>
    <ul onclick="goCTCWallet()">
        <li class="sub_title">
            <span>CTC지갑 바로가기</span>
        </li>
        <li class="right_arrow">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ?>/right_arrow.png" />
        </li>
    </ul>
    <?php if($member['mb_id'] == '01050958112' || $member['mb_id'] == '01096415095'): ?>
        <?php var_dump($_SERVER); ?>
        <a href="https://barrybarries.kr/testtestbarry">테스트 path</a>
        <?php var_dump(file_get_contents('/onefamily11/test.txt')) ?>
    <?php endif; ?>
</div>