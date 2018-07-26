<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/21
 * Time: 15:38
 */

namespace Business\Controller;
use Think\Controller;


class StoreController extends Controller
{
    public function __construct(){
        Controller::__construct();
        if(!session("business_id")){
            $this->redirect("login");
        }
    }

    public function index(){
        $p = $_GET['page'];
        if(!$p){
            $view = "index";
            $p = 1;
        }else{
            $view = "ajaxIndex";
        }
        $storeModel = D("restaurant");
        $s_condition['business_id'] = session('business_id');
        $s_condition['status'] = 1;
        $page_num = 3;

        $resList = $storeModel->where($s_condition)->page($p,$page_num)->order("restaurant_id desc")->select();
        foreach($resList as $key => $val){
            $m_condition['restaurant_id'] = $val['restaurant_id'];
            $restaurantManagerModel = D("restaurant_manager");
            $manager_name = $restaurantManagerModel->where($m_condition)->field("manager_name")->find()['manager_name'];

            if(!$manager_name){
                $manager_name = '暂无';
            }
            $resList[$key]['manager_name'] = $manager_name;

        }
        $this->assign("resList",$resList);

        $count = $resList = $storeModel->where($s_condition)->count();
        $page = new \Think\PageAjax($count,$page_num);

        $pp = $page->show();
        $this->assign("page",$pp);

        //获取改代理商下的所有店铺管理员
        $business_id = session('business_id');
        $condition['business_id'] = $business_id;
        $restaurantManagerModel = D('restaurant_manager');
        $managerList = $restaurantManagerModel->where($condition)->select();
//        dump($managerList);
        $this->assign('managerList',$managerList);

        $this->display($view);
    }

    public function manager(){
        $rst_manager_model = D('restaurant_manager');
        $condition['business_id'] = session('business_id');
        $managerList = $rst_manager_model->where($condition)->select();
        $this->assign('managerList',$managerList);
        $this->display();
    }

    //添加店铺
    public function addStore(){
        $r_condition['restaurant_name'] = I("post.restaurant_name");
        $r_condition['restaurant_url'] = "http://192.168.31.101/index.php/home/index/index/restaurant_id/";
        $r_condition['business_id'] = session("business_id");

        $restaurantModel = D("restaurant");
        $restaurantModel->startTrans();
        $rel = $restaurantModel->add($r_condition);
        if($rel){
            //补全url地址
            $rt_data['restaurant_url'] = "http://192.168.31.101/index.php/home/index/index/restaurant_id/".$rel;
            $rt_data['restaurant_id'] = $rel;
            $rel2 = $restaurantModel->save($rt_data);
            if($rel2 === false){
                $restaurantModel->rollback();
            }

            //修改店铺管理员所属店铺
            $manager_id = I('post.managerName');
            $condition['id'] = $manager_id;
            $data['restaurant_id'] = $rel;
            $rst_manager_model = D("restaurant_manager");
            $rel3 = $rst_manager_model->where($condition)->save($data);
            if($rel3 === false){
                $restaurantModel->rollback();
            }

            //为店铺添加流程
            $rst_process_model = D("restaurant_process");
            $rp_data['restaurant_id'] = $rel;
            $rp_data['process_status'] = 1;
            for($i=1;$i<=5;$i++){
                $rp_data['process_id'] = $i;
                $rel4 = $rst_process_model->add($rp_data);
                if($rel4 === false){
                    $restaurantModel->rollback();
                }
            }

            //为店铺添加打印小票控制记录
            $rst_bill_model = D('restaurant_bill');
            $rb_data['restaurant_name'] = 1;
            $rb_data['qrcode'] = 1;
            $rb_data['address'] = 1;
            $rb_data['restaurant_phone'] = 1;
            $rb_data['take_out_phone'] = 1;
            $rb_data['subscription'] = 1;
            $rb_data['restaurant_id'] = $rel;
            $rel5 = $rst_bill_model->add($rb_data);
            if($rel5 === false){
                $restaurantModel->rollback();
            }

            //为店铺添加默认的横屏、
            $advertisementModel = D('advertisement');
            $ad_data['advertisement_type'] = 0;
            $ad_data['advertisement_image_url'] = "./Application/Admin/Uploads/upadvert_heng/582a85b6c4652.png";
            $ad_data['restaurant_id'] = $rel;
            $rel6 = $advertisementModel->add($ad_data);
            if($rel6 === false){
                $restaurantModel->rollback();
            }

            //默认竖屏广告
            $ad_data['advertisement_type'] = 1;
            $ad_data['advertisement_image_url'] = "./Application/Admin/Uploads/upadvert_heng/582a85b6c4652.png";
            $ad_data['restaurant_id'] = $rel;
            $rel7 = $advertisementModel->add($ad_data);
            if($rel7 === false){
                $restaurantModel->rollback();
            }

            //添加默认横屏点餐页
            $rst_page_model = D('restaurant_page');
            $rsp_data['order_page_id'] = 1;
            $rsp_data['replace_id'] = 1;
            $rsp_data['restaurant_id'] = $rel;
            $rel8 = $rst_page_model->add($rsp_data);
            if($rel8 === false){
                $restaurantModel->rollback();
            }

            //添加默认竖屏点餐页
            $rsp_data['order_page_id'] = 6;
            $rel9 = $rst_page_model->add($rsp_data);
            if($rel9 === false){
                $restaurantModel->rollback();
            }

            //添加手机端模板
            $rsp_data['order_page_id'] = 9;
            $rel10 = $rst_page_model->add($rsp_data);
            if($rel10 === false){
                $restaurantModel->rollback();
            }

            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $restaurantModel->commit();
            exit(json_encode($msg));
        } else{
            $msg['code'] = 0;
            $msg['msg'] = "操作失败";
            exit(json_encode($msg));
        }

    }

    public function editInfo(){
        $restaurant_id = I('post.restaurant_id');

    }

    public function deleteInfo(){
        //店铺删除（假删除，修改其状态为0）
        $restaurant_id = I('post.restaurant_id');
        $condition['restaurant_id'] = $restaurant_id;
        $restaurantModel = D('restaurant');
        $data['status'] = 0;
        $rel = $restaurantModel->where($condition)->save($data);

        if($rel !== false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $restaurantModel->commit();
            exit(json_encode($msg));
        }
//        $restaurantModel = D('restaurant');
//        $restaurantModel->startTrans();
//
//
//        $condition['restaurant_id'] = $restaurant_id;
//
//
//        //删除店铺信息
//        $rel = $restaurantModel->delete();
//        if($rel === false){
//            $restaurantModel->rollback();
//        }
//
//        //删除店铺广告
//        $advertisementModel = D('advertisement');
//        $rel2 = $advertisementModel->where($condition)->delete();
//        if($rel2 === false){
//            $restaurantModel->rollback();
//        }
//
//        //删除店铺点餐模板
//        $rst_page_model = D('restaurant_page');
//        $rel3 = $rst_page_model->where($condition)->delete();
//        if($rel3 === false){
//            $restaurantModel->rollback();
//        }
//
//        //删除默认点餐流程
//        $rst_process_model = D('restaurant_process');
//        $rel4 = $rst_process_model->where($condition)->delete();
//        if($rel4 === false){
//            $restaurantModel->rollback();
//        }
//
//        //删除餐厅小票控制
//        $rst_bill_model = D('restaurant_bill');
//        $rel5 = $rst_bill_model->where($condition)->delete();
//        if($rel5 === false){
//            $restaurantModel->rollback();
//        }
//
//        $restaurantModel->commit();
//        $msg['code'] = 1;
//        $msg['msg'] = "操作成功";
//        $restaurantModel->commit();
//        exit(json_encode($msg));
    }
}