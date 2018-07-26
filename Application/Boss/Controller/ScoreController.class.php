<?php
namespace Boss\Controller;
use Think\Controller;

class ScoreController extends CommonController{
	//菜品份数统计页
	public function index(){
		//判断是否存在session，不存在返回登录页
		if(!session("adminIDWithBoss")){
			$this->redirect('Common/login');
		}
		
		//判断是一开始加载数据还是点击搜索加载数据
		if($_POST){
			$startDate = I('startDate');
			$endDate = I('endDate');
			$startTime = I('startTime');
			$endTime = I('endTime');
		}else{
			$startDate = $this->getWhenMonth_firstday();	//得到当前月的第一天(日期格式)
			$endDate = $this->getWhenMonth_lastday();		//得到当前月的最后一天(日期格式)
			$startTime = "00:00:00";
			$endTime = "23:59:59";
		}

		//将日期格式转为时间戳，返回指定条件的第一天到最后一天的时间戳范围,默认返回当前月
		$timeStamp = $this->getWhenMonth_timeStamp($startDate, $endDate, $startTime, $endTime);	
		//传入管理员类型,返回店铺集(店铺与品牌的区别)
		$adminType = session("adminTypeWithBoss");

        // 如果是店铺账号进来则返回单个店铺id
		$storeRange = $this->getStoreRange($adminType);
		//传入开始与结束时间戳，返回查询订单条件集
		$conditionSet = $this->getConditionSet($timeStamp['startTimeStamp'],$timeStamp['endTimeStamp'],$storeRange);

		//传入条件集,返回菜品份数统计
//		$allFoodNameArr = $this->getFoodScoreCountResult_withTime($conditionSet);
		$allFoodNameArr = $this->getFood($conditionSet);

		//传入条件集,返回菜品属性份数统计
//		$allFoodAttributeArr = $this->getFoodAttributeCountResult_withTime($conditionSet);
		$allFoodAttributeArr = $this->getFoodAttribute($conditionSet);

		//步长
		$maxScore = $this->getMaxScore(array_values($allFoodNameArr));
		$stepLength = floor((session("screenWidth")-120)/$maxScore);
			
		//默认时间范围填充
		$this->assign("startDate",$startDate);
		$this->assign("endDate",$endDate);
		$this->assign("startTime",$startTime);
		$this->assign("endTime",$endTime);
		
		$this->assign("allFoodNameArr",$allFoodNameArr);					//菜品份数统计
		$this->assign("allFoodAttributeArr",$allFoodAttributeArr);			//菜品属性份数统计
		$this->assign("stepLength",$stepLength);							//每份菜品步长
		$this->display();
	}
	
	//根据条件集查询所有订单ID集
	public function getAllOrder_when($conditionSet){
		$order = order();
		$allOrder_when = $order->where($conditionSet)->field('order_id')->select();
		if(!empty($allOrder_when)){											//判断订单集是否为空
			$allOrderArr = array();
			foreach($allOrder_when as $key1=>$value1){
				$allOrderArr[] = $value1['order_id'];
			}	
		}
		return $allOrderArr;				//不为空返回订定集,否则返回空
	}
	
	//菜品份数的统计结果(条件时间内)
	public function getFoodScoreCountResult_withTime($conditionSet){
		//根据条件集查询所有订单ID集
		$allOrderArr = $this->getAllOrder_when($conditionSet);
		if(!empty($allOrderArr)){											//订单集不为空
			$condition1['order_id'] = array("in",$allOrderArr);
		
			//根据订单ID集查询所有不同名的菜品集及所对应的份数
			$order_food = order_F();
			$allFoodName_when = $order_food->where($condition1)->distinct(true)->field('food_name')->select();
			if(!empty($allFoodName_when)){
				$allFoodNameArr = array();
				foreach($allFoodName_when as $key2=>$value2){
					$condition1['food_name'] = $value2['food_name'];
					$allFoodNum_when = $order_food->where($condition1)->field('food_num')->select();
					foreach($allFoodNum_when as $key3=>$value3){
						$allFoodNameArr[$value2['food_name']] +=$value3['food_num'];
					}
				}
			}	
		}
		return $allFoodNameArr;				//不为空返回菜品份数统计结果,否则返回空
	}

	//菜品份数的统计结果(条件时间内)---分表
	public function getFood($conditionSet)
	{
		//判断是店铺还是品牌
		if(session("adminTypeWithBoss") == 1){
			$restaurant_id = implode(',',$conditionSet['restaurant_id']['1']);
		}else{
			$restaurant_id = $conditionSet['restaurant_id'];
		}
		$sql = "select distinct food_name,sum(food_num) num from tabName2 a LEFT join tabName1 b ON a.order_id = b.order_id WHERE  add_time BETWEEN {$conditionSet['add_time']['1']['0']} AND {$conditionSet['add_time']['1']['1']} AND restaurant_id IN ({$restaurant_id}) AND pay_type IN (0,1,2,4,5) AND order_type IN (1,2,3) AND order_status <> 0 GROUP BY food_name order by a.order_id";
		$arr = unionSelect2($conditionSet['add_time']['1']['0'],$conditionSet['add_time']['1']['1'],$sql);
		//遍历处理数组
		$data = array();
		foreach($arr as $k=>$v){
			$key = $v['food_name'];
			$value = $v['num'];
			$data["$key"] = $value;
		}

		return $data;
	}

	//菜品属性份数的统计结果(条件时间内) ----分表
	public function getFoodAttribute($conditionSet)
	{
		//判断是店铺还是品牌
		if(session("adminTypeWithBoss") == 1){
			$restaurant_id = implode(',',$conditionSet['restaurant_id']['1']);
		}else{
			$restaurant_id = $conditionSet['restaurant_id'];
		}
		$sql1 = "SELECT t1.food_attribute_name,SUM(food_num) num FROM tabName3 t1 inner join tabName2 t2 on t1.order_food_id = t2.order_food_id RIGHT join tabName1 t3 on t2.order_id = t3.order_id
where t3.add_time>={$conditionSet['add_time']['1']['0']} AND t3.add_time<={$conditionSet['add_time']['1']['1']} AND restaurant_id IN ({$restaurant_id}) and pay_type IN (0,1,2,4,5) and count_type = 1 and order_status<>0 group by food_attribute_name ORDER BY t3.order_id";
		$arr = unionSelect2($conditionSet['add_time']['1']['0'],$conditionSet['add_time']['1']['1'],$sql1);
		//遍历处理数组
		$data = array();
		foreach($arr as $k=>$v){
			$key = $v['food_attribute_name'];
			$value = $v['num'];
			$data["$key"] = $value;
		}

		return $data;
	}

	//菜品属性份数的统计结果(条件时间内)
	public function getFoodAttributeCountResult_withTime($conditionSet){
		//根据条件集查询所有订单ID集
		$allOrderArr = $this->getAllOrder_when($conditionSet);
		if(!empty($allOrderArr)){											//订单集不为空
			$condition1['order_id'] = array("in",$allOrderArr);
			
			//根据订单ID集查询order_food_id集
			$order_food = order_F();
			$allOrderFoodId = $order_food->where($condition1)->field("order_food_id")->select();
			if(!empty($allOrderFoodId)){
				$orderFoodIdArr = array();
				foreach($allOrderFoodId as $key3=>$value3){
					$orderFoodIdArr[] = $value3['order_food_id'];
				}
				$condition2['order_food_id'] = array("in",$orderFoodIdArr);
				
				//根据order_food_id集查询属性类型为统计的属性名及相应份数
				$order_food_attribute = order_F_A();
				$allCountTypeStatus = $order_food_attribute->where($condition2)->field("count_type,food_attribute_name,order_food_id")->select();
				if(!empty($allCountTypeStatus)){
					$allCountTypeStatusArr = array();
					foreach($allCountTypeStatus as $key4=>$value4){
						if($value4['count_type'] == 1){
							$condition3['order_food_id'] = $value4['order_food_id'];
							$attributeNum = $order_food->where($condition3)->field("food_num")->find()['food_num'];
							//判断原属性数组里是否存在该属性，是:份数叠加，否:添加进数组
							if(array_key_exists($value4['food_attribute_name'],$allCountTypeStatusArr)){
								$allCountTypeStatusArr[$value4['food_attribute_name']] += $attributeNum;
							}else{
								$allCountTypeStatusArr[$value4['food_attribute_name']] = $attributeNum;
							}	
						}
					}
				}
			}
		}
		return $allCountTypeStatusArr;
	}

	//得到最大份数
	public function getMaxScore($arr){
		$max=$arr[0];
       	$length=count($arr);
		
        for($i=1;$i<$length;$i++){
             if($arr[$i]>$max){
                $max=$arr[$i];
           }
        }
        return $max;
	}
}
?>