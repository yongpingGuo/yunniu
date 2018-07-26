<?php
namespace Home\Controller;
use Think\Controller;
use Think\Verify;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\WxpayMicropay2\MicroPay_1;
use PayMethod\Wechat\WechatPay;
use Think\Encrypt;

class VipCardNotifyController extends Controller
{
    public function index(){
        $this->display();
    }

    /**
     * 会员卡微信支付通知处理
     * @return type
     */
    public function wxNotify() {
        file_put_contents(__DIR__."/"."fff.txt","订单号："."888. |||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            //返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
//            file_put_contents(LOG_PATH . 'pay_notify.log', var_export($notifyInfo, TRUE));
            //操作数据库处理订单信息；
            $orderModel = M('vipcard_charge');
            $order_sn = $notifyInfo['out_trade_no'];
            file_put_contents(__DIR__."/"."fff.txt","订单号：".$order_sn."|||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
            $order_status = $orderModel->where(array('order_sn'=>$order_sn))->getField('order_status');
            if($order_status == 1){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            }else{
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $data['pay_type'] = 2;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                // 删除开卡费用支付二维码
                delVipCardQrcode($order_sn,1);
            }
        }
    }

    /**
     * wapay微信支付通知处理
     * @return type
     */
    public function waypayWxNotify() {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            //返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
//            file_put_contents(LOG_PATH . 'pay_notify.log', var_export($notifyInfo, TRUE));
            //操作数据库处理订单信息；
            $orderModel = M('wapay');
            $order_sn = $notifyInfo['out_trade_no'];
            $order_status = $orderModel->where(array('order_sn'=>$order_sn))->getField('order_status');
            if($order_status == 1){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            }else{
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                // 删除开卡费用支付二维码
                @unlink('img/wapay/wapay'.$order_sn.'.png');
            }
        }
    }

    /**
     * 会员卡支付宝支付通知处理
     * @return type
     */
    public function aliNotify() {
        $out_trade_no = $_POST['out_trade_no'];
        file_put_contents(__DIR__."/"."fff11.txt","订单号：".$out_trade_no."|||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
        $orderModel = M('vipcard_charge');

        $alipay_config = $this->config();
        $alipay_config['notify_url'] = C('HOST_NAME').'/index.php/home/VipCardNotify/aliNotify';

        import('Vendor.alipayf2f.lib.alipay_notify');
//        $alipay_config = $this->alipay_config;
        $alipay_config['sign_type'] = $_POST['sign_type'];
        $alipay_config['notify_id'] = $_POST['notify_id'];
        $alipayNotify = new \AlipayNotify($alipay_config);

        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result) {//验证成功
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //操作数据库处理订单信息；
                $order_sn = $out_trade_no;
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $data['pay_type'] = 1;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                // 删除开卡费用支付二维码
                delVipCardQrcode($order_sn,1);
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";		//请不要修改或删除
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "fail";
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    // 阿里支付配置值
    public function config()
    {
        $alipay_config = array();
        //$alipay_config['partner'] = '2088521274983214';
        $alipay_config['alipay_public_key'] = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
        $alipay_config['merchant_private_key'] = "MIICXQIBAAKBgQCrPLze9s9rl23JubwCkh0y5TXuttAhHE98y9y/UTWhlnKaQ4x3XB9QO/vP6xZOpHC3P7u3dpSDSgzCtzeZbUONBERAMxumI/cNfw/ylu3NA6jpQk8OJeoEOqEohZku/qq8mReR6fVIAoXPHEFJXlyL41Ny97n1wCLal0fuHWHobwIDAQABAoGARFQFLZcgp1cSeQdDLWdufUuXHL0YCc5JLYwPdswJ8YOeEU5Y85vv5s04qvusuA7H52doGUoY8taOhvgjGHbQGAL1eJsAIxImiLQfqgEeeJmX2n0/gnX9RIA77eKVZVO+JbTCDLTzf4uCVb6TwTauOaVzt3ZGn2ZbP9Vfq6Lc02kCQQDV3LtM8XQ+r+uOwpfvpnUOrK6ryFRSU+7G7RLhA8hIsq9A7wc1T2oEUzpsmERozGc/qeDBru9NlcyThe1kCv97AkEAzPn9rMNMgol8Yqg8mjcRFPFhqneTLGhBWiEs4zF2ju8yvYxtYv5MgRntygwb1SL4OnkJYFeAm7zurs0kmLeOnQJBAJOSsDBlAQjszcgCIWO+YlIQ+KsTHpR81GyyVO+uc3suyd4t0rSHqyl24P7kh3glbC2zJKOh+gF4l+VIako5iJcCQGR+kEuaeLFrPKuV9hhZtStCaPLNqz9TYe8RYtOEla7gQU1DQwIM0W9eSgIMS70EZxUr8FfmrqwsRg03kKC7JdUCQQCNXOkX/UJS0bmIHAmIl17YxgXywxaPEI12bt7QWduKEkUqlDRQgrlPtrwWddO1iZOM/+PjDkvU4cKrIg65mMS1";
        $alipay_config['charset'] = "UTF-8";
        $alipay_config['gatewayUrl'] = 'https://openapi.alipay.com/gateway.do';
        $alipay_config['app_id']	= "2017022305833230";
        $alipay_config['notify_url'] = C('HOST_NAME').'/index.php/home/VipCardNotify/aliNotify';
        $alipay_config['MaxQueryRetry'] = '10';
        $alipay_config['QueryDuration'] = '3';
        //$alipay_config['transport'] = 'https';
        $alipay_config['cacert'] = getcwd().'cacert.pem';
        $alipay_config['sign_type'] = 'RSA';
        return $alipay_config;
    }

    /**
     * 会员预充值支付宝支付通知处理
     */
    public function prepaidAliNotify()
    {
        $out_trade_no = $_POST['out_trade_no'];
        $orderModel = M('prepaid_order');

        $alipay_config = $this->config();
        $alipay_config['notify_url'] = C('HOST_NAME').'/index.php/home/VipCardNotify/prepaidAliNotify';

        import('Vendor.alipayf2f.lib.alipay_notify');
//        $alipay_config = $this->alipay_config;
        $alipay_config['sign_type'] = $_POST['sign_type'];
        $alipay_config['notify_id'] = $_POST['notify_id'];
        $alipayNotify = new \AlipayNotify($alipay_config);

        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result) {//验证成功
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //操作数据库处理订单信息；
                $order_sn = $out_trade_no;
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $data['pay_type'] = 1;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                // 删除开卡费用支付二维码
                delPrepaidQrcode($order_sn,1);

                // 在prepaid_order表更新各种优惠
                $return = $this->update_benefit_in_order($order_sn);
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";		//请不要修改或删除
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "fail";
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    /**
     * 会员预充值微信支付通知处理
     */
    public function prepaidWxNotify()
    {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            //返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
//            file_put_contents(LOG_PATH . 'pay_notify.log', var_export($notifyInfo, TRUE));
            //操作数据库处理订单信息；
            $orderModel = M('prepaid_order');
            $order_sn = $notifyInfo['out_trade_no'];
            $order_status = $orderModel->where(array('order_sn'=>$order_sn))->getField('order_status');
            if($order_status == 1){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            }else{
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $data['pay_type'] = 2;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                // 删除开卡费用支付二维码
                delPrepaidQrcode($order_sn,1);

                // 在prepaid_order表更新各种优惠
                $return = $this->update_benefit_in_order($order_sn);
            }
        }
    }

    /**
     * 更新获得优惠后的对应的优惠详情
     * @param $order_sn
     * @param $relation_id
     * @param $account
     * @param $benefit
     * @param $finall_benefit
     * @return bool
     */
    public function update_benefit_in_order($order_sn){
        $vipInfo = M('prepaid_order')->where(array('order_sn'=>$order_sn))->field('vip_id,finall_benefit')->find();
        $vip_id = $vipInfo['vip_id'];
        // 查询出该会员当前在数据库有多少余额
        $vip = M("vip");
        $remainder = $vip->where(array("id"=>$vip_id))->getField("remainder");
        $all_money = $vipInfo['finall_benefit'] + $remainder;
        $total['remainder'] = $all_money;
        $res = $vip->where(array("id"=>$vip_id))->save($total);

        $where['order_sn'] = $order_sn;
        $data['finall_remainder'] = $all_money;  // 客户最后的余额
        $rel = M('prepaid_order')->where($where)->save($data);
        if($rel !== false){
            return true;
        }else{
            return false;
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
        $xml.= "</xml>";
        return $xml;
    }
}