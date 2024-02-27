<?php

namespace barry\goods;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\common\Filter as barryFilter;
use \barry\db\DriverApi as barryDb;

use \InvalidArgumentException;
use \Exception;

class Detail{

    private $data = false;
    private $memberId = false;
    private $logger = false;


    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }
    
    public function goodsDetailProcess(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $filter = barryFilter::singletonMethod();
            $db = barryDb::singletonMethod();
            
            $barrydb = $db-> init();

            $targetPostData = array(
                'goodsId' => 'integer',
                'goodsTable'=>'stringNotEmpty',
                'action' => 'stringNotEmpty',
                'code' => 'integer');

            $filterData = array();
            $filterData = $filter->postDataFilter($this->data,$targetPostData);
//            foreach($this->data as $key => $value){
//                if(array_key_exists($key,$targetPostData)) {
//                    if($targetPostData[$key] == 'integer'){
//                        Assert::{$targetPostData[$key]}((int)$value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
//                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
//                    }
//                    else{
//                        Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
//                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
//                    }
//                }
//            }

            unset($this->data, $targetPostData);
            
         
            $goodsInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_write_'.$filterData['goodsTable'])
            ->where('wr_id = ?')
            ->setParameter(0,$filterData['goodsId'])
            ->execute()->fetch();
            
            if(!$goodsInfo){
                $this->logger->error('goodsInfo does not exist');
                throw new Exception('상품정보가 유효하지 않습니다.',9999);
            }


            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)    
                ->execute()->fetch();
         
            if(!$memberInfo){
                throw new Exception('올바른 접근이 필요 합니다.(2)' , 9999);
            }
            if($memberInfo['mb_id'] != $goodsInfo['mb_id']){
                throw new Exception('올바른 접근이 필요 합니다.' , 9999);
            }
                
            if($filterData['action'] == 'soldout'){
                if($memberInfo['mb_id'] == $goodsInfo['mb_id']){
                    if($goodsInfo['it_soldout']==1){
                        $actionProc = $barrydb->createQueryBuilder()
                            ->update('g5_write_'.$goodsInfo['it_me_table'])
                            ->set('it_soldout', 0)
                            ->where('wr_id = ?')
                            ->setParameter(0, $goodsInfo['wr_id'])
                            ->execute();
                        if(!$actionProc){
                           throw new Exception('정상 처리를 실패하였습니다.',406);
                        }
                        $goodsCode = 10;
                        $goodsMsg = '재고 있음으로 상태 변경이 되었습니다.';

                    }
                    else{
                        throw new Exception('이미 정상처리된 상품입니다.',406);
                    }
                }
                else{
                    throw new Exception('정상 및 품절처리는 판매자 본인만 할 수 있습니다.',403);
                }
            }
            else if($filterData['action'] == 'unSoldout'){
                if($memberInfo['mb_id'] == $goodsInfo['mb_id']) {
                    if($goodsInfo['it_soldout']==0){
                        $actionProc = $barrydb->createQuerybuilder()
                        ->update('g5_write_'.$goodsInfo['it_me_table'])
                        ->set('it_soldout',1)
                        ->where('wr_id = ?')
                        ->setParameter(0,$goodsInfo['wr_id'])
                        ->execute();

                        if(!$actionProc){
                            throw new Exception('품절 처리를 실패하였습니다',406);
                        }
                        $goodsCode = 20;
                        $goodsMsg = '품절로 상태 변경되었습니다.';
                    }
                    else{
                        throw new Exception('이미 품절처리된 상품입니다.', 406);
                    }
                }
                else{
                    throw new Exception('정상 및 품절처리는 판매자 본인만 할 수 있습니다.',403);
                }
            }
            else if($filterData['action'] == 'recover'){
                if($memberInfo['mb_id'] == $goodsInfo['mb_id']) {
                    if($goodsInfo['del_yn']=='Y'){
                        $actionProc = $barrydb->createQuerybuilder()
                        ->update('g5_write_'.$goodsInfo['it_me_table'])
                        ->set('del_yn','"N"')
                        ->where('wr_id = ?')
                        ->setParameter(0,$goodsInfo['wr_id'])
                        ->execute();

                    if(!$actionProc){
                        throw new Exception('복구 처리를 실패하였습니다',406);
                    }
                        $goodsCode = 20;
                        $goodsMsg = '복구로 상태 변경되었습니다.';
                    }
                    else{
                        throw new Exception('이미 복구 처리된 상품입니다.', 406);
                    }
                }
                else{
                    throw new Exception('삭제 및 복구처리는 판매자 본인만 할 수 있습니다.',403);
                }
            }
            else if($filterData['action'] == 'delete'){
                if($memberInfo['mb_id'] == $goodsInfo['mb_id']) {
                    if($goodsInfo['del_yn']=='N'){
                        $actionProc = $barrydb->createQuerybuilder()
                        ->update('g5_write_'.$goodsInfo['it_me_table'])
                        ->set('del_yn','"Y"')
                        ->where('wr_id = ?')
                        ->setParameter(0,$goodsInfo['wr_id'])
                        ->execute();

                        if(!$actionProc){
                            throw new Exception('삭제 처리를 실패하였습니다',406);
                        }
                        $goodsCode = 30;
                        $goodsMsg = '삭제로 상태 변경되었습니다.';
                    }
                    else{
                        throw new Exception('이미 삭제 처리된 상품입니다.', 406);
                    }

                }
                else{//commit teset
                    throw new Exception('정상 및 품절처리는 판매자 본인만 할 수 있습니다.',403);
                }
            }
            else if($filterData['action'] == 'reConsider'){
                if($memberInfo['mb_id'] == $goodsInfo['mb_id']){
                    if($goodsInfo['it_publish'] == 90 || $goodsInfo['it_publish'] ==99){
                        $actionProc = $barrydb->createQueryBuilder()
                            ->update('g5_write_'.$goodsInfo['it_me_table'])
                            ->set('it_publish', 0)
                            ->where('wr_id = ?')
                            ->setParameter(0, $goodsInfo['wr_id'])
                            ->execute();
                        if(!$actionProc){
                           throw new Exception('실패 하였습니다!',9999);
                        }
                        $goodsCode = 10;
                        $goodsMsg = '재심사 신청이 완료되었습니다.';
                    }
                    else{
                        throw new Exception('이미 재심사 신청되었습니다.',406);;
                    }
                }
                else{
                    throw new Exception('재심사 신청은 본인만 할 수 있습니다.',403);
                }
            }

            return array('code' => 200, 'goodsCode' => $goodsCode, 'goodsMsg' => $goodsMsg);

        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('goodsDetail status fail!');
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>