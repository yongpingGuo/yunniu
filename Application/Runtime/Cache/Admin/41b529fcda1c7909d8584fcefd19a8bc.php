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
            
    <div class="container-fluid">
        <table class="table table-bordered table-condensed">
            <tr>
                <td></td>
                <td>{{langData.name[lang]}}</td>
                <td>{{langData.machineCode[lang]}}</td>
                <!-- <td>{{langData.dateOfExpiry[lang]}}</td> -->
                <td>{{langData.lastUsedTime[lang]}}</td>
                <td>{{langData.status[lang]}}</td>
            </tr>
            <?php if(is_array($device_list)): $i = 0; $__LIST__ = $device_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                    <td><?php echo ($i); ?></td>
                    <td><?php echo ($vo["device_name"]); ?></td>
                    <td><?php echo ($vo["device_code"]); ?></td>
                    <!-- <td><?php echo ($vo["end_time"]); ?></td> -->
                    <td><?php echo ($vo["last_time"]); ?></td>
                    <?php if($vo["device_status"] == 1): ?><td>{{langData.on[lang]}}</td>
                        <?php else: ?>
                        <td>{{langData.off[lang]}}</td><?php endif; ?>
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </table>
        <div class="text-center device-page">
            <ul class="pagination">
                <?php echo ($page); ?>
            </ul>
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
    
</body>

</html>