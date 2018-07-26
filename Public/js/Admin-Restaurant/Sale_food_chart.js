	//跟据时间条件查询菜品销量
	function search(obj){
		var commit_type = $(obj).data('commit_type');
		console.log(commit_type);
		if(commit_type == 0){
			$("#myform").attr("action","/index.php/admin/Sale/food_chart/commit_type/0");
			$("#myform").submit();
		}else{
			$("#myform").attr("action","/index.php/admin/Sale/exportExcal_num");
			$("#myform").submit();
		}
	}



