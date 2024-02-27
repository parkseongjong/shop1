<?php

namespace barry\goods;

use \ezyang\htmlpurifier;

use \League\Plates\Engine;

use \barry\common\Util as barryUtil;
use \barry\common\Token as barryToken;
use \barry\common\Filter as barryFilter;
use \barry\common\Uri as barryUri;
use \barry\db\DriverApi as barryDb;

use \InvalidArgumentException;
use \Exception;

class Upload{

    private $data = false;
    private $memberId = false;
    private $logger = false;
    private $session = false;
    private $filterData = array();


    public function __construct($postData, $memberId, $containerInfo){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $containerInfo->get('logger');
        $this->session = $containerInfo->get('session');
        unset($postData,$memberId,$containerInfo);
    }
    /*
    * new item(goods) upload
    * 상품 등록
    * 선택 옵션 등록
    *
    * 상품 수정
    * 선택 옵션 수정
    *
    */
    public function itemUpload(){
        try{
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $uri = barryUri::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();
            $token = barryToken::singletonMethod();

            /*
             *
             * 초기 필터 START
             *
             */

            //gb member info build
            $memberInfo = $util->getGbMember($this->memberId);

            //변수를 필터링 하기전에 권한이 없으면 fail
            if($memberInfo['mb_level'] > 4){
                $this->logger->error('item UPLOAD not auth');
                throw new Exception('상품을 등록 할 권한이 없습니다.',403);
            }

            $targetPostData = array(//레거시를 컬럼을 그대로 사용한다...
                'token' => 'stringNotEmpty',
                'uid' => 'stringNotEmpty',
                'w' => 'string', // 신규 등록 , 수정 여부, 없을땐 신규 등록, u 일 때는 수정
                'tableId' => 'stringNotEmpty',
                'itemSubject' => 'stringNotEmpty', // 상품명
                'itemCategory' => 'string', // 카테고리
                'itemContents' => 'stringNotEmpty', // 상품 내용
                'priceEtp3' => 'integer', //e-tp3가
                'priceEmc' => 'integer', //e-mc가
                'priceEkrw' => 'integer',//e-krw가
                'priceEctc' => 'integer',//e-ctc가
                'priceKrw' => 'integer', //KRW가
                'priceType' => 'stringNotEmpty', //판매 타입
                'retailPrice' => 'string', //코인 상품 소비자 가격
                'cashRetailPrice' => 'string', //비코인 상품 소비자 가격
                'optSubject' => 'isArray', // 선택 옵션 설정 제목
                'optSubjectValue' => 'isArray', // 선택 옵션 설정 항목
                'itemStockQty' => 'integerNotEmpty',         // 재고수량
                'itemNotiQty' => 'integer',          // 재고 통보수량
                'itemLimit' => 'integer',          // 한정 판매 여부
                'itemLimitQty' => 'integer',          // 한정 판매 개인 제한 수량
                'itemLimitActivativationDatetime' => 'string',          // 한정 판매 시작 일자
                'itemLimitDeactivativationDatetime' => 'string',          // 한정 판매 종료 일자
                'krwCosting' => 'integer',          // e코인변환 전 값
                //'imageFixSourceList' => 'isArray',          // 이미지 파일
                'files' => 'middlewareUploadFile',          // 이미지 파일 slim 미들웨어 한번 거치기 때문에 files 로 받음.
                //'io_id' => 'chr30',          // 선택 옵션 고유 ID chr 30이 있어 별도처리, upload나 modify에 쓰이진 않고, ajax 할 때만 쓰임...?
                'optId' => 'chr30',          // 선택 옵션 등록 시 고유 id chr 30이 있어 별도처리,
                'optPrice' => 'selectOption',          // 선택 옵션 등록 시 값 (KRW)
                'optPriceEtp3' => 'selectOption',          // 선택 옵션 등록 시 값 (e-TP3)
                'optPriceEmc' => 'selectOption',          // 선택 옵션 등록 시 값 (e-MC)
                'optCosting' => 'selectOption',          // 선택 옵션 e코인 변환 전 값
                'optStockQty' => 'selectOption',          // 선택 옵션 수량
                'optUse' => 'selectOption',          // 선택 옵션 사용 여부
                'epayPerCoin' => 'integer',    //환율비율
                'exRate' => 'integer',          //환율
                'minimumRetailPrice' => 'integer'          //인터넷 최저가
            );

            //유입 데이터 필터
            $this->filterData = $filter->postDataFilter($this->data,$targetPostData);
            unset($this->data,$filter);

            /* token 확인 */
            if(!$this->filterData['token'] || !$token->validSessionToken($this->filterData['tableId'],$this->filterData['token'],$this->session,'ss_write_','_token')){
                $this->logger->error('seller item uplaod token error(2)');
                throw new Exception('올바른 방법으로 이용해 주십시오.(2)',9999);
            }
            $token->clearSessionToken($this->filterData['tableId'],$this->session,'ss_write_','_token');

            //GB 테이블명 후처리 필터
            $this->filterData['tableId'] = substr(preg_replace('/[^a-z0-9_]/i', '', trim($this->filterData['tableId'])), 0, 20);
            // bo table 체크
            if(!$this->filterData['tableId']){
                $this->logger->error('seller item uplaod token error');
                throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
            }

            //gb board 정보 가져오기
            $boardInfo = $util->getGbBoard($this->filterData['tableId']);
            if(!$boardInfo){
                $this->logger->error('not found boardTable');
                throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
            }

            //기본적으로 레벨3이상 가능하지만, 그 이상으로 레벨을 GB에서 조정 했을 때 조작..
            if($memberInfo['mb_level'] < $boardInfo['bo_write_level']){
                $this->logger->error('item UPLOAD not auth (2)');
                throw new Exception('상품을 등록/수정 할 수 있는 권한이 없습니다.',403);
            }

            //gb filter
            if(!self::itemFilter(array('memberInfo' => $memberInfo,'boardInfo' => $boardInfo))){
                $this->logger->error('GB filter fail');
                throw new Exception('비 정상적인 접근 입니다.',9999);
            }

            /*
             *
             * 초기 필터 END
             *
             */

            if(!$this->filterData['w'] == ''){//비어있는 경우 상품 등록
                $this->logger->error('seller item upload error');
                throw new Exception('알 수 없는 요청 입니다.',403);
            }

            //레거시는 table이 분리 되어 있음... 작성 요청한 db table 명 build
            $writeTargetTable = 'g5_write_'.$boardInfo['bo_table'];

            //now get datetime
            $nowDateTimeSql = $util->getDateSql();
            //seo title bluid
            $seoTitle = $uri->existSeoTitleRecursive('itemUpload', $uri->generateSeoTitle($this->filterData['itemSubject']), $writeTargetTable, $this->filterData['itemId']);

            //상품 등록
            //gb order number save
            $nextNumber = $util->getNextNum($writeTargetTable);

            //상품 등록 값 DB 반영
            $this->logger->info('상품 정보 삽입!');
            $insertProc = $barrydb->createQueryBuilder()
                ->insert($writeTargetTable)
                ->setValue('wr_num','?')//0
                ->setValue('wr_reply', '" "')//reply는 없음
                ->setValue('wr_comment', '" "')
                ->setValue('wr_option','?')
                ->setValue('ca_name','?')
                ->setValue('wr_subject','?')
                ->setValue('wr_content','?')
                ->setValue('wr_seo_title','?')
                ->setValue('mb_id','?')//6
                ->setValue('wr_password','" "')//비회원은 상품 등록 불가 기본적으로 없게 처리
                ->setValue('wr_name','?')
                ->setValue('wr_datetime','?')
                ->setValue('wr_updatetime','?')
                ->setValue('wr_last','?')
                ->setValue('wr_ip','?')
                ->setValue('wr_1','?')//12
                ->setValue('wr_2','?')
                ->setValue('wr_10','?')
                ->setValue('wr_price_type','?')
                ->setValue('wr_retail_price','?')
                ->setValue('it_stock_qty','?')//17
                ->setValue('it_noti_qty','?')
                ->setValue('it_limit','?')
                ->setValue('it_limit_qty','?')
                ->setValue('it_limit_activativation_datetime','?')
                ->setValue('it_limit_deactivativation_datetime','?')//22
                ->setValue('it_cast_price','?')
                ->setValue('it_cast_type','?')
                ->setValue('it_per_rate','?')
                ->setValue('it_rate','?')
                ->setValue('wr_3','?')
                ->setValue('wr_4','?')
                ->setValue('it_minimum_retail_price','?')
                ->setParameter(0,$nextNumber)
                ->setParameter(1,'html2')
                ->setParameter(2,$this->filterData['itemCategory'])
                ->setParameter(3,$this->filterData['itemSubject'])
                ->setParameter(4,$this->filterData['itemContents'])
                ->setParameter(5,$seoTitle)
                ->setParameter(6,$memberInfo['mb_id'])
                ->setParameter(7,$memberInfo['mb_name'])
                ->setParameter(8,$nowDateTimeSql)
                ->setParameter(9,$nowDateTimeSql)
                ->setParameter(10,$nowDateTimeSql)
                ->setParameter(11,$_SERVER['REMOTE_ADDR'])
                ->setParameter(12,$this->filterData['priceEtp3'])
                ->setParameter(13,$this->filterData['priceEmc'])
                ->setParameter(14,$this->filterData['priceKrw'])
                ->setParameter(15,$this->filterData['priceType'])
                ->setParameter(16,$this->filterData['retailPrice'])
                ->setParameter(17,$this->filterData['itemStockQty'])
                ->setParameter(18,$this->filterData['itemNotiQty'])
                ->setParameter(19,$this->filterData['itemLimit'])
                ->setParameter(20,$this->filterData['itemLimitQty'])
                ->setParameter(21,$this->filterData['itemLimitActivativationDatetime'])
                ->setParameter(22,$this->filterData['itemLimitDeactivativationDatetime'])
                ->setParameter(23,$this->filterData['krwCosting'])
                ->setParameter(24,$this->filterData['it_cast_type'])
                ->setParameter(25,(string)$this->filterData['epayPerCoin'])
                ->setParameter(26,(string)$this->filterData['exRate'])
                ->setParameter(27,$this->filterData['priceEkrw'])
                ->setParameter(28,$this->filterData['priceEctc'])
                ->setParameter(29,$this->filterData['minimumRetailPrice'])
                ->execute();

            $this->filterData['itemId'] = $barrydb->lastInsertId();

            //GB parent 에 update (게시판 기능을 사용하는게 아니라.. 사실 사용 안해도 됨.. 하지만 레거시 DB 무결성을 위해...)
            $updateProc = $barrydb->createQueryBuilder()
                ->update($writeTargetTable)
                ->set('wr_parent', '?')
                ->where('wr_id = ?')
                ->setParameter(0,$this->filterData['itemId'])
                ->setParameter(1,$this->filterData['itemId'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('seller item parent error');
                throw new Exception('알 수 없는 요청 입니다.',403);
            }

            //레거시 board 최신 글에 insert
            $insertProc = $barrydb->createQueryBuilder()
                ->insert('g5_board_new')
                ->setValue('bo_table', '?')
                ->setValue('wr_id', '?')
                ->setValue('wr_parent', '?')
                ->setValue('bn_datetime', '?')
                ->setValue('mb_id', '?')
                ->setParameter(0,$boardInfo['bo_table'])
                ->setParameter(1,$this->filterData['itemId'])
                ->setParameter(2,$this->filterData['itemId'])
                ->setParameter(3,$nowDateTimeSql)
                ->setParameter(4,$memberInfo['mb_id'])
                ->execute();
            if(!$insertProc){
                $this->logger->error('seller item new insert error');
                throw new Exception('알 수 없는 요청 입니다.',403);
            }

            //레거시 board count에 update
            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_board')
                ->set('bo_count_write', '?')
                ->where('bo_table = ?')
                ->setParameter(0,$boardInfo['bo_count_write']+1)
                ->setParameter(1,$boardInfo['bo_table'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('seller item board count error');
                throw new Exception('알 수 없는 요청 입니다.',403);
            }

            //선택 옵션 Build
            if(isset($this->filterData['optId'])){
                self::selectOptionUpload($writeTargetTable, $boardInfo);
            }

            //file 처리
            /// 디렉토리 퍼미션 0755
            /// 파일 퍼미션 0644
            $this->logger->info('상품 파일 정보 삽입!(상품 대표 사진)');
            if(!self::itemImageUpload($writeTargetTable, $this->filterData['files']['imageFixSourceList'], $this->filterData['itemId'], $boardInfo, $nowDateTimeSql, 'itemTitle')){
                $this->logger->error('title images files upload fail');
                throw new Exception('상품 대표 사진 업로드를 실패하였습니다..',406);
            }
            $this->logger->info('상품 파일 정보 삽입!(상품 정보 사진)');
            if(!self::itemImageUpload($writeTargetTable, $this->filterData['files']['imageSearchItemDetailSourceList'], $this->filterData['itemId'], $boardInfo, $nowDateTimeSql,'itemDetail')){
                $this->logger->error('detail images files upload fail');
                throw new Exception('상품 정보 사진 업로드를 실패하였습니다..',406);
            }

            $htmlData = self::getHtmlCompletePage(array('bbsUrl'=>BARRY_G5_BBS_URL,'returnUrl' => BARRY_URL.'/'.$boardInfo['bo_table'].'/'.$this->filterData['itemId']));

            $this->logger->alert('item upload!!'.$memberInfo['mb_id'].'/goods(item) ID:'.$this->filterData['itemId'].'/tabldId:'.$boardInfo['bo_table']);
            return array('code' => 200, 'uploadMsg' => '상품 등록을 완료 하였습니다.', 'html' => $htmlData);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('item upload fail!/'.$e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function itemModify(){
        try{
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $uri = barryUri::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();
            $token = barryToken::singletonMethod();

            /*
             *
             * 초기 필터 START
             *
             */

            //gb member info build
            $memberInfo = $util->getGbMember($this->memberId);

            //변수를 필터링 하기전에 권한이 없으면 fail
            if($memberInfo['mb_level'] > 4){
                $this->logger->error('item UPLOAD not auth');
                throw new Exception('상품을 수정 할 권한이 없습니다.',403);
            }

            $tempFilterData = $filter->postDataFilter(array('tableId' => $this->data['tableId'], 'itemId' => $this->data['itemId']),array('tableId'=>'stringNotEmpty','itemId'=>'integer'));
            //필터 전 상품 승인 상태를 봐야 하기 때문에...... 임시로 select 가 필요함.. 임시로 썻던 데이터는 모두 unset 처리 한다.
            $itemTEMPInfo = $barrydb->createQueryBuilder()
                ->select('it_publish')
                ->from('g5_write_'.$tempFilterData['tableId'])
                ->where('wr_id = ?')
                ->andWhere('mb_id = ?')
                ->setParameter(0, $tempFilterData['itemId'])
                ->setParameter(1, $memberInfo['mb_id'])
                ->execute()->fetch();
            if(!$itemTEMPInfo){
                $this->logger->error('not found item(goods) info[TEMP]');
                throw new Exception('상품 정보가 유효하지 않습니다.',9999);
            }

            //임시 데이터는 신뢰 할 수 없기 때문에 실제 처리에 쓰이는 데이터는 itemInfo를 사용한다.
            if($itemTEMPInfo['it_publish'] == 0 || $itemTEMPInfo['it_publish'] == 90 || $itemTEMPInfo['it_publish'] == 99){
                //미승인, 반려
                $targetPostData = array(//레거시를 컬럼을 그대로 사용한다...
                    'token' => 'stringNotEmpty',
                    'uid' => 'stringNotEmpty',
                    'w' => 'string', // 신규 등록 , 수정 여부, 없을땐 신규 등록, u 일 때는 수정
                    'tableId' => 'stringNotEmpty',
                    'itemId' => 'integer', //수정 시 사용 (선택 옵션 연결 item 고유 id )
                    'itemSubject' => 'stringNotEmpty', // 상품명
                    'itemCategory' => 'string', // 카테고리
                    'itemContents' => 'stringNotEmpty', // 상품 내용
                    'priceEtp3' => 'integer', //e-tp3가
                    'priceEmc' => 'integer', //e-mc가
                    'priceEkrw' => 'integer',//e-krw가
                    'priceEctc' => 'integer',//e-ctc가
                    'priceKrw' => 'integer', //KRW가
                    'priceType' => 'stringNotEmpty', //판매 타입
                    'retailPrice' => 'string', //코인 상품 소비자 가격
                    'cashRetailPrice' => 'string', //비코인 상품 소비자 가격
                    'optSubject' => 'isArray', // 선택 옵션 설정 제목
                    'optSubjectValue' => 'isArray', // 선택 옵션 설정 항목
                    'itemStockQty' => 'integerNotEmpty',         // 재고수량
                    'itemNotiQty' => 'integer',          // 재고 통보수량
                    'itemLimit' => 'integer',          // 한정 판매 여부
                    'itemLimitQty' => 'integer',          // 한정 판매 개인 제한 수량
                    'itemLimitActivativationDatetime' => 'string',          // 한정 판매 시작 일자
                    'itemLimitDeactivativationDatetime' => 'string',          // 한정 판매 종료 일자
                    'krwCosting' => 'integer',          // e코인변환 전 값
                    //'imageFixSourceList' => 'isArray',          // 이미지 파일
                    'files' => 'middlewareUploadFile',          // 이미지 파일 slim 미들웨어 한번 거치기 때문에 files 로 받음.
                    //'io_id' => 'chr30',          // 선택 옵션 고유 ID chr 30이 있어 별도처리, upload나 modify에 쓰이진 않고, ajax 할 때만 쓰임...?
                    'optId' => 'chr30',          // 선택 옵션 등록 시 고유 id chr 30이 있어 별도처리,
                    'optPrice' => 'selectOption',          // 선택 옵션 등록 시 값 (KRW)
                    'optPriceEtp3' => 'selectOption',          // 선택 옵션 등록 시 값 (e-TP3)
                    'optPriceEmc' => 'selectOption',          // 선택 옵션 등록 시 값 (e-MC)
                    'optCosting' => 'selectOption',          // 선택 옵션 e코인 변환 전 값
                    'optStockQty' => 'selectOption',          // 선택 옵션 수량
                    'optUse' => 'selectOption',          // 선택 옵션 사용 여부
                    'minimumRetailPrice' => 'integer'          //인터넷 최저가
                );
                $itemFilterFlag = false;
            }
            else{
                //승인
                $targetPostData = array(//레거시를 컬럼을 그대로 사용한다...
                    'token' => 'stringNotEmpty',
                    'uid' => 'stringNotEmpty',
                    'w' => 'string', // 신규 등록 , 수정 여부, 없을땐 신규 등록, u 일 때는 수정
                    'tableId' => 'stringNotEmpty',
                    'itemId' => 'integerNotEmpty', //수정 시 사용 (선택 옵션 연결 item 고유 id )
                    'optSubject' => 'isArray', // 선택 옵션 설정 제목
                    'optSubjectValue' => 'isArray', // 선택 옵션 설정 항목
                    'itemStockQty' => 'integerNotEmpty',         // 재고수량
                    'itemNotiQty' => 'integer',          // 재고 통보수량
                    'itemLimit' => 'integer',          // 한정 판매 여부
                    'itemLimitQty' => 'integer',          // 한정 판매 개인 제한 수량
                    'itemLimitActivativationDatetime' => 'string',          // 한정 판매 시작 일자
                    'itemLimitDeactivativationDatetime' => 'string',          // 한정 판매 종료 일자
                    'files' => 'middlewareUploadFile',          // 이미지 파일 slim 미들웨어 한번 거치기 때문에 files 로 받음.
                    'optId' => 'chr30',          // 선택 옵션 등록 시 고유 id chr 30이 있어 별도처리,
                    'optStockQty' => 'selectOption',          // 선택 옵션 수량
                    'optUse' => 'selectOption',          // 선택 옵션 사용 여부
                    'minimumRetailPrice' => 'integer',          //인터넷 최저가
                    'priceKrw' => 'integer'          //KRW 가
                );
                $itemFilterFlag = true;

            }
            unset($tempFilterData,$itemTEMPInfo);
            //임시 확인이 끝났으면 유입 데이터를 필터 한다.

            //유입 데이터 필터
            $this->filterData = $filter->postDataFilter($this->data,$targetPostData);
            unset($this->data,$filter);
            /* token 확인 */
            if(!$this->filterData['token'] || !$token->validSessionToken($this->filterData['tableId'],$this->filterData['token'],$this->session,'ss_write_','_token')){
                $this->logger->error('seller item uplaod token error(2)');
                throw new Exception('올바른 방법으로 이용해 주십시오.(2)',9999);
            }
            $token->clearSessionToken($this->filterData['tableId'],$this->session,'ss_write_','_token');

            //GB 테이블명 후처리 필터
            $this->filterData['tableId'] = substr(preg_replace('/[^a-z0-9_]/i', '', trim($this->filterData['tableId'])), 0, 20);
            // bo table 체크
            if(!$this->filterData['tableId']){
                $this->logger->error('seller item uplaod token error');
                throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
            }

            //gb board 정보 가져오기
            $boardInfo = $util->getGbBoard($this->filterData['tableId']);
            if(!$boardInfo){
                $this->logger->error('not found boardTable');
                throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
            }

            //기본적으로 레벨3이상 가능하지만, 그 이상으로 레벨을 GB에서 조정 했을 때 조작..
            if($memberInfo['mb_level'] < $boardInfo['bo_write_level']){
                $this->logger->error('item UPLOAD not auth (2)');
                throw new Exception('상품을 등록/수정 할 수 있는 권한이 없습니다.',403);
            }

            //gb filter
            if(!self::itemFilter(array('memberInfo' => $memberInfo,'boardInfo' => $boardInfo),$itemFilterFlag)){
                $this->logger->error('GB filter fail');
                throw new Exception('비 정상적인 접근 입니다.',9999);
            }
            unset($itemFilterFlag);

            /*
             *
             * 초기 필터 END
             *
             */

            if(!$this->filterData['w'] == 'u'){//u가 아닌 경우 수정 요청이 아님.
                $this->logger->error('seller item upload error');
                throw new Exception('알 수 없는 요청 입니다.',403);
            }

            //레거시는 table이 분리 되어 있음... 작성 요청한 db table 명 build
            $writeTargetTable = 'g5_write_'.$boardInfo['bo_table'];

            //상품 수정
            $itemInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from($writeTargetTable)
                ->where('wr_id = ?')
                ->andWhere('mb_id = ?')
                ->setParameter(0, $this->filterData['itemId'])
                ->setParameter(1, $memberInfo['mb_id'])
                ->execute()->fetch();
            if(!$itemInfo){
                $this->logger->error('not found item(godds) info');
                throw new Exception('상품 정보가 유효하지 않습니다.',9999);
            }

            //now get datetime
            $nowDateTimeSql = $util->getDateSql();

            //승인 전에는 모두 수정 가능, 승인 후 에는 재고 수량만 수정 가능
            if($itemInfo['it_publish'] == 0 || $itemInfo['it_publish'] == 90 || $itemInfo['it_publish'] == 99){

                //seo title bluid
                $seoTitle = $uri->existSeoTitleRecursive('itemUpload', $uri->generateSeoTitle($this->filterData['itemSubject']), $writeTargetTable, $itemInfo['wr_id']);
                $updateProc = $barrydb->createQueryBuilder()
                    ->update($writeTargetTable)
                    ->set('ca_name','?')
                    ->set('wr_subject','?')
                    ->set('wr_content','?')
                    ->set('wr_seo_title','?')
                    ->set('mb_id','?')
                    ->set('wr_name','?')//5
                    ->set('wr_updatetime','?')
                    ->set('wr_last','?')
                    ->set('wr_ip','?')
                    ->set('wr_1','?')//9
                    ->set('wr_2','?')//10
                    ->set('wr_10','?')
                    ->set('wr_price_type','?')
                    ->set('wr_retail_price','?')
                    ->set('it_stock_qty','?')//14
                    ->set('it_noti_qty','?')//15
                    ->set('it_limit','?')
                    ->set('it_limit_qty','?')
                    ->set('it_limit_activativation_datetime','?')
                    ->set('it_limit_deactivativation_datetime','?')//19
                    ->set('it_cast_price','?')//20
                    ->set('it_cast_type','?')
                    ->set('wr_3','?')
                    ->set('wr_4','?')
                    ->set('it_minimum_retail_price','?')
                    ->where('wr_id = ?')
                    ->andWhere('it_me_table = ?')
                    ->setParameter(0,$this->filterData['itemCategory'])
                    ->setParameter(1,$this->filterData['itemSubject'])
                    ->setParameter(2,$this->filterData['itemContents'])
                    ->setParameter(3,$seoTitle)
                    ->setParameter(4,$memberInfo['mb_id'])
                    ->setParameter(5,$memberInfo['mb_name'])
                    ->setParameter(6,$nowDateTimeSql)
                    ->setParameter(7,$nowDateTimeSql)
                    ->setParameter(8,$_SERVER['REMOTE_ADDR'])
                    ->setParameter(9,$this->filterData['priceEtp3'])
                    ->setParameter(10,$this->filterData['priceEmc'])
                    ->setParameter(11,$this->filterData['priceKrw'])
                    ->setParameter(12,$this->filterData['priceType'])
                    ->setParameter(13,$this->filterData['retailPrice'])
                    ->setParameter(14,$this->filterData['itemStockQty'])
                    ->setParameter(15,$this->filterData['itemNotiQty'])
                    ->setParameter(16,$this->filterData['itemLimit'])
                    ->setParameter(17,$this->filterData['itemLimitQty'])
                    ->setParameter(18,$this->filterData['itemLimitActivativationDatetime'])
                    ->setParameter(19,$this->filterData['itemLimitDeactivativationDatetime'])
                    ->setParameter(20,$this->filterData['krwCosting'])
                    ->setParameter(21,$this->filterData['it_cast_type'])
                    ->setParameter(22,$this->filterData['priceEkrw'])
                    ->setParameter(23,$this->filterData['priceEctc'])
                    ->setParameter(24,$this->filterData['minimumRetailPrice'])
                    ->setParameter(25,$itemInfo['wr_id'])
                    ->setParameter(26,$itemInfo['it_me_table'])
                    ->execute();
                if(!$updateProc){
                    $this->logger->error('item modify fail');
                    throw new Exception('상품 수정을 실패 하였습니다.',406);
                }

                //승인 전 선택 옵션 처리
                if(!empty($itemInfo['it_option_subject'])){
                    if(!self::checkIsEmptySelectOptionValueStatus($this->filterData['optSubjectValue'])){
                        if(isset($this->filterData['optId'])) {
                            //item info에 비어 있지 않고, 항목에도 데이터가 있다면, 새로 변경하는 것으로 간주. 새로 upload
                            self::selectOptionDelete($itemInfo);
                            self::selectOptionUpload($writeTargetTable, $boardInfo);
                        }
                    }
                    else{
                        //item info에 비어 있지 않을 땐 update 처리.
                        self::selectOptionUpdate($itemInfo);
                    }
                }
                else{
                    //item info에 비어 있을 땐 새로 insert 처리 해주기.
                    if(isset($this->filterData['optId'])) {
                        self::selectOptionDelete($itemInfo);
                        self::selectOptionUpload($writeTargetTable, $boardInfo);
                    }
                }

                //기존 상품 이미지는 무조건 삭제,
                $this->logger->info('기존 상품 이미지 제거');
                if(!self::itemImageDelete($itemInfo['wr_id'], $boardInfo)){
                    $this->logger->error('images files upload fail');
                    throw new Exception('이전 상품 삭제를 실패하였습니다.',406);
                }

                //새 상품 이미지 삽입
                $this->logger->info('상품 파일 정보 삽입(수정)(상품 대표 사진)!');
                if(!self::itemImageUpload($writeTargetTable, $this->filterData['files']['imageFixSourceList'], $this->filterData['itemId'], $boardInfo, $nowDateTimeSql,'itemTitle')){
                    $this->logger->error('images files upload fail');
                    throw new Exception('상품 사진 업로드를 실패하였습니다..',406);
                }

                $this->logger->info('상품 파일 정보 삽입(수정)!(상품 정보 사진)');
                if(!self::itemImageUpload($writeTargetTable, $this->filterData['files']['imageSearchItemDetailSourceList'], $this->filterData['itemId'], $boardInfo, $nowDateTimeSql,'itemDetail')){
                    $this->logger->error('detail images files upload fail');
                    throw new Exception('상품 정보 사진 업로드를 실패하였습니다..',406);
                }

            }
            else{
                //승인 되었을 때 선택 옵션 처리.
                //선택 옵션이 있는 경우에는 무조건 옵션을 선택 해야 주문이 가능
                if(!empty($itemInfo['it_option_subject'])){
                    //GB 선택 옵션은 IO_ID 값으로 받는게 아닌, io_No 작은 -> 큰 수로 차례대로 배치 되고 수정 됨... 수정 시 선택 옵션 재고 값 날아오는걸 받아서 전체 조회.
                    //TO-DO: 추후 io_id 값 매칭으로 변경 할 수 있으면 좋을 것 같음.
                    $selectOptionInfo= $barrydb->createQueryBuilder()
                        ->select('*')
                        ->from('g5_shop_item_option')
                        ->where('it_id = ?')
                        ->andWhere('io_me_table = ?')
                        ->setParameter(0,$itemInfo['wr_id'])
                        ->setParameter(1,$itemInfo['it_me_table'])
                        ->orderBy('io_no','ASC')
                        ->execute()->fetchAll();
                    $selectOptionInfoIndex = 0;
                    foreach ($selectOptionInfo as $key => $value){
                        $optionOrderStockQty = $barrydb->createQueryBuilder()
                            ->select('SUM(wr_6) as sumQty')
                            ->from('g5_write_order')
                            ->where('wr_1 = ?')
                            ->andWhere('wr_9 = ?')
                            ->andWhere('ct_stock_use = 0')
                            ->andWhere('wr_status in ("order", "delivery")')
                            ->andWhere('wr_10 = "completePayment"')
                            ->andWhere('io_id = ?')
                            ->setParameter(0,$itemInfo['wr_id'])
                            ->setParameter(1,$boardInfo['bo_table'])
                            ->setParameter(2,$value['io_id'])
                            ->execute()->fetch();
                        if($optionOrderStockQty['sumQty'] > $this->filterData['optStockQty'][$selectOptionInfoIndex]){
                            $tempValue = explode(chr(30), $value['io_id']);
                            $this->logger->error('select option larger than the order quantity');
                            throw new Exception('선택 옵션('.$tempValue[0].' > '.$tempValue[1].') 재고 수량이 주문 대기 중인 수량 보다 작을 수 없습니다. 더 이상 주문을 받기 어렵다면 상품을 품절 상태로 변경해주세요. 주문 대기 수량 : '.$optionOrderStockQty['sumQty'],406);
                        }
                        $selectOptionInfoIndex++;
                    }
                    unset($selectOptionInfo,$selectOptionInfoIndex, $tempValue);
                    self::selectOptionUpdate($itemInfo);
                }
            }

            //기존 재고수량과  수량 build
            $itemOrderStockQty = $barrydb->createQueryBuilder()
                ->select('SUM(wr_6) as sumQty')
                ->from('g5_write_order')
                ->where('wr_1 = ?')
                ->andWhere('wr_9 = ?')
                ->andWhere('ct_stock_use = 0')
                ->andWhere('wr_status in ("order", "delivery")')
                ->andWhere('wr_10 = "completePayment"')
                ->setParameter(0,$itemInfo['wr_id'])
                ->setParameter(1,$boardInfo['bo_table'])
                ->execute()->fetch();
            // 변경 할 재고 수량보다 현 주문이 더 큰지?
            if($this->filterData['itemStockQty'] < $itemOrderStockQty['sumQty']){
                $this->logger->error('larger than the order quantity');
                throw new Exception('재고 수량이 주문 대기 중인 수량 보다 작을 수 없습니다. 주문 대기 수량:'.$itemOrderStockQty['sumQty'].' 더 이상 주문을 받기 어렵다면 상품을 품절 상태로 변경해주세요.',406);
            }
            unset($itemOrderStockQty);

            //재고 수량 수정
            $updateParams = array();
            $updateProc = $barrydb->createQueryBuilder()
                ->update($writeTargetTable)
                ->set('it_stock_qty', '?')
                ->set('wr_updatetime', '?');
            array_push($updateParams,$this->filterData['itemStockQty']);
            array_push($updateParams,$nowDateTimeSql);

            //쇼핑몰 최저가 수정
            $updateProc
                ->set('it_minimum_retail_price', '?');
            array_push($updateParams,$this->filterData['minimumRetailPrice']);

            //KRW 가 수정
            $updateProc
                ->set('wr_10', '?');
            array_push($updateParams,$this->filterData['priceKrw']);

            //한정 판매 수량 수정
            if($itemInfo['it_limit_qty'] > 0){
                $updateProc
                    ->set('it_limit', '?')
                    ->set('it_limit_qty', '?');
                array_push($updateParams,$this->filterData['itemLimit']);
                array_push($updateParams,$this->filterData['itemLimitQty']);
            }

            array_push($updateParams,$itemInfo['wr_id']);
            $updateProc
                ->where('wr_id = ?')
                ->setParameters($updateParams)
                ->execute();
            unset($updateParams);

            $this->logger->alert('item modify!!'.$memberInfo['mb_id'].'/goods(item) ID:'.$itemInfo['wr_id'].'/tabldId:'.$itemInfo['it_me_table']);
            return array('code' => 200, 'uploadMsg' => '상품 수정을 완료 하였습니다.');
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('item upload fail!(modify)/'.$e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    private function itemFilter(array $info, $itemPublishStatus = false){

        $util = barryUtil::singletonMethod();

        //상품 고유 값 필터
        if (!isset($this->filterData['itemId'])) {
            $this->filterData['itemId'] = 0;
        }

        //$itemPublishStatus 가 false면 미승인 true면 승인 승인 미승인 각각 필터 하는 범위가 다름.
        if($itemPublishStatus === false){
            //gb 분류 정보 가져오기
            if ($info['boardInfo']['bo_use_category']) {
                if (!$this->filterData['itemCategory']) {
                    $this->logger->error('category not select error');
                    throw new Exception('분류를 선택하세요.',406);
                }
                else {
                    $categories = array_map('trim', explode("|", $info['boardInfo']['bo_category_list']));
                    if (!empty($categories) && !in_array($this->filterData['itemCategory'], $categories)){
                        $this->logger->error('category error');
                        throw new Exception('분류를 올바르게 입력하세요.',406);
                    }

                    if (empty($categories)){
                        $this->logger->error('category error!');
                        throw new Exception('분류를 올바르게 입력하세요.',406);
                    }
                }
            }

            //상품 판매 타입 build
            if(!$this->filterData['priceType']){
                $this->logger->error('item(goods) price type error');
                throw new Exception('상품 판매 타입을 설정 하세요.',406);
            }

            //상품명 필터
            if(!$this->filterData['itemSubject']){
                $this->logger->error('item(goods) subject error');
                throw new Exception('상품명을 설정 하세요.',406);
            }
            $this->filterData['itemSubject'] = substr(trim($this->filterData['itemSubject']), 0, 255);

            //상품 내용 필터
            if(!$this->filterData['itemContents']){
                $this->logger->error('item(goods) contents error');
                throw new Exception('상품 내용을 설정 하세요.',406);
            }
            $this->filterData['itemContents'] = substr(trim($this->filterData['itemContents']), 0, 65536);
            $this->filterData['itemContents'] = preg_replace("#[\\\]+$#", "", $this->filterData['itemContents']);


            //wr_1 wr_2 모두 데이터가 있는 경우에는 e-tp3 , e-mc모두를 사용한다고 가정함, TP3MC
            if($this->filterData['priceType'] == 'TP3MC'){
                if (($this->filterData['priceEtp3'] == '0' || empty($this->filterData['priceEtp3'])) && ($this->filterData['priceEmc'] == '0' || empty($this->filterData['priceEmc']))) {
                    $this->logger->error('price type error(2)');
                    throw new Exception('e-TP3, e-MC 중 하나라도 설정을 해야합니다.',406);
                }
                if (empty($this->filterData['priceEtp3']) || $this->filterData['priceEtp3'] == '0') {
                    $this->filterData['priceEtp3']  = 0;
                    //e-tp3를 설정하지 않았다면, mc 타입 판매 상품으로.
                    $this->filterData['priceType'] = 'MC';
                }
                if (empty($this->filterData['priceEmc'])|| $this->filterData['priceEmc'] == '0') {
                    $this->filterData['priceEmc'] = 0;
                    //e-mc를 설정하지 않았다면 e-tp3 타입 판매 상품으로.
                    $this->filterData['priceType'] = 'TP3';
                }

                $this->filterData['priceKrw'] =  $this->filterData['priceEctc'] = $this->filterData['priceEkrw'] = 0;
            }
            else if($this->filterData['priceType'] == 'EKRW'){
                if (($this->filterData['priceEkrw'] == '0' || empty($this->filterData['priceEkrw']))) {
                    $this->logger->error('price type error(2)');
                    throw new Exception('e-krw 설정을 해야합니다.',406);
                }
                if (empty($this->filterData['priceEkrw'])|| $this->filterData['priceEkrw'] == '0') {
                    $this->filterData['priceEkrw'] = 0;
                    //e-krw를 설정하지 않았다면  타입 판매 상품으로.
                    $this->filterData['priceType'] = 'EKRW';
                }
                $this->filterData['priceEtp3'] = $this->filterData['priceEmc'] = $this->filterData['priceEctc'] = $this->filterData['priceKrw'] = 0;
            }
            else if($this->filterData['priceType'] == 'ECTC'){
                if (($this->filterData['priceEctc'] == '0' || empty($this->filterData['priceEctc']))) {
                    $this->logger->error('price type error(2)');
                    throw new Exception('e-ctc 설정을 해야합니다.',406);
                }
                if (empty($this->filterData['priceEctc'])|| $this->filterData['priceEctc'] == '0') {
                    $this->filterData['priceEctc'] = 0;
                    //e-ctc를 설정하지 않았다면  타입 판매 상품으로.
                    $this->filterData['priceType'] = 'ECTC';
                }
                $this->filterData['priceEtp3'] = $this->filterData['priceEmc'] = $this->filterData['priceEkrw'] = $this->filterData['priceKrw'] = 0;
            }
            else{
                if ($this->filterData['priceKrw'] == '0' || empty($this->filterData['priceKrw'])) {
                    $this->logger->error('price type error(3)');
                    throw new Exception('현금을 입력하세요.',406);
                }
                $this->filterData['priceEtp3'] = $this->filterData['priceEmc'] = $this->filterData['priceEctc'] = $this->filterData['priceEkrw'] = 0;

                if($this->filterData['priceType'] == 'CREDITCARD'){
                    $this->filterData['priceType'] = 'CREDITCARD';
                }
                else{
                    $this->filterData['priceType'] = 'KRW';
                }

            }

            //수량 build
            if ($this->filterData['itemStockQty'] < $this->filterData['itemNotiQty']) {
                $this->logger->error('noti qty bigger');
                throw new Exception('재고 통보수량이 재고수량보다 더 클 수 없습니다.',406);
            }

            //판매 타입이 coin 이라면, 변환 원화 값 저장
            if($this->filterData['priceType'] == 'TP3MC' || $this->filterData['priceType'] == 'EKRW' || $this->filterData['priceType'] == 'ECTC'){
                if($this->filterData['krwCosting'] <= 0 || !preg_match("/^[0-9]/i", $this->filterData['krwCosting'])){
                    $this->logger->error('krwCosting value error');
                    throw new Exception('변환 할 값이 0보다 작거나 같을 수 없습니다.',406);
                }
                else{//값이 있으면, cast type은 KRW로 설정, (추후 더 추가 되기 전까지)
                    $this->filterData['it_cast_type'] = 'KRW';
                }
            }
            else{
                $this->filterData['it_cast_type'] = 'NONE';

                //소비자가.. 현금인 경우는 0원 처리.
                $this->filterData['retailPrice'] = 0;
                $this->filterData['krwCosting'] = 0;
            }

            $this->filterData['fileCount'] = count($this->filterData['files']['imageFixSourceList']);
            if($this->filterData['fileCount'] <= 0){
                $this->logger->error('file not found!');
                throw new Exception('첨부 된 사진이 없습니다.',406);
            }
            if($this->filterData['fileCount'] > 20){
                $this->logger->error('file count max!');
                throw new Exception('사진 첨부 최대 개수를 초과 하였습니다.',406);
            }
            /*
                //업로드 가능한 사진 개수
                let maxFiles = 20;
                //업로드 가능한 최대 제한 용량 (64MB), 67108864 bytes
                let maxSize = 67108864;
                //업로드 단일 사진 제한 용량 (15MB), 15728640 bytes
                let perMaxSize = 15728640;
            */
            //이미지 파일 용량 확인
            $fileTotalSize = false;
            foreach ($this->filterData['files']['imageFixSourceList'] as $key => $value){
                $targetFileSize = $value->getSize();

                if($targetFileSize <= 0){
                    $this->logger->error('upload array same fail!');
                    throw new Exception('업로드 요청한 사진 중에 누락된 사진 파일이 존재합니다!.',406);
                }

                if($targetFileSize > 15728640){
                    $this->logger->error('perMaxSize fail!');
                    throw new Exception('단일 사진 용량을 초과 하였습니다.',406);
                }
                $fileTotalSize += $targetFileSize;
            }
            if($fileTotalSize > 67108864){
                $this->logger->error('maxSize fail!');
                throw new Exception('전체 사진 용량을 초과 하였습니다.',406);
            }
            //파일 스트림 크기가 좀 있으니.. 순회에 쓰인 별칭 변수는 언셋 처리
            unset($key,$value,$fileTotalSize,$targetFileSize);

        }

        //재고값이 없다면 기본 값 처리
        if(!isset($this->filterData['itemStockQty'])){
            $this->filterData['itemStockQty'] = 1;
        }
        if(!isset($this->filterData['itemNotiQty'])){
            $this->filterData['itemNotiQty'] = 0;
        }
        //itemLimitQty가 1보다 작은 경우 한정 판매 사용 안함.
        if(!isset($this->filterData['itemLimitQty']) || $this->filterData['itemLimitQty'] <= 0){
            $this->filterData['itemLimit'] = 0;
            $this->filterData['itemLimitActivativationDatetime'] = $this->filterData['itemLimitDeactivativationDatetime'] = $util->getDateSqlDefault();
        }
        else{
            $this->filterData['itemLimit'] = 1;
        }

        //정상 처리라면 ... OK
        return true;

    }

    /**
     * @param string $writeTargetTable -> insert or update table taerget
     * @param array $boardInfo -> board 정보
     */
    private function selectOptionUpload(String $writeTargetTable, Array $boardInfo){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $this->logger->info('선택 옵션 등록 처리!');
        //opt type 에 따라 기본 값 값 설정.
        //opt_id가 배열로 존재하면, 바로 순회하며 insert?
        if(isset($this->filterData['optId'])) {
            $selectOptionInfo = $barrydb->createQueryBuilder()
                ->select('io_no')
                ->from('g5_shop_item_option')
                ->where('it_id = ?')
                ->andWhere('io_me_table = ?')
                ->setParameter(0, $this->filterData['itemId'])
                ->setParameter(1, $boardInfo['bo_table'])
                ->execute()->fetchAll();
            if ($selectOptionInfo) {
                $this->logger->error('select option duplicate!');
                throw new Exception('이미 선택 옵션이 등록 되어 있습니다.! 관리자에게 문의 해주세요.', 406);
            }

            if (!isset($this->filterData['optSubject'])) {
                $this->logger->error('select option subject notFound!');
                throw new Exception('선택 옵션 제목이 누락 되었습니다.', 406);
            }

            //선택 옵션 제목 Build
            $itemOptionSubject = false;
            foreach ($this->filterData['optSubject'] as $key => $value) {
                if (!empty($this->filterData['optSubject'][$key])) {
                    if ($key != 0) {
                        $itemOptionSubject .= ',' . $this->filterData['optSubject'][$key];
                    } else {
                        $itemOptionSubject = $this->filterData['optSubject'][$key];
                    }
                }
            }

            //item(Goods) 정보에 선택 옵션 제목 업데이트
            //컬럼과 동일한 값이 유입 되면 false 리턴 됨... 익셉션 제거.
            $updateProc = $barrydb->createQueryBuilder()
                ->update($writeTargetTable)
                ->set('it_option_subject', '?')
                ->where('wr_id = ?')
                ->setParameter(0, trim($itemOptionSubject))
                ->setParameter(1, $this->filterData['itemId'])
                ->execute();

            $selectOptionBuilder = $barrydb->createQueryBuilder()
                ->insert('g5_shop_item_option')
                ->setValue('io_id', '?')//0
                ->setValue('io_type', 0)
                ->setValue('it_id', '?')
                ->setValue('io_price', '?')
                ->setValue('io_stock_qty', '?')
                ->setValue('io_noti_qty', '?')//4
                ->setValue('io_use', '?')
                ->setValue('wr_price_type', '?')
                ->setValue('io_price_etp3', '?')
                ->setValue('io_price_emc', '?')
                ->setValue('io_me_table', '?')
                ->setValue('io_cast_type', '?')//10
                ->setValue('io_cast_price', '?');


            //다른 선택 옵션 값들은 opt_id 삽입 시 순차적으로 들어왔으므로 index 기준으로  삽입 할 데이터 build
            foreach ($this->filterData['optId'] as $key => $value) {

                //타입별로 컬럼이 따로 있음... 원하는 타입이 아닐 땐 다른 컬럼은 초기화
                if ($this->filterData['priceType'] == 'KRW') {
                    $this->filterData['optPriceEtp3'][$key] = 0;
                    $this->filterData['optPriceEmc'][$key] = 0;
                    $this->filterData['optCosting'][$key] = 0;
                } else {
                    $this->filterData['optPrice'][$key] = 0;
                }

                $tempArray = array();
                array_push($tempArray, $this->filterData['optId'][$key]);
                array_push($tempArray, $this->filterData['itemId']);
                array_push($tempArray, $this->filterData['optPrice'][$key]);
                array_push($tempArray, $this->filterData['optStockQty'][$key]);
                array_push($tempArray, 0);//noti(통보) 수량은 0으로 처리.
                array_push($tempArray, 1);
                array_push($tempArray, $this->filterData['priceType']);
                array_push($tempArray, $this->filterData['optPriceEtp3'][$key]);
                array_push($tempArray, $this->filterData['optPriceEmc'][$key]);
                array_push($tempArray, $boardInfo['bo_table']);
                array_push($tempArray, 'KRW');
                array_push($tempArray, $this->filterData['optCosting'][$key]);
                $selectOptionBuilder
                    ->setParameters($tempArray)
                    ->execute();
            }
            unset($selectOptionBuilder, $tempArray);

            return true;
        }
    }

    /**
     * @param array $itemInfo -> item 정보
     */
    private function selectOptionUpdate(Array $itemInfo){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        //레거시 소스 그대로 유지.. 하려 했으나... 승인 상태 goods(item)은 불러오고 재등록 하기가 불편함. 새로 update문을 짜서 넣는게 더 깔끔 해보임 ....
        //레거시 소스에서는 선택옵션 설정 시 ASC 기준으로 차례로 데이터를 삽입함... 즉 ..신뢰성이 많이 떨어짐....
        $selectOptionSubject = explode(',', $itemInfo['it_option_subject']);
        $selectOptionInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_shop_item_option')
            ->where('it_id = ?')
            ->andWhere('io_me_table = ?')
            ->setParameter(0,$itemInfo['wr_id'])
            ->setParameter(1,$itemInfo['it_me_table'])
            ->orderBy('io_no','ASC')
            ->execute()->fetchAll();
        if(!$selectOptionInfo){
            return false;
        }

        if($selectOptionInfo){
            foreach ($selectOptionInfo as $key => $value){
                $selectOptionInfoUpdateProc = $barrydb->createQueryBuilder()
                    ->update('g5_shop_item_option')
                    ->set('io_stock_qty', '?')
                    ->set('io_use', '?')
                    ->where('io_no = ?')
                    ->setParameter(0,$this->filterData['optStockQty'][$key])
                    ->setParameter(1,$this->filterData['optUse'][$key])
                    ->setParameter(2,$value['io_no'])
                    ->execute();
            }
        }

        return true;
    }

    /**
     * @param array $itemInfo -> item 정보
     */
    private function selectOptionDelete(Array $itemInfo){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $barrydb->createQueryBuilder()
            ->delete('g5_shop_item_option')
            ->where('it_id = ?')
            ->andWhere('io_me_table = ?')
            ->setParameter(0,$itemInfo['wr_id'])
            ->setParameter(1,$itemInfo['it_me_table'])
            ->execute();

        return true;
    }

    /**
     * @param array $array -> 선택 옵션 항목 값
     */
    private function checkIsEmptySelectOptionValueStatus(array $array){
        foreach ($array as $key => $value){
            if(empty($value)){
                return true;
            }
            else{
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $writeTargetTable -> files 정보 update 대상 table
     * @param array $fileArray -> files 데이터 담긴 array
     * @param string $targetId -> update 대상 고유 id goods(item) 고유 id
     * @param array $boardInfo -> board 정보
     * @param string $dateTime -> update 시간
     * @param string $type -> 상품 대표 사진 OR 상품 정보 사진 여부 (itemTitle,itemDetail) 아무런 설정 없을 시 itemTitle
     */
    private function itemImageUpload($writeTargetTable, $fileArray, $targetId, $boardInfo, $dateTime, $type){

        $util = barryUtil::singletonMethod();
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        if($type == 'itemDetail'){
            $type = 'itemDetail';
            //bf_no은 대표 사진 다음 count 로 반영
            $itemInfo = $barrydb->createQueryBuilder()
                ->select('bf_no')
                ->from('g5_board_file')
                ->where('bo_table = ?')
                ->andWhere('wr_id = ?')
                ->setParameter(0,$boardInfo['bo_table'])
                ->setParameter(1,$targetId)
                ->orderBy('bf_no','DESC')
                ->setMaxResults(1)
                ->execute()->fetch();
            $fileOrder = $itemInfo['bf_no'] + 1;
        }
        else{
            $type = 'itemTitle';
            $fileOrder = 1;
        }


        foreach ($fileArray as $key => $value) {
            if($value->getSize() > 0){
                $uploadFileInfo = $util->slimApiMoveUploadedFile($_SERVER['DOCUMENT_ROOT'] . '/data/file/'.$boardInfo['bo_table'], $value, 'image');
                if($uploadFileInfo){
                    $insertProc = $barrydb->createQueryBuilder()
                        ->insert('g5_board_file')
                        ->setValue('bo_table', '?')
                        ->setValue('wr_id', '?')
                        ->setValue('bf_no', '?')
                        ->setValue('bf_source', '?')
                        ->setValue('bf_file', '?')
                        ->setValue('bf_content', '?')//5
                        ->setValue('bf_download',0)
                        ->setValue('bf_filesize', '?')
                        ->setValue('bf_width', '?')
                        ->setValue('bf_height', '?')
                        ->setValue('bf_type', '?')
                        ->setValue('bf_datetime', '?')//10
                        ->setValue('bf_storage', '?')//11
                        ->setParameter(0,$boardInfo['bo_table'])
                        ->setParameter(1,$targetId)
                        ->setParameter(2,$fileOrder)
                        ->setParameter(3,$uploadFileInfo['name'].'.'.$uploadFileInfo['extension'])//GB 에서는 파일명에 확장자 까지 붙여줍니다.
                        ->setParameter(4,$uploadFileInfo['convertName'].'.'.$uploadFileInfo['extension'])
                        ->setParameter(5,'')//GB 에디터로 이미지 첨부 할 때 쓰이는 컬럼 입니다.
                        ->setParameter(6,$uploadFileInfo['size'])
                        ->setParameter(7,$uploadFileInfo['width'])
                        ->setParameter(8,$uploadFileInfo['height'])
                        ->setParameter(9,$uploadFileInfo['predefinedImageType'])
                        ->setParameter(10,$dateTime)
                        ->setParameter(11,$type)
                        ->execute();
                    $fileOrder++;
                }
            }
        }
        //레거시 상품 정보 file 개 수를 업데이트 해줌.
        $barrydb->createQueryBuilder()
            ->update($writeTargetTable)
            ->set('wr_file', '?')
            ->where('wr_id = ?')
            ->setParameter(0,($fileOrder-1))
            ->setParameter(1,$targetId)
            ->execute();

        //파일 스트림 크기가 좀 있으니.. 순회에 쓰인 별칭 변수는 언셋 처리
        unset($key,$value,$fileOrder,$uploadFileInfo);
        return true;
    }

    /**
     * @param string $targetId -> update 대상 고유 id goods(item) 고유 id
     * @param array $boardInfo -> board 정보
     */
    private function itemImageDelete($targetId, $boardInfo){

        $util = barryUtil::singletonMethod();
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();
        $fileInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_board_file')
            ->where('bo_table = ?')
            ->andWhere('wr_id = ?')
            ->setParameter(0,$boardInfo['bo_table'])
            ->setParameter(1,$targetId)
            ->execute()->fetchAll();
        foreach($fileInfo as $value){
            unlink($_SERVER['DOCUMENT_ROOT'].'/data/file/'.$boardInfo['bo_table'].'/'.$value['bf_file']);
            if(preg_match("/\.(jpg|jpeg|gif|png)$/i",$value['bf_file'])) {
                $util->deleteThumbnail($_SERVER['DOCUMENT_ROOT'].'/data',$boardInfo['bo_table'], $value['bf_file']);
            }
        }

        //기존 파일 db 정보 제거
        //(제거를 하지 않고 update를 칠지.. 고민이 좀 필요함....)
        $barrydb->createQueryBuilder()
            ->delete('g5_board_file')
            ->where('bo_table = ?')
            ->andWhere('wr_id = ?')
            ->setParameter(0,$boardInfo['bo_table'])
            ->setParameter(1,$targetId)
            ->execute();
        //goods(item) file count 리셋
        $barrydb->createQueryBuilder()
            ->update('g5_write_'.$boardInfo['bo_table'])
            ->set('wr_file',0)
            ->where('it_me_table = ?')
            ->andWhere('wr_id = ?')
            ->setParameter(0,$boardInfo['bo_table'])
            ->setParameter(1,$targetId)
            ->execute();

        return true;
    }

    /**
     * @param array $array -> view에 리턴 할 값
     */
    private function getHtmlCompletePage(array $array){
        $templates = new Engine(BARRY_API_VIEW_ROOT_PATH.'/goods', 'html');
        return $templates->render('complete.skin', ['data' => $array]);
    }
}