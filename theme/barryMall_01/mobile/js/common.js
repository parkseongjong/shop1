$(function($) {
    $("#setting_go").on("click", function() {
        document.location.href = "/bbs/memberSetting.php";
    });
    $("#gnb_back").on("click", function() {
        history.back();
    });
    $("#main_go").on("click", function() {
        document.location.href = g5_url;
    });

    $("#gnb_open").on("click", function() {
        if ($("#gnb").css('display')=='block'){
            $("#sch_open").show();  // 검색버튼 보이기
            $("#setting_go").show();
            // 스크롤풀기
            $('html, body').css({'overflow':'auto', 'height':'auto'});
            $('#gnb').bind('touchmove');
        } else {
            $("#sch_open").hide();  // 검색버튼 감추기
            $("#setting_go").hide();
            // 스크롤막기
            $('html, body').css({'overflow':'hidden', 'height':'100%'});
            //모바일 터치 스크롤... 해야해서 사용안함.-_-...
//            $('#gnb').bind('touchmove', function(e){
//                e.preventDefault();
//            });
        }
        $("#gnb").toggle();
    });
    
    $("#gnb_close").on("click", function() {
        $("#sch_open").show();  // 검색버튼 보이기
        $("#setting_go").show();
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

    $(document).mouseup(function (e){
        var container = $("#hd_sch");
        if( container.has(e.target).length === 0)
        container.hide();
    });
    //상단으로
    $("#top_btn").on("click", function() {
        $("html, body").animate({scrollTop:0}, '500');
        return false;
    });

    $.datepicker.setDefaults({
        prevText: '이전 달',
        nextText: '다음 달',
        monthNames: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],
        monthNamesShort: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],
        dayNames: ['일', '월', '화', '수', '목', '금', '토'],
        dayNamesShort: ['일', '월', '화', '수', '목', '금', '토'],
        dayNamesMin: ['일', '월', '화', '수', '목', '금', '토'],
        showMonthAfterYear: true,
        yearSuffix: '년'
    });

});

/*


    함수 목록


*/

//alert 은 jquery confirm lib로 대체 될 예정 입니다.
function bsCommonAlert(msg = '경고!', type = 'warning' ){
    
    /*
        success
        danger
        warning
        info
    */
    var html = '';
    html += '<div id="bsCommonAlert" class="alert alert-'+type+' alert-dismissible fade show bsCommonAlert" role="alert">';
    html += '   <strong>'+msg+'</strong>';
    html += '   <button type="button" class="close" data-dismiss="alert" aria-label="Close">';
    html += '       <span aria-hidden="true">&times;</span>';
    html += '   </button>';
    html += '</div>';
    if(!$('#bsCommonAlert').length){
        $('body').append(html);  
    }
}

function formDataToJson($data){
    let unindexedarray = $data.serializeArray();
    let indexedArray = {};

    $.map(unindexedarray, function(n, i){
        indexedArray[n['name']] = n['value'];
    });

    return indexedArray;
}

function btnDisabledStatus(target = false,type = false){
    if(!target) {
        return false;
    }
    else{
        if(type){
            target.attr('disabled',true);
        }
        else{
            target.attr('disabled',false);
        }
    }
}

// not callback foreach
async function asyncForEach(array, callback) {
    for (let index = 0; index < array.length; index++) {
        const result = await callback(array[index], index, array);
    }
}

/*
    targetObj = [
            {'target':'opt_id[]','postName':'optId'},
            {'target':'opt_stock_qty[]','postName':'optStockQty'},
            {'target':'opt_use[]','postName':'optUse'},
            {'target':'opt_price[]','postName':'optPrice'},
    ];
    원하는 post key 값으로 build 할 때,
 */
async function formDataBuild(formData,targetObj){
    await asyncForEach(targetObj, async function(item, index) {
        formData.delete(item.target);
        await asyncForEach($('input[name="' + item.target + '"]'), function (item2, index2) {
            formData.append(item.postName, $(item2).val());
        });
    });
}

// json 타입으로 배열 form build
async function jsonTypeFormDataBuild(formData){
    let tempObj = {};
    let pattern = new RegExp("[\[\]]$");
    for await(peer of formData.entries()){
        if(pattern.test(peer[0])){
            if(Array.isArray(tempObj[peer[0]])){
                tempObj[peer[0]].push(
                    peer[1]
                );
            }
            else{
                tempObj[peer[0]] = new Array();
                tempObj[peer[0]].push(
                    peer[1]
                );
            }

        }
        else{
            tempObj[peer[0]] = peer[1];
        }

    }
    return JSON.stringify(tempObj);
}

//formDataBuild,jsonTypeFormDataBuild 동시에 수행하는 function
async function jsonTypeFormDataAndFormDataBuild(formData,targetObj){
    await asyncForEach(targetObj, async function(item, index) {
        formData.delete(item.target);
        console.log(item.postName);
        await asyncForEach($('[name="' + item.target + '"]'), function (item2, index2) {
            formData.append(item.postName, $(item2).val());
        });
    });

    let tempObj = {};

    for await(peer of formData.entries()){
        let comparison = await targetObj.find(function(element, index, array){
            return (element.postName == peer[0])?true:false;
        });
        if(comparison){
            if(Array.isArray(tempObj[peer[0]])){
                tempObj[peer[0]].push(
                    peer[1]
                );
            }
            else{
                tempObj[peer[0]] = new Array();
                tempObj[peer[0]].push(
                    peer[1]
                );
            }
        }
        else{
            tempObj[peer[0]] = peer[1];
        }
    }
    return JSON.stringify(tempObj);
}

function commaAdd(value) {
    return Number(value).toLocaleString('en');
}

//공백 확인 (임시)
function checkSpace(str) {
    if(typeof str == false){
        return true;
    }
    if(str.length === 0){
        return true;
    }
    else if(str.search(/^\s/) >= 1) {
        return true;
    }
    else {
        return false;
    }
}
//선택 옵션 공백 또는 undefined 확인
//search 못찾으면 -1 반환
//찾으면 true 리턴
function checkUndefined(str) {
    console.log(str);
    if(str == false){
        return true;
    }
    if(typeof str == 'undefined') {
        return true
    }
    else if(str.search(/^(undefined|\s+)/) >= 1){
        return true;
    }
    else {
        return false;
    }
}

function checkNaN(str){
    if(typeof str == false){
        return true;
    }
    if(isNaN(str) == true) {
        return true
    }
    else {
        return false;
    }
}
function notready() {
    alert('준비중인 서비스입니다..');
    return false;
}
function goShopping() {
    document.location.href = '/bbs/board.php?bo_table=Shop';
}
function goUsed() {
    document.location.href = '/bbs/board.php?bo_table=used';
}
function goCar() {
    document.location.href = '/bbs/board.php?bo_table=car';
}
function goOffline() {
    document.location.href = '/offline';
}
function goEstate() {
    document.location.href = '/bbs/board.php?bo_table=estate';
}
function goMarket() {
    document.location.href = '/bbs/board.php?bo_table=market';
}

function a1o(){		
    $('#xx1').bPopup()
}		
function a1x(){
    $('#xx1').bPopup().close();
}
function fsearchbox_submit(f)
{
    if (f.stx.value.length < 2) {
        $('#xx1').bPopup()
       // alert("검색어는 두글자 이상 입력하십시오.");
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