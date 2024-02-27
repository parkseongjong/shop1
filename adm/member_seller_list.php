<?php
$sub_menu = "200400";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from g5_member_seller_apply A left join g5_member B on A.mb_id=B.mb_id ";

$sql_search = " where (1) ";
if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case 'confirm_yn' :
            $sql_search .= " (A.{$sfl} = '{$stx}') ";
            break;
        default :
            $sql_search .= " (A.{$sfl} like '{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}
/*
if ($is_admin != 'super')
    $sql_search .= " and mb_level <= '{$member['mb_level']}' ";
*/
if (!$sst) {
    $sst = "ms_reg_date";
    $sod = "desc";
}

$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '판매회원요청리스트';
include_once('./admin.head.php');

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$colspan = 16;
?>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <span class="btn_ov01"><span class="ov_txt">신청회원수 </span><span class="ov_num"> <?php echo number_format($total_count) ?>명 </span></span>
</div>

<form name="fmemberlist" id="fmemberlist" action="./member_seller_list_update.php" onsubmit="return fmemberlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col" id="mb_list_chk" rowspan="2" >
            <label for="chkall" class="sound_only">회원 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col" id="mb_list_id"><?php echo subject_sort_link('mb_id') ?>닉네임</a></th>
        <th scope="col" id="mb_list_name"><?php echo subject_sort_link('mb_name') ?>이름</a></th>
        <th scope="col" id="mb_list_nick"><?php echo subject_sort_link('mb_nick') ?>아이디</a></th>
        <th scope="col" id="mb_list_auth">회원상태</th>
        <th scope="col" id="mb_list_deny"><?php echo subject_sort_link('mb_level', '', 'desc') ?>권한</a></th>
        <th scope="col" id="mb_list_join"><?php echo subject_sort_link('confirm_yn', '', 'desc') ?>승인여부</a></th>
        <th scope="col" id="mb_list_join"><?php echo subject_sort_link('mb_reg_date', '', 'desc') ?>신청일</a></th>
        <th scope="col" id="mb_list_lastcall"><?php echo subject_sort_link('mb_today_login', '', 'desc') ?>최종접속</a></th>
        <th scope="col" id="mb_list_mng">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        if ($is_admin == 'group') {
            $s_mod = '';
        } else {
            $s_mod = '<a href="./member_form.php?'.$qstr.'&amp;w=u&amp;mb_id='.$row['mb_id'].'" class="btn btn_03">수정</a>';
        }

        $mb_nick = get_sideview($row['mb_id'], get_text($row['mb_nick']), $row['mb_email'], $row['mb_homepage']);

        $mb_id = $row['mb_id'];
        $leave_msg = '';
        $intercept_msg = '';
        $intercept_title = '';
        if ($row['mb_leave_date']) {
            $mb_id = $mb_id;
            $leave_msg = '<span class="mb_leave_msg">탈퇴함</span>';
        }
        else if ($row['mb_intercept_date']) {
            $mb_id = $mb_id;
            $intercept_msg = '<span class="mb_intercept_msg">차단됨</span>';
            $intercept_title = '차단해제';
        }
        if ($intercept_title == '')
            $intercept_title = '차단하기';

        $address = $row['mb_zip1'] ? print_address($row['mb_addr1'], $row['mb_addr2'], $row['mb_addr3'], $row['mb_addr_jibeon']) : '';

        $bg = 'bg'.($i%2);

    ?>

    <tr class="<?php echo $bg; ?>">
        <td headers="mb_list_chk" class="td_chk">
            <input type="hidden" name="mb_id[<?php echo $i ?>]" value="<?php echo $row['mb_id'] ?>" id="mb_id_<?php echo $i ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['mb_name']); ?> <?php echo get_text($row['mb_nick']); ?>님</label>
            <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
        </td>
        <td headers="mb_list_name" class="td_mbname2"><?php echo get_text($row['mb_nick']); ?></td>
        <td headers="mb_list_nick" class="td_name2 sv_use"><div><?php echo $row['mb_name'] ?></div></td>
        <td headers="mb_list_id" class="td_name2 sv_use">
            <?php echo $mb_id ?>
            <?php
            //소셜계정이 있다면
            if(function_exists('social_login_link_account')){
                if( $my_social_accounts = social_login_link_account($row['mb_id'], false, 'get_data') ){
                    
                    echo '<div class="member_social_provider sns-wrap-over sns-wrap-32">';
                    foreach( (array) $my_social_accounts as $account){     //반복문
                        if( empty($account) || empty($account['provider']) ) continue;
                        
                        $provider = strtolower($account['provider']);
                        $provider_name = social_get_provider_service_name($provider);
                        
                        echo '<span class="sns-icon sns-'.$provider.'" title="'.$provider_name.'">';
                        echo '<span class="ico"></span>';
                        echo '<span class="txt">'.$provider_name.'</span>';
                        echo '</span>';
                    }
                    echo '</div>';
                }
            }
            ?>
        </td>
        <td headers="mb_list_auth" class="td_mbstat">
            <?php
            if ($leave_msg || $intercept_msg) echo $leave_msg.' '.$intercept_msg;
            else echo "정상";
            ?>
        </td>
        <td headers="mb_list_auth" class="td_mbstat">
            <?php echo get_member_level_select("mb_level[$i]", 1, $member['mb_level'], $row['mb_level']) ?>
        </td>
        <td headers="mb_list_join" class="td_datetime2">
            <?php
            if ($row['confirm_yn']=='Y') {
                $confirm_yn = '승인됨';
            } else if ($row['confirm_yn']=='N') {
                $confirm_yn = '승인대기중';
            } else {
                $confirm_yn = $row['confirm_yn'];
            }
            echo $confirm_yn;
            ?>
        </td>
        <td headers="mb_list_join" class="td_datetime2"><?php echo $row['ms_reg_date']; ?></td>
        <td headers="mb_list_lastcall" class="td_date2"><?php echo substr($row['mb_today_login'],2,8); ?></td>
        <td headers="mb_list_mng" class="td_mng td_mng_s"><?php echo $s_mod ?></td>
    </tr>
    <tr class="<?php echo $bg; ?>">
        

    </tr>

    <?php
    }
    if ($i == 0)
        echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">자료가 없습니다.</td></tr>";
    ?>
    </tbody>
    </table>
</div>


</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
function fmemberlist_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택삭제") {
            return false;
    }

    return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
