$(function($) {

});
/*


    함수 목록


*/
function goDetail(wr_id) {
    document.location.href = "/bbs/member_my_orderdetail.php?wr_id="+wr_id;
}
function goTempDetail(wr_id,bo_table) {
    document.location.href = g5_url+'/plugin/barryIntegration/?wr_id='+wr_id+'&target_bo_table='+bo_table
}
function goFinishDelivery(wr_id,code) {
    $.confirm({
        title: '안내',
        content: '상품을 전달 받았나요?',
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
                        url : g5_url+"/API/barry/order/status/finish",
                        type : 'PUT',
                        processData: true,
                        contentType: 'application/json; charset=UTF-8',
                        dataType : 'json',
                        data : JSON.stringify({'orderId':wr_id, 'code':code}),
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
                            bsCommonAlert(xhr + " : " + status);
                        }
                    });
                }
            },
        }
    });
}