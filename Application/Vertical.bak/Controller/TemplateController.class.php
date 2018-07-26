<?php
namespace Vertical\Controller;
use Think\Controller;
use Vertical\Service\RouteService;
/**
 * Created by PhpStorm.
 * User: liangbaobin
 * Date: 2017/2/16
 * Time: 22:37
 */
class TemplateController extends Controller
{
    protected $routeService;
    protected $restaurant_id;
    protected $next_page_filename;

    public function __construct($ignore_process = array(4)){
        Controller::__construct();
        $this->restaurant_id = session("restaurant_id");
        $this->routeService = new RouteService($ignore_process,2);
    }

    public function serviceRoute(){
        if(session('group_id') == 2){
            exit("请选择正确模板");
        }
        $current_action = I("current_action");
        $next_process = $this->routeService->getNextProcess($this->restaurant_id,$current_action);
        $this->next_page_filename = "Template/".$next_process['filename'];
        $next_action = $next_process['action'];
        $this->$next_action();
    }

    public function index(){
        $this->display($this->next_page_filename);
    }

    public function select(){
        $this->display($this->next_page_filename);
    }

    public function order(){
        // 将是否开启积分物品的标识传递到视图中判断是否显示
        $score_where['restaurant_id'] = session("restaurant_id");
        $score_where['type'] = 4;
        $if_open = D("set")->where($score_where)->getField("if_open");
        if(empty($if_open)){
            $if_open = 0;
        }
        $this->assign("if_open",$if_open);

        $this->display($this->next_page_filename);
    }

    public function number(){
        $this->display($this->next_page_filename);
    }

    public function pay(){
        $orderModel = D("order");
        $o_condition['order_sn'] = I("get.order_sn");
        $rel = $orderModel->where($o_condition)->field("total_amount,order_sn")->find();
        $pay_select_model = D('pay_select');
        $ps_condition['restaurant_id'] = session('restaurant_id');
        $pay_select_config = $pay_select_model->where($ps_condition)->select();

        $pay_select = array();
        foreach($pay_select_config as $key => $val){
            if($val['value'] == 1){
                $pay_select[$val['s_num']] = 1;
            }else{
                $pay_select[$val['s_num']] = 0;
            }
        }

        $set_where['type'] = 0;
        $set_where['restaurant_id'] = session('restaurant_id');
        $set_model = D("set");
        $set_info = $set_model->where($set_where)->find();
        $discount = $set_info['if_open'];

        $this->assign("wechat_code",$pay_select['1']);
        $this->assign("discount",$discount);
        $this->assign("ali_code",$pay_select['4']);
        $this->assign("wechat",$pay_select['3']);
        $this->assign("cash",$pay_select['2']);
        $this->assign("total_amount",$rel['total_amount']);
        $this->assign("order_sn",$rel['order_sn']);
        $this->display($this->next_page_filename);
    }

    public function finish(){
        $this->display();
    }
    public function nopayfinish(){
        $this->display();
    }

    public function scorePromptPage(){
        $restaurant_id = session('restaurant_id');
        $this->assign("restaurant_id",$restaurant_id);
        $this->display();
    }

    public function payPrompt(){
        $this->display();
    }

    // 生成进入公众号的二维码
    public function public_number_qrc(){
        Vendor('phpqrcode.phpqrcode');

        $restaurant_id = session('restaurant_id');
        $business_id = D("restaurant")->where(array("restaurant_id"=>$restaurant_id))->getField("business_id");
        $public_number_url = D("public_number_set")->where(array("business_id"=>$business_id))->getField("public_number_url");

        // 传递会员id过去
        $val = $public_number_url;

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(4);//生成图片大小

        //生成二维码图片
        $object = new \QRcode();
        $object->png($val,false, $errorCorrectionLevel, $matrixPointSize,4);
    }
}