//改变流程页开启或关闭状态
function changestatu(obj,i){
    var clsv = $(obj).attr('class');
    var cls = "." + clsv;
    // var status = $(obj).val();
    var hschek = $(cls).is(':checked');
    if (hschek) {
        status = 1;
    }else{
        status = 0;
    }
    $.ajax({
        type:"get",
        url:"/index.php/MobileAdmin/Moudle/modifyprocess",
        data:{"id":i,"status":status},
        dataType:"json",
        success:function(data){
            layer.msg("改变状态成功！")
        }
    });
}

//改变下单成功语
function changeadvlang(){
    var value = $("#advlang_textarea").val();
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/moudle/adv_langSet",
        data:{"adv_language":value},
        dataType:"json",
        success:function(data){
            layer.msg("修改成功，当前提示语:"+data['adv_language']);
        }
    });
}
