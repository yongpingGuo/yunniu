<?php
namespace MobileAdmin\Controller;
use Think\Controller;
class MemberController extends Controller {
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

    // 折扣设置
    public function index(){
        $restaurant_id = session('restaurant_id');
        // 会员折扣开关
        $set = D("set");
        $set_condition['type'] = 0;
        $set_condition['restaurant_id'] = $restaurant_id;
        $vip_if_open = $set->where($set_condition)->getField('if_open');
        $this->assign("vip_if_open",$vip_if_open);
        // 店铺折扣开关
        $set_condition['type'] = 5;
        $restaurant_if_open = $set->where($set_condition)->getField('if_open');
        $this->assign("restaurant_if_open",$restaurant_if_open);


        $this->display();
   }

    // 切换会员开关
    public function change_switch_status(){
        $open_which = I('post.open_which');
        if($open_which == 1){
            // 选中会员折扣的处理
            $return = $this->vip_discount();
            if(!$return){
                $return_data['code'] = 0;
                exit(json_encode($return_data));
            }
        }elseif($open_which == 2){
            // 选中店铺折扣的处理
            $return = $this->restaurant_discount();
            if(!$return){
                $return_data['code'] = 0;
                exit(json_encode($return_data));
            }
        }elseif($open_which == 0){
            // 关闭店铺折扣
            $res = $this->operate_switch(5,0);
            if(!$res){
                $return_data['code'] = 0;
                exit(json_encode($return_data));
            }
            // 关闭店会员折扣
            $res1 = $this->operate_switch(0,0);
            if(!$res1){
                $return_data['code'] = 0;
                exit(json_encode($return_data));
            }
        }
        $return_data['code'] = 1;
        exit(json_encode($return_data));
    }

    // 选中会员折扣的操作
    public function vip_discount(){
        // 判断是否已存在
        $set = D("set");
        $restaurant_id = session("restaurant_id");
        $receive['restaurant_id'] = $restaurant_id;
        $where = array("restaurant_id"=>$restaurant_id,"type"=>0);  // type=0表示会员折扣的开关设置
        $data = $set->where($where)->find();
        if(empty($data)){
            // 新增会员折扣，并开启
            $receive['if_open'] = 1;
            $receive['type'] = 0;
            $add = $set->add($receive);
            if(!$add){
                return false;
            }
        }else{
            // 开启会员折扣
            $res = $this->operate_switch(0,1);
            if(!$res){
                return false;
            }
        }

        // 关闭店铺折扣
        $res1 = $this->operate_switch(5,0);
        if(!$res1){
            return false;
        }
        return true;
    }

    // 选中店铺折扣的操作
    public function restaurant_discount(){
        $set = D("set");
        $restaurant_id = session("restaurant_id");
        $receive['restaurant_id'] = $restaurant_id;
        $where = array("restaurant_id"=>$restaurant_id,"type"=>5);  // type=5表示店铺折扣的开关设置
        $data = $set->where($where)->find();
        if(empty($data)){
            // 新增店铺折扣，并开启
            $receive['if_open'] = 1;
            $receive['type'] = 5;
            $add = $set->add($receive);
            if(!$add){
                return false;
            }
        }else{
            // 开启店铺折扣
            $res = $this->operate_switch(5,1);
            if(!$res){
                return false;
            }
        }

        // 关闭会员折扣
        $res1 = $this->operate_switch(0,0);
        if(!$res1){
            return false;
        }
        return true;
    }

    // 操作开关公共函数
    public function operate_switch($type,$if_open)
    {
        $set = D("set");
        $where['restaurant_id'] = session("restaurant_id");
        $where['type'] = $type;
        $save['if_open'] = $if_open;
        $data = $set->where($where)->save($save);
        if($data === false){
            return false;
        }else{
            return true;
        }
    }

    // 店铺折扣
    public function discount_all(){
        // 店铺优惠折扣信息
        $discount_restaurant_info = D('restaurant_discount')->where(array('restaurant_id'=>session('restaurant_id')))->find();
        $this->assign("discount_restaurant_info",$discount_restaurant_info);
        $this->display();
    }

    // 保存店铺折扣信息
    public function keep_restaurant_discount()
    {
        /*[id] => 81
        [money] => 3.00
        [discount] => 6.0
        [reduce] => 9.00*/

        $restaurant_discount = D("restaurant_discount");
        if ('' != I("post.id")) {
            // 编辑
            if($restaurant_discount->create(I("post."))){
                if($restaurant_discount->save(I("post.")) !== false){
                    $return_data['code'] = 1;
                    $return_data['msg'] = '保存成功';
                    exit(json_encode($return_data));
                }else{
                    $return_data['code'] = 0;
                    $return_data['msg'] = '保存失败';
                    exit(json_encode($return_data));
                }
            }else{
                $return_data['code'] = 0;
                $return_data['msg'] = '保存失败';
                exit(json_encode($return_data));
            }
        }else{
            $restaurant_id = session("restaurant_id");
            // 增加
            $if_have = $restaurant_discount->where(array('restaurant_id'=>$restaurant_id))->getField('id');
            if($if_have){
                $return_data['code'] = 0;
                $return_data['msg'] = '该店铺已有对应的店铺折扣';
                exit(json_encode($return_data));
            }
            $add['money'] = I("post.money");
            $add['discount'] = I("post.discount");
            $add['reduce'] = I("post.reduce");
            $add['restaurant_id'] = $restaurant_id;

            if($restaurant_discount->create($add)){
                if($restaurant_discount->add($add)){
                    $return_data['code'] = 1;
                    $return_data['msg'] = '新增成功';
                    exit(json_encode($return_data));
                }else{
                    $return_data['code'] = 0;
                    $return_data['msg'] = '新增失败';
                    exit(json_encode($return_data));
                }
            }else{
                $return_data['code'] = 0;
                $return_data['msg'] = '新增失败';
                exit(json_encode($return_data));
            }
        }
    }

    // 删除店铺折扣
    public function deleteDisc_restaurant()
    {
        $discount = D("restaurant_discount");
        $where["id"] = I("post.id");
        // 删除折扣信息
        if($discount->where($where)->delete()){
            $return_data['code'] = 1;
            exit(json_encode($return_data));
        }else{
            $return_data['code'] = 0;
            exit(json_encode($return_data));
        }
    }

    // 会员折扣
    public function discount_vip(){
        $restaurant_id = session('restaurant_id');
        $restaurant = D("restaurant");
        $condition['restaurant_id'] = $restaurant_id;
        $business_id= $restaurant->where($condition)->getField("business_id");
        // 会员折扣信息
        $discount = D("discount");
        $condition['restaurant_id'] = $restaurant_id;
        $discount_info = $discount->where($condition)->select();
        $this->assign('discount_info', $discount_info);

        $where['business_id'] = $business_id;
        $vip_group = D("vip_group");
        $group_info = $vip_group->where($where)->select();
        $this->assign("group_info",$group_info);
        /*foreach ($discount_info as $key => $value) {
            foreach ($group_info as $k => $v) {
                if ($value['group_id'] == $v['group_id']) {
                    $discount_info[$key]['group_id'] = $v['group_name'];
                }elseif ($value['group_id'] == 0) {
                    $discount_info[$key]['group_id'] = '默认会员组';
                }
            }
        }*/

        $this->display();
    }

    // 新增会员折扣时，会员组的下拉框选择
    public function get_group(){
        $restaurant_id = session('restaurant_id');
        $restaurant = D("restaurant");
        $condition['restaurant_id'] = $restaurant_id;
        $business_id= $restaurant->where($condition)->getField("business_id");
        $where['business_id'] = $business_id;
        $vip_group = D("vip_group");
        $group_info = $vip_group->where($where)->select();
        exit(json_encode($group_info));
    }

    // 保存会员折扣
    public function keep_discount(){
        $restaurant_id = session("restaurant_id");
        $discount = D("discount");
        if ('' != I("post.id")) {
            $self_id = $discount->where(array("id"=>I("post.id")))->getField("group_id");

            $all_group_id = $discount->where(array("restaurant_id"=>session("restaurant_id")))->field("group_id")->select();
            $arr = array();
            foreach($all_group_id as $v){
                if($v['group_id'] == $self_id){
                    // 去掉当前编辑的对象本身没改变时的id
                    unset($v['group_id']);
                }else{
                    $arr[] = $v['group_id'];
                }
            }
            if(in_array(I("post.group_id"),$arr)){
                // 说明该分组已经有折扣规则使用
                $return_data['code'] = 0;
                $return_data['msg'] = "已有折扣规则在使用该分组名，请另外选择";
                exit(json_encode($return_data));
            }else{
                // 允许修改
                if($discount->create(I("post."))){
                    if($discount->save(I("post.")) !== false){
                        $return_data['code'] = 1;
                        $return_data['msg'] = "保存成功";
                        exit(json_encode($return_data));
                    }else{
                        $return_data['code'] = 0;
                        $return_data['msg'] = "保存失败，请重试";
                        exit(json_encode($return_data));
                    }
                }else{
                    $return_data['code'] = 0;
                    $return_data['msg'] = "保存失败，请重试";
                    exit(json_encode($return_data));
                }
            }
        }else{
            $condition['restaurant_id'] = $restaurant_id;
            $condition['group_id'] = I("post.group_id");
            $record = $discount->where($condition)->find();
            if($record){
                // 当前添加的会员组已经有了对应的折扣信息
                $return_data['code'] = 0;
                $return_data['msg'] = "当前添加的会员组已经有了对应的折扣信息，请勿重复添加";
                exit(json_encode($return_data));
            }else{
                $add['money'] = I("post.money");
                $add['discount'] = I("post.discount");
                $add['group_id'] = I("post.group_id");
                $add['reduce'] = I("post.reduce");
                $add['restaurant_id'] = session("restaurant_id");

                if($discount->create($add)){
                    if($return_id = $discount->add($add)){
                        $return_data['code'] = 1;
                        $return_data['id'] = $return_id;
                        $return_data['msg'] = "添加成功";
                        exit(json_encode($return_data));
                    }else{
                        $return_data['code'] = 0;
                        $return_data['msg'] = "添加失败，请重试";
                        exit(json_encode($return_data));
                    }
                }else{
                    $return_data['code'] = 0;
                    $return_data['msg'] = "添加失败，请重试";
                    exit(json_encode($return_data));
                }
            }
        }
    }

    // 删除折扣
    public function deleteDisc()
    {
        $discount = D("discount");
        $where["id"] =I("post.id");
        // 删除折扣信息
        if($discount->where($where)->delete()){
            $return_data['code'] = 1;
            $return_data['msg'] = "删除成功";
            exit(json_encode($return_data));
        }else{
            $return_data['code'] = 0;
            $return_data['msg'] = "删除失败，请重试";
            exit(json_encode($return_data));
        }
    }

    // 会员广告
    public function ad_vip(){
        $this->display();
    }

    // 会员顶部广告
    public function ad_top(){
        $adver = D('advertisement_vip');
        $condition['restaurant_id'] = session('restaurant_id');
        $condition['advertisement_type'] = 0;
        $arr = $adver->where($condition)->select();//顶部广告
        $this->assign("info",$arr);
        $this->display();
    }

    // 会员底部广告
    public function ad_bottom(){
        $adver = D('advertisement_vip');
        $condition1['restaurant_id'] = session('restaurant_id');
        $condition1['advertisement_type'] = 1;
        $arr1 = $adver->where($condition1)->select();//底部广告
        $this->assign("info1",$arr1);

        $this->display();
    }

    //上传顶部会员广告
    public function uploadimg_top(){
        $adver = D('advertisement_vip');
        $upload = new \Think\Upload();      // 实例化上传类
        $upload->maxSize   =     1024*1024*6 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      'advertisement_vip/'; // 设置附件上传目录
        $upload->autoSub = false;
        $z   =   $upload->upload();
        $picpathname = './Application/Admin/Uploads/'.$z[file]['savepath'] . $z[file]['savename'];
        $data['advertisement_image_url'] = $picpathname;
        $data['restaurant_id'] = session('restaurant_id');
        $data['advertisement_type'] = 0;
        $map['advertisement_id'] = I('post.aid');
        if($_POST['wtype'] == "default"){
            $addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
            if($addr != "./Application/Admin/Uploads/default/default_hengadv.jpg"){
                $addr = ltrim($addr,".");
                $address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
                unlink($address);
            }
            $data['advertisement_id'] = I('post.aid');
            $adver->save($data);
        }else{
            if($_POST['statu'] == ""){
                $num = $adver->add($data);
            }else{
                $addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
                if($addr != "./Application/Admin/Uploads/default/default_hengadv.jpg"){
                    $addr = ltrim($addr,".");
                    $address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
                    unlink($address);
                }
                $data['advertisement_id'] = I('post.aid');
                $data1 = $adver->save($data);
            }
        }
        $where['advertisement_type'] = 0;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign('info',$arr);
        $this->display('ajax_top');
    }

    //删除顶部会员广告
    public function deladver_top(){
        $adver = D('advertisement_vip');
        //删除服务器上的图片
        $imgaddr = $adver->where("advertisement_id=".$_POST['advertisement_id'])->field("advertisement_image_url")->find()['advertisement_image_url'];
        $imgaddr = ltrim($imgaddr,".");
        $address = dirname(dirname(dirname(dirname(__FILE__)))).$imgaddr;
        unlink($address);

        $adver->where('advertisement_id='.$_POST['advertisement_id'])->delete();
        $where['advertisement_type'] = 0;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign('info',$arr);
        $this->display('ajax_top');
    }

    //上传底部会员广告
    public function uploadimg_bottom(){
        $adver = D('advertisement_vip');
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     1024*1024*6 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      'advertisement_vip1/'; // 设置附件上传目录
        $upload->autoSub = false;
        $z   =   $upload->upload();
        $picpathname = './Application/Admin/Uploads/'.$z[file]['savepath'] . $z[file]['savename'];
        $data['advertisement_image_url'] = $picpathname;
        $data['restaurant_id'] = session('restaurant_id');
        $data['advertisement_type'] = 1;
        $map['advertisement_id'] = I('post.aid');
        if($_POST['wtype'] == "default"){
            $addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
            if($addr != "./Application/Admin/Uploads/default/default_shuadv.jpg"){
                $addr = ltrim($addr,".");
                $address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
                unlink($address);
            }
            $data['advertisement_id'] = I('post.aid');
            $adver->save($data);
        }else{
            if($_POST['statu'] == ""){
                $num = $adver->add($data);
            }else{
                $addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
                if($addr != "./Application/Admin/Uploads/default/default_shuadv.jpg"){
                    $addr = ltrim($addr,".");
                    $address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
                    unlink($address);
                }
                $data['advertisement_id'] = I('post.aid');
                $data1 = $adver->save($data);
            }
        }
        $where['advertisement_type'] = 1;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign('info1',$arr);
        $this->display('ajax_bottom');
    }

    //删除底部广告
    public function deladver_bottom(){
        $adver = D('advertisement_vip');
        //删除服务器上的图片
        $imgaddr = $adver->where("advertisement_id=".$_POST['advertisement_id'])->field("advertisement_image_url")->find()['advertisement_image_url'];
        $imgaddr = ltrim($imgaddr,".");
        $address = dirname(dirname(dirname(dirname(__FILE__)))).$imgaddr;
        unlink($address);

        $adver->where('advertisement_id='.$_POST['advertisement_id'])->delete();
        $where['advertisement_type'] = 1;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign("info1",$arr);
        $this->display('ajax_bottom');
    }

    // 积分消费
    public function consumption(){
        $this->display();
    }

    // 积分赠送
    public function consumption_integral(){
        // 积分规则
        $where['restaurant_id'] = session("restaurant_id");
        $restaurant = D("restaurant");
        $business_id = $restaurant->where($where)->getField("business_id");
        $condition['business_id'] = $business_id;

        $condition['type'] = 2;
        $all_benefit = D("all_benefit");
        $prepaid_rules = $all_benefit->where($condition)->select();
        $this->assign("prepaid_rules",$prepaid_rules);

        // 积分开关
        $set = D("set");
        $condition['type'] = 2;
        $condition['restaurant_id'] = session("restaurant_id");
        $if_open = $set->where($condition)->getField("if_open");
        $this->assign("if_open",$if_open);

        $this->display();
    }

    // 积分兑换
    public function consumption_gift(){
        # 积分物品
        // 要根据business_id和类型去获取
        $where['restaurant_id'] = session("restaurant_id");
        $restaurant = D("restaurant");
        $business_id = $restaurant->where($where)->getField("business_id");
        $g_condition['business_id'] = $business_id;
        $score_goods = D("score_goods");
        $img_rules = $score_goods->where($g_condition)->select();
        $this->assign("img_rules",$img_rules);

        // 积分物品开关
        $set = D("set");
        $goods_condition['type'] = 4;
        $goods_condition['restaurant_id'] = session("restaurant_id");
        $goods_open = $set->where($goods_condition)->getField("if_open");
        $this->assign("goods_open",$goods_open);
        $this->display();
    }

    # 积分消费
    public function point_consumptio(){
        // 积分规则
        // 要根据business_id和类型去获取
        $where['restaurant_id'] = session("restaurant_id");
        $restaurant = D("restaurant");
        $business_id = $restaurant->where($where)->getField("business_id");
        $condition['business_id'] = $business_id;

        $condition['type'] = 2;
        $all_benefit = D("all_benefit");
        $prepaid_rules = $all_benefit->where($condition)->select();
        $this->assign("prepaid_rules",$prepaid_rules);

        // 积分开关
        $set = D("set");
        $condition['type'] = 2;
        $condition['restaurant_id'] = session("restaurant_id");
        $if_open = $set->where($condition)->getField("if_open");
        $this->assign("if_open",$if_open);

        # 获取积分现金规则
        // 要根据business_id和类型去获取
        $where['restaurant_id'] = session("restaurant_id");
        $restaurant = D("restaurant");
        $business_id = $restaurant->where($where)->getField("business_id");
        $condition['business_id'] = $business_id;
        $condition['type'] = 3;
        $all_benefit = D("all_benefit");
        $point_cash_rules = $all_benefit->where($condition)->find();

        $this->assign("point_cash_rules",$point_cash_rules);
        // 去掉小数点
        $score = intval($point_cash_rules['benefit']);
        if($score == 0){
            unset($score);
        }
        $this->assign("score",$score);

        # 获取积分现金开关
        // 积分现金开关
        $set = D("set");
        $cash_condition['type'] = 3;
        $cash_condition['restaurant_id'] = session("restaurant_id");
        $cash_open = $set->where($cash_condition)->getField("if_open");
        $this->assign("cash_open",$cash_open);



        # 积分物品
        // 要根据business_id和类型去获取
        $g_condition['business_id'] = $business_id;
        $score_goods = D("score_goods");
        $img_rules = $score_goods->where($g_condition)->select();
        $this->assign("img_rules",$img_rules);

        // 积分物品开关
        $goods_condition['type'] = 4;
        $goods_condition['restaurant_id'] = session("restaurant_id");
        $goods_open = $set->where($goods_condition)->getField("if_open");
        $this->assign("goods_open",$goods_open);
        $this->display();
    }

    // 积分赠送开关切换
    public function point_set(){
        $receive = $this->set(2);
        if($receive){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    # 积分物品开关切换
    public function goods_set()
    {
        $receive = $this->set(4);
        if($receive){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    # 公共的添加设置的封装方法(但折扣的设置方法是自己独立出来一个的，因为折扣那里多了一个是否会员的判断)
    // 参数为类型  0：折扣  1：预充值  2：积分设置  3：积分现金  4：积分物品
    public function set($type)
    {
        # 接收设置信息，存入设置表
        $receive = array();
        $receive['if_open'] = I("post.if_open");    // 是否开启  1开启，0关闭

        # 设置信息存入数据表
        // 有两种做法：一、只做更新的，因为在新增店铺的时候就同时给它一条设置记录  二、做更新和做添加，根据店铺id去查询是否有此记录，没有则添加，有则更新。
        // 判断数据表中是否已经有了此记录
        $set = D("set");
        $restaurant_id = session("restaurant_id");
        $receive['restaurant_id'] = $restaurant_id;
        $where = array("restaurant_id"=>$restaurant_id,"type"=>$type);  // 指定类型为$type
        $data = $set->where($where)->find();
        if($data){
            // 已有记录，就更新
            if($set->where($where)->save($receive) !== false){
                return true;
            }else{
                return false;
            }
        }else{
            // 没有记录就添加
            // 添加的时候要指定类型为$type
            $receive['type'] = $type;
            if($set->add($receive)){
                return true;
            }else{
                return false;
            }
        }
    }

    // 充值信息
    public function recharge(){
        //充值信息
        $where['restaurant_id'] = session("restaurant_id");
        $restaurant = D("restaurant");
        $business_id = $restaurant->where($where)->getField("business_id");
        $condition1['business_id'] = $business_id;
        $condition1['type'] = 1;
        $all_benefit = D("all_benefit");
        $prepaid_rules = $all_benefit->where($condition1)->select();
        $this->assign("prepaid_rules",$prepaid_rules);

        //店铺余额开关
        $set = D("set");
        $set_condition['type'] = 6; // 6代表余额开关
        $if_open_remind = $set->where($set_condition)->getField('if_open');
        $this->assign("if_open_remind",$if_open_remind);
        $this->display();
    }

    // 余额开关切换
    public function remind_set(){
        $receive = $this->set(6);
        if($receive){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }
}