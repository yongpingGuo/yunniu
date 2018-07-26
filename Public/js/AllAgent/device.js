	/**
	 * 作者：凯
	 * 日期：2017。01.10
	 */
	
	/**
	 * 点击代理下的地区，显示该地区下所有店铺
	 * @param {Object} b   代理ID
	 * @param {Object} c   区域ID
	 * @param {Object} object
	 */
	function showinfo(b,c,object){
			var restaurant_name = $(object).prev().attr("value");
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/Device/showInfobykey/business_id/"+b+"/restaurant_name/"+restaurant_name+"/cityid/"+c+"",
				async:true,
				success:function(data){
					$('#uuid').attr('value',b);
					$('#uuid2').attr('value',restaurant_name);
					$('#uuid3').attr('value',c);
					$("#listtable").html(data);
				}
			});
		}
			
		//编辑前的填充
		function modify(i){
			var session_manager_id = $("#session_manager_id").val();
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/Agent/getManagerRank/manager_id/"+session_manager_id,
				async:true,
				dataType:"json",
				success:function(data){
					if(data.rank != 4){
						$("#editDevice").modal("show");
						$.ajax({
							type:"get",
							url:"/index.php/allAgent/Device/modify_device/device_id/"+i+"",
							async:true,
							dataType:"json",
							success:function(data){
								$("input[name='restaurant_id']").val(data.restaurant_id);
								$("input[name='address']").val(data.address);
								$("input[name='device_name']").val(data.device_name);
								$("input[name='start_time']").val(data.start_time);
								$("input[name='end_time']").val(data.end_time);
								$("input[name='device_id']").val(data.device_id);
								if(data.device_status == 1){
									$("input[name='state']:eq(0)").prop("checked",true);
								}else{
									$("input[name='state']:eq(1)").prop("checked",true);
								}
							}
						});
					}else{
						layer.msg("抱歉，您的权限不够，无法进行此操作！");
					}
				}
			});
		}
		//删除设备
		function del(i){
			var session_manager_id = $("#session_manager_id").val();
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/Agent/getManagerRank/manager_id/"+session_manager_id,
				async:true,
				dataType:"json",
				success:function(data){
					if(data.rank == 1 || data.rank == 2){
						var business_id = $("#uuid").val();
						var restaurant_name = $("#uuid2").val();
						var cityid = $("#uuid3").val();
						var msg = confirm("确定要删除？");
						if(msg == true){
							$.ajax({
								type:"post",
								url:"/index.php/allAgent/Device/del_device",
								data:{"id":i,"business_id":business_id,"restaurant_name":restaurant_name,"cityid":cityid},
								async:true,
								success:function(data){
									$("#listtable").html(data);
								},
							});
						}
					}else{
						layer.msg("抱歉，您的权限不够，无法进行此操作！");
					}
				}
			})		
		}
		
		//搜索代理下的相关店铺设备信息
		function search(){
			var value = $("input[name='key']").val();
			if(value){
				$.ajax({
					type:"get",
					url:"/index.php/allAgent/Device/showdevicebykey/key/"+value+"",
					async:true,
					success:function(data){
						$("#mytable").html(data);
					}
				});
			}
		}
		
		//修改设备
		function commit(){
			var data = $("#myform").serialize();
			var name = $("input[name='device_name']").val();
			if(name){
				$.ajax({
					type:"post",
					url:"/index.php/allAgent/Device/update_device",
					async:true,
					data:data,
					success:function(data){			
						$('#editDevice').modal('hide')
						$("#listtable").html(data);
					}
				});
			}else{
				alert("设备名称不为空!");
			}
		}
		
		function search_time(){
			var device_start_time = $("#device_start_time").val();
			var device_end_time = $('#device_end_time').val();
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/Device/searchDevicebyTime/device_start_time/"+device_start_time+"/device_end_time/"+device_end_time,
				async:true,
				success:function(data){
					$("#listtable").html(data);
				}
			});
		}
		
		//ajax分页
		$("#detail-page").children().children("a").click(function(){
			var page = parseInt($(this).data("page"));
			var device_start_time = $('#device_start_time').val();
			var device_end_time = $('#device_end_time').val();
			if(device_start_time && device_end_time != ""){//判断是有查询条件分页还是非查询条件分页
				url = "/index.php/allAgent/Device/searchDevicebyTime/device_start_time/"+device_start_time+"/device_end_time/"+device_end_time+"/page/"+page;
			}else{
				url = "/index.php/allAgent/Device/device_ajax/page/"+page;
			}
			$.ajax({
				type:"get",
				url:url,
				success:function(data){
					$("#listtable").html(data);
				},
				error:function(){
				 	alert("出错了");
				}
			});
		});
		

	