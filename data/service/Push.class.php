<?php
namespace data\service;
/**
 *集合各种情况下的推送,包括取餐柜模式的阿里推送，以及核销屏模式下的阿里推送
 * 但不包括美团，菜品售罄的推送,不包括钉钉取餐柜的推送(钉钉取餐柜推送已有:takeMeal.class.php)
 */

class Push extends BaseService
{
    protected $appId;
    protected $appSecret;

    /**
     *判断店铺推送类型:
     * @param ；$restaurant_id 店铺id
     * @return :返回店铺推送类型
     */
    public function pushType()
    {
        $where['restaurant_id'] = $_SESSION['restaurant_id'];
        $pushType = M('restaurant')->where($where)->getField('push_type');
        return $pushType;
    }

    //获取微信关注用户的信息
    public function getUser($openid)
    {
        $access_token = $this->get_access_token();
        $url_userinfo = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $user_info = $this->curlHttp($url_userinfo);
        $userArr = json_decode($user_info,true);
        return $userArr['headimgurl'];
    }

    //获取工号的access_token
    public function get_access_token()
    {

        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }else{
            $restaurant_id = $_SESSION['restaurant_id'];
            $data = M('wechat')->where('restaurant_id=%d',$restaurant_id)->find();
            $this->appId = $data['appid'];
            $this->appSecret=$data['appsecret'];
        }

        // 判断access_token的存在
        $key = 'access_token'.$_SESSION['restaurant_id'];
        if (empty(S("$key"))) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = $this->curlHttp($url);
            $arr = json_decode($res,true);
            $access_token = $arr['access_token'];
        }else{
            $access_token = S("$key");
        }
        return $access_token;
    }

    /**
     * get方式的CURL
     *string $url
     */
    public function curlHttp($url,$data='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // ssl报错
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST ,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER ,false);
        if(!empty($data)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }


    /**
     *  柜叫号推送接口(取餐柜模式上多加一个柜叫号模式)
     * @param $order_sn 订单号
     * @param device_id 放餐屏的设备ID
     * @param $type 1首次推送，2取餐推送
     * @param $window_name 窗口名
     */
    public function guiJiaoHao($order_sn,$devices_ids,$type,$window_name)
    {
        // 推送的数据
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn,desk_code,window_num,openid,order_id')->find();
        //获取微信头像
//        $openid = $orderInfo['openid'];
//        $_SESSION['restaurant_id'] = $orderInfo['restaurant_id'];
//        if(empty($openid)){
//            $push_data['headimgurl'] = '';
//        }else{
//            $push_data['headimgurl'] = $this->getUser($openid);
//        }
        $push_data['add_time'] = $orderInfo['add_time'];   // 添加时间
        $push_data['push_status'] = $orderInfo['push_status'];  // 1未做，3未取餐，5已取餐，7超时
        $push_data['take_num'] = $orderInfo['take_num'];       // 取餐号
        $push_data['order_sn'] = $orderInfo['order_sn'];       // 订单号
        $push_data['type'] = $type;       // 1首次推送，2取餐后的推送
        $push_data['desk_code'] = $orderInfo['desk_code'];       // 柜子号
        $push_data['window_name'] = $window_name;       // 窗口名
//        $push_data['window_num'] = $orderInfo['window_num'];       //窗口号
        // $push_data['food_info'] = order_F()->where(array('order_id'=>$orderInfo['order_id']))->field('food_name,food_detail,order_food_id')->select();
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
        $appKey = M('app_key')->where(array('app_type'=>5))->getField('app_key');
        // 对应的device_id
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."推送给柜叫号:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }



    /**
     *客户下单后的第一步推送,推送给放餐屏(取餐柜模式的第一步推送)
     * @param:$order_sn 订单号
     */
    public function pushOneCupboard($order_sn)
    {

        //查询订单详细信息
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn,order_id')->find();
        $restaurant_id = $orderInfo['restaurant_id'];
        $cancell_num = $orderInfo['cancell_num'];
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
            $push_data['food_info'] = order_F()->where(array('order_id'=>$orderInfo['order_id']))->field('food_name,food_detail,order_food_id')->select();
            $appKey = M('app_key')->where(array('app_type'=>1))->getField('app_key');   //type为1的app_key，即放餐柜
            $where = array(
                'restaurant_id'=>$restaurant_id,
                'type'=>1   // 1、放餐屏，2、取餐屏，3、准备中/请取餐
            );
            // 对应的device_id
            $devices_ids = M('dc_take_meal_device')->where($where)->field('device_id')->select();
            $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
            file_put_contents("./"."restaurant_push.txt", '店铺id'.$restaurant_id."设备id:".json_encode($devices_ids)."推送给放餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);

            //推送给柜叫号
            $devices_ids_gjh = M('dc_take_meal_device')->where(array('type'=>5,'restaurant_id'=>$restaurant_id))->field('device_id')->select();
            $this->guiJiaoHao($order_sn,$devices_ids_gjh,1);
        }
    }


    /**
     *  放餐屏同步接口后的推送，推给取餐屏(取餐柜模式的第二步推送)
     * @param $type 'put_meal'放餐屏放餐，'timeout'超时
     * @param $qucangui_device_id 取餐柜的设备ID
     * @param $push_data 推送的数据
     */
    public function pushTwoCupboard($type,$qucangui_device_id,$push_data)
    {
        $push_data['type'] = $type;        // 类型为：'put_meal'放餐屏放餐，'timeout'超时
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
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给取餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }


    /**
     *  取餐屏接口同步后的推送，推给放餐屏(取餐柜模式的第三步推送)
     * @param $order_sn 订单号
     * @param $fangcan_device_id 放餐屏的设备ID
     * @param $type 1首次推送，2取餐推送
     */
    public function pushThreeCupboard($order_sn,$fangcan_device_id,$fangcan_window_name = '')
    {
        // 推送的数据
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        if(!$orderInfo){
            lastOrder()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        }
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
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给放餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }



    /******************************************上面是取餐柜模式的推送方法，下面的是核销屏的推送方法**************************************************/


    /**
     *客户下单后推送给核销屏,厨房以及叫号屏(核销屏模式的第一步推送)
     * @param:$order_sn订单号
     */
    public function pushOneScreen($order_sn)
    {
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        $restaurant_id = $orderInfo['restaurant_id'];
        $cancell_num = $orderInfo['cancell_num'];
        // 生成核销号
        $cancellNum = $this->createCellNum($order_sn,$restaurant_id,$cancell_num);
        // 还没推送过
        if(true){
            $res = order()->where(array('order_sn'=>$order_sn))->save(array('push_status'=>1));   // 记录下单后的第一次推送
            
            /*
             获取订单详情
            */
             $table_time=date("Y").date("m");
             $sql="SELECT * from order_food_$table_time where order_id=(SELECT order_id from order_$table_time where order_sn='$order_sn')";
            $order_details_arr=M()->query($sql);
            $push_data['food_info']=array();
            foreach ($order_details_arr as $key => $value) {
                $push_data['food_info'][$value['order_food_id']]['food_name']=$value['food_name'];
                $push_data['food_info'][$value['order_food_id']]['food_num']=$value['food_num'];
                $push_data['food_info'][$value['order_food_id']]['food_price2']=$value['food_price2'];
                $push_data['food_info'][$value['order_food_id']]['food_total_price']=$value['food_price2']*$value['food_num'];
            }
            sort($push_data['food_info']);
            // 推送的数据
            $push_data['add_time'] = $orderInfo['add_time'];   // 添加时间
            $push_data['push_status'] = 1;  // 1未做，3未取餐，5已取餐，7超时
            $push_data['window_id'] = '';       // 窗口id，首次推送为空
            $push_data['take_num'] = $orderInfo['take_num'];       // 取餐号
            $push_data['order_sn'] = $orderInfo['order_sn'];       // 订单号
            $push_data['type'] = 1;       // 1首次推送，2取餐后的推送
            $php_title = 'founpad_restaurant_push'; // 标题

            // 推给核销屏
            $appKey = M('app_key')->where(array('app_type'=>4))->getField('app_key');   // 对应核销屏类型的app_key
            $where = array(
                'restaurant_id'=>$restaurant_id,
                'type'=>4   // 1、放餐屏，2、取餐屏，3、准备中/请取餐 ,4核销屏
            );
            // 查询核销屏的device_id
            $devices_ids = M('dc_take_meal_device')->where($where)->field('device_id')->select();
            
            if($devices_ids){   //有$devices_ids
                $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
                file_put_contents("./"."restaurant_push.txt", '店铺id'.$restaurant_id."设备id:".json_encode($devices_ids)."推送给核销:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
            }


            //也要推送给叫号屏
            $_push_jh['order_sn'] = $orderInfo['order_sn'];       // 订单号
            $_push_jh['status'] = 'order_preparing';

            $appKey = M('app_key')->where(array('app_type'=>3))->getField('app_key');   // 对应叫号类型的app_key
            $where = array(
                'restaurant_id'=>$restaurant_id,
                'type'=>3   // 1、放餐屏，2、取餐屏，3、准备中/请取餐 ,4核销屏
            );
            // 查询叫号屏的device_id
            $devices_ids = M('dc_take_meal_device')->where($where)->field('device_id')->select();
            $response = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
            file_put_contents("./"."restaurant_push.txt", '店铺id'.$restaurant_id."设备id:".json_encode($devices_ids)."推送叫号屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);


        }
    }



    /**
     *  核销屏菜品做好后同步接口后的推送，推给叫号屏(核销屏模式的第二步推送)
     * @param $type 'put_meal'放餐屏放餐，'timeout'超时
     * @param $device_id 叫号屏的设备ID
     * @param $push_data 推送的数据
     */
    public function pushTwoScreen($type,$devices_ids,$push_data)
    {
        $push_data['type'] = $type;        // 类型为：'put_meal'核销屏放餐，'timeout'超时
        $push_data['platform'] = 'jiaohaoping';
        $php_title = 'founpad_restaurant_push'; // 标题
        /**
         * 阿里推送公共方法
         * @param Array $devices_ids 设备ID数组（二维数组）
         * @param String $php_title 消息标题
         * @param String $php_body  具体内容
         * @return mixed|\SimpleXMLElement
         */
        // 1、放餐屏，2、取餐屏，3、准备中/请取餐
        // 叫号屏的app_key
        $appKey = M('app_key')->where(array('app_type'=>3))->getField('app_key');
        // 对应叫号屏的device_id
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给叫号屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }



    /**
     *  核销屏取餐完成接口同步后的推送，推给叫号屏(核销屏模式的第三步推送)
     * @param $order_sn 订单号
     * @param device_id 放餐屏的设备ID
     * @param $type 1首次推送，2取餐推送
     */
    public function pushThreeScreen($order_sn,$devices_ids)
    {
        // 推送的数据
        $orderInfo = order()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        if(!$orderInfo){
            lastOrder()->where(array('order_sn'=>$order_sn))->field('restaurant_id,cancell_num,add_time,push_status,take_num,order_sn')->find();
        }
        $push_data['add_time'] = $orderInfo['add_time'];   // 添加时间
        $push_data['push_status'] = $orderInfo['push_status'];  // 1未做，3未取餐，5已取餐，7超时
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
        $appKey = M('app_key')->where(array('app_type'=>3))->getField('app_key');
        // 对应的device_id
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,json_encode($push_data),$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给叫号屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }


    /**
     *生成订单的核销号(取餐柜那边是生成)
     */
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


    // 推送给厨房
    public function push_to_cookRoom($order_sn)
    {
        // 推送的数据
        $push_data['type'] = 'place_order';   // 类型为：下单
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
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$restaurant_id."设备id:".json_encode($devices_ids)."推送给厨房:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
    }


    /**
     *  定时任务清理推给放餐屏
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
        $appKey = M('app_key')->where(array('app_type'=>1))->getField('app_key');
        // 对应的device_id
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,$push_data,$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给放餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }

    /**
     *  定时任务清理推给取餐屏
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
        $appKey = M('app_key')->where(array('app_type'=>2))->getField('app_key');
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,$push_data,$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."接口同步的推送，推给取餐屏:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }




    public function delOrderData($devices_ids,$type){
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
        $where['app_type'] = $type;
        $appKey = M('app_key')->where($where)->getField('app_key');
        $response = $res = $this->ali_push_to_android_can_set($devices_ids,$php_title,$push_data,$appKey);
        file_put_contents("./"."restaurant_push.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".json_encode($devices_ids)."删除核销屏叫号屏柜叫号订单信息:".json_encode($response)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
        /****************************************推送给Android***************************************/
    }













}