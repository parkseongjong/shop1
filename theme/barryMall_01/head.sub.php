<?php
// 이 파일은 새로운 파일 생성시 반드시 포함되어야 함
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5_debug['php']['begin_time'] = $begin_time = get_microtime();

if (!isset($g5['title'])) {
    $g5['title'] = $config['cf_title'];
    $g5_head_title = $g5['title'];
}
else {
    $g5_head_title = $g5['title']; // 상태바에 표시될 제목
    $g5_head_title .= " | ".$config['cf_title'];
}

$g5['title'] = strip_tags($g5['title']);
$g5_head_title = strip_tags($g5_head_title);

// 현재 접속자
// 게시판 제목에 ' 포함되면 오류 발생
$g5['lo_location'] = addslashes($g5['title']);
if (!$g5['lo_location'])
    $g5['lo_location'] = addslashes(clean_xss_tags($_SERVER['REQUEST_URI']));
$g5['lo_url'] = addslashes(clean_xss_tags($_SERVER['REQUEST_URI']));
if (strstr($g5['lo_url'], '/'.G5_ADMIN_DIR.'/') || $is_admin == 'super') $g5['lo_url'] = '';

/*
// 만료된 페이지로 사용하시는 경우
header("Cache-Control: no-cache"); // HTTP/1.1
header("Expires: 0"); // rfc2616 - Section 14.21
header("Pragma: no-cache"); // HTTP/1.0
*/
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta property="og:type" content="website"> 
<meta property="og:title" content="베리베리쇼핑몰"> 
<meta property="og:url" content="https://barrybarries.kr/">
<meta property="og:description" content="암호화폐 쇼핑몰"> 
<meta property="og:image:width" content="200" />
<meta property="og:image:height" content="100" />
<meta property="og:image" content="<?php echo G5_URL ?>/img/logo1.png">

<?php
if (G5_IS_MOBILE) {
    echo '<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=0,maximum-scale=10,user-scalable=yes">'.PHP_EOL;
    echo '<meta name="HandheldFriendly" content="true">'.PHP_EOL;
    echo '<meta name="format-detection" content="telephone=no">'.PHP_EOL;
    echo '<meta http-equiv="X-UA-Compatible" content="IE=10,chrome=1">'.PHP_EOL;
} else {
    echo '<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=0,maximum-scale=10,user-scalable=yes">'.PHP_EOL;
    echo '<meta http-equiv="imagetoolbar" content="no">'.PHP_EOL;
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
}

if($config['cf_add_meta'])
    echo $config['cf_add_meta'].PHP_EOL;
?>
<title><?php echo $g5_head_title; ?></title>
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo run_replace('head_css_url', G5_THEME_CSS_URL.'/'.(G5_IS_MOBILE ? 'mobile' : 'default').'.css?ver='.G5_CSS_VER, G5_THEME_URL); ?>">
<!--[if lte IE 8]>
<script src="<?php echo G5_JS_URL ?>/html5.js"></script>
<![endif]-->
<script>
// 자바스크립트에서 사용하는 전역변수 선언
var g5_url       = "<?php echo G5_URL ?>";
var g5_bbs_url   = "<?php echo G5_BBS_URL ?>";
var g5_is_member = "<?php echo isset($is_member)?$is_member:''; ?>";
var g5_is_admin  = "<?php echo isset($is_admin)?$is_admin:''; ?>";
var g5_is_mobile = "<?php echo G5_IS_MOBILE ?>";
var g5_bo_table  = "<?php echo isset($bo_table)?$bo_table:''; ?>";
var g5_sca       = "<?php echo isset($sca)?$sca:''; ?>";
var g5_editor    = "<?php echo ($config['cf_editor'] && $board['bo_use_dhtml_editor'])?$config['cf_editor']:''; ?>";
var g5_cookie_domain = "<?php echo G5_COOKIE_DOMAIN ?>";
var g5_board_skin_url = "<?php echo $board_skin_url ?>";
</script>
<?php
//COMMON JS, CSS
add_javascript('<script src="'.G5_JS_URL.'/jquery-1.12.4.min.js"></script>', 0);
@add_javascript('<script src="'.G5_JS_URL.'/jquery-migrate-1.4.1.min.js"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery-ui-1.12.1.min.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.menu.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/common.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/wrest.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/placeholders.min.js"></script>', 0);
//add_javascript('<script src="'.G5_JS_URL.'/jquery.bxslider.min.js"></script>', 0);// swiper 로 교체 될 예정
add_javascript('<script src="'.G5_JS_URL.'/shop.js"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/bpopup.js"></script>', 0);//임시로 사용 될 팝업 레거시,,
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/font-awesome/css/font-awesome.min.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/jquery-ui-1.12.1.min.css">', 0);
//add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/jquery.bxslider.min.css">', 0);// swiper 로 교체 될 예정

//theme COMMON JS, CSS
add_javascript('<script src="'.BARRY_THEME_CUSTOM_PC_JS_URL.'/swiper-5.4.5.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_PC_CSS_URL.'/custom/swiper-5.4.5.min.css">', 0);

//Mobile 
if(G5_IS_MOBILE) {
    add_javascript('<script src="'.G5_JS_URL.'/modernizr.custom.70111.js"></script>', 0);// overflow scroll 감지
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/lightbox.js"></script>', 0);
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/bootstrap-4.5.3.js"></script>', 0);
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/jquery-confirm.js"></script>', 0);
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/jquery-ui-timepicker-addon.min.js"></script>', 1);
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/jquery-ui-timepicker-ko.js"></script>', 2);
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/jquery.ui.touch-punch.min.js"></script>', 2);
    add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/common.js"></script>', 0);

    if(defined('_INDEX_')){
        add_javascript('<script src="'.BARRY_THEME_CUSTOM_MOBILE_JS_URL.'/main.js"></script>', 0);
    }

    add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_MOBILE_CSS_URL.'/custom/common.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_MOBILE_CSS_URL.'/custom/lightbox.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_MOBILE_CSS_URL.'/custom/bootstrap-4.5.3.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_MOBILE_CSS_URL.'/custom/jquery-confirm.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_MOBILE_CSS_URL.'/custom/jquery-ui-timepicker-addon.min.css">', 0);

    if(defined('_INDEX_')){
        add_stylesheet('<link rel="stylesheet" href="'.BARRY_THEME_CUSTOM_MOBILE_CSS_URL.'/custom/main.css">', 0);
    }

}//PC
else{
    
}
if(!defined('G5_IS_ADMIN'))
    echo $config['cf_add_script'];
?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-GC1FQR8VZM"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-GC1FQR8VZM');
</script>
</head>
<body<?php echo isset($g5['body_script']) ? $g5['body_script'] : ''; ?>>
<?php
//print_r($member);

if ($is_member) { // 회원이라면 로그인 중이라는 메세지를 출력해준다.
    $sr_admin_msg = '';
    if ($is_admin == 'super') $sr_admin_msg = "최고관리자 ";
    else if ($is_admin == 'group') $sr_admin_msg = "그룹관리자 ";
    else if ($is_admin == 'board') $sr_admin_msg = "게시판관리자 ";

    echo '<div id="hd_login_msg">'.$sr_admin_msg.get_text($member['mb_nick']).'님 로그인 중 ';
    echo '<a href="'.G5_BBS_URL.'/logout.php">로그아웃</a></div>';
}
?>