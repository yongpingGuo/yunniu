/**
 * Created by Administrator on 2016/12/3.
 */

function changeCodeRestaurant(obj){
    var restaurant_id = $(obj).val();
    var code_id = $(obj).data("code_id");
    $.get("/index.php/agent/DeskCode/changeCodeRestaurant1",{"restaurant_id":restaurant_id,"code_id":code_id},function(msg){
    	alert(msg.msg);
    	if(msg.code == 0){
    		self.location.href = "/index.php/agent/DeskCode/deskCode";
    	}   
    });
}