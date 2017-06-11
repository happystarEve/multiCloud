function Dialog(config){
	this.init.apply(this,arguments);
	this.boolConfirm=true;
}
Dialog.prototype={
	init:function(config){
		config=config || {};
		var k,defaults={
			target:{},
			width:640,
			height:undefined,
			mask:true,
			zIndex:101,
			showYesBtn:true,
			showNoBtn:true,
			yesBtnText:'确定',
			noBtnText:'取消',
			yesFn:null,
			noFn:null,
			title:'信息提示',
			header:false,       //header为true,则为另一样式头部。
			minimization:false //Minimization为true，则有最小化、最大化
		}
		this.options={};
		for(k in defaults){
			// 检查自定义参数，若没有则使用默认参数
			this.options[k] = config[k] !== undefined ? config[k] : defaults[k];
		}
	},
	createDialog:function(){
		var dialogBox = document.createElement('div'),header,footer,
		yesBtn='<input type="button" value="'+this.options.yesBtnText+'" class="btnSub">',
		noBtn='<input type="button" value="'+this.options.noBtnText+'" class="btnCancel">';
		dialogBox.className = "DialogBox";
		header='<div class="dia_hd"><a href="#;" title="关闭窗口" class="close_btn">X</a>' + this.options.title + '</div>';
		footer='<div class="dia_ft">'+noBtn+yesBtn+'</div>';

		if((!this.options.header)&&(!this.options.minimization)){
			header='<div class="dia_hd"><a href="javascript:void(0);" title="关闭窗口" class="close_btn">X</a>' + this.options.title + '</div>';
		}else if((this.options.header)&&(!this.options.minimization)){
			header='<div class="dia_hd_1"><a href="javascript:void(0);" title="关闭窗口" class="close_btn">X</a>' + this.options.title + '</div>';
		}else if((!this.options.header)&&(this.options.minimization)){
			header='<div class="dia_hd"><a href="javascript:void(0);" title="关闭窗口" class="close_btn">X</a><a href="#;" title="最小化窗口" class="min_btn"></a><a href="#;" title="还原窗口" class="max_btn"></a>' + this.options.title + '</div>';
		}else{
			header='<div class="dia_hd_1"><a href="javascript:void(0);" title="关闭窗口" class="close_btn">X</a><a href="#;" title="最小化窗口" class="min_btn"></a><a href="#;" title="还原窗口" class="max_btn"></a>' + this.options.title + '</div>';
		}

		if(!this.options.showYesBtn){
			yesBtn='';
		}
		if(!this.options.showNoBtn){
			noBtn='';
		}
		if(!this.options.title===''){
			header='';
		}
		if(!this.options.showYesBtn && !this.options.showNoBtn){
			footer='';
		}
		dialogBox.innerHTML = header +'<div class="dialogCon"></div>'+footer;
		dialogBox.id='DialogBox_'+$(this.options.target)[0].id;
		document.body.appendChild(dialogBox);
		this.dialogBox = dialogBox = $(dialogBox);
		var This=this;
		this.dialogBox.find('.close_btn').bind('click',function(){
			This.close();
		});
		this.dialogBox.find('.min_btn').bind('click',function(){
			This.minimize();
		});
		this.dialogBox.find('.max_btn').bind('click',function(){
			This.maximize();
		});
		$(".close_btn").mousedown(function(){
			$(this).addClass("active");
		});
		$(".close_btn").mouseup(function(){
			$(this).removeClass("active");
		});
		$(".min_btn").mousedown(function(){
			$(this).addClass("active");
		});
		$(".min_btn").mouseup(function(){
			$(this).removeClass("active");
		});
		$(".max_btn").mousedown(function(){
			$(this).addClass("active");
		});
		$(".max_btn").mouseup(function(){
			$(this).removeClass("active");
		});
		return this;
	},
	show:function(){
		var btnSub,btnCancel,This=this;
		if(!this.boxHeight){
			//console.log(this.options.target)
			this.boxHeight=$(this.options.target).outerHeight()+70;
		}
		var h=this.options.height || this.boxHeight,
			w=this.options.width;
		this.showMask();
		if($('#DialogBox_'+$(this.options.target)[0].id).length==0){
			this.createDialog();
		}else{
			this.dialogBox=$('#DialogBox_'+$(this.options.target)[0].id);
		}
		$(this.options.target).show();
		this.dialogBox.find('.dialogCon').append($(this.options.target));
		var xheight=$(window).height(),
			l=Math.round(($(document).width()-w)/2),
			t=Math.round((xheight-h)/2);
		this.dialogBox.css({
			visibility: 'visible',left:l,top:t,width:w,height:h,zIndex:this.options.zIndex,
		})
		btnSub=this.dialogBox.find('.btnSub');
		btnCancel=this.dialogBox.find('.btnCancel');
		// btnSub.unbind('click');
		btnCancel.unbind('click');
		// btnSub.bind('click',function(){
		// 	This.confirm();
		// })
		btnCancel.bind('click',function(){
			This.close();
		})
		this.dialogBox.fadeIn();
	},
	close:function(){
		this.dialogBox.fadeOut();
		this.hideMask();
		if(typeof this.noFn==='function'){
			this.options.noFn.call(this);
		}
	},
	minimize:function(){
		var xheight=$(window).height()-41;
		this.dialogBox.css({left:0,top:xheight,height:41});
	},
	maximize:function(){
		var xheight=$(window).height(),
			w=this.options.width,
			h=this.options.height || this.boxHeight,
			l=Math.round(($(document).width()-w)/2),
			t=Math.round((xheight-h)/2);		
			this.dialogBox.css({left:l,top:t,height:h});
	},
	confirm:function(){
		if(typeof this.noFn==='function'){
			this.options.yesFn.call(this);
		}
		if(this.boolConfirm){
			this.dialogBox.fadeOut();
			this.hideMask();
		}
	},
	showMask:function(){
		if(!this.options.mask) return false;
		var mask=$('#mask');
		if(mask.length===0){
			var div = document.createElement('div');
			var xheight = $(document).height();
			div.id = 'mask';
			document.body.appendChild(div);
			mask = $(div);
			mask.css({
				height:xheight+'px',opacity:0.3,position:'fixed',background:'#000000',width:'100%',zIndex:this.options.zIndex-1
			});
		}
		mask.show();
		return this;
	},
	hideMask:function(){
		var mask=$('#mask');
		if(mask.length>0){
			mask.hide();
		}
		return this;
	}
}