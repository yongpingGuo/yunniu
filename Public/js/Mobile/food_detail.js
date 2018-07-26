
$('.modal-dish-icon').height($(window).width()*0.6)
$(function(){
    $("#attr_form :radio").each(function(index,element){
        if($(element).data('key') == 1){
            $(this).parent().toggleClass('attr-select');
            $(this).parent().trigger('click');
        }
    })
})

$('#first li input').click(function(){
    $(this).parent().addClass('attr-select');
    $(this).parent().siblings().removeClass('attr-select');
});


$('#third li input').click(function(){
    $(this).parent().toggleClass('attr-select');
});

function changePrice(){
    var attrs = "";
    var food_price = $("#food_price").data("food_price");
    var attr_amount_price = 0;
    $("#attr_form .attr-select input").each(function(){
        attrs += $(this).data("fd_at_id")+"-";
        var attr_price = Number($(this).val());
        attr_amount_price += attr_price;
    });
    var food_num3 = Number($("#food_num").html());
    var single_price = attr_amount_price+Number(food_price);
    var last_price = Number((attr_amount_price+Number(food_price))*food_num3);
    last_price = last_price.toFixed(2);
    $("#food_price").html(last_price);
    $("#food-checked").data("single_price",single_price);
    $("#food-checked").data("attrs",attrs);
}

function decreaseNum(obj){
    var food_data = $(obj).data();
    var food_num = $("#food_num").html();
    var new_food_price = $("#food_price").html();
    var priceByOne = new_food_price/food_num;
    var last_price = Number(new_food_price)-Number(priceByOne);

    if(last_price > 0){
        last_price = last_price.toFixed(2);
        var last_num = Number(food_num)-1;
        $("#food_num").html(last_num);
        $("#food_price").html(last_price);
    }
}

function increaseNum(obj){
    var food_data2 = $(obj).data();
    var food_num2 = $("#food_num").html();
    var new_food_price2 = $("#food_price").html();
    var priceByOne2 = new_food_price2/food_num2;
    var last_price2 = Number(new_food_price2)+Number(priceByOne2);

    var last_num2 = Number(food_num2)+1;
    last_price2 = last_price2.toFixed(2);

    $("#food_num").html(last_num2);
    $("#food_price").html(last_price2);
}

function addOrderItem(obj){
    var food_id = $(obj).data("food_id");
    var single_price = $(obj).data("single_price");
    var food_num = Number($("#food_num").html());
    var food_name = $(obj).data("food_name");
    var food_price = Number($("#food_price").html()).toFixed(2);
    var attrs = $(obj).data("attrs");
    var k = isSameFood(attrs,food_id);
    if(k>=0){
        var sec = $('#foodlist').children().eq(k);
        var old_food_price = Number(sec.data("food_price"));
        var mold_food_num = Number(sec.data("food_num"));
        var last_food_price = Number(food_price)+old_food_price;
        last_food_price = last_food_price.toFixed(2);
        var last_food_num = food_num+mold_food_num;
        sec.data("food_price",last_food_price);
        sec.data("food_num",last_food_num);

        sec.children().eq(2).html(last_food_num);
        sec.children().eq(0).find('b').html(last_food_price);
        sec.children().eq(0).find('span').data('food_price',last_food_price);
    }else {
        var str =  '<div class="dish-item flex-content vertical-flex" data-food_id="' + food_id + '" data-attrs="' + attrs + '" data-food_price="' + food_price + '" data-food_num="' + food_num + '">'
                   +'<div class="flex-main">'
                      +'<div class="dish-name">' + food_name + '</div>'
                        +'<div class="dish-price">'
                            +'<small>&yen;</small>'
                            +'<span class="cart-num" data-food_price="' + food_price + '">'+ '<b>'+food_price +'</b>'+'元</span>'
                        +'</div>'
                      +'</div>'
                      +'<button class="minus-btn" onclick = "minus(this)" data-food_id="' + food_id + '" data-single_price="' + single_price + '">'
                        +'<i class="iconfont icon-minus"></i>'
                      +'</button>'
                      +'<div class="cart-num" data-food_id="' + food_id + '" data-single_price="' + single_price + '">' + food_num + '</div>'
                      +'<button class="plus-btn" onclick = "plus(this)" data-food_id="' + food_id + '" data-single_price="' + single_price + '">'
                         +'<i class="iconfont icon-plus"></i>'
                      +'</button>'
                   +'</div>';
        $('#foodlist').append(str);
    }
    countTotal();
    if($(obj).data('have_attribute')==1){
        console.log('无规格模态框')
        var info_obj=$('.dish-right .plus-btn[data-food_id="'+food_id+'"]');
        if (info_obj.siblings('.minus-btn').length == 0) {
            var str = '<button class="minus-btn" onclick="foodMinus(this)" data-food_id="' + food_id + '">\
                        <i class="iconfont icon-minus"></i>\
                    </button>\
                    <span class="cart-num" data-food_id="' + food_id + '">'+food_num+'</span>'
            info_obj.before(str);
        } else {
            info_obj.siblings('.cart-num').html(last_food_num)
        }
    }
}

function isSameFood(attr,food_id){
    var l_k = -1;
    $('#foodlist').children().each(function(k,v){
        var t = $(this).data("attrs");
        var t_id = $(this).data("food_id");
        if(t == attr && t_id == food_id){
            l_k = k;
        }
    });
    return l_k;
}

$('#food-checked').click(function(){
    $('#foodModal').modal('hide');
})

function countTotal(){
    var total = 0;
    $('#foodlist').children().each(function(){
        var t = Number($(this).data("food_price"));
        total += t;
    });
    total = total.toFixed(2);
    $("#Total").html(total);

    var column_num = parseInt($("#foodlist").children().length);
    $("#numv").html(column_num);
    if (total == 0) {
        $('.order-cart').hide();
    }
}

function plus(obj){
    var food_id=Number($(obj).data("food_id"));
    var single_price = Number($(obj).data("single_price"));
    var nowPrice = Number($(obj).parent().children().eq(0).find('span').data("food_price"));
    var nowNum = Number($(obj).prev().html());

    var lastNum = nowNum+1;
    var lastPrice = nowPrice+single_price;
    lastPrice = lastPrice.toFixed(2);
    $('.dish-right .cart-num[data-food_id="'+food_id+'"]').html(lastNum);
    $(obj).prev().html(lastNum);
    $(obj).parent().children().eq(0).find('b').html(lastPrice);
    $(obj).parent().children().eq(0).find('span').data("food_price",lastPrice);
    $(obj).parent().data("food_price",lastPrice);
    $(obj).parent().data("food_num",lastNum);
    countTotal();
}

function minus(obj){
    var food_id=Number($(obj).data("food_id"));
    var single_price = Number($(obj).data("single_price"));
    var nowPrice = Number($(obj).parent().children().eq(0).find('span').data("food_price"));
    var nowNum = Number($(obj).next().html());
    var lastNum = nowNum-1;
    var lastPrice = nowPrice-single_price;
    $('.dish-right .cart-num[data-food_id="'+food_id+'"]').html(lastNum);

    lastPrice = lastPrice.toFixed(2);
    if(lastNum > 0){
        $(obj).next().html(lastNum);
        $(obj).parent().children().eq(0).find('b').html(lastPrice);
        $(obj).parent().children().eq(0).find('span').data("food_price",lastPrice);
        $(obj).parent().data("food_price",lastPrice);
        $(obj).parent().data("food_num",lastNum);
    }else{
        $(obj).parent().remove();
        $('.dish-right .cart-num[data-food_id="'+food_id+'"]').prev().remove();
        $('.dish-right .cart-num[data-food_id="'+food_id+'"]').remove();
    }
    countTotal();
}

function clearorder(){
    $('#foodlist').children().remove();
    $("#numv").html(0);
    $("#Total").html("0.00");

}