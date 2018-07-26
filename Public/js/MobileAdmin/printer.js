function update(printer_id){
    self.location.href = '/index.php/MobileAdmin/DataDock/bill_edit/printer_id/'+printer_id;
}

function submit_printer(){
    var printer_name = $('input[name="printer_name"]' ).val();
    var printer_ip = $('input[name="printer_ip"]' ).val();
    var printer_port = $('input[name="printer_port"]' ).val();
    if(printer_name == ''){
        layer.msg('打印机名称不能为空');
        return false;
    }
    if(printer_ip == ''){
        layer.msg('打印机ip不能为空');
        return false;
    }
    if(printer_port == ''){
        layer.msg('打印机端口号不能为空');
        return false;
    }

    var form = $("#printerInfo")[0];
    var formData = new FormData(form);
    $.ajax({
        url:'/index.php/MobileAdmin/DataDock/addeditprinter',
        data:formData,
        type:'post',
        dataType:'json',
        cache:false,
        processData:false,
        contentType:false,
        success:function(msg){
            if(msg.code == 1){
                alert('操作成功');
                self.location.href = "/index.php/MobileAdmin/DataDock/printer";
            }
        },
        error:function(){
            console.log('访问出错');
        }
    });
}

function deletePrinter(obj){
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        var printer_id = $(obj).data("printer_id");
        $.ajax({
            url:"/index.php/MobileAdmin/DataDock/deletePrinter",
            data:{'printer_id':printer_id},
            type:'post',
            dataType:'json',
            success:function(msg){
                if(msg.code == 1){
                    self.location.href = "/index.php/MobileAdmin/DataDock/printer";
                }
            },
            error:function(){
                console.log("访问出错了");
            }
        });
        layer.close(index);
    });
    return false;
}

