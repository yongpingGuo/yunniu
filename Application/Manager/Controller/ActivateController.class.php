<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/29
 * Time: 9:15
 */

namespace Manager\Controller;
use Think\Controller;
use Think\jpush;

class ActivateController extends Controller
{
    //激活界面
    public function activate(){

        $device_code = I("device_code");
        $this->assign("device",$device_code);
        $this->display();
    }

    public function isActivate(){
        $register_code = I("register_code");
        $device_code = I("device_code");

        /**
         * 1、获取用户的注册码和机器码
         * 2、对数据库进行比对，有则更新，没有则添加
         */
        //首先查看注册码是否存在
        $codeModel = D("code");
        $c_condition['code'] = $register_code;
        $codeInfo = $codeModel->where($c_condition)->find();

        //如果注册码存在，则查看注册码是否可用
        if($codeInfo){
            $code_status = $codeInfo['code_status'];
            if($code_status == 1){
                //注册码可用，判断注册码是否绑定机器
                $deviceModel = D("device");
                $d_condition['code_id'] = $codeInfo['code_id'];
                $device = $deviceModel->where($d_condition)->field("device_code")->find();
                if($device && $device['device_code'] != $device_code){
                    $msg["code"] = 0;
                    $msg["msg"] = "此注册码已经绑定别的机器";
                    exit(json_encode($msg));
                }else{
                    //注册码没有绑定机器则添加一条记录，表示该机器添加成功
                    $data['code_id'] = $codeInfo['code_id'];
                    $data['device_code'] = $device_code;
                    $nowTime = time();
                    $data['start_time'] = $nowTime+$codeInfo['code_timestamp'];
                    $data['end_time'] = $nowTime+$codeInfo['rest_timestamp'];
                    $t_data['device_code'] = $device_code;
                    $rel = $deviceModel->where($t_data)->find();

                    $co_condition['code_id'] = $codeInfo['code_id'];
                    $co_data['code_status'] = 0;
                    $codeModel->where($co_condition)->save($co_data);

                    if($rel){
                        $result = $deviceModel->where($t_data)->save($data);
                    }else{
                        $result = $deviceModel->add($data);
                    }
                    
                    if($result!==false){
                        $msg["code"] = 1;
                        $msg["msg"] = "注册成功";
                        $msg['device_code'] = $device_code;
                        $msg['register_code'] = $register_code;
                        $msg['start_time'] =  $data['start_time'];
                        $msg['end_time'] = $data['end_time'];
                        $msg['url_l'] = "http://192.168.31.101/home/index/index/restaurant/".$codeInfo['restaurant_id'];
                        $msg['url_v'] = "http://192.168.31.101/home/index/index/restaurant/".$codeInfo['restaurant_id'];
                        $msg['status'] = 1;
                        $rel = sendInfo($msg,$device_code);
						//var_dump($rel);
                        if($rel){
                            $msg2["code"] = 1;
                            $msg2["msg"] = "注册成功";
                            exit(json_encode($msg2));
                        }else{
                            $msg2["code"] = 0;
                            $msg2["msg"] = "注册失败";
                            exit(json_encode($msg2));
                        }

                    }
                }
            }else{
                $msg3["code"] = 0;
                $msg3["msg"] = "注册码不可用";
                exit(json_encode($msg3));
            }
        }else{
            $msg4["code"] = 0;
            $msg4["msg"] = "注册码不存在";
            exit(json_encode($msg4));
        }
    }
}