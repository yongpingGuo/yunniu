<?php
namespace Home\Controller;
use Think\Cache\Driver\Memcache;
use Think\Controller;
use Think\Storage\Driver\File;




//use Think\jpush;

class IndexController extends Controller
{
	private $is_security = false;

	//盘点该机器是否可用
	public function isLogin(){
//		dump(session('restaurant_id'));
		//从cookie中获取设备的机器码
		$device_code = cookie("device_code");
//		var_dump($device_code);
		/**
		 * 如果机器码不存在，则提示非法访问
		 */
//		$device_code = "28:f3:66:5c:1c:07";
		if($device_code == false){
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
//			dump($code_id);
			if($code_id){
				/**
				 * 机器码绑定的机器码存在，查看注册码的剩余时间是否大于0；
				 */
				$codeModel = D("code");
				$c_condition['code_id'] = $code_id;
				$codeInfo = $codeModel->where($c_condition)->find();
//				dump($codeInfo);

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
					$restaurant_id = session("restaurant_id");
//					dump($restaurant_id);
					if(!$restaurant_id){
//						echo 111;
						session("restaurant_id",$codeInfo['restaurant_id']);
					}
					//dump(session("restaurant_id"));
					$cc_data['last_time'] = time();
					$rel = $codeModel->where($c_condition)->save($cc_data);
					return $this->is_security = true;
				}
			}else{
				return $this->is_security = false;
			}
		}
	}


    # 广告页
	public function index()
	{
		if ($this->is_security){
			$this->is_security = false;		
			$restaurant = D('Restaurant');
			$condition1['restaurant_id'] = session('restaurant_id');
			$time = $restaurant->where($condition1)->field('advertise_time')->find();
			$times = $time['advertise_time']*1000;
			$this->assign("time",$times);
			$advertisement = D('advertisement');
			$condition['advertisement_type'] = 0;
			$condition['restaurant_id'] = session('restaurant_id');;
			$data = $advertisement->where($condition)->select();
			$this->assign("info",$data);
			$this->display("index");

		}else{
			$this->processRoute();
		}
	}

	/**
	 *客户端点餐页面
	 * 
	 * 
	 */
	public function order(){
		if ($this->is_security){
        	
			$this->is_security = false;
			
			$tableNum = I("get.tablenum");
			$orderType = I("get.order_type");       # 在店享用或者打包带走，在select.html那边传递过来
			if($tableNum){
				session("tableNum",$tableNum);
			}
			if($orderType){
				session("orderType",$orderType);
			}
			
			//判断选择餐牌号的页面是否开启
			$restaurant_process_model = D("restaurant_process");
			$condition["process_id"] = 4;    //级别大于当前流程页        # 4代表选择餐牌号的页面
			$condition["restaurant_id"] = session("restaurant_id");
			$isOpenNum = $restaurant_process_model->where($condition)->field("process_status")->find()['process_status'];
			session("isOpenNum",$isOpenNum);

	//-------------------------------------------菜品分类信息---------------------------------------
			$food_category = D('food_category');
			$category_time = D('category_time');
			$condition['restaurant_id'] = session('restaurant_id');
			$condition['is_timing'] = 0;        # 是否定时
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
            # 条件是当前时间要在开始时间和结束时间之内 并且分类要开启了定时   获取它的分类ID
			$category_ids = $category_time->where($t_condition)->distinct("category_id")->field("category_id")->select();
			if($category_ids){					//存在时间段定时记录(当前店铺)
				$category_id_list = array();
				foreach ($category_ids as $k => $v) {   # 将分类ID再遍历出来，放到另外一个数组里面
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
			$this->assign("info", $arr);        # 将合并未开启定时与开启了定时且符合条件的菜品分类结果分配到模板中

	//--------------------------------------------菜品信息-----------------------------------------			
			$food = D('food');
			$food_category_relative = D('food_category_relative');      # 食物和食物分类关联表
			if($arr){                                 //如果存在菜品分类
				$foodIdArr = array();				  //存在菜品分类所对应的菜品信息集合
				foreach($arr as $vinfo){
					$where1['food_category_id'] = $vinfo['food_category_id'];
					$foodIdList = $food_category_relative->where($where1)->field('food_id')->select();		#　在食物与食物分类表中根据分类ＩＤ查询食物ＩＤ
                    foreach($foodIdList as $fil){
                        // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
                        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
                        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

                        $Model = M(); // 实例化一个model对象 没有对应任何数据表
                        $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $fil[food_id] and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end");


                        if($num){
//                            dump($fil['food_id']);
                            $sum = 0;
                            foreach($num as $n){
                                $sum += $n['num'];
                            }
//                            dump($sum);

                            // 查询出该food_id对应多少限额
                            $fit_num = D("food")->where(array("food_id"=>$fil['food_id']))->getField("foods_num_day");
                            // dump($fit_num);
                            if($sum < $fit_num){
                                $foodIdArr[] = $fil['food_id'];     # 将食物ID放到一个数组里面
                            }
                           //  dump($num);
                        }else{
                            $foodIdArr[] = $fil['food_id'];     # 将食物ID放到一个数组里面
                        }
	 				}
				}

                // p($foodIdArr);
				if($foodIdArr){						  //如果存在菜品信息集合
					$f_condition['is_sale'] = 1;	  //1:上架
					$f_condition['food_id'] = array("in",$foodIdArr);

					$arr1 = $food->where($f_condition)->order('sort asc')->select();

					$prom = D('prom');				  //处理时价，若菜品在时价范围内，前端显示时价时的价格
					foreach($arr1 as $k1=>$v1){
						if($v1['is_prom'] == 1){	  //1：开启时价
							$where2['prom_id'] = $v1['food_id'];    #　时价id = 食物id
							$when_time = time();
							$where2['prom_start_time'] = array("lt",$when_time); 
							$where2['prom_end_time'] = array("gt",$when_time);//   prom_start_time<when_time<prom_end_time
							$prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
							if($prom_price){		  //如果存在符合条件的时价
								$food_price = $prom_price;      #　食物的价格就等于时价
							}else{
								$food_price = $v1['food_price'];    #　食物的价格就等于自身原价
							}
						}else{
							$food_price = $v1['food_price'];
						}
						$arr1[$k1]['food_price'] = $food_price;
					}
				}
			}
			$this->assign("info1", $arr1);
			/*$Restaurant = D('Restaurant');
			$condition['restaurant_id'] = session("restaurant_id");
			$result = $Restaurant->where($condition)->field('tplcolor_id')->find();
			$this->assign("tpl",$result);*/
			$this->assign("tpl",change_telcolor());

            $this->display("order");

		}else{
			$this->overdue();
			exit;
		}
	}

	//自定义路由
	public function processRoute()
	{
		$this->is_security = true;
		$this->isLogin();

		if(!$this->is_security){
			$this->overdue();
			exit;
		};

		/**
		 * 获取餐厅的流程
		 */
		//获取餐厅当前的流程
		$current_process = I("get.process");            //当前流程页
		$processModel = D("process");                    //创建一个流程的model

		$sort = 0;
		if ($current_process) {
			$condition2['process_url'] = $current_process;
			$result = $processModel->where($condition2)->field("process_id,sort")->find();      # 当前流程页的详情
			if($result){
				$sort = $result["process_id"];  # 排序 = 当前流程页的id
			}else{
				$this->overdue();
				exit;
			}
		}

		$restaurant_process_model = D("restaurant_process");    # 餐厅流程页
		$condition["process_id"] = array(array("gt", $sort),array("neq",4),"and");    //级别大于当前流程页          # 并且不等于4（餐牌号页）
		$condition["process_status"] = 1;                //流程页状态为开启状态
		$condition["restaurant_id"] = session("restaurant_id");


		$restaurant_next_process = $restaurant_process_model->where($condition)->order("process_id")->find();   # 下一个流程

		$process_id = $restaurant_next_process["process_id"];       # 下一个流程的ID
		$next_process = "";

		$condition3["process_id"] = $restaurant_next_process["process_id"];
        # 获得下一个流程的url  流程在process里面是固定了顺序的，首先是广告页，然后就进来了路由分发，再下一个流程下一个流程这样
		$next_process = $processModel->where($condition3)->field("process_url")->find()['process_url'];
		$this->$next_process();     # 跳转到下一个流程
	}

	public function perfect(){
		echo "低缓页面";
	}

	//就餐方式选择页
	public function select(){
		if ($this->is_security) {
			$this->is_security = false;                 # 防止非法进入
			$this->assign("tpl",change_telcolor());     # 改变模板颜色   此方法在公共函数里面，根据当前餐馆ID获取对应的模板颜色，然后在选择页以隐藏域形式传递过去
			$this->display("select");
		} else {
			$this->overdue();
			exit;
		}
	}

	//餐桌号选择页
	public function number()
	{
		if ($this->is_security) {
			$this->is_security = false;
			$this->assign("tpl",change_telcolor());
			$this->display("number");
		} else {
			$this->overdue();
		}
	}

	//支付页
	public function pay()
	{
		if ($this->is_security) {
			$this->is_security = false;
			$this->assign("tpl",change_telcolor());

			$orderModel = order();
			$o_condition['order_sn'] = I("get.order_sn");
			$rel = $orderModel->where($o_condition)->field("total_amount,order_sn")->find();

			$pay_select_model = D('pay_select');
			$ps_condition['restaurant_id'] = session('restaurant_id');
			$pay_select_config = $pay_select_model->where($ps_condition)->select();

			$this->assign("pay_select",$pay_select_config);
			$this->assign("order",$rel);
			$this->display("pay");
		} else {
			$this->overdue();
		}
	}

	//支付完成页
	public function finish(){
		$restaurant = D('Restaurant');
		$this->assign("tpl",change_telcolor());
		$condition['restaurant_id'] = session("restaurant_id");

		$result1 = $restaurant->where($condition)->field('adv_language')->find()['adv_language'];
		$this->assign("adv_lang",$result1);
		$this->display("finish");
	}

	//获取订单状态,打印状态
	public function getOrderStatus(){
		//查找订单状态
		$order_sn = I("post.order_sn");

		$orderModel = order();
		$o_condition['order_sn'] = $order_sn;
		$order = $orderModel->where($o_condition)->find();
		$order_status = $order['order_status'];

		if($order_status == 3){
			$data['code'] = 1;
			$data['msg'] ='支付成功';
			exit(json_encode($data));
		}
	}

	//显示分类菜品信息
	public function showtypefood($type = 0){
		$food_category_relative = D('food_category_relative');
		$food = D('food');
		$condition['food_category_id'] = $type;         # 传递过来的菜品ID
		$arr = $food_category_relative->where($condition)->select();    # 查询“食物分类与食物表”

        $shuzu = array();
        foreach($arr as $a){
            $shuzu[] = $a['food_id'];
        }

        $tiaojian['food_id'] = array("in",$shuzu);
        $all = D("food")->where($tiaojian)->order('sort asc')->select();
        $shuzu1 = array();
        foreach($all as $a1){
            $shuzu1[] = $a1['food_id'];
        }


        $food = D('food');
        $arrlist = array();
        foreach ($shuzu1 as $v){
            // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

            $Model = M(); // 实例化一个model对象 没有对应任何数据表
            $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $v and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end");

            if($num) {
                // 当天到目前为止消费数量
                $sum = 0;
                foreach ($num as $n) {
                    $sum += $n['num'];
                }
                // 查询出该food_id对应多少限额
                $fit_num = D("food")->where(array("food_id" => $v))->getField("foods_num_day");
                if($sum >= $fit_num){
                    continue;
                }
            }

            $condition1['food_id'] = $v;
            $condition1['restaurant_id'] = session("restaurant_id");
            $condition1['is_sale'] = 1;
            $result = $food->where($condition1)->find();
            if($result){
                if($result['is_prom'] == 1){
                    $prom = D('prom');
                    $where2['prom_id'] = $v;
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

		/*$food = D('food');
		$arrlist = array();
		foreach ($arr as $v){
            // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
            $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
            $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

            $Model = M(); // 实例化一个model对象 没有对应任何数据表
            $num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $v[food_id] and t2.order_status in ('3','11','12')
                        and t2.pay_time between $start and $end");

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
				$arrlist[] = $result;
			}
		}*/
		$this->assign("info2", $arrlist);
		$this->display('orderAjax');
	}

	//加载模态框
	public function findfoodinfo(){
        // 获取food_id供加载静态文件使用
        $food_id = I('get.food_id');

		$food = D('food');
		$condition['food_id'] = I('get.food_id');	
		$food_price = $food->where($condition)->field('food_price')->find()['food_price'];
		$is_prom = $food->where($condition)->field('is_prom')->find()['is_prom'];
		$prom = D('prom');
		if($is_prom == 1){
			$where2['prom_id'] = I('get.food_id');
			$when_time = time();										   //判断当前时间是否在时价的范围内
			$where2['prom_start_time'] = array("lt",$when_time); 
			$where2['prom_end_time'] = array("gt",$when_time);             //prom_start_time<when_time<prom_end_time
			$prom_price = $prom->where($where2)->field('prom_price')->find()['prom_price'];
			if($prom_price){			
				$food_price = $prom_price;
			}else{
				$food_price = $food_price;
			}
		}else{
			$food_price = $food_price;
		}	
		$this->assign("food_price",$food_price);
			
		//$arr = $food->where($condition)->field("food_id,food_name,food_img,food_price,food_desc")->find();
		$arr = $food->where($condition)->field("food_id,food_name,food_img,food_desc")->find();
		$this->assign("info3", $arr);

		$attribute_type = D('attribute_type');
		$at_condition['food_id'] = $arr['food_id'];
		$at_list = $attribute_type->where($at_condition)->field('attribute_type_id,type_name,select_type')->select();
		$food_attribute = D('food_attribute');

		foreach ($at_list as $k => $v){
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
		// dump($at_list);die();
		$this->assign("at_list",$at_list);
//		exit;

		  $this->display('orderPopup');
	}

	public function PlaceOrder(){
		$order = order();
		$order->startTrans();//开启事务
		$e_arr = I('post.');          //拿到右边记录列表数组

		$arr = array();
		foreach($e_arr as $e_k => $e_v){
			$temp['food_id'] = $e_v[0];
			$temp['food_num'] = $e_v[1];
			$temp['food_attr'] = str_replace("-","|",$e_v[2]);
			$temp['order_type'] = session("orderType");
			$arr[] = $temp;
		}

//		print_r($arr);
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
		$condition2['table_num'] = session("tableNum") ? str_pad(session('tableNum'),3,"0",STR_PAD_LEFT) : 000;  //餐桌号
		$condition2['restaurant_id'] = session('restaurant_id');
		if($arr[0]['order_type']){
			$condition2['order_type'] = $arr[0]['order_type']; //用餐方式
		}else{
			$condition2['order_type'] = 1;
		}
		$condition2['terminal_order'] = 1; //点餐方式，终端区别
		//dump($condition2);
		$result = $order->data($condition2)->add();//增加一条订单
		if(!$result){
			$order->rollback();
			exit;
		}

		session("tableNum",null);
		session("orderType",null);

		$order_food = order_F();
		$food = D('food');
		$condition3['order_id'] = $result;
		//$result1list = array();
		$order_food_attribute = order_F_A();
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
			$condition3['district_id']	= $food1['district_id'];
			$result1 = $order_food->add($condition3);
			if(!$result1){
				$order->rollback();
				exit;
			}

			$food_attr_string1 = $v2['food_attr'];
			$arr2 = explode('|', $food_attr_string1, -1);

			if($arr2[0] != 0){
				foreach($arr2 as $v3){
					//var_dump($v3);
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
	
	public function TableNumber(){
		$numberTable = $_POST['number'];
//		var_dump($numberTable);
		$this->assign('number',$numberTable);
		$this->display('Index/order');
		
	}

	public function verifyImg(){
		$verify = new \Think\Verify();
		$verify->entry();
	}


	private function overdue(){
		$this->display("overdue");
	}

	//现金支付
	public function jpushCashPay(){;
		$device_code = cookie("device_code");
		$order_sn = I("post.order_sn");

		//查询订单信息
		$orderModel = order();
		$where['order_sn'] = $order_sn;
		$order = $orderModel->where($where)->field('order_id,restaurant_id')->find();

		//设置订单的支付方式为现金支付，订单状态为未支付
		$data['order_id'] = $order['order_id'];
		$data['order_status'] = 1;
		$data['pay_type'] = 3;

		//为该订单确定一个支付号
		$start_day = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$end_day = mktime(23,59,59,date('m'),date('d'),date('Y'));
		$c_condition['restaurant_id'] = $order['restaurant_id'];
		$c_condition['add_time'] = array("between",array($start_day,$end_day));
		$c_condition['pay_num'] = array("neq",0);
		$pay_num = $orderModel->where($c_condition)->count();
		$data['pay_num'] = str_pad($pay_num+1,3,"0",STR_PAD_LEFT);
		//保存订单信息
		$r = $orderModel->save($data);
		if($r){
			$msg['msg'] = "成功";
			$msg['code'] = 1;
			exit(json_encode($msg));
		}else{
			$msg['msg'] = "失败";
			$msg['code'] = 0;
			exit(json_encode($msg));
		}

	}

	public function setTableNum(){
		$tableNum = I("post.tableNum");
		session("tableNum",$tableNum);
		if(session("tableNum")){
			$msg["code"] = 1;
			$msg["msg"] = "成功";
			exit(json_encode($msg));
		}
	}
}