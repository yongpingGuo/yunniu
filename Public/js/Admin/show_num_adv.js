//叫号屏广告上传
function uploadAd(file){
    var prevDiv = $(file).parent()[0];
    var statu = $(file).siblings('img').attr('src');//获取广告位状态（空或已有广告）
    if($(file).siblings('img').attr('data-img')=="add"){
        statu="";
    }
    var aid =$(file).parent().attr('id');
    var wtype =$(file).attr('name');
    if(file.files && file.files[0]){
        var reader = new FileReader();
        reader.onload = function(evt){
            prevDiv.innerHTML = '<img src="' + evt.target.result + '" class="uploadImg" />';
        }
        reader.readAsDataURL(file.files[0]);
    }else{
        prevDiv.innerHTML = '<div class="img uploadImg" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
    }
    var formdata=new FormData();
    formdata.append("file" , file.files[0]);			//上传文件
    formdata.append("wtype",wtype);						//广告位类型，第一个广告位是默认，第二个广告位是可变
    formdata.append("aid",aid)							//广告id
    formdata.append("statu",statu);						//广告图片src值
    $.ajax({
        type : 'post',
        url : '/index.php/admin/Moudle/uploadJiaohaoImg',
        data : formdata,
        cache : false,
        processData : false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
        contentType : false, // 不设置Content-type请求头
        success : function(data){
            layer.msg(vm.langData.success[vm.lang])
            $("#mytr88").html(data);
        }
    });
}

//删除竖屏广告
function deleteAd(z){
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function() {
        $.ajax({
            type:"post",
            url:"/index.php/admin/Moudle/delQucanAdv",
            data:{"advertisement_id":z},
            success:function(data){
                $("#mytr88").html(data);
                layer.msg(vm.langData.success[vm.lang]);
            }
        });
    });
}