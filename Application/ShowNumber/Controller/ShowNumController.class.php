<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/15
 * Time: 15:40
 */
namespace ShowNumber\Controller;
use Think\Controller;

class ShowNumController extends Controller
{

    public function __construct(){
        parent::__construct();
//        $device_code = I("post.device_code");
        $device_code = I("device_code");
        $device_model = D("equipment");
        $where['equipment_code'] = $device_code;
        $device_info = $device_model->where($where)->find();
        $now_time = date('Y-m-d H:i:s',time());
        if($now_time < $device_info['Terminal_time']){
            session("device_code",$device_code);
            session("restaurant_id",$device_info['restaurant_id']);
        }
    }

    /**
     * 汇总叫号屏
     */
    public function showNumber(){
        $equipment_code = session("device_code");
        $equipment_model = D('equipment');
        $where['equipment_code'] = $equipment_code;
        $equipment_info = $equipment_model->where($where)->find();
        $restaurant_id = $equipment_info['restaurant_id'];
        $config_model = D("config");
        $where['restaurant_id'] = $restaurant_id;
        $where['config_type'] = "functionality";
        $where['config_name'] = "show_num";
        $mark = $config_model->where($where)->find()['config_value'];
        if($mark){
            session("district_mark",$mark);
            $this->display();
        }else{
            echo "分区叫号功能还没开启";
        }
    }

    /**
     * 分区叫号屏
     */
    public function districtShowNumber(){
        $equipment_code = session("device_code");
        $equipment_model = D('equipment');
        $where['equipment_code'] = $equipment_code;
        $equipment_info = $equipment_model->where($where)->find();
        $rs_where["yell_equipment_id"] = $equipment_info["equipment_id"];
        $restaurant_restrict_model = D("restaurant_district");
        $rel = $restaurant_restrict_model->where($rs_where)->find();
        if(!$rel){
            exit("叫号屏设备信息或分区信息不存在");
        }
        session("district_id",$rel['district_id']);
        session("district_mark",$rel['district_mark']);
        $this->display();
    }

    /**
     * 核销屏
     */
    public function writeOff(){
        $equipment_code = session("device_code");
//        file_put_contents(__DIR__."/"."hexiao.txt","设备码：".$equipment_code."\r\n",FILE_APPEND);  // 38:AA:3C:B2:B1:F4

        $equipment_model = D('equipment');
        $where['equipment_code'] = $equipment_code;
        $equipment_info = $equipment_model->where($where)->find();

        $yell_cancel_model = D("yell_cancel");
        $yc_where["cancel_equipment_id"] = $equipment_info["equipment_id"]; // 35
//        file_put_contents(__DIR__."/"."hexiao.txt","设备id：".$equipment_info["equipment_id"]."\r\n",FILE_APPEND);
        $rel1 = $yell_cancel_model->where($yc_where)->find();   // 35

        $rs_where["yell_equipment_id"] = $rel1["yell_equipment_id"];
//        file_put_contents(__DIR__."/"."hexiao.txt","yell_id：".$rel1["yell_equipment_id"]."\r\n",FILE_APPEND);
        $restaurant_restrict_model = D("restaurant_district");
        $rel2 = $restaurant_restrict_model->where($rs_where)->find();
//        file_put_contents(__DIR__."/"."hexiao.txt","分区名：".$rel2['district_name']."\r\n",FILE_APPEND);
        if(!$rel2){
            exit("叫号屏或核销屏设备信息不存在");
        }
        session("district_id",$rel2['district_id']);
        session("district_mark",$rel1['cancel_mark']);
        $this->display();
    }
}