	//获得横竖移动模板
	function findTelp(page){
		var theme_code = $(page).val();
		var tpltype = $(page).data('tpltype');
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/replaceTemp/theme_code/"+theme_code+"/tpltype/"+tpltype,
			dataType:"json",
			success:function(data){
				alert(data.msg);
				if(tpltype != 2){
					$.ajax({
						type:"post",
						url:"/index.php/admin/Moudle/device",
						success:function(data){
							$("#row").html(data);
						},
					});
				}else{
					$.ajax({
						type:"post",
						url:"/index.php/admin/Moudle/device",
						success:function(data){
							$("#row").html(data);
						},
					});
				}
			},
		});
	}

	//横屏选择订制模板
	$('.hengradio').change(function(dom){
		var restaurant_page_group_id = $("input[name='heng']:checked").val();
		console.log(status);
		var tpltype = $("input[name='heng']:checked").data('tpltype');
		console.log(tpltype);
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/useTemp/restaurant_page_group_id/"+restaurant_page_group_id+"/tpltype/"+tpltype,
			dataType:"json",
			success:function(data){
				alert(data.msg);
			}
		});
	});
	
	//竖屏选择定制模板
	$('.shuradio').change(function(){
		var restaurant_page_group_id = $("input[name='shu']:checked").val();
		var tpltype = $("input[name='shu']:checked").data('tpltype');
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/useTemp/restaurant_page_group_id/"+restaurant_page_group_id+"/tpltype/"+tpltype,
			dataType:"json",
			success:function(data){
				alert(data.msg);
			}
		});
	});
	
	//移动端选择定制模板
	$('.tplphone').change(function(){
		var restaurant_page_group_id = $("input[name = 'phone']:checked").val();
		var tpltype = $("input[name='phone']:checked").data('tpltype');
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/useTemp/restaurant_page_group_id/"+restaurant_page_group_id+"/tpltype/"+tpltype,
			dataType:"json",
			success:function(data){
				layer.msg(vm.langData.success[vm.lang]);
			}
		});
	});

	//改变横屏模板的颜色样式
	$('.color1').change(function(){
		var id = $('input[name="color"]:checked').val();
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/changecolor/tplcolor_id/"+id,
			dataType:"json",
			success:function(data){
				layer.msg(vm.langData.success[vm.lang]);
			},
			error:function(){
				layer.msg(vm.langData.failed[vm.lang]);
			}
		});
	});
	
	//改变竖屏模板的颜色样式
	$('.color2').change(function(){
		var id = $('input[name="color_shu"]:checked').val();
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/changecolor1/tplcolor1_id/"+id,
			dataType:"json",
			success:function(data){
				alert(data.msg);
			},
			error:function(){
				layer.msg(vm.langData.failed[vm.lang]);
			}
		});
	});

	//改变移动端的颜色样式
	$('.color3').change(function(){
		var id = $('input[name="color_ph"]:checked').val();
		var parent=$(this).parent();
		$.ajax({
			type:"get",
			url:"/index.php/admin/Moudle/changecolor2/tplcolor2_id/"+id,
			dataType:"json",
			success:function(data){
				layer.msg(vm.langData.success[vm.lang]);
				parent.addClass('active').siblings().removeClass('active');
			},
			error:function(){
				layer.msg(vm.langData.failed[vm.lang]);
			}
		});	
	});


//-----------------------------------------------备份---------------------------------
/*	//删除横竖屏模板
	function delpage1(i){
		$msg = "您确定要删除该模板？";
		if(confirm($msg) == true){
			$.ajax({
				type:"post",
				url:"/index.php/admin/Moudle/delTemp",
				data:{"id":i},
				dataType:"json",
				success:function(data){	
					alert("删除成功！");
					$.ajax({
						type:"post",
						url:"/index.php/admin/Moudle/device",
						//dataType:"json",
						success:function(data){
							$("#row").html(data);
						}
					});
				}
			});
		}
	}*/
	
	/*//删除移动屏模板
	function delpage(i){
		$msg = "您确定要删除该模板？";
		if(confirm($msg) == true){
			$.ajax({
				type:"post",
				url:"/index.php/admin/Moudle/delTemp",
				data:{"id":i},
				dataType:"json",
				success:function(data){	
						$.ajax({
							type:"post",
							url:"/index.php/admin/Moudle/mobile",
							//dataType:"json",
							success:function(data){
								//alert(data);
								$('#row').html(data);
							}
						});
				}	
			});
		}
		
	}*/
	