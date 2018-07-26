// 图片大小设置
    function previewSize(){
        $('.dish-img-preview').height(function(){
            return $(this).width()*0.6;
        });
    }
    previewSize();
    $(window).resize(function(event) {
        previewSize();
    });

// 口味
var index;
$(".cayenne span").click(function(event) {
    if($(this).data('index')==1&&$(this).attr('class')=="active"){
        $('#notSpicy').addClass('active').siblings().removeClass('active');
        index=0;
    }
    else{
        index=$(this).data('index');
        $(this).addClass('active').siblings().removeClass('active');
    }
    // console.log(index);
});

function click_hot_level(obj){
    $('#hot_level').val($(obj).data('index'));
    $(obj).addClass('active').siblings().removeClass('active');
}

function point_zan(obj){
    if($(obj).attr('class') == "like-btn active"){
        $(obj).removeClass('active');
        $('#star_level' ).val(0);
        $('#dianzan_value' ).val(0);
    }else{
        $(obj).addClass('active');
        $('#star_level' ).val(1);
        $('#dianzan_value' ).val(1);
    }
}

function preview(file) {
    var prevDiv = document.getElementById('preview');
    var picinfo = file.files[0]; //input
    if( picinfo.size > 6*1024*1024 ){  //用size属性判断文件大小不能超过5M
        layer.msg( "您上传的文件超过6M！" ) ;
        $("input[name='food_pic']").val('');
        prevDiv.innerHTML = '';
        return false;
    }
    if (file.files && file.files[0]) {
        var reader = new FileReader();
        reader.onload = function (evt) {
            prevDiv.innerHTML = '<img src="' + evt.target.result + '"/>';
        }
        reader.readAsDataURL(file.files[0]);
    }
}

function belong_category(obj){
    var hschek = $(obj ).children().is(':checked');
    if(!hschek){
        $(obj ).children().prop("checked",true);
        $(obj ).addClass('active');
    }else{
        $(obj ).children().prop("checked",false);
        $(obj ).removeClass('active');
    }
}

$("#save_food").click(function() {
    var hschek = $(".is_prom").is(':checked');
    if (hschek) {
        status = 1;
    }else{
        status = 0;
    }

    var img_src = $("input[name='food_pic']").val();
    var food_name = $("input[name='food_name']").val();
    var food_price = $("input[name='food_price']").val();
    var foods_num_day = $("input[name='foods_num_day']").val();
    var sort1 = $("input:checkbox[name='sort1[]']:checked").length;
    var print_id = $("#print_id").children('option').length;
    var save_status = $("#save_status").val();
    var is_prom = status;
    var prom_price = $("input[name='prom_price']").val();
    //var prom_discount = $("input[name='prom_discount']").val();
    var prom_goods_num = $("input[name='prom_goods_num']").val();
    var prom_start_time = $("input[name='prom_start_time']").val();
    var prom_end_time = $("input[name='prom_end_time']").val();
    $("input[name='cayenne']").val(index);
    $("input[name='is_prom']").val(status);

    if (is_prom != 0) {
        if (!(prom_price && prom_goods_num && prom_start_time && prom_end_time)) {
            layer.msg("星号项不能为空");
            return false;
        }
    }
    if (!(food_name && food_price && foods_num_day)) {
        layer.msg('请将菜品信息填写完整');
    } /*else if (!img_src) {
     layer.msg("没有上传菜品图片");
     }*/ else if (!sort1 > 0) {
        layer.msg("没有选择菜品分类");
    } else if (print_id == 0) {
        layer.msg("没有打印机，请选对接打印机!");
    } else {
        var formData = new FormData($("#food_info")[0]);
        $.ajax({
            url: "/index.php/MobileAdmin/dishes/createfoodinfo",
            type: "post",
            data: formData,
            async: true,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend:function(){
                layer.open({
                    type:3,
                    icon:2,
                    skin:"loading"
                });
            },
            success: function(data) {
                layer.closeAll('loading');
                location.href = '/index.php/MobileAdmin/Dishes/food_set/food_category_id/' + data.first_sort;
            },
            error:function(){
                alert("出错了");
            }
        });
    }
});