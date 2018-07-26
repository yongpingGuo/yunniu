<?php
namespace AllAgent\Controller;
use Think\Controller;

class DeskQrcController extends Controller
{
    public function __construct(){
        Controller::__construct();
        if(!session("manager_id")){
            $this->redirect("login");
        }
    }
    public function qrc_code(){
        $codeModel = D("qrc_code");
        $p = I("get.page");
        $pageNum = 10;
		$where['qrc_code_status'] = 1;
        $count = $codeModel->where($where)->count();
        $Page = new \Think\Page($count,$pageNum);
        $show = $Page->show();
        $this->assign('page',$show);
        $codeList = $codeModel->where($where)->page($p,$pageNum)->select();
        $businessModel = D("business");
        foreach($codeList as $key => $val){
            $business_name = $businessModel ->field("business_name")->find()["business_name"];
            $codeList[$key]['business_name'] = $business_name;
            $codeList[$key]['qrc_code_timestamp'] = date("Y-m-d H:i:s",time());
            if($codeList[$key]['last_time'] != 0){
                $codeList[$key]['qrc_code_timestamp'] = date("Y-m-d H:i:s",$codeList[$key]['last_time']);
            }
            $codeList[$key]['qrc_rest_timestamp'] = date("Y-m-d H:i:s",time()+$codeList[$key]['rest_timestamp']);
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
        $codeModel = D("qrc_code");
        $codeModel->startTrans();
        for($i = 0;$i<$code_num;$i++){
            $code = create_guid();
            $data['qrc_code'] = $code;
            $data['qrc_code_timestamp'] = 30*24*3600;
            $data['qrc_rest_timestamp'] = 30*24*3600;
            $data['business_id'] = I("post.business_id");
            $data['qrc_code_status'] = 1;
            $result = $codeModel->add($data);
            if(!$result){
                $codeModel->rollback();
                $msg["code"] = 1;
                $msg["msg"] = "操作失败";
                exit(json_encode($msg));
            }
        }
		$where1['qrc_code_status'] = 1;
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
        $codeModel = D("qrc_code");

        $condition['qrc_code_timestamp'] = $newTime;
        $condition['qrc_rest_timestamp'] = $newTime;
        $condition['qrc_code_id'] = $code_id;

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

    function deleteCode($code_id){
        $condition['qrc_code_id'] = $code_id;
        $codeModel = D('qrc_code');
        $result = $codeModel->where($condition)->delete();
        if($result){
        	$where1['qrc_code_status'] = 1;
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

    function findInfo($code_id){
        $condition['qrc_code_id'] = $code_id;
        $codeModel = D('qrc_code');

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
        $data["qrc_code_id"] = I("get.code_id");

        $codeModel = D("qrc_code");
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
	
	public function batch_delete(){
		$code_IdArr = explode(',', I('code_id'));
		$where['qrc_code_id'] = array('in',$code_IdArr);
		$qrc_code = D('qrc_code');
		$r = $qrc_code->where($where)->delete();
		if($r){
			$where1['qrc_code_status'] = 1;
			$num = $qrc_code->where($where1)->count();
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
}