<?php
/*
 * wr_id, member모두 GB 전역 변수 입니다.
 * API 대비... 리팩토링,
 *
 *
 */
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/barryDbDriver/Driver.php');
use barry\db\Driver as barryGbDb;

try{
    //GB 필터 START
    if ($is_guest) {
        throw new Exception('로그인 되어 있지 않습니다.',9999);
    }
    //targetTable (상품 상세, 신규 채팅방 생성 접근)
    if (isset($_GET['targetTable'])) {
        $filterData['targetTable'] = preg_replace('/[^a-z0-9_]/i', '', trim($_GET['targetTable']));
        $filterData['targetTable'] = substr($filterData['targetTable'], 0, 20);
    }
    else{
        $filterData['targetTable'] = false;
    }
    //wr_id (상품 상세, 신규 채팅방 생성 / 채팅방이 이미 있는 경우는 채팅)
    //common에서 wr_id가 없으면 0 값 리턴
    $filterData['itemId'] = $wr_id;

    //mr_id (채팅방이 이미 있는 경우 채팅)
    if (isset($_GET['mr_id'])) {
        $filterData['roomId'] = (int)$_GET['mr_id'];
    }
    else {
        $filterData['roomId'] = 0;
    }


    unset($_GET,$_POST);
    //GB 필터 END

    $chatAllInfo = array();

    $db = barryGbDb::singletonMethod();
    $barrydb = $db-> init();

    //mr_id 유입 (설정 > 채팅톡)
    if($filterData['roomId'] > 0) {
        $roomInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_memo_room')
            ->where('mr_id = ?')
            ->andWhere('me_recv_mb_id = ? OR me_send_mb_id = ?')
            ->setParameter(0,$filterData['roomId'])
            ->setParameter(1,$member['mb_id'])
            ->setParameter(2,$member['mb_id'])
            ->execute()->fetch();
        if(!$roomInfo){
            throw new Exception('비정상적인 접근 입니다.',9999);
        }
        else{
            //메시지를 전송 할 접근자 (발신자) build
            $sendId = $member['mb_id'];
            $roomInfoId = $roomInfo['mr_id'];
            //  메시지를 받을 상대방 (수신자) build
            //수신자와 접근자가 동일 하면, 수신자 id는 send 값 선언
            if($roomInfo['me_recv_mb_id'] == $sendId){
                $recvId = $roomInfo['me_send_mb_id'];
            }
            else{
                //발신자와 접근자가 동일 하면, 수신자 id는 recv 값 선언
                $recvId = $roomInfo['me_recv_mb_id'];
            }
        }
        //itemInfo 사용안함.
        $itemInfo = false;
    }
    else {// wr_id 유입 (상품 상세)
        //상품 상세를 통해 채팅방을 신규로 열 수 있으며, 신규로 열린 이후에는 mr_id값을 기준으로 chat에서 주고받음...
        $itemInfo = $barrydb->createQueryBuilder()
            ->select('wr_id, wr_name,mb_id, it_me_table, wr_subject, wr_price_type, wr_1, wr_2, wr_10')
            ->from('g5_write_'.$filterData['targetTable'])
            ->where('wr_id = ?')
            ->setParameter(0,$filterData['itemId'])
            ->execute()->fetch();

        //item info Build
        //코인 타입 빌드
        if($itemInfo['wr_price_type'] == 'TP3'){
            $itemInfo['itemPrice'] = array(['price'=>$itemInfo['wr_1'],'paymentType' => 'e-TP3']);
        }
        else if($itemInfo['wr_price_type'] == 'MC') {
            $itemInfo['itemPrice'] = array(['price'=>$itemInfo['wr_2'],'paymentType' => 'e-MC']);
        }
        else if($itemInfo['wr_price_type'] == 'KRW') {
            $itemInfo['itemPrice'] = array(['price'=>$itemInfo['wr_10'],'paymentType' => '원(현금)']);
        }
        else if($itemInfo['wr_price_type'] == 'TP3MC'){
            $itemInfo['itemPrice'] = array(
                [
                    'price'=>$itemInfo['wr_1'],
                    'paymentType' => 'e-TP3'
                ],
                [
                    'price'=>$itemInfo['wr_2'],
                    'paymentType' => 'e-MC'
                ],
            );
        }
        //파일 상세를 통해서 들어온 경우는 item(goods) 사진 노출
        $chatAllInfo['thumb'] = $thumb = get_list_thumbnail($itemInfo['it_me_table'], $itemInfo['wr_id'], 150, 150, false, true);
        $chatAllInfo['itemInfo'] = $itemInfo;

        $recvId = $itemInfo['mb_id'];    //  메시지를 받을 상대방 (수신자) build
        $sendId = $member['mb_id'];      //  메시지를 전송 할 접근자 (발신자) build

        //기존에 만들어져 있는 채팅방이 있는지 수발신자 바꿔가며 확인
        $roomInfoBuild = $barrydb->createQueryBuilder()
            ->select('mr_id')
            ->from('g5_memo_room')
            ->where('me_recv_mb_id = ?')
            ->andWhere('me_send_mb_id = ?');
        $roomInfo = $roomInfoBuild
            ->setParameter(0,$recvId)
            ->setParameter(1,$sendId)
            ->execute()->fetch();

        if(!$roomInfo){
            $roomInfo = $roomInfoBuild
                ->setParameter(0,$sendId)
                ->setParameter(1,$recvId)
                ->execute()->fetch();
        }

        //기존에 만들어져 있는 채팅방이 있는경우
        if($roomInfo){
            $roomInfoId = $roomInfo['mr_id'];
        }
        else{//없는경우 false 리턴
            $roomInfo = false;
        }

    }

    //채팅방 공통 처리 START
    if ($roomInfo) {
        $roomInfoBuild = $barrydb->createQueryBuilder()
            ->select('A.*,B.mb_name, B.mb_nick, B.mb_id')
            ->from('g5_memo_room', 'A');

        if ($roomInfo['me_recv_mb_id'] == $sendId) {
            //접근자가 수신자와 일치 할 경우?
            $roomInfoBuild
                ->innerJoin('A', 'g5_member', 'B', 'A.me_send_mb_id = B.mb_id');
        }
        else {
            //접근자가 발신자와 일치 할 경우?
            $roomInfoBuild
                ->innerJoin('A', 'g5_member', 'B', 'A.me_recv_mb_id = B.mb_id');
        }

        $roomInfo = $roomInfoBuild
            ->where('A.mr_id = ' . $roomInfoId)
            ->execute()->fetch();
    }
    //roomInfo에 member 정보 join해서, 데이터 다시 build (기존 채팅방이 있는경우),
    if ($roomInfo) {
        //발신자 수신자 정보 build
        $chatAllInfo['send'] = $sendId;
        $chatAllInfo['recv'] = $recvId;

        $chatAllInfo['roomInfo'] = $roomInfo;

        //노출 mb_id 빌드
        $chatAllInfo['name'] = $roomInfo['mb_name'];
        // 제목 build
        ob_start();
        $g5['title'] = '채팅톡 - '.$roomInfo['mb_name'].'('.$roomInfo['mb_id'].')';
        include_once('./_head.php');
        $contents = ob_get_contents();
        ob_end_clean();

        $day_letter = array("일","월","화","수","목","금","토");
        $date = "00.00";

        //첫 화면 build, API로 변경 시에는.. 필요 없을 듯 싶음.
        $chatInfo = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_memo_new')
            ->where('mr_id = '.$roomInfo['mr_id'])
            ->andWhere('me_write_datetime BETWEEN DATE_ADD(NOW(),INTERVAL -1 MONTH ) AND NOW()')
            ->orderBy('me_id','ASC')
            ->execute()->fetchAll();

        foreach ($chatInfo as $key => $value) {
            $wdate = str_replace('-', '.', substr($value['me_write_datetime'], 5, 5));
            if ($wdate[0]=='0') $wdate = substr($wdate, 1);

            $whour = round(substr($value['me_write_datetime'], 11, 2));
            $wmin = substr($value['me_write_datetime'], 14, 2);

            if ($whour>12) {
                $apm = '오후';
                $whour -= 12;
            } else {
                $apm = '오전';
            }

            if ($wdate != $date) {
                $date = $wdate;
                $day_w = date('w', strtotime($value['me_write_datetime']));
                $chatInfo[$key]['dateTimeTitleBuild'] = "$date.(".$day_letter[$day_w].")";
            }
            else{
                //없을 땐 false로 리턴
                $chatInfo[$key]['dateTimeTitleBuild'] = false;
            }

            //내가 쓴 메시지면 true
            if($value['me_write_mb_id'] == $sendId){
                $chatInfo[$key]['msgMeCheck'] = true;
            }
            else{
                $chatInfo[$key]['msgMeCheck'] = false;
            }
            $chatInfo[$key]['msgBuild'] = $value['me_memo'];
            $chatInfo[$key]['msgDateTimeAmPm'] = $apm;
            $chatInfo[$key]['msgDateTimeHour'] = $whour;
            $chatInfo[$key]['msgDateTimeMin'] = $wmin;
        }

        $chatCheck = $barrydb->createQueryBuilder()
            ->select('*')
            ->from('g5_memo_new')
            ->where('mr_id = '.$roomInfo['mr_id'])
            ->andWhere('me_write_datetime BETWEEN DATE_ADD(NOW(),INTERVAL -1 MONTH ) AND NOW()')
            ->orderBy('me_id','desc')
            ->execute()->fetch();

        if($chatCheck['me_recv_mb_id'] == $member['mb_id']){
            $barrydb->createQueryBuilder()
                ->update('g5_memo_new')
                ->set('msg_check', '?')
                ->where('mr_id = ?')
                ->andWhere('msg_check = ?')
                ->setParameter(0,1)
                ->setParameter(1,$roomInfoId)
                ->setParameter(2,0)
                ->execute();
        }
        unset($chatCheck);

    }
    else{// 없는 경우에는 update 할 떄 만들어 줘야 하니... chatinfo 변수에 ercv와 send만 넘겨준다.
        //발신자 수신자 정보 build
        $chatAllInfo['send'] = $sendId;
        $chatAllInfo['recv'] = $recvId;
        $chatAllInfo['roomInfo'] = $chatInfo = false;
        ob_start();
        $g5['title'] = '채팅톡 - '.$itemInfo['wr_name'].'('.$itemInfo['mb_id'].')';
        include_once('./_head.php');
        $contents = ob_get_contents();
        ob_end_clean();
    }

    $chatAllInfo['chatInfo'] = $chatInfo;
    unset($itemInfo,$chatInfo,$roomInfo,$day_letter,$date,$wdate,$whour,$wmin);
    //버퍼에 있던 head 출력
    echo $contents;
    //채팅방 공통 처리 END
    //skin 연결 분기, 굳이... 파일까지 나눌 이유가.. 있는지.. 고민을..
    if($filterData['roomId'] > 0) {
        include_once($member_skin_path.'/memberMemoDetailChat.skin.php');
    }
    else{
        include_once($member_skin_path.'/memberMemoDetailSeller.skin.php');
    }

}
catch (Exception $e){
    if($e->getCode() == 9999){
        alert($e->getMessage());
    }
    else{
        alert('관리자에 문의해주세요.');
    }
}
include_once('./_tail.sub.php');
?>
