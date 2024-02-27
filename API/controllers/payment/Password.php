<?php

namespace barry\payment;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\order\Util as barryOrderUtil;
use \barry\db\DriverApi as barryDb;
use \barry\encrypt\RsaApi as barryRsa;
use \InvalidArgumentException;
use \Exception;

class Password{
    
    private $data = false;
    private $memberId = false;
    private $logger = false;
    
    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }
    
    public function process(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $orderUtil = barryOrderUtil::singletonMethod();
            $barrydb = $db-> init();
            $barryRsa = new barryRsa;
            /*
                eTokenType 레거시에서는 mc,tp3로 eToken 타입이 날아옵니다.

            */
            $targetPostData = array(
                'plainPassword' => 'stringNotEmpty',
                'sellerAddress' => 'stringNotEmpty',
                'orderPhone' => 'stringNotEmpty',
                'orderId' => 'stringNotEmpty');
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data, $targetPostData);// Plain data와 targetPostData는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);

            /*
                TO-DO
                
                프로토 타입: validate는 최초 유입되는 값만 체크
                추 후 : validate는 db로 조회된 값을 사용 전에도 체크 ?? 굳이..?
                
                0. validate 사용
                0. XSS 필터링
                0. CURL 함수 만들기.. common에..
                1. 세션 아이디 값으로 회원 정보 조회
                1-1. 판매자 월렛주소 유효한지 확인
                2. 비밀번호 암호화 (프론트에서 공개키 암호화 하면, 여기선 복호화 하고 다시 인크립션)
                3. 사이버트론에 회원정보 조회,
                4. 결제 요청 전 오더 내용 빌드
                5. 사이버트론에 비밀번호 값 조회
                6. 결제 요청
                7. 결제 요청까지 완료 되었다면 최종 정상 리턴
            
            */
           
            //BARRY 세션 id 값 과 날아온 orderPhone(member id 값과) 일치 하는지 조회
            /*
                barry member table columns comment
                mb_1: sellerAddress (virtual_wallet_address)
                mb_2: cybertron member id value 
                mb_3: ckey
            */
            $this->logger->info('세션 값과 주문자 id 값 일치하니?');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->andWhere('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->setParameter(1, $filterData['orderPhone'])
                ->execute()->fetchAll();
            if(!$memberInfo){
                $this->logger->error('session id select error');
                throw new Exception('세션 값과 주문자 id 값이 일치하지 않습니다.',9999);
            }

            //BARRY 판매자 월렛이 유효한가?
            $this->logger->info('판매자 월렛 유효하니?');
            $sellerInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_1 = ?')
                ->setParameter(0, $filterData['sellerAddress'])
                ->execute()->fetchAll();
            if(!$sellerInfo){
                $this->logger->error('password process error');
                throw new Exception('판매자 월렛이 유효하지 않습니다..',9999);
            }
            
            //사이버트론에 회원 정보 조회
            //ckey : barry auth, kind : api response type, user_id : cybertron member id value 
            $loadPostData = array(
                'ckey' => $memberInfo[0]['mb_3'],
                'kind' => 'check',
                'user_id' => $memberInfo[0]['mb_2']
            );
            $this->logger->info('사이버트론 회원 정보 조회');
            $curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis.php',$loadPostData),true);

            if(!$curlReturn || $curlReturn['code'] != '00'){
                $this->logger->error('cybertron member select error/'.$memberInfo[0]['mb_id'].'/'.$loadPostData['ckey'].'/'.$loadPostData['kind'].'/'.$loadPostData['user_id']);
                throw new Exception('사이버트론 회원 정보와 일치하지 않습니다.',9999);
            }

            //결제 요청 전 오더 내용 빌드
            // AS-IS: 리턴된 wr_id(오더 id)값과 검색 된 구매자 id, 결제가 완료가 아닌 경우 info를 가져온다.
            // TO-BE: 리턴된 wr_id(오더 id)값과 검색 된 구매자 id, waitPayment만 가져온다.
            $this->logger->info('주문자 오더가 유효하니?');
            $orderInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_order')
                ->where('wr_id = ?')
                ->andWhere('mb_id = ?')
                ->andWhere('wr_10 = ?')
                ->setParameter(0, $filterData['orderId'])
                ->setParameter(1, $memberInfo[0]['mb_id'])
                ->setParameter(2, 'waitPayment')
                ->execute()->fetchAll();
            if(!$orderInfo){
                $this->logger->error('order info select error/'.$memberInfo[0]['mb_id'].'/'.$filterData['orderId']);
                throw new Exception('이미 결제 처리 되었거나 주문자 오더가 유효하지 않습니다.',20);
            }

            //결제 요청 된 오더의 item(goods)가 삭제(품절,일시정지) 처리 된 경우 라면 결제를 허용하지 않는다.
            //ex) 오더 처리 후 상품이 정상이 아니더라도 결제 안함.
            /*
             *
             * 레거시 item은 카테고리별로 db가 분리 되어 있습니다.. 그래서 불가피하게 join을 해서 추적합니다.
             * 레거시 order은 item id 값이 wr_1에 저장 되어 있습니다.
             */
            $this->logger->info('주문자 오더에 연결된 item이 판매가 가능하니?');
            $orderItemValid = $barrydb->createQueryBuilder()
                ->select('B.del_yn, B.it_soldout, B.it_option_subject')
                ->from('g5_write_order', 'A')
                ->innerJoin('A', 'g5_write_'.$orderInfo[0]['wr_9'], 'B', 'A.wr_1 = B.wr_id')
                ->where('A.wr_id = ?')
                ->andWhere('A.mb_id = ?')
                ->andWhere('A.wr_10 = ?')
                ->setParameter(0, $orderInfo[0]['wr_id'])
                ->setParameter(1, $memberInfo[0]['mb_id'])
                ->setParameter(2, 'waitPayment')
                ->execute()->fetchAll();
            if($orderItemValid[0]['del_yn'] == 'Y' || $orderItemValid[0]['it_soldout'] == 1){
                $this->logger->error('order connect item info error/'.$memberInfo[0]['mb_id'].'/'.$orderInfo[0]['wr_id']);
                throw new Exception('주문 상품이 품절이거나 판매자가 판매를 일시 중지 하였습니다.',10);
            }

            //사이버트론에 결제 비밀번호 인증
            //ckey : barry auth, kind : api response type, user_id : cybertron buyer member(orderer) id value , user_pw : transfer password
            $loadPostData = array(
                'ckey' => $memberInfo[0]['mb_3'],
                'kind' => 'passwd',
                'user_id' => $memberInfo[0]['mb_2'],
                'user_pw' => $barryRsa->encrypt($filterData['plainPassword'])
            );
            $this->logger->info('사이버트론 결제 비밀번호 인증');
            $curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis.php',$loadPostData),true);
            if($curlReturn['code'] == '00'){
                //정상인 경우 처리 없음.
            }
            else if($curlReturn['code'] == '55'){
                $this->logger->error('cybertron transfer password number of inputs error/'.$memberInfo[0]['mb_id']);
                throw new Exception('결제 비밀번호 입력 횟수를 초과 하였습니다.',155);
            }
            else if($curlReturn['code'] == '66'){
                $this->logger->error('cybertron transfer password not set/'.$memberInfo[0]['mb_id']);
                throw new Exception('전송 비밀번호가 설정 되지 않았습니다. 전송 비밀번호를 설정해주세요.',166);
            }
            else if($curlReturn['code'] == '44'){
                $this->logger->error('cybertron identity authentication not set/'.$memberInfo[0]['mb_id']);
                throw new Exception('본인인증 후 이용할 수 있습니다..',144);
            }
            else if($curlReturn['code'] == '77'){
                $this->logger->error('cybertron transfer password not matched error/'.$memberInfo[0]['mb_id']);
                throw new Exception('결제 비밀번호가 일치하지 않습니다.',177);
            }
            else{//노출 할 오류 값이 아니라면 모두 일치 하지 않습니다. 리턴
                $this->logger->error('cybertron transfer password auth error/'.$memberInfo[0]['mb_id']);
                throw new Exception('유효한 결제 요청이 아닙니다.',9000);
            }

            //판매자 고유 id 조회
            /*
                mb_2에 고유 id가 있으면 사용, 없으면 사이버트론 DB에서 조회
            */
            $this->logger->info('판매자 고유 id 조회');
            if($sellerInfo[0]['mb_2']){
                $sellerUserId = $sellerInfo[0]['mb_2'];
            }
            else{
                $ctcWalletdb = $db-> ctcWallet();
                $ctcWalletMemberInfo = $ctcWalletdb->createQueryBuilder()
                ->select('id')
                ->from('admin_accounts')
                ->where('virtual_wallet_address = ?')
                ->setParameter(0, $sellerInfo[0]['mb_1'])
                ->execute()->fetchAll();
                $sellerUserId = $ctcWalletMemberInfo[0]['id'];
                unset($ctcWalletdb);
            }
            
            //결제 정보 빌드
            /*
                wr_ct_price : goods(item) price
                wr_io_price : goods(item) option price
                wr_price_type : payment e-coin type
                wr_6 : order qty
            */
            $this->logger->info('결제 정보 빌드');
            
            /*
                legacy barry db value : TP3, MC , KRW
                cybertron request value : ETP3, EMC
            */
            if($orderInfo[0]['wr_price_type'] == 'MC'){
                $unit = 'EMC';
            }
            else if($orderInfo[0]['wr_price_type'] == 'TP3'){
                $unit = 'ETP3';
            }
			else if($orderInfo['wr_price_type'] == 'EKRW'){
                $unit = 'EKRW';
            }
            else if($orderInfo['wr_price_type'] == 'ECTC'){
                $unit = 'ECTC';
            }
            else{
                $unit = '';
            }
            
            $amount = (float) ($orderInfo[0]['wr_ct_price'] + $orderInfo[0]['wr_io_price']) * $orderInfo[0]['wr_6'];
                
            //사이버트론에 결제 요청
            /*
                ckey : barry auth, 
                kind : api response type, 
                seller_user_id : cybertron seller user_id value,
                seller_address : cybertorn seller wellet address (virtual?),
                buyer_user_id : cybertron buyer member(orderer) id value , 
                amount : payment account(Total), 
                unit : payment e-coin Type
            */

            $loadPostData = array(
                'ckey' => $memberInfo[0]['mb_3'],
                'kind' => 'payment',
                'seller_user_id' => $sellerUserId,
                'seller_address' => $sellerInfo[0]['mb_1'],
                'buyer_user_id' => $memberInfo[0]['mb_2'],
                'amount' => $amount,
                'unit' => $unit
            );
            $curlReturn = json_decode($util -> getCurl('https://cybertronchain.com/apis/barry/apis.php',$loadPostData),true);
            $this->logger->info('결제 요청');
            //익셉션 코드값? 메시지값? 뭘로 리턴 할까?
            //잔액이 부족한 경우는 오류 값을 노출 한다.
            if($curlReturn['code'] == '00'){
                //정상인 경우 처리 없음.
            }
            else if($curlReturn['code'] == '55'){
                $this->logger->error('cybertron payment e-coin balance short/'.$memberInfo[0]['mb_id']);
                throw new Exception('결제 잔액이 부족합니다.',255);
            }
            else if($curlReturn['code'] == '44'){//수수료가 부족한 경우
                $this->logger->error('cybertron payment e-coin fee paid short/'.$memberInfo[0]['mb_id']);
                throw new Exception('결제 수수료가 부족합니다.',244);
            }
            else if($curlReturn['code'] == '33'){//전송 가능한 금액보다 더 적은 금액을 전송 할 경우
                $this->logger->error('cybertron payment balance over e-coin/'.$memberInfo[0]['mb_id']);
                throw new Exception('결제 하려는 금액이 최소 결제 가능한 금액 이하 입니다.',233);
            }
            else{//노출 할 오류 값이 아니라면 결제 요청 실패. 리턴
                $TEMP = false;
                foreach ($loadPostData as $key => $value){
                    $TEMP .= '/'.$key.':'.$value.'';
                }
                foreach ($curlReturn as $key => $value){
                    $TEMP .= '/'.$key.':'.$value.'';
                }
                $this->logger->error('cybertron payment request error/'.$memberInfo[0]['mb_id'].$TEMP);
                unset($TEMP);
                throw new Exception('사이버트론 결제 요청에 실패 하였습니다.',9000);
            }


            //익셉션을 피해 잘 왔다면 결제 완료, 내부 오더에 결제 완료 update를 해준다.
            /*
             * barry order table columns comment
             * wr_10 : completePayment status
             */
            $this->logger->info('내부 오더 결제 완료 처리');
            $orderProc = $barrydb->createQueryBuilder()
                ->update('g5_write_order')
                ->set('wr_10', '"completePayment"')
                ->where('wr_id = ?')
                ->setParameter(0, $orderInfo[0]['wr_id'])
                ->execute();
            if(!$orderProc){
                $this->logger->error('order status complete fail/'.$memberInfo[0]['mb_id']);
                throw new Exception('내부 오더 결제 완료 처리를 실패 하였습니다..',9000);
            }

            //결제가 완료 된 order의 재고를 체크 한다.
            //stock check, order wr_1 wr_id, order wr_9 bo_table
            if ($orderItemValid[0]['it_option_subject']) {
                $it_stock_qty = (int)$orderUtil->get_list_option_stock_qty_barry($orderInfo[0]['wr_1']);
                $it_noti_stock_qty = (int)$orderUtil->get_list_option_noti_qty_barry($orderInfo[0]['wr_1']);
            }
            else {
                $it_stock_qty = (int)$orderUtil->get_it_stock_qty_barry($orderInfo[0]['wr_1'],$orderInfo[0]['wr_9']);
                $it_noti_stock_qty = (int)$orderUtil->get_it_noti_qty_barry($orderInfo[0]['wr_1'],$orderInfo[0]['wr_9']);
            }
            $this->logger->info('결제 완료 된 order 재고 값 재고:'.$it_stock_qty.'/통보:'.$it_noti_stock_qty);

            if($it_stock_qty <= 0 || $it_stock_qty <= $it_noti_stock_qty){
                $soldoutProc = $barrydb->createQueryBuilder()
                    ->update('g5_write_'.$orderInfo[0]['wr_9'])
                    ->set('it_soldout',1)
                    ->where('wr_id = ?')
                    ->setParameter(0, $orderInfo[0]['wr_1'])
                    ->execute();
                if(!$soldoutProc){
                    $this->logger->error('soldout status modify complete fail/'.$orderInfo[0]['wr_1']);
                    throw new Exception('품절 처리 실패!',404);
                }
            }

            $returnArray = array(
                'wr_id' => $orderInfo[0]['wr_id'],
                'targetBoard' => $orderInfo[0]['wr_9']
            );
            $this->logger->alert($loadPostData['kind'].'/'.$loadPostData['seller_user_id'].'/'.$loadPostData['seller_address'].'/'.$loadPostData['buyer_user_id'].'/'.$loadPostData['amount'].'/'.$loadPostData['unit'].'/order id:'.$orderInfo[0]['wr_id']);
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //주요 데이터 조회 전 (주문 요청자가 맞는지 체크) 오류 코드 : 9999
            //주요 데이터 조회 후 (주문 요청자가 맞는지 체크) 오류 코드 : 9000
            //결제에 실패한 경우 유효하지 않는 오더로 처리 합니다.
            //결제 완료 : completePayment, 결제 대기 : waitPayment, 결제 실패 : failPayment
            //프로토 타입에서는 비어 있으면 결제 대기 상태 였습니다. (데이터 일부가 그렇게 남아 있음.)

            //orderID값이 변조되서 들어오면 추적 할 수 없기 때문에, waitPayment로 남습니다.

            // TO-DO:
            // order ID값을 세션으로 추적 할 수 있게 추 후 수정은 어떤지?
            // 메모리?문제..? 비정상적인 접근을 리소스 손해 보면서 까지 감수 해야하나?
            if($e->getCode() != 177){
                $barrydb->createQueryBuilder()
                    ->update('g5_write_order')
                    ->set('wr_10', '"failPayment"')
                    ->where('wr_id = ?')
                    ->andwhere('mb_id = ?')
                    ->setParameter(0, $filterData['orderId'])
                    ->setParameter(1, $this->memberId)
                    ->execute();
            }
            if($e->getCode() != 9999){
                $this->logger->error('after auth/payment fail!/session memberId:'.$this->memberId);
            }
            else{
                $this->logger->error('before auth/payment fail!/session memberId:'.$this->memberId);
            }
            
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
    
    public function test(){
        try{
            $loadPostData = array(
                'it_id' => rand(),
                'it_name' => 'test',
            );
            $loadFileData = array(
                'it_img1' => array(
                                'fileName' => 'test.jpg',
                                'filePath' => './test.jpg'
                            )
            );
            
            $util = barryUtil::singletonMethod();
            //$curlReturn = json_decode($util -> getCurl('http://local_new_barry/barry/user/barryItemUpdate.php',$loadPostData),true);
            $curlReturn = $util -> getCurlFiles('http://local_new_barry/barry/user/barryItemUpdate.php',$loadPostData,$loadFileData);
            var_dump($curlReturn);
            if(!$curlReturn){
                $this->logger->error('test select error/');
                throw new Exception('test 일치하지 않습니다~',9999);
            }
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>