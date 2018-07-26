<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/16
 * Time: 16:08
 */

namespace ShowNumber\Controller;
use ShowNumber\Service;
use Think\Controller;

class ActivateController extends Controller
{
    public function equipmentActivate(){
        $register_code = I("register_code");
        $device_code = I("device_code");
        $device_name = I("device_name");
        $type = I("type");
        $activateService = new Service\ActivateService();
        $activateService->activate($register_code,$device_code,$device_name,$type);
    }

    public function activateQrc(){
        $type = I("type");
        $device_code = I("device_code");
        $this->assign("type",$type);
        $this->assign("device_code",$device_code);
        $this->display();
    }

    /**
     * 提交机器类型和机器码，获取对应的激活链接的二维码
     */
    public function showNumQrc(){
        $type = I("type");
        $device_code = I("device_code");
        $qrInfo = "http://".$_SERVER["HTTP_HOST"]."/index.php/ShowNumber/Activate/activateLink/device_code/".$device_code."/type/".$type;
        //生成二维码图片并直接输出
        Vendor('phpqrcode.phpqrcode');

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(14);//生成图片大小

        $object = new \QRcode();
        ob_clean();
        $object->png($qrInfo,false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    /**
     * 激活链接页面
     * 填写注册码激活
     */
    public function activateLink(){
        $device_code = I("device_code");
        $type = I("type");
        $this->assign("device_code",$device_code);
        $this->assign("type",$type);
        $this->display();
    }

    /**
     * 查询设备是否激活
     *
     */
    public function equipmentStatus(){
        $type = I("type");
        $device_code = I("device_code");
        $equipment_model = D('equipment');
        $where['type'] = $type;
        $where['equipment_code'] = $device_code;
        $rel = $equipment_model->where($where)->find();
        if($rel){
            $returnData['code'] = 1;
            $returnData['msg'] = "操作成功";
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
        }
        exit(json_encode($returnData));
    }

    /**
     * 清除长隆设备的激活记录
     */
    public function clearDeviceRecord(){
        $device_code = I("post.device_code");
//        file_put_contents(__DIR__."/"."shanchuLog.txt","设备码：".$device_code."\r\n",FILE_APPEND);

        $deviceModel = D("equipment");

        $de_where['equipment_code'] = $device_code;

        //判断注册码是否过期，过期就删除。
        $device_info = $deviceModel->where($de_where)->find();
        $device_info['start_time'] = date("Y-m-d H:i:s",$device_info['start_time']);
        $device_info['end_time'] = date("Y-m-d H:i:s",$device_info['end_time']);

        $code_id = $device_info['code_id'];
        $c_where['code_id'] = $code_id;
        $code_model = D("code");
        $code_info = $code_model->where($c_where)->field("rest_timestamp")->find();

        if($code_info['rest_timestamp'] > 0){
            $data["code_status"] = 1;
            $code_model->where($c_where)->save($data);
        }else{
            $code_model->delete($c_where);
        }

        $rel = $deviceModel->where($de_where)->delete();
//        file_put_contents(__DIR__."/log.txt",var_export($device_info,true)."\r\n",FILE_APPEND);

        // 根据设备码查询出对应的equipment_type
        $type = $device_info['equipment_type'];
        if($type == "yell"){
            $yell_equipment_id = $device_info['yell_equipment_id'];
            $yell_save['yell_equipment_id'] = 0;
            // 如果删除的是叫号屏，那就用对应叫号屏设备ID将对应的restaurant_district表里的该条记录的yell_equipment_id改为0。
            D("restaurant_distinct")->where(array("yell_equipment_id"=>$yell_equipment_id))->save($yell_save);
            // 如果删除的是叫号屏，yell_cancel表以叫号屏ID做条件对应的记录的yell_equipment_id改为0
            D("yell_cancel")->where(array("yell_equipment_id"=>$yell_equipment_id))->save($yell_save);
        }elseif($type == "cancel"){
            // 如果删除的是核销屏，yell_cancell表的整条记录就要删掉
            D("yell_cancel")->where(array("cancel_equipment_id"=>$device_info['cancel_equipment_id']))->delete();
        }

        exit($rel);
    }
}