<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wxd38774797fcc83b7';//服务商的
	//受理商ID，身份标识
	const MCHID = '1444065502';//服务商的
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = '1234567890123456789012345founpad';//服务商的

	static $SUB_APPID = '';//子商户的
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	static $SUB_APPSECRET = '';//子商户的

	static $SUB_MCHID = '';//子商户的

	//=======【JSAPI路径设置】===================================
	//获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
	const JS_API_CALL_URL = 'http://yunniutest.cloudabull.com.com/index.php/mobile/WxPay/pay';
//	const JS_API_CALL_URL = "http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/WxPay/pay";

	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径
	const SSLCERT_PATH =__DIR__."/".'cacert/apiclient_cert.pem';
	const SSLKEY_PATH = __DIR__."/".'cacert/apiclient_key.pem';
	
	//=======【异步通知url设置】===================================
	//异步通知url，商户根据实际开发过程设定
	const NOTIFY_URL = 'http://yunniutest.cloudabull.com/index.php/home/wxChat/notify';
//	const NOTIFY_URL = "http://".$_SERVER["HTTP_HOST"]."/index.php/home/wxChat/notify";

	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
}
/*$configModel = D('config');
$condition['config_type'] = "wxpay";
$condition['restaurant_id'] = session("restaurant_id");
$wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
$wxpay_c = dealConfigKeyForValue($wxpay_config);
WxPayConf_pub::$SUB_APPID = $wxpay_c['wxpay_appid'];
WxPayConf_pub::$SUB_APPSECRET = $wxpay_c['wxpay_appsecret'];
WxPayConf_pub::$SUB_MCHID = $wxpay_c['wxpay_child_mchid'];*/

// 用以区分是从config表获取对接数据还是从wx_prepaid_config表获取
if(session("wx_prepaid_flag")){//充值
    // 从wx_prepaid_config表
    $configModel = D('wx_prepaid_config');
    $condition['business_id'] = session("business_id");
    $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
    $wxpay_c = dealConfigKeyForValue($wxpay_config);
}else{
    // 从config表
    $configModel = D('config');
    $condition['config_type'] = "wxpay";
    $condition['restaurant_id'] = session("restaurant_id");
    $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
    $wxpay_c = dealConfigKeyForValue($wxpay_config);
}

WxPayConf_pub::$SUB_APPID = $wxpay_c['wxpay_appid'];
WxPayConf_pub::$SUB_APPSECRET = $wxpay_c['wxpay_appsecret'];
WxPayConf_pub::$SUB_MCHID = $wxpay_c['wxpay_child_mchid'];