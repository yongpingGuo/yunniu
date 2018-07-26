<?php
namespace Admin\Controller;
use Think\Controller;

class SaleController extends Controller {

    public function __construct(){
        Controller::__construct();
        $admin_id = session("re_admin_id");
        if(!$admin_id){
            redirect("Index/login");
        }
        $restaurant_manager_model = D('restaurant_manager');
        $restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
        session('restaurant_id',$restaurant_id);
    }

    //明细查询入口页面
    public function index(){
        $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
        $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）

        $startDate = date("Y-m-d",$beginThisMonth);
        $this->assign("startDate",$startDate);

        $endDate = date("Y-m-d",$endThisMonth);
        $this->assign("endDate",$endDate);

        $startTime = "00:00:00";
        $endTime = "23:59:59";

        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }else{
            $startTimeStr = $beginThisMonth;
            $endTimeStr = $endThisMonth;
        }

        $condition['pay_type'] = array("in",'0,1,2,4,5,6');		//第三个条件：支付方式      新增一个余额支付

        $condition['order_type'] = array("in",'1,2,3');
        $restaurant_id = session('restaurant_id');
        $condition["restaurant_id"] = $restaurant_id;
        $condition['order_status'] = array("neq",0);

        // 分表统计总数
        $sql_orignal="SELECT
                        SUM(total_amount) AS tp_sum
                    FROM
                        `tabName`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ('0', '1', '2', '4', '5','6')
                    AND `order_type` IN ('1', '2', '3')
                    AND `restaurant_id` = $restaurant_id
                    AND `order_status` <> 0
                    LIMIT 1";
        $all_total_amount = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');
//        dump($all_total_amount);
        $all_total_amount = number_format($all_total_amount,2);
//        dump($all_total_amount);
        $this->assign("total_amount",$all_total_amount);
        //获取收银员列表
        $cashierModel = D("cashier");
        $where = [];
        $where['restaurant_id'] = session('restaurant_id');
        $cashierList = $cashierModel->where($where)->field("cashier_name,cashier_id")->select();
        $this->assign('cashierList', $cashierList);

        $this->display();
    }

    //明细查询异步获取订单列表
    public function orderInfo(){
        /**
         * 搜索条件
         */
        $restaurant_id = session('restaurant_id');
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");
        $condition = array();
        $startDate = empty($startDate) ? date('Y-m-d',time()) : $startDate;
        $endDate = empty($endDate) ? $startDate : $endDate;
        $startTime = empty($startTime) ? '00:00:00' : $startTime;
        $endTime = empty($endTime) ? '23:59:59' : $endTime;
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);
        $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));

        //是否有收银员
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        $cashieridJoin = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
           $condition['cashier_id'] = $cashier_id;

            $cashierid .= " cashier_id=".$cashier_id." AND";
            $cashieridJoin .= " t1.cashier_id=".$cashier_id." AND";
        }

 
        //支付类型
        $pay_type = I("post.pay_type");
        $paytype = array(0,1,2,4,5,6);
        if($pay_type == 99){
            $condition['pay_type'] = array("in",$paytype);
            $paytypeStr = '0,1,2,4,5,6';
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $condition['pay_type'] = array("in",$pay_type);
            $paytypeStr = $pay_type;
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }
        //就餐方式
        $order_type = I("post.order_type");
        $ordertype = array(1,2,3);
        if($order_type == 99){
            $condition['order_type'] = array("in",$ordertype);
            $ordertypeStr = '1,2,3';
            $sqlOrderType = '1,2,3';  // 用于分表查询条件
        }else{
            $condition['order_type'] = array("in",$order_type);
            $ordertypeStr = $order_type;
            $sqlOrderType = $order_type;  // 用于分表查询条件
        }
        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
           $condition['refuse'] = 0;
           $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        $orderModel = order();
        $condition["restaurant_id"] = session('restaurant_id');
		$condition['order_status'] = array("neq",0);

        /**
         * 分页查询订单数据
         */
        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 25;
        $condition["restaurant_id"] = session('restaurant_id');
		$condition['order_status'] = array("neq",0);

        // 分表统计总数
        $sql_orignal="SELECT COUNT(*) AS tp_count FROM tabName WHERE `add_time` BETWEEN $startTimeStr AND $endTimeStr AND `pay_type` IN ($sqlPayType) AND `order_type` IN ($sqlOrderType) AND ".$cashierid." `refuse` IN (".$refuseStr.") AND `restaurant_id` = $restaurant_id AND `order_status` <> 0 LIMIT 1";
        $count = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_count');

        // 满足条件的分表订单结果集（order和order_food连表查询）
        $sql_orignal="SELECT t1.order_id,t1.refuse,t1.total_amount,t2.`food_price2`,t2.`food_num`,t2.`refuse_num` FROM `tabName1` t1 RIGHT JOIN tabName2 t2 on t1.order_id = t2.order_id WHERE t1.`add_time` BETWEEN $startTimeStr AND $endTimeStr AND t1.`pay_type` IN ($sqlPayType)
                    AND t1.`order_type` IN ($sqlOrderType) AND ".$cashieridJoin." t1.`refuse` IN (".$refuseStr.") AND t1.`restaurant_id` = $restaurant_id AND t1.`order_status` <> 0 ORDER BY t1.order_id DESC";

        $order_lists = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

        $sqlList = "SELECT
                        *
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND $cashierid `refuse` IN ($refuseStr)
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn
                    ORDER BY
                        order_id DESC
                    ";
        // 分页数据结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sqlList,2,($page-1)*$page_num,$page_num);

        // 获取order表的数据（不连表）
       /* $sqlOrderList="SELECT * FROM `tabName1` WHERE `add_time` BETWEEN $startTimeStr AND $endTimeStr AND `pay_type` IN ($sqlPayType) AND `order_type` IN ($sqlOrderType)
        AND $cashieridJoin `refuse` IN ($refuseStr) AND `restaurant_id` = $restaurant_id AND `order_status` <> 0 GROUP BY order_sn ORDER BY order_id desc ";
                $order_lists = unionSelect2($startTimeStr,$endTimeStr,$sqlOrderList);*/

//        $order_lists = $orderModel->where($condition)->group('order_sn')->order("order_id desc")->select();

        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');

        $this->assign("page",$show);
        /**
         * 查询订单每个订单关联的商品信息
         */
        $refuse_num = 0;
        $refuse_total = 0;

        $have_count_orderid = [];
        foreach($order_lists as $key => $val){
            if ($val['refuse'] == 1) {
                if(!in_array($val['order_id'],$have_count_orderid)){
                    $refuse_total += $val['total_amount'];

                    $have_count_orderid[] = $val['order_id'];
                }
                $refuse_num += $val['food_num'];
            }

            if ($val['refuse'] == 2) {
                $refuse_num += $val['refuse_num'];

                if ($val['refuse_num'] > 0) {
                    $refuse_total += $val['food_price2'];
                }
            }
        }

        foreach($order_list as $key => $val){ 
            $condition['order_id'] = $val['order_id'];
            $order_list[$key]["add_time"] = date("Y-m-d H:i:s",$val['add_time']);
            $order_id = $val['order_id'];
            $yearMonth = date('Ym',$val['add_time']);
            $orderFoodModel = 'order_food_'.$yearMonth;
            $orderFoodAttributeModel = 'order_food_attribute_'.$yearMonth;
            /*$sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM `tabName2` WHERE `order_id` = $order_id";
            $food_list = unionSelect2($startTimeStr,$endTimeStr,$sqlFoodList);*/
            $sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM $orderFoodModel WHERE `order_id` = $order_id";
            $food_list = M()->query($sqlFoodList);
			foreach($food_list as $key1=>$value1){
				$condition1['order_food_id'] = $value1['order_food_id'];
                $order_food_id = $value1['order_food_id'];
                /*$sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM `tabName3` WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = unionSelect2($startTimeStr,$endTimeStr,$sql_attribute_Arr);*/
                $sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM $orderFoodAttributeModel WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = M()->query($sql_attribute_Arr);
				$attribute_Arr1 = array();
				foreach($attribute_Arr as $abA_key=>$abA_value){
					if($abA_value['count_type'] == 1){
						$attribute_Arr1[$abA_key] = $abA_value;
					}
				}
				$food_list[$key1]['attribute_list'] = $attribute_Arr1;	//每个食品下的属性列表
			}
            $order_list[$key]['food_info'] = $food_list;	
        }

        $this->assign("orderInfo",$order_list);
        unset($orderModel);

        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierid .= " cashier_id=".$cashier_id." AND";
        }

        $restaurant_id = session('restaurant_id');
         //订单信息
        /*$orderListSql = "SELECT `total_amount`,pay_type,order_type,restaurant_id,order_status,add_time,cashier_id,refuse FROM `tabName1` WHERE
        `restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND refuse IN (".$refuseStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn";*/

        $orderListSql = "SELECT `total_amount`,pay_type,order_type,restaurant_id,order_status,add_time,cashier_id,refuse,benefit_money,extra_charge FROM `tabName1` WHERE
        `restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND refuse IN (".$refuseStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn";
        // 满足条件的分表订单结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$orderListSql);

        $re_count = 0;
        $wechat = 0;
        $alipay = 0;
        $cash   = 0;
        $member = 0;
        $fourth = 0;
        $dingding = 0;
        $benefit_money_total = 0;   // 总的优惠
        $extra_charge_total = 0;    // 总的附加费
        $count = count($order_list);
        foreach ($order_list as $k => $v) {
            $benefit_money_total += $v['benefit_money'];
            $extra_charge_total += $v['extra_charge'];

            if ($v['refuse'] !=0 ) {
                $re_count++;
            }

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
            if ($v['pay_type'] == 6) {
                $dingding += $v['total_amount'];//第四方总额
            }
        }
        $statisData = [];
        $statisData['cash'] = floatval($cash);
        $statisData['alipay'] = floatval($alipay);
        $statisData['wechat'] = floatval($wechat);
        $statisData['member'] = floatval($member);
        $statisData['fourth'] = floatval($fourth);
        $statisData['dingding'] = floatval($dingding);
        $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth+$dingding;
        $statisData['count'] = $count;
        $statisData['re_count'] = $re_count;
        $statisData['refuse_num'] = $refuse_num;
        $statisData['refuse_total'] = $refuse_total;

        $statisData['benefit_money_total'] = $benefit_money_total;     // 总的优惠
        $statisData['extra_charge_total'] = $extra_charge_total;       // 总的附加费

                //菜品统计
        /*$all_foodinfo = $Model->query(" SELECT food_id,food_name,SUM(food_num) food_num FROM (SELECT a.order_id,food_id,food_num,food_name FROM order_food a LEFT JOIN (SELECT order_id,restaurant_id,add_time,order_status,order_type,pay_type,cashier_id,refuse from `order` GROUP BY order_sn) b
        ON a.order_id=b.order_id WHERE b.restaurant_id=".$restaurant_id." AND ".$cashierid." b.add_time >=".$startTimeStr." AND b.add_time<=".$endTimeStr." AND b.order_status <> 0 AND b.order_type IN (".$ordertypeStr.") AND b.pay_type IN (".$paytypeStr.")) c GROUP BY food_id");*/

        $orderListSql = " SELECT food_id,food_name,SUM(food_num) food_num FROM (SELECT a.order_id,food_id,food_num,food_name FROM tabName2 a LEFT JOIN (SELECT order_id,restaurant_id,add_time,order_status,order_type,pay_type,cashier_id,refuse from `tabName1` GROUP BY order_sn) b
        ON a.order_id=b.order_id WHERE b.restaurant_id=".$restaurant_id." AND ".$cashierid." b.add_time >=".$startTimeStr." AND b.add_time<=".$endTimeStr." AND b.order_status <> 0 AND b.order_type IN (".$ordertypeStr.") AND b.pay_type IN (".$paytypeStr.")) c GROUP BY food_id";

        // 满足条件的分表订单结果集
        $all_foodinfo = unionSelect2($startTimeStr,$endTimeStr,$orderListSql);

        $dishesTotle = 0;
        foreach ($all_foodinfo as $k => $v) {
            $dishesTotle += $v['food_num'];
        }

        $statisData['dishes_data_totle'] = $dishesTotle;
        $this->assign('statisData', $statisData);
        $this->assign('refuse', $refuse);
        ////

        $this->display("ajaxOrderInfo");
    }

    //明细查询ajax请求
    public function orderInfoAjax(){
        /**
         * 搜索条件
         */
        $restaurant_id = session('restaurant_id');
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");
        $condition = array();
        $startDate = empty($startDate) ? date('Y-m-d',time()) : $startDate;
        $endDate = empty($endDate) ? $startDate : $endDate;
        $startTime = empty($startTime) ? '00:00:00' : $startTime;
        $endTime = empty($endTime) ? '23:59:59' : $endTime;
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);
        $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));

        //是否有收银员
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        $cashieridJoin = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $condition['cashier_id'] = $cashier_id;

            $cashierid .= " cashier_id=".$cashier_id." AND";
            $cashieridJoin .= " t1.cashier_id=".$cashier_id." AND";
        }


        //支付类型
        $pay_type = I("post.pay_type");
        $paytype = array(0,1,2,4,5,6);
        if($pay_type == 99){
            $condition['pay_type'] = array("in",$paytype);
            $paytypeStr = '0,1,2,4,5,6';
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $condition['pay_type'] = array("in",$pay_type);
            $paytypeStr = $pay_type;
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }
        //就餐方式
        $order_type = I("post.order_type");
        $ordertype = array(1,2,3);
        if($order_type == 99){
            $condition['order_type'] = array("in",$ordertype);
            $ordertypeStr = '1,2,3';
            $sqlOrderType = '1,2,3';  // 用于分表查询条件
        }else{
            $condition['order_type'] = array("in",$order_type);
            $ordertypeStr = $order_type;
            $sqlOrderType = $order_type;  // 用于分表查询条件
        }
        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        $orderModel = order();
        $condition["restaurant_id"] = session('restaurant_id');
        $condition['order_status'] = array("neq",0);

        /**
         * 分页查询订单数据
         */
        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 25;
        $condition["restaurant_id"] = session('restaurant_id');
        $condition['order_status'] = array("neq",0);

        // 分表统计总数
        $sql_orignal="SELECT COUNT(*) AS tp_count FROM tabName WHERE `add_time` BETWEEN $startTimeStr AND $endTimeStr AND `pay_type` IN ($sqlPayType) AND `order_type` IN ($sqlOrderType) AND ".$cashierid." `refuse` IN (".$refuseStr.") AND `restaurant_id` = $restaurant_id AND `order_status` <> 0 LIMIT 1";
        $count = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_count');

        // 满足条件的分表订单结果集（order和order_food连表查询）
        $sql_orignal="SELECT t1.order_id,t1.refuse,t1.total_amount,t2.`food_price2`,t2.`food_num`,t2.`refuse_num` FROM `tabName1` t1 RIGHT JOIN tabName2 t2 on t1.order_id = t2.order_id WHERE t1.`add_time` BETWEEN $startTimeStr AND $endTimeStr AND t1.`pay_type` IN ($sqlPayType)
                    AND t1.`order_type` IN ($sqlOrderType) AND ".$cashieridJoin." t1.`refuse` IN (".$refuseStr.") AND t1.`restaurant_id` = $restaurant_id AND t1.`order_status` <> 0 ORDER BY t1.order_id DESC";

        $order_lists = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

        $sqlList = "SELECT
                        *
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND $cashierid `refuse` IN ($refuseStr)
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn
                    ORDER BY
                        order_id DESC
                    ";
        // 分页数据结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sqlList,2,($page-1)*$page_num,$page_num);


        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');
        $allpage = $count / $page_num;
        $info['allpage'] = ceil($allpage);//进一取整取总页数
//        $this->assign("page",$show);
        $info['page'] = $show;      //分页
        /**
         * 查询订单每个订单关联的商品信息
         */
        $refuse_num = 0;
        $refuse_total = 0;

        $have_count_orderid = [];
        foreach($order_lists as $key => $val){
            if ($val['refuse'] == 1) {
                if(!in_array($val['order_id'],$have_count_orderid)){
                    $refuse_total += $val['total_amount'];

                    $have_count_orderid[] = $val['order_id'];
                }
                $refuse_num += $val['food_num'];
            }

            if ($val['refuse'] == 2) {
                $refuse_num += $val['refuse_num'];

                if ($val['refuse_num'] > 0) {
                    $refuse_total += $val['food_price2'];
                }
            }
        }

        foreach($order_list as $key => $val){
            $condition['order_id'] = $val['order_id'];
            $order_list[$key]["add_time"] = date("Y-m-d H:i:s",$val['add_time']);
            $order_id = $val['order_id'];
            $yearMonth = date('Ym',$val['add_time']);
            $orderFoodModel = 'order_food_'.$yearMonth;
            $orderFoodAttributeModel = 'order_food_attribute_'.$yearMonth;
            /*$sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM `tabName2` WHERE `order_id` = $order_id";
            $food_list = unionSelect2($startTimeStr,$endTimeStr,$sqlFoodList);*/
            $sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM $orderFoodModel WHERE `order_id` = $order_id";
            $food_list = M()->query($sqlFoodList);
            foreach($food_list as $key1=>$value1){
                $condition1['order_food_id'] = $value1['order_food_id'];
                $order_food_id = $value1['order_food_id'];
                /*$sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM `tabName3` WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = unionSelect2($startTimeStr,$endTimeStr,$sql_attribute_Arr);*/
                $sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM $orderFoodAttributeModel WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = M()->query($sql_attribute_Arr);
                $attribute_Arr1 = array();
                foreach($attribute_Arr as $abA_key=>$abA_value){
                    if($abA_value['count_type'] == 1){
                        $attribute_Arr1[$abA_key] = $abA_value;
                    }
                }
                $food_list[$key1]['attribute_list'] = $attribute_Arr1;	//每个食品下的属性列表
            }
            $order_list[$key]['food_info'] = $food_list;
        }

        $info['order_list'] = $order_list;      //订单详情
        unset($orderModel);

        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierid .= " cashier_id=".$cashier_id." AND";
        }

        $restaurant_id = session('restaurant_id');

        $orderListSql = "SELECT `total_amount`,pay_type,order_type,restaurant_id,order_status,add_time,cashier_id,refuse,benefit_money,extra_charge FROM `tabName1` WHERE
        `restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND refuse IN (".$refuseStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn";
        // 满足条件的分表订单结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$orderListSql);

        $re_count = 0;
        $wechat = 0;
        $alipay = 0;
        $cash   = 0;
        $member = 0;
        $fourth = 0;
        $dingding = 0;
        $benefit_money_total = 0;   // 总的优惠
        $extra_charge_total = 0;    // 总的附加费
        $count = count($order_list);
        foreach ($order_list as $k => $v) {
            $benefit_money_total += $v['benefit_money'];
            $extra_charge_total += $v['extra_charge'];

            if ($v['refuse'] !=0 ) {
                $re_count++;
            }

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
            if ($v['pay_type'] == 6) {
                $dingding += $v['total_amount'];//第四方总额
            }
        }
        $statisData = [];
        $statisData['cash'] = floatval($cash);
        $statisData['alipay'] = floatval($alipay);
        $statisData['wechat'] = floatval($wechat);
        $statisData['member'] = floatval($member);
        $statisData['fourth'] = floatval($fourth);
        $statisData['dingding'] = floatval($dingding);
        $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth+$dingding;
        $statisData['count'] = $count;
        $statisData['re_count'] = $re_count;
        $statisData['refuse_num'] = $refuse_num;
        $statisData['refuse_total'] = $refuse_total;
        $statisData['benefit_money_total'] = $benefit_money_total;     // 总的优惠
        $statisData['extra_charge_total'] = $extra_charge_total;       // 总的附加费


        $orderListSql = " SELECT food_id,food_name,SUM(food_num) food_num FROM (SELECT a.order_id,food_id,food_num,food_name FROM tabName2 a LEFT JOIN (SELECT order_id,restaurant_id,add_time,order_status,order_type,pay_type,cashier_id,refuse from `tabName1` GROUP BY order_sn) b
        ON a.order_id=b.order_id WHERE b.restaurant_id=".$restaurant_id." AND ".$cashierid." b.add_time >=".$startTimeStr." AND b.add_time<=".$endTimeStr." AND b.order_status <> 0 AND b.order_type IN (".$ordertypeStr.") AND b.pay_type IN (".$paytypeStr.")) c GROUP BY food_id";

        // 满足条件的分表订单结果集
        $all_foodinfo = unionSelect2($startTimeStr,$endTimeStr,$orderListSql);

        $dishesTotle = 0;
        foreach ($all_foodinfo as $k => $v) {
            $dishesTotle += $v['food_num'];
        }

        $statisData['dishes_data_totle'] = $dishesTotle;
        $info['statisData'] = $statisData;      //替换成数组给前端
        $info['refuse'] =  $refuse;         //替换成数组给前端
        $this->ajaxReturn($info);
    }

    // 明细查询分页获取订单列表
    public function ajaxPage(){
        /**
         * 搜索条件
         */
        $restaurant_id = session('restaurant_id');
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");
        $condition = array();
        $startDate = empty($startDate) ? date('Y-m-d',time()) : $startDate;
        $endDate = empty($endDate) ? $startDate : $endDate;
        $startTime = empty($startTime) ? '00:00:00' : $startTime;
        $endTime = empty($endTime) ? '23:59:59' : $endTime;
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);
        $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));

        //是否有收银员
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        if ($cashier_id) {
            $cashierid .= " cashier_id=".$cashier_id." AND";
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if($pay_type == 99){
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }
        //就餐方式
        $order_type = I("post.order_type");
        if($order_type == 99){
            $sqlOrderType = '1,2,3';  // 用于分表查询条件
        }else{
            $sqlOrderType = $order_type;  // 用于分表查询条件
        }
        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        /**
         * 分页查询订单数据
         */
        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 25;

        // 分表统计总数
        $sql_orignal="SELECT COUNT(*) AS tp_count FROM tabName WHERE `add_time` BETWEEN $startTimeStr AND $endTimeStr AND `pay_type` IN ($sqlPayType) AND `order_type` IN ($sqlOrderType) AND ".$cashierid." `refuse` IN (".$refuseStr.") AND `restaurant_id` = $restaurant_id AND `order_status` <> 0 LIMIT 1";
        $count = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_count');

        $sqlList = "SELECT
                        *
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND $cashierid `refuse` IN ($refuseStr)
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn
                    ORDER BY
                        order_id DESC
                    ";
        // 分页数据结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sqlList,2,($page-1)*$page_num,$page_num);

        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');

        $this->assign("page",$show);
        /**
         * 查询订单每个订单关联的商品信息
         */
        foreach($order_list as $key => $val){
            $condition['order_id'] = $val['order_id'];
            $order_list[$key]["add_time"] = date("Y-m-d H:i:s",$val['add_time']);
            $order_id = $val['order_id'];
            $yearMonth = date('Ym',$val['add_time']);
            $orderFoodModel = 'order_food_'.$yearMonth;
            $orderFoodAttributeModel = 'order_food_attribute_'.$yearMonth;
           /* $sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM `tabName2` WHERE `order_id` = $order_id";
            $food_list = unionSelect2($startTimeStr,$endTimeStr,$sqlFoodList);*/
            $sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM $orderFoodModel WHERE `order_id` = $order_id";
            $food_list = M()->query($sqlFoodList);
            foreach($food_list as $key1=>$value1){
                $condition1['order_food_id'] = $value1['order_food_id'];
                $order_food_id = $value1['order_food_id'];
                /*$sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM `tabName3` WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = unionSelect2($startTimeStr,$endTimeStr,$sql_attribute_Arr);*/
                $sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM $orderFoodAttributeModel WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = M()->query($sql_attribute_Arr);
                $attribute_Arr1 = array();
                foreach($attribute_Arr as $abA_key=>$abA_value){
                    if($abA_value['count_type'] == 1){
                        $attribute_Arr1[$abA_key] = $abA_value;
                    }
                }
                $food_list[$key1]['attribute_list'] = $attribute_Arr1;	//每个食品下的属性列表
            }
            $order_list[$key]['food_info'] = $food_list;
        }

        $this->assign("orderInfo",$order_list);
        unset($orderModel);

        $this->display();
    }

    // 明细查询分页获取订单列表
    public function ajaxPageAjax(){
        /**
         * 搜索条件
         */
        $restaurant_id = session('restaurant_id');
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");
        $condition = array();
        $startDate = empty($startDate) ? date('Y-m-d',time()) : $startDate;
        $endDate = empty($endDate) ? $startDate : $endDate;
        $startTime = empty($startTime) ? '00:00:00' : $startTime;
        $endTime = empty($endTime) ? '23:59:59' : $endTime;
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);
        $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));

        //是否有收银员
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        if ($cashier_id) {
            $cashierid .= " cashier_id=".$cashier_id." AND";
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if($pay_type == 99){
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }
        //就餐方式
        $order_type = I("post.order_type");
        if($order_type == 99){
            $sqlOrderType = '1,2,3';  // 用于分表查询条件
        }else{
            $sqlOrderType = $order_type;  // 用于分表查询条件
        }
        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        /**
         * 分页查询订单数据
         */
        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 25;

        // 分表统计总数
        $sql_orignal="SELECT COUNT(*) AS tp_count FROM tabName WHERE `add_time` BETWEEN $startTimeStr AND $endTimeStr AND `pay_type` IN ($sqlPayType) AND `order_type` IN ($sqlOrderType) AND ".$cashierid." `refuse` IN (".$refuseStr.") AND `restaurant_id` = $restaurant_id AND `order_status` <> 0 LIMIT 1";
        $count = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_count');

        $sqlList = "SELECT
                        *
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND $cashierid `refuse` IN ($refuseStr)
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn
                    ORDER BY
                        order_id DESC
                    ";
        // 分页数据结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sqlList,2,($page-1)*$page_num,$page_num);

        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');

        $allpage = $count / $page_num;
        $info['allpage'] = ceil($allpage);//进一取整取总页数
        $info['page'] = $show;      //分页
        /**
         * 查询订单每个订单关联的商品信息
         */
        foreach($order_list as $key => $val){
            $condition['order_id'] = $val['order_id'];
            $order_list[$key]["add_time"] = date("Y-m-d H:i:s",$val['add_time']);
            $order_id = $val['order_id'];
            $yearMonth = date('Ym',$val['add_time']);
            $orderFoodModel = 'order_food_'.$yearMonth;
            $orderFoodAttributeModel = 'order_food_attribute_'.$yearMonth;
            /* $sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM `tabName2` WHERE `order_id` = $order_id";
             $food_list = unionSelect2($startTimeStr,$endTimeStr,$sqlFoodList);*/
            $sqlFoodList = "SELECT `food_id`,`food_price2`,`food_num`,`food_name`,`order_food_id`,`refuse_num` FROM $orderFoodModel WHERE `order_id` = $order_id";
            $food_list = M()->query($sqlFoodList);
            foreach($food_list as $key1=>$value1){
                $condition1['order_food_id'] = $value1['order_food_id'];
                $order_food_id = $value1['order_food_id'];
                /*$sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM `tabName3` WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = unionSelect2($startTimeStr,$endTimeStr,$sql_attribute_Arr);*/
                $sql_attribute_Arr = "SELECT `food_attribute_name`,`food_attribute_price`,`count_type` FROM $orderFoodAttributeModel WHERE `order_food_id` = $order_food_id";
                $attribute_Arr = M()->query($sql_attribute_Arr);
                $attribute_Arr1 = array();
                foreach($attribute_Arr as $abA_key=>$abA_value){
                    if($abA_value['count_type'] == 1){
                        $attribute_Arr1[$abA_key] = $abA_value;
                    }
                }
                $food_list[$key1]['attribute_list'] = $attribute_Arr1;	//每个食品下的属性列表
            }
            $order_list[$key]['food_info'] = $food_list;
        }

        $this->assign("orderInfo",$order_list);
        unset($orderModel);
        $info['orderInfo'] = $order_list;

        $this->ajaxReturn($info);
    }

    /**
     * 统计某菜品的销售情况
     */
    public function countFoodSale(){
        /**
         * 获取查询的条件，查询相关订单信息
         */
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        //支付类型
        $pay_type = I("post.pay_type");
        $paytype = array(0,1,2,4,5,6);
        if($pay_type == 99){
            $condition['pay_type'] = array("in",$paytype);
            $paytypeStr = '0,1,2,4,5,6';
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $condition['pay_type'] = array("in",$pay_type);
            $paytypeStr = $pay_type;
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }
        //就餐方式
        $order_type = I("post.order_type");
        $ordertype = array(1,2,3);
        if($order_type == 99){
            $condition['order_type'] = array("in",$ordertype);
            $ordertypeStr = '1,2,3';
            $sqlOrderType = '1,2,3';
        }else{
            $condition['order_type'] = array("in",$order_type);
            $ordertypeStr = $order_type;
            $sqlOrderType = $order_type;
        }
        $restaurant_id = session("restaurant_id");
        $condition['restaurant_id'] = $restaurant_id;
        $condition['order_status'] = array("neq",0);

        //是否有收银员
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        $joincashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $condition['cashier_id'] = $cashier_id;

            $cashierid .= " cashier_id=".$cashier_id." AND";
            $joincashierid .= " t2.cashier_id=".$cashier_id." AND";
        }


        $order_model = order();
//        $order_list = $order_model->where($condition)->field("order_id")->group('order_sn')->select();

        $sql_sub = "SELECT
                        `order_id`,`add_time`
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `refuse` IN ($refuseStr)
                    AND $cashierid
                    `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn";

        // 满足条件的分表订单结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sql_sub);

        $orders2 = array();
        foreach($order_list as $order_key => $order_val){
            $orders2[] = $order_val["order_id"];
        }
        if(empty($orders2)){
            exit;
        }
        // $order_list2 = 0;
        /**
         * 计算搜索菜品的销售情况
         */
        $food_name = I("post.food_name");
        //获取菜品的id
        $f_condition['food_name'] = array("like","%".$food_name."%");
        $f_condition['order_id'] = array("in",$orders2);
        $food_model = order_F();
//        $order_list2 = $food_model->where($f_condition)->field("order_id,food_name,food_price2,food_num")->select();

        $sql = "SELECT
                    t1.`order_id`,
                    t1.`food_name`,
                    t1.`food_price2`,
                    t1.`food_num`,
                    t2.`add_time`,
                    t2.`order_type`
                FROM
                    `tabName2` t1
                    LEFT JOIN tabName1 t2
                    ON t1.order_id = t2.order_id
                WHERE
                    t1.`food_name` LIKE '%$food_name%'
                AND
                 t2.`add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND t2.`pay_type` IN ($sqlPayType)
                    AND t2.`refuse` IN ($refuseStr)
                    AND $joincashierid
                    t2.`order_type` IN ($sqlOrderType)
                    AND t2.`restaurant_id` = $restaurant_id
                    AND t2.`order_status` <> 0
                    ";
        // 满足条件的分表订单结果集
        $order_list2 = unionSelect2($startTimeStr,$endTimeStr,$sql);
//        p($order_list2);


        $ids = implode(',',$orders2);
//        $orderIds = $food_model->where($f_condition)->field("order_id")->select();

        $sqlGetIds = "SELECT
                    `order_id`
                FROM
                    `tabName2`
                WHERE
                    `food_name` LIKE '%$food_name%'
                AND `order_id` IN (
                    $ids
                )";
        // 满足条件的分表订单结果集
        $orderIds = unionSelect2($startTimeStr,$endTimeStr,$sqlGetIds);

        $order_Ids = [];
        foreach ($orderIds as $k => $v) {
            $order_Ids[] = $v['order_id'];
        }

        if(empty($order_Ids)){
            exit;
        }

        $str = implode(',', $order_Ids);

        /////
        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierid .= " cashier_id=".$cashier_id." AND";
        }
        $Model = M();
        $restaurant_id = session('restaurant_id');
        //订单信息
        //订单总数
        /*$count = $Model->query("SELECT COUNT(*) count FROM (SELECT order_sn FROM `order` WHERE order_id in (".$str.") AND
`restaurant_id` = ".$restaurant_id." AND ".$cashierid." `order_status` <> 0 AND  pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn) a");*/

//        $count = $food_model->where($f_condition)->count();


        $sql_orignal="SELECT
                            COUNT(*) AS tp_count
                        FROM
                            `tabName`
                        WHERE
                            `order_id` IN (
                            $str
                        )
                        LIMIT 1";
        $count = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_count');

        //计算销售总额
        // 遍历菜品订单表
        $all_total_amount = 0;
        $orders22 = array();
        $dishNum = 0;
        foreach($order_list2 as $kt => $vt){
            $dishNum += $vt['food_num'];

            $lso_condition['order_id'] = $vt['order_id'];
//            $order = $order_model->where($lso_condition)->find();
            $month = date('Ym',$vt['add_time']);
            $order = M("order_$month")->where($lso_condition)->find();
            $tmp = $vt["food_price2"];
            $all_total_amount +=$tmp;
            $order_list2[$kt]['add_time'] = $order['add_time'];
            $order_list2[$kt]['pay_type'] = $order['pay_type'];
            $order_list2[$kt]['order_sn'] = $order['order_sn'];
            $order_list2[$kt]['total_amount'] = $vt["food_price2"];
            $order_list2[$kt]['f_type'] = 1;

            $orders22[] = $vt['order_id'];
        }

        $wechat = 0;
        $alipay = 0;
        $cash   = 0;
        $member = 0;
        $fourth = 0;
        foreach ($order_list2 as $k => $v) {
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
//        $dishesTotle = count($order_list2);

        $statisData = [];
        $statisData['cash'] = floatval($cash);
        $statisData['alipay'] = floatval($alipay);
        $statisData['wechat'] = floatval($wechat);
        $statisData['member'] = floatval($member);
        $statisData['fourth'] = floatval($fourth);
        $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth;
        $statisData['dishes_data_totle'] = $dishNum;
        $statisData['count'] = $count;

        //------------------------------------属性数组-------------------------------------
        $order_food_attributeModel = order_F_A();
        $attr_all_orderfoodId = array();
//        foreach($orders22 as $os2_key=>$os2_value){
        foreach($order_list2 as $os2_key=>$os2_value){
            $os2_condition['order_id'] = $os2_value['order_id'];
//            $attr_one_orderfoodId = $food_model->where($os2_condition)->field('order_food_id')->select();
            $month = date('Ym',$os2_value['add_time']);
            $attr_one_orderfoodId = M("order_food_$month")->where($os2_condition)->field('order_food_id')->select();
            $attr_orderfoodIdArr = array();
            foreach($attr_one_orderfoodId as $aoof_key=>$aoof_value){
                $attr_orderfoodIdArr[$aoof_key] = $aoof_value['order_food_id'];
            }
            $attr_all_orderfoodId[] = $attr_orderfoodIdArr;
        }
        $attr_all_orderfoodId1 = arrayChange($attr_all_orderfoodId);

        $f_condition1['order_food_id'] = array("in",$attr_all_orderfoodId1);
//        $all_attrArr = $order_food_attributeModel->where($f_condition1)->field("order_food_id,food_attribute_name,food_attribute_price,count_type")->select(false);

        $orderFoodIds = implode(',',$attr_all_orderfoodId1);
        $sql = "SELECT
                        t1.`order_food_id`,
                        t1.`food_attribute_name`,
                        t1.`food_attribute_price`,
                        t1.`count_type`,
                        t1.`num`,
                        t3.order_sn,
                        t3.add_time,
                        t3.order_type,
                        t3.pay_type,
                        t3.order_id
                    FROM
                        `tabName3` t1
                        LEFT JOIN `tabName2` t2
                        ON t1.order_food_id = t2.order_food_id
                        LEFT JOIN `tabName1` t3
                        ON t2.order_id = t3.order_id
                    WHERE
                        t1.`food_attribute_name` LIKE '%$food_name%'
                    AND t1.`order_food_id` IN (
                        $orderFoodIds
                    )";

        // 满足条件的分表订单结果集
        $all_attrArr = unionSelect2($startTimeStr,$endTimeStr,$sql);


        $order_list3 = array();
        foreach($all_attrArr as $aaA_key=>$aaA_value){
            # 属性价格不等于0的才显示
            if($aaA_value['count_type'] == 1 && $aaA_value['food_attribute_price'] != 0){
                $orl3_condition['order_food_id'] = $aaA_value['order_food_id'];
//                $attr_order_id = $food_model->where($orl3_condition)->field('order_id')->find()['order_id'];
                $order_list3[$aaA_key]['order_id'] = $aaA_value['order_id'];
                $order_list3[$aaA_key]['food_name'] = $aaA_value['food_attribute_name'];
                $order_list3[$aaA_key]['food_price2'] = $aaA_value['food_attribute_price'];
                $order_list3[$aaA_key]['food_num'] = 1;
//                $order_list3[$aaA_key]['add_time'] = $order_model->where("order_id=$attr_order_id")->field("add_time")->find()['add_time'];
                $order_list3[$aaA_key]['add_time'] = $aaA_value['add_time'];
                $order_list3[$aaA_key]['pay_type'] = $aaA_value['pay_type'];
                $order_list3[$aaA_key]['order_sn'] = $aaA_value['order_sn'];
                $order_list3[$aaA_key]['total_amount'] = $aaA_value['food_attribute_price'];
                $order_list3[$aaA_key]['f_type'] = 2;
            }
        }
        $all_countInfoArr = array();                //合并菜品与菜品属性数组(两数组格式相同，只是把查出来的第二个数组加在第一个数组尾部使其连贯)
        foreach($order_list2 as $ol2_value){
            $all_countInfoArr[] = $ol2_value;
        }
        foreach($order_list3 as $ol3_value){
            $all_countInfoArr[] = $ol3_value;
        }


        $all_total_amount = number_format($all_total_amount,2);
        $this->assign('statisData', $statisData);
        $this->assign("total_amount",$all_total_amount);
        $this->assign("order_list",$all_countInfoArr);
        $this->display();
    }


    /**
     * 统计某菜品的销售情况ajax
     */
    public function countFoodSaleAjax(){
        /**
         * 获取查询的条件，查询相关订单信息
         */
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        //支付类型
        $pay_type = I("post.pay_type");
        $paytype = array(0,1,2,4,5,6);
        if($pay_type == 99){
            $condition['pay_type'] = array("in",$paytype);
            $paytypeStr = '0,1,2,4,5,6';
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $condition['pay_type'] = array("in",$pay_type);
            $paytypeStr = $pay_type;
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }
        //就餐方式
        $order_type = I("post.order_type");
        $ordertype = array(1,2,3);
        if($order_type == 99){
            $condition['order_type'] = array("in",$ordertype);
            $ordertypeStr = '1,2,3';
            $sqlOrderType = '1,2,3';
        }else{
            $condition['order_type'] = array("in",$order_type);
            $ordertypeStr = $order_type;
            $sqlOrderType = $order_type;
        }
        $restaurant_id = session("restaurant_id");
        $condition['restaurant_id'] = $restaurant_id;
        $condition['order_status'] = array("neq",0);

        //是否有收银员
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        $joincashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $condition['cashier_id'] = $cashier_id;

            $cashierid .= " cashier_id=".$cashier_id." AND";
            $joincashierid .= " t2.cashier_id=".$cashier_id." AND";
        }


        $order_model = order();
//        $order_list = $order_model->where($condition)->field("order_id")->group('order_sn')->select();

        $sql_sub = "SELECT
                        `order_id`,`add_time`
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `refuse` IN ($refuseStr)
                    AND $cashierid
                    `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn";

        // 满足条件的分表订单结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sql_sub);

        $orders2 = array();
        foreach($order_list as $order_key => $order_val){
            $orders2[] = $order_val["order_id"];
        }
        if(empty($orders2)){
            exit;
        }
        // $order_list2 = 0;
        /**
         * 计算搜索菜品的销售情况
         */
        $food_name = I("post.food_name");
        //获取菜品的id
        $f_condition['food_name'] = array("like","%".$food_name."%");
        $f_condition['order_id'] = array("in",$orders2);
        $food_model = order_F();
//        $order_list2 = $food_model->where($f_condition)->field("order_id,food_name,food_price2,food_num")->select();

        $sql = "SELECT
                    t1.`order_id`,
                    t1.`food_name`,
                    t1.`food_price2`,
                    t1.`food_num`,
                    t2.`add_time`,
                    t2.`order_type`
                FROM
                    `tabName2` t1
                    LEFT JOIN tabName1 t2
                    ON t1.order_id = t2.order_id
                WHERE
                    t1.`food_name` LIKE '%$food_name%'
                AND
                 t2.`add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND t2.`pay_type` IN ($sqlPayType)
                    AND t2.`refuse` IN ($refuseStr)
                    AND $joincashierid
                    t2.`order_type` IN ($sqlOrderType)
                    AND t2.`restaurant_id` = $restaurant_id
                    AND t2.`order_status` <> 0
                    ";
        // 满足条件的分表订单结果集
        $order_list2 = unionSelect2($startTimeStr,$endTimeStr,$sql);
//        p($order_list2);


        $ids = implode(',',$orders2);
//        $orderIds = $food_model->where($f_condition)->field("order_id")->select();

        $sqlGetIds = "SELECT
                    `order_id`
                FROM
                    `tabName2`
                WHERE
                    `food_name` LIKE '%$food_name%'
                AND `order_id` IN (
                    $ids
                )";
        // 满足条件的分表订单结果集
        $orderIds = unionSelect2($startTimeStr,$endTimeStr,$sqlGetIds);

        $order_Ids = [];
        foreach ($orderIds as $k => $v) {
            $order_Ids[] = $v['order_id'];
        }

        if(empty($order_Ids)){
            exit;
        }

        $str = implode(',', $order_Ids);

        /////
        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierid .= " cashier_id=".$cashier_id." AND";
        }
        $Model = M();
        $restaurant_id = session('restaurant_id');
        //订单信息
        //订单总数


        $sql_orignal="SELECT
                            COUNT(*) AS tp_count
                        FROM
                            `tabName`
                        WHERE
                            `order_id` IN (
                            $str
                        )
                        LIMIT 1";
        $count = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_count');

        //计算销售总额
        // 遍历菜品订单表
        $all_total_amount = 0;
        $orders22 = array();
        $dishNum = 0;
        foreach($order_list2 as $kt => $vt){
            $dishNum += $vt['food_num'];

            $lso_condition['order_id'] = $vt['order_id'];
//            $order = $order_model->where($lso_condition)->find();
            $month = date('Ym',$vt['add_time']);
            $order = M("order_$month")->where($lso_condition)->find();
            $tmp = $vt["food_price2"];
            $all_total_amount +=$tmp;
            $order_list2[$kt]['add_time'] = $order['add_time'];
            $order_list2[$kt]['pay_type'] = $order['pay_type'];
            $order_list2[$kt]['order_sn'] = $order['order_sn'];
            $order_list2[$kt]['total_amount'] = $vt["food_price2"];
            $order_list2[$kt]['f_type'] = 1;

            $orders22[] = $vt['order_id'];
        }

        $wechat = 0;
        $alipay = 0;
        $cash   = 0;
        $member = 0;
        $fourth = 0;
        foreach ($order_list2 as $k => $v) {
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
//        $dishesTotle = count($order_list2);

        $statisData = [];
        $statisData['cash'] = floatval($cash);
        $statisData['alipay'] = floatval($alipay);
        $statisData['wechat'] = floatval($wechat);
        $statisData['member'] = floatval($member);
        $statisData['fourth'] = floatval($fourth);
        $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth;
        $statisData['dishes_data_totle'] = $dishNum;
        $statisData['count'] = $count;

        //------------------------------------属性数组-------------------------------------
        $order_food_attributeModel = order_F_A();
        $attr_all_orderfoodId = array();
//        foreach($orders22 as $os2_key=>$os2_value){
        foreach($order_list2 as $os2_key=>$os2_value){
            $os2_condition['order_id'] = $os2_value['order_id'];
//            $attr_one_orderfoodId = $food_model->where($os2_condition)->field('order_food_id')->select();
            $month = date('Ym',$os2_value['add_time']);
            $attr_one_orderfoodId = M("order_food_$month")->where($os2_condition)->field('order_food_id')->select();
            $attr_orderfoodIdArr = array();
            foreach($attr_one_orderfoodId as $aoof_key=>$aoof_value){
                $attr_orderfoodIdArr[$aoof_key] = $aoof_value['order_food_id'];
            }
            $attr_all_orderfoodId[] = $attr_orderfoodIdArr;
        }
        $attr_all_orderfoodId1 = arrayChange($attr_all_orderfoodId);

        $f_condition1['order_food_id'] = array("in",$attr_all_orderfoodId1);
//        $all_attrArr = $order_food_attributeModel->where($f_condition1)->field("order_food_id,food_attribute_name,food_attribute_price,count_type")->select(false);

        $orderFoodIds = implode(',',$attr_all_orderfoodId1);
        $sql = "SELECT
                        t1.`order_food_id`,
                        t1.`food_attribute_name`,
                        t1.`food_attribute_price`,
                        t1.`count_type`,
                        t1.`num`,
                        t3.order_sn,
                        t3.add_time,
                        t3.order_type,
                        t3.pay_type,
                        t3.order_id
                    FROM
                        `tabName3` t1
                        LEFT JOIN `tabName2` t2
                        ON t1.order_food_id = t2.order_food_id
                        LEFT JOIN `tabName1` t3
                        ON t2.order_id = t3.order_id
                    WHERE
                        t1.`food_attribute_name` LIKE '%$food_name%'
                    AND t1.`order_food_id` IN (
                        $orderFoodIds
                    )";

        // 满足条件的分表订单结果集
        $all_attrArr = unionSelect2($startTimeStr,$endTimeStr,$sql);


        $order_list3 = array();
        foreach($all_attrArr as $aaA_key=>$aaA_value){
            # 属性价格不等于0的才显示
            if($aaA_value['count_type'] == 1 && $aaA_value['food_attribute_price'] != 0){
                $orl3_condition['order_food_id'] = $aaA_value['order_food_id'];
//                $attr_order_id = $food_model->where($orl3_condition)->field('order_id')->find()['order_id'];
                $order_list3[$aaA_key]['order_id'] = $aaA_value['order_id'];
                $order_list3[$aaA_key]['food_name'] = $aaA_value['food_attribute_name'];
                $order_list3[$aaA_key]['food_price2'] = $aaA_value['food_attribute_price'];
                $order_list3[$aaA_key]['food_num'] = 1;
//                $order_list3[$aaA_key]['add_time'] = $order_model->where("order_id=$attr_order_id")->field("add_time")->find()['add_time'];
                $order_list3[$aaA_key]['add_time'] = $aaA_value['add_time'];
                $order_list3[$aaA_key]['pay_type'] = $aaA_value['pay_type'];
                $order_list3[$aaA_key]['order_sn'] = $aaA_value['order_sn'];
                $order_list3[$aaA_key]['total_amount'] = $aaA_value['food_attribute_price'];
                $order_list3[$aaA_key]['f_type'] = 2;
            }
        }
        $all_countInfoArr = array();                //合并菜品与菜品属性数组(两数组格式相同，只是把查出来的第二个数组加在第一个数组尾部使其连贯)
        foreach($order_list2 as $ol2_value){
            $all_countInfoArr[] = $ol2_value;
        }
        foreach($order_list3 as $ol3_value){
            $all_countInfoArr[] = $ol3_value;
        }


        $all_total_amount = number_format($all_total_amount,2);
//        $this->assign('statisData', $statisData);
//        $this->assign("total_amount",$all_total_amount);
//        $this->assign("order_list",$all_countInfoArr);

        $arr_food['statisData'] = $statisData;
        $arr_food['total_amount'] = $all_total_amount;
        $arr_food['order_list']  = $all_countInfoArr;
        $this->ajaxReturn($arr_food);
    }

    // 明细查询导出1
    public function exportExcel(){			//导出Excel表
        /**
         * 搜索条件
         */
        $startDate = I("post.startDate");				//条件时间范围查询
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");
        $condition = array();			//声明条件数组
        //收银员条件
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $condition['cashier_id'] = $cashier_id;

            $cashierid .= " cashier_id=".$cashier_id." AND";
        }

        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //支付类型
        $pay_type = I("post.pay_type");
        $paytype = array(0,1,2,4,5,6);
        if($pay_type == 99){
            $condition['pay_type'] = array("in",$paytype);
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }

        //就餐方式
        $order_type = I("post.order_type");
        $ordertype = array(1,2,3);
        if($order_type == 99){
            $condition['order_type'] = array("in",$ordertype);
            $sqlOrderType = '1,2,3';  // 用于分表查询条件
        }else{
            $condition['order_type'] = array("in",$order_type);
            $sqlOrderType = $order_type;  // 用于分表查询条件
        }

        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        $pay_type_str = array(
            "现金","支付宝","微信","","余额",'第四方支付'				//(3,1,2,4)0是取消支付
        );

        $pay_str = "";
        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp]."、";
        }
        $this->assign("pay_str",$pay_str);

        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }

        $this->assign("order_str",$order_str);

        $condition['restaurant_id'] = session('restaurant_id');

        $restaurant_id = session('restaurant_id');

        $condition['order_status']= array('neq', 0);

//        $orderArr = $orderModel->where($condition)->field('order_id,order_sn,order_type,pay_type,add_time,cashier_id,total_amount,original_price,benefit_money,reduce,vip_or_restaurant')->group('order_sn')->select();

        $sql = "SELECT
                    `order_id`,
                    `order_sn`,
                    `order_type`,
                    `pay_type`,
                    `add_time`,
                    `cashier_id`,
                    `total_amount`,
                    `original_price`,
                    `benefit_money`,
                    `reduce`,
                    `vip_or_restaurant`
                FROM
                    `tabName1`
                WHERE
                    `add_time` BETWEEN $startTimeStr
                AND $endTimeStr
                AND `pay_type` IN ($sqlPayType)
                AND `refuse` IN ($refuseStr)
                AND $cashierid
                `order_type` IN ($sqlOrderType)
                AND `restaurant_id` = $restaurant_id
                AND `order_status` <> 0
                GROUP BY
                    order_sn";
        // 满足条件的分表订单结果集
        $orderArr = unionSelect2($startTimeStr,$endTimeStr,$sql);

        //通过收银员id得到收银员名字
        $cashierModel = D("cashier");
        $where = [];
        $where['restaurant_id'] = session('restaurant_id');
        foreach ($orderArr as $k => $v) {
            $orderArr[$k]['cashier_id'] = $cashierModel->where("cashier_id={$v['cashier_id']}")->field('cashier_name')->limit(1)->find()['cashier_name'];
        }

        $order_type = array(
            1 => "店吃",
            2 => "打包带走",
            3 => "微信外卖",
        );

        $pay_type = array(
            0 => "现金",
            1 => "支付宝",
            2 => "微信",
            3 => "未支付",
            4 => "余额支付",
            5 => "第四方支付",
        );

        $o_condition['add_time'] = array("between",array($startTime,$endTime));

        $title = array(
            "订单号","总价","支付时间"
        );

        $orderList = array();
        foreach($orderArr as $key=>$value){
            $obj1['order_sn'] = $value['order_sn'];
            $obj1['add_time'] = date("Y-m-d h:i:s",$value['add_time']);
            $obj1['order_type'] = $order_type[$value['order_type']];
            $obj1['pay_type'] = $pay_type[$value['pay_type']];
            $obj1['total_price'] = $value['total_amount'];     // 最后的价格
            $obj1['cashier_id'] = $value['cashier_id'];

            if($value['vip_or_restaurant'] == 1){
                // 不打折扣的情况下
                $obj1['original_price'] = $value['total_amount'];   // 原价等于最终价
                $obj1['benefit_money'] = 0;         // 优惠为0
            }else{
                // 打折扣的情况下
                $obj1['original_price'] = $value['original_price'];   // 原价
                $obj1['benefit_money'] = $value['benefit_money'];;         // 优惠
            }
            $orderList[] = $obj1;
        }
        $xlsName  = "营业额报表、导出时间(".date("Y-m-d",time()).")";
        $xlsCell  = array(
            array('order_sn','订单号'),
            array('add_time','日期时间'),
            array('order_type','就餐方式'),
            array('pay_type','支付方式'),
            array('original_price','原价'),
            array('benefit_money','优惠'),
            array('total_price','总价'),
            array('cashier_id','收银员')
        );
        exportExcel($xlsName,$xlsCell,$orderList);
    }

    // 明细查询导出2
    public function exportExcel1(){
        /**
         * 获取查询的条件，查询相关订单信息
         */
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        $condition = array();
        //收银员条件
        $cashier_id = I("post.cashier_id");
        $cashierid = '';
        $joincashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $condition['cashier_id'] = $cashier_id;

            $cashierid .= " cashier_id=".$cashier_id." AND";
            $joincashierid .= " t2.cashier_id=".$cashier_id." AND";
        }
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }
        //订单状态
        $refuse = I("post.refuse");
        if ($refuse == '99') {
            $refuseStr = '0,1,2';
        }
        if ($refuse == '0') {
            $condition['refuse'] = 0;
            $refuseStr = '0';
        }
        if ($refuse == '1') {
            $condition['refuse'] = array("in",array(1,2));
            $refuseStr = '1,2';
        }

        //支付类型
        /*  $pay_type = I("post.pay_type");
          if(!empty($pay_type)){
              $condition['pay_type'] = array("in",$pay_type);
          }*/


        //支付类型
        $pay_type = I("post.pay_type");
        $paytype = array(0,1,2,4,5,6);
        if($pay_type == 99){
            $condition['pay_type'] = array("in",$paytype);
            $sqlPayType = '0,1,2,4,5,6';  // 用于分表查询条件
        }else{
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = $pay_type;  // 用于分表查询条件
        }

        //就餐方式
        $order_type = I("post.order_type");
        $ordertype = array(1,2,3);
        if($order_type == 99){
            $condition['order_type'] = array("in",$ordertype);
            $sqlOrderType = '1,2,3';  // 用于分表查询条件
        }else{
            $condition['order_type'] = array("in",$order_type);
            $sqlOrderType = $order_type;  // 用于分表查询条件
        }


        $pay_type_str = array(
            "现金","支付宝","微信","","余额",'第四方支付'
        );
        $pay_str = "";
        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp]."、";
        }

        $this->assign("pay_str",$pay_str);

        /* //就餐方式
         $order_type = I("post.order_type");
         if(!empty($order_type)){
             $condition['order_type'] = array("in",$order_type);
         }*/


        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }
        $this->assign("order_str",$order_str);
        $restaurant_id = session("restaurant_id");
        $condition['restaurant_id'] = $restaurant_id;
        $condition['order_status']= array('neq', 0);

        $sql_sub = "SELECT
                        `order_id`,`add_time`
                    FROM
                        `tabName1`
                    WHERE
                        `add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND `pay_type` IN ($sqlPayType)
                    AND `refuse` IN ($refuseStr)
                    AND $cashierid
                    `order_type` IN ($sqlOrderType)
                    AND `restaurant_id` = $restaurant_id
                    AND `order_status` <> 0
                    GROUP BY
                        order_sn";

        // 满足条件的分表订单结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sql_sub);

        $orders2 = array();
        foreach($order_list as $order_key => $order_val){
            $orders2[] = $order_val["order_id"];
        }

        if(empty($orders2)){
            exit;
        }

        /**
         * 计算搜索菜品的销售情况
         */
        $food_name = I("post.food_name");

        //获取菜品的id
        $f_condition['food_name'] = array("like","%".$food_name."%");
        $f_condition['order_id'] = array("in",$orders2);

        $sql = "SELECT
                    t1.`order_id`,
                    t1.`food_name`,
                    t1.`food_price2`,
                    t1.`food_num`,
                    t2.`add_time`
                FROM
                    `tabName2` t1
                    LEFT JOIN tabName1 t2
                    ON t1.order_id = t2.order_id
                WHERE
                    t1.`food_name` LIKE '%$food_name%'
                AND
                 t2.`add_time` BETWEEN $startTimeStr
                    AND $endTimeStr
                    AND t2.`pay_type` IN ($sqlPayType)
                    AND t2.`refuse` IN ($refuseStr)
                    AND $joincashierid
                    t2.`order_type` IN ($sqlOrderType)
                    AND t2.`restaurant_id` = $restaurant_id
                    AND t2.`order_status` <> 0
                    ";

        // 满足条件的分表订单结果集
        $order_list2 = unionSelect2($startTimeStr,$endTimeStr,$sql);

        $order_type2 = array(
        //		（1店吃，2打包带走，3微信外卖）
        1 => "店吃",
        2 => "打包带走",
        3 => "微信外卖",
                );

                $pay_type2 = array(
        //		（0现金，1支付宝，2微信，3未支付）
        0 => "现金",
        1 => "支付宝",
        2 => "微信",
        3 => "未支付",
        4 => "余额",
        5 => "第四方支付",
                );

        //计算销售总额
        $all_total_amount = 0;
        foreach($order_list2 as $kt => $vt){
            $lso_condition['order_id'] = $vt['order_id'];
            $month = date('Ym',$vt['add_time']);
            $order = M("order_$month")->where($lso_condition)->find();
            $all_total_amount+=$vt["food_price2"];
            $order_list2[$kt]['add_time'] = date("Y-m-d H:i:s",$order['add_time']);
            $order_list2[$kt]['pay_type'] = $pay_type2[$order['pay_type']];
            $order_list2[$kt]['order_sn'] = $order['order_sn'];
            $order_list2[$kt]['total_amount'] = $vt["food_price2"];
            $order_list2[$kt]['order_type'] = $order_type2[$order['order_type']];
            $order_list2[$kt]['f_type'] = 1;
        }

        $order_list3 = array();
        foreach($order_list2 as $key=>$value){
            $order_list3[$key]['order_sn'] = $value['order_sn'];
            $order_list3[$key]['food_name'] = $value['food_name'];
            $order_list3[$key]['add_time'] = $value['add_time'];
            $order_list3[$key]['order_type'] = $value['order_type'];
            $order_list3[$key]['pay_type'] = $value['pay_type'];
            $order_list3[$key]['food_price2'] = number_format($value["food_price2"]/$value["food_num"],2);
            $order_list3[$key]['food_num'] = $value['food_num'];
            $order_list3[$key]['total_amount'] = $value['total_amount'];
        }

        $attr_all_orderfoodId = array();
        foreach($order_list as $os2_key=>$os2_value){
            $os2_condition['order_id'] = $os2_value['order_id'];
            $month = date('Ym',$os2_value['add_time']);
            $attr_one_orderfoodId = M("order_food_$month")->where($os2_condition)->field('order_food_id')->select();
            $attr_orderfoodIdArr = array();
            foreach($attr_one_orderfoodId as $aoof_key=>$aoof_value){
                $attr_orderfoodIdArr[$aoof_key] = $aoof_value['order_food_id'];
            }
            $attr_all_orderfoodId[] = $attr_orderfoodIdArr;
        }

        $attr_all_orderfoodId1 = arrayChange($attr_all_orderfoodId);

        $f_condition1['food_attribute_name'] = array("like","%".$food_name."%");
        $f_condition1['order_food_id'] = array("in",$attr_all_orderfoodId1);
//        $all_attrArr = $order_food_attributeModel->where($f_condition1)->field("order_food_id,food_attribute_name,food_attribute_price,count_type")->select();
        $orderFoodIds = implode(',',$attr_all_orderfoodId1);
        $sql = "SELECT
                        t1.`order_food_id`,
                        t1.`food_attribute_name`,
                        t1.`food_attribute_price`,
                        t1.`count_type`,
                        t1.`num`,
                        t3.order_sn,
                        t3.add_time,
                        t3.order_type,
                        t3.pay_type
                    FROM
                        `tabName3` t1
                        LEFT JOIN `tabName2` t2
                        ON t1.order_food_id = t2.order_food_id
                        LEFT JOIN `tabName1` t3
                        ON t2.order_id = t3.order_id
                    WHERE
                        t1.`food_attribute_name` LIKE '%$food_name%'
                    AND t1.`order_food_id` IN (
                        $orderFoodIds
                    )";

        // 满足条件的分表订单结果集
        $all_attrArr = unionSelect2($startTimeStr,$endTimeStr,$sql);

        //dump($all_attrArr);
        $order_list4 = array();
        foreach($all_attrArr as $aaA_key=>$aaA_value){
            if($aaA_value['count_type'] == 1){
                $orl3_condition['order_food_id'] = $aaA_value['order_food_id'];
                $order_list4[$aaA_key]['order_sn'] = $aaA_value['order_sn'];
                $order_list4[$aaA_key]['food_name'] = $aaA_value['food_attribute_name'];
                $order_list4[$aaA_key]['add_time'] = date('Y-m-d H:i:s',$aaA_value['add_time']);
                $order_list4[$aaA_key]['order_type'] = $order_type2[$aaA_value['order_type']];
                $order_list4[$aaA_key]['pay_type'] = $pay_type2[$aaA_value['pay_type']];
                $order_list4[$aaA_key]['food_price2'] = $aaA_value['food_attribute_price'];
                $order_list4[$aaA_key]['food_num'] = $aaA_value['num'];
                $order_list4[$aaA_key]['total_amount'] = $aaA_value['food_attribute_price'];
            }
        }
        $all_countInfoArr = array();				//合并菜品与菜品属性数组(两数组格式相同，只是把查出来的第二个数组加在第一个数组尾部使其连贯)
        foreach($order_list3 as $ol2_value){
            $all_countInfoArr[] = $ol2_value;
        }
        foreach($order_list4 as $ol3_value){
            $all_countInfoArr[] = $ol3_value;
        }

        $xlsName  = "营业额报表、导出时间(".date("Y-m-d",time()).")";
        $xlsCell  = array(
            array('order_sn','订单号'),
            array('food_name','菜品'),
            array('add_time','日期时间'),
            array('order_type','就餐方式'),
            array('pay_type','支付方式'),
            array('food_price2','单价'),
            array('food_num','数量'),
        );
        exportExcel($xlsName,$xlsCell,$all_countInfoArr);
    }

    // 数据年表中的封装函数
    public function funForYear($restaurant_id,$startTimeStr,$endTimeStr,$payType){
        $sql_orignal="SELECT
                            SUM(total_amount) AS total_amount
                        FROM
                        (
                            SELECT
                                    `order_sn`,
                                    `total_amount`
                                FROM
                                    `tabName1`
                                WHERE
                                    `restaurant_id` = $restaurant_id
                                    AND `order_status` <> 0
                                    AND `add_time` BETWEEN $startTimeStr
                                    AND $endTimeStr
                                    AND `pay_type` = $payType
                                    AND `order_type` IN ('1', '2', '3')
                                GROUP BY
                                    order_sn
                            ) a";
        return $sql_orignal;
    }

    //数据年表
    public function year()
    {
        $restaurant_id = session("restaurant_id");
        if (IS_POST) {
            $Model = M();
            $year = $_POST['year'];
            $month = $returnData = $wx = $ali = $cash = $mem = $minsheng = $returnInfo = $totleInfo = [];
//            $month = [01,02,03,04,05,06,07,08,09,10,11,12];
            $month = [01,02,03,04,05,06,07,8,9,10,11,12];
            /**********功能************/
            $order_model = order();
            $month_list = monthForYear($year);  //返回当前年份的12个月，形如2016-1,2016-2的时间戳
            $m_condition['restaurant_id'] = $restaurant_id;
        foreach($month_list as $k => $v){
            $m_condition['order_status'] = array("neq",0);
            $m_condition['add_time'] = array("between",array($v['month_start'],$v['month_end']));//支付时间在每个月内
            $m_condition['pay_type'] = array("in",'0,1,2,4,5,6');   // 增加了一个余额
            $m_condition['order_type'] = array("in",'1,2,3');
          
            $m_condition['pay_type'] = 0;
//            $cashQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
//            $cashinfo = $order_model->query("select SUM(total_amount) AS total_amount from (".$cashQuery.") a");

            $startTimeStr = $v['month_start'];
            $endTimeStr = $v['month_end'];

            $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,0);
            $cashinfo = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

            if($cashinfo){
                $cash[] = floatval($cashinfo[0]['total_amount']);
            }else{
                $cash[] = 0;
            }

            $m_condition['pay_type'] = 1;
            /*$alipayQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $alipay = $order_model->query("select SUM(total_amount) AS total_amount from (".$alipayQuery.") a");  */
            $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,1);
            $alipay = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

            if($alipay){
                $ali[] = floatval($alipay[0]['total_amount']);
            }else{
                $ali[] = 0;
            }
            $m_condition['pay_type'] = 2;
           /* $wechatQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $wechat = $order_model->query("select SUM(total_amount) AS total_amount from (".$wechatQuery.") a");*/

            $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,2);
            $wechat = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
            if($wechat){
                $wx[] = floatval($wechat[0]['total_amount']);
            }else{
                $wx[] = 0;
            }

            // 新增一个余额:每个月内的订单类型为余额总营业额
            $m_condition['pay_type'] = 4;
            /*$remainderQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $remainder = $order_model->query("select SUM(total_amount) AS total_amount from (".$remainderQuery.") a");*/

            $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,4);
            $remainder = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
            if($remainder){
                $mem[] = floatval($remainder[0]['total_amount']);
            }else{
                $mem[] = 0;
            }

            // 新增一个第四方支付:每个月内的订单类型为余额总营业额
            $m_condition['pay_type'] = 5;
           /* $fourthQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $fourth = $order_model->query("select SUM(total_amount) AS total_amount from (".$fourthQuery.") a");*/
            $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,5);
            $fourth = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
            if($fourth){
                $minsheng[] = floatval($fourth[0]['total_amount']);
            }else{
                $minsheng[] = 0;
            }

        }
            /**********功能************/
            //总计
            /*$totle = $Model->query("SELECT SUM(total_amount) total_amount,months FROM (SELECT pay_type,order_sn,total_amount,from_unixtime(add_time, '%m') months
FROM `order` WHERE from_unixtime(add_time,'%Y')=".$year." AND order_status <> 0 AND restaurant_id=".$restaurant_id." AND pay_type IN (0,1,2,4,5,6) AND order_type IN (1,2,3) GROUP BY order_sn) a GROUP BY months;");*/

            $sql_orignal= "SELECT SUM(total_amount) total_amount,months FROM (SELECT pay_type,order_sn,total_amount,from_unixtime(add_time, '%m') months
FROM `tabName1` WHERE from_unixtime(add_time,'%Y')=".$year." AND order_status <> 0 AND restaurant_id=".$restaurant_id." AND pay_type IN (0,1,2,4,5,6) AND order_type IN (1,2,3) GROUP BY order_sn) a GROUP BY months";
            $totle = unionSelect2(mktime(0,0,0,1,1,$year),mktime(0,0,0,12,1,$year),$sql_orignal);

            $returnData['month'] = $month;
            $returnData['wx'] = $wx;
            $returnData['ali'] = $ali;
            $returnData['cash'] = $cash;
            $returnData['mem'] = $mem;
            $returnData['minsheng'] = $minsheng;
            foreach ($totle as $K => $v) {
                $totleInfo[] = floatval($v['total_amount']);
            }
            $returnData['totle'] = $totleInfo;
             if ($totle) {
                $returnInfo['code'] = 1;
                $returnInfo['msg'] = '数据返回成功';
                $returnInfo['data'] = $returnData;
            }else{
                $returnInfo['code'] = 0;
                $returnInfo['msg'] = '无数据返回';
                $returnInfo['data'] = '';
            }
           $this->ajaxReturn($returnInfo);
        }else{
            //查询该店开店的年份
            $order_model = order();
            $condition['add_time'] = array("neq",0);
            $condition['pay_type'] = array("in",'0,1,2,4,5,6');     // 多了余额(4)
            $condition['order_type'] = array("in",'1,2,3');
            $condition['restaurant_id'] = $restaurant_id;
            $condition['order_status'] = array("neq",0);
            $years = $order_model->where($condition)->field("add_time")->select();
            $year_list = array();

            if(empty($years)){
                $when_year = date("Y");
                $year_list[] = $when_year;
            }else{
                foreach($years as $key => $val){
                    $yearss = date("Y",$val['add_time']);
                    $year_list[] = $yearss; 
                }
            }

            $unique_arr = array_unique ( $year_list );
            $year = date("Y");
            $this->assign("year",$year);
            $this->assign("year_list",$unique_arr);
            $this->display();
        }

    }

    // 数据月表中的封装函数
    public function funForMonth($restaurant_id,$month,$year,$d,$payType){
        $sql_orignal= "SELECT
                        SUM(total_amount) AS total_amount
                      FROM
                        (
                            SELECT
                                `order_sn`,
                                `total_amount`
                            FROM
                                `tabName1`
                            WHERE
                                `restaurant_id` = $restaurant_id
                            AND `order_status` <> 0
                            AND `order_type` IN ('1', '2', '3')
                            AND from_unixtime(add_time, \"%m\") = $month
                            AND from_unixtime(add_time, \"%Y\") = $year
                            AND from_unixtime(add_time, \"%d\") = $d
                            AND `pay_type` = $payType
                            GROUP BY
                                order_sn
                        ) a";
        $sales = unionSelect2(mktime(0,0,0,$month,1,$year),mktime(0,0,0,$month,date('t'),$year),$sql_orignal);
        return $sales;
    }

    // 数据月表
    public function month()
    {
        $restaurant_id = session("restaurant_id");
        if (IS_POST) {
            $year = date('Y');
            $month = $_POST['month'];
            $monthDay = get_day($year,$month);
            /**********功能************/
            $order_model = order();
            $returnData = $wx = $ali = $cash = $mem = $minsheng = $returnInfo = $totleInfo = [];
            $m_condition['restaurant_id'] = $restaurant_id;
            $m_condition['order_status'] = array("neq",0);
            $m_condition['order_type'] = array("in",'1,2,3');
            $m_condition['from_unixtime(add_time, "%m")'] = array("eq",intval($month));
            $m_condition['from_unixtime(add_time, "%Y")'] = array("eq",intval($year));
            $month = intval($month);
            $year = intval($year);
        foreach($monthDay as $dk => $dv){
            $m_condition['from_unixtime(add_time, "%d")'] = array("eq",$dv);
            $m_condition['pay_type'] = array("in","0,1,2,4,5,6");
            // 首先构造子查询SQL 
            /*$salesQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $sales = $order_model->query("select SUM(total_amount) AS total_amount from (".$salesQuery.") a");*/
            $sql_orignal= "SELECT
                        SUM(total_amount) AS total_amount
                    FROM
                        (
                            SELECT
                                `order_sn`,
                                `total_amount`
                            FROM
                                `tabName1`
                            WHERE
                                `restaurant_id` = $restaurant_id
                            AND `order_status` <> 0
                            AND `order_type` IN ('1', '2', '3')
                            AND from_unixtime(add_time, \"%m\") = $month
                            AND from_unixtime(add_time, \"%Y\") = $year
                            AND from_unixtime(add_time, \"%d\") = $dv
                            AND `pay_type` IN ('0', '1', '2', '4', '5')
                            GROUP BY
                                order_sn
                        ) a";
            $sales = unionSelect2(mktime(0,0,0,$month,1,$year),mktime(0,0,0,$month,date('t'),$year),$sql_orignal);
//            p(M()->getLastSql());
            if($sales){
                $totleInfo[] = floatval($sales[0]['total_amount']);
            }else{
                $totleInfo[] = 0;
            }
            $m_condition['pay_type'] = 0;
           /* $cashQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $cashinfo = $order_model->query("select SUM(total_amount) AS total_amount from (".$cashQuery.") a");*/
            $cashinfo = $this->funForMonth($restaurant_id,$month,$year,$dv,0);
//            p(M()->getLastSql());
            if($cashinfo){
                $cash[] = floatval($cashinfo[0]['total_amount']);
            }else{
                $cash[] = 0;
            }
            $m_condition['pay_type'] = 1;
           /* $alipayQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $alipay = $order_model->query("select SUM(total_amount) AS total_amount from (".$alipayQuery.") a");*/
            $alipay = $this->funForMonth($restaurant_id,$month,$year,$dv,1);
            if($alipay){
                $ali[] = floatval($alipay[0]['total_amount']);
            }else{
                $ali[] = 0;
            }
            $m_condition['pay_type'] = 2;
           /* $wechatQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $wechat = $order_model->query("select SUM(total_amount) AS total_amount from (".$wechatQuery.") a");*/
            $wechat = $this->funForMonth($restaurant_id,$month,$year,$dv,2);
            if($wechat){
                $wx[] = floatval($wechat[0]['total_amount']);
            }else{
                $wx[] = 0;
            }

            // 新增一个余额
            $m_condition['pay_type'] = 4;
            /*$remainderQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $remainder = $order_model->query("select SUM(total_amount) AS total_amount from (".$remainderQuery.") a");*/
            $remainder = $this->funForMonth($restaurant_id,$month,$year,$dv,4);
            if($remainder){
                $mem[] = floatval($remainder[0]['total_amount']);
            }else{
                $mem[] = 0;
            }

            // 新增一个余额
            $m_condition['pay_type'] = 5;
            /*$fourthQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
            // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
            $fourth = $order_model->query("select SUM(total_amount) AS total_amount from (".$fourthQuery.") a");*/
            $fourth = $this->funForMonth($restaurant_id,$month,$year,$dv,4);
            if($fourth){
                $minsheng[] = floatval($fourth[0]['total_amount']);
            }else{
                $minsheng[] = 0;
            }
        }
            /**********功能************/

            $returnData['day'] = $monthDay;
            $returnData['wx'] = $wx;
            $returnData['ali'] = $ali;
            $returnData['cash'] = $cash;
            $returnData['mem'] = $mem;
            $returnData['minsheng'] = $minsheng;
            $returnData['totle'] = $totleInfo;
            if ($returnData) {
                $returnInfo['code'] = 1;
                $returnInfo['msg'] = '数据返回成功';
                $returnInfo['data'] = $returnData;
            }else{
                $returnInfo['code'] = 0;
                $returnInfo['msg'] = '无数据返回';
                $returnInfo['data'] = '';
            }
           $this->ajaxReturn($returnInfo);
        }else{
            $month = date("m");
            $this->assign("month",$month);
            $this->display();
        }
    }

    //菜品销售
    public function food_chart(){
        //获取收银员列表
        $cashierModel = D("cashier");
        $where = [];
        $where['restaurant_id'] = session('restaurant_id');
        $cashierList = $cashierModel->where($where)->field("cashier_name,cashier_id")->select();
        $this->assign('cashierList', $cashierList);

        if(I('commit_type') != ""){
            $startDate = I('startDate');
            $endDate = I('endtDate');
            $startTime =I('startTime');
            $endTime = I('endTime');
        }else{
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $this->assign("startDate",$startDate);
        $this->assign("endDate",$endDate);
        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
        }
        $cashier_id = I("cashier_id");//查询菜品信息
        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierid .= " b.cashier_id=".$cashier_id." AND";
        }
        $this->assign('cashier_id', $cashier_id);
        $Model = M();
        $restaurant_id = session('restaurant_id');
        $sql1 = " SELECT food_id,food_name,SUM(food_num) num FROM (SELECT a.order_id,food_id,food_num,food_name FROM tabName2 a LEFT JOIN (SELECT order_id,restaurant_id,add_time,order_status,pay_type,cashier_id from `tabName1` GROUP BY order_sn) b
        ON a.order_id=b.order_id WHERE b.restaurant_id={$restaurant_id} AND {$cashierid} b.add_time >={$startTimeStr} AND b.add_time<={$endTimeStr} AND b.order_status <> 0 AND b.pay_type IN (0,1,2,4,5,6)) c GROUP BY food_id";

        $all_foodinfo = unionSelect2($startTimeStr,$endTimeStr,$sql1);

        //查询菜品规格信息
        $cashierids = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierids = "cashier_id=".$cashier_id." AND";
        }

        $sql4 = "SELECT t1.food_attribute_name,SUM(food_num) num FROM tabName3 t1 inner join tabName2 t2 on t1.order_food_id = t2.order_food_id RIGHT join tabName1 t3 on t2.order_id = t3.order_id
where t3.add_time>={$startTimeStr} AND t3.add_time<={$endTimeStr} AND {$cashierids} restaurant_id = {$restaurant_id} and pay_type IN (0,1,2,4,5,6) and count_type = 1 and order_status<>0 group by food_attribute_name";

        $info = unionSelect2($startTimeStr,$endTimeStr,$sql4);


        $this->assign("all_foodinfo",$all_foodinfo);
        $this->assign("all_attributeArr",$info);

        $num_arr = array();
        foreach($all_foodinfo as $key5=>$value5){
            $num_arr[] = $value5['num'];
        }

        $step_length = 220/max($num_arr);
        $this->assign("step_length",round($step_length ,4));

        foreach($info as $aAA_value){
            $num_arr1[] = $aAA_value['num'];
        }
        $step_length_attr = 220/max($num_arr1);
        $this->assign("step_length_attr",round($step_length_attr ,4));


        $this->display();
    }

    // 菜品销售导出
    public function exportExcal_num(){
        $startDate = I('startDate');
        $endDate = I('endtDate');
        $startTime =I('startTime');
        $endTime = I('endTime');
        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        $condition["restaurant_id"] =  $restaurant_id =  session('restaurant_id');
        $condition['order_status'] = array("neq",0);

        $condition['pay_type'] = array("in",'0,1,2,4,5,6');		//第三个条件：支付方式      新增一个余额支付


        $sql2 = "select DISTINCT food_id,food_name from tabName2 a LEFT JOIN tabName1 b ON a.order_id = b.order_id  WHERE add_time>={$startTimeStr} AND add_time<={$endTimeStr} AND restaurant_id = {$restaurant_id} AND pay_type IN (0,1,2,4,5,6) AND order_status <> 0 ORDER BY b.order_id";
        $food_idArr  = unionSelect2($startTimeStr,$endTimeStr,$sql2);

        $all_foodinfo = array();

        foreach($food_idArr as $key1=>$value1){
            $all_foodinfo[$key1]['food_name'] = $value1['food_name'];

            //统计出数量，时间为：当前时间
            $sql4 = "select food_num from tabName2 a LEFT join tabName1 b ON a.order_id = b.order_id WHERE food_id = {$value1['food_id']} AND add_time>={$startTimeStr} AND add_time<={$endTimeStr} AND restaurant_id = {$restaurant_id} AND pay_type IN (0,1,2,4,5,6) AND order_status <> 0";
            $food_numArr = unionSelect2($startTimeStr,$endTimeStr,$sql4);

            foreach($food_numArr as $kn=>$vn){
                $all_foodinfo[$key1]['food_num'] += $vn['food_num'];
            }

            //查询去年的时间及订单
            $last_year = date('Y',strtotime("-1 year")); //去年
            $lastyear_monthArr = monthForYear($last_year);
            $lastyear_allOrderNum = array();
            foreach($lastyear_monthArr as $key2=>$value2){
                $condition['add_time'] = array('between',array($value2['month_start'],$value2['month_end']));

                $sql5 = "select order_id from tabName1 WHERE add_time BETWEEN {$value2['month_start']} AND {$value2['month_end']} AND restaurant_id = {$restaurant_id} AND pay_type IN (0,1,2,4,5,6) AND order_status <> 0 GROUP BY order_sn";
                $whenResturant_OrderArr1 = unionSelect2($value2['month_start'],$value2['month_end'],$sql5);
                $lastyear_OrderIdArr = array();						//当月的订单
                foreach($whenResturant_OrderArr1 as $key3=>$value3){
                    $lastyear_OrderIdArr[] = $value3['order_id'];
                }
                if(!empty($lastyear_OrderIdArr)){
//						$condition3['order_id'] = array("in",$lastyear_OrderIdArr);
//						$condition3['food_id'] = $value1['food_id'];
//						$food_numArr1 = $order_food->where($condition3)->field('food_num')->select();		//当前条年的菜品份数

                    //替换成sql6
                    $sql6 = "select food_num from tabName2 a LEFT join tabName1 b ON a.order_id = b.order_id WHERE food_id = {$value1['food_id']} AND add_time BETWEEN {$value2['month_start']} AND {$value2['month_end']} AND restaurant_id = {$restaurant_id} AND pay_type IN (0,1,2,4,5,6) AND order_status <> 0";
                    $food_numArr1 = unionSelect2($value2['month_start'],$value2['month_end'],$sql6);
                    foreach($food_numArr1 as $kn1=>$vn1){
                        $lastyear_allOrderNum[$key2] += $vn1['food_num'];
                    }
                }else{
                    $lastyear_allOrderNum[$key2] = 0;
                }
            }
            $all_foodinfo[$key1]['year'] = $last_year;								//去年年份
            $all_foodinfo[$key1]['lastyear_allOrderNum'] = $lastyear_allOrderNum;	//去年的每月该菜品份数

            //前年的数据
            $previous_year = date('Y',strtotime("-2 year"));//前年
            $lastyear_monthArr1 = monthForYear($previous_year);
            $lastyear_allOrderNum1 = array();
            foreach($lastyear_monthArr1 as $key3=>$value3){							//将一年分为12个月
//					$condition['add_time'] = array('between',array($value3['month_start'],$value3['month_end']));
//
//					$whenResturant_OrderArr2 = $order->where($condition)->field('order_id')->group('order_sn')->select(); //每月的订单集
//					$lastyear_OrderIdArr1 = array();
//					foreach($whenResturant_OrderArr2 as $key4=>$value4){
//						$lastyear_OrderIdArr1[] = $value4['order_id'];
//					}
//					if(!empty($lastyear_OrderIdArr1)){
//						$condition4['order_id'] = array("in",$lastyear_OrderIdArr1);
//						$condition4['food_id'] = $value1['food_id'];
//						$food_numArr2 = $order_food->where($condition4)->field('food_num')->select();		//当前条年的菜品份数

                //替换成sql7
                $sql7 = "select food_num from tabName2 a LEFT join tabName1 b ON a.order_id = b.order_id WHERE food_id = {$value1['food_id']} AND add_time BETWEEN {$value3['month_start']} AND {$value3['month_end']} AND restaurant_id = {$restaurant_id} AND pay_type IN (0,1,2,4,5,6) AND order_status <> 0";
                $food_numArr2 = unionSelect2($value2['month_start'],$value2['month_end'],$sql7);

                foreach($food_numArr2 as $kn2=>$vn2){
                    $lastyear_allOrderNum1[$key3] += $vn2['food_num'];
                }

            }
            $all_foodinfo[$key1]['year1'] = $previous_year;
            $all_foodinfo[$key1]['lastyear_allOrderNum1'] = $lastyear_allOrderNum1;
            $all_foodinfo[$key1]['sort'] = $key1+1;
        }




//			p($all_foodinfo);
        $xlsName  = "菜品图表、导出时间(".date("Y-m-d",time()).")";
        $xlsSearchDate = "日期：".date("Y-m-d h:i:s",$startTimeStr)." 至 ".date("Y-m-d h:i:s",$endTimeStr);;
        exportExcel1($xlsName,$xlsSearchDate,$all_foodinfo);
    }



    /************外卖统计**********************/
    // 美团订单统计
    public function meituan(){
        if(isset($_POST['startDate']) && isset($_POST['endtDate']) && isset($_POST['startTime']) && isset($_POST['endTime'])){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
        }else {
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $this->assign("startDate",$startDate);
        $this->assign("endDate",$endDate);
        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);

        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 20;
        $condition1['jubaopen_order.ctime'] = array("between",array($startTimeStr,$endTimeStr));
        $restaurant_id = session('restaurant_id');
        $condition1['jubaopen_order.ePoiId'] = $restaurant_id;
        $condition1['jubaopen_result.status'] = 8;
        $jubaopen_order = D("jubaopen_order");
        // 总的份数
        $count=$jubaopen_order
//            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId')
            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId or jubaopen_order.orderId=jubaopen_result.orderId')
            ->where($condition1)
            ->count('distinct(orderIdView)');
        $this->assign("count",$count);

        // 订单详情
        $order_list=$jubaopen_order
//            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId')
            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId or jubaopen_order.orderId=jubaopen_result.orderId')
            ->where($condition1)
            ->page($page,$page_num)
            ->field('jubaopen_order.orderIdView,jubaopen_order.ctime,jubaopen_order.daySeq,jubaopen_order.detail,jubaopen_result.activityDetails,
            jubaopen_result.commisionAmount,jubaopen_result.foodAmount,jubaopen_result.payType,jubaopen_result.settleAmount,
            jubaopen_result.shippingAmount,jubaopen_result.totalActivityAmount')
            ->group('jubaopen_order.orderIdView')
            ->order('jubaopen_order.id desc')
            ->select();

        foreach($order_list as $key=>$val){
            $order_list[$key]['detail'] = json_decode($order_list[$key]['detail'],true);
            $order_list[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
        }

        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');
        $this->assign("page1",$show);

        $show_fanti = str_replace('上一页','上壹頁',$show);
        $show_fanti = str_replace('下一页','下壹頁',$show_fanti);
        $show_fanti = str_replace('首页','首頁',$show_fanti);
        $this->assign('page2',$show_fanti);//繁体页数

        $show_yin = str_replace('上一页','Previous',$show);
        $show_yin = str_replace('下一页','next',$show_yin);
        $show_yin = str_replace('首页','first',$show_yin);
        $this->assign('page3',$show_yin);//英文页数

        $this->assign("order_list",$order_list);

        $Model = D('jubaopen_order');
        // 商家总收入、总的商品金额、总的抽佣金额、总的配送金额，总的活动金额
        /*$query_total = 'SELECT SUM(commisionAmount) AS sum_chouyong,SUM(foodAmount) AS sum_goodsAmount,SUM(settleAmount) AS sum_restaurant_income,
SUM(shippingAmount) AS sum_shippingAmount,SUM(totalActivityAmount) AS sum_totalActivityAmount FROM
(SELECT a.orderIdView,a.ctime,a.detail,b.activityDetails,b.commisionAmount,b.foodAmount,b.payType,b.settleAmount,b.shippingAmount,b.totalActivityAmount
FROM jubaopen_order AS a LEFT JOIN jubaopen_result AS b ON a.orderIdView = b.orderId WHERE b.`status`=8 AND a.`ePoiId` = '.$restaurant_id.' AND a.`ctime` BETWEEN '.$startTimeStr.' AND '.$endTimeStr. ' GROUP BY a.orderIdView ORDER BY a.id) AS c';*/

        $query_total = 'SELECT SUM(commisionAmount) AS sum_chouyong,SUM(foodAmount) AS sum_goodsAmount,SUM(settleAmount) AS sum_restaurant_income,
SUM(shippingAmount) AS sum_shippingAmount,SUM(totalActivityAmount) AS sum_totalActivityAmount FROM
(SELECT a.orderIdView,a.ctime,a.detail,b.activityDetails,b.commisionAmount,b.foodAmount,b.payType,b.settleAmount,b.shippingAmount,b.totalActivityAmount
FROM jubaopen_order AS a LEFT JOIN jubaopen_result AS b ON a.orderIdView = b.orderId OR a.orderId = b.orderId WHERE b.`status`=8 AND a.`ePoiId` = '.$restaurant_id.' AND a.`ctime` BETWEEN '.$startTimeStr.' AND '.$endTimeStr. ' GROUP BY a.orderIdView ORDER BY a.id) AS c';
        $total = $Model->query($query_total);
        $total = $total[0];
        foreach($total as $key=>$val){
            if($val == null){
                $total[$key] = 0;
            }
        }
        $this->assign("total",$total);
        $this->display();
    }

    // 美团订单点击分页时
    public function meituan_order_page(){
        if(isset($_POST['startDate']) && isset($_POST['endtDate']) && isset($_POST['startTime']) && isset($_POST['endTime'])){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
        }else {
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $this->assign("startDate",$startDate);
        $this->assign("endDate",$endDate);
        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);

        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 20;
        $condition1['jubaopen_order.ctime'] = array("between",array($startTimeStr,$endTimeStr));
        $condition1['jubaopen_order.ePoiId'] = session('restaurant_id');
        $condition1['jubaopen_result.status'] = 8;
        $jubaopen_order = D("jubaopen_order");
        // 总的份数
        $count=$jubaopen_order
//            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId')
            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId or jubaopen_order.orderId=jubaopen_result.orderId')
            ->where($condition1)
            ->count('distinct(orderIdView)');
        $this->assign("count",$count);

        // 订单详情
        $order_list=$jubaopen_order
//            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId')
            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId or jubaopen_order.orderId=jubaopen_result.orderId')
            ->where($condition1)
            ->page($page,$page_num)
            ->field('jubaopen_order.orderIdView,jubaopen_order.ctime,jubaopen_order.daySeq,jubaopen_order.detail,jubaopen_result.activityDetails,
            jubaopen_result.commisionAmount,jubaopen_result.foodAmount,jubaopen_result.payType,jubaopen_result.settleAmount,
            jubaopen_result.shippingAmount,jubaopen_result.totalActivityAmount')
            ->group('jubaopen_order.orderIdView')
            ->order('jubaopen_order.id desc')
            ->select();

        foreach($order_list as $key=>$val){
            $order_list[$key]['detail'] = json_decode($order_list[$key]['detail'],true);
            $order_list[$key]['ctime'] = date('Y-m-d H:i:s',$val['ctime']);
        }

        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');
        $this->assign("page1",$show);

        $show_fanti = str_replace('上一页','上壹頁',$show);
        $show_fanti = str_replace('下一页','下壹頁',$show_fanti);
        $show_fanti = str_replace('首页','首頁',$show_fanti);
        $this->assign('page2',$show_fanti);//繁体页数

        $show_yin = str_replace('上一页','Previous',$show);
        $show_yin = str_replace('下一页','next',$show_yin);
        $show_yin = str_replace('首页','first',$show_yin);
        $this->assign('page3',$show_yin);//英文页数

        $this->assign("order_list",$order_list);
        $this->display('ajaxMeituanOrderInfo');
    }

    // 美团导出Excel表
    public function exportExcel_meituan(){			//导出Excel表
        if(isset($_POST['startDate']) && isset($_POST['endtDate']) && isset($_POST['startTime']) && isset($_POST['endTime'])){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
        }else {
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);

        $condition1['jubaopen_order.ctime'] = array("between",array($startTimeStr,$endTimeStr));
        $restaurant_id = session('restaurant_id');
        $condition1['jubaopen_order.ePoiId'] = $restaurant_id;
        $condition1['jubaopen_result.status'] = 8;
        $jubaopen_order = D("jubaopen_order");

        // 订单详情
        $orderArr=$jubaopen_order
//            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId')
            ->join('left join jubaopen_result on jubaopen_order.orderIdView=jubaopen_result.orderId or jubaopen_order.orderId=jubaopen_result.orderId')
            ->where($condition1)
            ->field('jubaopen_order.orderIdView,jubaopen_order.ctime,jubaopen_order.daySeq,
            jubaopen_result.commisionAmount,jubaopen_result.foodAmount,jubaopen_result.payType,jubaopen_result.settleAmount,
            jubaopen_result.shippingAmount,jubaopen_result.totalActivityAmount')
            ->group('jubaopen_order.orderIdView')
            ->order('jubaopen_order.id desc')
            ->select();

        $orderList = array();
        foreach($orderArr as $key=>$value){
//            $obj1['orderIdView'] = $value['orderIdView'];
            $obj1['daySeq'] = $value['daySeq'];
            $obj1['ctime'] = date('Y-m-d H:i:s',$value['ctime']);
            $obj1['pay_type'] = $value['pay_type'] == 1 ? '货到付款': '在线支付';
            $obj1['foodAmount'] = $value['foodAmount'];
            $obj1['commisionAmount'] = $value['commisionAmount'];
            $obj1['shippingAmount'] = $value['shippingAmount'];
            $obj1['totalActivityAmount'] = $value['totalActivityAmount'];
            $obj1['settleAmount'] = $value['settleAmount'];
            $orderList[] = $obj1;
        }
        $xlsName  = "营业额报表、导出时间(".date("Y-m-d",time()).")";
                //        日期时间	订单号	支付类型	商品金额	抽佣金额	配送费	总活动款	商家收入
                        $xlsCell  = array(
                //            array('orderIdView','订单号'),
                array('daySeq','流水号'),
                array('ctime','日期时间'),
                array('pay_type','支付类型'),
                array('foodAmount','商品金额'),
                array('commisionAmount','抽佣金额'),
                array('shippingAmount','配送费'),
                array('totalActivityAmount','总活动款'),
                array('settleAmount','商家收入'),
                        );
        exportExcel($xlsName,$xlsCell,$orderList);
    }

    // 饿了么订单统计
    public function eleme(){
        if(isset($_POST['startDate']) && isset($_POST['endtDate']) && isset($_POST['startTime']) && isset($_POST['endTime'])){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
        }else {
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $this->assign("startDate",$startDate);
        $this->assign("endDate",$endDate);
        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);

        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 20;
        $condition1['activeAt'] = array("between",array($startTimeStr,$endTimeStr));
        $condition1['final_type'] = 18;
        $restaurant_id = session('restaurant_id');
        $condition1['restaurant_id'] = $restaurant_id;    // 此店铺ID是插入订单时绑定的
        $eleme_order = D("eleme_order");
        // 总的份数
        $count=$eleme_order
            ->where($condition1)
            ->count('distinct(orderId)');

        // 订单详情
        $order_list=$eleme_order
            ->where($condition1)
            ->page($page,$page_num)
            ->field('orderId,activeAt,groups,income,serviceFee,deliverFee,totalPrice,originalPrice,daySn')
            ->group('orderId')
            ->order('primary_id desc')
            ->select();


        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');
        $this->assign("page",$show);
        foreach($order_list as $key=>$val){
            $order_list[$key]['groups'] = json_decode($val['groups']);
            $order_list[$key]['activeAt'] = date('Y-m-d H:i:s',$val['activeAt']);
        }
        $this->assign("order_list",$order_list);

        $Model = D('eleme_order');
        // 商户实收、订单数、服务费、配送金额
        $query_total = 'SELECT SUM(income) AS income_sum,COUNT(primary_id) AS order_count,SUM(serviceFee) AS serviceFee_sum,
SUM(deliverFee) AS deliverFee_sum FROM (SELECT a.income,a.primary_id,a.serviceFee,a.deliverFee from eleme_order a
WHERE `final_type`=18 AND `restaurant_id` = '.$restaurant_id.' AND `activeAt` BETWEEN '.$startTimeStr.' AND '.$endTimeStr.' GROUP BY orderId) as b ';
        $total = $Model->query($query_total);

        $income_sum = $total[0]['income_sum'] == null ? 0 : $total[0]['income_sum'];
        $serviceFee_sum = $total[0]['serviceFee_sum'] == null ? 0 : $total[0]['serviceFee_sum'];
        $deliverFee_sum = $total[0]['deliverFee_sum'] == null ? 0 : $total[0]['deliverFee_sum'];
        $this->assign("income_sum",$income_sum);
        $this->assign("serviceFee_sum",$serviceFee_sum);
        $this->assign("deliverFee_sum",$deliverFee_sum);
        $this->assign("order_count",$total[0]['order_count']);

        $this->display();
    }

    // 饿了么订单点击分页时
    public function eleme_order_page(){
        if(isset($_POST['startDate']) && isset($_POST['endtDate']) && isset($_POST['startTime']) && isset($_POST['endTime'])){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
        }else {
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $this->assign("startDate",$startDate);
        $this->assign("endDate",$endDate);
        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);

        $page = I("get.page") ? I("get.page") : 1;
        $page_num = 20;
        $condition1['activeAt'] = array("between",array($startTimeStr,$endTimeStr));
        $condition1['final_type'] = 18;
        $restaurant_id = session('restaurant_id');
        $condition1['restaurant_id'] = $restaurant_id;    // 此店铺ID是插入订单时绑定的
        $eleme_order = D("eleme_order");
        // 总的份数
        $count=$eleme_order
            ->where($condition1)
            ->count('distinct(orderId)');

        // 订单详情
        $order_list=$eleme_order
            ->where($condition1)
            ->page($page,$page_num)
            ->field('orderId,activeAt,groups,income,serviceFee,deliverFee,totalPrice,originalPrice,daySn')
            ->group('orderId')
            ->order('primary_id desc')
            ->select();

        $Page = new \Think\PageAjax($count,$page_num);
        $show = $Page->show('');
        $this->assign("page",$show);
        foreach($order_list as $key=>$val){
            $order_list[$key]['groups'] = json_decode($val['groups']);
            $order_list[$key]['activeAt'] = date('Y-m-d H:i:s',$val['activeAt']);
        }
        $this->assign("order_list",$order_list);
        $this->display('ajaxElemeOrderInfo');
    }

    // 饿了么导出Excel表
    public function exportExcel_eleme(){			//导出Excel表
        if(isset($_POST['startDate']) && isset($_POST['endtDate']) && isset($_POST['startTime']) && isset($_POST['endTime'])){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
        }else {
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
            $startDate = date("Y-m-d",$beginThisMonth);
            $endDate = date("Y-m-d",$endThisMonth);
            $startTime = "00:00:00";
            $endTime = "23:59:59";
        }
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);

        $condition['activeAt'] = array("between",array($startTimeStr,$endTimeStr));
        $condition['final_type'] = 18;
        $restaurant_id = session('restaurant_id');
        $condition['restaurant_id'] = $restaurant_id;    // 此店铺ID是插入订单时绑定的
        $eleme_order = D("eleme_order");
        // 订单详情
        $orderArr = $eleme_order
            ->where($condition)
            ->field('orderId,activeAt,groups,income,serviceFee,deliverFee,totalPrice,originalPrice,daySn')
            ->group('orderId')
            ->order('primary_id desc')
            ->select();

        $orderList = array();
        foreach($orderArr as $key=>$value){
//            $obj1['order_sn'] = $value['orderId'];    // 订单号
            $obj1['daySn'] = $value['daySn'];   // 流水号
            $obj1['add_time'] = date('Y-m-d H:i:s',$value['activeAt']);
            $obj1['serviceFee'] = $value['serviceFee'];
            $obj1['deliverFee'] = $value['deliverFee'];
            $obj1['income'] = $value['income'];     // 商家实收
            $obj1['originalPrice'] = $value['originalPrice'];     // 原价
            $obj1['totalPrice'] = $value['totalPrice'];     // 客户实际支付

            $orderList[] = $obj1;
        }
        $xlsName  = "营业额报表、导出时间(".date("Y-m-d",time()).")";
        $xlsCell  = array(
        //            array('order_sn','订单号'),
        array('daySn','流水号'),
        array('add_time','日期时间'),
        array('serviceFee','服务费'),
        array('deliverFee','配送费'),
        array('originalPrice','原价'),
        array('totalPrice','客户实际支付'),
        array('income','商家实收'),
        );
        exportExcel($xlsName,$xlsCell,$orderList);
    }
    /************外卖统计**********************/
	
}