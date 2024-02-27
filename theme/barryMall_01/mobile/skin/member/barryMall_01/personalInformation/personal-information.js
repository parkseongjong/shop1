$(function($) {
    console.log(window.firstVisitAgree);
    $(document).on("click",'.agree-button',async function() {
        event.preventDefault();
        let submitBtnTarget = $('button.agree-button');
        btnDisabledStatus(submitBtnTarget,true);
        //console.log(window.firstVisitAgree);
        if(window.firstVisitAgree.type == 'Y'){
			console.log('here');
            $.ajax({
                cache: false,
                url: g5_url + '/API/personal-information',
                type: 'POST',
                processData: false,
                contentType: 'application/json; charset=UTF-8',
                dataType: 'json',
                data: JSON.stringify({"token":window.firstVisitAgree.token}),
                success: function (data, textStatus) {
                    console.log(data);
                    if (data.code == 200) {
                        // bsCommonAlert(data.piMsg, 'success');
                        window.location.replace(g5_url);
                    }
                    else {
                        bsCommonAlert(data.piMsg, 'danger');
                        if(data.piCode == 406){
                            window.location.replace(g5_url);
                        }
                    }
                    btnDisabledStatus(submitBtnTarget,false);
                },
                error: function (xhr, status) {
                    //console.log(xhr);
                    bsCommonAlert('오류!', 'danger');
                    btnDisabledStatus(submitBtnTarget,false);
                }
            });
        }
        else{
			//console.log('hihi');
            window.location.href = g5_url+'/index.php?ckey='+window.firstVisitAgree.url+'&firstVisitAgree=N'
        }
    });

    $(document).on("click",'.disagree-button',async function() {
        bsCommonAlert('개인정보 제 3자 제공 동의를 거부 시 베리베리스마켓 이용이 제한 됩니다.', 'danger');
    });
});

