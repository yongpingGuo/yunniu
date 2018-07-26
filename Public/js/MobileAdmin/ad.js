function add_ad(){
    var str = '<div class="section flex-content vertical-flex">'
                     +'<div class="flex-main">'
                        +'<div class="img_horizontal">'
                            +'<img src="">'
                        +'</div>'
                      +'</div>'
                      +'<div class="text-right">'
                          +'<div class="section-row">'
                            +'<div class="file-content">'
                            +'<button class="danger-btn-sm">选择图片</button>'
                            +'<input type="hidden" value=""/>'
                            +'<input type="file" name="change" onchange="preview1(this,1)">'
                            +'</div>'
                          +'</div>'
                      +'<button class="danger-btn-sm" onclick="deladver(1,1,this)">删除图片</button>' 
                      +'</div>'
               +'</div>';
    $('#ad_list' ).append(str);
}

function add_vertical(){
    var str = '<div class="section flex-content vertical-flex">'
              +'<div class="flex-main">'
              +'<div class="img_vertical">'
              +'<img src="">'
              +'</div>'
              +'</div>'
              +'<div class="text-right">'
              +'<div class="section-row">'
              +'<div class="file-content">'
              +'<button class="danger-btn-sm">选择图片</button>'
              +'<input type="hidden" value=""/>'
              +'<input type="file" name="change" onchange="preview(this,1)">'
              +'</div>'
              +'</div>'
              +'<button class="danger-btn-sm" onclick="deladver1(1,1,this)">删除图片</button>'  
              +'</div>'
              +'</div>';
    $('#vertical_list' ).append(str);
}

function preview1(file,type){
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
        url : '/index.php/MobileAdmin/Moudle/uploadimg',
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
            $('#ad_list').html(data);
        }
    });
}

function preview(file,type){
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
        url : '/index.php/MobileAdmin/Moudle/uploadphimg',
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
            $('#vertical_list').html(data);
        }
    });
}

//删除横屏广告
function deladver(z,type,obj){
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        if(type == 1){
            $(obj ).parent().parent().remove();
        }else{
            $.ajax({
                type:"post",
                url:"/index.php/MobileAdmin/Moudle/deladver",
                data:{"advertisement_id":z},
                success:function(data){
                    $("#ad_list").html(data);
                }
            });
        }
        layer.close(index);
    });
    return false;
}

//删除竖屏广告
function deladver1(z,type,obj){
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        if(type == 1){
            $(obj ).parent().parent().remove();
        }else{
            $.ajax({
                type:"post",
                url:"/index.php/MobileAdmin/Moudle/deladver1",
                data:{"advertisement_id":z},
                success:function(data){
                    $("#vertical_list").html(data);
                }
            });
        }
        layer.close(index);
    });
    return false;
}

//每张广告的间隔时间
function changetime(){
    var time  = document.getElementById('interval').value;
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/moudle/timeSettings",
        data:{"advertise_time":time},
        dataType:"json",
        success:function(data){
            if(data.code == 1){
                layer.msg('修改成功');
            }else{
                layer.msg('修改失败');
            }
        }
    });
}

//改变默认双屏客显广告语
function changeDoubleLang(){
    var value = $("input[name='double_adv_lang']").val();
    $.ajax({
        type:"post",
        url:"/index.php/MobileAdmin/moudle/double_adv_langSet",
        data:{"double_adv_language":value},
        dataType:"json",
        success:function(data){
            layer.msg(data);
        }
    });
}

// 添加双屏客显
function add_double(){
    var str = '<div class="section flex-content vertical-flex">'
        +'<div class="flex-main">'
        +'<div class="img_vertical">'
        +'<img src="">'
        +'</div>'
        +'</div>'
        +'<div class="text-right">'
        +'<div class="section-row">'
        +'<div class="file-content">'
        +'<button class="danger-btn-sm">选择图片</button>'
        +'<input type="hidden" value=""/>'
        +'<input type="file" name="change" onchange="preview_double(this,1)">'
        +'</div>'
        +'</div>'
        +'<button class="danger-btn-sm" onclick="deladver1_double(1,1,this)">删除图片</button>'
        +'</div>'
        +'</div>';
    $('#double_list' ).append(str);
}

// 上传双屏客显图片
function preview_double(file,type){
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
        url : '/index.php/MobileAdmin/Moudle/uploadphimg_double',
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
            console.log(data);
            layer.closeAll('loading');
            layer.msg("上传成功！");
            $('#double_list').html(data);
        }
    });
}

//删除双屏客显广告
function deladver1_double(z,type,obj){
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        if(type == 1){
            $(obj ).parent().parent().remove();
        }else{
            $.ajax({
                type:"post",
                url:"/index.php/MobileAdmin/Moudle/deladver1_double",
                data:{"advertisement_id":z},
                success:function(data){
                    $("#double_list").html(data);
                }
            });
        }
        layer.close(index);
    });
    return false;
}




