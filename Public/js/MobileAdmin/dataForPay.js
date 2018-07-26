$(function(){
    var mode = $('#hidden_mode' ).val();
    $('input[name="mode"]' ).val([mode])
});

function is_mode(obj) {
    var status = $(obj).val();
    $.ajax({
        url: "/index.php/MobileAdmin/dataDock/selectMode",
        data: {
            "mode": status
        },
        dataType: "json",
        type: "post",
        success: function(data) {
           if(data == 1){
               layer.msg('修改状态成功');
           }else{
               layer.msg('修改状态失败');
           }
        },
        error:function(){
            layer.msg("出错了");
        }
    });
}

function is_open(obj) {
    var config_name = $(obj).attr("name");
    var hschek = $(obj).is(':checked');
    if (hschek) {
        status = 1;
    }else{
        status = 0;
    }
    $.ajax({
        url: "/index.php/MobileAdmin/dataDock/selectPay",
        data: {
            "value": status,
            "config_name": config_name
        },
        type: "post",
        success: function(data) {
            if(data == 1){
                layer.msg('修改状态成功');
            }else{
                layer.msg('修改状态失败');
            }
        },
        error:function(){
            layer.msg("出错了");
        }
    });
}

function submitPayInfo(obj){
    var type = $(obj).data("paytype");
    var formData;
    if(type == 'wxpay'){
        var formName1 = "wxpayForm";
        var form1 = $("#"+formName1)[0];
        formData = new FormData(form1);
    }else if(type == 'others'){
        var formName3 = "othersForm";
        var form3 = $("#"+formName3)[0];
        formData = new FormData(form3);
    }else{
        var formName2 = "alipayForm";
        var form2 = $("#"+formName2)[0];
        formData = new FormData(form2);
    }
    $.ajax({
        url:"/index.php/MobileAdmin/DataDock/editAddPayInfo/type/"+type,
        data:formData,
        type:'post',
        dataType:'json',
        contentType:false,
        cache:false,
        processData:false,
        success:function(data){
            if(data == 1){
                layer.msg('保存成功');
            }else{
                layer.msg('保存失败');
            }
        },
        error:function(){
            layer.msg('网络出错了');
        }
    });
}

function submitPayInfo_fourth(obj){
    var formData;
    var formName3 = "othersForm";
    var form3 = $("#"+formName3)[0];
    formData = new FormData(form3);

    $.ajax({
        url:"/index.php/MobileAdmin/DataDock/editAddPayInfos",
        data:formData,
        type:'post',
        dataType:'json',
        contentType:false,
        cache:false,
        processData:false,
        success:function(data){
            if(data == 1){
                layer.msg('保存成功');
            }else{
                layer.msg('保存失败');
            }
        },
        error:function(){
            layer.msg("网络出错了");
        }
    });
}

function create_qr_code(obj){
    var type = $(obj ).data('type');
    $.ajax({
        url:"/index.php/MobileAdmin/DataDock/create_pay_test_qrc",
        data:{'type':type},
        type:'post',
        dataType:'json',
        success:function(data){
            if(data.code == 1){
                $(obj ).siblings('img').attr('src', data.qr_code_url).css('display','block');
                //$(obj ).siblings('img').attr('src', '/Public/images/pay_03.png').css('display','block');
            }else{
                layer.msg('生成二维码失败');
            }
        },
        error:function(){
            layer.msg("网络出错了");
        }
    });
}
