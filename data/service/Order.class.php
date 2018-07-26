<?php
namespace data\service;

/*
*订单服务层类
*/
class Order extends BaseService{
    /*
    *获取订单列表
    */
    public function getList($where, $order = "pay_time desc") {
        Return order()->where($where)->order($order)->select();
    }
    /*
    *安卓获取订单详情
    */
    public function getInfo($order_sn) {
        $where['order_sn'] = $order_sn;
        $order_field = "order_id, add_time, order_sn, total_amount as all_price, desk_code, use_day, use_time, is_reserve,take_num";
        $order_info = order()->Field($order_field)->where($where)->find();
        if(empty($order_info['desk_code'])) $order_info['desk_code'] = '';
        if(empty($order_info)) Return false;
        $map['order_id'] = $order_info['order_id'];
        $order_food_field = "food_name as Pname, print_id as print_id1, tag_print_id,food_id, food_num, food_price2, order_food_id";
        $order_food =  order_F()->Field($order_food_field)->where($map)->select();
        $price = 0;
        foreach($order_food as $key=>$val) {
            $maps['order_food_id'] = $val['order_food_id'];
            $order_attr = order_F_A()->where($maps)->select();
            foreach($order_attr as $k=>$v) {
                if(!empty($v)){
                    $attr[$k]['Pname'] = $val['Pname'];
                    $attr[$k]['attribute_name'] = $v['food_attribute_name'];
                    $attr[$k]['tag_print_id'] = $v['tag_print_id'];
                    $attr[$k]['print_id1'] = $val['print_id1'];
                    $attr[$k]['print_id'] = $v['print_id'];
                    $attr[$k]['food_id'] = $val['food_id'];
                    $attr[$k]['food_num'] = $val['food_num'];
                    $order_info['food'][$key]['name'][] = $v['food_attribute_name'];
                    $order_info['food'][$key]['food_attrs'][] = $v['food_attribute_id'];
                }
            }
            if(empty($attr)){
                $attr = array();
                $order_info['food'][$key]['name'] = [];
                $order_info['food'][$key]['food_attrs'] = [];
            }

            $order_info['food'][$key]['fname'] = $attr;
            $order_info['food'][$key]['tag_print_id'] = $val['tag_print_id'];
            $order_info['food'][$key]['spname'] = $val['Pname'];
            $order_info['food'][$key]['print_id1'] = $val['print_id1'];
            $order_info['food'][$key]['print_id'] = $val['print_id1'];
            $order_info['food'][$key]['price'] = $val['food_price2'];
            $order_info['food'][$key]['food_num'] = $val['food_num'];
            $order_info['food'][$key]['food_id'] = $val['food_id'];
            $order_info['food'][$key]['PriceAll'] = $price + $val['food_price2'];
            unset($attr);
        }
        Return $order_info;
    }
    /*
    *获取订单主表信息
    */
    public function getPrimInfo($where) {
        $where['restaurant_id'] = $this->restaurant_id;
        Return order()->where($where)->find();
    }
    /*
    *微信获取订单详情
    */
    public function getWxInfo($order_id) {
        $where['order_id'] = $order_id;
        $info = order()->where($where)->find();
        $info['food'] = order_F()->where($where)->select();
        Return $info;
    }
    /*
    *修改订单信息
    */
    public function updateInfo($where, $data) {
        $data['update_time'] = time();
        Return order()->where($where)->save($data);
    }
    /*
    *获取下单时间设置信息
    */
    public function getSetTimeInfo($is_use = 0) {
        $where['restaurant_id'] = $this->restaurant_id;
        $info = M("order_time_set")->where($where)->find();
        if($is_use > 0) $map['is_use'] = 1;
        $map['order_timset_id'] = $info['order_timset_id'];
        $info['ext'] = M("order_time_set_ext")->where($map)->order("order_timext_id asc")->select();
        Return $info;
    }
    /*
    *订单统计判断
    */
    public function getCount($where) {
        Return order()->where($where)->count();
    }
    /*
    *添加下单时间选择设置
    */
    public function addSetTime($data) {
        $order_time_set_model =  M("order_time_set");
        $order_time_set_ext_model =  M("order_time_set_ext");
        $data_time_set = array(//主表数据构建
            'types' => $data['types'],
            'restaurant_id' => $this->restaurant_id,
            'is_today' => $data['is_today'],
            'is_tomorrow' => $data['is_tomorrow'],
            'is_free_today' => $data['is_free_today'] * 1,
            'is_free_tomorrow' =>$data['is_free_tomorrow'] * 1,
            'stop_ordering_time' => $data['stop_ordering_time'],
            'add_order_time' => $data['add_order_time'],
            'business_hours' => json_encode(array($data['start_business_hours'], $data['end_business_hours'])),
        );
        $where['restaurant_id'] = $this->restaurant_id;
        $order_timset_id = $order_time_set_model->where($where)->getField("order_timset_id");
        if($order_timset_id > 0){//更新
            $res = $order_time_set_model->where($where)->save($data_time_set);
            $map['order_timset_id'] = $order_timset_id;
            $order_time_set_ext_model->where($map)->delete();
        }else{//添加
            $res = $order_timset_id = $order_time_set_model->add($data_time_set);
        }
        for($i = 1; $i < 20; $i++){//构建具体时间数组
            if(empty($data['times_'.$i])) continue;
            $ext_data[] = array(
                'restaurant_id' => $this->restaurant_id,
                'order_timset_id' => $order_timset_id,
                'is_use' => $data['is_use_'.$i],
                'times' => $data['times_'.$i]
            );
        }
        Return $order_time_set_ext_model->addAll($ext_data);
    }
}
