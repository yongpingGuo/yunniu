<?php
namespace Api\Controller;
use data\service\SellOut as ServiceSellOut;
use data\service\TakeMeal as ServiceTakeMeal;

class FourthPayController extends BaseController
{
    /**
     * 同步md5秘钥
     */
    public function tongbu_md5_key()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $md5_key_android = I('post.md5_key');   // md5秘钥值
            $md5_key_database = D('fourth_md5_key')->where(array('restaurant_id'=>session('restaurant_id')))->getField('md5_key');
            if($md5_key_database){
                // 更新
                if($md5_key_android != $md5_key_database){
                    $res = D('fourth_md5_key')->where(array('restaurant_id'=>session('restaurant_id')))->save(array('md5_key'=>$md5_key_android));
                    if($res === false){
                        $returnData['code'] = 0;
                        $returnData['msg'] = "编辑md5秘钥值失败";
                        exit(json_encode($returnData));
                    }
                }
            }else{
                // 新增
                $add['md5_key'] = $md5_key_android;
                $add['restaurant_id'] = session('restaurant_id');
                $res = D('fourth_md5_key')->add($add);
                if(!$res){
                    $returnData['code'] = 0;
                    $returnData['msg'] = "新增md5秘钥值失败";
                    exit(json_encode($returnData));
                }

            }
            $returnData['code'] = 1;
            $returnData['msg'] = "同步数据成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 第四方支付的反扫
     */
    public function pay_scan()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $fourth_sn = I('post.fourth_sn');   // 提交给民生的订单号
            $order_sn = I('post.order_sn');     // 服务器订单号
            $public_key = I('post.public_key'); // 秘钥
            $operater_id = I('post.operater_id'); // 操作员ID

            if($operater_id != 0 && $operater_id == null){
                $returnData['code'] = 0;
                $returnData['msg'] = "缺少操作员ID";
                exit(json_encode($returnData));
            }

            $tiao_xing_ma = I('post.tiao_xing_ma'); // 条形码
            $business_no = I('post.business_no');   // 商户号
            if(!($fourth_sn && $order_sn && $public_key && $tiao_xing_ma && $business_no)){
                $returnData['code'] = 0;
                $returnData['msg'] = "参数不完整";
                exit(json_encode($returnData));
            }

            $restaurant_id = session('restaurant_id');
            $MD5_KEY = D('fourth_md5_key')->where(array('restaurant_id'=>$restaurant_id))->getField('md5_key');
            if(empty($MD5_KEY)){
                $returnData['code'] = 0;
                $returnData['msg'] = "还未同步md5_key值";
                exit(json_encode($returnData));
            }

            // 将民生订单号关联到数据库订单号
            $res = order()->where(array('order_sn'=>$order_sn))->save(array('minsheng_post_no'=>$fourth_sn));
            if($res === false){
                $returnData['code'] = 0;
                $returnData['msg'] = "关联系统订单与民生订单失败";
                exit(json_encode($returnData));
            }
            // 订单金额
//            $total_aomount = order()->where(array('order_sn'=>$order_sn))->getField('total_amount');

            // 订单信息
            $order_info = order()->where(array('order_sn'=>$order_sn))->field('total_amount,order_status')->find();
            if(empty($order_info)){
                $returnData['code'] = 0;
                $returnData['msg'] = '没有对应的订单信息';
                exit(json_encode($returnData));
            }
            if($order_info['order_status'] == 3){
                $returnData['code'] = 0;
                $returnData['msg'] = '已支付';
                exit(json_encode($returnData));
            }
            $total_aomount = $order_info['total_amount'];


            $notify_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/pay_scan_notify";   // 回调地址
            $APIurl = 'http://payapi.498.net/api3.0/api.php'; //接口地址
            // 生成json串
            $data['a'] = "payScan"; // 接口名
            $data['i'] = $operater_id;       // 操作员ID
            $data['j'] = $total_aomount;    // 金额
            $data['c'] = $tiao_xing_ma;     // 条码或二维码数据
            $data['q'] = "0";           // 支付宝通道值，0智能扫码
            $data['t'] = "1";           // 支付方式 固定值 1
            $data['tn'] = $device_code;     // 终端号
            $data['z'] = "2";               // 终端类型 (1:手机等移动设备 2:电脑软件 3:POS 4:web,wap)
            $data['oo'] = $fourth_sn;        // 18位订单号，规则如文件头
            $data['notify_url'] = $notify_url;
            $json = json_encode($data);
            // 获取RSA加密
            $RSAstr = $this->rsaEncode($json, $business_no, $public_key);
            // 发送http请求
            $returnText = $this->getHttpResponsePOST($APIurl, 's='.$RSAstr);
            $receive = json_decode($returnText,true);
//            receive:{"f":"2","m":"交易正在处理中，需要用户确认！","d":{"go":"20170916185917062","o":"215055595522356784","oo":"315055595527028897","u":"",
//              "dt":"2017-09-16 18:59:17","q":"2","t":"1","qt":"12","j":"0.02","hj":"0","gj":"0","tj":"0","sj":"0.02"}}
            if($receive['f'] == 0){
                $returnData['code'] = 0;
                $returnData['msg'] = $receive['m'];
                exit(json_encode($returnData));
            }
//            file_put_contents(__DIR__."/"."pay_scan.txt",'receive:'.$returnText.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);

            $returnData['code'] = 1;
            $returnData['msg'] = '请求成功';
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 反扫回调
     */
    public function pay_scan_notify()
    {
        $rece = file_get_contents('php://input');
//        file_put_contents(__DIR__."/"."fan_sao_notify.txt",'notify:'.$rece.",time:".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

        // 接收值
        $returnText = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
        // 验签
        $return = $this->check_sign($returnText);
        if(!$return) {
            echo 'error';
//            file_put_contents(__DIR__."/"."check_sign_fourth.txt",'反扫:'.$return.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);
            exit;
        }

        // 判断是否成功
        $pay_type = $returnText['d']['q'];      // 支付类型：1支付宝，2微信

//        file_put_contents(__DIR__."/"."fansao_q.txt",'反扫q的值:'.$pay_type.",time:".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

        $receive = $this->is_success($returnText);
        if($receive){
            echo 'success';
        }else{
            echo 'error';
        }
    }

    /**
     * 主扫
     */
    public function pay_code()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $fourth_sn = I('post.fourth_sn');   // 提交给民生的订单号
            $order_sn = I('post.order_sn');     // 服务器订单号
            $public_key = I('post.public_key'); // 秘钥
            $business_no = I('post.business_no');   // 商户号
            $operater_id = I('post.operater_id'); // 操作员ID

            if($operater_id != 0 && $operater_id == null){
                $returnData['code'] = 0;
                $returnData['msg'] = "缺少操作员ID";
                exit(json_encode($returnData));
            }
            if(!($fourth_sn && $order_sn && $public_key && $business_no)){
                $returnData['code'] = 0;
                $returnData['msg'] = "参数不完整";
                exit(json_encode($returnData));
            }

            $restaurant_id = session('restaurant_id');
            $MD5_KEY = D('fourth_md5_key')->where(array('restaurant_id'=>$restaurant_id))->getField('md5_key');
            if(empty($MD5_KEY)){
                $returnData['code'] = 0;
                $returnData['msg'] = "还未同步md5_key值";
                exit(json_encode($returnData));
            }

            // 将民生订单号关联到数据库订单号
            $res = order()->where(array('order_sn'=>$order_sn))->save(array('minsheng_post_no'=>$fourth_sn));
            if($res === false){
                $returnData['code'] = 0;
                $returnData['msg'] = "关联系统订单与民生订单失败";
                exit(json_encode($returnData));
            }

            // 订单信息
            $order_info = order()->where(array('order_sn'=>$order_sn))->field('total_amount,order_status')->find();
            if(empty($order_info)){
                $returnData['code'] = 0;
                $returnData['msg'] = '没有对应的订单信息';
                exit(json_encode($returnData));
            }
            if($order_info['order_status'] == 3){
                $returnData['code'] = 0;
                $returnData['msg'] = '已支付';
                exit(json_encode($returnData));
            }
            $total_aomount = $order_info['total_amount'];

            // 请求支付宝二维码
            $ali_notity_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/ali_notify";
            $APIurl = 'http://payapi.498.net/api3.0/api.php'; //接口地址

            $ali_data['a'] = "payCode";     // 接口名称
            $ali_data['i'] = $operater_id;      // 操作员ID
            $ali_data['j'] = $total_aomount;    // 金额
            $ali_data['q'] = "1";   // 支付通道  1.支付宝, 2.微信支付
            $ali_data['tn'] = $device_code;     // 终端号
            $ali_data['t'] = '3';       // 支付方式 固定值 3
            $ali_data['z'] = '2';       // 终端类型 (1:手机等移动设备 2:电脑软件 3:POS 4:web,wap)
            $ali_data['oo'] = $fourth_sn;   // 18位订单号
            $ali_data['notify_url'] = $ali_notity_url;
            $json = json_encode($ali_data);
            $RSAstr = $this->rsaEncode($json, $business_no, $public_key);
            $returnText = $this->getHttpResponsePOST($APIurl, 's='.$RSAstr);
            $receive = json_decode($returnText,true);
            $ali_qr = $receive['d']['qr'];      // 支付宝二维码URL

//            file_put_contents(__DIR__."/"."zhusao_code.txt",'ali_receive:'.$returnText.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);

            // 请求微信二维码
            $weixin_notity_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/weixin_notify";
            $APIurl = 'http://payapi.498.net/api3.0/api.php'; //接口地址
            $weixin_data['a'] = "payCode";
            $weixin_data['i'] = $operater_id;
            $weixin_data['j'] = $total_aomount;
            $weixin_data['q'] = "2";
            $weixin_data['tn'] = $device_code;
            $weixin_data['t'] = '3';
            $weixin_data['z'] = '2';
            $weixin_data['oo'] = $fourth_sn;
            $weixin_data['notify_url'] = $weixin_notity_url;
            $json_weixin = json_encode($weixin_data);
            $RSAstr_weixin = $this->rsaEncode($json_weixin, $business_no, $public_key);
            $returnText_weixin = $this->getHttpResponsePOST($APIurl, 's='.$RSAstr_weixin);
            $receive_weixin = json_decode($returnText_weixin,true);
            $weixin_qr = $receive_weixin['d']['qr'];        // 微信二维码URL

//            file_put_contents(__DIR__."/"."zhusao_code.txt",'weixin_receive:'.$returnText_weixin.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);

            if($ali_qr && $weixin_qr){
                $ali_qr = str_replace(array("/",'?'),array('@@','$$'),$ali_qr);
                $weixin_qr = str_replace(array("/",'?'),array('@@','$$'),$weixin_qr);

                $returnData['code'] = 1;
                $returnData['ali_qr'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/create_qrc_img/code_url/".$ali_qr."/type/1";
                $returnData['weixin_qr'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/create_qrc_img/code_url/".$weixin_qr."/type/2";
                $returnData['msg'] = "请求民生二维码成功";
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['ali_qr'] = '';
                $returnData['weixin_qr'] = '';
                $returnData['msg'] = "请求民生二维码失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['ali_qr'] = '';
            $returnData['weixin_qr'] = '';
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 主扫（同步订单时生成）
     */
    public function pay_code_in_place_order($data_arr)
    {
        $device_code = $data_arr['device_code'];
        $this->isLogin($device_code);
        if ($this->is_security) {
            $fourth_sn = $data_arr['fourth_sn'];   // 提交给民生的订单号
            $order_sn = $data_arr['order_sn'];     // 服务器订单号
            $public_key = $data_arr['public_key']; // 秘钥
            $operater_id = $data_arr['operater_id']; // 操作员ID
            $business_no = $data_arr['business_no'];   // 商户号

            if($operater_id != 0 && $operater_id == null){
                $returnData['code'] = 0;
                $returnData['msg'] = '缺少操作员ID';
                return $returnData;
            }
            if(!($fourth_sn && $order_sn && $public_key && $business_no)){
                $returnData['code'] = 0;
                $returnData['msg'] = '参数不完整';
                return $returnData;
            }

            $restaurant_id = session('restaurant_id');
            $MD5_KEY = D('fourth_md5_key')->where(array('restaurant_id'=>$restaurant_id))->getField('md5_key');
            if(empty($MD5_KEY)){
                $returnData['code'] = 0;
                $returnData['msg'] = '没有对应的md5_key信息';
                return $returnData;
            }

            // 将民生订单号关联到数据库订单号
            $res = order()->where(array('order_sn'=>$order_sn))->save(array('minsheng_post_no'=>$fourth_sn));
            if($res === false){
                $returnData['code'] = 0;
                $returnData['msg'] = '关联服务器订单与民生银行订单失败';
                return $returnData;
            }

            // 订单信息
            $order_info = order()->where(array('order_sn'=>$order_sn))->field('total_amount,order_status')->find();
            if(empty($order_info)){
                $returnData['code'] = 0;
                $returnData['msg'] = '没有对应的订单信息';
                return $returnData;
            }
            if($order_info['order_status'] == 3){
                $returnData['code'] = 0;
                $returnData['msg'] = '已支付';
                return $returnData;
            }
            $total_aomount = $order_info['total_amount'];

            // 请求支付宝二维码
            $ali_notity_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/ali_notify";
            $APIurl = 'http://payapi.498.net/api3.0/api.php'; //接口地址

            $ali_data['a'] = "payCode";     // 接口名称
            $ali_data['i'] = $operater_id;      // 操作员ID
            $ali_data['j'] = $total_aomount;    // 金额
            $ali_data['q'] = "1";   // 支付通道  1.支付宝, 2.微信支付
            $ali_data['tn'] = $device_code;     // 终端号
            $ali_data['t'] = '3';       // 支付方式 固定值 3
            $ali_data['z'] = '2';       // 终端类型 (1:手机等移动设备 2:电脑软件 3:POS 4:web,wap)
            $ali_data['oo'] = $fourth_sn;   // 18位订单号
            $ali_data['notify_url'] = $ali_notity_url;
            $json = json_encode($ali_data);
            $RSAstr = $this->rsaEncode($json, $business_no, $public_key);
            $returnText = $this->getHttpResponsePOST($APIurl, 's='.$RSAstr);
            $receive = json_decode($returnText,true);
            $ali_qr = $receive['d']['qr'];      // 支付宝二维码URL

//            file_put_contents(__DIR__."/"."zhusao_code.txt",'ali_receive:'.$returnText.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);

            // 请求微信二维码
            $weixin_notity_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/weixin_notify";
            $APIurl = 'http://payapi.498.net/api3.0/api.php'; //接口地址
            $weixin_data['a'] = "payCode";
            $weixin_data['i'] = $operater_id;
            $weixin_data['j'] = $total_aomount;
            $weixin_data['q'] = "2";
            $weixin_data['tn'] = $device_code;
            $weixin_data['t'] = '3';
            $weixin_data['z'] = '2';
            $weixin_data['oo'] = $fourth_sn;
            $weixin_data['notify_url'] = $weixin_notity_url;
            $json_weixin = json_encode($weixin_data);
            $RSAstr_weixin = $this->rsaEncode($json_weixin, $business_no, $public_key);
            $returnText_weixin = $this->getHttpResponsePOST($APIurl, 's='.$RSAstr_weixin);
            $receive_weixin = json_decode($returnText_weixin,true);
            $weixin_qr = $receive_weixin['d']['qr'];        // 微信二维码URL

//            file_put_contents(__DIR__."/"."zhusao_code.txt",'weixin_receive:'.$returnText_weixin.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);

            if($ali_qr && $weixin_qr){
                $ali_qr = str_replace(array("/",'?'),array('@@','$$'),$ali_qr);
                $weixin_qr = str_replace(array("/",'?'),array('@@','$$'),$weixin_qr);

                $ali_qr_urlencode = urlencode($ali_qr);
                $weixin_qr_urlencode = urlencode($weixin_qr);

                $returnData['code'] = 1;
//
//                $returnData['ali_qr'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/create_qrc_img/code_url/".$ali_qr_urlencode."/type/1";
                $returnData['ali_qr'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/create_qrc_img/code_url/".$ali_qr_urlencode."/type/1/order_sn/$fourth_sn";
//                $returnData['weixin_qr'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/create_qrc_img/code_url/".$weixin_qr_urlencode."/type/2";
                $returnData['weixin_qr'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/FourthPay/create_qrc_img/code_url/".$weixin_qr_urlencode."/type/2/order_sn/$fourth_sn";
                $returnData['msg'] = "请求民生二维码成功";
                return $returnData;
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "请求民生二维码失败，支付宝msg：".$receive['m'].",微信msg：".$receive_weixin['m'];
                return $returnData;
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            return $returnData;
        }
    }

    /**
     * 支付宝回调
     */
    public function ali_notify()
    {
//        file_put_contents(__DIR__."/"."zhusao_ali_notify.txt",'rece:'.file_get_contents('php://input').",time:".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

        $returnText = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
        // 验签
        $return = $this->check_sign($returnText);
        if(!$return) {
            echo 'error';
//            file_put_contents(__DIR__."/"."check_sign_fourth.txt",'ali_zhu_sao:'.$return.",time:".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
            exit;
        }

        // 判断是否成功
        $receive = $this->is_success($returnText);    // 1代表支付宝支付
        if($receive){
            echo 'success';
        }else{
            echo 'error';
        }
    }

    /**
     * 微信回调
     */
    public function weixin_notify(){
//        file_put_contents(__DIR__."/"."zhusao_weixin_notify.txt",'rece:'.file_get_contents('php://input').",time:".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

        $returnText = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
        // 验签
        $return = $this->check_sign($returnText);
        if(!$return) {
            echo 'error';
//            file_put_contents(__DIR__."/"."check_sign_fourth.txt",'weixin_zhusao:'.$return.",time:".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
            exit;
        }

        // 判断是否成功
        $receive = $this->is_success($returnText);    // 2代表微信支付
        if($receive){
            echo 'success';
        }else{
            echo 'error';
        }
    }

    /**
     * 判断第四方支付返回的值是否是支付成功的回调
     * @param $returnText  ，第四方支付返回的整个数据
     * @return bool
     */
    public function is_success($returnText){
        $minsheng_post_no = $returnText['d']['oo'];
        if($returnText == null || $minsheng_post_no == null){
            return false;
        }
        $f = $returnText['f'];
        $m = $returnText['m'];
        if($f == 1 && $m == "支付成功!"){
            // 成功
            $where['minsheng_post_no'] = $minsheng_post_no;
            $save['pay_time'] = time();
            $save['order_status'] = 3;
            $save['pay_type'] = 5;
            $save['minsheng_trade_no'] = $returnText['d']['go'];
            $res = order()->where($where)->save($save);
            if($res === false){
                return false;
            }

            // 删除第四方支付二维码
            delQrcode($minsheng_post_no,1);

            // 售罄处理
            $order_sn = order()->where($where)->getField('order_sn');
            $S_SellOut = new ServiceSellOut();
            $S_SellOut->sellOutDeal($order_sn);

            // 取餐柜推送
            $S_TakeMeal = new ServiceTakeMeal();
            $S_TakeMeal->takeMealPush($order_sn,1,array(1,3));
            return true;
        }else{
            return false;
        }
    }

    /**
     * 公共验签方法
     * @param $returnText  ，第四方支付回调的整个数据
     * @return bool  返回值
     */
    public function check_sign($returnText){
        $d = $returnText['d'];
        ksort($d); //自然排序
        $signStr = '';
        $i = 0;
        foreach($d as $key=>$val){
            if($i == 0){
                $signStr .= $key.'='.$val;
            }else{
                $signStr .= '&'.$key.'='.$val;
            }
            ++$i;
        }
        // 通过回调的民生的订单号定位到订单所属的店铺，进而查出该店铺对应的md5_key值
        $minsheng_post_no = $returnText['d']['oo'];
        if($minsheng_post_no == null){
            return false;
        }

        $restaurant_id = order()->where(array('minsheng_post_no'=>$minsheng_post_no))->getField('restaurant_id');
        $MD5_KEY = D('fourth_md5_key')->where(array('restaurant_id'=>$restaurant_id))->getField('md5_key');
        if($MD5_KEY == null){
            return false;
        }

        $signStr = $signStr . '&key=' . $MD5_KEY;
        $signVerify = strtoupper(md5($signStr));

        if($signVerify == $returnText['sign']) {
            return true;
        } else{
            return false;
        }
    }


    /**
     *  进行RSA加密
     * @param $json ，json参数值
     * @param $business_no  ，商户号
     * @param $public_key  ，公钥
     * @return string   返回RSA加密结果
     */
    function rsaEncode($json,$business_no,$public_key){
        $pu_key = openssl_pkey_get_public($public_key);//这个函数可用来判断公钥是否是可用的 ，可用返回资源id Resource id
        $encrypted = '';
        $len = strlen($json);
        if($len % 117 != 0){//分段数
            $l = ($len-$len % 117)/117+1;
        }else{
            $l = $len/117;
        }
        for($i=0;$i<$l;++$i){//分段加密
            $tmp = '';
            openssl_public_encrypt(substr($json,$i*117,117),$tmp,$pu_key);//公钥加密
            $tmp = base64_encode($tmp);
            $encrypted .= $tmp;
        }
        return urlencode($encrypted.$business_no);
    }

    /**
     * @param $url  ，地址
     * @param $para  ，参数
     * @return mixed|string
     */
    function getHttpResponsePOST($url,  $para) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);//
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
        $responseText = curl_exec($curl);
        $curl_errno = curl_errno($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);
        if($curl_errno>0){
            return '{"f":"0","m":"'.$curl_error.'"}';
        }else{
            return $responseText;
        }
    }

    /**
     * 生成第四方支付二维码图片，参数为第四方支付返回的的二维码地址，type为区分微信跟支付宝的图标
     */
    public function create_qrc_img()
    {
        $code_url_before = urldecode(I('get.code_url'));
        $order_sn = I('get.order_sn');
        if($code_url_before == null){
            $return['code'] = 0;
            $return['msg'] = '缺乏code_url参数';
            exit(json_encode($return));
        }
        $code_url = str_replace(array("@@",'$$'),array('/','?'),$code_url_before);

        $type = I('get.type');
        if($type == null){
            $return['code'] = 0;
            $return['msg'] = '缺乏type参数';
            exit(json_encode($return));
        }
        if($type == 1){
            // 支付宝
            $logo = 'alicode.png';//准备好的logo图片;
//            $qr_code = 'fourth_ali_code.png';
            $qr_code = 'img/fourth/ali'.$order_sn.'.png';
        }else{
            $logo = 'wechat.png';//准备好的logo图片;
//            $qr_code = 'fourth_weixin_code.png';
            $qr_code = 'img/fourth/wx'.$order_sn.'.png';
        }

        vendor("phpqrcode.phpqrcode");
        $url = $code_url;
        $errorCorrectionLevel = 'M';//容错级别
        $matrixPointSize = 6;//生成图片大小
        \QRcode::png($url,$qr_code,$errorCorrectionLevel, $matrixPointSize,2);
        //QRcode::png($url);
        $QR = $qr_code;//已经生成的原始二维码图

        if ($logo !== FALSE){
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        ob_clean();
        Header("Content-type: image/png");
        ImagePng($QR);
        imagedestroy($QR);
    }
}
