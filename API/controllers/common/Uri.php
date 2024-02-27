<?php


namespace barry\common;

use \barry\db\DriverApi as barryDb;
use \barry\common\Util as barryUtil;

class Uri{

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

    public function generateSeoTitle($string, $wordLimit=8){
        $separator = '-';

        if($wordLimit != 0){
            $wordArr = explode(' ', $string);
            $string = implode(' ', array_slice($wordArr, 0, $wordLimit));
        }

        $quoteSeparator = preg_quote($separator, '#');

        $trans = array(
            '&.+?;'                    => '',
            '[^\w\d _-]'            => '',
            '\s+'                    => $separator,
            '('.$quoteSeparator.')+'=> $separator
        );

        $string = strip_tags($string);

        if( function_exists('mb_convert_encoding') ){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        }

        foreach ($trans as $key => $val){
            $string = preg_replace('#'.$key.'#iu', $val, $string);
        }

        $string = strtolower($string);

        return trim(trim($string, $separator));
    }

    public function existSeoUrl($type, $seoTitle, $writeTable, $sqlId=0){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $existsTitle = '';
        $sqlId = preg_replace('/[^a-z0-9_\-]/i', '', $sqlId);
        // 영카트 상품코드의 경우 - 하이픈이 들어가야 함

        if($type === 'itemUpload'){
            $row = $barrydb->createQueryBuilder()
                ->select('wr_seo_title')
                ->from($writeTable)
                ->where('wr_seo_title = ?')
                ->andWhere('wr_id != ?')
                ->setParameter(0,$seoTitle)
                ->setParameter(1,$sqlId)
                ->setMaxResults(1)
                ->execute()->fetch();
            $existsTitle = $row['wr_seo_title'];
        }

        if ($existsTitle){
            return 'is_exists';
        }
        else{
            return '';
        }
    }

    public function existSeoTitleRecursive($type, $seoTitle, $writeTable, $sqlId=0){
        $util = barryUtil::singletonMethod();
        static $count = 0;

        $seoTitleAdd = ($count > 0) ? $util->utf8StringCut($seoTitle, 200 - ($count+1), '')."-$count" : $seoTitle;

        if(!self::existSeoUrl($type, $seoTitleAdd, $writeTable, $sqlId) ){
            return $seoTitleAdd;
        }

        $count++;

        if( $count > 198 ){
            return $seoTitleAdd;
        }

        return self::existSeoTitleRecursive($type, $seoTitle, $writeTable, $sqlId);
    }
}