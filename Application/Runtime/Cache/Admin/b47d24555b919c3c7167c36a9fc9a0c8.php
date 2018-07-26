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
            
    <ul class="nav nav-tabs">
        <li class="active">
            <a href="<?php echo U('Restaurant/receipt');?>">{{langData.customerTicketSet[lang]}}</a>
        </li>
        <li>
            <a href="<?php echo U('DataDock/printer');?>">{{langData.kitchenPrintSet[lang]}}</a>
        </li>
    </ul>
    <section class="section">
        <div class="section-header">{{langData.customerTicketTemplate[lang]}}</div>
        <div class="section-content receipt-content">
            <div class="clearfix">
                <div class="pull-left receipt-left">
                    <div class="section-tips text-center">{{langData.ticketTips[lang]}}</div>
                    <div class="receipt">
                        <input id="restaurant_id" name="restaurant_id" value="<?php echo ($restaurant_id); ?>" type="hidden">
                        <?php if($restaurant["top_logo"] != '' ): ?><div class="receipt-logo" id="top_logo">
                                <img src="<?php echo ($top_logo_url); ?>">
                            </div><?php endif; ?>
                        <div class="receipt-larger" id="restaurant_name"><?php echo ($restaurant["restaurant_name"]); ?></div>
                        <div>{{langData.time[lang]}}：2016-09-20 15:30:20</div>
                        <div>{{langData.orderNumber[lang]}}：DC1CCAE33581B11529111</div>
                        <div class="flex-content recepir-hr">
                            <div class="flex-main">{{langData.dishName[lang]}}</div>
                            <div class="receipt-num">{{langData.quantity[lang]}}</div>
                            <div class="receipt-price">{{langData.price[lang]}}</div>
                        </div>
                        <div class="flex-content">
                            <div class="flex-main">{{langData.dish1[lang]}}</div>
                            <div class="receipt-num">1</div>
                            <div class="receipt-price">15.00</div>
                        </div>
                        <div class="flex-content">
                            <div class="flex-main">{{langData.CocaCola[lang]}}</div>
                            <div class="receipt-num">1</div>
                            <div class="receipt-price">3.00</div>
                        </div>
                        <div class="text-right recepir-hr">{{langData.total[lang]}}：18.00</div>
                        <div class="text-right">{{langData.paymentTypes[lang]}}：{{langData.WeChat[lang]}}</div>
                        <div id="order_type" class="receipt-larger recepir-hr">{{langData.orderType[lang]}}：{{langData.eatInShop[lang]}}</div>
                        <div id="take_num" class="receipt-larger recepir-hr"><?php echo ($restaurant["take_num"]); ?>:1001</div>
                        <div id="pay_prompt" class="text-center"><?php echo ($restaurant["pay_prompt"]); ?></div>
                        <div id="pay_num" class="receipt-larger recepir-hr"><?php echo ($restaurant["pay_num"]); ?>:888</div>
                        <div id="pay_prompt2" class="text-center"><?php echo ($restaurant["pay_prompt2"]); ?></div>
                        <div id="desk_num" class="receipt-larger recepir-hr"><?php echo ($restaurant["desk_num"]); ?>:666</div>
                        <div id="forward_prompt" class="text-center"><?php echo ($restaurant["forward_prompt"]); ?></div>
                        <hr class="recepir-hr">
                        <div id="address">{{langData.address[lang]}}:<?php echo ($restaurant["address"]); ?></div>
                        <div id="restaurant_phone">{{langData.tel[lang]}}:<?php echo ($restaurant["telephone1"]); ?></div>
                        <div id="take_out_phone">{{langData.takeAwayTel[lang]}}:<?php echo ($restaurant["telephone2"]); ?></div>
                        <div id="subscription">{{langData.officialOrder[lang]}}:<?php echo ($restaurant["subscription"]); ?></div>
                        <div id="down_prompt" class="text-center">
                            <b><?php echo ($restaurant["down_prompt"]); ?></b>
                        </div>
                        <?php if($restaurant["next_logo"] != '' ): ?><div class="receipt-logo" id="next_logo">
                                <img src="<?php echo ($next_logo_url); ?>">
                            </div><?php endif; ?>
                    </div>
                </div>
                <div class="pull-left receipt-info">
                    <form action="javascript:void(0)" id="restaurant_form2">
                        <table>
                            <tbody>
                                <tr>
                                    <td>{{langData.topLogo[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <input type="checkbox">
                                            <?php if($restaurant_bill["top_logo"] == 1): ?><input type="checkbox" name="name" class="top_logo" onchange="is_open('top_logo',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="name" class="top_logo" onchange="is_open('top_logo',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="file" id="top_file" style="display:none">
                                        <button class="blue-btn" onclick="F_Open_dialog(1)">{{langData.upload[lang]}}</button>
                                        <!--<input type="file" onchange="previewImage(event)">-->
                                        <span class="section-tips">{{langData.ticketLogoTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.bottomLogo[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["next_logo"] == 1): ?><input type="checkbox" name="name" class="next_logo" onchange="is_open('next_logo',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="name" class="next_logo" onchange="is_open('next_logo',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="file" id="next_file" style="display:none">
                                        <button class="blue-btn" onclick="F_Open_dialog(2)">{{langData.upload[lang]}}</button>
                                        <!--<input type="file" onchange="previewImage(event)">-->
                                        <span class="section-tips">{{langData.ticketLogoTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.restaurantName[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["restaurant_name"] == 1): ?><input type="checkbox" name="name" class="restaurant_name" onchange="is_open('restaurant_name',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="name" class="restaurant_name" onchange="is_open('restaurant_name',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="restaurant_name" value="<?php echo ($restaurant["restaurant_name"]); ?>">
                                        <span class="section-tips">{{langData.sevenWords[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.takeMealNumber[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["take_num"] == 1): ?><input type="checkbox" name="take_num" class="take_num" onchange="is_open('take_num',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="take_num" class="take_num" onchange="is_open('take_num',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                        <!-- <?php if($restaurant_bill["take_num"] == 1): ?><label><input type="radio" name="take_num" value="1"  checked onchange="is_open('take_num',this)">
                                            开启</label>
                                        <label><input type="radio" name="take_num" value="0" onchange="is_open('take_num',this)">
                                            关闭</label>
                                        <?php else: ?>
                                        <label><input type="radio" name="take_num" value="1" onchange="is_open('take_num',this)" >
                                            开启</label>
                                        <label><input type="radio" name="take_num" value="0" onchange="is_open('take_num',this)" checked>
                                            关闭</label><?php endif; ?> -->
                                    </td>
                                    <td>
                                        <input type="text" name="take_num" value="<?php echo ($restaurant["take_num"]); ?>" :placeholder="langData.takeMealNumber[lang]">
                                        <span class="section-tips">{{langData.takeMealNumberTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <span>{{langData.hint[lang]}}:</span>
                                    </td>
                                    <td>
                                        <input type="text" name="pay_prompt" value="<?php echo ($restaurant["pay_prompt"]); ?>" class="input-lager">
                                        <span class="section-tips">{{langData.fifteenWords[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.restaurantNumber[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["qrcode"] == 1): ?><input type="checkbox" name="take" class="qrcode" onchange="is_open('qrcode',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="take" class="qrcode" onchange="is_open('qrcode',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                        <!-- <?php if($restaurant_bill["qrcode"] == 1): ?><label><input type="radio" name="take" value="1" checked onchange="is_open('qrcode',this)">
                                            开启</label>
                                        <label><input type="radio" name="take" value="0" onchange="is_open('qrcode',this)">
                                            关闭</label>
                                        <?php else: ?>
                                        <label><input type="radio" name="take" value="1" onchange="is_open('qrcode',this)" >
                                            开启</label>
                                        <label><input type="radio" name="take" value="0" checked onchange="is_open('qrcode',this)">
                                            关闭</label><?php endif; ?> -->
                                    </td>
                                    <td>
                                        <input type="text" name="desk_num" value="<?php echo ($restaurant["desk_num"]); ?>">
                                        <span class="section-tips">{{langData.restaurantNumberTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>{{langData.hint[lang]}}:</td>
                                    <td>
                                        <input type="text" name="forward_prompt" value="<?php echo ($restaurant["forward_prompt"]); ?>">
                                        <span class="section-tips">{{langData.fifteenWords[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.paymentNumber[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["pay_num"] == 1): ?><input type="checkbox" name="pay_num" class="pay_num" onchange="is_open('pay_num',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="pay_num" class="pay_num" onchange="is_open('pay_num',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="pay_num" value="<?php echo ($restaurant["pay_num"]); ?>">
                                        <span class="section-tips">{{langData.paymentNumberTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>{{langData.hint[lang]}}:</td>
                                    <td>
                                        <input type="text" name="pay_prompt2" value="<?php echo ($restaurant["pay_prompt2"]); ?>">
                                        <span class="section-tips">{{langData.fifteenWords[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.packageHall[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["order_type"] == 1): ?><input type="checkbox" name="order_type" class="order_type" onchange="is_open('order_type',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="order_type" class="order_type" onchange="is_open('order_type',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="section-tips receipt-info-tips">{{langData.packageHallTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.address[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["address"] == 1): ?><input type="checkbox" name="address" class="address" onchange="is_open('address',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="address" class="address" onchange="is_open('address',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="address" value="<?php echo ($restaurant["address"]); ?>">
                                        <span class="section-tips">{{langData.addressTips[lang]}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.tel[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["restaurant_phone"] == 1): ?><input type="checkbox" name="restaurant_phone" class="restaurant_phone" onchange="is_open('restaurant_phone',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="restaurant_phone" class="restaurant_phone" onchange="is_open('restaurant_phone',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="telephone1" value="<?php echo ($restaurant["telephone1"]); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.takeAwayTel[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["take_out_phone"] == 1): ?><input type="checkbox" name="take_out_phone" class="take_out_phone" onchange="is_open('take_out_phone',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="take_out_phone" class="take_out_phone" onchange="is_open('take_out_phone',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="telephone2" value="<?php echo ($restaurant["telephone2"]); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.officialOrder[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["subscription"] == 1): ?><input type="checkbox" name="subscription" class="subscription" onchange="is_open('subscription',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="subscription" class="subscription" onchange="is_open('subscription',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="subscription" value="<?php echo ($restaurant["subscription"]); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{langData.advertisingSlogan[lang]}}:</td>
                                    <td>
                                        <div class="checkbox-switch">
                                            <?php if($restaurant_bill["down_prompt"] == 1): ?><input type="checkbox" name="down_prompt" class="down_prompt" onchange="is_open('down_prompt',this)" checked>
                                                <?php else: ?>
                                                <input type="checkbox" name="down_prompt" class="down_prompt" onchange="is_open('down_prompt',this)"><?php endif; ?>
                                            <label></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="down_prompt" value="<?php echo ($restaurant["down_prompt"]); ?>" class="larger-input">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="text-center">
                            <button class="blue-btn" onclick="submit_form()">{{langData.save[lang]}}</button>
                            <input type="hidden" value="<?php echo ($restaurant["restaurant_id"]); ?>" name="restaurant_id">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

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
    
    <script>
    function is_open(name, obj) {
        var id = "#" + name;
        var cls = "." + name;
        var hschek = $(cls).is(':checked');
        if (hschek) {
            status = 1;
        } else {
            status = 0;
        }
        var restaurant_id = $("#restaurant_id").val();
        $.ajax({
            url: "/index.php/admin/restaurant/changeBillStatus",
            data: { "name": name, "status": status, "restaurant_id": restaurant_id },
            type: 'post',
            dataType: "json",
            success: function(msg) {
                if (msg.code == 1) {
                    if (status == 1) {
                        if (name == "take_num") {
                            $("#pay_prompt").show();
                        }
                        if (name == "pay_num") {
                            $("#pay_prompt2").show();
                        }
                        if (name == "qrcode") {

                            $("#forward_prompt").show();
                            $("#desk_num").show();
                        }

                        if (name == "top_logo") { //上logo显示
                            $("#top_logo").show();
                        }

                        if (name == "next_logo") { //下logo显示
                            $("#next_logo").show();
                        }

                        $(id).show();
                    } else {
                        if (name == "take_num") {
                            $("#pay_prompt").hide();
                        }
                        if (name == "pay_num") {
                            $("#pay_prompt2").hide();
                        }
                        if (name == "qrcode") {
                            $("#forward_prompt").hide();
                            $("#desk_num").hide();
                        }
                        if (name == "top_logo") { //上logo
                            $("#top_logo").hide();
                        }
                        if (name == "next_logo") { //上logo
                            $("#next_logo").hide();
                        }
                        $(id).hide();
                    }
                } else {
                    layer.msg(msg.msg);
                }
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }

    function submit_form() {
        var form = $("#restaurant_form2")[0];
        var formData = new FormData(form);
        $.ajax({
            url: "/index.php/admin/restaurant/receipt",
            data: formData,
            dataType: 'json',
            type: 'post',
            //          async: false,
            cache: false,
            contentType: false,
            processData: false,
            success: function(msg) {
                if (msg.code == 1) {
                    layer.msg(vm.langData.success[vm.lang]);
                    location.reload();
                } else {
                    layer.msg(vm.langData.failed[vm.lang]);
                }
            },
            error: function() {
                layer.msg(vm.langData.networkError[vm.lang]);
            }
        });
    }

    $(function() {
        var restaurant_id = $("#restaurant_id").val();
        console.log(restaurant_id);
        $.ajax({
            url: "/index.php/admin/restaurant/getBillStatus",
            data: { "restaurant_id": restaurant_id },
            type: 'post',
            dataType: "json",
            success: function(msg) {
                $.each(msg, function(k, v) {
                    console.log(k);
                    console.log(v);
                    if (v == 0) {
                        if (k == "take_num") {
                            $("#pay_prompt").hide();
                        }
                        if (k == "pay_num") {
                            $("#pay_prompt2").hide();
                        }
                        if (k == "qrcode") {
                            $("#desk_num").hide();
                            $("#forward_prompt").hide();
                        }
                        $("#" + k).hide();
                    }
                });
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    });

    //上传logo
    function F_Open_dialog(type) {
        if (type == 1) {
            document.getElementById("top_file").click(); //上logo
        } else {
            document.getElementById("next_file").click(); //下logo
        }

        //上logo
        $('#top_file').change(function() {
            var formData = new FormData();
            formData.append("file", $(this)[0].files[0]);
            formData.append("type", 1);
            $.ajax({
                url: "/index.php/admin/restaurant/changeRestaurantBillLogo",
                data: formData,
                type: 'post',
                dataType: "json",
                contentType: false,
                processData: false,
                async: false,
                cache: false,
                success: function(msg) {
                    if (msg.code == 1) {
                        layer.msg(vm.langData.success[vm.lang])
                        location.reload();
                    }
                }
            });

        })

        //下logo
        $('#next_file').change(function() {
            var formData = new FormData();
            formData.append("file", $(this)[0].files[0]);
            formData.append("type", 2);
            $.ajax({
                url: "/index.php/admin/restaurant/changeRestaurantBillLogo",
                data: formData,
                type: 'post',
                dataType: "json",
                contentType: false,
                processData: false,
                async: false,
                cache: false,
                success: function(msg) {
                    if (msg.code == 1) {
                        layer.msg(vm.langData.success[vm.lang])
                        location.reload();

                    }
                }
            });

        })
    }

    is_next_logo = <?php echo ($is_next_logo); ?>;
    is_top_logo = <?php echo ($is_top_logo); ?>;
    //上logo
    if (is_top_logo == 1) {
        $("#top_logo").show();
    } else if (is_top_logo == 0) {
        $("#top_logo").hide();
    }

    //下logo
    if (is_next_logo == 1) {
        $("#next_logo").show();

    } else if (is_next_logo == 0) {
        $("#next_logo").hide();
    }
    </script>

</body>

</html>