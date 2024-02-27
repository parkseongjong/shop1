/*


    함수목록


 */
function stockOnline(id,table) {
   page =$("#page").val();
    $.confirm({
        title: '경고!',
        content: '재고 있음 상태로 변경 할까요?<br>\n' +
            '\'<b>주문 미확인</b>\', \'<b>배송중</b>\' 상태의 주문 건이 상품 재고수를 차감 합니다.<br>\n' +
            '주문 건이 \'<b>배송완료</b>\' 되었을 때 설정한 상품 재고수에 영향을 미치지 않으니 반드시 재고 있음 상태로 변경하기 전<br>\n' +
            '기존 주문건이 \'<b>배송완료</b>\' 처리가 되었는지, 또는 상품의 재고수량을 추가로 늘렸는지 확인해 주세요.<br>\n'+
            '<div class="alert alert-info" role="alert"><h3 class="stockOnlineAlertLink"><p> 먼저 상품 재고 수량을 확인 해주세요!</p><a href="'+g5_bbs_url+'/write.php?w=u&bo_table='+g5_bo_table+'&wr_id='+id+'">상품 재고 수량을 수정하러 바로가기</a></h3></div>',
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //처리 없음
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {
                    $.ajax({
                        cache : false,
                        url : g5_url+"/API/barry/goods/status/soldout",
                        type : 'POST',
                        dataType : 'json',
                        data : {'goodsId':id,'goodsTable':table},
                        success : function(data) {
                            if(data.code == 200) {
                                
                                console.log(page);
                                bsCommonAlert(data.goodsMsg,'success');
                                setTimeout(() => {
                                    document.location.href = "/bbs/member_goodslist.php?bo_table="+table+"&page="+page;
                                }, 1000);
                                
                            }
                            else{
                                bsCommonAlert(data.goodsMsg,'danger');
                                console.log(data);
                            }

                        },
                        error:function(request,status,error){
                            console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                        }
                    });

                }
            },
        }
    });
}

function stockOffline(id,table) {
    var page = $("#page").val();
    $.confirm({
        
        title: '경고!',
        content: '재고 없음 상태로 변경 할까요?',
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //처리 없음
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {
                    $.ajax({
                        cache : false,
                        url : g5_url+"/API/barry/goods/status/unSoldout",
                        type : 'POST',
                        dataType : 'json',
                        data : {'goodsId':id,'goodsTable':table},
                        success : function(data) {
                            if(data.code == 200) {
                                bsCommonAlert(data.goodsMsg,'success');
                                setTimeout(() => {
                                    document.location.href = "/bbs/member_goodslist.php?bo_table="+table+"&page="+page;
                                }, 1000);
                                 
                            }
                            else{
                                bsCommonAlert('서버 연결에 실패하였습니다.','danger');
                            }
                        },
                        error:function(request,status,error){
                            console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                        }
                    });
                }
            },
        }
    });
}
function recover(id,table) {
    var page = $("#page").val();
    $.confirm({
        title: '경고!',
        content: '상품을 복구 할까요?',
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //처리 없음
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {
                    $.ajax({
                        cache : false,
                        url : g5_url+"/API/barry/goods/status/recover",
                        type : 'POST',
                        dataType : 'json',
                        data : {'goodsId':id,'goodsTable':table},
                        success : function(data) {
                            if(data.code == 200) {
                                bsCommonAlert(data.goodsMsg,'success');
                                setTimeout(() => {
                                    document.location.href = "/bbs/member_goodslist.php?bo_table="+table+"&page="+page;
                                }, 1000);
                                 
                            }
                            else{
                                bsCommonAlert('서버 연결에 실패하였습니다.','danger');
                            }

                        },
                        error:function(request,status,error){
                            console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                        }
                    });
                }
            },
        }
    });
}


function remove(id,table) {
    var page = $("#page").val();
    $.confirm({
        title: '경고!',
        content: '주의!! 정말 상품을 삭제할까요?',
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //처리 없음
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {
                    $.ajax({
                        cache : false,
                        url : g5_url+"/API/barry/goods/status/delete",
                        type : 'POST',
                        dataType : 'json',
                        data : {'goodsId':id,'goodsTable':table},
                        success : function(data) {
                            if(data.code == 200) {
                                bsCommonAlert(data.goodsMsg,'success');
                                setTimeout(() => {
                                    document.location.href = "/bbs/member_goodslist.php?bo_table="+table+"&page="+page;
                                }, 1000);
                                 
                            }
                            else{
                                bsCommonAlert('서버 연결에 실패하였습니다.','danger');
                            }

                        },
                        error:function(request,status,error){
                            console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                        }
                    });
                }
            },
        }
    });
}

function goodTempValidCheck(id,table) {
    page = $("#page").val();
    $.confirm({
        title: '경고!',
        content: '재심사 신청을 정말 하시겠습니까?',
        buttons: {
            cancel:{
                text: '취소',
                btnClass: 'btn btn-dark',
                action : function () {
                    //처리 없음
                }
            },
            confirm:{
                text: '확인',
                btnClass: 'btn btn-success',
                action : function () {
                    $.ajax({
                        cache : false,
                        url : g5_url+'/API/barry/goods/status/reConsider',
                        type : 'POST',
                        dataType : 'json',
                        data : {'goodsId':id , 'goodsTable':table},
                        success : function(data) {
                            if(data.code == 200){
                                bsCommonAlert(data.goodsMsg,'success');
                                setTimeout(() => {
                                    location.reload("/bbs/member_goodslist.php?bo_table="+table+"&page="+page);
                                }, 1000);
                               console.log(data);
                            }
                            else{
                                bsCommonAlert(data.goodsMsg,'success');
                                console.log(data);
                            }
                        },
                        error:function(request,status,error){
                            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
                        }
                    });
                }
            },
        }
    });
}

function goDetail(id,table,page) {
    document.location.href = "/bbs/member_goodsdetail.php?bo_table="+table+"&wr_id="+id+"&page="+page;
}
