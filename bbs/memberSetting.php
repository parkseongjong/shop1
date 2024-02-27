
<?php
include_once('./_common.php');

if ($is_guest) {
    alert('로그인된 회원만 이용하실 수 있습니다.');
}

$g5['title'] = '설정';
include_once('./_head.php');

include_once($member_skin_path.'/memberSetting.skin.php');

include_once(G5_PATH.'/tail.sub.php');
?>
