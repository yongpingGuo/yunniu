<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/21
 * Time: 15:42
 */

namespace Business\Controller;
use Think\Controller;

class BusinessController extends Controller
{
    public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }

    public function index(){
        $condition['business_id'] = session('business_id');
        $businessModel = D('business');
        $businessInfo = $businessModel->where($condition)->find();
        $this->assign('businessInfo',$businessInfo);
        $this->display();
    }

    public function editBusinessInfo(){
        dump(I('post.'));
    }
}