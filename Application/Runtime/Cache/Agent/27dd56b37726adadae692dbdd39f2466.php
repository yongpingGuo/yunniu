<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>方派品牌商登录</title>
	<link rel="stylesheet" type="text/css" href="/Public/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="/Public/css/agent.css">
	<script type="application/javascript" src="/Public/js/jquery-3.1.0.min.js"></script>
</head>
<body class="login-bg">
	<div class="login">
		<div class="login-content">
			<h1 class="login-head">
				<img src="/Public/images/admin_logo.png">
				<span>方派品牌商后台</span>
			</h1>
			<form id="myform">
			<div class="login-main">
				<h3 class="main-head">欢迎登录</h3>
				<input type="text" name="username" class="form-control login-input" placeholder="用户名">
				<input type="password" name="pwd" class="form-control login-input" placeholder="密码">
				<div class="code-content">
					<input type="text" name="code" class="form-control login-input" placeholder="验证码">
					<div class="code-box">
						<img src="/index.php/Agent/Index/verifyImg" class="code-img" onclick="this.src='/index.php/Agent/Index/verifyImg/'+Math.random()">
					</div>
				</div>				
				<button class="form-control login-btn" type="button" onclick="commit()">登录</button>
				<input type="reset" id="reset" style="display: none;"/>
			</div>
			</form>
		</div>
	</div>
</body>
<script>
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
				url:"/index.php/agent/index/checklogin/username/"+username+"/pwd/"+pwd+"/code/"+code+"",
				async:true,
				dataType:"json",
				success:function(data){
					if(data.code == 0){
						location.href = "/index.php/agent/Index/index";
					}else{
						alert(data.msg);
                        $(".code-img").click();
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

</script>
</html>