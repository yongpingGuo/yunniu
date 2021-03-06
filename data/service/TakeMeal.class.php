<?php
namespace data\service;
/*
* 取餐柜推送信息
*/
class TakeMeal extends BaseService{
    /**
     *  客户下单支付回调后的放餐屏推送（原本为多个推送的公共方法，先仅为客户下单后推到放餐和准备中的推送），版本2
     * @param $order_sn 订单号
     * @param $typeArr 推给哪些设备
     */
    public function takeMealPush_two($order_sn,$typeArr=array(1,3)){
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        $restaurant_id = $orderInfo['restaurant_id'];
        $cancell_num = $orderInfo['cancell_num'];
        $restaurantArr = array(131);
        if(in_array($restaurant_id,$restaurantArr)){
            // 生成核销号
            $cancellNum = $this->createCellNum($order_sn,$restaurant_id,$cancell_num);
            // 还没推送过
            if($cancellNum != 'stop'){
                $res = order()->where(array('order_sn'=>$order_sn))->save(array('push_status'=>1));   // 记录下单后的第一次推送
                /****************************************推送给Android***************************************/
                // 推送的数据
                $push_data['add_time'] = $orderInfo['add_time'];   // 添加时间
                $push_data['push_status'] = 1;  // 1未做，3未取餐，5已取餐，7超时
                $push_data['window_id'] = '';       // 窗口id，首次推送为空
                $push_data['take_num'] = $orderInfo['take_num'];       // 取餐号
                $push_data['order_sn'] = $orderInfo['order_sn'];       // 订单号
                $push_data['type'] = 1;       // 1首次推送，2取餐后的推送
                $php_title = 'founpad_restaurant_push'; // 标题
                // 推给放餐屏
                $appKey = M('app_key')->where(array('app_type'=>1))->getField('app_key');   // 对应的app_key
                $where = array(
                    'restaurant_id'=>$restaurant_id,
                    'type'=>1   // 1、放餐屏，2、取餐屏，3、准备中/请取餐
                );
                // 对应的device_id
                $devices_ids = M('take_meal_device')->where($where)->field('device_id')->select();
                $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
                // 推给厨房
                $this->push_to_cookRoom($order_sn);

            }
        }
    }

    // 推送给厨房
    public function push_to_cookRoom($order_sn)
    {
        /****************************************推送给Android***************************************/
        // 推送的数据
        $push_data['type'] = 'dingding_place_order';   // 类型为：下单
        $push_data['order_sn'] = $order_sn;
        $push_data['platform'] = 'mobile';
        // 查出当前订单号所属店铺
        $restaurant_id = order()->where(array('order_sn'=>$order_sn))->getField('restaurant_id');

        $devices_ids = M('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        $appKey = M('jubaopen_ali_push_config')->getField('appKey');
        $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey,$type = 2);
        file_put_contents("./"."push.txt", "设备id:".json_encode($devices_ids)."推送给厨房:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    /**
     *  接口同步的推送，推给取餐屏
     * @param $type 'put_meal'放餐屏放餐，'timeout'超时
     * @param $qucangui_device_id 取餐柜的设备ID
     * @param $push_data 推送的数据
     */
    public function pushQucanping($type,$qucangui_device_id,$push_data){
        /****************************************推送给Android***************************************/
        // 推送的数据
        // 类型为：'put_meal'放餐屏放餐，'timeout'超时
        $push_data['type'] = $type;
        $push_data['platform'] = 'take_meal_box';
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 对应的app_key
        $appKey = M('app_key')->where(array('app_type'=>2))->getField('app_key');
        // 对应的device_id
        $devices_ids = array(array('device_id'=>$qucangui_device_id));
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        file_put_contents("./"."push.txt", "设备id:".json_encode($devices_ids)."接口同步的推送，推给取餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    /**
     *  接口同步的推送，推给叫号屏
     * @param $order_sn 订单号
     * @param $type 请取餐还是核销
     * @param $jiaohao_device_id 叫号屏的设备ID
     */
    public function pushJiaohao($order_sn,$type,$jiaohao_device_id,$device_name = ''){
        /****************************************推送给Android***************************************/
        // 推送的数据
        // 类型为：'take_meal'请取餐，'cancell'核销，'timeout'超时
        if($type == 'take_meal'){
            $push_data['device_name'] = $device_name;
        }
        $push_data['type'] = $type;
        $push_data['order_sn'] = $order_sn;
        $push_data['platform'] = 'take_meal_box';
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 对应的app_key
        $appKey = M('app_key')->where(array('app_type'=>3))->getField('app_key');
        // 对应的device_id
        $devices_ids = array(array('device_id'=>$jiaohao_device_id));
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,$push_data,$appKey);
        file_put_contents("./"."push.txt", "设备id:".json_encode($devices_ids)."接口同步的推送，推给叫号屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    /**
     *  接口同步的推送，推给放餐屏
     * @param $order_sn 订单号
     * @param $fangcan_device_id 放餐屏的设备ID
     * @param $type 1首次推送，2取餐推送
     */
    public function pushFangcan($order_sn,$fangcan_device_id,$fangcan_window_name = ''){
        /****************************************推送给Android***************************************/
        // 推送的数据
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        $push_data['add_time'] = $orderInfo['add_time'];   // 添加时间
        $push_data['push_status'] = $orderInfo['push_status'];  // 1未做，3未取餐，5已取餐，7超时
        $push_data['window_name'] = $fangcan_window_name;// 放餐窗口名，首次推送为空
        $push_data['take_num'] = $orderInfo['take_num'];       // 取餐号
        $push_data['order_sn'] = $orderInfo['order_sn'];       // 订单号
        $push_data['type'] = 2;       // 1首次推送，2取餐后的推送
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 对应的app_key
        $appKey = M('app_key')->where(array('app_type'=>1))->getField('app_key');
        // 对应的device_id
        $devices_ids = array(array('device_id'=>$fangcan_device_id));
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        file_put_contents("./"."push.txt", "设备id:".json_encode($devices_ids)."接口同步的推送，推给放餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    /**
     *  接口同步的推送，推给放餐屏
     * @param $order_sn 订单号
     * @param $fangcan_device_id 放餐屏的设备ID
     * @param $type 1首次推送，2取餐推送
     */
    public function pushFangcanWhenDelBind($devices_ids){
        /****************************************推送给Android***************************************/
        // 推送的数据
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 对应的app_key
        $push_data = 'clear';
        $appKey = M('app_key')->where(array('app_type'=>5))->getField('app_key');
        // 对应的device_id
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,$push_data,$appKey);
        file_put_contents("./"."push.txt", "设备id:".json_encode($devices_ids)."接口同步的推送，推给放餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    /**
     *  接口同步的推送，推给取餐屏
     * @param $type 'put_meal'放餐屏放餐，'timeout'超时
     * @param $qucangui_device_id 取餐柜的设备ID
     * @param $push_data 推送的数据
     */
    public function pushqucanWhenDelBind($devices_ids){
        /****************************************推送给Android***************************************/
        $php_title = 'founpad_restaurant_push'; // 标题
        $push_data = 'clear';
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 对应的app_key
        $appKey = M('app_key')->where(array('app_type'=>4))->getField('app_key');
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,$push_data,$appKey);
        file_put_contents("./"."push.txt", "设备id:".json_encode($devices_ids)."接口同步的推送，推给取餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    // 生成核销号
    public function createCellNum($order_sn,$restaurant_id,$cancell_num){
        if($cancell_num != null){
            return 'stop';
        }
        $randNum = str_pad(mt_rand(1,9999),4,"0",STR_PAD_LEFT );
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $condition1['add_time'] = array("between",array($start,$end));
        $condition1['restaurant_id'] = $restaurant_id;
        $condition1['cancell_num'] = $randNum;
        $ifHave = order()->where($condition1)->getField('order_id');
        if($ifHave){
            $this->createCellNum($order_sn,$restaurant_id,$cancell_num);
        }
        order()->where(array('order_sn'=>$order_sn))->save(array('cancell_num'=>$randNum));
        return $randNum;
    }
}
