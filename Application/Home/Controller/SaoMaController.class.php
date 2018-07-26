<?php
/**
 * Created by PhpStorm.
 * User: liangbaobin
 * Date: 2016/11/13
 * Time: 23:33
 */

namespace Home\Controller;
use Think\Controller;
use PayMethod\alipaydirect\lib\AlipaySubmit;


class SaoMaController extends Controller
{
    private $alipay_config;

    public function __construct(){
        Controller::__construct();
        $alipay_config = array();
        //$alipay_config['partner'] = '2088521274983214';
        $alipay_config['alipay_public_key'] = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
        $alipay_config['merchant_private_key'] = "MIICXQIBAAKBgQCrPLze9s9rl23JubwCkh0y5TXuttAhHE98y9y/UTWhlnKaQ4x3XB9QO/vP6xZOpHC3P7u3dpSDSgzCtzeZbUONBERAMxumI/cNfw/ylu3NA6jpQk8OJeoEOqEohZku/qq8mReR6fVIAoXPHEFJXlyL41Ny97n1wCLal0fuHWHobwIDAQABAoGARFQFLZcgp1cSeQdDLWdufUuXHL0YCc5JLYwPdswJ8YOeEU5Y85vv5s04qvusuA7H52doGUoY8taOhvgjGHbQGAL1eJsAIxImiLQfqgEeeJmX2n0/gnX9RIA77eKVZVO+JbTCDLTzf4uCVb6TwTauOaVzt3ZGn2ZbP9Vfq6Lc02kCQQDV3LtM8XQ+r+uOwpfvpnUOrK6ryFRSU+7G7RLhA8hIsq9A7wc1T2oEUzpsmERozGc/qeDBru9NlcyThe1kCv97AkEAzPn9rMNMgol8Yqg8mjcRFPFhqneTLGhBWiEs4zF2ju8yvYxtYv5MgRntygwb1SL4OnkJYFeAm7zurs0kmLeOnQJBAJOSsDBlAQjszcgCIWO+YlIQ+KsTHpR81GyyVO+uc3suyd4t0rSHqyl24P7kh3glbC2zJKOh+gF4l+VIako5iJcCQGR+kEuaeLFrPKuV9hhZtStCaPLNqz9TYe8RYtOEla7gQU1DQwIM0W9eSgIMS70EZxUr8FfmrqwsRg03kKC7JdUCQQCNXOkX/UJS0bmIHAmIl17YxgXywxaPEI12bt7QWduKEkUqlDRQgrlPtrwWddO1iZOM/+PjDkvU4cKrIg65mMS1";
        $alipay_config['charset'] = "UTF-8";
        $alipay_config['gatewayUrl'] = 'https://openapi.alipay.com/gateway.do';
        $alipay_config['app_id']	= "2017022305833230";
        $alipay_config['notify_url'] = C('HOST_NAME')."/index.php/Home/SaoMa/notify";
        $alipay_config['MaxQueryRetry'] = '10';
        $alipay_config['QueryDuration'] = '3';
        //$alipay_config['transport'] = 'https';
        $alipay_config['cacert'] = getcwd().'cacert.pem';
        $alipay_config['sign_type'] = 'RSA';

        $this->alipay_config = $alipay_config;
    }

    // 开卡费用支付宝条码支付
    public function card_alipay_barcodePay(){
        Vendor('alipayf2f.f2fpay.service.AlipayTradeService');
        Vendor('alipayf2f.f2fpay.model.builder.AlipayTradePayContentBuilder');		//条码支付请求bizContent结构体
        $order_sn = $_POST['order_sn'];
        $orderModel = M('vipcard_charge');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        if($orderInfo['order_status'] == 1){
            exit;
        }
        /*$restaurant_id = session("restaurant_id");
        if(!$restaurant_id){
            $this::__construct();
        }*/
        $outTradeNo = $_POST['order_sn'];
        if($orderInfo['restaurant_or_business'] == 2){
            // 读店铺的配置
            $restaurant_name = D("restaurant")->where(array("restaurant_id"=>$orderInfo['relation_id']))->getField("restaurant_name");
        }else{
            $restaurant_name = D("business")->where(array("business_id"=>$orderInfo['relation_id']))->getField("business_name");
        }
        $subject = $restaurant_name;
        // (必填) 订单总金额，单位为元，不能超过1亿元
        // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
        $totalAmount = $orderInfo['total_amount'];
        // (必填) 付款条码，用户支付宝钱包手机app点击“付款”产生的付款条码
        $authCode = $_POST['qr_number']; //28开头18位数字
        // (可选) 订单不可打折金额，可以配合商家平台配置折扣活动，如果酒水不参与打折，则将对应金额填写至此字段
        // 如果该值未传入,但传入了【订单总金额】,【打折金额】,则该值默认为【订单总金额】-【打折金额】
        $undiscountableAmount = "0.01";
        // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
        $body = "购买商品2件共15.00元";
        // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
        $providerId = "2088621244519152"; //系统商pid,作为系统商返佣数据提取的依据
        $extendParams = new \ExtendParams();
        $extendParams->setSysServiceProviderId($providerId);
        $extendParamsArr = $extendParams->getExtendParams();
        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";
        // 商品明细列表，需填写购买商品详细信息，
        $goodsDetailList = array();
        //第三方应用授权令牌,商户授权系统商开发模式下使用
        // app_auth_token要区分店铺和代理的
        if($orderInfo['restaurant_or_business'] == 2){
            // 读店铺的配置
            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $oti_data['restaurant_id'] = $orderInfo['restaurant_id'];
        }else{
            $oti_data['business_id'] = $orderInfo['business_id'];
        }
        $aat_rel = M("restaurant_other_info")->where($oti_data)->find();
        $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写
        if(!$appAuthToken){
            exit;
        }
        // 创建请求builder，设置请求参数
        $barPayRequestBuilder = new \AlipayTradePayContentBuilder();
        $barPayRequestBuilder->setOutTradeNo($outTradeNo);
        $barPayRequestBuilder->setTotalAmount($totalAmount);
        $barPayRequestBuilder->setAuthCode($authCode);
        $barPayRequestBuilder->setTimeExpress($timeExpress);
        $barPayRequestBuilder->setSubject($subject);
        $barPayRequestBuilder->setBody($body);
        $barPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
        $barPayRequestBuilder->setExtendParams($extendParamsArr);
        $barPayRequestBuilder->setGoodsDetailList($goodsDetailList);
        $barPayRequestBuilder->setAppAuthToken($appAuthToken);

        $alipay_config = $this->alipay_config;
        $config = array (
            //支付宝公钥
            'alipay_public_key' => $alipay_config['alipay_public_key'],
            //商户私钥
            'merchant_private_key' => $alipay_config['merchant_private_key'],
            //编码格式
            'charset' => $alipay_config['charset'],
            //支付宝网关
            'gatewayUrl' =>  $alipay_config['gatewayUrl'],
            //应用ID
            'app_id' => $alipay_config['app_id'],
            //异步通知地址,只有扫码支付预下单可用
            //'notify_url' => $alipay_config['notify_url'],
            //最大查询重试次数
            'MaxQueryRetry' => $alipay_config['MaxQueryRetry'],
            //查询间隔
            'QueryDuration' => $alipay_config['QueryDuration'],
        );
        // 调用barPay方法获取当面付应答
        $barPay = new \AlipayTradeService($config);			//当面付2.0服务实现，包括条码支付（带轮询）、扫码支付、消费查询、消费退款
        $barPayResult = $barPay->barPay($barPayRequestBuilder);
        switch ($barPayResult->getTradeStatus()){
            case "SUCCESS":
                // print_r($barPayResult->getResponse());
                //操作数据库处理订单信息；
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $data['pay_type'] = 1;			//支付宝
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                // 删除开卡费用支付二维码
                delVipCardQrcode($order_sn,1);
                break;
            case "FAILED":
                if (!empty($barPayResult->getResponse())) {
//	                print_r($barPayResult->getResponse());
                    echo 0;
                    exit;
                }
                break;
            case "UNKNOWN":
                if (!empty($barPayResult->getResponse())) {
//	                print_r($barPayResult->getResponse());
                    echo 0;
                    exit;
                }
                break;
            default:
                echo 0;
                exit;
                break;
        }
        return;
    }

    // 预充值支付宝条码支付
    public function prepaid_alipay_barcodePay(){
        Vendor('alipayf2f.f2fpay.service.AlipayTradeService');
        Vendor('alipayf2f.f2fpay.model.builder.AlipayTradePayContentBuilder');		//条码支付请求bizContent结构体
        $order_sn = $_POST['order_sn'];
        $orderModel = M('prepaid_order');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        if($orderInfo['order_status'] == 1){
            exit;
        }

        $outTradeNo = $_POST['order_sn'];
//        $restaurant_name = D("restaurant")->where(array("restaurant_id"=>$restaurant_id))->getField("restaurant_name");
        if($orderInfo['restaurant_or_business'] == 2){
            // 读店铺的配置
            $restaurant_name = D("restaurant")->where(array("restaurant_id"=>$orderInfo['restaurant_id']))->getField("restaurant_name");
        }else{
            $restaurant_name = D("business")->where(array("business_id"=>$orderInfo['business_id']))->getField("business_name");
        }
        $subject = $restaurant_name;
        // (必填) 订单总金额，单位为元，不能超过1亿元
        // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
        $totalAmount = $orderInfo['total_amount'];
        // (必填) 付款条码，用户支付宝钱包手机app点击“付款”产生的付款条码
        $authCode = $_POST['qr_number']; //28开头18位数字
        // (可选) 订单不可打折金额，可以配合商家平台配置折扣活动，如果酒水不参与打折，则将对应金额填写至此字段
        // 如果该值未传入,但传入了【订单总金额】,【打折金额】,则该值默认为【订单总金额】-【打折金额】
        $undiscountableAmount = "0.01";
        // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
        $body = "购买商品2件共15.00元";
        // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
        $providerId = "2088621244519152"; //系统商pid,作为系统商返佣数据提取的依据
        $extendParams = new \ExtendParams();
        $extendParams->setSysServiceProviderId($providerId);
        $extendParamsArr = $extendParams->getExtendParams();
        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";
        // 商品明细列表，需填写购买商品详细信息，
        $goodsDetailList = array();
        //第三方应用授权令牌,商户授权系统商开发模式下使用
        // app_auth_token要区分店铺和代理的
        if($orderInfo['restaurant_or_business'] == 2){
            // 读店铺的配置
            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $oti_data['restaurant_id'] = $orderInfo['restaurant_id'];
        }else{
            $oti_data['business_id'] = $orderInfo['business_id'];
        }
        $aat_rel = M("restaurant_other_info")->where($oti_data)->find();
        $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写
        if(!$appAuthToken){
            exit;
        }
        // 创建请求builder，设置请求参数
        $barPayRequestBuilder = new \AlipayTradePayContentBuilder();
        $barPayRequestBuilder->setOutTradeNo($outTradeNo);
        $barPayRequestBuilder->setTotalAmount($totalAmount);
        $barPayRequestBuilder->setAuthCode($authCode);
        $barPayRequestBuilder->setTimeExpress($timeExpress);
        $barPayRequestBuilder->setSubject($subject);
        $barPayRequestBuilder->setBody($body);
        $barPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
        $barPayRequestBuilder->setExtendParams($extendParamsArr);
        $barPayRequestBuilder->setGoodsDetailList($goodsDetailList);
        $barPayRequestBuilder->setAppAuthToken($appAuthToken);

        $alipay_config = $this->alipay_config;
        $config = array (
            //支付宝公钥
            'alipay_public_key' => $alipay_config['alipay_public_key'],
            //商户私钥
            'merchant_private_key' => $alipay_config['merchant_private_key'],
            //编码格式
            'charset' => $alipay_config['charset'],
            //支付宝网关
            'gatewayUrl' =>  $alipay_config['gatewayUrl'],
            //应用ID
            'app_id' => $alipay_config['app_id'],
            //异步通知地址,只有扫码支付预下单可用
            //'notify_url' => $alipay_config['notify_url'],
            //最大查询重试次数
            'MaxQueryRetry' => $alipay_config['MaxQueryRetry'],
            //查询间隔
            'QueryDuration' => $alipay_config['QueryDuration'],
        );
        // 调用barPay方法获取当面付应答
        $barPay = new \AlipayTradeService($config);			//当面付2.0服务实现，包括条码支付（带轮询）、扫码支付、消费查询、消费退款
        $barPayResult = $barPay->barPay($barPayRequestBuilder);
        switch ($barPayResult->getTradeStatus()){
            case "SUCCESS":
                // print_r($barPayResult->getResponse());
                //操作数据库处理订单信息；
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 1;
                $data['pay_type'] = 1;			//支付宝
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                break;
            case "FAILED":
                if (!empty($barPayResult->getResponse())) {
//	                print_r($barPayResult->getResponse());
                    echo 0;
                    exit;
                }
                break;
            case "UNKNOWN":
                if (!empty($barPayResult->getResponse())) {
//	                print_r($barPayResult->getResponse());
                    echo 0;
                    exit;
                }
                break;
            default:
                echo 0;
                exit;
                break;
        }
        return;
    }
}