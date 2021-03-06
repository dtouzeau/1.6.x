<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if($usersmenus->AsMailBoxAdministrator==false){echo "alert('No privs');";die();}

	
	if(isset($_GET["manual-update"])){manual_update();exit;}
	if( isset($_GET['TargetArticaUploaded']) ){upload_artica_perform();exit();}
	if(isset($_GET["file-uploader-demo1"])){upload_artica_final();exit;}
	if(isset($_GET["uncompress"])){uncompress();exit;}
	if(isset($_GET["remove"])){remove();exit;}
	
	
	js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{manual_update}");
	echo "YahooWinBrowse('700','$page?manual-update=yes','$title',true)";
	
}	
function manual_update(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$UploadAFile=$tpl->javascript_parse_text("{upload_package}");
	$allowedExtensions="allowedExtensions: ['gz'],";
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$users=new usersMenus();
	$distri=$users->LinuxDistriFullName;
	$arch=$users->ArchStruct;
	$html="
	<H2>{manual_update}</H2>
	<div class=explain style='font-size:16px'>
	<strong>{your_operating_system}:$distri ({$arch}Bits)</strong>
	<hr>
	{nginx_manual_update_text}</div>
	<center>
	<table style='width:80%'>
	<tr>
		<td width=1%><img src='img/arrow-blue-left-32.png'></td>
		<td><a href=\"http://www.artica.fr/artica-catzdb.php\" 
		target=_new style='font-size:16px;text-decoration:underline'>{find_packages}</a></td>
	</tr>
	</table>
	</center>
	
	<center style='margin:10px;width:99%'>
		<center id='file-uploader-demo1' style='width:100%;text-align:center'></center>
	</center>
	<script>
function createUploader$t(){
	var uploader$t = new qq.FileUploader({
		element: document.getElementById('file-uploader-demo1'),
		action: '$page',$allowedExtensions
		template: '<div class=\"qq-uploader\">' +
		'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
		'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
		'<ul class=\"qq-upload-list\"></ul>' +
		'</div>',
		debug: false,
		params: {
		TargetArticaUploaded: 'yes',
		//select-file: '{$_GET["select-file"]}'
		},
		onComplete: function(id, fileName){
		PathUploaded(fileName);
		}
	});

}

function PathUploaded(fileName){
	LoadAjax('file-uploader-demo1','$page?file-uploader-demo1=yes&fileName='+fileName);
}
createUploader$t();
</script>
";
echo $tpl->_ENGINE_parse_body($html);


}
function upload_artica_perform(){

	usleep(300);
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);

	$sock=new sockets();
	$sock->getFrameWork("services.php?lighttpd-own=yes");

	if (isset($_GET['qqfile'])){
		$fileName = $_GET['qqfile'];
		if(function_exists("apache_request_headers")){
			$headers = apache_request_headers();
			if ((int)$headers['Content-Length'] == 0){
				writelogs("content length is zero",__FUNCTION__,__FILE__,__LINE__);
				die ('{error: "content length is zero"}');
			}
		}else{
			writelogs("apache_request_headers() no such function",__FUNCTION__,__FILE__,__LINE__);
		}
	} elseif (isset($_FILES['qqfile'])){
		$fileName = basename($_FILES['qqfile']['name']);
		writelogs("_FILES['qqfile']['name'] = $fileName",__FUNCTION__,__FILE__,__LINE__);
		if ($_FILES['qqfile']['size'] == 0){
			writelogs("file size is zero",__FUNCTION__,__FILE__,__LINE__);
			die ('{error: "file size is zero"}');
		}
	} else {
		writelogs("file not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "file not passed"}');
	}

	writelogs("upload_form_perform() -> OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);

	if (count($_GET)){
		$datas=json_encode(array_merge($_GET, array('fileName'=>$fileName)));
		writelogs($datas,__FUNCTION__,__FILE__,__LINE__);

	} else {
		writelogs("query params not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "query params not passed"}');
	}
	writelogs("upload_form_perform() -> OK {$_GET['qqfile']} upload_max_filesize=".ini_get('upload_max_filesize')." post_max_size:".ini_get('post_max_size'),__FUNCTION__,__FILE__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/class.file.upload.inc");
	$allowedExtensions = array();
	$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
	$sizeLimit2 = qqFileUploader::toBytes(ini_get('post_max_size'));

	if($sizeLimit2<$sizeLimit){$sizeLimit=$sizeLimit2;}

	$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($content_dir);

	writelogs("upload_form_perform() -> OK",__FUNCTION__,__FILE__,__LINE__);



	if(is_file("$content_dir$fileName")){
		writelogs("upload_form_perform() -> $content_dir$fileName OK",__FUNCTION__,__FILE__,__LINE__);
		$sock=new sockets();
		echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
		return;

	}
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;

}

function upload_artica_final(){
	$fileName=$_GET["fileName"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$fileNameEnc=urlencode($fileName);
	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>{uncompressing} $fileName</div>");
	echo "$text<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','$page?uncompress=$fileNameEnc');
	</script>
	
	";
	
}

function uncompress(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$fileName=$_GET["uncompress"];
	$sock=new sockets();
	$fileName=urlencode($fileName);
	$data=unserialize(base64_decode($sock->getFrameWork("nginx.php?uncompress-nginx=$fileName")));
	
	if($data==null){
		echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{failed}</div>");
		return;
	}
	
	if(!is_array($data)){
		echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{failed}</div>");
		return;
	}
	
	if(!$data["R"]){
		echo $tpl->_ENGINE_parse_body("<div style='font-size:16px'>{$data["T"]}</div>");
		return;
	}
	
	
	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>{$data["T"]}</div>");
	echo "$text<div id='$t'></div>
	<script>
		LoadAjaxTiny('$t','$page?remove=$fileName');
	</script>
	
	";
	
}

function remove(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();
	$fileName=$_GET["remove"];
	$sock=new sockets();
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$fileName";
	@unlink($content_dir);
	$text=$tpl->_ENGINE_parse_body("<div style='font-size:16px'>$fileName: {deleted}</div>");
	echo "$text<div id='$t'></div>
	<script>
		LoadAjax('nginx-section-status','miniadmin.proxy.reverse.php?nginx-section-status=yes');
		YahooWinBrowseHide();
	</script>
	
	";	
	
}
