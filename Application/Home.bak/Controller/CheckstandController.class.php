<?php
namespace Home\Controller;
use Think\Controller;

class CheckstandController extends Controller{
	private $is_security = false;

	public function isLogin(){
		//从cookie中获取设备的机器码
		$device_code = I("cookie.device_code");

		$cashier_id = session("cashier_id");

		if(!$cashier_id){
			$this->redirect("Checkstand/login");
			exit;
		}else{
			$cashierModel = D("cashier");
			$cashier_condition['cashier_id'] = $cashier_id;

			$cashier_restaurant_id = $cashierModel->where($cashier_condition)->field("restaurant_id")->find()['restaurant_id'];

			if($cashier_restaurant_id != session("restaurant_id")){
				$this->redirect("Checkstand/login");
				exit;
			}
		}

		/**
		 * 如果机器码不存在，则提示非法访问
		 */
		if($device_code == ""){
			return $this->is_security = false;
		}else{
			$deviceModel = D("device");
			$d_condition['device_code'] = $device_code;
			$deviceInfo = $deviceModel->where($d_condition)->field("code_id,device_status")->find();
			$code_id = $deviceInfo['code_id'];
			if(!$code_id){
				//注册码过期或者已经删除;
				exit("注册码过期或者已经删除");
			}
			$device_status = $deviceInfo['device_status'];
			if(!$device_status){
				//该机器已经被禁用;
				exit("该机器已经被禁用");
			}
			if($code_id){
				/**
				 * 机器码绑定的机器码存在，查看注册码的剩余时间是否大于0；
				 */

				$codeModel = D("code");
				$c_condition['code_id'] = $code_id;
				$codeInfo = $codeModel->where($c_condition)->find();

				$code_restTimestamp = $codeInfo['rest_timestamp'];

				$oldTime = $codeInfo['last_time'];
				if($codeInfo['last_time'] == 0){
					$oldTime = time();
				}


				$currentTime = time();
				session('login_time',$currentTime);

				$code_restTimestamp = $code_restTimestamp-($currentTime-$oldTime);

				$c_data['rest_timestamp'] = $code_restTimestamp;
				$codeModel->where($c_condition)->save($c_data);

				if($code_restTimestamp < 0){
					return $this->is_security = false;
				}else{
					$cc_data['last_time'] = time();
					$codeModel->where($c_condition)->save($cc_data);
					$restaurant_id = session("restaurant_id");
					if(!$restaurant_id){
						session("restaurant_id",$codeInfo['restaurant_id']);
					}
					return $this->is_security = true;
				}
			}else{
				return $this->is_security = false;
			}
		}
	}

	public function index()
	{
		$this->isLogin();
		if ($this->is_security){
			$this->is_security = false;

			//判断选择餐牌号的页面是否开启
			$restaurant_process_model = D("restaurant_process");
			$condition["process_id"] = 4;    //级别大于当前流程页
			$condition["restaurant_id"] = session("restaurant_id");
			$isOpenNum = $restaurant_process_model->where($condition)->field("process_status")->find()['process_status'];
			$this->assign("isOpenNum",$isOpenNum);

			//-------------------------------------------菜品分类信息---------------------------------------
			$food_category = D('food_category');
			$category_time = D('category_time');
			$condition['restaurant_id'] = session('restaurant_id');
			$condition['is_timing'] = 0;
			$arr = $food_category->where($condition)->order('sort asc')->select();	//首先查詢未設置定時的菜品分類

			$where['restaurant_id'] = session('restaurant_id');
			$where['is_timing'] = 1;
			$food_categoryIdList =  $food_category->where($where)->field('food_category_id')->select();//然後查詢有定時，且時間段符合當前時間的分類ID
			if($food_categoryIdList){     				//如果有分类开启了定时
				$food_categoryNewIdList = array();		//开启定时的菜品分类ID集合（当前店铺）
				foreach($food_categoryIdList as $foodvv){
					$food_categoryNewIdList[] = $foodvv['food_category_id'];
				}

				//第一种时间段的定时查询
				$current_time = time();
				$t_condition['time1'] = array("lt",$current_time);
				$t_condition['time2'] = array("gt",$current_time);//           time1<$current_time<time2
				$t_condition['category_id'] = array("in",$food_categoryNewIdList);
				$category_ids = $category_time->where($t_condition)->distinct("category_id")->field("category_id")->select();
				if($category_ids){					//存在时间段定时记录(当前店铺)
					$category_id_list = array();
					foreach ($category_ids as $k => $v) {
						$index = "cid" . $v['category_id'];
						$category_id_list[$index] = $v['category_id'];
					}
				}
				//第二种星期段的定时查询
				$current_week = date("w");
				$ftg_condition['timing_day'] = array("like", "%" . $current_week . "%");
				$ftg_condition['food_category_id'] = array("in",$food_categoryNewIdList);
				$category_timing_model = D("food_category_timing");
				$category_ids2 = $category_timing_model->where($ftg_condition)->distinct("food_category_id")->field("food_category_id,start_time,end_time")->select();
				$category_id_list2 = array();
				if($category_ids2){					//存在星期段定时记录(当前店铺)
					foreach ($category_ids2 as $kk => $vv) {
						$start_time = strtotime($vv['start_time']);
						$end_time = strtotime($vv['end_time']);
						if($start_time < $current_time && $end_time > $current_time){
							$index = "cid" . $vv["food_category_id"];
							$category_id_list2[$index] = $vv["food_category_id"];
						}
					}
				}

				//两种定时情况结果合并
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
				if($lastCategoryIdsList){                 //存在两种情况合并的结果,查询出开启定时且符合条件的分类集合
					$l_condition['food_category_id'] = array("in", $lastCategoryIdsList);
					$arr2 = $food_category->where($l_condition)->order('sort asc')->select();
					$arr = array_merge($arr, $arr2);      //合并未开启定时与开启了定时且符合条件的菜品分类结果
				}
			}
			$this->assign("info", $arr);

			//--------------------------------------------菜品信息-----------------------------------------
			$food = D('food');
			$food_category_relative = D('food_category_relative');
			if($arr){                                 //如果存在菜品分类
				$foodIdArr = array();				  //存在菜品分类所对应的菜品信息集合
				foreach($arr as $vinfo){
					$where1['food_category_id'] = $vinfo['food_category_id'];
					$foodIdList = $food_category_relative->where($where1)->field('food_id')->select();
					foreach($foodIdList as $fil){
						$foodIdArr[] = $fil['food_id'];
					}
				}
				if($foodIdArr){						  //如果存在菜品信息集合
					$f_condition['is_sale'] = 1;	  //1:上架
					$f_condition['food_id'] = array("in",$foodIdArr);
					$arr1 = $food->where($f_condition)->order('sort asc')->select();
					$prom = D('prom');				  //处理时价，若菜品在时价范围内，前端显示时价时的价格
					foreach($arr1 as $k1=>$v1){
						if($v1['is_prom'] == 1){	  //1：开启时价
							$where2['prom_id'] = $v1['food_id'];
							$when_time = time();
							$where2['prom_start_time'] = array("lt",$when_time);
							$where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
							$prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
							if($prom_price){		  //如果存在符合条件的时价
								$food_price = $prom_price;
							}else{
								$food_price = $v1['food_price'];
							}
						}else{
							$food_price = $v1['food_price'];
						}
						$arr1[$k1]['food_price'] = $food_price;
					}
				}
			}
			$this->assign("info1", $arr1);
			$this->assign("tpl",change_telcolor());

			$this->display('order');
		}else{
			$this->redirect("Checkstand/login");
			exit;
		}
	}

	public function pay()
	{
		$this->isLogin();
		if($this->is_security) {
			$this->is_security = false;
			$this->assign("tpl",change_telcolor());
			$this->display("pay");
		} else {
			$this->redirect("Checkstand/login");
		}
	}

	public function finish(){
		$restaurant = D('Restaurant');
		$this->assign("tpl",change_telcolor());
		$condition['restaurant_id'] = session('restaurant_id');
		$result1 = $restaurant->where($condition)->field('adv_language')->find()['adv_language'];
		//dump($result1);
		$this->assign("adv_lang",$result1);
		$this->display("finish");
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
				$arrlist[] = $result;
			}
		}
		//dump($arrlist);
		$this->assign("info2", $arrlist);
		$this->display('orderAjax');
	}


	//加载模态框
	public function findfoodinfo(){
		$food = D('food');
		$condition['food_id'] = I('get.food_id');
		$is_prom = $food->where($condition)->field('is_prom')->find()['is_prom'];
		$food_price = $food->where($condition)->field('food_price')->find()['food_price'];
		$prom = D('prom');
		if($is_prom == 1){
			$where2['prom_id'] = I('get.food_id');
			$when_time = time();
			$where2['prom_start_time'] = array("lt",$when_time); 
			$where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
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
		
		
		//$arr = $food->where($condition)->field("food_id,food_name,food_img,food_price,food_desc")->find();
		$arr = $food->where($condition)->field("food_id,food_name,food_img,food_desc")->find();
		$this->assign("info3", $arr);
//		dump($arr);

		$attribute_type = D('attribute_type');
		$at_condition['food_id'] = $arr['food_id'];
		$at_list = $attribute_type->where($at_condition)->field('attribute_type_id,type_name,select_type')->select();
		$food_attribute = D('food_attribute');

		foreach ($at_list as $k => $v) {
			$fa_condition['attribute_type_id'] = $v['attribute_type_id'];
			$f_attr = $food_attribute->where($fa_condition)->field("food_attribute_id,attribute_name,attribute_price")->select();

				foreach($f_attr as $fok => $fov){
				$length = strlen($fov["attribute_name"]);
				if($length <= 15){
					$f_attr[$fok]['length_type'] = "attr-sm";
				}elseif($length > 15){
					$f_attr[$fok]['length_type'] = "attr-lg";
				}
			}

			$at_list[$k]["attrs"] = $f_attr;
		}
//		dump($at_list);
		$this->assign("at_list",$at_list);
//		exit;
		$this->display('orderPopup');
	}

	public function PlaceOrder(){
		$order = D('order');
		$order->startTrans();//开启事务
		$e_arr = I('post.');          //拿到右边记录列表数组


		I("get.order_type") ? $order_type = I("get.order_type") : $order_type = 1;
		I("get.tableNum") ? $tableNum = I("get.tableNum") : $tableNum = 0;

		$arr = array();
		foreach($e_arr as $e_k => $e_v){
			$temp['food_id'] = $e_v[0];
			$temp['food_num'] = $e_v[1];
			$temp['food_attr'] = str_replace("-","|",$e_v[2]);
			$temp['order_type'] = $order_type;
			$arr[] = $temp;
		}

		$arraylist = array();       //单价数组
		$totallist = array();		//属性价数组
		$numberlist = array();		//份数数组

		$food = D('food');
		$food_attribute = D('food_attribute');

		foreach ($arr as $v) {//$arr右边多条记录，一条一条遍历
			$attlist = array();    //储存属性价格（一维数组）
			$food_attr_string = $v['food_attr'];
			$arr1 = explode('|', $food_attr_string, -1);//将属性以|分割成PHP一维数组

			foreach ($arr1 as $v1) {    //将属性一维数组遍历查询对应属性的价格
				$condition['food_attribute_id'] = (int)$v1;//
				//var_dump($condition);
				$att = $food_attribute->where($condition)->field('attribute_price')->find();
				$att = $att['attribute_price'];
				$attlist[] = $att;//将查询出的对应价格存进数组
			}
			$atttotal = array_sum($attlist);    //php、array_sum()可以将一维数组的值相加得到属性总价
			//unset($attlist);       //释放内存
			$totallist[] = $atttotal;     //购物车每条菜品记录的属性总和
			$condition['food_id'] = $v['food_id'];
			$is_prom = $food->where($condition)->field('is_prom')->find()['is_prom'];
			$foodlist = $food->where($condition)->field('food_price')->find()['food_price'];
			if($is_prom == 1){
				$prom = D('prom');
				$where2['prom_id'] = $v['food_id'];
				$when_time = time();
				$where2['prom_start_time'] = array("lt",$when_time); 
				$where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
				$prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
				if($prom_price){
					$foodlist = $prom_price;
				}
			}else{
				$foodlist = $foodlist;
			}
				
			$foodlist = $foodlist;
			//var_dump($foodlist);
			$arraylist[] = (float)$foodlist;   //购物车每条菜品记录的单价
			$numberlist[] = (int)$v['food_num'];   //购物车每条菜品记录的份数
		}
		//var_dump($totallist);
		//var_dump($arraylist);
		//var_dump($numberlist);
		//单价一维数组与属性总价一维数组相加（对于坐标相加）
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
		//var_dump($f);
		$foodtotal = array_sum($f);
		//var_dump($foodtotal);
		//将查出来的对像Object，依次添加进数组里，形成二维数组
		//var_dump($arraylist);
		$start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
		$end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
		$condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
		$condition1['restaurant_id'] = session("restaurant_id");     //店铺id

		$num = $order->where($condition1)->count();        //两时间之间的订单数
		$order_sn = "DC".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单

		$add_time = time();            //下单时间
		$total_amount = $foodtotal;         //订单总价
		$condition2['order_sn'] = $order_sn; //订单号
		$condition2['add_time'] = $add_time; //下单时间
		$condition2['total_amount'] = $total_amount;  //订单总价
		$condition2['table_num'] = $tableNum ? $tableNum : 000;  //餐桌号
		$condition2['restaurant_id'] = session('restaurant_id');
		if($arr[0]['order_type']){
			$condition2['order_type'] = $arr[0]['order_type']; //用餐方式
		}else{
			$condition2['order_type'] = 1;
		}
		$condition2['terminal_order'] = 2; //点餐方式，终端区别
		//dump($condition2);
		$result = $order->data($condition2)->add();//增加一条订单
		if(!$result){
			$order->rollback();
			exit;
		}
		$order_food = D('order_food');
		$food = D('food');
		$condition3['order_id'] = $result;
		//$result1list = array();
		$order_food_attribute = D('order_food_attribute');
		foreach($arr as $v2){
			$attlist1 = array();    //储存属性价格（一维数组）
			$condition3['food_id'] = $v2['food_id'];
			$food1 = $food->where("food_id=".$v2['food_id'])->find();
			$condition3['food_name'] = $food1['food_name'];
			$condition3['food_num']	= $v2['food_num'];
			$food_attr_string1 = $v2['food_attr'];
			$arrz = explode('|', $food_attr_string1, -1);//将属性以|分割成PHP一维数组
			foreach ($arrz as $v1){    //将属性一维数组遍历查询对应属性的价格
				$condition7['food_attribute_id'] = (int)$v1;//
				//var_dump($condition);
				$att1 = $food_attribute->where($condition7)->field('attribute_price')->find();
				$att1 = $att1['attribute_price'];
				$attlist1[] = $att1;//将查询出的对应价格存进数组
			}
			$atttotal1 = array_sum($attlist1);
			$condition3['food_price2']	= (float)$atttotal1+$food1['food_price'];
			$result1 = $order_food->add($condition3);
			if(!$result1){
				$order->rollback();
				exit;
			}
			$food_attr_string1 = $v2['food_attr'];
			$arr2 = explode('|', $food_attr_string1, -1);

			if($arr2[0] != 0){
				foreach($arr2 as $v3){
					if($v3 == 0){
						$att1 = 0;
						$att2 = 0;
					}else{
						$condition4['food_attribute_id'] = (int)$v3;//
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
					}
					$condition5['food_attribute_name'] = $att1;
					$condition5['food_attribute_price'] = $att2;
					$condition5['print_id'] = $print_id;
					$condition5['count_type'] = $count_type;
					$condition5['order_food_id'] = $result1;
					$result2 = $order_food_attribute->add($condition5);
					if(!$result2){
						$order->rollback();
						exit;
					}
				}
			}

		}

		$rel = $order->commit();
		if($rel){
			$r_data["order_sn"] = $order_sn;
			$returnData["code"] = 1;
			$returnData["msg"] = "下单成功";
			$returnData['data'] = $r_data;
			exit(json_encode($returnData));
		}
	}

	public function order_list(){
		$order = D('order');

		$condition['order_status']  = array('neq',10);
		$condition['restaurant_id']  =session('restaurant_id');;
		$count = $order->where($condition)->count();
		$p = I('p') ? I('p'): 1;
		$pageNum = 5;
		$Page = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page -> setConfig('first','首页');
		$Page -> setConfig('last','共%TOTAL_PAGE%页');
		$Page -> setConfig('prev','上一页');
		$Page -> setConfig('next','下一页');
		$Page -> setConfig('link','indexpagenumb');
		$Page -> setConfig('theme','%FIRST%  %UP_PAGE%  %LINK_PAGE%  %DOWN_PAGE%  %END%');
		$show = $Page->show();// 分页显示输出
		$arr = $order->where($condition)->page($p,$pageNum)->select();
		$order_food = D('order_food');
		$food = D('food');
		foreach($arr as $k => $a){
			$arrlist = array();
			$condition['order_id'] = $a['order_id'];
			$arr1 = $order_food->where($condition)->select();
			$arrlist = array();
			$arrlist1 = array();
			$arrlist2 = array();
			foreach($arr1 as $a1){
				$arrlist[] = $a1['food_name'];
				$arrlist1[] = $a1['food_price2'];
				$arrlist2[] = $a1['food_num'];
				//dump($arr2['food_name']);
			}
			$arr[$k]['namelist'] =$arrlist;
			$arr[$k]['pricelist'] = $arrlist1;
			$arr[$k]['numlist'] = $arrlist2;
			//dump($arr1);
		}


		$this->assign('page',$show);// 赋值分页输出
		$this->assign("info",$arr);

		$restaurant = D('Restaurant');
		$condition['restaurant_id'] = 1;
		$result = $restaurant->field('tplcolor_id')->find();
		$this->assign("tpl",$result);
		$this->display();


	}

	//分页或去餐桌的信息
	public function deskInfo(){
		$order = D('order');

		$condition['order_status']  = array('neq',10);
		$pp = I("get.page");
		$p = I("get.page") ? I("get.page") : 1;
		$count = $order->where($condition)->count();
		$p = I("get.page") ? I("get.page") : 1;
		$pageNum = 5;
		$page = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
		$show = $page->show();// 分页显示输出
		$arr = $order->where($condition)->page($p,$pageNum)->select();
		$order_food = D('order_food');
		$food = D('food');
		foreach($arr as $k => $a){
			$arrlist = array();
			$condition['order_id'] = $a['order_id'];
			$arr1 = $order_food->where($condition)->select();
			$arrlist = array();
			$arrlist1 = array();
			$arrlist2 = array();
			foreach($arr1 as $a1){
				$arrlist[] = $a1['food_name'];
				$arrlist1[] = $a1['food_price2'];
				$arrlist2[] = $a1['food_num'];
				//dump($arr2['food_name']);
			}
			$arr[$k]['namelist'] =$arrlist;
			$arr[$k]['pricelist'] = $arrlist1;
			$arr[$k]['numlist'] = $arrlist2;
			//dump($arr1);
		}


		$this->assign('page',$show);// 赋值分页输出
		$this->assign("info",$arr);

		$restaurant = D('Restaurant');
		$condition['restaurant_id'] = 1;
		$result = $restaurant->field('tplcolor_id')->find();
		$this->assign("tpl",$result);
		if($pp == ""){
			$this->display('order_list');
		}else{
			$this->display('order_list2');
		}

	}


	public function showorderbykey(){
		//dump(I('get.typeid'));
		$order = D('order');
		if(I('get.typeid')==10){
			$condition['order_status']  = array('neq',10);
			$condition['restaurant_id'] = session('restaurant_id');
			$arr = $order->where($condition)->select();
		}else{
			$condition['order_type'] = I('get.typeid');
			$condition['order_status']  = array('neq',10);
			$condition['restaurant_id'] = session('restaurant_id');
			$arr = $order->where($condition)->select();
		}
		$order_food = D('order_food');
		$food = D('food');
		foreach($arr as $k => $a){
			$arrlist = array();
			$condition['order_id'] = $a['order_id'];
			$arr1 = $order_food->where($condition)->select();
			$arrlist = array();
			$arrlist1 = array();
			$arrlist2 = array();
			foreach($arr1 as $a1){
				$arrlist[] = $a1['food_name'];
				$arrlist1[] = $a1['food_price2'];
				$arrlist2[] = $a1['food_num'];
				//dump($arr2['food_name']);
			}
			$arr[$k]['namelist'] =$arrlist;
			$arr[$k]['pricelist'] = $arrlist1;
			$arr[$k]['numlist'] = $arrlist2;
			//dump($arr1);
		}

		//dump($arr);
		$this->assign("info",$arr);
		$this->display('order_list2');
	}


	public function showorderbykey1(){
		//dump(I('get.typeid'));
		$order = D('order');
		if(I('get.typeid')==10){
			$condition['order_status']  = array('neq',10);
			$condition['restaurant_id'] = session('restaurant_id');
			$arr = $order->where($condition)->select();
		}else{
			$condition['terminal_order'] = I('get.typeid');
			$condition['order_status']  = array('neq',10);
			$condition['restaurant_id'] = session('restaurant_id');
			$arr = $order->where($condition)->select();
		}
		$order_food = D('order_food');
		$food = D('food');
		foreach($arr as $k => $a){
			$arrlist = array();
			$condition['order_id'] = $a['order_id'];
			$arr1 = $order_food->where($condition)->select();
			$arrlist = array();
			$arrlist1 = array();
			$arrlist2 = array();
			foreach($arr1 as $a1){
				$arrlist[] = $a1['food_name'];
				$arrlist1[] = $a1['food_price2'];
				$arrlist2[] = $a1['food_num'];
				//dump($arr2['food_name']);
			}
			$arr[$k]['namelist'] =$arrlist;
			$arr[$k]['pricelist'] = $arrlist1;
			$arr[$k]['numlist'] = $arrlist2;
			//dump($arr1);
		}

		//dump($arr);
		$this->assign("info",$arr);
		$this->display('order_list2');
	}


	//收银员删除订单，实际数据库未删除订单，只是将订单状态更改为10，10已删除
	public function updatestatu(){
		$order = D('order');
		$condition['order_id'] = I('post.id');
		$condition['order_status'] = 10;
		$a = $order->save($condition);
		if($a){
			$msg['msg'] = "删除成功!";
			$msg['data'] = 0;
		}else{
			$msg['msg'] = "删除失败!";
			$msg['data'] = 1;
		}
		exit(json_encode($msg));

	}

	//收银员登录
	public function checklogin(){
		$cashier = D('cashier');
		$condition['Cashier_phone'] = I('post.phone');
		$condition['Cashier_pwd'] = I('post.pwd');
		$r = $cashier->where($condition)->find();
		//dump($r);
		if($r){
			session('names',$r['cashier_name']);
			$msg['msg'] = "登录成功";
			$msg['data'] = 1;
		}else{
			$msg['msg'] = "登录失败";
			$msg['data'] = 0;
		}
		exit(json_encode($msg));
	}

	//收银员统计订单
	public function ordercount(){
		$order = D('order');
		$result = $order->count();
		if($result){
			$msg['msg'] = "统计成功！";
			$msg['data'] = $result;
		}else{
			$msg['msg'] = "统计失败！";
			$msg['data'] = 0;
		}

		exit(json_encode($msg));
	}

	//验证登录
	public function check(){
		$cashier = D('cashier');
		if(I("remember")){//先判断登录时是否有构选记住我
			$phone = cookie('v_phone');
			$pwd = cookie('v_pwd');
			$value = cookie();
//			dump($value);
			//exit;
			if(!empty($phone) && !empty($pwd)){//判断是否有cookie值，如有则为记住我登录
				$condition['cashier_phone'] = I('phone');
				$object = $cashier->where($condition)->find();
				if($object['cashier_pwd'] == I('pwd')){
					session("names",$object['cashier_name']);
					session("cashier_id",$object['cashier_id']);
					session("restaurant_id",$object['restaurant_id']);
					
					$condition1['restaurant_id'] = $object['restaurant_id'];
					$restaurant_manager = D('restaurant_manager');
					$id = $restaurant_manager->where($condition1)->field('id')->find()['id'];
					session("re_admin_id",$id);
					
					
					$this->redirect('index');
//					dump(session('restaurant_id'));
				}else{
					$this->error("密码错误!");
				}
			}else{//没有cookie值则为，有记住我功能的输入登录
				$condition['cashier_phone'] = I('phone');
				$object = $cashier->where($condition)->find();
				if($object['cashier_pwd'] == I('pwd')){
					session("restaurant_id",$object['restaurant_id']);
					session("names",$object['cashier_name']);
					session("cashier_id",$object['cashier_id']);
						
					$condition1['restaurant_id'] = $object['restaurant_id'];
					$restaurant_manager = D('restaurant_manager');
					$id = $restaurant_manager->where($condition1)->field('id')->find()['id'];
					session("re_admin_id",$id);
					
					
					//把用户名存入cookie，退出登录后在表单保存用户名信息
					cookie('v_phone',I('phone'));
					cookie('v_pwd',I('pwd'));
					$this->redirect('index');
				}else{
					$this->error("密码错误!");
				}

			}
		}else{//没记住我功能的输入登录，
			cookie('v_phone',null); 
			cookie('v_pwd',null);
			$condition['cashier_phone'] = I('phone');
			$object = $cashier->where($condition)->find();
			if($object['cashier_pwd'] == I('pwd')){
				session("names",$object['cashier_name']);
				session("cashier_id",$object['cashier_id']);
				session("restaurant_id",$object['restaurant_id']);
				
				$condition1['restaurant_id'] = $object['restaurant_id'];
				$restaurant_manager = D('restaurant_manager');
				$id = $restaurant_manager->where($condition1)->field('id')->find()['id'];
				session("re_admin_id",$id);
					
				$this->redirect('index');
			}else{
				$this->error("密码错误!");
			}
		}

	}

	//收银员退出
	public function checkout(){
		session('cashier_id',null);
		$this->redirect('login');//
	}

	public function login(){
		$restaurant_id = session('restaurant_id');
		if(!$restaurant_id){
			$device_code = cookie("device_code");
			$deviceModel = D("device");
			$d_condition['device_code'] = $device_code;
			$deviceInfo = $deviceModel->where($d_condition)->field("code_id,device_status")->find();
			$code_id = $deviceInfo['code_id'];
			$codeModel = D("code");
			$c_condition['code_id'] = $code_id;
			$codeInfo = $codeModel->where($c_condition)->find();
			$restaurant_id = $codeInfo['restaurant_id'];
			session("restaurant_id",$restaurant_id);
		}
		//$restaurant_id = 1;
		$cashier = D('cashier');
		$arr = $cashier->where("restaurant_id=$restaurant_id")->select();
		$this->assign("cashierinfo",$arr);
		//dump($arr);

		$phone = cookie('v_phone');
		$pwd = cookie('v_pwd');
		if(!empty($phone) && !empty($pwd)){//判断是否有cookie值，
			$this->assign("phone",$phone);
			$this->assign("pwd",$pwd);
			$this->assign("status",1);
			$this->display();
		}else{
			$this->assign("status",0);
			$this->display();
		}
		//$this->display();
	}

	public function jpushOpenCashTable(){
		$device_code = cookie("device_code");
		$order_sn = I("post.order_sn");

		$order = D('order');
		$where['order_sn'] = $order_sn;
		$order_id = $order->where($where)->field('order_id')->find()['order_id'];
		//dump($order_id);
		$data['order_id'] = $order_id;
		$data['pay_type'] = 0;
		$data['pay_time'] = time();
		$data['order_status'] = 3;
		$r = $order->save($data);

		//获取订单信息，判断是否要推送到展示餐牌号展示页面
		$orderInfo = $order->where($where)->field("table_num,desk_code,restaurant_id")->find();
		$restaurantModel = D("Restaurant");
		$rr_condition['restaurant_id'] = $orderInfo['restaurant_id'];
		$show_device_code = $restaurantModel->where($rr_condition)->field("show_num_d")->find()['show_num_d'];
		if($orderInfo['table_num'] == 0 && $orderInfo['desk_code'] == 0){
			$content['tips'] = "下单成功推送showNum";
			$contentJson = json_encode($content);
			$post_data = array ("type" => "publish","to" => $show_device_code,"content" => $contentJson);
			$rel2 = sendMsgToDevice($post_data);
		}

		if($r){
			$msg['msg'] = "成功";
			$msg['order_sn'] = $order_sn;
			$msg['code'] = 1;
			exit(json_encode($msg));
		}
	}

	public function jpushPayForNum(){
		$orderModel = D('order');
		$pay_num = I("post.pay_num");
		$restaurant_id = session("restaurant_id");

		$o_order['pay_num'] = str_pad($pay_num,3,"0",STR_PAD_LEFT);
		$o_order['restaurant_id'] = $restaurant_id;
		$start_time = mktime(0,0,0,date('m'),date("d"),date('Y'));
		$end_time = mktime(23,59,59,date('m'),date("d"),date('Y'));
		$o_order['add_time'] = array("between",array($start_time,$end_time));
		$order= $orderModel->where($o_order)->field("order_sn,order_status,total_amount")->find();

		$order_sn = $order['order_sn'];
		$order_status = $order['order_status'];
		if($order_status == 3){
			$msg['msg'] = "已支付";
			$msg['code'] = 0;
			exit(json_encode($msg));
		}

		if($order_sn){
				$msg['msg'] = "成功";
				$msg['order_sn'] = $order['order_sn'];
				$msg['total'] = $order['total_amount'];
				$msg['code'] = 1;
				exit(json_encode($msg));
		}else{
			$msg['msg'] = "支付号不存在";
			$msg['code'] = 0;
			exit(json_encode($msg));
		}
	}

	//获取订单状态
	public function getOrderStatus(){
		$order_sn = I("post.order_sn");
//		dump($order_sn);
		$orderModel = D("order");
		$o_condition['order_sn'] = $order_sn;
		$order = $orderModel->where($o_condition)->find();
		$order_status = $order['order_status'];
//		dump($order_status);
		if($order_status == 3){
			$data['code'] = 1;
			$data['msg'] ='支付成功';
			exit(json_encode($data));
		}
	}
	
	//收银员云登录后台
	public function admin_login(){
		$restaurant_id = session("restaurant_id");
		$restaurant_manager = D('restaurant_manager');
		$condition['restaurant_id'] = $restaurant_id;
		$login_account = $restaurant_manager->where($condition)->field('login_account')->find()['login_account'];
		$this->assign("login_account",$login_account);
		
		$re_admin_id = cookie("re_admin_id");
		if($re_admin_id){
			$this->assign("password",cookie("password"));
			$this->assign("autoFlag",1);
		}
		$this->display();
	}


}
?>