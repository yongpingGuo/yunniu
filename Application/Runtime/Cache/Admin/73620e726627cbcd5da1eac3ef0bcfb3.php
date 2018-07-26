<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <link rel="stylesheet" type="text/css" href="/Public/wangEditor/css/wangEditor.min.css">

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
            <a href="#home" data-toggle="tab">{{langData.pointsDonated[lang]}}</a>
        </li>
        <li><a href="#present" data-toggle="tab">{{langData.pointsGifts[lang]}}</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab tab-pane active in" id="home">
            <section class="section">
                <div class="section-header">
                    <span>{{langData.pointsDonated[lang]}}</span>
                    <div class="checkbox-switch">
                        <?php if($if_open == 1): ?><input type="checkbox" name="score" class="score" onchange="to_bd(this.name,'/index.php/Admin/Member/discount_set1')" checked>
                        <?php else: ?>
                        <input type="checkbox" name="score" class="score" onchange="to_bd(this.name,'/index.php/Admin/Member/discount_set1')"><?php endif; ?>
                    
                        <label></label>
                    </div>
                    <span class="section-tips">{{langData.pointsDonatedTips[lang]}}</span>
                </div>
                <div class="section-content">
                    <table class="point_consumptio-table">
                    <?php foreach ($prepaid_rules as $k => $v): ?>
                        <tr>
                            <td><?php echo ++$k;?></td>
                            <td>{{langData.spendingFull[lang]}}<?php echo $v['account'];?>{{langData.yuan[lang]}}</td>
                            <td>{{langData.pointsDonated[lang]}}:<?php echo $v['benefit'];?>{{langData.point[lang]}}</td>
                        </tr>
                    <?php endforeach ?>
                    </table>
                </div>
            </section>
        </div>
        <div class="tab tab-pane" id="present">
            <section class="section point_consumptio-content">
                <div class="section-header">
                    <span>{{langData.redeemPoints[lang]}}</span>
                    <div class="checkbox-switch">
                        <?php if($goods_open == 1): ?><input type="checkbox" name="change" class="change" onchange="to_bd(this.name,'/index.php/Admin/Member/goods_set')" checked>
                        <?php else: ?>
                        <input type="checkbox" name="change" class="change" onchange="to_bd(this.name,'/index.php/Admin/Member/goods_set')"><?php endif; ?>
                        <label></label>
                    </div>
                    <span class="section-tips">{{langData.redeemPointsTips[lang]}}</span>
                </div>
                <div class="section-content">
                    <div id="photo" class="clearfix">
                        <?php if(is_array($img_rules)): foreach($img_rules as $key=>$v): ?><div class="pull-left point-exchange-item">
                                <div class="pic-thumbnail">
                                    <img src="/Public/Uploads/Goods/<?php echo ($v[goods_img]); ?>">
                                    <input type="hidden" name="id" value="<?php echo ($v['id']); ?>" />
                                    <input type="hidden" name="goods_name" value="<?php echo ($v['goods_name']); ?>" />
                                    <input type="hidden" name="score" value="<?php echo ($v['score']); ?>" />
                                    <input type="hidden" name="money" value="<?php echo ($v['money']); ?>" />
                                    <input type="hidden" name="goods_desc" value="<?php echo ($v['goods_desc']); ?>" />
                                </div>
                                <div class="flex-content">
                                    <span class="flex-main"><?php echo ($v['goods_name']); ?></span>
                                    <span><?php echo ($v[score]); ?>{{langData.point[lang]}}</span>
                                </div>
                            </div><?php endforeach; endif; ?>
                    </div>
                </div>
            </section>
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
    
    <script src="/Public/js/vip.js"></script>
    <script>
    $('[name="if_open"]').val([$("#cash_open").val()]);
    $('.point_goods').val([$("#goods_open").val()]);


    function to_bd(a,url){
    var cls = "." + a;
    var hschek = $(cls).is(':checked');
    if (hschek) {
        b = 1;
    }else{
        b = 0;
    }
        // 发送ajax
        $.post(url,{"if_open":b},function(data){
            if(data.status == 0){
                alert(data.info);
            }
        });
}
    </script>

</body>

</html>