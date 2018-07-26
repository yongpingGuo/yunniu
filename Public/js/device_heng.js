//获得横屏模板
	function findTelp(page){
		
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/replaceTemp",
			data:{"template_code":$(page).val(),"restaurant_id":1},
			dataType:"json",
			success:function(data){
				alert("模板获取成功！请选中下方按钮应用获取模板");
				$.ajax({
					type:"post",
					url:"/index.php/admin/Moudle/showallTemp",
					dataType:"json",
					success:function(data){
				var value = data;
				mm = '<div class="col-sm-1 mt-30">横屏</div><div class="col-sm-2"><img src="/public/images/orderPage.png">'+
				'<div class="mt-5"><input type="radio" name="color" value="0"/>红<input type="radio" name="color" value="1"/>蓝<input type="radio" name="color" value="2"/>'+
				'绿</div><div><input type="radio" name="radio1" value = "" checked="checked" onclick = "yinyong(this)">'+
				'<span class="ml-10">模板1</span></div></div>';
				for(var i in value){
					mm += '<div class="col-sm-2"><div style="width: 168px; height: 95px;border:2px solid darkslategrey;position:relative">'+
				'<img src="/'+data[i].order_page_img+'" style = "width:168px;height:95px"><button class="delete-btn" onclick = "delpage1('+data[i].ids+','+0+')">'+
				'<img src="/public/images/delete.png" ></button></div><div class="mt-5">'+
				'模板名称:<span style = "color:red">'+data[i].order_page_name+'<span></div><div>';
							
				if(data[i].replace_status == 1){
					mm += '<input type="radio" name="radio1" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong(this)" checked>订制模板</div></div>';
				}else{
					mm += '<input type="radio" name="radio1" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong(this)">订制模板</div></div>';
				}
				}
				mm += '<div class="col-sm-2"><div style="width: 168px; height: 95px;border:2px solid darkslategrey;">'+
				'<img src="/public/images/themeContact.png" style = "width:168px;height:95px"></div><div class="mt-5">'+
				'<input type="text" placeholder="请输入模板名称" onchange="findTelp(this)" id="telp"></div><div>'+
				'<input type="radio" name="radio1" id = ""  value = "" onclick = "yinyong(this)">订制模板</div></div>';
				$("#row").html(mm);
				
			},
				});
			},
			error:function(data){
				alert("提取码错误或模板已存在");javascript:location.reload();
			}
		});
	}
	
	
	//显示所拥有的横屏模板
	$(function(){
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/showallTemp",
			dataType:"json",
			success:function(data){
				var value = data;
				mm = '<div class="col-sm-1 mt-30">横屏</div><div class="col-sm-2"><img src="/public/images/orderPage.png">'+
				'<div class="mt-5" id = "wrap"><input type="radio" name="color" value = "0" onchange="changecolor()"/>红<input type="radio" name="color" value = "1" onchange="changecolor()"/>蓝<input type="radio" name="color" value = "2" onchange="changecolor()"/>绿'+
				'</div><div><input type="radio" name="radio1" value = "" checked="checked" onclick = "yinyong(this)">'+
				'<span class="ml-10">模板1</span></div></div>';
				for(var i in value){
					mm += '<div class="col-sm-2"><div style="width: 168px; height: 95px;border:2px solid darkslategrey;position:relative">'+
				'<img src="/'+data[i].order_page_img+'" style = "width:168px;height:95px"><button class="delete-btn" onclick = "delpage1('+data[i].ids+','+0+')">'+
				'<img src="/public/images/delete.png" ></button></div><div class="mt-5">'+
				'模板名称:<span style = "color:red">'+data[i].order_page_name+'<span></div><div>';
							
				if(data[i].replace_status == 1){
					mm += '<input type="radio" name="radio1" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong(this)" checked>订制模板</div></div>';
				}else{
					mm += '<input type="radio" name="radio1" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong(this)">订制模板</div></div>';
				}
				}
				mm += '<div class="col-sm-2"><div style="width: 168px; height: 95px;border:2px solid darkslategrey;">'+
				'<img src="/public/images/themeContact.png" style = "width:168px;height:95px"></div><div class="mt-5">'+
				'<input type="text" placeholder="请输入模板名称" onchange="findTelp(this)" id="telp"></div><div>'+
				'<input type="radio" name="radio1" id = ""  value = "" onclick = "yinyong(this)">订制模板</div></div>';
				$("#row").html(mm);
				
				
			}
		});	
		 
		//请求移动端模板显示
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/showphTemp",
			dataType:"json",
			success:function(data){
				var value = data;
				mm = '<div class="col-sm-1 mt-30">竖屏</div><div class="col-sm-2"><img src="/public/images/orderVertical.png">'+
			'<div class="mt-5"><img src="/public/images/themeColor.png"></div><div><input type="radio" name = "radio2" checked="checked">'+
				'<span class="ml-10">模板1</span></div></div>';
				for(var i in value){
					mm += '<div class="col-sm-2"><div style="width: 64px; height: 109px;border:2px solid darkslategrey;position:relative">'+
					'<img src="/'+data[i].order_page_img+'" style = "width:61px; height:107px"><button class="delete-btn" onclick = "delpage1('+data[i].ids+','+1+')">'+
				'<img src="/public/images/delete.png" ></button></div><div class="mt-5">'+
				'模板名称：<span style = "color:red">'+data[i].order_page_name+'</span></div>';
				
				if(data[i].replace_status == 1){
					mm += '<div><input type="radio" name = "radio2"  id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong1(this)" checked>订制模板</div></div></div>';
				}else{
					mm += '<div><input type="radio" name = "radio2" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong1(this)">订制模板</div></div></div>';
				}
				}
				mm += '<div class="col-sm-2"><div style="width: 64px; height: 109px;border:2px solid darkslategrey;">'+
				'<img src="/public/images/themeVertical.png"></div><div class="mt-5"><input type="text" placeholder="请输入模板名称" onchange = "findphTelp(this)"></div>'+
			'<div><input type="radio" name = "radio2" >订制模板</div></div>';
				$('#row1').html(mm);
			}
		});
	});
	
	//应用PC模板
	function yinyong(statu){
		//alert($(statu).val());
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/useTemp",
			data:{"ids":$(statu).attr('id')},
			dataType:"json",
			success:function(data){
				alert("成功应用该模板！");
			}
		});
	}
	
	//应用竖屏模板
	function yinyong1(statu){
		//alert($(statu).val());
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/useTemp1",
			data:{"ids":$(statu).attr('id')},
			dataType:"json",
			success:function(data){
				alert("成功应用该模板！");
			}
		});
	}
	//删除模板
	function delpage1(i,t){
		$msg = "您确定要删除该模板？";
		if(confirm($msg) == true){
			$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/delTemp",
			data:{"id":i,"type":t},
			dataType:"json",
			success:function(data){	
				alert("删除成功！");
				if(data['type'] == "0"){
				$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/showallTemp",
			dataType:"json",
			success:function(data){
				var value = data;
				mm = '<div class="col-sm-1 mt-30">横屏</div><div class="col-sm-2"><img src="/public/images/orderPage.png">'+
				'<div class="mt-5"><img src="/public/images/themeColor.png"></div><div><input type="radio" name="radio1" value = "" checked="checked" onclick = "yinyong(this)">'+
				'<span class="ml-10">模板1</span></div></div>';
				for(var i in value){
					mm += '<div class="col-sm-2"><div style="width: 168px; height: 95px;border:2px solid darkslategrey;position:relative">'+
				'<img src="/'+data[i].order_page_img+'" style = "width:168px;height:95px"><button class="delete-btn" onclick = "delpage1('+data[i].ids+','+0+')">'+
				'<img src="/public/images/delete.png" ></button></div><div class="mt-5">'+
				'模板名称:<span style = "color:red">'+data[i].order_page_name+'<span></div><div>';
							
				if(data[i].replace_status == 1){
					mm += '<input type="radio" name="radio1" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong(this)" checked>订制模板</div></div>';
				}else{
					mm += '<input type="radio" name="radio1" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong(this)">订制模板</div></div>';
				}
				}
				mm += '<div class="col-sm-2"><div style="width: 168px; height: 95px;border:2px solid darkslategrey;">'+
				'<img src="/public/images/themeContact.png" style = "width:168px;height:95px"></div><div class="mt-5">'+
				'<input type="text" placeholder="请输入模板名称" onchange="findTelp(this)" id="telp"></div><div>'+
				'<input type="radio" name="radio1" id = ""  value = "" onclick = "yinyong(this)">订制模板</div></div>';
				$("#row").html(mm);

			}
		});
				}else{
					$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/showphTemp",
			dataType:"json",
			success:function(data){
				var value = data;
				mm = '<div class="col-sm-1 mt-30">竖屏</div><div class="col-sm-2"><img src="/public/images/orderVertical.png">'+
			'<div class="mt-5"><img src="/public/images/themeColor.png"></div><div><input type="radio" name = "radio2" checked="checked">'+
				'<span class="ml-10">模板1</span></div></div>';
				for(var i in value){
					mm += '<div class="col-sm-2"><div style="width: 64px; height: 109px;border:2px solid darkslategrey;position:relative">'+
					'<img src="/'+data[i].order_page_img+'" style = "width:61px; height:107px"><button class="delete-btn" onclick = "delpage1('+data[i].ids+','+1+')">'+
				'<img src="/public/images/delete.png" ></button></div><div class="mt-5">'+
				'模板名称：<span style = "color:red">'+data[i].order_page_name+'</span></div>';
				
				if(data[i].replace_status == 1){
					mm += '<div><input type="radio" name = "radio2"  id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong1(this)" checked>订制模板</div></div></div>';
				}else{
					mm += '<div><input type="radio" name = "radio2" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong1(this)">订制模板</div></div></div>';
				}
				}
				mm += '<div class="col-sm-2"><div style="width: 64px; height: 109px;border:2px solid darkslategrey;">'+
				'<img src="/public/images/themeVertical.png"></div><div class="mt-5"><input type="text" placeholder="请输入模板名称" onchange = "findphTelp(this)"></div>'+
			'<div><input type="radio" name = "radio2" >订制模板</div></div>';
				$('#row1').html(mm);
			}
		});
				}
			}
		});
		}
		
	}
	
	
	
	//获得竖屏模板
	function findphTelp(page){
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/replaceTemp",
			data:{"template_code":$(page).val(),"restaurant_id":1},
			dataType:"json",
			success:function(data){
				//$(page).parent().prev().children().attr("src","/"+data[0].order_page_img);
				alert("模板获取成功！请选中下方按钮应用获取模板");
	$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/showphTemp",
			dataType:"json",
			success:function(data){
				var value = data;
				mm = '<div class="col-sm-1 mt-30">竖屏</div><div class="col-sm-2"><img src="/public/images/orderVertical.png">'+
			'<div class="mt-5"><img src="/public/images/themeColor.png"></div><div><input type="radio" name = "radio2" checked="checked">'+
				'<span class="ml-10">模板1</span></div></div>';
				for(var i in value){
					mm += '<div class="col-sm-2"><div style="width: 64px; height: 109px;border:2px solid darkslategrey;position:relative">'+
					'<img src="/'+data[i].order_page_img+'" style = "width:61px; height:107px"><button class="delete-btn" onclick = "delpage1('+data[i].ids+','+1+')">'+
				'<img src="/public/images/delete.png" ></button></div><div class="mt-5">'+
				'模板名称：<span style = "color:red">'+data[i].order_page_name+'</span></div>';
				
				if(data[i].replace_status == 1){
					mm += '<div><input type="radio" name = "radio2"  id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong1(this)" checked>订制模板</div></div></div>';
				}else{
					mm += '<div><input type="radio" name = "radio2" id = "'+data[i].ids+'"  value = "'+data[i].replace_status+'" onclick = "yinyong1(this)">订制模板</div></div></div>';
				}
				}
				mm += '<div class="col-sm-2"><div style="width: 64px; height: 109px;border:2px solid darkslategrey;">'+
				'<img src="/public/images/themeVertical.png"></div><div class="mt-5"><input type="text" placeholder="请输入模板名称" onchange = "findphTelp(this)"></div>'+
			'<div><input type="radio" name = "radio2" >订制模板</div></div>';
				$('#row1').html(mm);
			}
		});
				
		},
		error:function(data){
				alert("输入模板ID有误或该模板已存在");javascript:location.reload();
			}
		});
		
	}
	
	function changecolor(){
		var id = $('#wrap input[name="color"]:checked').val();
		$.ajax({
			type:"post",
			url:"/index.php/admin/Moudle/changecolor",
			data:{"tplcolor_id":id},
			success:function(data){
				alert("成功");
			},
			error:function(){
				alert("失败");
			}
		});
		
		/*if(val_payPlatform == 1){
			$('#global-css').attr('href','main_blue.css');
		}*/
	}
	