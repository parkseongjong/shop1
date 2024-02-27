<?php
include_once('./_common.php');

$g5['title'] = '채팅톡 목록';
include_once('./_head.php');
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;

try{

    if ($is_guest) {
        throw new Exception('로그인 되어 있지 않습니다.',9999);
    }

    $db = barryGbDb::singletonMethod();
    $barrydb = $db-> init();

    //sendId 설정
    $sendId = $member['mb_id'];

    //전체 room 정보 build
    $roomInfo = $barrydb->createQueryBuilder()
        ->select('*, B.mb_name , B.mb_nick')
        ->from('g5_memo_room','A')
        ->leftjoin('A', 'g5_member','B','A.me_send_mb_id = B.mb_id')
        ->where('A.me_recv_mb_id = ? OR A.me_send_mb_id = ?')
        ->setParameter(0,$sendId)
        ->setParameter(1,$sendId)
        ->orderBy('memo_time' , 'desc')
        ->execute()->fetchAll();
    //room 정보가 없는 경우 false를 리턴.
    if(!$roomInfo){
        $chatAllInfo = false;
    }
    else{
        $chatAllInfo = array();
        $index=0;
        //room info 마다 순회
        foreach($roomInfo as $key=>$value){

            //각 room 마다 마지막 메시지는 어차피 1레코드만 가져오는거라.. limit로 offset 설정.
            $chatInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_memo_new')
                ->where('mr_id = ?')
                ->setParameter(0,$value['mr_id'])
                ->orderBy('me_write_datetime' , 'desc')
                ->setMaxResults(1)
                ->execute()->fetch();

            //메모 저장.. //me_memo
            $chatAllInfo[$index]['lastMsg'] = $chatInfo['me_memo'];

            //메세지 카운트 build
            $msgCount = $barrydb->createQueryBuilder()
                ->select('COUNT(*) AS cnt')
                ->from('g5_memo_new')
                ->where('mr_id = ?')
                ->andWhere('msg_check = ?')
                ->setParameter(0,$value['mr_id'])
                ->setParameter(1,0)
                ->execute()->fetch();
            if($msgCount['cnt'] > 0){
                $chatAllInfo[$index]['msgCount'] = $msgCount['cnt'];
            }
            else{
                $chatAllInfo[$index]['msgCount'] = 0;
            }

            //채팅방 1개 당 room info build
            $onePerRoomInfoBuild = $barrydb->createQueryBuilder()
                ->select('A.*, B.mb_name , B.mb_nick')
                ->from('g5_memo_room','A');
            if($value['me_recv_mb_id'] == $sendId) {
                $onePerRoomInfoBuild
                    ->leftjoin('A', 'g5_member','B','A.me_send_mb_id = B.mb_id');
            }
            else{
                $onePerRoomInfoBuild
                    ->leftjoin('A', 'g5_member','B','A.me_recv_mb_id = B.mb_id');
            }
            $onePerRoomInfo = $onePerRoomInfoBuild
                ->where('A.mr_id = ?')
                ->setParameter(0,$value['mr_id'])
                ->orderBy('memo_time' , 'desc')
                ->execute()->fetch();
            unset($onePerRoomInfoBuild);

            //각 채팅방 정보 삽입
            $chatAllInfo[$index]['chatInfo'] = $onePerRoomInfo;

            if($sendId == $value['me_recv_mb_id']){
                $chatAllInfo[$index]['talkerMbid'] = $value['me_send_mb_id'];
            }
            else{
                $chatAllInfo[$index]['talkerMbid'] = $value['me_recv_mb_id'];
            }

            $wdate = str_replace('-', '.', substr($chatInfo['me_write_datetime'], 5, 5));
            if ($wdate=='0') $wdate = substr($wdate, 1);

            $whour = round(substr($chatInfo['me_write_datetime'], 11, 2));
            $wmin = substr($chatInfo['me_write_datetime'], 14, 2);
            $wyear = round(substr($chatInfo['me_write_datetime'],0,4));




            if ($whour>12) {
                $chatAllInfo[$index]['msgDateTimeAmPm'] = '오후';
                $whour -= 12;
            }
            else {
                $chatAllInfo[$index]['msgDateTimeAmPm'] = '오전';
            }

            //data 값 삽입
            $chatAllInfo[$index]['msgDateTime'] = $wdate;
            $chatAllInfo[$index]['msgDateTimeHour'] = $whour;
            $chatAllInfo[$index]['msgDateTimeMin'] = $wmin;
            $chatAllInfo[$index]['msgDateTimeYear'] = $wyear;

            //count 노출 여부 build
            if($chatInfo['msg_check'] == 0 && $sendId != $chatInfo['me_write_mb_id']) {
                $chatAllInfo[$index]['countCheck'] = true;
            }
            else{
                $chatAllInfo[$index]['countCheck'] = false;
            }


            $index++;
        }
    }
    /* return value
        array(1) {
          [0]=>
          array(8) {
            ["lastMsg"]=>
            string(3) "asd"
            ["msgCount"]=>
            string(1) "6"
            ["chatInfo"]=>
            array(7) {
              ["mr_id"]=>
              string(4) "2144"
              ["me_recv_mb_id"]=>
              string(11) "01050958112"
              ["me_send_mb_id"]=>
              string(11) "01096415095"
              ["me_create_datetime"]=>
              string(19) "2020-12-10 17:12:02"
              ["memo_time"]=>
              string(19) "2021-04-07 11:24:59"
              ["mb_name"]=>
              string(23) "오정택(본인인증)"
              ["mb_nick"]=>
              string(23) "오정택(본인인증)"
            }
            ["talkerMbid"]=>
            string(11) "01096415095"
            ["msgDateTimeAmPm"]=>
            string(6) "오전"
            ["msgDateTime"]=>
            string(5) "04.07"
            ["msgDateTimeMin"]=>
            string(2) "24"
            ["msgDateTimeYear"]=>
            float(2021)
          }
        }
     */
    unset($roomInfo,$chatInfo,$onePerRoomInfo,$wdate,$whour,$wmin,$wyear);
    include_once($member_skin_path.'/memberMemoList.skin.php');
}
catch (Exception $e){
    if($e->getCode() == 9999){
        alert($e->getMessage());
    }
    else{
        alert('관리자에 문의해주세요.');
    }
    //var_dump($e->getMessage());
}
include_once(G5_PATH.'/_tail.php');
?>