	function canvasSize(){
		$('.chart_data').width($(window).width());
		$('.chart_data').height($('.chart_data').width()*0.6);
	}
	canvasSize();

	//明细列表，指定年，指定月，显示指定日
	function monthChange(){
		var getCheckedYear = $("#checkedYear").val();
		var getCheckedMonth = $("#checkedMonth").val();
		if(getCheckedYear && getCheckedMonth){
			$.ajax({
				type:"get",
				url:"/index.php/Boss/Detail/getSearchDayNumByAjax/searchYear/"+getCheckedYear+"/searchMonth/"+getCheckedMonth,
				dataType:"json",
				async:false,
				success:function(data){
					//console.log(data);
					var str = "";
					for(var i=0;i<data.length;i++){
						str += "<option value = "+data[i]+">"+data[i]+"</option>";
					}
					$("#checkDay").html(str);
				}
			});
		}
	}

	
	//--------------------------------------指定条件饼状图配置开始----------------------------------------------
	
	// 基于准备好的dom，初始化echarts实例
	var dayTurnoverChart = echarts.init(document.getElementById('dayTurnover'));
	
	//当天现金、支付宝、微信营业额
	var CashToday = $("#dayTurnover").data("cashtoday");
	var CashToday = CashToday ? CashToday : 0;

	var AlipayToday = $("#dayTurnover").data("alipaytoday");
	var AlipayToday = AlipayToday ? AlipayToday : 0;

	var WechatToday = $("#dayTurnover").data("wechattoday");
	var WechatToday = WechatToday ? WechatToday : 0;

    // 余额
    var RemainderToday = $("#dayTurnover").data("remaindertoday");
    var RemainderToday = RemainderToday ? RemainderToday : 0;
    
	
	//今日、当月的饼状图属性配置	
	daySearchOption = { 
	    title : {
	        text: '日报表',
	        textStyle:{
	        	fontWeight:'normal',
	            color:'#006bf0',
	            fontSize:16
	        },
	        top:10
	    },
	    legend: {
	        orient: 'vertical',
	        top:'30%',
	        right: '5',
	        data: ['微信','支付宝','现金','余额']
	    },
	    series : [
	        {
	            name: '日报表',
	            type: 'pie',
	            radius : '50%',
	            center: ['35%', '50%'],
	            data:[
	                {
	                	value:WechatToday, 
	                	name:'微信',
	                	itemStyle:{
	                	    normal:{
	                	    	color: new echarts.graphic.LinearGradient(0,0,1, 0,[{
                	    			offset: 0, color: '#6be24a' // 0% 处的颜色
                	    		}, {
                	  				offset: 1, color: '#0a9e0a' // 100% 处的颜色
                				}], false)
	                	    }	
	                	},
        	            label: {
        		            normal: {
        		                textStyle: {
        		                    color: '#0a9e0a'
        		                }
        		            }
        	       		}
	                },
	                {
	                	value:AlipayToday, 
	                	name:'支付宝',
	                	itemStyle:{
	                	    normal:{
	                	    	color: '#0370ff'
	                	    }	
	                	},
	                	label: {
        		            normal: {
        		                textStyle: {
        		                    color: '#0370ff'
        		                }
        		            }
        	       		}
	                },
	                {
	                	value:CashToday, 
	                	name:'现金',
	                	itemStyle:{
	                	    normal:{
	                	    	color: new echarts.graphic.LinearGradient(0,0,1, 0,[{
                	    			offset: 0, color: '#e94745' // 0% 处的颜色
                	    		}, {
                	  				offset: 1, color: '#b50000' // 100% 处的颜色
                				}], false)
	                	    }	
	                	},
	                	label: {
        		            normal: {
        		                textStyle: {
        		                    color: '#b50000'
        		                }
        		            }
        	       		}
	                },
                    {
                        value:RemainderToday,
                        name:'余额',
                        itemStyle:{
                            normal:{
                                color: new echarts.graphic.LinearGradient(0,0,1, 0,[{
                                    offset: 0, color: '#9933ff' // 0% 处的颜色
                                }, {
                                    offset: 1, color: '#9933ff' // 100% 处的颜色
                                }], false)
                            }
                        },
                        label: {
                            normal: {
                                textStyle: {
                                    color: '#9933ff'
                                }
                            }
                        }
                    },
	            ],
	            stillShowZeroSum :true,
	            label: {
		            normal: {
		                position: 'outside',
		                formatter: '\n{d}%\n{c}元'
		            }
	       		},
	            itemStyle: {
	                emphasis: {
	                    shadowBlur: 10,
	                    shadowOffsetX: 0,
	                    shadowColor: 'rgba(0, 0, 0, 0.5)'
	                }
	            }
	        }
	    ]
	};

	// 使用刚指定的配置项和数据显示图表。
    dayTurnoverChart.setOption(daySearchOption);
    
	//----------------------------------------指定条件饼状图配置结束--------------------------------------------
	
	//----------------------------------------指定条件月报表柱状图开始------------------------------------------
	var monthTurnoverChart = echarts.init(document.getElementById('monthTurnover'));

	var monthOption = {
	    color: ['#29527c'],
	    title: {
	        text: '月报表',
	        textStyle:{
	        	fontWeight:'normal',
	            color:'#006bf0',
	            fontSize:16
	        },
	        top:10
	    },
	    grid:{
	    	left:'15'
	    },
	    xAxis : [
	        {
	        	axisTick:false,
	            name:'日',
	            type : 'category',
	            boundaryGap: true,
	            data : [],
	            axisLine:{show:false},
	            axisTick:{show:false},
	        }
	    ],
	    yAxis: {
	    	show : false,
	        name:'营业额',
	        type: 'value',
	        axisLabel: {
	            formatter: '{value}元'
	        }
	    },
	    series: [ 
	       {
	            "name": "现金",
	            "type": "bar",
	            "stack": "年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "#b40406",
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
	            "name": "支付宝",
	            "type": "bar",
	            "stack": "年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "#006bff",
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
	            "name": "微信",
	            "type": "bar",
	            "stack": "年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "#0a9e0a",
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
                "stack": "年",
                "barMaxWidth": 35,
                "barGap": "10%",
                "itemStyle": {
                    "normal": {
                        "color": "#9933ff",
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
            }
        ]
	};
	//得到指定年、月,天数数组
	var getCheckedYear = $("#checkedYear").val();
	var getCheckedMonth = $("#checkedMonth").val();
	var datArr = getDaysInMonth(getCheckedYear,getCheckedMonth);
	monthOption.xAxis[0]['data'] = datArr;
	
	
	$monthCash = $("#monthTurnover").data("monthcash");
	$monthAlipay = $("#monthTurnover").data("monthalipay");
	$monthWeChat = $("#monthTurnover").data("monthwechat");
	$monthRemainder = $("#monthTurnover").data("monthremainder");
	monthOption.series[0]['data'] = $monthCash;
	monthOption.series[1]['data'] = $monthAlipay;
	monthOption.series[2]['data'] = $monthWeChat;
	monthOption.series[3]['data'] = $monthRemainder;

	// 使用刚指定的配置项和数据显示图表。
    monthTurnoverChart.setOption(monthOption);
	
	
	
	
	//----------------------------------------指定条件年报表柱状图结束------------------------------------------
	

	//----------------------------------------指定条件年报表柱状图开始------------------------------------------
	var yearTurnoverChart = echarts.init(document.getElementById('yearTurnover'));

	var yearOption = {
	    color: ['#29527c'],
	    title: {
	        text: '年报表',
	        textStyle:{
	        	fontWeight:'normal',
	            color:'#006bf0',
	            fontSize:16
	        },
	        top:10
	    },
	    xAxis : [
	        {
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
	    	show : false,
	        name:'营业额',
	        type: 'value',
	        axisLabel: {
	            formatter: '{value}元'
	        }
	    },
	    series: [ 
	       {
	            "name": "现金",
	            "type": "bar",
	            "stack": "年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "#b40406",
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
	            "name": "支付宝",
	            "type": "bar",
	            "stack": "年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "#006bff",
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
	            "name": "微信",
	            "type": "bar",
	            "stack": "年",
	            "barMaxWidth": 35,
	            "barGap": "10%",
	            "itemStyle": {
	                "normal": {
	                    "color": "#0a9e0a",
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
                "stack": "年",
                "barMaxWidth": 35,
                "barGap": "10%",
                "itemStyle": {
                    "normal": {
                        "color": "#9933ff",
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
            }
        ]
	};
	
	$yearCash = $("#yearTurnover").data("yearcash");
	$yearAlipay = $("#yearTurnover").data("yearalipay");
	$yearWeChat = $("#yearTurnover").data("yearwechat");
	$yearRemainder = $("#yearTurnover").data("yearremainder");
	yearOption.series[0]['data'] = $yearCash;
	yearOption.series[1]['data'] = $yearAlipay;
	yearOption.series[2]['data'] = $yearWeChat;
	yearOption.series[3]['data'] = $yearRemainder;

	// 使用刚指定的配置项和数据显示图表。
    yearTurnoverChart.setOption(yearOption);
    
   //----------------------------------------指定条件年报表柱状图结果束------------------------------------------ 
   
   
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
    

    $(window).resize(function() {
    	canvasSize();
		dayTurnoverChart.resize();
		monthTurnoverChart.resize();
		yearTurnoverChart.resize();
	});

