<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
    
/*
*
*
*   view.mobile.skin.php
*/
    add_stylesheet('<link rel="stylesheet" href="'.$coupon_skin_url.'/css/common.mobile.css">', 0);
    add_javascript('<script src="'.$coupon_skin_url.'/error.mobile.js"></script>', 1);// error.js
?>
<script>
    //Integration error Javascript 전역 변수
    barry_error_msg = '<?php echo $errMsg ?>';
</script>

<article id="integrationMobileApp" class="contentsWrap">
    <div class="contents container-fluid">
        <div class="alert alert-danger" role="alert">
            <?php echo $errMsg ?>
        </div>
        <div class="errorContents">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ; ?>/bbs_empty.png" />
        </div>
    </div>
</article>