<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
<script>
    //Member orderDetail 전역 변수
    barry_orderlist_page = 1; //임시 처리
    barry_orderlist_type = 'seller';//임시 처리
    barry_member_skin_url = '<?php echo $member_skin_url ?>';
    barryVirtualWalletAddress = '<?php echo $memberOrderDetailInfo['memberInfo']['mb_1']; ?>';
</script>
