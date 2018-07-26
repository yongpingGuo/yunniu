<?php
namespace Admin\Controller;

use ElemeOpenApi\Config\Config;
use ElemeOpenApi\Api\ShopService;
Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.Api.ShopService');
class ElemepushController extends BaseController{
    /**
     *  接收饿了么推送的订单
     */
    public function push_eleme_order(){
        header('Content-Type:application/json; charset=utf-8');

        if(IS_GET){
            file_put_contents(__DIR__."/"."Elemepush_check_heart.txt",'心跳检测'."\r\n\r\n",FILE_APPEND);
            echo '{"message":"ok"}';exit;
        }

        $content = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);

        if(array_key_exists('type',$content)){
            file_put_contents(__DIR__."/"."Elemepush_order.txt",'订单信息:'.json_encode($content)."\r\n\r\n",FILE_APPEND);

            # 签名校验
            $secret = D('eleme_config')->getField('app_secret');
            $receiver = $this->eleme_check_signature($content,$secret);
            if(!$receiver){
                echo '{"message":"error"}';exit;
            }

            # 消息类型
            $type = $content['type'];
            switch ($type) {
                case 10:    // 订单生效（接收订单）
                    $return = $this->receive_order($content);
                    break;
                case 12:    // 商户接单（确认订单）
                    $content['style'] = 'confirm';
                    $return = $this->receive_orderStatus_change($content);
                    break;
                case 14:    // 订单被取消，情景：1商户在还没接单前取消
                    $content['style'] = 'cancel';
                    $return = $this->receive_orderStatus_change($content);
                    break;
                case 15:    // 订单被置为无效（客户下单后取消）
                    $content['style'] = 'cancel';
                    $return = $this->receive_orderStatus_change($content);
                    break;
                case 17:    // 订单强制无效（情景：1、商家确认订单后，再取消订单）
                    $content['style'] = 'cancel';
                    $return = $this->receive_orderStatus_change($content);
                    break;
                case 18:    // 订单完结
                    $content['style'] = 'finish';
                    $return = $this->receive_orderStatus_change($content);
                    break;
                case 20:    // 用户申请取消单
                    $content['style'] = 'user_apply_cancel';
                    $return = $this->refund_order($content);
                    break;
                case 21:    // 用户撤回取消单申请
                    $content['style'] = 'user_revoke_cancel';
                    $return = $this->refund_order($content);
                    break;
                case 22:    // 商户拒绝取消单
                    $content['style'] = 'restaurant_disagree_cancel';
                    $return = $this->refund_order($content);
                    break;
                case 23:    // 商户同意取消单
                    $content['style'] = 'restaurant_agree_cancel';
                    $return = $this->refund_order($content);
                    break;
                case 24:    // 用户申请仲裁取消单
                    $content['style'] = 'user_apply_arbitration_cancel';
                    $return = $this->refund_order($content);
                    break;
                case 25:    // 客服仲裁取消单申请有效
                    $content['style'] = 'eleme_arbitration_cancel_valid';
                    $return = $this->refund_order($content);
                    break;
                case 26:    // 客服仲裁取消单申请无效
                    $content['style'] = 'eleme_arbitration_cancel_invalid';
                    $return = $this->refund_order($content);
                    break;
                case 30:    // 用户申请退单
                    $content['style'] = 'user_apply_tuidan';
                    $return = $this->refund_order($content);
                    break;
                case 31:    // 用户取消退单
                    $content['style'] = 'user_revoke_tuidan';
                    $return = $this->refund_order($content);
                    break;
                case 32:    // 商户拒绝退单
                    $content['style'] = 'restaurant_disagree_tuidan';
                    $return = $this->refund_order($content);
                    break;
                case 33:    // 商户同意退单
                    $content['style'] = 'restaurant_agree_tuidan';
                    $return = $this->refund_order($content);
                    break;
                case 34:    // 用户申请仲裁
                    $content['style'] = 'user_apply_arbitration_tuidan';
                    $return = $this->refund_order($content);
                    break;
                case 35:    // 客服仲裁退单有效
                    $content['style'] = 'eleme_arbitration_tuidan_valid';
                    $return = $this->refund_order($content);
                    break;
                case 36:    // 客服仲裁退单无效
                    $content['style'] = 'eleme_arbitration_tuidan_invalid';
                    $return = $this->refund_order($content);
                    break;
                case 45:    // 用户催单
                    $content['style'] = 'reminder';
                    $return = $this->receive_reminder($content);
                    break;
                case 46:    // 商家回复用户催单
                    $content['style'] = 'restaurant_return_reminder';
                    $return = $this->receive_reminder($content);
                    break;
                case 91:    // 店铺营业状态变化
                    $return = $this->receive_restaurant_change($content);
                    break;
                case 92:    // 店铺状态变更消息
                    $return = $this->receive_restaurant_change($content);
                    break;
            }

            // 运单状态变化消息
            if($type>=51 && $type<=76){
                $content['style'] = 'delivery_status';
                $return = $this->delivery_status_change($content);
            }

            if(!$return){
                echo '{"message":"error"}';exit;
            }

            echo '{"message":"ok"}';
        }
    }

    /**
     *  接收饿了么推送过来的订单信息
     * @param $content
     */
    public function receive_order($content)
    {
        $dat['order_info_json']=json_encode($content);
        $eleme_push_order=D('eleme_push_order');
        /*防止重复推送*/
        $eleme_push_orders=$eleme_push_order->where($dat)->find();
        if($eleme_push_orders)
        {
            return false;
        }
        $res1 = $eleme_push_order->data($dat)->add();
        
        // 接收饿了么推送过来的订单数据（type=10的,订单生效）
        $message = json_decode($content['message'], true, 512, JSON_BIGINT_AS_STRING);
        foreach($message as $key=>$val){
            // 此userId为下单用户的id，非商户id。。（但是两者推送过来的字段名一样）
            if($key == 'userId'){
                $content['placeorder_userId'] = $val;
            }elseif($key == 'groups' || $key == 'phoneList' || $key == 'orderActivities'){
                $content[$key] = json_encode($val);
            }elseif($key == 'activeAt' || $key == 'createdAt'){
                $content[$key] = strtotime($val);
            }else{
                $content[$key] = $val;
            }
        }
        unset($content['message']);
        // 根据shopId查出restaurant_id
        $restaurant_id = D('eleme_token')->where(array('shopId'=>$content['shopId']))->getField('restaurant_id');
        $content['restaurant_id'] = $restaurant_id;

        $condition['orderId'] = $content['orderId'];
        $if_same = D('eleme_order')->where($condition)->getField('primary_id');
        if(!$if_same){
            $res2 = D('eleme_order')->data($content)->add();
        }else{
            $res2 = true;
        }

        // 推送给安卓
        if($res2){
            // 组装安卓所需数据
            $push_data['type'] = 'place_order';   // 类型为：下单
            $push_data['orderId'] = $content['orderId'];
            $push_data['platform'] = 'eleme';  // 区分美团和饿了么
            /***********推送开始*****************/
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

            /*file_put_contents(__DIR__."/"."Elemepush_Alipush_Android_status.txt",'流水：'.$content['daySn'].'，订单ID：'. $content['orderId'].
                '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/
            /***********推送结束*****************/
            return true;
        }else{
            return false;
        }
    }

    /**
     * 接收订单状态变更消息。包括：已确认订单、订单被取消、订单置为无效、订单强制无效、订单完结
     * @param $content
     */
    public function receive_orderStatus_change($content){
        // 接收饿了么推送过来的已确认订单数据（type=12的,订单已确认）
        $message = json_decode($content['message'],true, 512, JSON_BIGINT_AS_STRING);
        foreach($message as $key=>$val){
            $content[$key] = $val;
        }
        unset($content['message']);
        // 直接关联点餐系统店铺id
        $restaurant_id = D('eleme_token')->where(array('shopId'=>$content['shopId']))->getField('restaurant_id');
        $content['restaurant_id'] = $restaurant_id;

        $condition['orderId'] = $content['orderId'];
        $condition['type'] = $content['type'];
        $if_same = D('eleme_orderstatus_change')->where($condition)->getField('id');
        if(!$if_same){
            $res2 = D('eleme_orderstatus_change')->data($content)->add();
            // 各种回调状态都在eleme_order表进行记录，便于统计
            $res = D('eleme_order')->where(array('orderId'=>$content['orderId']))->save(array('final_type'=>$content['type']));
        }else{
            $res2 = true;
        }

        // 推送给安卓
        if($res2){
            // 组装安卓所需数据
            $push_data['type'] = $content['style'];   // 推给安卓的类型
            $push_data['orderId'] = $content['orderId'];
            $push_data['platform'] = 'eleme';  // 区分美团和饿了么
            /***********推送开始*****************/
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

           /* file_put_contents(__DIR__."/"."Elemepush_Alipush_orderStatus_change.txt",'类型为：'.$content['type'].'，订单ID：'. $content['orderId'].
                '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/
            /***********推送结束*****************/
            return true;
        }else{
            return false;
        }
    }

    /** 接收饿了么推送过来的催单信息
     * @param $content
     */
    public function receive_reminder($content){
        // 接收饿了么推送过来的催单数据
        $message = json_decode($content['message'],true, 512, JSON_BIGINT_AS_STRING);
        foreach($message as $key=>$val){
            $content[$key] = $val;
        }
        unset($content['message']);

        $restaurant_id = D('eleme_token')->where(array('shopId'=>$content['shopId']))->getField('restaurant_id');
        $content['restaurant_id'] = $restaurant_id;

        $res2 = D('eleme_reminder')->data($content)->add();
        // 推送给安卓
        if($res2){
            // 组装安卓所需数据
            $push_data['type'] = $content['style'];   // 推给安卓的类型
            $push_data['orderId'] = $content['orderId'];
            $push_data['remindId'] = $content['remindId'];
            $push_data['platform'] = 'eleme';  // 区分美团和饿了么
            /***********推送开始*****************/
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

           /* file_put_contents(__DIR__."/"."Elemepush_Alipush_Android_reminder.txt",'类型为：'.$content['type'].'，订单ID：'. $content['orderId'].
                '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/
            /***********推送结束*****************/
            return true;
        }else{
            return false;
        }
    }

    /**
     * 关于退单
     * @param $content
     */
    public function refund_order($content){
        $message = json_decode($content['message'],true, 512, JSON_BIGINT_AS_STRING);
        foreach($message as $key=>$val){
            $content[$key] = $val;
        }
        unset($content['message']);

        $restaurant_id = D('eleme_token')->where(array('shopId'=>$content['shopId']))->getField('restaurant_id');
        $content['restaurant_id'] = $restaurant_id;

        $condition['orderId'] = $content['orderId'];
        $condition['type'] = $content['type'];
        $if_same = D('eleme_refund')->where($condition)->getField('id');
        if(!$if_same){
            $res2 = D('eleme_refund')->data($content)->add();
            // 30用户申请退单,31用户取消退单，32商户拒绝退单，33商家同意退单，34用户申请仲裁，35客服仲裁退单有效，36客服仲裁退单无效
            $type_num = array(30,31,32,33,34,35,36);
            if (in_array($content['type'], $type_num))
            {
                if($type_num == 33 || $type_num == 35){
                    // 退单成功的也得记录到eleme_order订单表的final_type字段
                    $res3 = D('eleme_order')->where(array('orderId'=>$content['orderId']))->save(array('final_type'=>$content['type']));
                }
                // 记录订单完成后退单模块的状态
                $res1 = D('eleme_order')->where(array('orderId'=>$content['orderId']))->save(array('refund_type'=>$content['type']));
            }else{
                $res = D('eleme_order')->where(array('orderId'=>$content['orderId']))->save(array('final_type'=>$content['type']));
            }
        }else{
            $res2 = true;
        }

        // 推送给安卓
        if($res2){
            // 组装安卓所需数据
            $push_data['type'] = $content['style'];   // 推给安卓的类型
            $push_data['orderId'] = $content['orderId'];
            $push_data['refundStatus'] = $content['refundStatus'];
            $push_data['reason'] = $content['reason'];
            $push_data['platform'] = 'eleme';  // 区分美团和饿了么
            /***********推送开始*****************/
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

           /* file_put_contents(__DIR__."/"."Elemepush_Alipush_Android_refund.txt",'类型为：'.$content['type'].'，订单ID：'. $content['orderId'].
                '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/
            /***********推送结束*****************/
            return true;
        }else{
            return false;
        }
    }

    /**
     * 关于配送状态
     * @param $content
     */
    public function delivery_status_change($content){
        $message = json_decode($content['message'],true, 512, JSON_BIGINT_AS_STRING);
        foreach($message as $key=>$val){
            $content[$key] = $val;
        }
        unset($content['message']);
        // 代号转成具体文字说明
        $state = $content['state'];
        $content['state'] = D('eleme_delivery_status_exchange')->where(array('origin_state'=>$state))->getField('exchange_to_chinese');

        $subState = $content['subState'];
        $content['subState'] = D('eleme_delivery_status_exchange')->where(array('origin_state'=>$subState))->getField('exchange_to_chinese');

        $restaurant_id = D('eleme_token')->where(array('shopId'=>$content['shopId']))->getField('restaurant_id');
        $content['restaurant_id'] = $restaurant_id;

        $res2 = D('eleme_delivery_status')->data($content)->add();

        // 推送给安卓
        if($res2){
            // 组装安卓所需数据
            $push_data['type'] = $content['style'];   // 推给安卓的类型
            $push_data['orderId'] = $content['orderId'];
            $push_data['state'] = $content['state'];    // 运单主状态
            $push_data['subState'] = $content['subState'];  // 运单子状态
            $push_data['name'] = $content['name'];  // 配送员姓名
            $push_data['phone'] = $content['phone'];    // 配送员联系方式
            $push_data['updateAt'] = $content['updateAt'];  // 状态变更的时间戳，单位秒
            $push_data['platform'] = 'eleme';  // 区分美团和饿了么
            /***********推送开始*****************/
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

          /*  file_put_contents(__DIR__."/"."Elemepush_Alipush_Android_refund.txt",'类型为：'.$content['type'].'，订单ID：'. $content['orderId'].
                '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/
            /***********推送结束*****************/
            return true;
        }else{
            return false;
        }
    }

    /**
     *  接收店铺状态变更
     * @param $content
     */
    public function receive_restaurant_change($content){
        // 接收饿了么推送过来的催单数据
        $message = json_decode($content['message'],true, 512, JSON_BIGINT_AS_STRING);
        $payload = $message['payload'];
        $newstatus = $payload['newStatus']['busy_level'];
        // 0营业中，2已关店
        if($newstatus == 2){
            // 调用接口置营业
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];
            // 获取token信息
            $restaurant_id = D('eleme_token')->where(array('shopId'=>$content['shopId']))->getField('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if($token_info['again_grant'] == 2){
                // 需要重新授权
                return true;
            }

            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $shop_service = new shopService($token, $config);
            $shop_id = $content['shopId'];
            $properties = array();
            $properties["isOpen"] = 1;
            $result = $shop_service->update_shop($shop_id, $properties);
        }
        return true;
    }

    /**
     *  订单推送测试
     */
    public function push_test(){
        // 组装安卓所需数据
        $push_data['type'] = 'place_order';   // 类型为：下单
        $push_data['orderId'] = $_GET['orderId'];
        $push_data['platform'] = 'eleme';
        /***********推送开始*****************/
        // 传给安卓的数据存进日志
//            file_put_contents(__DIR__."/"."Alipush_data_jubaopen.txt",json_encode($push_data)."\r\n\r\n",FILE_APPEND);

//        $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$data['ePoiId']))->field('device_id')->select();
//        $devices_ids = array(array('device_id'=>'82aa24b856054e14944fa5de28055bfd'));
        $devices_ids = array(array('device_id'=>'755432a2c5f440bc97c230113dc29a79'));

        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data));
    }
}
