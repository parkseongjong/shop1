<?php
namespace barry\coupon;

if (!defined('_GNUBOARD_')) exit;

use \ezyang\htmlpurifier;
use \barry\db\DriverApi as barryDb;

require G5_PATH.'/API/vendor/autoload.php';

$purifieConfig = \HTMLPurifier_Config::createDefault();
$purifier = new \HTMLPurifier($purifieConfig);
$db = barryDb::singletonMethod();
$barryDb = $db -> init();

unset($db);

define('BARRY_PLUGIN_COUPON_VER', '1.0');// 최초 버전

define('BARRY_PLUGIN_COUPON_DIR',             'barryCoupon');
define('BARRY_PLUGIN_COUPON_PATH',            G5_PLUGIN_PATH.'/'.BARRY_PLUGIN_COUPON_DIR);
define('BARRY_PLUGIN_COUPON_URL',             G5_PLUGIN_URL.'/'.BARRY_PLUGIN_COUPON_DIR);


$coupon['skin'] = "basic"; // 사용 스킨
$coupon_skin_path = BARRY_PLUGIN_COUPON_PATH.'/skin/'.$coupon['skin']; //스킨 path
$coupon_skin_url = BARRY_PLUGIN_COUPON_URL .'/skin/'.$coupon['skin']; //스킨 url


?>