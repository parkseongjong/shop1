<?php

namespace barry\coupon;

use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \Exception;

/**
 * Class Auth
 * @package barry\coupon
 */
class Auth{

    /**
     * @var bool
     */
    private $data = false;
    /**
     * @var bool
     */
    private $memberId = false;
    /**
     * @var bool
     */
    private $logger = false;

    /**
     * Auth constructor.
     * @param $postData
     * @param $memberId
     * @param $logger
     */
    public function __construct($postData, $memberId, $logger){
        $this->data = $postData;
        $this->memberId = $memberId;
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function seller(){
        try{

            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            /*
             * 1. 권한이 3인지 확인
             * 2. 권한이 3이 아니라면 쿠폰 확인.
             * 3. 쿠폰이 확인 된다면 권한 3으로 업데이트
             *
             * 1. 권한이 3이라면 쿠폰의 유효기간 확인
             * 2. 유효기간이 만료 되었다면 권한 3 회수
             *
             */

            $this->logger->info('접근 회원 정보 조회');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select fail');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            //level 3 : seller , level 2 : user
            //그 외에 경우는 판별하지 않음
            $this->logger->info('[seller]이미 발급된 쿠폰이 있는지?');
            $couponInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_coupon_status')
                ->where('bcs_mb_id = ?')
                ->andWhere('bcs_status = ?')
                ->andWhere('bcs_type = ?')
                ->setParameter(0, $memberInfo['mb_no'])
                ->setParameter(1, 'activation')
                ->setParameter(2, 'seller')
                ->execute()->fetch();

            if($couponInfo){
                if($memberInfo['mb_level'] == 3){
                    // 만료 된 쿠폰 이라면 레벨 2로 강등
                    if(self::expireCheck($couponInfo['bcs_deactivation_datetime'])){
                        //mb level 강등
                        $this->logger->info('[seller]만료된 쿠폰! 레벨강등!');
                        self::memberLevelModify(2,$memberInfo['mb_no'],$barrydb);
                        //시간이 만료된 쿠폰 비활성화
                        $this->logger->info('[seller]만료된 쿠폰! 쿠폰 비활성화 !');
                        self::couponStatusModify(false,$couponInfo['bcs_uniq'],$barrydb);

                        return true;

                    }
                }
                else if($memberInfo['mb_level'] == 2){
                    //만료된 쿠폰이 아니라면, 레벨 3으로...!!
                    if(!self::expireCheck($couponInfo['bcs_deactivation_datetime'])){
                        $this->logger->info('[seller]유효한 쿠폰! 등급업!');
                        self::memberLevelModify(3,$memberInfo['mb_no'],$barrydb);
                    }
                    else{
                        //시간이 만료된 쿠폰 비활성화와 레벨 강등
                        $this->logger->info('[seller]레벨2 상태, 쿠폰 비활성화 감지!');
                        self::couponStatusModify(false,$couponInfo['bcs_uniq'],$barrydb);
                    }

                    return true;
                }
                else{
                    $this->logger->error('not coupon seller target member');
                    throw new Exception('seller 쿠폰 판별 대상이 아닙니다.',9999);
                }
                return true;
            }
        }
        catch (Exception $e){
            //return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
            return false;
        }
    }

    /**
     *
     */
    public function premium(){
        try{
            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            /*
             *
             * 프리미엄 권한만 있는지 확인...
             *
             */

            $this->logger->info('접근 회원 정보 조회');
            $memberInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('g5_member')
                ->where('mb_id = ?')
                ->setParameter(0, $this->memberId)
                ->execute()->fetch();
            if(!$memberInfo){
                $this->logger->error('session id select fail');
                throw new Exception('세션 값과 접근자 회원 정보가 존재하지 않습니다.',9999);
            }

            $this->logger->info('[premium]이미 발급된 쿠폰이 있는지?');
            $couponInfo = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_coupon_status')
                ->where('bcs_mb_id = ?')
                ->andWhere('bcs_status = ?')
                ->andWhere('bcs_type = ?')
                ->setParameter(0, $memberInfo['mb_no'])
                ->setParameter(1, 'activation')
                ->setParameter(2, 'premium')
                ->execute()->fetch();
            if($couponInfo){
                //super, corpUser는 제외
                if($memberInfo['mb_10'] != 'super' && $memberInfo['mb_10'] != 'corpUser'){
                    if($memberInfo['mb_10'] == 'premium'){
                        // 만료 된 쿠폰 이라면 user로 강등
                        if(self::expireCheck($couponInfo['bcs_deactivation_datetime'])){
                            //mb10  강등
                            $this->logger->info('[premium]만료된 쿠폰! 프리미엄 상태 강등!!');
                            self::memberMb10Modify('user',$memberInfo['mb_no'],$barrydb);
                            //시간이 만료된 쿠폰 비활성화
                            $this->logger->info('[premium]만료된 쿠폰! 쿠폰 비활성화 감지!');
                            self::couponStatusModify(false,$couponInfo['bcs_uniq'],$barrydb);
                            return true;
                        }
                    }//초창기 member은 user가 아닌경우 비어있음.
                    else if($memberInfo['mb_10'] == 'user' || $memberInfo['mb_10'] == ''){
                        //만료 된 쿠폰이 아니라면, premium
                        $this->logger->info('[premium]유효한 쿠폰! 프리미엄 상태 승격');
                        if(!self::expireCheck($couponInfo['bcs_deactivation_datetime'])){
                            self::memberMb10Modify('premium',$memberInfo['mb_no'],$barrydb);
                        }
                        else{
                            //시간이 만료된 쿠폰 비활성화와 user 강등
                            $this->logger->info('[premium]프리미엄 강등 상태, 쿠폰 비활성화 감지!');
                            self::couponStatusModify(false,$couponInfo['bcs_uniq'],$barrydb);
                        }
                        return true;
                    }
                    else{
                        $this->logger->error('not coupon premium target member');
                        throw new Exception('premium 쿠폰 판별 대상이 아닙니다.',9999);
                    }
                }
                return true;
            }
        }
        catch (Exception $e){
            //return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
            return false;
        }
    }

    //unix time 으로 비교, 만료 된 일시 : true, 아닐 때 : false
    private function expireCheck($expireDate = false){
        if($expireDate === false){
            return false;
        }

        $nowDate = time();
        $expireDate = strtotime($expireDate);

        if($expireDate <= $nowDate ){
            return true;
        }
        else{
            return false;
        }
    }

    private function memberLevelModify($level = false, $memberId = false, $dbDriver = false){
        if($level === false || $memberId === false || $dbDriver === false){
            return false;
        }

        $updateProc = $dbDriver->createQueryBuilder()
            ->update('g5_member')
            ->set('mb_level', '?')
            ->where('mb_no = ?')
            ->setParameter(0, $level)
            ->setParameter(1, $memberId)
            ->execute();
        if(!$updateProc){
            $this->logger->error('member level modify proc fail');
            throw new Exception('레벨 수정 작업을 실패 하였습니다.',9999);
        }
    }

    private function memberMb10Modify($level = false, $memberId = false, $dbDriver = false){
        if($level === false || $memberId === false || $dbDriver === false){
            return false;
        }

        $updateProc = $dbDriver->createQueryBuilder()
            ->update('g5_member')
            ->set('mb_10', '?')
            ->where('mb_no = ?')
            ->setParameter(0, $level)
            ->setParameter(1, $memberId)
            ->execute();
        if(!$updateProc){
            $this->logger->error('member mb10 modify proc fail');
            throw new Exception('mb10 수정 작업을 실패 하였습니다.',9999);
        }
    }

    private function couponStatusModify($status = NULL, $uniqId = false, $dbDriver = false){
        if($status === NULL || $uniqId === false || $dbDriver === false){
            return false;
        }

        if($status === true){
            $status = 'activation';
        }
        else{
            $status = 'deactivation';
        }
        $updateProc = $dbDriver->createQueryBuilder()
            ->update('barry_coupon_status')
            ->set('bcs_status', '?')
            ->where('bcs_uniq = ?')
            ->setParameter(0, $status)
            ->setParameter(1, $uniqId)
            ->execute();
        if(!$updateProc){
            $this->logger->error('seller coupon modify proc fail');
            throw new Exception('seller 쿠폰 수정 작업을 실패 하였습니다.',9999);
        }
    }

}

?>