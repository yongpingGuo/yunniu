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
            
	<div class="sale-search-form">
		<form  method="get" id="myform">
			<div class="item">
				<span>{{langData.cashier[lang]}}:</span>
					<select name="cashier_id" id="cashier_id" class="select-grey">
					<option value="">{{langData.all[lang]}}</option>
					<?php foreach ($cashierList as $k => $v): if($cashier_id == $v['cashier_id']) { $selected = "selected='selected'"; }else{ $selected = ""; } ?>
						<option value="<?php echo $v['cashier_id'];?>" <?php echo $selected;?>><?php echo $v['cashier_name'];?></option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="item">
				<span>{{langData.date[lang]}}:</span>
				<input class="selectIcon" type="text" id="startDate" name="startDate" value="<?php echo ($startDate); ?>">
				<span>-</span>
				<input class="selectIcon" type="text" id="endtDate" name="endtDate" value="<?php echo ($endDate); ?>">
			</div>
			<div class="item">
				<span>{{langData.time[lang]}}:</span>
				<input class="selectIcon" type="text" id="startTime" name="startTime" value="<?php echo ($startTime); ?>">
				<span>-</span>
				<input class="selectIcon" type="text" id="endTime" name="endTime" value="<?php echo ($endTime); ?>">
			</div>
			<button class="blue-btn" type="button" data-commit_type = "0" onclick="search(this)">{{langData.search[lang]}}</button>
			<button class="blue-btn" type="button" data-commit_type = "1" onclick="search(this)">
				<span>{{langData.export[lang]}}</span>
				<img src="/Public/images/out.png" class="mini-icon">
			</button>
		</form>
	</div>
	<div class="clearfix" id="ajax_html">
		<section class="section small-section pull-left">
			<div class="section-header">{{langData.mainCourse[lang]}}</div>
			<div class="section-content dishes-stat">
				<table>
					<?php if(is_array($all_foodinfo)): $i = 0; $__LIST__ = $all_foodinfo;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
							<td><?php echo ($key+1); ?></td>
							<td>
								<div class="food-chart-name"><?php echo ($v["food_name"]); ?></div>
							</td>
							<td class="progress-td">
								<div class="dishes-progress-bg">
									<div class="dishes-progress" style="width:<?php echo ($v[num]*$step_length); ?>px;"></div>
								</div>
							</td>
							<td><?php echo ($v["num"]); ?>{{langData.copies[lang]}}</td>	
						</tr><?php endforeach; endif; else: echo "" ;endif; ?>
				</table>	
			</div>
		</section>
		<section class="section small-section pull-left">
			<div class="section-header">{{langData.specification[lang]}}</div>
			<div class="section-content dishes-stat">
				<table>
					<?php if(is_array($all_attributeArr)): $i = 0; $__LIST__ = $all_attributeArr;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
							<td><?php echo ($key+1); ?></td>
							<td>
								<div class="food-chart-name"><?php echo ($v["food_attribute_name"]); ?></div>
							</td>
							<td class="progress-td">
								<div class="dishes-progress-bg">
									<div class="dishes-progress" style="width:<?php echo ($v[num]*$step_length_attr); ?>px;"></div>
								</div>
							</td>
							<td><?php echo ($v[num]); ?>{{langData.copies[lang]}}</td>	
						</tr><?php endforeach; endif; else: echo "" ;endif; ?>
				</table>	
			</div>
		</section>		
		<div>
			<ul class="pagination" id="detail-page">
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
	<script src="/Public/js/Admin-Restaurant/Sale_food_chart.js"></script>

</body>

</html>