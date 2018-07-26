<?php
namespace MobileAdmin\Controller;
use Think\Controller;

class SaleController extends Controller {

    public function __construct(){
        Controller::__construct();
        $admin_id = session("re_admin_id");
        if(!$admin_id){
            redirect("/index.php/MobileAdmin/Index/login");
        }
        $restaurant_manager_model = D('restaurant_manager');
        $restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
        session('restaurant_id',$restaurant_id);
    }

	// 明细报表
    public function index(){
        $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
        $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）

        $startDate = date("Y/m/d",$beginThisMonth);
        $this->assign("startDate",$startDate);

        $endDate = date("Y/m/d",$endThisMonth);
        $this->assign("endDate",$endDate);

        $startTime = "00:00";
        $endTime = "23:59";

        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);

        $condition = array();

        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));
        }
        $condition['pay_type'] = array("in",'0,1,2,4,5');
		$condition['order_type'] = array("in",'1,2,3');
        $restaurant_id = session('restaurant_id');
        $condition["restaurant_id"] = $restaurant_id;
		$condition['order_status'] = array("neq",0);
        $orderModel = order();
//        $total_amount = $orderModel->where($condition)->sum("total_amount");
//        p(M()->getLastSql());
        // 分表统计总数
        $sql_orignal="SELECT
                            SUM(total_amount) AS tp_sum
                        FROM
                            `tabName`
                        WHERE
                            `add_time` BETWEEN $startTimeStr
                        AND $endTimeStr
                        AND `pay_type` IN ('0', '1', '2', '4', '5')
                        AND `order_type` IN ('1', '2', '3')
                        AND `restaurant_id` = $restaurant_id
                        AND `order_status` <> 0
                        LIMIT 1";
        $total_amount = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');


        $all_total_amount = number_format($total_amount,2);
        $this->assign("total_amount",$all_total_amount);

        $this->display();
    }

    // 明细报表异步获取
    public function orderInfo(){
        /**
         * 搜索条件
         */
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");
        $condition = array();
        $startDate = empty($startDate) ? date("Y/m/d",time()) : $startDate;
        $endDate = empty($endDate) ? $startDate : $endDate;
        $startTime = empty($startTime) ? '00:00' : $startTime;
        $endTime = empty($endTime) ? '23:59' : $endTime;
        $startTimeStr = strtotime($startDate." ".$startTime);
        $endTimeStr = strtotime($endDate." ".$endTime);
        $condition['add_time'] = array("between",array($startTimeStr,$endTimeStr));

        //支付类型
        $paytype = array(0,1,2,4,5);
        $condition['pay_type'] = array("in",$paytype);
        $paytypeStr = '0,1,2,4,5';

        //就餐方式
        $ordertype = array(1,2,3);
        $condition['order_type'] = array("in",$ordertype);
        $ordertypeStr = '1,2,3';

        $refuseStr = '0,1,2';

        $orderModel = order();
        $restaurant_id = session('restaurant_id');
        $condition["restaurant_id"] = session('restaurant_id');
        $condition['order_status'] = array("neq",0);

        $condition["restaurant_id"] = session('restaurant_id');
        $condition['order_status'] = array("neq",0);

//        $order_lists = $orderModel->where($condition)->group('order_sn')->order("order_id desc")->select();

        // 满足条件的分表订单结果集（order和order_food连表查询）
        $sql_orignal="SELECT
                            t1.order_id,t1.refuse,t1.total_amount,
                            t2.food_price2,
                            t2.food_num,
                            t2.refuse_num
                        FROM
                            `tabName1` t1
                            LEFT JOIN `tabName2` t2
                            ON t1.order_id = t2.order_id
                        WHERE
                            t1.`add_time` BETWEEN $startTimeStr
                        AND $endTimeStr
                        AND t1.`pay_type` IN (0, 1, 2, 4, 5)
                        AND t1.`order_type` IN (1, 2, 3)
                        AND t1.`restaurant_id` = $restaurant_id
                        AND t1.`order_status` <> 0
                        ORDER BY
                            t1.order_id DESC";

        $order_lists = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

        $refuse_num = 0;    // 退菜份数
        $refuse_total = 0;
        $order_food_model = order_F();
        /*foreach($order_lists as $key => $val){
            $condition['order_id'] = $val['order_id'];
            $food_lists = $order_food_model->where($condition)->field("order_id,food_id,food_price2,food_num,food_name,order_food_id,refuse_num")->select();
            if ($val['refuse'] == 1) {
                $refuse_total += $val['total_amount'];
            }

            foreach($food_lists as $key1=>$value1){
                if ($val['refuse'] == 1) {
                    $refuse_num += $value1['food_num'];
                }
                if ($val['refuse'] == 2) {
                    $refuse_num += $value1['refuse_num'];
                    if ($value1['refuse_num'] > 0) {
                        $refuse_total += $value1['food_price2'];
                    }
                }
            }
        }*/

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

        $Model = M();
        $restaurant_id = session('restaurant_id');
        //订单信息
        /*$order_list = $Model->query("SELECT `total_amount`,pay_type,order_type,restaurant_id,order_status,add_time,order_sn,refuse FROM `order`   WHERE
 `restaurant_id` = ".$restaurant_id." AND `order_status` <> 0 AND pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND refuse IN (".$refuseStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn");*/

        $orderListSql = "SELECT `total_amount`,pay_type,order_type,restaurant_id,order_status,add_time,order_sn,refuse FROM `tabName1`   WHERE
 `restaurant_id` = ".$restaurant_id." AND `order_status` <> 0 AND pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND refuse IN (".$refuseStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn";

        // 满足条件的分表订单结果集
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$orderListSql);

        //订单总数
        /*$count = $Model->query("SELECT order_sn,refuse FROM `order` WHERE
`restaurant_id` = ".$restaurant_id." AND `order_status` <> 0 AND  pay_type IN (".$paytypeStr.") AND order_type IN (".$ordertypeStr.") AND refuse IN (".$refuseStr.") AND `add_time` BETWEEN ".$startTimeStr." AND ".$endTimeStr." GROUP BY order_sn");*/

        $re_count = 0;
        foreach ($order_list as $k => $v) {
            if ($v['refuse'] !=0 ) {
                $re_count++;
            }
        }
        $wechat = 0;
        $alipay = 0;
        $cash   = 0;
        $member = 0;
        $fourth = 0;
        $count = count($order_list);
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
        $statisData['cash'] = floatval($cash);
        $statisData['alipay'] = floatval($alipay);
        $statisData['wechat'] = floatval($wechat);
        $statisData['member'] = floatval($member);
        $statisData['fourth'] = floatval($fourth);

        $statisData['total'] = $cash+$alipay+$wechat+$member+$fourth;
        $statisData['count'] = $count;
        $statisData['re_count'] = $re_count;
        $statisData['refuse_num'] = $refuse_num;
        $statisData['refuse_total'] = $refuse_total;

        $this->assign('statisData', $statisData);

        $this->display("ajaxOrderInfo");
    }

    //菜品图表
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
            $startDate = date("Y/m/d",$beginThisMonth);
            $endDate = date("Y/m/d",$endThisMonth);
            $startTime = "00:00";
            $endTime = "23:59";
        }
        $this->assign("startDate",$startDate);
        $this->assign("endDate",$endDate);
        $this->assign("startTime",$startTime);
        $this->assign("endTime",$endTime);
        $this->display();
    }

    public function food_chart_ajax(){
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
        }
        $cashier_id = I("cashier_id");
        $cashierid = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierid .= " b.cashier_id=".$cashier_id." AND";
        }
        $this->assign('cashier_id', $cashier_id);
        $Model = M();
        $restaurant_id = session('restaurant_id');


        //查询菜品
        $sql1 = "SELECT food_id,food_name,SUM(food_num) num FROM (SELECT a.order_id,food_id,food_num,food_name FROM tabName2 a LEFT JOIN (SELECT order_id,restaurant_id,add_time,order_status,pay_type,cashier_id from tabName1 GROUP BY order_sn) b
        ON a.order_id=b.order_id WHERE b.restaurant_id={$restaurant_id} AND {$cashierid} b.add_time >= {$startTimeStr} AND b.add_time<= {$endTimeStr} AND b.order_status <> 0 AND b.pay_type IN (0,1,2,4,5)) c GROUP BY food_id";
        $all_foodinfo = unionSelect2($startTimeStr,$endTimeStr,$sql1);

        $cashierids = '';
        if ($cashier_id) {
            $cashier_id = intval($cashier_id);
            $cashierids .= " cashier_id=".$cashier_id." AND";
        }

        //查询菜品信息
        $sql4 = "SELECT t1.food_attribute_name,SUM(food_num) num FROM tabName3 t1 inner join tabName2 t2 on t1.order_food_id = t2.order_food_id RIGHT join tabName1 t3 on t2.order_id = t3.order_id
where t3.add_time>={$startTimeStr} AND t3.add_time<={$endTimeStr} AND {$cashierids} restaurant_id = {$restaurant_id} and pay_type IN (0,1,2,4,5) and count_type = 1 and order_status<>0 group by food_attribute_name";

        $info = unionSelect2($startTimeStr,$endTimeStr,$sql4);

        $this->assign("all_foodinfo",$all_foodinfo);
        $this->assign("all_attributeArr",$info);

        $num_arr = array();
        foreach($all_foodinfo as $key5=>$value5){
            $num_arr[] = $value5['num'];
        }

        $step_length = 150/max($num_arr);
        $this->assign("step_length",round($step_length ,4));

        $num_arr1 = array();
        foreach($info as $aAA_value){
            $num_arr1[] = $aAA_value['num'];
        }
        $step_length_attr = 150/max($num_arr1);
        $this->assign("step_length_attr",round($step_length_attr ,4));

        $this->display('food_chart_ajax');
    }

    //数据年表
    public function year()
    {
        $restaurant_id = session("restaurant_id");
        if (IS_POST) {
            $Model = M();
            $year = $_POST['year'];
            $month = $returnData = $wx = $ali = $cash = $mem = $returnInfo = $totleInfo = [];
            /**********功能************/
            $order_model = order();
            $month_list = monthForYear($year);  //返回当前年份的12个月，形如2016-1,2016-2的时间戳
            $m_condition['restaurant_id'] = $restaurant_id;

            $every_month_data = [];
            $every_month_inner_data = [];
            foreach($month_list as $k => $v){
                $m_condition['order_status'] = array("neq",0);
                $m_condition['add_time'] = array("between",array($v['month_start'],$v['month_end']));//支付时间在每个月内
                $m_condition['pay_type'] = array("in",'0,1,2,4,5');   // 增加了一个余额
                $m_condition['order_type'] = array("in",'1,2,3');

                $m_condition['pay_type'] = 0;
               /* $cashQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $cashinfo = $order_model->query("select SUM(total_amount) AS total_amount from (".$cashQuery.") a");*/
//                p(M()->getLastSql());

                $startTimeStr = $v['month_start'];
                $endTimeStr = $v['month_end'];
                $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,0);
                $cashinfo = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

                if($cashinfo){
                    $every_month_inner_data['cash'] = floatval($cashinfo[0]['total_amount']);
                }else{
                    $every_month_inner_data['cash'] = 0;
                }
                $m_condition['pay_type'] = 1;
                /*$alipayQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $alipay = $order_model->query("select SUM(total_amount) AS total_amount from (".$alipayQuery.") a");*/

                $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,1);
                $alipay = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

                if($alipay){
                    $every_month_inner_data['alipay'] = floatval($alipay[0]['total_amount']);
                }else{
                    $every_month_inner_data['alipay'] = 0;
                }
                $m_condition['pay_type'] = 2;
               /* $wechatQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $wechat = $order_model->query("select SUM(total_amount) AS total_amount from (".$wechatQuery.") a");*/

                $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,2);
                $wechat = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
                if($wechat){
                    $every_month_inner_data['wechat'] = floatval($wechat[0]['total_amount']);
                }else{
                    $every_month_inner_data['wechat'] = 0;
                }

                // 新增一个余额:每个月内的订单类型为余额总营业额
                $m_condition['pay_type'] = 4;
               /* $remainderQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $remainder = $order_model->query("select SUM(total_amount) AS total_amount from (".$remainderQuery.") a");*/

                $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,4);
                $remainder = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
                if($remainder){
                    $every_month_inner_data['remainder'] = floatval($remainder[0]['total_amount']);
                }else{
                    $every_month_inner_data['remainder'] = 0;
                }

                // 新增一个第四方支付:每个月内的订单类型为余额总营业额
                $m_condition['pay_type'] = 5;
                /*$fourthQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $fourth = $order_model->query("select SUM(total_amount) AS total_amount from (".$fourthQuery.") a");*/

                $sql_orignal=$this->funForYear($restaurant_id,$startTimeStr,$endTimeStr,5);
                $fourth = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

                if($fourth){
                    $every_month_inner_data['fourth'] = floatval($fourth[0]['total_amount']);
                }else{
                    $every_month_inner_data['fourth'] = 0;
                }
                $every_month_data[] = $every_month_inner_data;
            }

            $this->assign("every_month_data",$every_month_data);

            //总计
            /*$totle = $Model->query("SELECT SUM(total_amount) total_amount,months FROM (SELECT pay_type,order_sn,total_amount,from_unixtime(add_time, '%m') months
FROM `order` WHERE from_unixtime(add_time,'%Y')=".$year." AND order_status <> 0 AND restaurant_id=".$restaurant_id." AND pay_type IN (0,1,2,4,5) AND order_type IN (1,2,3) GROUP BY order_sn) a GROUP BY months;");*/

            $sql_orignal= "SELECT SUM(total_amount) total_amount,months FROM (SELECT pay_type,order_sn,total_amount,from_unixtime(add_time, '%m') months
FROM `tabName1` WHERE from_unixtime(add_time,'%Y')=".$year." AND order_status <> 0 AND restaurant_id=".$restaurant_id." AND pay_type IN (0,1,2,4,5) AND order_type IN (1,2,3) GROUP BY order_sn) a GROUP BY months";
            $totle = unionSelect2(mktime(0,0,0,1,1,$year),mktime(0,0,0,12,1,$year),$sql_orignal);

            for($i=1;$i<13;$i++){
                if($i<10){
                    $k = '0'.$i;
                    $totleInfo[$k] = 0;
                }else{
                    $totleInfo[$i] = 0;
                }
            }

            $all_year_total = 0;
            foreach ($totle as $K => $v) {
                $totleInfo[$v['months']] = floatval($v['total_amount']);
                $all_year_total += floatval($v['total_amount']);
            }
            $returnData['totle'] = $totleInfo;

            $this->assign("returnData",$returnData);
            $this->assign("all_year_total",$all_year_total);

            $step_length = 150/max($totleInfo);
            $this->assign("step_length",round($step_length ,4));


            $this->display('year_ajax');
        }else{
            //查询该店开店的年份
            $order_model = order();
            $condition['add_time'] = array("neq",0);
            $condition['pay_type'] = array("in",'0,1,2,4,5');     // 多了余额(4)
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
                    if($yearss>=2017){
                        $year_list[] = $yearss;
                    }
                }
            }
            $unique_arr = array_unique ( $year_list );
            $year = date("Y");
            $this->assign("year",$year);
            $this->assign("year_list",$unique_arr);
            $this->display();
        }
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

    // 月报表
    public function month()
    {
        $restaurant_id = session("restaurant_id");
        if (IS_POST) {
            $year = date('Y');
            $month = $_POST['month'];
            $monthDay = get_day($year,$month);
            /**********功能************/
            $order_model = order();
            $returnData = $wx = $ali = $cash = $mem = $returnInfo = $totleInfo = [];
            $m_condition['restaurant_id'] = $restaurant_id;
            $m_condition['order_status'] = array("neq",0);
            $m_condition['order_type'] = array("in",'1,2,3');
            $m_condition['from_unixtime(add_time, "%m")'] = array("eq",intval($month));
            $m_condition['from_unixtime(add_time, "%Y")'] = array("eq",intval($year));

            $every_month_data = [];
            $every_month_inner_data = [];
            $all_year_total = 0;
            foreach($monthDay as $dk => $dv){
                $m_condition['from_unixtime(add_time, "%d")'] = array("eq",$dv);
                $m_condition['pay_type'] = array("in","0,1,2,4,5");
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

                if($sales){
                    $totleInfo[] = floatval($sales[0]['total_amount']);
                    $all_year_total += floatval($sales[0]['total_amount']);
                }else{
                    $totleInfo[] = 0;
                }
                $m_condition['pay_type'] = 0;
                /*$cashQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $cashinfo = $order_model->query("select SUM(total_amount) AS total_amount from (".$cashQuery.") a");*/
                $cashinfo = $this->funForMonth($restaurant_id,$month,$year,$dv,0);
                if($cashinfo){
//                    $cash[] = floatval($cashinfo[0]['total_amount']);
                    $every_month_data['cash'] = floatval($cashinfo[0]['total_amount']);
                }else{
//                    $cash[] = 0;
                    $every_month_data['cash'] = 0;
                }
                $m_condition['pay_type'] = 1;
                /*$alipayQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $alipay = $order_model->query("select SUM(total_amount) AS total_amount from (".$alipayQuery.") a");*/
                $alipay = $this->funForMonth($restaurant_id,$month,$year,$dv,1);
                if($alipay){
//                    $ali[] = floatval($alipay[0]['total_amount']);
                    $every_month_data['alipay'] = floatval($alipay[0]['total_amount']);
                }else{
//                    $ali[] = 0;
                    $every_month_data['alipay'] = 0;
                }
                $m_condition['pay_type'] = 2;
                /*$wechatQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $wechat = $order_model->query("select SUM(total_amount) AS total_amount from (".$wechatQuery.") a");*/
                $wechat = $this->funForMonth($restaurant_id,$month,$year,$dv,2);
                if($wechat){
//                    $wx[] = floatval($wechat[0]['total_amount']);
                    $every_month_data['wechat'] = floatval($wechat[0]['total_amount']);
                }else{
//                    $wx[] = 0;
                    $every_month_data['wechat'] = 0;
                }

                // 新增一个余额
                $m_condition['pay_type'] = 4;
                /*$remainderQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $remainder = $order_model->query("select SUM(total_amount) AS total_amount from (".$remainderQuery.") a");*/
                $remainder = $this->funForMonth($restaurant_id,$month,$year,$dv,4);
                if($remainder){
//                    $mem[] = floatval($remainder[0]['total_amount']);
                    $every_month_data['remainder'] = floatval($remainder[0]['total_amount']);
                }else{
//                    $mem[] = 0;
                    $every_month_data['remainder'] = 0;
                }

                // 新增一个余额
                $m_condition['pay_type'] = 5;
                /*$fourthQuery = $order_model->field('order_sn,total_amount')->table('order')->group('order_sn')->where($m_condition)->select(FALSE);
                // 当select方法传入false参数的时候，表示不执行当前查询，而只是生成查询SQL。
                $fourth = $order_model->query("select SUM(total_amount) AS total_amount from (".$fourthQuery.") a");*/
                $fourth = $this->funForMonth($restaurant_id,$month,$year,$dv,5);
                if($fourth){
//                    $minsheng[] = floatval($fourth[0]['total_amount']);
                    $every_month_data['fourth'] = floatval($fourth[0]['total_amount']);
                }else{
//                    $minsheng[] = 0;
                    $every_month_data['fourth'] = 0;
                }
                $every_month_inner_data[] = $every_month_data;
            }
            $this->assign("every_month_inner_data",$every_month_inner_data);
            $this->assign("totleInfo",$totleInfo);
            $this->assign("all_year_total",$all_year_total);

            $step_length = 150/max($totleInfo);
            $this->assign("step_length",round($step_length ,4));
            $this->display('month_ajax');
        }else{
            $month = date("m");
            $this->assign("month",$month);
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

    // 美团订单统计
    public function meituan(){
        if(IS_POST){
            // 接收时间条件
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];
            // 转换成时间戳
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);

            $restaurant_id = session('restaurant_id');
            $Model = D('jubaopen_order');
            // 总的订单数、商家总收入、总的商品金额、总的抽佣金额、总的配送金额，总的活动金额
            /*$query_total = 'SELECT COUNT(orderIdView) AS all_num, SUM(commisionAmount) AS sum_chouyong,SUM(foodAmount) AS sum_goodsAmount,SUM(settleAmount) AS sum_restaurant_income,
SUM(shippingAmount) AS sum_shippingAmount,SUM(totalActivityAmount) AS sum_totalActivityAmount FROM
(SELECT a.orderIdView,a.ctime,a.detail,b.activityDetails,b.commisionAmount,b.foodAmount,b.payType,b.settleAmount,b.shippingAmount,b.totalActivityAmount
FROM jubaopen_order AS a LEFT JOIN jubaopen_result AS b ON a.orderIdView = b.orderId WHERE b.`status`=8 AND a.`ePoiId` = '.$restaurant_id.' AND a.`ctime` BETWEEN '.$startTimeStr.' AND '.$endTimeStr. ' GROUP BY a.orderIdView ORDER BY a.id) AS c';*/

            $query_total = 'SELECT COUNT(orderIdView) AS all_num, SUM(commisionAmount) AS sum_chouyong,SUM(foodAmount) AS sum_goodsAmount,SUM(settleAmount) AS sum_restaurant_income,
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
            $this->display('meituan_ajax');
        }else{
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,00,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y/m/d",$beginThisMonth);
            $endDate = date("Y/m/d",$endThisMonth);
            $startTime = "00:00";
            $endTime = "23:59";

            $this->assign("startDate",$startDate);
            $this->assign("endDate",$endDate);
            $this->assign("startTime",$startTime);
            $this->assign("endTime",$endTime);
            $this->display();
        }
    }

    // 饿了么订单统计
    public function eleme(){
        if(IS_POST){
            $startDate=$_POST['startDate'];
            $endDate=$_POST['endtDate'];
            $startTime=$_POST['startTime'];
            $endTime=$_POST['endTime'];

            $this->assign("startDate",$startDate);
            $this->assign("endDate",$endDate);
            $this->assign("startTime",$startTime);
            $this->assign("endTime",$endTime);

            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $restaurant_id = session('restaurant_id');
            $Model = D('eleme_order');
            // 总营业额、商户实收、订单数、服务费、商家承担的活动金额
            $query_total = 'SELECT SUM(totalPrice) AS total_price_sum,SUM(income) AS income_sum,COUNT(primary_id) AS order_count,SUM(serviceFee) AS serviceFee_sum,
            SUM(shopPart) AS shopPart_sum,SUM(deliverFee) AS deliverFee_sum FROM (SELECT a.totalPrice,a.income,a.primary_id,a.serviceFee,a.shopPart,a.deliverFee from eleme_order a
            WHERE `final_type`=18 AND `restaurant_id` = '.$restaurant_id.' AND `activeAt` BETWEEN '.$startTimeStr.' AND '.$endTimeStr.
            ' GROUP BY orderId) as b ';
            $total = $Model->query($query_total);

            $income_sum = $total[0]['income_sum'] == null ? 0 : $total[0]['income_sum'];
            $serviceFee_sum = $total[0]['serviceFee_sum'] == null ? 0 : $total[0]['serviceFee_sum'];
            $shopPart_sum = $total[0]['shopPart_sum'] == null ? 0 : $total[0]['shopPart_sum'];
            $total_price_sum = $total[0]['total_price_sum'] == null ? 0 : $total[0]['total_price_sum'];
            $deliverFee_sum = $total[0]['deliverFee_sum'] == null ? 0 : $total[0]['deliverFee_sum'];
            $this->assign("deliverFee_sum",$deliverFee_sum);  // 总的配送费
            $this->assign("total_price_sum",$total_price_sum);  // 总营业额
            $this->assign("income_sum",$income_sum);    // 商户实收
            $this->assign("serviceFee_sum",$serviceFee_sum);     // 服务费
            $this->assign("shopPart_sum",$shopPart_sum);    // 商家承担的活动金额
            $this->assign("order_count",$total[0]['order_count']);  // 订单数

            $this->display('eleme_ajax');
        }else{
            $beginThisMonth=mktime(0,0,0,date('m'),date('d'),date('Y'));		//开始日期（当前年当前月的日期）
            $endThisMonth=mktime(23,59,00,date('m'),date('t'),date('Y'));		//结束日期（当前年当前月的日期）
            $startDate = date("Y/m/d",$beginThisMonth);
            $endDate = date("Y/m/d",$endThisMonth);
            $startTime = "00:00";
            $endTime = "23:59";

            $this->assign("startDate",$startDate);
            $this->assign("endDate",$endDate);
            $this->assign("startTime",$startTime);
            $this->assign("endTime",$endTime);
            $this->display();
        }
    }
}