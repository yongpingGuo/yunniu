$("#form_date").datetimepicker({
    format:'yyyy-mm-dd',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    minView: "month", //选择日期后，不会再跳转去选择时分秒 
    language:  'zh-CN'
});
$("#form_starttime").datetimepicker({
    format:'hh:ii:00',
    autoclose: true,
    startView: "hour", //选择时分秒 
    language:  'zh-CN'
});
$("#form_endtime").datetimepicker({
    format:'hh:ii:00',
    autoclose: true,
    startView: "hour", //选择时分秒 
    language:  'zh-CN'
});
$('#startDate').datetimepicker({
    format:'yyyy-mm-dd',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    minView: "month", //选择日期后，不会再跳转去选择时分秒 
    language:  'zh-CN'
}).on("click",function(ev){
    $(this).datetimepicker("setEndDate", $("#endtDate").val());
});
$('#endtDate').datetimepicker({
    format:'yyyy-mm-dd',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    minView: "month", //选择日期后，不会再跳转去选择时分秒 
    language:  'zh-CN'
}).on("click", function (ev) {
    $(this).datetimepicker("setStartDate", $("#startDate").val());
});

$('#discount_startDate').datetimepicker({
    format:'yyyy-mm-dd hh:ii:00',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    language:  'zh-CN'
}).on("click",function(ev){
    $(this).datetimepicker("setEndDate", $("#discount_endtDate").val());
});
$('#discount_endtDate').datetimepicker({
    format:'yyyy-mm-dd hh:ii:00',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    language:  'zh-CN'
}).on("click", function (ev) {
    $(this).datetimepicker("setStartDate", $("#discount_startDate").val());
});

$('#startTime').datetimepicker({
    format:'hh:ii:00',
    autoclose: true,
    startView: "hour", //选择时分秒 
    language:  'zh-CN'
}).on("click", function (ev) {
    $(this).datetimepicker("setStartDate", $("#startDate").val());
});
$('#endTime').datetimepicker({
   format:'hh:ii:00',
    autoclose: true,
    startView: "hour", //选择时分秒 
    language:  'zh-CN'
}).on("click", function (ev) {
    $(this).datetimepicker("setStartDate", $("#endtDate").val());
});


$("#device_start_time").datetimepicker({
    format:'yyyy-mm-dd',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    minView: "month", //选择日期后，不会再跳转去选择时分秒 
    language:  'zh-CN'
});

$("#device_end_time").datetimepicker({
    format:'yyyy-mm-dd',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    minView: "month", //选择日期后，不会再跳转去选择时分秒 
    language:  'zh-CN'
});