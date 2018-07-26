<?php
namespace Mobile\Controller;
use Think\Controller;
use Think\Verify;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\Wechat\WechatPay;
use data\service\Order as ServiceOrder;

use \Push\Request\V20160801 as Push;
Vendor("ali_push.aliyun-php-sdk-core.Config");
Vendor("ali_push.Push.Request.V20160801.PushRequest");
Vendor("ali_push.Push.Request.V20160801/PushMessageToAndroidRequest");
Vendor("ali_push.Push.Request.V20160801/QueryPushStatByMsgRequest");
use data\service\SellOut as ServiceSellOut;

class WxPayController extends Controller
{
    private $i = 0;
    public function pay(){
        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');

        $order_sn = I('order_sn');
        $orderModel = D('order');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        session('restaurant_id',$orderInfo['restaurant_id']);
        session('desk_code',$orderInfo['desk_code']);
        $this->assign("order_sn",$order_sn);

        $restaurant_id = $orderInfo['restaurant_id'];

        //查询店铺餐桌二维码对应集成打印机的机器的机器码
        $qrc_code_model = D("qrc_code");
        $qrc_condition['restaurant_id'] = $restaurant_id;
        $qrc_code_id = $qrc_code_model->where($qrc_condition)->field("qrc_code_id")->find()['qrc_code_id'];
        $qrc_device_model = D("qrc_device");
        $qrcd_condition['qrc_code_id'] = $qrc_code_id;
        $device_code = $qrc_device_model->where($qrcd_condition)->field('qrc_device_code')->find()['qrc_device_code'];

        $Out_trade_no = $order_sn;
        $restaurant_name = D("restaurant")->where(array("restaurant_id"=>$orderInfo['restaurant_id']))->getField("restaurant_name");
        if($restaurant_name){
            $Body=$restaurant_name;
        }else{
            $Body='方雅智慧点餐';
        }
        $Total_fee = $orderInfo['total_amount']*100;


        //使用jsapi接口
        $jsApi = new \JsApi_pub();

        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
        if (!isset($_GET['code']))
        {
            //触发微信返回code码
            $url = $jsApi->createOauthUrlForCode(\WxPayConf_pub::JS_API_CALL_URL."/order_sn/".$order_sn);
            Header("Location: $url");
        }else
        {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }

        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("sub_openid","$openid");
        $unifiedOrder->setParameter("body",$Body);//商品描述
        //自定义订单号，此处仅作举例
        $unifiedOrder->setParameter("out_trade_no",$Out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee",$Total_fee);//总金额
        if($device_code){
            $unifiedOrder->setParameter("attach",$device_code);//机器码
        }
        $unifiedOrder->setParameter("notify_url","http://shop.founpad.com/index.php/mobile/WxPay/notify");//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        //非必填参数，商户可根据实际情况选填
        $unifiedOrder->setParameter("sub_appid",\WxPayConf_pub::$SUB_APPID);//子商户号
        $unifiedOrder->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//子商户号


        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        $jsApiParameters = $jsApi->getParameters();
        $this->assign("jsApiParameters",$jsApiParameters);
        $this->display();
    }

    /**
     * 支付通知处理
     * @return type
     */
    public function notify(){
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
            file_put_contents(__DIR__."/".'pay_notify.log', var_export($notifyInfo, TRUE));

            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];
            $orderModel = D("order");
            $o_condition['order_sn'] = $order_sn;
            $orderInfo = $orderModel->where($o_condition)->field("order_status,pay_time")->find();
            $order_status = $orderInfo['order_status'];
            $pay_time = $orderInfo['pay_time'];
            if($order_status >= 3 && $pay_time>0){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
                exit;
            }else{
                $data['order_status'] = 3;
                $data['pay_type'] = 2;
                $data['openid'] = $notifyInfo['sub_openid'];
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                if($rel !== false){
                    $this->i++;
                    $returnData['status'] = 2;
                    $returnData['order_sn'] = $order_sn;
                    $device_code = $notifyInfo['attach'];

                    $rel2 = sendInfo($returnData,$device_code);
                    if($rel2['errmsg'] == "Succeed"){
                        $txt = $this->i;
                        $txt.= "|".$order_sn."|".date("Y-m-d H:i:s",time())."\r\n";
                        file_put_contents(__DIR__."log.txt",$txt , FILE_APPEND);
                    }

                    // 售罄处理
                    $S_SellOut = new ServiceSellOut();
                    $S_SellOut->sellOutDeal($order_sn);

                    /****************************************推送给Android***************************************/
                    // 推送的数据
                    $push_data['type'] = 'weixin_place_order';   // 类型为：下单
                    $push_data['order_sn'] = $order_sn;
                    $push_data['platform'] = 'mobile';
                    // 查出当前订单号所属店铺
                    $restaurant_id = D('order')->where(array('order_sn'=>$order_sn))->getField('restaurant_id');

                    $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
                    $php_title = 'founpad_restaurant_push'; // 标题
                    /**
                     * 阿里推送公共方法
                     * @param Array $devices_ids 设备ID数组（二维数组）
                     * @param String $php_title 消息标题
                     * @param String $php_body  具体内容
                     * @return mixed|\SimpleXMLElement
                     */
                    $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data));

                    /****************************************推送给Android***************************************/
                }
            }
            # 所有操作成功，返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
        }
    }

    public function ToXml($returnMsg)
    {
        $xml = "<xml>";
        foreach ($returnMsg as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


/*--------------预充值微信支付开始--------------*/
    public function wxPrepaid()
    {


        // 根据订单查询出vip_id，再查询出代理ID
        $prepaid_order = D("prepaid_order");
        $order_sn = I('order_sn');
        $orderInfo = $prepaid_order->where(array("order_sn"=>$order_sn))->find();
        $vipModel = D("vip");
        $vip_info = $vipModel->where(array("id"=>$orderInfo['vip_id']))->find();
        $business_id = $vip_info['business_id'];

        // 还要传递一个openid过去回调函数处进行处理
        $vip_openid = $vip_info["openid"];  // 写成vip_openid是为了跟下面微信支付接口的openid区分

        // 将代理ID存到session里面，用作查询对接信息的条件
        session('business_id',$business_id);
        // 将区分从cofig表还是wx_prepaid_config表获取对接信息的标识存进session
        session("wx_prepaid_flag",I("get.wx_prepaid_flag"));

        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');

        $Out_trade_no = $order_sn;
//        $Body='方雅智慧点餐';
        $business_name = D("business")->where(array("business_id"=>$business_id))->getField("business_name");
        if($business_name){
            $Body=$business_name;
        }else{
            $Body='方雅智慧点餐';
        }

        $Total_fee = $orderInfo['total_amount']*100;


        //使用jsapi接口
        $jsApi = new \JsApi_pub();

        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
        if (!isset($_GET['code']))
        {
            //触发微信返回code码
            // $url = $jsApi->createOauthUrlForCode(\WxPayConf_pub::JS_API_CALL_URL."/order_sn/".$order_sn);
            $url = $jsApi->createOauthUrlForCode("http://shop.founpad.com/index.php/mobile/WxPay/wxPrepaid/order_sn/".$order_sn);
            Header("Location: $url");
        }else
        {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }

        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("sub_openid","$openid");
        $unifiedOrder->setParameter("body",$Body);//商品描述
        //自定义订单号，此处仅作举例
        $unifiedOrder->setParameter("out_trade_no",$Out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee",$Total_fee);//总金额
        if($vip_openid){
            $unifiedOrder->setParameter("attach",$vip_openid);
        }
        $unifiedOrder->setParameter("notify_url","http://shop.founpad.com/index.php/mobile/Member/prepaid");//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        //非必填参数，商户可根据实际情况选填
        $unifiedOrder->setParameter("sub_appid",\WxPayConf_pub::$SUB_APPID);//子商户号
        $unifiedOrder->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//子商户号


        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        
        $jsApiParameters = $jsApi->getParameters();
        $this->assign("jsApiParameters",$jsApiParameters);
        $this->display("pay");
    }
    /*
    *更新订单餐桌号--
    */
    public function updateDeskCode() {
        $data = I();
        if(empty($data['desk_code'])) $this->ajaxReturn(array('code'=>1, 'msg'=>'桌子号错误，请联系店家'));
        if(intval($data['order_id']) < 1) $this->ajaxReturn(array('code'=>1, 'msg'=>'订单错误，请联系店家'));
        $S_Order = new ServiceOrder();
        $order_info = $S_Order->getWxInfo($data['order_id']);
        if($order_info['order_status'] == 12) $this->ajaxReturn(array('code'=>1, 'msg'=>'该订单已核销过了'));
        $where['order_id'] = $data['order_id'];
        $upd_data['desk_code'] = $data['desk_code'];
        $upd_data['order_status'] = 12;
        $res = $S_Order->updateInfo($where, $upd_data);
        if($res){
            $push_data['type'] = 'weixin_place_order';   // 类型为：下单
            $push_data['order_sn'] = $order_info['order_sn'];
            $push_data['platform'] = 'mobile';
            $devices_ids = M('push_to_device_by_ali')->where("restaurant_id=".session("restaurant_id"))->field('device_id')->select();
            $php_title = 'founpad_restaurant_push'; // 标题
            $response = $this->ali_push_to_android_can_set($devices_ids, $php_title, json_encode($push_data));
            $this->ajaxReturn(array('code'=>0, 'msg'=>'下单成功，等待服务员送餐')); 
        }
        $this->ajaxReturn(array('code'=>1, 'msg'=>'下单失败，请联系店家')); 
    }
/*--------------预充值微信支付结束--------------*/

    /**
     * 阿里推送公共方法（能够进行推送时间等的控制）
     * @param Array $devices_ids 设备ID数组
     * @param String $php_title 消息标题
     * @param String $php_body  具体内容
     * @return mixed|\SimpleXMLElement
     */
    public function ali_push_to_android_can_set($devices_ids,$php_title,$php_body){
        // 设置你自己的AccessKeyId/AccessSecret/AppKey
        $ali_push_config = D('jubaopen_ali_push_config')->find();
        $accessKeyId = $ali_push_config['accessKeyId'];
        $accessKeySecret = $ali_push_config['accessKeySecret'];
        $appKey = $ali_push_config['appKey'];

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
}