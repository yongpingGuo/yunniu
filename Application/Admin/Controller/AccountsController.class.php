<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Page;
	
	class AccountsController extends Controller{

		public function __construct(){
			Controller::__construct();
			$admin_id = session("re_admin_id");
			if(!$admin_id){
				redirect("Index/login");
			}
			$restaurant_manager_model = D('restaurant_manager');
			$restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
			session('restaurant_id',$restaurant_id);
		}
		
		//收银员主页
		public function index(){
			$cashier = D('cashier');
			$where['restaurant_id'] = session('restaurant_id');
			$cashierArr = $cashier->where($where)->select();
			$this->assign("cashierArr",$cashierArr);
			$this->display();
		}
		
		//收银员主页操作后的ajax页
		public function AccountAjax(){
			$cashier = D('cashier');
			$where['restaurant_id'] = session('restaurant_id');
			$cashierArr = $cashier->where($where)->select();
			$this->assign("cashierArr",$cashierArr);
			$this->display('ajaxIndex');
		}
		
		
	//添加收银员
	public function Accountsadd(){
		$cashier = D('cashier');
		//帐号校验
		$data['cashier_name'] = $_POST['Cashier_name'];
		$data['cashier_pwd'] = $_POST['Cashier_pwd'];
		$data['cashier_phone'] = $_POST['Cashier_phone'];
		$data['cashier_sex'] = $_POST['Cashier_sex'];
		$data['restaurant_id'] = session('restaurant_id');
		$r = $cashier->add($data);
		if($r){
			$this->AccountAjax();
		}		
	}
		
	//删除
	public function Accountsdel(){
		$cashier = D('cashier');
		$where['cashier_id'] = I('post.Cashier_id');
		$r = $cashier->where($where)->delete();
		if($r){
			$this->AccountAjax();
		}
	}
		
	//编辑前填充
	public function Accountsmodify(){
		$cashier = D('cashier');
		$where['cashier_id'] = I('post.Cashier_id');
		$arr = $cashier->where($where)->find();
		exit(json_encode($arr));
	}
		
	//编辑
	public function Accountsupdata(){
		$cashier = D('cashier');
		$data['cashier_id'] = $_POST['Cashier_id'];
		$data['cashier_name'] = $_POST['Cashier_name'];
		$data['cashier_pwd'] = $_POST['Cashier_pwd'];
		//$data['cashier_phone'] = $_POST['Cashier_phone'];
		$data['cashier_sex'] = $_POST['Cashier_sex'];
		$data['restaurant_id'] = session('restaurant_id');
		$r = $cashier->save($data);
		if($r){
			$this->AccountAjax();
		}else{
			$this->AccountAjax();
		}
	}
		
	//模糊查询
	public function selectBykey(){
		$cashier = D('cashier');
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['cashier_name'] = array('like',"%".$_POST['key']."%");
		$cashierArr = $cashier->where($condition)->select();
		$this->assign("cashierArr",$cashierArr);
		$this->display('ajaxIndex');		
	}
		
		/*//分页
		public function deskInfo(){
			$cashier = D('cashier');
			$pp = I("get.page");
//			dump($pp);
			$condition['restaurant_id'] = session('restaurant_id');
			$p = I("get.page") ? I("get.page") : 1;
			$count = $cashier->count();
			$page = new \Think\PageAjax($count,5);
			$cashierinfo = $cashier->where($condition)->page($p,5)->select();
			$this->assign('info',$cashierinfo);
			$page2  = $page->show();
			$this->assign('page',$page2);
			if($pp == ""){
				$this->display('index');
			}else{
				$this->display('ajaxIndex');
			}
		}*/
	}
?>