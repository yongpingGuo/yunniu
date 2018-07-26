/**
 * Created by Administrator on 2016/11/16.
 */
function submitPayInfo(obj){
    var type = $(obj).data("paytype");
    console.log(type);
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
    console.log(formData);
    $.ajax({
        url:"/index.php/admin/DataDock/editAddPayInfo/type/"+type,
        data:formData,
        type:'post',
        dataType:'json',
        contentType:false,
        cache:false,
        processData:false,
        success:function(data){
            layer.msg(vm.langData.success[vm.lang]);
        },
        error:function(){
            layer.msg(vm.langData.networkError[vm.lang]);
        }
    });
}

function submitPayInfos(obj){
    var formData;
    var formName3 = "othersForm";
    var form3 = $("#"+formName3)[0];
    formData = new FormData(form3);

    $.ajax({
        url:"/index.php/admin/DataDock/editAddPayInfos",
        data:formData,
        type:'post',
        dataType:'json',
        contentType:false,
        cache:false,
        processData:false,
        success:function(data){
            if(data == 1){
                layer.msg(vm.langData.success[vm.lang]);
            }else{
                layer.msg(vm.langData.failed[vm.lang]);
            }
        }
    });
}