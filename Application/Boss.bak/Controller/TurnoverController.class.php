<?php
namespace Boss\Controller;
use Think\Controller;

class TurnoverController extends CommonController{
	//菜品营业额统计页
	public function index(){
		//判断是否存在session，不存在返回登录页
		if(!session("adminIDWithBoss")){
			$this->redirect('Common/login');
		}
		
		//当天开始与结束时间戳
		$startTimeStamp = $this->getBeginToday();		
		$endTimeStamp = $this->getEndToday();	
		
		//店铺集					
		$adminType = session("adminTypeWithBoss");	
		$storeRange = $this->getStoreRange($adminType);			
			
		if (is_string($storeRange)) {
			$Model = M();
	        //订单信息
	        $order_list = $Model->query("SELECT SUM(total_amount) total_amount,a.pay_type 
			FROM (SELECT `total_amount`,pay_type,restaurant_id,order_status,add_time,cashier_id FROM `order` GROUP BY order_sn ) a WHERE
	 		`restaurant_id` = ".$storeRange." AND `order_status` <> 0 AND a.pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStamp." AND ".$endTimeStamp." GROUP BY a.pay_type");

	        foreach ($order_list as $k => $v) {
	            if ($v['pay_type'] == 0) {
	                $totalAmount_isCashToday = $v['total_amount'];//现金总额
	            }
	        }
		}else{
			$pay_type = 0;		//现金
			$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
			$totalAmount_isCashToday = $this->getTurnover_withCondition($conditionSet);
		}

		//--------------------当天现金、支付宝、微信、总、统计营业额(当日营业额饼状图)----------------------------
		
		
		$pay_type = 1;	    //支付宝
		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
		$totalAmount_isAlipayToday = $this->getTurnover_withCondition($conditionSet);
		
		$pay_type = 2;		//微信
		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
		$totalAmount_isWeChatToday = $this->getTurnover_withCondition($conditionSet);

        $pay_type = 4;		//新增一个余额
        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
        $totalAmount_isRemainderToday = $this->getTurnover_withCondition($conditionSet);

        $pay_type = 5;		//新增一个第四方支付
        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
        $totalAmount_isFourthToday = $this->getTurnover_withCondition($conditionSet);

		$totalAmout_whenToday = $totalAmount_isCashToday+$totalAmount_isAlipayToday+$totalAmount_isWeChatToday+$totalAmount_isRemainderToday+$totalAmount_isFourthToday;
		
		//当天的订单数
		$pay_type = array("in","0,1,2,4,5");
		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
		$orderNum_withToday = $this->getOrderNum_withToday($conditionSet);


        // 当天退菜数，先取出所有的退菜的记录，包括整单退、分菜品退
        $pay_type = array("in","0,1,2,4,5");
        $refuse = array("in",array(1,2));   // 包括整单退、分菜品退
        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange,$refuse);
        $RefuseNum_withToday = $this->getRefuseNum_withToday($conditionSet);
        $refuse_num = $RefuseNum_withToday['refuse_num'];
        $refuse_total = $RefuseNum_withToday['refuse_total'];
        //今日退菜数、退菜金额
        $this->assign("refuse_num",$refuse_num);
        $this->assign("refuse_total",$refuse_total);



		
		//-----------------------------------------------------------------------------------------------------
		
		//-----------------------------当月现金、支付宝、微信、总、统计营业额(月营业额饼状图)----------------------	
		
		//当月的开始与结束时间戳
		$startDate = $this->getWhenMonth_firstday();
		$endDate = $this->getWhenMonth_lastday();
		$startTime = "00:00:00";
		$endTime = "23:59:59";
		$timeStamp_whenMonth = $this->getWhenMonth_timeStamp($startDate, $endDate, $startTime, $endTime);
		$startTimeStamp = $timeStamp_whenMonth['startTimeStamp'];
		$endTimeStamp = $timeStamp_whenMonth['endTimeStamp'];
			

		if (is_string($storeRange)) {
			$Model = M();
	        //订单信息
	        $order_list = $Model->query("SELECT SUM(total_amount) total_amount,a.pay_type 
			FROM (SELECT `total_amount`,pay_type,restaurant_id,order_status,add_time,cashier_id FROM `order` GROUP BY order_sn ) a WHERE
	 		`restaurant_id` = ".$storeRange." AND `order_status` <> 0 AND a.pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStamp." AND ".$endTimeStamp." GROUP BY a.pay_type");

	        foreach ($order_list as $k => $v) {
	            if ($v['pay_type'] == 0) {
	                $totalAmount_isCashMonth = $v['total_amount'];//现金总额
	            }
	        }
		}else{
			$pay_type = 0;		//现金
			$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
			$totalAmount_isCashMonth = $this->getTurnover_withCondition($conditionSet);
		}

		// $pay_type = 0;		//现金
		// $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
		// $totalAmount_isCashMonth = $this->getTurnover_withCondition($conditionSet);
		
		$pay_type = 1;		//支付宝
		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
		$totalAmount_isAlipayMonth = $this->getTurnover_withCondition($conditionSet);
		
		$pay_type = 2;		//微信
		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
		$totalAmount_isWeChatMonth = $this->getTurnover_withCondition($conditionSet);

        $pay_type = 4;		//新增一个余额
        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
        $totalAmount_isRemainderMonth = $this->getTurnover_withCondition($conditionSet);

        $pay_type = 5;		//新增一个第四方支付
        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange);
        $totalAmount_isFourthMonth = $this->getTurnover_withCondition($conditionSet);
		
		$totalAmount_whenMonth = $totalAmount_isCashMonth+$totalAmount_isAlipayMonth+$totalAmount_isWeChatMonth+$totalAmount_isRemainderMonth+$totalAmount_isFourthMonth;
		
		//-----------------------------------------------------------------------------------------------------
		
		//当天
		$this->assign("totalAmount_isCashToday",$totalAmount_isCashToday);
		$this->assign("totalAmount_isAlipayToday",$totalAmount_isAlipayToday);
		$this->assign("totalAmount_isWeChatToday",$totalAmount_isWeChatToday);

		$this->assign("totalAmount_isRemainderToday",$totalAmount_isRemainderToday);    // 新增余额
		$this->assign("totalAmount_isFourthToday",$totalAmount_isFourthToday);    // 新增第四方支付

		$this->assign("totalAmout_whenToday",$totalAmout_whenToday);
		
		//本月
		$this->assign("totalAmount_isCashMonth",$totalAmount_isCashMonth);
		$this->assign("totalAmount_isAlipayMonth",$totalAmount_isAlipayMonth);
		$this->assign("totalAmount_isWeChatMonth",$totalAmount_isWeChatMonth);

		$this->assign("totalAmount_isRemainderMonth",$totalAmount_isRemainderMonth);          // 新增余额
        $this->assign("totalAmount_isFourthMonth",$totalAmount_isFourthMonth);    // 新增第四方支付

		$this->assign("totalAmount_whenMonth",$totalAmount_whenMonth);
		
		//今日订单数
		$this->assign("orderNum_withToday",$orderNum_withToday);

        $restaurant_model = D("restaurant");
        $r_where['restaurant_id'] = session("Rid_withBoss");
        $rel = $restaurant_model->where($r_where)->field("logo")->find();
        $logo = $rel['logo'];
        $this->assign("logo",$logo);
		$this->display();
	}
		
	//当天(今日)的订单份数
	public function getOrderNum_withToday($conditionSet){
		$order = D("order");
		$orderNum_withToday = $order->where($conditionSet)->count();
		return $orderNum_withToday;
	}

    //当天(今日)的退菜份数
    public function getRefuseNum_withToday($conditionSet){
        $order = D("order");

        $order_lists = $order->where($conditionSet)->group('order_sn')->select();
        $refuse_num = 0;    // 退菜份数
        $refuse_total = 0;  // 退菜总额
        $order_food_model = D("order_food");
        foreach($order_lists as $key => $val){
            $condition['order_id'] = $val['order_id'];
            // 每个退菜（整单、单个菜）订单对应的所有的order_food的详情
            $food_lists = $order_food_model->where($condition)->field("order_id,food_id,food_price2,food_num,food_name,order_food_id,refuse_num")->select();

            // 判断是整单退还是单个菜退
            if($val['refuse'] == 1){
                // 整单退
                $refuse_total += $val['total_amount'];  // 退菜金额累加
                // 退菜份数累加（菜品订单的菜品数）
                foreach($food_lists as $key1=>$value1){
                    $refuse_num += $value1['food_num'];
                }
            }elseif($val['refuse'] == 2){
                // 单个菜退
                foreach($food_lists as $key1=>$value1){
                    // 退菜数字段的菜品数
                    if ($value1['refuse_num'] > 0) {
                        $refuse_total += $value1['food_price2'];
                        $refuse_num += $value1['food_num'];
                    }
                }
            }
        }
        $arr['refuse_num'] = $refuse_num;
        $arr['refuse_total'] = $refuse_total;
        return $arr;
    }
}
?>