<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if((!$users->AsSambaAdministrator) OR (!$users->AsSystemAdministrator)){
		$tpl=new templates();
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
}

if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["autofs-status"])){status_service();exit;}
if(isset($_POST["EnableAutoFSDebug"])){EnableAutoFSDebugSave();exit;}

if(isset($_GET["mounts-list-js"])){mounts_list_js();exit;}
if(isset($_GET["mounts"])){mounts_list();exit;}
if(isset($_GET["now-search-items"])){mounts_list_items();exit;}
if(isset($_GET["form-add-js"])){form_add_js();exit;}
if(isset($_GET["form-add-popup"])){form_add_popup();exit;}
if(isset($_GET["form-add-details"])){form_add_details();exit;}
if(isset($_GET["form-add-usblist"])){usblist();exit;}

if(isset($_GET["FTP_SERVER"])){PROTO_FTP_ADD();exit;}
if(isset($_GET["CIFS_SERVER"])){PROTO_CIFS_ADD();exit;}
if(isset($_GET["NFS_SERVER"])){PROTO_NFS_ADD();exit;}
if(isset($_GET["HTTP_SERVER"])){PROTO_WEBDAVFS_ADD();exit;}



if(isset($_GET["USB_UUID"])){PROTO_USB_ADD();exit;}
if(isset($_GET["AutoFSDeleteDN"])){mounts_delete();exit;}
if(isset($_GET["logs"])){mounts_events();exit;}
if(isset($_GET["syslog-table"])){mounts_events_query();exit;}


js();


function form_add_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{add_mount_point}");
	$YahooWin="YahooWin4";
	if($_GET["field"]<>null){$YahooWin="LoadWinORG";}
	
	echo "$YahooWin('850','$page?form-add-popup=yes&dn={$_GET["dn"]}&t={$_GET["t"]}&field={$_GET["field"]}','$title');";
	
}
function mounts_list_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{mounts_list}");
	$YahooWin="LoadWinORG";
	echo "$YahooWin('800','$page?mounts=yes&t={$_GET["t"]}&field={$_GET["field"]}','$title');";
}


function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{automount_center}");
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','$mailman::$cmd');";
	echo $html;
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("autofs.php?service-cmds={$_GET["service-cmds-peform"]}&MyCURLTIMEOUT=120")));

	$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";

	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
		$html=$html."
		<tr class=$classtr>
		<td width=99%><code style='font-size:12px'>$val</code></td>
		</tr>
		";


	}

	$html=$html."
	</tbody>
</table>
</div>
<script>
	RefreshTab('main_config_autofs');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}

function form_add_popup(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();	
	if($users->CURLFTPFS_INSTALLED){$protos["FTP"]="{ftp_directory}";}
	if($users->CIFS_INSTALLED){$protos["CIFS"]="{windows_network_share}";}
	if($users->DAVFS_INSTALLED){$protos["DAVFS"]="{TAB_WEBDAV}";}
	$protos["NFSV3"]="NFS v3";
	$protos["NFSV4"]="NFS v4";
	$protos["USB"]="{external_device}";
	$protos[null]="{select}";
	
	$html="
	<div id='form-autofs-add-div'>
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:22px' colspan=2><div class=explain style='font-size:22px'>{autofs_wizard_1}</td>
		
	</tr>			
			
	<tr>
		<td class=legend style='font-size:22px'>{filesystem_type}:</td>
		<td>". Field_array_Hash($protos,"proto",null,"ChangeFS()",null,0,"font-size:22px;padding:3px")."</td>
	</tr>
	</table>
	<hr>
	<div id='autofs-details' style='min-height:220px'></div>
	</div>
	<script>
		function ChangeFS(){
			var proto=document.getElementById('proto').value;
			LoadAjax('autofs-details','$page?form-add-details=yes&field={$_GET["field"]}&t={$_GET["t"]}&dn={$_GET["dn"]}&proto='+proto);
		}
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);		
	
	
}

function form_add_details(){
switch ($_GET["proto"]) {
		case 'FTP':form_add_details_FTP();break;
		case 'CIFS':form_add_details_CIFS();break;
		case 'NFSV3':form_add_details_NFS();break;
		case 'NFSV4':form_add_details_NFS();break;
		case 'NFSV4':form_add_details_NFS();break;
		case 'DAVFS':form_add_details_DAVFS();break;
		case 'Start':StartApplyConfig();break;
		default:
			break;
	}
	
}

function EnableAutoFSDebugSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableAutoFSDebug",$_POST["EnableAutoFSDebug"]);
	
	
	
	
}
function form_add_details_USB(){
	
	$dn=$_GET["dn"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:22px' colspan=2><div class=explain style='font-size:22px'>{autofs_wizard_2}</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{local_directory_name}:</td>
		<td>". Field_text("USB_LOCAL_DIR",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>			
	</table>
	<div style='width:100%;text-align:right;margin-bottom:5px'>". imgtootltip("refresh-32.png","{refresh}","refreshUsbList()")."</div>
	<div id='local-sub-list' style='height:250px;overflow:auto'></div>
	
	<script>


	function refreshUsbList(){
		LoadAjax('local-sub-list','$page?form-add-usblist=yes&field={$_GET["field"]}');
	}
	refreshUsbList();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function usblist(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock->getFrameWork("cmd.php?usb-scan-write=yes");
	if(!file_exists('ressources/usb.scan.inc')){return ;}
	include("ressources/usb.scan.inc");
	include_once("ressources/class.os.system.tools.inc");
	$os=new os_system();
	$count=0;
	//print_r($_GLOBAL["usb_list"]);
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
		<th>{type}</th>
		<th>{label}</th>
		<th>{size}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while (list ($UUID, $ligne) = each ($_GLOBAL["usb_list"]) ){
		$TYPE=$ligne["TYPE"];
		$ID_MODEL=$ligne["ID_MODEL"];
		$LABEL=$ligne["LABEL"];
		$DEV=$ligne["DEV"];
		if($LABEL==null){$LABEL=$ID_MODEL;}
		$SIZE=explode(";",$ligne["SIZE"]);
		
		$real_size=$SIZE[0];
		if($DEV<>null){$LABEL=$LABEL." ($DEV)";}
		
		
		if($real_size==null){$real_size="{unknown}";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		$selected=imgtootltip("plus-24.png","{select}","AutoFSUSB('$UUID')");
		$html=$html . "
		<tr  class=$classtr>
			<td width=1%><img src='img/usb-32-green.png'></td>
			<td width=1% align='center' nowrap><strong style='font-size:14px'><code style='color:$color'>$TYPE</code></td>
			<td width=99% align='left'><strong style='font-size:14px'>$LABEL</strong></td>
			<td width=1% align='left' nowrap><strong style='font-size:14px'>$real_size</strong></td>
			<td width=1%>$selected</td>
		</tr>";		
		
		
	}
	
	$html=$html."</tbody></table>
	<script>

	var x_AutoFSUSB= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		if(document.getElementById('main_config_autofs')){RefreshTab('main_config_autofs');}
		if(document.getElementById('BackupTaskAutoFSMountedList')){RefreshAutoMountsBackup();}
		var field='{$_GET["field"]}';
		if(field.length>1){Loadjs('autofs.wizard.php?field='+field);WinORGHide();return;}
		YahooWin4Hide();		
	}		
	
	function AutoFSUSB(key){
		var XHR = new XHRConnection();
		XHR.appendData('USB_UUID',key);	
		var localdir=document.getElementById('USB_LOCAL_DIR').value;
		if(localdir.length==0){alert('LOCAL DIR !');return;}
		XHR.appendData('USB_LOCAL_DIR',document.getElementById('USB_LOCAL_DIR').value);
		XHR.sendAndLoad('$page', 'GET',x_AutoFSUSB);
		}	

	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function form_add_details_FTP(){
	$dn=$_GET["dn"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:22px' colspan=2><div class=explain style='font-size:22px'>{autofs_wizard_2}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{remote_server_name}:</td>
		<td>". Field_text("FTP_SERVER",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{ftp_user}:</td>
		<td>". Field_text("FTP_USER",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("FTP_PASSWORD",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{local_directory_name}:</td>
		<td>". Field_text("FTP_LOCAL_DIR",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>			
	<tr>
		<td colspan=2 align='right' style='font-size:22px'>
		<hr>". button("{add}","SaveAutoFsFTP()","30px")."</td>
	</tr>
	</table>
	
	<script>
		
var x_SaveAutoFsFTP= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	if(document.getElementById('BackupTaskAutoFSMountedList')){RefreshAutoMountsBackup();}
	$('#flexRT{$_GET["t"]}').flexReload();
	var field='{$_GET["field"]}';
	if(field.length>1){Loadjs('autofs.wizard.php?field='+field);WinORGHide();return;}
	YahooWin4Hide();	
	}	
		
	function SaveAutoFsFTP(){
		var XHR = new XHRConnection();
		XHR.appendData('FTP_SERVER',document.getElementById('FTP_SERVER').value);
		XHR.appendData('FTP_USER',document.getElementById('FTP_USER').value);
		
		var pp=encodeURIComponent(document.getElementById('FTP_PASSWORD').value);
		
		XHR.appendData('FTP_PASSWORD',pp);
		XHR.appendData('FTP_LOCAL_DIR',document.getElementById('FTP_LOCAL_DIR').value);	
		XHR.sendAndLoad('$page', 'GET',x_SaveAutoFsFTP);
	}		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function form_add_details_DAVFS(){
$dn=$_GET["dn"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:22px' colspan=2><div class=explain style='font-size:22px'>{autofs_wizard_2}</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{url}:</td>
		<td>". Field_text("HTTP_SERVER",null,"font-size:22px;padding:3px;width:350px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{web_user}:</td>
		<td>". Field_text("HTTP_USER",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("HTTP_PASSWORD",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{local_directory_name}:</td>
		<td>". Field_text("HTTP_LOCAL_DIR",null,"font-size:22px;padding:3px;width:250px",
				null,null,null,false,"SaveAutoWebDavfsCK(event)")."</td>
	</tr>			
	<tr>
		<td colspan=2 align='right' style='font-size:22px'>
		<hr>". button("{add}","SaveAutoWebDavfs()","30px")."</td>
	</tr>
	</table>
	
	<script>
		
var x_SaveAutoWebDavfs= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	if(document.getElementById('BackupTaskAutoFSMountedList')){RefreshAutoMountsBackup();}
	$('#flexRT{$_GET["t"]}').flexReload();
	var field='{$_GET["field"]}';
	if(field.length>1){Loadjs('autofs.wizard.php?field='+field);WinORGHide();return;}
	YahooWin4Hide();	
	}	
	
	function SaveAutoWebDavfsCK(e){
		if(!checkEnter(e)){return;}
		SaveAutoWebDavfs();
	}
		
	function SaveAutoWebDavfs(){
		var XHR = new XHRConnection();
		XHR.appendData('HTTP_SERVER',document.getElementById('HTTP_SERVER').value);
		XHR.appendData('HTTP_USER',document.getElementById('HTTP_USER').value);
		XHR.appendData('HTTP_PASSWORD',document.getElementById('HTTP_PASSWORD').value);
		XHR.appendData('HTTP_LOCAL_DIR',document.getElementById('HTTP_LOCAL_DIR').value);
		//document.getElementById('form-autofs-add-div').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';	
		XHR.sendAndLoad('$page', 'GET',x_SaveAutoWebDavfs);
	}		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
		
	
}

function form_add_details_NFS(){
	$dn=$_GET["dn"];
	$page=CurrentPageName();
	$tpl=new templates();	

switch ($_GET["proto"]) {
		case 'NFSV3':$type="nfs3";break;
		case 'NFSV4':$type="nfs4";break;
		default:
			break;
	}	
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:22px' colspan=2><div class=explain style='font-size:22px'>{autofs_wizard_2}</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:22px'>{remote_server_name}:</td>
		<td>". Field_text("NFS_SERVER",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{target_directory}:</td>
		<td>". Field_text("NFS_FOLDER",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{local_directory_name}:</td>
		<td>". Field_text("NFS_LOCAL_DIR",null,"font-size:22px;padding:3px;width:250px")."</td>
	</tr>			
	<tr>
		<td colspan=2 align='right' style='font-size:22px'>
		<hr>". button("{add}","SaveAutoFsNFS()","30px")."</td>
	</tr>
	</table>
	
	<script>
		
var x_SaveAutoFsNFS= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	if(document.getElementById('BackupTaskAutoFSMountedList')){RefreshAutoMountsBackup();}
	$('#flexRT{$_GET["t"]}').flexReload();
	var field='{$_GET["field"]}';
	if(field.length>1){Loadjs('autofs.wizard.php?field='+field);WinORGHide();return;}
	YahooWin4Hide();	
	}	
		
	function SaveAutoFsNFS(){
		var XHR = new XHRConnection();
		XHR.appendData('NFS_SERVER',document.getElementById('NFS_SERVER').value);
		XHR.appendData('NFS_FOLDER',document.getElementById('NFS_FOLDER').value);
		XHR.appendData('NFS_LOCAL_DIR',document.getElementById('NFS_LOCAL_DIR').value);
		XHR.appendData('NFS_PROTO','$type');
		//document.getElementById('form-autofs-add-div').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';	
		XHR.sendAndLoad('$page', 'GET',x_SaveAutoFsNFS);
	}		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function form_add_details_CIFS(){
	$dn=$_GET["dn"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();
	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
	
	if($users->SAMBA_INSTALLED){
		if($SambaEnabled==1){
			$button_browes="<input type='button' 
			OnClick=\"javascript:Loadjs('samba.smbtree.php?server-field=CIFS_SERVER&folder-field=CIFS_FOLDER')\" 
			value=\"{browse}&nbsp;&raquo;&raquo\" style='font-size:14px'>";
		}
	}
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td style='font-size:22px' colspan=2><div class=explain style='font-size:22px'>{autofs_wizard_2}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:22px'>{remote_server_name}:</td>
		<td>". Field_text("CIFS_SERVER",null,"font-size:22px;padding:3px;width:250px")."</td>
		<td>$button_browes</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{target_directory}:</td>
		<td>". Field_text("CIFS_FOLDER",null,"font-size:22px;padding:3px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{username}:</td>
		<td>". Field_text("CIFS_USER",null,"font-size:22px;padding:3px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td>". Field_password("CIFS_PASSWORD",null,"font-size:22px;padding:3px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{local_directory_name}:</td>
		<td>". Field_text("CIFS_LOCAL_DIR",null,"font-size:22px;padding:3px;width:250px")."</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td colspan=3 align='right' style='font-size:22px'>
		<hr>". button("{add}","SaveAutoFsCIFS()","30px")."</td>
	</tr>
	</table>
	
	<script>
		
var x_SaveAutoFsCIFS= function (obj) {
	
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	
	if(document.getElementById('BackupTaskAutoFSMountedList')){RefreshAutoMountsBackup();}
	if(document.getElementById('main_config_autofs')){RefreshTab('main_config_autofs');}
	$('#flexRT{$_GET["t"]}').flexReload();
	var field='{$_GET["field"]}';
	if(field.length>1){Loadjs('autofs.wizard.php?field='+field);WinORGHide();return;}
	YahooWin4Hide();
	}	
		
	function SaveAutoFsCIFS(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('CIFS_PASSWORD').value);
		var pp1=encodeURIComponent(document.getElementById('CIFS_FOLDER').value);
		XHR.appendData('CIFS_SERVER',document.getElementById('CIFS_SERVER').value);
		XHR.appendData('CIFS_USER',document.getElementById('CIFS_USER').value);
		XHR.appendData('CIFS_FOLDER',pp1);
		XHR.appendData('CIFS_PASSWORD',pp);
		XHR.appendData('CIFS_LOCAL_DIR',document.getElementById('CIFS_LOCAL_DIR').value);	
		XHR.sendAndLoad('$page', 'GET',x_SaveAutoFsCIFS);
	}		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function PROTO_USB_ADD(){
	$ldap=new clladp();
	$sock=new sockets();
	if($_GET["USB_LOCAL_DIR"]==null){$_GET["USB_LOCAL_DIR"]=$_GET["USB_UUID"];}
	$_GET["USB_LOCAL_DIR"]=strtolower($ldap->StripSpecialsChars($_GET["USB_LOCAL_DIR"]));
	
	$upd=array();
	$dn="cn={$_GET["USB_LOCAL_DIR"]},ou=auto.automounts,ou=mounts,$ldap->suffix";	
	
	$pattern="-fstype=auto\tUUID=\"{$_GET["USB_UUID"]}\"";	
	
	if(!$ldap->ExistsDN($dn)){
		$upd["ObjectClass"][]='top';
		$upd["ObjectClass"][]='automount';
		$upd["cn"][]="{$_GET["USB_LOCAL_DIR"]}";
		$upd["automountInformation"][]=$pattern;
		if(!$ldap->ldap_add($dn,$upd)){echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;}
		$sock->getFrameWork("autofs.php?autofs-reload=yes");
		return;
	}
	
	
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;
		return false;
	}	
	
	$sock->getFrameWork("autofs.php?autofs-reload=yes");	
	
}

function PROTO_CIFS_ADD(){
	$ldap=new clladp();
	$sock=new sockets();
	$auto=new autofs();
	$_GET["CIFS_PASSWORD"]=autofs_escape_chars(url_decode_special_tool($_GET["CIFS_PASSWORD"]));
	$_GET["CIFS_FOLDER"]=autofs_escape_chars(url_decode_special_tool($_GET["CIFS_FOLDER"]));
	
	
	$_GET["CIFS_LOCAL_DIR"]=strtolower($ldap->StripSpecialsChars($_GET["CIFS_LOCAL_DIR"]));
	$upd=array();
	$dn="cn={$_GET["CIFS_LOCAL_DIR"]},ou=auto.automounts,ou=mounts,$ldap->suffix";	
	if($_GET["CIFS_USER"]<>null){
		$auth="user={$_GET["CIFS_USER"]},pass={$_GET["CIFS_PASSWORD"]}";
	}
	
	$pattern="-fstype=cifs,rw,noperm,$auth ://{$_GET["CIFS_SERVER"]}/{$_GET["CIFS_FOLDER"]}";	
	
	if(!$ldap->ExistsDN($dn)){
		$upd["ObjectClass"][]='top';
		$upd["ObjectClass"][]='automount';
		$upd["cn"][]="{$_GET["CIFS_LOCAL_DIR"]}";
		$upd["automountInformation"][]=$pattern;
		if(!$ldap->ldap_add($dn,$upd)){echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;}
		$sock->getFrameWork("autofs.php?autofs-reload=yes");
		return;
	}
	
	
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;
		return false;
	}	
	
	$sock->getFrameWork("autofs.php?autofs-reload=yes");	
}

function PROTO_NFS_ADD(){
	$auto=new autofs();
	$ldap=new clladp();
	$sock=new sockets();
	$_GET["NFS_LOCAL_DIR"]=strtolower($ldap->StripSpecialsChars($_GET["NFS_LOCAL_DIR"]));
	$upd=array();
	$dn="cn={$_GET["NFS_LOCAL_DIR"]},ou=auto.automounts,ou=mounts,$ldap->suffix";	
	
	$pattern="-fstype={$_GET["NFS_PROTO"]},rw,soft,intr,rsize=8192,wsize=8192\t{$_GET["NFS_SERVER"]}/{$_GET["NFS_FOLDER"]}/&";	
	
	if(!$ldap->ExistsDN($dn)){
		$upd["ObjectClass"][]='top';
		$upd["ObjectClass"][]='automount';
		$upd["cn"][]="{$_GET["NFS_LOCAL_DIR"]}";
		$upd["automountInformation"][]=$pattern;
		if(!$ldap->ldap_add($dn,$upd)){echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;}
		$sock->getFrameWork("autofs.php?autofs-reload=yes");
		return;
	}
	
	
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;
		return false;
	}	
	
	$sock->getFrameWork("autofs.php?autofs-reload=yes");	
}

function PROTO_WEBDAVFS_ADD(){
	$auto=new autofs();
	$ldap=new clladp();
	$sock=new sockets();
	$_GET["HTTP_LOCAL_DIR"]=strtolower($ldap->StripSpecialsChars($_GET["HTTP_LOCAL_DIR"]));
	$upd=array();
	$uri=$_GET["HTTP_SERVER"];
	
	if(!preg_match("#^http.*?:\/\/#",$_GET["HTTP_SERVER"],$re)){
		echo "{$_GET["HTTP_SERVER"]}: Bad format\nuse https://... or http://...";
		return;
	}
	
	
	$_GET["HTTP_SERVER"]=str_replace(":","\:",$_GET["HTTP_SERVER"]);
	
	
	if(substr($_GET["HTTP_SERVER"],strlen($_GET["HTTP_SERVER"])-1,1)<>"/"){
		$_GET["HTTP_SERVER"]=$_GET["HTTP_SERVER"]."/";
	}
	
	if($_GET["HTTP_LOCAL_DIR"]==null){$_GET["HTTP_LOCAL_DIR"]=time();}
	
	$dn="cn={$_GET["HTTP_LOCAL_DIR"]},ou=auto.automounts,ou=mounts,$ldap->suffix";	
	$pattern="-fstype=davfs,rw,nosuid,nodev,user :{$_GET["HTTP_SERVER"]}";
	
	$password=addslashes($_GET["HTTP_PASSWORD"]);
	$user=addslashes($_GET["HTTP_USER"]);
	$local_dir=addslashes($_GET["HTTP_LOCAL_DIR"]);
	$q=new mysql();
	
if(!$ldap->ExistsDN($dn)){
	$sql="INSERT IGNORE INTO automount_davfs(local_dir,user,password,uri) VALUES('$local_dir','$user','$password','$uri')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$upd["ObjectClass"][]='top';
	$upd["ObjectClass"][]='automount';
	$upd["cn"][]="{$_GET["HTTP_LOCAL_DIR"]}";
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->ldap_add($dn,$upd)){echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;}
	$sock->getFrameWork("autofs.php?autofs-reload=yes");
	return;
	}
	
	
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;
		return false;
	}	
	
	$sql="UPDATE automount_davfs SET `user`='$user',`password`='$password',`uri`='$uri' WHERE `local_dir`='$local_dir'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$sock->getFrameWork("autofs.php?autofs-reload=yes");	
}


function PROTO_FTP_ADD(){
	$auto=new autofs();
	$ldap=new clladp();
	$sock=new sockets();
	$_GET["FTP_LOCAL_DIR"]=strtolower($ldap->StripSpecialsChars($_GET["FTP_LOCAL_DIR"]));
	$upd=array();
	$dn="cn={$_GET["FTP_LOCAL_DIR"]},ou=auto.automounts,ou=mounts,$ldap->suffix";
	$_GET["FTP_PASSWORD"]=percent_encoding(url_decode_special_tool($_GET["FTP_PASSWORD"]));
	

	
	
	
	if($_GET["FTP_USER"]<>null){
		$auth="{$_GET["FTP_USER"]}\:{$_GET["FTP_PASSWORD"]}\@";
	}
	
	$pattern="-fstype=curl,rw,allow_other,nodev,nonempty,noatime :ftp\://$auth{$_GET["FTP_SERVER"]}/";
	

if(!$ldap->ExistsDN($dn)){
	$upd["ObjectClass"][]='top';
	$upd["ObjectClass"][]='automount';
	$upd["cn"][]="{$_GET["FTP_LOCAL_DIR"]}";
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->ldap_add($dn,$upd)){echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;}
	$sock->getFrameWork("autofs.php?autofs-reload=yes");
	return;
	}
	
	
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;
		return false;
	}	
	
	$sock->getFrameWork("autofs.php?autofs-reload=yes");
}


function js(){
		$jsstart="AutofsConfigLoad()";
		if(isset($_GET["windows"])){$jsstart="AutofsConfigLoadPopup()";}
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{automount_center}");
		$html="
		
		
		function AutofsConfigLoad(){
			$('#BodyContent').load('$page?tabs=yes');
			}
			
		function AutofsConfigLoadPopup(){
			YahooWin3(921,'$page?tabs=yes','$title');
			}					
			
		$jsstart;
		";
		echo $html;
		
	
}


function status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$add=Paragraphe("net-disk-add-64.png","{add_mount_point}","{add_mount_point_text}","javascript:");
	$EnableAutoFSDebug=$sock->GET_INFO("EnableAutoFSDebug");
	if(!is_numeric($EnableAutoFSDebug)){$EnableAutoFSDebug=1;}
	
	$add=imgtootltip("add-128.png","{add_mount_point}","Loadjs('$page?form-add-js=yes')");
	
	$autofs=new autofs();
	$hash=$autofs->automounts_Browse();	
	if(count($hash)==0){
		$error=FATAL_ERROR_SHOW_128_DESIGN(null,"{AUTOFS_ERROR_NO_CONNECTION}");
		
		
	}
	
	$html="
	<div style='font-size:30px'>{automount_center}</div>
	<div class=explain style='font-size:24px'>{autofs_about}</div>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' style='width:500px'>
		<div id='autofs-status'></div>
		</td>
		<td valign='top' width=1000px'>
			
		<center>
		$add
		<center style='font-size:18px;margin-bottom:20px;margin-top:15px'>{add_mount_point_text}</center>
		$error		
		<table style='width:99%'>
			<tr>
				<td class=legend style='font-size:22px'>{debug_mode}:</td>
				<td>". Field_checkbox_design("EnableAutoFSDebug",1,$EnableAutoFSDebug,"EnableAutoFSDebugCheck()")."
			</td>
		</table>
		</center>
	</tr>
	</table>
	<script>
	var xEnableAutoFSDebugCheck= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		Loadjs('autofs.restart.progress.php')
	}		
	
	
	
	
		function EnableAutoFSDebugCheck(){
			var XHR = new XHRConnection();
			if(document.getElementById('EnableAutoFSDebug').checked){
				XHR.appendData('EnableAutoFSDebug','1');
			}else{
				XHR.appendData('EnableAutoFSDebug','0');
			}
	
			XHR.sendAndLoad('$page', 'POST',xEnableAutoFSDebugCheck);
		}	
	
		
	
	
		LoadAjax('autofs-status','$page?autofs-status=yes');
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function status_service(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$datas=base64_decode($sock->getFrameWork("autofs.php?autofs-ini-status=yes"));	
	
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("APP_AUTOFS",$ini,null,0);
	$html="<center style='padding-left:10px'>$status
	". button("{restart_service}", "Loadjs('autofs.restart.progress.php')",28,430)."	
	
	</center>";
	echo $tpl->_ENGINE_parse_body($html);		
	
}


function tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["status"]='{status}';
	$array["mounts"]='{mounts_list}';
	$array["logs"]='{events}';
	
	
	

	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:26px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_autofs");

		
	
}

function mounts_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$source=$tpl->_ENGINE_parse_body("{source}");
	$local_directory_name=$tpl->_ENGINE_parse_body("{local_directory_name}");
	$title=$tpl->_ENGINE_parse_body($title);
	$users=new usersMenus();
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$link_computer=$tpl->_ENGINE_parse_body("{link_computer}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$add_mount_point=$tpl->_ENGINE_parse_body("{add_mount_point}");
	$remove_connection_ask=$tpl->_ENGINE_parse_body("{remove_connection_ask}");
	$buttons="
	buttons : [
		{name: '<strong style=font-size:18px>$add_mount_point</strong>', bclass: 'add', onpress : add_mount_point$t},
	],";		
		
	if($_GET["field"]<>null){$buttons=null;}
	

$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
var TMPMD$t='';
$('#flexRT$t').flexigrid({
	url: '$page?now-search-items=yes&t=$t&field={$_GET["field"]}',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$proto</span>', name : 'proto', width : 90, sortable : false, align: 'center'},	
		{display: '<span style=font-size:18px>$source</span>', name : 'source', width :609, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$local_directory_name</span>', name : 'ipaddr', width :544, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 90, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$proto', name : 'proto'},
		{display: '$source', name : 'source'},
		{display: '$local_directory_name', name : 'name'},
		
		],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function add_mount_point$t(){
	Loadjs('$page?form-add-js=yes&t=$t');
}
	var x_AutoFSDeleteDN= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		$('#row'+TMPMD$t).remove();
	}		
	
	function AutoFSDeleteDN(key,id,localmount){
		if(confirm('$remove_connection_ask '+localmount)){
			TMPMD$t=id;
			var XHR = new XHRConnection();
			XHR.appendData('AutoFSDeleteDN',key);
			XHR.appendData('localmount',localmount);		
			XHR.sendAndLoad('$page', 'GET',x_AutoFSDeleteDN);
		}
	}	

</script>


";	
echo $html;	
	
}

function mounts_list_items(){
	$autofs=new autofs();
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$hash=$autofs->automounts_Browse();
	if(file_exists('ressources/usb.scan.inc')){include("ressources/usb.scan.inc");}
	$page=1;
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($hash);
	$data['rows'] = array();	
	$fontsize="30px";
	$cs=0;
	while (list ($localmount, $array) = each ($hash) ){
		$id=md5(serialize($array));
		$delete=imgsimple("delete-42.png",null,"AutoFSDeleteDN('{$array["DN"]}','$id','$localmount')");
		if(preg_match("#\{device\}:(.+)#",$array["SRC"],$re)){
			$uuid=$re[1];
			$ligne=$_GLOBAL["usb_list"][$uuid];
			$TYPE=$ligne["TYPE"];
			$ID_MODEL=$ligne["ID_MODEL"];
			$LABEL=$ligne["LABEL"];
			$DEV=$ligne["DEV"];
			if($LABEL==null){$LABEL=$ID_MODEL;}
			if($LABEL==null){$LABEL=$uuid;}
			$SIZE=explode(";",$ligne["SIZE"]);	
			$array["SRC"]="{device}: $LABEL ({$SIZE[0]})";
		}
		if($_GET["field"]<>null){$addform="&target-dir={$_GET["field"]}&select-dir=yes";}
		$browsejs="Loadjs('tree.php?mount-point=/automounts/$localmount$addform')";
		$array["SRC"]=$tpl->_ENGINE_parse_body($array["SRC"]);
		
		if($_GET["field"]<>null){
			
			$delete=imgsimple("arrow-blue-left-32.png",null,$browsejs);		
			
			
			
		}
		
		
		
		
		$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:$fontsize'>{$array["FS"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$array["SRC"]}</a></span>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:$browsejs\" style='font-size:$fontsize;text-decoration:underline'>/automounts/$localmount</a></span>",
		"<center>$delete</center>" )
		);	

		$cs++;
		if($cs>$_POST["rp"]){break;}		
		
	
	}
	
	
		$data['total'] = $cs;
		echo json_encode($data);
		return;		
	
}


function mounts_list_old(){
	$autofs=new autofs();
	$page=CurrentPageName();
	$tpl=new templates();		
	$hash=$autofs->automounts_Browse();
	if(file_exists('ressources/usb.scan.inc')){include("ressources/usb.scan.inc");}
	$add=imgtootltip("32-plus.png","{add}","Loadjs('$page?form-add-js=yes')");
	$html="
	<div id='AutoFSMountedList'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>$add</th>
		<th>{proto}</th>
		<th>{source}</th>
		<th>{local_directory_name}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		

	while (list ($localmount, $array) = each ($hash) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		$delete=imgtootltip("delete-32.png","{delete}","AutoFSDeleteDN('{$array["DN"]}')");
		if(preg_match("#\{device\}:(.+)#",$array["SRC"],$re)){
			$uuid=$re[1];
			$ligne=$_GLOBAL["usb_list"][$uuid];
			$TYPE=$ligne["TYPE"];
			$ID_MODEL=$ligne["ID_MODEL"];
			$LABEL=$ligne["LABEL"];
			$DEV=$ligne["DEV"];
			if($LABEL==null){$LABEL=$ID_MODEL;}
			if($LABEL==null){$LABEL=$uuid;}
			$SIZE=explode(";",$ligne["SIZE"]);	
			$array["SRC"]="{device}: $LABEL ({$SIZE[0]})";
		}	
		
		
		$html=$html . "
		<tr  class=$classtr>
			<td width=1%><img src='img/net-drive-32.png'></td>
			<td width=1% align='center' nowrap><strong style='font-size:14px'><code style='color:$color'>{$array["FS"]}</code></td>
			<td width=99% align='left'><strong style='font-size:14px'>{$array["SRC"]}</strong></td>
			<td width=1% align='left' nowrap><strong style='font-size:11px'>/automounts/$localmount</strong></td>
			<td width=1%>$delete</td>
		</tr>";		
	}
	
	$html=$html."</tbody></table></div>
	<script>

	var x_AutoFSDeleteDN= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_autofs');
	}		
	
	function AutoFSDeleteDN(key){
		var XHR = new XHRConnection();
		XHR.appendData('AutoFSDeleteDN',key);	
		document.getElementById('AutoFSMountedList').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_AutoFSDeleteDN);
		}	

	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}
function mounts_delete(){
	$ldap=new clladp();
	include_once('ressources/class.samba.inc');
	$auto=new autofs();
	$auto->automounts_Browse();
	
	$INFOS=$auto->hash_by_dn[$_GET["AutoFSDeleteDN"]];
	$FOLDER=$INFOS["FOLDER"];
	$fullpath="/automounts/$FOLDER";
	$FOLDER=addslashes($FOLDER);
	$q=new mysql();
	$sql="DELETE FROM automount_davfs WHERE local_dir='$FOLDER'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$sql="DELETE FROM xapian_folders WHERE autmountdn='{$_GET["AutoFSDeleteDN"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
	$samba=new samba();
	if(isset($samba->main_shared_folders[$fullpath])){
		unset($samba->main_array[$samba->main_shared_folders[$fullpath]]);
		$samba->SaveToLdap();
	}
	
	if(!$ldap->ldap_delete($_GET["AutoFSDeleteDN"])){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;
	}
	
	$sock=new sockets();
	$sock->getFrameWork("autofs.php?autofs-reload=yes");	
	
}

function mounts_events(){
	$page=CurrentPageName();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$event=$tpl->_ENGINE_parse_body("{event}");
	$local_directory_name=$tpl->_ENGINE_parse_body("{local_directory_name}");
	$title=$tpl->_ENGINE_parse_body($title);
	$users=new usersMenus();
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$domain=$tpl->javascript_parse_text("{domain}");
	$search=$tpl->_ENGINE_parse_body("{search}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_computer}");
	$add_mount_point=$tpl->_ENGINE_parse_body("{add_mount_point}");
	$remove_connection_ask=$tpl->_ENGINE_parse_body("{remove_connection_ask}");
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$add_mount_point</strong>', bclass: 'add', onpress : add_mount_point$t},
	],";
	
	$buttons=null;
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	var TMPMD$t='';
	$('#flexRT$t').flexigrid({
	url: '$page?syslog-table=yes',
		dataType: 'json',
		colModel : [
			{display: '<span style=font-size:18px>$date</span>', name : 'proto', width : 215, sortable : false, align: 'left'},
			{display: '<span style=font-size:18px>$event</span>', name : 'source', width :1209, sortable : true, align: 'left'},
		],
		$buttons
		searchitems : [
			{display: '$search', name : 'search'},

	
			],
			sortname: 'hostname',
			sortorder: 'asc',
			usepager: true,
			title: '',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '99%',
			height: 550,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
</script>
";
			echo $html;
}

function mounts_events_query(){
	$tpl=new templates();
	$pattern=base64_encode($_POST["query"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?syslog-query=$pattern&prefix=automount&rp={$_POST["rp"]}");
	$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
	if(!is_array($array)){json_error_show("no data");}
	$fontsize="16px";
	$cs=0;
	krsort($array);
	while (list ($ey, $line) = each ($array) ){
		$id=md5($line);
		
		if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[[0-9]+\]:\s+(.+)#", $line,$re)){continue;}
		
		$xdate=strtotime(date("Y-m")."-{$re[2]} {$re[3]}");
		$zdate=$tpl->time_to_date($xdate,true);
		$event=$re[4];
		
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<span style='font-size:$fontsize'>$zdate</a></span>",
						"<span style='font-size:$fontsize'>$event</span>",
						
						 )
		);
	
		$cs++;
		if($cs>$_POST["rp"]){break;}
	
	
	}
	
	
	$data['total'] = $cs;
	echo json_encode($data);
	return;
	
	
	
	
	
}

