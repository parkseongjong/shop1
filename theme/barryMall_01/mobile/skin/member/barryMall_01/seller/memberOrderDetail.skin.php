<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
//add_javascript('<script src="'.$member_skin_url.'/seller/memberOrderDetail.js"></script>', 1);
add_javascript('<script src="'.$member_skin_url.'/common/memberOrderCommon.js"></script>', 1);
add_javascript('<script src="'.$member_skin_url.'/seller/memberOrderDetail.js"></script>', 1);
include_once ($member_skin_path.'/common/memberOrderDetailHead.php');
?>

<article id="goodsInfoDetail" class="contentsWrap">
    <div class="contents container-fluid">
        <ul>
            <li>
                <section
                        class="card mb-3 <?php echo ($memberOrderDetailInfo['wr_status'] =='finish')?'bg-secondary':'';?> <?php echo ($memberOrderDetailInfo['wr_status'] =='cancel')?'bg-warning ':'';?>"
                >
                    <div class="row no-gutters">
                        <div class="col-4 imgArea">
                            <?php if($memberOrderDetailInfo['thumb']): ?>
                                <img class="card-img" src="<?php echo $memberOrderDetailInfo['thumb']['src']; ?>" alt="상품 이미지">
                            <?php else: ?>
                                <div class="notImage">이미지가 없습니다.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-8">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $memberOrderDetailInfo['itemSubject']?></h5>
                                <p class="card-text">
                                    <section class="info">
                                        <ul class="contents left">
                                            <li>
                                                <h4>주문 번호</h4>
                                                <span><?php echo $memberOrderDetailInfo['wr_id']; ?></span>
                                            </li>
											<li>
                                                <h4>카드 주문 번호</h4>
                                                <span><?php echo $memberOrderDetailInfo['od_number']; ?></span>
                                            </li>
                                            <li>
                                                <h4>상품 번호</h4>
                                                <span><?php echo $memberOrderDetailInfo['itemId']; ?></span>
                                            </li>
                                            <li>
                                                <h4>결제 수단</h4>
                                                <span><?php echo $memberOrderDetailInfo['paymentType']; ?>(<?php echo $cardNameJsonType['cardName'] ?>)</span>
                                            </li>
                                            <li>
                                                <h4>주문자</h4>
                                                <span><?php echo $memberOrderDetailInfo['wr_4']; ?></span>
                                            </li>
                                            <li>
                                                <h4>주문자 연락처</h4>
                                                <span><?php echo $memberOrderDetailInfo['wr_5']; ?></span>
                                            </li>
                                            <li>
                                                <h4 class="textHit">수령인</h4>
                                                <span><?php echo $memberOrderDetailInfo['wr_11']; ?></span>
                                            </li>
                                            <li>
                                                <h4 class="textHit">수령인 연락처</h4>
                                                <span><?php echo $memberOrderDetailInfo['wr_12']; ?></span>
                                            </li>
                                        </ul>
                                    </section>
                                </p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card-body">
                                <section class="info">
                                    <ul class="contents left">
                                        <li>
                                            <h4 class="textHit">배송지</h4>
                                            <span><?php echo $memberOrderDetailInfo['receiverAddress']; ?></span>
                                        </li>
                                        <li>
                                            <h4>주문 일시</h4>
                                            <span><?php echo $memberOrderDetailInfo['wr_datetime']; ?></span>
                                        </li>
                                        <li>
                                            <h4>배송 상태</h4>
                                            <span>
                                            <?php if ($memberOrderDetailInfo['wr_status'] =='order'): ?>
                                                <span class="badge badge-pill badge-dark">진행중</span>
                                            <?php elseif ($memberOrderDetailInfo['wr_status'] =='delivery'): ?>
                                                <span class="badge badge-pill badge-warning">배송중</span>
                                            <?php elseif ($memberOrderDetailInfo['wr_status'] =='finish'): ?>
                                                <span class="badge badge-pill badge-success">완료</span>
                                            <?php elseif ($memberOrderDetailInfo['wr_status'] =='cancel'): ?>
                                                <span class="badge badge-pill badge-dark">주문 취소</span>
                                            <?php endif; ?>
                                            </span>
                                        </li>
                                        <li class="flex-column mt-1 mb-1">
                                            <?php if ($memberOrderDetailInfo['wr_status'] =='order'): ?>
                                                <span class="btn btn-dark btn-block" onclick="goDelivery('<?php echo $memberOrderDetailInfo['wr_id']; ?>')">배송시작으로 변경</span>
                                            <?php elseif ($memberOrderDetailInfo['wr_status'] =='delivery'): ?>
                                                <?php if ($memberOrderDetailInfo['invoiceInfo'] !== false): ?>
                                                    <div class="card text-white bg-dark mb-3">
                                                        <div class="card-header">[배송 회사]<?php echo $memberOrderDetailInfo['invoiceInfo']['boi_corp'] ?></div>
                                                        <div class="card-body">
                                                            <h5 class="card-title">[운송장 번호]<?php echo $memberOrderDetailInfo['invoiceInfo']['boi_number'] ?></h5>
                                                        </div>
                                                        <div class="card-footer">[운송장 번호 입력 일시]<?php echo $memberOrderDetailInfo['invoiceInfo']['boi_datetime'] ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="btn btn-warning btn-block" onclick="goCancelDelivery('<?php echo $memberOrderDetailInfo['wr_id']; ?>')">배송 취소로 변경</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </section>
                                <section class="info">
                                    <div class="title">
                                        <h3>주문 금액</h3>
                                        <span><?php echo number_format($memberOrderDetailInfo['itemCartTotalPrice']).''.$memberOrderDetailInfo['paymentType'];?></span>
                                    </div>
                                    <ul class="contents">
                                        <li>
                                            <h4>상품금액</h4>
                                            <span><?php echo number_format($memberOrderDetailInfo['wr_ct_price']).''.$memberOrderDetailInfo['paymentType'];?></span>
                                        </li>
                                        <li>
                                            <h4 class="wide">선택 옵션 추가금액</h4>
                                            <span><?php echo number_format($memberOrderDetailInfo['wr_io_price']).''.$memberOrderDetailInfo['paymentType'];?></span>
                                        </li>
                                        <li>
                                            <h4>수량</h4>
                                            <span><?php echo number_format($memberOrderDetailInfo['wr_6']);?></span>
                                        </li>
                                    </ul>
                                </section>
                                <section class="info">
                                    <div class="title">
                                        <h3>결제 정보</h3>
                                    </div>
                                    <ul class="contents left">
                                        <li>
                                            <h4>결제 상태</h4>
                                            <span>

                                                <?php if($memberOrderDetailInfo['wr_10'] == 'completePayment'):?>
													<span class="badge badge-success">결제 완료</span>
                                                <?php elseif($memberOrderDetailInfo['wr_10'] == 'deferredPayment'):?>
                                                    <span class="badge badge-warning">주문 완료</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">결제 대기</span>
                                                <?php endif; ?>
                                            </span>
                                        </li>
                                        <li>
                                            <?php if($memberOrderDetailInfo['wr_price_type'] == 'CREDITCARD'): ?>
                                                <h4>승인 번호</h4>
                                                <span><?php echo ($memberOrderDetailNumber !== false)?$memberOrderDetailNumber['bpps_auth_number']:'승인 번호가 없습니다.'; ?></span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </section>
                            </div>
                        </div>
                    </div>
                </section>
            </li>
        </ul>
        <!--tp3,etp3,mc,emc -->
        <article class="card virtualWalletAccountArea">
            <div class="title">
                <h3>가상지갑 거래내역</h3>
            </div>
            <section class="contents">
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
                    <h4 class="text-center"><?php echo $memberOrderDetailInfo['memberInfo']['mb_name']; ?> 님의 가상지갑 주소</h4>
                    <h3 class="alert alert-info text-center"><?php echo $memberOrderDetailInfo['memberInfo']['mb_1'] ?></h3>
                </section>

                <section class="accountList">
                    <ul>
                        <li></li>
                    </ul>
                    <span id="load" class="btn btn-secondary btn-block">더 보기</span>
                </section>
            </section>
        </article>
    </div>
</article>
