<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <link rel="stylesheet" type="text/css" href="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.min.css">

    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <form id="search_form" action="/index.php/Admin/Sale/exportExcel" method="post">
        <div class="stats-detail-header">
            <div class="item">
                <span>{{langData.cashier[lang]}}:</span>
                <select name="cashier_id" id="cashier_id" class="select-grey">
                    <option value="">{{langData.all[lang]}}</option>
                    <?php foreach ($cashierList as $k => $v): ?>
                    <option value="<?php echo $v['cashier_id'];?>">
                        <?php echo $v['cashier_name'];?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="item">
                <span>{{langData.payMethod[lang]}}:</span>
                <select name="pay_type" class="select-grey">
                    <option value="99">{{langData.all[lang]}}</option>
                    <option value="2">{{langData.WeChat[lang]}}</option>
                    <option value="1">{{langData.Alipay[lang]}}</option>
                    <option value="0">{{langData.cash[lang]}}</option>
                    <option value="4">{{langData.member[lang]}}</option>
                    <option value="5">{{langData.bankReceipt[lang]}}</option>
                </select>
            </div>
            <div class="item">
                <span>{{langData.mealStyle[lang]}}:</span>
                <select name="order_type" class="select-grey">
                    <option value="99">{{langData.all[lang]}}</option>
                    <option value="1">{{langData.eatInShop[lang]}}</option>
                    <option value="2">{{langData.package[lang]}}</option>
                </select>
            </div>
            <div class="item">
                <span>{{langData.orderStatus[lang]}}:</span>
                <select name="refuse" class="select-grey">
                    <option value="99">{{langData.all[lang]}}</option>
                    <option value="0">{{langData.normal[lang]}}</option>
                    <option value="1">{{langData.refund[lang]}}</option>
                </select>
            </div>
            <button class="blue-btn" @click="getOrderInfo" type="button">{{langData.search[lang]}}</button>
            <button class="blue-btn" type="button" onclick="exportway()">
                <span>{{langData.export[lang]}}</span>
                <img src="/Public/images/out.png" class="mini-icon">
            </button>
            <div class="item">
                <input type="radio" name="sortType" id="saleAmount" checked value="1">
                <label for="saleAmount">{{langData.turnover[lang]}}</label>
                <input type="radio" id="food_nameTag" name="sortType" value="2">
                <label for="food_nameTag">{{langData.dishes[lang]}}:</label>
                <input type="text" id="food_name" name="food_name">
            </div>
            <div class="item">
                <span>{{langData.date[lang]}}:</span>
                <input class="selectIcon" type="text" id="startDate" name="startDate" value="<?php echo ($startDate); ?>">
                <span>{{langData.to[lang]}}</span>
                <input class="selectIcon" type="text" id="endtDate" name="endtDate" value="<?php echo ($endDate); ?>">
            </div>
            <div class="item">
                <span>{{langData.time[lang]}}</span>
                <input class="selectIcon" type="text" id="startTime" name="startTime" value="<?php echo ($startTime); ?>">
                <span>{{langData.to[lang]}}</span>
                <input class="selectIcon" type="text" id="endTime" name="endTime" value="<?php echo ($endTime); ?>">
            </div>
        </div>
    </form>
    <div class="clearfix" id="orderInfo">
        <div class="pull-left section stats-detail">
            <div class="section-header text-center">{{langData.orderDetails[lang]}}</div>
            <div class="section-content">
                <div v-show="!dishesSearch">
                    <table class="w100">
                        <tbody>
                            <tr class="text-center">
                                <td></td>
                                <td>{{langData.dateAndTime[lang]}}</td>
                                <td>{{langData.orderNumber[lang]}}</td>
                                <td>{{langData.status[lang]}}</td>
                                <td>{{langData.mealStyle[lang]}}</td>
                                <td>{{langData.payMethod[lang]}}</td>
                                <td>{{langData.originalPrice[lang]}}</td>
                                <td>{{langData.discount[lang]}}</td>
                                <td>{{langData.additionalFee[lang]}}</td>
                                <td class="text-right">{{langData.total[lang]}}</td>
                            </tr>
                            <template v-for="(list,index) in orderList" v-show="orderList.length!==0">
                                <tr class="stats-detail-item">
                                    <td>
                                        <span class="stats-detail-index">{{index+1}}</span>
                                        <button class="stats-detail-btn" @click="open_close('foodDetail'+index,$event)">+</button>
                                    </td>
                                    <td>{{list.add_time}}</td>
                                    <td>{{list.order_sn}}</td>
                                    <td class="stats-detail-status">
                                        <span v-if="list.refuse==0">{{langData.normal[lang]}}</span>
                                        <span v-else>{{langData.refund[lang]}}</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="list.order_type==1">{{langData.eatInShop[lang]}}</span>
                                        <span v-if="list.order_type==2">{{langData.package[lang]}}</span>
                                        <span v-if="list.order_type==3">{{langData.WeChatTakeout[lang]}}</span>
                                    </td>
                                    <td class="text-center">
                                        <!-- 支付方式（0现金，1支付宝，2微信，3未支付,4余额，5第四方支付，6钉钉会员支付） -->
                                        <img v-if="list.pay_type==0" src="/Public/images/cash_icon.png">
                                        <img v-if="list.pay_type==1" src="/Public/images/alipay_icon.png">
                                        <img v-if="list.pay_type==2" src="/Public/images/wechat_icon.png">
                                        <span v-if="list.pay_type==3">{{langData.unpaid[lang]}}</span>
                                        <img v-if="list.pay_type==4" src="/Public/images/VIP.png">                                 
                                        <img v-if="list.pay_type==5" src="/Public/images/card.png">
                                        <img v-if="list.pay_type==6" src="/Public/images/VIP.png">
                                    </td>
                                    <?php if($vo["vip_or_restaurant"] == 1): ?><td class="text-center">{{list.total_amount}}</td>
                                        <td class="text-center">0</td>
                                        <?php else: ?>
                                        <td class="text-center">{{list.original_price}}</td>
                                        <td class="text-center">{{list.benefit_money}}</td><?php endif; ?>
                                    <td class="text-center">{{list.extra_charge}}</td>
                                    <td class="text-right">{{list.total_amount}}</td>
                                </tr>
                                <tr :id="'foodDetail'+index" hidden>
                                    <td></td>
                                    <td colspan="3">
                                        <table class="stats-detail-dishes">
                                            <template v-for="detail in list.food_info">
                                                <tr>
                                                    <td class="stats-detail-name">{{detail.food_name}}</td>
                                                    <td class="stats-detail-num">{{detail.food_num}}{{langData.copies[lang]}}</td>
                                                    <!-- <td class="stats-detail-num" v-if="detail.attribute_list.length!==0">
                                                        <template v-for="attr in detail.attribute_list">
                                                            {{detail.food_price2-attr.food_attribute_price}}{{langData.yuan[lang]}}
                                                        </template>
                                                    </td> -->
                                                    <td class="stats-detail-num">{{detail.food_price2}}{{langData.yuan[lang]}}</td>
                                                    <td class="stats-detail-status">
                                                        <span v-if="list.refuse==0">{{langData.normal[lang]}}</span>
                                                        <span v-else>{{langData.refund[lang]}}</span>
                                                    </td>
                                                </tr>
                                                <tr v-for="attr in detail.attribute_list">
                                                    <td class="stats-detail-attr">{{attr.food_attribute_name}}</td>
                                                    <td class="stats-detail-num">{{detail.food_num}}{{langData.copies[lang]}}</td>
                                                    <td class="stats-detail-num" v-if="attr.food_attribute_price">{{attr.food_attribute_price}}{{langData.yuan[lang]}}</td>
                                                    <td class="stats-detail-status"></td>
                                                </tr>
                                            </template>
                                        </table>
                                    </td>
                                    <td colspan="3"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="text-center">
                        <ul class="pagination" v-show="page>1">
                            <li>
                                <a @click="showPage(currentPage-1)">&laquo;</a>
                            </li>
                            <li v-for="item in page" :class="{'active':item==currentPage}" :key="item" v-if="showText(item)">
                                <a @click="showPage(item)"  v-text="showText(item)">{{item}}</a>
                            </li>
                            <li data-page="next">
                                <a  data-page="next" @click="showPage(currentPage+1)">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-show="dishesSearch">
                    <table class="table-condensed">
                        <tbody>
                            <tr class="text-center">
                                <td></td>
                                <td>{{langData.orderNumber[lang]}}</td>
                                <td>{{langData.dishes[lang]}}</td>
                                <td>{{langData.dateAndTime[lang]}}</td>
                                <td>{{langData.mealStyle[lang]}}</td>
                                <td>{{langData.payMethod[lang]}}</td>
                                <td>{{langData.unitPrice[lang]}}</td>
                                <td>{{langData.quantity[lang]}}</td>
                                <td class="text-right">{{langData.total[lang]}}</td>
                            </tr>
                            <tr v-for="(list,index) in  dishesList">
                                <td>{{index+1}}</td>
                                <td>{{list.order_sn}}</td>
                                <td>
                                    {{list.food_name}}
                                    <span class="danger" v-if="list.f_type==2">({{langData.attributes[lang]}})</span>
                                </td>
                                <td>{{list.add_time|time}}</td>
                                <td class="text-center">
                                    <span v-if="list.order_type==1">{{langData.eatInShop[lang]}}</span>
                                    <span v-if="list.order_type==2">{{langData.package[lang]}}</span>
                                    <span v-if="list.order_type==3">{{langData.WeChatTakeout[lang]}}</span>
                                </td>
                                <td class="text-center">
                                    <img v-if="list.pay_type==0" src="/Public/images/cash_icon.png">
                                    <img v-if="list.pay_type==1" src="/Public/images/alipay_icon.png">
                                    <img v-if="list.pay_type==2" src="/Public/images/wechat_icon.png">
                                    <span v-if="list.pay_type==3">{{langData.unpaid[lang]}}</span>
                                    <img v-if="list.pay_type==4" src="/Public/images/VIP.png">                                 
                                    <img v-if="list.pay_type==5" src="/Public/images/card.png">
                                    <img v-if="list.pay_type==6" src="/Public/images/VIP.png">
                                </td>
                                <td class="text-center">{{list.food_price2/list.food_num}}</td>
                                <td class="text-center">{{list.food_num}}</td>
                                <td class="text-right">{{list.food_price2}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="pull-left">
            <div id="container" style="height:200px"></div>
            <div class="stats-detail-info">
                <div class="flex-content summary-info-item">
                    <div class="flex-main">
                        <span class="summary-info-icon cash"></span>
                        <span>{{langData.cash[lang]}}</span>
                    </div>
                    <span>{{statisData.cash}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <div class="flex-main">
                        <span class="summary-info-icon wechat"></span>
                        <span>{{langData.WeChat[lang]}}</span>
                    </div>
                    <span>{{statisData.wechat}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <div class="flex-main">
                        <span class="summary-info-icon alipay"></span>
                        <span>{{langData.Alipay[lang]}}</span>
                    </div>
                    <span>{{statisData.alipay}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <div class="flex-main">
                        <span class="summary-info-icon member"></span>
                        <span>{{langData.member[lang]}}</span>
                    </div>
                    <span>{{statisData.member}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <div class="flex-main">
                        <span class="summary-info-icon card"></span>
                        <span>{{langData.bankReceipt[lang]}}</span>
                    </div>
                    <span>{{statisData.fourth}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <div class="flex-main">
                        <span class="summary-info-icon member"></span>
                        <span>钉钉会员</span>
                    </div>
                    <span>{{statisData.dingding}}</span>
                </div>
            </div>
            <div class="stats-detail-total">
                <div>{{langData.total[lang]}}:{{statisData.total}}{{langData.yuan[lang]}}</div>
                <div  v-show="!dishesSearch">
                    <div>{{langData.discount[lang]}}:{{statisData.benefit_money_total}}{{langData.yuan[lang]}}</div>
                    <div>{{langData.additionalFee[lang]}}:{{statisData.extra_charge_total}}{{langData.yuan[lang]}}</div>
                    <div v-if="refuse==99">{{langData.revenueAmount[lang]}}:{{statisData.total-statisData.refuse_total}}{{langData.yuan[lang]}}</div>
                </div>
            </div>

            <div class="stats-detail-info">
                <div class="flex-content summary-info-item">
                    <span class="flex-main">{{langData.numberOfOrder[lang]}}:</span>
                    <span>{{statisData.count}}{{langData.list[lang]}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <span class="flex-main">{{langData.RetreatMenuNumber[lang]}}:</span>
                    <span>{{statisData.re_count}}{{langData.list[lang]}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <span class="flex-main">{{langData.retreatCopies[lang]}}:</span>
                    <span>{{statisData.refuse_num}}{{langData.copies[lang]}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <span class="flex-main">{{langData.refundAmount[lang]}}:</span>
                    <span>{{statisData.refuse_total}}{{langData.yuan[lang]}}</span>
                </div>
                <div class="flex-content summary-info-item">
                    <span class="flex-main">{{langData.NumberOfDishes[lang]}}:</span>
                    <span>{{statisData.dishes_data_totle}}{{langData.copies[lang]}}</span>
                </div>
            </div>
        </div>
    </div>

        </div>
        
        
    </div>
    <script src="/Public/js/vue.js"></script>
    <script src="/Public/language.json?v=20180428"></script>
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    <script src="/Public/bootstrap/js/bootstrap.min.js"></script>
    <script src="/Public/layer/layer.js"></script>
    <script src="/Public/js/Admin/common.js"></script>
    
    <script src="/Public/highcharts/highcharts.js"></script>
<script src="/Public/highcharts/exporting.js"></script>
    <script type="text/javascript">
    var vm = new Vue({
        el: "#lang-content",
        data: {
            lang: language,
            langData: langData,
            page:0,
            currentPage:1,
            orderList:[],
            dishesList:[],
            statisData:[],
            refuse:0,
            dishesSearch:false,
        },
        mounted: function() {
            this.getOrderInfo();
        },
        methods: {
            // 页码显示（有省略号）
           showText: function (i) {
                var that = this;
                var index= that.currentPage;
                var sum= that.page;
                if (i < 3 || i > (sum - 2)) { // 前两个和最后两个始终显示
                    return i
                } else if (i <= index + 2 && i >= index) { // 当前页的前一页和后一页始终显示
                    return i
                } else if (i === index + 3 || i === index - 1) { // 当前页的前前页和后后页显示 ...
                    return '...'
                } // 除此之外都不显示
                return false
           },
            getOrderInfo: function () {   
                var that=this;            
                var url="/index.php/Admin/Sale/orderInfoAjax";
                var form = $("#search_form")[0];
                var formData = new FormData(form);
                var temp = $("input[name='sortType']:checked").val();
                if ($("#food_name").val() != "" && temp == 2) { 
                    that.dishesSearch=true;
                    url="/index.php/admin/sale/countFoodSaleAjax";
                }else{
                    that.dishesSearch=false;
                }                
                $.ajax({
                    url: url,
                    data: formData,
                    type: "post",
                    contentType: false,
                    processData: false,
                    async: true,
                    cache: false,
                    beforeSend: function() {
                        layer.open({
                            type: 3,
                            icon: 2,
                            skin: "loading"
                        });
                    },
                    success: function(data) { 
                        layer.closeAll('loading');
                        if(data){
                            that.statisData=data.statisData;

                            if(that.dishesSearch){
                                that.dishesList=data.order_list;
                            } else{
                                var dataList=data.order_list;
                                for(var i in dataList){
                                    var foodInfo=dataList[i].food_info;
                                    for(var j in foodInfo){
                                        var foodItem=foodInfo[j];
                                        if(foodItem.attribute_list.length>0){
                                            var price=foodItem.food_price2;
                                            for(var k in foodItem.attribute_list){
                                                price=price-foodItem.attribute_list[k].food_attribute_price;
                                            }
                                            dataList[i].food_info[j].food_price2=price;
                                        }
                                    }
                                }
                                that.orderList=dataList;
                            }
                            if(data.allpage){
                                that.page=data.allpage;
                            }      
                            if(data.refuse){
                                that.refuse=data.refuse;
                            }        
                            var chart = Highcharts.chart('container', {
                                chart: {
                                    plotBackgroundColor: null,
                                    plotBorderWidth: null,
                                    plotShadow: false,
                                },
                                credits: {
                                    enabled: false
                                },
                                title: {
                                    floating:true,
                                    text: '',
                                    style:{"fontSize": "14px" }
                                },
                                tooltip: {
                                    pointFormat: '{point.percentage:.1f}'
                                },
                                plotOptions: {
                                    pie: {
                                        allowPointSelect: true,
                                        cursor: 'pointer',
                                        dataLabels: {
                                            enabled: false,
                                        },
                                        point: {
                                            events: {
                                                mouseOver: function(e) {  
                                                    chart.setTitle({
                                                        text: e.target.name+ '\t'+ e.target.y
                                                    });
                                                }
                                            }
                                        },
                                    }
                                },
                                series: [{
                                    type: 'pie',
                                    innerSize: '85%',
                                    name: '',
                                    data: [
                                        {name:vm.langData.Alipay[vm.lang],y:that.statisData.alipay,color:'#5897f6'},
                                        {name:"钉钉会员",y:that.statisData.dingding,color:'#ffcc00'},
                                        {name:vm.langData.member[vm.lang],y:that.statisData.member,color:'#ffcc00'},
                                        {name:vm.langData.WeChat[vm.lang],y:that.statisData.wechat,color:'#2ed023'},
                                        {name:vm.langData.bankReceipt[vm.lang],y:that.statisData.fourth, color:'#ff4da6'},
                                        {name:vm.langData.cash[vm.lang],y:that.statisData.cash, color:'red'}
                                    ]
                                }]
                            }, function(c) {
                                // 环形图圆心
                                var centerY = c.series[0].center[1],
                                    titleHeight = parseInt(c.title.styles.fontSize);
                                c.setTitle({
                                    y:centerY + titleHeight/2
                                });
                                chart = c;
                            });
                        }
                        else{
                            layer.msg("none")
                        }

                    },
                    error: function(){
                        layer.closeAll('loading');
                        layer.msg(vm.langData.networkError[vm.lang]);
                    }
                });                
            },
            open_close:function(id,event){
                $("#"+id).toggle();
                var status=$(event.currentTarget).html();
                if(status=="+"){
                    $(event.currentTarget).html("-")
                }
                else{
                    $(event.currentTarget).html("+")
                }
            },
            showPage:function(selectedPage){
                if(selectedPage< 1) { 
                    selectedPage=1 ;
                } else if(selectedPage> this.page) { 
                    selectedPage = this.page; 
                }   
                this.currentPage=selectedPage; 
                var that=this;
                var form = $("#search_form")[0];
                var formData = new FormData(form);
                $.ajax({
                    url: "/index.php/Admin/Sale/ajaxPageAjax?page="+selectedPage,
                    data: formData,
                    type: "post",
                    contentType: false,
                    processData: false,
                    async: true,
                    cache: false,
                    beforeSend: function() {
                        layer.open({
                            type: 3,
                            icon: 2,
                            skin: "loading"
                        });
                    },
                    success: function(data) {  
                        that.page=data.allpage;              
                        that.orderList=data.orderInfo;                     
                        layer.closeAll('loading');
                    }
                })
            }
        }

    })
    </script>

    <!-- 自定义js -->
    
    <script src="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.min.js"></script>
<script src="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.zh-CN.js"></script>
<script src="/Public/bootstrap-datetimepicker-master/bootstrap-datetimepicker.zh-TW.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var dateLang=vm.lang;
		$("#form_date").datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		});
		$("#form_starttime").datetimepicker({
		    format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		});
		$("#form_endtime").datetimepicker({
		    format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		});
		$('#startDate').datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		}).on("click",function(ev){
		    $(this).datetimepicker("setEndDate", $("#endtDate").val());
		});
		$('#endtDate').datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#startDate").val());
		});

		$('#discount_startDate').datetimepicker({
		    format:'yyyy-mm-dd hh:ii:00',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    language:  dateLang
		}).on("click",function(ev){
		    $(this).datetimepicker("setEndDate", $("#discount_endtDate").val());
		});
		$('#discount_endtDate').datetimepicker({
		    format:'yyyy-mm-dd hh:ii:00',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#discount_startDate").val());
		});

		$('#startTime').datetimepicker({
		    format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#startDate").val());
		});
		$('#endTime').datetimepicker({
		   format:'hh:ii:00',
		    autoclose: true,
		    startView: "hour", //选择时分秒 
		    language:  dateLang
		}).on("click", function (ev) {
		    $(this).datetimepicker("setStartDate", $("#endtDate").val());
		});


		$("#device_start_time").datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		});

		$("#device_end_time").datetimepicker({
		    format:'yyyy-mm-dd',
		    todayBtn: true,
		    autoclose: true,
		    todayHighlight: true,
		    minView: "month", //选择日期后，不会再跳转去选择时分秒 
		    language:  dateLang
		});
	});
</script>
    <script type="text/javascript">
        function exportExcel() {
            var form = $("#search_form")[0];
            var formDate = new FormData(form);
            $.ajax({
                type: "post",
                url: "/index.php/Admin/Sale/exportExcel",
                data: formDate,
                dataType: "json",
                contentType: false,
                processData: false,
                async: false,
                cache: false,
                success: function(msg) {
                    console.log("导出成功");
                },
                error: function() {
                    console.log("访问出错");
                }
            });
        }

        // 导出
        function exportway() {
            var value = $("input[name='sortType']:checked").val();
            if (value == 1) {
                $("#search_form").attr('action', '/index.php/Admin/Sale/exportExcel');
            } else {
                $("#search_form").attr('action', '/index.php/Admin/Sale/exportExcel1');
            }
            $("#search_form").submit();
        }
    </script>

</body>

</html>