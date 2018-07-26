<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/16
 * Time: 16:09
 */
namespace ShowNumber\Service;

class ActivateService
{
    public function activate($register_code,$device_code,$device_name,$type){
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
                $c_restaurant_id = $codeInfo['restaurant_id'];
                if($c_restaurant_id == 0){
                    $msg["code"] = 0;
                    $msg["msg"] = "请为该注册码先绑定店铺，谢谢！";
                    exit(json_encode($msg));
                }

                //注册码可用，判断注册码是否绑定机器
                $deviceModel = D("equipment");
                $d_condition['code_id'] = $codeInfo['code_id'];
                $device = $deviceModel->where($d_condition)->field("equipment_code")->find();
                if($device && $device['equipment_code'] != $device_code){
                    $msg["code"] = 0;
                    $msg["msg"] = "此注册码已经绑定别的机器";
                    exit(json_encode($msg));
                }else{
                    //注册码没有绑定机器则添加一条记录，表示该机器添加成功
                    $code_id = $codeInfo['code_id'];
                    $data['code_id'] = $codeInfo['code_id'];
                    $data['equipment_code'] = $device_code;
                    $nowTime = time();
                    $data['Inital_time'] = date("Y-m-d H:i:s",$nowTime);
                    $data['Terminal_time'] = date("Y-m-d H:i:s",$nowTime+$codeInfo['rest_timestamp']);
                    $data['equipment_name'] = $device_name;
                    $data['restaurant_id'] = $codeInfo['restaurant_id'];
                    $data['equipment_type'] = $type;
                    $t_data['equipment_code'] = $device_code;
                    $rel = $deviceModel->where($t_data)->find();

                    $co_condition['code_id'] = $codeInfo['code_id'];
                    $co_data['code_status'] = 0;
                    $codeModel->where($co_condition)->save($co_data);

                    if($rel){
                        $result = $deviceModel->where($t_data)->save($data);
                    }else{
                        $result = $deviceModel->add($data);

                    }

                    $c_data["code_status"] = 0;
                    $codeModel->where("code_id = $code_id")->save($c_data);

                    if($result!==false){
                        if($rel !== false){
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