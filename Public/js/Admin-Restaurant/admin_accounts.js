	$("#addwindow").click(function(){
		$("input[type=reset]").trigger("click");
		$("input[name='commit_way']").val(0);
		$('#addRole').modal('show');
		$("input[name='Cashier_phone']").attr("disabled",false);	
	});

	$('#commit').click(function(){
		var Cashier_name = $("input[name='Cashier_name']").val();
		var Cashier_phone = $("input[name='Cashier_phone']").val();
		var Cashier_pwd = $("input[name='Cashier_pwd']").val();
		var Cashier_pwds = $("input[name='Cashier_pwds']").val();
		var commit_way = $("input[name='commit_way']").val();
		var reg = new RegExp("^[0-9]*$");  
		if(Cashier_name && Cashier_phone && Cashier_pwd && Cashier_pwds){
			if(reg.test(Cashier_phone) && reg.test(Cashier_pwd) && reg.test(Cashier_pwds)){
				if(Cashier_pwd == Cashier_pwds){
					if(commit_way == 0){
						$.ajax({
							type:"post",
							url:"/index.php/admin/Accounts/Accountsadd",
							data:$("#myform").serialize(),
							success:function(data){
								$("#mytable").html(data);
								$('#addRole').modal('hide');
							},
							error:function(){
								layer.msg(vm.langData.error[vm.lang]);
							}
						});
					}else{
						$.ajax({
							type:"post",
							url:"/index.php/admin/Accounts/Accountsupdata",
							data:$("#myform").serialize(),
							success:function(data){
								$("#mytable").html(data);
								$('#addRole').modal('hide');
								$("input[type=reset]").trigger("click");
							},
							error:function(){
								layer.msg(vm.langData.error[vm.lang]);
							}
						});
					}
				}else{
					layer.msg(vm.langData.psdMatch[vm.lang]);
				}
				}else{
					layer.msg(vm.langData.wrongFormat[vm.lang]);
				}
		}else{
			layer.msg(vm.langData.asteriskWarn[vm.lang]);
		}		 
		});
		
	//删除
	function del(a){
		var msg = vm.langData.deleteConfirm[vm.lang];
		if(confirm(msg) == true){
			$.ajax({
				type:"post",
				url:"/index.php/admin/Accounts/Accountsdel",
				data:{"Cashier_id":a},
				success:function(data){
					layer.msg(vm.langData.successfullyDeleted[vm.lang]);
					$("#mytable").html(data);
				}
			});
		}		
	}	
		
	//编辑前的填充
	function modify(i){
		$("input[name='Cashier_phone']").attr("disabled",true);	
		$("input[name='commit_way']").val(1);
		$.ajax({
			type:"post",
			url:"/index.php/admin/Accounts/Accountsmodify",
			data:{"Cashier_id":i},
			dataType:"json",
			success:function(data){
			$("input[name='Cashier_id']").val(data.cashier_id);
			$("input[name='Cashier_name']").val(data.cashier_name);
			$("input[name='Cashier_pwd']").val(data.cashier_pwd);
			$("input[name='Cashier_pwds']").val(data.cashier_pwd);
			$("input[name='Cashier_phone']").val(data.cashier_phone);
			$("input[name='Cashier_address']").html(data.cashier_address);
			if(data.cashier_sex == 1){
				$("input[name='Cashier_sex']:eq(0)").prop("checked",true);
			}else{
				$("input[name='Cashier_sex']:eq(1)").prop("checked",true);
			}
			}
		});	
	}
	
	//模糊查询		
	$("#sel").click(function(){
		$.ajax({
			type:"post",
			url:"/index.php/admin/Accounts/selectBykey",
			data:{"key":$("#key").val()},
			success:function(data){
				$("#mytable").html(data);
			}
		});
	});