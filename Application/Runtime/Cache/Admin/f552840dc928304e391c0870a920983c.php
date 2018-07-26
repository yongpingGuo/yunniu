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
            
    <form id="order_time_info" method="post" onsubmit="return false">
        <!-- 选择用餐方式 -->
        <div class="section-header-border">
            <p>{{langData.bookMealTips[lang]}}</p>
            <label>
                <input class="radio-circle" type="radio" name="types" value="1" id="ist" <?php echo ($info[ 'types']==1 ? 'checked': ''); ?>/>
                <i></i>{{langData.mealOnTime[lang]}}
            </label>
            <label>
                <input class="radio-circle" type="radio" name="types" value="2" id="ism" <?php echo ($info[ 'types']==2 ? 'checked': ''); ?>/>
                <i></i>{{langData.freeToEat[lang]}}
            </label>
            <div class="section-tips section-tips-indent">{{langData.mealOnTimeTips[lang]}}</div>
            <div class="section-tips section-tips-indent">{{langData.freeToEatTips[lang]}}</div>
        </div>
        <!-- 打单时间 -->
        <div class="container-fluid">
            <div>
                <span>{{langData.printTime[lang]}}</span>
                <input type="text" class="small-input">
                <span>{{langData.minutesOrMore[lang]}}</span>
            </div>
            <div class="section-tips">{{langData.printTimeTips[lang]}}</div>
            <div class="section-tips">{{langData.printTimeTips1[lang]}}</div>
            <div class="section-tips">{{langData.printTimeTips2[lang]}}</div>
            <p class="section-tips">{{langData.printTimeTips3[lang]}}</p>

        </div>
        <div class="clearfix">
            <!-- 准时用餐 -->
            <section class="section small-section pull-left">
                <div class="section-header">{{langData.mealOnTime[lang]}}:</div>
                <div class="section-content">
                    <div>
                        <span>{{langData.stopOrderingTime[lang]}}:{{langData.advance[lang]}}</span>
                        <input type="text" name="stop_ordering_time" value="<?php echo ($info["stop_ordering_time"]); ?>" :placeholder="langData.time[lang]" class="small-input">
                        <span>{{langData.minutesOrMore[lang]}}</span>
                    </div>
                    <p class="section-tips">{{langData.stopOrderingTimeTips[lang]}}</p>
                    <div>
                        <span>{{langData.todayOrder[lang]}}:</span>
                        <div class="checkbox-switch">
                            <input type="hidden" name="is_today" value="0">
                            <input type="checkbox" name="is_today" value="1" class="score" <?php echo ($info[ 'is_today']==1 ? 'checked': ''); ?>>
                            <label></label>
                        </div>
                        <span class="section-tips">{{langData.todayOrderTips[lang]}}</span>
                    </div>
                    <div>
                        <span>{{langData.tomorrowOrder[lang]}}:</span>
                        <div class="checkbox-switch">
                            <input type="hidden" name="is_tomorrow" value="0">
                            <input type="checkbox" name="is_tomorrow" value="1" class="score" <?php echo ($info[ 'is_tomorrow']==1 ? 'checked': ''); ?>>
                            <label></label>
                        </div>
                        <span class="section-tips">{{langData.tomorrowOrderTips[lang]}}</span>
                    </div>
                    <table class="table-condensed">
                        <tbody>
                            <?php if(empty($info['ext'])): ?><tr class="size">                               
                                    <td>
                                        <input type="text" name="times_1" :placeholder="langData.time[lang]" onClick="WdatePicker({el:this,dateFmt:'HH:mm'})" class="small-input">
                                    </td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <input type="hidden" name="is_use_1" value="0">
                                            <input type="checkbox" value="1" name="is_use_1" class="score">
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="remove-btn" onclick="deltr(this)"></button>
                                    </td>
                                </tr><?php endif; ?>
                            <?php if(is_array($info['ext'])): foreach($info['ext'] as $k=>$vo): ?><tr class="size">                                
                                    <td>
                                        <input type="text" name="times_<?php echo ($k+1); ?>" value="<?php echo ($vo['times']); ?>" :placeholder="langData.tomorrow[lang]" onClick="WdatePicker({el:this,dateFmt:'HH:mm'})" class="small-input">
                                    </td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <input type="hidden" name="is_use_<?php echo ($k+1); ?>" value="0">
                                            <input type="checkbox" name="is_use_<?php echo ($k+1); ?>" value="1" class="score" <?php echo ($vo[ 'is_use']==1 ? 'checked': ''); ?>>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="remove-btn" onclick="deltr(this)"></button>
                                    </td>
                                </tr><?php endforeach; endif; ?>
                            <tr id="tr_time" class="text-center">
                                <td colspan="3">
                                    <a href="javascript:;" class="blue-btn" id="add_time">{{langData.add[lang]}}</a>
                                    <button type="submit" class="blue-btn" name="times">{{langData.save[lang]}}</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>                 
                </div>
            </section>
            <!-- 自由用餐時間 -->
            <section class="section small-section pull-left">
                <div class="section-header">{{langData.freeToEat[lang]}}:</div>
                <div class="section-content">
                    <p>
                        <span>{{langData.businessHours[lang]}}:</span>
                        <input type="text" name="start_business_hours" value="<?php echo ($info["business_hours"]["0"]); ?>" :placeholder="langData.time[lang]" onClick="WdatePicker({el:this,dateFmt:'HH:mm'})" class="small-input">
                        <span>{{langData.to[lang]}}</span>
                        <input type="text" name="end_business_hours" value="<?php echo ($info["business_hours"]["1"]); ?>" :placeholder="langData.time[lang]" onClick="WdatePicker({el:this,dateFmt:'HH:mm'})" class="small-input">
                    </p>
                    <div>
                        <span>{{langData.orderTime[lang]}}:{{langData.plus[lang]}}</span>
                        <input type="text" name="add_order_time" value="<?php echo ($info["add_order_time"]); ?>" :placeholder="langData.time[lang]" class="small-input">
                        <span>{{langData.minutesOrMore[lang]}}</span>
                    </div>
                    <p class="section-tips">{{langData.orderTimeTips[lang]}}</p>
                    <div>
                        <span>{{langData.todayOrder[lang]}}:</span>
                        <div class="checkbox-switch">
                            <input type="checkbox" name="is_free_today" value="1" <?php echo ($info[ 'is_free_today']==1 ? 'checked': ''); ?>>
                            <label></label>
                        </div>
                        <span class="section-tips">{{langData.todayOrderTips[lang]}}</span>
                    </div>
                    <div>
                        <span>{{langData.tomorrowOrder[lang]}}:</span>
                        <div class="checkbox-switch">
                            <input type="checkbox" name="is_free_tomorrow" value="1" <?php echo ($info[ 'is_free_tomorrow']==1 ? 'checked': ''); ?>>
                            <label></label>
                        </div>
                        <span class="section-tips">{{langData.tomorrowOrderTips[lang]}}</span>
                    </div>
                    <div class="text-center">
                        <input type="hidden" name="size" id="size" value='0' />
                        <button type="submit" class="blue-btn" name="times">{{langData.save[lang]}}</button>
                    </div> 
                </div>               
            </section>
        </div>
        
    </form>

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
    
    <script type="text/javascript" src="/Public/js/My97DatePicker/WdatePicker.js"></script>
    <script>
    $(function() {
        /*添加时间tr标签*/
        $("#add_time").click(function() {
            var nums = $(".size").length + 1;
            var data_html = '<tr class="size">\
                                <td>\
                                    <input type="text" name="times_' + nums + '" onClick="WdatePicker({el:this,dateFmt:\'HH:mm\'})" class="small-input">\
                                </td>\
                                <td>\
                                    <div class="checkbox-switch">\
                                        <input type="hidden" name="is_use_' + nums + '" value="0">\
                                        <input type="checkbox" name="is_use_' + nums + '" value="1" class="score" checked><label></label>\
                                    </div>\
                                </td>\
                                <td>\
                                    <button type="button" class="remove-btn" onclick="deltr(this)"></button>\
                                </td>\
                            </tr>';
            $("#tr_time").before(data_html);
        });
        /*提交表单*/
        $("form").submit(function() {
            var radio_val = $(":radio:checked").val();
            if (typeof(radio_val) == 'undefined') {
                layer.msg(vm.langData.selectTypeSet[vm.lang]);
                return false;
            }
            var dataStr = $(this).serialize();
            $.ajax({
                type: "POST",
                url: "<?php echo U('OrderSet/setTimes');?>",
                data: dataStr,
                success: function(data) {
                    if (data.code == 0) return layer.msg(vm.langData.pleaseAddTime[vm.lang]);
                    layer.msg(vm.langData.success[vm.lang], {time:1000},function() {
                        location.reload();
                    });
                }
            })
        });
    });
    /*删除时间tr标签*/
    function deltr(obj) {
        layer.confirm('', {
            title: vm.langData.deleteConfirm[vm.lang],
            btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
        }, function() {
            $(obj).parent().parent().remove();
            layer.msg(vm.langData.success[vm.lang])
        });

    }
    </script>

</body>

</html>