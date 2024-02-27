<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
include_once(G5_LIB_PATH.'/barry.lib.php');

if($member['mb_id'] == '01050958112' || $member['mb_id'] == '01096415095'){
    var_dump(adminGetStockInfo($bo_table));
    if(G5_DEBUG === true && G5_DEBUG_VAR === true){
        var_dump($view);
    }
}

//CTC WALLET 결제 관련 CLASS
include_once(G5_PLUGIN_PATH.'/barryCtcWallet/CtcWallet.php');
use barry\wallet\CtcWallet as ctcWallet;

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
add_javascript('<script src="'.$board_skin_url.'/view.js"></script>', 0);
//다음 주소 script
echo (G5_POSTCODE_JS);

/////// 레거시라서 view skin 상단에 소스 코드를 추가 합니다. 원칙적으로는 안됌.

//한마음 가상 아이디 판매상품의 전화번호는 '0260911125'번으로 노출한다.
if (in_array($view['mb_id'],BARRY_CORP_ARRAY) || $view['mb_id'] == '0260911133'){
    $phoneInfo = '0260911125';
}
else{
    $phoneInfo = $view['mb_id'];
}

//price type build
$itemPriceInfo = array();
if ($view['wr_price_type'] == "KRW" || $view['wr_price_type'] == "CREDITCARD") {
    $itemPriceInfo[] = array('price' => number_format($view['wr_10']), 'unit' => '원');
}
else if ($view['wr_price_type'] == "TP3MC") {
    if ($view['wr_1'] != 0) {
        $itemPriceInfo[] = array('price' => number_format($view['wr_1']), 'unit' => 'e-TP3');
    }
    if ($view['wr_2'] != 0) {
        $itemPriceInfo[] = array('price' => number_format($view['wr_2']), 'unit' => 'e-MC');
    }
}
else if ($view['wr_price_type'] == "TP3") {
    $itemPriceInfo[] = array('price' => number_format($view['wr_1']), 'unit' => 'e-TP3');
}
else if ($view['wr_price_type'] == "MC") {
    $itemPriceInfo[] = array('price' => number_format($view['wr_2']), 'unit' => 'e-MC');
}
else if ($view['wr_price_type'] == "EKRW") {
    $itemPriceInfo[] = array('price' => number_format($view['wr_3']), 'unit' => 'e-KRW');
}
else if ($view['wr_price_type'] == "ECTC") {
    $itemPriceInfo[] = array('price' => number_format($view['wr_4']), 'unit' => 'e-CTC');
}
$sellerVirtualWalletAdress = get_member($view['mb_id'],'mb_1');
$viewInfoArray = array(
    'itemId' => $view['wr_id'],
    'sellerName' => $view['wr_name'],
    'sellerId' => $view['mb_id'],
    'sellerVirtualWalletAdress' => $sellerVirtualWalletAdress['mb_1'],
    'priceType' => $view['wr_price_type'],
    'deleteStatus' => $view['del_yn'],
    'soldout' => $view['it_soldout'],
    'publish' => $view['it_publish'],
    'optionSubject' => (empty($view['it_option_subject'])?false:$view['it_option_subject']),
    'memberId' => $member['mb_id'],
    'memberAuth' => $member['mb_level'],
    'cartPriceType' => false,
    'cartOptionPrice' => false,
    'cartOption' => false,
    'optId' => false,
    'optType' => false,
    'address' => false,
    'previewItemTotalPrice' => 0,
    'cardPaymentAuth' => false //카드결제 시 생년월일 사업자등록번호 선택여부
);

unset($sellerVirtualWalletAdress);
$viewJavascriptGlobalVariable = json_encode($viewInfoArray,JSON_UNESCAPED_UNICODE);

?>
<script>
    //View Javascript 전역 변수
    barryView = JSON.parse('<?php echo $viewJavascriptGlobalVariable; unset($viewJavascriptGlobalVariable);?>');
</script>

<!-- 게시물 읽기 시작 { -->
<article id="bo_v" class="container-fluid">
    <section class="imageArea">
        <div id="viewDetailSlide" class="swiper-container">
            <div class="swiper-wrapper">
                <?php if($view['file']['count']): ?>
                    <?php for ($i=1; $i<=$view['file']['count']; $i++): ?>
                        <?php if ($view['file'][$i]['image_width'] > '800' || $view['file'][$i]['image_height'] > '800'): ?>
                            <?php $detailImageFileName = thumbnail($view['file'][$i]['file'],G5_DATA_PATH.'/file/'.$bo_table.'/',G5_DATA_PATH.'/file/'.$bo_table.'/detail/',800,800,false,false,'center',false,'90/0.5/3'); ?>
                            <div class="swiper-slide"><img src="<?php echo $view['file'][$i]['path'].'/detail/'.$detailImageFileName ?>"></div>
                        <?php else: ?>
                            <div class="swiper-slide"><img src="<?php echo $view['file'][$i]['path'].'/'.$view['file'][$i]['file']; ?>" class="small" ></div>
                        <?php endif ?>
                    <?php endfor; ?>
                <?php else: ?>
                    <div class="swiper-slide noImage">이미지가 없습니다</div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination"></div>

            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </section>


    <?php if ($update_href || $delete_href ): ?>
        <section class="viewControlArea form-group">
            <ul class="viewControl">
                <?php if ($update_href && $update_href) : ?>
                    <li><a href="<?php echo G5_BBS_URL; ?>/member_goodsdetail.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $view['wr_id']?>" class="btn btn-secondary"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>상품 수정</a></li>
                <?php endif; ?>
            </ul>
        </section>
    <?php endif;  ?>


    <div class="viewContents form-group">
        <h3 class="itemTitle">
            <?php
                echo cut_str(get_text($view['wr_subject']), 70);
                echo ($view['it_soldout']==1)?'<span class="badge badge-danger soldout">품절</span>':'';
            ?>
        </h3>
        <?php //임시 처리로 오프라인 카테고리(테이블) 과, 쇼핑몰 최저가가 있을 때만 노출 한다. ?>
        <?php if($view['it_minimum_retail_price'] > 0 ): ?>
            <?php if ($bo_table == 'offline' && $view['wr_price_type'] == "KRW" || $view['wr_price_type'] == "CREDITCARD"): ?>
                <div class="minimumRetailPriceInfo">
                    <ul>
                        <li>
                            <h4>쇼핑몰 최저가</h4>
                            <span class="minimum">
                                <?php
                                    // 상품금액 - 최저가
                                    $tempPrice = $view['it_minimum_retail_price'] - $view['wr_10'];
                                    //할인율
                                    $bulidMinRetailPricePercent = ($tempPrice/$view['it_minimum_retail_price'])*100;
                                ?>
                                <b><strike><?php echo number_format($view['it_minimum_retail_price']); ?></strike></b>
                                <span class="unit">원</span>
                            </span>
                        </li>
                        <li>
                            <h4>베리몰 특별할인 금액</h4>
                            <span>
                                <b><?php echo number_format($view['wr_10']); ?></b>
                                <span class="unit">원</span>
                            </span>
                        </li>
                    </ul>
                </div>
            <?php else: //일반 회원들에 대한 경우가 없어서... 임시 처리.?>
                <div class="minimumRetailPriceInfo">
                    <ul>
                        <li>
                            <h4>쇼핑몰 최저가</h4>
                            <span class="minimum">
                                <?php
                                // 상품금액 - 최저가
                                $tempPrice = $view['it_minimum_retail_price'] - $view['wr_10'];
                                //할인율
                                $bulidMinRetailPricePercent = ($tempPrice/$view['it_minimum_retail_price'])*100;
                                ?>
                                <b><strike><?php echo number_format($view['it_minimum_retail_price']); ?></strike></b>
                                <span class="unit">원</span>
                            </span>
                        </li>
                        <li>
                            <h4>베리몰 특별할인 금액</h4>
                            <?php foreach ($itemPriceInfo as $key => $value): ?>
                                <span>
                                   <b><?php echo $value['price'] ?></b>
                                    <span class="unit"><?php echo $value['unit'] ?></span>
                                </span>
                            <?php endforeach; ?>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php else: ?>
        <div class="priceInfo">
            <span>판매금액</span>

            <ul class="amountList">
                <?php foreach ($itemPriceInfo as $key => $value): ?>
                <li>
                    <b><?php echo $value['price'] ?></b>
                    <span class="unit"><?php echo $value['unit'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if($view['wr_price_type'] == "CREDITCARD"): ?>
            <div class="priceInfo">
                <span class="badge badge-dark">카드/현금 결제 가능 상품</span>
            </div>
        <?php elseif($view['wr_price_type'] == "KRW"): ?>
            <div class="priceInfo">
                <span class="badge badge-dark">현금 결제 가능 상품</span>
            </div>
        <?php endif; ?>

        <?php if($view['it_limit'] == 1): ?>
            <section class="limitArea">
                <div class="card">
                    <div class="card-header">
                        한정판매
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">1 인당 제한 주문 수량 : <?php echo $view['it_limit_qty']; ?> 개</h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            시작 일자: <?php echo (string) date('Y-m-d H:i', strtotime($view['it_limit_activativation_datetime'])); ?>
                        </h6>
                        <h6 class="card-subtitle mb-2 text-muted">
                            종료 일자: <?php echo (string) date('Y-m-d H:i', strtotime($view['it_limit_deactivativation_datetime'])); ?>
                        </h6>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="viewContents form-group">
        <div class="title">
            <h3>상품 정보</h3>
        </div>
        <!-- 본문 내용 시작 { -->
        <div class="itemDescription">
            <?php echo get_view_thumbnail($view['content']); ?>
        </div>
        <div class="itemDescription">
            <ul class="itemDetailImage">
            <?php if($view['file']['itemDetailcount']): ?>
                <?php for ($i=($view['file']['count']+1); $i<=($view['file']['count']+$view['file']['itemDetailcount']); $i++): ?>
                    <li><img src="<?php echo $view['file'][$i]['path'].'/'.$view['file'][$i]['file']?>"></li>
                <?php endfor; ?>
            <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="viewContents form-group">
        <section class="viewOtherInfo">
            <?php if ($view['wr_price_type']!='KRW'): ?>
                <div class="cashBack">
                    <span class="badge badge-pill badge-dark">※ 거래가 완료되면 20% 캐시백을 드립니다. ※</span>
                </div>
            <?php endif; ?>
            <div class="card sellerInfo">
                <div class="card-header">
                    판매자 정보
                </div>
                <img src="<?php G5_URL; ?>/img/no_profile.gif" class="profileImage" alt="판매자 프로필 이미지">
                <div class="card-body">
                    <h5><?=$view['wr_name']?> / <a href="tel:<?php echo $phoneInfo; ?>"><?=$phoneInfo?></a></h5>
                </div>
            </div>

            <div class="alert alert-dark mt-3" role="alert">
                ※ 판매자에게 코인 결제를 실패 하셨다면 ? : 설정 > 주문내역 > 내가 주문한 내역 > 원하는 주문한 내역 클릭 후 주문 완료 내역을 확인해 주세요. ※
                <a href="<?php echo G5_BBS_URL ?>/memberOrderList.php"><span class="badge badge-info">바로가기</span></a>
            </div>

            <a href="<?php echo get_pretty_url($bo_table,'','&page='.$page); ?>">
                <span class="btn btn-secondary btn-block">이전 목록</span>
            </a>
        </section>
    </div>
    <!-- iOS command Area 추가 -->
    <article id="commandArea" class="commandArea">
        <ul class="command">
            <?php if($member['mb_level'] > 1 ):?>
                <li>
                    <a href="<?php echo G5_BBS_URL;?>/memberMemoDetail.php?targetTable=<?php echo $bo_table; ?>&wr_id=<?php echo $view['wr_id']; ?>" class="btn btn-dark chat" target="_self">
                        <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/icon_chat.png" />판매자와 채팅
                    </a>
                </li>
                <li>
                    <span class="btn btn-dark order" data-toggle="modal" data-target="#orderLayer">주문하기</span>
                </li>
            <?php else: ?>
                <li>
                    <a href="https://cybertronchain.com/wallet2/" class="btn btn-primary login">CTC 지갑에서 로그인</a>
                </li>
            <?php endif; ?>
        </ul>
    </article>

    <!-- modal 처리에.... form 추가 -->
    <!-- input box 정리 -->
    <article id="orderLayer" class="modal orderLayer" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">상품주문</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="닫기">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="orderUpload" name="orderUpload" action="#" method="post" autocomplete="off">
                        <input type="hidden" name="tableId" id="tableId" value="<?php echo $bo_table ?>">
                        <?php if ($view['wr_price_type'] == 'TP3MC'): ?>
                            <ul class="list-group coinType">
                                <?php if($view['wr_1'] != 0): ?>
                                    <li class="list-group-item" data-me-type-value="e-TP3" data-me-value="<?php echo $view['wr_1']; ?>">e-TP3 코인으로 결제<b>(e-TP3)</b></li>
                                <?php endif; ?>
                                <?php if($view['wr_2'] != 0): ?>
                                    <li class="list-group-item" data-me-type-value="e-MC" data-me-value="<?php echo $view['wr_2']; ?>">e-MC 코인으로 결제<b>(e-MC)</b></li>
                                <?php endif; ?>
                            </ul>
                        <?php elseif($view['wr_price_type'] == 'EKRW'): ?>
                            <ul class="list-group coinType">
                                <?php if($view['wr_3'] != 0): ?>
                                    <li class="list-group-item" data-me-type-value="e-KRW" data-me-value="<?php echo $view['wr_3']; ?>">e-KRW 코인으로 결제<b>(e-KRW)</b></li>
                                <?php endif; ?>
                            </ul>
                        <?php elseif($view['wr_price_type'] == 'ECTC'): ?>
                            <ul class="list-group coinType">
                                <?php if($view['wr_4'] != 0): ?>
                                    <li class="list-group-item" data-me-type-value="e-CTC" data-me-value="<?php echo $view['wr_4']; ?>">e-CTC 코인으로 결제<b>(e-CTC)</b></li>
                                <?php endif; ?>
                            </ul>
                        <?php elseif($view['wr_price_type'] == 'CREDITCARD'): ?>
                            <ul class="list-group coinType">
                                <li class="list-group-item" data-me-type-value="CREDITCARD" data-me-value="<?php echo $view['wr_10']; ?>">카드 결제<b>(CARD)</b></li>
                                <li class="list-group-item" data-me-type-value="KRW" data-me-value="<?php echo $view['wr_10']; ?>">현금 결제<b>(원)</b></li>
                            </ul>
                        <?php else: ?>
                            <ul class="list-group coinType">
                                <li class="list-group-item" data-me-type-value="KRW" data-me-value="<?php echo $view['wr_10']; ?>">현금 결제<b>(원)</b></li>
                            </ul>
                        <?php endif; ?>

                        <?php if($view['it_option_subject']): ?>
                            <section class="form-group">
                                <div class="title">
                                    <h3>선택 옵션</h3>
                                </div>
                                <?php // 선택옵션
                                echo str_replace(array('class="get_item_options"', 'id="it_option_', 'class="it_option"'), array('class="get_item_options"', 'id="it_option_', 'class="it_option custom-select"'), $option_item);
                                ?>
                            </section>
                        <?php endif ?>
                        <div class="form-group">
                            <div class="title">
                                <h3>주문 수량</h3>
                            </div>
                            <div class="input-group mb-2 mt-2">
                                <input type="number" name="qty" min="0" max="9999" step="1" pattern="\d*"  class="form-control" placeholder="주문수량" required="required"/>
                                <div class="input-group-prepend">
                                    <div class="input-group-text">개</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="title">
                                <h3>주문 금액</h3>
                                <span id="previewItemTotalPrice">0</span>
                            </div>
                            <ul class="contents">
                                <li>
                                    <h4>상품금액</h4>
                                    <span id="previewItemPrice">0</span>
                                </li>
                                <li>
                                    <h4 class="wide">선택 옵션 추가금액</h4>
                                    <span id="previewItemSelectOptionPrice">0</span>
                                </li>
                                <li>
                                    <h4>수량</h4>
                                    <span id="previewItemQty">0</span>
                                </li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <div class="title">
                                <h3>주문자 정보</h3>
                            </div>
                            <label for="order_name">성함</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="성함 (주문자)" value="<?php echo $member['mb_name']?>" required="required" readonly/>
                            <label for="order_phone">연락처</label>
                            <input type="text" name="phone" id="phone" class="form-control" pattern="\d*" title="숫자만 입력 가능 합니다." placeholder="연락처 (주문자 연락처)" value="<?php echo $member['mb_id']?>" required="required" readonly/>
                        </div>

                        <div class="form-group">
                            <div class="title">
                                <h3>수령인 정보</h3>
                            </div>
                            <label for="samename" class="checkBoxContainer">
                                주문자와 동일 합니다.
                                <input type="checkbox" name="samename" id="samename" value="Y" />
                                <span class="checkmark"></span>
                            </label>
                            <label for="recvName">성함</label>
                            <input type="text" name="recvName" id="recvName" class="form-control" placeholder="성함 (수령인)" />
                            <label for="recvPhone">연락처</label>
                            <input type="text" name="recvPhone" id="recvPhone" class="form-control" pattern="\d*" title="숫자만 입력 가능 합니다." placeholder="연락처 (수령인 연락처)" />
                        </div>
                        <!-- 주문자 주소 -->
                        <div class="form-group">
                            <div class="title">
                                <h3>주문자 주소</h3>
                            </div>
                            <div>
                                <div class="addressSearch btn btn-dark" onClick="win_zip('orderUpload', 'memberZip', 'memberAddr1', 'memberAddr2', 'memberAddr3', 'memberJibun');">
                                    주소검색
                                </div>
                                <label for="memberAddressSave" class="checkBoxContainer">
                                    주소를 저장 합니다.
                                    <input type="checkbox" name="memberAddressSave" id="memberAddressSave">
                                    <span class="checkmark"></span>
                                </label>
                            </div>

                            <input type="text" name="memberZip" class="form-control" placeholder="우편번호" value="<?php echo ($member['mb_member_addr_jibeon'] != '' ? $member['mb_member_zip1'].$member['mb_member_zip2']:'')?>"readonly required>
                            <input type="text" name="memberAddr1" class="form-control" placeholder="배송지 주소" value="<?php echo ($member['mb_member_addr_jibeon'] != '' ? $member['mb_member_addr1']:'')?>"readonly required>
                            <input type="text" name="memberAddr2" class="form-control" placeholder="상세 주소1" value="<?php echo ($member['mb_member_addr_jibeon'] != '' ? $member['mb_member_addr2']:'')?>">
                            <input type="text" name="memberAddr3" class="form-control" placeholder="상세 주소2" value="<?php echo ($member['mb_member_addr_jibeon'] != '' ? $member['mb_member_addr3']:'')?>">
                            <input type="hidden" name="memberJibun" value="<?php echo ($member['mb_member_addr_jibeon'] != '' ? $member['mb_member_addr_jibeon']:'')?>">
                        </div>

                        <div class="form-group">
                            <div class="title">
                                <h3>수령자 주소</h3>
                            </div>
                            <div>
                                <div class="addressSearch btn btn-dark" onClick="win_zip('orderUpload', 'zip', 'addr1', 'addr2', 'addr3', 'jibun');">
                                    주소검색
                                </div>
                                <label for="addressSave" class="checkBoxContainer">
                                    주소를 저장 합니다.
                                    <input type="checkbox" name="addressSave" id="addressSave">
                                    <span class="checkmark"></span>
                                </label>
                            </div>

                            <input type="text" name="zip" class="form-control" placeholder="우편번호" value="<?php echo ($member['mb_addr_jibeon'] != '' ? $member['mb_zip1'].$member['mb_zip2']:'')?>"readonly required>
                            <input type="text" name="addr1" class="form-control" placeholder="배송지 주소" value="<?php echo ($member['mb_addr_jibeon'] != '' ? $member['mb_addr1']:'')?>"readonly required>
                            <input type="text" name="addr2" class="form-control" placeholder="상세 주소1" value="<?php echo ($member['mb_addr_jibeon'] != '' ? $member['mb_addr2']:'')?>">
                            <input type="text" name="addr3" class="form-control" placeholder="상세 주소2" value="<?php echo ($member['mb_addr_jibeon'] != '' ? $member['mb_addr3']:'')?>">
                            <input type="hidden" name="jibun" value="<?php echo ($member['mb_addr_jibeon'] != '' ? $member['mb_addr_jibeon']:'')?>">

                        </div>
                        <div class="form-group">
                            <div class="title">
                                <h3>주문 메모</h3>
                            </div>
                            <textarea name="odMemo" class="orderMemo form-control" ></textarea>
                        </div>
                        <div class="form-group btnConfirm">
                            <span class="btn btn-dark" data-dismiss="modal" aria-label="취소">취소</span>
                            <input type="submit" id="btnSubmit" class="btn btn-success" value="상품주문">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </article>

    <?php if($view['wr_price_type'] != 'KRW'): ?>
        <article id="paymentLayer" class="modal paymentLayer" tabindex="1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form id="paymentform" name="paymentform" method="post">
                            <?php
                            $ctcWallet = ctcWallet::singletonMethod();
                            $ctcWallet-> init('basic');
                            //plainPassword, sellerAdress, orderer PhoneNumber만 넘기면 API에서 조회,
                            $ctcWallet-> getTransferPasswordCheckFormBuild();
                            ?>
                        </form>
                    </div>
                </div>
            </div>
        </article>
    <?php endif; ?>
    <article id="paymentCardLayer" class="modal paymentCardLayer" tabindex="1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">카드결제</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <form id="paymentCardform" name="paymentCardform" method="post">
                        <section class="alert alert-warning mt-3">
                            5만원 이하는 할부 결제가 불가능 합니다.
                        </section>
                        <div class="cardInfo bg-secondary">
                            <div>
                                <label for="cardNumber"  class="cardDataTitle text-black">카드번호</label>
                                <input type="text" name="cardNumber" class="form-control" placeholder="카드 번호" maxlength="20" pattern="[0-9]*" title="문자나 '-'을 제외하고 숫자 형식으로 입력해주세요." maxlength="20" required="required">
                                <small tabindex="-1" class="text-white">최대 20자리 카드 번호를 입력해주세요.</small>
                            </div>
                            <div>
                                <div class="row">
                                    <div class="col">
                                        <span>
                                            <div>
                                                <label for="expireMonth" class="cardDataTitle text-black">유효기간(월)</label>
                                                    <div>
                                                        <select name="expireMonth"  required="required" class="form-group selectpicker" >
                                                            <option selected></option>
                                                            <?php for($i=1; $i <= 12; $i++): ?>
                                                                <?php if($i < 10): ?>
                                                                    <option value="<?php echo '0'.$i; ?>"><?php echo '0'.$i; ?></option>
                                                                <?php else: ?>
                                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                            </div>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <span>
                                            <div>
                                                <label for="expireYear" class="cardDataTitle text-black">유효기간(년)</label>
                                                    <div>
                                                        <?php
                                                            $str = substr(G5_TIME_YMD,2,2);
                                                            $targetLength = $str + 15;
                                                        ?>
                                                        <select name="expireYear"  required="required" class="form-group selectpicker">
                                                                <option selected></option>
                                                                <?php for($i=$str; $i <= $targetLength; $i++): ?>
                                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                        </select>
                                                    </div>
                                            </div>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <section class="alert bg-secondary mt-3">
                            <div class="paymentAuthArea">
                                <p class="cardDataTitle">입력 하실 정보를 선택 해주세요.</p>
                                <span class="btn btn-info" data-type="brithday" id="btnBirthday">생년월일</span>
                                <span class="btn btn-info" data-type="companyNumber">사업자등록번호</span>
                            </div>
                            <label class="cardDataTitle" id="cardDataTitle">
                                <!-- 생년월일 -->
                            </label>

                            <section class="birthDayArea row">
                                <select name="birthDayYear" class="col form-group">
                                    <option selected>년</option>
                                    <?php for($i=1921; $i <= 2121; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="birthDayMonth" class="col ml-1 mr-1 form-group">
                                    <option selected>월</option>
                                    <?php for($i=1; $i <= 12; $i++): ?>
                                        <?php if($i < 10): ?>
                                            <option value="<?php echo '0'.$i; ?>"><?php echo '0'.$i; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </select>
                                <select name="birthDayDay" class="col form-group">
                                    <option selected>일</option>
                                    <?php for($i=1; $i <= 31; $i++): ?>
                                        <?php if($i < 10): ?>
                                            <option value="<?php echo '0'.$i; ?>"><?php echo '0'.$i; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </select>
                            </section>

                            <section class="companyNumberArea">
                                <input type="text" name="companyNumber" class="form-control" placeholder="1112233334" pattern="[0-9]*" maxlength="10">
                                <small class="text-white">법인카드인 경우 사업자등록번호를 입력해야 합니다.</small>
                            </section>
                        </section>

                        <label class="cardDataTitle">
                            성명
                        </label>
                        <input type="text" name="userName" class="form-control" required="required" pattern="[가-힣]+" maxlength="50">
                        <label class="cardDataTitle">
                            연락처
                        </label>
                        <input type="text" name="userMobileNumber" class="form-control" pattern="[0-9]*" required="required" maxlength="11">
                        <label class="cardDataTitle">
                            비밀번호(앞2자리)
                        </label>
                        <input type="password" name="cardPw" class="form-control" pattern="[0-9]*" required="required" maxlength="2">
                        <label class="cardDataTitle">
                            할부기간
                        </label>
                            <div>
                                <select name="quota"  required="required" class="form-group selectpicker">
                                    <option selected value="00">일시불</option>
                                    <option value="02">2</option>
                                    <option value="03">3</option>
                                    <option value="04">4</option>
                                    <option value="05">5</option>
                                    <option value="06">6</option>
                                    <option value="07">7</option>
                                    <option value="08">8</option>
                                    <option value="09">9</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                        <input type="hidden" name="amount" class="form-control" value="0" >
                        <input type="hidden" name="orderId" class="form-control">
                        <button type="submit" id="btnSubmit" class="btn btn-success btn-block">결제하기</button>
                        <button type="reset" class="btn btn-danger btn-block">다시입력</button>
                    </form>
                </div>
            </div>
        </div>
    </article>
</article>
<!-- } 게시판 읽기 끝 -->
