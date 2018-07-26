<?php
namespace Admin\Controller;
use data\service\Order as ServiceOrder;
/*
*订单相关
*/
class OrderSetController extends BaseController
{
    private $S_Order;
    public function __construct() {
        parent::__construct();
        $this->S_Order = new ServiceOrder();
    }
    /*
    *设定下单时间
    */
    public function setTimes() {
        if(IS_POST){
            $res = $this->S_Order->addSetTime(I());
            if($res) $this->ajaxReturn(array('code'=>1, 'msg'=>'操作成功'));
             $this->ajaxReturn(array('code'=>0, 'msg'=>'请添加时间'));
        }
        $info = $this->S_Order->getSetTimeInfo();
        $info['business_hours'] = json_decode($info['business_hours']);
        $this->assign('info', $info);
        $this->display('setTimes');
    }
}
