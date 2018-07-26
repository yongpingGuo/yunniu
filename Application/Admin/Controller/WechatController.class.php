<?php
namespace Admin\Controller;
use Think\Controller;
use Gaoming13\WechatPhpSdk\Wechat;
use Gaoming13\WechatPhpSdk\Api;

#微信控制器
Class WechatController extends Controller
{
    protected $appId;
    protected $appSecret;
    
    //微信页面
    public function index()
    {
        // dump(session());die;
        //查询店铺的公众号信息
        $wechat = M('wechat')->where(array('restaurant_id ='.session('restaurant_id')))->find();
        // dump($wechat);
        $this->assign('wechat',$wechat);
        $this->display();
    }

    //上传用于验证服务器的文件
    public function fileUpload()
    {

        if ($_FILES["myfile"]["error"] > 0)
        {
            $return['code'] = 1;
            $return['msg'] = '上传出错,错误码为'.$_FILES["myfile"]["error"];

        }else {

            if (file_exists("./" . $_FILES["myfile"]["name"])){
                $return['code'] = 2;
                $return['msg'] = '文件已存在';
            }else {
                $res = move_uploaded_file($_FILES["myfile"]["tmp_name"], "./" . $_FILES["myfile"]["name"]);
                if($res){
                    $return['code'] = 0;
                }else{
                    $return['code'] = 1;
                    $return['msg'] = '上传失败';
                }
            }
        }

        $this->ajaxReturn($return);

    }

    //微信设置的数据保存和添加
    public function wechatSet()
    {
        $data['type'] = I('post.type');
        $data['name'] = I('post.name');
        $data['appid'] = I('post.appid');
        $data['appsecret'] = I('post.appsecret');
        $data['account'] = I('post.account');
        $data['first_id'] = I('first_id');
        $data['describe'] = I('describe');
        $data['url'] = I('post.url');

        $wechat = M('wechat');
        $exist = $wechat->where(array('restaurant_id = '.session('restaurant_id')))->find();
        if($exist){
            //数据存在的，需要修改
            $res = $wechat->where(array('restaurant_id = '.session('restaurant_id')))->data($data)->save();
            if($res){
                $return['code'] = 0;  //修改成功
            }else{
                $return['code'] = 1;
                $return['msg'] = '修改失败';
            }
        }else{
            //数据不存在，直接插入数据
            $data['restaurant_id'] = session('restaurant_id');
            $res = $wechat->data($data)->add();
            if($res){
                $return['code'] = 2;//添加成功
            }else{
                $return['code'] = 3;
                $return['msg'] = '添加失败';
            }
        }

        $this->ajaxReturn($return);

    }

    //微信服务器验证
    public function valid()
    {
        $wechat = new Wechat(
            array(
                'appId' 		=>	'',
                'token' 		=> 	'myshop',
                'encodingAESKey' =>	''
            )
        );

        // 获取消息
        $msg = $wechat->serve();

        // 回复消息
        if ($msg->MsgType == 'text' && $msg->Content == '你好') {

            $wechat->reply("你也好！");
        }

    }


    //生成自定义菜单数组
    public function menu()
    {
        if(IS_POST){
            $menu = I('post.menu');
            $data['menu_json'] = htmlspecialchars_decode($menu);
            $data['menu_json'] = '{"button":'.$data['menu_json'].'}';
            $res = M('wechat')->where('restaurant_id=%d',$_SESSION['restaurant_id'])->data($data)->save();
            if($res){
                $res1 = $this->menuCreate($data['menu_json']);//生成自定义菜单
                if(empty($res1['0'])){
                    $this->ajaxReturn(array('code'=>'0','msg'=>'数据修改成功并成功生成自定义菜单'));
                }else{
                    $msg = '数据插入成功,生成自定义菜单失败:  errcode:'.$res1['0']->errcode . 'errmsg:'.$res1['0']->errmsg;

                    $this->ajaxReturn(array('code'=>'1','msg'=>$msg));
                }
                $this->ajaxReturn(array('code'=>'0','msg'=>'数据插入成功'));
            }else{
                $this->ajaxReturn(array('code'=>'1','msg'=>'数据插入失败'));
            }
            
        }else{
//            $shop_id = $_SESSION['ad_shop_id'];
//            $shop_link = 'http://myshop.cloudabull.com/index.php/WeiShang/Index/getUserInfo?shop_id='.$shop_id;
//            $agent_shop = 'http://myshop.cloudabull.com/index.php/WeiShang/Agent/getUserInfo?shop_id='.$shop_id.'&type=agent_shop';
//            $agent_index = 'http://myshop.cloudabull.com/index.php/WeiShang/Agent/getUserInfo?shop_id='.$shop_id.'&type=agent_index';
//            $this->assign('shop_link',$shop_link);
//            $this->assign('agent_shop',$agent_shop);
//            $this->assign('agent_index',$agent_index);

            $this->display();
        }

    }


    public function menuAjax()
    {
        $menu = M('wechat')->field('menu_json')->where('restaurant_id=%d',$_SESSION['restaurant_id'])->find();
        $menu = json_decode($menu['menu_json']);
        $this->ajaxReturn($menu);
    }

    /**
     *生成自定义菜单
     */
    public function menuCreate($menu)
    {   $restaurant_id = $_SESSION['restaurant_id'];
        $data = M('wechat')->where('restaurant_id=%d',$restaurant_id)->find();
        $this->appId = $data['appid'];
        $this->appSecret=$data['appsecret'];

        $arr = array(
            'appId' => $this->appId,
            'appSecret' => $this->appSecret,
            'get_access_token' => function(){
                // 用户需要自己实现access_token的返回
                if (empty(S('access_token'))) {

                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
                    $res = $this->curlHttp($url);
                    $arr = json_decode($res,true);
                    $access_token	= $arr['access_token'];
                }else{
                    $access_token = S('access_token');
                }
                return $access_token;
            },
            'save_access_token' => function($token) {
                // 用户需要自己实现access_token的保存
                S('access_token', $token, 7100);
            }
        );
        $api = new Api($arr);
        $res = $api->create_menu($menu);
        return $res;

    }


    /**
     * get方式的CURL
     *string $url
     */
    public function curlHttp($url,$data='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // ssl报错
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST ,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER ,false);
        if(!empty($data)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}