<?php
namespace Admin\Controller;

class MeituanpushController extends BaseController {
    /**
     * 获取店铺详细信息
     * @param String $orderId  订单ID
     * @return mixed
     */
    public function get_restaurant($orderId){
        // 其实也是店铺id
        $app_poi_code = D('jubaopen_order')->where(array('orderId'=>$orderId))->getField('ePoiId');
        $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
        $now = time();
        $url = 'http://api.open.cater.meituan.com/waimai/poi/queryPoiInfo';
        $system_param = array(
            'appAuthToken'=>$appAuthToken,
            'charset'=>'UTF-8',
            "timestamp"=>$now,
            'ePoiIds'=> $app_poi_code
        );
        // 聚宝盆get请求
        $return = jubaopen_http_get($url,$system_param);
        return $return;
    }

    /**
     *  对美团推送过来的数据校验sign签名
     **/
    public static function checkSign($data) {
        $data_sign = $data['sign'];
        unset($data['sign']); //去除sign
        array_filter($data); //过滤值为空的参数
        ksort($data); //自然排序

        $tmp = array();
        foreach ($data as $key => $value) {
            $tmp[] = "$key$value";
        }
        $strSign = implode('', $tmp);

        $sign_key = D('meituan_config')->getField('signkey');
        $strSign = $sign_key.$strSign;
        $sign = strtolower(sha1($strSign));

        if ($sign == $data_sign){
            return true;
        }else{
            return false;
        }
    }


    /**
    *获取用户手机号码
     */
    public function getUserPhone()
    {
        if(array_key_exists('developerId',$_POST) && array_key_exists('timestamp',$_POST) && array_key_exists('sign',$_POST)){
            file_put_contents("./"."getuserphone_jubaopen.txt",'推送数据格式:'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
            echo '{"data":"OK"}';
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收聚宝盆订单推送
     */
    public function push_order_jubaopen(){
        if(array_key_exists('order',$_POST)){
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'push验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }

            /*********************美团推送订单过来服务器开始*********************/
            //添加分离好的数据到美团订单表
            $order_detail=M('jubaopen_order');
            $data=$_POST;
            //file_put_contents(__DIR__."/"."Meituan_push_order.txt",'订单信息:'.json_encode($data)."\r\n\r\n",FILE_APPEND);
            // 传过来总共字段  developerId  ePoiId  sign  order
            // order字段
            $order = json_decode($data['order'],true);
            foreach($order as $key=>$val){
                // 过滤掉表情字符
                if($key == 'detail' || $key == 'extras' || $key == 'poiReceiveDetail' || $key == 'caution'){
                    $data[$key] = $this->filterEmoji($val);
//                    file_put_contents(__DIR__."/"."meituan_guolv.txt",'订单信息:'.$this->filterEmoji($val)."\r\n\r\n",FILE_APPEND);
                }else{
                    $data[$key] = $val;
                }
            }
            unset($data['order']);

            $if_same = $order_detail->where(array('orderId'=>$data['orderId']))->getField('id');
            if($if_same){
                $res = true;
            }else{
                // 不存在数据库才添加
                $res = $order_detail->data($data)->add();
            }

            /*********************美团推送订单过来服务器结束*********************/

            /***************************服务器推送订单给安卓开始*********************************/
            if($res){
                // 组装安卓所需数据
                $push_data['type'] = 'place_order';   // 类型为：下单
                $push_data['orderId'] = $data['orderId'];
                $push_data['platform'] = 'meituan';
                    /***********推送开始*****************/
                // 传给安卓的数据存进日志
//            file_put_contents(__DIR__."/"."Alipush_data_jubaopen.txt",json_encode($push_data)."\r\n\r\n",FILE_APPEND);

                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$data['ePoiId']))->field('device_id')->select();
                $php_title = 'founpad_restaurant_push'; // 标题
                /**
                 * 阿里推送公共方法
                 * @param Array $devices_ids 设备ID数组（二维数组）
                 * @param String $php_title 消息标题
                 * @param String $php_body  具体内容
                 * @return mixed|\SimpleXMLElement
                 */
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data));

                file_put_contents(__DIR__."/"."Alipush_Android_status.txt",'流水：'.$order['daySeq'].'，订单ID：'. $order['orderId'].
                    '，店铺：'.$data['ePoiId'].'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                    "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
//               $return = $this->query_push_status($response['MessageId']);
                 /*file_put_contents(__DIR__."/"."Alipush_device_id.txt",
                     '信息：'.json_encode($devices_ids).
                     '，店铺：'.$data['ePoiId'].
                     '，msg_id：'.$response['MessageId'].
                     "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/
                 /***********推送结束*****************/
                echo '{"data": "OK"}';
            }else{
                echo '{"data":"error"}';
            }
            /***************************服务器推送订单给安卓结束*********************************/
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收聚宝盆取消订单
     */
    public function order_cancel_jubaopen(){
        if(array_key_exists('developerId',$_POST)){
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'cancel验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }


            //添加推送取消订单信息到美团订单取消表
            $cancel_order=M('jubaopen_cancel');
            $data=$_POST;
            $detail = json_decode($data['orderCancel'],true);
            $data['orderId'] = $detail['orderId'];
            $data['reasonCode'] = $detail['reasonCode'];
            $data['reason'] = $detail['reason'];

            $if_same = $cancel_order->where(array('orderId'=>$data['orderId']))->getField('id');
            if($if_same){
                $res = true;
            }else{
                // 不存在数据库才添加
                $res = $cancel_order->data($data)->add();
            }

            if($res){
                M('jubaopen_order')->where(array('orderId'=>$data['orderId']))->save(array('order_status'=>3));

                $data['type'] = 'cancel'; // 取消订单类型
                $data['platform'] = 'meituan';  // 区分美团和饿了么
                unset($data['orderCancel']);

                /***********推送开始*****************/
                // 通过标识查出店铺channel_id
                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$data['ePoiId']))->field('device_id')->select();
                $php_title = 'founpad_restaurant_push'; // 标题
                /**
                 * 阿里推送公共方法
                 * @param Array $devices_ids 设备ID数组（二维）
                 * @param String $php_title 消息标题
                 * @param String $php_body  具体内容
                 * @return mixed|\SimpleXMLElement
                 */
                // 发送
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($data));
                /* file_put_contents(__DIR__."/"."Alipush_cancel_status.txt",'订单ID：'. $data['orderId'].
                     '，店铺：'.$data['ePoiId'].'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                     "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/
                /***********推送结束*****************/

                echo '{"data":"OK"}';
            }else{
                echo '{"data":"error"}';
            }
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收聚宝盆订单结算
     */
    public function order_result_jubaopen(){
        if(array_key_exists('developerId',$_POST)){
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'result验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }


            //添加推送结算订单信息数据到美团订单数据表
            $order_result=M('jubaopen_result');
            $data=$_POST;

            /*file_put_contents(__DIR__."/"."jubaopen_result.txt",
                    '，结算信息：'.json_encode($data).
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/


            $detail = json_decode($data['tradeDetail'],true);
            foreach($detail as $key=>$val){
                $data[$key] = $val;
            }
            unset($data['tradedetail']);

            /*****判断推送过来的是订单展示ID还是订单ID，此处应为订单展示ID****/
            // 只管status值为8的
            /*if($data['status'] == 8){
                // 判断是否重复添加
                $where['orderId'] = $data['orderId'];
                $where['status'] = $data['status'];
                $if_same = $order_result->where($where)->getField('id');

                if($if_same){
                    $res = true;
                }else{
                    // 注意，这里接收到的订单ID是订单展示id，虽然其命名是orderId，但不是其他接口中的订单id（orderId），
                    $res = $order_result->data($data)->add();
                }
            }else{
                // 状态值不是8的不作入库处理
                $res = true;
            }*/
            // 判断是否重复添加
            $where['orderId'] = $data['orderId'];
            $where['status'] = $data['status'];
            $if_same = $order_result->where($where)->getField('id');

            if($if_same){
                $res = true;
            }else{
                // 注意，这里接收到的订单ID是订单展示id，虽然其命名是orderId，但不是其他接口中的订单id（orderId），
                $res = $order_result->data($data)->add();
            }

            if($res){
                echo '{"data":"OK"}';
            }else{
                echo '{"data":"error"}';
            }
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收聚宝盆已完成订单信息，并推送给安卓
     */
    public function order_finish_jubaopen(){
        if(array_key_exists('developerId',$_POST)){
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'finish验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }


            //将已完成的订单id和订单展示id插入已完成表
            $meituan_already_finish = D('jubaopen_already_finish');
            $data=$_POST;
            // 穿过来总共字段  developerId  ePoiId  sign  order
            // order字段
            $order = json_decode($data['order'],true);
            $data['orderId'] = $order['orderId'];
            $data['orderIdView'] = $order['orderIdView'];
            $data['status'] = $order['status'];

            $where['orderId'] = $data['orderId'];
            $if_same = $meituan_already_finish->where($where)->getField('id');
            if($if_same){
                $res = true;
            }else{
                $res = $meituan_already_finish->data($data)->add();
            }

            unset($data['order']);
            $data['type'] = 'finish';   // 推送类型为已完成
            $data['platform'] = 'meituan';  // 区分美团和饿了么

            if($res){
                M('jubaopen_order')->where(array('orderId'=>$data['orderId']))->save(array('order_status'=>4));
                /***********推送开始*****************/
                // 通过标识查出店铺channel_id
                $restaurant_id = $_POST['ePoiId'];
                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
                $php_title = 'founpad_restaurant_push'; // 标题
                /**
                 * 阿里推送公共方法
                 * @param Array $devices_ids 设备ID数组（二维）
                 * @param String $php_title 消息标题
                 * @param String $php_body  具体内容
                 * @return mixed|\SimpleXMLElement
                 */
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($data));
               /* file_put_contents(__DIR__."/"."Alipush_finish_status.txt",'流水：'.$order['daySeq'].'，订单ID：'. $data['orderId'].
                    '，店铺：'.$data['ePoiId'].'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/
                /***********推送结束*****************/

                echo '{"data":"OK"}';
            }else{
                echo '{"data":"error"}';
            }
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收聚宝盆已确认订单信息
     */
    public function order_confirm_jubaopen()
    {
        if(array_key_exists('developerId',$_POST)){
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'confirm验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }


            //将已完成的订单id和订单展示id插入已完成表
            $meituan_already_confirm = D('jubaopen_already_confirm');
            $data=$_POST;
            // order字段
            $order = json_decode($data['order'],true);
            $data['orderId'] = $order['orderId'];
            $data['orderIdView'] = $order['orderIdView'];
            $data['status'] = $order['status'];

            $where['orderId'] = $data['orderId'];
            $if_same = $meituan_already_confirm->where($where)->getField('id');
            if($if_same){
                $res = true;
            }else{
                $res = $meituan_already_confirm->add($data);
            }

            $daySeq = $order['daySeq'];
            $data['type'] = 'confirm';   // 推送类型为已确认
            $data['platform'] = 'meituan';  // 区分美团和饿了么
            unset($data['order']);

            if($res){
                M('jubaopen_order')->where(array('orderId'=>$data['orderId']))->save(array('order_status'=>2));
                /***********推送开始*****************/
                // 通过标识查出店铺channel_id
                $restaurant_id = $_POST['ePoiId'];
                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
                $php_title = 'founpad_restaurant_push'; // 标题
                /**
                 * 阿里推送公共方法
                 * @param Array $devices_ids 设备ID数组（二维）
                 * @param String $php_title 消息标题
                 * @param String $php_body  具体内容
                 * @return mixed|\SimpleXMLElement
                 */
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($data));
               /* file_put_contents(__DIR__."/"."Alipush_confirm_status.txt",'流水：'.$daySeq.'，订单ID：'. $data['orderId'].
                    '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/
                /***********推送结束*****************/

                echo '{"data":"OK"}';
            }else{
                echo '{"data":"error"}';
            }
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收门店状态变更，如果是休息状态，则自动将其改为营业
     */
    public function receive_restaurant_status(){
        if(array_key_exists('developerId',$_POST)){
            $data=$_POST;
            $order = json_decode($data['poiStatus'],true);
            $poiStatus = $order['poiStatus'];
            if($poiStatus == 120){
                //　说明是休息状态，调用置营业接口
                $url = 'http://api.open.cater.meituan.com/waimai/poi/open';
                $app_poi_code = $order['ePoiId'];
                $appAuthToken = D('meituan')->where(array('app_poi_code'=>$app_poi_code))->getField('appAuthToken');
                $now = time();
                $system_param = array(
                    'appAuthToken'=>$appAuthToken,
                    'charset'=>'UTF-8',
                    'timestamp'=>$now,
                );
                $application_param = array(

                );
                $res = jubaopen_http_post($url,$system_param,$application_param);
            }
            echo '{"data":"OK"}';
        }else{
            echo '{"data":"error"}';
        }
    }

    /**
     *  接收聚宝盆退款类消息
     */
    public function receive_jubaopen_refund()
    {
        if (array_key_exists('developerId', $_POST)) {
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'refund验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }


            $data  = $_POST;
            $order = json_decode($data['orderRefund'], true);
            foreach ($order as $key => $val) {
                $data[$key] = $val;
            }
            unset($data['orderRefund']);

            // 判断是否是被商家拒绝后第二次发起退款申请（同一类型，同一订单号在数据库已有记录）
            if($data['notifyType'] == 'apply'){
                $where['type'] = 'apply';
                $where['orderId'] = $data['orderId'];  // 美团订单id
                $if_two = D('jubaopen_refund')->where($where)->getField('id');
                if($if_two){
                    // 第二次发起申请退款
                    $data['notifyType'] = 'second_apply';
                }
            }

            $res = D('jubaopen_refund')->data($data)->add();

            // 区分不同的退款类型
           /* apply	发起退款
            agree	确认退款
            reject	驳回退款
            cancelRefund	用户取消退款申请
            cancelRefundComplaint	取消退款申诉*/

            # 消息类型
            $push_data['type'] = $data['notifyType'];   // 退款的类型
            $push_data['platform'] = 'meituan';  // 区分美团和饿了么
            $push_data['orderId'] = $data['orderId'];  // 美团订单id
            $push_data['reason'] = $data['reason'];  // 原因

            if ($res) {
                if($data['notifyType'] == 'agree'){
                    // 退款成功
                    M('jubaopen_order')->where(array('orderId'=>$data['orderId']))->save(array('order_status'=>6));
                }else{
                    // 退款中
                    M('jubaopen_order')->where(array('orderId'=>$data['orderId']))->save(array('order_status'=>5));
                }

                /***********推送开始*****************/
                // 通过标识查出店铺channel_id
                $restaurant_id = $_POST['ePoiId'];
                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();

                $php_title = 'founpad_restaurant_push'; // 标题
                /**
                 * 阿里推送公共方法
                 * @param Array $devices_ids 设备ID数组（二维）
                 * @param String $php_title 消息标题
                 * @param String $php_body  具体内容
                 * @return mixed|\SimpleXMLElement
                 */
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data));
               /* file_put_contents(__DIR__."/"."Alipush_refund_status.txt",'订单ID：'. $data['orderId'].
                    '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/
                /***********推送结束*****************/
                echo '{"data":"OK"}';
            } else {
                echo '{"data":"error"}';
            }
        }
    }

    /**
     *  接收聚宝盆配送状态消息
     */
    public function jubaopen_deliver_status()
    {
        if (array_key_exists('developerId', $_POST)) {
            $receive = $this->checkSign($_POST);
            if(!$receive){
                // 签名错误
                file_put_contents(__DIR__."/"."receive_jubaopen_check.txt",'delivery验证错误，'.$_POST['ePoiId']."\r\n\r\n",FILE_APPEND);
                echo '{"data":"error"}';
                exit;
            }


            $data  = $_POST;
            $order = json_decode($data['shippingStatus'], true);
            foreach ($order as $key => $val) {
                if($key == 'dispatcherName'){
//                    $val = urldecode($val);
                    $val = $val;
                }
                $data[$key] = $val;
            }
            unset($data['shippingStatus']);

            $res = D('jubaopen_delivery_status')->data($data)->add();

            if ($res) {
                /***********推送开始*****************/
                // 通过标识查出店铺channel_id
                $restaurant_id = $data['ePoiId'];
                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
                $php_title = 'founpad_restaurant_push'; // 标题

                unset($data['developerId']);
                unset($data['ePoild']);
                unset($data['sign']);
//                0-配送单发往配送;10-配送单已确认;20-骑手已取餐;40-骑手已送达;100-配送单已取消
                $shippingStatus = $data['shippingStatus'];
                switch($shippingStatus){
                    case 0:
                        $data['shippingStatus'] = '配送单发往配送';
                        break;
                    case 10:
                        $data['shippingStatus'] = '配送单已确认';
                        break;
                    case 20:
                        $data['shippingStatus'] = '骑手已取餐';
                        break;
                    case 40:
                        $data['shippingStatus'] = '骑手已送达';
                        break;
                    case 100:
                        $data['shippingStatus'] = '配送单已取消';
                        break;
                }
                $data['type'] = 'delivery_status';   // 推送类型为配送状态
                $data['platform'] = 'meituan';  // 区分美团和饿了么
                /**
                 * 阿里推送公共方法
                 * @param Array $devices_ids 设备ID数组（二维）
                 * @param String $php_title 消息标题
                 * @param String $php_body  具体内容
                 * @return mixed|\SimpleXMLElement
                 */
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($data));
                /*file_put_contents(__DIR__."/"."jubaopen_deliver_status.txt",'订单ID：'. $data['orderId'].
                    '，店铺：'.$restaurant_id.'，MessageId:'.$response['MessageId'].'，RequestId：'.$response['RequestId'].
                    "，时间：".date('Y-m-d H:i:s')."\r\n\r\n",FILE_APPEND);*/
                /***********推送结束*****************/
                echo '{"data":"OK"}';
            } else {
                echo '{"data":"error"}';
            }
        }
    }

    /**
     *  接收聚宝盆云端心跳检测
     */
    public function jubaopen_heartbeat()
    {
//        file_put_contents(__DIR__."/"."jubaopen_heartbeat.txt","心在跳"."\r\n\r\n",FILE_APPEND);
         echo '{"data":"OK"}';
    }

    /**
     *  接收聚宝盆门店解除绑定的信息
     */
    public function jubaopen_unbind()
    {
        file_put_contents(__DIR__."/"."jubaopen_unbind.txt",json_encode($_POST)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
        // 清除对应的店铺记录
        $epoiId = I('post.epoiId');
        $res = D('meituan')->where(array('app_poi_code'=>$epoiId))->delete();
//        echo '{"data":"OK"}';
        echo '{"data":"success"}';
    }

    /**
     *  聚宝盆订单推送测试
     */
    public function push_test(){
        // 组装安卓所需数据
        $push_data['type'] = 'place_order';   // 类型为：下单
        $push_data['orderId'] = $_GET['orderId'];
        $push_data['platform'] = 'meituan';
        /***********推送开始*****************/
        // 传给安卓的数据存进日志
//            file_put_contents(__DIR__."/"."Alipush_data_jubaopen.txt",json_encode($push_data)."\r\n\r\n",FILE_APPEND);

//        $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$data['ePoiId']))->field('device_id')->select();
//        $devices_ids = array(array('device_id'=>'82aa24b856054e14944fa5de28055bfd'));   // 云牛测试机
//        $devices_ids = array(array('device_id'=>'cb9a4aa5a4ad4f2b8077bfae7a0fc727'));   // 豪客兹
        $devices_ids = array(array('device_id'=>'fe6680b6fb3042a1a897ff2f169f7e9d'));   // SZJW001

        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data));
        p($response);
    }

    public function queryPush()
    {
        $response = $this->query_push_status(I('msg_id'));
        p($response);
    }

    /**
     *  过滤表情符号
     */
    function filterEmoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

        return $str;
    }
}
