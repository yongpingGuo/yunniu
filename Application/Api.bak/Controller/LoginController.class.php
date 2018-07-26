<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/3
 * Time: 9:24
 */

namespace Api\Controller;

class LoginController extends BaseController
{
    /*public function __construct()
    {
        $token = I("post.token");
        $condition['token'] = $token;
        $info = D("interface_login_check")->where($condition)->find();

        if(!$info){
            $returnData['code'] = "0";
            $returnData['msg'] = "非法访问";
            exit(json_encode($returnData));
        }
    }*/

    //收银员登陆接口
    public function cashierLogin(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_id = session("restaurant_id");
            $cashier_model = D('cashier');
            $cashier_phone = I("post.cashier_phone");
            $cashier_pwd = I("post.cashier_pwd");
            $cs_where['restaurant_id'] = $restaurant_id;
            $rel = $cashier_model->where($cs_where)->field("cashier_phone,cashier_pwd")->select();
            foreach($rel as $key => $val){
                if($val['cashier_phone'] == $cashier_phone && md5($val['cashier_pwd']) == $cashier_pwd){
                    $returnData['code'] = 1;
                    $returnData['msg'] = "登录成功";
                    exit(json_encode($returnData));
                }
            }
            $returnData['code'] = 0;
            $returnData['msg'] = "账号或密码不存在";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }

    //收银员账号信息
    public function getCashierInfo(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_id = session("restaurant_id");
            $cashier_model = D("cashier");
            $c_where['restaurant_id'] = $restaurant_id;
            $rel = $cashier_model->where($c_where)->field("cashier_phone,cashier_pwd,cashier_id,cashier_name")->select();
            if($rel){
                foreach($rel as $key => $val){
                    $rel[$key]['cashier_pwd'] = md5($val["cashier_pwd"]);
                }
                $returnData['code'] = 1;
                $returnData['msg'] = "获取成功";
                $returnData['data'] = $rel;
                exit(json_encode($returnData,false));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "用户信息为空";
                $returnData['data'] = "";
                exit(json_encode($returnData,false));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期";
            $returnData['data'] = "";
            exit(json_encode($returnData,false));
        }
    }
}