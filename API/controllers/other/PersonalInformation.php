<?php

namespace barry\other;

use \ezyang\htmlpurifier;

use \League\Plates\Engine;

use \barry\common\Util as barryUtil;
use \barry\common\Filter as barryFilter;
use \barry\db\DriverApi as barryDb;
use \barry\encrypt\RsaApi as barryRsa;

use \InvalidArgumentException;
use \Exception;

class PersonalInformation{

    private $data = false;
    private $memberId = false;
    private $logger = false;
    private $session = false;
    private $filterData = array();


    public function __construct($postData, $memberId, $containerInfo){
        $this->data = $postData;
		//토큰 ID가 유입 됩니다.
        $this->memberId = $memberId;
        $this->logger = $containerInfo->get('logger');
        $this->session = $containerInfo->get('session');
        unset($postData,$memberId,$containerInfo);
    }

    public function agree(){
        try{
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();
            $barryRsa = new barryRsa;

            //2021.07.30 WALLET DB 서버 분리로.. API로 대체  By.OJT
            $loadPostData = $util->serverCommunicationBuild('walletadmin',$this->memberId);
            $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/normal.php?type=barryAuth',$loadPostData),true);
            //gb mb_id 조회
            $this->logger->alert('BARRY AUTH CRUL 확인!!! /'.print_r($curlReturn,true));
            if($curlReturn['code'] == '00'){
                //복호화
                foreach ($curlReturn['data'] as $key => $value){
                    $curlReturn['data'][$key] = $barryRsa->decrypt($value);
                }
                if(!$curlReturn['data']['ctc_key']){
                    $walletTokenInfo = false;
                }
                else{
                    $walletTokenInfo = $curlReturn['data'];
                }
            }
            else{
                $walletTokenInfo = false;
            }

            //gb mb_id 조회
            $this->logger->alert('BARRY AUTH 확인!!! /'.print_r($walletTokenInfo,true));
            //mb_id는 ctc wlalet 고유 ID 이다.
            $memberInfo = $util->getGbMemberMb2($walletTokenInfo['mb_id']);
            $this->logger->error('BARRY AUTH 확인!!!!!!@@@@ /'.print_r($memberInfo,true));

			//베리에 회원 정보가 없고 첫 방문 시 ... 처리
			if(!$memberInfo){
                $loadPostData = $util->serverCommunicationBuild('walletadmin',$walletTokenInfo['mb_id']);
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

                if(!$ctcWalletInfo){
                    $this->logger->error('personal-information not auth(2)');
                    throw new Exception('personal-information 권한이 없습니다.',403);
                }

                //레거시 코드
                $name = (trim($ctcWalletInfo['auth_name']) != '') ? trim($ctcWalletInfo['auth_name']) : ($ctcWalletInfo['lname'].$ctcWalletInfo['name']);
                $ymd = date('Y-m-d');
                $ymdhis = $ymd . date('H:i:s');

                $insetProc = $barrydb->createQueryBuilder()
                    ->insert('g5_member')
                    ->setValue('mb_id', '?')
                    ->setValue('mb_password', '?')
                    ->setValue('mb_name', '?')
                    ->setValue('mb_nick', '?')
                    ->setValue('mb_nick_date', '?')
                    ->setValue('mb_today_login', '?')
                    ->setValue('mb_datetime', '?')//6
                    ->setValue('mb_level', '?')
                    ->setValue('mb_open_date', '?')
                    ->setValue('mb_email_certify', '?')
                    ->setValue('mb_2', '?')
                    ->setValue('mb_4', '?')
                    ->setValue('mb_5', '?')
                    ->setValue('mb_6', '?')
                    ->setValue('mb_3', '?')
                    ->setParameter(0,$ctcWalletInfo['buildBarryId'])
                    ->setParameter(1,'none')
                    ->setParameter(2,$name)
                    ->setParameter(3,$name)
                    ->setParameter(4,$ymd)
                    ->setParameter(5,$ymdhis)
                    ->setParameter(6,$ymdhis)
                    ->setParameter(7,'2')
                    ->setParameter(8,$ymd)
                    ->setParameter(9,$ymdhis)
                    ->setParameter(10,$walletTokenInfo['mb_id'])
                    ->setParameter(11,$ctcWalletInfo['id_auth'])
                    ->setParameter(12,'1')
                    ->setParameter(13,$util->getDateSql())
                    ->setParameter(14,$walletTokenInfo['ctc_key'])
                    ->execute();
                $barryId = $barrydb->lastInsertId();
                $loadPostData = $util->serverCommunicationBuild('walletadmin',$walletTokenInfo['mb_id']);
                $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/personalInformation.php',$loadPostData),true);

                if($curlReturn['code'] != '00'){
                    $TEMP = false;
                    foreach ($loadPostData as $key => $value){
                        $TEMP .= '/'.$key.':'.$value.'';
                    }
                    foreach ($curlReturn as $key => $value){
                        $TEMP .= '/'.$key.':'.$value.'';
                    }
                    $this->logger->error('personal-information wallet update fail/'.$TEMP);
                    throw new Exception('반영 실패',9999);
                }

                // gb 회원아이디 세션 생성
                $this->session->set('ss_mb_id',$ctcWalletInfo['buildBarryId']);
                // gb 베리 고유 아이디 세션 생성
                $this->session->set('ss_mb_no',$barryId);

                $this->logger->alert('personal-information complete!!/'.$walletTokenInfo['mb_id']);
                return array('code' => 200, 'msg' => '동의 완료');

			}
			else{
			    //기존 회원 방문시
                //숫자형이 아닌, 스트링으로 ... 비교 해야함.
                if($memberInfo['mb_5'] >= '1'){
                    $this->logger->error('personal-information already agreed /'.print_r($memberInfo,true));
                    throw new Exception('이미 동의 하셨습니다.',406);
                }
                
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('g5_member')
                    ->set('mb_5','?')
                    ->set('mb_6','?')
                    ->where('mb_no = ?')
                    ->setParameter(0,1)
                    ->setParameter(1,$util->getDateSql())
                    ->setParameter(2,$memberInfo['mb_no'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('personal-information update fail');
                    throw new Exception('반영 실패',9999);
                }

                //$curlReturn = json_decode($util -> getCurlApi('http://local_wallet/apis/barry/personalInformation.php',$util->serverCommunicationBuild('walletadmin',$memberInfo['mb_2'])),true);
                $loadPostData = $util->serverCommunicationBuild('walletadmin',$memberInfo['mb_2']);
                //$curlReturn = json_decode($util -> getCurlApi('http://local_wallet/apis/barry/personalInformation.php',$loadPostData),true);
                $curlReturn = json_decode($util -> getCurlApi('https://cybertronchain.com/apis/barry/personalInformation.php',$loadPostData),true);
                if($curlReturn['code'] != '00'){
                    $TEMP = false;
                    foreach ($loadPostData as $key => $value){
                        $TEMP .= '/'.$key.':'.$value.'';
                    }
                    foreach ($curlReturn as $key => $value){
                        $TEMP .= '/'.$key.':'.$value.'';
                    }
                    $this->logger->error('personal-information wallet update fail/'.$TEMP);
                    throw new Exception('반영 실패',9999);
                }

                // gb 회원아이디 세션 생성
                $this->session->set('ss_mb_id',$memberInfo['mb_id']);
                // gb 베리 고유 아이디 세션 생성
                $this->session->set('ss_mb_no',$memberInfo['mb_no']);

                $this->logger->alert('personal-information complete!!/'.$memberInfo['mb_id']);
                return array('code' => 200, 'msg' => '동의 완료');
            }
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('personal-information fail!/'.$e->getCode().'/'.$e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}