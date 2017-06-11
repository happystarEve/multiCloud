//获取地址栏参数
function GetQueryString(name){
     var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     var r = window.location.search.substr(1).match(reg);
     if(r!=null)return  unescape(r[2]); return null;
}
$(function(){
	var zTreeObj;
	$("input[name=folderlist]").click(function(){
		var countcheck=$("input[name=folderlist]:checked").length;
		//checkbox多于2，则重命名不能用
		if(countcheck>1){
			$("#rename").attr("disabled","true");
		}else{
			$("#rename").removeAttr("disabled");
		}

		if(countcheck > 0){
			$('.tools').show();
			$('.buttons').show();
			$('.tools_all').hide();
			$(".count").html(countcheck);
			$("input[name=select_file]").prop("checked",true);
		}else{
			$('[type=checkbox]:checkbox').prop("checked", false);
			$('.tools').hide();
			$('.buttons').hide();
			$('.tools_all').show();
		}	
	});

	//已选中的全不选
	$("input[name=select_file]").click(function(){
		$('[type=checkbox]:checkbox').prop("checked", false);
		$('.tools').hide();
		$('.buttons').hide();
		$('.tools_all').show();
	});
	//共多少文件的全选/全不选
	$("#total_files").click(function(){
		if(this.checked){
			$("input[name=folderlist]").prop("checked", true);
		}else{
			$("input[name=folderlist]").prop("checked", false);
		}
		
		$(".total").html($("input[name=folderlist]:checked").length);
		$(".buttons").toggle();
	});

	// /*右键显示菜单*/
	// var tr = document.getElementsByTagName("tr");
	// var docuH= document.documentElement.clientHeight+document.documentElement.scrollTop+ document.body.scrollTop;
	// var tipH =$( document.getElementById("contextMenu")).height(),X,Y,totalH;
	
	// for(var i=2; i<tr.length; i++){
	// 	tr[i].oncontextmenu = function (event){  
	//         var event = event || window.event;  
	//         var top = event.clientY ;  
	//         var left = event.clientX + "px"; 
	// 		totalH = $(this).offset().top+tipH; 
			
	// 		Y=totalH>docuH?top-tipH:top;
			
	//       	$(".contextMenu").css("left",left);
	//       	$(".contextMenu").css("top",Y+"px");
	//       	$(".contextMenu").show();
	//       	$(".hoverMenu").hide(); 
	//         event.preventDefault(); 
	//    	};
	//     //点击隐藏菜单  
	//   	document.onclick = function ()  {  
	//         $(".contextMenu").hide();
	//     };  
	// }
	// /*hover菜单*/
	// $(".fa-chevron-circle-down").click(function(event){
	// 	e=arguments.callee.caller.arguments[0] || window.event; 
	// 	var top = e.clientY+ "px";  
	//  	var left = e.clientX + "px";
	//  	console.log(top); 
	// 	$(".hoverMenu").css("left",left);
 //  		$(".hoverMenu").css("top",top);
 //  		$(".hoverMenu").show(); 
 //  		$(".contextMenu").hide();
	// }).mouseout(function(){
	// 	$(".hoverMenu").hide()	
	// });
	// $(".hoverMenu").mouseover(function(){
	// 	$(this).show();	
	// }).mouseout(function(){
	// 	$(this).hide();	
	// });

	//提交上传文件
	$("#uploadfile").change(function(){
		$("#uploadform").submit();
		// var uploadfile = $("#uploadfile").val();
		// var fileName = uploadfile.split("\\");
		// var folderName = GetQueryString("folderName");
		// if(folderName !=null && folderName.toString().length>1){
		// 	var objectName = folderName + fileName[fileName.length-1];
		// }else{
		// 	var objectName = fileName[fileName.length-1];
		// }
		// $.ajax({
		// 	type: "GET",
		// 	url: "http://localhost/finaldesign/checkfile?objectName="+objectName,
		// 	dataType: "json",
		// 	success: function(data){
		// 		if(data.success){
		// 			$("#uploadform").submit();
		// 		}else{
		// 			r = confirm(data.msg);
		// 			if(r == true){
		// 				$("#uploadform").submit();
		// 			}
		// 		}
		// 	},
		// 	error: function(jqXHR){
		// 		alert("发生错误：" + jqXHR.status);
		// 	}
		// });	
	});

	//新建文件夹
	$("#addnewfile").click(function(){
		$("#newfileitem").show();
	});
	$(".newfile_check").click(function(){
		if($(".newfile").val() == ""){
			$(".newfile").val("新建文件夹");
		}
		$("#newfileform").submit();
		$("#newfileitem").hide();
	});
	$(".newfile_close").click(function(){
		$("#newfileitem").hide();
	});

	//点击重命名按钮
	$("#rename").click(function(){
		var id = $("input[name='folderlist']:checked").attr('id');
		$("input[name='folderlist']:checked").parent().siblings(".filehide").show();
		$("input[name='folderlist']:checked").parent().siblings(".fileshow").hide();
		$("#checkedId").val(id);
	});
	$(".rename_check").click(function(){
		if($(".renamefile").val() == ""){
			alert("文件(夹)名称不能为空，请输入文件名称");
		}else{
			$("#newfileform").submit();
			$(".filehide").hide();
			$(".fileshow").show();
		}
	});
	$(".rename_close").click(function(){
		$(".filehide").hide();
		$(".fileshow").show();
	})
	//点击下载按钮
	$("#download").click(function(){
		var str = "";
		var href = window.location.href;
		var num = href.indexOf("?");
		$("input[name='folderlist']:checked").each(function () {
			str += this.id + ',';
        });
        var id_list = str.split(",");
        for(var i=0; i<id_list.length-1; i++){
        	if(num < 0){
        		document.write('<a id="d'+i+'" href="'+href+"?Id="+id_list[i]+'" target=_blank>x</a>');
        	}else{
        		document.write('<a id="d'+i+'" href="'+href.substring(0,num-1)+"?Id="+id_list[i]+'" target=_blank>x</a>');
        	}   	
        }
        for(var i=0; i<id_list.length-1; i++){
        	document.getElementById('d'+i).click();
        }
        window.location = href;
	});
	//点击删除按钮
	$("#delete").click(function(){
		var str = "";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;
		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(confirm("确定要删除这"+count+"个文件？")){
			if(num < 0){
				window.location = href + "?delId=" + str;
			}else{
				window.location = href.substring(0,num-1) + "?delId=" + str;
			}
		}	
	});
	//点击复制到按钮
	$("#copy").click(function(){
		var dialog=new Dialog({target:'#copy_file',width:450,height:300,showYesBtn:false,showNoBtn:false,title: '复制到'});
		dialog.show();
		//树状文件夹 ztree
		var setting = {
			data: {
				simpleData: {
					enable: true
				}
			}
		};
		$.ajax({
			type: "GET",
			url: "http://localhost/finaldesign/tree?act=tree",
			dataType: "json",
			success: function(data){
				if(data.success){
					var treeNodes = data.msg;
					zTreeObj = $.fn.zTree.init($("#showtree"), setting, treeNodes); 
				}else{
					alert("文件夹树加载错误");
				}
			},
			error: function(jqXHR){
				alert("发生错误：" + jqXHR.status);
			}
		});	
	});

	//提交复制到表单
	$("#copySub").click(function(){
		var str="";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;
		var node = zTreeObj.getSelectedNodes();

		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(num < 0){
			window.location = href + "?copyId=" + str + "&treeNodeId=" + node[0]['id'];
		}else{
			window.location = href.substring(0, num-1) + "?copyId=" + str + "&treeNodeId=" + node[0]['id'];
		}
	});

	//点击移动到按钮
	$("#remove").click(function(){
		var dialog=new Dialog({target:'#remove_file',width:450,height:300,showYesBtn:false,showNoBtn:false,title: '移动到'});
		dialog.show();
		//树状文件夹 ztree
		var setting = {
			data: {
				simpleData: {
					enable: true
				}
			}
		};
		$.ajax({
			type: "GET",
			url: "http://localhost/finaldesign/tree?act=tree",
			dataType: "json",
			success: function(data){
				if(data.success){
					var treeNodes = data.msg;
					zTreeObj = $.fn.zTree.init($("#showremovetree"), setting, treeNodes); 
				}else{
					alert("文件夹树加载错误");
				}
			},
			error: function(jqXHR){
				alert("发生错误：" + jqXHR.status);
			}
		});	
	});

	//提交移动到表单
	$("#removeSub").click(function(){
		var str="";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;
		var node = zTreeObj.getSelectedNodes();

		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(num < 0){
			window.location = href + "?removeId=" + str + "&treeNodeId=" + node[0]['id'];
		}else{
			window.location = href.substring(0, num-1) + "?removeId=" + str + "&treeNodeId=" + node[0]['id'];
		}
	});

	/************修改安全策略************/
	//百度云
	$("#baiduCloud").click(function(){
		var str="";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;

		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(num < 0){
			window.location = href + "?securityId=" + str + "&securityName=baiduCloud";
		}else{
			window.location = href.substring(0, num) + "?securityId=" + str + "&securityName=baiduCloud";
		}
	});
	//阿里云
	$("#aliCloud").click(function(){
		var str="";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;

		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(num < 0){
			window.location = href + "?securityId=" + str + "&securityName=aliCloud";
		}else{
			window.location = href.substring(0, num) + "?securityId=" + str + "&securityName=aliCloud";
		}
	});
	//双存储
	$("#dualStorage").click(function(){
		var str="";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;
		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(num < 0){
			window.location = href + "?securityId=" + str + "&securityName=dualStorage";
		}else{
			window.location = href.substring(0, num) + "?securityId=" + str + "&securityName=dualStorage";
		}
	});
	//半存储
	$("#halfStorage").click(function(){
		var str="";
		var href = window.location.href;
		var num = href.indexOf("?");
		var count = 0;

		$("input[name='folderlist']:checked").each(function(){
			count++;
			str += this.id + ',';
		});
		if(num < 0){
			window.location = href + "?securityId=" + str + "&securityName=halfStorage";
		}else{
			window.location = href.substring(0, num) + "?securityId=" + str + "&securityName=halfStorage";
		}
	});
});	