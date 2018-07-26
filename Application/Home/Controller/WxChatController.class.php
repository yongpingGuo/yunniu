<?php
/**
 * Created by PhpStorm.
 * User: liangbaobin
 * Date: 2016/11/13
 * Time: 18:33
 */
namespace Home\Controller;
use Think\Controller;
use Think\Verify;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\WxpayMicropay2\MicroPay_1;
use PayMethod\Wechat\WechatPay;
use Think\Encrypt;
use data\service\Push as ServicePush;
use data\service\SellOut as ServiceSellOut;

class WxChatController extends Controller
{
    public function index(){
        $this->display();
    }

    /**
     * 支付通知处理
     * @return type
     */
    public function notify() {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            //返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);

            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
//            file_put_contents(LOG_PATH . 'pay_notify.log', var_export($notifyInfo, TRUE));
//            file_put_contents('./wxpay.log', 'aaaaaaa');
            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];

            $order_status = order()->where(array('order_sn'=>$order_sn))->getField('order_status');

            if($order_status == 3){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            }else{
                session("num".$order_sn,1);
                $orderModel = order();
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 3;
                $data['pay_type'] = 2;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                // 删除第三方支付二维码
                delQrcode($order_sn,2);

                // 进行积分换取处理
                $vip_id = $notifyInfo['attach'];
//                file_put_contents(__DIR__."/"."discount_test.txt","会员id：".$vip_id."\r\n",FILE_APPEND);

                // 判断是不是会员
                if($vip_id != 0) {
                    // 判断当前店铺有没有开启积分换取
                    $restaurant_id = $orderModel->where(array("order_sn" => $order_sn))->getField("restaurant_id");
                    // 店铺还是代理
                    $business_id = M('restaurant')->where(array('restaurant_id'=>$restaurant_id))->getField('business_id');
                    $vip_mode = M('business')->where(array('business_id'=>$business_id))->getField('vip_mode');
                    if($vip_mode == 1){
                        // 代理
//                        $score_condition['business_id'] = $business_id;
                        $rule_condition['business_id'] = $business_id;
                    }else{
//                        $score_condition['restaurant_id'] = $restaurant_id;
                        $rule_condition['restaurant_id'] = $restaurant_id;
                    }
                    $score_condition['restaurant_id'] = $restaurant_id;
                    $score_condition['type'] = 2;
                    $if_open = D("set")->where($score_condition)->getField("if_open");
                    // 判断有没有开启
                    if ($if_open) {
                        // 根据代理id查出积分设置规则
//                        $business_id = D("restaurant")->where(array("restaurant_id" => $restaurant_id))->getField("business_id");
//                        $rule_condition['business_id'] = $business_id;
                        $rule_condition['type'] = 2;
                        $rule = M("all_benefit")->where($rule_condition)->find();
                        // 判断消费额是否大于等于积分设置的金额
                        $total_money = $notifyInfo['total_fee']/100;
                        if ($total_money >= $rule['account']) {
                            $get_score = floor($total_money / $rule['account']) * $rule['benefit'];
                            // 获取会员原本的积分
                            $vipInfos = M("vip")->where(array("id" => $vip_id))->field("score,total_consume")->find();
                            $blc_vip_data['score'] = $vipInfos['score'] + $get_score;   // 更新会员积分

                            $blc_vip_data['total_consume'] = $vipInfos['total_consume'] + $total_money;   // 更新会员消费总额

                            $blc_vip_data['id'] = $vip_id;
                            $rel_score = M("vip")->save($blc_vip_data);
                            $orderModel->where($o_condition)->save(array("score"=>$get_score));
                        }
                    }
                }else{
                    $rel_score = 1;
                }

                if($rel !== false && $rel_score !== false){
                    // 售罄处理
                    $S_SellOut = new ServiceSellOut();
                    $S_SellOut->sellOutDeal($order_sn);

//                    file_put_contents('./wxpay2.log', 'bbbbb');
                    //阿里推送
                    //查询推送模式然后进行推送
                    $push = new ServicePush();
                    $restaurant_id = $orderModel->where(array("order_sn" => $order_sn))->getField("restaurant_id");
                    $_SESSION['restaurant_id'] = $restaurant_id;
                    $push_type = $push->pushType();
                    if($push_type == 2){ //核销屏的推送模式
                        $push->pushOneScreen($order_sn);
                    }elseif($push_type == 3){ //取餐柜的推送模式
                        $push->pushOneCupboard($order_sn);
                    }else{
                        //普通模式不用推
                    }

//                    file_put_contents(__DIR__."/"."payLog.txt","订单号：".$order_sn."|"."时间：".date("Y-m-d H:i:s",time())."|支付方式：微信扫码支付"."\r\n",FILE_APPEND);
                    //获取订单信息，判断是否要推送到展示餐牌号展示页面
                    $orderInfo = $orderModel->where($o_condition)->field("table_num,desk_code,restaurant_id")->find();
                    $restaurantModel = D("Restaurant");
                    $rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
                    $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
                    if($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0){
                        $content['tips'] = "下单成功推送showNum";
                        $content['order_sn'] = $order_sn;
                        $contentJson = json_encode($content);
                        $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                        $rel2 = sendMsgToDevice($post_data);

                        //推送到所有分区的叫号屏，核销屏
                        $restaurant_id = $orderInfo['restaurant_id'];
                        pushAllDistrict($restaurant_id,$order_sn);
                    }
                }
//                session($order_sn,true);
                session($order_sn,'order_sn');
            }
        }
    }

    /**
     * 生成预支付码
     * @return type
     */
    public function qrc() {
        $outer_no = I('get.order_sn');
//        $outer_no = "DC0000116112907505000004";

        $orderModel = order();
        $o_condition['order_sn'] = $outer_no;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status,discount,vip_id,restaurant_id")->find();
        session("restaurant_id",$rel['restaurant_id']);

        vendor("weixinjsdk.WxPayPubHelper.WxPayPubHelper");

      /*  $orderModel = order();
        $o_condition['order_sn'] = $outer_no;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,order_status")->find();*/
        if($rel['order_status'] == 3){
            exit;
        }
        $price = $rel['total_amount']*100;

        $restaurant_name = D("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");

//        $body = "方雅餐饮系统";
        $body = $restaurant_name;
        if (empty($outer_no) || empty($price)) {
            return ['code' => 'ERROR', 'info' => '参数错误!'];
        }

        $configModel = D('config');
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session("restaurant_id");
        $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
        $wxpay_c = dealConfigKeyForValue($wxpay_config);

        if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
            $pay = & load_wechat('Pay');
            $device_code = cookie('device_code');
            $code_url = $pay->getPrepayId("",$body, $outer_no, $price, U('WxChat/notify', null, null, TRUE), 'NATIVE',$device_code);

            if ($code_url === FALSE) {
                var_dump(['code' => 'ERROR', 'info' => '创建预支付码失败，' . $pay->errMsg]);
                exit;
            }
        }else{
            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();

//            $unifiedOrder->setParameter("body","方雅点餐系统");//商品描述
            $unifiedOrder->setParameter("body",$restaurant_name);//商品描述
            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $out_trade_no = $outer_no;
            $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee",$price);//总金额
            $unifiedOrder->setParameter("notify_url",\WxPayConf_pub::NOTIFY_URL);//通知地址
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
//        dump($code_url);
//        exit;
        error_reporting(E_ERROR);
        vendor("phpqrcode.phpqrcode");
        $url = urldecode($code_url);
        $errorCorrectionLevel = 'M';//容错级别
        $matrixPointSize = 6;//生成图片大小
        \QRcode::png($url,'qrcode.png', $errorCorrectionLevel, $matrixPointSize,2);
        //QRcode::png($url);

        $logo = 'wechat.png';//准备好的logo图片
        $QR = 'qrcode.png';//已经生成的原始二维码图

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

    //处理扫描器扫描得到的数据
    public function microPay(){
        $order_sn = $_POST['order_sn'];
//        $order_sn = $_GET['order_sn'];
        $order_info = explode("|",$order_sn);

        if($order_info[0] == "score"){
            // 传递过来的形式：score|$to|restaurant_id       $_POST['qr_number']是score_goods_order里面的订单号
            // 可以在这里可以将店铺id传递过来，更新score_goods_order订单表里的店铺id
            $restaurant_id = $order_info[2];

            $score_where['restaurant_id'] = $restaurant_id;
            $score_where['type'] = 4;
            $if_open = D("set")->where($score_where)->getField("if_open");

            $to = $order_info[1];

            $score_order_id = $_POST['qr_number'];
            $key = C("SECRET_KEY");
            $data = $score_order_id;
            $en = new Encrypt();
            $qr_number = $en->decrypt($data,$key);
            $score_order_model = D("score_goods_order");
            //查找出订单信息
            $where['order_sn'] = $qr_number;
            $order_goods_info = $score_order_model->where($where)->find();
            if(empty($order_goods_info)){
                // 判断订单号存不存在
                $content["type"] = "score";
                $content["pay_status"] = "0";
                $content["order_sn"] = $qr_number ;
                $content["msg"] = "订单号不存在";
                $this->pushDiscount($to,$content);
                exit;
            }
            // 判断是否开启
            if($if_open){
                //兑积分商品，判断用户积分是否充足，充足扣取积分，修改订单状态，不充足则提示积分不足

                //查看会员的信息
                $vip_where['id'] = $order_goods_info['vip_id'];
                $vip_model = D('vip');
                $vip_info = $vip_model->where($vip_where)->find();

                if($order_goods_info['order_status'] == 1){
                    // 判断是否已经兑换过
                    $content["type"] = "score";
                    $content["pay_status"] = "0";
                    $content["order_sn"] = $qr_number ;
                    $content["msg"] = "已经兑换过";
                    $this->pushDiscount($to,$content);
                    exit;
                }

                //对比订单所需积分跟会员积分
                if($order_goods_info['score'] > $vip_info['score']){
                    //如果订单所需积分大于会员积分，兑换失败
                    $content["type"] = "score";
                    $content["pay_status"] = "0";
                    $content["order_sn"] = $qr_number;
                    $content["msg"] = "兑换失败";
                    $this->pushDiscount($to,$content);
                }else{
                    //如果订单积分小于等于会员积分，兑换成功
                    $vip_data['score'] = $vip_info['score'] - $order_goods_info['score'];
                    $vip_model->where($vip_where)->save($vip_data);
                    $score_goods_order_data['pay_time'] = time();
                    $score_goods_order_data['order_status'] = 1;
                    $score_goods_order_data['restaurant_id'] = $restaurant_id;
                    $score_order_model->where($where)->save($score_goods_order_data);

                    //兑换成功返回content
                    $content["type"] = "score";
                    $content["pay_status"] = "1";
                    $content["order_sn"] = $qr_number;
                    $content["msg"] = "兑换成功";
                    $this->pushDiscount($to,$content);
                }
            }else{
                $content["type"] = "score";
                $content["pay_status"] = "0";
                $content["order_sn"] = $qr_number;
                $content["msg"] = "本店没有开启积分商品兑换";
                $this->pushDiscount($to,$content);
            }

        }elseif($order_info[1] == "discount"){
            $to = $order_info[2];
            //客户折扣处理
            $disc_qr_number = I("qr_number");
            $key = C("SECRET_KEY");
            $en = new Encrypt();
            $disc_qr_number2 = $en->decrypt($disc_qr_number,$key);


            $disc_vip_info = explode("|",$disc_qr_number2);
            $disc_time = $disc_vip_info[0];
            $now_time = time();
            $time_mistake = $now_time-$disc_time;
            if($time_mistake > 1800){
                //折扣支付返回content
                $content["type"] = "discount";
                $content["pay_status"] = "0";
                $content["order_sn"] = $order_info[0];
                $content["msg"] = "二维码失效";
                $this->pushDiscount($to,$content);
            }else{

                $order_model = order();
                $disc_order_sn = $order_info[0];
                $order_where['order_sn'] = $disc_order_sn;
                $disc_order_info = $order_model->where($order_where)->find();

                //获取会员所在分组id
                $disc_vip_id = $disc_vip_info[1];
                $vip_model = D('vip');
                $disc_where['id'] = $disc_vip_id;
                $group_id = $vip_model->where($disc_where)->field("group_id")->find()['group_id'];


                //获取会员所在分组在本店的折扣
                session("restaurant_id",$disc_order_info["restaurant_id"]);
                $restaurant_id = session("restaurant_id");
                $r_disc_where["group_id"] = $group_id;
                $r_disc_where["restaurant_id"] = $restaurant_id;
                $discount_model = D('discount');
                $discount_info = $discount_model->where($r_disc_where)->find();
                $discount = $discount_info['discount'];
                // 默认会员组有可能没有对应的折扣信息
                if(!$discount){
                    $discount = 10;
                }

                //修改订单的折扣

                if($disc_order_info['order_status'] > 3){
                    //折扣支付返回content
                    $content["type"] = "discount";
                    $content["pay_status"] = "0";
                    $content["order_sn"] = $order_info[0];
                    $content["msg"] = "已经支付过了";
                    $this->pushDiscount($to,$content);
                    exit;
                }

               if($disc_order_info['discount'] != 0){
                    //余额支付返回content
                    $content["type"] = "discount";
                    $content["pay_status"] = "0";
                    $content["order_sn"] = $order_info[0];
                    $content["msg"] = "已经享受过折扣了";
                    $this->pushDiscount($to,$content);
                    exit;
                }

                $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
                $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
                $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
                $condition1['restaurant_id'] = session("restaurant_id");     //店铺id

                $num = $order_model->where($condition1)->count();        //两时间之间的订单数

                $order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"A",STR_PAD_LEFT);//订单号，$num+1表示最新一订单


                $order_data['vip_id'] = $disc_vip_id;

                $order_data['discount'] = $discount/10;
                $order_data['original_price'] = $disc_order_info['total_amount'];
                $order_data['order_sn'] = $order_sn;
                $order_data['total_amount'] = $disc_order_info['total_amount']*$discount/10;
                $order_model->where($order_where)->save($order_data);

                //push修改先折扣成功信息
                $content["type"] = "discount";
                $content["pay_status"] = "1";
                $content["order_sn"] = $order_data['order_sn'];
                $content["msg"] = "获取折扣成功";
                $this->pushDiscount($to,$content);
            }
        }elseif($order_info[1] == "balance"){
            $to = $order_info[2];

            //客户支付处理
            $blc_qr_number = I("qr_number");
            $key = C("SECRET_KEY");
            $en = new Encrypt();
            $blc_qr_number2 = $en->decrypt($blc_qr_number,$key);

            $blc_vip_info = explode("|",$blc_qr_number2);
            $blc_time = $blc_vip_info[0];
            $now_time = time();
            $time_mistake = $now_time-$blc_time;
            if($time_mistake > 1800){
                //余额支付返回content
                $content["type"] = "balance";
                $content["pay_status"] = "0";
                $content["order_sn"] = $order_info[0];
                $content["msg"] = "二维码失效";
                $this->pushDiscount($to,$content);
            }else {
                //获取用户余额
                $blc_vip_id = $blc_vip_info[1];
                $blc_where['id'] = $blc_vip_id;
                $vip_model = D("vip");
//                $remainder = $vip_model->where($blc_where)->field("remainder")->find()['remainder'];
                $vip_info = $vip_model->where($blc_where)->field("remainder,score,total_consume")->find();
                $remainder = $vip_info['remainder'];
                $score = $vip_info['score'];    // 会员原有积分
                $total_consume = $vip_info['total_consume'];    // 会员原有消费总额
               
                //获取订单的总额
                $blc_order_where['order_sn'] = $order_info[0];
                $order_model = order();
                $blc_order_info = $order_model->where($blc_order_where)->find();
                if($blc_order_info['order_status'] > 3){
                    //余额支付返回content
                    $content["type"] = "balance";
                    $content["pay_status"] = "0";
                    $content["order_sn"] = $order_info[0];
                    $content["msg"] = "已经支付过了";
                    $this->pushDiscount($to,$content);
                    exit;
                }
                if($blc_order_info['total_amount'] > $remainder){
                    //余额支付返回content
                    $content["type"] = "balance";
                    $content["pay_status"] = "0";
                    $content["order_sn"] = $order_info[0];
                    $content["msg"] = "余额不足";
                    $this->pushDiscount($to,$content);
                }else{
                    $order_model->startTrans();

                    // 换取积分开始      根据订单号查询店铺id
                    $o_where['order_sn'] = $order_info[0];
                    $restaurant_id = $order_model->where($o_where)->getField("restaurant_id");
                    $o_condition['restaurant_id'] = $restaurant_id;
                    $o_condition['type'] = 2;
                    $if_open = D("set")->where($o_condition)->getField("if_open");
                    // 判断有没有开启
                    if($if_open){
                        // 根据代理id查出积分设置规则
                        $business_id = D("restaurant")->where(array("restaurant_id"=>$restaurant_id))->getField("business_id");
                        $rule_condition['business_id'] = $business_id;
                        $rule_condition['type'] = 2;
                        $rule = D("all_benefit")->where($rule_condition)->find();
                        if($rule){
                            // 判断消费额是否大于等于积分设置的金额
                            if($blc_order_info['total_amount'] >= $rule['account']){
                                $get_score = floor($blc_order_info['total_amount']/$rule['account'])*$rule['benefit'];
                            }
                        }
                    }else{
                        $get_score = 0;
                    }
                    $blc_vip_data['score'] = $score + $get_score;   // 更新会员积分
                    $blc_vip_data['total_consume'] = $total_consume + $blc_order_info['total_amount'];   // 更新会员消费总额
                    // 换取积分结束
                    // 生成订单的时候，就有一个默认的会员id=0，积分也是默认为0，然后来到这里支付完后就更新会员id为当前会员的id，订单积分也做更新
                    $blc_order_data["vip_id"] = $blc_vip_id;
                    $blc_order_data["score"] = $get_score;

                    $blc_order_data["order_status"] = 3;
                    $blc_order_data["pay_time"] = time();
                    $blc_order_data["pay_type"] = 4;
                    $order_save_rel = $order_model->where($blc_order_where)->save($blc_order_data);

                    $blc_vip_data['remainder'] = $remainder-$blc_order_info['total_amount'];
                    $save_vip_rel = $vip_model->where($blc_where)->save($blc_vip_data);


                    if($order_save_rel!== false && $save_vip_rel!==false){
                        $order_model->commit();
                        //余额支付返回content
                        $content["type"] = "balance";
                        $content["pay_status"] = "1";
                        $content["order_sn"] = $order_info[0];
                        $content["msg"] = "支付成功";
                        $this->pushDiscount($to, $content);
                    }else{
                        $order_model->rollback();
                    }
                }
            }
        }else{
            $orderModel = order();
            $ono_condition['order_sn'] = $order_sn;
            $orderInfo = $orderModel->where($ono_condition)->find();
            session("restaurant_id",$orderInfo['restaurant_id']);
            if($orderInfo['order_status'] == 3){
                exit;
            }
            $auth_code = $_POST['qr_number'];
            $prefix_num = substr($auth_code,0,2);
            if(in_array($prefix_num,C('AL_PAY_PREFIX'))){

                //查询该店铺的支付方式看是否开启支持支付宝支付
                $restaurant_id = session('restaurant_id');
                $pay_select_model = D('pay_select');
                $p_where['restaurant_id'] = $restaurant_id;
                $p_where['config_name'] = "ali-code";
                $rel = $pay_select_model->where($p_where)->find();
                if($rel){
                    $post_data = array ("order_sn" => $order_sn,"qr_number" => $auth_code);
                    $ali_out_put = http_post("http://".$_SERVER["HTTP_HOST"].U("Home/AlipayDirect/alipay_barcodePay"),$post_data);
                    echo $ali_out_put;
                    exit;
                }
            }
            $price = $orderInfo['total_amount']*100;

            if(in_array($prefix_num,C('WX_PAY_PREFIX'))){
                session('restaurant_id',$orderInfo['restaurant_id']);

                $configModel = D('config');
                $condition['config_type'] = "wxpay";
                $condition['restaurant_id'] = session("restaurant_id");
                $wxpay_config = $configModel->where($condition)->field("config_name,config_value")->select();
                $wxpay_c = dealConfigKeyForValue($wxpay_config);

                $restaurant_name = D("restaurant")->where(array("restaurant_id"=>session("restaurant_id")))->getField("restaurant_name");

                if(!$wxpay_c['wxpay_child_mchid'] || $wxpay_c['wxpay_child_mchid'] == ""){
                    require getcwd()."/Application/PayMethod/WxpayMicropay2/lib/WxPay.Data.php";
                    $result = false;
                    if($auth_code){
                        $input = new \WxPayMicroPay();
                        $input->SetAuth_code($auth_code);
//                        $input->SetBody("方雅餐饮系统");
                        $input->SetBody($restaurant_name);
                        $input->SetTotal_fee($price);


//                        $business_order = \WxPayConfig::$MCHID.date("YmdHis");
                        $business_order = $order_sn.'fp';
                        order()->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));


//                        $input->SetOut_trade_no(\WxPayConfig::$MCHID.date("YmdHis"));
                        $input->SetOut_trade_no($business_order);
                        $microPay = new MicroPay_1();
                        $result = $microPay->pay($input);
                    }

                    if($result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
                        //如果支付成功则使用极光推送推送到设备打印订单小票
                        //操作数据库处理订单信息；
                        $orderModel = order();
                        $o_condition['order_sn'] = $order_sn;
                        $data['order_status'] = 3;
                        $data['pay_type'] = 2;
                        $time = time();
                        $data['pay_time'] = $time;
                        $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                        if($rel !==false){
                            // 售罄处理
                            $S_SellOut = new ServiceSellOut();
                            $S_SellOut->sellOutDeal($order_sn);

                            // 删除第三方支付二维码
                            delQrcode($order_sn,2);

                            //获取订单信息，判断是否要推送到展示餐牌号展示页面
                            $orderInfo = $orderModel->where($o_condition)->field("table_num,desk_code,restaurant_id")->find();
                            $restaurantModel = D("Restaurant");
                            $rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
                            $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
                            if($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0){
                                $content['tips'] = "下单成功推送showNum";
                                $content['order_sn'] = $order_sn;
                                $contentJson = json_encode($content);
                                $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                                $rel2 = sendMsgToDevice($post_data);
                                //推送到所有分区的叫号屏，核销屏
                                $restaurant_id = $orderInfo['restaurant_id'];
                                pushAllDistrict($restaurant_id,$order_sn);
                            }

                            $msg["code"] = 1;
                            $msg['msg'] = "支付成功";
                            exit(json_encode($msg));
                        }
                    }
                }else{
                    require getcwd()."/Application/PayMethod/WxpayMicropay/lib/WxPay.Data.php";
                    $result = false;
                    if($auth_code){
                        $input = new \WxPayMicroPay();
                        $input->SetAuth_code($auth_code);
//                        $input->SetBody("方雅餐饮系统");
                        $input->SetBody($restaurant_name);
                        $input->SetTotal_fee($price);

                        //　提交的商户订单号跟系统订单号联系起来
//                        $business_order = \WxPayConfig::$MCHID.date("YmdHis");
                        $business_order = $order_sn.'fp';
                        order()->where(array('order_sn'=>$order_sn))->save(array('saoma_out_trade_no'=>$business_order));

//                        $input->SetOut_trade_no(\WxPayConfig::$MCHID.date("YmdHis"));
                        $input->SetOut_trade_no($business_order);
                        $input->SetSub_mch_id(\WxPayConfig::$SUB_MCHID);

                        $microPay = new MicroPay();
                        $result = $microPay->pay($input);
                    }

                    if($result == true){
                        //如果支付成功则使用极光推送推送到设备打印订单小票

                        //操作数据库处理订单信息；
                        $orderModel = order();
                        $o_condition['order_sn'] = $order_sn;
                        $data['order_status'] = 3;
                        $data['pay_type'] = 2;
                        $time = time();
                        $data['pay_time'] = $time;
                        $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                        //  根据获取到的sub_openid（用户相对于子商户公众号的openid，如果已经在该子商户的公众号进行了注册，则在数据库的会员表已有记录，就是会员了）
                        // 判断该用户是否会员
                        $vip_tiaojian['openid'] = $result["sub_openid"];
                        $vipInfo =  M("vip")->where($vip_tiaojian)->find();
                        if($vipInfo){
                            $restaurant_id = $orderInfo['restaurant_id'];
                            // 店铺还是代理
                            $business_id = M('restaurant')->where(array('restaurant_id'=>$restaurant_id))->getField('business_id');
                            $vip_mode = M('business')->where(array('business_id'=>$business_id))->getField('vip_mode');
                            if($vip_mode == 1){
                                // 代理
//                                $score_get['business_id'] = $business_id;
                                $score_rule_condition['business_id'] = $business_id;
                            }else{
//                                $score_get['restaurant_id'] = $restaurant_id;
                                $score_rule_condition['restaurant_id'] = $restaurant_id;
                            }


                            // 判断积分换取有没有开启
                            $restaurant_id = $orderModel->where(array("order_sn"=>$order_sn))->getField("restaurant_id");
                            $score_get['restaurant_id'] = $restaurant_id;
                            $score_get['type'] = 2;
                            $if_open = M("set")->where($score_get)->getField("if_open");
                            if($if_open){
//                                $business_id = M("restaurant")->where(array("restaurant_id"=>$restaurant_id))->getField("business_id");
//                                $score_rule_condition['business_id'] = $business_id;
                                $score_rule_condition['type'] = 2;
                                $score_rule = M("all_benefit")->where($score_rule_condition)->find();

                                // 判断消费额是否大于等于积分设置的金额
                                $sum_money = $result['total_fee']/100;
                                if($sum_money >= $score_rule['account']){
                                    $get_score = floor($sum_money/$score_rule['account'])*$score_rule['benefit'];
                                    $score_vip_data['score'] = $vipInfo['score'] + $get_score;   // 更新会员积分
                                    $score_vip_data['total_consume'] = $vipInfo['total_consume'] + $sum_money;   // 更新会员消费总额
                                    $vip_rel = M("vip")->where($vip_tiaojian)->save($score_vip_data);

                                    // 更新订单积分和会员id
                                    $order_save['score'] = $get_score;
                                    $order_save['vip_id'] = $vipInfo['id'];
                                    $order_score_rel = $orderModel->where(array("order_sn"=>$order_sn))->save($order_save);
                                }
                            }
                        }

                        if($rel !==false){
                            // 售罄处理
                            $S_SellOut = new ServiceSellOut();
                            $S_SellOut->sellOutDeal($order_sn);

                            // 删除第三方支付二维码
                            delQrcode($order_sn,2);

                            //支付回掉记录
                            //获取订单信息，判断是否要推送到展示餐牌号展示页面
                            $orderInfo = $orderModel->where($o_condition)->field("table_num,desk_code,restaurant_id")->find();
                            $restaurantModel = D("Restaurant");
                            $rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
                            $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

                            if($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0){
                                $content['tips'] = "下单成功推送showNum";
                                $content['order_sn'] = $order_sn;
                                $contentJson = json_encode($content);
                                $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                                $rel2 = sendMsgToDevice($post_data);
                                //推送到所有分区的叫号屏，核销屏
                                $restaurant_id = $orderInfo['restaurant_id'];
                                pushAllDistrict($restaurant_id,$order_sn);
                            }
                            echo 1;
                            exit;
                        }
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

    //推送到支付提示页payPrompt.html
    public function pushDiscount($to,$content){
        $url = "http://shop.founpad.com:6428/";
        $post_data['type'] = "send";
        $post_data['to'] = $to;
        $post_data['content'] = json_encode($content);
        http_post($url,$post_data);
    }


    /**************手机后台支付测试回调**************/
    // 微信支付测试成功回调处理
    public function Wx_notify_url() {
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            //返回正常状态，防止微信重复推荐通知
            echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);

            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];

            if(session($order_sn)){
                echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
            }else{
                $orderModel = D("pay_test_demo");
                $o_condition['order_sn'] = $order_sn;
                $data['order_status'] = 3;
                $data['pay_type'] = 2;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                session($order_sn,true);
            }
        }
    }

    /**************手机后台支付测试回调**************/
}