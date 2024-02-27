<?php
/*
*   plugin/barryCtcWallet/barryCtcWallet.php
*   
*   
*   CTC Wallet 관련 메소드가 호출 됩니다. 
*   IMG과 같은 에셋은 templates을 통해 불러 옵니다.
*/
namespace barry\wallet;

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
use \League\Plates\Engine;
use \League\Plates\Extension\Asset;
use \Exception;
use \barry\db\Driver as barryDb;
use \barry\encrypt\Rsa as barryRsa;

require __DIR__.'/vendor/autoload.php';

class CtcWallet{
    
    public $version = '1.0.0';
    
    const BARRY_CTC_WALLET_DIR = 'barryCtcWallet';
    const BARRY_CTC_WALLET_PATH = G5_PLUGIN_PATH.'/'.self::BARRY_CTC_WALLET_DIR;
    const BARRY_CTC_WALLET_URL = G5_PLUGIN_URL.'/'.self::BARRY_CTC_WALLET_DIR;
    
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
    
    public function init($path = 'basic'){
        try{
            $this->setSkinPath($path);
            $this->setSkinAssetsUrl($path);
            if(!file_exists($this->skinPath)){
                throw new Exception('존재하는 skin이 아닙니다.');
            }
        }
        catch(Exception $e){
            echo '<p class="init_error">초기화 안내: ' .$e->getMessage().'</p>';
        }
    }
    
    //skin path check
    private function setSkinPath($path = false){
        if($path === false){
            throw new Exception("skin 경로 없습니다. 설정을 해주세요");
        }
        else{
            $this->skinPath = self::BARRY_CTC_WALLET_PATH.'/skin/'.$path;
        }
    }        
    //skin setSkinAssetsUrl
    private function setSkinAssetsUrl($path = false){
        if($path === false){
            throw new Exception("skin 경로 없습니다. 설정을 해주세요");
        }
        else{
            $this->skinAssetsUrl = self::BARRY_CTC_WALLET_URL.'/skin/'.$path.'/assets';
        }
    }    
    
    public function getTransferPasswordCheckFormBuild(){
        return $this->transferPasswordCheckFormBuild();
    }
    
    //transferPasswordCheckForm 빌드
    private function transferPasswordCheckFormBuild(){
        try{
            $templates = new Engine($this->skinPath, 'html');
            //var_dump($this->skinPath);
            $templates->loadExtension(new Asset($this->skinPath.'/assets',false));
            
//            $db = barryDb::singletonMethod();
//            $barryRsa = new barryRsa();
            
            if($this->deviceCheck()){
                echo('PC는 지원하지 않습니다.');
            }
            else{
                echo $templates->render('transferPasswordCheckForm.skin', ['msg' => '테스트 메시지','asstsUrl' => $this->skinAssetsUrl]);
            }
            
        }
        catch(Exception $e){
            echo '<p class="boardHeader-error">Barry CTC Wallet 안내: ' .$e->getMessage().'</p>';
        }
    }
    
    //global 로 device 체크...
    private function deviceCheck(){
        //PC Defalt
        if(G5_IS_MOBILE){
            return false;
        }
        else{
            return true;
        }
    }
    
    
}

?>