	// 基于准备好的dom，初始化echarts实例
	var monthChart = echarts.init(document.getElementById('month_canvas'));
	var yearChart = echarts.init(document.getElementById('year_canvas'));

	// 指定图表的配置项和数据
//----------------------------------------月图表-----------------------------------
	var monthOption = {
	    color: ['#29527c'],
	    title: {
	        text: '月销量统计报表'
	    },
	    tooltip: {
	        trigger: 'axis',
	        "axisPointer": {
	            "type": "shadow",
	            textStyle: {
	                color: "#fff"
	            }
	
	        },
	    },
	    toolbox: {
	        show: true,
	        feature: {
	            dataZoom: {
	                show:false
	            },
	            dataView: {readOnly: true},
	            magicType: {type: ['line', 'bar', 'stack', 'tiled']},
	            restore: {show:false},
	            saveAsImage: {}
	        },
	        right:20
    	},
	    xAxis : [
	        {
	            name:'日',
	            type : 'category',
	            boundaryGap: true,
	            data : [],//一个月的天数
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
	        /*{
	            name:'销量',
	            type:'bar',
	            data:[162.2, 134.9,114.9, 127.0, 123.2, 225.6, 76.7, 135.6, 171.0, 123.2, 125.6, 176.7,162.2, 114.9, 117.0, 23.2, 25.6, 176.7, 135.6, 162.2, 132.6, 20.0, 116.4, 131.3, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3]
	        }*/
	       
	       {
	            "name": "现金",
	            "type": "bar",
	            //"stack": "数据统计",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(255,0,0)",
	                    "label": {
	                       // "show": true,
	                        "textStyle": {
	                            "color": "#fff"
	                        },
	                        "position": "insideTop",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	            "data": [],
	        },
	        {
	            "name": "微信",
	            "type": "bar",
	            //"stack": "数据统计",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(0,255,0)",
	                    "label": {
	                        //"show": true,
	                        "textStyle": {
	                            "color": "#fff"
	                        },
	                        "position": "insideTop",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	            "data": [],
	        },
	        {
	            "name": "支付宝",
	            "type": "bar",
	            //"stack": "数据统计",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(0,0,255)",
	                    "label": {
	                        //"show": true,
	                        "textStyle": {
	                            "color": "#fff"
	                        },
	                        "position": "insideTop",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	            "data": [],
	        },


            {
                "name": "余额",
                "type": "bar",
                //"stack": "数据统计",
                "barMaxWidth": 35,
                "barGap": "10%",
                "itemStyle": {
                    "normal": {
                        "color": "rgb(222,125,44)",
                        "label": {
                            //"show": true,
                            "textStyle": {
                                "color": "#fff"
                            },
                            "position": "insideTop",
                            formatter: function(p) {
                                return p.value > 0 ? (p.value) : '';
                            }
                        }
                    }
                },
                "data": [],
            },


	        {
	            "name": "共",
	            "type": "bar",
	           // "stack": "数据统计",
	            symbolSize:10,
	            symbol:'circle',
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(0,0,0)",
	                    "barBorderRadius": 0,
	                    "opacity":0,
	                    "label": {
	                        "show": true,
	                        "position": "top",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	           data:[]
	       },
	    ]
	};

//-----------------------------------------年图表---------------------------------------------
	var yearOption = {
	    color: ['#29527c'],
	    title: {
	        text: '年销量统计报表'
	    },
	    tooltip: {
	        trigger: 'axis',
	        "axisPointer": {
	            "type": "shadow",
	            textStyle: {
	                color: "#fff"
	            }
	
	        },
	    },
	    toolbox: {
	        show: true,
	        feature: {
	            dataZoom: {
	                show:false
	            },
	            dataView: {readOnly: true},
	            magicType: {type: ['line', 'bar', 'stack', 'tiled']},
	            restore: {show:false},
	            saveAsImage: {}
	        },
	        right:20
    	},
	    xAxis : [
	        {
	            name:'月',
	            type : 'category',
	            boundaryGap: true,
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
	            formatter: '{value}元'
	        }
	    },
	    series: [ 
	       {
	            "name": "现金",
	            "type": "bar",
	            //"stack": "数据统计年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(255,0,0)",
	                    "label": {
	                       // "show": true,
	                        "textStyle": {
	                            "color": "#fff"
	                        },
	                        "position": "insideTop",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	            "data": [],
	        },
	        {
	            "name": "微信",
	            "type": "bar",
	           // "stack": "数据统计年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(0,255,0)",
	                    "label": {
	                        //"show": true,
	                        "textStyle": {
	                            "color": "#fff"
	                        },
	                        "position": "insideTop",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	            "data": [],
	        },
	        {
	            "name": "支付宝",
	            "type": "bar",
	           // "stack": "数据统计年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(0,0,255)",
	                    "label": {
	                      //  "show": true,
	                        "textStyle": {
	                            "color": "#fff"
	                        },
	                        "position": "insideTop",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	            "data": [],
	        },

            {
                "name": "余额",
                "type": "bar",
                // "stack": "数据统计年",
                "barMaxWidth": 35,
                "barGap": "10%",
                "itemStyle": {
                    "normal": {
                        "color": "rgb(222,125,44)",
                        "label": {
                            //  "show": true,
                            "textStyle": {
                                "color": "#fff"
                            },
                            "position": "insideTop",
                            formatter: function(p) {
                                return p.value > 0 ? (p.value) : '';
                            }
                        }
                    }
                },
                "data": [],
            },

	        {
	            "name": "共",
	            "type": "bar",
	           // "stack": "数据统计年",
	            symbolSize:10,
	            symbol:'circle',
	            "itemStyle": {
	                "normal": {
	                    "color": "rgb(0,0,0)",
	                    "barBorderRadius": 0,
	                    "opacity":0,
	                    "label": {
	                        "show": true,
	                        "position": "top",
	                        formatter: function(p) {
	                            return p.value > 0 ? (p.value) : '';
	                        }
	                    }
	                }
	            },
	           "data":[],
	       }
	    ]
	};

	//指定年的每月现金、微信、支付宝、总和数据显示
	var sales_for_year = $("#year_data").data("year_data");
	var cash_for_year = $("#year_data").data('year_cash');
	var alipay_for_year = $("#year_data").data('year_alipay');
	var wechat_for_year = $("#year_data").data('year_wechat');
	var remainder_for_year = $("#year_data").data('year_remainder');     // 新增一个余额

	yearOption.series[0]['data'] = cash_for_year;
	yearOption.series[1]['data'] = wechat_for_year;
	yearOption.series[2]['data'] = alipay_for_year;
	yearOption.series[3]['data'] = remainder_for_year;
	yearOption.series[4]['data'] = sales_for_year;

	//指定月的每日现金、微信、支付宝、总和数据显示
	var sales_for_month = $("#month_data").data("month_data");
	var cash_for_month = $("#month_data").data("month_cash");
	var alipay_for_month = $("#month_data").data("month_alipay");
	var wechat_for_month = $("#month_data").data("month_wechat");
	var remainder_for_month = $("#month_data").data("month_remainder");   // 新增余额
	monthOption.series[0]['data'] = cash_for_month;
	monthOption.series[1]['data'] = wechat_for_month;
	monthOption.series[2]['data'] = alipay_for_month;
	monthOption.series[3]['data'] = remainder_for_month;
	monthOption.series[4]['data'] = sales_for_month;

	//指定年、月下的月销量统计报表的x轴日期数组
	var select_year = $("#year").val();
	var select_month = $("#month").val();
	var day_counts = getDaysInMonth(select_year,select_month);
	monthOption.xAxis[0]['data'] = day_counts;

	// 使用刚指定的配置项和数据显示图表。
	monthChart.setOption(monthOption);
	yearChart.setOption(yearOption);

	//月变化、显示对应年，对应月的图表
	function monthData(){
	    var month = $("#month").val();
	    console.log(month);
	    var year = $("#year").val();
	    var restaurant_id = $("#restaurant_id").val();
	    $.ajax({
	        url:'/index.php/admin/sale/ajax_sales_for_month',
	        data:{"month":month,"year":year,"restaurant_id":restaurant_id},
	        type:'post',
	        dataType:"json",
	        success:function(msg){
	            $("#salesInfo").html(msg['salesInfo']);
	            monthOption.series[0]['data'] = msg['cash_for_month'];
	            monthOption.series[1]['data'] = msg['wechat_for_month'];
	            monthOption.series[2]['data'] = msg['alipay_for_month'];
	            monthOption.series[3]['data'] = msg['remainder_for_month'];    // 新增一个余额
	            monthOption.series[4]['data'] = msg['sales_for_month'];
	            var day_counts = getDaysInMonth(year,month);
				monthOption.xAxis[0]['data'] = day_counts;    
	            monthChart.setOption(monthOption);
	        }
	    });
	}

	//年变化、显示对应年、对应月的图表
	function yearData(){
	    var year = $("#year").val();
	    var month = $("#month").val();
	    var restaurant_id = $("#restaurant_id").val();
	    $.ajax({
	        url:'/index.php/admin/sale/ajax_sales_for_year',
	        data:{"month":month,"year":year,"restaurant_id":restaurant_id},
	        type:'post',
	        dataType:"json",
	        success:function(msg){
	           	yearOption.series[0]['data'] = msg['cash_for_year'];
				yearOption.series[1]['data'] = msg['wechat_for_year'];
				yearOption.series[2]['data'] = msg['alipay_for_year'];
				yearOption.series[3]['data'] = msg['remainder_for_year'];
				yearOption.series[4]['data'] = msg['sales_for_year'];
	            yearChart.setOption(yearOption);
	        }
	    });
	    monthData();
	}

	/**
	 * 
	 * @param {Object} year  指定年
	 * @param {Object} month 指定月
	 * return 日期数组   即【1,2,3...28】
	 */
	function getDaysInMonth(year,month){
	    month = parseInt(month,10);
	    var temp = new Date(year,month,0); 		//return temp.getDate();
	    var day_count = temp.getDate();
	    var day_array = new Array();
		for(var i=1;i<=day_count;i++){
			day_array[i-1] = i;
		}
		return day_array;
	}


