<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/13
 * Time: 16:48
 */

namespace Api\Service;

class ShowNumberService
{
    protected $comingPageLength = 20;        //准备中的订单个数
    protected $finishPageLength = 10;        //finish的订单个数

    /*========================================汇总屏==============================================*/
    /**
     * 获取汇总屏准备中（coming）的订单号
     * @param $restaurant_id
     * @return array
     */
    public function getComingFoodOrderNum($restaurant_id){
        $order_model = D("order");
        $where['restaurant_id'] = $restaurant_id;
        $where['order_status'] = 3;
        $where['table_num'] = 0;
        $day_starting = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $day_ending = mktime(23,59,59,date('m'),date('d'),date('Y'));
        $where['pay_time'] = array('between',array($day_starting,$day_ending));
        $rel = $order_model->where($where)->field("order_sn,order_id")->select();

        $coming_order_list = [];
        $order_food_model = D("order_food");
        foreach($rel as $key => $val){
            $of_where["order_id"] = $val['order_id'];
            $of_rel = $order_food_model->where($of_where)->field("district_id")->find();
            if($of_rel['district_id']){
                $coming_order_list[] = substr($val['order_sn'],-5,5);
            }
        }
        $coming_order_list = $this->paging($coming_order_list,$this->comingPageLength);
        return $coming_order_list;
    }

    /**
     * 获取汇总屏所有分区（finish）的订单号
     * @param $restaurant_id
     * @param $status
     * @return array
     */
    public function getAllOrderNum($restaurant_id,$status = 2){
        $district_model = D("restaurant_district");
        $where['restaurant_id'] = $restaurant_id;
        $district_info = $district_model->where($where)->field("district_id,district_name")->select();
        foreach($district_info as $key => $val){
            $rel = $this->getOrderNum($restaurant_id,$status,$val['district_id']);
            $district_info[$key]['finishNum'] = $rel;
        }
        return $district_info;
    }


    /*=====================================分区叫号屏==================================*/
    /**
     * 获取分区的(coming,finish)的取餐号
     * @param $restaurant_id
     * @param $status
     * @param $district_id
     * @return array
     */
    public function getOrderNum($restaurant_id,$status,$district_id){
        $order_model = D('order');
        $result = array();
        $order_ids = $this->getOrderIds($restaurant_id,$status,$district_id);
        foreach($order_ids as $oik => $oiv){
            $where['restaurant_id'] = $restaurant_id;
            $where['order_status'] = array('in',array(3,11));
            $where['table_num'] = 0;
            $where['order_id'] = $oiv['order_id'];
            $day_starting = mktime(0,0,0,date('m'),date('d'),date('Y'));
            $day_ending = mktime(23,59,59,date('m'),date('d'),date('Y'));
            $where['pay_time'] = array('between',array($day_starting,$day_ending));
            $o_rel = $order_model->where($where)->field("order_sn")->find();
            if(!empty($o_rel)){
                $result[] = substr($o_rel['order_sn'],-5,5);
            }
        }
        $result = $this->paging($result,$this->finishPageLength);
        return $result;
    }

    /*======================================核销屏======================================*/

    /**
     * 获取分区中的所有订单
     * @param $restaurant_id
     * @param $district_id
     * @return array
     */
    public function getAllOrderInDistrict($restaurant_id,$district_id){
        $result = array();
        $order_ids = $this->getOrderIds($restaurant_id,$status = "all",$district_id);
        $order_model = D("order");
        foreach($order_ids as $key => $val){
            $where['order_id'] = $val['order_id'];
            $rel = $order_model->where($where)->field("order_id,order_sn")->find();
            $rel['order_sn'] = substr($rel['order_sn'],-5,5);
            $result[] = $rel;
        }
        return $result;
    }

    /**
     * 获取订单的在某一分区的菜品
     * @param $district_id
     * @param $order_id
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getOrderFoodsByOne($district_id,$order_id){
        $order_food_model = D("order_food");
        $where['district_id'] = $district_id;
        $where['order_id'] = $order_id;
        $rel = $order_food_model->where($where)->select();
        return $rel;
    }

    /**
     * 修改订单中某一菜品的状态
     * @param $order_food_id
     * @param $status
     * @return bool|false|int
     */
    public function setOrderFoodStatus($order_food_id,$status){
        $order_food_model = D("order_food");
        $where['order_food_id'] = $order_food_id;
        $data['status'] = $status;
        $rel = $order_food_model->where($where)->save($data);
        if($rel){
            $order_id = $order_food_model->where($where)->field("order_id")->find()['order_id'];
            $this->setOrderStatus($order_id,$status);
        }
        return $rel;
    }

    /**
     * 改变订单的状态
     * @param $order_id
     * @param $status
     * @return bool|false|int
     */
    public function setOrderStatus($order_id,$status){
        $order_food_model = D('order_food');
        $where['order_id'] = $order_id;
        $rel = $order_food_model->where($where)->count();
        $where['status'] = $status;
        $rel2 = $order_food_model->where($where)->count();
        if($rel == $rel2){
            $order_model = D("order");
            $result = false;
            if($status == 2){
                $o_where['order_id'] = $order_id;
                $data['order_status'] = 11;
                $result =$order_model->where($o_where)->save($data);
            }
            if($status == 3){
                $o_where['order_id'] = $order_id;
                $data['order_status'] = 12;
                $result = $order_model->where($o_where)->save($data);
            }
            return $result;
        }else{
            return false;
        }
    }

    /*======================================辅助方法======================================*/
    /**
     * 获取订单id
     * @param $restaurant_id
     * @param $status
     * @param $district_id
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getOrderIds($restaurant_id,$status = 'all',$district_id){
        $order_model = D("order");
        $where['restaurant_id'] = $restaurant_id;
        $day_starting = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $day_ending = mktime(23,59,59,date('m'),date('d'),date('Y'));
        $where['pay_time'] = array('between',array($day_starting,$day_ending));
        $today_first_order_id = $order_model->where($where)->field("order_id")->find()['order_id'];
        $order_food_model = D("order_food");
        $o_where = array();
        $o_where['status'] = array("between",array(1,2));
        if($status != 'all'){
            $o_where['status'] = $status;
        }
        if(!$district_id){
            return [];
        }
        $o_where['district_id'] = $district_id;
        $o_where['order_id'] = array("egt",$today_first_order_id);
        $order_ids = $order_food_model->where($o_where)->distinct("order_id")->field("order_id")->select();

        // 过滤掉没支付的开始
        foreach($order_ids as $key=>$oi){
            $status = $order_model->where(array("order_id"=>$oi['order_id']))->field("order_status,pay_type")->find();
            if($status['order_status'] == 0 || $status['pay_type'] == 3){
                unset($order_ids[$key]);
            }
        }
        // 过滤掉没支付的结束

        return $order_ids;
    }

    /**
     * 将一个一维数组转换为一个二维数组
     * @param $array             //一维数组
     * @param $childLength       //每个子数组的长度
     * @return array
     */
    public function paging($array,$childLength){
        if(!is_array($array)){
            return [];
        };
        $result = array();
        $length = count($array);
        $array_num = $length/$childLength;
        for($i=0;$i<$array_num;$i++)
        {
            $result[] = array_slice($array, $i * $childLength ,$childLength);
        }
        return $result;
    }
}