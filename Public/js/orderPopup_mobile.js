	$(function(){
		$("#attr_form :radio").each(function(index,element){		
			if($(element).data('key') == 1){
				$(this).parent().toggleClass('attr-select');
				$(this).parent().trigger('click');
			}
		})
	})
	//模态框减
	function decreaseNum(obj){
		var food_data = $(obj).data();
		console.log(food_data);
		var food_num = $("#food_num").html();  //份数
		console.log(food_num);
		var food_price = food_data['food_price'];
		var attr_amount = food_data['attr_amount'];
		var new_food_price = $("#food_price").html();  //变价
		console.log(new_food_price);
        var priceByOne = new_food_price/food_num;
		var last_price = Number(new_food_price)-Number(priceByOne);
		console.log(last_price);
		if(last_price > 0){
			last_price = last_price.toFixed(2);
			$("#food_price").html(last_price);
			var last_num = Number(food_num)-1;
			$("#food_num").html(last_num);
		}
	}
	
	//模态框加
	function increaseNum(obj){
		var food_data2 = $(obj).data();
		console.log(food_data2);
		var food_num2 = $("#food_num").html();
		var food_price2 = food_data2['food_price'];
		var attr_amount2 = food_data2['attr_amount'];
		var new_food_price2 = $("#food_price").html();
		var priceByOne2 = new_food_price2/food_num2;
		var last_price2 = Number(new_food_price2)+Number(priceByOne2);

		var last_num2 = Number(food_num2)+1;
		last_price2 = last_price2.toFixed(2);
		$("#food_price").html(last_price2);
		$("#food_num").html(last_num2);
	}
	
	//模态框选择属性，价格变化
	function changePrice(){
		var attrs = "";
		var food_price = $("#food_price").data("food_price");   //菜品原始单价
		var attr_amount_price = 0;
		$("#attr_form .attr-select input").each(function(){
				attrs += $(this).data("fd_at_id")+"-";
				var attr_price = Number($(this).val());
				console.log(attr_price);
				attr_amount_price += attr_price;
		});
		var food_num3 = Number($("#food_num").html());
		var single_price = attr_amount_price+Number(food_price);
		var last_price = Number((attr_amount_price+Number(food_price))*food_num3);
		last_price = last_price.toFixed(2);
		$("#food_price").html(last_price);
		$("#food-checked").data("single_price",single_price);
		console.log(attrs);
		$("#food-checked").data("attrs",attrs);

	}

	//模态框确定
	function addOrderItem(obj){
		var food_id = $(obj).data("food_id");
		var single_price = $(obj).data("single_price");
		var food_num = Number($("#food_num").html());
		var food_name = $(obj).data("food_name");
		var food_price = Number($("#food_price").html()).toFixed(2);
		var attrs = $(obj).data("attrs");
		var k = isSameFood(attrs,food_id);
		if(k>=0){
			var sec = $('#foodlist li').eq(k);
			var old_food_price = Number(sec.data("food_price"));
			var mold_food_num = Number(sec.data("food_num"));
			var last_food_price = Number(food_price)+old_food_price;
			last_food_price = last_food_price.toFixed(2);
			var last_food_num = food_num+mold_food_num;
			sec.data("food_price",last_food_price);
			sec.data("food_num",last_food_num);
			sec.children().find('b').html(last_food_price);
			sec.children().find('span:eq(0)').html(last_food_num);
		}else {
			var str = '<li class="cart-item"  data-food_id="' + food_id + '" data-attrs="' + attrs + '" data-food_price="' + food_price + '" data-food_num="' + food_num + '">'
					+ '<div class="cart-left">' + food_name + '</div>'
					+ '<div class="cart-right">'
					+ '<button class="btn-none" onclick = "minus(this)" data-food_id="' + food_id + '" data-single_price="' + single_price + '">'
					+ '<img src="__PUBLIC__/images/minus_btn.png">'
					+ '</button>'
					+ '<span data-food_id="' + food_id + '" data-single_price="' + single_price + '">' + food_num + '</span>'
					+ '<button class="btn-none" onclick = "plus(this)" data-food_id="' + food_id + '" data-single_price="' + single_price + '">'
					+ '<img src="__PUBLIC__/images/plus_mobile.png">'
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
		var l_k = -1;
		$('#foodlist li').each(function(k,v){
			var t = $(this).data("attrs");
			var t_id = $(this).data("food_id");
			if(t == attr && t_id == food_id){
				l_k = k;
				console.log(k);
			}
		});
		return l_k;
	}
	
	//合计总数
	function countTotal(){
		//console.log("countTotal");
		var total = 0;
		$('#foodlist li').each(function(){
			var t = Number($(this).data("food_price"));
			total += t;
		});
		var column_num = parseInt($("#foodlist").children('li').length);//购物车内菜品栏数	
		total = total.toFixed(2);
		$("#Total").html(total);
		$("#numv").html(column_num);
	}
	
	//购物车栏内份数减
	function minus(obj){
		var single_price = Number($(obj).data("single_price"));//模态单一价
		var nowPrice = Number($(obj).next().next().next().data('food_price'));//模态框总价
		console.log(nowPrice);
		var nowNum = Number($(obj).next().html());
		var lastNum = nowNum-1;
		var lastPrice = nowPrice-single_price;
		console.log(lastPrice);
		lastPrice = lastPrice.toFixed(2);
		if(lastNum > 0){			
			$(obj).next().html(lastNum);
			$(obj).parent().find('span').eq(1).html("&yen;"+lastPrice+"元");
			$(obj).parent().find('span').eq(1).data("food_price",lastPrice);
			$(obj).parents('li').data("food_price",lastPrice);
			$(obj).parents('li').data("food_num",lastNum);		
		}else{	
			$(obj).parent().prev().parent().remove();
		}
		countTotal();
	}

	//购物车栏内份数加
	function plus(obj){
		var single_price = Number($(obj).data("single_price"));
		var nowPrice = Number($(obj).next().data("food_price"));
		var nowNum = Number($(obj).prev().html());

		var lastNum = nowNum+1;
		var lastPrice = nowPrice+single_price;
		lastPrice = lastPrice.toFixed(2);
		$(obj).parent().find("span").eq(0).html(lastNum);
		//$(obj).parent().prev().children().html("&yen;"+lastPrice);
		$(obj).next().html("&yen;"+lastPrice+"元");
		$(obj).next().data("food_price",lastPrice);
		$(obj).parents('li').data("food_price",lastPrice);
		$(obj).parents('li').data("food_num",lastNum);
		countTotal();
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
