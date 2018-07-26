function uIsNull(){    
    var str = document.getElementById('food_name').value.trim();    
    if(str.length==0){    
		document.getElementById("fn").innerHTML = "不能为空!";    
    }else{
		 document.getElementById("fn").innerHTML = ""; 
		}    
}    

	function uIsNull1(){    
	    var str = document.getElementById('food_price').value.trim(); 
	    var reg=/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/; 	
	    if(str.length==0){    
			document.getElementById("fp").innerHTML = "不能为空!";    
	    }else if(!reg.test(str)){
		    document.getElementById("fp").innerHTML = "输入错误";    
    	}else{
			 document.getElementById("fp").innerHTML = ""; 
		}    
} 

	function uIsNull2(){    
	    var str = document.getElementById('discount').value.trim(); 
	    var reg=/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/; 
	    if(str.length==0){
	    	document.getElementById("di").innerHTML = "";    
	    }else if(!reg.test(str)){
		    document.getElementById("di").innerHTML = "输入错误";    
    	}else{
			 document.getElementById("di").innerHTML = ""; 
		}    
}    

	function uIsNull3(){    
	    var str = document.getElementById('foods_num_day').value.trim(); 
	    var reg=/^[1-9]\d*$/; 
	    if(str.length==0){
	    	document.getElementById("fnd").innerHTML = "不能为空";    
	    }else if(!reg.test(str)){
		    document.getElementById("fnd").innerHTML = "输入错误";    
    	}else{
			 document.getElementById("fnd").innerHTML = ""; 
		}    
}    

	function uIsNull4(){    
	    var str = document.getElementById('prom_price').value.trim(); 
	    var reg=/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/; 	
	    if(str.length==0){    
			document.getElementById("pp").innerHTML = "";    
	    }else if(!reg.test(str)){
		    document.getElementById("pp").innerHTML = "输入错误";    
    	}else{
			 document.getElementById("pp").innerHTML = ""; 
		}    
} 
	
	function uIsNull5(){    
	    var str = document.getElementById('discount1').value.trim(); 
	    var reg=/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/; 
	    if(str.length==0){
	    	document.getElementById("dis").innerHTML = "";    
	    }else if(!reg.test(str)){
		    document.getElementById("dis").innerHTML = "输入错误";    
    	}else{
			 document.getElementById("dis").innerHTML = ""; 
		}    
}    

	function uIsNull6(){    
	    var str = document.getElementById('prom_goods_num').value.trim(); 
	    var reg=/^[1-9]\d*$/; 
	    if(str.length==0){
	    	document.getElementById("pgn").innerHTML = "不能为空";    
	    }else if(!reg.test(str)){
		    document.getElementById("pgn").innerHTML = "输入错误";    
    	}else{
			 document.getElementById("pgn").innerHTML = ""; 
		}    
}    