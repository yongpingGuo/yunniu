<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/17
 * Time: 15:13
 */

namespace Vertical\Controller;
use think\Controller;

class OrderController extends Controller
{
    public function immediatePay(){
        $restaurant_id = session("restaurant_id");
        $carts = I("post.carts");
        $order_type = I("post.order_type");

        $order_model = order();
        $order_model->startTrans();
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
        $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
        $condition1['restaurant_id'] = session("restaurant_id");     //店铺id

        $num = $order_model->where($condition1)->count();        //两时间之间的订单数

        $order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单

        $food_model = D("food");
        $total_amount = 0;
        $food_list = array();
        foreach($carts as $key => $val){
            $food_where['food_id'] = $val['food_id'];
            $food_info = $food_model->where($food_where)->find();
            $food_num = $val['food_num'];
            $amount = $food_info['food_price']*$food_num;
            $total_amount += $amount;

            $food_list[$key]['food_id'] = $val['food_id'];
            $food_list[$key]['food_num'] = $val['food_num'];
            $food_list[$key]['food_price2'] = $val['food_price'];
            $food_list[$key]['food_name'] = $val['food_name'];
            $food_list[$key]['district_id'] = $val['district_id'];
        }
        $add_time = time();

        $order_data['add_time'] = $add_time;
        $order_data['order_type'] = $order_type;
        $order_data['restaurant_id'] = $restaurant_id;
        $order_data['order_sn'] = $order_sn;
        $order_data['total_amount'] = $total_amount;

        $order_id = $order_model->add($order_data);

        if($order_id !== false){
            foreach($food_list as $fk => $fv){
                $food_list[$fk]['order_id'] = $order_id;
            }
            $order_food_model = order_F();
            $rel = $order_food_model->addAll($food_list);
            if($rel !== false){
                $order_model->commit();
                $returnData['code'] = 1;
                $returnData['msg'] = "下单成功";
                $returnData['order_sn'] = $order_sn;
                exit(json_encode($returnData));
            }else{
                $order_model->rollback();
                $returnData['code'] = 0;
                $returnData['msg'] = "下单失败";
                $returnData['order_sn'] = $order_sn;
                exit(json_encode($returnData));
            }
        }else{
            $order_model->rollback();
            $returnData['code'] = 0;
            $returnData['msg'] = "下单失败";
            $returnData['order_sn'] = $order_sn;
            exit(json_encode($returnData));
        }
    }

    public function no_pay_order(){
        $restaurant_id = session("restaurant_id");
        $carts = I("post.carts");
        $order_type = I("post.order_type");

        $order_model = order();
        $order_model->startTrans();
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
        $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
        $condition1['restaurant_id'] = session("restaurant_id");     //店铺id

        $num = $order_model->where($condition1)->count();        //两时间之间的订单数

        $order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单

        $food_model = D("food");
        $total_amount = 0;
        $food_list = array();
        foreach($carts as $key => $val){
            $food_where['food_id'] = $val['food_id'];
            $food_info = $food_model->where($food_where)->find();
            $food_num = $val['food_num'];
            $amount = $food_info['food_price']*$food_num;
            $total_amount += $amount;

            $food_list[$key]['food_id'] = $val['food_id'];
            $food_list[$key]['food_num'] = $val['food_num'];
            $food_list[$key]['food_price2'] = $val['food_price'];
            $food_list[$key]['food_name'] = $val['food_name'];
            $food_list[$key]['district_id'] = $val['district_id'];
        }
        $add_time = time();

        $order_data['add_time'] = $add_time;
        $order_data['order_type'] = $order_type;
        $order_data['restaurant_id'] = $restaurant_id;
        $order_data['order_sn'] = $order_sn;
        $order_data['is_no_pay'] = 1;
        $order_data['pay_type'] = 3;
        $order_data['total_amount'] = $total_amount;

        $order_id = $order_model->add($order_data);

        if($order_id !== false){
            foreach($food_list as $fk => $fv){
                $food_list[$fk]['order_id'] = $order_id;
            }
            $order_food_model = order_F();
            $rel = $order_food_model->addAll($food_list);
            if($rel !== false){
                $order_model->commit();
                $returnData['code'] = 1;
                $returnData['msg'] = "下单成功";
                $returnData['order_sn'] = $order_sn;
                exit(json_encode($returnData));
            }else{
                $order_model->rollback();
                $returnData['code'] = 0;
                $returnData['msg'] = "下单失败";
                $returnData['order_sn'] = $order_sn;
                exit(json_encode($returnData));
            }
        }else{
            $order_model->rollback();
            $returnData['code'] = 0;
            $returnData['msg'] = "下单失败";
            $returnData['order_sn'] = $order_sn;
            exit(json_encode($returnData));
        }
    }
}