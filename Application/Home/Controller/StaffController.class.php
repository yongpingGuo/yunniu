<?php
namespace Home\Controller;
use Think\Controller;

class StaffController extends Controller{
	public function __construct(){
		Controller::__construct();
		if(!cookie('restaurant_id')){
			exit("没有绑定店铺");
		};
	}
	public function index(){
		$order = order();
		$condition['restaurant_id'] = cookie('restaurant_id');
		$restaurantModel = D("Restaurant");
		$device_code = $restaurantModel->where($condition)->find()['show_num_d'];
		$this->assign("device_code",$device_code);
		$t = time();
		$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
		$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));

		//coming中的订单
		$condition['table_num'] = 0;
		$condition['desk_code'] = 0;
		$condition['pay_time']  = array('between',array($start,$end));
		$condition['order_status']  = array('not in',array(10,11,12));
		$resultArr = $order->where($condition)->select();

		//finish的订单
		$condition1['restaurant_id'] = cookie('restaurant_id');
		$condition1['table_num'] = 0;
		$condition1['desk_code'] = 0;
		$condition1['pay_time']  = array('between',array($start,$end));
		$condition1['order_status']  = 11;
		$resultArr1 = $order->where($condition1)->select();

		$condition2['restaurant_id'] = cookie('restaurant_id');
		$condition2['advertisement_type'] = 2;
		$adver = D('advertisement');
		$addr = $adver->where($condition2)->field('advertisement_image_url')->find()['advertisement_image_url'];
		$this->assign("addr",$addr);
		$this->assign("resultArrLen",count($resultArr));
		$this->assign("resultArrLen1",count($resultArr1));
		$this->assign("total",ceil(count($resultArr)/16));
		$this->assign("total1",ceil(count($resultArr1)/5));
		$this->assign("resultArr",$resultArr);
		$this->assign("resultArr1",$resultArr1);
		$this->display();
	}

	//取餐核销页面
	public function clerk(){
		//10 11 12
		//echo date('Ymd',time())

		$order = order();
		$condition['restaurant_id'] = cookie('restaurant_id');
		$restaurantModel = D("Restaurant");
		$device_code = $restaurantModel->where($condition)->find()['show_num_d'];
		$this->assign("device_code",$device_code);
		$t = time();
		$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
		$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
		//echo date("Y-m-d H:i:s",$end);
		$condition['table_num'] = 0;
		$condition['desk_code'] = 0;
		$condition['pay_time']  = array('between',array($start,$end));
		$condition['order_status']  = array('not in',array("10","12"));
		$arr = $order->where($condition)->order("pay_time")->select();
		foreach($arr as $k => $v){
			$arr[$k]["order_sn"] =  substr($v['order_sn'],-5,5);//pddt.com
		}

		//dump($resultArr);
		$this->assign("info",$arr);
		$this->display();
	}

	//通过order_id显示ordre内容
	public function getorderinfo(){
		//dump(I('get.order_id'));
		$order = order();
		$condition['order_id'] = I('get.order_id');
		$arr = $order->where($condition)->find();
//		dump($arr);
		$this->assign("info1",$arr);
		$order_food = order_F();
		$arr2 = $order_food->where($condition)->select();
		$this->assign("info2",$arr2);
		$this->display('clerk1');
	}

	//订单请取单状态
	public function changestatus(){
		//dump(I('get.order_id'));
		$order = order();
		$condition['order_status'] = 11;
		$condition['order_id'] = I('get.order_id');
		$r = $order->save($condition);
		if($r){
//			$orderInfo = $order->where($condition)->field("order_sn,restaurant_id")->find();
			$orderInfo = $order->where($condition)->field("order_sn,restaurant_id,take_num")->find();
			//$content['status'] = 111;
			//file_put_contents(__DIR__."/"."orderSNlog.txt","消息：原order_sn值：".$orderInfo['order_sn']."|日期：".date('Y-m-d h:i:s')."\r\n",FILE_APPEND);

           /* if($orderInfo['take_num']){
                $content['order_sn'] = $orderInfo['take_num'];
            }else{
                $content['order_sn'] = substr($orderInfo['order_sn'],-5);
            }*/
			$content['order_sn'] = substr($orderInfo['order_sn'],-5);
            /*$tmp = $orderInfo['take_num'];
            if(strlen($tmp) == 4){
                // 12007   2009
                $begin = substr($tmp,0,1);
                $end = substr($tmp,-3,3);
                $final = $begin.".".$end;
            }elseif(strlen($tmp) == 5){
                $begin = substr($tmp,0,2);
                $end = substr($tmp,-3,3);
                $final = $begin.".".$end;
            }else{
                $final = $tmp;
            }
			$content['take_num'] = $final;*/
            $content['take_num'] = $orderInfo['take_num'];


			$content['action'] = "finish_order";
			//file_put_contents(__DIR__."/"."orderSNlog.txt","消息：order_sn值：".$content['order_sn']."|日期：".date('Y-m-d h:i:s')."\r\n",FILE_APPEND);
			$contentJson = json_encode($content);

			$restaurantModel = D("Restaurant");
			$rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
			$show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

			$post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
			$rel = sendMsgToDevice($post_data);
//			if($rel == "ok"){
			$msg['msg'] = '请取单状态修改成功';
			$msg['data'] = 1;
//			}else{
//				$msg['msg'] = '请取单推送修改失败';
//				$msg['data'] = 0;
//			}
		}else{
			$msg['msg'] = '请取单状态修改失败';
			$msg['data'] = 0;
		}
		exit(json_encode($msg));
	}

	//订单核销状态
	public function changestatus1(){
		//dump(I('get.order_id'));
		$order = order();
		$condition['order_status'] = 12;
		$condition['order_id'] = I('get.order_id');
		$r = $order->save($condition);
		if($r){
			$orderInfo = $order->where($condition)->field("order_sn,restaurant_id")->find();
			$content['status'] = 112;
			$content['order_sn'] = $orderInfo['order_sn'];
			$contentJson = json_encode($content);

			$restaurantModel = D("Restaurant");
			$rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
			$show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];

			$post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);

			$msg['msg'] = '请取单状态修改成功';
			$msg['data'] = 1;

			echo json_encode($msg);
			$rel = sendMsgToDevice($post_data);
			exit;
		}else{
			$msg['msg'] = '请核销状态修改失败';
			$msg['data'] = 0;
			echo json_encode($msg);
			exit;
		}
	}

	//显示器上数据刷新
	public function refresh(){
		$order = order();
		$condition['restaurant_id'] = cookie('restaurant_id');
		$t = time();
		$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
		$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
		//echo date("Y-m-d H:i:s",$end);
		$condition['table_num'] = 0;
		$condition['desk_code'] = 0;
		$condition['pay_time']  = array('between',array($start,$end));
		$condition['order_status']  = array('not in',array(10,11,12));
		$resultArr = $order->where($condition)->select();

		$this->assign("resultArrLen",count($resultArr));
		$this->assign("total",ceil(count($resultArr)/16));
		//$this->assign("total1",ceil(count($resultArr1)/8));
		$this->assign("resultArr",$resultArr);
		$this->display('table');
	}

	public function refresh1(){
		$order = order();
		$condition['restaurant_id'] = cookie('restaurant_id');
		$t = time();
		$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
		$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
		//echo date("Y-m-d H:i:s",$end);
		$condition['table_num'] = 0;
		$condition['desk_code'] = 0;
		$condition['pay_time']  = array('between',array($start,$end));
		$condition['order_status']  = array('eq',11);
		$resultArr1 = $order->where($condition)->select();

		$this->assign("resultArrLen1",count($resultArr1));
		$this->assign("total1",ceil(count($resultArr1)/5));
		$this->assign("resultArr1",$resultArr1);
		$this->display('table1');
	}
}