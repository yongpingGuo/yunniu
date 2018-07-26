function classifyHeight() {
    var classifyHeight=$(window).height()-$('.header').height()-$('.footer').height()-6*14;
    $('.classify-content').css('min-height', classifyHeight);
}
classifyHeight();
$(window).resize(function(event) {
    classifyHeight();
});

//点击菜品类表显示对应菜品信息
function showinfo(obj){
    var food_category_id = $(obj).data("id");
    $.ajax({
        type:"get",
        url:"/index.php/MobileAdmin/dishes/food_up_in_cate/food_category_id/"+food_category_id+"",
        success:function (data){
            $('#mytr').html(data);
        }
    });
    $(obj).siblings().removeClass('active');
    $(obj).addClass('active');
}

function moveup(obj){
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var when_tr = parseInt($(obj).data('index'));
    if(when_tr==1){
        return false;
    }
    var food_category_id = $(obj).data('food_category_id');
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/dishes/moveup",
        data:{"sort":sort,"food_id":food_id},
        dataType:"json",
        success:function(data){
            if(data.code == 1){
                $.ajax({
                    url:"/index.php/MobileAdmin/Dishes/food_up_in_cate/food_category_id/"+food_category_id,
                    type:"get",
                    success:function(data){
                        $("#mytr").html(data);
                    },
                    error:function(){
                        alert("出错了");
                    }
                });
            }
        }
    });
}

function movedown(obj){
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var last_tr = $("#mytr").children().children('div:last');
    var downObj = $(obj).parent().parent().parent();
    if(last_tr == downObj){
        return false;
    }
    var food_category_id = $(obj).data('food_category_id');
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/dishes/movedown",
        data:{"sort":sort,"food_id":food_id},
        dataType:"json",
        success:function(data){
            if(data.code == 1){
                $.ajax({
                    url:"/index.php/MobileAdmin/Dishes/food_up_in_cate/food_category_id/"+food_category_id,
                    type:"get",
                    success:function(data){
                        $("#mytr").html(data);
                    },
                    error:function(){
                        alert("出错了");
                    }
                });
            }
        }
    });
}

//点菜品编辑跳到指定编辑页
function modify_food(food_id){
    location.href = "/index.php/MobileAdmin/Dishes/food_edit/food_id/"+food_id;
}

//删除菜品表信息
function delfoodinfo(obj){
    var food_id = $(obj ).data('food_id');
    var food_category_id = $(obj ).data('food_category_id');

    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        $.ajax({
            type:"post",
            url:"/index.php/MobileAdmin/Dishes/delfoodinfo",
            data: {'food_id':food_id,'food_category_id':food_category_id},
            async:true,
            success:function(data){
                if(data == 1){
                    $(obj).parent().parent().remove();
                }else{
                    layer.msg('删除失败');
                }
            }
        });
        layer.close(index);
    });
}


function type_and_attr(food_id){
    location.href = "/index.php/MobileAdmin/Dishes/type_and_attr/food_id/"+food_id;
}