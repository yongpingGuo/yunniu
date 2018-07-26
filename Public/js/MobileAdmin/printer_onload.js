$(function(){
    var printer_type = $('#print_type').val();
    if(printer_type == 0 || printer_type == 1){
        $( "#bill" ).addClass( "active" );
    }else if(printer_type == 2){
        $( "#tag" ).addClass( "active" );
    }
});

// 打印机类型选择
function print_type_select(param){
    if(param == 'bill'){
        $( "#bill" ).addClass( "active" );
        $( "#tag" ).removeClass("active");
        $('#print_type').val(0);
    }else if(param == 'tag'){
        $( "#tag" ).addClass( "active" );
        $( "#bill" ).removeClass("active");
        $('#print_type').val(2)
    }
}
