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
    BARRY BARRIES 에 처음 오셨나요?
</div>

<div class="desc">
    BARRY BARRIES 회원가입은 CTC지갑 페이지에서 하실수 있습니다. CTC지갑 페이지에서 회원가입을 하시고 같은 아이디와 비밀번호로 로그인 하실 수 있습니다.
</div>

<div class="desc2">
    * 아래 버튼을 클릭하신후 CTC지갑 페이지에서 회원가입을 완료하세요.<br />
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
