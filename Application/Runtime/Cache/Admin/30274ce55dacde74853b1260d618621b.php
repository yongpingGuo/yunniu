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
            <a href="<?php echo U('Sale/year');?>">{{langData.annualReport[lang]}}</a>
        </li>
        <li>
            <a href="<?php echo U('Sale/month');?>">{{langData.monthlyReport[lang]}}</a>
        </li>
    </ul>
    <div class="container-fluid">
        <form class="panel-heading alert-success" id="postForm">
            <span>{{langData.annualReport[lang]}}:</span>
            <select id="year" name="year">
                <?php if(is_array($year_list)): $i = 0; $__LIST__ = $year_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v_year): $mod = ($i % 2 );++$i; if($v_year == $year): ?><option value="<?php echo ($v_year); ?>" selected><?php echo ($v_year); ?></option>
                        <?php else: ?>
                        <option value="<?php echo ($v_year); ?>"><?php echo ($v_year); ?></option><?php endif; endforeach; endif; else: echo "" ;endif; ?>
            </select>
        </form>
        <div id="contain"></div>
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
    
    <script src="/Public/highcharts/highcharts.js"></script>
<script src="/Public/highcharts/exporting.js"></script>
    <script src="/Public/highcharts/highcharts_lang.js"></script>
    <script type="text/javascript">
        $(function() {
            $("#contain").css('min-width', $('.main-content').width() - 50);
            //查询年表
            $('#postForm').on('submit', function(e) {
                var ev = window.event || e;
                window.event ? window.event.returnValue = false : ev.preventDefault();
                var vs = $('select  option:selected').val();
                $.ajax({
                    url: '/index.php/admin/sale/year',
                    dataType: 'json',
                    data: { "year": vs },
                    type: 'POST',
                    beforeSend: function() {
                        layer.open({
                            type: 3,
                            icon: 2,
                            skin: "loading"
                        });
                    },
                    success: function(data) {
                        layer.closeAll('loading');
                        if (data.code == 1) {
                            chart(data.data);
                        } else {
                            alert(data.msg);
                        }
                    }
                })
                return false;
            });

            $('form').submit();
            // 选择按年
            $("#year").change(function() {
                $('#postForm').submit();
            });
        })

        function chart(data) {
            $('#contain').highcharts({
                chart: {
                    type: 'column'
                },
                credits: {
                    enabled: false
                },
                title: {
                    text: vm.langData.annualReport[vm.lang]
                },
                xAxis: {
                    categories: data.month
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: vm.langData.sales[vm.lang]
                    }
                },
                tooltip: {
                    headerFormat: '',
                    pointFormat: '<table><tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:.2f} </b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                },
                series: [{
                    name: vm.langData.cash[vm.lang],
                    data: data.cash
                }, {
                    name: vm.langData.WeChat[vm.lang],
                    data: data.wx
                }, {
                    name: vm.langData.Alipay[vm.lang],
                    data: data.ali
                }, {
                    name: vm.langData.member[vm.lang],
                    data: data.mem
                }, {
                    name: vm.langData.bankReceipt[vm.lang],
                    data: data.minsheng
                }, {
                    name: vm.langData.total[vm.lang],
                    data: data.totle
                }]
            });
        };
    </script>

</body>

</html>