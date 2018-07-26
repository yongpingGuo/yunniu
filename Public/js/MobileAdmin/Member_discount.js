function change_switch_status(obj){
    var open_which = $(obj).val();
    $.ajax({
        url: "/index.php/MobileAdmin/Member/change_switch_status",
        type: "post",
        data: {'open_which':open_which},
        async: false,
        cache: false,
        dataType: "json",
        success: function(data) {
            if(data.code == 1){
                layer.msg('操作成功');
            }else{
                layer.msg('操作失败');
            }
        },
        error:function(){
            alert("出错了");
        }
    });
}

function keep_restaurant_discount(obj,add_or_edit){
    var money_restaurant = $("#money_restaurant").val();
    var discounts_restaurant = $("#discounts_restaurant").val();
    var reduce_restaurant = $("#reduce_restaurant").val();

    if(!(money_restaurant && discounts_restaurant && reduce_restaurant)){
        layer.msg("所填内容不能为空");
        return false;
    }

    if(isNaN(money_restaurant)){
        layer.msg("请确保“满多少元”为数字");
        $("#money_restaurant").val('');
        return false;
    }

    if(money_restaurant<0){
        layer.msg("请确保“满多少元”大于等于0");
        $("#money").val('');
        return false;
    }

    if(isNaN(discounts_restaurant)){
        layer.msg("请确保“折扣”为数字");
        $("#discounts_restaurant").val('');
        return false;
    }

    if(discounts_restaurant<1 || discounts_restaurant>10){
        layer.msg("请确保“折扣值”在1到10之间（包含1和10），如：8折为8,8.5折为8.5");
        $("#discounts_restaurant").val('');
        return false;
    }

    if(isNaN(reduce_restaurant)){
        layer.msg("请确保“立减”为数字");
        $("#reduce_restaurant").val('');
        return false;
    }

    if(reduce_restaurant<0){
        layer.msg("请确保“立减值”大于等于0");
        $("#money").val('');
        return false;
    }

    var form_data = $(obj ).parent().parent().parent();
    var formData = new FormData(form_data[0]);
    $.ajax({
        url: "/index.php/MobileAdmin/Member/keep_restaurant_discount",
        type: "post",
        data: formData,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(data) {
            if(data.code == 1){
                if(add_or_edit == 1){
                    layer.msg('保存成功');
                }else{
                    location.href = "/index.php/MobileAdmin/Member/discount_all";
                }
            }else{
                layer.msg(data.msg);
            }
        },
        error:function(){
            layer.msg("出错了");
        }
    });
}

function deleteDisc_restaurant(obj) {
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        var _id = $(obj).data('type_id');
        $.ajax({
            url:"/index.php/MobileAdmin/Member/deleteDisc_restaurant",
            dataType:'json',
            data:{"id":_id},
            type:'POST',
            success:function(data){
                if (data.code==1) {
                    $(obj).parent().parent().parent().remove();
                    $("#new_add").show();
                }else{
                    layer.msg('删除失败');
                }
            }
        });
        layer.close(index);
    });
    return false;
}

function add_restaurant_discount(){
    var str =
                '<form action="javascript:void(0)">'
                    +'<div class="section-row">'
                    +'<input type="hidden" name="id"/>'
                       +'<div>'
                           +'<span>消费满</span>'
                           +'<input type="text" name="money" id="money_restaurant" class="input-xs">'
                           +'<span> 元时：</span>'
                       +'</div>'
                       +'<div class="flex-content vertical-flex">'
                       +'<div class="flex-main">'
                         +'<span>&nbsp;&nbsp;折扣</span>'
                         +'<input type="text" name="discount" id="discounts_restaurant" class="input-xs">'
                         +'<span>折，立减</span>'
                         +'<input type="text" name="reduce" id="reduce_restaurant" class="input-xs">'
                         +'<span>元</span>'
                       +'</div>'
                         +'<button class="default-btn" onclick="keep_restaurant_discount(this,2)">新增</button>'
                     +'</div>'
                     +'<hr class="hr hr-dashed">'
                    +'</div>'
                +'</form>';
    $('#restaurant_discount_list').html(str);
    $("#new_add").hide();
    $("#new_add_two").hide();
}

