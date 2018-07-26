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
            <a href="<?php echo U('Device/deskInfo');?>">{{langData.tableSet[lang]}}</a>
        </li>
        <li>
            <a href="<?php echo U('Moudle/mobile');?>">{{langData.styleSet[lang]}}</a>
        </li>
    </ul>
    <section class="section">
        <div class="section-header">{{langData.tableSet[lang]}}</div>
        <div class="section-content">
            <div class="clearfix">
                <span>{{langData.orderTitle[lang]}}:</span>
                <input type="text" name="wx_order_title" value="<?php echo ($wx_order_title); ?>" id="wx_order_title" />
                <button type="button" onclick="update_title()" class="blue-btn">{{langData.save[lang]}}</button>
                <?php if($qrc_order == 1): ?><button class="pull-right blue-btn" onclick="addDesk()">{{langData.addTableNumber[lang]}}</button>
                    <?php else: endif; ?>
            </div>
            <?php if($qrc_order == 1): ?><span>{{langData.dateOfExpiry[lang]}}:2018-07-20</span>
                <?php else: ?>
                <span>{{langData.dateOfExpiry[lang]}}:</span>
                <span class="text-danger">{{langData.nonactivated[lang]}}</span><?php endif; ?>
            <?php if($qrc_order == 1): ?><div id="comment_list">
                    <table class="table-code table-condensed">
                        <tbody>
                            <tr>
                                <td></td>
                                <td>{{langData.tableNumber[lang]}}</td>
                                <td class="text-center">{{langData.machineCode[lang]}}</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php if(is_array($deskInfo)): $i = 0; $__LIST__ = $deskInfo;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
                                    <td><?php echo ($i); ?></td>
                                    <td><?php echo ($vo["desk_code"]); ?></td>
                                    <td class="text-center">
                                        <img src="<?php echo ($vo["code_img"]); ?>" class="table-code-img">
                                    </td>
                                    <td>
                                        <button class="blue-btn" data-img_path="<?php echo ($vo["code_img"]); ?>" onclick="downloadImg(this)">{{langData.downloadPicture[lang]}}</button>
                                    </td>
                                    <td>
                                        <button class="edit-btn" data-desk_id="<?php echo ($vo["desk_id"]); ?>" data-desk_code="<?php echo ($vo["desk_code"]); ?>" onclick="editDesk(this)"></button>
                                        <button class="remove-btn" data-desk_id="<?php echo ($vo["desk_id"]); ?>" onclick="delDesk(this)"></button>
                                    </td>
                                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <ul class="pagination" id="detail-page"><?php echo ($page); ?></ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php else: endif; ?>
        </div>
    </section>

        </div>
        
        
    <!--模态框-->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">{{langData.addTableNumber[lang]}}</h4>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0)" id="desk_form">
                        <div class="form-group">
                            <label for="table">{{langData.tableNumber[lang]}}:</label>
                            <input type="text" id="table" name="desk_code" placeholder="A01-1"><span class="text-danger"> {{langData.tableNumberTips[lang]}}</span>
                            <input type="hidden" name="type" value="add">
                            <input type="hidden" name="desk_id" value="">
                        </div>
                    </form>
                    <div class="text-center">
                        <button type="button" class="blue-btn" onclick="submit_deskForm()">{{langData.save[lang]}}</button>
                    </div>
                </div>               
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal -->
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
    //  var totalnum = $("#detail-page").attr("data-totalnum"); //分页数量
    $("#detail-page").children().children("a").click(function() {
        var page = parseInt($(this).data("page"));
        console.log(page);
        $.ajax({
            url: "/index.php/admin/device/deskInfo",
            data: { "page": page },
            type: "get",
            success: function(data) {
                $("#comment_list").html(data);
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    });

    function downloadImg(obj) {
        var img_path = $(obj).data('img_path');
        location.href = "/index.php/admin/device/downloadImg?imgPath=" + img_path;
    }

    function submit_deskForm() {
        var form = $('#desk_form')[0];
        var formData = new FormData(form);
        var url = "";
        var type1 = $('#desk_form input').eq(1).val();

        var type2 = $('#desk_form input').eq(1).val();

        if (type1 == "add") {
            url = "/index.php/admin/device/createDesk";
        } else if (type2 == "edit") {
            url = "/index.php/admin/device/editDesk";
        }
        console.log(url);
        $.ajax({
            url: url,
            dataType: "json",
            type: "post",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function(msg) {
                if (msg.code == 1) {
                    //                  alert("成功");
                    self.location.href = "/index.php/admin/device/deskInfo";
                } else {
                    layer.msg(vm.langData.failed[vm.lang]);
                }
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }

    function editDesk(obj) {
        var desk_code = $(obj).data("desk_code");
        var desk_id = $(obj).data("desk_id");
        $("#desk_form input").eq(0).val(desk_code);
        $("#desk_form input").eq(1).val("edit");
        $("#desk_form input").eq(2).val(desk_id);
        $("#myModal").modal("show");
        $('#myModal').on('hidden.bs.modal', function() {
            $("#desk_form input").eq(0).val("");
        });
    }

    function addDesk() {
        $("#desk_form input").eq(1).val("add");
        $("#myModal").modal("show");
    }

    function delDesk(obj) {
        var desk_id = $(obj).data("desk_id");
        $.ajax({
            url: "/index.php/admin/device/delDesk",
            data: { "desk_id": desk_id },
            dataType: 'json',
            type: "post",
            success: function(msg) {
                if (msg.code == 1) {
                    self.location.href = "/index.php/admin/device/deskInfo";
                } else {
                    self.location.href = "/index.php/admin/device/deskInfo";
                }
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }

    //编辑微信order标题
    function update_title() {
        var wx_order_title = $("#wx_order_title").val();
        $.ajax({
            type: "post",
            url: "/index.php/Admin/Device/update_title",
            data: { "wx_order_title": wx_order_title },
            dataType: "json",
            async: true,
            success: function(data) {
                layer.msg(data.msg);
            },
            error: function() {
                layer.msg(vm.langData.networkError[vm.lang]);
            }
        });
    }
    </script>

</body>

</html>