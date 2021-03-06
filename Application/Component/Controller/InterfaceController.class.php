<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/23
 * Time: 9:57
 */

namespace Component\Controller;
use Think\Controller;

class InterfaceController extends Controller
{
    /**
     * 获取打印机数据接口
     */
    public function Info(){
        $status = I('post.action');
        //串口打印机打印的数据接口
        if($status == "print_bill"){
            //获取订单信息，店铺信息，推送打印
            $order_sn = I("post.order_sn");

            $orderInfo = getOrderInfoForBill($order_sn);

            $restaurant_id = $orderInfo['restaurant_id'];
            $restaurantBillModel = D('restaurant_bill');
            $billStyle = $restaurantBillModel->where("restaurant_id = $restaurant_id")->find();

            $returnData = array();
            $data['time'] = "下单时间：".date("Y-m-d H:i:s",$orderInfo['pay_time']);
            $data['order_id'] = "订单号：".substr($orderInfo['order_sn'],-5,5);
            $data['foods'] = $orderInfo['food_list'];

            $data["price_all"] = "总计：".$orderInfo['total_amount'];
            //送餐地址，查询订单的另一张关联表。因为订单的配送地址和收货人只有在微信点餐是会体现出来，所以另外保存一张表；
//            $data["out_sell_address"] = "送餐地址:陈远银,13711151026,广州市番禺区大石镇,南大路鸿图工业园A6栋";
            $data["out_sell_address"] = "";

            //获取店铺信息
            $restaurantModel = D('Restaurant');
            $restaurantInfo = $restaurantModel->where("restaurant_id = $restaurant_id")->find();

            if($billStyle['take_num'] == 1){
                $data["take_food"] = "取餐号：".substr($orderInfo['order_sn'],-5,5);
                $data["pay_prompt"] = $restaurantInfo['pay_prompt'];//二维码信息
            }else{
                $data["take_food"] = "";
                $data["pay_prompt"] = "";
            }

            if($billStyle['pay_num'] == 1){
                if($orderInfo['pay_num'] != 0){
                    $data["pay_num"] = "支付号：".$orderInfo['pay_num'];
                    $data["pay_prompt2"] = $restaurantInfo['pay_prompt2'];//二维码信息
                }else{
                    $data["pay_num"] = "";
                    $data["pay_prompt2"] = "";
                }
            }else{
                $data["pay_num"] = "";
                $data["pay_prompt2"] = "";
            }

//            dump($orderInfo);
            // 下单时按的那个餐桌号为0  并且餐桌二维码的餐桌号不为空例如：A01-2-
            if($orderInfo['table_num'] == 0 && $orderInfo["desk_code"]){
                $data["qr_msg"] = "";
                $data["forward_prompt"] = "";
                $data["get_food_number"] = "餐桌号：".$orderInfo['desk_code'];
            }else{
                // 是否打印取餐二维码
                if($billStyle['qrcode'] == 1){
                    if($orderInfo['table_num'] == 0){
                        $data["qr_msg"] = "";//二维码信息
                        $data["forward_prompt"] = "";
                        $data["get_food_number"] = "";
                    }else{
                        $data["qr_msg"] = "";
                        $data["forward_prompt"] = $restaurantInfo['forward_prompt'];
                        $data["get_food_number"] = "餐牌号：".$orderInfo['table_num'];
                    }
                }else{
                    $data["qr_msg"] = "";//二维码信息
                    $data["forward_prompt"] = "";
                    $data["get_food_number"] = "";
                }
            }

            if($billStyle['order_type'] == 1){
                //1店吃，2打包带走，3微信外卖
                if($orderInfo['order_type'] == 1){
                    $data['order_type'] = "订单类型：堂吃";
                }else if($orderInfo['order_type'] == 2){
                    $data['order_type'] = "订单类型：外带";
                }else if($orderInfo['order_type'] == 3){
                    $data['order_type'] = "订单类型：微信外卖";
                }
            }else{
                $data['order_type'] = "";
            }

            if($orderInfo['pay_type'] == 0){
                $data['pay_type'] = "支付方式：现金";
            }else if($orderInfo['pay_type'] == 1){
                $data['pay_type'] = "支付方式：支付宝";
            }else if($orderInfo['pay_type'] == 2){
                $data['pay_type'] = "支付方式：微信";
            }else if($orderInfo['pay_type'] == 3){
                $data['pay_type'] = "支付方式：未支付";
                $data['time'] = "下单时间：".date("Y-m-d H:i:s",time());
            }elseif($orderInfo['pay_type'] == 4){
                $data['pay_type'] = "支付方式：余额支付";
            }else{
                $data['pay_type'] = "";
            }

            if($billStyle['restaurant_name'] == 1){
                $data['store_name'] = $restaurantInfo['restaurant_name'];
            }else{
                $data['store_name'] = "";
            }

            if($billStyle['address'] == 1){
                $data["store_address"] = "店铺地址：".$restaurantInfo['address'];
            }else{
                $data["store_address"] = "";
            }

            if($billStyle['restaurant_phone'] == 1){
                $data["store_phone"] = "店铺电话：".$restaurantInfo['telephone1'];
            }else{
                $data["store_phone"] = "";
            }

            if($billStyle['take_out_phone'] == 1){
                $data["store_out_sell_phone"] = "外卖电话：".$restaurantInfo['telephone2'];
            }else{
                $data["store_out_sell_phone"] = "";
            }

            if($billStyle['subscription'] == 1){
                $data["store_gong_zhong_hao"] = "点餐公众号：".$restaurantInfo['subscription'];
            }else{
                $data["store_gong_zhong_hao"] = "";
            }

            if($billStyle['down_prompt'] == 1){
                $data["down_prompt"] = $restaurantInfo['down_prompt'];
            }else{
                $data["down_prompt"] = "";
            }

            $returnData['bill'] = $data;
            exit(json_encode($returnData));
        }

        //厨房打印机获取数据接口
        if($status = "print_bill_back") {
            $order_sn = I("post.order_sn");

            $orderModel = D("order");
            $o_condition["order_sn"] = $order_sn;
            $orderInfo = $orderModel->where($o_condition)->field("order_id,pay_time,order_sn,total_amount,restaurant_id,order_status,table_num,order_type,desk_code,is_no_pay")->find();

            if ($orderInfo['order_status'] == 3 ||$orderInfo['is_no_pay'] == 1) {
                $restaurant_id = $orderInfo['restaurant_id'];

                $restaurantModel = D("Restaurant");
                $r_condition['restaurant_id'] = $restaurant_id;
                $restaurant_name = $restaurantModel->where($r_condition)->field("restaurant_name")->find();
                $restaurantName = $restaurant_name['restaurant_name'];
                $payTime = "下单时间：" . date("Y-m-d H:i:s", $orderInfo['pay_time']);
                $order_id = $orderInfo['order_id'];

                $order_food_model = D("order_food");
                $food_list = $order_food_model->where("order_id = $order_id")->field("order_food_id,food_id,food_num,food_name")->select();

                $food_attribute_model = D("order_food_attribute");
                $printerModel = D('printer');
                $order_food_ids = array();
                $foodModel = D("food");

                $print_ids = array();
                foreach ($food_list as $k => $v) {
                    $food_condition['food_id'] = $v['food_id'];
                    $f_print_id = $foodModel->where($food_condition)->field("print_id")->find()["print_id"];
                    $food_list[$k]['f_print_id'] = $f_print_id;
                    if (!in_array($f_print_id, $print_ids)) {
                        $print_ids[] = $f_print_id;
                        $p_condition['printer_id'] = $f_print_id;
                        $printer[] = $printerModel->where($p_condition)->field("printer_id,print_type,printer_ip")->find();
                    }

                    $order_food_id = $v['order_food_id'];
                    $order_food_ids[] = $order_food_id;
                    $food_attrs = $food_attribute_model->where("order_food_id = $order_food_id")->field("food_attribute_name,print_id")->select();
                    foreach ($food_attrs as $fak => $fav) {
                        $print_id = $fav['print_id'];
                        if (!in_array($print_id, $print_ids)) {
                            $print_ids[] = $print_id;
                            $p_condition['printer_id'] = $print_id;
                            $printer[] = $printerModel->where($p_condition)->field("printer_id,print_type,printer_ip")->find();
                        }
                    }
                }

                $temp = substr($order_sn, -5, 5);
                $order_sn = "订单号：" . $temp;
                $allData = array();
                foreach ($printer as $pk => $pv) {
                    $data = array();
                    $data['order_sn'] = $order_sn;
                    $data['paytime'] = $payTime;

                    $data['table_num'] = "餐牌号：".$orderInfo['table_num'];

                    if($orderInfo['desk_code']){
                        $data['table_num'] = "餐桌号：".$orderInfo['desk_code'];
                    }

//                    1店吃，2打包带走，3微信外卖
                    if($orderInfo['order_type'] == 1){
                        $data['order_type'] = "订单类型：堂吃";
                    }else if($orderInfo['order_type'] == 2){
                        $data['order_type'] = "订单类型：外带";
                    }else if($orderInfo['order_type'] == 3){
                        $data['order_type'] = "订单类型：微信外卖";
                    }

                    $data['restaurant_name'] = $restaurantName;

                    $ofm_condition['print_id'] = $pv['printer_id'];
                    $ofm_condition['order_food_id'] = array("in", $order_food_ids);
                    $o_rel = $food_attribute_model->where($ofm_condition)->select();
//                    dump($o_rel);
                    if ($o_rel){
                        if ($pv['print_type'] == 0){
                            $data["type"] = 1;
                            $data['ip'] = $pv['printer_ip'];
                            $temp_foodInfo = array();
                            foreach ($food_list as $fk => $fv){
                                if($fv['f_print_id'] == $pv['printer_id']){
                                    $temp_foodInfo[] = $fv;
                                    foreach ($o_rel as $ork => $orv) {
                                        if ($orv['order_food_id'] == $fv['order_food_id']){
                                            if ($orv['food_attribute_name']) {
                                                $temp_c = count($temp_foodInfo) - 1;
                                                $temp_foodInfo[$temp_c]['attr_arr'][] = $orv['food_attribute_name'];
                                            }
                                        }
                                    }
                                }else{
                                    foreach ($o_rel as $ork => $orv) {
                                        if ($orv['order_food_id'] == $fv['order_food_id'] && $orv['print_id'] == $pv['printer_id']){
                                            if ($orv['food_attribute_name']) {
                                                //order_food_id,food_id,food_num,food_name
                                                $temp_attr_food['order_food_id'] = $fv['order_food_id'];
                                                $temp_attr_food['food_id'] = $fv['food_id'];
                                                $temp_attr_food['food_num'] = $fv['food_num'];
                                                $temp_attr_food['food_name'] = $orv['food_attribute_name'];
                                                $temp_foodInfo[] = $temp_attr_food;
                                            }
                                        }
                                    }
                                }
                            }
                            $data['food_list'] = $temp_foodInfo;
                        }

                        if ($pv['print_type'] == 1) {
                            $data["type"] = 2;
                            $data['ip'] = $pv['printer_ip'];
                            $attr_arr = array();
                            $attr_arr_num = array();
                            foreach ($o_rel as $ark => $arv) {
                                $ofof_condition['order_food_id'] = $arv['order_food_id'];
                                $food_num = $order_food_model->where($ofof_condition)->field("food_num")->find()['food_num'];
                                if ($arv['food_attribute_name']) {
                                    $attr_arr[] = $arv['food_attribute_name'];
                                    $attr_arr_num[] = $food_num;
                                }
                            }
                            $data['attr_arr'] = $attr_arr;
                            $data['attr_arr_num'] = $attr_arr_num;
                        }
                        $allData[] = $data;
                    } else {
                        $data["type"] = 1;
                        $data['ip'] = $pv['printer_ip'];
                        $temp_foodInfo = array();
                        foreach ($food_list as $fk => $fv){
                            if($fv['f_print_id'] == $pv['printer_id']){
                                $temp_foodInfo[] = $fv;
                                foreach ($o_rel as $ork => $orv) {
                                    if ($orv['order_food_id'] == $fv['order_food_id']) {
                                        if ($orv['food_attribute_name']) {
                                            $temp_foodInfo[$fk]['attr_arr'][] = $orv['food_attribute_name'];
                                        }
                                    }
                                }
                            }
                        }
                        $data['food_list'] = $temp_foodInfo;
                        $allData[] = $data;
                    }
                }
                $returnData["data"] = $allData;
                exit(json_encode($returnData));
            }else{
                $returnData["data"] = "";
                exit(json_encode($returnData));
            }
        }
    }

    /**
     * 积分商品订单信息
     */
    public function ScoreGoodsOrderInfo(){
        $id = I("id");
        $score_goods_order_model = D("score_goods_order");
        $where['id'] = $id;
        $score_goods_order_info = $score_goods_order_model->where($where)->find();
        if(!empty($score_goods_order_info)){
            $returnData['code'] = 1;
            $returnData['msg'] = "获取成功";
            $returnData['data'] = $score_goods_order_info;
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "获取失败";
            $returnData['data'] = "";
        }
        exit(json_encode($returnData));
    }

    public function districtPrint(){
        $order_sn = I("order_sn");
        $order_model = D('order');
        $where['order_sn'] = $order_sn;
//        $where['order_sn'] = "DC0013117031511004400005";
        $rel = $order_model->where($where)->find();
        if($rel){
            if($rel['order_status'] != 3){
                $returnData['code'] = 0;
                $returnData['msg'] = "获取失败";
                $returnData['data'] = "";
                exit(json_encode($returnData));
            }
            $order_food_model = D('order_food');
            $of_where['order_id'] = $rel['order_id'];
            $food_list = $order_food_model->where($of_where)->field("food_num,food_price2,food_name,district_id")->select();
            $district_foods = [];
            //先将菜品分区
            foreach($food_list as $key => $val){
                $district_foods[$val['district_id']]['food_list'][] = $val;
            }

            $printData = array();
            foreach($district_foods as $f_key => $f_val){
                $di_where['district_id'] = $f_key;
                $district_name = D('restaurant_district')->where($di_where)->field("district_name")->find()['district_name'];
                $district_foods[$f_key]['time'] = "下单时间：".date("Y-m-d H:i:s",$rel['pay_time']);
                $district_foods[$f_key]['order_sn'] = "订单号：".substr($rel['order_sn'],-5,5);
                $district_foods[$f_key]['take_num'] = "取餐号：".substr($rel['order_sn'],-5,5);
                $district_foods[$f_key]['tips'] = "恁此票到".$district_name."取餐";
                $printData[] = $district_foods[$f_key];
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "获取成功";
            $returnData['data'] = $printData;
            exit(json_encode($returnData));
        }
    }

    /**
     * 接收打印机的状态
     * @param $device_code //打印机机器码
     * @param $status   //打印机纸张状态（ 0纸尽，1正常，2纸将尽）
     * return $rel (json字符串)
     */
    public function printerStatus(){
        $status = I("printer_status");
        $device_code = I("device_code");

        $device_model = D("device");
        if($status == 7){
            $status = 0;
        }

        if($status == 8){
            $status = 2;
        }

        $where['device_code'] = $device_code;
        $save_data['paper_status'] = $status;

        $rel = $device_model->where($where)->save($save_data);

        if($rel !== false){
//            if($status === 0){
//                $compress_device_code = num16to32(md5($device_code));
//
//                $content = "纸尽";
//                $contentJson = json_encode($content);
//                $post_data = array ("type" => "publish","to" => $compress_device_code,"content" => $contentJson);
//                $rel2 = sendMsgToDevice($post_data);
//            }

            $returnMsg['code'] = 1;
            $returnMsg['rel'] = $rel;
            exit(json_encode($returnMsg));
        }else{
            $returnMsg['code'] = 0;
            $returnMsg['rel'] = $rel;
            exit(json_encode($returnMsg));
        }
    }

    //横屏点餐机版本 钉钉
    public function versionInfo(){
        $data['version'] = "15";
        exit(json_encode($data));
    }

    public function shownumversionInfo(){
        $data['version'] = 1;
        exit(json_encode($data));
    }


    public function chckversionInfo(){
        $data['version'] = 1;
        exit(json_encode($data));
    }

    //竖屏点餐机版本
    public function versionInfo_vertical(){
        $data['version'] = "15";
        exit(json_encode($data));
    }

    //竖屏点餐原生机版本
    public function versionInfo_yuansheng(){
        $data['version'] = "56";

        exit(json_encode($data));
    }
    //收银旋转屏点餐原生机版本
    public function rotate_yuansheng(){
        $data['version'] = "68";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/new_yuanshengl.apk";
        exit(json_encode($data));
    }
    //吉仑特版本28.4
    public function now_version_Updated_vertical(){
       $data['version'] = "57";
       $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/jilunte_vertical.apk";
       exit(json_encode($data));
    }
    public function now_version_Updated_horizontal(){
       $data['version'] = "69";
       $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/jilunte_horizontal.apk";
       exit(json_encode($data));
    }
    //微信点餐机版本
    public function weixinversionInfo(){
        $data['version'] = "2";
        exit(json_encode($data));
    }

    //本地服务中心
    public function new_localserver(){
        $data['version'] = "110";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/new_localserver.apk";
        exit(json_encode($data));
    }

    //核销
    public function new_hexiao(){
        $data['version'] = "109";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/new_hexiao.apk";
        exit(json_encode($data));
    }

    //叫号
    public function new_jiaohao(){
        $data['version'] = "111";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/new_jiaohao.apk";
        exit(json_encode($data));
    }

    //粤新
    public function new_yuexin(){
        $data['version'] = "12";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/new_yuexin.apk";
        exit(json_encode($data));
    }

    // 取餐柜
    public function putmeal(){
        $data['version'] = "1";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/putmeal.apk";
        exit(json_encode($data));
    }

    //取餐屏
    public function qucangping()
    {
        $data['version'] = "7";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/qucangping.apk";
        exit(json_encode($data));
    }

    //海南航空
    public function new_hainanhangkong(){
        $data['version'] = "121";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/new_hainanhangkong.apk";
        exit(json_encode($data));
    }

    // MBImodel
    public function MBImodel(){
        $data['version'] = "101";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/MBImodel.apk";
        exit(json_encode($data));
    }
    

    //微信
    public function weixin_take_meal()//取餐屏
    {
        $data['version'] = "1";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/wechatqucangui.apk";
        exit(json_encode($data));
    }
    public function weixin_place_meal()//放餐屏
    {
        $data['version'] = "1";
        $data['domain'] = "http://".$_SERVER["HTTP_HOST"]."/wechatfangcan.apk";
        exit(json_encode($data));
    }



    //访问外网请求成功与否的接口
    public function ishttp(){
            $url = 'http://shop.founpad.com/';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);  // $resp = curl_exec($ch);
            $curl_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curl_code == 200|| $curl_code == 302) {
                $returnData['code'] = 1;
                $returnData['msg'] = "连接成功";
                exit(json_encode($returnData));
            } else {
                $returnData['code'] = 0;
                $returnData['msg'] = "连接失败";
                exit(json_encode($returnData));
            }        
    }

}
