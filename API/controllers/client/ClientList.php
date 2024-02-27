<?php

namespace barry\client;

use PDO;
use \ezyang\htmlpurifier;
use \barry\common\Rsa;
use \barry\common\Json;

class ClientList{
    
    protected $pdo;
    protected $queryProcedureError='쿼리 실행 실패';
    protected $queryProcedureReturnError='쿼리 결과 리턴 실패';
    
    public function __construct($pdo){
        $this->pdo = $pdo;
    }
    
    public function save(array $data){
         try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            foreach($data as $key => $value){
                $input_{$purifier->purify($key)} = $purifier->purify($value);
            }
             
            $enc = new Rsa();
            $enc -> makeKey();
            $key = $enc -> viewKey();
            //디버그용
            $input_url = 'http://test.com';
            $input_name = '테스트 이름';
            $input_sirVersion = '테스트 Sir 버전';
            $input_engineVersion = '테스트 엔진 버전';
            $input_regdate = date("Y-m-d-H:i:s", time());
            $input_modifydate = date("Y-m-d-H:i:s", time());
            $input_sirUpdateDate = '';
            $input_dbVersion = '없음';
            $input_apiVersion = '없음';
            //디버그용 END
             
            $input_key = hash('sha256', $input_url, false);
            $input_publicKey = $key['publicKey']; 
            $input_privateKey = $key['privateKey']; 
             
            $query = 'INSERT INTO client_list
                        (cli_key, cli_url, cli_name, cli_sir_version, cli_engine_version, cli_regdate, cli_modifydate, cli_sir_update_date, cli_db_version, cli_api_version, cli_public_key, cli_private_key)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?);';
            $ret = $this->pdo->prepare($query);
            $ret->bindValue(1, $input_key, PDO::PARAM_STR);
            $ret->bindValue(2, $input_url, PDO::PARAM_STR);
            $ret->bindValue(3, $input_name, PDO::PARAM_STR);
            $ret->bindValue(4, $input_sirVersion, PDO::PARAM_STR);
            $ret->bindValue(5, $input_engineVersion, PDO::PARAM_STR);
            $ret->bindValue(6, $input_regdate, PDO::PARAM_STR);
            $ret->bindValue(7, $input_modifydate, PDO::PARAM_STR);
            $ret->bindValue(8, $input_sirUpdateDate, PDO::PARAM_STR);
            $ret->bindValue(9, $input_dbVersion, PDO::PARAM_STR);
            $ret->bindValue(10, $input_apiVersion, PDO::PARAM_STR);
            $ret->bindValue(11, $input_publicKey, PDO::PARAM_STR);
            $ret->bindValue(12, $input_privateKey, PDO::PARAM_STR);
            
            if(!$ret->execute()){
                throw new Exception(queryProcedureError);
            }
             
            $status = new Json();
            return $status->success();
        }
        catch (Exception $e){
            echo $e->getMessage();
            exit();
        }
    }
    
    public function delete($target){
        return false;
    }    
    
    public function singleSearch(){
        return false;
    }   
    
    public function multiSearch(){
        return false;
    }
    
    public function draw(){
        try{
            //Json::success();
            $query = 'SELECT * FROM client_list';
            $ret = $this->pdo->prepare($query);
            
            if(!$ret->execute()){
                throw new Exception(queryProcedureError);
            }
            if(!$ret = $ret->fetchAll()){
                throw new Exception(queryProcedureReturnError);
            }
            return $ret;
        }
        catch (Exception $e){
            echo $e->getMessage();
            exit();
        }
    }
}

?>