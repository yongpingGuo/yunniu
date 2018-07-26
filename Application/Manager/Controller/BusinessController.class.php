<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/24
 * Time: 10:48
 */
namespace Manager\Controller;
use Think\Controller;
use Think\Xcrypt;

class BusinessController extends Controller
{
    private $key = '1234567812345678';

    function index(){
        $businessModel = D("business");
        $business_list = $businessModel->select();
        $m = new Xcrypt($this->key,'cbc');
        foreach($business_list as $key => $val){
            $business_list[$key]['business_password'] = $m->decrypt($val['business_password']);
        }
        unset($m);
        $this->assign("business_list",$business_list);
        $this->display();
    }

    function dealBusiness(){

        //获取提交过来的数据
        $businessModel = D('business');

        $type = I("post.type");
        $result = "";
        $data = $businessModel->create();

        //对密码进行加密
        $m = new Xcrypt($this->key,'cbc');
        $data['business_password'] = $m->encrypt($data['business_password']);

        if($type == "add"){
            $result = $businessModel->add($data);
        }elseif($type == "edit"){
            $result = $businessModel->save($data);
        }
        unset($m);
        unset($businessModel);

        if($result !== false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            exit(json_encode($msg));
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "操作失败";
            exit(json_encode($msg));
        }
    }

    public function delBusiness(){
        $business_id = I("get.business_id");
        $businessModel = D("business");

        $result = $businessModel->where("business_id = $business_id")->delete();
        //暂时删除记录，后续可能会处理其关联数据

        if($result !== false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            exit(json_encode($msg));
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "操作失败";
            exit(json_encode($msg));
        }
    }
}