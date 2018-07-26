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
    <link rel="stylesheet" href="/Public/css/base.css?v=20180125">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180228">
    <title>餐饮店云管理</title>
</head>

<body class="index-body">
    <div id="lang-content" class="h100" v-cloak>
        <header class="admin-header">
            <div class="admin-header-content clearfix">
                <!--<img class="header-logo" src="<?php echo ($logo); ?>">-->
                <!--<img class="header-logo" src="<?php echo ($Restaurant['logo']); ?>">-->
                <img class="header-logo" src="<?php echo $_SESSION['logo'];?>">
                <span class="header-title">{{langData.headerTitle[lang]}}</span>
                <div class="pull-right header-user flex-content">
                    <div id="account" class="header-user-name flex-main">
                        <div><?php echo (session('login_account')); ?></div>
                        <div>
                            <?php echo $_SESSION['restaurant_name'];?>
                        </div>
                    </div>
                    <button class="header-logout" onclick="loginout()">{{langData.signOut[lang]}}</button>
                    <div class="dropdown">
                        <button type="button" class="dropdown-toggle" data-toggle="dropdown">
                            {{langData.language[lang]}}
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a onclick="setCookie('zh-CN')">中文简体</a></li>
                            <li><a onclick="setCookie('zh-TW')">中文繁體</a></li>
                            <li><a onclick="setCookie('en')">English</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        <div class="main">
            <!-- 左侧导航栏 -->
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <img src="/Public/images/store.png">
                            <span>{{langData.shopSet[lang]}}</span>
                        </div>
                        <ul class="treeview-menu">
                            <li class="treeview-item active">
                                <a target='rightFrame' href="<?php echo U('Restaurant/index');?>">{{langData.shopInfo[lang]}}</a>
                            </li>
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Moudle/index');?>">{{langData.selfServiceDeviceSet[lang]}}</a>
                            </li>
                            <li class="treeview-item" id="nav_receipt">
                                <a target='rightFrame' href="<?php echo U('Restaurant/receipt');?>">{{langData.billSet[lang]}}</a>
                            </li>
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('DataDock/dataForPay');?>">{{langData.paymentSet[lang]}}</a>
                            </li>
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Accounts/index');?>">{{langData.roleAccount[lang]}}</a>
                            </li>
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Device/index');?>">{{langData.equipment[lang]}}</a>
                            </li>
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Index/upload');?>">{{langData.importExport[lang]}}</a>
                            </li>
                        </ul>
                    </li>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <a target='rightFrame' href="<?php echo U('Dishes/index');?>">
                                <img src="/Public/images/dishes.png">
                                <span>{{langData.disheSet[lang]}}</span>
                            </a>
                        </div>
                    </li>
                    <?php if($is_en): ?><li class="treeview">
                            <div class="treeview-header treeview-item">
                                <a target='rightFrame' href="<?php echo U('Category/index');?>">
                                    <img src="/Public/images/dishes.png">
                                    <span>{{langData.timeClassify[lang]}}</span>
                                </a>
                            </div>
                        </li><?php endif; ?>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <img src="/Public/images/data.png">
                            <span>{{langData.statistics_title[lang]}}</span>
                        </div>
                        <ul class="treeview-menu">
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Sale/food_chart');?>">{{langData.foodSales[lang]}}</a>
                            </li>
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Sale/index');?>">{{langData.detailEnquiry[lang]}}</a>
                            </li>
                            <li class="treeview-item" id="nav_year_month">
                                <a target='rightFrame' href="<?php echo U('Sale/year');?>">{{langData.yearlyReport[lang]}}</a>
                            </li>
                            <li class="treeview-item" id="nav_waimai">
                                <a target='rightFrame' href="<?php echo U('Sale/meituan');?>">{{langData.takeOutReport[lang]}}</a>
                            </li>
                        </ul>
                    </li>
                    <!-- 判断是否要显示-->
                    <?php if($type == 0) echo '
                        <li class="treeview">
                            <div class="treeview-header treeview-item">
                                <img src="/Public/images/data.png">
                                <span>{{langData.officialAccount[lang]}}</span>
                            </div>
                            <ul class="treeview-menu">
                                <li class="treeview-item">
                                    <a target="rightFrame" href="/index.php/Admin/Wechat/index">{{langData.officialAccount[lang]}}</a>
                                </li>
                                <li class="treeview-item">
                                    <a target="rightFrame" href="/index.php/Admin/Wechat/menu">{{langData.customizeMenus[lang]}}</a>
                                </li>
                            </ul>
                        </li>' ?>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <a target='rightFrame' href="<?php echo U('DataDock/meituanTest');?>">
                                <img src="/Public/images/connector.png">
                                <span>{{langData.takeOutDocking[lang]}}</span>
                            </a>
                        </div>
                    </li>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <img src="/Public/images/member.png">
                            <span>{{langData.memberSettings[lang]}}</span>
                        </div>
                        <ul class="treeview-menu">
                            <!--<li class="treeview-item">-->
                            <!--<a target='rightFrame' href="<?php echo U('Member/vip_group');?>">{{langData.memberGroupSet[lang]}}</a>-->
                            <!--</li>-->
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Member/setting');?>">{{langData.memberSettings[lang]}}</a>
                            </li>
                            <!--<li class="treeview-item">-->
                            <!--<a target='rightFrame' href="<?php echo U('Member/prepaid');?>">{{langData.preRecharge[lang]}}</a>-->
                            <!--</li>-->
                            <!--<li class="treeview-item">-->
                            <!--<a target='rightFrame' href="<?php echo U('Member/point_set');?>">{{langData.pointsSet[lang]}}</a>-->
                            <!--</li>-->
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Member/point_consumptio');?>">{{langData.consumptionPoints[lang]}}</a>
                            </li>
                            <!--<li class="treeview-item">-->
                            <!--<a target='rightFrame' href="<?php echo U('Member/members');?>">{{langData.memberInfo[lang]}}</a>-->
                            <!--</li>-->
                            <!--<li class="treeview-item">-->
                            <!--<a target='rightFrame' href="<?php echo U('Member/sms_docking');?>">{{langData.SMSDocking[lang]}}</a>-->
                            <!--</li>-->
                            <!--<li class="treeview-item">-->
                            <!--<a target='rightFrame' href="<?php echo U('Member/official_accounts');?>">{{langData.officialAccountSet[lang]}}</a>-->
                            <!--</li>-->
                            <li class="treeview-item">
                                <a target='rightFrame' href="<?php echo U('Member/vip_advertisement');?>">{{langData.memberAdvertising[lang]}}</a>
                            </li>
                        </ul>
                    </li>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <a target='rightFrame' href="<?php echo U('billBoard/index');?>">
                                        <img src="/Public/images/menu.png">
                                        <span>{{langData.electronicMenu[lang]}}</span>
                                    </a>
                        </div>
                    </li>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <a target='rightFrame' href="<?php echo U('device/show_num_device');?>">
                                <img src="/Public/images/device.png">
                                <span>{{langData.callNumberDevice[lang]}}</span>
                            </a>
                        </div>
                    </li>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <a target='rightFrame' href="<?php echo U('OrderSet/setTimes');?>">
                                <img src="/Public/images/time.png">
                                <span>{{langData.booking[lang]}}</span>
                            </a>
                        </div>
                    </li>
                    <li class="treeview">
                        <div class="treeview-header treeview-item">
                            <a target='rightFrame' href="<?php echo U('Device/deskInfo');?>">
                                <img src="/Public/images/code.png">
                                <span>{{langData.scanToOrder[lang]}}</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </aside>
            <iframe src="<?php echo U('Restaurant/index');?>" class="main-iframe" id="rightFrame" name="rightFrame"></iframe>
        </div>
    </div>
    <script src="/Public/js/vue.js"></script>
    <script src="/Public/language.json?v=2018012501"></script>
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

    function submit_form() {
        var password = $("input[name='password']").val();
        var passwords = $("input[name='passwords']").val();
        if (password === passwords) {
            var form = $("#restaurant_form")[0];
            var formData = new FormData(form);
            $.ajax({
                url: "/index.php/admin/restaurant/index",
                data: formData,
                dataType: 'json',
                type: 'post',
                //          async: false,
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
    </script>
</body>

</html>