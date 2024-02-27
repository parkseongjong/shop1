<?php
if (!defined('_GNUBOARD_')) exit;
//include_once(G5_PLUGIN_PATH.'/barryCoupon/GbCoupon.php');
//use barry\gbCoupon\Coupon as gbCoupon;
include_once(G5_PLUGIN_PATH.'/barryBanner/GbBanner.php');
use barry\gbBanner\Banner as gbBanner;

// 최신댓글 추출 
function latest_comment($skin_dir="", $mb_id, $rows=10, $subject_len=40, $options="") 
{ 
    global $g5; 

    if ($skin_dir) 
		$latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
       // $latest_skin_path = "$g4[path]/skin/latest/$skin_dir"; 
    else 
		$latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
        //$latest_skin_path = "$g4[path]/skin/latest/basic"; 

    $list = array(); 

$sql_common = " from $g5[board_new_table] a, $g5[board_table] b, $g5[group_table] c 
              where a.bo_table = b.bo_table and b.gr_id = c.gr_id and b.bo_use_search = '1' "; 
if ($gr_id) 
    $sql_common .= " and b.gr_id = '$gr_id' "; 
    
$sql_common .= " and a.wr_id <> a.wr_parent "; 


    $sql_common .= " and a.mb_id = '$mb_id' "; 
$sql_order = " order by a.bn_id desc "; 

if (!$page) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지) 
$from_record = ($page - 1) * $rows; // 시작 열을 구함 

$sql = " select a.*, b.bo_subject, c.gr_subject, c.gr_id 
          $sql_common 
          $sql_order 
          limit $from_record, $rows "; 

    $result = sql_query($sql); 
for ($i=0; $row=sql_fetch_array($result); $i++) 
{ 
    $tmp_write_table = $g5[write_prefix] . $row[bo_table]; 

        $comment = ""; 
        $comment_link = ""; 

        $row2 = sql_fetch(" select * from $tmp_write_table where wr_id = '$row[wr_id]' "); 
        $list[$i] = $row2; 

        $name = get_sideview($row2[mb_id], cut_str($row2[wr_name], $config[cf_cut_name]), $row2[wr_email], $row2[wr_homepage]); 
        // 당일인 경우 시간으로 표시함 
        $datetime = substr($row2[wr_datetime],0,10); 
        $datetime2 = $row2[wr_datetime]; 
        if ($datetime == $g4[time_ymd]) 
            $datetime2 = substr($datetime2,11,5); 
        else 
            $datetime2 = substr($datetime2,5,5); 



    $list[$i][gr_id] = $row[gr_id]; 
    $list[$i][bo_table] = $row[bo_table]; 
    $list[$i][name] = $name; 
    $list[$i][comment] = $comment; 
    $list[$i][href] = "$G5_PATH/bbs/board.php?bo_table=$row[bo_table]&wr_id=$row2[wr_id]{$comment_link}";
    $list[$i][datetime] = $datetime; 
    $list[$i][datetime2] = $datetime2; 

    $list[$i][gr_subject] = $row[gr_subject]; 
    $list[$i][bo_subject] = $row[bo_subject]; 
    $list[$i][subject] = conv_subject($row2[wr_content], $subject_len, "…"); 
} 

    
    ob_start(); 
    include "$latest_skin_path/latest.skin.php"; 
    $content = ob_get_contents(); 
    ob_end_clean(); 

    return $content; 
}

// 최신글 추출
// $cache_time 캐시 갱신시간
function latest($skin_dir='', $bo_table, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $g5;
    
    list($bo_table, $category) = explode("|", $bo_table);
	if($category) $where = " AND ca_name = '".$category."' ";

    if (!$skin_dir) $skin_dir = 'basic';

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

    $cache_fwrite = false;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/latest-{$bo_table}-{$skin_dir}-{$rows}-{$subject_len}-serial.php";

        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }
            
            if(!$cache_fwrite) {
                try{
                    $file_contents = file_get_contents($cache_file);
                    $file_ex = explode("\n\n", $file_contents);
                    $caches = unserialize(base64_decode($file_ex[1]));

                    $list = (is_array($caches) && isset($caches['list'])) ? $caches['list'] : array();
                    $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
                } catch(Exception $e){
                    $cache_fwrite = true;
                    $list = array();
                }
            }
        }
    }

    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();

        $sql = " select * from {$g5['board_table']} where bo_table = '{$bo_table}' ";
        $board = sql_fetch($sql);
        $bo_subject = get_text($board['bo_subject']);

        $tmp_write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
        //$sql = " select * from {$tmp_write_table} where wr_is_comment = 0 order by wr_num limit 0, {$rows} ";
        $sql = " select * from {$tmp_write_table} where wr_is_comment = 0".$where." order by wr_num limit 0, {$rows} "; 
        $result = sql_query($sql);
        for ($i=0; $row = sql_fetch_array($result); $i++) {
            try {
                unset($row['wr_password']);     //패스워드 저장 안함( 아예 삭제 )
            } catch (Exception $e) {
            }
            $row['wr_email'] = '';              //이메일 저장 안함
            if (strstr($row['wr_option'], 'secret')){           // 비밀글일 경우 내용, 링크, 파일 저장 안함
                $row['wr_content'] = $row['wr_link1'] = $row['wr_link2'] = '';
                $row['file'] = array('count'=>0);
            }
            $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);
        }

        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $caches = array(
                'list' => $list,
                'bo_subject' => sql_escape_string($bo_subject),
                );
            $cache_content = "<?php if (!defined('_GNUBOARD_')) exit; ?>\n\n";
            $cache_content .= base64_encode(serialize($caches));  //serialize

            fwrite($handle, $cache_content);
            fclose($handle);

            @chmod($cache_file, 0640);
        }
    }

    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

//삭제 되지 않은 최신 상품 노출
function latest_item($skin_dir='', $bo_table, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $g5;

    if (!$skin_dir) $skin_dir = 'basic';

    $time_unit = 3600;  // 1시간으로 고정

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

    $caches = false;

    if(G5_USE_CACHE) {
        $cache_file_name = "latest-{$bo_table}-{$skin_dir}-{$rows}-{$subject_len}-".g5_cache_secret_key();
        $caches = g5_get_cache($cache_file_name, $time_unit * $cache_time);
        $cache_list = isset($caches['list']) ? $caches['list'] : array();
        g5_latest_cache_data($bo_table, $cache_list);
    }

    if( $caches === false ){

        $list = array();

        $board = get_board_db($bo_table, true);

        $bo_subject = get_text($board['bo_subject']);

        $tmp_write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름

        $sql = " select * from {$tmp_write_table} where wr_is_comment = 0 and del_yn = 'N' and it_soldout = 0 and (it_publish = 1 OR it_publish = 99) order by wr_num limit 0, {$rows} ";

        $result = sql_query($sql);
        for ($i=0; $row = sql_fetch_array($result); $i++) {
            try {
                unset($row['wr_password']);     //패스워드 저장 안함( 아예 삭제 )
            } catch (Exception $e) {
            }
            $row['wr_email'] = '';              //이메일 저장 안함
            if (strstr($row['wr_option'], 'secret')){           // 비밀글일 경우 내용, 링크, 파일 저장 안함
                $row['wr_content'] = $row['wr_link1'] = $row['wr_link2'] = '';
                $row['file'] = array('count'=>0);
            }
            $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);

            $list[$i]['first_file_thumb'] = (isset($row['wr_file']) && $row['wr_file']) ? get_board_file_db($bo_table, $row['wr_id'], 'bf_file, bf_content', "and bf_type between '1' and '3'", true) : array('bf_file'=>'', 'bf_content'=>'');
            $list[$i]['bo_table'] = $bo_table;
            // 썸네일 추가
            if($options && is_string($options)) {
                $options_arr = explode(',', $options);
                $thumb_width = $options_arr[0];
                $thumb_height = $options_arr[1];
                $thumb = get_list_thumbnail($bo_table, $row['wr_id'], $thumb_width, $thumb_height, false, true);
                // 이미지 썸네일
                if($thumb['src']) {
                    $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" width="'.$thumb_width.'" height="'.$thumb_height.'">';
                    $list[$i]['img_thumbnail'] = '<a href="'.$list[$i]['href'].'" class="lt_img">'.$img_content.'</a>';
                    // } else {
                    //     $img_content = '<img src="'. G5_IMG_URL.'/no_img.png'.'" alt="'.$thumb['alt'].'" width="'.$thumb_width.'" height="'.$thumb_height.'" class="no_img">';
                }
            }
        }
        g5_latest_cache_data($bo_table, $list);

        if(G5_USE_CACHE) {

            $caches = array(
                'list' => $list,
                'bo_subject' => sql_escape_string($bo_subject),
            );

            g5_set_cache($cache_file_name, $caches, $time_unit * $cache_time);
        }
    } else {
        $list = $cache_list;
        $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
    }

    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

//프리미엄 아이템 노출
function latest_premium_item($skin_dir='', $bo_table, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $g5, $gbCoupon;

    if (!$skin_dir) $skin_dir = 'basic';

    $time_unit = 600;  // 캐시시간 10분

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

    $caches = false;

    if(G5_USE_CACHE) {
        $cache_file_name = "latest-premium-item-{$bo_table}-{$skin_dir}-{$rows}-{$subject_len}-".g5_cache_secret_key();
        $caches = g5_get_cache($cache_file_name, $time_unit * $cache_time);
        $cache_list = isset($caches['list']) ? $caches['list'] : array();

        //log inset class load...
        g5_latest_cache_data($bo_table, $cache_list);
        foreach ($cache_list as $key => $value){
            $gbCoupon->adItemLogInsert($value['itemAdId'],'view');
        }
    }
    if( $caches === false ){

        $list = array();

        $board = get_board_db($bo_table, true);

        $bo_subject = get_text($board['bo_subject']);

        $tmp_write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름

        //cash 파일 탈취가 우려 되어 bia 실제 컬럼 명을 노출시키지 않습니다.
        $sql = "
                select A.bia_id as itemAdId, B.*
                from barry_item_ads as A
                inner join {$tmp_write_table} as B
                on A.bia_item_id = B.wr_id
                where bia_use = 1 and bia_type = '{$bo_table}'
                order by RAND() limit 0, {$rows} ";

        $result = sql_query($sql);

        for ($i=0; $row = sql_fetch_array($result); $i++) {
            try {
                unset($row['wr_password']);     //패스워드 저장 안함( 아예 삭제 )
            } catch (Exception $e) {
            }
            $row['wr_email'] = '';              //이메일 저장 안함
            if (strstr($row['wr_option'], 'secret')){           // 비밀글일 경우 내용, 링크, 파일 저장 안함
                $row['wr_content'] = $row['wr_link1'] = $row['wr_link2'] = '';
                $row['file'] = array('count'=>0);
            }
            $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);

            $gbCoupon->adItemLogInsert($row['itemAdId'],'view');
            //프리미엄 링크
            $list[$i]['premiumLink'] = G5_BBS_URL.'/premiumLink.php?bo_table='.$bo_table.'&wr_id='.$row['wr_id'];

            $list[$i]['first_file_thumb'] = (isset($row['wr_file']) && $row['wr_file']) ? get_board_file_db($bo_table, $row['wr_id'], 'bf_file, bf_content', "and bf_type between '1' and '3'", true) : array('bf_file'=>'', 'bf_content'=>'');
            $list[$i]['bo_table'] = $bo_table;
            // 썸네일 추가
            if($options && is_string($options)) {
                $options_arr = explode(',', $options);
                $thumb_width = $options_arr[0];
                $thumb_height = $options_arr[1];
                $thumb = get_list_thumbnail($bo_table, $row['wr_id'], $thumb_width, $thumb_height, false, true);
                // 이미지 썸네일
                if($thumb['src']) {
                    $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" width="'.$thumb_width.'" height="'.$thumb_height.'">';
                    $list[$i]['img_thumbnail'] = '<a href="'.$list[$i]['href'].'" class="lt_img">'.$img_content.'</a>';
                    // } else {
                    //     $img_content = '<img src="'. G5_IMG_URL.'/no_img.png'.'" alt="'.$thumb['alt'].'" width="'.$thumb_width.'" height="'.$thumb_height.'" class="no_img">';
                }
            }
        }
        g5_latest_cache_data($bo_table, $list);

        if(G5_USE_CACHE) {

            $caches = array(
                'list' => $list,
                'bo_subject' => sql_escape_string($bo_subject),
            );

            g5_set_cache($cache_file_name, $caches, $time_unit * $cache_time);
        }
    } else {
        $list = $cache_list;
        $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
    }

    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

//banner 노출
function latest_banner($skin_dir='', $location = 'none', $publishLocation = 'none', $cache_time=1, $options='')
{
    global $g5;

    if (!$skin_dir) $skin_dir = 'basic';

    $time_unit = 3600;  // 1시간으로 고정

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

    $caches = false;

    if(G5_USE_CACHE) {
        $cache_file_name = "latest-banner-{$location}-{$publishLocation}-{$skin_dir}-".g5_cache_secret_key();
        $caches = g5_get_cache($cache_file_name, $time_unit * $cache_time);
        $cache_list = isset($caches['list']) ? $caches['list'] : array();
        g5_latest_cache_data($location, $cache_list);
    }

    if( $caches === false ){

        $list = array();
        $gbBanner = new gbBanner();
        $list = $gbBanner->draw($location, $publishLocation);
        //캐시 내용 담길곳...
        //배너 api에서 셀렉팅 데이터 보여 보여주고 , 베리 인터페이스 ㄱㄱ
        g5_latest_cache_data($location, $list);

        if(G5_USE_CACHE) {

            $caches = array(
                'list' => $list
            );

            g5_set_cache($cache_file_name, $caches, $time_unit * $cache_time);
        }
    }
    else {
        $list = $cache_list;
    }

    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

// 최신글 랜덤으로 추출 
function latest_random($skin_dir='', $bo_table, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $g5;
    list($bo_table, $category) = explode("|", $bo_table); 
	if($category) $where = " AND ca_name = '".$category."' ";
 
    if (!$skin_dir) $skin_dir = 'basic';
 
    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }
 
    $cache_fwrite = false;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/latest-{$bo_table}-{$skin_dir}-{$rows}-{$subject_len}.php";
 
        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }
 
            if(!$cache_fwrite)
                include($cache_file);
        }
    }
 
    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();
 
        $sql = " select * from {$g5['board_table']} where bo_table = '{$bo_table}' ";
        $board = sql_fetch($sql);
        $bo_subject = get_text($board['bo_subject']);
 
        $tmp_write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
        //$sql = " select * from {$tmp_write_table} where wr_is_comment = 0 order by rand() desc limit 0, {$rows} ";
        $sql = " select * from {$tmp_write_table} where wr_is_comment = 0".$where." order by rand() desc limit 0, {$rows} "; 
        $result = sql_query($sql);
        for ($i=0; $row = sql_fetch_array($result); $i++) {
            $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);
        }
 
        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $cache_content = "<?php\nif (!defined('_GNUBOARD_')) exit;\n\$bo_subject='".$bo_subject."';\n\$list=".var_export($list, true)."?>";
            fwrite($handle, $cache_content);
            fclose($handle);
        }
    }
 
    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();
 
    return $content;
}

// 그룹 최신글 추출
// $cache_time 캐시 갱신시간
function latest_group2($skin_dir='', $gr_id, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $g5;
 
    if (!$skin_dir) $skin_dir = 'basic';
 
    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }
 
    $cache_fwrite = false;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/latest-group-{$gr_id}-{$skin_dir}-{$rows}-{$subject_len}.php";
 
        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }
 
            if(!$cache_fwrite)
                include($cache_file);
        }
    }
 
    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();
        $sql_common = " from {$g5['board_new_table']} a, {$g5['board_table']} b, {$g5['group_table']} c where a.bo_table = b.bo_table and b.gr_id = c.gr_id and b.bo_use_search = 1 ";
        $sql_common .= " and b.gr_id = '$gr_id' ";
        // $sql_common .= " and a.bo_table not in ('aaaa', 'bbbb') ";
        $sql_common .= " and a.wr_id = a.wr_parent ";
        $sql_order = " order by a.bn_id desc ";
        $sql = " select a.*, b.bo_subject, c.gr_subject, c.gr_id {$sql_common} {$sql_order} limit 0, {$rows}";
		$result = sql_query($sql);
 
		for ($i=0; $row=sql_fetch_array($result); $i++) {
 
			$sql = " select * from {$g5['board_table']} where bo_table = '{$row['bo_table']}' ";
			$board = sql_fetch($sql);
			$gr_subject = $row['gr_subject'];
 
			$tmp_write_table = $g5['write_prefix'] . $row['bo_table'];
			$row2 = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$row['wr_id']}' ");
 
			$list[$i] = $row2;
			$list[$i] = get_list($row2, $board, $latest_skin_url, $subject_len);
			$list[$i]['bo_subject'] = $row['bo_subject'];
			$list[$i]['bo_table'] = $row['bo_table'];
		}
 
        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $cache_content = "<?php\nif (!defined('_GNUBOARD_')) exit;\n\$gr_subject='".$gr_subject."';\n\$list=".var_export($list, true)."?>";
            fwrite($handle, $cache_content);
            fclose($handle);
        }
    }
 
    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();
 
    return $content;
}







// 스크랩
// $cache_time 캐시 갱신시간
function my_scrap($skin_dir='', $bo_table, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $g5;
    
	$where = " AND mb_id = 'admin' ";

    if (!$skin_dir) $skin_dir = 'basic';

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

    $cache_fwrite = false;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/latest-{$bo_table}-{$skin_dir}-{$rows}-{$subject_len}-serial.php";

        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }
            
            if(!$cache_fwrite) {
                try{
                    $file_contents = file_get_contents($cache_file);
                    $file_ex = explode("\n\n", $file_contents);
                    $caches = unserialize(base64_decode($file_ex[1]));

                    $list = (is_array($caches) && isset($caches['list'])) ? $caches['list'] : array();
                    $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
                } catch(Exception $e){
                    $cache_fwrite = true;
                    $list = array();
                }
            }
        }
    }

    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();

        $sql = " select * from g5_scrap where bo_table = 'free_marketing' ";
        $board = sql_fetch($sql);

        $sql = " select * from g5_scrap where bo_table = 'free_marketing'".$where." order by ms_datetime limit 0, {$rows} "; 
        $result = sql_query($sql);
        for ($i=0; $row = sql_fetch_array($result); $i++) {
            
            $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);
        }

        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $caches = array(
                'list' => $list,
                'bo_subject' => sql_escape_string($bo_subject),
                );
            $cache_content = "<?php if (!defined('_GNUBOARD_')) exit; ?>\n\n";
            $cache_content .= base64_encode(serialize($caches));  //serialize

            fwrite($handle, $cache_content);
            fclose($handle);

            @chmod($cache_file, 0640);
        }
    }

    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}



// $bo_tables 테이블들 사이 콤마(,) 단위로 구분해서 넣을 것, 콤마 사이에 공백 없이 (ex aaa,bbb,)
function latest_all($skin_dir='', $bo_tables, $rows=10, $subject_len=40, $cache_time=1, $options='')
{

    global $g5;

    if (!$skin_dir) $skin_dir = 'basic';

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

        $list = array();
        $sql_common = " from {$g5['board_new_table']} a  where find_in_set(a.bo_table, '{$bo_tables}')";
        $sql_common .= " and a.wr_id = a.wr_parent ";
        $sql_order = " order by a.bn_id desc ";
        $sql = " select a.* {$sql_common} {$sql_order} limit 0, {$rows}";

        $result = sql_query($sql);
        
        for ($i=0; $row=sql_fetch_array($result); $i++) {

            $sql = " select * from {$g5['board_table']} where bo_table = '{$row['bo_table']}' ";

          

            $board = sql_fetch($sql);

            $tmp_write_table = $g5['write_prefix'] . $row['bo_table'];
            $row2 = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$row['wr_id']}' ");



            $list[$i] = $row2;
            $list[$i] = get_list($row2, $board, $latest_skin_url, $subject_len);
            $list[$i]['bo_subject'] = $row['bo_subject'];
            $list[$i]['bo_table'] = $row['bo_table'];
        }

    ob_start();
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
?>

