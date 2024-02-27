<?php
namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Banner
{
    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $logger)
    {
        $this->data = $postData;
        $this->logger = $logger;
    }

    public function bannerList(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'page' => 'integer',
                'numRows' => 'integer',
                'orderKey' => 'string',
                'orderDir' => 'string',
                'searchKeyword' => 'string',
            );
            $filterData = array();
            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    if($key == 'searchKeyword' && $value === false){
                        //서치 키워드가 없을 때는 그대로 false 처리
                        $filterData['searchKeyword'] = $value;
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data, $targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:', $filterData);
            $bannerListInfoQueryBuilder = $barrydb->createQueryBuilder();
            $bannerListInfoQueryBuilder
                ->select('*')
                ->from('barry_banner');
            if (!empty($filterData['searchKeyword'])) {
                $bannerListInfoQueryBuilder
                    ->where('bb_subject like ?')
                    ->setParameter(0, '%' . $filterData['searchKeyword'] . '%');
            }
            if (!empty($filterData['orderKey']) && !empty($filterData['orderDir'])) {
                $bannerListInfoQueryBuilder
                    ->addOrderBy($filterData['orderKey'], $filterData['orderDir']);
            }
            else{
                $bannerListInfoQueryBuilder
                    ->orderBy('bb_id', 'desc');
            }

            //rows 제한 잡히기 전에 전체 rows 리턴
            $bannerListListInfoTotalCount = $bannerListInfoQueryBuilder->execute()->rowCount();

            $bannerListInfo = $bannerListInfoQueryBuilder
                ->setFirstResult(($filterData['page'] - 1) * $filterData['numRows'])
                ->setMaxResults($filterData['numRows'])
                ->execute()->fetchAll();

            unset($bannerListInfoQueryBuilder);

            if (!$bannerListInfo) {
                $this->logger->error('banner list select error');
                throw new Exception('배너 리스트를 불러오지 못하였습니다.', 9999);
            }

            $nowIsoUnixDateTime = time();
            foreach ($bannerListInfo as $key => $value){
                //unix time 끼리 비교해서, 아닌 경우 false 를.. 같이 넘겨주기
                if($nowIsoUnixDateTime >= strtotime($value['bb_deactivation_datetime'])){
                    $bannerListInfo[$key]['activationStatus'] = false;
                 }
                else{
                    $bannerListInfo[$key]['activationStatus'] = true;
                }
            }

            $returnArray = array(
                'count' => $bannerListListInfoTotalCount,
                'list' => $bannerListInfo,
            );

            $this->logger->alert('banner list 요청 완료');
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

    //이미지 업로드 시 배너는 썸네일을 생성하지 않습니다.(관리자가 올리기 때문에?, )추 후 필요하다면 작업이 필요할 것 같습니다.
    public function bannerUpload(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'bannerSubject' => 'stringNotEmpty',
                'bannerContent' => 'stringNotEmpty',
                'bannerLocation' => 'stringNotEmpty',
                'bannerPublishLocation' => 'string',
                'bannerLink' => 'string',
                'bannerActivationDate' => 'stringNotEmpty',
                'bannerDeactivationDate' => 'stringNotEmpty',
                'uriObj' => 'object',
                'files' => 'isArray',
            );
            $filterData = array();
            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                    //파일과 uri object는 예외 처리 valid 타입인지만 확인,
                    if($key == 'files' || $key == 'uriObj'){
                        $filterData[$purifier->purify($key)] = $value;
                    }
                    else{
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data, $targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:', $filterData);

            if($filterData['bannerSubject'] == "" ){
                throw new Exception('배너 제목을 입력해 주세요.',9999);
            }

            if($filterData['bannerContent'] == "" ){
                throw new Exception('배너 내용을 입력해 주세요.',9999);
            }

            if($filterData['bannerLocation'] == 'none' ){
                throw new Exception('배너 노출 위치를 선택해 주세요.',9999);
            }

            //메인 top 선택시 bannerPublishLocation값이 없어서 넣어줌 .
            if($filterData['bannerLocation'] == 'mainTop') {
                $filterData['bannerPublishLocation'] = 'none';
            }

            if($filterData['bannerLocation'] == 'categoryTop'){
                if($filterData['bannerPublishLocation'] == 'none' ){
                    throw new Exception('배너 노출 대상을 선택해 주세요.',9999);
                }
            }
            if($filterData['bannerActivationDate']== NULL){
                throw new Exception('배너 활성화 일자를 선택해 주세요',9999);
            }

            if($filterData['bannerDeactivationDate'] == NULL){
                throw new Exception('배너 비활성화 일자를 선택해 주세요',9999);
            }

            //다중 파일은 아직 대응 하지 않음... 일단 그대로 둠.
            foreach ($filterData['files']['bannerSource'] as $key => $value){
                $uploadFileInfo = $util->slimApiMoveUploadedFile($_SERVER['DOCUMENT_ROOT'].'/data/apiBanner',$value,'image');
                if(!$uploadFileInfo){
                    $this->logger->error('banner file upload fail!');
                    throw new Exception('파일 업로드에 실패 하였습니다.',9999);
                }

                //location과 type은 따로 유효성 체크 하지 않습니다.
                if(empty($filterData['bannerLink'])){
                    $filterData['bannerLink'] = 'none';
                }

                //var_dump($uploadFileInfo);
                $barrydb->createQueryBuilder()
                    ->insert('barry_banner')
                    ->setValue('bb_activation_datetime', '?')
                    ->setValue('bb_deactivation_datetime', '?')
                    ->setValue('bb_subject', '?')
                    ->setValue('bb_content', '?')
                    ->setValue('bb_source', '?')
                    ->setValue('bb_target', '?')//5
                    ->setValue('bb_width', '?')
                    ->setValue('bb_height', '?')
                    ->setValue('bb_type', '?')
                    ->setValue('bb_size', '?')
                    ->setValue('bb_url', '?')//10
                    ->setValue('bb_location_type', '?')
                    ->setValue('bb_publish_location', '?')
                    ->setValue('bb_datetime', '?')
                    ->setValue('bb_update_datetime', '?')
                    ->setValue('bb_link', '?')//15
                    ->setValue('bb_use', '?')
                    ->setParameter(0,$filterData['bannerActivationDate'])
                    ->setParameter(1,$filterData['bannerDeactivationDate'])
                    ->setParameter(2,$filterData['bannerSubject'])
                    ->setParameter(3,$filterData['bannerContent'])
                    ->setParameter(4,$uploadFileInfo['name'])
                    ->setParameter(5,$uploadFileInfo['convertName'])
                    ->setParameter(6,$uploadFileInfo['width'])
                    ->setParameter(7,$uploadFileInfo['height'])
                    ->setParameter(8,$uploadFileInfo['extension'])
                    ->setParameter(9,$uploadFileInfo['size'])
                    ->setParameter(10,$filterData['uriObj']->getScheme().'://'.$filterData['uriObj']->getHost().'/data/apiBanner/'.$uploadFileInfo['convertName'].'.'.$uploadFileInfo['extension'])
                    ->setParameter(11,$filterData['bannerLocation'])
                    ->setParameter(12,$filterData['bannerPublishLocation'])
                    ->setParameter(13,$util->getDateSql())
                    ->setParameter(14,$util->getDateSqlDefault())
                    ->setParameter(15,$filterData['bannerLink'])
                    ->setParameter(16,1)
                    ->execute();
            }

            $this->logger->alert('배너 업로드 요청 완료');
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

    public function bannerModify(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'bannerId' => 'integer',
                'bannerSubject' => 'stringNotEmpty',
                'bannerContent' => 'stringNotEmpty',
                'bannerLocation' => 'stringNotEmpty',
                'bannerPublishLocation' => 'string',
                'bannerLink' => 'string',
                'bannerActivationDate' => 'stringNotEmpty',
                'bannerDeactivationDate' => 'stringNotEmpty',
                'uriObj' => 'object',
                'files' => 'isArray',
            );
            $filterData = array();
            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    //파일과 uri object는 예외 처리 valid 타입인지만 확인,
                    if($key == 'files' || $key == 'uriObj'){
                        Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $value;
                    }
                    else if($targetPostData[$key] == 'integer'){
                        Assert::{$targetPostData[$key]}((int)$value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }
            unset($this->data, $targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:', $filterData);

            $bannerInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_banner')
                ->where('bb_id = ?')
                ->setParameter(0,$filterData['bannerId'])
                ->execute()->fetch();

            if(!$bannerInfo){
                $this->logger->error('banner info not found');
                throw new Exception('배너 정보가 존재 하지 않습니다.',9999);
            }
            if($filterData['bannerSubject'] == ""){
                throw new Exception('배너 제목을 입력해 주세요.',9999);
            }
            if($filterData['bannerContent'] == ""){
                throw new Exception('배너 내용을 입력해 주세요.',9999);
            }
            if($filterData['bannerLocation'] == 'none'){
                throw new Exception('배너 노출 위치를 선택해 주세요.',9999);
            }
            //메인 top 선택시 bannerPublishLocation값이 없어서 넣어줌 .
            if($filterData['bannerLocation'] == 'mainTop') {
                $filterData['bannerPublishLocation'] = 'none';
            }
            if($filterData['bannerLocation'] == 'categoryTop'){
                if($filterData['bannerPublishLocation'] == 'none' ){
                    throw new Exception('배너 노출 대상을 선택해 주세요.',9999);
                }
            }
            if($filterData['bannerActivationDate']== NULL){
                throw new Exception('배너 활성화 일자를 선택해 주세요',9999);
            }
            if($filterData['bannerDeactivationDate'] == NULL){
                throw new Exception('배너 비활성화 일자를 선택해 주세요',9999);
            }

            foreach ($filterData['files']['bannerSource'] as $key => $value){
                // 수정시 파일을 올려놓지 않았을때.
                if($value->getSize() <= 0){
                    $barrydb->createQueryBuilder()
                        ->update('barry_banner')
                        ->set('bb_activation_datetime', '?')
                        ->set('bb_deactivation_datetime', '?')
                        ->set('bb_subject', '?')
                        ->set('bb_content', '?')
                        ->set('bb_location_type', '?')
                        ->set('bb_publish_location', '?')
                        ->set('bb_update_datetime', '?')
                        ->set('bb_link', '?')
                        ->where('bb_id = ?') //15
                        ->setParameter(0,$filterData['bannerActivationDate'])
                        ->setParameter(1,$filterData['bannerDeactivationDate'])
                        ->setParameter(2,$filterData['bannerSubject'])
                        ->setParameter(3,$filterData['bannerContent'])
                        ->setParameter(4,$filterData['bannerLocation'])
                        ->setParameter(5,$filterData['bannerPublishLocation'])
                        ->setParameter(6,$util->getDateSql())
                        ->setParameter(7,$filterData['bannerLink'])
                        ->setParameter(8,$bannerInfo['bb_id'])
                        ->execute();
                }
                else{//수정 시 파일이 있을 때
                    $uploadFileInfo = $util->slimApiMoveUploadedFile($_SERVER['DOCUMENT_ROOT'].'/data/apiBanner',$value,'image');
                    $barrydb->createQueryBuilder()
                        ->update('barry_banner')
                        ->set('bb_activation_datetime', '?')
                        ->set('bb_deactivation_datetime', '?')
                        ->set('bb_subject', '?')
                        ->set('bb_content', '?')
                        ->set('bb_source', '?')
                        ->set('bb_target', '?')//5
                        ->set('bb_width', '?')
                        ->set('bb_height', '?')
                        ->set('bb_type', '?')
                        ->set('bb_size', '?')
                        ->set('bb_url', '?')//10
                        ->set('bb_location_type', '?')
                        ->set('bb_publish_location', '?')
                        ->set('bb_update_datetime', '?')
                        ->set('bb_link', '?')
                        ->where('bb_id = ?') //15
                        ->setParameter(0,$filterData['bannerActivationDate'])
                        ->setParameter(1,$filterData['bannerDeactivationDate'])
                        ->setParameter(2,$filterData['bannerSubject'])
                        ->setParameter(3,$filterData['bannerContent'])
                        ->setParameter(4,$uploadFileInfo['name'])
                        ->setParameter(5,$uploadFileInfo['convertName'])
                        ->setParameter(6,$uploadFileInfo['width'])
                        ->setParameter(7,$uploadFileInfo['height'])
                        ->setParameter(8,$uploadFileInfo['extension'])
                        ->setParameter(9,$uploadFileInfo['size'])
                        ->setParameter(10,$filterData['uriObj']->getScheme().'://'.$filterData['uriObj']->getHost().'/data/apiBanner/'.$uploadFileInfo['convertName'].'.'.$uploadFileInfo['extension'])
                        ->setParameter(11,$filterData['bannerLocation'])
                        ->setParameter(12,$filterData['bannerPublishLocation'])
                        ->setParameter(13,$util->getDateSql())
                        ->setParameter(14,$filterData['bannerLink'])
                        ->setParameter(15,$bannerInfo['bb_id'])
                        ->execute();

                    $uploadFileInfo = $util->slimApiDeleteUploadedFile($_SERVER['DOCUMENT_ROOT'].'/data/apiBanner',$bannerInfo);
                    if(!$uploadFileInfo){
                        $this->logger->error('banner file modify delete fail!');
                        throw new Exception('파일 수정 중 기존 파일 삭제에 실패 하였습니다.',9999);
                    }

                }
            }

            $this->logger->alert('배너 수정 요청 완료');
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

    public function bannerDetail(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'bannerId' => 'integer'
            );
            $filterData = array();
            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data, $targetPostData);// Plain data는 unset 합니다.
            $this->logger->info('필터 데이터:', $filterData);
            $bannerDetailInfoQueryBuilder = $barrydb->createQueryBuilder();
            $bannerDetailInfoQueryBuilder
                ->select('*')
                ->from('barry_banner')
                ->where('bb_id = ?')
                ->setParameter(0, $filterData['bannerId']);

            //rows 제한 잡히기 전에 전체 rows 리턴, 단일 노출엔 굳이 필요 없음
            //$bannerDetailInfoInfoTotalCount = $bannerDetailInfoQueryBuilder->execute()->rowCount();

            $bannerDetailInfo = $bannerDetailInfoQueryBuilder
                ->execute()->fetch();

            unset($bannerDetailInfoQueryBuilder);

            if (!$bannerDetailInfo) {
                $this->logger->error('banner detail select error');
                throw new Exception('배너 디테일을 불러오지 못하였습니다.', 9999);
            }

            $nowIsoUnixDateTime = time();
            //unix time 끼리 비교해서, 아닌 경우 false 를.. 같이 넘겨주기
            if($nowIsoUnixDateTime >= strtotime($bannerDetailInfo['bb_deactivation_datetime'])){
                $bannerDetailInfo['activationStatus'] = false;
            }
            else{
                $bannerDetailInfo['activationStatus'] = true;
            }

            $returnArray = array(
                'list' => $bannerDetailInfo,
            );

            $this->logger->alert('배너 디테일 요청 완료');
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

    public function bannerDisabled(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'bannerId' => 'String'
            );
            $filterData = array();
            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }

            $bannerInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_banner')
                ->where('bb_id = ?')
                ->setParameter(0,$filterData['bannerId'])
                ->execute()->fetch();
            if(!$bannerInfo){
                $this->logger->error('banner info not found');
                throw new Exception('배너 정보가 존재 하지 않습니다.',9999);
            }

            $barrydb->createQueryBuilder()
                ->update('barry_banner')
                ->set('bb_use', '?')
                ->where('bb_id = ?')
                ->setParameter(0,0)
                ->setParameter(1,$filterData['bannerId'])
                ->execute();
            $this->logger->alert('배너 수정 요청 완료');
            return array('code' => 200 , 'otherCode' => 200 , 'msg'=> '배너 사용 안함');
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


    public function bannerEnabled(){
        try {
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'bannerId' => 'String'
            );
            $filterData = array();

            foreach ($this->data as $key => $value) {
                if (array_key_exists($key, $targetPostData)) {
                    Assert::{$targetPostData[$key]}($value, 'valid error: ' . $key . ' valid type: ' . $targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            $bannerInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_banner')
                ->where('bb_id = ?')
                ->setParameter(0, $filterData['bannerId'])
                ->execute()->fetch();

            if (!$bannerInfo) {
                $this->logger->error('banner info not found');
                throw new Exception('배너 정보가 존재 하지 않습니다.', 9999);
            }
            $barrydb->createQueryBuilder()
                ->update('barry_banner')
                ->set('bb_use', '?')
                ->where('bb_id = ?')
                ->setParameter(0, 1)
                ->setParameter(1, $filterData['bannerId'])
                ->execute();

            $this->logger->alert('배너 사용 요청 완료');
            return array('code' => 200, 'otherCode' => 200, 'msg' => '배너 사용 요청 완료');
        }
        catch (InvalidArgumentException $e) {
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code' => 9999, 'msg' => $e->getMessage());
        }
        catch (Exception $e) {
            return array('code' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }
}

?>