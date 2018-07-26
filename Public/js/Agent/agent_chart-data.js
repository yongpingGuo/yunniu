// 基于准备好的dom，初始化echarts实例
var monthChart = echarts.init(document.getElementById('month_canvas'));
var yearChart = echarts.init(document.getElementById('year_canvas'));


// 指定图表的配置项和数据
var monthOption = {
    color: ['#29527c'],
    title: {
        text: '月销量统计报表'
    },
    tooltip: {
        trigger: 'axis'
    },
    toolbox: {
        show: true,
        feature: {
            dataZoom: {
                show:false
            },
            dataView: {readOnly: true},
            magicType: {type: ['line', 'bar']},
            restore: {show:false},
            saveAsImage: {}
        },
        right:20
    },
    xAxis : [
        {
            name:'日',
            type : 'category',
            boundaryGap: false,
            data : ['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30'],//一个月的天数
            axisLine:{show:false},
            axisTick:{show:false},
            axisLabel:{
                textStyle: {
                    color: '#000'
                }
            }
        }
    ],
    yAxis: {
        name:'销量',
        type: 'value',
        axisLabel: {
            formatter: '{value}元'
        }
    },
    series: [
        {
            name:'销量',
            type:'line',
            data:[162.2, 134.9,114.9, 127.0, 123.2, 225.6, 76.7, 135.6, 171.0, 123.2, 125.6, 176.7,162.2, 114.9, 117.0, 23.2, 25.6, 176.7, 135.6, 162.2, 132.6, 20.0, 116.4, 131.3, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
        }
    ]
};
var yearOption = {
    color: ['#29527c'],
    title: {
        text: '年销量统计报表'
    },
    tooltip: {
        trigger: 'axis'
    },
    toolbox: {
        show: true,
        feature: {
            dataZoom: {
                show:false
            },
            dataView: {readOnly: true},
            magicType: {type: ['line', 'bar']},
            restore: {show:false},
            saveAsImage: {}
        },
        right:20
    },
    xAxis : [
        {
            name:'月',
            type : 'category',
            boundaryGap: false,
            data : ['1','2','3','4','5','6','7','8','9','10','11','12'],
            axisLine:{show:false},
            axisTick:{show:false},
            axisLabel:{
                textStyle: {
                    color: '#000'
                }
            }
        }
    ],
    yAxis: {
        name:'销量',
        type: 'value',
        axisLabel: {
            formatter: '{value}万元'
        }
    },
    series: [
        {
            name:'销量',
            type:'line',
            data:[162.2, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
        }
    ]
};


var sales_for_year = $("#year_data").data("year_data");
var sales_for_month = $("#month_data").data("month_data");
console.log(sales_for_year);
console.log(sales_for_month);
monthOption.series[0]['data'] = sales_for_month;
yearOption.series[0]['data'] = sales_for_year;
// 使用刚指定的配置项和数据显示图表。
monthChart.setOption(monthOption);
yearChart.setOption(yearOption);

function monthData(){//改变月,统针报表的变化
    var month = $("#month").val();
    var year = $("#year").val();
    $.ajax({
        url:'/index.php/agent/sale/ajax_sales_for_month',
        data:{"month":month,"year":year},
        type:'post',
        dataType:"json",
        success:function(msg){
            $("#salesInfo").html(msg['salesInfo']);
            monthOption.series[0]['data'] = msg['sales_for_month'];
            monthChart.setOption(monthOption);
        }
    });
}

function yearData(){//改变年,统针报表的变化
    var year = $("#year").val();
    var month = $("#month").val();
    $.ajax({
        url:'/index.php/agent/sale/ajax_sales_for_year',
        data:{"month":month,"year":year},
        type:'post',
        dataType:"json",
        success:function(msg){
            yearOption.series[0]['data'] = msg['sales_for_year'];
            yearChart.setOption(yearOption);
        }
    });
    monthData();
}
