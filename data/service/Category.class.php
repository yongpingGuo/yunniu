<?php
namespace data\service;

/*
*菜时分类
*/
class Category extends BaseService{
    /*
    *获取列表
    */
    public function getList($where, $order = "sorts desc") {
        if($this->restaurant_id > 0) $where['restaurant_id'] = $this->restaurant_id;
        Return M("food_time_category")->where($where)->order($order)->select();
    }
    /*
    *获取基本信息
    */
    public function getInfo($food_time_category_id) {
        $where['restaurant_id'] = $this->restaurant_id;
        $where['food_time_category_id'] = $food_time_category_id;
        Return M("food_time_category")->where($where)->find();
    }
    /*
    *菜时分类添加
    */
    public function timeAdd($data){
        $data['restaurant_id'] = $this->restaurant_id;
        $data['sorts'] = $data['sorts'] * 1;
        Return M("food_time_category")->add($data);
    }
    /*
    *删除分类
    */
    public function del($food_time_category_id) {
        $where['restaurant_id'] = $this->restaurant_id;
        $where['food_time_category_id'] = $food_time_category_id;
        Return M("food_time_category")->where($where)->delete();
    }
    /*
    *修改菜时分类
    */
    public function timeUpdate($data) {
        $where['restaurant_id'] = $this->restaurant_id;
        $where['food_time_category_id'] = $data['food_time_category_id'];
        unset($data['food_time_category_id']);
        Return M("food_time_category")->where($where)->save($data);
    }
}
