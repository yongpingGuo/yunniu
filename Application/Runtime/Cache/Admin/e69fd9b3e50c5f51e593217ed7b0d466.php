<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <ul id="myTab" class="nav nav-tabs">
        <li class="active">
            <a href="#wxpay" data-toggle="tab" id="wx">{{langData.WeChat[lang]}}</a>
        </li>
        <li><a href="#alipay" data-toggle="tab" id="ali">{{langData.Alipay[lang]}}</a></li>
        <li><a href="#others" data-toggle="tab" id="si">{{langData.bankReceipt[lang]}}</a></li>
        <li><a href="#pay-select" data-toggle="tab" id="state">{{langData.statusSwitch[lang]}}</a></li>
    </ul>
    <div class="pay-content">
        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade in active clearfix" id="wxpay">
                <img src="/Public/images/receipt.jpg" class="pull-right pay-code">
                <form action="javascript:void(0)" id="wxpayForm">
                    <table class="table-condensed">
                        <tbody>
                            <tr>
                                <td>
                                    <span>{{langData.WeChatPayment[lang]}}:</span>
                                    <div class="checkbox-switch">
                                        <?php if(is_array($pay_select)): $i = 0; $__LIST__ = $pay_select;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$pa_vo): $mod = ($i % 2 );++$i; if($pa_vo['s_num'] == 1): if($pa_vo['value'] == 1): ?><input type="checkbox" name="<?php echo ($pa_vo["config_name"]); ?>" class="wx" onchange="is_open('wx',this)" checked>
                                                    <?php else: ?>
                                                    <input type="checkbox" name="<?php echo ($pa_vo["config_name"]); ?>" class="wx" onchange="is_open('wx',this)"><?php endif; endif; endforeach; endif; else: echo "" ;endif; ?>
                                        <label></label>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>{{langData.WeChatBusinessNumber[lang]}}:</td>
                                <td>
                                    <input type="text" value="<?php echo ($wx_config["wxpay_child_mchid"]); ?>" :placeholder="langData.subBusiness[lang]" name="wxpay_child_mchid" id="wxpay_child_mchid">
                                </td>
                                <td>
                                    <span class="section-tips">{{langData.subBusinessTips[lang]}}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.officialAccountAPPID[lang]}}:</td>
                                <td>
                                    <input type="text" value="<?php echo ($wx_config["wxpay_appid"]); ?>" name="wxpay_appid" id="wxpay_appid">
                                </td>
                                <td>
                                    <span class="section-tips">{{langData.officialAccountAPPIDKeyTips[lang]}}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.officialAccountKey[lang]}}:</td>
                                <td>
                                    <input type="text" value="<?php echo ($wx_config["wxpay_appsecret"]); ?>" name="wxpay_appsecret" id="wxpay_appsecret">
                                </td>
                                <td>
                                    <span class="section-tips">{{langData.officialAccountAPPIDKeyTips[lang]}}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
                <div class="container-fluid">
                    <button class="blue-btn" data-paytype="wxpay" onclick="submitPayInfo(this)">{{langData.save[lang]}}</button>
                </div>
                <div class="section-tips">
                    <div>{{langData.note[lang]}}:</div>
                    <div>* {{langData.WeChatDataDockTips1[lang]}}</div>
                    <div>* {{langData.WeChatDataDockTips2[lang]}}</div>
                    <div>* {{langData.WeChatDataDockTips3[lang]}}</div>
                    <div>* {{langData.WeChatDataDockTips4[lang]}}:https://mp.weixin.qq.com/</div>
                    <div>* {{langData.WeChatDataDockTips5[lang]}}:http://kf.qq.com/faq/161223jeuArU161223NVVVj2.html</div>
                </div>
            </div>
            <div class="tab-pane fade" id="alipay">
                <div>
                    <span>{{langData.AlipayPayment[lang]}}:</span>
                    <div class="checkbox-switch">
                        <?php if(is_array($pay_select)): $i = 0; $__LIST__ = $pay_select;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$pa_vo): $mod = ($i % 2 );++$i; if($pa_vo['s_num'] == 4): if($pa_vo['value'] == 1): ?><input type="checkbox" name="<?php echo ($pa_vo["config_name"]); ?>" class="ali" onchange="is_open('ali',this)" checked>
                                    <?php else: ?>
                                    <input type="checkbox" name="<?php echo ($pa_vo["config_name"]); ?>" class="ali" onchange="is_open('ali',this)"><?php endif; endif; endforeach; endif; else: echo "" ;endif; ?>
                        <label></label>
                    </div>
                </div>
                <div>
                    <span>{{langData.AlipayPayApply[lang]}}:</span>
                    <a class="blue-btn" target="_blank" href="https://b.alipay.com/settling/index.htm?appId=2017022305833230">{{langData.goToAlipayApply[lang]}}</a>
                </div>
                <div class="section-tips">
                    <div>{{langData.aliApplyTips1[lang]}}:</div>
                    <div class="section-tips-indent">{{langData.aliApplyTips2[lang]}}</div>
                    <div class="section-tips-indent">{{langData.aliApplyTips3[lang]}}</div>
                </div>
                <div>
                    <span>{{langData.alipayPID[lang]}}:</span>
                    <?php if($pid == 0): ?><input type="text" name="aliNumber" id="aliPid">
                        <?php else: ?>
                        <input type="text" name="aliNumber" id="aliPid" value="<?php echo ($pid); ?>"><?php endif; ?>
                    <button target="_blank" class="blue-btn" onclick="changeUrl()">{{langData.officialAuthorization[lang]}}</button>
                    <span class="section-tips">{{langData.alipayPIDTips[lang]}}</span>
                </div>
                <div class="section-tips">
                    <div>{{langData.statement[lang]}}:</div>
                    <div>* {{langData.aliDataDockTips1[lang]}}</div>
                    <div>* {{langData.aliDataDockTips2[lang]}}</div>
                    <div>* {{langData.aliDataDockTips3[lang]}}</div>
                    <div>* {{langData.aliDataDockTips4[lang]}}:https://cshall.alipay.com/enterprise/knowledgeDetail.htm?knowledgeId=201602045710</div>
                </div>
            </div>
            <div class="tab-pane fade" id="others">
                <div class="flex-content">
                    <div>
                        <form action="javascript:void(0)" id="othersForm">
                            <table class="table-condensed">
                                <tbody>
                                    <tr>
                                        <td>{{langData.businessNumber[lang]}}:</td>
                                        <td>
                                            <input type="text" value="<?php echo ($fourth["account"]); ?>" name="account">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{langData.password[lang]}}:</td>
                                        <td>
                                            <input type="text" value="<?php echo ($fourth["pwd"]); ?>" name="pwd">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                        <div class="container-fluid">
                            <button class="blue-btn" data-paytype="others" onclick="submitPayInfos(this)">{{langData.save[lang]}}</button>
                        </div>
                        <div class="section-tips">
                            <div>{{langData.advantage[lang]}}:</div>
                            <div>* {{langData.bankReceiptAdvantage[lang]}}</div>
                            <div>{{langData.disadvantage[lang]}}:</div>
                            <div>* {{langData.bankReceiptDisadvantage[lang]}}</div>
                            <div>{{langData.statement[lang]}}:</div>
                            <div>* {{langData.bankReceiptTips1[lang]}}</div>
                            <div>* {{langData.bankReceiptTips2[lang]}}</div>
                            <div>* {{langData.bankReceiptTips3[lang]}}</div>
                            <div>* {{langData.bankReceiptTips4[lang]}}</div>
                            <div>* {{langData.bankReceiptTips5[lang]}}</div>
                        </div>
                    </div>
                    <div>
                        <img src="/Public/images/receipt.jpg" class="pay-code">
                        <img src="/Public/images/minsheng.jpg" class="pay-code">
                    </div>
                </div>
                
            </div>
            <div class="tab-pane fade" id="pay-select">
                <?php if($pay_mode["mode"] == 1): ?><label>
                        <input type="radio" name="mode" value="1" class="radio-circle mode1" onchange="is_mode(this)" checked>
                        <i></i>
                        <span>{{langData.officialPayment[lang]}}</span>
                    </label>
                    <label>
                        <input type="radio" name="mode" value="2" class="radio-circle mode2" onchange="is_mode(this)">
                        <i></i>
                        <span>{{langData.bankReceipt[lang]}}</span>
                    </label>
                    <?php elseif($pay_mode["mode"] == 2): ?>
                    <label>
                        <input type="radio" name="mode" value="1" class="radio-circle mode1" onchange="is_mode(this)">
                        <i></i>
                        <span>{{langData.officialPayment[lang]}}</span>
                    </label>
                    <label>
                        <input type="radio" name="mode" value="2" class="radio-circle mode2" onchange="is_mode(this)" checked>
                        <i></i>
                        <span>{{langData.bankReceipt[lang]}}</span>
                    </label>
                    <?php else: ?>
                    <label>
                        <input type="radio" name="mode" value="1" class="radio-circle mode1" onchange="is_mode(this)">
                        <i></i>
                        <span>{{langData.officialPayment[lang]}}</span>
                    </label>
                    <label>
                        <input type="radio" name="mode" value="2" class="radio-circle mode2" onchange="is_mode(this)">
                        <i></i>
                        <span>{{langData.bankReceipt[lang]}}</span>
                    </label><?php endif; ?>
                <div class="section-tips section-tips-indent">{{langData.payMethodSuggest[lang]}}</div>
                <div>
                    <span>{{langData.selfServiceCash[lang]}}:</span>
                    <div class="checkbox-switch">
                        <?php if(is_array($pay_select)): $i = 0; $__LIST__ = $pay_select;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$pa_vo): $mod = ($i % 2 );++$i; if($pa_vo['s_num'] == 2): if($pa_vo['value'] == 1): ?><input type="checkbox" name="<?php echo ($pa_vo["config_name"]); ?>" class="cash" onchange="is_open('cash',this)" checked>
                                    <?php else: ?>
                                    <input type="checkbox" name="<?php echo ($pa_vo["config_name"]); ?>" class="cash" onchange="is_open('cash',this)"><?php endif; endif; endforeach; endif; else: echo "" ;endif; ?>
                        <label></label>
                    </div>
                    <span class="section-tips">{{langData.selfServiceCashSuggest[lang]}}</span>
                </div>
                <div class="section-tips">{{langData.selfServiceCashTips[lang]}}</div>
                <div class="row">
                    <?php if(is_array($pay_select)): $i = 0; $__LIST__ = $pay_select;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$pa_vo): $mod = ($i % 2 );++$i;?><div class="col-xs-3">
                            <div class="pay-item">
                                <?php if($pa_vo['s_num'] == 99): ?><p><?php echo ($pa_vo["name"]); ?></p>
                                    <img src="<?php echo ($pa_vo["img"]); ?>">
                                    <?php if($pa_vo['value'] == 1): ?><input type="radio" name="<?php echo ($pa_vo["config_name"]); ?>" value="1" checked onchange="changeStatus(this)">{{langData.on[lang]}}
                                        <input type="radio" name="<?php echo ($pa_vo["config_name"]); ?>" value="0" onchange="changeStatus(this)">{{langData.off[lang]}}
                                        <?php else: ?>
                                        <input type="radio" name="<?php echo ($pa_vo["config_name"]); ?>" value="1" onchange="changeStatus(this)">{{langData.on[lang]}}
                                        <input type="radio" name="<?php echo ($pa_vo["config_name"]); ?>" value="0" checked onchange="changeStatus(this)">{{langData.off[lang]}}<?php endif; endif; ?>
                            </div>
                        </div><?php endforeach; endif; else: echo "" ;endif; ?>
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
    
        <script type="text/javascript">
        var vm = new Vue({
            el: "#lang-content",
            data: {
                lang: language,
                langData: langData
            }
        })
        </script>
    
    <!-- 自定义js -->
    
    <script src="/Public/js/PayInfo.js"></script>
    <script>
    function changeStatus(obj) {
        var value = $(obj).val();
        var config_name = $(obj).attr("name");
        $.ajax({
            url: "/index.php/Admin/dataDock/selectPay",
            data: {
                "value": value,
                "config_name": config_name
            },
            type: "post",
            success: function() {
                layer.msg(vm.langData.success[vm.lang]);
            }
        });
    }

    function is_open(name, obj) {
        var config_name = $(obj).attr("name");
        var cls = "." + name;
        var hschek = $(cls).is(':checked');
        if (hschek) {
            status = 1;
        } else {
            status = 0;
        }
        $.ajax({
            url: "/index.php/Admin/dataDock/selectPay",
            data: {
                "value": status,
                "config_name": config_name
            },
            type: "post",
            success: function() {
                layer.msg(vm.langData.success[vm.lang]);
            }
        });
    }

    function is_mode(obj) {
        var status = $(obj).val();
        $.ajax({
            url: "/index.php/Admin/dataDock/selectMode",
            data: {
                "mode": status
            },
            type: "post",
            success: function() {
                layer.msg(vm.langData.success[vm.lang]);
            }
        });
    }


    function changeUrl() {
        var aliNumber = $("#aliPid").val();
        if (aliNumber) {
            var url = "/index.php/component/test/testCreate/aliNumber/" + aliNumber;
            window.open(url);
        }
    }
    </script>

</body>

</html>