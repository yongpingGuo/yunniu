<?php
namespace MobileAdmin\Controller;
use Think\Controller;
use Think\Encrypt;
use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;
use ElemeOpenApi\Api\UserService;
Vendor('ElemeOpenApi.Api.UserService');
Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.OAuth.OAuthClient');
class WaimaiController extends Controller {

    public function __construct(){
        Controller::__construct();
        $admin_id = session("re_admin_id");
        if(!$admin_id){
            redirect("/index.php/MobileAdmin/Index/login");
        }
        $restaurant_manager_model = D('restaurant_manager');
        $restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
        session('restaurant_id',$restaurant_id);
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
            }
        }
        // 视图中已绑定了的店铺中该显示的内容
        if($grant_situation == 1){
            $display_content = '未绑定';
        }elseif($grant_situation == 2){
            $display_content = $token_info['restaurant_name'];
        }else{
            $display_content = '需要重新授权';
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
        $this->display();
    }
    
    // 美团对接
    public function index(){
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
        $this->display();
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

    // 饿了么对接
    public function eleme(){
        $restaurant_id = session('restaurant_id');
        $condition['restaurant_id'] = $restaurant_id;
        // eleme_bill_foot_language饿了么外卖小票底部广告语
        $language_info = D('Restaurant')->where($condition)->getField('eleme_bill_foot_language');
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
            $display_content = '未绑定';
        }elseif($grant_situation == 2){
            $display_content = $token_info['restaurant_name'];
        }else{
            $display_content = '需要重新授权';
        }
        $this->assign('display_content',$display_content);

        $this->assign('grant_situation',$grant_situation);  // 视图辨别授权情况的标识

        // 创建授权链接
        //根据OAuth2.0中的对应state，scope和callback_url，获取授权URL
        $state = $restaurant_id;
        $scope = 'all';
        $callback_url = urlencode("https://eleme.cloudabull.com/admin/Elemepush/call_back");
        $auth_url = $client->get_auth_url($state, $scope, $callback_url);
        $this->assign('auth_url',$auth_url);

        $this->display();
    }
}
