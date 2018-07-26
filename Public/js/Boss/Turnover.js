	function canvasSize(){
		$('.chart_data').width($(window).width());
		$('.chart_data').height($('.chart_data').width()*0.6);
	}
	canvasSize();

	// 基于准备好的dom，初始化echarts实例
	var dayTurnoverChart = echarts.init(document.getElementById('dayTurnover'));
	var monthTurnoverChart = echarts.init(document.getElementById('monthTurnover'));
	
	//当天现金、支付宝、微信、余额支付营业额
	var CashToday = $("#dayTurnover").data("cashtoday");
	var CashToday = CashToday ? CashToday : 0;

	var AlipayToday = $("#dayTurnover").data("alipaytoday");
	var AlipayToday = AlipayToday ? AlipayToday : 0;

	var WechatToday = $("#dayTurnover").data("wechattoday");
	var WechatToday = WechatToday ? WechatToday : 0;

    var RemainderToday = $("#dayTurnover").data("remaindertoday");  // 新增余额
    var RemainderToday = RemainderToday ? RemainderToday : 0;

    var FourthToday = $("#dayTurnover").data("fourthtoday");  // 新增第四方支付
    var FourthToday = FourthToday ? FourthToday : 0;
	
	//本月现金、支付宝、微信、余额支付营业额
	var CashMonth = $("#monthTurnover").data("cashmonth");
	var CashMonth = CashMonth ? CashMonth : 0;

	var AlipayMonth = $("#monthTurnover").data("alipaymonth");
	var AlipayMonth = AlipayMonth ? AlipayMonth : 0;

	var WechatMonth = $("#monthTurnover").data("wechatmonth");
	var WechatMonth = WechatMonth ? WechatMonth : 0;

    var RemainderMonth = $("#monthTurnover").data("remaindermonth");    // 新增余额
    var RemainderMonth = RemainderMonth ? RemainderMonth : 0;

    var FourthMonth = $("#monthTurnover").data("fourthmonth");    // 新增余额
    var FourthMonth = FourthMonth ? FourthMonth : 0;
	
	//今日、当月的饼状图属性配置	
	whenDayOption = { 
	    title : {
	        text: '今日营业额',
	        textStyle:{
	        	fontWeight:'normal',
	            color:'#006bf0',
	            fontSize:16
	        },
	        top:10
	    },
	   /* tooltip : {
	        trigger: 'item',
	        formatter: "{a} {b} : {c}份 ({d}%)",
	         textStyle: {
	                    fontWeight: 'bold',
	                    fontSize:18
	                }
	    },*/
	    legend: {
	        orient: 'vertical',
	        top:'30%',
	        right: '5',
	        data: ['微信','支付宝','现金','余额','银行代收']         // 新增余额、银行代收
	    },
	    /*toolbox: {
	        show: true,
	        orient: 'vertical',
	        left: 'right',
	        top: 'center',
	        feature: {
	            dataView: {readOnly: false},
	            restore: {},
	            saveAsImage: {}
	        }
	    },*/
	    series : [
	        {
	            name: '今日营业额',
	            type: 'pie',
	            radius : '60%',
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
                    {
                        value:FourthToday,
                        name:'银行代收',
                        itemStyle:{
                            normal:{
                                color: new echarts.graphic.LinearGradient(0,0,1, 0,[{
                                                                                        offset: 0, color: '#FF0090' // 0% 处的颜色
                                                                                    }, {
                                                                                        offset: 1, color: '#FF0090' // 100% 处的颜色
                                                                                    }], false)
                            }
                        },
                        label: {
                            normal: {
                                textStyle: {
                                    color: '#FF0090'
                                }
                            }
                        }
                    },
	            ],
	            label: {
		            normal: {
		                formatter: '\n{d}%\n{c}元',
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
	
	
	whenMonthOption = { 
	    title : {
	        text: '本月营业额',
	        textStyle:{
	        	fontWeight:'normal',
	            color:'#006bf0',
	            fontSize:16
	        },
	        top:10
	    },
	    /*tooltip : {
	        trigger: 'item',
	        formatter: "{a} {b} : {c}份 ({d}%)",
	         textStyle: {
	                    fontWeight: 'bold',
	                    fontSize:18
	                }
	    },*/
	    legend: {
	        orient: 'vertical',
	        top:'30%',
	        right: '5',
	        data: ['微信','支付宝','现金','余额','银行代收']        // 新增余额、银行代收
	    },
	    /*toolbox: {
	        show: true,
	        orient: 'vertical',
	        left: 'right',
	        top: 'center',
	        feature: {
	            dataView: {readOnly: false},
	            restore: {},
	            saveAsImage: {}
	        }
	    },*/
	    series : [
	        {
	            name: '本月营业额',
	            type: 'pie',
	            //roseType:'area',
	            radius : '60%',
	            center: ['35%', '50%'],
	            data:[
	                {
	                	value:WechatMonth, 
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
	                	value:AlipayMonth, 
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
	                	value:CashMonth, 
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
                        value:RemainderMonth,
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
                    {
                        value:FourthMonth,
                        name:'银行代收',
                        itemStyle:{
                            normal:{
                                color: new echarts.graphic.LinearGradient(0,0,1, 0,[{
                                                                                        offset: 0, color: '#FF0090' // 0% 处的颜色
                                                                                    }, {
                                                                                        offset: 1, color: '#FF0090' // 100% 处的颜色
                                                                                    }], false)
                            }
                        },
                        label: {
                            normal: {
                                textStyle: {
                                    color: '#FF0090'
                                }
                            }
                        }
                    },
	            ],
	            label: {
		            normal: {
		                position: 'outside',
		                formatter: '\n{d}%\n{c}元',
		                textStyle: {
		                    color: '',
		                    fontWeight: 'bold',
		                    fontSize: 12
		                }
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
    dayTurnoverChart.setOption(whenDayOption);
    monthTurnoverChart.setOption(whenMonthOption);

    

    $(window).resize(function() {
    	canvasSize();
		dayTurnoverChart.resize();
		monthTurnoverChart.resize();
	});
	
	//退出登录
	function loginout(){
		layer.msg('您确定要退出？', {
		   time: 0, //不自动关闭
		   btn: ['确定', '取消'],
		   yes: function(index){
		 	 location.href = "/index.php/Boss/Common/loginout";
		   }
		});
	}
