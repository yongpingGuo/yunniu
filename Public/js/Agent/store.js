	/**
	 * 作者：凯
	 * 日期：2017.01.10
	 */




	// 百度地图：首先创建地图实例，之后用一个Point坐标点和缩放级别来初始化地图。
	var map = new BMap.Map("map_container"); // 创建地图实例  
	map.centerAndZoom(new BMap.Point(116.404, 39.915), 18); // 初始化地图，设置中心点坐标和地图级别     
	map.enableScrollWheelZoom(true); //开启鼠标滚轮缩放
	var lat, lng;

	// 创建地理编码实例      
	var myGeo = new BMap.Geocoder();

	function searchAddress() {
	    var address = $('#province option:selected').html() + $('#city option:selected').html() +
	        $('#area option:selected').html() + document.getElementById("address").value;
	    var city = $('#province option:selected').html() + $('#city option:selected').html();
	    // 将地址解析结果显示在地图上，并调整地图视野    
	    // 创建地址解析器实例     
	    var myGeo = new BMap.Geocoder();
	    // 将地址解析结果显示在地图上，并调整地图视野    
	    myGeo.getPoint(address, function(point) {
	        if (point) {
	            lat = point.lat;
	            lat = point.lng;
	            map.centerAndZoom(point, 19);
	            map.addOverlay(new BMap.Marker(point));
	        }
	    }, city);
	}

	//点击新增显示省
	function addStore() {
	    $("input[name='form_id']").attr("value", 0);
	    $.ajax({
	        type: "get",
	        url: "/index.php/Agent/Store/show_province",
	        async: true,
	        dataType: "json",
	        success: function(data) {
	            var value = data;
	            var mm = "<option value='0'>请选择</option>";
	            for (var i in value) {
	                mm += "<option value=" + value[i].id + ">" + value[i].name + "</option>";
	            }
	            $("#province").html(mm);
	        }
	    });
	    $("#storeModal").modal("show");
	}

	//选择省后，显示对应市
	function changercity() {
	    var id = $('#province option:selected').val(); //选中的值	
	    if (id != 0) {
	        $.ajax({
	            type: "get",
	            url: "/index.php/Agent/Store/show_city/id/" + id + "",
	            async: false,
	            dataType: "json",
	            success: function(data) {
	                var value = data;
	                var mm = "<option value='0'>请选择</option>";
	                for (var i in value) {
	                    mm += "<option value=" + value[i].id + ">" + value[i].name + "</option>";
	                }
	                $("#city").html(mm);
	            }
	        });
	    } else {
	        $('#city').html("<option value='0'>请选择</option>");
	        $('#area').html("<option value='0'>请选择</option>");
	    }
	}

	//选择市后，显示对应区
	function changerarea() {
	    var id = $('#city option:selected').val(); //选中的值
	    if (id != 0) {
	        $.ajax({
	            type: "get",
	            url: "/index.php/Agent/Store/show_area/id/" + id + "",
	            async: false,
	            dataType: "json",
	            success: function(data) {
	                var value = data;
	                var mm = "<option value='0'>请选择</option>";
	                for (var i in value) {
	                    mm += "<option value=" + value[i].id + ">" + value[i].name + "</option>";
	                }
	                $("#area").html(mm);
	            }
	        });
	    } else {
	        $('#area').html("<option value='0'>请选择</option>");
	    }
	}

	//编辑店铺和新增店铺	
	function commit() {
	    var session_id = $("input[name='session_id']").val();
	    var restaurant_name = $("input[name='restaurant_name'").val();
	    var province_id = $("#province option:selected").val();
	    var city_id = $("#city option:selected").val();
	    var area_id = $("#area option:selected").val();
	    var address = $("input[name='address']").val();
	    var telephone1 = $("input[name='telephone1']").val();
	    var telephone2 = $("input[name='telephone2']").val();
	    var login_account = $("input[name='login_account']").val();
	    var password = $("input[name='password']").val();
	    var passwords = $("input[name='passwords']").val();
	    var p = parseInt($('.current').text());
	    if (isNaN(p)) {
	        p = 1;
	    } else {
	        p = parseInt($('.current').text());
	    }
	    var form_id = $("input[name='form_id']").val();
	    if (restaurant_name && province_id && city_id && area_id && address && telephone1 && login_account && password && passwords) {
	        if (password == passwords) {
	            if (form_id == 0) {
	                $.ajax({
	                    type: "post",
	                    url: "/index.php/Agent/Store/add_store",
	                    async: true,
	                    data: {
	                        "session_id": session_id,
	                        "restaurant_name": restaurant_name,
	                        "province_id": province_id,
	                        "city_id": city_id,
	                        "area_id": area_id,
	                        "address": address,
	                        "telephone1": telephone1,
	                        "telephone2": telephone2,
	                        "login_account": login_account,
	                        "password": password,
	                        "lat": lat,
	                        "lng": lng
	                    },
	                    dataType: "json",
	                    success: function(data) {
	                        if (data.code == 1) {
	                            layer.msg(data.msg);
	                            $("#storeModal").modal("hide");
	                            self.location.href = "/index.php/Agent/Store/store/page/" + data.page + ".html";
	                        }
	                    },
	                    error: function() {
	                        layer.msg("出错了或帐号已存在！");
	                    }
	                });
	            } else {
	                var restaurant_id = $("input[name='restaurant_id']").val();
	                $.ajax({
	                    type: "post",
	                    url: "/index.php/Agent/Store/edit_store",
	                    async: true,
	                    data: {
	                        "session_id": session_id,
	                        "restaurant_name": restaurant_name,
	                        "province_id": province_id,
	                        "city_id": city_id,
	                        "area_id": area_id,
	                        "address": address,
	                        "telephone1": telephone1,
	                        "telephone2": telephone2,
	                        "restaurant_id": restaurant_id,
	                        "login_account": login_account,
	                        "password": password,
	                        "lat": lat,
	                        "lng": lng
	                    },
	                    dataType: "json",
	                    success: function(data) {
	                        if (data.code == 1) {
	                            layer.msg(data.msg);
	                            $("#storeModal").modal("hide");
	                            self.location.href = "/index.php/Agent/Store/store/page/" + p + ".html";
	                        }
	                    },
	                    error: function() {
	                        layer.msg("出错了或帐号已存在！");
	                    }
	                });
	            }
	        } else {
	            layer.msg("密码不一致！");
	        }
	    } else {
	        layer.msg("所显示项不能为空！");
	    }
	}

	//店铺模态框消失时，重置表单input
	$('#storeModal').on('hidden.bs.modal', function() {
	    $("input[type='reset']").trigger('click');
	    $("#login_account").attr("disabled", false);
	});

	//删除店铺
	function delstore(i) {
	    var p = parseInt($('.current').text());
	    var msg = confirm("确定删除？");
	    if (msg == true) {
	        $.ajax({
	            type: "get",
	            url: "/index.php/Agent/Store/del_store/restaurant_id/" + i,
	            async: true,
	            dataType: "json",
	            success: function(data) {
	                if (data.code == 1) {
	                    layer.msg(data.msg);
	                    self.location.href = "/index.php/Agent/Store/store/page/" + data.page + ".html";
	                }
	            }
	        });
	    }
	}

	//编辑前的填充
	function modify_store(i) {
	    $("input[name='form_id']").attr("value", 1);
	    $.ajax({
	        type: "get",
	        url: "/index.php/Agent/Store/show_province", //编辑省下拉填充
	        async: false,
	        dataType: "json",
	        success: function(data) {
	            var value = data;
	            var mm = "<option value='0'>请选择</option>";
	            for (var i in value) {
	                mm += "<option value=" + value[i].id + ">" + value[i].name + "</option>";
	            }
	            $("#province").html(mm);
	            $("#login_account").attr("disabled", true);
	        }
	    });

	    $.ajax({
	        type: "get",
	        url: "/index.php/Agent/Store/modify_store/restaurant_id/" + i + "",
	        async: false,
	        dataType: "json",
	        success: function(data) {
	            $("input[name='restaurant_name']").val(data.restaurant_name);
	            $("input[name='address']").val(data.address);
	            $("input[name='telephone1']").val(data.telephone1);
	            $("input[name='telephone2']").val(data.telephone2);
	            $("input[name='restaurant_url']").val(data.restaurant_url);
	            $("input[name='restaurant_id']").val(data.restaurant_id);
	            $("input[name='login_account']").val(data.login_account);
	            $("input[name='password']").val(data.password);
	            $("input[name='passwords']").val(data.password);
	            $("#province").val(data.city1);
	            changercity();
	            $("#city").val(data.city2);
	            changerarea();
	            $("#area").val(data.city3);
	            $("#restaurant_manager").val(data.manager_id);
	            $("#lat").val(data.lat); //经纬度
	            $("#lng").val(data.lng);
	            var latlng = data.lat + ',' + data.lng;
	            $("#latLng").val(latlng);
	            searchAddress();
	        }
	    });
	}

	//修改代理的单多店模式dd
	var checked = $(".store_type:checked").attr('id');
	$('.store_type').on('click', function() {
	    var id = $(this).attr('id');
	    console.log(checked);
	    console.log(id);

	    if (id != checked) {
	        layer.msg('你要更换单多店铺模式吗?', {
	            time: 0,
	            btn: ['确定', '取消'],
	            yes: function(index) {
	                layer.close(index);
	                $.ajax({
	                    type: "get",
	                    url: "/index.php/Agent/Store/storeType1", //店铺模式的切换
	                    data: {
	                        type: id
	                    },
	                    async: false,
	                    dataType: "json",
	                    success: function(data) {
	                        if (data.code == 0) {
	                            layer.msg(data.msg);
	                            setTimeout(function() {
	                                parent.location.reload();
	                            }, 1000);
	                        } else {
	                            layer.msg(data.msg);
	                        }
	                    },
	                    error: function(msg) {
	                        alert('请求错误');
	                    }
	                })
	            }
	        })
	    }

	})