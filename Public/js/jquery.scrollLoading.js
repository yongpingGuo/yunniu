/*!
 * jquery.scrollLoading.js  
*/
(function($) {
	$.fn.scrollLoading = function(options) {
		var defaults = {
			attr: "data-url",
			container: $(window),
			callback: $.noop,
			preloadHeight:0
		};
		var params = $.extend({}, defaults, options || {});
		params.cache = [];
		$(this).each(function() {
			var node = this.nodeName.toLowerCase(), url = $(this).attr(params["attr"]);
			 
			//����
			var data = {
				obj: $(this),
				tag: node,
				url: url
			};
			params.cache.push(data);
		});
		
		var callback = function(call) {
			if ($.isFunction(params.callback)) {
				params.callback.call(call.get(0));
			}
		};
		//��̬��ʾ����
		var loading = function() {
			var preloadHeight = params.preloadHeight ;
			var contHeight = params.container.height();
			if ($(window).get(0) === window) {
				contop = $(window).scrollTop();
			} else {
				contop = params.container.offset().top;
			}		
 
			var flag = true ;
			$.each(params.cache, function(i, data) {
				var o = data.obj, tag = data.tag, url = data.url, post, posb;

				if (o) {
					
					post = o.offset().top - contop, post + o.height();
					
					if (o.is(':visible') && (post >= 0 && post-preloadHeight < contHeight) || (posb > 0 && posb <= contHeight)) {
						 
						if (url) {
							//�������������
							if (tag === "img") {
								//ͼƬ���ı�src
								callback(o.attr("src", url));		
							} else {
								o.load(url, {}, function() {
									callback(o);
								});
							}		
						} else {
							// �޵�ַ��ֱ�Ӵ����ص�
							callback(o);
						}
						data.obj = null;	
					}else{
						flag=false;
					}
				}
			});	

			if(flag){
				params.container.unbind("scroll", loading);
			}
 
		};
		
		//�¼�����
		//������ϼ�ִ��
		loading();
		//����ִ��
		params.container.bind("scroll", loading);
	};
})(jQuery);