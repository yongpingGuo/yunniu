<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <div class="restaurant-info">
        <form action="javascript:void(0)" id="restaurant_form">
            <input type="hidden" value="<?php echo ($Restaurant["restaurant_id"]); ?>" name="restaurant_id">
            <section class="section">
                <div class="section-header">{{langData.logo[lang]}}</div>
                <div class="section-content">
                    <div class="logo-preview" id="preview">
                        <img id="imghead" src="<?php echo ($Restaurant["logo"]); ?>">
                    </div>
                    <div class="logo-upload">
                        <div class="file-content blue-btn">
                            <span>{{langData.upload[lang]}}</span>
                            <input type="file" onchange="previewImage(event)">
                        </div>
                        <div class="section-tips">{{langData.logoUploadTips[lang]}}</div>
                    </div>
                </div>
            </section>
            <section class="section">
                <div class="section-header">{{langData.information[lang]}}</div>
                <div class="section-content">
                    <table class="table-condensed">
                        <tbody>
                            <tr>
                                <td>
                                    <span class="text-danger">*</span>
                                    <span>{{langData.shopName[lang]}}</span>
                                </td>
                                <td>
                                    <input type="text" value="<?php echo ($Restaurant["restaurant_name"]); ?>" id="restaurant_name" name="restaurant_name" :placeholder="langData.shopName[lang]">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.takeAwayTel[lang]}}1：</td>
                                <td>
                                    <input type="text" value="<?php echo ($Restaurant["telephone1"]); ?>" id="telephone1" name="telephone1" :placeholder="langData.takeAwayTel[lang]">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.takeAwayTel[lang]}}2：</td>
                                <td>
                                    <input type="text" value="<?php echo ($Restaurant["telephone2"]); ?>" id="telephone2" name="telephone2" :placeholder="langData.takeAwayTel[lang]">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.address[lang]}}：</td>
                                <td>
                                    <input type="text" value="<?php echo ($Restaurant["address"]); ?>" id="address" name="address" :placeholder="langData.address[lang]" class="larger-input">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.account[lang]}}：</td>
                                <td>
                                    <input type="text" value="<?php echo ($object["login_account"]); ?>" name="login_account" disabled="disabled">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="text-danger">*</span>
                                    <span>{{langData.password[lang]}}：</span>
                                </td>
                                <td>
                                    <input type="password" value="<?php echo ($object["password"]); ?>" name="password">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="text-danger">*</span>
                                    <span>{{langData.confirmPassword[lang]}}：</span>
                                </td>
                                <td>
                                    <input type="password" value="<?php echo ($object["password"]); ?>" name="passwords">
                                </td>
                            </tr>
                             <tr>
                                <td>{{langData.erp_card[lang]}}：</td>
                                <td>
                                    <input type="text" value="<?php echo ($Restaurant["erp_card"]); ?>" id="erp_card" name="erp_card" :placeholder="langData.ERPAnnotation[lang]"><span>(非长青腾商家，无须设置)</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
		    <section class="section">
				<div class="section-header"><span>{{langData.modeChange[lang]}}</span></div>
				<div class="section-content">
					<div class="mode-item">
						<input type="radio" name="push_type" value="1" <?php if($Restaurant["push_type"] == '1'): ?>checked<?php endif; ?> />
						<span class="input-text">{{langData.modeType1[lang]}}</span>
					</div>
					<div class="mode-item">
						<input type="radio" name="push_type" value="2" <?php if($Restaurant["push_type"] == '2'): ?>checked<?php endif; ?>/>
						<span class="input-text">{{langData.modeType2[lang]}}</span>
					</div>
					<div class="mode-item">
						<input type="radio" name="push_type" value="3" <?php if($Restaurant["push_type"] == '3'): ?>checked<?php endif; ?>/>
						<span class="input-text">{{langData.modeType3[lang]}}</span>
					</div>
				</div>
			</section>
		</form>
		<div class="text-center">
            <button class="blue-btn" onclick="submit_form()">{{langData.save[lang]}}</button>
        </div>
    </div>

        </div>
        
        
    </div>
    <script src="/Public/js/vue.js"></script>
    <script src="/Public/language.json?v=20180428"></script>
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    <script src="/Public/bootstrap/js/bootstrap.min.js"></script>
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
        </script>
    
    <!-- 自定义js -->
    
    <script>
	$(".mode-item").click(function(){   //模式切换
		$(".mode-item").find('input').prop("checked",false);
		$(this).find('input').prop("checked",true);
	});
    function submit_form() {
        var password = $("input[name='password']").val();
        var passwords = $("input[name='passwords']").val();
        var pushType = $("input[name='push_type']:checked").val();
        
        if (password === passwords) {
            var form = $("#restaurant_form")[0];
            var formData = new FormData(form);
            $.ajax({
                url: "/index.php/admin/restaurant/index",
                data: formData,
                dataType: 'json',
                type: 'post',
                //			async: false,
                cache: false,
                contentType: false,
                processData: false,
                success: function(msg) {
                    if (msg.code == 1) {
                        layer.msg(vm.langData.success[vm.lang]);
                    } else {
                        layer.msg(vm.langData.failed[vm.lang]);
                    }
                },
                error: function() {
                    layer.msg(vm.langData.networkError[vm.lang]);
                }
            });
        } else {
            layer.msg(vm.langData.psdMatch[vm.lang]);
        }
    }


    //图片上传预览
    function previewImage(event) {
        var file;
        console.log(event);
        if (typeof event.target === 'undefined') file = event.target[0];
        else file = event.target.files[0];

        if (!file || !window.FileReader) {
            layer.msg(vm.langData.browserUpload[vm.lang]);
            return;
        }

        if (/^image/.test(file.type)) {
            var img = document.getElementById('imghead');
            var reader = new FileReader();
            reader.onload = function(evt) {
                img.src = evt.target.result;
            }
            reader.readAsDataURL(file);
        } else {
            layer.msg(vm.langData.imageType[vm.lang]);
            return false;
        }
        var formData = new FormData();
        formData.append("file", file);
        $.ajax({
            url: "/index.php/admin/restaurant/changeRestaurantLogo/",
            data: formData,
            type: 'post',
            dataType: "json",
            contentType: false,
            processData: false,
            async: false,
            cache: false,
            success: function(msg) {
                if (msg.code == 1) {
                    layer.msg(vm.langData.uploadSuccess[vm.lang])
                }
            }
        });
    }
    </script>

</body>

</html>