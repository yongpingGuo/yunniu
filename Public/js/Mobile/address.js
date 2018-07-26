$(document).ready(function() {
    // 配置参数
    wx.config({
        debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
        appId: appId, // 必填，公众号的唯一标识
        timestamp: timestamp, // 必填，生成签名的时间戳
        nonceStr: nonceStr, // 必填，生成签名的随机串
        signature: signature, // 必填，签名，见附录1
        jsApiList: [
            'getLocation'
        ] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
    });
    console.log('load,'+appId);
    //ready
    wx.ready(function() {
        wx.getLocation({
            type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
            success: function(res) {
                var latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                var longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。
                console.log(latitude,longitude);
                if (latitude && longitude) {

                    //请求城市列表
                    $.ajax({
                        type: 'get',
                        url: "/index.php/Mobile/AgentWeixin/city_list",
                        data: {
                            lat: latitude,
                            lng: longitude,
                            type:0,//表示成功获取到经纬度
                        },
                        //正常
                        success: function(data) {
                            console.log(data);
                            for(var i in data){
                                console.log(data[i]['name']);
                                var city = '<option' + ' ' + 'id='+ data[i]['id'] + '>'+data[i]['name']+'</option>';
                                $("#city_list").append(city);
                            }
                        },
                        //错误
                        error: function(err) {
                            console.log(err)
                            // alert(err.errMsg);
                        }
                    })

                    //请求店铺列表
                    $.ajax({
                        type: 'get',
                        url: "/index.php/Mobile/AgentWeixin/distanceAjax",
                        data: {
                            lat: latitude,
                            lng: longitude,
                            type:0,//表示成功获取到经纬度
                        },
                        //正常
                        success: function(data) {
                            console.log('获取了当前的地理位置')
                            for (var i in data) {
                                var append_html = html_data(data[i]);
                                $("#show").append($(append_html));
                            }
                        },
                        //错误
                        error: function(err) {
                            console.log(err)
                            // alert(err.errMsg);
                        }
                    })
                } else {
                    alert('获取当前的地理位置失败');
                }
            },
            fail:function(err){
                //alert('获取地理位置失败！');
                $.ajax({
                    type: 'get',
                    url: "/index.php/Mobile/AgentWeixin/distanceAjax",
                    data: {
                        lat: latitude,
                        lng: longitude,
                        type:1,//表示不能获取到经纬度
                    },
                    //正常
                    success: function(data) {
                        console.log('获取了当前的地理位置')
                        for (var i in data) {
                            var append_html = html_data(data[i]);
                            $("#show").append($(append_html));
                        }
                    },
                    //错误
                    error: function(err) {
                        console.log(err)
                        // alert(err.errMsg);
                    }
                })

                console.log(err)
            },

            //点击取消时
            cancel:function(res_cancel){
                $.ajax({
                    type: 'get',
                    url: "/index.php/Mobile/AgentWeixin/distanceAjax",
                    data: {
                        lat: latitude,
                        lng: longitude,
                        type:2,//用户自己取消了
                    },
                    //正常
                    success: function(data) {
                        console.log('获取了当前的地理位置')
                        for (var i in data) {
                            var append_html = html_data(data[i]);
                            $("#show").append($(append_html));
                        }
                    },
                    //错误
                    error: function(err) {
                        console.log(err)
                        // alert(err.errMsg);
                    }
                })
            }
        });
    });
    // error
    wx.error(function(res) {
        console.log(res.errMsg);
        alert(res.errMsg)
        // config信息验证失败会执行error函数，如签名过期导致验证失败，具体错误信息可以打开config的debug模式查看，也可以在返回的res参数中查看，对于SPA可以在这里更新签名。
        // alert('请用手机微信打开');
    });

});

// if (navigator.geolocation) {
//     navigator.geolocation.getCurrentPosition(function(position) {
//         var longitude = position.coords.longitude;
//         var latitude = position.coords.latitude;
//         if (latitude && longitude) {
//             $.ajax({
//                 type: 'get',
//                 url: "/index.php/Mobile/AgentWeixin/distanceAjax",
//                 data: {
//                     lat: latitude,
//                     lng: longitude,
//                 },
//                 //正常
//                 success: function(data) {
//                     console.log(data);
//                     for (var i in data) {
//                         var append_html = html_data(data[i]);
//                         $("#show").append($(append_html));
//                     }
//                 },
//                 //错误
//                 error: function() {
//                     alert('请求错误');
//                 }
//             })
//         } else {
//             alert('获取当前的地理位置失败');
//         }
//     }, function(error) {
//         switch (error.code) {
//             case 1:
//                 alert("位置服务被拒绝");
//                 break;

//             case 2:
//                 alert("暂时获取不到位置信息");
//                 break;

//             case 3:
//                 alert("获取信息超时");
//                 break;

//             case 4:
//                 alert("未知错误");
//                 break;
//         }
//     }, {
//         timeoenableHighAcuracy: false,
//         timeout: 5000,
//         maximumAge: 5
//     });
// } else {
//     alert("浏览器不支持获取地理位置信息!");
// }


//生成html文档
function html_data(data) {
    var d = data;
    var url =  host +"/index.php/mobile/index/index/restaurant_id/" + d['restaurant_id'];
    var str =
        '<div class="address-item for_sear" >' +

        '<div class="flex-content vertical-flex">' +
        '<i class="iconfont icon-address address-item-icon">' + '</i>' +
        '<div class="flex-main">' +
        '<div class="address-name">' + d['restaurant_name'] + '</div>' +
        '<div class="address-detail">' + d['city_detail'] + '</div>' +
        '</div>' +

        '<div>' +
        //'<span class="address-item-num">' + d['distance'] + '</span>' +
        //'<span class="text-grey">' + '公里' + '</span>' +
        d['distance']+
        '</div>' +
        '</div>' +


        '<div class="address-time">' +

        //'<div class="address-time-info">' +
        //'<div>' + '取餐时间:' + '</div>' +
        //'<div class="flex-cintent">' +
        //'<i class="iconfont icon-radio text-active">' + '</i>' +
        //'<span>' + '现在，支付成功后直接取餐' + '</span>' +
        //'</div>' +
        //
        //'<div>' +
        //'<i class="iconfont icon-radio text-grey">' + '</i>' +
        //'<span>' + '稍晚，预约当天 稍晚时间 签到后取餐' + '</span>' +
        //'</div>' +
        //'</div>' +

        '<button class="danger-btn" onclick="location=\'' + url + '\'">' + '进入菜单' + '</button>'+
        //'<div class="small">' +
        //'<span class="text-active">' + '提示：' + '</span>' +
        //'<span class="text-grey">' + '根据当天实际完成的支付时间，您指定的取餐时间可能会顺延' + '</span>' +
        //'</div>' +

        '</div>' +
        '</div>';
    return str;
}

$("body").delegate(".address-item", "click", function() {
    $(this).addClass('active').siblings().removeClass('active');
});