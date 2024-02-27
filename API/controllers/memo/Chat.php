<?php 

namespace barry\memo;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;

use \InvalidArgumentException;
use \Exception;


class Chat{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }
    
    public function chatProcess(){
        try{
            $util = barryUtil::singletonMethod();
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $targetPostData = array(
                'mrId' => 'integer',
                'action' => 'stringNotEmpty',
                'code' => 'integer'
            );

            $filterData = array();
            foreach($this->data as $key => $value){
                if(array_key_exists($key,$targetPostData)) {
                    if($targetPostData[$key] == 'integer'){
                        Assert::{$targetPostData[$key]}((int)$value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = (int)$purifier->purify($value);
                    }
                    else{
                        Assert::{$targetPostData[$key]}($value,'valid error: '.$key.' valid type: '.$targetPostData[$key]);
                        $filterData[$purifier->purify($key)] = $purifier->purify($value);
                    }
                }
            }

            unset($this->data, $targetPostData);

            $memberInfoCheck = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();

            if(!$memberInfoCheck){
                throw new Exception('올바른 접근이 필요 합니다.' , 9999);
            }

            /*
            $memoChatBuild = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_memo_new')
                ->where('mr_id = ?')
                ->setParameter(0,$filterData['mrId'])
                ->andWhere('me_write_datetime BETWEEN  DATE_ADD(NOW(),INTERVAL -1 YEAR ) AND DATE_ADD(NOW(),INTERVAL -1 MONTH)')
                // ->andWhere('me_write_datetime BETWEEN  DATE_ADD(NOW(),INTERVAL -2 DAY ) AND NOW() - INTERVAL 1 DAY')
                ->orderBy('me_id', 'asc')
                ->execute()->fetchAll();

            $prevMemoChatBuild = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_memo_new')
                ->where('mr_id = ?')
                ->setParameter(0,$filterData['mrId'])
                ->andWhere('me_write_datetime BETWEEN  DATE_ADD(NOW(),INTERVAL -1 YEAR ) AND DATE_ADD(NOW(),INTERVAL -1 MONTH)')
                // ->andWhere('me_write_datetime BETWEEN  DATE_ADD(NOW(),INTERVAL -10 DAY ) AND NOW() - INTERVAL 2 DAY')
                ->orderBy('me_id', 'asc')
                ->execute()->fetchAll();

            //한달보다 이전 데이터가 있다면 1년 까지만 보여줌 ..
            if($prevMemoChatBuild) {
                $memoChatBuild = $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('g5_memo_new')
                    ->where('mr_id =?')
                    ->setParameter(0,$filterData['mrId'])
                    ->andWhere('me_write_datetime BETWEEN  DATE_ADD(NOW(),INTERVAL -1 YEAR ) AND DATE_ADD(NOW(),INTERVAL -1 MONTH)')
                    ->orderBy('me_id', 'asc')
                    ->execute()->fetchAll();
            }
            */
            $memoChatBuild2 = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_memo_new')
                ->where('mr_id = ?')
                ->setParameter(0,$filterData['mrId'])
                ->andWhere("me_write_datetime BETWEEN DATE_ADD(NOW(),INTERVAL -5 YEAR ) AND DATE_ADD(NOW(),INTERVAL -1 MONTH)")
                ->orderBy('me_id','desc')
                ->execute()->fetch();

            if($memoChatBuild2){
                $memoChatBuild =  $barrydb->createQueryBuilder()
                    ->select('*')
                    ->from('g5_memo_new')
                    ->where('mr_id = ?')
                    ->setParameter(0,$filterData['mrId'])
                    ->andWhere('me_write_datetime BETWEEN DATE_ADD("'.$memoChatBuild2['me_write_datetime'].'",INTERVAL -1 YEAR ) AND DATE_ADD(NOW(),INTERVAL -1 MONTH)')
                    ->orderBy('me_id', 'asc')
                    ->execute()->fetchall();
            }
            else{
                throw new Exception('채팅정보가 없습니다.' , 403);
            }

            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_memo_room')
                ->where('mr_id = ?')
                ->setParameter(0, $filterData['mrId'])
                ->execute()->fetch();

            //접근자와 받는이가 일치하는경우
            if($memberInfo['me_recv_mb_id']== $this->memberId){
                $row = $barrydb->createQueryBuilder()
                ->select('*, B.mb_name , B.mb_nick')
                ->from('g5_memo_room','A')
                ->leftjoin('A', 'g5_member','B','A.me_send_mb_id = B.mb_id')
                ->where('A.mr_id = ?')
                ->setParameter(0,$filterData['mrId'])
                ->execute()->fetchAll();
            }
            else {//접근자와 보낸이가 일치하는 경우
                $row = $barrydb->createQueryBuilder()
                ->select('*, B.mb_name , B.mb_nick')
                ->from('g5_memo_room','A')
                ->leftjoin('A', 'g5_member','B','A.me_recv_mb_id = B.mb_id')
                ->where('A.mr_id = ?')
                ->setParameter(0,$filterData['mrId'])
                ->execute()->fetchAll();
            }


            if(!$memoChatBuild) {
                throw new Exception('채팅정보가 없습니다.' , 403);
            }
            $day_letter = array("일","월","화","수","목","금","토");
            $date = "00.00";


           for($i = 0; $i<count($memoChatBuild); $i++){

                $target_nick = $row[0]['mb_name'];
                $wdate = str_replace('-', '.', substr($memoChatBuild[$i]['me_write_datetime'], 5, 5));
                if ($wdate[0]=='0') $wdate = substr($wdate, 1);

                $whour = round(substr($memoChatBuild[$i]['me_write_datetime'], 11, 2));
                $wmin = substr($memoChatBuild[$i]['me_write_datetime'], 14, 2);

                if ($whour>12) {
                    $apm = '오후';
                    $whour -= 12;
                }
                else {
                    $apm = '오전';
                }

                if ($wdate != $date) {
                    $date = $wdate;
                    $day_w = date('w', strtotime($memoChatBuild[$i]['me_write_datetime']));
                    $day = $day_letter[$day_w];
                }
                else {
                    $day = null;
                }

                $result[$i] = array(
                    'date' => $date,
                    'day' => $day,
                    'apm' => $apm,
                    'whour' => $whour,
                    'wmin' => $wmin,
                    'target_nick' =>$target_nick
                );
            }


            return array('code' => 200,  'memoInfo' =>$memoChatBuild , 'result' =>$result);
        }
        catch (InvalidArgumentException $e){
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('variable valid error');
            $this->logger->error($e->getMessage());
            return array('code'=>9999, 'msg'=>$e->getMessage());
        }
        catch (Exception $e){
            $this->logger->error('memo chat get fail!');
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }
    }
}

?>
