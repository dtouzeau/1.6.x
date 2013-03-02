<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.spamassassin.inc');
include_once('ressources/class.amavis.inc');

session_start();
$ldap=new clladp();
if(isset($_GET["loadhelp"])){loadhelp();exit;}

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_POST["white-list-host-del"])){hosts_WhiteList_del();exit;}
	if(isset($_POST["white-list-host"])){hosts_WhiteList_add();exit;}
	if(isset($_GET["list-table"])){list_table();exit;}
	
	
	page();
function page(){

		$t=time();
		$page=CurrentPageName();
		$tpl=new templates();
		$users=new usersMenus();
		$sock=new sockets();
		$tt=$_GET["t"];
		$t=time();
		$q=new mysql();
		$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
		$delete=$tpl->javascript_parse_text("{delete}");
		$items=$tpl->_ENGINE_parse_body("{items}");
	
		$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
		$new_item=$tpl->_ENGINE_parse_body("{new_item}");
		$import=$tpl->_ENGINE_parse_body("{import}");
		$title=$tpl->_ENGINE_parse_body("{PostfixAutoBlockDenyAddWhiteList_explain}");
		$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
		$hostname=$tpl->_ENGINE_parse_body("{hostname}");
		$delete=$tpl->_ENGINE_parse_body("{delete}");
	
		$buttons="
		buttons : [
		{name: '$new_item', bclass: 'add', onpress : AddHostWhite$t},
		],";

	
		$html="
	
	
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
	
		<script>
		var memid$t='';
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?list-table=yes&t=$t',
		dataType: 'json',
		colModel : [
		
		{display: '$ipaddr', name : 'ipaddr', width : 238, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :491, sortable : true, align: 'left'},
		{display: '$delete', name : 'bounce_error', width : 31, sortable : false, align: 'left'},
		],
		$buttons
		searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		
		],
		sortname: 'ipaddr',
		sortorder: 'asc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: 840,
		height: 650,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	var x_AddHostWhite$t=function(obj){
    	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
 	  	$('#flexRT$t').flexReload();
      }	
	var x_DelHostWhite$t=function(obj){
    	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
 	  	$('#row'+memid$t).remove();
      }		
	
	function AddHostWhite$t(){
		var server=prompt('$title\\n10.10.10.0/24\\n192.168.1.1\\nhost.domain.tld\\nhost.*.tld');
		if(server){
			var XHR = new XHRConnection();
			XHR.appendData('white-list-host',server);
			XHR.sendAndLoad('$page', 'POST',x_AddHostWhite$t);
			}
		}
		
	function DelHostWhite$t(ip,server,id){
		memid$t=id;
		if(!confirm('$are_you_sure_to_delete '+server+'['+ip+']')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('white-list-host-del',server);
		XHR.appendData('white-list-host-delip',ip);
		XHR.sendAndLoad('$page', 'POST',x_DelHostWhite$t);
	}	
	
	</script>";
		echo $html;
	}
	
	
function list_table(){
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("postfix_whitelist_con", "artica_backup")){$q->BuildTables();}
	$table="postfix_whitelist_con";
	$t=$_GET["t"];
	$database="artica_backup";
	$FORCE_FILTER=1;
	
	
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("!Error: $table No such table");}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			if(!$q->ok){json_error_show("$q->mysql_error");}
			$total = $ligne["TCOUNT"];
			if($total==0){json_error_show("No rows for $searchstring");}
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		
	
		$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";
	
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql,$database);
		if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
		$today=date('Y-m-d');
		$style="font-size:14px;";
		
		$unknown=$tpl->_ENGINE_parse_body("{unknown}");
		while ($ligne = mysql_fetch_assoc($results)) {
	
			
			$md=md5(serialize($ligne));
			$cells=array();
			$delete=imgsimple("delete-32.png",null,"DelHostWhite$t('{$ligne["ipaddr"]}','{$ligne["hostname"]}','$md')");
			$cells[]="<span style='font-size:18px;'>{$ligne["ipaddr"]}</span>";
			$cells[]="<span style='font-size:18px;'>{$ligne["hostname"]}</span>";
			$cells[]="<span style='font-size:11px;'>$delete</span>";
				
				
				
			$data['rows'][] = array(
					'id' =>$md,
					'cell' => $cells
			);
	
	
		}
	
		echo json_encode($data);
	}	
function hosts_WhiteList_add(){
		if($_POST["white-list-host"]==null){echo "NULL VALUE";return null;}
	
		$users=new usersMenus();
		$tpl=new templates();
		if(!$users->AsPostfixAdministrator){
			$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
			echo "$error";
			die();
		}
	
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$_POST["white-list-host"])){
			if(strpos($_POST["white-list-host"], "*")==0){
				$ipaddr=gethostbyname($_POST["white-list-host"]);
			}
			$hostname=$_POST["white-list-host"];
		}else{
			$ipaddr=$_POST["white-list-host"];
			$hostname=gethostbyaddr($_POST["white-list-host"]);
		}
	
		$sql="INSERT IGNORE INTO postfix_whitelist_con (ipaddr,hostname) VALUES('$ipaddr','$hostname')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	}
	
function hosts_WhiteList_del(){
		$users=new usersMenus();
		$tpl=new templates();
		if(!$users->AsPostfixAdministrator){
			$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
			echo "$error";
			die();
		}
	
		$found=false;
		$q=new mysql();
		$server=$_POST["white-list-host-del"];
		if($server<>nulll){
			$sql="DELETE FROM postfix_whitelist_con WHERE ipaddr='$server'";
			$q->QUERY_SQL($sql,"artica_backup");
			$sql="DELETE FROM postfix_whitelist_con WHERE hostname='$server'";
			$q->QUERY_SQL($sql,"artica_backup");
		}
		if(trim($_POST["white-list-host-delip"])<>null){
			$sql="DELETE FROM postfix_whitelist_con WHERE ipaddr='{$_POST["white-list-host-delip"]}'";
			$q->QUERY_SQL($sql,"artica_backup");
			$sql="DELETE FROM postfix_whitelist_con WHERE hostname='{$_POST["white-list-host-delip"]}'";
			$q->QUERY_SQL($sql,"artica_backup");			
		}
		
		
		
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	
	}	