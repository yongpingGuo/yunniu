    var geocoder, citylocation, map, marker = null;
    var init = function() {
        var center = new qq.maps.LatLng(23.116717, 113.388027);
        map = new qq.maps.Map(document.getElementById('container'), {
            center: center,
            zoom: 13
        });
        marker = new qq.maps.Marker({
            position: center,
            draggable: true,
            map: map

        });
        //调用地址解析类
        geocoder = new qq.maps.Geocoder({
            complete: function(result) {
                map.setCenter(result.detail.location);
                marker.setPosition(result.detail.location)
                lat = result.detail.location.lat;
                lng = result.detail.location.lng;
                console.log(lat);
                console.log(lng);
            }
        });

        //获取城市列表接口设置中心点
        citylocation = new qq.maps.CityService({
            complete: function(result) {
                map.setCenter(result.detail.latLng);
                marker.setPosition(result.detail.latLng);
            }
        });
        //调用searchLocalCity();方法  根据用户IP查询城市信息。
        citylocation.searchLocalCity();
        //设置Marker停止拖动事件
        // qq.maps.event.addListener(marker, 'dragend', function() {
        //     var latLng=marker.getPosition();
        //     geocoder.getAddress(latLng);
        //     console.log(geocoder.getAddress(latLng))
        // });
    }

    function searchAddress() {
        var address = $('#province option:selected').html() + $('#city option:selected').html() +
            $('#area option:selected').html() + document.getElementById("address").value;
        //通过getLocation();方法获取位置信息值
        geocoder.getLocation(address);
        //设置服务请求成功的回调函数
                geocoder.setComplete(function(result) {
                    map.setCenter(result.detail.location);
                    var marker = new qq.maps.Marker({
                        map: map,
                        position: result.detail.location
                    });
                    //点击Marker会弹出反查结果
                    qq.maps.event.addListener(marker, 'click', function() {
                        alert("坐标地址为： " + result.detail.location);
                    });
                });
                //若服务请求失败，则运行以下函数
                geocoder.setError(function() {
                    alert("出错了，请输入正确的地址！！！");
                });
    }

    //格局经纬度定位
    function codeLatLng(lat,lng) {
        //获取经纬度数值
        var latLng = new qq.maps.LatLng(lat, lng);

        //调用信息窗口
        var info = new qq.maps.InfoWindow({map: map});
        //调用获取位置方法
        geocoder.getAddress(latLng);
    }