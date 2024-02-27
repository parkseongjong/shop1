<?php

namespace barry\coupon;

use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \Exception;

use \barry\coupon\PremiumInterface;

class Premium implements PremiumInterface{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData = false, $memberId = false, $logger = false){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    /*
     * insert : 이미 삽입된 데이터, 삭제되거나 품절된 데이터를 제외하고 삽입만 수행 (인자값이 있는 경우는 단일 (single) 삽입을 수행, 없는 경우 전체)
     */
    public function adItemInsert($gbId = false){
        try {

            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            /*
             * 1. 프리미엄 회원 전체 또는 특정 ( $gbId 인자 값은 모두 필터링 된 데이터가 들어옴)
             * 2. 프리미엄 회원들 마다 item 서치 (삭제된 item, 품절 item 은 서치에서 제외)
             * 3. 삽입,
             */

            //주의 여기선 member info where이 mb_id가 아닌 mb_no로 고유 id로 where 함.
            $this->logger->info('GB member 조회!');
            $memberInfoQueryBuild = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_10 = ?')
                ->setParameter(0, 'premium');
                if($gbId !== false){
                    $memberInfoQueryBuild
                    ->andWhere('mb_no = ?')
                    ->setParameter(1, $gbId);
                }
                $memberInfo = $memberInfoQueryBuild
                ->execute()->fetchAll();
            if (!$memberInfo) {
                $this->logger->error('not premium member select');
                throw new Exception('프리미엄 회원이 존재하지 않습니다.', 9999);
            }


            $oldItemTable = array('Shop', 'market', 'estate', 'car');
            $itemInfo = array();

            $this->logger->info('barry_item_ads 삽입 데이터 build');
            //barry_item_ads 삽입 데이터 build
            foreach ($memberInfo as $key => $value){
                $itemInfoTempCheck = false;
                //gb 고유 id 값으로 4개 테이블(old) Item 조회 (차집합)
                foreach ($oldItemTable as $key2 => $value2) {
                    $itemInfoTemp = $barrydb->createQueryBuilder()
                        ->select('A.wr_id')
                        ->from('g5_write_' . $value2,'A')
                        ->leftJoin('A','barry_item_ads', 'B','A.wr_id = B.bia_item_id')
                        ->where('A.mb_id = ?')
                        ->andWhere('A.del_yn = ?')
                        ->andWhere('A.it_soldout = ?')
                        ->andWhere('B.bia_item_id is null')
                        ->andWhere('A.it_publish = ?')
                        ->setParameter(0, $memberInfo[$key]['mb_id'])
                        ->setParameter(1, 'N')
                        ->setParameter(2, '0')
                        ->setParameter(3, '1')
                        ->execute()->fetchAll();

                    if($itemInfoTemp){
                        $itemInfoTempCheck = true;
                        $itemInfo[$memberInfo[$key]['mb_no']][$value2] = $itemInfoTemp;
                    }
//                    array_push($itemInfo[$memberInfo[$key]['mb_no']],$itemInfoTemp);
                }
                if($itemInfoTempCheck === true){
                    //CTC mb Id도 별도로 넣어주기...
                    $itemInfo[$memberInfo[$key]['mb_no']]['ctcMbId'] = $memberInfo[$key]['mb_2'];
                }
            }
            unset($itemInfoTemp,$itemInfoTempCheck);
            /*
                array(2) {
                    [6126]=>
                      array(5) {
                        ["Shop"]=>
                        array(5) {
                          [0]=>
                          array(1) {
                            ["wr_id"]=>
                            string(3) "456"
                          }
                        }
                        ["market"]=>
                        array(0) {
                        }
                        ["estate"]=>
                        array(0) {
                        }
                        ["car"]=>
                        array(0) {
                        }
                        ["ctcMbId"]=>
                        string(0) ""
                      }
                }
             */
            //OLD 기준 입니다.
            foreach($itemInfo as $key => $value){// GB 고유 id 영역
                foreach ($value as $key2 => $value2) { // table 영역
                    if($key2 != 'ctcMbId'){
                        foreach ($value2 as $key3 => $value3){ // wr_id 영역 ( item 고유 id)
                            $barrydb->createQueryBuilder()
                                ->insert('barry_item_ads')
                                ->setValue('bia_mb_id', '?')
                                ->setValue('bia_ctc_mb_id', '?')
                                ->setValue('bia_datetime', '?')
                                ->setValue('bia_item_id', '?')
                                ->setValue('bia_type', '?')
                                ->setValue('bia_use', '?')
                                ->setParameter(0,$key)
                                ->setParameter(1,$value['ctcMbId'])
                                ->setParameter(2,$util->getDateSql())
                                ->setParameter(3,$value3['wr_id'])
                                ->setParameter(4,$key2)
                                ->setParameter(5,1)
                                ->execute();
                        }
                    }
                }
            }

            if(!$itemInfo){
                $this->logger->error('not new add items');
            }

        }
        catch (Exception $e){
            //return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
            return true;
        }
    }

    /*
     * update : 이미 삭제된 item 이나 , 품절된 item은 사용 안함으로 업데이트 수행, (기존에 적재된 데이터를 삭제할 수 없기 때문.)
     */
    public function adItemUpdate(){
        try {

            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $oldItemTable = array('Shop', 'market', 'estate', 'car');
            $itemInfo = array();

            $this->logger->info('barry_item_ads 업데이트 데이터 build');
            //barry_item_ads 업데이트 데이터 build
            //gb 고유 id 값으로 4개 테이블(old) Item 조회
            //disable 영역 0:미승인 상품 90: 반려 상품
            foreach ($oldItemTable as $key => $value) {
                $itemInfoTemp = $barrydb->createQueryBuilder()
                    ->select('B.bia_id')
                    ->from('g5_write_' . $value,'A')
                    ->innerJoin('A','barry_item_ads', 'B','A.wr_id = B.bia_item_id AND B.bia_use = ? AND B.bia_type = ?')
                    ->where('A.del_yn = ?')
                    ->orWhere('A.it_soldout = ?')
                    ->orWhere('A.it_publish = ?')
                    ->orWhere('A.it_publish = ?')
                    ->setParameter(1, 1)
                    ->setParameter(2, $value)
                    ->setParameter(3, 'Y')
                    ->setParameter(4, '1')
                    ->setParameter(5, '0')
                    ->setParameter(6, '90')
                    ->execute()->fetchAll();

                if($itemInfoTemp){
                    $itemInfo[$value] = $itemInfoTemp;
                }
            }
            unset($itemInfoTemp);
            foreach($itemInfo as $key => $value){// table 영역
                foreach ($value as $key2 => $value2) { // wr_id 영역
                    //var_dump($value2['bia_id']);
                    $updateProc = $barrydb->createQueryBuilder()
                        ->update('barry_item_ads')
                        ->set('bia_use', '?')
                        ->where('bia_id = ?')
                        ->setParameter(0,'0')
                        ->setParameter(1,$value2['bia_id'])
                        ->execute();
                       if(!$updateProc){
                           $this->logger->error('adItemUpdate fail!!!'.$value2['bia_id']);
                       }
                }
            }
            if(!$itemInfo){
                $this->logger->error('not update items');
            }

            $itemInfo = array();
            //enable 영역
            foreach ($oldItemTable as $key => $value) {
                $itemInfoTemp = $barrydb->createQueryBuilder()
                    ->select('B.bia_id')
                    ->from('g5_write_' . $value,'A')
                    ->innerJoin('A','barry_item_ads', 'B','A.wr_id = B.bia_item_id AND B.bia_use = ? AND B.bia_type = ?')
                    ->where('A.del_yn = ?')
                    ->andWhere('A.it_soldout = ?')
                    ->andWhere('A.it_publish = ?')
                    ->setParameter(1, 0)
                    ->setParameter(2, $value)
                    ->setParameter(3, 'N')
                    ->setParameter(4, '0')
                    ->setParameter(5, '1')
                    ->execute()->fetchAll();

                if($itemInfoTemp){
                    $itemInfo[$value] = $itemInfoTemp;
                }
            }
            unset($itemInfoTemp);

            foreach($itemInfo as $key => $value){// table 영역
                foreach ($value as $key2 => $value2) { // wr_id 영역
                    $updateProc = $barrydb->createQueryBuilder()
                        ->update('barry_item_ads')
                        ->set('bia_use', '?')
                        ->where('bia_id = ?')
                        ->setParameter(0,'1')
                        ->setParameter(1,$value2['bia_id'])
                        ->execute();
                       if(!$updateProc){
                           $this->logger->error('adItemUpdate fail!!!'.$value2['bia_id']);
                       }
                }
            }
            if(!$itemInfo){
                $this->logger->error('not update items');
            }

        }
        catch (Exception $e){
            //return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
            return (array('code'=>$e->getCode(), 'msg'=>$e->getMessage()));
            return true;
        }
    }

    public function adItemCheck(){
        try{
            /*
             * 1. ad table에 있는 item들 프리미엄 만료 되었는지 조회
             * 2. 만료된 데이터들은 비활성화 처리
             * why 삭제 처리를 안하는가? : 이미 수집된 데이터를 삭제 할 수 는 없기 때문에 비활성화 처리
             */
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();


            $this->logger->info('item ad 조회!');
            $itemAdsInfo = $barrydb->createQueryBuilder()
                ->select('A.*, B.mb_10')
                ->from('barry_item_ads', 'A')
                ->innerJoin('A','g5_member','B','A.bia_mb_id = B.mb_no')
                ->where('A.bia_use = 1')
                ->andWhere('B.mb_10 != "premium"')
                ->orWhere('B.mb_10 is null')
                ->execute()->fetchAll();

            if (!$itemAdsInfo) {
                $this->logger->error('not item ad select');
                throw new Exception('광고 item이 존재 하지 않습니다.', 9999);
            }

            foreach ($itemAdsInfo as $key=>$value){
                $updateProc = $barrydb->createQueryBuilder()
                    ->update('barry_item_ads')
                    ->set('bia_use', 0)
                    ->where('bia_id = ?')
                    ->setParameter(0,$value['bia_id'])
                    ->execute();
            }

        }
        catch (Exception $e){
            return true;
        }
    }

    public function adItemLogInsert($biaId = false, $type = false){
        try{
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            //type 부분은 따로 필터링 할 필요는 아직 까지 없는 것 같음, 추 후 필요로 한다면 필터 방법을 생각해 보자,
            /*
             * view 또는 click log insert
             */


            if($this->memberId !== false){
                $memberInfo = $barrydb->createQueryBuilder()
                    ->select('mb_no, mb_2')
                    ->from('g5_member')
                    ->where('mb_id = ?')
                    ->setParameter(0, $this->memberId)
                    ->execute()->fetch();
                if (!$memberInfo) {
                    $this->logger->error('adItemLog insert member select fail');
                    throw new Exception('adItemLog insert member select fail', 9999);
                }
            }
            else{
                $memberInfo = array(
                    'mb_no' => 'guest',
                    'mb_2' => 'guest'
                );
            }

            $barrydb->createQueryBuilder()
                ->insert('barry_item_ads_log')
                ->setValue('bia_id', '?')
                ->setValue('bial_mb_id', '?')
                ->setValue('bial_ctc_mb_id', '?')
                ->setValue('bial_datetime', '?')
                ->setValue('bial_type', '?')
                ->setValue('bial_user_agent', '?')
                ->setValue('bial_ip', '?')
                ->setParameter(0, $biaId)
                ->setParameter(1, $memberInfo['mb_no'])
                ->setParameter(2, $memberInfo['mb_2'])
                ->setParameter(3, $util->getDateSql())
                ->setParameter(4, $type)
                ->setParameter(5,isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'NOT FOUND')
                ->setParameter(6,isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NOT FOUND')
                ->execute();
        }
        catch (Exception $e){
            return true;
        }

    }

    public function adItemReport(){
        try{
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $this->logger->alert('item ads report start');
            //log 데이터 합산 할 때 .. 음... datetime 비교문도 추가 해야 함.... 금일 수정한걸 더 수정 할 순 없잖아~?
            //아 ... report 호출 시 대상 날짜가 현재 날짜 뒤에 있어야 함.
            $todayDatetime = $util->getDateSql();
            $previousDatetime = date('Y-m-d', strtotime($todayDatetime.'-1 days'));

            //금일 리포팅 된 데이터가 1개라도 있으면... 리포트는 수행하지 않는다.
            $itemAdsReportInfo = $barrydb->createQueryBuilder()
                ->select('biar_id')
                ->from('barry_item_ads_report')
                ->where('date(biar_datetime) = ?')
                ->setParameter(0,date('Y-m-d', strtotime($todayDatetime)))
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->execute()->fetch();
            if($itemAdsReportInfo){
                $this->logger->error('[adItemReport]the duplicate check report');
                throw new Exception('adItemReport를 금일 이미 수행 하였습니다.',9999);
            }



            $itemAdsInfo = $barrydb->createQueryBuilder()
                ->select('bia_id, bia_view_rate, bia_click_rate')
                ->from('barry_item_ads')
                ->execute()->fetchAll();

            //view , click count 등 report에 필요한 항목 빌드 후 ads에 view, click 값 update. report
            //SQL에서 build 값 수행? 아니면 전체를 가져와서, php 에서 build 수행 ?
            foreach ($itemAdsInfo as $key => $value){
                $buildData = array();

                for($i=0; $i<=3; $i++){
                    $mainBuilder = $barrydb->createQueryBuilder()
                        ->select('*')
                        ->from('barry_item_ads_log')
                        ->where('bia_id = ?')
                        ->andWhere('date(bial_datetime) = ?')
                        ->setParameter(0,$value['bia_id'])
                        ->setParameter(1,$previousDatetime);

                    if($i==0){
                        $buildData[$i]['memberType'] = 'total';
                        $buildData[$i]['count'] = $mainBuilder
                            ->andWhere('bial_type = ?')
                            ->setParameter(2,'view')
                            ->execute()->rowCount();
                        $buildData[$i]['type'] = 'view';
                    }
                    else if($i==1){
                        $buildData[$i]['memberType'] = 'total';
                        $buildData[$i]['count'] = $mainBuilder
                            ->andWhere('bial_type = ?')
                            ->setParameter(2,'click')
                            ->execute()->rowCount();
                        $buildData[1]['type'] = 'click';
                    }
                    else if($i==2){
                        $buildData[$i]['memberType'] = 'guest';
                        $buildData[$i]['count'] = $mainBuilder
                            ->andWhere('bial_type = ?')
                            ->andWhere('bial_mb_id = ?')
                            ->andWhere('bial_ctc_mb_id = ?')
                            ->setParameter(2,'view')
                            ->setParameter(3,'guest')
                            ->setParameter(4,'guest')
                            ->execute()->rowCount();
                        $buildData[$i]['type'] = 'view';
                    }
                    else if($i==3){
                        $buildData[$i]['memberType'] = 'guest';
                        $buildData[$i]['count'] = $mainBuilder
                            ->andWhere('bial_type = ?')
                            ->andWhere('bial_mb_id = ?')
                            ->andWhere('bial_ctc_mb_id = ?')
                            ->setParameter(2,'click')
                            ->setParameter(3,'guest')
                            ->setParameter(4,'guest')
                            ->execute()->rowCount();
                        $buildData[$i]['type'] = 'click';
                    }
                }

                //report에 데이터 삽입,
                $tempViewCount = $tempClickCount = 0;
                foreach ($buildData as $buildDataKey => $buildDataKeyValue){
                    $insertProc = $barrydb->createQueryBuilder()
                        ->insert('barry_item_ads_report')
                        ->setValue('bia_id', '?')
                        ->setValue('biar_datetime', '?')
                        ->setValue('biar_rate', '?')
                        ->setValue('biar_type', '?')
                        ->setValue('biar_member_type', '?')
                        ->setParameter(0,$value['bia_id'])
                        ->setParameter(1,$util->getDateSql())
                        ->setParameter(2, $buildDataKeyValue['count'])
                        ->setParameter(3, $buildDataKeyValue['type'])
                        ->setParameter(4, $buildDataKeyValue['memberType'])
                        ->execute();
                    if(!$insertProc){
                        $this->logger->error('[adItemReport]item ads report insert proc fail /bia_id:'.$value['bia_id']);
                    }

                    if($buildDataKeyValue['type'] == 'view' && $buildDataKeyValue['memberType'] == 'total'){
                        $tempViewCount = $tempViewCount + $buildDataKeyValue['count'];
                    }
                    else if($buildDataKeyValue['type'] == 'click' && $buildDataKeyValue['memberType'] == 'total'){
                        $tempClickCount = $tempClickCount + $buildDataKeyValue['count'];
                    }
                }

                //count 값이 0이 라면 굳이 update를 하지 않는다.
                if($tempViewCount != 0 || $tempClickCount != 0){
                    $tempViewCount = $value['bia_view_rate'] + $tempViewCount;
                    $tempClickCount = $value['bia_click_rate'] + $tempClickCount;

                    $updateProc = $barrydb->createQueryBuilder()
                        ->update('barry_item_ads')
                        ->set('bia_view_rate','?')
                        ->set('bia_click_rate','?')
                        ->set('bia_update_datetime','?')
                        ->where('bia_id = ?')
                        ->setParameter(0,$tempViewCount)
                        ->setParameter(1,$tempClickCount)
                        ->setParameter(2,$util->getDateSql())
                        ->setParameter(3,$value['bia_id'])
                        ->execute();
                    if(!$updateProc){
                        $this->logger->error('[adItemReport]item ads view/click update proc fail /bia_id:'.$value['bia_id']);
                    }
                }
            }
            $this->logger->alert('item ads report END');
        }
        catch (Exception $e){
            return true;
            //return var_dump($e->getMessage());
        }
    }

}

?>