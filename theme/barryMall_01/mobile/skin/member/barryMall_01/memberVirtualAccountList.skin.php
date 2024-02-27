<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
//가상지갑 내용이 아직은 판매 주문 내역 상세와 다를게 없어서 같이 사용, 추 후 common으로 분리해서 사용.
add_javascript('<script src="'.$member_skin_url.'/seller/memberOrderDetail.js"></script>', 1);

?>
<script>
    //Member orderDetail 전역 변수
    barryVirtualWalletAddress = <?php echo ($memberVirtualAccountInfo['memberInfo']['mb_1'])?'"'.$memberVirtualAccountInfo['memberInfo']['mb_1'].'"':'false'; ?>;
</script>

<article id="virtualAccountList" class="contentsWrap">
    <div class="contents container-fluid">
        <!--tp3,etp3,mc,emc -->
        <article class="card virtualWalletAccountArea">
            <div class="title">
                <h3>가상지갑 거래내역</h3>
            </div>
            <section class="contents">
                <?php if($memberVirtualAccountInfo['memberInfo']['mb_1']): ?>
                    <section class="menu">
                        <ul class="flex-wrap">
                            <li data-type="tp3">TP3</li>
                            <li data-type="etp3">e-TP3</li>
                            <li data-type="mc">MC</li>
                            <li data-type="emc">e-MC</li>
                            <li data-type="ekrw">e-KRW</li>
                            <li data-type="ectc">e-CTC</li>
                        </ul>
                    </section>

                    <section class="accountInfo">
                        <h4 class="text-center"><?php echo $memberVirtualAccountInfo['memberInfo']['mb_name']; ?> 님의 가상지갑 주소</h4>
                        <h3 class="alert alert-info text-center"><?php echo $memberVirtualAccountInfo['memberInfo']['mb_1'] ?></h3>
                    </section>

                    <section class="accountList">
                        <ul>
                            <li></li>
                        </ul>
                        <span id="load" class="btn btn-secondary btn-block">더 보기</span>
                    </section>
                <?php else: ?>
                    <article class="emptyPage">
                        <div class="alert alert-info">
                            판매자가 아닙니다.
                        </div>
                    </article>
                <?php endif; ?>
            </section>
        </article>
    </div>
</article>