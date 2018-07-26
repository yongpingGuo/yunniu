/**
 * edit by lamson 2016-10-28
 */
var Validator = {
	//语言包，可自由扩展
	lang : {
		"zh-cn":{
			fail:"提交失败,请检查以下选项:",
			require:'不能为空',
			require2:'请选择',
			email:'邮箱格式不正确',
			repeat:'两次输入不一致',
			url:'网址格式不正确',
			date:'日期格式不正确',
			df:'格式不正确'
		},
		"zh-tw":{
			fail:"提交失敗,請檢查以下選項:",
			require:'不能為空',
			require2:'請選擇',
			email:'郵箱格式不正確',
			repeat:'兩次輸入不一致',
			url:'網址格式不正確',
			date:'日期格式不正確',
			df:'格式不正確'
		},
		"en-us":{
			fail:"Failure to submit, please check the following options:",
			require:'Can not be empty',
			require2:'Please choose',
			email:'Wrong email format',
			repeat:'Twice for inconsistency',
			url:'Wrong url format',
			date:'Wrong date format',
			df:'Wrong format'
		},
		"ko-kr":{
			fail:"실패 제출, 다음 옵션을 확인:",
			require:'비워 둘 수 없습니다',
			require2:'선택하세요',
			email:'전자 우편함의 형식이 올바르지 않습니다.',
			repeat:'일치하지 않는 두 개의 입력',
			url:'잘못된 URL',
			date:'날짜 형식이 잘못되었습니다',
			df:'잘못된 형식'
		},
		"km-km":{
			fail:"ដាក់ស្នើបរាជ័យពិនិត្យមើលជម្រើសដូចខាងក្រោមនេះ:",
			require:'មិនអាចទទេ',
			require2:'សូមជ្រើសរើស',
			email:'ទ្រង់ទ្រាយសំបុត្រមិនត្រឹមត្រូវ',
			repeat:'ធាតុទាំងពីរគឺមិនស្របគ្នា',
			url:'URL មិនត្រឹមត្រូវ',
			date:'ទ្រង់ទ្រាយកាលបរិច្ឆេទមិនត្រឹមត្រូវ',
			df:'ទ្រង់ទ្រាយមិនត្រឹមត្រូវ'
		}
	},
	Require : /.+/,
	Email : /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/,
	Phone : /^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/,
	//Mobile : /^((\(\d{2,3}\))|(\d{3}\-))?((13[0-9])|(15[^4,\D])|(17[0-9])|(18[0,5-9]))\d{8}$/,
	Mobile : /^1\d{10}$/,//edit by linkunyuan
	//Url : /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/,
	Url : /^(http|https|ftp):\/\/\S+\.\w+/,//edit by linkunyuan
	IdCard : "this.IsIdCard(value)",
	Currency : /^\d+(\.\d+)?$/,
	Number : /^\d+$/,
	Zip : /^[1-9]\d{5}$/,
	QQ : /^[1-9]\d{4,11}$/,
	Integer : /^[-\+]?\d+$/,
	Double : /^[-\+]?\d+(\.\d+)?$/,
	English : /^[A-Za-z]+$/,
	Chinese : /^[\u0391-\uFFE5]+$/,
	Username : /^[a-z]\w{3,}$/i,
	UnSafe : /^(([A-Z]*|[a-z]*|\d*|[-_\~!@#\$%\^&\*\.\(\)\[\]\{\}<>\?\\\/\'\"]*)|.{0,5})$|\s/,//edit by linkunyuan
	IsSafe : function(str){return !this.UnSafe.test(str);},
	Ip : /^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d))))$/,	//add by lamson 20130322
	SafeString : "this.IsSafe(elt.value)",
	Filter : "this.DoFilter(elt.value, elt.getAttribute('accept'))",
	Limit : "this.limit(elt.value.length,elt.getAttribute('min'), elt.getAttribute('max'))",
	LimitB : "this.limit(this.LenB(elt.value), elt.getAttribute('min'), elt.getAttribute('max'))",
	Date : "this.IsDate(elt.value, elt.getAttribute('min'), elt.getAttribute('format'))",
	//Repeat : "value == document.getElementsByName(getAttribute('to'))[0].value", 
	Repeat : "elt.value == elt.form[elt.getAttribute('to')].value",//edit by linkunyuan
	Range : "elt.getAttribute('min') < (elt.value|0) && (elt.value|0) < elt.getAttribute('max')",
	Compare : "this.compare(elt.value,elt.getAttribute('operator'), elt.getAttribute('to'))",
	Custom : "this.Exec(elt.value, elt.getAttribute('regexp'))",
	Group : "this.MustChecked(elt.getAttribute('name'), elt.getAttribute('min'), elt.getAttribute('max'))",
	limit : function(len,min, max)
	{
		min = min || 0;
		max = max || Number.MAX_VALUE;
		return (min <= len && len <= max);
	},
	LenB : function(str)
	{
		return str.replace(/[^\x00-\xff]/g,"**").length;
	},
	Exec : function(op, reg)
	{
		return new RegExp(reg,"g").test(op);
	},
	compare : function(op1,operator,op2)
	{
		switch (operator)
		{
			case "NotEqual":
				return (op1 != op2);
			case "GreaterThan":
				return (op1 > op2);
			case "GreaterThanEqual":
				return (op1 >= op2);
			case "LessThan":
				return (op1 < op2);
			case "LessThanEqual":
				return (op1 <= op2);
			default:
				return (op1 == op2); 
		}
	},
	MustChecked : function(name, min, max)
	{
		var groups = document.getElementsByName(name);
		var hasChecked = 0;
		min = min || 1;
		max = max || groups.length;
		for(var i=groups.length-1;i>=0;i--){if(groups[i].checked){hasChecked++;}}
		return ( min <= hasChecked && hasChecked <= max );
	},
	DoFilter : function(input, filter)
	{
		return new RegExp("^.+\.(?=EXT)(EXT)$".replace(/EXT/g, filter.split(/\s*,\s*/).join("|")), "gi").test(input);
	},
	IsIdCard : function(number)
	{
		var date, Ai;
		var verify = "10x98765432";
		var Wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
		var area = ['','','','','','','','','','','','北京','天津','河北','山西','内蒙古','','','','','','辽宁','吉林','黑龙江','','','','','','','','上海','江苏','浙江','安微','福建','江西','山东','','','','河南','湖北','湖南','广东','广西','海南','','','','重庆','四川','贵州','云南','西藏','','','','','','','陕西','甘肃','青海','宁夏','新疆','','','','','','台湾','','','','','','','','','','香港','澳门','','','','','','','','','国外'];
		var re = number.match(/^(\d{2})\d{4}(((\d{2})(\d{2})(\d{2})(\d{3}))|((\d{4})(\d{2})(\d{2})(\d{3}[x\d])))$/i);
		if(re == null){return false;}
		if(re[1] >= area.length || area[re[1]] == ""){return false;}
		if(re[2].length == 12)
		{
			Ai = number.substr(0, 17);
			date = [re[9], re[10], re[11]].join("-");
		}
		else
		{
			Ai = number.substr(0, 6) + "19" + number.substr(6);
			date = ["19" + re[4], re[5], re[6]].join("-");
		}
		if(!this.IsDate(date, "ymd")){return false;}
		var sum = 0;
		for(var i = 0;i<=16;i++)
		{
			sum += Ai.charAt(i) * Wi[i];
		}
		Ai += verify.charAt(sum%11);
		return (number.length ==15 || number.length == 18 && number == Ai);
	},
	IsDate : function(op, formatString)
	{
		formatString = formatString || "ymd";
		var m, year, month, day;
		switch(formatString)
		{
			case "ymd" :
				m = op.match(new RegExp("^((\\d{4})|(\\d{2}))([-./])(\\d{1,2})\\4(\\d{1,2})$"));
				if(m == null ) return false;
				day = m[6];
				month = m[5]*1;
				year = (m[2].length == 4) ? m[2] : GetFullYear(parseInt(m[3], 10));
			break;
			case "dmy" :
				m = op.match(new RegExp("^(\\d{1,2})([-./])(\\d{1,2})\\2((\\d{4})|(\\d{2}))$"));
				if(m == null ) return false;
				day = m[1];
				month = m[3]*1;
				year = (m[5].length == 4) ? m[5] : GetFullYear(parseInt(m[6], 10));
			break;
			default :
			break;
		}
		if(!parseInt(month)){return false;}
		month = month==0 ?12:month;
		var date = new Date(year, month-1, day);
		return (typeof(date) == "object" && year == date.getFullYear() && month == (date.getMonth()+1) && day == date.getDate());
		function GetFullYear(y){return ((y<30 ? "20" : "19") + y)|0;}
	},
	ErrorItem : [],
	ErrorMessage : [],
	ClearState : function(elem)
	{
		with(elem)
		{
			if(style.color == "red"){style.color = "";}
			var lastNode = parentNode.childNodes[parentNode.childNodes.length-1];
			if(lastNode.className == "__ErrorMessagePanel"){parentNode.removeChild(lastNode);}	//edit by lamson
		}
	},
	AddError : function(ele, index, dt)
	{
		var str = ele.getAttribute("msg") ? ele.getAttribute("msg") : (ele.getAttribute('placeholder') ? ele.getAttribute('placeholder') :(this.DefalutMsg[dt]?this.DefalutMsg[dt]:this.DefalutMsg['Default']));
		this.ErrorItem[this.ErrorItem.length] = ele;
		this.ErrorMessage[this.ErrorMessage.length] = this.ErrorMessage.length + ":" + str;
	},
	Validate : function(fobj, mode, lang)
	{
		var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];		
		this.ErrorItem = [''];
		this.ErrorMessage = [_lang.fail];
		this.DefalutMsg = {
			Require:_lang.require,
			Require2:_lang.require2,
			Email:_lang.email,
			Repeat:_lang.repeat,
			Url:_lang.url,
			Date:_lang.date,
			Group:_lang.require2,
			Default:_lang.df
		};
		
		var fobj = ( typeof(fobj)=='object'? fobj : document.getElementById(fobj) ) || (window.event ? (event.srcElement || event.target) : null);
		if(!fobj){return true;}

		for(var i in fobj.elements)
		{
			var elt = fobj.elements[i];
			if(navigator.userAgent.indexOf('MSIE')==-1)
			{
				if(isNaN(i) || (! elt.name && ! elt.id)){continue;}	
			}else
			{
				try{
					if(! elt.name || ! elt.tagName){continue;}
				}catch(e)
				{
					continue;
				}
			}
			
			var _dt = elt.getAttribute("dataType");
			if(typeof(_dt) == "object" || typeof(this[_dt]) == "undefined"){continue;}
			this.ClearState(elt);
			if(elt.getAttribute("require") == "false" && elt.value == ""){continue;}
			switch(_dt)
			{
				case "IdCard" :
				case "Date" :
				case "Repeat" :
				case "Range" :
				case "Compare" :
				case "Custom" :
				case "Group" : 
				case "Limit" :
				case "LimitB" :
				case "SafeString" :
				case "Filter" :
					if(! eval(this[_dt]))
					{
						this.AddError(elt, i, _dt);
					}
				break;
				default :
					if(! this[_dt].test(elt.value))
					{
						this.AddError(elt, i, _dt + (elt.tagName == 'SELECT' ? 2 : ''));
					}
				break;
			}
		}

		if(this.ErrorMessage.length > 1)
		{
			mode = mode || 3; // edit by lamson
			var errCount = this.ErrorItem.length;
			switch(mode)
			{
				case 2 :
					for(var i=1;i<errCount;i++){this.ErrorItem[i].style.color = "red";}
				case 1 :
					alert(this.ErrorMessage.join("\n"));
					try{this.ErrorItem[1].focus();}catch(e){};
				break;
				case 3 :
					//edit by lamson
					if(typeof(IS_MOBILE)!='undefined' && IS_MOBILE)
					{
						lamDialogs_1 = typeof(lamDialogs_1) == 'undefined' ? new LamDialogs('lamDialogs_1') : lamDialogs_1;
						lamDialogs_1.toast(this.ErrorMessage[1].replace(/^\d+:/, ''));
						try{this.ErrorItem[1].focus();}catch(e){};			
					}
					else
					{
						for(var i=1;i<errCount;i++)
						{
							try{
								this.showMsg(this.ErrorItem[i], i);
							}
							catch(e){alert(e.description);}
						}
						try{this.ErrorItem[1].focus();}catch(e){};
					}
				break;
				default :
					alert(this.ErrorMessage.join("\n"));
				break;
			}
			return false;
		}
		return true;
	},
	//add by lamson
	showMsg : function(obj, i)
	{
		var span = document.createElement("SPAN");
		span.className = "__ErrorMessagePanel";	//edit by lamson
		//span.id = "__ErrorMessagePanel";
		span.style.color = "red";
		span.style.paddingLeft = "6px";	//add by lamson
		obj.parentNode.appendChild(span);
		span.innerHTML = (this.ErrorMessage[i] || obj.getAttribute('msg')).replace(/^\d+:/," * ");		
	}
};