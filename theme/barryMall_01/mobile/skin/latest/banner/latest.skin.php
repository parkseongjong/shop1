<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

add_javascript('<script src="'.$latest_skin_url.'/main.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
$list_count = (is_array($list) && $list) ? count($list) : 0;
?>
<?php if ($list_count != 0) : ?>
    <article class="bannerLatest">
        <!-- Slider main container -->
        <div id="bannerLatestSlide"class="swiper-container">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->
                <?php foreach ($list as $value): ?>
                    <?php if($value['bb_link'] == 'none'): ?>
                        <div class="swiper-slide">
                            <img src="<?php echo $value['bb_url'] ?>" alt="<?php echo $value['bb_target'] ?>">
                        </div>
                    <?php else: ?>
                        <div class="swiper-slide">
                            <a href="<?php echo $value['bb_link']; ?>">
                                <img src="<?php echo $value['bb_url'] ?>" alt="<?php echo $value['bb_target'] ?>">
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </article>
<?php endif; ?>
