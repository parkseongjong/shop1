<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
add_javascript('<script src="'.$board_skin_url.'/writeModify.js"></script>', 0);
?>
<script>
    //write Javascript 전역 변수
    barryWritePriceType = <?php echo $write['wr_price_type']?'"'.$write['wr_price_type'].'"':'false'; ?>;
    barryWriteItemId = <?php echo ($write['wr_id'])?$write['wr_id']:'false'; ?>;
    barryWriteModifyStatus = <?php echo ($w)?'"'.$w.'"':'false' ?>;
    barryWriteSelectOptionStatus = <?php echo (!empty($write['it_option_subject']))?'true':'false'?>;
</script>
<!-- 게시물 수정 시작 { -->
<section id="bo_w" class="container-fluid">
    <h2 class="sound_only"><?php echo $g5['title'] ?></h2>
    <form name="itemUpload" id="itemUpload" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
        <input type="hidden" name="w" value="<?php echo $w ?>">
        <input type="hidden" name="tableId" id="tableId" value="<?php echo $bo_table ?>">
        <input type="hidden" name="sca" value="<?php echo $sca ?>">
        <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
        <input type="hidden" name="stx" value="<?php echo $stx ?>">
        <input type="hidden" name="spt" value="<?php echo $spt ?>">
        <input type="hidden" name="sst" value="<?php echo $sst ?>">
        <input type="hidden" name="sod" value="<?php echo $sod ?>">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <div class="form-group">
            <div class="title">
                <h3>상품 정보</h3>
            </div>
            <section class="card mb-3">
                <div class="row no-gutters">
                    <div class="col-4">
                        <img class="card-img" src="<?php echo $goodsInfo['thumb']['src']; ?>"alt="상품 이미지">
                    </div>
                    <div class="col-8">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $write['wr_subject']?></h5>
                            <p class="card-text">
                                <?php echo $goodsInfo['content']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            <section class="info">
                <ul class="contents left">
                    <?php foreach ($goodsStatusInfo as $key => $value):?>
                        <?php if($value['status'] == 0):?>
                            <li>
                                <h4><?php echo $value['title'] ?></h4><span><span <?php echo($value['type'] == 'text'?' ':'class="badge badge-danger"');?>><?php echo $value['msg']; ?></span></span>
                            </li>
                        <?php else: ?>
                            <li>
                                <h4><?php echo $value['title'] ?></h4><span><span <?php echo($value['type'] == 'text'?' ':'class="badge badge-success"');?>><?php echo $value['msg']; ?></span></span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach;?>
                </ul>
            </section>
            <div class="alert alert-info modifyAlert" role="alert">
                <p>이미 승인 된 상품 수정 시 재고 수량만 변경 가능 합니다.</p>
                <p>정상 또는 품절 상품으로 설정하고 싶다면 ? 설정 > 내 상품관리 > 상품 선택 > 정상 또는 품절 상태 변경 버튼 클릭 으로 이용 가능합니다.</p>
            </div>
        </div>

        <div class="form-group">
            <div class="title">
                <h3>상품 가격 및 재고(필수 항목)</h3>
            </div>

            <article>
                <label for="itemStockQty" class="sound_only">재고수량</label>
                <h3 class="badge badge-dark">
                    - 재고수량을 0 일 때 품절상품으로 노출됩니다.<br>
                    (선택 옵션이 있는 경우 선택 옵션의 재고 수량을 우선으로 확인 합니다)
                </h3>
                <section>
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">재고수량</span>
                        </div>
                        <input type="number" name="itemStockQty" value="<?php echo(isset($write['it_stock_qty']))?$write['it_stock_qty']:1 ?>" id="itemStockQty" class="form-control" size="8" min="1" placeholder="재고수량" required>
                    </div>
                </section>
            </article>
            <article>
                <label for="minimumRetailPrice" class="sound_only">쇼핑몰 최저가</label>
                <section>
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">쇼핑몰 최저가</span>
                        </div>
                        <input type="number" name="minimumRetailPrice" value="<?php echo(isset($write['it_minimum_retail_price']))?$write['it_minimum_retail_price']:0 ?>" id="minimumRetailPrice" class="form-control" size="8" min="1" placeholder="쇼핑몰 최저가">
                    </div>
                </section>
            </article>
            <article>
                <label for="" class="sound_only">현금(원)</label>
                <section>
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">현금(원)</span>
                        </div>
                        <input type="number" name="priceKrw" value="<?php echo(isset($write['wr_10']))?$write['wr_10']:0 ?>" id="minimumRetailPrice" class="form-control" size="8" min="1" placeholder="현금(원)">
                    </div>
                </section>
            </article>
        </div>

        <?php if($write['it_limit'] == 1): ?>
        <div class="form-group">
            <div class="title">
                <h3>한정 판매 설정(선택 항목)</h3>
            </div>
            <div id="limitCollapse">
                <article>
                    <h3 class="badge badge-dark">
                        - 제한 주문 수량이 1 이상 일 때 자동으로 한정 판매를 사용 합니다.<br>
                    </h3>
                    <section>
                        <div class="input-group mb-1">
                            <div class="input-group-prepend">
                                <span class="input-group-text">1 인당 제한 주문 수량</span>
                            </div>
                            <input type="number" name="itemLimitQty" value="<?php echo(isset($write['it_limit_qty']))?$write['it_limit_qty']:0 ?>" id="itemLimitQty" class="form-control" size="8" min="0" placeholder="제한 구매 수량">
                        </div>
                    </section>
                </article>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($write['it_option_subject'])): ?>
            <div class="form-group">
                <div class="title">
                    <h3>상품 선택옵션(선택 항목)</h3>
                </div>
                <div id="sit_option_frm">

                </div>
            </div>
        <?php endif;?>
        <div class="form-group btnConfirm">
            <a href="./board.php?bo_table=<?php echo $bo_table ?>" class="btn btn-dark">취소</a>
            <input type="submit" value="수정완료" id="btn_submit" accesskey="s" class="btn btn-success">
        </div>

    </form>
</section>

<!-- } 게시물 수정 끝 -->