$(function(){
    // 辣程度的初始化选中
    $('#hots').children('span').each(function(){
        if($(this).data('index') == $('#hot_level').val()){
            $(this).addClass('active');
        };
    });

    // 是否点赞
   /* if($('#star_level').val() == 1){
        $('#point_good').addClass('active');
    }*/
    if($('#dianzan_value').val() == 1){
        $('#point_good').addClass('active');
    }
});

function save_food(food_id) {
    var hschek = $(".is_prom").is(':checked');
    if (hschek) {
        status = 1;
    }else{
        status = 0;
    }

    var food_name = $("input[name='food_name']").val();
    var food_price = $("input[name='food_price']").val();
    var foods_num_day = $("input[name='foods_num_day']").val();
    var sort1 = $("input:checkbox[name='sort1[]']:checked").length;
    var print_id = $("#print_id").children('option').length;
    var is_prom = status;
    var prom_price = $("input[name='prom_price']").val();
    if (!(food_name && food_price && foods_num_day)) {
        layer.msg("星号项不能为空");
    } else if (!sort1 > 0) {
        layer.msg("没有选择菜品分类");
    } else if (print_id == 0) {
        layer.msg("没有打印机，请选对接打印机!");
    } else {
        var formData = new FormData($("#food_info")[0]);
        $.ajax({
            url: "/index.php/MobileAdmin/Dishes/modifyfoodinfo/food_id/"+food_id,
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
                location.href = '/index.php/MobileAdmin/Dishes/food_set/food_category_id/' + data.first_sort;
            },
            error: function () {
             alert("出错了");
             }
        });
    }
}
