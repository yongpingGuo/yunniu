//改变票据底部美团广告语
function changeadvlang_meituan(){
    var value = $("#bill_foot_language").val();
    if(!value){
        layer.msg("广告语不能为空");
        return false;
    }
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/Waimai/adv_langSet",
        data:{"bill_foot_language":value,type:'meituan'},
        dataType:"json",
        success:function(data){
            layer.msg("修改成功，当前广告语："+data);
        }
    });
}
//改变票据底部饿了么广告语
function changeadvlang_eleme(){
    var value = $("#eleme_bill_foot_language").val();
    if(!value){
        layer.msg("广告语不能为空");
        return false;
    }
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/Waimai/adv_langSet",
        data:{"eleme_bill_foot_language":value,type:'eleme'},
        dataType:"json",
        success:function(data){
            layer.msg("修改成功，当前广告语："+data);
        }
    });
}
$('#meituan_grant').click(function(){
    var url = $('#grant_url').val();
    window.open(url);
});

$('#eleme_grant').click(function(){
    var if_grant = $('#if_grant').val();
    /*if(if_grant == 2){
     layer.msg("该店铺已经授权");
     return false;
     }*/
    var auth_url = $('#eleme_grant_url').val();
    window.open(auth_url);
});

$('#meituan_unbind').click(function(){
    var has_bind = $('#has_bind' ).val();
    if(has_bind == 0){
        layer.msg('该店铺还未绑定美团外卖');
        return false;
    }
    var url = $('#unbind_url').val();
    window.open(url);
});