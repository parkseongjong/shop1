<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>
<div class="mbskin">
    <div class="login">
        <p class="title">
            BARRY BARRIES 에 처음 오셨나요?
        </p>
        <p>
            BARRY BARRIES 회원가입은 CTC지갑 페이지에서 할 수 있습니다. CTC지갑 페이지에서 회원가입을 하고 같은 아이디와 비밀번호로 로그인 할 수 있습니다.
        </p>
        <p>
            * 아래 버튼을 클릭하신후 CTC지갑 페이지에서 회원가입을 완료하세요.
        </p>
        <p>
            <button type="submit" class="btn_submit" id="btn_go_wallet" >CTC지갑 바로가기</button>
        </p>

    </div>
</div>

<script>
jQuery(function($){
    $("#btn_go_wallet").on("click", function() {
        document.location.href = 'https://cybertronchain.com/wallet2';
    });
});
</script>
