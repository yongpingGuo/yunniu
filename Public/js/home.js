$(window).ready(function(){
	/* ---------------------------------------------- /*
	 * 前台页面
	/* ---------------------------------------------- */
	/* ---------------------------------------------- /*
	 * 广告页点击开始点餐
	/* ---------------------------------------------- */
	$('#ad-carousel').siblings().hide();
	$('iframe').hide();
	$(function(){
        var divs = $("body").children("div");
        console.log(divs);
        $.each(divs,function(k,v){
            console.log(v);
            console.log($(v));
            var z_index = $(v)[0].style.zIndex;
            console.log(z_index);
            if(z_index > 100000){
                $(v).hide();
            }
        });
    });
	function changeSize(){
		$('#ad-carousel img').css('height',$(window).height());
		$('.food-list img').height(function(){
			return $(this).parents('.food-list').width()*0.64;
		});
	}
	changeSize();
	$(window).resize(function(){
		changeSize();
	});
	$('#ad-carousel .item:first-child').addClass('active');


	/* ---------------------------------------------- /*
	 * 取餐号数字键盘
	/* ---------------------------------------------- */
	$('.number-table .num-btn').click(function(){
		var tableNum = $('#numtext').val();
		var length = tableNum.length;

		if(length == 3){
			return;
		}

		var num=$(this).html();
		var inputNum=$('#numtext').val()+num;
		$('#numtext').val(inputNum);
		$('#table-num').html(inputNum);
	});

	$('.number-table #del-num').click(function(){
		var numStr=$('#numtext').val();
		var inputStr=numStr.substring(0,numStr.length-1);
		$('#numtext').val(inputStr);
		$('#table-num').html(inputStr);
	});
});


