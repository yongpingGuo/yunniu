<?php
namespace Agent\Controller;
use Think\Controller;
class AdminController extends Controller{
	public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }
	public function admin(){
		$business = D('business');
		$manager_power = D('manager_power');
		$p = I("param.page");
		$pageNum = 8;
		$count = $business->count();
		$Page = new \Think\PageAjax($count,$pageNum);
		$show = $Page->show();
		$this->assign('page',$show);
		$Arrlist = $business->page($p,$pageNum)->select();
		//$Parlist = array();
		foreach($Arrlist as $k=>$v){
			$Parlist = explode(',',$v['business_power']);
			$condition['id'] = array('in',$Parlist);		
			$ResultArr = $manager_power->where($condition)->select();
			$businessArr = array();
			foreach($ResultArr as $v1){
				$businessArr[] = $v1['power_name'];
			}
			$Arrlist[$k]['power_name1'] = $businessArr;
			$Parlist1 = explode(',',$v['device_power']);
			$condition1['id'] = array('in',$Parlist1);
			$ResultArr1 = $manager_power->where($condition1)->select();
			$deviceArr = array();
			foreach($ResultArr1 as $v2){
				$deviceArr[] = $v2['power_name'];
			}
			$Arrlist[$k]['power_name2'] = $deviceArr;
			
			//
			$Parlist2 = explode(',',$v['admin_power']);		
			$condition2['id'] = array('in',$Parlist2);
			$ResultArr2 = $manager_power->where($condition2)->select();
			$adminArr = array();
			foreach($ResultArr2 as $v3){
				$adminArr[] = $v3['power_name'];
			}
			$Arrlist[$k]['power_name3'] = $adminArr;
		}
		//dump($Arrlist);
		$this->assign("Arrlist",$Arrlist);
		$this->display();
	}

	//管理员公共显示全部的方法
	public function common(){
		$business = D('business');
		$manager_power = D('manager_power');
		$p = I("param.page") ? I("param.page") : 1;
		$pageNum = 8;
		$count = $business->count();
		$Page = new \Think\PageAjax($count,$pageNum);
		$show = $Page->show();
		$this->assign('page',$show);
		$Arrlist = $business->page($p,$pageNum)->select();
		//$Parlist = array();
		foreach($Arrlist as $k=>$v){
			$Parlist = explode(',',$v['business_power']);
			$condition['id'] = array('in',$Parlist);		
			$ResultArr = $manager_power->where($condition)->select();
			$businessArr = array();
			foreach($ResultArr as $v1){
				$businessArr[] = $v1['power_name'];
			}
			$Arrlist[$k]['power_name1'] = $businessArr;
			//
			
			//
			$Parlist1 = explode(',',$v['device_power']);
			$condition1['id'] = array('in',$Parlist1);
			$ResultArr1 = $manager_power->where($condition1)->select();
			$deviceArr = array();
			foreach($ResultArr1 as $v2){
				$deviceArr[] = $v2['power_name'];
			}
			$Arrlist[$k]['power_name2'] = $deviceArr;
			
			//
			$Parlist2 = explode(',',$v['admin_power']);		
			$condition2['id'] = array('in',$Parlist2);
			$ResultArr2 = $manager_power->where($condition2)->select();
			$adminArr = array();
			foreach($ResultArr2 as $v3){
				$adminArr[] = $v3['power_name'];
			}
			$Arrlist[$k]['power_name3'] = $adminArr;
		}
		//dump($Arrlist);
		$this->assign("Arrlist",$Arrlist);
		$this->display('adminAjax');
	}
	
	//添加管理员
	public function add_admin(){	
		$business = D('business');
		//先查询数据库是否有此帐号，确保帐唯一性
		$where['business_account'] = I('post.manager_account');
		$r = $business->where($where)->find();
		if(!$r){
			$data['business_name'] = I('post.manager_name');
			$data['business_account'] = I('post.manager_account');
			$data['business_password'] = I('post.manager_password');
			$data['business_phone'] = I('post.manager_phone');
			$data['business_ps'] = I('post.manager_ps');		
			$data['business_power'] = I('post.text');
			$data['device_power'] = I('post.text1');
			$data['admin_power'] = I('post.text2');
			$result = $business->add($data);
			if($result){
				$this->common();
			}else{
				$code = 2;
				echo $code;
			}
		}else{
			$code = 1;
			echo $code;
		}
	}
	
	//删除管理员
	public function del_admin(){
		$business = D('business');
		$condition['business_id'] = I('get.manager_id');
		$r = $business->where($condition)->delete();
		if($r){
			$this->common();
		}
	}
	
	//编辑前的填充
	public function modify_admin(){
		$business = D('business');
		$condition['business_id'] = I('get.manager_id');
		$Object = $business->where($condition)->find();
		exit(json_encode($Object));
	}
	
	//编辑管理员
	public function edit_admin(){
		$business = D('business');
		$data['business_id'] = I('post.manager_id');
		$data['business_name'] = I('post.manager_name');
		$data['business_account'] = I('post.manager_account');
		$data['business_password'] = I('post.manager_password');
		$data['business_phone'] = I('post.manager_phone');
		$data['business_ps'] = I('post.manager_ps');
		$data['business_power'] = I('post.text');
		$data['device_power'] = I('post.text1');
		$data['admin_power'] = I('post.text2');
		$r = $business->save($data);
		if($r){
			$this->common();
		}
	}
}
