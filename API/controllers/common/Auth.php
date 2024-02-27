<?php

namespace barry\common;

//use PDO; //PDO 사용 안함, barry driver 사용.
use \barry\common\Json;
use \Lcobucci\JWT\Builder; //생성
use \Lcobucci\JWT\Signer\Key; //생성
use \Lcobucci\JWT\Signer\Rsa\Sha256; //생성
use \Lcobucci\JWT\Parser; //파싱
use \Lcobucci\JWT\ValidationData; //검증

class Auth{

    public function __construct(){

    }

    public function get(){
        //var_dump(__DIR__);

        $signer = new Sha256();
        //여기 경로는,,,, plugin에 있는걸로...경로 재지정
        $privateKey = new Key('file://'.__DIR__.'/rsaData/private.pem');
        $time = time();

        $token = (new Builder())->issuedBy('http://example.com') // iss 토큰 발급자
                                ->permittedFor('http://example.org') // aud 토큰 대상자
                                ->identifiedBy('4f1g23a12aa', true) // jti 토큰 고유 식별자(일회용 토큰)
                                ->issuedAt($time) //iat 토큰 발급 시간
                                ->canOnlyBeUsedAfter($time + 60) // nbf 토큰 활성 날짜, 이 시간이 지나야 토큰 사용가능
                                ->expiresAt($time + 3600) // exp 토큰 만료일자
                                ->withClaim('uid', 1) // 사용자 정의 클레임 "uid"
                                ->getToken($signer,  $privateKey); // SHA256 싸인, 비밀키로 암호화

        $publicKey = new Key('file://'.__DIR__.'/rsaData/public');
        echo($token.'<br>----');
        var_dump($token->verify($signer, $publicKey));
    }

    public function parser(){

        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLmNvbSIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE1OTc4MDA2NTQsIm5iZiI6MTU5NzgwMDcxNCwiZXhwIjoxNTk3ODA0MjU0LCJ1aWQiOjF9.kDmnZG4hRrrHNvghFjLKGwA09tPc0gFWZCbvJqjoQBnBJTGuVoUk6eCnHZ1al8xNZwYWiw0vfYLnunFfAwih-8rFv3UDYEJc4TCgVi2N8FKbivMOrqP-GwVokW7w74iymfS1cWtKtldoNev4zO_9f3iDaELHjt-NTRl_TmXxoFE5SRwBf2Xahy1dAJmDw4PqTFnFQ_7-KiLmlV9jVBX-OAorQt3Szpqt_K7Z337sSwSauDI4LdC0Yj1xuVpoaqvCf5GM9CyEDvYKmOJqPMXskCILBNbaWyeQMxW7lL7hHZIAZUwPbynM8uKpCO8Cl3b7hooOsfX675DmtZdZsTH5Og';
        $token = (new Parser())->parse((string) $token);
        $token->getHeaders();
        $token->getClaims();

        echo $token->getHeader('jti');
        echo $token->getClaim('iss');
        echo $token->getClaim('uid');
    }

    public function validating(){
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLmNvbSIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE1OTc4MDA2NTQsIm5iZiI6MTU5NzgwMDcxNCwiZXhwIjoxNTk3ODA0MjU0LCJ1aWQiOjF9.kDmnZG4hRrrHNvghFjLKGwA09tPc0gFWZCbvJqjoQBnBJTGuVoUk6eCnHZ1al8xNZwYWiw0vfYLnunFfAwih-8rFv3UDYEJc4TCgVi2N8FKbivMOrqP-GwVokW7w74iymfS1cWtKtldoNev4zO_9f3iDaELHjt-NTRl_TmXxoFE5SRwBf2Xahy1dAJmDw4PqTFnFQ_7-KiLmlV9jVBX-OAorQt3Szpqt_K7Z337sSwSauDI4LdC0Yj1xuVpoaqvCf5GM9CyEDvYKmOJqPMXskCILBNbaWyeQMxW7lL7hHZIAZUwPbynM8uKpCO8Cl3b7hooOsfX675DmtZdZsTH5Og';
        $token = (new Parser())->parse((string) $token);
        var_dump($token);

        $time = time();
         var_dump($time);
        $data = new ValidationData();
        $data->setIssuer('http://example.com');
        $data->setAudience('http://example.org');
        $data->setId('4f1g23a12aa');// 대상.. 주소 hash 값

        var_dump($token->validate($data)); //1

        $data->setCurrentTime($time + 61);

        var_dump($token->validate($data)); //2

        $data->setCurrentTime($time + 4000);

        var_dump($token->validate($data)); //3

        $dataWithLeeway = new ValidationData($time, 20);
        $dataWithLeeway->setIssuer('http://example.com');
        $dataWithLeeway->setAudience('http://example.org');
        $dataWithLeeway->setId('4f1g23a12aa');

        var_dump($token->validate($dataWithLeeway)); //4

        $dataWithLeeway->setCurrentTime($time + 51);

        var_dump($token->validate($dataWithLeeway)); //5

        $dataWithLeeway->setCurrentTime($time + 3610);

        var_dump($token->validate($dataWithLeeway)); //6

        $dataWithLeeway->setCurrentTime($time + 4000);

        var_dump($token->validate($dataWithLeeway)); //7
    }

    public function sessionAuth(){
        if(!isset($_SESSION['ss_mb_id'])){
            return false;
        }
        else{
            return true;
        }
    }
    public function getSessionId(){
        if(isset($_SESSION['ss_mb_id'])){
            return $_SESSION['ss_mb_id'];
        }
        else{
            return false;
        }
    }

    //jwt 인증으로 변경 될 예정 입니다.
    public function ckeyAuth($postData){
        if($postData['ckey'] != 'ctctoken'){
            return false;
        }
        else{
            return true;
        }
    }

    //jwt 인증으로 변경 될 예정 입니다.
    public function ckeyAuthHeader($data){
        $temp = explode(' ',$data);
        if($temp[0] != 'BarryKey' || $temp[1] != 'ctctoken'){
            return false;
        }
        else{
            return true;
        }
    }
}

?>
