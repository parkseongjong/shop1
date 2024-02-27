/*

    view.js

*/

//orderID value
let orderId = false;

$(function($){
    //item(goods)가 삭제 상태 일 때
    if(barryView.deleteStatus == 'Y'){
        bsCommonAlert('판매자가 판매를 일시 중지 하였습니다.','danger');
    }
    //item(goods)가 품절 상태 일 때
    if(barryView.soldout == 1){
        bsCommonAlert('품절 된 상품 입니다.','danger');
    }

    //item(goods)가 미승인 상태 일때, 반려 상태 일때...
    if(barryView.publish == 0 || barryView.publish == 90) {
        bsCommonAlert('미승인 된 상품 입니다.','danger');
    }

    //barryView.priceType
    var viewDetailSlider = new Swiper('#viewDetailSlide', {
        navigation: {
            nextEl: '#viewDetailSlide .swiper-button-next',
            prevEl: '#viewDetailSlide .swiper-button-prev'
        },
        pagination: {
            el: '#viewDetailSlide .swiper-pagination',
            type: 'bullets',
            clickable: true
        },
        autoplay: false
    });

    //주문자와 동일 클릭
    $('#samename').on("click", function() {
        var b = $('input[name="samename"]').is(":checked");
        if (b) {
            var order_name = $.trim($('#name').val());
            var order_phone = $.trim($('#phone').val());
            $('#recvName').val(order_name);
            $('#recvPhone').val(order_phone);
        }
        //alert(b);
    });

    //비회원 주문하기 버튼 이벤트
    if(barryView.memberAuth == 1) {
        $('#orderLayer').on('show.bs.modal', function (e) {
            bsCommonAlert('비회원은 주문 할 수 없습니다.');
            return false;
        })
    }
    //item(goods)가 삭제 상태일때
    if(barryView.deleteStatus == 'Y'){
        $('#orderLayer').on('show.bs.modal', function (e) {
            bsCommonAlert('판매자가 판매를 일시 중지 하였습니다.','danger');
            return false;
        })
    }
    if(barryView.soldout == 1){
        $('#orderLayer').on('show.bs.modal', function (e) {
            bsCommonAlert('품절 된 상품 입니다.','danger');
            return false;
        })
    }
    if(barryView.publish == 0 || barryView.publish == 90){
        $('#orderLayer').on('show.bs.modal', function (e) {
            bsCommonAlert('미승인 된 상품 입니다.','danger');
            return false;
        })
    }
    //barryView.priceType

    paymemntRequrie = false;
    //결제 화면, 현금이 아닐 때 modal show
    if(barryView.priceType != 'KRW'){
        paymemntRequrie = true;
    }

    let qty = $('input[name="qty"]');

    //코인 타입 bulid
    $(document).on("click", "ul.coinType > li", function(){
        $('ul.coinType > li').removeClass('active');
        barryView.cartPrice  = $(this).data('me-value');
        barryView.cartPriceType = $(this).data('me-type-value');
        $(this).addClass('active');
        qty.trigger('change');
    });

    //선택옵션 데이터 처리
    $(document).on("change", "select.it_option", function() {
        if(barryView.cartPriceType == false){
            bsCommonAlert('결제 할 종류를 선택해주세요.');
            $(this).find('option').eq(0).prop('selected',true);
            return false;
        }
        var itemOption = {};
        var optionCount = $("select.it_option").size();
        var optionValue = '';

        for(i=1; i<=3; i++){
            var target = $('#it_option_'+i);
            var targetSelectd = target.find('option:selected');
            //단일 선택 옵션 OR 다중 선택 옵션에서 마지막 선택 옵션인 경우 value 값만 가져온다.
            //다중
            if(optionCount <= i){
                if(target.size() && target.prop('disabled') != true){
                    optionValue = targetSelectd.val();
                    itemOption[i] = '{"option-title":"'+targetSelectd.data('option-title')+'","option-value":"'+targetSelectd.data('option-value')+'","io-type":"'+targetSelectd.data('io-type')+'"}';
                }
            }
            //단일
            else{
                if(target.size() && target.prop('disabled') != true){
                    itemOption[i] = '{"option-title":"'+targetSelectd.data('option-title')+'","option-value":"'+targetSelectd.data('option-value')+'","io-type":"'+targetSelectd.data('io-type')+'"}';
                }
            }

        }

        var cartOption = '',
            optId = '',
            optType = '',
            cartOptionPrice = 0,
            objKeyCount = Object.keys(itemOption).length,
            splitString = ' / ';

        for(i=1; i<= objKeyCount; i++){
            var itemOptionJsonValue =  JSON.parse(itemOption[i]);
            //console.log(itemOptionJsonValue);
            cartOption  += itemOptionJsonValue['option-title']+':'+itemOptionJsonValue['option-value'];
            if(i==objKeyCount){
                optId  += itemOptionJsonValue['option-value'];
            }
            else{
                optId  += itemOptionJsonValue['option-value']+String.fromCharCode(30);
            }
            optType = itemOptionJsonValue['io-type'];// 옵션에 type은 모두 동일 하므로, 하나만 넣습니다.
            if(i < objKeyCount){
                cartOption += splitString;
            }
        }
        cartOptionPriceArray = optionValue.split(',');
        //현금결제
        if(barryView.cartPriceType == 'KRW'){
            //최종 선택된 옵션 value는 선택옵션 value,현금가,e-TP3가,e-MC가 입니다.
            cartOptionPrice = cartOptionPriceArray[1];
        }//ee-TP3, ee-MC 결제
        else{
            //최종 선택된 옵션 value는 선택옵션 value,현금가,e-TP3가,e-MC가 입니다.
            if(barryView.cartPriceType == 'e-TP3'){
                cartOptionPrice = cartOptionPriceArray[2];
            }
            else if(barryView.cartPriceType == 'e-MC'){
                cartOptionPrice = cartOptionPriceArray[3];
            }
        }
        //가공 된 데이터 최종적으로 obj에 삽입
        barryView.cartOption = cartOption;
        barryView.cartOptionPrice = cartOptionPrice;
        barryView.optId = optId;
        barryView.optType = optType;
        //console.log(barryView);
    });

    //수량 입력 시 금액 노출 하기
    let previewItemTotalPrice = $('#previewItemTotalPrice');
    let previewItemPrice = $('#previewItemPrice');
    let previewItemSelectOptionPrice = $('#previewItemSelectOptionPrice');
    let previewItemQty = $('#previewItemQty');
    $(document).on('propertychange change keyup',qty,function() {
        //console.log(barryView);
        if(Number(qty.val()) > 9999){
            bsCommonAlert('너무 큰 수를 입력 할 수 없습니다..','danger');
            qty.val(0);
            return false;
        }
        barryView.qty = Number(qty.val());
        let tempTotalPrice = (Number(barryView.cartPrice) + Number(barryView.cartOptionPrice)) * barryView.qty;
        let tempPrice = Number(barryView.cartPrice);
        let tempPriceType = (barryView.cartPriceType == false)?'.':barryView.cartPriceType;

        //CARD 결제 일 때는 노출 단위 원으로 노출.
        if(tempPriceType == 'CREDITCARD'){
            tempPriceType = '원';
        }
        if(checkNaN(tempTotalPrice)){
            tempTotalPrice = 0;
        }
        if(checkNaN(tempPrice)){
            tempPrice = 0;
        }
        previewItemTotalPrice.html(commaAdd(tempTotalPrice)+tempPriceType);
        previewItemPrice.html(commaAdd(tempPrice)+tempPriceType);
        //console.log(checkUndefined(barryView.cartOptionPrice));
        previewItemSelectOptionPrice.html((barryView.cartOptionPrice == false || checkUndefined(barryView.cartOptionPrice) == true)?'없음':commaAdd(barryView.cartOptionPrice)+tempPriceType);
        previewItemQty.html(barryView.qty);
        barryView.previewItemTotalPrice = tempTotalPrice;
    });

    //submit 막기
    $(document).on("submit",'#orderUpload',async function(){
        event.preventDefault();

        let modalEl = $('#orderLayer');

        //비회원은 주문 할 수 없다.
        if(barryView.memberAuth <= 1){
            bsCommonAlert('비회원은 주문 할 수 없습니다.');
            modalEl.modal('hide');
            return false;
        }

        //GB input 변수 재선언
        let f = this;
        //파일 체크 변수
        let status = true;
        let orderUploadFormData = new FormData($(this)[0]);
        let submitBtnTarget = $('#orderUpload #btnSubmit');
        btnDisabledStatus(submitBtnTarget,true);

        if (typeof(f.tableId) == "undefined") {
            return;
        }

        var table = f.tableId.value;
        var token = get_write_token(table);

        if(!token) {
            bsCommonAlert('토큰 정보가 올바르지 않습니다.', 'danger');
            return false;
        }

        orderUploadFormData.append('token',token);
        //배송지 주소 build
        barryView.address = f.zip.value+' '+f.addr1.value+' '+f.addr2.value+' '+f.addr3.value+' '+f.jibun.value;
        for(let key in barryView){
            orderUploadFormData.append(key,barryView[key]);
        }

        let sendPostData = await jsonTypeFormDataBuild(orderUploadFormData);
        //console.log('checkFrontValid Before:'+sendPostData);

        //선택옵션 유효성 체크 해야함. (선택옵션이 있으면 유효성 체크를 하는 쪽으로,.)
        if (await checkFrontValid(f,barryView) == false ) {
            btnDisabledStatus(submitBtnTarget,false);
            return false;
        }

        //payment가 필요하고, price type이 KRW가 아닐 때 다시 한번 물어본다.
        if(paymemntRequrie == true && barryView.priceType != 'KRW'){

            let type = false;
            barryView.qty = Number(qty.val());
            let amount = (Number(barryView.cartPrice) + Number(barryView.cartOptionPrice)) * barryView.qty;

            if(barryView.cartPriceType == 'CREDITCARD'){
                barryView.cartPriceType = '원';
            }

            $.confirm({
                title: '구매확인',
                content: barryView.sellerName+'('+barryView.sellerId+')님에게 '+amount+' '+barryView.cartPriceType+'를 보내시겠습니까?',
                buttons: {
                    cancel:{
                        text: '취소',
                        btnClass: 'btn btn-dark',
                        action : function () {
                            btnDisabledStatus(submitBtnTarget,false);
                        }
                    },
                    confirm:{
                        text: '확인',
                        btnClass: 'btn btn-success',
                        action : function () {
                            orderAjax(modalEl, submitBtnTarget, sendPostData);
                        }
                    },
                }
            });
        }
        else{
            orderAjax(modalEl, submitBtnTarget, sendPostData);
        }

    });
});
///////////////ajax로 변환
$(document).on("submit",'#paymentCardform',async function(){
    event.preventDefault();
    let modalEl = $('#paymentCardLayer');

    let orderUploadFormData = new FormData($(this)[0]);
    let submitBtnTarget = $('#paymentCardform #btnSubmit');
    btnDisabledStatus(submitBtnTarget,true);

    let paymentCardformData = await jsonTypeFormDataBuild(orderUploadFormData);

    $.ajax({
        cache: false,
        url: g5_url+"/API/barry/order/item/payment/credit-card",
        type:'POST',
        processData:false,
        contentType:'application/json; charset=UTF-8',
        dataType:'json',
        data: paymentCardformData,
        success: function(data, textStatus){
            if(data.code == 200){
                bsCommonAlert('결제를 성공 하였습니다.','success');
                //타임3초
                setTimeout(function() {
                    window.location.href= g5_url+'/plugin/barryIntegration/?wr_id='+orderId+'&target_bo_table='+g5_bo_table;
                },3000);
            }
            else{
                bsCommonAlert(data.paymentMsg);
                setTimeout(function() {
                    btnDisabledStatus(submitBtnTarget,false);
                },3000);
            }

        },
        error:function (request, status, error){
            bsCommonAlert('결제가 실패 되었습니다.', 'danger');
            setTimeout(function() {
                btnDisabledStatus(submitBtnTarget,false);
            },3000);
        }
    });
    //console.log(paymentCardformData);
});

/*


    함수 목록


*/

function orderAjax(modalEl, submitBtnTarget, sendPostData){
    //console.log('orderAajx:'+sendPostData);
    $.ajax({
        cache : false,
        //url : "order.php",
        url : g5_url+"/API/barry/order/item/user/upload",
        type : 'POST',
        processData: false,
        contentType: 'application/json; charset=UTF-8',
        dataType : 'json',
        data : sendPostData,
        success : function(data, textStatus) {
            //console.log(barryView);
            if(data.code == 200){
                //btnDisabledStatus(submitBtnTarget,false); btn disable을 풀지 않는다.
                modalEl.modal('hide');
                orderId = data.orderId;
                $('input[name=orderId]').val(orderId);
                $('#orderLayer').modal('hide');
                //payment가 필요하고, price type이 KRW가 아닐 때 결제 비밀번호를 띄워준다.
                if(paymemntRequrie == true && barryView.priceType == 'TP3MC' || barryView.priceType == 'TP3' || barryView.priceType == 'MC'){
                    $('#paymentLayer').modal('show');//일반 코인 결제
                    //카드결제 일 때 검증용 어마운트 값 삽입..!
                    $('input[name=amount]').val(barryView.previewItemTotalPrice);

                }
                else if(paymemntRequrie == true && barryView.priceType == 'CREDITCARD'){
                    $('#paymentCardLayer').modal('show');//paymentLayer 를 없애고 카드 모달 오픈
                    //카드결제 일 때 검증용 어마운트 값 삽입..!
                    $('input[name=amount]').val(barryView.previewItemTotalPrice);
                }
                else{
                    //주문 완료 페이지로 이동.
                    //window.location.href= g5_url+'/plugin/barryIntegration/?wr_id='+data.wr_id+'&target_bo_table='+g5_bo_table;
                    window.location.href= g5_url+'/plugin/barryIntegration/?wr_id='+data.orderId+'&target_bo_table='+g5_bo_table;
                }
            }
            else if(data.orderMsg){
                //console.log(data.orderMsg);
                btnDisabledStatus(submitBtnTarget,false);
                bsCommonAlert(data.orderMsg,'danger');
            }
            else{
                //console.log(data.orderMsg);
                btnDisabledStatus(submitBtnTarget,false);
                bsCommonAlert('서버 연결에 실패하였습니다.','danger');
            }

        }, // success
        error : function(xhr, status) {
            btnDisabledStatus(submitBtnTarget,false);
            bsCommonAlert('서버 연결에 실패하였습니다.','danger');
        }
    });
}

function checkFrontValid(theForm,barryView){
    //console.log(barryView);
    if(!barryView.cartPriceType){
        bsCommonAlert('결제 할 종류를 선택해주세요.');
        return false;
    }
    else if(checkSpace(theForm.name.value)){
        bsCommonAlert('주문자 성함이 비어있습니다. 확인해주세요.');
        theForm.name.focus();
        return false;
    }
    else if(checkSpace(theForm.phone.value)){
        bsCommonAlert('주문자 연락처가 비어있습니다. 확인해주세요.');
        theForm.phone.focus();
        return false;
    }
    else if(checkSpace(theForm.qty.value) || Number.isInteger(Number(theForm.qty.value)) === false || theForm.qty.value <= 0){
        //alert("구매 수량이 비어 있습니다. 확인해주세요.")
        bsCommonAlert('주문 수량이 비어 있거나 숫자(정수)가 아닙니다. 확인해주세요.');
        theForm.qty.focus();
        return false;
    }
    else if(checkSpace(theForm.recvName.value)){
        bsCommonAlert('수령인 성함을 입력해주세요.');
        theForm.recvName.focus();
        return false;
    }
    else if(checkSpace(theForm.recvPhone.value)){
        bsCommonAlert('수령인 연락처를 입력해주세요.');
        theForm.recvPhone.focus();
        return false;
    }
    else if(checkSpace(theForm.zip.value)){
        bsCommonAlert('배송지 주소가 비어 있습니다. 주소 검색을 해주세요.');
        theForm.zip.focus();
        return false;
    }
    else if(checkSpace(theForm.addr1.value)){
        bsCommonAlert('배송지 주소가 비어 있습니다. 주소 검색을 해주세요.');
        theForm.addr1.focus();
        return false;
    }
    else if(checkSpace(theForm.addr2.value)){
        bsCommonAlert('배송지 상세 주소를 입력해주세요.');
        theForm.addr2.focus();
        return false;
    }
    else if(checkSpace(barryView.address)){
        bsCommonAlert('배송지 주소가 비어 있습니다. 확인해주세요.');
        return false;
    }

    //선택옵션은 선택 옵션이 존재 할 때만 유효성 체크를 한다.
    if(barryView.optionSubject != false){
        if(checkUndefined(barryView.cartOption) || checkUndefined(barryView.optId)){
            bsCommonAlert('선택 옵션을 선택해주세요.');
            return false;
        }
    }
    
    return true;
}
