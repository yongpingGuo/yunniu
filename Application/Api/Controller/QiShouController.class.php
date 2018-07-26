<?php
namespace Api\Controller;
use data\service\Push as ServiceTakeMeal;
use Mobile\Controller\WechatController;

/**
 *骑手app相关的api控制器
 */
class QiShouController extends BaseController
{


    /**
     *  获取安卓收银设备的device_id，绑定对应的device_id和device_code
     *  device_code  设备码
     *  type  类型    设备类型 1放餐屏，2取餐屏，3准备中/请取餐 , 4核销屏 ,5柜叫号 ,6骑手柜
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
     *骑手下单
     *
     */
    public function placeOrder()
    {
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if ($this->is_security) {
            $client_order = I("post.order_sn"); //骑手订单号
            // 安卓的本地订单号
            $qs_order = M('qs_order');
            $rel = $qs_order->where(array('order_sn' => $client_order))->find();
            if ($rel) {
                $returnData['code'] = 2;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "数据库中已有对应记录";
                exit(json_encode($returnData));
            }

            //进行订单同步，客户端订单与服务器订单做映射
            //1、生成订单
            M()->startTrans(); //开启事务
            $add_time = I("post.time");
            $orderInfo['add_time'] = strtotime($add_time); //添加时间
            $orderInfo['restaurant_id'] = session("restaurant_id");
            $orderInfo['order_status'] = 1; //订单状态
            $orderInfo['order_sn'] = $client_order; //订单号
            $orderInfo['total_price'] = I('post.price');    //总价
            $orderInfo['rider_phone'] = I('post.rider_phone');    //骑手手机号码
//            $orderInfo['createCellNum'] = $this->createCellNum($client_order,$orderInfo['restaurant_id']);
            $order_id = $qs_order->add($orderInfo);

            if($order_id !== 0){  //订单表插入成功
                $qs_order_detail = M('qs_order_detail');    //订单详情表
                $cusList = I('post.cusList');
//                file_put_contents("./"."qishou.txt", '店铺id'.$_SESSION['restaurant_id']."设备id:".$cusList.":"."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);

                $cusList = json_decode(htmlspecialchars_decode($cusList),true);//转化成数组
                foreach($cusList as $k=>$v){
                    $add_data['order_id'] = $order_id;
                    $add_data['user_phone'] = $v['phone_num'];
                    $add_data['window_num'] = $v['window_num'];
                    $add_data['cancell_num'] = $this->getNum();
                    file_put_contents("./"."cancell_num.txt",'code'.$add_data['cancell_num']."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
                    $res = $qs_order_detail->add($add_data);
                }

                if($order_id !== 0 && $res){
                    M()->commit();
                }else{
                    M()->rollback();
                }

            }else{
                $returnData['code'] = 2;
                $returnData['order_sn'] = "";
                $returnData['msg'] = "插入订单失败";
                exit(json_encode($returnData));
            }

            // 如果传递过来的微信、支付宝标识有值，则返回支付二维码

            /*****************************判断安卓是需要民生的码还是官方的码*********************************///返回二维码
            $need_which = I('post.need_which'); // 1官方  2民生
            if ($need_which == 2) {
                // 需要民生的码
                // 实例化FourthPay类的对象
                $FourthPay = new QiShouFourthPayController();
                // 接收参数
                $data_arr['fourth_sn'] = I('post.fourth_sn');   // 提交给民生的订单号
                $data_arr['order_sn'] = $client_order;     // 服务器订单号
                $data_arr['public_key'] = I('post.public_key'); // 秘钥
                $data_arr['operater_id'] = I('post.operater_id'); // 操作员ID
                $data_arr['business_no'] = I('post.business_no');   // 商户号
                $data_arr['device_code'] = $device_code;
                $return = $FourthPay->pay_code_in_place_order($data_arr);
                if ($return['code'] == 1) {
                    $returnData['wx_adress'] = $return['weixin_qr'];
                    $returnData['ali_adress'] = $return['ali_qr'];
                } else {
                    $returnData['code'] = 0;
                    $returnData['msg'] = $return['msg'];
                    $returnData['wx_adress'] = 0;
                    $returnData['ali_adress'] = 0;
                }
                exit(json_encode($returnData));
            } else {
                // 需要官方的码
                $returnData['code'] = 1;
                $returnData['msg'] = 'success';
                $returnData['wx_adress'] = "http://" . $_SERVER["HTTP_HOST"] . "/index.php/vertical/QiShouWxChat/qrc/order_sn/" . $client_order . "/device_code/" . $device_code;
                $returnData['ali_adress'] = "http://" . $_SERVER["HTTP_HOST"] . "/index.php/vertical/QiShouAlipayDirect/alipay_code/order_sn/" . $client_order . "/device_code/" . $device_code;
                exit(json_encode($returnData));
            }

            /*****************************判断安卓是需要民生的码还是官方的码*********************************/

        } else {
            $returnData['code'] = 0;
            $returnData['order_sn'] = "";
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            exit(json_encode($returnData));
        }
    }


    //验证成功删除验证码
    public function delCancellNum()
    {
        $cancellNum = I('cancell_num');
        $order_sn = I('order_sn');
        $device_code = I('device_code');

        $this->isLogin($device_code);
        $qs_order = M('qs_order');
        $qs_order_detail = M('qs_order_detail');
        if($this->is_security) {
            $where['order_sn'] = $order_sn;
            $order_id = $qs_order->where($where)->field('order_id')->find();
            $save['cancell_num'] = 0;
            $save['status'] = 1;
            $map['order_id'] = $order_id;
            $map['cancell_num'] = $cancellNum;
            $res = $qs_order_detail->where(array('order_id'=>$order_id['order_id'],'cancell_num'=>$cancellNum))->save($save);

            if($res){//修改成功，判断这订单是否完全取完餐
                $order_count = $qs_order_detail->where(array('order_id'=>$order_id['order_id']))->count();
                $status_sum = $qs_order_detail->where(array('order_id'=>$order_id['order_id']))->sum('status');
                if($order_count == $status_sum){
                    //相等即这一订单全部柜子都已取完餐,修改状态
                    $order_save['order_status'] = 4;
                    $qs_order->where($where)->save($order_save);
                }
            }

            $returnData['code'] = '1';
            $returnData['msg'] = "success";
            echo json_encode($returnData);
        }else{
            $returnData['code'] = '0';
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }


    //发送短信验证码
    public function sms(){
                $_SESSION['restaurant_id'] = 131;
        $order_sn   = 'DC1ccae300357420180514145840';
                $info = M('restaurant')->where(array("restaurant_id"=>session("restaurant_id")))->field('address')->find();
                $restaurant = M('restaurant_manager')->where(array("restaurant_id"=>session("restaurant_id")))->field('business_id')->find();
                dump($info);
                $sms_info = M("sms_vip")->where(array("business_id"=>$restaurant['business_id']))->find();
                dump($sms_info);
                $user_sms_temp_id = $sms_info['user_sms_temp_id'];
                $rider_sms_temp_id = $sms_info['rider_sms_temp_id'];
                $appkey = $sms_info['appkey'];
                $secret = $sms_info['secret'];
                $sign = $sms_info['sign'];


                $address = $info['address'];
                $template_one = "{\"address\":\"$address\",\"address\":\"$address\"}";
                $template_two = "{}";


                //查询订单详情
        $qs_order = M('qs_order');
                $order_info = $qs_order->where(array('order_sn'=>$order_sn))->field('rider_phone,order_id')->find();
                $rider_phone = $order_info['rider_phone'];
                $result = sendSms_new($appkey,$secret,$rider_phone,$sign,$template_two,$rider_sms_temp_id);//短信发给骑手
        dump($result);
                file_put_contents("./"."sms_log.txt",'order_sn'.$order_sn."推送给骑手的短信:".json_encode($result)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);

                $order_id = $order_info['order_id'];
                $order_detail = M('qs_order_detail')->where(array('order_id'=>$order_id))->field('user_phone,cancell_num')->select();
                foreach($order_detail as $k=>$v){
                    dump($v);
                    $code = $v['cancell_num'];
                    dump($code);
                    $template_one = "{\"address\":\"$address\",\"code\":\"$code\"}";
                    $result = sendSms_new($appkey,$secret,$v['user_phone'],$sign,$template_one,$user_sms_temp_id);//短信发给骑手
//                    file_put_contents("./"."sms_log.txt",'order_sn'.$order_sn."推送给用户的短信:".json_encode($result)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
                    dump($result);
                }
                $return['code'] = 1;
                $return['msg'] = 'success';

                echo json_encode($return);

    }


    /**
     *放餐完成通知骑手和用户
     */
    public function putDone()
    {
        $qs_order = M('qs_order');
        $device_code = I("post.device_code");
        $this->isLogin($device_code);
        if($this->is_security) {
            $order_sn = I("order_sn");
            $save['order_status'] = 3;
            $where['order_sn'] = $order_sn;
            $res = $qs_order->where($where)->save($save);//把订单标识
            if($res){
                //短信通知-------------
                //查询短信api配置
                $info = M('restaurant')->where(array("restaurant_id"=>session("restaurant_id")))->field('address')->find();
                $restaurant = M('restaurant_manager')->where(array("restaurant_id"=>session("restaurant_id")))->field('business_id')->find();
                $sms_info = M("sms_vip")->where(array("business_id"=>$restaurant['business_id']))->find();
                $user_sms_temp_id = $sms_info['user_sms_temp_id'];
                $rider_sms_temp_id = $sms_info['rider_sms_temp_id'];
                $appkey = $sms_info['appkey'];
                $secret = $sms_info['secret'];
                $sign = $sms_info['sign'];

                $address = $info['address'];



                //查询订单详情
                $order_info = $qs_order->where(array('order_sn'=>$order_sn))->field('rider_phone,order_id')->find();
                $rider_phone = $order_info['rider_phone'];


                $order_id = $order_info['order_id'];
                $order_detail = M('qs_order_detail')->where(array('order_id'=>$order_id))->field('user_phone,cancell_num')->select();

                foreach($order_detail as $k=>$v){
                    //短信发给用户
                    $code = $v['cancell_num'];
                    $template_one = "{\"address\":\"$address\",\"code\":\"$code\"}";
                    $result = sendSms_new($appkey,$secret,$v['user_phone'],$sign,$template_one,$user_sms_temp_id);//短信发给骑手
                    file_put_contents("./"."sms_log.txt",'order_sn'.$order_sn."推送给用户的短信:".json_encode($result)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);

                    //短信发给骑手
                    $phone = $v['user_phone'];
                    $template_two = "{\"phone\":\"$phone\",\"code\":\"$code\"}";
                    $result = sendSms_new($appkey,$secret,$rider_phone,$sign,$template_two,$rider_sms_temp_id);
                    file_put_contents("./"."sms_log.txt",'order_sn'.$order_sn."推送给骑手的短信:".json_encode($result)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
                }

                $return['code'] = 1;
                $return['msg'] = 'success';
                echo json_encode($return);
            }
        }else{
            $returnData['code'] = '0';
            $returnData['msg'] = "该设备已过期";
            exit(json_encode($returnData));
        }
    }


    /*
    *生成取餐柜核销验证码
    */
    public function getNum() {
        $arr = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        $str = '';
        for($i = 0; $i < 4; $i++) {
            $str .= $arr[rand(0, 9)];
        }
        $where['cancell_num'] = $str;
        $num = M('qs_order_detail')->where($where)->count();
        if($num > 0) $this->getNum();
        return $str;
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



    public function test11()
    {
        //短信通知-------------
        //查询短信api配置
        $_SESSION['restaurant_id'] = 956;
        $info = M('restaurant')->where(array("restaurant_id"=>session("restaurant_id")))->field('address')->find();
        $restaurant = M('restaurant_manager')->where(array("restaurant_id"=>session("restaurant_id")))->field('business_id')->find();
        $sms_info = M("sms_vip")->where(array("business_id"=>$restaurant['business_id']))->find();
        $user_sms_temp_id = $sms_info['user_sms_temp_id'];
        $rider_sms_temp_id = $sms_info['rider_sms_temp_id'];
        $appkey = $sms_info['appkey'];
        $secret = $sms_info['secret'];
        $sign = $sms_info['sign'];
        $address = $info['address'];



        //查询订单详情
//        $order_id = $order_info['order_id'];
//        $order_detail = M('qs_order_detail')->where(array('order_id'=>$order_id))->field('user_phone,cancell_num')->select();

        $user_phone = '13229456485#2233';
//        $user_phone = 13631461045;
        $user_sms_temp_id   = 'SMS_135802165';
        //短信发给用户
        $fjh = '#2233#';
        $code = 123456;
            $template_one = "{\"fjh\":\"$fjh\",\"address\":\"$address\",\"code\":\"$code\"}";
            $result = sendSms_new($appkey,$secret,$user_phone,$sign,$template_one,$user_sms_temp_id);//短信发给骑手
        dump($result);
    }


    public function test133()
    {
        $encode='GBK';  //页面编码和短信内容编码为GBK。重要说明：如提交短信后收到乱码，请将GBK改为UTF-8测试。如本程序页面为编码格式为：ASCII/GB2312/GBK则该处为GBK。如本页面编码为UTF-8或需要支持繁体，阿拉伯文等Unicode，请将此处写为：UTF-8

        $username='chenyy';  //用户名

        $password_md5=md5('yin1234');  //32位MD5密码加密，不区分大小写

        $apikey='481242054776ccc2f69a84916c01b4f9';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）

        $mobile='13229459761';  //手机号,只发一个号码：13800000001。发多个号码：13800000001,13800000002,...N 。使用半角逗号分隔。

        $content='#9051# ,老总收到短信给我截个图';  //要发送的短信内容，特别注意：签名必须设置，网页验证码应用需要加添加【图形识别码】。

//        $content = iconv("GBK","UTF-8",$content);

        $contentUrlEncode = urlencode($content);//执行URLencode编码  ，$content = urldecode($content);解码

        dump($contentUrlEncode);
        $result = $this->sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode);  //进行发送

        if(strpos($result,"success")>-1) {
            //提交成功
            //逻辑代码
        } else {
            //提交失败
            //逻辑代码
        }
        echo $result;  //输出result内容，查看返回值，成功为success，错误为error，（错误内容在上面有显示）

    }


    //发送接口
    function sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode)
    {
        //发送链接（用户名，密码，apikey，手机号，内容）
        $url = "http://m.5c.com.cn/api/send/index.php?";  //如连接超时，可能是您服务器不支持域名解析，请将下面连接中的：【m.5c.com.cn】修改为IP：【115.28.23.78】
        $data=array
        (
            'username'=>$username,
            'password_md5'=>$password_md5,
            'apikey'=>$apikey,
            'mobile'=>$mobile,
            'content'=>$contentUrlEncode,
            'encode'=>$encode,
        );
        $result = $this->curlSMS($url,$data);
        //print_r($data); //测试
        return $result;
    }

    function curlSMS($url,$post_fields=array())
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);//用PHP取回的URL地址（值将被作为字符串）
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//使用curl_setopt获取页面内容或提交数据，有时候希望返回的内容作为变量存储，而不是直接输出，这时候希望返回的内容作为变量
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
        curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
        curl_setopt($ch,CURLOPT_POST,1);//设置这个选项为一个零非值，这个post是普通的application/x-www-from-urlencoded类型，多数被HTTP表调用。
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
        $data = curl_exec($ch);//抓取URL并把他传递给浏览器
        curl_close($ch);//释放资源
        $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
        return $res[2]; //然后在这里返回数组。
    }

    public function post($url, $data, $proxy = null, $timeout = 20) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); //在HTTP请求中包含一个"User-Agent: "头的字符串。
        curl_setopt($curl, CURLOPT_HEADER, 0); //启用时会将头文件的信息作为数据流输出。
        curl_setopt($curl, CURLOPT_POST, true); //发送一个常规的Post请求
        curl_setopt($curl,  CURLOPT_POSTFIELDS, $data);//Post提交的数据包
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //文件流形式
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); //设置cURL允许执行的最长秒数。
        $content = curl_exec($curl);
        curl_close($curl);
        unset($curl);
        return $content;
    }



}