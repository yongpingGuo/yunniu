<?php
namespace AllAgent\Controller;
use Think\Controller;
class DeviceController extends Controller{
	public function __construct(){
        Controller::__construct();
        if(!session("manager_id")){
            $this->redirect("login");
        }
    }
	//设备页面
	public function device(){
		$business = D('business');
		$businessArr = $business->field('business_id,business_name')->select();
		foreach($businessArr as $k=>$v){
		$restaurant = D('Restaurant');
		$condition['business_id'] = $v['business_id'];
		$condition['status'] = 1;
		$Arrlist = $restaurant->distinct(true)->field('restaurant_name')->where($condition)->select();
		//dump($Arrlist);
		foreach($Arrlist as $key=>$value){
			$Arrlist1 = array();
			$condition1['restaurant_name'] = $value['restaurant_name'];
			$condition1['business_id'] = $v['business_id'];
			$condition1['status'] = 1;
			$Arrlist2 = $restaurant->where($condition1)->distinct(true)->field('city3')->select();
			//dump($Arrlist2);
			$cityModel = D('region');
			$CityArr = array();
			foreach($Arrlist2 as $key2=>$value2){
				$c_condition['id'] = $value2['city3'];
				$cityName = $cityModel->where($c_condition)->field("name")->select();
				//$CityArr = array();
				foreach($cityName as $value3){
					$CityArr = $value3['name'];
				}
				$condition2['city3'] = $value2['city3'];
				$condition2['business_id'] = $v['business_id'];
				$condition2['restaurant_name'] = $value['restaurant_name'];
				$address = $restaurant->where($condition2)->field('address')->select();
				
				//dump($address);
				$Arrlist2[$key2]['cityArr'] = $CityArr;
				$Arrlist2[$key2]['addressArr'] = $address;
			}		
			$Arrlist[$key]['City1Array'] = $Arrlist2;		
		}
		$businessArr[$k]['restaurantNameArr'] = $Arrlist;
		}
		/*echo "<pre>";
		print_r($businessArr);
		echo "<pre>";*/
		
		$this->assign("Arrlist",$businessArr);
		$this->common1();
	
		$renew = D('renew');
		$renewArr = $renew->where("id=1")->find();
		$this->assign("renewArr",$renewArr);
		$this->display();	
	}
	
	//点击地区显示对应区域内店铺设备
	public function showInfobykey(){
		$restaurant = D('Restaurant');
		$condition['business_id'] = I('get.business_id');
		$condition['restaurant_name'] = I('get.restaurant_name');
		$condition['city3'] = I('get.cityid');
		$Arrlist = $restaurant->where($condition)->field('restaurant_id,address')->select();     //查询指定区域的店铺
		$code = D('code');
		$device = D('device');
		// $condition9['restaurant_id'] = session('restaurant_id');
		// $codeArr = $code->where($condition9)->select();
		$deviceArr1 = array();																	 //查询指定区域店铺的设备
		foreach($Arrlist as $k=>$v){
			$condition1['restaurant_id'] = $v['restaurant_id'];
			$Codelist = $code->where($condition1)->field('code_id,last_time')->select();
			foreach($Codelist as $k1=>$v1){
				$condition2['code_id'] = $v1['code_id'];
				$deviceArr = $device->where($condition2)->find();
				if($deviceArr){
					$deviceArr1[] = $deviceArr;	
				}	
			}		
		}	
		foreach($deviceArr1 as $k2=>$v2){														 //设备数组添加地址字段
			$condition3['code_id'] = $v2['code_id'];
			$restaurant_id = $code->where($condition3)->field('restaurant_id')->find()['restaurant_id'];
			$address = $restaurant->where("restaurant_id=$restaurant_id")->field('address')->find()['address'];
			// $last_time = $code->where("restaurant_id=$restaurant_id")->field('last_time')->find()['last_time'];
			$deviceArr1[$k2]['address'] = $address;
			// $deviceArr1[$k2]['last_time'] = date('Y-m-d H:i:s',$last_time);
		}	
		foreach ($deviceArr1 as $k => $v) {
            foreach ($Codelist as $key => $value) {
                if ($v['code_id'] == $value['code_id']) {
                    // $deviceArr1[$k]['end_time'] = date('Y-m-d',$v['end_time']);
                    $deviceArr1[$k]['last_time'] = date('Y-m-d H:i:s',$value['last_time']);
                }
            }
          
        }

		$this->assign('deviceArr',$deviceArr1);
		$this->display('deviceAjax');
	}

	
	public function device_ajax(){
		$device = D('device');
		$deviceArr = $device->select();
		$code = D('code');
		$restaurant = D('Restaurant');
		foreach($deviceArr as $key=>$value){
			$w['code_id'] = $value['code_id'];
			$restaurant_id = $code->where($w)->field('restaurant_id')->find()['restaurant_id'];
			$last_time = $code->where($w)->field('last_time')->find()['last_time'];
			$where['restaurant_id'] = $restaurant_id;
			$where['status'] = 1;
			$address = $restaurant->where($where)->field('address')->find()['address'];
			$deviceArr[$key]['address'] = $address;
			$deviceArr[$key]['last_time'] = date('Y-m-d H:i:s',$last_time);
		}

		$p = I("param.page") ? I("param.page") : 1;
		$pageNum = 15;
		$count=count($deviceArr);
        $Page= new \Think\PageAjax($count,$pageNum);
		$show = $Page->show();// 分页显示输出
        $list=array_slice($deviceArr,($p-1)*$pageNum,$pageNum);
		$this->assign('deviceArr',$list);// 
		$this->assign('page',$show);// 赋值分页输出
		$this->display('deviceAjax');
	}
	

	//设备显示所有公共方法(加载全部时遍历)
	public function common1(){
		$device = D('device');
		$deviceArr = $device->select();
		$code = D('code');
		$restaurant = D('Restaurant');
		foreach($deviceArr as $key=>$value){
			$w['code_id'] = $value['code_id'];
			$restaurant_id = $code->where($w)->field('restaurant_id')->find()['restaurant_id'];
			$where['restaurant_id'] = $restaurant_id;
			$where['status'] = 1;
			$address = $restaurant->where($where)->field('address')->find()['address'];
			$deviceArr[$key]['address'] = $address;
		}
		
		$count=count($deviceArr);
		$pageNum = 15;
        $Page= new \Think\PageAjax($count,$pageNum);
		$show = $Page->show();// 分页显示输出
        $list=array_slice($deviceArr,$Page->firstRow,$Page->listRows);
		$this->assign('deviceArr',$list);// 
		$this->assign('page',$show);// 赋值分页输出
	}

	//设备显示所有公共方法(点击地区分类加载时遍历)
	public function common2(){
		$restaurant = D('Restaurant');
		$condition['business_id'] = I('post.uuid');
		$condition['restaurant_name'] = I('post.uuid2');
		$condition['city3'] = I('post.uuid3');
		$Arrlist = $restaurant->where($condition)->field('restaurant_id,address')->select();     //查询指定区域的店铺
		$code = D('code');
		$device = D('device');
		$deviceArr1 = array();																	 //查询指定区域店铺的设备
		foreach($Arrlist as $k=>$v){
			$condition1['restaurant_id'] = $v['restaurant_id'];
			$Codelist = $code->where($condition1)->field('code_id')->select();
			foreach($Codelist as $k1=>$v1){
				$condition2['code_id'] = $v1['code_id'];
				$deviceArr = $device->where($condition2)->find();
				if($deviceArr){
					$deviceArr1[] = $deviceArr;	
				}	
			}		
		}	
		foreach($deviceArr1 as $k2=>$v2){														 //设备数组添加地址字段
			$condition3['code_id'] = $v2['code_id'];
			$restaurant_id = $code->where($condition3)->field('restaurant_id')->find()['restaurant_id'];
			$address = $restaurant->where("restaurant_id=$restaurant_id")->field('address')->find()['address'];
			$deviceArr1[$k2]['address'] = $address;
		}		
		$this->assign('deviceArr',$deviceArr1);
	}

	//编辑设备前的填充
	public function modify_device(){
		$device = D('device');
		$condition['device_id'] = I('get.device_id');
		$object = $device->where($condition)->find();
		$condition1['code_id'] = $object['code_id'];
		$code = D('code');
		$condition2['restaurant_id'] = $code->where($condition1)->field('restaurant_id')->find()['restaurant_id'];
		$restaurant = D('Restaurant');
		$address = $restaurant->where($condition2)->field('address')->find()['address'];
		$object['address'] = $address;
		$object['restaurant_id'] = $condition2['restaurant_id'];
		$object['start_time'] = date("Y-m-d",$object['start_time']);
		$object['end_time'] = date("Y-m-d",$object['end_time']);
		exit(json_encode($object));
	}

	//删除设备
	public function del_device(){
			$device = D('device');
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

			$business_id = I('post.business_id');
			if(!empty($business_id)){
				$this->common2();
			}else{
				$this->common1();
			}
			$this->display('deviceAjax');
		}
	}

	//编辑设备信息
	public function update_device(){
		$device = D('device');
		//$device->startTrans();
		$data['device_id'] = I('post.device_id');
		$data['device_name'] = I('post.device_name');	
		$data['device_status'] = I('post.state');
		$data['end_time'] = strtotime(I('post.end_time'));

		$device->save($data);
		$restaurant = D('Restaurant');
		$condition['restaurant_id'] = I('post.restaurant_id');
		$condition['address'] = I('post.address');

		//更改设备到期时间同时更改绑定该设备的注册码的时间
		$where['device_id'] = I("post.device_id");
		$deviceInfo = $device->where($where)->field("code_id,start_time")->find();
		$codeModel = D('code');
		$c_where['code_id'] = $deviceInfo['code_id'];
		$timestamp = $data['end_time']-$deviceInfo['start_time'];
		$c_data['code_timestamp'] = $timestamp;
		$c_data['rest_timestamp'] = $timestamp;
		$codeModel->where($c_where)->save($c_data);

		$restaurant->save($condition);
		
		$business_id = I('post.uuid');
		if(!empty($business_id)){
			$this->common2();
		}else{
			$this->common1();
		}
		$this->display('deviceAjax');			
	}
		
		
	//代理模糊查询
	public function showdevicebykey(){
		$key = I('get.key');
		$business = D('business');
		$where['business_name'] = array("like","%".$key."%");
		$businessArr = $business->where($where)->field('business_id,business_name')->select();   //根据条件查出代理的ID,名称
		foreach($businessArr as $k=>$v){
		$restaurant = D('Restaurant');
		$condition['business_id'] = $v['business_id'];
		$Arrlist = $restaurant->distinct(true)->field('restaurant_name')->where($condition)->select();	//查出该代理下的有店铺名称
		foreach($Arrlist as $key=>$value){
			$Arrlist1 = array();
			$condition1['restaurant_name'] = $value['restaurant_name'];
			$condition1['business_id'] = $v['business_id'];
			$Arrlist2 = $restaurant->where($condition1)->distinct(true)->field('city3')->select();		//查出每间店铺的所在地区
			$cityModel = D('region');
			$CityArr = array();
			foreach($Arrlist2 as $key2=>$value2){
				$c_condition['id'] = $value2['city3'];
				$cityName = $cityModel->where($c_condition)->field("name")->select();
				foreach($cityName as $value3){
					$CityArr = $value3['name'];
				}
				$condition2['city3'] = $value2['city3'];
				$condition2['business_id'] = $v['business_id'];
				$condition2['restaurant_name'] = $value['restaurant_name'];
				$address = $restaurant->where($condition2)->field('address')->select();
				$Arrlist2[$key2]['cityArr'] = $CityArr;
				$Arrlist2[$key2]['addressArr'] = $address;
			}		
			$Arrlist[$key]['City1Array'] = $Arrlist2;		
		}
		$businessArr[$k]['restaurantNameArr'] = $Arrlist;
		}
		
		$this->assign("Arrlist",$businessArr);
		//$this->common1();
		$this->display('deviceajaxleft');
	}

	//查据日期查询设备
	public function searchDevicebyTime(){
		$device_start_time = I('get.device_start_time');
		$device_end_time = I('get.device_end_time');
		$time1 = strtotime($device_start_time);
		$time2 = strtotime($device_end_time);
		$map['end_time'] = array("elt",$time2); //end_time>time1
		$map['start_time'] = array("egt",$time1);	//start_time<time2
		$device = D('device');
		$deviceArr = $device->where($map)->field('device_id,device_code,device_status,device_name,start_time,end_time,code_id')->select();
		$code = D('code');
		$restaurant = D('Restaurant');
		foreach($deviceArr as $key=>$value){
			$w['code_id'] = $value['code_id'];
			$restaurant_id = $code->where($w)->field('restaurant_id')->find()['restaurant_id'];
			$where['restaurant_id'] = $restaurant_id;
			$where['status'] = 1;
			$address = $restaurant->where($where)->field('address')->find()['address'];
			$deviceArr[$key]['address'] = $address;
		}
		$p = I("param.page") ? I("param.page") : 1;
		$pageNum = 15;
		$count=count($deviceArr);
        $Page= new \Think\PageAjax($count,$pageNum,$parameter);
		$show = $Page->show();// 分页显示输出
        $list=array_slice($deviceArr,($p-1)*$pageNum,$pageNum);
		$this->assign('deviceArr',$list);// 
		$this->assign('page',$show);// 赋值分页输出
		$this->display('deviceAjax');
	}
}
