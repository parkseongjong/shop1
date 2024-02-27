<?php

namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Log{
    
    private $data = false;
    private $memberId = false;
    private $logger = false;
    
    public function __construct($postData, $logger){
        $this->data = $postData;
        $this->logger = $logger;
    }
    
    public function list(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'testPostHIHIojt' => 'stringNotEmpty');
            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)){
                    Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                    $filterData[$purifier->purify($key)] = $purifier->purify($value);
                }
            }
            unset($this->data);// Plain data는 unset 합니다.

            //조인해서..
            $this->logger->info('log check 값 가져오기');
            $logInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_log')
                ->execute()->fetchAll();
            if(!$logInfo){
                $this->logger->error('log check select error');
                throw new Exception('로그 리스트를 불러오지 못하였습니다.',9999);
            }

            $returnArray = array(
                'list' => $logInfo
            );

            $this->logger->alert('로그 리스트를 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin log variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
    public function legacyList(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'page' => 'stringNotEmpty',
                'num_rows' => 'stringNotEmpty',
                'order_key' => 'string',
                'order_dir' => 'string',
                's_keyword' => 'string',
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

            //SQL BUILD 조인?
            $this->logger->info('media check 값 가져오기');

            $mediaInfoQueryBuilder = $barrydb->createQueryBuilder();
            $mediaInfoQueryBuilder
                ->select('*')
                //->from('barry_media_user_check')
                ->from('barry_log');
            if (!empty($filterData['s_keyword'])) {
                $mediaInfoQueryBuilder
                    ->where('id like ?')
                    ->orWhere('channel like ?')
                    ->orWhere('message like ?')
                    ->orWhere('time like ?')
                    ->setParameter(0, '%'.$filterData['s_keyword'].'%')
                    ->setParameter(1, '%'.$filterData['s_keyword'].'%')
                    ->setParameter(2, '%'.$filterData['s_keyword'].'%')
                    ->setParameter(3, '%'.$filterData['s_keyword'].'%');
            }
            $mediaInfoQueryBuilder
                ->orderBy('id','desc');
            if (!empty($filterData['order_key']) && !empty($filterData['order_dir'])) {
                $mediaInfoQueryBuilder
                    ->addOrderBy($filterData['order_key'],$filterData['order_dir']);
            }

            //rows 제한 잡히기 전에 전체 rows 리턴
            $mediaInfoTotalCount = $mediaInfoQueryBuilder->execute()->fetchColumn();

            $mediaInfo = $mediaInfoQueryBuilder
                ->setFirstResult(($filterData['page']-1)*$filterData['num_rows'])
                ->setMaxResults($filterData['num_rows'])
                ->execute()->fetchAll();

            unset($mediaInfoQueryBuilder);
            //var_dump($mediaInfo->getSql());
            //var_dump($mediaInfo->getParameters());

            if(!$mediaInfo){
                $this->logger->error('media check select error');
                throw new Exception('로그 리스트를 불러오지 못하였습니다.',9999);
            }
            $returnArray = array(
                'count' => $mediaInfoTotalCount,
                'list' => $mediaInfo
            );

            $this->logger->alert('로그 리스트를 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('admin media variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>