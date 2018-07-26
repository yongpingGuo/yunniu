/**
 * 基本类
 * 
 * @author 林坤源
 * @version 5.5.5 最后修改时间 2016年05月13日
 * @link http://www.lamsonphp.com
 * @example
 * 		导入的例子： <script src="js/lamson.js" id="LamSonJs" lang="zh-cn" type="text/javascript"></script> 
				   或： <script src="js/lamson.js?01" id="LamSonJs" lang="zh-cn" type="text/javascript"></script> 
 * 语言请使用 zh-cn,en-us,zh-tw,ko-kr,km-km,pt-br 等语系-地区码的国际标准
 * 		对象： jQuery(1.9+)
 * 		函数： 
 */
if(typeof (jQuery) != 'function')
{
	alert('jQuery库不存在，请检查该页面是否已成功导入jquery.js');
}
// 如果没启用cookie就跳到指定页
if( ! navigator.cookieEnabled)
{
	window.location = '/illegal.html';
}

var $LamSonJs = $('#LamSonJs');
// 常量
if(typeof(APP) == 'undefined')
{
	CONTROLLER = $LamSonJs.data('controller') + '/';
	PUBLIC = $LamSonJs.data('public') + '/';
	URL_PATHINFO = $LamSonJs.data('urlmodel')+'';
	
	var _mvc_arr = CONTROLLER.split('/'), ROOT = APP = MODULE = "";
	for(var i=0,len=_mvc_arr.length-1; i<len; i++)
	{
		if(i<len-3)
		{
			ROOT += _mvc_arr[i] + '/';
		}
		if(i<len-2)
		{
			APP += _mvc_arr[i] + '/';
		}
		if(i<len-1)
		{
			MODULE += _mvc_arr[i] + '/';
		}
	}

	IS_MOBILE = 0;
	IN_WEIXIN = 0;
	
	VENDORS = PUBLIC + 'Vendors/';
	COM_IMG = PUBLIC + 'Common/images/';
	COM_CSS = PUBLIC + 'Common/css/';
	COM_JS = PUBLIC + 'Common/js/';
	IMG = PUBLIC + _mvc_arr[len-2] + '/images/';
	CSS = PUBLIC + _mvc_arr[len-2] + '/css/';
	JS = PUBLIC + _mvc_arr[len-2] + '/js/';
	
	_mvc_arr = i= len = null;
	
}

$(function(){
	$('.lamTip').mouseenter(function(){
		lamTip(this);	
	}).mouseleave(function(){
		$lamTip.add($lamTipPointer).fadeOut(300);
	});
	//给主表单绑定提交验证函数
	if($('form').length && typeof(Validator)=='undefined')
	{
		//jQuery.getScript(COM_JS + "validator.js"); // 这种方式不会缓存
		var script = document.createElement('script');
		script.src = COM_JS + "validator.js";
		document.body.appendChild(script);
			
		$(document).on('submit', '#masterForm', function(){return typeof(verlify_form)=='function' ? verlify_form(this) : _verlify_form(this);});
		
	}
	
	//美化上传组件
	lamBtyFile();

	// html5原生支持placeholder，对于不支持的浏览器（IE 9-），可用js模拟实现。
	// edit by lamson 2013-05-06

	// 判断是否支持placeholder
	function isPlaceholer()
	{
		var input = document.createElement('input');
		return "placeholder" in input;
	}
	// 不支持的代码
	if(!isPlaceholer())
	{
		$(':text, :password, textarea').each(function(index, element) {
			var $ele = $(element);
			if($ele.attr('placeholder'))
			{
				$ele.wrap('<samp style="position:relative; display:inline-block;"></samp>').after('<label></label>')
				.next().html($ele.attr('placeholder')).addClass('placeHolderLabel placeHolderLabel' + (element.id||element.className)).css({position:'absolute', left:0, top:0, textIndent:'4px', color:'#A9A9A9', fontSize:'12px', width:$ele.outerWidth(), height:$ele.outerHeight(), lineHeight:$ele.outerHeight()+'px', display:element.value=='' && ! $ele.is(':hidden') ? 'inline-display' : 'none'})
				.click(function(){
					$ele.focus();
				});			
				$ele.focus(function(){
					$(this).next().hide();
				})
				.blur(function(){
					if(this.value == "")
					{
						$(this).next().show();
					}
				});
			}
		});
	}
});
function _verlify_form(obj, mode)
{
	return Validator.Validate(obj, mode || 3);
}

var LamSon = {
	// 语言
	lang : $LamSonJs.attr('lang') || 'zh-cn',
	
	// 文档对象
	doc : function (top){
		return top ? window.top.document : document;
	},
	
	/*
	 * HTML的event兼容解决方案是 <a onclick="dofunc(event)">lamson</a>，其中 function dofunc(evt){ alert(evt.type); } JS的event兼容解决方法是 obj.onclick = function (event){ var evt = event || window.event; alert(evt); } JQUERY的event兼容解决方法是 obj.click(function(evt){alert(evt.type);})
	 */
	fixEvent : function (evt){
		var obj = evt || window.event;
		if(!obj)
		{
			obj = {
				srcElement : null,
				pageX : 0,
				pageY : 0,
				screenX : 0,
				screenY : 0
			};
		}
		return obj;
	},
	
	srcElement : function (evt){
		evt = this.fixEvent(evt);
		return window.attachEvent ? evt.srcElement : evt.target;
	},
	
	// 获取鼠标位置
	mouseCoords : function (evt, sc){
		evt = this.fixEvent(evt);
		if(!sc) // 相对于文档位置
		{
			//Chrome, Firefox, IE 9+
			if(evt.pageX || evt.pageY)
			{
				return {
					x : evt.pageX,
					y : evt.pageY
				};
			}
			return {
				//对于IE(s)和Firefox来讲，获取本窗口的滚动条顶部的偏移要用 document.documentElement.scrollTop
				//但对chrome来讲，得用document.body.scrollTop
				x : evt.clientX + document.documentElement.scrollLeft,
				y : evt.clientY + document.documentElement.scrollTop
			};
		}
		else //相对于可视区域（也叫相对于屏幕）
		{
			
			return {
				x : evt.screenX,
				y : evt.screenY
			};
		}
	},
	
	//将json格式的参数里面的每一对键值对都扩展进obj对象
	extend : function (json, obj){
		for( var i in json)
		{
			obj.prototype[i] = json[i];
		}
	}
}

/**
 * 检测客户端环境
 *
 * @author 林坤源
 * @version 4.5 最后修改时间 2013年04月25日
 * @link http://www.lamsonphp.com
 * 如需更强大的检测，可以参考 BrowserDetect类的写法
 	http://www.quirksmode.org/js/detect.html
 * 需要依赖的资源：
	对象：
		LamSon
	函数：
 *
 */
var LamClient = new function ()
{
	//语言包，可自由扩展
	this.lang = {
		"zh-cn":{
			other:"其它"
		},
		"zh-tw":{
			other:"其它"
		},
		"en-us":{
			other:"Other"
		},
		"ko-kr":{
			other:"다른"
		},
		"km-km":{
			other:"ផ្សេងទៀត"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];

	//私有实例属性（用函数内的局部变量来实现）
	var _ua = navigator.userAgent, _vd = navigator.vendor;

	//浏览器的名称
	this.bsName = (function (){  
		if(_ua.indexOf('MSIE')>=0){return 'IE'; }   
		else if(_ua.indexOf('Firefox')>=0){return 'Firefox';}    
		else if(_ua.indexOf('Chrome')>=0){return 'Chrome';}
		else if(window.opera){return 'Opera';}
		else if(_vd.indexOf('Apple')>=0){return 'Safari';}    
		else if(_vd.indexOf('Camino')>=0){return 'Camino';}    
		else if(_vd.indexOf('KDE')>=0){return 'Konqueror';} 
		else if(_vd.indexOf('iCab')>=0){return 'iCab';}
		else if(_ua.indexOf('Netscape')>=0){return 'Netscape';}
		else if(_ua.indexOf('OmniWeb')>=0){return 'OmniWeb';}
		else if(_ua.indexOf('Gecko')>=0){return 'Mozilla';}
		else{return _lang.other;}
	})();
	
	/*
		浏览器的版本号
		bsVersion：浏览器版本号
		ieVersion：IE版本号(如果为IE的话)
	*/
	switch(this.bsName)
	{
		case 'IE':
			var msie = _ua.match(/MSIE \d+./i).toString();   
			this.bsVersion = this.ieVersion = msie.replace(/MSIE (\d+)./i,'$1');
		break;
		case 'Firefox':
			this.bsVersion = _ua.substring(_ua.lastIndexOf('/')+1);
		break;
		case 'Chrome':
			this.bsVersion = _ua.substring(_ua.lastIndexOf('Chrome')+7, _ua.lastIndexOf(' ')+1);
		break;
		default:
			this.bsVersion = _ua;
		break;
	}

	this.ie = this.bsName == 'IE' ? true : false;
	for(var v=6; v<=10; v++)
	{
		this['ie'+v] = this.ieVersion == v;
	}
	
	this.adjust = {
		//各浏览器下防止出现滚动条的减少的值
		num:{
			x:(this.ie6 || this.ie7) ? 22 : 0,
			y:this.bsName=='Chrome' ? 4 : (this.bsName=='Firefox' ? 4 : ( (this.ie6 || this.ie7) ? 0 : 6))	
		},
		//各浏览器在分辨率较小的情况下防止垂直方向出现滚动条的减少的值
		scr:{
			x:0,
			y:this.bsName=='Chrome' ? 0 : (this.bsName=='Firefox' ? 2 : ( (this.ie6 || this.ie7) ? 0 : 0))	
		}
	}
}

// 如果可以全屏
function full_screen()
{
	try{
		top.window.moveTo(0,0);
		if (document.all){top.window.resizeTo(screen.availWidth,screen.availHeight);}
		else if (document.layers||document.getElementById)
		{
			if (top.window.outerHeight<screen.availHeight||top.window.outerWidth<screen.availWidth)
			{
				top.window.outerHeight = screen.availHeight;
				top.window.outerWidth = screen.availWidth;
			}
		}
	}catch(e){}
}

//兼容主流浏览器的窗口关闭
function lamclose()
{
	window.opener = null;
	window.open('', '_self');
	window.close();
}

/**
 * 加入收藏夹
 * @author 林坤源
 * @version 4.4 最后修改时间 2013年04月11日
 * @link http://www.lamsonphp.com
 * @param String url 要加入收藏的网址，默认为当前顶窗口的地址栏网址
 * @param String title 网页名称，默认为当前顶窗口的网页标题
 * @param String lang 语言类型
 * @example
 * 		<a onclick="addfavorite('http://www.lamsonphp.com', '林坤源的个人博客')">加入收藏</a> 
 * 需要依赖的资源： 
 *		对象：
 *		函数： 
 		常量：
 * 注意：目前（2013-04-25）只有 IE 和 Firefox 有【加入收藏】的JS接口，Chrome 和 Safari 默认配置下都不支持 
 */
function addfavorite(url, title, lang)
{
	//语言包，可自由扩展
	this.lang = {
		"zh-cn":{
			nonsup:"您的浏览器不支持此操作，请使用Ctrl+D进行添加！"
		},
		"zh-tw":{
			nonsup:"您的瀏覽器不支持此操作，請使用Ctrl+D進行添加！"
		},
		"en-us":{
			nonsup:"Your browser does not support this operation, please use Ctrl + D!"
		},
		"ko-kr":{
			nonsup:"귀하의 브라우저가이 작업을 지원하지 않는 추가 Ctrl 키 + D를 사용!"
		},
		"km-km":{
			nonsup:"កម្មវិធីរុករករបស់អ្នកពុំគាំទ្រប្រតិបត្ដិការនេះប្រើ Ctrl + D ដើម្បីបន្ថែម!"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];

	url = url || window.top.location.href;
	title = title || window.top.document.title;
	try
	{
		window.external.addFavorite(url, title);
	}
	catch (e)
	{
		try
		{
		   window.sidebar.addPanel(title, url, "");
		}
		catch (e)
		{
		   alert(_lang.nonsup);
		}
	}
}

/**
 * 设为首页
 * @author 林坤源
 * @version 4.4 最后修改时间 2013年04月11日
 * @link http://www.lamsonphp.com
 * @param Object obj 当前对象
 * @param String url 要加入收藏的网址，默认为当前工作网页的域名
 * @param String lang 语言类型
 * @example
 * 		<a onclick="sethomepage(this, 'http://www.lamsonphp.com')">设为首页</a> 
 * 需要依赖的资源： 
 *		对象：
 *		函数： 
 		常量： 
 * 注意：目前（2013-04-25）只有 IE 有【设为首页】的JS接口，Firefox 和 Chrome 和 Safari 默认配置下都不支持
 */
function sethomepage(obj, url, lang)
{
	//语言包，可自由扩展
	this.lang = {
		"zh-cn":{
			beset:"此操作被浏览器拒绝！\n请在浏览器地址栏输入 about:config 并回车\n然后将[signed.applets.codebase_principal_support]设置为'true'",
			nonsup:'抱歉，您所使用的浏览器无法完成此操作。'
		},
		"zh-tw":{
			beset:"此操作被瀏覽器拒絕！ \n請在瀏覽器地址欄輸入 about:config 並回車\n然後將[signed.applets.codebase_principal_support]設置為'true'",
			nonsup:'抱歉，您所使用的瀏覽器無法完成此操作。'
		},
		"en-us":{
			beset:"Refused by your browser! \n Please input about:config in the browser location and press Enter-key \n Then set  [signed.applets.codebase_principal_support] to 'true'",
			nonsup:'I\'m sorry，your broswer don\'t support。'
		},
		"ko-kr":{
			beset:"이 작업은 거부하도록 브라우저입니다! 설정하고 Enter 키를 누릅니다 : 브라우저의 주소 표시 줄에 대한 입력[Signed.applets.codebase_principal_support]은 '참'으로 설정",
			nonsup:'죄송합니다, 브라우저는이 작업을 완료하는 데 사용됩니다.'
		},
		"km-km":{
			beset:"ប្រតិបត្តិការនេះត្រូវបានច្រានចោលកម្មវិធីរុករក! \ N នៅក្នុងរបារអាសយដ្ឋានរបស់កម្មវិធីរុករកបញ្ចូល about: config រួចចុចបញ្ចូល \ n បន្ទាប់មក [signed.applets.codebase_principal_support] ត្រូវបានកំណត់ទៅ 'ពិត'",
			nonsup:'សូមអភ័យទោសប៉ុន្តែអ្នកកំពុងប្រើកម្មវិធីរុករកមួយដើម្បីបញ្ចប់ការប្រតិបត្ដិការនេះ។'
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];
	
	url = url || ('http://'+document.domain);
	try
	{
		obj.style.behavior='url(#default#homepage)';
		obj.setHomePage(url);
		return true;
	}
	catch(e)
	{
		if(window.netscape)
		{
			try
			{
				netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect"); 
			} 
			catch (e)
			{ 
				alert(_lang.beset); 
			}
			var prefs = Components.classes['@mozilla.org/preferences-service;1'].getService(Components.interfaces.nsIPrefBranch);
			prefs.setCharPref('browser.startup.homepage', url);
		}
		else
		{
			alert(_lang.nonsup);
		}
	}
}

/**
 * 插入FLASH，当embed_id为空时，随机生成一个ID值
 */
function flash(url, w, h, embed_id, flashvars)
{
	return '<embed width="'+ w +'" height="'+ h +'" src="'+ url +'" id="'+ (embed_id || ('f'+Math.ceil(Math.random()*35))) +'" quality="high"  align="middle" allowFullScreen="true"  allowScriptAccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" wmode="transparent" hspace="0" vspace="0" '  + (flashvars? ' flashvars="' +  flashvars + '" ' : '') +  '/>';
}

/**
 * 格式化日期
 * @author 林坤源  
 * @param string format 格式化字符串   
	j：将日显示为不带前导零的数字，如1   
	d：将日显示为带前导零的数字，如01   
	D：将日显示为缩写形式，如Sun   
	l：将日显示为全名，如Sunday   
	w: 中文的星期，如星期日
	n：将月份显示为不带前导零的数字，如一月显示为1   
	m：将月份显示为带前导零的数字，如01  
	M：将月份显示为缩写形式，如Jan  
	F：将月份显示为完整月份名，如January  
	y：以两位数字格式显示年份，如2012将显示为12  
	Y：以四位数字格式显示年份，如2012
	g：使用12小时制将小时显示为不带前导零的数字，注意||的用法  
	h：使用12小时制将小时显示为带前导零的数字  
	G：使用24小时制将小时显示为不带前导零的数字  
	H：使用24小时制将小时显示为带前导零的数字  
	I：将分钟显示为不带前导零的数字  
	i：将分钟显示为带前导零的数字  
	S：将秒显示为不带前导零的数字  
	s：将秒显示为带前导零的数字  
	b：将毫秒显示为不带前导零的数字  
	B：将毫秒显示为带前导零的数字  
	a：显示am/pm  
	A：显示AM/PM
 * @return string 格式化后的日期
 * @example new Date().format("Y-m-d H:i:s w")
 */ 
Date.prototype.format = function (format) {  
    var date = this;  
    return format.replace(/"[^"]*"|'[^']*'|\b(?:[jdDlwnmMFyYghGHIiSsbBaA])\b/g, function($0){  
		switch($0){  
			case 'j': return date.getDate();  
			case 'd': return zeroize(date.getDate());  
			case 'D': return ['Sun', 'Mon', 'Tue', 'Wed', 'Thr', 'Fri', 'Sat'][date.getDay()];  
			case 'l': return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][date.getDay()];  
			case 'w': return ' 星期' + '日一二三四五六'.charAt(date.getDay());
			case 'n': return date.getMonth() + 1;  
			case 'm': return zeroize(date.getMonth() + 1);  
			case 'M': return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][date.getMonth()];  
			case 'F': return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][date.getMonth()];  
			case 'y': return new String(date.getFullYear()).substr(2);  
			case 'Y': return date.getFullYear();  
			case 'g': return date.getHours() % 12 || 12;  
			case 'h': return zeroize(date.getHours() % 12 || 12);  
			case 'G': return date.getHours();  
			case 'H': return zeroize(date.getHours());  
			case 'I': return date.getMinutes();  
			case 'i': return zeroize(date.getMinutes());  
			case 'S': return date.getSeconds();  
			case 's': return zeroize(date.getSeconds());  
			case 'b': return date.getMilliseconds();  
			case 'B': return zeroize(date.getMilliseconds());  
			case 'a': return date.getHours() < 12 ? 'am' : 'pm';  
			case 'A': return date.getHours() < 12 ? 'AM' : 'PM';
		}  
    });  
}

/**
 * 将日期及时间显示在$obj上
 * @param Object $obj obj的Jquery对象
 * @param String fmt 格式
 * @param Bool lunar 是否显示农历日期，为true时必须同时加载cndate.js
 * @param int init 初始化时间
 */
function show_datetime($obj, fmt, lunar, init)
{
	var delay = -1;
	var fmt = fmt ? fmt : 'Y年m月d日';
	if(fmt.match(/s/i))
	{
		delay = 1;	
	}else if(fmt.match(/i/i))
	{
		delay = 60;
	}
	
	function get_time(init)
	{
		var d = init ? new Date(init) : new Date();
		var str =  d.format(fmt) + ( lunar && typeof(GetLunarDay)=='function' ? ' ' + GetLunarDay(d.getFullYear(),d.getMonth()+1,d.getDate()) : '' );
		if($obj[0])
		{
			if( typeof($obj[0].innerHTML)!='undefined')
			{
				 $obj.html( str );
			}else
			{
				$obj.val( str );	
			}
		}
	}
	get_time(init || 0);
	delay >0 && window.setInterval(function(){
		if(init)
		{
			init = init+delay*1000;
			get_time(init);
		}else{
			get_time();
		}
	}, delay*1000);
}

/**
 * 检测唯一性字段的值是否可用
 * 
 * @author 林坤源
 * @version 5.4.6 最后修改时间 2015年07月27日
 * @link http://www.lamsonphp.com
 * @param Object obj 要检测的对象
 * @param Number|String id 查询的时候要跳过的主键值（字段值）
 * @param String key 字段名
 * @param String langkey 语言的下标，为空时则取控制器名
 * @param bool async 是否采用异步通讯(默认: true) 默认设置下，所有请求均为异步请求。如果需要发送同步请求，请将此选项设置为 false。注意，同步请求将锁住浏览器，用户其它操作必须等待请求完成才可以执行。
 * @param String lang 语言类型
 * @example
 * 		<input type="text" name="username" regexp=".{2,15}" vldurl="User/" msg="管理员名称长度不符合要求" onblur="unique(this, 1, 'username', 'user')" />
 * 注意：IMG目录下要有一张名为no.gif和yes.gif的图片。要将该字段的验证规则写在regexp属性里面，提示文本写在msg里面(非必须), 验证的访问地址前缀写在vldurl里面(非必须)。 
 * 需要依赖的资源： 
 *		对象： LamSon, jQuery
 *		函数： 
 		常量： IMG
 */
function unique(obj, id, key, langkey, async, lang)
{
	// 语言包，可自由扩展
	this.lang = {
		"zh-cn":{
			df:"格式有误"
		},
		"zh-tw":{
			df:"格式有誤"
		},
		"en-us":{
			df:"Wrong format"
		},
		"ko-kr":{
			df:"형식 오류"
		},
		"km-km":{
			df:"ដែលមិនត្រឹមត្រូវ"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];

	if(typeof (obj) == 'string')
	{
		return !$('input[name="' + obj + '"]').next('dfn').find('img[src*="no.gif"]').length;
	}

	id = id || 0;
	async = async === undefined ? true : async;
	var $obj = $(obj);

	$obj.siblings('span').remove();
	var $s = $obj.next('dfn');
	if(!$s.length)
	{
		$obj.after('<dfn class="LamImmediate"></dfn>');
		$s = $obj.next('dfn');
	}else
	{
		$s.addClass('LamImmediate');
	}
	$s.html(' <img src="' + COM_IMG + 'loading_spin.gif" />');
	if(obj.value == '')
	{
		if($obj.attr('require') == 'false')
		{
			$s.html('');
			return true;
		}else
		{
			$s.html(' <img src="' + IMG + 'no.gif" /> ' + ($obj.attr('msg') || _lang.df)).css('color', 'red');
			return false;
		}
	}
	else
	{
		if($obj.attr('regexp') && !new RegExp($obj.attr('regexp')).test(obj.value))
		{
			$s.html(' <img src="' + IMG + 'no.gif" /> ' + ($obj.attr('msg') || _lang.df)).css('color', 'red');
			return false;
		}
		else
		{
			$.ajax(U(($(obj).attr('vldurl') || '') + 'validate', (key || obj.name) + '=' + encodeURIComponent(obj.value) + '&id=' + id + (langkey ? '&langkey=' + langkey : '')),{async:async, dataType:'json', success:function(json){
				$s.html('&nbsp;<img src="' + IMG + json.ok + '.gif" /> ' + json.html).removeClass().addClass('LamImmediateAjax').css('color', json.ok == 'no' ? 'red' : 'green');
				return json.ok == 'no';	//注意此处只有在async为false时才有作用
			}});
		}
	}
}

/**
 * URL组装 支持不同URL模式
 *
 * @param string url URL格式：模块/控制器/操作[?key=value&key=value]
 * @param string args 传入的参数 key=value&key=value
 * @return string
 * 需要依赖的资源： 
 * 		对象： jQuery(1.9+)
 * 		函数： 
 */
function U(url, args)
{
	var prefix = '';
	args = args || '';
	//去掉开头的&
	if(args.indexOf('&') == 0)
	{
		args = args.substr(1);
	}
		
	var path = url.split('?');
	
	switch(path[0].split('/').length)
	{
		case 1:
			prefix = CONTROLLER;
		break;
		case 2:
			prefix = MODULE;
		break;
		case 3:
			prefix = APP;
		break;
	}
	
	path[1] && (args = path[1] + '&' + args);
	
	url = prefix + (path[0] != '' ? path[0]+ '/' : '');
	if(args)
	{
		switch(URL_PATHINFO) // 注意数据类型
		{
			case '0':
			case '2':
				url += '?' + args;
			break;
			
			case '1':
				url += args.replace(/=|&/g, '/');
			break;
		}
	}
	
	return encodeURI (url);
}

//字符个数统计
function char_cnt(obj, name)
{
	$('#'+(name || obj.name)+'_charcnt').html($(obj).val().length);	
}
//处理中文的逗号
function deal_comma(obj)
{
	obj.value = $.trim(obj.value.replace(/，/g, ','));	
}
//限制只能输入数字
function must_digit(obj)
{
	obj.value = obj.value.replace(/\D/g, '');
}

 /*  
 * 填充0字符  
 * @param String/Number value 需要填充的字符串
 * @param Number length 总长度  
 * @return 填充后的字符串  
 */ 
function zeroize(value, length)
{  
	if(!length){length = 2;}  
	value = new String(value);  
	for(var i = 0, zeros = ''; i < (length - value.length); i++){zeros += '0';}  
	return zeros + value;  
};  

/**
 * 显示Loading图片
 * 
 * @author 林坤源
 * @param Object $obj 容器的jQuery对象
 * @param String text 要显示的文本
 * @param String id 要显示的图片的路径
 * @param String lang 语言类型
 * 需要依赖的资源：
 * 		对象： LamSon, jQuery
 * 		函数： 
 * 		常量：COM_IMG
 * 注意：COM_IMG 目录下要有一张名为loading.gif的图片
 */
function show_loading($obj, text, img, lang)
{
	// 语言包，可自由扩展
	this.lang = {
		"zh-cn":{
			loading:"数据加载中……"
		},
		"zh-tw":{
			loading:"數據加載中……"
		},
		"en-us":{
			loading:"loading……"
		},
		"ko-kr":{
			loading:"데이터 로딩……"
		},
		"km-km":{
			loading:"កំពុងផ្ទុក"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];

	text = text==undefined ? _lang.loading : text;
	img = img || (COM_IMG +"loading.gif");
	$obj.show().html('<p class="loadingP"><img src="' + img + '"/>' + text + '</p>');
}

/**
 * Mask(遮罩层)
 * 
 * @author 林坤源
 * @version 4.4.0
 * @param Object cssjson json格式的css设置，例如{opacity:0.5, background:'#EEE'}
 * @param String id 遮罩层的id值
 * @param Boolean top 是否在最顶层窗口显示 
 * 需要依赖的资源： 
 * 		对象： LamSon, LamClient, jQuery
 * 		函数：
 */
function mask(cssjson, id, top)
{
	id = id || 'lamWinMask';
	if($('#' + id).length)
	{
		return;
	}
	var $body = $(LamSon.doc(top).body);

	// ie6下当前窗口只能动态加载当前文档中的对象，所以可以window.top.document.body.append( window.top.document.createElement('div') )，但不能window.top.document.body.append( document.createElement('div') )

	var div = LamSon.doc(top).createElement('div');
	var $div = $(div);
	$div.attr('id', id);
	// 一定要先包启层后再给层设置CSS，不然会出现闪烁的现象
	$body.append($div)
	// 禁止对页面的任何操作
	.bind('selectstart.plugin contextmenu.plugin', function()
	{
		return false
	});

	$div.attr('style', 'position:fixed;top:0;left:0;text-align:center;z-index:1000;filter:alpha(opacity=20);opacity:0.2; background:#000;').width(window.screen.availWidth).height(window.screen.availHeight);
	if(typeof (cssjson) == 'object')
	{
		if(cssjson.opacity)
		{
			div.style.filter = 'alpha(opacity=' + cssjson.opacity * 100 + ')';
			div.style.opacity = cssjson.opacity;
			delete (cssjson.opacity);
		}
		$div.css(cssjson);
	}
	// IE 6 下必须做一些特殊的处理
	if(LamClient.ie6)
	{
		var width = $body.outerWidth() - (top ? LamClient.adjust.num.x : 0);
		var height = $body.outerHeight();
		$(div).css({
			'position' : 'absolute',
			'width' : width,
			'height' : height
		});

		// 为了遮盖select标签
		// 第一种方案为 IE 6 创建Iframe遮罩,缺点就是无法出现透明效果了
		// $('<iframe id="lamMaskIframe"></iframe>').appendTo($body).css({ position:"absolute", top:0, left:0, height:height, width:width, border:0 });
		// 第二种方案为IE6 下出现遮罩层的时候，把可见的select全隐藏掉了(如果某些select不想被隐藏，可以用css提高visibility的优先级)，移除遮盖层时再显示出来。
		$('select:visible').addClass('lamMaskSelect').css('visibility', 'hidden');
	}
}

// 移除Mask(遮罩层)
function unmask(id, top)
{
	var b = LamSon.doc(top).body;
	try
	{
		b.removeChild(LamSon.doc(top).getElementById(id || 'lamWinMask'));
	}catch(e)
	{
	}
	$(b).unbind('.plugin');
	$('.lamMaskSelect').css('visibility', 'visible').removeClass('lamMaskSelect');
}

/**
 * 图像上传前的预览，只支持 IE 6+ 和 Firefox 3+, Chrome
 * 
 * @author 林坤源
 * @version 5.3.0 最后修改时间 2013年04月25日
 * @link http://www.lamsonphp.com
 * @example //例子一：图片文件域
 *          <tr class="lamFileWrapper lamFileThumbWrapper">
 *          <th>......</th>
 *          <td> <div class="lamFileBox" thumb="0"><input type="text" class="lamThumbTxt" readonly="readonly" /><button class="lamBtnView" type="button">浏览...</button><button type="button" class="lamBtnAdd">增加</button><button type="button" class="lamBtnRemove">移除</button><input type="file" name="thumb[]" class="lamUpload" /><input type="hidden" name="_thumb[]" value="" /></div> <img class="lamThumbImg" thumb="0" style="width:140px;height:200px;" src="images/thumbview.gif" /> </td>
 *          </tr>
 *          <tr class="lamFileWrapper lamFileThumbWrapper">
 *          <th>......</th>
 *          <td> <div class="lamFileBox" thumb="1"><input type="text" class="lamThumbTxt" readonly="readonly" /><button class="lamBtnView" type="button">浏览...</button><button type="button" class="lamBtnAdd">增加</button><button type="button" class="lamBtnRemove">移除</button><input type="file" name="thumb[]" class="lamUpload" /><input type="hidden" name="_thumb[]" value="" /></div> <img class="lamThumbImg" thumb="1" style="width:140px;height:200px;" src="images/thumbview.gif" /> </td>
 *          </tr>
 *          //例子二：附件文件域
 *          <tr class="lamFileWrapper lamFileAttachWrapper">
 *          <th>......</th>
 *          <td> <div class="lamFileBox"><input type="text" class="lamThumbTxt" readonly="readonly" /><button class="lamBtnView" type="button">浏览...</button><input type="file" name="files" class="lamUpload" /></div> </td>
 *          </tr>
 * 需要依赖的资源： 
 * 		对象：LamSon, LamClient, JQuery 
 * 		函数：
 * 		常量：IMG
 *
 * 注意：IMG目录下要有一张名为blank.gif的图片和thumbview.gif的图片
 * 新版本的 Firefox 7 不认识getAsDataURL()，解决办法是使用： var file=document.getElementByIdx_x("file"); var objectURL = window.URL.createObjectURL(file.files[0]); alert(objectURL); 		
 */
// 美化上传组件
function lamBtyFile()
{
	lamFileThumbWrapperLength = $('.lamFileThumbWrapper').length;
	/* Firefox 的文件域不支持width，得用size，size="1"时为85像素，每个size之间相差7个像素的宽度 */
	if(LamClient.bsName == 'Firefox')
	{
		$('input.lamUpload').attr("size", 1 + ($('.lamUpload').outerWidth() - 85 - $('button.lamBtnView').outerWidth()) / 7);
	}

	$('input.lamUpload').bind('change.lam',function(evt)
	{
		lamFileUpload(evt);
	});
	$('button.lamBtnAdd').bind('click.lam',function(evt)
	{
		lamFileBtnAdd(evt);
	}).first().show();
	$('button.lamBtnRemove').bind('click.lam',function(evt)
	{
		lamFileBtnRemove(evt);
	}).filter(':gt(0)').show();

	if(typeof (show_thumb) == 'function')
	{
		$('.lamThumbImg[thumb]').each(function(index, element)
		{
			show_thumb($(element), $(element).attr('del'));
		});
	}
}
// 相关事件的响应函数
// 【增加】按钮的响应函数
function lamFileBtnAdd(evt)
{
	var obj = evt.target;
	var $lamFile = $(obj).parent();
	var $wrapper = $(obj).parents('.lamFileWrapper');
	if($wrapper.has('.lamFileThumbWrapper'))
	{
		lamFileBtnAddThumb(obj, $lamFile, $wrapper);

		// 图片文件域中【增加】按钮的自定义扩展函数
		if(typeof (lamFileBtnAddThumbExtend) == 'function')
		{
			lamFileBtnAddThumbExtend(obj, $lamFile, $wrapper);
		}
	}
	else if($wrapper.has('.lamFileAttachWrapper'))
	{
		lamFileBtnAddAttach(obj, $lamFile, $wrapper);

		// 附件文件域中【增加】按钮的自定义扩展函数
		if(typeof (lamFileBtnAddAttachExtend) == 'function')
		{
			lamFileBtnAddAttachExtend(obj, $lamFile, $wrapper);
		}
	}
}
// 图片文件域中【增加】按钮的内置扩展函数
function lamFileBtnAddThumb(obj, $lamFile, $wrapper)
{
	var index = lamFileThumbWrapperLength++;
	var $last = $('.lamFileThumbWrapper:last');
	var $clone;

	// IE 6 动态生成的INPUT是不能改NAME的，而且 IE 6 下对于clone()所得到的对象所做的修改也会同时影响到原对象（原因未知）
	if(LamClient.ie6)
	{
		var html = $wrapper.html().replace(/thumb="0"/ig, 'thumb="' + index + '"');
		// wrapper容器所使用的标签
		var tag = $wrapper[0].tagName.toLowerCase();
		var $clone = $('<' + tag + ' class="lamFileWrapper lamFileThumbWrapper">' + html + '</' + tag + '>');

		$clone.find('input.lamUpload').change(function(evt)
		{
			lamFileUpload(evt);
		});
		$clone.find('button.lamBtnAdd').click(function(evt)
		{
			lamFileBtnAdd(evt);
		});
		$clone.find('button.lamBtnRemove').click(function(evt)
		{
			lamFileBtnRemove(evt);
		});
	}
	else
	{
		var $clone = $(obj).parents('.lamFileThumbWrapper').clone(true);
	}

	$clone.insertAfter($last).find('.autoGrid').remove().end().find('.lamFileBox').attr('thumb', index).find(':text, :file, :hidden').val('').siblings('.lamBtnAdd').hide().next().show();
	$clone.find('.lamThumbImg').attr({
		thumb : index,
		src : IMG + 'thumbview.gif'
	});
}
// 附件文件域中【增加】按钮的内置扩展函数
function lamFileBtnAddAttachExtend(obj, $lamFile, $wrapper)
{

}

// 【移除】按钮的响应函数
function lamFileBtnRemove(evt)
{
	var obj = evt.target;
	var $lamFile = $(obj).parent();
	var $wrapper = $(obj).parents('.lamFileWrapper');
	if(confirm('确定要移除？'))
	{
		// 移除前的自定义扩展函数
		if($wrapper.has('.lamFileThumbWrapper') && typeof (lamFileBtnRemoveThumbExtend) == 'function')
		{
			lamFileBtnRemoveThumbExtend(obj, $lamFile, $wrapper);
		}
		else if($wrapper.has('.lamFileAttachWrapper') && typeof (lamFileBtnRemoveAttachExtend) == 'function')
		{
			lamFileBtnRemoveAttachExtend(obj, $lamFile, $wrapper);
		}

		// 移除整个wrapper容器
		$wrapper.remove();
	}
}

// 【文件域】的响应函数
function lamFileUpload(evt)
{
	var obj = evt.target;
	var $lamFile = $(obj).parent();
	var $wrapper = $(obj).parents('.lamFileWrapper');

	// 在文本域中显示文件域的内容
	$lamFile.find(':text').val(obj.value);

	if($wrapper.has('.lamFileThumbWrapper'))
	{
		lamFileUploadThumb(obj, $lamFile, $wrapper);

		// 图片文件域onchange时的自定义扩展函数
		if(typeof (lamFileUploadThumbExtend) == 'function')
		{
			lamFileUploadThumbExtend(obj, $lamFile, $wrapper);
		}
	}
	else if($wrapper.has('.lamFileAttachWrapper'))
	{
		lamFileUploadAttach(obj, $lamFile, $wrapper);

		// 附件文件域onchange时的自定义扩展函数
		if(typeof (lamFileUploadAttachExtend) == 'function')
		{
			lamFileUploadAttachExtend(obj, $lamFile, $wrapper);
		}
	}
}
// 图片文件域onchange时的内置扩展函数
function lamFileUploadThumb(obj, $lamFile, $wrapper)
{
	// ///////////////// 预览 /////////////////////////
	var $obj = $(obj);// 文件对象
	var $img = $('.lamThumbImg[thumb="' + $lamFile.attr('thumb') + '"]');// 预览图片对象
	// IE 9- 采用滤镜进行预览
	if(LamClient.ie && !LamClient.ie10)
	{
		// 本来 IE 9- 都可以采用滤镜效果进行预览的，但经测试发现部份 IE 6 浏览器对滤镜的支持不好，故此分开设置
		if(LamClient.ie6)
		{
			$img.attr('src', $obj.val());
		}
		else
		{
			try
			{
				// IE7+ 因安全性问题已无法像 IE 6 那样直接通过 input[file].value 获取完整的文件路径
				$obj.select();

				if(window.top == window) // 非框架嵌入
				{
					$obj.blur(); // 解决 IE 9 下document.selection拒绝访问的错误，不过如果网页是以框架的形式显示，仍然会报错

				}
				else
				{
					// $ele.focus(); //如果当前页面被嵌在框架中，则file域.blur()之后，file域中原本被选中的文本将会失去选中的状态，因此，不能使用file域.blur()。可以让当前页面上的其他元素，如div，button等获得焦点即可，如div对象.focus()。注意，如果是div，则要确保div有至少1像素的高和宽，方可获得焦点。
					$lamFile.find('.lamBtnView').focus();
				}

				// 设置滤镜并显示
				$img[0].src = IMG + 'blank.gif';
				$img[0].style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod='scale',src='" + document.selection.createRange().text + "')";
				document.selection.empty();
			}catch(e)
			{
			}
		}
	}
	else
	// IE 10+ , Firefox 3.6+, Chrome 使用html 5新增的对象
	{
		var reader = new FileReader();
		reader.readAsDataURL($obj[0].files[0]);
		reader.onload = function(e)
		{
			$img.attr('src', reader.result);
		}
	}
}
// 附件文件域onchange时的内置扩展函数
function lamFileUploadAttach(obj, $lamFile, $wrapper)
{

}

/**
 * jQuery Dialogs 插件
 * 
 * @author 林坤源
 * @version 1.5 最后修改时间 2014年11月27日
 * @link http://www.lamsonphp.com
 * @copyright Copyright &copy; 2007-2014, 林坤源 
 * 需要依赖的资源： 
 * 		对象：LamSon, jQuery(1.9+), jQuery UI(1.10.2+)
 * 		函数：
 * 注意：如果对按钮绑定了响应函数，则插件将会关闭“自动关闭”的功能，需要在按钮的响应函数中显式调用才会关闭。   对象.close(true);即可
 */
function LamDialogs(wrapid, obj, lang)
{
	var _this = this;	//让私有方法可以调用公有属性和公有方法
	this.lang = {
		"zh-cn":{
			ok_btn:"确 定",
			cancel_btn:"取 消",
			alert_title:"信息警告框",
			okalert_title:"信息提示框",
			confirm_title:"信息确认框",
			prompt_title:"信息输入框"
		},
		"zh-tw":{
			ok_btn:"確 定",
			cancel_btn:"取 消",
			alert_title:"信息警告框",
			okalert_title:"信息提示框",
			confirm_title:"信息確認框",
			prompt_title:"信息輸入框"
		},
		"en-us":{
			ok_btn:"O K",
			cancel_btn:"Cancel",
			alert_title:"Warning Box",
			okalert_title:"Info Box",
			confirm_title:"Confirm Box",
			prompt_title:"Prompt Box"
		},
		"ko-kr":{
			ok_btn:"결 정",
			cancel_btn:"취 소",
			alert_title:"경고 상자",
			okalert_title:"정보 상자",
			confirm_title:"상자를 확인",
			prompt_title:"프롬프트 상자"
		},
		"km-km":{
			ok_btn:"ដែលបានកំណត់",
			cancel_btn:"លុបចោល",
			alert_title:"ប្រអប់សារព្រមាន",
			okalert_title:"ប្រអប់សារ",
			confirm_title:"ប្រអប់ធីកព",
			prompt_title:"ប្រអប់បញ្ចូលព"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];
	
	this.top = 0;
	this.wrapid = wrapid || 'LamDialogsWrapper';
	this.$evtObj = $(obj);
	this.$uiDiglog = null;
	this.opns = {};
	this.flscr = 1;	//是否跟随滚动条（针对IE 6）
	this.draggable = ! LamClient.ie6;	//IE6 下的拖拽容易出错，所以默认禁止
	this.autoClose = true;	//是否自动关闭
	this.initAutoClose = true;	//是否自动关闭(每次响应按钮时都会将autoClose属性的值保持与这个值同步)
	
	if(typeof(LamDialogs._initialized)=='undefined')
	{
		//最好在文档已加载完毕之后（例如放在jquery的$(document).ready(function(){});里）再执行这个操作。否则IE6 下可能会报“无法打开站点，已终止操作”的错误提示
		LamDialogs.prototype.init = function (){
			if(!this.$wrapObj || !this.$wrapObj.length)
			{
				$(document.body).append('<table id="' + this.wrapid + '" class="LamDialogsWrapper"><tr>' + '<th id="' + this.wrapid + 'FstTh" class="LamDialogsFstTh"></th>' + '<td id="' + this.wrapid + 'FstTd" class="LamDialogsFstTd"></td>' + '</tr></table>');
			}
			this.$wrapObj = $('#' + this.wrapid);
			return this;
		};
		
		//【关闭】按钮的点击事件的响应
		LamDialogs.prototype.close = function (auto){
			if(auto || this.autoClose)
			{
				try{
					this.$wrapObj.dialog("close");
				}catch(e){};
				
				if(LamClient.ie6)
				{
					$('.lamMaskSelect').css('visibility', 'visible').removeClass('lamMaskSelect');
				}
			}
		};
		LamDialogs.prototype.dialogOption = function (opns){
			if(LamClient.ie7 && opns.width)
			{
				$('.ui-dialog-titlebar').width(opns.width);
			}
			this.opns = opns;
		};

		// 构造$()对象的dialog()方法所需的JSON参数
		LamDialogs.prototype.createOption = function (callback_ok, callback_no, title, innerclass){
			var _this = this;
			var option = {
				draggable : _this.draggable,
				modal : true,
				width : 'auto',
				buttons : {},
				title : title || (_lang[innerclass.toLowerCase() + '_title']),
				open : function (event, ui){
				},
				dragStop : function (event, ui){
					_this.top = parseInt(_this.$wrapObj.parent('.ui-dialog').css('top')) - $(window).scrollTop();
				},
				close : function (event, ui){
				},
				show:{effect:'highlight', color:'#FF0000', duration:300},
				hide:{effect:(LamClient.ie && LamClient.bsVersion<9) ? 'fade' : 'explode', duration:500}	
			};
			// 确定按钮
			option['buttons'][_lang['ok_btn']] = function (){
				if( callback_ok && typeof (callback_ok) == 'function' )
				{
					_this.autoClose = _this.initAutoClose;
					callback_ok();
					_this.close();
				}else
				{
					_this.close(true);
				}
			};
			// 取消按钮
			if(typeof(callback_no)!='object')	//注意 null的typeof()是返回object而不是null!!!!!
			{
				option['buttons'][_lang['cancel_btn']] = function (){
					_this.$wrapObj.parent('.ui-dialog').off('keydown.LamDialogsKeydown');// 去掉【Esc】键的响应函数
					if( callback_no && typeof (callback_no) == 'function' )
					{
						_this.autoClose = _this.initAutoClose;
						callback_no();
						_this.close();
					}else
					{
						_this.close(true);
					}
				};
			}
			return option;
		};
		
		
		LamDialogs.prototype.dialog = function (message, callback_ok, callback_no, title, classname, innerclass){
			var _this = this;
			this.$wrapObj.removeClass().addClass('LamDialogsWrapper ' + innerclass + (classname ? ' '+ classname : ''))
			.find('.LamDialogsFstTd').html(message);
			this.$wrapObj.dialog($.extend(this.createOption(callback_ok, callback_no, title, innerclass), this.opns));
			this.$uiDiglog = this.$wrapObj.parent('.ui-dialog');
			this.top = parseInt(this.$uiDiglog.css('top')) - $(window).scrollTop();
			
			if( ! this.draggable) 
			{
				$('.ui-dialog-titlebar').addClass('ui-dialog-titlebar-draggable');
			}
			
			if(LamClient.ie7 || LamClient.ie6)
			{
				this.$wrapObj.width() >=280 || this.$wrapObj.width(280);
				this.$uiDiglog.children('.ui-dialog-titlebar').width(this.$wrapObj.width() + 14);
			}
			if(LamClient.ie6)
			{
				var height = Math.max( $(document.body).outerHeight(true),  $(document.documentElement).outerHeight(true) );
				$('.ui-widget-overlay').css({
					'position' : 'absolute',
					'height' : height
				});
				
				_this.$uiDiglog.css({
					'left':'30%'
				});
				
				// 为了遮盖select标签(可参考mask()的写法)
				// 第二种方案为IE6 下出现遮罩层的时候，把可见的select全隐藏掉了(如果某些select不想被隐藏，可以用css提高visibility的优先级)，移除遮盖层时再显示出来。
				$('select:visible').filter(function(index) {
					return $(this).attr('nohide')!=1;
				}).addClass('lamMaskSelect').css('visibility', 'hidden');
				
				if(_this.flscr)
				{
					$(window).scroll(function (e){
						_this.$uiDiglog.css('top', _this.top + $(window).scrollTop());
					}).trigger('scroll');
				}
			}
	
			this.$uiDiglog.find(".ui-dialog-buttonset button:eq(1)").addClass('LamCancelBtn');
			return false;
		};
		
		LamDialogs.prototype.alert = function (message, callback_ok, title, classname){
			return this.dialog(message, callback_ok, null, title, classname, 'Alert');
		};
	
		LamDialogs.prototype.okAlert = function (message, callback_ok, title, classname){
			return this.dialog(message, callback_ok, null, title, classname, 'OkAlert');
		};
		
		LamDialogs.prototype.confirm = function (message, callback_ok, callback_no, title, classname){
			return this.dialog(message, callback_ok, callback_no||'', title, classname, 'Confirm');
		};
	
		LamDialogs.prototype.prompt = function (message, callback_ok, callback_no, title, classname){
			return this.dialog(message, callback_ok, callback_no||'', title, classname, 'Prompt');
		};
		
		LamDialogs.prototype.toast = function (message, callback_ok, second, cbsecond){
			this.opns = {
				show:{effect:'fade', duration:300},
				hide:{effect:'fade', duration:500}
			};
			this.dialog(message, callback_ok, '', '', '', 'Toast');
			var $wrapObj = this.$wrapObj;
			$wrapObj.parent().addClass('ToastDialog');
			window.setTimeout(function(){
				$wrapObj.next().find('.LamCancelBtn').click();
			}, second || 1500);
			typeof(callback_ok)=='function' && window.setTimeout(function(){callback_ok();}, cbsecond || 2500 );
		};
	}
	LamDialogs._initialized = true;
}

/**
 * LamDialogs()的辅助函数，当提示框中有输入框时，可以利用这个函数来对输入框进行keypress的绑定响应
 */
function lamdialogs_inputkeypress(obj, event, index)
{
	if((event.keyCode || event.which) == 13)	//按了Enter键
	{
		$(obj).parents('.LamDialogsWrapper').next().find('button:eq(' + (index || 0) + ')').click();
	}	
}

/**
 * 数据异步操作类
 *
 * @author 林坤源
 * @version 5.4 最后修改时间 2014年08月30日
 * @link http://www.lamsonphp.com
	
 * 需要依赖的资源：
 *		对象：LamSon, JQuery
 *		函数：
 */
function LamList()
{
	var _this = this;	//让私有方法可以调用公有属性和公有方法
	this.realUrl = '';	//最后一次ajax列表页的url
	this.filter = {
		sort_order : '',
		sort_by : 'DESC'
	};
	this.ajaxenable = true;
	this.separate = '§';
	
	if(typeof(LamList._initialized)=='undefined')
	{
		LamList.prototype.getUrl = function (url){
			url = url || window.location.href;
			return url.split('?').shift() + '?';
		}

		/**
		 * 创建一个可编辑区
		 * @param Object obj 容器对象
		 * @param String|Object json 要传递的数据(内部最终会转成json格式)
		 * @param RegExp regexp正则表达式
		 * @param Stirng url 处理页的网址（不含参数），为空则取当前网址
		 * @param String|Object inputype 输入框的类型，默认为text，如果需要根据下拉菜单面板中点入，就用select§func；如果想直接显示一个下拉菜单，将列表项以json格式传入
		 * @example
		 	<td onclick="lamList_edit(this, 'sortorder§1', /\d+/, '必须为整数', 'action.php')">30</td>
			<td onclick="lamList_edit(this, {0:'sortorder§1', name:'lamson'}, /\d+/, '必须为整数', 'action.php', 'number')">30</td>
			<td onclick="lamList_edit(this, {0:'sortorder§1', name:'lamson'}, /\d+/, '必须为整数', 'action.php', 'select§func')">30</td>
		 	<script>
			var lamList = new LamList();
			function lamList_edit(obj, json, regexp, msg, url, inputype)
			{
				json = typeof(json)=='string' ? {0:json} : json;
				var arr = json[0].split(lamList.separate);
				delete(json[0]);
				json.field = arr[0];
				json.id = arr[1];
				lamList.edit(obj, json, regexp, msg, url, inputype);
			}
			</script>
		 */
		LamList.prototype.edit = function (obj, json, regexp, msg, url, inputype){
			var $obj = $(obj);
			if(obj.firstChild)
			{
				var tag = obj.firstChild.tagName;
				if(typeof(tag) != 'undefined' && (tag == 'INPUT' || tag == 'SELECT' || tag == 'OPTION'))
				{
					return;
				}
			}
			/* 保存原始的内容 */
			var org = $obj.html();
			var val = $obj.text();
			var w = $obj.width() + (obj.tagName == 'A' ? 20 : 0);

			if(typeof(inputype) == 'object')
			{
				var html = '<select>';
				for(var k in inputype)
				{
					html += '<option value="' + k + '" title="' + inputype[k] + '" ' + (inputype[k]==val ? ' selected="selected"' : '') + '>' + inputype[k] + '</option>';	
				}
				html += '</select>';
				$obj.html(html).find('select').change(function(){
					var str = this.value;
					var opnobj = this.options[this.selectedIndex];
					json[json.field] = str;//encodeURIComponent(str);
					delete(json.field);
					$.ajax({
						global : false,
						type : "POST",
						url : U(url || 'ajaxEdit'),
						data : json,
						success : function(data){
							$obj.html( data ? opnobj.innerHTML : org );
					   }
					});
				}).end().mouseleave(function(){
					$(this).find('select').change();
				});
			}else if(typeof(inputype) == 'string' && inputype.indexOf('select') != -1)
			{
				var arr = inputype.split('§');
				typeof(arr[1]) == 'function' && arr[1](obj, json, url, inputype);
			}else
			{
				/* 创建一个输入框 */
				var $txt = $('<input type="' + (inputype || 'text') + '" />');
				/* 隐藏对象中的内容，并将输入框加入到对象中 */
				$obj.html('').append($txt);
				$txt.val( (val == 'N/A') ? '' : val ).width(w-6).trigger('focus')
				/* 编辑区输入事件处理函数 */
				.keyup(function(evt){
					var $obj = $(evt.target);
					if(evt.keyCode == 13)	//按了Enter键
					{
						$obj.trigger('blur');
						return false;
					}
					else if(evt.keyCode == 27)	//按了ESC键
					{
						$obj.parent().html(org);
					}
				})
				
				/* 编辑区失去焦点的处理函数 */
				.blur(function(evt){
					var str = $.trim($txt.val());
					if(regexp && ! regexp.test(str))	//如果有正则表达式的验证原则
					{
						msg = msg || '格式有误';
						if(typeof(LamDialogs)=='object')
						{
							LamDialogs.alert($txt, msg, function(){
								if(LamClient.ie)
								{
									$txt[0].focus();
								}else
								{
									window.setTimeout(function(){$txt[0].focus();}, 100);
								}
							});
						}else
						{
							alert(msg);
							if(LamClient.ie)
							{
								$txt[0].focus();
							}else
							{
								window.setTimeout(function(){$txt[0].focus();}, 100);
							}
						}
						return false;
					}
					json[json.field] = str;//encodeURIComponent(str);
					delete(json.field);
					$.ajax({
						global : false,
						type : "POST",
						url : U(url || 'ajaxEdit'),
						data : json,
						success : function(data){
							$obj.html( data ? str : org );
					   }
					});
				});	
			}
		}
		
		/**
		 * 切换状态
		 * @param Object obj 图片对象，例如 yes.gif 或 no.gif
		 * @param String|Object json 要传递的数据(内部最终会转成json格式，并且自动加一个val成员，值通过自定义属性togglevalue自动获取)
		 * @param Stirng url 处理页的网址（不含参数），为空则取当前网址
		 * @param function callback 回调函数
		 * @example
		  		<img src="images/yes.gif" togglevalue="1" onClick="lamList_toggle(this, 'isshow§1', 'Menu/ajaxEdit')" alt="">
		 		<img src="images/yes.gif" togglevalue="1" onClick="lamList_toggle(this, {0:'isshow§1', name:'lamson'}, 'Menu/ajaxEdit')" alt="">
		 		<script>
				var lamList = new LamList();
				function lamList_toggle(obj, json, url, callback)
				{
					json = typeof(json)=='string' ? {0:json} : json;
					var arr = json[0].split(lamList.separate);
					delete(json[0]);
					json.field = arr[0];
					json.id = arr[1];
					lamList.toggle(obj, json, url, callback);
				}
				</script>
		 * 注意：COM_IMG 目录下要有名为yes.gif和no.gif的图片
		 */
		LamList.prototype.toggle = function (obj, json, url, callback){
			json[json.field] = value = Math.abs( $(obj).attr('togglevalue')-1 );
			delete(json.field);
			$.ajax({
			   global : false,
			   type : "POST",
			   url : U(url || 'ajaxEdit'),
			   data : json,
			   success : function(data){
				 obj.src = COM_IMG + (obj.src.lastIndexOf('yes.gif')==-1 ?  'yes.gif' : 'no.gif');
				 $(obj).attr('togglevalue', value);
				 typeof(callback) == 'function' && callback(obj, value, data);
			   }
			});
		}
		
		/**
		 * ajax加载新数据
		 * @param String para 给传递的参数
		 * @param Function func 数据加载成功后要执行的回调函数
		 * @param String id 要装载数据的容器的id值
		 * @param Stirng url 处理页的网址（不含参数），为空则取当前网址
		 * @param String loadid 装载数据前loading图所在的容器id值
		 */
		LamList.prototype.list = function (para, func, id, url, loadid){
			para = para || '';
			//this.realUrl = this.getUrl(url) + para + this.compileFilter();
			this.realUrl = U(url || 'index', para + this.compileFilter()) ;
			if(this.ajaxenable)
			{
				this.ajax(func, id, loadid);
			}else
			{
				window.location = this.realUrl;
			}
		}
		
		/**
		 * 切换排序方式
		 * @param String sort_order 要排序的字段名
		 * @param String act action标识，例如list
		 * @param Function func 数据加载成功后要执行的回调函数
		 * @param String id 要装载数据的容器的id值
		 * @param Stirng url 处理页的网址（不含参数），为空则取当前网址
		 */
		LamList.prototype.sort = function (sort_order, act, func, id, url){
			this.filter.sort_order = sort_order;
			this.filter.sort_by = this.filter.sort_by == 'DESC' ? 'ASC' : 'DESC';
			if(act)
			{
				this.filter.act = act;	
			}
			this.list('', func, id, url);
		}
		
		/**
		 * ajax底层实现
		 * @param Function func 数据加载成功后要执行的回调函数
		 * @param String id 要装载数据的容器的id值
		 * @param String loadid 装载数据前loading图所在的容器id值
		 */
		LamList.prototype.ajax = function (func, id, loadid){
			id = id || 'listBox';
			loadid = loadid || 'loadImg';
			
			$.ajax({
				global : false,
				beforeSend : function(){$('#' + id).html(''); show_loading($('#' + loadid)); },
				type : "GET",
				url : this.realUrl,
				success : function(data){
					window.setTimeout( function(){
						$('#' + loadid).html('');
						$('#' + id).html(data);
						typeof(func)=='function' ? func() : '';
					}, 1000);
				}
			});
		}
			
		LamList.prototype.compileFilter = function (str){
			str = str || '';
			var args = '';
			for(var i in this.filter)
			{
				if(str.indexOf('&' + i + '=') == -1 && this.filter[i] != '')
				{
					args += '&' + i + '=' + encodeURIComponent(this.filter[i]);
				}
			}
			return args;
		}
			
		LamList._initialized = true;  //静态属性
	}
}

/**
 * 将一个下拉菜单面板绑定到一个输入框对象
 * @param Object obj 输入框对象
 * @param Object pobj 面板所在的父对象
 * @param Object|String content 面板的内容
 * @param Object|String cssjson 面板的样式json格式，或者class名string格式
 * @param Function callback 面板加载显示后要执行的函数
 * @param Function clickback 点击面板的某一列表项后要执行的函数
 */
function selectrler_toinput(obj, pobj, content, cssjson, clickback)
{
	//如果内容是json对象，则自动组装成面板的内容
	if(typeof(content=='object'))
	{
		var html = '<ul id="selectrlerToinputUl">';
		for(var k in content)
		{
			html += '<li data-key="' + k + '" title="' + content[k] + '">' + content[k] + '</li>';	
		}
		html += '</ul>';
	}else
	{
		var html = content;
	}
	$('#selectrlerToinput').remove();
	var $div = $('<div id="selectrlerToinput" style="position:absolute;z-index:1006;background:#FFF;border:#CCC 1px solid;overflow:hidden;"></div>');
	var div = $div[0];
	$div.css({top:$(obj).offset().top + $(obj).height() + 2, left:$(obj).offset().left}).width($(obj).width()).html(html).appendTo(typeof(pobj)=='object' ? pobj : document.body);
	//如果有附加样式
	if(typeof (cssjson) == 'object')
	{
		if(cssjson.opacity)
		{
			div.style.filter = 'alpha(opacity=' + cssjson.opacity * 100 + ')';
			div.style.opacity = cssjson.opacity;
			delete (cssjson.opacity);
		}
		$div.css(cssjson);
	}
	//如果有附加class名
	else if(cssjson)
	{
		$div.addClass(cssjson);	
	}
	//鼠标离开则自动移除
	$div.mouseleave(function(e) {
		$(this).remove();
	});
	//给列表项绑定点击事件
	$('#selectrlerToinputUl li').click(function(){
		obj.value = $(this).data('key');
		if(typeof(clickback) == 'function')
		{
			clickback(obj, $div, this);
		}else
		{
			$div.remove();	
		}
	});
	typeof(callback) == 'function' && callback($div);
}

/**
 * 获取经纬度 
 */
function geolocation(success, fail, noalert, lang)
{
	var _this = this;	//让私有方法可以调用公有属性和公有方法
	this.lang = {		
		"zh-cn":{
			timeout:"连接超时，请重试",
			refuse:"您拒绝了使用位置共享服务，查询已取消",
			cannot:"非常抱歉，我们暂时无法获取您的位置信息"
		},
		"zh-tw":{
			timeout:"連接超時，請重試",
			refuse:"您拒絕了使用位置共享服務，查詢已取消",
			cannot:"非常抱歉，我們暫時無法獲取您的位置信息"
		},
		"en-us":{
			timeout:"Connection timed out, please try again",
			refuse:"You have rejected the use of location sharing service to check canceled",
			cannot:"Sorry, we are unable to get to your location"
		},
		"ko-kr":{
			timeout:"연결 시간 초과, 다시 시도하십시오",
			refuse:"당신은 취소 확인하려면 위치 공유 서비스의 사용을 거부 한",
			cannot:"우리는, 우리는 당신의 위치를 얻을 수 없습니다 죄송하고"
		},
		"km-km":{
			timeout:"តភ្ជាប់បានអស់ពេល, សូមព្យាយាមម្តងទៀត",
			refuse:"អ្នកបានច្រានចោលពីការប្រើប្រាស់សេវាការចែករំលែកទីតាំងដើម្បីពិនិត្យមើលបានលុបចោល",
			cannot:"យើងកំពុងសោកស្តាយដែលយើងមិនអាចទទួលបានទៅទីតាំងរបស់អ្នក"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];
	
	var geol;		
	try {
		if (typeof(navigator.geolocation) == 'undefined')
		{
			geol = google.gears.factory.create('beta.geolocation');
		}
		else
		{
			geol = navigator.geolocation;
		}
	} catch (e) {
		alert(e.message);
	}

	if (geol)
	{
		geol.getCurrentPosition(
			function(position)
			{
				if(typeof(success) == 'function')
				{
					return success(position.coords.longitude, position.coords.latitude);
				}else
				{
					return {lng:position.coords.longitude, lat:position.coords.latitude};
				}
			},
			function(e)
			{
				if(! noalert)
				{
					switch(e.code)
					{
						case e.TIMEOUT:alert(_lang.timeout);break;
						case e.PERMISSION_DENIED:alert(_lang.refuse);	break;
						case e.POSITION_UNAVAILABLE:alert(_lang.cannot);break;
					}
				}
				typeof(fail) == 'function' && fail(e.code);
			}, 
			{timeout:10000}//设置十秒超时
		);	
	}
}

/**
 * @author 林坤源
 * @version 最后修改时间 2013年11月08日
 */
function lamTip(obj, text, thewidth, thecolor)
{
	var _this = this;
	if(typeof(lamTip._initialized)=='undefined')
	{
		function init()
		{
			_this.offsetfromcursorX = 12;
			_this.offsetfromcursorY = 10;
			_this.offsetdivfrompointerX = 10;
			_this.offsetdivfrompointerY = 14;
			//窗口的width	
			_this.tpWinWidth = $('body').width() - 20;
			//窗口的height
			_this.tpWinHeight = $('body').height()-20;
			//图片的最大宽度
			_this.imgMaxWidth = _this.tpWinWidth*0.8;
			//图片的最大高度
			_this.imgMaxHeight = _this.tpWinHeight*0.8;
			
			$('<div id="lamTip" style="max-width:' + _this.imgMaxWidth + 'px;"></div><img id="lamTipPointer" src="' + COM_IMG + 'arrow2.gif">').appendTo(document.body);
			$lamTip = $('#lamTip');
			$lamTip.mouseenter(function(){
				$lamTip.finish().show();
			}).on('click mouseleave', function(){
				$lamTip.add($lamTipPointer).finish().hide();
			});
			$lamTipPointer = $('#lamTipPointer');	
		}
		init();
	}
	
	function positionTip(e)
	{
		var nondefaultpos = false;
		//鼠标的X坐标
		var curX = e.pageX ;
		//鼠标的Y坐标
		var curY = e.pageY;

		//鼠标离窗口右边框的距离
		var rightedge = _this.tpWinWidth - curX - _this.offsetfromcursorX;
		//鼠标离窗口下边框的距离
		var bottomedge = _this.tpWinHeight - curY - _this.offsetfromcursorY;
		
		//X坐标默认与鼠标X坐标相同
		$lamTip.css('left', curX + _this.offsetfromcursorX - _this.offsetdivfrompointerX);
		$lamTipPointer.css('left', curX + _this.offsetfromcursorX);
		//如果右边被窗口档住了，则往左移直到右边全显示
		if(rightedge<$lamTip.width())
		{
			$lamTip.css('left', curX - ($lamTip.width() - rightedge));
			nondefaultpos = true;
		}
		//如果左边被窗口挡住了，则往右移，直到左边全显示
		if(parseInt($lamTip.css('left'))<0)
		{
			$lamTip.css('left', _this.offsetdivfrompointerX);
		}

		//Y坐标默认与鼠标Y坐标相同
		$lamTip.css('top', curY + _this.offsetfromcursorY + _this.offsetdivfrompointerY);
		$lamTipPointer.css('top', curY + _this.offsetfromcursorY);
		//如果下边被窗口档住了，则往上移直到上边全显示
		if(bottomedge<$lamTip.height())
		{
			$lamTip.css('top', curY - ($lamTip.height() - bottomedge));
			nondefaultpos = true;
		}
		//如果上边被窗口挡住了，则往下移，直到上边全显示
		if(parseInt($lamTip.css('top')<0))
		{
			$lamTip.css('top', _this.offsetdivfrompointerY);
		}

		$lamTip.finish().fadeIn(500);
		
		if(! nondefaultpos){$lamTipPointer.show();}
		else{$lamTipPointer.hide();}
	}
	
	lamTip._initialized = true;
	
	if(thewidth){$lamTip.width(thewidth);}
	if(thecolor){$lamTip.css({backgroundColor:thecolor});}
	
	positionTip(window.event || arguments.callee.caller.arguments[0]);
	
	$lamTip.html(text || $(obj).data('lamtipcon'));

	return false;
}

function fitimg(obj)
{
	var w = $('body').width() * 0.8;
	var h = $('body').height() * 0.8;
	var $obj = $(obj);
	
	if($obj.width() > w)	
	{
		$obj.height($obj.height() * w / $obj.width());
		$obj.width(w);
	}
	
	if($obj.height() > h)	
	{
		$obj.width($obj.width() * h / $obj.height());
		$obj.height(h);
	}
}

//一些特殊字符串的实体格式化处理
function text_trans(str)
{
	if(!str){return '';}
	var ereg = /\<|\>|\"|\'/g;
	str = $.trim(str).replace(ereg,  
		function(mathstr){  
			switch(mathstr){  
				case "<":  
					return "&lt;";  
					break;  
				case ">":  
					return "&gt;";  
					break;  
				case "\"":  
					return "&quot;";  
					break;  
				case "'":  
					return "&#39;";  
					break; 
				default :  
					break;  
			}  
		}  
	);
	return str; 
}

/**
 * 过滤url中的空参数，以符合pathinfo的访问规则
 *
 * @param bool keepudf 是否保留undefined值
 * @return string 处理后的网址
 */
function trim_urlpara(str, keepudf)
{
	if(! keepudf)
	{
		str = str.replace(/[^&]+=undefined/g, '');
	}
	str = str.replace(/[^&]+=&/g, '&').replace(/&{2,}/g, '&').replace(/&[^&]+=$/, '');
	return str;
}

/**
 * 默认的单选按钮点击事件 default beauty radiobox click
 * @example
 	<code>
		<td class="btyWapper"><div class="btyRadioBox" data-click="setmode(this)"><label><input type="radio" name="mode" value="0" />操作模式</label><label><input type="radio" name="mode" value="1" />选择模式</label></div></td>
	</code>
 */
function dfbrb_click(index, element, bnum, offset)
{
	bnum = bnum || 2;
	offset = offset || 1;
	var $Labels = $(element).children('label');
	$Labels.width( parseInt((($(element).parents('.btyWapper').width() || $('#btyWapperRef').width())-bnum) / $Labels.length)-offset )
	.find(':radio').click(function (){
		var $label = $(this).parent('label');
		$label.addClass('current').siblings().removeClass('current');
		var extendfun = $label.parent().data('click');
		extendfun && eval(extendfun);
	});	
}

/**
 * 默认的复选框点击事件 default beauty checkbox click
 */
function dfbcb_click(obj)
{
	if(obj.checked)
	{
		$(obj).parent().addClass('current');
	}else
	{
		$(obj).parent().removeClass('current');	
	}	
}

/**
 * ajax添加新地址
 * @example
 	<code>
		<a href="User/addAddress.html" onClick="return ajax_addr(this, addr_backcall)">添加新地址</a>
	</code>
 */
function ajax_addr(obj, backcall, lang)
{
	var _this = this;	//让私有方法可以调用公有属性和公有方法
	this.lang = {		
		"zh-cn":{
			naddr:"添加新地址"
		},
		"zh-tw":{
			naddr:"添加新地址"
		},
		"en-us":{
			naddr:"Add a new address"
		},
		"ko-kr":{
			naddr:"새 주소 추가"
		},
		"km-km":{
			naddr:"បន្ថែមអាសយដ្ឋានថ្មីមួយ"
		}
	};
	var _lang = this.lang[typeof(lang)!='undefined' ? lang : (typeof (LamSon) == 'object' ? LamSon.lang : 'zh-cn')];
	
	lamDialogs_5 = typeof(lamDialogs_5) == 'undefined' ? new LamDialogs('LamDialogsWrapperAddr') : lamDialogs_5;
	$.get(obj.href, function(data){
		lamDialogs_5.confirm(data, function(){
			lamDialogs_5.autoClose = false;
			var $form = $('#LamDialogsWrapperAddr form');
			var res = $form[0].onsubmit();
			if(res)
			{
				$.post($form[0].action, $form.serialize(), function(res){
					if(res.status)
					{
						typeof(backcall) == 'function'	&& backcall($form[0], res);
					}else
					{
						alert(res.info);	
					}
				}, 'json');
			}
		}, '', _lang.naddr, 'LamDialogsWrapperAddr');
		
		$('.btyRadioBox').each(function(index, element) {
			dfbrb_click(index, element);
		});
		$('[name="isdefault"][value="1"]').click();	
	});
	return false;	
}

/**
 * Create a cookie with the given key and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String key The key of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given key.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String key The key of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function (key, value, options) {

    // key and value given, set cookie...
    if (arguments.length > 1 && (value === null || typeof value !== "object")) {
        options = jQuery.extend({}, options);

        if (value === null) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? String(value) : encodeURIComponent(String(value)),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};