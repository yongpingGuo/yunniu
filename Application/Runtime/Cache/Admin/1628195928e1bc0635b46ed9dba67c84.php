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
            
	<ul class="nav nav-tabs">
		<li class="active">
			<a href="#meituan" data-toggle="tab">{{langData.meituan[lang]}}</a>
		</li>
		<li>
			<a href="#eleme" data-toggle="tab">{{langData.eleme[lang]}}</a>
		</li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane fade in active" id="meituan">
			<div class="section-content">
				<div>
					<span>{{langData.boundShop[lang]}}:</span>
                    <?php if(empty($restaurant_name)):?>
                        <span>{{langData.notBound[lang]}}</span>
                        <?php else: ?>
                            <?php echo ($restaurant_name); ?>
                    <?php endif; ?>
				</div>
                <input type="hidden" value="<?php echo ($url); ?>" id="grant_url"/>
                <input type="hidden" value="<?php echo ($unbind_url); ?>" id="unbind_url"/>
                <input type="hidden" value="<?php echo ($has_bind); ?>" id="has_bind"/>
				<button type="button" class="blue-btn" id="meituan_grant">{{langData.goAuthorization[lang]}}</button>
				<div class="section-tips">{{langData.takeoutadBoundTips[lang]}}</div><br>
                <button type="button" class="blue-btn" id="meituan_unbind" style="background: #FF0000">{{langData.goUnzip[lang]}}</button>
			</div>
			<div class="section-content">
				<span>{{langData.billBottomAds[lang]}}:</span>
				<input type="text" name="bill_foot_language" value="<?php echo ($language_info['bill_foot_language']); ?>" id="bill_foot_language" class="large-input" onchange="changeadvlang_meituan()">      <span class="text-danger">({{langData.takeoutadBoundSaveTips[lang]}})</span>
                <div class="section-tips">{{langData.takeoutadBoundOperatTips[lang]}}</div>
			</div>
		</div>

		<div class="tab-pane fade" id="eleme">
            <div class="section-content">
                <div>
                    <span>{{langData.boundShop[lang]}}:</span>
                    <?php if($display_content['code'] == 1): ?>{{langData.notBound[lang]}}
                        <?php elseif($display_content['code'] == 3): ?>{{langData.reauthorize[lang]}}
                        <?php else: ?> <?php echo ($display_content['msg']); endif; ?>
                </div>
                <input type="hidden" value="<?php echo ($auth_url); ?>" id="eleme_grant_url"/>
                <input type="hidden" value="<?php echo ($grant_situation); ?>" id="if_grant"/>
                <button type="button" class="blue-btn" id="eleme_grant">{{langData.goAuthorization[lang]}}</button>
                <div class="section-tips">{{langData.takeoutadBoundTips[lang]}}</div>
            </div>
            <div class="section-content">
                <span>{{langData.billBottomAds[lang]}}:</span>
                <input type="text" name="eleme_bill_foot_language" value="<?php echo ($language_info['eleme_bill_foot_language']); ?>" id="eleme_bill_foot_language" class="large-input" onchange="changeadvlang_eleme()"><span class="text-danger">({{langData.takeoutadBoundSaveTips[lang]}})</span>
                <div class="section-tips">{{langData.takeoutadBoundOperatTips[lang]}}</div>
            </div>
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
    //改变票据底部美团广告语
    function changeadvlang_meituan(){
        var value = $("#bill_foot_language").val();
        if(!value){
            layer.msg(vm.langData.advertisingSloganNotEmpty[vm.lang]);
            return false;
        }
        $.ajax({
            type:"post",
            url:"/index.php/admin/DataDock/adv_langSet",
            data:{"bill_foot_language":value,type:'meituan'},
            dataType:"json",
            success:function(data){
                layer.tips(vm.langData.success[vm.lang],'#bill_foot_language');
            }
        });
    }
    //改变票据底部饿了么广告语
    function changeadvlang_eleme(){
        var value = $("#eleme_bill_foot_language").val();
        if(!value){
            layer.msg(vm.langData.advertisingSloganNotEmpty[vm.lang]);
            return false;
        }
        $.ajax({
            type:"post",
            url:"/index.php/admin/DataDock/adv_langSet",
            data:{"eleme_bill_foot_language":value,type:'eleme'},
            dataType:"json",
            success:function(data){
                layer.tips(vm.langData.success[vm.lang],'#eleme_bill_foot_language');
            }
        });
    }
    $('#meituan_grant').click(function(){
        var url = $('#grant_url').val();
        window.open(url);
    });
    $('#eleme_grant').click(function(){
        var if_grant = $('#if_grant').val();
        var auth_url = $('#eleme_grant_url').val();
        window.open(auth_url);
    });

    $('#meituan_unbind').click(function(){
        var has_bind = $('#has_bind' ).val();
        if(has_bind == 0){
            layer.msg(vm.langData.meituanUnbindTips[vm.lang]);
            return false;
        }
        var url = $('#unbind_url').val();
        window.open(url);
    });

</script>

</body>

</html>