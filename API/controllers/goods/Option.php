<?php


namespace barry\goods;

use \ezyang\htmlpurifier;

use \League\Plates\Engine;
use \League\Plates\Extension\Asset;

use \barry\common\Util as barryUtil;
use \barry\common\Token as barryToken;
use \barry\common\Filter as barryFilter;
use \barry\common\Uri as barryUri;
use \barry\order\Util as barryOrderUtil;
use \barry\db\DriverApi as barryDb;

use \InvalidArgumentException;
use \Exception;

class Option{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    //상품 등록 시 사용 메소드
    public function getSellerUploadSelectOptionForm(){
        try{
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'optSubject' => 'isArrayNotEmpty',
                'optSubjectValue' => 'isArrayNotEmpty',
                'priceType' =>'stringNotEmpty',
            );
            $filterData = $filter->postDataFilter($this->data,$targetPostData);
            unset($this->data,$filter);

            /*
             * 따로 필터를 하긴 했는데, /[\'\"\\\'\\\"]/ 을 안해도 이스케이프 되는지? 확인 . 될 것 같긴 한데, 테스트 필요
             */
            $optSubjectCount = count($filterData['optSubject']);
            $optSubjectValueCount = count($filterData['optSubjectValue']);
            if($optSubjectCount > 3 || $optSubjectValueCount > 3){
                $this->logger->error('upload array count overflow!');
                throw new Exception('지정된 요청 선택 옵션을 초과 하였습니다. ',406);
            }

            //제목과 내용 배열 개수가 일치하지 않으면 비정상으로 간주.
            if($optSubjectCount != $optSubjectValueCount){
                $this->logger->error('upload array count fail!');
                throw new Exception('누락 된 내용이 있습니다. 옵션 제목과 옵션 항목을 확인해 주세요!',406);
            }
            $optionFilterData = array();
            foreach ($filterData['optSubject'] as $key => $value){
                $optionFilterData['data'][$key]['subject'] = trim($value);
                $optionFilterData['data'][$key]['convert'] = explode(',',trim($filterData['optSubjectValue'][$key]));
            }
            $optionFilterData['type'] = $filterData['priceType'];
            unset($filterData);
            /*
             array(3) {
              ["data"]=>
              array(2) {
                [0]=>
                array(2) {
                  ["subject"]=>
                  string(6) "크기"
                  ["convert"]=>
                  array(3) {
                    [0]=>
                    string(3) "큰"
                    [1]=>
                    string(3) "중"
                    [2]=>
                    string(3) "작"
                  }
                }
                [1]=>
                array(2) {
                  ["subject"]=>
                  string(6) "색상"
                  ["convert"]=>
                  array(2) {
                    [0]=>
                    string(3) "빨"
                    [1]=>
                    string(3) "노"
                  }
                }
              }
              ["type"]=>
              string(6) "coin"
            }
             */
            //select opiton build
            $optIdBuild = array();
            $key = 0;
            foreach ($optionFilterData['data'][0]['convert'] as $operand1Key => $operand1Value){//1번?
                if(isset($optionFilterData['data'][$key+1])){//2번째 옵션 까지 있는 경우 저장
                    foreach ($optionFilterData['data'][$key+1]['convert'] as $operand2Key => $operand2Value){//2번
                        if(isset($optionFilterData['data'][$key+2])){//3번째 옵션 까지 있는 경우 저장
                            foreach ($optionFilterData['data'][$key+2]['convert'] as $operand3Key => $operand3Value) {//3번
                                array_push($optIdBuild, $operand1Value.chr(30).$operand2Value.chr(30).$operand3Value);
                            }
                        }
                        else{
                            array_push($optIdBuild,$operand1Value.chr(30).$operand2Value);
                        }
                    }
                }
                else{
                    //1번째 옵션 까지만 있는 경우 저장
                    array_push($optIdBuild,$operand1Value);
                }
            }
            $optionFilterData['optIdList'] = $optIdBuild;

            $htmlData = self::getHtmlForm($optionFilterData,'upload');
            return array('code' => 200, 'html' => $htmlData);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('select option form return fail!');
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function getSellerModifySelectOptionForm(){
        try{
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'itemId' => 'integerNotEmpty',
                'tableId' =>'stringNotEmpty'
            );
            $filterData = $filter->postDataFilter($this->data,$targetPostData);
            unset($this->data,$filter);

            //gb member info build
            $memberInfo = $util->getGbMember($this->memberId);
            if(!$memberInfo){
                $this->logger->error('getSellerSelectOptionForm auth fail!');
                throw new Exception('선택 옵션을 수정 할 권한이 없습니다.',403);
            }

            $boardInfo = $util->getGbBoard($filterData['tableId']);
            if(!$boardInfo){
                $this->logger->error('not found boardTable');
                throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
            }

            $writeTargetTable = 'g5_write_'.$boardInfo['bo_table'];

            $itemInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from($writeTargetTable)
                ->where('wr_id = ?')
                ->andWhere('mb_id = ?')
                ->setParameter(0,$filterData['itemId'])
                ->setParameter(1,$memberInfo['mb_id'])
                ->execute()->fetch();
            if(!$itemInfo){
                $this->logger->error('getSellerSelectOptionForm not found item info');
                throw new Exception('상품 정보가 없습니다.',406);
            }

            $selectOptionItem = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_shop_item_option')
                ->where('io_me_table = ?')
                ->andWhere('it_id = ?')
                ->setParameter(0,$boardInfo['bo_table'])
                ->setParameter(1,$itemInfo['wr_id'])
                ->orderBy('io_no','ASC')
                ->execute()->fetchAll();
            if(!$selectOptionItem){
                $this->logger->error('getSellerSelectOptionForm not found selectOption info');
                throw new Exception('선택 옵션 정보가 없습니다.',406);
            }
            $optIdBuild = array();
            $filterData['optSubject'] = explode(',',trim($itemInfo['it_option_subject']));
            foreach ($selectOptionItem as $key => $value){
                //선택 옵션에 priceType은 모두 동일 함
                if($key == 0){
                    $optionFilterData['type'] = $value['wr_price_type'];
                }
                $optIdBuild[$key]['id'] =  $value['io_id'];
                $optIdBuild[$key]['info'] = $value;
            }

            $optionFilterData['optIdList'] = $optIdBuild;
            $optionFilterData['publishStatus'] = $itemInfo['it_publish'];

            $htmlData = self::getHtmlForm($optionFilterData,'modify');
            return array('code' => 200, 'html' => $htmlData);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('select option form return fail!');
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    //상품 구매 시 노출 되는 user 선택 옵션, 선택한 select에 다음 선택 옵션 항목 리턴
    //ORDER 마치고 계속 진행. single 도 변경 되어야 하고 shop.lib등.. 레거시 소스를 다 뜯어야함. (레거시 상태론 사용 가능)
    public function getUserSelectOptionSingleList(){
        $util = barryUtil::singletonMethod();
        $filter = barryFilter::singletonMethod();
        $orderUtil = barryOrderUtil::singletonMethod();
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $targetPostData = array(
            'itemId' => 'integerNotEmpty',
            'tableId' =>'stringNotEmpty',
            'optId' => 'singleChr30NotEmpty', //선택 옵션 호출 optId
            'totalCount' => 'integerNotEmpty', // 선택 옵션 항목 개수
            'thisCallIndex' => 'integerNotEmpty', //선택 옵션 호출 Select index 위치
            'thisCallTitle' => 'stringNotEmpty' //선택 옵션 호출 항목 제목
        );
        $filterData = $filter->postDataFilter($this->data,$targetPostData);
        unset($this->data,$filter);

        $boardInfo = $util->getGbBoard($filterData['tableId']);
        //goods(Item) 정보를 불러오는건 캐시를 적용 할 검토가 필요함.
        $itemInfo = $barrydb->createQueryBuilder()
            ->select('wr_id, it_option_subject')
            ->from('g5_write_'.$boardInfo['bo_table'])
            ->where('wr_id = ?')
            ->andWhere('it_me_table = ?')
            ->setParameter(0,$filterData['itemId'])
            ->setParameter(1,$boardInfo['bo_table'])
            ->execute()->fetch();
        if(!$itemInfo){
            $this->logger->error('getUserSelectOptionSingleList not found item info');
            throw new Exception('상품 정보가 없습니다.',406);
        }

        $optionInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_shop_item_option')
            ->where('io_type = 0')
            ->andWhere('it_id = ?')
            ->andWhere('io_use = 1')
            ->andWhere('io_id like ?')
            ->andWhere('io_me_table = ?')
            ->setParameter(0,$filterData['itemId'])
            ->setParameter(1,$filterData['optId'].'%')  ///싱글 chr30 필터링 하는 부분을 만들어야 할 것 같음.. 지금 갖고 있는건 배열 chr30 뿐임..
            ->setParameter(2,$boardInfo['bo_table'])
            ->orderBy('io_no','ASC')
            ->execute()->fetchAll();
        if(!$optionInfo){
            $this->logger->error('getUserSelectOptionSingleList not found selectOption info');
            throw new Exception('선택 옵션 정보가 없습니다.',406);
        }

        //선택한 선택 옵션 다음 항목 제목 build
        if($filterData['thisCallTitle'] && $itemInfo['it_option_subject']){
            $temp = explode(',', $itemInfo['it_option_subject']);
            if($filterData['thisCallTitle'] != $temp[$filterData['thisCallIndex']]){
                $this->logger->error('getUserSelectOptionSingleList thisCallTitle error');
                throw new Exception('선택 옵션 요청한 제목이 일치 하지 않습니다.',403);
            }


            if(isset($temp[$filterData['thisCallIndex']+1])){
                $optionFilterData['firstOptionTitle'] = $temp[$filterData['thisCallIndex']+1];
            }
            else{
                $optionFilterData['firstOptionTitle'] = false;
            }
        }

        //var_dump($optionInfo);


        $optionFilterData['optionList'] = $optionCheck = array();
        $optionFilterDataindex = 0;

        foreach ($optionInfo as $optionInfoKey => $optionInfoValue){
            $val = explode(chr(30), $optionInfoValue['io_id']);
            $key = $filterData['thisCallIndex'] + 1;

            //chr 30으로 분리 했으나 값이 없는 경우 다음으로.
            if(!strlen($val[$key])){
                continue;
            }

            $continue = false;
            foreach($optionCheck as $v) {
                if(strval($v) === strval($val[$key])) {
                    //이미 opt 배열안에 있는 경우 스킵
                    $continue = true;
                    break;
                }
            }
            if($continue){
                continue;
            }

            array_push($optionCheck,strval($val[$key]));
            /*
             * optionLastCheck 가 굳이 필요한가..?
             *
             */

            if($key + 1 < $filterData['totalCount']) {
                //카테고리 최종 선택 값 이전 데이터들 입니다.
                //var_dump($val[$key]);
                //최종 선택 값 이전 데이터 buildValue는 VALUE만 넣는다.
                $optionFilterData['optionList'][$optionFilterDataindex]['optionBuildValue'] = $val[$key];
                $optionFilterData['optionList'][$optionFilterDataindex]['optionValue'] = $val[$key];
                $optionFilterData['optionList'][$optionFilterDataindex]['optionType'] = $optionInfoValue['io_type'];
                $optionFilterData['optionList'][$optionFilterDataindex]['optionLastCheck'] = false;
            }
            else{
                //마지막 옵션 값에서 옵션에 값과 type이 결정 됨 (vue 로 넘겨줄땐 unit과 price는 json으로 내보내 줘야 할 듯.)
                $ioStockQty = $orderUtil->get_option_stock_qty_barry($itemInfo['wr_id'], $optionInfoValue['io_id'], $optionInfoValue['io_me_table']);

                if($ioStockQty < 1){
                    $optionFilterData['optionList'][$optionFilterDataindex]['soldOutStatus'] = true;
                    $optionFilterData['optionList'][$optionFilterDataindex]['soldOutMsg'] = '[품절]';
                }
                else{
                    $optionFilterData['optionList'][$optionFilterDataindex]['soldOutStatus'] = false;
                    $optionFilterData['optionList'][$optionFilterDataindex]['soldOutMsg'] = '[재고있음]';
                }

                //io_id , 현금가, etp3가 , emc가, 재고수량
                $optionFilterData['optionList'][$optionFilterDataindex]['optionBuildValue'] = $val[$key].','.$optionInfoValue['io_price'].','.$optionInfoValue['io_price_etp3'].','.$optionInfoValue['io_price_emc'].','.$ioStockQty;
                $optionFilterData['optionList'][$optionFilterDataindex]['optionValue'] = $val[$key];
                $optionFilterData['optionList'][$optionFilterDataindex]['optionType'] = $optionInfoValue['io_type'];
                $optionFilterData['optionList'][$optionFilterDataindex]['optionLastCheck'] = true;

                if($optionInfoValue['wr_price_type'] == 'KRW'){
                    //KRW 원화
                    $optionFilterData['optionList'][$optionFilterDataindex]['priceKrw'] = ($optionInfoValue['io_price'] > 0)?number_format($optionInfoValue['io_price']):0;
                    $optionFilterData['optionList'][$optionFilterDataindex]['priceKrwUnit'] = '원';
                }
                else if($optionInfoValue['wr_price_type'] == 'TP3MC'){
                    //coin
                    $optionFilterData['optionList'][$optionFilterDataindex]['priceTP3'] = ($optionInfoValue['io_price_etp3'] > 0)?number_format($optionInfoValue['io_price_etp3']):0;
                    $optionFilterData['optionList'][$optionFilterDataindex]['priceMC'] = ($optionInfoValue['io_price_emc'] > 0)?number_format($optionInfoValue['io_price_emc']):0;
                    $optionFilterData['optionList'][$optionFilterDataindex]['priceTP3Unit'] = 'e-TP3';
                    $optionFilterData['optionList'][$optionFilterDataindex]['priceMCUnit'] = 'e-MC';

                }
                else{
                    $this->logger->error('getUserSelectOptionSingleList not found priceType');
                    throw new Exception('선택 옵션 결제 수단을 찾을 수 없습니다.',403);

                }

                //io_id , 현금가, etp3가 , emc가, 재고수량
                //$str .= PHP_EOL.'<option value="'.$val[$key].','.$optionInfoValue['io_price'].','.$optionInfoValue['io_price_etp3'].','.$optionInfoValue['io_price_emc'].','.$io_stock_qty.'" data-option-title="'.$option_title.'" data-option-value="'.$val[$key].'" data-io-type="'.$optionInfoValue['io_type'].'">'.$val[$key].$price.$soldout.'</option>';
            }
            $optionFilterDataindex++;
        }
        /*
            중간 select option 구조
                array(2) {
                  ["firstOptionTitle"]=>
                  string(6) "색상"
                  ["optionList"]=>
                  array(2) {
                    [0]=>
                    array(3) {
                      ["optionBuildValue"]=>
                      string(6) "노랑"
                      ["optionValue"]=>
                      string(6) "노랑"
                      ["optionType"]=>
                      string(1) "0"
                    }
                    [1]=>
                    array(3) {
                      ["optionBuildValue"]=>
                      string(6) "검은"
                      ["optionValue"]=>
                      string(6) "검은"
                      ["optionType"]=>
                      string(1) "0"
                    }
                  }
                }
            마지막 select option 구조
                array(2) {
                  ["firstOptionTitle"]=>
                  string(6) "키링"
                  ["optionList"]=>
                  array(2) {
                    [0]=>
                    array(6) {
                      ["priceTP3"]=>
                      string(1) "5"
                      ["priceMC"]=>
                      string(1) "5"
                      ["priceTP3Unit"]=>
                      string(5) "e-TP3"
                      ["priceMCUnit"]=>
                      string(4) "e-MC"
                      ["soldOutStatus"]=>
                      bool(false)
                      ["soldOutMsg"]=>
                      string(14) "[재고있음]"
                    }
                    [1]=>
                    array(6) {
                      ["priceTP3"]=>
                      string(1) "5"
                      ["priceMC"]=>
                      string(1) "5"
                      ["priceTP3Unit"]=>
                      string(5) "e-TP3"
                      ["priceMCUnit"]=>
                      string(4) "e-MC"
                      ["soldOutStatus"]=>
                      bool(false)
                      ["soldOutMsg"]=>
                      string(14) "[재고있음]"
                    }
                  }
                }
         */
//        var_dump($optionFilterData);
//        var_dump($optionCheck);
        $htmlData = self::getHtmlForm($optionFilterData,'userViewSingle');
        return array('code' => 200, 'html' => $htmlData);
    }

    //ORDER 마치고 계속 진행. single 도 변경 되어야 하고 shop.lib등.. 레거시 소스를 다 뜯어야함.
    public function getUserSelectOptionAllList(){
        $util = barryUtil::singletonMethod();
        $filter = barryFilter::singletonMethod();
        $orderUtil = barryOrderUtil::singletonMethod();
        $db = barryDb::singletonMethod();
        $barrydb = $db->init();

        $targetPostData = array(
            'itemId' => 'integerNotEmpty',
            'tableId' =>'stringNotEmpty',
        );
        $filterData = $filter->postDataFilter($this->data,$targetPostData);
        unset($this->data,$filter);

        $boardInfo = $util->getGbBoard($filterData['tableId']);
        //goods(Item) 정보를 불러오는건 캐시를 적용 할 검토가 필요함.
        $itemInfo = $barrydb->createQueryBuilder()
            ->select('wr_id, it_option_subject')
            ->from('g5_write_'.$boardInfo['bo_table'])
            ->where('wr_id = ?')
            ->andWhere('it_me_table = ?')
            ->setParameter(0,$filterData['itemId'])
            ->setParameter(1,$boardInfo['bo_table'])
            ->execute()->fetch();
        if(!$itemInfo){
            $this->logger->error('getUserSelectOptionAllList not found item info');
            throw new Exception('상품 정보가 없습니다.',406);
        }
        $optionInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_shop_item_option')
            ->where('io_type = 0')
            ->andWhere('it_id = ?')
            ->andWhere('io_use = 1')
            ->andWhere('io_me_table = ?')
            ->setParameter(0,$filterData['itemId'])
            ->setParameter(2,$boardInfo['bo_table'])
            ->orderBy('io_no','ASC')
            ->execute()->fetchAll();
        if(!$optionInfo){
            $this->logger->error('getUserSelectOptionAllList not found selectOption info');
            throw new Exception('선택 옵션 정보가 없습니다.',406);
        }

        $optionFilterData = array();
        //선택 옵션 제목 분리
        $selectOptionSubjectArray = explode(',',$itemInfo['it_option_subject']);
        //선택 옵션 개수를 기준으로 option Value 삽입.
        foreach ($selectOptionSubjectArray as $key => $value){
            //비어 있는 경우 배열 unset
            if(empty($value)){
                unset($selectOptionSubjectArray[$key]);
            }
            else{
                array_push($optionFilterData,array('firstOptionTitle'=>$value));
            }

        }

        $optionFilterDataCount = count($optionFilterData);
        $optionCheck = array();//중복 된 option value는 제외 하기 위한 체크.
        foreach ($optionInfo as $key => $value){
            $optionValueArray = explode(chr(30),$value['io_id']);

            //io_id chr30으로 분리해서 분리된 항목들 build
            foreach ($optionValueArray as $key2 => $value2){
                //선택 옵션 제목 만큼만 저장,
                if($optionFilterDataCount >= $key2){
                    if(!in_array($value2,$optionCheck[$key2])) {
                        //배열 선언 축약형은 배열 선언이 안되었을때만 사용하기. 소스가 너무 길어짐...
                        $optionFilterData[$key2]['optionList'][] = array(
                            'optionValue' => $value2,
                        );
                        $optionCheck[$key2][] = $value2;
                    }
                }
            }
        }
        var_dump($optionFilterData);
        unset($selectOptionSubjectArray,$optionCheck);


        exit();

    }

    private function getHtmlForm(array $array, string $type = 'none'){
        $templates = new Engine(BARRY_API_VIEW_ROOT_PATH.'/goods', 'html');
        if($type == 'upload'){
            return $templates->render('sellerUploadSelectOptionForm.skin', ['data' => $array]);
        }
        else if($type == 'modify'){
            return $templates->render('sellerModifySelectOptionForm.skin', ['data' => $array]);
        }
        else if($type == 'userViewSingle'){
            return $templates->render('userViewSelectOptionFormSingle.skin', ['data' => $array]);
        }
        else if($type == 'userViewAll'){
            return $templates->render('userViewSelectOptionFormAll.skin', ['data' => $array]);
        }
        else{
            throw new Exception('올바른 방법으로 이용해 주십시오.',9999);
        }

    }
}