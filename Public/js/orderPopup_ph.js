
//加载模态框时，如果有前两行属性，单选默认每行的第一个属性
$(function() {
    var price = $('#price').parent().attr('value'); //单价
    if ($('#first').children().length) {
        if ($('#second').children().length) {
            console.log("全有");
            document.getElementsByName('radio1')[0].checked = "checked";
            $('#first li:first-child').addClass('attr-select');	
            document.getElementsByName('radio2')[0].checked = "checked";
            var attribute1 = document.getElementsByName('radio1')[0].value; //第一个默认属性价格
            var attribute2 = document.getElementsByName('radio2')[0].value; //第二个默认属性价格
            var price = parseFloat(attribute1) + parseFloat(attribute2) + parseFloat(price);
        } else {
            console.log("只有一");
            document.getElementsByName('radio1')[0].checked = "checked";
            var attribute1 = document.getElementsByName('radio1')[0].value; //第一个默认属性价格
            var price = parseFloat(attribute1) + parseFloat(price);
        }
    } else {
        console.log("全没");
        var price = parseFloat(price);
    }
    $('#price').html(price);
});


$('.attr-select-item input').click(function(){
	$(this).parent().addClass('attr-select');
	$(this).parent().siblings().removeClass('attr-select');
});

//模态框里的添加
function addnum(num) {
    var a = parseInt($(num).prev().html()) + 1; //份数
    $(num).prev().html(a);
    var price = parseFloat($(num).next().attr('value')); //单价
    //总价 = (单价+属性价)*份数
    if ($('#first').children().length) {
        if ($('#second').children().length) {
            if ($('#third').children().length) {
                console.log("全有");
                var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一零碎价
                var attribute2 = parseFloat($('#second input[name="radio2"]:checked').val()); //第二零碎价
                var list = new Array();
                $("input:checkbox[name='checkbox1']:checked").map(function(index, elem) {
                    list[index] = parseFloat($(elem).val());
                });
                var result = 0;
                for (var i = 0; i < list.length; i++) {
                    result += list[i];
                }
                var attribute3 = parseFloat(result); //第三个零碎价	
                var total = (price + attribute1 + attribute2 + attribute3) * a;
            } else {
                console.log("只有一二、没有三");
                var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一行属性价
                var attribute2 = parseFloat($('#second input[name="radio2"]:checked').val()); //第二行属性价
                console.log(attribute1 + "|" + attribute2);
                var total = (price + attribute1 + attribute2) * a;
            }
        } else {
            console.log("只有一、没有二三");
            var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一行属性价
            var total = (price + attribute1) * a;
        }
    } else {
        console.log("全没");
        var total = price * a;
    }
    $('#price').html(total);
}

//模态框里的减少
function renum(nums) {
    if (parseInt($(nums).next().html()) >= 2) {
        var b = parseInt($(nums).next().html()) - 1;
        $(nums).next().html(b);
        var price = parseFloat($(nums).next().next().next().attr('value')); //单价
        if ($('#first').children().length) {
            if ($('#second').children().length) {
                if ($('#third').children().length) {
                    console.log("全有");
                    var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一零碎价
                    var attribute2 = parseFloat($('#second input[name="radio2"]:checked').val()); //第二零碎价
                    var list = new Array();
                    $("input:checkbox[name='checkbox1']:checked").map(function(index, elem) {
                        list[index] = parseFloat($(elem).val());
                    });
                    var result = 0;
                    for (var i = 0; i < list.length; i++) {
                        result += list[i];
                    }
                    var attribute3 = parseFloat(result); //第三个零碎价	
                    var total = (price + attribute1 + attribute2 + attribute3) * b;
                } else {
                    console.log("只有一二、没有三");
                    var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一行属性价
                    var attribute2 = parseFloat($('#second input[name="radio2"]:checked').val()); //第二行属性价
                    console.log(attribute1 + "|" + attribute2);
                    var total = (price + attribute1 + attribute2) * b;
                }
            } else {
                console.log("只有一、没有二三");
                var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一行属性价
                var total = (price + attribute1) * b;
            }
        } else {
            console.log("全没");
            var total = price * b;
        }
        $('#price').html(total);
    }
}

//右边记录的添加
function leftaddnum(num) {
    var a = parseInt($(num).prev().html()) + 1; //右边记录菜品的份数
    $(num).prev().html(a);
    if ($('#first').children().length) {
        console.log("有");
        var attr1 = parseFloat($(num).next().children().attr('id'));
        var price = parseFloat($(num).next().children().attr('value')) + attr1; //(原价+属性价)	
    } else {
        console.log('全无');
        var price = parseFloat($(num).next().children().attr('value')); //(原价)
    }
    var variable = parseFloat($(num).next().children().html()); //未按添加按钮前在右边显示的价格
    var price1 = parseFloat(variable + price); //右边单条单种菜品的总价
    $(num).next().children().html(price1);
    var total = parseFloat($('#Total').html()); //没点右边+-按钮前的总价
    var total1 = total + price;
    $('#Total').html(total1);
}

//右边记录的减少
function leftrenum(nums) {
    if (parseInt($(nums).next().html()) >= 2) {
        var b = parseInt($(nums).next().html()) - 1;
        $(nums).next().html(b);
        if ($('#first').children().length) {
            var attr1 = parseFloat($(nums).next().next().next().children().attr('id'));
            var price = parseFloat($(nums).next().next().next().children().attr('value')) + attr1; //(原价+属性价)	
        } else {
            console.log('全无');
            var price = parseFloat($(nums).next().next().next().children().attr('value')); //(原价)
        }
        var variable = parseFloat($(nums).next().next().next().children().html()); //右边菜品动态变后
        var price1 = parseFloat(variable - price); //右边单条单种菜品的总价
        $(nums).next().next().next().children().html(price1);
        var total = parseFloat($('#Total').html()); //没点右边+-按钮前的总价
        var total1 = total - price;
        //console.log(total);
        $('#Total').html(total1);
    } else {
        var variable = parseFloat($(nums).next().next().next().children().html()); //右边菜品动态变后
        //console.log(variable);
        var total = parseFloat($('#Total').html()); //没点右边+-按钮前的总价
        var total1 = total - variable;
        $('#Total').html(total1);
        $(nums).parent().prev().parent().remove();

    }
	var numv = parseInt($('#foodlist').children('li').length);
 	$('#numv').html(numv);
}




//更改第一行单选属性时，价格的变化
function findPrice(price) {
    if ($('#second').children().length) {
        if ($('#third').children().length) {
            console.log("全有");
            var attribute1 = $(price).attr('value'); //第一个属性价
            var attribute2 = $('#second input[name="radio2"]:checked').val(); //第二个属性价
            var list = new Array();
            $("input:checkbox[name='checkbox1']:checked").map(function(index, elem) {
                list[index] = parseFloat($(elem).val());
            });
            var result = 0;
            for (var i = 0; i < list.length; i++) {
                result += list[i];
            }
            var attribute3 = result; //第三个属性价	
            var price = $("#price").parent().attr('value'); //菜品单价(不变)
            var num = $('#food_num').html();
            var total = (parseFloat(attribute1) + parseFloat(attribute2) + parseFloat(attribute3) + parseFloat(price)) * parseInt(num);
        } else {
            console.log("只有一、二");
            var attribute1 = $(price).attr('value'); //第一个属性价
            var attribute2 = $('#second input[name="radio2"]:checked').val(); //第二个属性价
            var price = $("#price").parent().attr('value'); //菜品单价(不变)
            var num = $('#food_num').html();
            var total = (parseFloat(attribute1) + parseFloat(attribute2) + parseFloat(price)) * parseInt(num);
        }
    } else {
        console.log("只有一");
        var attribute1 = parseFloat($(price).attr('value')); //第一个属性价
        var price = parseFloat($("#price").parent().attr('value')); //菜品单价(不变)
        var num = $('#food_num').html();
        var total = (attribute1 + price) * parseInt(num);
    }
    $("#price").html(total);
}
//第二行属性（零碎价）
function findPrice1(price1) {
    var price = $("#price").parent().attr('value'); //菜品单价(不变)
    var num = $('#food_num').html();
    if ($('#third').children().length) {
        console.log("全有");
        var attribute1 = $('#first input[name="radio1"]:checked').val(); //第一个零碎价
        var attribute2 = $(price1).attr('value'); //第二个零碎价
        var list = new Array();
        $("input:checkbox[name='checkbox1']:checked").map(function(index, elem) {
            list[index] = parseFloat($(elem).val());
        });
        var result = 0;
        for (var i = 0; i < list.length; i++) {
            result += list[i];
        }
        var attribute3 = result; //第三个零碎价
        var total = (parseFloat(attribute1) + parseFloat(attribute2) + parseFloat(attribute3) + parseFloat(price)) * parseInt(num);
    } else {
        console.log("只有一二");
        var attribute1 = $('#first input[name="radio1"]:checked').val(); //第一个零碎价
        var attribute2 = $(price1).attr('value'); //第二个零碎价
        var price = $("#price").parent().attr('value'); //菜品单价(不变)
        var num = $('#food_num').html();
        var total = (parseFloat(attribute1) + parseFloat(attribute2) + parseFloat(price)) * parseInt(num);
    }
    $("#price").html(total)
}
//第三行属性（零碎价）
function findPrice2(price2) {
    var attribute1 = $('#first input[name="radio1"]:checked').val(); //第一个零碎价
    var attribute2 = $('#second input[name="radio2"]:checked').val(); //第二个零碎价
    var list = new Array();
    $("input:checkbox[name='checkbox1']:checked").map(function(index, elem) {
        list[index] = parseFloat($(elem).val());
    });
    var result = 0;
    for (var i = 0; i < list.length; i++) {
        result += list[i];
    }
    var attribute3 = result; //第三个零碎价	
    var price = $("#price").parent().attr('value'); //菜品单价(不变)
    var num = $('#food_num').html();
    var attribute = parseFloat(attribute1) + parseFloat(attribute2) + parseFloat(attribute3); //总零碎价
    var total = (parseFloat(attribute) + parseFloat(price)) * parseInt(num);
    $("#price").html(total)
}


//点击模态框确定所提交给右边记录的操作
function commit(com) {
    var name = $('#foodname').html(); //菜品名		
    var numbers = $('#food_num').html(); //菜品份数
    var uprice = parseFloat($('#price').parent().attr('value')); //原价 
    if ($('#first').children().length) {
        if ($('#second').children().length) {
            if ($('#third').children().length) {
                console.log("全有");
                var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一零碎价
                var attrname = $('#first input[name="radio1"]:checked').attr('uname'); //第一行属性名
                var uid1 = $('#first input[name="radio1"]:checked').attr('uid'); //第一行属性名所对应的ID
                //console.log("属性名"+attrname);
                var attribute2 = parseFloat($('#second input[name="radio2"]:checked').val()); //第二零碎价
                var attrname2 = $('#second input[name="radio2"]:checked').attr('uname'); //第二行属性名
                var uid2 = $('#second input[name="radio2"]:checked').attr('uid'); //第二行属性名所对应的ID
                //console.log("属性名2"+attrname2);
                var list = new Array();
                var listname = new Array();
                var listuid = new Array();
                $("input:checkbox[name='checkbox1']:checked").map(function(index, elem) {
                    list[index] = parseFloat($(elem).val());
                    listname[index] = $(elem).attr('uname');
                    listuid[index] = $(elem).attr('uid');
                });
                // console.log(listuid);
                var result = 0; //计算第三行属性总价
                for (var i = 0; i < list.length; i++) {
                    result += list[i];
                }
                var attribute3 = parseFloat(result); //第三个零碎价	
                var attribute = parseFloat(attribute1 + attribute2 + attribute3);

                console.log(listuid.length);
                if (listuid.length) {
                    var uids = 0; //计算第三行属性ID总和
                    for (var i = 0; i < listuid.length; i++) {
                        uids += listuid[i];
                    }
                } else {
                    uids = 0;
                }

            } else {
                console.log("只有一二");
                var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一零碎价
                var attrname = $('#first input[name="radio1"]:checked').attr('uname'); //第一行属性名
                var uid1 = $('#first input[name="radio1"]:checked').attr('uid'); //第一行属性名所对应的ID
                //console.log("属性名"+attrname);
                var attribute2 = parseFloat($('#second input[name="radio2"]:checked').val()); //第二零碎价
                var attrname2 = $('#second input[name="radio2"]:checked').attr('uname'); //第二行属性名
                var uid2 = $('#second input[name="radio2"]:checked').attr('uid'); //第二行属性名所对应的ID
                var attribute = attribute1 + attribute2;
                uids = 0;
            }
        } else {
            console.log("只有一");
            var attribute1 = parseFloat($('#first input[name="radio1"]:checked').val()); //第一零碎价
            var attrname = $('#first input[name="radio1"]:checked').attr('uname'); //第一行属性名
            var uid1 = $('#first input[name="radio1"]:checked').attr('uid'); //第一行属性名所对应的ID
            var attribute = attribute1;
            uid2 = 0;
            uids = 0;

        }
    } else {
        console.log("全无");
        var attribute = 0;
        uid1 = 0;
        uid2 = 0;
        uids = 0;
    }

    var att = attribute; //三行属性价总和
    var price = $('#price').html(); //模态框上的总价
    var id = $(com).parent().prev().prev().prev().attr('id'); //模态框确定时菜品的ID
    console.log(id);
    var array1 = {};
    array1['name'] = name; //菜品名
    array1['uprice'] = parseFloat(uprice); //单价
    array1['att'] = parseFloat(att); //属性总价
    array1['attrname'] = attrname; //第一行所选属性名称
    array1['attrname2'] = attrname2; //第二行所选属性名称
    array1['attrname3'] = listname; //第三行所选属性名称数组
    array1['uid'] = uid1; //第一行所选属性ID
    array1['uid2'] = uid2; //第二行所选属性ID
    array1['uid3'] = uids; //第三行所选属性ID总和
    array1['listuid'] = listuid;
    array1['price'] = parseFloat(price); //总价(原价 +属性价)*份数
    array1['num'] = parseInt(numbers);
    array1['id'] = parseInt(id);


    if (array1['id'] == parseInt($('#fd' + array1['id'] + '').attr('value'))) {
        if (parseInt(array1['uid'] + "" + array1['uid2'] + "" + array1['uid3']) == parseInt($('#' + array1['uid'] + '' + array1['uid2'] + '' + array1['uid3'] + '').attr('value'))) {
            for (var i = 0; i < array1['num']; i++) {
                $('#' + array1['uid'] + '' + array1['uid2'] + '' + array1['uid3'] + '').prev().prev().trigger("click");
            }
        } else {
            var divhtml = '<li class="cart-item" id = "fd' + array1['id'] + '" value = ' + array1['id'] + '><div class="cart-left">' + array1['name'] + '</div>' +
                '<div class="cart-right"><button class="btn-none" onclick = "leftrenum(this)"><img src="/Public/images/minus_btn.png">' +
                '</button><span>' + array1['num'] + '</span><button class="btn-none" onclick = "leftaddnum(this)"><img src="/Public/images/plus_mobile.png">' +
                '</button><span class="red">&yen;<span id = ' + array1['att'] + ' value = ' + array1['uprice'] + '>' + array1['price'] + '</span>元</span>' +
                '<b style ="display:none" id = ' + array1['uid'] + '' + array1['uid2'] + '' + array1['uid3'] + ' value = ' + array1['uid'] + '' + array1['uid2'] + '' + array1['uid3'] + '></b>' +
                '<b style ="display:none" id = ' + array1['uid'] + ' value = ' + array1['uid'] + '>' + array1['attrname'] + '</b>' +
                '<b style ="display:none" id = ' + array1['uid2'] + ' value = ' + array1['uid2'] + '>' + array1['attrname2'] + '</b>' +
                '<b style ="display:none"  value = ' + array1['listuid'] + '>' + array1['attrname3'] + '</b></div></li>';

            $('#foodlist').append(divhtml);
            var numv = parseInt($('#foodlist').children('li').length);
            //console.log("号"+$('#foodlist').children('li').length);
            $('#numv').html(numv);
        }

    } else {
        var divhtml = '<li class="cart-item" id = "fd' + array1['id'] + '" value = ' + array1['id'] + '><div class="cart-left">' + array1['name'] + '</div>' +
            '<div class="cart-right"><button class="btn-none" onclick = "leftrenum(this)"><img src="/Public/images/minus_btn.png">' +
            '</button><span>' + array1['num'] + '</span><button class="btn-none" onclick = "leftaddnum(this)"><img src="/Public/images/plus_mobile.png">' +
            '</button><span class="red">&yen;<span id = ' + array1['att'] + ' value = ' + array1['uprice'] + '>' + array1['price'] + '</span>元</span>' +
            '<b style ="display:none" id = ' + array1['uid'] + '' + array1['uid2'] + '' + array1['uid3'] + ' value = ' + array1['uid'] + '' + array1['uid2'] + '' + array1['uid3'] + '></b>' +
            '<b style ="display:none" id = ' + array1['uid'] + ' value = ' + array1['uid'] + '>' + array1['attrname'] + '</b>' +
            '<b style ="display:none" id = ' + array1['uid2'] + ' value = ' + array1['uid2'] + '>' + array1['attrname2'] + '</b>' +
            '<b style ="display:none" value = ' + array1['listuid'] + '>' + array1['attrname3'] + '</b></div></li>';

        $('#foodlist').append(divhtml);
         var numv = parseInt($('#foodlist').children('li').length);
         // console.log("号"+$('#foodlist').children('li').length);
        $('#numv').html(numv);
    }




    var divhtml2 = $('#foodlist').children();
    var listString = [];
    $.each(divhtml2, function(index, domEle) { //index代表数组下标，domEle代表当前下标所对应的值
        var danprice = $(this).find('span').eq(2).html();
        listString[index] = parseFloat(danprice);
    });
    var result = 0;
    for (var i = 0; i < listString.length; i++) {
        result += listString[i];
    }
    //console.log(result);
    $('#Total').html(result);

}


/* ---------------------------------------------- /*
 * 当模态框选择确定时，整个框特效飞进购物车
/* ---------------------------------------------- */
$('#food-checked').click(function() {
    var flyElm = '<div class="flyElm"></div>';
    $('body').append(flyElm);
    $('.flyElm').append($('.modal-content').clone());
    $('.flyElm').css({
        'background-color': 'red',
        'z-index': '3000',
        'width': $(window).width()*0.2,
        'height': $(window).width()*0.2,
        'position': 'absolute',
        'bottom': $('.modal-content').height()*0.5,
        'left': $('.modal-content').width()*0.4,
        'border-radius': '50%'
    });
    $('.flyElm').animate({
        bottom: 0,
        left: 0,
        width: '10px',
        height: '10px'
    }, 'slow');
    $('#foodModal').modal('hide');
})
$('#foodModal').on('hidden.bs.modal', function() {
    $('.flyElm').remove();
})
