<?php
namespace Mobile\Controller;
use Think\Controller;
use Think\Encrypt;
class MemberController extends Controller {
    public function index(){
        // 获取头像
        $openid = session("USER.openid");
         // I("get.restaurant_id");
        $vip = D("vip");
        $advtVipModel = D("advertisement_vip");
        $where = [];
        $where['restaurant_id'] = session('restaurant_id');
        // echo "<script>alert(".session('restaurant_id').")</script>";
        // var_dump(session('restaurant_id'));
        $where['advertisement_type'] = 0;
        $info = $advtVipModel->where($where)->select();
        $this->assign('info', $info);//顶部
        $where1 = [];
        $where1['restaurant_id'] = session('restaurant_id');
        $where1['advertisement_type'] = 1;
        $info1 = $advtVipModel->where($where1)->select();
        $this->assign('info1', $info1);///底部
        $data = $vip->where(array("openid"=>$openid))->find();
        $this->assign("data",$data);
        // 获取积分
        $total_score = $this->get_db_score(session("USER.openid"));
        $this->assign("total_score",$total_score);
        $this->display();
    }

    # 接收由微信确认授权后传递过来的数据，然后注册或者去会员中心
    public function receiver_weixin(){
        // 2、获取到网页授权的Access_token
        /*$appid = "wxa9be3598671d1982";
        $AppSecret = "14c17c03b92fbe64f1bd458561a0da08";*/

        $appid = I("get.appid");
        $AppSecret = I("get.AppSecret");

        $code = I("get.code");
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$AppSecret."&code=".$code."&grant_type=authorization_code";
        // $res = $this->http_curl($url,'get');
        $res = http_get($url);

        $res = json_decode($res);

        $access_token = $res->access_token;
        $openid = $res->openid;

        if($openid){
            session("openid",$openid);
        }

        // 将系统的openid存进session，避免刷新页面时$vip_info值为空，跳到注册页面
        $openid = session("openid");
        // 3、拉取用户的详细信息
        $url2 = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res2 = http_get($url2);
        $res3 = json_decode($res2,true);

        # 以下是会员验证板块
        $vipModel = D("Vip");
        // 然后根据openID去会员表查询是否有该用户的记录，有则跳转到会员中心，没有则跳转到注册
        $vip_info = $vipModel->where(array("openid"=>$openid))->find();

        if($vip_info){
            // 更新数据库中的出生年份（进入下一年时年龄自动改变）
            $year = explode("/",$vip_info['birthday'])[0];
            // 现在的真实年龄=当前年份-出生年份
            $real_age =  date("Y")-$year;
            if($vip_info['age'] != $real_age){
                // 不相等则进行更新处理
                $vipModel->where(array("openid"=>$openid))->save(array("age"=>$real_age));
            }

            // 跳到会员中心
            session("USER",array(
                // 以下两者都能单独唯一标识会员
                'id'=>$vip_info['id'],   // 将会员id存进session中
                'openid'=>$vip_info['openid'],   // 将openid存进session中
            ));
            /**********轮播广告************/
            $advtVipModel = D("advertisement_vip");
            $where = [];
            $where['restaurant_id'] = session('restaurant_id');
            $where['advertisement_type'] = 0;
            $info = $advtVipModel->where($where)->select();
            $this->assign('info', $info);//顶部
            $where1 = [];
            $where1['restaurant_id'] = session('restaurant_id');
            $where1['advertisement_type'] = 1;
            $info1 = $advtVipModel->where($where1)->select();
            $this->assign('info1', $info1);///底部
            /**********轮播广告************/
            // 获取头像
            $openid1 = session("USER.openid");
            $vip = D("vip");
            $data = $vip->where(array("openid"=>$openid1))->find();
            $this->assign("data",$data);
            // 获取积分
            $total_score = $this->get_db_score(session("USER.openid"));
            $this->assign("total_score",$total_score);

            $this->display("index");
        }else{
            // 判断session中代理id对应的短信配置表的is_new_user的值，0是旧用户，则使用旧的阿里大于短信接口，1是新用户，使用搬迁后的阿里短信接口
            $old_or_new = D("sms_vip")->where(array("business_id"=>session("business_id")))->getField("is_new_user");
            $this->assign("old_or_new",$old_or_new);

            // 跳到注册  需要携带的是：openid、昵称、性别、头像
            // 注意：这个顺序定下来不要随意改变，因为在处理端那边要炸开
            $translate = array();
            $translate['openid'] =  $res3['openid'];
            $translate['nickname'] = $res3['nickname'];
            $translate['sex'] = $res3['sex'];
            $translate['headimgurl'] = $res3['headimgurl'];

            $translate = implode("|",$translate);   // 拼成字符串用隐藏域传输
            $this->assign("translate",$translate);
            $this->display("reg");
        }
    }

    # 生成会员个人二维码
    public function vip_code(){
        Vendor('phpqrcode.phpqrcode');

        // 传递会员id过去
        $val = session("USER.id");

        // 加个当前的时间戳，然后到那边后截取出来，再判断那时的时间戳与提交过去的时间戳的时间差是否在一个特定的范围内，是则合法
        $date = time();
        $val = $date."|".$val;

        $key = C("SECRET_KEY");
        $en = new Encrypt();
        $val = $en->encrypt($val,$key);

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(4);//生成图片大小

        //生成二维码图片
        $object = new \QRcode();
        $object->png($val,false, $errorCorrectionLevel, $matrixPointSize,0);
    }

    # 生成积分物品订单号和二维码
    public function placeorder(){
        // 首先生成订单信息，然后再生成二维码
        $goods_name = I("post.goods_name");
        $score = I("post.score");
        $id = I("post.goods_id");

        $vip_id = session("USER.id");

        $business_id = D("vip")->where(array("id"=>$vip_id))->getField("business_id");

        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
        $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
        $condition1['vip_id'] = $vip_id;     //会员id

        $score_goods_order = D("score_goods_order");
        $num = $score_goods_order->where($condition1)->count();        //两时间之间的订单数
        $order_sn = "DC".str_pad($vip_id,5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示同一个会员最新一订单

        $add_time = time();            //下单时间
        $condition2['order_sn'] = $order_sn; //订单号
        $condition2['add_time'] = $add_time; //下单时间
        $condition2['vip_id'] = $vip_id;  //会员ID
        $condition2['score'] = $score;  //商品积分
        $condition2['goods_name'] = $goods_name;  //商品名称
        $condition2['business_id'] = $business_id;  //代理id
        $condition2['goods_id'] = $id;  //商品id
        $result = $score_goods_order->data($condition2)->add();//增加一条订单

        // 生成订单成功就生成二维码
        if($result){
            echo $order_sn;
        }
    }

    public function orderQrc($order_sn){
        Vendor('phpqrcode.phpqrcode');

        $key = C("SECRET_KEY");
        $en = new Encrypt();
        $val = $en->encrypt($order_sn,$key);

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(4);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $object->png($val,false, $errorCorrectionLevel, $matrixPointSize,0);
    }


    /*------------------------共用的方法开始-----------------------*/
    # 公共的：根据用户的openid获取代理id
    public function get_business_id($openid=''){
        $vip = D("vip");
        $condition = array("openid"=>$openid);
        $business_id = $vip->where($condition)->getField('business_id');
        return $business_id;
    }


    # 公共的：封装一个根据openid获取数据库积分的方法
    public function get_db_score($openid = ''){
        $vip = D("vip");
        $score =  $vip->where(array('openid'=>$openid))->getField("score");
        return $score;
    }
    /*------------------------共用的方法结束-----------------------*/

    # 注册界面
    public function reg(){
        $this->display();
    }

    /*------------------------积分明细开始-----------------------*/
    public function integration(){
        $openid = session("USER.openid");
        // 调用获取数据库积分的方法
        $total_score = $this->get_db_score($openid);
        // 积分总额
        $this->assign("total_score",$total_score);

        # 并且去订单表获取数据（指定会员，订单状态已支付以上,该笔消费记录换取的积分不为0）
        $order = D("order");
        $condition['vip_id'] = session("USER.id");
        // 订单的状态（0待支付，1已接单，2未接单，3已支付，4未配送，5配送中，6未收货，7已收货，8未评价，9已评价,10已删除,11请取餐,12核销）
        // 0，1，3，10，11，12 这几种
        $condition['order_status'] = array("in","3,11,12");     // 注意：如果后面使用了大于3的其他的状态，就要在这里加
        // 加个积分不为0的条件
        $condition['score'] = array("NEQ",0);
//        $order->where('order_status>=3 AND order_status<>10')->select();

        // 过去一年的记录
        $current_year = Date("Y");
        $current_month = Date("m");

        $last_year = Date("Y")-1;

        //查询上一年
        $previous_year = array();
        for($i = 12;$i>= $current_month;$i--){
            //2016-3 开始时间-结束时间
            $begin_time = strtotime($last_year."-".$i."-1 00:00:00");   // 当前月的一号
            $end_time = strtotime($last_year."-".($i+1)."-1 00:00:00")-1;   // 下个月的一号的前一点
            $condition['pay_time'] = array("BETWEEN",array($begin_time,$end_time));
            $previous_year[$i] = $order->where($condition)->order("pay_time desc")->select();
        }

        //查询今年
        $now_year = array();
        for($j = $current_month;$j>= 1;$j--){
            //2016-3 开始时间-结束时间
            $kaishi_time = strtotime($current_year."-".$j."-1 00:00:00");
            $jieshu_time = strtotime($current_year."-".($j+1)."-1 00:00:00")-1;
            $condition['pay_time'] = array("BETWEEN",array($kaishi_time,$jieshu_time));
            $now_year[$j] = $order->where($condition)->order("pay_time desc")->select();
        }

        $score_detail = array(
            $current_year => $now_year,
            $last_year => $previous_year,
        );

        $this->assign("score_detail",$score_detail);
        $this->display();
    }
    /*------------------------积分明细结束-----------------------*/
    #　模拟测试用的方法
    function test(){
        session("USER",array(
            // 以下两者都能单独唯一标识会员
            'id'=>16,   // 将会员id存进session中
            'openid'=>"oToCav5pz2onMKIJro8Bf46Awl6c",   // 将openid存进session中
        ));
        p(session("USER.openid"));
    }

    /*------------------------积分商城开始-----------------------*/
    # 返回积分商品的列表数据
    public function goods_list()
    {
        $keyword = I("get.keyword");
        if($keyword !='')
        {
            $condition['goods_name'] = array('like', "%$keyword%");
        }
        // 获取到score_goods里面的所有物品
        $score_goods = D("score_goods");
        $agent_id = $this->get_business_id(session("USER.openid"));
        $condition['business_id'] = $agent_id;
        $data = $score_goods->where($condition)->select();
        $this->assign("data",$data);
        $this->display("");
    }

    # 返回人气排行从高到低的积分商品
    public function saleNum(){
       // 具体代理，订单状态为1，积分商品表里面还有这个记录（join）
        $agent_id = $this->get_business_id(session("USER.openid"));
        $data = D("score_goods_order")->field("*,count('id') as num")   // 本来是要注明是score_goods_order表的*和count的
            ->join("score_goods on score_goods_order.goods_id = score_goods.id")
            ->group("score_goods_order.goods_name")
            ->where(array("score_goods_order.order_status"=>1,"score_goods_order.business_id"=>$agent_id))
            ->order("num desc")
            ->select();

        // 还要输出商品表里面除了以上的商品以外的商品
        $arr = array();
        foreach($data as $v){
            // 订单表里面的商品id
            $arr[] = $v['goods_id'];
        }
        if(empty($arr)){
            $arr = array(0);
        }

        $score_goods = D("score_goods");
        $agent_id = $this->get_business_id(session("USER.openid"));
        $condition['business_id'] = $agent_id;
        $condition['id'] = array("not in",$arr);
        $info = $score_goods->where($condition)->select();
        $this->assign("info",$info);

        $this->assign("data",$data);
        $this->display("goods_list");
    }
    /*------------------------积分商城结束-----------------------*/


    /*------------------------积分商城下的具体商品的信息开始-----------------------*/
    public function goods(){
        $id = I("get.id");
        // 根据商品ID去获取具体的商品信息
        $score_goods = D("score_goods");
        $data = $score_goods->where(array("id"=>$id))->find();

        $vip = D("vip");
        $vip_score = $vip->where(array("openid"=>session("USER.openid")))->getField("score");
        $this->assign("vip_score",$vip_score);

        $data['goods_desc'] = htmlspecialchars_decode( $data['goods_desc']);
        $this->assign("data",$data);
        $this->display();
    }
    /*------------------------积分商城结束-----------------------*/

    /*----------------------我的余额开始（该模板页面也包含预充值）-------------------------*/
    public function remainder(){
        $openid = session("USER.openid");
        $vip = D("vip");
        $vipinfo = $vip->where(array("openid"=>$openid))->field("remainder,business_id")->find();
        $this->assign("remainder",$vipinfo['remainder']);
        // 查询出预充值的额度
        $where['business_id'] = $vipinfo['business_id'];
        $where['type'] = 0;
        $prepaid_set = D("business_set")->where($where)->getField("if_open");
        if($prepaid_set){
            $condition['business_id'] = $vipinfo['business_id'];
            $condition['type'] = 1;
            $prepaid = D("all_benefit")->where($condition)->order("account asc")->field("account")->select();
            $this->assign("prepaid",$prepaid);
        }
        $this->display();
    }
    /*----------------------我的余额结束-------------------------*/


    /*------------------------预充值开始-------------------------*/
    # 对客户提交的预充值数据进行优惠处理
    public function prepaid(){
        header('Content-Type:text/xml; charset=utf-8');
        $postStr = file_get_contents("php://input");
        $notifyInfo = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($notifyInfo['result_code'] == 'SUCCESS' && $notifyInfo['return_code'] == 'SUCCESS') {
            # 记录支付通知信息，这里需要更新业务订单支付状态，根据实际情况操作吧。
            file_put_contents(__DIR__."/"."MobileMemberPrepaidCallback.txt",$notifyInfo['out_trade_no']."\r\n\r\n",FILE_APPEND);

            // $restaurant_id = $notifyInfo['restaurant_id'];
            $openid = $notifyInfo['attach'];
            $id = D("vip")->where(array("openid"=>$openid))->getField("id");
            session("USER",array(
                "id"=>$id,
                'openid'=>$openid   // 将会员入口处的openid存进session中
            ));

            // 根据openid去查代理
            $vipmodel = D("vip");
            $business_id = $vipmodel->where(array("openid"=>$openid))->getField("business_id");
            session("business_id",$business_id);

            //操作数据库处理订单信息；
            $order_sn = $notifyInfo['out_trade_no'];
            $orderModel = D("prepaid_order");
            $o_condition['order_sn'] = $order_sn;
            $orderInfo = $orderModel->where($o_condition)->field("order_status,pay_time")->find();
            $order_status = $orderInfo['order_status'];
            $pay_time = $orderInfo['pay_time'];

            if($order_status == 0 && $pay_time == 0){
                file_put_contents(__DIR__."/".'pay_notify_prepaid.log', var_export($notifyInfo, TRUE));

                $data['order_status'] = 1;
                $time = time();
                $data['pay_time'] = $time;
                $rel = $orderModel->where($o_condition)->save($data); //更改订单状态为支付状态，更新支付时间

                if($rel === false){
                    // 如果更新订单信息失败，就将此错误存储到一个错误表里面
                    // prepaid_callback_fail    order_sn、problem_table
                    $prepaid_callback_fail = D("prepaid_callback_fail");
                    $add['order_sn'] = $order_sn;
                    $add['problem_table'] = "prepaid_order";
                    $prepaid_callback_fail->add($add);
                }

                #　进行预充值优惠规则处理
                // 看有没有开启预充值优惠
                // $openid = session("USER.openid");

                $condition['business_id'] = session("business_id");
                // 手动添加类型条件
                $condition['type'] = 0; // 在business_set中0代表预充值
                $business_set = D("business_set");
                $data = $business_set->where($condition)->find();

                $total_fee = $notifyInfo['total_fee']/100;

                if($data['if_open'] == 1){  // 是否开启预充值
                    // 开启了，就进行预充值规则优惠处理
                    $this->_prepaid($openid,$total_fee,$order_sn);
                }else{
                    // 没有开启就正常处理
                    $vip = D("vip");
                    $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");

                    // 也就是直接将充值额累加到数据库余额中
                    $prepaid = $total_fee;

                    $total = array('remainder' => $prepaid + $remainder);

                    $res = $vip->where(array("openid"=>$openid))->save($total);    // 更新会员数据

                    // 更新订单表最终的具体充值额
                    $where['order_sn'] = $order_sn;
                    $data['finall_benefit'] = $prepaid;
                    $data['origin_remainder'] = $remainder;
                    $finall_remainder = $vip->where(array("openid"=>$openid))->getField('remainder');
                    $data['finall_remainder'] = $finall_remainder;  // 客户最后的余额
                    $rel = D('prepaid_order')->where($where)->save($data);

                    if($res === false){
                        // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
                        // prepaid_callback_fail    order_sn、problem_table
                        $prepaid_callback_fail = D("prepaid_callback_fail");
                        $add1['order_sn'] = $order_sn;
                        $add1['problem_table'] = "vip";
                        $prepaid_callback_fail->add($add1);
                    }
                }
            }

           # 所有操作成功，返回正常状态，防止微信重复推荐通知
           echo $this->ToXml(['return_code' => 'SUCCESS', 'return_msg' => 'SAVE DATA SUCCESS']);
       }
   }

    public function ToXml($returnMsg)
   {
       $xml = "<xml>";
       foreach ($returnMsg as $key=>$val)
       {
           if (is_numeric($val)){
               $xml.="<".$key.">".$val."</".$key.">";
           }else{
               $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
           }
       }
       $xml.="</xml>";
       return $xml;
   }

   # 预充值规则处理
    public function _prepaid($openid,$translate_prepaid,$order_sn){
       // 根据代理id读取数据库中的预充值数据
       $business_id = session("business_id");   // 上面prepaid方法存进了session
       // 数据表all_benefit是预充值和积分一起共用的,区分的字段是type：1代表预充值、2代表积分设置、3代表积分现金、4积分物品
       $all_benefit = D("all_benefit");
       $where['business_id'] =  $business_id;
       $where['type'] = 1;  // 手动添加类型
       $pre_rules = $all_benefit->where($where)->order("account asc")->select();

       // 传递过来的预充值额
       $prepaid = $translate_prepaid;
       // 最后的充值额
       $last_prepaid = $prepaid;
       // 开启了，循环遍历出数据库的预充值规则
       foreach($pre_rules as $key=>$val){
           // 判断是不是最后一个规则，并且那个规则的金额小于等于传递过来的价格
           if($key == count($pre_rules)-1 && $val['account'] <= $prepaid){
               // 规则的金额小于等于传递过来的充值额  最后的充值额就等于传递过来的加上送的
               $last_prepaid = $prepaid + $val['benefit'];
               // 查询出该会员当前在数据库有多少余额
               $vip = D("vip");
               $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
               $all_money = $last_prepaid + $remainder;
               // 更新操作
               $total['remainder'] = $all_money;      // 最后的充值额+余额
               $res = $vip->where(array("openid"=>$openid))->save($total);

               // 在prepaid_order表更新各种优惠
               $return = $this->update_benefit_in_order($order_sn,$remainder,$val['id'],$val['account'],$val['benefit'],$last_prepaid);

               if($res === false){
                   // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
                   // prepaid_callback_fail    order_sn、problem_table
                   $prepaid_callback_fail = D("prepaid_callback_fail");
                   $add2['order_sn'] = $order_sn;
                   $add2['problem_table'] = "vip";
                   $prepaid_callback_fail->add($add2);
               }
               break;
           }

           // if判断，如果满足则执行相应的规则（基于一个前提：不是最后一条规则的情况下）
           if($val['account'] <= $prepaid){    // 如果当前规则的金额小于等于传递过来的预充值额
               $temp = $pre_rules[$key+1]['account'];      // 当前规则的下一条规则
               if($temp>$prepaid){                         // 当前规则<当前预充值额<当前规则的下一条规则
                   $last_prepaid = $prepaid + $val['benefit'];       // 就取当前规则的优惠
                   // 查询出该会员当前在数据库有多少余额
                   $vip = D("vip");
                   $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
                   $all_money = $last_prepaid + $remainder;
                   $total['remainder'] = $all_money;

                   $res = $vip->where(array("openid"=>$openid))->save($total);

                   // 在prepaid_order表更新各种优惠
                   $return = $this->update_benefit_in_order($order_sn,$remainder,$val['id'],$val['account'],$val['benefit'],$last_prepaid);

                   if($res === false){
                       // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
                       // prepaid_callback_fail    order_sn、problem_table
                       $prepaid_callback_fail = D("prepaid_callback_fail");
                       $add2['order_sn'] = $order_sn;
                       $add2['problem_table'] = "vip";
                       $prepaid_callback_fail->add($add2);
                   }
                    break;
               }
           }elseif($val['account'] > $prepaid){
               // 连第一条规则（最小金额的规则）都不满足  那就正常处理
               $vip = D("vip");
               $remainder = $vip->where(array("openid"=>$openid))->getField("remainder");
               // 也就是直接将充值额累加到数据库余额中
               $prepaid = $translate_prepaid;
               $total = array('remainder' => $prepaid + $remainder);
               $res = $vip->where(array("openid"=>$openid))->save($total);

               // 在prepaid_order表更新各种优惠
               $return = $this->update_benefit_in_order($order_sn,$remainder,0,0,0,$prepaid);

               if($res === false){
                   // 如果更新会员余额信息失败，就将此错误存储到一个错误表里面
                   // prepaid_callback_fail    order_sn、problem_table
                   $prepaid_callback_fail = D("prepaid_callback_fail");
                   $add2['order_sn'] = $order_sn;
                   $add2['problem_table'] = "vip";
                   $prepaid_callback_fail->add($add2);
               }
               break;
           }
       }
   }

    /**
     * 更新获得优惠后的对应的优惠详情
     * @param $order_sn
     * @param $relation_id
     * @param $account
     * @param $benefit
     * @param $finall_benefit
     * @return bool
     */
    public function update_benefit_in_order($order_sn,$origin_remainder,$relation_id,$account,$benefit,$finall_benefit){
        $where['order_sn'] = $order_sn;
        $data['origin_remainder'] = $origin_remainder;
        $data['relation_rule_id'] = $relation_id;
        $data['account'] = $account;
        $data['benefit'] = $benefit;
        $data['finall_benefit'] = $finall_benefit;

        $vip_id = D('prepaid_order')->where(array('order_sn'=>$order_sn))->getField('vip_id');
        $finall_remainder = D('vip')->where(array('id'=>$vip_id))->getField('remainder');
        $data['finall_remainder'] = $finall_remainder;  // 客户最后的余额
        $rel = D('prepaid_order')->where($where)->save($data);
        if($rel !== false){
            return true;
        }else{
            return false;
        }
    }

    # 余额明细
    public function touchBalance()
    {
        # 并且去订单表获取数据（指定会员使用了余额支付）
        $order = D("order");
        $condition['vip_id'] = session("USER.id");
        $condition['pay_type'] = 4;
        // 订单的状态（0待支付，1已接单，2未接单，3已支付，4未配送，5配送中，6未收货，7已收货，8未评价，9已评价,10已删除,11请取餐,12核销）
        // 0，1，3，10，11，12 这几种
        $condition['order_status'] = array("in","3,11,12");     // 注意：如果后面使用了大于3的其他的状态，就要在这里加
//        $User->where('order_status>=3 AND order_status<>10')->select();

        // 过去一年的记录
        $current_year = Date("Y");
        $current_month = Date("m");

        $last_year = Date("Y")-1;

        //查询上一年
        $previous_year = array();
        for($i = 12;$i>= $current_month;$i--){
            //2016-3 开始时间-结束时间
            $begin_time = strtotime($last_year."-".$i."-1 00:00:00");   // 当前月的一号
            $end_time = strtotime($last_year."-".($i+1)."-1 00:00:00")-1;   // 下个月的一号的前一点
            $condition['pay_time'] = array("BETWEEN",array($begin_time,$end_time));
            $previous_year[$i] = $order->where($condition)->order("pay_time desc")->select();
        }

        //查询今年
        $now_year = array();
        for($j = $current_month;$j>= 1;$j--){
            //2016-3 开始时间-结束时间
            $kaishi_time = strtotime($current_year."-".$j."-1 00:00:00");
            $jieshu_time = strtotime($current_year."-".($j+1)."-1 00:00:00")-1;
            $condition['pay_time'] = array("BETWEEN",array($kaishi_time,$jieshu_time));
            $now_year[$j] = $order->where($condition)->order("pay_time desc")->select();
        }

        $consume_detail = array(
            $current_year => $now_year,
            $last_year => $previous_year,
        );
        $this->assign("consume_detail",$consume_detail);
        $this->display();
    }
   /*------------------------预充值结束-----------------------*/

    /*------------------------个人信息开始-------------------------*/
    public function member_info()
    {
        $vip = D("vip");
        if(IS_POST){
            // 判断提交过来的生日跟之前数据库的生日是否一样，不一样则进行更新年龄
            $now_birthday = I("post.birthday");
            $before_birthday = $vip->where(array("id"=>I("post.id")))->getField("birthday");
            if($before_birthday != $now_birthday){
                $year = explode("/",$now_birthday)[0];
                // 当前年份减去出生年份
                $_POST['age'] =  date("Y")-$year;
            }

            if($vip->create(I("post."))){
                if($vip->save() !== false){
                    // 编辑成功
                    $this->success("保存成功");
                }else{
                    // 编辑失败
                    $this->error("保存失败");
                }
            }else{
                $this->error("保存失败");
            }
        }
        // 根据session里面的电话号码来获取用户信息
        $openid = session("USER.openid");
        $info = $vip->where(array("openid"=>$openid))->find();
        $this->assign("info",$info);
        $this->display();
    }

    /*------------------------个人信息结束-----------------------*/


    /*------------------------注册开始-----------------------*/
    //发送短信验证码
    public function sms(){
        $mobile = I("post.mobile");
        $old_or_new = I("post.old_or_new");
        if($mobile){
            // 设置随机数
            $rand = mt_rand(1000,9999);
            // 设置有效时间
            $m = 3;
            // 把加密的内容加密后存入cookie，并设置有效期
            cookie("sms",md5(C("SECURESTR").$rand),$m*60);
            // 读取短信配置信息
            $sms_info = D("sms_vip")->where(array("business_id"=>session("business_id")))->find();
            $msgid = $sms_info['temp_id'];
            $appkey = $sms_info['appkey'];
            $secret = $sms_info['secret'];
            $sign = $sms_info['sign'];
            $template = "{\"msgcode\":\"$rand\"}";
            // 判断是新用户还是老用户，然后调用不同的短信接口
            if($old_or_new == "0"){
                // 0老用户
                $result = alimsg($appkey,$secret,$mobile,$sign,$template,$msgid);
            }else{
                // 1新用户
                $result = sendSms_new($appkey,$secret,$mobile,$sign,$template,$msgid);
            }

            if($result['code'])
            {
                echo 1;
            }else
            {
                echo "发送失败，原因为：$result[msg]";
            }


           // 查询数据库中的短信对接信息
            /*$sms_info = D("sms_vip")->where(array("business_id"=>session("business_id")))->find();
            $msgid = $sms_info['temp_id'];
            $appkey = $sms_info['appkey'];
            $secret = $sms_info['secret'];
            $sign = $sms_info['sign'];
            $template = "{\"msgcode\":\"$rand\"}";

            $result = alimsg($appkey,$secret,$mobile,$sign,$template,$msgid);

            if($result['code'])
            {
                echo 1;
            }else
            {
                echo "发送失败，原因为：$result[msg]";
            }*/
        }
    }
    
    // 注册验证
    public function mobileReg()
    {
        if(IS_POST){
            $Vip =  D("Vip");
            // 通过用户输入的：手机、生日(年龄)
            // 短信验证
            if(cookie("sms") != md5(C("SECURESTR").I("post.smsCode"))){
                $this->error("短信验证错误");
            }

            // 删除cookie
            cookie("sms",null);

            // 年龄要通过计算得出
            // 当前年份减去出生年份
            $year = explode("/",$_POST['birthday'])[0];
            $_POST['age'] =  date("Y")-$year;

            // 关联代理ID
            $_POST['business_id'] = session("business_id");

            // 将传递过来的字符串拆成数组
            $arr = explode("|",I("post.arr"));
            /*Array
            (
                [0] => 888
                [1] => tom
                [2] => 0
                [3] => 头像地址
            )
            */
            // openid、昵称、性别、头像
            $_POST['openid'] = $arr[0];
            $_POST['username'] = $arr[1];   // 数据表是username，但是微信那边是nickname
            $_POST['sex'] = $arr[2];
            $_POST['headimgurl'] = $arr[3];

            // 再加上表单那边填写的手机号，生日，就跟数据库中的vip表中的字段吻合了

            // 数据入库
            if($Vip->create()){
                if($id = $Vip->add()){
                    // 设置session
                    $openid = $_POST['openid'];
                    session("USER",array(
                        'id'=>$id,
                        'openid'=> $openid
                    ));
                    // 跳转到用户中心
                    $this->success('',U("member/index"));
                }else{
                    $this->error("注册失败");
                }
            }else{
                $this->error("注册失败");
            }

        }

    }
    /*------------------------注册结束-----------------------*/


    /*------------------------预充值支付开始-----------------------*/
    public function pre_pay(){
        if(IS_GET){
            $this->assign("order_sn",I("get.order_sn"));
            $this->assign("total_amount",I("get.total_amount"));
            $this->display("pre_pay");
            exit;
        }

        // 生成订单信息再去支付
        $vip_id = session("USER.id");

        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间
        $condition1['add_time'] = array("between",array($start,$end));     //开启时间与结束时间之间
        $condition1['vip_id'] = $vip_id;     //会员id

        $prepaid_order = D("prepaid_order");
        $num = $prepaid_order->where($condition1)->count();        //两时间之间的订单数
        $order_sn = "DC".str_pad($vip_id,5,"0",STR_PAD_LEFT).date("ymdHis",time()).str_pad($num+1,5,"0",STR_PAD_LEFT);//订单号，$num+1表示同一个会员最新一订单

        $add_time = time();            //下单时间
        $total_amount = I("post.account");         //订单总价
        $condition2['order_sn'] = $order_sn; //订单号
        $condition2['add_time'] = $add_time; //下单时间
        $condition2['total_amount'] = $total_amount;  //订单总价
        $condition2['vip_id'] = $vip_id;  //会员ID

        $result = $prepaid_order->data($condition2)->add();//增加一条订单

        if($result){
            $r_data["order_sn"] = $order_sn;
            $r_data["total_amount"] = $total_amount;
            $returnData["code"] = 1;
            $returnData["msg"] = "下单成功";
            $returnData['data'] = $r_data;
            exit(json_encode($returnData));

        }else{
            $returnData["msg"] = "预充值失败";
            exit(json_encode($returnData));
        }
    }
    /*------------------------预充值支付结束-----------------------*/
    
    # 余额支付明细
    public function remainder_detail(){
        $order = D("order");
        $condition['vip_id'] = session("USER.id");
        // 指定会员，余额支付，订单状态
        $condition['pay_type'] = 4;
        // 订单的状态（0待支付，1已接单，2未接单，3已支付，4未配送，5配送中，6未收货，7已收货，8未评价，9已评价,10已删除,11请取餐,12核销）
        // 0，1，3，10，11，12 这几种
        $condition['order_status'] = array("in","3,11,12");     // 注意：如果后面使用了大于3的其他的状态，就要在这里加
//        $User->where('order_status>=3 AND order_status<>10')->select();
        $pay_detail = $order->where($condition)->select();
        $this->assign("pay_detail",$pay_detail);
        $this->display("");
    }
}