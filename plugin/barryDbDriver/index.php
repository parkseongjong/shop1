<?php
if($_SERVER['REMOTE_ADDR'] != '127.0.0.1'){
    exit();
}
include_once('../../common.php');
error_reporting(E_ALL);
ini_set("display_errors", 1);
echo('aaa');
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryDb;

include_once('../../head.sub.php');
$test = barryDb::singletonMethod();
$test-> init();

include_once('../../tail.sub.php');
?>