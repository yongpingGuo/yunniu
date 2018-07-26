<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28
 * Time: 9:09
 */

namespace Agent\Controller;
use Think\Controller;

class DeskCodeController extends Controller
{
    public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }

    //商家注册码列表
    public function deskCode(){
        $codeModel = D("qrc_code");
        $condition['business_id'] = session('business_id');
		$condition['qrc_code_status'] = 1;
		$p = I('get.page');
		$pageNum = 15;
		$count = $codeModel->where($condition)->count();
		$Page = new \Think\Page($count,$pageNum);
		$show = $Page->show();
		$this->assign("page",$show);
        $codeList = $codeModel->page($p,$pageNum)->where($condition)->select();
        $this->assign('codeList',$codeList);

        $restaurantModel = D("Restaurant");
		$condition1['business_id'] = session('business_id');
		$condition1['status'] = 1;
        $restaurantList = $restaurantModel->where($condition1)->select();
        $this->assign("restaurantList",$restaurantList);
        $this->display('deskCode');
    }
	
		

	//餐桌注册码
    public function changeCodeRestaurant1(){
        $restaurant_id = I("restaurant_id");
        $code_id = I("code_id");
        $codeModel = D("qrc_code");
		$data['business_id'] = session('business_id');
        $data['restaurant_id'] = $restaurant_id;
        //判断店铺已经绑定过是否，有则提示绑定失败，退出；
        $co_num = $codeModel->where($data)->count();
        if($co_num >= 1 && $restaurant_id != 0){
            $msg['code'] = 0;
            $msg['msg'] = "换绑失败,该店铺已绑定";
            $this->ajaxReturn($msg);
            exit;
        }

        $data['qrc_code_id'] = $code_id;
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