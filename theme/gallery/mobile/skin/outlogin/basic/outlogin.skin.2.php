<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$outlogin_skin_url.'/style.css">', 0);
?>

<!-- 로그인 후 외부로그인 시작 -->
<div id="ol_after" class="ol">
   
    <button type="button" class="prf_btn"><?php echo get_member_profile_img($member['mb_id']); ?> <?php echo $nick ?> <i class="fa fa-angle-down"></i></button>

    <ul id="ol_after_private">
        <li><a href="<?php echo G5_BBS_URL ?>/logout.php">로그아웃</a> </li>
        <li><a href="<?php echo G5_BBS_URL ?>/member_setting.php" title="설정">설정</a></li>
        <li><a href="https://cybertronchain.com/wallet2" title="CTC지갑">CTC지갑 바로가기</a></li>
    </ul>

</div>

<script>
// 탈퇴의 경우 아래 코드를 연동하시면 됩니다.
function member_leave()
{
    if (confirm("정말 회원에서 탈퇴 하시겠습니까?"))
        location.href = "<?php echo G5_BBS_URL ?>/member_confirm.php?url=member_leave.php";
}

$(".prf_btn").on("click", function() {
    $("#ol_after_private").toggle();
});

$(document).mouseup(function (e){
    var container = $("#ol_after_private");
    if( container.has(e.target).length === 0)
    container.hide();
});

</script>

<!-- 로그인 후 외부로그인 끝 -->
