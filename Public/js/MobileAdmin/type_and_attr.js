function add_type(){
    var print_list;
    $.ajax({
        url:"/index.php/MobileAdmin/dishes/get_print",
        dataType:"json",
        success:function(msg){
            append_str(msg);
        },
        error:function(){
            alert('出错了');
        }
    });
}

function append_str(return_print){
    var cook_option = '';
    var tag_option = '';
    $.each(return_print, function (k,v) {
        if( v.print_type != 2){
            cook_option += '<option value="'+v.printer_id+'">'+v.printer_name+'</option>';
        }else if(v.print_type == 2){
            tag_option += '<option value="'+v.printer_id+'">'+v.printer_name+'</option>';
        }
    })

    var str = '<section class="section">\
                    <form action="javascript:void(0)">\
                        <div class="flex-content vertical-flex section-row">\
                            <div class="flex-main">\
                                <span class="text-6">规格分类名称：</span>\
                                <input type="text" class="input-sm" name="type_name" placeholder="例：饮料">\
                            </div>\
                            <button class="default-btn" onclick="keep_type(this,1)">保存</button>\
                            <input type="hidden" name="attribute_type_id" value=""/>\
                            <button class="default-btn" onclick="del_type(this,1)">删除</button>\
                        </div>\
                        <div class="section-row">\
                            <span class="text-6">厨房打印机：</span>\
                            <div class="select-reset">\
                                <select name="print_id">\
                                    <option value="0">不设打印</option>'+cook_option+
                                '</select>\
                            </div>\
                        </div>\
                        <div class="section-row">\
                            <span class="text-6">标签打印机：</span>\
                            <div class="select-reset">\
                                <select name="tag_print_id">\
                                    <option value="0">不设打印</option>'+tag_option+
                                '</select>\
                            </div>\
                        </div>\
                        <div class="section-row">\
                            <span class="text-6">统计：</span>\
                            <div class="checkbox-switch">\
                                <input type="hidden" name="count_type" value="0"/>\
                                <input type="checkbox" name="count_types" onchange="change_status(this)">\
                                <label></label>\
                            </div>\
                        </div>\
                        <div class="section-row">\
                            <span class="text-6">选择：</span>\
                            <label class="radio">\
                                <input type="radio" name="select_type" value="0" checked>\
                                <i class="circle-icon"></i>\
                                <span>单选</span>\
                            </label>\
                            <label class="radio">\
                                <input type="radio" name="select_type" value="1">\
                                <i class="circle-icon"></i>\
                                <span>多选</span>\
                            </label>\
                        </div>\
                        <hr class="hr">\
                        <button class="danger-btn" data-attribute_type_id = "" data-if_save = "0" onclick="add_attr(this)" type="button">\
                            <i class="iconfont icon-plus"></i>\
                        </button>\
                    </form>\
                </section>\
            ';
    $("#list").append(str);
}

function add_attr(obj){
    var if_save = $(obj).data('if_save');
    var attribute_type_id = $(obj).data('attribute_type_id');
    if(if_save == 0){
        layer.msg('请先保存规格分类信息');
        return false;
    }
    var attr_str = '<form action="javascript:void(0)">\
                        <div class="flex-content vertical-flex section-row">\
                            <span>规格：</span>\
                            <input type="text" class="input flex-main" name="attribute_name" placeholder="例:可乐">\
                            <span>+</span>\
                            <input type="number" pattern="[0-9]*" class="input-xs" name="attribute_price" placeholder="价格">\
                            <span class="text-2 text-center">元</span>\
                            <button class="default-btn" data-food_attribute_id="" data-attribute_type_id="'+attribute_type_id+'" onclick="keep_attr(this,1)">保存</button>\
                            <button class="default-btn" onclick="del_attr(this)">删除</button>\
                        </div>\
                    </form>';
    $(obj).before(attr_str);
}

// 保存属性类别
function keep_type(obj,division){
    var cate_name = $(obj ).prev().children('input' ).val();
    if(cate_name == ''){
        layer.msg('分类名不能为空');
        return false;
    }

    var formData = new FormData($(obj ).parent().parent()[0]);
    var food_id = $('#food_id').val();
    formData.append("food_id", food_id);

    var attribute_type_id = $(obj).next().val();
    formData.append("attribute_type_id", attribute_type_id);
    $.ajax({
        url: "/index.php/MobileAdmin/Dishes/keep_attr_type",
        type: "post",
        data: formData,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(data) {
            if(data.code == 1){
                layer.msg('保存成功');
                if(division == 1){
                    var last_child = $(obj ).parent().parent().children(":last-child");
                    last_child.data('if_save',1);
                    if(data.attribute_type_id != ''){
                        $(obj).next().val(data.attribute_type_id);
                        last_child.data('attribute_type_id',data.attribute_type_id);
                    }
                }
            }else{
                layer.msg("保存失败");
            }
        },
        error: function () {
            layer.msg("出错了");
        }
    });
}

// 保存属性
function keep_attr(obj,division){
    var attr_name = $(obj ).siblings('input[name="attribute_name"]')[0];
    var attr_price = $(obj ).siblings('input[name="attribute_price"]')[0];
    if(attr_name.value == ''){
        layer.msg('属性名不能为空');
        return false;
    }
    if(attr_price.value == ''){
        layer.msg('属性价格不能为空');
        return false;
    }
    if(isNaN(attr_price.value)){
        layer.msg('属性价格必须为数字');
        return false;
    }

    var formData = new FormData($(obj ).parent().parent()[0]);
    var attribute_type_id = $(obj).data('attribute_type_id');
    formData.append("attribute_type_id", attribute_type_id);

    var food_attribute_id = $(obj).data('food_attribute_id');
    formData.append("food_attribute_id", food_attribute_id);
    $.ajax({
        url: "/index.php/MobileAdmin/Dishes/keep_attr",
        type: "post",
        data: formData,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(data) {
            if(data.code == 1){
                layer.msg('保存成功');
                if(division == 1){
                    if(data.food_attribute_id != ''){
                        $(obj).data('food_attribute_id',data.food_attribute_id)
                    }
                }
            }else{
                layer.msg("保存失败");
            }
        },
        error: function () {
            layer.msg("出错了");
        }
    });
}

function change_status(obj){
    var hschek = $(obj ).is(':checked');
    if(hschek){
        $(obj ).prev().val(1);
    }else{
        $(obj ).prev().val(0);
    }
}

function del_type(obj,division){
    var attribute_type_id = $(obj ).prev().val();
    if(attribute_type_id == ''){
        layer.msg('请先保存规格分类信息');
        return false;
    }

    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        $.ajax({
            url: "/index.php/MobileAdmin/Dishes/del_type",
            type: "post",
            data: {'attribute_type_id':attribute_type_id},
            dataType: "json",
            success: function(data) {
                if(data.code == 1){
                    layer.msg('删除成功');
                    if(division==1){
                        $(obj).parent().parent().remove();
                    }else{
                        $(obj).parent().parent().parent().remove();
                    }
                }else{
                    layer.msg("删除失败");
                }
            },
            error: function () {
                layer.msg("出错了");
            }
        });
        layer.close(index);
    });
}

// 删除属性
function del_attr(obj){
    var food_attribute_id = $(obj ).prev().data('food_attribute_id');
    if(food_attribute_id == ''){
        layer.msg('请先保存属性信息');
        return false;
    }

    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        $.ajax({
            url: "/index.php/MobileAdmin/Dishes/del_attr",
            type: "post",
            data: {'food_attribute_id':food_attribute_id},
            dataType: "json",
            success: function(data) {
                if(data.code == 1){
                    layer.msg('删除成功');
                    $(obj).parent().parent().remove();
                }else{
                    layer.msg("删除失败");
                }
            },
            error: function () {
                layer.msg("出错了");
            }
        });
        layer.close(index);
    });
}

function radio_check(obj){
    var last_child = $(obj ).parent().parent().children(":last-child");
    last_child.val($(obj ).val())
}