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