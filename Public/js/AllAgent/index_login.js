	/**
	 * 作者：凯
	 * 日期：2017.01.11
	 */
	$(document).keyup(function(event){
		if(event.keyCode ==13){
			commit();
		}
	});
	
	function commit(){
		var username = $("input[name='username']").val();
		var pwd = $("input[name='pwd']").val();
		var code =  $("input[name='code']").val();
		if(username && pwd){
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/index/checklogin/username/"+username+"/pwd/"+pwd+"/code/"+code+"",
				async:true,
				dataType:"json",
				success:function(data){
					if(data.code == 0){
						location.href = "/index.php/allAgent/Index/index";
					}else{
						alert(data.msg);
						$(".code-img").trigger('click');
                        $('input[name="code"]' ).val('');
					}
				}
			});
		}else{
			alert("用户名和密码不能为空！");	
		}
	}

	if(window !=top){  
		top.location.href=location.href;  
	}