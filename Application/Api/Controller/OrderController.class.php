<?php
namespace Api\Controller;
use data\service\Order as ServiceOrder;
use data\service\SellOut as ServiceSellOut;
use data\service\TakeMeal as ServiceTakeMeal;
use data\service\Push as ServicePush;

class orderController extends BaseController
{
    /*public function __construct()
    {
        $token = I("post.token");
        $condition['token'] = $token;
        $info = D("interface_login_check")->where($condition)->find();

        if(!$info){
            $returnData['code'] = "0";
            $returnData['msg'] = "非法访问";
            exit(json_encode($returnData));
        }
    }*/
    /*
    *获取订单详情
    */
    public function getInfo() {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $order_sn = I("order_sn");
            if(empty($order_sn)) $this->ajaxReturn(array('code'=>'0', 'msg'=> '订单编号不能为空'));

            $map['order_sn'] = $order_sn;
            $if_exist = order()->where($map)->find();
            if($if_exist['is_request'] == 0){
                $S_Order = new ServiceOrder();
                $order_info[] = $S_Order->getInfo($order_sn);
                //请求之后标识订单已请求过
                $save['is_request'] = 1;
                $where['order_sn'] = $order_sn;
                order()->where($where)->save($save);//把订单标识
                echo json_encode($order_info);
            }else{
                $returnData['code'] = '0';
                $returnData['msg'] = "该订单已打单";
                echo json_encode($returnData);
            }

        }else{
            $returnData['code'] = '0';
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }


    public function getUnRequestOrder()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $S_Order = new ServiceOrder();
            //请求之后标识订单已请求过
            $where['restaurant_id'] = $_SESSION['restaurant_id'];
            $where['is_request'] = 0;
            $where['order_status'] = 3;
            $order_list = order()->where($where)->field('order_sn')->select();
            foreach($order_list as $k=>$v){
                $order_info[] = $S_Order->getInfo($v['order_sn']);
            }

            //请求之后标识订单已请求过
            if(!empty($order_list)){
                $save['is_request'] = 1;
                $where2['restaurant_id'] = $_SESSION['restaurant_id'];
                $where2['order_status'] = 3;
                order()->where($where2)->save($save);//把订单标识
            }

            echo json_encode($order_info);
        }else{
            $returnData['code'] = '0';
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }


    /*
    *修改订单信息
    */
    public function updatInfo() {
        $this->validates();
        $order_sn = I("order_sn");
        if(empty($order_sn)) $this->returnJson('0', '订单编号不能为空');
        $S_Order = new ServiceOrder();
        $where['order_sn'] = $order_sn;
        $where['restaurant_id'] = session("restaurant_id");
        $res = $S_Order->updateInfo($where, array('order_status'=>3,'pay_time'=>time()));
        if($res) $this->returnJson("1", '修改成功');
        $this->returnJson("0", '修改失败');
    }
    # 原来的收银端开始
    //同步订单信息，本地订单与服务器订单做映射（原来的收银端）
    public function orderSynchronization(){
        $device_code = I("device_code") ? :cookie("device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $orderData = I("post.orderData");
            $orderData = str_replace("&quot;","\"",$orderData);
            $orderData = str_replace("&amp;quot;","\"",$orderData);
            $orderDataInfo = json_decode($orderData);
            //同步订单信息，做映射
            foreach ($orderDataInfo as $key => $value) {
                if(!empty($value->pay_num)){
                    if(empty($value->qr_number)){
                        $pay_order_sn =  $value->pay_num;
                        $pay_order_model = order();
                        $po_where['order_sn'] = $pay_order_sn;
                        $po_data['order_status'] = 3;
                        $po_data['pay_type'] = 0;
                        $po_data['pay_time'] = time();
                        $pay_order_model->where($po_where)->save($po_data);

                        /* // 开始
                         // 现金支付也要推送消息到各个屏
                         $orderInfo1 = order()->where($po_where)->field("table_num,desk_code,restaurant_id")->find();
                         $restaurantModel = D("Restaurant");
                         $rr_condition['restaurant_id'] = $orderInfo1['restaurant_id'];
                         $show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
                         if($orderInfo1['table_num'] == 0 && $orderInfo1['desk_code'] == 0){
                             $content['tips'] = "下单成功推送showNum";
                             $content['order_sn'] = $pay_order_sn;
                             $contentJson = json_encode($content);
                             $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                             $rel2 = sendMsgToDevice($post_data);
                             //推送到所有分区的叫号屏，核销屏
                             $restaurant_id = $orderInfo1['restaurant_id'];
                             pushAllDistrict($restaurant_id,$pay_order_sn);
                         }
                         // 结束*/


                        $returnData['code'] = 1;
                        $returnData['order_sn'] = $pay_order_sn;
                        $returnData['msg'] = "订单同步成功";
                        exit(json_encode($returnData));
                    }else{
                        $this->saoMa($value->pay_num,$value->qr_number);
                    }

                }

                /**
                 * 1、判断订单号是否存在（存在则跳出本次循环，continue）
                 * 2、进行订单同步，客户端订单与服务器订单做映射
                 * 3、判断订单信息中是否$is_pay == true（是则调用扫码枪支付接口，否则不作处理）
                 */

                $is_pay = false;

                //判断订单号是否存在（存在则跳出本次循环，continue）
                $condition['restaurant_id'] = session("restaurant_id");
                $condition['client_order_sn'] = $value->order_sn;

                $client_order_model = D("client_order");
                $rel = $client_order_model->where($condition)->find();
                if($rel){
                    $returnData['code'] = 0;
                    $returnData['order_sn'] = "";
                    $returnData['msg'] = "映射失败";
                    exit(json_encode($returnData));
                }

                //进行订单同步，客户端订单与服务器订单做映射
                //1、生成订单
                $order_model = order();
                $order_model->startTrans(); //开启事务

                $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
                $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
                $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
                $condition1['restaurant_id'] = session("restaurant_id");     //店铺id

                $num = $order_model->where($condition1)->count();        //两时间之间的订单数

                $order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单

                $orderInfo['order_type'] = $value->order_type;
                $orderInfo['add_time'] = strtotime($value->add_time);
                $orderInfo['pay_time'] = strtotime($value->add_time);
                $orderInfo['restaurant_id'] = session("restaurant_id");
                $orderInfo['order_status'] = 3;
                $orderInfo['order_sn'] = $order_sn;
                $qr_number = "";
                if($value->qr_number){
                    $is_pay = true;
                    $orderInfo['pay_time'] = strtotime($value->add_time);
                    $orderInfo['order_status'] = 0;
                    $qr_number = $value->qr_number;
                }

                $order_id = $order_model->add($orderInfo);

                $total_amount = 0;

                if($order_id !== 0 && !empty($value->foods)){
                    $food_model = D("food");
                    $order_food_model = order_F();
                    $order_food_attr_model = order_F_A();
                    $food_attr_model = D("food_attribute");
                    $attr_type_model = D("attribute_type");
                    foreach($value->foods as $f_key => $f_val){
                        $f_where['food_id'] = $f_val->food_id;
                        $foodInfo = $food_model->where($f_where)->find();
                        $orderFoodData = Array();
                        $orderFoodData['food_name'] = $foodInfo['food_name'];
//                        $orderFoodData['food_price2'] = $foodInfo['food_price'];
                        $orderFoodData['food_price2'] = $foodInfo['food_price']*$f_val->food_num;
                        $orderFoodData['district_id'] = $foodInfo['district_id'];
                        $orderFoodData['food_num'] = $f_val->food_num;
                        $orderFoodData['food_id'] = $f_val->food_id;
                        $orderFoodData['order_id'] = $order_id;
                        $order_food_id = $order_food_model->add($orderFoodData);
//                        $food_price2 = $foodInfo['food_price'];
                        $food_price2 = $foodInfo['food_price']*$f_val->food_num;
                        if($order_food_id !== false && !empty($f_val->food_attrs)){
                            foreach($f_val->food_attrs as $fa_key => $fa_val){
                                $fa_where['food_attribute_id'] = $fa_val;
                                $food_attribute_info = $food_attr_model->where($fa_where)->find();
//                                $food_price2+=$food_attribute_info['attribute_price'];
                                $food_price2+=$food_attribute_info['attribute_price']*$f_val->food_num;

                                $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                                $attr_type_info = $attr_type_model->where($atm_where)->find();

                                $orderFoodAttrData['order_food_id'] = $order_food_id;
                                $orderFoodAttrData['food_attribute_name'] = $food_attribute_info['attribute_name'];
                                $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price']*$f_val->food_num ;
                                $orderFoodAttrData['print_id'] = $attr_type_info['print_id'];
                                $orderFoodAttrData['count_type'] = $attr_type_info['count_type'];
                                $order_food_attr_id = $order_food_attr_model->add($orderFoodAttrData);
                                if($order_food_attr_id === false){
                                    $order_model->rollback();
                                }
                            }
                        }
                        //更新$food_price2
                        $orderFoodData2['food_price2'] = $food_price2;
                        $orderFoodData2['order_food_id'] = $order_food_id;
                        $order_food_model->save($orderFoodData2);

                        if($order_food_id === false){
                            $order_model->rollback();
                        }
                        $total_amount+=$food_price2;
                    }
                    if($order_id === false){
                        $order_model->rollback();
                    }
                }
                //更新$total_amount
                $orderInfo['total_amount'] = $total_amount;
                $orderInfo['order_id'] = $order_id;
                $order_model->save($orderInfo);
                $order_model->commit();

                //2、订单映射
                $clientData['client_order_sn'] =  $value->order_sn;
                $clientData['restaurant_id'] =  session('restaurant_id');
                $clientData['order_id'] =  $order_id;
                $rel = $client_order_model->add($clientData);
                if($rel === false){
                    $returnData['code'] = 0;
                    $returnData['order_sn'] = "";
                    $returnData['msg'] = "映射失败";
                    exit(json_encode($returnData));
                }

                //判断订单信息中是否$is_pay == true（是则调用扫码枪支付接口，否则不作处理）
                if($is_pay == true){
                    $this->saoMa($order_sn,$qr_number);
                }
            }
            $returnData['code'] = 1;
            $returnData['order_sn'] = $order_sn;
            $returnData['msg'] = "订单同步成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    //支付号支付  收银端输入支付号，来服务器换取订单号和订单总价（原来的收银端）
    public function payNumVerify(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $orderModel = order();
            $pay_num = I("post.pay_num");
            $restaurant_id = session("restaurant_id");

            $o_order['pay_num'] = str_pad($pay_num,3,"0",STR_PAD_LEFT);
            $o_order['restaurant_id'] = $restaurant_id;
            $start_time = mktime(0,0,0,date('m'),date("d"),date('Y'));
            $end_time = mktime(23,59,59,date('m'),date("d"),date('Y'));
            $o_order['add_time'] = array("between",array($start_time,$end_time));
            $order= $orderModel->where($o_order)->field("order_sn,order_status,total_amount")->find();

            $order_sn = $order['order_sn'];
            $order_status = $order['order_status'];
            if($order_status == 3){
                $msg['msg'] = "已支付";
                $msg['code'] = 0;
                exit(json_encode($msg));
            }

            if($order_sn){
                $msg['msg'] = "成功";
                $msg['order_sn'] = $order['order_sn'];
                $msg['total'] = $order['total_amount'];
                $msg['code'] = 1;
                exit(json_encode($msg));
            }else{
                $msg['msg'] = "支付号不存在";
                $msg['code'] = 0;
                exit(json_encode($msg));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }
    # 原来的收银端结束

    /**
     * 获取叫号屏准备中的订单
     */
    public function getPrepareOrder(){
        $restaurant_id = session("restaurant_id");
//        $restaurant_id = 131;
        $orderProcessService = new \Api\Service\OrderProcessService();
        $rel = $orderProcessService->getPrepareOrder($restaurant_id);
        if(!empty($rel)){
            $returnData['code'] = 1;
            $returnData['msg'] = "操作成功";
            $returnData['data'] = $rel;
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            $returnData['data'] = "";
        }
        exit(json_encode($returnData));
    }

    /**
     * 获取点餐页面的数据
     * 方式：post or get
     * device_code(测试或者收银端需要传递)
     * cookie('device_code')横竖屏
     */
    public function getOrderPageInfo(){
        $device_code = I("device_code") ? :cookie("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_id = session("restaurant_id");
            $food_category = D('food_category');
            $category_time = D('category_time');
            $food_time_category_id = intval(I("food_time_category_id"));
            $condition['restaurant_id'] = $restaurant_id;
            $condition['is_timing'] = 0;        # 是否定时
            $arr = $food_category->where($condition)->order('sort asc')->select();	//首先查詢未設置定時的菜品分類

            $where['restaurant_id'] = session('restaurant_id');
            $where['is_timing'] = 1;
            $food_categoryIdList =  $food_category->where($where)->field('food_category_id')->select();//然後查詢有定時，且時間段符合當前時間的分類ID
            if($food_categoryIdList){     				//如果有分类开启了定时
                $food_categoryNewIdList = array();		//开启定时的菜品分类ID集合（当前店铺）
                foreach($food_categoryIdList as $foodvv){
                    $food_categoryNewIdList[] = $foodvv['food_category_id'];
                }

                //第一种时间段的定时查询
                $current_time = time();
                $t_condition['time1'] = array("lt",$current_time);
                $t_condition['time2'] = array("gt",$current_time);//           time1<$current_time<time2
                $t_condition['category_id'] = array("in",$food_categoryNewIdList);
                # 条件是当前时间要在开始时间和结束时间之内 并且分类要开启了定时   获取它的分类ID
                $category_ids = $category_time->where($t_condition)->distinct("category_id")->field("category_id")->select();
                if($category_ids){					//存在时间段定时记录(当前店铺)
                    $category_id_list = array();
                    foreach ($category_ids as $k => $v) {   # 将分类ID再遍历出来，放到另外一个数组里面
                        $index = "cid" . $v['category_id'];
                        $category_id_list[$index] = $v['category_id'];
                    }
                }
                //第二种星期段的定时查询
                $current_week = date("w");
                $ftg_condition['timing_day'] = array("like", "%" . $current_week . "%");
                $ftg_condition['food_category_id'] = array("in",$food_categoryNewIdList);
                $category_timing_model = D("food_category_timing");
                $category_ids2 = $category_timing_model->where($ftg_condition)->distinct("food_category_id")->field("food_category_id,start_time,end_time")->select();
                $category_id_list2 = array();
                if($category_ids2){					//存在星期段定时记录(当前店铺)
                    foreach ($category_ids2 as $kk => $vv) {
                        $start_time = strtotime($vv['start_time']);
                        $end_time = strtotime($vv['end_time']);
                        if($start_time < $current_time && $end_time > $current_time){
                            $index = "cid" . $vv["food_category_id"];
                            $category_id_list2[$index] = $vv["food_category_id"];
                        }
                    }
                }

                //两种定时情况结果合并
                if($category_id_list == null){
                    $categoryIdsList = $category_id_list2;
                }else if($category_id_list2 == null){
                    $categoryIdsList = $category_id_list;
                }else{
                    $categoryIdsList = array_merge($category_id_list, $category_id_list2);
                }
                $lastCategoryIdsList = array();
                foreach ($categoryIdsList as $vvv) {
                    $lastCategoryIdsList[] = $vvv;
                }
                if($lastCategoryIdsList){                 //存在两种情况合并的结果,查询出开启定时且符合条件的分类集合
                    $l_condition['food_category_id'] = array("in", $lastCategoryIdsList);
                    $arr2 = $food_category->where($l_condition)->order('sort asc')->select();
                    $arr = array_merge($arr, $arr2);      //合并未开启定时与开启了定时且符合条件的菜品分类结果
                }
            }

            $food = D('food');
            $prom = D('prom');				  //处理时价，若菜品在时价范围内，前端显示时价时的价格
            $food_category_relative = D('food_category_relative');      # 食物和食物分类关联表
            if($arr){                                 //如果存在菜品分类
                foreach($arr as $vkey =>$vinfo){
                    // 将菜品分类图标替换为服务器的完整路径
                    $arr[$vkey]["img_url"] = "http://".$_SERVER['HTTP_HOST'].$vinfo['img_url'];

                    $foodIdArr = array();				  //存在菜品分类所对应的菜品信息集合
                    $where1['food_category_id'] = $vinfo['food_category_id'];
                    $foodIdList = $food_category_relative->where($where1)->field('food_id')->select();		#　在食物与食物分类表中根据分类ＩＤ查询食物ＩＤ
                    foreach($foodIdList as $fil){
                        // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
                       /* $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
                        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

                        $Model = M(); // 实例化一个model对象 没有对应任何数据表
                        $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $fil[food_id] and t2.order_status in ('3','11','12')
                        and t2.pay_time between $start and $end");


                        if($num){
// // //                            dump($fil['food_id']);
//                             $sum = 0;
//                             foreach($num as $n){
//                                 $sum += $n['num'];
//                             }
// //                            dump($sum);

                            // 查询出该food_id对应多少限额
                            // $fit_num = D("food")->where(array("food_id"=>$fil['food_id']))->getField("foods_num_day");
                            // dump($fit_num);
                            // if($sum < $fit_num){
                                $foodIdArr[] = $fil['food_id'];     # 将食物ID放到一个数组里面
                            // }
                            //  dump($num);
                        }else{
                            $foodIdArr[] = $fil['food_id'];     # 将食物ID放到一个数组里面
                        }*/
                        $foodIdArr[] = $fil['food_id'];     # 将食物ID放到一个数组里面
                    }
                    $arr1=[];
                    if($foodIdArr){
                        $f_condition['is_sale'] = 1;	  //1:上架
                        $f_condition['food_id'] = array("in",$foodIdArr);
                        if($food_time_category_id > 0) $f_condition['_string'] = "FIND_IN_SET($food_time_category_id, time_category)";
                        $arr1 = $food->where($f_condition)->order('sort asc')->select();

                        foreach($arr1 as $k1=>$v1){
                            if($v1['is_prom'] == 1){	  //1：开启时价
                                $where2['prom_id'] = $v1['food_id'];    #　时价id = 食物id
                                $when_time = time();
                                $where2['prom_start_time'] = array("lt",$when_time);
                                $where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
                                $prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
                                if($prom_price){		  //如果存在符合条件的时价
                                    $food_price = $prom_price;      #　食物的价格就等于时价
                                }else{
                                    $food_price = $v1['food_price'];    #　食物的价格就等于自身原价
                                }
                            }else{
                                $food_price = $v1['food_price'];
                            }
                            $arr1[$k1]['food_price'] = $food_price;

                            $attribute_type = D("attribute_type");
                            $attr_where['food_id'] = $v1['food_id'];
                            $foodAttrs = $attribute_type->where($attr_where)->select();
                            $food_attribute_model = D("food_attribute");
                            foreach($foodAttrs as $fak => $fav){
                                $fd_where['attribute_type_id'] = $fav['attribute_type_id'];
                                $atr_items = $food_attribute_model->where($fd_where)->select();
                                $foodAttrs[$fak]['attr_items'] = $atr_items;
                            }
                            $arr1[$k1]["foodAttrs"] = $foodAttrs;
                            $img_url = substr($v1['food_img'],1);
                            $arr1[$k1]["food_img"] = "http://".$_SERVER['HTTP_HOST'].$img_url;

                            // 判断是否是今天范围内的售罄
                            if($v1['is_shutdown'] == 1){
                                $return = $this->is_shutdown($v1['update_time']);
                                if($return){
                                    // 未售罄
                                    $arr1[$k1]['is_shutdown'] = 0;
                                }
                            }
                        }
                    }
                    $arr[$vkey]["food_list"] = $arr1;

                }
            }
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

    // 判断是否是今天范围内的售罄
    public function is_shutdown($update_time)
    {
        $Date = date('Y-m-d',time());
        $startTime = '00:00:00';
        $endTime = '23:59:59';
        $startTimeStr = strtotime($Date." ".$startTime);
        $endTimeStr = strtotime($Date." ".$endTime);
        // 但是不是在今天范围内售罄，而是昨天的售罄，则还是属于未售罄
        if(!($startTimeStr<$update_time && $update_time<$endTimeStr)) {
            return true;
        }else{
            return false;
        }
    }

    // 扫码枪和微光支付
    public function client_saoMa()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            // 服务器订单号
            $order_sn = I("post.order_sn");
            // 扫码枪扫出的条形码
            $tiao_xing_ma = I("post.tiao_xing_ma");
            $this->saoMa($order_sn,$tiao_xing_ma);
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 收银台现金支付（单纯的现金）
    public function cash_pay_only()
    {
        $device_code = I("post.device_code");
        $cashier_id = I("post.cashier_id");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderData = I("post.orderData");
            $orderData = str_replace("&quot;","\"",$orderData);
            $orderData = str_replace("&amp;quot;","\"",$orderData);
            $orderDataInfo_before = json_decode($orderData);
            // 传递过来的数组永远只有一个元素，就不用做循环了，直接取第一个元素
            $orderDataInfo = $orderDataInfo_before[0];

            // 根据传递过来的安卓本地的订单号判断服务器有没有关联的订单信息
            $client_order = $orderDataInfo->order_sn;

            if($client_order == null){
                $returnData['code'] = 0;
                $returnData['order_sn'] = $client_order;
                $returnData['pay_type'] = 0;    // 支付类型
                $returnData['msg'] = "订单号为空";
                exit(json_encode($returnData));
            }

            $order_info = order()->where(array('order_sn'=>$client_order))->find();

            // 如果服务器订单表没有对应的订单数据（之前没网，现在有网了，提交过来），则先重新生成订单
            if(empty($order_info)){
                //进行订单同步，客户端订单与服务器订单做映射
                //1、生成订单
                $order_model = order();
                $order_model->startTrans(); //开启事务

                $orderInfo['order_type'] = $orderDataInfo->order_type;
                $orderInfo['add_time'] = strtotime($orderDataInfo->add_time);
                $orderInfo['pay_time'] = strtotime($orderDataInfo->add_time);
                $orderInfo['restaurant_id'] = session("restaurant_id");
                $orderInfo['order_status'] = 3;
                $orderInfo['pay_type'] = 0;
                $orderInfo['order_sn'] = $client_order;
                //是否有收银员id
                if ($cashier_id) {
                    $orderInfo['cashier_id'] =  $cashier_id;
                }
                // 添加取餐号（数据库新增一个字段）
                if($orderDataInfo->take_num){
                    $orderInfo['take_num'] = $orderDataInfo->take_num;
                }

                $order_id = $order_model->add($orderInfo);

                $total_amount = 0;

                if($order_id !== 0 && !empty($orderDataInfo->foods)){
                    $food_model = D("food");
                    $order_food_model = order_F();
                    $order_food_attr_model = order_F_A();
                    $food_attr_model = D("food_attribute");
                    $attr_type_model = D("attribute_type");
                    $is_foods_empty = 0; //用于判断食物为空的次数
                    foreach($orderDataInfo->foods as $f_key => $f_val){
                        $f_where['food_id'] = $f_val->food_id;
                        $foodInfo = $food_model->where($f_where)->find();
                        //判断查询出来的菜品是否为空数据
                        if(!empty($foodInfo)) {      //不为空就插入数据库order_food
                            $orderFoodData = Array();
                            $orderFoodData['food_name'] = $foodInfo['food_name'];
//                        $orderFoodData['food_price2'] = $foodInfo['food_price'];
                            $orderFoodData['food_price2'] = $foodInfo['food_price']*$f_val->food_num;
                            $orderFoodData['district_id'] = $foodInfo['district_id'];
                            $orderFoodData['food_num'] = $f_val->food_num;
                            $orderFoodData['food_id'] = $f_val->food_id;
                            $orderFoodData['order_id'] = $order_id;
                            $order_food_id = $order_food_model->add($orderFoodData);
//                        $food_price2 = $foodInfo['food_price'];
                            $food_price2 = $foodInfo['food_price']*$f_val->food_num;
                            if($order_food_id !== false && !empty($f_val->food_attrs)){
                                foreach($f_val->food_attrs as $fa_key => $fa_val){
                                    $fa_where['food_attribute_id'] = $fa_val;
                                    $food_attribute_info = $food_attr_model->where($fa_where)->find();
//                                $food_price2+=$food_attribute_info['attribute_price'];
                                    $food_price2+=$food_attribute_info['attribute_price']*$f_val->food_num;

                                    $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                                    $attr_type_info = $attr_type_model->where($atm_where)->find();

                                    $orderFoodAttrData['order_food_id'] = $order_food_id;
                                    $orderFoodAttrData['num'] = $f_val->food_num;
                                    $orderFoodAttrData['food_attribute_name'] = $food_attribute_info['attribute_name'];
                                    $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price']*$f_val->food_num ;
                                    $orderFoodAttrData['print_id'] = $attr_type_info['print_id'];
                                    $orderFoodAttrData['count_type'] = $attr_type_info['count_type'];
                                    $order_food_attr_id = $order_food_attr_model->add($orderFoodAttrData);
                                    if($order_food_attr_id === false){
                                        $order_model->rollback();
                                        $returnData['code'] = 0;
                                        $returnData['order_sn'] = "";
                                        $returnData['pay_type'] = 0;    // 支付类型
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
                                $returnData['order_sn'] = "";
                                $returnData['msg'] = "同步失败";
                                $returnData['pay_type'] = 0;    // 支付类型
                                exit(json_encode($returnData));
                            }
                            $total_amount+=$food_price2;
                        }else{
                            //记录空食物的次数
                            $is_foods_empty = $is_foods_empty + 1;
                        }

                    }

                    if($order_id === false){
                        $order_model->rollback();
                        $returnData['code'] = 0;
                        $returnData['order_sn'] = "";
                        $returnData['pay_type'] = 0;    // 支付类型
                        $returnData['msg'] = "同步失败";
                        exit(json_encode($returnData));
                    }
                }

                //更新$total_amount
                if($is_foods_empty != 0){ //等于0说明食物没有空
                    $total_amount = $orderDataInfo->origin_price;//原价拿安卓那边的
                }

                //更新$total_amount
                $orderInfo_save['total_amount'] = $total_amount;       // 最终价
                $orderInfo_save['original_price'] = $total_amount;   // 原价
                $orderInfo_save['order_id'] = $order_id;

                /**************店铺折扣和整单或者菜品折扣开始*************/
                // 判断是否享受了店铺优惠和整单折扣或者菜品折扣，1、if_enjoy_benefits 店铺优惠，3、整单折扣或者菜品折扣,4、会员折扣
                if($orderDataInfo->if_enjoy_benefits == 1 || $orderDataInfo->if_enjoy_benefits == 3 || $orderDataInfo->if_enjoy_benefits == 4){
                    /*file_put_contents(__DIR__."/"."placeOrder_restaurant_discount.txt",'是否享受优惠为：'. $orderDataInfo->if_enjoy_benefits.
                        '，订单号为：'. $client_order.
                        '，原价为：'. $orderDataInfo->origin_price.
                        '，享受优惠后的价格为：'. $orderDataInfo->after_benefit_price.
                        '，优惠了多少：'. $orderDataInfo->benefit_money.
                        '，打了多少折：'. $orderDataInfo->discount.
                        '，立减多少：'. $orderDataInfo->reduce.
                        "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

                    $orderInfo_save['original_price'] = $orderDataInfo->origin_price;   // 原价
                    $orderInfo_save['total_amount'] = $orderDataInfo->after_benefit_price;   // 享受优惠后的价格
                    $orderInfo_save['benefit_money'] = $orderDataInfo->benefit_money;   // 优惠了多少
                    $orderInfo_save['discount'] = $orderDataInfo->discount;   // 打了多少折
                    $orderInfo_save['reduce'] = $orderDataInfo->reduce;   // 立减多少
                    // 区分是会员折扣还是整个店铺的折扣，vip_or_restaurant，1不打折，2会员折扣，3整个店铺折扣，4自定义折扣（另外有接口），5整单或者菜品折扣
                    if($orderDataInfo->if_enjoy_benefits == 1){
                        $vip_or_restaurant = 3; // 3整个店铺折扣
                    }elseif($orderDataInfo->if_enjoy_benefits == 3){
                        $vip_or_restaurant = 5; // 5整单或者菜品折扣
                    }else{
                        $vip_or_restaurant = 2; // 2会员折扣
                        $orderInfo_save['vip_id'] = $orderDataInfo->vip_id;;   // 订单关联会员id
                    }
                    $orderInfo_save['vip_or_restaurant'] = $vip_or_restaurant;
                }elseif($orderDataInfo->if_enjoy_benefits == 2){
                    $orderInfo_save['total_amount'] = $total_amount+floatval($orderDataInfo->surcharge);    // 优惠后价格
                }

                /**************店铺折扣和整单或者菜品折扣结束*************/

                /**************附加费开始*************/
                $orderInfo_save['extra_charge'] = floatval($orderDataInfo->surcharge);
                /**************附加费结束*************/

                $order_model->save($orderInfo_save);
                $order_model->commit();

                // 售罄处理
                $S_SellOut = new ServiceSellOut();
                $S_SellOut->sellOutDeal($client_order);

                // 取餐柜推送
                $S_TakeMeal = new ServiceTakeMeal();
                $S_TakeMeal->takeMealPush($client_order,1,array(1,3));

                // 推送开始
                $blc_order_where['order_sn'] = $client_order;
//                $orderInfo8 = order()->where($blc_order_where)->field("table_num,desk_code,restaurant_id")->find();
                $orderInfo8 = order()->where($blc_order_where)->field("table_num,desk_code,restaurant_id,minsheng_post_no")->find();
                $rr_condition['restaurant_id'] = $orderInfo8['restaurant_id'];
                $show_device_code = D("Restaurant")->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

                // 删除第三方支付二维码
                delQrcode($client_order,2);
                // 删除第四方支付二维码
                delQrcode($orderInfo8['minsheng_post_no'],1);

                if($orderInfo8['table_num'] == 0 && $orderInfo8['desk_code'] == 0){
                    $content1['tips'] = "下单成功推送showNum";
                    $content1['order_sn'] = $client_order;
                    $contentJson = json_encode($content1);
                    $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                    // 推送到单区叫号屏
                    sendMsgToDevice($post_data);
                    //推送到所有分区的叫号屏，核销屏
                    $restaurant_id = $orderInfo8['restaurant_id'];
//                        pushAllDistrict($restaurant_id,$order_sn);
                }
                // 推送结束

                $returnData['code'] = 1;
                $returnData['order_sn'] = $client_order;
                $returnData['pay_type'] = 0;    // 支付类型为现金支付
                $returnData['msg'] = "订单同步成功";
                exit(json_encode($returnData));
            }else{
                // 服务器有对应的订单数据，直接现金，跳过重新生成订单，关联订单
                if($order_info['order_status'] == 3){
                    $returnData['code'] = 2;
                    $returnData['order_sn'] = $client_order;
                    $returnData['pay_type'] = 0;    // 支付类型
                    $returnData['msg'] = "已经支付过了";
                    exit(json_encode($returnData));
                }

                $pay_order_model = order();
                $po_where['order_sn'] = $client_order;
                $po_data['order_status'] = 3;
                $po_data['pay_type'] = 0;
                $po_data['pay_time'] = strtotime($orderDataInfo->add_time);
                $pay_order_model->where($po_where)->save($po_data);

                // 售罄处理
                $S_SellOut = new ServiceSellOut();
                $S_SellOut->sellOutDeal($client_order);

                // 推送开始
                $blc_order_where['order_sn'] = $client_order;
                $orderInfo8 = order()->where($blc_order_where)->field("table_num,desk_code,restaurant_id,minsheng_post_no")->find();
                $rr_condition['restaurant_id'] = $orderInfo8['restaurant_id'];
                $show_device_code = D("Restaurant")->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

                // 删除第三方支付二维码
                delQrcode($client_order,2);
                // 删除第四方支付二维码
                delQrcode($orderInfo8['minsheng_post_no'],1);

                if($orderInfo8['table_num'] == 0 && $orderInfo8['desk_code'] == 0){
                    $content1['tips'] = "下单成功推送showNum";
                    $content1['order_sn'] = $client_order;
                    $contentJson = json_encode($content1);
                    $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                    // 推送到单区叫号屏
                    sendMsgToDevice($post_data);
                    //推送到所有分区的叫号屏，核销屏
                    $restaurant_id = $orderInfo8['restaurant_id'];
                    pushAllDistrict($restaurant_id,$client_order);
                }
                // 推送结束

                $returnData['code'] = 1;
                $returnData['order_sn'] = $client_order;
                $returnData['pay_type'] = 0;    // 支付类型
                $returnData['msg'] = "订单同步成功";

                //查询推送模式然后进行推送
                $order_sn = $client_order;
                $push = new ServicePush();
                $push_type = $push->pushType();
                if($push_type == 2){ //核销屏的推送模式
                    $push->pushOneScreen($order_sn);
                }elseif($push_type == 3){ //取餐柜的推送模式
                    $push->pushOneCupboard($order_sn);
                }else{
                    //普通模式不用推
                }

                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['pay_type'] = 0;    // 支付类型
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 阿里钉钉会员支付
    public function dingding_vip()
    {
        $device_code = I("post.device_code");
//        $cashier_id = I("post.cashier_id");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $orderData = I("post.orderData");
            $orderData = str_replace("&quot;","\"",$orderData);
            $orderData = str_replace("&amp;quot;","\"",$orderData);
            $orderDataInfo_before = json_decode($orderData);
            // 传递过来的数组永远只有一个元素，就不用做循环了，直接取第一个元素
            $orderDataInfo = $orderDataInfo_before[0];

            // 根据传递过来的安卓本地的订单号判断服务器有没有关联的订单信息
            $client_order = $orderDataInfo->order_sn;

            if($client_order == null){
                $returnData['code'] = 0;
                $returnData['order_sn'] = $client_order;
                $returnData['pay_type'] = 6;    // 支付类型
                $returnData['msg'] = "订单号为空";
                exit(json_encode($returnData));
            }

            $order_info = order()->where(array('order_sn'=>$client_order))->find();

            // 如果服务器订单表没有对应的订单数据（之前没网，现在有网了，提交过来），则先重新生成订单
            if(empty($order_info)){
                //进行订单同步，客户端订单与服务器订单做映射
                //1、生成订单
                $order_model = order();
                $order_model->startTrans(); //开启事务

                $orderInfo['order_type'] = $orderDataInfo->order_type;
                $orderInfo['add_time'] = strtotime($orderDataInfo->add_time);
                $orderInfo['pay_time'] = strtotime($orderDataInfo->add_time);
                $orderInfo['restaurant_id'] = session("restaurant_id");
                $orderInfo['order_status'] = 3;
                $orderInfo['pay_type'] = 6;
                $orderInfo['order_sn'] = $client_order;
                $orderInfo['ali_order_sn'] = I("post.ali_order_sn");//阿里的订单号
                //是否有收银员id
//                if ($cashier_id) {
//                    $orderInfo['cashier_id'] =  $cashier_id;
//                }
                // 添加取餐号（数据库新增一个字段）
                if($orderDataInfo->take_num){
                    $orderInfo['take_num'] = $orderDataInfo->take_num;
                }

                $order_id = $order_model->add($orderInfo);

                $total_amount = 0;

                if($order_id !== 0 && !empty($orderDataInfo->foods)){
                    $food_model = D("food");
                    $order_food_model = order_F();
                    $order_food_attr_model = order_F_A();
                    $food_attr_model = D("food_attribute");
                    $attr_type_model = D("attribute_type");
                    foreach($orderDataInfo->foods as $f_key => $f_val){
                        $f_where['food_id'] = $f_val->food_id;
                        $foodInfo = $food_model->where($f_where)->find();
                        $orderFoodData = Array();
                        $orderFoodData['food_name'] = $foodInfo['food_name'];
//                        $orderFoodData['food_price2'] = $foodInfo['food_price'];
                        $orderFoodData['food_price2'] = $foodInfo['food_price']*$f_val->food_num;
                        $orderFoodData['district_id'] = $foodInfo['district_id'];
                        $orderFoodData['food_num'] = $f_val->food_num;
                        $orderFoodData['food_id'] = $f_val->food_id;
                        $orderFoodData['order_id'] = $order_id;
                        $order_food_id = $order_food_model->add($orderFoodData);
//                        $food_price2 = $foodInfo['food_price'];
                        $food_price2 = $foodInfo['food_price']*$f_val->food_num;
                        if($order_food_id !== false && !empty($f_val->food_attrs)){
                            foreach($f_val->food_attrs as $fa_key => $fa_val){
                                $fa_where['food_attribute_id'] = $fa_val;
                                $food_attribute_info = $food_attr_model->where($fa_where)->find();
//                                $food_price2+=$food_attribute_info['attribute_price'];
                                $food_price2+=$food_attribute_info['attribute_price']*$f_val->food_num;

                                $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                                $attr_type_info = $attr_type_model->where($atm_where)->find();

                                $orderFoodAttrData['order_food_id'] = $order_food_id;
                                $orderFoodAttrData['num'] = $f_val->food_num;
                                $orderFoodAttrData['food_attribute_name'] = $food_attribute_info['attribute_name'];
                                $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price']*$f_val->food_num ;
                                $orderFoodAttrData['print_id'] = $attr_type_info['print_id'];
                                $orderFoodAttrData['count_type'] = $attr_type_info['count_type'];
                                $order_food_attr_id = $order_food_attr_model->add($orderFoodAttrData);
                                if($order_food_attr_id === false){
                                    $order_model->rollback();
                                    $returnData['code'] = 0;
                                    $returnData['order_sn'] = "";
                                    $returnData['pay_type'] = 0;    // 支付类型
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
                            $returnData['order_sn'] = "";
                            $returnData['msg'] = "同步失败";
                            $returnData['pay_type'] = 0;    // 支付类型
                            exit(json_encode($returnData));
                        }
                        $total_amount+=$food_price2;
                    }
                    if($order_id === false){
                        $order_model->rollback();
                        $returnData['code'] = 0;
                        $returnData['order_sn'] = "";
                        $returnData['pay_type'] = 6;    // 支付类型
                        $returnData['msg'] = "同步失败";
                        exit(json_encode($returnData));
                    }
                }
                //更新$total_amount
                $orderInfo_save['total_amount'] = $total_amount;       // 最终价
                $orderInfo_save['original_price'] = $total_amount;   // 原价
                $orderInfo_save['order_id'] = $order_id;

                /**************店铺折扣和整单或者菜品折扣开始*************/
                // 判断是否享受了店铺优惠和整单折扣或者菜品折扣，1、if_enjoy_benefits 店铺优惠，3、整单折扣或者菜品折扣,4、会员折扣
                if($orderDataInfo->if_enjoy_benefits == 1 || $orderDataInfo->if_enjoy_benefits == 3 || $orderDataInfo->if_enjoy_benefits == 4){
                    /*file_put_contents(__DIR__."/"."placeOrder_restaurant_discount.txt",'是否享受优惠为：'. $orderDataInfo->if_enjoy_benefits.
                        '，订单号为：'. $client_order.
                        '，原价为：'. $orderDataInfo->origin_price.
                        '，享受优惠后的价格为：'. $orderDataInfo->after_benefit_price.
                        '，优惠了多少：'. $orderDataInfo->benefit_money.
                        '，打了多少折：'. $orderDataInfo->discount.
                        '，立减多少：'. $orderDataInfo->reduce.
                        "，时间：".date("Y-m-d H:i:s",time())."\r\n\r\n",FILE_APPEND);*/

                    $orderInfo_save['original_price'] = $orderDataInfo->origin_price;   // 原价
                    $orderInfo_save['total_amount'] = $orderDataInfo->after_benefit_price;   // 享受优惠后的价格
                    $orderInfo_save['benefit_money'] = $orderDataInfo->benefit_money;   // 优惠了多少
                    $orderInfo_save['discount'] = $orderDataInfo->discount;   // 打了多少折
                    $orderInfo_save['reduce'] = $orderDataInfo->reduce;   // 立减多少
                    // 区分是会员折扣还是整个店铺的折扣，vip_or_restaurant，1不打折，2会员折扣，3整个店铺折扣，4自定义折扣（另外有接口），5整单或者菜品折扣
                    if($orderDataInfo->if_enjoy_benefits == 1){
                        $vip_or_restaurant = 3; // 3整个店铺折扣
                    }elseif($orderDataInfo->if_enjoy_benefits == 3){
                        $vip_or_restaurant = 5; // 5整单或者菜品折扣
                    }else{
                        $vip_or_restaurant = 2; // 2会员折扣
                        $orderInfo_save['vip_id'] = $orderDataInfo->vip_id;;   // 订单关联会员id
                    }
                    $orderInfo_save['vip_or_restaurant'] = $vip_or_restaurant;
                }elseif($orderDataInfo->if_enjoy_benefits == 2){
                    $orderInfo_save['total_amount'] = $total_amount+floatval($orderDataInfo->surcharge);    // 优惠后价格
                }

                /**************店铺折扣和整单或者菜品折扣结束*************/

                /**************附加费开始*************/
                $orderInfo_save['extra_charge'] = floatval($orderDataInfo->surcharge);
                /**************附加费结束*************/

                $order_model->save($orderInfo_save);
                $order_model->commit();

                // 售罄处理
                $S_SellOut = new ServiceSellOut();
                $S_SellOut->sellOutDeal($client_order);

                // 取餐柜推送
                $S_TakeMeal = new ServiceTakeMeal();
                $S_TakeMeal->takeMealPush($client_order,1,array(1,3));

                // 推送开始
                $blc_order_where['order_sn'] = $client_order;
//                $orderInfo8 = order()->where($blc_order_where)->field("table_num,desk_code,restaurant_id")->find();
                $orderInfo8 = order()->where($blc_order_where)->field("table_num,desk_code,restaurant_id,minsheng_post_no")->find();
                $rr_condition['restaurant_id'] = $orderInfo8['restaurant_id'];
                $show_device_code = D("Restaurant")->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

                // 删除第三方支付二维码
                delQrcode($client_order,2);
                // 删除第四方支付二维码
                delQrcode($orderInfo8['minsheng_post_no'],1);

                if($orderInfo8['table_num'] == 0 && $orderInfo8['desk_code'] == 0){
                    $content1['tips'] = "下单成功推送showNum";
                    $content1['order_sn'] = $client_order;
                    $contentJson = json_encode($content1);
                    $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                    // 推送到单区叫号屏
                    sendMsgToDevice($post_data);
                    //推送到所有分区的叫号屏，核销屏
                    $restaurant_id = $orderInfo8['restaurant_id'];
//                        pushAllDistrict($restaurant_id,$order_sn);
                }
                // 推送结束

                $returnData['code'] = 1;
                $returnData['order_sn'] = $client_order;
                $returnData['pay_type'] = 6;    // 支付类型为阿里会员支付
                $returnData['msg'] = "订单同步成功";
                exit(json_encode($returnData));
            }else{
                // 服务器有对应的订单数据，直接现金，跳过重新生成订单，关联订单
                if($order_info['order_status'] == 3){
                    $returnData['code'] = 2;
                    $returnData['order_sn'] = $client_order;
                    $returnData['pay_type'] = 0;    // 支付类型
                    $returnData['msg'] = "已经支付过了";
                    exit(json_encode($returnData));
                }

                $pay_order_model = order();
                $po_where['order_sn'] = $client_order;
                $po_data['order_status'] = 3;
                $po_data['pay_type'] = 0;
                $po_data['pay_time'] = strtotime($orderDataInfo->add_time);
                $pay_order_model->where($po_where)->save($po_data);

                // 售罄处理
                $S_SellOut = new ServiceSellOut();
                $S_SellOut->sellOutDeal($client_order);

                // 推送开始
                $blc_order_where['order_sn'] = $client_order;
                $orderInfo8 = order()->where($blc_order_where)->field("table_num,desk_code,restaurant_id,minsheng_post_no")->find();
                $rr_condition['restaurant_id'] = $orderInfo8['restaurant_id'];
                $show_device_code = D("Restaurant")->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

                // 删除第三方支付二维码
                delQrcode($client_order,2);
                // 删除第四方支付二维码
                delQrcode($orderInfo8['minsheng_post_no'],1);

                if($orderInfo8['table_num'] == 0 && $orderInfo8['desk_code'] == 0){
                    $content1['tips'] = "下单成功推送showNum";
                    $content1['order_sn'] = $client_order;
                    $contentJson = json_encode($content1);
                    $post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
                    // 推送到单区叫号屏
                    sendMsgToDevice($post_data);
                    //推送到所有分区的叫号屏，核销屏
                    $restaurant_id = $orderInfo8['restaurant_id'];
                    pushAllDistrict($restaurant_id,$client_order);
                }
                // 推送结束

                $returnData['code'] = 1;
                $returnData['order_sn'] = $client_order;
                $returnData['pay_type'] = 0;    // 支付类型
                $returnData['msg'] = "订单同步成功";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['pay_type'] = 0;    // 支付类型
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 支付号支付的第二步处理（现金或者扫码）
    public function payNumSecond(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $orderData = I("post.orderData");
            $orderData = str_replace("&quot;","\"",$orderData);
            $orderData = str_replace("&amp;quot;","\"",$orderData);
            $orderDataInfo_before = json_decode($orderData);
            // 传递过来的数组永远只有一个元素，就不用做循环了，直接取第一个元素
            $orderDataInfo = $orderDataInfo_before[0];

            // 根据传递过来的安卓本地的订单号判断服务器有没有关联的订单信息
            $client_order_sn = $orderDataInfo->order_sn;    // 安卓本地订单
            $condition_client['restaurant_id'] = session("restaurant_id");
            $condition_client['client_order_sn'] = $client_order_sn;
            $client_order_model = D("client_order");
            // 根据安卓本地订单号查出关联的服务器订单信息
            $order_id = $client_order_model->where($condition_client)->getField("order_id");
            if($order_id){
                $order_info = order()->where(array("order_id"=>$order_id))->field("order_sn,order_status")->find();
            }

            // 如果服务器订单表没有对应的订单数据（之前没网，现在有网了，提交过来），则先重新生成订单
            if(empty($order_id) && empty($order_info)){
                //进行订单同步，客户端订单与服务器订单做映射
                //1、生成订单
                $order_model = order();
                $order_model->startTrans(); //开启事务

                $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
                $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
                $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
                $condition1['restaurant_id'] = session("restaurant_id");     //店铺id

                $num = $order_model->where($condition1)->count();        //两时间之间的订单数

                $order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单

                $orderInfo['order_type'] = $orderDataInfo->order_type;
                $orderInfo['add_time'] = strtotime($orderDataInfo->add_time);
                $orderInfo['pay_time'] = strtotime($orderDataInfo->add_time);
                $orderInfo['restaurant_id'] = session("restaurant_id");
                $orderInfo['order_status'] = 3;
                $orderInfo['order_sn'] = $order_sn;

                // 添加取餐号（数据库新增一个字段）
                if($orderDataInfo->take_num){
                    $orderInfo['take_num'] = $orderDataInfo->take_num;
                }

                $qr_number = "";
                $is_pay = false;
                // 如果有支付条形码(qr_number)（没有服务器关联订单，所以没有pay_num），说明是支付号扫码支付（不是现金），做个标记，支付状态改0，等扫码回调后处理
                if($orderDataInfo->qr_number){
                    $is_pay = true;
                    $orderInfo['pay_time'] = strtotime($orderDataInfo->add_time);
                    $orderInfo['order_status'] = 0;
                    $qr_number = $orderDataInfo->qr_number;
                }

                $order_id = $order_model->add($orderInfo);

                $total_amount = 0;

                if($order_id !== 0 && !empty($orderDataInfo->foods)){
                    $food_model = D("food");
                    $order_food_model = order_F();
                    $order_food_attr_model = order_F_A();
                    $food_attr_model = D("food_attribute");
                    $attr_type_model = D("attribute_type");
                    foreach($orderDataInfo->foods as $f_key => $f_val){
                        $f_where['food_id'] = $f_val->food_id;
                        $foodInfo = $food_model->where($f_where)->find();
                        $orderFoodData = Array();
                        $orderFoodData['food_name'] = $foodInfo['food_name'];
//                        $orderFoodData['food_price2'] = $foodInfo['food_price'];
                        $orderFoodData['food_price2'] = $foodInfo['food_price']*$f_val->food_num;
                        $orderFoodData['district_id'] = $foodInfo['district_id'];
                        $orderFoodData['food_num'] = $f_val->food_num;
                        $orderFoodData['food_id'] = $f_val->food_id;
                        $orderFoodData['order_id'] = $order_id;
                        $order_food_id = $order_food_model->add($orderFoodData);
//                        $food_price2 = $foodInfo['food_price'];
                        $food_price2 = $foodInfo['food_price']*$f_val->food_num;
                        if($order_food_id !== false && !empty($f_val->food_attrs)){
                            foreach($f_val->food_attrs as $fa_key => $fa_val){
                                $fa_where['food_attribute_id'] = $fa_val;
                                $food_attribute_info = $food_attr_model->where($fa_where)->find();
//                                $food_price2+=$food_attribute_info['attribute_price'];
                                $food_price2+=$food_attribute_info['attribute_price']*$f_val->food_num;

                                $atm_where['attribute_type_id'] = $food_attribute_info['attribute_type_id'];
                                $attr_type_info = $attr_type_model->where($atm_where)->find();

                                $orderFoodAttrData['order_food_id'] = $order_food_id;
                                $orderFoodAttrData['food_attribute_name'] = $food_attribute_info['attribute_name'];
                                $orderFoodAttrData['food_attribute_price'] = $food_attribute_info['attribute_price']*$f_val->food_num ;
                                $orderFoodAttrData['print_id'] = $attr_type_info['print_id'];
                                $orderFoodAttrData['count_type'] = $attr_type_info['count_type'];
                                $order_food_attr_id = $order_food_attr_model->add($orderFoodAttrData);
                                if($order_food_attr_id === false){
                                    $order_model->rollback();
                                    $returnData['code'] = 0;
                                    $returnData['order_sn'] = "";
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
                            $returnData['order_sn'] = "";
                            $returnData['msg'] = "同步失败";
                            exit(json_encode($returnData));
                        }
                        $total_amount+=$food_price2;
                    }
                    if($order_id === false){
                        $order_model->rollback();
                        $returnData['code'] = 0;
                        $returnData['order_sn'] = "";
                        $returnData['msg'] = "同步失败";
                        exit(json_encode($returnData));
                    }
                }
                //更新$total_amount
                $orderInfo['total_amount'] = $total_amount;
                $orderInfo['order_id'] = $order_id;
                $order_model->save($orderInfo);

                //2、订单映射
                $clientData['client_order_sn'] =  $orderDataInfo->order_sn;    // 安卓生成的订单号
                $clientData['restaurant_id'] =  session('restaurant_id');
                $clientData['order_id'] =  $order_id;
                $rel = $client_order_model->add($clientData);
                if($rel === false){
                    $order_model->rollback();
                    $returnData['code'] = 0;
                    $returnData['order_sn'] = "";
                    $returnData['msg'] = "关联失败";
                    exit(json_encode($returnData));
                }
                $order_model->commit();

                //判断订单信息中是否$is_pay == true（是则调用扫码枪支付接口，否则不作处理）
                if($is_pay == true){
                    $this->saoMa($order_sn,$qr_number);
                }

                $returnData['code'] = 1;
                $returnData['order_sn'] = $order_sn;
                $returnData['pay_type'] = 0;    // 支付类型为现金支付
                $returnData['msg'] = "订单同步成功";
                exit(json_encode($returnData));
            }else{
                // 服务器有对应的订单数据，直接现金或者扫码，跳过重新生成订单，关联订单
                if($order_info['order_status'] == 3){
                    $returnData['code'] = 0;
                    $returnData['order_sn'] = "";
                    $returnData['msg'] = "已经支付过了";
                    exit(json_encode($returnData));
                }

                $pay_order_sn =  $order_info['order_sn'];   // 服务器订单号
                // 条形码为空，则是现金支付，不为空则是扫码支付
                if(empty($orderDataInfo->qr_number)){
                    $pay_order_model = order();
                    $po_where['order_sn'] = $pay_order_sn;
                    $po_data['order_status'] = 3;
                    $po_data['pay_type'] = 0;
                    $po_data['pay_time'] = time();
                    $pay_order_model->where($po_where)->save($po_data);
                    $returnData['code'] = 1;
                    $returnData['order_sn'] = $pay_order_sn;
                    $returnData['pay_type'] = 0;    // 支付类型
                    $returnData['msg'] = "订单同步成功";
                    exit(json_encode($returnData));
                }else{
                    $this->saoMa($pay_order_sn,$orderDataInfo->qr_number);
                }
            }
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /**
     * 扫码枪支付
     * @param $order_sn
     * @param $qr_number
     */
    public function saoMa($order_sn,$qr_number){
        //调用扫码枪支付接口$order_sn;$qr_number;
        $url = "http://".$_SERVER["HTTP_HOST"]."/index.php/home/wxChat/microPay";
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
        file_put_contents('./saoma1.log', 'aaaaaaa');
        //打印获得的数据
        if($output){
            $returnData['code'] = 1;
            $returnData['order_sn'] = $order_sn;
            $pay_type = order()->where(array("order_sn"=>$order_sn))->getField("pay_type");
            $returnData['pay_type'] = $pay_type;    // 返回支付类型
            $returnData['msg'] = "支付成功";

            file_put_contents('./saoma2.log','bbbbb');
            //查询推送模式然后进行推送
            $restaurant_id = order()->where(array("order_sn" => $order_sn))->getField("restaurant_id");
            $_SESSION['restaurant_id'] = $restaurant_id;
            $push = new ServicePush();
            $push_type = $push->pushType();
            if($push_type == 2){ //核销屏的推送模式
                $push->pushOneScreen($order_sn);
            }elseif($push_type == 3){ //取餐柜的推送模式
                $push->pushOneCupboard($order_sn);
            }else{
                //普通模式不用推
            }

            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "支付失败";
            exit(json_encode($returnData));
        }
    }
}