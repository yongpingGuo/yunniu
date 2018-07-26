<?php
namespace AllAgent\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function _empty(){
        redirect("/index.php/AllAgent/Index/login");
    }
    public function index(){
    	if(!session("manager_id")){
    		$this->redirect("login");
    	}
        $this->display();
    }
	
//-------------------------------------------总后台帐号操作--------------------------------------------	
	public function verifyImg(){
		$config = array( 
			'imageW' => 160,
			'imageH' => 41,
			'fontSize'    =>    20,    // 验证码字体大小 
			'length'      =>    4,     // 验证码位数 
			'useNoise'    =>    false, // 关闭验证码杂点
			'fontttf' => '4.ttf', 
		);
		$Verify =     new \Think\Verify($config);
		$Verify->entry();
	}
	
	public function login(){
		$this->display();
	}
	
	//登录校验
	public function checklogin(){
		if(!empty($_GET)){
			$verify = new \Think\Verify();
			$Vresult = $verify->check($_GET['code']);
			if($Vresult){
				$manager = D('manager');
				$condition['manager_account'] = I('get.username');
				$result = $manager->where($condition)->find();
				if($result){
					if($result['manager_password'] == I('get.pwd')){
						session('manager_account',$result['manager_account']);
						session('manager_id',$result['manager_id']);
						$msg['msg'] = "登录成功!";
						$msg['code'] = 0;
					}else{
						$msg['msg'] = "用户名或者密码有误!";
						$msg['code'] = 1;
					}
				}else{
					$msg['msg'] = "用户名或者密码有误!";
					$msg['code'] = 2;
				}
			}else{
				$msg['msg'] = "验证码有错误!";
				$msg['code'] = 3;
			}
		}else{
			$msg['msg'] = "输入错误!";
			$msg['code'] = 4;
		}
		exit(json_encode($msg));
	}
	
	//退出登录
	public function loginout(){
		session('manager_id',null);
		$msg['msg'] = "退出成功";
		$msg['code'] = 1;
		exit(json_encode($msg));
	}
	
	//帐号编辑前填充
	public function account_edit(){
		$manager = D('manager');
		$condition['manager_id'] = I('get.manager_id');
		$object = $manager->where($condition)->find();
		$this->ajaxReturn($object);	
	}
	
	//帐号编辑
	public function update_account(){
		$manager = D('manager');
		$data['manager_id'] = I('post.manager_id');
		//$data['manager_account'] = I('post.manager_account');
		$data['manager_password'] = I('post.manager_password');
		$r = $manager->save($data);
		if($r){
			session('manager_account',I('post.manager_account'));
			$msg['msg'] = "编辑成功";
			$msg['code'] = 1;
			$msg['data'] = I('post.manager_account');
		}else{
			$msg['msg'] = "编辑失败";
			$msg['code'] = 0;
		}	
		$this->ajaxReturn($msg);
	}
}