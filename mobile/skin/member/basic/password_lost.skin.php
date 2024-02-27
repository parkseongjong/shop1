<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<style type="text/css">
.title {font-size:19px; padding:40px 0;text-align:center;}
.desc {padding:5px 23px; font-size:15px;text-align:center;}
.desc2 {padding:5px 30px; font-size:15px;text-align:center;margin-top:15px;}
.mbskin p {padding:30px 27px 45px; font-size:15px;}
</style>

<div class="title">
    아이디 / 비밀번호 찾기
</div>

<div class="desc">
    아이디 / 비밀번호 찾기는 CTC지갑에서 하실 수 있습니다. CTC지갑에서 아이디 / 비밀번호를 찾으시고 로그인 하신후 베리베리로 이동하세요.
</div>

<div class="desc2">
    * 아래 버튼을 클릭하시면 CTC지갑 페이지로 이동합니다.<br />
</div>

<div class="mbskin">

	<p>
        <button type="submit" class="btn_submit" id="btn_go_wallet" >CTC지갑 바로가기</button>
    </p>

</div>

<script>
jQuery(function($){
    $("#btn_go_wallet").on("click", function() {
        document.location.href = 'https://cybertronchain.com/wallet2';
    });
});
</script>
