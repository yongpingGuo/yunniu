	$('#commit').click(function(){	
		var commit_way = $("input[name='commit_way']").val();
		var manager_account = $("input[name='manager_account']").val();
		var manager_password = $("input[name='manager_password']").val();
		var manager_passwords = $("input[name='manager_passwords']").val();
		var manager_phone = $("input[name='manager_phone']").val();
		var manager_ps = $("#manager_ps").val();
   		var group_id = $("#group_id").val();
        if(manager_account && manager_password && manager_passwords && manager_phone){
			if(manager_password === manager_passwords){
				if((/^1[34578]\d{9}$/.test(manager_phone))){
					if(commit_way == 0){
						$.ajax({
							type:"post",
							url:"/index.php/allAgent/Admin/add_admin",
							async:true,
							data:{"manager_account":manager_account,"manager_password":manager_password,"manager_phone":manager_phone,
							"manager_ps":manager_ps,"group_id":group_id},
							dataType:"json",
							success:function(data){
								if(data.code == 1){
									$('#addAdmin').modal('hide');
									self.location.href = "/index.php/allAgent/admin/admin/page/"+data.page+".html";
								}else{
									layer.msg(data.msg);
								}
							},
							error:function(data){
								layer.msg("帐号已存在！或网络错误");
							}
						});
					}else{
						var manager_id = $("input[name='manager_id']").val();
						var p = parseInt($('.current').text());
						$.ajax({
							type:"post",
							url:"/index.php/allAgent/Admin/edit_admin",
							async:true,
							data:{"manager_id":manager_id,"manager_account":manager_account,"manager_password":manager_password,
							"manager_phone":manager_phone,"manager_ps":manager_ps,"group_id":group_id},
							dataType:"json",
							success:function(data){
								if(data.code == 1){
									$('#addAdmin').modal('hide');
									self.location.href = "/index.php/allAgent/admin/admin/page/"+p+".html";
								}else{
									layer.msg("编辑失败！");
								}	
							}
						});
					}
				}else{
					layer.msg("手机号码格式错误!");
				}
			}else{
				layer.msg("密码不一致!");
			}
		}else{
			layer.msg("星号项不能为空!");
		}    
	});
		
	//删除管理员
	function del_admin(i){
		var msg = confirm("确定要删除吗？");
		if(msg == true){
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/Admin/del_admin/manager_id/"+i,
				async:true,
				dataType:"json",
				success:function(data){
					if(data.code == 1){
						self.location.href = "/index.php/allAgent/admin/admin/page"+data.page+".html";
					}else{
						layer.msg(data.msg);
					}
				}
			});
		}
	}
	
	//编辑前的表单填充
	function modify_admin(i){
		$("#admintext").text('编辑管理员');
		$("input[name='commit_way']").attr('value',1);
		$("input[name='manager_account']").attr('disabled',true);
		$.ajax({
				type:"get",
				url:"/index.php/allAgent/Admin/modify_admin/manager_id/"+i,
				async:true,
				dataType:"json",
				success:function(data){
					console.log(data);
					$("input[name='manager_id']").val(data.manager_id);
					$("input[name='manager_account']").val(data.manager_account);
					$("input[name='manager_password']").val(data.manager_password);
					$("input[name='manager_passwords']").val(data.manager_password);
					$("input[name='manager_phone']").val(data.manager_phone);
					$("#manager_ps").val(data.manager_ps);
					$("#group_id").val(data.group_id);
					if(data.session_group_id != 1){
						$("#admin_group").remove();
					}
				}
			});
	}
	
	//新增模态框消失时重置表单
	$('#addAdmin').on('hidden.bs.modal', function(){
		$("#admintext").text('新增管理员');
		$("input[name='manager_account']").attr('disabled',false);
  		$("input[type='reset']").trigger('click');
		});
		
	//点击新增，模态框提交方式改为0,   0:新增、1：编辑
	function showmodel(){
		$("input[name='commit_way']").attr("value",0);
		$('#addAdmin').modal('show')
	}
	
