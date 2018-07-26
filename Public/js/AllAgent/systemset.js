	$('.set-sidebar li').click(function () {
			$(this).siblings().removeClass('active');
			$(this).addClass('active');
		});
		
	//显示设备年限
	function show_renew(){
		$.ajax({
			type:"get",
			url:"/index.php/allAgent/Systemset/show_renew",
			async:true,
			success:function(data){
				$("#show_right").html(data);
			}
		});
	}
		
	//修改设备年限
	function update_renew(){
		var renew_time1 = $("input[name='renew_time1']").val();
		var renew_time2 = $("input[name='renew_time2']").val();
		var renew_time3 = $("input[name='renew_time3']").val();
		if(renew_time1 && renew_time2 && renew_time3){
			$.ajax({
				type:"post",
				url:"/index.php/allAgent/Systemset/update_renew",
				data:{"renew_time1":renew_time1,"renew_time2":renew_time2,"renew_time3":renew_time3},
				async:true,
				success:function(data){
					$("#show_right").html(data);
					layer.msg("编辑成功!");
				},
				error:function(){
					layer.msg("出错了!");
				}
			});
		}else{
			layer.msg("不能为空!");
		}	
	}