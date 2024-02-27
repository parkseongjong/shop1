<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<style type="text/css">
.title {font-size:19px; padding:20px 0;text-align:center;}
.desc {padding:5px 23px; font-size:15px;text-align:center;}
.desc2 {padding:5px 30px; font-size:15px;text-align:center;margin-top:15px;}
</style>

<div id="mb_login" class="mbskin" style="font-size: 1.3em;">
    <h1><?php echo $g5['title'] ?></h1>

    <div class="title">
        BARRY BARRIES 오신것을 환영합니다!!
    </div>

    <div class="desc">
        BARRY BARRIES 로그인은 CTC지갑에서 하실수 있습니다. CTC지갑 페이지에서 로그인 하시고 하단의 베리베리 링크를 누르시면 됩니다.
    </div>

    <style type="text/css">
    .desc2 {padding:45px 30px 0 30px; margin:0 20px;font-size:15px;color:#777;text-align:center;margin-top:35px; border-top:1px solid #ccc;}
    .mbskin p {padding:30px 27px 45px 0; font-size:15px; margin:0 20px;}
    </style>

    <div class="desc2">
        * 아래 버튼을 클릭하신후 CTC지갑 페이지에서 로그인을 완료하세요.<br />
    </div>


    <div class="mbskin">
        <p>
            <button type="submit" class="btn_submit" id="btn_go_wallet" >CTC 지갑에서 로그인</button>
        </p>
    </div>


    <?php // 쇼핑몰 사용시 여기부터 ?>
    <?php if ($default['de_level_sell'] == 1) { // 상품구입 권한 ?>

        <!-- 주문하기, 신청하기 -->
        <?php if (preg_match("/orderform.php/", $url)) { ?>

    <section id="mb_login_notmb" class="mbskin">
        <h2>비회원 구매</h2>

        <p>
            비회원으로 주문하시는 경우 포인트는 지급하지 않습니다.
        </p>

        <div id="guest_privacy">
            <?php echo conv_content($default['de_guest_privacy'], $config['cf_editor']); ?>
        </div>

        <label for="agree">개인정보수집에 대한 내용을 읽었으며 이에 동의합니다.</label>
        <input type="checkbox" id="agree" value="1">

        <div class="btn_confirm">
            <a href="javascript:guest_submit(document.flogin);" class="btn_submit">비회원으로 구매하기</a>
        </div>

        <script>
        function guest_submit(f)
        {
            if (document.getElementById('agree')) {
                if (!document.getElementById('agree').checked) {
                    alert("개인정보수집에 대한 내용을 읽고 이에 동의하셔야 합니다.");
                    return;
                }
            }
            f.url.value = "<?php echo $url; ?>";
            f.action = "<?php echo $url; ?>";
            f.submit();
        }
        </script>
    </section>

    <?php } else if (preg_match("/orderinquiry.php$/", $url)) { ?>
    <div class="mbskin" id="mb_login_od_wr">
        <h2>비회원 주문조회 </h2>

        <fieldset id="mb_login_od">
            <legend>비회원 주문조회</legend>

            <form name="forderinquiry" method="post" action="<?php echo urldecode($url); ?>" autocomplete="off">

            <label for="od_id" class="od_id sound_only">주문서번호<strong class="sound_only"> 필수</strong></label>
            <input type="text" name="od_id" value="<?php echo $od_id; ?>" id="od_id" required class="frm_input required" size="20" placeholder="주문서번호">
            <label for="id_pwd" class="od_pwd sound_only" >비밀번호<strong class="sound_only"> 필수</strong></label>
            <input type="password" name="od_pwd" size="20" id="od_pwd" required class="frm_input required" placeholder="비밀번호">
            <input type="submit" value="확인" class="btn_submit">

            </form>
        </fieldset>

        <section id="mb_login_odinfo">
            <p>메일로 발송해드린 주문서의 <strong>주문번호</strong> 및 주문 시 입력하신 <strong>비밀번호</strong>를 정확히 입력해주십시오.</p>
        </section>

    </div>
    <?php } ?>

    <?php } ?>
    <?php // 쇼핑몰 사용시 여기까지 반드시 복사해 넣으세요 ?>

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
