<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28
 * Time: 9:09
 */

namespace Business\Controller;
use Think\Controller;

class CodeController extends Controller
{
    public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }

    //商家注册码列表
    public function codeList(){
        $codeModel = D("code");
        $condition['business_id'] = session('business_id');
        $codeList = $codeModel->where($condition)->select();
        $this->assign('codeList',$codeList);

        $restaurantModel = D("restaurant");
        $restaurantList = $restaurantModel->where($condition)->select();
        $this->assign("restaurantList",$restaurantList);

//        var_dump($codeList);
//        var_dump($restaurantList);
		
        $this->display();
    }

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