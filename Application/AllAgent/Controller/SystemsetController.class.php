<?php
namespace AllAgent\Controller;
use Think\Controller;
class SystemsetController extends Controller{
	public function __construct(){
        Controller::__construct();
        if(!session("manager_id")){
            $this->redirect("login");
        }
    }
	
	//系统设置，设备时间年限显示
	public function show_renew(){
		$renew = D('renew');
		$renewArr = $renew->where("id=1")->find();
		$this->assign("renewArr",$renewArr);
		$this->display('renew');
	}
	
	//系统设置，设备时间年限修改
	public function update_renew(){
		if($_POST){
			$renew = D('renew');
			$data['renew_time1'] = I('post.renew_time1');
			$data['renew_time2'] = I('post.renew_time2');
			$data['renew_time3'] = I('post.renew_time3');
			$r = $renew->where("id=1")->save($data);
			if($r){
				$renewArr = $renew->where("id=1")->find();
				$this->assign("renewArr",$renewArr);
				$this->display('renew');
			}
		}
	}


    /*********图标管理*****************/
    //系统设置，图标管理
    public function ico_manager(){
        $ico_category = D('ico_category');
        $first_category = $ico_category->order('id')->select();
        // 如果分类为空则跳转到添加分类
        if(empty($first_category)){
            $this->redirect("Systemset/ico_category");
        }

        $this->assign('first_category',$first_category);
       // 拼接下拉选项
        $options = '';
        foreach($first_category as $key=>$val){
            $options .="<option value=".$val['id'].">".$val['category_name']."</option>";
        }
        $this->assign('options',$options);

        // 第一个元素对应的id
        $second_category_id = $first_category[0]['id'];
        if($second_category_id != null){
            $where['relation_category_id'] = $second_category_id;
            $data =  D('ico_manager')->where($where)->find();
        }else{
            $data = [];
        }

        if($data){
            // 编辑
            $this->assign('data',$data);
            $this->display('ico_index');
        }else{
            // 新增
            $this->display();
        }
    }

    // 处理添加
    protected function _add()
    {
       /* Array
        (
            [second_lever] => 4
            [img_id] =>
            [_rootpath] => /Public/Uploads/ICO
            [photo] => ,2017-08-21/599a4ce7cc893.png,2017-08-21/599a4ce8cab85.png,2017-08-21/599a4ce8d3567.png
        )*/
        $_POST['relation_category_id'] = I('post.second_lever');
        $second_lever = I('post.second_lever');
        unset($_POST['second_lever']);
        unset($_POST['img_id']);
        // 删除百度上传插件中新增的一个文件域
        unset($_FILES['file']);
        $ico_manager = D('ico_manager');

        // 创建数据并入库(goods 商品基本信息)
        if($ico_manager->create())
        {
            if($id = $ico_manager->add())
            {
                $this->_show();
                $return = $this->_show($transport_img_id=$id);
                // 让其处于选中状态
                $this->assign('second_category_id',$second_lever);
                // 当前图片信息
                $this->assign('data',$return['data']);
                // 二级选项的详细信息
                $this->assign('options',$return['options']);
                $this->display('ico_index');
                exit;
            }else
            {
                $this->error('添加失败，原因为：' . $ico_manager->getDbError());
            }
        }else
        {
            $this->error('添加失败，原因为：'  . $ico_manager->getError());
        }
    }

    // 添加和编辑完后的跳转
    public function ico_index(){
        // IS_POST，添加或者编辑完后的逻辑处理（包括ico_manager.html,ico_index.html两个页面）
        if(IS_POST){
            $img_id = I('post.img_id');
            if($img_id){
                // 编辑
                $where['img_id'] = $img_id;
                $res =  D('ico_manager')->save(I('post.'));
                $second_lever = I('post.second_lever');
                $return = $this->_show($transport_img_id=$img_id);
                // 让其处于选中状态
                $this->assign('second_category_id',$second_lever);
                // 当前图片信息
                $this->assign('data',$return['data']);
                // 二级选项的详细信息
                $this->assign('options',$return['options']);
                $this->display('ico_index');
                exit;
            }else{
                // 新增的处理
                $this->_add();
                exit;
            }
        }

        // 非IS_POST，则是新增方法（_add()）处理完后跳转过来的
        $return = $this->_show();
        // 让其处于选中状态
        $this->assign('second_category_id',I('get.second_lever'));
        // 当前图片信息
        $this->assign('data',$return['data']);
        // 二级选项的详细信息
        $this->assign('options',$return['options']);
        $this->display('ico_index');
    }

    public function _show($transport_img_id=0){
        $img_id = I('get.img_id') ? : $transport_img_id;
        $ico_category = D('ico_category');
        $first_category = $ico_category->order('id')->select();
        // 拼接下拉选项
        $options = '';
        if(!empty($first_category)){
            foreach($first_category as $key=>$val){
                $options .="<option value=".$val['id'].">".$val['category_name']."</option>";
            }
        }

        // 图片信息回显
        $where['img_id'] = $img_id;
        $data =  D('ico_manager')->where($where)->find();
        $arr['data'] = $data;
        $arr['options'] = $options;
        return $arr;
    }

    // 删除相册中的某一张图片
    public function delPhoto($img_id = 0 , $raw = '')
    {
        $path = trim($_POST['_rootpath'], '/') . '/';
        // 删除指定图片
        @unlink($path . $raw);

        // 删除规格图
        @unlink( $path . get_thumb($raw, 'sma'));

        // 更新对应的记录的photo字段
        if($img_id)
        {
            // photo字段中的当前相片的地址替换为空
            $data['photo'] = array('exp',"replace(photo, ',$raw', '')");
            // 根据条件保存修改的数据
            M('ico_manager')->where("img_id='$img_id'")->save($data);
        }
    }

    // 在删除图片前判断是否允许删除图片
    public function if_can_del(){
        $find = I('post.raw');
        $all_food_cate = D('food_category')->field('img_url')->select();
        $data = 0;  // 允许删除

        foreach($all_food_cate as $key=>$val){
            $res = strpos($val['img_url'], $find);
            if($res !== false){
                // 说明有菜品分类在使用此菜品分类图标
                $data = 1;  // 不允许删除
                break;
            }
        }
        $this->ajaxReturn($data);
    }


    // 插件点击开始上传后调用的多文件上传，并输出上传了的文件名到隐藏域，用作提交到写进数据库的处理
    public function mulUpload()
    {
        // file_put_contents( uniqid() . '.txt', var_export($_POST, true) . var_export($_FILES, true) );

        // 调用函数实现上传
        $res = upload();

        // 输出子目录及文件名
        echo date('Y-m-d') . '/' .$res['file']['savename'];
    }

    // 点击第二级的时候，获取相应的图片信息
    public function photo_change(){
        $photo_info = D('ico_manager')->where(array('relation_category_id'=>I('get.second_level_id')))->find();
        exit(json_encode($photo_info));
    }

    /**************添加分类开始************/
    public function ico_category(){
        $ico_category = D('ico_category');
        if(IS_POST){
            // 创建数据并入库
            if($ico_category->create())
            {
                if($ico_category->add())
                {
                    $return['code'] = 1;
                    $return['msg'] = '新增成功';
                    exit(json_encode($return,JSON_UNESCAPED_UNICODE));
                }else
                {
                    $return['code'] = 0;
                    $return['msg'] = '新增失败，请重试';
                    exit(json_encode($return,JSON_UNESCAPED_UNICODE));
                }
            }else
            {
                $return['code'] = 1;
                $return['msg'] = '新增失败，请重试';
                exit(json_encode($return,JSON_UNESCAPED_UNICODE));
            }
        }
       $this->display();
    }

    // 添加的分类名是否相同
    public function catefory_name_if_same(){
        $if_same = D('ico_category')->where(array('category_name'=>I('post.category_name')))->find();
        if($if_same){
            $return['code'] = 1;
            $return['msg'] = '存在相同分类名';
        }else{
            $return['code'] = 2;
            $return['msg'] = '不存在相同分类名';
        }
        exit(json_encode($return,JSON_UNESCAPED_UNICODE));
    }
    /************添加分类结束**************/
}
