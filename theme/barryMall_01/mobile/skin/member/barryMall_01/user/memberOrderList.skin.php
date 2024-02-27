<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
add_javascript('<script src="'.$member_skin_url.'/user/memberOrderList.js"></script>', 1);
include_once ($member_skin_path.'/common/memberOrderListHead.php');
?>
<?php
if($memberOrderList['count'] != 0):
    $memberOrderListCount = $memberOrderList['count']-1;
?>
    <article id="goodsInfoList" class="contentsWrap">
        <div class="contents container-fluid">
            <ul>
                <?php for($i=0;$i<=$memberOrderListCount;$i++): ?>
                    <li>
                        <section
                                class="card mb-3 <?php echo ($memberOrderList[$i]['wr_status'] =='finish')?'bg-secondary':'';?> <?php echo ($memberOrderList[$i]['wr_status'] =='cancel')?'bg-warning ':'';?>"
                        >
                            <div class="row no-gutters">
                                <div class="col-4 imgArea">
                                    <?php if($memberOrderList[$i]['thumb']): ?>
                                        <img class="card-img" src="<?php echo $memberOrderList[$i]['thumb']['src']; ?>"alt="상품 이미지">
                                    <?php else: ?>
                                        <div class="notImage">이미지가 없습니다.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-8" onclick="goTempDetail('<?php echo $memberOrderList[$i]['wr_id']?>','<?php echo $memberOrderList[$i]['wr_9']?>')">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <?php echo $memberOrderList[$i]['itemInfo']['wr_subject']?>
                                            <?php if($memberOrderList[$i]['wr_10'] == 'completePayment'):?>
												<span class="badge badge-success">결제 완료</span>
                                            <?php elseif($memberOrderList[$i]['wr_10'] == 'deferredPayment'):?>
                                                <span class="badge badge-warning">주문 완료</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">결제 대기</span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="card-text">
                                            <section class="info">
                                                <ul class="contents left">
                                                    <li>
                                                        <h4>주문 번호</h4>
                                                        <span><?php echo $memberOrderList[$i]['wr_id']; ?></span>
                                                    </li>
    <!--                                                <li>-->
    <!--                                                    <h4>상품 번호</h4>-->
    <!--                                                    <span>--><?php //echo $memberOrderList[$i]['itemInfo']['wr_id']; ?><!--</span>-->
    <!--                                                </li>-->
                                                    <li>
                                                        <h4>결제 수단</h4>
                                                        <span>
                                                            <?php echo $memberOrderList[$i]['paymentType']; ?>
                                                            <?php if($memberOrderList[$i]['cardInfo'] !=false ): ?>
                                                                (<?php echo ($memberOrderList[$i]['cardInfo']['cardName']) ?>)
                                                            <?php endif; ?>
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <h4>주문자</h4>
                                                        <span><?php echo $memberOrderList[$i]['wr_4']; ?></span>
                                                    </li>
                                                    <li>
                                                        <h4>주문자 연락처</h4>
                                                        <span><?php echo $memberOrderList[$i]['wr_5']; ?></span>
                                                    </li>
                                                    <li>
                                                        <h4 class="textHit">수령인</h4>
                                                        <span><?php echo $memberOrderList[$i]['wr_11']; ?></span>
                                                    </li>
                                                    <li>
                                                        <h4 class="textHit">수령인 연락처</h4>
                                                        <span><?php echo $memberOrderList[$i]['wr_12']; ?></span>
                                                    </li>
                                                    <li>
                                                        <h4>주문 금액</h4>
                                                        <span><?php echo number_format($memberOrderList[$i]['itemCartTotalPrice']).''.$memberOrderList[$i]['paymentType'];?></span>
                                                    </li>
                                                </ul>
                                            </section>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card-body">
                                        <section class="info">
                                            <ul class="contents left">
                                                <li>
                                                    <h4 class="textHit">배송지</h4>
                                                    <span><?php echo $memberOrderList[$i]['receiverAddress']; ?></span>
                                                </li>
                                                <li>
                                                    <h4>주문 일시</h4>
                                                    <span><?php echo $memberOrderList[$i]['wr_datetime']; ?></span>
                                                </li>
                                                <li>
                                                    <h4>배송 상태</h4>
                                                    <span>
                                                        <?php if ($memberOrderList[$i]['wr_status'] =='order'): ?>
                                                            <span class="badge badge-pill badge-dark">진행중</span>
                                                        <?php elseif ($memberOrderList[$i]['wr_status'] =='delivery'): ?>
                                                            <span class="badge badge-pill badge-warning">배송이 시작되었습니다.</span>
                                                        <?php elseif ($memberOrderList[$i]['wr_status'] =='finish'): ?>
                                                            <span class="badge badge-pill badge-success">완료</span>
                                                        <?php elseif ($memberOrderList[$i]['wr_status'] =='cancel'): ?>
                                                            <span class="badge badge-pill badge-dark">주문 취소</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </li>
                                                <li class="flex-column mt-1 mb-1">
                                                    <?php if ($memberOrderList[$i]['wr_status'] =='order'): ?>
                                                        <p class="alert alert-dark">판매자가 주문정보를 확인중 입니다.</p>
                                                    <?php elseif ($memberOrderList[$i]['wr_status'] =='delivery'): ?>
                                                        <?php if ($memberOrderList[$i]['invoiceInfo'] !== false): ?>
                                                            <div class="card text-white bg-dark mb-3">
                                                                <div class="card-header">[배송 회사]<?php echo $memberOrderList[$i]['invoiceInfo']['boi_corp'] ?></div>
                                                                <div class="card-body">
                                                                    <h5 class="card-title">[운송장 번호]<?php echo $memberOrderList[$i]['invoiceInfo']['boi_number'] ?></h5>
                                                                </div>
                                                                <div class="card-footer">[운송장 번호 입력 일시]<?php echo $memberOrderList[$i]['invoiceInfo']['boi_datetime'] ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <p class="alert alert-info">상품을 전달 받으셨나요? 배송 완료 버튼을 눌러주세요.</p>
                                                        <span class="btn btn-success btn-block" onclick="goFinishDelivery('<?=$memberOrderList[$i]['wr_id']?>','<?php echo ($memberOrderList[$i]['od_etoken_log_id'] == NULL)?0:$memberOrderList[$i]['od_etoken_log_id']; ?>')">배송 완료</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li class="flex-column mt-1 mb-1">
                                                    <span class="btn btn-info btn-block" onclick="goTempDetail('<?php echo $memberOrderList[$i]['wr_id']?>','<?php echo $memberOrderList[$i]['wr_9']?>')">상세 정보 보기</span>
                                                </li>
                                            </ul>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    </article>
    <?php echo($write_pages); ?>
<?php else: ?>
    <article id="goodsInfoList" class="contentsWrap">
        <div class="contents container-fluid">
            <ul>
                <li class="empty">
                    <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/bbs_empty.png" />
                    <p>주문 내역이 없어요!</p>
                </li>
            </ul>
        </div>
    </article>
<?php endif; ?>
