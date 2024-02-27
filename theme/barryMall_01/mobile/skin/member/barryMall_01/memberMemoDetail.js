$(function($) {
    $("html, body").scrollTop( $(document).height());

});
/*


    함수 목록


*/
function more(mr_id){
    //접근자 정보 삽입
    mb_id = barry_orderDetail_send;

    $.ajax({
        cache : false,
        url : g5_url+"/API/barry/memo",
        type : 'GET',
        data : {'mrId':mr_id},
        success : function(data) {
            if(data.code == 200) {
                for(var i = 0; i<=data.memoInfo.length; i++){
                    if(data.memoInfo[i]){
                        if(data.result[i]['day']!=null){
                            var msg = "<div class='datetime'>"+data.result[i]['date']+" ("+data.result[i]['day']+")</div>";

                            $('.more_section').append(msg);
                        }
                        if(data.memoInfo[i]['me_write_mb_id']==mb_id){

                            var msg = data.memoInfo[i]['me_memo'];
                            var msg2 = "<div class='me'><ul><li class='sym'>&nbsp;</li><li class='desc'>";
                            msg2 += msg;
                            msg2 += "</li><li class='dt'>"+data.result[i]['apm']+"<br>"+data.result[i]['whour']+":"+data.result[i]['wmin']+"</li></ul></div>";

                            $('.more_section').append(msg2);
                        }
                        if(data.memoInfo[i]['me_write_mb_id']!=mb_id){
                            var msg = "";
                            msg +="<div class='target'><ul><li class='img'><img src='"+g5_url+"/img/no_profile.gif' alt='profile_image' />";
                            msg +="     <span>"+data.result[0]['target_nick']+"</span></li><li class='sym'>&nbsp;</li> <li class='desc'>"+data.memoInfo[i]['me_memo']+"</li>"
                            msg +="<li class='dt'>"+data.result[i]['apm']+"<br>"+data.result[i]['whour']+":"+data.result[i]['wmin']+"</li></ul></div>";
                            $('.more_section').append(msg);
                        }
                    }
                }
                $("#load").hide();
            }
            else {
                bsCommonAlert(data.memoInfo);
            }

        },
        error:function(request,status,error){
            //console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            bsCommonAlert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
};

function sendMemo() {
    var memo = $.trim($('#me_memo').val());
    if (memo==''){
        bsCommonAlert('내용을 입력하세요.');
        return false;
    }
    $.ajax({
        url : '/bbs/memberMemoUpdate.php',
        type : 'POST',
        data : {'target_id':barry_orderDetail_recv,'mb_id':barry_orderDetail_send,"me_memo":memo},
        dataType : 'json',
        success : function(resp){
            if (resp.msg == 'fail') {
                alert(''+resp.msg);
            }
            else if (resp.msg == 'success') {
                var msg = resp.data;
                var msg2 = "<div class='me'><ul><li class='sym'>&nbsp;</li><li class='desc'>";
                msg2 += msg;
                msg2 += "</li><li class='dt'>조금전</li></ul></div>";
                $('#memo_log').append(msg2);
                $("html, body").scrollTop( $(document).height());
                $('#me_memo').val('');
                $("#load").hide();
            }
            else {
                bsCommonAlert('알수없는 오류');
            }
        },
        error:function(request,status,error){
            console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            bsCommonAlert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);

        }
    });
}