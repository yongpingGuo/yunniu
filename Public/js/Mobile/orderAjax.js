$(function(){
	$('.dish-icon').width($('.dish-item').width()-120);
    $('.dish-icon').height(($('.dish-item').width()-120)*0.6);
    $("#attr_form :radio").each(function(index,element){
        if($(element).data('key') == 1){
            $(this).parent().toggleClass('attr-select');
            $(this).parent().trigger('click');
        }
    })
})