<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

	<!-- Bootstrap 核心 CSS 文件 -->
	<link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">

	<!-- mobile CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/activate.css">
	<!-- HTML5 Shim 和 Respond.js 用于让 IE8 支持 HTML5元素和媒体查询 -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->

	<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
	<script src="/Public/js/jquery-3.1.0.min.js"></script>
	<!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
	<script src="/Public/bootstrap/js/bootstrap.min.js"></script>

	<!-- layer CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/layer.css">
	<script src="/Public/js/layer.js"></script>

	<title>激活</title>
</head>
<body>
	<header class="home-header">立即激活</header>
	<div class="container-fluid activate">
		<input type="hidden" id="device_code" name="device_code" value="<?php echo ($device); ?>">
		<input class="form-control" type="text" id="register_code" name="register_code" placeholder="请输入激活码" >
		<input class="form-control" type="text" id="device_name" name="device_name" placeholder="请定义一个设备名称（如：展厅横屏点餐）" >
		<button class="form-control btn btn-danger" onclick="submit_code()">激活</button>
	</div>
	<footer class="home-footer"></footer>
	<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content clearfix text-center">
				<button type="button" class="modal-close pull-right" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
					<div class="modal-imgcontent">
						<img src="/Public/images/ok.png" class="center-block">
					</div>
				<p class="success-info" id="info">注册成功</p>
			</div>
		</div>
	</div>
</body>
<script>
	function submit_code(){
		var register_code = $("#register_code").val();
		var device_code = $("#device_code").val();
		var device_name = $("#device_name").val();
		console.log(register_code);
		console.log(device_code);

		if(register_code == ""){
			layer.msg("注册码不能为空！");
			return;
		}

		if(device_name == ""){
			layer.msg("设备名称必填！");
			return;
		}

		$.ajax({
			url:"/index.php/allAgent/activate/isActivate",
			type:"post",
			data:{"register_code":register_code,"device_code":device_code,'device_name':device_name},
			dataType:"json",
			success:function(msg){
				//console.log(msg);
				if(msg.code == 1){
					$("#info").html("注册成功");
					$("#loginModal").modal("show");
				}else{
					$("#info").html(msg.msg);
					layer.msg(msg.msg);
				}
			}
		});
	}
</script>
</html>