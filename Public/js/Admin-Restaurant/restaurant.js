	function submit_form(){
		var form = $("#restaurant_form")[0];
		var formData = new FormData(form);
		var password = $("input[name='password']").val();
		var passwords = $("input[name='passwords']").val();
		if(password == passwords){
			$.ajax({
				url:"/index.php/admin/Restaurant/index",
				data:formData,
				dataType:'json',
				type:'post',
				cache: false,
				contentType: false,
				processData: false,
				success:function(msg){
					if(msg.code == 1){
						alert("编辑成功!");
					}else{
						alert("编辑失败!");
					}
				},
				error:function(){
					alert("网络出错了");
				}
			});
		}else{
			alert("两次密码不一致！");
		}	
	}