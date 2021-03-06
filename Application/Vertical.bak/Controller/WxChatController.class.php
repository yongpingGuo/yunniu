<?php
/**
 * Created by PhpStorm.
 * User: liangbaobin
 * Date: 2016/11/13
 * Time: 18:33
 */
namespace Vertical\Controller;
use Think\Controller;
use Think\Verify;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\Wechat\WechatPay;

class WxChatController extends Controller
{
    public function index(){
        $this->display();
    }

    /**
     * 支付通知处理
     * @return type
     */
    public function notify() {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            //返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);

            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。

            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];

            if(session($order_sn)){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            }else{
                session("num".$order_sn,1);
                $orderModel = D("order");
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 3;
                $data['pay_type'] = 2;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                if($rel !== false){
                    //获取订单信息，判断是否要推送到展示餐牌号展示页面
                    $orderInfo = $orderModel->where($o_condition)->field("table_num,desk_code,restaurant_id")->find();
                    $restaurantModel = D("Restaurant");
                    $rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
                    $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
                    if($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0){
                        $content['tips'] = "下单成功推送showNum";
                        $contentJson = json_encode($content);
                        $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                        $rel2 = sendMsgToDevice($post_data);
                        $restaurant_id = $orderInfo['restaurant_id'];
                        pushAllDistrict($restaurant_id,$order_sn);
                    }
                }
                session($order_sn,true);
            }
        }
    }

    /**
     * 生成预支付码
     * @return type
     */
    public function qrc() {
        $outer_no = I('get.order_sn');
//        $outer_no = "DC0000116112907505000004";

        $orderModel = D("order");
        $o_condition['order_sn'] = $outer_no;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status,discount,vip_id,restaurant_id")->find();
        session("restaurant_id",$rel['restaurant_id']);

        vendor("weixinjsdk.WxPayPubHelper.WxPayPubHelper");

       /* $orderModel = D("order");
        $o_condition['order_sn'] = $outer_no;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status,discount,vip_id,restaurant_id")->find();*/
        $vip_id = $rel['vip_id'];   // 拿到会员id

        // 避免两台机器同时下单
        $order_num = D("order")->where(array("order_sn"=>$outer_no))->count();
        if($order_num>1){
            echo '此笔订单重复，请重新点餐';
            exit;
        }

        if($rel['order_status'] == 3){
            exit;
        }
        $price = $rel['total_amount']*100;

        $restaurant_name = D("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");

//        $body = "方雅餐饮系统";
        $body = $restaurant_name;
        if (empty($outer_no) || empty($price)) {
            return ['code' => 'ERROR', 'info' => '参数错误!'];
        }

        $configModel = D('config');
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session("restaurant_id") ? : $rel['restaurant_id'];
        $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
        $wxpay_c = dealConfigKeyForValue($wxpay_config);

        if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
            $pay = & load_wechat('Pay');
            $device_code = cookie('device_code') ? :I("get.device_code");
            $code_url = $pay->getPrepayId("",$body, $outer_no, $price, U('WxChat/notify', null, null, TRUE), 'NATIVE',$device_code);

            if ($code_url === FALSE) {
                var_dump(['code' => 'ERROR', 'info' => '创建预支付码失败，' . $pay->errMsg]);
                exit;
            }
        }else{
            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();

//            $unifiedOrder->setParameter("body","方雅点餐系统");//商品描述
            $unifiedOrder->setParameter("body",$restaurant_name);//商品描述
            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $out_trade_no = $outer_no;
            $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee",$price);//总金额

            // 二维码有效期
            $begin_time = date("YmdHis",time());
            $end_time = date("YmdHis",time()+135);

            $unifiedOrder->setParameter("time_start",$begin_time);
            $unifiedOrder->setParameter("time_expire",$end_time);

            if($vip_id){
                $unifiedOrder->setParameter("attach",$vip_id);//会员id
            }

            $unifiedOrder->setParameter("notify_url",\WxPayConf_pub::NOTIFY_URL);//通知地址
            $unifiedOrder->setParameter("trade_type","NATIVE");
            //非必填参数，商户可根据实际情况选填
            $unifiedOrder->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//注：是主户代理申请的 这里的子商户的商户号

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            }
            elseif($unifiedOrderResult["result_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            }
            elseif($unifiedOrderResult["code_url"] != NULL)
            {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
        }
//        dump($code_url);
//        exit;
        error_reporting(E_ERROR);
        vendor("phpqrcode.phpqrcode");
        $url = urldecode($code_url);
        $errorCorrectionLevel = 'M';//容错级别
        $matrixPointSize = 6;//生成图片大小
//        $wx_img = 'http://'.$_SERVER["HTTP_HOST"].'/img/third/wx'.$outer_no.'.png';
        $wx_img = 'img/third/wx'.$outer_no.'.png';
//        p($wx_img);
//        \QRcode::png($url,'qrcode.png',$errorCorrectionLevel, $matrixPointSize,2);
        \QRcode::png($url,$wx_img,$errorCorrectionLevel, $matrixPointSize,2);
        //QRcode::png($url);

        $logo = 'wechat.png';//准备好的logo图片
//        $QR = 'qrcode.png';//已经生成的原始二维码图
        $QR = $wx_img;//已经生成的原始二维码图

        if ($logo !== FALSE){
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        ob_clean();
        Header("Content-type: image/png");
        ImagePng($QR);
        imagedestroy($QR);
    }

    // 生成店铺手机后台的测试码
    public function pay_test_qrc() {
        $outer_no = I('get.order_sn');
        $orderModel = D("pay_test_demo");
        $o_condition['order_sn'] = $outer_no;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status,restaurant_id")->find();
        session("restaurant_id",$rel['restaurant_id']);
        vendor("weixinjsdk.WxPayPubHelper.WxPayPubHelper");

        // 避免两台机器同时下单
        $order_num = $orderModel->where($o_condition)->count();
        if($order_num>1){
            echo '此笔订单重复，请重新点餐';
            exit;
        }

        if($rel['order_status'] == 3){
            echo '此笔订单已支付';
            exit;
        }
        $price = $rel['total_amount']*100;
        $restaurant_name = D("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
        $body = $restaurant_name;
        if (empty($outer_no) || empty($price)) {
            return ['code' => 'ERROR', 'info' => '参数错误!'];
        }

        $configModel = D('config');
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = $rel['restaurant_id'];
        $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
        $wxpay_c = dealConfigKeyForValue($wxpay_config);

        if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
            $pay = & load_wechat('Pay');
            $device_code = cookie('device_code') ? :I("get.device_code");
            $code_url = $pay->getPrepayId("",$body, $outer_no, $price, U('WxChat/notify', null, null, TRUE), 'NATIVE',$device_code);

            if ($code_url === FALSE) {
                var_dump(['code' => 'ERROR', 'info' => '创建预支付码失败，' . $pay->errMsg]);
                exit;
            }
        }else{
            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body",$restaurant_name);//商品描述
            //自定义订单号，此处仅作举例
            $out_trade_no = $outer_no;
            $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee",$price);//总金额

            // 二维码有效期
            $begin_time = date("YmdHis",time());
            $end_time = date("YmdHis",time()+135);

            $unifiedOrder->setParameter("time_start",$begin_time);
            $unifiedOrder->setParameter("time_expire",$end_time);
            $notify_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/Home/WxChat/Wx_notify_url";
            $unifiedOrder->setParameter("notify_url",$notify_url);//通知地址

            $unifiedOrder->setParameter("trade_type","NATIVE");
            //非必填参数，商户可根据实际情况选填
            $unifiedOrder->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//注：是主户代理申请的 这里的子商户的商户号

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            }
            elseif($unifiedOrderResult["result_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            }
            elseif($unifiedOrderResult["code_url"] != NULL)
            {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
        }
//        dump($code_url);
//        exit;
        error_reporting(E_ERROR);
        vendor("phpqrcode.phpqrcode");
        $url = urldecode($code_url);
        $errorCorrectionLevel = 'M';//容错级别
        $matrixPointSize = 6;//生成图片大小
        \QRcode::png($url,'qrcode.png',$errorCorrectionLevel, $matrixPointSize,2);
        //QRcode::png($url);

        $logo = 'wechat.png';//准备好的logo图片
        $QR = 'qrcode.png';//已经生成的原始二维码图

        if ($logo !== FALSE){
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        ob_clean();
        Header("Content-type: image/png");
        ImagePng($QR);
        imagedestroy($QR);
    }


    public function microPay(){
        $order_sn = $_POST['order_sn'];
        $orderModel = D('order');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        session('restaurant_id',$orderInfo['restaurant_id']);
        require getcwd()."/Application/PayMethod/WxpayMicropay/lib/WxPay.Data.php";
//        $auth_code = "130176713531424298";
        $auth_code = $_POST['qr_number'];
        $device_code = $_POST['device_code'];
        $result = false;
        if($auth_code){
            $input = new \WxPayMicroPay();
            $input->SetAuth_code($auth_code);
            $input->SetBody("方雅餐饮系统");
//            $input->SetTotal_fee("1");
            $input->SetTotal_fee($orderInfo['total_amount']*100);
            $input->SetOut_trade_no(\WxPayConfig::$MCHID.date("YmdHis"));

            $microPay = new MicroPay();
            $result = $microPay->pay($input);
//            dump($result);
        }

        if($result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
            //如果支付成功则使用极光推送推送到设备打印订单小票

            //操作数据库处理订单信息；
            $orderModel = D("order");
            $o_condition['order_sn'] = $order_sn;
            $data['order_status'] = 3;
            $data['pay_type'] = 2;
            $time = time();
            $data['pay_time'] = $time;
            $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

            if($rel !==false){

                //获取订单信息，判断是否要推送到展示餐牌号展示页面
                $orderInfo = $orderModel->where($o_condition)->field("table_num,desk_code,restaurant_id")->find();
                $restaurantModel = D("Restaurant");
                $rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
                $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
                if($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0){
                    $content['tips'] = "下单成功推送showNum";
                    $contentJson = json_encode($content);
                    $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                    $rel2 = sendMsgToDevice($post_data);
                }

                $msg["code"] = 1;
                $msg['msg'] = "支付成功";
                exit(json_encode($msg));
            }

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
}