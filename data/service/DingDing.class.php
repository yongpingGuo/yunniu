<?php
namespace data\service;
/*
* 钉钉相关接口
*/
class DingDing extends BaseService{
    private $corpid;
    private $corpsecret;
    private $agent_id;
    public function __construct() {
        $this->corpid = "ding9d0e82321b65941e35c2f4657eb6378f";
        $this->corpsecret = "AkJ2JtYuWsjF7azQpq60T6Scrf-bkJffSlL_Tn8zO0fd1L6rRfhV1UrxbEZWl3wJ";
        $this->agent_id = "159863031";
    }
    /*
    *curl post json数据
    */
    public function postJson($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        Return $result;
    }
    /*
    *获取access_token
    */
    public function getAccessToken() {
      //  $access_token = S("access_token");
    //    if(empty($access_token)){
            $url = "https://oapi.dingtalk.com/gettoken?corpid=".$this->corpid."&corpsecret=".$this->corpsecret."";
            $access_token_info = json_decode(file_get_contents($url), true);
          //  S("access_token", $access_token_info['access_token'], 7000);
            $access_token = $access_token_info['access_token'];
      //  }
        Return $access_token;
    }
    /*
    *加载dd.config配置
    */
    public function getConfig($appid, $appsecret) {
        $access_token = $this->getAccessToken();
     //   $ticket = S("ticket");
        //if(empty($ticket)){
            $url = "https://oapi.dingtalk.com/get_jsapi_ticket?access_token=$access_token";
            $ticket_info = json_decode(file_get_contents($url), true);
          //  S("ticket", $ticket_info['ticket'], 7000);
            $ticket = $ticket_info['ticket'];
       // }
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < 16; $i++) {
          $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        $time = time();
        $sing_str = "jsapi_ticket=$ticket&noncestr=$str&timestamp=".$time."&url=http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."";
        $sing_str = sha1($sing_str);
        Return array("agent_id"=>$this->agent_id, 'corp_id'=>$this->corpid, 'time_stamp'=>$time, 'nonceStr'=>$str, 'signature'=>$sing_str);
    }
    /*
    *获取用户授权后openid信息
    */
    public function getOpenidInfo($code) {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/user/getuserinfo?access_token=".$access_token."&code=$code";
        $user_info = json_decode(file_get_contents($url, $data_string), true);//获取openid
        Return $user_info;
    }
    /*
    *获取成员详情
    */
    public function getUserInfo($user_id) {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/user/get?access_token=$access_token&userid=$user_id";
        $user_info = json_decode(file_get_contents($url), true);//获取openid
        Return $user_info;
    }
    /*
    *给用户发送消息
    */
    public function sendUserMsg($user_id, $msg) {
        $access_token = $this->getAccessToken();
        $url = "https://oapi.dingtalk.com/message/send?access_token=$access_token";
        $msg = array(
            "touser" => $user_id,
            "agentid" => $this->agent_id,
            "msgtype" => "text",
            "text" => array("content" => $msg)
        );
        $msg = json_encode($msg);
        $res = json_decode($this->postJson($url, $msg), true);
        if($res['errmsg'] != "ok") Return false;
        Return true;
    }
}
