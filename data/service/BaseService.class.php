<?php
namespace data\service;

use \Push\Request\V20160801 as Push;
Vendor("ali_push.aliyun-php-sdk-core.Config");
Vendor("ali_push.Push.Request.V20160801.PushRequest");
Vendor("ali_push.Push.Request.V20160801/PushMessageToAndroidRequest");
Vendor("ali_push.Push.Request.V20160801/QueryPushStatByMsgRequest");

/*
*最基类
*/
class BaseService {
    protected $restaurant_id;//餐厅id

    public function __construct() {
        $this->restaurant_id = session("restaurant_id");
    }

    /**
     * 阿里推送公共方法（能够进行推送时间等的控制）
     * @param Array $devices_ids 设备ID数组
     * @param String $php_title 消息标题
     * @param String $php_body  具体内容
     * @param String $push_to_device_type  推到的目的机器设备类型，默认为下单支付后的初次推送
     * @param String $type  1取餐柜应用推送，2点餐应用的推送
     * @return mixed|\SimpleXMLElement
     */
    public function ali_push_to_android_can_set($devices_ids,$php_title,$php_body,$appKey,$type = 1){
        // 设置你自己的AccessKeyId/AccessSecret/AppKey
        $ali_push_config = M('take_meal_ali_push_config')->where(array('type'=>1))->find();
        $accessKeyId = $ali_push_config['accessKeyId'];
        $accessKeySecret = $ali_push_config['accessKeySecret'];
//        $appKey = $ali_push_config['appKey'];
        if($type == 2){
            // 2点餐应用的推送
            $ali_push_config = D('jubaopen_ali_push_config')->find();
            $accessKeyId = $ali_push_config['accessKeyId'];
            $accessKeySecret = $ali_push_config['accessKeySecret'];
        }

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
