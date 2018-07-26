function change(e,picID) {
    var pic = document.getElementById(picID),
        file = e;

    var ext=file.value.substring(file.value.lastIndexOf(".")+1).toLowerCase();

    // gif在IE浏览器暂时无法显示
    if(ext!='png'&&ext!='jpg'&&ext!='jpeg'){
        file.value = "";
        // alert("图片的格式必须为png或者jpg或者jpeg格式！");
        return;
    }
    var isIE = navigator.userAgent.match(/MSIE/)!= null,
        isIE6 = navigator.userAgent.match(/MSIE 6.0/)!= null;

    if(isIE) {
        file.select();
        var reallocalpath = document.selection.createRange().text;

        // IE6浏览器设置img的src为本地路径可以直接显示图片
        if (isIE6) {
            pic.src = reallocalpath;
        }else {
            // 非IE6版本的IE由于安全问题直接设置img的src无法显示本地图片，但是可以通过滤镜来实现
            pic.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod='image',src=\"" + reallocalpath + "\")";
            // 设置img的src为base64编码的透明图片 取消显示浏览器默认图片
            pic.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
        }
    }else {
        var file = file.files[0];
        var reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(e){
            pic.src=this.result;
        }
    }
}

/**
 * upLoad picture
 * @param fileInput <input type="file">
 * @param success   success(data)--data is path
 * @param error     error(data)  --data is xmlObject
 */
function upLoadPic(fileInput, success, error) {
    var xml = new XMLHttpRequest();
    if(xml==undefined) {
        // layer.msg("浏览器不支持");
    } else {
        var file = fileInput[0].files[0];
        if(file==undefined) {
            // layer.msg("请选择图片")
        } else {
            var picForm = new FormData;
            picForm.append("file", file);
            xml.onload = function(data) {
                var info = data.srcElement;
                if(info.readyState == 4 && info.status == 200) {
                    info = JSON.parse(info.responseText);
                    if(info.ret==100) {
                        success(info.info);
                    } else {
                        error(data);
                    }
                } else {
                    error(data);
                }
            };
            xml.open("POST","/upload/pic.html");
            xml.send(picForm);
        }
    }
}