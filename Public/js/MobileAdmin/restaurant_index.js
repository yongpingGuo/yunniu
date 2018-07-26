function submit_form(){
    var password = $("input[name='password']").val();
    var passwords = $("input[name='passwords']").val();
    if(password === passwords){
        var form = $("#restaurant_form")[0];
        var formData = new FormData(form);
        $.ajax({
            url:"/index.php/MobileAdmin/restaurant/index",
            data:formData,
            dataType:'json',
            type:'post',
            //			async: false,
            cache: false,
            contentType: false,
            processData: false,
            success:function(msg){
                if(msg.code == 1){
                    layer.msg('操作成功');
                    setTimeout('jump()',1000);
                }else{
                    layer.msg('操作失败');
                }
            },
            error:function(){
                layer.msg('网络出错了');
            }
        });
    }else{
        layer.msg("两次密码不一致");
    }
}

function jump(){
    location.href = '/index.php/MobileAdmin/index/index';
}

//图片上传预览
function previewImage(event)
{
    var file;
    if (typeof event.target === 'undefined') file = event.target[0];
    else file = event.target.files[0];

    if (!file || !window.FileReader){
        layer.msg('浏览器不支持上传，请换个浏览器试试');
        return;
    }

    if (/^image/.test(file.type)) {
        var size = Math.floor(file.size / 1024);
        if (size > 1024*6) {
            layer.msg("文件大小不能超过6M");
            return false;
        }
        var img = document.getElementById('imghead');
        var reader = new FileReader();
        reader.onload = function(evt){
            img.src = evt.target.result;
        }
        reader.readAsDataURL(file);
    }
    else{
        layer.msg('上传的不是图片');
        return false;
    }
    var formData = new FormData();
    formData.append("file",file);
    $.ajax({
        url:"/index.php/MobileAdmin/restaurant/changeRestaurantLogo",
        data:formData,
        type:'post',
        dataType:"json",
        contentType:false,
        processData:false,
        async:false,
        cache:false,
        success:function(msg){
            if(msg.code == 1){
                layer.msg("logo替换成功")
            }
        }
    });
}