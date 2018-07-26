<?php
namespace Api\Controller;
class PushController extends BaseController
{
    /**
     *  获取安卓收银设备的device_id
     *  device_code  设备码
     *  device_id   机器对应的device_id
     */
    public function DeviceId_relation_aliPush(){
        $device_code = I("post.device_code");   // 机器码
        $device_id = I("post.device_id");   // 阿里推送所需的device_id
        if($device_code == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "device_code为空";
            exit(json_encode($returnData));
        }
        if($device_id == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "device_id为空";
            exit(json_encode($returnData));
        }

        $this->isLogin($device_code);
        if ($this->is_security) {
            $restaurant_id = session("restaurant_id");

            /*file_put_contents(__DIR__."/"."Push_bangding_device_id.txt",
                    '，店铺id：'.$restaurant_id.
                    '，device_code:'.$device_code.
                    '，device_id:'.$device_id.
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/


            /***删除掉那些曾今被激活过的但是没有清除掉的其他店铺的记录（预防有些记录没有被清除掉）***/
            if($restaurant_id != null && $device_id != null){
                $where['device_id'] = $device_id;
                $where['restaurant_id'] = array("neq",$restaurant_id);
                // 判断是否已经存在，不存在才添加
                $del = D("push_to_device_by_ali")->where($where)->delete();

                /*file_put_contents(__DIR__."/"."Push_bangding_device_id.txt",
                    '，店铺id(delete)：'.$restaurant_id.
                    '，device_code(delete):'.$device_code.
                    '，device_id(delete):'.$device_id.
                    '，del(delete):'.$del.
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/
            }
            /***删除掉那些曾今被激活过的但是没有清除掉的其他店铺的记录（预防有些记录没有被清除掉）***/

            // 判断当前店铺对应的记录是否已经存在，不存在才添加
            $add['device_id'] = $device_id;
            $add['restaurant_id'] = $restaurant_id;
            $if = D("push_to_device_by_ali")->where($add)->find();
            if(!$if){
                $res = D("push_to_device_by_ali")->add($add);
                if($res){
                    $returnData['code'] = 1;
                    $returnData['msg'] = "绑定成功";
                    exit(json_encode($returnData));
                }else{
                    $returnData['code'] = 0;
                    $returnData['msg'] = "绑定失败";
                    exit(json_encode($returnData));
                }
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "数据库中店铺已有此记录，无需再添加";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 清除阿里推送所需的device_id的激活记录
     *  device_code  设备码
     *  device_id   机器对应的device_id
     */
    public function clear_ali_device_id(){
        $device_id = I("post.device_id");   // 阿里推送所需的device_id
        if($device_id == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "device_id为空";
            exit(json_encode($returnData));
        }

        $de_where['device_id'] = $device_id;
        $del = D("push_to_device_by_ali")->where($de_where)->delete();

        if($del === false){
            $returnData['code'] = 0;
            $returnData['msg'] = "删除不成功，请重试";
            exit(json_encode($returnData));
        }

        $returnData['code'] = 1;
        $returnData['msg'] = "删除device_id成功";
        exit(json_encode($returnData));
    }



    /****************聚宝盆开始********************/
    /**
     *  聚宝盆确认接单
     *  device_code  设备码
     *  orderId   聚宝盆分配的orderId
     */
    public function jubaopen_order_confirm(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            //商家确认订单
            $url = "http://api.open.cater.meituan.com/waimai/order/confirm";
            $orderId = I("post.orderId");
            $now = time();
            $app_poi_code = D('jubaopen_order')->where(array('orderId'=>$orderId))->getField('ePoiId');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            // 系统级参数
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );
            // 应用级参数
            $application_param = array(
                'orderId'=>$orderId
            );

            // 调用封装好的方法请求
            $res = jubaopen_http_post($url,$system_param,$application_param);
            $res = json_decode($res,true);
            if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "确认订单成功";
                exit(json_encode($returnData));
            }
            // 根据返回值来判断确认成功还是失败，再返回给安卓
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  聚宝盆商家取消订单
     *  device_code  设备码
     *  order_id   美团分配的order_id
     *  reason   取消原因（商家自定义的原因）
     *  reasonCode   规范化取消原因code（系统给定值，根据对应的原因选择对应的值，如2010）
     */
    public function jubaopen_restaurant_cancel_order(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $url = "http://api.open.cater.meituan.com/waimai/order/cancel";
            $orderId = I("post.orderId");   // 订单ID
            $reason = I("post.reason");     // 自定义原因
            $reasonCode = I("post.reasonCode");     // 原因代号
            $now = time();
            $app_poi_code = D('jubaopen_order')->where(array('orderId'=>$orderId))->getField('ePoiId');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            // 系统级参数
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );
            // 应用级参数
            $application_param = array(
                'orderId'=>$orderId,
                'reason'=>$reason,
                'reasonCode'=>$reasonCode
            );
            // 调用封装好的方法请求
            $res = jubaopen_http_post($url,$system_param,$application_param);
            $res = json_decode($res,true);
            if($res['data'] == 'ok'){
                 /*file_put_contents(__DIR__."/"."restaurant_meituan_cancel.txt",
                    '，reasonCode：'.$reasonCode.'，reason:'.$reason.
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/

                // 写进取消订单的表（表中用字段区分是美团取消还是商家取消）
                $developerId = D('meituan_config')->getField('developerId');
                $add_cancel['developerId'] = $developerId;
                $add_cancel['ePoiId'] = session('restaurant_id');
                $add_cancel['orderId'] = $orderId;
                $add_cancel['reasonCode'] = $reasonCode;
                $add_cancel['reason'] = $reason;
                $add_cancel['type'] = 2;    // 2表示商家自动取消  1表示美团推送的取消
                $rs = D('jubaopen_cancel')->data($add_cancel)->add();

                M('jubaopen_order')->where(array('orderId'=>$orderId))->save(array('order_status'=>3));

                $returnData['code'] = 1;
                $returnData['msg'] = "取消订单成功";
                exit(json_encode($returnData));
            }
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
    public function get_data_by_orderId(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderId = I("post.orderId");
            $condition['orderId'] = $orderId;
            $condition['status'] = 2;
            $condition['is_request'] = 0;
            $data = D('jubaopen_order')->where($condition)->find();

            if($data){
                $data['utime'] = date('m-d H:i',$data['utime']);
                if($data['deliveryTime'] == 0){
                    $data['deliveryTime'] = 0;
                }else{
                    $data['deliveryTime'] = date('m-d H:i',$data['deliveryTime']);
                }
                $data['favorites'] = (empty($data['favorites']) ? false :true);
                $data['isFavorites'] = (empty($data['isFavorites']) ? false :true);
                $data['isPoiFirstOrder'] = (empty($data['isPoiFirstOrder']) ? false :true);
                $data['poiFirstOrder'] = (empty($data['poiFirstOrder']) ? false :true);

                $data['ctime'] = date('m-d H:i',$data['ctime']);
                $data['detail'] = json_decode($data['detail'],true);
                $data['poiReceiveDetail'] = json_decode($data['poiReceiveDetail'],true);
                $data['extras'] = json_decode($data['extras']);
                // 将美团配送方式从代号改成中文
                $logisticsCode_detail = D('jubaopen_logisticscode')->where(array('logisticsCode'=> $data['logisticsCode']))->getField('logisticsCode_detail');
                $data['logisticsCode'] = $logisticsCode_detail;

                // 获取底部广告语
                $bill_foot_language = D('restaurant')->where(array('restaurant_id'=>$data['ePoiId']))->getField('bill_foot_language');
                $data['bill_foot_language'] = $bill_foot_language;

                $save['is_request'] = 0;
                $res = D('jubaopen_order')->where(array('orderId' => $orderId))->save($save);  //标识订单请求状态
                if ($res) {
                    $returnData['code'] = 1;
                    $returnData['msg'] = "获取订单数据成功";
                    $returnData['data'] = json_encode($data);
                    exit(json_encode($returnData));
                }
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "该订单号没有对应的数据或者已经被请求过";
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


    public function getUnrequestOrder(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $where['status'] = 2;
            $where['order_status'] = 1;
            $where['is_request'] = 0;
            $where['utime'] = array('egt', time() - 86400);
            $orderIds = M('jubaopen_order')->where($where)->field('orderId')->select();
            foreach ($orderIds as $key => $val) {
                $data = D('jubaopen_order')->where(array('orderId' => $val['orderId']))->find();

                if($data){
                    $data['utime'] = date('m-d H:i',$data['utime']);
                    if($data['deliveryTime'] == 0){
                        $data['deliveryTime'] = 0;
                    }else{
                        $data['deliveryTime'] = date('m-d H:i',$data['deliveryTime']);
                    }
                    $data['favorites'] = (empty($data['favorites']) ? false :true);
                    $data['isFavorites'] = (empty($data['isFavorites']) ? false :true);
                    $data['isPoiFirstOrder'] = (empty($data['isPoiFirstOrder']) ? false :true);
                    $data['poiFirstOrder'] = (empty($data['poiFirstOrder']) ? false :true);

                    $data['ctime'] = date('m-d H:i',$data['ctime']);
                    $data['detail'] = json_decode($data['detail'],true);
                    $data['poiReceiveDetail'] = json_decode($data['poiReceiveDetail'],true);
                    $data['extras'] = json_decode($data['extras']);
                    // 将美团配送方式从代号改成中文
                    $logisticsCode_detail = D('jubaopen_logisticscode')->where(array('logisticsCode'=> $data['logisticsCode']))->getField('logisticsCode_detail');
                    $data['logisticsCode'] = $logisticsCode_detail;

                    // 获取底部广告语
                    $bill_foot_language = D('restaurant')->where(array('restaurant_id'=>$data['ePoiId']))->getField('bill_foot_language');
                    $data['bill_foot_language'] = $bill_foot_language;

                    $save['is_request'] = 1;
                    $res = D('jubaopen_order')->where(array('orderId' => $val['orderId']))->save($save);  //标识订单请求状态
                    if ($res) {
                        $order_info[] = $data;
                    }
                }
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "获取订单数据成功";
            $returnData['data'] = json_encode($order_info);
            exit(json_encode($returnData));
        } else {
            $returnData['code'] = '0';
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }


    /**
     *  聚宝盆同意退款
     *  device_code  设备码
     *  orderId   聚宝盆分配的orderId
     *  reason   同意的原因
     */
    public function jubaopen_agree_refund(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $url = "http://api.open.cater.meituan.com/waimai/order/agreeRefund";
            $orderId = I("post.orderId");
            $now = time();
            $app_poi_code = D('jubaopen_order')->where(array('orderId'=>$orderId))->getField('ePoiId');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            // 系统级参数
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );
            $reason = I('post.reason') == null ? '同意退款' : I('post.reason');
            // 应用级参数
            $application_param = array(
                'orderId'=>$orderId,
                'reason'=>$reason
            );

            // 调用封装好的方法请求
            $res = jubaopen_http_post($url,$system_param,$application_param);
            $res = json_decode($res,true);
            if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "同意退款成功";
                exit(json_encode($returnData));
            }
            // 根据返回值来判断确认成功还是失败，再返回给安卓
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }



    /**
     *  聚宝盆拒绝退款
     *  device_code  设备码
     *  orderId   聚宝盆分配的orderId
     *  reason   拒绝的原因
     */
    public function jubaopen_reject_refund(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $url = "http://api.open.cater.meituan.com/waimai/order/rejectRefund";
            $orderId = I("post.orderId");
            $now = time();
            $app_poi_code = D('jubaopen_order')->where(array('orderId'=>$orderId))->getField('ePoiId');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            // 系统级参数
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );
            $reason = I('post.reason') == null ? '拒绝退款' : I('post.reason');
            // 应用级参数
            $application_param = array(
                'orderId'=>$orderId,
                'reason'=>$reason
            );

            // 调用封装好的方法请求
            $res = jubaopen_http_post($url,$system_param,$application_param);
            $res = json_decode($res,true);
            if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "同意退款成功";
                exit(json_encode($returnData));
            }
            // 根据返回值来判断确认成功还是失败，再返回给安卓
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  查询众包费用
     *  device_code  设备码
     *  orderIds   美团分配的orderId，多个orderId用逗号隔开
     */
    public function query_zhongbao_shipping(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $url = "http://api.open.cater.meituan.com/waimai/order/queryZbShippingFee";
            $orderIds = I("post.orderIds");  // 多个orderId用逗号隔开
            $now = time();
            $app_poi_code = session('restaurant_id');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );

            $application_param = array(
                'orderIds'=>$orderIds
            );

            $res = jubaopen_http_post($url,$system_param,$application_param);
            exit($res); // 返回价格信息给安卓
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  众包预下单
     *  device_code  设备码
     *  orderId   美团分配的orderId
     *  shippingFee  众包查询接口返回的配送费
     *  tipAmount    小费，不加小费输入0.0
     */
    public function zhongbao_prepare(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $url = "http://api.open.cater.meituan.com/waimai/order/prepareZbDispatch";
            $orderId = I("post.orderId");  // 多个orderId用逗号隔开
            $shippingFee = I("post.shippingFee");  // 众包查询接口返回的配送费
            $tipAmount = I("post.tipAmount");  // 小费，不加小费输入0.0
            $now = time();
            $app_poi_code = session('restaurant_id');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );

            $application_param = array(
                'orderId'=>$orderId,
                'shippingFee'=>$shippingFee,
                'tipAmount'=>$tipAmount
            );

            $res = jubaopen_http_post($url,$system_param,$application_param);
            $res = json_decode($res,true);
            if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "预下单成功";
                exit(json_encode($returnData));
            }else{
                /*{
                    "data":"ng",
                    "code":"1",
                    "msg":"运费变更",
                    "wm_order_view_id:"10234534654645645",
                    "new_shipping_fee":"4.0",
                    "count_start":"1456818140",
                    "count_down":"30"
                code	int	1 价格有变动 2 异常 3 商家余额不足 5 已发配送
                }*/
                $code = $res['code'];
                switch($code){
                    case 1:
                        $res['detail'] = '价格有变动';
                        break;
                    case 2:
                        $res['detail'] = '异常';
                        break;
                    case 3:
                        $res['detail'] = '商家余额不足';
                        break;
                    case 5:
                        $res['detail'] = '已发配送';
                        break;
                }
                $res['code'] = 0;
                exit(json_encode($res));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**（未完待续）
     *  众包预下单失败后（1 价格有变动 2 异常 3 商家余额不足 5 已发配送），调用确认下单接口进行下单
     *  device_code  设备码
     *  orderId   美团分配的orderId
     *  shippingFee  众包查询接口返回的配送费
     *  tipAmount    小费，不加小费输入0.0
     */
    public function zhongbao_confirm_after_fail(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $url = "http://api.open.cater.meituan.com/waimai/order/prepareZbDispatch";
            $orderId = I("post.orderId");  // 多个orderId用逗号隔开
            $shippingFee = I("post.shippingFee");  // 众包查询接口返回的配送费
            $tipAmount = I("post.tipAmount");  // 小费，不加小费输入0.0
            $now = time();
            $app_poi_code = session('restaurant_id');
            $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                'timestamp'=>$now,
            );

            $application_param = array(
                'orderId'=>$orderId,
                'shippingFee'=>$shippingFee,
                'tipAmount'=>$tipAmount
            );

            $res = jubaopen_http_post($url,$system_param,$application_param);
            $res = json_decode($res,true);
            if($res['data'] == 'ok'){
                $returnData['code'] = 1;
                $returnData['msg'] = "预下单成功";
                exit(json_encode($returnData));
            }else{
                /*{
                    "data":"ng",
                    "code":"1",
                    "msg":"运费变更",
                    "wm_order_view_id:"10234534654645645",
                    "new_shipping_fee":"4.0",
                    "count_start":"1456818140",
                    "count_down":"30"
                code	int	1 价格有变动 2 异常 3 商家余额不足 5 已发配送
                }*/
                $code = $res['code'];
                switch($code){
                    case 1:
                        $res['detail'] = '价格有变动';
                        break;
                    case 2:
                        $res['detail'] = '异常';
                        break;
                    case 3:
                        $res['detail'] = '商家余额不足';
                        break;
                    case 5:
                        $res['detail'] = '已发配送';
                        break;
                }
                $res['code'] = 0;
                exit(json_encode($res));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }
    /****************聚宝盆结束********************/

    // 美团一键同步数据
    public function oneKeySync()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $condition['ctime'] = array("between",array($start,$end));
            $condition['order_status'] = array("in",'1,2,3,4,6');
            $condition['ePoiId'] = session('restaurant_id');
//            $condition['ePoiId'] = 706;
            $allOrderInfo = M('jubaopen_order')->where($condition)->select();
            $newOrder = [];             // 新订单
            $haveConfirmOrder = [];     // 已确认订单
            $haveFinishOrder = [];      // 已完成订单
            $refundSuccessOrder = [];      // 退款成功订单
            foreach ($allOrderInfo as $key=>$val){
                if($val['order_status'] == 1){
                    // 新订单
                    $newOrder[] = $this->dealData($val);
                }elseif ($val['order_status'] == 2){
                    // 已确认订单
                    $haveConfirmOrder[] = $this->dealData($val);
                }elseif ($val['order_status'] == 4){
                    // 已完成订单
                    $haveFinishOrder[] = $this->dealData($val);
                }elseif ($val['order_status'] == 6 || $val['order_status'] == 3){
                    // 退款成功订单
                    $refundSuccessOrder[] = $this->dealData($val);
                }
            }
            $returnData['code'] = 1;
            $returnData['newOrder'] = $newOrder;
            $returnData['haveConfirmOrder'] = $haveConfirmOrder;
            $returnData['haveFinishOrder'] = $haveFinishOrder;
            $returnData['refundSuccessOrder'] = $refundSuccessOrder;
            $returnData['msg'] = "获取数据成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 对从数据库查询出来的数据进行处理
    public function dealData($data)
    {
        $data['utime'] = date('m-d H:i',$data['utime']);
        if($data['deliveryTime'] == 0){
            $data['deliveryTime'] = 0;
        }else{
            $data['deliveryTime'] = date('m-d H:i',$data['deliveryTime']);
        }
        $data['favorites'] = (empty($data['favorites']) ? false :true);
        $data['isFavorites'] = (empty($data['isFavorites']) ? false :true);
        $data['isPoiFirstOrder'] = (empty($data['isPoiFirstOrder']) ? false :true);
        $data['poiFirstOrder'] = (empty($data['poiFirstOrder']) ? false :true);

        $data['ctime'] = date('m-d H:i',$data['ctime']);
        $data['detail'] = json_decode($data['detail'],true);
        $data['poiReceiveDetail'] = json_decode($data['poiReceiveDetail'],true);
        $data['extras'] = json_decode($data['extras']);
        // 将美团配送方式从代号改成中文
        $logisticsCode_detail = D('jubaopen_logisticscode')->where(array('logisticsCode'=> $data['logisticsCode']))->getField('logisticsCode_detail');
        $data['logisticsCode'] = $logisticsCode_detail;

        // 获取底部广告语
        $bill_foot_language = D('restaurant')->where(array('restaurant_id'=>$data['ePoiId']))->getField('bill_foot_language');
        $data['bill_foot_language'] = $bill_foot_language;
        return json_encode($data);
    }
}