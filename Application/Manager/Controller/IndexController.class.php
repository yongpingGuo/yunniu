<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/21
 * Time: 15:13
 */
namespace Manager\Controller;
use Think\Controller;
class IndexController extends Controller
{
   public function index(){
    	if(!session("admin_name")){
    		$this->redirect("login");
    	}
        $this->display();
    }
	
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
						session('admin_name',$result['manager_account']);
						session('admin_pwd',$result['manager_password']);
						$msg['msg'] = "登录成功!";
						$msg['code'] = 0;
					}else{
						$msg['msg'] = "密码有误!";
						$msg['code'] = 1;
					}
				}else{
					$msg['msg'] = "用户不存在!";
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
		session(null);
		$msg['msg'] = "退出成功";
		$msg['code'] = 1;
		exit(json_encode($msg));
	}
}