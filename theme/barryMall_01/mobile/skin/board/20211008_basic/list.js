/*

    list.skin.php

*/
$(function($) {
    var w = $(document).width();
    var h = $(window).height();
    $('#add_seller_back').css({'width':w,'height':h});

    $('body').append($('#add_seller_back'));
    $('body').append($('#add_seller'));

    var add_h = $('#add_seller').height();

    $('.add_content').height(add_h-37); // 흰색 박스 영역
    $('.add_content_top').height(add_h-37-115); // 상단 본문 영역 (나머지는 버튼 영역)

    $('.add_navi').height(20);
    $('.add_navi').css('margin-top',17);

    $('#add_seller_back').click(function(){
        add_seller_hide();
    });

	
	// 검색 조건 추가, 201015, YMJ
    // 검색 조건 수정 20210318 PJH
	$("#price_type").on('change', function() {
		var price_type1 = $("#price_type option:selected").val();
		var bo_table = $("#fboardlist_bo_table").val();
        if(barry_write_sfl != 'false' && barry_write_stx != 'false'){
            location.href="/bbs/board.php?bo_table="+bo_table+"&price_type="+price_type1+'&stx='+barry_write_stx+'&sfl='+barry_write_sfl;
        }
        else{
            location.href="/bbs/board.php?bo_table="+bo_table+"&price_type="+price_type1;
        }
	});

});
/*


    함수 목록


*/
function goSellerIntro() {
    $('#add_seller_1').show();
    $('#add_seller_2').hide();
    $('#add_seller_3').hide();
}
function goSellerInfo() {
    $('#add_seller_1').hide();
    $('#add_seller_2').show();
    $('#add_seller_3').hide();
}
function goSellerFinish() {
    $('#add_seller_1').hide();
    $('#add_seller_2').hide();
    $('#add_seller_3').show();
}

function goSellerUpdate() {
    $.ajax({
        url : '/bbs/add_seller.php',
        type : 'POST',
        data : {},
        dataType : 'json',
        success : function(resp){
            if (resp.err) {
                alert(''+resp.err);
            } else if (resp.success) {
                alert('신청되었습니다.');
                add_seller_hide();
            } else {
                alert('알수없는 오류');
                add_seller_hide();
            }
        },
        error : function(resp){
            alert('잠시후 다시 이용하시기 바랍니다.');
            add_seller_hide();
        }
    });
}

function add_seller() {
    goSellerIntro();
    $('#add_seller_back').show();
    $('#add_seller').show();
}
function add_seller_hide() {
    $('#add_seller_back').hide();
    $('#add_seller').hide();
}