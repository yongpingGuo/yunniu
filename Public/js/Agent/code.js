/**
 * Created by Administrator on 2016/10/28.
 */

function changeCodeRestaurant(obj){
    var restaurant_id = $(obj).val();
    var code_id = $(obj).data("code_id");
    $.get("/index.php/agent/Code/changeCodeRestaurant",{"restaurant_id":restaurant_id,"code_id":code_id},function(msg){
        alert(msg.msg);
    });
}