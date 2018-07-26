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
            
    <ul class="nav nav-tabs" id="mytab">
        <li class="active">
            <a href="#discount" data-toggle="tab">{{langData.memberDiscountSet[lang]}}</a>
        </li>
        <li>
            <a href="#restaurant_discount" data-toggle="tab">{{langData.storeDiscountSet[lang]}}</a>
        </li>
        <li><a href="#charge" data-toggle="tab">{{langData.rechargeInfo[lang]}}</a></li>
        <li><a href="#group" data-toggle="tab">{{langData.memberGroup[lang]}}</a></li>
        <li><a href="#remind" data-toggle="tab">{{langData.balanceSwitch[lang]}}</a></li>
        <li><a href="#discountReduce" data-toggle="tab">{{langData.dishDiscountLegislation[lang]}}</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab tab-pane in active" id="discount">
            <section class="section">
                <div class="section-header">
                    <span>{{langData.memberDiscounts[lang]}}</span>
                    <div class="checkbox-switch">
                        <?php if($if_open == 1): ?><input type="checkbox" name="if_open" class="if_open" onchange="to_db(this.name,'/index.php/Admin/Member/discount_set')" checked>
                            <?php else: ?>
                            <input type="checkbox" name="if_open" class="if_open" onchange="to_db(this.name,'/index.php/Admin/Member/discount_set')"><?php endif; ?>
                        <label></label>
                    </div>
                </div>
                <div class="section-content">
                    <table class="member-discount-table">
                        <tbody>
                        <?php foreach ($discount_info as $K => $v): ?>
                        <tr id="tr<?php echo $v['id'];?>">
                            <td><?php echo ++$k;?></td>
                            <td>{{langData.full[lang]}} <?php echo $v['money'];?>{{langData.yuan[lang]}}</td>
                            <td>{{langData.discount2[lang]}}:<?php echo $v['discount'];?>{{langData.discount3[lang]}}</td>
                            <td>{{langData.legislative[lang]}}:<?php echo $v['reduce'];?>{{langData.yuan[lang]}}</td>
                            <td><?php echo $v['group_id'];?></td>
                            <td>
                                <button data-toggle="modal" data-target="#discountModal" id='modify' data-type_id="<?php echo $v['id'];?>">
                                    <img src="/Public/images/edit.png">
                                </button>
                                <button onclick="return deleteDisc(this)" data-type_id="<?php echo $v['id'];?>">
                                    <img src="/Public/images/remove.png">
                                </button>
                            </td>
                        </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                    <button class="blue-btn" data-toggle="modal" data-target="#discountModal" onclick="reset()">{{langData.addDiscount[lang]}}</button>
                </div>
            </section>
        </div>

        <div class="tab tab-pane" id="restaurant_discount">
            <section class="section">
                <div class="section-header">
                    <span>{{langData.shopDiscounts[lang]}}</span>
                    <div class="checkbox-switch">
                        <?php if($if_open_restaurant == 1): ?><input type="checkbox" name="if_open_restaurant" class="if_open_restaurant" onchange="to_db(this.name,'/index.php/Admin/Member/restaurant_discount_set')" checked>
                            <?php else: ?>
                            <input type="checkbox" name="if_open_restaurant" class="if_open_restaurant" onchange="to_db(this.name,'/index.php/Admin/Member/restaurant_discount_set')"><?php endif; ?>
                        <label></label>
                        <input type="hidden" name="refresh" value="<?php echo ($refresh); ?>" id="refresh"/>
                    </div>
                </div>
                <div class="section-content">
                    <?php if($discount_restaurant_info):?>
                        <table class="member-discount-table" id="tab">
                        <tbody>
                        <tr>
                            <td>{{langData.full[lang]}} <?php echo ($discount_restaurant_info['money']); ?> {{langData.yuan[lang]}}</td>
                            <td>{{langData.discount2[lang]}}:<?php echo ($discount_restaurant_info['discount']); ?>{{langData.discount3[lang]}}</td>
                            <td>{{langData.legislative[lang]}}:<?php echo ($discount_restaurant_info['reduce']); ?> {{langData.yuan[lang]}}</td>
                            <td>
                                <button data-toggle="modal" data-target="#discountModal_restaurant" id='modify_restaurant' data-type_id="<?php echo ($discount_restaurant_info['id']); ?>">
                                    <img src="/Public/images/edit.png">
                                </button>
                                <button onclick="return deleteDisc_restaurant(this)" data-type_id="<?php echo ($discount_restaurant_info['id']); ?>">
                                    <img src="/Public/images/remove.png">
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if(!$discount_restaurant_info):?>
                        <button class="blue-btn" data-toggle="modal" data-target="#discountModal_restaurant" onclick="reset()">{{langData.addDiscount[lang]}}</button>
                    <?php endif; ?>

                    <button class="blue-btn" data-toggle="modal" data-target="#discountModal_restaurant" onclick="reset()" style="display:none;" id="new_add">{{langData.addDiscount[lang]}}</button>

                </div>
            </section>
        </div>


        <div class="tab tab-pane" id="charge">
            <section class="section">
                <div class="section-header">
                    <span>{{langData.rechargeDiscount[lang]}}</span>
                    <span class="section-tips">{{langData.rechargeSetTips[lang]}}</span>
                </div>
                <div class="section-content">
                    <div class="member-charge">
                        <table>
                            <tbody>
                                <tr>
                                    <th></th>
                                    <th>{{langData.rechargeAmount[lang]}}</th>
                                    <th>{{langData.giftAmount[lang]}}</th>
                                    <th>{{langData.datedAmount[lang]}}</th>
                                </tr>
                            <?php foreach ($prepaid_rules as $k => $v): ?>
                                <tr>
                                    <td><?php echo ++$k;?></td>
                                    <td><?php echo $v['account'];?>{{langData.yuan[lang]}}</td>
                                    <td class="text-right"><?php echo $v['benefit'];?>{{langData.yuan[lang]}}</td>
                                    <td class="text-right"><?php echo $v['account']+$v['benefit'];?>{{langData.yuan[lang]}}</td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="tab tab-pane" id="group">
            <section class="section">
                <div class="section-header">
                    <span>{{langData.memberGroupInfo[lang]}}</span>
                    <span class="section-tips">{{langData.memberGroupTips[lang]}}</span>
                </div>
                <div class="section-content">
                    <div class="memberGroup">
                        <?php foreach ($group_info as $k => $v): ?>
                            <div class="memberGroup-item flex-content">
                                <span><?php echo ++$k;?></span>
                                <span class="flex-main text-right"><?php echo $v['group_name'];?></span>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </section>
        </div>
        <div class="tab tab-pane" id="remind">
            <section class="section">
                <div class="section-header">
                    <span>{{langData.balanceSwitch[lang]}}</span>
                    <div class="checkbox-switch">
                        <?php if($if_open_remind == 1): ?><input type="checkbox" name="if_open_remind" class="if_open_remind" onchange="to_db(this.name,'/index.php/Admin/Member/remind_set')" checked>
                            <?php else: ?>
                            <input type="checkbox" name="if_open_remind" class="if_open_remind" onchange="to_db(this.name,'/index.php/Admin/Member/remind_set')"><?php endif; ?>
                        <label></label>
                    </div>
                </div>
            </section>
        </div>
        <div class="tab tab-pane" id="discountReduce">
            <section class="section">
                <div class="section-content flex-content">
                    <div class="flex-main">
                        <table class="member-discount-table" id="addDisc">
                            <tbody>
                            <?php foreach ($discount as $K1 => $v): ?>
                            <tr id="tr<?php echo $v['id'];?>">
                                <td><?php echo ++$k1;?></td>
                                <td>{{langData.discount2[lang]}}:<?php echo $v['val'];?>{{langData.discount3[lang]}}</td>
                                <td>
                                    <button data-toggle="modal" data-target="#OrderOrFoodDiscountModal" onclick="edit_info(<?php echo $v['val'];?>,<?php echo $v['id'];?>,'discount')">
                                        <img src="/Public/images/edit.png">
                                    </button>
                                    <button onclick="return deleteDiscOrRedu(this)" data-type_id="<?php echo $v['id'];?>">
                                        <img src="/Public/images/remove.png">
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                        <!--<button class="blue-btn" data-toggle="modal" data-target="#OrderOrFoodDiscountModal" onclick="addDiscRedu(1)">新增折扣</button>-->
                        <button class="blue-btn" onclick="addDiscRedu(1)">{{langData.addDiscount[lang]}}</button>
                    </div>
                    <div class="flex-main">
                        <table class="member-discount-table" id="addRedu">
                            <tbody>
                            <?php foreach ($reduce as $K2 => $v): ?>
                            <tr id="tr<?php echo $v['id'];?>">
                                <td><?php echo ++$k2;?></td>
                                <td>{{langData.legislative[lang]}}:<?php echo $v['val'];?>{{langData.yuan[lang]}}</td>
                                <td>
                                    <button data-toggle="modal" data-target="#OrderOrFoodReduceModal" onclick="edit_info(<?php echo $v['val'];?>,<?php echo $v['id'];?>,'reduce')">
                                        <img src="/Public/images/edit.png">
                                    </button>
                                    <button onclick="return deleteDiscOrRedu(this)" data-type_id="<?php echo $v['id'];?>">
                                        <img src="/Public/images/remove.png">
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                        <button class="blue-btn" onclick="addDiscRedu(2)">{{langData.addLegislation[lang]}}</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

        </div>
        
           
    <div class="modal fade discountModal" id="discountModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title" id="myModalLabel">{{langData.editMemberDiscount[lang]}}</h4>
                </div>
                <div class="modal-body">
                <form id="postRoleInfo" class="form-horizontal" >
                     <input type="hidden" name="id" id="Jid">
                    <div class="discountModal-info">
                        <span>{{langData.memberGroup[lang]}}:</span>
                        <select name="group_id" class="selector select-grey">
                            <option value="0">{{langData.defaultMemberGroup[lang]}}</option>
                            <?php foreach ($group_info as $k => $v): ?>
                            <?php echo "<option value=".$v['group_id'].">".$v['group_name']."</option>";?>
                            <?php endforeach ?>
                        </select>
                        <span>{{langData.full[lang]}}</span>
                        <input type="text" name="money" class="mini-input" id="money">
                        <span>{{langData.yuan[lang]}},{{langData.discount2[lang]}}</span>
                        <input type="text" name="discount" class="mini-input" id="discounts">
                        <span>{{langData.discount3[lang]}},{{langData.legislative[lang]}}</span>
                        <input type="text" name="reduce" class="mini-input" id="reduce"> 
                        <span>{{langData.yuan[lang]}}</span>                  
                    </div>
                    <div class="section-tips">
                  <!--       <div>* 当选择为所有顾客时，即非会员也享有此折扣。</div>
                        <div>* 当有多个折扣设置时，系统取最大折扣优惠。</div> -->
                        <div>* {{langData.discountTips1[lang]}}</div>
                        <div>* {{langData.discountTips2[lang]}}</div>
                        <div>* {{langData.discountTips5[lang]}}</div>
                        <div>* {{langData.discountTips3[lang]}}</div>
                        <div>* {{langData.discountTips4[lang]}}</div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="blue-btn">{{langData.save[lang]}}</button>
                        <input  type="reset" id="reset" style="display:none;"/>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade discountModal_restaurant" id="discountModal_restaurant">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title" id="myModalLabel_">{{langData.shopDiscountEdit[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <form id="postRoleInfo_restaurant" class="form-horizontal" >
                        <input type="hidden" name="id" id="Jid_restaurant">
                        <div class="discountModal-info">
                            <span>{{langData.full[lang]}}</span>
                            <input type="text" name="money" class="mini-input" id="money_restaurant">
                            <span>{{langData.yuan[lang]}},{{langData.discount2[lang]}}</span>
                            <input type="text" name="discount" class="mini-input" id="discounts_restaurant">
                            <span>{{langData.discount3[lang]}},{{langData.legislative[lang]}}</span>
                            <input type="text" name="reduce" class="mini-input" id="reduce_restaurant">
                            <span>{{langData.yuan[lang]}}</span>
                        </div>
                        <div class="section-tips">
                            <!--       <div>* 当选择为所有顾客时，即非会员也享有此折扣。</div>
                                  <div>* 当有多个折扣设置时，系统取最大折扣优惠。</div> -->
                            <div>* {{langData.discountTips1[lang]}}</div>
                            <div>* {{langData.discountTips2[lang]}}</div>
                            <div>* {{langData.discountTips5[lang]}}</div>
                            <div>* {{langData.discountTips3[lang]}}</div>
                            <div>* {{langData.discountTips4[lang]}}</div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="blue-btn">{{langData.save[lang]}}</button>
                            <input  type="reset" id="reset_restaurant" style="display:none;"/>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade OrderOrFoodDiscountModal" id="OrderOrFoodDiscountModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title" id="myModalLabel1">{{langData.discountOperation[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <form id="postRoleInfo_order_or_food" class="form-horizontal" action="javascript:void(0)">
                        <input type="hidden" name="id" id="discount_id">
                        <div class="discountModal-info">
                            <span>{{langData.discount2[lang]}}</span>
                            <input type="text" name="val" class="mini-input" id="order_or_food_discount">
                            <input type="hidden" name="discount_or_reduce" value="1"/>
                            <span>{{langData.discount3[lang]}}</span>
                        </div>
                        <div class="section-tips">
                            <div>* {{langData.discountTips1[lang]}}</div>
                            <div>* {{langData.discountTips2[lang]}}</div>
                            <div>* {{langData.discountTips5[lang]}}</div>
                            <div>* {{langData.discountTips3[lang]}}</div>
                        </div>分
                        <div class="text-center">
                            <button class="blue-btn" onclick="order_or_food_post(this,'discount')">{{langData.save[lang]}}</button>
                            <input  type="reset" id="order_or_food_discount_reset" style="display:none;"/>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade OrderOrFoodReduceModal" id="OrderOrFoodReduceModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                    <h4 class="modal-title" id="myModalLabel2">{{langData.reduceOperation[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <form id="order_or_food_reduce_form" class="form-horizontal" action="javascript:void(0)">
                        <input type="hidden" name="id" id="reduce_id">
                        <div class="discountModal-info">
                            <span>{{langData.legislative[lang]}}</span>
                            <input type="text" name="val" class="mini-input" id="order_or_food_reduce">
                            <input type="hidden" name="discount_or_reduce" value="2"/>
                            <span>{{langData.yuan[lang]}}</span>
                        </div>
                        <div class="section-tips">
                            <div>* {{langData.discountTips1[lang]}}</div>
                            <div>* {{langData.discountTips4[lang]}}</div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="blue-btn" onclick="order_or_food_post(this,'reduce')">{{langData.save[lang]}}</button>
                            <input  type="reset" id="order_or_food_reduce_reset" style="display:none;"/>
                        </div>
                    </form>
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
      
<script>
    $(function(){
       if($("#refresh").val() == '1'){
           $('#mytab li:eq(1) a').tab('show');
       }else if($("#refresh").val() == '2'){
           $('#mytab li:eq(5) a').tab('show');
       }
    });

    // 将各类型的设置传递到数据库
function to_db(a,url){
    var cls = "." + a;
    var hschek = $(cls).is(':checked');
    if (hschek) {
        b = 1;
    }else{
        b = 0;
    }
    // 判断是if_open还是if_vip
    if(a == "if_open"){
        // 发送ajax
        $.post(url,{"if_open":b},function(data){
            var object = JSON.parse( data )
            if(object.code === 1){
                if(object.if_open === '1'){
                    $('[name="if_open_restaurant"]').prop("checked", true);
                }else{
                    $('[name="if_open_restaurant"]').prop("checked", false);
                }
            }else{
                alert(object.msg);
            }
        });
    }
    // 店铺折扣
    if(a == "if_open_restaurant"){
        // 发送ajax
        $.post(url,{"if_open_restaurant":b},function(data){
            var object = JSON.parse( data )
            if(object.code === 1){
                if(object.if_open === '1'){
                    $('[name="if_open"]').prop("checked", true);
                }else{
                    $('[name="if_open"]').prop("checked", false);
                }
            }else{
                alert(object.msg);
            }
        });
    }

    // 余额开关
    if(a == "if_open_remind"){
        // 发送ajax
        $.post(url,{"if_open_remind":b},function(data){
            var object = JSON.parse( data )
            if(object.code === 1){
                if(object.if_open === '1'){
                    $('[name="if_open_remind"]').prop("checked", true);
                }else{
                    $('[name="if_open_remind"]').prop("checked", false);
                }
            }else{
                alert(object.msg);
            }
        });
    }

}
    function reset() {
        $("input[type='reset']").trigger('click');
    }

    function addDiscRedu(type){
        $("input[type='reset']").trigger('click');
        if(type==1){
            var len = $('#addDisc' ).find('tr' ).length;
        }else{
            var len = $('#addRedu' ).find('tr' ).length;
        }
        if(len>=4){
            if(type==1){
                $('#OrderOrFoodDiscountModal').modal('hide');
            }else{
                $('#OrderOrFoodReduceModal').modal('hide');
            }
            layer.msg(vm.langData.fourRulesTips[vm.lang]);
        }else{
            if(type==1){
                $('#OrderOrFoodDiscountModal').modal('show')
            }else{
                $('#OrderOrFoodReduceModal').modal('show')
            }
        }
    }

    function deleteDisc(obj) {
        layer.confirm('', {
            title: vm.langData.deleteConfirm[vm.lang],
            btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
            },function(index){
                var _id = $(obj).data('type_id');
                $.ajax({
                    url:"/index.php/Admin/Member/deleteDisc",
                    dataType:'json',
                    data:{"id":_id},
                    type:'POST',
                    success:function(data){
                        if (data==1) {
                            $("#tr"+_id).remove();
                        }
                    }
                });
                layer.close(index);
            });
            return false;
    }

    function deleteDiscOrRedu(obj) {
        layer.confirm('', {
            title: vm.langData.deleteConfirm[vm.lang],
            btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
        },function(index){
            var _id = $(obj).data('type_id');
            $.ajax({
                url:"/index.php/Admin/Member/deleteDiscOrRedu",
                dataType:'json',
                data:{"id":_id},
                type:'POST',
                success:function(data){
//                    location.href = '/index.php/Admin/Member/setting/refresh/2'
                    $.get('/index.php/Admin/Member/ajaxFill',{},function(returnData){
                        $('#discountReduce' ).html(returnData)
                    });
                }
            });
            layer.close(index);
        });
        return false;
    }

    function deleteDisc_restaurant(obj) {
        layer.confirm('', {
            title: vm.langData.deleteConfirm[vm.lang],
            btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
        },function(index){
            var _id = $(obj).data('type_id');
            $.ajax({
                url:"/index.php/Admin/Member/deleteDisc_restaurant",
                dataType:'json',
                data:{"id":_id},
                type:'POST',
                success:function(data){
                    if (data==1) {
                        $("#tab").remove();
                        $("#new_add").show();
                    }
                }
            });
            layer.close(index);
        });
        return false;
    }

    //修改
    $(document).on('click','#modify',function() {
        $.ajax({
            url:"/index.php/Admin/Member/editSetting",
            dataType:'json',
            data:{"id":$(this).data('type_id')},
            type:'POST',
            success:function(data){
                $('#Jid').val(data.id);
                $('#money').val(data.money);
                $('#discounts').val(data.discount);
                $('#reduce').val(data.reduce);
                $(".selector").find("option[value="+data.group_id+"]").prop("selected",true);
            }
        });
    });

    function edit_info(val,id,disc_or_rede){
        $('#'+disc_or_rede+'_id').val(id);
        $('#order_or_food_'+disc_or_rede).val(val);
    }


    $(document).on('click','#modify_restaurant',function() {
        $.ajax({
            url:"/index.php/Admin/Member/editSetting_restaurant",
            dataType:'json',
            data:{"id":$(this).data('type_id')},
            type:'POST',
            success:function(data){
                $('#Jid_restaurant').val(data.id);
                $('#money_restaurant').val(data.money);
                $('#discounts_restaurant').val(data.discount);
                $('#reduce_restaurant').val(data.reduce);
            }
        });
    });

    $("#postRoleInfo").on('submit',function(e) {
        var ev = window.evnet || e
        window.event ? window.event.returnValue = false : ev.preventDefault();

        var money = $("#money").val();
        var discounts = $("#discounts").val();
        var reduce = $("#reduce").val();

        if(!(money && discounts && reduce)){
            layer.msg(vm.langData.notBeEmpty[vm.lang]);
            return false;
        }

        if(isNaN(money)){
            layer.msg(vm.langData.yuanIsNumber[vm.lang]);
            $("#money").val('');
            return false;
        }

        if(money<0){
            layer.msg(vm.langData.yuanGreater[vm.lang]);
            $("#money").val('');
            return false;
        }

        if(isNaN(discounts)){
            layer.msg(vm.langData.discountIsNumber[vm.lang]);
            $("#discounts").val('');
            return false;
        }

        if(discounts<1 || discounts>10){
            layer.msg(vm.langData.discountRange[vm.lang]);
            $("#discounts").val('');
            return false;
        }

        if(isNaN(reduce)){
            layer.msg(vm.langData.reduceIsNumber[vm.lang]);
            $("#reduce").val('');
            return false;
        }

        if(reduce<0){
            layer.msg(vm.langData.reduceGreater[vm.lang]);
            $("#money").val('');
            return false;
        }

            $.ajax({
                url:"/index.php/Admin/Member/setting",
                dataType:'json',
                data:$('#postRoleInfo').serialize(),
                type:'POST',
                success:function(data){
//                    console.log(data);
                    if(data == 1){
                        $('#discountModal').modal('hide');
                        window.location.reload();
                    }else{
                        layer.msg(vm.langData.discountExist[vm.lang]);
                        $('#discountModal').modal('hide');
                        $("input[type='reset']").trigger('click');
                    }
                }
            });
    });

    $("#postRoleInfo_restaurant").on('submit',function(e) {
        var ev = window.evnet || e
        window.event ? window.event.returnValue = false : ev.preventDefault();

        var money_restaurant = $("#money_restaurant").val();
        var discounts_restaurant = $("#discounts_restaurant").val();
        var reduce_restaurant = $("#reduce_restaurant").val();

        if(!(money_restaurant && discounts_restaurant && reduce_restaurant)){
            layer.msg(vm.langData.notBeEmpty[vm.lang]);
            return false;
        }

        if(isNaN(money_restaurant)){
            layer.msg(vm.langData.yuanIsNumber[vm.lang]);
            $("#money_restaurant").val('');
            return false;
        }

        if(money_restaurant<0){
            layer.msg(vm.langData.yuanGreater[vm.lang]);
            $("#money").val('');
            return false;
        }

        if(isNaN(discounts_restaurant)){
            layer.msg(vm.langData.discountIsNumber[vm.lang]);
            $("#discounts_restaurant").val('');
            return false;
        }

        if(discounts_restaurant<1 || discounts_restaurant>10){
            layer.msg(vm.langData.discountRange[vm.lang]);
            $("#discounts_restaurant").val('');
            return false;
        }

        if(isNaN(reduce_restaurant)){
            layer.msg(vm.langData.reduceIsNumber[vm.lang]);
            $("#reduce_restaurant").val('');
            return false;
        }

        if(reduce_restaurant<0){
            layer.msg(vm.langData.reduceGreater[vm.lang]);
            $("#money").val('');
            return false;
        }

        $.ajax({
            url:"/index.php/Admin/Member/setting_restaurant",
            dataType:'json',
            data:$('#postRoleInfo_restaurant').serialize(),
            type:'POST',
            success:function(data){
//                console.log(data);
                if(data == 1){
                    $('#discountModal_restaurant').modal('hide');
                    location.href = '/index.php/Admin/Member/setting/refresh/1'
                }else{
                    layer.msg(data.info);
                    $('#discountModal_restaurant').modal('hide');
                    $("input[type='reset']").trigger('click');
                }
            }
        });
    });

    // 订单或者菜品折扣
    function order_or_food_post(obj,type){
        if(type == 'discount'){
            var discounts = $("#order_or_food_discount").val();
            if(!(discounts)){
                layer.msg(vm.langData.notBeEmpty[vm.lang]);
                return false;
            }

            if(isNaN(discounts)){
                layer.msg(vm.langData.discountIsNumber[vm.lang]);
                $("#order_or_food_discount").val('');
                return false;
            }

            if(discounts<1 || discounts>10){
                layer.msg(vm.langData.discountRange[vm.lang]);
                $("#order_or_food_discount").val('');
                return false;
            }
        }else{
            var reduce = $("#order_or_food_reduce").val();
            if(isNaN(reduce)){
                layer.msg(vm.langData.reduceIsNumber[vm.lang]);
                $("#order_or_food_reduce").val('');
                return false;
            }

            if(reduce<0){
                layer.msg(vm.langData.reduceGreater[vm.lang]);
                $("#order_or_food_reduce").val('');
                return false;
            }
        }
        $.ajax({
            url:"/index.php/Admin/Member/order_or_food_discount",
            dataType:'json',
//            data:$('#postRoleInfo_order_or_food').serialize(),
            data:$(obj).parent().parent().serialize(),
            type:'POST',
            success:function(data){
                $('#OrderOrFoodDiscountModal').modal('hide');
                $('#OrderOrFoodReduceModal').modal('hide');
                if(data == 1){
//                    location.href = '/index.php/Admin/Member/setting/refresh/2'
                    $.get('/index.php/Admin/Member/ajaxFill',{},function(returnData){
                        $('#discountReduce' ).html(returnData)
                    });
                }else{
                    layer.msg(vm.langData.legislationExist[vm.lang]);
                    $("input[type='reset']").trigger('click');
                }
            }
        });
    }
</script>

</body>

</html>