<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
<script>
    //Member orderlist 전역 변수
    barry_orderlist_page = <?php echo $page ?>;
    barry_orderlist_type = '<?php echo $memberOrderListType ?>';
    barry_member_skin_url = '<?php echo $member_skin_url ?>';

</script>
<section class="menu">
    <ul>
        <li class="<?php echo ($memberOrderListType == 'seller')?'on':'';?>" onclick="document.location.href='/bbs/memberOrderList.php?type=seller'">상품 판매 내역</li>
        <li class="<?php echo ($memberOrderListType == 'user')?'on':'';?>" onclick="document.location.href='/bbs/memberOrderList.php?type=user'">내가 주문한 내역</li>
    </ul>
</section>
