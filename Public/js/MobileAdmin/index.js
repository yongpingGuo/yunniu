function loginout() {
    //询问框
    layer.confirm("确定要退出？", function() {
        $.ajax({
            type: "get",
            url: "/index.php/MobileAdmin/Index/loginout",
            async: true,
            dataType: "json",
            success: function(data) {
                if (data.code == 0) {
                    location.href = "/index.php/MobileAdmin/Index/login";
                }
            }
        });
    });
}
$(function(){
    $('iframe').css('display','none');
})