<?php
namespace Mobile\Controller;
use Think\Controller;
use data\service\Order;
use data\service\DingDing;
use data\service\Restaurant;
use data\service\TakeMeal;
use data\service\SellOut as ServiceSellOut;

class DingDingController extends Controller {
    private $appid;
    private $secret;
    /*
    *初始化
    */
    public function _initialize() {
        $this->S_DingDing = new DingDing();
        $this->appid = "dingoaxlkzeusp07dcvb25";//开放应用平台的 
        $this->secret = "U3C6GX7hDn0eRafw3gNSeAr022stRURoE1qzLEQnHiFyam_wMw-Viwjoa0XcYGnk";//开放应用平台的
    }
    /*
    *获取企业用户id
    */
    public function getUserId() {
        $openid = cookie('openid');
        if(empty($openid)){
            $data = I("code");
            $user_info = $this->S_DingDing->getOpenidInfo($data['code']);
            cookie('openid', $user_info['userid'], 1296000);//店铺id默认缓存15天
            $openid = $user_info['userid'];
        }
        session("openid", $openid);
        echo session("openid");
    }
    /*
    *钉钉预点餐首页
    */
    public function index(){
        $data = I();
        if(empty($data['restaurant_id'])) $this->error("店铺id不能为空");
        session("restaurant_id", $data['restaurant_id']);//餐厅id
        session("desk_code", null);
        $dingding_config = $this->S_DingDing->getConfig($this->appid, $this->secret);
        
        $this->assign("dingding_config", $dingding_config);
        $this->display();
    }
    /*
    *取餐时间
    */
    public function selectEatTime() {
        $order_sn = I("get.order_sn");
        $restaurant_id = order()->where(array('order_sn'=>$order_sn))->getField('restaurant_id');
        session("restaurant_id", $restaurant_id);
        $S_order = new Order();
        $timeInfo = $S_order->getSetTimeInfo(1);
        if(empty($timeInfo['ext'])) $this->redirect('ding_ding/pay', 'order_sn='.$order_sn);
        if($timeInfo['types'] == 1){  //准时用餐
            // 过滤掉比当前小时：分钟小的数据
            $timeInfo['ext_tomo'] = $timeInfo['ext'];
            foreach($timeInfo['ext'] as $key=>$val){
                if(strtotime($val['times']) < time()){
                    unset($timeInfo['ext'][$key]);
                }
            }
        }else{//自由用餐
             $time_arr= json_decode($timeInfo['business_hours'],true);

            $time_start_tomorrow = strtotime($time_arr['0']) + $timeInfo['add_order_time'] * 60;

            //以下是今天的时间,time1_today  time2_today
            //判断当前时间跟起始时间

            $time_now_add_order_time = time() + $timeInfo['add_order_time'] * 60;
//            $time_today_start = strtotime($time_arr['0']) + $timeInfo['add_order_time'] * 60;
            if(strtotime($time_arr['0']) > $time_now_add_order_time ){
                $start_time = strtotime($time_arr['0']);
                $timeInfo['time1_today'] = date('H:i',$start_time);
            }else{
                $start_time = time() + $timeInfo['add_order_time'] * 60;
                $timeInfo['time1_today'] = date('H:i',$start_time);
            }

            //判断当前时间跟结束时间
            $endtime = time() + $timeInfo['add_order_time'] * 60;
            if(strtotime($time_arr['1']) < $endtime ){
                $timeInfo['time2_today'] = 0;
            }else{
                $timeInfo['time2_today'] = $time_arr['1'];    //结束时间
            }

            //以下time1,time2都是明天的时间
            $time_start_tomorrow = strtotime($time_arr['0']);
            $timeInfo['time1'] = date('H:i',$time_start_tomorrow);  //明天的结束时间
            $timeInfo['time2'] = $time_arr['1'];    //结束时间
        }

        $this->assign('timeInfo', $timeInfo);
        $this->assign('order_sn', $order_sn);
        $this->display();
    }
    /*
    *加载阿里支付配置
    */
    public function pay() {
        $order_sn = I("get.order_sn");
        if(empty($order_sn)) $this->error("订单号不能为空!");
        $rand = rand(1111, 9999);
        session('rand', $rand);
        $sing = md5($order_sn.$rand);
        $order_info = order()->where(array('order_sn'=>$order_sn))->find();
        $qrc_condition['restaurant_id'] = $order_info['restaurant_id'];
        $qrc_code_id = M("qrc_code")->where($qrc_condition)->getField("qrc_code_id");
        $qrcd_condition['qrc_code_id'] = $qrc_code_id;
        $device_code = M("qrc_device")->where($qrcd_condition)->getField('qrc_device_code');
        $this->assign('order_info', $order_info);
        $this->assign('device_code', $device_code);
        $this->assign('sing', $sing);
        $this->display();
    }
    /*
    *我的订单
    */
    public function orderList() {
        $S_order = new Order();
        $where = "openid ='".session('openid')."' and is_reserve= 1 and (order_status = 3 or order_status = 12)";
        $list = $S_order->getList($where);
        foreach($list as $key=>$val) {
            $time = $val['use_day'] == 2?date("Y-m-d", $val['pay_time'] + 86400):date("Y-m-d", $val['pay_time']);
            $list[$key]['use_time'] = $time." ".$val['use_time'];
        }
        $this->assign('list', $list);
        $url =  C('HOST_NAME').'/Mobile/ding_ding/index/restaurant_id/'.$_SESSION['restaurant_id'];
        $this->assign('url', $url);
        $this->display();
    }
    /*
    *订单详情
    */
    public function orderInfo() {
        $S_order = new Order();
        $order_id = intval(I("order_id"));
        if(empty($order_id)) $this->error("参数错误");
        $order_info = $S_order->getWxInfo($order_id);
        if(empty($order_info['consumpt_code'])){//生成二维码
            Vendor('phpqrcode.phpqrcode');
            //$url = C("HOST_NAME").U("Order/check", "order_id=$order_id");
            $url = $order_info['cancell_num'];
            $path = "./Public/Uploads/Order/".date("Y-m-d/", time());
            if(!is_readable($path)) is_file($path) or mkdir($path,0700);
            $img_path = $path.$order_info['order_sn'].".png";
            $qrcode = new \QRcode();
            $qrcode->png($url, $img_path, 3, 4, 2);//操作消费券二维码
            $S_order->updateInfo(array("order_id"=>$order_id), array('consumpt_code'=>substr($img_path, 1)));
        }
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();//获取餐厅信息
        $consumpt_code = empty($order_info['consumpt_code'])?substr($img_path, 1):$order_info['consumpt_code'];
        $time = $order_info['use_day'] == 2?date("Y-m-d", $order_info['pay_time'] + 86400):date("Y-m-d", $order_info['pay_time']);
        $this->assign('use_time', $time." ".$order_info['use_time']);
        $this->assign('consumpt_code', $consumpt_code);
        $this->assign('order_info', $order_info);
        $this->assign('restaurant_info', $restaurant_info);
        $this->display();
    }
    /*
    *钉钉支付宝支付后的异步通知
    */
    public function notifys() {
        $data = I();
        if(!empty($data)){
            $S_Order = new Order();
            $order_sn = $data['out_trade_no'];
            $where['order_sn'] = $data['out_trade_no'];
            $order_info = $S_Order->getPrimInfo($where);
            if($order_info['order_status'] >= 3 || $order_info['pay_time'] > 0) Return false;
            $data['order_status'] = 3;
            $data['pay_type'] = 1;
            $data['pay_time'] = time();
            $res = $S_Order->updateInfo($where, $data);
            if(empty($res)) Return false;
            // 售罄处理
            $S_SellOut = new ServiceSellOut();
            $S_SellOut->sellOutDeal($order_sn);
            $S_TakeMeal = new TakeMeal();
            $S_TakeMeal->takeMealPush_two($order_sn);
            echo "success";
        }
    }



}

