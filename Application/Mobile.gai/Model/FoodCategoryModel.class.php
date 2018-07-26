<?php
namespace Mobile\Model;
use Think\Model;
	
	class FoodCategoryModel extends Model{
        // 得到菜品分类和菜品信息
        public function getAllFoodInfo(){
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

            // 返回所需数据
            $return_data['info'] = $arr;
            $return_data['food_infos'] = $food_infos;
            return $return_data;
        }

        // 菜品分类下的菜品，用于懒加载
        public function layzLoad($food_category_id){
            $condition['food_category_id'] = $food_category_id;
            $food_category_relative = D('food_category_relative');
            $arr = $food_category_relative->where($condition)->select();
            $food = D('food');
            $arrlist = array();
            foreach ($arr as $v){
                // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
                $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
                $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

                $Model = M(); // 实例化一个model对象 没有对应任何数据表
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
                    $have_attribute = $Model->query('SELECT COUNT(*)  AS total_num FROM attribute_type AS t1 RIGHT JOIN food_attribute AS t2 ON t1.attribute_type_id = t2.attribute_type_id WHERE t1.food_id = '.$v['food_id']);
                    $result['have_attribute'] = $have_attribute[0]['total_num'];
                    $arrlist[] = $result;
                }
            }
            return $arrlist;
        }
	}
?>