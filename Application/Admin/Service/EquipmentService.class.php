<?php
namespace Admin\Service;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/8
 * Time: 17:42
 */
class EquipmentService
{
    protected $equipment_type;

    public function __construct(){
        $this->equipment_type = C("equipment_type");
    }

    /**
     * @param $equipment_type
     * @param $restaurant_id
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getEquipmentInfo($equipment_type,$restaurant_id){
        //判断$equipment_type是否在设备类型里面
        if(!in_array($equipment_type,$this->equipment_type)){
            return [];
        }

        //查询条件
        $where['equipment_type'] = $equipment_type;
        $where['restaurant_id'] = $restaurant_id;

        $equipment_model = D("equipment");
        $equipments = $equipment_model->where($where)->select();

        if($equipment_type == 'yell'){
            $district_model = D("restaurant_district");
            $yell_cancel_model = D("yell_cancel");
            foreach($equipments as $key => $val){
                $where['yell_equipment_id'] = $val['equipment_id'];
                $d_rel = $district_model->where($where)->field("district_id")->find();
                $district_id = $d_rel['district_id'];
                $equipments[$key]['district'] = $district_id;

                $yell_cancel_rel = $yell_cancel_model->where($where)->find();
                if(!empty($yell_cancel_rel)){
                    $equipments[$key]['disabled'] = true;
                    continue;
                }
                $equipments[$key]['disabled'] = false;
            }
        }

        if($equipment_type == 'cancel'){
            $yell_cancel_model = D("yell_cancel");
            foreach($equipments as $key => $val){
                $where['cancel_equipment_id'] = $val['equipment_id'];
                $d_rel = $yell_cancel_model->where($where)->field("yell_equipment_id")->find();
                $yell_equipment_id = $d_rel['yell_equipment_id'];
                if($yell_equipment_id){
                    $equipments[$key]['yell_equipment_id'] = $yell_equipment_id;
                }else{
                    $equipments[$key]['yell_equipment_id'] = "";
                }

            }
        }

        return $equipments;
    }
}