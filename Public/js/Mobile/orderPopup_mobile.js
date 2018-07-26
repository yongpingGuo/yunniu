	//编历模态框里的属性，如果为单选则默认选中第一个
	$(function(){
		$("#attr_form :radio").each(function(index,element){		
			if($(element).data('key') == 1){
				$(this).parent().toggleClass('attr-select');
				$(this).parent().trigger('click');
			}
		})
	})
	
	//模态框加
	function increaseNum(obj){
		var food_data2 = $(obj).data();								//将最初的菜品ID与菜品单价存储到对象中
		var food_num2 = $("#food_num").html();						//模态框显示份数 (变)
		var food_price2 = food_data2['food_price'];					//菜品单价(不含属性)
		var attr_amount2 = food_data2['attr_amount'];				//菜品属性总价
		var new_food_price2 = $("#food_price").html();				//变价、即单种菜品最后的价格(其中含份数与属性的叠加)
		var priceByOne2 = new_food_price2/food_num2;				//单种菜品价格(含属性)      之后以此单价计算
		var last_price2 = Number(new_food_price2)+Number(priceByOne2);		//价格原基础上+1份

		var last_num2 = Number(food_num2)+1;						//份数原基础+1
		last_price2 = last_price2.toFixed(2);						//处理价格,保留小数点两位
		
		$("#food_num").html(last_num2);								//重新填充,份数+1后的价格
		$("#food_price").html(last_price2);							//重新填充,份数+1后的份数
	}
	
	//模态框减
	function decreaseNum(obj){	
		var food_data = $(obj).data();								//将最初的菜品ID与菜品单价存储到对象中
		var food_num = $("#food_num").html(); 						//模态框显示份数 (变)
		var food_price = food_data['food_price'];					//菜品单价(不含属性)
		var attr_amount = food_data['attr_amount'];					//菜品属性总价
		var new_food_price = $("#food_price").html();  				//变价、即单种菜品最后的价格(其中含份数与属性的叠加)
        var priceByOne = new_food_price/food_num;					//单种菜品价格(含属性)      之后以此单价计算
		var last_price = Number(new_food_price)-Number(priceByOne);	//价格原基础上-1份

		if(last_price > 0){											//价格大于0时,其它处理
			last_price = last_price.toFixed(2);						//处理价格,保留小数点两位	
			var last_num = Number(food_num)-1;
			$("#food_num").html(last_num);							//重新填充,份数-1后的份数
			$("#food_price").html(last_price);						//重新填充,份数-1后的价格
		}
	}
	
	//模态框选择属性，价格变化
	function changePrice(){											//每点击一下属性，就会触发重新处理数据
		var attrs = "";
		var food_price = $("#food_price").data("food_price");   	//菜品单价(不含属性)
		var attr_amount_price = 0;									//初始化菜品属性总价为0
		$("#attr_form .attr-select input").each(function(){			//遍历选中属性ID，拼接成字符串
				attrs += $(this).data("fd_at_id")+"-";				
				var attr_price = Number($(this).val());
				attr_amount_price += attr_price;					//遍历选中属性的价格,求合计算属性总价
		});
		var food_num3 = Number($("#food_num").html());				//模态框显示份数 (变)
		var single_price = attr_amount_price+Number(food_price);	//单种菜品价格(含属性)      之后以此单价计算
		var last_price = Number((attr_amount_price+Number(food_price))*food_num3);	//变价、即单种菜品最后的价格(其中含份数与属性的叠加)
		last_price = last_price.toFixed(2);							//处理价格,保留小数点两位	
		$("#food_price").html(last_price);							//重新填充,属性更改后的最终价格
		$("#food-checked").data("single_price",single_price);		//单种菜品价格(含属性)，传递给之后购物车处理
		$("#food-checked").data("attrs",attrs);						//将属性字符串存储，传递给之后后台二次计算价格
	}

	//模态框确定
	function addOrderItem(obj){
		var food_id = $(obj).data("food_id");						//菜品ID
		var single_price = $(obj).data("single_price");				//单个模态框确认之后，最终价格
		var food_num = Number($("#food_num").html());				//模态框显示份数 (变)
		var food_name = $(obj).data("food_name");					//菜品名称
		var food_price = Number($("#food_price").html()).toFixed(2);//处理价格,保留小数点两位	
		var attrs = $(obj).data("attrs");							//属性字符串
		var k = isSameFood(attrs,food_id);							//判断购物车是否有相同的记录
		if(k>=0){													//存在大于0,原记录上加上份数与价格
			var sec = $('#foodlist li').eq(k);
			var old_food_price = Number(sec.data("food_price"));	//原购物车记录的价格
			var mold_food_num = Number(sec.data("food_num"));		//原购物车记录的份数
			var last_food_price = Number(food_price)+old_food_price;//相同菜品记录在购物车最后的叠加价格
			last_food_price = last_food_price.toFixed(2);			//处理价格,保留小数点两位	
			var last_food_num = food_num+mold_food_num;				//相同菜品记录在购物车最后的叠加份数
			sec.data("food_price",last_food_price);					//将叠加后价格存诸，之后处理
			sec.data("food_num",last_food_num);						//将叠加后份数存诸，之后处理
			
			sec.children().find('span:eq(0)').html(last_food_num);	//叠加后的份数重新填充
			sec.children().find('b').html(last_food_price);			//叠加后的价格重新填充
			sec.children().find('span:eq(1)').data('food_price',last_food_price);
		}else {														//不存在新建购物车记录
			var str = '<li class="cart-item"  data-food_id="' + food_id + '" data-attrs="' + attrs + '" data-food_price="' + food_price + '" data-food_num="' + food_num + '">'
					+ '<div class="cart-left">' + food_name + '</div>'
					+ '<div class="cart-right">'
					+ '<button class="btn-none" onclick = "minus(this)" data-food_id="' + food_id + '" data-single_price="' + single_price + '">'
					+ '<img src="/Public/images/minus_btn.png">'
					+ '</button>'
					+ '<span data-food_id="' + food_id + '" data-single_price="' + single_price + '">' + food_num + '</span>'
					+ '<button class="btn-none" onclick = "plus(this)" data-food_id="' + food_id + '" data-single_price="' + single_price + '">'
					+ '<img src="/Public/images/plus_mobile.png">'
					+ '</button>'
					+ '<span class="red" data-food_price="' + food_price + '">&yen;' + '<b>'+food_price +'</b>'+'元</span>'
					+ '</div>'
					+ '</li>';
			$('#foodlist').append(str);
		}
		countTotal();
	}
	
	//模态框确定时，判断右侧购物车是否已存在该商品，存在：相同记录上叠加份数与价格，不存在，新建购物车记录
	function isSameFood(attr,food_id){
		var l_k = -1;									//默认小于0
		$('#foodlist li').each(function(k,v){
			var t = $(this).data("attrs");
			var t_id = $(this).data("food_id");
			if(t == attr && t_id == food_id){			//判断菜品ID与属性字符串
				l_k = k;
			}
		});
		return l_k;
	}
	

	//购物车栏内份数加
	function plus(obj){
		var single_price = Number($(obj).data("single_price"));				//单种菜品价格(含属性)
		var nowPrice = Number($(obj).next().data("food_price"));			//购物车份数未+1时的价格
		var nowNum = Number($(obj).prev().html());							//购物车份数未+1时的份数

		var lastNum = nowNum+1;												//原基础上份数+1
		var lastPrice = nowPrice+single_price;								//原基础上价格+1份
		lastPrice = lastPrice.toFixed(2);

		$(obj).parent().find("span:eq(0)").html(lastNum);					//重新填充份数
		$(obj).parents('li').children().find('b').html(lastPrice)		  	//重新填充价格
		$(obj).parents('li').find('span:eq(1)').data("food_price",lastPrice);	//操作后将余数返回赋值,为一下操作准备
		$(obj).parents('li').data("food_price",lastPrice);
		$(obj).parents('li').data("food_num",lastNum);
		countTotal();
	}
	
	//购物车栏内份数减
	function minus(obj){
		var single_price = Number($(obj).data("single_price"));				  //单种菜品价格(含属性)
		var nowPrice = Number($(obj).next().next().next().data('food_price'));//购物车份数未-1时的价格
		var nowNum = Number($(obj).next().html());							  //购物车份数未-1时的份数
		var lastNum = nowNum-1;
		var lastPrice = nowPrice-single_price;								  //原基础上价格-1份

		lastPrice = lastPrice.toFixed(2);
		if(lastNum > 0){			
			$(obj).next().html(lastNum);									  //重新填充份数	
			$(obj).parents('li').children().find('b').html(lastPrice)		  //重新填充价格
			$(obj).parents('li').find('span:eq(1)').data("food_price",lastPrice);	//操作后将余数返回赋值,为一下操作准备
			$(obj).parents('li').data("food_price",lastPrice);				  //存储每条购物车记录价格，总价时需遍历此值
			$(obj).parents('li').data("food_num",lastNum);					  
		}else{	
			$(obj).parent().prev().parent().remove();						  //移除购物车记录
		}
		countTotal();
	}
	
	//合计总数
	function countTotal(){
		var total = 0;
		$('#foodlist li').each(function(){
			var t = Number($(this).data("food_price"));						//购物车每条记录的价格
			//console.log(t);
			total += t;
		});
		total = total.toFixed(2);
		$("#Total").html(total);											//总价填充
		
		var column_num = parseInt($("#foodlist").children('li').length);	//购物车内菜品栏数	
		$("#numv").html(column_num);
	}
	
	//清空购物车
	function clearorder(){
		$('#foodlist').children('li').remove();
		$("#numv").html(0);
		$("#Total").html("0.00");
		
	}
	
	
	//模态框选中样式
	$('#food-checked').click(function(){
			//console.log("1111");
			// var flyElm = '<div class="flyElm"></div>';
			// $('body').append(flyElm);
			// $('.flyElm').append($('.food-modal-content').clone());
			// $('.flyElm').css({
			// 	'background-color':'red',
			// 	'z-index':'3000',
			// 	'width':$('.food-list').width()+'px',
			// 	'height':$('.food-list').width()+'px',
			// 	'position':'absolute',
			// 	'top':$('.food-modal-content').offset().top+'px',
			// 	'left':$('.food-modal-content').offset().left+'px',
			// 	'border-radius':'50%'
			// });
			// $('.flyElm').animate({
			// 	top:$('.food-select').offset().top +$('.food-select').height() +'px',
			// 	left:$('.food-select').offset().left +$('.food-select').width() +'px',
			// 	width:'50px',
			// 	height:'50px'
			// },'slow');
			$('#foodModal').modal('hide');
//				$('.food-select').append($('.food-select-item:first-child').clone());
		})
		// $('#foodModal').on('hidden.bs.modal', function () {
		// 	$('.flyElm').remove();
		// })
	
//		$('#first li input').click(function(){
//			$(this).parent().addClass('attr-select');
//			$(this).parent().siblings().removeClass('attr-select');
//		});
//		$('#second li input').click(function(){
//			$(this).parent().addClass('attr-select');
//			$(this).parent().siblings().removeClass('attr-select');
//		});
//		$('#third li input').click(function(){
//			$(this).parent().toggleClass('attr-select');
//		});

		$('#first li input').click(function(){
			$(this).parent().addClass('attr-select');
			$(this).parent().siblings().removeClass('attr-select');
		});
		
		
		$('#third li input').click(function(){
			$(this).parent().toggleClass('attr-select');
		});
