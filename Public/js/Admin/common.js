/* ---------------------------------------------- /*
 * 用户语言判断
/* ---------------------------------------------- */
var language = "zh-CN";
checkCookie();
function setCookie(value) {
    // 设置cookie
    language = value;
    var d = new Date();
    d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));//Cookie有效期365天
    var expires = "expires=" + d.toGMTString();
    document.cookie = "language=" + value + "; " + expires + "; path=/";
    vm.lang = value;
    layer.msg(vm.langData.success[vm.lang])
    location.reload(); 
}
function getCookie() {
    // 获取cookie
    var name = "language" + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}
function checkCookie() {
    // 检测cookie
    var lang = getCookie("language");
    if (lang != "") {
        language = lang;
    } else {
        //没有获取到语言cookie，js检测浏览器语言，并存到cookie
        var browserLang = navigator.language; //判断除IE外其他浏览器使用语言
        if (!browserLang) browserLang = navigator.browserLanguage;
        if (browserLang.indexOf('zh') > -1) {
            if (browserLang.indexOf('CN') > -1) {
                language = "zh-CN"//简体
            } else {
                language = "zh-TW"//繁体
            }
        } else {
            language = "en"//英文
        }
        setCookie(language);
    }
}
/* ---------------------------------------------- /*
 * 时间戳转换，自定义filter名称为'time'
/* ---------------------------------------------- */
Vue.filter('time', function(value) {
    var date = new Date(parseInt(value) * 1000);
    Y = date.getFullYear(),
        m = date.getMonth() + 1,
        d = date.getDate(),
        H = date.getHours(),
        i = date.getMinutes(),
        s = date.getSeconds();
    if (m < 10) {
        m = '0' + m;
    }
    if (d < 10) {
        d = '0' + d;
    }
    if (H < 10) {
        H = '0' + H;
    }
    if (i < 10) {
        i = '0' + i;
    }
    if (s < 10) {
        s = '0' + s;
    }
    var t = Y + '-' + m + '-' + d + ' ' + H + ':' + i + ':' + s;
    return t;
});
/* ---------------------------------------------- /*
 * 左侧折叠菜单
/* ---------------------------------------------- */
for(var i in $('.sidebar-menu a')){
    var hrefArr=$('.sidebar-menu a')[i].href;
    if (hrefArr) {
        hrefArr=hrefArr.replace(".html", "").toLowerCase();
        // hrefArr=hrefArr.split("Admin")[1];
        if (location.href.toLowerCase().indexOf(hrefArr) >= 0) {
            var eleParent = $($('.sidebar-menu a')[i]).parent();
            eleParent.addClass('active');
            eleParent.parent(".treeview-menu").show();
            break;
        }
    }
}
$(document).ready(function() {
    $('.treeview-header').click(function() {
        $(this).parent().siblings().find('.treeview-menu').stop().slideUp();
        $(this).siblings().stop().slideToggle();
    });
    $('.treeview-item').click(function() {
        $(this).parents().find('.treeview-item').removeClass('active');
        $(this).addClass('active');
    });
});


/* ---------------------------------------------- /*
 * 退出
/* ---------------------------------------------- */
function loginout() {
    //询问框
    layer.confirm('', {
        title: vm.langData.sureSignOut[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "get",
            url: "/index.php/admin/Index/loginout",
            async: true,
            dataType: "json",
            success: function(data) {
                layer.close(index);
                if (data.code == 0) {
                    location.href = "/index.php/admin/Index/login";
                } else {
                    location.href = "/index.php/home/checkstand/admin_login";
                }
            }
        });
    });
}
/* ---------------------------------------------- /*
 * 管理员帐号修改
/* ---------------------------------------------- */
function modify_manager(i) {
    console.log(i);
    $.ajax({
        type: "get",
        url: "/index.php/Admin/index/account_edit/id/" + i + "",
        async: true,
        dataType: "json",
        success: function(data) {
            console.log(data);
            $("input[name='manager_id']").val(data.id);
            $("input[name='manager_account']").val(data.login_account);
            $("input[name='manager_password']").val(data.password);
            $("input[name='manager_passwords']").val(data.password);
        }
    });

}
function update_account() {
    var manager_account = $("input[name='manager_account']").val();
    var manager_password = $("input[name='manager_password']").val();
    var manager_passwords = $("input[name='manager_passwords']").val();
    if (manager_account && manager_password && manager_passwords) {
        if (manager_password == manager_passwords) {
            $.ajax({
                type: "post",
                url: "/index.php/Admin/index/update_account",
                async: true,
                data: $("#myform").serialize(),
                success: function(data) {
                    alert(data.msg);
                    if (data.code == 1) {
                        $('#edit-user').modal('hide');
                        $("#account").html(data.data);
                    }
                },
            });
        } else {
            alert("密码不一致");
        }
    } else {
        alert("所显示项不能为空!")
    }
}