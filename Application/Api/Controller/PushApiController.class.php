<?php
namespace Api\Controller;
use data\service\Push as ServiceTakeMeal;
use Mobile\Controller\WechatController;

/**
 *各种推送模式的API
 * 注意: push_status:1、推送了，未放餐  3、已放餐  5、已取餐   7、超时  9、取消放餐
 */
class PushApiController extends BaseController
{


    /**
     *  获取安卓收银设备的device_id，绑定对应的device_id和device_code
     *  device_code  设备码
     *  type  类型    设备类型 1放餐屏，2取餐屏，3准备中/请取餐 , 4核销屏
     *  device_id   机器对应的device_id
     */
    public function DeviceId_relation_aliPush(){
        $device_code = I("post.device_code");   // 机器码
        $device_id = I("post.device_id");   // 阿里推送所需的device_id
        $type = I("post.type");   // 设备类型 1放餐屏，2取餐屏，3准备中/请取餐
        if($device_code == null || $device_id == null || $type == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "参数值中存在空值";
            exit(json_encode($returnData));
        }

        $this->isLogin($device_code);
        if ($this->is_security) {
            $restaurant_id = session("restaurant_id");
            /***删除掉那些曾今被激活过的但是没有清除掉的其他店铺的记录（预防有些记录没有被清除掉）***/
            if($restaurant_id != null && $device_id != null){
                $where['device_id'] = $device_id;
                $where['restaurant_id'] = array("neq",$restaurant_id);
                $del = M("dc_take_meal_device")->where($where)->delete();
            }
           

            // 判断当前店铺对应的记录是否已经存在，不存在才添加
            $add['device_id'] = $device_id;
            $add['restaurant_id'] = $restaurant_id;
            $add['type'] = $type;
            $if = M("dc_take_meal_device")->where($add)->find();
            if(!$if){
                // 关联设备表的设备ID
                /*$d_condition['device_code'] = $device_code;
                $deviceInfo = M("device")->where($d_condition)->field('device_id,device_name')->find();*/
                $add['relation_device_code'] = $device_code;
                $res = M("dc_take_meal_device")->add($add);
                if($res){
                    $returnData['code'] = 1;
                    $returnData['msg'] = "绑定成功";
                    exit(json_encode($returnData));
                }else{
                    $returnData['code'] = 0;
                    $returnData['msg'] = "绑定失败";
                    exit(json_encode($returnData));
                }
            }else{
                $returnData['code'] = 1;
                $returnData['msg'] = "数据库中店铺已有此记录，无需再添加";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     * 清除取餐柜阿里推送所需的device_id的激活记录
     *  device_code  设备码
     *  device_id   机器对应的device_id
     */
    public function clean_qucangui_device_id(){
        $device_code = I("post.device_code");   // 机器码
        $device_id = I("post.device_id");   // 阿里推送所需的device_id
        if($device_code == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "device_code为空";
            exit(json_encode($returnData));
        }
        if($device_id == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "device_id为空";
            exit(json_encode($returnData));
        }

        $this->isLogin($device_code);
        if ($this->is_security) {
            $de_where['device_id'] = $device_id;
            $del = M("dc_take_meal_device")->where($de_where)->delete();
            if($del === false){
                $returnData['code'] = 0;
                $returnData['msg'] = "删除不成功，请重试";
                exit(json_encode($returnData));
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "删除device_id成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     * 获取各种设备屏的信息
     * 1放餐屏，2取餐屏，3准备中/请取餐
     * 原名getFangcanpingInfo，后改为getDeviceInfo
     */
    public function getDeviceInfo(){
        $device_code = I("post.device_code");   // 机器码
        if($device_code == null){
            $returnData['code'] = 0;
            $returnData['msg'] = "device_code为空";
            exit(json_encode($returnData));
        }
        $this->isLogin($device_code);
        if ($this->is_security) {
            $de_where['type'] = I('post.type');
            $de_where['restaurant_id'] = session('restaurant_id');
            $deviceInfo = M("dc_take_meal_device")->where($de_where)->field('status,device_name')->select();
            $returnData['code'] = 1;
            $returnData['msg'] = "成功获取数据";
            $returnData['deviceInfo'] = $deviceInfo;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     *查看订单详情api
     */
    public function askDetailInfo()
    {
        $this->validates();//检查设备码是否过期
        $order_sn = I('post.order_sn');
        if($order_sn == null){
            $returnData['code']     = 0;
            $returnData['msg']      = "订单号不能为空";
            exit(json_encode($returnData));
        }

        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('order_id,add_time,take_num,total_amount')->find();
        if(!$orderInfo){
            $orderInfo = lastOrder()->where(array('order_sn'=>$order_sn))->field('order_id,add_time,take_num,total_amount')->find();
        }

        if(empty($orderInfo)){
            $returnData['code']     = 0;
            $returnData['msg']      = "没有对应的订单信息";
            exit(json_encode($returnData));
        }
        $foodInfo = order_F()->where(array('order_id'=>$orderInfo['order_id']))->field('food_id,food_name as spname,food_price2,food_num')->select();
        foreach($foodInfo as $key => $val){
            $foodInfo[$key]['price'] = $val['food_price2']/$val['food_num'];    // 单价
            unset($foodInfo[$key]['food_price2']);
            $foodInfo[$key]['PriceAll'] = $orderInfo['total_amount'];    // 总价
        }
        // 返回数据给安卓
        $returnData['add_time'] = date("Y-m-d H:i:s",$orderInfo['add_time']);
        $returnData['foods'] = $foodInfo;
        $returnData['order_sn'] = $order_sn;
        $returnData['table_num'] = $orderInfo['take_num'];  // 取餐号
        $returnData['cancellNum'] = md5($orderInfo['cancell_num']);  // 核销号
        $returnData['code']     = 1;
        $returnData['msg']      = "获取信息成功";
        exit(json_encode($returnData));
    }


    /** 获取放餐柜柜子的device_id
     * @param: $group_name 柜子名称
     */
    public function getGroupDeviceId($group_name)
    {
        $group_where['group_name'] = $group_name;
        $group_where['restaurant_id'] = session('restaurant_id');
        $group_where['type'] = 2;
        $relation_device_code = M('dc_window_group')
            ->where($group_where)
            ->getField('device_code');
        $device_id = M('dc_take_meal_device')
            ->where(array('relation_device_code'=>$relation_device_code,'type'=>2))
            ->getField('device_id');
        return $device_id;
    }


    /**
     *  取餐验证
     *  device_code  设备码
     *  cancell_num  核销码(md5)
     *  order_sn     订单号
     */
    public function takeMealCheck()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $cancell_num = I('post.cancell_num');   // 核销号
            if($cancell_num == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "核销号不能为空";
                exit(json_encode($returnData));
            }
            $order_sn = I('post.order_sn');   // 订单号
            if($order_sn == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "订单号不能为空";
                exit(json_encode($returnData));
            }

            //数据是否在当前的表中
            $if_exist = order()->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))->find();
            if($if_exist){
                $database_cancell_num = order()
                    ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                    ->getField('cancell_num');  // 数据库里面的核销号
            }else{
                $database_cancell_num = lastOrder()
                    ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                    ->getField('cancell_num');  // 数据库里面的核销号
            }

            if(!$database_cancell_num){
                $returnData['code']     = 0;
                $returnData['msg']      = "该订单号没有对应的核销号信息";
                exit(json_encode($returnData));
            }
            if(md5($database_cancell_num) == $cancell_num){
                // 验证正确
                $returnData['code']     = 1;
                $returnData['msg']      = "验证正确";
                exit(json_encode($returnData));
            }else{
                $returnData['code']     = 0;
                $returnData['msg']      = "验证失败";
                exit(json_encode($returnData));
            }
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     *  取餐验证
     *  device_code  设备码
     *  cancell_num  核销码(md5)
     *  order_sn     订单号
     */
    public function numCheck()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $cancell_num = I('cancell_num');   // 核销号
            if($cancell_num == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "核销号不能为空";
                exit(json_encode($returnData));
            }

            //数据是否在当前的表中
            $order = order()->where(array('cancell_num'=>$cancell_num,'restaurant_id'=>session('restaurant_id')))->field('cancell_num,order_sn')->find();
            $database_cancell_num = $order['cancell_num'];

            if(!$database_cancell_num){
                $returnData['code']     = 0;
                $returnData['msg']      = "该订单号没有对应的核销号信息";
                exit(json_encode($returnData));
            }
            if($database_cancell_num == $cancell_num){
                // 验证正确
                $returnData['code']     = 1;
                $returnData['msg']      = "验证正确";
                $returnData['order_sn'] = $order['order_sn'];
                $returnData['window_nums'] = M('dc_window_info')->where(array('occupy_order_sn'=>$order['order_sn'],'type'=>2,'restaurant_id'=>session('restaurant_id')))->field('window_name')->select();
                exit(json_encode($returnData));
            }else{
                $returnData['code']     = 0;
                $returnData['msg']      = "验证失败";
                exit(json_encode($returnData));
            }
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }




    // 获取设置的超时数
    public function getTimeout(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $timeout_value = M('timeout_take_meal')->where(array('restaurant_id'=>session('restaurant_id')))->getField('timeout_value');
            if($timeout_value == null){
                $timeout_value = 0;
            }
            $returnData['code']     = 1;
            $returnData['timeout_value'] = $timeout_value;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     *  对应的餐超时，然后取出对应的餐
     *  device_code 设备码
     *  order_sn 定单号
     *  group_name  柜子号
     *  window_name 窗口号
     */
    public function whichFoodTimeout(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');
            // 对应的方餐柜/取餐柜的餐置为空
            $where = array('occupy_order_sn'=>$order_sn);
            $save = array('occupy_order_sn'=>'','status'=>1,'put_meal_time'=>''); // 柜子可用，没有被订单占用
            M('dc_window_info')->where($where)->save($save);
            // 对应的订单状态改为超时
            // push_status 7、订单超时
            $order_save['desk_code'] = 0;    // 柜子号
            $order_save['window_num'] = '';    // 窗口号
            $order_save['push_status'] = 7;    // 超时

            $if_exist = order()->where(array('order_sn'=>$order_sn))->find();
            if($if_exist){
                $res = order()->where(array('order_sn'=>$order_sn))->save($order_save);
            }else{
                $res = lastOrder()->where(array('order_sn'=>$order_sn))->save($order_save);
            }

            //  推给取餐屏
            $S_TakeMeal = new ServiceTakeMeal();
            // 推给取餐屏，根据设备号查出要推给的对应的分组的取餐屏的device_id
            $group_name = I('post.group_name'); // 柜子号
            $device_id = $this->getGroupDeviceId($group_name);
            $type = 'timeout';  // 订单超时
            $window_name = I('post.window_name');
            $push_data['qucan_window_name'] = $window_name;    // 取餐窗口名
            $push_data['cancell_num'] = order()
                ->where(array('order_sn'=>$order_sn))
                ->getField('cancell_num');
            $push_data['order_sn'] = $order_sn;
            if($device_id){
                $S_TakeMeal->pushTwoCupboard($type,$device_id,$push_data);
            }


            // 推给微信用户
            //获取订单信息
            if($if_exist){
                $to_dd = order()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();
            }else{
                $to_dd = lastOrder()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();
            }

            $openid = $to_dd['openid'];
            $data_send['first'] = '您好!';
            $data_send['OrderSn'] = $to_dd['take_num'];
            $data_send['url'] = C('HOST_NAME').'/index.php/Mobile/order/info/order_id/'.$to_dd['order_id'];
            $data_send['OrderStatus'] = '订单超时';
            $data_send['remark'] = '您的订单取餐超时，请等待重新放餐后的订单信息';
            $data_send2 = array('a'=>$data_send);
            WechatPushController::templateSend($openid,$data_send2);

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }



    /**
     *  超时的单重新放餐
     *  device_code  设备码
     *  order_sn     订单号
     *  put_meal_time  放餐时间
     *  window_id    放餐屏窗口id
     *  window_name
     *  group_name
     */
    public function timeoutFoodRePut(){
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');
            if($order_sn == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "订单号不能为空";
                exit(json_encode($returnData));
            }
            // 判断该餐是否已经放过
            $ifHavePut = M('dc_window_info')->where(array('occupy_order_sn'=>$order_sn))->find();
            if($ifHavePut){
                $returnData['code']     = 0;
                $returnData['msg']      = "该餐已经存在于取餐柜中";
                exit(json_encode($returnData));
            }

            $if_exist = order()->where(array('order_sn'=>$order_sn))->find();
            if($if_exist){
                // push_status 3、放餐屏接口同步
                $res = order()
                    ->where(array('order_sn'=>$order_sn))
                    ->save(array('push_status'=>3));
            }else{
                // push_status 3、放餐屏接口同步
                $res = lastOrder()
                    ->where(array('order_sn'=>$order_sn))
                    ->save(array('push_status'=>3));
            }

            // 哪个订单，放到了哪个窗口
            // 哪个窗口被占用
            $save = array(
                'status'=>3, // 被占用
                'occupy_order_sn'=>$order_sn, // 被占用的订单号
                'put_meal_time'=>I('post.put_meal_time'),      // 放餐时间
            );
            $window_id = I('post.window_id');   // 放餐屏窗口id
            $window_name = I('post.window_name');   // 放餐屏窗口名称
            $group_name = I('post.group_name');   // 放餐屏柜子号
            $group_ids = M('dc_window_group')->where(array('group_name'=>$group_name,'restaurant_id'=>session('restaurant_id')))->field('group_id')->select();
            $ids_arr = [];
            foreach ($group_ids as $key=>$val){
                $ids_arr[] = $val['group_id'];
            }

            $where = array(
                'window_name'=>$window_name,
                'restaurant_id'=>session('restaurant_id'),
                'group_id'=>array('in',$ids_arr),
//                'type'=>1
            );
            $res = M('dc_window_info')->where($where)->save($save);
            $order_save['desk_code'] = $group_name;    // 柜子号
            $order_save['window_num'] = $window_name;    // 窗口号

            if($if_exist){
                order()->where(array('order_sn'=>$order_sn))->save($order_save);
                $push_data['cancell_num'] = order()
                    ->where(array('order_sn'=>$order_sn))
                    ->getField('cancell_num');
            }else{
                lastOrder()->where(array('order_sn'=>$order_sn))->save($order_save);
                $push_data['cancell_num'] = lastOrder()
                    ->where(array('order_sn'=>$order_sn))
                    ->getField('cancell_num');
            }

            // 阿里推送
            $S_TakeMeal = new ServiceTakeMeal();

            // 获取取餐柜子的device_id
            $device_id = $this->getGroupDeviceId($group_name);

            $type = 'put_meal';  // 放餐屏放餐
            $push_data['qucan_window_name'] = $window_name;
            $push_data['cancell_num'] = order()
                ->where(array('order_sn'=>$order_sn))
                ->getField('cancell_num');
            $push_data['order_sn'] = $order_sn;
            if($device_id){
                $S_TakeMeal->pushTwoCupboard($type,$device_id,$push_data);
            }else{
                $returnData['code']     = 0;
                $returnData['msg']      = "取餐屏的柜子号与放餐屏不一致，或者还没设置取餐屏";
                exit(json_encode($returnData));
            }

            // 推给微信用户
            //获取订单信息
            if($if_exist){
                $to_dd = order()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();
            }else{
                $to_dd = lastOrder()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();
            }

            $openid = $to_dd['openid'];
            $data_send['first'] = '您好!';
            $data_send['OrderSn'] = $to_dd['take_num'];
            $data_send['url'] = C('HOST_NAME').'/index.php/Mobile/order/info/order_id/'.$to_dd['order_id'];
            $data_send['OrderStatus'] = '超时重新放餐';
            $data_send['remark'] = '您的预点餐订单编号：'.$to_dd['take_num'].'，已完成。请尽快前往取餐使用。柜号：'.$group_name.'柜'.$window_name.'号窗，取餐验证码：'.$push_data['cancell_num'];
            $data_send2 = array('a'=>$data_send);
            WechatPushController::templateSend($openid,$data_send2);

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }



    // 获取整个店铺柜子分组和窗口信息以及窗口对应的订单信息
    // takeOrPutWindowId去掉
    public function getGroupAndWindow()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
//            $device_code = '1c:ca:e3:3b:60:40';
            $window_group = M('dc_window_group')
                ->where(array('restaurant_id'=>session('restaurant_id'),'device_code'=>$device_code))
                ->field('group_id,group_name')
                ->select();

            $orderTab = 'order_'.date("Ym");
            $order_food = 'order_food_'.date("Ym");

            foreach ($window_group as $key=>$val){
                $window_group[$key]['window_info'] = M('dc_window_info')
                    ->alias('t1')
                    ->join("left join $orderTab t2 on t1.occupy_order_sn = t2.order_sn")
                    ->where(array('t1.group_id'=>$val['group_id']))
                    ->field('t1.window_id,t1.delay_time,t1.window_name,t1.group_id,t1.status,t1.put_meal_time,
                    t1.occupy_order_sn,t2.take_num,t2.push_status,t2.cancell_num,t2.order_id')
                    ->order("window_name asc")
                    ->select();
            }

            foreach($window_group['0']['window_info'] as $k=>$v){
                $window_group[0]['window_info'][$k]['food_info'] = order_F()->where(array('order_id'=>$v['order_id']))->field('order_food_id,food_name,food_detail')->select();
            }
            $returnData['code']     = 1;
            $returnData['info']     = $window_group;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    // 获取所有未放餐的订单信息
    public function getAllNotPutMeal()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $start=mktime(0,0,0,date("m"),date("d")-1,date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
            $where['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
            $where['order_status'] = 3;
            $where['restaurant_id'] = session('restaurant_id');
            $where['push_status'] = array('in','1,7,9');  // 未未完成的订单
            $orderInfo = order()->where($where)->field('order_status,order_sn,take_num,push_status,cancell_num,order_id')->select();
            foreach($orderInfo as $k => $v){
                $orderInfo["$k"]['food_info']= order_F()->where(array('order_id'=>$v['order_id']))->field('order_food_id,food_name,food_detail')->select();
            }
            $returnData['code']     = 1;
            $returnData['info']     = $orderInfo;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    // 核销屏模式获取所有未完成的订单信息
    public function getOrder()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $start=mktime(0,0,0,date("m"),date("d")-1,date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
            $where['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
            $where['order_status'] = 3;
            $where['restaurant_id'] = session('restaurant_id');
            $where['push_status'] = array('in','1,3,7,9');  // 未未完成的订单
            $orderInfo = order()->where($where)->field('order_status,order_sn,take_num,push_status,cancell_num,order_id,desk_code')->select();
            foreach($orderInfo as $k => $v){
                $orderInfo["$k"]['food_info']= order_F()->where(array('order_id'=>$v['order_id']))->field('order_food_id,food_name,food_detail')->select();
            }
            $returnData['code']     = 1;
            $returnData['info']     = $orderInfo;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 核销屏模式获取所有未完成的订单信息
    public function getOrderDetail()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $start=mktime(0,0,0,date("m"),date("d")-1,date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
            $where['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
            $where['order_status'] = 3;
            $where['restaurant_id'] = session('restaurant_id');
            $where['push_status'] = array('in','1,7,3,9');  // 未未完成的订单
            $orderInfo = order()->where($where)->field('order_status,order_sn,take_num,push_status,cancell_num,order_id')->select();
            foreach($orderInfo as $k => $v){
                $orderInfo["$k"]['food_info']= order_F()->where(array('order_id'=>$v['order_id']))->field('food_name,food_num,food_price2,food_num * food_price2 as food_total_price')->select();
            }
            $returnData['code']     = 1;
            $returnData['info']     = $orderInfo;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    // 取餐柜获取所有未完成的订单信息(包含菜品详情和菜品状态)
    public function getOrderInfo()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $start=mktime(0,0,0,date("m"),date("d")-1,date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
            $where['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
            $where['order_status'] = 3;
            $_SESSION['restaurant_id'] = 131;
            $where['restaurant_id'] = session('restaurant_id') ;
            $where['push_status'] = array('in','1,7,9');  // 未未完成的订单
            $orderInfo = order()->where($where)->field('order_status,order_sn,take_num,push_status,cancell_num,order_id')->select();
            $order_food = 'order_food_'.date("Ym");
            foreach($orderInfo as $k => $v){
//                $orderInfo["$k"]['food_info'] = order_F()
//                      ->alias('t1')
//                      ->join('left join dc_window_info t2 on t2.window_id = t1.window_id')
//                      ->where(array('order_id'=>$v['order_id']))
//                      ->field('t1.food_name,t1.food_status,t2.window_name as window_num')
//                      ->select();
                $orderInfo["$k"]['food_info']= order_F()->where(array('order_id'=>$v['order_id']))->field('food_name,food_detail')->select();
            }
            $returnData['code']     = 1;
            $returnData['info']     = $orderInfo;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }



    // 同步窗口分组和窗口信息新版本3
    public function windowInfoSync_three()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            // 新生成的分组信息
            $newCreateGroup = [];
            // 新生成的窗口信息
            $newCreateWindow = [];
            $groupName = I('post.groupName');   // 分组名
            $reflectNum = I('post.reflectNum'); // 对应的窗口数
            $type = I('post.type');     // 窗口类型，1放餐窗，2取餐窗
            if(!is_numeric($reflectNum)){
                $returnData['code']     = 0;
                $returnData['msg']      = "窗口数不合法";
                exit(json_encode($returnData));
            }

            $groupWhere['restaurant_id'] = session('restaurant_id');
            $groupWhere['group_name'] = $groupName;
            $groupWhere['device_code'] = $device_code;
            $groupWhere['type'] = $type;
            $group_id = I('post.group_id');
            if(!is_numeric($group_id) || $group_id == 0){
                // 分组名不能重复
                $same['restaurant_id'] = session('restaurant_id');
                $same['group_name'] = $groupName;
                $same['type'] = $type;
                $ifSame = M('dc_window_group')->where($same)->find();
                if(!empty($ifSame)){
                    $returnData['code']     = 0;
                    $returnData['msg']      = "分组名重复，请重新设置分组名1";
                    exit(json_encode($returnData));
                }

                // 同一个device_code不能重复添加
                $ifHave = M('dc_window_group')->where(array('device_code'=>$device_code))->getField('group_id');
                if($ifHave){
                    $group_id = $ifHave;
                    M('dc_window_group')->where(array('device_code'=>$device_code))->save(array('group_name'=>$groupName));
                }else{
                    // 分组名不存在，添加分组
                    $group_id = M('dc_window_group')->add($groupWhere);
                }
                $newCreateGroup[$group_id] = $groupName;
            }else{
                $same['restaurant_id'] = session('restaurant_id');
                $same['group_name'] = $groupName;
                $same['type'] = $type;
                $ifSame = M('dc_window_group')->where($same)->getField('group_id');
                if($ifSame && $group_id != $ifSame){
                    $returnData['code']     = 0;
                    $returnData['msg']      = "分组名重复，请重新设置分组名2";
                    exit(json_encode($returnData));
                }
                // 编辑分组名
                M('dc_window_group')->where(array('group_id'=>$group_id))->save(array('group_name'=>$groupName));
            }

            $reflectNum = intval($reflectNum);
            $sql = "SELECT MAX(window_name) as maxWindowName FROM dc_window_info WHERE restaurant_id = ".session('restaurant_id')." AND type = $type AND group_id = $group_id";
            $maxWindowName = M()->query($sql)[0]['maxWindowName']; // 最大的窗口数
            if($maxWindowName != null && $maxWindowName > $reflectNum){
                $windowIdsArr = [];
                $delWhere = array(
                    'window_name'=>array('gt',$reflectNum),
                    'restaurant_id'=>session('restaurant_id'),
                    'type'=>$type,
                    'group_id'=>$group_id
                );
                $windowIds = M('dc_window_info')
                    ->where($delWhere)
                    ->field('window_id')
                    ->select();
                foreach ($windowIds as $key=>$val){
                    $windowIdsArr[] = $val['window_id'];
                }
                $res = M('dc_window_info')->where(array('relation_other_id'=>array('in',$windowIdsArr)))->delete();  // 删除被关联
                // 删除多出的窗口数
                $del = M('dc_window_info')
                    ->where($delWhere)
                    ->delete();
            }

            for ($i=1;$i<=$reflectNum;$i++){
                $groupInfo['restaurant_id'] = session('restaurant_id');
                $groupInfo['type'] = $type;
                $groupInfo['window_name'] = $i;
                $groupInfo['group_id'] = $group_id;
                $window_id = M('dc_window_info')->where($groupInfo)->getField('window_id');
                if(!$window_id){
                    // 窗口名不存在，添加窗口名
                    $add_window_id = M('dc_window_info')->add($groupInfo);
                    $newCreateWindow[$add_window_id] = $i;
                }
            }
            $returnData['newCreateGroup'] = $newCreateGroup;
            $returnData['newCreateWindow'] = $newCreateWindow;
            $returnData['code']     = 1;
            $returnData['msg']      = "同步数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     *  延时放餐
     *  device_code  设备码
     *  time_num  推迟的时间数
     *  order_sn     订单号
     */
    public function delayPutMeal()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $time_num = I('post.time_num');   // 推迟的时间数,单位：秒
            if(!is_numeric($time_num)){
                $returnData['code']     = 0;
                $returnData['msg']      = "推迟的时间数不合法";
                exit(json_encode($returnData));
            }
            $order_sn = I('post.order_sn');   // 订单号
            if($order_sn == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "订单号不能为空";
                exit(json_encode($returnData));
            }
            $order_id = order()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->getField('order_id');
            if(!$order_id){
                // 避免上个月和这个月接壤的时候，这个月1号拿不了上个月的订单号
                $order_id_last = lastOrder()
                    ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                    ->getField('order_id');
                if(!$order_id_last){
                    $returnData['code']     = 0;
                    $returnData['msg']      = "该订单号没有对应的订单信息";
                    exit(json_encode($returnData));
                }
            }
            // 设置延时
            $window_where['occupy_order_sn'] = $order_sn;
            $info = M('dc_window_info')->where($window_where)->find();
            if(empty($info)){
                $returnData['code']     = 0;
                $returnData['msg']      = "该订单号没有被绑定的窗口信息";
                exit(json_encode($returnData));
            }
            $save['delay_time'] = $info['delay_time'] + $time_num;
            $res = M('dc_window_info')->where($window_where)->save($save);
            if($res === false){
                $returnData['code']     = 0;
                $returnData['msg']      = "延时放餐失败";
                exit(json_encode($returnData));
            }else{
                // 改为推送给微信用户
                if($info['delay_time'] == 0){
                    $time_num = $time_num/2;
                }
                $delay_time = $time_num/60;

                // 推给微信用户
                //获取take_num订单编号
                $to_dd = order()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();

                $openid = $to_dd['openid'];
                $data_send['first'] = '您好!';
                $data_send['OrderSn'] = $to_dd['take_num'];
                $data_send['url'] = C('HOST_NAME').'/index.php/Mobile/order/info/order_id/'.$to_dd['order_id'];
                $data_send['OrderStatus'] = '延时放餐';
                $data_send['remark'] = '您的订单('.$order_sn.')延时：'.$delay_time.'分钟。（'.date('Y-m-d H:i:s').')';

                $data_send2 = array('a'=>$data_send);
                WechatPushController::templateSend($openid,$data_send2);

                $returnData['code']     = 1;
                $returnData['msg']      = "延时放餐成功";
                exit(json_encode($returnData));
            }
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }

    }


    /**
     *  取消放餐
     *  device_code  设备码
     *  order_sn     订单号
     *  新增
     *  group_name   柜子号
     *  window_name  窗口名
     *  qucan_window_id 改成 qucan_window_name
     */
    public function cancelPutMeal()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');   // 订单号
            if($order_sn == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "订单号不能为空";
                exit(json_encode($returnData));
            }
            $order_id = order()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->getField('order_id');
            if(!$order_id){
                // 避免上个月和这个月接壤的时候，这个月1号拿不了上个月的订单号
                $order_id_last = lastOrder()
                    ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                    ->getField('order_id');
                if(!$order_id_last){
                    $returnData['code']     = 0;
                    $returnData['msg']      = "该订单号没有对应的订单信息";
                    exit(json_encode($returnData));
                }
                $res = lastOrder()
                    ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                    ->save(array('push_status'=>1));
            }

            //修改order_food表里面的食物状态
            $food_info = I('food_info');
            $window_name = I('post.window_name');
            $food_info = json_decode(htmlspecialchars_decode($food_info),true);
            foreach($food_info as $k=>$v){
                $order_food = order_F()->where(array('order_food_id'=>$v['order_food_id']))->field('food_num,food_status,food_detail')->find();
                $tmp_arr = json_decode($order_food['food_detail'],true);
                $i = 0;
                $a = 0;
                while($i != 1){
//                    echo $a.'<br>';
                    if($tmp_arr["$a"]['food_status'] == 2 && $tmp_arr[$a]['window_name'] == $window_name ){
                        $tmp_arr["$a"]['food_status'] = 1;
                        $tmp_arr[$a]['window_name'] = '';
                        $update_data['food_detail'] = json_encode($tmp_arr);
                        $update_data['food_status'] = $order_food['food_status'] - 2;
                        //取消放餐，修改数据
                        order_F()->where(array('order_food_id'=>$v['order_food_id']))->save($update_data);
                        $i++;
                        unset($tmp_arr);
                        unset($order_food);
                        unset($update_data);
                    }
                    $a++;

                }

            }

            // 状态改为取消放餐(改为未放餐)
            $res = order()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->save(array('push_status'=>1));
            // 设置延时
            $window_where['occupy_order_sn'] = $order_sn;
            $window_where['window_name'] = $window_name;
            $window_where['type'] = 1;
            $info = M('dc_window_info')->where($window_where)->find();
            if(empty($info)){
                $returnData['code']     = 0;
                $returnData['msg']      = "该订单号没有被绑定的窗口信息";
                exit(json_encode($returnData));
            }
            $save['put_meal_time'] = '';
            $save['occupy_order_sn'] = '';
            $save['status'] = 1;
            $save['delay_time'] = 0;
            unset($window_where['type']);
            $res = M('dc_window_info')->where($window_where)->save($save);
            if($res === false){
                $returnData['code']     = 0;
                $returnData['msg']      = "取消放餐失败";
                exit(json_encode($returnData));
            }else{
                // 推给微信用户
                //获取take_num订单编号
                $to_dd = order()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();
                if(!$to_dd){
                    // 避免上个月和这个月接壤的时候，这个月1号拿不了上个月的订单号
                    $to_dd = lastOrder()
                        ->where(array('order_sn'=>$order_sn))->Field('take_num,order_id,openid')->find();;
                    if(!$to_dd){
                        $returnData['code']     = 0;
                        $returnData['msg']      = "该订单号没有对应的订单信息";
                        exit(json_encode($returnData));
                    }
                }
                $openid = $to_dd['openid'];
                $data_send['first'] = '您好!';
                $data_send['OrderSn'] = $to_dd['take_num'];
                $data_send['url'] = C('HOST_NAME').'/index.php/Mobile/order/info/order_id/'.$to_dd['order_id'];
                $data_send['OrderStatus'] = '取消放餐';
                $data_send['remark'] = "您的订单(".$order_sn.")已取消放餐，接下来请留意最新的放餐信息。（".date('Y-m-d H:i:s').')';

                $data_send2 = array('a'=>$data_send);
                WechatPushController::templateSend($openid,$data_send2);


                // 推给取餐屏，根据设备号查出要推给的对应的分组的取餐屏的device_id
                $group_name = I('post.group_name'); // 分组名
                $window_name = I('post.window_name'); // 窗口名
                $relation_device_code = M('dc_window_group')
                    ->where(array('group_name'=>$group_name,'type'=>2,))
                    ->getField('device_code');
                $device_id = M('dc_take_meal_device')
                    ->where(array('relation_device_code'=>$relation_device_code,'type'=>2))
                    ->getField('device_id');
                $type = 'cancellPutMeal';  // 取消放餐
                $push_data['qucan_window_name'] = $window_name;
                $push_data['order_sn'] = $order_sn;
                // 阿里推送给取餐屏
                $S_TakeMeal = new ServiceTakeMeal();
                if($device_id){
                    $S_TakeMeal->pushTwoCupboard($type,$device_id,$push_data);
                }else{
                    $returnData['code']     = 0;
                    $returnData['msg']      = "取餐屏的柜子号与放餐屏不一致，或者还没设置取餐屏";
                    exit(json_encode($returnData));
                }

                //推送给柜叫号
                $restaurant_id = $_SESSION['restaurant_id'];
                $devices_ids_gjh = M('dc_take_meal_device')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
                $S_TakeMeal->guiJiaoHao($order_sn,$devices_ids_gjh,2,$window_name);

                $returnData['code']     = 1;
                $returnData['msg']      = "取消放餐成功";
                exit(json_encode($returnData));
            }
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }



    /**
     *  根据设备号获取分组id????
     * 方法名getdeviceId改为getGroupId
     */
    public function getGroupId()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $group_id = M('dc_window_group')->where(array('device_code'=>$device_code))->getField('group_id');
            $returnData['code']     = 1;
            $returnData['group_id']     = $group_id;
            $returnData['msg']      = "获取数据成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }



    /**
     *  定时删除窗口与订单的绑定关系
     *  fromLinux  founpad
     */
    public function timingDel()
    {
        if(I('fromLinux') != 'founpad'){
            exit('非法访问');
        }
        // 更改放餐屏设备被占用情况
        $save = array(
            'status'=>1, // 可用
            'occupy_order_sn'=>'', // 此柜子改为没有被占用的订单号
            'put_meal_time'=>'',      // 放餐时间
            'delay_time'=>'',      // 放餐时间
        );
        $occupy_where = array('status'=>3);
        $res = M('dc_window_info')->where($occupy_where)->save($save);
        $fangcan_device_ids = M('dc_take_meal_device')->where(array('type'=>1))->field('device_id')->select();
        $qucan_device_ids = M('dc_take_meal_device')->where(array('type'=>2))->field('device_id')->select();
        $S_TakeMeal = new ServiceTakeMeal();
        // 推给放餐屏
        $S_TakeMeal->pushFangcanWhenDelBind($fangcan_device_ids);
        // 推给取餐屏
        $S_TakeMeal->pushqucanWhenDelBind($qucan_device_ids);
    }



    public function delOrderData()
    {
        if(I('fromLinux') != 'founpad'){
            exit('非法访问');
        }
        // 更改放餐屏设备被占用情况
        $save = array(
            'push_status'=>5, // 可用
        );
        $occupy_where['push_status'] = array('in'=>'1,3,7,9');
        $res = order()->where($occupy_where)->save($save);
        if($res){
            $device_ids_3 = M('dc_take_meal_device')->where(array('type'=>3))->field('device_id')->select();
            $device_ids_4 = M('dc_take_meal_device')->where(array('type'=>4))->field('device_id')->select();
            $device_ids_5 = M('dc_take_meal_device')->where(array('type'=>5))->field('device_id')->select();

            $S_TakeMeal = new ServiceTakeMeal();
            // 推给核销屏
            $S_TakeMeal->delOrderData($device_ids_3,3);
            $S_TakeMeal->delOrderData($device_ids_4,4);
            $S_TakeMeal->delOrderData($device_ids_5,5);
        }


    }





    /**************************************************以上都是跟同步推送无关的接口，下面两个接口跟同步推送有关***************************************************/



    /**
     *  放餐后订单同步且阿里推送
     *  device_code  设备码
     *  order_sn     订单号
     *  put_meal_time  放餐时间
     *  window_id    放餐屏窗口id
     * 多加：window_name,group_name
     * 不传：取餐窗口id  qucan_window_id，放餐窗口fangcan_window_id
     */
    public function fangcanInterface()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');
            if($order_sn == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "订单号不能为空";
                exit(json_encode($returnData));
            }

            $push = new ServiceTakeMeal();
            $push_type = $push->pushType();
            if($push_type == 2){ //核销屏的推送模式
                $this->screen_fc($order_sn);
            }elseif($push_type == 3){ //取餐柜的推送模式
                $this->cupboard_fc($order_sn);
            }else{
                exit('默认模式不能请求该接口');
            }

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     *  取餐后订单同步且阿里推送
     *  device_code  设备码
     *  fangcan_window_id  放餐窗口id
     *  order_sn     订单号
     *  放餐屏窗口id不用推，放餐屏窗口名要推
     *  推送放餐device_id换成窗口名
     */
    public function qucanInterface()
    {
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $order_sn = I('post.order_sn');
            if($order_sn == null){
                $returnData['code']     = 0;
                $returnData['msg']      = "订单号不能为空";
                exit(json_encode($returnData));
            }

            $push = new ServiceTakeMeal();
            $push_type = $push->pushType();
            if($push_type == 2){ //核销屏的推送模式
                $this->screen_qc();
            }elseif($push_type == 3){ //取餐柜的推送模式
                $this->cupboard_qc();
            }else{
                exit('默认模式不能请求该接口');
            }

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    /**
     *放餐柜放餐的业务逻辑
     */
    public function cupboard_fc($order_sn)
    {
        // 获取取餐柜子的device_id
        $group_name = I('post.group_name');   // 放餐屏柜子号
        $device_id = $this->getGroupDeviceId($group_name);
        if(!$device_id){
            $returnData['code']     = 0;
            $returnData['msg']      = "取餐屏的柜子号与放餐屏不一致，或者还没设置取餐屏";
            exit(json_encode($returnData));
        }


        // 哪个订单，放到了哪个窗口
        // 哪个窗口被占用
        $save = array(
            'status'=>3, // 被占用
            'occupy_order_sn'=>$order_sn, // 被占用的订单号
            'put_meal_time'=>I('post.put_meal_time'),      // 放餐时间
        );
        $window_id = I('post.window_id');   // 放餐屏窗口id
        $window_name = I('post.window_name');   // 放餐屏窗口名称
        $group_ids = M('dc_window_group')->where(array('group_name'=>$group_name,'restaurant_id'=>session('restaurant_id')))->field('group_id')->select();
        $ids_arr = [];
        foreach ($group_ids as $key=>$val){
            $ids_arr[] = $val['group_id'];
        }

        $where = array(
            'window_name'=>$window_name,
            'restaurant_id'=>session('restaurant_id'),
            'group_id'=>array('in',$ids_arr),
//                'type'=>1
        );
        $res = M('dc_window_info')->where($where)->save($save);
        $order_save['desk_code'] = $group_name;    // 柜子号
        $order_save['window_num'] = $window_name;    // 窗口号

        //数据是否存于当前月份表
        $if_exist = order()->where(array('order_sn'=>$order_sn))->find();
        if($if_exist){
            order()->where(array('order_sn'=>$order_sn))->save($order_save);
        }else{
            lastOrder()->where(array('order_sn'=>$order_sn))->save($order_save);
        }

        //修改order_food表的数据
        $order = order()->where(array('order_sn'=>$order_sn))->field('order_id')->find();
        $order_food = I('food_info');
        $order_food = json_decode(htmlspecialchars_decode($order_food),true);
        foreach($order_food as $k=>$v){
            $order_food_map['food_name'] = $v['food_name'];
            $order_food_map['order_id'] = $order['order_id'];
            $order_food_map['order_food_id'] = $v['order_food_id'];
            /************************************************/
            //先查询看看这个菜品的food_detail是否为空
            $order_f_res = order_F()->where($order_food_map)->field('food_detail,food_status')->find();
            if(!empty($order_f_res['food_detail'])){
                $arr_tmp = json_decode($order_f_res['food_detail'],true);
                $num = count($arr_tmp);
                $arr_tmp["$num"]['food_status'] = $v['food_status'];//遍历的数据
                $arr_tmp["$num"]['window_name'] = $window_name;
                array_shift($arr_tmp);//让第一个元素出队
                $save_['food_detail'] = json_encode($arr_tmp,true);
            }

            $save_['food_status'] = $order_f_res['food_status'] + 2;
            order_F()->where($order_food_map)->save($save_);

            unset($save_);
            unset($order_f_res);
        }

        //判断该订单是否全部放餐完毕
        $count = order_F()->where(array('order_id'=>$order['order_id']))->sum('food_num');
        $status_sum = order_F()->where(array('order_id'=>$order['order_id']))->sum('food_status');
//        file_put_contents('./test_data.log',"count:$count,status_sum:$status_sum,余：$status_sum/$count");
        if($status_sum/$count == 2){

            if($if_exist){
                // push_status 3、放餐屏接口同步 (push_status = 3表示放餐)
                $res = order()
                    ->where(array('order_sn'=>$order_sn))
                    ->save(array('push_status'=>3));
            }else{
                $res = lastOrder()
                    ->where(array('order_sn'=>$order_sn))
                    ->save(array('push_status'=>3));
            }
        }


        // 阿里推送
        $S_TakeMeal = new ServiceTakeMeal();
        // 推给取餐屏，根据设备号查出要推给的对应的分组的取餐屏的device_id

        // 获取取餐柜子的device_id
        $device_id = $this->getGroupDeviceId($group_name);

        $type = 'put_meal';  // 放餐屏放餐
        $push_data['qucan_window_name'] = $window_name;
        $push_data['cancell_num'] = order()
            ->where(array('order_sn'=>$order_sn))
            ->getField('cancell_num');
        $push_data['order_sn'] = $order_sn;
        if($device_id){
            $S_TakeMeal->pushTwoCupboard($type,$device_id,$push_data);
        }

        //推送给柜叫号
        $restaurant_id = $_SESSION['restaurant_id'];
        $devices_ids_gjh = M('dc_take_meal_device')->where(array('type'=>5,'restaurant_id'=>$restaurant_id))->field('device_id')->select();
        $S_TakeMeal->guiJiaoHao($order_sn,$devices_ids_gjh,'put_meal',$window_name);

        // 改为推送给微信用户
        //获取take_num订单编号
        if($if_exist){
            $to_dd = order()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id')->find();
            $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
        }else{
            $to_dd = lastOrder()->where(array('order_sn'=>$order_sn))->Field('take_num,order_id')->find();
            $user_id = lastOrder()->where(array('order_sn'=>$order_sn))->getField('openid');
        }

        $openid = $user_id;
        $data_send['first'] = '您好!';
        $data_send['OrderSn'] = $to_dd['take_num'];
        $data_send['url'] = C('HOST_NAME').'/index.php/Mobile/order/info/order_id/'.$to_dd['order_id'];
        $data_send['OrderStatus'] = '已放餐';
        $data_send['remark'] = "您的预点餐订单编号：".$to_dd['take_num']."，已完成。请尽快前往取餐使用。柜号：".$group_name."柜".$window_name."号窗，取餐验证码：".$push_data['cancell_num'];

        $data_send2 = array('a'=>$data_send);
        WechatPushController::templateSend($openid,$data_send2);
    }


    /**
     *核销屏放餐的业务逻辑
     */
    public function screen_fc($order_sn)
    {
        // 判断该餐是否已经放过
        $ifHavePut = order()->where(array('order_sn'=>$order_sn))->getField('push_status');

        if($ifHavePut == 3){
            $returnData['code']     = 0;
            $returnData['msg']      = "该订单已放餐";
            exit(json_encode($returnData));
        }

        // push_status 3、放餐屏接口同步 (push_status = 3表示放餐)
        $res = order()->where(array('order_sn'=>$order_sn))->save(array('push_status'=>3));

        //避免订单数据在上一个月份
        if(!$ifHavePut){
            $ifHavePut = lastOrder()->where(array('order_sn'=>$order_sn))->getField('push_status');
            if($ifHavePut == 3){
                $returnData['code']     = 0;
                $returnData['msg']      = "该订单已放餐";
                exit(json_encode($returnData));
            }
            $res = lastOrder()->where(array('order_sn'=>$order_sn))->save(array('push_status'=>3));
        }


        if(!$res){
            $returnData['code']     = 0;
            $returnData['msg']      = "订单更新失败";
            exit(json_encode($returnData));
        }


        // 获取核销屏的device_id
        $device_id = M('dc_take_meal_device')->where(array('type'=>3,'restaurant_id'=>session('restaurant_id')))->field('device_id')->select();
        if(!$device_id){
            $returnData['code']     = 0;
            $returnData['msg']      = "该店铺没有设置好设备";
            exit(json_encode($returnData));
        }


        // 阿里推送
        $S_TakeMeal = new ServiceTakeMeal();
        // 推给取餐屏，根据设备号查出要推给的对应的分组的取餐屏的device_id

        $type = 'put_meal';  // 放餐屏放餐
        $push_data['cancell_num'] = order()
            ->where(array('order_sn'=>$order_sn))
            ->getField('cancell_num');
        $push_data['order_sn'] = $order_sn;
        if($device_id){
            $S_TakeMeal->pushTwoScreen($type,$device_id,$push_data);
        }


        // 改为推送给微信用户
        $to_dd['take_num'] = order()->where(array('order_sn'=>$order_sn))->getField('take_num');
        $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
        $msg3 = "您的预点餐订单编号：".$to_dd['take_num']."，已完成。请尽快前往取餐使用";

    }


    /**
     *放餐柜取餐后的业务逻辑
     */
    public function cupboard_qc()
    {
        $window_nums = $_POST['window_name'];
        $window_nums = json_decode($window_nums,true);
        $occupy_order_sn = I('order_sn');  // 订单号

        //处理取餐成功的菜品状态
//        $window_nums = I('window_name');
//        $window_nums = json_decode(htmlspecialchars_decode($window_nums),true);
        foreach($window_nums as $k2=>$v2){
            $window_arr[] = $v2['window_name'];
        }

        $occupy_order_sn = I('order_sn');  // 订单号
        $order_id = order()->where(array('order_sn'=>$occupy_order_sn))->getField('order_id');
        $data = order_F()->where(array('order_id'=>$order_id))->field('food_detail,order_food_id')->select();

        $ii = 0;
        foreach($data as $key=>$val){
            $food_detail = json_decode($val['food_detail'],true);
            foreach($food_detail as $k1=>$v1){
                if($v1['food_status'] == 2 && in_array($v1['window_name'],$window_arr)){
                    $food_detail["$k1"]['food_status'] = 3; //food_status为3表示已取餐
                    $ii++;        //累计取餐次数
                }
            }
            $json_tmp = json_encode($food_detail);
            //需要修改order_food的数据
            $updata_arr['food_detail'] = $json_tmp;
            $map['order_food_id'] = $val['order_food_id'];
            order_F()->where($map)->save($updata_arr);

        }

        //把累计取餐的次数修改order表
        $food_count = order()->where(array('order_sn'=>$occupy_order_sn))->getField('food_count');
        $order_save['food_count'] = $food_count + $ii;
        order()->where(array('order_sn'=>$occupy_order_sn))->save($order_save);

        /*************************************ddd******************************************************/


        $food_count = order()->where(array('order_sn'=>$occupy_order_sn))->getField('food_count');  //累计的取餐数量
        $food_all_num = order_F()->where(array('order_id'=>$order_id))->sum('food_num');    //菜品数量

        if($food_all_num == $food_count){
            order()->where(array('order_sn'=>$occupy_order_sn,'restaurant_id'=>session('restaurant_id')))->save(array('push_status'=>5,'cancell_num'=>''));
        }

        // 更改放餐屏设备被占用情况
        $save = array(
            'status'=>1, // 可用
            'occupy_order_sn'=>'', // 此柜子改为没有被占用的订单号
            'put_meal_time'=>'',      // 放餐时间
            'delay_time'=>'',      // 延时放餐时间
        );

        // 更改取餐屏和放餐屏的设备占用情况
        M('dc_window_info')->where(array('occupy_order_sn'=>$occupy_order_sn))->save($save);

        // 阿里推送
        $group_name = I('group_name'); // 柜子号
        $d_code = M('dc_window_group')->where(array('group_name'=>$group_name,'type'=>1,'restaurant_id'=>session('restaurant_id')))->getField('device_code');
        //获取阿里device_id
        $fangcan_device_id = M('dc_take_meal_device')->where(array('relation_device_code'=>$d_code ,'type' => 1))->getField('device_id');
        $S_TakeMeal = new ServiceTakeMeal();
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        if(empty($fangcan_device_id)){
            $returnData['code']     = 0;
            $returnData['msg']      = "放餐屏的柜子号与取餐屏不一致，或者还没设置放餐屏";
            exit(json_encode($returnData));
        }
        $window_name = I('window_name');
        $S_TakeMeal->pushThreeCupboard($occupy_order_sn,$fangcan_device_id,$window_name);

        //推送给柜叫号
        $restaurant_id = $_SESSION['restaurant_id'];
        $devices_ids_gjh = M('dc_take_meal_device')->where(array('type'=>5,'restaurant_id'=>$restaurant_id))->field('device_id')->select();
        $S_TakeMeal->guiJiaoHao($occupy_order_sn,$devices_ids_gjh,2,$window_name);
    }


    /**
     *核销屏取餐后的业务逻辑
     */
    public function screen_qc()
    {
        $order_sn = I('order_sn');  // 订单号
        $device_id = M('dc_take_meal_device')->where(array('type'=>3,'restaurant_id'=>session('restaurant_id')))->field('device_id')->select();
        if(empty($device_id)){
            $returnData['code']     = 0;
            $returnData['msg']      = "该店铺还没有绑定叫号屏的设备id";
            exit(json_encode($returnData));
        }

        $if_exist = order()->where(array('order_sn'=>$occupy_order_sn,'restaurant_id'=>session('restaurant_id')))->find();
        if($if_exist){
            // push_status 5、取餐屏接口同步
            $res1 = order()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->save(array('push_status'=>5,'cancell_num'=>''));
        }else{
            // push_status 5、取餐屏接口同步
            $res1 = lastOrder()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->save(array('push_status'=>5,'cancell_num'=>''));
        }

        if(!$res1){
            $returnData['code']     = 0;
            $returnData['msg']      = "订单更新失败";
            exit(json_encode($returnData));
        }

        // 阿里推送
        $S_TakeMeal = new ServiceTakeMeal();
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 推给放餐屏核销
        if(empty($device_id)){
            $returnData['code']     = 0;
            $returnData['msg']      = "放餐屏的柜子号与取餐屏不一致，或者还没设置放餐屏";
            exit(json_encode($returnData));
        }
        $S_TakeMeal->pushThreeScreen($order_sn,$device_id);

    }
    /**
     *点餐窗口组删除
     */
    public function del_window(){
        $device_code = I("post.device_code");   // 机器码
        $this->isLogin($device_code);
        if ($this->is_security) {
        $restaurant_id = session("restaurant_id");
        $data['device_code']=$device_code;
        $data['restaurant_id']=$restaurant_id;
        $dc_window_group=M('dc_window_group');
        $window_group=$dc_window_group->where($data)->field('group_id')->select();
        if(empty($window_group))
        {
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不存在窗口组";
            exit(json_encode($returnData));
        }
        $dc_window_group->startTrans();
        $del_window_group=$dc_window_group->where($data)->delete();
        if($del_window_group==false)
        {
           $returnData['code'] = 0;
           $returnData['msg'] = "删除失败请重新删除";
           exit(json_encode($returnData));
        }
        foreach ($window_group as $key => $value) {
            $window_group_id=$value['group_id'].',';

        }
        $window_group_id=substr($window_group_id,0,strlen($window_group_id)-1);
        $window_group_data['group_id']=array('in',$window_group_id);
        $window_group_data['restaurant_id']=$restaurant_id;
        $del_window_info=M('dc_window_info')->where($window_group_data)->delete();
        if($del_window_info===false)
        {
           $dc_window_group->rollback();
           $returnData['code'] = 0;
           $returnData['msg'] = "删除失败请重新删除";
           exit(json_encode($returnData));
        }
        $dc_window_group->commit();
        $returnData['code'] = 1;
        $returnData['msg'] = "删除成功";
        exit(json_encode($returnData));
        }
        else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    //查询
    public function aaa()
    {
        $window_nums = I('window_name');
        $window_nums = json_decode(htmlspecialchars_decode($window_nums),true);
        foreach($window_nums as $k2=>$v2){
            $window_arr[] = $v2['window_name'];
        }

        $occupy_order_sn = I('order_sn');  // 订单号
        $order_id = order()->where(array('order_sn'=>$occupy_order_sn))->getField('order_id');
        $data = order_F()->where(array('order_id'=>$order_id))->field('food_detail,order_food_id ')->select();

        $ii = 0;
        foreach($data as $key=>$val){
            $food_detail = json_decode($val['food_detail'],true);
            foreach($food_detail as $k1=>$v1){
                if($v1['food_status'] == 2 && in_array($v1['window_name'],$window_arr)){
                    $food_detail["$k1"]['food_status'] = 3; //food_status为3表示已取餐
                    $ii++;        //累计取餐次数

                }
            }
            $json_tmp = json_encode($food_detail);

            //需要修改order_food的数据
            $updata_arr['food_status'] = $json_tmp;
            $map['order_food_id'] = $val['order_food_id'];
//            order_F()->where($map)->save($updata_arr);

        }

        //把累计取餐的次数修改order表
        $food_count = order()->where(array('order_sn'=>$occupy_order_sn))->getField('food_count');
        echo $food_count.'<br>';
        $order_save['food_count'] = $food_count + $ii;
//        order()->where(array('order_sn'=>$occupy_order_sn))->save($order_save);
        echo $ii;
    }

    public function bbb()
    {
        $a = $_POST['window_name'];
        dump($a);
        $window_nums = json_decode(htmlspecialchars_decode($a),true);
        dump($window_nums);
        $window_nums = json_decode($a,true);
        dump($window_nums);
    }

    public function pushtest($order_sn,$type){
        $table_time=date("Y").date("m");
        $sql="SELECT * from order_food_$table_time where (order_id=(SELECT order_id from order_$table_time where order_sn='$order_sn') and food_status/food_num=2)";
        $order_details_arr=M()->query($sql);
        $push_data['food_info']=array();
        foreach ($order_details_arr as $key => $value) {
            $push_data['food_info'][$value['order_food_id']]['food_name']=$value['food_name'];
            $push_data['food_info'][$value['order_food_id']]['food_detail']=$value['food_detail'];
            $push_data['food_info'][$value['order_food_id']]['order_food_id']=$value['order_food_id'];

        }
        sort($push_data['food_info']);
        dump($push_data['food_info']);


    }












}