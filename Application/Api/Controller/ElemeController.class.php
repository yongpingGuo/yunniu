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
    public function eleme_confirm_order()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if ($token_info['again_grant'] == 2) {
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
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么取消订单
     */
    public function eleme_cancel_order()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if ($token_info['again_grant'] == 2) {
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
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么回复催单
     */
    public function eleme_reply_reminder()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if ($token_info['again_grant'] == 2) {
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
        } else {
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
    public function eleme_get_data_by_orderId()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderId = I("post.orderId");
            $condition['orderId'] = $orderId;
            $condition['type'] = 10;
            $data = D('eleme_order')->where($condition)->find();
//            $data['createdAt'] = str_replace("T"," ",$data['createdAt']);
            $data['createdAt'] = date('Y-m-d H:i:s', $data['createdAt']);
//            $data['activeAt'] = str_replace("T"," ",$data['activeAt']);
            $data['activeAt'] = date('Y-m-d H:i:s', $data['activeAt']);

            if ($data) {
                // 获取底部广告语
                $bill_foot_language = D('restaurant')->where(array('restaurant_id' => session('restaurant_id')))->getField('eleme_bill_foot_language');
                $data['eleme_bill_foot_language'] = $bill_foot_language;

                /*   $returnData['code'] = 1;
                   $returnData['msg'] = "获取订单数据成功";
                   $returnData['data'] = json_encode($data);*/

                // 方便安卓获取数据，全部字段都直接传输
                /*foreach($data as $key=>$val){
                    if($key == 'groups' || $key == 'phoneList' || $key == 'orderActivities'){
                        $data[$key] = json_decode($data[$key], true, 512, JSON_BIGINT_AS_STRING);
                    }elseif($key == 'deliverTime' && $val == null){
                        $data[$key] = 0;
                    }
                }*/
                foreach ($data as $key => $val) {
                    if ($key == 'groups' || $key == 'phoneList' || $key == 'orderActivities') {
                        $data[$key] = json_decode($data[$key], true, 512, JSON_BIGINT_AS_STRING);
                        if ($key == 'orderActivities') {
                            foreach ($data[$key] as $e => $v) {
                                $data[$key][$e]['id'] = 0;
                                $data[$key][$e]['restaurantPart'] = 0;
                                $data[$key][$e]['elemePart'] = 0;
                            }
                        }
                    } elseif ($key == 'deliverTime' && $val == null) {
                        $data[$key] = 0;
                    }
                }

                $data['code'] = 1;
                $data['msg'] = "获取订单数据成功";
                exit(json_encode($data));
            } else {
                $returnData['code'] = 0;
                $returnData['msg'] = "该订单号没有对应的数据";
                $returnData['data'] = "";
                exit(json_encode($returnData));
            }
        } else {
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
    public function eleme_agree_refund()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if ($token_info['again_grant'] == 2) {
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
        } else {
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
    public function eleme_disagree_refund()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];

            // 获取token信息
            $restaurant_id = session('restaurant_id');
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据

            if ($token_info['again_grant'] == 2) {
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
            if ($order_id == null) {
                $returnData['code'] = 0;
                $returnData['msg'] = "订单号不能为空";
                exit(json_encode($returnData));
            }
            if ($reason == null) {
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
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 饿了么一键同步数据
     */
    public function oneKeySync()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $eleme_config = D('eleme_config')->find();
            $app_key = $eleme_config['app_key'];
            $app_secret = $eleme_config['app_secret'];
            // 获取token信息
            $restaurant_id = session('restaurant_id');
//            $restaurant_id = 427;
            $token_info = get_or_refresh_token($restaurant_id); // 返回的是eleme_token表中当前店铺对应的数据
            if ($token_info['again_grant'] == 2) {
                // refresh_token过期，需要重新授权
                $returnData['code'] = 0;
                $returnData['msg'] = "refresh_token过期，需要重新授权";
                exit(json_encode($returnData));
            }
            $token = $token_info['access_token'];
            //实例化一个配置类
            $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
            $order_service = new orderService($token, $config);
            $shop_id = M('eleme_token')->where(array('restaurant_id'=>session('restaurant_id')))->getField('shopId');
//            $shop_id = 157054401;
            $page_no = 1;
            $page_size = 50;
//            $date = "2018-01-19";
            $date = date('Y-m-d');
            $result = $order_service->get_all_orders($shop_id, $page_no, $page_size, $date);
            $afterJson = json_decode(json_encode($result), true);
            $list = $afterJson['list'];
            /*“未生效订单”：用户下单未支付的订单；
            “未处理订单”：用户下单成功后商家未接单；
            “退单处理中”：退单处理中的订单；
            “已处理的有效订单”：已接单的有效订单；
            “无效订单”：无效订单
                └ pending
                String
                未生效订单
                └ unprocessed
                String
                未处理订单
                └ refunding
                String
                退单处理中
                └ valid
                String
                已处理的有效订单
                └ invalid
                String
                无效订单
                └ settled
                String
                已完成订单
            */

            // 请求除第一页外的数据(除第一页外还有多少页)
            $lessPage = $afterJson['total'] / $page_size - 1 > 0 ? ceil($afterJson['total'] / $page_size) : 0;  // 因为下面的循环是从第二页算起，所以不用减一了
            for ($i = 2; $i <= $lessPage; $i++) {
                $result = $order_service->get_all_orders($shop_id, $i, $page_size, $date);
                $afterJson = json_decode(json_encode($result), true);
                $listOthers = $afterJson['list'];
                $list = array_merge_recursive($list, $listOthers);
            }
            $lastInfo = $this->getElemeInfo($list);
            $returnData['code'] = 1;
            $returnData['data'] = $lastInfo;
            $returnData['msg'] = "同意取消订单成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 获取饿了么的相关订单信息
    public function getElemeInfo($data)
    {
        if ($data) {
            // 获取底部广告语
            $bill_foot_language = D('restaurant')->where(array('restaurant_id' => session('restaurant_id')))->getField('eleme_bill_foot_language');
            foreach ($data as $key => $val) {
                $data[$key]['orderId'] = $val['id'];
                unset($data[$key]['id']);
                $data[$key]['eleme_bill_foot_language'] = $bill_foot_language;
                $data[$key]['createdAt'] = str_replace("T"," ",$val['createdAt']);
                $data[$key]['activeAt'] = str_replace("T"," ",$val['activeAt']);
                if ($key == 'orderActivities') {
                    foreach ($data[$key] as $e => $v) {
                        $data[$key][$e]['id'] = 0;
                        $data[$key][$e]['restaurantPart'] = 0;
                        $data[$key][$e]['elemePart'] = 0;
                    }
                }elseif ($key == 'deliverTime' && $val == null) {
                    $data[$key] = 0;
                }
            }

            $notDealOrder = []; // 新订单
            $refundDeal = []; // 退单处理中的订单
            $haveDeal = []; // 已接单的有效订单
            $haveFinish = []; // 已完成订单
            $successRefund = []; // 成功退单
            foreach ($data as $key => $val) {
                if ($val['status'] == 'unprocessed') {
                    $notDealOrder[] = json_encode($val);
                } elseif ($val['status'] == 'refunding') {
                    $refundDeal[] = json_encode($val);
                } elseif ($val['status'] == 'valid') {
                    $haveDeal[] = json_encode($val);
                } elseif ($val['status'] == 'settled') {
                    $haveFinish[] = json_encode($val);
                } elseif ($val['refundStatus'] == 'successful') {
                    $successRefund[] = json_encode($val);
                }
            }
            $orderList = array(
                'notDealOrder' => $notDealOrder,
                'refundDeal' => $refundDeal,
                'haveDeal' => $haveDeal,
                'haveFinish' => $haveFinish,
                'successRefund' => $successRefund,
            );
            return $orderList;
        }else{
            return $orderList = array(
                'notDealOrder' => [],
                'refundDeal' => [],
                'haveDeal' => [],
                'haveFinish' => [],
                'successRefund' => [],
            );
        }

        /****************饿了么结束********************/
    }
}