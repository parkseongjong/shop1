<?php

namespace barry\common;

use \barry\common\Filter as barryFilter;

//slim session은 컨테이너에 정의한 데이터를 갖고 와서 사용 할 것,

/*
  $exists = $session->exists('my_key');
  $exists = isset($session->my_key);
  $exists = isset($session['my_key']);

  // Get variable value
  $my_value = $session->get('my_key', 'default');
  $my_value = $session->my_key;
  $my_value = $session['my_key'];

  // Set variable value
  $app->get('session')->set('my_key', 'my_value');
  $session->my_key = 'my_value';
  $session['my_key'] = 'my_value';

  // Merge value recursively
  $app->get('session')->merge('my_key', ['first' => 'value']);
  $session->merge('my_key', ['second' => ['a' => 'A']]);
  $letter_a = $session['my_key']['second']['a']; // "A"

  // Delete variable
  $session->delete('my_key');
  unset($session->my_key);
  unset($session['my_key']);

  // Destroy session
  $session::destroy();

  // Get session id
  $id = $this->session::id();
 */

class Token{

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public static function singletonMethod()
    {
        return self::getInstance();// static 멤버 함수 호출
    }

    protected function __construct()
    {

    }

    private function __clone()
    {

    }

    private function __wakeup()
    {

    }

    public function setSessionToken($sessionName, $value, $containerSession, $prefix=NULL, $suffix=NULL){
        $containerSession->set(trim($prefix.$sessionName.$suffix), $value);
        return true;
    }

    public function getSessionToken($sessionName, $containerSession, $prefix=NULL, $suffix=NULL){
        return $containerSession->get(trim($prefix.$sessionName.$suffix), false);
    }

    public function clearSessionToken($sessionName, $containerSession, $prefix=NULL, $suffix=NULL){
        $containerSession->delete(trim($prefix.$sessionName.$suffix));
        return true;
    }

    public function validSessionToken($sessionName, $tokenValue, $containerSession, $prefix=NULL, $suffix=NULL){
        if($containerSession->get(trim($prefix.$sessionName.$suffix), false) == $tokenValue){
            return true;
        }
        else{
            return false;
        }
    }

    //GB write에서 사용, goods(Item) upload 사용
    public function setUploadToken($tableId, $containerInfo){
        $filter = barryFilter::singletonMethod();
        $session = $containerInfo->get('session');
        $tempFilterData = $filter->postDataFilter(array('bo_table' => $tableId),array('bo_table'=>'stringNotEmpty'));
        $token = md5(uniqid(rand(), true));
        self::setSessionToken($tableId,$token,$session,'ss_write_','_token');
        unset($tempFilterData);

        return array('code' => 200, 'token'=> $token);
    }
}