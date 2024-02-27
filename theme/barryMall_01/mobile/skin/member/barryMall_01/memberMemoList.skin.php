<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 1);
//add_javascript('<script src="'.$member_skin_url.'/....js"></script>', 1);
?>
<div id="memoList">
    <?php if ($chatAllInfo): ?>
        <?php foreach ($chatAllInfo as $key => $value): ?>
            <ul>
                <li class="thumb">
                    <div class="img_inner"><img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/no_profile.png" alt='profile_image' /></div>
                </li>
                <li class="desc">
                    <a href="/bbs/memberMemoDetail.php?mr_id=<?php echo $value['chatInfo']['mr_id']?>"><?php echo $value['chatInfo']['mb_name']?>(<?php echo $value['talkerMbid']; ?>)
                        <?php if($value['countCheck']): ?>
                            <span class="count"><?php echo $value['msgCount']?></span>
                        <?php endif; ?>
                        <div class="content">
                            <?php echo $value['lastMsg']?>
                        </div>
                    </a>
                </li>
                <li class="dt">
                    <?php echo $value['msgDateTimeYear']; ?>:<?php echo $value['msgDateTime']; ?>:<?php echo $value['msgDateTimeAmPm']; ?> <?php echo $value['msgDateTimeHour']; ?>:<?php echo $value['msgDateTimeMin']; ?>
                </li>
            </ul>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/chat_empty.png" />
            <div class="desc">
                채팅 내역이 아직 없어요!
            </div>
        </div>
    <?php endif;  ?>
</div>
