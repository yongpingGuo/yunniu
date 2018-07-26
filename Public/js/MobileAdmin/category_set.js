
//菜品分类数据上移
function moveup1(obj){
    var sort = $(obj).data('sort');
    var food_category_id = $(obj).data('food_category_id');
    var tr = $(obj).parents("div");
    if(tr.index() != 0){
        $.ajax({
            type:"post",
            url:"/index.php/MobileAdmin/dishes/moveup1",
            data:{"sort":sort,"food_category_id":food_category_id},
            success:function(data){
                $('#mytype').html(data);
            },
            error:function(){
                alert("出错了");
            }
        });
    }
}

//菜品分类数据下移
function movedown1(obj){
    var len = parseInt(($("#mytype").children('div').length)-1);
    var sort = $(obj).data('sort');
    var food_category_id = $(obj).data('food_category_id');
    var tr = $(obj).parents("div");
    console.log(tr.index());
    if (tr.index() != len){
        $.ajax({
            type:"post",
            url:"/index.php/MobileAdmin/dishes/movedown1",
            data:{"sort":sort,"food_category_id":food_category_id},
            success:function(data){
                $('#mytype').html(data);
            },
            error:function(){
                alert("出错了");
            }
        });
    }
}

function food_category_edit(food_category_id){
    self.location.href = '/index.php/MobileAdmin/dishes/food_category_edit/food_category_id/'+food_category_id;
}

function point_img(src){
    var pos = src.indexOf("/Public");
    var final_src = src.substr(pos);
    // 类型归为系统图标
    $("#ico_category_type" ).val(1);
    $("#img_url").val(final_src);
    $("#img_display").attr('src',final_src);

    var file = $("#user_define_img")
    file.after(file.clone().val(""));
    file.remove();
}

var ico_img = '__PUBLIC__/images/defaultFoodCate1.png';
function preview(file) {
    var picinfo = file.files[0]; //input
    if( picinfo.size > 5*1024*1024 ){  //用size属性判断文件大小不能超过5M
        alert( "您上传的文件超过5M！" ) ;
        $("input[name='user_define_img']").val('');
        $("#img_display").attr('src',ico_img);
        return false;
    }
    if (file.files && file.files[0]) {
        var reader = new FileReader();
        reader.onload = function (evt) {
            $("#img_display").attr('src',evt.target.result);
            $("#img_url").val(evt.target.result);
            // 类型归为自定义图标
            $("#ico_category_type" ).val(2);
        }
        reader.readAsDataURL(file.files[0]);
    }
}

function addTiming(obj){
    var type = $(obj).data("type");
    if(type){
    var str = '<div><div class="section-row">\
    <span>日期：</span>\
   <input type="text" class="input-time datepicker-start"  name="startTime">\
     <span>至</span>\
     <input type="text" class="input-time datepicker-end" name="endTime">\
    </div>\
    <div class="section-row">\
   <span>时间：</span>\
    <input type="text" class="input-time timepicker" name="startHour">\
   <span>至</span>\
   <input type="text" class="input-time timepicker" name="endHour">\
    </div></div>';
        $("#time").append(str);
    }else{
        var str = '<div><div class="section-row">\
                 <input type="text" class="input-time timepicker" name="dayStartTime">\
                 <span>至</span>\
                 <input type="text" class="input-time timepicker" name="dayEndTime">\
                 </div>\
                 <div>\
                 <label class="checkbox">\
                 <input type="checkbox" name="monday" value="1">\
                <span>周一</span>\
                </label>\
                <label class="checkbox">\
                <input type="checkbox" name="tuesday" value="2"">\
                 <span>周二</span>\
                 </label>\
                 <label class="checkbox">\
                  <input type="checkbox" name="wednesday" value="3">\
                  <span>周三</span>\
                  </label>\
                  <label class="checkbox">\
                  <input type="checkbox" name="thursday" value="4">\
                   <span>周四</span>\
                   </label>\
                   <label class="checkbox">\
                    <input type="checkbox" name="friday" value="5">\
                    <span>周五</span>\
                    </label>\
                    <label class="checkbox">\
                     <input type="checkbox" name="saturday" value="6">\
                     <span>周六</span>\
                     </label>\
                     <label class="checkbox">\
                  <input type="checkbox" name="sunday" value="0">\
                  <span>周日</span>\
                  </label>\
                  </div></div>';
        $("#day").append(str);
    }
    dateTimePicker();
}


function commit(add_or_edit){
    var hschek = $("input[name='is_timing']").is(':checked');
    // 判断是否开启定时。0：关闭，1：开启
    if (hschek) {
        status = 1;
    }else{
        status = 0;
    }

    if(add_or_edit != 1){
        // 新增菜品分类
        if($("#food_category_name").val() == ""){
            alert("菜品分类不能为空");
        }else{
            var food_category_name = $("#food_category_name").val();
            var formdata=new FormData();
            formdata.append("food_category_name",food_category_name);
            formdata.append("is_timing",status);

            // 图标URL
            var img_url = $("#img_url").val();
            formdata.append("img_url",img_url);
            // 图标类型
            var ico_category_type = $('#ico_category_type' ).val();
            formdata.append("ico_category_type",ico_category_type);
            // 自定义文件域
            formdata.append("user_define_img",$("#user_define_img")[0].files[0]);

            var timeInfo = $("#time").children();
            var dayInfo = $("#day").children();
            if(status == 1){
                var day_str = '';
                // 遍历最外层div
                $.each(dayInfo,function(k,v){
                    $.each($(v).children(),function(k1,v1){
                        // 第一个子元素的input元素
                        if(k1 == 0){
                            var first_input = $(v1 ).children().eq(0).val();
                            var second_input = $(v1 ).children().eq(4).val();
                            if(first_input != '' && second_input != ''){
                                day_str += first_input+','+second_input+',';
                            }
                        }else{
                            // 第二个子元素下的input元素
                            $.each($(v1).children(),function(k2,v2){
                                var if_checked = $(v2 ).children().eq(0)[0].checked;
                                var val = $(v2 ).children().eq(0).val();
                                if(if_checked && val){
                                    //console.log($(v2 ).children().eq(0).val());
                                    day_str += val+'-';
                                }
                            });
                            day_str += '|';
                        }
                    });
                });
                //console.log(day_str);

                //var time_str = '';
                var timeInfoArray = new Array();
                $.each(timeInfo,function(k3,v3){
                    timeInfoArray[k3] = new Array();
                    var j = 0;
                    $.each($(v3).find('input:odd'),function(k4,v4){
                        if($(v4).val() != ''){
                            //time_str += $(v4).val()+',';

                            timeInfoArray[k3][j] = $(v4).val();
                            j++;
                        }
                    });
                    //time_str += '|';
                });
                //console.log(time_str);
                //console.log(timeInfoArray);

                timeInfoArray = JSON.stringify(timeInfoArray);
                formdata.append("time",timeInfoArray);
                formdata.append("day",day_str)
            }
            $.ajax({
                type : 'post',
                url : '/index.php/MobileAdmin/Dishes/createDishetype',
                data:formdata,
                // dataType:"json",
                cache : false,
                async: true,
                processData : false,
                contentType : false,
                beforeSend:function(){
                    layer.open({
                        type:3,
                        icon:2,
                        skin:"loading"
                    });
                },
                success : function(data){
                    self.location.href = '/index.php/MobileAdmin/Dishes/category_set';
                },
                error:function(){
                    alert("出错了");
                }
            });
        }
    }else{
        // 编辑菜品分类
        var formdata=new FormData();
        formdata.append("food_category_id",$("#food_category_id").val());
        formdata.append("food_category_name",$("#food_category_name").val());
        formdata.append("is_timing",status);

        // 图标URL
        var img_url = $("#img_url").val();
        formdata.append("img_url",img_url);
        // 图标类型
        var ico_category_type = $('#ico_category_type' ).val();
        formdata.append("ico_category_type",ico_category_type);
        // 文件域
        formdata.append("user_define_img",$("#user_define_img")[0].files[0]);

        var timeInfo = $("#time").children();
        var dayInfo = $("#day").children();
        if(status == 1){
            var day_str = '';
            // 遍历最外层div
            $.each(dayInfo,function(k,v){
                $.each($(v).children(),function(k1,v1){
                    // 第一个子元素的input元素
                    if(k1 == 0){
                        var first_input = $(v1 ).children().eq(0).val();
                        var second_input = $(v1 ).children().eq(4).val();
                        if(first_input != '' && second_input != ''){
                            day_str += first_input+','+second_input+',';
                        }
                    }else{
                        // 第二个子元素下的input元素
                        $.each($(v1).children(),function(k2,v2){
                            var if_checked = $(v2 ).children().eq(0)[0].checked;
                            var val = $(v2 ).children().eq(0).val();
                            if(if_checked && val){
                                //console.log($(v2 ).children().eq(0).val());
                                day_str += val+'-';
                            }
                        });
                        day_str += '|';
                    }
                });
            });
            //console.log(day_str);

            //var time_str = '';
            var timeInfoArray = new Array();
            $.each(timeInfo,function(k3,v3){
                timeInfoArray[k3] = new Array();
                var j = 0;
                $.each($(v3).find('input:odd'),function(k4,v4){
                    if($(v4).val() != ''){
                        //time_str += $(v4).val()+',';

                        timeInfoArray[k3][j] = $(v4).val();
                        j++;
                    }
                });
                //time_str += '|';
            });
            //console.log(time_str);
            //console.log(timeInfoArray);

            timeInfoArray = JSON.stringify(timeInfoArray);
            formdata.append("time",timeInfoArray);
            formdata.append("day",day_str)
        }

        $.ajax({
            type : 'post',
            url : '/index.php/MobileAdmin/Dishes/modifyDishestype',
            data:formdata,
            // dataType:"json",
            cache : false,
            processData : false,
            contentType : false,
            success : function(data){
                self.location.href = '/index.php/MobileAdmin/Dishes/category_set';
            },
            error:function(){
                alert("出错了");
            }
        });

    }
}



function deltype(cid){
    var msg = "您真的确定要删除吗？\n\n请确认！";
    console.log(cid);
    if (confirm(msg)==true){
        $.ajax({
            type:"get",
            url:"/index.php/MobileAdmin/dishes/delDishestype/food_category_id/"+cid+"",
            success:function(data){
                if(data == 1){
                    alert("无法删除拥有子集的分类");
                }else{
                    $('#mytype').html(data);
                }
            },
            error:function(){
                alert("出错了");
            }
        });
    }
}

$("#detail-page").children().children("a").click(function(){
    var page = parseInt($(this).data("page"));
    console.log(page);
    $.ajax({
        url:"/index.php/admin/Dishes/deskInfo/page/"+page+"",
        type:"get",
        success:function(data){
            $("#mytr").html(data);
        },
        error:function(){
            alert("出错了");
        }
    });
});

function showtime2(){
    var hschek = $("input[name='is_timing']").is(':checked');
    if (hschek) {
        document.getElementById('timeout_week').style.display = "";
        document.getElementById('timeout_date').style.display = "";
    }else{
        document.getElementById('timeout_week').style.display = "none";
        document.getElementById('timeout_date').style.display = "none";
    }

}
