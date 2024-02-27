<?php
include_once('./_common.php');
$g5['title'] = '쿠폰';//제목도 변경을 해야한다면... 캐시를.. 이용 후 처리
include_once('./_head.php');
include_once(G5_LIB_PATH.'/barry.lib.php');
include_once(G5_PLUGIN_PATH.'/barryCoupon/config.php');

use \Webmozart\Assert\Assert;
//어차피 vue 단일 페이지라..... php 에서 타입 조정이 필요하지 않을 것 같음. 필요할지 모르니 일단 구현.
try{
    
    if($member['mb_id'] == ''){
        throw new Exception('로그인이 필요합니다.');
    }

    if(G5_IS_MOBILE){
        $coupon_skin_page = "/view.mobile.skin.php";

    }
    else{
        $coupon_skin_page = "/view.pc.skin.php";
    }

}
catch(Exception $e){
    $errMsg = $e->getMessage();
    $coupon_skin_page = "/error.mobile.skin.php";
} 
    //include_once ($coupon_skin_path.$coupon_skin_page);
	echo('
		<style>
			.temp-class{
				font-size: 17px;
				word-break: break-word;
				white-space: pre-wrap;
				font-family: "Noto Sans KR",sans-serif;
			}
		</style>
	    <div class="contents container-fluid">
			<div class="alert alert-info" role="alert">
				프리미엄, 판매자 쿠폰 기능을 준비중 입니다! 🤔
				<pre class="temp-class">
안녕하세요. 베리베리 쇼핑몰 입니다.

드디어 !  우리 베리몰 판매자 여러분들을 위하여 편의성 유료 이용권인 
프리미엄, 판매자 쿠폰 기능을 선보일 날이 바로 코 앞까지 찾아왔습니다. 🙌

그럼 먼저 쿠폰 기능이 무엇인지 궁금하실텐데요. 🤔

하나 하나 안내 해드리겠습니다! 🥳

<b>Q. 쿠폰 기능이 무엇인가요.?</b>
베리몰에 추가 될 편의성 이용권 입니다.

<b>Q. 쿠폰 종류에는 어떤 것들이 있나요.?</b>
베리몰 내에서 판매 자격을 가질 수 있는 판매자 자격 쿠폰과, 판매자를 위한 편의 기능인 프리미엄 쿠폰이 있습니다.

<b>Q. 판매자 쿠폰이란 무엇인가요.?</b>
기존 판매자 자격을 부여 하기 위해서는 교육을 받은 후 신청한 회원분들에 한하여 베리몰 담당자가 직접 확인 후 수동으로 자격을 부여하는 형식으로 진행 되어 왔습니다. 

이 때문에 실시간으로 확인이 이뤄지지 않아 불편함을 겪고 있었는데요. 

판매자 쿠폰은 구매 즉시 판매자 자격을 부여 받을 수 있으며, 이를 통한 수익은 판매자 여러분께 더 많은 편의 기능을 만드는데 사용 됩니다.

<b>Q. 프리미엄 쿠폰이란 무엇인가요?</b>
판매자 분들에게 편의성을 제공하는 쿠폰 입니다.
프리미엄 판매자분들은 판매중인 상품을 상품 목록 상위로 광고 노출 시킬 수 있으며,  광고 배너에 광고를 노출 시킬 수 있는 자격 역시 부여 됩니다.

<b>Q. 앞으로도 다른 종류의 쿠폰을 출시 할 계획이 있나요?</b>
네. 추가적으로 쿠폰을 출시 할 계획 입니다. 베리몰은 계속 성장 중 입니다. 다른 부족한 부분도 지속적으로 업데이트를 하고 있습니다.

<b>Q. 프리미엄 쿠폰의 상품 목록 상위 광고 노출은 어떤 식으로 결정 되나요?</b>
프리미엄 쿠폰 이용자들 중 특정 이용자 편파적인 노출을 방지하기 위해, 베리몰 시스템 내 알고리즘에 의하여 랜덤으로 노출 됩니다. 

<b>Q. 프리미엄 쿠폰을 이용중이에요, 그런데 내 상품이 상위 광고에 노출 되는지 모르겠어요.</b>
프리미엄 쿠폰 이용자 분들에게는 내 상품이 카테고리별로 프리미엄 광고 목록에 얼마나 노출과 클릭 되었는지에 대한 상황을 알 수 있게 보고서를 제공 합니다.

<b>Q. 구체적인 쿠폰 출시 일이 궁금합니다.</b>
아직 구체적인 출시일은 결정 되지 않았습니다.
더 안정적인 서비스를 위해 최종 결정 되면 안내 드리겠습니다.

<b>Q. 기존 판매자들은 어떻게 되나요?</b>
기존 판매자 여러분들은 무료 판매자 체험 기간이 종료되는 시점(판매자 쿠폰 출시 일)으로 판매자 자격을 상실하게 됩니다. 
판매자 쿠폰 출시 이후에는 쿠폰을 이용해야 판매자 자격을 가질 수 있습니다.

<b>Q. 판매자 자격을 가지고 있다가, 기간이 만료 되어 자격을 상실 하였습니다. 판매 기능은 모두 사용 할 수 없나요?</b>

<b>판매자 자격을 상실하여도 사용 가능한 기능은 아래와 같습니다.</b>
상품 판매 내역 확인
상품 배송 상태 변경

<b>판매자 자격을 상실 하였을때 사용 불가능한 기능은 아래와 같습니다.</b>
상품 등록/수정/삭제/품절 복구/품절 처리
이미 등록된 상품 판매

모든 내용은 확정된 내용이 아니며, 쿠폰 출시 전까지 추가 되거나 수정 될 수 있습니다.

고맙습니다.

베리베리 쇼핑몰 팀 드림.
				</pre>
			</div>
		</div>
	');
    unset($dbObject,$row,$id,$bo_table,$errMsg);

    echo PHP_EOL.'<!-- CouponSkin : '.$coupon['skin'].' -->'.PHP_EOL;

include_once('./_tail.php');
?>