<?php
/*


    Util.php

    싱글톤 패턴 calss 입니다.


*/
namespace barry\order;

use \barry\db\DriverApi as barryDb;

class Util {

    public static function getInstance(){
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }
    public static function singletonMethod(){
        return self::getInstance();// static 멤버 함수 호출
    }
    protected function __construct() {

    }
    private function __clone(){

    }
    private function __wakeup(){

    }

    // 상품의 재고 (창고재고수량 - 주문대기수량)
    //it_id는 wr_id 값,
    //wr_6은 개수 입니다.
    //wr_1은 item의 wr_id 입니다.
    public function get_it_stock_qty_barry($it_id, $table){
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();
        //테이블 명은 각 write에 유입 될 때 필터링 되니 이 곳에선 따로 필터링을 안합니다.

        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('it_stock_qty')
            ->from('g5_write_'.$table)
            ->where('wr_id = ?')
            ->setParameter(0, $it_id)
            ->execute()->fetch();
        if(!$qtyInfo){
            $jaego = 0;
        }
        else{
            $jaego = (int)$qtyInfo['it_stock_qty'];
        }


        // 재고에서 빼지 않았고 주문인것만
        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('SUM(wr_6) as sum_qty')
            ->from('g5_write_order')
            ->where('wr_1 = ?')
            ->andWhere('wr_9 = ?')
            ->andWhere('io_id = ""')
            ->andWhere('ct_stock_use = 0')
            ->andWhere('wr_status in ("order", "delivery")')
            ->andWhere('wr_10 = "completePayment"')
            ->setParameter(0, $it_id)
            ->setParameter(1, $table)
            ->execute()->fetch();
        if(!$qtyInfo){
            $daegi = 0;
        }
        else{
            $daegi = (int)$qtyInfo['sum_qty'];
        }


        return $jaego - $daegi;
    }

    public function get_it_noti_qty_barry($it_id, $table){
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        //테이블 명은 각 write에 유입 될 때 필터링 되니 이 곳에선 따로 필터링을 안합니다.
        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('it_noti_qty')
            ->from('g5_write_'.$table)
            ->where('wr_id = ?')
            ->setParameter(0, $it_id)
            ->execute()->fetch();
        if(!$qtyInfo){
            $noticeQty = 0;
        }
        else{
            $noticeQty = (int)$qtyInfo['it_noti_qty'];
        }


        return $noticeQty;
    }


    // 옵션의 재고 (창고재고수량 - 주문대기수량)
    //it_id는 wr_id 값,
    //$type은 사용하지 않습니다. 레거시 barry 에서는 추가 옵션 사용을 안하고 선택 옵션만 사용 합니다.
    //wr_6은 개수 입니다.
    //wr_1은 item의 wr_id 입니다.

    public function get_option_stock_qty_barry($it_id, $io_id, $table){
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('io_stock_qty')
            ->from('g5_shop_item_option')
            ->where('it_id = ?')
            ->andWhere('io_id = ?')
            ->andWhere('io_me_table = ?')
            ->andWhere('io_type = 0')
            ->setParameter(0, $it_id)
            ->setParameter(1, $io_id)
            ->setParameter(2, $table)
            ->execute()->fetch();
        if(!$qtyInfo){
            $jaego = 0;
        }
        $jaego = (int)$qtyInfo['io_stock_qty'];

        // 재고에서 빼지 않았고 주문인것만
        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('SUM(wr_6) as sum_qty')
            ->from('g5_write_order')
            ->where('wr_1 = ?')
            ->andWhere('wr_9 = ?')
            ->andWhere('io_id = ""')
            ->andWhere('ct_stock_use = 0')
            ->andWhere('wr_status in ("order", "delivery")')
            ->andWhere('wr_10 = "completePayment"')
            ->setParameter(0, $it_id)
            ->setParameter(1, $table)
            ->execute()->fetch();
        if(!$qtyInfo){
            $daegi = 0;
        }
        else{
            $daegi = (int)$qtyInfo['sum_qty'];
        }

        return $jaego - $daegi;
    }

    public function get_list_option_stock_qty_barry($it_id, $table){
        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('io_stock_qty')
            ->from('g5_shop_item_option')
            ->where('it_id = ?')
            ->andWhere('io_me_table = ?')
            ->andWhere('io_type = 0')
            ->setParameter(0, $it_id)
            ->setParameter(1,$table)
            ->execute()->fetchAll();
        if(!$qtyInfo){
            $jaego = 0;
        }
        else{
            foreach ($qtyInfo as $row){
                $jaego += (int)$row['io_stock_qty'];
            }
        }

        // 재고에서 빼지 않았고 주문인것만
        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('SUM(wr_6) as sum_qty')
            ->from('g5_write_order')
            ->where('wr_1 = ?')
            ->andWhere('wr_9 = ?')
            ->andWhere('io_type = 0')
            ->andWhere('ct_stock_use = 0')
            ->andWhere('wr_status in ("order", "delivery")')
            ->andWhere('wr_10 = "completePayment"')
            ->setParameter(0, $it_id)
            ->setParameter(1, $table)
            ->execute()->fetchAll();
        if(!$qtyInfo){
            $daegi = 0;
        }
        else{
            foreach ($qtyInfo as $row){
                $daegi += (int)$row['sum_qty'];
            }
        }

        return $jaego - $daegi;
    }

    public function get_option_noti_qty_barry($it_id, $io_id){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('io_noti_qty')
            ->from('g5_shop_item_option')
            ->where('it_id = ?')
            ->andWhere('io_id = ?')
            ->andWhere('io_type = 0')
            ->setParameter(0, $it_id)
            ->setParameter(1, $io_id)
            ->execute()->fetch();
        if(!$qtyInfo){
            $noticeQty = 0;
        }
        else{
            $noticeQty = (int)$qtyInfo['io_noti_qty'];
        }

        return $noticeQty;
    }

    public function get_list_option_noti_qty_barry($it_id){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('io_noti_qty')
            ->from('g5_shop_item_option')
            ->where('it_id = ?')
            ->andWhere('io_type = 0')
            ->andWhere('io_use = 1')
            ->setParameter(0, $it_id)
            ->execute()->fetchAll();
        if(!$qtyInfo){
            $noticeQty = 0;
        }
        else{
            foreach ($qtyInfo as $row){
                $noticeQty += (int)$row['io_noti_qty'];
            }
        }

        return $noticeQty;
    }

    public function getMemberItemQty($itemId, $tableId, $memberId){

        $db = barryDb::singletonMethod();
        $barrydb = $db-> init();

        $qtyInfo = $barrydb->createQueryBuilder()
            ->select('SUM(wr_6) as sumQty')
            ->from('g5_write_order')
            ->where('wr_1 = ?')
            ->andWhere('wr_9 = ?')
            ->andWhere('ct_stock_use = 0')
            ->andWhere('wr_status in ("order", "delivery")')
            ->andWhere('wr_10 = "completePayment"')
            ->andWhere('mb_id = ?')
            ->setParameter(0, $itemId)
            ->setParameter(1, $tableId)
            ->setParameter(2, $memberId)
            ->execute()->fetch();

            return (int)$qtyInfo['sumQty'];
    }


}

?>