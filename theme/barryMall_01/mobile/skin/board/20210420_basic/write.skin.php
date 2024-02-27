<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/item/css/cropper.css">', 1);
add_javascript('<script src="'.$board_skin_url.'/write.js"></script>', 0);
//add_javascript('<script src="'.$board_skin_url.'/rolldate/js/rolldate.min.js"></script>', 0);
add_javascript('<script src="'.$board_skin_url.'/item/js/cropper.min.js"></script>', 1);
?>
<script>
    //write Javascript 전역 변수
    barryWritePriceType = <?php echo $write['wr_price_type']?'"'.$write['wr_price_type'].'"':'false'; ?>;
    barryWriteItemId = <?php echo ($write['wr_id'])?$write['wr_id']:'false'; ?>;
    barryWriteModifyStatus = <?php echo ($w)?'"'.$w.'"':'false' ?>;
    barryWriteSelectOptionStatus = <?php echo (!empty($write['it_option_subject']))?'true':'false'?>;
</script>
<!-- 게시물 작성 시작 { -->
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
    <?php
    $option = '';
    $option_hidden = '';
    if ($is_notice || $is_html || $is_secret || $is_mail) {
        $option = '';
        if ($is_notice) {
            $option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
        }

        if ($is_html) {
            if ($is_dhtml_editor) {
                $option_hidden .= '<input type="hidden" value="html1" name="html">';
            } else {
                $option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">HTML</label>';
            }
        }

        if ($is_secret) {
            if ($is_admin || $is_secret==1) {
                $option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">비밀글</label>';
            } else {
                $option_hidden .= '<input type="hidden" name="secret" value="secret">';
            }
        }

        if ($is_mail) {
            $option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
        }

    }
    echo $option_hidden;
    ?>

    <?php if ($option): ?>
        <div class="form-group">
            <span class="sound_only">옵션</span>
            <?php echo $option ?>
        </div>
    <?php endif; ?>
    <div class="form-group">
        <article>
            <a href="#goodsInfo01" class="btn btn-secondary" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="goodsInfo01">
                <h3>상품판매 등록 안내사항 보기</h3>
            </a>
            <!-- 임시 처리..-->
            <div class="collapse" id="goodsInfo01">
                <img src="https://barrybarries.kr/data/editor/2103/5c7e6effb4a5766202cf986aa805fc3a_1615539585_094.png" class="img-fluid mt-1" alt="상품판매 등록 안내사항">
            </div>
        </article>
    </div>
    <div class="form-group">
        <div class="title">
            <h3>상품 가격 및 재고(필수 항목)</h3>
        </div>
        <article class="coinTypeCheckArea mb-3">
            <label class="sound_only">코인 타입</label>
            <ul class="list-group coinType">
                <li class="list-group-item" data-type="coin">e-TP3, e-MC 코인으로 판매<b>(e-TP3, e-MC)</b></li>
                <li class="list-group-item" data-type="krw">현금으로 판매<b>(원)</b></li>
            </ul>
        </article>

        <article id="coinSelect">
            <h3 class="badge badge-dark">- 현금(원)을 입력하시면 e-TP3, e-MC 판매 금액이 자동 적용 됩니다.</h3>
            <section class="krwCostingArea">
                <div class="input-group mb-1">
                    <div class="input-group-prepend">
                        <span class="input-group-text">현금(원)</span>
                    </div>
                    <input type="number" name="krwCosting" value="<?php echo (isset($write['it_cast_price']))?(int)$write['it_cast_price']:''; ?>" id="krwCosting" class="form-control" size="30" min="1" placeholder="판매금액 (숫자만 입력.현금)">
                </div>
                <div id="krwCosingWait" class="flex-column align-items-center alert alert-info mb-1">
                    <strong>현금(원) <i class="fa fa-arrow-right"></i> e-TP3 환산 중</strong>
                    <div class="spinner-border text-success" role="status">
                        <span class="sr-only">변환 중</span>
                    </div>
                </div>
                <div class="flex-column align-items-center alert alert-info mb-1">
                    <strong id="coinPerValue"></strong> e-TP3 당 <strong id="coinRate"></strong>원

                </div>
            </section>
            <h3 class="badge badge-dark">- e-TP3, e-MC 모두 입력 하시면 두 코인 결제가능합니다.</h3>
            <section class="etp3Area">
                <div class="input-group mb-1">
                    <div class="input-group-prepend">
                        <span class="input-group-text">e-TP3</span>
                    </div>
                    <input type="number" name="priceEtp3" value="<?php echo ($wr_1==0)?'':$wr_1 ?>" id="priceEtp3" class="form-control" size="30" min="1" placeholder="현금(원) → e-TP3 판매금액" required readonly>
                </div>
            </section>

            <section class="emcArea">
                <div class="input-group mb-1">
                    <div class="input-group-prepend">
                        <span class="input-group-text">e-MC</span>
                    </div>
                    <input type="number" name="priceEmc" value="<?php echo ($wr_2==0)?'':$wr_2 ?>" id="priceEmc"  class="form-control" size="30" min="1" placeholder="현금(원) → e-MC 판매금액" required readonly>
                </div>
            </section>

            <section>
                <div class="input-group mb-1">
                    <div class="input-group-prepend">
                        <span class="input-group-text">소비자가</span>
                    </div>
                    <input type="number" name="retailPrice" value="<?php if (isset($write['wr_retail_price'])) { echo $write['wr_retail_price']; } ?>" id="retailPrice" class="form-control" size="50" min="1" required readonly>
                </div>
            </section>
        </article>

        <article id="krwSelect">
            <h3 class="badge badge-dark">- 현금으로 결제됩니다.</h3>
            <section class="krwArea">
                <div class="input-group mb-1">
                    <div class="input-group-prepend">
                        <span class="input-group-text">현금(원)</span>
                    </div>
                    <input type="number" name="priceKrw" value="<?php echo $wr_10 ?>" id="priceKrw"  class="form-control " size="25" min="1" placeholder="판매금액 (숫자만 입력.현금)" required>
                </div>
            </section>
        </article>

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
        <!-- 재고 통보 수량 일시 사용 중지 -->
<!--        <article>-->
<!--            <label for="it_noti_qty" class="sound_only">재고 통보수량</label>-->
<!--            <h3 class="badge badge-dark">-->
<!--                - 상품의 재고가 통보수량보다 작을 때 품절 상품으로 노출됩니다.<br>-->
<!--                재고 수량 보다 큰 값이 될 수 없습니다.<br>-->
<!--                사용하지 않는다면 0으로 둡니다.<br>-->
<!--                (선택 옵션이 있는 경우 선택 옵션의 재고 통보수량을 우선으로 확인 합니다)-->
<!--            </h3>-->
<!--            <section>-->
<!--                <div class="input-group mb-1">-->
<!--                    <div class="input-group-prepend">-->
<!--                        <span class="input-group-text">재고 통보수량</span>-->
<!--                    </div>-->
                    <input type="hidden" name="itemNotiQty" value="<?php echo(isset($write['it_noti_qty']))?$write['it_noti_qty']:0 ?>" id="itemNotiQty" class="form-control" size="8" min="0" placeholder="재고 통보수량" required>
<!--                </div>-->
<!--            </section>-->
<!--        </article>-->
    </div>

    <div class="form-group">
        <div class="title">
            <a href="#limitCollapse" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="limitCollapse"><h3>한정 판매 설정(선택 항목)</h3></a>
        </div>
        <div class="collapse" id="limitCollapse">
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
            <article>
                <h3 class="badge badge-dark">
                    - 시작일 이전 또는 종료일이 이후에는 주문이 불가능 합니다.<br>
                </h3>
                <section>
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">한정 판매 시작일</span>
                        </div>
                        <input type="text" name="itemLimitActivativationDatetime" value="<?php echo(isset($write['it_limit_activativation_datetime']))?$write['it_limit_activativation_datetime']:''; ?>" id="itemLimitActivativationDatetime" class="form-control" size="20" min="0" placeholder="시작일자" readonly>
                    </div>
                </section>
                <section>
                    <div class="input-group mb-1">
                        <div class="input-group-prepend">
                            <span class="input-group-text">한정 판매 종료일</span>
                        </div>
                        <input type="text" name="itemLimitDeactivativationDatetime" value="<?php echo(isset($write['it_limit_deactivativation_datetime']))?$write['it_limit_deactivativation_datetime']:''; ?>" id="itemLimitDeactivativationDatetime" class="form-control" size="20" min="0" placeholder="종료일자" readonly>
                    </div>
                </section>
            </article>
        </div>
    </div>

    <div class="form-group">
        <div class="title">
            <h3>상품 정보(필수 항목)</h3>
        </div>
        <label for="itemSubject" class="sound_only">제목<strong>필수</strong></label>
        <div id="autosave_wrapper" class="mb-1">
            <input type="text" name="itemSubject" value="<?php echo $subject ?>" id="itemSubject" required class="form-control required" size="50" maxlength="255" placeholder="제목" required>
        </div>
    <?php if ($is_category) { ?>
        <label for="itemCategory"  class="sound_only">분류<strong>필수</strong></label>
        <select name="itemCategory" id="itemCategory" class="form-control custom-select mb-1" required>
            <option value="">분류를 선택하세요</option>
            <?php echo $category_option ?>
        </select>
    <?php } ?>
        <label for="itemContents" class="sound_only">내용<strong>필수</strong></label>
        <div class="itemContents <?php echo $is_dhtml_editor ? $config['cf_editor'] : ''; ?>">
            <?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
        </div>
    </div>

    <div class="form-group">
        <div class="title">
            <h3>상품 선택옵션(선택 항목)</h3>
        </div>
        <div class="sit_option">
            <div class="alert alert-info optionsAlert" role="alert">
                <p>옵션항목은 콤마(,) 로 구분하여 여러개를 입력할 수 있습니다.</p>

                <p>옷을 예로 들어</p> 
                <p>[옵션1 : 사이즈 , 옵션1 항목 : XXL,XL,L,M,S]</p>
                <p>[옵션2 : 색상 , 옵션2 항목 : 빨,파,노]</p>
                <p><b>옵션명과 옵션항목에 따옴표(\', ")는 입력할 수 없습니다.</b></p>
                <p>옵션 목록 버튼을 클릭하여 각 선택 옵션 별 가격을 설정 할 수 있습니다.</p>
            </div>
            <div class="form-group">
                <label for="opt1_subject">옵션1</label>
                <input type="text" name="optSubject[]" value="<?php echo $opt_subject[0]; ?>" id="opt1_subject" class="form-control" size="15" placeholder="예시: 크기">

                <label for="opt1"><b>옵션1 항목</b></label>
                <input type="text" name="optSubjectValue[]" value="" id="opt1" class="form-control" size="50" placeholder="예시: 큰사이즈,중간사이즈,작은사이즈">
            </div>
            <div class="form-group">
                <label for="opt2_subject">옵션2</label>
                <input type="text" name="optSubject[]" value="<?php echo $opt_subject[1]; ?>" id="opt2_subject" class="form-control" size="15" placeholder="예시 : 색상">

                <label for="opt2"><b>옵션2 항목</b></label>
                <input type="text" name="optSubjectValue[]" value="" id="opt2" class="form-control" size="50" placeholder="예시: 노란색,검은색,파란색">
            </div>
            <div class="form-group">
                <label for="opt3_subject">옵션3</label>
                <input type="text" name="optSubject[]" value="<?php echo $opt_subject[2]; ?>" id="opt3_subject" class="form-control" size="15">

                <label for="opt3"><b>옵션3 항목</b></label>
                <input type="text" name="optSubjectValue[]" value="" id="opt3" class="form-control" size="50">
            </div>

            <button type="button" id="option_table_create" class="btn btn-dark btn-block">옵션목록생성</button>
        </div>
        <div id="sit_option_frm">

        </div>
    </div>

    <div id="imageUpload" class="form-group">
        <div class="title">
            <h3>상품 사진 설정(필수 항목)</h3>
        </div>
        <div class="alert alert-danger imageFilesAlert" role="alert">
            <p><b>주의!</b> 상품 사진에는 판매 상품명과 실물 상품 사진이 반드시 포함 되어야 합니다.</p>
            <p>상품 사진에 판매 품목이 정확히 무엇인지 노출 되지 않는다면 상품 승인에 불이익을 받을 수 있습니다.</p>
        </div>
        <div class="alert alert-info imageFilesAlert" role="alert">
            <p>상품 사진 가이드를 <b>확인</b>해주세요.</p>
            <p><b>이미지 파일(JPG,PNG,GIF)</b>만 업로드 할 수 있습니다.</p>
            <p>권장 사진 사이즈 : 600x600 </p>
            <p>이미지 등록시 자동으로 확대되거나 리사이징 될 수 있습니다.</p>
            <p><b>장당 15MB, 최대 64MB</b>까지 업로드 가능합니다.</p>
            <p>최대 20장까지 등록할 수 있습니다.</p>
            <p>상품과 무관한 사진 또는 분쟁이 발생한 사진은 관리자에 의해 노출이 제한될 수 있습니다.</p>
            <p><b>상품 사진은 승인 시 수정하지 못하며, 미승인 상태에서 수정 시 새로운 사진을 다시 업로드 해야 합니다.</b></p>
        </div>

        <div class="custom-file mb-1">
            <input type="file" id="imageSearch" name="imageSource[]" class="custom-file-input" accept="media_type, .jpg, .jpeg, .gif, .png" multiple="multiple" required>
            <label class="custom-file-label" for="imageSearch">상품 사진 선택 하기<span id="fileCountDraw">0</span>/20</label>
        </div>
        <div class="input-group mb-1">
            <span class="btn btn-sm btn-info" data-toggle="modal" data-target="#imageCropArea">
                상품 사진 수정/확인
            </span>
        </div>

        <ul id="imageList">
        </ul>

        <div class="alert alert-info imageFilesAlert" role="alert">
            <i class="fa fa-mouse-pointer dragInfoAnimation"></i>
            <p>상품 사진 노출 순서를 변경 하려면 위 상품 사진 목록에 사진을 누른 뒤 원하는 순서로 옮겨주세요.</p>
            <p><b>상품 사진을 여러개를 올리고 싶으신가요?</b> 사진 목록에서 원하는 사진 중 1개를 3초간 꾹 눌러주세요. 그 후 여러 사진 선택이 가능합니다.</p>
        </div>

    </div>

    <div class="form-group btnConfirm">
        <a href="./board.php?bo_table=<?php echo $bo_table ?>" class="btn btn-dark">취소</a>
        <input type="submit" value="등록 완료" id="btn_submit" accesskey="s" class="btn btn-success">
    </div>
    </form>
</section>

<div class="modal fade" id="imageCropArea" tabindex="-1" aria-labelledby="imageCropAreaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageCropAreaLabel">상품 사진 수정/확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pl-5 pr-5">
                <div id="imageCropAreaSlide" class="swiper-container">
                    <div class="swiper-wrapper">
                    </div>
                    <div class="swiper-pagination"></div>

                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="alert alert-info">
                    붉은색 박스 안으로 자르고 싶은 부분을 드래그하거나 확대한 뒤 '수정반영' 버튼을 눌러주셔야 사진 편집 내용이 반영 됩니다.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- } 게시물 작성 끝 -->