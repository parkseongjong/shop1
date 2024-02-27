<?php
/*
 * Copyright (c) 2021. Barry
 */

namespace barry\payment;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class DanalPayup{

    /**
     * @var bool
     */
    private $data = false;
    /**
     * @var bool
     */
    private $memberId = false;
    /**
     * @var bool
     */
    private $logger = false;

    /**
     * DanalPayup constructor.
     * @param $postData
     * @param $memberId
     * @param $logger
     */
    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    //다날 페이업 인증결제

    /**
     * @return array
     */
    public function auth(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

//            $targetPostData = array(
//                'plainPassword' => 'stringNotEmpty',
//                'sellerAddress' => 'stringNotEmpty',
//                'orderPhone' => 'stringNotEmpty',
//                'orderId' => 'stringNotEmpty');
//            $filterData = array();
//            foreach($this->data as $key => $value){
//                if(array_key_exists($key,$targetPostData)){
//                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
//                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
//                }
//            }
//            unset($this->data, $targetPostData);// Plain data와 targetPostData는 unset 합니다.
            //$this->logger->info('필터 데이터:',$filterData);

            //hash('sha256', $input_url, false);
            //위변조 방지 서명 해시 알고리즘 순서는 다음과 같습니다...
            //가맹점아이디|오더넘버|어마운트|apikey|타임스탬프


            $loadPostData = array(
                'orderNumber' => '9999009999', //가맹정 생성 주문번호
                'amount' => '5000', //총 결제 금액
                'itemName' => 'test상품', //판매 상품명
                'userName' => '구매자이름', //구매자 이름
                'userAgent' => 'WP', //사용자 환경 mobile : WM , PC : WP
                'returnUrl' => 'http://local_barry/API/payup/payment/auth', // 완료 결과를 보내줄 url, (post로 쏴줌)
                'userEmail' => 'NULL', //사용자 이메일 필수 아님
                'signature' => hash('sha256',trim('oneheartsmart|9999009999|5000|095ec521316a4665b44d8abeb1b41e9a|20210112170304'),false), //위변조 방지 서명
                'timestamp' => '20210112170304', //타임스템프
            );
            $curlReturn = json_decode($util -> getCurlApi('https://api.testpayup.co.kr/ap/api/payment/oneheartsmart/order',$loadPostData),true);
            var_dump($curlReturn);

            echo('ok');
            exit();
            $this->logger->info('다날 페이업 auth 결제 요청');


            $returnArray = array(
                'test' => 'tttttt',
            );

            $this->logger->alert('hihihi...');

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

    //다날 페이업 카드결제

    /**
     * @return array|int[]
     */
    public function creditCard(){



        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            //카드 정보는 모두 String로 받음.
            $targetPostData = array(
                'orderNumber' => 'stringNotEmpty',
                'orderType' => 'stringNotEmpty',
                'cardNumber' => 'stringNotEmpty',
                'expireMonth' => 'stringNotEmpty',
                'expireYear' => 'stringNotEmpty',
                'birthDayYear' => 'string',
                'birthDayMonth' => 'string',
                'birthDayDay' => 'string',
                'companyNumber' =>'string',
                'userName' => 'stringNotEmpty',
                'userMobileNumber' => 'string',
                'cardPw' => 'stringNotEmpty',
                'quota' => 'stringNotEmpty',
                'amount' => 'stringNotEmpty');
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if ($targetPostData[$key] == 'integer') {
                        Assert::{$targetPostData[$key]}((int)$value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    } else {
                        Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            //targetPostData에 정의한 데이터가 filter 된 데이터에 없다면, 비정상적인 행위로 보고 예외 처리를 합니다.

            /*
             * birthday 변수 추가로.. 누락 여부 확인을 하지 않음.
            foreach ($targetPostData as $key => $value){
                if(!array_key_exists($key,$filterData)){
                    $this->logger->error('missing variable exists');
                    throw new Exception('누락 된 변수가 존재 합니다.'.$key,9999);
                }
            }
            */

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
                $this->logger->error('session id select error');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            $this->logger->info('주문 정보 빌드, 주문 타입 확인, 유효성 확인');
            //주문 번호 정보 빌드 쿠폰인지, 다른 타입 일 떄는 확장 필요.
            if($filterData['orderType'] == 'coupon') {

                $this->logger->info('주문 번호 정보 빌드');
                $orderNumberInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('barry_coupon_status')
                    ->where('bcs_uniq = ?')
                    ->setParameter(0, $filterData['orderNumber'])
                    ->execute()->fetch();
                if (!$orderNumberInfo) {
                    $this->logger->error('PG payment order id build fail!');
                    throw new Exception('PG payment 주문 번호 정보 빌드 실패!', 9999);
                }

                //리턴된 orderNumber에 타입 추가
                $orderNumberInfo['orderType'] = 'coupon';

                $this->logger->info('요청한 주문 건이 이미 결제 처리 되었는지?');
                $paymentInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('barry_pg_payment_status')
                    ->where('bpps_order_id = ?')
                    ->andWhere('bpps_status = ?')
                    ->setParameter(0, $orderNumberInfo['bcs_id'])
                    ->setParameter(1, 'complete')
                    ->execute()->fetch();
                if ($paymentInfo) {
                    $this->logger->error('fail! the duplicate check your order code');
                    throw new Exception('이미 결제 완료 된 결제 건 입니다.', 406);
                }

            }
            else if($filterData['orderType'] == 'item'){
                //카드결제 추가
                //g5_write_order 정보 조회
                $orderNumberInfo = $barrydb ->createQueryBuilder()
                    ->select('*')
                    ->from('g5_write_order')
                    ->where('od_number = ?')
                    ->setParameter(0,$filterData['orderNumber'])
                    ->execute()->fetch();
                if (!$orderNumberInfo) {
                    $this->logger->error('PG payment order id build fail!');
                    throw new Exception('PG payment 주문 번호 정보 빌드 실패!', 9999);
                }


                $itemInfo = $barrydb->createQueryBuilder()
                    ->select('wr_subject')
                    ->from('g5_write_'.$orderNumberInfo['wr_9'])
                    ->where('wr_id = ?')
                    ->setParameter(0,$orderNumberInfo['wr_1'])
                    ->execute()->fetch();
                if(!$itemInfo){
                    throw new Exception('유효하지 않은 상품 입니다.',9999);
                }

                $orderNumberInfo['orderType'] = 'item';
                //barry_pg_payment_status 결제 되었는지 다시 확인
                $paymentInfo = $barrydb ->createQueryBuilder()
                    ->select('*')
                    ->from('barry_pg_payment_status')
                    ->where('bpps_order_id = ?')
                    ->andWhere('bpps_payment_type = ?')
                    ->andWhere('bpps_status = ?')
                    ->setParameter(0,$orderNumberInfo['wr_id'])
                    ->setParameter(1,'creditCard')
                    ->setParameter(2,'complete')
                    ->execute()->fetch();
                if($paymentInfo) {
                    $this->logger->error('fail! the duplicate check your creditCard payment code/bpps_id'.$paymentInfo['bpps_id']);
                    throw new Exception('이미 결제 완료 된 결제 건 입니다.', 406);
                }

            }
            else{
                $this->logger->error('payment request, order type not vaild');
                throw new Exception('결제 요청 주문 타입이 유효하지 않습니다.', 9999);
            }

            $this->logger->info('카드 결제 정보 빌드!');

            $birthDayYearCut = substr($filterData['birthDayYear'],2,2);

            if(!isset($filterData['companyNumber'])){
                $filterData['birthday'] = $birthDayYearCut.$filterData['birthDayMonth'].$filterData['birthDayDay'];
            }
            else{
                $filterData['birthday'] = $filterData['companyNumber'];
            }

            //카드 결제 정보 빌드
            $paymentInfoBuild = array(
                'cardNumber' => 20,//카드번호 최대 20자리까지 자르기
                'expireMonth' => 2,
                'expireYear' => 2,
                'birthday' => 10,
                'cardPw' => 2);

            foreach ($filterData as $key => $value){
                if(array_key_exists($key,$paymentInfoBuild)){
                    $filterData[$key] = trim(substr($filterData[$key],0,$paymentInfoBuild[$key]));
                }
            }
            $timeSteamp = $util->getDateTime();


            //위변조 방지 서명 해시 전문 순서는 다음과 같습니다...
            //가맹점아이디|오더넘버|어마운트|apikey|타임스탬프
            //real 가맹점 ID : oneheartsmart
            //apiKey : 095ec521316a4665b44d8abeb1b41e9a

            unset($paymentInfoBuild);
            $loadPostData = array(

                'cardNo' => $filterData['cardNumber'], //카드번호
                'expireMonth' => $filterData['expireMonth'], //유효기간 월
                'expireYear' => $filterData['expireYear'], //유효기간 년
                'birthday' => $filterData['birthday'], //생년월일,사업자번호
                'cardPw' => $filterData['cardPw'], //카드 비밀번호
                'quota' => $filterData['quota'], // 할부기간

                'userName' => $filterData['userName'], //구매자 이름
                'mobileNumber' => $filterData['userMobileNumber'], //구매자 핸드폰번호 필수 아님
                'kakaoSend' => 'N', //카카오 알림톡 발송 필수 아님
                'userEmail' => '', //사용자 이메일 필수 아님
                'timestamp' => $timeSteamp, //타임스템프
            );

            if($filterData['orderType'] == 'coupon') {
                $loadPostData['orderNumber'] = $orderNumber = $orderNumberInfo['bcs_subject']; //판매 상품명
                $loadPostData['amount'] = $amount = $orderNumberInfo['bcs_price'];//쿠폰 금액
                $loadPostData['itemName'] = $orderNumberInfo['bcs_subject'];
                /*
                 * 'orderNumber' => $orderNumberInfo['bcs_uniq'], //가맹정 생성 주문번호
                 * 'amount' => $amount, //총 결제 금액
                 * 'itemName' => $orderNumberInfo['bcs_subject'], //판매 상품명
                 */
            }
            else if($filterData['orderType'] == 'item'){
                //상품 금액 build
                if($orderNumberInfo['io_id']){
                    $amount = ($orderNumberInfo['wr_ct_price'] + $orderNumberInfo['io_price']) * $orderNumberInfo['wr_6'];
                }
                else{
                    $amount = $orderNumberInfo['wr_ct_price'] * $orderNumberInfo['wr_6'];
                }

                $loadPostData['orderNumber'] = $orderNumber = $orderNumberInfo['od_number']; //판매 상품명
                $loadPostData['amount'] =(string) $amount;//상품 금액
                $loadPostData['itemName'] = $itemInfo['wr_subject'];
            }
            //$signature = hash('sha256',trim('free|'.$orderNumber.'|'.$amount.'|7a5db716382f4491b24da844d0173525|'.$timeSteamp),false);
            $signature = hash('sha256',trim('oneheartsmart|'.$orderNumber.'|'.$amount.'|095ec521316a4665b44d8abeb1b41e9a|'.$timeSteamp),false);
            $loadPostData['signature'] = $signature;
            unset($paymentInfo);
            $loadPostDataMasking = $loadPostData;
            //DB 저장 제외 할 card 정보 (exclusion card data)
            $temp = array('cardNo','expireMonth','expireYear','birthday','cardPw');
            foreach ($loadPostDataMasking as $key => $value){
                if(in_array($key,$temp)){
                    unset($loadPostDataMasking[$key]);
                }
            }
            unset($temp);

            $this->logger->info('결제 전 결제 정보 데이터 삽입!');
            $paymentBuilder = $barrydb->createQueryBuilder()
                ->insert('barry_pg_payment_status')
                ->setValue('bpps_order_id','?')
                ->setValue('bpps_order_type','?')
                ->setValue('bpps_pg_type','?')
                ->setValue('bpps_payment_type','?')
                ->setValue('bpps_item_name','?')
                ->setValue('bpps_user_name','?')
                ->setValue('bpps_number','?')
                ->setValue('bpps_amount','?')
                ->setValue('bpps_installment','?')
                ->setValue('bpps_status','?')
                ->setValue('bpps_request','?')
                ->setValue('bpps_request_datetime','?');
            if($filterData['orderType'] == 'coupon') {
                $paymentProc = $paymentBuilder
                    ->setParameter(0,$orderNumberInfo['bcs_id'])
                    ->setParameter(1,$orderNumberInfo['orderType'])
                    ->setParameter(2,'payup')
                    ->setParameter(3,'creditCard')
                    ->setParameter(4,$orderNumberInfo['bcs_subject'])
                    ->setParameter(5,$filterData['userName'])
                    ->setParameter(6,$filterData['userMobileNumber'])
                    ->setParameter(7,$amount)
                    ->setParameter(8,$filterData['quota'])
                    ->setParameter(9,'wait')
                    ->setParameter(10,json_encode($loadPostDataMasking,JSON_UNESCAPED_UNICODE))
                    ->setParameter(11,$util->getDateSql())
                    ->execute();
            }
            else if($filterData['orderType'] == 'item'){
                $paymentProc = $paymentBuilder
                    ->setParameter(0,$orderNumberInfo['wr_id'])
                    ->setParameter(1,$orderNumberInfo['orderType'])
                    ->setParameter(2,'payup')
                    ->setParameter(3,'creditCard')
                    ->setParameter(4,$itemInfo['wr_subject'])
                    ->setParameter(5,$filterData['userName'])
                    ->setParameter(6,$filterData['userMobileNumber'])
                    ->setParameter(7,$amount)
                    ->setParameter(8,$filterData['quota'])
                    ->setParameter(9,'wait')
                    ->setParameter(10,json_encode($loadPostDataMasking,JSON_UNESCAPED_UNICODE))
                    ->setParameter(11,$util->getDateSql())
                    ->execute();
            }

            if(!$paymentProc){
                $this->logger->error('PG payment info build fail');
                throw new Exception('결제 전 결제 정보 데이터 삽입을 실패 하였습니다.',9999);
            }
            unset($loadPostDataMasking);
            $paymentId = $barrydb->lastInsertId();

            //주문 번호 정보 테이블에 결제 정보 고유 ID 업데이트, 다른 타입 일 떄는 확장 필요.
            if($filterData['orderType'] == 'coupon') {
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_coupon_status')
                    ->set('bpps_id','?')
                    ->where('bcs_id = ?')
                    ->setParameter(0,$paymentId)
                    ->setParameter(1,$orderNumberInfo['bcs_id'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('from order number table is payment id update fail!');
                    throw new Exception('주문 번호 정보 테이블에 결제 정보 고유 id 업데이트를 실패 하였습니다.',9999);
                }
            }

            $this->logger->info('다날 페이업 creditCard 결제 요청');
            $curlReturn = json_decode($util -> getCurlApi('https://api.payup.co.kr/v2/api/payment/oneheartsmart/keyin2',$loadPostData),true);
            //$curlReturn = json_decode($util -> getCurlApi('https://api.testpayup.co.kr/v2/api/payment/free/keyin2',$loadPostData),true); // (test)

//            var_dump($util -> getCurlApi('https://api.testpayup.co.kr/v2/api/payment/free/keyin2',$loadPostData));

            if($curlReturn['responseCode'] == '0000' ){
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status', '?')
                    ->set('bpps_response', '?')
                    ->set('bpps_response_datetime', '?')
                    ->set('bpps_transaction_id', '?')
                    ->set('bpps_auth_number', '?')
                    ->where('bpps_id = ?')
                    ->setParameter(0, 'complete')
                    ->setParameter(1, json_encode($curlReturn,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2, $util->getDateSql())
                    ->setParameter(3, $curlReturn['transactionId'])
                    ->setParameter(4, $curlReturn['authNumber'])
                    ->setParameter(5, $paymentId)
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('PG response update fail[ok]/ paymentID:'.$paymentId);
                    throw new Exception('PG 사 결제 요청 결과 업데이트를 실패 하였습니다.',9999);
                }
            }
            else{
                $this->logger->error('PG payment request fail/'.$paymentId);

                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status', '?')
                    ->set('bpps_response', '?')
                    ->set('bpps_response_datetime', '?')
                    ->where('bpps_id = ?')
                    ->setParameter(0, 'fail')
                    ->setParameter(1, json_encode($curlReturn,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2, $util->getDateSql())
                    ->setParameter(3, $paymentId)
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('PG response update fail/ paymentID:'.$paymentId);
                    throw new Exception('PG 사 결제 요청 결과 업데이트를 실패 하였습니다.',9999);
                }

                //PG사 응답 코드별 익셉션
                if(self::responseCode($curlReturn['responseCode']) !== false){
                    $temp = self::responseCode($curlReturn['responseCode']);
                    $this->logger->error($temp['en'].$temp['innerCode']);
                    throw new Exception($temp['ko'],$temp['innerCode']);
                }

                throw new Exception('PG 사에 결제 요청을 실패 하였습니다.',9999);
            }

//            $returnArray = array(
//                'testname' => '오정택~',
//            );

            $this->logger->alert('다날 페이업 creditCard 결제 완료./ paymentID:'.$paymentId);

            //return array('code' => 200, 'data' => $returnArray);
            return array('code' => 200);
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

    //다날 페이업 가상계좌 결제

    /**
     * @return array
     */
    public function virtualBank(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            //정보는 모두 String로 받음.
            $targetPostData = array(
                'orderNumber' => 'stringNotEmpty',
                'orderType' => 'stringNotEmpty',
                'bankCode' => 'stringNotEmpty',
                'cashReceiptUse' => 'stringNotEmpty',
                'cashReceiptType' => 'string',
                'cashReceiptNo' => 'string',
                'userMobileNumber' => 'stringNotEmpty'
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if ($targetPostData[$key] == 'integer') {
                        Assert::{$targetPostData[$key]}((int)$value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    } else {
                        Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            //targetPostData에 정의한 데이터가 filter 된 데이터에 없다면, 비정상적인 행위로 보고 예외 처리를 합니다.
            foreach ($targetPostData as $key => $value){
                if(!array_key_exists($key,$filterData) && $value == 'stringNotEmpty'){
                    $this->logger->error('[virtualBank]missing variable exists');
                    throw new Exception('[virtualBank]누락 된 변수가 존재 합니다.',9999);
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
                $this->logger->error('[virtualBank]session id select error');
                throw new Exception('[virtualBank]세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            //이미 발급한 가상계좌가 있는지 확인 (만료된 경우는 재 발급 가능.)
            $this->logger->info('이미 발급 한 가상계좌가 있는지?');
            $couponInfo = $barrydb->createQueryBuilder()
                            ->select('*')
                            ->from('barry_coupon_status', 'A')
                            ->innerJoin('A','barry_pg_payment_status', 'B', 'A.bpps_id = B.bpps_id')
                            ->where('bcs_mb_id = ?')
                            ->andWhere('bpps_payment_type = ?')
                            ->andWhere('bpps_status = ?')
                            ->andWhere('bpps_bank_expire_datetime >= ?')
                            ->setParameter(0, $memberInfo['mb_no'])
                            ->setParameter(1, 'virtualBank')
                            ->setParameter(2, 'wait')
                            ->setParameter(3, $util->getDateSql())
                            ->execute()->fetch();
            if($couponInfo){
                $this->logger->error('[virtualBank]fail! the duplicate check your account number');
                throw new Exception('이미 발급 된 가상 계좌 번호가 존재 합니다.',406);
            }

            $this->logger->info('주문 정보 빌드, 주문 타입 확인, 유효성 확인');
            //주문 번호 정보 빌드 쿠폰인지, 다른 타입 일 떄는 확장 필요.
            if($filterData['orderType'] == 'coupon') {

                $this->logger->info('주문 번호 정보 빌드');
                $orderNumberInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('barry_coupon_status')
                    ->where('bcs_uniq = ?')
                    ->setParameter(0, $filterData['orderNumber'])
                    ->execute()->fetch();
                if (!$orderNumberInfo) {
                    $this->logger->error('[virtualBank]PG payment order id build fail!');
                    throw new Exception('[virtualBank] PG payment 주문 번호 정보 빌드 실패!', 9999);
                }

                //리턴된 orderNumber에 타입 추가
                $orderNumberInfo['orderType'] = 'coupon';

                $this->logger->info('요청한 주문 건이 이미 결제 처리 되었는지?');
                $paymentInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('barry_pg_payment_status')
                    ->where('bpps_order_id = ?')
                    ->andWhere('bpps_status = ?')
                    ->setParameter(0, $orderNumberInfo['bcs_id'])
                    ->setParameter(1, 'complete')
                    ->execute()->fetch();
                if ($paymentInfo) {
                    $this->logger->error('[virtualBank]fail! the duplicate check your order code');
                    throw new Exception('[virtualBank] 이미 결제 완료 된 결제 건 입니다.', 406);
                }

            }
            else{
                $this->logger->error('[virtualBank] payment request, order type not vaild');
                throw new Exception('[virtualBank] 결제 요청 주문 타입이 유효하지 않습니다.', 9999);
            }


            $this->logger->info('가상 계좌 정보 빌드!');
            //가상 계좌 정보 빌드
            $virBankInfoBuild = array(
                'bankCode' => 4,
                'cashReceiptUse' => 1,
                'cashReceiptType' => 1,

            );

            foreach ($filterData as $key => $value){
                if(array_key_exists($key,$virBankInfoBuild)){
                    $filterData[$key] = trim(substr($filterData[$key],0,$virBankInfoBuild[$key]));
                }
            }

            $timeSteamp = $util->getDateTime();
            $virBankdeactivationDatetime = date("YmdHis", strtotime($timeSteamp.'+'.((int)3).' days'));

            //지금은 단일 결제만..!
            $amount = $orderNumberInfo['bcs_price'];
            //위변조 방지 서명 해시 전문 순서는 다음과 같습니다...
            //가맹점아이디|오더넘버|어마운트|apikey|타임스탬프
            //real 가맹점 ID : oneheartsmart
            //apiKey : eb4b056ce32642ecac7fe6691e7791c9
            $signature = hash('sha256',trim('oneheartsmart|'.$orderNumberInfo['bcs_uniq'].'|'.$amount.'|eb4b056ce32642ecac7fe6691e7791c9|'.$timeSteamp),false);
            //$signature = hash('sha256',trim('apitest|'.$orderNumberInfo['bcs_uniq'].'|'.$amount.'|b0b05aabde674ad09a0f56c131bb3314|'.$timeSteamp),false);

            $loadPostData = array(
                'orderNumber' => $orderNumberInfo['bcs_uniq'], //가맹정 생성 주문번호
                'signature' => $signature,
                'timestamp' => $timeSteamp, //타임스템프
                'amount' => $amount, //총 결제 금액
                'itemName' => $orderNumberInfo['bcs_subject'], //판매 상품명
                'userName' => $memberInfo['mb_name'], //구매자 이름

                'bankCode' => $filterData['bankCode'],// 입금은행 뱅크 코드
                'expireDate' => $virBankdeactivationDatetime, //입금 마감 일시
                'cashUseFlag' => $filterData['cashReceiptUse'], //현금영수증 발급 여부
                'cashType' => $filterData['cashReceiptType'], //현금영수증 구분 필수 아님
                'cashNo' => $filterData['cashReceiptNo'], //현금영수증 번호 필수 아님

                'mobileNumber' => $filterData['userMobileNumber'], //구매자 핸드폰번호 필수 아님
                'kakaoSend' => 'N', //카카오 알림톡 발송 필수 아님
                'userEmail' => '', //사용자 이메일 필수 아님
            );

            $this->logger->info('[virtualBank]결제 전 결제 정보 데이터 삽입!');
            $paymentProc = $barrydb->createQueryBuilder()
                ->insert('barry_pg_payment_status')
                ->setValue('bpps_order_id','?')
                ->setValue('bpps_order_type','?')
                ->setValue('bpps_pg_type','?')
                ->setValue('bpps_payment_type','?')
                ->setValue('bpps_item_name','?')
                ->setValue('bpps_user_name','?')
                ->setValue('bpps_number','?')
                ->setValue('bpps_amount','?')
                ->setValue('bpps_installment','?')
                ->setValue('bpps_status','?')
                ->setValue('bpps_request','?')
                ->setValue('bpps_request_datetime','?')
                ->setValue('bpps_cash_use','?')
                ->setValue('bpps_cash_type','?')
                ->setValue('bpps_cash_no','?')
                ->setParameter(0,$orderNumberInfo['bcs_id'])
                ->setParameter(1,$orderNumberInfo['orderType'])
                ->setParameter(2,'payup')
                ->setParameter(3,'virtualBank')
                ->setParameter(4,$orderNumberInfo['bcs_subject'])
                ->setParameter(5,$memberInfo['mb_name'])
                ->setParameter(6,$filterData['userMobileNumber'])
                ->setParameter(7,$amount)
                ->setParameter(8,0)
                ->setParameter(9,'wait')
                ->setParameter(10,json_encode($loadPostData,JSON_UNESCAPED_UNICODE))
                ->setParameter(11,$util->getDateSql())
                ->setParameter(12,$filterData['cashReceiptUse'])
                ->setParameter(13,$filterData['cashReceiptType'])
                ->setParameter(14,$filterData['cashReceiptNo'])
                ->execute();
            if(!$paymentProc){
                $this->logger->error('[virtualBank] PG payment info build fail');
                throw new Exception('[virtualBank] 결제 전 결제 정보 데이터 삽입을 실패 하였습니다.',9999);
            }
            unset($loadPostDataMasking);
            $paymentId = $barrydb->lastInsertId();

            //주문 번호 정보 테이블에 결제 정보 고유 ID 업데이트, 다른 타입 일 떄는 확장 필요.
            $updateProc = $barrydb->createQueryBuilder()
                ->update('barry_coupon_status')
                ->set('bpps_id','?')
                ->where('bcs_id = ?')
                ->setParameter(0,$paymentId)
                ->setParameter(1,$orderNumberInfo['bcs_id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('[virtualBank] from order number table is payment id update fail!');
                throw new Exception('[virtualBank] 주문 번호 정보 테이블에 결제 정보 고유 id 업데이트를 실패 하였습니다.',9999);
            }

            $this->logger->info('다날 페이업 virtual bank 결제 요청');
            $curlReturn = json_decode($util -> getCurlApi('https://api.payup.co.kr/va/api/payment/oneheartsmart/issue',$loadPostData),true);
            //$curlReturn = json_decode($util -> getCurlApi('https://api.testpayup.co.kr/va/api/payment/apitest/issue',$loadPostData),true); // (test)

            if($curlReturn['responseCode'] == '0000' ){
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status', '?')
                    ->set('bpps_response', '?')
                    ->set('bpps_response_datetime', '?')
                    ->set('bpps_bank_holder', '?')
                    ->set('bpps_bank_name', '?')
                    ->set('bpps_bank_account', '?')
                    ->set('bpps_bank_expire_datetime', '?')
                    ->set('bpps_bank_activation_datetime', '?')
                    ->set('bpps_transaction_id', '?')
                    ->where('bpps_id = ?')
                    ->setParameter(0, 'wait')//입금 처리 전에는 wait 상태
                    ->setParameter(1, json_encode($curlReturn,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2, $util->getDateSql())
                    ->setParameter(3, $curlReturn['accountHolder'])
                    ->setParameter(4, $curlReturn['bankName'])
                    ->setParameter(5, $curlReturn['account'])
                    ->setParameter(6, $util->getSqlDateInNotIsoUnixDateTimeConvert($curlReturn['expireDate']))
                    ->setParameter(7, $util->getSqlDateInNotIsoUnixDateTimeConvert($curlReturn['issueDate']))
                    ->setParameter(8, $curlReturn['transactionId'])
                    ->setParameter(9, $paymentId)
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('[virtualBank] PG response update fail[ok]');
                    throw new Exception('[virtualBank] PG 사 결제 요청 결과 업데이트를 실패 하였습니다.',9999);
                }
            }
            else{
                $this->logger->error('[virtualBank] PG payment request fail/'.$paymentId);

                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status', '?')
                    ->set('bpps_response', '?')
                    ->set('bpps_response_datetime', '?')
                    ->where('bpps_id = ?')
                    ->setParameter(0, 'fail')
                    ->setParameter(1, json_encode($curlReturn,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2, $util->getDateSql())
                    ->setParameter(3, $paymentId)
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('[virtualBank] PG response update fail');
                    throw new Exception('[virtualBank] PG 사 결제 요청 결과 업데이트를 실패 하였습니다.',9999);
                }

                //PG사 응답 코드별 익셉션
                if(self::responseCode($curlReturn['responseCode']) !== false){
                    $temp = self::responseCode($curlReturn['responseCode']);
                    $this->logger->error($temp['en']);
                    throw new Exception($temp['ko'],$temp['innerCode']);
                }

                throw new Exception('[virtualBank] PG 사에 결제 요청을 실패 하였습니다.',9999);
            }

            $returnArray = array(
                'accountHolder' => $curlReturn['accountHolder'],
                'account' => $curlReturn['account'],
                'bankName' => $curlReturn['bankName'],
                'amount' => $curlReturn['amount'],
                'expireDate' => $curlReturn['expireDate'],
                'expireDateConvert' => $util->getSqlDateInNotIsoUnixDateTimeConvert($curlReturn['expireDate']),
                'issueDate' => $curlReturn['issueDate'],
                'issueDateConvert' => $util->getSqlDateInNotIsoUnixDateTimeConvert($curlReturn['issueDate']),
            );

            $this->logger->alert('danalPayup virtualBank account generate complete');

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

    //다날 페이업 가상계좌 입금 확인
    /**
     * @return array
     */
    public function virtualBankReturn(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            //정보는 모두 String로 받음.
            $targetPostData = array(
                'RESULT' => 'string',
                'STATUS_CODE' => 'stringNotEmpty',
                'TRANSACTION_ID' => 'stringNotEmpty',
                'MERCHANT_ID' => 'stringNotEmpty',
                'ORDER_NUMBER' => 'string',
                'RESPONSE_MSG' => 'stringNotEmpty',
                'RESPONSE_CODE' => 'stringNotEmpty',
                'DEPOSIT_DATETIME' => 'string',
                'DEPOSITOR' => 'string',
                'AMOUNT' => 'string',
                'ACCOUNT' => 'string',
                'CASH_AUTH_NO' => 'string'
            );

            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if ($targetPostData[$key] == 'integer') {
                        Assert::{$targetPostData[$key]}((int)$value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    } else {
                        Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            //targetPostData에 정의한 데이터가 filter 된 데이터에 없다면, 비정상적인 행위로 보고 예외 처리를 합니다.
            foreach ($targetPostData as $key => $value){
                if(!array_key_exists($key,$filterData) && $value == 'stringNotEmpty'){
                    $this->logger->error('[virtualBank]missing variable exists');
                    throw new Exception('[virtualBank]누락 된 변수가 존재 합니다.',9999);
                }
            }

            $this->logger->alert('[virtualBankReturn]RESPONSE/'.json_encode($filterData,JSON_UNESCAPED_UNICODE));

            if($filterData['MERCHANT_ID']!='oneheartsmart'){
                $this->logger->error('[virtualBankReturn] MERCHANT ID FAILL');
                throw new Exception('[virtualBankReturn] 페이업 가맹점 아이디가 일치 하지 않습니다.', 9999);
            }

            //정상 코드라면, 거래번호,주문 번호가 일치 하는지 확인.
            //금액이 완전 일치했을 떄 오는지 ? 아니면 일치하지 않아도 오는지 확인 필요.
            //지금은 쿠폰만 구현 되어 있지만, 추 후 확장 시 트랜잭션 ID와 오더 ID가 쿠폰인지 추가 된 확장 타입 인지 확인 후 리턴 해줘야 함.
            if($filterData['STATUS_CODE'] == '2001'){
                $virtualBankReturnInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('barry_pg_payment_status', 'A')
                    ->innerJoin('A','barry_coupon_status','B','A.bpps_id = B.bpps_id')
                    ->where('bcs_uniq = ?')
                    ->andWhere('bpps_transaction_id = ?')
                    ->andWhere('bpps_status = ?')
                    ->setParameter(0, $filterData['ORDER_NUMBER'])
                    ->setParameter(1, $filterData['TRANSACTION_ID'])
                    ->setParameter(2, 'wait')
                    ->execute()->fetch();
                if (!$virtualBankReturnInfo) {
                    $this->logger->error('[virtualBankReturn] TRANSACTION ID, ORDER NUMBER FAILL');
                    throw new Exception('[virtualBankReturn] 거래번호, 주문 번호가 일치 하지 않습니다.', 9999);
                }

                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status', '?')
                    ->set('bpps_deposit_response', '?')
                    ->set('bpps_deposit_datetime', '?')
                    ->set('bpps_response_datetime', '?')
                    ->set('bpps_deposit_amount', '?')
                    ->set('bpps_cash_auth_no', '?')
                    ->where('bpps_order_id = ?')
                    ->andWhere('bpps_transaction_id = ?')
                    ->setParameter(0, 'complete')//입금 처리 전에는 wait 상태
                    ->setParameter(1, json_encode($filterData,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2, $filterData['DEPOSIT_DATETIME'])
                    ->setParameter(3, $util->getDateSql())
                    ->setParameter(4, $filterData['AMOUNT'])
                    ->setParameter(5, $filterData['CASH_AUTH_NO'])
                    ->setParameter(6, $virtualBankReturnInfo['bpps_order_id'])
                    ->setParameter(7, $virtualBankReturnInfo['bpps_transaction_id'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('[virtualBank] PG response update fail[ok]');
                    throw new Exception('[virtualBank] PG 사 결제 요청 결과 업데이트를 실패 하였습니다.',9999);
                }

                $activationDatetime = $util->getDateSql();
                $deactivationDatetime = date("Y-m-d H:i:s", strtotime($activationDatetime.'+'.(int)$virtualBankReturnInfo['bcs_timeleft'].' days'));

                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_coupon_status')
                    ->set('bcs_status', '?')
                    ->set('bcs_activation_datetime', '?')
                    ->set('bcs_deactivation_datetime', '?')
                    ->where('bcs_uniq = ?')
                    ->setParameter(0,'activation')
                    ->setParameter(1,$activationDatetime)
                    ->setParameter(2,$deactivationDatetime)
                    ->setParameter(3,$virtualBankReturnInfo['bcs_uniq'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('[virtualBank] cupon activation proc fail');
                    throw new Exception('[virtualBank] 쿠폰 활성화 작업을 실패 하였습니다.',9999);
                }


            }//2009: 계좌발급취소 9001: 입금취소
            else{

                $virtualBankReturnInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('barry_pg_payment_status', 'A')
                    ->innerJoin('A','barry_coupon_status','B','A.bpps_id = B.bpps_id')
                    ->where('bcs_uniq = ?')
                    ->andWhere('bpps_transaction_id = ?')
                    ->andWhere('bpps_status = ?')
                    ->setParameter(0, $filterData['ORDER_NUMBER'])
                    ->setParameter(1, $filterData['TRANSACTION_ID'])
                    ->setParameter(2, 'wait')
                    ->execute()->fetch();
                if (!$virtualBankReturnInfo) {
                    $this->logger->error('[virtualBankReturn] TRANSACTION ID, ORDER NUMBER FAILL');
                    throw new Exception('[virtualBankReturn] 거래번호, 주문 번호가 일치 하지 않습니다.', 9999);
                }

                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_pg_payment_status')
                    ->set('bpps_status', '?')
                    ->set('bpps_deposit_response', '?')
                    ->set('bpps_deposit_datetime', '?')
                    ->set('bpps_cancel_datetime', '?')
                    ->set('bpps_response_datetime', '?')
                    ->set('bpps_deposit_amount', '?')
                    ->set('bpps_cash_auth_no', '?')
                    ->where('bpps_order_id = ?')
                    ->andWhere('bpps_transaction_id = ?')
                    ->setParameter(0, 'cancel')//입금 처리 전에는 wait 상태
                    ->setParameter(1, json_encode($filterData,JSON_UNESCAPED_UNICODE))
                    ->setParameter(2, $filterData['DEPOSIT_DATETIME'])
                    ->setParameter(3, $util->getDateSql())
                    ->setParameter(4, $util->getDateSql())
                    ->setParameter(5, $filterData['AMOUNT'])
                    ->setParameter(6, $filterData['CASH_AUTH_NO'])
                    ->setParameter(7, $virtualBankReturnInfo['bpps_order_id'])
                    ->setParameter(8, $virtualBankReturnInfo['bpps_transaction_id'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('[virtualBank] PG response update fail[ok]');
                    throw new Exception('[virtualBank] PG 사 결제 요청 결과 업데이트를 실패 하였습니다.',9999);
                }

            }

            $returnArray = array(
                'payupReturnCode' => 'KPVA0000',
            );

            $this->logger->alert('danalPayup virtualBankReturn complete.');

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

    //응답코드에 따라 ko,en 응답 메시지, 코드 셋팅
    //innerCode는 내부 처리용 코드 입니다,.
    /**
     * @param $code
     * @return array|bool|float|int|mixed|\stdClass|string|null
     */
    private function responseCode($code){

        $codeArray = array(
            1001 =>'{"ko":"등록 되지 않은 서비스 아이디 입니다.","en":"PG Not register service id","innerCode":9999}',
            1002 =>'{"ko":"제휴사 프로세스 생성 오류.","en":"PG Proc create error":9999}',
            1003 =>'{"ko":"정산 대상 금액 부족으로 취소가 불가 합니다.","en":"PG Proc create error","innerCode":9999}',
            1004 =>'{"ko":"취소 대상건이 없습니다.","en":"PG Not found target cancel","innerCode":9999}',
            1005 =>'{"ko":"취소 가능 상태가 아닙니다.","en":"PG this status not cancel","innerCode":9999}',
            1006 =>'{"ko":"취소 업데이트 에러!","en":"PG cancel update error","innerCode":9999}',
            2001 =>'{"ko":"전문 검증 에러","en":"PG telegram validation error","innerCode":9999}',
            5001 =>'{"ko":"결제 금액이 최소 결제 금액 보다 작습니다.","en":"PG the payment amount is less than the min payment amount","innerCode":9999}',
            5002 =>'{"ko":"결제 금액이 최대 결제 금액 보다 큽니다.","en":"PG the payment amount is higher than the max payment amount","innerCode":9999}',
            5003 =>'{"ko":"가맹점 일 한도 초과!.","en":"PG 가맹점 일 한도 초과!","innerCode":9999}',
            5004 =>'{"ko":"가맹점 월 한도 초과!","en":"PG 가맹점 월 한도 초과!","innerCode":9999}',
            5005 =>'{"ko":"이행보증보험 한도 초과!","en":"PG 이행보증보험 한도 초과!","innerCode":9999}',
            5006 =>'{"ko":"카드 할부가능 개월수가 초과되었습니다. 카드할부 변경문의는 페이업(1644–1017)으로 연락 부탁드립니다.","en":"The number of months available for card installment has been exceeded. For card installment changes, please contact Payup (1644-1017).","innerCode":5006}',
            6001 =>'{"ko":"파라미터명은 필수 입니다.","en":"PG 파라미터명은 필수 입니다.","innerCode":9999}',
            6002 =>'{"ko":"파라미터명이 잘못 되었습니다.","en":"PG 파라미터명이 잘못 되었습니다.","innerCode":6002}',
            6003 =>'{"ko":"파라미터를 확인해주세요.","en":"PG 파라미터를 확인해주세요.","innerCode":9999}',
            6004 =>'{"ko":"관리자에게 문의 해주세요.","en":"PG 관리자에게 문의 해주세요.","innerCode":9999}',
            1157 =>'{"ko":"지원하지 않거나 등록되지 않은 카드사입니다.","en":"CARD this card or corp is not approved","innerCode":1157}',
            1301 =>'{"ko":"승인 거절, 서비스 불가 카드 입니다.","en":"CARD this card is not approved","innerCode":1301}',
            3100 =>'{"ko":"유효기간이 만료 되었습니다.","en":"CARD card expiration date","innerCode":3100}',
            3101 =>'{"ko":"할부 개월 오류입니다.","en":"Installment month error.","innerCode":3101}',
            3192 =>'{"ko":"유효한 카드번호가 아닙니다","en":"CARD card not a valid number","innerCode":3192}',
            3110 =>'{"ko":"카드번호 오류 입니다.","en":"CARD card password error","innerCode":3110}',
            3115 =>'{"ko":"카드 비밀번호가 일치하지 않습니다. 3회 초과 시 카드사에 문의 바랍니다.","en":"CARD card password error","innerCode":3115}',
            3128 =>'{"ko":"거래정지 카드입니다.","en":"Suspension card.","innerCode":3128}',
            3133 =>'{"ko":"잔액이 부족합니다.","en":"The balance is insufficient.","innerCode":3133}',
            3223 =>'{"ko":"비밀번호 입력 횟수 3회 초과 오류입니다. 카드사 문의 바랍니다.","en":"CARD you reached the maximum password input attempts please contact your credit card corp","innerCode":3223}',
			3102 =>'{"ko":"할부 금액 오류 입니다.","en":"CARD installment payment error","innerCode":3102}',
            3112 =>'{"ko":"한도가 초과 하였습니다.","en":"CARD limit has been exceeded.","innerCode":3112}',
            3119 =>'{"ko":"유효기간 오류 입니다.","en":"CARD expiration date error.","innerCode":3119}',
            3217 =>'{"ko":"인증 정보 오류 입니다.(예) 생년월일 등 개인정보를 확인해주세요.","en":"Authentication information error","innerCode":3217}',
            3143 =>'{"ko":"카드사 문의 바랍니다.","en":"Please contact the credit card company.","innerCode":3143}',
            8484 =>'{"ko":"카드 유효기간이 만료 되었습니다..","en":"Card Number exp error","innerCode":8484}'
        );

        if(array_key_exists((int)$code,$codeArray)){
            return json_decode($codeArray[(int)$code],true);
        }
        else{
            return false;
        }
    }

}
?>