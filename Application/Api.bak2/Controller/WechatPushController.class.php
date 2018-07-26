<?php
namespace Api\Controller;
use Think\Controller;
use Gaoming13\WechatPhpSdk\Api;

Class WechatPushController extends Controller
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
        // 判断access_token的存在
        $key = 'access_token'.$_SESSION['restaurant_id'];

        if(!$_SESSION['restaurant_id']){
            die('restaurant_id丢失');
        }else{
            $restaurant_id = $_SESSION['restaurant_id'];
            $data = M('wechat')->where('restaurant_id=%d',$restaurant_id)->find();
            self::$appId = $data['appid'];
            self::$appSecret=$data['appsecret'];
        }

        if (empty(S("$key"))) {
            $appid = self::$appId;
            $appSecret = self::$appSecret;
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appSecret}";
            $res = self::curlHttp($url);
            file_put_contents("./"."wechat_push.txt", "**********获取accessToken返回数据".$res.'.....'.time() , FILE_APPEND);
            $arr = json_decode($res,true);
            $access_token = $arr['access_token'];
        }else{
            $access_token = S("$key");
        }
        return $access_token;
    }

    //获取模板id
    public function getTemplateId($access_token,$title = '订单状态更新')
    {
        //获取模板
        $url3 = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$access_token}";
        $res3 = self::curlHttp($url3);
        $templateList =  json_decode($res3,true);
        file_put_contents("./"."wechat_push.txt", "********获取模板列表返回数据".$templateList.'.....'.time() , FILE_APPEND);

        if(empty($templateList)){
            die();//没有添加模板信息
        }

        foreach($templateList['template_list'] as $key =>$val){
            if($val['title'] == $title){
                $template_id = $val['template_id'];
            }else{

            }
        }

        //查询是否有这个id
        if(!empty($template_id)){
            return $template_id;
        }
    }


    //发送模板
    static public function templateSend($openid, $data,$pushType = 3)
    {
        $access_token = self::get_access_token();   //获取accessToken
        $template_id  = self::getTemplateId($access_token) ;//获取模板id


        if(!empty($template_id)){
            //把传入的数据进行遍历输出
            foreach($data as $k=>$v){
                $first = $v['first'];
                $url = $v['url']? $v['url'] : "http://weixin.qq.com/download";
                $OrderSn = $v['OrderSn']? $v['OrderSn'] : '暂无信息';
                $OrderStatus = $v['OrderStatus']? $v['OrderStatus'] : '暂无信息';
                $remark = $v['remark']? $v['remark'] : '请点击查看详情';
            }

            $msg = array(
                'touser'=>$openid,
                'template_id'=>"$template_id",
                "url"=>"$url",
                'data'=>array(
                    'first'=>array(
                        'value'=>"$first",
                        "color"=>"#173177"
                    ),
                    'OrderSn'=>array(
                        'value'=>$OrderSn,
                        "color"=>"#173177"
                    ),
                    'OrderStatus'=>array(
                        'value'=>"$OrderStatus",
                        "color"=>"#173177"
                    ),
                    'remark'=>array(
                        'value'=>"$remark",
                        "color"=>"#173177"
                    )
                ),
            );

        }

        //发送信息
        $url5 = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $a5 = json_encode($msg);
        $res5 = self::curlPost($url5,$a5);

        file_put_contents("./"."wechat_push.txt", "******模板推送后返回数据".$res5.'.....'.time()."\r\n\r\n" , FILE_APPEND);

    }


    public function test()
    {   $_SESSION['restaurant_id'] = 131;
        $openid = 'oToCav09WviMqiubyf8I3BFRzh1I';
        $data['first'] = '您好!';
        $data['OrderSn'] = '20179999992564255';
        $data['url'] = "http://www.baidu.com";
        $data['OrderStatus'] = '已发货';
        $data['remark'] = '您的预点餐订单编号：11111已完成。请尽快前往取餐使用。柜号：C柜1号窗，取餐验证码：4111,点击这里查看详情。';

        $data2 = array('a'=>$data);
        $this->templateSend($openid,$data2);
    }
}