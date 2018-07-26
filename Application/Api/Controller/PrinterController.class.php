<?php
namespace Api\Controller;
class PrinterController extends BaseController
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

    public function getPrinterByDeviceCode(){
        $device_code = I("device_code");
        $this->isLogin($device_code);
        if($this->is_security){
            $restaurant_id = session("restaurant_id");
            $print_model = D("printer");
            $p_where['restaurant_id'] = $restaurant_id;
            $rel = $print_model->where($p_where)->select();
            if($rel){
                $returnData['code'] = 1;
                $returnData['msg'] = "获取打印机信息成功";
                $returnData['data'] = $rel;
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "无打印机设备";
                $returnData['data'] = "";
                exit(json_encode($returnData));
            }
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "该设备已过期，没有权限拿数据";
            $returnData['data'] = "";
            exit(json_encode($returnData));
        }
    }
}