/*

    write.skin.php

*/

$(function($) {

    // 콜백
    // coinApiInfo(function(result){
    //     console.log(result);
    //     coinRate = result[0];
    //     coinPerValue = result[1];
    // });
    var limitrActivationDate = $('#itemLimitActivativationDatetime');
    var limitrDeactivationDate = $('#itemLimitDeactivativationDatetime');

    limitrActivationDate.datetimepicker({
        showTimepicker: true,
        controlType: 'select',
        minDate: '0d',
        maxDate: '+30d',
        dateFormat: "yy-mm-dd",
        timeFormat: 'HH:mm:ss',
        hour: 0,
        minute: 0,
        second: 0,
        defaultDate: 1
    });

    limitrDeactivationDate.datetimepicker({
        showTimepicker: true,
        controlType: 'select',
        minDate: '1d',
        maxDate: '+1y',
        dateFormat: "yy-mm-dd",
        timeFormat: 'HH:mm:ss',
        hour: 23,
        minute: 59,
        second: 59,
        defaultDate: 7
    });
    // limitrActivationDate.datepicker("setDate", "today");
    // limitrDeactivationDate.datepicker("setDate", "+7");

    //promise를 이용 할 수밖에....?
    //비율 값 셋팅
    coinApiInfo2()
        .then(function(result) {

            //원화 -> 코인 환율 환산
            var coinRate = Math.floor(result[0]);
            var coinPerValue = result[1];
            var krwCost = false;
            var eTP3El = $('#priceEtp3');
            var eMCEl = $('#priceEmc');
            var krwCosingWaitEl = $('#krwCosingWait');
            var retailPriceEl = $('#retailPrice');

            $('#coinRate').html(coinRate);
            $('#coinPerValue').html(coinPerValue);

            //원화 -> e-TP3 적용 환율 환산
            $(document).on("change keyup paste input", "#krwCosting", function (){
                //console.log('ok');
                krwCosingWaitEl.addClass('on');
                clearTimeout(krwCost);
                var krwValue = $(this).val();
                //console.log(krwValue);
                if(krwValue <= 0){
                    krwCosingWaitEl.removeClass('on');
                    return false;
                }
                krwCost = setTimeout(function() {
                    if (krwValue <= 100) {
                        bsCommonAlert('현금 환산을 위해 100보다 큰 수를 입력 해주세요.');
                        krwCosingWaitEl.removeClass('on');
                        return false;
                    }
                    //console.log(krwValue / coinRate);
                    var castingValue = Math.floor(krwValue / coinRate);
                    eTP3El.val(castingValue);
                    //임시로 e-MC도 동일하게 처리. 소비자가는 krwValue를 준다.
                    eMCEl.val(castingValue);
                    retailPriceEl.val(krwValue);
                    krwCosingWaitEl.removeClass('on');
                },1000);
            });

            //coin e-TP3 일괄 적용 , emc임시 적용
            $(document).on("change keyup paste input", "#opt_com_krwCosting", function (){
                clearTimeout(krwCost);
                var krwValue = $(this).val();
                if(krwValue <= 0){
                    return false;
                }
                krwCost = setTimeout(function() {
                    if (krwValue <= 100) {
                        bsCommonAlert('현금 환산을 위해 100보다 큰 수를 입력 해주세요.');
                        return false;
                    }
                    var castingValue = Math.floor(krwValue / coinRate);
                    $('#opt_com_price_etp3').val(castingValue);
                    //emc 임시..
                    $('#opt_com_price_emc').val(castingValue);
                },1000);
            });

            //coin e-TP3 개별 적용 , emc임시 적용
            $(document).on("change keyup paste input", ".opt_price_krwCosting", function (){
                clearTimeout(krwCost);
                let krwValue = $(this).val();
                let number = $(this).parents('ul').data('me-number');
                if(krwValue <= 0){
                    return false;
                }
                krwCost = setTimeout(function() {
                    if (krwValue <= 100) {
                        bsCommonAlert('현금 환산을 위해 100보다 큰 수를 입력 해주세요.');
                        return false;
                    }
                    var castingValue = Math.floor(krwValue / coinRate);
                    $('#opt_price_etp3_'+number).val(castingValue);
                    $('#opt_price_emc_'+number).val(castingValue);
                },1000);
            });


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

            // 옵션목록생성
            $(document).on("click", "#option_table_create", async function() {
                let optSubject = $('input[name="optSubject[]"]');
                let optSubjectValue = $('input[name="optSubjectValue[]"]');
                let option_table = $("#sit_option_frm");

                let optSubjectArray = false;
                let optSubjectValueArray = false;

                await asyncForEach(optSubject,function(obj, index){
                    if(index == 0){
                        optSubjectArray = new Array();
                    }
                    if($(obj).val()){
                        optSubjectArray.push($(obj).val());
                    }

                });
                await asyncForEach(optSubjectValue,function(obj, index){
                    if(index == 0){
                        optSubjectValueArray = new Array();
                    }
                    if($(obj).val()) {
                        optSubjectValueArray.push($(obj).val());
                    }
                });
                console.log(optSubjectArray);
                console.log(optSubjectValueArray);
                //JSON.stringify(testOjb)
                $.ajax({
                    cache : false,
                    url : g5_url+'/API/barry/goods/item/upload/option/select',
                    type : 'POST',
                    processData: true,
                    contentType : 'application/json; charset=UTF-8',
                    dataType : 'json',
                    data : JSON.stringify({'optSubject':optSubjectArray,'optSubjectValue':optSubjectValueArray,'priceType':barryWritePriceType}),
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
            });
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
                if(barryWritePriceType =="KRW"){
                    var opt_price = $.trim($("#opt_com_price").val());
                }
                else{
                    var opt_price_etp3 = $.trim($("#opt_com_price_etp3").val());
                    var opt_price_emc = $.trim($("#opt_com_price_emc").val());
                    var opt_krwCosting = $.trim($("#opt_com_krwCosting").val());
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
                            if($("#opt_com_price_chk").is(":checked")){
                                $("#opt_price_"+meNumber).val(opt_price);
                            }
                            if($("#opt_com_stock_chk").is(":checked")) {
                                $("#opt_stock_qty_"+meNumber).val(opt_stock);
                            }
                            if($("#opt_com_use_chk").is(":checked")){
                                $("#opt_use_"+meNumber).val(opt_use);
                            }
                        }
                        else{
                            if($("#opt_com_price_etp3_chk").is(":checked")){
                                $("#opt_price_etp3_"+meNumber).val(opt_price_etp3);
                                $("#opt_price_krwCosting_"+meNumber).val(opt_krwCosting);
                                //임시 처리
                                $("#opt_price_emc_"+meNumber).val(opt_price_emc);
                            }
                            if($("#opt_com_price_emc_chk").is(":checked")){
                                //임시로 e-MC 적용
                                //$("#opt_price_emc_"+meNumber).val(opt_price_emc);
                                $("#opt_price_etp3_"+meNumber).val(opt_price_etp3);
                                $("#opt_price_emc_"+meNumber).val(opt_price_emc);
                                $("#opt_price_krwCosting_"+meNumber).val(opt_krwCosting);
                            }
                            if($("#opt_com_stock_chk").is(":checked")) {
                                $("#opt_stock_qty_"+meNumber).val(opt_stock);
                            }

                            // if($("#opt_com_noti_chk").is(":checked"))
                            //     $tr.find("input[name='opt_noti_qty[]']").val(opt_noti);

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
                            if($("#opt_com_price_chk").is(":checked")){
                                $("#opt_price_"+meNumber).val(opt_price);
                            }
                            if($("#opt_com_stock_chk").is(":checked")) {
                                $("#opt_stock_qty_"+meNumber).val(opt_stock);
                            }
                            if($("#opt_com_use_chk").is(":checked")){
                                $("#opt_use_"+meNumber).val(opt_use);
                            }
                        }
                        else{
                            if($("#opt_com_price_etp3_chk").is(":checked")){
                                $("#opt_price_etp3_"+meNumber).val(opt_price_etp3);
                                $("#opt_price_krwCosting_"+meNumber).val(opt_krwCosting);
                                //임시 처리
                                $("#opt_price_emc_"+meNumber).val(opt_price_emc);
                            }
                            if($("#opt_com_price_emc_chk").is(":checked")){
                                //임시로 e-MC 적용
                                //$("#opt_price_emc_"+meNumber).val(opt_price_emc);
                                $("#opt_price_etp3_"+meNumber).val(opt_price_etp3);
                                $("#opt_price_emc_"+meNumber).val(opt_price_emc);
                                $("#opt_price_krwCosting_"+meNumber).val(opt_krwCosting);
                            }
                            if($("#opt_com_stock_chk").is(":checked")) {
                                $("#opt_stock_qty_"+meNumber).val(opt_stock);
                            }

                            // if($("#opt_com_noti_chk").is(":checked"))
                            //     $tr.find("input[name='opt_noti_qty[]']").val(opt_noti);

                            if($("#opt_com_use_chk").is(":checked")){
                                $("#opt_use_"+meNumber).val(opt_use);
                            }
                        }
                    });

                }
            });

        })
        .catch(function(result) {
            //reject는 따로 하지 않음.
        });

    // 지연방식.
    // coinApiInfo3()
    //     .done(function(t) {
    //         console.log(t);
    //     });

    //상품 사진 영역 START

    //업로드 가능한 사진 개수
    let maxFiles = 20;
    //업로드 가능한 최대 제한 용량 (64MB), 67108864 bytes
    let maxSize = 67108864;
    //업로드 단일 사진 제한 용량 (15MB), 15728640 bytes
    let perMaxSize = 15728640;

    let imageListTarget = $('#imageList');
    let imageFixSource = new Array();
    let imageCropAreaTarget = $('#imageCropArea');

    imageListTarget.sortable({
        opacity: 1,
        containment: 'parent',
        items: '> li',
        handle: '.itemImage',
        tolerance: 'pointer',
        change: function( event, ui ) {
        },
        update: function( event, ui ) {
            processImageList(imageFixSource);
        },
        receive : function( event, ui ) {
        }
    });
    imageListTarget.disableSelection();

    $(document).on("change",'#imageSearch',async function(){
        //앨리먼트 list, array 초기화
        imageFixSource = [];
        imageListTarget.empty();
        //사진 유효성 체크 true: 통과, false: 실패
        let fileStatus = true;
        //console.log($(this)[0].files);
        //files에 있는 내용 옮겨 담기, 보안정책상 그대로 사용불가
        await Array.prototype.forEach.call($(this)[0].files, function(file) {
            //fildId : obj 고유 id, fileData : file data, fileCropElement: crop area에 들어갈 앨리먼트, fileCropObj: crop obj, fileElementCropped: crop 여부
            tempOjb = {
                'fileId': false,
                'fileData':file,
                'fileCropElement':false,
                'fileCropObj':false,
                'fileCropFileData':false,
                'fileElementCropped':false,
            };
            imageFixSource.push(tempOjb);
        });
        //console.log(imageFixSource);

        let totalSize = 0;
        //사진 파일 유효성 체크
        if(imageFixSource.length > maxFiles){
            fileStatus = false;
            bsCommonAlert('올릴 수 있는 상품 사진 개수를 초과 하였습니다.');
            return false;
        }

        await asyncForEach(imageFixSource,function(obj, index){
            totalSize += obj.fileData.size;

            if(!obj.fileData.type.match("image.*")){
                fileStatus = false;
                bsCommonAlert('상품 사진이 유효한 확장자가 아닙니다.');
                return false;
            }

            if(obj.fileData.size > perMaxSize){
                fileStatus = false;
                bsCommonAlert('상품 사진 단일 용량을 초과 하였습니다.');
                return false;
            }

        });

        if(totalSize > maxSize){
            fileStatus = false;
            bsCommonAlert('상품 사진 전체 용량을 초과 하였습니다.');
            return false;
        }

        if(fileStatus == false){
            return false;
        }

        //html에 업로드 상품 사진 개 수 업데이트
        fileCountDraw(imageFixSource.length);

        //리팩토링 필요, array or object에 원하는 원소가 있는지 체크하는 function 필요!
        let imageFixSourceCount = imageFixSource.length-1;
        //console.log(imageFixSourceCount);
        let tempHtml = '';

        await asyncForEach(imageFixSource,async function(obj, index){
            obj.fileId = 'uploadId-'+index;
            tempHtml += imageListDraw(index,obj.fileId,await getDataImage(obj.fileData));
            obj.fileCropElement = cropElementDraw(obj.fileId,await getDataImage(obj.fileData));
        });

        //이미지 리스트에 이미지 뿌려주기
        imageListTarget.append(tempHtml);

        //삭제 버튼 이벤트 초기화
        let removeTarget = imageListTarget.find('li > i');
        removeTarget.off();
        removeTarget.on('click', function (event) {
            $(this).parent().remove();
            let removeTargetThis = $(this);
            asyncForEach(imageFixSource,function(obj, index){
                if(obj.fileId == removeTargetThis.parent().data('realIndex')){
                    //console.log(removeTargetThis.parent().data('realIndex'));
                    imageFixSource.splice(index,1);
                    fileCountDraw(imageFixSource.length);
                    //console.log(imageFixSource.length);
                }
            });
            //console.log(imageFixSource);

            setImagelistIndex();
        });

        //상품 사진 편집 modal 호출
        imageCropAreaTarget.modal('show');
    });

    let imageCropAreaSlideSlider = false;

    //modal이 열릴 때 기본 처리
    imageCropAreaTarget.on('shown.bs.modal', async function (event) {
        event.preventDefault();
        let wrapperTarget = imageCropAreaTarget.find('.modal-body > #imageCropAreaSlide > .swiper-wrapper');
        let tempHtml = '';
        //console.log(imageFixSource);
        //ojb 내용 들 corp area에 뿌려주기
        await asyncForEach(imageFixSource,function(obj, index){
            //이미 크롭 되었다면 크롭 된 내용을 보여준다. (크롭시 crop objcet는 파괴 되고, value는 corop된 images base 64가 들어감.)
            //앨리먼트를 변수에 넣는 이유는, append를 하면 순차적으로 앨리먼트가 나타나지 않아서, 따로 처리..
            if(imageFixSource[index].fileElementCropped == true){
                tempHtml += (obj.fileCropObj);
            }
            else{
                tempHtml += (obj.fileCropElement);
            }
        });
        wrapperTarget.append(tempHtml);
        //반복문을 왠만하면 두번 안돌리고 싶지만, 이미 crop 처리 된건 인스턴스 생성 안해야 함..
        await asyncForEach(imageFixSource,function(obj, index){
            if(imageFixSource[index].fileElementCropped == false){
                imageFixSource[index].fileCropObj = initCrop(wrapperTarget.find('> div[data-real-index="'+obj.fileId+'"] .itemImage')[0]);
            }
        });

        let confirmTarget = imageCropAreaTarget.find('.modal-body > #imageCropAreaSlide > .swiper-wrapper .cropConfirm');
        confirmTarget.off();
        //반영시 crop obj 해제 하고, 수정된 내용 반영
        confirmTarget.on('click', function (event) {
            let realIndex = $(this).data('realIndex');
            let confirmTargerThis = $(this);
            //이미 크롭 된 건 크롭 예외
            asyncForEach(imageFixSource,function(obj, index){
                //console.log(obj.fileId);
                if(obj.fileId == realIndex){
                    if(obj.fileElementCropped != true){
                            obj.fileCropObj.getCroppedCanvas({width:600, height:600}).toBlob(function(blob){
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                obj.fileCropObj.destroy();
                                obj.fileCropObj = cropElementDraw(realIndex,reader.result,true);
                                obj.fileCropFileData = new File([blob], obj.fileData.name, {
                                    type: blob.type,
                                });
                                obj.fileElementCropped = true;
                                wrapperTarget.find('> div[data-real-index="'+realIndex+'"] .itemImage').attr('src',reader.result);
                            }
                            reader.readAsDataURL(blob);
                        });
                        confirmTargerThis.remove();
                    }
                    else{
                        bsCommonAlert('이미 반영 처리 된 상품 사진 입니다.');
                    }
                }

            });
        });
        imageCropAreaSlideSlider = new Swiper('#imageCropAreaSlide', {
            speed: 400,
            spaceBetween: 0,
            navigation: {
                nextEl: '#imageCropAreaSlide .swiper-button-next',
                prevEl: '#imageCropAreaSlide .swiper-button-prev',
            },
            autoHeight: false,
            shortSwipes: false,
            simulateTouch: false,
            allowTouchMove: false,
            autoplay: false,
            loop: false
        });
    })

    //modal이 닫히면 모두 파괴
    imageCropAreaTarget.on('hidden.bs.modal', function (event) {
        imageCropAreaTarget.find('.modal-body > #imageCropAreaSlide > .swiper-wrapper').empty();
        imageCropAreaSlideSlider.destroy();
        asyncForEach(imageFixSource,function(obj, index){
            if(imageFixSource[index].fileElementCropped != true){
                imageFixSource[index].fileCropObj.destroy();
            }
        });
    })

    //상품 사진 영역 END

    var paymentTypeSelectTarget = $('ul.coinType > li');
    var coinSelectTarget = $('#coinSelect');
    var krwSelectTarget = $('#krwSelect');

    //코인 타입 bulid
    paymentTypeSelectTarget.on("click",function(){
        $('ul.coinType > li').removeClass('active');
        $(this).addClass('active');

        if($(this).data('type') == 'coin'){
            //전역 변수도 설정... 이럴거면 input에서 type 쏘지말고.. js에서 쏘는건..어떨지?
            barryWritePriceType = 'TP3MC';
            krwSelectTarget.find('input').attr('disabled',true);
            krwSelectTarget.hide();
            coinSelectTarget.show();
            coinSelectTarget.find('input').attr('disabled',false);
        }
        else{
            barryWritePriceType = 'KRW';
            krwSelectTarget.find('input').attr('disabled',false);
            krwSelectTarget.show();
            coinSelectTarget.hide();
            coinSelectTarget.find('input').attr('disabled',true);
        }
        //선택 옵션 리스트 제거
        $("#sit_option_frm").empty();
        console.log(barryWritePriceType);
    });

    //결제 타입 설정
    if(barryWritePriceType =="KRW"){
        paymentTypeSelectTarget.eq(1).trigger('click');
    }
    else{
        paymentTypeSelectTarget.eq(0).trigger('click');
    }

    //submit 막기
    $(document).on("submit",'#itemUpload',async function(){
        //병욱님이 전달 해주신 이미지 자르는 샘플이 ES 문법을 따라서, 변수 선언만 ES로..
        event.preventDefault();
        //GB write 변수 재선언
        let f = this;
        //파일 체크 변수
        let status = true;
        let itemUploadFormData = new FormData($(this)[0]);
        let submitBtnTarget = $('#btn_submit');
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

        itemUploadFormData.append('token',token);
        itemUploadFormData.append('itemId',barryWriteItemId);
        itemUploadFormData.append('priceType',barryWritePriceType );

        //fix된 image 정보 담아주기,
        if(imageFixSource.length <= 0){
            status = false;
        }
        else{
            await asyncForEach(imageFixSource,function(item, index){
                if(item.fileElementCropped == false){
                    status = false;
                    return false;
                }
                itemUploadFormData.append('imageFixSourceList[]',item.fileCropFileData);
            });
        }

        if(status == false){
            bsCommonAlert('상품 사진이 없거나 수정 반영이 안된 사진이 있습니다.');
            btnDisabledStatus(submitBtnTarget,false);
            return false;
        }

        //http body를 줄이기 위해 정렬 안된 첨부 이미지는 제거 합니다.
        itemUploadFormData.delete('imageSource[]');

        /*
        console.log(itemUploadFormData.entries());
        for(let peer of itemUploadFormData.entries()) {
        	console.log(peer[0]+ ', '+ peer[1]);
        	console.log(peer);
        	if(peer[1].length <= 0){
        		console.log(peer[1].length);
        		console.log('0입니다!');
        	}
        	else{
        		console.log(peer[1].length);
        		console.log('1입니다!');
        		//파일인 경우에는, 따로 검사를 해야 함..
        	}
        }
         */

        //상품 등록 리팩토링은 SMS 발송.. 작업 완료 후
        var selectType = barryWritePriceType;

        if(selectType == "TP3MC"){
            if(f.priceEtp3.value == "" && f.priceEmc.value == ""){
                f.priceEtp3.value = "";
                bsCommonAlert('e-TP3 또는 e-MC 가격을 하나라도  넣어주세요!\n 입력하신 코인가격으로 결제됩니다.');
                btnDisabledStatus(submitBtnTarget,false);
                return false;
            }

        }
        else{
            if(f.priceKrw.value == ""){
                f.priceEtp3.value = "";
                f.priceEmc.value = "";
                bsCommonAlert('현금가격을 넣어주세요!\n현금으로 판매됩니다');
                btnDisabledStatus(submitBtnTarget,false);
                return false;
            }
        }

        if(f.itemLimitQty.value > 0){

            if(f.itemLimitActivativationDatetime.value <= 0){
                bsCommonAlert('한정 판매 시작일을 설정해 주세요!');
                btnDisabledStatus(submitBtnTarget,false);
                return false;
            }
            if(f.itemLimitDeactivativationDatetime.value <= 0){
                bsCommonAlert('한정 판매 종료일을 설정해 주세요!');
                btnDisabledStatus(submitBtnTarget,false);
                return false;
            }

        }

        if(parseInt(f.itemStockQty.value) < parseInt(f.itemNotiQty.value)){
            bsCommonAlert('재고 통보수량이 재고수량보다 더 클 수 없습니다.');
            btnDisabledStatus(submitBtnTarget,false);
            return false;
        }


        var subject = "";
        var content = "";

        $.ajax({
            url: g5_bbs_url+"/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.itemSubject.value,
                "content": f.itemContents.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (subject) {
            bsCommonAlert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
            btnDisabledStatus(submitBtnTarget,false);
            f.itemSubject.focus();
            return false;
        }

        if (content) {
            bsCommonAlert("내용에 금지단어('"+content+"')가 포함되어있습니다");
            btnDisabledStatus(submitBtnTarget,false);
            if (typeof(ed_itemContents) != "undefined")
                ed_wr_content.returnFalse();
            else
                f.itemContents.focus();
            return false;
        }
        let uploadUrl = false
        //false 인 경우 상품 등록 상태
        if(barryWriteItemId == false){
            uploadUrl = '/API/barry/goods/item/upload';
        }
        else{
            uploadUrl = '/API/barry/goods/item/upload/modifications';
        }
        // ajax 처리 할 곳 ..
        $.ajax({
            cache : false,
            //url : g5_bbs_url+'/write_update.php',
            //url : g5_bbs_url+'/itemUpload.php',
            url : g5_url+uploadUrl,
            type : 'POST',
            processData: false,
            //contentType : 'multipart/form-data; charset=UTF-8',
            contentType : false,
            dataType : 'json',
            data : itemUploadFormData,
            success : function(data, textStatus) {
                //console.log(data);
                //console.log(textStatus);
                if(data.code == 200){
                    bsCommonAlert(data.uploadMsg,'success');
                    $('#bo_w').html(data.html);
                }
                else{
                    bsCommonAlert(data.uploadMsg,'warning');
                }
                btnDisabledStatus(submitBtnTarget,false);
                return false;
            },
            error : function(xhr, status) {
                console.log(xhr);
                btnDisabledStatus(submitBtnTarget,false);
            }
        });

        return false;
    });
});
/*


    함수 목록


*/
function initCrop(cropImageDataId) {
    //const cropData = {};
    const cropperOptions = {
        viewMode: 1, // 크롭 상자가 캔버스 크기를 초과하지 않도록 제한
        dragMode: 'move',
        // initialAspectRatio: 1, // 크롭 상자의 가로세로 비율 초기값
        aspectRatio: 1, // 크롭 상자의 가로세로 비율
        responsive: false,
        //data: cropData,
        movable: true, // Enable to move the image.
        rotatable: false, // Enable to rotate the image.
        scalable: false, // Enable to scale the image.
        zoomable: true, // Enable to zoom the image.
        cropBoxMovable: false,
        cropBoxResizable: false,
        minCropBoxWidth: 600, // The minimum width of the crop box. (Note: This size is relative to the page, not the image.)
        minCropBoxHeight: 600,
        autoCrop: true,
        autoCropArea: 1,
        crop(event) {

        },
    }
    return new Cropper(cropImageDataId,cropperOptions);
}

//imageList draw
function imageListDraw(index, realIndex, data){
    let html =(
        '<li data-index="'+index+'" data-real-index="'+realIndex+'">' +
        '   <img src="'+data+'" class="img-thumbnail itemImage">' +
            '<i class="fa fa-window-close"></i>' +
        '</li>'
    );
    return html;
}
//crop area, slide draw
function cropElementDraw(realIndex,data,type= false){
    let html = (
        '<div class="swiper-slide" data-real-index="'+realIndex+'">' +
            '<article class="cropContentsWrap">' +
                '<img src="'+data+'" class="img-thumbnail itemImage">' +
            '</article>'

        //'<img src="'+data+'" class="itemImage">'
    );
    //이미 반영 완료 된 경우 버튼을 노출 시키지 않는다.
    if(type == true){
        html +=(
            '<span class="btn btn-success btn-block cropConfirm" data-real-index="'+realIndex+'">수정 반영 완료</span>' +
            '</div>'
        );
    }
    else{
        html +=(
            '<span class="btn btn-primary btn-block cropConfirm" data-real-index="'+realIndex+'">수정 반영</span>' +
            '</div>'
        );
    }

    return html;
}
//file count draw
function fileCountDraw(value = 0){
    $('#fileCountDraw').html(value);
}
//file reader return
function getfileInfo(value){
    return new Promise((resolve, reject)=>{
        let reader = new FileReader();
        reader.onload = function(e) {
            return resolve(e.target.result);
        }
        reader.readAsDataURL(value);
    })
}
//get data:image, array 값들 받아서 array로 리턴?
async function getDataImage(value){
    //let image = await Promise.all(getfileInfo(value));
    let image = await getfileInfo(value).then(function(imageData){
       return imageData;
    });
    return image;
}
//imagelist index reset
function setImagelistIndex(){
    let elItemIndex = 0;
    for(let elItem of $('#imageList > li')) {
        //실제 데이터형을 써야 하는데.. 일단.. 셀렉터 때문에 속성으로 수정도 같이
        $(elItem).data('index',elItemIndex);
        $(elItem).attr('data-index',elItemIndex);
        elItemIndex++;
    }
    return true;
}
//imagelist index get
function getImagelistIndex(){
    let tempArray = new Array();
    for(let elItem of $('#imageList > li')) {
        tempArray.push(
            {
                index:$(elItem).data('index'),
                realIndex:$(elItem).data('realIndex')
            }
        )
    }
    return tempArray;
}
//valueArray 인자값은 deep copy가 아님 shallow copy,
async function processImageList(valueArray){
    await setImagelistIndex();
    let indexTempArray = new Array();
    indexTempArray = await getImagelistIndex();
    let tempArray = new Array();
    tempArray = valueArray.slice();

    await asyncForEach(tempArray,async function(obj, index){
        await asyncForEach(indexTempArray,function(innerObj, innerIndex){
            if(obj.fileId == innerObj.realIndex){
                valueArray[innerObj.index] = obj;
            }
        });
    });
}

function coinApiInfo(callback){
    $.ajax({
        cache : false,
        url : "https://cybertronchain.com/apis/barry/apis_test.php",
        type : 'POST',
        dataType : 'json',
        data : {'ckey':'tempToken','kind':'getprice2','coin_type':'e-TP3'},
        success : function(data, textStatus) {
            if(data.code == '00'){
                console.log(data);
                var temp = new Array();
                temp[0] = data.ex_rate;
                temp[1] = data.epay_per_coin;

                callback(temp);
            }
            else{
                bsCommonAlert('현금 환율 불러오기를 정상 처리 하지 못하였습니다.');
            }
        },
        error : function(xhr, status) {
            bsCommonAlert('서버와 연결에 실패 하였습니다.', 'danger');
        }
    });
}

function coinApiInfo2(){
    return new Promise(function(resolve, reject) {
        $.ajax({
            //cache : false,
            url : "https://cybertronchain.com/apis/barry/apis.php",
            type : 'POST',
            dataType : 'json',
            data : {'ckey':'tempToken','kind':'getprice2','coin_type':'e-TP3'},
            success : function(data, textStatus) {
                if(data.code == '00'){
                    //console.log(data);
                    var temp = new Array();
                    temp[0] = data.ex_rate;
                    temp[1] = data.epay_per_coin;
                    resolve(temp);
                }
                else{
                    bsCommonAlert('현금 환율 불러오기를 정상 처리 하지 못하였습니다.');
                }
            },
            error : function(xhr, status) {
                bsCommonAlert('서버와 연결에 실패 하였습니다.', 'danger');
            }
        });
    });
}

function coinApiInfo3(){
    var target = $.Deferred();
    $.ajax({
        //async : false,
        cache : false,
        url : "https://cybertronchain.com/apis/barry/apis_test.php",
        type : 'POST',
        dataType : 'json',
        data : {'ckey':'tempToken','kind':'getprice2','coin_type':'e-TP3'},
        success : function(data, textStatus) {
            if(data.code == '00'){
                var temp = new Array();
                temp[0] = data.ex_rate;
                temp[1] = data.epay_per_coin;
                target.resolve(temp);

            }
            else{
                bsCommonAlert('현금 환율 불러오기를 정상 처리 하지 못하였습니다.');
            }
        },
        error : function(xhr, status) {
            bsCommonAlert('서버와 연결에 실패 하였습니다.', 'danger');
        }
    });
    return target.promise();
}
