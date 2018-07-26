<?php if (!defined('THINK_PATH')) exit();?>﻿<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

	<!-- Bootstrap 核心 CSS 文件 -->
	<link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
	<!-- layer CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/layer.css">
	
	<!-- common CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/common.css">
	<!-- main CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/main.css">
	<!-- horizontal CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/horizontal.css">
	
	<!-- 更换颜色 CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/color_orange.css" id="global-css">
	<!-- HTML5 Shim 和 Respond.js 用于让 IE8 支持 HTML5元素和媒体查询 -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
	<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->

	<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
	
	<script src="/Public/js/jquery-3.1.0.min.js"></script>
	<!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
	<script src="/Public/bootstrap/js/bootstrap.min.js"></script>
	<script src="/Public/js/home.js"></script>
	
	<script src="/Public/js/layer.js"></script>
	<script>
		$(function(){
			//横屏客户端模板颜色更改
			var tpl = $("#tpl").val();
			if(tpl==0){
				$('#global-css').attr('href','/Public/css/color_red.css');
			}else if(tpl==1){
				$('#global-css').attr('href','/Public/css/color_blue.css');
			}else if(tpl==2){
				$('#global-css').attr('href','/Public/css/color_green.css');
			}else if(tpl==3){
				$('#global-css').attr('href','/Public/css/color_yellow.css');
			}else if(tpl==4){
				$('#global-css').attr('href','/Public/css/color_black.css');
			}else{
				$('#global-css').attr('href','/Public/css/color_orange.css');
			}	
		})
	</script>
	
	<title>方雅点餐系统</title>
	
</head>

	

<!-- 故障页 -->
<body>
	<header class="home-header">
	</header>
	<div class="overdue">
		<h2 class="text-center">
			<img src="/Public/images/exclamation.png">
			<span>非法访问或设备已过期,请联系厂商续费</span>
		</h2>
	</div>

	<footer class="home-footer text-center">
	</footer>
</body>
</html>