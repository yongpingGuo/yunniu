	$(function(){
		$("#attr_form :radio").each(function(index,element){		
			if($(element).data('key') == 1){
				$(this).parent().toggleClass('attr-select');
				$(this).parent().trigger('click');
			}
		})
	})
	
	//模态框里的份数减
	function decreaseNum(obj){
		var food_data = $(obj).data();
		console.log(food_data);
		var food_num = $("#food_num").html();  //份数
		var food_price = food_data['food_price'];
		//var attr_amount = food_data['attr_amount'];
		var new_food_price = $("#food_price").html();  //变价
        var priceByOne = new_food_price/food_num;
		var last_price = Number(new_food_price)-Number(priceByOne);
		if(last_price > 0){
			last_price = last_price.toFixed(2);
			$("#food_price").html(last_price);
			var last_num = Number(food_num)-1;
			$("#food_num").html(last_num);
			$('#show_num').html(last_num);
		}
	}

	//模态框里的份数加
	function increaseNum(obj){
		var food_data2 = $(obj).data();
		console.log(food_data2);
		var food_num2 = $("#food_num").html();
		var food_price2 = food_data2['food_price'];
		//var attr_amount2 = food_data2['attr_amount'];
		var new_food_price2 = $("#food_price").html();
		var priceByOne2 = new_food_price2/food_num2;
		var last_price2 = Number(new_food_price2)+Number(priceByOne2);
		var last_num2 = Number(food_num2)+1;
		last_price2 = last_price2.toFixed(2);
		$("#food_price").html(last_price2);
		$("#food_num").html(last_num2);
		$('#show_num').html(last_num2);
	}

	//选择属性时菜品总价的变化
	function changePrice(){
		var attrs = "";
		var food_price = $("#food_price").data("food_price");   //菜品原始单价
		var attr_amount_price = 0;
		$("#attr_form .attr-select input").each(function(){		//遍历带有.attr-select的属性，.attr-select即选中
				attrs += $(this).data("fd_at_id")+"-";			//拼接属性ID字符串
				var attr_price = Number($(this).val());
				//console.log(attr_price);
				attr_amount_price += attr_price;				//属性总价
		});
		var food_num3 = Number($("#food_num").html());
		var single_price = attr_amount_price+Number(food_price);//菜品原价+菜品属性 = 菜品变价
		var last_price = Number((attr_amount_price+Number(food_price))*food_num3);	//菜品变价*份数 = 购物车此ID菜品一条记录的总价
		last_price = last_price.toFixed(2);
		$("#food_price").html(last_price);
		$("#food-checked").data("single_price",single_price);
		//console.log(attrs);
		$("#food-checked").data("attrs",attrs);
	}

	//点击模态框确认时，将菜品相关信息保存至右侧的购物车中
	function addOrderItem(obj){
		var food_id = $(obj).data("food_id");
		var single_price = $(obj).data("single_price");
		var food_num = Number($("#food_num").html());
		var food_name = $(obj).data("food_name");
		var food_price = Number($("#food_price").html()).toFixed(2);
		var attrs = $(obj).data("attrs");
		var k = isSameFood(attrs,food_id);
		if(k>=0){
			var sec = $('#foodlist section').eq(k);
			var old_food_price = Number(sec.data("food_price"));
			var mold_food_num = Number(sec.data("food_num"));
			var last_food_price = Number(food_price)+old_food_price;
			last_food_price = last_food_price.toFixed(2);
			var last_food_num = food_num+mold_food_num;
			sec.data("food_price",last_food_price);
			sec.data("food_num",last_food_num);
			sec.children(1).children(0).find("b").html("&yen;"+last_food_price);
			sec.children(1).children(0).find("b").data("food_price",last_food_price);
			sec.children(1).children(1).find("span").html(last_food_num);
		}
		else{
			var str = '<section class="food-select-item"  data-food_id="'+food_id+'" data-attrs="'+attrs+'" data-food_price="'+food_price+'" data-food_num="'+food_num+'">'
					+'<div class="food-name">'+food_name+'</div>'
					+'<div class="select-content clearfix">'
					+'<div class="pull-left text-left orange">'
					+'<b  data-food_price="'+food_price+'">&yen;'+food_price+'</b></div>'
					+'<div class="pull-right text-right">'
					+'<button class="btn-none" onclick = "minus(this)" data-food_id="'+food_id+'" data-single_price="'+single_price+'">'
					+'<img src="/Public/images/minus_btn.png">'
					+'</button>'
					+'<span class="select-num">'+food_num+'</span>'
					+'<button class="btn-none" onclick = "plus(this)" data-food_id="'+food_id+'" data-single_price="'+single_price+'">'
					+'<img src="/Public/images/plus_btn.png">'
					+'</button>'
					+'</div>'
					+'</div>'
					+'</section>';

			$('#foodlist').append(str);
		}
		var startX=$('.modal-img-content img').offset().left+$('.modal-img-content img').width()/2;
		var startY=$('.modal-img-content img').offset().top+$('.modal-img-content img').height()/2;
		var endX=$('.food-select-list').offset().left+$('.food-select-list').width()/2;
		var endY=$('.food-select-list').offset().top+$('.food-select-list').height();

		var flyEle='<div id="flyEle"></div>';
		// $('#foodModal').removeClass('fade');
	    $(flyEle).fly({
			start: {
				top: startY, 
				left: startX
			},
			end: {
				top: endY, 
				left: endX,
				width:20,
				height:20
			},
			speed: 3.3, //越大越快，默认1.2
			onEnd: function(){
				$('#flyEle').remove();
				$('#foodModal').modal('hide');
				// $('#foodModal').addClass('fade');
			}
	    });
		countTotal();
	}

	//合计总数
	function countTotal(){
		var total = 0;
		$('#foodlist section').each(function(){
			var t = Number($(this).data("food_price"));
			total += t;
		});
		total = total.toFixed(2);
		$("#Total").html(total);
	}

	//模态框确定时，判断右侧购物车是否已存在该商品，存在：相同记录上叠加份数与价格，不存在，新建购物车记录
	function isSameFood(attr,food_id){
		var l_k = -1;
		$('#foodlist section').each(function(k,v){
			var t = $(this).data("attrs");
			var t_id = $(this).data("food_id");
			if(t == attr && t_id == food_id){
				l_k = k;
				console.log(k);
			}
		});
		return l_k;
	}

	//购物车每条记录的减
	function minus(obj){
		var single_price = Number($(obj).data("single_price"));		//单一的价格：份数为一，但是是原价+属性价
		var nowPrice = Number($(obj).parent().prev().children().data("food_price"));//购物车当前记录的总价格
		var nowNum = Number($(obj).parent().find("span").html());					//购物车当前记录的总份数
		var lastNum = nowNum-1;
		var lastPrice = nowPrice-single_price;
		lastPrice = lastPrice.toFixed(2);
		if(lastNum > 0){			
			console.log(lastPrice);
			$(obj).parent().find("span").html(lastNum);
			$(obj).parent().prev().children().html("&yen;"+lastPrice);
			$(obj).parent().prev().children().data("food_price",lastPrice);
			$(obj).parent().parent().parent().data("food_price",lastPrice);
			$(obj).parent().parent().parent().data("food_num",lastNum);	
		}else{	
			$(obj).parent().parent().parent().remove();
		}
		countTotal();
	}

	//购物车每条记录的加
	function plus(obj){
		var single_price = Number($(obj).data("single_price"));		//单一的价格：份数为一，但是是原价+属性价
		var nowPrice = Number($(obj).parent().prev().children().data("food_price"));//购物车当前记录的总价格
		var nowNum = Number($(obj).parent().find("span").html());					//购物车当前记录的总份数
		var lastNum = nowNum+1;
		var lastPrice = nowPrice+single_price;
		lastPrice = lastPrice.toFixed(2);
		$(obj).parent().find("span").html(lastNum);
		$(obj).parent().prev().children().html("&yen;"+lastPrice);
		$(obj).parent().prev().children().data("food_price",lastPrice);
		$(obj).parent().parent().parent().data("food_price",lastPrice);
		$(obj).parent().parent().parent().data("food_num",lastNum);
		countTotal();
	}
	
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
	
		$('#first li input').click(function(){
			$(this).parent().addClass('attr-select');
			$(this).parent().siblings().removeClass('attr-select');
		});
		
		
		$('#third li input').click(function(){
			$(this).parent().toggleClass('attr-select');
		});
		
	
