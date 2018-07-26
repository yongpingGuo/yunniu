<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/24
 * Time: 10:50
 */

namespace Manager\Controller;
use Think\Controller;

class DeviceController extends Controller
{
    public function index(){
        $deviceModel = D("device");
        $p = I("get.page");
        $count = $deviceModel->count();
        $pageNum = 10;
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page->show();
        $this->assign('page',$show);


        $deviceList = $deviceModel->page($p,$pageNum)->select();
        $codeModel = D('code');
        $businessModel = D('business');
        foreach($deviceList as $key => $val){
            $condition["code_id"] = $val['code_id'];
            $codeInfo = $codeModel->where($condition)->field("code,business_id")->find();
            $code = $codeInfo['code'];
            $deviceList[$key]['code'] = $code ? $code : "暂未注册";

            $bi_condition['business_id'] = $codeInfo['business_id'];
            $businessInfo = $businessModel->where($bi_condition)->field("business_id,business_name")->find();
            $deviceList[$key]['business_name'] = $businessInfo['business_name'];
            $deviceList[$key]['business_id'] = $businessInfo['business_id'];
        }


        $this->assign("deviceList",$deviceList);
        $this->display();
    }

    public function code(){
        $codeModel = D("code");
        $p = I("get.page");
        $pageNum = 10;
        $count = $codeModel->count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page->show();
        $this->assign('page',$show);
        $codeList = $codeModel->page($p,$pageNum)->select();
        $businessModel = D("business");
        foreach($codeList as $key => $val){
            $business_name = $businessModel ->field("business_name")->find()["business_name"];
            $codeList[$key]['business_name'] = $business_name;
            $codeList[$key]['code_timestamp'] = ($val['code_timestamp']/3600)/24;
            $codeList[$key]['rest_timestamp'] = ($val['rest_timestamp']/3600)/24;
        }
        $this->assign("codeList",$codeList);

        $businessList = $businessModel->field("business_id,business_name")->select();
        $this->assign("businessList",$businessList);

        unset($codeModel);
        unset($codeList);
        unset($businessModel);
        unset($businessList);
        $this->display();
    }

    //生成注册码
    public function create_code(){
        $code_num = I("post.code_num");
        $codeModel = D("code");
        $codeModel->startTrans();
        for($i = 0;$i<$code_num;$i++){
            $code = create_guid();
            $data['code'] = $code;
            $data['code_timestamp'] = 30*24*3600;
            $data['rest_timestamp'] = 30*24*3600;
            $data['business_id'] = I("post.business_id");
            $data['code_status'] = 0;
            $result = $codeModel->add($data);
            if(!$result){
                $codeModel->rollback();
                $msg["code"] = 1;
                $msg["msg"] = "操作失败";
                exit(json_encode($msg));
            }
        }
        $codeModel->commit();
        unset($codeModel);
        $msg['code'] = 1;
        $msg['msg'] = "操作成功";
        exit(json_encode($msg));
    }

    public function changeCodeTime(){
        $code_id = I("get.code_id");
        $dayNum = I("get.dayNum");
        $codeTimestamp = $dayNum*24*3600;

        $codeModel = D("code");
        $code_time = $codeModel->where("code_id = $code_id")->field("code_timestamp,rest_timestamp")->find();
        $newRestTimestamp = $codeTimestamp-$code_time["code_timestamp"] + $code_time["rest_timestamp"];

        $condition['code_timestamp'] = $codeTimestamp;
        $condition['rest_timestamp'] = $newRestTimestamp;
        $condition['code_id'] = $code_id;

        $result = $codeModel->save($condition);

        if($result != false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = array(
                "codeTime" => $codeTimestamp/86400,
                "codeRestTime" => $newRestTimestamp/86400,
            );
            unset($codeModel);
            exit(json_encode($msg));
        }
    }

    public function changeCodeRestTime(){
        $code_id = I("get.code_id");
        $dayNum = I("get.dayNum");
        $codeRestTimestamp = $dayNum*24*3600;

        $codeModel = D("code");
        $code_time = $codeModel->where("code_id = $code_id")->field("code_timestamp,rest_timestamp")->find();

        $codeTimestamp = $code_time['code_timestamp'];

        $condition = array();
        if($codeRestTimestamp < $code_time['code_timestamp']){
            $condition['code_timestamp'] = $code_time['code_timestamp'];
        }else{
            $condition['code_timestamp'] = $codeRestTimestamp;
        }

        $condition['rest_timestamp'] = $codeRestTimestamp;
        $condition['code_id'] = $code_id;

        $result = $codeModel->save($condition);

        if($result != false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = array(
                "codeTime" => $condition['code_timestamp']/86400,
                "codeRestTime" =>  $condition['rest_timestamp']/86400,
            );
            unset($codeModel);
            exit(json_encode($msg));
        }
    }

    function deleteCode($code_id){
        $condition['code_id'] = $code_id;
        $codeModel = D('code');
        $result = $codeModel->where($condition)->delete();
        if($result){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $this->ajaxReturn($msg);
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "操作失败";
            $this->ajaxReturn($msg);
        }
    }

    function findInfo($code_id){
        $condition['code_id'] = $code_id;
        $codeModel = D('code');

        $restaurant_id = $codeModel->where($condition)->field("restaurant_id")->find()['restaurant_id'];

        $codeRelative = array();
        if($restaurant_id){
            //查找店铺信息
            $restaurantModel = D("restaurant");
            $restaurantInfo = $restaurantModel->where("restaurant_id = $restaurant_id")->find();
            $codeRelative['restaurant'] = $restaurantInfo;
        }

        //查找设备信息
        $deviceModel = D("device");
        $deviceInfo = $deviceModel->where($condition)->find();

        if($deviceInfo){
            $codeRelative['device'] = $deviceInfo;
        }

        unset($codeModel);
        if($deviceInfo || $restaurant_id){
            $this->ajaxReturn($codeRelative);
        }else{
            $msg['msg'] = "没有关联";
            $this->ajaxReturn($msg);
        }
    }

    public function changeCodeBusiness(){
        $data["business_id"] = I("get.business_id");
        $data["code_id"] = I("get.code_id");

        $codeModel = D("code");
        $result = $codeModel->save($data);
        unset($codeModel);
        if($result !== false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";

            $this->ajaxReturn($msg);
            exit;
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "操作失败";
            $this->ajaxReturn($msg);
            exit;
        }
    }
}