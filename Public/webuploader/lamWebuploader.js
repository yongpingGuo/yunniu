/**
 * 基于百度的WebUploader
 * @author 林坤源
 * @version 0.1.5 最后修改时间 2016年12月05日
 * @param {String} warpid 最外层容器的id
 * @param {Object} config 配置项（json格式）
 * @param {String} method 要调用的方法名，目前暂时有imgUploader和fileUploader
 */
function lamWebuploader(warpid, config, method)
{
	var obj = new LamWebuploader(warpid);
	obj[method || 'imgUploader'](config);
	return obj;
}

/**
 * 自定义多图片上传
 */
function LamWebuploader(warpid)
{
	var $LamWu = $('#lamWebuploader');
	// 路径
	var BASE_URL = $LamWu.attr('src').split('lamWebuploader.js')[0];
	// 优化retina, 在retina下这个值是2
    var ratio = window.devicePixelRatio || 1,
		// 缩略图大小
        thumbnailWidth = 300 * ratio,
        thumbnailHeight = 300 * ratio;
	
	// 默认大示意图
	var src = BASE_URL + 'images/thumbview.gif';
		
	// 最外层容器
	this.$wrap = $('#' + warpid);
	
	// 队列容器
	this.$queue = this.$wrap.find('.queueList');
	if(! this.$queue.length)
	{
		this.$queue = $('<div class="queueList"></div>').appendTo( this.$wrap );
	}
	
	// 没选择文件之前的内容
	this.$placeHolder = this.$wrap.find('.placeholder');
	if(! this.$placeHolder.length)
	{
		this.$placeHolder = $('<div class="placeholder"><div class="filePicker lamInputFile2"></div><p></p></div>').appendTo( this.$queue );
	}
	
	// 文件列表
	this.$fileList = this.$queue.find('.fileList');
	if(! this.$fileList.length)
	{
		this.$fileList = $('<ul class="fileList"></ul>').appendTo( this.$queue );
	}
	
	// 状态栏，包括进度和控制按钮
	this.$statusBar = this.$wrap.find('.statusBar');
	if(! this.$statusBar.length)
	{
		this.$statusBar = $('<div class="statusBar" style="display:none;"><ins class="progress"><span class="text">0%</span><span class="percentage"></span></ins><div class="info"></div><div class="btns"><div class="filePicker lamInputFile"></div><div class="uploadBtn">开始上传</div></div></div>').appendTo( this.$wrap );
	}
	
	// 主选择按钮
	this.$lamInputFile = this.$wrap.find('.lamInputFile');
	// 副选择按钮
	this.$lamInputFile2 = this.$wrap.find('.lamInputFile2');
	
	// 文件总体选择信息。
	this.$info = this.$wrap.find('.info');
	
	// 上传按钮
	this.$upload = this.$wrap.find('.uploadBtn');

	// 总体进度条
	this.$progress = this.$statusBar.find('.progress').hide(),
	
	// 添加的文件数量
	this.fileCount = 0;
		
	// 添加的文件总大小
	this.fileSize = 0;
	
	// 已删除的文件数量（只算已上传后又删除的）
	this.fileDeled = 0;
	
	// 可能有pedding, ready, uploading, confirm, done.
	// 状态 pending:队列中   uploading:上传中   paused:暂停   done:完成
	this.state = 'pedding';
	
	// 所有文件的进度信息，key为file id
	this.percentages = {};
	

	if(typeof(LamWebuploader._initialized)=='undefined')
	{
		// 浏览器是否支持css3中的旋转
		var supportTransition = (function(){
            var s = document.createElement('p').style,
                r = 'transition' in s ||
                      'WebkitTransition' in s ||
                      'MozTransition' in s ||
                      'msTransition' in s ||
                      'OTransition' in s;
            s = null;
            return r;
        })();
		
		if ( !WebUploader.Uploader.support() )
		{
			alert( 'Web Uploader 不支持您的浏览器！如果你使用的是IE浏览器，请尝试升级 flash 播放器');
			throw new Error( 'WebUploader does not support the browser you are using.' );
		}

		LamWebuploader.prototype.init = function(cnf, config){
			
			var _this = this;
			
			this.config = $.extend({
					swf:BASE_URL + 'Uploader.swf', // flash文件
					pick: {
						id:this.$lamInputFile2[0],
						innerHTML: '点击选择'
					},
					dnd: this.$queue[0], // 指定Drag And Drop拖拽的容器，如果不指定，则不启动
					paste: document.body,
					disableGlobalDnd: true,
					chunked: true,
					/*fileNumLimit: 20,
					fileSizeLimit: 200 * 1024 * 1024,    // 200 M
					fileSingleSizeLimit: 5 * 1024 * 1024,    // 5 M*/
                    fileNumLimit: 50,
                    fileSizeLimit: 500 * 1024 * 1024,    // 200 M
                    fileSingleSizeLimit: 5 * 1024 * 1024,    // 5 M
					rootpath : '', // 服务器的文件存放总路径
					bigImg : '.bigImg', // 大图的选择器规则，例如.class 或者 #id之类的
					rawId : '', // #id。隐藏域的JQ标识。如果有设置值，那么文件上传到服务器后返回的响应信息将会被累加到这个隐藏域里
					rawSign : ',' // 分隔符
				}, cnf || {}, config || {}
			);
			
			// 将文件存放总路径也添加到post数据中
			this.config.formData = $.extend({}, this.config.formData || {}, {_rootpath : this.config.rootpath});
			
			// 高清图		
			/*if(this.$wrap.hasClass('lamThumbWrap'))
			{
				this.$bigImg = this.$wrap.find(this.config.bigImg);
				if(! this.$bigImg.length)
				{
					this.$bigImg = $('<img class="' + this.config.bigImg.substr(1) + '" src="' + src + '">').prependTo(this.$wrap);
				}
			}*/
			
			// console.log(this.config);
			this.$placeHolder.find('em').html(this.config.fileNumLimit);
			
			// 实例化
			this.uploader = WebUploader.create(this.config);
			// 添加“添加文件”的按钮，
			this.uploader.addButton({
				id: this.$lamInputFile[0],
				innerHTML: '继续添加'
			});
			
			this.uploader.on( 'all', function( type ) {
				switch( type )
				{
					case 'uploadFinished':
						_this.setState( 'confirm' );
					break;
		
					case 'startUpload':
						_this.setState( 'uploading' );
					break;
		
					case 'stopUpload':
						_this.setState( 'paused' );
					break;
				}
			});
			
			this.$upload.on('click', function() {
				if ( $(this).hasClass( 'disabled' ) ) {
					return false;
				}
		
				if ( _this.state === 'ready' ) {
					_this.uploader.upload();
				} else if ( _this.state === 'paused' ) {
					_this.uploader.upload();
				} else if ( _this.state === 'uploading' ) {
					_this.uploader.stop();
				}
			});
			
			this.$info.on( 'click', '.retry', function() {
				_this.uploader.retry();
			} );
		
			this.$info.on( 'click', '.ignore', function() {
				
			} );
		
			this.$upload.addClass( 'state-' + this.state );
			this.updateTotalProgress();
			
			/**
			 * 当有文件被添加进队列时触发
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 * @param {Object} file File对象
			 */
			this.uploader.on('fileQueued', function(file){
				_this.addFile( file );
				_this.setState( 'ready' );
				_this.updateTotalProgress();
				
				// 对外扩展接口
				typeof(_this.config.fileQueued) == 'function' && _this.config.fileQueued(file, _this);
			});
			
			
			/**
			 * 当有文件被从队列中移除时触发
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 * @param {Object} file 数组，内容为原始File(lib/File）对象
			 */
			this.uploader.on('fileDequeued', function(file){
				var $li = $('#'+file.id);
				// 向file对象添加一个自定义属性，记录它所对应的服务器上的文件名
				file._raw = $li.data('raw');
				// 已上传的文件删除前最好先确认一下
				if(file._raw)
				{
                    // 判断是否允许删除
                    $.ajax({
                        url:"/index.php/allAgent/Systemset/if_can_del",
                        dataType:'json',
                        data:{"raw":file._raw},
                        type:'POST',
                        success:function(data){
                            if (data==1) {
                                // 有分类在使用该图标，不允许删除
                                layer.msg('有菜品分类在使用该图标，暂时不能删除');
                            }else{
                                layer.confirm('将同时删除服务器上的文件，此操作不可撤消，确定要删除不？', {icon:3}, function(index){
                                    rm();
                                    layer.close(index);
                                });
                            }
                        }
                    });

                    /*layer.confirm('将同时删除服务器上的文件，此操作不可撤消，确定要删除不？', {icon:3}, function(index){
                        rm();
                        layer.close(index);
                    });*/
				}
				else
				{
					rm();	
				}
				
				function rm(index)
				{
					file = _this.removeFile(file, $li);
				
					// 对外扩展接口
					typeof(_this.config.fileDequeued) == 'function' && _this.config.fileDequeued(file, _this);
				}
			});
			
			/**
			 * 文件上传过程中创建进度条实时显示
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 * @param {Object} file File对象
			 * @param {Number} percentage 上传进度
			 */
			this.uploader.on('uploadProgress', function(file, percentage){
				var $li = $('#'+file.id),
				$percent = $li.find('.progress span');
				
				$percent.css( 'width', percentage * 100 + '%' );
				_this.percentages[ file.id ][ 1 ] = percentage;
				_this.updateTotalProgress();
				
				// 对外扩展接口
				typeof(_this.config.uploadProgress) == 'function' && _this.config.uploadProgress(file, percentage, _this);
			});
			
			/**
			 * 单个文件上传成功时触发
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 * @param {Object} file File对象
			 * @param {Object} response 服务端返回的数据
			 */
			this.uploader.on('uploadSuccess', function(file, response){
				if(_this.config.rawId)
				{
					$('#' + file.id).data('raw', response._raw);
					$(_this.config.rawId)[0].value += _this.config.rawSign + response._raw;
				}
				typeof(_this.config.uploadSuccess) == 'function' && _this.config.uploadSuccess(file, response, _this);
			});

			/**
			 * 单个文件上传失败时触发
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 * @param {Object} file File对象
			 * @param {String} reason 出错的code
			 */
			this.uploader.on('uploadError', function(file, reason){
				typeof(_this.config.uploadError) == 'function' && _this.config.uploadError(file, reason, _this);
			});
			
			/**
			 * 单个文件上传完成时触发（不管成功或者失败）
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 * @param {Object} file File对象
			 */
			this.uploader.on('uploadComplete', function(file){
				typeof(_this.config.uploadComplete) == 'function' && _this.config.uploadComplete(file, _this);
			});
			
			/**
			 * 当所有文件上传结束时触发
			 * @author 林坤源
			 * @version 0.1.5 最后修改时间 2016年12月05日
			 */
			this.uploader.on('uploadFinished', function(){
				typeof(_this.config.uploadFinished) == 'function' && _this.config.uploadFinished(_this);
			});
			
			if(this.config.rawId)
			{
				var val = $(this.config.rawId).val();
				// 如果表单处于编辑模式
				if(val)
				{
					// 构建file，并一一显示
					val = val.replace(new RegExp('^' + this.config.rawSign + '|' + this.config.rawSign + '$', 'ig'), '').split(this.config.rawSign);
					for(var vk in val)
					{
						var ext = val[vk].replace(/.+(\.[^.]+)$/ig, "$1");
						var type = ext.match(/\.(gif|jpg|jpeg|bmp|png)$/i);
						this.addFile({
							id : 'WUED_FILE_' + vk,
							name : val[vk],
							size : 0,
							ext : ext,
							rotation : 0,
							_raw : 	val[vk],
							type : type ? 'image/' + type[1]: 'application/octet-stream',
							status : 'complete',
							getStatus : function(){return this.status;},
							on : function(status, callback){callback('complete', 'queued');},
							setStatus : function(s){this.status = s;}
						});
					}
				}
			}
		}
		
		/**
		 * 通用图片上传的对外调用接口
		 */
		LamWebuploader.prototype.imgUploader = function(config) {
			//console.log('imgUploader');
			this.$placeHolder.find('p').html('或将照片拖到这里，单次最多可选<em></em>张');
			this.init({
				accept : {
					title: 'Images',
					extensions: 'jpg,jpeg,png',
					mimeTypes: 'image/jpg,/image/jpeg,image/png'
				},
				unit : '张',
				fname : '图片'
			}, config);
		}
		
		/**
		 * 通用文件上传的对外调用接口
		 * 
		 */
		LamWebuploader.prototype.fileUploader = function(config) {
			//console.log('fileUploader');
			this.$placeHolder.find('p').html('或将文件拖到这里，单次最多可选<em></em>个，只支持后缀名为：“jpg,jpeg,png”的图片');
			this.init({
				unit : '个',
				fname : '文件'
			},config);
		}

		// 设置状态	
		LamWebuploader.prototype.setState = function(val) {
			var file, stats;
	
			if ( val === this.state ) {
				return;
			}
	
			this.$upload.removeClass( 'state-' + this.state );
			this.$upload.addClass( 'state-' + val );
			this.state = val;

			switch ( this.state ) {
				case 'pedding':
					this.$placeHolder.removeClass( 'element-invisible' );
					this.$lamInputFile2.removeClass( 'element-invisible');
					this.$queue.removeClass('filled');
					this.$fileList.hide();
					this.$statusBar.addClass( 'element-invisible' );
					this.uploader.refresh();
					break;
	
				case 'ready':
					this.$placeHolder.addClass( 'element-invisible' );
					this.$lamInputFile2.removeClass( 'element-invisible');
					this.$queue.addClass('filled');
					this.$fileList.show();
					this.$statusBar.removeClass('element-invisible');
					this.$upload.text( '开始上传' ).removeClass( 'disabled' );
					this.uploader.refresh();
					break;
	
				case 'uploading':
					this.$lamInputFile2.addClass( 'element-invisible' );
					this.$progress.show();
					this.$upload.text( '暂停上传' );
					break;
	
				case 'paused':
					this.$progress.show();
					this.$upload.text( '继续上传' );
					break;
	
				case 'confirm':
					this.$progress.hide();
					this.$upload.text( '开始上传' ).addClass( 'disabled' );
	
					stats = this.uploader.getStats();
					if ( stats.successNum && !stats.uploadFailNum ) {
						this.setState( 'finish' );
						return;
					}
					break;
				case 'finish':
					stats = this.uploader.getStats();
					if ( stats.successNum ) {
						//alert('上传成功，请继续点击“提交”按钮');
                        layer.tips('上传成功，请继续点击“提交”按钮','#sbm');
					} else {
						// 没有成功的图片，重设
						this.state = 'done';
						location.reload();
					}
					break;
			}
	
			this.updateStatus();
		}
		
		/**
		 * 当有文件添加进来时执行，负责view的创建
		 */
		LamWebuploader.prototype.addFile = function (file) {
			var _this = this;
			
			this.fileCount++;
			this.fileSize += file.size;
			
			if ( this.fileCount === 1 )
			{
				this.$placeHolder.addClass( 'element-invisible' );
				this.$statusBar.show();
			}
			
			var $li = $( '<li id="' + file.id + '">' +
					'<p class="title" title="' + file.name + '">' + file.name + '</p>' +
					'<p class="imgWrap"></p>'+
					'<p class="progress"><span></span></p>' +
					'</li>' ),
			
				$btns = $('<div class="file-panel">' +
					'<span class="cancel">删除</span>' +
					'<span class="rotateRight">向右旋转</span>' +
					'<span class="rotateLeft">向左旋转</span></div>').appendTo( $li ),
					
				$prgress = $li.find('p.progress span'),
				$wrap = $li.find( 'p.imgWrap' ),
				$info = $('<p class="error"></p>'),
			
				showError = function( code ) {
					switch( code ) {
						case 'exceed_size':
							text = '文件大小超出';
							break;
			
						case 'interrupt':
							text = '上传暂停';
							break;
			
						default:
							text = '上传失败，请重试';
							break;
					}
			
					$info.text( text ).appendTo( $li );
				},
				
				// 添加缩略图
				wrapImg = function( error, src ) {
					if ( error ) {
						$wrap.text( '不能预览' );
						return;
					}
			
					//var img = $('<img src="'+src+'">');
					//$wrap.empty().append( img );
					$wrap.html('<img src="' + src + '">');
					$wrap.find('img').click(function(){
						try{_this.$bigImg.attr('src', this.src);}catch(e){}
					}).click();
				};
		
			if ( file.getStatus() === 'invalid' )
			{
				showError( file.statusText );
			} else
			{
				// @todo lazyload
				$wrap.text( '预览中' );
				
				//console.log(file);
				
				// 如果是图片，就生成缩略图
				if(file.type.indexOf('image/')!=-1)
				{
					if(file.source)
					{
						this.uploader.makeThumb( file, wrapImg, thumbnailWidth, thumbnailHeight );	
					}
					else
					{
						wrapImg('', this.config.rootpath + '/' + file._raw);
					}
				}				
				else
				{
					wrapImg('', BASE_URL + 'images/' + file.ext + '.jpg');
				}
			
				this.percentages[ file.id ] = [ file.size, 0 ];
				file.rotation = 0;
			}
		
			file.on('statuschange', function( cur, prev ) {
				if ( prev === 'progress' ) {
					$prgress.hide().width(0);
				} else if ( prev === 'queued' ) {
					//$li.off( 'mouseenter mouseleave' );
					//$btns.remove();
					$btns.children(':gt(0)').remove();
				}
		
				// 成功
				if ( cur === 'error' || cur === 'invalid' ) {
					//console.log( file.statusText );
					showError( file.statusText );
					_this.percentages[ file.id ][ 1 ] = 1;
				} else if ( cur === 'interrupt' ) {
					showError( 'interrupt' );
				} else if ( cur === 'queued' ) {
					_this.percentages[ file.id ][ 1 ] = 0;
				} else if ( cur === 'progress' ) {
					$info.remove();
					$prgress.css('display', 'block');
				} else if ( cur === 'complete' ) {
					$li.append( '<span class="success"></span>' );
				}
		
				$li.removeClass( 'state-' + prev ).addClass( 'state-' + cur );
			});
		
			$li.on( 'mouseenter', function() {
				$btns.stop().animate({height: 30});
			});
		
			$li.on( 'mouseleave', function() {
				$btns.stop().animate({height: 0});
			});
		
			$btns.on( 'click', 'span', function() {
				var index = $(this).index(),
					deg;
		
				switch ( index ) {
					case 0:
						_this.uploader.removeFile( file );
						return;
		
					case 1:
						file.rotation += 90;
						break;
		
					case 2:
						file.rotation -= 90;
						break;
				}
		
				if ( supportTransition ) {
					deg = 'rotate(' + file.rotation + 'deg)';
					$wrap.css({
						'-webkit-transform': deg,
						'-mos-transform': deg,
						'-o-transform': deg,
						'transform': deg
					});
				} else {
					$wrap.css( 'filter', 'progid:DXImageTransform.Microsoft.BasicImage(rotation='+ (~~((file.rotation/90)%4 + 4)%4) +')');
				}
			});
		
			$li.appendTo( this.$fileList );
			
			file._raw && $li.data('raw', file._raw);
		}
		
		/**
		 * 负责view的销毁
		 */
		LamWebuploader.prototype.removeFile = function (file, $li) {
			this.fileCount--;
			this.fileSize -= file.size;

			if ( !this.fileCount ) {
				this.setState( 'pedding' );
			}
			
			delete this.percentages[ file.id ];
			// 统计已删除的（只算已上传的）
			if(file._raw)
			{
				this.fileDeled++;
				// ajax请求服务端（请自己编写服务端的业务逻辑，例如删除文件之类的）
				$.post(this.config.delServer, $.extend({raw:file._raw}, this.config.formData || {}));
			}
			
			this.updateTotalProgress();
			
			$li.off().find('.file-panel').off().end().remove();
			
			var $fst = this.$fileList.children(":eq(0)"); // fileList文件队列里的第一个文件
			// 调用大示意图
			try{
				this.$bigImg.attr('src', $fst.length ? $fst.find('img').attr('src') : src);
			}catch(e){}
			
			this.updateStatus();
			
			this.updateTotalProgress();
			
			if(this.config.rawId)
			{
				var $rawObj = $(this.config.rawId);
				$rawObj.val( $rawObj.val().replace(this.config.rawSign + file._raw, '') );
			}
			
			return file;
		}
		
		/**
		 * 更新进度栏
		 */
		LamWebuploader.prototype.updateTotalProgress = function () {
			var loaded = 0,
				total = 0,
				spans = this.$progress.children(),
				percent;
			$.each( this.percentages, function( k, v ) {
				total += v[ 0 ];
				loaded += v[ 0 ] * v[ 1 ];
			} );
	
			percent = total ? loaded / total : 0;
			
	
			spans.eq( 0 ).text( Math.round( percent * 100 ) + '%' );
			spans.eq( 1 ).css( 'width', Math.round( percent * 100 ) + '%' );
			this.updateStatus();
		}
	
		/**
		 * 更新上传状态
		 */
		LamWebuploader.prototype.updateStatus = function ()	{
			/*var text = '', stats;
	
			if ( this.state === 'ready' ) {
				text = '选中' + this.fileCount + this.config.unit + this.config.fname + '，共' +
						WebUploader.formatSize( this.fileSize ) + '。';
			} else if ( this.state === 'confirm' ) {
				stats = this.uploader.getStats();
				if ( stats.uploadFailNum ) {
					text = '已成功上传' + stats.successNum+ '张照片至XX相册，'+
						stats.uploadFailNum + '张照片上传失败，<a class="retry" href="#">重新上传</a>失败图片或<a class="ignore" href="#">忽略</a>'
				}
	
			} else {
				stats = this.uploader.getStats();
				text = '共' + this.fileCount + this.config.unit + '（' +
						WebUploader.formatSize( this.fileSize )  +
						'），已上传' + (stats.successNum - this.fileDeled) + this.config.unit;
	
				if ( stats.uploadFailNum ) {
					text += '，失败' + stats.uploadFailNum + this.config.unit;
				}
			}
	
			this.$info.html( text );*/
			//console.log(this);
		}
		
		/**
		 * 调整按钮位置
		 */
		LamWebuploader.prototype.refresh = function (time)	{
			var _this = this;
			window.setTimeout(function(){_this.uploader.refresh();}, time || 500);	
		}
		
		LamWebuploader._initialized = true;  //静态属性
	}

	return this;
}
