<?php
namespace Mobile\Controller;
use Think\Controller;

class BaseController extends Controller
{   
    public function __construct() {
        parent::__construct();
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        $code = $_GET['code'];
        dump($code);
        if (!isset($_GET['code']))
        {
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME")."/index.php/mobile/Base/index/code/".$code);
            Header("Location: $url");
            exit;
        }

        //=========步骤1：网页授权获取用户openid============
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();

        $configModel = D('config');
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session("restaurant_id");
        $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
        $wxpay_c = dealConfigKeyForValue($wxpay_config);

        $appid = $wxpay_c['wxpay_appid'];
        $appsecret = $wxpay_c['wxpay_appsecret'];


         //获取调用接口凭证
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $access_msg = json_decode(file_get_contents($access_token));
        $token = $access_msg->access_token;
        //获取用户是否订阅了公众号
        $subscribe_msg = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid";
        $subscribe = json_decode(file_get_contents($subscribe_msg));
        $gzxx = $subscribe->subscribe;


        //判断并返回数据
        if($gzxx === 1){
            $returnData['code'] = 1;
            $returnData['msg'] = '已关注';
  
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = '未关注';
            dump($returnData);
            return $returnData;
        }
    }
    public function index(){

    }
   
}