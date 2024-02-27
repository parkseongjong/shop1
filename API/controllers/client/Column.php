<?php

namespace barry\client;

use PDO;
use \ezyang\htmlpurifier;
use \barry\common\Rsa;
use \barry\common\Json;
use Exception;

class Column{
    
    protected $pdo;
    protected $queryProcedureError='쿼리 실행 실패';
    protected $queryProcedureReturnError='쿼리 결과 리턴 실패';
    
    public function __construct($pdo){
        $this->pdo = $pdo;
    }
    
    public function save(array $data){
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
    
    public function get($date = false){
        try{
            $date = date('Y-m-d H:i:s',strtotime($date));
            //Json::success();
            //$query = 'SELECT * FROM test where wr_datetime >= "2020-02-23 00:00:00" ';
            $query = 'SELECT wr_subject, ca_name, wr_content, wr_link1,wr_last, wr_datetime  FROM test where wr_datetime >= ? order by wr_datetime ASC';
            $ret = $this->pdo->prepare($query);
            $ret->bindValue(1, $date, PDO::PARAM_STR);
            
            if(!$ret->execute()){
                throw new Exception($this->queryProcedureError);
            }
            if(!$ret = $ret->fetchAll()){
                throw new Exception($this->queryProcedureReturnError);
            }
            $ret = new Json($ret);
            return $ret->encode();
        }
        catch (Exception $e){
            echo $e->getMessage();
            exit();
        }
    }
}

?>