<?php
/*
*   plugin/barryDbDriver/DriverApi.php
*   
*   
*   barry Db 관련 메소드가 호출 됩니다. 
*   DriverApi는 Api 에서 사용하기 위한 수정 본 입니다.
*   
*/
namespace barry\db;

use \Doctrine\DBAL\DriverManager as DbalDb;
use \PDO;
use \Exception;

require __DIR__.'/vendor/autoload.php';

class DriverApi{
    
    public $version = '1.0.0';
    
//    const BARRY_DB_DIR = 'barryDbDriver';
//    const BARRY_DB_PATH = G5_PLUGIN_PATH.'/'.self::BARRY_DB_DIR;
//    const BARRY_DB_URL = G5_PLUGIN_URL.'/'.self::BARRY_DB_DIR;
    private $connectionParams = array(
                                        'dbname' => 'onefamily11',
                                        'user' => 'onefamily11',
                                        'password' => 'd1elta!green!@01',
                                        'host' => 'localhost',
                                        'driver' => 'pdo_mysql',
                                    );
    private $ctcWalletConnectionParams = array(
                                        'dbname' => 'wallet',
                                        'user' => 'web3_cybertron',
                                        'password' => 'web@1387251!abeieh123#ieh',
                                        'host' => '175.126.82.225',
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
            return 'Barry DB초기화 안내: ' .$e->getMessage();
        }
    }    
    
    public function ctcWallet(){
        try{
            $conn = DbalDb::getConnection($this->ctcWalletConnectionParams);
            return($conn);
        }
        catch(Exception $e){
            return 'Barry DB초기화 안내: ' .$e->getMessage();
        }
    }

    public function getPDO(){
        try{
            $conn = new PDO('mysql:host='.$this->connectionParams['host'].';port=3306;dbname='.$this->connectionParams['dbname'].';charset=utf8', $this->connectionParams['user'], $this->connectionParams['password']);
            return($conn);
        }
        catch(Exception $e){
            return 'Barry DB초기화 안내: ' .$e->getMessage();
        }
    }
    
}

?>