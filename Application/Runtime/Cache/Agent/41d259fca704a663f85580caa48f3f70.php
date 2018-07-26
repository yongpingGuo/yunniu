<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

	<!-- Bootstrap 核心 CSS 文件 -->
	<link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
	<!-- admin CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/base.css">
	<!-- admin CSS 文件 -->
	<link rel="stylesheet" href="/Public/css/agent.css">
	<!-- HTML5 Shim 和 Respond.js 用于让 IE8 支持 HTML5元素和媒体查询 -->	
	<!--[if lt IE 9]>	
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->

	<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
	<script src="/Public/js/jquery-3.1.0.min.js"></script>
	<!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
	<script src="/Public/bootstrap/js/bootstrap.min.js"></script>

	<!-- layer文件 -->
	<script src="/Public/layer/layer.js"></script>
	<title>方派点餐系统代理后台</title>
</head>


<body>
    <section class="container-fluid" id="comment_list">
        <div class="main-content">
            <div class="store-head">
                <button class="btn-black add-agent" onclick="addStore()">新增店铺</button>
                <form action="/index.php/agent/Store/store" method="get">
                    <input type="text" placeholder="请输入店铺名称" name="restaurant_name_key">
                    <input type="submit" name="submit" value="搜索" style="padding-left: 20px;padding-right: 20px;" />
                </form>
                <label>
                    <input type="radio" name="store" id="0" class="radio-circle store_type" <?php if($type==0 ) echo checked?> >
                    <i></i>
                    <span>单店铺</span>
                </label>
                <label>
                    <input type="radio" name="store" id="1" class="radio-circle store_type" <?php if($type==1 ) echo checked?>>
                    <i></i>
                    <span>多店铺</span>
                </label>
            </div>
            <div id="mytable">
                <table class="agent-table table-hover">
                    <thead>
                        <tr class="text-center">
                            <td>序号</td>
                            <td>店铺名称</td>
                            <td>店铺区域</td>
                            <td>店铺详细地址</td>
                            <td>店铺管理员</td>
                            <td>外卖电话1</td>
                            <td>外卖电话2</td>
                            <td>操作</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(is_array($Arrlist2)): $i = 0; $__LIST__ = $Arrlist2;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                                <td><?php echo ($i); ?></td>
                                <td><?php echo ($vo["restaurant_name"]); ?></td>
                                <td><?php echo ($vo["city3_name"]); ?></td>
                                <td>
                                    <span class="store-address">
                                    <?php echo ($vo["address"]); ?>
                                </span>
                                </td>
                                <td><?php echo ($vo["login_account"]); ?></td>
                                <td><?php echo ($vo["telephone1"]); ?></td>
                                <td><?php echo ($vo["telephone2"]); ?></td>
                                <td>
                                    <button class="btn btn-black" data-toggle="modal" data-target="#storeModal" onclick="modify_store(<?php echo ($vo["restaurant_id"]); ?>)">编辑</button>
                                    <button class="btn btn-default" onclick="delstore(<?php echo ($vo["restaurant_id"]); ?>)">删除</button>
                                </td>
                            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                    </tbody>
                </table>
                <div>
                    <ul class="pagination" id="detail-page">
                        <?php echo ($page); ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <div class="modal fade storeModal" id="storeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-head" id="myModalLabel">新增店铺</div>
                <form action="javascript:void(0)" id="add_restaurant">
                    <input type="hidden" name="form_id">
                    <input type="hidden" name="restaurant_id">
                    <input type="hidden" value="lat" id="lat">
                    <input type="hidden" value="lng" id="lng">
                    <input id="latLng" type="hidden" value="">
                    <input type="hidden" name="session_id" value="<?php echo (session('business_id')); ?>">
                    <table class="table-condensed">
                        <tbody>
                            <tr>
                                <td class="text-right">店铺名称:</td>
                                <td>
                                    <input type="text" id="storeName" name="restaurant_name" placeholder="店铺名称">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">地区:</td>
                                <td>
                                    <select name="province" id="province" onchange="changercity()">
                                        <option value="0">请选择</option>
                                    </select>
                                    <select name="city" id="city" onchange="changerarea()">
                                        <option value="0">请选择</option>
                                    </select>
                                    <select name="area" id="area" onchange="searchAddress()">
                                        <option value="0">请选择</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">详细地址:</td>
                                <td>
                                    <input type="text" id="address" name="address" placeholder="详细地址">
                                    <button class="btn btn-black" onclick="searchAddress()">地图定位</button>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <div id="map_container" style="width: 320px;height: 300px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">外卖电话:</td>
                                <td>
                                    <input type="text" id="telephone1" name="telephone1" placeholder="外卖电话1">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">外卖电话:</td>
                                <td>
                                    <input type="text" id="telephone2" name="telephone2" placeholder="外卖电话2">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">帐号:</td>
                                <td>
                                    <input type="text" id="login_account" name="login_account" placeholder="管理员帐号">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">密码:</td>
                                <td>
                                    <input type="password" id="password" name="password" placeholder="管理员密码">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right">确认密码:</td>
                                <td>
                                    <input type="password" id="passwords" name="passwords" placeholder="管理员密码">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-center">
                        <button type="button" class="btn btn-black" onclick="commit()">提交</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                        <input type="reset" name="reset" style="display: none;">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=3.0&ak=jKAGB1FgXBx1oFIY9uB8xZxVb8yvhzMs"></script>
    <!-- <script charset="utf-8" src="http://map.qq.com/api/js?v=2.exp"></script> -->
    <script type="text/javascript" src="/Public/js/BusinessManager.js"></script>
    <!-- <script type="text/javascript" src="/Public/js/Agent/store_qq_map.js"></script> -->
    <script type="text/javascript" src="/Public/js/Agent/store.js?2018020101"></script>
</body>

</html>