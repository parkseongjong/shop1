<?php
/*
 *
 * 상품 리스트를 가져오는 latest 입니다.. 레거시 스타일을 그대로 적용 하였습니다.
 *
 */
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
$list_count = (is_array($list) && $list) ? count($list) : 0;
?>

<?php if (count($list) != 0) : ?>
    <article class="premiumItemsLates">
        <h2>
            프리미엄 등록 광고
        </h2>
        <ul>
        <?php for ($i=0; $i<$list_count; $i++) :

            $fileInfo = get_file($bo_table,$list[$i]['wr_id']);

            if($fileInfo['count'] != 0){
                if($fileInfo[1]['image_width'] >= 150 || $fileInfo[1]['image_width'] >= 150){

                    $listImageFileName = thumbnail($fileInfo[1]['file'],G5_DATA_PATH.'/file/'.$bo_table.'/',G5_DATA_PATH.'/file/'.$bo_table.'/',150,150,false,true,'center',false,'80/0.5/3');

                    $img_content = '<a href="'.$list[$i]['premiumLink'].'"><img src="'.$fileInfo[1]['path'].'/'.$listImageFileName.'" class="card-img-top img-thumbnail"></a>';
                }
                else{
                    $img_content = '<a href="'.$list[$i]['premiumLink'].'"><img src="'.$fileInfo[1]['path'].'/'.$fileInfo[1]['file'].'" class="card-img-top img-thumbnail"></a>';
                }
            }
            else{
                $img_content = '<a href="'.$list[$i]['premiumLink'].'"><div class="img-thumbnail notImage">이미지가 없습니다.</div></a>';
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
                    <div class="card-img-area">
                        <?=$img_content?>
                        <?php echo($list[$i]['it_soldout']==1)?'<span class="badge badge-danger soldout">품절</span>':'' ?>
                    </div>
                    <div class="card-body">
                        <?php if ($is_category && $list[$i]['ca_name']) { ?>
                            <p><a href="<?php echo $list[$i]['ca_name_href'] ?>" class="cate_link"><?=$list[$i]['ca_name'] ?></a></p>
                        <?php } ?>
                        <a href="<?=$list[$i]['premiumLink'] ?>" class="listSbjA">
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
                </div>
            </li>
        <?php endfor; ?>
        </ul>
    </article>
<?php endif; ?>
