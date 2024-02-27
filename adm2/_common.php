<?php


define('G5_IS_ADMIN', true);
include_once ('../common.php');


define('G5_ADMIN_PATH2',     G5_ADMIN_PATH.'2');


include_once(G5_ADMIN_PATH2.'/admin.lib.php');

if( isset($token) ){
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}

run_event('admin_common');
?>