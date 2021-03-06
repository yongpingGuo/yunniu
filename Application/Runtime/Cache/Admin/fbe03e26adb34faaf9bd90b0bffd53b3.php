<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <link rel="stylesheet" type="text/css" href="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.min.css">
    <!-- 编辑菜品信息 -->

    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <input type="hidden" id="food_id" value="<?php echo ($food_id); ?>">
    <input type="hidden" id="food_category_id" value="<?php echo ($_GET['food_category_id']); ?>">
    <input type="hidden" id="page" value="<?php echo ($_GET['page']); ?>">
    <section class="section">
        <div class="section-header">{{langData.dishesBasicSet[lang]}}</div>
        <div class="section-content dishes-info">
            <form action="javascript:void(0)" id="food_info">
                <div class="clearfix">
                    <div class="pull-left dishes-info-left">
                        <div id="preview" class="img-preview">
                            <img src="/<?php echo ($info["food_img"]); ?>" alt="">
                        </div>
                        <div class="text-center">
                            <div class="section-tips">{{langData.dishImgSize[lang]}}</div>
                            <div class="file-content blue-btn">
                                <span>{{langData.upload[lang]}}</span>
                                <input type="file" name="img_pic" onchange="preview(this)" />
                            </div>
                        </div>
                    </div>
                    <div class="pull-left dishes-info-right">
                        <table>
                            <tbody>
                                <tr>
                                    <td>{{langData.chineseName[lang]}}:</td>
                                    <td colspan="3">
                                        <input type="text" name="food_name" value="<?php echo ($info["food_name"]); ?>" :placeholder="langData.chineseName[lang]" class="large-input">
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.diserp[lang]}}:</td>
                                    <td colspan="3">
                                        <input type="text" name="erp_number" value="<?php echo ($info["erp_number"]); ?>" :placeholder="langData.ErpFoodid[lang]" class="large-input">
                                    </td>
                                </tr>
                                <?php if($is_en): ?><tr>
                                        <td>{{langData.englishName[lang]}}:</td>
                                        <td colspan="3">
                                            <input type="text" name="food_name_en" value="<?php echo ($info["food_name_en"]); ?>" :placeholder="langData.englishName[lang]" class="large-input">
                                        </td>
                                    </tr><?php endif; ?>
                                <tr>
                                    <td>{{langData.dishesDescription[lang]}}:</td>
                                    <td colspan="3">
                                        <textarea name="food_desc" :placeholder="langData.dishesDescription[lang]"><?php echo ($info["food_desc"]); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.dishprice[lang]}}:</td>
                                    <td colspan="3">
                                        <input type="text" name="food_price" value="<?php echo ($info["food_price"]); ?>" :placeholder="langData.price[lang]" class="small-input">
                                        <span>{{langData.yuan[lang]}}</span>
                                    </td>
                                </tr>
                               
                                <tr>
                                    <td>{{langData.dailyLimit[lang]}}:</td>
                                    <td colspan="3">
                                        <input type="text" name="food_num_day" value="<?php echo ($info["foods_num_day"]); ?>" class="small-input">
                                        <span>({{langData.todaySold[lang]}}:</span>
                                        <span class="text-danger"><?php echo ($num); ?> </span>
                                        <span>{{langData.copies[lang]}})</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.taste[lang]}}</td>
                                    <td>
                                        <div class="cayenne">
                                            <input type="hidden" name="cayenne" />
                                            <?php if($info["hot_level"] == 0): ?><span id="notSpicy" class="hide active" data-index="0"></span>
                                                <!-- 不辣与微辣 -->
                                                <span data-index="1"></span>
                                                <!-- 中辣 -->
                                                <span data-index="2"></span>
                                                <!-- 大辣 -->
                                                <span data-index="3"></span>
                                                <?php elseif($info["hot_level"] == 1): ?>
                                                <span id="notSpicy" class="hide" data-index="0"></span>
                                                <!-- 不辣与微辣 -->
                                                <span data-index="1" class="active"></span>
                                                <!-- 中辣 -->
                                                <span data-index="2"></span>
                                                <!-- 大辣 -->
                                                <span data-index="3"></span>
                                                <?php elseif($info["hot_level"] == 2): ?>
                                                <span id="notSpicy" class="hide" data-index="0"></span>
                                                <!-- 不辣与微辣 -->
                                                <span data-index="1"></span>
                                                <!-- 中辣 -->
                                                <span data-index="2" class="active"></span>
                                                <!-- 大辣 -->
                                                <span data-index="3"></span>
                                                <?php elseif($info["hot_level"] == 3): ?>
                                                <span id="notSpicy" class="hide" data-index="0"></span>
                                                <!-- 不辣与微辣 -->
                                                <span data-index="1"></span>
                                                <!-- 中辣 -->
                                                <span data-index="2"></span>
                                                <!-- 大辣 -->
                                                <span data-index="3" class="active"></span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td></td>
                                    <td class="textR">
                                        <span>{{langData.recommend[lang]}}:</span>
                                        <div class="star inline">
                                            <input type="radio" name="star_level" value="1" id="star_level1" />
                                            <span>★</span>
                                            <input type="radio" name="star_level" value="2" id="star_level2" />
                                            <span>★</span>
                                            <input type="radio" name="star_level" value="3" id="star_level3" />
                                            <span>★</span>
                                            <input type="radio" name="star_level" value="4" id="star_level4" />
                                            <span>★</span>
                                            <input type="radio" name="star_level" value="5" id="star_level5" />
                                            <span>★</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <fieldset>
                    <legend>{{langData.dishesCategorySettings[lang]}}</legend>
                    <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i; if($v[is_select] == 1): ?><label class="classifySelect">
                                <input type="checkbox" name="sort1[]" value="<?php echo ($v["food_category_id"]); ?>" checked>
                                <span><?php echo ($v["food_category_name"]); ?></span>
                            </label>
                            <?php else: ?>
                            <label class="classifySelect">
                                <input type="checkbox" name="sort1[]" value="<?php echo ($v["food_category_id"]); ?>">
                                <span><?php echo ($v["food_category_name"]); ?></span>
                            </label><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                </fieldset>
                <input type="hidden" name="is_en" id="is_en" value='<?php echo ($is_en); ?>' />
                <?php if($is_en): ?><fieldset>
                        <legend>{{langData.timeClassify[lang]}}</legend>
                        <?php if(is_array($time_category_list)): $i = 0; $__LIST__ = $time_category_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><label class="classifySelect">
                                <input class="menu_input" type="checkbox" name="time_category[]" value="<?php echo ($v["food_time_category_id"]); ?>" <?php if(in_array($v[ 'food_time_category_id'], $time_category)){ echo "checked"; } ?>>
                                <span><?php echo ($v["food_timcate_name"]); ?></span>
                            </label><?php endforeach; endif; else: echo "" ;endif; ?>
                    </fieldset><?php endif; ?>
                <fieldset>
                    <legend>{{langData.printerSettings[lang]}}:</legend>
                    <div class="inline-block">
                        <span>{{langData.kitchenPrinter[lang]}}:</span>
                        <select name="print_id" id="print_id"  class="select-grey">
                            <option value="0">{{langData.noPrint[lang]}}</option>
                            <?php foreach ($printerList as $k => $v): if ($v['printer_id'] == $info['print_id']) { $selected = "selected='selected'"; }else{ $selected = ""; } ?>
                            <?php if ($v['print_type'] !=2) { echo "<option value='".$v['printer_id']."'".$selected.">".$v['printer_name']."</option>"; };?>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="inline-block">
                        <span>{{langData.labelPrinter[lang]}}:</span>
                        <select name="tag_print_id" id="sel" class="select-grey">
                            <option value="0">{{langData.noPrint[lang]}}</option>
                            <?php foreach ($printerList as $k => $v): if ($v['printer_id'] == $info['tag_print_id']) { $selected = "selected='selected'"; }else{ $selected = ""; } ?>
                            <?php if ($v['print_type'] ==2) { echo "<option value='".$v['printer_id']."'".$selected.">".$v['printer_name']."</option>"; };?>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="inline-block">
                        <span>{{langData.calledScreenPartition[lang]}}:</span>
                        <select name="district" id="district" class="select-grey">
                            <?php if(is_array($district_list)): $i = 0; $__LIST__ = $district_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$district_vo): $mod = ($i % 2 );++$i; if($info['district_id'] == $district_vo['district_id']): ?><option value="<?php echo ($district_vo['district_id']); ?>" selected>
                                        <?php echo ($district_vo["district_name"]); ?>
                                    </option>
                                    <?php else: ?>
                                    <option value="<?php echo ($district_vo['district_id']); ?>">
                                        <?php echo ($district_vo["district_name"]); ?>
                                    </option><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                        </select>
                    </div>
                </fieldset>
                <div class="text-center">
                    <button class="blue-btn" onclick="save_food()">{{langData.save[lang]}}</button>
                </div>
            </form>
        </div>
    </section>
    <section class="section">
        <div class="section-header">{{langData.dishesSpecificationSet[lang]}}</div>
        <div class="section-content">
            <div id="dishesAttrList">
                <?php if(is_array($attr_type_list)): $i = 0; $__LIST__ = $attr_type_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$at_vo): $mod = ($i % 2 );++$i;?><div class="dishes-spec">
                        <div class="dishes-spec-header">
                            <b><?php echo ($at_vo["type_name"]); ?></b>
                            <button class="edit-btn" data-toggle="modal" data-target="#edit-dishes-sort" data-type_id="<?php echo ($at_vo["attribute_type_id"]); ?>" onclick="editType(this)"></button>
                            <button class="remove-btn" data-type_id="<?php echo ($at_vo["attribute_type_id"]); ?>" onclick="deleteType(this)"></button>
                        </div>
                        <div id="attrType<?php echo ($at_vo["attribute_type_id"]); ?>" class="clearfix">
                            <!--<input type="hidden" name="type" id="type" value="add">     -->
                            <div class="pull-left dishes-attr-left">{{langData.specificationName[lang]}}:</div>
                            <div class="pull-left dishes-attr-list">
                                <?php if(is_array($at_vo['attr_list'])): $i = 0; $__LIST__ = $at_vo['attr_list'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo2): $mod = ($i % 2 );++$i;?><div class="dishes-attr-item">
                                        <div><?php echo ($vo2["attribute_name"]); ?></div>
                                        <div>
                                            <span class="text-danger">+</span>
                                            <span><?php echo ($vo2["attribute_price"]); ?>{{langData.yuan[lang]}}</span>
                                        </div>
                                        <button class="edit-btn dishes-attr-edit" data-attr_id="<?php echo ($vo2["food_attribute_id"]); ?>" data-toggle="modal" data-target="#edit-attr" onclick="editAttr(this)"></button>
                                        <button class="remove-btn dishes-attr-del" data-attr_id="<?php echo ($vo2["food_attribute_id"]); ?>" onclick="deleteAttr(this)"></button>
                                    </div><?php endforeach; endif; else: echo "" ;endif; ?>
                                <div class="dishes-attr-add">
                                    <button type="button" onclick="addAttr(this)" data-type_id="<?php echo ($at_vo["attribute_type_id"]); ?>" data-toggle="modal" data-target="#edit-attr">
                                        <img src="/Public/images/add_down.png">
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div><?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
            <div>
                <button class="blue-btn" data-toggle="modal" onclick="show_food_type()">{{langData.newSpecCategory[lang]}}</button>
                <span class="section-tips">{{langData.attrPrinterTips[lang]}}</span>
            </div>
        </div>
    </section>

        </div>
        
        
    <!-- 新增分类Modal -->
    <div class="modal fade" id="add-dishes-sort" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title">{{langData.dishesSpecCategorySet[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0)" id="addDishesAttrType">
                        <table class="table-condensed">
                            <tr>
                                <td>{{langData.classificationName[lang]}}:</td>
                                <td>
                                    <input type="text" name="type_name">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.kitchenPrinter[lang]}}:</td>
                                <td>
                                    <select name="print_id" class="select-grey">
                                        <option value="0">{{langData.noPrinter[lang]}}</option>
                                        <?php foreach ($printerList as $k => $v): if ($v['printer_id'] == $info['print_id']) { $selected = "selected='selected'"; }else{ $selected = ""; } ?>
                                        <?php if ($v['print_type'] !=2) { echo "<option value='".$v['printer_id']."'>".$v['printer_name']."</option>"; };?>
                                        <?php endforeach ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.labelPrinter[lang]}}:</td>
                                <td>
                                    <select name="tag_print_id" class="select-grey">
                                        <option value="0">{{langData.noPrinter[lang]}}</option>
                                        <?php foreach ($printerList as $k => $v): ?>
                                        <?php if ($v['print_type'] ==2) { echo "<option value='".$v['printer_id']."'>".$v['printer_name']."</option>"; };?>
                                        <?php endforeach ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.statistics[lang]}}:</td>
                                <!--<td>
                                    &lt;!&ndash; <input type="radio" name="count_type" value="0" checked>否
                                    <input type="radio" name="count_type" value="1">是 &ndash;&gt;
                                    <div class="checkbox-switch">
                                        <input type="checkbox" name="count_type">
                                        <label></label>
                                    </div>
                                </td>-->
                                <td>
                                    <input type="hidden" name="count_type" value="0">
                                    <div class="checkbox-switch">
                                        <input type="checkbox" name="count_types" onclick="changestatu(this)">
                                        <label></label>
                                    </div>
                                    <!-- <input type="radio" name="count_type" value="0" checked>否
                                        <input type="radio" name="count_type" value="1">是
                                        <span style="color: red;">(是否列入数据统计)</span> -->
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.select[lang]}}:</td>
                                <td>
                                    <label>
                                        <input class="radio-circle" type="radio" name="select_type" value="0" checked>
                                        <i></i>
                                        <span>{{langData.singleChoice[lang]}}</span>
                                    </label>
                                    <label>
                                        <input class="radio-circle" type="radio" name="select_type" value="1">
                                        <i></i>
                                        <span>{{langData.multipleChoice[lang]}}</span>
                                    </label>
                            </tr>
                        </table>
                        <div class="text-center">
                            <button type="button" class="blue-btn" onclick="addDishesAttrType(this)" data-food_id="<?php echo ($food_id); ?>">{{langData.add[lang]}}</button>
                            <input type="reset" name="reset1" id="reset1" style="display:none;" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- 修改分类Modal -->
    <div class="modal fade" id="edit-dishes-sort" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title">{{langData.dishesSpecCategorySet[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <div class="attr-content" id="attr_content_byId"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- 新增修改属性Modal -->
    <div class="modal fade" id="edit-attr" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title">{{langData.dishesSpecCategorySet[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0)" id="add_attr">
                        <input type="hidden" name="attribute_type_id" id="attribute_type_id">
                        <input type="hidden" name="food_attribute_id" id="food_attribute_id">
                        <input type="hidden" name="type" id="type" value="add">
                        <table class="table-condensed">
                            <tr>
                                <td>{{langData.specificationName[lang]}}:</td>
                                <td>
                                    <input type="text" name="attribute_name" :placeholder="langData.CocaCola[lang]">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.overlayPrice[lang]}}:</td>
                                <td>
                                    <input type="text" name="attribute_price" value="0.00" class="small-input">
                                    <span>{{langData.yuan[lang]}}</span>
                                </td>
                            </tr>
                        </table>
                        <div class="text-center">
                            <button type="button" class="blue-btn" onclick="addDishesAttr()">{{langData.save[lang]}}</button>
                            <input type="reset" class="hidden">
                        </div>
                    </form>
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
    
    <script src="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.min.js"></script>
<script src="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.zh-CN.js"></script>
<script src="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.zh-TW.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var dateLang=vm.lang;
		$("#form_date").datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		});
		$("#form_starttime").datetimepicker({
		    format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		});
		$("#form_endtime").datetimepicker({
		    format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		});
		$('#startDate').datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		}).on("click",function(ev){
		    $(this).datetimepicker("setEndDate", $("#endtDate").val());
		});
		$('#endtDate').datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#startDate").val());
		});

		$('#discount_startDate').datetimepicker({
		    format:'yyyy-mm-dd hh:ii:00',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    language:  dateLang
		}).on("click",function(ev){
		    $(this).datetimepicker("setEndDate", $("#discount_endtDate").val());
		});
		$('#discount_endtDate').datetimepicker({
		    format:'yyyy-mm-dd hh:ii:00',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#discount_startDate").val());
		});

		$('#startTime').datetimepicker({
		    format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#startDate").val());
		});
		$('#endTime').datetimepicker({
		   format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#endtDate").val());
		});


		$("#device_start_time").datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		});

		$("#device_end_time").datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		});
	});
</script>
    <script type="text/javascript">
    // 口味
    var index;
    $(".cayenne span").click(function(event) {
            if($(this).data('index')==1&&$(this).attr('class')=="active"){
                $('#notSpicy').addClass('active').siblings().removeClass('active');
                index=0;
            }
            else{
                index=$(this).data('index');
                $(this).addClass('active').siblings().removeClass('active');
            }
            console.log(index);  
        });

    //新增属性值
    function addAttr(obj){
            var attribute_type_id = $(obj).data("type_id");
            $("#attribute_type_id").val(attribute_type_id);
            $("#type").val("add");
            $("input[type='reset']").trigger('click');

    }

      function test(obj) {

            var attributename = $(obj).closest("form").find("input[name='attribute_name']").val();
            var attributeprice = $(obj).closest("form").find("input[name='attribute_price']").val();
            var attributetypeid = $(obj).closest("form").find("input[name='attribute_type_id']").val();
            var foodattributeid = $(obj).closest("form").find("input[name='food_attribute_id']").val();

            $.ajax({
            
                url: '/index.php/admin/dishes/subm',
                data:{'attribute_name' : attributename, 'attribute_price' : attributeprice, 'attribute_type_id' : attributetypeid, 'food_attribute_id' : foodattributeid},
                type: 'POST',
                dataType: 'json',
                error:function(data){
                    console.log(data);
                },
                success:function(json){
             // console.log(json);
                    $(obj).closest("form").find("input[name='attribute_name']").val(json.data.attribute_name);         
                    $(obj).closest("form").find("input[name='attribute_price']").val(json.data.attribute_price);
                    $(obj).closest("form").find("input[name='food_attribute_id']").val(json.data.food_attribute_id);
                    layer.msg(vm.langData.success[vm.lang]);
            
                }
            });
        }




    $(function() {
        var is_prom = <?php echo ($info["is_prom"]); ?>;
        if (is_prom == 0) {
            $("input[name='is_prom']:eq(0)").prop("checked", true);
        } else {
            $("input[name='is_prom']:eq(1)").prop("checked", true);
            $("#showdiscount").show();
        }
    });

    $("input[name='is_prom']").change(function() {
        var value = $(this).val();
        if (value == 1) {
            $("#showdiscount").show();
        } else {
            $("#showdiscount").hide();
        }
    });

    function preview(file) {
        var prevDiv = document.getElementById('preview');
        var picinfo = file.files[0]; //input 
        if (picinfo.size > 1 * 1024 * 1024) { //用size属性判断文件大小不能超过5M 
            layer.msg(vm.langData.uploadLimit[vm.lang]);
            $("input[name='img_pic']").val('');
            // prevDiv.innerHTML = '';
            return false;
        }
        if (file.files && file.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                prevDiv.innerHTML = '<img src="' + evt.target.result + '"/>';
            }
            reader.readAsDataURL(file.files[0]);
        } else {
            prevDiv.innerHTML = '<div class="img"  style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
        }
    }

    function select_all(obj) {
        var tt = $(obj).val();
        if (tt == 0) {
            $(".menu_input").prop("checked", true);
            $(obj).val(1);
        } else if (tt == 1) {
            $(".menu_input").prop("checked", false);
            $(obj).val(0)
        }
    }

    function save_food() {
        var hschek = $(".is_prom").is(':checked');
            if (hschek) {
                status = 1;
            }else{
                status = 0;
            }
        // var img_src = $("input[name='img_pic']").val();
        var food_name = $("input[name='food_name']").val();
        var food_name_en = $("input[name='food_name_en']").val();
        var food_price = $("input[name='food_price']").val();
        //var discount  = $("input[name='discount']").val();
        var foods_num_day = $("input[name='food_num_day']").val();
        var sort1 = $("input:checkbox[name='sort1[]']:checked").length;
        var time_category = $("input:checkbox[name='time_category[]']:checked").length; 
        var print_id = $("#print_id").children('option').length;
        var is_en = parseInt($("#is_en").val());
        var is_prom = status;
        var prom_price = $("input[name='prom_price']").val();
        //var prom_discount = $("input[name='prom_discount']").val();
        var prom_goods_num = $("input[name='prom_goods_num']").val();
        var prom_start_time = $("input[name='prom_start_time']").val();
        var prom_end_time = $("input[name='prom_end_time']").val();
        var erp_number = $("input[name='erp_number']").val();
            $("input[name='cayenne']").val(index);
            $("input[name='is_prom']").val(status);
        if (!(food_name && food_price && foods_num_day)) {
            layer.msg(vm.langData.asteriskWarn[vm.lang]);
        } else if (!sort1 > 0) {
            layer.msg(vm.langData.notChooseDishesCategory[vm.lang]);
        }else if (print_id == 0) {
            layer.msg(vm.langData.noPrinter[vm.lang]);
        } else {
            if (is_prom != 0) {
                if (!(prom_price && prom_goods_num && prom_start_time && prom_end_time)) {
                    layer.msg(vm.langData.asteriskWarn[vm.lang]);
                    return false;
                }
            }
            var formData = new FormData($("#food_info")[0]);
            $.ajax({
                url: "/index.php/Admin/Dishes/modifyfoodinfo/food_id/<?php echo ($food_id); ?>",
                type: "post",
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(data) {
                    $("#type_form").data('id', data.food_id);
                    layer.confirm('', { title: vm.langData.editDishNext[vm.lang], btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]] },function(index){
                        layer.close(index)
                    },function(index) {
                        var food_category_id = $("#food_category_id").val();
                        var page = $("#page").val();
                        if (food_category_id != 0) { //菜品记录的编辑
                            location.href = '/index.php/admin/Dishes/index/';
                        } else {
                            location.href = '/index.php/admin/Dishes/index/page/' + page;
                        }
                    });
                }
            });
        }
    }

    function mypreview(file) {
        var prevDiv = $(file).parent().prev();
        console.log();
        prevDiv = prevDiv[0];
        console.log(prevDiv);
        if (file.files && file.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                prevDiv.innerHTML = "";
                prevDiv.innerHTML = '<img src="' + evt.target.result + '" class="pre100 center-block" style="width:100%;height:100%;" />';
            }
            reader.readAsDataURL(file.files[0]);
        } else {
            prevDiv.innerHTML = "";
            prevDiv.innerHTML = '<div style="width:100%;height:100%;" class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
        }
    }

    //新增菜品类别的模态框
    function show_food_type() {
        $("#add-dishes-sort").modal('show');
        $('input[name="count_type"]' ).val(0);
        $("input[type='reset']").trigger('click');
    }

    function addDishesAttrType(obj) {
        $("#add-dishes-sort").modal("hide");
        var form = $("#addDishesAttrType")[0];
        var formData = new FormData(form);
        var food_id = $(obj).data("food_id");
        formData.append("food_id", food_id);
        $.ajax({
            url: '/index.php/admin/dishes/addDishesAttrType',
            data: formData,
            type: "post",
            dataType: 'json',
            contentType: false,
            processData: false,
            cache: false,
            success: function(msg) {
                console.log(msg);
                if (msg.code == 1) {
                    var data = msg.data;
                    console.log(data);
                    var str = '<div class="dishes-spec">\
                        <div class="dishes-spec-header"> \
                            <b>' + data['type_name'] + '</b>\
                            <button class="edit-btn" data-toggle="modal" data-target="#edit-dishes-sort" data-type_id="' + data["attribute_type_id"] + '" onclick="editType(this)"></button>\
                            <button class="remove-btn" data-type_id="' + data["attribute_type_id"] + '" onclick="deleteType(this)"></button>\
                        </div>\
                        <div id="attrType' + data["attribute_type_id"] + '" class="clearfix">\
                            <div class="pull-left dishes-attr-left">'+vm.langData.specificationName[vm.lang]+':</div> \
                            <div class="pull-left dishes-attr-list">\
                                <div class="dishes-attr-add">\
                                    <button type="button" onclick="addAttr(this)" data-type_id="' + data["attribute_type_id"] + '"  data-toggle="modal" data-target="#edit-attr">\
                                        <img src="/Public/images/add_down.png">\
                                    </button>\
                                </div>\
                            </div>\
                        </div>\
                    </div>';
                    $("#dishesAttrList").append(str);
                }
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }

    function editAttr(obj) {
        var food_attribute_id = $(obj).data("attr_id");
        $("#food_attribute_id").val(food_attribute_id);
        $("#type").val("edit");
        $.ajax({
            type: "get",
            url: "/index.php/admin/dishes/getDishesAttr/food_attribute_id/" + food_attribute_id + "",
            async: true,
            success: function(data) {
                $("input[name='attribute_name']").val(data.attribute_name);
                $("input[name='attribute_price']").val(data.attribute_price);
                $("input[name='food_attribute_id']").val(data.food_attribute_id);
            }
        });
    }


    function addDishesAttr1(obj) {
        var attribute_type_id = $(obj).data("type_id");
        $("#attribute_type_id").val(attribute_type_id);
        $("#type").val("add");
        $("input[type='reset']").trigger('click');
    }

    function subm(){
        var form = $(".add_attr")[0];
        var formData = new FormData(form);
            $.ajax({
                url: '/index.php/admin/dishes/subm',
                type: 'post',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                async: false,
                success: function(msg) {
                    console.log(msg);
                }
            });
    }

    function addDishesAttr(obj) {
        $("#edit-attr").modal("hide");
        var type = $("#type").val();
        var form = $("#add_attr")[0];
        var formData = new FormData(form);
        var url;
        //        console.log(url);
        if (type == "add") {
            url = '/index.php/admin/dishes/addDishesAttr';
            $.ajax({
                url: url,
                type: 'post',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                async: false,
                success: function(msg) {
                    var data = msg.data;
                    var str='<div class="dishes-attr-item">\
                       <input type="hidden" name="attribute_type_id" class="attribute_type_id" value="'+data['attribute_type_id']+'"> \
                                <div>'+data['attribute_name']+'</div>\
                                <div>\
                                    <span class="text-danger">+</span>\
                                    <span>'+data['attribute_price']+vm.langData.yuan[vm.lang]+'</span>\
                                </div>\
                                <button class="edit-btn dishes-attr-edit" data-attr_id="'+data['food_attribute_id']+'"  data-toggle="modal" data-target="#edit-attr" onclick="editAttr(this)"></button>\
                                <button class="remove-btn dishes-attr-del" data-attr_id="'+data['food_attribute_id']+'" onclick="deleteAttr(this)"></button>\
                            </div>'
                   $("#attrType"+data['attribute_type_id']).find('.dishes-attr-add').before(str);

                },
                error: function() {
                    layer.msg(vm.langData.error[vm.lang]);
                }
            });
        } else if (type == "edit") {
            var food_id = $("#food_id").val();
            url = '/index.php/admin/dishes/editDishesAttr';
            $.ajax({
                url: url,
                type: 'post',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                async: false,
                success: function(msg) {
                    console.log(msg);
                    if (msg.code == 1) {
                        self.location.href = "/index.php/admin/Dishes/edit/food_id/" + food_id;
                    }
                },
                error: function() {
                    layer.msg(vm.langData.error[vm.lang]);
                }
            });
        }

    }

    function editType(obj) {
        var type_id = $(obj).data('type_id');
        $.ajax({
            url: "/index.php/admin/Dishes/getTypeAttrs",
            type: "post",
            data: {
                "type_id": type_id
            },
            success: function(data) {
                $("#attr_content_byId").html(data);
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }

    function changestatu(obj) {
          var hschek = $(obj).closest("form").find("input[name='count_types']").is(':checked');
            if (hschek) {
                $(obj).closest("form").find("input[name='count_type']").val(1);
            }else{
                $(obj).closest("form").find("input[name='count_type']").val(0);
            }
    }

    function editDishesType() {
        var food_id = $("#food_id").val();
        var form = $("#editDishesType")[0];
        var formData = new FormData(form);
        formData.append("food_id", food_id);
        $.ajax({
            url: "/index.php/admin/dishes/editDishesType",
            type: "post",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            cache: false,
            success: function(msg) {
                if (msg.code == 1) {
                    self.location.href = "/index.php/admin/Dishes/edit/food_id/" + food_id;
                }
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }

    function deleteAttr(obj) {
        var attr_id = $(obj).data("attr_id");
        $.ajax({
            url: "/index.php/admin/dishes/deleteAttr",
            data: {
                "attr_id": attr_id
            },
            type: "post",
            dataType: "json",
            success: function(msg) {
                if (msg.code == 1) {
                    $(obj).parent().remove();
                }
            },
            error: function() {
                console.log();
            }
        });
    }

    function deleteType(obj) {
        var type_id = $(obj).data("type_id");
        $.ajax({
            url: "/index.php/admin/dishes/deleteType",
            data: {
                "type_id": type_id
            },
            type: "post",
            dataType: "json",
            success: function(msg) {
                if (msg.code == 1) {
                    $(obj).parent().parent().remove();
                     location.reload();
                }
            },
            error: function() {
                console.log();
            }
        });
    }

    var level_num = <?php echo ($info["star_level"]); ?>;
    var level_id = ($('#star_level' + level_num));
    if (level_id.val() == level_num) {
        $('#star_level0').removeAttr('checked');
        console.log(level_num);
        level_id.attr('checked', 'checked');
    }
    </script>

</body>

</html>