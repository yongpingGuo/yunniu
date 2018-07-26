$(window).ready(function(){
	
	$('.dishes-sale-item button').click(function(event) {
		$(this).parents('.sale-item-head').siblings('.dishes-sale-info').slideToggle();
	});
});
