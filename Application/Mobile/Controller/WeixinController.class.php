<?php
namespace Mobile\Controller;
use Think\Controller;
use data\service\Restaurant;
class WeixinController extends Controller {
        #　会员同意授权，如果用户同意授权，页面将跳转至 redirect_uri/?code=CODE&state=STATE。
    public function getUserDetail(){
        // 获取到微信公众号设置链接处传递过来的代理ID
        $business_id = I("get.business_id");
        $restaurant_id = I("get.restaurant_id");
        // 存到session中
        session("business_id",$business_id);
        session("restaurant_id",$restaurant_id);

        // 判断是店铺进来还是代理进来，如果带有restaurant_flag，则是店铺进来
        $restaurant_flag = I('get.restaurant_flag');
        if($restaurant_flag){
            session("restaurant_flag",$restaurant_flag);
        }

        // 判断后台是开了店铺还是开了代理
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();
        $business_info = $S_Restaurant->getBusinessInfo($restaurant_info['business_id']);
        if($business_info['vip_mode'] == 1 && $restaurant_flag){// 关联代理的会员，但是进来的URL却是店铺的
            exit('当前开启的是代理的会员，请从代理的公众号进入');
        }elseif($business_info['vip_mode'] == 0 && !$restaurant_flag)// 关联店铺的会员，但是进来的URL却是代理的
        {
            exit('当前开启的是店铺的会员，请从店铺的公众号进入');
        }


        // 查询出数据库中的当前代理的对应的appid
        $public_number_set = D("public_number_set");
        $public_info = $public_number_set->where(array("business_id"=>$business_id))->find();
        $appid = $public_info['appid'];
        $AppSecret = $public_info['appsecret'];


        // 1、获取到code
        // $appid = "wxa9be3598671d1982";  // 云牛appid
        // $redirect_uri = urlencode("http://shop.founpad.com/index.php/Mobile/member/receiver_weixin");    // 获取到授权后要跳转到的地址
        $redirect_uri = urlencode(C('HOST_NAME')."/index.php/Mobile/member/receiver_weixin?appid=".$appid."&AppSecret=".$AppSecret);    // 获取到授权后要跳转到的地址
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
        // $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect&appid=".$appid."&AppSecret=".$AppSecret;
        header("location:".$url);
    }

}