<?php
namespace barry\gbCoupon;

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가


use \Monolog\Logger;
use \MySQLHandler\MySQLHandler;
use \barry\db\DriverApi as barryDb;

use \barry\common\Auth;

use \barry\coupon\Auth as barryCouponAuth;
use \barry\coupon\Premium as barryCouponPremium;
use \barry\coupon\PremiumInterface;

require G5_PATH.'/API/vendor/autoload.php';

class Coupon implements PremiumInterface{

    public $version = '1.0.0';
    private $logger = false;

    const BARRY_COUPON_DIR = 'barryCoupon';
    const BARRY_COUPON_PATH = G5_PLUGIN_PATH.'/'.self::BARRY_COUPON_DIR;
    const BARRY_COUPON_URL = G5_PLUGIN_URL.'/'.self::BARRY_COUPON_DIR;

    const VERSION = '1.0.0';

    public function __construct(){
        date_default_timezone_set('Asia/Seoul');
        $this->logger = new \Monolog\Logger('barryMall');
        $db = barryDb::singletonMethod();
        $mySQLHandler = new MySQLHandler($db->getPDO(), "barry_log", array(), \Monolog\Logger::ERROR);

        $this->logger->pushHandler($mySQLHandler);
        unset($db);
        unset($mySQLHandler);
        $this->logger->info('mono Log Barry gbCouponAuth log 로드 완료');
    }

    public function sellerCheck(){
        $auth = new auth();
        if (!$auth->sessionAuth()) {
            $this->logger->error('sellerCheck gbCouponAuth fail');
            return false;
        }
        else {
            $memberId = $auth->getSessionId(); //주문자 ID 값 (핸드폰 번호)
        }
        $barryCouponAuth =  new barryCouponAuth(false,$memberId,$this->logger);
        return $barryCouponAuth->seller();
    }

    public function premiumCheck(){
        $auth = new auth();
        if (!$auth->sessionAuth()) {
            $this->logger->error('premiumCheck gbCouponAuth fail');
            return false;
        }
        else {
            $memberId = $auth->getSessionId(); //GB ID 값 (핸드폰 번호)
        }
        $barryCouponAuth =  new barryCouponAuth(false,$memberId,$this->logger);
        return $barryCouponAuth->premium();
    }

    public function adItemInsert(){
        $barryCouponPremium =  new barryCouponPremium(false,false,$this->logger);
        return $barryCouponPremium->adItemInsert();
    }

    public function adItemUpdate(){
        $barryCouponPremium =  new barryCouponPremium(false,false,$this->logger);
        return $barryCouponPremium->adItemUpdate();
    }

    public function adItemCheck(){
        $barryCouponPremium =  new barryCouponPremium(false,false,$this->logger);
        return $barryCouponPremium->adItemCheck();
    }

    public function adItemLogInsert($biaId = false, $type = false){
        $auth = new auth();
        if (!$auth->sessionAuth()) {
            $memberId = false;
        }
        else {
            $memberId = $auth->getSessionId(); //GB ID 값 (핸드폰 번호)
        }
        $barryCouponPremium =  new barryCouponPremium(false,$memberId,$this->logger);
        return $barryCouponPremium->adItemLogInsert($biaId, $type);
    }

    public function adItemReport(){
        //curl을 corn으로 쓴다면, API에서 호출 해야 하고, header에 key값 태우기
//        $auth = new auth();
//        if (!$auth->sessionAuth()) {
//            $memberId = false;
//        }
//        else {
//            $memberId = $auth->getSessionId(); //GB ID 값 (핸드폰 번호)
//        }
        $barryCouponPremium =  new barryCouponPremium(false,false,$this->logger);
        return $barryCouponPremium->adItemReport();
    }



}


?>