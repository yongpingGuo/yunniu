	/**
	 * 作者：凯
	 * 日期：2017.01.11
	 */
	
	$('.header ul>li').click(function() {
		$(this).addClass('active').siblings().removeClass('active');
	});
	$('.dropdown').mouseover(function(){
		$(this).find('.dropdown-list').stop().slideDown();
	});
	$('.dropdown').mouseout(function(){
		$(this).find('.dropdown-list').stop().slideUp(100);
	});

	$('.header ul>li').click(function() {
		$(this).addClass('active').siblings().removeClass('active');
	});
	function loginout(){
		var msg = confirm("确定要退出？");
		if(msg == true){
			$.ajax({
				type:"get",
				url:"/index.php/agent/Index/loginout",
				async:true,
				dataType:"json",
				success:function(data){
					if(data.code == 1){
						location.href = "/index.php/agent/Index/login";
					}
				}
			});
		}
	}


	//管理员帐号修改
	function modify_manager(i){
		$.ajax({
			type:"get",
			url:"/index.php/Agent/index/account_edit/business_id/"+i+"",
			async:true,
			dataType:"json",
			success:function(data){
				console.log(data);
				$("input[name='manager_id']").val(data.business_id);
				$("input[name='manager_account']").val(data.business_account);
				$("input[name='manager_password']").val(data.business_password);
				$("input[name='manager_passwords']").val(data.business_password);
			}
		});
	}

	function update_account(){
		var manager_account = $("input[name='manager_account']").val();
		var manager_password = $("input[name='manager_password']").val();
		var manager_passwords = $("input[name='manager_passwords']").val();
		if(manager_account && manager_password && manager_passwords){
			if(manager_password == manager_passwords){
				$.ajax({
					type:"post",
					url:"/index.php/Agent/index/update_account",
					async:true,
					data:$("#myform").serialize(),
					success:function(data){
						alert("密码编辑成功！请重新登录!");
						if(data.code == 1){		
							$('#edit-user').modal('hide');
							location.href = "/index.php/Agent/Index/login";
						}
					}
				});
			}else{
				alert("密码不一致");
			}
		}else{
			alert("所显示项不能为空!")
		}
	}