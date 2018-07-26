function keep_role(commit_way){
    var Cashier_name = $("input[name='Cashier_name']").val();
    var Cashier_phone = $("input[name='Cashier_phone']").val();
    var Cashier_pwd = $("input[name='Cashier_pwd']").val();
    var Cashier_pwds = $("input[name='Cashier_pwds']").val();
    var reg = new RegExp("^[0-9]*$");
    if(Cashier_name && Cashier_phone && Cashier_pwd && Cashier_pwds){
        if(reg.test(Cashier_phone) && reg.test(Cashier_pwd) && reg.test(Cashier_pwds)){
            if(Cashier_pwd == Cashier_pwds){
                if(commit_way == 0){
                    $.ajax({
                        type:"post",
                        url:"/index.php/MobileAdmin/Accounts/role_add",
                        data:$("#add_role").serialize(),
                        dataType: "json",
                        success:function(data){
                            if(data.code == 1){
                                location.href = "/index.php/MobileAdmin/Accounts/index";
                            }else{
                                layer.msg('新增角色出错');
                            }
                        },
                        error:function(){
                            layer.msg("出错了或帐号已存在!");
                        }
                    });
                }else{
                    $.ajax({
                        type:"post",
                        url:"/index.php/MobileAdmin/Accounts/role_edit",
                        data:$("#edit_role").serialize(),
                        dataType: "json",
                        success:function(data){
                            if(data.code == 1){
                                location.href = "/index.php/MobileAdmin/Accounts/index";
                            }else{
                                layer.msg('编辑角色出错');
                            }
                        },
                        error:function(){
                            layer.msg("出错了或帐号已存在!");
                        }
                    });
                }
            }else{
                layer.msg("密码不一致!");
            }
        }else{
            layer.msg("账号或者密码格式错误,必须是纯数字组合!");
        }
    }else{
        layer.msg("请将信息填写完整!");
    }
}

function edit_role(id){
    location.href = "/index.php/MobileAdmin/Accounts/role_edit/id/"+id;
}

function del_role(obj){
    var cashier_id = $(obj).data('cashier_id');
    layer.confirm('您确定要删除吗？', {icon:3}, function(index){
        $.ajax({
            type:"post",
            url:"/index.php/MobileAdmin/Accounts/Accountsdel",
            data:{"Cashier_id":cashier_id},
            dataType: "json",
            success:function(data){
                if(data.code == 1){
                    layer.msg('删除角色成功');
                    $(obj ).parent().parent().remove();
                }else{
                    layer.msg('出错了');
                }
            },
            error:function(){
                layer.msg("出错了!");
            }
        });
        layer.close(index);
    });
}