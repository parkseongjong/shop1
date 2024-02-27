<?php
use barry\common\Util as barryUtil;
use barry\db\DriverApi as barryDb;
use barry\encrypt\RsaApi as barryRsa;

include_once('./_common.php');
require G5_PATH.'/API/vendor/autoload.php';


$ret = array('err'=>'fail');

$is_guest = false;
if ($is_guest) {
    $ret['err'] = '회원만 이용하실 수 있습니다.';
}
else {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $util = barryUtil::singletonMethod();
        $barryRsa = new barryRsa;
//        $mb_id = $member['mb_id'];      // 로그인한 회원
//        $mb_name = $member['mb_name'];      // 로그인한 회원명
        $today = date('Y-m-d H:i:s');

        $loadPostData = $util->serverCommunicationBuild('walletadmin',$member['mb_id']);
        $loadPostData['name'] = $member['mb_name'];

        $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/normal.php?type=barrySellerInfo',$loadPostData),true);

        //정상 처리 되었을 때 true를 리턴함.
        if($curlReturn['code'] == '00'){
            $barryRequestInfo = true;
        }
        else{
            $barryRequestInfo = false;
        }

        // 정보있는 경우만 처리
        if (!$barryRequestInfo) {

            $ret['err'] = "이미 등록되어 있습니다.\n관리자 승인이 완료되면 이용하실 수 있습니다.";
            //$ret['err'] = "이미 등록되어 있습니다.\n안내해 드린 입금계좌로 입금 완료하시면 등록 회원으로 승급됩니다.";

        }
        else {
            $ret = array('success'=>"요청이 등록되었습니다.");
        }
    }
}

echo json_encode($ret);
