<?php
/*
 *
 *
 *  내가 주문한 내역은 plugin\barryIntegration 에서 임시 vue로 동작중
 *
 *
 */
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;
try{
    if ($is_guest) {
        throw new Exception('로그인 되어 있지 않습니다.',9999);
    }

    $db = barryGbDb::singletonMethod();
    $barrydb = $db-> init();

    $g5['title'] = '가상지갑 입/출금 내역';
    include_once('./_head.php');

    //gb 전역변수 값을 넣어줌..
    $memberVirtualAccountInfo['memberInfo'] = $member;

    echo('<!-- virtualAccount START -->');
    include_once($member_skin_path.'/memberVirtualAccountList.skin.php');
    echo('<!-- virtualAccount END -->');

}
catch (Exception $e){
    if($e->getCode() == 9999){
        alert($e->getMessage());
    }
    else{
        alert('관리자에 문의해주세요.');
    }

    //var_dump($e->getMessage());
}

include_once('./_tail.php');
?>
