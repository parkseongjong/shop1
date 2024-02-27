<?
include_once('./_common.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/poll.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/connect.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/latest_group.lib.php');
?>

<!-- 게시판 목록 시작 { -->
<div id="bo_list" style="width:<?php echo $width; ?>">


    <form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">


    <!-- } 게시판 페이지 정보 및 버튼 끝 -->
    <div class="tbl_head01 tbl_wrap"  style="background:#fff">
        <table>
      
        <thead>
        <tr>

            <th scope="col">내가 쓴 글</th>
            <?php if ($is_good) { ?><th scope="col"><?php echo subject_sort_link('wr_good', $qstr2, 1) ?>추천 <i class="fa fa-sort" aria-hidden="true"></i></a></th><?php } ?>
            <?php if ($is_nogood) { ?><th scope="col"><?php echo subject_sort_link('wr_nogood', $qstr2, 1) ?>비추천 <i class="fa fa-sort" aria-hidden="true"></i></a></th><?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $i<count($list); $i++) {
			 if($member['mb_id']!=$list[$i]['mb_id'])
			 continue;

         ?>
        <tr class="<?php if ($list[$i]['is_notice']) echo "bo_notice"; ?>">
           <!-- <td class="td_num2">
            <?php
            if ($list[$i]['is_notice']) // 공지사항
                echo '<strong class="notice_icon"><i class="fa fa-bullhorn" aria-hidden="true"></i><span class="sound_only">공지</span></strong>';
            else if ($wr_id == $list[$i]['wr_id'])
                echo "<span class=\"bo_current\">열람중</span>";
            else
                echo $list[$i]['num'];
             ?>
            </td>-->

            <td class="td_subject" style="padding-left:<?php echo $list[$i]['reply'] ? (strlen($list[$i]['wr_reply'])*10) : '0'; ?>px">
                <div class="bo_tit">
						
                        <?php
                            $thumb = get_list_thumbnail($board['bo_table'], $list[$i]['wr_id'], $board['bo_gallery_width'], $board['bo_gallery_height'], false, true);

                            if($thumb['src']) {
                                $img_content = '<a href="'.$list[$i]['href'].'" class="listImgA"><i class="listImg"><img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" ></i></a>';
								} else {
                                $img_content = '<!-- no image -->';
								}

								echo $img_content;
                         ?>
                        <?php echo $list[$i]['icon_reply'] ?>
                        <?php
                            if (isset($list[$i]['icon_secret'])) echo rtrim($list[$i]['icon_secret']);
                         ?>
				 <div>
                <?php
                if ($is_category && $list[$i]['ca_name']) {
                 ?>
                <a href="<?php echo $list[$i]['ca_name_href'] ?>" class="bo_cate_link"><?php echo $list[$i]['ca_name'] ?></a>
                <?php } ?>		
				</div>
				
				<div><a href="<?php echo $list[$i]['href'] ?>" class="listSbjA">
                        
						<strong><?php echo $list[$i]['subject'] ?></strong>
                       
						<?php
						// if ($list[$i]['link']['count']) { echo '['.$list[$i]['link']['count']}.']'; }
						// if ($list[$i]['file']['count']) { echo '<'.$list[$i]['file']['count'].'>'; }
						//if (isset($list[$i]['icon_file'])) echo rtrim($list[$i]['icon_file']);
						//if (isset($list[$i]['icon_link'])) echo rtrim($list[$i]['icon_link']);
						if (isset($list[$i]['icon_new'])) echo rtrim($list[$i]['icon_new']);
						if (isset($list[$i]['icon_hot'])) echo rtrim($list[$i]['icon_hot']);
						?>
                    <?php if ($list[$i]['comment_cnt']) { ?><span class="sound_only">댓글</span><span class="cnt_cmt">+ <?php echo $list[$i]['wr_comment']; ?></span><span class="sound_only">개</span><?php } ?>
                    </div>
<!--					<em class="listCont"><?php echo cut_str(strip_tags($list[$i]['wr_content']),30," . . . ") ?></em>-->
					<em class="listCont" style="font-size:16px; font-weight:bold"><?php echo number_format($list[$i]['wr_1']); ?>원</em>
					<u class="listInfo">
						<span class="sound_only">작성자</span> <u class="listInfoName"><?php echo $list[$i]['name'] ?></u> / 
						<span class="sound_only">조회</span><i class="fa fa-eye" aria-hidden="true"></i> <u><?php echo $list[$i]['wr_hit'] ?></u> / 
						<span class="sound_only">작성일</span><i class="fa fa-clock-o" aria-hidden="true"></i> <u><?php echo $list[$i]['datetime2'] ?></u>
					</u></a>
                </div>

            </td>
            <?php if ($is_good) { ?><td class="td_num"><?php echo $list[$i]['wr_good'] ?></td><?php } ?>
            <?php if ($is_nogood) { ?><td class="td_num"><?php echo $list[$i]['wr_nogood'] ?></td><?php } ?>

        </tr>
        <?php } ?>
        <?php if (count($list) == 0) { echo '<tr><td colspan="'.$colspan.'" class="empty_table">게시물이 없습니다.</td></tr>'; } ?>
        </tbody>
        </table>
    </div>
	</form>
</div>