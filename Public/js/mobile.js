/* ---------------------------------------------- /*
 * 手机页面
/* ---------------------------------------------- */
(function (doc, win) {
    var docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
        recalc = function () {
            var clientWidth = docEl.clientWidth;
            if (!clientWidth) return;
            docEl.style.fontSize = 12 * (clientWidth / 320) + 'px';
        };

    if (!doc.addEventListener) return;
    win.addEventListener(resizeEvt, recalc, false);
    doc.addEventListener('DOMContentLoaded', recalc, false);
})(document, window);

$(window).ready(function(){

	/* ---------------------------------------------- /*
	 * 广告页点击开始点餐
	/* ---------------------------------------------- */
	$('#ad-carousel .item:first-child').addClass('active');
	$('#ad-carousel img').css('height',$(window).height());

	/* ---------------------------------------------- /*
	 * 餐牌号数字键盘
	/* ---------------------------------------------- */
	$('.num-btn').click(function(){
		var num=$(this).html();
		var inputNum=$('.number-left input').val()+num;
		$('.number-left input').val(inputNum);
	});

	$('#del-num').click(function(){
		var numStr=$('.number-left input').val();
		var inputStr=numStr.substring(0,numStr.length-1);
		$('.number-left input').val(inputStr);
	});

	

	$('.sorts-list li').click(function(){
		$(this).siblings().removeClass('active');
		$(this).addClass('active');
	});
	
	/* ---------------------------------------------- /*
	 * 选择支付方式
	/* ---------------------------------------------- */
	$('.pay-item').click(function(){
		$('.pay-item-right span').removeClass('red');
		$(this).find('.pay-item-right span').addClass('red');
	});

	$("#cartModal").on("show.bs.modal", function() {
	    $('.home-footer').addClass('footer-show');
	});

	$("#cartModal").on("hide.bs.modal", function() {
	    $('.home-footer').removeClass('footer-show');
	});

});