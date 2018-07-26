<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/13
 * Time: 16:46
 */

namespace Api\Controller;

use Api\Service\ShowNumberService;

class ShowNumberController extends BaseController
{
    /*public function __construct()
    {
        $token = I("post.token");
        $condition['token'] = $token;
        $info = D("interface_login_check")->where($condition)->find();

        if(!$info){
            $returnData['code'] = "0";
            $returnData['msg'] = "非法访问";
            exit(json_encode($returnData));
        }
    }*/

    /*=================================汇总叫号屏====================================*/
    /**
     * 获取coming中的取餐号(coming)
     */
    public function getComingOrderNum(){
        $device_code = I("device_code");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $show_num_service = new ShowNumberService();
            $rel = $show_num_service->getComingFoodOrderNum(session('restaurant_id'));
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     * 获取所有分区及其对应的finish的取餐号(finish)
     */
    public function getAllFinishOrderNum(){
        $device_code = I("device_code");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $show_num_service = new ShowNumberService();
            $rel = $show_num_service->getAllOrderNum(session('restaurant_id'),2);
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /*=======================================叫号屏=====================================*/
    /**
     * 获取单个分区的取餐号(status:[1 coming]、[2 finish])
     */
    public function getOrderNumByStatus(){
        $device_code = I("device_code");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $district_id = I("district_id");
            $status = I("status");
            if(!$district_id){
                $returnData['code'] = 0;
                $returnData['msg'] = "参数错误";
                exit(json_encode($returnData));
            }
            $show_num_service = new ShowNumberService();
            $rel = $show_num_service->getOrderNum(session('restaurant_id'),$status,$district_id);
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /*=====================================核销屏===================================*/
    /**
     * 获取分区对应的核销屏的所有订单
     */
    public function getAllOrderInDistrict(){
        $device_code = I("device_code");
        $district_id = I("district_id");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $show_num_service = new ShowNumberService();
            $rel = $show_num_service->getAllOrderInDistrict(session('restaurant_id'),$district_id);
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     * 获取订单的在某一分区的菜品
     */
    public function getOrderFoodsByOne(){
        $device_code = I("device_code");
        $district_id = I("district_id");
        $order_id = I("order_id");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $show_num_service = new ShowNumberService();
            $rel = $show_num_service->getOrderFoodsByOne($district_id,$order_id);
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     * 修改order_food 的状态
     */
    public function setOrderFoodStatus(){
        $device_code = I("device_code");
        $order_food_id = I("order_food_id");
        $status = I("status");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $show_num_service = new ShowNumberService();
            $rel = $show_num_service->setOrderFoodStatus($order_food_id,$status);
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     *一键取餐或核销
     */
    public function setAllOrderFoodStatus(){
        $device_code = I("device_code");
        $order_food_ids = I("order_food_ids");
        $status = I("status");
        $order_food_ids = explode(",",$order_food_ids);
        $this->equipmentLogin($device_code);
        if($this->is_security){
            $show_num_service = new ShowNumberService();
            $order_food_model = D('order_food');
            $rel = false;
            foreach($order_food_ids as $key => $val){
                $where['order_food_id'] = $val;
                $o_rel = $order_food_model->where($where)->find();
                if($o_rel['status'] != 3 && $o_rel['status'] != ($status-2)){
                    $rel = $show_num_service->setOrderFoodStatus($val,$status);
                }
            }
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "操作成功";
            }
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     * 取餐、核销操作后推送信息
     */
    public function pushUpdateShowNum(){
        $device_code = I("device_code");
        $this->equipmentLogin($device_code);
        if($this->is_security){
            //推送更新
            $restaurant_id = session('restaurant_id');
//            dump($restaurant_id);
            $currentNum = I('currentNum');
            pushAllDistrict($restaurant_id,$currentNum);
        }
    }
}