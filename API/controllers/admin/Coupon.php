<?php

namespace barry\admin;

use \Webmozart\Assert\Assert;
use \ezyang\htmlpurifier;
use \barry\db\DriverApi as barryDb;
use \InvalidArgumentException;
use \Exception;

class Coupon
{

    private $data = false;
    private $memberId = false;
    private $logger = false;

    public function __construct($postData, $logger)
    {
        $this->data = $postData;
        $this->logger = $logger;
    }

    //조인을 3개 해야할 것 같음...?
    public function couponPaymentList()
    {
        try {
            $config = \HTMLPurifier_Config::createDefault();
            $purifier = new \HTMLPurifier($config);
            $db = barryDb::singletonMethod();
            $barrydb = $db->init();

            $targetPostData = array(
                'page' => 'stringNotEmpty',
                'num_rows' => 'stringNotEmpty',
                'order_key' => 'string',
                'order_dir' => 'string',
                's_keyword' => 'string',
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
            $couponPaymentListInfoQueryBuilder = $barrydb->createQueryBuilder();
            $couponPaymentListInfoQueryBuilder
                ->select('A.*, B.*')
                ->from('barry_coupon_status', 'A')
                ->innerJoin('A','barry_pg_payment_status', 'B','A.bpps_id = B.bpps_id');
            if (!empty($filterData['s_keyword'])) {
                $couponPaymentListInfoQueryBuilder
                    ->where('A.bcs_subject like ?')
                    ->orWhere('A.bcs_timeleft like ?')
                    ->orWhere('A.bcs_price like ?')
                    ->setParameter(0, '%' . $filterData['s_keyword'] . '%')
                    ->setParameter(1, '%' . $filterData['s_keyword'] . '%')
                    ->setParameter(2, '%' . $filterData['s_keyword'] . '%');
            }
            if (!empty($filterData['order_key']) && !empty($filterData['order_dir'])) {
                $couponPaymentListInfoQueryBuilder
                    ->addOrderBy($filterData['order_key'], $filterData['order_dir']);
            }
            else{
                $couponPaymentListInfoQueryBuilder
                    ->orderBy('A.bcs_id', 'desc');
            }

            //rows 제한 잡히기 전에 전체 rows 리턴
            $couponPaymentListInfoTotalCount = $couponPaymentListInfoQueryBuilder->execute()->rowCount();

            $couponPaymentListInfo = $couponPaymentListInfoQueryBuilder
                ->setFirstResult(($filterData['page'] - 1) * $filterData['num_rows'])
                ->setMaxResults($filterData['num_rows'])
                ->execute()->fetchAll();

            unset($couponPaymentListInfoQueryBuilder);

            if (!$couponPaymentListInfo) {
                $this->logger->error('couponPaymentListInfo select error');
                throw new Exception('쿠폰 결제 리스트를 불러오지 못하였습니다.', 9999);
            }

            $returnArray = array(
                'count' => $couponPaymentListInfoTotalCount,
                'list' => $couponPaymentListInfo
            );
            $this->logger->alert('쿠폰 결제 리스트를 정상적으로 불러왔습니다.');
            return array('code' => 200, 'data' => $returnArray);
        } catch (InvalidArgumentException $e) {
            //유효성 체크에 실패하면 false로 보내준다.
            $this->logger->error('couponPaymentListInfo variable valid error');
            $this->logger->error($e->getMessage());
            return array('code' => 9999, 'msg' => $e->getMessage());
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return array('code' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }
}
?>