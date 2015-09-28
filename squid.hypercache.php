<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


if(isset($_GET["service-status"])){ServiceStatus();exit;}
if(isset($_POST["HyperCacheStoreID"])){HyperCacheStoreID();exit;}
if(isset($_GET["websites-js"])){websites_js();exit;}
if(isset($_GET["websites-popup"])){websites_popup();exit;}

// --hypercachestoreid
popup();


function websites_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	$title="{websites}";
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(400,'$page?websites-popup=yes','$title')";
	
}


function websites_popup(){
	$sock=new sockets();
	$HyperCacheWebsitesList=$sock->GET_INFO("HyperCacheWebsitesList");
	$HyperCacheWebsitesList_json=json_decode($HyperCacheWebsitesList);
	echo "<ul>";
	foreach($HyperCacheWebsitesList_json as $key) {
		echo "<li style='font-size:18px'>
			<a href='http://$key->url'  style='text-decoration:underline' target=_new>$key->site</a></li>";
		
		
	
	
	}	
	echo "</ul>";
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$squid=new squidbee();
	$users=new usersMenus();
	$t=time();
	$HyperCacheStoreID=intval($sock->GET_INFO("HyperCacheStoreID"));
	$HyperCacheStoreIDLicense=$sock->GET_INFO("HyperCacheStoreIDLicense");
	$HyperCacheMemEntries=intval($sock->GET_INFO("HyperCacheMemEntries"));
	$HyperCacheBuffer=intval($sock->GET_INFO("HyperCacheBuffer"));
	$HyperCacheLicStatus=unserialize($sock->GET_INFO("HyperCacheLicStatus"));
	$HyperCacheLicensedMode=intval($sock->GET_INFO("HyperCacheLicensedMode"));
	if($HyperCacheMemEntries==0){$HyperCacheMemEntries=500000;}
	if($HyperCacheBuffer==0){$HyperCacheBuffer=50;}
	$error_SSL_BUMP=null;
	$HyperCacheWebsitesList=$sock->GET_INFO("HyperCacheWebsitesList");
	$HyperCacheWebsitesList_json=json_decode($HyperCacheWebsitesList);
	
	foreach($HyperCacheWebsitesList_json as $key) {
		$HyperCacheWebsitesList_AR[$key->url]=$key->site;
		
		
	}
	
	
	$HyperCacheHTTPListenPort=intval($sock->GET_INFO("HyperCacheHTTPListenPort"));
	if(!is_numeric($HyperCacheHTTPListenPort)){$HyperCacheHTTPListenPort=8700;}
	$HyperCacheHTTPListenPortSSL=$sock->GET_INFO("HyperCacheHTTPListenPortSSL");
	if(!is_numeric($HyperCacheHTTPListenPort)){$HyperCacheHTTPListenPort=8700;}
	if(!is_numeric($HyperCacheHTTPListenPortSSL)){$HyperCacheHTTPListenPortSSL=8900;}
	if($HyperCacheHTTPListenPort==0){$HyperCacheHTTPListenPort=8700;}

	$ip=new networking();
	$ipsH=$ip->ALL_IPS_GET_ARRAY();
	unset($ipsH["127.0.0.1"]);
	
	$q=new mysql_squid_builder();
	$eval=null;

	if(count($HyperCacheWebsitesList_AR)){
		$count=count($HyperCacheWebsitesList_AR);
		$HyperCacheWebsitesList_text="<div style='width:100%;text-align:right;margin-top:15px'>
		<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?websites-js=yes');\" style='text-decoration:underline'>$count {supported_websites}</a></div>";
	}
	
	if(isset($HyperCacheLicStatus["expired"])){
		
		$step_text="{license_expired}";
		$textcolor="#C90505";
		
		if($HyperCacheLicStatus["expired"]==0){
			$textcolor="#23A83E";
			$step_text="{license_active}";
		}
		
		if(intval($HyperCacheLicStatus["edate"])>10){
			$t=time();
			$seconds_restantes=intval($HyperCacheLicStatus["edate"])- $t;
			$minutes_restantes=$seconds_restantes/60;
			$heures=$minutes_restantes/60;
			$jours=$heures/24;
			if($jours<31){$eval=" - {evaluation_mode}&nbsp;";}
			$jours=round($jours);
			$dateexp=$q->time_to_date($HyperCacheLicStatus["edate"])." ".date("Y",$HyperCacheLicStatus["edate"]);
			
			
			
			$licestatus="<table style='margin-top:10px;margin-bottom:10px'>
			<tr>
				<td valign='top' nowrap><img src='img/license-64.png'></td>
				<td style='padding-left:15px;font-size:18px'>{hypercache_license}:&nbsp;
				<span style='color:$textcolor'>$step_text</span>&nbsp;&nbsp;<i style='font-size:18px'>{expiredate}:&nbsp;$dateexp ($jours {days}$eval)</i></td>
			</tr>
			</table>";
		}
	}
	
	
	
	
	$SSL_BUMP=$squid->SSL_BUMP;
	
	
	if($SSL_BUMP==0){
		$error_SSL_BUMP=
		"<table style='margin-top:10px;margin-bottom:10px'>
		<tr>
			<td valign='top' nowrap><img src='img/warning-panneau-64.png'></td>
			<td style='padding-left:15px;font-size:18px'>{warn_videocache_nossl}</td>
		</tr>
		</table>";
	}
	
	
	$html="<table style='width:100%'>
	<tr>
		<td style='width:240px;vertical-align:top'><div id='status-hypercache-testa'></div></td>
		<td style='width:99%'>$error_SSL_BUMP$licestatus
			<div style='width:98%' class=form>
			". Paragraphe_switch_img("{HYPERCACHE_STOREID}", "{HYPERCACHE_STOREID_EXPLAIN}","HyperCacheStoreID",
					$HyperCacheStoreID,null,1160)."
			". Paragraphe_switch_img("{use_licensed_plugin}", "{HYPERCACHE_LICENSED_EXPLAIN}$HyperCacheWebsitesList_text","HyperCacheLicensedMode",
					$HyperCacheLicensedMode,null,1160)."							
							
			$HyperCacheLicensedMode
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:26px'>{hypercache_license}:</td>
					<td>".Field_text("HyperCacheStoreIDLicense", $HyperCacheStoreIDLicense,"font-size:26px;width:520px")."</td>
					<td>&nbsp;</td>
				</tr>
			<tr><td colspan=3 align='right'><hr>".button("{apply}","Save$t()",30)."</td></tr>
		</table>
		</div>
	</td>
</tr>
</table>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	Loadjs('squid.hypercache.progress.php');
}

function Save$t(){
	var XHR = new XHRConnection();
	var EnableSquidCacheBoosters=0;
	XHR.appendData('HyperCacheStoreIDLicenseLastLic','$HyperCacheStoreIDLicense');
	XHR.appendData('HyperCacheStoreID',document.getElementById('HyperCacheStoreID').value);
	XHR.appendData('HyperCacheStoreIDLicense',document.getElementById('HyperCacheStoreIDLicense').value);
	XHR.appendData('HyperCacheLicensedMode',document.getElementById('HyperCacheLicensedMode').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

LoadAjax('status-hypercache-testa','$page?service-status=yes');

</script>
";


echo $tpl->_ENGINE_parse_body($html);
}

function HyperCacheStoreID(){
	$sock=new sockets();
	$sock->SET_INFO("HyperCacheStoreID", $_POST["HyperCacheStoreID"]);
	$sock->SET_INFO("HyperCacheStoreIDLicenseLastLic", $_POST["HyperCacheStoreIDLicenseLastLic"]);
	$sock->SET_INFO("HyperCacheStoreIDLicense", $_POST["HyperCacheStoreIDLicense"]);
	$sock->SET_INFO("HyperCacheLicensedMode", $_POST["HyperCacheLicensedMode"]);
	
	
}

function ServiceStatus(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$data=$sock->getFrameWork('cmd.php?hypercachestoreid-ini-status=yes');
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/hypercache.status");
	$APP_UFDBCAT=DAEMON_STATUS_ROUND("HYPERCACHE_STOREID",$ini,null,1);
	$APP_HYPERCACHE_TAIL=$tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("APP_HYPERCACHE_TAIL",$ini,null,1));
	echo $tpl->_ENGINE_parse_body("
			$APP_UFDBCAT<br>$APP_HYPERCACHE_TAIL
			<div style='margin-top:10px;text-align:right;width:100%'><div style='float:right'>".
			imgsimple("refresh-32.png",null,"LoadAjax('status-hypercache-testa','$page?service-status=yes');")."</div></div>");
}