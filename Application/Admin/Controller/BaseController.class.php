<?php
namespace Admin\Controller;
use Think\Controller;
use \Push\Request\V20160801 as Push;
Vendor("ali_push.aliyun-php-sdk-core.Config");
Vendor("ali_push.Push.Request.V20160801.PushRequest");
Vendor("ali_push.Push.Request.V20160801/PushMessageToAndroidRequest");
Vendor("ali_push.Push.Request.V20160801/QueryPushStatByMsgRequest");

use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;
Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.OAuth.OAuthClient');

class BaseController extends Controller
{
    public function __construct() {
        parent::__construct();
//        $is_appoint_shop = C("BUSINESS_ID") == session('business_id')?true:false;//指定商家是否支持菜品英文等功能判断
        $is_appoint_shop = C("BUSINESS_ID") == session('business_id') || session('business_id') == 25?true:false;//指定商家是否支持菜品英文等功能判断
        $this->assign('is_en', $is_appoint_shop);
        if(session("restaurant_id")) $this->restaurant_id = session("restaurant_id");
    }
    public function _empty(){
        redirect("/index.php/Admin/Index/login");
    }
    /**
     * 查询消息的发送状态
     * @param String  $msg_id  发送消息接口返回的msg_id
     */
    public function query_push_status($msg_id){
        $ali_push_config = D('jubaopen_ali_push_config')->find();
        $accessKeyId = $ali_push_config['accessKeyId'];
        $accessKeySecret = $ali_push_config['accessKeySecret'];
        $appKey = $ali_push_config['appKey'];

        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessKeySecret);
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new Push\QueryPushStatByMsgRequest();

        $request->setAppKey($appKey);
        $request->setMessageId($msg_id);

        $response = $client->getAcsResponse($request);

        $encode = json_encode($response);
        $decode = json_decode($encode,true);
        $temp = $decode['PushStats']['PushStat'][0];
        if($temp){
            $SentCount = $temp['SentCount'];    // 发送数
            $ReceivedCount = $temp['ReceivedCount'];    // 到达数
            $OpenedCount = $temp['OpenedCount'];    // 打开数
            $DeletedCount = $temp['DeletedCount'];  // 删除数
            $MessageId = $temp['MessageId'];

            if($SentCount == $ReceivedCount){
                $return['code'] = 1;
                $return['msg_id'] = $MessageId;
                return $return;
            }else{
                $return['code'] = 0;
                $return['msg_id'] = $MessageId;
                return $return;
            }
        }else{
            $return['code'] = 2;
            $return['msg_id'] = "原因不详";
            return $return;
        }
    }

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

    /**
     * 饿了么校验签名
     * @param $message ，饿了么推送过来的信息
     * @param $secret ，饿了么应用秘钥，eleme_config表的app_secret字段
     * @return bool
     */
    function eleme_check_signature($message, $secret)
    {
        $params = $message;
        $signature = $message["signature"];
        unset($params["signature"]);

        ksort($params);
        $string = "";
        foreach ($params as $key => $value) {
            $string .= $key . "=" . $value;
        }
        $splice = $string . $secret;
        $md5 = strtoupper(md5($splice));

        if ($signature != $md5) {
            return false;
        }
        return true;
    }
}