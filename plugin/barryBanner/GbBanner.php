<?php
namespace barry\gbBanner;

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

use \Monolog\Logger;
use \MySQLHandler\MySQLHandler;
use \barry\db\DriverApi as barryDb;


use \barry\banner\Slide as barryBannerSlide;
use \barry\banner\SlideInterface;

require G5_PATH.'/API/vendor/autoload.php';

class Banner implements SlideInterface{

    public $version = '1.0.0';
    private $logger = false;

    const BARRY_BANNER_DIR = 'barryBanner';
    const BARRY_BANNER_PATH = G5_PLUGIN_PATH.'/'.self::BARRY_BANNER_DIR;
    const BARRY_BANNER_URL = G5_PLUGIN_URL.'/'.self::BARRY_BANNER_DIR;

    const VERSION = '1.0.0';

    public function __construct(){
        date_default_timezone_set('Asia/Seoul');
        $this->logger = new \Monolog\Logger('barryMall');
        $db = barryDb::singletonMethod();
        $mySQLHandler = new MySQLHandler($db->getPDO(), "barry_log", array(), \Monolog\Logger::ERROR);

        $this->logger->pushHandler($mySQLHandler);
        unset($db);
        unset($mySQLHandler);
        $this->logger->info('mono Log Barry gbBanner log 로드 완료');
    }

    public function draw(string $location, string $publishLocation){
        $barryBannerSlide =  new barryBannerSlide(false,false,$this->logger);
        return $barryBannerSlide->draw($location, $publishLocation);
    }

    public function draw2(){

    }
}


?>