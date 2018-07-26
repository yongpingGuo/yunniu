<?php
namespace Mobile\Controller;
use Think\Controller;
use Gaoming13\WechatPhpSdk\Wechat;
use Gaoming13\WechatPhpSdk\Api;

#手机端的微信控制器(静态)
Class WechatController extends Controller
{
    static protected $appId;
    static protected $appSecret;

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
     *post方式的curl
     *
     */
    public function curlPost($url,$post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        return $output;
    }



    //获取公众号的access_token
    public function get_access_token()
    {
//        $_SESSION['restaurant_id'] = 131;

        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }else{
            $restaurant_id = $_SESSION['restaurant_id'];
            $data = M('wechat')->where('restaurant_id=%d',$restaurant_id)->find();
            self::$appId = $data['appid'];
            self::$appSecret=$data['appsecret'];
        }
        // 判断access_token的存在
        $key = 'token'.$data['appid'].$_SESSION['restaurant_id'];

        if (empty(S("$key"))) {
            $appid = self::$appId;
            $appSecret = self::$appSecret;
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appSecret}";
            $res = self::curlHttp($url);
            $arr = json_decode($res,true);
            $access_token = $arr['access_token'];
            S("$key",$access_token,3600);
        }else{
            $access_token = S("$key");
        }

        return $access_token;
    }


    /**
     *获取用户openid,静默授权(先通过get拿到的shop_id去查询其设置的appid和appscrect)
     */
    static public function getUserInfo($url)
    {
        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }

        $restaurant_id = $_SESSION['restaurant_id'];

        $data = M('wechat')->where('restaurant_id=%d',$restaurant_id)->find();
        self::$appId = $data['appid'];
        self::$appSecret=$data['appsecret'];

        //实例化api
        $arr = array(
            'appId' => self::$appId,
            'appSecret' => self::$appSecret,
            'get_access_token' => function(){
                // 用户需要自己实现access_token的返回
                if (empty(S('access_token'))) {
                    $appId = self::$appId;
                    $appSecret = self::$appSecret;
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
                    $res = self::curlHttp($url);
                    $arr = json_decode($res,true);
                    $access_token	= $arr['access_token'];
                }else{
                    $access_token = S('access_token');
                }
                return $access_token;
            },
            'save_access_token' => function($token) {
                // 用户需要自己实现access_token的保存
                S('access_token', $token, 7100);
            }
        );
        $api = new Api($arr);

        $authorize_url = $api->get_authorize_url('snsapi_base', $url);
        header('location:'.$authorize_url);

    }


    //网页授权回调url,静默授权回调
    static public function redirectUri()
    {
        // 获取用户信息(用户授权)
        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }

        $data = M('wechat')->where('restaurant_id=%d', $_SESSION['restaurant_id'])->find();
        self::$appId = $data['appid'];
        self::$appSecret = $data['appsecret'];
        //实例化api
        $arr = array(
            'appId' => self::$appId,
            'appSecret' => self::$appSecret,
            'get_access_token' => function () {
                // 用户需要自己实现access_token的返回
                if (empty(S('access_token'))) {
                    $appId = self::$appId;
                    $appSecret = self::$appSecret;
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
                    $res = self::curlHttp($url);
                    $arr = json_decode($res, true);
                    $access_token = $arr['access_token'];
                } else {
                    $access_token = S('access_token');
                }
                return $access_token;
            },
            'save_access_token' => function ($token) {
                // 用户需要自己实现access_token的保存
                S('access_token', $token, 7100);
            }
        );

        $api = new Api($arr);
        list($err, $user_info) = $api->get_userinfo_by_authorize('snsapi_base');
        // 判断授权是否成功
        if ($user_info !== null) {
            $_SESSION['openid'] = $openid = $user_info->openid;//获取openid
//            $_SESSION['nickname'] = $user_info->nickname;
//            $_SESSION['headimgurl'] =$user_info->headimgurl;
//            $_SESSION['unionid'] =$user_info->unionid;
        }
        // 判断用户是否关注
        $access_token = self::get_access_token();

        if(empty($access_token)){
            $return['code'] = 1;
            $return['msg'] = '获取access_token出错';
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
            $data = self::curlHttp($url);
            $object = json_decode($data);
            $subscribe = $object->subscribe;

            if(empty($object)){
                $return['code'] = 3;
                $return['msg'] = '获取用户信息出错';
            }elseif($subscribe == 1){
                $return['code'] = 0;
                $return['msg'] = '此用户已关注';
            }elseif($subscribe === 0){
                $return['code'] = 4;
                $return['msg'] = '此用户未关注';
            }else{
                $return['code'] = 5;
                $return['msg'] = '其他错误';
            }
        }
        return $return;
    }


    //获取用户信息
    static public function userInfo($url)
    {
        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }

        $restaurant_id = $_SESSION['restaurant_id'];

        $data = M('wechat')->where('restaurant_id=%d',$restaurant_id)->find();
        self::$appId = $data['appid'];
        self::$appSecret=$data['appsecret'];

        //实例化api
        $arr = array(
            'appId' => self::$appId,
            'appSecret' => self::$appSecret,
            'get_access_token' => function(){
                // 用户需要自己实现access_token的返回
                if (empty(S('access_token'))) {
                    $appId = self::$appId;
                    $appSecret = self::$appSecret;
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
                    $res = self::curlHttp($url);
                    $arr = json_decode($res,true);
                    $access_token	= $arr['access_token'];
                }else{
                    $access_token = S('access_token');
                }
                return $access_token;
            },
            'save_access_token' => function($token) {
                // 用户需要自己实现access_token的保存
                S('access_token', $token, 7100);
            }
        );
        $api = new Api($arr);

        $authorize_url = $api->get_authorize_url('snsapi_userinfo', $url);
        header('location:'.$authorize_url);
    }


    //获取用户信息回调url
    static public function userInfoUrl()
    {
        // 获取用户信息(用户授权)
        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }

        $data = M('wechat')->where('restaurant_id=%d', $_SESSION['restaurant_id'])->find();
        self::$appId = $data['appid'];
        self::$appSecret = $data['appsecret'];
        //实例化api
        $arr = array(
            'appId' => self::$appId,
            'appSecret' => self::$appSecret,
            'get_access_token' => function () {
                // 用户需要自己实现access_token的返回
                if (empty(S('access_token'))) {
                    $appId = self::$appId;
                    $appSecret = self::$appSecret;
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
                    $res = self::curlHttp($url);
                    $arr = json_decode($res, true);
                    $access_token = $arr['access_token'];
                } else {
                    $access_token = S('access_token');
                }
                return $access_token;
            },
            'save_access_token' => function ($token) {
                // 用户需要自己实现access_token的保存
                S('access_token', $token, 7100);
            }
        );

        $api = new Api($arr);
        list($err, $user_info) = $api->get_userinfo_by_authorize('snsapi_userinfo');
        // 判断授权是否成功
        if ($user_info !== null) {
            $_SESSION['openid'] = $openid = $user_info->openid;//获取openid
            $_SESSION['nickname'] = $user_info->nickname;
            $_SESSION['headimgurl'] =$user_info->headimgurl;
            $_SESSION['unionid'] =$user_info->unionid;
        }

        // 判断用户是否关注
        $access_token = self::get_access_token();

        if(empty($access_token)){
            $return['code'] = 1;
            $return['msg'] = '获取access_token出错';
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
            $data = self::curlHttp($url);
            $object = json_decode($data);
            $subscribe = $object->subscribe;

            if(empty($object)){
                $return['code'] = 3;
                $return['msg'] = '获取用户信息出错';
            }elseif($subscribe == 1){
                $return['code'] = 0;
                $return['msg'] = '此用户已关注';
            }elseif($subscribe === 0){
                $return['code'] = 4;
                $return['msg'] = '此用户未关注';
            }else{
                $return['code'] = 5;
                $return['msg'] = '其他错误';
            }
        }
        return $return;
    }



}
