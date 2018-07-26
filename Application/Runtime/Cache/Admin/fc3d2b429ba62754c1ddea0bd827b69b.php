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
	    <li>
	        <a href="<?php echo U('Restaurant/receipt');?>">{{langData.customerTicketSet[lang]}}</a>
	    </li>
	    <li class="active">
	        <a href="<?php echo U('DataDock/printer');?>">{{langData.kitchenPrintSet[lang]}}</a>
	    </li>
	</ul>
	<section class="section printer-content">
		<div class="section-header">{{langData.kitchenLabelPrinter[lang]}}</div>
		<div class="section-content">
			<table class="print-table">
				<tr>
					<td>{{langData.name[lang]}}</td>
					<td>{{langData.template[lang]}}</td>
					<td>{{langData.IP[lang]}}</td>
					<td>{{langData.port[lang]}}</td>
					<td>{{langData.remarks[lang]}}</td>
					<td></td>
				</tr>
				<?php if(is_array($printList)): $i = 0; $__LIST__ = $printList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
						<td><?php echo ($vo["printer_name"]); ?></td>
						<td>
							<?php if($vo["print_type"] == 0): ?>{{langData.mainKitchen[lang]}}
								<?php elseif($vo["print_type"] == 1): ?>
								{{langData.deputyKitchen[lang]}}
								<?php else: ?>
								{{langData.label[lang]}}<?php endif; ?>
						</td>
						<td><?php echo ($vo["printer_ip"]); ?></td>
						<td><?php echo ($vo["printer_port"]); ?></td>
						<td><?php echo ($vo["printer_brand"]); ?></td>
						<td>
							<button onclick="editPrinter(this)" data-printer_id="<?php echo ($vo["printer_id"]); ?>" data-printer_name="<?php echo ($vo["printer_name"]); ?>" data-printer_ip="<?php echo ($vo["printer_ip"]); ?>" data-printer_brand="<?php echo ($vo["printer_brand"]); ?>" data-printer_version="<?php echo ($vo["printer_version"]); ?>" data-printer_port="<?php echo ($vo["printer_port"]); ?>" data-print_type="<?php echo ($vo["print_type"]); ?>">
								<img src="/Public/images/edit.png">
							</button>
							<button data-printer_id="<?php echo ($vo["printer_id"]); ?>" onclick="deletePrinter(this)">
								<img src="/Public/images/remove.png">
							</button>
						</td>
					</tr><?php endforeach; endif; else: echo "" ;endif; ?>
			</table>
			<button class="blue-btn" onclick="addPrinter()">{{langData.addPrinter[lang]}}</button>
			<div class="small print-tips">{{langData.printerTips[lang]}}</div>
		</div>
	</section>

        </div>
        
        
	<!-- 模态框（Modal） -->
	<div class="modal fade" id="printModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
					<h4 class="modal-title" id="myModalLabel">{{langData.addPrinter[lang]}}</h4>
				</div>
				<div class="modal-body">
					<form action="javascript:void(0)" id="printerInfo">
						<input type="hidden" name="type" id="type" value="add">
						<table class="printModal-table table-condensed">
							<tbody>
								<tr>
									<td>{{langData.name[lang]}}:</td>
									<td>
										<input type="text" value="" name="printer_name" id="printer_name" :placeholder="langData.name[lang]">
									</td>
								</tr>
								<tr>
									<td>{{langData.IP[lang]}}:</td>
									<td>
										<input type="text" value="" name="printer_ip" id="printer_ip" :placeholder="langData.IP[lang]">
										<span class="info-input">{{langData.IPInfo[lang]}}</span>
									</td>
								</tr>
								<tr>
									<td>{{langData.port[lang]}}:</td>
									<td><input type="text" value="9100" name="printer_port" readonly></td>
								</tr>
								<tr>
									<td>{{langData.remarks[lang]}}:</td>
									<td>
										<input type="text" value="" name="printer_brand" id="printer_brand">
									</td>
								</tr>
								<tr>
									<td>{{langData.template[lang]}}:</td>
									<td>
										<div class="clearfix">
											<div class="printer-template">
												<label for="print_type1"> 
													<img src="/Public/images/receipt1.png">
													<div>
														<input type="radio" name="print_type" value="0" checked id="print_type1" class="radio-circle">
														<i></i>
														<!-- <img src="/Public/images/radio.png"> -->
														<span>{{langData.ticketPrinter[lang]}}</span>
													</div>
												</label>
											</div>
									
											<div class="printer-template">
												<label for="print_type3"> 
													<img src="/Public/images/receipt2.png">
													<div>
														<input type="radio" name="print_type" value="2" id="print_type3" class="radio-circle">
														<i></i>
														<span>{{langData.labelPrinter[lang]}}</span>
													</div>
												</label>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
					<div class="text-center">
						<button type="button" class="blue-btn" onclick="submit_printer()">{{langData.save[lang]}}</button>
					</div>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal -->
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
	$('#nav_receipt').addClass('active').parent().show();
	function addPrinter(){
	
		$("#printModal").modal("show");
		//$("#printerInfo input").val("");
		$("#printer_port").val("9100");
		$("#type").val("add");

	}

	function editPrinter(obj){
		$("#type").val("edit");
		var data = $(obj).data();
		$("#printerInfo").append('<input type="hidden" name="printer_id" id="type" value="'+data['printer_id']+'">');
		$("#printer_name").val(data['printer_name']);
		$("#printer_ip").val(data['printer_ip']);
		$("#printer_port").val(data['printer_port']);
		$("#printer_brand").val(data['printer_brand']);
		$("#printer_version").val(data['printer_version']);
		$("input[name=print_type]").each(function(){
			if($(this).val() == data['print_type']){
				console.log($(this));
				$(this).prop("checked",true);
			}
		});


		$("#printModal").modal("show");
	}

	function submit_printer(){
		var form = $("#printerInfo")[0];
		var formData = new FormData(form);
		$.ajax({
			url:'/index.php/admin/DataDock/addeditprinter',
			data:formData,
			type:'post',
			dataType:'json',
			cache:false,
			processData:false,
			contentType:false,
			success:function(msg){
				if(msg.code == 1){
					location.reload();
				}
			},
			error:function(){
				console.log(vm.langData.error[vm.lang]);
			}
		});
	}

	function deletePrinter(obj){
		var printer_id = $(obj).data("printer_id");
		console.log(printer_id);
		$.ajax({
			url:"/index.php/admin/DataDock/deletePrinter",
			data:{'printer_id':printer_id},
			type:'post',
			dataType:'json',
			success:function(msg){
				if(msg.code == 1){
					location.reload();
				}
			},
			error:function(){
				console.log(vm.langData.error[vm.lang]);
			}
		});
	}
</script>

</body>

</html>