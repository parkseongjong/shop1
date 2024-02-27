<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/barry.lib.php');

// 선택옵션으로 인해 셀합치기가 가변적으로 변함
$colspan = 5;

$bo_gallery_width = 150;
$bo_gallery_height = 150;



if ($is_checkbox) $colspan++;
if ($is_good) $colspan++;
if ($is_nogood) $colspan++;

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v=2">', 0);
add_javascript('<script src="'.$board_skin_url.'/list.js"></script>', 0);

//$width = '130%';
echo latest_banner('theme/banner','categoryTop', $bo_table);
?>
<script>
    //list.skin Javascript 전역 변수
    barry_write_stx = '<?php echo ($stx)?$stx:'false';?>';
    barry_write_sfl = '<?php echo ($sfl)?$sfl:'false';?>';

</script>
    <div id="add_seller_bacak"></div>

    <div id="add_seller">
        <div id="add_seller_1">
            <div class="add_content">
                <div class="add_content_top">
                    <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ; ?>/add_seller_1.png" />
                    <div style="text-align:center; color:#000000; font-size:17px; font-weight:400; margin-top:20px">
                        상품등록을 원하세요?
                    </div>
                    <div style="text-align:center; color:#404040; font-size:15px; line-height:20px; font-weight:100; padding-top:20px;">
                        상품을 등록하기 위해서는<br />별도의 판매자 신청이 필요합니다.
                    </div>
                </div>
                <div class="add_content_bottom">
                    <div class="btns">
                        <input type="button" value="네, 신청하겠습니다" onclick="goSellerInfo()" />
                    </div>
                    <div style="text-align:center; color:#8a8a8a; font-size:14px; font-weight:100; padding-top:20px;" onclick="add_seller_hide()">
                        다음에 할게요
                    </div>
                </div>
            </div>
            <div class="add_navi">
                <ul class="pos">
                    <li><div class="pos_on"></div></li>
                    <li><div class="pos_off"></div></li>
                    <li><div class="pos_off"></div></li>
                </ul>
            </div>
        </div>
        <div id="add_seller_2">
            <div class="add_content">
                <div class="add_content_top">
                    <table>
                    <tr>
                        <th>판매자명</th>
                        <td><?=$member['mb_name']?></td>
                    </tr>
                    <tr>
                        <th>연락처</th>
                        <td><?=$member['mb_id']?></td>
                    </tr>
                    </table>

                    <div class="sep_line">&nbsp;</div>

                    <div style="text-align:center; color:#303300; font-size:15px; line-height:20px; font-weight:100; padding-top:20px;">
                        이 정보로 판매자 신청을<br />하는게 맞습니까?
                    </div>
                </div>
                <div class="add_content_bottom">
                    <div class="btns">
                        <input type="button" value="네, 맞습니다" onclick="goSellerFinish()" />
                    </div>
                    <div style="text-align:center; color:#8a8a8a; font-size:14px; font-weight:100; padding-top:20px;" onclick="goSellerIntro()">
                        아니요, 정보가 다릅니다
                    </div>
                </div>
            </div>
            <div class="add_navi">
                <ul class="pos">
                    <li><div class="pos_off"></div></li>
                    <li><div class="pos_on"></div></li>
                    <li><div class="pos_off"></div></li>
                </ul>
            </div>
        </div>
        <div id="add_seller_3">
            <div class="add_content">
                <div class="add_content_top">
                    <div style="text-align:left; color:#000000; font-size:16px; font-weight:400; margin-top:25px; margin-left:25px">
                        판매자 등급은 승인된 날부터<br />3개월간 무료입니다.
                    </div>
<?php
/*
                    <div style="text-align:left; color:#000000; font-size:16px; font-weight:400; margin-top:25px; margin-left:25px">
                        판매자 등급은 1개월<br />30,000원(VAT별도) 입니다.
                    </div>
                    <table style="margin-top:20px">
                    <tr>
                        <th>은행명</th>
                        <td>신한은행</td>
                    </tr>
                    <tr>
                        <th>계좌주명</th>
                        <td>(주)한가족몰</td>
                    </tr>
                    <tr>
                        <th>계좌번호</th>
                        <td>100-500-1010102</td>
                    </tr>
                    </table>
*/
?>
                    <div class="sep_line" style="margin:20px 26px 0 26px">&nbsp;</div>

                    <div style="text-align:center; color:#303300; font-size:15px; line-height:20px; font-weight:100; padding-top:6px;">
                        신청 하신후 관리자 승인이 되어야<br />상품등록이 가능합니다
                    </div>
<?php
/*
                    <div style="text-align:center; color:#303300; font-size:15px; line-height:20px; font-weight:100; padding-top:6px;">
                        입금확인 된 후에<br />상품등록이 가능합니다
                    </div>
*/
?>
                </div>
                <div class="add_content_bottom">
                    <div class="btns">
                        <input type="button" value="신청완료" onclick="goSellerUpdate()" />
                    </div>
                    <div style="text-align:center; color:#8a8a8a; font-size:14px; font-weight:100; padding-top:20px;" onclick="add_seller_hide()">
                        창을 닫습니다
                    </div>
                </div>
            </div>
            <div class="add_navi">
                <ul class="pos">
                    <li><div class="pos_off"></div></li>
                    <li><div class="pos_off"></div></li>
                    <li><div class="pos_on"></div></li>
                </ul>
            </div>
        </div>
    </div>

<!-- 게시판 목록 시작 { -->
<div id="bo_list" style="width:<?php echo $width; ?>">

    <form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">

    <input type="hidden" name="bo_table" id="fboardlist_bo_table" value="<?php echo $bo_table ?>"><!-- 검색 조건 단위 추가 위해 id값 추가함, 201015, YMJ -->
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">

    <!-- 게시판 페이지 정보 및 버튼 시작 { -->
    <div id="bo_btn_top" >

		<?php
		$search_unit_array = array('TP3' => 'TP3', 'KRW' => '원','MC'=>'MC'); // 검색 조건 추가, 201015, YMJ
		?>
        <ul class="btn_bo_user2">
            <li>
                <select name="price_type" id="price_type" class="custom-select">
                    <option value=""  selected> 전체</option>  
                    <option value="TP3" <?php if($price_type =="TP3"){ echo 'selected';}?>> e-TP3</option>
                    <option value="MC" <?php if($price_type =="MC"){ echo 'selected';}?>>e-MC</option>
                    <option value="KRW" <?php if($price_type =="KRW"){ echo 'selected';}?>>원</option>  
                </select> 
            </li>
        </ul>

        <?php if ($rss_href || $write_href) { ?>
        <ul class="btn_bo_user">
        	<?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">관리자</span></a></li><?php } ?>
            <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>

<!--
            <li>
            	<button type="button" class="btn_bo_sch btn_b01 btn" title="게시판 검색"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">게시판 검색</span></button>
            </li>
-->

        <?php if ($write_href) { ?>
            <li><a href="/bbs/memberOrderList.php" class="btn btn-outline-dark" title="판매내역조회"><i class="fa" aria-hidden="true">판매내역조회</i><span class="sound_only">주문리스트</span></a></li>
            <li><a href="<?php echo $write_href ?>" class="btn btn-outline-success" title="판매등록"><i class="fa fa-pencil" aria-hidden="true"> 판매등록</i><span class="sound_only">판매등록</span></a></li>
        <?php } ?>

        	<?php if ($is_admin == 'super' || $is_auth) {  ?>
        	<li>
        		<button type="button" class="btn_more_opt is_list_btn btn_b01 btn" title="게시판 리스트 옵션"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">게시판 리스트 옵션</span></button>
        		<?php if ($is_checkbox) { ?>
		        <ul class="more_opt is_list_btn">
		            <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value"><i class="fa fa-trash-o" aria-hidden="true"></i> 선택삭제</button></li>
		            <li><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value"><i class="fa fa-files-o" aria-hidden="true"></i> 선택복사</button></li>
		            <li><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value"><i class="fa fa-arrows" aria-hidden="true"></i> 선택이동</button></li>
		        </ul>
		        <?php } ?>
        	</li>
        	<?php }  ?>
        </ul>
        <?php } ?>

    <?php if (!$is_guest && !$write_href) { ?>
        <ul class="btn_bo_user">
            <li><a href="javascript:;" class="btn btn-outline-dark" title="판매자신청" onclick="add_seller()"><i class="fa fa-pencil" aria-hidden="true"> 판매자신청</i><span class="sound_only">판매자신청</span></a></li>
        </ul>
    <?php } ?>

        <?php if ($is_checkbox) { ?>
            <label for="chkall" class="sound_only">현재 페이지 게시물 전체</label>
            <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);">
        <?php } ?>
        <!--  <th scope="col">제품 목록</th> -->
        <?php if ($is_good) { ?><span style="margin-left:10px"><?php echo subject_sort_link('wr_good', $qstr2, 1) ?>추천 <i class="fa fa-sort" aria-hidden="true"></i></span><?php } ?>
        <?php if ($is_nogood) { ?><span style="margin-left:10px"><?php echo subject_sort_link('wr_nogood', $qstr2, 1) ?>비추천 <i class="fa fa-sort" aria-hidden="true"></i></span><?php } ?>
    </div>
    <!-- } 게시판 페이지 정보 및 버튼 끝 -->

    <?php
        //프리미엄 광고 영역
        //echo latest_item('theme/premiumItems', $bo_table, 4, 50);
//        for($i=0; $i<=30; $i++){
//            echo latest_premium_item('theme/premiumItems', $bo_table, 4, 50);
//            echo($i.'<br>');
//        }
        echo latest_premium_item('theme/premiumItems', $bo_table, 4, 50);
    ?>

<div class="data_grid">
    <h2>
        일반 등록
    </h2>
    <ul>
<?php
    for ($i=0; $i<count($list); $i++) :
//        if ($i % 2 == 0) {
//            echo "<div style='clear:both'></div>";
//        }
//        
        $fileInfo = get_file($board['bo_table'],$list[$i]['wr_id']);
        if($fileInfo['count'] != 0){
            if($fileInfo[1]['image_width'] >= 150 || $fileInfo[1]['image_width'] >= 150){
                
                $listImageFileName = thumbnail($fileInfo[1]['file'],G5_DATA_PATH.'/file/'.$bo_table.'/',G5_DATA_PATH.'/file/'.$bo_table.'/',150,150,false,true,'center',false,'80/0.5/3');
                
                $img_content = '<a href="'.$list[$i]['href'].'"><img src="'.$fileInfo[1]['path'].'/'.$listImageFileName.'" class="card-img-top img-thumbnail"></a>';
            }
            else{
                $img_content = '<a href="'.$list[$i]['href'].'"><img src="'.$fileInfo[1]['path'].'/'.$fileInfo[1]['file'].'" class="card-img-top img-thumbnail"></a>';
            }
        }
        else{
            $img_content = '<a href="'.$list[$i]['href'].'"><div class="img-thumbnail notImage">이미지가 없습니다.</div></a>';
        }

        $unit = (isset($list[$i]['wr_price_type']) && $list[$i]['wr_price_type']=='KRW') ? '원' : 'TP3';
        if($list[$i]['wr_price_type'] == "KRW" ) {
            $price = "<p>".number_format($list[$i]['wr_10'])."<span class='unit'>원</span></p>";
        }
        else if($list[$i]['wr_price_type']== "TP3MC") {
            $price ='';
            if($list[$i]['wr_1'] != 0){
                $price .= "<p>".number_format($list[$i]['wr_1'])."<span class='unit'>e-TP3</span></p>";
            }
            if($list[$i]['wr_2'] != 0){
                $price .= "<p>".number_format($list[$i]['wr_2'])."<span class='unit'> e-MC</span></p>";
            }

        }
        else if ($list[$i]['wr_price_type']=="TP3") {
            $price = '<p>'.number_format($list[$i]['wr_1'])."<span class='unit'>e-TP3</span></p>";
        }
        else if ($list[$i]['wr_price_type']=="MC"){
            $price = "<p>".number_format($list[$i]['wr_2'])."<span class='unit'>e-MC</span></p>";
        }
 ?>
        <li class="<?=($list[$i]['is_notice']) ? "bo_notice cardWrap" : 'cardWrap' ?>">
            <div class="cell card <?php echo($list[$i]['it_soldout']==1)?'soldout':'' ?>">
                <?php if ($is_checkbox) { ?>
                    <label for="chk_wr_id_<?=$i ?>" class="sound_only"><?=$list[$i]['subject'] ?></label>
                    <input type="checkbox" name="chk_wr_id[]" value="<?=$list[$i]['wr_id'] ?>" id="chk_wr_id_<?=$i ?>">
                <?php } ?>
                <section class="itemMainInfo">
                    <div class="card-img-area">
                        <?php
                            echo ($img_content);
                            echo($list[$i]['it_soldout'] == 1)?'<span class="badge badge-danger soldout">품절</span>':'';
                            echo ($list[$i]['it_limit'] == 1)?'<span class="badge badge-warning limit">한정판매</span>':'';
                        ?>
                    </div>
                    <div class="card-body">
                        <?php if ($is_category && $list[$i]['ca_name']): ?>
                            <p><a href="<?php echo $list[$i]['ca_name_href'] ?>" class="cate_link"><?=$list[$i]['ca_name'] ?></a></p>
                        <?php endif; ?>
                        <a href="<?=$list[$i]['href'] ?>" class="listSbjA">
                            <div class="SbjBlock card-title">
                                <?=$list[$i]['subject'] ?><?=(isset($list[$i]['icon_hot']) && false) ? rtrim($list[$i]['icon_hot']) : '' ?>
                                <?php if ($list[$i]['comment_cnt']) { ?>
                                    <span class="sound_only">댓글</span>
                                    <span class="cnt_cmt">+ <?=$list[$i]['wr_comment'] ?></span>
                                    <span class="sound_only">개</span><?php } ?>
                            </div>
                            <div class="card-text">
                                <div class="amount">
                                    <?php echo $price ?>
                                </div>
                                <p class="retailPrice">
                                    <?php
                                    $wr_retail_price = (isset($list[$i]['wr_retail_price'])) ? $list[$i]['wr_retail_price'] : '0';
                                    if (empty($wr_retail_price)) $wr_retail_price = '0';
                                    $wr_retail_price = round($wr_retail_price);
                                    if ($wr_retail_price > 0) {
                                        echo "(소비자가 ".number_format($list[$i]['wr_retail_price'])." 원)";
                                    } else {
                                        echo "&nbsp;";
                                    }
                                    ?>
                                </p>
                            </div>
                        </a>
                    </div>
                </section>
                <?php if($list[$i]['it_limit'] == 1 && $list[$i]['it_soldout'] == 0): ?>
                    <section class="itemOtherInfo">

                            <div class="card limitInfo">
                                <div class="card-header">
                                    한정판매
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">1 인당 제한 주문 수량 : <?php echo $list[$i]['it_limit_qty']; ?> 개</h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        시작 일자: <?php echo (string) date('Y-m-d H:i', strtotime($list[$i]['it_limit_activativation_datetime'])); ?>
                                    </h6>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        종료 일자: <?php echo (string) date('Y-m-d H:i', strtotime($list[$i]['it_limit_deactivativation_datetime'])); ?>
                                    </h6>
                                </div>
                            </div>
                    </section>
                <?php endif;?>
            </div>
        </li>
<?php
    endfor;
    if (count($list) == 0):
?>
            <li class="empty_table">
                <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ; ?>/bbs_empty.png" />
                <div class="desc">
                    등록된 상품이 없습니다.
                </div>
            </li>
<?php
    endif;
?>
    </ul>
</div>

	<!-- 페이지 -->
	<?php echo $write_pages; ?>
	<!-- 페이지 -->

    <?php if ($list_href || $is_checkbox || $write_href) { ?>
    <div class="bo_fx">
        <?php if ($list_href || $write_href) { ?>
        <ul class="btn_bo_user">
        	<?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">관리자</span></a></li><?php } ?>
            <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
            <?php if ($write_href) { ?>
            <li><a href="/bbs/memberOrderList.php" class="btn btn-outline-dark" title="판매내역조회"><i class="fa" aria-hidden="true">판매내역조회</i><span class="sound_only">주문리스트</span></a></li>
            <li><a href="<?php echo $write_href ?>" class="btn btn-outline-success" title="판매등록"><i class="fa fa-pencil" aria-hidden="true"> 판매등록</i><span class="sound_only">판매등록</span></a></li>
            <?php } ?>
        </ul>	
        <?php } ?>
    </div>
    <?php } ?>
    </form>


    <script>
    jQuery(function($){
        // 게시판 검색
        $(".btn_bo_sch").on("click", function() {
            $(".bo_sch_wrap").toggle();
        })
        $('.bo_sch_bg, .bo_sch_cls').click(function(){
            $('.bo_sch_wrap').hide();
        });

        var w = $('.base_img').width();
        $('.base_img').height(w);
    });
    </script>
    <!-- } 게시판 검색 끝 --> 
</div>

<?php if($is_checkbox) { ?>
<noscript>
<p>자바스크립트를 사용하지 않는 경우<br>별도의 확인 절차 없이 바로 선택삭제 처리하므로 주의하시기 바랍니다.</p>
</noscript>
<?php } ?>

<?php if ($is_checkbox) { ?>
<script>
	

	
function all_checked(sw) {
    var f = document.fboardlist;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]")
            f.elements[i].checked = sw;
    }
}

function fboardlist_submit(f) {
    var chk_count = 0;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
            chk_count++;
    }

    if (!chk_count) {
        alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택복사") {
        select_copy("copy");
        return;
    }

    if(document.pressed == "선택이동") {
        select_copy("move");
        return;
    }

    if(document.pressed == "선택삭제") {
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
            return false;

        f.removeAttribute("target");
        f.action = g5_bbs_url+"/board_list_update.php";
    }

    return true;
}

// 선택한 게시물 복사 및 이동
function select_copy(sw) {
    var f = document.fboardlist;

    if (sw == "copy")
        str = "복사";
    else
        str = "이동";

    var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");

    f.sw.value = sw;
    f.target = "move";
    f.action = g5_bbs_url+"/move.php";
    f.submit();
}

// 게시판 리스트 관리자 옵션
jQuery(function($){
    $(".btn_more_opt.is_list_btn").on("click", function(e) {
        e.stopPropagation();
        $(".more_opt.is_list_btn").toggle();
    });
    $(document).on("click", function (e) {
        if(!$(e.target).closest('.is_list_btn').length) {
            $(".more_opt.is_list_btn").hide();
        }
    });
});
</script>
<?php } ?>

<!-- } 게시판 목록 끝 -->

