<?php

namespace barry\banner;

use \barry\common\Util as barryUtil;
use \barry\db\DriverApi as barryDb;
use \Exception;

use \barry\banner\SlideInterface;

/**
 * Class Slide
 * @package barry\banner
 */
class Slide implements SlideInterface{

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
     * Slide constructor.
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
     * @return false|mixed[]
     */
    public function draw(string $location, string $publishLocation){
        try{

            $util = barryUtil::singletonMethod();
            $db = barryDb::singletonMethod();
            $barrydb = $db-> init();

            $this->logger->info('배너 정보 조회');

            $nowDateTime = $util->getDateSql();

            $bannerInfoQueryBuild = $barrydb->createQueryBuilder()
                ->select('*')
                ->from('barry_banner')
                ->where('bb_use = ?')
                ->andWhere('bb_activation_datetime <= ?') //활성화 시간이 현 시간보다 작아야함
                ->andWhere('bb_deactivation_datetime >= ?') // 만료시간이 현 시간보다 커야함
                ->setParameter(0, 1)
                ->setParameter(1, $nowDateTime)
                ->setParameter(2, $nowDateTime);

                if($location != 'none'){
                    $bannerInfoQueryBuild
                        ->andWhere('bb_location_type = ?')
                        ->setParameter(3, $location);
                }

                if($publishLocation != 'none'){
                    $bannerInfoQueryBuild
                        ->andWhere('bb_publish_location = ?')
                        ->setParameter(4, $publishLocation);
                }
                $bannerInfo = $bannerInfoQueryBuild
                ->execute()->fetchAll();

            if(!$bannerInfo){
                $this->logger->error('banner info not found');
                throw new Exception('배너 정보가 존재 하지 않습니다.',9999);
            }

            return $bannerInfo;
        }
        catch (Exception $e){
            //return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
            return false;
        }
    }

    public function draw2(){

    }
}

?>