<?php
namespace data\service;

/*
*店铺相关
*/
class Restaurant extends BaseService{
    /*
    *获取店铺信息
    */
    public function getInfo() {
        $where['restaurant_id'] = $this->restaurant_id;
        Return M("restaurant")->where($where)->find();
    }
    /*
    *获取微信配置信息
    */
    public function getWxConfig() {
        $where['restaurant_id'] = $this->restaurant_id;
        $where['config_type'] = "wxpay";
        $wx_config = M("config")->where($where)->select();
        Return dealConfigKeyForValue($wx_config);
    }
    /*
    *获取代理信息
    */
    public function getBusinessInfo($business_id) {
        $where['business_id'] = $business_id;
        Return M("business")->where($where)->find();
    }
}
