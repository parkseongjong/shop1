<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<style type="text/css">
.seller_info {text-align:center;}
.seller_info .profile_image {width:50px;border-radius:50px;}
.seller_info .seller_badge {border:1px solid #afafaf;color:#9f9f9f; margin-right:8px;padding:0px 5px; border-radius:10px;font-size:12px;line-height:10px;height:11px;}
.seller_info .seller_name {font-size:1.2em;margin-right:8px}
.seller_info .sellet_phone {font-size:1.1em;margin-left:8px}

#good_info {margin:20px}
#good_info ul {list-style-type:none; width:100%; overflow:hidden;}
#good_info li {float:left;}
#good_info li.thumb {width:26%;}
#good_info li.thumb img {width:100%}
#good_info li.desc {width:68%; margin-left:6%; font-size:15px;}
#good_info li.desc .price_line {margin-top:10px; text-align:right;}
#good_info li.desc .unit {font-size:14px; color:#999;}
#good_info li.desc .amount {font-size:16px; font-weight:700; color:#222; margin-left:10px;}

#memo_list {padding-bottom:80px}
.win_desc {text-align:center}

#input_section {position:fixed; bottom:0; height:60px; line-height:60px; border-top:1px solid #ddd; width:100%; background:white;}
#input_section ul {list-style-type:none; width:100%; overflow:hidden;}
#input_section li {float:left;}
#input_section li.input {width:86%}
#input_section li.img {width:10%;padding-top:8px}
#input_section input {width:92%; height:40px; line-height:40px; border:1px solid #ccc; border-radius:40px; padding:0 16px; margin-left:14px; font-size:16px; background:#fafafa;}
#input_section img {width:100%}
</style>

<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div class="to_content"><a href="#container">본문 바로가기</a></div>

    <div id="hd_wrapper">

        <div id="logo">
            <div class="seller_info">
                <p><span class="seller_badge">판매자</span><span class="seller_name"><?=$view['wr_name']?></span></p>
            </div>
        </div>
        <button type="button" id="gnb_back"><span class="sound_only"> 이전</span></button>

    </div>

    <script>
    $("#gnb_back").on("click", function() {
        history.back();
    });
    </script>
</header>

<?php
$bo_gallery_width = 150;
$bo_gallery_height = 150;

$thumb = get_list_thumbnail($board['bo_table'], $wr_id, $bo_gallery_width, $bo_gallery_height, false, true);

if($thumb['src']) {
    $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" class="base_img" />';
} else {
    $img_content = '<!-- no image -->';
}
?>

<div id="good_info">
    <ul>
        <li class="thumb">
            <div><?=$img_content?></div>
        </li>
        <li class="desc">
            <?=$view['subject'] ?>
            <div class="price_line"><span class="unit">판매금액</span><span class="amount"><?=number_format($view['wr_1']) ?> TP3</span>
        </li>
    </ul>
</div>

<style type="text/css">

#memo_log .me {margin-right:16px;margin-top:24px;padding-bottom:12px;}
#memo_log .me ul {list-style-type:none;width:100%;overflow-x:hidden;border-radius:15px;}
#memo_log .me li {float:right;margin:0;padding:0;border:0;}
#memo_log .me li.sym {width:15px;height:30px;background:url('/img/chat_right.png') -3px 20px no-repeat;background-size:100%;}
#memo_log .me li.desc {width:260px;max-width:66%;background:#515151;border-radius:15px;padding:13px 19px;color:white}
#memo_log .me li.dt {font-size:11px;color:#a2a2a2;margin-right:10px;padding-top:10px;}

#memo_log .target {margin-left:16px;margin-top:24px;padding-bottom:12px;}
#memo_log .target ul {list-style-type:none;width:100%;overflow-x:hidden}
#memo_log .target li {float:left;margin:0;padding:0;border:0;}
#memo_log .target li.img {width:50px;padding-top:2px;}
#memo_log .target li.img img {width:100%;border-radius:50px;}
#memo_log .target li.sym {width:15px;height:30px;background:url('/img/chat_left.png') 3px 20px no-repeat;background-size:100%;}
#memo_log .target li.desc {width:220px;max-width:60%;background:#f6f6f6;border-radius:15px;padding:13px 19px;}
#memo_log .target li.dt {font-size:11px;color:#a2a2a2;margin-left:10px;padding-top:10px;}

#memo_list .win_desc {margin-top:24px;}
#memo_list .datetime {width:86px; margin:16px auto; background:#c1c1c1; color:white; border-radius:20px; padding:4px 13px; text-align:center;}
</style>

<!-- 쪽지 목록 시작 { -->
<div id="memo_list" class="new_win">
    <div class="new_win_con2">

        <div id="memo_log" class="memo_list">
<?php
    $target_id = $view['mb_id'];    // 판매자 아이디
    $mb_id = $member['mb_id'];      // 로그인한 회원

    $sql = "select mr_id from g5_memo_room where me_recv_mb_id = '{$target_id}' and me_send_mb_id = '{$mb_id}'";
    $row = sql_fetch($sql);

    $day_letter = array("일","월","화","수","목","금","토");
    $date = "00.00";

    if ($row) {
        $sql = "select * from g5_memo_new where mr_id = '".$row['mr_id']."' order by me_id asc";
        $result = sql_query($sql);

        while ($row = sql_fetch_array($result)) {

            $wdate = str_replace('-', '.', substr($row['me_write_datetime'], 5, 5));
            if ($wdate[0]=='0') $wdate = substr($wdate, 1);

            $whour = round(substr($row['me_write_datetime'], 11, 2));
            $wmin = substr($row['me_write_datetime'], 14, 2);

            if ($whour>12) {
                $apm = '오후';
                $whour -= 12;
            } else {
                $apm = '오전';
            }

            if ($wdate != $date) {
                $date = $wdate;
                $day_w = date('w', strtotime($row['me_write_datetime']));
                echo "<div class='datetime'>".$date." ({$day_letter[$day_w]})</div>";
            }

            if ($row['me_write_mb_id']==$mb_id) {   // 내가 쓴글
                echo "<div class='me'><ul><li class='sym'>&nbsp;</li><li class='desc'>".$row['me_memo']."</li><li class='dt'>{$apm}<br />{$whour}:{$wmin}</li></ul></div>";
            } else {
                echo "<div class='target'><ul><li class='img'><img src='".BARRY_THEME_CUSTOM_MOBILE_IMG_URL."/no_profile.gif' alt='profile_image' /></li><li class='sym'>&nbsp;</li><li class='desc'>".$row['me_memo']."</li><li class='dt'>{$apm}<br />{$whour}:{$wmin}</li></ul></div>";
            }
        }
    }
?>
</div>

        <p class="win_desc"><i class="fa fa-info-circle" aria-hidden="true"></i> 쪽지 보관일수는 최장 <strong><?php echo $config['cf_memo_del'] ?></strong>일 입니다.
        </p>
    </div>
</div>
<!-- } 쪽지 목록 끝 -->

<?php
//print_r($view);
?>

<form name="fmemoform" id="fmemoform" onsubmit="return false" autocomplete="off">
<input type="hidden" name="target_id" value="<?=$target_id?>" />

<div id="input_section">
    <ul>
        <li class="input">
            <input type="text" name="me_memo" id="me_memo" value="" placeholder="메세지를 입력하세요." />
        </li>
        <li class="img">
            <img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/send_message.png" onclick="sendMemo()" />
        </li>
    </ul>
</div>

</form>

<script>
function sendMemo() {
    var memo = $.trim($('#me_memo').val());
    if (memo==''){
        alert('내용을 입력하세요.'); return;
    }
    $.ajax({
        url : '/bbs/memo_update.php',
        type : 'POST',
        data : $('#fmemoform').serialize(),
        dataType : 'json',
        success : function(resp){
            if (resp.msg == 'fail') {
                alert(''+resp.msg);
            } 
            else if (resp.msg == 'success') {
                var msg = $('#me_memo').val();
                msg = $(msg).text().trim();
                
                var msg2 = "<div class='me'><ul><li class='sym'>&nbsp;</li><li class='desc'>";
                msg2 += msg;
                msg2 += "</li><li class='dt'>조금전</li></ul></div>";
                $('#memo_log').append(msg2);
                //$('#memo_log').scrollTop($('#memo_log')[0].scrollHeight);
                    
                $('#me_memo').val('');
            } 
            else {
                alert('알수없는 오류');
            }
        },
        error : function(resp){
            alert('저장실패. 잠시후 다시 이용하시기 바랍니다.');
        }
    });
}
</script>
