<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/4
 * Time: 14:50
 */
namespace Home\Controller;
use Think\Controller;

class ModuleController extends Controller
{
    /**
     * 获取菜品的分类
     */
    public function foodCategory(){
        $restaurant_id = session("restaurant_id");
        $r_condition['restaurant_id'] = $restaurant_id;
        $r_condition['is_timing'] = 0;
        $foodCategoryModel = D("food_category");
        $foodCategoryList = $foodCategoryModel->where($r_condition)->select();

        $returnData = array();
        if($foodCategoryList){
            $returnData['code'] = 1;
            $returnData['msg'] = "操作成功";
            $returnData['data'] = $foodCategoryList;
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            $returnData['data'] = "没有数据";
        }
        exit(json_encode($returnData));
}

    /**
     * 获取菜品分类的相应的菜品，默认获取所有的菜品
     */
    public function foodList(){
        $foodCategoryId = I("post.foodCategoryId");
        $foodCategoryModel = D("food_category_relative");
        if($foodCategoryId){
            $fc_condition['foodCategoryId'] = session("foodCategoryId");
            $foodIds = $foodCategoryModel->where($fc_condition)->select();
        }else{
            $foodIds = $foodCategoryModel->select();
        }
        $f_condition['restaurant_id'] = session("restaurant_id");

        $foodList = array();
        $foodModel = D('food');
        foreach($foodIds as $key => $val){
            $f_condition['food_id'] = $val['food_id'];
            $food = $foodModel->field("food_id,food_img,food_price,star_level,food_name")->find();
            $foodList[] = $food;
        }
        $returnData = array();
        if($foodList){
            $returnData['code'] = 1;
            $returnData['msg'] = "操作成功";
            $returnData['data'] = $foodList;
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            $returnData['data'] = "没有数据";
        }
        exit(json_encode($returnData));
    }

    /**
     * 获取某个菜品的详细信息(菜品id,菜品名称，菜品名称，菜品价格，菜品描述，菜品可选的类别属性。)
     */
    public function foodInfo(){
        //获取post过来的菜品id
        $foodId = I("post.food_id");
        $f_condition["food_id"] = $foodId;
        $foodModel = D('food');
        $foodInfo = $foodModel->where($f_condition)->find();

        //获取菜品的可选属性类别
        $foodAttrTypeModel = D("attribute_type");
        $foodAttrTypes = $foodAttrTypeModel->where($f_condition)->field("attribute_type_id,select_type")->select();
//        dump($foodAttrTypes);

        $foodAttrModel = D('food_attribute');

        foreach($foodAttrTypes as $key => $val){
            $at_condition["attribute_type_id"] = $val['attribute_type_id'];
            $foodAttrTypes[$key]['foodAttrType'] = $foodAttrModel->where($at_condition)->field("food_attribute_id,attribute_name,attribute_price")->select();
        }

        $foodInfo[] = $foodAttrTypes;

        $returnData = array();
        if($foodInfo){
            $returnData['code'] = 1;
            $returnData['msg'] = "操作成功";
            $returnData['data'] = $foodInfo;
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "操作失败";
            $returnData['data'] = "没有数据";
        }
        exit(json_encode($returnData));

    }
}