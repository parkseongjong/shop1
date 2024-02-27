<?php
namespace barry\integration;
use \PDO;

if (!defined('_GNUBOARD_')) exit;

define('BARRY_PLUGIN_INTEGRATION_VER', '1.0');// 최초 버전

define('BARRY_PLUGIN_INTEGRATION_DIR',             'barryIntegration');
define('BARRY_PLUGIN_INTEGRATION_PATH',            G5_PLUGIN_PATH.'/'.BARRY_PLUGIN_INTEGRATION_DIR);
define('BARRY_PLUGIN_INTEGRATION_URL',             G5_PLUGIN_URL.'/'.BARRY_PLUGIN_INTEGRATION_DIR);

$integration['skin'] = "basic"; // 사용 스킨

$integration_skin_path = BARRY_PLUGIN_INTEGRATION_PATH.'/skin/'.$integration['skin']; //스킨 path
$integration_skin_url = BARRY_PLUGIN_INTEGRATION_URL .'/skin/'.$integration['skin']; //스킨 url

$dsn = "mysql:host=".G5_MYSQL_HOST.";port=3306;dbname=".G5_MYSQL_DB.";charset=utf8";
try {
    $db = new PDO($dsn, G5_MYSQL_USER, G5_MYSQL_PASSWORD);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
    echo $e->getMessage();
    //echo '치명적인 DB 오류 관리자에 문의해주세요.';
}
unset($dsn);

?>