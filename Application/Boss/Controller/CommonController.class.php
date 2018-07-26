<?php
namespace Boss\Controller;
use Think\Controller;

class CommonController extends Controller{
	public function index(){
		$this->redirect("Common/login");
	}
	
	//得到当天的开始时间(时间戳格式)
	public function getBeginToday(){
		$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
		return $beginToday;
	}
	
	//得到当天的结束时间(时间戳格式)
	public function getEndToday(){
		$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		return $endToday;
	}
	
	
	//得到当前月的第一天(日期格式)
	public function getWhenMonth_firstday(){
		$firstday = date('Y-m-d', mktime(0, 0, 0, date('m'), 1));
		return $firstday;	
	}
	
	//得到当前月的最后一天(日期格式)
	public function getWhenMonth_lastday(){
		$lastday =  date('Y-m-d', mktime(0, 0, 0,date('m')+1,1)-1);
		return $lastday;
	}
	
	//返回指定时间戳范围
	public function getWhenMonth_timeStamp($startDate,$endDate,$startTime,$endTime){
		$timeStamp['startTimeStamp'] = strtotime($startDate."".$startTime);
		$timeStamp['endTimeStamp'] = strtotime($endDate."".$endTime);
		return $timeStamp;
	}
	
	//店铺与品牌的区别(店铺：只一家,品牌：品牌下所有)
	public function getStoreRange($adminType = 0){
		if($adminType == 0){
			$storeRange = session("Rid_withBoss");				
		}else{
			$condition['business_id'] = session("Rid_withBoss");
			$condition['status'] = 1;
			$restaurant = D('restaurant');	
			$allRest_whenBusiness = $restaurant->where($condition)->field('restaurant_id')->select();
			$allRestaurantArr = array();
			foreach($allRest_whenBusiness as $key=>$value){
				$allRestaurantArr[] = $value['restaurant_id'];
			}
			$storeRange = array("in",$allRestaurantArr);
		}
		return $storeRange;
	}
	
	//份数统计条件集
	public function getConditionSet($startTimeStamp,$endTimeStamp,$storeRange){
		$conditionSet['restaurant_id'] = $storeRange;			//第一个条件：店铺ID集
		$conditionSet['order_status'] = array('neq',0);			//第二个条件：订单状态
//		$conditionSet['pay_type'] = array("in",'0,1,2');		//第三个条件：支付方式
		$conditionSet['pay_type'] = array("in",'0,1,2,4,5');		//第三个条件：支付方式      新增一个余额支付
		$conditionSet['add_time'] = array("between",array($startTimeStamp,$endTimeStamp));	//第四个条件：时间范围
		$conditionSet['order_type'] = array('in','1,2,3');
		return $conditionSet;
	}
	
	//营业额统计条件集
	public function getConditionSet_isTurnover($startTimeStamp,$endTimeStamp,$pay_type,$storeRange,$refuse=null){
        if($refuse != null){
            $conditionSet['refuse'] = $refuse;
        }
		$conditionSet['restaurant_id'] = $storeRange;			//第一个条件：店铺ID集
		$conditionSet['order_status'] = array('neq',0);						//第二个条件：订单状态
		$conditionSet['pay_type'] = $pay_type;					//第三个条件：支付方式
		$conditionSet['add_time'] = array("between",array($startTimeStamp,$endTimeStamp));	//第四个条件：时间范围
		$conditionSet['order_type'] = array('in','1,2,3');
		return $conditionSet;
	}
	
	//参入时间、支付类型条件，返回营业额
	public function getTurnover_withCondition($conditionSet){
		$order = order();
		$totalAmount_withCondition = $order->where($conditionSet)->sum('total_amount');
		return $totalAmount_withCondition;
	}

    //参入时间、支付类型条件，返回营业额（分表）(指定月的每日，指定年的每月)
    public function getTurnover_withCondition_fenbiao($conditionSet){
        $order = 'order_'.date('Ym',$conditionSet['add_time'][1][0]);
        $totalAmount_withCondition = M($order)->where($conditionSet)->sum('total_amount');
        return $totalAmount_withCondition;
    }
	
	//参入条件集，查询下单时间集，返回年份列表
	public function getYearList_withCondition($conditionSet){
		$order = order();
		$allPayTime = $order->where($conditionSet)->field("pay_time")->select();
		$payTimeArr = array();
		foreach($allPayTime as $key=>$value){
			$payTimeArr[] = date("Y",$value['pay_time']);
		}
		return array_unique($payTimeArr);
	}
	
	//登录界面
	public function login(){
		$this->display();
	}
	
	//登录校验
	public function loginCheck(){
		$adminType = I('adminType');
		if($adminType == 0){
			$restaurant_manager = D('restaurant_manager');
			$condition['login_account'] = I('login_account');
			$db_password = $restaurant_manager->where($condition)->field("id,login_account,password,restaurant_id")->find();
			if($db_password){
				$password = I('login_password');
				if($db_password['password'] == $password){
					session("adminIDWithBoss",$db_password['id']);
					session("adminNameWithBoss",$db_password['login_account']);
					session("adminTypeWithBoss",0);
					session("Rid_withBoss",$db_password['restaurant_id']);
					session("screenWidth",I('screenWidth'));
					$msg['msg'] = "登录成功！！";
					$msg['code'] = 1;
				}else{
					$msg['msg'] = "密码错误！！";
					$msg['code'] = 0;
				}
			}else{
				$msg['msg'] = "帐号不存在！！";
				$msg['code'] = 0;
			}
			exit(json_encode($msg));
		}else{
			$business = D('business');
			$condition['business_account'] = I('login_account');
			$db_password = $business->where($condition)->field("business_id,business_account,business_password")->find();
			if($db_password){
				$password = I('login_password');
				if($db_password['business_password'] == $password){
					session("adminIDWithBoss",$db_password['business_id']);
					session("adminNameWithBoss",$db_password['business_account']);
					session("adminTypeWithBoss",1);
					session("Rid_withBoss",$db_password['business_id']);
					session("screenWidth",I('screenWidth'));
					$msg['msg'] = "登录成功！！";
					$msg['code'] = 1;
				}else{
					$msg['msg'] = "密码错误！！";
					$msg['code'] = 0;
				}
			}else{
				$msg['msg'] = "帐号不存在！！";
				$msg['code'] = 0;
			}
			exit(json_encode($msg));
		}
	}

	//退出帐号
	public function loginout(){
		session("adminIDWithBoss",null);
		session("adminNameWithBoss",null);
		session("adminTypeWithBoss",null);
		session("Rid_withBoss",null);
		session("screenWidth",null);
		$this->redirect("Common/login");
	}

	
}
?>