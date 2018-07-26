<?php
namespace Mobile\Controller;
use Think\Controller;
use data\service\Order as ServiceOrder;
use data\api\JsSdk;
use data\service\Restaurant;

class OrderController extends Controller
{
    private $S_order;
    public function __construct() {
        parent::__construct();
        $this->S_order = new ServiceOrder(); 
    }
    /*
    *订单列表
    */
    public function index() {
        if(empty(cookie('restaurant_id'))) $this->error("您的缓存信息已过期，请先进入店铺!");
        $code = $_GET['code'];
        session("restaurant_id", cookie('restaurant_id'));//赋值店铺id
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();
        $business_info = $S_Restaurant->getBusinessInfo($restaurant_info['business_id']);
        session("wx_prepaid_flag", null);
        session("business_id", null);
        if($business_info['type'] == 1){//多店铺时读代理配置信息
            session("wx_prepaid_flag", 1);
            session("business_id", $restaurant_info['business_id']);
        }
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        if (empty($code))
        {
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME")."/index.php/mobile/Order/index");
            Header("Location: $url");
            exit;
        }
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();
        cookie("openid", $openid);
        $where = "openid ='".$openid."' and is_reserve= 1 and (order_status = 3 or order_status = 12)";
        $list = $this->S_order->getList($where);
        $this->assign('list', $list);
        $this->display();
    }
    /*
    *订单详情
    */
    public function info() {
        $order_id = intval(I("order_id"));
        if(empty($order_id)) $this->error("参数错误");
        $S_Restaurant = new Restaurant();
        $config = $S_Restaurant->getWxConfig();
        $S_JsSdk = new JsSdk($config['wxpay_appid'], $config['wxpay_appsecret']);
        $jssdk_config = $S_JsSdk->getSignPackage();
        $order_info = $this->S_order->getWxInfo($order_id);
        session("restaurant_id", $order_info['restaurant_id']);
        $this->assign('order_info', $order_info);
        $this->assign('order_id', $order_id);
        $this->assign('jssdk_config', $jssdk_config);
        $this->display();
    }
    /*
    *订单消费码
    */
    public function consumptCode() {
        $order_id = intval(I("order_id"));
        if(empty($order_id)) $this->error("参数错误");
        $order_info = $this->S_order->getWxInfo($order_id);
        if(empty($order_info['consumpt_code'])){
            Vendor('phpqrcode.phpqrcode');
            //$url = C("HOST_NAME").U("Order/check", "order_id=$order_id");
            $url = $order_id;
            $path = "./Public/Uploads/Order/".date("Y-m-d/", time());
            if(!is_readable($path)) is_file($path) or mkdir($path,0700);
            $img_path = $path.$order_info['order_sn'].".png";
            $qrcode = new \QRcode();
            $qrcode->png($url, $img_path, 3, 4, 2);//操作消费券二维码
            $this->S_order->updateInfo(array("order_id"=>$order_id), array('consumpt_code'=>substr($img_path, 1)));
        }
        $consumpt_code = empty($order_info['consumpt_code'])?substr($img_path, 1):$order_info['consumpt_code'];
        $this->assign('consumpt_code', $consumpt_code);
        $this->assign('order_info', $order_info);
        $this->display();
    }
    /*
    *订单消费券核对
    */
    public function check() {
        $order_id = intval(I("order_id"));
        if(empty($order_id)) $this->ajaxReturn(array('code'=>1, 'msg'=>'参数错误'));
    }
}