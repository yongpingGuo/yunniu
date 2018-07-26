<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/21
 * Time: 15:40
 */

namespace Business\Controller;
use Think\Controller;

class CountController extends Controller
{
    public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }

    public function index(){
        $condition['business_id'] = session('business_id');
        $condition['status'] = 1;
        $restaurantModel = D("restaurant");
        $restaurantList = $restaurantModel->where($condition)->select();
        $this->assign('restaurantList',$restaurantList);
        $this->display();
    }

    public function device(){
        $codeModel = D("code");
        $condition['business_id'] = session('business_id');
        $codeList = $codeModel->where($condition)->field('code_id,restaurant_id')->select();
        $restaurantModel = D('restaurant');
        $deviceModel = D('device');
        $deviceList = array();
        foreach($codeList as $key=>$val ){
            $d_condition['code_id'] = $val['code_id'];
            $device = $deviceModel->where($d_condition)->find();
            if($device){
                $r_condition['restaurant_id'] = $val['restaurant_id'];
                $device['restaurant_name'] = $restaurantModel->where($r_condition)->field('restaurant_name')->find()['restaurant_name'];
                $deviceList[] = $device;
            }
        }
//        dump($deviceList);
        $this->assign('deviceList',$deviceList);
        $this->display();
    }
}