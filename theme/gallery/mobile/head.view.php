<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once(G5_THEME_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/poll.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/connect.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');

if (empty($top_cate)) $top_cate='1';
?>

<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div class="to_content"><a href="#container">본문 바로가기</a></div>
<!--    <?php if ($is_admin) { ?><div class="admin_bar"><a href="<?php echo G5_ADMIN_URL ?>"> 관리자</a></div> <?php } ?>-->
    <?php
    if(defined('_INDEX_')) { // index에서만 실행
        include G5_MOBILE_PATH.'/newwin.inc.php'; // 팝업레이어
    } ?>

    <div id="hd_wrapper">

        <div id="logo">&nbsp;</div>
        <button type="button" id="gnb_back"><span class="sound_only"> 이전</span></button>
        <button type="button" id="gnb_like"><span class="sound_only"> 좋아요</span></button>
<?php/*
        <div id="top_cate">
            <ul>
                <li class="<?=($top_cate=='1')?'on':''?>" onclick="goShopping()">쇼핑</li>
                <li class="<?=($top_cate=='2')?'on':''?>" onclick="notready()">이벤트</li>
                <li class="<?=($top_cate=='3')?'on':''?>" onclick="notready()">부동산</li>
                <li class="<?=($top_cate=='4')?'on':''?>" onclick="notready()">전국현황</li>
            </ul>
        </div>
*/?>
        <div id="gnb">
            <button type="button" id="gnb_close" class="pc_sound_only"><i class="fa fa-times"></i><span class="sound_only">메뉴닫기 </span></button>

            <?php echo outlogin('theme/basic'); // 외부 로그인 ?>

            <div class="blank"></div>

            <div class="gnb_cate_title">쇼핑</div>

            <div class="gnb_1dul_shopping">
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

            <div class="gnb_cate_title">이벤트</div>

            <div class="gnb_1dul_shopping">
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

            <div class="gnb_cate_title">부동산</div>

            <div class="gnb_1dul_shopping">
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

        <div id="hd_sch">
            <h2>사이트 내 전체검색</h2>
            <form name="fsearchbox" action="<?php echo G5_BBS_URL ?>/search.php" onsubmit="return fsearchbox_submit(this);" method="get">
            <input type="hidden" name="sfl" value="wr_subject||wr_content">
            <input type="hidden" name="sop" value="and">
            <input type="text" name="stx" id="sch_stx" placeholder="검색어(필수)" required maxlength="20">
            <button type="submit" value="검색" id="sch_submit"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">검색</span></button>
            </form>

            <script>
            function fsearchbox_submit(f)
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

                return true;
            }
            function notready() {
                alert('서비스 준비중입니다..');
                return false;
            }
            function goShopping() {
                document.location.href = '/';
            }
            </script>

            <?php echo popular('theme/basic'); // 인기검색어 ?>
            <button type="button" class="btn_close">닫기</button>
        </div>


        <script>
        $(function () {
            //폰트 크기 조정 위치 지정
            var font_resize_class = get_cookie("ck_font_resize_add_class");
            if( font_resize_class == 'ts_up' ){
                $("#text_size button").removeClass("select");
                $("#size_def").addClass("select");
            } else if (font_resize_class == 'ts_up2') {
                $("#text_size button").removeClass("select");
                $("#size_up").addClass("select");
            }

            $(".hd_opener").on("click", function() {
                var $this = $(this);
                var $hd_layer = $this.next(".hd_div");

                if($hd_layer.is(":visible")) {
                    $hd_layer.hide();
                    $this.find("span").text("열기");
                } else {
                    var $hd_layer2 = $(".hd_div:visible");
                    $hd_layer2.prev(".hd_opener").find("span").text("열기");
                    $hd_layer2.hide();

                    $hd_layer.show();
                    $this.find("span").text("닫기");
                }
            });

            $(".btn_gnb_op").click(function(){
                $(this).toggleClass("btn_gnb_cl").next(".gnb_2dul").slideToggle(300);
                
            });

            $("#gnb_back").on("click", function() {
                history.back();
            });
            $("#gnb_open").on("click", function() {
                if ($("#gnb").css('display')=='block'){
                    $("#sch_open").show();  // 검색버튼 보이기
                    // 스크롤풀기
                    $('html, body').css({'overflow':'auto', 'height':'auto'});
                    $('#gnb').bind('touchmove');
                } else {
                    $("#sch_open").hide();  // 검색버튼 감추기
                    // 스크롤막기
                    $('html, body').css({'overflow':'hidden', 'height':'100%'});
                    $('#gnb').bind('touchmove', function(e){
                        e.preventDefault();
                    });
                }
                $("#gnb").toggle();
            });
            $("#gnb_close").on("click", function() {
                $("#sch_open").show();  // 검색버튼 보이기
                $('html, body').css({'overflow':'auto', 'height':'auto'});
                $('#gnb').bind('touchmove');
                $("#gnb").hide();
            });

            $("#sch_open").on("click", function() {
                $("#hd_sch").toggle();
            });
            $("#hd_sch .btn_close").on("click", function() {
                $("#hd_sch").hide();
            });

<?php
    $sql = "select count(*) as cnt from g5_scrap where mb_id = '{$member['mb_id']}' and bo_table = '{$bo_table}' and wr_id = '{$wr_id}'";
    $row = sql_fetch($sql);
    $scrap_yn = ($row['cnt'] > 0) ? true : false;

    if ($scrap_yn) {
?>
            $("#gnb_like").css('background-image', "url(../img/mobile/gnb_like_on.png)");
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
                                $("#gnb_like").css('background-image', "url(../img/mobile/gnb_like_on.png)");
                                //alert('찜목록에 추가되었습니다.');
                            } else if (resp.success=='remove'){
                                $("#gnb_like").css('background-image', "url(../img/mobile/gnb_like.png)");
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
            $(document).mouseup(function (e){
                var container = $("#hd_sch");
                if( container.has(e.target).length === 0)
                container.hide();
            });
        });
        </script>
        
    </div>
</header>



<div id="wrapper">

    <div id="container">
    <!--<?php if (!defined("_INDEX_")) { ?><h2 id="container_title" title="<?php echo get_text($g5['title']); ?>"><?php echo get_head_title($g5['title']); ?></h2><?php } ?>-->

<!--
<?php
echo "<img src=\"".G5_URL ."/theme/gallery/img/logo1.png\"  width=300>";
?>
-->