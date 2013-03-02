<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		echo "<H2>$alert</H2>";
		die();	
	}
	
	if(isset($_POST["aclname-save"])){acl_rule_save();exit;}
	if(isset($_GET["acls-list"])){acl_list();exit;}
	if(isset($_GET["Addacl-js"])){acl_js();exit;}
	if(isset($_GET["acl-rule-tabs"])){acl_rule_tab();exit;}
	if(isset($_GET["acl-rule-settings"])){acl_rule_settings();exit;}
	if(isset($_POST["acl-rule-delete"])){acl_rule_delete();exit;}
	if(isset($_POST["acl-rule-enable"])){acl_rule_enable();exit;}
	if(isset($_POST["acl-rule-move"])){acl_rule_move();exit;}
	if(isset($_POST["acl-rule-order"])){acl_rule_order();exit;}
	if(isset($_POST["aclrulename"])){acl_main_rule_edit();exit;}
	if(isset($_POST["ApplySquid"])){squid_compile();exit;}
	if(isset($_GET["csv"])){output_scv();exit;}
	page();
	
	
function acl_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=-1;}
	if($ID<1){
		$title=$tpl->javascript_parse_text("{new_rule}");
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM webfilters_sqacls WHERE ID='$ID'"));
		$title=utf8_encode($ligne["aclname"]);
	}
	$t=time();
	$html="var x_aclCallBack= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-{$_GET["t"]}').flexReload();
	}	
	
	
	function AclCreateNewAclRule(){
		var aclname=prompt('$title','New Rule...');
		if(!aclname){return;}
		var XHR = new XHRConnection();
		XHR.appendData('aclname-save', aclname);
		XHR.appendData('ID', '$ID');		      
		XHR.sendAndLoad('$page', 'POST',x_aclCallBack);  			
	}
	
	function aclShowMainStart(){
		var ID=$ID;
		if( ID<0 ){ AclCreateNewAclRule(); return; }
		YahooWin2(650,'$page?acl-rule-tabs=yes&ID=$ID&t={$_GET["t"]}','$title');
	
	}
	aclShowMainStart();
	";
	header("content-type: application/javascript");
	echo $html;
	
	
}

function acl_rule_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	
	$array["acl-rule-settings"]='{settings}';
	$array["acl-items"]='{items}';
	
	

	while (list ($num, $ligne) = each ($array) ){
		if($num=="acl-items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"squid.acls-rules.items.php?$num=yes&aclid=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&t=$t\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo "
	<div id=main_acl_rule_zoom style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_acl_rule_zoom').tabs();
			
			
			});
		</script>";	
}


function acl_rule_settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];	
	$t=time();
	$please_choose_a_bandwith_rule=$tpl->javascript_parse_text("{please_choose_a_bandwith_rule}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,acltpl FROM webfilters_sqacls WHERE ID='$ID'"));
	$aclname=utf8_encode($ligne["aclname"]);
	$acltpl=$ligne["acltpl"];
	$squid=new squidbee();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='url_rewrite_access_deny'"));
	$url_rewrite_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($url_rewrite_access_deny)){$url_rewrite_access_deny=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='access_deny'"));
	$access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($access_deny)){$access_deny=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='adaptation_access_deny'"));
	$adaptation_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($adaptation_access_deny)){$adaptation_access_deny=0;}		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='cache_deny'"));
	$cache_deny=$ligne["httpaccess_value"];
	if(!is_numeric($cache_deny)){$cache_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='access_allow'"));
	$access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($access_allow)){$access_allow=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='snmp_access_allow'"));
	$snmp_access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($snmp_access_allow)){$snmp_access_allow=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='log_access'"));
	$log_access=$ligne["httpaccess_value"];
	if(!is_numeric($log_access)){$log_access=0;}
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_access_except'"));
	$deny_access_except=$ligne["httpaccess_value"];
	if(!is_numeric($deny_access_except)){$deny_access_except=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='tcp_outgoing_tos'"));
	$tcp_outgoing_tos=$ligne["httpaccess_value"];
	$tcp_outgoing_tos_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_tos)){$tcp_outgoing_tos=0;}	
	if($tcp_outgoing_tos_value==null){$tcp_outgoing_tos_value="0x20";}	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='delay_access'"));
	$delay_access=$ligne["httpaccess_value"];
	$delay_access_id=$ligne["httpaccess_data"];
	if(!is_numeric($delay_access)){$delay_access=0;}	
	if(!is_numeric($delay_access_id)){$delay_access_id=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='tcp_outgoing_address'"));
	$tcp_outgoing_address=$ligne["httpaccess_value"];
	$tcp_outgoing_address_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_address)){$tcp_outgoing_address=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_quota_rule'"));
	$deny_quota_rule=$ligne["httpaccess_value"];
	$deny_quota_rule_id=$ligne["httpaccess_data"];
	if(!is_numeric($deny_quota_rule)){$deny_quota_rule=0;}	
	if($deny_quota_rule_id>0){
		$q3=new mysql();
		$ligne3=mysql_fetch_array($q3->QUERY_SQL("SELECT QuotaName FROM ext_time_quota_acl WHERE ID=$deny_quota_rule_id","artica_backup"));
		$deny_quota_rule_value=$ligne3["QuotaName"];
	}
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_log'"));
	$deny_log=$ligne["httpaccess_value"];
	if(!is_numeric($deny_log)){$deny_log=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='request_header_add'"));
	$request_header_add=$ligne["httpaccess_value"];
	$request_header_add_value=unserialize(base64_decode($ligne["httpaccess_data"]));
	if(!is_numeric($request_header_add)){$request_header_add=0;}else{
		$request_header_add_name=$request_header_add_value["header_name"];
		$request_header_add_value=$request_header_add_value["header_value"];
	}	
	
	
	$is33=0;
	$explain_no33squid="{explain_no33squid}: $squid->SQUID_VERSION";
	if($squid->IS_33){
		$is33=1;
		$explain_no33squid=null;
	}
	
	
	
	
	
	
	if($acltpl==null){$acltpl="{default}";}
	
	
	else{
			$md5=$acltpl;
			$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$acltpl}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$acltpl=addslashes($ligne2["template_title"]);
			$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($acltpl)."');";
			$acltpl="<a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:14px;text-decoration:underline'>$acltpl</a>";
		
	}
	
	if($delay_access_id>0){
		$q2=new mysql();
		$sql="SELECT rulename FROM squid_pools WHERE ID='$delay_access_id'";
		$ligne=mysql_fetch_array($q2->QUERY_SQL($sql,"artica_backup"));	
		$delay_access_id_text=$ligne["rulename"];
	}
	
	$ipz=new networking();
	$ipss=$ipz->ALL_IPS_GET_ARRAY();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$html="
	<div id='FormToParse$t'>
	<div id='divid$t'></div> 
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{rule_name}:</td>
		<td>". Field_text("aclrulename",$aclname,"font-size:14px;width:220px")."</td>
	</tr>	
	</table>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{allow}:</td>
		<td>". Field_checkbox("access_allow",1,$access_allow,"access_allow_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{deny_access}:</td>
		<td>". Field_checkbox("access_deny",1,$access_deny,"access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{deny_access_except}:</td>
		<td>". Field_checkbox("deny_access_except",1,$deny_access_except,"deny_access_except_check()")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{pass_trough_thewebfilter_engine}:</td>
		<td>". Field_checkbox("url_rewrite_access_deny",1,$url_rewrite_access_deny,"url_rewrite_access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{pass_trough_antivirus_engine}:</td>
		<td>". Field_checkbox("adaptation_access_deny",1,$adaptation_access_deny,"adaptation_access_deny_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{allow_snmp_access}:</td>
		<td>". Field_checkbox("snmp_access_allow",1,$snmp_access_allow,"snmp_access_allow_check()")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:14px'>{do_not_cache}:</td>
		<td>". Field_checkbox("cache_deny",1,$cache_deny,"cache_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{log_to_csv}:</td>
		<td>". Field_checkbox("log_access",1,$log_access,"log_access_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{deny_logging}:</td>
		<td>". Field_checkbox("deny_log",1,$deny_log,"deny_log_check()")."</td>
	</tr>				
	</table>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{limit_bandwidth}:</td>
		<td>". Field_checkbox("delay_access",1,$delay_access,"limit_bandwidth_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{bandwidth}:</td>
		<td>
			<span id='delay_access_id_text' style='font-size:14px;font-weight:bold'>$delay_access_id_text</span>
			<input type='hidden' id='delay_access_id' value='$delay_access_id'>
		</td>
		<td width=1%>". button('{browse}...',"Loadjs('squid.bandwith.php?browser-acl-js=yes&aclruleid=$ID')")."</td>
	</tr>			
	</table>
				
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{affect_quota_rule}:</td>
		<td>". Field_checkbox("deny_quota_rule",1,$deny_quota_rule,"deny_quota_rule_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{quota_rule}:</td>
		<td>
			<span id='deny_quota_rule_id_text' style='font-size:14px;font-weight:bold'>[$deny_quota_rule_id]:$deny_quota_rule_value</span>
			<input type='hidden' id='deny_quota_rule_id' value='$deny_quota_rule_id'>
		</td>
		<td width=1%>". button('{browse}...',"Loadjs('squid.ext_time_quota_acl.php?browser-quota-js=yes&checkbowid=deny_quota_rule&textid=deny_quota_rule_id_text&idnum=deny_quota_rule_id')")."</td>
	</tr>			
	</table>				

	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{request_header_add}:</td>
		<td>". Field_checkbox("request_header_add",1,$request_header_add,"request_header_addCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{header_name}:</td>
		<td>". Field_text("request_header_add_name",$request_header_add_name,'font-size:14px;width:210px')."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{header_value}:</td>
		<td>". Field_text("request_header_add_value",$request_header_add_value,'font-size:14px;width:210px')."</td>
	</tr>					
	</table>
	<div><i style='font-size:11px'>$explain_no33squid</i></div>			
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{tcp_outgoing_tos}:</td>
		<td>". Field_checkbox("tcp_outgoing_tos",1,$tcp_outgoing_tos,"tcp_outgoing_tosCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{tcp_outgoing_tos_value}:</td>
		<td>". Field_text("tcp_outgoing_tos_value",$tcp_outgoing_tos_value,'font-size:14px;width:90px')."</td>
	</tr>	
	</table>
	
	<table style='width:99%' class=form>	
	<tr>
		<td class=legend style='font-size:14px'>{acl_tcp_outgoing_address}:</td>
		<td>". Field_checkbox("tcp_outgoing_address-$t",1,$tcp_outgoing_address,"tcp_outgoing_address_check$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ipaddr}:</td>
		<td>". Field_array_Hash($ipss,"tcp_outgoing_address_value",$tcp_outgoing_address_value,null,null,0,"font-size:14px")."</td>
	</tr>	
	</table>	
	
	
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveAclRule$ID()",16)."</td>
	</tr>
	</table>
	</div>
	
	<script>
	
		var x_SaveAclRule$ID= function (obj) {
			document.getElementById('divid$t').innerHTML='';	
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			RefreshTab('main_acl_rule_zoom');
			$('#table-$t').flexReload();
			
		}
	
		function SaveAclRule$ID(){
			var XHR = new XHRConnection();
			XHR.appendData('aclrulename', document.getElementById('aclrulename').value);
			XHR.appendData('tcp_outgoing_tos_value', document.getElementById('tcp_outgoing_tos_value').value);
			XHR.appendData('tcp_outgoing_address_value', document.getElementById('tcp_outgoing_address_value').value);
			var delay_access_id=document.getElementById('delay_access_id').value;
			
			if(document.getElementById('delay_access').checked){
				if(delay_access_id==0){
					alert('$please_choose_a_bandwith_rule');
					return;
				}
			}
			XHR.appendData('delay_access_id', document.getElementById('delay_access_id').value);
			XHR.appendData('ID', '$ID');
			if(document.getElementById('tcp_outgoing_tos').checked){XHR.appendData('tcp_outgoing_tos', '1');}else{XHR.appendData('tcp_outgoing_tos', '0');}
			if(document.getElementById('access_allow').checked){XHR.appendData('access_allow', '1');}else{XHR.appendData('access_allow', '0');}
			if(document.getElementById('deny_access_except').checked){XHR.appendData('deny_access_except', '1');}else{XHR.appendData('deny_access_except', '0');}
			if(document.getElementById('url_rewrite_access_deny').checked){XHR.appendData('url_rewrite_access_deny', '1');}else{XHR.appendData('url_rewrite_access_deny', '0');}
			if(document.getElementById('access_deny').checked){XHR.appendData('access_deny', '1');}else{XHR.appendData('access_deny', '0');}
			if(document.getElementById('adaptation_access_deny').checked){XHR.appendData('adaptation_access_deny', '1');}else{XHR.appendData('adaptation_access_deny', '0');}
			if(document.getElementById('cache_deny').checked){XHR.appendData('cache_deny', '1');}else{XHR.appendData('cache_deny', '0');}
			if(document.getElementById('delay_access').checked){XHR.appendData('delay_access', '1');}else{XHR.appendData('delay_access', '0');}
			if(document.getElementById('tcp_outgoing_address-$t').checked){XHR.appendData('tcp_outgoing_address', '1');}else{XHR.appendData('tcp_outgoing_address', '0');}
			if(document.getElementById('snmp_access_allow').checked){XHR.appendData('snmp_access_allow', '1');}else{XHR.appendData('snmp_access_allow', '0');}
			if(document.getElementById('log_access').checked){XHR.appendData('log_access', '1');}else{XHR.appendData('log_access', '0');}
			if(document.getElementById('request_header_add').checked){XHR.appendData('request_header_add', '1');}else{XHR.appendData('request_header_add', '0');}
			if(document.getElementById('deny_log').checked){XHR.appendData('deny_log', '1');}else{XHR.appendData('deny_log', '0');}
			if(document.getElementById('deny_quota_rule').checked){XHR.appendData('deny_quota_rule', '1');}else{XHR.appendData('deny_quota_rule', '0');}
			
			XHR.appendData('deny_quota_rule_id', document.getElementById('deny_quota_rule_id').value);
			XHR.appendData('request_header_add_name', document.getElementById('request_header_add_name').value);
			XHR.appendData('request_header_add_value', document.getElementById('request_header_add_value').value);
			
						

			  
			
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveAclRule$ID);  		
		
		}
		
		

	
	function CheckAll(){
	var c=0;
	$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		if(document.getElementById(id).checked){c=c+1;}
		 	}
		 	
		});		
		
	if(c==0){
		$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
			 	var \$t = $(this);
			 	var id=\$t.attr('id');
			 	var value=\$t.attr('value');
			 	var type=\$t.attr('type');
			 	if(type=='checkbox'){
			 		document.getElementById(id).disabled=false;
			 	}
			 	
			});			
	
		}
	
	}
	
	function DisableAllInstead(zid){
		$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	if(zid==id){return;}
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		document.getElementById(id).checked=false;
		 		document.getElementById(id).disabled=true;
		 	}
		 	
		});	
	}
	
	function limit_bandwidth_check(){
		if(document.getElementById('delay_access').checked){DisableAllInstead('delay_access');}else{CheckAll();}
		
	}
	
	function access_allow_check(){
		if(document.getElementById('access_allow').checked){DisableAllInstead('access_allow');}else{CheckAll();}
	}
	
	function access_deny_check(){
		if(document.getElementById('access_deny').checked){DisableAllInstead('access_deny');}else{CheckAll();}
	}
	
	function deny_log_check(){
		if(document.getElementById('deny_log').checked){DisableAllInstead('deny_log');}else{CheckAll();}
	}

	function cache_deny_check(){
		if(document.getElementById('cache_deny').checked){DisableAllInstead('cache_deny');}else{CheckAll();}
	
	}
	
	function adaptation_access_deny_check(){
		if(document.getElementById('adaptation_access_deny').checked){DisableAllInstead('adaptation_access_deny');}else{CheckAll();}
	}
	
	function url_rewrite_access_deny_check(){
		if(document.getElementById('url_rewrite_access_deny').checked){DisableAllInstead('url_rewrite_access_deny');}else{CheckAll();}
	}
	
	function snmp_access_allow_check(){
		if(document.getElementById('snmp_access_allow').checked){DisableAllInstead('snmp_access_allow');}else{CheckAll();}
	}
	
	function log_access_check(){
		if(document.getElementById('log_access').checked){DisableAllInstead('log_access');}else{CheckAll();}
	}
	
	
	function deny_quota_rule_check(){
		if(document.getElementById('deny_quota_rule').checked){
			DisableAllInstead('deny_quota_rule');


		}else{CheckAll();}
		
	 } 
	
	
	function tcp_outgoing_address_check$t(){
		//alert(document.getElementById('tcp_outgoing_address-$t').checked);
		if(document.getElementById('tcp_outgoing_address-$t').checked){
		
			
			DisableAllInstead('tcp_outgoing_address');
			document.getElementById('tcp_outgoing_address_value').disabled=false;
			document.getElementById('tcp_outgoing_address-$t').checked=true;
			document.getElementById('tcp_outgoing_address-$t').disabled=false;
		}else{
			document.getElementById('tcp_outgoing_address_value').disabled=true;
			CheckAll();
		}
	}	
	

	function tcp_outgoing_tosCheck(){
		
		if(document.getElementById('tcp_outgoing_tos').checked){
			DisableAllInstead('tcp_outgoing_tos');
			document.getElementById('tcp_outgoing_tos_value').disabled=false;
		}else{
			document.getElementById('tcp_outgoing_tos_value').disabled=true;
			CheckAll();
		}
	}
	
	function deny_access_except_check(){
		if(document.getElementById('deny_access_except').checked){DisableAllInstead('deny_access_except');}else{CheckAll();}
	}
	
	function request_header_addCheck(){
		var is33=$is33;
		if(is33==0){return;}
		if(document.getElementById('request_header_add').checked){
			DisableAllInstead('request_header_add');
			document.getElementById('request_header_add_name').disabled=false;
			document.getElementById('request_header_add_value').disabled=false;
		}else{
			document.getElementById('request_header_add_name').disabled=true;
			document.getElementById('request_header_add_value').disabled=true;	
			CheckAll();	
		}
		
	}
	
	function features33_check(){
		var is33=$is33;
		document.getElementById('request_header_add').disabled=true;
		document.getElementById('request_header_add_value').disabled=true;
		document.getElementById('request_header_add_name').disabled=true;
		if(is33==0){return;}
		document.getElementById('request_header_add').disabled=false;
		if(document.getElementById('request_header_add').checked){
			DisableAllInstead('request_header_add');
			document.getElementById('request_header_add_name').disabled=false;
			document.getElementById('request_header_add_value').disabled=false;		
		}
		
	}
	
	
	limit_bandwidth_check();
	access_allow_check();
	access_deny_check();
	deny_access_except_check();
	tcp_outgoing_tosCheck();
	cache_deny_check();
	adaptation_access_deny_check();
	url_rewrite_access_deny_check();
	tcp_outgoing_address_check$t();
	snmp_access_allow_check();
	log_access_check();
	features33_check();
	deny_quota_rule_check();	
	</script>
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function acl_main_rule_edit(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	if(!isset($_POST["tcp_outgoing_tos_value"])){$_POST["tcp_outgoing_tos_value"]=null;}
	if(!isset($_POST["tcp_outgoing_address_value"])){$_POST["tcp_outgoing_address_value"]=null;}
	try {
		
		$q=new mysql_squid_builder();
		$acl=new squid_acls_groups();
		$ID=$_POST["ID"];
		$aclname=$_POST["aclrulename"];
		$sql="UPDATE webfilters_sqacls SET aclname='$aclname' WHERE ID='$ID'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		if(!$acl->aclrule_edittype($ID,"access_allow",$_POST["access_allow"])){return;}
		if(!$acl->aclrule_edittype($ID,"url_rewrite_access_deny",$_POST["url_rewrite_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"access_deny",$_POST["access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"adaptation_access_deny",$_POST["adaptation_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"cache_deny",$_POST["cache_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_access_except",$_POST["deny_access_except"])){return;}
		if(!$acl->aclrule_edittype($ID,"tcp_outgoing_tos",$_POST["tcp_outgoing_tos"],$_POST["tcp_outgoing_tos_value"])){return;}
		if(!$acl->aclrule_edittype($ID,"tcp_outgoing_address",$_POST["tcp_outgoing_address"],$_POST["tcp_outgoing_address_value"])){return;}
		if(!$acl->aclrule_edittype($ID,"delay_access",$_POST["delay_access"],$_POST["delay_access_id"])){return;}
		if(!$acl->aclrule_edittype($ID,"snmp_access_allow",$_POST["snmp_access_allow"],$_POST["snmp_access_allow"])){return;}
		if(!$acl->aclrule_edittype($ID,"log_access",$_POST["log_access"],$_POST["log_access"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_log",$_POST["deny_log"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_quota_rule",$_POST["deny_quota_rule"],$_POST["deny_quota_rule_id"])){return;}
		
		 
		
		
		
		$request_header_add_value["header_name"]=$_POST["request_header_add_name"];
		$request_header_add_value["header_value"]=$_POST["request_header_add_value"];		
		$request_header_add_value_final=base64_encode(serialize($request_header_add_value));
		if(!$acl->aclrule_edittype($ID,"request_header_add",$_POST["request_header_add"],$request_header_add_value_final)){return;}
		


		
		
		
										
	} catch (Exception $e) {
		echo $e->getMessage();
		return ;
	}
	
	
	

}

function TemplateName($md5){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	if($md5==null){return $tpl->_ENGINE_parse_body("<br>{and_display_error_page}: <strong>{default}</strong>");}
	$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$acltpl}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($ligne["template_title"])."');";
	return $tpl->_ENGINE_parse_body("<br>{and_display_error_page}: <a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:12px;text-decoration:underline'>{$ligne["template_title"]}</a>");
	}



function acl_rule_save(){
	$q=new mysql_squid_builder();
	$ID=$_POST["ID"];
	$aclname=$_POST["aclname-save"];
	
	if($ID<0){
		$sql="INSERT INTO webfilters_sqacls (aclname,enabled) VALUES ('$aclname',1)";
	}
	
	$q->CheckTables();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
		
}

function acl_rule_delete(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ID=$_POST["acl-rule-delete"];
	$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM webfilters_sqacls WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}		
	
	
}

function squid_compile(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
}

function acl_rule_enable(){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET enabled={$_POST["enable"]} WHERE ID={$_POST["acl-rule-enable"]}");
		if(!$q->ok){echo $q->mysql_error;return;}	
		
	}
	
function acl_rule_order(){
	$q=new mysql_squid_builder();
	$ID=$_POST["acl-rule-order"];
	$neworder=$_POST["acl-rule-value"];
	$sql="SELECT ID FROM webfilters_sqacls WHERE `xORDER`='{$neworder}'";	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["ID"]>0){
		$alt=$neworder+1;
		$sql="UPDATE webfilters_sqacls SET xORDER=$alt WHERE `ID`={$ligne["ID"]}";
		$q->QUERY_SQL($sql);
	}
	
	$sql="UPDATE webfilters_sqacls SET xORDER=$neworder WHERE `ID`={$ID}";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$c=0;
	$sql="SELECT ID FROM webfilters_sqacls ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {	
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}	
	
}
	
function acl_rule_move(){
	
	$q=new mysql_squid_builder();
	$sql="SELECT xORDER FROM webfilters_sqacls WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$xORDER_ORG=$ligne["xORDER"];
	$xORDER=$xORDER_ORG;
	if($_POST["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	//echo $sql."\n";
	
	if($_POST["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}' AND xORDER=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}' AND xORDER=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM webfilters_sqacls ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {	
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}
	
	
}

function page(){
	
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$t=time();
	$order=$tpl->javascript_parse_text("{order}");
	$squid_templates_error=$tpl->javascript_parse_text("{squid_templates_error}");
	$bandwith=$tpl->javascript_parse_text("{bandwith}");
	$session_manager=$tpl->javascript_parse_text("{session_manager}");
	$squid=new squidbee();
	$session_manager="{name: '$session_manager', bclass: 'clock', onpress : SessionManager$t},";
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==0){
		if(!$squid->IS_33){$session_manager=null;}
	}
	
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?acls-list=yes&t=$t&toexplainorg=table-$t&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$rule', name : 'aclname', width : 249, sortable : true, align: 'left'},
		{display: '$description', name : 'items', width : 457, sortable : false, align: 'left'},
		{display: '', name : 'up', width : 13, sortable : false, align: 'center'},
		{display: '', name : 'xORDER', width : 13, sortable : true, align: 'center'},
		{display: '', name : 'none2', width : 15, sortable : true, align: 'left'},
		{display: '', name : 'none3', width : 25, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddAcl},
	{name: '$groups', bclass: 'Group', onpress : GroupsSection$t},
	{name: '$bandwith', bclass: 'Network', onpress : BandwithSection$t},$session_manager
	{separator: true},
	{name: '$squid_templates_error', bclass: 'Script', onpress : SquidTemplatesErrors$t},
	{separator: true},
	{name: '$options', bclass: 'Settings', onpress : AclOptions$t},
	{separator: true},
	{name: '$apply_params', bclass: 'Reload', onpress : SquidBuildNow$t},
		],	
	searchitems : [
		{display: '$rule', name : 'aclname'},
		],
	sortname: 'xORDER',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 869,
	height: 450,
	singleSelect: true
	
	});   
});
function AddAcl() {
	Loadjs('$page?Addacl-js=yes&ID=-1&t=$t');
	
}	

function SessionManager$t(){
	Loadjs('squid.ext_time_quota_acl.php?t=$t')
}

function GroupsSection$t(){
	Loadjs('squid.acls.groups.php?js=yes&toexplainorg=table-$t');
}

function BandwithSection$t(){
	Loadjs('squid.bandwith.php?by-acls-js=yes&t=$t');

}

function AclOptions$t(){
	Loadjs('squid.acls.options.php?t=$t');
}

	var x_EnableDisableAclRule$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}

function AclUpDown(ID,dir){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-move', ID);
		XHR.appendData('acl-rule-dir', dir);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  	
}

function ChangeRuleOrder(ID,xdef){
	var neworder=prompt('$order',xdef);
	if(neworder){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-order', ID);
		XHR.appendData('acl-rule-value', neworder);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  	
	}
}

function SquidTemplatesErrors$t(){
	Loadjs('squid.templates.php');
}



	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	

	
	var x_SquidBuildNow= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}	
	
	
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

	var x_DeleteSquidAclRule$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowacl'+DeleteSquidAclGroupTemp).remove();
	}	
	
	
	function DeleteSquidAclRule(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_rule_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-delete', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclRule$t);
		}  		
	}


	
	function EnableDisableAclRule$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-enable', ID);
		if(document.getElementById('aclid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule$t);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}	


function acl_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	$t=$_GET["t"];
	$search='%';
	$table="webfilters_sqacls";
	$page=1;
	$data = array();
	$data['rows'] = array();

	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
		$default=$tpl->_ENGINE_parse_body("{default}");
		$q2=new mysql();
		$items=$q2->COUNT_ROWS("urlrewriteaccessdeny", "artica_backup");
		$explain=$tpl->_ENGINE_parse_body("{urlrewriteaccessdeny_explain} <strong>$items {items}</strong>");
		$data['rows'][] = array(
				'id' => "acl{$ligne['ID']}",
				'cell' => array("<a href=\"javascript:blur();\"  
				OnClick=\"javascript:Loadjs('squid.urlrewriteaccessdeny.php?t={$_GET["t"]}');\"
				style='font-size:16px;text-decoration:underline;color:black'>$default</span></A>
				",
				"<span style='font-size:12px;color:black'>$explain</span>",
				"&nbsp;","&nbsp;","&nbsp;","&nbsp;")
		);		
		
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	
	
	$acls=new squid_acls_groups();
	$order=$tpl->_ENGINE_parse_body("{order}:");
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("aclid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableAclRule$t('{$ligne['ID']}')");
		$ligne['aclname']=utf8_encode($ligne['aclname']);
		$delete=imgsimple("delete-24.png",null,"DeleteSquidAclRule('{$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#9C9C9C";}
		
		$explain=$tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ligne["enabled"]));
		
		$up=imgsimple("arrow-up-16.png","","AclUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-18.png","","AclUpDown('{$ligne['ID']}',0)");
		
		
	$data['rows'][] = array(
		'id' => "acl{$ligne['ID']}",
		'cell' => array("<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('$MyPage?Addacl-js=yes&ID={$ligne['ID']}&t={$_GET["t"]}');\" 
		style='font-size:16px;text-decoration:underline;color:$color'>{$ligne['aclname']}</span></A>
		<div style='font-size:11px'><i>$order&laquo;<a href=\"javascript:blur();\"
		Onclick=\"javascript:ChangeRuleOrder({$ligne['ID']},{$ligne["xORDER"]});\"
		style=\"text-decoration:underline\">{$ligne["xORDER"]}</a>&raquo;</i></div>",
		"<span style='font-size:12px;color:$color'>$explain</span>",
		$up,$down,$disable,$delete)
		);
	}
	
	
	echo json_encode($data);	
}
function output_scv(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$aclid=$_GET["csv"];
	$path=dirname(__FILE__)."/ressources/logs/web/access_acl_$aclid.csv";
	$file=basename($path);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?link-csv=$aclid");
	
	header('Content-type: application/vnd.ms-excel');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé */
	$fsize = filesize($path);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($path);
	@unlink($path);
	
}