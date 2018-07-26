<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/12
 * Time: 9:55
 */

namespace MobileAdmin\Controller;
use Think\Controller;

class RestaurantController extends Controller{
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

    public function index(){
        $restaurant_model = D("Restaurant");
        if(I("post.")){
            $restaurant_id = session('restaurant_id');
            if($restaurant_id){
                //修改
                $data = $restaurant_model->create();
                $result = $restaurant_model->where("restaurant_id = $restaurant_id")->data($data)->save();
				$restaurant_manager = D('restaurant_manager');
				$data1 = $restaurant_manager->create();
				$result1 = $restaurant_manager->where("restaurant_id = $restaurant_id")->data($data1)->save();
                if($result || $result1 !== false){
                    $msg['code'] = 1;
                    $msg['msg'] = "成功";
                    exit(json_encode($msg));
                }else{
                    $msg['code'] = 0;
                    $msg['msg'] = "失败";
                    exit(json_encode($msg));
                }
            }else{
                //添加
                $data = $restaurant_model->create();
                $result = $restaurant_model->data($data)->add();
				$restaurant_manager = D('restaurant_manager');
				$data1 = $restaurant_manager->create();
				$result1 = $restaurant_manager->data($data1)->add();
                if($result || $result1 !== false){
                    $msg['code'] = 1;
                    $msg['msg'] = "成功";
                    exit(json_encode($msg));
                }else{
                    $msg['code'] = 0;
                    $msg['msg'] = "失败";
                    exit(json_encode($msg));
                }
            }
        }else{
            //从数据库中获取数据，渲染页面
            $restaurant_id = session('restaurant_id');
            $restaurant = $restaurant_model->where("restaurant_id = $restaurant_id")->field('restaurant_id,restaurant_name,telephone1,telephone2,address,logo')->find();
            $this->assign('Restaurant',$restaurant);
			$restaurant_manager = D('restaurant_manager');
			$object = $restaurant_manager->where("restaurant_id=$restaurant_id")->find();
			$this->assign('object',$object);
            $this->display();
        }
    }

    public function changeRestaurantLogo(){
        $restaurant_id = session("restaurant_id");
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     1024*1024*6 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      './Public/images/restaurantLogo/'; // 设置附件上传根目录

        $restaurant_model = D("restaurant");
        $r_where['restaurant_id'] = $restaurant_id;
        $rel = $restaurant_model->where($r_where)->find();

        // 上传单个文件
        $info   =   $upload->uploadOne($_FILES['file']);
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{
            // 上传成功 获取上传文件信息
            $data['restaurant_id'] = $restaurant_id;
            $data['logo'] = "/Public/images/restaurantLogo/".$info['savepath'].$info['savename'];;
            $save_rel = $restaurant_model->save($data);
            if($rel && $save_rel !== false){
                if($rel['logo'] != '/Public/images/logo.png'){
                    unlink(".".$rel['logo']);
                }
                $msg['code'] = 1;
                $msg['msg'] = "成功";
                exit(json_encode($msg));
            }else{
                $msg['code'] = 0;
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
        }
    }

    public function receipt(){
        $restaurant_model = D("Restaurant");
        $restaurant_id = session('restaurant_id');
        $this->assign("restaurant_id",$restaurant_id);
        if(I("post.")){
            //修改
            $data = $restaurant_model->create();
            $result = $restaurant_model->where("restaurant_id = $restaurant_id")->data($data)->save();
            if($result !== false){
                $msg['code'] = 1;
                $msg['msg'] = "成功";
                exit(json_encode($msg));
            }else{
                $msg['code'] = 0;
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
            /*$restaurant_id = I("post.restaurant_id");
            if($restaurant_id){
                //修改
                $data = $restaurant_model->create();
                $result = $restaurant_model->where("restaurant_id = $restaurant_id")->data($data)->save();
                if($result !== false){
                    $msg['code'] = 1;
                    $msg['msg'] = "成功";
                    exit(json_encode($msg));
                }else{
                    $msg['code'] = 0;
                    $msg['msg'] = "失败";
                    exit(json_encode($msg));
                }
            }else{
                //添加
                $data = $restaurant_model->create();
                $result = $restaurant_model->data($data)->add();

                if($result !== false){
                    $msg['code'] = 1;
                    $msg['msg'] = "成功";
                    exit(json_encode($msg));
                }else{
                    $msg['code'] = 0;
                    $msg['msg'] = "失败";
                    exit(json_encode($msg));
                }
            }*/
        }else{
            //从数据库中获取数据，渲染页面
            $restaurant_bill_model = D('restaurant_bill');
            $restaurant_bill = $restaurant_bill_model->where("restaurant_id = $restaurant_id")->find();
            $this->assign("restaurant_bill",$restaurant_bill);

            $restaurant = $restaurant_model->where("restaurant_id = $restaurant_id")->find();


            //判断上logo
            $is_top_logo = $restaurant_bill['top_logo'];
            $top_logo_url = C('HOST_NAME').$restaurant['top_logo'];
            $this->assign('is_top_logo',$is_top_logo);
            $this->assign('top_logo_url',$top_logo_url);

            //判断下logo
            $is_next_logo = $restaurant_bill['next_logo'];
            $next_logo_url = C('HOST_NAME').$restaurant['next_logo'];
            $this->assign('is_next_logo',$is_next_logo);
            $this->assign('next_logo_url',$next_logo_url);

            $this->assign('restaurant',$restaurant);
            $this->display();
        }
    }

    public function changeBillStatus(){
        $condition['restaurant_id'] = session('restaurant_id');
        $name = I("post.name");
        $status = I("post.status");

        $data[$name] = $status;

        $restaurant_bill_model = D("restaurant_bill");

        $result = $restaurant_bill_model->where($condition)->data($data)->save();
        if($name == "take_num" ){
            $data["pay_prompt"] = $status;
            $result = $restaurant_bill_model->where($condition)->data($data)->save();
        }

        if($name == "pay_num" ){
            $data["pay_prompt2"] = $status;
            $result = $restaurant_bill_model->where($condition)->data($data)->save();
        }

        if($name == "qrcode" ){
            $data["forward_prompt"] = $status;
            $result = $restaurant_bill_model->where($condition)->data($data)->save();
            $data["desk"] = $status;
            $result = $restaurant_bill_model->where($condition)->data($data)->save();
        }

        if($result !== false){
            $msg['code'] = 1;
            $msg['msg'] = "成功";
            exit(json_encode($msg));
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "失败";
            exit(json_encode($msg));
        }
    }

    public function changeRestaurantBillLogo(){
        $restaurant_id = session("restaurant_id");
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      './Public/images/restaurantLogo/'; // 设置附件上传根目录

        $restaurant_model = D("restaurant");
        $r_where['restaurant_id'] = $restaurant_id;
        $rel = $restaurant_model->where($r_where)->find();

        // 上传单个文件
        $info   =   $upload->uploadOne($_FILES['file']);
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{
            // 上传成功 获取上传文件信息
            $data['restaurant_id'] = $restaurant_id;
            if($_POST['type'] == 1){//type为1表示上传了上logo
                $data['top_logo'] = "/Public/images/restaurantLogo/".$info['savepath'].$info['savename'];;
            }else{
                $data['next_logo'] = "/Public/images/restaurantLogo/".$info['savepath'].$info['savename'];;
            }
            $save_rel = $restaurant_model->save($data);
            if($rel && $save_rel !== false){
                if($_POST['type'] == 1){//上传成功删除原来的logo
                    unlink(".".$rel['top_logo']);
                }else{
                    unlink(".".$rel['next_logo']);
                }
                $msg['code'] = 1;
                $msg['msg'] = "成功";
                exit(json_encode($msg));
            }else{
                $msg['code'] = 0;
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
        }
    }

    public function getBillStatus(){
        $restaurant_id = I("post.restaurant_id");
        $restaurant_bill_model = D('restaurant_bill');
        $restaurant_bill = $restaurant_bill_model->where("restaurant_id = $restaurant_id")->field("restaurant_name,qrcode,address,restaurant_phone,take_out_phone,subscription,take_num,desk_num,forward_prompt,pay_prompt,down_prompt,pay_num,pay_prompt2,order_type")->find();
        exit(json_encode($restaurant_bill));
    }
}