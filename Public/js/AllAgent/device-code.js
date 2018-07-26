/**
 * Created by Administrator on 2016/10/25.
 */
	//新增注册码
	function create_code(){
	    var form = $("#code_num")[0];
	    var formData = new FormData(form);
	    console.log(form);
	    console.log(formData);
	
	    $.ajax({
	        url:"/index.php/AllAgent/Code/create_code",
	        data:formData,
	        dataType:"json",
	        type:"post",
	        cache:false,
	        processData:false,
	        contentType:false,
	        success:function(msg){
	            if(msg.code == 1){
	                layer.msg('生成成功!');
	                self.location.href = "/index.php/AllAgent/Code/code/page/"+msg.page+".html";
	            }
	        },
	        error:function(){
	        	layer.msg('网络错误!');
	        }
	    });
	}

function editDevice(obj){
    var dataInfo = $(obj).data();
    console.log(dataInfo);
    $("#business option").html(dataInfo.business_name);
    $("#business option").val(dataInfo.business_id);
    $("#code").val(dataInfo.code);
    $("#endTime").val(dataInfo.endtime);
    $("#deviceModal").modal("show");
}

$("#endTime").datetimepicker({
    format:'yyyy-mm-dd hh:ii:ss',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    minView: "hour", //选择日期后，不会再跳转去选择时分秒
    language:  'zh-CN'
});


$('#endTime').datetimepicker({
    format:'hh:ii:00',
    autoclose: true,
    startView: "hour", //选择时分秒
    language:  'zh-CN'
}).on("click", function (ev) {
    $(this).datetimepicker("setStartDate", $("#endtDate").val());
});

function isChange(obj){
    var business_id = $(obj).val();
    var code_id = $(obj).data("code_id");
    console.log(business_id);
    $.get("/index.php/AllAgent/Code/changeCodeBusiness",{"business_id":business_id,"code_id":code_id},function(msg){
        alert(msg.msg);
    });
}

function changeCodeTime(obj){
    var code_id = $(obj).data("code_id");
    var start_time = $("#start_time").val();
    var end_time = $("#end_time").val();
    console.log(code_id);
    $.ajax({
        url:'/index.php/AllAgent/Code/changeCodeTime',
        data:{"code_id":code_id,"start_time":start_time,"end_time":end_time},
        dataType:"json",
        type:"get",
        success:function(msg){
            $(obj).val(msg.data['codeTime']);
            $(obj).parent().next().children().val(msg.data['codeRestTime']);
            alert("修改成功");
        },
        error:function(){
            console.log("访问失败");
        }
    });
}
	//单个注册码删除
	function deleteCode(obj){
		var nowpage = parseInt($('.current').html());
	    if(confirm("你删除的注册码可能关联着其它设备，是否删除？")){
	        var code_id = $(obj).data("code_id");
	        $.getJSON("/index.php/AllAgent/Code/deleteCode",{"code_id":code_id},function(msg){
				self.location.href="/index.php/AllAgent/Code/code/page/"+msg.page+".html";
	        });
	    }
	}

		function findInfo(obj){
		    var code_id = $(obj).data("code_id");
		    console.log(code_id);
		    $.get("/index.php/AllAgent/Code/findInfo",{"code_id":code_id},function(msg){
		        console.log(msg);
		    });
		}
		
		//复选框全选、取消全选
		var isCheckAll = false;  
        function swapCheck() {  
            if (isCheckAll) {  
                $("input[type='checkbox']").each(function() {  
                    this.checked = false;  
                });  
                isCheckAll = false;  
            } else {  
                $("input[type='checkbox']").each(function() {  
                    this.checked = true;  
                });  
                isCheckAll = true;  
            }  
        }
        
        //批量删除勾选的
        function batch_delete(){
        	var msg = confirm('确定要批量删除?');
        	if(msg == true){
        		text = $("input:checkbox[name='check_code']:checked").map(function(index,elem) {
	            return $(elem).val();
		        }).get().join(',');
		      
		        $.ajax({
		        	type:"get",
		        	url:"/index.php/allAgent/Code/batch_delete/code_id/"+text,
		        	async:true,
		        	dataType:"json",
		        	success:function(data){
		        		console.log(data);
		        		if(data.code == 1){
		        			self.location.href = "/index.php/AllAgent/Code/code/page/"+data.page+".html";
		        		}else{
		        			layer.msg(data.msg);
		        		}
		        	}
		        });
        	}
        }