<?php
namespace AllAgent\Controller;
use Think\Controller;
class AdminController extends Controller{
	public function __construct(){
        Controller::__construct();
        if(!session("manager_id")){
            $this->redirect("login");
        }
    }
	//管理员列表
	public function admin(){
		$manager = D('manager');
		$auth_group_access = D('auth_group_access');
		$auth_group = D('auth_group');
		$Auth = new \Think\Auth();
		$ruleName = MODULE_NAME . '/' . ACTION_NAME; //规则唯一标识,取当前的控制器:Admin/index
		if(action_AuthCheck($ruleName)){
			$p = I('page')?I('page'):1;
			$count = $manager->count();
			$pageNum = 12;
			$Page = new \Think\Page($count,$pageNum);	
			$Arrlist = $manager->page($p,$pageNum)->select();
			foreach($Arrlist as $key=>$value){
				$condition['uid'] = $value['manager_id'];
				$group_id = $auth_group_access->where($condition)->field('group_id')->find()['group_id'];
				$Arrlist[$key]['title'] = $auth_group->where("id=$group_id")->field('title')->find()['title'];
			}
		
			$show = $Page->show();
		}else{
			$where['manager_id'] = session('manager_id');	
			$Arrlist = $manager->where($where)->select();	
			foreach($Arrlist as $key=>$value){
				$condition['uid'] = $value['manager_id'];
				$group_id = $auth_group_access->where($condition)->field('group_id')->find()['group_id'];
				$Arrlist[$key]['title'] = $auth_group->where("id=$group_id")->field('title')->find()['title'];
			}	
		}	
		$this->assign("Arrlist",$Arrlist);
		$this->assign("page",$show);
		
		$auth_group = D("auth_group");
		$resultArr = $auth_group->select();
		$this->assign("all_admingroup",$resultArr);
		$this->display();
	}
	
	//添加管理员
	public function add_admin(){
		$manager = D('manager');
		$manager->startTrans();
		$data['manager_account'] = I('post.manager_account');
		$data['manager_password'] = I('post.manager_password');
		$data['manager_phone'] = I('post.manager_phone');
		$data['manager_ps'] = I('post.manager_ps');
		$insert_id = $manager->add($data);
		if($insert_id){
			$auth_group_access = D('auth_group_access');
			$data1['uid'] = $insert_id;
			$data1['group_id'] = I('post.group_id');
			$insert_id1 = $auth_group_access->add($data1);
			if($insert_id1){
				$count = $manager->count();
				$page = ceil($count/12);
				$msg['msg'] = "新增管理员成功！";
				$msg['code'] = 1;
				$msg['page'] = $page;
				$manager->commit();
				exit(json_encode($msg));
				
			}else{
				$msg['msg'] = "新增管理员失败！";
				$msg['code'] = 0;
				$manager->rollback();
				exit(json_encode($msg));
			}		
		}else{	
			$msg['msg'] = "新增管理员失败！";
			$msg['code'] = 0;
			$manager->rollback();
			exit(json_encode($msg));
		}			
	}
	
	//删除管理员
	public function del_admin(){
		$Auth = new \Think\Auth();
		$ruleName = MODULE_NAME . '/' . ACTION_NAME; //规则唯一标识,取当前的控制器:Admin/index
		if(action_AuthCheck($ruleName)){
			$manager = D('manager');
			$manager->startTrans();
			$condition['manager_id'] = I('get.manager_id');
			$r = $manager->where($condition)->delete();
			if($r){
				$auth_group_access = D('auth_group_access');
				$condition1['uid'] = I('get.manager_id');
				$r1 = $auth_group_access->where($condition1)->delete();
				if($r1){
					$count = $manager->count();
					$page = ceil($count/12);
					$msg['msg'] = "删除管理员成功!";
					$msg['code'] = 1;
					$msg['page'] = $page;
					$manager->commit();
				}else{
					$msg['msg'] = "删除管理员失败!";
					$msg['code'] = 0;
					$manager->rollback();
				}
				exit(json_encode($msg));
			}else{
				$msg['msg'] = "删除管理员失败!";
				$msg['code'] = 0;
				$manager->rollback();
				exit(json_encode($msg));
			}
		}else{
			$msg['msg'] = "抱歉，您的权限不够，无法进行操作!";
			$msg['code'] = 2;
			exit(json_encode($msg));
		}
	}
	
	//编辑前的填充
	public function modify_admin(){
		$manager = D('manager');
		$condition['manager_id'] = I('get.manager_id');
		$Object = $manager->where($condition)->find();
		$auth_group_access = D('auth_group_access');
		$condition1['uid'] = I('get.manager_id');
		$group = $auth_group_access->where($condition1)->find();
		$Object['group_id'] = $group['group_id'];
		$condition2['uid'] = session('manager_id');
		$Object['session_group_id'] = $auth_group_access->where($condition2)->find()['group_id'];
		exit(json_encode($Object));
	}
	
	//编辑管理员
	public function edit_admin(){
		$manager = D('manager');
		$data['manager_id'] = I('post.manager_id');
		$data['manager_account'] = I('post.manager_account');
		$data['manager_password'] = I('post.manager_password');
		$data['manager_phone'] = I('post.manager_phone');
		$data['manager_ps'] = I('post.manager_ps');
		$r = $manager->save($data);
		$auth_group_access = D('auth_group_access');
		$uid = I('post.manager_id');
		if(I('post.group_id') != 0){
			$data1['group_id'] = I('post.group_id');
			$r1 = $auth_group_access->where("uid=$uid")->save($data1);
			if($r != FALSE || $r1 != FALSE){
				$msg['msg'] = "编辑管理员成功！";
				$msg['code'] = 1;
				exit(json_encode($msg));
			}
		}else{
			if($r != FALSE){
				$msg['msg'] = "编辑管理员成功！";
				$msg['code'] = 1;
				exit(json_encode($msg));
			}
		}
	}
}
