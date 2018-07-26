$(window).ready(function(){
	/* ---------------------------------------------- /*
	 * 左侧折叠菜单
	/* ---------------------------------------------- */
	$('.treeview-header').click(function(){
		//为当前选中选项添加选中样式
		$('.sidebar-menu').find('.active').removeClass('active');
		$(this).addClass('active');
		$(this).siblings().slideToggle();
		$(this).parent().siblings().find('.treeview-menu').slideUp();		
	});

	$('.treeview-menu li a').click(function(){		
		$('.treeview-menu').find('a').removeClass('active');
		$(this).addClass('active');
	});

	$("#select_date").datetimepicker({
	    format:'yyyy-mm-dd',
	    todayBtn: true,
	    autoclose: true,
	    todayHighlight: true,
	    minView: "month", //选择日期后，不会再跳转去选择时分秒 
	    language:  'zh-CN'
	});
});
