	/**
	 * 作者：凯
	 * 日期：2017.01.10
	 */
	$('.sidebar-list button').click(function(){
			$(this).siblings('ul').slideToggle();
	});
		
	function showinfo(i){//显示对应店铺的设备列表	
		$("#uuid").val(i);
		$.ajax({
			type:"get",
			url:"/index.php/agent/Device/showajaxinfo/restaurant_id/"+i+"",
			async:true,
			success:function(data){
				$("#mytable").html(data);			
			},
			error:function(){
				layer.msg('出错了');
			}
		});
	}
	
	//编辑设备前的表单填充
	function modify(i){
		$.ajax({
			type:"get",
			url:"/index.php/Agent/Device/modify_device/device_id/"+i+"",
			async:true,
			dataType:"json",
			success:function(data){
				console.log(data);
				$("input[name='restaurant_id']").val(data.restaurant_id);
				$("input[name='device_name']").val(data.device_name);
				$("input[name='device_id']").val(data.device_id);
				if(data.device_status == 1){
					$("input[name='state']:eq(0)").prop("checked",true);
				}else{
					$("input[name='state']:eq(1)").prop("checked",true);
				}
			}
		});
	}
			
	//提交编辑表单		
	function commit(){
		var uuid = $("#uuid").val();//区分是在一开始编辑设备，还是点击餐厅进入编辑设备
		var id = $("input[name='device_id']").val();
		var name = $("input[name='device_name']").val();
		var state = $("input[name='state']:checked").val();
		var page = $(".current").data('page');
		var restaurant_id = $("input[name='restaurant_id']").val();
		if(page == undefined){
			page = 1;
		}
		if(name){
			$.ajax({
				type:"post",
				url:"/index.php/Agent/Device/update_device",
				async:true,
				data:{"id":id,"name":name,"state":state,"uuid":uuid,"restaurant_id":restaurant_id},
				success:function(data){			
					$('#editDevice').modal('hide')
					$("#mytable").html(data);
				}
			});
		}else{
			alert("设备名称不为空!");
		}
	}
		
	function del(i){//删除设备记录
		var uuid = $("#uuid").val();
		var msg = confirm("确定要删除？");
		if(msg == true){
			$.ajax({
				type:"post",
				url:"/index.php/Agent/Device/del_device",
				data:{"id":i,"uuid":uuid},
				async:true,
				success:function(data){
					$("#mytable").html(data);
				}
			});
		}
	}
		
	function search(){//模糊查询
		var value = $("input[name='key']").val();
		$.ajax({
			type:"get",
			url:"/index.php/agent/Device/showdevicebykey/key/"+value+"",
			async:true,
			success:function(data){
				$("#device_left").html(data);
			}
		});
	}

	$("#detail-page").children().children("a").click(function(){
	var page = parseInt($(this).data("page"));
		$.ajax({
			url:"/index.php/agent/Device/device_ajax/page/"+page,
			type:"get",
			success:function(data){
				$("#mytable").html(data);
			},
			error:function(){
				alert("出错了");
			  }
		   });
    });
    
	//更改机器（设备）绑定的店铺
	function changeBindRes(obj){   
	    var code_id = $(obj).data("code_id"); //获取要换绑的机器的注册码id   
	    var restaurant_id = $(obj).val();	//获取要换绑的店铺id
	    $.ajax({
	        url:"/index.php/agent/Device/changeBindRes",
	        data:{"code_id":code_id,"restaurant_id":restaurant_id},
	        type:'post',
	        dataType:"json",
	        success:function(msg){
	            if(msg.resultCode == 1){
	                layer.msg(msg.msg);
	                self.location.href = '/index.php/agent/Device/device';
	            }
	        },
	        error:function(){
	            console.log("访问出错");
	        },
	    });
	}
    
    
	
