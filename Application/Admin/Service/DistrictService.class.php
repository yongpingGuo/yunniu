<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/9
 * Time: 10:22
 */

namespace Admin\Service;

class DistrictService
{
    /**
     * 获取店铺的分区状况
     * @param $restaurant_id
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getDistrictList($restaurant_id){
        $district_model = D("restaurant_district");
        $where['restaurant_id'] = $restaurant_id;
        $district_list = $district_model->where($where)->field("district_id,district_name,yell_equipment_id")->select();
        foreach($district_list as $key => $val){
            if($val['yell_equipment_id'] == 0){
                $district_list[$key]['disabled'] = false;
                continue;
            }
            $district_list[$key]['disabled'] = true;
        }

        return $district_list;
    }
}