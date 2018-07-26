function add_adv_top(){
    var str = '<div class="section flex-content vertical-flex">'
              +' <div class="flex-main">'
              +'<div class="img-vip-ad">'
              +'<img src="">'
              +'</div>'
              +'</div>'
              +'<div class="text-right">'
              +'<div class="section-row">'
              +'<div class="file-content">'
              +'<button class="danger-btn-sm">选择图片</button>'
              +'<input type="hidden" value=""/>'
              +'<input type="file" name="change" onchange="preview_top(this,1)">'
              +'</div>'
              +'</div>'
              +'<button class="danger-btn-sm" onclick="deladver_top(1,1,this)">删除图片</button>'
              +'</div>'
              +'</div>';
    $('#top_list' ).append(str);
}

function preview_top(file,type){
    var prevDiv = $(file).parent().parent().parent().prev().find('img');
    if(type == 1){
        statu="";
    }else{
        statu="edit"
    }
    var aid =$(file).prev().val();
    var wtype =$(file).attr('name');

    var picinfo = file.files[0];
    if( picinfo.size > 6*1024*1024 ){
        layer.alert( "您上传的文件超过6M！" ) ;
        $(file).val('');
        prevDiv.attr('src','');
        return false;
    }

    if(file.files && file.files[0]){
        var reader = new FileReader();
        reader.onload = function(evt){
            prevDiv.attr('src',evt.target.result);
        }
        reader.readAsDataURL(file.files[0]);
    }else{
        prevDiv.html('<div class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>');
    }

    var formdata=new FormData();
    formdata.append("file" , file.files[0]);
    formdata.append("wtype",wtype);
    formdata.append("aid",aid)
    formdata.append("statu",statu);
    $.ajax({
        type : 'post',
        url : '/index.php/MobileAdmin/Member/uploadimg_top',
        data : formdata,
        cache : false,
        processData : false,
        contentType : false,
        beforeSend:function(){
            layer.open({
                type:3,
                icon:2,
                skin:"loading"
            });
        },
        success : function(data){
            layer.closeAll('loading');
            layer.msg("上传成功！");
            $('#top_list').html(data);
        }
    });
}

function deladver_top(z,type,obj){
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        if(type == 1){
            $(obj ).parent().parent().remove();
        }else{
            $.ajax({
                type:"post",
                url:"/index.php/MobileAdmin/Member/deladver_top",
                data:{"advertisement_id":z},
                success:function(data){
                    $("#top_list").html(data);
                }
            });
        }
        layer.close(index);
    });
    return false;
}


function add_adv_bottom(){
    var str = '<div class="section flex-content vertical-flex">'
              +' <div class="flex-main">'
              +'<div class="img-vip-ad">'
              +'<img src="">'
              +'</div>'
              +'</div>'
              +'<div class="text-right">'
              +'<div class="section-row">'
              +'<div class="file-content">'
              +'<button class="danger-btn-sm">选择图片</button>'
              +'<input type="hidden" value=""/>'
              +'<input type="file" name="change" onchange="preview_bottom(this,1)">'
              +'</div>'
              +'</div>'
              +'<button class="danger-btn-sm" onclick="deladver_bottom(1,1,this)">删除图片</button>'
              +'</div>'
              +'</div>';
    $('#bottom_list' ).append(str);
}

function preview_bottom(file,type){
    var prevDiv = $(file).parent().parent().parent().prev().find('img');
    if(type == 1){
        statu="";
    }else{
        statu="edit"
    }
    var aid =$(file).prev().val();
    var wtype =$(file).attr('name');

    var picinfo = file.files[0];
    if( picinfo.size > 6*1024*1024 ){
        layer.alert( "您上传的文件超过6M！" ) ;
        $(file).val('');
        prevDiv.attr('src','');
        return false;
    }

    if(file.files && file.files[0]){
        var reader = new FileReader();
        reader.onload = function(evt){
            prevDiv.attr('src',evt.target.result);
        }
        reader.readAsDataURL(file.files[0]);
    }else{
        prevDiv.html('<div class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>');
    }

    var formdata=new FormData();
    formdata.append("file" , file.files[0]);
    formdata.append("wtype",wtype);
    formdata.append("aid",aid)
    formdata.append("statu",statu);
    $.ajax({
        type : 'post',
        url : '/index.php/MobileAdmin/Member/uploadimg_bottom',
        data : formdata,
        cache : false,
        processData : false,
        contentType : false,
        beforeSend:function(){
            layer.open({
                type:3,
                icon:2,
                skin:"loading"
            });
        },
        success : function(data){
            layer.closeAll('loading');
            layer.msg("上传成功！");
            $('#bottom_list').html(data);
        }
    });
}

function deladver_bottom(z,type,obj){
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        if(type == 1){
            $(obj ).parent().parent().remove();
        }else{
            $.ajax({
                type:"post",
                url:"/index.php/MobileAdmin/Member/deladver_bottom",
                data:{"advertisement_id":z},
                success:function(data){
                    $("#bottom_list").html(data);
                }
            });
        }
        layer.close(index);
    });
    return false;
}