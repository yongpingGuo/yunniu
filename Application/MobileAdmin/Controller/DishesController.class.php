<?php
namespace MobileAdmin\Controller;
use Think\Controller;
use data\service\SellOut as ServiceSellOut;

class DishesController extends Controller{
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
        redirect('/index.php/MobileAdmin/Dishes/category_set');
    }

    //新增菜品页面
    public function dish_add(){
        //获取店铺分区信息
        $district_model = D("restaurant_district");
        $district_where['restaurant_id'] = session("restaurant_id");
        $district_list = $district_model->where($district_where)->field("district_id,district_name")->select();
        $district_list[] = array(
            "district_id" => 0,
            "district_name" => "不设分区",
        );
        $this->assign("district_list",$district_list);

        $dishes = D('food_category');
        $condition['restaurant_id'] = session('restaurant_id');
        $arr = $dishes->where($condition)->order('sort asc')->select();
        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
        $this->assign("printerList",$printList);
        $this->assign('data', $arr);
        $this->display();
    }

    //新增菜品入库处理
    public function createfoodinfo()
    {
        $food = D('Food');
        if (!empty($_POST)){
            $food->startTrans();
            // var_dump($_FILES);exit();
//            $_POST['food_pic'] = './Application/Admin/Uploads/default/unupload.png';
            if($_FILES['food_pic']['error'] != 4){     //图片上传
                $upload = new \Think\Upload();// 实例化上传类
                // $upload->maxSize = 3145728;// 设置附件上传大小
                $upload->maxSize = 1024*1024*6;// 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->savePath = 'upfoodimg/'; // 设置附件上传目录
                $upload->autoSub = false;
                $z = $upload->upload();
                $picpathname = './Application/Admin/Uploads/' . $z[food_pic]['savepath'] . $z[food_pic]['savename'];
                $image = new \Think\Image();
                $image->open($picpathname);// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                $image->thumb(500, 300)->save($picpathname);
                $_POST['food_pic'] = $picpathname;

                // 不需要使用到水印图片
                $need_save = false;
            }else{
                // 需要使用到水印图片
                $need_save = true;
                $_POST['food_pic'] = './Application/Admin/Uploads/default/unupload.png';
            }


            // var_dump($_POST);exit();
            $restaurant_id = session('restaurant_id');
            //表单数据
            $data['food_name'] = $_POST['food_name'];
            $data['food_img'] = $_POST['food_pic'];
            $data['discount'] = $_POST['discount'];
            $data['food_price'] = $_POST['food_price'];
            $data['star_level'] = 5;    // 先固定5
            $data['dianzan'] = $_POST['dianzan'];    // 新增的一个字段，点赞，最终会替换star_level
            $data['hot_level'] = $_POST['hot_level'];
            $data['foods_num_day'] = $_POST['foods_num_day'];
            $data['food_desc'] = $_POST['food_desc'];
            $data['is_prom'] = isset($_POST['is_prom']) ? isset($_POST['is_prom']) :0;
            $data['district_id'] = $_POST['district'];
            $data['restaurant_id'] = $restaurant_id;
            $data['print_id'] = $_POST['print_id'];
            $data['tag_print_id'] = $_POST['tag_print_id'];

            $num = $food->where("restaurant_id=$restaurant_id")->max('sort');
            $data['sort'] = str_pad($num+1,3,"0",STR_PAD_LEFT);

            $r = $food->add($data);
            if($r != false){
                $relative = D('food_category_relative');
                $data2['food_id'] = $r;
                $sort1 = $_POST['sort1'];
                foreach ($sort1 as $so){
                    $data2['food_category_id'] = $so;
                    $r2 = $relative->add($data2);
                }
            }else{
                $food->rollback();
                $msg['code'] = "0";
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
            if($_POST['is_prom'] == 1){
                $prom = D('prom');
                $data1['prom_id'] = $r;
                $data1['prom_price'] = $_POST['prom_price'];
                $data1['discount'] = $_POST['prom_discount'];
                $data1['prom_goods_num'] = $_POST['prom_goods_num'];;
                $data1['prom_start_time'] = strtotime($_POST['prom_start_time']);
                $data1['prom_end_time'] = strtotime($_POST['prom_end_time']);
                $r1 = $prom->add($data1);
                //如果出现错误，则事务回滚
                if($r1 === false){
                    $food->rollback();
                    $msg['code'] = "0";
                    $msg['msg'] = "失败";
                    exit(json_encode($msg));
                }
            }

            if($r && $need_save){
                $food_name = $_POST['food_name'];
                $save_path = "./Public/default_food_water_print/".$r.".png";    // 水印图片保存路径和文件名，命名为菜品id
                $image = new \Think\Image();
                $image->open('./Public/default_food_water_print/food_water_print.png')  // 水印背景图
                ->text($food_name,'./font.ttc',30,'#000000',\Think\Image::IMAGE_WATER_CENTER)   // 水印内容为菜品名
                ->save($save_path);  // 保存为新图片

                $res = $food->where(array('food_id'=>$r))->save(array('food_img'=>$save_path));
            }
            $food->commit();
            $msg['code'] = "1";
            $msg['msg'] = "菜品新增成功，请在下方添加菜品附属类别";
            $msg['food_id'] = $r;
            $msg['first_sort'] = $sort1[0];

            // 新增菜品时，如果每天供应量为0，则为售罄，推送给安卓
            if($_POST['foods_num_day'] == 0){
                $food->where("food_id = $r")->save(array('is_shutdown'=>1));
                // 售罄处理 则推送消息给安卓
                $S_SellOut = new ServiceSellOut();
                $S_SellOut->whenUpdateFood($r,session('restaurant_id'),'sellOut');
            }

            exit(json_encode($msg));
        }
    }
    
    // 菜品设置
    public function food_set(){
        $dishes = D('food_category');
        $condition['restaurant_id'] = session("restaurant_id");
        $arr = $dishes->where($condition)->order('sort asc')->select();
        $this->assign('data', $arr);
        $relative = D('food_category_relative');
        // 如果不是新增后跳转过来的，则默认选中第一个菜品分类
        $get_food_category_id = I('get.food_category_id');
        if(empty($get_food_category_id)){
            $final_food_category_id = $arr[0]['food_category_id'];
        }else{
            $final_food_category_id = $get_food_category_id;
        }
        $map['food_category_id'] = $final_food_category_id;
        $this->assign('final_food_category_id', $final_food_category_id);   // 用于前端默认选中哪个分类

        $arr = $relative->where($map)->select();
        $food_list = array();
        $foodModel = D('food');
        $dishes = D('food_category');
        foreach ($arr as $v) {
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $foodModel->where($condition)->find();
            $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }
        $sortArr = array();
        foreach($food_list as $v1){
            $sortArr[] = $v1['sort'];
        }
        array_multisort($sortArr, SORT_ASC, $food_list);

        $this->assign('info',$food_list);
//        dump($food_list);
        $this->display();
    }

    //点击菜品编辑，填充数据
    public function food_edit(){
        //获取店铺分区信息
        $district_model = D("restaurant_district");
        $district_where['restaurant_id'] = session("restaurant_id");
        $district_list = $district_model->where($district_where)->field("district_id,district_name")->select();
        $district_list[] = array(
            "district_id" => 0,
            "district_name" => "不设分区",
        );
        $this->assign("district_list",$district_list);

        //获取当前所有的打印机消息
        $printerModel = D("printer");
        $pr_condition['restaurant_id'] = session("restaurant_id");
        $printerList = $printerModel->where($pr_condition)->select();
        $this->assign("printerList",$printerList);

        $id = $_GET['food_id'];
        $this->assign("food_id",$id);
        $food = D('Food');
        $arr = $food->find($id);
        $arr['food_img'] = $arr['food_img'];
        //根据打印机id获取打印机名称
        $where = [];
        $where['printer_id'] = $arr['tag_print_id'];
        $arr['printer_name'] = $printerModel->field('printer_name')->where($where)->find()['printer_name'];

        // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

        $Model = M(); // 实例化一个model对象 没有对应任何数据表
        $table_time=date("Y").date("m");
        $sql = "select SUM(t1.food_num) as num from order_food_$table_time t1 LEFT JOIN
                        `order_$table_time` t2 on t1.order_id = t2.order_id
                                                WHERE t1.food_id = $id and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end";
        $res = $Model->query($sql);
        // 当天到目前为止消费数量
        $total = $res[0]['num'] ? : 0;
        $this->assign("num", $total);
        $this->assign('info', $arr);

        //获取所有的菜品分类
        $dishes = D('food_category');
        $f_condition['restaurant_id'] = session('restaurant_id');
        $arr1 = $dishes->where($f_condition)->order('sort asc')->select();

        //获取该菜品所属的菜品分类
        $dishes = D('food_category_relative');
        $condition["food_id"] = $id;
        $arr2 = $dishes->where($condition)->select();

        //对对应的菜品分类做标记，以便前端显示
        foreach($arr1 as $key => $val){
            foreach($arr2 as $k =>$v){
                if($val['food_category_id'] == $v['food_category_id']){
                    $arr1[$key]["is_select"] = 1;
                    continue;
                }
            }
        }
        $this->assign("data", $arr1);


        //获取菜品的时价属性
        $prom = D('prom');
        $arr3 = $prom->find($id);
        $this->assign("info1", $arr3);

        //获取菜品的类别和属性
       /* $type_condition['food_id'] = $id;
        $attr_type_model = D('attribute_type');
        $attr_type_list = $attr_type_model->where($type_condition)->select();
        $food_attr_model = D('food_attribute');
        $attr_list=array();
        foreach($attr_type_list as $kt => $vt){
            $ft_condition['attribute_type_id'] = $vt['attribute_type_id'];
            $temp = $food_attr_model->where($ft_condition)->select();
            $temp2 = $food_attr_model->where($ft_condition)->count();
            $attr_type_list[$kt]['num'] = $temp2;
            $attr_list[$kt+1] = $temp;
        }

        $attr_type_list2 = array();
        foreach($attr_type_list as$ky => $vl){
            $attr_type_list2[$ky+1] = $vl;
            $attr_type_list2[$ky+1]['attr_list'] = $attr_list[$ky+1];
        }
        $this->assign("attr_type_list",$attr_type_list2);*/
        $this->display("food_edit");
    }

    # 编辑菜品
    public function modifyfoodinfo(){
        $food = D('food');
        if (!empty($_POST)){
            $data = array();
            if ($_FILES['food_pic']['error'] != 4){
                $condition['food_id'] = $_GET['food_id'];
                $addr_img = $food->where($condition)->field('food_img')->find()['food_img'];
                if($addr_img != './Application/Admin/Uploads/default/unupload.png'){
                    unlink($addr_img);
                }
                $up = new \Think\Upload();
                $up->maxSize = 1024*1024*6;// 设置附件上传大小
                $up->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $up->savePath = 'upfoodimg/'; // 设置附件上传目录
                $up->autoSub = false;
                $z = $up->uploadOne($_FILES['food_pic']);
                $picpathname = './Application/Admin/Uploads/' . $z['savepath'] . $z['savename'];
                $image = new \Think\Image();
                $image->open($picpathname);// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                $image->thumb(500, 300)->save($picpathname);
                $_POST['image'] = $picpathname;
                $data['food_img'] = $_POST['image'];
            }
            $food_id = $_GET['food_id'];

            // 在未更新前先查出关于售罄的详细信息
            $sellOut_info = $food->where("food_id = $food_id")->field('is_shutdown,update_time,sale_num')->find();

            $data['food_name'] = $_POST['food_name'];
            $data['food_price'] = $_POST['food_price'];
//            $data['discount'] = $_POST['discount'];
            $data['foods_num_day'] = $_POST['foods_num_day'];
//            $data['star_level'] = $_POST['star_level'];
            $data['dianzan'] = $_POST['dianzan'];    // 点赞最终替换原来的星级star_level
            $data['hot_level'] = $_POST['hot_level'];
            $data['food_desc'] = $_POST['food_desc'];
//            $data['is_prom'] = $_POST['is_prom'];
            $data['print_id'] = $_POST['print_id'];
            $data['tag_print_id'] = $_POST['tag_print_id'];
            $data['district_id'] = $_POST['district'];
            $data['restaurant_id'] = session('restaurant_id');
            $line = $food->where("food_id = $food_id")->save($data);

            $relative = D('food_category_relative');
            $data2['food_id'] = $food_id;
            $sort1 = $_POST['sort1'];
            //先删除改菜品的分类，然后重新添加关联
            $relative->where("food_id = $food_id")->delete();
            foreach ($sort1 as $so){
                $data2['food_category_id'] = $so;
                $r2 = $relative->add($data2);
            }
            if($_POST['is_prom'] == 1){//判定编辑时，是否开启了时价
                $prom_id = $_GET['food_id'];
                $prom = D('prom');
                $result = $prom->where("prom_id = $prom_id")->find();
                $data1['prom_id'] = $prom_id;
                $data1['prom_price'] = $_POST['prom_price'];
                $data1['discount'] = $_POST['prom_discount'];
                $data1['prom_goods_num'] = $_POST['prom_goods_num'];
                $data1['prom_start_time'] = strtotime($_POST['prom_start_time']);
                $data1['prom_end_time'] = strtotime($_POST['prom_end_time']);
                if($result){//如果开启了时价，判段之前是否存在时价,存在编辑时价表
                    $prom->save($data1);
                }else{//不存在，新增时价表
                    $prom->add($data1);
                }
            }

            // 修改了每日供应量，与售罄进行对比，比售罄量多则推送消息给安卓
            $food_num_day = $_POST['foods_num_day'];
            $Date = date('Y-m-d',time());
            $startTime = '00:00:00';
            $endTime = '23:59:59';
            $startTimeStr = strtotime($Date." ".$startTime);
            $endTimeStr = strtotime($Date." ".$endTime);
            $update_time = $sellOut_info['update_time'];

            // 情况一：
            // 更新时间不在今天内的，改为shutdown为0，sale_num为0，update_time为当前
            // 然后判断当前food_num_day为0的话就shutdown
            // 情况二：
            // 更新时间在今天范围内的，shutdown为1，然后判断当前food_num_day大于sale_num的话就上架，shutdown改为0
            // 更新时间在今天范围内的，shutdown为0，然后判断当前food_num_day小于等于sale_num的话就售罄，shutdown改为1
            if(($startTimeStr<$update_time && $update_time<$endTimeStr) && $update_time !== null){
                // shutdown为1
                if($sellOut_info['is_shutdown'] == 1){
                    if($food_num_day>$sellOut_info['sale_num']){
                        $food->where("food_id = $food_id")->save(array('is_shutdown'=>0));
                        // 上架处理 则推送消息给安卓
                        $S_SellOut = new ServiceSellOut();
                        $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'onSale');
                    }
                }else{
                    if($food_num_day <= $sellOut_info['sale_num']){
                        $food->where("food_id = $food_id")->save(array('is_shutdown'=>1));
                        // 售罄处理 则推送消息给安卓
                        $S_SellOut = new ServiceSellOut();
                        $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'sellOut');
                    }
                }
            }else{
                // 不是在今天范围内
                // shutdown置为0，已卖份数置为0，更新时间为当前
                $food->where("food_id = $food_id")->save(array('is_shutdown'=>0,'sale_num'=>0,'update_time'=>time()));
                // 如果客户设置的每天供应量为0的话，则也为shutdown售罄，推给安卓
                if($food_num_day == 0){
                    $S_SellOut = new ServiceSellOut();
                    $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'sellOut');
                }
            }

            if ($line !== false) {
                $msg['code'] = "1";
                $msg['msg'] = "成功";
                $msg['first_sort'] = $sort1[0];
                exit(json_encode($msg));
            }else {
                $msg['code'] = "0";
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
        }
    }

    //删除菜品分类关联表
    public function delfoodinfo(){
        $food_id = I('post.food_id');
        $food_category_id = I('post.food_category_id');

        $food_category_relative = D('food_category_relative');
        // 先判断该菜品共关联了多少个分类，如果大于一个，则只删除菜品与菜品分类关联表的记录，如果等于1则连菜品信息也要删除
        $relative_num = $food_category_relative->where(array('food_id'=>$food_id))->count();
        if($relative_num>1){
            $food_category_relative->where(array('food_id'=>$food_id,'food_category_id'=>$food_category_id))->delete();
            $this->ajaxReturn(1);
        }else{
            $food = D('food');
            $food->startTrans();
            $condition['food_id'] = $food_id;
            $result1 =  $food_category_relative->where($condition)->delete(); //先删除菜品表与菜品分类关联的第三个表

            $prom = D('prom');
            $condition1['prom_id'] = $food_id;
            $result2 = $prom->where($condition1)->delete();    //删除菜品定时表

            $addr_img = $food->where($condition)->field('food_img')->find()['food_img']; //先找到菜品图片
            if($addr_img != "./Application/Admin/Uploads/default/unupload.png"){
                unlink($addr_img);						//菜品删除图片
            }
            $result3 = $food->where($condition)->delete(); //再删除菜品记录
            if($result1 !== false && $result2 !== false && $result3 !== false){
                $attribute_type = D('attribute_type');
                $addr_list = $attribute_type->where($condition)->select(); //查询菜品类别表，删除关联类别记录
                $food_attribute = D('food_attribute');
                foreach($addr_list as $k => $v){
                    $condition2['attribute_type_id'] = $v['attribute_type_id'];
                    $addr_list1 = $food_attribute->where($condition2)->select(); //查谒菜品属性表，删除关联属性记录
                    foreach($addr_list1 as $k=>$v){
                        unlink($addr_list1[$k]['attribute_img']);
                        $condition3['attribute_type_id']  = $addr_list1[$k]['attribute_type_id'];
                        $food_attribute->where($condition3)->delete();
                    }
                }
                $attribute_type->where($condition)->delete();
                $food->commit();
                $this->ajaxReturn(1);
            }else{
                $food->rollback();
                $this->ajaxReturn(0);
            }
        }
    }

    //数据上移
    public function moveup(){
        $food = D('food');
        $when_sort = I('post.sort');
        $food_id = I('post.food_id');
        $map['sort'] = array('lt',I('post.sort'));
        $map['restaurant_id'] = session('restaurant_id');
        $last_sort = $food->where($map)->order('sort desc')->field('sort')->limit(1)->find()['sort'];   // 数值小于当前排序ID的排序ID
        $last_id = $food->where($map)->order('sort desc')->field('food_id')->limit(1)->find()['food_id'];   // 数值小于当前排序ID的菜品ID
        if($last_sort>0){
            $newsort = $last_sort;
            $last_sort = I('post.sort');
            $obj['sort'] = $last_sort;
            $obj['food_id'] = $last_id;
            $r = $food->save($obj);
            $when_sort = $newsort;
            $obj1['sort'] = $when_sort;
            $obj1['food_id'] = I('post.food_id');
            $r1 = $food->save($obj1);
            if($r && $r1){
                $msg['msg'] = "成功";
                $msg['code'] = 1;
                exit(json_encode($msg));
            }
        }
    }

    //测试回调id
    public function bbb()
    {
        $msg_id = I('get.id');
        if(empty($msg_id)){
            echo '请输入msg_id';
            die();
        }
        $base = A('Admin/Base');
        $res = $base->query_push_status($msg_id);
        dump($res);
    }

    //把菜品设为售罄状态
    public function sell_all()
    {
        $food_id = I('post.food_id');
        $map['food_id'] =I('post.food_id');
        $map['restaurant_id'] = session('restaurant_id');
        $data['is_shutdown'] = I('get.is_shutdown');

//        $type = I('post.not_sell_all');
//        if($type === 1 ){//这说明把菜设为正常状态（没有售罄）,是否售罄，0否，1是，默认0否
//            $data['is_shutdown'] = 0;
//        }else{
//            $data['is_shutdown'] = 1;
//        }
        $res = M('food')->where($map)->save($data);

        if($res){
            /**************************************进行阿里推送*********************************************/
            if($data['is_shutdown'] != 1){ //设为上架
                // 上架处理 则推送消息给安卓
                $S_SellOut = new ServiceSellOut();
                $res = $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'onSale');

            }else{
                $restaurant_id = $_SESSION['restaurant_id'];
                $devices_ids = D('push_to_device_by_ali')->where(array('restaurant_id'=>$restaurant_id))->field('device_id')->select();

                $php_title = 'this_food_sellout'; // 标题
                // 推送的数据
                $push_data['type'] = 'sellOut';   // 类型为：售罄
                $push_food_ids[] = $food_id;
                $push_data['food_id'] = $push_food_ids;
                $push_data['platform'] = 'payNotify';
                $php_body = json_encode($push_data);

                $base = A('Admin/Base');
                $res = $base->ali_push_to_android_can_set($devices_ids,$php_title,$php_body);
            }
            /****************************************阿里推送*************************************/
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }


    //数据下移
    public function movedown(){
        $food = D('food');
        $when_sort = I('post.sort');					//当前排序ID
        $food_id = I('post.food_id');					//当前自增ID
        $map['sort'] = array('Gt',I('post.sort'));   	//sort	大于传过来的sort
        $map['restaurant_id'] = session('restaurant_id');
        $next_sort = $food->where($map)->order('sort asc')->field('sort')->limit(1)->find()['sort'];			//下一个排序ID
        $next_id = $food->where($map)->order('sort asc')->field('food_id')->limit(1)->find()['food_id'];		//下一个自增ID
        if($next_sort>0){
            $newsort = $next_sort;							//新建第三个ID来存储下一个排序ID
            $next_sort = I('post.sort');					//下一个排序ID被赋值为当前排序ID
            $obj['sort'] = $next_sort;
            $obj['food_id'] = $next_id;						//修改上一个sort
            $r = $food->save($obj);
            $when_sort = $newsort;							//将第三个ID值赋于当前ID
            $obj1['sort'] = $when_sort;
            $obj1['food_id'] = I('post.food_id');
            $r1 = $food->save($obj1);
            if($r && $r1){
                $msg['msg'] = "成功";
                $msg['code'] = 1;
                exit(json_encode($msg));
            }
        }
    }

    // 分类中的菜品上移后的页面刷新
    public function food_up_in_cate(){
        $relative = D('food_category_relative');
        $map['food_category_id'] = I('get.food_category_id');
        $arr = $relative->where($map)->select();
        $food_list = array();
        $foodModel = D('food');
        $dishes = D('food_category');
        foreach ($arr as $v) {
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $foodModel->where($condition)->find();
            $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }

        $sortArr = array();
        foreach($food_list as $v1){
            $sortArr[] = $v1['sort'];
        }
        array_multisort($sortArr, SORT_ASC, $food_list);
        $this->assign('info',$food_list);
        $this->display("food_up_in_cate");
    }

    //通过点击具体某个菜品分类来显示该分类下所有的菜品
    public function showDisinfoBykey(){
        $relative = D('food_category_relative');
        $map['food_category_id'] = I('get.food_category_id');
        $arr = $relative->where($map)->select();
        $food_list = array();
        $foodModel = D('food');
        $dishes = D('food_category');
        foreach ($arr as $v) {
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $foodModel->where($condition)->find();
            $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }
        $sortArr = array();
        foreach($food_list as $v1){
            $sortArr[] = $v1['sort'];
        }
        array_multisort($sortArr, SORT_ASC, $food_list);

        $this->assign("info",$food_list);
        $this->assign("food_category_id",I('get.food_category_id'));
        $this->display("showfood_by_cate");
    }

    /**********************************以下为属性和类别*************************************/
    public function type_and_attr(){
        $id = I('get.food_id');
        $type_condition['food_id'] = $id;
        $attr_type_model = D('attribute_type');
        $attr_type_list = $attr_type_model->where($type_condition)->select();
        $food_attr_model = D('food_attribute');
        $attr_list=array();
        foreach($attr_type_list as $kt => $vt){
            $ft_condition['attribute_type_id'] = $vt['attribute_type_id'];
            $temp = $food_attr_model->where($ft_condition)->select();
            $temp2 = $food_attr_model->where($ft_condition)->count();
            $attr_type_list[$kt]['num'] = $temp2;
            $attr_list[$kt+1] = $temp;
        }

        $attr_type_list2 = array();
        foreach($attr_type_list as$ky => $vl){
            $attr_type_list2[$ky+1] = $vl;
            $attr_type_list2[$ky+1]['attr_list'] = $attr_list[$ky+1];
        }

        $this->assign("attr_type_list",$attr_type_list2);

        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
        $this->assign("printerList",$printList);
        $this->assign("food_id",$id);
        $this->display();
    }

    /***********点击新增分类后***********/
    // 新增一个属性类别时，获取打印机信息
    public function get_print(){
        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
        exit(json_encode($printList));
    }

    // 新增属性分类
    public function keep_attr_type(){
        $attribute_type_id = I('post.attribute_type_id');
        $attr_type_model = D("attribute_type");
        if($attribute_type_id == 'undefined' || empty($attribute_type_id)){
            // 新增
            unset($_POST['attribute_type_id']);
            $data = $attr_type_model->create();
            $data['restaurant_id'] = session("restaurant_id");
            $rel = $attr_type_model->add($data);
            if($rel !== false){
                $msg['attribute_type_id'] = $rel;
                $msg['code'] = 1;
                $msg['msg'] = "操作成功";
                exit(json_encode($msg));
            }else{
                $msg['code'] = 0;
                $msg['msg'] = "操作失败";
                exit(json_encode($msg));
            }
        }else{
            // 编辑
            $data = $attr_type_model->create();
            $res = $attr_type_model->save($data);
            if($res !== false){
                $msg['attribute_type_id'] = $attribute_type_id;
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

    // 新增属性
    public function keep_attr(){
        $food_attribute_id = I('post.food_attribute_id');
        $attrModel = D("food_attribute");
        if($food_attribute_id == 'undefined' || empty($food_attribute_id)){
            // 新增
            unset($_POST['food_attribute_id']);
            $data1 = $attrModel->create();
            $data['attribute_type_id'] = $data1['attribute_type_id'];
            $data['attribute_name'] = $data1['attribute_name'];
            $data['attribute_price'] = $data1['attribute_price'];
            $rel = $attrModel->add($data);
            if($rel !== false){
                $msg['food_attribute_id'] = $rel;
                $msg['code'] = 1;
                $msg['msg'] = "操作成功";
                exit(json_encode($msg));
            }else{
                $msg['code'] = 0;
                exit(json_encode($msg));
            }
        }else{
            // 编辑
            $data1 = $attrModel->create();
            $rel = $attrModel->save($data1);
            if($rel !== false){
                $msg['food_attribute_id'] = $food_attribute_id;
                $msg['code'] = 1;
                $msg['msg'] = "操作成功";
                exit(json_encode($msg));
            }else{
                $msg['code'] = 0;
                exit(json_encode($msg));
            }
        }

    }

    // 删除规格类别
    public function del_type(){
        $type_id = I('post.attribute_type_id');
        $attribute_type_model = D('attribute_type');
        $attribute_type_model->startTrans();

        $food_attribute_model = D('food_attribute');
        $condition['attribute_type_id'] = $type_id;
        // 删除属性
        $rel1 = $food_attribute_model->where($condition)->delete();
        if($rel1 === false){
            $attribute_type_model->rollback();
        }
        // 删除类别
        $rel2 = $attribute_type_model->where($condition)->delete();
        if($rel2 === false){
            $attribute_type_model->rollback();
        }
        if($rel2 !== false){
            $attribute_type_model->commit();
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            exit(json_encode($msg));
        }
    }

    // 删除属性
    public function del_attr(){
        $attr_id = I('post.food_attribute_id');
        $food_attribute_model = D('food_attribute');
        $condition['food_attribute_id'] = $attr_id;
        $rel = $food_attribute_model->where($condition)->delete();
        if($rel !== false){
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            exit(json_encode($msg));
        }
    }
    /**********************************以下为菜品分类***************************************/

    // 菜品分类设置
    public function category_set()
    {
        $dishes = D('food_category');
        $condition['restaurant_id'] = session("restaurant_id");
        $arr = $dishes->where($condition)->order('sort asc')->select();
        $this->assign('data', $arr);
        $this->display();
    }

    // 添加菜品分类页面
    public function food_category_add(){
        /***************菜品分类系统图标************************/
        // 分类表ico_category表里面id为1的菜品分类图标
        $ico_detail = D('ico_manager')->where(array('relation_category_id'=>1))->find();
        $photo = explode(",",$ico_detail['photo']);
        unset($photo[0]);
        $rootpath = $ico_detail['_rootpath'];
        $arr = array();
        foreach($photo as $key=>$val){
            $arr[$key-1]['photo'] = $rootpath.'/'.$val;
            $arr[$key-1]['relation_category_id'] = $ico_detail['relation_category_id'];
            $arr[$key-1]['img_id'] = $ico_detail['img_id'];
            $arr[$key-1]['ico_type'] = $ico_detail['ico_type'];
        }

        $this->assign('ico_detail',$arr);
        /***************菜品分类系统图标************************/

        $this->assign('data', $arr);
        $this->display();
    }

    // 点击分类后请求该分类下的具体的图标
    public function request_ico(){
        // 分类名
        $ico_category_id = I('get.id');
//        $ico_category_id = 57;
        $ico_detail = D('ico_manager')->where(array('relation_category_id'=>$ico_category_id))->find();
        $photo = explode(",",$ico_detail['photo']);
        unset($photo[0]);
        $rootpath = $ico_detail['_rootpath'];
        $arr = array();
        foreach($photo as $key=>$val){
            $arr[$key-1]['photo'] = $rootpath.'/'.$val;
            $arr[$key-1]['relation_category_id'] = $ico_detail['relation_category_id'];
            $arr[$key-1]['img_id'] = $ico_detail['img_id'];
            $arr[$key-1]['ico_type'] = $ico_detail['ico_type'];
        }
        exit(json_encode($arr));
    }

	//菜品分类操作后的ajax页面刷新
	public function food_category_ajax(){
		 $dishes = D('food_category');
         $ff_condition['restaurant_id'] = session('restaurant_id');
         $arr = $dishes->where($ff_condition)->order('sort asc')->select();
         $this->assign('data', $arr);
         $this->display('showcategory');
	}

	//新增菜品分类
    public function createDishetype(){
        $dishes = D('food_category');
        $dishes->startTrans();
        $info = $dishes->create();

        // 自定义图标上传
        if ($_FILES['user_define_img'] != null) {
            if ($_FILES['user_define_img']['error'] != 4) {
                // 调用函数实现上传
                $_POST['_rootpath'] = '/Public/Uploads/UserDefineIco'; // 用户自定义图标上传路径
                $res = upload();
                // 上传不成功
                if(is_string($res))
                {
                    $this->error('文件上传失败，原因为' . $res);
                }
                $final_img_url = '/'.$res['user_define_img']['savepath'] . $res['user_define_img']['savename'];
                $info['img_url'] = $final_img_url;
            }
        }

		$restaurant_id = session('restaurant_id');
		$category_num = $dishes->where("restaurant_id=$restaurant_id")->max('sort');    # 找出最大的排序号
		$info['sort'] = str_pad($category_num+1,3,"0",STR_PAD_LEFT);   //排序号           #　把字符串填充为新的长度　
        $info['restaurant_id'] = $restaurant_id;
        $line = $dishes->add($info);
        if($line !== false){
            /*[time] => [["2017/09/08","2017/09/16","13:30","13:40"],["2017/09/22","2017/09/30","13:30","13:30"],["2017/09/09","2017/09/21","13:25","00:20"]]
            [day] => 14:05,14:35,1-3-|17:05,19:30,2-4-|14:10,17:55,6-0-|*/

            // 开了定时设置
            if(I('post.is_timing')){
                $time = $_POST["time"];
                if($time){
                    $categoryTimeModel = D('category_time');
                    $time = json_decode($time);
                    foreach($time as $t_key => $t_val){
                        if(count($t_val) == 4){
                            $start_time = "$t_val[0] $t_val[2]";
                            $end_time = "$t_val[1] $t_val[3]";
                            $t_condition['time1'] = strtotime($start_time);
                            $t_condition['time2'] = strtotime($end_time);
                            $t_condition['category_id'] = $line;
                            $categoryTimeModel->add($t_condition);
                        }
                    }
                }
                $day = $_POST["day"];
                if($day){
//                     [day] => 12:10,13:20,135|13:05,13:40,36|13:15,12:20,0|
//                    [day] => 14:05,14:35,1-3-|17:05,19:30,2-4-|14:10,17:55,6-0-|
                    $explode_day = explode('|',$day);
                    unset($explode_day[count($explode_day)-1]);
                    $food_category_Model = D('food_category_timing');
                    foreach($explode_day as $d_key => $d_val){
                        $every_one = explode(',',$d_val);   // 炸开为每个独立的定时组合
                        if(count($every_one) == 3){
                            // 三个元素的才是完整的，开始时间、结束时间、星期
                            $d_data['timing_day'] = substr($every_one[2],0,strlen($every_one[2])-1); // 去掉最后的'-'
                            $d_data['start_time'] = $every_one[0];
                            $d_data['end_time'] = $every_one[1];
                            $d_data['food_category_id'] = $line;
                            $food_category_Model->add($d_data);
                        }
                    }
                }
            }
            $dishes->commit();

            $this->ajaxReturn(1);
        }else{
            $dishes->rollback();
            $this->error('分类添加失败');
        }
    }

    //删除菜品类别
    public function delDishestype(){
        $id = I("get.food_category_id");
		$food_category_relative = D('food_category_relative');
		$result1 = $food_category_relative->where("food_category_id=$id")->select();
		if(!$result1){
	        $category_time = D("category_time");
	        $arr = $category_time->where("category_id=$id")->select();
			if($arr){
		        foreach($arr as $a){
		        	$condition['id'] = $a['id'];
		        	$category_time->where($condition)->delete();
		        }
			}

			$food_category_timing = D('food_category_timing');
			$food_category_timingArr = $food_category_timing->where("food_category_id=$id")->select();
			if($food_category_timingArr){
				foreach($food_category_timingArr as $value){
					$condition1['food_category_timing_id'] = $value['food_category_timing_id'];
					$food_category_timing->where($condition1)->delete();
				}
			}
			$food_category = D('food_category');
            // 获取到该分类的图片信息，删除非系统、非自定义图标，也就是自定义图标
            $img_info = $food_category->where("food_category_id=$id")->field('ico_category_type,img_url')->find();
            if($img_info['ico_category_type'] == 2){
                if($img_info['img_url'] != '/Public/images/defaultFoodCate1.png'){
                    // 删除自定义图标
                    @unlink('.'.$img_info['img_url']);
                }
            }

			$food_category->where("food_category_id=$id")->delete();

			$this->food_category_ajax();
		}else{
			$code = 1;
			echo $code;
		}
    }

	//编辑菜品分类
    public function modifyDishestype(){
        $dishes = D('food_category');
        $dishes->startTrans();

        $default_url = '/Public/images/defaultFoodCate1.png';
        $condition['img_url'] = I('post.img_url');
        $condition['ico_category_type'] = I('post.ico_category_type');
        // 自定义图标上传
        if ($_FILES['user_define_img'] != null) {
            if ($_FILES['user_define_img']['error'] != 4) {
                // 调用函数实现上传
                $_POST['_rootpath'] = '/Public/Uploads/UserDefineIco'; // 用户自定义图标上传路径
                $res = upload();
                // 上传不成功
                if(is_string($res))
                {
                    $this->error('文件上传失败，原因为' . $res);
                }
                $final_img_url = '/'.$res['user_define_img']['savepath'] . $res['user_define_img']['savename'];
                $condition['img_url'] = $final_img_url;
                // 删除掉旧的图片
                $food_category = D('food_category');
                // 获取到该分类的图片信息，删除非系统、非自定义图标，也就是自定义图标
                $id = I('post.food_category_id');
                $img_info = $food_category->where("food_category_id=$id")->field('img_url,ico_category_type')->find();
                // 0默认图标、1系统图标、2自定义图标
                if($img_info['ico_category_type'] != 0 && $img_info['ico_category_type'] != 1 && $img_info['img_url'] != $default_url){
                    // 删除自定义图标
                    @unlink('.'.$img_info['img_url']);
                }
            }
        }

        // 判断是否需要删除图片
        $sent_img_url = I('post.img_url');
        $origin_img_url_info = $dishes->where(array('food_category_id'=>I('post.food_category_id')))->field('ico_category_type,img_url')->find();
        if($origin_img_url_info['ico_category_type'] != 0 && $origin_img_url_info['ico_category_type'] != 1){
            // 自定义图标
            if($origin_img_url_info['img_url'] != $sent_img_url && $origin_img_url_info['img_url'] != $default_url){
                // 图标发生了改变
                // 删除自定义图标
                @unlink('.'.$origin_img_url_info['img_url']);
            }
        }


        $food_category_id = I('post.food_category_id');
        $condition['food_category_id'] = I('post.food_category_id');
        $condition['restaturant_id'] = session('restaurant_id');
        $condition['food_category_name'] = I('post.food_category_name');
		$condition['is_timing'] = I('post.is_timing');

        $line = $dishes->save($condition);
        if($line !== false){
            if(I('post.is_timing')){
                $time = $_POST["time"];
                if($time){
                    // 先删除掉以前的定时记录，再重新添加
                    $categoryTimeModel = D('category_time');
                    $wt_condition['category_id'] = $food_category_id;
                    $categoryTimeModel->where($wt_condition)->delete();

                    $time = json_decode($time);
                    foreach($time as $t_key => $t_val){
                        if(count($t_val) == 4){
                            $start_time = "$t_val[0] $t_val[2]";
                            $end_time = "$t_val[1] $t_val[3]";
                            $t_condition['time1'] = strtotime($start_time);
                            $t_condition['time2'] = strtotime($end_time);
                            $t_condition['category_id'] = $food_category_id;
                            $categoryTimeModel->add($t_condition);
                        }
                    }
                }
                $day = $_POST["day"];
                if($day){
//                     [day] => 12:10,13:20,135|13:05,13:40,36|13:15,12:20,0|
//                    [day] => 14:05,14:35,1-3-|17:05,19:30,2-4-|14:10,17:55,6-0-|

                    // 先删除掉以前的定时记录，再重新添加
                    $food_category_Model = D('food_category_timing');
                    $wd_data['food_category_id'] = $food_category_id;
                    $food_category_Model->where($wd_data)->delete();

                    $explode_day = explode('|',$day);
                    unset($explode_day[count($explode_day)-1]);
                    $food_category_Model = D('food_category_timing');
                    foreach($explode_day as $d_key => $d_val){
                        $every_one = explode(',',$d_val);   // 炸开为每个独立的定时组合
                        if(count($every_one) == 3){
                            // 三个元素的才是完整的，开始时间、结束时间、星期
                            $d_data['timing_day'] = substr($every_one[2],0,strlen($every_one[2])-1); // 去掉最后的'-'
                            $d_data['start_time'] = $every_one[0];
                            $d_data['end_time'] = $every_one[1];
                            $d_data['food_category_id'] = $food_category_id;
                            $food_category_Model->add($d_data);
                        }
                    }
                }
            }

            $dishes->commit();
            $this->ajaxReturn(1);
        }else{
            $dishes->rollback();

        }
    }

	//菜品分类数据上移
    public function moveup1(){
        $food_category = D('food_category');
        $map['sort'] = array('lt',I('post.sort'));
		$map['restaurant_id'] = session('restaurant_id');
        $data = $food_category->where($map)->order('sort desc')->field('sort')->limit(1)->find()['sort'];
        $last_id = $food_category->where($map)->order('sort desc')->field('food_category_id')->limit(1)->find()['food_category_id'];
        if($data>0){
            $newsort = $data;   // 即将要取代的分类序号
            $data = I('post.sort'); // 当前传递过来的分类序号
            $obj['sort'] = $data;
			$obj['food_category_id'] = $last_id;//修改即将要取代的分类序号为当前传递过来的序号
            $r = $food_category->save($obj);
            $dataOri = $newsort;//即将要取代的分类序号
            $obj1['sort'] = $dataOri;
			$obj1['food_category_id'] = I('post.food_category_id');
            $r1 = $food_category->save($obj1);
        }
        $where['restaurant_id'] = session("restaurant_id");
        $arr = $food_category->where($where)->order('sort asc')->select();
        $this->assign("data",$arr);
        $this->display('showcategory');
    }

	 //菜品分类数据下移
    public function movedown1(){
        $food_category = D('food_category');
        $map['sort'] = array('Gt',I('post.sort'));
		$map['restaurant_id'] = session('restaurant_id');
        $data = $food_category->where($map)->order('sort asc')->field('sort')->limit(1)->find()['sort'];
        $next_id = $food_category->where($map)->order('sort asc')->field('food_category_id')->limit(1)->find()['food_category_id'];
        if($data>0){
            $newsort = $data;
            $data = I('post.sort');
            $obj['sort'] = $data;
			$obj['food_category_id'] = $next_id;
            $r = $food_category->save($obj);
            $dataOri = $newsort;
            $obj1['sort'] = $dataOri;
			$obj1['food_category_id'] = I('post.food_category_id');
            $r1 = $food_category->save($obj1);
        }
        $where['restaurant_id'] = session("restaurant_id");
        $arr = $food_category->where($where)->order('sort asc')->select();
        $this->assign("data",$arr);
        $this->display('showcategory');
	}

    // 菜品分类编辑回显
    public function food_category_edit()
    {
        $id = $_GET['food_category_id'];
        $dishes = D('food_category');
        $di_condition['restaurant_id'] = session("restaurant_id");
        $info = $dishes->where($di_condition)->find($id);

        // 日期定时
        $food_categoryModel = D("category_time");
        $t_condition['category_id'] = $info['food_category_id'];
        $categoryTimeList = $food_categoryModel->where($t_condition)->select();
        if($categoryTimeList){
            foreach($categoryTimeList as $k => $v){
                $start_time = date("Y/m/d H:i",$v['time1']);
                $start_explode = explode(' ',$start_time);
                // 开始年月
                $categoryTimeList[$k]['start_year'] = $start_explode[0];
                // 开始时、分
                $categoryTimeList[$k]['start_hour'] = $start_explode[1];

                $end_time = date("Y/m/d H:i",$v['time2']);
                $end_explode = explode(' ',$end_time);
                // 结束年月
                $categoryTimeList[$k]['end_year'] = $end_explode[0];
                // 结束时、分
                $categoryTimeList[$k]['end_hour'] = $end_explode[1];
            }
            $info['category_time'] = $categoryTimeList;
        }

        $food_category_timing_Model = D('food_category_timing');
        $tim_condition['food_category_id'] = $info['food_category_id'];
        $category_timing = $food_category_timing_Model->where($tim_condition)->select();

        if($category_timing){
            foreach($category_timing as $key => $val){
                $category_timing[$key]['timing_day'] = explode("-",$val['timing_day']);
            }
            $info['category_timing'] = $category_timing;
        }
        $this->assign('info',$info);

        /***************菜品分类系统图标************************/
        // 分类表ico_category表里面id为1的菜品分类图标
        $ico_detail = D('ico_manager')->where(array('relation_category_id'=>1))->find();
        $photo = explode(",",$ico_detail['photo']);
        unset($photo[0]);
        $rootpath = $ico_detail['_rootpath'];
        $arr = array();
        foreach($photo as $key=>$val){
            $arr[$key-1]['photo'] = $rootpath.'/'.$val;
            $arr[$key-1]['relation_category_id'] = $ico_detail['relation_category_id'];
            $arr[$key-1]['img_id'] = $ico_detail['img_id'];
            $arr[$key-1]['ico_type'] = $ico_detail['ico_type'];
        }

        $this->assign('ico_detail',$arr);
        /***************菜品分类系统图标************************/

        $this->display();
    }

}