<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/5
 * Time: 15:20
 */
namespace Mobile\Controller;
use Think\Controller;
use PayMethod\alipaydirect\lib\AlipaySubmit;
use PayMethod\alipaydirect\lib\AlipayNotify;


class AlipayWapPayController extends Controller
{
    private $alipay_config;
    private $i;

    public function __construct(){
        Controller::__construct();

        $configModel = D('config');
        $condition['config_type'] = "alipay";
        $condition['restaurant_id'] = session("restaurant_id");
//        $condition['restaurant_id'] = 1;
        $alipayConfig = $configModel->where($condition)->select();
        $alipayC = dealConfigKeyForValue($alipayConfig);

        $alipay_config = array();
        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        $alipay_config['partner'] = $alipayC['alipay_appid'];

        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        $alipay_config['seller_id']	= $alipayC['alipay_mchid'];

        // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
//        $alipay_config['key'] = '5k4ttknt96q5vnu5cctrd11jg2gf7ziq';
        $alipay_config['key'] = $alipayC['alipay_key'];

        // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $alipay_config['notify_url'] = "http://shop.founpad.com/index.php/mobile/AlipayWapPay/notify";
//        $alipay_config['notify_url'] = "http://120.77.39.169/index.php/home/AlipayDirect/notify";

        // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
//        $alipay_config['return_url'] = "http://192.168.31.101/create_direct_pay_by_user-PHP-UTF-8/return_url.php";

        //签名方式
        $alipay_config['sign_type'] = strtoupper('MD5');

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset']= strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert']    = getcwd().'\\cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport']    = 'http';

        // 支付类型 ，无需修改
        $alipay_config['payment_type'] = "1";

        // 产品类型，无需修改
        $alipay_config['service'] = "create_direct_pay_by_user";

        //二维码的类型
        $alipay_config['qr_pay_mode'] = 4;

        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


        //↓↓↓↓↓↓↓↓↓↓ 请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

        // 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
        $alipay_config['anti_phishing_key'] = "";

        // 客户端的IP地址 非局域网的外网IP地址，如：221.0.0.1
        $alipay_config['exter_invoke_ip'] = "";
        $this->alipay_config = $alipay_config;
    }

    public function wapPay(){
        /**************************请求参数**************************/
        $out_trade_no = I('get.order_sn');
        session("order_sn",$out_trade_no);

        $orderModel = order();
        $o_condition['order_sn'] = $out_trade_no;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,restaurant_id")->find();
        $restaurant_id = $rel['restaurant_id'];
//        dump($rel);
        session("restaurant_id",$restaurant_id);

        //查询店铺餐桌二维码对应集成打印机的机器的机器码
        $qrc_code_model = D("qrc_code");
        $qrc_condition['restaurant_id'] = $restaurant_id;
        $qrc_code_id = $qrc_code_model->where($qrc_condition)->field("qrc_code_id")->find()['qrc_code_id'];
        $qrc_device_model = D("qrc_device");
        $qrcd_condition['qrc_code_id'] = $qrc_code_id;
        $device_code = $qrc_device_model->where($qrcd_condition)->field('qrc_device_code')->find()['qrc_device_code'];

        $this::__construct();

        //订单名称，必填
        $restaurant_name = D("restaurant")->where(array("restaurant_id"=>$restaurant_id))->getField("restaurant_name");
        if($restaurant_name){
            $subject = $restaurant_name;
            $body = $restaurant_name;
        }else{
            $subject = "方派智慧点餐";
            $body = "方派智慧点餐";
        }
//        $subject = "云牛测试";

        //付款金额，必填
//        $total_fee = $rel['total_amount'];
        $total_fee = 0.01;

        //商品描述，可空
//        $body = "云牛测试";

        //公用回传参数
        $extra_common_param  = $device_code;

        //收银台页面上，商品展示的超链接，必填
//        $show_url = $_POST['WIDshow_url'];
        $show_url = "123123";

        $alipay_config = $this->alipay_config;

        /************************************************************/

//构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => $alipay_config['service'],
            "partner"       => $alipay_config['partner'],
            "seller_id"  => $alipay_config['seller_id'],
            "payment_type"	=> $alipay_config['payment_type'],
            "notify_url"	=> $alipay_config['notify_url'],
            "return_url"	=> $alipay_config['return_url'],
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
            "out_trade_no"	=> $out_trade_no,
            "subject"	=> $subject,
            "total_fee"	=> $total_fee,
            "show_url"	=> $show_url,
            //"app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
            "body"	=> $body,
            "extra_common_param" => $extra_common_param,
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.2Z6TSk&treeId=60&articleId=103693&docType=1
            //如"参数名"	=> "参数值"   注：上一个参数末尾需要“,”逗号。

        );

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        $this->assign("html_text",$html_text);
        $this->display();
    }

    public function notify(){
        $out_trade_no = $_POST['out_trade_no'];
//        $fp = fopen(__DIR__."/log.txt","w") or die("Unable to open file!");
//        fwrite($fp,$out_trade_no."|");
        $extra_common_param = $_POST['extra_common_param'];
//        fwrite($fp,$extra_common_param);
//        fclose($fp);
        $orderModel = order();
        $ono_condition['order_sn'] = $out_trade_no;
        $orderInfo = $orderModel->where($ono_condition)->find();
        session('restaurant_id',$orderInfo['restaurant_id']);
        $this::__construct();


        $alipayNotify = new AlipayNotify($this->alipay_config);
        $verify_result = $alipayNotify->verifyNotify();


        if($verify_result) {//验证成功
            //商户订单号

//            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            $extra_common_param = $_POST['extra_common_param'];

            if($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //操作数据库处理订单信息；
                $order_sn = $out_trade_no;
                $orderModel = order();
                $o_condition['order_sn'] = $order_sn;
                $orderInfo = $orderModel->where($o_condition)->field("order_status,pay_time")->find();
                $order_status = $orderInfo['order_status'];
                $pay_time = $orderInfo['pay_time'];
                if($order_status >= 3 && $pay_time>0){
                    echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
                    exit;
                }else{
                    $data['order_status'] = 3;
                    $data['pay_type'] = 1;
                    $time = time();
                    $data['pay_time'] = $time;
                    $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态

                    if($rel !== false){
                        $this->i++;
                        $returnData['status'] = 2;
                        $returnData['order_sn'] = $order_sn;

                        $device_code = $extra_common_param;

                        $rel2 = sendInfo($returnData,$device_code);
                        if($rel2['errmsg'] == "Succeed"){
                            $txt = $this->i;
                            $txt.= "|".$order_sn."|".date("Y-m-d H:i:s",time())."\r\n";
                            file_put_contents(__DIR__."/log.txt",$txt , FILE_APPEND);
                        }
                    }
                }

                //推送信息到android设备，使其打印小票

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";		//请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    public function tag(){
        $this->assign("order_sn",session('order_sn'));
        $this->display();
    }
}