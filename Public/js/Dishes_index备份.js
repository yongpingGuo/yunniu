function preview(file) {
    console.log(file.files[0]);
    var prevDiv = $(file).parent().prev()[0];
//        console.log(prevDiv);
    if (file.files && file.files[0]) {
        var reader = new FileReader();
        reader.onload = function (evt) {
            // prevDiv.innerHTML = '<img src="' + evt.target.result + '" style="width:100%;height:100%" />';
            $('#edit_upload_box').attr("src",evt.target.result);
        }
        reader.readAsDataURL(file.files[0]);
    }
    else {
        prevDiv.innerHTML = '<div class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
    }
}

function showtime(){
    document.getElementById('show1').style.display = "";
}
function hiddentime(){
    document.getElementById('show1').style.display = "none";
}
function showtime2(){
    document.getElementById('show2').style.display = "";
}
function hiddentime2(){
    document.getElementById('show2').style.display = "none";
}

//页面加载完毕时显示所有的类别信息
$(function(){
    page(1,21);
});

function page(p,cate){

    var url = "/index.php/admin/dishes/showallinfo"

    $.ajax({
        type:"post",
        url:url,
        dataType:"json",
        data:{"page":p,"food_category_id":cate},
        success:function(data){
//                    console.log(data.page_count);
            var mm = '<tr class="text-center"><td></td><td>排序</td><td>名称</td><td>图片</td><td>价格</td><td>类别</td><td>星级</td><td>时价</td><td>状态</td><<td></td></tr>';
            var value = data.data;
            console.log(value);
            //alert(value);
            for (var i in value) {
                var id = value[i].id;
                var food_name = value[i].food_name;
                var food_id = value[i].food_id;
                var food_price = value[i].food_price;
                var food_category_name = value[i].food_category_name;
                // var food_category_id = value[i].food_category_id;
                var star_level = value[i].star_level;
                var food_img = value[i].food_img;
                var food_state2 = value[i].is_sale;
                console.log(food_state2);
                var is_prom = (value[i].is_prom) ? "开启" : "关闭";
                var food_state = value[i].is_sale;
//                        if(food_state == 1){
//                            food_state = "上架";
//                        }else{
//                            food_state = "下架";
//                        }
//
//                        if(food_state == 1){
//                            food_state1 = "下架";
//                        }else{
//                            food_state1 = "上架";
//                        }
                var food_state = (value[i].is_sale==1) ? "上架" : "下架 ";
                var food_state1 = (value[i].is_sale==1) ? "下架" : "上架 ";
                //var is_prom = value[i].is_prom;
                if(star_level == 1){
                    var star = "★";
                }else if(star_level == 2){
                    var star = "★★";
                }else if(star_level == 3){
                    var star = "★★★";
                }else if(star_level == 4){
                    var star = "★★★★";
                }else{
                    var star = "★★★★★";
                }


                mm += '<tr><td>' + id + '</td><td><button class="btn-none" onclick = "moveup('+id+')"><img src="/public/images/up.png" ></button>'+
                    '<button class="btn-none" onclick = "movedown('+id+')"><img src="/public/images/down.png"></button>' +
                    '</td><td>' + food_name + '</td><td><img src = "/Application/Admin' + food_img + '" class="dishes-img"></td><td>' + food_price + '元</td><td>'+food_category_name+'</td>'+
                    '<td id = "star">'+star+'</td><td>'+is_prom+'</td><td>'+food_state+'</td><td><a href = "/index.php/admin/Dishes/edit/food_id/' + food_id + '"><button class="btn-none">编辑</button>'+
                    '</a><br><button class="btn-none" onclick = "showallstate('+food_id+','+food_state2+')">'+food_state1+'</button>'+
                    '<br><button class="btn-none" onclick = "del(' + id + ')">删除</button></td></tr>';
            }
            $("#mytr").html(mm);

//                    if(cate == 21){
            var str='<ul class="pagination">'
            var page_count = data.page_count;
            if(page_count < 2){
                for(var i=1;i<=page_count;i++){
                    if(i == p){
                        str += '<li class="active"><a href="javascript:void(0)" onclick="showPage(this,'+page_count+')">'+i+'</a></li>';
                    }else{
                        str += '<li><a href="javascript:void(0)" onclick="showPage(this,'+page_count+')">'+i+'</a></li>';
                    }
                }
            }else{
                str += '<li><a href="javascript:void(0)" onclick="showPage(this,'+page_count+')">《</a></li>';
                for(var i=1;i<=page_count;i++){
                    if(i == p){
                        str += '<li class="active"><a href="javascript:void(0)" onclick="showPage(this,'+page_count+')">'+i+'</a></li>';
                    }else{
                        str += '<li><a href="javascript:void(0)" onclick="showPage(this,'+page_count+')">'+i+'</a></li>';
                    }
                }
                str += '<li><a href="javascript:void(0)" onclick="showPage(this,'+page_count+')">》</a></li>';
            }
            str += '</ul>';
            $("#all ul").remove();
            $("#all").append(str);
//                    }else{
//                        var url = "/index.php/admin/dishes/showDisinfoBykey"
//                    }

        },
        error:function(){
            alert("出错了");
        }
    });
}

function showPage(obj,page_count){
//            alert($(obj).html());
    var food_category_id = $(obj).data("cate_id");
    var p = $(obj).html();
//            $(obj).attr("class","active");
    if(p == "》"){
        var li_list1 = $(obj).parent().parent().find('li');
        $.each(li_list1,function(k,v){
            if($(this).attr("class") == "active"){
                var p2 = $(this).children().eq(0).html();
                console.log(p2);
                p2 = parseInt(p2);
                if(p2 == page_count){
                    alert("此页已经是最后页了");
                }else{
                    p2 = parseInt(p2)+1;
                    page(p2);
                    console.log(p2);
                }
            }
        });
    }else if (p == "《"){
        var li_list2 = $(obj).parent().parent().find('li');
        $.each(li_list2,function(k,v){
            if($(this).attr("class") == "active"){
                var p2 = $(this).children().eq(0).html();
                p2 = parseInt(p2);
                console.log(p2);
                if(p2 == 1){
                    alert("此页已经是最前页了");
                }else{
                    p2 = parseInt(p2)-1;
                    page(p2);
                    console.log(p2);
                }
            }
        });
    }else{
//                $(obj).attr("class","active");
        page(p);
    }
}

function showPage2(obj,page_count,cate_id){
    var food_category_id = cate_id;
    var p = $(obj).html();
    if (p == "》") {
        var li_list1 = $(obj).parent().parent().find('li');
        $.each(li_list1, function (k, v) {
            if ($(this).attr("class") == "active") {
                var p2 = $(this).children().eq(0).html();
//                        console.log(p2);
                p2 = parseInt(p2);
                if (p2 == page_count) {
                    alert("此页已经是最后页了");
                } else {
                    p2 = parseInt(p2) + 1;
                    showinfo(p2,"",food_category_id);
//                            console.log(p2);
                }
            }
        });
    } else if (p == "《") {
        var li_list2 = $(obj).parent().parent().find('li');
        $.each(li_list2, function (k, v) {
            if ($(this).attr("class") == "active") {
                var p2 = $(this).children().eq(0).html();
                p2 = parseInt(p2);
//                        console.log(p2);
                if (p2 == 1) {
                    alert("此页已经是最前页了");
                } else {
                    p2 = parseInt(p2) - 1;
                    showinfo(p2,"",food_category_id);
//                            console.log(p2);
                }
            }
        });
    } else {
        //$(obj).attr("class","active");
        showinfo(p,"",food_category_id);
    }
}

function moveup(a,f){
    alert("上");
    $.ajax({
        type:"post",
        url:"/index.php/admin/dishes/moveup",
        data:{"id":a,"food_category_id":f},
        success:function(data){
            var mm = '<tr class="text-center"><td></td><td>排序</td><td>名称</td><td>图片</td><td>价格</td><td>类别</td><td>星级</td><td>时价</td><td>状态</td><<td></td></tr>';
            var value = data;
            //alert(value);
            for (var i in value) {
                var id = value[i].id;
                var food_name = value[i].food_name;
                var food_id = value[i].food_id;
                var food_price = value[i].food_price;
                var food_category_name = value[i].food_category_name;
                var food_category_id = value[i].food_category_id;
                var star_level = value[i].star_level;
                var food_img = value[i].food_img;
                var is_prom = value[i].is_prom ? "开启" : "关闭";
                var food_state2 = value[i].is_sale;
                var food_state = (value[i].is_sale==1) ? "上架" : "下架 ";
                var food_state1 = (value[i].is_sale==1) ? "下架" : "上架 ";
                //var is_prom = value[i].is_prom;
                if(star_level == 1){
                    var star = "★";
                }else if(star_level == 2){
                    var star = "★★";
                }else if(star_level == 3){
                    var star = "★★★";
                }else if(star_level == 4){
                    var star = "★★★★";
                }else{
                    var star = "★★★★★";
                }


                mm += '<tr><td>' + id + '</td><td><button class="btn-none" onclick = "moveup('+id+','+food_category_id+')"><img src="__PUBLIC__/images/up.png" ></button>'+
                    '<a href = "/index.php/admin/Dishes/movedown/id/'+id+'"><button class="btn-none"><img src="__PUBLIC__/images/down.png"></button></a>' +
                    '</td><td>' + food_name + '</td><td><img src = "/Application/Admin' + food_img + '" class="dishes-img"></td><td>' + food_price + '元</td><td>'+food_category_name+'</td>'+
                    '<td id = "star">'+star+'</td><td>'+is_prom+'</td><td>'+food_state+'</td><td><a href = "/index.php/admin/Dishes/edit/food_id/' + food_id + '"><button class="btn-none">编辑</button>'+
                    '</a><br><button class="btn-none" onclick = "showstate('+food_id+','+food_state2+','+food_category_id+')">'+food_state1+'</button>'+
                    '<br><button class="btn-none" onclick = "del(' + id + ')">删除</button></td></tr>';
            }
            $("#mytr").html(mm);
        }
    });
}

function movedown(b){
    alert("下");
    $.ajax({
        type:"post",
        url:"/index.php/admin/dishes/movedown",
        data:{"id":b},
        success:function(data){
            var mm = '<tr class="text-center"><td></td><td>排序</td><td>名称</td><td>图片</td><td>价格</td><td>类别</td><td>星级</td><td>时价</td><td>状态</td><<td></td></tr>';
            var value = data;
            //alert(value);
            for (var i in value) {
                var id = value[i].id;
                var food_name = value[i].food_name;
                var food_id = value[i].food_id;
                var food_price = value[i].food_price;
                var food_category_name = value[i].food_category_name;
                // var food_category_id = value[i].food_category_id;
                var star_level = value[i].star_level;
                var food_img = value[i].food_img;
                var is_prom = value[i].is_prom ? "开启" : "关闭";
                var food_state2 = value[i].is_sale;
                var food_state = (value[i].is_sale==1) ? "上架" : "下架 ";
                var food_state1 = (value[i].is_sale==1) ? "下架" : "上架 ";
                //var is_prom = value[i].is_prom;
                if(star_level == 1){
                    var star = "★";
                }else if(star_level == 2){
                    var star = "★★";
                }else if(star_level == 3){
                    var star = "★★★";
                }else if(star_level == 4){
                    var star = "★★★★";
                }else{
                    var star = "★★★★★";
                }


                mm += '<tr><td>' + id + '</td><td><button class="btn-none" onclick = "moveup('+id+')"><img src="__PUBLIC__/images/up.png" ></button>'+
                    '<button class="btn-none" onclick = "movedown('+id+')"><img src="__PUBLIC__/images/down.png"></button>' +
                    '</td><td>' + food_name + '</td><td><img src = "/Application/Admin' + food_img + '" class="dishes-img"></td><td>' + food_price + '元</td><td>'+food_category_name+'</td>'+
                    '<td id = "star">'+star+'</td><td>'+is_prom+'</td><td>'+food_state+'</td><td><a href = "/index.php/admin/Dishes/edit/food_id/' + food_id + '"><button class="btn-none">编辑</button>'+
                    '</a><br><button class="btn-none" onclick = "showallstate('+food_id+','+food_state2+')">'+food_state1+'</button>'+
                    '<br><button class="btn-none" onclick = "del(' + id + ')">删除</button></td></tr>';
            }
            $("#mytr").html(mm);
        }
    });
}


function modify1(c){
    $('#way').attr('value','edit');
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/updDishestype",
        data: {"food_category_id": c},
        success: function (data) {
            console.log(data);
            $('#food_category_id').attr("value",data.food_category_id);
            $('#food_category_name').attr("value",data.food_category_name);
            $("#edit_upload_box").attr('src',"/"+data.image);
            //$("#d").attr('action',"/index.php/admin/dishes/modifyDishestype/food_category_id/"+data.food_category_id);
            //$("#food_category_id").attr("value",data.food_category_id);
            if(data.is_timing == 1){
                $("input[name='is_timing']:eq(1)").prop("checked",true);
                $("#show2").show();
                $("#time").html("");
                if(data.category_time){

                    $.each(data.category_time,function(k,v){
                        var time1 = v['time1'];
                        var time2 = v['time2'];
                        var str = '<div style="margin-top: 5px"> <label for="startTime">开始时间：</label><input type="text" class="startTime" id="startTime" name="startTime" value="'+time1+'"> <label for="endTime">结束时间：</label><input type="text" name="endTime" class="endTime" id="endTime" value="'+time2+'"> </div>';
                        $("#time").append(str);
                    });
                    triggerTime();
                }
                $("#day").html("");
                if(data.category_timing){

                    $.each(data.category_timing,function(){
                        var str = '<div style="margin-top: 5px"> <input type="checkbox" name="monday" value="1"><label >星期一</label>'
                            +'<input type="checkbox" name="tuesday" value="2"><label>星期二</label>'
                            +'<input type="checkbox" name="wednesday" value="3"><label>星期三</label>'
                            +'<input type="checkbox" name="thursday" value="4"><label >星期四</label>'
                            +'<input type="checkbox" name="friday" value="5"><label>星期五</label>'
                            +'<input type="checkbox" name="saturday" value="6"><label >星期六</label>'
                            +'<input type="checkbox" name="sunday" value="0"><label >星期日</label>'
                            +'<select name="dayStartTime" id="">'+
                            '</select> 至' +
                            ' <select name="dayEndTime" id="">'+
                            '</select></div>';
                        $("#day").append(str);
                    });
                    triggerDay();
                    $.each(data.category_timing,function(k1,v1){
                        var timingDay = v1['timing_day'];
                        var dayStartTime = v1['start_time'];
                        var dayEndTime = v1['end_time'];
                        $.each(timingDay,function(k2,v2){
                            $("#day div").eq(k1).find("input").each(function(){
                                if($(this).val() == v2){
                                    $(this)[0].checked = true;
                                }
                            });
                        });

                        $("#day select").each(function(k5,v5){
                            if(k5 == 0){
                                $(this).children().each(function(){
                                    console.log(dayStartTime);
                                    if($(this).html() == dayStartTime){
                                        $(this)[0].selected = true;
                                    };
                                });
                            }else{
                                $(this).children().each(function(){
                                    if($(this).html() == dayEndTime){
                                        $(this)[0].selected = true;
                                    };
                                });
                            }
                        });
                    });
                }



            }else{
                $("input[name='is_timing']:eq(0)").prop("checked",false);
            }
        },
        error:function(){
            alert("出错了");
        }
    });
}

//通过传进来的类别参数显示对应数据
function showinfo(p,obj,cate_id) {
    var cate_id2 = $(obj).data("id");
    console.log(p);
    if(!cate_id2){
        cate_id2 = cate_id;
//            console.log(id);
    }
    console.log(cate_id2);
//			document.getElementById('a1').href="/index.php/admin/Dishes/add/food_category_id/"+id+"";
    $.ajax({
        type: "POST",
        url: "/index.php/admin/dishes/showDisinfoBykey",
        data: {"food_category_id": cate_id2,"page":p},
        success: function (data) {
            console.log(data);
            var mm = '<tr class="text-center"><td></td><td>排序</td><td>名称</td><td>图片</td><td>价格</td><td>类别</td><td>星级</td><td>时价</td><td>状态</td><td></td></tr>';
            var value = data.data;
            console.log(value);
            //alert(value);
            for (var i in value) {
                var id = value[i].id;
                var food_name = value[i].food_name;
                var food_id = value[i].food_id;
                var food_price = value[i].food_price;
                var food_category_name = value[i].food_category_name;
                var food_category_id = value[i].food_category_id;
                var star_level = value[i].star_level;
                var food_img = value[i].food_img;
                var is_prom = value[i].is_prom ? "开启" : "关闭";
                var food_state2 = value[i].is_sale;
                var food_state = (value[i].is_sale==1) ? "上架" : "下架 ";
                var food_state1 = (value[i].is_sale==1) ? "下架" : "上架 ";
                // alert(food_category_id);
                //var is_prom = value[i].is_prom;
                if(star_level == 1){
                    var star = "★";
                }else if(star_level == 2){
                    var star = "★★";
                }else if(star_level == 3){
                    var star = "★★★";
                }else if(star_level == 4){
                    var star = "★★★★";
                }else{
                    var star = "★★★★★";
                }

                mm += '<tr><td>' + id + '</td><td><button class="btn-none" onclick = "moveup('+id+','+food_category_id+')"><img src="__PUBLIC__/images/up.png" ></button>'+
                    '<a href = "/index.php/admin/Dishes/movedown/id/'+id+'"><button class="btn-none"><img src="__PUBLIC__/images/down.png"></button></a>' +
                    '</td><td>' + food_name + '</td><td><img src = "/Application/Admin' + food_img + '" class="dishes-img"></td><td>' + food_price + '元</td><td>'+food_category_name+'</td>'+
                    '<td id = "star">'+star+'</td><td>'+is_prom+'</td><td>'+food_state+'</td><td><a href = "/index.php/admin/Dishes/edit/food_id/' + food_id + '"><button class="btn-none">编辑</button>'+
                    '</a><br><button class="btn-none" onclick = "showstate('+food_id+','+food_state2+','+food_category_id+')">'+food_state1+'</button>'+
                    '<br><button class="btn-none" onclick = "del(' + id + ')">删除</button></td></tr>';
            }
            $("#mytr").html(mm);

            var str='<ul class="pagination">'
            var page_count = data.page_count;
            if(page_count < 2){
                for(var i=1;i<=page_count;i++){
                    if(i == p){
                        str += '<li class="active"><a href="javascript:void(0)" onclick="showPage2(this,'+page_count+','+cate_id2+')">'+i+'</a></li>';
                    }else{
                        str += '<li><a href="javascript:void(0)" onclick="showPage2(this,'+page_count+','+cate_id2+')">'+i+'</a></li>';
                    }
                }
            }else{
                str += '<li><a href="javascript:void(0)" onclick="showPage2(this,'+page_count+','+cate_id2+')">《</a></li>';
                for(var i=1;i<=page_count;i++){
                    if(i == p){
                        str += '<li class="active"><a href="javascript:void(0)" onclick="showPage2(this,'+page_count+','+cate_id2+')">'+i+'</a></li>';
                    }else{
                        str += '<li><a href="javascript:void(0)" onclick="showPage2(this,'+page_count+','+cate_id2+')">'+i+'</a></li>';
                    }
                }
                str += '<li><a href="javascript:void(0)" onclick="showPage2(this,'+page_count+','+cate_id2+')">》</a></li>';
            }
            str += '</ul>';
            $("#all ul").remove();
            $("#all").append(str);
        }
    });
}


function del(c) {
    var msg = "您真的确定要删除吗？\n\n请确认！";
    if (confirm(msg)==true){
        $.ajax({
            type:"post",
            url:"/index.php/admin/Dishes/delfoodinfo",
            data:{"id":c},
            success:function(data){
                location.reload();//重新加载页面
            }
        });
    }else{
        //不做任何处理
    }
}


function deltype(cid){
    var msg = "您真的确定要删除吗？\n\n请确认！";
    console.log(cid);
    if (confirm(msg)==true){
        $.ajax({
            type:"post",
            url:"/index.php/admin/dishes/delDishestype",
            data:{"food_category_id":cid},
            // dataType:'json',
            success:function(data){
                //location.reload();//重新加载页面
                $('#leftcategory').html(data);
            },
            error:function(data){
                alert("出错了");
            }
        });
    }
}
//显示所有类别信息对应的改变菜品状态方法
function showallstate(z,s){
    //alert(s);
    $.ajax({
        type:"post",
        url:"/index.php/admin/Dishes/updstate",
        //dataType:"json",
        data:{"food_id":z,"food_state":s},
        success:function(data){
            alert('更改菜品状态成功');
            var mm = '<tr class="text-center"><td></td><td>排序</td><td>名称</td><td>图片</td><td>价格</td><td>类别</td><td>星级</td><td>时价</td><td>状态</td><<td></td></tr>';
            var value = data;
            //alert(value);
            for (var i in value) {
                var id = value[i].id;
                var food_name = value[i].food_name;
                var food_id = value[i].food_id;
                var food_price = value[i].food_price;
                var star_level = value[i].star_level;
                var food_img = value[i].food_img;
                var food_state2 = value[i].is_sale;
//                console.log(food_state2);
                var is_prom = value[i].is_prom ? "开启" : "关闭";
                var food_state = (value[i].is_sale==1) ? "上架" : "下架 ";
                var food_state1 = (value[i].is_sale==1) ? "下架" : "上架 ";
                //var is_prom = value[i].is_prom;
                if(star_level == 1){
                    var star = "★";
                }else if(star_level == 2){
                    var star = "★★";
                }else if(star_level == 3){
                    var star = "★★★";
                }else if(star_level == 4){
                    var star = "★★★★";
                }else{
                    var star = "★★★★★";
                }


                mm += '<tr><td>' + id + '</td><td><button class="btn-none" onclick = "moveup('+id+')"><img src="__PUBLIC__/images/up.png"></button><button class="btn-none" onclick = "movedown('+id+')"><img src="__PUBLIC__/images/down.png"></button>' +
                    '</td><td>' + food_name + '</td><td><img src = "/Application/Admin' + food_img + '" class="dishes-img"></td><td>' + food_price + '元</td><td>所有</td>'+
                    '<td id = "star">'+star+'</td><td>'+is_prom+'</td><td>'+food_state+'</td><td><a href = "/index.php/admin/Dishes/edit/food_id/' + food_id + '"><button class="btn-none">编辑</button>'+
                    '</a><br><button class="btn-none" onclick = "showstate('+food_id+','+food_state2+')">'+food_state1+'</button>'+
                    '<br><button class="btn-none" onclick = "del(' + id + ')">删除</button></td></tr>';
            }
            $("#mytr").html(mm);
        }
    });
}

function showstate(z,s,c){
    //alert(s);
    $.ajax({
        type:"post",
        url:"/index.php/admin/Dishes/updstate",
        //dataType:"json",
        data:{"food_id":z,"food_state":s,"food_category_id":c},
        success:function(data){
            alert('更改菜品状态成功');
            var mm = '<tr class="text-center"><td></td><td>排序</td><td>名称</td><td>图片</td><td>价格</td><td>类别</td><td>星级</td><td>时价</td><td>状态</td><<td></td></tr>';
            var value = data;
            for (var i in value) {
                var id = value[i].id;
                var food_name = value[i].food_name;
                var food_id = value[i].food_id;
                var food_price = value[i].food_price;
                var food_category_name = value[i].food_category_name;
                var food_category_id = value[i].food_category_id;
                var star_level = value[i].star_level;
                var food_img = value[i].food_img;
                var is_prom = value[i].is_prom ? "开启" : "关闭";
                var food_state2 = value[i].is_sale;
//                console.log(food_state2);
                var food_state = (value[i].is_sale==1) ? "上架" : "下架 ";
                var food_state1 = (value[i].is_sale==1) ? "下架" : "上架 ";
                //var is_prom = value[i].is_prom;
                if(star_level == 1){
                    var star = "★";
                }else if(star_level == 2){
                    var star = "★★";
                }else if(star_level == 3){
                    var star = "★★★";
                }else if(star_level == 4){
                    var star = "★★★★";
                }else{
                    var star = "★★★★★";
                }


                mm += '<tr><td>' + id + '</td><td><button class="btn-none"><img src="__PUBLIC__/images/up.png"></button><button class="btn-none"><img src="__PUBLIC__/images/down.png"></button>' +
                    '</td><td>' + food_name + '</td><td><img src = "/Application/Admin' + food_img + '" class="dishes-img"></td><td>' + food_price + '元</td><td>'+food_category_name+'</td>'+
                    '<td id = "star">'+star+'</td><td>'+is_prom+'</td><td>'+food_state+'</td><td><a href = "/index.php/admin/Dishes/edit/food_id/' + food_id + '"><button class="btn-none">编辑</button>'+
                    '</a><br><button class="btn-none" onclick = "showstate('+food_id+','+food_state2+','+food_category_id+')">'+food_state1+'</button>'+
                    '<br><button class="btn-none" onclick = "del(' + id + ')">删除</button></td></tr>';
            }
            $("#mytr").html(mm);
        }
    });
}





function commit(){
    console.log($("#way").val());
    if($("#way").val() != "edit"){
        if($("input[name='food_category_name']").val() == ""){
            alert("菜品分类不能为空");
        }else{
            var formdata=new FormData();
            formdata.append("food_category_name",$("input[name='food_category_name']").val());
            formdata.append("is_timing",$("input[name='is_timing']:checked").val());
            if ($("#commitfile").val() != ""){
                var reader = new FileReader();
                reader.readAsDataURL($('#commitfile')[0].files[0]);
                formdata.append("file",$('#commitfile')[0].files[0]);
            }
            if($("input[name='is_timing']:checked").val() == 1){
                var timeInfo = $("#time").children();
                var dayInfo = $("#day").children();
                var dayInfoArray = new Array();
                $.each(dayInfo,function(k,v){
                    dayInfoArray[k] = new Array();
                    var i = 0;
                    $.each($(v).children(),function(k1,v1){
                        var length = $(v).children().length;
                        if($(v1)[0].checked == true || k1 == (length-2) || k1 == (length-1)) {
                            dayInfoArray[k][i] = $(v1).val();
                            i++;
                        }
                    });
                });

                var timeInfoArray = new Array();
                $.each(timeInfo,function(k3,v3){
                    timeInfoArray[k3] = new Array();
                    var j = 0;
                    $.each($(v3).children(),function(k4,v4){
                        if($(v4).val() != "") {
                            timeInfoArray[k3][j] = $(v4).val();
                            j++;
                        }
                    });
                });
                timeInfoArray = JSON.stringify(timeInfoArray);
                dayInfoArray = JSON.stringify(dayInfoArray);
                formdata.append("time",timeInfoArray);
                formdata.append("day",dayInfoArray)
            }
            $.ajax({
                type : 'post',
                url : '/index.php/admin/Dishes/createDishetype',
                data:formdata,
                // dataType:"json",
                cache : false,
                processData : false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
                contentType : false, // 不设置Content-type请求头
                success : function(data){
                    $('#leftcategory').html(data);
                    alert("上传成功！");
                    $("input[type='reset']").trigger("click");
                }
            });
        }
    }else{
        //alert("编辑");
        var formdata=new FormData();
        formdata.append("restaturant_id",$("#restaturant_id").val());
        formdata.append("food_category_id",$("#food_category_id").val());
        formdata.append("food_category_name",$("input[name='food_category_name']").val());
        formdata.append("is_timing",$("input[name='is_timing']:checked").val());
        console.log($("#commitfile").val());
        if ($("#commitfile").val() != ""){
            var reader = new FileReader();
            reader.readAsDataURL($('#commitfile')[0].files[0]);
            formdata.append("file",$('#commitfile')[0].files[0]);
        }


        $.ajax({
            type : 'post',
            url : '/index.php/admin/Dishes/modifyDishestype',
            data:formdata,
            // dataType:"json",
            cache : false,
            processData : false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
            contentType : false, // 不设置Content-type请求头
            success : function(data){
                $('#leftcategory').html(data);
                alert("编辑成功！");
                //$("input[type='reset']").trigger("click");
            }
        });
    }
}



$('#addSort').on('hidden.bs.modal', function (){
    // 执行一些动作...
    $('#food_category_name').attr("value","");
    $('#edit_upload_box').attr("src","");
    $("input[type='reset']").trigger("click");
})



function deletetime(){
    $('.dingtime').each(function(index,element){
        $(element).remove((index));});
}

/*
 ===========================================================================================================
 */
function trigger(){
    triggerTime();
    triggerDay();
}

function triggerTime(){
    $('.startTime').datetimepicker({
        format: 'yyyy-mm-dd hh:ii:00',
        language: 'zh-CN',
        pickDate: true,
        pickTime: true,
        autocolse:true,
        hourStep: 1,
        minuteStep: 15,
        secondStep: 30,
        inputMask: true
    }).on("click",function(ev){
        $(".startTime").datetimepicker("setEndDate", $(".endTime").val());
    });
    $('.endTime').datetimepicker({
        format: 'yyyy-mm-dd hh:ii:00',
        language: 'zh-CN',
        autocolse:true,
        pickDate: true,
        pickTime: true,
        hourStep: 1,
        minuteStep: 15,
        secondStep: 30,
        inputMask: true
    }).on("click", function (ev) {
        $(".endTime").datetimepicker("setStartDate", $(".startTime").val());
    });
}

function triggerDay(){
    for(var i=0;i<24;i++){
        if(i<10){
            $("#day").children(":last").find("select").append("<option value='0"+i+":00'>0"+i+":00</option>");  //添加一项option
            $("#day").children(":last").find("select").append("<option value='0"+i+":30'>0"+i+":30</option>");  //添加一项option
        }else{
            $("#day").children(":last").find("select").append("<option value='"+i+":00'>"+i+":00</option>");  //添加一项option
            $("#day").children(":last").find("select").append("<option value='"+i+":30'>"+i+":30</option>");  //添加一项option
        }
    }
}

function changeType(type){
    $("#add-btn").data("type",type);
}

function addTiming(obj){
    var type = $(obj).data("type");
    if(type){
        var str = '<div style="margin-top: 5px"> <label for="startTime">开始时间：</label><input type="text" class="startTime" id="startTime" name="startTime"> <label for="endTime">结束时间：</label><input type="text" name="endTime" class="endTime" id="endTime"> </div>';
        $("#time").append(str);
    }else{
        var str = '<div style="margin-top: 5px"> <input type="checkbox" name="monday" value="1"><label >星期一</label>'
            +'<input type="checkbox" name="tuesday" value="2"><label>星期二</label>'
            +'<input type="checkbox" name="wednesday" value="3"><label>星期三</label>'
            +'<input type="checkbox" name="thursday" value="4"><label >星期四</label>'
            +'<input type="checkbox" name="friday" value="5"><label>星期五</label>'
            +'<input type="checkbox" name="saturday" value="6"><label >星期六</label>'
            +'<input type="checkbox" name="sunday" value="0"><label >星期日</label>'
            +'<select name="dayStartTime" id="">' +
            '</select> 至' +
            ' <select name="dayEndTime" id="">'+
            '</select>';
        $("#day").append(str);
    }
    trigger();
}

$("#addSort").on("hidden.bs.modal",function(){
    $("#time").html("");
    $("#day").html("");
    $("#show2").hide();
});