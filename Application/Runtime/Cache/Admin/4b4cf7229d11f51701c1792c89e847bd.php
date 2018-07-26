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

    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <div class="clearfix">
        <!-- 菜品分类 start -->
        <section class="section dishes-classify">
            <div class="section-header clearfix">
                <span>{{langData.dishesCategory[lang]}}</span>
                <div class="pull-right">
                    <button class="blue-btn" data-toggle="modal" onclick="show_addSort()">+{{langData.addCategory[lang]}}</button>
                </div>
            </div>
            <div class="section-content" id="mytype">
                <table class="dishes-classify-table">
                    <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr data-food_category_id="<?php echo ($v["food_category_id"]); ?>">
                            <td class="text-right"><?php echo ($key+1); ?></td>
                            <td>
                                <button class="rank-up" data-sort="<?php echo ($v["sort"]); ?>" data-food_category_id="<?php echo ($v["food_category_id"]); ?>" onclick="moveup1(this)"></button>
                                <button class="rank-down movedown" data-sort="<?php echo ($v["sort"]); ?>" data-food_category_id="<?php echo ($v["food_category_id"]); ?>" onclick="movedown1(this)"></button>
                            </td>
                            <td>
                                <button data-id="<?php echo ($v["food_category_id"]); ?>" onclick="showinfo(this)"><?php echo ($v['food_category_name']); ?></button>
                                <br/><?php echo ($v["food_category_name_en"]); ?>
                            </td>
                            <td class="text-right">
                                <button class="edit-btn" data-toggle="modal" data-target="#addSort" onclick="modify1(<?php echo ($v["food_category_id"]); ?>)" id="modify">
                                </button>
                                <button class="remove-btn" onclick="deltype(<?php echo ($v["food_category_id"]); ?>)"></button>
                            </td>
                        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </table>
            </div>
        </section>
        <!-- 菜品分类 end -->
        <!-- 菜品列表 start -->
        <section class="section dishes-list">
            <div class="section-header clearfix">
                <span>{{langData.dishesSet[lang]}}</span>
                <div class="pull-right">
                    <button class="blue-btn" onclick="location.href='/index.php/admin/Dishes/add'">+{{langData.addDishes[lang]}}</button>
                </div>
            </div>
            <div class="section-content" id="mytr">
                <table class="dishes-list-table">
                    <?php if(is_array($info)): $i = 0; $__LIST__ = $info;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                            <td><?php echo ($key+1); ?></td>
                            <td>
                                <button class="rank-up" data-index="<?php echo ($key+1); ?>" data-sort="<?php echo ($v["sort"]); ?>" data-food_id="<?php echo ($v["food_id"]); ?>" onclick="moveup(this)"></button>
                                <button class="rank-down movedown" data-index="<?php echo ($key+1); ?>" data-sort="<?php echo ($v["sort"]); ?>" data-food_id="<?php echo ($v["food_id"]); ?>" onclick="movedown(this)"></button>
                            </td>
                            <td>
                                <img src="/<?php echo ($v["food_img"]); ?>" class="dishes-list-img">
                            </td>
                            <td class="dishes-list-name">
                                <span><?php echo ($v["food_name"]); ?> <?php echo ($v["food_name_en"]); ?></span>
                                <div class="section-tips">{{langData.classification[lang]}}:<?php echo ($v["cateData"]); ?></div>
                            </td>
                            <td class="dishes-list-price"><?php echo ($v["food_price"]); ?>{{langData.yuan[lang]}}</td>
                            <td class="dishes-list-star">
                                <span class="showStar">
                                    <?php if($v["star_level"] == 1): endif; ?>
                                    <?php if($v["star_level"] == 2): ?>★★<?php endif; ?>
                                    <?php if($v["star_level"] == 3): ?>★★★<?php endif; ?>
                                    <?php if($v["star_level"] == 4): ?>★★★★<?php endif; ?>
                                    <?php if($v["star_level"] == 5): ?>★★★★★<?php endif; ?>
                                </span>
                                <?php if($v["hot_level"] == 0): endif; ?>
                                <?php if($v["hot_level"] == 1): ?><img src="/Public/images/cayenne.png" class="showCayenne"><?php endif; ?>
                                <?php if($v["hot_level"] == 2): ?><img src="/Public/images/cayenne.png" class="showCayenne"> <img src="/Public/images/cayenne.png" class="showCayenne"><?php endif; ?>
                                <?php if($v["hot_level"] == 3): ?><img src="/Public/images/cayenne.png" class="showCayenne"> <img src="/Public/images/cayenne.png" class="showCayenne"> <img src="/Public/images/cayenne.png" class="showCayenne"><?php endif; ?>
                            </td>
                            <td class="dishes-list-price"><?php echo ($v["foods_num_day"]); ?>{{langData.copies[lang]}}</td>
                            <!-- <td>
                                <?php if(($v["is_prom"]) == "0"): ?><span>关闭</span>
                                    <?php else: ?>
                                    <span>开启</span><?php endif; ?>
                            </td>
                            
                            <?php if(($v["is_sale"]) == "0"): ?><td>下架</td>
                                <?php else: ?>
                                <td>上架</td><?php endif; ?> -->
                            <td class="text-right">
                                <div class="checkbox-switch">
                                    <?php if(($v["is_sale"]) == "1"): ?><input type="checkbox" onclick="changestatu(<?php echo ($v["food_id"]); ?>)" checked="checked">
                                        <?php else: ?>
                                        <input type="checkbox" onclick="changestatu(<?php echo ($v["food_id"]); ?>)"><?php endif; ?>
                                    <label></label>
                                </div>
                            </td>
                            <td class="dishes-list-operation">
                                <button class="edit-btn" onclick="modify_food(this)" data-food_id="<?php echo ($v["food_id"]); ?>"></button>
                                <button class="remove-btn" onclick="delfoodinfo(<?php echo ($v["food_id"]); ?>)"></button>
                            </td>
                        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </table>
                <div class="text-center">
                    <ul class="pagination" id="detail-page" v-if="lang=='zh-CN'"><?php echo ($page1); ?></ul>
                    <ul class="pagination" id="detail-page" v-if="lang=='zh-TW'"><?php echo ($page2); ?></ul>
                    <ul class="pagination" id="detail-page" v-if="lang=='en'"><?php echo ($page3); ?></ul>
                </div>
            </div>
        </section>
        <!-- 菜品列表 end -->
    </div>

        </div>
        
        
    <!-- 新增分类模态框（Modal） -->
    <div class="modal fade dishesClassifyModal" id="addSort" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="myform" action="javascript:void(0)">
                    <div class="modal-header">
                        <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true" id="close_btn"></button>
                        <h4 class="modal-title">{{langData.dishesCategorySettings[lang]}}</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="way" id="way" />
                        <input type="hidden" name="food_category_id" id="food_category_id" />
                        <input type="hidden" name="restaurant_id" id="restaurant_id" />
                        <div class="modal-item">
                            <span>{{langData.CategoryChineseName[lang]}}:</span>
                            <input type="text" name="food_category_name" id="food_category_name">
                        </div>
                        <?php if($is_en): ?><div class="modal-item">
                                <span>{{langData.categoryEnglishName[lang]}}:</span>
                                <input type="text" name="food_category_name_en" id="food_category_name_en">
                            </div><?php endif; ?>
                        <div class="modal-item">
                            <span>{{langData.customIcon[lang]}}:</span>
                            <div class="file-content blue-btn">
                                <span>{{langData.customIcon[lang]}}</span>
                                <input type="file" name="user_define_img" onchange="preview(this)" id="user_define_img">
                            </div>
                        </div>
                        <input type="hidden" name="ico_category_type" id="ico_category_type" value="0" />
                        <div class="modal-item">
                            <span>{{langData.iconPreview[lang]}}:</span>
                            <img src="/Public/images/defaultFoodCate1.png" class="classify-icon" id="classify-icon">
                            <input type="hidden" name="img_url" id="img_url" value="/Public/images/defaultFoodCate1.png" />
                        </div>
                        <div>
                            <?php if(is_array($ico_detail)): foreach($ico_detail as $key=>$vo): ?><img src="<?php echo ($vo['photo']); ?>" class="classify-icon" onclick="point_img(this.src)"><?php endforeach; endif; ?>
                        </div>
                        <div class="modal-item">
                            <span>{{langData.setAsTiming[lang]}}</span>
                            <div class="checkbox-switch">
                                <input type="checkbox" name="is_timing" id="is_timing" onchange="showtime2()">
                                <label></label>
                            </div>
                            <!--         <input type="radio" name="is_timing" value="0" onclick="hiddentime2()" checked="checked"> 关闭
                            <input type="radio" name="is_timing" value="1" onclick="showtime2()">开启 -->
                        </div>
                        <div id="show2" class="modal-item">
                            <ul id="myTab" class="nav nav-tabs">
                                <li class="active">
                                    <a href="#day" data-toggle="tab" onclick="changeType(0)">{{langData.weekTiming[lang]}}</a>
                                </li>
                                <li>
                                    <a href="#time" data-toggle="tab" onclick="changeType(1)">{{langData.dateTiming[lang]}}</a>
                                </li>
                            </ul>
                            <div id="myTabContent" class="tab-content">
                                <div class="tab-pane fade in active" id="day"></div>
                                <div class="tab-pane fade" id="time"></div>
                            </div>
                            <div class="modal-item">
                                <button class="blue-btn" id="add-btn" onclick="addTiming(this)" data-type="0">{{langData.add[lang]}}</button>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="button" class="blue-btn" data-dismiss="modal" onclick="commit()">{{langData.save[lang]}}</button>
                        </div>
                    </div>
                    <!--</form>-->
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal -->
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
    <script src="/Public/js/Dishes_index.js?v201711"></script>
    <script type="text/javascript">
    var ico_img = '/Public/images/defaultFoodCate1.png';

    function preview(file) {
        var picinfo = file.files[0]; //input
        if (picinfo.size > 1 * 1024 * 1024) {
            layer.msg(vm.langData.uploadLimit[vm.lang]);
            $("input[name='user_define_img']").val('');
            $("#classify-icon").attr('src', ico_img);
            return false;
        }
        if (file.files && file.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                $("#classify-icon").attr('src', evt.target.result);
                $("#img_url").val(evt.target.result);
                // 类型归为自定义图标
                $("#ico_category_type").val(2);
            }
            reader.readAsDataURL(file.files[0]);
        }
    }

    $("#close_btn").click(function() {
        $("#classify-icon").attr('src', ico_img);
        $("#img_url").val(ico_img);
        // 类型归为默认图标
        $("#ico_category_type").val(0);

        var file = $("#user_define_img")
        file.after(file.clone().val(""));
        file.remove();
    });

    function point_img(src) {
        var pos = src.indexOf("/Public");
        var final_src = src.substr(pos);
        // 类型归为系统图标
        $("#ico_category_type").val(1);

        $("#classify-icon").attr('src', final_src);
        $("#img_url").val(final_src);

        var file = $("#user_define_img")
        file.after(file.clone().val(""));
        file.remove();
    }
    </script>

</body>

</html>