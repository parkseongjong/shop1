/*

    writeModify.skin.php

*/

$(function($) {

    //it_id 0보다 큰 경우 수정으로, 등록된 선택 옵션이 있는지 확인 필요.
    if(barryWriteItemId > 0 && barryWriteSelectOptionStatus == true) {
        let option_table = $("#sit_option_frm");
        $.ajax({
            cache : false,
            url : g5_url+'/API/barry/goods/item/upload/option/select/'+g5_bo_table+'/'+barryWriteItemId,
            type : 'GET',
            processData: true,
            contentType : 'application/json; charset=UTF-8',
            dataType : 'json',
            //data : JSON.stringify({'optSubject':[],'optSubjectValue':[],'priceType':[]}),
            data : {},
            success : function(data, textStatus) {
                if(data.code == 200){
                    option_table.empty().html(data.html);
                }
                else{
                    bsCommonAlert(data.optionMsg,'warning');
                }
            },
            error : function(xhr, status) {
                console.log(xhr);
            }
        });
    }

    // 모두선택
    $(document).on("click", "input[name=opt_chk_all]", function() {
        if($(this).is(":checked")) {
            $("input[name='opt_chk[]']").attr("checked", true);
        } else {
            $("input[name='opt_chk[]']").attr("checked", false);
        }
    });

    // 일괄적용
    $(document).on("click", "#opt_value_apply", function() {
        if($(".opt_com_chk:checked").size() < 1) {
            bsCommonAlert('일괄 수정할 항목을 하나이상 체크해 주십시오.');
            return false;
        }

        var opt_stock = $.trim($("#opt_com_stock").val());
        var opt_noti = $.trim($("#opt_com_noti").val());
        var opt_use = $("#opt_com_use").val();
        var $el = $("input[name='opt_chk[]']:checked");
        var $elAll = $("input[name='opt_chk[]']");

        // 체크된 옵션이 있으면 체크된 것만 적용, 체크된게 없다면 전체 적용.
        //현금 coin 분리,
        if($el.size() > 0) {
            $el.each(function() {
                var meNumber = $(this).parents('ul').data('me-number');

                if(barryWritePriceType =="KRW"){
                    if($("#opt_com_stock_chk").is(":checked")) {
                        $("#opt_stock_qty_"+meNumber).val(opt_stock);
                    }
                    if($("#opt_com_use_chk").is(":checked")){
                        $("#opt_use_"+meNumber).val(opt_use);
                    }
                }
                else{
                    if($("#opt_com_stock_chk").is(":checked")) {
                        $("#opt_stock_qty_"+meNumber).val(opt_stock);
                    }
                    if($("#opt_com_use_chk").is(":checked")){
                        $("#opt_use_"+meNumber).val(opt_use);
                    }
                }

            });
        }
        else {
            $elAll.each(function() {
                var meNumber = $(this).parents('ul').data('me-number');

                if(barryWritePriceType =="KRW"){
                    if($("#opt_com_stock_chk").is(":checked")) {
                        $("#opt_stock_qty_"+meNumber).val(opt_stock);
                    }
                    if($("#opt_com_use_chk").is(":checked")){
                        $("#opt_use_"+meNumber).val(opt_use);
                    }
                }
                else{
                    if($("#opt_com_stock_chk").is(":checked")) {
                        $("#opt_stock_qty_"+meNumber).val(opt_stock);
                    }
                    if($("#opt_com_use_chk").is(":checked")){
                        $("#opt_use_"+meNumber).val(opt_use);
                    }
                }
            });
        }
    });


});
/*


    함수 목록


*/

//수정 페이지도 formData화,
$(document).on("submit",'#itemUpload',async function() {
    event.preventDefault();

    //GB write 변수 재선언
    let f = this;

    let itemUploadFormData = new FormData($(this)[0]);
    let submitBtnTarget = $('#btn_submit');
    btnDisabledStatus(submitBtnTarget,true);

    //select target name : json(post) data name
    let targetObj = [
            {'target':'optId[]','postName':'optId'},
            {'target':'optStockQty[]','postName':'optStockQty'},
            {'target':'optUse[]','postName':'optUse'},
    ];
    if(barryWritePriceType == 'KRW'){
        targetObj.push({'target':'optPrice[]','postName':'optPrice'});
    }
    else{
        targetObj.push(
            {'target':'optPriceEtp3[]','postName':'optPriceEtp3'},
            {'target':'optPriceEmc[]','postName':'optPriceEmc'}
        );
    }

    if (typeof(f.tableId) == "undefined") {
        return;
    }

    var table = f.tableId.value;
    var token = get_write_token(table);

    if(!token) {
        bsCommonAlert('토큰 정보가 올바르지 않습니다.', 'danger');
        return false;
    }

    itemUploadFormData.append('token',token);
    itemUploadFormData.append('itemId',barryWriteItemId);

    //post Data build
    let data = await jsonTypeFormDataAndFormDataBuild(itemUploadFormData,targetObj);

    $.ajax({
        cache: false,
        url: g5_url + '/API/barry/goods/item/upload/modifications',
        type: 'POST',
        processData: false,
        contentType: 'application/json; charset=UTF-8',
        dataType: 'json',
        data: data,
        success: function (data, textStatus) {
            console.log(data);
            if (data.code == 200) {
                bsCommonAlert(data.uploadMsg, 'success');
            }
            else {
                bsCommonAlert(data.uploadMsg, 'danger');
            }
            btnDisabledStatus(submitBtnTarget,false);
        },
        error: function (xhr, status) {
            //console.log(xhr);
            bsCommonAlert('오류!', 'danger');
            btnDisabledStatus(submitBtnTarget,false);
        }
    });

});
