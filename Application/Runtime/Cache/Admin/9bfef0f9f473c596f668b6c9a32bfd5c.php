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
            
    <section class="section">
        <div class="section-header clearfix">
            <span>{{langData.disheSet[lang]}}</span>
            <div class="pull-right">
                <button class="blue-btn" data-toggle="modal" data-target="#addSort">+{{langData.addCategory[lang]}}</button>
            </div>
        </div>
        <div class="section-content" id="mytype">
            <table class="w100 table-condensed">
                <tr>
                    <th>{{langData.sort[lang]}}</th>
                    <th>{{langData.CategoryChineseName[lang]}}</th>
                    <th>{{langData.categoryEnglishName[lang]}}</th>
                    <th class="text-right">{{langData.operating[lang]}}</th>
                </tr>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                        <td><?php echo ($v["sorts"]); ?></td>
                        <td>
                            <button data-id="<?php echo ($v["food_category_id"]); ?>"><?php echo ($v['food_timcate_name']); ?></button>
                        </td>
                        <td>
                            <button data-id="<?php echo ($v["food_category_id"]); ?>"><?php echo ($v['food_timcate_name_en']); ?></button>
                        </td>
                        <td class="text-right">
                            <button class="edit-btn" onclick="modify(<?php echo ($v["food_time_category_id"]); ?>)" id="modify"></button>
                            <button class="remove-btn" onclick="del(<?php echo ($v["food_time_category_id"]); ?>)"></button>
                        </td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            </table>
        </div>
    </section>

        </div>
        
        
    <div class="modal fade dishesClassifyModal" id="addSort" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="myform" action="javascript:void(0)">
                    <input type="hidden" name="food_time_category_id" id="food_time_category_id" value='' />
                    <div class="modal-header">
                        <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title">{{langData.disheSet[lang]}}</h4>
                    </div>
                    <div class="modal-body">
                        <div class="modal-item">
                            <span>{{langData.sort[lang]}}:</span>
                            <input type="text" name="sorts" id="sorts" style="width:80px;" placeholder="0">
                        </div>
                        <div class="modal-item">
                            <span>{{langData.CategoryChineseName[lang]}}:</span>
                            <input type="text" name="food_timcate_name" id="food_timcate_name">
                        </div>
                        <div class="modal-item">
                            <span>{{langData.categoryEnglishName[lang]}}:</span>
                            <input type="text" name="food_timcate_name_en" id="food_timcate_name_en">
                        </div>
                        <div class="text-center">
                            <button type="button" class="blue-btn" onclick="commit()">{{langData.save[lang]}}</button>
                        </div>
                    </div>
                </form>
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
    
    <script type="text/javascript">
    /*
     *提交表单
     */
    function commit() {
        var sorts = $("#sorts").val();
        var food_timcate_name = $("#food_timcate_name").val();
        var food_timcate_name_en = $("#food_timcate_name_en").val();
        var food_time_category_id = parseInt($("#food_time_category_id").val());
        if (food_timcate_name == '') {
            layer.msg(vm.langData.PleaseEnterChineseClassification[vm.lang]);
            return false;
        }
        if (food_timcate_name_en == '') {
            layer.msg(vm.langData.PleaseEnterEnglishClassification[vm.lang]);
            return false;
        }
        var urls = "<?php echo U('Category/timeAdd');?>";
        if (food_time_category_id > 0) urls = "<?php echo U('Category/timeUpdate');?>";
        $.post(urls, { sorts: sorts, food_timcate_name: food_timcate_name, food_time_category_id: food_time_category_id, food_timcate_name_en: food_timcate_name_en }, function(e) {
            $('#addSort').modal('hide');
            if (e.code == 0) {
                layer.msg(vm.langData.noChanges[vm.lang]);
                return false;
            }
            layer.msg(vm.langData.success[vm.lang],{time: 1000}, function() {
                location.reload();
            });
        });
    }
    /*
     *删除
     */
    function del(food_time_category_id) {
        layer.confirm('', {
            title: vm.langData.deleteConfirm[vm.lang],
            btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
        }, function() {
            $.post("<?php echo U('Category/del');?>", { food_time_category_id: food_time_category_id }, function(e) {
                $('#addSort').modal('hide');
                if (e.code == 0) {
                    layer.msg(vm.langData.failed[vm.lang]);
                    return false;
                }
                layer.msg(vm.langData.success[vm.lang], {time: 1000},function() {
                    location.reload();
                });
            });
        });
    }
    /*
     *修改
     */
    function modify(food_time_category_id) {
        $.get("<?php echo U('Category/getInfo');?>", { food_time_category_id: food_time_category_id }, function(e) {
            if (e.code == 0) {
                layer.msg(vm.langData.failed[vm.lang]);
                return false;
            }
            $("#sorts").val(e.msg.sorts);
            $("#food_timcate_name").val(e.msg.food_timcate_name);
            $("#food_timcate_name_en").val(e.msg.food_timcate_name_en);
            $("#food_time_category_id").val(e.msg.food_time_category_id);
            $("#addSort").modal('show');
        });
    }
    </script>

</body>

</html>