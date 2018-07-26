<?php
namespace MobileAdmin\Controller;
use Think\Controller;
class MoudleController extends Controller {
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

	//模板-点餐流程页
    public function index(){
    	$restaurant_process = D('restaurant_process');
		$p_condition['restaurant_id'] = session('restaurant_id');
    	$arr2 = $restaurant_process->where($p_condition)->order('process_id')->select();
    	$process = D('process');
    	$arrlist = array();
    	foreach($arr2 as $a2){
            if($a2['process_id'] != 3 && $a2['process_id'] != 5){
                $p_condition['process_id'] = $a2['process_id'];
                $processlist = $process->where($p_condition)->find();
                $processlist['process_status'] = $a2['process_status'];
                $arrlist[] = $processlist;
            }
    	}
    	$this->assign("info2",$arrlist); 					//流程页填充(当前店铺)

    	$restaurant = D('Restaurant');
		$r_condition['restaurant_id'] = session('restaurant_id');
		$adv_lang = $restaurant->where($r_condition)->field('adv_language')->find()['adv_language'];
    	$this->assign('info4',$adv_lang);                   // 支付成功提示语

        $this->display();
    }

    //点餐流程状态更改
    public function modifyprocess(){
    	$restaurant_process = D('restaurant_process');
		$condition['process_status'] = I('get.status');
		$condition['restaurant_id'] =session('restaurant_id');
		$data['restaurant_id'] =session('restaurant_id');
		$data["process_id"] = I('get.id');
		$result = $restaurant_process->where($data)->save($condition);

		$msg['msg'] = "修改流程页状态";
		$msg['result'] = $result;
		exit(json_encode($msg));	
    }
	
	//下单成功提示语设置
	public function adv_langSet(){
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['adv_language'] = I('post.adv_language');
		$restaurant = D('Restaurant');
		$n = $restaurant->save($condition);
		$arr = $restaurant->where($condition)->find();
    	exit(json_encode($arr));
	}

    // 广告设置
    public function ad_set(){
        $this->display();
    }

    // 横屏广告
    public function ad_horizontal(){
        $adver = D('advertisement');
        $condition['restaurant_id'] = session('restaurant_id');
        $condition['advertisement_type'] = 0;
        $arr = $adver->where($condition)->select();
        $this->assign("info",$arr);							//横屏广告填充(当前店铺)

        $restaurant = D('Restaurant');
        $r_condition['restaurant_id'] = session('restaurant_id');
        $time = $restaurant->where($r_condition)->field('advertise_time')->find()['advertise_time'];
        $this->assign('info3',$time);						//广告时间(当前店铺)
        $this->display();
    }

    // 竖屏广告
    public function ad_vertical(){
        $restaurant = D('Restaurant');
        $r_condition['restaurant_id'] = session('restaurant_id');
        $time = $restaurant->where($r_condition)->field('advertise_time')->find()['advertise_time'];
        $this->assign('info3',$time);						//广告时间(当前店铺)

        $adver = D('advertisement');
        $condition1['restaurant_id'] = session('restaurant_id');
        $condition1['advertisement_type'] = 1;
        $arr1 = $adver->where($condition1)->select();
        $this->assign("info1",$arr1); 						//竖屏广告填充(当前店铺)

        $shuping_adv_language = $restaurant->where($r_condition)->getField('shuping_adv_language');
        $this->assign('info5',$shuping_adv_language);                   //竖屏广告语

        $this->display();
    }

    //竖屏广告语默认设置
    public function shuping_adv_langSet(){
        $condition['restaurant_id'] = session('restaurant_id');
        $condition['shuping_adv_language'] = I('post.shuping_adv_language');
        $restaurant = D('Restaurant');
        $n = $restaurant->save($condition);
        $shuping_adv_language = $restaurant->where($condition)->getField('shuping_adv_language');
        $return = "修改成功，当前广告语:".$shuping_adv_language;
        if($shuping_adv_language == null){
            $return = '没有广告语';
        }
        exit(json_encode($return));
    }

    //广告轮播时间设置
    public function timeSettings(){
        $condition['restaurant_id'] = session('restaurant_id');
        $condition['advertise_time'] = I('post.advertise_time');
        $restaurant = D('Restaurant');
        $n = $restaurant->save($condition);
        if($n !== false){
            $return['code'] = 1;
            $return['msg'] = '修改成功';
        }else{
            $return['code'] = 0;
            $return['msg'] = '修改失败';
        }
        exit(json_encode($return));
    }

    //上传横版广告
    public function uploadimg(){
        $adver = D('advertisement');
        $upload = new \Think\Upload();      // 实例化上传类
        $upload->maxSize   =     1024*1024*6;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      'upadvert_heng/'; // 设置附件上传目录
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
        $this->display('adv_heng');
    }

    //删除横屏广告
    public function deladver(){
        $adver = D('advertisement');
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
        $this->display('adv_heng');
    }

    //上传竖版广告
    public function uploadphimg(){
        $adver = D('advertisement');
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     1024*1024*6 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      'upadvert_shu/'; // 设置附件上传目录
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
        $this->display('adv_shu');
    }

    //删除竖屏广告
    public function deladver1(){
        $adver = D('advertisement');
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
        $this->display('adv_shu');
    }
}