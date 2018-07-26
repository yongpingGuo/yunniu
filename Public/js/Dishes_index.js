$(document).ready(function() {
    showtime2();
});

function showtime() {
    document.getElementById('show1').style.display = "";
}

function hiddentime() {
    document.getElementById('show1').style.display = "none";
}

function showtime2() {
    var hschek = $("input[name='is_timing']").is(':checked');
    if (hschek) {

        document.getElementById('show2').style.display = "";
    } else {
        document.getElementById('show2').style.display = "none";
    }

}

//新增菜品分类
function show_addSort() {
    $("#addSort").modal('show');
    $('#way').val(0);
    $('#food_category_name').val('');
    $('#food_category_name_en').val('');
    $("input[name='is_timing']").prop("checked", false);
}
//编辑菜品分类
function modify1(c) {
    $('#way').val(1);
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/updDishestype",
        data: { "food_category_id": c },
        success: function(data) {
            //$("input[name='Restaurant']").attr("value",data.restaurant_id);
            $('#restaurant_id').attr("value", data.restaurant_id);
            $('#food_category_id').attr("value", data.food_category_id);
            $('#food_category_name').val(data.food_category_name);
            $('#food_category_name_en').val(data.food_category_name_en);
            $("#edit_upload_box").attr('src', "/" + data.image);

            $("#classify-icon").attr('src', data.img_url);
            $("#img_url").val(data.img_url);
            $("#ico_category_type").val(data.ico_category_type);

            //$("#d").attr('action',"/index.php/admin/dishes/modifyDishestype/food_category_id/"+data.food_category_id);
            //$("#food_category_id").attr("value",data.food_category_id);
            if (data.is_timing == 1) {
                $("input[name='is_timing']").prop("checked", true);
                $("#show2").show();
                $("#time").html("");
                if (data.category_time) {
                    $.each(data.category_time, function(k, v) {
                        var time1 = v['time1'];
                        var time2 = v['time2'];
                        var str = '<div class="modal-item">\
                    					<div class="inline-block">\
	                        				<label for="startTime">'+vm.langData.start[vm.lang]+':</label>\
	                        				<input type="text" class="startTime selectIcon" id="startTime" name="startTime" value="' + time1 + '">\
                        				</div>\
                        				<label for="endTime">'+vm.langData.end[vm.lang]+':</label>\
                        				<input type="text" name="endTime selectIcon" class="endTime" id="endTime" value="' + time2 + '">\
                        				<button>\
                        					<img src="/public/images/remove_circle.png">\
                        				</button>\
                        			</div>';
                        $("#time").append(str);
                    });
                    triggerTime();
                }

                $("#day").html("");
                if (data.category_timing) {
                    $.each(data.category_timing, function() {
                        var str = '<div>\
										<input type="checkbox" name="monday" value="1"><label>'+vm.langData.Monday[vm.lang]+'</label>\
										<input type="checkbox" name="tuesday" value="2"><label>'+vm.langData.Tuesday[vm.lang]+'</label>\
										<input type="checkbox" name="wednesday" value="3"><label>'+vm.langData.Wednesday[vm.lang]+'</label>\
										<input type="checkbox" name="thursday" value="4"><label>'+vm.langData.Thursday[vm.lang]+'</label>\
										<input type="checkbox" name="friday" value="5"><label>'+vm.langData.Friday[vm.lang]+'</label>\
										<input type="checkbox" name="saturday" value="6"><label>'+vm.langData.Saturday[vm.lang]+'</label>\
										<input type="checkbox" name="sunday" value="0"><label>'+vm.langData.Sunday[vm.lang]+'</label>\
										<span class="select-reset mini">\
											<select name="dayStartTime" id="">\
											</select>\
										</span>\
										<span>-</span>\
										<span class="select-reset mini">\
											<select name="dayEndTime" id="">\
											</select>\
										</span>\
										<button>\
											<img src="/public/images/remove_circle.png">\
										</button>\
		                            </div>';
                        $("#day").append(str);
                        triggerDay();
                    });

                    $.each(data.category_timing, function(k1, v1) {
                        var timingDay = v1['timing_day'];
                        var dayStartTime = v1['start_time'];
                        var dayEndTime = v1['end_time'];
                        $.each(timingDay, function(k2, v2) {
                            $("#day div").eq(k1).find("input").each(function() {
                                if ($(this).val() == v2) {
                                    $(this)[0].checked = true;
                                }
                            });
                        });

                        $("#day select").each(function(k5, v5) {
                            if (k5 == k1 * 2) {
                                $(this).children().each(function() {
                                    if ($(this).html() == dayStartTime) {
                                        $(this)[0].selected = true;
                                    };
                                });
                            } else if ((k5 == k1 * 2 + 1)) {
                                $(this).children().each(function() {
                                    if ($(this).html() == dayEndTime) {
                                        $(this)[0].selected = true;
                                    };
                                });
                            }
                        });
                    });
                }
            } else {
                $("input[name='is_timing']").prop("checked", false);
            }
        },
        error: function() {
            alert(vm.langData.error[vm.lang]);
        }
    });
}



function deltype(cid) {
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "get",
            url: "/index.php/admin/dishes/delDishestype/food_category_id/" + cid + "",
            success: function(data) {
                if (data == 1) {
                    layer.msg(vm.langData.canNotDeleteCategory[vm.lang]);
                } else {
                    $('#mytype').html(data);
                    layer.msg(vm.langData.success[vm.lang]);
                }
            },
            error: function() {
                alert(vm.langData.error[vm.lang]);
            }
        });
    });
}

//改变菜品上下架操作
function changestatu(i) {
	var theEvent = window.event || arguments.callee.caller.arguments[0];
	theEvent.preventDefault();
    var food_category_id = $("#food_category_id").val();
    if (food_category_id == "") {
        food_category_id = 0;
    } else {
        food_category_id = food_category_id;
    }
    var page = parseInt($('.current').data('page'));
    if (page == "NaN") {
        page == 1;
    } else {
        page = parseInt($('.current').data('page'));
    }
    layer.confirm('', {
        title: vm.langData.changeDishStatus[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
        $.ajax({
            type: "get",
            url: "/index.php/admin/Dishes/updstate/food_id/" + i + "/food_category_id/" + food_category_id + "/page/" + page + "",
            async: true,
            success: function(data) {
                $('#mytr').html(data);
                layer.msg(vm.langData.success[vm.lang]);
            }
        });
    });
}

//删除菜品表信息
function delfoodinfo(food_id) {

    var food_category_id = $("input[name='food_category_name']").val();
    var tr_leng = $("#mytr").children().children('tr').length;
    console.log(tr_leng);
    if (tr_leng > 2) {
        var page = parseInt($('.current').text());
    } else {
        var page = parseInt($('.current').text() - 1);
    }
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
    	$.ajax({
    	    type: "get",
    	    url: "/index.php/admin/Dishes/delfoodinfo/food_id/" + food_id + "/page/" + page + "",
    	    async: true,
    	    success: function(data) {
    	        $('#mytr').html(data);
    	        layer.msg(vm.langData.success[vm.lang]);
    	    }
    	});
    });
}

//删除菜品关联表信息
function delfoodinfo1(id) {
    var food_category_id = $("#food_category_id").val();
    var tr_leng = $("#mytr").children().children('tr').length;
    console.log(tr_leng);
    if (tr_leng > 2) {
        var page = parseInt($('.current').text());
    } else {
        var page = parseInt($('.current').text() - 1);
    }
    console.log(food_category_id);
    console.log(page);
    layer.confirm('', {
        title: vm.langData.deleteConfirm[vm.lang],
        btn: [vm.langData.yes[vm.lang], vm.langData.cancel[vm.lang]]
    }, function(index) {
    	$.ajax({
    	    type: "get",
    	    url: "/index.php/admin/Dishes/delfoodinfo1/id/" + id + "/food_category_id/" + food_category_id + "/p/" + page + "",
    	    async: true,
    	    success: function(data) {
    	        $('#mytr').html(data);
    	        layer.msg(vm.langData.success[vm.lang]);
    	    }
    	});
    });
}
//菜品分类数据上移
function moveup1(obj) {
    var sort = $(obj).data('sort');
    var food_category_id = $(obj).data('food_category_id');
    var tr = $(obj).parents("tr");
    console.log(tr.index());
    /*console.log(tr.index());*/
    if (tr.index() != 0) {
        $.ajax({
            type: "post",
            url: "/index.php/admin/dishes/moveup1",
            data: { "sort": sort, "food_category_id": food_category_id },
            success: function(data) {
                $('#mytype').html(data);
                layer.msg(vm.langData.success[vm.lang]);
            },
            error: function() {
                alert(vm.langData.error[vm.lang]);
            }
        });
    }
}

//菜品分类数据下移
function movedown1(obj) {
    var len = parseInt(($("#mytype").find('tr').length) - 1);
    //console.log(len);
    var sort = $(obj).data('sort');
    //console.log(sort);
    var food_category_id = $(obj).data('food_category_id');
    //console.log(food_category_id);
    var tr = $(obj).parents("tr");
    //console.log(tr.index());
    if (tr.index() != len) {
        $.ajax({
            type: "post",
            url: "/index.php/admin/dishes/movedown1",
            data: { "sort": sort, "food_category_id": food_category_id },
            success: function(data) {
                $('#mytype').html(data);
                layer.msg(vm.langData.success[vm.lang]);
            },
            error: function() {
                layer.msg(vm.langData.error[vm.lang]);
            }
        });
    }
}



/*	function movedown(obj){ 
    	var len = $(".movedown").length; 
	    var $tr = $(obj).parents("tr"); 
    	if ($tr.index() != len) { 
     	 $tr.fadeOut().fadeIn(); 
      	$tr.next().after($tr); 
    	} 
 }  */

//菜品数据上移(food表)
function moveup(obj) {
    var sort = $(obj).data('sort'); //排序ID
    var food_id = $(obj).data('food_id'); //菜品自增ID
    var when_tr = parseInt($(obj).data('index'));
    console.log(when_tr);
    var page = $(".current").data('page'); //当前页数
    if (page == undefined) {
        page = 1;
    }
    console.log(sort, food_id);
    if (page == 1 && when_tr == 1) {
        return false;
    }
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/moveup",
        data: { "sort": sort, "food_id": food_id },
        dataType: "json",
        success: function(data) {
            if (data.code == 1) {
                $.ajax({
                    url: "/index.php/admin/Dishes/deskInfo/page/" + page,
                    type: "get",
                    success: function(data) {
                        $("#mytr").html(data);
                    },
                    error: function() {
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                });
            }
        }
    });
}

//菜品数据下移(food表)
function movedown(obj) {
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var page = parseInt($(".current").html());
    var pageArr = new Array();
    $(".pagination").children().children('a').each(function(index, element) {
        var when_page = parseInt($(element).data('page'));
        pageArr[index] = when_page;
    });
    var max = pageArr[0]
    for (var i = 1; i < pageArr.length; i++) {
        if (pageArr[i] > max) {
            max = pageArr[i]; //获取最大页数
        }
    }
    var last_tr = $("#mytr").children().children('tr:last'); //获取最后一个tr
    var downObj = $(obj).parent(); //获取点击时的tr
    if (page == max && last_tr == downObj) { //如果当前页是最后一页且所点击的tr是最后一个tr，则中止操作
        return false;
    }
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/movedown",
        data: { "sort": sort, "food_id": food_id },
        dataType: "json",
        success: function(data) {
            if (data.code == 1) {
                $.ajax({
                    url: "/index.php/admin/Dishes/deskInfo/page/" + page,
                    type: "get",
                    success: function(data) {
                        $("#mytr").html(data);
                        layer.msg(vm.langData.success[vm.lang]);
                    },
                    error: function() {
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                });
            }
        }
    });
}

//点击菜品类表显示对应菜品信息		
function showinfo(obj) {
    var food_category_id = $(obj).data("id");
    $.ajax({
        type: "get",
        url: "/index.php/admin/dishes/showDisinfoBykey/food_category_id/" + food_category_id + "",
        success: function(data) {
            $('#mytr').html(data);
        }
    });
    $(obj).parents('tr').siblings().removeClass('active');
    $(obj).parents('tr').addClass('active');
}

//提交菜品分类模态框
function commit() {
    var hschek = $("input[name='is_timing']").is(':checked');
    // 判断是否开启定时。0：关闭，1：开启
    if (hschek) {
        status = 1;
    } else {
        status = 0;
    }

    if ($("#way").val() != 1) {
        // 新增菜品分类
        if ($("#food_category_name").val() == "") {
            layer.msg(vm.langData.dishesCategoryEmpty[vm.lang]);
        } else {
            var food_category_name = $("#food_category_name").val();
            var formdata = new FormData();
            formdata.append("food_category_name", food_category_name);
            formdata.append("food_category_name_en", $("#food_category_name_en").val());
            formdata.append("is_timing", status);

            // 图标URL
            var img_url = $("#img_url").val();
            formdata.append("img_url", img_url);
            // 图标类型
            var ico_category_type = $('#ico_category_type').val();
            formdata.append("ico_category_type", ico_category_type);
            // 自定义文件域
            formdata.append("user_define_img", $("#user_define_img")[0].files[0]);


            if (status == 1) {
                var timeInfo = $("#time").children();
                var dayInfo = $("#day").children();
                //console.log(dayInfo);
                var dayInfoArray = new Array();
                $.each(dayInfo, function(k, v) {
                    dayInfoArray[k] = new Array();
                    var i = 0;
                    $.each($(v).children(), function(k1, v1) {
                        //console.log($(v1),k1);
                        var length = $(v).children().length;
                        if ($(v1)[0].checked == true || k1 == (length - 4) || k1 == (length - 2)) {
                            if (k1 == (length - 4) || k1 == (length - 2)) {
                                dayInfoArray[k][i] = $(v1).children().val();
                            } else {
                                dayInfoArray[k][i] = $(v1).val();
                            }
                            i++;
                        }
                    });
                    //console.log($(v).children().length);
                });


                var timeInfoArray = new Array();
                $.each(timeInfo, function(k3, v3) {
                    timeInfoArray[k3] = new Array();
                    var j = 0;
                    $.each($(v3).children(), function(k4, v4) {
                        if (k4 == 0) {
                            var start_value = $(v4).children('input').val();
                            if (start_value != '') {
                                timeInfoArray[k3][j] = start_value;
                                j++;
                            }
                        } else {
                            if ($(v4).val() != "") {
                                timeInfoArray[k3][j] = $(v4).val();
                                j++;
                            }
                        }
                    });
                });

                timeInfoArray = JSON.stringify(timeInfoArray);
                dayInfoArray = JSON.stringify(dayInfoArray);
                formdata.append("time", timeInfoArray);
                formdata.append("day", dayInfoArray)
            }
            $.ajax({
                type: 'post',
                url: '/index.php/admin/Dishes/createDishetype',
                data: formdata,
                // dataType:"json",
                cache: false,
                processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
                contentType: false, // 不设置Content-type请求头
                success: function(data) {
                    $('#mytype').html(data);
                    // alert("新增成功！");
                    $("#classify-icon").attr('src', '/public/images/defaultFoodCate.png');

                    var file = $("#user_define_img")
                    file.after(file.clone().val(""));
                    file.remove();

                    $("input[type='reset']").trigger("click");
                }
            });
        }
    } else {
        // 编辑菜品分类
        var formdata = new FormData();
        formdata.append("restaurant_id", $("#restaurant_id").val());
        formdata.append("food_category_id", $("#food_category_id").val());
        formdata.append("food_category_name", $("#food_category_name").val());
        formdata.append("food_category_name_en", $("#food_category_name_en").val());
        formdata.append("is_timing", status);

        // 图标URL
        var img_url = $("#img_url").val();
        formdata.append("img_url", img_url);
        // 图标类型
        var ico_category_type = $('#ico_category_type').val();
        formdata.append("ico_category_type", ico_category_type);
        // 文件域
        formdata.append("user_define_img", $("#user_define_img")[0].files[0]);

        // if ($("#commitfile").val() != ""){
        //     var reader = new FileReader();
        //     reader.readAsDataURL($('#commitfile')[0].files[0]);
        //     formdata.append("file",$('#commitfile')[0].files[0]);
        // }

        if (status == 1) {
            var timeInfo = $("#time").children();
            var dayInfo = $("#day").children();
            //console.log(dayInfo);
            var dayInfoArray = new Array();
            $.each(dayInfo, function(k, v) {
                dayInfoArray[k] = new Array();
                var i = 0;
                $.each($(v).children(), function(k1, v1) {
                    //console.log($(v1),k1);
                    var length = $(v).children().length;
                    if ($(v1)[0].checked == true || k1 == (length - 4) || k1 == (length - 2)) {
                        if (k1 == (length - 4) || k1 == (length - 2)) {
                            dayInfoArray[k][i] = $(v1).children().val();
                        } else {
                            dayInfoArray[k][i] = $(v1).val();
                        }
                        i++;
                    }
                });
                //console.log($(v).children().length);
            });


            var timeInfoArray = new Array();
            $.each(timeInfo, function(k3, v3) {
                timeInfoArray[k3] = new Array();
                var j = 0;
                $.each($(v3).children(), function(k4, v4) {
                    if (k4 == 0) {
                        var start_value = $(v4).children('input').val();
                        if (start_value != '') {
                            timeInfoArray[k3][j] = start_value;
                            j++;
                        }
                    } else {
                        if ($(v4).val() != "") {
                            timeInfoArray[k3][j] = $(v4).val();
                            j++;
                        }
                    }
                });
            });

            timeInfoArray = JSON.stringify(timeInfoArray);
            dayInfoArray = JSON.stringify(dayInfoArray);
            formdata.append("time", timeInfoArray);
            formdata.append("day", dayInfoArray)
        }

        $.ajax({
            type: 'post',
            url: '/index.php/admin/Dishes/modifyDishestype',
            data: formdata,
            // dataType:"json",
            cache: false,
            processData: false, // 不处理发送的数据，因为data值是Formdata对象，不需要对数据做处理
            contentType: false, // 不设置Content-type请求头
            success: function(data) {
                $('#mytype').html(data);
                //alert("编辑成功！");
                //$("input[type='reset']").trigger("click");
                $("#classify-icon").attr('src', '/public/images/defaultFoodCate.png');

                var file = $("#user_define_img")
                file.after(file.clone().val(""));
                file.remove();
            }
        });

    }
}

//模态框消失后清空表单
$('#addSort').on('hidden.bs.modal', function() {
    // 执行一些动作...
    $('#food_category_name').attr("value", "");
    $('#edit_upload_box').attr("src", "");
    $("input[type='reset']").trigger("click");
    $("#time").html("");
    $("#day").html("");
    $("#show2").hide();
})

//删除时间
function deletetime() {
    $('.dingtime').each(function(index, element) {
        $(element).remove((index));
    });
}

/*
 ===========================================================================================================
 */
function trigger() {
    triggerTime();
    triggerDay();
}

function triggerTime() {
    $('.startTime').datetimepicker({
        format: 'yyyy-mm-dd hh:ii:00',
        language: 'zh-CN',
        pickDate: true,
        pickTime: true,
        autocolse: true,
        hourStep: 1,
        minuteStep: 15,
        secondStep: 30,
        inputMask: true
    }).on("click", function(ev) {
        $(".startTime").datetimepicker("setEndDate", $(".endTime").val());
    });
    $('.endTime').datetimepicker({
        format: 'yyyy-mm-dd hh:ii:00',
        language: 'zh-CN',
        autocolse: true,
        pickDate: true,
        pickTime: true,
        hourStep: 1,
        minuteStep: 15,
        secondStep: 30,
        inputMask: true
    }).on("click", function(ev) {
        $(".endTime").datetimepicker("setStartDate", $(".startTime").val());
    });
}

function triggerDay() {
    for (var i = 0; i < 24; i++) {
        if (i < 10) {
            /*$("#day").children(":last").find("select").append("<option onclick='assign()' value='0"+i+":00'>0"+i+":00</option>");  //添加一项option
            $("#day").children(":last").find("select").append("<option value='0"+i+":30'>0"+i+":30</option>");  //添加一项option*/
            $("#day").children(":last").find("select").append("<option value='0" + i + ":00'>0" + i + ":00</option>"); //添加一项option
            $("#day").children(":last").find("select").append("<option value='0" + i + ":30'>0" + i + ":30</option>"); //添加一项option
        } else {
            $("#day").children(":last").find("select").append("<option value='" + i + ":00'>" + i + ":00</option>"); //添加一项option
            $("#day").children(":last").find("select").append("<option value='" + i + ":30'>" + i + ":30</option>"); //添加一项option
        }
    }
}

function changeType(type) {
    $("#add-btn").data("type", type);
}

//添加时间段
function addTiming(obj) {
    var type = $(obj).data("type");
    if (type) {
        var str = '<div class="modal-item">\
	        				<div class="inline-block">\
	        					<label for="startTime">'+vm.langData.start[vm.lang]+':</label>\
	        					<input type="text" class="startTime selectIcon" id="startTime" name="startTime">\
	        				</div>\
	        				<label for="endTime">'+vm.langData.end[vm.lang]+':</label>\
	        				<input type="text" name="endTime" class="endTime selectIcon" id="endTime">\
	        				<button>\
	        					<img src="/public/images/remove_circle.png">\
	        				</button>\
        				</div>';
        $("#time").append(str);
    } else {
        var str = '<div class="modal-item">\
							<input type="checkbox" name="monday" value="1"><label>'+vm.langData.Monday[vm.lang]+'</label>\
							<input type="checkbox" name="tuesday" value="2"><label>'+vm.langData.Tuesday[vm.lang]+'</label>\
							<input type="checkbox" name="wednesday" value="3"><label>'+vm.langData.Wednesday[vm.lang]+'</label>\
							<input type="checkbox" name="thursday" value="4"><label>'+vm.langData.Thursday[vm.lang]+'</label>\
							<input type="checkbox" name="friday" value="5"><label>'+vm.langData.Friday[vm.lang]+'</label>\
							<input type="checkbox" name="saturday" value="6"><label>'+vm.langData.Saturday[vm.lang]+'</label>\
							<input type="checkbox" name="sunday" value="0"><label>'+vm.langData.Sunday[vm.lang]+'</label>\
							<span class="select-reset mini">\
								<select name="dayStartTime">\
								</select>\
							</span>\
							<span>-</span>\
							<span class="select-reset mini">\
								<select name="dayEndTime">\
								</select>\
							</span>\
							<button>\
								<img src="/public/images/remove_circle.png">\
							</button>\
                        </div>';
        $("#day").append(str);
    }
    trigger();
}

//点击页码执行动作
$("#detail-page").children().children("a").click(function() {
    var page = parseInt($(this).data("page"));
    $.ajax({
        url: "/index.php/admin/Dishes/deskInfo/page/" + page + "",
        type: "get",
        success: function(data) {
            $("#mytr").html(data);
        },
        error: function() {
            layer.msg(vm.langData.error[vm.lang]);
        }
    });
});



//点菜品编辑跳到指定编辑页且传递一个当前页数
function modify_food(obj) {
    var food_id = $(obj).data('food_id');
    var food_category_id = $(obj).data('food_category_id');
    var page = $('.current').data('page');
    console.log(food_category_id);
    if (food_category_id == undefined) {
        food_category_id = 0;
    }
    location.href = "/index.php/admin/Dishes/edit/food_id/" + food_id + "/food_category_id/" + food_category_id + "/page/" + page;
}