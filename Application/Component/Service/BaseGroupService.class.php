<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/22
 * Time: 15:07
 */

namespace Component\Service;

class BaseGroupService
{

    /**
     * 判断属性值正误
     * @param $group_id
     * @param $group_attr_name
     * @param $sample_value
     * @return bool
     */
    public function is_group_attr($group_id,$group_attr_name,$sample_value){
        $where['group_id'] = $group_id;
        $where['group_attr_name'] = $group_attr_name;
        $group_attr_model = D("group_attr");
        $rel = $group_attr_model->where($where)->find();
        $value = $rel['value'];
        if($sample_value == $value){
            return true;
        }else{
            return false;
        }
    }
}