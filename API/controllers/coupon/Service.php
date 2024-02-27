<?php

namespace barry\coupon;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\payment\DanalPayup as barryPayup;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Service{

    /**
     * @var bool
     */
    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    public function create(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'couponId' => 'stringNotEmpty',
                'couponSubject' => 'stringNotEmpty',
                'couponType' => 'stringNotEmpty',
                'couponTimeleft' => 'stringNotEmpty',
                'amount' => 'stringNotEmpty',
                'couponMsg' => 'string');
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if($targetPostData[$key] == 'integer'){
                        Assert::{$targetPostData[$key]}((int)$value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data, $targetPostData);// Plain data와 targetPostData는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);

            /*
             * 1. session 정보로 유효한 회원인지 확인
             * 2. session 정보 barry gb id , ctc wallet id 값 저장
             * 3. 활성화 되어 있는 쿠폰이 있는지 확인
             * 4. coupon 정보 빌드 후 생성
             */

            $this->logger->info('접근 회원 정보 조회');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select fail');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            //추후 추가 구매 기능이 필요하다면, 일자를 추가 해줘서 합산 해줘야 함.
            $this->logger->info('이미 발급된 쿠폰이 있는지?');
            $couponInfoBuilder = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_coupon_status')
                ->where('bcs_mb_id = ?')
                ->andWhere('bcs_status = ?')
                ->andWhere('bcs_type = ?')
                ->setParameter(0, $memberInfo['mb_no'])
                ->setParameter(1, 'activation');
                if($filterData['couponType']=='seller') {
                    $couponInfoBuilder
                        ->setParameter(2, 'seller');
                }
                else if($filterData['couponType']=='premium') {
                    $couponInfoBuilder
                        ->setParameter(2, 'premium');
                }
                else {
                    $this->logger->error('coupon type fail');
                    throw new Exception('잘못 된 쿠폰 타입 입니다.',9999);
                }

            $couponInfo = $couponInfoBuilder->execute()->fetch();
            unset($couponInfoBuilder);
            if($couponInfo){
                $this->logger->error('fail! the duplicate check your coupon');
                throw new Exception('이미 사용중인 쿠폰이 존재 합니다.',406);
            }
            //coupon info build
            //DB 기반 쿠폰 정보 리스트 변경
            //쿠폰 권종 (일 수)변조 불가능하게 처리
            $this->logger->info('쿠폰 정보는 변조 안되었는지 ');
            $couponListDataInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_coupon_list')
                ->where('bcl_id = ?')
                ->andWhere('bcl_use = ?')
                ->setParameter(0, $filterData['couponId'])
                ->setParameter(1, 1)
                ->execute()->fetch();
            if(!$couponListDataInfo){
                $this->logger->error('not found coupon list info');
                throw new Exception('구매 요청한 쿠폰 정보가 존재하지 않습니다.',9999);
            }
            //DB 정보 기준으로 쿠폰 정보가 삽입 되지만, 요청 들어온 값들과 비교하여 정상 요청인지 확인
            //그리고 쿠폰 정보가 수정 되는 순간 요청 정보 싱크 맞추기...
            if($filterData['amount']!=$couponListDataInfo['bcl_price'] || $filterData['couponSubject']!=$couponListDataInfo['bcl_subject'] || $filterData['couponType']!=$couponListDataInfo['bcl_type'] || $filterData['couponTimeleft']!=$couponListDataInfo['bcl_timeleft'] || $filterData['couponMsg']!=$couponListDataInfo['bcl_msg']){
                $this->logger->error('fail! this request coupon list info modulation!');
                throw new Exception('쿠폰 금액 필터에 실패 하였습니다..',9999);
            }

            unset($temp);

            $this->logger->info('쿠폰 정보 생성');
            $couponProc = $barrydb->createQueryBuilder()
                ->insert('barry_coupon_status')
                ->setValue('bcs_uniq','?')
                ->setValue('bcs_mb_id','?')
                ->setValue('bcs_ctc_mb_id','?')
                ->setValue('bcs_type','?')
                ->setValue('bcs_subject','?')
                ->setValue('bcs_timeleft','?')
                ->setValue('bcs_price','?')
                ->setValue('bcs_status','?')
                ->setValue('bcs_activation_datetime','?')
                ->setValue('bcs_deactivation_datetime','?')
                ->setValue('bcs_datetime','?')
                ->setParameter(0,$util->getUniqId('CP'))
                ->setParameter(1,$memberInfo['mb_no'])
                ->setParameter(2,$memberInfo['mb_2'])
                ->setParameter(3,$couponListDataInfo['bcl_type'])
                ->setParameter(4,$couponListDataInfo['bcl_subject'])
                ->setParameter(5,$couponListDataInfo['bcl_timeleft'])
                ->setParameter(6,$couponListDataInfo['bcl_price'])
                ->setParameter(7,'deactivation')
                ->setParameter(8,$util->getDateSqlDefault())
                ->setParameter(9,$util->getDateSqlDefault())
                ->setParameter(10,$util->getDateSql())
                ->execute();
            if(!$couponProc){
                $this->logger->error('coupon info build fail');
                throw new Exception('쿠폰 정보 생성을 실패 하였습니다.',9999);
            }

            $couponInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_coupon_status')
                ->where('bcs_id = ?')
                ->setParameter(0, $barrydb->lastInsertId())
                ->execute()->fetch();
            if(!$couponInfo){
                $this->logger->error('your coupon select fail!');
                throw new Exception('생성된 쿠폰 정보 조회를 실패 하였습니다.',406);
            }

            //bcs uniq value return (couponNumer is uniq use)
            $returnArray = array(
                'couponNumber' => $couponInfo['bcs_uniq'],
                'couponType' => $couponInfo['bcs_type'],
                'couponSubject' => $couponInfo['bcs_subject'],
                'couponTimeleft' => $couponInfo['bcs_timeleft'],
                'couponAmount' => $couponInfo['bcs_price'],
            );

            $this->logger->alert('coupon create complete/'.$memberInfo['mb_id'].'/'.$couponInfo['bcs_uniq']);

            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    //단일 쿠폰 정보 리턴
    public function getSingleCoupon(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();


            $targetPostData = array(
                'couponNumber' => 'stringNotEmpty');

            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if($targetPostData[$key] == 'integer'){
                        Assert::{$targetPostData[$key]}((int)$value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data, $targetPostData);// Plain data와 targetPostData는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);

            $this->logger->info('접근 회원 정보 조회');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select fail');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            $this->logger->info('쿠폰이 존재하는지?[단일]');
            $couponInfo = $barrydb->createQueryBuilder()
                ->select('
                    A.bcs_uniq as uniq, A.bcs_subject as subject, A.bcs_timeleft as timeleft, A.bcs_price as price, A.bcs_status as status, A.bcs_activation_datetime as activationDatetime, A.bcs_deactivation_datetime as deactivationDatetime, A.bcs_datetime as datetime,
                    B.bpps_payment_type as cardPaymentType, B.bpps_status as cardStatus, B.bpps_auth_number as cardAuthNumber, B.bpps_transaction_id as transaction
                ')
                ->from('barry_coupon_status', 'A')
                ->innerJoin('A', 'barry_pg_payment_status', 'B', 'A.bpps_id = B.bpps_id')
                ->where('bcs_mb_id = ?')
                ->setParameter(0, $memberInfo['mb_no']);
            if(!$couponInfo){
                $this->logger->error('not found coupon[SINGLE]');
                throw new Exception('존재 하지 않는 쿠폰 입니다.',404);
            }

            $returnArray = array(
                'coupon' => $couponInfo,
            );

            $this->logger->alert('coupon create complete/'.$memberInfo['mb_id']);

            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    //다중 쿠폰 정보 리턴
    public function getMultiCoupon(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $this->logger->info('접근 회원 정보 조회');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select fail');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            $this->logger->info('쿠폰이 존재하는지?[다중]');
            $couponInfoBuilder = $barrydb->createQueryBuilder()
                ->select('
                    A.bcs_uniq as uniq, A.bcs_subject as subject, A.bcs_timeleft as timeleft, A.bcs_price as price, A.bcs_status as status, A.bcs_activation_datetime as activationDatetime, A.bcs_deactivation_datetime as deactivationDatetime, A.bcs_datetime as datetime,
                    B.bpps_payment_type as paymentType, B.bpps_status as paymentStatus, B.bpps_auth_number as cardAuthNumber, B.bpps_transaction_id as transaction,
                    B.bpps_bank_holder as bankHolder, B.bpps_bank_name as bankName, B.bpps_bank_account as bankAccount, B.bpps_bank_expire_datetime as bankExpireDatetime, B.bpps_bank_activation_datetime as bankActivationDatetime
                ')
                ->from('barry_coupon_status', 'A')
                ->innerJoin('A', 'barry_pg_payment_status', 'B', 'A.bpps_id = B.bpps_id')
                ->where('bcs_mb_id = ?')
                ->setParameter(0, $memberInfo['mb_no']);

            $couponInfoTotalCount = $couponInfoBuilder->execute()->rowCount();

            $couponInfo = $couponInfoBuilder
                ->execute()->fetchAll();
            if(!$couponInfo){
                $this->logger->error('not found coupon[MULTI]');
                throw new Exception('존재 하지 않는 쿠폰 입니다.',404);
            }
            unset($couponInfoBuilder);

            $returnArray = array(
                'couponCount' => $couponInfoTotalCount,
                'couponList' => $couponInfo,
            );

            $this->logger->alert('coupon create complete/'.$memberInfo['mb_id']);

            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    //쿠폰 결제요청,
    public function payment(){
        try{
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

//            var_dump(time());
//            var_dump(strtotime('2021-05-05 16:26:00.0'));
//            var_dump($util->getSqlDateInIsoUnixDateTimeConvert('1620199560'));
//            var_dump(strtotime('2021-01-25 17:57:00.0'));
//            var_dump($util->getSqlDateInIsoUnixDateTimeConvert('1611565020'));

            // this data가 각 메소드에서 리셋되는 구조 입니다.
            // 여러 메소드가 실행 될때는, $this->data를 별도로 저장해서 보내줍니다.
            $cashData = $this->data;
            if($cashData['paymentType'] != 'creditCard' && $cashData['paymentType'] != 'virtualBank'){
                $this->logger->error('not supported coupon payment type');
                throw new Exception('유효한 쿠폰 결제 방법이 아닙니다.',406);
            }

            $cuponInfoCreate = self::create();

            if($cuponInfoCreate['code'] == 200){
                // 정상 코드,
                $cashData['orderType'] = 'coupon';//결제 요청 할 주문 종류

                if($cashData['paymentType'] == 'creditCard'){
                    $cashData['orderNumber'] = $cuponInfoCreate['data']['couponNumber']; // 생성된 쿠폰 넘버를 넘긴다.
                    $cashData['amount'] = $cuponInfoCreate['data']['couponAmount'];//쿠폰 금액
                }
                else{
                    $cashData['orderNumber'] = $cuponInfoCreate['data']['couponNumber']; // 생성된 쿠폰 넘버를 넘긴다.
                    $cashData['bankCode'] = $cashData['bankCodeSelected']; // 은행코드
                    $cashData['cashReceiptUse'] = $cashData['cashReceiptUseSelected']; // 현금영수증 발급여부
                    $cashData['cashReceiptType'] = $cashData['cashReceiptTypeSelected']; // 현금영수증 발급 타입
                    $cashData['cashReceiptNo'] = $cashData['cashReceiptNo']; // 현금영수증 식별번호
                    $cashData['userMobileNumber'] = $cashData['userMobileNumber']; // 핸드폰 번호

                    unset($cashData['cashReceiptUseSelected'],$cashData['cashReceiptTypeSelected']);

                    $cashData['amount'] = $cuponInfoCreate['data']['couponAmount'];//쿠폰 금액
                }
            }
            else{
                $this->logger->error('coupon info create fail');
                throw new Exception($cuponInfoCreate['msg'],$cuponInfoCreate['code']);
            }

            //test data START
//            $cashData['cardNumber'] = '4119040003943202';
//            $cashData['expireMonth'] = '12';
//            $cashData['expireYear'] = '21';
//            $cashData['birthday'] = '940103';
//            $cashData['userName'] = '오정택';
//            $cashData['userMobileNumber'] = '010050958112';
//            $cashData['cardPw'] = '99';
//            $cashData['quota'] = '0';
            //test data END

            $barryPayup = new barryPayup($cashData, $this->memberId, $this->logger);
            //신용 카드 결제 처리
            if($cashData['paymentType'] == 'creditCard'){
                $creditCardReturn = $barryPayup->creditCard();
                if($creditCardReturn['code'] == 200){
                    // 정상 코드,
                    // 정상 상태로 넘어오면, 쿠폰 상태를 활성화와 활성화/만료 시간 빌드
                    //bcs_activation_datetime, bcs_deactivation_datetime

                    $activationDatetime = $util->getDateSql();
                    $deactivationDatetime = date("Y-m-d H:i:s", strtotime($activationDatetime.'+'.(int)$cuponInfoCreate['data']['couponTimeleft'].' days'));

                    $updateProc = $barrydb->createQueryBuilder()
                        ->update('barry_coupon_status')
                        ->set('bcs_status', '?')
                        ->set('bcs_activation_datetime', '?')
                        ->set('bcs_deactivation_datetime', '?')
                        ->where('bcs_uniq = ?')
                        ->setParameter(0,'activation')
                        ->setParameter(1,$activationDatetime)
                        ->setParameter(2,$deactivationDatetime)
                        ->setParameter(3,$cuponInfoCreate['data']['couponNumber'])
                        ->execute();
                    if(!$updateProc){
                        $this->logger->error('cupon activation proc fail');
                        throw new Exception('쿠폰 활성화 작업을 실패 하였습니다.',9999);
                    }

                }
                else{
                    $this->logger->error('coupon payment request fail');
                    throw new Exception($creditCardReturn['msg'],$creditCardReturn['code']);
                }

                $this->logger->alert('coupon payment complete/card info form data:'.$cashData['userMobileNumber']);
                return array('code' => 200, 'couponMsg' => '쿠폰 결제가 완료 되었습니다.');

            }//가상 계좌 결제 처리
            else{
                $virtualBankReturn = $barryPayup->virtualBank();
                if($virtualBankReturn['code'] != 200){
                    $this->logger->error('coupon payment virtualBank request fail');
                    throw new Exception($virtualBankReturn['msg'],$virtualBankReturn['code']);
                }
                $this->logger->alert('coupon payment complete/virtualBank info form data:'.$cashData['userMobileNumber']);
                return array('code' => 200, 'couponMsg' => '입금 가상 계좌 발급이 완료 되었습니다.', 'data' => $virtualBankReturn['data']);
            }

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

}

?>