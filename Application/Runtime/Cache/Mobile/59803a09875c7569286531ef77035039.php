<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- css样式表 -->
    
    <link rel="stylesheet" type="text/css" href="/Public/bootstrap/css/bootstrap.min.css">

    <link rel="stylesheet" type="text/css" href="/Public/iconfont/iconfont.css">
    <link rel="stylesheet" type="text/css" href="/Public/css/order.css?20180712">
    <title>自助点餐系统</title>
</head>
    <!-- 主体部分 -->
    

    <body class="order-body clear">
    	<div id="ng-app" ng-app="menu" class="order-content" ng-controller="menuShow" ng-controller="ScrollController">
	        <header id="restaurantInfo" data-restaurant_id="<?php echo ($_GET['restaurant_id']); ?>" data-desk_code="<?php echo ($_GET['desk_code']); ?>">
	        </header><!-- <img class="classify-icon" src="/Public/images/avatar.png"> -->
			<div class="order-content">
	            <div class="classify-bd" id="navbarExample">
	                <ul class="nav">
	                    <li ng-repeat="item in classify">
	                        <a class="classify-item" ng-href="#classify{{item.food_category_id}}" ng-click="goUrl('#classify'+item.food_category_id)">
	                            <img class="classify-icon" ng-src="{{item.img_url}}">
	                            <div class="classify-name" ng-bind="item.food_category_name"></div>
	                        </a>
	                    </li>
	                </ul>
	            </div>
	            <section lazy-container class="dish-list" data-spy="scroll" data-target="#navbarExample">
	                <div ng-attr-id="classify{{item.food_category_id}}" ng-repeat="item in classify">
	                        <div ng-repeat="infoItem in info[item.food_category_id]" class="dish-item flex-content vertical-flex">
	                            <div class="img-div">
                                    <img ng-if="infoItem.is_shutdown==0" lazy-src="/{{infoItem.food_img}}" class="dish-icon lazy" ng-style="wh_num" data-toggle="modal" data-target="#foodModal" data-food_id="{{infoItem.food_id}}" data-have_attribute="infoItem.have_attribute>0?1:2" ng-click="findfoodinfo($event)">
                                    <img ng-if="infoItem.is_shutdown==1" lazy-src="/{{infoItem.food_img}}" class="dish-icon lazy" ng-style="wh_num" data-target="#foodModal" data-food_id="{{infoItem.food_id}}" data-have_attribute="infoItem.have_attribute>0?1:2">
                                    <!--是否显示售罄  ng-if="" -->
                                    <img ng-if="infoItem.is_shutdown==1 || foods_num_day==0" src="/Public/images/sell_out.png" class="sell-out-img">
                                </div>
	                            <div class="dish-right flex-main">
	                                <div class="dish-name" ng-bind="infoItem.food_name"></div>
	                                <div class="dish-price">
	                                    <span>&yen;</span>
	                                    <b class="price-num" ng-bind="infoItem.food_price"></b>
	                                </div>
	                                <button ng-if="infoItem.have_attribute>0" ng-class="infoItem.is_shutdown==1||foods_num_day==0?'selectAttr-btn disabled':'selectAttr-btn'" data-toggle="modal" data-target="#foodModal" data-food_id="{{infoItem.food_id}}" ng-disabled="infoItem.is_shutdown==1||foods_num_day==0?true:false" data-have_attribute="1" ng-click="findfoodinfo($event,'1')">选规格</button>
	                                <button ng-if="infoItem.have_attribute<=0" class="plus-btn" data-toggle="modal" data-food_id="{{infoItem.food_id}}" ng-disabled="infoItem.is_shutdown==1||foods_num_day==0?true:false" data-have_attribute="0" ng-click="findfoodinfo($event,'0')">
	                                    <i ng-class="infoItem.is_shutdown==1||foods_num_day==0?'iconfont icon-plus disabled':'iconfont icon-plus'"></i>
	                                </button>
	                            </div>
	                        </div>
	                    </div>
	            </section>
	        </div>
        </div>
        <footer class="order-footer flex-content vertical-flex">
            <i class="iconfont icon-up"></i>
            <div class="order-footer-left flex-main flex-content vertical-flex" onclick="showCart()">
                <i class="iconfont icon-canyin order-footer-icon"></i>
                <span class="order-footer-num" id="numv">0</span>
                <div>
                    <span>&yen;</span>
                    <span class="order-footer-total" id="Total">0.00</span>
                </div>
                <!--<small>（优惠前总价）</small>-->
            </div>
            <button class="order-footer-btn flex-content vertical-flex" onclick="PlaceOrder()">
                <span>选好了</span>
                <i class="iconfont icon-more"></i>
            </button>
        </footer>
        <div class="order-cart">
            <div class="order-cart-content">
                <i class="iconfont icon-down" onclick="showCart()"></i>
                <div class="order-cart-main">
                    <div id="foodlist">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="foodModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" id="modelfood">
            </div>
        </div>
        <input name="isCode" id="isCode" value="<?php echo ($returnData["code"]); ?>" hidden/>
        <input name="urlString" id="urlString" value="<?php echo ($returnData["data"]); ?>" hidden/>
       <!-- <div><input type="button" value="支付" onclick="apliPay()" /></div>-->
    </body>

    <!-- 引入JS -->
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    
    <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script src="/Public/bootstrap/js/bootstrap.min.js"></script>
    <script src="/Public/js/jquery.fly.min.js"></script>
    <script src="/Public/layer/mobile/layer.js"></script>
    <script src="/Public/js/angular.min.js"></script>
    <script src="/Public/js/me-lazyimg.js"></script>
    <script src="/Public/js/Mobile/order.js?v20180507"></script>
    <script type="text/javascript">
        // window.onload = function(){ 
        //     var code = document.getElementById("isCode").value;
        //     var url = document.getElementById("urlString").value;
        //     console.log(code);
        //     console.log(url);

        // 　　if(code==0){
        //         alert("未关注公众号！请先关注！");
        //         window.location.href = url;
        //     }
        // }
		var app = angular.module("menu", ['me-lazyimg']);
		var req = {
		    method: 'POST',//请求的方式
		    url: '/index.php/Mobile/Index/ajaxGetFoodInfo',//请求的地址
		    headers: {
		        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		        'Accept': '*/*'
		    },//请求的头，如果默认可以不写
		    timeout:5000//超时时间
		};
		app.controller('menuShow', function($scope, $http) {
			$http(req).then(function success(response){
//				var s = response.data+"";
//				var str = s.substring(s.indexOf("{"));
//				var data = str;
//				if (typeof str === 'string') {
//					data = JSON.parse(str);
//				}
				var data = response.data;
				$scope.classify = data.info;
				$scope.info = data.food_infos;
                //console.log(data.food_infos);
				$scope.wh_num = {
					'width':'11em',
					'height':'6.6em'
				};
				layer.closeAll('loading');
			}), function error(response) {
				layer.closeAll('loading');
		        layer.open({
					content: '抱歉，出错了',
					skin: 'msg',
					time: 2 //2秒后自动关闭
				})
		    };
		    $scope.findfoodinfo = function(obj,flag){
		        findfoodinfo(obj.currentTarget,flag);
		    }
		});
		app.controller('ScrollController', ['$scope', '$location', '$anchorScroll',
		  function($scope, $location, $anchorScroll) {
		    $scope.goUrl = function(url) {
			    $location.hash(url);
			    $anchorScroll();
		    };
		}]);

    function weixin_pay(order_sn){
        var check=1;
        var myDate = new Date();
        var time=myDate.getHours()+":"+myDate.getMinutes();
        var url=controller_url + "/updateOrder/";
        $.post(url, { use_time: time, use_day: check, order_sn: order_sn }, function(data) {
            if (data.code == 1) {
                layer.msg(data.msg);
                return false;
            }
            callpay();
     	});
    }
//调用微信JS api 支付
        function jsApiCall() {
            console.log(<?php echo ($jsApiParameters); ?>);
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
               <?php if(!empty($jsApiParameters))echo $jsApiParameters.',';?>
                function(res) {
                    WeixinJSBridge.log(res.err_msg);
                    if (res.err_msg == 'get_brand_wcpay_request:cancel') {
                        alert("您已取消了此次支付");
                        location.href = "/index.php/mobile/index/homePage/restaurant_id/<?php echo ($restaurant_id); ?>/business_id/<?php echo ($business_id); ?>/restaurants_id/<?php echo ($restaurants_id); ?>/pay_status/online";
                        return;
                    } else if (res.err_msg == 'get_brand_wcpay_request:fail') {
                        alert(JSON.stringify(res));
                        return;
                    } else if (res.err_msg == 'get_brand_wcpay_request:ok') {
                        var restaurant_id = $("#restaurant_id").val();
                        var desk_code = $("#desk_code").val();
                        //                            location.href="<?php echo U('Order/index', 'restaurant_id=restaurant_id&business_id=business_id');?>";
                        location.href = "/index.php/Mobile/Order/index/restaurant_id/<?php echo ($restaurant_id); ?>/business_id/<?php echo ($business_id); ?>/restaurants_id/<?php echo ($restaurants_id); ?>/";
                    } else {
                        alert("未知错误" + res.error_msg);
                        return;
                    }
                }
            );
        } 

        function callpay() {

            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                } else if (document.attachEvent) {
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            } else {
                jsApiCall();
            }
        }
        // <?php if(!empty($order_sn)) {echo 'window.onload=function(){weixin_pay("'.$order_sn.'");}';}?>
    </script>

</html>