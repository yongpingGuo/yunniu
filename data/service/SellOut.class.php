<?php
namespace data\service;
use \Push\Request\V20160801 as Push;
Vendor("ali_push.aliyun-php-sdk-core.Config");
Vendor("ali_push.Push.Request.V20160801.PushRequest");
Vendor("ali_push.Push.Request.V20160801/PushMessageToAndroidRequest");
Vendor("ali_push.Push.Request.V20160801/QueryPushStatByMsgRequest");


/*
*订单支付回调售罄服务层类
*/
class SellOut{
    /*
    * 支付回调关于菜品售罄的处理
    */
    public function sellOutDeal($order_sn) {
        $orderFoodMedel = 'order_food_'.date("Ym");
        $orderMedel = 'order_'.date("Ym");
        // 售罄处理
        // 当前订单对应的菜品id和菜品数量
        $sql = "SELECT food_id,food_num FROM $orderFoodMedel WHERE order_id = ( SELECT order_id FROM `$orderMedel` WHERE `order_sn` = '$order_sn')";
        $food_info = M()->query($sql);
        $push_food_ids = array();
        foreach($food_info as $val){
            $food_id = $val['food_id'];
            $food_num = $val['food_num'];
            // 最后更新时间
            $update_time = M('food')->where(array('food_id'=>$food_id))->getField('update_time');
            $Date = date('Y-m-d',time());
            $startTime = '00:00:00';
            $endTime = '23:59:59';
            $startTimeStr = strtotime($Date." ".$startTime);
            $endTimeStr = strtotime($Date." ".$endTime);
            if(!($startTimeStr<$update_time && $update_time<$endTimeStr)){
                // 最后更新的时间不在今天内，则清空sale_num字段
                M('food')->where(array('food_id'=>$food_id))->save(array('sale_num'=>0,'update_time'=>time(),'is_shutdown'=>0));
            }

            $foods = M('food')->where(array('food_id'=>$food_id))->field('update_time,foods_num_day,sale_num')->find();
            // sale_num字段叠加，更新时间
            $save = M('food')->where(array('food_id'=>$food_id))->save(array('sale_num'=>$foods['sale_num']+$food_num,'update_time'=>time()));
            // 比较每天供应量和已售卖的数量
            $have_save = $foods['sale_num']+$food_num;
            if($foods['foods_num_day'] <= $have_save){
                // 已售罄，将当前菜品id存进数组里
                $push_food_ids[] = $food_id;
                // 将此菜品标识为售罄
                M('food')->where(array('food_id'=>$food_id))->save(array('is_shutdown'=>1));
            }
        }
        if($push_food_ids){
            // 该订单有已售罄的菜品，则推送给安卓
            /****************************************推送给Android***************************************/
            // 推送的数据
            $push_data['type'] = 'sellOut';   // 类型为：售罄
            $push_data['food_id'] = $push_food_ids;
            $push_data['platform'] = 'payNotify';
            // 查出当前订单号所属店铺
            $restaurant_id = order()->where(array('order_sn'=>$order_sn))->getField('restaurant_id');

            $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
            $php_title = 'founpad_restaurant_push'; // 标题
            /**
             * 阿里推送公共方法
             * @param Array $devices_ids 设备ID数组（二维数组）
             * @param String $php_title 消息标题
             * @param String $php_body  具体内容
             * @return mixed|\SimpleXMLElement
             */
            $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$restaurant_id);
            /****************************************推送给Android***************************************/
        }
    }

    // 当更新菜品的每日供应量时，变售罄为上线，或者变上线为售罄，根据$type来进行判断
    public function whenUpdateFood($food_id,$restaurant_id,$type)
    {
        // 推送的数据
        $push_data['type'] = $type;   // 类型为：上架onSale,或者售罄sellOut
        $push_data['food_id'] = array($food_id);    // 具体某个菜品的id，写成数组是为了与sellOutDeal方法的结构一致
        $push_data['platform'] = 'payNotify';

        $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$restaurant_id);
    }

    /**
     * 阿里推送公共方法（能够进行推送时间等的控制）
     * @param Array $devices_ids 设备ID数组
     * @param String $php_title 消息标题
     * @param String $php_body  具体内容
     * @return mixed|\SimpleXMLElement
     */
    public function ali_push_to_android_can_set($devices_ids,$php_title,$php_body,$restaurant_id){
        // 设置你自己的AccessKeyId/AccessSecret/AppKey
        $ali_push_config = D('jubaopen_ali_push_config')->find();
        $accessKeyId = $ali_push_config['accessKeyId'];
        $accessKeySecret = $ali_push_config['accessKeySecret'];
        $appKey = $ali_push_config['appKey'];
        // 区分粤新应用
        $yuexinRestaurant = array(689);
        if(in_array($restaurant_id,$yuexinRestaurant)){
            $appKey = '24708211';
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
