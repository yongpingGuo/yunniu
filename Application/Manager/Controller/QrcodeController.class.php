<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/29
 * Time: 9:36
 */

namespace Manager\Controller;
use Think\Controller;

class QrcodeController extends Controller
{
    public function qrcode(){
        $device_code = I("device_code");
        //生成二维码
        Vendor('phpqrcode.phpqrcode');
        $url = "http://192.168.31.238/index.php/manager/activate/activate/device_code/".$device_code;
        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(4);//生成图片大小

        //生成二维码图片
        //echo $_SERVER['REQUEST_URI'];
        $object = new \QRcode();
        $date = date("Y-m-d/",time());
        $date2 = date("His",time());
        $path = "./Application/Manager/Uploads/qrcode/".$date;
        $path2 = "/Application/Manager/Uploads/qrcode/".$date;
        if(!is_readable($path)){
            is_file($path) or mkdir($path,0700);
        }
        $img_path = $path.$date2.".png";
        $img_path2 = $path2.$date2.".png";
        $object->png($url,$img_path, $errorCorrectionLevel, $matrixPointSize, 2);
        $this->assign("imgPath",$img_path2);
        $this->display();
    }
}