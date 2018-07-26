<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/2
 * Time: 17:31
 */
namespace Api\Controller;
use Think\Controller;

class BaseController extends Controller
{
   /* public function __construct(){
        $device_code = I("post.device_code");
        if($this->isLogin($device_code) == false){
            echo json_encode(array('code'=>0,'msg'=>"该设备已过期，没有权限拿数据"));
            exit;
        }
    }*/
    public $is_security = false;

    //盘点该机器是否可�?
    public function isLogin($device_code){
        /**
         * 如果机器码不存在，则提示非法访问
         */
        if($device_code == false){
            return $this->is_security = false;
        }else{
            $deviceModel = D("device");
            $d_condition['device_code'] = $device_code;
            $deviceInfo = $deviceModel->where($d_condition)->field("code_id,device_status")->find();
            $code_id = $deviceInfo['code_id'];
            if(!$code_id){
                //注册码过期或者已经删�?;
                return $this->is_security = false;
            }
            $device_status = $deviceInfo['device_status'];
            if(!$device_status){
                //该机器已经被禁用;
                return $this->is_security = false;
            }
            if($code_id){
                /**
                 * 机器码绑定的机器码存在，查看注册码的剩余时间是否大于0�?
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
                // $codeModel->where($c_condition)->save($c_data);

                if($code_restTimestamp < 0){
                    return $this->is_security = false;
                }else{
                    $restaurant_id = session("restaurant_id");
                    if(!$restaurant_id){
                        session("restaurant_id",$codeInfo['restaurant_id']);
                    }
                    $cc_data['last_time'] = time();
                    $rel = $codeModel->where($c_condition)->save($cc_data);
                    return $this->is_security = true;
                }
            }else{
                return $this->is_security = false;
            }
        }
    }

    //盘点该机器是否可�?
    public function equipmentLogin($device_code){
        /**
         * 如果机器码不存在，则提示非法访问
         */
        if($device_code == false){
            return $this->is_security = false;
        }else{
            $device_code = I("device_code");
            $device_model = D("equipment");
            $where['equipment_code'] = $device_code;
            $device_info = $device_model->where($where)->find();
            $now_time = date('Y-m-d H:i:s',time());
            if($now_time < $device_info['terminal_time']){
                session("device_code",$device_code);
                session("restaurant_id",$device_info['restaurant_id']);
                return $this->is_security = true;
            }else{
                return $this->is_security = false;
            }
        }
    }
    /*
    *����json��ʽ
    */
    public function returnJson($code, $data) {
        $returnData['code'] = $code;
        $returnData['msg'] = $data;
        echo json_encode($returnData);
        exit;
    }
    /*
    *��֤�豸��Ϣ
    */
    public function validates() {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if(empty($this->is_security)){
            $returnData['code'] = '1';
            $returnData['order_sn'] = "";
            $returnData['msg'] = "���豸�ѹ���";
            echo json_encode($returnData);
            exit;
        }
    }
}