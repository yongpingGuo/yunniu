<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/10
 * Time: 14:38
 */
namespace Component\Controller;
use Think\Cache\Driver\Memcache;
use Think\Controller;
use Think\Verify;
use Think\Encrypt;

class TestController extends Controller
{
    public function test(){
        echo "当前controller：".CONTROLLER_NAME."<br>";
        echo "当前action：".ACTION_NAME;
        $temp = "http://".$_SERVER["HTTP_HOST"].U("Home/AlipayDirect/alipay_barcodePay");
        dump($temp);
        $wx_pay_prefix = C('WX_PAY_PREFIX');
        dump($wx_pay_prefix);
        $al_pay_prefix = C('AL_PAY_PREFIX');
        dump($al_pay_prefix);
        $this->display("Test");
    }
    //Telefunken智能家居更新
    public function androidupdate(){
        redirect('http://smart.cloudabull.com/static/Uploads/apk/Telefunken.apk', 0, '页面跳转中...');
    }
    public function iosupdate(){
        redirect('https://itunes.apple.com/us/app/telefunken-smart/id1404784066?l=zh&ls=1&mt=8', 0, '页面跳转中...');
    }

    function testW(){
        $this->display();
    }

    function TsendMsgToDevice(){
        $post_data = I("post.");
        $result = sendMsgToDistrictDevice($post_data);
        $this->assign("result",$result);
        $this->display("testW");
    }

    function testWorker(){
        $this->display();
    }

    function testSendInfo(){
        $returnData['status'] = 2;
        $returnData['order_sn'] = "123123123123";
        $device_code ="1C:CA:E3:34:B2:17";
        $rel2 = sendInfo($returnData,$device_code);
        dump($rel2);
    }

    function exportOrderInfo(){
        $orderModel = D("order");
        $startTime = mktime(0,0,0,date("m")-1,1,date('Y'));
        $endTime = mktime(0,0,0,date("m")+1,1,date('Y'))-1;

        $o_condition['pay_time'] = array("between",array($startTime,$endTime));
        $orderInfo = $orderModel->field("order_sn,total_amount,pay_time")->where($o_condition)->select();

        $title = array(
            "订单号","总价","支付时间"
        );

        $fileName = "test1";
        exportexcel($orderInfo,$title,$fileName);
    }

    function setRestaurantCookie(){
        cookie("restaurant_id",session("restaurant_id"),3600);
    }

    // 店铺后台收款对接处，前往官方授权
    function testCreate(){
        $restaurant_id = session("restaurant_id");
        $restaurant_other_info = D('restaurant_other_info');
        $aliNumber = I("aliNumber");
        $data1['pay_number'] = $aliNumber;
        $data1['restaurant_id'] = $restaurant_id;
        // 查询当前店铺当前aliNumber对应的记录
        $find_result = $restaurant_other_info->where($data1)->find();

        if($find_result){
            // 有对应的记录
            echo "授权成功";
            exit;
        }else{
            // 当前店铺没有对应pay_number的记录，则查找当前pay_number在其他店是否有对应的记录
            $where['pay_number'] = $aliNumber;
            $info = $restaurant_other_info->where($where)->find();
            if($info){
                // 如果有，则说明此则pay_number已经授权，更新当前店铺以前的记录，或者重新添加一条即可
                $data2['pay_number'] = $aliNumber;
                $data2['app_auth_token'] = $info['app_auth_token'];
                // 查询当前店铺是否已经有了记录（但pay_number不是当前pay_number）
                $if_has_record = $restaurant_other_info->where("restaurant_id = $restaurant_id")->find();
                if($if_has_record){
                    // 更新成当前pay_number对应的信息即可
                    $restaurant_other_info->where("restaurant_id = $restaurant_id")->save($data2);
                }else{
                    // 新增一条
                    $data2['restaurant_id'] = $restaurant_id;
                    $restaurant_other_info->add($data2);
                }
                echo "授权成功";
                exit;
            }else{
                // 在其他店也没有对应的记录，前往授权
                $app_id = 2017022305833230;
                $redirect_uri = urlencode("http://shop.founpad.com/index.php/component/test/notify");
                $url = "https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id=".$app_id."&redirect_uri=".$redirect_uri;
                header("location:".$url);
            }
        }
    }

    // 店铺后台收款对接处，前往官方授权后的回调
    function notify(){
        $app_auth_code = I("app_auth_code");
        $app_id = I("app_id");

        vendor("alipayGrant.AopClient");

        $al = new \AopClient();
        $request = new \AlipayOpenAuthTokenAppRequest();
        $content['grant_type'] = "authorization_code";
        $content['code'] = $app_auth_code;
        $content = json_encode($content);
        $request->setBizContent($content);
        $result = $al->execute($request);
        $response = $result->alipay_open_auth_token_app_response;
        if($response->code == 10000){
            $app_auth_token = $response->app_auth_token;
            $user_id = $response->user_id;
            $this->assign("app_auth_token",$app_auth_token);
            $this->assign("user_id",$user_id);
            $this->display();
//            header("location:http://shop.founya.com/admin/dataDock/setAppAuthToken/AppAuthToken/".$app_auth_token);
        }else{
            echo "授权失败";
        }
    }

    # 业务流水批量查询接口
    public function queryinfo(){

        vendor("alipayGrant.AopClient");

        $aop = new \AopClient ();

        $request = new \AlipayOfflineMarketApplyorderBatchqueryRequest ();

        $content['apply_ids'] = array("2017012100107000000026333637");
//        $content['request_ids'] = "'2015123235324534','2015123235324535'";
//        $content['biz_id'] = "2017011900107000000026069457";
//        $content['biz_id'] = "2017011800107000000025893363";
        $content['biz_type'] = "SHOP";
        $content['action'] = "CREATE_SHOP";
        $content['op_id'] = "2088421780481061";
        $content['status'] = "PROCESS";
        $content['start_time'] = "2017-01-01 10:51:57";
        $content['end_time'] = "2017-03-29 10:51:57";
        $content['op_role'] = "ISV";
        $content['page_no'] = "1";
        $content['page_size'] = "20";

        $content = json_encode($content);
        $request->setBizContent($content);
        $app_auth_token = "201701BB736f87cfb166460c8ea82543aa464X21";
        $result = $aop->execute ( $request,null,$app_auth_token);
        dump($result);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败",$resultCode;
        }
    }

    public function notifyInfo(){
        $info = I("");
        file_put_contents(__DIR__."/NOTIFYINFO.TXT",var_export($info,true)."\r\n",FILE_APPEND);
    }

    public function encrypt(){
        $key = C("SECRET_KEY");
        $data = "1488160195|17";
        $en = new Encrypt();
        echo $en->encrypt($data,$key);
    }
    public function decrypt(){
        $key = C("SECRET_KEY");
        $data = I("password");
        $en = new Encrypt();
        echo $en->decrypt($data,$key);
    }
}
