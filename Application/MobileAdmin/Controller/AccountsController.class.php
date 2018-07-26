<?php
namespace MobileAdmin\Controller;
use Think\Controller;
use Think\Page;
	
class AccountsController extends Controller{

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

    //收银员主页
    public function index(){
        $cashier = D('cashier');
        $where['restaurant_id'] = session('restaurant_id');
        $cashierArr = $cashier->where($where)->select();
        $this->assign("cashierArr",$cashierArr);
        $this->display();
    }

    // 添加收银员
    public function role_add(){
        if(IS_POST){
            // 账号添加
            $cashier = D('cashier');
            $data['cashier_name'] = $_POST['Cashier_name'];
            $data['cashier_pwd'] = $_POST['Cashier_pwd'];
            $data['cashier_phone'] = $_POST['Cashier_phone'];
            $data['cashier_sex'] = $_POST['Cashier_sex'];
            $data['restaurant_id'] = session('restaurant_id');
            $r = $cashier->add($data);
            if($r){
                $return['code'] = 1;
                $return['msg'] = '成功';
            }else{
                $return['code'] = 0;
                $return['msg'] = '失败';
            }
            exit(json_encode($return));
        }
        $this->display();
    }

    // 编辑收银员
    public function role_edit(){
        $cashier = D('cashier');
        if(IS_POST){
            // 编辑处理
            $data['cashier_id'] = $_POST['Cashier_id'];
            $data['cashier_name'] = $_POST['Cashier_name'];
            $data['cashier_pwd'] = $_POST['Cashier_pwd'];
            //$data['cashier_phone'] = $_POST['Cashier_phone'];
            $data['cashier_sex'] = $_POST['Cashier_sex'];
            $r = $cashier->save($data);
            if($r !== false){
                $return['code'] = 1;
                $return['msg'] = '成功';
            }else{
                $return['code'] = 0;
                $return['msg'] = '失败';
            }
            exit(json_encode($return));
        }
        // 编辑回显
        $where['cashier_id'] = I('get.id');
        $role_info = $cashier->where($where)->find();
        $this->assign('role_info',$role_info);
        $this->display();
    }
		
	//删除
	public function Accountsdel(){
		$cashier = D('cashier');
		$where['cashier_id'] = I('post.Cashier_id');
		$r = $cashier->where($where)->delete();
        if($r !== false){
            $return['code'] = 1;
            $return['msg'] = '成功';
        }else{
            $return['code'] = 0;
            $return['msg'] = '失败';
        }
        exit(json_encode($return));
	}
}
?>