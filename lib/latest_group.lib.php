<?php
if (!defined('_GNUBOARD_')) exit;

// 최신글 추출
// $cache_time 캐시 갱신시간
function latest_group($skin_dir='', $gr_id, $mb_id='',$rows=10, $subject_len=40, $cache_time=1, $options='')

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
        $cache_file = G5_DATA_PATH."/cache/latest-{$gr_id}-group-{$skin_dir}-{$rows}-{$subject_len}.php";

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
                    $gr_subject = (is_array($caches) && isset($caches['gr_subject'])) ? $caches['gr_subject'] : '';
                } catch(Exception $e){
                    $cache_fwrite = true;
                    $list = array();
                }
            }
        }
    }

    if(!G5_USE_CACHE || $cache_fwrite) {
        $list = array();
        $sql_common = " from {$g5['board_new_table']} a, {$g5['board_table']} b, {$g5['group_table']} c where a.bo_table = b.bo_table and b.gr_id = c.gr_id and b.bo_use_search = 1 ";
        $sql_common .= " and a.mb_id = '$mb_id' ";
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

            try {
                unset($row2['wr_password']); //패스워드 저장 안함(아예 삭제)
            } catch (Exception $e) {
            }
            $row2['wr_email'] = ''; //이메일 저장 안함
            if (strstr($row2['wr_option'], 'secret')){ // 비밀글일 경우 내용, 링크, 파일 저장 안함
                $row2['wr_content'] = $row['wr_link1'] = $row2['wr_link2'] = '';
                $row2['file'] = array('count'=>0);
            }

			$list[$i] = $row2;
			$list[$i] = get_list($row2, $board, $latest_skin_url, $subject_len);
			$list[$i]['bo_subject'] = $row['bo_subject'];
			$list[$i]['bo_table'] = $row['bo_table'];
		}

        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $caches = array(
                'list' => $list,
                'gr_subject' => sql_escape_string($gr_subject),
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


?>