<?php

namespace Api\Controller;

Vendor('Php_sms.SmsUitl');
use \Php_sms\SmsUitl;


class SendSmsController extends BaseController
{
	
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
                    $add_data['fjh'] = $v['fjh'];
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


    //支付完成发送短信
	public function send()
	{	
		$where1 = array('order_sn' => I('order_sn'));
		$qs_order = M('qs_order');
		$data1 = $qs_order->where($where1)->find();
		$qs_order_detail = M('qs_order_detail');
		$where['order_id'] = $data1['order_id'];
		$data = $qs_order_detail->where($where)->order('order_detail_id desc')->select();

		/*****************************根据订单状态判断支付是否完成***********************************/
		if ($data1['order_status'] == 2) {
			foreach ($data as $v) {

				if (empty($v['fjh'])) {
					$content ='【外卖通知】您有一个外卖到达取餐易柜子了，取餐地址：'.$v['window_num'].'窗口，取餐验证码：'.$v['cancell_num'].'。请尽快取出享用！';				//信息模板
				} else {
					$content = $data['fjh'].'【外卖通知】您有一个外卖到达取餐易柜子了，取餐地址：'.$v['window_num'].'窗口，取餐验证码：'.$v['cancell_num'].'。请尽快取出享用！';				//信息模板
				}
				/*发送信息给客户*/
				$mobile = $v['user_phone'];					//客户手机				
				$sms = SmsUitl::getInstance();
				$result = $sms->sendsms($mobile, $content);  //调用发送短信
				file_put_contents("./"."sendsms_log.txt",'order_sn'.I('order_sn')."推送给用户的短信:".json_encode($result)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);

				/*发送信息给骑手*/
				$rider_mobile = $data1['rider_phone'];			//骑手手机
				$rider_content = '【取餐易】放餐通知：系统自动将验证码通过短信方式通知用户，用户'.$v['user_phone'].'的取餐码：'.$v['cancell_num'].'，切勿转发与回复本条消息。';			//骑手短信模板
				$rider_result = $sms->sendsms($rider_mobile, $rider_content);
				file_put_contents("./"."sendsms_log.txt",'order_sn'.I('order_sn')."推送给用户的短信:".json_encode($rider_result)."||时间".date("Y-m-d H:i:s")."\r\n\r\n", FILE_APPEND);
			}


			/*********************************根据返回结果判断短信是否发送成功并且返回数据********************************************/
			if(strpos($result,"success")>-1 && strpos($rider_result,"success")>-1 ) {
                $returnData = array('msg' => 'success', 'code' => 1);
                // $returnDate['msg'] = 'success';
                // $returnData['code'] = 1;
            	exit(json_encode($returnData));
            } else {
                $returnData = array('msg' => 'fail', 'code' => 2);
                // $returnData['code'] = 2;
                // $returnData['msg'] = 'fail';
            	exit(json_encode($returnData));
            }
		} else {
			$returnData = array('msg' => '订单有问题', 'code' => 0);
			// $returnData['code'] = 0;
   //          $returnData['msg'] = '订单有问题';
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



}