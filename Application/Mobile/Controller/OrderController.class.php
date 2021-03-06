<?php
namespace Mobile\Controller;
use Think\Controller;
use data\service\Order as ServiceOrder;
use data\api\JsSdk;
use data\service\Restaurant;
use data\service\TakeMeal as ServiceTakeMeal;

class OrderController extends Controller
{
    private $S_order;
    public function __construct() {
        parent::__construct();
        $this->S_order = new ServiceOrder(); 
        $this->S_take = new ServiceTakeMeal();
    }
    /*
    *订单列表
    */
    public function index() {
        $code = $_GET['code'];
        $restaurant_id = intval(I("restaurant_id"));
        $restaurants_id = intval(I("restaurants_id"));
        $business_id = intval(I("business_id"));
        if($restaurant_id > 0){//单店铺
            session("restaurant_id", $restaurant_id);//赋值店铺id
            session("wx_prepaid_flag", null);
            session("business_id", null);
        }else {//多店铺
            session("wx_prepaid_flag", 1);
            session("business_id", $business_id);
        }
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        if (empty($code))
        {
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME")."/index.php/mobile/Order/index/restaurant_id/$restaurant_id/business_id/$business_id/restaurants_id/$restaurants_id");
            Header("Location: $url");
            exit;
        }
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();
        cookie("openid", $openid);
        $where = "openid ='".$openid."' and (is_reserve= 1 or is_reserve= 0) and (order_status = 3 or order_status = 12)";
        $list = $this->S_order->getList($where);
        $this->assign('list', $list);
        if($restaurant_id==0)
        {
          $Order_url=C('HOST_NAME').'/Mobile/index/homePage/business_id/'.$business_id;
        }
        else{
          $Order_url=C('HOST_NAME').'/Mobile/index/homePage/restaurant_id/'.$restaurant_id;
        }
        // dump(session());
        $this->assign('url', $Order_url);
        $this->display('Index/orderList');
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
        /*判断验证码是否存在，如果不存在将生成验证码和二维码*/
        if(empty($order_info['cancell_num'])&&$order_info['push_status']!='5')
            {
              $order_info['cancell_num'] =$this->addcreateCellNum($order_info['order_sn'],$order_info['restaurant_id']);
              Vendor('phpqrcode.phpqrcode');
            //$url = C("HOST_NAME").U("Order/check", "order_id=$order_id");
            $url = $order_info['cancell_num'];
            if(!empty($order_info['consumpt_code']))
            {
                unlink('.'.$order_info['consumpt_code']);
            }
            $path = "./Public/Uploads/Order/".date("Y-m-d/", time());
            if(!is_readable($path)) is_file($path) or mkdir($path,0700);
            $img_path = $path.$order_info['order_sn'].".png";
            $qrcode = new \QRcode();
            $qrcode->png($url, $img_path, 3, 4, 2);//操作消费券二维码
            $this->S_order->updateInfo(array("order_id"=>$order_id), array('consumpt_code'=>substr($img_path, 1)));
            $order_info['consumpt_code']=substr($img_path, 1);
            }
            /*生成二维码*/
            if(empty($order_info['consumpt_code'])&&$order_info['push_status']!='5')
            {
              Vendor('phpqrcode.phpqrcode');
            //$url = C("HOST_NAME").U("Order/check", "order_id=$order_id");
            $url = $order_info['cancell_num'];
            $path = "./Public/Uploads/Order/".date("Y-m-d/", time());
            if(!is_readable($path)) is_file($path) or mkdir($path,0700);
            $img_path = $path.$order_info['order_sn'].".png";
            $qrcode = new \QRcode();
            $qrcode->png($url, $img_path, 3, 4, 2);//操作消费券二维码
            $this->S_order->updateInfo(array("order_id"=>$order_id), array('consumpt_code'=>substr($img_path, 1)));
            }
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();//获取餐厅信息
        $consumpt_code = empty($order_info['consumpt_code'])?substr($img_path, 1):$order_info['consumpt_code'];
        $time = $order_info['use_day'] == 2?date("Y-m-d", $order_info['pay_time'] + 86400):date("Y-m-d", $order_info['pay_time']);
        // dump($order_info);
        $this->assign('use_time', $time." ".$order_info['use_time']);
        $this->assign('order_info', $order_info);
        $this->assign('order_id', $order_id);
        $this->assign('consumpt_code', $consumpt_code);
        $this->assign('restaurant_info', $restaurant_info);
        $this->assign('jssdk_config', $jssdk_config);
        $this->display('Index/orderInfo');
    }
        /**
    *当核销码不存在时生成核销码
    **/
    public function addcreateCellNum($order_sn,$restaurant_id){
       $order = order();
       $ret=$order->where(array('order_sn'=>$order_sn))->field('cancell_num')->find();
       if(!empty($ret['cancell_num']))
       {
        return $ret['cancell_num'];
       }
       while (!is_numeric($cancell_num)) {
           $cancell_num=$this->S_take->createCellNum($order_sn,$restaurant_id);
       }
       
       return $cancell_num;
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