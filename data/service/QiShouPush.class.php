<?php
namespace data\service;
/**
 *集合各种情况下的推送,包括取餐柜模式的阿里推送，以及核销屏模式下的阿里推送
 * 但不包括美团，菜品售罄的推送,不包括钉钉取餐柜的推送(钉钉取餐柜推送已有:takeMeal.class.php)
 */

class QiShouPush extends BaseService
{


    /**
     *推送给骑手柜
     * @param:$order_sn 订单号
     */
    public function pushOneQiShouCupboard($order_sn)
    {

        //查询订单详细信息
        $orderInfo = M('qs_order')->where(array('order_sn'=>$order_sn))->field('restaurant_id,order_status,order_id')->find();
        $restaurant_id = $orderInfo['restaurant_id'];
        $order_status = $orderInfo['order_status'];
        $order_id = $orderInfo['order_id'];

        // 还没推送过
        if($order_status > 1){
//            $res = M('qs_order')->where(array('order_sn'=>$order_sn))->save(array('push_status'=>1));   // 记录下单后的第一次推送
            /****************************************推送给Android***************************************/
            $push_data['window_and_canaellnum'] = M('qs_order_detail')->where(array('order_id'=>$order_id))->field('window_num,cancell_num')->select();
            // 推送的数据
            $push_data['order_sn'] = $order_sn;       // 订单号
            $push_data['type'] = 1;       // 1首次推送，2取餐后的推送
            $push_data['pay_status'] = 'success';
            $php_title = 'founpad_restaurant_push'; // 标题
            // 推给放餐屏
            $appKey = M('app_key')->where(array('app_type'=>6))->getField('app_key');   //type为1的app_key，即放餐柜
            $where = array(
                'restaurant_id'=>$restaurant_id,
                'type'=>6   // 1、放餐屏，2、取餐屏，3、准备中/请取餐,6骑手柜
            );
            // 对应的device_id
            $devices_ids = M('dc_take_meal_device')->where($where)->field('device_id')->select();
            $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
            file_put_contents("./"."qishou_push.txt", '店铺id'.$restaurant_id.'order_sn'.$order_sn."设备id:".json_encode($devices_ids)."推送给骑手柜:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);

        }
    }


    /**
     *  放餐屏同步接口后的推送，推给取餐屏(取餐柜模式的第二步推送)
     * @param $type 'put_meal'放餐屏放餐，'timeout'超时
     * @param $qucangui_device_id 取餐柜的设备ID
     * @param $push_data 推送的数据
     */
    public function pushTwoCupboard($type,$qucangui_device_id,$push_data)
    {
        $push_data['type'] = $type;        // 类型为：'put_meal'放餐屏放餐，'timeout'超时
        $push_data['platform'] = 'take_meal_box';
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
        $appKey = M('app_key')->where(array('app_type'=>2))->getField('app_key');
        // 对应的device_id
        $devices_ids = array(array('device_id'=>$qucangui_device_id));
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给取餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }


}