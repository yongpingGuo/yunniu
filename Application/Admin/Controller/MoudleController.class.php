<?php
namespace Admin\Controller;
use Think\Controller;
class MoudleController extends Controller {
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

	//模板-点餐流程页
    public function index(){	
    	$adver = D('advertisement');
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['advertisement_type'] = 0;
    	$arr = $adver->where($condition)->select();
    	$this->assign("info",$arr);							//横屏广告填充(当前店铺)

    	$condition1['restaurant_id'] = session('restaurant_id');
		$condition1['advertisement_type'] = 1;
    	$arr1 = $adver->where($condition1)->select();
    	$this->assign("info1",$arr1); 						//竖屏广告填充(当前店铺)
    	
    	$condition2['restaurant_id'] = session('restaurant_id');
    	$condition2['advertisement_type'] = 3;
    	$arr5 = $adver->where($condition2)->select();
    	$this->assign("doubleDisplay",$arr5); 	  				//双屏客显广告填充(当前店铺)
    	
    	$restaurant_process = D('restaurant_process');
		$p_condition['restaurant_id'] = session('restaurant_id');
    	$arr2 = $restaurant_process->where($p_condition)->select();
    	$process = D('process');
    	$arrlist = array();
    	foreach($arr2 as $a2){
    		$p_condition['process_id'] = $a2['process_id'];
    		$processlist = $process->where($p_condition)->find();
    		$processlist['process_status'] = $a2['process_status'];
    		$arrlist[] = $processlist;
    	}

    	$this->assign("info2",$arrlist); 					//流程页填充(当前店铺)
    	
    	$restaurant = D('Restaurant');
		$r_condition['restaurant_id'] = session('restaurant_id');
    	$time = $restaurant->where($r_condition)->field('advertise_time')->find()['advertise_time'];
    	$this->assign('info3',$time);						//广告时间(当前店铺)
								
		$adv_lang = $restaurant->where($r_condition)->field('adv_language')->find()['adv_language'];
    	$this->assign('info4',$adv_lang);                   //默认广告语(当前店铺)

        $shuping_adv_language = $restaurant->where($r_condition)->getField('shuping_adv_language');
        $this->assign('info5',$shuping_adv_language);

        $double_adv_language = $restaurant->where($r_condition)->getField('double_adv_language');
        $this->assign('info6',$double_adv_language);    // 双屏客显
        //竖屏广告语
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

        $dianpu_id = session('restaurant_id');
        if(I('get.id') == 1){
            // 删除相关的广告页
            @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息
        }elseif(I('get.id') == 4){
            @ unlink(HTML_PATH . "$dianpu_id/order.html");  // 删除订单页
        }

		$msg['msg'] = "修改流程页状态";
		$msg['result'] = $result;
		exit(json_encode($msg));	
    }
    
    //广告轮播时间设置
    public function timeSettings(){
    	$condition['restaurant_id'] = session('restaurant_id');
    	$condition['advertise_time'] = I('post.advertise_time');
    	$restaurant = D('Restaurant');
    	$n = $restaurant->save($condition);
    	$arr = $restaurant->where($condition)->select();
    	exit(json_encode($arr));   	
    }
	
	//广告语默认设置
	public function adv_langSet(){
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['adv_language'] = I('post.adv_language');
		$restaurant = D('Restaurant');
		$n = $restaurant->save($condition);
		$arr = $restaurant->where($condition)->find();
    	exit(json_encode($arr));
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

    //双屏客显屏广告语默认设置
    public function double_adv_langSet(){
        $condition['restaurant_id'] = session('restaurant_id');
        $condition['double_adv_language'] = I('post.double_adv_language');
        $restaurant = M('Restaurant');
        $n = $restaurant->save($condition);
        $double_adv_language = $restaurant->where($condition)->getField('double_adv_language');
        $return = "修改成功，当前广告语:".$double_adv_language;
        if($double_adv_language == null){
            $return = '没有广告语';
        }
        exit(json_encode($return));
    }
    
    //上传横版广告
    public function uploadimg(){
        # 取出session中的restaurant_id，供删除缓存文件使用
        $dianpu_id = session('restaurant_id');

    	$adver = D('advertisement');
      	$upload = new \Think\Upload();      // 实例化上传类
      	$upload->maxSize   =     3145728 ;// 设置附件上传大小
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

            // 删除相关的广告页
            @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息

      	}else{
      		if($_POST['statu'] == ""){
      			$num = $adver->add($data);

                // 删除相关的广告页
                @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息

      		}else{
      			$addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
				if($addr != "./Application/Admin/Uploads/default/default_hengadv.jpg"){
					$addr = ltrim($addr,".");
	      			$address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
	      			unlink($address);
				}
      			$data['advertisement_id'] = I('post.aid');
      			$data1 = $adver->save($data);

                // 删除相关的广告页
                @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息
      		}
      	}
      	$where['advertisement_type'] = 0;
		$where['restaurant_id'] = session('restaurant_id');
		$arr = $adver->where($where)->select();
		$this->assign('info',$arr);
		$this->display('adv_heng');
    }
    
     //上传竖版广告
    public function uploadphimg(){
        # 取出session中的restaurant_id，供删除缓存文件使用
        $dianpu_id = session('restaurant_id');

    	$adver = D('advertisement');
      	$upload = new \Think\Upload();// 实例化上传类
      	$upload->maxSize   =     3145728 ;// 设置附件上传大小
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

            // 删除相关的广告页
            @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息

      	}else{
      		if($_POST['statu'] == ""){
      			$num = $adver->add($data);

                // 删除相关的广告页
                @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息

      		}else{
      			$addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
				if($addr != "./Application/Admin/Uploads/default/default_shuadv.jpg"){
					$addr = ltrim($addr,".");
	      			$address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
	      			unlink($address);
				}
      			$data['advertisement_id'] = I('post.aid');	
      			$data1 = $adver->save($data);

                // 删除相关的广告页
                @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息
      		}
      	}
      	$where['advertisement_type'] = 1;
		$where['restaurant_id'] = session('restaurant_id');
		$arr = $adver->where($where)->select();
		$this->assign('info1',$arr);
		$this->display('adv_shu');
    }
    
	//上传双屏客显广告
    public function uploadsnimg(){
        $adver = M('advertisement');
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      'upadvert_showNum/'; // 设置附件上传目录
        $upload->autoSub = false;
        $z   =   $upload->upload();
        $picpathname = './Application/Admin/Uploads/'.$z[file]['savepath'] . $z[file]['savename'];
        $data['advertisement_image_url'] = $picpathname;
        $data['restaurant_id'] = session('restaurant_id');
        $data['advertisement_type'] = 3; // 双屏客显
        $map['advertisement_id'] = I('post.aid');
        if($_POST['wtype'] == "default"){
            $addr = $adver->where($map)->field("advertisement_image_url")->find()['advertisement_image_url'];
            if($addr != "./Application/Admin/Uploads/default/default_doubleDisplay.jpg"){
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
                if($addr != "./Application/Admin/Uploads/default/default_doubleDisplay.jpg"){
                    $addr = ltrim($addr,".");
                    $address = dirname(dirname(dirname(dirname(__FILE__)))).$addr;
                    unlink($address);
                }
                $data['advertisement_id'] = I('post.aid');
                $data1 = $adver->save($data);
            }
        }
        $where['advertisement_type'] = 3;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign('info888',$arr);
        $this->display('adv_jiaohaoping');
    }
	
    //删除横屏广告
    public function deladver(){
        // 供删除缓存文件使用的店铺ID
        $dianpu_id = session("restaurant_id");

    	$adver = D('advertisement');
    	//删除服务器上的图片
    	$imgaddr = $adver->where("advertisement_id=".$_POST['advertisement_id'])->field("advertisement_image_url")->find()['advertisement_image_url'];
		$imgaddr = ltrim($imgaddr,".");
    	$address = dirname(dirname(dirname(dirname(__FILE__)))).$imgaddr;	
    	unlink($address);


        // 删除相关的广告页
        @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息

		$adver->where('advertisement_id='.$_POST['advertisement_id'])->delete();
		$where['advertisement_type'] = 0;
		$where['restaurant_id'] = session('restaurant_id');
		$arr = $adver->where($where)->select();
    	$this->assign('info',$arr);
    	$this->display('adv_heng');
    }
    
	//删除竖屏广告
    public function deladver1(){
        // 供删除缓存文件使用的店铺ID
        $dianpu_id = session("restaurant_id");

    	$adver = D('advertisement');
    	//删除服务器上的图片
    	$imgaddr = $adver->where("advertisement_id=".$_POST['advertisement_id'])->field("advertisement_image_url")->find()['advertisement_image_url'];
		$imgaddr = ltrim($imgaddr,".");
    	$address = dirname(dirname(dirname(dirname(__FILE__)))).$imgaddr;
    	unlink($address);

        // 删除相关的广告页
        @ unlink(HTML_PATH  . "$dianpu_id/index.html"); // @是为了抑制因文件不存在而删除失败的错误信息

		$adver->where('advertisement_id='.$_POST['advertisement_id'])->delete();
		$where['advertisement_type'] = 1;
		$where['restaurant_id'] = session('restaurant_id');
		$arr = $adver->where($where)->select();
		$this->assign("info1",$arr);
		$this->display('adv_shu');
    }

    //删除双屏客显屏广告
    public function deladver88(){
        $adver = M('advertisement');
        //删除服务器上的图片
        $imgaddr = $adver->where("advertisement_id=".$_POST['advertisement_id'])->field("advertisement_image_url")->find()['advertisement_image_url'];
        $imgaddr = ltrim($imgaddr,".");
        $address = dirname(dirname(dirname(dirname(__FILE__)))).$imgaddr;
        unlink($address);

        $adver->where('advertisement_id='.$_POST['advertisement_id'])->delete();
        $where['advertisement_type'] = 3;
        $where['restaurant_id'] = session('restaurant_id');
        $arr = $adver->where($where)->select();
        $this->assign("info888",$arr);
        $this->display('adv_jiaohaoping');
    }
    
    //模板-点餐设备界面
    public function device(){     										//模板表
    	$restaurant_page_group = D('restaurant_page_group');			//模板与店铺关联的第三表
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['page_screen'] = 1;
		$horizontal_page_group = $restaurant_page_group->where($condition)->select();		//查出当前店铺的所有终端模板记录（横，竖，移动)

		$group_detail_model = D("group_detail");
		foreach($horizontal_page_group as $hpg_key => $hpg_val){
			$h_where['group_id'] = $hpg_val['group_id'];
			$h_group_info = $group_detail_model->where($h_where)->find();
			$horizontal_page_group[$hpg_key]['group_name'] = $h_group_info['group_name'];
			$horizontal_page_group[$hpg_key]['group_img_url'] = $h_group_info['group_img_url'];
		}

		$condition['page_screen'] = 2;
		$vertical_page_group = $restaurant_page_group->where($condition)->select();		//查出当前店铺的所有终端模板记录（横，竖，移动)

		foreach($vertical_page_group as $vpg_key => $vpg_val){
			$h_where['group_id'] = $vpg_val['group_id'];
			$v_group_info = $group_detail_model->where($h_where)->find();
			$vertical_page_group[$vpg_key]['group_name'] = $v_group_info['group_name'];
			$vertical_page_group[$vpg_key]['group_img_url'] = $v_group_info['group_img_url'];
		}

		$this->assign('info',$horizontal_page_group);
		$this->assign('info2',$vertical_page_group);

    	$this->display();
    }
    
    //输入模板获取码，获取指定模板
	public function replaceTemp(){		
		//先判断该模板该餐厅是否存在
		$theme_code_model = D('theme_code');
		$data['theme_code'] = I('theme_code');
		$group_detail_info = $theme_code_model->where($data)->field('group_id')->find();
		$group_id = $group_detail_info['group_id'];
		$group_detail_model = D("group_detail");
		$group_detail_info = $group_detail_model->where("group_id = $group_id")->find();
		$tpltype = I('tpltype');

		if($group_detail_info['group_screen'] != $tpltype){
			$msg['msg'] = '提取码有误！';
			$msg['code'] = 1;
			exit(json_encode($msg));
		}
		$restaurant_page_group_model = D('restaurant_page_group');
		$data1['restaurant_id'] = session('restaurant_id');
		$data1['group_id'] = $group_id;
		$arr = $restaurant_page_group_model->where($data1)->find();
		if(!empty($arr)){
			$msg['msg'] = '模板已存在！';
			$msg['code'] = 2;
			exit(json_encode($msg));
		}else{
			$data2['group_id'] = $group_id;
			$data2['page_screen'] = $tpltype;
			$data2['restaurant_id'] = session('restaurant_id');
			$result2 = $restaurant_page_group_model->add($data2);						//添加记录到关联
			if($result2){
				$theme_code_model->where($data)->delete();
				$msg['msg'] = '获取模板成功！';
				$msg['code'] = 3;
				exit(json_encode($msg));
			}									
		}
	}    
	
	//应用横竖屏模板
	public function useTemp(){
		$restpage = D('restaurant_page_group');
		$condition['restaurant_page_group_id'] = I('restaurant_page_group_id');
		$condition['restaurant_id'] = session('restaurant_id');
	    $r = $restpage->where($condition)->field('status')->find()['status'];	//通过传过来的模板记录ID查询其状态
		if($r != 1){											//如果状态不等于1，则修改成1
			$condition['status'] = 1;
			$restpage->save($condition);
		}
		$map['restaurant_page_group_id'] = array('neq',I('restaurant_page_group_id'));					//将该店铺，其它对应终端的模板状态修改成0
		$map['restaurant_id'] = session('restaurant_id');
		$map['page_screen'] = I("tpltype");
		$data['status'] = 0;

		$r1 = $restpage->where($map)->save($data);
		if($r1){
			$msg['msg'] = "应用该模板成功！";
			$msg['data'] = 1;
		}else{
			$msg['msg'] = "应用该模板失败！";
			$msg['data'] = 2;
		}
		exit(json_encode($msg));
	}

    //上传叫号屏广告
    public function uploadJiaohaoImg(){
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
        $this->display('adv_qucanping');
    }

    //删除叫号屏广告
    public function delQucanAdv(){
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
        $this->display('adv_qucanping');
    }

	
/*	//删除横屏模板
	public function delTemp(){
		$restpage = D('restaurant_page');
		$condition['id'] = $_POST['id'];	
		$condition['restaurant_id'] = 1;
		$result = $restpage->where($condition)->delete();
		$msg['msg'] = "成功删除模板";
		$msg['data'] = $result;
		$msg['type'] = $_POST['type'];	
		exit(json_encode($msg));
	}*/
	
	
	//显示所获得的移动端模板（当前店铺）
	public function mobile(){
		$restaurant_page_group = D('restaurant_page_group');			//模板与店铺关联的第三表
		$condition['restaurant_id'] = session('restaurant_id');
		$condition['page_screen'] = 3;
		$mobile_page_group = $restaurant_page_group->where($condition)->select();		//查出当前店铺的所有终端模板记录（横，竖，移动)

		$group_detail_model = D("group_detail");
		foreach($mobile_page_group as $hpg_key => $hpg_val){
			$h_where['group_id'] = $hpg_val['group_id'];
			$h_group_info = $group_detail_model->where($h_where)->find();
			$mobile_page_group[$hpg_key]['group_name'] = $h_group_info['group_name'];
			$mobile_page_group[$hpg_key]['group_img_url'] = $h_group_info['group_img_url'];
		}
		$this->assign('info',$mobile_page_group);

		$this->display();
	}
	
	
	//应用移动端模板
	public function useTemp2(){
		$restpage = D('restaurant_page');
		$condition['id'] = $_POST['ids'];
		$condition['restaurant_id'] = session('restaurant_id');
	    $r = $restpage->where($condition)->field('replace_status')->select();
		//dump($r[0]['replace_status']);	
		if($r[0]['replace_status'] == 1){			
			$condition['replace_status'] = 1;
			$restpage->save($condition);
		}else{
			$condition['replace_status'] = 1;
			$restpage->save($condition);
		}
		$map['id'] = array('neq',$_POST['ids']);

		$arr = $restpage->where($map)->select();
		//dump($arr);
		foreach($arr as $v){
			$condition1['order_page_id'] = $v['order_page_id'];
			$page = D("page");
			$result = $page->where($condition1)->field('type')->select();
			$result = $result[0];
			//dump($result['type']);
			if($result['type'] == 2){
				$condition2['id'] = $v['id'];
				$condition2['replace_status'] = 0;
				$restpage->save($condition2);
			}
			//dump($result);
		}
		$msg['msg'] = "成功";
		$msg['data'] = $z;
		exit(json_encode($msg));
	}
	
	//更改横屏模板颜色
	public function changecolor(){
		$restaurant = D('Restaurant');
		$condition['tplcolor_id'] = I('get.tplcolor_id');
		$condition['restaurant_id'] = session('restaurant_id');
		$n = $restaurant->save($condition);
		if($n){

            // 删除订单页的缓存文件
            $dianpu_id = session("restaurant_id");
            @ unlink(HTML_PATH . "$dianpu_id/order.html");


			$msg['msg'] = "横屏模板颜色更改成功！";
			$msg['data'] = 0;	
		}else{
			$msg['msg'] = "横屏模板颜色更改失败！";
			$msg['data'] = 1;
		}
		$this->ajaxReturn($msg);
	}
	
	//更改竖屏模板颜色
	public function changecolor1(){
		$restaurant = D('Restaurant');
		$condition['tplcolor1_id'] = I('get.tplcolor1_id');
		$condition['restaurant_id'] = session('restaurant_id');
		$n = $restaurant->save($condition);
		if($n){
			$msg['msg'] = "模板颜色更改成功！";
			$msg['data'] = 0;	
		}else{
			$msg['msg'] = "模板颜色更改失败！";
			$msg['data'] = 1;
		}
		$this->ajaxReturn($msg);
	}
	
	//更改移动模板颜色
	public function changecolor2(){
		$restaurant = D('Restaurant');
		$condition['tplcolor2_id'] = I('get.tplcolor2_id');
		$condition['restaurant_id'] = session('restaurant_id');
		$n = $restaurant->save($condition);
		if($n){
			$msg['msg'] = "模板颜色更改成功！";
			$msg['data'] = 0;	
		}else{
			$msg['msg'] = "模板颜色更改失败！";
			$msg['data'] = 1;
		}
		$this->ajaxReturn($msg);
	}
	
	
}