	//点击菜品分类，显示对应菜品
	function showtypefood(i){
		$.ajax({
			type:"get",
			url:"/index.php/home/index/showtypefood/type/"+i+"",
			success:function(data){
				$("#food_info").html(data);
			}
		});
	}

	//点击菜品，显示菜品的详细信息
	function findfoodinfo(obj){
		var i = $(obj).data('food_id');
		$.ajax({
			type:"get",
			url:"/index.php/home/index/findfoodinfo/food_id/"+i,
			success:function(data){
				$("#modelfood").html(data);//加载模态框
			}
		});
	}

	//点击下单
	function PlaceOrder2(){
		var total = Number($("#Total").html()).toFixed(2);	
		var isOpenNum = $("#isOpenNum").val();				//获取餐牌功能的工作状态
		if(isOpenNum != 0 && total > 0){					//购物车内有菜品且开启餐牌页面		
			$("#tableModal").modal("show");					//如果则弹出模态框
		}else{
			placeor();
		}
	}

	function placeor(){
		var total = Number($("#Total").html()).toFixed(2);
		var isOpenNum = $("#isOpenNum").val();
		if(isOpenNum != 0 && total > 0){				//购物车内有菜品且开启餐牌页面
			var tableNum = Number($("#numtext").val());
			if(!tableNum || tableNum == 0){
				layer.msg('餐牌号不能为空或者为0，谢谢',{
					title: false,
					closeBtn: 0,
					shadeClose: true,
					skin: 'layer-class',
					area: '60%',
					time: 1000
				});
				return
			}

			$.ajax({
				url:"/index.php/home/index/setTableNum",
				type:'post',
				data:{"tableNum":tableNum},
				dataType:"json",
				async:false,
				success:function(msg){
					if(msg.code == 1){
						console.log(1111);
					}
				},
				error:function(){
					alert("出错了");
				}
			});
		}
		if (total <= 0) {
			console.log(total);
			layer.msg('请先选择菜品，谢谢',{
				title: false,
				closeBtn: 0,
				shadeClose: true,
				skin: 'layer-class',
				area: '60%',
				time: 1000
			});
		}else{
			var list = {};
			$('#foodlist section').each(function(k,v){
				var temp = [];
				temp["0"] = $(this).data("food_id");
				temp["1"] = $(this).data("food_num");
				temp["2"] = $(this).data("attrs");
				list[k] = temp;
			});
			console.log(list);
			$.ajax({
				type:"post",
				url:"/index.php/home/index/PlaceOrder",
				data:list,
				dataType:'json',
				async:false,
				success:function (data){
					if(data.code == 1){
						var order_sn = data.data['order_sn'];
						var Total = $("#Total").html();
						window.location.href = "/index.php/home/index/processRoute/process/order/price/" + Total + "/order_sn/" + order_sn;
					}
				},
				error: function(){
					alert("there is a error!");
				}
			});
		}
	}
