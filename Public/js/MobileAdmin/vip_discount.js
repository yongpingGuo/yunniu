function add_vip_discount(){
    $.ajax({
        url:"/index.php/MobileAdmin/Member/get_group",
        dataType:"json",
        success:function(msg){
            append_str(msg);
        },
        error:function(){
            layer.msg('出错了');
        }
    });
}

function append_str(return_group){
    var group_option = '<option value="0">默认会员组</option>';
    $.each(return_group, function (k,v) {
        group_option += '<option value="'+v.group_id+'">'+v.group_name+'</option>';
    })

    var str = '<form action="javascript:void(0)">'
                  +'<div class="section-row">'
                      +'<div>'
                          +'<span>会员组</span>'
                          +'<div class="select-reset">'
                              +'<select name="group_id">'
                                +group_option
                              +'</select>'
                          +'</div>'
                          +'<span>满</span>'
                          +'<input type="number" pattern="[0-9]*" name="money" class="input-xs">'
                          +'<span> 元：</span>'
                      +'</div>'
                      +'<div class="flex-content vertical-flex">'
                      +'<div class="flex-main">'
                      +'<span>折扣</span>'
                      +'<input type="number" pattern="[0-9]*" name="discount" class="input-xs">'
                      +'<span>折，立减</span>'
                    +'<input type="number" pattern="[0-9]*" name="reduce" class="input-xs">'
                      +'<span>元</span>'
                        +'</div>'
                        +'<button class="default-btn" onclick="keep_discount(this,1)">保存</button>'
                        +'<input type="hidden" name="id"/>'
                        +'<button class="default-btn" onclick="deleteDisc(this)">删除</button>'
                        +'</div> '
                        + '<hr class="hr hr-dashed">'
                +'</div>'
              +'</form>';
    $("#discount_list").append(str);
}

function keep_discount(obj,division){
    var money = $(obj ).parent().prev().find('input' ).val();
    var discounts = $(obj ).prev().find('input[name="discount"]').val();
    var reduce = $(obj ).prev().find('input[name="reduce"]').val();

    if(!(money && discounts && reduce)){
        layer.msg("所填内容不能为空");
        return false;
    }

    if(isNaN(money)){
        layer.msg("请确保“满多少元”为数字");
        $("#money").val('');
        return false;
    }

    if(money<0){
        layer.msg("请确保“满多少元”大于等于0");
        $("#money").val('');
        return false;
    }

    if(isNaN(discounts)){
        layer.msg("请确保“折扣”为数字");
        $("#discounts").val('');
        return false;
    }

    if(discounts<1 || discounts>10){
        layer.msg("请确保“折扣值”在1到10之间（包含1和10），如：8折为8,8.5折为8.5");
        $("#discounts").val('');
        return false;
    }

    if(isNaN(reduce)){
        layer.msg("请确保“立减”为数字");
        $("#reduce").val('');
        return false;
    }

    if(reduce<0){
        layer.msg("请确保“立减值”大于等于0");
        $("#money").val('');
        return false;
    }

    var form_obj = $(obj).parent().parent().parent();
    var formData = new FormData(form_obj[0]);
    $.ajax({
        url: "/index.php/MobileAdmin/Member/keep_discount",
        type: "post",
        data: formData,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(data) {
            if(data.code == 1){
                layer.msg(data.msg);
                if(division == 1){
                    $(obj).next().val(data.id);
                }
            }else{
                layer.msg(data.msg);
            }
        },
        error: function () {
            layer.msg("出错了");
        }
    });
}

function deleteDisc(obj){
    var id = $(obj ).prev().val();
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        if(id == ''){
            layer.msg('删除成功');
            $(obj ).parent().parent().parent().remove();
        }else{
            $.ajax({
                url:"/index.php/MobileAdmin/Member/deleteDisc",
                dataType:'json',
                data:{"id":id},
                type:'POST',
                success:function(data){
                    if (data.code == 1) {
                        layer.msg(data.msg);
                        $(obj ).parent().parent().parent().remove();
                    }else{
                        layer.msg(data.msg);
                    }
                }
            });
        }
        layer.close(index);
    });
    return false;
}