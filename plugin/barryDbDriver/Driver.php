<?php
/*
*   plugin/barryDbDriver/Driver.php
*   
*   
*   barry Db 관련 메소드가 호출 됩니다. 
*   
*/
namespace barry\db;

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
use \Doctrine\DBAL\DriverManager as DbalDb;
use \Exception;

require __DIR__.'/vendor/autoload.php';

class Driver{
    
    public $version = '1.0.0';
    
    const BARRY_DB_DIR = 'barryDbDriver';
    const BARRY_DB_PATH = G5_PLUGIN_PATH.'/'.self::BARRY_DB_DIR;
    const BARRY_DB_URL = G5_PLUGIN_URL.'/'.self::BARRY_DB_DIR;
    private $connectionParams = array(
                                        'dbname' => 'onefamily11',
                                        'user' => 'onefamily11',
                                        'password' => 'd1elta!green!@01',
                                        'host' => 'localhost',
                                        'driver' => 'pdo_mysql',
                                    );
    public $skinPath ='';
    public $skinAssetsUrl ='';
    
    const VERSION = '1.0.0';
        
    public static function getInstance(){
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }
    public static function singletonMethod(){
        return self::getInstance();// static 멤버 함수 호출
    }
    protected function __construct() {
        
    }
    private function __clone(){
        
    }
    private function __wakeup(){
        
    }
    
    public function init(){
        try{
            $conn = DbalDb::getConnection($this->connectionParams);
            //$queryBuilder = $conn->createQueryBuilder();
            //쿼리빌더는 쿼리 짤 때 마다 불러오기...
            return($conn);
        }
        catch(Exception $e){
            echo '<p class="init_error">Barry DB초기화 안내: ' .$e->getMessage().'</p>';
        }
    }
    
    
    
}

?>