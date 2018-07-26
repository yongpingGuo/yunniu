function is_open(name,obj) {
    var id = "#" + name;
    var cls = "." + name;
    // var status = $(obj).val();
    var hschek = $(cls).is(':checked');
    if (hschek) {
        status = 1;
    }else{
        status = 0;
    }
    var restaurant_id = $("#restaurant_id").val();
    $.ajax({
        url:"/index.php/MobileAdmin/restaurant/changeBillStatus",
        data:{"name":name,"status":status,"restaurant_id":restaurant_id},
        type:'post',
        dataType:"json",
        success:function(msg){
            if(msg.code == 1){
                if(status == 1){
                    if(name == "take_num"){
                        $("#pay_prompt").show();
                    }
                    if(name == "pay_num"){
                        $("#pay_prompt2").show();
                    }
                    if(name == "qrcode"){

                        $("#forward_prompt").show();
                        $("#desk_num").show();
                    }
                    if(name == "top_logo"){ //上logo显示
                        $("#top_logo").show();
                    }

                    if(name == "next_logo"){ //下logo显示
                        $("#next_logo").show();
                    }
                    $(id).show();
                }else{
                    if(name == "take_num"){
                        $("#pay_prompt").hide();
                    }
                    if(name == "pay_num"){
                        $("#pay_prompt2").hide();
                    }
                    if(name == "qrcode"){
                        $("#forward_prompt").hide();
                        $("#desk_num").hide();
                    }
                    if(name == "top_logo"){ //上logo
                        $("#top_logo").hide();
                    }
                    if(name == "next_logo"){ //上logo
                        $("#next_logo").hide();
                    }
                    $(id).hide();
                }
            }else{
                layer.msg(msg.msg);
            }
        },
        error:function(){
            layer.msg("出错了");
        }
    });
}

function submit_form() {
    var form = $("#restaurant_form2")[0];
    var formData = new FormData(form);
    $.ajax({
        url: "/index.php/MobileAdmin/restaurant/receipt",
        data: formData,
        dataType: 'json',
        type: 'post',
//			async: false,
        cache: false,
        contentType: false,
        processData: false,
        success: function (msg) {
            if (msg.code == 1) {
               layer.msg("成功");
            } else {
                layer.msg("失败");
            }
        },
        error: function () {
            layer.msg("网络出错了");
        }
    });
}

$(function(){
    var restaurant_id = $("#restaurant_id").val();
    $.ajax({
        url:"/index.php/MobileAdmin/restaurant/getBillStatus",
        data:{"restaurant_id":restaurant_id},
        type:'post',
        dataType:"json",
        success:function(msg){
            $.each(msg,function(k,v){
                if(v == 0){
                    if(k == "take_num"){
                        $("#pay_prompt").hide();
                    }
                    if(k == "pay_num"){
                        $("#pay_prompt2").hide();
                    }
                    if(k == "qrcode"){
                        $("#desk_num").hide();
                        $("#forward_prompt").hide();
                    }
                    $("#"+k).hide();
                }
            });
        },
        error:function(){
            layer.msg("出错了");
        }
    });
});

//上传logo
function F_Open_dialog(type)
{
    if(type == 1){
        document.getElementById("top_file").click();  //上logo
    }else{
        document.getElementById("next_file").click(); //下logo
    }

    //上logo
    $('#top_file').change(function(){
        console.log('这是上logo');
        var formData = new FormData();
        formData.append("file", $(this)[0].files[0]);
        formData.append("type", 1);
        $.ajax({
            url:"/index.php/MobileAdmin/restaurant/changeRestaurantBillLogo",
            data:formData,
            type:'post',
            dataType:"json",
            contentType:false,
            processData:false,
            async:false,
            cache:false,
            success:function(msg){
                if(msg.code == 1){
                    layer.alert("图片上传成功")
                    setTimeout(function(){
                        self.location.href = "/index.php/MobileAdmin/restaurant/receipt"
                    }, 2000);
                }
            }
        });

    })

    //下logo
    $('#next_file').change(function(){
        console.log('这是下logo');
        var formData = new FormData();
        formData.append("file", $(this)[0].files[0]);
        formData.append("type", 2);
        $.ajax({
            url:"/index.php/MobileAdmin/restaurant/changeRestaurantBillLogo",
            data:formData,
            type:'post',
            dataType:"json",
            contentType:false,
            processData:false,
            async:false,
            cache:false,
            success:function(msg){
                if(msg.code == 1){
                    layer.alert("图片上传成功")
                    setTimeout(function(){
                        self.location.href = "/index.php/MobileAdmin/restaurant/receipt"
                    }, 2000);

                }
            }
        });

    })
}
