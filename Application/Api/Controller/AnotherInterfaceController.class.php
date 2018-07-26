<?php
namespace Api\Controller;

class AnotherInterfaceController extends BaseController
{
    // 自定义折扣优惠
    public function define_discount()
    {
        // 参数1：device_code              设备码
        // 参数2：order_sn                 自定义折扣前的订单号
        // 参数3：after_discount_order_sn  自定义折扣后的订单号
        // 参数4：after_discount_price     自定义后的价格
        // 参数5：define_discount         自定义折扣
        // 参数5：need_which               是要官方还是民生的二维码，1官方，2民生
        // 如果是民生支付，还需以下参数：
        // 1、fourth_sn：提交给民生的订单号
        // 2、public_key：秘钥
        // 3、operater_id：操作员ID
        // 4、business_no：商户号
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_num               = I("post.order_sn");    // 自定义折扣前的服务器订单号
            $after_discount_order_sn = I("post.after_discount_order_sn");    // 自定义折扣后的订单号
            $after_discount_price    = I("post.after_discount_price");    // 自定义折扣后的价钱
            $define_discount         = I("post.define_discount");    // 自定义折扣

            if (!is_numeric($define_discount) || ($define_discount < 0 || $define_discount > 1)) {
                $content["code"] = "0";
                $content["msg"]  = "您的自定义折扣不合法";
                exit(json_encode($content));
            }

            if (!is_numeric($after_discount_price) || $after_discount_price <= 0) {
                $content["code"] = "0";
                $content["msg"]  = "您的自定义折扣后的价钱不合法";
                exit(json_encode($content));
            }

            if ($order_num == null) {
//                file_put_contents(__DIR__."/"."Client_discount_define.txt",'传递过来的折扣前的订单号：'.$order_num."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "您的折扣前的订单号为空";
                exit(json_encode($content));
            }

            if ($after_discount_order_sn == null) {
//                file_put_contents(__DIR__."/"."Client_discount_define.txt",'传递过来的折扣后的订单号：'.$after_discount_order_sn."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
                $content["code"]     = "0";
                $content["order_sn"] = $after_discount_order_sn;
                $content["msg"]      = "您的折扣后的订单号为空";
                exit(json_encode($content));
            }

            // 旧订单号的检验
            $order_model             = order();
            $order_where['order_sn'] = $order_num;  // 折扣前的订单号
            $disc_order_info         = $order_model->where($order_where)->find();
            if (empty($disc_order_info)) {
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "该订单号在数据库没有对应的信息";
                exit(json_encode($content));
            }

            // 是否已支付的检验
            if ($disc_order_info['order_status'] == 3) {
                $content["code"]     = "0";
                $content["order_sn"] = $order_num;
                $content["msg"]      = "已经支付过了";
                exit(json_encode($content));
            }

            // 判断是否已经享受过自定义的折扣
            if ($disc_order_info['vip_or_restaurant'] == 4) {
                $content["code"]                 = "0";
                $content["order_sn"]             = $order_num;
                $content["msg"]                  = "已经享受过自定义折扣了，不能再享受自定义折扣";
                $content["after_discount_price"] = "";
                $content["wx_adress"]            = "";
                $content["ali_adress"]           = "";
                exit(json_encode($content));
            }
            // 判断是否已经享受过会员的折扣
            if ($disc_order_info['vip_or_restaurant'] == 2) {
                $content["code"]                 = "0";
                $content["order_sn"]             = $order_num;
                $content["msg"]                  = "已经享受过会员折扣了，不能再享受会员折扣";
                $content["after_discount_price"] = "";
                $content["wx_adress"]            = "";
                $content["ali_adress"]           = "";
                exit(json_encode($content));
            }
            // 判断新的订单号是否重复
            $if_have = order()->where(array('order_sn' => $after_discount_order_sn))->getField("order_id");
            if ($if_have) {
                $content["code"]                 = "0";
                $content["msg"]                  = "新的订单号与数据库中已有订单重复";
                $content["after_discount_price"] = $after_discount_order_sn;
                exit(json_encode($content));
            }

            // 自定义折扣优惠处理
            $order_data['define_discount']   = $define_discount; // 自定义折扣字段，$define_discount是0.8,0.85折这样的形式
            $order_data['order_sn']          = $after_discount_order_sn;
            $order_data['vip_or_restaurant'] = 4; // 1代表不优惠，2代表会员优惠，3代表整个店铺的优惠，4代表自定义折扣优惠
            $after_discount_price            = round($after_discount_price, 2);
            $order_data['total_amount']      = $after_discount_price;  // 折后价
            // 优惠了多少 = 折扣前价格 - 折扣后价格 + 之前可能还存在店铺折扣优惠的价格
            $order_data['benefit_money'] = $disc_order_info['total_amount'] - $after_discount_price + $disc_order_info['benefit_money'];

            // 重新生成订单号，更改原来的订单的订单号为现在享受完折扣后的订单号
            $res = $order_model->where($order_where)->save($order_data);
            if ($res === false) {
                $content["code"] = "0";
                $content["msg"]  = "享受折扣失败";
                exit(json_encode($content));
            }

            /*****************************判断安卓是需要民生的码还是官方的码*********************************/
            $need_which = I('post.need_which'); // 1官方  2民生
            if ($need_which == 2) {
                // 需要民生的码
                // 实例化FourthPay类的对象
                $FourthPay = new FourthPayController();
                // 接收参数
                $data_arr['fourth_sn']   = I('post.fourth_sn');   // 提交给民生的订单号
                $data_arr['order_sn']    = $after_discount_order_sn;     // 服务器订单号
                $data_arr['public_key']  = I('post.public_key'); // 秘钥
                $data_arr['operater_id'] = I('post.operater_id'); // 操作员ID
                $data_arr['business_no'] = I('post.business_no');   // 商户号
                $data_arr['device_code'] = $device_code;
                $return                  = $FourthPay->pay_code_in_place_order($data_arr);
                if ($return['code'] == 1) {
                    $content["code"] = "1";
                    // 返回新生成的服务器订单号
                    $content["now_order_sn"] = $after_discount_order_sn;
                    // 以前的服务器订单号
                    $content["begin_order_num"] = $order_num;
                    $content["msg"]             = "获取折扣成功";

                    $content["wx_adress"]  = $return['weixin_qr'];
                    $content["ali_adress"] = $return['ali_qr'];

//                    file_put_contents(__DIR__."/"."define_discount_minsheng.txt",'返回去的折扣信息：'.json_encode($content,JSON_UNESCAPED_UNICODE)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
                } else {
                    $content["code"]     = "0";
                    $content["order_sn"] = $order_num;
                    $content["msg"]      = $return['msg'];
                }
                exit(json_encode($content));
            } else {
                // 需要官方的码
                // 以下为打折成功后返回的信息
                $content["code"] = "1";
                // 返回新生成的服务器订单号
                $content["now_order_sn"] = $after_discount_order_sn;
                // 以前的服务器订单号
                $content["begin_order_num"] = $order_num;
                $content["msg"]             = "获取折扣成功";

                $content["wx_adress"]  = "http://" . $_SERVER["HTTP_HOST"] . "/index.php/vertical/WxChat/qrc/order_sn/" . $after_discount_order_sn . "/device_code/" . $device_code;
                $content["ali_adress"] = "http://" . $_SERVER["HTTP_HOST"] . "/index.php/vertical/AlipayDirect/alipay_code/order_sn/" . $after_discount_order_sn . "/device_code/" . $device_code;

//                file_put_contents(__DIR__."/"."define_discount_guanfang.txt",'返回去的折扣信息：'.json_encode($content,JSON_UNESCAPED_UNICODE)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);
                exit(json_encode($content));
            }

            /*****************************判断安卓是需要民生的码还是官方的码*********************************/

        } else {
            $content["code"] = "0";
            $content["msg"]  = "设备已过期，无权限拿数据";
            exit(json_encode($content));
        }
    }

    // 自定义折扣优惠，重新提交数据、重新生成订单
    public function define_discount_new()
    {
        $device_code = I("post.device_code");
        $cashier_id  = I("post.cashier_id");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderData            = I("post.orderData");
            $orderData            = str_replace("&quot;", "\"", $orderData);
            $orderData            = str_replace("&amp;quot;", "\"", $orderData);
            $orderDataInfo_before = json_decode($orderData);
            // 传递过来的数组永远只有一个元素，就不用做循环了，直接取第一个元素
            $orderDataInfo = $orderDataInfo_before[0];

//            file_put_contents(__DIR__."/"."receiver_order.txt",'店铺id'.session('restaurant_id').'，数据：'.json_encode($orderDataInfo)."，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);

            $table_num = I("post.table_num");
            //同步订单信息，做映射
            // 安卓的本地订单号
            $client_order = $orderDataInfo->order_sn;
            $rel          = order()->where(array('order_sn' => $client_order))->find();
            if ($rel) {
                $returnData['code']     = 2;
                $returnData['order_sn'] = "";
                $returnData['msg']      = "数据库中已有对应记录";
                exit(json_encode($returnData));
            }

            //进行订单同步，客户端订单与服务器订单做映射
            //1、生成订单
            $order_model = order();
            $order_model->startTrans(); //开启事务

            $orderInfo['order_type']    = $orderDataInfo->order_type;
            $orderInfo['add_time']      = strtotime($orderDataInfo->add_time);
            $orderInfo['restaurant_id'] = session("restaurant_id");
            $orderInfo['order_status']  = 0;
            $orderInfo['order_sn']      = $client_order;

            if ($orderDataInfo->take_num) {
                // 添加取餐号（数据库新增一个字段）
                $orderInfo['take_num'] = $orderDataInfo->take_num;
            }

            // 如果存在餐桌号，则将其记录进订单信息
            if ($table_num) {
                $orderInfo['table_num'] = str_pad($table_num, 3, "0", STR_PAD_LEFT);
            }
            //是否有收银员id
            if ($cashier_id) {
                $orderInfo['cashier_id'] = $cashier_id;
            }

            // 添加安卓本地生成的支付号
            if ($orderDataInfo->pay_num) {
                $orderInfo['zhifuhao'] = $orderDataInfo->pay_num;
            }

            $order_id = $order_model->add($orderInfo);

            /*file_put_contents(__DIR__."/"."order_info.txt",'插入的订单号为：'. $orderInfo['order_sn'].
                "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

            $total_amount = 0;
            if ($order_id !== 0 && !empty($orderDataInfo->foods)) {
                $food_model            = D("food");
                $order_food_model      = order_F();
                $order_food_attr_model = order_F_A();
                $food_attr_model       = D("food_attribute");
                $attr_type_model       = D("attribute_type");
                foreach ($orderDataInfo->foods as $f_key => $f_val) {
                    $f_where['food_id']           = $f_val->food_id;
                    $foodInfo                     = $food_model->where($f_where)->find();
                    $orderFoodData                = Array();
                    $orderFoodData['food_name']   = $foodInfo['food_name'];
                    $orderFoodData['food_price2'] = $foodInfo['food_price'] * $f_val->food_num;
                    $orderFoodData['district_id'] = $foodInfo['district_id'];
                    $orderFoodData['food_num']    = $f_val->food_num;
                    $orderFoodData['food_id']     = $f_val->food_id;
                    $orderFoodData['order_id']    = $order_id;
                    $orderFoodData['print_id']    = $foodInfo['print_id'];
                    $order_food_id                = $order_food_model->add($orderFoodData);
                    $food_price2                  = $foodInfo['food_price'] * $f_val->food_num;
                    if ($order_food_id !== false && !empty($f_val->food_attrs)) {
                        foreach ($f_val->food_attrs as $fa_key => $fa_val) {
                            $fa_where['food_attribute_id'] = $fa_val;
                            $food_attribute_info           = $food_attr_model->where($fa_where)->find();

                            $food_price2 += $food_attribute_info['attribute_price'] * $f_val->food_num;

                            $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                            $attr_type_info                 = $attr_type_model->where($atm_where)->find();

                            $orderFoodAttrData['order_food_id']        = $order_food_id;
                            $orderFoodAttrData['num']                  = $f_val->food_num;
                            $orderFoodAttrData['food_attribute_id']    = $food_attribute_info['food_attribute_id'];
                            $orderFoodAttrData['food_attribute_name']  = $food_attribute_info['attribute_name'];
                            $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price'] * $f_val->food_num;
                            $orderFoodAttrData['print_id']             = $attr_type_info['print_id'];
                            $orderFoodAttrData['count_type']           = $attr_type_info['count_type'];
                            $orderFoodAttrData['tag_print_id']         = $attr_type_info['tag_print_id'];
                            $order_food_attr_id                        = $order_food_attr_model->add($orderFoodAttrData);
                            if ($order_food_attr_id === false) {
                                $order_model->rollback();

                                $returnData['code']     = 0;
                                $returnData['order_sn'] = "";
                                $returnData['msg']      = "同步订单失败";
                                exit(json_encode($returnData));
                            }
                        }
                    }
                    //更新$food_price2
                    $orderFoodData2['food_price2']   = $food_price2;
                    $orderFoodData2['order_food_id'] = $order_food_id;
                    $order_food_model->save($orderFoodData2);

                    if ($order_food_id === false) {
                        $order_model->rollback();

                        $returnData['code']     = 0;
                        $returnData['order_sn'] = "";
                        $returnData['msg']      = "同步订单失败";
                        exit(json_encode($returnData));
                    }
                    $total_amount += $food_price2;
                }
                if ($order_id === false) {
                    $order_model->rollback();

                    $returnData['code']     = 0;
                    $returnData['order_sn'] = "";
                    $returnData['msg']      = "同步订单失败";
                    exit(json_encode($returnData));
                }
            }

            //更新$total_amount
            $orderInfo_save['original_price'] = $total_amount;   // 原价
            $orderInfo_save['order_id']       = $order_id;

            /**************自定义折扣开始*************/
            $after_discount_price = I("post.after_discount_price");    // 自定义折扣后的价钱
            $define_discount      = I("post.define_discount");    // 自定义折扣

            if (!is_numeric($define_discount) || ($define_discount < 0 || $define_discount > 1)) {
                $order_model->rollback();
                $content["code"] = "0";
                $content["msg"]  = "您的自定义折扣不合法";
                exit(json_encode($content));
            }

            if (!is_numeric($after_discount_price) || $after_discount_price <= 0) {
                $order_model->rollback();
                $content["code"] = "0";
                $content["msg"]  = "您的自定义折扣后的价钱不合法";
                exit(json_encode($content));
            }

            $after_discount_price                = round($after_discount_price, 2);
            $orderInfo_save['total_amount']      = $after_discount_price;    // 优惠后价格
            $orderInfo_save['define_discount']   = $define_discount; // 自定义折扣字段，$define_discount是0.8,0.85折这样的形式
            $orderInfo_save['vip_or_restaurant'] = 4; // 1代表不优惠，2代表会员优惠，3代表整个店铺的优惠，4代表自定义折扣优惠
            $orderInfo_save['benefit_money']     = $total_amount - $after_discount_price;   // 优惠了多少 = 折扣前价格 - 折扣后价格
            /**************自定义折扣结束*************/

            $save_res = $order_model->save($orderInfo_save);
            if ($save_res === false) {
                $order_model->rollback();
                $returnData['code']     = 0;
                $returnData['order_sn'] = "";
                $returnData['msg']      = "自定义折扣失败";
                exit(json_encode($returnData));
            }


            // 如果传递过来的微信、支付宝标识有值，则返回支付二维码
            // 调二维码之前再次判断是否已支付过
            $order_status = order()->where(array("order_sn" => $client_order))->getField("order_status");
            if ($order_status == 3) {
                $order_model->rollback();
                $returnData['code']     = 0;
                $returnData['order_sn'] = "";
                $returnData['msg']      = "此笔订单已经支付";
                exit(json_encode($returnData));
            }

            // 检测是否重单
            $order_num = order()->where(array("order_sn" => $client_order))->count();
            if ($order_num > 1) {
                $order_model->rollback();
                $returnData['code']     = 0;
                $returnData['order_sn'] = "";
                $returnData['msg']      = "此笔订单重复";
                exit(json_encode($returnData));
            }

            $order_model->commit();

            // 以下是订单同步成功后返回给安卓的信息
            $returnData['code']     = 1;
            $returnData['order_sn'] = $client_order;
            $returnData['msg']      = "订单同步成功";
            $dev_code               = I("post.device_code");

            /*****************************判断安卓是需要民生的码还是官方的码*********************************/
            $need_which = I('post.need_which'); // 1官方  2民生
            if ($need_which == 2) {
                // 需要民生的码
                // 实例化FourthPay类的对象
                $FourthPay = new FourthPayController();
                // 接收参数
                $data_arr['fourth_sn']   = I('post.fourth_sn');   // 提交给民生的订单号
                $data_arr['order_sn']    = $client_order;     // 服务器订单号
                $data_arr['public_key']  = I('post.public_key'); // 秘钥
                $data_arr['operater_id'] = I('post.operater_id'); // 操作员ID
                $data_arr['business_no'] = I('post.business_no');   // 商户号
                $data_arr['device_code'] = $device_code;
                $return                  = $FourthPay->pay_code_in_place_order($data_arr);
                if ($return['code'] == 1) {
                    $returnData['wx_adress']  = $return['weixin_qr'];
                    $returnData['ali_adress'] = $return['ali_qr'];
                } else {
                    $returnData['code']       = 0;
                    $returnData['msg']        = $return['msg'];
                    $returnData['wx_adress']  = 0;
                    $returnData['ali_adress'] = 0;
                }
                exit(json_encode($returnData));
            } else {
                // 需要官方的码
                if ($orderDataInfo->wx_url) {
                    $returnData['wx_adress'] = "http://" . $_SERVER["HTTP_HOST"] . "/index.php/vertical/WxChat/qrc/order_sn/" . $client_order . "/device_code/" . $dev_code;
                } else {
                    $returnData['wx_adress'] = 0;
                }
                if ($orderDataInfo->ali_url) {
                    $returnData['ali_adress'] = "http://" . $_SERVER["HTTP_HOST"] . "/index.php/vertical/AlipayDirect/alipay_code/order_sn/" . $client_order . "/device_code/" . $dev_code;
                } else {
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

        } else {
            $returnData['code']     = 0;
            $returnData['order_sn'] = "";
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 整单或者菜品打折立减数据
    public function discountAndReduce()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $info = M('order_or_food_discount')->where(array('restaurant_id'=>session('restaurant_id')))->field('val,discount_or_reduce')->order('id')->select();
            $discount = array();    //折扣
            $reduce = array();      // 立减
            foreach($info as $key=>$val){
                if($val['discount_or_reduce'] == 1){
                    $discount[] = $val['val'];
                }else{
                    $reduce[] = $val['val'];
                }
            }
            $returnData['code']     = 1;
            $returnData['discount'] = $discount;
            $returnData['reduce'] = $reduce;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['order_sn'] = "";
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 安卓请求会员折扣信息
    public function discountInfo()
    {
        // 参数1：device_code 设备码
        // 参数2：phone   手机号
        // 参数3：totalAmount  总价
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $totalAmount = I("post.totalAmount");
            if(!is_numeric($totalAmount)){
                $content["code"] = "0";
                $content["msg"] = "总价不合法";
                exit(json_encode($content));
            }

            $disc_qr_number = I("post.phone");//手机号
            if(!is_numeric($disc_qr_number)){
                $content["code"] = "0";
                $content["msg"] = "您的电话号码不合法";
                exit(json_encode($content));
            }

            $restaurant_id = session('restaurant_id');
            $business_id = M('restaurant')->where(array("restaurant_id"=>$restaurant_id))->getField('business_id');
            $search['business_id'] = $business_id;
            $search['phone'] = $disc_qr_number;
            $vip_model = M('vip');
            $vipData = $vip_model->where($search)->find();

           // 双模式
            /*$restaurant_id = session('restaurant_id');
            $business_id = M('restaurant')->where(array("restaurant_id"=>$restaurant_id))->getField('business_id');
            $model = M('business')->where(array('business_id'=>$business_id))->getField('vip_mode');
            if($model == 0){
                // 店铺
                $search['restaurant_id'] = session('restaurant_id');
            }else{
                // 代理
                $search['business_id'] = $business_id;
            }
            $search['phone'] = $disc_qr_number;
            $vipData = M('vip')->where($search)->find();*/

            //手机号码不是会员
            if (!$vipData) {
                $content["code"] = "2";
                $content["msg"] = "您不是会员";
                exit(json_encode($content));
            }else{
                //获取会员所在分组id
                $group_id = $vipData['group_id'];//分组id
                $disc_vip_id = $vipData['id'];//会员id
                //获取会员所在分组在本店的折扣
                $r_disc_where["group_id"] = $group_id;

                // 双模式
                /*if($model == 0){
                    // 店铺
                    $r_disc_where["restaurant_id"] = $restaurant_id;
                }else{
                    // 代理
                    $r_disc_where["business_id"] = $business_id;
                }*/

                $r_disc_where["restaurant_id"] = $restaurant_id;
                $discount_model = M('discount');
                $discount_info = $discount_model->where($r_disc_where)->find();

                // 折扣信息为空则不打折
                if(empty($discount_info)){
                    // 不打折
                    $return_data['discount'] = '0'; // 折扣
                    $return_data['reduce'] = '0'; // 立减
                    $return_data["code"] = "2";
                    $return_data["msg"] = "没有您对应的折扣信息";
                    exit(json_encode($return_data));
                }else{
                    // 打折
                    $discount = $discount_info['discount'];
                    $reduce = $discount_info['reduce'];
                    $full_momey = $discount_info['money'];
                    // 默认会员组有可能没有对应的折扣信息
                    if(!$discount){
                        $discount = 10;
                    }
                    $final_discount = $discount/10; // $discount是8,8.5折这样的形式

                    if($totalAmount>=$full_momey){
                        // 够资格折扣
                        $return_data['discount'] = $final_discount; // 折扣
                        $return_data['reduce'] = $reduce; // 立减
                        $return_data['full_money'] = $full_momey; // 满多少钱
                    }else{
                        // 不够资格打折的
                        $return_data['discount'] = '0'; // 折扣
                        $return_data['reduce'] = '0'; // 立减
                        $return_data["code"] = "2";
                        $return_data["msg"] = "您的消费额不满足折扣的条件";
                        exit(json_encode($return_data));
                    }
                }
            }
            $return_data["code"] = "1";
            $return_data["vip_id"] = $disc_vip_id;
            $return_data["msg"] = "获取数据成功";
            exit(json_encode($return_data));
        }else{
            $content["code"] = "0";
            $content["msg"] = "设备已过期，无权限拿数据";
            exit(json_encode($content));
        }
    }

    // 获取该店铺的logo
    public function getLogoImg(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            // 店铺相关信息
            $p_condition['restaurant_id'] = session("restaurant_id");
            $logo = M("restaurant")->where($p_condition)->getField('logo');
            $img_url = substr($logo,1);
            $img_url = "http://".$_SERVER['HTTP_HOST'].'/'.$img_url;
            $returnData['code'] = 1;
            $returnData['msg'] = "获取图片成功";
            $returnData['logo'] = $img_url;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['img_data'] = "";
            exit(json_encode($returnData));
        }
    }
}
