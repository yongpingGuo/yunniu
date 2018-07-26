<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Encrypt;
use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;
use ElemeOpenApi\Api\UserService;
Vendor('ElemeOpenApi.Api.UserService');
Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.OAuth.OAuthClient');
class DataDockController extends Controller {

    public function __construct(){
        Controller::__construct();
        $admin_id = session("re_admin_id");
        if(!$admin_id){
            redirect("Index/login");
        }
        $restaurant_manager_model = D('restaurant_manager');
        $restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
        session('restaurant_id',$restaurant_id);
    }

    /**
     * 获取支付信息
     */
    public function dataForPay(){
        $configModel = D("config");
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session('restaurant_id');
        $wx_config = $configModel->where($condition)->select();
        $wx_config_list = dealConfigKeyForValue($wx_config);
        $this->assign("wx_config",$wx_config_list);

        $condition['config_type'] = "alipay";
        $alipay_config = $configModel->where($condition)->select();
        $alipay_config_list = dealConfigKeyForValue($alipay_config);
        $this->assign("alipay_config",$alipay_config_list);

        $pay_select_model = D('pay_select');
        $ps_condition['restaurant_id'] = session('restaurant_id');
        $pay_select_config = $pay_select_model->where($ps_condition)->select();
        $this->assign("pay_select",$pay_select_config);

        $fourth_model = D('fourth');
        $fm_condition['restaurant_id'] = session('restaurant_id');
//        $fourth_config = $fourth_model->where($fm_condition)->select();
        $fourth_config = $fourth_model->where($fm_condition)->find();
//        $this->assign("fourth",$fourth_config[0]);
        $key            = C("F_KEY");
        $en             = new Encrypt();
        $fourth_config['pwd'] = $en->decrypt($fourth_config['pwd'], $key);
        $this->assign("fourth",$fourth_config);

        $pay_mode_model = D('pay_mode');
        $pm_condition['restaurant_id'] = session('restaurant_id');
        $pay_mode_config = $pay_mode_model->where($pm_condition)->select();
        $this->assign("pay_mode",$pay_mode_config[0]);

        $restaurant_other_info = D("restaurant_other_info");
        $roi_where['restaurant_id'] = session("restaurant_id");
        $rel = $restaurant_other_info->where($roi_where)->find();
        $pid = $rel['pay_number'];
        if(empty($pid)){
            $pid = 0;
        }
        $this->assign("pid",$pid);

        $this->display('dataForPay');
    }

    /**
     * 增加修改支付信息
     */
    public function editAddPayInfo(){
        $type = I("get.type");
        $configModel = D('config');
        $configModel->startTrans();
        $pay_data = I('post.');
        unset($pay_data['wechat-code']);

        $data['restaurant_id'] = session("restaurant_id");
        $data['config_type'] = $type;
        foreach($pay_data as $key => $val){
            $data['config_name'] = $key;
            $data['config_value'] = $val;
            $condition['config_name'] = $key;
            $condition['restaurant_id'] = $data['restaurant_id'];
            $tempRel = $configModel->field("config_id")->where($condition)->find();

            if($tempRel){
                $data2['config_id'] = $tempRel['config_id'];
                $rel = $configModel->where($data2)->save($data);
            }else{
                $rel = $configModel->add($data);
            }
            if($rel === false){
                $configModel->rollback();
            }
        }
        $configModel->commit();
    }
    //第四方支付
    public function editAddPayInfos()
    {
        $fourthModel = D("fourth");
        $data = $fourthModel->create();
        $restaurant_id = session("restaurant_id");
        $data['restaurant_id'] = $restaurant_id;
        $condition['restaurant_id'] = $restaurant_id;
        $tempRel = $fourthModel->where($condition)->find();
        if($tempRel){
            // 判断密码是否有改动过
            /*if($data['pwd'] == $tempRel['pwd']){
                // 没改动，只更新account值
                $rel = $fourthModel->where($condition)->save(array('account'=>$data['account']));
            }else{
                // 有改动
                $key            = C("F_KEY");
                $en             = new Encrypt();
                $data['pwd'] = $en->encrypt($data['pwd'], $key);
                $rel = $fourthModel->where($condition)->save($data);
            }*/
            $key            = C("F_KEY");
            $en             = new Encrypt();
            $data['pwd'] = $en->encrypt($data['pwd'], $key);
            $rel = $fourthModel->where($condition)->save($data);

            if($rel !== false){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }
        }else{
            $key            = C("F_KEY");
            $en             = new Encrypt();
            $data['pwd'] = $en->encrypt($data['pwd'], $key);
            $rel = $fourthModel->add($data);
            if($rel){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }
        }
    }

    /**
     * 增加/修改打印机
     */
    public function addEditPrinter(){
        $type = I("post.type");
        $printerModel = D("printer");
        $printerInfo = $printerModel->create();
        $printerInfo['restaurant_id'] = session('restaurant_id');
        if($type == "add"){
            $rel = $printerModel->add($printerInfo);
        }
        if($type == "edit"){
//            dump($printerInfo);
            $rel = $printerModel->save($printerInfo);
        }

        if($rel !== false){
            $returnMsg['code'] = 1;
            $returnMsg['msg'] = "操作成功";
            exit(json_encode($returnMsg));
        }
    }

    /**
     * 打印机显示页面
     */
    public function printer(){
        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
        $Model = M();
        foreach ($printList as $k => $v) {
            $ifPrint = $Model->query('SELECT print_id FROM food WHERE find_in_set("'.$v['printer_id'].'", print_id)');
            $ifPrint_tag = $Model->query('SELECT tag_print_id FROM food WHERE find_in_set("'.$v['printer_id'].'", tag_print_id)');
            if ($ifPrint || $ifPrint_tag) {
                $printList[$k]['if_use'] = '使用中';
            }elseif (!$ifPrint && !$ifPrint_tag) {
                $printList[$k]['if_use'] = '未使用';
            }
        }
       
        $this->assign('printList',$printList);
        $this->display();
    }

    /**
     * 删除打印机
     */
    public function deletePrinter(){
        $printer_id = I("post.printer_id");
//        dump($printer_id);
        $condition['printer_id'] = $printer_id;
        $printerModel = D('printer');
        $rel = $printerModel->where($condition)->delete();
        if($rel !== false){
            $returnMsg['code'] = 1;
            $returnMsg['msg'] = "操作成功";
            exit(json_encode($returnMsg));
        }
    }

    /**
     * 获取当前拥有的打印机（返回json数据）
     */
    public function getPrinter(){
        $printerModel = D('printer');
        $condition['restaurant_id'] = session("restaurant_id");
        $printerList = $printerModel->where($condition)->select();
        exit(json_encode($printerList));
    }

    public function selectPay(){
        $pay_select = D('pay_select');
        $data = $pay_select->create();
        $condition['restaurant_id'] = session("restaurant_id");
        $condition['config_name'] = $data['config_name'];
        $pay_select->where($condition)->save($data);
    }

    public function selectMode(){
        $pay_mode = D('pay_mode');
        $data = $pay_mode->create();
        $condition['restaurant_id'] = session("restaurant_id");
        $data['restaurant_id'] = session("restaurant_id");
        $modeData = $pay_mode->where($condition)->find();
        if ($modeData == '') {
            $pay_mode->where($condition)->add($data);
        }else{
            $pay_mode->where($condition)->save($data);
        }
        
        $pay_select = D('pay_select');
        if ($data['mode'] == 1) {
            $condition1['config_name'] = 'wechat-code';
            $condition1['restaurant_id'] = session("restaurant_id");
            $data1['value'] = 1;
            $pay_select->where($condition1)->save($data1);
            $condition2['config_name'] = 'ali-code';
            $condition2['restaurant_id'] = session("restaurant_id");
            $data2['value'] = 1;
            $pay_select->where($condition2)->save($data2);
        }elseif ($data['mode'] == 2) {
            $condition1['config_name'] = 'wechat-code';
            $condition1['restaurant_id'] = session("restaurant_id");
            $data1['value'] = 0;
            $pay_select->where($condition1)->save($data1);
            $condition2['config_name'] = 'ali-code';
            $condition2['restaurant_id'] = session("restaurant_id");
            $data2['value'] = 0;
            $pay_select->where($condition2)->save($data2);
        }
    }

    //获取支付宝授权
    public function setAppAuthToken(){
        $restaurant_id = session("restaurant_id");
        $app_auth_token = I("app_auth_token");
        $user_id = I("user_id");

        $restaurant_other_info = D('restaurant_other_info');

        $data2['restaurant_id'] = $restaurant_id;
        $data['restaurant_id'] = $restaurant_id;
        $data['app_auth_token'] = $app_auth_token;
        $data['pay_number'] = $user_id;

        $find_result = $restaurant_other_info->where($data2)->find();

        if($find_result){
            $add_rel = $restaurant_other_info->where($data2)->save($data);
            if($add_rel){
                echo "授权成功";
            }else{
                echo "授权失败";
            }
        }else{
            $add_rel = $restaurant_other_info->add($data);
            if($add_rel){
                echo "授权成功";
            }else{
                echo "授权失败";
            }
        }
    }

    //支付宝创建门店信息
    public function createShop(){
//        $post_data = I("");
//        if($post_data){
            $app_auth_token = $this->getAppAuthToken();
//            $app_auth_token = "";
            $restaurant_other_info = D('restaurant_other_info');
            vendor("alipayGrant.AopClient");
            $al = new \AopClient();

            $request2 = new \AlipayOfflineMarketShopCreateRequest();

            $content2['store_id'] = "hz009";    // 外部门店编号
            $content2['category_id'] = "2015050700000018";  // 类目ID
            $content2['brand_name'] = "YUNNIU";        // 品牌名
//            $content2['brand_logo'] = "1T8Pp00AT7eo9NoAJkMR3AAAACMAAQEC";   // 品牌LOGO; 图片ID，不填写则默认为门店首图main_image。
            $content2['main_shop_name'] = "爱尚咖啡厅";        // 主门店名
            $content2['branch_shop_name'] = "东圃1011号店";     // 分店名称
            $content2['province_code'] = "440000";          // 省份编码
            $content2['city_code'] = "440100";              // 城市编码
            $content2['district_code'] = "440106";          // 区县编码
            $content2['address'] = "东圃阳光桃源";  //  门店详细地址
            $content2['longitude'] = "113.420738";          // 经度
            $content2['latitude'] = "23.118558";          // 纬度
            $content2['contact_number'] = "13612344321,021-12336754";   // 门店电话号码
            $content2['notify_mobile'] = "13867498729";         // 门店店长电话号码
            $content2['main_image'] = "cH6qfIzsT1iJmAa3GESTswAAACMAAQED";   // 门店首图
            // 门店审核时需要的图片；至少包含一张门头照片，两张内景照片
            $content2['audit_images'] = "cH6qfIzsT1iJmAa3GESTswAAACMAAQED,cH6qfIzsT1iJmAa3GESTswAAACMAAQED,cH6qfIzsT1iJmAa3GESTswAAACMAAQED";    // 门店审核时需要的图片
            $content2['business_time'] = "周一-周五 09:00-20:00,周六-周日 10:00-22:00";
            $content2['wifi'] = "T";
            $content2['parking'] = "F";
            $content2['value_added'] = "免费茶水、免费糖果";
            $content2['avg_price'] = "35";
            $content2['isv_uid'] = "2088421780481061";  // ISV返佣id
            $content2['licence'] = "1T8Pp00AT7eo9NoAJkMR3AAAACMAAQEC";
            $content2['licence_code'] = "H001232";
            $content2['licence_name'] = "广州云牛网络科技有限公司";
//            $content2['business_certificate'] = "cH6qfIzsT1iJmAa3GESTswAAACMAAQED";
//            $content2['business_certificate_expires'] = "2020-03-20";
//            $content2['auth_letter'] = "cH6qfIzsT1iJmAa3GESTswAAACMAAQED";
            $content2['is_operating_online'] = "T";
//            $content2['online_url'] = "http://shop.founya.com";
            $content2['operate_notify_url'] = "http://shop.founya.com/component/test/notifyInfo";
            // $content2['implement_id'] = "HU002,HT002";
            $content2['no_smoking'] = "T";
            $content2['box'] = "T";
            $content2['request_id'] = "2015123235324536";
//            $content2['other_authorization'] = "cH6qfIzsT1iJmAa3GESTswAAACMAAQED";
//            $content2['licence_expires'] = "2020-10-20";
            $content2['op_role'] = "ISV";
            $content2['biz_version'] = "2.0";

            $content2 = json_encode($content2);
            $request2->setBizContent($content2);
            $result2 = $al->execute ( $request2,null,$app_auth_token);

            $responseNode = str_replace(".", "_", $request2->getApiMethodName()) . "_response";

            $resultCode = $result2->$responseNode->code;

            dump($result2);
            if(!empty($resultCode)&&$resultCode == 10000){
                $response = $result2->alipay_offline_market_shop_create_response;
                $shop_id = $response->apply_id;
                $data2['shop_id'] = $shop_id;
                $data['restaurant_id'] = session('restaurant_id');
                $restaurant_other_info->where($data)->save($data2);
                echo "创建成功";
            } else {
                echo "创建失败",$resultCode;
            }
//        }else{
//            $this->display();
//        }
    }

    //支付宝上传门店照片和视频接口
    public function aliUploadImg(){
        vendor("alipayGrant.AopClient");
        vendor("alipayGrant.AlipayOfflineMaterialImageUploadRequest");
        $al = new \AopClient();
        $app_auth_token = $this->getAppAuthToken();
        $request = new \AlipayOfflineMaterialImageUploadRequest();
        $request->setImageType("jpg");
        $request->setImageName("测试图片");
        $request->setImageContent("@"."/www/web/founya/xiaomianmendian.jpg");
        $request->setImagePid("2088021822217233");
        $result = $al->execute ( $request,null,$app_auth_token);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        dump($result);
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }

    //获取当前店铺的$app_auth_token
    public function getAppAuthToken(){
        $restaurant_id = session("restaurant_id");
        $restaurant_other_info = D('restaurant_other_info');
        $data['restaurant_id'] = $restaurant_id;
        $find_result = $restaurant_other_info->where($data)->find();

        $app_auth_token = $find_result['app_auth_token'];

        return $app_auth_token;
    }

    // 外卖对接
    public function meituanTest(){
        $restaurant_id = session('restaurant_id');
        // 避免美团由于运营商问题请求超时出错，所以将店铺名在店铺绑定的那一刻就跟绑定信息绑定了
        $restaurant_name = D('meituan')->where(array('app_poi_code'=>$restaurant_id))->getField('restaurant_name');
        $this->assign('restaurant_name',$restaurant_name);  // 如果为空则为未绑定

        $config_info = D('meituan_config')->find();    // 从美团配置表取出配置信息

        $developerId = $config_info['developerId'];  // 美团聚宝盆开发者ID
        $businessId = 2;        // 2代表外卖项目
        $ePoiId = session('restaurant_id'); // 店铺ID，作为自定义参数传给美团，成为该店铺的标识
        $signKey = $config_info['signkey'];    // 从美团配置表取出配置信息
        $url = 'https://open-erp.meituan.com/storemap?developerId='.$developerId.'&businessId='.$businessId.'&ePoiId='.$ePoiId.'&signKey='.$signKey;
        // 美团授权跳转的url
        $this->assign('url',$url);

        $condition['restaurant_id'] = session('restaurant_id');
        // bill_foot_language美团外卖小票底部广告语，eleme_bill_foot_language饿了么外卖小票底部广告语
        $language_info = D('Restaurant')->where($condition)->field('bill_foot_language,eleme_bill_foot_language')->find();
        // 底部广告语
        $this->assign("language_info",$language_info);

        # 饿了么授权
        $where['restaurant_id'] = session('restaurant_id');
        $token_info = D('eleme_token')->where($where)->find();
        $eleme_config = D('eleme_config')->find();
        $app_key = $eleme_config['app_key'];
        $app_secret = $eleme_config['app_secret'];

        //实例化一个配置类
        $config = new Config($app_key, $app_secret, C("ELEME_ENVIRONMENT"));
        //使用config对象，实例化一个授权类
        $client = new OAuthClient($config);

        // 初始化授权情况，1为未授权，2为已经授权，3为需要重新授权，以便前端视图显示
        $grant_situation = 1;   // 未授权

        // 已经授过权
        if($token_info){
            $grant_situation = 2;   // 2为已经授权

            // 在使用token前，要判断有没有过期，如果有，则进行refresh获取token
            $expires_in = $token_info['expires_in'];     // 有效期
            $create_time = $token_info['create_time'];  // 创建时间
            $now = time();  // 当前时间
            // 距离过期时间小于等于多少并且大于0就refresh获取token（单位：秒），如果距离时间小于0则refresh_token失效
            $range_time = $expires_in-($now-$create_time);
            if($range_time<C("ELEME_EXPIRES_IN") && $range_time>0){
                $refresh_token = $token_info['refresh_token'];
                $scope = "all";
                $return = $client->get_token_by_refresh_token($refresh_token, $scope);
                $arr = (array)$return;

                $save['access_token'] = $arr['access_token'];
                $save['expires_in'] = $arr['expires_in'];
                $save['refresh_token'] = $arr['refresh_token'];
                $save['create_time'] = time();
                $res = D('eleme_token')->where($where)->save($save);

                // 如果refresh了token就重新获取数据库中的token数据供后续使用
                $token_info = D('eleme_token')->where($where)->find();
            }elseif($range_time<=0){
                // refresh_token也失效，只能重新授权
                $del = D('eleme_token')->where(array('restaurant_id'=>session('restaurant_id')))->delete();
                $grant_situation = 3;   // 3为需要重新授权
                file_put_contents(__DIR__."/"."grant_against.txt","restaurant_id:".$restaurant_id."|range_time:".$range_time.
                    "|expires_in:".$expires_in."|now:".$now.'|create_time:'.$create_time.'|C:'.C("ELEME_EXPIRES_IN").
                    "|时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
            }
        }
        // 视图中已绑定了的店铺中该显示的内容
        if($grant_situation == 1){
            $display_content['code'] = 1;
            $display_content['msg'] = '未绑定';
        }elseif($grant_situation == 2){
            $display_content['code'] = 2;
            $display_content['msg'] = $token_info['restaurant_name'];
        }else{
            $display_content['code'] = 3;
            $display_content['msg'] = '需要重新授权';
        }
        $this->assign('display_content',$display_content);

        $this->assign('grant_situation',$grant_situation);  // 视图辨别授权情况的标识

        // 创建授权链接
        //根据OAuth2.0中的对应state，scope和callback_url，获取授权URL
        $state = session('restaurant_id');
        $scope = 'all';
        $callback_url = urlencode("https://eleme.cloudabull.com/admin/Elemepush/call_back");
        $auth_url = $client->get_auth_url($state, $scope, $callback_url);
        $this->assign('auth_url',$auth_url);

        // 美团解绑链接
        $config_info = D('meituan_config')->field('developerId,signkey')->find();
        $ePoiId = session('restaurant_id');
        $businessId = 2;
        $appAuthToken = D('meituan')->where(array('app_poi_code'=>$ePoiId))->getField('appAuthToken');
        if($appAuthToken){
            // 已授权
            $this->assign('has_bind',1);
            $unbind_url = "https://open-erp.meituan.com/releasebinding?signKey=".$config_info['signkey']."&businessId=".$businessId."&appAuthToken=".$appAuthToken;
            $this->assign('unbind_url',$unbind_url);
        }else{
            // 未授权
            $this->assign('has_bind',0);
        }
//        redirect("https://open-erp.meituan.com/releasebinding?signKey=".$config_info['signkey']."&businessId=".$businessId."&appAuthToken=".$appAuthToken);
        $this->display('meituanTest');
    }

    //票据底部广告语设置
    public function adv_langSet(){
        $type = I('post.type');
        if($type == "meituan"){
            $condition['bill_foot_language'] = I('post.bill_foot_language');
            $return = $condition['bill_foot_language'];
        }else{
            $condition['eleme_bill_foot_language'] = I('post.eleme_bill_foot_language');
            $return = $condition['eleme_bill_foot_language'];
        }
        $condition['restaurant_id'] = session('restaurant_id');
        $restaurant = D('Restaurant');
        $n = $restaurant->save($condition);
        exit(json_encode($return));
    }
}
