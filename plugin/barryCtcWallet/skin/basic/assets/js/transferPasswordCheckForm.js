/*

    transferPasswordCheckForm.js
	@TO-DO
	레거시 내용, 리뉴얼 때 수정 필요...
*/

let pass = '';
let pass_length = 0;

$(function($){
    let password_length = 6;

	$("#stf_message_btn").on('click tap', function(){
		var num = $("#stf_message_btn a").attr('data-num');
		if (num == 'fail') {
			stf_num_result(g5_url, 'reload');
		} 
        else{
            stf_num_result(g5_url, 'reload');
        }
	});

	$("#set_transferpw_frm_send .number p").on('click tap', function(){
		var num = $(this).attr('data-num');
		if (num == 'del') { // 삭제
			stf_num_del();
		} 
        else if (num == 're') { // 재배열
			stf_num_re();
		} 
        else if (num != '' && pass_length < password_length) { // 0~9
			pass = pass + num;
			$("#pass_area_"+pass_length+" img").attr('src',barry_transferPasswordCheckForm_asstsUrl+'/img/pass_input_y.png');
			pass_length = pass_length + 1;
            
            //리뉴얼 후 재사용을 위해 우선은 각 데이터를 board view.skin에서 다시 불러온다.
			if (pass_length == password_length) {
				$.ajax({
					url : g5_url+'/API/ctcwallet/payment/password/check',
					type : 'POST',
					dataType : 'json',
					data : {plainPassword : pass, sellerAddress :  barryView.sellerVirtualWalletAdress, orderPhone : barryView.memberId, orderId : orderId},
					success : function(resp){
                        //console.log(resp);
						if (!resp.paymentCode && resp.code == 200) {
							$('#paymentLayer').modal('hide');
							//console.log('결제 완료');
							window.location.href= g5_url+'/plugin/barryIntegration/?wr_id='+resp.data.wr_id+'&target_bo_table='+resp.data.targetBoard;
                        }
                        else if (resp.code == 200 && resp.paymentCode == 10 || resp.paymentCode == 20 || resp.paymentCode == 177 || resp.paymentCode == 144 || resp.paymentCode == 155 || resp.paymentCode == 166 || resp.paymentCode == 255 || resp.paymentCode == 244 || resp.paymentCode == 233) {
                            stf_message_box_setting('none', "다시 입력하기", '1', resp.paymentMsg);
						}
					},
					error : function(resp){
						//console.log(resp);
						stf_message_box_setting('none', "다시 입력하기", '2');
					}
				});
			}
		}
		return false; // 브라우저에 따라서 중복실행하는 경우 방지
	});
    
});
/*


    함수 목록


*/
function stf_message_box_setting(data_num, btn_text, msg_index, msg) {
	$("#stf_message_btn a").attr({'data-num':data_num});
	$("#stf_message_btn a span").html(btn_text);
	$("#set_transferpw_frm_send #stf_message_s"+msg_index).removeClass('none').html(msg);;
	$("#set_transferpw_frm_send #explain1").addClass('none');
	$("#set_transferpw_frm_send #stf_message").removeClass('none');
	$("#set_transferpw_frm_send #stf_message_btn").removeClass('none');
}

function stf_num_result(page, status) {
	if (status == 'reload') {
		
		pass_length = 0;
		pass = '';
		document.paymentform.pas1.value = pass;
		$("#set_transferpw_frm_send .stf_message").addClass('none');
		$("#stf_message").addClass('none');
		$("#stf_message_btn").addClass('none');
		$(".password_area img").attr('src',barry_transferPasswordCheckForm_asstsUrl+'/img/pass_input_n.png');
		$("#explain1").removeClass('none');
		stf_num_re();
		
	} 
    else if ( status == 'move') {
		location.href = page + '.php';
	}
}

// set_transferpw
function stf_arrayShuffle(oldArray) {
    var newArray = oldArray.slice();
    var len = newArray.length;
    var i = len;
    while (i--) {
        var p = parseInt(Math.random()*len);
        var t = newArray[i];
        newArray[i] = newArray[p];
        newArray[p] = t;
    }
    return newArray;
} //

function stf_num_re() {
	var num_arr = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
	var new_array = stf_arrayShuffle(num_arr);
	var len = new_array.length;
	for (var i = 0; i < len; i++) {
		if (i + 1 == len) {
			$(".number p").eq(len).attr({'id':'pass_number_'+new_array[i], 'data-num':new_array[i]});
			//$(".number p").eq(len).html('<span>'+new_array[i]+'</span>');
			$(".number p").eq(len).html('<span><img src="'+barry_transferPasswordCheckForm_asstsUrl+'/img/'+new_array[i]+'.png" alt="'+new_array[i]+'" /></span>');
		} 
        else {
			$(".number p").eq(i).attr({'id':'pass_number_'+new_array[i], 'data-num':new_array[i]});
			//$(".number p").eq(i).html('<span>'+new_array[i]+'</span>');
			$(".number p").eq(i).html('<span><img src="'+barry_transferPasswordCheckForm_asstsUrl+'/img/'+new_array[i]+'.png" alt="'+new_array[i]+'" /></span>');
		}
	}
}

function stf_num_del() {
	if ( pass_length > 0 ) {
		if (pass_length == 1) {
			pass_length = 0;
			pass = '';
			$("#pass_area_"+pass_length+" img").attr('src',barry_transferPasswordCheckForm_asstsUrl+'/img/pass_input_n.png');
		} 
        else {
			pass_length = pass_length - 1;
			pass = pass.substr(0, pass_length);
			$("#pass_area_"+pass_length+" img").attr('src',barry_transferPasswordCheckForm_asstsUrl+'/img/pass_input_n.png');
		}
	}
} //