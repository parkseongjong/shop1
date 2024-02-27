<?php
if($_SERVER['REMOTE_ADDR'] != '127.0.0.1'){
    exit();
}
include_once('../../common.php');
error_reporting(E_ALL);
ini_set("display_errors", 1);
include_once(G5_PLUGIN_PATH.'/barryEncrypt/Rsa.php');
use barry\encrypt\Rsa as barryRsa;

include_once('../../head.sub.php');
$test = new barryRsa;

var_dump($test->encrypt('가나다라마바사'));
var_dump($test->encrypt('01050958112 오정택'));
var_dump($test->decrypt('uJ9QA/pFwkSvkRg5jcZWOs3xH8QYgle4IWp9E1vAAA0caoAaPOHCNN4gDQREBqzrvCW2Np47XQzrWfQDcyGFRk/6dm5NcVx60xv9wS3cKoXlF9FtmCkMqxSLzYP81KdQSyDK21MyjY7pJ4Bo/CCla+QXFTY9SfB2WzM2eDdZ+WzCHogRfSESd3/HKmHJBUDCrIFWtr3WJ3Sf8BYqKkssSPji+fZv9QcsokkxPNo3G0m6kwp2kW40EBF9eZUi/AWIl4U1qLD2QaMW8W4xIbFhNKqyqRdQjeSTztMtFngxnkS2sihWx9m6rp/8UO2XJILTuxOclMdu+BHCyG9H0GVKpg=='));
var_dump($test->decrypt('XX5diy6kJIkmziQ4jVm//tAxTtsUqMQjN9npjpFUckdfvfQf2jKxnEqkBkedET5KvCCcodLlR8J9KskPSmZB+2f4wbCylM8Rs9/Yll8dZhO9Aflz9k2249K8En3zSwtgvG47Z3OxvlGBFp5Zw6DlQDeLwhwEK2GvV4hdavGMRx/+/LUmc7Cjb5sWjt35H7Pphd6qYZu5AlnvYmunZzyiEUKmsK/uY/Pug480vsDs+w8ed+IfFZdsLz4LZ1g9zVlO6vEzdxPSj/9th+puamOtu+upMQ6IbKyeCQTJLp0xSt/2NRCttq0gvXszZSbDHL+Q9VHeg1PNOz47+5d//3qyog=='));
var_dump($test->decrypt('eTdMb7U0JylKfTm3VAmlm+1q06RulXRGc59tONB4MOPaUaQpfVxbtl8WjPAbj2WAG8wYp6xhoUVdg/2nPGnuyeHl63SAj5Zmn89FJ8NjWArThHm7KkdB0ZbQYgfZLjfHgY0wKVX/sKdhojAs72nQA7vKVAQqTGGFWjXW63vYRkq0KwBCUmuKAy/SJKhpqxLsbOk4VGWTeZxYNEPPYS9gyuxz4/xNg7Ii8LBS4LU/rugASVrFCB3KvdeIfl3qb6UwiNlUtOpiRtUdswoDwKhzBMpCm163Bzic3e4OVoFL1zEMRgqYw3FbFRkkKudxEfIANKpP+yPI8kQK2KwYgFbkBw=='));

include_once('../../tail.sub.php');
?>