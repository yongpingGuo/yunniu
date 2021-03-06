<?php
namespace Api\Controller;

use data\service\TakeMeal as ServiceTakeMeal;
use data\service\DingDing;

class TakeMealBoxController extends BaseController
{
    // push_status:1、推送了，未放餐  3、已放餐  5、已取餐   7、超时  9、取消放餐

    /**
     *  获取安卓收银设备的device_id，用于取餐柜
     *  device_code  设备码
     *  type  类型
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
                $del = M("take_meal_device")->where($where)->delete();
            }
            /***删除掉那些曾今被激活过的但是没有清除掉的其他店铺的记录（预防有些记录没有被清除掉）***/

            // 判断当前店铺对应的记录是否已经存在，不存在才添加
            $add['device_id'] = $device_id;
            $add['restaurant_id'] = $restaurant_id;
            $add['type'] = $type;
            $if = M("take_meal_device")->where($add)->find();
            if(!$if){
                // 关联设备表的设备ID
                /*$d_condition['device_code'] = $device_code;
                $deviceInfo = M("device")->where($d_condition)->field('device_id,device_name')->find();*/
                $add['relation_device_code'] = $device_code;
                $res = M("take_meal_device")->add($add);
                if($res){
                    $returnData['code'] = 1;
                    $returnData['msg'] = "绑定成功";
                    exit(json_encode($returnData));
                }else{
                    $returnData['code'] = 0;
                    $returnData['msg'] = "绑定失败";
                    exit(json_encode($returnData));
                }
            }
            $returnData['code'] = 1;
            $returnData['msg'] = "数据库中店铺已有此记录，无需再添加";
            exit(json_encode($returnData));
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
            $del = M("take_meal_device")->where($de_where)->delete();
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
     * 获取各种屏信息
     * 1放餐屏，2取餐屏，3准备中/请取餐
     */
    public function getFangcanpingInfo(){
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
            $deviceInfo = M("take_meal_device")->where($de_where)->field('status,device_name')->select();
            $returnData['code'] = 1;
            $returnData['msg'] = "删除device_id成功";
            $returnData['deviceInfo'] = $deviceInfo;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备不合法，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 取餐柜取得详细订单信息
    public function askDetailInfo()
    {
        $this->validates();
        $order_sn = I('post.order_sn');
        if($order_sn == null){
            $returnData['code']     = 0;
            $returnData['msg']      = "订单号不能为空";
            exit(json_encode($returnData));
        }
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('order_id,add_time,take_num,total_amount')->find();
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

    /**
     *  放餐屏推送的接口同步
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
            // 判断该餐是否已经放过
            $ifHavePut = M('window_info')->where(array('occupy_order_sn'=>$order_sn))->find();
            if($ifHavePut){
                $returnData['code']     = 0;
                $returnData['msg']      = "该餐已经存在于取餐柜中";
                exit(json_encode($returnData));
            }
            // 获取取餐柜子的device_id
            $group_name = I('post.group_name');   // 放餐屏柜子号
            $device_id = $this->getGroupDeviceId($group_name);
            if(!$device_id){
                $returnData['code']     = 0;
                $returnData['msg']      = "取餐屏的柜子号与放餐屏不一致，或者还没设置取餐屏";
                exit(json_encode($returnData));
            }

            // push_status 3、放餐屏接口同步
            $res = order()
                ->where(array('order_sn'=>$order_sn))
                ->save(array('push_status'=>3));
            // 哪个订单，放到了哪个窗口
            // 哪个窗口被占用
            $save = array(
                'status'=>3, // 被占用
                'occupy_order_sn'=>$order_sn, // 被占用的订单号
                'put_meal_time'=>I('post.put_meal_time'),      // 放餐时间
            );
            $window_id = I('post.window_id');   // 放餐屏窗口id
            $window_name = I('post.window_name');   // 放餐屏窗口名称
            $group_ids = M('window_group')->where(array('group_name'=>$group_name,'restaurant_id'=>session('restaurant_id')))->field('group_id')->select();
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
            $res = M('window_info')->where($where)->save($save);
            $order_save['desk_code'] = $group_name;    // 柜子号
            $order_save['window_num'] = $window_name;    // 窗口号
            order()->where(array('order_sn'=>$order_sn))->save($order_save);

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
                $S_TakeMeal->pushQucanping($type,$device_id,$push_data);
            }

            // 推给钉钉用户
            //获取take_num订单编号
            $to_dd['take_num'] = order()->where(array('order_sn'=>$order_sn))->getField('take_num');
            $dingDing = new DingDing();
            $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
            // 调用了阿里推送后，时间会变成比当前时间少八个小时
//            $msg = '您的核销号是：'.$push_data['cancell_num'].'。请到'.$group_name.'号柜子，'.$window_name.'号窗口取餐。（'.date('Y-m-d H:i:s',time()+3600*8).')';
//            $msg2 = '预点餐订单号:'.$push_data['order_sn'].',取餐码：'.$push_data['cancell_num'].',请尽快到'.$group_name.'柜'.$window_name.'号窗取餐食用';

            $msg3 = "您的预点餐订单编号：".$to_dd['take_num']."，已完成。请尽快前往取餐使用。柜号：".$group_name."柜".$window_name."号窗，取餐验证码：".$push_data['cancell_num'];
            $dingDing->sendUserMsg($user_id, $msg3);

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    // 获取放餐柜柜子的device_id
    public function getGroupDeviceId($group_name)
    {
        $group_where['group_name'] = $group_name;
        $group_where['restaurant_id'] = session('restaurant_id');
        $group_where['type'] = 2;
        $relation_device_code = M('window_group')
            ->where($group_where)
            ->getField('device_code');
        $device_id = M('take_meal_device')
            ->where(array('relation_device_code'=>$relation_device_code))
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
            $database_cancell_num = order()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->getField('cancell_num');  // 数据库里面的核销号
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
     *  取餐屏推送的接口同步
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
            $occupy_order_sn = I('order_sn');  // 订单号

            $window_name = I('window_name');   // 窗口名
            $group_name = I('group_name'); // 柜子号
            $group_ids = M('window_group')->where(array('group_name'=>$group_name,'restaurant_id'=>session('restaurant_id')))->field('group_id')->select();
            $ids_arr = [];
            foreach ($group_ids as $key=>$val){
                $ids_arr[] = $val['group_id'];
            }
            $occupy_where = array(
                'window_name'=>$window_name,
                'restaurant_id'=>session('restaurant_id'),
                'group_id'=>array(array('in',$ids_arr)),
            );
            $occupy_where['type'] = 1;
            $group_id = M('window_info')->where($occupy_where)->getField('group_id');
            $d_code = M('window_group')->where(array('group_id'=>$group_id))->getField('device_code');
            $fangcan_device_id = M('take_meal_device')->where(array('relation_device_code'=>$d_code))->getField('device_id');
            if(empty($fangcan_device_id)){
                $returnData['code']     = 0;
                $returnData['msg']      = "放餐屏的柜子号与取餐屏不一致，或者还没设置放餐屏";
                exit(json_encode($returnData));
            }


            // push_status 5、取餐屏接口同步
            $res1 = order()
                ->where(array('order_sn'=>$occupy_order_sn,'restaurant_id'=>session('restaurant_id')))
                ->save(array('push_status'=>5,'cancell_num'=>''));
            // 更改放餐屏设备被占用情况
            $save = array(
                'status'=>1, // 可用
                'occupy_order_sn'=>'', // 此柜子改为没有被占用的订单号
                'put_meal_time'=>'',      // 放餐时间
                'delay_time'=>'',      // 延时放餐时间
            );
            /*$window_name = I('post.window_name');   // 窗口名
            $group_name = I('post.group_name'); // 柜子号
            $group_ids = M('window_group')->where(array('group_name'=>$group_name,'restaurant_id'=>session('restaurant_id')))->field('group_id')->select();
            $ids_arr = [];
            foreach ($group_ids as $key=>$val){
                $ids_arr[] = $val['group_id'];
            }

            $occupy_where = array(
                'window_name'=>$window_name,
                'restaurant_id'=>session('restaurant_id'),
                'group_id'=>array(array('in',$ids_arr)),
            );*/

            unset($occupy_where['type']);
            // 更改取餐屏和放餐屏的设备占用情况
            $res2 = M('window_info')->where($occupy_where)->save($save);
            // 更改取餐屏的设备占用情况

            // 阿里推送
            $S_TakeMeal = new ServiceTakeMeal();
            // 1、放餐屏，2、取餐屏，3、准备中/请取餐
            // 推给放餐屏核销
            /*$occupy_where['type'] = 1;
            $group_id = M('window_info')->where($occupy_where)->getField('group_id');
            $device_code = M('window_group')->where(array('group_id'=>$group_id))->getField('device_code');
            $fangcan_device_id = M('take_meal_device')->where(array('relation_device_code'=>$device_code))->getField('device_id');*/
            if(empty($fangcan_device_id)){
                $returnData['code']     = 0;
                $returnData['msg']      = "放餐屏的柜子号与取餐屏不一致，或者还没设置放餐屏";
                exit(json_encode($returnData));
            }
            $S_TakeMeal->pushFangcan($occupy_order_sn,$fangcan_device_id,$window_name);
            // 修改钉钉用户订单

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
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
            M('window_info')->where($where)->save($save);
            // 对应的订单状态改为超时
            // push_status 7、订单超时
            $order_save['desk_code'] = 0;    // 柜子号
            $order_save['window_num'] = '';    // 窗口号
            $order_save['push_status'] = 7;    // 超时
            $res = order()->where(array('order_sn'=>$order_sn))->save($order_save);

            //  推给取餐屏
            // 阿里推送

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
                $S_TakeMeal->pushQucanping($type,$device_id,$push_data);
            }

            // 推给钉钉用户，此单超时
            $dingDing = new DingDing();
            $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
            $msg = '您的订单取餐超时，请等待重新放餐后的订单信息';
            $dingDing->sendUserMsg($user_id, $msg);

            $returnData['code']     = 1;
            $returnData['msg']      = "同步成功";
            exit(json_encode($returnData));
        } else {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }

    /*
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
            $ifHavePut = M('window_info')->where(array('occupy_order_sn'=>$order_sn))->find();
            if($ifHavePut){
                $returnData['code']     = 0;
                $returnData['msg']      = "该餐已经存在于取餐柜中";
                exit(json_encode($returnData));
            }

            // push_status 3、放餐屏接口同步
            $res = order()
                ->where(array('order_sn'=>$order_sn))
                ->save(array('push_status'=>3));
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
            $group_ids = M('window_group')->where(array('group_name'=>$group_name,'restaurant_id'=>session('restaurant_id')))->field('group_id')->select();
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
            $res = M('window_info')->where($where)->save($save);
            $order_save['desk_code'] = $group_name;    // 柜子号
            $order_save['window_num'] = $window_name;    // 窗口号
            order()->where(array('order_sn'=>$order_sn))->save($order_save);

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
                $S_TakeMeal->pushQucanping($type,$device_id,$push_data);
            }else{
                $returnData['code']     = 0;
                $returnData['msg']      = "取餐屏的柜子号与放餐屏不一致，或者还没设置取餐屏";
                exit(json_encode($returnData));
            }

            // 推给钉钉用户
            $to_dd['take_num'] = order()
                ->where(array('order_sn'=>$order_sn))
                ->getField('take_num');

            $dingDing = new DingDing();
            $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
            // 调用了阿里推送后，时间会变成比当前时间少八个小时
//            $msg = '您的核销号是：'.$push_data['cancell_num'].'。请到'.$group_name.'号柜子，'.$window_name.'号窗口取餐。（'.date('Y-m-d H:i:s',time()+3600*8).')';
            $msg1 = '您的预点餐订单编号：'.$to_dd['take_num'].'，已完成。请尽快前往取餐使用。柜号：'.$group_name.'柜'.$window_name.'号窗，取餐验证码：'.$push_data['cancell_num'];
            $dingDing->sendUserMsg($user_id, $msg1);

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
            $window_group = M('window_group')
                ->where(array('restaurant_id'=>session('restaurant_id'),'device_code'=>$device_code))
                ->field('group_id,group_name')
                ->select();
            $orderTab = 'order_'.date("Ym");
            foreach ($window_group as $key=>$val){
                $window_group[$key]['window_info'] = M('window_info')
                    ->alias('t1')
                    ->join("left join $orderTab t2 on t1.occupy_order_sn = t2.order_sn")
                    ->where(array('t1.group_id'=>$val['group_id']))
                    ->field('t1.window_id,t1.delay_time,t1.window_name,t1.group_id,t1.status,t1.put_meal_time,
                    t1.occupy_order_sn,t2.take_num,t2.push_status,t2.cancell_num')
                    ->order("window_name asc")
                    ->select();
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
            $where['push_status'] = array('in','1,7,9');  // 未放餐的订单，或者超时的订单，取消放餐
            $orderInfo = order()->where($where)->field('order_sn,take_num,push_status')->select();
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
                $ifSame = M('window_group')->where($same)->find();
                if(!empty($ifSame)){
                    $returnData['code']     = 0;
                    $returnData['msg']      = "分组名重复，请重新设置分组名1";
                    exit(json_encode($returnData));
                }

                // 同一个device_code不能重复添加
                $ifHave = M('window_group')->where(array('device_code'=>$device_code))->getField('group_id');
                if($ifHave){
                    $group_id = $ifHave;
                    M('window_group')->where(array('device_code'=>$device_code))->save(array('group_name'=>$groupName));
                }else{
                    // 分组名不存在，添加分组
                    $group_id = M('window_group')->add($groupWhere);
                }
                $newCreateGroup[$group_id] = $groupName;
            }else{
                $same['restaurant_id'] = session('restaurant_id');
                $same['group_name'] = $groupName;
                $same['type'] = $type;
                $ifSame = M('window_group')->where($same)->getField('group_id');
                if($ifSame && $group_id != $ifSame){
                    $returnData['code']     = 0;
                    $returnData['msg']      = "分组名重复，请重新设置分组名2";
                    exit(json_encode($returnData));
                }
                // 编辑分组名
                M('window_group')->where(array('group_id'=>$group_id))->save(array('group_name'=>$groupName));
            }

            $reflectNum = intval($reflectNum);
            $sql = "SELECT MAX(window_name) as maxWindowName FROM window_info WHERE restaurant_id = ".session('restaurant_id')." AND type = $type AND group_id = $group_id";
            $maxWindowName = M()->query($sql)[0]['maxWindowName']; // 最大的窗口数
            if($maxWindowName != null && $maxWindowName > $reflectNum){
                $windowIdsArr = [];
                $delWhere = array(
                    'window_name'=>array('gt',$reflectNum),
                    'restaurant_id'=>session('restaurant_id'),
                    'type'=>$type,
                    'group_id'=>$group_id
                );
                $windowIds = M('window_info')
                    ->where($delWhere)
                    ->field('window_id')
                    ->select();
                foreach ($windowIds as $key=>$val){
                    $windowIdsArr[] = $val['window_id'];
                }
                $res = M('window_info')->where(array('relation_other_id'=>array('in',$windowIdsArr)))->delete();  // 删除被关联
                // 删除多出的窗口数
                $del = M('window_info')
                    ->where($delWhere)
                    ->delete();
            }

            for ($i=1;$i<=$reflectNum;$i++){
                $groupInfo['restaurant_id'] = session('restaurant_id');
                $groupInfo['type'] = $type;
                $groupInfo['window_name'] = $i;
                $groupInfo['group_id'] = $group_id;
                $window_id = M('window_info')->where($groupInfo)->getField('window_id');
                if(!$window_id){
                    // 窗口名不存在，添加窗口名
                    $add_window_id = M('window_info')->add($groupInfo);
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
     

    // 清除device_code绑定的窗口
    public function del_windowInfo_device_code()
    {
        $device_code = I("post.device_code");
        
        $this->isLogin($device_code);
        if ($this->is_security) {
            $restaurant_id=session('restaurant_id');
            $window_group_model=M('window_group');
            $window_info_model=M('window_info');
            $window_group_id=$window_group_model->where(array('device_code'=>$device_code,'restaurant_id'=>$restaurant_id))->getField('group_id',true);
            if(!$window_group_id)
            {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备未绑定窗口";
            exit(json_encode($returnData));
            }
            if(count($window_group_id)>1)
            {
            $returnData['code']     = 0;
            $returnData['msg']      = "该设备绑定多条数据";
            exit(json_encode($returnData));
            }
            $window_group_id=$window_group_id[0];
            $window_info_model->startTrans();//开启事务
            $window_info=$window_info_model->where(array('group_id'=>$window_group_id))->delete();
            if($window_info)
            {
            $window_group=$window_group_model->where(array('group_id'=>$window_group_id))->delete();
            if($window_group)
            {
            $window_info_model->commit();
            $returnData['code']     = 1;
            $returnData['msg']      = "清除成功";
            exit(json_encode($returnData));  
            }
            $window_info_model->rollback();
            $returnData['code']     = 0;
            $returnData['msg']      = "清除失败";
            exit(json_encode($returnData));
            }
            $returnData['code']     = 0;
            $returnData['msg']      = "清除失败";
            exit(json_encode($returnData));
        }
        else {
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
            $info = M('window_info')->where($window_where)->find();
            if(empty($info)){
                $returnData['code']     = 0;
                $returnData['msg']      = "该订单号没有被绑定的窗口信息";
                exit(json_encode($returnData));
            }
            $save['delay_time'] = $info['delay_time'] + $time_num;
            $res = M('window_info')->where($window_where)->save($save);
            if($res === false){
                $returnData['code']     = 0;
                $returnData['msg']      = "延时放餐失败";
                exit(json_encode($returnData));
            }else{
                // 推给钉钉用户
                $dingDing = new DingDing();
                $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
                if($info['delay_time'] == 0){
                    $time_num = $time_num/2;
                }
                $delay_time = $time_num/60;
                $msg = '您的订单('.$order_sn.')延时：'.$delay_time.'分钟。（'.date('Y-m-d H:i:s').')';
                $dingDing->sendUserMsg($user_id, $msg);

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
                    ->save(array('push_status'=>9));
            }
            // 状态改为取消放餐
            $res = order()
                ->where(array('order_sn'=>$order_sn,'restaurant_id'=>session('restaurant_id')))
                ->save(array('push_status'=>9));
            // 设置延时
            $window_where['occupy_order_sn'] = $order_sn;
            $window_where['type'] = 1;
            $info = M('window_info')->where($window_where)->find();
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
            $res = M('window_info')->where($window_where)->save($save);
            if($res === false){
                $returnData['code']     = 0;
                $returnData['msg']      = "取消放餐失败";
                exit(json_encode($returnData));
            }else{
                // 推给钉钉用户
                $dingDing = new DingDing();
                $user_id = order()->where(array('order_sn'=>$order_sn))->getField('openid');
                $msg = "您的订单(".$order_sn.")已取消放餐，接下来请留意最新的放餐信息。（".date('Y-m-d H:i:s').')';
                $dingDing->sendUserMsg($user_id, $msg);

                // 推给取餐屏，根据设备号查出要推给的对应的分组的取餐屏的device_id
                $group_name = I('post.group_name'); // 分组名
                $window_name = I('post.window_name'); // 窗口名
                $relation_device_code = M('window_group')
                    ->where(array('group_name'=>$group_name,'type'=>2,))
                    ->getField('device_code');
                $device_id = M('take_meal_device')
                    ->where(array('relation_device_code'=>$relation_device_code))
                    ->getField('device_id');
                $type = 'cancellPutMeal';  // 取消放餐
                $push_data['qucan_window_name'] = $window_name;
                $push_data['order_sn'] = $order_sn;
                // 阿里推送
                $S_TakeMeal = new ServiceTakeMeal();
                if($device_id){
                    $S_TakeMeal->pushQucanping($type,$device_id,$push_data);
                }else{
                    $returnData['code']     = 0;
                    $returnData['msg']      = "取餐屏的柜子号与放餐屏不一致，或者还没设置取餐屏";
                    exit(json_encode($returnData));
                }

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
     *  根据设备号获取分组id
     */
    public function getDeviceId()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $group_id = M('window_group')->where(array('device_code'=>$device_code))->getField('group_id');
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
        $res = M('window_info')->where($occupy_where)->save($save);
        $fangcan_device_ids = M('take_meal_device')->where(array('type'=>1))->field('device_id')->select();
        $qucan_device_ids = M('take_meal_device')->where(array('type'=>2))->field('device_id')->select();
        $S_TakeMeal = new ServiceTakeMeal();
        // 推给放餐屏
        $S_TakeMeal->pushFangcanWhenDelBind($fangcan_device_ids);
        // 推给取餐屏
        $S_TakeMeal->pushqucanWhenDelBind($qucan_device_ids);
    }



}
