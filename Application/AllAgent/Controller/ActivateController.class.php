<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/29
 * Time: 9:15
 */

namespace AllAgent\Controller;
use Think\Controller;
use Think\jpush;

class ActivateController extends Controller
{
    // 手机扫了安卓提供的二维码后，手机界面显示的激活界面
    public function activate(){
        $device_code = I("device_code");
        if($device_code == null){
            $this->display('error');
        }else{
            $this->assign("device",$device_code);
            $this->display();
        }
    }

    // 手机输入激活信息后，进行动态绑定的处理
    public function isActivate(){
        $register_code = I("register_code");
        $device_code = I("device_code");
        $device_name = I("device_name");
        if($device_code == null){
            $msg["code"] = 0;
            $msg["msg"] = "设备码不能为空！";
            exit(json_encode($msg));
        }
        if($register_code == null){
            $msg["code"] = 0;
            $msg["msg"] = "注册码不能为空！";
            exit(json_encode($msg));
        }

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
                $deviceModel = D("device");
                $d_condition['code_id'] = $codeInfo['code_id'];
                $device = $deviceModel->where($d_condition)->field("device_code")->find();
                if($device && $device['device_code'] != $device_code){
                    $msg["code"] = 0;
                    $msg["msg"] = "此注册码已经绑定别的机器";
                    exit(json_encode($msg));
                }else{
                    //注册码没有绑定机器则添加一条记录，表示该机器添加成功
                    $code_id = $codeInfo['code_id'];
                    $data['code_id'] = $codeInfo['code_id'];
                    $data['device_code'] = $device_code;
                    $nowTime = time();
                    $data['start_time'] = $nowTime;
                    //$data['end_time'] = $nowTime+$codeInfo['rest_timestamp'];
                    $data['end_time'] = 1546151279;
                    $data['device_name'] = $device_name;
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

                    $c_data["code_status"] = 0;
                    $codeModel->where("code_id = $code_id")->save($c_data);

                    if($result!==false){
						//var_dump($rel);
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

    public function qrcActivate(){
        $device_code = I("device_code");
        $this->assign("device",$device_code);
        $this->display();
    }

    public function isQrcActivate(){
        $register_code = I("register_code");
        $device_code = I("device_code");
        $device_name = I("device_name");

        /**
         * 1、获取用户的注册码和机器码
         * 2、对数据库进行比对，有则更新，没有则添加
         */
        //首先查看注册码是否存在
        $codeModel = D("qrc_code");
        $c_condition['qrc_code'] = $register_code;
        $codeInfo = $codeModel->where($c_condition)->find();

        //如果注册码存在，则查看注册码是否可用
        if($codeInfo){
            $code_status = $codeInfo['qrc_code_status'];

            if($code_status == 1){
                $c_restaurant_id = $codeInfo['restaurant_id'];
                if($c_restaurant_id == 0){
                    $msg["code"] = 0;
                    $msg["msg"] = "请为该注册码先绑定店铺，谢谢！";
                    exit(json_encode($msg));
                }

                $qrc_condition['restaurant_id'] = $c_restaurant_id;
                $code_num = $codeModel->where($qrc_condition)->count();
                if($code_num > 1){
                    $msg["code"] = 0;
                    $msg["msg"] = "一个店铺只能有一台微信点餐机";
                    exit(json_encode($msg));
                }

                //注册码可用，判断注册码是否绑定机器
                $deviceModel = D("qrc_device");
                $d_condition['qrc_code_id'] = $codeInfo['qrc_code_id'];
                $device = $deviceModel->where($d_condition)->field("qrc_device_code")->find();
                if($device && $device['qrc_device_code'] != $device_code){
                    $msg["code"] = 0;
                    $msg["msg"] = "此注册码已经绑定别的机器";
                    exit(json_encode($msg));
                }else{
                    //注册码没有绑定机器，则添加一条记录，表示该机器添加成功

                    $code_id = $codeInfo['qrc_code_id'];
                    $data['qrc_code_id'] = $codeInfo['qrc_code_id'];
                    $data['qrc_device_code'] = $device_code;
                    $nowTime = time();
                    $data['start_time'] = $nowTime;
                    //$data['end_time'] = $nowTime+$codeInfo['qrc_rest_timestamp'];
                    $data['end_time'] = 1546151279;
                    $t_data['qrc_device_code'] = $device_code;
                    $data['qrc_device_name'] = $device_name;
                    $rel = $deviceModel->where($t_data)->find();

                    $co_condition['qrc_code_id'] = $codeInfo['qrc_code_id'];
                    $co_data['qrc_code_status'] = 0;

                    $codeModel->where($co_condition)->save($co_data);

                    if($rel){
                        $result = $deviceModel->where($t_data)->save($data);
                    }else{
                        $result = $deviceModel->add($data);
                    }

                    $c_data["qrc_code_status"] = 0;
                    $codeModel->where("qrc_code_id = $code_id")->save($c_data);

                    if($result!==false){
                        //生成餐桌二维码
                        Vendor('phpqrcode.phpqrcode');

                        $desk_code_list = array("A01-1","A01-2");

                        foreach($desk_code_list as $desk_code){
                            $url = "http://shop.founya.com/index.php/mobile/index/index/restaurant_id/".$c_restaurant_id."/desk_code/"."$desk_code";
                            $errorCorrectionLevel =intval(3) ;//容错级别
                            $matrixPointSize = intval(4);//生成图片大小

                            //生成二维码图片
                            //echo $_SERVER['REQUEST_URI'];
                            $object = new \QRcode();
                            $date = date("Y-m-d/",time());
                            $date2 = date("His",time());
                            $path = "./Application/Admin/Uploads/qrcode/".$date;
                            if(!is_readable($path)){
                                is_file($path) or mkdir($path,0700);
                            }

                            //url要关联desk_id，方便修改。
                            $img_path = $path.$date2.".png";
                            $object->png($url,$img_path, $errorCorrectionLevel, $matrixPointSize, 2);

                            //构造餐桌资料添加进数据库
                            $qrc_data['desk_code'] = $desk_code;
                            $qrc_data['restaurant_id'] = $c_restaurant_id;
                            $qrc_data['code_img'] = "/Application/Admin/Uploads/qrcode/".$date.$date2.".png";
                            $qrc_data['qrcode_url'] = "/Application/Admin/Uploads/qrcode/".$date.$date2.".png";
                            $desk_model = D('desk');

                            $result = $desk_model->data($qrc_data)->add();
                        }

                        $msg2["code"] = 1;
                        $msg2["msg"] = "注册成功";
                        exit(json_encode($msg2));
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

    public function createQrcode($info,$action){
        $qrInfo = "http://shop.founpad.com/index.php/allAgent/activate/$action/device_code/".$info;
        //生成二维码图片并直接输出
        Vendor('phpqrcode.phpqrcode');

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(14);//生成图片大小

        $object = new \QRcode();
        ob_clean();
        $object->png($qrInfo,false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    //餐桌二维码激活
    public function showQrcode(){
        $device_code = I("device_code");
        $info = $device_code;
        $this->assign("info",$info);
        $this->display();
    }

    //点餐机激活
    public function showDQrcode(){
        $device_code = I("device_code");
        $info = $device_code;
        $this->assign("info",$info);
        $this->display();
    }

    public function qrcActivateStatus(){
        I("activate_type")== 1 ? $prefix = "" : $prefix = "qrc_";

        $device_code = I("device_code");

        $deviceModel = D($prefix."device");

        $de_condition[$prefix.'device_code'] = $device_code;
        $rel = $deviceModel->where($de_condition)->find();

        if($rel){
            $qrc_code_id = $rel[$prefix.'code_id'];
            $qrc_code_model = D($prefix."code");
            $qrc_condition[$prefix.'code_id'] = $qrc_code_id;
            $qrc_code_info = $qrc_code_model->where($qrc_condition)->find();
            if($qrc_code_info){
                $qrc_rest_timestamp = $qrc_code_info[$prefix."rest_timestamp"];
                if($qrc_rest_timestamp > 0){
                    $msg['code'] = 1;
                    $msg['mag'] = "激活成功";
                    exit(json_encode($msg));
                }
            }else{
                $msg['code'] = 0;
                $msg['mag'] = "激活失败";
                exit(json_encode($msg));
            }
        }else{
            $msg['code'] = 0;
            $msg['mag'] = "激活失败";
            exit(json_encode($msg));
        }
    }

    public function advertiseActive(){
        $device_code = I("device_code");
        $this->assign("device",$device_code);
        $this->display();
    }

    public function isAdvertiseActive(){
        $register_code = I("register_code");
        $device_code = I("device_code");
        $device_name = I("device_name");
        $screen_type = I("screen_type");

        /**
         * 1、获取用户的注册码和机器码
         * 2、对数据库进行比对，有则更新，没有则添加
         */
        //首先查看注册码是否存在
        $codeModel = D("code");
        $c_condition['code'] = $register_code;
        $codeInfo = $codeModel->where($c_condition)->find();

        if($device_code){
            $bill_board_model = D("bill_board");
            $where['bill_board_code'] = $device_code;
            $rel = $bill_board_model->where($where)->find();
            if(!empty($rel)){
                $returnData['code'] = 0;
                $returnData['msg'] = "该设备已经激活";
                exit(json_encode($returnData));
            }
        }

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
                $deviceModel = D("device");
                $d_condition['code_id'] = $codeInfo['code_id'];
                $device = $deviceModel->where($d_condition)->field("device_code")->find();
                if($device && $device['device_code'] != $device_code){
                    $msg["code"] = 0;
                    $msg["msg"] = "此注册码已经绑定别的机器";
                    exit(json_encode($msg));
                }else{
                    //注册码没有绑定机器则添加一条记录，表示该机器添加成功
                    $data['bill_board_code'] = $device_code;
                    $data['restaurant_id'] = $c_restaurant_id;
                    $data['bill_board_status'] = 1;
                    $nowTime = time();
                    $data['bb_start_time'] = $nowTime;
                    //$data['bb_end_time'] = $nowTime+$codeInfo['rest_timestamp'];
                    $data['bb_end_time'] = 1546151279;

                    $data['is_active'] = 1;
                    $data['bill_board_name'] = $device_name;
                    $data['$screen_type'] = $screen_type;

                    $bill_board_model = D("bill_board");
                    $add_rel = $bill_board_model->add($data);

                    $code_id = $codeInfo['code_id'];
                    $rel = $codeModel->delete($code_id);
                    if($add_rel !== false){
                        //var_dump($rel);
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