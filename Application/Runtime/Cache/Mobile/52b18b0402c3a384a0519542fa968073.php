<?php if (!defined('THINK_PATH')) exit();?><!-- <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" /> -->


<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
		<link rel="stylesheet" type="text/css" href="/Public/css/ydc_order.css"/>
		<title></title>
	</head>
	<body class="order-body">
		<div class="order-content">
			<!-- 是否展示头部-->
			<?php if($show == 1): ?><header class="header-company">
					<div class="logo-img"><img src="<?php echo ($info["logo"]); ?>"/></div>
					<div class="company-info">
						<div class="company-name"><?php echo ($info["restaurant_name"]); ?></div>
						<div class="company-address"><?php echo ($info["address"]); ?></div>
					</div>
				</header><?php endif; ?>
			<div class="main-content">
				<div class="main-order">
					<div class="order-list">
						<a class="order-btn" id="diancan" href="<?php echo ($url['diancan']); ?>">
							<img src="/Public/images/diancan.png"/>
							<span class="btn-text">开始点餐</span>
						</a>
						<a class="order-btn" href="<?php echo ($url['myorder']); ?>">
							<img src="/Public/images/dingdan.png"/>
							<span class="btn-text">查看订单</span>
						</a>
					<input type="text" value="<?php echo ($desk_code); ?>" hidden id="desk_code">
					</div>
				</div>
			</div>
			<input name="isCode" id="isCode" value="<?php echo ($returnData["code"]); ?>" hidden/>
			<input name="urlString" id="urlString" value="<?php echo ($returnData["data"]); ?>" hidden/>
			<footer class="footer-company">
				<div class="footer-info">技术支持：方派科技</div>
			</footer>
		</div>
	</body>
</html>
<script>
	window.onload = function(){ 
		var desk_code = document.getElementById("desk_code").value;
		var oldUrl = document.getElementById("diancan").href;
		if(desk_code){
			document.getElementById("diancan").setAttribute("href",oldUrl+"/desk_code/"+desk_code);
		}
	}
</script>