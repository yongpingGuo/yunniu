<?php
namespace Api\Controller;
Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.OAuth.OAuthClient');
use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;

use ElemeOpenApi\Api\UserService;
Vendor('ElemeOpenApi.Api.UserService');

use ElemeOpenApi\Api\OrderService;
Vendor('ElemeOpenApi.Api.OrderService');
class ElemeController extends BaseController
{
    /****************饿了么开始********************/
    /**
     * 饿了么确认订单
     */
    public function eleme_confirm_order(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if($token_info['again_grant'] == 2){
                // refresh_token过期，需要重新授权
                $returnData['code'] = 0;
                $returnData['msg'] = "refresh_token过期，需要重新授权";
                exit(json_encode($returnData));
            }

            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $order_service = new orderService($token, $config);
            $order_id = I('post.orderId');
            $return = $order_service->confirm_order_lite($order_id);
//            dump($return);
            /*if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "确认订单成功";
                exit(json_encode($returnData));
            }*/
            // 根据返回值来判断确认成功还是失败，再返回给安卓
            $returnData['code'] = 1;
            $returnData['msg'] = "确认订单成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么取消订单
     */
    public function eleme_cancel_order(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if($token_info['again_grant'] == 2){
                // refresh_token过期，需要重新授权
                $returnData['code'] = 0;
                $returnData['msg'] = "refresh_token过期，需要重新授权";
                exit(json_encode($returnData));
            }

            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $order_service = new orderService($token, $config);
            $order_id = I('post.orderId');
//            $type = "others";
            $type = I('post.type');
//            $remark = "无法取得联系";
            $remark = "post.remark";
            $return = $order_service->cancel_order_lite($order_id, $type, $remark);
//            dump($return);
            /*if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "取消订单成功";
                exit(json_encode($returnData));
            }*/
            // 根据返回值来判断确认成功还是失败，再返回给安卓
            $returnData['code'] = 1;
            $returnData['msg'] = "取消订单成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么回复催单
     */
    public function eleme_reply_reminder(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if($token_info['again_grant'] == 2){
                // refresh_token过期，需要重新授权
                $returnData['code'] = 0;
                $returnData['msg'] = "refresh_token过期，需要重新授权";
                exit(json_encode($returnData));
            }

            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $order_service = new orderService($token, $config);
            $remind_id = I('post.remindId');
//            $type = "custom";
            $type = I('post.type');
//            $content = "已售完";
            $content = I('post.content');
            $return = $order_service->reply_reminder($remind_id, $type, $content);

            // 根据返回值来判断确认成功还是失败，再返回给安卓
            $returnData['code'] = 1;
            $returnData['msg'] = "回复催单成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  客户端用orderId来后台请求完整数据
     *  device_code  设备码
     *  orderId   美团分配的orderId
     */
    public function eleme_get_data_by_orderId(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderId = I("post.orderId");
            $condition['orderId'] = $orderId;
            $condition['type'] = 10;
            $data = D('eleme_order')->where($condition)->find();
//            $data['createdAt'] = str_replace("T"," ",$data['createdAt']);
            $data['createdAt'] = date('Y-m-d H:i:s',$data['createdAt']);
//            $data['activeAt'] = str_replace("T"," ",$data['activeAt']);
            $data['activeAt'] = date('Y-m-d H:i:s',$data['activeAt']);

            if($data){
                // 获取底部广告语
                $bill_foot_language = D('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('eleme_bill_foot_language');
                $data['eleme_bill_foot_language'] = $bill_foot_language;

             /*   $returnData['code'] = 1;
                $returnData['msg'] = "获取订单数据成功";
                $returnData['data'] = json_encode($data);*/

                // 方便安卓获取数据，全部字段都直接传输
                foreach($data as $key=>$val){
                    if($key == 'groups' || $key == 'phoneList' || $key == 'orderActivities'){
                        $data[$key] = json_decode($data[$key], true, 512, JSON_BIGINT_AS_STRING);
                    }elseif($key == 'deliverTime' && $val == null){
                        $data[$key] = 0;
                    }
                }

                $data['code'] = 1;
                $data['msg'] = "获取订单数据成功";
                exit(json_encode($data));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "该订单号没有对应的数据";
                $returnData['data'] = "";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么同意取消单/退单
     * orderId  饿了么订单id
     */
    public function eleme_agree_refund(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if($token_info['again_grant'] == 2){
                // refresh_token过期，需要重新授权
                $returnData['code'] = 0;
                $returnData['msg'] = "refresh_token过期，需要重新授权";
                exit(json_encode($returnData));
            }

            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $order_service = new orderService($token, $config);
            $order_id = I('post.orderId');
            $return = $order_service->agree_refund_lite($order_id);
//            dump($return);
            /*if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "确认订单成功";
                exit(json_encode($returnData));
            }*/


            // 根据返回值来判断确认成功还是失败，再返回给安卓
            $returnData['code'] = 1;
            $returnData['msg'] = "同意取消订单成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么不同意取消单/退单
     * orderId  饿了么订单id
     * reason  不同意的原因
     */
    public function eleme_disagree_refund(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if($token_info['again_grant'] == 2){
                // refresh_token过期，需要重新授权
                $returnData['code'] = 0;
                $returnData['msg'] = "refresh_token过期，需要重新授权";
                exit(json_encode($returnData));
            }

            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $order_service = new orderService($token, $config);
            $order_id = I('post.orderId');
//            $reason = "商品已经卖完";
            $reason = I('post.reason');
            if($order_id == null){
                $returnData['code'] = 0;
                $returnData['msg'] = "订单号不能为空";
                exit(json_encode($returnData));
            }
            if($reason == null){
                $returnData['code'] = 0;
                $returnData['msg'] = "原因不能为空";
                exit(json_encode($returnData));
            }
            $order_service->disagree_refund_lite($order_id, $reason);

//            dump($return);
            /*if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "确认订单成功";
                exit(json_encode($returnData));
            }*/


            // 根据返回值来判断确认成功还是失败，再返回给安卓
            $returnData['code'] = 1;
            $returnData['msg'] = "同意取消订单成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /****************饿了么结束********************/
}