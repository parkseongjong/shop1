<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

?>

<div id="mb_login" class="mbskin">
    <h1><?php echo $g5['title'] ?></h1>
    <div class="login">
        <p class="title">
            BARRY BARRIES 에 처음 오셨나요?
        </p>
        <p>
            BARRY BARRIES 로그인은 CTC지갑 페이지에서 할 수 있습니다. CTC지갑 페이지에서 로그인을 하고 마켓을 이용해주세요.
        </p>
        <p>
            * 아래 버튼을 클릭하신후 CTC지갑 페이지에서 로그인해주세요.
        </p>
        <p>
            <button type="submit" class="btn_submit" id="btn_go_wallet" >CTC 지갑에서 로그인</button>
        </p>
    </div>
</div>

<script>
jQuery(function($){
    $("#login_auto_login").click(function(){
        if (this.checked) {
            this.checked = confirm("자동로그인을 사용하시면 다음부터 회원아이디와 비밀번호를 입력하실 필요가 없습니다.\n\n공공장소에서는 개인정보가 유출될 수 있으니 사용을 자제하여 주십시오.\n\n자동로그인을 사용하시겠습니까?");
        }
    });
    $("#btn_go_wallet").on("click", function() {
        document.location.href = 'https://cybertronchain.com/wallet2';
    });
});

function flogin_submit(f)
{
    if( $( document.body ).triggerHandler( 'login_sumit', [f, 'flogin'] ) !== false ){
        return true;
    }
    return false;
}
</script>
