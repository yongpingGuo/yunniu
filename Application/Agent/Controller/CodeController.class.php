<?php
namespace Agent\Controller;
use Think\Controller;
class CodeController extends Controller{
	public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }
	
	public function codeList(){
        $codeModel = D("code");
        $condition['business_id'] = session('business_id');
		$condition['code_status'] = 1;
		$p = I("get.page");
		$count = $codeModel->where($condition)->count();
		$pageNum = 15;
		$Page = new \Think\Page($count,$pageNum);
		$show = $Page->show();
		$this->assign("page",$show);
        $codeList = $codeModel->page($p,$pageNum)->where($condition)->select();
		$now = time();
		foreach($codeList as $key => $val){
			if($val['last_time'] == 0){
				$codeList[$key]['code_timestamp'] = date("Y-m-d H:i:s",$now);
				$codeList[$key]['rest_timestamp'] = date("Y-m-d H:i:s",$now+$codeList['rest_timestamp']);
			}
		}	
        $this->assign('codeList',$codeList);
        $restaurantModel = D("Restaurant");
		$condition['status'] = array('neq',0);
        $restaurantList = $restaurantModel->where($condition)->select();
        $this->assign("restaurantList",$restaurantList);
        $this->display('codeList');
    }
	
	//设备注册码
	public function changeCodeRestaurant(){
        $restaurant_id = I("restaurant_id");
        $code_id = I("code_id");

        $data['restaurant_id'] = $restaurant_id;
        $data['code_id'] = $code_id;

        $codeModel = D("code");
        $result = $codeModel->save($data);

        if($result !== false){
            $msg['code'] = 1;
            $msg['msg'] = "换绑成功";
            $this->ajaxReturn($msg);
            exit;
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "换绑失败";
            $this->ajaxReturn($msg);
            exit;
        }
    }
}
