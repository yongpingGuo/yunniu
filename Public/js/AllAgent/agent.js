	/**
	 * 作者：凯
	 * 日期：2017.01.09
	 */
	
	//新增代理时的模态框相应操作
	function addAgent(){
		$("input[name='form_id']").val(1);
		$('#addAgent').modal('show');
		$("#title").text('新增代理商');
	}

	//模态框的提交(1/新增,2/编辑)
	function commit(){
		var form_id = $("input[name='form_id']").val();							//(1/新增,2/编辑)
		var business_id = $("input[name='business_id']").val();
		var business_name = $("input[name='business_name']").eq(1).val();
		var business_account = $("input[name='business_account']").eq(1).val();
		var business_password = $("input[name='business_password']").val();
		var business_passwords = $("input[name='business_passwords']").val();
		var corporate_name = $("input[name='corporate_name']").val();
		var business_grade = $("#grade").val();
		var business_contact = $("input[name='business_contact']").val();
		var business_phone = $("input[name='business_phone']").val();
		var business_ps = $('#business_ps').val();
		var p = parseInt($('.current').text());
		if(p == 'NaN'){
			p = 1;
		}else{
			p = parseInt($('.current').text());
		}
		if(business_name && business_account && business_password && business_passwords && business_grade && business_contact && business_phone){
			if(business_password == business_passwords){
				if((/^1[34578]\d{9}$/.test(business_phone))){
					if(form_id == 1){
						$.ajax({
							type:"post",
							url:"/index.php/allAgent/Agent/add_business",
							data:{"business_name":business_name,"business_account":business_account,"business_password":business_password,"business_grade":business_grade,
								"corporate_name":corporate_name,"business_contact":business_contact,"business_phone":business_phone,"business_ps":business_ps},
							async:true,
							dataType:"json",
							success:function(data){
								if(data.code == 1){
									$('#addAgent').modal('hide');
									self.location.href = '/index.php/AllAgent/Agent/agent/page/'+data.page+'.html';
								}else{
									layer.msg(data.msg);
								}
							},
							error:function(){
								layer.msg('帐号已存在或出错了!');
							}
						});
					}else{
						$.ajax({
							type:"post",
							url:"/index.php/allAgent/Agent/update_business",
							data:{"business_id":business_id,"business_name":business_name,"business_account":business_account,"business_password":business_password,"business_grade":business_grade,
								"corporate_name":corporate_name,"business_contact":business_contact,"business_phone":business_phone,"business_ps":business_ps},
							async:true,
							dataType:"json",
							success:function(data){
								if(data.code == 1){
									$('#addAgent').modal('hide');
									self.location.href = '/index.php/AllAgent/Agent/agent/page/'+p+'.html';
								}else{
									layer.msg(data.msg);
								}	
							},
							error:function(){
								layer.msg('帐号已存在或出错了!');
							}
						});
					}
				}else{
					layer.msg('手机号格式不确定!');
				}
			}else{
				layer.msg('密码不一致!');
			}
		}else{
			layer.msg('输入错误，星号项不能为空!');
		}
	}

	//删除代理商
	function delinfo(i){
		var msg = confirm("确定要删除？");
		if(msg == true){
			$.ajax({
				type:"get",
				url:"/index.php/allAgent/Agent/del_business/id/"+i,
				async:true,
				dataType:"json",
				success:function(data){
					//$("#mytable").html(data);
					if(data.code == 1){
						self.location.href = '/index.php/AllAgent/Agent/agent/page/'+data.page+'.html';
					}else{
						layer.msg(data.msg);
					}
				}
			});
		}
	}

	//编辑前的表单填充
	function modify(i){
		var session_manager_id = $("#session_manager_id").val();
		$.ajax({
			type:"get",
			url:"/index.php/allAgent/Agent/getManagerRank/manager_id/"+session_manager_id,
			async:true,
			dataType:"json",
			success:function(data){
				if(data.rank != 4){
					$("#addAgent").modal('show');
					$("#title").text('编辑代理商');
					$.ajax({
						type:"get",
						url:"/index.php/allAgent/Agent/modify_business/id/"+i+"",
						async:true,
						dataType:"json",
						success:function(data){
							//console.log(data);
							$("input[name='business_id']").val(data.business_id);
							$("input[name='business_name']").eq(1).val(data.business_name);
							$("input[name='business_account']").eq(1).val(data.business_account);
							$("input[name='business_password']").val(data.business_password);
							$("input[name='business_passwords']").val(data.business_password);
							$("input[name='corporate_name']").val(data.corporate_name);
							$("#grade").val(data.business_grade);
							$("input[name='business_contact']").val(data.business_contact);
							$("input[name='business_phone']").val(data.business_phone);
							$('#business_ps').val(data.business_ps);
							$("input[name='form_id']").val(2);
							$("#business_account").attr("disabled",true);
						}
					});
				}else{
					layer.msg("抱歉，您的权限不够，无法进行此操作!");
				}
			}
		});
	}

	//代理模态框消失后的处理
	$('#addAgent').on('hidden.bs.modal', function(){
		$("input[type='reset']").trigger('click');
		$("#business_account").attr("disabled",false);
	});
