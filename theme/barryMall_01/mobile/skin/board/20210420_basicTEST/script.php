<script>

	var total_page = "<?=$total_page?>";
	var now_page = "<?=$page?>";
	var roll_page = now_page;


	$(window).scroll(function(){
		var chkBtm = parseInt($(document).height()) - parseInt($(window).height());
		
		if(chkBtm == $(window).scrollTop()){
			roll_page++;
			if(roll_page <= total_page){
				callContent(roll_page,'append');
			}
		}else if($(window).scrollTop() == 0){			
			now_page--;
			if(now_page > 0){
				callContent(now_page,'prepend');
			}
		}
	});

	function callContent(a,b){

		var url = "<?=G5_BBS_URL?>/board.php?bo_table=<?=$bo_table?>&page="+a;
		var tbody = "";
		var thtml = "";
		$.ajax({
			type:"POST",
			url:url,
			dataType : "html",
			success: function(html){
				tbody = html.split('<tbody>');
				thtml = tbody[1].split('</tbody>');
				setTimeout(function() { 
					if(b=='append'){
						$(".tbl_head01").find('tbody').append(thtml[0]);
					}else{
						$(".tbl_head01").find('tbody').prepend(thtml[0]);
					}
				}, 1000);
				
			},
			error: function(xhr, status, error) {
				alert(error);
			}  
		});
	}

</script>