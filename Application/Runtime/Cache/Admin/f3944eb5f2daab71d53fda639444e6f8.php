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
            
    <div class="section">
        <div class="section-header">
            <span>{{langData.roleManagement[lang]}}</span>
        </div>
        <div class="section-content" id="mytable">
            <table class="accounts-table">
                <thead>
                    <tr>
                        <td></td>
                        <td>{{langData.name[lang]}}</td>
                        <td>{{langData.account[lang]}}</td>
                        <td>{{langData.gender[lang]}}</td>
                        <!--<td>{{langData.role[lang]}}</td>-->
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                    <?php if(is_array($cashierArr)): $i = 0; $__LIST__ = $cashierArr;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                            <td><?php echo ($key+1); ?></td>
                            <td><?php echo ($v["cashier_name"]); ?></td>
                            <td><?php echo ($v["cashier_phone"]); ?></td>
                            <td>
                                <?php if($v["cashier_sex"] == 1): ?>{{langData.male[lang]}}
                                    <?php else: ?>{{langData.female[lang]}}<?php endif; ?>
                            </td>
                            <!--<td></td>-->
                            <td>
                                <button class="edit-btn" type="button" data-toggle="modal" data-target="#addRole" onclick="modify(<?php echo ($v["cashier_id"]); ?>)"></button>
                                <button class="remove-btn" type="button" onclick="del(<?php echo ($v["cashier_id"]); ?>)"></button>
                            </td>
                        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </tbody>
            </table>
            <ul class="pagination" id="detail-page"><?php echo ($page); ?></ul>
            <button type="button" class="blue-btn" data-toggle="modal" id="addwindow">{{langData.addRole[lang]}}</button>
            <div class="accounts-tips section-tips">
                {{langData.accountTips1[lang]}}<br>
                {{langData.accountTips2[lang]}}<br>
                {{langData.accountTips3[lang]}}<br>
                {{langData.accountTips4[lang]}}<br>
            </div>
        </div>
    </div>

        </div>
        
        
    <div class="modal fade" id="addRole" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="myform" enctype="multipart/form-data">
                    <div class="modal-header">
                        <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title" id="myModalLabel">{{langData.editRole[lang]}}</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="commit_way" />
                        <input type="hidden" name="Cashier_id" />
                        <table class="accountsModal-table">
                            <tr>
                                <td>{{langData.name[lang]}}:</td>
                                <td>
                                    <input type="text" name="Cashier_name">
                                    <span class="section-tips">{{langData.accountNameTips[lang]}}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.account[lang]}}:</td>
                                <td>
                                    <input type="text" name="Cashier_phone" placeholder="请输入纯数字组合">
                                    <span class="section-tips">{{langData.phoneTips[lang]}}</span>
                                </td>
                            </tr>
                            <!--<tr>
                                <td>{{langData.role[lang]}}:</td>
                                <td>
                                    <select class="select-grey">
                                        <option>{{langData.manager[lang]}}</option>
                                    </select>
                                </td>
                            </tr>-->
                            <tr>
                                <td>{{langData.password[lang]}}:</td>
                                <td>
                                    <input type="password" name="Cashier_pwd" placeholder="请输入纯数字组合">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.confirmPassword[lang]}}:</td>
                                <td>
                                    <input type="password" name="Cashier_pwds" placeholder="请输入纯数字组合">
                                </td>
                            </tr>
                            <tr>
                                <td>{{langData.gender[lang]}}:</td>
                                <td>
                                    <input type="radio" name="Cashier_sex" value="1" checked="checked">{{langData.male[lang]}}
                                    <input type="radio" name="Cashier_sex" value="0">{{langData.female[lang]}}
                                </td>
                            </tr>
                        </table>
                        <div class="text-center">
                            <button type="button" class="blue-btn" id="commit">{{langData.save[lang]}}</button>
                        </div>
                    </div>
                </form>
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
    
    <script src="/Public/js/Admin-Restaurant/admin_accounts.js"></script>

</body>

</html>