<?php

namespace barry\admin;

use \barry\common\Filter as barryFilter;
use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Goods{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $logger){
        $this->data = $postData;
        $this->logger = $logger;
    }

    //cybertron admin/main 갯수 뿌려주는 메소드
    public function goodsPublishCount(){

        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();


            //정상 상품 개수
            $publishStatus = 0;
            $deleteStatus = "Y";

            $query = $tempTeble = $tempWhere = '';
            $queryDelN = $tempTebleDelN = $tempWhereDelN = '';

            $tempParams = $tempParamsDelN = array();

            $oldItemTable = array('Shop', 'offline','market', 'estate', 'car');
            //전체 카테고리 빌드
            foreach ($oldItemTable as $key => $value) {
                //정상 상품 미승인
                if($key == 0){
                    $tempWhere = (' WHERE it_publish = ? AND del_yn = ? ');
                    array_push($tempParams,$publishStatus,$deleteStatus);
                    $query = ('SELECT * FROM g5_write_'.$value.' '.$tempWhere);

                }
                else{
                    $tempWhere = (' WHERE it_publish = ? AND del_yn = ?');
                    array_push($tempParams,$publishStatus,$deleteStatus);

                    $query .=('union all 
                                (
                                    SELECT *
                                    FROM g5_write_'.$value.' '.$tempWhere.'
                                )
                    ');
                }
            }

            //삭제된 상품의 개수
            $publishStatus = 0;
            $deleteStatus = "N";

            foreach ($oldItemTable as $key => $value) {
                //삭제 상품 미승인
                if($key == 0){
                    $tempTebleDelN = (' WHERE it_publish = ? AND del_yn = ?');
                    array_push($tempParamsDelN,$publishStatus,$deleteStatus);

                    $queryDelN = ('SELECT * FROM g5_write_'.$value.' '.$tempTebleDelN.' ');

                }
                else{
                    $tempTebleDelN = (' WHERE it_publish = ? AND del_yn = ?');
                    array_push($tempParamsDelN,$publishStatus,$deleteStatus);

                    $queryDelN .=('union all 
                                (
                                    SELECT *
                                    FROM g5_write_'.$value.' '.$tempTebleDelN.'
                                )
                    ');
                }
            }

            $tempTeble = $query;
            $tempTebleDelN = $queryDelN;

            try {
                //rows 제한 잡히기 전에 전체 rows 리턴
                $goodsInfoTotalCount = $barrydb->executeQuery($tempTeble, $tempParams)->rowCount();
                $goodsInfoTotalCountDelN = $barrydb->executeQuery($tempTebleDelN, $tempParamsDelN)->rowCount();
            }
            catch (Exception $e){
                //존재하지 않는 table을 검색 했을 때는 error 로그만 남겨준다.
                $this->logger->error('goods select error/code'.$e->getCode().'/'.'msg'.$e->getMessage());
            }
            unset($tempTeble, $tempTebleDelN, $tempParams, $tempParamsDelN, $oldItemTable);

            $returnArray = array(
                'countDelY' => $goodsInfoTotalCount,
                'countDelN' => $goodsInfoTotalCountDelN,
            );

            $this->logger->alert('미승인 상품을 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('미승인 상품을 variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function getMultiItem(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'page' => 'integer',
                'numRows' => 'integer',
                'orderKey' => 'string',
                'orderDir' => 'string',
                'searchKeyword' => 'string',
                'publishStatus' => 'stringNotEmpty',
                'deleteStatus' => 'stringNotEmpty',
                'table' => 'stringNotEmpty',
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    if($key == 'searchKeyword' && $value === false){
                        //서치 키워드가 없을 때는 그대로 false 처리
                        $filterData['searchKeyword'] = $value;
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }

                }
            }
            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);

            if($filterData['deleteStatus'] == 'Y'){
                $deleteStatus = 'Y';
            }
            else if($filterData['deleteStatus'] == 'N'){
                $deleteStatus = 'N';
            }
            else{
                $this->logger->error('getMultiItem valid fail!');
                throw new Exception('잘못 된 데이터가 유입 되었습니다.', 9999);
            }

            if($filterData['publishStatus'] == 'publish'){
                $publishStatus = 1;
            }
            else if($filterData['publishStatus'] == 'unpublish'){
                $publishStatus = 0;
            }
            else if($filterData['publishStatus'] == 'reject'){
                $publishStatus = 90;
            }
            else{
                $this->logger->error('getMultiItem valid fail!');
                throw new Exception('잘못 된 데이터가 유입 되었습니다.', 9999);
            }

            // getMultiItem 빌드
            $query = $tempTeble = $tempWhere = '';
            $tempParams = array();
            $oldItemTable = array('Shop', 'offline','market', 'estate', 'car');
            //전체 카테고리 빌드
            if($filterData['table'] == 'all-category'){
                foreach ($oldItemTable as $key => $value) {
                    if($key == 0){
                        if (!empty($filterData['publishStatus'])) {
                            $tempWhere = (' WHERE it_publish = ?');
                            array_push($tempParams,$publishStatus);
                        }

                        if (!empty($filterData['deleteStatus'])) {
                            $tempWhere .= (' AND del_yn = ?');
                            array_push($tempParams,$deleteStatus);
                        }

                        if (!empty($filterData['searchKeyword'])) {
                            $tempWhere .= (' AND wr_subject like ?');
                            array_push($tempParams,'%'.$filterData['searchKeyword'].'%');
                        }

                        $query = ('SELECT * FROM g5_write_'.$value.' '.$tempWhere);

                    }
                    else{
                        if (!empty($filterData['publishStatus'])) {
                            $tempWhere = (' WHERE it_publish = ?');
                            array_push($tempParams,$publishStatus);
                        }

                        if (!empty($filterData['deleteStatus'])) {
                            $tempWhere .= (' AND del_yn = ?');
                            array_push($tempParams,$deleteStatus);
                        }

                        if (!empty($filterData['searchKeyword'])) {
                            $tempWhere .= (' AND wr_subject like ?');
                            array_push($tempParams,'%'.$filterData['searchKeyword'].'%');
                        }

                        $query .=('union all 
                                    (
                                        SELECT *
                                        FROM g5_write_'.$value.' '.$tempWhere.'
                                    )
                        ');
                    }
                }

                $tempTeble = $query;
                if (!empty($filterData['orderKey']) && !empty($filterData['orderDir'])) {
                    $tempTeble .= (' ORDER BY ? ?, wr_id desc');
                    array_push($tempParams,$filterData['orderKey']);
                    array_push($tempParams,$filterData['orderDir']);
                }
                else{
                    $tempTeble .= (' ORDER BY ? ?');
                    array_push($tempParams,'wr_id');
                    array_push($tempParams,'desc');
                }
//                var_dump($tempTeble);
//                var_dump($tempParams);

                try {
                    //rows 제한 잡히기 전에 전체 rows 리턴
                    $goodsInfoTotalCount = $barrydb->executeQuery($tempTeble, $tempParams)->rowCount();

                    //inline execute PDO 제한으로 , linit 절에서는 bind를 지원하지 못함.
                    $tempTeble .= (' LIMIT '.(int)($filterData['page']-1)*$filterData['numRows'].', '.(int)$filterData['numRows']);

                    $goodsInfo = $barrydb->executeQuery($tempTeble, $tempParams)->fetchAll();
                }
                catch (Exception $e){
                    //존재하지 않는 table을 검색 했을 때는 error 로그만 남겨준다.
                    $this->logger->error('goods select error/code'.$e->getCode().'/'.'msg'.$e->getMessage());
                }
                unset($temp, $tempTeble, $oldItemTable);
            }//단일 카테고리 빌드
            else{
                if(in_array($filterData['table'],$oldItemTable)){
                    //단일 테이블은 쿼리빌더로...
                    $goodsInfoQueryBuilder = $barrydb->createQueryBuilder();
                    $goodsInfoQueryBuilder
                        ->select('*')
                        ->from('g5_write_'.$filterData['table']);
                    if (!empty($filterData['publishStatus'])) {
                        $goodsInfoQueryBuilder
                            ->andWhere('it_publish = ?')
                            ->setParameter(0, $publishStatus);
                    }
                    if (!empty($filterData['deleteStatus'])) {
                        $goodsInfoQueryBuilder
                            ->andWhere('del_yn = ?')
                            ->setParameter(1, $deleteStatus);
                    }
                    if (!empty($filterData['searchKeyword'])) {
                        $goodsInfoQueryBuilder
                            ->andWhere('wr_subject like ?')
                            ->setParameter(1, '%' . $filterData['searchKeyword'] . '%');
                    }
                    if (!empty($filterData['orderKey']) && !empty($filterData['orderDir'])) {
                        $goodsInfoQueryBuilder
                            ->addOrderBy($filterData['orderKey'], $filterData['orderDir']);
                    }
                    else{
                        $goodsInfoQueryBuilder
                            ->orderBy('wr_id', 'desc');
                    }

                    //rows 제한 잡히기 전에 전체 rows 리턴
                    $goodsInfoTotalCount = $goodsInfoQueryBuilder->execute()->rowCount();

                    $goodsInfo = $goodsInfoQueryBuilder
                        ->setFirstResult(($filterData['page'] - 1) * $filterData['numRows'])
                        ->setMaxResults($filterData['numRows'])
                        ->execute()->fetchAll();

                    unset($goodsInfoQueryBuilder);

                }
                else{
                    $this->logger->error('getMultiItem select error');
                    throw new Exception('유효한 table 값이 아닙니다.', 9999);
                }
            }

            if (!$goodsInfo) {
                $this->logger->error('getMultiItem select error(2)');
                throw new Exception('상품 정보를 불러오지 못하였습니다.', 9999);
            }

            //선택 옵션이 있는 경우에는 선택 옵션 값도 넘겨 줘야 함...
            foreach ($goodsInfo as $key => $value){
                if($value['it_option_subject']){
                    $goodsInfoOption = $barrydb->createQueryBuilder()
                        ->select('A.wr_id, A.it_me_table, B.*')
                        ->from('g5_write_'.$value['it_me_table'],'A')
                        ->innerJoin('A','g5_shop_item_option','B','A.wr_id = B.it_id')
                        ->where('A.wr_id = ?')
                        ->andWhere('B.io_me_table = ?')
                        ->setParameter(0,$value['wr_id'])
                        ->setParameter(1,$value['it_me_table'])
                        ->execute()->fetchAll();

                    $goodsInfo[$key]['optionInfo'] = $goodsInfoOption;
                }
                else{
                    $goodsInfo[$key]['optionInfo'] = false;
                }
            }
            //이미지 경로 build,
            foreach ($goodsInfo as $key => $value){
                $goodsImgInfo = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('g5_board_file')
                    ->where('wr_id = ?')
                    ->andWhere('bo_table = ?')
                    ->setParameter(0, $value['wr_id'])
                    ->setParameter(1, $value['it_me_table'])
                    ->execute()->fetchAll();
                if(!$goodsImgInfo){
                    $goodsInfo[$key]['imgUrl'] = false;
                }
                else{
                    $tempArray = array();
                    foreach ($goodsImgInfo as $key2 => $value2){
                        array_push($tempArray, 'https://barrybarries.kr/data/file/'.$value['it_me_table'].'/'.$value2['bf_file']);
                    }
                    $goodsInfo[$key]['imgUrl'] = $tempArray;
                    unset($tempArray);
                }
            }


//            var_dump($goodsInfoTotalCount);

            //count는 전체 카운트를..
            $returnArray = array(
                'count' => $goodsInfoTotalCount,
                'list' => $goodsInfo,
            );

            $this->logger->alert('getMultiItem를 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin getMultiItem를 variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function getSingleItem(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }

            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);


            // getSingleItem 빌드
            $goodsInfoQueryBuilder = $barrydb->createQueryBuilder();
            $goodsInfoQueryBuilder
                ->select('*')
                ->from('g5_write_'.$filterData['table'])
                ->where('wr_id = ?')
                ->setParameter(0,  $filterData['id'] );

            //rows 제한 잡히기 전에 전체 rows 리턴
            $goodsInfoTotalCount = $goodsInfoQueryBuilder->execute()->rowCount();

            $goodsInfo = $goodsInfoQueryBuilder
                ->execute()->fetch();

            unset($goodsInfoQueryBuilder);

            if (!$goodsInfo) {
                $this->logger->error('getSingleItem select error');
                throw new Exception('상품 정보를 불러오지 못하였습니다.', 9999);
            }

            //이미지 경로 build,
            $goodsInfoQueryBuilder = $barrydb->createQueryBuilder();
            $goodsImgInfo = $goodsInfoQueryBuilder
                ->select('*')
                ->from('g5_board_file')
                ->where('wr_id like ?')
                ->andWhere('bo_table like ?')
                ->setParameter(0,  $filterData['id'] )
                ->setParameter(1,  $filterData['table'] )
                ->execute()->fetchAll();
            if(!$goodsImgInfo){
                $goodsInfo['imgUrl'] = false;
            }
            else{
                $tempArray = array();
                foreach ($goodsImgInfo as $key => $value){
                    array_push($tempArray, 'http://barrybarries.kr/data/file/'.$filterData['table'].'/'.$value['bf_file']);
                }
                $goodsInfo['imgUrl'] = $tempArray;
                unset($tempArray);
            }

            $returnArray = array(
                'count' => $goodsInfoTotalCount,
                'list' => $goodsInfo,
            );

            $this->logger->alert('getSingleItem를 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin getSingleItem variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }


    //승인 처리
    public function publishItem(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }

            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:',$filterData);

            // item 빌드
            $goodsInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_'.$filterData['table'])
                ->where('wr_id = ?')
                ->andWhere('it_publish = 1')
                ->setParameter(0,  $filterData['id'] )
                ->execute()->fetch();
            if ($goodsInfo) {
                $this->logger->error('publishItem select error');
                throw new Exception('이미 승인 처리 되었습니다. 상품 정보를 불러오지 못하였습니다.', 9999);
            }

            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_'.$filterData['table'])
                ->set('it_publish', 1)
                ->set('it_publish_updatetime' , '?')
                ->where('wr_id = ?')
                ->setParameter(0,$util->getDateSql())
                ->setParameter(1,$filterData['id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('cpublishItem update proc fail');
                throw new Exception('상품 승인 작업을 실패 하였습니다.',9999);
            }
            $returnArray = array(
                'publishCode' => 200,
                'publishMsg' => '상품 승인 처리가 완료 되었습니다.',
            );
            $this->logger->alert('publishItem 완료!'.$filterData['id'].$filterData['table']);
            return array('code' => 200, 'data' => $returnArray);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin publishItem variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function unpublishItem(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }

            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:'.$filterData);

            // item 빌드
            $goodsInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_'.$filterData['table'])
                ->where('wr_id = ?')
                ->andWhere('it_publish = 0')
                ->setParameter(0,  $filterData['id'] )
                ->execute()->fetch();
            if ($goodsInfo) {
                $this->logger->error('unpublishItem select error');var_dump($goodsInfo);
                throw new Exception('이미 미승인 처리 되었습니다. 상품 정보를 불러오지 못하였습니다.', 9999);
            }

            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_'.$filterData['table'])
                ->set('it_publish', 0)
                ->set('it_publish_updatetime' , '?')
                ->where('wr_id = ?')
                ->setParameter(0,$util->getDateSql())
                ->setParameter(1,$filterData['id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('cpublishItem update proc fail');
                throw new Exception('상품 미승인 작업을 실패 하였습니다.',9999);
            }
            $returnArray = array(
                'publishCode' => 200,
                'publishMsg' => '상품 미승인 처리가 완료 되었습니다.',
            );
            $this->logger->alert('unpublishItem 완료!'.$filterData['id'].$filterData['table']);
            return array('code' => 200, 'data' => $returnArray);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin unpublishItem variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function reject(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
                'type' => 'stringNotEmpty',
                'reason' => 'string',
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:'.$filterData);

            // item 빌드
            $goodsInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_write_'.$filterData['table'])
                ->where('wr_id = ?')
                ->andWhere('it_publish = 90')
                ->setParameter(0,  $filterData['id'] )
                ->execute()->fetch();
            if ($goodsInfo) {
                $this->logger->error('reject select error');
                throw new Exception('이미 반려 처리 되었거나 상품 정보를 불러오지 못하였습니다.', 9999);
            }

            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_'.$filterData['table'])
                ->set('it_publish', 90)
                ->set('it_publish_updatetime' , '?')
                ->set('it_publish_msg' , '?')
                ->where('wr_id = ?')
                ->setParameter(0,$util->getDateSql())
                ->setParameter(1,$filterData['reason'])
                ->setParameter(2,$filterData['id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('reject update proc fail');
                throw new Exception('상품 반려 작업을 실패 하였습니다.',9999);
            }
            $returnArray = array(
                'publishCode' => 200,
                'publishMsg' => '상품 반려 처리가 완료 되었습니다.',
            );
            $this->logger->alert('reject 완료!'.$filterData['id'].$filterData['table']);
            return array('code' => 200, 'data' => $returnArray);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin reject variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function tempModify(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
                'data' => 'stringNotEmpty'
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:'.$filterData);

            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_'.$filterData['table'])
                ->set('wr_content', '?')
                ->where('wr_id = ?')
                ->setParameter(0,$filterData['data'])
                ->setParameter(1,$filterData['id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('tempModify update proc fail');
                throw new Exception('tempModify 작업을 실패 하였습니다.',9999);
            }
            $this->logger->alert('tempModify 완료!'.$filterData['id'].$filterData['table']);
            return array('code' => 200,'msg'=>'수정 성공!');

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('tempModify valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function tempModify2(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
                'data' => 'stringNotEmpty'
            );
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data,$targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:'.$filterData);

            $updateProc = $barrydb->createQueryBuilder()
                ->update('g5_write_'.$filterData['table'])
                ->set('wr_subject', '?')
                ->where('wr_id = ?')
                ->setParameter(0,$filterData['data'])
                ->setParameter(1,$filterData['id'])
                ->execute();
            if(!$updateProc){
                $this->logger->error('tempModify update proc fail');
                throw new Exception('tempModify 작업을 실패 하였습니다.',9999);
            }
            $this->logger->alert('tempModify 완료!'.$filterData['id'].$filterData['table']);
            return array('code' => 200,'msg'=>'수정 성공!');

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('tempModify valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }

    public function tempModify3(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'table' => 'stringNotEmpty',
                'id' => 'integer',
                'files' => 'middlewareUploadFile',          // 이미지 파일 slim 미들웨어 한번 거치기 때문에 files 로 받음.
            );
            $filterData = array();
            //필터가 안되어서 일단 임시 처리
            $filterData = $filter->postDataFilter($this->data,$targetPostData);
            $filterData['files'] = $this->data['files'];
            unset($this->data,$targetPostData);// Plain data는 unset 합니다.

            $this->logger->info('필터 데이터:'.print_r($filterData,true));

            $boardInfo = array('bo_table'=>$filterData['table']);
            //기존 상품 상세 이미지는 무조건 삭제,
            $this->logger->info('기존 상품 이미지 제거');
            if(!self::itemImageDeleteItemDetail($filterData['id'], $boardInfo)){
                $this->logger->error('images files upload fail');
                throw new Exception('이전 상품 삭제를 실패하였습니다.',406);
            }

            $this->logger->info('상품 파일 정보 삽입(수정)!(상품 정보 사진)');
            if(!self::itemImageUpload('g5_write_'.$filterData['table'], $filterData['files']['data'], $filterData['id'], $boardInfo, $util->getDateSql(),'itemDetail')){
                $this->logger->error('detail images files upload fail');
                throw new Exception('상품 정보 사진 업로드를 실패하였습니다..',406);
            }

            $this->logger->alert('tempModify3 완료!'.$filterData['id'].$filterData['table']);
            return array('code' => 200,'msg'=>'수정 성공!');

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('tempModify valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            //var_dump($e->getMessage());
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
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
    private function itemImageDeleteItemDetail($targetId, $boardInfo){

        $util = barryUtil::singletonMethod();
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();
        $fileInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_board_file')
            ->where('bo_table = ?')
            ->andWhere('wr_id = ?')
            ->andWhere('bf_storage = ?')
            ->setParameter(0,$boardInfo['bo_table'])
            ->setParameter(1,$targetId)
            ->setParameter(2,'itemDetail')
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
            ->andWhere('bf_storage = ?')
            ->setParameter(0,$boardInfo['bo_table'])
            ->setParameter(1,$targetId)
            ->setParameter(2,'itemDetail')
            ->execute();
        //goods(item) file count 리셋은 안함... 관리자에서 수정 시 무조건 1개씩 수정 되기 때문.

        return true;
    }
}

?>