<?php
    //Barry API 환경별 정의 입니다.
    if(BARRY_API !== true) exit();

    //const BARRY_ENV = 'DEV';
    const BARRY_ENV = 'REAL';
    const BARRY_DISPLAY_ERROR = true;

    $define = getServerInfo();

    define("BARRY_ROOT_PATH", $define['rootPath']);
    define("BARRY_API_ROOT_PATH", $define['rootPath'].'/API');
    define("BARRY_API_VIEW_ROOT_PATH", $define['rootPath'].'/API/view');

    define("BARRY_URL", $define['url']);
    define("BARRY_API_URL", $define['url'].'/API');
    define("BARRY_G5_BBS_URL", $define['url'].'/bbs');

    unset($define);

    function getServerInfo(){
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        if(isset($_SERVER['HTTP_HOST']) && preg_match('/:[0-9]+$/', $host)){
            $host = preg_replace('/:[0-9]+$/', '', $host);
        }
        $hostName = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*]/", '', $host);
        unset($host);
        $http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 's' : '') . '://';
        $subName = explode('.',$hostName);
        if($subName[0] == $hostName){
            $subName = false;
        }
        else{
            $subName = $subName[0];
        }
        $array = array(
            'rootPath' => $_SERVER['DOCUMENT_ROOT'],
            'url' => $http.$hostName,
            'hostName' => $hostName,
            'subName' => $subName
        );
        return $array;
    }
?>