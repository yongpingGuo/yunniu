<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/10
 * Time: 14:38
 */
namespace Component\Controller;
use Think\Controller;
use \Push\Request\V20160801 as Push;
use data\service\TakeMeal as ServiceTakeMeal;

use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;
use ElemeOpenApi\Api\UserService;
use ElemeOpenApi\Api\ShopService;
use ElemeOpenApi\Api\OrderService;
use Think\Model;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\WxpayMicropay2\MicroPay_1;
use data\service\TakeMeal;


Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.OAuth.OAuthClient');
Vendor('ElemeOpenApi.Api.OrderService');
Vendor('ElemeOpenApi.Api.ShopService');

Vendor("ali_push.aliyun-php-sdk-core.Config");
Vendor("ali_push.Push.Request.V20160801.PushRequest");
Vendor("ali_push.Push.Request.V20160801/PushMessageToAndroidRequest");
Vendor("ali_push.Push.Request.V20160801/QueryPushStatByMsgRequest");

use data\service\SellOut as ServiceSellOut;

class DemoController extends Controller
{
    private function createTab2018(){
        // 2018年
        for($i=1;$i<13;$i++){
            if($i<10){
                $i = '0'.$i;
            }
            $this->createTable('2018'.$i);
        }
    }

    // 创建表（步骤一：创建2016到2017的表）
    private function create12(){
        // 2017年
        for($i=1;$i<13;$i++){
            if($i<10){
                $i = '0'.$i;
            }
            $this->createTable('2017'.$i);
        }

        // 2016年
        for($i=11;$i<13;$i++){
            $this->createTable('2016'.$i);
        }
    }

    // 导入2016年的数据（步骤二）
    private function addData2016()
    {
        // 2016年
        $data = monthForYear(2016);
        foreach($data as $key=>$val){
            $start = $val['month_start'];
            $end = $val['month_end'];
            $temp = $key+1;
            if($temp>10){
                $str = "INSERT INTO order_2016$temp SELECT * FROM `order` WHERE add_time BETWEEN $start AND $end";
                $res = $data = M()->execute($str);
                dump($res);
            }
        }

        $data = monthForYear(2016);
        foreach($data as $key=>$val){
            $temp = $key+1;
            if($temp>10){
                $str = "INSERT INTO order_food_2016$temp SELECT * FROM order_food WHERE order_id in (SELECT order_id FROM order_2016$temp)";
                $res = $data = M()->execute($str);
                dump($res);
            }
        }

        $data = monthForYear(2016);
        foreach($data as $key=>$val){
            $temp = $key+1;
            if($temp>10){
                $str = "INSERT INTO order_food_attribute_2016$temp SELECT * FROM order_food_attribute WHERE order_food_id in (SELECT order_food_id FROM order_food_2016$temp)";
                $res = $data = M()->execute($str);
                dump($res);
            }
        }
    }

    // 导入2017数据到order（步骤二）
    private function addToOrder(){
        $data = monthForYear(2017);
        foreach($data as $key=>$val){
            $start = $val['month_start'];
            $end = $val['month_end'];
            $temp = $key+1;
            if($temp<10){
                $m = '0'.$temp;
            }else{
                $m = $temp;
            }

            $str = "INSERT INTO order_2017$m SELECT * FROM `order` WHERE add_time BETWEEN $start AND $end";
            $res = $data = M()->execute($str);
            dump($res);
        }
    }

    // 导入2017数据到order_food（步骤二）
    private function addToOrderFood(){
        $data = monthForYear(2017);
        foreach($data as $key=>$val){
            // 前8个表（分开导入）
            if($key>=7){
                $temp = $key+1;
                if($temp<10){
                    $m = '0'.$temp;
                }else{
                    $m = $temp;
                }

                $str = "INSERT INTO order_food_2017$m SELECT * FROM order_food WHERE order_id in (SELECT order_id FROM order_2017$m)";
                $res = $data = M()->execute($str);
                dump($res);
            }
        }
    }

    // 导入2017数据到order_food_attribute（步骤二）
    private function addToOrderFoodAttr(){
        $data = monthForYear(2017);
        foreach($data as $key=>$val){
            // 前8个表（分开导入）
            if($key>=7){
                $temp = $key+1;
                if($temp<10){
                    $m = '0'.$temp;
                }else{
                    $m = $temp;
                }

                $str = "INSERT INTO order_food_attribute_2017$m SELECT * FROM order_food_attribute WHERE order_food_id in (SELECT order_food_id FROM order_food_2017$m)";
                $res = $data = M()->execute($str);
                dump($res);
            }
        }
    }

    // 建表
    private function createTable($month){
        $orderTable = 'order_' . $month;
        $orderFoodTable = 'order_food_' . $month;
        $orderFoodAttributeTable= 'order_food_attribute_' . $month;
        // order表
        $res1 = M()->execute(
            "Create Table If Not Exists restaurant.$orderTable(
               `order_id` int(11) NOT NULL AUTO_INCREMENT,
              `order_sn` varchar(100) NOT NULL COMMENT '订单号',
              `order_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '就餐方式（1店吃，2打包带走，3微信外卖）',
              `pay_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付方式（0现金，1支付宝，2微信，3未支付,4余额，5第四方支付）',
              `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总价',
              `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '订单生成（下单）时间',
              `pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '订单支付时间',
              `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
              `use_day` tinyint(1) DEFAULT '1' COMMENT '使用时间1今天 2明天',
              `use_time` char(30) DEFAULT '' COMMENT '使用具体时间',
              `order_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '订单的状态（0待支付，1已接单，2未接单，3已支付，4未配送，5配送中，6未收货，7已收货，8未评价，9已评价,10已删除,11请取餐,12核销）',
              `restaurant_id` int(11) NOT NULL COMMENT '店铺ID',
              `restaurant_name` varchar(80) DEFAULT '' COMMENT '餐厅名字',
              `table_num` char(4) NOT NULL DEFAULT '0' COMMENT '餐桌号',
              `terminal_order` tinyint(4) NOT NULL DEFAULT '1' COMMENT '(终端点餐，1:横竖屏，2：收银台，3：微信移动',
              `desk_code` varchar(50) DEFAULT '0',
              `consumpt_code` varchar(150) DEFAULT NULL COMMENT '消费码',
              `pay_num` varchar(10) NOT NULL DEFAULT '0' COMMENT '取餐号',
              `define_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '自定义折扣',
              `discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '折扣,如7折，则0.7。。7.5折则0.75，默认0.0没有享受折扣',
              `reduce` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '立减多少',
              `benefit_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠了多少(原价-优惠价），其中，优惠价=原价*折扣-立减',
              `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
              `vip_or_restaurant` tinyint(4) NOT NULL DEFAULT '1' COMMENT '区分是会员折扣还是整个店铺的折扣，1不折扣，2会员折扣，3整个店铺折扣，4自定义折扣优惠。默认为1',
              `is_no_pay` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否不用支付（0要支付，1不用支付）',
              `is_reserve` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为预定1是0否',
              `score` int(11) NOT NULL DEFAULT '0' COMMENT '会员积分',
              `openid` varchar(35) DEFAULT '' COMMENT '会员openid',
              `vip_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
              `take_num` int(11) NOT NULL DEFAULT '0' COMMENT '取餐号（Android客户端生成）',
              `zhifuhao` int(11) NOT NULL DEFAULT '0' COMMENT '支付号Android传递过来的支付号',
              `cashier_id` int(11) NOT NULL DEFAULT '0' COMMENT '''收银员id',
              `refuse` tinyint(4) NOT NULL DEFAULT '0' COMMENT '（0：正常订单，1：整单退款，2：选择菜品退款）',
              `refuse_reason` varchar(255) DEFAULT NULL COMMENT '订单退菜理由，默认为空，只有整单退菜时填写',
              `saoma_out_trade_no` varchar(100) DEFAULT NULL COMMENT '原生被扫支付，提交给官方的商户订单号',
              `minsheng_trade_no` varchar(100) DEFAULT NULL COMMENT '民生支付回调时的官方订单号(go字段）',
              `related_user` varchar(50) DEFAULT NULL,
              `minsheng_post_no` varchar(50) DEFAULT NULL COMMENT '提交给民生的订单号（由安卓生成），反扫或主扫时提交给服务器处理，进行关联',
              `extra_charge` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '附加费',
              PRIMARY KEY (`order_id`),
              KEY `order_restaurant_$month` (`restaurant_id`),
              CONSTRAINT `order_restaurant_$month` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`restaurant_id`)
                )ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='点餐系统订单表'"
        );
        // order_food表
        $res2 = M()->execute(
            "Create Table If Not Exists restaurant.$orderFoodTable(
              `order_food_id` int(11) NOT NULL AUTO_INCREMENT,
                  `order_id` int(11) NOT NULL COMMENT '订单id',
                  `food_id` int(11) NOT NULL COMMENT '食物id',
                  `tag_print_id` int(10) NOT NULL DEFAULT '0' COMMENT '标签打印机ID',
                  `print_id` int(10) NOT NULL DEFAULT '0' COMMENT '网络打印机ID',
                  `food_num` int(11) NOT NULL DEFAULT '1' COMMENT '菜品份数',
                  `food_price2` decimal(10,2) NOT NULL,
                  `food_name` varchar(100) NOT NULL,
                  `district_id` int(11) NOT NULL DEFAULT '0',
                  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1准备中，2finish，3已核销',
                  `refuse_num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '退菜数量，0默认，即没有退菜，当整单退款时，仍然为0',
                  `refuse_reason` varchar(255) DEFAULT NULL COMMENT '默认为空，菜品退菜时填写',
                  PRIMARY KEY (`order_food_id`),
                  KEY `order_food1` (`order_id`),
                  KEY `order_food2` (`food_id`),
                  KEY `food_id` (`food_id`),
                  CONSTRAINT `order_food1_$month` FOREIGN KEY (`order_id`) REFERENCES `$orderTable` (`order_id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='点餐系统菜品订单表'"
        );
        // order_food_attribute表
        $res3 = M()->execute(
            "Create Table If Not Exists restaurant.$orderFoodAttributeTable(
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `order_food_id` int(11) NOT NULL COMMENT '菜品订单关联id',
              `food_attribute_id` mediumint(10) NOT NULL DEFAULT '0',
              `food_attribute_name` varchar(50) NOT NULL COMMENT '菜品属性名称',
              `food_attribute_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '菜品属性叠加价格',
              `print_id` int(11) NOT NULL DEFAULT '0',
              `tag_print_id` int(10) NOT NULL DEFAULT '0',
              `num` int(11) NOT NULL COMMENT '份数',
              `count_type` smallint(4) NOT NULL DEFAULT '0' COMMENT '类别下的属性是否列入统计（0 : 否，1：是）',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='点餐系统菜品属性订单表'"
        );
    }





    // 删除order表外键
    private function delWaijian(){
        for($i=1;$i<=12;$i++){
            if($i<10){
                $m = '0'.$i;
            }else{
                $m = $i;
            }

            $str = "ALTER TABLE order_2017$m DROP FOREIGN KEY order_restaurant_2017$m";

            $res = $data = M()->execute($str);
            dump($res);
        }
    }

    // 删除order_food表
    private function delOrderFood(){
        for($i=1;$i<=12;$i++){
            if($i<10){
                $m = '0'.$i;
            }else{
                $m = $i;
            }

            $str = "drop table order_food_2017$m";

            $res = $data = M()->execute($str);
            dump($res);
        }
    }

    // 添加order表外键
    private function addForeignKey(){
        for($i=1;$i<=12;$i++){
            if($i<10){
                $m = '0'.$i;
            }else{
                $m = $i;
            }
            $sql = "alter table order_2017$m add constraint order_restaurant_2017$m foreign key(restaurant_id) REFERENCES restaurant(restaurant_id)";
            $res = M()->execute($sql);
        }
    }

    // 添加一列
    private function addCloumn(){
        // 2018年
        /*for($i=1;$i<13;$i++){
            if($i<10){
                $i = '0'.$i;
            }
            $tabName = 'order_2018'.$i;
            $sqlStr = "alter table $tabName add column(
                                  `remainder` decimal(10,2) DEFAULT NULL COMMENT '使用完余额支付后的会员余额',
                                  `summary_score` int(11) DEFAULT NULL COMMENT '使用完会员余额后的会员总分')";
            $res = M()->execute($sqlStr);
        }*/

        // 2017年
        /*for($i=1;$i<13;$i++){
            if($i<10){
                $i = '0'.$i;
            }
            $tabName = 'order_2017'.$i;
            $sqlStr = "alter table $tabName add column(
                                  `remainder` decimal(10,2) DEFAULT NULL COMMENT '使用完余额支付后的会员余额',
                                  `summary_score` int(11) DEFAULT NULL COMMENT '使用完会员余额后的会员总分')";
            $res = M()->execute($sqlStr);
        }*/

        // 2016年
       /* for($i=11;$i<13;$i++){
            $tabName = 'order_2016'.$i;
            $sqlStr = "alter table $tabName add column(
                                  `remainder` decimal(10,2) DEFAULT NULL COMMENT '使用完余额支付后的会员余额',
                                  `summary_score` int(11) DEFAULT NULL COMMENT '使用完会员余额后的会员总分')";
            $res = M()->execute($sqlStr);
        }

        $sqlStr = "alter table `order` add column(
                                  `remainder` decimal(10,2) DEFAULT NULL COMMENT '使用完余额支付后的会员余额',
                                  `summary_score` int(11) DEFAULT NULL COMMENT '使用完会员余额后的会员总分')";
        $res = M()->execute($sqlStr);*/
    }

    // 删除表
    private function drop(){
        for($i=1;$i<13;$i++){
            if($i<10){
                $i = '0'.$i;
            }
            M()->execute("drop table order_food_2017".$i);
            M()->execute("drop table order_food_attribute_2017".$i);
            M()->execute("drop table order_2017".$i);
        }

        // 2016年
        for($i=11;$i<13;$i++){
            M()->execute("drop table order_food_2016".$i);
            M()->execute("drop table order_food_attribute_2016".$i);
            M()->execute("drop table order_2016".$i);
        }
    }

    private function test(){
        $restaurant_ids = M('restaurant')->field('restaurant_id')->select();
        foreach ($restaurant_ids as $key=>$val){
            $reduce = [2,3,5,10];
            foreach($reduce as $value){
                $ext_reduce = array(
                    'restaurant_id' => $val['restaurant_id'],
                    'val' => $value,
                    'discount_or_reduce' => 2,
                );
                $rsl18 = M("order_or_food_discount")->add($ext_reduce);
            }

            // 默认折扣：7  8  8.5  9折
            $discount = [7,8,8.5,9];
            foreach($discount as $v){//构建具体时间数组
                $ext_discount = array(
                    'restaurant_id' => $val['restaurant_id'],
                    'val' => $v,
                    'discount_or_reduce' => 1,
                );
                $rsl19 = M("order_or_food_discount")->add($ext_discount);
            }
        }
    }

    /**
     *  接口同步的推送，推给取餐屏
     * @param $order_sn 订单号
     * @param $type 显示还是不显示
     * @param $qucangui_device_id 取餐柜的设备ID
     */
    public function pushQucanping(){
        /****************************************推送给Android***************************************/
        // 推送的数据
        // 类型为：'display'显示，'timeout'超时
        $push_data['type'] = 'type';
        $push_data['order_sn'] = 'fff';
        $push_data['platform'] = 'take_meal_box';
//        $push_data = 'take_meal_box';
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 对应的app_key
//        $appKey = M('app_key')->where(array('app_type'=>1))->getField('app_key');
        $appKey = '24791139';
        // 对应的device_id
        $devices_ids = array(array('device_id'=>'dc7fa88d8e894bce941aeeed9dea1967'));
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        p($response);
        /****************************************推送给Android***************************************/
    }

    /**
     * 阿里推送公共方法（能够进行推送时间等的控制）
     * @param Array $devices_ids 设备ID数组
     * @param String $php_title 消息标题
     * @param String $php_body  具体内容
     * @param String $push_to_device_type  推到的目的机器设备类型，默认为下单支付后的初次推送
     * @return mixed|\SimpleXMLElement
     */
    public function ali_push_to_android_can_set($devices_ids,$php_title,$php_body,$appKey){
        // 设置你自己的AccessKeyId/AccessSecret/AppKey
        $ali_push_config = M('jubaopen_ali_push_config')->find();
//        $accessKeyId = $ali_push_config['accessKeyId'];
        $accessKeyId = 'LTAIeRhJRf6khv76';
//        $accessKeySecret = $ali_push_config['accessKeySecret'];
        $accessKeySecret = 'C6qy4jKseP6F04ehitK6btEaSz3JQe';
//        $appKey = $ali_push_config['appKey'];

        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessKeySecret);
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new Push\PushRequest();
        // 推送目标
        $request->setAppKey($appKey);
        $request->setTarget("DEVICE"); //推送目标: DEVICE:推送给设备; ACCOUNT:推送给指定帐号,TAG:推送给自定义标签; ALL: 推送给全部

        // 设备ID数组
        $devices_str='';//多台设备用逗号隔开
        foreach($devices_ids as $key=>$val){
            if($key == count($devices_ids)-1){
                $devices_str.=$val['device_id'];
            }else{
                $devices_str.=$val['device_id'].',';
            }
        }
        $request->setTargetValue($devices_str); //根据Target来设定，如Target=DEVICE, 则对应的值为 设备id1,设备id2. 多个值使用逗号分隔.(帐号与设备有一次最多100个的限制)

        $request->setDeviceType("ANDROID"); //设备类型 ANDROID iOS ALL.
        $request->setPushType("MESSAGE"); //消息类型 MESSAGE NOTICE
        $request->setTitle($php_title); // 消息的标题
        $request->setBody($php_body); // 消息的内容
        // 推送控制
        $expireTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+300 second'));//设置失效时间为5分钟
        $request->setExpireTime($expireTime);
        $request->setStoreOffline("true"); // 离线消息是否保存,若保存, 在推送时候，用户即使不在线，下一次上线则会收到
        $response = $client->getAcsResponse($request);
        $arr['MessageId'] = $response->MessageId;
        $arr['RequestId'] = $response->RequestId;
        return $arr;
    }

    public function push_test()
    {
       /* $code_id = M('code')->where(array('code'=>'BB28B42F2E74E7B6'))->getField('code_id');
        p($code_id);*/

        $add['device_code'] = 'a0205d72c4055995';
        $add['device_status'] = 1;
        $add['device_name'] = 'fff';
        $add['code_id'] = 3754;
        $add['start_time'] = 1517969103;
        $add['end_time'] = 1543597200;
//        $res = M('device')->add($add);
        $res = M('code')->where(array('code_id'=>3754))->save(array('code_status'=>0));
        p($res);
    }

    // waypay测试（主扫）
    public function wapay()
    {
        $add['order_sn'] = I('post.order_sn');
        $add['total_amount'] = I('post.total_amount');
        $add['add_time'] = time();
        $orderInfo = M('wapay')->where(array('order_sn'=>I('post.order_sn')))->find();
        if(!empty($orderInfo)){
            $return['code'] = 0;
            $return['url'] = '';
            exit(json_encode($return));
        }
        M('wapay')->add($add);
        $wx_adress_ulr = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/WxChat/wapayQrc/order_sn/".$add['order_sn']."/device_code/".I('post.device_code');
        $return['code'] = 1;
        $return['url'] = $wx_adress_ulr;
        exit(json_encode($return));
    }

    // waypay测试轮询获取订单状态
    public function getOrderStatus(){
        // 服务器订单号
        $order_sn = I("post.order_sn");
        if($order_sn == null){
            $data['code'] = 0;
            $data['msg'] ='订单号为空';
            exit(json_encode($data));
        }

        $orderModel = M('wapay');
        $o_condition['order_sn'] = $order_sn;
        $order_status = $orderModel->where($o_condition)->getField('order_status');

        if($order_status == 1){
            $data['code'] = 1;
            $data['msg'] ='支付成功';
            exit(json_encode($data));
        }else{
            $data['code'] = 0;
            $data['msg'] ='未支付';
            exit(json_encode($data));
        }
    }




    /**
     * 预充值和开卡扫码枪支付
     * @param $order_sn
     * @param $qr_number
     * @param type 是开卡还是预充值,1开卡，2预充值
     */
    public function saoMa(){
        $order_sn = I("post.order_sn");
        $qr_number = I("post.tiao_xing_ma");
        //调用扫码枪支付接口$order_sn;$qr_number;
        // 开卡
        $url = "http://".$_SERVER["HTTP_HOST"]."/index.php/component/Demo/wapayMicroPay";
        $post_data = array ("order_sn" => $order_sn,"qr_number" =>$qr_number );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
//        exit($output);
        //打印获得的数据
        if($output == 0){
            exit(0);
        }
    }

    // wapay处理扫描器扫描得到的数据
    public function wapayMicroPay(){
        $order_sn = $_POST['order_sn'];
        $orderModel = M('wapay');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        // 店铺
        session("restaurant_id",131);

        if($orderInfo['order_status'] == 1){
            echo 2;
            exit;
        }
        $auth_code = $_POST['qr_number'];
        $prefix_num = substr($auth_code,0,2);
        $price = $orderInfo['total_amount']*100;
        if(in_array($prefix_num,C('WX_PAY_PREFIX'))){
            // 读店铺的配置
            $configModel = M('config');
            $condition['config_type'] = "wxpay";
            $condition['restaurant_id'] = 131;
            session('restaurant_id',131);
            $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
            $wxpay_c = dealConfigKeyForValue($wxpay_config);
            $restaurant_name = M("restaurant")->where(array("restaurant_id"=>131))->getField("restaurant_name");
            if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
                require getcwd()."/Application/PayMethod/WxpayMicropay2/lib/WxPay.Data.php";
                if($auth_code){
                    $input = new \WxPayMicroPay();
                    $input->SetAuth_code($auth_code);
                    if(!$restaurant_name){
                        $input->SetBody("方雅餐饮系统");
                    }else{
                        $input->SetBody($restaurant_name);
                    }
                    $input->SetTotal_fee($price);
                    $business_order = \WxPayConfig::$MCHID.'wapay'.date("YmdHis");
                    $orderModel->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));
                    $input->SetOut_trade_no($business_order);
                    $microPay = new MicroPay_1();
                    $result = $microPay->pay($input);
                    if($result == true){
                        //操作数据库处理订单信息；
                        $o_condition['order_sn'] = $order_sn;
                        $data['order_status'] = 1;
                        $time = time();
                        $data['pay_time'] = $time;
                        $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                        // 删除开卡费用支付二维码
                        @unlink('img/wapay/wapay'.$order_sn.'.png');
                        echo 1;
                        exit;
                    }else{
                        echo 2;
                        exit;
                    }
                }
            }else{
                require getcwd()."/Application/PayMethod/WxpayMicropay/lib/WxPay.Data.php";
                $result = false;
                if($auth_code){
                    $input = new \WxPayMicroPay();
                    $input->SetAuth_code($auth_code);
                    if(!$restaurant_name){
                        $input->SetBody("方雅餐饮系统");
                    }else{
                        $input->SetBody($restaurant_name);
                    }
                    $input->SetTotal_fee($price);
                    //　提交的商户订单号跟系统订单号联系起来
                    $business_order = \WxPayConfig::$MCHID.'waypay'.date("YmdHis");
                    $orderModel->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));
                    $input->SetOut_trade_no($business_order);
                    $input->SetSub_mch_id(\WxPayConfig::$SUB_MCHID);
                    $microPay = new MicroPay();
                    $result = $microPay->pay($input);
                }
                if($result == true){
                    //操作数据库处理订单信息；
                    $o_condition['order_sn'] = $order_sn;
                    $data['order_status'] = 1;
                    $time = time();
                    $data['pay_time'] = $time;
                    $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                    // 删除开卡费用支付二维码
                    @unlink('img/wapay/wapay'.$order_sn.'.png');
                    echo 1;
                    exit;
                }else{
                    echo 2;
                    exit;
                }
            }
        }
        if(!in_array($prefix_num,C('WX_PAY_PREFIX'))){
            echo 0;
            exit;
        }
    }

    public function test1()
    {
        $S_TakeMeal = new TakeMeal();
        $res = $S_TakeMeal->push_to_cookRoom('DC0013118020813481500027');
        p($res);
    }



}
