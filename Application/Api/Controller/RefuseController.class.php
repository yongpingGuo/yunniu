<?php
namespace Api\Controller;

class RefuseController extends BaseController
{
    //获取订单信息
    public function get_order(){
      //\Think\Log::record('post:'.$_POST);
      $device_code = I("post.device_code");
      $this->isLogin($device_code);
      $where['restaurant_id'] = session("restaurant_id");
      $where['order_status']=array('not in','0,1,2');
      $where['order_type']=array('in','1,2');
      if($this->is_security && $where['restaurant_id']){
        if(isset($_POST['order_id'])){
          $where['order_id']=trim($_POST['order_id']);
        }elseif(isset($_POST['order_sn'])){
          $where['order_sn']=trim($_POST['order_sn']);
        }elseif(isset($_POST['zhifuhao'])){
          $where['zhifuhao']=trim($_POST['zhifuhao']);
          $where['add_time']=array('between',strtotime(date('Y-m-d')).','.strtotime(date('Y-m-d').' 23:59:59'));
        }elseif (isset($_POST['start_time'])) {
          if(isset($_POST['end_time'])){
            $where['add_time']=array('between',trim($_POST['start_time']).','.trim($_POST['end_time']));
          }else {
            $where['add_time']=array('gt',trim($_POST['start_time']));
          }
        }else {
          $where['add_time']=array('between',(strtotime(date('Y-m-d H:i:s'))-60*30).','.strtotime(date('Y-m-d H:i:s')));
        }
        $order_list=order()->where(array($where))->field('order_id,order_sn,add_time,total_amount,zhifuhao,take_num,pay_type,refuse')->order('order_id desc')->select();

          $orderFoodTab = "order_food_".date("Ym");
          $orderFoodAttrTab = "order_food_attribute_".date("Ym");
        if($order_list){
          foreach ($order_list as $key => $value) {
            $order_list[$key]['food_list']=order_F()->join("left join food on $orderFoodTab.food_id=food.food_id")
                                                          ->join('left join printer on food.print_id=printer.printer_id')
                                                          ->where(array('order_id'=>$value['order_id']))
                                                          ->field("order_food_id,$orderFoodTab.food_num,$orderFoodTab.food_price2,$orderFoodTab.food_name,refuse_num,printer_ip")
                                                          ->select();
            foreach ($order_list[$key]['food_list'] as $k => $val) {
              $order_list[$key]['food_list'][$k]['food_attribute']=order_F_A()->join("left join printer on $orderFoodAttrTab.print_id=printer.printer_id")->where(array('order_food_id'=>$val['order_food_id']))->field('food_attribute_name,food_attribute_price,printer_ip')->select();
            }
          }
          $this->ajaxReturn(array('code'=>'1','msg'=>'查询成功','data'=>$order_list));
        }else {
          $this->ajaxReturn(array('code'=>'0','msg'=>'未查找到订单'));
        }
      }else {
        $this->ajaxReturn(array('code'=>'0','msg'=>'设备信息或登录无效'));
      }
    }

    //退单
    public function refuse(){
      //\Think\Log::record('post:'.json_encode($_POST));
      $device_code = I("post.device_code");
      $this->isLogin($device_code);
      $data['restaurant_id'] = session("restaurant_id");
      if($this->is_security && $data['restaurant_id']){
        $order_id=$_POST['order_id'];
        $order_sn=$_POST['order_sn'];
        $refuse_type=$_POST['refuse_type'];
        $refuse_food_info=$_POST['refuse_food_info'];
        $reason=$_POST['refuse_reason'];
        $order_info=order()->where(array('order_id'=>$order_id,'order_sn'=>$order_sn,'refuse'=>'0','order_status'=>array('not in','0,1,2')))->field('order_id,refuse')->find();
        if($refuse_type=='1' && $order_info['refuse']=='0'){
          $refuse_all=order()->where(array('order_id'=>$order_id,'order_sn'=>$order_sn,'order_status'=>array('not in','0,1,2')))->data(array('refuse'=>'1','refuse_reason'=>$reason))->save();
          if($refuse_all){
            $result_order_info=$this->get_result_order_info($order_sn);
            $this->ajaxReturn(array('code'=>'1','msg'=>'整单退单成功','data'=>$result_order_info));
          }else {
            $this->ajaxReturn(array('code'=>'0','msg'=>'整单退单失败'));
          }
        }elseif ($refuse_type=='2') {
          $model=M();
          $model->startTrans();
          if($order_info['refuse']=='0'){
            $refuse_food=order()->where(array('order_sn'=>$order_sn,'refuse'=>'0','order_status'=>array('not in','0,1,2')))->data(array('refuse'=>'2'))->save();
            if(!$refuse_food){
              $this->ajaxReturn(array('code'=>'0','msg'=>'菜品退单失败'));
            }
          }elseif ($order_info['refuse']=='1') {
            $this->ajaxReturn(array('code'=>'0','msg'=>'菜品退单失败'));
          }
          $food_list=json_decode($refuse_food_info,true);
          $order_food_info=order_F()->where(array('order_id'=>$order_id))->field('order_food_id,food_num,refuse_num')->select();
          $order_food_id=0;
          foreach ($food_list as $key => $value) {
            foreach ($order_food_info as $k => $val) {
              if($value['order_food_id']==$val['order_food_id']){
                if($val['food_num']>=($val['refuse_num']+$value['refuse_num'])){
                  $order_food_id=$val['order_food_id'];
                  $up_order_foods=order_F()->where(array('order_id'=>$order_id,'order_food_id'=>$value['order_food_id']))->data(array('refuse_num'=>($val['refuse_num']+$value['refuse_num']),'refuse_reason'=>$reason))->save();
                  if(!$up_order_foods){
                    $model->rollback();
                    $this->ajaxReturn(array('code'=>'0','msg'=>'菜品退单失败'));
                  }
                }else {
                  $this->ajaxReturn(array('code'=>'0','msg'=>'菜品退单失败,菜品数量错误'));
                }
              }
            }
          }
          if($order_food_id==0){
            $this->ajaxReturn(array('code'=>'0','msg'=>'菜品退单失败,要退的菜品不存在'));
          }
          $model->commit();
          $result_order_info=$this->get_result_order_info($order_sn,$order_food_id);
          $this->ajaxReturn(array('code'=>'1','msg'=>'菜品退单成功','data'=>$result_order_info));
        }else {
          $this->ajaxReturn(array('code'=>'0','msg'=>'菜品退单失败'));
        }
      }else {
        $this->ajaxReturn(array('code'=>'0','msg'=>'设备信息或登录无效'));
      }
    }

    public function get_result_order_info($order_sn,$order_food_id=''){
        $yearMonthOrderTab = "order_".date("Ym");
        $orderFoodTab = "order_food_".date("Ym");
        $orderFoodAttrTab = "order_food_attribute_".date("Ym");
      $order_info=order()->join("left join restaurant on $yearMonthOrderTab.restaurant_id=restaurant.restaurant_id")
                            ->where(array('order_sn'=>$order_sn))
                            ->field("restaurant.restaurant_name,telephone1,address,order_id,order_sn,add_time,total_amount,order_type,zhifuhao,$yearMonthOrderTab.take_num,pay_type,refuse,refuse_reason")->find();
      if($order_info){
        $where['order_id']=$order_info['order_id'];
        if($order_food_id!=''){
          $where['order_food_id']=$order_food_id;
        }
        $order_info['food_list']=order_F()->join("left join food on $orderFoodTab.food_id=food.food_id")
                                                      ->join('left join printer on food.print_id=printer.printer_id')
                                                      ->where($where)
                                                      ->field("order_food_id,$orderFoodTab.food_num,$orderFoodTab.food_price2,$orderFoodTab.food_name,refuse_num,refuse_reason,printer_ip")
                                                      ->select();
        foreach ($order_info['food_list'] as $k => $val) {
          $order_info['food_list'][$k]['food_attribute']=order_F_A()->join("left join printer on $orderFoodAttrTab.print_id=printer.printer_id")->where(array('order_food_id'=>$val['order_food_id']))->field('food_attribute_name,food_attribute_price,printer_ip')->select();
        }
      }
      return $order_info;

    }
}
