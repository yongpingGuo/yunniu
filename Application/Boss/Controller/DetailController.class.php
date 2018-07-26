<?php
namespace Boss\Controller;
use Think\Controller;

class DetailController extends CommonController{
	//明细统计页面
	public function index(){
		//判断是否存在session，不存在返回登录页
		if(!session("adminIDWithBoss")){
			$this->redirect('Common/login');
		}
		
		$adminType = session("adminTypeWithBoss");
		
		//判断是初始加载页面与刷新、还是POST指定条件查询数据
		if($_POST){
			//查询日期的开始、结束时间戳、店铺集
			$checkDate = I("checkedYear")."-".I("checkedMonth")."-".I("checkDay");
			$startTimeStamp = strtotime($checkDate."00:00:00");
			$endTimeStamp = strtotime($checkDate."23:59:59");
			
			//判断查询的是所有店铺还是指定店铺
			$storeRange = I('restaurant_id') ? I('restaurant_id'):$this->getStoreRange($adminType);
			
			//指定查询后,把条件又回赋值给页面，防止页面刷新,条件变化
			$searchYear = date("Y",strtotime($checkDate));
			$searchMonth = date("m",strtotime($checkDate));
			$searchDay = date("d",strtotime($checkDate));
			$searchRestaurant = I("restaurant_id");
		}else{
			//默认为当天的开始、结束时间戳、店铺集：店铺默认自已、品牌：默认所有
			$startTimeStamp = $this->getBeginToday();
			$endTimeStamp = $this->getEndToday();
			$storeRange = $this->getStoreRange($adminType);
			$searchYear = date("Y");
			$searchMonth = date("m");
			$searchDay = date("d");
		}
        /*$startTimeStamp = 1509465600;
		$endTimeStamp = 1509551999;*/

        $totalAmount_isWeChatSearchDay = 0;
        $totalAmount_isAlipaySearchDay = 0;
        $totalAmount_isCashSearchDay   = 0;
        $totalAmount_isRemainderSearchDay = 0;
        $totalAmount_isFourthSearchDay = 0;
        if (is_string($storeRange)) {
            $restaurantCondition = " restaurant_id = $storeRange ";
            $Model = M();
        }else{
            $storeRangeStr = implode(',',$storeRange[1]);
            $restaurantCondition = " restaurant_id IN ($storeRangeStr) ";

//            $pay_type = 0;		//现金
//            $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
//            $totalAmount_isCashSearchDay = $this->getTurnover_withCondition($conditionSet);
        }

        //订单信息
//        $order_list = $Model->query("SELECT SUM(total_amount) total_amount,a.pay_type
//            FROM (SELECT `total_amount`,pay_type,restaurant_id,order_status,add_time,cashier_id FROM `order` GROUP BY order_sn ) a WHERE
//            `restaurant_id` = ".$storeRange." AND `order_status` <> 0 AND a.pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStamp." AND ".$endTimeStamp." GROUP BY a.pay_type");
//
//        foreach ($order_list as $k => $v) {
//            if ($v['pay_type'] == 0) {
//                $totalAmount_isCashSearchDay = $v['total_amount'];//现金总额
//            }
//        }


        $sql_orignal = "SELECT SUM(total_amount) total_amount,a.pay_type
            FROM (SELECT `total_amount`,pay_type,restaurant_id,order_status,add_time,cashier_id FROM `tabName1` GROUP BY order_sn ) a WHERE
            $restaurantCondition AND `order_status` <> 0 AND a.pay_type IN ('0','1','2','4','5') AND `add_time` BETWEEN ".$startTimeStamp." AND ".$endTimeStamp." GROUP BY a.pay_type";
        $order_list = unionSelect2($startTimeStamp,$endTimeStamp,$sql_orignal);

        foreach ($order_list as $k => $v) {
            if ($v['pay_type'] == 0) {
                $totalAmount_isCashSearchDay = $v['total_amount'];//现金总额
            }

            if ($v['pay_type'] == 1) {
                $totalAmount_isAlipaySearchDay = $v['total_amount'];//支付宝总额
            }
            if ($v['pay_type'] == 2) {
                $totalAmount_isWeChatSearchDay = $v['total_amount'];//微信宝总额
            }
            if ($v['pay_type'] == 4) {
                $totalAmount_isRemainderSearchDay = $v['total_amount'];//会员余额总额
            }
            if ($v['pay_type'] == 5) {
                $totalAmount_isFourthSearchDay = $v['total_amount'];//第四方总额
            }
        }
		
		//------------------------查询指定日期的现金、支付宝、微信、总统计营业额(日报表饼状图)-----------------------
	
		
//		$pay_type = 1;		//支付宝
//		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
//		$totalAmount_isAlipaySearchDay = $this->getTurnover_withCondition($conditionSet);
//
//		$pay_type = 2;		//微信
//		$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
//		$totalAmount_isWeChatSearchDay = $this->getTurnover_withCondition($conditionSet);
//
//        $pay_type = 4;		//余额支付
//        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
//        $totalAmount_isRemainderSearchDay = $this->getTurnover_withCondition($conditionSet);
//
//        $pay_type = 5;		//第四方支付
//        $conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
//        $totalAmount_isFourthSearchDay = $this->getTurnover_withCondition($conditionSet);
		
		//查询日期的总营业额
		$totalAmout_searchDay = $totalAmount_isCashSearchDay+$totalAmount_isAlipaySearchDay+$totalAmount_isWeChatSearchDay+$totalAmount_isRemainderSearchDay+$totalAmount_isFourthSearchDay; // 新增余额、第四方
		
		//---------------------------------------------------------------------------------------------------------
		
		//---------------------------条件年、月，每日的现金营业额(月报表柱状图)---------------------------------------
		$pay_type = 0;			//现金
		$everyDayTurnover_isCash = $this->getEveryDayTurnover($startTimeStamp, $pay_type, $storeRange);
		
		$pay_type = 1;			//支付宝
		$everyDayTurnover_isAlipay = $this->getEveryDayTurnover($startTimeStamp, $pay_type, $storeRange);
		
		$pay_type = 2;			//微信
		$everyDayTurnover_isWeChat = $this->getEveryDayTurnover($startTimeStamp, $pay_type, $storeRange);

        $pay_type = 4;			//余额
        $everyDayTurnover_isRemainder = $this->getEveryDayTurnover($startTimeStamp, $pay_type, $storeRange);

        $pay_type = 5;			//第四方
        $everyDayTurnover_isFourth = $this->getEveryDayTurnover($startTimeStamp, $pay_type, $storeRange);

		//条件月每日的营业额
		$everyDayTotalAmount = $this->getOneMonthTurnover_withSearch($searchYear, $searchMonth,$storeRange);
			
		//条件月总营业额
		$totalAmountSearchMonth = $this->getTotalAmountSearchMonth($everyDayTotalAmount);
		
		//----------------------------------------------------------------------------------------------------
	
		//-----------------------------条件年每月现金营业额(年报表柱状图)---------------------------------------
		$pay_type = 0;			//现金
		$everyMonthTurnover_isCash = $this->getEveryMonthTurnover($startTimeStamp,$pay_type, $storeRange);
		
		$pay_type = 1;			//支付宝
		$everyMonthTurnover_isAlipay = $this->getEveryMonthTurnover($startTimeStamp,$pay_type, $storeRange);
		
		$pay_type = 2;			//微信
		$everyMonthTurnover_isWeChat = $this->getEveryMonthTurnover($startTimeStamp,$pay_type, $storeRange);

        $pay_type = 4;			//余额
        $everyMonthTurnover_isRemainder = $this->getEveryMonthTurnover($startTimeStamp,$pay_type, $storeRange);

        $pay_type = 5;			//第四方
        $everyMonthTurnover_isFourth = $this->getEveryMonthTurnover($startTimeStamp,$pay_type, $storeRange);
		
		//条件年每月的营业额
		$everyMonthTotalAmount = $this->getOneYearTurnover_whithSearch($searchYear, $storeRange);

		
		//条件年总营业额
		$totalAmountSearchYear = $this->getTotalAmountSearchYear($everyMonthTotalAmount);
		
		//-----------------------------------------------------------------------------------------------------
		
		//当登录类型为品牌时列出该品牌下的所有店铺，若为店铺则隐藏下拉
		$restaurantArr = $this->getStoreList($adminType);
		
		//年、月、日份列表
		$payTimeArr = $this->getYearList($adminType);
		$monthList = $this->getMonthList();
		$dayList = $this->getSearchDayNum($searchYear, $searchMonth);
		
		//当前年、月、日
		$this->assign("whenYear",$searchYear);
		$this->assign("whenMonth",$searchMonth);
		$this->assign("whenDay",$searchDay);
		$this->assign("searchRestaurant",$searchRestaurant);
		
		//年、月、日份列表	
		$this->assign("payTimeArr",$payTimeArr);
		$this->assign("monthList",$monthList);	
		$this->assign("dayList",$dayList);
		
		$this->assign("restaurantArr",$restaurantArr);
		
		//条件日(日报表饼状图)
		$this->assign("totalAmount_isCashSearchDay",$totalAmount_isCashSearchDay);
		$this->assign("totalAmount_isAlipaySearchDay",$totalAmount_isAlipaySearchDay);
		$this->assign("totalAmount_isWeChatSearchDay",$totalAmount_isWeChatSearchDay);
		$this->assign("totalAmount_isRemainderSearchDay",$totalAmount_isRemainderSearchDay);   // 新增余额
		$this->assign("totalAmount_isFourthSearchDay",$totalAmount_isFourthSearchDay);   // 新增第四方
		$this->assign("totalAmout_searchDay",$totalAmout_searchDay);
		
		//条件年、月(月报表柱状图)
		$this->assign("everyDayTurnover_isCash",json_encode($everyDayTurnover_isCash));
		$this->assign("everyDayTurnover_isAlipay",json_encode($everyDayTurnover_isAlipay));
		$this->assign("everyDayTurnover_isWeChat",json_encode($everyDayTurnover_isWeChat));
		$this->assign("everyDayTurnover_isRemainder",json_encode($everyDayTurnover_isRemainder));     // 新增余额
		$this->assign("everyDayTurnover_isFourth",json_encode($everyDayTurnover_isFourth));     // 新增第四方
		$this->assign("totalAmountSearchMonth",$totalAmountSearchMonth);
		
		//条件年(年报表柱状图)
		$this->assign("everyMonthTurnover_isCash",json_encode($everyMonthTurnover_isCash));
		$this->assign("everyMonthTurnover_isAlipay",json_encode($everyMonthTurnover_isAlipay));
		$this->assign("everyMonthTurnover_isWeChat",json_encode($everyMonthTurnover_isWeChat));
		$this->assign("everyMonthTurnover_isRemainder",json_encode($everyMonthTurnover_isRemainder));     // 新增余额
		$this->assign("everyMonthTurnover_isFourth",json_encode($everyMonthTurnover_isFourth));     // 新增第四方
		$this->assign("totalAmountSearchYear",$totalAmountSearchYear);
		$this->display();
	}
	
	//得到一年中每个月的明细营业额(默认：当前年,条件：查询年)
	public function getOneYearTurnover_whithSearch($searchYear,$storeRange){
		$everyMonthSearchYearTimeStamp = monthForYear($searchYear);
		$pay_type = array("in","0,1,2,4,5");  // 新增余额
		$everyMonthTotalAmount = array();
		for($i=0;$i<=11;$i++){
			$startTimeStamp = $everyMonthSearchYearTimeStamp[$i]['month_start'];
			$endTimeStamp = $everyMonthSearchYearTimeStamp[$i]['month_end'];
			$everyConditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
			$everyTotalAmount =  $this->getTurnover_withCondition_fenbiao($everyConditionSet);
			$everyMonthTotalAmount[] = $everyTotalAmount;
		}
		return $everyMonthTotalAmount;
	}
	
	//得到条件年的总营业额
	public function getTotalAmountSearchYear($everyMonthTotalAmount){
		$totalAmountSearchYear = array_sum($everyMonthTotalAmount);
		return $totalAmountSearchYear;
	}
	
	
	//得到一个月每一天的的明细营业额(默认：当前月,条件：查询月)
	public function getOneMonthTurnover_withSearch($searchYear,$searchMonth,$storeRange){
		$everyDaySearchMonthTimeStamp = dayForMonth($searchYear,$searchMonth);
		$pay_type = array("in","0,1,2,4,5");     // 新增余额
		$everyDayTotalAmount = array();
		for($i=0;$i<count($everyDaySearchMonthTimeStamp);$i++){
			$startTimeStamp = $everyDaySearchMonthTimeStamp[$i]['day_start'];
			$endTimeStamp = $everyDaySearchMonthTimeStamp[$i]['day_end'];
			$everyConditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
			$everyTotalAmount =  $this->getTurnover_withCondition_fenbiao($everyConditionSet);
			$everyDayTotalAmount[] = $everyTotalAmount;
		}
		return $everyDayTotalAmount;
	}
	
	//得到条件月的总营业额
	public function getTotalAmountSearchMonth($everyDayTotalAmount){
		$totalAmountSearchMonth = array_sum($everyDayTotalAmount);
		return $totalAmountSearchMonth;
	}
	
	//列出店铺年份列表(此年份以店铺下第一单为第一年)
	public function getYearList($adminType = 0){
		//条件集
		$pay_type = array("in","0,1,2,4,5");    // 新增余额
		$storeRange = $this->getStoreRange($adminType);
		$conditionSet['restaurant_id'] = $storeRange;
		$conditionSet['order_status'] = array('neq',0);
		$conditionSet['pay_type'] = array("in","0,1,2,4,5");  // 新增余额
		$conditionSet['order_type'] = array("in","1,2,3");
		
		//得到年份列表
		$payTimeArr = $this->getYearList_withCondition($conditionSet);
		return $payTimeArr;
	}
	
	//列出月份列表
	public function getMonthList(){
		$monthList = array("01","02","03","04","05","06","07","08","09","10","11","12");
		return $monthList;
	}
	
	//列出指定年月的天数
	public function getSearchDayNum($searchYear,$searchMonth){
		//指定年月天数
		$dayNum = get_days_by_year($searchYear,$searchMonth);		
		//天数数组
		$dayList = array();
		for($i=1;$i<=$dayNum;$i++){
			if($i<10){
				$i = "0".$i;
			}
			$dayList[] = $i;
		}
		return $dayList;
	}
	
	//选中月份,ajax动态变化天数
	public function getSearchDayNumByAjax($searchYear,$searchMonth){
		//指定年月天数
		$dayNum = get_days_by_year($searchYear,$searchMonth);		
		//天数数组
		$dayList = array();
		for($i=1;$i<=$dayNum;$i++){
			if($i<10){
				$i = "0".$i;
			}
			$dayList[] = $i;
		}
		exit(json_encode($dayList));
	}
	
	//登录类型：品牌登录，列出该代理下的所有店铺、 店铺登录、为空且隐藏DIV
	public function getStoreList($adminType){
		$restaurantArr = array();
		if($adminType == 0){
			$restaurantArr = "";
		}else{
			$condition['business_id'] = session("adminIDWithBoss");
			$condition['status'] = 1;
			$restaurant = D('restaurant');
			$allRestaurant = $restaurant->where($condition)->field("restaurant_id,restaurant_name")->select();
			foreach($allRestaurant as $key=>$value){
				$restaurantArr[$value['restaurant_id']] = $value['restaurant_name'];
			}
		}
		return $restaurantArr;
	}
	
	//指定条件年、月，月报表每日营业额
	public function getEveryDayTurnover($Date,$pay_type,$storeRange){
		$checkedYear = date("Y",$Date);
		$checkedMonth = date("m",$Date);
		$everyDayTimeStampArr = dayForMonth($checkedYear,$checkedMonth);
		$everyDayTurnover = array();
		for($i=0;$i<count($everyDayTimeStampArr);$i++){
			$startTimeStamp = $everyDayTimeStampArr[$i]['day_start'];
			$endTimeStamp = $everyDayTimeStampArr[$i]['day_end'];
			$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
			$totalAmount_withCondition = $this->getTurnover_withCondition_fenbiao($conditionSet);
			$everyDayTurnover[] = $totalAmount_withCondition ? $totalAmount_withCondition:0;
		}
		return $everyDayTurnover;
	}
	
	
	//指定条件年报表现金每月的营业额
	public function getEveryMonthTurnover($Date,$pay_type,$storeRange){
		$checkedYear = date("Y",$Date);
		$everyMonthTimeStampArr = monthForYear($checkedYear);
		$everyMonthTurnover = array();
		for($i=0;$i<12;$i++){
			$startTimeStamp = $everyMonthTimeStampArr[$i]['month_start'];
			$endTimeStamp = $everyMonthTimeStampArr[$i]['month_end'];
			$conditionSet = $this->getConditionSet_isTurnover($startTimeStamp, $endTimeStamp, $pay_type, $storeRange);
			$totalAmount_withCondition = $this->getTurnover_withCondition_fenbiao($conditionSet);
			$everyMonthTurnover[] =	$totalAmount_withCondition ? $totalAmount_withCondition:0;
		}
		return $everyMonthTurnover;
	}
	
}
?>