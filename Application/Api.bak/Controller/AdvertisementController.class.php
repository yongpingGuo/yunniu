<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/15
 * Time: 14:08
 */

namespace Api\Controller;

class AdvertisementController extends BaseController
{
    /*public function __construct()
    {
        $token = I("post.token");
        $condition['token'] = $token;
        $info = D("interface_login_check")->where($condition)->find();

        if(!$info){
            $returnData['code'] = "0";
            $returnData['msg'] = "非法访问";
            exit(json_encode($returnData));
        }
    }*/

    /**
     * 获取店铺广告
     * 方式：get or post 参数：device_code(测试用，实际用cookie中的device_code) advertisement_type(必须有)
     */
    public function getAdvertisementList(){
        $device_code = cookie("device_code");

        if(!empty(I('post.device_code'))){
            $device_code = I('post.device_code');
        }

        $advertisement_type = I("advertisement_type");
        $this->isLogin($device_code);
        $restaurant_id = session("restaurant_id");

        $re_where['restaurant_id'] = $restaurant_id;
        $restaurant_model = D("restaurant");
        $advertise_time = $restaurant_model->where($re_where)->field("advertise_time")->find()['advertise_time'];

        $advertisement_model = D("advertisement");
        $where['restaurant_id'] = $restaurant_id;
        $where['advertisement_type'] = $advertisement_type;
        $advertisement_list = $advertisement_model->where($where)->select();

        foreach($advertisement_list as $key => $val){
            $advertisement_image_url = substr($val['advertisement_image_url'],1,strlen($val['advertisement_image_url']));
            $advertisement_list[$key]['advertisement_image_url'] = "http://".$_SERVER['HTTP_HOST'].$advertisement_image_url;
        }

        if(!empty($advertisement_list)){
            $returnDate['code'] = 1;
            $returnDate['msg'] = "获取成功";
            $returnDate['advertise_time'] = $advertise_time;
            $returnDate['data'] = $advertisement_list;
        }else{
            $returnDate['code'] = 0;
            $returnDate['msg'] = "获取失败";
            $returnDate['advertise_time'] = $advertise_time;
            $returnDate['data'] = $advertisement_list;
        }
        exit(json_encode($returnDate));
    }
    //获取会员广告
      public function getAdvertisementList_vip(){
        $device_code = cookie("device_code");

        if(!empty(I('post.device_code'))){
            $device_code = I('post.device_code');
        }

        // $advertisement_type = I("advertisement_type");
        $this->isLogin($device_code);
        $restaurant_id = session("restaurant_id");

        $re_where['restaurant_id'] = $restaurant_id;
        $restaurant_model = D("restaurant");
        $advertise_time = $restaurant_model->where($re_where)->field("advertise_time")->find()['advertise_time'];

        $advertisement_model = D("advertisement_vip");
        $where['restaurant_id'] = $restaurant_id;
        // $where['advertisement_type'] = $advertisement_type;
        $advertisement_list = $advertisement_model->where($where)->select();

        foreach($advertisement_list as $key => $val){
            $advertisement_image_url = substr($val['advertisement_image_url'],1,strlen($val['advertisement_image_url']));
            $advertisement_list[$key]['advertisement_image_url'] = "http://".$_SERVER['HTTP_HOST'].$advertisement_image_url;
        }

        if(!empty($advertisement_list)){
            $returnDate['code'] = 1;
            $returnDate['msg'] = "获取成功";
            $returnDate['advertise_time'] = $advertise_time;
            $returnDate['data'] = $advertisement_list;
        }else{
            $returnDate['code'] = 0;
            $returnDate['msg'] = "获取失败";
            $returnDate['advertise_time'] = $advertise_time;
            $returnDate['data'] = $advertisement_list;
        }
        exit(json_encode($returnDate));
    }
}