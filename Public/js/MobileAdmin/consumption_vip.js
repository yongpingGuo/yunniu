function to_bd(a,url){
    var cls = "." + a;
    var hschek = $(cls).is(':checked');
    if (hschek) {
        b = 1;
    }else{
        b = 0;
    }
    // 发送ajax
    $.post(url,{"if_open":b},function(data){
        if(data == 1){
            layer.msg('操作成功');
        }else{
            layer.msg('操作失败');
        }
    });
}