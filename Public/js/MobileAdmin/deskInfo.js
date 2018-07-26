//编辑微信order标题
function update_title() {
    var wx_order_title = $("#wx_order_title").val();
    $.ajax({
        type: "post",
        url: "/index.php/MobileAdmin/Device/update_title",
        data: { "wx_order_title": wx_order_title },
        dataType: "json",
        async: true,
        success: function(data) {
            layer.msg(data.msg);
        },
        error: function() {
            layer.msg("网络错误");
        }
    });
}

function editDesk(obj) {
    var desk_code = $(obj).data("desk_code");
    var desk_id = $(obj).data("desk_id");
    location.href = '/index.php/MobileAdmin/Device/deskEdit/desk_code/' + desk_code + '/desk_id/' + desk_id;
}

function submit_deskForm() {
    var form = $('#desk_form')[0];
    var formData = new FormData(form);
    var url = "";
    var type1 = $('#desk_form input').eq(1).val();
    if (type1 == "add") {
        url = "/index.php/MobileAdmin/device/deskAdd";
    } else if (type1 == "edit") {
        url = "/index.php/MobileAdmin/device/editDesk";
    }
    $.ajax({
        url: url,
        dataType: "json",
        type: "post",
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(msg) {
            if (msg.code == 1) {
                self.location.href = "/index.php/MobileAdmin/device/deskInfo";
            } else {
                alert("新增失败");
            }
        },
        error: function() {
            alert("出错了");
        }
    });
}

function downloadImg(obj) {
    var img_path = $(obj).data('img_path');
    location.href = "/index.php/MobileAdmin/device/downloadImg?imgPath=" + img_path;
}

function delDesk(obj) {
    var desk_id = $(obj).data("desk_id");
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        $.ajax({
            url: "/index.php/MobileAdmin/device/delDesk",
            data: { "desk_id": desk_id },
            dataType: 'json',
            type: "post",
            success: function(msg) {
                if (msg.code == 1) {
                    //self.location.href = "/index.php/MobileAdmin/device/deskInfo";
                    $(obj ).parent().parent().remove();
                } else {
                    layer.msg('删除出错');
                }
            },
            error: function() {
                alert("出错了");
            }
        });
        layer.close(index);
    });
}