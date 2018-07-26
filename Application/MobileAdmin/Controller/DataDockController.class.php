<?php
namespace MobileAdmin\Controller;
use Think\Controller;
use Think\Encrypt;
use ElemeOpenApi\Config\Config;
use ElemeOpenApi\OAuth\OAuthClient;
use ElemeOpenApi\Api\UserService;
Vendor('ElemeOpenApi.Api.UserService');
Vendor('ElemeOpenApi.Config.Config');
Vendor('ElemeOpenApi.OAuth.OAuthClient');
class DataDockController extends Controller {

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

    /**
     * 获取支付信息
     */
    public function dataForPay(){
        $configModel = D("config");
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session('restaurant_id');
        $wx_config = $configModel->where($condition)->select();
        $wx_config_list = dealConfigKeyForValue($wx_config);
        $this->assign("wx_config",$wx_config_list);

        $condition['config_type'] = "alipay";
        $alipay_config = $configModel->where($condition)->select();
        $alipay_config_list = dealConfigKeyForValue($alipay_config);
        $this->assign("alipay_config",$alipay_config_list);

        $pay_select_model = D('pay_select');
        $ps_condition['restaurant_id'] = session('restaurant_id');
        $pay_select_config = $pay_select_model->where($ps_condition)->select();
        $this->assign("pay_select",$pay_select_config);

        $fourth_model = D('fourth');
        $fm_condition['restaurant_id'] = session('restaurant_id');
        $fourth_config = $fourth_model->where($fm_condition)->find();
        $this->assign("fourth",$fourth_config);

        $pay_mode_model = D('pay_mode');
        $pm_condition['restaurant_id'] = session('restaurant_id');
        $mode = $pay_mode_model->where($pm_condition)->getField('mode');
        $this->assign("mode",$mode);

        $restaurant_other_info = D("restaurant_other_info");
        $roi_where['restaurant_id'] = session("restaurant_id");
        $rel = $restaurant_other_info->where($roi_where)->find();
        $pid = $rel['pay_number'];
        if(empty($pid)){
            $pid = 0;
        }
        $this->assign("pid",$pid);

        $this->display();
    }

    // 微信官方
    public function wechat(){
        $configModel = D("config");
        $condition['config_type'] = "wxpay";
        $condition['restaurant_id'] = session('restaurant_id');
        $wx_config = $configModel->where($condition)->select();
        $wx_config_list = dealConfigKeyForValue($wx_config);
        $this->assign("wx_config",$wx_config_list);
        $this->display();
    }

    // 支付宝官方
    public function alipay(){
        $restaurant_other_info = D("restaurant_other_info");
        $roi_where['restaurant_id'] = session("restaurant_id");
        $rel = $restaurant_other_info->where($roi_where)->find();
        $pid = $rel['pay_number'];
        $this->assign("pid",$pid);
        $this->display();
    }

    // 银行代收
    public function fourth(){
        $fourth_model = D('fourth');
        $fm_condition['restaurant_id'] = session('restaurant_id');
        $fourth_config = $fourth_model->where($fm_condition)->find();

        $key            = C("F_KEY");
        $en             = new Encrypt();
        $fourth_config['pwd'] = $en->decrypt($fourth_config['pwd'], $key);

        $this->assign("fourth",$fourth_config);
        $this->display();
    }

    /**
     * 增加修改支付信息
     */
    public function editAddPayInfo(){
        $type = I("get.type");
        $configModel = D('config');
        $configModel->startTrans();
        $pay_data = I('post.');
        $data['restaurant_id'] = session("restaurant_id");
        $data['config_type'] = $type;
        foreach($pay_data as $key => $val){
            $data['config_name'] = $key;
            $data['config_value'] = $val;
            $condition['config_name'] = $key;
            $condition['restaurant_id'] = $data['restaurant_id'];
            $tempRel = $configModel->field("config_id")->where($condition)->find();
            if($tempRel){
                $data2['config_id'] = $tempRel['config_id'];
                $rel = $configModel->where($data2)->save($data);
            }else{
                $rel = $configModel->add($data);
            }
            if($rel === false){
                $configModel->rollback();
                $this->ajaxReturn(0);
            }
        }
        $configModel->commit();
        $this->ajaxReturn(1);
    }
    //第四方支付
    public function editAddPayInfos()
    {
        $fourthModel = D("fourth");
        $data = $fourthModel->create();
        $restaurant_id = session("restaurant_id");
        $data['restaurant_id'] = $restaurant_id;
        $condition['restaurant_id'] = $restaurant_id;
        $tempRel = $fourthModel->where($condition)->find();
        if($tempRel){
            // 判断密码是否有改动过
            /*if($data['pwd'] == $tempRel['pwd']){
                // 没改动，只更新account值
                $rel = $fourthModel->where($condition)->save(array('account'=>$data['account']));
            }else{
                // 有改动
                $key            = C("F_KEY");
                $en             = new Encrypt();
                $data['pwd'] = $en->encrypt($data['pwd'], $key);
                $rel = $fourthModel->where($condition)->save($data);
            }*/
            $key            = C("F_KEY");
            $en             = new Encrypt();
            $data['pwd'] = $en->encrypt($data['pwd'], $key);
            $rel = $fourthModel->where($condition)->save($data);

            if($rel !== false){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }
        }else{
            $key            = C("F_KEY");
            $en             = new Encrypt();
            $data['pwd'] = $en->encrypt($data['pwd'], $key);
            $rel = $fourthModel->add($data);
            if($rel){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }
        }
    }

    // 生成微信、支付宝支付测试二维码
    public function create_pay_test_qrc()
    {
        // 生成订单号
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
        $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
        $condition1['restaurant_id'] = session("restaurant_id");     //店铺id
        $order = D("pay_test_demo");
        $num = $order->where($condition1)->count();        //两时间之间的订单数
        $order_sn = "PAY".str_pad(session('restaurant_id'),5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示最新一订单
        // 生成订单表记录
        $data['order_sn'] = $order_sn;
        $data['add_time'] = time();
        $data['total_amount'] = 0.01;
        $data['restaurant_id'] = session("restaurant_id");
        $res = $order->add($data);
        if($res){
            $type = I('post.type');
            // 判断是生成支付宝还是微信二维码
            if($type == 1){
                // 支付宝
                $qr_code_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/AlipayDirect/pay_test_alipay_code/order_sn/".$order_sn;
            }else{
                // 微信
                $qr_code_url = "http://".$_SERVER["HTTP_HOST"]."/index.php/vertical/WxChat/pay_test_qrc/order_sn/".$order_sn;
            }
            $return_data['code'] = 1;
            $return_data['qr_code_url'] = $qr_code_url;
            exit(json_encode($return_data));
        }else{
            $return_data['code'] = 0;
            $return_data['qr_code_url'] = '';
            exit(json_encode($return_data));
        }

    }

    /**
     * 增加/修改打印机
     */
    public function addEditPrinter(){
        $type = I("post.type");
        $printerModel = D("printer");
        $printerInfo = $printerModel->create();
        $printerInfo['restaurant_id'] = session('restaurant_id');
        if($type == "add"){
            $rel = $printerModel->add($printerInfo);
        }
        if($type == "edit"){
            $rel = $printerModel->save($printerInfo);
        }

        if($rel !== false){
            $returnMsg['code'] = 1;
            $returnMsg['msg'] = "操作成功";
            exit(json_encode($returnMsg));
        }
    }

    /**
     * 打印机显示页面
     */
    public function printer(){
        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
        $Model = M();
        foreach ($printList as $k => $v) {
            $ifPrint = $Model->query('SELECT print_id FROM food WHERE find_in_set("'.$v['printer_id'].'", print_id)');
            $ifPrint_tag = $Model->query('SELECT tag_print_id FROM food WHERE find_in_set("'.$v['printer_id'].'", tag_print_id)');
            if ($ifPrint || $ifPrint_tag) {
                $printList[$k]['if_use'] = '使用中';
            }elseif (!$ifPrint && !$ifPrint_tag) {
                $printList[$k]['if_use'] = '未使用';
            }
        }
       
        $this->assign('printList',$printList);
        $this->display();
    }

    // 获取编辑时用于回显的打印机信息
    public function bill_edit(){
        $printer_id = I('get.printer_id');
        $printerModel = D("printer");
        $p_condition['printer_id'] = $printer_id;
        $printer_info = $printerModel->where($p_condition)->find();
        $this->assign('printer_info',$printer_info);
        $this->display();
    }

    // 添加打印机页面
    public function bill_add(){
        $this->display();
    }

    /**
     * 删除打印机
     */
    public function deletePrinter(){
        $printer_id = I("post.printer_id");
        $condition['printer_id'] = $printer_id;
        $printerModel = D('printer');
        $rel = $printerModel->where($condition)->delete();
        if($rel !== false){
            $returnMsg['code'] = 1;
            $returnMsg['msg'] = "操作成功";
            exit(json_encode($returnMsg));
        }
    }

    /**
     * 获取当前拥有的打印机（返回json数据）
     */
    public function getPrinter(){
        $printerModel = D('printer');
        $condition['restaurant_id'] = session("restaurant_id");
        $printerList = $printerModel->where($condition)->select();
        exit(json_encode($printerList));
    }

    // 使用状态的更改
    public function selectPay(){
        $pay_select = D('pay_select');
        $data = $pay_select->create();
        $condition['restaurant_id'] = session("restaurant_id");
        $condition['config_name'] = $data['config_name'];
        $res = $pay_select->where($condition)->save($data);
        if($res !== false){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    // 原生支付与第四方支付的选择
    public function selectMode(){
        $pay_mode = D('pay_mode');
        $data = $pay_mode->create();
        $condition['restaurant_id'] = session("restaurant_id");
        $modeData = $pay_mode->where($condition)->find();
        // 判断是新增还是编辑
        if ($modeData == '') {
            $data['restaurant_id'] = session("restaurant_id");
            $pay_mode->add($data);
        }else{
            $pay_mode->where($condition)->save($data);
        }
        
        $pay_select = D('pay_select');
        if ($data['mode'] == 1) {
            $condition1['config_name'] = 'wechat-code';
            $condition1['restaurant_id'] = session("restaurant_id");
            $data1['value'] = 1;
            $pay_select->where($condition1)->save($data1);
            $condition2['config_name'] = 'ali-code';
            $condition2['restaurant_id'] = session("restaurant_id");
            $data2['value'] = 1;
            $pay_select->where($condition2)->save($data2);
        }elseif ($data['mode'] == 2) {
            $condition1['config_name'] = 'wechat-code';
            $condition1['restaurant_id'] = session("restaurant_id");
            $data1['value'] = 0;
            $pay_select->where($condition1)->save($data1);
            $condition2['config_name'] = 'ali-code';
            $condition2['restaurant_id'] = session("restaurant_id");
            $data2['value'] = 0;
            $pay_select->where($condition2)->save($data2);
        }
        $this->ajaxReturn(1);
    }

    //票据底部广告语设置
    public function adv_langSet(){
        $type = I('post.type');
        if($type == "meituan"){
            $condition['bill_foot_language'] = I('post.bill_foot_language');
            $return = $condition['bill_foot_language'];
        }else{
            $condition['eleme_bill_foot_language'] = I('post.eleme_bill_foot_language');
            $return = $condition['eleme_bill_foot_language'];
        }
        $condition['restaurant_id'] = session('restaurant_id');
        $restaurant = D('Restaurant');
        $n = $restaurant->save($condition);
        exit(json_encode($return));
    }
}
