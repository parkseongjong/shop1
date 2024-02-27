$(function($) {

});
/*


    함수 목록


*/

function goDelivery(wr_id) {
    $.confirm({
        title: '안내',
        //content: '배송 시작으로 변경 할까요?',
        content: 'url:'+barry_member_skin_url+'/seller/memberOrderDelivery.ajax.php',
        onContentReady: function () {
            var target = $('#orderDeliveryCorp');
            var invoiceTitle =  $('#orderDeliveryInvoiceTitle');
            var invoice =  $('#orderDeliveryInvoice');
            var invoiceArea = $('#orderDeliveryInvoiceArea');
            var corpAutoComplete = $('#orderDeliveryCorpAutoComplete');
            target.off();
            target.on("propertychange change keyup",function() {
                if(invoiceArea.hasClass('d-none')){
                    invoiceArea.removeClass('d-none');
                }

                if($(this).val() == 9999){
                    invoiceTitle.html('연락 가능한 번호');
                    invoice.attr('placeholder','연락 가능한 번호 숫자만 입력');
                }
                else if($(this).val() == ''){
                    invoiceArea.addClass('d-none');
                }
                else{
                    invoiceTitle.html('운송장 번호');
                    invoice.attr('placeholder','운송장 번호 숫자만 입력');
                }
            });
            $.ajax({
                cache : false,
                url : g5_url+"/API/barry/order/invoice/list",
                type : 'GET',
                processData: true,
                contentType: 'application/json; charset=UTF-8',
                dataType : 'json',
                data : false,
                success : function(data, textStatus) {
                    if(data.code == 200) {
                        var corpList = [];
                        data.data.list.forEach(function (item, index,array) {
                            corpList.push({'code':item.corpCode,'name':item.corpName});
                            target.append('<option value="'+item.corpCode+'">'+item.corpName+'</option>')
                        });
                        corpAutoComplete.autocomplete({
                            autoFocus: true,
                            classes:{
                                'ui-autocomplete':'memberOrderDeliveryAutoComplete',
                            },
                            source: function (request, response) {
                                var regKeyword = $.ui.autocomplete.escapeRegex(request.term);
                                var matcher = new RegExp("" + regKeyword, "gi");
                                response($.grep(($.map(corpList, function (value, key) {
                                    return {
                                        label: value.name,
                                        value: value.code
                                    };
                                })),function (item) {
                                    return matcher.test(item.label);
                                }))
                            },
                            select: function(event, ui) {
                                corpAutoComplete.val(ui.item.label);
                                target.val(ui.item.value).attr('selected','selected');
                                target.trigger('change');
                            },
                            minLength: 1,
                            appendTo: '#orderDeliveryCorpAutoCompleteArea'
                        });
                    }
                    else{
                        bsCommonAlert('서버 연결에 실패하였습니다.','danger');
                    }

                }, // success
                error : function(xhr, status) {
                    bsCommonAlert(xhr.code+'알 수 없는 오류가 발생 하였습니다.');
                }
            });
        },
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //없음.
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {

                    //택배를 이용하는게 아니라면 , 택배 유효성 검사 부분 전역 변수는 비활성화.

                    var orderDeliveryCorpTarget = $('#orderDeliveryCorp');
                    var orderDeliveryCorpTargetValue = orderDeliveryCorpTarget.val();
                    if(!orderDeliveryCorpTargetValue && orderDeliveryCorpTargetValue.length <= 0){
                        bsCommonAlert('배송 방법을 선택해주세요.','warning');
                        return false;
                    }

                    var orderDeliveryInvoiceTarget = $('#orderDeliveryInvoice');
                    var orderDeliveryInvoiceTargetValue = orderDeliveryInvoiceTarget.val();

                    if(!orderDeliveryInvoiceTargetValue && orderDeliveryInvoiceTargetValue.length <= 0){
                        bsCommonAlert('번호를 입력해주세요.','warning');
                        return false;
                    }

                    $.ajax({
                        cache : false,
                        url : g5_url+"/API/barry/order/status/delivery",
                        type : 'PUT',
                        processData: true,
                        contentType: 'application/json; charset=UTF-8',
                        dataType : 'json',
                        data : JSON.stringify({'orderId':wr_id, 'orderDeliveryInvoice':orderDeliveryInvoiceTargetValue, 'orderDeliveryCorp':orderDeliveryCorpTargetValue}),
                        success : function(data, textStatus) {
                            if(data.code == 200) {
                                bsCommonAlert(data.orderMsg,'success');
                                if(data.orderCode != 104 && data.orderCode != 403 && data.orderCode != 406){
                                    setTimeout(document.location.href = "/bbs/memberOrderList.php?page="+barry_orderlist_page+"&type="+barry_orderlist_type, 3000);
                                }
                            }
                            else{
                                bsCommonAlert('서버 연결에 실패하였습니다.','danger');
                            }

                        }, // success
                        error : function(xhr, status) {
                            bsCommonAlert(xhr.code+'알 수 없는 오류가 발생 하였습니다.');
                        }
                    });
                }
            },
        }
    });
}

function goCancelDelivery(wr_id) {
    $.confirm({
        title: '안내',
        content: '주문 미확인 으로 변경 할까요?',
        //content: 'url:'+barry_member_skin_url+'/seller/memberOrderCancel.ajax.php',
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //없음.
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {
                    $.ajax({
                        cache : false,
                        url : g5_url+"/API/barry/order/status/order",
                        type : 'PUT',
                        processData: true,
                        contentType: 'application/json; charset=UTF-8',
                        dataType : 'json',
                        data : JSON.stringify({'orderId':wr_id}),
                        success : function(data, textStatus) {
                            if(data.code == 200) {
                                bsCommonAlert(data.orderMsg,'success');
                                setTimeout(document.location.href = "/bbs/memberOrderList.php?page="+barry_orderlist_page+"&type="+barry_orderlist_type, 3000);
                            }
                            else{
                                bsCommonAlert('서버 연결에 실패하였습니다.','danger');
                            }

                        }, // success
                        error : function(xhr, status) {
                            bsCommonAlert(xhr.code+'알 수 없는 오류가 발생 하였습니다.');
                        }
                    });
                }
            },
        }
    });
}