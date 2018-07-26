<?php
namespace Agent\Controller;
use Think\Controller;
class DeviceController extends Controller{
	public function device(){
		//---------------------------------------------设备左侧树形列表--------------------------------------------
		$restaurant = D('Restaurant');
		$condition['business_id'] = session('business_id');

		//获取代理下的店铺列表
		$restaurantModel = D("restaurant");
		$condition['status'] = array("neq",0);
		$res_list = $restaurantModel->where($condition)->field("restaurant_id,restaurant_name")->select();
		$this->assign("restaurant_list",$res_list);

		$condition['status'] = 1;
		$Arrlist = $restaurant->distinct(true)->field('restaurant_name')->where($condition)->select();	//查询该代理下所有不同名的品牌
		foreach($Arrlist as $key=>$value){
			$Arrlist1 = array();
			$condition1['restaurant_name'] = $value['restaurant_name'];
			$condition1['business_id'] = session('business_id');
			$condition1['status'] = 1;
			$Arrlist2 = $restaurant->where($condition1)->distinct(true)->field('city3,restaurant_name')->select();
			$cityModel = D('region');
			foreach($Arrlist2 as $key2=>$value2){
				$c_condition['id'] = $value2['city3'];
				$cityName = $cityModel->where($c_condition)->field("name")->find()['name'];
				$condition2['restaurant_name'] = $value2['restaurant_name'];
				$condition2['city3'] = $value2['city3'];
				$condition2['business_id'] = session('business_id');
				$condition2['status'] = 1;
				$Arrlist3 = $restaurant->where($condition2)->select();
				$Arrlist4 = array();
				foreach($Arrlist3 as $key3=>$vv){
					$Arrlist4[$key3]['address'] = $vv["address"];
					$Arrlist4[$key3]['id'] = $vv["restaurant_id"];
				}
				$Arrlist1[$cityName] = $Arrlist4;
			}	
			$Arrlist[$key]['CityArray'] = $Arrlist1;				
		}
		$this->assign("Arrlist",$Arrlist);			//设备左侧列表的渲染

		//------------------------------------------//设备右侧的数据表格-----------------------------------
		$where['business_id'] = session('business_id');
		$code = D('code');
		$RuseltArr = $code->where($where)->field('code_id,restaurant_id')->select();

		$RuseltArr3 = array();
		$device = D('device');
		foreach($RuseltArr as $vvalue){
			$condition3['code_id'] = $vvalue['code_id'];
			$RusultArr2 = $device->where($condition3)->find();
			if($RusultArr2){
				$RusultArr2['start_time'] = $RusultArr2['start_time'];
				$RusultArr2['end_time'] = $RusultArr2['end_time'];
				$res_condition['restaurant_id'] = $vvalue['restaurant_id'];
				$resInfo = $restaurantModel->where($res_condition)->field("restaurant_id,restaurant_name")->find();

				$temp = array_merge($resInfo,$RusultArr2);
				$RuseltArr3[] = $temp;
			}
		}

		$page = I('page')?I('page'):1;
		$row = 15;
		$pageattr = array_page($RuseltArr3,$row,$page);
		$this->assign("page",$pageattr['show']);
		$this->assign("deviceList",$pageattr['list']);
		$this->display();
	}


	//设备页面操作ajax刷新
	public function device_ajax(){
		$where['business_id'] = session('business_id');

		//获取代理下的店铺列表
		$restaurantModel = D("restaurant");
		$where['status'] = array("neq",0);
		$res_list = $restaurantModel->where($where)->field("restaurant_id,restaurant_name")->select();
		$this->assign("restaurant_list",$res_list);

		$code = D('code');
		$RuseltArr = $code->where($where)->field('code_id,restaurant_id')->select();	
		$RuseltArr3 = array();
		$device = D('device');
		foreach($RuseltArr as $vvalue){
			$condition3['code_id'] = $vvalue['code_id'];
			$RusultArr2 = $device->where($condition3)->find();
			if($RusultArr2){
				$res_condition['restaurant_id'] = $vvalue['restaurant_id'];
				$resInfo = $restaurantModel->where($res_condition)->field("restaurant_id,restaurant_name")->find();
				$temp = array_merge($resInfo,$RusultArr2);
				$RuseltArr3[] = $temp;
			}
		}
		$page = I('page')?I('page'):1;
		$row = 15;
		$pageattr = array_page($RuseltArr3,$row,$page);
		$this->assign("page",$pageattr['show']);
		$this->assign("deviceList",$pageattr['list']);
		$this->display('deviceAjax');
	}
	
	//设备管理左侧选择ajax加载显示
	public function showajaxinfo(){
		$map['business_id'] = session('business_id');

		//获取代理下的店铺列表
		$restaurantModel = D("restaurant");
		$map['status'] = array("neq",0);
		$res_list = $restaurantModel->where($map)->field("restaurant_id,restaurant_name")->select();
		$this->assign("restaurant_list",$res_list);
		$code = D('code');
		$condition['restaurant_id'] = I('restaurant_id');
		$condition['business_id'] = session('business_id');
		$Arrlist = $code->where($condition)->select();				//查询同一代理下同一间店铺的注册码(所有注册码不分状态)
		$device = D('device');
		$Arrlist2 = array();
		foreach($Arrlist as $value){
			$condition1['code_id'] = $value['code_id'];
			$object = $device->where($condition1)->find();
			if($object){
				$where['restaurant_id'] = I('restaurant_id');
				$resInfo=$restaurantModel->where($where)->field("restaurant_id,restaurant_name")->find();
				$temp = array_merge($resInfo,$object);
				$Arrlist2[] = $temp;								//在设备表中判断这些注册码有哪些绑定了设备
			}	
 		}
		/*$page = I('page')?I('page'):1;
		$row = 2;
		$pageattr = array_page($Arrlist2,$row,$page);
		$this->assign("page",$pageattr['show']);*/
		$this->assign("deviceList",$Arrlist2);
		$this->display('deviceAjax');
	}
	
	//设备编辑表单前的填充
	public function modify_device(){
		$device = D('device');
		$condition['device_id'] = I('get.device_id');
		$object = $device->where($condition)->field('device_name,device_status,code_id,device_id')->find();
		$code = D('code');
		$condition1['code_id'] = $object['code_id'];
		$restaurant_id = $code->where($condition1)->field('restaurant_id')->find()['restaurant_id'];
		$object['restaurant_id'] = $restaurant_id;
		exit(json_encode($object));
	}

	//编辑设备
	public function update_device(){
		$device = D('device');
		$data['device_id'] = I('post.id');
		$data['device_name'] = I('post.name');
		$data['device_status'] = I('post.state');
		$r = $device->save($data);
		if($r){
			$uuid = I('post.uuid');
			if(!empty($uuid)){
				$this->showajaxinfo();
			}else{
				$this->device_ajax();
			}
		}
	}

	//删除设备
	public function del_device(){
		/*$device = D('device');
		$where['device_id'] = I('post.id');
		$device_info = $device->where($where)->field("code_id")->find();

		$r = $device->where($where)->delete();

		if($r !== false){
			file_put_contents(__DIR__."/del_device_ids.txt",var_export($where)."|",FILE_APPEND);
			//小于0
			$code_id = $device_info['code_id'];
			$c_where['code_id'] = $code_id;
			$code_model = D("code");
			$code_info = $code_model->where($c_where)->field("rest_timestamp")->find();

			if($code_info['rest_timestamp'] > 0){
				$data["code_status"] = 1;
				$code_model->where($c_where)->save($data);
			}else{
				$code_model->delete($c_where);
			}
		}*/

        $deviceModel = D("device");
        $de_where['device_id'] = I('post.id');
        $device_info = $deviceModel->where($de_where)->find();
        $code_id = $device_info['code_id'];
        $c_where['code_id'] = $code_id;
        $code_model = D("code");
        $res = $code_model->where($c_where)->delete();
        $rel = $deviceModel->where($de_where)->delete();


		//删除成功，重新获取设备列表
		$where['business_id'] = session('business_id');
		$code = D('code');
		$Arrlist = $code->where($where)->select();
		$ResultArr = array();
		$device = D('device');
		foreach($Arrlist as $vvalue){
			$condition3['code_id'] = $vvalue['code_id'];
			$RusultArr2 = $device->where($condition3)->find();
			if($RusultArr2){
				$ResultArr[] = $RusultArr2;
			}
		}
//		dump($ResultArr);
		$this->assign("deviceList",$ResultArr);
		$this->display('deviceAjax');
	}

	//设备页面店铺模糊查询
	public function showdevicebykey(){
		$key = I('get.key');
		$restaurant = D('Restaurant');
		$condition['business_id'] = session('business_id');
		$condition['restaurant_name'] = array("like","%".$key."%");
		$condition['status'] = 1;
		$Arrlist = $restaurant->distinct(true)->field('restaurant_name')->where($condition)->select();//条件查询该代理下的所有店铺
		foreach($Arrlist as $key=>$value){
			$Arrlist1 = array();
			$condition1['restaurant_name'] = $value['restaurant_name'];
			$condition1['business_id'] = session('business_id');
			$condition1['status'] = 1;
			$Arrlist2 = $restaurant->where($condition1)->distinct(true)->field('city3,restaurant_name')->select();
			$cityModel = D('region');
			foreach($Arrlist2 as $key2=>$value2){
				$c_condition['id'] = $value2['city3'];
				$cityName = $cityModel->where($c_condition)->field("name")->find()['name'];
				$condition2['restaurant_name'] = $value2['restaurant_name'];
				$condition2['city3'] = $value2['city3'];
				$condition2['business_id'] = session('business_id');
				$condition2['status'] = 1;
				$Arrlist3 = $restaurant->where($condition2)->select();
				$Arrlist4 = array();
				foreach($Arrlist3 as $key3=>$vv){
					$Arrlist4[$key3]['address'] = $vv["address"];
					$Arrlist4[$key3]['id'] = $vv["restaurant_id"];
				}
				$Arrlist1[$cityName] = $Arrlist4;	
			}	
			$Arrlist[$key]['CityArray'] = $Arrlist1;				
		}
		$this->assign("Arrlist",$Arrlist);
		
		$where['business_id'] = session('business_id');
		$code = D('code');
		$RuseltArr = $code->where($where)->select();
		$RuseltArr3 = array();
		$device = D('device');
		foreach($RuseltArr as $vvalue){
			$condition3['code_id'] = $vvalue['code_id'];
			$RusultArr2 = $device->where($condition3)->find();
			if($RusultArr2){
				$RuseltArr3[] = $RusultArr2;
			}
		}
		$this->assign("Arrlist2",$RuseltArr3);	
		$this->display('deviceAjaxleft');
	}

	
	//更改机器（设备）绑定的店铺
	public function changeBindRes(){
		//获取注册码id,要换绑的店铺id
		$code_id = I("post.code_id");
		$restaurant_id = I("post.restaurant_id");

		$codeModel = D('code');

		$where['code_id'] = $code_id;
		$data['restaurant_id'] = $restaurant_id;

		$rel = $codeModel->where($where)->save($data);

		if($rel !== false){
			$msg['resultCode'] = 1;
			$msg['msg'] = "操作成功";
			exit(json_encode($msg));
		}else{
			$msg['resultCode'] = 0;
			$msg['msg'] = "操作失败";
			exit(json_encode($msg));
		}
	}
}
