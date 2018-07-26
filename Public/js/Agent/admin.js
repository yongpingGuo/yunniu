$('#commit').click(function(){
		var commit_way = $("input[name='commit_way']").val();
		var manager_name = $("input[name='manager_name']").val();
		var manager_account = $("input[name='manager_account']").val();
		var manager_password = $("input[name='manager_password']").val();
		var manager_passwords = $("input[name='manager_passwords']").val();
		var manager_phone = $("input[name='manager_phone']").val();
		var manager_ps = $("#manager_ps").val();
		var p = parseInt($('.current').text());
		text = $("input:checkbox[name='business_power']:checked").map(function(index,elem) {
			return $(elem).val();
		}).get().join(',');

		text1 = $("input:checkbox[name='device_power']:checked").map(function(index,elem) {
			return $(elem).val();
		}).get().join(',');

		text2 = $("input:checkbox[name='admin_power']:checked").map(function(index,elem) {
			return $(elem).val();
		}).get().join(',');

		if(manager_name && manager_account && manager_password && manager_passwords && manager_phone){
			if(manager_password === manager_passwords){
				if(commit_way == 0){
					$.ajax({
						type:"post",
						url:"/index.php/agent/Admin/add_admin",
						async:true,
						data:{
							"manager_name":manager_name,
							"manager_account":manager_account,
							"manager_password":manager_password,
							"manager_phone":manager_phone,
							"manager_ps":manager_ps,
							"text":text,
							"text1":text1,
							"text2":text2,
							"page":p

						},
						success:function(data){
							if(data == 1){
								alert("该帐户已存在");
							}else if(data == 2){
								alert("新增失败");
							}else{
								$("#mytable").html(data);
								$('#addAdmin').modal('hide');
							}
						},
					});
				}else{
					var manager_id = $("input[name='manager_id']").val();
					$.ajax({
						type:"post",
						url:"/index.php/agent/Admin/edit_admin",
						async:true,
						data:{
							"manager_name":manager_name,
							"manager_id":manager_id,
							"manager_account":manager_account,
							"manager_password":manager_password,
							"manager_phone":manager_phone,
							"manager_ps":manager_ps,
							"text":text,
							"text1":text1,
							"text2":text2,
							"page":p

						},
						success:function(data){
							$("#mytable").html(data);
							$('#addAdmin').modal('hide')
						}
					});
				}
			}else{
				alert("密码不一致!");
			}
		}else{
			alert("不能为空");
		}

	});


	function del_admin(i){
		var p = parseInt($('.current').text());
		var msg = confirm("确定要删除吗？");
		if(msg == true){
			$.ajax({
				type:"get",
				url:"/index.php/agent/Admin/del_admin/manager_id/"+i+"/page/"+p+"",
				async:true,
				success:function(data){
					$("#mytable").html(data);
				},
				error:function(){
					alert("错误");
				}
			});
		}
	}

	function modify_admin(i){
		$("input[name='commit_way']").attr('value',1);
		$.ajax({
			type:"get",
			url:"/index.php/agent/Admin/modify_admin/manager_id/"+i+"",
			async:true,
			dataType:"json",
			success:function(data){
				$("input[name='manager_name']").val(data.business_name);
				$("input[name='manager_account']").val(data.business_account);
				$("input[name='manager_password']").val(data.business_password);
				$("input[name='manager_passwords']").val(data.business_password);
				$("input[name='manager_phone']").val(data.business_phone);
				$("#manager_ps").val(data.business_ps);
				$("input[name='manager_id']").val(data.business_id);
				var str  = data.business_power;
				var str1 = data.device_power;
				var str2 = data.admin_power;
				$(str.split(",")).each(function (i,dom){
					$(":checkbox[value='"+dom+"']").prop("checked",true);
				});
				$(str1.split(",")).each(function (i,dom){
					$(":checkbox[value='"+dom+"']").prop("checked",true);
				});
				$(str2.split(",")).each(function (i,dom){
					$(":checkbox[value='"+dom+"']").prop("checked",true);
				});

			}
		});
	}


	$('#addAdmin').on('hidden.bs.modal', function (){
		$("input[type='reset']").trigger('click');
	});

	function showmodel(){
		$("input[name='commit_way']").attr("value",0);
		$('#addAdmin').modal('show')
	}

	//ajax分页
	$("#detail-page").children().children("a").click(function(){
		var page = parseInt($(this).data("page"));
		console.log(page);
		$.ajax({
			url:"/index.php/agent/Admin/common",
			data:{"page":page},
			type:"get",
			success:function(data){
				$("#mytable").html(data);
			},
			error:function(){
			 alert("出错了");
			}
		});
	});