<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
add_javascript('<script src="'.$member_skin_url.'/memberMemoDetail.js"></script>', 1);
include_once ($member_skin_path.'/common/memberMemoDetailHead.php');

?>
<?php if($chatAllInfo['roomInfo']['mr_id']): ?>
    <span id="load" class="btn btn-secondary btn-block" onclick="more('<?=$chatAllInfo['roomInfo']['mr_id'];?>');">더 보기</span>
<?php endif; ?>

<?php if($chatAllInfo['itemInfo']): ?>
<div id="good_info">
    <ul>
        <li class="thumb">
            <div><img class="card-img" src="<?php echo $chatAllInfo['thumb']['src']; ?>" alt="상품 이미지"></div>
        </li>
        <li class="desc">
            <?php echo $chatAllInfo['itemInfo']['wr_subject']; ?>
            <div class="price_line"><span class="unit">판매금액</span>
                <span class="amount">
                    <?php foreach ($chatAllInfo['itemInfo']['itemPrice'] as $itemPriceKey => $itemPriceValue): ?>
                        <?php echo number_format((float)$itemPriceValue['price']); ?>
                    <?php echo $itemPriceValue['paymentType'];?>
                    <?php endforeach; ?>
                </span>

        </li>
    </ul>
</div>
<?php endif; ?>

<div class="more_section"></div>

<!-- 채팅 목록 START -->
<div id="memo_list" class="new_win">
    <div class="new_win_con2">
        <div id="memo_log" class="memo_list">
            <?php if($chatAllInfo['chatInfo']): ?>
                <?php foreach ($chatAllInfo['chatInfo'] as $key => $value): ?>
                    <?php if($value['dateTimeTitleBuild'] !== false): ?>
                        <div class='datetime'><?php echo $value['dateTimeTitleBuild']; ?></div>
                    <?php endif; ?>

                    <?php if($value['msgMeCheck'] === true): ?>
                        <div class='me'>
                            <ul>
                                <li class='sym'>&nbsp;</li>
                                <li class='desc'><?php echo $value['msgBuild']; ?></li>
                                <li class='dt'><?php echo $value['msgDateTimeAmPm']; ?><br />
                                <?php echo $value['msgDateTimeHour']; ?>:<?php echo $value['msgDateTimeMin']; ?></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class='target'>
                            <ul>
                                <li class='img'>
                                    <img src='<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/no_profile.gif' alt='profile_image' />
                                    <span><?php echo $chatAllInfo['roomInfo']['mb_name']; ?></span>
                                </li>
                                <li class='sym'>&nbsp;</li>
                                <li class='desc'><?php echo $value['msgBuild']; ?></li>
                                <li class='dt'>
                                    <?php echo $value['msgDateTimeAmPm']; ?>
                                    <br />
                                    <?php echo $value['msgDateTimeHour']; ?>:<?php echo $value['msgDateTimeMin']; ?>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- 채팅 목록 END  -->

<form name="fmemoform" id="fmemoform" onsubmit="return false" autocomplete="off">
    <div id="input_section">
        <ul>
            <li class="input">
                <input type="text" name="me_memo" id="me_memo" value="" placeholder="메세지를 입력하세요." />
            </li>
            <li class="img">
                <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/send_message.png" onclick="sendMemo()" />
            </li>
        </ul>
    </div>
</form>
