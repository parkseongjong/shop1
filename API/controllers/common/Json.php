<?php

namespace barry\common;

class Json {
// 200 success
// 403 auth Fail
// 404 Fail 
    //인자값에 배열 데이터가 있다면, 리턴 할 배열에 추가를 해서 상태값을 보내줍니다.
    public function success($data = false){
        if($data == false){
            return json_encode(array('code'=>200,'msg'=>'success'), JSON_UNESCAPED_UNICODE);
        }
        else{
            $data['code'] = 200;
            $data['msg'] = 'success';
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }    
    
    public function authFail($data = false){
        if($data == false){
            return json_encode(array('code'=>403,'msg'=>'authFail'), JSON_UNESCAPED_UNICODE);
        }
        else{
            $data['code'] = 403;
            $data['msg'] = 'authFail';
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }
    
    public function fail($data = false){
        if($data == false){
            return json_encode(array('code'=>404,'msg'=>'fail'), JSON_UNESCAPED_UNICODE);
        }
        else{
            $data['code'] = 404;
            $data['msg'] = 'fail';
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }
}

?>