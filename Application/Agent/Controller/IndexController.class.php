<?php
namespace Agent\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function _empty(){
        redirect("/index.php/Admin/Index/login");
    }

    public function index(){
		if (!session("business_id")) {
			$this->redirect("login");
		}

		//根据商家的type去开关代理的公众号
		$map['business_id'] = $_SESSION['business_id'];
		$bus = M('business')->field('type,vip_mode')->where($map)->find();

		$this->assign('type',$bus['type']);
		$this->assign('vip_mode',$bus['vip_mode']);    // 会员模式
        $this->display();
    }

	//验证码
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
		ob_clean();
		$Verify->entry();
	}

	//登录界面
	public function login(){
		$this->display();
	}
	
	//登录校验
	public function checklogin(){
		if(!empty($_GET)){
			$verify = new \Think\Verify();
			$Vresult = $verify->check($_GET['code']);
			if($Vresult){
				$business = D('business');
				$condition['business_account'] = I('get.username');
				$result = $business->where($condition)->find();
				if($result){
					if($result['business_password'] == I('get.pwd')){
						session('business_name',$result['business_name']);
						session('business_account',$result['business_account']);
						session('business_id',$result['business_id']);
						//session('admin_pwd',$result['password']);
						//$msg['msg'] = "登录成功!";
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
		session('business_name',null);
		session('business_account',null);
		session('business_id',null);
		$msg['msg'] = "退出成功";
		$msg['code'] = 1;
		exit(json_encode($msg));
	}
	
	//帐号编辑前填充
	public function account_edit(){
		$business = D('business');
		$condition['business_id'] = I('get.business_id');
		$object = $business->where($condition)->find();
		$this->ajaxReturn($object);	
	}
	
	//帐号编辑
	public function update_account(){
		$business = D('business');
		$data['business_id'] = I('post.manager_id');
		//$data['business_account'] = I('post.manager_account');
		$data['business_password'] = I('post.manager_password');
		$r = $business->save($data);
		if($r){
			session('business_account',I('post.manager_account'));
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