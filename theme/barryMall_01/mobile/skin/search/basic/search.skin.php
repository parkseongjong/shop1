<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가


// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$search_skin_url.'/style.css">', 0);

?>
<form name="fsearch" onsubmit="return fsearch_submit(this);" method="get">
<input type="hidden" name="srows" value="<?php echo $srows ?>">
<fieldset id="sch_res_detail">
    <legend>상세검색</legend>
    <div class="form-row">
        <div class="form-row col-12">
            <div class="col-6 mb-1">
                <?php echo $group_select ?>
                <script>document.getElementById("gr_id").value = "<?php echo $gr_id ?>";</script>
            </div>

            <div class="col-6 mb-1">
                <label for="sfl" class="sound_only">검색조건</label>
                <select name="sfl" id="sfl" class="custom-select">
                    <option value="wr_subject||wr_content"<?php echo get_selected($_GET['sfl'], "wr_subject||wr_content") ?>>제목+내용</option>
                    <option value="wr_subject"<?php echo get_selected($_GET['sfl'], "wr_subject") ?>>제목</option>
                    <option value="wr_content"<?php echo get_selected($_GET['sfl'], "wr_content") ?>>내용</option>
                    <option value="mb_id"<?php echo get_selected($_GET['sfl'], "mb_id") ?>>회원아이디</option>
                    <option value="wr_name"<?php echo get_selected($_GET['sfl'], "wr_name") ?>>이름</option>
                </select>
            </div>
        </div>
        <div class="form-row col-12">
            <div class="d-flex col-12 mb-1">
                <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
                <input type="text" name="stx" id="stx" value="<?php echo $text_stx ?>" class="col-9 form-control" required  maxlength="20">
                <button type="submit" class="col-3 btn btn-success btn-block" value="검색"><i class="fa fa-search" aria-hidden="true"></i></button>
            </div>
        </div>

        <script>
            function fsearch_submit(f)
            {


                if (f.stx.value.length < 2) {
                    alert("검색어는 두글자 이상 입력하십시오.");
                    f.stx.select();
                    f.stx.focus();
                    return false;
                }

                // 검색에 많은 부하가 걸리는 경우 이 주석을 제거하세요.
                var cnt = 0;
                for (var i=0; i<f.stx.value.length; i++) {
                    if (f.stx.value.charAt(i) == ' ')
                        cnt++;
                }

                if (cnt > 1) {
                    alert("빠른 검색을 위하여 검색어에 공백은 한개만 입력할 수 있습니다.");
                    f.stx.select();
                    f.stx.focus();
                    return false;
                }

                f.action = "";
                return true;
            }
        </script>
    </div>
    <div>
      
        <input type="hidden" value="and" id="sop_and" name="sop">
        <label for="sop_and"></label>
    </div>
</fieldset>
</form>

<div id="sch_result">

    <?php
    if ($stx) {
        if ($board_count) {
    ?>
    <section id="sch_res_ov">
        <h2><strong class="sch_word"><?php echo $stx ?></strong> 전체검색 결과</h2>
        <dl>
            <dt>카테고리</dt>
            <dd><strong><?php echo $board_count ?>개</strong></dd>
            <dt>상품</dt>
            <dd><strong><?php echo number_format($total_count) ?>개</strong></dd>
        </dl>
        <p><?php echo number_format($page) ?>/<?php echo number_format($total_page)?>

         페이지 열람 중</p>
    </section>
    <?php
         }
    }
    ?>

    <?php
    
    if ($stx) {
        if ($board_count) {
     ?>
    <ul id="sch_res_board">
        <li><a href="?<?php echo $search_query ?>&amp;gr_id=<?php echo $gr_id ?>" <?php echo $sch_all ?>>전체 카테고리</a></li>
        <?php echo $str_board_list; ?>
    </ul>
    <?php
        } else {
     
     ?>
     
    <div class="empty_list">검색된 상품이 없습니다.</div>
    <?php } }  ?>

    <div class="list_01">
    <?php if ($stx && $board_count) { ?><section class="sch_res_list"><?php }  ?>

    
    <?php
    $k=0;
    //1depth for
    for ($idx=$table_index, $k=0; $idx<count($g5_search['tables']) && $k<$rows; $idx++):
        $i=0;
    ?>

    <?php if($list[$idx]): ?>
        <h2><a href="<?php echo get_pretty_url($g5_search['tables'][$idx], '', $search_query); ?>"><?php echo $bo_subject[$idx] ?> 카테고리 결과</a></h2>
        <ul class="data_grid">
        <?php
            for ($i=0; $i<count($list[$idx]) && $k<$rows; $i++, $k++)://2depth for

                $fileInfo = get_file($g5_search['tables'][$idx],$list[$idx][$i]['wr_id']);
                if($fileInfo['count'] != 0){
                    if($fileInfo[1]['image_width'] >= 150 || $fileInfo[1]['image_width'] >= 150){

                        $listImageFileName = thumbnail($fileInfo[1]['file'],G5_DATA_PATH.'/file/'.$g5_search['tables'][$idx].'/',G5_DATA_PATH.'/file/'.$g5_search['tables'][$idx].'/',150,150,false,true,'center',false,'80/0.5/3');

                        $img_content = '<a href="'.$list[$idx][$i]['href'].'"><img src="'.$fileInfo[1]['path'].'/'.$listImageFileName.'" class="card-img-top img-thumbnail"></a>';
                    }
                    else{
                        $img_content = '<a href="'.$list[$idx][$i]['href'].'"><img src="'.$fileInfo[1]['path'].'/'.$fileInfo[1]['file'].'" class="card-img-top img-thumbnail"></a>';
                    }
                }

                $unit = (isset($list[$idx][$i]['wr_price_type']) && $list[$idx][$i]['wr_price_type']=='KRW') ? '원' : 'TP3';
                if($list[$idx][$i]['wr_price_type'] == "KRW" ) {
                    $price = "<p>".number_format((float)$list[$idx][$i]['wr_10'])."<span class='unit'>원</span></p>";

                }
                else if($list[$idx][$i]['wr_price_type']== "TP3MC") {
                    $price ='';
                    if($list[$idx][$i]['wr_1'] != 0){
                        $price .= "<p>".number_format($list[$idx][$i]['wr_1'])."<span class='unit'>e-TP3</span></p>";
                    }
                    if($list[$idx][$i]['wr_2'] != 0){
                        $price .= "<p>".number_format($list[$idx][$i]['wr_2'])."<span class='unit'> e-MC</span></p>";
                    }

                }
                else if ($list[$idx][$i]['wr_price_type']=="TP3") {
                    $price = '<p>'.number_format((float)$list[$idx][$i]['wr_1'])."<span class='unit'>e-TP3</span></p>";
                }
                else if ($list[$idx][$i]['wr_price_type']=="MC"){
                    $price = "<p>".number_format($list[$idx][$i]['wr_2'])."<span class='unit'>e-MC</span></p>";
                }
                else if ($list[$idx][$i]['wr_price_type']=="EKRW"){
                    $price = "<p>".number_format($list[$idx][$i]['wr_3'])."<span class='unit'>e-KRW</span></p>";
                }
                else if ($list[$idx][$i]['wr_price_type']=="ECTC"){
                    $price = "<p>".number_format($list[$idx][$i]['wr_4'])."<span class='unit'>e-CTC</span></p>";
                }
                else if ($list[$idx][$i]['wr_price_type'] == "CREDITCARD"){
                    $price = "<p>".number_format($list[$idx][$i]['wr_10'])."<span class='unit'>원</span></p>";
                }
        ?>
                <?php if ($list[$idx][$i]['subject']): //제목이 있으면 게시물을 보여줌?>
                        <li class="<?=($list[$idx][$i]['is_notice']) ? "bo_notice cardWrap" : 'cardWrap' ?>">
                            <div class="cell card <?php echo($list[$idx][$i]['it_soldout']==1)?'soldout':'' ?>">
                                <?php if ($is_checkbox) { ?>
                                    <label for="chk_wr_id_<?=$i ?>" class="sound_only"><?=$list[$idx][$i]['subject'] ?></label>

                                    <input type="checkbox" name="chk_wr_id[]" value="<?=$list[$idx][$i]['wr_id'] ?>" id="chk_wr_id_<?=$i ?>">
                                <?php } ?>
                                    <div class="card-img-area">
                                        <?=$img_content?>
                                        <?php echo($list[$idx][$i]['it_soldout']==1)?'<span class="badge badge-danger soldout">품절</span>':'' ?>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($is_category && $list[$idx][$i]['ca_name']) { ?>
                                            <a href="<?php echo $list[$idx][$i]['href'] ?><?php echo $comment_href ?>"><?php echo $comment_def ?><?php echo $list[$idx][$i]['subject'] ?></a>
                                        <?php } ?>
                                            <a href="<?=$list[$idx][$i]['href'] ?>" class="listSbjA">
                                                <div class="SbjBlock card-title">
                                                <a href="<?php echo $list[$idx][$i]['href'] ?><?php echo $comment_href ?>"><?php echo $comment_def ?><?php echo $list[$idx][$i]['subject'] ?></a>
                                                    <?php if ($list[$idx][$i]['comment_cnt']) { ?>
                                                        <span class="sound_onl  y">댓글</span>
                                                        <span class="cnt_cmt">+ <?=$list[$idx][$i]['wr_comment'] ?></span>
                                                        <span class="sound_only">개</span><?php } ?>
                                                </div>
                                                <div class="card-text">
                                                    <div class="amount">
                                                        <?php echo $price ?>
                                                    </div>
                                                    <p class="retailPrice">
                                                        <?php
                                                            $wr_retail_price = (isset($list[$idx][$i]['wr_retail_price'])) ? $list[$idx][$i]['wr_retail_price'] : '0';
                                                            if (empty($wr_retail_price)) $wr_retail_price = '0';
                                                            $wr_retail_price = round($wr_retail_price);
                                                            if ($wr_retail_price > 0) {
                                                                echo "(소비자가 ".number_format($list[$idx][$i]['wr_retail_price'])." 원)";
                                                            }
                                                            else {
                                                                echo "&nbsp;";
                                                            }
                                                        ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <?php if($list[$idx][$i]['wr_price_type'] == "CREDITCARD"): ?>
                                                        <span class="badge badge-dark">카드 결제</span>
                                                    <?php elseif($list[$idx][$i]['wr_price_type'] == "KRW"): ?>
                                                        <span class="badge badge-dark">현금 결제</span>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                    </div>
                            </div>
                        </li>

                <?php endif; ?>
                <?php if (count($list) == 0): ?>
                    <li class="empty_table">
                        <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL ; ?>/bbs_empty.png" />
                        <div class="desc">
                            등록된 상품이 없습니다
                        </div>
                    </li>
                <?php endif; ?>
           <?php endfor;//2depth for end ?>
        </ul>
    <?php endif; ?>

    <?php
        //wr_subejct
        $i=0;
        if($list[$idx][$i]['wr_subject']):
    ?>

        <div class="sch_more btn btn-secondary btn-block">
            <a href="<?php echo get_pretty_url($g5_search['tables'][$idx], '', $search_query); ?>"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo $bo_subject[$idx] ?> 검색 결과 더보기</a>
        </div>

    <?php
        endif;
    endfor//1depth for end
    ?>

    <?php if ($stx && $board_count) {  ?></section><?php }  ?>

    </div>

    <?php echo $write_pages ?>

    
</div>
