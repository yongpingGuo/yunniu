<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/22
 * Time: 15:25
 */

namespace Vertical\Service;
use Component\Service\BaseGroupService;

class GroupService extends BaseGroupService
{
    private $group_id = "";

    public function __construct($group_id){
        $this->group_id = $group_id;
    }

    public function is_no_pay($order_sn){
        $bool = $this->is_group_attr($this->group_id,"no_pay",1);
        $order_model = D("order");
        if($bool){
            $where['order_sn'] = $order_sn;
            $data['order_status'] = 1;
            $order_model->where($where)->save($data);
        }
    }
}