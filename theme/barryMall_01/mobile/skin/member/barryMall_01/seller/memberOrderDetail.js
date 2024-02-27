$(function($) {
    var token = 'etp3'; //기본 값
    var waddr = barryVirtualWalletAddress;
    var page = false;
    var appendTarget = $('.accountList > ul');
    var virtualWalletAccountAreaMenuTarget = $('.virtualWalletAccountArea .menu > ul > li');
    var clicked = false;

    $(document).on("click", '#load', function(e){
        e.preventDefault();
        if(clicked == false){
            clicked = true;
            $.ajax({
                url: "https://cybertronchain.com/wallet2/api_barry/get_history.php?token="+token+"&waddr="+waddr,
                type: "GET",
                dataType: 'json',
                data:{'page': page},
                success: function(data){
                    //console.log(data);
                    if(data.data.length==0){
                        if(page == 1){
                            appendTarget.append(loadEmpty());
                            $("#load").hide();
                        }
                        else{
                            bsCommonAlert('마지막 입/출금 내역입니다.','warning');
                        }
                    }
                    else{
                        page++;
                        for(var i = 0; i<=data.data.length; i++){
                            if(data.data[i]) {
                                appendTarget.append(
                                    loadDatadraw(
                                        data.data[i].name,
                                        data.data[i].amount,
                                        data.data[i].sign,
                                        data.data[i].datetime,
                                        data.data[i].name_text,
                                        data.data[i].type,
                                        (data.data[i].goodsId > 0)?data.data[i].goodsId:' ',
                                        (data.data[i].goodsId > 0)?data.data[i].goodsSubject:' ',
                                        (data.data[i].goodsId > 0)?data.data[i].goodsTable:' ',
                                        token
                                    )
                                );
                            }
                        }
                    }
                    clicked = false;
                },
            });
        }
    });

    $(document).on("click", '.virtualWalletAccountArea .menu > ul > li', function(){
        virtualWalletAccountAreaMenuTarget.removeClass('on');
        $("#load").show();
        $(this).addClass('on');

        token = $(this).data('type');
        page = 1;
        appendTarget.html('');

        //load event 트리거
        $('#load').trigger('click');
    });

    //초기로드 시 보여질 항목
    virtualWalletAccountAreaMenuTarget.eq(1).trigger('click');
});
/*


    함수 목록


*/
//추후.. draw 쪽도.. style 분리 하기.
function loadDatadraw(name,amount,sign,datetime,name_text,type,goodsId,goodsSubject,goodsTable,unit){
    var html = '';

    html += '<li style="margin:16px 0 15px 5px; border-bottom:1px solid #e3e3e3; padding-bottom:15px">';
    html += '   <ul style="border-bottom:0; padding:0; margin:0">';
    html += '     <li><span style="font-size:11.3pt; font-weight:400; color:#555">'+sign+''+amount+'<span style="color:#aaa"> '+unit+'</span><div>'+goodsSubject+''+goodsTable+'</div></span></li>';
    html += '     <li style="float:right; ccolor:#888; padding-right:10px">'+type+'</li>';
    html += '   </ul>';
    html += '   <div style="margin:3px 12px; color:#7f7f7f">'+datetime+'</div>';
    html += '   <div style="margin:0 12px; color:#7f7f7f">'+name_text+ ' : '+name+'</div>';
    html += '</li>';

    return html;
};

function loadEmpty(){
    var html ='';

    html += '<li class="empty">';
    html += '   <div class="alert alert-danger"> 거래 내역이 없어요! </div>';
    html += '</li>';

    return html;
};