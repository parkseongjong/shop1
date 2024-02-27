<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ($is_guest) {
    alert('로그인된 회원만 이용하실 수 있습니다.');
}
?>

<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div id="hd_wrapper">

        <div id="logo" style="text-align:left; padding-left:50px; font-size:20px; padding-top:16px; font-weight:700">설정</div>
        <button type="button" id="gnb_back"><span class="sound_only"> 이전</span></button>

    </div>

    <script>
    $("#gnb_back").on("click", function() {
        document.location.href = '/';
        //history.back();
    });
    </script>
</header>

<style type="text/css">
#hd {border-bottom:1px solid #ececce;}
#profile_info {}
#profile_info .img_outer {width:118px; height:118px; margin:20px auto 0; border:4px solid #62c234; border-radius:120px; background:white;}
#profile_info .img_inner {width:100px; height:100px; margin:5px; border-radius:120px; background:#dadada; text-align:center}
#profile_info .img_inner img {width:40%; margin-top:30px;}
#profile_info .name {padding-top:4px; font-size:21px; font-weight:700; text-align:center}

#quick_link {padding:10px 10px 25px 10px; margin-top:30px; border-bottom:4px solid #f7f7f7; overflow:hidden;}
#quick_link ul {list-style-type:none;margin-left:1%;overflow:hidden}
#quick_link li {float:left; margin:0 9%; width:15%; text-align:center; font-size:12px}
#quick_link li img {width:50%;}

#mymenu_link {}
#mymenu_link ul {list-style-type:none;width:100%;height:65px;border-bottom:2px solid #e1e1e1;overflow:hidden}
#mymenu_link li {float:left; height:65px;line-height:65px;}
#mymenu_link li.sub_title {width:80%;}
#mymenu_link li.sub_title span {font-size:16px;font-weight:400;margin-left:24px;}
#mymenu_link li.right_arrow {float:right;width:20%;text-align:right;padding-right:22px;}
#mymenu_link li.right_arrow img {width:8px;}
</style>

<div id="profile_info">

    <div class="img_outer">
        <div class="img_inner">
            <img src="/img/mobile/no_profile.png" alt='profile_image' />
        </div>
    </div>
    <div class="name"><?=$member['mb_nick']?></div>

</div>

<div id="quick_link">
    <ul>
        <li>
            <a href="/bbs/member_setting_chat.php"><img src="/img/mobile/myinfo_chat.png" /><br />채팅톡</a>
        </li>
        <li>
            <a href="/bbs/member_setting_scrap.php"><img src="/img/mobile/myinfo_favorite.png" /><br />찜한 상품</a>
        </li>
        <li>
            <img src="/img/mobile/myinfo_review.png" /><br />리뷰
        </li>
    </ul>
</div>

<div id="mymenu_link">
    <ul onclick="goOrderList()">
        <li class="sub_title">
            <span>주문내역</span>
        </li>
        <li class="right_arrow">
            <img src="/img/mobile/right_arrow.png" />
        </li>
    </ul>
    <ul onclick="goMyGoodsList()">
        <li class="sub_title">
            <span>내 상품관리</span>
        </li>
        <li class="right_arrow">
            <img src="/img/mobile/right_arrow.png" />
        </li>
    </ul>
    <ul onclick="goMyVirtualWalletInOut()">
        <li class="sub_title">
            <span>가상지갑 입/출금내역</span>
        </li>
        <li class="right_arrow">
            <img src="/img/mobile/right_arrow.png" />
        </li>
    </ul>
    <ul onclick="goMyInfo()">
        <li class="sub_title">
            <span>내 정보관리</span>
        </li>
        <li class="right_arrow">
            <img src="/img/mobile/right_arrow.png" />
        </li>
    </ul>
    <!--ul>
        <li class="sub_title">
            <span>주소록관리</span>
        </li>
        <li class="right_arrow">
            <img src="/img/mobile/right_arrow.png" />
        </li>
    </ul-->
    <ul onclick="goCTCWallet()">
        <li class="sub_title">
            <span>CTC지갑 바로가기</span>
        </li>
        <li class="right_arrow">
            <img src="/img/mobile/right_arrow.png" />
        </li>
    </ul>
</div>

<script>
function goMyInfo() {
    document.location.href = '/bbs/member_confirm.php?url=register_form.php';
}
function goMyGoodsList() {
    document.location.href = '/bbs/member_goodslist.php';
}
function goOrderList() {
    document.location.href = '/bbs/member_orderlist.php';
}
function goMyVirtualWalletInOut() {
    document.location.href = '/bbs/member_virtinout.php';
}
function goCTCWallet() {
    document.location.href = 'https://cybertronchain.com/wallet2';
}
</script>