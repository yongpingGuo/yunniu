<?php
namespace Agent\Controller;
use Think\Controller;

class SaleController extends Controller {

    public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }

    //首页默认选择所有查询条件查询
    public function index(){
        /**
         * 默认开始时间为当前月的开始时间
         * 默认结束时间为当前月的结束时间
         * 搜索范围：营业金额 所有店铺
         * 支付方式：所有
         * 就餐方式：所有
         */
        $beginThisMonth=mktime(0,0,0,date('m'),1,date('Y'));
        $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));

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
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //查询代理下的所有店铺
        $business_id = session("business_id");
        $rst_condition['business_id'] = $business_id;
		$rst_condition['status'] = 1;
        $restaurant_model = D('restaurant');
        $restaurants = $restaurant_model->where($rst_condition)->field("restaurant_id,restaurant_name")->select();

        //循环查询每家店铺在条件时间内的营业额
        $restaurant_sales = array();
        $total_amount = 0;
        foreach($restaurants as $rk => $rv){
            $condition['restaurant_id'] = $restaurant_id = $rv['restaurant_id'];
            // 分表统计
            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `restaurant_id` = $restaurant_id
                            LIMIT 1";
            $restaurant_sale = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');

            $restaurant_sale ? $restaurant_sales[] = $restaurant_sale:false;
            $total_amount += $restaurant_sale;
        }

        $this->assign('restaurant',$restaurants);
        $this->assign("total_amount",$total_amount);
        $this->display();
    }


    //分页获取订单列表
    public function orderInfo(){
    //---------------------------------------支付时间，支付类型，就餐类型条件---------------------------------------
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if(!empty($pay_type)){
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = implode(',',$pay_type);
            $_SESSION['sqlPayType'] = $sqlPayType;
        }

        $pay_type_str = array(
            0=>"现金",1=>"支付宝",2=>"微信",4=>"会员支付",5=>"银行代收",6=>"钉钉会员"
        );
        $pay_str = "";
        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp]."、";
        }
        $pay_str = mb_substr($pay_str,0,-1);
        $this->assign("pay_str",$pay_str);

        //就餐方式
        $order_type = I("post.order_type");
        if(!empty($order_type)){
            $condition['order_type'] = array("in",$order_type);
            $sqlOrderType = implode(',',$order_type);
        }

        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }
        $order_str = mb_substr($order_str,0,-1);
        $this->assign("order_str",$order_str);
	//----------------------------------------------条件内的查询操作------------------------------------------------
        $store = I("post.store");
        $restaurant_model = D('restaurant');
        $order_model = order();
        $sales_datas = array();
        $total_amount = 0;
        if($store == 0) {//是否查询所有店铺0:查询代理下的所有店铺1:查询该代理下指店铺         
            $business_id = session("business_id");
            $rst_condition['business_id'] = $business_id;
			$rst_condition['status'] = 1;
			$count = $restaurant_model->where($rst_condition)->count();
			$PageNum = 50;
			$page = I('get.page')?I('get.page'):1;		
			$Page = new \Think\PageAjax($count,$PageNum);
			$show = $Page->show('');
            $restaurant_ids = $restaurant_model->where($rst_condition)->field("restaurant_id")->page($page,$PageNum)->select();	
            foreach ($restaurant_ids as $rk => $rv){
                $condition['restaurant_id'] = $rv['restaurant_id'];
                $rst_condition['restaurant_id'] = $restaurant_id = $rv['restaurant_id'];
                $restaurant_name = $restaurant_model->where($rst_condition)->field("restaurant_name")->find()['restaurant_name'];
//                $sales_data = $order_model->where($condition)->sum("total_amount");

                // 分表统计
                $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `pay_type` IN ($sqlPayType)
                            AND `order_type` IN ($sqlOrderType)
                            AND `restaurant_id` = $restaurant_id
                            LIMIT 1";
                $sales_data = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');
//                p(M()->getLastSql());

                $total_amount += $sales_data;
                $sales_data ? $sales_datas[$rk]["sales_data"] = $sales_data : $sales_datas[$rk]["sales_data"] = 0;
                $sales_datas[$rk]['pay_str'] = $pay_str;
                $sales_datas[$rk]['order_str'] = $order_str;
                $sales_datas[$rk]['restaurant_name'] = $restaurant_name;
            }
            $this->assign("restaurant_name", "所有");
        }elseif($store == 1){
            $restaurant_id = I('post.restaurant');
            $condition['restaurant_id'] = $restaurant_id;
            $rst_condition['restaurant_id'] = $restaurant_id;
            $restaurant_name = $restaurant_model->where($rst_condition)->field("restaurant_name")->find()['restaurant_name'];
//            $sales_data = $order_model->where($condition)->sum("total_amount");

            // 分表统计
            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `pay_type` IN ($sqlPayType)
                            AND `order_type` IN ($sqlOrderType)
                            AND `restaurant_id` = $restaurant_id
                            LIMIT 1";
            $sales_data = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');

            $total_amount += $sales_data;
            $sales_data ? $sales_datas[0]["sales_data"] = $sales_data : $sales_datas[0]["sales_data"] = 0;
            $sales_datas[0]['pay_str'] = $pay_str;
            $sales_datas[0]['order_str'] = $order_str;
            $sales_datas[0]['restaurant_name'] = $restaurant_name;
            $this->assign("restaurant_name", $restaurant_name);
        }
        $this->assign("total_amount",number_format($total_amount,2));
		//分页查询订单数据
        
        $this->assign("sales_datas", $sales_datas);
		$this->assign('page',$show);// 赋值分页输出*/
        $this->display("ajaxOrderInfo");
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
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if(!empty($pay_type)){
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = implode(',',$pay_type);
        }

        $pay_type_str = array(
            "现金","支付宝","微信","","会员支付","银行代收","钉钉会员"
        );
        $pay_str = "";

        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp]."、";
        }
        $pay_str = mb_substr($pay_str,0,-1);
        $this->assign("pay_str",$pay_str);

        //就餐方式
        $order_type = I("post.order_type");

        if(!empty($order_type)){
            $condition['order_type'] = array("in",$order_type);
            $sqlOrderType = implode(',',$order_type);
        }

        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }
        $order_str = mb_substr($order_str,0,-1);
        $this->assign("order_str",$order_str);

        $order_model = order();


//        $order_list = $order_model->where($condition)->field("order_id")->select();

        // 分表统计
        $sql_orignal="SELECT
                            `order_id`
                        FROM
                            `tabName1`
                        WHERE
                            `pay_time` BETWEEN $startTimeStr
                        AND $endTimeStr
                        AND `pay_type` IN ($sqlPayType)
                        AND `order_type` IN ($sqlOrderType)";
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
//        p($order_list);

        $orders2 = array();
        foreach($order_list as $order_key => $order_val){
            $orders2[] = $order_val["order_id"];
        }

        //是否查询所有店铺
        $store = I("post.store");
        $restaurant_model = D('restaurant');
        $order_model = order();

       	//dump($condition);
        if($store == 0){
            //查询代理下的所有店铺
            $rst_condition['business_id'] = session("business_id");
			$rst_condition['status'] = 1;
            $restaurant_ids = $restaurant_model->where($rst_condition)->field("restaurant_id")->select();
			$all_restaurantIdArr = array();				//该代理下，所有店铺的ID集(一维数组)
			$all_orderIdArr = array();					//该代理下，所有店铺下，所有订单集(二维数组)
			foreach($restaurant_ids as $key=>$value){
				$all_restaurantIdArr[] = $value['restaurant_id'];
			}
			//dump($all_restaurantIdArr);
			$orders2 = array();						 
            foreach($all_restaurantIdArr as $rk => $rv){
                $condition['restaurant_id'] = $rv;		
//                $orders2[] = $order_model->where($condition)->field("order_id")->select();

                $sql_orignal="SELECT
                                        `order_id`
                                    FROM
                                        `tabName1`
                                    WHERE
                                        `pay_time` BETWEEN $startTimeStr
                                    AND $endTimeStr
                                    AND `pay_type` IN ($sqlPayType)
                                    AND `order_type` IN ($sqlOrderType)
                                    AND `restaurant_id` = $rv";
                $orders2[] = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
            }
			//dump($orders2);
			$orders2s = array();				//该代理下每间店铺的订单集
			foreach($orders2 as $key1=>$value1){
				foreach($value1 as $key2=>$value2){
					$orders2s[] = $value2['order_id'];
				}
			}

			//dump($orders2s);
			$all_orderIdArr[] = $orders2s;
            $this->assign("restaurant_name", "所有");
        }elseif($store == 1){	
            $condition['restaurant_id'] = $dianpu_id = I('post.restaurant');
			$orders2 = array();
//            $orders2[] = $order_model->where($condition)->field("order_id")->select();//查询所选择店铺，符合条件的所有订单

            $sql_orignal="SELECT
                                    `order_id`
                                FROM
                                    `tabName1`
                                WHERE
                                    `pay_time` BETWEEN $startTimeStr
                                AND $endTimeStr
                                AND `pay_type` IN ($sqlPayType)
                                AND `order_type` IN ($sqlOrderType)
                                AND `restaurant_id` = $dianpu_id";
            $orders2[] = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

			if(empty($orders2)){
				exit;
			}
		
			$orders2s = array();				//该代理下每间店铺的订单集
			foreach($orders2 as $key1=>$value1){
				foreach($value1 as $key2=>$value2){
					$orders2s[] = $value2['order_id'];
				}
			}
			
			$all_orderIdArr[] = $orders2s;
			$rest_id = I('post.restaurant');
			$restaurant_name = $restaurant_model->where("restaurant_id=$rest_id")->field('restaurant_name')->find()['restaurant_name'];
			$this->assign("restaurant_name",$restaurant_name);
        }

        if(empty($all_orderIdArr[0])){
            exit;
        }
		

        /**
         * 计算搜索菜品的销售情况(范围是该代理下的所有代铺)
         */
        //dump($all_orderIdArr[0]);
        foreach($all_orderIdArr as $rok => $rov){
        	//dump($rov);
            $food_name = I("post.food_name");
            $f_condition['food_name'] = array("like","%".$food_name."%");
            $f_condition['order_id'] = array("in",$all_orderIdArr[0]);
            $str = implode(',',$all_orderIdArr[0]);
            $food_model = order_F();
			//order_list2 代理下，每个店铺order表的数组(二维数组)
			$page = I('get.page')?I('get.page'):1;
//			$count = $food_model->where($f_condition)->count();

            $sql_orignal="SELECT
                                COUNT(*) AS tp_count
                            FROM
                                `tabName`
                            WHERE
                                `food_name` LIKE '%$food_name%'
                            AND `order_id` IN (
                                  $str
                            )
                            LIMIT 1";
            $count = countNum($startTimeStr,$endTimeStr,$type=2,$sql_orignal,$field='tp_count');


			$pageNum = 50;
			$Page = new \Think\PageAjax($count,$pageNum);
			$show = $Page->show('');
//            $order_list2 = $food_model->where($f_condition)->field("food_name,food_price2,food_num,order_id")->page($page,$pageNum)->select();
//            p(M()->getLastSql());
            $sqlList = "SELECT
                            t1.`food_name`,
                            t1.`food_price2`,
                            t1.`food_num`,
                            t1.`order_id`,
                            t2.add_time
                        FROM
                            `tabName2` t1
                            LEFT JOIN tabName1 t2
                            ON t1.order_id = t2.order_id
                        WHERE
                            t1.`food_name` LIKE '%$food_name%'
                        AND t1.`order_id` IN (
                            $str
                        )
                        ";
            // 分页数据结果集
            $order_list2 = unionSelect2($startTimeStr,$endTimeStr,$sqlList,2,($page-1)*$pageNum,$pageNum);
			
			foreach($order_list2 as $key3=>$value3){
				$condition2['order_id'] = $value3['order_id'];
				$orderinfo = M("order_".date("Ym",$value3['add_time']))->where($condition2)->field('order_sn,order_type,pay_type,pay_time')->find();
				$order_list2[$key3]['order_sn'] = $orderinfo['order_sn'];
				$order_list2[$key3]['order_type'] = $orderinfo['order_type'];
				$order_list2[$key3]['pay_type'] = $orderinfo['pay_type'];
				$order_list2[$key3]['pay_time'] = date('Y-m-d',$orderinfo['pay_time']);
				$order_list2[$key3]['Onefood_price'] = number_format($value3['food_price2']*$value3['food_num'],2);//一条菜品记录的价格
			}
            $total_amount = 0;
            $total_number = 0;
            foreach($order_list2 as $kt => $vt){  //计算销售总额
                $total += $vt['Onefood_price'];
            }
			$this->assign("total_amount",$total);
        }
		$this->assign("page",$show);
		$this->assign("order_list2",$order_list2);
        $this->display('ajaxFoodSale');
    }

    /**
     * 获取某个月份的销售每一天的销售情况和当月的销售总额
     * @param $month
     * @return array()
     */
    public function monthlySales($year,$month,$restaurant_IdArr1){
        $order_model = order();
        $day_list = dayForMonth($year,$month);
        $sales_for_month = array();
        $month_sales = 0;
        $m_condition['restaurant_id'] = array('in',$restaurant_IdArr1);
        $ids = implode(',',$restaurant_IdArr1);
        foreach($day_list as $dk => $dv){
            $m_condition['pay_time'] = array("between",array($dv['day_start'],$dv['day_end']));
            $startTime = $dv['day_start'];
            $endTime = $dv['day_end'];
//            $sales = $order_model->where($m_condition)->sum("total_amount");

            // 分表统计
            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `restaurant_id` IN (
                                    $ids
                                )
                            AND `pay_time` BETWEEN $startTime
                            AND $endTime
                            LIMIT 1";
            $sales = countNum($startTime,$endTime,$type=1,$sql_orignal,$field='tp_sum');

            $month_sales += $sales;						//条件月内该代理下所有店铺的月营业额
            if($sales){
                $sales_for_month[] = $sales;
            }else{
                $sales_for_month[] = 0;
            }
        }
//        dump($sales_for_month);
        $data["sales_for_month"] = $sales_for_month;
        $data["month_sales"] = $month_sales;
        return $data;
    }

    /**
     * 获取某一年的销售情况
     * @param $year
     * @return array();
     */
    public function annualSales($year,$restaurant_IdArr){
        $order_model = order();
        $month_list = monthForYear($year);		//输入年份，返回该年份中每个月的开始时间与结束时间
        $sales_for_year = array();
        $m_condition['restaurant_id'] = array("in",$restaurant_IdArr);
        $ids = implode(',',$restaurant_IdArr);
        foreach($month_list as $k => $v){
            $m_condition['pay_time'] = array("between",array($v['month_start'],$v['month_end']));
            $startTime = $v['month_start'];
            $endTime = $v['month_end'];
			//dump($m_condition);
//            $sales = $order_model->where($m_condition)->sum("total_amount");

            // 分表统计
            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `restaurant_id` IN (
                                    $ids
                                )
                            AND `pay_time` BETWEEN $startTime
                            AND $endTime
                            LIMIT 1";
            $sales = countNum($startTime,$endTime,$type=1,$sql_orignal,$field='tp_sum');


            if($sales){
                $sales_for_year[] = $sales/10000;
            }else{
                $sales_for_year[] = 0;
            }
        }
        $data["sales_for_year"] = $sales_for_year;
        return $data;
    }

    /**
     * 数据统计data页数据渲染
     */
    public function data(){
        $where['business_id'] = session('business_id');
		$restaurant = D('restaurant');
		$restaurant_IdArr = $restaurant->where($where)->field('restaurant_id')->select();
		$restaurant_IdArr1 = array();
		foreach($restaurant_IdArr as $value){
			$restaurant_IdArr1[] = $value['restaurant_id'];
		}
//        $order_model = order();
//        $condition['pay_time'] = array("neq",0);
//		$condition['restaurant_id'] = array("in",$restaurant_IdArr1);
//        $years = $order_model->where($condition)->field("pay_time")->select();
////        p(M()->getLastSql());
//        $year_list = array();
//		if(empty($years)){
//			$when_year = date("Y");
//			$year_list[] = $when_year;
//		}
//
//        foreach($years as $key => $val){
//            /*if(in_array($year_list,$val['pay_time']) || empty($year_list)){
//                $year_list[] = date("Y",$val['pay_time']);
//            }*/
//            $yearss = date("Y",$val['pay_time']);
//         	$year_list[] = $yearss;
//        }
//		$unique_arr = array_unique ( $year_list );
        $unique_arr = array(
            date('Y')-1,
            date('Y')
        );

        $this->assign("year_list",$unique_arr);
		
        //查询该店今年的销售情况，分月份查询
        $year = date("Y");
        $month = date("m");

        $this->assign("year",$year);
        $this->assign("month",$month);
        //$this->assign("restaurant_id",$restaurant_id);
        $yearData = $this->annualSales($year,$restaurant_IdArr1);

        $sales_for_year = $yearData["sales_for_year"];

        //获取当月的销售每一天的销售情况和当月的销售总额
        $monthData = $this->monthlySales($year,$month,$restaurant_IdArr1);
        $month_sales = $monthData['month_sales'];			//条件年，条件月下该代理的所有店铺的营业额总和
        $sales_for_month = $monthData['sales_for_month'];	//条件年，条件月下该代理的所有店铺每一天的营业额情况
//        dump($sales_for_month);
        //获取上个月的销售总量计算销售波动百分比
        if($month-1>0){
            $monthData2 = $this->monthlySales($year,$month-1,$restaurant_IdArr1);
        }else{
            $monthData2 = $this->monthlySales($year-1,12,$restaurant_IdArr1);
        }

        $month_sales2 = $monthData2['month_sales'];			 //条件年，上个月该代理的所有店铺的营业额总和
        if($month_sales2 == 0 && $month_sales != 0){
            $salesPercent = 1;
        }elseif($month_sales2 == 0 && $month_sales == 0){
            $salesPercent = 0;
        }else{
            $salesPercent = ($month_sales-$month_sales2)/$month_sales2;
        }
        if($salesPercent < 0){
            $salesPercent = (0-$salesPercent)*100;
            $status = "下降";
        }else{
            $salesPercent = $salesPercent*100;
            $status = "上升";
        }
        $salesInfo = $month."月共销售：".number_format($month_sales,2)."元，同比上月".$status.number_format($salesPercent,2)."%";
        $this->assign("salesInfo",$salesInfo);
        $this->assign("month_sales",$month_sales);
        $this->assign("sales_for_year",json_encode($sales_for_year));
        $this->assign("sales_for_month",json_encode($sales_for_month));
		//dump($sales_for_month);
        $this->display();
    }

    public function ajax_sales_for_year(){
        $year = I("post.year");
        $where['business_id'] = session('business_id');
		$restaurant = D('restaurant');
		$restaurant_IdArr = $restaurant->where($where)->field('restaurant_id')->select();
		$restaurant_IdArr1 = array();
		foreach($restaurant_IdArr as $value){
			$restaurant_IdArr1[] = $value['restaurant_id'];
		}
        $data = $this->annualSales($year,$restaurant_IdArr1);
        exit(json_encode($data));
    }

    public function ajax_sales_for_month(){
        $year = I("post.year");
        $month = I("post.month");
        $where['business_id'] = session('business_id');
		$restaurant = D('restaurant');
		$restaurant_IdArr = $restaurant->where($where)->field('restaurant_id')->select();
		$restaurant_IdArr1 = array();
		foreach($restaurant_IdArr as $value){
			$restaurant_IdArr1[] = $value['restaurant_id'];
		}
        $data = $this->monthlySales($year,$month,$restaurant_IdArr1);
		
        //获取上个月的销售总量计算销售波动百分比
        if($month-1>0){
            $monthData2 = $this->monthlySales($year,$month-1,$restaurant_IdArr1);
        }else{
            $monthData2 = $this->monthlySales($year-1,12,$restaurant_IdArr1);
        }

        $month_sales = $data['month_sales'];

        $month_sales2 = $monthData2['month_sales'];
        if($month_sales2 == 0 && $month_sales != 0){
            $salesPercent = 1;
        }elseif($month_sales2 == 0 && $month_sales == 0){
            $salesPercent = 0;
        }else{
            $salesPercent = ($month_sales-$month_sales2)/$month_sales2;
        }

        if($salesPercent < 0){
            $salesPercent = (0-$salesPercent)*100;
            $status = "下降";
        }else{
            $salesPercent = $salesPercent*100;
            $status = "上升";
        }
        $salesInfo = $month."月共销售：".number_format($month_sales,2)."元，同比上月".$status.number_format($salesPercent,2)."%";
        $data['salesInfo'] = $salesInfo;
        exit(json_encode($data));
    }

	public function exportExcel(){
		$startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if(!empty($pay_type)){
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = implode(',',$pay_type);
        }

        $pay_type_str = array(
            "现金","支付宝","微信","","会员支付","银行代收","钉钉会员"
        );
        $pay_str = "";
        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp]."、";
        }

        $this->assign("pay_str",$pay_str);

        //就餐方式
        $order_type = I("post.order_type");
        if(!empty($order_type)){
            $condition['order_type'] = array("in",$order_type);
            $sqlOrderType = implode(',',$order_type);
        }

        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }

        $this->assign("order_str",$order_str);
	//----------------------------------------------条件内的查询操作------------------------------------------------
        $store = I("post.store");
        $restaurant_model = D('restaurant');
        $order_model = order();
        $sales_datas = array();
        $total_amount = 0;
        if($store == 0) {//是否查询所有店铺0:查询代理下的所有店铺1:查询该代理下指店铺         
            $business_id = session("business_id");
            $rst_condition['business_id'] = $business_id;
			$rst_condition['status'] = 1;
			$count = $restaurant_model->where($rst_condition)->count();
			$PageNum = 50;
			$page = I('get.page')?I('get.page'):1;		
			$Page = new \Think\PageAjax($count,$PageNum);
			$show = $Page->show('');
            $restaurant_ids = $restaurant_model->where($rst_condition)->field("restaurant_id")->page($page,$PageNum)->select();	
            foreach ($restaurant_ids as $rk => $rv){
                $condition['restaurant_id'] = $rv['restaurant_id'];
                $rst_condition['restaurant_id'] = $restaurantId = $rv['restaurant_id'];
                $restaurant_name = $restaurant_model->where($rst_condition)->field("restaurant_name")->find()['restaurant_name'];
//                $sales_data = $order_model->where($condition)->sum("total_amount");

                $sql_orignal="SELECT
                                    SUM(total_amount) AS tp_sum
                                FROM
                                    `tabName`
                                WHERE
                                    `pay_time` BETWEEN $startTimeStr
                                AND $endTimeStr
                                AND `pay_type` IN ($sqlPayType)
                                AND `order_type` IN ($sqlOrderType)
                                AND `restaurant_id` = $restaurantId
                                LIMIT 1";
                $sales_data = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');
//                p(M()->getLastSql());

                $total_amount += $sales_data;
                $sales_data ? $sales_datas[$rk]["sales_data"] = $sales_data : $sales_datas[$rk]["sales_data"] = 0;
                $sales_datas[$rk]['pay_str'] = $pay_str;
                $sales_datas[$rk]['order_str'] = $order_str;
                $sales_datas[$rk]['restaurant_name'] = $restaurant_name;
            }
            $this->assign("restaurant_name", "所有");
        }elseif($store == 1){
            $restaurant_id = I('post.restaurant');
            $condition['restaurant_id'] = $restaurant_id;
            $rst_condition['restaurant_id'] = $restaurant_id;
            $restaurant_name = $restaurant_model->where($rst_condition)->field("restaurant_name")->find()['restaurant_name'];
//            $sales_data = $order_model->where($condition)->sum("total_amount");

            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `pay_type` IN ($sqlPayType)
                            AND `order_type` IN ($sqlOrderType)
                            AND `restaurant_id` = $restaurant_id
                            LIMIT 1";
            $sales_data = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');


            $total_amount += $sales_data;
            $sales_data ? $sales_datas[0]["sales_data"] = $sales_data : $sales_datas[0]["sales_data"] = 0;
            $sales_datas[0]['pay_str'] = $pay_str;
            $sales_datas[0]['order_str'] = $order_str;
            $sales_datas[0]['restaurant_name'] = $restaurant_name;
            $this->assign("restaurant_name", $restaurant_name);
        }
       // $this->assign("total_amount",number_format($total_amount,2));

		foreach($sales_datas as $key=>$value){
			$sales_datas1[$key]['restaurant_name'] = $value['restaurant_name'];
			$sales_datas1[$key]['order_str'] = $value['order_str'];
			$sales_datas1[$key]['pay_str'] = $value['pay_str'];
			$sales_datas1[$key]['sales_data'] = $value['sales_data'];
		}
		//$xlsName  = "营业额报表、导出时间(".date("Y-m-d",time()).")";
        $xlsCell  = array(
        array('restaurant_name','店铺名称'),
        array('order_str','就餐方式'),
        array('pay_str','支付方式'),
        array('sales_data','销售总额'),
        );
        exportExcel($xlsName,$xlsCell,$sales_datas1);
	}

	public function exportExcel1(){
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
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if(!empty($pay_type)){
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = implode(',',$pay_type);
        }

        $pay_type_str = array(
            "现金","支付宝","微信","","会员支付","银行代收","钉钉会员"
        );
        $pay_str = "";
        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp]."、";
        }

        $this->assign("pay_str",$pay_str);

        //就餐方式
        $order_type = I("post.order_type");

        if(!empty($order_type)){
            $condition['order_type'] = array("in",$order_type);
            $sqlOrderType = implode(',',$order_type);
        }

        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }
        $this->assign("order_str",$order_str);

        $order_model = order();

//        $order_list = $order_model->where($condition)->field("order_id")->select();

        // 分表统计
        $sql_orignal="SELECT
                            `order_id`
                        FROM
                            `tabName1`
                        WHERE
                            `pay_time` BETWEEN $startTimeStr
                        AND $endTimeStr
                        AND `pay_type` IN ($sqlPayType)
                        AND `order_type` IN ($sqlOrderType)";
        $order_list = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);


        $orders2 = array();
        foreach($order_list as $order_key => $order_val){
            $orders2[] = $order_val["order_id"];
        }

        //是否查询所有店铺
        $store = I("post.store");
        $restaurant_model = D('restaurant');
        $order_model = order();

       	
        if($store == 0){
            //查询代理下的所有店铺
            $rst_condition['business_id'] = session("business_id");
			$rst_condition['status'] = 1;
            $restaurant_ids = $restaurant_model->where($rst_condition)->field("restaurant_id")->select();
			$all_restaurantIdArr = array();				//该代理下，所有店铺的ID集(一维数组)
			$all_orderIdArr = array();					//该代理下，所有店铺下，所有订单集(二维数组)
			foreach($restaurant_ids as $key=>$value){
				$all_restaurantIdArr[] = $value['restaurant_id'];
			}
			//dump($all_restaurantIdArr);
			$orders2 = array();
            foreach($all_restaurantIdArr as $rk => $rv){
                $condition['restaurant_id'] = $rv;
//                $orders2[] = $order_model->where($condition)->field("order_id")->select();

                $sql_orignal="SELECT
                                        `order_id`
                                    FROM
                                        `tabName1`
                                    WHERE
                                        `pay_time` BETWEEN $startTimeStr
                                    AND $endTimeStr
                                    AND `pay_type` IN ($sqlPayType)
                                    AND `order_type` IN ($sqlOrderType)
                                    AND `restaurant_id` = $rv";
                $orders2[] = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);
            }
			//dump($orders2);
			$orders2s = array();				//该代理下每间店铺的订单集
			foreach($orders2 as $key1=>$value1){
				foreach($value1 as $key2=>$value2){
					$orders2s[] = $value2['order_id'];
				}
			}
			//dump($orders2s);
			$all_orderIdArr[] = $orders2s;
			//dump($all_orderIdArr);
            $this->assign("restaurant_name", "所有");
        }elseif($store == 1){
            $condition['restaurant_id'] = $restaurant_id = I('post.restaurant');
//            $orders2[] = $order_model->where($condition)->field("order_id")->select();

            $sql_orignal="SELECT
                                `order_id`
                            FROM
                                `tabName1`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `pay_type` IN ($sqlPayType)
                            AND `order_type` IN ($sqlOrderType)
                            AND `restaurant_id` = $restaurant_id";
            $orders2[] = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

			$orders2s = array();				//该代理下每间店铺的订单集
			foreach($orders2 as $key1=>$value1){
				foreach($value1 as $key2=>$value2){
					$orders2s[] = $value2['order_id'];
				}
			}
			//dump($orders2s);
			$all_orderIdArr[] = $orders2s;
			$rest_id = I('post.restaurant');
			$restaurant_name = $restaurant_model->where("restaurant_id=$rest_id")->field('restaurant_name')->find()['restaurant_name'];
			$this->assign("restaurant_name",$restaurant_name);
        }

        if(empty($all_orderIdArr)){
            exit;
        }


        /**
         * 计算搜索菜品的每家店铺的销售情况
         */
        $xorder_type = array(
			1=>"店吃",
			2=>"打包",
			3=>"微信占餐"
		);
		$xpay_type = array(
			0=>"现金",
			1=>"支付宝",
			2=>"微信",
			3=>"未支付",
			4=>"余额",
			5=>"银行代收",
		);
        foreach($all_orderIdArr as $rok => $rov){       	
            $food_name = I("post.food_name");
            $f_condition['food_name'] = array("like","%".$food_name."%");
            $f_condition['order_id'] = array("in",$rov);
            $food_model = order_F();
            $ids = implode(',',$rov);
			//order_list2 代理下，每个店铺order表的数组(二维数组)
//            $order_list2 = $food_model->where($f_condition)->field("food_name,food_price2,food_num,order_id")->select();

            $sql_orignal="SELECT
                                t1.`food_name`,
                                t1.`food_price2`,
                                t1.`food_num`,
                                t1.`order_id`,
                                t2.add_time
                            FROM
                                `tabName2` t1
                                  LEFT JOIN `tabName1` t2
                                  ON t1.order_id = t2.order_id
                            WHERE
                                t1.`food_name` LIKE '%$food_name%'
                            AND t1.`order_id` IN (
                                $ids
                            )";
            $order_list2 = unionSelect2($startTimeStr,$endTimeStr,$sql_orignal);

			foreach($order_list2 as $key3=>$value3){
				$condition2['order_id'] = $value3['order_id'];
				$orderinfo = M("order_".date('Ym',$value3['add_time']))->where($condition2)->field('order_sn,order_type,pay_type,pay_time')->find();
				$order_list2[$key3]['order_sn'] = $orderinfo['order_sn'];
				$order_list2[$key3]['order_type'] = $xorder_type[$orderinfo['order_type']];
				$order_list2[$key3]['pay_type'] = $xpay_type[$orderinfo['pay_type']];
				$order_list2[$key3]['pay_time'] = date('Y-m-d',$orderinfo['pay_time']);
				$order_list2[$key3]['Onefood_price'] = number_format($value3['food_price2']*$value3['food_num'],2);//一条菜品记录的价格
			}
		
			foreach($order_list2 as $key4=>$value4){
				$order_list3[$key4]['order_sn'] = $value4['order_sn'];
				$order_list3[$key4]['food_name'] = $value4['food_name'];
				$order_list3[$key4]['pay_time'] = $value4['pay_time'];
				$order_list3[$key4]['order_type'] = $value4['order_type'];
				$order_list3[$key4]['pay_type'] = $value4['pay_type'];
				$order_list3[$key4]['food_price2'] = $value4['food_price2'];
				$order_list3[$key4]['food_num'] = $value4['food_num'];
				$order_list3[$key4]['Onefood_price'] = $value4['Onefood_price'];
			}
			$xlsCell  = array(
	        array('order_sn','订单号'),
	        array('food_name','菜品名称'),
	        array('pay_time','支付时间'),
	        array('order_type','就餐方式'),
	        array('pay_type','支付方式'),
	        array('food_price2','菜品单价'),
	        array('food_num','菜品份数'),
	        array('Onefood_price','单记录价格'),
	        );
	        exportExcel($xlsName,$xlsCell,$order_list3);
        }
	}


    //----------------------------------------------店铺会员消费统计开始------------------------------------------------
    /**
     * 店铺会员消费统计
     */
    public function vipConsumeData(){
        /**
         * 默认开始时间为当前月的开始时间
         * 默认结束时间为当前月的结束时间
         * 搜索范围：营业金额 所有店铺
         * 支付方式：所有
         * 就餐方式：所有
         */
        $beginThisMonth=mktime(0,0,0,date('m'),1,date('Y'));
        $endThisMonth=mktime(23,59,59,date('m'),date('t'),date('Y'));   // 给定的月份所应有的天数：date('t')

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
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //查询代理下的所有店铺
        $business_id = session("business_id");
        $rst_condition['business_id'] = $business_id;
        $rst_condition['status'] = 1;
        $restaurant_model = D('restaurant');
        $restaurants = $restaurant_model->where($rst_condition)->field("restaurant_id,restaurant_name")->select();

        //循环查询每家店铺在条件时间内的营业额
        $order_model = order();
        $restaurant_sales = array();
        $total_amount = 0;
        foreach($restaurants as $rk => $rv){
            $condition['restaurant_id'] = $restaurant_id = $rv['restaurant_id'];
            $condition['pay_type'] = 4;     // 只查余额
//            $restaurant_sale = $order_model->where($condition)->sum("total_amount");

            // 分表统计
            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `restaurant_id` = $restaurant_id
                            AND `pay_type` = 4
                            LIMIT 1";
            $restaurant_sale = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');

            $restaurant_sale ? $restaurant_sales[] = $restaurant_sale:false;      // 有则把每家店铺的营业额放到一个数组
            $total_amount += $restaurant_sale;  // 每个店铺的营业额累加
        }

        $this->assign('restaurant',$restaurants);
        $this->assign("total_amount",$total_amount);
        $this->display('vipConsumeData');
    }

    //分页获取订单列表
    public function orderInfo_vip(){
        //---------------------------------------支付时间，支付类型，就餐类型条件---------------------------------------
        $startDate = I("post.startDate");
        $startTime = I("post.startTime");
        $endDate = I("post.endtDate");
        $endTime = I("post.endTime");

        $condition = array();
        //判断是否有时间，有则添加到查询寻条件
        if(!empty($startDate) && !empty($startTime) && !empty($endDate) && !empty($endTime)){
            $startTimeStr = strtotime($startDate." ".$startTime);
            $endTimeStr = strtotime($endDate." ".$endTime);
            $condition['pay_time'] = array("between",array($startTimeStr,$endTimeStr));
        }

        //支付类型
        $pay_type = I("post.pay_type");
        if(!empty($pay_type)){
            $condition['pay_type'] = array("in",$pay_type);
            $sqlPayType = implode(',',$pay_type);
        }

        /*$pay_type_str = array(
            "现金","支付宝","微信","余额"
        );*/
        $pay_type_str = array(
            "余额",
        );
        $pay_str = "";
        foreach($pay_type as $vp){
            $pay_str .= $pay_type_str[$vp-4]."、";       // 把余额前面三个都不要了，所以减4
        }

        $this->assign("pay_str",$pay_str);

        //就餐方式
       /* $order_type = I("post.order_type");
        if(!empty($order_type)){
            $condition['order_type'] = array("in",$order_type);
        }

        $order_type_str = array(
            "店内点餐","打包带走"
        );

        $order_str = "";
        foreach($order_type as $vod){
            $order_str .= $order_type_str[$vod-1]."、";
        }

        $this->assign("order_str",$order_str);*/
        //----------------------------------------------条件内的查询操作------------------------------------------------
        $store = I("post.store");
        $restaurant_model = D('restaurant');
        $order_model = order();
        $sales_datas = array();
        $total_amount = 0;
        if($store == 0) {//是否查询所有店铺0:查询代理下的所有店铺1:查询该代理下指店铺
            $business_id = session("business_id");
            $rst_condition['business_id'] = $business_id;
            $rst_condition['status'] = 1;
            $count = $restaurant_model->where($rst_condition)->count();
            $PageNum = 50;
            $page = I('get.page')?I('get.page'):1;
            $Page = new \Think\PageAjax($count,$PageNum);
            $show = $Page->show('');
            $restaurant_ids = $restaurant_model->where($rst_condition)->field("restaurant_id")->page($page,$PageNum)->select();
            foreach ($restaurant_ids as $rk => $rv){
                $condition['restaurant_id'] = $rv['restaurant_id'];

                $condition['pay_type'] = 4;     // 只查余额的

                $rst_condition['restaurant_id'] = $restaurant_id = $rv['restaurant_id'];
                $restaurant_name = $restaurant_model->where($rst_condition)->field("restaurant_name")->find()['restaurant_name'];

//                $sales_data = $order_model->where($condition)->sum("total_amount");
//                p(M()->getLastSql());


                // 分表统计
                $sql_orignal="SELECT
                                    SUM(total_amount) AS tp_sum
                                FROM
                                    `tabName`
                                WHERE
                                    `pay_time` BETWEEN $startTimeStr
                                AND $endTimeStr
                                AND `pay_type` = 4
                                AND `restaurant_id` = $restaurant_id
                                LIMIT 1";
                $sales_data = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');

                $total_amount += $sales_data;
                $sales_data ? $sales_datas[$rk]["sales_data"] = $sales_data : $sales_datas[$rk]["sales_data"] = 0;
                $sales_datas[$rk]['pay_str'] = $pay_str;
//                $sales_datas[$rk]['order_str'] = $order_str;      // 不考虑就餐方式
                $sales_datas[$rk]['restaurant_name'] = $restaurant_name;
            }
            $this->assign("restaurant_name", "所有");
        }elseif($store == 1){   // 查单个店铺
            $restaurant_id = I('post.restaurant');
            $condition['restaurant_id'] = $restaurant_id;

            $condition['pay_type'] = 4;     // 只查余额的

            $rst_condition['restaurant_id'] = $restaurant_id;
            $restaurant_name = $restaurant_model->where($rst_condition)->field("restaurant_name")->find()['restaurant_name'];
//            $sales_data = $order_model->where($condition)->sum("total_amount");

            // 分表统计
            $sql_orignal="SELECT
                                SUM(total_amount) AS tp_sum
                            FROM
                                `tabName`
                            WHERE
                                `pay_time` BETWEEN $startTimeStr
                            AND $endTimeStr
                            AND `pay_type` = 4
                            AND `restaurant_id` = $restaurant_id
                            LIMIT 1";
            $sales_data = countNum($startTimeStr,$endTimeStr,$type=1,$sql_orignal,$field='tp_sum');

            $total_amount += $sales_data;
            $sales_data ? $sales_datas[0]["sales_data"] = $sales_data : $sales_datas[0]["sales_data"] = 0;
            $sales_datas[0]['pay_str'] = $pay_str;
//            $sales_datas[0]['order_str'] = $order_str;
            $sales_datas[0]['restaurant_name'] = $restaurant_name;
            $this->assign("restaurant_name", $restaurant_name);
        }
        $this->assign("total_amount",number_format($total_amount,2));
        //分页查询订单数据

        $this->assign("sales_datas", $sales_datas);
        $this->assign('page',$show);// 赋值分页输出*/
        $this->display("ajaxOrderInfo_vip");
    }

    //----------------------------------------------店铺会员消费统计结束------------------------------------------------
	
}