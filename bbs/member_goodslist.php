<?php
include_once('./_common.php');
if ($is_guest) {
    alert('로그인 되어 있지 않습니다.');
}
add_javascript('<script src="'.G5_BBS_URL.'/goodsDetail.js"></script>', 1);
$g5['title'] = '설정';
include_once(G5_PATH.'/head.sub.php');
?>

<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div id="hd_wrapper">

        <div id="logo" style="text-align:left; padding-left:50px; font-size:20px; padding-top:16px; font-weight:700">등록한 상품</div>
        <button type="button" id="setting_go" class="gnb_back"><span class="sound_only"> 이전</span></button>

    </div>

</header>

<style type="text/css">
    #hd {border-bottom:1px solid #ececce;}
    #good_info {margin:20px}
    #good_info ul {list-style-type:none; width:100%; border-bottom:1px solid #f3f3f3; padding-bottom:10px; margin-bottom:20px; overflow:hidden;}
    #good_info li {float:left;}
    #good_info li.thumb {width:15%;}
    #good_info li.thumb .img_inner {width:50px; height:50px; margin:5px; border-radius:120px; background:#dadada; text-align:center}
    #good_info li.thumb img {width:100%; margin-top:4px;}
    #good_info li.desc {width:80%; margin-left:5%;}
    #good_info li.desc .count {font-size:12px; color:white;padding:0 6px; background:#f7115d; border-radius:20px; margin-right:8px}
    #good_info li.desc .del_y {font-size:13px; color:white;padding:2px 7px; background:#f7115d; border-radius:20px; margin-right:8px}
    #good_info li.desc .del_n {font-size:13px; color:white;padding:2px 7px; background:#11c711; border-radius:20px; margin-right:8px}
    #good_info li.desc .content {margin-top:5px;font-size:13px;line-height:19px;color:#888;}
    #good_info li.desc .dt {margin-top:12px; color:#aaa}
    #good_info .empty {padding-top:100px; text-align:center;}
    #good_info .empty img {width:100px;}
    #good_info .empty .desc {margin-top:20px;color:#bfbfbf;font-size:16px;}

    .menu ul {list-style-type:none; width:100%; height:43px; border-bottom:1px solid #e3eced; }
    .menu li {float:left; width:25%; height:43px; line-height:43px; text-align:center; color:#a0a0a0;}
    .menu li.on {background:#ededed; color:#757575;}
</style>

<?php
if (!isset($bo_table) || trim($bo_table) == '') $bo_table = 'Shop';
?>

<section class="menu">
    <ul>
        <li <?php if ($bo_table=='Shop') echo 'class="on"'; ?> onclick="document.location.href='/bbs/member_goodslist.php?bo_table=Shop'">P2P쇼핑</li>
        <li <?php if ($bo_table=='offline') echo 'class="on"'; ?> onclick="document.location.href='/bbs/member_goodslist.php?bo_table=offline'">오프라인 매장</li>
        <li <?php if ($bo_table=='estate') echo 'class="on"'; ?> onclick="document.location.href='/bbs/member_goodslist.php?bo_table=estate'">부동산</li>
        <li <?php if ($bo_table=='market') echo 'class="on"'; ?> onclick="document.location.href='/bbs/member_goodslist.php?bo_table=market'">벼룩시장 광고</li>
    </ul>
</section>

<div id="good_info">

    <?php
    if (!isset($page) || (isset($page) && $page == 0)) $page = 1;
    if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)

    $page_rows = 8;

    $sql = "select count(*) as cnt from g5_write_{$bo_table} where mb_id='{$member['mb_id']}'";
    $row = sql_fetch($sql);

    $total_count = $row['cnt'];

    $total_page  = ceil($total_count / $page_rows);  // 전체 페이지 계산

    $from_record = ($page - 1) * $page_rows; // 시작 열을 구함
    if($from_record < 0) $from_record = 0;

    // 회원에게 들어온 주문 전체
    $sql = "select * from g5_write_{$bo_table} where mb_id='{$member['mb_id']}' order by wr_id desc limit {$from_record}, {$page_rows}";
    $result = sql_query($sql);

    $write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, 'member_goodslist.php?bo_table='.$bo_table);

    if ($result->num_rows > 0) {

        $bo_gallery_width = 150;
        $bo_gallery_height = 150;

        while ($row = sql_fetch_array($result)) {

            //$thumb = get_list_thumbnail($bo_table, $row['wr_1'], $bo_gallery_width, $bo_gallery_height, false, true);

            // wr_1 : 상품테입블의 index
            $thumb = get_list_thumbnail($bo_table, $row['wr_id'], $bo_gallery_width, $bo_gallery_height, false, true);

            if($thumb['src']) {
                $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" class="base_img" />';
            }
            else {
                $img_content = '<!-- no image -->';
            }

            //코인 타입 빌드
            if($row['wr_price_type'] == 'TP3'){
                $row['wr_price_type'] = 'e-TP3';
                $bulid = number_format($row['wr_1']).' e-TP3';
            }
            else if($row['wr_price_type'] == 'MC') {
                $bulid = number_format($row['wr_2']).' e-MC';
            }
            else if($row['wr_price_type'] == 'TP3MC') {
                $bulid = number_format($row['wr_1']).' e-TP3'.'/'.number_format($row['wr_2']).' e-MC';
            }
            else if($row['wr_price_type'] == 'EKRW') {
                $bulid = number_format($row['wr_3']).' e-KRW';
            }
            else if($row['wr_price_type'] == 'ECTC') {
                $bulid = number_format($row['wr_4']).' e-CTC';
            }
			else if($row['wr_price_type'] == "KRW" ) {
				$bulid = "<p>".number_format($row['wr_10'])."<span class='unit'>원</span></p>";
			}
			else if ($row['wr_price_type'] == "CREDITCARD"){
				$bulid = "<p>".number_format($row['wr_10'])."<span class='unit'>원</span></p>";
			}
            ?>
            <ul>
                <li class="thumb">
                    <div><?=$img_content?></div>
                </li>
                <li class="desc" onclick="goDetail('<?=$row['wr_id']?>','<?=$row['it_me_table'];?>','<?=$page;?>')">
                    <span><?=$row['ca_name']?></span> <span>(<?php echo get_text($row['mb_id'])?>)</span>
                    <div class="content">
                        <?=$row['wr_datetime']?><br /><?php echo get_text($row['wr_subject'])?>
                    </div>
                    <div class="content">
                        <span class="bold"><?php echo $bulid ?></span>
                    </div>
                    <div class="status">
                        <?php if ($row['it_publish']==1): ?>
                            <span class="badge badge-success">승인</span>
                        <?php elseif($row['it_publish']==90): ?>
                            <span class="badge badge-warning">반려</span>
                        <?php else: ?>
                            <span class="badge badge-danger">미승인</span>
                        <?php endif; ?>
                    </div>
                    <div class="status">
                        <?php if ($row['del_yn']=='Y'): ?>
                            <span class="badge badge-danger">판매중지(삭제됨)</span>
                        <?php else: ?>
                            <span class="badge badge-success">판매중(정상)</span>
                        <?php endif; ?>
                    </div>
                    <div class="status">
                        <?php if ($row['it_soldout']==0): ?>
                            <span class="badge badge-success">정상</span>
                        <?php else: ?>
                            <span class="badge badge-danger">품절</span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php if ($row['it_publish'] == 0): ?>
                    <li class="desc">
                        <div class="alert alert-danger">
                            미승인 된 상품은 베리몰에서 판매 할 수 없습니다.
                            심사 관련 사항은 베리몰에 문의 해주세요.
                        </div>
                    </li>
                <?php elseif($row['it_publish'] == 90): ?>
                    <li class="desc">
                        <div class="alert alert-danger">
                            <p>상품 승인 심사가 반려 되었습니다.</p>
                            <p>심사 관련 사항은 베리몰에 문의 해주세요.</p>
                            <p>반려 사유 : [<?php echo $row['it_publish_msg'] ?>]</p>
                            <span class="btn btn-info btn-block" onclick="goodTempValidCheck('<?=$row['wr_id'];?>','<?=$row['it_me_table'];?>')">재심사 신청</span>
                            <a href="<?php echo G5_BBS_URL.'/write.php?w=u&bo_table='.$row['it_me_table'].'&wr_id='.$row['wr_id']; ?>" class="btn btn-warning btn-block">상품 수정</a>
                        </div>
                    </li>
                <?php elseif($row['it_publish'] == 99): ?>
                        <li class="desc">
                            <div class="alert alert-danger">
                                <?php if($row['it_cast_price'] <= 0 && $row['wr_1'] > 0): ?>
                                    <h3>해당 상품은 e-TP3을 사용하고 있습니다. 상품 수정을 통해 현금(원)을 입력해 주세요.</h3>
                                    <img src="https://barrybarries.kr/theme/barryMall_01/mobile/img/info.png" class="img-fluid" alt="임시 알림">
                                <?php endif; ?>
                                <p>상품 등록 승인제도 업데이트 이전</p>
                                <p><b>2021-03-12</b> 이전 상품은 <b>2021-03-26일</b>까지 재심사 신청을 하지 않으면</p>
                                <p>베리몰에서 판매 할 수 없습니다.</p>
                                <p>심사 관련 사항은 베리몰에 문의 해주세요.</p>
                                <?php if($row['it_cast_price'] <= 0 && $row['wr_1'] > 0): ?>

                                <?php else: ?>
                                    <span class="btn btn-info btn-block" onclick="goodTempValidCheck('<?=$row['wr_id'];?>','<?=$row['it_me_table'];?>')">재심사 신청</span>
                                <?php endif; ?>
                                <a href="<?php echo G5_BBS_URL.'/write.php?w=u&bo_table='.$row['it_me_table'].'&wr_id='.$row['wr_id']; ?>" class="btn btn-warning btn-block">상품 수정</a>
                            </div>
                        </li>
                <?php endif; ?>
            </ul>
            <?php
        }

        echo $write_pages;

    } else {
        ?>
        <div class="empty">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/bbs_empty.png" />
            <div class="desc">
                등록한 상품이 없어요!
            </div>
        </div>
        <?php
    }
    ?>
</div>

<div>
    <!-- 추후 수정 할 내용 -->
    <input type="hidden" id="page" value="<?php echo $page ?>">
</div>



<?php
include_once(G5_PATH.'/tail.sub.php');
?>
