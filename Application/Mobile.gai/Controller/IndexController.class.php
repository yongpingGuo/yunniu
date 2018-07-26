<?php
namespace Mobile\Controller;
use Think\Controller;
use data\service\Order;
use data\service\Restaurant;

class IndexController extends Controller {
    // 订单页
    public function index_old(){
        session("restaurant_id", I("get.restaurant_id"));
        session("desk_code", I("get.desk_code"));
        if(empty(I("get.desk_code"))) session("desk_code", null);//不存在桌子号则删除

        $restaurant_id = session("restaurant_id");

        $food_category = D('food_category');
        $category_time = D('category_time');
        $condition['restaurant_id'] = $restaurant_id;
        $condition['is_timing'] = 0;
        $arr = $food_category->where($condition)->order('sort asc')->select();
        $where['restaurant_id'] = session('restaurant_id');
        $where['is_timing'] = 1;
        $food_categoryIdList =  $food_category->where($where)->field('food_category_id')->select();
        if($food_categoryIdList){
            $food_categoryNewIdList = array();
            foreach($food_categoryIdList as $foodvv){
                $food_categoryNewIdList[] = $foodvv['food_category_id'];
            }

            //第一种时间段的查询
            $current_time = time();
            $t_condition['time1'] = array("lt",$current_time);
            $t_condition['time2'] = array("gt",$current_time);
            $t_condition['category_id'] = array("in",$food_categoryNewIdList);
            $category_ids = $category_time->where($t_condition)->distinct("category_id")->field("category_id")->select();
            if($category_ids){
                $category_id_list = array();
                foreach ($category_ids as $k => $v) {
                    $index = "cid" . $v['category_id'];
                    $category_id_list[$index] = $v['category_id'];
                }
            }

            //第二种星期段的查询
            $current_week = date("w");
            $ftg_condition['timing_day'] = array("like", "%" . $current_week . "%");
            $ftg_condition['food_category_id'] = array("in",$food_categoryNewIdList);
            $category_timing_model = D("food_category_timing");
            $category_ids2 = $category_timing_model->where($ftg_condition)->distinct("food_category_id")->field("food_category_id,start_time,end_time")->select();

            $category_id_list2 = array();
            if ($category_ids2) {
                foreach ($category_ids2 as $kk => $vv) {
                    $start_time = strtotime($vv['start_time']);
                    $end_time = strtotime($vv['end_time']);
                    if ($start_time < $current_time && $end_time > $current_time) {
                        $index = "cid" . $vv["food_category_id"];
                        $category_id_list2[$index] = $vv["food_category_id"];
                    }
                }
            }

            //合并两种情况下的分类ID
            if($category_id_list == null){
                $categoryIdsList = $category_id_list2;
            }else if($category_id_list2 == null){
                $categoryIdsList = $category_id_list;
            }else{
                $categoryIdsList = array_merge($category_id_list, $category_id_list2);
            }

            $lastCategoryIdsList = array();
            foreach ($categoryIdsList as $vvv) {
                $lastCategoryIdsList[] = $vvv;
            }

            if($lastCategoryIdsList){
                $l_condition['food_category_id'] = array("in", $lastCategoryIdsList);
                $arr2 = $food_category->where($l_condition)->select();
                $arr = array_merge($arr, $arr2);
            }
        }

        $sortArr = array();
        $food_infos = array();
        foreach($arr as $key=>$v1){
            $sortArr[] = $v1['sort'];

            $foodUnderCate = $this->layzLoad($v1['food_category_id']);
            $food_infos[$v1['food_category_id']] = $foodUnderCate;
        }
        array_multisort($sortArr, SORT_ASC, $arr);

        $this->assign("info", $arr);
        $this->assign("food_infos", $food_infos);
        $this->display();
    }

    public function index(){
        session("restaurant_id", I("get.restaurant_id"));
        session("desk_code",I("get.desk_code"));
        cookie('restaurant_id', I("get.restaurant_id"), 1296000);//店铺id默认缓存15天
        if(empty(I("get.desk_code"))) session("desk_code", null);//不存在桌子号则删除
        $this->display();
    }

    // 菜品主页页面加载完后ajax请求
    public function ajaxGetFoodInfo()
    {
        $food_category = D('food_category');
        $return_data = $food_category->getAllFoodInfo();
        $this->ajaxReturn($return_data);
    }

    // 菜品分类下的菜品，用于懒加载
    public function layzLoad($food_category_id){
        $condition['food_category_id'] = $food_category_id;
        $food_category_relative = D('food_category_relative');
        $arr = $food_category_relative->where($condition)->select();
        //dump($arr);
        $food = D('food');
        $arrlist = array();
        //dump($arr);
        foreach ($arr as $v){
            // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

            $Model = M(); // 实例化一个model对象 没有对应任何数据表
           /* $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $v[food_id] and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end");*/

            $sql = "select SUM(t1.food_num) as num from order_food t1 LEFT JOIN
                        `order` t2 on t1.order_id = t2.order_id
												WHERE t1.food_id = $v[food_id] and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end";
            $res = $Model->query($sql);
            // 当天到目前为止消费数量
            $num = $res[0]['num'];

            if($num) {
                // 查询出该food_id对应多少限额
                $fit_num = D("food")->where(array("food_id" => $v['food_id']))->getField("foods_num_day");
                if($num >= $fit_num){
                    continue;
                }
            }

            $condition1['food_id'] = $v['food_id'];
            $condition1['restaurant_id'] = session("restaurant_id");
            $condition1['is_sale'] = 1;
            $result = $food->where($condition1)->find();
//            $result = $food->where($condition1)->field('is_prom,food_price')->find();
            if($result){
                if($result['is_prom'] == 1){
                    $prom = D('prom');
                    $where2['prom_id'] = $v['food_id'];
                    $when_time = time();
                    $where2['prom_start_time'] = array("lt",$when_time);
                    $where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
                    $prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
                    if($prom_price){
                        $result['food_price'] = $prom_price;
                    }else{
                        $result['food_price'] = $result['food_price'];
                    }
                }else{
                    $result['food_price'] = $result['food_price'];
                }
                // 该菜品是否有属性
//                $have_attribute = $Model->query('SELECT COUNT(*)  AS total_num FROM attribute_type AS t1 INNER JOIN food_attribute AS t2 ON t1.attribute_type_id = t2.attribute_type_id WHERE food_id = '.$v['food_id']);
                $have_attribute = $Model->query('SELECT COUNT(*)  AS total_num FROM attribute_type AS t1 RIGHT JOIN food_attribute AS t2 ON t1.attribute_type_id = t2.attribute_type_id WHERE t1.food_id = '.$v['food_id']);
                $result['have_attribute'] = $have_attribute[0]['total_num'];
                $arrlist[] = $result;
            }
        }
        return $arrlist;
    }

    // 点击+按钮查看菜品详情
    public function findfoodinfo(){
        session("restaurant_id",I("get.restaurant_id"));
        session("desk_code",I("get.desk_code"));
        $food = D('food');
        $condition['food_id'] = I('get.food_id');
        $is_prom = $food->where($condition)->field('is_prom')->find()['is_prom'];
        $food_price = $food->where($condition)->field('food_price')->find()['food_price'];
        $prom = D('prom');
        if($is_prom == 1){
            $where2['prom_id'] = I('get.food_id');
            $when_time = time();
            $where2['prom_start_time'] = array("lt",$when_time);
            $where2['prom_end_time'] = array("gt",$when_time);
            $prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
            if($prom_price){
                $prom_price = $prom_price;
            }else{
                $prom_price = $food_price;
            }
        }else{
            $prom_price = $food_price;
        }

        $this->assign("food_price",$prom_price);

        $arr = $food->where($condition)->field("food_id,food_name,food_img,food_desc")->find();
        $this->assign("info3", $arr);

        $attribute_type = D('attribute_type');
        $at_condition['food_id'] = $arr['food_id'];
        $at_list = $attribute_type->where($at_condition)->field('attribute_type_id,type_name,select_type')->select();
        $food_attribute = D('food_attribute');

        foreach ($at_list as $k => $v) {
            $fa_condition['attribute_type_id'] = $v['attribute_type_id'];
            $f_attr = $food_attribute->where($fa_condition)->field("food_attribute_id,attribute_name,attribute_price")->select();

            foreach($f_attr as $fok => $fov){
                $length = strlen($fov["attribute_name"]);
                if($length <= 12){
                    $f_attr[$fok]['length_type'] = "attr-sm";
                }elseif($length > 12){
                    $f_attr[$fok]['length_type'] = "attr-lg";
                }
            }

            $at_list[$k]["attrs"] = $f_attr;
        }
        $this->assign("at_list",$at_list);

        $this->display('orderPopup');
    }

    //显示分类菜品信息
    public function showtypefood($type = 0){
        $food_category_relative = D('food_category_relative');
        $food = D('food');
        $condition['food_category_id'] = $type;
        $arr = $food_category_relative->where($condition)->select();
        //dump($arr);
        $food = D('food');
        $arrlist = array();
        //dump($arr);
        foreach ($arr as $v){
            // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

            $Model = M(); // 实例化一个model对象 没有对应任何数据表
            $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $v[food_id] and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end");

            if($num) {
                // 当天到目前为止消费数量
                $sum = 0;
                foreach ($num as $n) {
                    $sum += $n['num'];
                }
                // 查询出该food_id对应多少限额
                $fit_num = D("food")->where(array("food_id" => $v['food_id']))->getField("foods_num_day");
                if($sum >= $fit_num){
                    continue;
                }
            }

            $condition1['food_id'] = $v['food_id'];
            $condition1['restaurant_id'] = session("restaurant_id");
            $condition1['is_sale'] = 1;
            $result = $food->where($condition1)->find();
            if($result){
                if($result['is_prom'] == 1){
                    $prom = D('prom');
                    $where2['prom_id'] = $v['food_id'];
                    $when_time = time();
                    $where2['prom_start_time'] = array("lt",$when_time);
                    $where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
                    $prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
                    if($prom_price){
                        $result['food_price'] = $prom_price;
                    }else{
                        $result['food_price'] = $result['food_price'];
                    }
                }else{
                    $result['food_price'] = $result['food_price'];
                }
                // 该菜品是否有属性
                $have_attribute = $Model->query('SELECT COUNT(*)  AS total_num FROM attribute_type AS t1 INNER JOIN food_attribute AS t2 ON t1.attribute_type_id = t2.attribute_type_id WHERE food_id = '.$v['food_id']);
                $result['have_attribute'] = $have_attribute[0]['total_num'];
                $arrlist[] = $result;
            }
        }
        //dump($arrlist);
        $this->assign("info2", $arrlist);
        $this->display('orderAjax');
    }

    // 下单
    public function PlaceOrder(){
        $order = order();
        $order->startTrans();
        $e_arr = I('post.');

        $arr = array();
        foreach($e_arr as $e_k => $e_v){
            $temp['food_id'] = $e_v[0];
            $temp['food_num'] = $e_v[1];
            $temp['food_attr'] = str_replace("-","|",$e_v[2]);
            $temp['order_type'] = I("get.order_type");
            $arr[] = $temp;
        }

        $arraylist = array();       //单价数组
        $totallist = array();		//属性价数组
        $numberlist = array();		//份数数组

        $food = D('food');
        $food_attribute = D('food_attribute');

        foreach ($arr as $v) {
            $attlist = array();
            $food_attr_string = $v['food_attr'];
            $arr1 = explode('|', $food_attr_string, -1);

            foreach ($arr1 as $v1) {
                $condition['food_attribute_id'] = (int)$v1;
                $att = $food_attribute->where($condition)->field('attribute_price')->find();
                $att = $att['attribute_price'];
                $attlist[] = $att;
            }
            $atttotal = array_sum($attlist);

            $totallist[] = $atttotal;
            $where['food_id'] = $v['food_id'];
            $is_prom = $food->where($where)->field('is_prom')->find()['is_prom'];
            $foodlist = $food->where($where)->field('food_price')->find()['food_price'];
            if($is_prom == 1){
                $prom = D('prom');
                $where2['prom_id'] = $v['food_id'];
                $when_time = time();
                $where2['prom_start_time'] = array("lt",$when_time);
                $where2['prom_end_time'] = array("gt",$when_time);
                $prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
                $foodlist = $prom_price;
            }else{
                $foodlist = $foodlist;
            }
            $foodlist = $foodlist;
            $arraylist[] = (float)$foodlist;
            $numberlist[] = (int)$v['food_num'];
        }
        //var_dump($totallist);
        //var_dump($arraylist);
        //var_dump($numberlist);
        $aLen = count($totallist);
        $bLen = count($arraylist);
        if ($aLen > $bLen) {
            $len = $aLen;
        } else {
            $len = $bLen;
        }
        $c = array();
        for ($i = 0; $i < $len; $i++) {
            $c[] = $totallist[$i] + $arraylist[$i];
        }
        //var_dump($c);
        //单价与属性相加后的价格一维数组与数目相乘（对于坐标相乘）
        $dLen = count($c);
        $eLen = count($numberlist);
        if ($dLen > $eLen) {
            $len = $dLen;
        } else {
            $len = $eLen;
        }
        $f = array();
        for ($i = 0; $i < $len; $i++) {
            $f[] = $c[$i] * $numberlist[$i];
        }

        $foodtotal = array_sum($f);

        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $condition1['add_time'] = array("between",array($start,$end));
        $condition1['restaurant_id'] = session("restaurant_id");

        $num = $order->where($condition1)->count();
        $order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单

//        file_put_contents(__DIR__."/"."placeOrder_num.txt",'|订单号:'.$order_sn."||店铺ID：".session("restaurant_id")."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);

        $add_time = time();            //下单时间
        $total_amount = $foodtotal;         //订单总价
        $condition2['order_sn'] = $order_sn; //订单号
        $condition2['add_time'] = $add_time; //下单时间
        $condition2['total_amount'] = $total_amount;  //订单总价
        $condition2['original_price'] = $total_amount;  //订单原价
        $condition2['table_num'] = $arr[0]['table_num'] ? $arr[0]['table_num'] : 000;  //餐桌号
        $condition2['desk_code'] = session("desk_code");
        if(empty(session("desk_code"))) $condition2['is_reserve'] = 1;//是否为预定
        $condition2['restaurant_id'] = session("restaurant_id");
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();
        $condition2['restaurant_name'] = $restaurant_info['restaurant_name'];
        if($arr[0]['order_type']){
            $condition2['order_type'] = $arr[0]['order_type'];
        }else{
            $condition2['order_type'] = 1;
        }
        $condition2['terminal_order'] = 3;

        $condition2['related_user'] = $_SESSION['openid'];
        $result = $order->data($condition2)->add();

        if(!$result){
            $order->rollback();
//            exit;
            $returnData["code"] = 0;
            $returnData["msg"] = "下单失败（add_order）";
            exit(json_encode($returnData));
        }
        $order_food = order_F();
        $food = D('food');
        $condition3['order_id'] = $result;

        $order_food_attribute = order_F_A();
        foreach($arr as $v2){
            $attlist1 = array();
            $condition3['food_id'] = $v2['food_id'];
            $food1 = $food->where("food_id=".$v2['food_id'])->find();
            $condition3['food_name'] = $food1['food_name'];
            $condition3['food_num']	= $v2['food_num'];
            $food_attr_string1 = $v2['food_attr'];
            $arrz = explode('|', $food_attr_string1, -1);
            foreach ($arrz as $v1){
                $condition7['food_attribute_id'] = (int)$v1;
                $att1 = $food_attribute->where($condition7)->field('attribute_price')->find();
                $att1 = $att1['attribute_price'];
                $attlist1[] = $att1;
            }
            $atttotal1 = array_sum($attlist1);
            $condition3['food_price2']	= (float)$atttotal1+$food1['food_price'];

            $condition3['print_id']	= $food1['print_id'];
            $condition3['tag_print_id']	= $food1['tag_print_id'];
            $result1 = $order_food->add($condition3);
            if(!$result1){
                $order->rollback();
//                exit;
                $returnData["code"] = 0;
                $returnData["msg"] = "下单失败（add_orderFood）";
                exit(json_encode($returnData));
            }
            $food_attr_string1 = $v2['food_attr'];
            $arr2 = explode('|', $food_attr_string1, -1);
            if($arr2[0] != 0){
                foreach($arr2 as $v3){
                    if($v3 == 0){
                        $att1 = 0;
                        $att2 = 0;
                    }else{
                        $condition4['food_attribute_id'] = (int)$v3;
                        $att1 = $food_attribute->where($condition4)->field('attribute_name')->find();
                        $att1 = $att1['attribute_name'];
                        $att2 = $food_attribute->where($condition4)->field('attribute_price')->find();
                        $att2 = $att2['attribute_price'];
                    }
                    $p_condition5['food_attribute_id'] = (int)$v3;
                    $attr_id = $food_attribute->where($p_condition5)->field('attribute_type_id')->find()['attribute_type_id'];
                    if($attr_id){
                        $attribute_type_model = D("attribute_type");
                        $print_id = $attribute_type_model->where("attribute_type_id = $attr_id")->field("print_id")->find()['print_id'];
                        $count_type = $attribute_type_model->where("attribute_type_id = $attr_id")->field('count_type')->find()['count_type'];
                        $tag_print_id = $attribute_type_model->where("attribute_type_id = $attr_id")->field('tag_print_id')->find()['tag_print_id'];
                    }
                    $condition5['food_attribute_name'] = $att1;
                    $condition5['food_attribute_price'] = $att2;
                    $condition5['print_id'] = $print_id;
                    $condition5['count_type'] = $count_type;
                    $condition5['order_food_id'] = $result1;
                    $condition5['tag_print_id'] = $tag_print_id;
                    $condition5['num'] = $v2['food_num'];
                    $condition5['food_attribute_id'] = $v3;
                    $result2 = $order_food_attribute->add($condition5);
                    if(!$result2){
                        $order->rollback();
//                        exit;
                        $returnData["code"] = 0;
                        $returnData["msg"] = "下单失败（add_orderFoodAttr）";
                        exit(json_encode($returnData));
                    }
                }
            }

        }
        //var_dump($result);
        //var_dump($result1);
        //var_dump($result2);
        $rel = $order->commit();
        if($rel){
            $r_data["order_sn"] = $order_sn;
            $returnData["code"] = 1;
            $returnData["msg"] = "下单成功";
            $returnData['data'] = $r_data;
            exit(json_encode($returnData));
        }
    }
    /*
    *判断单店铺还是多店铺情况，配置session值，支付api需通过session读配置
    */
    private function isRestaurantType() {
        session("restaurant_id", cookie('restaurant_id'));//赋值店铺id
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();
        $business_info = $S_Restaurant->getBusinessInfo($restaurant_info['business_id']);
        session("wx_prepaid_flag", null);
        session("business_id", null);
        if($business_info['type'] == 1){//多店铺时读代理配置信息
            session("wx_prepaid_flag", 1);
            session("business_id", $restaurant_info['business_id']);
        }
    }
    /*
    *选择订单就餐时间
    */
    public function selectEatTime() {
        $order_sn = I("get.order_sn");
        $restaurant_id = order()->where(array('order_sn'=>$order_sn))->getField('restaurant_id');
        session("restaurant_id", $restaurant_id);
        cookie('restaurant_id', $restaurant_id, 1296000);//店铺id默认缓存15天
        $this->isRestaurantType();
        $S_order = new Order();
        $timeInfo = $S_order->getSetTimeInfo();
        if(empty($timeInfo['ext']) || !empty(session("desk_code"))) $this->redirect('Index/pay_old', 'order_sn='.$order_sn);
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        $jsApi = new \JsApi_pub();
        if (!isset($_GET['code']))
        {
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME")."/index.php/mobile/Index/selectEatTime/order_sn/".$order_sn);
            Header("Location: $url");
            exit;
        }
        //获取code码，以获取openid
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();
        $this->payConfigLoad($order_sn, $openid);
        // 过滤掉比当前小时：分钟小的数据
        foreach($timeInfo['ext'] as $key=>$val){
            if(strtotime($val['times']) < time()){
                unset($timeInfo['ext'][$key]);
            }
        }
        $this->assign('timeInfo', $timeInfo);
        $this->assign('order_sn', $order_sn);
        $this->display();
    }
    /*
    *修改订单信息
    */
    public function updateOrder() {
        $data = I();
        if(empty($data['use_time'])) $this->ajaxReturn(array('code'=>1, 'msg'=>'请选择使用时间'));
        $update_data = array(
            'use_day' => empty($data['use_day']) ? 1: $data['use_day'],
            'use_time' => $data['use_time']
        );
        $where['order_sn'] = $data['order_sn'];
        $S_order = new Order();
        $res = $S_order->updateInfo($where, $update_data);
        if($res) $this->ajaxReturn(array('code'=>0, 'msg'=>'操作成功', 'order_sn'=>$data['order_sn']));
        $this->ajaxReturn(array('code'=>1, 'msg'=>'操作失败'));
    }
    /*
    *微支付配置预加载
    */
    public function payConfigLoad($order_sn, $openid) {
        $S_Order = new Order();
        $where['order_sn'] = $order_sn;
        $order_info = $S_Order->getPrimInfo($where);
        session('restaurant_id', $order_info['restaurant_id']);
        session('desk_code', $order_info['desk_code']);
        $qrc_condition['restaurant_id'] = $order_info['restaurant_id'];
        $qrc_code_id = M("qrc_code")->where($qrc_condition)->getField("qrc_code_id");
        $qrcd_condition['qrc_code_id'] = $qrc_code_id;
        $device_code = M("qrc_device")->where($qrcd_condition)->getField('qrc_device_code');
        $S_Restaurant = new Restaurant();
        $restaurant_info = $S_Restaurant->getInfo();
        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("sub_openid", $openid);
        $unifiedOrder->setParameter("body", $restaurant_info['restaurant_name']);//商品描述
        $unifiedOrder->setParameter("out_trade_no", $order_sn);//商户订单号
        $unifiedOrder->setParameter("total_fee", $order_info['total_amount'] * 100);//总金额
        if($device_code){
            $unifiedOrder->setParameter("attach", $device_code);//机器码
        }
        $unifiedOrder->setParameter("notify_url", "http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/WxPay/notify");//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        //非必填参数，商户可根据实际情况选填
        $unifiedOrder->setParameter("sub_appid", \WxPayConf_pub::$SUB_APPID);//子商户号
        $unifiedOrder->setParameter("sub_mch_id", \WxPayConf_pub::$SUB_MCHID);//子商户号
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi = new \JsApi_pub();
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        $this->assign("jsApiParameters",$jsApiParameters);
        
    }
    // 支付页
    public function pay_old()
    {
        $order_sn = I("get.order_sn");
        $orderModel = order();
        $o_condition['order_sn'] = $order_sn;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        if(empty($rel)) $this->error("订单号错误~");
        $this->assign("order",$rel);

        session("restaurant_id", $rel['restaurant_id']);
        session("desk_code",$rel['desk_code']);
        cookie('restaurant_id', $rel['restaurant_id'], 1296000);//店铺id默认缓存15天

        $this->isRestaurantType();
        file_put_contents(__DIR__."/"."sesssion2.txt","sesion：".json_encode($_SESSION)."，cookie:".json_encode($_COOKIE)."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);

        /*************************微信支付处理***************************/
        //商户基本信息,可以写死在WxPay.Config.php里面，其他详细参考WxPayConfig.php
        vendor('weixinjsdk.WxPayPubHelper.WxPayPubHelper');
        //使用jsapi接口
        $jsApi = new \JsApi_pub();
        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
//        file_put_contents(__DIR__."/"."visit_num.txt",'|订单号:'.$order_sn."||店铺ID：".$restaurant_id."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
        if (!isset($_GET['code']))
        {
//            file_put_contents(__DIR__."/"."visit_num_set.txt",'no_set|订单号:'.$order_sn."||店铺ID：".$restaurant_id."||时间".date("Y-m-d H:i:s")."\r\n\r\n",FILE_APPEND);
            //触发微信返回code码
//            $url = $jsApi->createOauthUrlForCode("http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/Index/pay_old/order_sn/".$order_sn);
            $url = $jsApi->createOauthUrlForCode(C("HOST_NAME")."/index.php/mobile/Index/pay_old/order_sn/".$order_sn);
            Header("Location: $url");
            exit;
        }
        //获取code码，以获取openid
        $code = $_GET['code'];
        $jsApi->setCode($code);
        $openid = $jsApi->getOpenId();
        $this->payConfigLoad($order_sn, $openid);
        $restaurant = D('Restaurant');
        $condition['restaurant_id'] = $rel['restaurant_id'];
        $result = $restaurant->field('tplcolor_id')->find();
        $this->assign("tpl", $result);

        $orderModel = order();
        $o_condition['order_sn'] = $order_sn;
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn,desk_code,restaurant_id")->find();
        $this->assign("order",$rel);

        $this->display("pay_old");
    }

    public function getIP() /*获取客户端IP*/
    {
        if (@$_SERVER["HTTP_X_FORWARDED_FOR"])
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else if (@$_SERVER["HTTP_CLIENT_IP"])
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        else if (@$_SERVER["REMOTE_ADDR"])
            $ip = $_SERVER["REMOTE_ADDR"];
        else if (@getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (@getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (@getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else
            $ip = "Unknown";
        return $ip;
    }

    // 改变堂吃外带类型
    public function change_order_type()
    {
        $order_sn = I('post.order_sn');
        $order_type = I('post.order_type');
        $res = order()->where(array('order_sn'=>$order_sn))->save(array('order_type'=>$order_type));
        if($res !== false){
            $return['code'] = 1;
            $return['msg'] = '成功';
            exit(json_encode($return));
        }else{
            $return['code'] = 0;
            $return['msg'] = '成功';
            exit(json_encode($return));
        }
    }
}


