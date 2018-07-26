<?php
namespace AllAgent\Controller;
use Think\Controller;
class AgentController extends Controller{
	public function __construct(){
        Controller::__construct();
        if(!session("manager_id")){
            $this->redirect("login");
        }
    }
	//代理页面
	public function agent(){
		$business = D('business');
		$key1 = I('get.business_account');
		$key2 = I('get.business_name');	
		$p = I("param.page")?I('param.page'):1;
		$pageNum = 10;
		if(empty($key1) && empty($key2)){
			$count = $business->count();
			$Page = new \Think\Page($count,$pageNum);
			$show = $Page->show();
			$this->assign('page',$show);
			$Arrlist = $business->page($p,$pageNum)->select();
		}else{
			$where['business_account'] = array("like","%".$key1."%");
			$where['business_name'] = array("like","%".$key2."%");
			$count = $business->where($where)->count();
			$parameter['business_account'] = I('get.business_account');
			$parameter['business_name'] = I('get.business_name');
			$Page = new \Think\Page($count,$pageNum,$parameter);
			$show = $Page->show();
			$this->assign('page',$show);
			$Arrlist = $business->where($where)->page($p,$pageNum)->select();
		}
		$this->assign("Arrlist",$Arrlist);		
        $this->display();
    }

	//新增代理商
	public function add_business(){
		$business = D('business');
		$condition['business_name'] = I('post.business_name');
		$condition['business_account'] = I('post.business_account');
		$condition['business_password'] = I('post.business_password');
		$condition['corporate_name'] = I('post.corporate_name');
		$condition['business_grade'] = I('post.business_grade');
		$condition['business_phone'] = I('post.business_phone');
		$condition['business_contact'] = I('post.business_contact');
		$condition['business_ps'] = I('post.business_ps');
		$r = $business->add($condition);
		if($r){	
			$pageNum = 10;
			$count = $business->count();
			$page = ceil($count/$pageNum);
			$msg['msg'] = "新增成功！";
			$msg['code'] = 1;
			$msg['page'] = $page;		
		}else{
			$msg['msg'] = "新增失败！";
			$msg['code'] = 0;
		}
		exit(json_encode($msg));
	}
	
	//编辑前的填充
	public function modify_business(){
		$business = D('business');
		$condition['business_id'] = I('get.id');
		$object = $business->where($condition)->find();
		exit(json_encode($object));
	}
	
	//编辑代理商
	public function update_business(){
		$business = D('business');
		$condition['business_id'] = I('post.business_id');
		$condition['business_name'] = I('post.business_name');
		$condition['business_account'] = I('post.business_account');
		$condition['business_password'] = I('post.business_password');
		$condition['corporate_name'] = I('post.corporate_name');
		$condition['business_grade'] = I('post.business_grade');
		$condition['business_phone'] = I('post.business_phone');
		$condition['business_contact'] = I('post.business_contact');
		$condition['business_ps'] = I('post.business_ps');
		$r = $business->save($condition);
		if($r){
			$msg['msg'] = "编辑成功！";
			$msg['code'] = 1;
		}else{
			$msg['msg'] = "编辑失败！";
			$msg['code'] = 0;
		}
		exit(json_encode($msg));
	}
	
	//删除代理商
	public function del_business(){
		$Auth = new \Think\Auth();
		$ruleName = MODULE_NAME . '/' . ACTION_NAME; //规则唯一标识,取当前的控制器:Admin/index
		if(action_AuthCheck($ruleName)){
			$business = D('business');
			$condition['business_id'] = I('get.id');
			$r = $business->where($condition)->delete();
			if($r){
				//$this->agent_ajax();
				$pageNum = 10;
				$count = $business->count();
				$page = ceil($count/$pageNum);
				$msg['msg'] = "删除成功！";
				$msg['code'] = 1;
				$msg['page'] = $page;
			}else{
				$msg['msg'] = "删除失败！";
				$msg['code'] = 0;
			}
			exit(json_encode($msg));
		}else{
			$msg['msg'] = "抱歉，您的权限不够，无法进行此操作！";
			$msg['code'] = 2;
			exit(json_encode($msg));
		}
	}

	//获取当前登录品牌商的管理员等级
	public function getManagerRank(){
		$manager_id = I('get.manager_id');
		$auth_group_access = D('auth_group_access');
		$condition['uid'] = $manager_id;
		$group_id = $auth_group_access->where($condition)->field('group_id')->find()['group_id'];
		$msg['msg'] = "获取当前管理员等级";
		$msg['rank'] = $group_id;
		exit(json_encode($msg));
	}
}
