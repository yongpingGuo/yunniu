<?php

namespace Api\Controller;
use Think\Controller;

class GetTokenController extends Controller
{
    // 换取token
    public function get_token(){
//        $device_code = I("post.device_code");
        $device_code = I("device_code");
        if(empty($device_code)){
            $return['code'] = 0;
            $return['msg'] = "请提交设备码";
            $return['token'] = "";
            exit(json_encode($return));
        }else{
            $deviceModel = D("device");
            $d_condition['device_code'] = $device_code;
            $deviceInfo = $deviceModel->where($d_condition)->field("code_id,device_status")->find();
            $code_id = $deviceInfo['code_id'];
            if(!$code_id){
                $return['code'] = 0;
                $return['msg'] = "注册码过期或者已经删除";
                $return['token'] = "";
                exit(json_encode($return));
            }
            $device_status = $deviceInfo['device_status'];
            if(!$device_status){
                $return['code'] = 0;
                $return['msg'] = "该机器已经被禁用";
                $return['token'] = "";
                exit(json_encode($return));
            }
            if($code_id){
                /**
                 * 机器码绑定的注册码存在，查看注册码的剩余时间是否大于0；
                 */
                $codeModel = D("code");
                $c_condition['code_id'] = $code_id;
                $codeInfo = $codeModel->where($c_condition)->find();
                $code_restTimestamp = $codeInfo['rest_timestamp'];
                $oldTime = $codeInfo['last_time'];
                if($codeInfo['last_time'] == 0){
                    $oldTime = time();
                }
                $currentTime = time();
                session('login_time',$currentTime);
                $code_restTimestamp = $code_restTimestamp-($currentTime-$oldTime);
                $c_data['rest_timestamp'] = $code_restTimestamp;
                if($code_restTimestamp < 0){
                    $return['code'] = 0;
                    $return['msg'] = "激活码已过期";
                    $return['token'] = "";
                    exit(json_encode($return));
                }else{
                    $cc_data['last_time'] = time();
                    $rel = $codeModel->where($c_condition)->save($cc_data);
                }
            }else{
                $return['code'] = 0;
                $return['msg'] = "注册码过期或者已经删除";
                $return['token'] = "";
                exit(json_encode($return));
            }
        }

        // 生成token
//        $username = I("post.username");
        $username = I("username");
//        $password = I("post.password");
        $password = I("password");
        // 先判断传递过来的用户名和密码对应的信息是否存在
        // 注意，在后台添加用户名和密码时，不要添加重复的用户名和密码，不然条件就不唯一了
        $con['username'] = $username;
        $con['password'] = $password;
        $info = D("interface_login_check")->where($con)->find();
        if($info){
            // 创建token 每次请求都进行改变
            $str=substr($password,2,15);
            $time = substr(time(),4);
            $username = substr($username,1,6);
            $str.=$username.$time;
            $create_token = md5($str);
            $res = D("interface_login_check")->where($con)->save(array("token"=>$create_token));
            $return['code'] = 1;
            $return['msg'] = "换取token成功";
            $return['token'] = $create_token;
            exit(json_encode($return));
        }else{
            $return['code'] = 0;
            $return['msg'] = "用户名和密码不对";
            $return['token'] = "";
            exit(json_encode($return));
        }
    }
}