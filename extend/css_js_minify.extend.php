<?php
if (!defined('_GNUBOARD_')) exit; // Unable to access direct pages

// php 버전이 5.4 보다 낮으면 사용할수 없습니다.
if( version_compare( PHP_VERSION, '5.4' , '<' ) ){
    return;
}

//return;

/* css js 압축 작동이 안되게 하려면 위의 return 코드의 주석을 풀면 압축이 안됩니다. */
/* 또는 config.php 파일에서 상수 G5_USE_CACHE 의 값을 false 또는 0 으로 수정하면 압축이 안됩니다.  */

if( ! (defined('G5_USE_CACHE') && G5_USE_CACHE) ) return;

define('G5_SKIN_FILES_CASE', 0);

/* 위의 상수 G5_SKIN_FILES_CASE 는 0이면 css 또는 js 파일을 합하여 압축하고, 1이면 각각 파일로 압축합니다.  */

include_once(G5_PLUGIN_PATH.'/minify/classes.php');

$GLOBALS['g5_pack_minifier'] = G5_PACK_MINIFIER::getInstance();
?>