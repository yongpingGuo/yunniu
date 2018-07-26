<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" href="/Public/css/login.css?20180124">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="login-wrapper" v-cloak>
        <header class="login-header">
            <img src="/Public/images/admin_logo.png">
            <span>方派店铺后台</span>
            <!--<span>{{langData.founpadAdmin[lang]}}</span>-->
        </header>
        <div class="login-content flex-content">
            <form id="myform" class="login-left">
                <h3 class="login-title">欢迎登录</h3>
                <input class="input" type="text" name="login_account" value="<?php echo ($login_account); ?>" placeholder="用户名">
                <input class="input" type="password" name="password" value="<?php echo ($password); ?>" placeholder="密码">
                <label class="checkbox-content">
                    <?php if($autoFlag == 1): ?><input type="checkbox" value="1" name="autoFlag" checked="checked" />
                        <?php else: ?>
                        <input type="checkbox" value="1" name="autoFlag" /><?php endif; ?>
                    <span>记住密码</span>
                </label>
                <div class="code-content flex-content flex-justify">
                    <input type="text" name="code" placeholder="验证码">
                    <img class="code-img" src="/index.php/Admin/Index/verifyImg" onclick="this.src='/index.php/Admin/Index/verifyImg/'+Math.random()">
                </div>
                
                <button class="form-control login-btn" type="button" id="loginBtn" onclick="commit()">登录</button>
                <input type="reset" id="reset" style="display: none;" />
            </form>
            <div class="login-right">
                <img src="/Public/images/app.jpg">
                <div>手机APP下载</div>
            </div>
        </div>
    </div>
    <script src="/Public/js/vue.js"></script>
    <script src="/Public/language.json"></script>
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    <script src="/Public/layer/layer.js"></script>
    <script src="/Public/js/Admin/common.js"></script>
    <script type="text/javascript">
    var vm = new Vue({
        el: "#lang-content",
        data: {
            lang: language,
            langData: langData
        }
    })
    var cloud = sessionStorage.getItem("cloud");
    if (cloud == 1) {
        $('body').css('height', $(window).height() / 2);
    }

    function loginHeight() {
        if ($('.login').height() > $('body').height()) {
            $('body').css('overflow', 'auto');
        } else {
            $('body').css('overflow', 'hidden');
        }
    }
    loginHeight();
    $(window).resize(function(event) {
        loginHeight();
    });
    $(document).keyup(function(event) {
        if (event.keyCode == 13) {
            commit();
        }
    });

    function commit() {
        var login_account = $("input[name='login_account']").val();
        var password = $("input[name='password']").val();
        var code = $("input[name='code']").val();
        var autoFlag = $("input[type='checkbox']").is(':checked');
        var login_way = 0; //登录入口(0:从后台路径登录，1：从收银端登录)
        if (autoFlag == true) {
            autoFlag = 1;
        } else {
            autoFlag = 0;
        }
        if (login_account && password) {
            $.ajax({
                type: "POST",
                url: "/index.php/admin/index/checklogin",
                async: true,
                data: { "login_account": login_account, "password": password, "code": code, "autoFlag": autoFlag, "login_way": login_way },
                dataType: "json",
                success: function(data) {
                    if (data.code != 1) {
                        layer.msg(vm.langData.verificationCodeError[vm.lang]);
                        $(".code-img").trigger('click');
                        $('input[name="code"]').val('');
                    } else {
                        sessionStorage.setItem("id", data.id);
                        //                    top.location.href = "/index.php/admin/index";
                        top.location.href = "/index.php/Admin/Index/index.html";

                    }
                }
            });
        } else {
            layer.msg(vm.langData.notEmpty[vm.lang])
        }
    }
    </script>
</body>

</html>