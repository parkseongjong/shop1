<?php
declare(strict_types=1);

use \DI\Container;
use \Slim\Factory\AppFactory;

const BARRY_API = true;

// barry define,
require_once (__DIR__.'/config/define.php');

ini_set("display_errors", (string)BARRY_DISPLAY_ERROR);

require (__DIR__ .'/vendor/autoload.php');

$c = new Container();
// Set barry containerSettings!
$settings = require_once (__DIR__ . '/config/containerSettings.php');
$settings($c);

AppFactory::setContainer($c);
$app = AppFactory::create();

$appSettings = require_once(__DIR__.'/config/appSettings.php');
$appSettings($app);
/**
 * @OA\Info(title="BARRY API", version="1.0", description="BARRY API 명세 입니다.", contact={"name":"01050958112"})
 */
$routesListArray = array('other','ctcWallet','payup','coupon','barryOrder','barryGoods','barryMemo','admin');
foreach ($routesListArray as $routesListArrayKey => $routesListArrayValue){
    $routes = require_once(__DIR__.'/routes/'.$routesListArrayValue.'.php');
    $routes($app);
}
unset($appSettings,$routes,$routesListArray,$routesListArrayKey,$routesListArrayValue);

$app->run();

?>