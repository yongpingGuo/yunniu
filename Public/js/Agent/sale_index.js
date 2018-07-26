	$(function(){
		var form = $("#search_form")[0];
		var formData = new FormData(form);
		$.ajax({
			url:'/index.php/agent/sale/orderInfo',
			data:formData,
			type:"post",
			contentType:false,
			processData:false,
			async:false,
			cache:false,
			success:function(data){
				$("#orderInfo").html(data);
				putData();
			}
		});
	});

	//点击搜索执行不同查询方菜，temp 1:店铺营业额 2：某菜品的销量
	function submit_form(){
		var form = $("#search_form")[0];
		url = "/index.php/agent/sale/orderInfo";
		var temp = $("input[name='sortType']:checked").val();
		if($("#food_name").val() != "" && temp == 2){
			url = "/index.php/agent/sale/countFoodSale";
		}
		var formDate = new FormData(form);
		$.ajax({
			url:url,
			data:formDate,
			type:"post",
			contentType:false,
			processData:false,
			success:function(data){
				console.log(data);
				$("#orderInfo").html(data);
				putData();
			},
			error:function(){
				alert("出错了");
			}
		});
	}

	function select_all_pay(obj){
		var value = $(obj).val();
		if(value == "off"){
			$(obj).val("on");
			var t1 = $(obj).parent().parent().find("li");
			$.each(t1,function(k1,v1){
				$(this).children().prop("checked","checked");
			});
		}else{
			$(obj).val("off");
			var t2 = $(obj).parent().parent().find("li");
			$.each(t2,function(k2,v2){
				$(this).children().prop("checked",false);
			});
		}
	}

	function select_all_order(obj){
		var value = $(obj).val();
		if(value == "off"){
			$(obj).val("on");
			var t1 = $(obj).parent().parent().find("li");
			$.each(t1,function(k1,v1){
				$(this).children().prop("checked",true);
			});
		}else{
			$(obj).val("off");
			var t2 = $(obj).parent().parent().find("li");
			$.each(t2,function(k2,v2){
				$(this).children().prop("checked",false);
			});
		}
	}

	function putData(){
		//修改统计结果
		var startDate = $("#startDate").val();
		var endDate = $("#endtDate").val();
		$("#search_data").html(startDate+" - "+endDate);

		var food_name = $("#food_name").val();
		if(food_name){
			$("#search_food").html("菜品:"+food_name);
		}else{
			$("#search_food").val("所有");
		}

		var pay_type = $("#pay_str").val();
		console.log(pay_type);
		if(pay_type == ""){
			$("#search_pay_type").html("支付方式：所有");
		}else{
			$("#search_pay_type").html("支付方式："+pay_type);
		}

		var order_type = $("#order_str").val();
		console.log(order_type);
		if(order_type == ""){
			$("#search_order_type").html("就餐方式：所有");
		}else{
			$("#search_order_type").html("就餐方式："+order_type);
		}

		var restaurant_name = $("#restaurant_n").val();
		if(restaurant_name == ""){
			$("#restaurant_name").html("店铺名称：所有");
		}else{
			$("#restaurant_name").html("店铺名称："+restaurant_name);
		}

		var search_total_amount = $("#total_amount").val();
		$("#search_total_amount").html(search_total_amount+"元");
	}
	
	//不同的搜索结果，执行不同的导出方法
	function exportway(){
		var value = $("input[name='sortType']:checked").val();
		if(value == 1){
			$("#search_form").attr('action','/index.php/agent/Sale/exportExcel');
		}else{
			$("#search_form").attr('action','/index.php/agent/Sale/exportExcel1');
		}
		$("#search_form").submit();
	}
	
/*	//ajax分页
	$("#detail-page").children().children("a").click(function(){
	var page = parseInt($(this).data("page"));
		$.ajax({
			type:"get",
			url:"/index.php/agent/Sale/orderInfo/page/"+page,	
			success:function(data){
				$("#orderInfo").html(data);
			},
			error:function(){
				alert("出错了");
			  }
		   });
    });*/