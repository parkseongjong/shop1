<?php

namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Media{
    
    private $data = false;
    private $memberId = false;
    private $logger = false;
    
    public function __construct($postData, $logger){
        $this->data = $postData;
        $this->logger = $logger;
    }
    
    public function boardList(){
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


            //SQL !!
            $this->logger->info('media check 값(board) 가져오기');
            // media board list 빌드 (전체 내용)
            $mediaInfo = $barrydb->createQueryBuilder()
                ->select('bmuc_board_table')
                ->DISTINCT()
                ->from('barry_media_user_check')
                ->execute()->fetchAll();

            if(!$mediaInfo){
                $this->logger->error('media check select error');
                throw new Exception('미디어 체크 리스트(board)를 불러오지 못하였습니다.',9999);
            }

            //명시된 board table 데이터 리스트 빌드

            //cafe 24 maria db 환경 : 10.1.13-MariaDB
            //test maria db 환경 : 10.3.8-MariaDB
            //cafe24 버전은 with as 절을 사용 하지 못함 ( 10.2x) 버전 부터 가능한 모양..?
            //with as 절을 모방에서 사용해야하는데, 일단 사용중인 건 단일 테이블이라 1개 조인으로 갑니다.
            //임시 테이블, 파생 테이블, 인라인 뷰 등으로 대체 할 수 있을 것 같음.
            $query = '';
            $tempI= 0;
            $temp = array();
            foreach ($mediaInfo as $key => $value) {
                if($tempI==0){
                    $query = ('	
                                SELECT distinct bmuc_board_wr_id, wr_subject ,wr_name, wr_datetime, wr_last, wr_ip ,bmuc_board_table
                                FROM barry_media_user_check
                                inner join g5_write_'.$value['bmuc_board_table'].'
                                on bmuc_board_wr_id = wr_id 
                                where bmuc_board_table = ?
	                        ');

                    array_push($temp,$value['bmuc_board_table']);
                }
                else{
                    $query .=('union all 
                                    (
                                        SELECT distinct bmuc_board_wr_id, wr_subject ,wr_name, wr_datetime, wr_last, wr_ip ,bmuc_board_table
                                        FROM barry_media_user_check
                                        inner join g5_write_'.$value['bmuc_board_table'].'
                                        on bmuc_board_wr_id = wr_id 
                                        where bmuc_board_table = ?
                                    )
                            ');
                    array_push($temp,$value['bmuc_board_table']);
                }
                $tempI++;
            }

            /*
            $tempTeble = ('
                            WITH TEMPTABLE AS (
                            '.$query.'
                            )
                            SELECT * FROM TEMPTABLE
                         ');
            */
            //with 절 문제로 임시 처리...
            $tempTeble = $query;

            if (!empty($filterData['s_keyword'])) {
                $tempTeble .= ('WHERE wr_subject like ?');
                array_push($temp,'%'.$filterData['s_keyword'].'%');
            }
            if (!empty($filterData['order_key']) && !empty($filterData['order_dir'])) {
                $tempTeble .= (' ORDER BY '.$filterData['order_key'].' '.$filterData['order_dir']);
                //$tempTeble .= (' ORDER BY ?');
                //array_push($temp,$filterData['order_key'].' '.$filterData['order_dir']);
            }
            $tempTeble .= (' LIMIT '.(int)($filterData['page']-1)*$filterData['num_rows'].', '.(int)$filterData['num_rows']);
            //array_push($temp,(int)($filterData['page']-1)*$filterData['num_rows'], (int)$filterData['num_rows']);

            try {
                $boardInfo = $barrydb->executeQuery($tempTeble, $temp)->fetchAll();
            }
            catch (Exception $e){
                //존재하지 않는 table을 검색 했을 때는 error 로그만 남겨준다.
                $this->logger->error('media check table select error/code'.$e->getCode().'/'.'msg'.$e->getMessage());
            }

            unset($mediaInfo, $tempI, $temp, $tempTeble);
            /*
            $boardInfo = array();
            foreach ($mediaInfo as $key => $value){
                try{
                    $query = false;
                    $query = 'SELECT * FROM ? WHERE = ?';
                    $madiaInfoTempQueryBuilder = $barrydb->executeQuery("SELECT * FROM articles WHERE publish_date > ?");
                    $stmt->bindValue(1, $date, "datetime");
                    $stmt->execute();

                    $madiaInfoTempQueryBuilder = $barrydb->createQueryBuilder()
                        ->select('*')
                        ->from('g5_write_'.$value['bmuc_board_table'])
                        ->where('wr_id = ?')
                        ->setParameter(0, $value['bmuc_board_wr_id'])
                        ->orderBy('wr_id','desc');
                        if (!empty($filterData['order_key']) && !empty($filterData['order_dir'])) {
                            $madiaInfoTempQueryBuilder
                                ->addOrderBy($filterData['order_key'],$filterData['order_dir']);
                        }
                    $madiaInfoTemp = $madiaInfoTempQueryBuilder
                        ->setFirstResult(($filterData['page']-1)*$filterData['num_rows'])
                        ->setMaxResults($filterData['num_rows'])
                        ->execute()->fetch();

                    //명시된 board table 값도 넣어준다.
                    foreach ($madiaInfoTemp as $key2 => $value2){
                        $madiaInfoTemp['bmuc_board_table'] = $value['bmuc_board_table'];
                        $madiaInfoTemp['bmuc_id'] = $value['bmuc_id'];
                    }
                    array_push($boardInfo,$madiaInfoTemp);
                }
                //존재하지 않는 table을 검색 했을 때는 error 로그만 남겨준다.
                catch (Exception $e){
                    $this->logger->error('media check table select error/code'.$e->getCode().'/'.'msg'.$e->getMessage());
                }
            }

            unset($mediaInfo, $madiaInfoTemp, $madiaInfoTempQueryBuilder);
*/
            //명시된 board table 데이터 리스트 빌드 전체 count
            $boardInfoTotalCount = count($boardInfo);

            $returnArray = array(
                'count' => $boardInfoTotalCount,
                'list' => $boardInfo
            );
            $this->logger->alert('미디어 체크 리스트(board)를 정상적으로 불러왔습니다.');
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

    public function linkHitList(){
        try{
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'board_table' => 'stringNotEmpty',
                'board_wrid' => 'stringNotEmpty',
                'bmucid' => 'stringNotEmpty',
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


            //SQL !!
            // media board table hit list 빌드
            $this->logger->info('media check 값(link hit) 가져오기');
            $mediaInfoQueryBuilder = $barrydb->createQueryBuilder()
                ->select('COUNT(bmuc_id) AS count')
                ->from('barry_media_user_check')
                ->Where('bmuc_board_wr_id = ?')
                ->setParameter(0, $filterData['board_wrid']);
                if (!empty($filterData['s_keyword'])) {
                    $mediaInfoQueryBuilder
                        ->andwhere('bmuc_id like ?')
                        ->orWhere('bmuc_board_wr_id like ?')
                        ->orWhere('bmuc_mb_no like ?')
                        ->orWhere('bmuc_board_table like ?')
                        ->orWhere('bmuc_datetime like ?')
                        ->setParameter(0, '%'.$filterData['s_keyword'].'%')
                        ->setParameter(1, '%'.$filterData['s_keyword'].'%')
                        ->setParameter(2, '%'.$filterData['s_keyword'].'%')
                        ->setParameter(3, '%'.$filterData['s_keyword'].'%');
                }
            $mediaInfoTotalCount =  $mediaInfoQueryBuilder->execute()->fetch();

            $mediaInfoQueryBuilder = $barrydb->createQueryBuilder();
            //barry member 테이블에 저장되는 cyber member id(no) 값을 가져온다.
            $mediaInfoQueryBuilder
                ->select('A.*, B.mb_2')
                ->from('barry_media_user_check', 'A')
                ->innerJoin('A', 'g5_member', 'B', 'A.bmuc_mb_no = B.mb_no')
                ->Where('A.bmuc_board_wr_id = ?')
                ->setParameter(0, $filterData['board_wrid']);
                 if (!empty($filterData['s_keyword'])) {
                     $mediaInfoQueryBuilder
                         ->andwhere('bmuc_id like ?')
                         ->orWhere('bmuc_board_wr_id like ?')
                         ->orWhere('bmuc_mb_no like ?')
                         ->orWhere('bmuc_board_table like ?')
                         ->orWhere('bmuc_datetime like ?')
                         ->setParameter(0, '%'.$filterData['s_keyword'].'%')
                         ->setParameter(1, '%'.$filterData['s_keyword'].'%')
                         ->setParameter(2, '%'.$filterData['s_keyword'].'%')
                         ->setParameter(3, '%'.$filterData['s_keyword'].'%');
                 }
            $mediaInfoQueryBuilder
                ->orderBy('bmuc_id','desc');
            if (!empty($filterData['order_key']) && !empty($filterData['order_dir'])) {
                $mediaInfoQueryBuilder
                ->addOrderBy($filterData['order_key'],$filterData['order_dir']);
            }
            $mediaInfo = $mediaInfoQueryBuilder
                ->setFirstResult(($filterData['page']-1)*$filterData['num_rows'])
                ->setMaxResults($filterData['num_rows'])
                ->execute()->fetchAll();

            unset($mediaInfoQueryBuilder);
            //var_dump($mediaInfo->getSql());
            //var_dump($mediaInfo->getParameters());

            if(!$mediaInfo){
                $this->logger->error('media check select error');
                throw new Exception('미디어 체크 리스트(link hit)를 불러오지 못하였습니다.',9999);
            }

            $returnArray = array(
                'count' => $mediaInfoTotalCount['count'],
                'list' => $mediaInfo,
				'boardTable' => $filterData['board_table'],
                'boardWrId' => $filterData['board_wrid'],
            );

            $this->logger->alert('미디어 체크 리스트(link hit)를 정상적으로 불러왔습니다.');
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