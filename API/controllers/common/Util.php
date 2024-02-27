<?php
/*


    Util.php 
    
    싱글톤 패턴 class 입니다.


*/
namespace barry\common;

use \Psr\Http\Message\UploadedFileInterface as Files;
use \barry\db\DriverApi as barryDb;
use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \Exception;
use \DOMDocument;

class Util {
        
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
    /*
        Accept: application/json', 'Content-Type: application/json; charset=UTF-8'
        Accept: text/html', 'Content-Type: text/html; charset=UTF-8
        Accept: text/html', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8
        Content-Type:multipart/form-data
        hearder Authorization
    */
    //내부 문서에 따라 사이버트론 curl은 text 타입 입출력 text 타입 json 형태 응답을 받습니다.
    public function getCurl($url = false, $data = false){
        try{
            $curl = curl_init();
            
            $data = http_build_query($data);
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "gzip",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 3000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    'Accept: text/html', 
                    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                    'Content-Length:'.strlen($data),
                    "cache-control: no-cache"
                ),
                CURLOPT_VERBOSE => false
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if($err){
                throw new Exception('curl error');
            }
            
            return $response;
            
        }
        catch (Exception $e){
            return $e->getMessage();
        }
        
    }

    //api끼리 통신 시 사용 되는 curl 입니다. 요청, 응답, 컨텐츠타입 모두 application/json,
    public function getCurlApi($url = false, $data = false){
        try{
            $curl = curl_init();
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "gzip",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 3000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json; charset=UTF-8',
                    'Content-Length:'.strlen($data),
                    "cache-control: no-cache"
                ),
                CURLOPT_VERBOSE => false,
            ));

            $response = curl_exec($curl);
            $curlInfo = curl_getinfo($curl);
            $err = curl_error($curl);
            curl_close($curl);
//            var_dump($err);

            if($err){
                throw new Exception('curl error');
            }

            return $response;

        }
        catch (Exception $e){
            return $e->getMessage();
        }

    }

    //api끼리 통신 시 사용 되는 curl(GET) 입니다. 요청, 응답, 컨텐츠타입 모두 application/json,
    public function getCurlApiTypeGet($url = false, $data = false){
        try{
            $curl = curl_init();
            $url.='?'.http_build_query($data,'','&');

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "gzip",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 3000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json; charset=UTF-8',
                    "cache-control: no-cache"
                ),
                CURLOPT_VERBOSE => false,
            ));

            $response = curl_exec($curl);
            $curlInfo = curl_getinfo($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if($err){
                throw new Exception('curl error');
            }

            return $response;

        }
        catch (Exception $e){
            return $e->getMessage();
        }

    }


    //미디어 게시판, 링크 og:image 추출
    public function getCurlGnuLinkImage($url = false){
        try{

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $curlInfo = curl_getinfo($curl);
            curl_close($curl);

            if($curlInfo['http_code']!=200){
                if($curlInfo['http_code'] == 301 || $curlInfo['http_code'] == 302 || $curlInfo['http_code'] == 303) {
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $curlInfo['redirect_url'],
                        CURLOPT_RETURNTRANSFER => true,
                    ));
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
                }
            }

            if($response === false){
                throw new Exception('curl not found');
            }
            if($err){
                throw new Exception('curl error');
            }

            $dom_obj = new DOMDocument();
            @$dom_obj->loadHTML($response);
            $meta_val = false;

            if(@count($dom_obj) > 0) {
                foreach($dom_obj->getElementsByTagName('meta') as $meta) {
                    if($meta->getAttribute('property')=='og:image'){
                        // og:image를 찾아서 meta_val에 저장했습니다.
                        $meta_val = $meta->getAttribute('content');
                        break;
                    }
                }
            }
            if(empty($meta_val)) {
                throw new Exception('not found meta data');
            }

            return $meta_val;
        }
        catch (Exception $e){
            return $e->getMessage();
        }

    }
    
    
    //multipart/form-data 를 빌드해서 전송 합니다.
    //일반 CURL과 다르게 인자값을 3가지 받습니다.
    public function getCurlFiles($url = false, $data = false, $fileData = false){
        try{
            
            $curl = curl_init();
            $files = array();
            foreach($fileData as $key => $value){
                
                $innerArray = array();
                
                foreach($value as $key2 => $value2){
                    if($key2 == 'filePath'){
                        $innerArray[$key2] = file_get_contents($value2);
                    }
                    else{
                        $innerArray[$key2] = $value2;
                    }
                }
                
                $files[$key] = $innerArray;
            }
            
            unset($fileData);
            
            $boundary = uniqid();
            $data = $this->buildDataFiles($boundary, $data, $files);
            var_dump($data);
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "gzip",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 3000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    'Accept: text/html', 
                    'Content-Type: multipart/form-data; boundary=-------------'.$boundary.'; charset=UTF-8',
                    'Content-Length:'.strlen($data),
                    "cache-control: no-cache"
                ),
                CURLOPT_VERBOSE => true,
                CURLOPT_STDERR => fopen('./logs/curl.log','w+'),
                CURLOPT_FILE => fopen('./logs/curlResponseContents.log','w+'),
                CURLOPT_WRITEHEADER => fopen('./logs/curlResponseHeader.log.log','w+'),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if($err){
                throw new Exception('curl error');
            }
            
            return $response;
            
        }
        catch (Exception $e){
            return $e->getMessage();
        }
        
    }

    //multipart/form-data data build
    //RFC7578 참조 https://tools.ietf.org/html/rfc7578
    private function buildDataFiles($boundary, $fields, $files){
        
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }

        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="'.$content['fileName'].'"' . $eol
                //. 'Content-Type: image/png'.$eol
                //. 'Content-Type: image/jpg'.$eol
                . 'Content-Transfer-Encoding: binary'.$eol
                ;

            $data .= $eol;
            $data .= $content['filePath'] . $eol;
        }
        $data .= "--" . $delimiter . "--".$eol;

        return $data;
    }

    //서버끼리 API 통신 시 위변조 체크
    public function serverCommunicationAuth($protocol,$token,$timestemp,$signature){
        //5분 초과 시 유효하지 않음
        if(time() >= strtotime ( '+5 minutes' , $timestemp)){
            return false;
        }
        $tokenHash = md5($token);
        $buildSignature = hash('sha256',trim($protocol.'|'.$tokenHash.'|'.$timestemp),false);
        if($buildSignature != $signature){
            return false;
        }
        else{
            return true;
        }
    }

    //CTC WALLET : walletadmin
    //BARRY : barryadmin
    public function serverCommunicationBuild($protocol = false,$token){
        if($protocol === false){
            $protocol = 'barryadmin';
        }
        $timestemp = time();
        $tokenHash = md5($token);
        return [
            'signature' => hash('sha256',trim($protocol.'|'.$tokenHash.'|'.$timestemp),false),
            'timestamp' => $timestemp,
            'value' => $token
        ];
    }

/*******************************************************************************
    유일한 키를 얻는다.
    (GB 버전)
    type이 없는 경우는 숫자(String) 데이터만 둘어간다.
    결과 :

    년월일시분초00 ~ 년월일시분초99
    년(4) 월(2) 일(2) 시(2) 분(2) 초(2) 100분의1초(2)
    총 16자리이며 년도는 2자리로 끊어서 사용해도 됩니다.
    예) 2008062611570199 또는 08062611570199 (2100년까지만 유일키)

    사용하는 곳 :
    1. 게시판 글쓰기시 미리 유일키를 얻어 파일 업로드 필드에 넣는다.
    2. 주문번호 생성시에 사용한다.
    3. 기타 유일키가 필요한 곳에서 사용한다.
*******************************************************************************/
    public function getUniqId($type = false){

        //난수 생성에 조금 더 생각을.. 해봐야 할 것 같음.
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();
        $barrydb->executeQuery( 'LOCK TABLE barry_uniqid WRITE');
        while (1) {
            // 년월일시분초에 100분의 1초 두자리를 추가함 (1/100 초 앞에 자리가 모자르면 0으로 채움)
            $key = (string)date('YmdHis', time()) . str_pad((int)(microtime() * 100), 2, "0", STR_PAD_LEFT);
            if($type != false){
                $key = trim((string)$type.$key);
            }
            try{
               $proc = $barrydb->executeQuery('INSERT INTO barry_uniqid set bu_uniqid = "'.$key.'", bu_ip = "'.$_SERVER['REMOTE_ADDR'].'"');
               if($proc){
                   break;
               }
            }
            catch (Exception $e) {
                //bu_uniqid 속성이 PRI(PK)라, 중복 시 SQL 오류가 발생함, 바깥에서 안잡고 이곳에서 에러를 잡지만, 아무런 처리 안하고 중복 되지 않은 키가 나올 때 까지 while 한다.
            }
            usleep(10000); // 100분의 1초를 쉰다
        }
        $barrydb->executeQuery('UNLOCK TABLES');
        return $key;
    }

    //now date
    public function getDateTime(){
        $key = (string) date('YmdHis', time());
        return $key;
    }

    //now date
    public function getDateSql(){
        $key = (string) date('Y-m-d H:i:s', time());
        return $key;
    }

    public function getDateSqlDefault(){
        $key = '0000-00-00 00:00:00';
        return $key;
    }

    //not iso unixDate(YmdHis ex.20210121163029) -> sqlDate(Y-m-d H:i:s ex.2021-01-21 16:30:29)
    public function getSqlDateInNotIsoUnixDateTimeConvert($date){
        $key = (string) date('Y-m-d H:i:s', strtotime($date));
        return $key;
    }

    //iso unixDate(time stamp ex.1611219583) -> sqlDate(Y-m-d H:i:s ex.2021-01-21 16:30:29)
    public function getSqlDateInIsoUnixDateTimeConvert($date){
        $key = (string) date('Y-m-d H:i:s', $date);
        return $key;
    }

    //GB 회원 정보 호출, $memberId은 mb_no가 아니라 mb_id(핸드폰 아이디) 이다
    public function getGbMember($memberId = false){
        if($memberId === false){
            return false;
        }
        $db = barryDb::singletonMethod();
        $barrydb = $db -> init();
        unset($db);

        $memberId = preg_replace("/[^0-9a-z_]+/i", "", $memberId);

        $memberInfo = $barrydb->createQuerybuilder()
            ->select('*')
            ->from('g5_member')
            ->where('mb_id = ?')
            ->setParameter(0,$memberId)
            ->execute()->fetch();
        if(!$memberInfo){
            return false;
        }
        else{
            return $memberInfo;
        }
    }

    //GB 회원 정보 호출, mb2 (wallet 고유 id)
    public function getGbMemberMb2($mb2 = false){
        if($mb2 === false){
            return false;
        }
        $db = barryDb::singletonMethod();
        $barrydb = $db -> init();
        unset($db);

        $mb2 = preg_replace("/[^0-9a-z_]+/i", "", $mb2);

        $memberInfo = $barrydb->createQuerybuilder()
            ->select('*')
            ->from('g5_member')
            ->where('mb_2 = ?')
            ->setParameter(0,$mb2)
            ->execute()->fetch();
        if(!$memberInfo){
            return false;
        }
        else{
            return $memberInfo;
        }
    }

    //GB board 테이블 정보 호출,
    public function getGbBoard($tableName = false, bool $single = false){
        if($tableName === false){
            return false;
        }
        $db = barryDb::singletonMethod();
        $barrydb = $db -> init();
        unset($db);

        $tableName = preg_replace("/[^0-9a-z_]+/i", "", $tableName);


        $boardInfoQueryBuilder = $barrydb->createQuerybuilder();
        if($single === true){
            $boardInfoQueryBuilder -> select('bo_table');
        }
        else{
            $boardInfoQueryBuilder -> select('*');
        }
        $boardInfo = $boardInfoQueryBuilder
            ->from('g5_board')
            ->where('bo_table = ?')
            ->setParameter(0,$tableName)
            ->execute()->fetch();
        if(!$boardInfo){
            return false;
        }
        else{
            return $boardInfo;
        }
    }

    // GB board write item 다음 고유 id 호출
    function getNextNum($queryTableFullName){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();
        unset($db);

        // 가장 작은 번호를 얻어
        $nextItemId = $barrydb->createQuerybuilder()
            ->select('min(wr_num) as minItemNum')
            ->from($queryTableFullName)
            ->execute()->fetch();

        // 가장 작은 번호에 1을 빼서 넘겨줌
        return (int)($nextItemId['minItemNum'] - 1);
    }

    function deleteThumbnail($path = false, $targetTable = false, $file = false){
        if(!$path || !$targetTable || !$file){
            return false;
        }
        $fn = preg_replace("/\.[^\.]+$/i", "", basename($file));
        $files = glob($path.'/file/'.$targetTable.'/thumb-'.$fn.'*');

        //detail 썸네일 이미지도 삭제
        $detailFiles = glob($path.'/file/'.$targetTable.'/detail/thumb-'.$fn.'*');
        if (is_array($files)) {
            foreach ($files as $filename){
                unlink($filename);
            }
        }
        if (is_array($detailFiles)) {
            foreach ($detailFiles as $detailName){
                unlink($detailName);
            }
        }
    }

    public function utf8StringCut($str, $size, $suffix='...' ){

        if(mb_strlen($str)<=$size) {
            return $str;
        }
        else {
            $str = mb_substr($str, 0, $size, 'utf-8');
            $str .= $suffix;
        }

        return $str;
    }


    /**
     * @param string $directory
     * @param Files $uploadedFile
     * @param string $option image 일때는 이미지 처리 file 때는 그 외 파일 처리
     * @return false|string
     * @throws Exception
     */
    public function slimApiMoveUploadedFile(string $directory, Files $uploadedFile, string $option){
        /*
            getStream() = object(Slim\Psr7\Stream)#
            moveTo($targetPath)
            getSize() = int(275877)
            getError()
            getClientFilename() = 4cf8b9d6129ac528adae9c1f42a76d60.jpg
            getClientMediaType() = image/jpeg
         */

        $extentsionList = array(
            'image' => 'jpg|jpeg|gif|png|swf',
            'media' => 'asx|asf|wmv|wma|mpg|mpeg|mov|avi|mp3',
            'other' => 'php|pht|phtm|htm|cgi|pl|exe|jsp|asp|inc'
        );
        //file name은 특수문자 넘어올 수 있으니 제거
        $fullFileName = preg_replace('/["\'<>=#&!%\\\\(\)\*\+\?]/', '',$uploadedFile->getClientFilename());
        $mediaType = $uploadedFile->getClientMediaType();
        $metaData = $uploadedFile->getStream()->getMetadata();
        $fileSize = $uploadedFile->getStream()->getSize();
        $extension = pathinfo($fullFileName, PATHINFO_EXTENSION);
        $singleFileName = pathinfo($fullFileName, PATHINFO_FILENAME);

        foreach ($extentsionList as $key => $value){
            //이미지 처리 이미지 확장자가 아닌경우 false 처리
            if($option == 'image'){
                if($key == 'image'){
                    if(!preg_match('/\.('.$value.')$/i', $fullFileName)){
                        return false;
                    }
                    //파일 스트림을 직접 검사, 이미지 아닌 경우 false 처리
                    //참고 사항 https://www.php.net/manual/en/function.exif-imagetype
                    $temp = exif_imagetype($metaData['uri'] );
                    if(!$temp || $temp > 16){
                        return false;
                    }
                 }
            }//그 외 확장자 경우 false 처리, 아직 동영상은 처리 해야 할 이슈가 없음.
            else if($option == 'file'){
                if(!preg_match('/\.('.$value.')$/i', $fullFileName)){
                    return false;
                }//그 외 확장자 처리, 악성 파일 실행 못하게 확장자 변경,
                else{
                    $extension = $extension.'----x';
                }
            }
            else{
                return false;
            }
        }

        //참고 사항 http://php.net/manual/en/function.random-bytes.php
        //$convertSingleFileName = bin2hex(random_bytes(8));
        //유니크 함 때문에... 혹시 몰라서 바이트 수를 늘립니다.
        $realFilePath = $directory;
        while(1){
            //만약 파일명이 중복 된다면, (난수 테스트 5000개 중복 없음) 참고 사항 : http://192.168.0.10:9011/admin/barrybarries/issue/25
            $convertSingleFileName = bin2hex(random_bytes(16));
            $convertFullName = sprintf('%s.%0.5s', $convertSingleFileName, $extension);
            if(!file_exists($realFilePath.'/'.$convertFullName)) {
                break;
            }
        }
        $realFilePathLocation = $realFilePath.'/'.$convertFullName;
        $uploadedFile->moveTo($realFilePathLocation);
        //파일 퍼미션 변경
        chmod($realFilePathLocation, 0644);

        $return = array();

        if($option == 'image'){
            //위에서 한꺼번에 exif_imagetype 말고 getimagesize 처리를 해도 되지만, 비정상적인 파일이 있을 수 있기에... 이렇게 처리..
            //3번째 인자는 GD 타입이 리턴 됩니다 GB에서 파일 타입을 int 형으로 저장 하기 때문에 추가 합니다. 참고 : https://www.php.net/manual/en/image.constants.php
            list($imageWidth, $imageHeight, $predefinedImageType) = getimagesize($realFilePathLocation);
            $return['type'] = 'image';
            $return['width'] = $imageWidth;
            $return['height'] = $imageHeight;
            $return['predefinedImageType'] = $predefinedImageType;
            $return['size'] = $fileSize;
            $return['name'] = $singleFileName;
            $return['convertName'] = $convertSingleFileName;
            $return['extension'] = $extension;
            $return['path'] = $realFilePath;
            $return['pathLocation'] = $realFilePathLocation;
        }//일반 파일 처리는 아직... 추후 구현, db에 저장 해야함.
        elseif($option == 'file'){

        }
        else{
            return false;
        }


        return $return;
    }

    public function slimApiDeleteUploadedFile(string $directory, $oldFileInfo){

        $realFilePath = $directory;
        $convertFullName = sprintf('%s.%0.5s', $oldFileInfo['bb_target'], $oldFileInfo['bb_type']);

        if(file_exists($realFilePath.'/'.$convertFullName)) {
            unlink($realFilePath.'/'.$convertFullName);
            return true;
        }
        else{
            return false;
        }
    }

}

?>