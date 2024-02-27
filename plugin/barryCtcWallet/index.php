<?php
if($_SERVER['REMOTE_ADDR'] != '127.0.0.1'){
    exit();
}
include_once('../../common.php');

error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once(G5_PLUGIN_PATH.'/barryCtcWallet/CtcWallet.php');
use barry\wallet\CtcWallet as ctcWallet;

include_once('../../head.sub.php');
$test = ctcWallet::singletonMethod();
$test-> init('basic');
$test-> getTransferPasswordCheckFormBuild();



include_once('../../tail.sub.php');
?>