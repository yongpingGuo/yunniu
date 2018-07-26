<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/24
 * Time: 10:50
 */

namespace AllAgent\Controller;
use Think\Controller;

class CodeController extends Controller
{
	public function __construct(){
        Controller::__construct();
        if(!session("manager_id")){
            $this->redirect("login");
        }
    }
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
		$where['code_status'] = 1;
        $count = $codeModel->where($where)->count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page->show();
        $this->assign('page',$show);
        $codeList = $codeModel->page($p,$pageNum)->where($where)->select();
        $businessModel = D("business");
        foreach($codeList as $key => $val){
            $business_name = $businessModel ->field("business_name")->find()["business_name"];
            $codeList[$key]['business_name'] = $business_name;
            $codeList[$key]['code_timestamp'] = date("Y-m-d H:i:s",time());
            if($codeList[$key]['last_time'] != 0){
                $codeList[$key]['code_timestamp'] = date("Y-m-d H:i:s",$codeList[$key]['last_time']);
            }
            $codeList[$key]['rest_timestamp'] = date("Y-m-d H:i:s",time()+$codeList[$key]['rest_timestamp']);
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
            $data['rest_timestamp'] = 720*24*3600;
            $data['business_id'] = I("post.business_id");
            $data['code_status'] = 1;
            $result = $codeModel->add($data);
            if(!$result){
                $codeModel->rollback();
                $msg["code"] = 1;
                $msg["msg"] = "操作失败";
                exit(json_encode($msg));
            }
        }
		$where1['code_status'] = 1;
		$num = $codeModel->where($where1)->count();
		$page = ceil($num/10);
        $codeModel->commit();
        unset($codeModel);
        $msg['code'] = 1;
        $msg['msg'] = "操作成功";
		$msg['page'] = $page;
        exit(json_encode($msg));
    }

    public function changeCodeTime(){
        $code_id = I("get.code_id");
        $start_time = strtotime(I("get.start_time"));
        $end_time = strtotime(I("get.end_time"));

        $newTime = $end_time-$start_time;
        $codeModel = D("code");

        $condition['code_timestamp'] = $newTime;
        $condition['rest_timestamp'] = $newTime;
        $condition['code_id'] = $code_id;

        $result = $codeModel->save($condition);

        if($result !== false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = array(
                "codeTime" => date("Y-m-d H:i:s",$end_time-$newTime),
                "codeRestTime" => date("Y-m-d H:i:s",$end_time),
            );
            unset($codeModel);
            exit(json_encode($msg));
        }
    }

	//单个注册码删除
    function deleteCode($code_id){
        $condition['code_id'] = $code_id;
        $codeModel = D('code');
        $result = $codeModel->where($condition)->delete();
        if($result){
        	$where1['code_status'] = 1;
			$num = $codeModel->where($where1)->count();
			$page = ceil($num/10);
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
			$msg['page'] = $page;
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "操作失败";
        }
 			$this->ajaxReturn($msg);
    }
	
	//注册码批量删除
	public function batch_delete(){
		$code_IdArr = explode(',', I('code_id'));
		$where['code_id'] = array('in',$code_IdArr);
		$code = D('code');
		$r = $code->where($where)->delete();
		if($r){
			$where1['code_status'] = 1;
			$num = $code->where($where1)->count();
			$page = ceil($num/10);
			$msg['msg'] = '批量删除成功！';
			$msg['code'] = 1;
			$msg['page'] = $page;	
		}else{
			$msg['msg'] = '批量删除失败！';
			$msg['code'] = 0;
		}
		exit(json_encode($msg));	
	}

    function findInfo($code_id){
        $condition['code_id'] = $code_id;
        $codeModel = D('code');

        $restaurant_id = $codeModel->where($condition)->field("restaurant_id")->find()['restaurant_id'];

        $codeRelative = array();
        if($restaurant_id){
            //查找店铺信息
            $restaurantModel = D("Restaurant");
            $restaurantInfo = $restaurantModel->where("restaurant_id = $restaurant_id")->find();
            $codeRelative['Restaurant'] = $restaurantInfo;
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