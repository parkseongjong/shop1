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

<div class="more_section"></div>

<!-- 채팅 목록 START -->
<div id="memo_list" class="new_win">
    <div id="memo_log" class="memo_list">
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
    </div>
</div>
<!-- 채팅 목록 END  -->


<!--

    seller chat 다 JS 전역 변수로.. 전환 하기, 무조건 발신 수신 값만 전달하고 UPDATE에서 room 조회 후 mr_id 파악해서 넣어주자.
    추후 web websocket 걸릴 때는, 최초 연결 시 mr_id 발급 받아서 그 값으로 대화 하면 될듯.?n
-->

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
