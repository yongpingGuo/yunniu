<?php
namespace Api\Controller;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\WxpayMicropay2\MicroPay_1;
use data\service\SellOut as ServiceSellOut;
class VipController extends BaseController
{
    /**
     *  获取店铺会员组，或者获取代理会员组
     *  device_code
     *  mode  1代理，2店铺
     */
    public function restaurantVipGroup()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $mode = I('post.mode');
            // 双模式
            if($mode == 1){
                // 代理
                $where['business_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            }else{
                // 店铺
                $where['restaurant_id'] = session("restaurant_id");
            }
            // 店铺
//            $where['restaurant_id'] = session("restaurant_id");
            $restaurantGroupInfo = M("vip_group")->where($where)->field('group_id,group_name')->select();
            $defaulGroup = array('group_id'=>0,'group_name'=>'默认会员组');
            array_unshift($restaurantGroupInfo,$defaulGroup);
            $returnData['code'] = 1;
            $returnData['msg'] = "获取数据成功";
            $returnData['restaurantGroupInfo'] = $restaurantGroupInfo;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  新增会员
     *  device_code
     *  mode  1代理，2店铺
     */
    public function addVip()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $mode = I('post.mode');
            $add['restaurant_or_business'] = $mode; // 代理还是店铺会员  1代理，2店铺
            if($mode == 1){
                // 代理
                $add['business_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            }else{
                // 店铺
                $add['restaurant_id'] = session("restaurant_id");
            }
            $add['phone'] = I('post.phone');       // 手机号
            // 检测手机号是否被注册过
            $ifReg = M('vip')->where($add)->find();
            if($ifReg){
                $returnData['code'] = 0;
                $returnData['msg'] = "此手机号码已被注册过";
                exit(json_encode($returnData));
            }

            $add['username'] = I('post.username'); // 用户名
            $add['birthday'] = I('post.birthday');       // 生日，这种形式：2016/3/17
            $add['password'] = I('post.password'); // 支付密码
            $year = explode("/",$add['birthday'])[0];
            $add['age'] =  date("Y")-$year; // 年龄
            $add['sex'] =  I('post.sex'); // 性别，1男，2女
            $add['group_id'] =  I('post.group_id'); // 会员ID，默认会员则为0
            $res = M('vip')->add($add);
            if($res){
                $returnData['code'] = 1;
                $returnData['msg'] = "新增会员成功";
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "新增会员失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  编辑会员
     *  device_code
     *  mode  1代理，2店铺
     */
    public function editVip()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $id = I('post.id');
            if(!is_numeric($id)){
                $returnData['code'] = 0;
                $returnData['msg'] = "会员id不合法";
                exit(json_encode($returnData));
            }
            $edit['phone'] = I('post.phone');       // 手机号
            $edit['username'] = I('post.username'); // 用户名
            $edit['birthday'] = I('post.birthday');       // 生日，这种形式：2016/3/17
            $edit['password'] = I('post.password'); // 支付密码
            $year = explode("/",$edit['birthday'])[0];
            $edit['age'] =  date("Y")-$year; // 年龄
            $edit['sex'] =  I('post.sex'); // 性别，1男，2女
            $edit['group_id'] =  I('post.group_id'); // 会员ID，默认会员则为0
            $res = M('vip')->where(array('id'=>$id))->save($edit);
            if($res !== false){
                $returnData['code'] = 1;
                $returnData['msg'] = "编辑会员成功";
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "编辑会员失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     *  消费记录
     *  device_code
     *  mode  1代理，2店铺
     */
    public function consumptionRecord()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $mode = I('post.mode');
            $add['restaurant_or_business'] = $mode; // 代理还是店铺会员  1代理，2店铺
            if($mode == 1){
                // 代理
                $add['business_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            }else{
                // 店铺
                $add['restaurant_id'] = session("restaurant_id");
            }
            // 根据日期、手机号查询会员消费记录
            $startTime = I('post.startTime');
            $endTime = I('post.endTime');
            $vip_id = I('post.vip_id');
            // 预充值
            $where_prepaid['vip_id'] = $vip_id;
            $where_prepaid['order_status'] = 1;
            $where_prepaid['add_time'] = array('between',array($startTime,$endTime));
            $prepaidRecord = M('prepaid_order')->where($where_prepaid)->field('total_amount,benefit,finall_remainder as remainder,add_time')->order('add_time desc')->select();
            foreach ($prepaidRecord as $key=>$val){
                $prepaidRecord[$key]['total_amount'] = '+'.$prepaidRecord[$key]['total_amount'];
                $prepaidRecord[$key]['benefit'] = '+'.$prepaidRecord[$key]['benefit'];
            }
            // 消费
            $orderListSql = "SELECT add_time,total_amount,score,remainder,summary_score FROM `tabName1` WHERE `vip_id` = $vip_id AND
                            `pay_type` = 4 AND `order_status` IN ('3','11','12') AND `add_time` BETWEEN $startTime AND $endTime ORDER BY add_time desc ";
            // 满足条件的分表订单结果集
            $consumptionRecord = unionSelect2($startTime,$endTime,$orderListSql);
            foreach ($consumptionRecord as $key=>$val){
                $consumptionRecord[$key]['score'] = '+'.$consumptionRecord[$key]['score'];
                $consumptionRecord[$key]['total_amount'] = '-'.$consumptionRecord[$key]['total_amount'];
            }
            // 礼品
            $where_score['vip_id'] = $vip_id;
            $where_score['order_status'] = 1;
            $where_score['add_time'] = array('between',array($startTime,$endTime));
            $goodsRecord = M('score_goods_order')->where($where_score)->field('add_time,goods_name,score,summary_score')->order('add_time desc')->select();
            foreach ($goodsRecord as $key=>$val){
                $goodsRecord[$key]['score'] = '-'.$goodsRecord[$key]['score'];
            }

            if(empty($prepaidRecord)){
                $prepaidRecord = [];
            }elseif(empty($consumptionRecord)){
                $consumptionRecord = [];
            }elseif(empty($goodsRecord)){
                $goodsRecord = [];
            }
            $data['prepaidRecord'] = $prepaidRecord;
            $data['consumptionRecord'] = $consumptionRecord;
            $data['goodsRecord'] = $goodsRecord;
            $returnData['code'] = 1;
            $returnData['msg'] = "获取数据成功";
            $returnData['info'] = $data;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 生成开卡费用订单
     */
    public function createCardOrder(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $wxOrAli = I('post.wxOrAli'); //　需要微信还是支付宝二维码，１支付宝，２微信，3都需要
            $order_sn = I('post.order_sn');
            $add['order_sn'] = $order_sn;
            $record = M('vipcard_charge')->where($add)->find();
            if($record){
                $returnData['code'] = 2;
                $returnData['msg'] = "数据库中已有对应记录";
                exit(json_encode($returnData));
            }
            $mode = I('post.mode');
            $add['restaurant_or_business'] = $mode; // 代理还是店铺会员  1代理，2店铺
            if($mode == 1){
                // 代理
                $add['restaurant_or_business'] = 1;
                $add['relation_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            }else{
                // 店铺
                $add['restaurant_or_business'] = 2;
                $add['relation_id'] = session("restaurant_id");
            }
            $add['total_amount'] = I('post.total_amount');
            $add['add_time'] = I('post.add_time');
            $add['vip_id'] = I('post.vip_id');
            $res = M('vipcard_charge')->add($add);
            if($res){
                $returnData['code'] = 1;
                $returnData['order_sn'] = $order_sn;
                $returnData['msg'] = "订单同步成功";
                if($wxOrAli == 1){
                    $returnData['ali_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/aliCode/order_sn/".$order_sn."/device_code/".$device_code;
                }elseif($wxOrAli == 2){
                    $returnData['wx_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/wxCode/order_sn/".$order_sn."/device_code/".$device_code;
                }else{
                    $returnData['ali_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/aliCode/order_sn/".$order_sn."/device_code/".$device_code;
                    $returnData['wx_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/wxCode/order_sn/".$order_sn."/device_code/".$device_code;
                    exit(json_encode($returnData));
                }
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "添加订单失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 生成开卡费用微信二维码
     *
     */
    public function wxCode() {
        $outer_no = I('get.order_sn');
        $device_code = I('get.device_code');
        $orderModel = M('vipcard_charge');
        $o_condition['order_sn'] = $outer_no;
        $rel = $orderModel->where($o_condition)
            ->field("total_amount,order_sn,order_status,relation_id,restaurant_or_business")
            ->find();
        if($rel['restaurant_or_business'] == 2){
            // 读店铺的配置
            $configModel = M('config');
            $condition['config_type'] = "wxpay";
            $condition['restaurant_id'] = $rel['relation_id'];
            session('restaurant_id',$rel['relation_id']);
            $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
            $wxpay_c = dealConfigKeyForValue($wxpay_config);
        }else{
            // 读代理的配置
            $condition['business_id'] = $rel['relation_id'];
            $wxpay_config = M('wx_prepaid_config')->where($condition)->field("config_name,config_value")->select();
            $wxpay_c = dealConfigKeyForValue($wxpay_config);
            // 用于区别读取代理的配置
            session("wx_prepaid_flag","flag");
            session('business_id',$rel['relation_id']);
        }
        vendor("weixinjsdk.WxPayPubHelper.WxPayPubHelper");
        // 避免两台机器同时下单
        $order_num = $orderModel->where($o_condition)->count();
        if($order_num>1){
            echo '此笔订单重复，请重新点餐';
            exit;
        }
        if($rel['order_status'] == 1){
            exit;
        }
        $price = $rel['total_amount']*100;
        $restaurant_name = M("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
        $body = $restaurant_name;
        if (empty($outer_no) || empty($price)) {
            return ['code' => 'ERROR', 'info' => '参数错误!'];
        }

        if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
            $pay = & load_wechat('Pay');
            $code_url = $pay->getPrepayId("",$body, $outer_no, $price, U(C('HOST_NAME')."/index.php/Home/VipCardNotify/wxNotify", null, null, TRUE), 'NATIVE',$device_code);
            if ($code_url === FALSE) {
                var_dump(['code' => 'ERROR', 'info' => '创建预支付码失败，' . $pay->errMsg]);
                exit;
            }
        }else{
            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body",$restaurant_name);//商品描述
            //自定义订单号，此处仅作举例
            $out_trade_no = $outer_no;
            $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee",$price);//总金额
            // 二维码有效期
            $begin_time = date("YmdHis",time());
            $end_time = date("YmdHis",time()+135);
            $unifiedOrder->setParameter("time_start",$begin_time);
            $unifiedOrder->setParameter("time_expire",$end_time);
            /*if($vip_id){
                $unifiedOrder->setParameter("attach",$vip_id);//会员id
            }*/
            $unifiedOrder->setParameter("notify_url",C('HOST_NAME')."/index.php/Home/VipCardNotify/wxNotify");//通知地址
            $unifiedOrder->setParameter("trade_type","NATIVE");
            //非必填参数，商户可根据实际情况选填
            $unifiedOrder->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//注：是主户代理申请的 这里的子商户的商户号
            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();
            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
            }
            elseif($unifiedOrderResult["result_code"] == "FAIL")
            {
                //商户自行增加处理流程
                echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            }
            elseif($unifiedOrderResult["code_url"] != NULL)
            {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
        }
        error_reporting(E_ERROR);
        vendor("phpqrcode.phpqrcode");
        $url = urldecode($code_url);
        $errorCorrectionLevel = 'M';//容错级别
        $matrixPointSize = 6;//生成图片大小
        $wx_img = 'img/vipCard/wx'.$outer_no.'.png';
        \QRcode::png($url,$wx_img,$errorCorrectionLevel, $matrixPointSize,2);

        $logo = 'wechat.png';//准备好的logo图片
        $QR = $wx_img;//已经生成的原始二维码图

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

    /**
     * 生成开卡费用支付宝二维码
     *
     */
    function aliCode(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $out_trade_no = I('get.order_sn');
            @unlink("qrcode.png");
            Vendor('alipayf2f.f2fpay.service.AlipayTradeService');
            Vendor('alipayf2f.f2fpay.model.builder.AlipayTradePrecreateContentBuilder');
            $alipay_config = $this->config();

//        $out_trade_no = I('get.order_sn');
            if (!empty($out_trade_no)&& trim($out_trade_no)!=""){
                // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
                // 需保证商户系统端不能重复，建议通过数据库sequence生成，
                //$outTradeNo = "qrpay".date('Ymdhis').mt_rand(100,1000);
                $orderModel = M('vipcard_charge');
                $o_condition['order_sn'] = $out_trade_no;
                $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status,relation_id,restaurant_or_business")->find();
                // 避免两台机器同时下单
                $order_num = $orderModel->where($o_condition)->count();
                if($order_num>1){
                    echo '此笔订单重复，请重新点餐';
                    exit;
                }
                if($rel['order_status'] == 1){
                    exit;
                }
                $price = $rel['total_amount'];
                if($price < 0.01){
                    $price = 0.01;
                }
                $outTradeNo = $out_trade_no;
                $restaurant_name = M("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
                // (必填) 订单标题，粗略描述用户的支付目的。如“XX品牌XXX门店消费”
                $subject = $restaurant_name;
                // (必填) 订单总金额，单位为元，不能超过1亿元
                // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
                $totalAmount = $price;
                // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，系统商开发使用,详情请咨询支付宝技术支持
                $providerId = "2088621244519152"; //系统商pid,作为系统商返佣数据提取的依据
                $extendParams = new \ExtendParams();
                $extendParams->setSysServiceProviderId($providerId);
                $extendParamsArr = $extendParams->getExtendParams();
                // 支付超时，线下扫码交易定义为5分钟
                $timeExpress = "2m";

                // app_auth_token要区分店铺和代理的
                if($rel['restaurant_or_business'] == 2){
                    // 读店铺的配置
                    //第三方应用授权令牌,商户授权系统商开发模式下使用
                    $oti_data['restaurant_id'] = $rel['relation_id'];
                }else{
                    $oti_data['business_id'] = $rel['relation_id'];
                }
                $aat_rel = M("restaurant_other_info")->where($oti_data)->find();
                $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写

                //第三方应用授权令牌,商户授权系统商开发模式下使用
                /*$restaurant_other_info = M("restaurant_other_info");
                $restaurant_id = $rel['restaurant_id'];
                $oti_data['restaurant_id'] = $restaurant_id;
                $aat_rel = $restaurant_other_info->where($oti_data)->find();
                $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写*/
                if(!$appAuthToken){
                    exit;
                }
                // 创建请求builder，设置请求参数
                $qrPayRequestBuilder = new \AlipayTradePrecreateContentBuilder();
                $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
                $qrPayRequestBuilder->setTotalAmount($totalAmount);
                $qrPayRequestBuilder->setTimeExpress($timeExpress);
                $qrPayRequestBuilder->setSubject($subject);
                $qrPayRequestBuilder->setBody($body);
                $qrPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
                $qrPayRequestBuilder->setExtendParams($extendParamsArr);
                $qrPayRequestBuilder->setGoodsDetailList($goodsDetailList);
//			$qrPayRequestBuilder->setStoreId("hz008");
//			$qrPayRequestBuilder->setOperatorId($operatorId);
//			$qrPayRequestBuilder->setAlipayStoreId($alipayStoreId);
                $qrPayRequestBuilder->setAppAuthToken($appAuthToken);
                //公用回传参数
//            $extra_common_param  = cookie("device_code") ? :I("get.device_code");
                $config = array (
                    //支付宝公钥
                    'alipay_public_key' => $alipay_config['alipay_public_key'],
                    //商户私钥
                    'merchant_private_key' => $alipay_config['merchant_private_key'],
                    //编码格式
                    'charset' => $alipay_config['charset'],

                    //支付宝网关
                    'gatewayUrl' =>  $alipay_config['gatewayUrl'],

                    //应用ID
                    'app_id' => $alipay_config['app_id'],

                    //异步通知地址,只有扫码支付预下单可用
                    'notify_url' => C('HOST_NAME')."/index.php/Home/VipCardNotify/aliNotify",

                    //最大查询重试次数
                    'MaxQueryRetry' => $alipay_config['MaxQueryRetry'],

                    //查询间隔
                    'QueryDuration' => $alipay_config['QueryDuration'],

                );

                // 调用qrPay方法获取当面付应答
                $qrPay = new \AlipayTradeService($config);
                $qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
                $response = $qrPayResult->getResponse();
                $code_addr = $response->qr_code;
                Vendor("phpqrcode.phpqrcode");
                $errorCorrectionLevel = 'M';//容错级别
                $matrixPointSize = 6;//生成图片大小
                $ali_img = 'img/vipCard/ali'.$out_trade_no.'.png';
//            \QRcode::png($code_addr,'qrcode.png', $errorCorrectionLevel, $matrixPointSize,2);
                \QRcode::png($code_addr,$ali_img, $errorCorrectionLevel, $matrixPointSize,2);
                $logo = 'alicode.png';//准备好的logo图片
//            $QR = 'qrcode.png';//已经生成的原始二维码图
                $QR = $ali_img;//已经生成的原始二维码图

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
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 阿里支付配置值
    public function config()
    {
        $alipay_config = array();
        //$alipay_config['partner'] = '2088521274983214';
        $alipay_config['alipay_public_key'] = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
        $alipay_config['merchant_private_key'] = "MIICXQIBAAKBgQCrPLze9s9rl23JubwCkh0y5TXuttAhHE98y9y/UTWhlnKaQ4x3XB9QO/vP6xZOpHC3P7u3dpSDSgzCtzeZbUONBERAMxumI/cNfw/ylu3NA6jpQk8OJeoEOqEohZku/qq8mReR6fVIAoXPHEFJXlyL41Ny97n1wCLal0fuHWHobwIDAQABAoGARFQFLZcgp1cSeQdDLWdufUuXHL0YCc5JLYwPdswJ8YOeEU5Y85vv5s04qvusuA7H52doGUoY8taOhvgjGHbQGAL1eJsAIxImiLQfqgEeeJmX2n0/gnX9RIA77eKVZVO+JbTCDLTzf4uCVb6TwTauOaVzt3ZGn2ZbP9Vfq6Lc02kCQQDV3LtM8XQ+r+uOwpfvpnUOrK6ryFRSU+7G7RLhA8hIsq9A7wc1T2oEUzpsmERozGc/qeDBru9NlcyThe1kCv97AkEAzPn9rMNMgol8Yqg8mjcRFPFhqneTLGhBWiEs4zF2ju8yvYxtYv5MgRntygwb1SL4OnkJYFeAm7zurs0kmLeOnQJBAJOSsDBlAQjszcgCIWO+YlIQ+KsTHpR81GyyVO+uc3suyd4t0rSHqyl24P7kh3glbC2zJKOh+gF4l+VIako5iJcCQGR+kEuaeLFrPKuV9hhZtStCaPLNqz9TYe8RYtOEla7gQU1DQwIM0W9eSgIMS70EZxUr8FfmrqwsRg03kKC7JdUCQQCNXOkX/UJS0bmIHAmIl17YxgXywxaPEI12bt7QWduKEkUqlDRQgrlPtrwWddO1iZOM/+PjDkvU4cKrIg65mMS1";
        $alipay_config['charset'] = "UTF-8";
        $alipay_config['gatewayUrl'] = 'https://openapi.alipay.com/gateway.do';
        $alipay_config['app_id']	= "2017022305833230";
//        $alipay_config['notify_url'] = 'http://shop.founpad.com/index.php/home/AlipayDirect/notify';
        $alipay_config['notify_url'] = "http://".$_SERVER["HTTP_HOST"].'/index.php/home/VipCardNotify/notify';
        $alipay_config['MaxQueryRetry'] = '10';
        $alipay_config['QueryDuration'] = '3';
        //$alipay_config['transport'] = 'https';
        $alipay_config['cacert'] = getcwd().'cacert.pem';
        $alipay_config['sign_type'] = 'RSA';
        return $alipay_config;
    }

    /**
     * 开卡费用现金
     */
    public function cardCash(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');
            $add['order_sn'] = $order_sn;
            $record = M('vipcard_charge')->where($add)->find();
            if($record){
                // 修改订单状态
                $save['order_status'] = 1;
                $save['pay_type'] = 0;  // 现金
                $save['pay_time'] = time();
                $res = M('vipcard_charge')->where($add)->save($save);
                if($res === false){
                    $returnData['code'] = 0;
                    $returnData['msg'] = "现金支付失败";
                    exit(json_encode($returnData));
                }else{
                    $returnData['code'] = 1;
                    $returnData['msg'] = "现金支付成功";
                    exit(json_encode($returnData));
                }
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "没有对应的订单信息";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }
    
    /**
     * 获取会员充值信息
     */
    public function vipPrepaidInfo(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $mode = I('post.mode'); //　需要代理还是店铺的会员充值信息，1代理，2店铺
            if($mode == 1){
                // 代理
                $where = array(
                    'business_id'=>M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id'),
                    'type'=>1
                );
            }else{
                // 店铺
                $where = array(
                    'restaurant_id'=>session('restaurant_id'),
                    'type'=>1
                );
            }
            $prepaidInfo = M('all_benefit')->where($where)->field('id,account,benefit')->select();
            if(empty($prepaidInfo)){
                $prepaidInfo = [];
            }
            $returnData['code'] = 1;
            $returnData['info'] = $prepaidInfo;
            $returnData['msg'] = "获取预充值信息成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 生成会员预充值信息二维码
     */
    public function createPrepaidOrder(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');
            $condition2['order_sn'] = $order_sn; //订单号
//            $condition2['vip_id'] = I('post.vip_id');  //会员ID
            $record = M('prepaid_order')->where($condition2)->find();
            $wxOrAli = I('post.wxOrAli'); // 需要微信还是支付宝，1支付宝，2微信
            if($record){
                $returnData['code'] = 2;
                $returnData['msg'] = "数据库中已有对应记录";
                exit(json_encode($returnData));
            }

            $condition2['add_time'] = I('post.add_time'); //下单时间
            $condition2['total_amount'] = I('post.account');  //订单总价（客户需要付的钱）
            $condition2['restaurant_or_busines'] = I('post.mode');  //店铺还是代理
            if(I('post.mode') == 1){
                // 代理
                $condition2['restaurant_or_business'] = 1;
                $condition2['business_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            }else{
                $condition2['restaurant_or_business'] = 2;
                $condition2['restaurant_id'] = session('restaurant_id');
            }

            // 相关优惠信息的记录
            $condition2['origin_remainder'] = M('vip')->where(array('id'=>I('post.vip_id')))->getField('remainder');  // 原有会员余额
            $condition2['relation_rule_id'] = I('post.relation_rule_id');   // 关联的优惠规则id
            $condition2['account'] = I('post.account');   // 充多少
            $condition2['benefit'] = I('post.benefit');   // 送多少
            $condition2['finall_benefit'] = I('post.account')+I('post.benefit');  // 最终充值加优惠总共多少
            $condition2['vip_id'] = I('post.vip_id');  // 会员ID

            $result = M('prepaid_order')->data($condition2)->add();//增加一条订单

            if($result){
                $returnData['code'] = 1;
                $returnData['order_sn'] = $order_sn;
                $returnData['msg'] = "订单同步成功";
                if($wxOrAli == 1){
                    $returnData['ali_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/aliPrepaid/order_sn/".$order_sn."/device_code/".$device_code;
                }elseif($wxOrAli == 2){
                    $returnData['wx_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/wxPrepaid/order_sn/".$order_sn."/device_code/".$device_code;
                }else{
                    $returnData['ali_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/aliPrepaid/order_sn/".$order_sn."/device_code/".$device_code;
                    $returnData['wx_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/Api/Vip/wxPrepaid/order_sn/".$order_sn."/device_code/".$device_code;
                }
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "订单同步失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 生成预充值支付宝二维码
     */
    public function aliPrepaid(){
        $device_code = I("get.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $out_trade_no = I('get.order_sn');
            @unlink("qrcode.png");
            Vendor('alipayf2f.f2fpay.service.AlipayTradeService');
            Vendor('alipayf2f.f2fpay.model.builder.AlipayTradePrecreateContentBuilder');
            $alipay_config = $this->config();

            if (!empty($out_trade_no)&& trim($out_trade_no)!=""){
                // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
                // 需保证商户系统端不能重复，建议通过数据库sequence生成，
                //$outTradeNo = "qrpay".date('Ymdhis').mt_rand(100,1000);
                $orderModel = M('prepaid_order');
                $o_condition['order_sn'] = $out_trade_no;
                $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status,restaurant_id,business_id,restaurant_or_business")->find();
                // 避免两台机器同时下单
                $order_num = $orderModel->where($o_condition)->count();
                if($order_num>1){
                    echo '此笔订单重复，请重新点餐';
                    exit;
                }
                if($rel['order_status'] == 1){
                    exit;
                }
                $price = $rel['total_amount'];
                if($price < 0.01){
                    $price = 0.01;
                }
                $outTradeNo = $out_trade_no;
                $restaurant_name = M("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
                // (必填) 订单标题，粗略描述用户的支付目的。如“XX品牌XXX门店消费”
                $subject = $restaurant_name;
                // (必填) 订单总金额，单位为元，不能超过1亿元
                // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
                $totalAmount = $price;
                // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，系统商开发使用,详情请咨询支付宝技术支持
                $providerId = "2088621244519152"; //系统商pid,作为系统商返佣数据提取的依据
                $extendParams = new \ExtendParams();
                $extendParams->setSysServiceProviderId($providerId);
                $extendParamsArr = $extendParams->getExtendParams();
                // 支付超时，线下扫码交易定义为5分钟
                $timeExpress = "2m";

                // app_auth_token要区分店铺和代理的
                if($rel['restaurant_or_business'] == 2){
                    // 读店铺的配置
                    //第三方应用授权令牌,商户授权系统商开发模式下使用
                    $oti_data['restaurant_id'] = $rel['restaurant_id'];
                    $aat_rel = M("restaurant_other_info")->where($oti_data)->find();
                    $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写
                }else{
                    $oti_data['business_id'] = $rel['business_id'];
                    $aat_rel = M("restaurant_other_info")->where($oti_data)->find();
                    $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写
                }

                //第三方应用授权令牌,商户授权系统商开发模式下使用
                /*$restaurant_other_info = M("restaurant_other_info");
                $restaurant_id = $rel['restaurant_id'];
                $oti_data['restaurant_id'] = $restaurant_id;
                $aat_rel = $restaurant_other_info->where($oti_data)->find();
                $appAuthToken = $aat_rel['app_auth_token'];//根据真实值填写*/
                if(!$appAuthToken){
                    exit;
                }
                // 创建请求builder，设置请求参数
                $qrPayRequestBuilder = new \AlipayTradePrecreateContentBuilder();
                $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
                $qrPayRequestBuilder->setTotalAmount($totalAmount);
                $qrPayRequestBuilder->setTimeExpress($timeExpress);
                $qrPayRequestBuilder->setSubject($subject);
                $qrPayRequestBuilder->setBody($body);
                $qrPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
                $qrPayRequestBuilder->setExtendParams($extendParamsArr);
                $qrPayRequestBuilder->setGoodsDetailList($goodsDetailList);
//			$qrPayRequestBuilder->setStoreId("hz008");
//			$qrPayRequestBuilder->setOperatorId($operatorId);
//			$qrPayRequestBuilder->setAlipayStoreId($alipayStoreId);
                $qrPayRequestBuilder->setAppAuthToken($appAuthToken);
                //公用回传参数
//            $extra_common_param  = cookie("device_code") ? :I("get.device_code");
                $config = array (
                    //支付宝公钥
                    'alipay_public_key' => $alipay_config['alipay_public_key'],
                    //商户私钥
                    'merchant_private_key' => $alipay_config['merchant_private_key'],
                    //编码格式
                    'charset' => $alipay_config['charset'],

                    //支付宝网关
                    'gatewayUrl' =>  $alipay_config['gatewayUrl'],

                    //应用ID
                    'app_id' => $alipay_config['app_id'],

                    //异步通知地址,只有扫码支付预下单可用
                    'notify_url' => C('HOST_NAME')."/index.php/Home/VipCardNotify/prepaidAliNotify",

                    //最大查询重试次数
                    'MaxQueryRetry' => $alipay_config['MaxQueryRetry'],

                    //查询间隔
                    'QueryDuration' => $alipay_config['QueryDuration'],

                );

                // 调用qrPay方法获取当面付应答
                $qrPay = new \AlipayTradeService($config);
                $qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
                $response = $qrPayResult->getResponse();
                $code_addr = $response->qr_code;
                Vendor("phpqrcode.phpqrcode");
                $errorCorrectionLevel = 'M';//容错级别
                $matrixPointSize = 6;//生成图片大小
                $ali_img = 'img/prepaid/ali'.$out_trade_no.'.png';
//            \QRcode::png($code_addr,'qrcode.png', $errorCorrectionLevel, $matrixPointSize,2);
                \QRcode::png($code_addr,$ali_img, $errorCorrectionLevel, $matrixPointSize,2);
                $logo = 'alicode.png';//准备好的logo图片
//            $QR = 'qrcode.png';//已经生成的原始二维码图
                $QR = $ali_img;//已经生成的原始二维码图

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
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 生成预充值微信二维码
     */
    public function wxPrepaid() {
        $outer_no = I("get.order_sn");
        $device_code = I("get.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderModel = M('prepaid_order');
            $o_condition['order_sn'] = $outer_no;
            $rel = $orderModel->where($o_condition)
                ->field("total_amount,order_sn,order_status,restaurant_id,business_id,restaurant_or_business")
                ->find();
            if($rel['restaurant_or_business'] == 2){
                // 读店铺的配置
                $configModel = M('config');
                $condition['config_type'] = "wxpay";
                $condition['restaurant_id'] = $rel['restaurant_id'];
                session('restaurant_id',$rel['restaurant_id']);
                $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);
            }else{
                // 读代理的配置
                $condition['business_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
                $wxpay_config = M('wx_prepaid_config')->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);
                // 用于区别读取代理的配置
                session("wx_prepaid_flag","flag");
                session('business_id',$rel['business_id']);
            }
            vendor("weixinjsdk.WxPayPubHelper.WxPayPubHelper");
            // 避免两台机器同时下单
            $order_num = $orderModel->where($o_condition)->count();
            if($order_num>1){
                echo '此笔订单重复，请重新点餐';
                exit;
            }
            if($rel['order_status'] == 1){
                exit;
            }
            $price = $rel['total_amount']*100;
            $restaurant_name = M("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
            $body = $restaurant_name;
            if (empty($outer_no) || empty($price)) {
                return ['code' => 'ERROR', 'info' => '参数错误!'];
            }

            if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
                $pay = & load_wechat('Pay');
                $code_url = $pay->getPrepayId("",$body, $outer_no, $price, U(C('HOST_NAME')."/index.php/Home/VipCardNotify/prepaidWxNotify", null, null, TRUE), 'NATIVE',$device_code);
                if ($code_url === FALSE) {
                    var_dump(['code' => 'ERROR', 'info' => '创建预支付码失败，' . $pay->errMsg]);
                    exit;
                }
            }else{
                //使用统一支付接口
                $unifiedOrder = new \UnifiedOrder_pub();
                $unifiedOrder->setParameter("body",$restaurant_name);//商品描述
                //自定义订单号，此处仅作举例
                $out_trade_no = $outer_no;
                $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
                $unifiedOrder->setParameter("total_fee",$price);//总金额
                // 二维码有效期
                $begin_time = date("YmdHis",time());
                $end_time = date("YmdHis",time()+135);
                $unifiedOrder->setParameter("time_start",$begin_time);
                $unifiedOrder->setParameter("time_expire",$end_time);
                /*if($vip_id){
                    $unifiedOrder->setParameter("attach",$vip_id);//会员id
                }*/
                $unifiedOrder->setParameter("notify_url",C('HOST_NAME')."/index.php/Home/VipCardNotify/prepaidWxNotify");//通知地址
                $unifiedOrder->setParameter("trade_type","NATIVE");
                //非必填参数，商户可根据实际情况选填
                $unifiedOrder->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//注：是主户代理申请的 这里的子商户的商户号
                //获取统一支付接口结果
                $unifiedOrderResult = $unifiedOrder->getResult();
                //商户根据实际情况设置相应的处理流程
                if ($unifiedOrderResult["return_code"] == "FAIL")
                {
                    //商户自行增加处理流程
                    echo "通信出错：".$unifiedOrderResult['return_msg']."<br>";
                }
                elseif($unifiedOrderResult["result_code"] == "FAIL")
                {
                    //商户自行增加处理流程
                    echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                    echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
                }
                elseif($unifiedOrderResult["code_url"] != NULL)
                {
                    //从统一支付接口获取到code_url
                    $code_url = $unifiedOrderResult["code_url"];
                    //商户自行增加处理流程
                    //......
                }
            }
            error_reporting(E_ERROR);
            vendor("phpqrcode.phpqrcode");
            $url = urldecode($code_url);
            $errorCorrectionLevel = 'M';//容错级别
            $matrixPointSize = 6;//生成图片大小
            $wx_img = 'img/prepaid/wx'.$outer_no.'.png';
            \QRcode::png($url,$wx_img,$errorCorrectionLevel, $matrixPointSize,2);

            $logo = 'wechat.png';//准备好的logo图片
            $QR = $wx_img;//已经生成的原始二维码图

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
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 预充值现金支付
     */
    public function prepaidCash(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $prepaidModel = M('prepaid_order');
            $order_sn = I('post.order_sn');
            $condition2['order_sn'] = $order_sn; //订单号
            $record = $prepaidModel->where($condition2)->find();
            if($record){
                // 修改订单状态
                $save['order_status'] = 1;
                $save['pay_type'] = 0;  // 现金
                $save['pay_time'] = time();
                $res = $prepaidModel->where($condition2)->save($save);
                if($res === false){
                    $returnData['code'] = 0;
                    $returnData['msg'] = "现金支付失败";
                    exit(json_encode($returnData));
                }else{
                    // 在prepaid_order表更新各种优惠
                    $return = $this->update_benefit_in_order($order_sn);
                    $returnData['code'] = 1;
                    $returnData['msg'] = "现金支付成功";
                    exit(json_encode($returnData));
                }
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "没有对应的订单信息";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 更新获得优惠后的对应的优惠详情
     * @param $order_sn
     * @param $relation_id
     * @param $account
     * @param $benefit
     * @param $finall_benefit
     * @return bool
     */
    public function update_benefit_in_order($order_sn){
        $vipInfo = M('prepaid_order')->where(array('order_sn'=>$order_sn))->field('vip_id,finall_benefit')->find();
        $vip_id = $vipInfo['vip_id'];
        // 查询出该会员当前在数据库有多少余额
        $vip = M("vip");
        $remainder = $vip->where(array("id"=>$vip_id))->getField("remainder");
        $all_money = $vipInfo['finall_benefit'] + $remainder;
        $total['remainder'] = $all_money;
        $res = $vip->where(array("id"=>$vip_id))->save($total);

        $where['order_sn'] = $order_sn;
        $data['finall_remainder'] = $all_money;  // 客户最后的余额
        $rel = M('prepaid_order')->where($where)->save($data);
        if($rel !== false){
            return true;
        }else{
            return false;
        }
    }

    // 轮询获取预充值订单状态
    public function getPrepaidStatus(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            // 服务器订单号
            $order_sn = I("post.order_sn");
            if($order_sn == null){
                $data['code'] = 0;
                $data['pay_type'] = "";
                $data['client_order_sn'] = "";
                $data['server_order_sn'] = "";
                $data['msg'] ='订单号为空';
                exit(json_encode($data));
            }

            $orderModel = M('prepaid_order');
            $o_condition['order_sn'] = $order_sn;
            $order_status = $orderModel->where($o_condition)->field("order_status,pay_type")->find();

            if($order_status['order_status'] == 1){
                $data['code'] = 1;
                $data['pay_type'] = $order_status['pay_type'];
                $data['order_sn'] = $order_sn;
                $data['msg'] ='支付成功';
                exit(json_encode($data));
            }else{
                $data['code'] = 0;
                $data['pay_type'] = "";
                $data['order_sn'] = $order_sn;
                $data['msg'] ='未支付';
                exit(json_encode($data));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 轮询获取开卡订单状态
    public function getVipCardStatus(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            // 服务器订单号
            $order_sn = I("post.order_sn");
            if($order_sn == null){
                $data['code'] = 0;
                $data['pay_type'] = "";
                $data['order_sn'] = $order_sn;
                $data['msg'] ='订单号为空';
                exit(json_encode($data));
            }

            $orderModel = M('vipcard_charge');
            $o_condition['order_sn'] = $order_sn;
            $order_status = $orderModel->where($o_condition)->field("order_status,pay_type")->find();

            if($order_status['order_status'] == 1){
                $data['code'] = 1;
                $data['pay_type'] = $order_status['pay_type'];
                $data['order_sn'] = $order_sn;
                $data['msg'] ='支付成功';
                exit(json_encode($data));
            }else{
                $data['code'] = 0;
                $data['pay_type'] = "";
                $data['order_sn'] = $order_sn;
                $data['msg'] ='未支付';
                exit(json_encode($data));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 获取会员列表
     */
    public function vipList(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $mode = I('post.mode'); //　需要代理还是店铺的会员信息，1代理，2店铺
            if($mode == 1){
                // 代理
                $where = array(
                    'vip.business_id'=>M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id'),
                    'restaurant_or_business'=>1
                );
            }else{
                // 店铺
                $where = array(
                    'vip.restaurant_id'=>session('restaurant_id'),
                    'restaurant_or_business'=>2
                );
            }
            $vipInfo = M('vip')
                ->join('left join vip_group on vip.group_id = vip_group.group_id')
                ->where($where)
                ->field('id,username,phone,vip_group.group_name,sex,birthday,remainder,score,password')
                ->select();
            foreach ($vipInfo as $key =>$val){
                if($val['group_name'] == null){
                    $vipInfo[$key]['group_name'] = '默认会员组';
                }
            }
            if(empty($vipInfo)){
                $vipInfo = [];
            }
            $returnData['code'] = 1;
            $returnData['info'] = $vipInfo;
            $returnData['msg'] = "获取会员信息成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 预充值和开卡扫码枪支付
     * @param $order_sn
     * @param $qr_number
     * @param type 是开卡还是预充值,1开卡，2预充值
     */
    public function saoMa(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I("post.order_sn");
            $qr_number = I("post.tiao_xing_ma");
            $type = I("post.type");

            //调用扫码枪支付接口$order_sn;$qr_number;
            if($type == 1){
                // 开卡
                $url = "http://".$_SERVER["HTTP_HOST"]."/index.php/api/Vip/cardMicroPay";
                $model = M('vipcard_charge');
            }else{
                // 预充值
                $url = "http://".$_SERVER["HTTP_HOST"]."/index.php/api/Vip/prepaidMicroPay";
                $model = M('prepaid_order');
            }
            $post_data = array ("order_sn" => $order_sn,"qr_number" =>$qr_number );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $output = curl_exec($ch);
            curl_close($ch);

            exit(json_encode($output));

            //打印获得的数据
            if($output){
                $returnData['code'] = 1;
                $returnData['order_sn'] = $order_sn;
                $pay_type = $model->where(array("order_sn"=>$order_sn))->getField("pay_type");
                $returnData['pay_type'] = $pay_type;    // 返回支付类型
                $returnData['msg'] = "支付成功";
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "支付失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 开卡费用处理扫描器扫描得到的数据
    public function cardMicroPay(){
        $order_sn = $_POST['order_sn'];
        $orderModel = M('vipcard_charge');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        if($orderInfo['restaurant_or_business'] == 1){
            //　代理
            session("business_id",$orderInfo['relation_id']);
        }else{
            // 店铺
            session("restaurant_id",$orderInfo['relation_id']);
        }

        if($orderInfo['order_status'] == 1){
            exit;
        }
        $auth_code = $_POST['qr_number'];
        $prefix_num = substr($auth_code,0,2);
        if(in_array($prefix_num,C('AL_PAY_PREFIX'))){
            //查询该店铺的支付方式看是否开启支持支付宝支付
            if($orderInfo['restaurant_or_business'] == 1){
                //　代理
                $p_where['business_id'] = session("business_id");
            }else{
                // 店铺
                $p_where['restaurant_id'] = session("restaurant_id");
            }
            $pay_select_model = D('pay_select');
            $p_where['config_name'] = "ali-code";
            $rel = $pay_select_model->where($p_where)->find();
            if($rel){
                $post_data = array ("order_sn" => $order_sn,"qr_number" => $auth_code);
                $ali_out_put = http_post("http://".$_SERVER["HTTP_HOST"].U("Home/SaoMa/card_alipay_barcodePay"),$post_data);
                echo $ali_out_put;
                exit;
            }
        }
        $price = $orderInfo['total_amount']*100;
        if(in_array($prefix_num,C('WX_PAY_PREFIX'))){
            if($orderInfo['restaurant_or_business'] == 2){
                // 读店铺的配置
                $configModel = M('config');
                $condition['config_type'] = "wxpay";
                $condition['restaurant_id'] = $orderInfo['restaurant_id'];
                session('restaurant_id',$orderInfo['restaurant_id']);
                $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);
                $restaurant_name = D("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
            }else{
                // 读代理的配置
                $condition['business_id'] = $orderInfo['business_id'];
                $wxpay_config = M('wx_prepaid_config')->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);
                // 用于区别读取代理的配置
                session("wx_prepaid_flag","flag");
                session('business_id',$orderInfo['business_id']);
                $restaurant_name = D("business")->where(array("business_id"=>session("business_id")))->getField("business_name");
            }
            if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
                require getcwd()."/Application/PayMethod/WxpayMicropay2/lib/WxPay.Data.php";
                if($auth_code){
                    $input = new \WxPayMicroPay();
                    $input->SetAuth_code($auth_code);
                    if(!$restaurant_name){
                        $input->SetBody("方雅餐饮系统");
                    }else{
                        $input->SetBody($restaurant_name);
                    }
                    $input->SetTotal_fee($price);
                    $business_order = \WxPayConfig::$MCHID.'card'.date("YmdHis");
                    $orderModel->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));
                    $input->SetOut_trade_no($business_order);
                    $microPay = new MicroPay_1();
                    $result = $microPay->pay($input);
                    if($result == true){
                        //操作数据库处理订单信息；
                        $o_condition['order_sn'] = $order_sn;
                        $data['order_status'] = 1;
                        $data['pay_type'] = 2;
                        $time = time();
                        $data['pay_time'] = $time;
                        $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                        // 删除开卡费用支付二维码
                        delVipCardQrcode($order_sn,1);
                    }else{
                        echo 0;
                        exit;
                    }
                }
            }else{
                require getcwd()."/Application/PayMethod/WxpayMicropay/lib/WxPay.Data.php";
                $result = false;
                if($auth_code){
                    $input = new \WxPayMicroPay();
                    $input->SetAuth_code($auth_code);
                    if(!$restaurant_name){
                        $input->SetBody("方雅餐饮系统");
                    }else{
                        $input->SetBody($restaurant_name);
                    }
                    $input->SetTotal_fee($price);
                    //　提交的商户订单号跟系统订单号联系起来
                    $business_order = \WxPayConfig::$MCHID.'card'.date("YmdHis");
                    $orderModel->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));
                    $input->SetOut_trade_no($business_order);
                    $input->SetSub_mch_id(\WxPayConfig::$SUB_MCHID);
                    $microPay = new MicroPay();
                    $result = $microPay->pay($input);
                }
                if($result == true){
                    //操作数据库处理订单信息；
                    $o_condition['order_sn'] = $order_sn;
                    $data['order_status'] = 1;
                    $data['pay_type'] = 2;
                    $time = time();
                    $data['pay_time'] = $time;
                    $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                    // 删除开卡费用支付二维码
                    delVipCardQrcode($order_sn,1);
                }else{
                    echo 0;
                    exit;
                }
            }
        }
        if(!in_array($prefix_num,C('WX_PAY_PREFIX')) && in_array($prefix_num,C('AL_PAY_PREFIX'))){
            echo 0;
            exit;
        }
    }

    // 预充值处理扫描器扫描得到的数据
    public function prepaidMicroPay(){
        $order_sn = $_POST['order_sn'];
        $orderModel = M('prepaid_order');
        $ono_condition['order_sn'] = $order_sn;
        $orderInfo = $orderModel->where($ono_condition)->find();
        if($orderInfo['restaurant_or_business'] == 1){
            //　代理
            session("business_id",$orderInfo['business_id']);
        }else{
            // 店铺
            session("restaurant_id",$orderInfo['restaurant_id']);
        }

        if($orderInfo['order_status'] == 1){
            exit;
        }
        $auth_code = $_POST['qr_number'];
        $prefix_num = substr($auth_code,0,2);
        if(in_array($prefix_num,C('AL_PAY_PREFIX'))){
            //查询该店铺的支付方式看是否开启支持支付宝支付
            if($orderInfo['restaurant_or_business'] == 1){
                //　代理
                $p_where['business_id'] = session("business_id");
            }else{
                // 店铺
                $p_where['restaurant_id'] = session("restaurant_id");
            }
            $pay_select_model = D('pay_select');
            $p_where['config_name'] = "ali-code";
            $rel = $pay_select_model->where($p_where)->find();
            if($rel){
                $post_data = array ("order_sn" => $order_sn,"qr_number" => $auth_code);
                $ali_out_put = http_post("http://".$_SERVER["HTTP_HOST"].U("Home/SaoMa/prepaid_alipay_barcodePay"),$post_data);
//                $ali_out_put = http_post("http://".$_SERVER["HTTP_HOST"].U("Home/AlipayDirect/prepaid_alipay_barcodePay"),$post_data);
                // 在prepaid_order表更新各种优惠
                $return = $this->update_benefit_in_order($order_sn);
                echo $ali_out_put;
                exit;
            }
        }
        $price = $orderInfo['total_amount']*100;
        if(in_array($prefix_num,C('WX_PAY_PREFIX'))){
            if($orderInfo['restaurant_or_business'] == 2){
                // 读店铺的配置
                $configModel = M('config');
                $condition['config_type'] = "wxpay";
                $condition['restaurant_id'] = $orderInfo['restaurant_id'];
                session('restaurant_id',$orderInfo['restaurant_id']);
                $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);
                $restaurant_name = D("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");
            }else{
                // 读代理的配置
                $condition['business_id'] = $orderInfo['business_id'];
                $wxpay_config = M('wx_prepaid_config')->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);
                // 用于区别读取代理的配置
                session("wx_prepaid_flag","flag");
                session('business_id',$orderInfo['business_id']);
                $restaurant_name = D("business")->where(array("business_id"=>session("business_id")))->getField("business_name");
            }

            if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
                require getcwd()."/Application/PayMethod/WxpayMicropay2/lib/WxPay.Data.php";
                $result = false;
                if($auth_code){
                    $input = new \WxPayMicroPay();
                    $input->SetAuth_code($auth_code);
                    if(!$restaurant_name){
                        $input->SetBody("方雅餐饮系统");
                    }else{
                        $input->SetBody($restaurant_name);
                    }
                    $input->SetTotal_fee($price);
//                    $business_order = \WxPayConfig::$MCHID.'prepaid'.date("YmdHis");
                    $business_order = $order_sn.'pre';
                    M('prepaid_order')->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));
                    $input->SetOut_trade_no($business_order);
                    $microPay = new MicroPay_1();
                    $result = $microPay->pay($input);
                }
                if($result == true){
                    //操作数据库处理订单信息；
                    $o_condition['order_sn'] = $order_sn;
                    $data['order_status'] = 1;
                    $data['pay_type'] = 2;
                    $time = time();
                    $data['pay_time'] = $time;
                    $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                    // 删除预充值费用支付二维码
                    delPrepaidQrcode($order_sn,1);
                    // 在prepaid_order表更新各种优惠
                    $return = $this->update_benefit_in_order($order_sn);
                    echo 1;
                    exit;
                }else{
                    echo 0;
                    exit;
                }
            }else{
                require getcwd()."/Application/PayMethod/WxpayMicropay/lib/WxPay.Data.php";
                $result = false;
                if($auth_code){
                    $input = new \WxPayMicroPay();
                    $input->SetAuth_code($auth_code);
                    if(!$restaurant_name){
                        $input->SetBody("方雅餐饮系统");
                    }else{
                        $input->SetBody($restaurant_name);
                    }
                    $input->SetTotal_fee($price);
                    //　提交的商户订单号跟系统订单号联系起来
//                    $business_order = \WxPayConfig::$MCHID.'prepaid'.date("YmdHis");
                    $business_order = $order_sn.'pre';
                    $orderModel->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));
                    $input->SetOut_trade_no($business_order);
                    $input->SetSub_mch_id(\WxPayConfig::$SUB_MCHID);
                    file_put_contents(__DIR__."/"."Vip.txt",'sub_mchid：'.\WxPayConfig::$SUB_MCHID.',order_sn:'.$business_order."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
                    $microPay = new MicroPay();
                    $result = $microPay->pay($input);
                }
                if($result == true){
                    //操作数据库处理订单信息；
                    $o_condition['order_sn'] = $order_sn;
                    $data['order_status'] = 1;
                    $data['pay_type'] = 2;
                    $time = time();
                    $data['pay_time'] = $time;
                    $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态
                    // 删除预充值费用支付二维码
                    delPrepaidQrcode($order_sn,1);
                    // 在prepaid_order表更新各种优惠
                    $return = $this->update_benefit_in_order($order_sn);
                    echo 1;
                    exit;
                }else{
                    echo 0;
                    exit;
                }
            }
        }
        if(!in_array($prefix_num,C('WX_PAY_PREFIX')) && in_array($prefix_num,C('AL_PAY_PREFIX'))){
            echo 0;
            exit;
        }
    }

    // 店铺还是代理模式
    public function restaurantOrBusiness(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $where['business_id'] = M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            $type = M("business")->where($where)->getField('vip_mode');
            $returnData['code'] = 1;
            if($type == 0){
                $type = 2;
            }
            $returnData['type'] = $type;
            $returnData['msg'] = "获取信息成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    public function ToXml($returnMsg)
    {
        $xml = "<xml>";
        foreach ($returnMsg as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.= "</xml>";
        return $xml;
    }

    // 手机号+密码进行余额消费
    public function phonePasswordConsump()
    {
        // 参数1：device_code  设备码
        // 参数2：phone  电话号码
        // 参数3：order_sn  订单号
        // 参数4：password  密码
        // 参数5：mode  代理还是店铺  1代理，2店铺
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            // 当前店铺所属代理id
            $now_business_id =  M('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            // 传递过来的服务器的订单号
            $order_num = I("post.order_sn");
            if ($order_num == null) {
                //余额支付返回content
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "订单号为空";
                exit(json_encode($content));
            }
            $order_info = order()->where(array('order_sn'=>$order_num))->find();
            if(empty($order_info)){
                //余额支付返回content
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "该订单号对应的订单信息不存在";
                exit(json_encode($content));
            }
            // 手机号
            $phone = I('post.phone');
            if(!(is_numeric($phone)) || $phone == ''){
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "手机号不合法";
                exit(json_encode($content));
            }
            // 密码
            $password = I('post.password');
            if(empty($password)){
                //余额支付返回content
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "密码不能为空";
                exit(json_encode($content));
            }
            // 代理还是店铺
            $mode = I('post.mode');
            $blc_where['phone'] = $phone;
            $blc_where['restaurant_or_business'] = $mode;
            /*if($mode == 1){
                // 代理
                $blc_where['business_id'] = $now_business_id;
            }else{
                // 店铺
                $blc_where['restaurant_id'] = session('restaurant_id');
            }*/
            // 代理
            $blc_where['business_id'] = $now_business_id;
            $vip_model = M("vip");
            $vip_info = $vip_model
                ->where($blc_where)
                ->field("id,username,remainder,score,total_consume,business_id,restaurant_or_business,restaurant_id,password")
                ->find();
            if(empty($vip_info)){
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "手机号或者密码错误";
                exit(json_encode($content));
            }
            if($vip_info['password'] != $password){
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "手机号或者密码错误";
                exit(json_encode($content));
            }
            $remainder     = $vip_info['remainder'];    // 会员原有余额
            $score         = $vip_info['score'];    // 会员原有积分
            $total_consume = $vip_info['total_consume'];    // 会员原有消费总额
            //获取订单的总额
            $blc_order_where['order_sn'] = $order_num;
            $order_model = order();
            $blc_order_info = $order_model->where($blc_order_where)->find();
            if ($blc_order_info['order_status'] >= 3) {
                //余额支付返回content
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "已经支付过了";
                exit(json_encode($content));
            }
            if ($blc_order_info['total_amount'] > $remainder) {
                //余额支付返回content
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "余额不足，请用其他方式支付";
                exit(json_encode($content));
            } else {
                $order_model->startTrans();
                // 换取积分开始      根据订单号查询店铺id
                $o_where['order_sn'] = $order_num;
                /*if($mode == 1){
                    // 代理
                    $o_condition['business_id'] = $now_business_id;
                }else{
                    $o_condition['restaurant_id'] = session('restaurant_id');
                }*/
                // 代理
                $o_condition['restaurant_id'] = session('restaurant_id');
                $o_condition['type'] = 2;
                $if_open = M("set")->where($o_condition)->getField("if_open");
                // 判断有没有开启
                if ($if_open) {
                    /*if($mode == 1){
                        // 代理
                        $rule_condition['business_id'] = $now_business_id;
                    }else{
                        $rule_condition['restaurant_id'] = session('restaurant_id');
                    }*/
                    // 代理
                    $rule_condition['business_id'] = $now_business_id;
                    // 根据代理id查出积分设置规则
                    $rule_condition['type'] = 2;
                    $rule = M("all_benefit")->where($rule_condition)->find();
                    if ($rule) {
                        // 判断消费额是否大于等于积分设置的金额
                        if ($blc_order_info['total_amount'] >= $rule['account']) {
                            $get_score = floor($blc_order_info['total_amount'] / $rule['account']) * $rule['benefit'];
                        } else {
                            $get_score = 0;
                        }
                    }else{
                        $get_score = 0;
                    }
                }else{
                    $get_score = 0;
                }
                $blc_vip_data['score'] = $score + $get_score;   // 更新会员积分
                $blc_vip_data['total_consume'] = $total_consume + $blc_order_info['total_amount'];   // 更新会员消费总额
                // 换取积分结束
                // 生成订单的时候，就有一个默认的会员id=0，积分也是默认为0，然后来到这里支付完后就更新会员id为当前会员的id，订单积分也做更新
                $blc_order_data["vip_id"] = $vip_info['id'];
                $blc_order_data["score"]  = $get_score;
                $blc_order_data["order_status"] = 3;
                $blc_order_data["pay_time"] = time();
                $blc_order_data["pay_type"] = 4;
                $blc_order_data["remainder"] = $remainder - $blc_order_info['total_amount'];    // 此时的会员余额
                $blc_order_data["summary_score"] = $score + $get_score;    // 此时的会员总分
                $order_save_rel = $order_model->where($blc_order_where)->save($blc_order_data);
                // 更新会员余额
                $blc_vip_data['remainder'] = $remainder - $blc_order_info['total_amount'];
                $save_vip_rel  = $vip_model->where(array('id'=>$vip_info['id']))->save($blc_vip_data);
                if ($order_save_rel !== false && $save_vip_rel !== false) {
                    $order_model->commit();
                    // 售罄处理
                    $S_SellOut = new ServiceSellOut();
                    $S_SellOut->sellOutDeal($order_num);
                    //余额支付返回content
                    $content["code"]     = "1";
                    $content["order_sn"] = $order_num;
                    $content["msg"]      = "支付成功";
                    exit(json_encode($content));
                } else {
                    $order_model->rollback();
                    $content["code"]     = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"]      = "支付失败";
                    exit(json_encode($content));
                }
            }
        }
        else {
            $content["code"]     = "0";
            $content["order_sn"] = "";
            $content["msg"]      = "设备已过期，无权限获取数据";
            exit(json_encode($content));
        }
    }
}
