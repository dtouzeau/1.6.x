<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}
	if(isset($_GET["remove-cache-js"])){remove_cache_js();exit;}
	if(isset($_GET["remove-cache"])){remove_cache_button();exit;}
	if(isset($_POST["remove-cache"])){remove_cache();exit;}
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["graph1"])){graph1();exit;}
	if(isset($_GET["graph2"])){graph2();exit;}
	if(isset($_GET["table1"])){table1();exit;}
	if(isset($_GET["table3"])){table3();exit;}
	if(isset($_GET["graph3"])){graph3();exit;}	


	

	
page();

function stats_requeteur(){
	$tpl=new templates();
	$page=CurrentPageName();

	$ahref_sys="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('$page?requeteur-js=yes&t={$_GET["t"]}')\">";
	echo $tpl->_ENGINE_parse_body("$ahref_sys{build_the_query}</a>");
}
function requeteur_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$build_the_query=$tpl->javascript_parse_text("{build_the_query}::{flow}");
	echo "YahooWin('670','$page?requeteur-popup=yes&t={$_GET["t"]}','$build_the_query');";
}


function build_query_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	$interval=$_GET["interval"];
	$t=$_GET["t"];
	$user=$_GET["user"];
	$md5=md5("FLOW:$from$to$interval$user");
	$_SESSION["SQUID_STATS_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_TIME1"]=$_GET["time1"];
	
	$_SESSION["SQUID_STATS_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_TIME2"]=$_GET["time2"];
	
	
	$timetext1=$tpl->time_to_date(strtotime("{$_GET["date1"]} {$_GET["time1"]}"),true);
	$timetext2=$tpl->time_to_date(strtotime("{$_GET["date2"]} {$_GET["time2"]}"),true);
	
	
	$nextFunction="Loadjs('$page?graph1=yes&t=$t&container=graph-$t&zmd5=$md5&t=$t');";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,builded FROM reports_cache WHERE `zmd5`='$md5'"));
	if(intval($ligne["ID"])==0){
		$array["FROM"]=$from;
		$array["TO"]=$to;
		$array["INTERVAL"]=$interval;
		$array["USER"]=$user;
		$serialize=mysql_escape_string2(serialize($array));
		$title="{flow}: $timetext1 - {to} $timetext2 $interval - $user";
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES 
		('$md5','$title','FLOW',NOW(),'$serialize')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('". $tpl->javascript_parse_text($q->mysql_errror)."')";return;}
		echo "Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded')";
		return;
	}
	
	if(intval($ligne["builded"]==0)){
echo "
function Start$t(){
	Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded&t=$t');
}

if(document.getElementById('graph-$t')){
	document.getElementById('graph-$t').innerHTML='<center><img src=img/loader-big.gif></center>';
}
LockPage();	
setTimeout('Start$t()',800);
";



return;
}
	
	echo $nextFunction;
	
}
function remove_cache_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,`title` FROM `reports_cache` WHERE `zmd5`='$zmd5'"));
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return;}
	$title=$tpl->javascript_parse_text("{delete} id {$ligne["ID"]} \"{$ligne["title"]}\" ($zmd5)");
	$page=CurrentPageName();
	
	
	$t=time();
echo "
var xLinkEdHosts$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){ alert(res); return; }
	
	
	if( document.getElementById('BROWSE_STATISTICS_CACHES2') ){
		$('#BROWSE_STATISTICS_CACHES2').flexReload();
	}	
	if( document.getElementById('BROWSE_STATISTICS_CACHES') ){
		$('#BROWSE_STATISTICS_CACHES').flexReload();
	}
	
	
}
	
	
function LinkEdHosts$t(){
	if(!confirm('$title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('remove-cache','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xLinkEdHosts$t);
}
LinkEdHosts$t();
" ;
}
function remove_cache(){
	$zmd5=$_POST["remove-cache"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='{$_POST["remove-cache"]}'");
	$tpl=new templates();
	$table="chronos$zmd5";
	if($q->TABLE_EXISTS($table)){
		$q->QUERY_SQL("DROP TABLE `$table`");
	}
}

function requeteur_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	squid_stats_default_values();
	$t=$_GET["t"];
	
	$per["1h"]="1 {hour}";
	$per["1d"]="1 {day}";
	$per["1w"]="1 {week}";
	$per["30d"]="1 {month}";
	
	
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	
	
	$q=new influx();
	$date_start=date("Y-m-d",intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_START")));
	$date_end=date("Y-m-d",intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_END")));
	$Selectore="mindate:$date_start;maxdate:$date_end";
	
	
	$stylelegend="style='vertical-align:top;font-size:18px;padding-top:5px' nowrap";
	$html="<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
		<td $stylelegend class=legend>{interval}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($per,"interval-$t","10m","blur()",null,0,"font-size:18px;")."</td>
	</tr>
	<tr>
		<td $stylelegend class=legend>{members}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($members,"members-$t",$_SESSION["SQUID_STATS_MEMBER"],"blur()",null,0,"font-size:18px;")."</td>
	</tr>
	<tr>
		<td $stylelegend class=legend>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_DATE1"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;".Field_text("from-time-$t",$_SESSION["SQUID_STATS_TIME1"],";font-size:18px;width:82px")."</td>
		
	</tr>
		<td $stylelegend class=legend>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_DATE2"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("to-time-$t",$_SESSION["SQUID_STATS_TIME2"],";font-size:18px;width:82px")."</td>
	</tr>
	<tr>	
		<td style='vertical-align:top;font-size:18px;' colspan=2 align='right'>". button("{generate_statistics}","Run$t()",18)."</td>
	</tr>
	</table>
	</div>
<script>
function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var user=document.getElementById('members-$t').value;
	var interval=document.getElementById('interval-$t').value;
	Loadjs('$page?query-js=yes&t=$t&container=graph-$t&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval+'&user='+user);

}
</script>
";	
	
echo $tpl->_ENGINE_parse_body($html);
	
	
}



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=null;
	
	echo "<div style='float:right;margin:5px;margin-top:5px'>".button($tpl->_ENGINE_parse_body("{build_the_query}"), "Loadjs('$page?requeteur-js=yes&t=$t')",16)."</div>";
	$content="<center style='margin:50px'>". button("{build_the_query}","Loadjs('$page?requeteur-js=yes&t=$t')",42)."</center>";
	
	
	if($_GET["zmd5"]==null){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE report_type='FLOW' ORDER BY zDate DESC LIMIT 0,1"));
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE zmd5='{$_GET["zmd5"]}'"));
		
	}
	
	
	if($ligne["zmd5"]<>null){
		$nextFunction="Loadjs('$page?graph1=yes&t=$t&container=graph-$t&zmd5={$ligne["zmd5"]}&t=$t');";
		$content="<center><img src=img/loader-big.gif></center>";
		$title="<div style='font-size:30px;margin-bottom:20px'>".$tpl->javascript_parse_text($ligne["title"])."</div>";
	}

	$html="$title<div style='text-align:left' id='button-$t'></div>
	<div style='width:1480px;height:550px;margin-bottom:10px' id='graph-$t'>
	$content
	
	</div>	
	
	
	<table style='width:100%'>
	<tr>
		<td width=90%>		
			<div style='width:800px;height:550px' id='graph2-$t'></div>
		</td>
		<td style='width:5%;vertical-align:top'>		
			<div id='table1-$t'></div>
		</td>
	</tr>
	<tr>
		<td colspan=2><p>&nbsp;</p></td>
	</tr>
	<tr>
		<td width=90%>		
			<div style='width:800px;height:550px' id='graph3-$t'></div>
		</td>
		<td style='width:5%;vertical-align:top'>		
			<div  id='table3-$t'></div>
		</td>
	</tR>
</table>	
	
	
<script>
	LoadAjaxTiny('stats-requeteur','$page?stats-requeteur=yes&t=$t');
	$nextFunction
</script>";
	
echo $tpl->_ENGINE_parse_body($html);
		
}
function graph1(){
	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	
	if(!isset($MAIN["GRAPH1"])){
		echo "alert('Corrupted data...{$ligne["values"]}');UnlockPage();";
		$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");
		return;
	}
	
	$page=CurrentPageName();
	$time=time();
	
	$xdata=$MAIN["GRAPH1"]["xdata"];
	$ydata=$MAIN["GRAPH1"]["ydata"];
	
	
	
	
	$title="{downloaded_flow} (MB)";
	$timetext=$_GET["interval"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title=$title;
	//$highcharts->subtitle="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.rtt.php')\" style='font-size:16px;text-decoration:underline'>{realtime_flow}</a>";
	$highcharts->TitleFontSize="14px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle="MB";
	$highcharts->xAxis_labels=true;
	
	$highcharts->LegendSuffix="MB";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();
	echo "\nLockPage();\nLoadjs('$page?graph2=yes&zmd5=$zmd5&t={$_GET["t"]}&container=graph2-{$_GET["t"]}')\n";
	echo "LoadAjax('button-{$_GET["t"]}','$page?remove-cache=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";

}	

function graph2(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');UnlockPage();";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	
	$PieData=$MAIN["GRAPH2"]["PIEDATA"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{websites}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{websites}/{size} (MB)");
	echo $highcharts->BuildChart();
	echo "LoadAjax('table1-{$_GET["t"]}','$page?table1=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";
	
	
}

function remove_cache_button(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$button_browse=null;
	$button_empty=null;
	$sql="SELECT COUNT(ID) as tcount,report_type FROM `reports_cache` GROUP BY report_type HAVING `report_type`='FLOW'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error_html();}
	
	if(intval($ligne["tcount"])>0){
		$button_browse=$tpl->_ENGINE_parse_body(button("{browse_cache}",
				"Loadjs('squid.statistics.browse-cache.php?report_type=FLOW')",16));
	}
	
	$button_empty=$tpl->_ENGINE_parse_body(button("{empty_cache}","Loadjs('$page?remove-cache-js=yes&zmd5={$_GET["zmd5"]}')",16));
	echo "<table><tr><td nowrap>$button_browse</td><td>&nbsp;</td><td>$button_empty</td></tr></table>";
}

function graph3(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$MAIN=unserialize(base64_decode($values));
	
	$highcharts=new highcharts();
	$highcharts->container="graph3-{$_GET["t"]}";
	$highcharts->PieDatas=$MAIN["GRAPH3"]["PIEDATA"];
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{$MAIN["GRAPH3"]["TYPE"]}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{$MAIN["GRAPH3"]["TYPE"]}/{size} (MB)");
	echo $highcharts->BuildChart();	
	echo "LoadAjax('table3-{$_GET["t"]}','$page?table3=yes&zmd5=$zmd5&t={$_GET["t"]}');\n";
	
}

function table3(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$MAIN=unserialize(base64_decode($values));
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{members}</td><th style='font-size:18px'>{size}</td></tr>");
	while (list ($site, $size) = each ($MAIN["GRAPH3"]["TABLE"]) ){
		$js="Loadjs('squid.statistics.report.member.php?from-zmd5=$zmd5&USER_DATA=".urlencode($site)."');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;text-decoration:underline'>";
		
		
		if($size>1024){$size=FormatBytes($size/1024);}else{$size="$size Bytes";}
		$html[]="<tr><td style='font-size:18px;padding:8px'>$href$site</a></td><td style='font-size:18px'>$size</td></tr>";
	}
	
	$html[]="</table>";
	$html[]="<script>";
	$html[]="UnlockPage()";
	$html[]="</script>";
	echo @implode("", $html);
		
	
	
}

function table1(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$MAIN=unserialize(base64_decode($values));
	
	$html[]="<table style='width:100%'>";
	$html[]=$tpl->_ENGINE_parse_body("<tr><th style='font-size:18px;padding:8px'>{websites}</td><th style='font-size:18px'>{size}</td></tr>");
	while (list ($site, $size) = each ($MAIN["GRAPH2"]["TABLE"]) ){
		if($size>1024){$size=FormatBytes($size/1024);}else{$size="$size Bytes";}
		
		
		
		
		$html[]="<tr><td style='font-size:18px;padding:8px'>$site</a></td><td style='font-size:18px'>$size</td></tr>";
	}
		
	$html[]="</table>";
	$html[]="<script>";
	$html[]="Loadjs('$page?graph3=yes&zmd5={$_GET["zmd5"]}&t={$_GET["t"]}')";
	$html[]="</script>";
	echo @implode("", $html);
	
	
}