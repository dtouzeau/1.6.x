<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ldap.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["status"])){status();exit;}
if(isset($_POST["EnableeCapGzip"])){Save();exit;}
tabs();


function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	if(!is_file("/usr/lib/ecap_adapter_gzip.so")){
		echo FATAL_ERROR_SHOW_128("<div style='font-size:26px'>{ERROR_MISSING_MODULE_UPDATE_PROXY}</div>
				<center style='font-size:22px;margin:30px;font-weight:bold'>3.5.8-20150910-r13912 {or_above}</div>
				<p style='font-size:42px;text-align:right;margin-top:30px'>".texttooltip("{update_proxy_engine}","position:top:{proxy_engine_available_explain}","javascript:LoadProxyUpdate();")."</p>");
		die();
		
	}
	
	$tpl=new templates();
	$array["status"]='{status}';
	
	
	
	
	$fontsize="22";
	
	while (list ($num, $ligne) = each ($array) ){
	
			if($num=="exclude"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"squid.hosts.blks.php?popup=yes&blk=6\" style='font-size:{$fontsize}'>
					<span>$ligne</span></a></li>\n");
					continue;
		}
		
		if($num=="exclude-www"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.wwwex.php\" style='font-size:{$fontsize}'>
							<span style='font-size:{$fontsize}'>$ligne</span></a></li>\n");
							continue;
		}
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:{$fontsize}px;'>
				<span style='font-size:{$fontsize}px;'>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_ecapGzip_tabs',1490)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	
			echo $html;
	
}

function status(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableeCapGzip=intval($sock->GET_INFO("EnableeCapGzip"));
	
	
	$p=Paragraphe_switch_img("{http_compression}", "{http_compression_explain}","EnableeCapGzip",$EnableeCapGzip,null,1400);
	
	$html="<div style='width:98%' class=form>
	$p
	<div style='width:100%;margin-top:20px;text-align:right'><hr>". button("{apply}","Save$t()",40)."</div>
	</div>
<script>
var x_Save$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Loadjs('squid.ecap.progress.php');
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableeCapGzip', document.getElementById('EnableeCapGzip').value);
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
}
</script>			
			
";
echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableeCapGzip", $_POST["EnableeCapGzip"]);
	
}
