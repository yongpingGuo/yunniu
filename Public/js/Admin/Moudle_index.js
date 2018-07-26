//横屏广告的预览+上传(预览时就上传)
function preview1(file) {
    var prevDiv = $(file).parent()[0]; //获取上传图片父级所在的DOM对象
    var statu = $(file).siblings('img').attr('src'); //获取广告位状态（空或已有广告）
    if ($(file).siblings('img').attr('data-img') == "add") {
        statu = "";
    }
    var aid = $(file).parent().attr('id'); //当前广告ID
    var wtype = $(file).attr('name'); //广告位类型(默认或动态广告位)
    //------------------------------------广告位的广告预览-----------------------------------------
    if (file.files && file.files[0]) {
        var reader = new FileReader();
        reader.onload = function(evt) {
            prevDiv.innerHTML = '<img src="' + evt.target.result + '" class="uploadImg" />';
        }
        reader.readAsDataURL(file.files[0]);
    } else {
        prevDiv.innerHTML = '<div class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
    }
    //------------------------------------广告位上传广告--------------------------------------------
    var formdata = new FormData();
    formdata.append("file", file.files[0]); //上传文件
    formdata.append("wtype", wtype); //广告位类型，第一个广告位是默认，第二个广告位是可变
    formdata.append("aid", aid) //广告id
    formdata.append("statu", statu); //广告图片src值
    $.ajax({
        type: 'post',
        url: '/index.php/admin/Moudle/uploadimg',
        data: formdata,
        cache: false,
        processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
        contentType: false, // 不设置Content-type请求头
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang]);
            $('#mytr').html(data);
        }
    });
}

//竖屏广告的预览+上传(预览时就上传)
function preview(file) {
    var prevDiv = $(file).parent()[0];
    var statu = $(file).siblings('img').attr('src'); //获取广告位状态（空或已有广告）
    if ($(file).siblings('img').attr('data-img') == "add") {
        statu = "";
    }
    var aid = $(file).parent().attr('id');
    var wtype = $(file).attr('name');
    if (file.files && file.files[0]) {
        var reader = new FileReader();
        reader.onload = function(evt) {
            prevDiv.innerHTML = '<img src="' + evt.target.result + '" class="uploadImg" />';
        }
        reader.readAsDataURL(file.files[0]);
    } else {
        prevDiv.innerHTML = '<div class="img uploadImg" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
    }
    var formdata = new FormData();
    formdata.append("file", file.files[0]); //上传文件
    formdata.append("wtype", wtype); //广告位类型，第一个广告位是默认，第二个广告位是可变
    formdata.append("aid", aid) //广告id
    formdata.append("statu", statu); //广告图片src值
    $.ajax({
        type: 'post',
        url: '/index.php/admin/Moudle/uploadphimg',
        data: formdata,
        cache: false,
        processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
        contentType: false, // 不设置Content-type请求头
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang]);
            $("#mytr1").html(data);
        }
    });
}

//删除横屏广告
function deladver(z) {
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "post",
            url: "/index.php/admin/Moudle/deladver",
            data: { "advertisement_id": z },
            success: function(data) {
                $("#mytr").html(data);
            }
        });
        layer.close(index);
    });
}


//删除竖屏广告
function deladver1(z) {
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "post",
            url: "/index.php/admin/Moudle/deladver1",
            data: { "advertisement_id": z },
            success: function(data) {
                $("#mytr1").html(data);
            }
        });
        layer.close(index);
    });
}

//改变流程页开启或关闭状态
function changestatu(obj, i) {
    var clsv = $(obj).attr('class');
    var cls = "." + clsv;
    // var status = $(obj).val();
    var hschek = $(cls).is(':checked');
    if (hschek) {
        status = 1;
    } else {
        status = 0;
    }
    $.ajax({
        type: "get",
        url: "/index.php/admin/Moudle/modifyprocess",
        data: { "id": i, "status": status },
        dataType: "json",
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang]);
        }
    });
}

//每张广告的间隔时间
function changetime() {
    var time = document.getElementById('interval').value;
    $.ajax({
        type: "post",
        url: "/index.php/admin/moudle/timeSettings",
        data: { "advertise_time": time },
        dataType: "json",
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang] + "!" + vm.langData.pictureIntervals[vm.lang] + data[0]['advertise_time'] + vm.langData.seconds[vm.lang]);
        }
    });
}

//改变默认广告语
function changeadvlang() {
    var value = $("input[name='advlang']").val();
    $.ajax({
        type: "post",
        url: "/index.php/admin/moudle/adv_langSet",
        data: { "adv_language": value },
        dataType: "json",
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang] + "!" + vm.langData.OrdersuccessfulHint[vm.lang] + data['adv_language']);
        }
    });
}

//改变默认竖屏广告语
function changeShuPingLang() {
    var value = $("input[name='shuping_adv_lang']").val();
    $.ajax({
        type: "post",
        url: "/index.php/admin/moudle/shuping_adv_langSet",
        data: { "shuping_adv_language": value },
        dataType: "json",
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang]);
        }
    });
}
// 双屏客显广告上传
function doubleDisplay(file) {
    var prevDiv = $(file).parent()[0];
    var statu = $(file).siblings('img').attr('src'); //获取广告位状态（空或已有广告）
    if ($(file).siblings('img').attr('data-img') == "add") {
        statu = "";
    }
    var aid = $(file).parent().attr('id');
    var wtype = $(file).attr('name');
    if (file.files && file.files[0]) {
        var reader = new FileReader();
        reader.onload = function(evt) {
            prevDiv.innerHTML = '<img src="' + evt.target.result + '" class="uploadImg" />';
        }
        reader.readAsDataURL(file.files[0]);
    } else {
        prevDiv.innerHTML = '<div class="img uploadImg" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
    }
    var formdata = new FormData();
    formdata.append("file", file.files[0]); //上传文件
    formdata.append("wtype", wtype); //广告位类型，第一个广告位是默认，第二个广告位是可变
    formdata.append("aid", aid) //广告id
    formdata.append("statu", statu); //广告图片src值
    $.ajax({
        type: 'post',
        url: '/index.php/admin/Moudle/uploadsnimg',
        data: formdata,
        cache: false,
        processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
        contentType: false, // 不设置Content-type请求头
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang]);
            $("#mytr88").html(data);
        }
    });
}

//删除双屏客显屏广告
function deladver88(z) {
    layer.confirm('', {
            title: vm.langData.deleteConfirm[vm.lang],
            btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
        }, function(index) {
            $.ajax({
                type: "post",
                url: "/index.php/admin/Moudle/deladver88",
                data: { "advertisement_id": z },
                success: function(data) {
                    $("#mytr88").html(data);
                }
        });
        layer.close(index);
    });
}



//改变默认双屏客显屏广告语
function changeDoubleLang() {
    var value = $("input[name='double_display']").val();
    $.ajax({
        type: "post",
        url: "/index.php/admin/moudle/double_adv_langSet",
        data: { "double_adv_language": value },
        dataType: "json",
        success: function(data) {
            layer.msg(vm.langData.success[vm.lang]);
        }
    });
}