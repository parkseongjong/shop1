<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once(G5_THEME_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');
include_once(G5_LIB_PATH.'/barry.lib.php');

//개인정보 제 3자 제공 동의 페이지 관련 START (기존 barry는... 사용안하고 새로 변경 되니.., 임시로 넣음.

if($_SERVER['REMOTE_ADDR'] == '112.171.120.140' || $_SERVER['REMOTE_ADDR'] == '112.171.120.162') {
    if (!empty($member['mb_id'])) {
        if (empty($member['mb_5']) && $member['mb_5'] <= 0 || trim($_GET['ckey'])) {
            include_once($member_skin_path . '/personalInformation/personal-information.php');
            include_once(G5_THEME_PATH . '/tail.sub.php');
            exit();
        }
    }
}

//개인정보 제 3자 제공 동의 페이지 관련 END

$top_cate='0';

$barryThisLocation = basename($_SERVER['PHP_SELF'],'.php');
//통합 head control,
//list와 detail 구분으로 뒤로가기, 설정으로 가기 판별...
/*
 * list : 상위 홈 으로
 * detail : 상위 리스트 뒤로가기
 * settingHome : barry 메인으로
 */
//채팅톡 판매 같은 경우에는.. wrid가 있어서 아마.. view head 노출 중
$barryThisLocationControl = array(
        'memberMemoList' => 'list', //채팅톡 목록
        'memberMemoDetail' => 'detail', //채팅톡 상세, (상품 상세 > 채팅톡, 설정 > 채팅톡)
        'memberOrderList' => 'list', //주문 내역
        'memberOrderDetail' => 'detail', //상품 판매 내역 상세 (내가 주문한 내역 상세 같은 경우는 wr_id가 있어서 view head 노출 중
        'memberVirtualAccount' => 'list', // 가상지갑 입출금 내용
        'memberSetting' => 'settingHome' // 설정
);

//used는 안쓰는 table 입니다.. offline(오프라인매장) 추가 car (자동차) 제거
$targetBo_table = array('Shop','offline','car','estate','market','used','media');
if (isset($bo_table)) {
    switch($bo_table) {
        case 'Shop':
            $top_cate='1';
            break;
//        case 'car':
//            $top_cate='2';
//            break;
        case 'offline':
            $top_cate='2';
            break;
        case 'estate':
            $top_cate='3';
            break;
        case 'market':
            $top_cate='4';
            break;
        case 'used':
            $top_cate='5';
            break;
        case 'media':
            $top_cate='6';
            break;
    }
}
?>
<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>
    <div class="to_content"><a href="#container">본문 바로가기</a></div>
    <?php
        if(defined('_INDEX_')) { // index에서만 실행
            include BARRY_THEME_CUSTOM_PATH.'/mobile/newwin.inc.php'; // 팝업레이어
        }
    ?>
    <div id="hd_wrapper">
        <?php if(isset($wr_id) && $wr_id): ?>
            <!-- View head -->
            <div id="logo" class="textTitle">
                <?php echo utf8_strcut($g5['title'],17) ?>
            </div>
            <?php if(in_array($bo_table,$targetBo_table)): ?>
                <a href="<?php echo get_pretty_url($bo_table,'','&page='.$page); ?>">
                    <button type="button" class="gnb_back"><span class="sound_only"> 이전</span></button>
                </a>
            <?php else: ?>
                <button type="button" id="gnb_back" class="gnb_back"><span class="sound_only"> 이전</span></button>
            <?php endif; ?>
            <?php if(in_array($bo_table,$targetBo_table)): ?>
                <button type="button" id="gnb_like"><span class="sound_only"> 좋아요</span></button>
            <?php endif; ?>
        <?php elseif(array_key_exists($barryThisLocation,$barryThisLocationControl))://통합 head를 쓰는 페이지 control ?>
            <!-- Control head -->
            <div id="logo" class="textTitle">
                <?php echo $g5['title'] ?>
            </div>
            <?php foreach ($barryThisLocationControl as $barryThisLocationControlKey => $barryThisLocationControlValue): ?>
                <?php if($barryThisLocationControlKey == $barryThisLocation): ?>
                    <?php if($barryThisLocationControlValue == 'list'): ?>
                        <button type="button" id="setting_go" class="gnb_back"><span class="sound_only"> 이전</span></button>
                    <?php elseif($barryThisLocationControlValue == 'detail'): ?>
                        <button type="button" id="gnb_back" class="gnb_back"><span class="sound_only"> 이전</span></button>
                    <?php elseif($barryThisLocationControlValue == 'settingHome'): ?>
                        <button type="button" id="main_go" class="gnb_back"><span class="sound_only"> 이전</span></button>
                    <?php endif; ?>
                    <?php break; ?>
                <?php else: ?>
<!--                    없는 경우는 대부분 인베드에서 처리 됨.-->
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
             <!-- Normarl head -->
            <div id="logo">
                <article class="headerTopMenu">
                    <ul>
                        <li>
                            <a href="<?php echo G5_PLUGIN_URL; ?>/barryCoupon/#">
                                <span class="couponIco"></span>
                            </a>
                        </li>
                    </ul>
                </article>
                <a href="<?php echo G5_URL ?>"><img src="<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/logo_3.png" alt="<?php echo $config['cf_title']; ?>"></a>
            </div>
            <button type="button" id="gnb_open"><span class="sound_only"> 메뉴열기</span></button>
            <button type="button" id="sch_open"><span class="sound_only"> 검색열기</span></button>
            <button type="button" id="setting_go" class="setting_go"><span class="sound_only"> 설정</span></button>
            <div id="top_cate">
                <ul>
                    <li class="<?=($top_cate=='1')?'on':''?>" onclick="goShopping()">P2P쇼핑</li>
                    <li class="<?=($top_cate=='2')?'on':''?>" onclick="goOffline()">오프라인 매장</li>
                    <li class="<?=($top_cate=='3')?'on':''?>" onclick="goEstate()">부동산</li>
                    <li class="<?=($top_cate=='4')?'on':''?>" onclick="goMarket()">벼룩시장</li>
                    <li class="<?=($top_cate=='6')?'on':''?>"><a href="<?php echo G5_URL;?>/media" target="_self">동영상</a></li>
                </ul>
            </div>
        <?php endif ?>
        <?php
            unset($barryThisLocationControlKey, $barryThisLocationControlValue, $barryThisLocationControl);
        ?>
    <article id="gnb">
        <div class="contents">
            <button type="button" id="gnb_close" class="pc_sound_only"><i class="fa fa-times"></i><span class="sound_only">메뉴닫기 </span></button>

            <?php echo outlogin('theme/basic'); // 외부 로그인 ?>

            <div class="gnb_1dul_shopping">
                <div class="gnb_cate_title">P2P 쇼핑</div>
                <table>
                    <tr>
                        <?php
                        $menu_datas = get_menu_db(1, true);
                        $i=0;
                        foreach($menu_datas as $row){
                            if( empty($row) ) continue;
                        ?>
                            <td>
                                <a href="<?=$row['me_link'] ?>" target="_<?=$row['me_target'] ?>" class="gnb_1da"><?=$row['me_name'] ?></a>
                            </td>
                        <?php
                        $i++;
                        if ($i%3==0) echo "</tr><tr>";
                        } //end foreach $row

                        if (count($menu_datas) == 0) {  ?>
                                <td id="gnb_empty">메뉴 준비 중입니다.<?php if ($is_admin) { ?> <br><a href="<?php echo G5_ADMIN_URL; ?>/menu_list.php">관리자모드 &gt; 환경설정 &gt; 메뉴설정</a>에서 설정하세요.<?php } ?></td>
                        <?php } ?>
                    </tr>
                </table>
            </div>

            

            <div class="gnb_1dul_shopping">
                <div class="gnb_cate_title">이벤트</div>
                <table>
                    <tr class="header">
                        <td>기획전</td>
                        <td>이벤트</td>
                        <td>쿠폰/캐시백</td>
                    </tr>
                    <tr>
                        <td><a class="notready" href="javascript:;" onclick="notready()">5월 기획전</a></td>
                        <td><a class="notready" href="javascript:;" onclick="notready()">5월 이벤트</a></td>
                        <td><a class="notready" href="javascript:;" onclick="notready()">20% 캐시백</a></td>
                    </tr>
                </table>
            </div>

            <div class="gnb_1dul_shopping">
                <div class="gnb_cate_title">부동산</div>
                <table>
                    <tr class="header">
                        <td>매입</td>
                        <td>전세</td>
                        <td>월세</td>
                    </tr>
                    <tr>
                        <td><a class="notready" href="javascript:;" onclick="notready()">상가</a></td>
                        <td><a class="notready" href="javascript:;" onclick="notready()">임대</a></td>
                        <td><a class="notready" href="javascript:;" onclick="notready()">공인중개사</a></td>
                    </tr>
                </table>
            </div>

        </div>
    </article>
    
	<div style="min-width:180px;width:80%;height:95px; border:0px solid blue;display: none;background-color:white; border-radius: 5px;padding:10px;box-shadow: 0px 0px 10px 5px rgba(130, 170, 120, 0.5);" id="xx1">
	     <a href="javascript:a1x();" style="text-align: center;float:right; padding: 3px; font-size:30xp;border:1px solid#B9B9B9;color:red;width:28px;border-radius: 5px;">X</a>
		www.barrybarries.kr 메세지
		<br><span style="font-size:15px;margin-top:15px;height:40px;"  > 검색어는 두글자 이상 입력하십시오.</span>
		 <span style="padding-top:3px; display: block"> <button type="button" value="close" style="color:cadetblue; border-radius: 7px; float:right;padding:5px;"  onclick="javascript:a1x(); "> 확 인 </button></span>
	</div>
				
        <div id="hd_sch">
            <h2>사이트 내 전체검색</h2>
            <form name="fsearchbox" action="<?php echo G5_BBS_URL ?>/search.php" onsubmit="return fsearchbox_submit(this);" method="get">
            <input type="hidden" name="sfl" value="wr_subject||wr_content">
            <input type="hidden" name="sop" value="and">
            <input type="text" name="stx" id="sch_stx" placeholder="검색어(필수)" required maxlength="20">
            <button type="submit" value="검색" id="sch_submit"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">검색</span></button>
            </form>

            <?php echo popular('theme/basic'); // 인기검색어 ?>
            <button type="button" class="btn_close">닫기</button>
        </div>
    </div>
</header>
<script>
//(레거시 코드) 수정 필요.
$(function () {
<?php
    $sql = "select count(*) as cnt from g5_scrap where mb_id = '{$member['mb_id']}' and bo_table = '{$bo_table}' and wr_id = '{$wr_id}'";
    $row = sql_fetch($sql);
    $scrap_yn = ($row['cnt'] > 0) ? true : false;

    if ($scrap_yn) {
?>
    $("#gnb_like").css('background-image', "url(<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/gnb_like_on.png)");
    <?php } ?>

    $("#gnb_like").on("click", function() {
        
    <?php if ($is_guest) { ?>
        alert('로그인한 회원만 이용 가능합니다.');
    <?php } else { ?>
        $.ajax({
            url : '/bbs/favorite_add.php',
            type : 'POST',
            data : {'wr_id':<?=$wr_id?>, 'bo_table':'<?=$bo_table?>'},
            dataType : 'json',
            success : function(resp){
                if (resp.err) {
                    alert(''+resp.err);
                } else if (resp.success) {
                    if (resp.success=='add'){
                        $("#gnb_like").css('background-image', "url(<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/gnb_like_on.png)");
                        //alert('찜목록에 추가되었습니다.');
                    } else if (resp.success=='remove'){
                        $("#gnb_like").css('background-image', "url(<?php echo BARRY_THEME_CUSTOM_MOBILE_IMG_URL; ?>/gnb_like.png)");
                        //alert('찜 목록에서 제거되었습니다.');
                    } else {
                        alert(''+resp.err);
                    }
                } else {
                    alert('알수없는 오류');
                }
            },
            error : function(resp){
                alert('실패. 잠시후 다시 이용하시기 바랍니다.');
            }
        });
        <?php } ?>
    });
    
});
</script>


<div id="wrapper">
    <div id="container">