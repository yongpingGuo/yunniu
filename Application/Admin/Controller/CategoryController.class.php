<?php
namespace Admin\Controller;
use data\service\Category;

/*
*菜时分类
*/
class CategoryController extends BaseController
{
    private $S_Category;
    public function __construct() {
        parent::__construct();
        $this->S_Category = new Category();
    }
    public function index() {
        $list = $this->S_Category->getList();
        $this->assign('list', $list);
        $this->display();
    }
    /*
    *菜时分类添加
    */
    public function timeAdd() {
        $data = I();
        if(empty($data['food_timcate_name'])) $this->ajaxReturn(array('code'=>0, 'msg'=>'请输入分类名'));
        $res = $this->S_Category->timeAdd($data);
        if($res) $this->ajaxReturn(array('code'=>1, 'msg'=>'添加成功'));
        $this->ajaxReturn(array('code'=>0, 'msg'=>'添加失败'));
    }
    /*
    *删除
    */
    public function del() {
        $food_time_category_id = intval(I("food_time_category_id"));
        $res = $this->S_Category->del($food_time_category_id);
        if($res) $this->ajaxReturn(array('code'=>1, 'msg'=>'删除成功'));
        $this->ajaxReturn(array('code'=>0, 'msg'=>'删除失败'));
    }
    /*
    *获取基本信息
    */
    public function getInfo() {
        $food_time_category_id = intval(I("food_time_category_id"));
        $info = $this->S_Category->getInfo($food_time_category_id);
        if($info) $this->ajaxReturn(array('code'=>1, 'msg'=>$info));
        $this->ajaxReturn(array('code'=>0, 'msg'=>'获取失败'));
    }
    /*
    *修改菜时分类信息
    */
    public function timeUpdate() {
        $data = I();
        if(empty($data['food_timcate_name'])) $this->ajaxReturn(array('code'=>0, 'msg'=>'请输入分类名'));
        $res = $this->S_Category->timeUpdate($data);
        if($res) $this->ajaxReturn(array('code'=>1, 'msg'=>'修改成功'));
        $this->ajaxReturn(array('code'=>0, 'msg'=>'没有任何修改'));
    }
}
