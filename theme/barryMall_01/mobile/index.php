<?php

use barry\common\Util as barryUtil;
use barry\db\DriverApi as barryDb;
use barry\encrypt\RsaApi as barryRsa;

if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_INDEX_', true);

require G5_PATH.'/API/vendor/autoload.php';


## CTC 지갑에서 암호화된 아이디가 넘어오는 경우 자동 로그인 시킨다.
if (isset($_GET['ckey'])) {

    //ckey가 있는경우 헤드
    include_once(G5_THEME_PATH.'/head.sub.php');
	include_once(G5_LIB_PATH.'/latest.lib.php');
    $ckey = trim(filter_input(INPUT_GET, 'ckey', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $firstVisitAgree = trim(filter_input(INPUT_GET, 'firstVisitAgree', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    $util = barryUtil::singletonMethod();
    $barryRsa = new barryRsa;

    $loadPostData = $util->serverCommunicationBuild('walletadmin',$ckey);
    $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/normal.php?type=barryAuth',$loadPostData),true);


    if($curlReturn['code'] == '00'){

        //복호화
        foreach ($curlReturn['data'] as $key => $value){
            $curlReturn['data'][$key] = $barryRsa->decrypt($value);
        }
        if(!$curlReturn['data']['ctc_key']){
            $barryAuthInfo = false;
        }
        else{
            $barryAuthInfo = $curlReturn['data'];
        }
    }
    else{
        $barryAuthInfo = false;
    }
    // 정보있는 경우만 처리
    if ($barryAuthInfo) {

        /*
         *  개인정보 제 3자 제공 동의
         *
         *  베리가 새로 리뉴얼 되기 때문에, ctc key 로그인을 따로 제3체크와 분리 해야 하지만, 하드코딩으로 처리 합니다.
         *
         *  기존 회원 들중 mb2가 없는 사람들은 개인정보 제3자 제공 동의가 정상적으로 안됨, .... mb_2가 없으면... update 한번 해준다.
         *
         */

        //레거시를 그대로 사용....
        $row = $barryAuthInfo;

        //wallet info 가져오기
        $loadPostData = $util->serverCommunicationBuild('walletadmin',$row['mb_id']);
        $loadPostData['type'] = 'walletInfo';
        $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/normal.php?type=walletInfo',$loadPostData),true);
        if($curlReturn['code'] == '00'){
            //복호화
            foreach ($curlReturn['data'] as $key => $value){
                $curlReturn['data'][$key] = $barryRsa->decrypt($value);
            }

            if(!$curlReturn['data']['email']){
                $ctcWalletInfo = false;
            }
            else{
                $ctcWalletInfo = $curlReturn['data'];
            }
        }
        else{
            $ctcWalletInfo = false;
        }

        if($ctcWalletInfo === false){
            echo('CTC WALLET 회원만 이용 가능 합니다. 확인해 주세요');
            exit();
        }

        // cyberTron barry auth, mb_id를 기준으로 찾습니다.
        $mb = get_member_mb2($ctcWalletInfo['id']);
        //barry 에 wallet 고유 ID 값이 없는 경우, 전화번호 기준으로 정보를 찾습니다. (전화번호 변경 되면.. 어떻게?)
        if($mb === NULL ){
            $mb = get_member($ctcWalletInfo['buildBarryId']);
        }
        if(!$mb['mb_2']){
            $sql = "UPDATE g5_member SET mb_2 = ".$ctcWalletInfo['id']." WHERE mb_id =".$mb['mb_id'];
            sql_query($sql);
        }

        if ($firstVisitAgree == 'Y') {
            include_once($member_skin_path . '/personalInformation/personal-information.php');
            include_once(G5_THEME_PATH . '/tail.sub.php');
            exit();
        }

        $ymd = date('Y-m-d');
        $ymdhis = $ymd . date('H:i:s');

        ## 회원 테이블에 해당 없으면 CTC 서버에서 회원정보 가져온다.
        if (!$mb['mb_id']) {
            echo('비정상적인 접근 입니다 관리자에게 문의 해주세요.');
            exit();
            ## 데이터가 있으면 베리베리에 신규 회원 row를 생성한다. -> 신규생성은 개인정보 동의 페이지에서 진행 합니다.
            /*
            if ($ctcWalletInfo) {
                //레거시를 그대로 사용....
                $row2 = $ctcWalletInfo;

                $name = (trim($row2['auth_name']) != '') ? trim($row2['auth_name']) : ($row2['lname'].$row2['name']);
                $sql_insert = " insert into g5_member
                            set mb_id = '{$row2['buildBarryId']}',
                                 mb_password = 'none',
                                 mb_name = '{$name}',
                                 mb_nick = '{$name}',
                                 mb_nick_date = '".$ymd."',
                                 mb_today_login = '".$ymdhis."',
                                 mb_datetime = '".$ymdhis."',
                                 mb_level = '2',
                                 mb_open_date = '".$ymd."',
                                 mb_email_certify = '".$ymdhis."',
                                 mb_2 = '".$ctcWalletInfo['id']."',
                                 mb_4 = '".$row2['id_auth']."',
                                 mb_5 = '1',
                                 mb_6 = '".$util->getDateSql()."',
                                 mb_3 = '".$row['ctc_key']."'";
                sql_query($sql_insert);

                $mb = get_member($row2['buildBarryId']);

                $loadPostData = $util->serverCommunicationBuild('walletadmin',$mb['mb_2']);
                $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/personalInformation.php',$loadPostData),true);
            }
            */
        }// CTC MEMBER 고유 id값이 BARRY MEMBER에 없으면 저장한다.
        else{

			if(!$mb['mb_2']){
				$sql = "UPDATE g5_member SET mb_2 = ".$row['mb_id']." WHERE mb_id =".$mb['mb_id'];
				sql_query($sql);
			}

			//mb_4는 사이버트론에 본인 인증 여부 값을 가져 옵니다.
			//로그인 시 본인인증이 안된 계정은 이름 업데이트를 위해 한번 더 확인 합니다.
            if($mb['mb_4'] != 'Y'){
                if ($ctcWalletInfo) {
                    if($ctcWalletInfo['id_auth'] == 'Y'){
                        $sql = "UPDATE g5_member SET mb_name = '".$ctcWalletInfo['auth_name']."', mb_nick = '".$ctcWalletInfo['auth_name']."', mb_4 = '".$ctcWalletInfo['id_auth']."' WHERE mb_id ='".$mb['mb_id']."'";
                        sql_query($sql);
                    }
                }
            }

           //사이버트론과 API 연동을 위해 CKEY를 member 테이블에 갖는다. (mb_3)
            $sql = "UPDATE g5_member SET mb_today_login = '".$ymdhis."', mb_3 = '".$row['ctc_key']."' WHERE mb_id =".$mb['mb_id'];
            sql_query($sql);
        }

        $loadPostData = $util->serverCommunicationBuild('walletadmin',$ckey);
        $util -> getCurlApi('https://cybertronchain.com/apis/barry/normal.php?type=barryAuthExp',$loadPostData);

        if ($mb['mb_id']) {

            $mem_check = true;

            // 차단된 아이디인가?
            $ymd = date('Y-m-d');
            if ($mb['mb_block_date'] && $mb['mb_block_date'] != '0000-00-00' && $mb['mb_block_date'] >= $ymd) {
                alert('회원님은 접근이 금지되어 있습니다.\n기간 : ~'.$mb['mb_block_date']);
            }

            // 차단된 아이디인가?
            if ($mb['mb_intercept_date'] && $mb['mb_intercept_date'] <= date("Ymd", G5_SERVER_TIME)) {
                $mem_check = false;
            }
		

            // 탈퇴한 아이디인가?
            if ($mb['mb_leave_date'] && $mb['mb_leave_date'] <= date("Ymd", G5_SERVER_TIME)) {
                $mem_check = false;
            }

            if ($mem_check) {

                //session_unset(); // 모든 세션변수를 언레지스터 시켜줌
                //session_destroy(); // 세션해제함

                $is_social_login = false;
                run_event('login_session_before', $mb, $is_social_login);

                // 회원아이디 세션 생성
                set_session('ss_mb_id', $mb['mb_id']);
                // 베리 고유 아이디 세션 생성
                set_session('ss_mb_no', $mb['mb_no']);

                // FLASH XSS 공격에 대응하기 위하여 회원의 고유키를 생성해 놓는다. 관리자에서 검사함 - 110106
                set_session('ss_mb_key', md5($mb['mb_datetime'] . get_real_client_ip() . $_SERVER['HTTP_USER_AGENT']));
            }
        }
    }


    /*print_r($_SESSION);
    echo "<br>";
    echo "<br>";
    echo "<br>";
    echo "<br>";

    echo "--------------------";
    echo "<br>";
    echo $member['mb_id'];
    exit;*/

	if(G5_DEBUG === false){
        //여기로 정상적으로 타고 와짐
        echo "<script>document.location.href='https://barrybarries.kr';</script>";
	}
	else{
		echo "<script>document.location.href='http://local_barry';</script>";
	}

	include_once(G5_THEME_PATH.'/tail.sub.php');
}
else{
	//ckey 가 없는 경우. 헤드
    include_once(G5_THEME_MOBILE_PATH.'/head.php');
	
    //main top banner
    echo latest_banner('theme/banner','mainTop');

    $indexItemLatest = array(
      array('table' => 'Shop', 'select' => false),
      array('table' => 'offline', 'select' => false),
      array('table' => 'estate', 'select' => false),
      array('table' => 'market', 'select' => false),
    );
    for($i=0;$i<=1;$i++){
        while(1){
            $target = mt_rand(0,3);
            if($indexItemLatest[$target]['select'] === false){
                echo latest_item('theme/newItems', $indexItemLatest[$target]['table'], 3, 50);
                $indexItemLatest[$target]['select'] = true;
                break;
            }
        }
    }
    unset($target,$i,$indexItemLatest);
	
	include_once(G5_THEME_MOBILE_PATH.'/tail.php');
	
}


?>