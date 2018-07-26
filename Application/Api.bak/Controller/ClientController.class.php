<?php
namespace Api\Controller;

use Think\Verify;
use PayMethod\WxpayMicropay\MicroPay;
use PayMethod\WxpayMicropay2\MicroPay_1;
use PayMethod\Wechat\WechatPay;
use Think\Encrypt;
use data\service\Category;
use data\service\SellOut as ServiceSellOut;

class ClientController extends BaseController
{
    /* public function __construct()
   {
//       $token = I("post.token");
       $token = I("token");
       $condition['token'] = $token;
       $info = D("interface_login_check")->where($condition)->find();
       if(!$info){
           $returnData['code'] = "0";
           $returnData['msg'] = "非法访问";
           exit(json_encode($returnData));
       }
   }*/

    // 客户端下单时，同步订单信息，本地订单与服务器订单做映射（支付状态为未支付，支付类型为未确定）
    public function placeOrder(){
        $device_code = I("post.device_code");
        $cashier_id = I("post.cashier_id");
        $this->isLogin($device_code);
        if($this->is_security) {
            $orderData = I("post.orderData");
            $orderData = str_replace("&quot;","\"",$orderData);
            $orderData = str_replace("&amp;quot;","\"",$orderData);
            $orderDataInfo_before = json_decode($orderData);
            // 传递过来的数组永远只有一个元素，就不用做循环了，直接取第一个元素
            $orderDataInfo = $orderDataInfo_before[0];

//            file_put_contents(__DIR__."/"."receiver_order.txt",'店铺id'.session('restaurant_id').'，数据：'.json_encode($orderDataInfo)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

            $table_num = I("post.table_num");
            //同步订单信息，做映射
            // 安卓的本地订单号
            $client_order = $orderDataInfo->order_sn;
            $rel = D("order")->where(array('order_sn'=>$client_order))->find();
            if($rel){
                $returnData['code'] = 2;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "数据库中已有对应记录";
                exit(json_encode($returnData));
            }

            //进行订单同步，客户端订单与服务器订单做映射
            //1、生成订单
            $order_model = D("order");
            $order_model->startTrans(); //开启事务

            $orderInfo['order_type'] = $orderDataInfo->order_type;
            $orderInfo['add_time'] = strtotime($orderDataInfo->add_time);
            $orderInfo['restaurant_id'] = session("restaurant_id");
            $orderInfo['order_status'] = 0;
            $orderInfo['order_sn'] = $client_order;

            if($orderDataInfo->take_num){
                // 添加取餐号（数据库新增一个字段）
                $orderInfo['take_num'] = $orderDataInfo->take_num;
            }

            // 如果存在餐桌号，则将其记录进订单信息
            if($table_num){
                $orderInfo['table_num'] =  str_pad($table_num,3,"0",STR_PAD_LEFT);
            }
            //是否有收银员id
            if ($cashier_id) {
                $orderInfo['cashier_id'] =  $cashier_id;
            }

            // 添加安卓本地生成的支付号
            if($orderDataInfo->pay_num){
                $orderInfo['zhifuhao'] =  $orderDataInfo->pay_num;
            }

            $order_id = $order_model->add($orderInfo);

            /*file_put_contents(__DIR__."/"."order_info.txt",'插入的订单号为：'. $orderInfo['order_sn'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

            $total_amount = 0;
            if($order_id !== 0 && !empty($orderDataInfo->foods)){
                $food_model = D("food");
                $order_food_model = D("order_food");
                $order_food_attr_model = D("order_food_attribute");
                $food_attr_model = D("food_attribute");
                $attr_type_model = D("attribute_type");
                foreach($orderDataInfo->foods as $f_key => $f_val){
                    $f_where['food_id'] = $f_val->food_id;
                    $foodInfo = $food_model->where($f_where)->find();
                    $orderFoodData = Array();
                    $orderFoodData['food_name'] = $foodInfo['food_name'];
                    $orderFoodData['food_price2'] = $foodInfo['food_price']*$f_val->food_num;
                    $orderFoodData['district_id'] = $foodInfo['district_id'];
                    $orderFoodData['food_num'] = $f_val->food_num;
                    $orderFoodData['food_id'] = $f_val->food_id;
                    $orderFoodData['order_id'] = $order_id;
			        $orderFoodData['print_id'] = $foodInfo['print_id'];
                    $order_food_id = $order_food_model->add($orderFoodData);
                    $food_price2 = $foodInfo['food_price']*$f_val->food_num;
                    if($order_food_id !== false && !empty($f_val->food_attrs)){
                        foreach($f_val->food_attrs as $fa_key => $fa_val){
                            $fa_where['food_attribute_id'] = $fa_val;
                            $food_attribute_info = $food_attr_model->where($fa_where)->find();

                            $food_price2+=$food_attribute_info['attribute_price']*$f_val->food_num;

                            $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                            $attr_type_info = $attr_type_model->where($atm_where)->find();

                            $orderFoodAttrData['order_food_id'] = $order_food_id;
                            $orderFoodAttrData['num'] = $f_val->food_num;
					        $orderFoodAttrData['food_attribute_id'] = $food_attribute_info['food_attribute_id'];
                            $orderFoodAttrData['food_attribute_name'] = $food_attribute_info['attribute_name'];
                            $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price']*$f_val->food_num;
                            $orderFoodAttrData['print_id'] = $attr_type_info['print_id'];
                            $orderFoodAttrData['count_type'] = $attr_type_info['count_type'];
                            $orderFoodAttrData['tag_print_id'] = $attr_type_info['tag_print_id'];
                            $order_food_attr_id = $order_food_attr_model->add($orderFoodAttrData);
                            if($order_food_attr_id === false){
                                $order_model->rollback();

                                $returnData['code'] = 0;
                                $returnData['order_sn'] = "";
                                $returnData['msg'] = "同步订单失败";
                                exit(json_encode($returnData));
                            }
                        }
                    }
                    //更新$food_price2
                    $orderFoodData2['food_price2'] = $food_price2;
                    $orderFoodData2['order_food_id'] = $order_food_id;
                    $order_food_model->save($orderFoodData2);

                    if($order_food_id === false){
                        $order_model->rollback();

                        $returnData['code'] = 0;
                        $returnData['order_sn'] = "";
                        $returnData['msg'] = "同步订单失败";
                        exit(json_encode($returnData));
                    }
                    $total_amount+=$food_price2;
                }
                if($order_id === false){
                    $order_model->rollback();

                    $returnData['code'] = 0;
                    $returnData['order_sn'] = "";
                    $returnData['msg'] = "同步订单失败";
                    exit(json_encode($returnData));
                }
            }

            //更新$total_amount
            $orderInfo_save['total_amount'] = $total_amount;    // 优惠后价格
            $orderInfo_save['original_price'] = $total_amount;   // 原价
            $orderInfo_save['order_id'] = $order_id;

            /**************店铺折扣开始*************/
            /*
             if_enjoy_benefits:1享受，2不享受
            原价：origin_price
            优惠后的价格：after_benefit_price （原价*折扣-立减）
            优惠了多少：benefit_money  （原价-优惠价格）
            打了多少折扣：discount
            立减了多少：reduce
            */
            // 判断是否享受了店铺优惠
            if($orderDataInfo->if_enjoy_benefits == 1){
                /*file_put_contents(__DIR__."/"."placeOrder_restaurant_discount.txt",'是否享受优惠为：'. $orderDataInfo->if_enjoy_benefits.
                    '，订单号为：'. $client_order.
                    '，原价为：'. $orderDataInfo->origin_price.
                    '，享受优惠后的价格为：'. $orderDataInfo->after_benefit_price.
                    '，优惠了多少：'. $orderDataInfo->benefit_money.
                    '，打了多少折：'. $orderDataInfo->discount.
                    '，立减多少：'. $orderDataInfo->reduce.
                    "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

                // 享受了店铺优惠
                $orderInfo_save['original_price'] = $orderDataInfo->origin_price;   // 原价
                $orderInfo_save['total_amount'] = $orderDataInfo->after_benefit_price;   // 享受优惠后的价格
                $orderInfo_save['benefit_money'] = $orderDataInfo->benefit_money;   // 优惠了多少
                $orderInfo_save['discount'] = $orderDataInfo->discount;   // 打了多少折
                $orderInfo_save['reduce'] = $orderDataInfo->reduce;   // 立减多少
                $orderInfo_save['vip_or_restaurant'] = 3;   // 区分是会员折扣还是整个店铺的折扣，1不打折，2会员折扣，3整个店铺折扣
            }

            /**************店铺折扣结束*************/

            $save_res = $order_model->save($orderInfo_save);
            if($save_res === false){
                $order_model->rollback();

                $returnData['code'] = 0;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "同步订单失败";
                exit(json_encode($returnData));
            }


            // 如果传递过来的微信、支付宝标识有值，则返回支付二维码
            // 调二维码之前再次判断是否已支付过
            $order_status = D("order")->where(array("order_sn"=>$client_order))->getField("order_status");
            if($order_status == 3){
                $order_model->rollback();

                $returnData['code'] = 0;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "此笔订单已经支付";
                exit(json_encode($returnData));
            }

            // 检测是否重单
            $order_num = D("order")->where(array("order_sn"=>$client_order))->count();
            if($order_num>1){
                $order_model->rollback();

                $returnData['code'] = 0;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "此笔订单重复";
                exit(json_encode($returnData));
            }

            $order_model->commit();


            // 以下是订单同步成功后返回给安卓的信息
            $returnData['code'] = 1;
            $returnData['order_sn'] = $client_order;
            $returnData['msg'] = "订单同步成功";
            $dev_code = I("post.device_code");

            /*****************************判断安卓是需要民生的码还是官方的码*********************************/
            $need_which = I('post.need_which'); // 1官方  2民生
            if($need_which == 2){
                // 需要民生的码
                // 实例化FourthPay类的对象
                $FourthPay = new FourthPayController();
                // 接收参数
                $data_arr['fourth_sn'] = I('post.fourth_sn');   // 提交给民生的订单号
                $data_arr['order_sn'] = $client_order;     // 服务器订单号
                $data_arr['public_key'] = I('post.public_key'); // 秘钥
                $data_arr['operater_id'] = I('post.operater_id'); // 操作员ID
                $data_arr['business_no'] = I('post.business_no');   // 商户号
                $data_arr['device_code'] =$device_code;   
                $return = $FourthPay->pay_code_in_place_order($data_arr);
                if($return['code'] == 1){
                    $returnData['wx_adress'] = $return['weixin_qr'];
                    $returnData['ali_adress'] = $return['ali_qr'];
                }else{
                    $returnData['code'] = 0;
                    $returnData['msg'] = $return['msg'];
                    $returnData['wx_adress'] = 0;
                    $returnData['ali_adress'] = 0;
                }
                exit(json_encode($returnData));
            }else{
                // 需要官方的码
                if($orderDataInfo->wx_url){
                    $returnData['wx_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/WxChat/qrc/order_sn/".$client_order."/device_code/".$dev_code;
                }else{
                    $returnData['wx_adress'] = 0;
                }
                if($orderDataInfo->ali_url){
                    $returnData['ali_adress'] = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/AlipayDirect/alipay_code/order_sn/".$client_order."/device_code/".$dev_code;
                }else{
                    $returnData['ali_adress'] = 0;
                }
                /*file_put_contents(__DIR__."/"."placeOrderLog.txt","店铺ID：".session("restaurant_id")."|服务器订单号："
                    .$client_order."|安卓订单号：".$client_order."|"."打印日志时间：".date("Y-m-d H:i:s",time()).
                    "\r\n|订单总价：".$total_amount.
                    "\r\n|订单状态：".$order_status.
                    "\r\n|安卓本地订单添加时间：".$orderDataInfo->add_time.
                    "\r\n|微信二维码URL：".$returnData['wx_adress']."\r\n"."|支付宝二维码URL：".$returnData['ali_adress'].
                    "\r\n|是否要二维码，微信：".$orderDataInfo->wx_url."支付宝：".$orderDataInfo->ali_url."。\r\n\r\n",FILE_APPEND);*/
                exit(json_encode($returnData));
            }

            /*****************************判断安卓是需要民生的码还是官方的码*********************************/

        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 从无网到有网后进行订单同步
    public function order_tongbu(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $orderData = I("post.orderData");
            $orderData = str_replace("&quot;","\"",$orderData);
            $orderData = str_replace("&amp;quot;","\"",$orderData);
            $orderDataInfo_before = json_decode($orderData);
            $orderDataInfo = $orderDataInfo_before[0];

            $client_order = $orderDataInfo->order_sn;
            $rel = D("order")->where(array('order_sn'=>$client_order))->find();
            if($rel){
                $returnData['code'] = 0;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "数据库中已有对应记录";
                exit(json_encode($returnData));
            }

            //进行订单同步，客户端订单与服务器订单做映射
            //1、生成订单
            $order_model = D("order");
            $order_model->startTrans(); //开启事务

            $orderInfo['order_type'] = $orderDataInfo->order_type;
            $orderInfo['add_time'] = strtotime($orderDataInfo->add_time);
            $orderInfo['pay_time'] = strtotime($orderDataInfo->add_time);
            $orderInfo['restaurant_id'] = session("restaurant_id");
            $orderInfo['order_status'] = 0;
            $orderInfo['order_sn'] = $client_order;

            if($orderDataInfo->take_num){
                // 添加取餐号（数据库新增一个字段）
                $orderInfo['take_num'] = $orderDataInfo->take_num;
            }

            if($orderDataInfo->pay_num){
                // 添加支付号（数据库新增一个字段）
                $orderInfo['zhifuhao'] = $orderDataInfo->pay_num;
            }

            $order_id = $order_model->add($orderInfo);

            $total_amount = 0;

            if($order_id !== 0 && !empty($orderDataInfo->foods)){
                $food_model = D("food");
                $order_food_model = D("order_food");
                $order_food_attr_model = D("order_food_attribute");
                $food_attr_model = D("food_attribute");
                $attr_type_model = D("attribute_type");
                foreach($orderDataInfo->foods as $f_key => $f_val){
                    $f_where['food_id'] = $f_val->food_id;
                    $foodInfo = $food_model->where($f_where)->find();
                    $orderFoodData = Array();
                    $orderFoodData['food_name'] = $foodInfo['food_name'];
                    $orderFoodData['food_price2'] = $foodInfo['food_price']*$f_val->food_num;
                    $orderFoodData['district_id'] = $foodInfo['district_id'];
                    $orderFoodData['food_num'] = $f_val->food_num;
                    $orderFoodData['food_id'] = $f_val->food_id;
                    $orderFoodData['order_id'] = $order_id;
                    $order_food_id = $order_food_model->add($orderFoodData);
                    $food_price2 = $foodInfo['food_price']*$f_val->food_num;
                    if($order_food_id !== false && !empty($f_val->food_attrs)){
                        foreach($f_val->food_attrs as $fa_key => $fa_val){
                            $fa_where['food_attribute_id'] = $fa_val;
                            $food_attribute_info = $food_attr_model->where($fa_where)->find();
                            $food_price2+=$food_attribute_info['attribute_price']*$f_val->food_num;

                            $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                            $attr_type_info = $attr_type_model->where($atm_where)->find();

                            $orderFoodAttrData['order_food_id'] = $order_food_id;
                            $orderFoodAttrData['num'] = $f_val->food_num;
                            $orderFoodAttrData['food_attribute_name'] = $food_attribute_info['attribute_name'];
                            $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price']*$f_val->food_num;
                            $orderFoodAttrData['print_id'] = $attr_type_info['print_id'];
                            $orderFoodAttrData['count_type'] = $attr_type_info['count_type'];
                            $order_food_attr_id = $order_food_attr_model->add($orderFoodAttrData);
                            if($order_food_attr_id === false){
                                $order_model->rollback();
                                $returnData['code'] = 0;
                                $returnData['client_order_sn'] = "";
                                $returnData['server_order_sn'] = "";
                                $returnData['msg'] = "同步失败";
                                exit(json_encode($returnData));
                            }
                        }
                    }
                    //更新$food_price2
                    $orderFoodData2['food_price2'] = $food_price2;
                    $orderFoodData2['order_food_id'] = $order_food_id;
                    $order_food_model->save($orderFoodData2);

                    if($order_food_id === false){
                        $order_model->rollback();
                        $returnData['code'] = 0;
                        $returnData['client_order_sn'] = "";
                        $returnData['server_order_sn'] = "";
                        $returnData['msg'] = "同步失败";
                        exit(json_encode($returnData));
                    }
                    $total_amount+=$food_price2;
                }
                if($order_id === false){
                    $order_model->rollback();
                    $returnData['code'] = 0;
                    $returnData['client_order_sn'] = "";
                    $returnData['server_order_sn'] = "";
                    $returnData['msg'] = "同步失败";
                    exit(json_encode($returnData));
                }
            }
            //更新$total_amount
            $orderInfo_save['total_amount'] = $total_amount;    // 最终价
            $orderInfo_save['original_price'] = $total_amount;   // 原价
            $orderInfo_save['order_id'] = $order_id;

            /**************店铺折扣开始*************/
            /*
             if_enjoy_benefits:1享受，2不享受
            原价：origin_price
            优惠后的价格：after_benefit_price （原价*折扣-立减）
            优惠了多少：benefit_money  （原价-优惠价格）
            打了多少折扣：discount
            立减了多少：reduce
            */
            // 判断是否享受了店铺优惠
            if($orderDataInfo->if_enjoy_benefits == 1){
                /*file_put_contents(__DIR__."/"."orderTongbu_restaurant_discount.txt",'是否享受优惠为：'. $orderDataInfo->if_enjoy_benefits.
                    '，订单号为：'. $orderDataInfo->order_sn.
                    '，原价为：'. $orderDataInfo->origin_price.
                    '，享受优惠后的价格为：'. $orderDataInfo->after_benefit_price.
                    '，优惠了多少：'. $orderDataInfo->benefit_money.
                    '，打了多少折：'. $orderDataInfo->discount.
                    '，立减多少：'. $orderDataInfo->reduce.
                    "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

                // 享受了店铺优惠
                $orderInfo_save['original_price'] = $orderDataInfo->origin_price;   // 原价
                $orderInfo_save['total_amount'] = $orderDataInfo->after_benefit_price;   // 享受优惠后的价格
                $orderInfo_save['benefit_money'] = $orderDataInfo->benefit_money;   // 优惠了多少
                $orderInfo_save['discount'] = $orderDataInfo->discount;   // 打了多少折
                $orderInfo_save['reduce'] = $orderDataInfo->reduce;   // 立减多少
                $orderInfo_save['vip_or_restaurant'] = 3;   // 区分是会员折扣还是整个店铺的折扣，1不打折，2会员折扣，3整个店铺折扣
            }

            /**************店铺折扣结束*************/

            $order_model->save($orderInfo_save);
            $order_model->commit();

            $cl_order = $orderDataInfo->order_sn;
            $returnData['code'] = 1;
            $returnData['client_order_sn'] = $cl_order;
            $returnData['server_order_sn'] = $cl_order;
            $returnData['msg'] = "订单同步成功";

           /* file_put_contents(__DIR__."/"."if_ask_order_tongbu.txt",'是否有请求order_tongbu：'. json_encode($returnData).
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['client_order_sn'] = "";
            $returnData['server_order_sn'] = "";
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 支付号支付
    public function zhifuhao_pay(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $zhifuhao = I("post.zhifuhao");
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

            $condition1['restaurant_id'] = session("restaurant_id");
            $condition1['zhifuhao'] = $zhifuhao;
            $condition1['add_time'] = array("between",array($start,$end));

            // 判断此订单当前店铺今天内是否唯一
            /*$num = D("order")->where($condition1)->count();
            if($num>1){
                $returnData['code'] = 0;
                $returnData['msg'] = "此笔订单今天范围内不唯一";
                exit(json_encode($returnData));
            }*/

            // 首先判断此笔订单在不在今天内
            $order_info = D("order")->where($condition1)->field("order_id,order_status,order_sn,total_amount")->find();
            if(empty($order_info['order_id'])){
                $returnData['code'] = 2;
                $returnData['msg'] = "今天范围内没有这笔订单";
                $returnData['cookroom'] = "";
                $returnData['receipt'] = "";
                $returnData['label'] = "";
                exit(json_encode($returnData));
            }
            $json = json_encode($order_info);
            /*file_put_contents(__DIR__."/"."zhifuhaocheck.txt","订单状态：".$order_info['order_status']."|支付号："
                .$zhifuhao."|订单id".$order_info['order_id']."|json".$json."。\r\n\r\n",FILE_APPEND);*/
            // 判断订单是否已经支付
            if($order_info['order_status'] == "3"){
                $returnData['code'] = 2;
                $returnData['msg'] = "该笔订单已经支付过";
                $returnData['cookroom'] = "";
                $returnData['receipt'] = "";
                $returnData['label'] = "";
                exit(json_encode($returnData));
            }

            // 返回打印数据给安卓
            $print_data = D("print_after_zhifuhao")->where(array("order_sn"=>$order_info['order_sn']))->find();

            $returnData['code'] = 1;
            $returnData['msg'] = "支付成功";
            $returnData['cookroom'] = $print_data['cookroom'];  // 厨房打印信息
            $returnData['receipt'] = $print_data['receipt'];    // 小票打印信息
            $returnData['label'] = $print_data['label'];    // 标签打印信息
            $returnData['total_amount'] = $order_info['total_amount'];    // 订单总价
            $returnData['order_sn'] = $order_info['order_sn'];    // 订单号
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            $returnData['cookroom'] = "";
            $returnData['receipt'] = "";
            $returnData['label'] = "";
            exit(json_encode($returnData));
        }
    }

    /**
     * 支付号支付完后更改订单状态
     * 方式：post
     * 设备码：device_code
     * 订单号：order_sn
     * 收银员id：cashier_id
     */
    public function callback_after_zhifuhao(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I("post.order_sn");
            $save['pay_time'] = time();
            $save['order_status'] = 3;
            $save['cashier_id'] = I("post.cashier_id");
            // 支付状态默认现金支付
            $res = D("order")->where(array("order_sn"=>$order_sn))->save($save);
            if($res === false){
                $returnData['code'] = 0;
                $returnData['msg'] = "更改订单状态失败";
                exit(json_encode($returnData));
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "更改订单状态成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }
    
    /**
     * 客户按到收银台现金支付按钮的时候，提交支付号支付完后需打印的打印数据过来
     * 方式：post
     * 设备码：device_code
     * 订单号：order_sn
     * 厨房打印信息：cookroom
     * 小票打印信息：receipt
     * 标签打印信息：label
     */
    public function print_after_zhifuhao(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I("post.order_sn");
            $print_after_zhifuhao = D("print_after_zhifuhao");
            // 判断是否已经同步过
            $id = $print_after_zhifuhao->where(array("order_sn"=>$order_sn))->getField("id");
            if($id){
                $returnData['code'] = 0;
                $returnData['msg'] = "该笔订单对应的打印信息已经存在于数据库";
                exit(json_encode($returnData));
            }
            // 数据入库
            $add_data = array();
            $add_data['order_sn'] = $order_sn;
            $add_data['cookroom'] = $_POST['cookroom'];
            $add_data['receipt'] = $_POST['receipt'];
            $add_data['label'] = $_POST['label'];
            if($print_after_zhifuhao->create($add_data)){
                if($print_after_zhifuhao->add($add_data)){
                    $returnData['code'] = 1;
                    $returnData['msg'] = "添加数据成功";
                    exit(json_encode($returnData));
                }else{
                    $returnData['code'] = 0;
                    $returnData['msg'] = "添加数据失败";
                    exit(json_encode($returnData));
                }
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "添加数据失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 获取该店铺是否开启餐桌号，是否开启会员折扣、余额的数据
     * 方式：post
     * device_code
     * cookie('device_code')横竖屏
     */
    public function getIfOpenInfo(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_process = D("restaurant_process");
            $restaurant_id = session("restaurant_id");
            $pr_condition['restaurant_id'] = $restaurant_id;
            $info = $restaurant_process->where($pr_condition)->field("process_id,process_status")->select();
            $status = array();
            foreach($info as $s){
                if($s['process_id'] == 1){
                    $status["advertise_status"] = $s['process_status'];
                }elseif($s['process_id'] == 2){
                    $status["select_status"] = $s['process_status'];
                }elseif($s['process_id'] == 3){
                    $status["order_status"] = $s['process_status'];
                }elseif($s['process_id'] == 4){
                    $status["number_status"] = $s['process_status'];
                }elseif($s['process_id'] == 5){
                    $status["pay_status"] = $s['process_status'];
                }
            }
            // 各流程开启关闭情况
            $arr['all_status'] = $status;   // 0关闭，1开启

            // 是否开启折扣
            $discount_where['restaurant_id'] = $restaurant_id;
            $discount_where['type'] = 0;
            $discount_open = D("set")->where($discount_where)->getField("if_open");
            if(empty($discount_open)){
                $discount_open = 0;
            }
            // 是否开启折扣
            $arr['discount'] = $discount_open;  // 0关闭，1开启

            // 是否开启店铺折扣

            /*
             * 数组下标为restaurant_discount的是关于店铺折扣的信息，里面是一个数组，下标值为if_open_restaurant_discount表示是否开启店铺折扣，
             * money表示消费满多少钱，discount表示打多少折（discount已转化成零点几的形式），reduce表示立减多少元，
             * 具体返回数据在开发前可使用http_post请求进行浏览器请求
             **/

            /**处理机制：只有当开关开了，并且有优惠信息才给安卓返回开关开了和相应的优惠信息，
            如果开关开了，但是没有优惠信息，则给安卓返回开关关了，优惠信息为''，
            如果开关关了，就算有优惠信息，也给安卓返回开关关了，又有信息为''
             *
             **/
            $discount_restaurant_where['restaurant_id'] = $restaurant_id;
            $discount_restaurant_where['type'] = 5;
            $restaurant_discount_open = D("set")->where($discount_restaurant_where)->getField("if_open");
            if(empty($restaurant_discount_open)){
                $restaurant_discount_open = 0;
            }
            $restaurant_discount_detail = D('restaurant_discount')->where(array('restaurant_id'=>session('restaurant_id')))->find();
            // 开了店铺折扣，并且有折扣详情的时候才返回数据给安卓
            if(!($restaurant_discount_open && $restaurant_discount_detail)){
                $restaurant_discount_detail['money'] = '';
                $restaurant_discount_detail['discount'] = '';
                $restaurant_discount_detail['reduce'] = '';
                $restaurant_discount_open = 0;
            }

            // 是否开启折扣
            $discount_arr['if_open_restaurant_discount'] = $restaurant_discount_open;
            $discount_arr['money'] = $restaurant_discount_detail['money'];

            if($restaurant_discount_detail['discount'] == ''){
                $discount_arr['discount'] = '';
            }else{
                if($restaurant_discount_detail['discount']/10 == 1){
                    $discount_arr['discount'] = 1;
                }else{
                    $discount_arr['discount'] = $restaurant_discount_detail['discount']/10;
                }
            }

            $discount_arr['reduce'] = $restaurant_discount_detail['reduce'];
            $arr['restaurant_discount'] = $discount_arr;  // 0关闭，1开启

            $returnData['code'] = 1;
            $returnData['msg'] = "获取数据成功";
            $returnData['data'] = $arr;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData));
        }
    }

    #店铺余额支付开关
    public function getIfOpenpre(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            // 是否开启余额支付
            $where['restaurant_id'] = session("restaurant_id");
            $where['type'] = 6;         // 6代表余额支付
            $remind_open = D("set")->where($where)->getField("if_open");
            if(empty($remind_open)){
                $remind_open = 0;
            }
            // 是否开启余额
            $arr['piepaidpre'] = $remind_open;  // 0关闭，1开启

            $returnData['code'] = 1;
            $returnData['msg'] = "获取数据成功";
            $returnData['data'] = $arr;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData));
        }
    }

    // 是否开启积分物品
    public function if_cancel()
    {
//        $device_code = I("post.device_code");
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $score_where['restaurant_id'] = session("restaurant_id");
            $score_where['type'] = 4;
            $if_open = D("set")->where($score_where)->getField("if_open");
            if(empty($if_open)) {
                $if_open = 0;
            }
            // 0关闭，1开启
            $returnData['code'] = 1;
            $returnData['msg'] = "获取数据成功";
            $returnData['score'] = $if_open;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 公众号入口链接
    public function public_num_url()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $business_where['restaurant_id'] = session("restaurant_id");
            $business_id = D("restaurant")->where($business_where)->getField("business_id");
            $public_where["business_id"] = $business_id;
            $public_number_url = D("public_number_set")->where($public_where)->getField("public_number_url");
            $returnData['code'] = 1;
            $returnData['msg'] = "获取数据成功";
            $returnData['url'] = $public_number_url;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 要显示哪些支付类型
    public function pay_type()
    {
         $device_code = I("post.device_code");
         $this->isLogin($device_code);
         if ($this->is_security) {
             $pay_select_model = D('pay_select');
             $ps_condition['restaurant_id'] = session('restaurant_id');
             $pay_select_config = $pay_select_model->where($ps_condition)->select();
             foreach($pay_select_config as $ps_va){
                 if($ps_va['s_num'] == "1"){
                     // 微信支付
                     $arr['wx'] = $ps_va['value'];
                 }elseif($ps_va['s_num'] == "2"){
                    // 银联或者现金
                     $arr['cash'] = $ps_va['value'];
                 }elseif($ps_va['s_num'] == "3"){
                    // 微信刷卡支付
                     $arr['wechat'] = $ps_va['value'];
                 }else{
                    // 支付宝支付
                     $arr['ali_code'] = $ps_va['value'];
                 }

             }
             $returnData['code'] = 1;
             $returnData['msg'] = "获取数据成功";
             $returnData['data'] = $arr;
             exit(json_encode($returnData));
         }else{
             $returnData['code'] = 0;
             $returnData['msg'] = "该设备已过期，没有权限拿数据";
             exit(json_encode($returnData));
         }
    }

    public function pay_mode()
    {
         $device_code = I("post.device_code");
         $this->isLogin($device_code);
         if ($this->is_security) {
             $pay_mode_model = D('pay_mode');
             $ps_condition['restaurant_id'] = session('restaurant_id');
             $pay_mode_config = $pay_mode_model->where($ps_condition)->find();
             if ($pay_mode_config == null) {
                 $pay_mode_config['mode'] = 1;
             }
             $returnData['code'] = 1;
             $returnData['msg'] = "获取数据成功";
             $returnData['data'] = $pay_mode_config['mode'];
             exit(json_encode($returnData));
         }else{
             $returnData['code'] = 0;
             $returnData['msg'] = "该设备已过期，没有权限拿数据";
             exit(json_encode($returnData));
         }
    }

    // 获取该店铺相关信息
    public function restaurantAddress(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            // 店铺相关信息
            $p_condition['restaurant_id'] = session("restaurant_id");
            $restaurant_info = D("restaurant")->where($p_condition)->find();
            $info = array();
            $info["restaurant_name"] = $restaurant_info['restaurant_name'];
            $info["address"] = $restaurant_info['address'];
            $info["telephone1"] = $restaurant_info['telephone1'];
            $info["telephone2"] = $restaurant_info['telephone2'];
            $info["subscription"] = $restaurant_info['subscription'];
            $info["down_prompt"] = $restaurant_info['down_prompt'];
            $info["take_num"] = $restaurant_info['take_num'];   // 取餐号（店铺有可能改成取餐号1234）
            $info["pay_prompt"] = $restaurant_info['pay_prompt'];   // 取餐号对应提示语
            $info["pay_num"] = $restaurant_info['pay_num'];   // 支付号号（店铺有可能改成支付号1234）
            $info["pay_prompt2"] = $restaurant_info['pay_prompt2'];   // 支付号对应提示语
            $info["desk_num"] = $restaurant_info['desk_num'];   // 餐牌号（店铺有可能改成餐牌号1234）
            $info["forward_prompt"] = $restaurant_info['forward_prompt'];   // 餐牌号对应提示语
            $info["adv_language"] = $restaurant_info['adv_language'];   // 支付成功广告语
            $info["shuping_adv_language"] = $restaurant_info['shuping_adv_language'];   // 竖屏广告语

            // 打印机小票内容是否要打印
            $restaurant_bill = D("restaurant_bill");
            $restaurant_bill_info = $restaurant_bill->where($p_condition)->find();
            unset($restaurant_bill_info['restaurant_bill_id']);
            unset($restaurant_bill_info['restaurant_id']);

            // 该店铺下的所有机器设备码
            $code_ids = D("code")->where($p_condition)->field("code_id")->select();
            $code_arr = array();
            foreach($code_ids as $val){
                $code_arr[] = $val['code_id'];
            }
            $code_condition['code_id'] = array("in",$code_arr);
            $code_condition['device_status'] = 1;
            $code_condition['end_time'] = array("gt",time());
            $devices = D("device")->where($code_condition)->field("device_code,device_id")->select();

            if($info || $restaurant_bill_info || $devices){
                $returnData['code'] = 1;
                $returnData['msg'] = "获取数据成功";
                // 店铺信息
                $returnData['restaurant_data'] = $info ? : "";
                // 小票的内容是否显示
                $returnData['print_data'] = $restaurant_bill_info ? : "";
                // 该店铺下所有的设备码
                $returnData['device_data'] = $devices ? : "";
                exit(json_encode($returnData));
            }
            $returnData['code'] = 0;
            $returnData['msg'] = "没有该店铺的信息";
            $returnData['restaurant_data'] = "";
            $returnData['print_data'] = "";
            $returnData['device_data'] = "";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['restaurant_data'] = "";
            $returnData['print_data'] = "";
            $returnData['device_data'] = "";
            exit(json_encode($returnData));
        }
    }

    // 轮询获取订单状态
    public function getOrderStatus(){
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

        $orderModel = D("order");
        $o_condition['order_sn'] = $order_sn;
        $order_status = $orderModel->where($o_condition)->field("order_status,pay_type,order_id")->find();

        if($order_status['order_status'] == 3){
            if($order_status['pay_type'] == 0 || $order_status['pay_type'] == 4){
                // 现金支付和余额支付就不使用轮询，直接在对应接口那里返回状态
                $data['code'] = 0;
                $data['pay_type'] = "";
                $data['client_order_sn'] = "";
                $data['server_order_sn'] = "";
                $data['msg'] ='未支付';
                exit(json_encode($data));
            }

            $data['code'] = 1;
            $data['pay_type'] = $order_status['pay_type'];
            $data['client_order_sn'] = $order_sn;
            $data['server_order_sn'] = $order_sn;
            $data['msg'] ='支付成功';
            exit(json_encode($data));
        }else{
            $data['code'] = 0;
            $data['pay_type'] = "";
            $data['client_order_sn'] = "";
            $data['server_order_sn'] = "";
            $data['msg'] ='未支付';
            exit(json_encode($data));
        }
    }

      // 轮询获取订单状态222
    public function getOrderStatus2(){
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

        $orderModel = D("order");
        $o_condition['order_sn'] = $order_sn;
        $order_status = $orderModel->where($o_condition)->field("order_status,pay_type,order_id")->find();

        if($order_status['order_status'] == 3){
            if($order_status['pay_type'] == 0 || $order_status['pay_type'] == 4){
                // 现金支付和余额支付就不使用轮询，直接在对应接口那里返回状态
                $data['code'] = 0;
                $data['pay_type'] = "";
                $data['msg'] ='未支付';
                exit(json_encode($data));
            }
            $data['code'] = 1;
            $data['pay_type'] = $order_status['pay_type'];
            $data['msg'] ='支付成功';
            exit(json_encode($data));
        }else{
            $data['code'] = 0;
            $data['pay_type'] = "";
            $data['msg'] ='未支付';
            exit(json_encode($data));
        }
    }

    // 获取该店铺下的所有的菜品图片和菜品名
    public function getAllImg(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            // 店铺相关信息
            $p_condition['restaurant_id'] = session("restaurant_id");

            $arr1 = D("food")->where($p_condition)->select();
            $arr = array();
            $i = 1;
            foreach($arr1 as $k1=>$v1){
                $i++;
                $i = array();
                $img_url = substr($v1['food_img'],1);
                $i["img"] = "http://".$_SERVER['HTTP_HOST'].$img_url;
                $i['food_name'] = $v1['food_name'];
                $arr[] = $i;
            }
            if(empty($arr)){
                $returnData['code'] = 0;
                $returnData['msg'] = "没有相关菜品图片信息";
                $returnData['img_data'] = "";
                exit(json_encode($returnData));
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "获取图片信息成功";
            $returnData['img_data'] = $arr;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['img_data'] = "";
            exit(json_encode($returnData));
        }
    }

    // 获取该店铺下的所有的菜品分类图标
    public function getFoodCateImg(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            // 店铺相关信息
            $p_condition['restaurant_id'] = session("restaurant_id");
            $food_category = D('food_category');
            $food_category_info = $food_category->where($p_condition)->select();
            $arr = array();
            $i = 1;
            foreach($food_category_info as $k1=>$v1){
                $i++;
                $i = array();
                // 将菜品分类图标替换为服务器的完整路径
                $i["img_url"] = "http://".$_SERVER['HTTP_HOST'].$v1['img_url'];     // 菜品分类图标id
                $i['food_category_name'] = $v1['food_category_name'];       // 菜品分类名
                $i['food_category_id'] = $v1['food_category_id'];           // 菜品分类id
                $arr[] = $i;
            }
            if(empty($arr)){
                $returnData['code'] = 0;
                $returnData['msg'] = "没有相关菜品分类图标信息";
                $returnData['img_data'] = "";
                exit(json_encode($returnData));
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "获取菜品分类图标信息成功";
            $returnData['img_data'] = $arr;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['img_data'] = "";
            exit(json_encode($returnData));
        }
    }

    // 获取该店铺下所有的菜品属性id和属性名
    public function allAttr(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            // 先获取该店铺下的所有的菜品ID
            $food_ids = D("food")->where(array("restaurant_id"=>session("restaurant_id")))->field("food_id")->select();
            $arr_fs = array();
            foreach($food_ids as $fs){
                $arr_fs[] = $fs['food_id'];
            }
            $type_condition['food_id'] = array("in",$arr_fs);
            // 找出所有的菜品属性分类
            $attr_type_ids = D("attribute_type")->where($type_condition)->field("attribute_type_id")->select();
            $atis_arr = array();
            foreach($attr_type_ids as $ati){
                $atis_arr[] = $ati['attribute_type_id'];
            }
            // 找出所有的属性
            $fa_condition['attribute_type_id'] = array("in",$atis_arr);
            $attr_info = D("food_attribute")->where($fa_condition)->field("food_attribute_id,attribute_name")->select();
            $afo = array();
            foreach($attr_info as $af){
                $afo[$af['food_attribute_id']] = $af['attribute_name'];
            }
            if(empty($afo)){
                $returnData['code'] = 0;
                $returnData['msg'] = "没有该店铺的属性信息";
                $returnData['attr_data'] = "";
                exit(json_encode($returnData));
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "获取信息成功";
            $returnData['attr_data'] = $afo;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 收银端点击云按钮，进入数据统计
    public function getData()
    {
        $device_code = I("get.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $p_condition['restaurant_id'] = session("restaurant_id");
            $admin_id = D("restaurant_manager")->where($p_condition)->getField("id");
            session('re_admin_id', $admin_id);
            $this->display("data");
        }else{
            $this->display("error");
        }
    }

    // 接收微光积分核销接口
    public function vip_score_cancel(){
        // 参数1：device_code: 设备码
        // 参数2：qr_number：积分商品的订单号（二维码里面的内容）
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $restaurant_id = session("restaurant_id");
            $score_where['restaurant_id'] = $restaurant_id;
            $score_where['type'] = 4;
            $if_open = D("set")->where($score_where)->getField("if_open");

            // 加密了的积分商品的订单号
            $score_order_id = I("post.qr_number");
            $key = C("SECRET_KEY");
            $data = $score_order_id;
            $en = new Encrypt();
            $qr_number = $en->decrypt($data,$key);
            $score_order_model = D("score_goods_order");
            //查找出订单信息
            $where['order_sn'] = $qr_number;
            $order_goods_info = $score_order_model->where($where)->find();

            // 首先判断当前会员是不是当前店铺的会员
            // 查出当前店铺所属的代理ID
            $now_business_id =  D('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
            // 查出当前会员所属的代理ID
            $now_vip_belong_business_id = $order_goods_info['business_id'];
            if($now_business_id != $now_vip_belong_business_id){
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $qr_number;
                $content["msg"]      = "您不是当前店铺的会员";
                exit(json_encode($content));
            }


            if(empty($order_goods_info)){
                // 判断订单号存不存在
                $content["type"] = "score";
                $content["code"] = "0";
                $content["order_sn"] = $qr_number ;
                $content["msg"] = "订单号不存在";
                exit(json_encode($content));
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
                    $content["code"] = "0";
                    $content["order_sn"] = $qr_number ;
                    $content["msg"] = "已经兑换过";
                    exit(json_encode($content));
                }

                //对比订单所需积分跟会员积分
                if($order_goods_info['score'] > $vip_info['score']){
                    //如果订单所需积分大于会员积分，兑换失败
                    $content["type"] = "score";
                    $content["code"] = "0";
                    $content["order_sn"] = $qr_number ;
                    $content["msg"] = "积分不足";
                    exit(json_encode($content));
                }else{
                    //如果订单积分小于等于会员积分，兑换成功
                    $vip_data['score'] = $vip_info['score'] - $order_goods_info['score'];
                    $vip_model->where($vip_where)->save($vip_data);
                    $score_goods_order_data['pay_time'] = time();
                    $score_goods_order_data['order_status'] = 1;
                    $score_goods_order_data['restaurant_id'] = $restaurant_id;
                    $score_order_model->where($where)->save($score_goods_order_data);

                    $content["type"] = "score";
                    $content["code"] = "1";
                    $content["order_sn"] = $qr_number ;
                    $goods_info = array();
                    $goods_info['order_sn'] = $order_goods_info['order_sn'];
                    $goods_info['goods_name'] = $order_goods_info['goods_name'];
                    $goods_info['score'] = $order_goods_info['score'];
                    $add_shjian = date("Y/m/d H:i:s",$order_goods_info['add_time']);
                    $goods_info['add_time'] = $add_shjian;
                    $content["goods_info"] = $goods_info ;
                    $content["msg"] = "兑换成功";
                    exit(json_encode($content));
                }
            }else{
                $content["type"] = "score";
                $content["code"] = "0";
                $content["order_sn"] = $qr_number ;
                $content["msg"] = "本店没有开启积分商品兑换";
                exit(json_encode($content));
            }
        }else{
            $content["type"] = "score";
            $content["code"] = "0";
            $content["order_sn"] = "";
            $content["msg"] = "设备已过期，没有权限拿数据";
            exit(json_encode($content));
        }
    }

    // 接收微光会员折扣
    public function vip_discount()
    {
        // 参数1：device_code 设备码
        // 参数2：qr_number  二维码内容（版本一）| 手机号（版本二）
        // 参数3：order_sn  服务器订单号
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $vip_model = D('vip');
            // 会员二维码|会员号码
            $disc_qr_number = I("qr_number");//原来传的是二维码，现在传的是手机号

            if(!is_numeric($disc_qr_number)){
                $content["type"] = "discount";
                $content["code"] = "0";
                $content["order_sn"] = "";
                $content["msg"] = "您的电话号码不合法";
                $content["after_discount_price"] = "";
                $content["wx_adress"] = "";
                $content["ali_adress"] = "";
                exit(json_encode($content));
            }

            $order_num = I("post.order_sn");
           if($order_num == null){
//                file_put_contents(__DIR__."/"."Client_discount.txt",'传递过来的订单号：'.$order_num."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

                $content["type"] = "discount";
                $content["code"] = "0";
                $content["order_sn"] = $order_num;
                $content["msg"] = "您的订单号为空";
                $content["after_discount_price"] = "";
                $content["wx_adress"] = "";
                $content["ali_adress"] = "";
                exit(json_encode($content));
            }

            $business_id = D('restaurant')->where(array("restaurant_id"=>session('restaurant_id')))->getField('business_id');
            $search['business_id'] = $business_id;
            $search['phone'] = $disc_qr_number;
            $vipData = $vip_model->where($search)->find();

            //手机号码不是会员
            if (!$vipData) {
                $content["type"] = "discount";
                $content["code"] = "0";
                $content["order_sn"] = $order_num;
                $content["msg"] = "您不是会员";
                $content["after_discount_price"] = "";
                $content["wx_adress"] = "";
                $content["ali_adress"] = "";
                exit(json_encode($content));
            }else{
                $order_model = D("order");
                $order_where['order_sn'] = $order_num;
                $disc_order_info = $order_model->where($order_where)->find();
                if(empty($disc_order_info)){
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"] = "该订单号在数据库没有对应的信息";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }

                // 判断是否已经享受过整个店铺的折扣
                if($disc_order_info['vip_or_restaurant'] == 3){
                    //折扣支付返回content
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"] = "已经享受过店铺折扣了，不能再享受会员折扣";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }

                if($disc_order_info['order_status'] == 3){
                    //折扣支付返回content
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"] = "已经支付过了";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }

                if($disc_order_info['discount'] != 0){
                    //余额支付返回content
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"] = "已经享受过折扣了";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }

                $after_discount_order_sn = I('post.after_discount_order_sn');
                if($after_discount_order_sn == null){
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = "";
                    $content["msg"] = "新的订单号为空";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }
                $if_have = D("order")->where(array('order_sn'=>$after_discount_order_sn))->getField("order_id");
                if($if_have){
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = "";
                    $content["msg"] = "新的订单号与数据库中已有订单重复";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }

                //获取会员所在分组id
                $group_id = $vipData['group_id'];//分组id
                $disc_vip_id = $vipData['id'];//会员id
                //获取会员所在分组在本店的折扣
                $restaurant_id = $disc_order_info["restaurant_id"];
                $r_disc_where["group_id"] = $group_id;
                $r_disc_where["restaurant_id"] = $restaurant_id;
                $discount_model = D('discount');
                $discount_info = $discount_model->where($r_disc_where)->find();

                /*
                 * 处理机制：
                 * 如果折扣信息为空，该订单就还是会关联该会员id的，但是折扣信息全部置为默认的值0，vip_or_restaurant为1不优惠
                 * 如果消费价格达不到折扣规则中的满多少钱，处理同上
                */
                // 折扣信息为空则不打折
                if(empty($discount_info)){
                    // 不打折
                    $order_data['vip_id'] = $disc_vip_id;
                    $order_data['discount'] = 0;
                    $order_data['original_price'] = $disc_order_info['total_amount'];
                    $order_data['order_sn'] = $after_discount_order_sn;
                    $after_discount = $disc_order_info['total_amount'];
                    // 折后价
                    $order_data['total_amount'] = $after_discount;
                    // 立减多少
                    $reduce = 0;

                    $order_data['reduce'] = 0; // 立减
                    $order_data['benefit_money'] = 0; // 优惠了多少
                    $order_data['vip_or_restaurant'] = 1; // vip_or_restaurant，1代表不优惠，2代表会员优惠，3代表整个店铺的优惠
                }else{
                    // 打折
                    $discount = $discount_info['discount'];
                    $reduce = $discount_info['reduce'];
                    $full_momey = $discount_info['money'];
                    // 默认会员组有可能没有对应的折扣信息
                    if(!$discount){
                        $discount = 10;
                    }

                    $order_data['vip_id'] = $disc_vip_id;
                    $order_data['discount'] = $discount/10; // $discount是8,8.5折这样的形式
                    $order_data['original_price'] = $disc_order_info['total_amount'];   // 原价多少
                    $order_data['order_sn'] = $after_discount_order_sn;
                    $order_data['vip_or_restaurant'] = 2; // vip_or_restaurant，1代表不优惠，2代表会员优惠，3代表整个店铺的优惠

                    // 判断是否够资格折扣
                    if($disc_order_info['total_amount']>=$full_momey){
                        $after_discount = $disc_order_info['total_amount']*$discount/10-$reduce;
                        if($after_discount<0.01){
                            $after_discount = 0.01;
                        }
                    }else{
                        // 不够资格打折的，该订单就不记录该店铺的折扣信息，并且字段vip_or_restaurant的值设为1，没打折
                        $after_discount = $disc_order_info['total_amount'];
                        $order_data['discount'] = 0;
                        $order_data['vip_or_restaurant'] = 1; // vip_or_restaurant，1代表不优惠，2代表会员优惠，3代表整个店铺的优惠
                        $reduce = 0; // 立减
                    }
                    $after_discount = round($after_discount,2); // 保留两位小数
                    // 折后价
                    $order_data['total_amount'] = $after_discount;

                    $order_data['reduce'] = $reduce; // 立减
                    $order_data['benefit_money'] = $disc_order_info['total_amount']-$after_discount; // 优惠了多少

                }

                // 重新生成订单号，更改原来没享受折扣前的订单的订单号为现在享受完折扣后的订单号
                $res = $order_model->where($order_where)->save($order_data);
                if($res === false){
                    $content["type"] = "discount";
                    $content["code"] = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"] = "享受折扣失败";
                    $content["after_discount_price"] = "";
                    $content["wx_adress"] = "";
                    $content["ali_adress"] = "";
                    exit(json_encode($content));
                }

                /*// 以下为打折成功后返回的信息
                $content["type"] = "discount";
                $content["code"] = "1";
                // 返回新生成的服务器订单号
                $content["now_order_sn"] = $after_discount_order_sn;
                // 以前的服务器订单号
                $content["begin_order_num"] = $order_num;
                $content["msg"] = "获取折扣成功";
                // 折后价
                $content["after_discount_price"] = $after_discount;
                // 原价
                $content['zongjia'] = $disc_order_info['total_amount'];
                // 优惠了多少
                $content['youhui'] = $disc_order_info['total_amount']-$after_discount;
                // 立减多少
                $content['reduce'] = $reduce;

                $content["wx_adress"] = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/WxChat/qrc/order_sn/".$after_discount_order_sn."/device_code/".$device_code;
                $content["ali_adress"] = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/AlipayDirect/alipay_code/order_sn/".$after_discount_order_sn."/device_code/".$device_code;

                file_put_contents(__DIR__."/"."Client_discount.txt",'返回去的折扣信息：'.json_encode($content,JSON_UNESCAPED_UNICODE).'，减多少钱：'.$reduce."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

               exit(json_encode($content));*/
                /*****************************判断安卓是需要民生的码还是官方的码*********************************/
                $need_which = I('post.need_which'); // 1官方  2民生
                if($need_which == 2){
                    // 需要民生的码
                    // 实例化FourthPay类的对象
                    $FourthPay = new FourthPayController();
                    // 接收参数
                    $data_arr['fourth_sn'] = I('post.fourth_sn');   // 提交给民生的订单号
                    $data_arr['order_sn'] = $after_discount_order_sn;     // 服务器订单号
                    $data_arr['public_key'] = I('post.public_key'); // 秘钥
                    $data_arr['operater_id'] = I('post.operater_id'); // 操作员ID
                    $data_arr['business_no'] = I('post.business_no');   // 商户号
                    $data_arr['device_code'] =$device_code;
                    $return = $FourthPay->pay_code_in_place_order($data_arr);
                    if($return['code'] == 1){
                        $content["type"] = "discount";
                        $content["code"] = "1";
                        // 返回新生成的服务器订单号
                        $content["now_order_sn"] = $after_discount_order_sn;
                        // 以前的服务器订单号
                        $content["begin_order_num"] = $order_num;
                        $content["msg"] = "获取折扣成功";
                        // 折后价
                        $content["after_discount_price"] = $after_discount;
                        // 原价
                        $content['zongjia'] = $disc_order_info['total_amount'];
                        // 优惠了多少
                        $content['youhui'] = $disc_order_info['total_amount']-$after_discount;
                        // 立减多少
                        $content['reduce'] = $reduce;

                        $content["wx_adress"] = $return['weixin_qr'];
                        $content["ali_adress"] = $return['ali_qr'];

//                        file_put_contents(__DIR__."/"."Client_discount.txt",'返回去的折扣信息：'.json_encode($content,JSON_UNESCAPED_UNICODE).'，减多少钱：'.$reduce."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
                    }else{
                        $content["type"] = "discount";
                        $content["code"] = "0";
                        $content["order_sn"] = $order_num;
                        $content["msg"] = $return['msg'];
                        $content["after_discount_price"] = "";
                        $content["wx_adress"] = "";
                        $content["ali_adress"] = "";
                    }
                    exit(json_encode($content));
                }else{
                    // 需要官方的码
                    // 以下为打折成功后返回的信息
                    $content["type"] = "discount";
                    $content["code"] = "1";
                    // 返回新生成的服务器订单号
                    $content["now_order_sn"] = $after_discount_order_sn;
                    // 以前的服务器订单号
                    $content["begin_order_num"] = $order_num;
                    $content["msg"] = "获取折扣成功";
                    // 折后价
                    $content["after_discount_price"] = $after_discount;
                    // 原价
                    $content['zongjia'] = $disc_order_info['total_amount'];
                    // 优惠了多少
                    $content['youhui'] = $disc_order_info['total_amount']-$after_discount;
                    // 立减多少
                    $content['reduce'] = $reduce;

                    $content["wx_adress"] = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/WxChat/qrc/order_sn/".$after_discount_order_sn."/device_code/".$device_code;
                    $content["ali_adress"] = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/AlipayDirect/alipay_code/order_sn/".$after_discount_order_sn."/device_code/".$device_code;

//                    file_put_contents(__DIR__."/"."Client_discount.txt",'返回去的折扣信息：'.json_encode($content,JSON_UNESCAPED_UNICODE).'，减多少钱：'.$reduce."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

                    exit(json_encode($content));
                }

                /*****************************判断安卓是需要民生的码还是官方的码*********************************/
            }
        }else{
            $content["type"] = "discount";
            $content["code"] = "0";
            $content["order_sn"] = "";
            $content["msg"] = "设备已过期，无权限拿数据";
            $content["after_discount_price"] = "";
            $content["wx_adress"] = "";
            $content["ali_adress"] = "";
            exit(json_encode($content));
        }
    }

    // 接收微光余额支付
    public function balance()
    {
        // 参数1：device_code  设备码
        // 参数2：qr_number  个人二维码
        // 参数3：order_sn  订单号
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            // 传递过来的服务器的订单号
            $order_num = I("post.order_sn");
            if ($order_num == null) {
                //余额支付返回content
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "订单号为空";
                exit(json_encode($content));
            }
            $order_info = D('order')->where(array('order_sn'))->find();
            if(empty($order_info)){
                //余额支付返回content
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "该订单号对应的订单信息不存在";
                exit(json_encode($content));
            }


            //客户支付处理
            $blc_qr_number  = I("qr_number");
            // var_dump($blc_qr_number);exit();
            $key            = C("SECRET_KEY");
            $en             = new Encrypt();
            $blc_qr_number2 = $en->decrypt($blc_qr_number, $key);
            $blc_vip_info = explode("|", $blc_qr_number2);
            $blc_time     = $blc_vip_info[0];
            $now_time     = time();
            $time_mistake = $now_time - $blc_time;

//            file_put_contents(__DIR__."/"."Client_balance.txt",'send_original_msg:'.$blc_qr_number.',before_decrypt_str:'.$blc_qr_number2.',after_decrypt_arr:'.json_encode($blc_vip_info,JSON_UNESCAPED_UNICODE).',order_sn:'.$order_num.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);

            if(!(is_numeric($blc_time) && is_numeric($blc_vip_info[1]))){
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "请准确扫描二维码信息";
                exit(json_encode($content));
            }

            if ($time_mistake > 1800) {
                //余额支付返回content
                $content["type"]     = "balance";
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "二维码失效";
                exit(json_encode($content));
            } else {
                //获取用户余额
                $blc_vip_id      = $blc_vip_info[1];
                $blc_where['id'] = $blc_vip_id;
                $vip_model       = D("vip");
                $vip_info      = $vip_model->where($blc_where)->field("id,username,remainder,score,total_consume,business_id")->find();
                // 首先判断当前会员是不是当前店铺的会员
                // 查出当前店铺所属的代理ID
                $now_business_id =  D('restaurant')->where(array('restaurant_id'=>session('restaurant_id')))->getField('business_id');
                // 查出当前会员所属的代理ID
                $now_vip_belong_business_id = $vip_info['business_id'];

                if($now_business_id != $now_vip_belong_business_id){
                    /*file_put_contents(__DIR__."/"."Client_balance_problem.txt",'传递过来的会员信息：'.json_encode($blc_vip_info).'，当前代理：'.$now_business_id.
                        '，会员所属代理：'.$now_vip_belong_business_id.'，数据表查出的当前会员信息：'.json_encode($vip_info)."，时间：".date("Y-m-d H:i:s",time())."，session_id：".session('restaurant_id')."，会员id:".$blc_where['id']."\r\n\r\n",FILE_APPEND);*/
                    $content["type"]     = "balance";
                    $content["code"]     = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"]      = "您不是当前店铺的会员";
                    exit(json_encode($content));
                }

                $remainder     = $vip_info['remainder'];    // 会员原有余额
                $score         = $vip_info['score'];    // 会员原有积分
                $total_consume = $vip_info['total_consume'];    // 会员原有消费总额

                //获取订单的总额
                $blc_order_where['order_sn'] = $order_num;
                $order_model                 = D("order");
                $blc_order_info              = $order_model->where($blc_order_where)->find();
                if ($blc_order_info['order_status'] >= 3) {
                    //余额支付返回content
                    $content["type"]     = "balance";
                    $content["code"]     = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"]      = "已经支付过了";
                    exit(json_encode($content));
                }
                if ($blc_order_info['total_amount'] > $remainder) {
                    //余额支付返回content
                    $content["type"]     = "balance";
                    $content["code"]     = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"]      = "余额不足，请用其他方式支付";
                    exit(json_encode($content));
                } else {
                    $order_model->startTrans();

                    // 换取积分开始      根据订单号查询店铺id
                    $o_where['order_sn'] = $order_num;
                    $restaurant_id = $order_model->where($o_where)->getField("restaurant_id");
                    $o_condition['restaurant_id'] = $restaurant_id;
                    $o_condition['type'] = 2;
                    $if_open = D("set")->where($o_condition)->getField("if_open");
                    // 判断有没有开启
                    if ($if_open) {
                        // 根据代理id查出积分设置规则
                        $business_id = D("restaurant")->where(array("restaurant_id" => $restaurant_id))->getField("business_id");
                        $rule_condition['business_id'] = $business_id;
                        $rule_condition['type'] = 2;
                        $rule = D("all_benefit")->where($rule_condition)->find();
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
                    $blc_order_data["vip_id"] = $blc_vip_id;
                    $blc_order_data["score"]  = $get_score;

                    $blc_order_data["order_status"] = 3;
                    $blc_order_data["pay_time"] = time();
                    $blc_order_data["pay_type"] = 4;
                    $order_save_rel = $order_model->where($blc_order_where)->save($blc_order_data);

                    $blc_vip_data['remainder'] = $remainder - $blc_order_info['total_amount'];
                    $save_vip_rel  = $vip_model->where($blc_where)->save($blc_vip_data);

                    if ($order_save_rel !== false && $save_vip_rel !== false) {
                        $order_status = $order_model->where($blc_order_where)->getField('order_status');
                        if($order_status == 3){
                            $order_model->commit();
                            /*file_put_contents(__DIR__."/"."Client_balance_final_check.txt",'order_sn:'.$order_num.",time:".date("Y-m-d H:i:s",time()).",session_id:".session('restaurant_id')."\r\n\r\n",FILE_APPEND);*/
                        }else{
                            $order_model->rollback();
                            $content["type"]     = "balance";
                            $content["code"]     = "0";
                            $content["order_sn"] = $order_num;
                            $content["msg"]      = "支付失败";
                            exit(json_encode($content));
                        }

                        // 售罄处理
                        $S_SellOut = new ServiceSellOut();
                        $S_SellOut->sellOutDeal($order_num);

                        // 推送开始
                        $orderInfo8 = $order_model->where($blc_order_where)->field("table_num,desk_code,restaurant_id")->find();
                        $rr_condition['restaurant_id'] = $orderInfo8['restaurant_id'];
                        $show_device_code = D("Restaurant")->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

                        if($orderInfo8['table_num'] == 0 && $orderInfo8['desk_code'] == 0){
                            $content1['tips'] = "下单成功推送showNum";
                            $content1['order_sn'] = $order_num;
                            $contentJson = json_encode($content1);
                            $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                            // 推送到单区叫号屏
                            sendMsgToDevice($post_data);
                            //推送到所有分区的叫号屏，核销屏
                            $restaurant_id = $orderInfo8['restaurant_id'];
//                            pushAllDistrict($restaurant_id,$order_num);
                        }
                        // 推送结束

                        //余额支付返回content
                        $content["type"]     = "balance";
                        $content["code"]     = "1";
                        $content["order_sn"] = $order_num;
                        $content["msg"]      = "支付成功";
                        exit(json_encode($content));
                    } else {
                        $order_model->rollback();
                        $content["type"]     = "balance";
                        $content["code"]     = "0";
                        $content["order_sn"] = $order_num;
                        $content["msg"]      = "支付失败";
                        exit(json_encode($content));
                    }
                }
            }
        }
        else {
            $content["type"]     = "balance";
            $content["code"]     = "0";
            $content["order_sn"] = "";
            $content["msg"]      = "设备已过期，无权限获取数据";
            exit(json_encode($content));
        }
    }


    /*--------------------各种屏推送接口-----------------------*/
    /**
     * 是否有全部顾客的优惠以及优惠信息
     */
    public function allCustomer()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_id = session("restaurant_id");
            //是否设置全部顾客的优惠信息
            $discountModel = D("discount");
            $where = [];
            $where['group_id'] = 0;
            $where['restaurant_id'] = $restaurant_id;
            $customerInfo = $discountModel->where($where)->find();
             //是否开启折扣
            $setModel = D("set");
            $where1 = [];
            $where1['type'] = 0;
            $where1['restaurant_id'] = $restaurant_id;
            $setInfo = $setModel->where($where1)->field('if_open')->find();
            if ($setInfo['if_open'] == 1 && $customerInfo) {
                $data = [];
                $data['money'] = $customerInfo['money'];//满多少
                $data['discount'] = $customerInfo['discount'];//打几折
                $data['reduce'] = $customerInfo['reduce'];//立减多少
                $returnData['code'] = 1;
                $returnData['msg'] = "获取顾客优惠信息成功";
                $returnData['data'] = $data;
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "非会员无优惠信息";
                $returnData['data'] = '';
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
        }
    }
    /**
     * 第四方支付信息
     */
    public function fourth()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_id = session("restaurant_id");
            $fourth_model = D('fourth');
            $fm_condition['restaurant_id'] = $restaurant_id;
            $fourth_config = $fourth_model->where($fm_condition)->find();
        
            if ($fourth_config) {
                $key            = C("F_KEY");
                $en             = new Encrypt();
                $pwd = $en->decrypt($fourth_config['pwd'], $key);
                $account = $fourth_config['account'];
                $data = [];
                $data['account'] = $account;
                $data['pwd'] = $pwd;
                $returnData['code'] = 1;
                $returnData['msg'] = "获取数据成功";
                $returnData['data'] = $data;
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "无设置的第四方信息";
                $returnData['data'] = "";
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
        }
    }
    /**
     * 获取菜品剩余份数
     */
    public function dishes_rest()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $foodModel = D("food");
            $restaurant_id = session("restaurant_id");
            
            $where = [];
            $where['restaurant_id'] = $restaurant_id;
            $foodInfo = $foodModel->field('food_id,foods_num_day')->where($where)->select();
            // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

            $Model = M(); // 实例化一个model对象 没有对应任何数据表
            foreach ($foodInfo as $k => $v) {
                $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                            `order` t2 on t1.order_id = t2.order_id and t1.food_id = {$v['food_id']} and t2.order_status in ('3','11','12')
                            and t2.pay_time between $start and $end");
                if($num){
                    $total = 0;
                    foreach($num as $n){
                        $total += $n['num'];
                    }
                }else{
                    $total = 0;
                }
                if ($v['foods_num_day']-$total >0 ) {
                    unset($foodInfo[$k]);
                }
            }
            $foodInfo = array_values($foodInfo);
            if ($foodInfo == []) {
                    $returnData['code'] = 0;
                    $returnData['msg'] = "没有售罄的菜品信息";
                    $returnData['district_data'] = $foodInfo;
                    exit(json_encode($returnData));
            }else{
                $returnData['code'] = 1;
                $returnData['msg'] = "获取售罄菜品信息成功";
                $returnData['district_data'] = $foodInfo;
                exit(json_encode($returnData));
            }
           
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['district_data'] = "";
            exit(json_encode($returnData));
        }
    }
    /**
     *统计打单（旧版，弃置）
     */
    public function statis_old()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $orderModel = D("order");
            $startDate = I("post.startDate");
            $startTime = I("post.startTime");
            $endDate = I("post.endtDate");
            $endTime = I("post.endTime");
            $restaurant_id = session("restaurant_id");
            //判断是否有时间，有则添加到查询寻条件
            if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
                $startTimeStr = strtotime($startDate." ".$startTime);
                $endTimeStr = strtotime($endDate." ".$endTime);
            }
            //是否有收银员
            $cashier_id = I("post.cashier_id");
            $cashierid = '';
            if ($cashier_id) {
                $cashier_id = intval($cashier_id);
                $cashierid .= " cashier_id=".$cashier_id." AND";
            }

            /*************************添加退单开始*******************************/
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
            //是否有收银员
            if ($cashier_id) {
                $cashier_id = intval($cashier_id);
                $condition['cashier_id'] = $cashier_id;
            }

            $orderModel = D("order");
            $condition["restaurant_id"] = session('restaurant_id');
            $condition['order_status'] = array("neq",0);
            $order_lists = $orderModel->where($condition)->group('order_sn')->order("order_id desc")->select();

            /**
             * 查询订单每个订单关联的商品信息
             */
            $refuse_num = 0;    // 退菜份数
            $refuse_total = 0;  // 退菜金额
            $order_food_model = D("order_food");
            foreach($order_lists as $key => $val){
                $condition['order_id'] = $val['order_id'];
                $food_lists = $order_food_model->where($condition)->field("order_id,food_id,food_price2,food_num,food_name,order_food_id,refuse_num")->select();
                if ($val['refuse'] == 1) {
                    $refuse_total += $val['total_amount'];
                }
                if ($val['refuse'] == 2) {
                    foreach ($food_lists as $k => $v) {
                        if ($v['refuse_num'] > 0) {
                            $refuse_total += $v['food_price2'];
                        }
                    }
                }
                foreach($food_lists as $key1=>$value1){
                    if ($val['refuse'] == 1) {
                        $refuse_num += $value1['food_num'];
                    }
                    if ($val['refuse'] == 2) {
                        $refuse_num += $value1['refuse_num'];
                    }
                }
            }
            /************************添加退单结束********************************/


            $Model = M();
             //订单信息
            $order_list = $Model->query("SELECT SUM(total_amount) total_amount,a.pay_type 
FROM (SELECT `total_amount`,pay_type,restaurant_id,order_status,add_time,cashier_id FROM `order` GROUP BY order_sn ) a WHERE
 `restaurant_id` = ".$restaurant_id." AND `order_status` <> 0 AND ".$cashierid." a.pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY a.pay_type");

            //订单总数
            /*$count = $Model->query("SELECT COUNT(*) count FROM (SELECT order_sn FROM `order` WHERE
`restaurant_id` = ".$restaurant_id." AND `order_status` <> 0 AND ".$cashierid." pay_type IN ('0','1','2','4') AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn) a");*/

            /************************添加退单开始********************************/
            //订单总数
            $count = $Model->query("SELECT order_sn,refuse FROM `order` WHERE
`restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND  pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn");

            $re_count = 0;  // 退菜单数
            foreach ($count as $k => $v) {
                if ($v['refuse'] !=0 ) {
                    $re_count++;
                }
            }
            // 订单数
            $count = count($count);
            /************************添加退单结束********************************/



            $wechat = 0;
            $alipay = 0;
            $cash   = 0;
            $member = 0;
            $fourth = 0;
            foreach ($order_list as $k => $v) {
                if ($v['pay_type'] == 0) {
                    $cash = $v['total_amount'];//现金总额
                }
                if ($v['pay_type'] == 1) {
                    $alipay = $v['total_amount'];//支付宝总额
                }
                if ($v['pay_type'] == 2) {
                    $wechat = $v['total_amount'];//微信宝总额
                }
                if ($v['pay_type'] == 4) {
                    $member = $v['total_amount'];//会员余额总额
                }
                if ($v['pay_type'] == 5) {
                    $fourth = $v['total_amount'];
                }
            }
            $statisData = [];
            $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth;
            $statisData['cash'] = $cash;
            $statisData['alipay'] = $alipay;
            $statisData['wechat'] = $wechat;
            $statisData['member'] = $member;
            $statisData['fourth'] = $fourth;
//            $statisData['count'] = $count[0]['count'];
            $statisData['count'] = $count;

            $statisData['re_count'] = $re_count;    // 退菜单数
            $statisData['refuse_num'] = $refuse_num;    // 退菜份数
            $statisData['refuse_total'] = $refuse_total;    // 退菜金额
            $statisData['after_refuse_total'] = $statisData['total']-$refuse_total;    // 退菜后的金额

            if (empty($order_list)) {
                $returnData['code'] = 0;
                $returnData['msg'] = "暂无统计数据";
                $returnData['data'] = "";
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }else{
                $returnData['code'] = 1;
                $returnData['msg'] = "获取统计数据成功";
                $returnData['data'] = $statisData;
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
        }

    }

    /**
     *统计打单
     */
    public function statis()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $orderModel = D("order");
            $startDate = I("post.startDate");
            $startTime = I("post.startTime");
            $endDate = I("post.endtDate");
            $endTime = I("post.endTime");
            $restaurant_id = session("restaurant_id");
            //判断是否有时间，有则添加到查询寻条件
            if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
                $startTimeStr = strtotime($startDate." ".$startTime);
                $endTimeStr = strtotime($endDate." ".$endTime);
            }
            //是否有收银员
            $cashier_id = I("post.cashier_id");
            $cashierid = '';
            if ($cashier_id) {
                $cashier_id = intval($cashier_id);
                $cashierid .= " cashier_id=".$cashier_id." AND";
            }

            /*************************添加退单开始*******************************/
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
            //是否有收银员
            if ($cashier_id) {
                $cashier_id = intval($cashier_id);
                $condition['cashier_id'] = $cashier_id;
            }

            $orderModel = D("order");
            $condition["restaurant_id"] = session('restaurant_id');
            $condition['order_status'] = array("neq",0);
            $order_lists = $orderModel->where($condition)->group('order_sn')->order("order_id desc")->select();

            /**
             * 查询订单每个订单关联的商品信息
             */
            $refuse_num = 0;    // 退菜份数
            $refuse_total = 0;  // 退菜金额
            $order_food_model = D("order_food");
            foreach($order_lists as $key => $val){
                $condition['order_id'] = $val['order_id'];
                $food_lists = $order_food_model->where($condition)->field("order_id,food_id,food_price2,food_num,food_name,order_food_id,refuse_num")->select();
                if ($val['refuse'] == 1) {
                    $refuse_total += $val['total_amount'];
                }
                if ($val['refuse'] == 2) {
                    foreach ($food_lists as $k => $v) {
                        if ($v['refuse_num'] > 0) {
                            $refuse_total += $v['food_price2'];
                        }
                    }
                }
                foreach($food_lists as $key1=>$value1){
                    if ($val['refuse'] == 1) {
                        $refuse_num += $value1['food_num'];
                    }
                    if ($val['refuse'] == 2) {
                        $refuse_num += $value1['refuse_num'];
                    }
                }
            }
            /************************添加退单结束********************************/


            $Model = M();


            /************************添加退单开始********************************/
            //订单总数
            $count = $Model->query("SELECT order_sn,refuse FROM `order` WHERE
`restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND  pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn");

            $re_count = 0;  // 退菜单数
            foreach ($count as $k => $v) {
                if ($v['refuse'] !=0 ) {
                    $re_count++;
                }
            }
            // 订单数
            $count = count($count);
            /************************添加退单结束********************************/

            $order_list = $Model->query("SELECT `total_amount`,pay_type,order_type,restaurant_id,order_status,add_time,cashier_id FROM `order`   WHERE
 `restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn");
            $wechat = 0;
            $alipay = 0;
            $cash   = 0;
            $member = 0;
            $fourth = 0;
            foreach ($order_list as $k => $v) {
                if ($v['pay_type'] == 0) {
                    $cash += $v['total_amount'];//现金总额
                }
                if ($v['pay_type'] == 1) {
                    $alipay += $v['total_amount'];//支付宝总额
                }
                if ($v['pay_type'] == 2) {
                    $wechat += $v['total_amount'];//微信宝总额
                }
                if ($v['pay_type'] == 4) {
                    $member += $v['total_amount'];//会员余额总额
                }
                if ($v['pay_type'] == 5) {
                    $fourth += $v['total_amount'];//第四方总额
                }
            }

            $statisData = [];
            $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth;
            $statisData['cash'] = floatval($cash);
            $statisData['alipay'] = floatval($alipay);
            $statisData['wechat'] = floatval($wechat);
            $statisData['member'] = floatval($member);
            $statisData['fourth'] = floatval($fourth);
            $statisData['count'] = $count;

            $statisData['re_count'] = $re_count;    // 退菜单数
            $statisData['refuse_num'] = $refuse_num;    // 退菜份数
            $statisData['refuse_total'] = $refuse_total;    // 退菜金额
            $statisData['after_refuse_total'] = $statisData['total']-$refuse_total;    // 退菜后的金额

            if (empty($order_list)) {
                $returnData['code'] = 0;
                $returnData['msg'] = "暂无统计数据";
                $returnData['data'] = "";
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }else{
                $returnData['code'] = 1;
                $returnData['msg'] = "获取统计数据成功";
                $returnData['data'] = $statisData;
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData,JSON_UNESCAPED_UNICODE));
        }

    }

    /**
     *菜品统计打单
     */
    public function statis_dishes()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $startDate = I("post.startDate");
            $startTime = I("post.startTime");
            $endDate = I("post.endtDate");
            $endTime = I("post.endTime");
            //判断是否有时间，有则添加到查询寻条件
            if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
                $startTimeStr = strtotime($startDate." ".$startTime);
                $endTimeStr = strtotime($endDate." ".$endTime);
            }

        $Model = M();
        $restaurant_id = session('restaurant_id');
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
            if ($cashier_id) {
                $cashier_id = intval($cashier_id);
                $cashierid .= " cashier_id=".$cashier_id." AND";
            }
        // var_dump($cashierid);    
        //菜品统计
        $all_foodinfo = $Model->query(" SELECT food_id,food_name,SUM(food_num) food_num FROM (SELECT a.order_id,food_id,food_num,food_name FROM order_food a LEFT JOIN (SELECT order_id,restaurant_id,add_time,order_status,pay_type,cashier_id from `order` GROUP BY order_sn) b
        ON a.order_id=b.order_id WHERE b.restaurant_id=".$restaurant_id." AND ".$cashierid." b.add_time >=".$startTimeStr." AND b.add_time<=".$endTimeStr." AND b.order_status <> 0 AND b.pay_type IN (0,1,2,4,5)) c GROUP BY food_id");
        //属性统计
        $attr_foodid = $Model->query("SELECT order_food_id FROM `order` a RIGHT JOIN order_food b ON a.order_id=b.order_id 
WHERE add_time>=".$startTimeStr." AND add_time<=".$endTimeStr." AND ".$cashierid." restaurant_id=".$restaurant_id." AND pay_type IN (0,1,2,4,5) AND order_status<>0 GROUP BY order_sn");
        $order_food_id = [];
        foreach ($attr_foodid as $k => $v) {
            $order_food_id[] = $v['order_food_id'];
        }
        $str = implode(',', $order_food_id);
            if($str){
                $info = $Model->query("SELECT food_attribute_name attr_name,SUM(num) attr_num FROM order_food_attribute WHERE order_food_id in (".$str.") AND count_type=1 GROUP BY food_attribute_name");
            }else{
                $info = array();
            }
//        $info = $Model->query("SELECT food_attribute_name attr_name,SUM(num) attr_num FROM order_food_attribute WHERE order_food_id in (".$str.") AND count_type=1 GROUP BY food_attribute_name");
            if (empty($all_foodinfo)) {
                $returnData['code'] = 0;
                $returnData['msg'] = "暂无统计数据";
                $returnData['data'] = "";
                exit(json_encode($returnData,JSON_UNESCAPED_UNICODE ));
            }else{
                $dishesTotle = 0;
                $dishes_attr_totle = 0;
                foreach ($all_foodinfo as $k => $v) {
                    $dishesTotle += $v['food_num'];
                }
                foreach ($info as $k => $v) {
                    $dishes_attr_totle += $v['attr_num'];
                }
                // $b = Array();
                // foreach ($allAttribute_Arr1 as $key => $value) {
                //     $b[]=Array('attr_name'=>$key,'attr_num'=>$value);
                // }
               
                // var_dump($b);exit();

                $returnData['code'] = 1;
                $returnData['msg'] = "获取统计数据成功";
                $returnData['dishes_data'] = $all_foodinfo;
                $returnData['dishes_data_totle'] = $dishesTotle;
                $returnData['dishes_attr'] = $info;
                $returnData['dishes_attr_totle'] = $dishes_attr_totle;
                exit(json_encode($returnData ,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData ,JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     *关闭订单
     */
    public function close_order(){
        // 接收设备码
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $restaurant_id = session("restaurant_id");

            // 判断当前店铺是使用官方支付还是第四方支付，1官方支付，2第四方支付
            $pay_mode = D('pay_mode');
            $mode = $pay_mode->where(array('restaurant_id'=>$restaurant_id))->getField('mode');
            if($mode == 2){
                // 第四方支付，过滤掉，不用关闭
                $returnData['code'] = 1;
                $returnData['msg'] = "关闭订单成功";
                exit(json_encode($returnData));
            }


            // 接收订单号
            $order_sn = I("post.order_sn");
            // 接收是要关微信还是支付宝，还是两者都关
            $wx = I("post.wx");
            $ali = I("post.ali");
            // 引入微信的有关文件
//            session("restaurant_id",$restaurant_id);    // 存入session，微信配置文件中有用，由于父类base已有session可取，此处不用存入
            vendor("weixinjsdk.WxPayPubHelper.WxPayPubHelper");
            // 引入支付宝的有关文件
            Vendor('alipayf2f.aop.AopClient');
            Vendor('alipayf2f.aop.request.AlipayTradeCloseRequest');

            // 是否要关闭微信，是否要关闭支付宝，1表示要关闭，2表示不要关闭
            if(empty($order_sn)){
                $returnData['code'] = 0;
                $returnData['msg'] = "订单为空";
                exit(json_encode($returnData));
            }elseif(empty($wx)){
                $returnData['code'] = 0;
                $returnData['msg'] = "微信是否关闭标识为空";
                exit(json_encode($returnData));
            }elseif(empty($ali)){
                $returnData['code'] = 0;
                $returnData['msg'] = "支付宝是否关闭标识为空";
                exit(json_encode($returnData));
            }

            // 判断是否要关闭微信
            if($wx == "1"){
                $orderClose = new \OrderClose_pub();
                $orderClose->setParameter("out_trade_no",$order_sn);//商户订单号
                //非必填参数，商户可根据实际情况选填
                $orderClose->setParameter("sub_mch_id",\WxPayConf_pub::$SUB_MCHID);//注：是主户代理申请的 这里的子商户的商户号
                //获取关闭订单结果
                $orderCloseResult = $orderClose->getResult();
                $return_code = $orderCloseResult['return_code'];
                $return_msg = $orderCloseResult['return_msg'];
            }else{
                $return_code = "SUCCESS";
            }

            // 判断是否要关闭支付宝
            if($ali == "1"){
                $aop = new \AopClient ();
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = '2017022305833230';
                $aop->rsaPrivateKey = "MIICXQIBAAKBgQCrPLze9s9rl23JubwCkh0y5TXuttAhHE98y9y/UTWhlnKaQ4x3XB9QO/vP6xZOpHC3P7u3dpSDSgzCtzeZbUONBERAMxumI/cNfw/ylu3NA6jpQk8OJeoEOqEohZku/qq8mReR6fVIAoXPHEFJXlyL41Ny97n1wCLal0fuHWHobwIDAQABAoGARFQFLZcgp1cSeQdDLWdufUuXHL0YCc5JLYwPdswJ8YOeEU5Y85vv5s04qvusuA7H52doGUoY8taOhvgjGHbQGAL1eJsAIxImiLQfqgEeeJmX2n0/gnX9RIA77eKVZVO+JbTCDLTzf4uCVb6TwTauOaVzt3ZGn2ZbP9Vfq6Lc02kCQQDV3LtM8XQ+r+uOwpfvpnUOrK6ryFRSU+7G7RLhA8hIsq9A7wc1T2oEUzpsmERozGc/qeDBru9NlcyThe1kCv97AkEAzPn9rMNMgol8Yqg8mjcRFPFhqneTLGhBWiEs4zF2ju8yvYxtYv5MgRntygwb1SL4OnkJYFeAm7zurs0kmLeOnQJBAJOSsDBlAQjszcgCIWO+YlIQ+KsTHpR81GyyVO+uc3suyd4t0rSHqyl24P7kh3glbC2zJKOh+gF4l+VIako5iJcCQGR+kEuaeLFrPKuV9hhZtStCaPLNqz9TYe8RYtOEla7gQU1DQwIM0W9eSgIMS70EZxUr8FfmrqwsRg03kKC7JdUCQQCNXOkX/UJS0bmIHAmIl17YxgXywxaPEI12bt7QWduKEkUqlDRQgrlPtrwWddO1iZOM/+PjDkvU4cKrIg65mMS1";
                $aop->alipayrsaPublicKey="MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
                $aop->apiVersion = '1.0';
                $aop->signType = 'RSA';
                $aop->postCharset='UTF-8';
                $aop->format='json';
                //第三方应用授权令牌,商户授权系统商开发模式下使用
                $restaurant_other_info = D("restaurant_other_info");
                $oti_data['restaurant_id'] = $restaurant_id;
                $appAuthToken = $restaurant_other_info->where($oti_data)->getField("app_auth_token");
                $request = new \AlipayTradeCloseRequest ();
                $request->setBizContent("{".
                    "\"out_trade_no\":\"".$order_sn."\"".
                    "}");
                $result = $aop->execute ($request,"",$appAuthToken);
                $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                $resultCode = $result->$responseNode->code;
            }else{
                $resultCode = 10000;
            }

            /*********删除没有支付的当前订单开始*********/
            $order_info = D('order')->where(array('order_sn'=>$order_sn))->field('order_id,order_status')->find();
            if($order_info['order_status'] == 3){
                $returnData['code'] = 0;
                $returnData['msg'] = "该笔订单已支付过，不能删除";
                exit(json_encode($returnData));
            }else{
                // 删除order_food表数据
                $res1 = D('order_food')->where(array('order_id'=>$order_info['order_id']))->delete();
                // 删除服务器和Android订单号关联表数据
                $res2 = D('client_order')->where(array('order_id'=>$order_info['order_id']))->delete();
                $res3 = D('order')->where(array('order_sn'=>$order_sn))->delete();
                $order_info_last = D('order')->where(array('order_sn'=>$order_sn))->getField('order_sn');
//                file_put_contents(__DIR__."/"."delete_useless_order.txt","店铺ID：".$restaurant_id."|||订单号：".$order_sn."|||删除返回结果：".$res1.'&'.$res2.'&'.$res3."|||最后订单信息：".$order_info_last."|||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
            }
            /*********删除没有支付的当前订单结束*********/


            // 总和微信和支付宝的接口返回信息，共同返回提示信息
            if((!empty($resultCode)&&($resultCode == 10000 || $resultCode == 40004)) && $return_code == "SUCCESS"){
                // 删除第四方支付二维码
                delQrcode($order_sn,1);
                // 删除第三方支付二维码
                delQrcode($order_sn,2);

                $returnData['code'] = 1;
                $returnData['msg'] = "关闭订单成功";
            } else {
                $returnData['code'] = 0;
                $returnData['msg'] = "关闭订单失败";
            }
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /********************以下是民生银行回调接口开始**********************/
    /**
     *  获取
     *  device_code  设备码
     *  order_sn   后台订单号
     *  pay_type   支付类型，1支付宝，2微信
     *  pay_time   支付时间
     */
    public function minsheng_bank_callback(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $pay_time = I('post.pay_time');
//            file_put_contents(__DIR__."/"."minsheng_callback.txt",'店铺id'.session('restaurant_id').'，数据：'.json_encode($_POST).'，安卓的pay_time：'.date("Y-m-d H:i:s",$pay_time)."，服务器时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
            $order_sn = I('post.order_sn');
//            $pay_type = I('post.pay_type');
            if($order_sn == null){
                $returnData['code'] = 0;
                $returnData['msg'] = "订单号不能为空";
                exit(json_encode($returnData));
            }
            $where['order_sn'] = $order_sn;
            $order_info = D('order')->where($where)->field('order_id,order_status')->find();
            if(!$order_info['order_id']){
                $returnData['code'] = 0;
                $returnData['msg'] = "没有该订单号对应的订单信息";
                exit(json_encode($returnData));
            }
            if($order_info['order_status'] == 3){
                $returnData['code'] = 0;
                $returnData['msg'] = "该订单已经支付过了";

//                file_put_contents(__DIR__."/"."minsheng_callback.txt",'店铺id'.session('restaurant_id').'，订单号：'.$order_sn.'，返回安卓数据：'.json_encode($returnData,JSON_UNESCAPED_UNICODE)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n\r\n",FILE_APPEND);
                exit(json_encode($returnData));
            }
            /*if($pay_type != 1 || $pay_type != 2){
                $returnData['code'] = 0;
                $returnData['msg'] = "请提交正确的支付类型";
                exit(json_encode($returnData));
            }*/
            $save['pay_type'] = 5;  // 统一写成第四方支付，5
            $save['order_status'] = 3;
            $minsheng_trade_no = I('post.minsheng_trade_no');
            if($minsheng_trade_no){
                $save['minsheng_trade_no'] = $minsheng_trade_no;
            }
            $save['pay_time'] = isset($pay_time) ? $pay_time : time();
            $res = D('order')->where($where)->save($save);
            if($res !== false){
                $returnData['code'] = 1;
                $returnData['msg'] = "回调修改状态成功";

//                file_put_contents(__DIR__."/"."minsheng_callback.txt",'店铺id'.session('restaurant_id').'，订单号：'.$order_sn.'，返回安卓数据：'.json_encode($returnData,JSON_UNESCAPED_UNICODE)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n\r\n",FILE_APPEND);
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "回调修改状态失败";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /********************以下是民生银行回调接口结束**********************/
    /*
    *获取菜时分类
    */
    public function getTimeCate() {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if(empty($this->is_security)) $this->returnJson(0, '该设备不合法，没有权限拿数据');
        $S_Category = new Category();
        $list = $S_Category->getList(array('restaurant_id'=>session("restaurant_id")));
        if($list) $this->returnJson(1, $list);
        $this->returnJson(0, '该店铺还没有添加菜时分类');
    }
}
