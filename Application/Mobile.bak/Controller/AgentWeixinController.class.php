<?php
namespace Mobile\Controller;
use Think\Controller;
use Gaoming13\WechatPhpSdk\Wechat;
use Gaoming13\WechatPhpSdk\Api;

//代理手机控制器
Class AgentWeixinController extends Controller
{
    protected $appId;
    protected $appSecret;

    public function index()
    {
        $type = I('get.type');
        $_SESSION['business_id'] = I('get.business_id');
        $where['business_id'] = I('get.business_id');
        $where['status'] = 1;

        $wx = M('agent_wechat')->field('appid')->where($where)->find();

        //js_sdk所需的参数
        //动态获取url
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $jsapi_ticket = $this->getJsApiTicket();
        $timestamp = time();
        $nonceStr = $this->getCode();
        $signature = "jsapi_ticket=".$jsapi_ticket."&noncestr=".$nonceStr."&timestamp=".$timestamp."&url=".$url;
        $signature = sha1($signature);

        $host = C('HOST_NAME');
        $this->assign('host',$host);
        $this->assign('appId',$wx['appid']);//该公众号的appid
        $this->assign('timestamp',$timestamp);
        $this->assign('nonceStr',$nonceStr);
        $this->assign('signature',$signature);
        $this->display('address');
    }


    public function city_list()
    {
        $ak = 'u5m11V1bBkG2ubDsubeLOhVB25D7pEFW';//百度开发者key
        $lat = $_GET['lat'];
        $lng = $_GET['lng'];
        $location = $lat.','.$lng;
        $url = "http://api.map.baidu.com/geocoder/v2/?location={$location}&output=json&pois=1&ak={$ak}";
        $res = $this->curlHttp($url);
        $res_arr = json_decode($res,true);

        if($res_arr['status'] == 0){
            $map['name'] = $res_arr["result"]["addressComponent"]["province"];
            $map['level'] = 1;
            $address = M('region')->field('id')->where($map)->find();
            $map2['parent_id'] = $address['id'];
            $city_list = M('region')->field('name,id')->where($map2)->select();
        }else{
            $city_list = null;
        }

        $this->ajaxReturn($city_list);
    }


    //计算两个经纬度之间的距离
    public function distanceAjax()
    {
        $where['business_id'] = $_SESSION['business_id'] = 25;
        $where['status'] = 1;
        $store = M('restaurant')->field('lat,lng,restaurant_id,restaurant_name,address,city1,city2,city3')->where($where)->select();
        //遍历去查询
        foreach($store as $k=>$v){
            $map['id'] = $v['city1'];
            $city1 = M('region')->where($map)->getField('name');

            $map2['id'] = $v['city2'];
            $city2 = M('region')->where($map2)->getField('name');

            $map3['id'] = $v['city3'];
            $city3 = M('region')->where($map3)->getField('name');

            $store["$k"]["city_detail"] = $city1.$city2.$city3.$v['address'];
        }
//        dump($store);
//
//        die();
        $lat1 = $_GET['lat'];
        $lng1 = $_GET['lng'];

        //type=0表示成功获取经纬度
        if($_GET['type'] == 0){
            foreach($store as $key=>$value){
                $lat2 = $value['lat'];
                $lng2 = $value['lng'];
                $store["$key"]['distance'] = $this->getDistance($lat1, $lng1, $lat2, $lng2)/1000;
            }

            //遍历排序
            $flag = array();
            foreach($store as $k=>$v){
                $flag[] = $v['distance'];
            }
            array_multisort($flag, SORT_ASC, $store);
        }


        $this->ajaxReturn($store);
    }

    /**
     * @desc 根据两点间的经纬度计算距离
     * @param float $lat 纬度值
     * @param float $lng 经度值
     */
    function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000; //approximate radius of earth in meters

        /*
        Convert these degrees to radians
        to work with the formula
        */

        $lat1 = ($lat1 * pi() ) / 180;
        $lng1 = ($lng1 * pi() ) / 180;

        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180;

        /*
        Using the
        Haversine formula

        http://en.wikipedia.org/wiki/Haversine_formula

        calculate the distance
        */

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }

    /**
     * get方式的CURL
     *string $url
     *
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


    //获取用户的access_token
    public function get_access_token()
    {
        // 判断access_token的存在
        if(!$_SESSION['business_id']){
            die('business_id丢失');
        }else{
            $shop_id = $_SESSION['shop_id'];
            $data = M('agent_wechat')->where('business_id=%d',$_SESSION['business_id'])->find();
            $this->appId = $data['appid'];
            $this->appSecret=$data['appsecret'];
        }

        $name = 'access_token'.$_SESSION['business_id'];
        if (empty(S("$name"))) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = $this->curlHttp($url);
            $arr = json_decode($res,true);
            $access_token = $arr['access_token'];
        }else{
            $access_token = S("$name");
        }
        return $access_token;
    }


    //获取js_ticket
    public function getJsApiTicket()
    {
        $js_ticket ='js_ticket'.$_SESSION['business_id'];
        $js_ticket_time = 'js_ticket_time'.$_SESSION['business_id'];

        if ($_SESSION["$js_ticket"] && $_SESSION["$js_ticket_time"] >time()) {
            $ticket = $_SESSION["$js_ticket"];
        }else{
            $tmp = $this->get_access_token();
            $bool = is_null(json_decode($tmp));

            //$bool为true的话$tmp为字符串，不为1的话说明$tmp为json数据
            if ($bool) {
                $access_token = $tmp;
            }else{
                $tmp_arr = json_decode($tmp,true);
                $access_token = $tmp_arr['access_token'];
            }
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
            $res = $this->curlHttp($url);

            $arr = json_decode($res,true);
            $ticket = $arr['ticket'];

            $_SESSION["$js_ticket"] = $ticket;
            $_SESSION["$js_ticket_time"] = time() + 7000;
        }

        return $ticket;
    }

    //获取随机码
    public function getCode($num=16)
    {
        $array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9');

        $tmpstr = '';
        $max = count($array);
        for ($i=1; $i < $num; $i++) {
            $key = rand(1,$max-1);  //'A' -> $array[0]
            $tmpstr .= $array[$key];
        }
        return $tmpstr;
    }


}