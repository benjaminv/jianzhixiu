$(function(){
	// 检查版本号
	$("#CHK_DM299_VERSION").on("click",function(){
		pb({
			id:"chk_dm299_version_dialog",
			title:"版本信息",
			width:588,
			content:"<div id='chk_dm299_version_content' style='padding:20px 0px;line-height:26px;'>正在检查版本信息，请稍后…</div>",
			ok_title:"确定",
			drag:false,
			foot:false,
			cl_cBtn:false,
		});
		$.get("dm299_scan.php?act=chk_dm299_version", function(data){ 
			$("#chk_dm299_version_content").html(data)});
	});

});
	
function set_security_file(file,obj){
	$.getJSON('dm299_scan.php', 'act=set_security_file&file='+file, function(data){
		if ( data['code']==0){
			alert(data['data'])
		}
		else{
			$(obj).parent().parent().parent().remove();
		}
	});
}

	
function get_file_content(file){
	$.get('dm299_scan.php', 'act=get_file_content&file='+file, function(data){
		if ( data=='no'){
			alert("无法打开此文件")
		}
		else{
			pb({
				id:"chk_dm299_file_dialog",
				title:"文件【"+file+"】",
				width:1200,
				height:440,
				content:'<div style="overflow-y:scroll;height:440px;">'+data+'</div>',
				ok_title:"确定",
				drag:false,
				foot:false,
				cl_cBtn:false,
			});
		}
	});
}

