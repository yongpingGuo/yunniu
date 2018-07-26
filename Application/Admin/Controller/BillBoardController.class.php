<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/8
 * Time: 9:29
 */

namespace Admin\Controller;
use Think\Controller;
use Think\Upload;

class BillBoardController extends Controller
{
    protected $week = array(
        0 => array(
            "label" => "星期一",
            "value" => "0"
        ),
        1 => array(
            "label" => "星期二",
            "value" => "1"
        ),
        2 => array(
            "label" => "星期三",
            "value" => "2"
        ),
        3 => array(
            "label" => "星期四",
            "value" => "3"
        ),
        4 => array(
            "label" => "星期五",
            "value" => "4"
        ),
        5 => array(
            "label" => "星期六",
            "value" => "5"
        ),
        6 => array(
            "label" => "星期日",
            "value" => "6"
        ),
    );

    public function __construct(){
        Controller::__construct();
        $admin_id = session("re_admin_id");
        if(!$admin_id){
            redirect("Index/login");
        }
        $restaurant_manager_model = D('restaurant_manager');
        $restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
        session('restaurant_id',$restaurant_id);
    }

    /**
     * 电子广告牌后台管理页面
     * 首先获取当前店铺已激活且没被禁用的电子餐牌设备
     * 然后以上设备对应的广告图片
     */
    public function index(){
        $this->display();
    }

    /**
     * 获取电子餐牌设备
     */
    public function bill_list(){
        $bill_board_model = D("bill_board");
        $data['restaurant_id'] = session("restaurant_id");
        $data['bill_board_status'] = 1;
        $data['is_active'] = 1;
        $bill_list = $bill_board_model->where($data)->select();
        exit(json_encode($bill_list));
    }

    /**
     * 获取电子广告牌的信息
     */
    public function getBillBoardInfo(){
        $id = I("get.id");
        $bill_board_model = D('bill_board');
        $where['bill_board_id'] = $id;
        $bill_board_info = $bill_board_model->where($where)->find();

        //获取电子餐牌的开机定时
        $bill_board_timer = D("bill_board_timer");
        $bill_board_timers = $bill_board_timer->where($where)->select();
        foreach($bill_board_timers as $key => $val){
            $oc_week = [false,false,false,false,false,false,false];
            $all = 0;
            if($val['week'] != ""){
                $week = explode("-",$val['week']);
                $all = count($week) == 7 ? true : false;
                foreach($week as $wv){
                    $oc_week[$wv] = true;
                }
            }
            $bill_board_timers[$key]['oc_week'] = $oc_week;
            $bill_board_timers[$key]['all'] = $all;
            $bill_board_timers[$key]['is_use'] = $val['is_use']== 0 ? false : true;
        }
        $bill_board_info['timers'] = $bill_board_timers;

        //获取电子餐牌的广告分组
        $bb_img_group_model = D("bb_img_group");
        $bb_img_group_list = $bb_img_group_model->where($where)->select();
        $bb_img_model = D("bill_board_img");

        $week = $this->week;
        foreach($bb_img_group_list as $big_key => $big_val){
            $bbi_where['bb_group_id'] = $big_val['bb_group_id'];
            $bg_img_list = $bb_img_model->where($bbi_where)->select();
            $bb_img_group_list[$big_key]["bb_group_imgs"] = $bg_img_list;
            $week_val = explode("-",$big_val['week']);
            foreach($week_val as $w_key => $w_val){
                $bb_img_group_list[$big_key]["value"][] = $w_val;
            }
            $bb_img_group_list[$big_key]["week"] = $week;
        }
        $bill_board_info['img_group'] = $bb_img_group_list;
        exit(json_encode($bill_board_info));
    }

    /**
     * 删除billBoard
     */
    public function deleteBill(){
        $bill_board_id = I("id");
        $bill_board_model = D("bill_board");
        $bill_board_model->startTrans();
        //删除开关机时间
        $bt_where['bill_board_id'] = $bill_board_id;
        $bill_board_timer_model = D("bill_board_timer");
        $bbt_del_rel = $bill_board_timer_model->where($bt_where)->delete();

        //删除图片组
        $bb_img_group_model = D("bb_img_group");
        $bb_imgs = $bb_img_group_model->where($bt_where)->select();
        $bill_board_img_model = D("bill_board_img");
        $bbi_del_rel = true;
        foreach($bb_imgs as $key => $val){
            $bbi_where['bb_group_id'] = $val['bb_group_id'];
            $bbi_del_rel = $bill_board_img_model->where($bbi_where)->delete();
        }
        $big_del_rel =  $bb_img_group_model->where($bt_where)->delete();
        $bb_del_rel = $bill_board_model->delete($bill_board_id);

        $returnData = array();
        if($bbt_del_rel !==false && $bbi_del_rel !==false && $big_del_rel!==false && $bb_del_rel!==false){
            $bill_board_model->commit();
            $returnData['code'] = 1;
            $returnData['msg'] = "删除成功";
        }else{
            $bill_board_model->rollback();
            $returnData['code'] = 0;
            $returnData['msg'] = "删除失败";
        }

        exit(json_encode($returnData));
    }

    /**
     * 记录BillBoard数据变化
     */
    public function recordChange($bill_board_id,$type){
        $data['bill_board_id'] = $bill_board_id;
        $data[$type] = 1;
        $bill_board_model = D("bill_board");
        $bill_board_model->save($data);
    }

    public function saveBillBoardTimer(){
        $bill_board_timers = D("bill_board_timer");
        $bill_board_timers->startTrans();
        $billBoardTimers = I("post.billBoardTimers");
        $this->recordChange($billBoardTimers[0]['bill_board_id'],'open_time_change');
        $save_billBoardTimers = [];
        $add_billBoardTimers = [];
        $save_rel = true;
        foreach($billBoardTimers as $key => $val){
            $data = [];
            $data['bill_board_id'] = $val['bill_board_id'];
            $data['starting_time'] = $val['starting_time'];
            $data['ending_time'] = $val['ending_time'];
            $data['is_use'] = $val['is_use'] == "true" ? 1:0;
            $data['week'] = "";
            foreach($val['oc_week'] as $ock => $ocv){
                if($ocv == "true"){
                    $data['week'] .= "$ock-";
                }
            }
            if($data['week']){
                $data['week'] = substr($data['week'],0,-1);
            }
            if($val['bill_timer_id'] == ""){
                $add_billBoardTimers[] = $data;
            }else{
                $data['bill_timer_id'] = $val['bill_timer_id'];
                $save_billBoardTimers[] = $data;
                $save_rel = $bill_board_timers->save($data);
                if($save_rel === false){
                    $bill_board_timers->rollback();
                }
            }
        }
        $add_rel = true;
        if(!empty($add_billBoardTimers)){
            $add_rel = $bill_board_timers->addAll($add_billBoardTimers);
        }
        if($add_rel && $save_rel!==false){
            $bill_board_timers->commit();
            $returnData['code'] = 1;
            $returnData['msg'] = "保存成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "保存失败";
            exit(json_encode($returnData));
        }
    }

    /**
     * 删除billboard的开机定时
     */
    public function deleteBillBoardTimer(){
        $bill_timer_id = I("get.bill_timer_id");
        $bill_board_timers = D("bill_board_timer");
        $where['bill_timer_id'] = $bill_timer_id;
        $bill_board_timer = $bill_board_timers->where($where)->find();
//        $this->recordChange($bill_board_timer['bill_board_id'],'open_time_change');
        $bill_board_timers->delete($bill_timer_id);

        if($bill_board_timers !== false){
            $returnData['code'] = 1;
            $returnData['msg'] = "删除成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "删除失败";
            exit(json_encode($returnData));
        }
    }

    /**
     *修改billboard的名称
     */
    public function editBillBoardName(){
        $bill_board_id = I("post.bill_board_id");
        $bill_board_name = I("post.bill_board_name");

        $bill_board_model = D("bill_board");

        $data['bill_board_id'] = $bill_board_id;
        $data['bill_board_name'] = $bill_board_name;

        $rel = $bill_board_model->save($data);
        if($rel !== false){
            $returnData['code'] = 1;
            $returnData['msg'] = "保存成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "保存失败";
            exit(json_encode($returnData));
        }
    }

    /**
     * 保存billboard的图片组
     */
    public function saveBillBoardImgGroup(){
        $bb_img_groups = I("post.bb_img_groups");
        // var_dump($bb_img_groups);
        $bb_img_group_model = D("bb_img_group");
        $this->recordChange($bb_img_groups[0]['bill_board_id'],'img_group_change');
        $bb_img_group_model->startTrans();
        $add_bb_img_groups = array();
        $save_rel = true;
        foreach($bb_img_groups as $key => $val){
            $data = array();
            $starting_date = mb_substr($val['starting_date'],0,33);
            $ending_date = mb_substr($val['ending_date'],0,33);
            $data['starting_time'] = $val['starting_time'];
            $data['ending_time'] = $val['ending_time'];
            $data['starting_date'] = date('Y-m-d',strtotime($starting_date));
            $data['ending_date'] = date('Y-m-d',strtotime($ending_date));

            $data['carousel_time'] = $val['carousel_time'];
            $data['sort'] = $val['sort'];
            $data['bill_board_id'] = $val['bill_board_id'];
            $data['week'] = "";
            foreach($val['value'] as $ock => $ocv){
                if($ocv != ""){
                    $data['week'] .= "$ocv-";
                }
            }
            if($data['week']){
                $data['week'] = substr($data['week'],0,-1);
            }

            if($val['bb_group_id']){
                $data['bb_group_id'] = $val['bb_group_id'];
                $save_rel = $bb_img_group_model->save($data);
                if($save_rel === false){
                    $bb_img_group_model->rollback();
                }
            }else{
                $add_bb_img_groups[] = $data;
            }
        }

        $add_rel = true;
        if(!empty($add_billBoardTimers)){
            $add_rel = $bb_img_group_model->addAll($add_bb_img_groups);
        }
        if($add_rel && $save_rel!==false){
            $bb_img_group_model->commit();
            $returnData['code'] = 1;
            $returnData['msg'] = "保存成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "保存失败";
            exit(json_encode($returnData));
        }
    }

    /**
     * 增加图片组
     */
    public function addBillBoardImgGroup(){
        $bill_board_id = I("post.bill_board_id");
//        $this->recordChange($bill_board_id,'img_group_change');
        $bb_img_group_model = D("bb_img_group");
        $where["bill_board_id"] = $bill_board_id;
        $last_sort = $bb_img_group_model->where($where)->max("sort");
        $data['bill_board_id'] = $bill_board_id;
        $data['sort'] = $last_sort+1;
        $add_rel = $bb_img_group_model->add($data);

        if($add_rel !== false){
            $week = $this->week;
            $i_where['bb_group_id'] = $add_rel;
            $img_group = $bb_img_group_model->where($i_where)->find();
            $img_group['week'] = $week;
            $img_group['bb_group_imgs'] = [];
            $img_group['value'] = ["0","1","2","3","4","5","6"];

            $returnData['code'] = 1;
            $returnData['msg'] = "增加成功";
            $returnData['data'] = $img_group;
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "增加失败";
            $returnData['data'] = "";
            exit(json_encode($returnData));
        }
    }

    /**
     * 删除图片分组
     */
    public function deleteImgGroup(){
        $bb_group_id = I("bb_group_id");
        $bb_img_group_model = D("bb_img_group");
//        $bb_img_group = $bb_img_group_model->where("bb_group_id = $bb_group_id")->find();
//        $this->recordChange($bb_img_group['bill_board_id'],'img_group_change');
        $bb_img_group_model->startTrans();
        $del_rel = $bb_img_group_model->delete($bb_group_id);
        if($del_rel !== false){
            $where['bb_group_id'] = $bb_group_id;
            $bill_board_img_model = D("bill_board_img");
            $imgs_url = $bill_board_img_model->where($where)->field("img_url")->select();
            foreach($imgs_url as $val){
                unlink("./".$val["img_url"]);
            }
            $del_img_rel = $bill_board_img_model->where($where)->delete();
            if($del_img_rel !== false){
                $bb_img_group_model->commit();
                $returnData['code'] = 1;
                $returnData['msg'] = "删除成功";
                exit(json_encode($returnData));
            }
        }else{
            $bb_img_group_model->rollback();
            $returnData['code'] = 0;
            $returnData['msg'] = "删除失败";
            exit(json_encode($returnData));
        }
    }

    /**
     * 上传图片
     */
    public function uploadImg(){
        $bb_group_id = I("post.bb_group_id");
        $bb_img_group_model = D("bb_img_group");
//        $bb_img_group = $bb_img_group_model->where("bb_group_id = $bb_group_id")->find();
//        $this->recordChange($bb_img_group['bill_board_id'],'img_group_change');
        //调用tp工具类实现文件上传
        $upload = new Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Public/images/bill_img/'; // 设置附件上传根目录
        // 上传单个文件
        $info = $upload->uploadOne($_FILES['file']);
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{
            // 上传成功 获取上传文件信息
            $bill_board_img_model = D("bill_board_img");
            $img_url = "/Public/images/bill_img/".$info['savepath'].$info['savename'];
            //添加
            $data['img_url'] = $img_url;
            $data['bb_group_id'] = $bb_group_id;
            $bim_id = $bill_board_img_model->add($data);

            if($bim_id !== false){
                $bill_board_img = $bill_board_img_model->where("id = $bim_id")->find();
                $returnData['code'] = 1;
                $returnData['msg'] = "添加成功";
                $returnData['data'] = $bill_board_img;
                exit(json_encode($returnData));
            }else{
                $returnData['code'] = 0;
                $returnData['msg'] = "添加失败";
                $returnData['data'] = "";
                exit(json_encode($returnData));
            }
        }
    }

    public function deleteImg(){
        $id = I("id");
        $bill_board_img_model = D("bill_board_img");
        $where['id'] = $id;
        $bill_board_img_info = $bill_board_img_model->where($where)->find();
        $del_rel = $bill_board_img_model->where($where)->delete();
        if($del_rel !== false){
            $img_url = $bill_board_img_info['img_url'];
            unlink("./".$img_url);
            $bb_group_id = $bill_board_img_info['bb_group_id'];
            $bb_img_group_model = D("bb_img_group");
//            $bb_img_group = $bb_img_group_model->where("bb_group_id = $bb_group_id")->find();
//            $this->recordChange($bb_img_group['bill_board_id'],'img_group_change');
            $returnData['code'] = 1;
            $returnData['msg'] = "删除成功";
            exit(json_encode($returnData));
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "删除失败";
            exit(json_encode($returnData));
        }
    }
}