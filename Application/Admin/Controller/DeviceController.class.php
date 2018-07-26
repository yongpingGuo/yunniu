<?php
namespace Admin\Controller;
use Think\Controller;
class DeviceController extends Controller {
    private $is_qrc = false;

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

    public function index(){
        /**
         * 获取登录店铺的所有设备（机器名称，机器码，到期日期，状态【开启/关闭】）
         */
      //  $condition['restaurant_id'] = session("restaurant_id"); //restaurant_id 通过session获得
      	
		$code = D('code');
		$condition['restaurant_id'] = session('restaurant_id');
		$codeArr = $code->where($condition)->select();
		//dump($codeArr);
		$device = D('device');
		$deviceArr = array();
		foreach($codeArr as $v){
			$where['code_id'] = $v['code_id'];
			$deviceObject = $device->where($where)->find();
			if($deviceObject){
				$deviceArr[] = $deviceObject;
			}
		}
        // var_dump($deviceArr);exit();
        foreach ($deviceArr as $k => $v) {
            foreach ($codeArr as $key => $value) {
                if ($v['code_id'] == $value['code_id']) {
                    $deviceArr[$k]['end_time'] = date('Y-m-d',$v['end_time']);
                    $deviceArr[$k]['last_time'] = date('Y-m-d H:i:s',$value['last_time']);
                }
            }
          
        }
		//dump($deviceArr);
		$this->assign('device_list',$deviceArr);
        $this->display();
		//dump(count($deviceArr));
        //$count = $device_list = $deviceModel->where($condition)->count();
       /* $count = count($deviceArr);
        $p = I('p') ? I('p'): 1;
//        var_dump($p);
        $pageNum = 2;
        $Page  = new \Think\Page($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page -> setConfig('first','首页');
        $Page -> setConfig('last','共%TOTAL_PAGE%页');
        $Page -> setConfig('prev','上一页');
        $Page -> setConfig('next','下一页');
        $Page -> setConfig('link','indexpagenumb');
        $Page -> setConfig('theme','%FIRST%  %UP_PAGE%  %LINK_PAGE%  %DOWN_PAGE%  %END%');
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $device_list = $deviceModel->where($condition)->page($p,$pageNum)->select();*/
       
    }

    //创建桌面二维码
    public function createDesk(){
        $restaurant_id = session("restaurant_id");
        $condition['restaurant_id'] = $restaurant_id;
        $qrcModel = D("qrc_code");
        $qrc_code_info = $qrcModel->where($condition)->find();

        //判断是否拥有创建餐桌二维码权限
        if($qrc_code_info){
            $qrc_condition['qrc_code_id'] = $qrc_code_info['qrc_code_id'];
            $qrc_device_model = D("qrc_device");
            $rel = $qrc_device_model->where($qrc_condition)->find();
            if($rel){
                $this->is_qrc = true;
            }
        }

        //当is_qrc为真时创建餐桌二维码
        if($this->is_qrc){
            //获取餐店的id
//        $restaurant_id = session('restaurant_id');
            $restaurant_id = session('restaurant_id');

            //获取post过来的餐桌号
            $desk_code = I("post.desk_code");

            //生成餐桌二维码
            Vendor('phpqrcode.phpqrcode');

            $url = "http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/index/index/restaurant_id/".$restaurant_id."/desk_code/"."$desk_code";
            $errorCorrectionLevel =intval(3) ;//容错级别
            $matrixPointSize = intval(4);//生成图片大小

            //生成二维码图片
            //echo $_SERVER['REQUEST_URI'];
            $object = new \QRcode();
            $date = date("Y-m-d/",time());
            $date2 = date("His",time());
            $path = "./Application/Admin/Uploads/qrcode/".$date;
            if(!is_readable($path)){
                is_file($path) or mkdir($path,0700);
            }

            //url要关联desk_id，方便修改。
            $img_path = $path.$date2.".png";
            $object->png($url,$img_path, $errorCorrectionLevel, $matrixPointSize, 2);

            //构造餐桌资料添加进数据库
            $data['desk_code'] = $desk_code;
            $data['restaurant_id'] = $restaurant_id;
            $data['code_img'] = "/Application/Admin/Uploads/qrcode/".$date.$date2.".png";
            $data['qrcode_url'] = "/Application/Admin/Uploads/qrcode/".$date.$date2.".png";
            $desk_model = D('desk');

            $result = $desk_model->data($data)->add();

            if($result !== false){
                unset($desk_model);
                $msg['code'] = 1;
                $msg['msg'] = "成功";
                exit(json_encode($msg));
            }else{
                unlink($img_path);
                unset($desk_model);

                $msg['code'] = 0;
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
        }else{
            $msg['code'] = 0;
            $msg['msg'] = "没有权限";
            exit(json_encode($msg));
        }
    }

    //分页获取餐桌的信息
    public function deskInfo(){
    	$restaurant = D('restaurant');
		$restaurant_id = session('restaurant_id');
        $condition['restaurant_id'] = $restaurant_id;
		$wx_order_title = $restaurant->where($condition)->field('wx_order_title')->find()['wx_order_title'];	
		$this->assign("wx_order_title",$wx_order_title);		//微信端order页title
		
		//判断该餐厅是否有开通餐桌二维码点餐
		$qrcModel = D("qrc_code");
		$qrc_code_info = $qrcModel->where($condition)->find();
		if($qrc_code_info){
			$qrc_condition['qrc_code_id'] = $qrc_code_info['qrc_code_id'];
			$qrc_device_model = D("qrc_device");
			$rel = $qrc_device_model->where($qrc_condition)->find();
			if($rel){
				$this->assign("qrc_order",1);
			}
		}
		
		
        $deskModel = D('desk');
        $pp = I("get.page");
        $p = I("get.page") ? I("get.page") : 1;
        $count = $deskModel->where($condition)->count();
        $page_num = 10;
        $page = new \Think\PageAjax($count,$page_num);
        $deskInfo = $deskModel->where($condition)->page($p,$page_num)->order("desk_id desc")->select();
        $this->assign('deskInfo',$deskInfo);
        $page2 = $page->show();
        $this->assign('page',$page2);
        if($pp == ""){
            $this->display('table');
        }else{
            $this->display('ajaxDeskInfo');
        }
    }

    //下载餐桌二维码
    public function downloadImg(){
        $imgPath = I("get.imgPath");

        $htp = $_SERVER['HTTP_HOST'];//获取当前的服务器名


        $dir =  "http://$htp";//获取当前站点根URL

        $picurl= $dir.$imgPath;  //这里记得用绝对路径才可以。

        header("Content-Disposition: attachment; filename=".basename($picurl));
        readfile($picurl);
    }

    public function delDesk(){
        $desk_id = I("post.desk_id");
        $condition['desk_id'] = $desk_id;

        $desk_model = D('desk');

        $img_path = $desk_model->where($condition)->field("code_img")->select()[0]['code_img'];
        $img_path = ".".$img_path;

//        var_dump($img_path);
//        exit;
        $result = $desk_model->where($condition)->delete();
        if($result !== false){
            unlink($img_path);
            unset($desk_model);
            $msg['code'] = 1;
            $msg['msg'] = "成功";
            exit(json_encode($msg));
        }else{
            unset($desk_model);
            $msg['code'] = 0;
            $msg['msg'] = "失败";
            exit(json_encode($msg));
        }
    }

    public function editDesk(){
//        var_dump($_POST);
        $desk_model = D("desk");
        $data = $desk_model->create();

        $condition['desk_id'] = $data['desk_id'];
        $code_img = $desk_model->where($condition)->field("code_img")->find()['code_img'];
        $restaurant_id = session('restaurant_id');

        //获取post过来的餐桌号
        $desk_code = I("post.desk_code");

        //生成餐桌二维码
        Vendor('phpqrcode.phpqrcode');
        $url = "http://".$_SERVER["HTTP_HOST"]."/index.php/mobile/index/index/restaurant_id/".$restaurant_id."/desk_code/"."$desk_code";
        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(4);//生成图片大小

        //生成二维码图片2
        $object = new \QRcode();
//        dump($code_img);
        $object->png($url,".".$code_img, $errorCorrectionLevel, $matrixPointSize, 2);

        $result = $desk_model->save($data);

        if($result !== false){
            unset($desk_model);
            $msg['code'] = 1;
            $msg['msg'] = "成功";
            exit(json_encode($msg));
        }else{
            unset($desk_model);
            $msg['code'] = 0;
            $msg['msg'] = "失败";
            exit(json_encode($msg));
        }
    }

	//编辑微信端order标题
	public function update_title(){
		$wx_order_title = I('post.wx_order_title');
		$restaurant = D('restaurant');
		$data['restaurant_id'] = session('restaurant_id'); 
		$data['wx_order_title'] = $wx_order_title;
		$result = $restaurant->save($data);
		if($result){
			$msg['msg'] = '编辑标题成功';
			$msg['code'] = 1;
		}else{
			$msg['msg'] = '编辑标题失败';
			$msg['code'] = 0;
		}
		exit(json_encode($msg));
	}

    //叫号屏管理
    public function show_num_device(){
        $config_model = D('config');
        $where['config_type'] = "functionality";
        $where['config_name'] = "show_num";
        $where['restaurant_id'] = session("restaurant_id");
        $config_value = $config_model->where($where)->field("config_value")->find()['config_value'];
        if($config_value){
            $is_open = 1;
            $this->assign('is_open',$is_open);
        }

        $adver = D('advertisement');
        $condition2['restaurant_id'] = session('restaurant_id');
        $condition2['advertisement_type'] = 2;
        $arr5 = $adver->where($condition2)->select();
        $this->assign("info888",$arr5); 						//叫号屏广告填充(当前店铺)
        $this->display();
    }

    //上传叫号屏广告
    public function uploadsnimg(){
        $adver = D('advertisement');
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      'upadvert_showNum/'; // 设置附件上传目录
        $upload->autoSub = false;
        $z   =   $upload->upload();
        $picpathname = './Application/Admin/Uploads/'.$z[file]['savepath'] . $z[file]['savename'];
        $data['advertisement_image_url'] = $picpathname;
        $data['restaurant_id'] = session('restaurant_id');
        $data['advertisement_type'] = 2;
        $map['advertisement_id'] = I('post.aid');
        if($_POST['wtype'] == "default"){
            $addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
            if($addr != "./Application/Admin/Uploads/default/default_hxadv.jpg"){
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
                if($addr != "./Application/Admin/Uploads/default/default_hxadv.jpg"){
                    $addr = ltrim($addr,".");
                    $address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
                    unlink($address);
                }
                $data['advertisement_id'] = I('post.aid');
                $data1 = $adver->save($data);
            }
        }
        $where['advertisement_type'] = 2;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign('info888',$arr);
        $this->display('adv_jiaohaoping');
    }

    //删除叫号屏广告
    public function deladver88(){
        $adver = D('advertisement');
        //删除服务器上的图片
        $imgaddr = $adver->where("advertisement_id=".$_POST['advertisement_id'])->field("advertisement_image_url")->find()['advertisement_image_url'];
        $imgaddr = ltrim($imgaddr,".");
        $address = dirname(dirname(dirname(dirname(__FILE__)))).$imgaddr;
        unlink($address);

        $adver->where('advertisement_id='.$_POST['advertisement_id'])->delete();
        $where['advertisement_type'] = 2;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign("info888",$arr);
        $this->display('adv_jiaohaoping');
    }

    //开启或关闭叫号屏
    public function openOrCloseShowNum(){
        $config_model = D('config');
        $is_open = I('is_open');
        $where['config_type'] = "functionality";
        $where['config_name'] = "show_num";
        $where['restaurant_id'] = session("restaurant_id");
        $config_value = $config_model->where($where)->field("config_value")->find();
        $rel = false;
        if($is_open == 1){
            if(empty($config_value)){
                $data['config_type'] = "functionality";
                $data['config_value'] = $this->generate_password();
                $data['config_name'] = "show_num";
                $data['restaurant_id'] = session("restaurant_id");
                $rel = $config_model->where($where)->add($data);
            }else if($config_value['config_value'] == 0 ){
                $data['config_type'] = "functionality";
                $data['config_value'] = $this->generate_password();
                $data['config_name'] = "show_num";
                $data['restaurant_id'] = session("restaurant_id");
                $where['restaurant_id'] = session("restaurant_id");
                $rel = $config_model->where($where)->save($data);
            }
        }elseif($is_open == 0){
            $data['config_type'] = "functionality";
            $data['config_value'] = 0;
            $data['config_name'] = "show_num";
            $data['restaurant_id'] = session("restaurant_id");
            $where['restaurant_id'] = session("restaurant_id");
            $rel = $config_model->where($where)->save($data);
        }
        if($rel){
            $returnData['code'] = 1;
            $returnData['msg'] = "修改成功";
        }else{
            $returnData['code'] = 0;
            $returnData['msg'] = "修改失败";
        }
        exit(json_encode($returnData));
    }

    /**
     * 随机生成6为字符串
     * @param int $length
     * @return string
     */
    function generate_password( $length = 6 ) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
}