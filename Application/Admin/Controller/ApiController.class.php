<?php
namespace Admin\Controller;
use think\Controller;

class ApiController extends Controller
{
    protected $equipment_type; //设备类型

    public function __construct()
    {
        parent::__construct();
        $this->equipment_type = C("equipment_type");
    }

    /**
     * 获取电子餐牌信息
     * 方式：get,post 参数：device_code
     */
    public function getBillBoardDeviceInfo()
    {
        $device_code = I("device_code");
        $bill_board_model = D("bill_board");
        $where['bill_board_code'] = $device_code;
        $bill_board_info = $bill_board_model->where($where)->find();
        $returnData = array();
        if ($bill_board_info) {
            $bill_board_info['bb_start_time'] = date("Y-m-d", $bill_board_info['bb_start_time']);
            $bill_board_info['bb_end_time'] = date("Y-m-d", $bill_board_info['bb_end_time']);
            $returnData['code'] = 1;
            $returnData['msg'] = "获取设备信息成功";
            $returnData['data'] = $bill_board_info;
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "获取设备信息失败";
            $returnData['data'] = "";
        }
        exit(json_encode($returnData));
    }

    /**
     * @param $device_code
     * @param $type
     * @return bool、object
     * 判断设备是否过期
     * 过期return false,没过期返回return $bill_board_info
     */
    public function isExpired($device_code, $type)
    {
        $bill_board_model = D("bill_board");
        $where['bill_board_code'] = $device_code;
        $bill_board_info = $bill_board_model->where($where)->find();

        $now_time = time();
        $bill_board_ending_timestamp = $bill_board_info['bb_end_time'];

        if ($bill_board_ending_timestamp < $now_time) {
            return false;
        } else {
            $data[$type] = 0;
            $bill_board_model->where($where)->save($data);
            return $bill_board_info;
        }
    }

    /**
     * 获取电子餐牌开关机定时信息
     * 方式：get,post 参数：device_code
     */
    public function getBillBoardTimerInfo()
    {
        $device_code = I("device_code");
        $bill_board_info = $this->isExpired($device_code, 'open_time_change');
        $bill_board_timer_model = D("bill_board_timer");
        $bbt_where['bill_board_id'] = $bill_board_info['bill_board_id'];
        $bill_board_timer_info = $bill_board_timer_model->where($bbt_where)->select();

        if ($bill_board_info) {
            $returnData['code'] = 1;
            $returnData['open_time_change'] = $bill_board_info['open_time_change'];
            $returnData['msg'] = "获取电子餐牌定时信息成功";
            $returnData['data'] = $bill_board_timer_info;
        } elseif ($bill_board_info == false) {
            $returnData['code'] = 0;
            $returnData['img_group_change'] = 0;
            $returnData['msg'] = "设备过期";
            $returnData['data'] = "";
        } else {
            $returnData['code'] = 0;
            $returnData['img_group_change'] = 0;
            $returnData['msg'] = "获取电子餐牌定时信息失败";
            $returnData['data'] = "";
        }
        exit(json_encode($returnData));
    }

    /**
     * 获取电子餐牌的轮播图片组
     * 方式：get,post 参数：device_code
     */
    public function getBillBoardImgGroupInfo()
    {
        $device_code = I("device_code");

        $bill_board_info = $this->isExpired($device_code, 'img_group_change');

        $bb_img_group_model = D("bb_img_group");
        $big_where['bill_board_id'] = $bill_board_info['bill_board_id'];
        $bb_img_group_info = $bb_img_group_model->where($big_where)->order("sort")->select();

        $bill_board_img_model = D("bill_board_img");
        foreach ($bb_img_group_info as $key => $val) {
            $bb_img_group_info[$key]['starting_date'] = date("Y-m-d", strtotime($val['starting_date']));
            $bb_img_group_info[$key]['ending_date'] = date("Y-m-d", strtotime($val['ending_date']));
            $bbi_where = array();
            $bbi_where['bb_group_id'] = $val['bb_group_id'];
            $imgs = $bill_board_img_model->where($bbi_where)->select();
            $bb_img_group_info[$key]['images'] = $imgs;
        }

        $returnData = array();
        if (empty($bill_board_info) || empty($bb_img_group_info)) {
            $returnData['code'] = 0;
            $returnData['img_group_change'] = 0;
            $returnData['msg'] = "获取电子餐牌定时信息失败";
            $returnData['data'] = "";
        } elseif ($bill_board_info == false) {
            $returnData['code'] = 0;
            $returnData['img_group_change'] = 0;
            $returnData['msg'] = "设备过期";
            $returnData['data'] = "";
        } else {
            $returnData['code'] = 1;
            $returnData['img_group_change'] = $bill_board_info['img_group_change'];
            $returnData['msg'] = "获取电子餐牌定时信息成功";
            $returnData['data'] = $bb_img_group_info;
        }
        exit(json_encode($returnData));
    }

    /**
     * 电子餐牌版本
     */
    public function getBillBoardVersion()
    {
        $data['version'] = 2;
        exit(json_encode($data));
    }

    /**
     * 获取叫号类设备预期绑定的区
     * @param $equipment_type
     */
    public function getEquipmentList($equipment_type)
    {
        $equipmentService = new \Admin\Service\EquipmentService();
        $restaurant_id = session("restaurant_id");
        $rel = $equipmentService->getEquipmentInfo($equipment_type, $restaurant_id);

        if (!empty($rel)) {
            $returnData['code'] = 1;
            $returnData['msg'] = "获取成功";
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
        $returnData['code'] = 0;
        $returnData['msg'] = "获取失败";
        $returnData['data'] = $rel;
        exit(json_encode($returnData));
    }

    /**
     * 获取店铺分区
     */
    public function getDistrictList()
    {
        $districtService = new \Admin\Service\DistrictService();
        $restaurant_id = session("restaurant_id");
        $rel = $districtService->getDistrictList($restaurant_id);
        if (!empty($rel)) {
            $returnData['code'] = 1;
            $returnData['msg'] = "获取成功";
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
        $returnData['code'] = 0;
        $returnData['msg'] = "获取失败";
        $returnData['data'] = $rel;
        exit(json_encode($returnData));
    }

    /**
     * 更改叫号屏对应的分区
     */
    public function changeYellEquipmentDistrict()
    {
        $district_model = D("restaurant_district");
        $yell_equipment_id = I("post.yell_equipment_id");
        $w_data['yell_equipment_id'] = $yell_equipment_id;
        $old_data['yell_equipment_id'] = 0;
        $district_model->where($w_data)->save($old_data);
        $save_data = I("post.");
        $rel = $district_model->save($save_data);
        if ($rel !== false) {
            $this->getDistrictList();
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "获取失败";
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     *更改核销屏对应的叫号屏
     */
    public function changeYellCancelRelation()
    {
        $yell_cancel_model = D("yell_cancel");
        $cancel_equipment_id = I("cancel_equipment_id");
        $yell_equipment_id = I("yell_equipment_id");
        $w_data['cancel_equipment_id'] = $cancel_equipment_id;
        $old_data['yell_equipment_id'] = $yell_equipment_id;
        if ($yell_cancel_model->where($w_data)->find()) {
            $rel = $yell_cancel_model->where($w_data)->save($old_data);
        } else {
            $old_data['cancel_equipment_id'] = $cancel_equipment_id;
            $old_data['cancel_mark'] = $this->generate_password(8);
            $rel = $yell_cancel_model->add($old_data);
        }


        if ($rel !== false) {
            $this->getEquipmentList("yell");
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "获取失败";
            $returnData['data'] = $rel;
            exit(json_encode($returnData));
        }
    }

    /**
     * 添加店铺分区
     */
    public function addDistrict()
    {
        $district_model = D("restaurant_district");
        $restaurant_id = session("restaurant_id");
        $district_name = I("post.district_name");
        $data['restaurant_id'] = $restaurant_id;
        $data['district_name'] = $district_name;
        $data['district_mark'] = $restaurant_id . $this->generate_password();
        $rel = $district_model->add($data);
        $returnData = [];
        if ($rel !== false) {
            $this->getDistrictList();
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "添加失败";
            $returnData['data'] = "";
        }
        exit(json_decode($returnData));
    }

    /**
     * 删除店铺分区
     */
    public function delDistrict()
    {
        $id = I("get.district_id");
        $district_model = D("restaurant_district");

        //判断分区能不能被删除
//        $where['district_id'] = $id;
//
//        $district_info = $district_model->where($where)->find();
//        if($district_info['yell_equipment_id'] != 0){
//            $returnData['code'] = 0;
//            $returnData['msg'] = "删除失败";
//            $returnData['data'] = "";
//            exit(json_encode($returnData));
//        }

        $district_model->startTrans();
        $rel = $district_model->delete($id);
        $returnData = [];
        if ($rel !== false) {
            $food_model = D("food");
            $f_where['district_id'] = $id;
            $f_data['district_id'] = 0;
            $f_rel = $food_model->where($f_where)->save($f_data);
            if ($f_rel === false) {
                $district_model->rollback();
                $returnData['code'] = 0;
                $returnData['msg'] = "删除失败";
                $returnData['data'] = "";
                exit(json_encode($returnData));
            }
            $district_model->commit();
            $this->getDistrictList();
        } else {
            $returnData['code'] = 0;
            $returnData['msg'] = "删除失败";
            $returnData['data'] = "";
        }
        exit(json_encode($returnData));
    }

    /**
     * 随机生成6为字符串
     * @param int $length
     * @return string
     */
    function generate_password( $length = 6 ) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ( $i = 0; $i < $length; $i++ ) {
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }

      //美团回调测试
    public function callback()
    {
        // 判断是否已经生成
        $restaurant_id = urldecode(I("post.ePoiId"));
        $add['app_poi_code'] = $restaurant_id; // 其实两者都是店铺ID
        $add['restaurant_id'] = $restaurant_id;

        $appAuthToken = I('post.appAuthToken');
        // 判断数据库有没有这条记录，没有才添加，有则不做处理
        $if = D('meituan')->where($add)->find();
        if(!$if){
            $add['businessId'] = I('post.businessId');
            $add['appAuthToken'] = $appAuthToken;

            // 绑定店铺名
            $now = time();
            $url = 'http://api.open.cater.meituan.com/waimai/poi/queryPoiInfo';
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                "timestamp"=>$now,
                'ePoiIds'=> $restaurant_id
            );
            // 聚宝盆get请求
            $return = jubaopen_http_get($url,$system_param);
            $array = json_decode($return,true);
            $restaurant_name = $array['data'][0]['name'];   // 美团商家店铺名
            $add['restaurant_name'] = $restaurant_name;


            $res = D("meituan")->add($add);
            if ($res) {
                $arr = [];
                $arr['data'] = "success";
                echo json_encode($arr);
            }
        }else{
            $save['businessId'] = I('post.businessId');
            $save['appAuthToken'] = $appAuthToken;

            // 绑定店铺名
            $now = time();
            $url = 'http://api.open.cater.meituan.com/waimai/poi/queryPoiInfo';
            $system_param = array(
                'appAuthToken'=>$appAuthToken,
                'charset'=>'UTF-8',
                "timestamp"=>$now,
                'ePoiIds'=> $restaurant_id
            );
            // 聚宝盆get请求
            $return = jubaopen_http_get($url,$system_param);
            $array = json_decode($return,true);
            $restaurant_name = $array['data'][0]['name'];   // 美团商家店铺名
            $save['restaurant_name'] = $restaurant_name;


            $res = D('meituan')->where($add)->save($save);
            if ($res !== false) {
                $arr = [];
                $arr['data'] = "success";
                echo json_encode($arr);
            }
        }
    }
}