<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__)."/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/effectiveTLDs.inc.php');
include_once(dirname(__FILE__).'/regDomain.inc.php');
include_once(dirname(__FILE__).'/class.simple.image.inc');


class mysql_squid_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="squidlogs";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $mysql_connection;
	var $EnableRemoteStatisticsAppliance=0;
	var $DisableArticaProxyStatistics=0;
	var $EnableSargGenerator=0;
	var $tasks_array=array();
	var $tasks_explain_array=array();
	var $tasks_remote_appliance=array();
	var $tasks_processes=array();
	var $tasks_disabled=array();
	var $last_id;
	var $acl_GroupType=array();
	
	function mysql_squid_builder(){
		$sock=new sockets();
		$squidEnableRemoteStatistics=$sock->GET_INFO("squidEnableRemoteStatistics");
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$squidRemostatisticsServer=$sock->GET_INFO("squidRemostatisticsServer");
		$squidRemostatisticsPort=$sock->GET_INFO("squidRemostatisticsPort");
		$squidRemostatisticsUser=$sock->GET_INFO("squidRemostatisticsUser");
		$squidRemostatisticsPassword=$sock->GET_INFO("squidRemostatisticsPassword");
		if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if($this->EnableRemoteStatisticsAppliance==1){$squidEnableRemoteStatistics=0;}
		$this->DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
		if(!is_numeric($this->DisableArticaProxyStatistics)){$this->DisableArticaProxyStatistics=0;}
		$this->EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
		if(!is_numeric($this->EnableSargGenerator)){$this->EnableSargGenerator=0;}
		$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
		$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
		if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	
		
		
		$this->acl_GroupType["src"]="{addr}";
		$this->acl_GroupType["arp"]="{ComputerMacAddress}";
		$this->acl_GroupType["dstdomain"]="{dstdomain}";
		$this->acl_GroupType["dst"]="{dst}";
		$this->acl_GroupType["proxy_auth"]="{members}";
		if($EnableKerbAuth==1){if($UseDynamicGroupsAcls==1){$this->acl_GroupType["proxy_auth_ads"]="{dynamic_activedirectory_group}";}}
		$this->acl_GroupType["port"]="{remote_ports}";
		$this->acl_GroupType["browser"]="{browser}";
		
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;
		
		
		if($squidEnableRemoteStatistics==1){
			$this->ClassSQL->mysql_admin=$squidRemostatisticsUser;
			$this->ClassSQL->mysql_password=$squidRemostatisticsPassword;
			$this->ClassSQL->mysql_port=$squidRemostatisticsPort;
			$this->ClassSQL->mysql_server=$squidRemostatisticsServer;
			$this->mysql_admin=$this->ClassSQL->mysql_admin;
			$this->mysql_password=$this->ClassSQL->mysql_password;
			$this->mysql_port=$this->ClassSQL->mysql_port;
			$this->mysql_server=$this->ClassSQL->mysql_server;			
		}
		$this->fill_task_array();
		$this->fill_tasks_disabled();
		if($this->TestingConnection()){
		}else{
			$this->MysqlFailed=true;
		}
		
	}
	
	private function fill_tasks_disabled(){
			$users=new usersMenus();
			if($this->DisableArticaProxyStatistics==1){
				$this->tasks_disabled[15]=true;
				$this->tasks_disabled[16]=true;
				$this->tasks_disabled[9]=true;
				$this->tasks_disabled[10]=true;
				$this->tasks_disabled[11]=true;
				$this->tasks_disabled[6]=true;
				$this->tasks_disabled[7]=true;
				$this->tasks_disabled[2]=true;
				$this->tasks_disabled[23]=true;
				$this->tasks_disabled[25]=true;
			}
			
			if(!$users->CORP_LICENSE){
				$this->tasks_disabled[20]=true;
				$this->tasks_disabled[30]=true;
			}
			
			if($this->EnableSargGenerator==0){
				$this->tasks_disabled[26]=true;
				$this->tasks_disabled[27]=true;
				
			}
			
			if($this->EnableRemoteStatisticsAppliance==1){
				$tasks_remote_appliance=$this->tasks_remote_appliance;
				while (list ($TaskType, $none) = each ($tasks_remote_appliance) ){
					$this->tasks_disabled[$TaskType]=true;
				}
			}
			
	}
	
	private function fill_task_array(){
			
			$this->tasks_array[0]="{select}";
			$this->tasks_array[1]="{databases_ufdbupdate}";
			$this->tasks_array[2]="{instant_update}";
			$this->tasks_array[3]="{databases_compilation}";
			$this->tasks_array[4]="{restart_proxy_service}";
			$this->tasks_array[5]="{restart_kav4Proxy}";
			$this->tasks_array[6]="{verify_urls_databases}";
			$this->tasks_array[7]="{build_hours_tables}";
			$this->tasks_array[8]="{update_tlse}";
			$this->tasks_array[9]="{rebuild_visited_sites}";
			$this->tasks_array[10]="{recategorize_schedule}";
			$this->tasks_array[11]="{build_month_tables}";
			$this->tasks_array[12]="{update_kaspersky_databases}";
			$this->tasks_array[13]="{launch_UpdateUtility}";
			$this->tasks_array[14]="{optimize_database}";
			$this->tasks_array[15]="{hourly_cache_performances}";
			$this->tasks_array[16]="{build_daily_visited_websites}";
			$this->tasks_array[17]="{cache_items}";
			$this->tasks_array[18]="{backup_categories}";
			$this->tasks_array[19]="{build_crypted_tables_catz}";
			$this->tasks_array[20]="{compile_ufdb_repos}";
			$this->tasks_array[21]="{importadmembers}";
			$this->tasks_array[22]="{synchronize_webfilter_rules}";
			$this->tasks_array[23]="{repair_categories}";
			$this->tasks_array[24]="{clean_cloud_datacenters}";
			$this->tasks_array[25]="{build_blocked_week_statistics}";
			$this->tasks_array[26]="{sarg_build_daily_stats}";
			$this->tasks_array[27]="{sarg_build_hourly_stats}";
			$this->tasks_array[28]="{thumbnail_parse}";
			$this->tasks_array[29]="{malware_uri}";
			$this->tasks_array[30]="{update_precompiled_ufdb}";
			$this->tasks_array[31]="{parse_squid_logs_queue}";
			$this->tasks_array[32]="{parse_squid_framework}";
			$this->tasks_array[33]="{squid_logrotate_perform}";
			$this->tasks_array[34]="{squid_week_stats}";
			$this->tasks_array[35]="{squid_backup_stats}";
			$this->tasks_array[36]="{members_stats}";
			$this->tasks_array[37]="{squid_tail_injector}";
			$this->tasks_array[38]="{web_injector}";
			$this->tasks_array[39]="{reconfigure_proxy_task}";
			$this->tasks_array[40]="{hourly_bandwidth_users}";
			$this->tasks_array[41]="{squid_rrd}";
			$this->tasks_array[42]="{compile_tlse_database}";
			
			
			
			
			
			
			$this->tasks_explain_array[1]="{databases_ufdbupdate_explain}";
			$this->tasks_explain_array[2]="{instant_update_explain}";
			$this->tasks_explain_array[3]="{databases_compilation_explain}";
			$this->tasks_explain_array[4]="{restart_proxy_service_explain}";
			$this->tasks_explain_array[5]="{restart_kav4Proxy_explain}";
			$this->tasks_explain_array[6]="{verify_urls_databases_explain}";
			$this->tasks_explain_array[7]="{build_hours_tables_explain}";
			$this->tasks_explain_array[8]="{update_tlse_explain}";
			$this->tasks_explain_array[9]="{rebuild_visited_sites_explain}";
			$this->tasks_explain_array[10]="{www_recategorize_explain}";
			$this->tasks_explain_array[11]="{build_month_tables_explain}";
			$this->tasks_explain_array[12]="{update_kaspersky_databases_explain}";
			$this->tasks_explain_array[13]="{launch_UpdateUtility_explain}";
			$this->tasks_explain_array[14]="{squid_optimize_database_explain}";
			$this->tasks_explain_array[15]="{hourly_cache_performances_explain}";
			$this->tasks_explain_array[16]="{build_daily_visited_websites_explain}";
			$this->tasks_explain_array[17]="{cache_items_tasks_explain}";
			$this->tasks_explain_array[18]="{backup_categories_explain}";
			$this->tasks_explain_array[19]="{build_crypted_tables_catz_explain}";
			$this->tasks_explain_array[20]="{compile_ufdb_repos_explain}";
			$this->tasks_explain_array[21]="{importadmembers_explain}";
			$this->tasks_explain_array[22]="{synchronize_webfilter_rules_text}";
			$this->tasks_explain_array[23]="{repair_categories_explain}";
			$this->tasks_explain_array[24]="{clean_cloud_datacenters_explain}";
			$this->tasks_explain_array[25]="{build_blocked_week_statistics_explain}";
			$this->tasks_explain_array[26]="{sarg_build_daily_stats_explain}";
			$this->tasks_explain_array[27]="{sarg_build_daily_stats_explain}";
			$this->tasks_explain_array[28]="{thumbnail_parse_explain}";
			$this->tasks_explain_array[29]="{malware_uri_explain}";
			$this->tasks_explain_array[30]="{update_precompiled_ufdb_explain}";
			$this->tasks_explain_array[31]="{parse_squid_logs_queue_explain}";
			$this->tasks_explain_array[32]="{parse_squid_framework_explain}";
			$this->tasks_explain_array[33]="{squid_logrotate_perform_explain}";
			$this->tasks_explain_array[34]="{squid_week_stats_explain}";
			$this->tasks_explain_array[35]="{squid_backup_stats_explain}";
			$this->tasks_explain_array[36]="{members_stats_explain}";
			$this->tasks_explain_array[37]="{squid_tail_injector_explain}";
			$this->tasks_explain_array[38]="{web_injector_explain}";
			$this->tasks_explain_array[39]="{reconfigure_proxy_task_explain}";
			$this->tasks_explain_array[40]="{hourly_bandwidth_users_explain}";
			$this->tasks_explain_array[41]="{squid_rrd_explain}";
			$this->tasks_explain_array[42]="{compile_tlse_database_explain}";
			
			

			$this->tasks_processes[1]="exec.squid.blacklists.php --update --bycron";
			$this->tasks_processes[2]="exec.update.blacklist.instant.php --bycron";
			$this->tasks_processes[3]="exec.squidguard.php --ufdbguard-recompile-dbs --bycron";
			$this->tasks_processes[4]="exec.squid.php --restart-squid";
			$this->tasks_processes[5]="exec.squid.php --restart-kav4proxy";
			$this->tasks_processes[6]="exec.squid.blacklists.php --inject";
			$this->tasks_processes[7]="exec.squid.stats.php --scan-hours";
			$this->tasks_processes[8]="exec.update.squid.tlse.php";
			$this->tasks_processes[9]="exec.squid.stats.php --visited-sites";
			$this->tasks_processes[10]="exec.squid.stats.php --re-categorize";
			$this->tasks_processes[11]="exec.squid.stats.php --scan-months";
			$this->tasks_processes[12]="exec.keepup2date.php --update";
			$this->tasks_processes[13]="exec.keepup2date.php --UpdateUtility";
			$this->tasks_processes[14]="exec.squid.stats.php --optimize";
			$this->tasks_processes[15]="exec.squid.stats.php --webcacheperfs";
			$this->tasks_processes[16]="exec.squid.stats.php --visited-days";
			$this->tasks_processes[17]="exec.squid.purge.php --scan";
			$this->tasks_processes[18]="exec.squid.cloud.compile.php --backup-catz";
			$this->tasks_processes[19]="exec.squid.cloud.compile.php --v2";
			$this->tasks_processes[20]="exec.squid.cloud.compile.php --ufdb";
			$this->tasks_processes[21]="exec.adusers.php";
			$this->tasks_processes[22]="exec.squidguard.php --build --force";	
			$this->tasks_processes[23]="exec.squid.stats.php --repair-categories";
			$this->tasks_processes[24]="exec.cleancloudcatz.php --all";		
			$this->tasks_processes[25]="exec.squid.stats.php --block-week";
			$this->tasks_processes[26]="exec.sarg.php --exec-daily";
			$this->tasks_processes[27]="exec.sarg.php --exec-hourly";
			$this->tasks_processes[28]="exec.squid.stats.php --thumbs-parse";
			$this->tasks_processes[29]="exec.squid.updateuris.malware.php --www";
			$this->tasks_processes[30]="exec.squid.blacklists.php --ufdb";
			$this->tasks_processes[31]="exec.dansguardian.injector.php";
			$this->tasks_processes[32]="exec.squid.framework.php";
			$this->tasks_processes[33]="exec.squid.php --rotate";
			$this->tasks_processes[34]="exec.squid.stats.php --week";
			$this->tasks_processes[35]="exec.squid.dbback.php";
			$this->tasks_processes[36]="exec.squid.stats.php --members-central";
			$this->tasks_processes[37]="exec.squid-tail-injector.php";
			$this->tasks_processes[38]="exec.dansguardian.injector.php";
			$this->tasks_processes[39]="exec.squid.php --build --force";
			$this->tasks_processes[40]="exec.squid.stats.php --users-size";
			$this->tasks_processes[41]="exec.squid-rrd.php";
			$this->tasks_processes[42]="exec.update.squid.tlse.php --compile";
	
			$this->tasks_remote_appliance["42"]=true;
			$this->tasks_remote_appliance["40"]=true;
			$this->tasks_remote_appliance["36"]=true;
			$this->tasks_remote_appliance["34"]=true;
			$this->tasks_remote_appliance["30"]=true;
			$this->tasks_remote_appliance["29"]=true;
			$this->tasks_remote_appliance["28"]=true;
			$this->tasks_remote_appliance["27"]=true;
			$this->tasks_remote_appliance["26"]=true;
			$this->tasks_remote_appliance["25"]=true;
			$this->tasks_remote_appliance["23"]=true;
			$this->tasks_remote_appliance["22"]=true;
			$this->tasks_remote_appliance["14"]=true;
			$this->tasks_remote_appliance["15"]=true;
			$this->tasks_remote_appliance["16"]=true;
			$this->tasks_remote_appliance["13"]=true;
			$this->tasks_remote_appliance["12"]=true;
			$this->tasks_remote_appliance["9"]=true;
			$this->tasks_remote_appliance["10"]=true;
			$this->tasks_remote_appliance["11"]=true;
			$this->tasks_remote_appliance["8"]=true;
			$this->tasks_remote_appliance["7"]=true;
			$this->tasks_remote_appliance["6"]=true;
			$this->tasks_remote_appliance["3"]=true;
			$this->tasks_remote_appliance["2"]=true;
			$this->tasks_remote_appliance["1"]=true;
			
			
	}
	
	public function CheckDefaultSchedules(){
		
		$allminutes="1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59";
		
		if(!$this->TABLE_EXISTS('webfilters_schedules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_schedules` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`TimeText` VARCHAR( 128 ) NOT NULL ,
			`TimeDescription` VARCHAR( 128 ) NOT NULL ,
			`TaskType` SMALLINT( 1 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `TaskType` , `TimeDescription`,`enabled`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("Fatal!!! $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}	
		
			$update=false;
			$array[1]=array("TimeText"=>"0 0,3,5,7,9,11,13,15,17,19,23 * * *","TimeDescription"=>"Check update each 3H");
			
			$array[6]=array("TimeText"=>"20,40,59 * * * *","TimeDescription"=>"each 20mn");
			$array[7]=array("TimeText"=>"5 * * * *","TimeDescription"=>"each Hour / 5mn");
			$array[8]=array("TimeText"=>"30 5,10,15,20 * * *","TimeDescription"=>"each 5 hours");
			$array[9]=array("TimeText"=>"0 3 * * *","TimeDescription"=>"each day at 03:00");
			$array[10]=array("TimeText"=>"0 5 * * *","TimeDescription"=>"each day at 05:00");
			$array[11]=array("TimeText"=>"0 1 * * *","TimeDescription"=>"each day at 01:00");
			$array[25]=array("TimeText"=>"30 2 * * *","TimeDescription"=>"each day at 02:30");
			$array[4]=array("TimeText"=>"0 7 * * *","TimeDescription"=>"each day at 07:00");
			$array[2]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Each hour");
			$array[3]=array("TimeText"=>"0 3 * * *","TimeDescription"=>"each day at 03:00");	
			$array[14]=array("TimeText"=>"30 6 * * *","TimeDescription"=>"Optimize all tables  each day at 06h30");
			$array[15]=array("TimeText"=>"0 * * * *","TimeDescription"=>"Calculate cache performance each hour");
			$array[16]=array("TimeText"=>"30 5,10,15,20 * * *","TimeDescription"=>"each 5 hours");
			$array[21]=array("TimeText"=>"0 2,4,6,8,10,12,14,16,18,20,22 * * *","TimeDescription"=>"Check AD server each 2H");
			$array[25]=array("TimeText"=>"14 4 * * *","TimeDescription"=>"Members Week blocked at 04:14");
			$array[28]=array("TimeText"=>"10,20,30,40,50 * * * *","TimeDescription"=>"check thumbnails queue each 10mn");
			$array[29]=array("TimeText"=>"30 6 * * *","TimeDescription"=>"Update infected uris Each day at 06h30");
			$array[30]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"Update precompiled databases Each day at 04h30");
			$array[31]=array("TimeText"=>"0,5,10,15,20,25,30,35,40,45,50,55 * * * *","TimeDescription"=>"Check queue requests each 5mn");
			$array[32]=array("TimeText"=>"0,10,20,30,40,50 * * * *","TimeDescription"=>"Check framework requests each 10mn");
			$array[34]=array("TimeText"=>"30 6 * * *","TimeDescription"=>"Compile week tables Each day at 06h30");
			$array[36]=array("TimeText"=>"5 5 * * *","TimeDescription"=>"Members statistics each day at 05h05");
			$array[37]=array("TimeText"=>"* * * * *","TimeDescription"=>"Inject into Mysql each minute");
			$array[38]=array("TimeText"=>"* * * * *","TimeDescription"=>"Inject into Mysql each minute");
			$array[40]=array("TimeText"=>"10 * * * *","TimeDescription"=>"Each hour +10mn");
			$array[41]=array("TimeText"=>"3,6,9,11,13,16,19,21,26,29,31,36,39,41,46,49,51,56,59 * * * *","TimeDescription"=>"Check AD server each 3M");
			$array[42]=array("TimeText"=>"30 4 * * *","TimeDescription"=>"Compile Toulouse databases tables Each day at 04h30");
			
			

			while (list ($TaskType, $content) = each ($array) ){
				if($this->tasks_disabled[$TaskType]){continue;}
				$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT TimeText FROM webfilters_schedules WHERE TaskType=$TaskType"));
				if($ligne["TimeText"]<>null){continue;}
				
				$sql="INSERT IGNORE INTO webfilters_schedules (TimeDescription,TimeText,TaskType,enabled) 
					VALUES('{$content["TimeDescription"]}','{$content["TimeText"]}','$TaskType',1)";				
				
				$this->QUERY_SQL($sql);
				$update=true;
			}
			
			if($update){$sock=new sockets();$sock->getFrameWork("squid.php?build-schedules=yes");}
			
		
	}
	
	FUNCTION DELETE_TABLE($table){
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $this->database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$this->database);
	}		
	
	
	public function TestingConnection(){
		$this->ok=true;
		$this->ClassSQL->ok=true;
		$a=$this->ClassSQL->TestingConnection();
		$this->mysql_error=$this->ClassSQL->mysql_error;
		return $a;
	}
	
	public function COUNT_ROWS($table,$database=null){
		$this->ok=true;
		if($database<>$this->database){$database=$this->database;}
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			$this->ok=false;
			$this->mysql_error=$this->ClassSQL->mysql_error;
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			
			writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
	
	
	public function TABLE_SIZE($table,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->TABLE_SIZE($table,$database);		
	}
	
	public function TABLE_EXISTS($table,$database=null){
		if($table=="category_teans"){$table="category_teens";}
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		$a=$this->ClassSQL->TABLE_EXISTS($table,$database);
		return $a;
		
	}
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	
	public function FIELD_EXISTS($table,$field,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}
	
	public function BD_CONNECT($noretry=false){
		$this->ClassSQL->BD_CONNECT();
		$this->mysql_connection=$this->ClassSQL->mysql_connection;
		
	}
	
	public function QUERY_SQL($sql,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	private function FIELD_TYPE($table,$field,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	public function CheckTable_dansguardian(){
		$this->CheckTables();
	}
	
	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if($GLOBALS["VERBOSE"]){writelogs(mysql_num_rows($results)." events for $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tsum"];
		
	}
	
	public function LIST_TABLES_QUERIES(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#dansguardian_events_([0-9]{1,4})([0-9]{1,2})([0-9]{1,2})#", $ligne["c"],$re))
			$array[$ligne["c"]]=$re[1]."-".$re[2]."-".$re[3];
		}
		return $array;
		
	}

	public function CategoryShellEscape($category){
		$category=trim($category);
		if($category==null){return;}
		$category=str_replace("/", "_", $category);
		$category=str_replace("-", "_", $category);
		$category=str_replace(" ", "_", $category);		
		return $category;
	}
	

	
	public function LIST_TABLES_HOURS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_HOURS"])){return $GLOBALS["SQUID_LIST_TABLES_HOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_hour'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_hour#", $ligne["c"])){
				$GLOBALS["SQUID_LIST_TABLES_HOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
	}
	
	public function LIST_TABLES_dansguardian_events(){
		if(isset($GLOBALS["LIST_TABLES_dansguardian_events"])){return $GLOBALS["LIST_TABLES_dansguardian_events"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#dansguardian_events_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_dansguardian_events"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;			
		
	}
	public function LIST_TABLES_BLOCKED_WEEK(){
		if(isset($GLOBALS["LIST_TABLES_BLOCKED_WEEK"])){return $GLOBALS["LIST_TABLES_BLOCKED_WEEK"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked_week'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_blocked_week#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_BLOCKED_WEEK"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
		
	}
	
	public function LIST_TABLES_BLOCKED_DAY(){
		if(isset($GLOBALS["LIST_TABLES_BLOCKED_DAY"])){return $GLOBALS["LIST_TABLES_BLOCKED_DAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked_days'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_blocked_days#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_BLOCKED_DAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
		
	}	
	public function LIST_TABLES_VISITED(){
		if(isset($GLOBALS["LIST_TABLES_VISITED"])){return $GLOBALS["LIST_TABLES_VISITED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_visited'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_visited#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_VISITED"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
		
	}	
	
	
	
	
	
	
	
	public function LIST_TABLES_WORKSHOURS(){
		if(isset($GLOBALS["LIST_TABLES_WORKSHOURS"])){return $GLOBALS["LIST_TABLES_WORKSHOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'squidhour_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#squidhour_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_WORKSHOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
	}

	public FUNCTION TLSE_CONVERTION(){
			$f["agressif"]="aggressive";
			$f["audio-video"]="audio-video";
			$f["celebrity"]="celebrity";
			$f["cleaning"]="cleaning";
			$f["dating"]="dating";
			$f["filehosting"]="filehosting";
			$f["gambling"]="gamble";
			$f["hacking"]="hacking";
			$f["liste_bu"]="liste_bu";
			$f["manga"]="manga";
			$f["mobile-phone"]="mobile-phone";
			$f["press"]="press";
			$f["radio"]="webradio";
			$f["redirector"]="proxy";
			$f["sexual_education"]="sexual_education";
			$f["sports"]="recreation/sports";
			$f["tricheur"]="tricheur";
			$f["webmail"]="webmail";
			$f["adult"]="porn";
			$f["arjel"]="arjel";
			$f["bank"]="finance/banking";
			$f["chat"]="chat";
			$f["cooking"]="hobby/cooking";
			$f["drogue"]="drugs";
			$f["financial"]="financial";
			$f["games"]="games";
			$f["jobsearch"]="jobsearch";
			$f["marketingware"]="marketingware";
			$f["phishing"]="phishing";
			$f["remote-control"]="remote-control";
			$f["shopping"]="shopping";
			$f["strict_redirector"]="strict_redirector";
			$f["astrology"]="astrology";
			$f["blog"]="blog";
			$f["child"]="children";
			$f["dangerous_material"]="dangerous_material";
			$f["forums"]="forums";
			$f["lingerie"]="sex/lingerie";
			$f["malware"]="malware";
			$f["mixed_adult"]="mixed_adult";
			$f["publicite"]="publicite";
			$f["reaffected"]="reaffected";
			$f["sect"]="sect";
			$f["social_networks"]="socialnet";
			$f["strong_redirector"]="strong_redirector";
			$f["warez"]="warez";
			return $f;		
		
	}
	
	
	public function LIST_TABLES_DAYS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_DAYS"])){return $GLOBALS["SQUID_LIST_TABLES_DAYS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_day' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["SQUID_LIST_TABLES_DAYS"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_day#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["SQUID_LIST_TABLES_DAYS"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_DAYS count:". count($GLOBALS["SQUID_LIST_TABLES_DAYS"])."\n";}
		return $array;		
	}	
	
	public function LIST_TABLES_WEEKS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_WEEKS"])){return $GLOBALS["SQUID_LIST_TABLES_WEEKS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_week' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["SQUID_LIST_TABLES_WEEKS"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_week#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["SQUID_LIST_TABLES_WEEKS"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "SQUID_LIST_TABLES_WEEKS count:". count($GLOBALS["SQUID_LIST_TABLES_WEEKS"])."\n";}
		return $array;			
		
	}
	
	public function LIST_TABLES_MONTH(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_MONTH"])){return $GLOBALS["SQUID_LIST_TABLES_MONTH"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_day' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["SQUID_LIST_TABLES_WEEKS"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_day#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["SQUID_LIST_TABLES_MONTH"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "SQUID_LIST_TABLES_MONTH count:". count($GLOBALS["SQUID_LIST_TABLES_MONTH"])."\n";}
		return $array;			
		
	}	
	
	public function LIST_TABLES_WEEKS_BLOCKED(){
		if(isset($GLOBALS["LIST_TABLES_WEEKS_BLOCKED"])){return $GLOBALS["LIST_TABLES_WEEKS_BLOCKED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked_week' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["LIST_TABLES_WEEKS_BLOCKED"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_blocked_week#", $ligne["c"])){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["LIST_TABLES_WEEKS_BLOCKED"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_WEEKS_BLOCKED count:". count($GLOBALS["LIST_TABLES_WEEKS_BLOCKED"])."\n";}
		return $array;			
		
	}

	public function LIST_TABLES_DAYS_BLOCKED(){
		if(isset($GLOBALS["LIST_TABLES_DAYS_BLOCKED"])){return $GLOBALS["LIST_TABLES_DAYS_BLOCKED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_blocked' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)." memory count:". count($GLOBALS["LIST_TABLES_DAYS_BLOCKED"])."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#^[0-9]+_blocked$#", trim($ligne["c"]))){
					$array[$ligne["c"]]=$ligne["c"];
					$GLOBALS["LIST_TABLES_DAYS_BLOCKED"][$ligne["c"]]=$ligne["c"];
			}
		}
		if($GLOBALS["VERBOSE"]){echo "LIST_TABLES_DAYS_BLOCKED count:". count($GLOBALS["LIST_TABLES_DAYS_BLOCKED"])."\n";}
		return $array;			
		
	}	
	
	public function LIST_TABLES_MEMBERS(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_members' ORDER BY table_name";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#[0-9]+_members#", $ligne["c"])){$array[$ligne["c"]]=$ligne["c"];}
		}
		return $array;		
	}		
	
	public function HIER(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		return $ligne["tdate"];
	}
	
	public function LIST_TABLES_CATEGORIES(){
		if(isset($GLOBALS["LIST_TABLES_CATEGORIES"])){return $GLOBALS["LIST_TABLES_CATEGORIES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["c"]=="category_"){$this->QUERY_SQL("DROP TABLE `category_`");continue;}
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_CATEGORIES"]=$array;
		return $array;
		
	}

	public function LIST_TABLES_WEIGHTED(){
		if(isset($GLOBALS["LIST_TABLES_WEIGHTED"])){return $GLOBALS["LIST_TABLES_WEIGHTED"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'weigthed_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["VERBOSE"]){echo "{$ligne["c"]}\n";}
			if($ligne["c"]=="weighted_"){$this->QUERY_SQL("DROP TABLE `weighted_`");}
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_WEIGHTED"]=$array;
		return $array;
		
	}	
	
	public function UPDATE_CATEGORIES_TABLES($sitename,$category){
		if(trim($sitename)==null){return;}
		if(trim($category)==null){return;}
		$array=$this->LIST_TABLES_HOURS();
		while (list ($num, $tablename) = each ($array) ){$this->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$sitename'");}
		$array=$this->LIST_TABLES_DAYS();
		while (list ($num, $tablename) = each ($array) ){$this->QUERY_SQL("UPDATE $tablename SET category='$category' WHERE sitename='$sitename'");}		
	}
	
	public function UPDATE_WEBSITES_TABLES($sitename,$newsitename){
		if(trim($sitename)==null){return;}
		if(trim($newsitename)==null){return;}
		$array=$this->LIST_TABLES_HOURS();
		while (list ($num, $tablename) = each ($array) ){$this->QUERY_SQL("UPDATE $tablename SET sitename='$newsitename' WHERE sitename='$sitename'");}
		$array=$this->LIST_TABLES_DAYS();
		while (list ($num, $tablename) = each ($array) ){$this->QUERY_SQL("UPDATE $tablename SET sitename='$newsitename' WHERE sitename='$sitename'");}		
	}
	
	public function ACCOUNTS_ISP(){
		$array=array();
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
		$sql="SELECT userid,publicip FROM usersisp WHERE enabled=1";
		$results=$this->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["publicip"]==null){continue;}
			$array[$ligne["publicip"]]=$ligne["userid"];
		}
		
		$GLOBALS[__CLASS__.__FUNCTION__]=$array;
		return $array;
	}

	
	public function TablePrimaireHour($prefix=null){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if($prefix==null){$prefix=date("YmdH");}
		
		$table="squidhour_$prefix";
		
		if(!$this->TABLE_EXISTS($table,$this->database)){
		writelogs("Checking $table in $this->database NOT EXISTS...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sql="CREATE TABLE IF NOT EXISTS `$table` (
		  `sitename` varchar(90) NOT NULL,
		  `ID` bigint(100) NOT NULL AUTO_INCREMENT,
		  `uri` varchar(90) NOT NULL,
		  `TYPE` varchar(50) NOT NULL,
		  `REASON` varchar(255) NOT NULL,
		  `CLIENT` varchar(50) NOT NULL DEFAULT '',
		  `hostname` varchar(120) NOT NULL DEFAULT '',
		  `zDate` datetime NOT NULL,
		  `zMD5` varchar(90) NOT NULL,
		  `uid` varchar(128) NOT NULL,
		  `remote_ip` varchar(20) NOT NULL,
		  `country` varchar(20) NOT NULL,
		  `QuerySize` int(10) NOT NULL,
		  `cached` int(1) NOT NULL DEFAULT '0',
		  `MAC` varchar(20) NOT NULL,
		  PRIMARY KEY (`ID`),
		  UNIQUE KEY `zMD5` (`zMD5`),
		  KEY `sitename` (`sitename`,`TYPE`,`CLIENT`,`uri`),
		  KEY `hostname` (`hostname`),
		  KEY `zDate` (`zDate`),
		  KEY `cached` (`cached`),
		  KEY `uri` (`uri`),
		  KEY `remote_ip` (`remote_ip`),
		  KEY `uid` (`uid`),
		  KEY `country` (`country`),
		  KEY `MAC` (`MAC`)
		) ";
			 $this->QUERY_SQL($sql,$this->database); 
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_error=$this->mysql_error."\n$sql";
				return false;
			}else{
				writelogs("Checking $table SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}
		}
		
		if(!$this->FIELD_EXISTS("$table", "hostname")){$this->QUERY_SQL("ALTER TABLE `$table` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
		
	}
	
	public function check_youtube_hour($timekey=null){
		if($timekey==null){$timekey=date('YmdH');}
		
		$table="youtubehours_$timekey";
		if(!$this->TABLE_EXISTS("$table",$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`$table` (
			`zDate` datetime NOT NULL,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` BIGINT(100) NOT NULL,
			`youtubeid` VARCHAR(60) NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `zDate`(`zDate`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 )";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		
	}
	
	
public function CheckTables($table=null){
		$md5=md5("CheckTables($table)");
		if(isset($GLOBALS[$md5])){return;}
		$GLOBALS[$md5]=true;
		
	if($this->EnableRemoteStatisticsAppliance==1){return;}
	if($GLOBALS["AS_ROOT"]){
		if(!$GLOBALS["VERBOSE"]){
			$unix=new unix();
			$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
			if($unix->file_time_min($timefile)<30){return true;}
			@unlink($timefile);
			@file_put_contents($timefile,time());
		}
	}
	
	if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
	$this->TablePrimaireHour();
	$this->CreateWeekTable();
	
	if($this->TABLE_EXISTS("category_teans")){
		if(!$this->TABLE_EXISTS("category_teens")){
			$this->QUERY_SQL("RENAME TABLE `category_teans` TO `category_teens`");
		}
	}
	
	if($table==null){$table="dansguardian_events_".date('Ymd');}	
	if(!$this->TABLE_EXISTS($table,$this->database)){
		writelogs("Checking $table in $this->database NOT EXISTS...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sql="CREATE TABLE IF NOT EXISTS `$table` (
		  `sitename` varchar(90) NOT NULL,
		  `ID` bigint(100) NOT NULL AUTO_INCREMENT,
		  `uri` varchar(90) NOT NULL,
		  `TYPE` varchar(50) NOT NULL,
		  `REASON` varchar(255) NOT NULL,
		  `CLIENT` varchar(50) NOT NULL DEFAULT '',
		  `hostname` varchar(120) NOT NULL DEFAULT '',
		 `account` BIGINT(100) NOT NULL,
		  `zDate` datetime NOT NULL,
		  `zMD5` varchar(90) NOT NULL,
		  `uid` varchar(128) NOT NULL,
		  `remote_ip` varchar(20) NOT NULL,
		  `country` varchar(20) NOT NULL,
		  `QuerySize` BIGINT(100) NOT NULL,
		  `hits` BIGINT(100) NOT NULL,
		  `cached` int(1) NOT NULL DEFAULT '0',
		  `MAC` varchar(20) NOT NULL,
		  PRIMARY KEY (`ID`),
		  UNIQUE KEY `zMD5` (`zMD5`),
		  KEY `sitename` (`sitename`,`TYPE`,`CLIENT`,`uri`),
		  KEY `zDate` (`zDate`),
		  KEY `hostname` (`hostname`),KEY `account` (`account`),
		  KEY `cached` (`cached`),
		  KEY `uri` (`uri`),
		  KEY `hits` (`hits`),
		  KEY `remote_ip` (`remote_ip`),
		  KEY `uid` (`uid`),
		  KEY `country` (`country`),
		  KEY `MAC` (`MAC`)
		) ";
			 $this->QUERY_SQL($sql,$this->database); 
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_error=$this->mysql_error."\n$sql";
				return false;
			}else{
				writelogs("Checking $table SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}
		}
	if(!$this->FIELD_EXISTS("$table", "hostname")){$this->QUERY_SQL("ALTER TABLE `$table` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
	if(!$this->FIELD_EXISTS("$table", "hits")){$this->QUERY_SQL("ALTER TABLE `$table` ADD `hits` BIGINT(100) NOT NULL,ADD KEY `hits` (`hits`)");}
		
		
	$tableblock=date('Ymd')."_blocked";	
	if(!$this->TABLE_EXISTS($tableblock,'artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `$tableblock` (
			  `ID` bigint(100) NOT NULL AUTO_INCREMENT,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `client` varchar(90) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` BIGINT(100) NOT NULL,
			  `website` varchar(125) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `rulename` varchar(50) NOT NULL,
			  `public_ip` varchar(40) NOT NULL,
			  `uri` varchar(255) NOT NULL,
			  `event` varchar(20) NOT NULL,
			  `why` varchar(90) NOT NULL,
			  `explain` text NOT NULL,
			  `blocktype` varchar(255) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `website` (`website`),
			  KEY `category` (`category`),
			  KEY `rulename` (`rulename`),
			  KEY `public_ip` (`public_ip`),
			  KEY `event` (`event`),
			  KEY `why` (`why`)
			)"; 
		$this->QUERY_SQL($sql); 
		
			if(!$this->ok){
					writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->mysql_error=$this->mysql_error."\n$sql";
					return false;
				}else{
					writelogs("Checking $table SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}

		}
		$tableblock=date('Ymd')."_blocked";
		$this->RepairTableBLock($tableblock);
		
		
		$tableblockMonth=date('Ym')."_blocked_days";
		if(!$this->TABLE_EXISTS($tableblockMonth,'artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `$tableblockMonth` (
			`zmd5` VARCHAR( 100 ) NOT NULL PRIMARY KEY ,
			`hits` BIGINT( 100 ),
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`client` VARCHAR( 90 ) NOT NULL ,
			`hostname` VARCHAR( 120 ) NOT NULL ,
			`account` BIGINT(100) NOT NULL ,
			`website` VARCHAR( 125 ) NOT NULL ,
			`category` VARCHAR( 50 ) NOT NULL ,
			`rulename` VARCHAR( 50 ) NOT NULL ,
			`public_ip` VARCHAR( 40 ) NOT NULL ,
			KEY `zDate` (`zDate`),
			KEY `hits` (`hits`),
			KEY `client` (`client`),
			KEY `hostname` (`hostname`),
			KEY `account` (`account`),
			KEY `website` (`website`),
			KEY `category` (`category`),
			KEY `rulename` (`rulename`),
			KEY `public_ip` (`public_ip`)
			
			)"; 
		$this->QUERY_SQL($sql); 
		
			if(!$this->ok){
					writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->mysql_error=$this->mysql_error."\n$sql";
					return false;
				}else{
					writelogs("Checking $table SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}

		}

		if(!$this->FIELD_EXISTS("$tableblockMonth", "hostname")){$this->QUERY_SQL("ALTER TABLE `$tableblockMonth` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
		if(!$this->FIELD_EXISTS("$tableblockMonth", "account")){$this->QUERY_SQL("ALTER TABLE `$tableblockMonth` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");}
		
			
		if($this->TABLE_EXISTS("webfilters_schedules",$this->database)){
			if(!$this->FIELD_EXISTS("webfilters_schedules","Params",$this->database)){
				$this->QUERY_SQL("ALTER TABLE `webfilters_schedules` ADD `Params` TEXT NOT NULL");
			}
		}		
		 

		
		
		
		if(!$this->FIELD_EXISTS($table,"uid",$this->database)){
			$sql="ALTER TABLE `$table` ADD `uid` VARCHAR( 128 ) NOT NULL,ADD INDEX ( uid )";
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_error=$this->mysql_error."\n$sql";
			}			
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('webfilter_rules',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_rules` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				  	groupmode INT(1) NOT NULL,
				  	enabled INT(1) NOT NULL,
					groupname VARCHAR(90) NOT NULL,
					BypassSecretKey VARCHAR(90) NOT NULL,
					endofrule VARCHAR(50) NOT NULL,
					blockdownloads INT(1) NOT NULL DEFAULT '0' ,
					naughtynesslimit INT(2) NOT NULL DEFAULT '50' ,
					searchtermlimit INT(2) NOT NULL DEFAULT '30' ,
					bypass INT(1) NOT NULL DEFAULT '0' ,
					deepurlanalysis  INT(1) NOT NULL DEFAULT '0' ,
					UseExternalWebPage SMALLINT(1) NOT NULL DEFAULT '0' ,
					ExternalWebPage VARCHAR(256) NOT NULL DEFAULT,
					sslcertcheck INT(1) NOT NULL DEFAULT '0' ,
					sslmitm INT(1) NOT NULL DEFAULT '0',
					GoogleSafeSearch smallint(1) NOT NULL DEFAULT '0',
					TimeSpace TEXT NOT NULL,
					TemplateError TEXT NOT NULL,
					RewriteRules TEXT NOT NULL,
				  KEY `groupname` (`groupname`),
				  KEY `enabled` (`enabled`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		if(!$this->FIELD_EXISTS("webfilter_rules", "endofrule")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `endofrule` VARCHAR(50)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "BypassSecretKey")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `BypassSecretKey` VARCHAR(90)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "embeddedurlweight")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `embeddedurlweight` INT(1)");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "TimeSpace")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `TimeSpace` TEXT NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "RewriteRules")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `RewriteRules` TEXT NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "TemplateError")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `TemplateError` TEXT NOT NULL");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "GoogleSafeSearch")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `GoogleSafeSearch` smallint(1) NOT NULL DEFAULT '0'");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "UseExternalWebPage")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `UseExternalWebPage` smallint(1) NOT NULL DEFAULT '0'");}
		if(!$this->FIELD_EXISTS("webfilter_rules", "ExternalWebPage")){$this->QUERY_SQL("ALTER TABLE `webfilter_rules` ADD `ExternalWebPage` VARCHAR(255) NOT NULL");}
		
		
		
		
		if(!$this->TABLE_EXISTS('webfilter_group',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_group` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					groupname VARCHAR(90) NOT NULL,
					localldap INT(1) NOT NULL DEFAULT '0' ,
					enabled INT(1) NOT NULL DEFAULT '1' ,
					gpid INT(10) NOT NULL DEFAULT '0' ,
					description VARCHAR(255) NOT NULL,
					`dn` VARCHAR(255) NOT NULL,
				  KEY `groupname` (`groupname`),
				  KEY `gpid` (`gpid`),
				  KEY `enabled` (`enabled`),
				  KEY `dn` (`dn`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	
		
		if(!$this->FIELD_EXISTS("webfilter_group", "dn")){$this->QUERY_SQL("ALTER TABLE `webfilter_group` ADD `dn` VARCHAR( 255 ) NOT NULL ,ADD INDEX ( `dn` )");} 
		
		
		if(!$this->TABLE_EXISTS('webfilters_dtimes_rules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_dtimes_rules` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`TimeName` VARCHAR( 128 ) NOT NULL ,
			`TimeCode` TEXT NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`ruleid` INT( 100 ) NOT NULL ,
			INDEX ( `TimeName` , `enabled` , `ruleid` )
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_databases_disk',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_databases_disk` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`filename` VARCHAR( 128 ) NOT NULL ,
			`size` BIGINT(100) ,
			`filtime` BIGINT(100) ,
			`category` VARCHAR( 50 ) NOT NULL ,
			INDEX ( `size` , `category` ,`filtime`),
			KEY `filename` (`filename`)
			)";	
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->FIELD_EXISTS("webfilters_databases_disk", "filtime")){
			$this->QUERY_SQL("ALTER TABLE `webfilters_databases_disk` ADD `filtime`  BIGINT(100) NOT NULL ,ADD INDEX ( `filtime` )");
		}

		if(!$this->TABLE_EXISTS('webfilters_quotas',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_quotas` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`xtype` VARCHAR( 40 ) NOT NULL ,
			`value` VARCHAR(150) ,
			`maxquota` BIGINT(100) ,
			`enabled` smallint(1),
			`duration` smallint(1),
			KEY `type` (`xtype`),
			KEY `value` (`value`),
			KEY `duration` (`duration`),
			KEY `maxquota` (`maxquota`)
			)";	
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		if(!$this->TABLE_EXISTS('webtests',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webtests` (
			`sitename` VARCHAR( 250 ) NOT NULL PRIMARY KEY ,
			`category` VARCHAR( 128 ) NOT NULL ,
			`family` VARCHAR( 128 ) NOT NULL,
			`Country` VARCHAR( 50 ) NOT NULL,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`ipaddr` VARCHAR(50) NOT NULL ,
			`SiteInfos` TEXT NOT NULL ,
			`checked` SMALLINT( 1 ) NOT NULL ,
			 KEY `pattern` (`category`),
			 KEY `Country` (`Country`),
			 KEY `checked` (`checked`),
			 KEY `family` (`family`),
			 KEY `ipaddr` (`ipaddr`),
			 KEY `zDate` (`zDate`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}else{
			if(!$this->FIELD_EXISTS("webtests", "Country")){
				$this->QUERY_SQL("ALTER TABLE `webtests` ADD `Country`  VARCHAR( 50 ) NOT NULL ,ADD INDEX ( `Country` )");
			}
		}
		


			
		if(!$this->TABLE_EXISTS('webcacheperfs',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webcacheperfs` (
			`zTimeInt` BIGINT(100) NOT NULL PRIMARY KEY,
			`zHour` TINYINT( 4 ) NOT NULL,
			`zDay` TINYINT( 4 ) NOT NULL,
			`zMonth` TINYINT( 4 ) NOT NULL,
			`zYear` INT( 5 ) NOT NULL,
			`notcached` BIGINT( 100 ) NOT NULL,
			`cached` BIGINT( 100 ) NOT NULL,
			`pourc` TINYINT( 2 ) NOT NULL ,
			INDEX ( `zHour` , `zDay` , `zMonth`,`zYear`,`notcached`,`cached`,`pourc` )
			 
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}	
		
		if(!$this->TABLE_EXISTS('UserAuthDays',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`UserAuthDays` (
			`zMD5` VARCHAR(90) PRIMARY KEY ,
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`zDate` TIMESTAMP NOT NULL,
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` BIGINT(100) NOT NULL,
			`QuerySize` BIGINT(100) NOT NULL,
			`hits` BIGINT(100) NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `hostname`(`hostname`),
			 KEY `zDate`(`zDate`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 )";
			$this->QUERY_SQL($sql,$this->database);
		}
		if(!$this->TABLE_EXISTS('UserAuthDaysGrouped',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`UserAuthDaysGrouped` (
			`ipaddr` VARCHAR(40),
			`hostname` VARCHAR(128),
			`uid` VARCHAR(40) NOT NULL,
			`MAC` VARCHAR(20) NOT NULL,
			`account` BIGINT(100) NOT NULL,
			`QuerySize` BIGINT(100) NOT NULL,
			`hits` BIGINT(100) NOT NULL,
			 KEY `ipaddr`(`ipaddr`),
			 KEY `hostname`(`hostname`),
			 KEY `uid`(`uid`),
			 KEY `MAC`(`MAC`),
			 KEY `account`(`account`)
			 )";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('youtube_objects',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`youtube_objects` (
			`youtubeid` VARCHAR(60) NOT NULL PRIMARY KEY,
			`category` VARCHAR(90),
			`title` VARCHAR(255) NOT NULL,
			`content` TEXT NOT NULL,
			`uploaded` TIMESTAMP NOT NULL,
			`hits` BIGINT(100) NOT NULL,
			`duration` BIGINT(100) NOT NULL,
			`thumbnail` longblob NOT NULL,
			 KEY `category`(`category`),
			 KEY `uploaded`(`uploaded`),
			 KEY `title`(`title`),
			 KEY `duration`(`duration`),
			 KEY `hits`(`hits`)
			 )";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		

		
		if(!$this->TABLE_EXISTS('RegexCatz',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`RegexCatz` (
			`ID` BIGINT(100) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zMD5` VARCHAR( 90 ) NOT NULL,
			`RegexPattern` VARCHAR( 255 ) NOT NULL,
			`category` VARCHAR( 90 ) NOT NULL,
			`enabled` TINYINT( 1 ) NOT NULL,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX (`enabled`,`zDate`),
			KEY `zMD5`(`zMD5`),
			KEY `category`(`category`)
			 
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		if(!$this->FIELD_EXISTS("webtests", "checked")){
			$this->QUERY_SQL("ALTER TABLE `webtests` ADD `checked`  SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `checked` )");
		}
		
		if(!$this->TABLE_EXISTS('websites_caches_params',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`websites_caches_params` (
			`sitename` VARCHAR(255) NOT NULL PRIMARY KEY,
			`MIN_AGE` INT( 10 ) NOT NULL,
			`PERCENT` INT( 10 ) NOT NULL,
			`MAX_AGE` INT( 10 ) NOT NULL,
			`options` TINYINT(1 ) NOT NULL,
			INDEX ( `MIN_AGE` , `PERCENT` , `MAX_AGE`,`options`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}			
		
		
		if(!$this->TABLE_EXISTS('webfilters_sqtimes_rules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqtimes_rules` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`TimeName` VARCHAR( 128 ) NOT NULL ,
			`TimeCode` TEXT NOT NULL ,
			`TemplateError` TEXT NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`Allow` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `TimeName` , `enabled`,`Allow`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		if(!$this->TABLE_EXISTS('webfilters_schedules',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_schedules` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`TimeText` VARCHAR( 128 ) NOT NULL ,
			`TimeDescription` VARCHAR( 128 ) NOT NULL ,
			`TaskType` SMALLINT( 1 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `TaskType` , `TimeDescription`,`enabled`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}	

		
		if(!$this->TABLE_EXISTS('webfilters_bigcatzlogs',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_bigcatzlogs` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`category_table` VARCHAR( 90 ) NOT NULL ,
			`category` VARCHAR( 60 ) NOT NULL ,
			`AddedItems` BIGINT( 100 ) NOT NULL ,
			INDEX ( `AddedItems` , `category`,`zDate`),
			KEY `category_table`(`category_table`)
			
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		if(!$this->FIELD_EXISTS("webtests", "family")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `family` VARCHAR( 128 ) NOT NULL,ADD INDEX (`family`)");}
		if(!$this->FIELD_EXISTS("webtests", "zDate")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,ADD INDEX (`zDate`)");}
		if(!$this->FIELD_EXISTS("webtests", "ipaddr")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `ipaddr` VARCHAR(50) NOT NULL,ADD INDEX (`ipaddr`)");}
		if(!$this->FIELD_EXISTS("webtests", "SiteInfos")){$this->QUERY_SQL("ALTER TABLE `webtests` ADD `SiteInfos`  TEXT NOT NULL");}		
		if(!$this->FIELD_EXISTS("webfilters_sqtimes_rules", "Allow")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqtimes_rules` ADD `Allow`  SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `Allow` )");}
		if(!$this->FIELD_EXISTS("webfilters_sqtimes_rules", "TemplateError")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqtimes_rules` ADD `TemplateError`  TEXT  NOT NULL");}
		
		if(!$this->TABLE_EXISTS('webfilters_sqtimes_assoc',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqtimes_assoc` (
			`zMD5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`TimeRuleID` INT( 100 ) NOT NULL ,
			`gpid` INT( 100 ) NOT NULL ,
			INDEX ( `zMD5` , `TimeRuleID`,`gpid`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "FATAL !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		}		

		if(!$this->TABLE_EXISTS('webfilters_sqgroups',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqgroups` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`GroupName` VARCHAR( 128 ) NOT NULL ,
			`GroupType` VARCHAR(50) NOT NULL ,
			`acltpl` VARCHAR(90) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `GroupName` , `enabled`,`GroupType`),
			KEY `acltpl`(`acltpl`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}	

		if(!$this->TABLE_EXISTS('webfilters_sqitems',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqitems` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`pattern` VARCHAR( 128 ) NOT NULL ,
			`gpid` INT( 100 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			INDEX ( `pattern` , `enabled`,`gpid`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		
		if(!$this->TABLE_EXISTS('webfilters_sqacls',$this->database)){	
			$sql="CREATE TABLE `webfilters_sqacls` (
			`ID` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`aclname` VARCHAR( 128 ) NOT NULL ,
			`acltpl`  VARCHAR( 90 ) NOT NULL ,
			`enabled` SMALLINT( 1 ) NOT NULL ,
			`xORDER` SMALLINT( 2 ) NOT NULL ,
			INDEX ( `aclname` , `enabled`,`xORDER`),
			KEY `acltpl`(`acltpl`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "FATAL !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		}
		if(!$this->FIELD_EXISTS("webfilters_sqacls", "xORDER")){
			$this->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `xORDER` smallint(2) NOT NULL,ADD INDEX(`xORDER`)");
		}
	

	if(!$this->TABLE_EXISTS('webfilters_sqaclaccess',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqaclaccess` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT( 100 ) NOT NULL ,
			`httpaccess` VARCHAR( 60 ) NOT NULL ,
			`httpaccess_value`  SMALLINT( 1 ) NOT NULL,
			`httpaccess_data`  VARCHAR(255) NOT NULL,
			INDEX ( `aclid` ,`httpaccess_value`),
			KEY `httpaccess`(`httpaccess`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "FATAL !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		}
		
		
		
		if(!$this->TABLE_EXISTS('webfilters_sqacllinks',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_sqacllinks` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`aclid` BIGINT( 100 ) NOT NULL ,
			`negation` smallint(1) NOT NULL ,
			`gpid` INT( 100 ) NOT NULL ,
			INDEX ( `aclid` , `gpid`,`negation`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){if($GLOBALS["AS_ROOT"]){echo "FATAL !!! $this->mysql_error\n-----\n$sql\n-----\n";}}
		}
		
		if(!$this->FIELD_EXISTS("webfilters_sqacllinks", "negation")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqacllinks` ADD `negation` smallint(1) NOT NULL");}

		if(!$this->TABLE_EXISTS('webfilters_usersasks',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_usersasks` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`ipaddr` VARCHAR( 60 ) NOT NULL ,
			`sitename` VARCHAR( 255 ) NOT NULL ,
			`uid` VARCHAR( 128 ) NOT NULL ,
			INDEX ( `ipaddr`),
			KEY `sitename`(`sitename`),
			KEY `uid`(`uid`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('squid_storelogs',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `squid_storelogs` (
				  `ID` bigint(100) NOT NULL AUTO_INCREMENT,
				  `filename` varchar(128) NOT NULL,
				  `fileext` varchar(4) NOT NULL,
				  `filesize` bigint(100)  NOT NULL,
				  `filecontent` longblob  NOT NULL,
				  `filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `Compressedsize` bigint(100) NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `filename` (`filename`),
				  KEY `filesize` (`filesize`),
				  KEY `fileext` (`fileext`),
				  KEY `filetime` (`filetime`),
				  KEY `Compressedsize` (`Compressedsize`)
				)";	
			$this->QUERY_SQL($sql,$this->database);
		} 
		
		
		if(!$this->TABLE_EXISTS('webfilters_nodes',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_nodes` (
			`MAC` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`uid` VARCHAR( 128 ) NOT NULL ,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`nmap` smallint(1) NOT NULL ,
			`nmapreport` TEXT NOT NULL ,
			 INDEX ( `uid`,`nmap`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_backupeddbs',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_backupeddbs` (
			`filepath` VARCHAR( 128 ) NOT NULL PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`size` BIGINT( 100 ) NOT NULL ,
			INDEX ( `zDate`),
			KEY `size`(`size`)
			)";	

			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->FIELD_EXISTS("webfilters_nodes", "nmap")){$this->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `nmap` SMALLINT( 1 ) NOT NULL ,ADD INDEX ( `nmap` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_nodes", "nmapreport")){$this->QUERY_SQL("ALTER TABLE `webfilters_nodes` ADD `nmapreport` TEXT NOT NULL");}		
		if(!$this->FIELD_EXISTS("webfilters_usersasks", "uid")){$this->QUERY_SQL("ALTER TABLE `webfilters_usersasks` ADD `uid` VARCHAR( 128 ) NOT NULL ,ADD INDEX ( `uid` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_usersasks", "zDate")){$this->QUERY_SQL("ALTER TABLE `webfilters_usersasks` ADD `zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");}
		if(!$this->FIELD_EXISTS("webfilters_sqacls", "acltpl")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `acltpl` VARCHAR( 90 ) NOT NULL ,ADD INDEX ( `acltpl` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_sqgroups", "acltpl")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqgroups` ADD `acltpl` VARCHAR( 90 ) NOT NULL ,ADD INDEX ( `acltpl` ) ");}
		if(!$this->FIELD_EXISTS("webfilters_sqaclaccess", "httpaccess_data")){$this->QUERY_SQL("ALTER TABLE `webfilters_sqaclaccess` ADD `httpaccess_data` VARCHAR( 255 ) NOT NULL");}

		
		
		if(!$this->TABLE_EXISTS('squidservers',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`squidservers` (
				`ipaddr` VARCHAR( 90 ) NOT NULL ,
				`port` INT( 2 ) NOT NULL DEFAULT 9000 ,
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`udpated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`created` TIMESTAMP NOT NULL ,
				PRIMARY KEY ( `ipaddr` ) ,
				INDEX ( `hostname` , `udpated` , `created` )
				)";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			}
		}

		if(!$this->FIELD_EXISTS("squidservers", "udpated")){
			$this->QUERY_SQL("ALTER TABLE `squidservers` ADD `udpated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,ADD INDEX ( `udpated` )");
		}

		if(!$this->TABLE_EXISTS('ftpunivtlse1fr',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`ftpunivtlse1fr` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`filename` VARCHAR( 60) NOT NULL,
				`websitesnum` INT( 10 ) NOT NULL ,
				`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				PRIMARY KEY ( `filename` ) ,
				INDEX ( `websitesnum` , `zmd5` , `zDate` )
				)";
			$this->QUERY_SQL($sql,$this->database);
		}			
		
		
		

		if(!$this->TABLE_EXISTS('webfilter_members',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_members` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					pattern VARCHAR(90) NOT NULL,
					enabled INT(1) NOT NULL DEFAULT '1' ,
					groupid INT(10) NOT NULL DEFAULT '0' ,
					membertype INT(1) NOT NULL DEFAULT '0' ,
					  KEY `pattern` (`pattern`),
					  KEY `groupid` (`groupid`),
					  KEY `membertype` (`membertype`),
					  KEY `enabled` (`enabled`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	
		
		
		if(!$this->TABLE_EXISTS('cacheconfig',$this->database)){	
			$sql="CREATE TABLE `cacheconfig` (
					`uuid` VARCHAR( 90 ) NOT NULL ,
					`hostname` VARCHAR( 128 ) NOT NULL ,
					`workers` INT( 2 ) NOT NULL ,
					`cachesDirectory` VARCHAR( 255 ) NOT NULL,
					`globalCachesize` INT( 100 ) NOT NULL ,
					`alternateConfig` MEDIUMTEXT NOT NULL ,
					PRIMARY KEY ( `uuid` ) ,
					INDEX ( `hostname` , `workers` , `globalCachesize` )
					) ";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('cachestatus',$this->database)){	
			$sql="CREATE TABLE `cachestatus` (
					`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`uuid` VARCHAR( 90 ) NOT NULL ,
					`cachedir` VARCHAR( 255 ) NOT NULL ,
					`maxsize` BIGINT( 100 ) NOT NULL ,
					`currentsize` BIGINT( 100 ) NOT NULL ,
					`pourc` VARCHAR( 20 ) NOT NULL,
					INDEX ( `maxsize` , `currentsize` , `pourc` ),
					KEY `uuid` (`uuid`),
					KEY `cachedir` (`cachedir`)
					) ";
			$this->QUERY_SQL($sql,$this->database);
		}		
	
		if(!$this->FIELD_EXISTS("webfilter_members", "membertype")){
			$this->QUERY_SQL("ALTER TABLE `webfilter_members` 
			ADD `membertype` INT(1) NOT NULL ,ADD KEY `membertype` (`membertype`)");}		
		
		if(!$this->TABLE_EXISTS('webfilter_bannedexts',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_bannedexts` (
				  `zmd5` varchar(90) NOT NULL,
				  `ext` varchar(10) NOT NULL,
				  `description` varchar(255) NOT NULL,
				  `enabled` tinyint(1) NOT NULL DEFAULT '1',
				  `ruleid` int(2) NOT NULL,
				  UNIQUE KEY `zmd5` (`zmd5`),
				  KEY `ext` (`ext`,`enabled`,`ruleid`),
				  KEY `description` (`description`)
				)";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('webfilter_bannedextsdoms',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_bannedextsdoms` (
				  `zmd5` varchar(90) NOT NULL,
				  `ext` varchar(10) NOT NULL,
				  `description` varchar(255) NOT NULL,
				  `enabled` tinyint(1) NOT NULL DEFAULT '1',
				  `ruleid` int(2) NOT NULL,
				  UNIQUE KEY `zmd5` (`zmd5`),
				  KEY `ext` (`ext`,`enabled`,`ruleid`),
				  KEY `description` (`description`)
				)";
			$this->QUERY_SQL($sql,$this->database);
		}

		
		if(!$this->TABLE_EXISTS('hotspot_sessions',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`hostspot_sessions` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT( 100 ) NOT NULL ,
			`maxtime` INT( 100 ) NOT NULL ,
			`finaltime` INT( 100 ) NOT NULL ,
			`username` VARCHAR( 128 ) NOT NULL ,
			`MAC` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
			`uid` VARCHAR( 128 ) NOT NULL ,
			`hostname` VARCHAR( 128 ) NOT NULL ,			
			PRIMARY KEY ( `md5` ) ,
			INDEX ( `logintime` , `maxtime` , `username` ,`finaltime`),
			KEY `MAC` (`MAC`),
			KEY `uid` (`uid`),
			KEY `hostname` (`hostname`)
			)";	
			$this->QUERY_SQL($sql,$this->database);
		}	
		if(!$this->FIELD_EXISTS("hostspot_sessions", "finaltime")){$this->QUERY_SQL("ALTER TABLE `hostspot_sessions` ADD `finaltime` BIGINT( 100 ) NOT NULL ,ADD INDEX ( `finaltime` )");}
		
		if(!$this->TABLE_EXISTS('webfilter_dnsbl',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_dnsbl` (
				  `dnsbl` varchar(128) NOT NULL,
				  `name` varchar(128) NOT NULL,
				  `uri` varchar(128) NOT NULL ,
				  `enabled` tinyint(1) NOT NULL DEFAULT '1',
				  UNIQUE KEY `dnsbl` (`dnsbl`),
				  KEY `name` (`name`,`enabled`)
				)";
			$this->QUERY_SQL($sql,$this->database);
		}		
		$this->checkDNSBLTables();

		if(!$this->TABLE_EXISTS('webfilters_categories_caches',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_categories_caches` (
			  `categorykey` varchar(50) NOT NULL,
			  `description` text NOT NULL,
			  `picture` varchar(50) NOT NULL,
			  `master_category` varchar(50) NOT NULL,
			  `categoryname` varchar(128) NOT NULL,
			  PRIMARY KEY (`categorykey`),
			  KEY `category` (`master_category`),
			  KEY `categoryname` (`categoryname`),
			  FULLTEXT KEY `description` (`description`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('webfilters_blkwhlts',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_blkwhlts` (
			  `pattern` varchar(128) NOT NULL,
			  `description` text NOT NULL,
			  `enabled` TINYINT(1) NOT NULL,
			  `PatternType` TINYINT(1) NOT NULL,
			  `blockType` TINYINT(1) NOT NULL,
			  PRIMARY KEY (`pattern`),
			  KEY `enabled` (`enabled`),
			  KEY `blockType` (`blockType`),
			  FULLTEXT KEY `description` (`description`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}	
		
		if(!$this->FIELD_EXISTS("webfilters_blkwhlts", "zmd5")){
			$this->QUERY_SQL("ALTER TABLE `webfilters_blkwhlts` ADD `zmd5` VARCHAR( 90 ) NOT NULL ,ADD INDEX ( `zmd5` )");} 

		if(!$this->TABLE_EXISTS('UserAutDB',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `UserAutDB` (
			  `zmd5` varchar(90) NOT NULL,
			  `MAC` varchar(90) NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `uid` varchar(128) NOT NULL,
			  `hostname` varchar(128) NOT NULL,
			  `UserAgent` varchar(128) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `uid` (`uid`),
			  KEY `hostname` (`hostname`),
			  KEY `UserAgent` (`UserAgent`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
		
		if(!$this->TABLE_EXISTS('webfilter_blks',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_blks` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	modeblk INT(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `category` (`category`),
				  KEY `modeblk` (`modeblk`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	
		
		if(!$this->TABLE_EXISTS('webfilter_termsg',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_termsg` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   groupname VARCHAR(128) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   KEY `groupname` (`groupname`),
				   KEY `enabled` (`enabled`)
				  
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilter_terms',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_terms` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   term VARCHAR(255) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   xregex smallint(1) NOT NULL,
				   UNIQUE KEY `term` (`term`),
				   KEY `enabled` (`enabled`),
				   KEY `xregex` (`xregex`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
	if(!$this->FIELD_EXISTS("webfilter_terms", "xregex")){$this->QUERY_SQL("ALTER TABLE `webfilter_terms` ADD `xregex` smallint( 1 ) NOT NULL ,ADD INDEX ( `xregex` )");}

		if(!$this->TABLE_EXISTS('webfilter_termsassoc',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_termsassoc` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   term_group BIGINT( 100) NOT NULL,
				   termid BIGINT( 100)NOT NULL,
				   KEY `term_group` (`term_group`),
				   KEY `termid` (`termid`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

		if(!$this->TABLE_EXISTS('webfilter_ufdbexpr',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_ufdbexpr` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   rulename VARCHAR(128) NOT NULL,
				   ruleid  BIGINT( 100) NOT NULL,
				   enabled smallint(1) NOT NULL,
				   KEY `rulename` (`rulename`),
				   KEY `enabled` (`enabled`),
				   KEY `ruleid` (`ruleid`)
				  
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

			if(!$this->TABLE_EXISTS('webfilter_ufdbexprassoc',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_ufdbexprassoc` (
				  `ID` BIGINT( 100) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				   groupid  BIGINT( 100) NOT NULL,
				   termsgid  BIGINT( 100) NOT NULL,
				   enabled INT(1) NOT NULL,
				   KEY `ID` (`ID`),
				   KEY `groupid` (`groupid`),
				   KEY `enabled` (`enabled`),
				   KEY `termsgid` (`termsgid`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}		


		if(!$this->TABLE_EXISTS('webfilters_dtimes_blks',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_dtimes_blks` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	modeblk INT(1) NOT NULL,
				  	category VARCHAR(128) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `category` (`category`),
				  KEY `modeblk` (`modeblk`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

		if(!$this->TABLE_EXISTS('webfilters_rewriterules',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_rewriterules` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    rulename VARCHAR(128) NOT NULL,
				  	enabled TINYINT(1) NOT NULL DEFAULT 1,
				  	ItemsCount INT(10) NOT NULL,
				  KEY `rulename` (`rulename`),
				  KEY `enabled` (`enabled`),
				  KEY `ItemsCount` (`ItemsCount`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_updates',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilters_updates` (
				    tablename VARCHAR(128) NOT NULL PRIMARY KEY,
				  	zDate timestamp NOT NULL,
				  	updated TINYINT(1) NOT NULL,
				  KEY `zDate` (`zDate`),
				  KEY `updated` (`updated`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('webfilters_rewriteitems',$this->database)){	
					$sql="CREATE TABLE IF NOT EXISTS `webfilters_rewriteitems` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    ruleid INT(2) NOT NULL,
				  	frompattern VARCHAR(128) NOT NULL,
				  	topattern VARCHAR(128) NOT NULL,
				  	enabled TINYINT(1) NOT NULL DEFAULT 1,
				  KEY `ruleid` (`ruleid`),
				  KEY `frompattern` (`frompattern`),
				  KEY `enabled` (`enabled`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

			if(!$this->TABLE_EXISTS('webstats_backup',$this->database)){	
					$sql="CREATE TABLE IF NOT EXISTS `webstats_backup` (
				     `tablename` VARCHAR( 90 )  NOT NULL PRIMARY KEY,
				     `filepath` VARCHAR(255) NOT NULL,
				  	 `filesize` BIGINT(100) NOT NULL,
					 KEY `filesize` (`filesize`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('webfilter_assoc_groups',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `webfilter_assoc_groups` (
				  `ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				    webfilter_id INT(2) NOT NULL,
				  	group_id INT(2) NOT NULL,
				  	zMD5 VARCHAR(09) NOT NULL,
				  KEY `webfilter_id` (`webfilter_id`),
				  KEY `group_id` (`group_id`),
				  UNIQUE KEY `zMD5` (`zMD5`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}			
		if(!$this->FIELD_EXISTS("webfilter_assoc_groups", "zMD5")){$this->QUERY_SQL("ALTER TABLE `webfilter_assoc_groups` ADD `zMD5` VARCHAR( 90 ) NOT NULL ,ADD UNIQUE KEY `zMD5` (`zMD5`)");}		
		
		if(!$this->TABLE_EXISTS('instant_updates',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `instant_updates` (
				  `ID` BIGINT( 100 ) NOT NULL PRIMARY KEY ,
				   `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  	CountItems BIGINT(100) NOT NULL,
				  	DbCount BIGINT(100) NOT NULL,
				    KEY `zDate` (`zDate`),
				  	KEY `CountItems` (`CountItems`),
				  	KEY `DbCount` (`DbCount`)
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

		if(!$this->TABLE_EXISTS('uris_malwares',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `uris_malwares` (
				  `uri` VARCHAR( 255 ) NOT NULL PRIMARY KEY 
				  ) ";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('UserAgents',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `UserAgents` (
				  `pattern` VARCHAR( 255 ) NOT NULL PRIMARY KEY 
				  ) ";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('uris_phishing',$this->database)){	
			$sql="CREATE TABLE IF NOT EXISTS `uris_phishing` (
				  `uri` VARCHAR( 255 ) NOT NULL PRIMARY KEY 
				  ) ";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		if(!$this->TABLE_EXISTS('tables_day',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `tables_day` (
			  `tablename` varchar(90) NOT NULL,
			  `zDate` date NOT NULL,
			  `size` int(100) NOT NULL,
			  `size_cached` int(100) NOT NULL,
			  `totalsize` int(100) NOT NULL,
			  `requests` int(100) NOT NULL,
			  `cache_perfs` int(2) NOT NULL,
			  `Hour` int(1) NOT NULL,
			  `members` int(1) NOT NULL DEFAULT '0',
			  `month_members` int(1) NOT NULL DEFAULT '0',
			  `month_flow` int(1) NOT NULL DEFAULT '0',
			  `blocks` int(1) NOT NULL,
			  `totalBlocked` bigint(100) NOT NULL,
			  `weekdone` smallint(1) NOT NULL DEFAULT '0',
			  `weekbdone` smallint(1) NOT NULL DEFAULT '0',
			  `month_query` tinyint(1) NOT NULL DEFAULT '0',
			  `not_categorized` INT(50) NOT NULL DEFAULT '0',
			  `visited_day` tinyint(1) NOT NULL DEFAULT '0',
			  `memberscentral` tinyint(1) NOT NULL DEFAULT '0',
			  `backuped` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`tablename`),
			  KEY `zDate` (`zDate`,`size`,`size_cached`,`cache_perfs`),
			  KEY `Hour` (`Hour`),
			  KEY `totalsize` (`totalsize`),
			  KEY `requests` (`requests`),
			  KEY `members` (`members`),
			  KEY `memberscentral` (`memberscentral`),
			  KEY `month_members` (`month_members`),
			  KEY `month_flow` (`month_flow`),
			  KEY `blocks` (`blocks`),
			  KEY `totalBlocked` (`totalBlocked`),
			  KEY `weekdone` (`weekdone`),
			  KEY `weekbdone` (`weekbdone`),
			  KEY `not_categorized` (`not_categorized`),
			  KEY `month_query` (`month_query`),
			  KEY `visited_day` (`visited_day`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if($GLOBALS["VERBOSE"]){echo "Check tables_day/blocks\n";}
		if(!$this->FIELD_EXISTS("tables_day", "blocks")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `blocks` INT( 1 ) NOT NULL ,ADD INDEX ( `blocks` )");}
		if($GLOBALS["VERBOSE"]){echo "Check tables_day/totalBlocked\n";}
		if(!$this->FIELD_EXISTS("tables_day", "totalBlocked")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `totalBlocked` BIGINT( 100 ) NOT NULL ,ADD INDEX ( `totalBlocked` )");}
		if($GLOBALS["VERBOSE"]){echo "Check tables_day/weekdone\n";}
		if(!$this->FIELD_EXISTS("tables_day", "weekdone")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `weekdone` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `weekdone` )");}
		if($GLOBALS["VERBOSE"]){echo "Check tables_day/weekbdone\n";}
		if(!$this->FIELD_EXISTS("tables_day", "weekbdone")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `weekbdone` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `weekbdone` )");}
		if($GLOBALS["VERBOSE"]){echo "Check tables_day/month_query\n";}
		if(!$this->FIELD_EXISTS("tables_day", "month_query")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `month_query` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `month_query` ) ");}
		if(!$this->FIELD_EXISTS("tables_day", "not_categorized")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `not_categorized` INT( 50 ) NOT NULL DEFAULT '0',ADD INDEX ( `not_categorized` ) ");}
		if(!$this->FIELD_EXISTS("tables_day", "visited_day")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `visited_day` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `visited_day` )");}
		if(!$this->FIELD_EXISTS("tables_day", "memberscentral")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `memberscentral` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `memberscentral` )");}
		if(!$this->FIELD_EXISTS("tables_day", "backuped")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `backuped` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `backuped` )");}
		if(!$this->FIELD_EXISTS("tables_day", "month_flow")){$this->QUERY_SQL("ALTER TABLE `tables_day` ADD `month_flow` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `month_flow` )");}
		
		 
		
		
	if(!$this->TABLE_EXISTS('squidtpls',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `squidtpls` (
			  `zmd5` varchar(90)  NOT NULL,
			  `template_name` varchar(128)  NOT NULL,
			  `template_body` LONGTEXT  NOT NULL,
			  `template_header` LONGTEXT  NOT NULL,
			  `template_title` varchar(255)  NOT NULL,
			  `template_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `lang` varchar(5)  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `template_name` (`template_name`,`lang`),
			  KEY `template_title` (`template_title`),
			  KEY `template_time` (`template_time`),
			  FULLTEXT KEY `template_body` (`template_body`)
			)";		
		$this->QUERY_SQL($sql,$this->database);
		}
		
		
		if(!$this->FIELD_EXISTS("squidtpls", "template_time")){		
			$sql="ALTER TABLE `squidtpls` ADD `template_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,ADD INDEX (`template_time`)"; 
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}			
		}			
		
		if(!$this->FIELD_EXISTS("squidtpls", "template_header")){$this->QUERY_SQL("ALTER TABLE `squidtpls` ADD `template_header` LONGTEXT  NOT NULL");}
		
		if(!$this->TABLE_EXISTS('tables_hours',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `tables_hours` (
			  `tablename` varchar(90) NOT NULL,
			  `zDate` date NOT NULL,
			  `size` BIGINT(100) NOT NULL,
			  `size_cached` BIGINT(100) NOT NULL,
			  `totalsize` INT( 100 ) NOT NULL ,
			  `requests` INT( 100 ) NOT NULL ,
			  `cache_perfs` INT( 2 ) NOT NULL ,
			  `members` int(1) NOT NULL,
			  PRIMARY KEY (`tablename`),
			  KEY `zDate` (`zDate`,`size`,`size_cached`,`cache_perfs`),
			  KEY `totalsize` (`totalsize`),
			  KEY `requests` (`requests`)
			)";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
		}		
		

		if(!$this->TABLE_EXISTS('updateblks_events',$this->database)){	
		$sql="CREATE TABLE `squidlogs`.`updateblks_events` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`zDate` TIMESTAMP NOT NULL ,
			`PID` INT( 5 ) NOT NULL ,
			`function` VARCHAR( 50 ) NOT NULL ,
			`category` VARCHAR( 50 ) NOT NULL ,
			`text` TEXT NOT NULL ,
			INDEX ( `zDate` , `PID` , `function` ),
			KEY `category` (`category`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}
		if(!$this->FIELD_EXISTS("updateblks_events", "category")){$this->QUERY_SQL("ALTER TABLE `updateblks_events` ADD `category` VARCHAR( 50 ) NOT NULL ,ADD KEY `category` (`category`)");} 		
		
		if(!$this->TABLE_EXISTS('visited_sites',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `visited_sites` (
			  `sitename` varchar(255) NOT NULL,
			  `Querysize` int(100) NOT NULL,
			  `category` varchar(255) NOT NULL,
			  `HitsNumber` int(100) NOT NULL,
			  `country` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `whois` TEXT,
			  `probablect1` varchar(60) NOT NULL ,
			  `probablect2` varchar(60) NOT NULL ,
			  `probablect3` varchar(60) NOT NULL ,
			  `NotVisitedSended` TINYINT(1) NOT NULL,
			  `thumbnail` TINYINT(1) NOT NULL,
			  PRIMARY KEY (`sitename`),
			  KEY `Querysize` (`Querysize`,`HitsNumber`,`country`),
			  KEY `familysite` (`familysite`),
			  KEY `probablect1` (`probablect1`),
			  KEY `probablect2` (`probablect2`),
			  KEY `probablect3` (`probablect3`),
			  KEY `category` (`category`),
			  KEY `NotVisitedSended` (`NotVisitedSended`),
			  KEY `thumbnail` (`thumbnail`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->INDEX_EXISTS("visited_sites", "category", $this->database)){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD KEY `category` (`category`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "whois")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `whois` TEXT");}
		if(!$this->FIELD_EXISTS("visited_sites", "probablect1")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `probablect1` varchar(60) NOT NULL ,ADD KEY `probablect1` (`probablect1`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "probablect2")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `probablect2` varchar(60) NOT NULL ,ADD KEY `probablect2` (`probablect2`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "probablect3")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `probablect3` varchar(60) NOT NULL ,ADD KEY `probablect3` (`probablect3`)");}		
		if(!$this->FIELD_EXISTS("visited_sites", "NotVisitedSended")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `NotVisitedSended` TINYINT(1) NOT NULL ,ADD KEY `NotVisitedSended` (`NotVisitedSended`)");}
		if(!$this->FIELD_EXISTS("visited_sites", "thumbnail")){$this->QUERY_SQL("ALTER TABLE `visited_sites` ADD `thumbnail` TINYINT(1) NOT NULL ,ADD KEY `thumbnail` (`thumbnail`)");}
		
		
		
		if(!$this->TABLE_EXISTS("visited_sites_catz",$this->database)){
			$sql="CREATE TABLE IF NOT EXISTS `visited_sites_catz` (
				 `zmd5` varchar(90) NOT NULL,
				 `category` varchar(60) NOT NULL,
				 `familysite` varchar(128) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `familysite` (`familysite`),
				  KEY `category` (`category`)
				  )";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		
		if(!$this->TABLE_EXISTS('stats_appliance_events',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `stats_appliance_events` (
			  `ID` bigint(100) NOT NULL AUTO_INCREMENT,
			  `hostname` varchar(60) NOT NULL,
			  `events` varchar(255) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  PRIMARY KEY (`ID`),
			  KEY `hostname` (`hostname`),
			  KEY `zDate` (`zDate`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('categorize',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `categorize` (
			  `zmd5` varchar(90) NOT NULL,
			  `pattern` varchar(255) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `uuid` varchar(128) NOT NULL,
			  `category` varchar(80) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zDate` (`zDate`,`category`),
			  KEY `pattern` (`pattern`),
			  KEY `uuid` (`uuid`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('framework_orders',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `framework_orders` (
			  `zmd5` varchar(90) NOT NULL,
			  `ORDER` varchar(255) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  PRIMARY KEY (`zmd5`),
			  KEY `ORDER` (`ORDER`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('categorize_changes',$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `categorize_changes` (
			  `zmd5` varchar(90) NOT NULL,
			  `sitename` varchar(255) NOT NULL,
			  `category` varchar(255) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `sitename` (`sitename`),
			  KEY `category` (`category`)
			)";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->TABLE_EXISTS('categorize_delete',$this->database)){	
				$sql="CREATE TABLE IF NOT EXISTS `categorize_delete` (
				  `sitename` varchar(255) NOT NULL,
				  `category` varchar(128) NOT NULL,
				  `zmd5` varchar(90) NOT NULL,
				  `sended` INT( 1 ) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `category` (`category`),
				  KEY `sitename` (`sitename`),
				  KEY `sended` (`sended`)
				)";
				
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->FIELD_EXISTS("categorize_delete", "sended")){$this->QUERY_SQL("ALTER TABLE `categorize_delete` ADD `sended` INT( 1 ) NOT NULL ,ADD INDEX ( `sended` )");} 
		
		if(!$this->TABLE_EXISTS('personal_categories',$this->database)){	
				$sql="CREATE TABLE `squidlogs`.`personal_categories` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`category_description` VARCHAR( 255 ) NOT NULL ,
				`master_category` VARCHAR( 50 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				INDEX ( `category_description` , `sended` ) ,
				KEY `master_category` (`master_category`),
				UNIQUE (`category`))";		
				$this->QUERY_SQL($sql,$this->database);
		}
		
		
	if(!$this->TABLE_EXISTS('work_optimize',$this->database)){	
		$sql="CREATE TABLE `squidlogs`.`work_optimize` (
		`table_name` VARCHAR( 50 ) NOT NULL ,
		`job` TINYINT NOT NULL ,
		PRIMARY KEY ( `table_name` ))";
		$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('work_squid_repo',$this->database)){	
		$sql="CREATE TABLE `squidlogs`.`work_squid_repo` (
		`version` VARCHAR( 50 ) NOT NULL ,
		`package` longblob NOT NULL ,
		PRIMARY KEY ( `version` )
		)";
		$this->QUERY_SQL($sql,$this->database);
		}		
		
				
		
	if(!$this->FIELD_EXISTS("personal_categories", "master_category")){$this->QUERY_SQL("ALTER TABLE `personal_categories` ADD `master_category` VARCHAR( 50 ) NOT NULL ,ADD KEY `master_category` (`master_category`)");}
		
	if(!$this->TABLE_EXISTS('usersisp',$this->database)){			
		$sql="CREATE TABLE `squidlogs`.`usersisp` (
				`userid` BIGINT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`email` VARCHAR( 128 ) NOT NULL ,
				`user_password` VARCHAR( 90 ) NOT NULL ,
				`createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`enabled` SMALLINT( 1 ) NOT NULL ,
				`publicip` VARCHAR( 60 ) NOT NULL ,
				`macaddr` VARCHAR( 60 ) NOT NULL ,
				`language` VARCHAR( 5 ) NOT NULL ,
				`updatedon` TIMESTAMP NOT NULL ,
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`ProxyPacDatas` TEXT NOT NULL,
				`ProxyPacCompiled` TEXT NOT NULL,
				`wwwname` VARCHAR( 255 ) NOT NULL ,
				`ProxyPacRemoveProxyListAtTheEnd` SMALLINT( 1 ) NOT NULL ,
				INDEX ( `createdon` , `enabled` , `updatedon` ) ,
				KEY `wwwname` (`wwwname`),
				UNIQUE (
					`email`,
					`publicip` ,
					`macaddr` ,
					`zmd5`
				)
			) ";
			$this->QUERY_SQL($sql,$this->database);
		}

		if(!$this->FIELD_EXISTS("usersisp", "wwwname")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `wwwname` VARCHAR( 255 ) NOT NULL ,ADD KEY `wwwname` (`wwwname`)");}

		if(!$this->TABLE_EXISTS('usersisp_blkcatz',$this->database)){			
			$sql="CREATE TABLE `squidlogs`.`usersisp_blkcatz` (`category` VARCHAR( 255 ) NOT NULL PRIMARY KEY) ";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('usersisp_blkwcatz',$this->database)){			
		$sql="CREATE TABLE `squidlogs`.`usersisp_blkwcatz` (
				`category` VARCHAR( 255 ) NOT NULL PRIMARY KEY
				) ";
			$this->QUERY_SQL($sql,$this->database);
		}		

		if(!$this->TABLE_EXISTS('usersisp_catztables',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`usersisp_catztables` (
				`ID` BIGINT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`zmd5` VARCHAR( 90 ) NOT NULL,
				`category` VARCHAR( 255 ) NOT NULL,
				`userid` BIGINT( 100 ) NOT NULL,
				`blck` smallint(1) NOT NULL,
				KEY `category` (`category`),
				UNIQUE (`zmd5`),
				INDEX ( `userid` , `blck`) 
				)";
			$this->QUERY_SQL($sql,$this->database);
		}
		
		if(!$this->TABLE_EXISTS('webfilters_thumbnails',$this->database)){	
			$sql="CREATE TABLE `squidlogs`.`webfilters_thumbnails` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`withid` SMALLINT( 1 ) NOT NULL ,
				`sended` SMALLINT( 1 ) NOT NULL ,
				`picture` BLOB NOT NULL ,
				`savedate` TIMESTAMP NOT NULL ,
				`filemd5` VARCHAR( 90 ) NOT NULL ,
				`LinkTo` VARCHAR( 90 ) NOT NULL ,
				PRIMARY KEY ( `zmd5` ) ,
				UNIQUE KEY `filemd5` (`filemd5`),
				KEY `LinkTo` (`LinkTo`),
				INDEX ( `withid` , `sended` , `savedate` )
				)";
			$this->QUERY_SQL($sql,$this->database);
		}		
			
		
		
		if(!$this->FIELD_EXISTS("webfilters_thumbnails", "filemd5")){$this->QUERY_SQL("ALTER TABLE `webfilters_thumbnails` ADD `filemd5` VARCHAR( 90 ) NOT NULL ,ADD UNIQUE (`filemd5`)");}
		if(!$this->FIELD_EXISTS("webfilters_thumbnails", "LinkTo")){$this->QUERY_SQL("ALTER TABLE `webfilters_thumbnails` ADD `LinkTo` VARCHAR( 90 ) NOT NULL ,ADD KEY `LinkTo` (`LinkTo`)");}
		if(!$this->FIELD_EXISTS("usersisp", "ProxyPacDatas")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `ProxyPacDatas` TEXT");}
		if(!$this->FIELD_EXISTS("usersisp", "ProxyPacRemoveProxyListAtTheEnd")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `ProxyPacRemoveProxyListAtTheEnd` SMALLINT( 1 ) NOT NULL");}  
		if(!$this->FIELD_EXISTS("usersisp", "ProxyPacCompiled")){$this->QUERY_SQL("ALTER TABLE `usersisp` ADD `ProxyPacCompiled` TEXT NOT NULL");}
		
		$this->CreateCategoryWeightedTable();
	}
	
	function COUNT_CATEGORIES(){
		$c=0;
		$tablescat=$this->LIST_TABLES_CATEGORIES();
		while (list ($table, $none) = each ($tablescat) ){		
			$count=$this->COUNT_ROWS($table);
			$c=$c+$count;
		}
		$crypt=new mysql_catz();
		$cryptedTables=0;
		if($c<3000000){
			$cryptedTables=$crypt->COUNT_CATEGORIES();
		}
		return $c+$cryptedTables;
	}
	
	
	FUNCTION MAC_TO_NAME($MAC=null){
		if($mac=="00:00:00:00:00:00"){return null;}
		if($MAC==null){return null;}
		include_once(dirname(__FILE__)."/class.tcpip.inc");
		$ip=new IP();
		$tt=array();
		if(!$ip->IsvalidMAC($MAC)){return null;}
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT uid FROM `webfilters_nodes` WHERE `MAC`='$MAC'"));
		if($ligne["uid"]<>null){return $ligne["uid"];}
		$results=$this->QUERY_SQL("SELECT hostname FROM `UserAutDB` WHERE `MAC`='$MAC' AND LENGTH(hostname)>0");
		while ($ligne = mysql_fetch_assoc($results)) {$tt[$ligne["hostname"]]=$ligne["hostname"];}
		if(count($tt)>0){return @implode(",", $tt);}
		
	}
	
	
	function WebsiteStrip($www){
		
		$www=trim(strtolower($www));
		if($www==null){return;}
		$www=stripslashes($www);
		if(preg_match("#href=\"(.+?)\">#", $www,$re)){$www=$re[1];}
		if(preg_match("#<a href.*?http://(.+?)([\/\"'>]#i",$www,$re)){$www=$re[1];}
		$www=str_replace("http://", "", $www);
		if(preg_match("#http.*?:\/\/(.+?)[\/\s]+#",$www,$re)){$www=$re[1];return $www;}
		if(preg_match("^www\.(.+)#", $www,$re)){$www=$re[1];}
		$www=str_replace("<a href=", "", $www);
		if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/"));}
		if(preg_match("#\.php$#", $www,$re)){echo "$www php script...\n";return;}
		if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){return;}
		return $www;
	}
	
	
	function ADD_CATEGORYZED_WEBSITE($sitename,$category){
		$category=trim($category);
		$sitename=trim(strtolower($sitename));
		$sitename=str_replace("\t", "", $sitename);
		$sitename=str_replace(chr(194),"",$sitename);
		$sitename=str_replace(chr(32),"",$sitename);
		$sitename=str_replace(chr(160),"",$sitename);			
		if(trim($sitename)==null){return;}
		if(trim($category)==null){return;}
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		$sock=new sockets();
		$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
		if(strpos($category, ",")>0){$categories=explode(",",$category);}else{$categories[]=$category;}
		while (list ($index, $cat) = each ($categories) ){
			$cat=trim($cat);
			$md5=md5("$cat$sitename");
			$category_table="category_".$this->category_transform_name($cat);
			if(!$this->TABLE_EXISTS($category_table)){
				writelogs("$category_table no such table",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return false;
			}
			
			$ligneX=mysql_fetch_array($this->QUERY_SQL("SELECT zmd5 FROM $category_table WHERE pattern='$sitename'"));
			if($ligneX["zmd5"]<>null){continue;}
			$this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$cat','$sitename','$uuid')");
			if(!$this->ok){echo "categorize $sitename failed $q->mysql_error\n";return false;}			
			
			$this->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$cat','$sitename','$uuid')");
			if(!$this->ok){echo $this->mysql_error."\n";return false;}	
					
		}
		
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?export-community-categories=yes");		
		return true;
	}
	
	
	FUNCTION REMOVE_CATEGORIZED_SITENAME($sitename,$category){
		$table=null;
		if(preg_match("#category_(.+?)#",$category)){$table=$category;}
		if($table==null){$table="category_".$this->category_transform_name($category);}
		writelogs("UPDATE `$table` SET `enabled`=0 WHERE `pattern`='$sitename'",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->QUERY_SQL("UPDATE `$table` SET `enabled`=0 WHERE `pattern`='$sitename'");
		if(!$this->ok){echo $q->mysql_error;return;}
		$md5=md5("$category$sitename");
		$sql="INSERT IGNORE INTO categorize_delete (sitename,category,zmd5) VALUES ('$sitename','$category','$md5')";
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->QUERY_SQL($sql);
		$sock=new sockets();
		$categories=$this->GET_CATEGORIES($sitename,true,true,true);
		$sql="UPDATE visited_sites SET category='$categories' WHERE sitename='$sitename'";
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$this->QUERY_SQL($sql);		
		$sock->getFrameWork("squid.php?export-deleted-categories=yes");
		
	}
	
	FUNCTION UID_FROM_MAC($mac){
		if(isset($GLOBALS[__FUNCTION__][$mac])){return $GLOBALS[__FUNCTION__][$mac];}
		if(trim($mac)==null){return ;}
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT uid FROM webfilters_nodes WHERE MAC='$mac'"));
		$GLOBALS[__FUNCTION__][$mac]=$ligne["uid"];
		return $ligne["uid"];
	}
	
	
	function GET_CATEGORIES($sitename,$nocache=false,$nok9=false,$noheuristic=false,$noArticaDB=false){
		

		
		
		$GLOBALS["CATEGORIZELOGS"]=array();
		if(isset($GLOBALS["GET_CATEGORIES_MEMORY"][$sitename])){return $GLOBALS["GET_CATEGORIES_MEMORY"][$sitename];}
		$cat=array();
		$cattmp=array();
		if(trim($sitename)==null){return;}
		$sitename=strtolower(trim($sitename));
		if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		if(preg_match("#^\.(.+)#", $sitename,$re)){$sitename=$re[1];}
		if(preg_match("#^\*\.(.+)#", $sitename,$re)){$sitename=$re[1];}		
		if(substr($sitename, 0,1)=="."){$sitename=substr($sitename, 1,strlen($sitename));}
		if(trim($sitename)==null){return;}
		
		
		if(!$nocache){
			$sql="SELECT category FROM visited_sites WHERE sitename='$sitename'";
			$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
			if(trim($ligne["category"])<>null){
				if($GLOBALS["VERBOSE"]){echo "visited_sites -> $sitename= cache:{$ligne["category"]} in ". __CLASS__ ." line: ".__LINE__."\n";}
				$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");
				return addslashes($ligne["category"]);
			}
		}
		
		
		$tablescat=$this->LIST_TABLES_CATEGORIES();
		$tablescat_count=0;
		reset($tablescat);
		while (list ($table, $none) = each ($tablescat) ){
			if($table=="category_"){continue;}
			if(preg_match("#bak$#", $table)){continue;}
			$sql="SELECT category FROM $table WHERE pattern='$sitename' AND enabled=1";
			$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
			$tablescat_count++;
			if($ligne["category"]<>null){
				
				if($GLOBALS["VERBOSE"]){echo "Found {$ligne["category"]} FOR \"$sitename\" in ". __CLASS__ ." line: ".__LINE__."\n";}
				$cattmp[$ligne["category"]]=$ligne["category"];
			}
		}
		//if($GLOBALS["VERBOSE"]){echo "$sitename -> ". $tablescat_count. " categories\n";}
		$cat=array();
		if(count($cattmp)>0){
			while (list ($a, $b) = each ($cattmp) ){if($b<>null){$cat[]=$b;}}
		}
		
		if(!$noArticaDB){
			$qz=new mysql_catz();
			$catz=$qz->GET_CATEGORIES($sitename);
			if($catz<>null){
				$catsql=addslashes($catz);
				$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");
				$this->QUERY_SQL("UPDATE visited_sites SET category='$catsql' WHERE sitename='$sitename'");
				return $catz;
			}
		}
		
		
		if(count($cat)==0){
			if(!$noheuristic){
				$catz=$this->CategoriesFamily($sitename);
				if($catz<>null){
					$GLOBALS["CATEGORIZELOGS-COUNT"]++;
					$sock=new sockets();
					$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
					$newmd5=md5("$catz$sitename");
					$GLOBALS["CATEGORIZELOGS"][]="GENERIC FOUND";
					$category_table="category_".$this->category_transform_name($catz);
					$this->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$sitename','$catz')");
					$this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$catz','$sitename','$uuid')");
					$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");				
					$cat[]=$catz;
				}else{
					$GLOBALS["CATEGORIZELOGS"][]="GENERIC FAILED";
				}
			}
		}	
		

		
		if(!$nok9){
			if(count($cat)==0){
				$ext=new external_categorize($sitename);
				$extcat=trim($ext->K9());
				if($extcat<>null){
					$GLOBALS["CATEGORIZELOGS-COUNT"]++;
					$sock=new sockets();$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
					$newmd5=md5("$extcat$sitename");
					$GLOBALS["CATEGORIZELOGS"][]="K9 FOUND";
					$category_table="category_".$this->category_transform_name($extcat);
					$this->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$sitename','$extcat')");
					$this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$extcat','$sitename','$uuid')");
					$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");				
					$cat[]=$extcat;
				}else{
					$GLOBALS["CATEGORIZELOGS"][]="K9 FAILED";
				}
			}
		}
		
		
		if(!$nok9){	
			if(count($cat)==0){
				$ext=new external_categorize($sitename);
				$extcat=trim($ext->UBoxTrendmicroGetCatCode());
				if($extcat<>null){
					$GLOBALS["CATEGORIZELOGS"][]="TREND SUCCESS";
					$GLOBALS["CATEGORIZELOGS-COUNT"]++;
					$sock=new sockets();
					$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
					$newmd5=md5("$extcat$sitename");
					$category_table="category_".$this->category_transform_name($extcat);
					$this->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$sitename','$extcat')");
					$this->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$extcat','$sitename','$uuid')");
					$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");				
					$cat[]=$extcat;
				}else{
					$GLOBALS["CATEGORIZELOGS"][]="TREND FAILED";
				}
			}	
		}		
		
		
		if(count($cat)>0){
				$FOUND=FALSE;
				$sitename=addslashes($sitename);
				$FOUNDQ=mysql_fetch_array($this->QUERY_SQL("SELECT sitename FROM visited_sites WHERE sitename='$sitename'"));
				if($FOUNDQ["sitename"]<>null){$FOUND=true;}			
				$category=@implode(",", $cat);
				$category=addslashes($category);
				$familysite=$this->GetFamilySites($sitename);
				if(!$nocache){	
					if(!$FOUND){
						
						$this->QUERY_SQL("INSERT IGNORE INTO visited_sites (sitename,category,familysite) VALUES ('$sitename','$category','$familysite')");
					}else{
						$this->QUERY_SQL("UPDATE visited_sites SET category='$category' WHERE sitename='$sitename'");
					}
				}
				$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");
				$GLOBALS["GET_CATEGORIES_MEMORY"][$sitename]=$category;
				return $category;
			}
			
		$ipaddr=gethostbyname($sitename);
		if($ipaddr==$sitename){
			$ipaddr=gethostbyname("www.$sitename");
			if($ipaddr==$sitename){
				$this->categorize_reaffected($sitename);
				$GLOBALS["CATEGORIZELOGS-COUNT"]++;
				return "reaffected";
			}
		}			
			
			
	}
	
	
	public function GetFamilySites($sitename){
		if(strpos(" $sitename", ".")==0){return $sitename;}
		
		
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $sitename)){return $sitename;}
		if(function_exists("getRegisteredDomain")){
			$tmp=trim(getRegisteredDomain($sitename));
			if($tmp<>null){return $tmp;}
		}
		
		$tmp=$this->GetFamilySitestt($sitename);
		if(strpos($tmp, ".")>0){return $tmp;}
		
		writelogs("Fatal unable to find familysite for $sitename",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		$bits = explode('.', $sitename);
    	$idz=count($bits);
    	$idz-=3;
    	if (strlen($bits[($idz+2)])==2) {
   			$url=$bits[$idz].'.'.$bits[($idz+1)].'.'.$bits[($idz+2)];
    		} else if (strlen($bits[($idz+2)])==0) {
    			$url=$bits[($idz)].'.'.$bits[($idz+1)];
    		} else {
    			$url=$bits[($idz+1)].'.'.$bits[($idz+2)];
   			 }
    if(substr($url, 0,1)=="."){$url=substr($url, 1,strlen($url));}
    return $url;
    }

    
    public function categorize_reaffected($www){
    	$www=trim(strtolower($www));
    	if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
    	if(preg_match("#^\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#^\*\.(.+)#", $www,$re)){$www=$re[1];}
    	
    	
    	if(!isset($GLOBALS["MYUUID"])){
    		$sock=new sockets();
			$GLOBALS["MYUUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
    	}
    	$newmd5=md5("reaffected$www");
    	$this->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$www','reaffected')");
		$this->QUERY_SQL("INSERT IGNORE INTO category_reaffected (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'reaffected','$www','{$GLOBALS["MYUUID"]}')");
		$this->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
    }
    
    
    private function GetFamilySitestt($domain){
			$tlds = array(
			    'com'=>true,
				'name'=>true,
				're'=>true,
				'ru'=>true,
				'ws'=>true,
				'org'=>true,
				'net'=>true,
				'cn'=>array('com'=>true),
				'ar'=>array('educ'=>true,"com"=>true),
			    'uk' => array('co' => true,"ac"=>true,"gov"=>true,"org"=>true,"me"=>true),
				'id' => array('net' => true,"web"=>true,"ac"=>true,"co"=>true,"or"=>true),
				'ua' => array('dn' => true,"dp"=>true,"od"=>true),
				'au' => array('net' => true,"com"=>true,"gov"=>true),
				'pt' => array('com' => true,"gov"=>true,"uc"=>true,"ua"=>true),
				'ph'=> array('com'=>true),
				'th' => array('co' => true,"go"=>true),
				'co' => array('gov' => true,"za"=>true),
				'gi' => array('gov' => true),
				'ca' => array('qc' => true),
				'ch' =>true,
				'cn' => array('com' => true,"gov"=>true),
				'cz' =>true,
				'il' => array('co' => true),
				'io' => true,
				'info'=>true,
				'jp'=>array('ne'=>true),
				'no'=>true,
				'bz'=>true,
				'br' => array('com' => true,"org"=>true),
				'ec' => array('com' => true),
				'eg' => array('gov' => true),
				'fi'=>true,
				'fm'=>true,
				'fr'=>array('gouv' => true),
				'ua'=>array('net'=>true,"com"=>true),
				'kz'=>true,
				'vn'=>true,
				'za'=>array('co'=>true),
				
				
		);
		
		// split domain
		$parts = explode('.', $domain);
		$tmp = $tlds;
		// travers the tree in reverse order, from right to left
		foreach (array_reverse($parts) as $key => $part) {
		    if (isset($tmp[$part])) {
		        $tmp = $tmp[$part];
		    } else {
		        break;
		    }
		}
		$get=implode('.', array_slice($parts, - $key - 1));
		if(substr($get, 0,1)=="."){$get=substr($get, 1,strlen($get)); }  
		return $get; 	
    } 
    
		
		
	
	
	
private function CategoriesFamily($www){
		include_once(dirname(__FILE__)."/class.squid.categorize.generic.inc");
		$f=new generic_categorize();
		$cat=$f->GetCategories($www);
		if($cat<>null){$this->ADD_CATEGORYZED_WEBSITE($www,$cat);
			writelogs("Generic Category $cat for $www done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return $cat;
		}

}  	
	
	
	function category_transform_name($category){
			$q=new mysql_catz(true);return $q->category_transform_name($category);
	}
	
	function CachePerfHour(){
		$currentHour=date("H");
		$currentHour=$currentHour-1;
		$currentDay=date('d');
		$currentMonth=date('m');
		$currentYear=date('Y');
		if(!$this->TABLE_EXISTS("webcacheperfs")){return -1;}
		$sql="SELECT pourc FROM webcacheperfs WHERE zHour=$currentHour AND zDay=$currentDay AND zMonth=$currentMonth AND zYear=$currentYear";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs($this->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		writelogs("$sql=`{$ligne["pourc"]}%`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return $ligne["pourc"];	
		
		
	}
	
	function TransArray(){
		$q=new mysql_catz(true);
		return $q->TransArray();	
		
	}
	
	function cat_totablename($category){
		$category=trim(strtolower($category));
		$trans=$this->TransArray();
		while (list ($table, $categories) = each ($trans) ){
			if($categories==$category){return $table;}
		}
		
	}
	
	
	function tablename_tocat($tablename){
	
			$trans=$this->TransArray();
			if(!isset($trans[$tablename])){
				$ligne2=mysql_fetch_array($this->QUERY_SQL("SELECT category FROM $tablename LIMIT 0,1"));
				return $ligne2["category"];
			}
			
			return $trans[$tablename];
			
	}
	
	function filaname_tocat($filename){
		if(strpos($filename, "/domains.ufdb")>0){$filename=str_replace("/domains.ufdb", "",$filename);}
		$q=new mysql_catz(true);
		$trans=$q->TransArray();	
		$filename=basename($filename);
		$filename=str_replace(".ufdb", "", $filename);
		if(preg_match("#^category_(.*)#", $filename,$re)){$filename=$re[1];}
		if(isset($trans["category_{$filename}"])){return $trans["category_{$filename}"];}
		$array["audio-video"]="audio-video";
		if(isset($array["$filename"])){return $array["$filename"];}
		
		return "!!! $filename";
	}
	
	
	
	function category_transform_name_toulouse($category){
			if($category=="finance/banking"){$category="bank";}
			if($category=="drugs"){$category="drogue";}
			if($category=="webradio"){$category="radio";}
			if($category=="recreation/sports"){$category="sports";}
			if($category=="sslsites"){$category="verisign";}
			if($category=="children"){$category="child";}
			if($category=="porn"){$category="adult";}
			if($category=="hobby/cooking"){$category="cooking";}
			if($category=="agressive"){$category="agressif";}
			$category=str_replace('/other',"other",$category);
			if(preg_match("#.+?\/(.+)#", $category,$re)){$category=$re[1];}
			$category=str_replace('/',"",$category);
			$category=str_replace('-',"",$category);
			$category=str_replace('_',"",$category);
			return $category;	
	}	
	
	function CreateCategoryTable($category,$fulltablename=null){
		$catz=new mysql_catz();
		$catz->CreateCategoryTable($category,$fulltablename);
		if($category=="drogue"){$category="drugs";}
		if($category=="gambling"){$category="gamble";}
		if($category=="hobby/games"){$category="games";}
		if($category=="forum"){$category="forums";}
		if($category=="spywmare"){$category="spyware";}			

		if($this->EnableRemoteStatisticsAppliance==1){return;}
		$category=$this->category_transform_name($category);
		$tablename=strtolower("category_$category");
		if($fulltablename<>null){$tablename=$fulltablename;}
		if($tablename=="category_teans"){$tablename="category_teens";}
		$tablename=strtolower($tablename);
		$tablename=str_replace("category_category_","category_",$tablename);
		if($tablename=="category_drogue"){$tablename="category_drugs";}
		if($tablename=="category_gambling"){$tablename="category_gamble";}
		if($tablename=="category_hobby_games"){$tablename="category_games";}
		if($tablename=="category_forum"){$tablename="category_forums";}
		if($tablename=="category_spywmare"){$tablename="category_spyware";}
		$tablename=strtolower($tablename);
		if(!$this->TABLE_EXISTS($tablename,$this->database)){	
		if($GLOBALS["VERBOSE"]){echo "CREATE CATEGORY TABLE `$tablename`\n";}
		
		
		
		$sql="CREATE TABLE `$this->database`.`$tablename` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`pattern` VARCHAR( 255 ) NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				UNIQUE KEY `pattern` (`pattern`),
				KEY `zDate` (`zDate`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`)
			)";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("Failed to create category_$category",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}			
		
	}
	
	function CreateCategoryWeightedTable(){
		
		if(!$this->TABLE_EXISTS("phraselists_weigthed",$this->database)){	
		$sql="CREATE TABLE `$this->database`.`phraselists_weigthed` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`pattern` VARCHAR( 255 ) NOT NULL ,
				`language` VARCHAR( 40 ) NOT NULL ,
				`score` INT( 3 ) NOT NULL DEFAULT '50',
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				KEY `zDate` (`zDate`),
	  			KEY `pattern` (`pattern`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`),
	  			KEY `language` (`language`),
	  			KEY `score` (`score`)
			)";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("Failed to create phraselists_weigthed",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->CategoryWeightedImport();
		}else{
			if($this->COUNT_ROWS("phraselists_weigthed")==0){$this->CategoryWeightedImport();}
		}			
		
	}
	
	
	function CreateCategoryBannedRegexPurllistTable(){
		
		if(!$this->TABLE_EXISTS("regex_urls",$this->database)){	
		$sql="CREATE TABLE `$this->database`.`regex_urls` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`category` VARCHAR( 20 ) NOT NULL ,
				`pattern` TEXT NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '0',
				`uuid` VARCHAR( 255 ) NOT NULL ,
				`sended` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `zmd5` ) ,
				KEY `zDate` (`zDate`),
	  			KEY `enabled` (`enabled`),
	  			KEY `sended` (`sended`),
	  			KEY `category` (`category`),
	  			KEY `uuid` (`uuid`),
	  			FULLTEXT KEY `pattern` (`pattern`)
			)";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("Failed to create regex_urls",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return;}
			$this->CategoryExpressionsUrlsImport();
			
		}else{
			if($this->COUNT_ROWS("regex_urls")==0){$this->CategoryExpressionsUrlsImport();}
		}			
		
	}	
	
	function CategoryWeightedImport(){
		$f=unserialize(@file_get_contents("ressources/databases/weightedPhrases.db"));
		$prefix="INSERT IGNORE INTO phraselists_weigthed (zmd5,zDate,category,pattern,score,uuid,language) VALUES ";
		while (list ($linum, $line) = each ($f) ){
			if(trim($line)==null){continue;}
			$t[]=$line;
			if(count($t)>500){$this->QUERY_SQL($prefix.@implode(",", $t));$t=array();}
		}
		if(count($t)>0){$this->QUERY_SQL($prefix.@implode(",", $t));$t=array();}	
		
		
		
	}
	
	function CategoryExpressionsUrlsImport(){
		$f["porn"][]="(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex|phone)+.*(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx)s?";
		$f["porn"][]="(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx)+.*(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex)+";
		$f["porn"][]="(adultsight|adultsite|adultsonly|adultweb|blowjob|bondage|centerfold|cumshot|cyberlust|cybercore|hardcore|masturbat)";
		$f["porn"][]="(bangbros|pussylip|playmate|pornstar|sexdream|showgirl|softcore|striptease)";
		$f["porn"][]="(incest|obscene|pedophil|pedofil)";
		$f["porn"][]="(sex|fuck|boob|cunt|fetish|tits|anal|hooter|asses|shemale|submission|porn|xxx|busty|knockers|slut|nude|naked|pussy)+.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)";
		$f["porn"][]="(girls|babes|bikini|model)+.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)";
		
		
		$f["models"][]="(male|m[ae]n|boy|girl|beaut|agen[ct]|glam)+.*(model|talent)";
		$f["proxies"][]="(cecid.php|nph-webpr|nph-pro|/dmirror|cgiproxy|phpwebproxy|__proxy_url|proxy.php)";
		$f["proxies"][]="(anonymizer|proxify|megaproxy)";
		$f["gamble"][]="(casino|bet(ting|s)|lott(ery|o)|gam(e[rs]|ing|bl(e|ing))|sweepstake|poker)";
		
		$f["recreation/sports"][]="(bowling|badminton|box(e[dr]|ing)|skat(e[rs]|ing)|hockey|soccer|nascar|wrest|rugby|tennis|sports|cheerlead|rodeo|cricket|badminton|stadium|derby)";
		$f["recreation/sports"][]="((paint|volley|bas(e|ket)|foot|quet)ball|/players[/\.]?|(carn|fest)ival)";
		
		$f["dating"][]="(meet|hook|mailord|latin|(asi|mexic|dominic|russi|kore|colombi|balk)an|brazil|filip|french|chinese|ukrain|thai|tour|foreign|date)+.*(dar?[lt]ing|(sing|coup)le|m[ae]n|girl|boy|guy|mat(e|ing)|l[ou]ve?|partner|meet)";
		$f["dating"][]="(marr(y|i[ae])|roman(ce|tic)|fiance|bachelo|dating|affair|personals)";
		$f["tracker"][]="(adlog.php|cnt.cgi|count.cgi|count.dat|count.jsp|count.pl|count.php|counter.cgi|counter.js|counter.pl|countlink.cgi|fpcount.exe|logitpro.cgi|rcounter.dll|track.pl|w_counter.js)";
		
		$prefix="INSERT IGNORE INTO regex_urls (zmd5,zDate,category,pattern,enabled) VALUES ";
		while (list ($category, $array) = each ($f) ){
			while (list ($index, $pattern) = each ($array) ){
				$md5=md5("$category$pattern");
				$date=date('Y-m-d H:i:s');
				$s[]="('$md5','$date','$category','$pattern',0)";
				
			}
			
		}
		if(count($s)>0){$this->QUERY_SQL($prefix.@implode(",", $s));$s=array();}	
		
		
	}
	

	function CreateWeekBlockedTable($week=null){
		if(preg_match("#[0-9]+_blocked_week#", $week)){$tableblock=$week;}
		else{
			if(!is_numeric($week)){$week=date('YW')."_blocked_week";}$tableblock="{$week}_blocked_week";
		}
		$tableblock=str_replace("_blocked_week_blocked_week", "_blocked_week", $tableblock);
		if(!$this->TABLE_EXISTS($tableblock)){		
			$sql="CREATE TABLE `$tableblock` (
			  `zMD5` VARCHAR(100) NOT NULL,
			  `day` INT(2) NOT NULL ,
			  `hits` BIGINT(100) NOT NULL ,
			  `client` varchar(90) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` BIGINT(100) NOT NULL,
			  `website` varchar(125) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `rulename` varchar(50) NOT NULL,
			  `event` varchar(20) NOT NULL,
			  `why` varchar(90) NOT NULL,
			  `explain` text NOT NULL,
			  `blocktype` varchar(255) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `day` (`day`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `website` (`website`),
			  KEY `category` (`category`),
			  KEY `rulename` (`rulename`),
			  KEY `hits` (`hits`),
			  KEY `event` (`event`),
			  KEY `why` (`why`)
			)"; 
			$this->QUERY_SQL($sql); 
			if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_error=$this->mysql_error."\n$sql";return false;}else{writelogs("Checking $tableblock SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}

		}		
		return true;
	
	}
	
	function CreateUserSizeRTTTable(){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("UserSizeRTT",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `UserSizeRTT` (
			  `zMD5` varchar(90) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `zdate` DATETIME NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` BIGINT(100) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `UserAgent` varchar(128) NOT NULL,
			  `size` BIGINT(100) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `uid` (`uid`),
			  KEY `zdate` (`zdate`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `MAC` (`MAC`),
			  KEY `UserAgent` (`UserAgent`),
			  KEY `size` (`size`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}	
	}

	function CreateUserSizeRTT_day($tablename){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` varchar(90) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `zdate` DATE NOT NULL,
			  `ipaddr` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` BIGINT(100) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `UserAgent` varchar(128) NOT NULL,
			  `size` BIGINT(100) NOT NULL,
			  `hits` BIGINT(100) NOT NULL,
			  `hour` smallint(4) NOT NULL,
			  KEY `uid` (`uid`),
			  KEY `zdate` (`zdate`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `MAC` (`MAC`),
			  KEY `UserAgent` (`UserAgent`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `hour` (`hour`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}

		return true;
		
	}
	
	
	
	function CreateHourTable($tablename){
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` varchar(90) NOT NULL,
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` BIGINT(100) NOT NULL,
			  `hour` int(2) NOT NULL,
			  `remote_ip` varchar(50) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `country` varchar(50) NOT NULL,
			  `size` int(10) NOT NULL,
			  `hits` int(10) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `cached` int(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `country` (`country`),
			  KEY `hour` (`hour`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}	

		if(!$this->FIELD_EXISTS("$tablename", "hostname")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
		return true;
		
	}
		
	private function checkDNSBLTables(){
		if($this->COUNT_ROWS("webfilter_dnsbl")>0){return;}
		$f=@file_get_contents(dirname(__FILE__)."/databases/db.surbl.txt");
		
		$prefix="INSERT IGNORE INTO webfilter_dnsbl(`dnsbl`,`name`,`uri`,`enabled`) VALUES ";
		if(preg_match_all("#<server>(.+?)</server>#is",$f,$servers)){
			while (list ($num, $line) = each ($servers[0])){
				if(preg_match("#<item>(.+?)</item>#",$line,$re)){$server_uri=$re[1];}
				if(preg_match("#<name>(.+?)</name>#",$line,$re)){$name=$re[1];}
				if(preg_match("#<uri>(.+?)</uri>#",$line,$re)){$info=$re[1];}
				$name=addslashes($name);
				$info=addslashes($info);
				$SQ[]="('$server_uri','$name','$info',0)";
			}
			
		}else{
			writelogs("Unable to preg_match in ".strlen($f)." bytes",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		if(count($SQ)>0){
			$this->QUERY_SQL($prefix.@implode($SQ, ","));
		}else{
			writelogs("Warning unable to found any DNSBL item",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		}	

	function CreateWeekTable($tablename=null){
		if($tablename==null){$tablename=date('YW')."_week";}
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` varchar(90) NOT NULL,
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` BIGINT(100) NOT NULL,
			  `day` int(2) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `size` int(10) NOT NULL,
			  `hits` int(10) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `cached` int(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `day` (`day`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){
				writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
				
		}

		if(!$this->FIELD_EXISTS("$tablename", "account")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");}
		return true;
		
	}
	
	function LOG_ADDED_CATZ($category_table,$rownumbers){
		//webfilters_bigcatzlogs
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
		if($rownumbers==0){return;}
		if(!is_numeric($rownumbers)){return ;}
		if($category_table==null){if(function_exists("WriteToSyslog")){WriteToSyslog("Fatal: No category Table set $called", basename(__FILE__));}}
		if($this->TABLE_EXISTS("webfilters_bigcatzlogs")){$this->CheckTables();}
		$categoryname=$this->tablename_tocat($category_table);
		if($categoryname==null){if(function_exists("WriteToSyslog")){WriteToSyslog("Warning: Unable to find category for $categoryname $called", basename(__FILE__));}}
		$sql="INSERT IGNORE INTO webfilters_bigcatzlogs (zDate,category_table,category,AddedItems) 
		VALUES (NOW(),'$category_table','$categoryname','$rownumbers')";
		$this->QUERY_SQL($sql);
		if(!$this->ok){if(function_exists("WriteToSyslogMail")){WriteToSyslogMail(__FUNCTION__."::$q->mysql_error", basename(__FILE__));}return;}
		//if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("$category_table $rownumbers new items", basename(__FILE__));}
		$ID=time();
		$sql="INSERT IGNORE INTO instant_updates (ID,zDate,CountItems) VALUES('$ID',NOW(),'$rownumbers')";
		$this->QUERY_SQL($sql);
		}
	
	
	function CreateVisitedDayTable($tablename=null){
		if($tablename==null){$tablename=date('Ymd')."_visited";}
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `size` int(10) NOT NULL,
			  `hits` int(10) NOT NULL,
			  PRIMARY KEY (`sitename`),
			  KEY `familysite` (`familysite`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
				
		}

		
		return true;
		
	}	
	
	function RepairTableBLock($tableblock){
		if($tableblock==null){return;}
		if(!$this->FIELD_EXISTS("$tableblock", "uri")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `uri` VARCHAR( 255 ) NOT NULL");}
		if(!$this->FIELD_EXISTS("$tableblock", "event")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `event` VARCHAR( 20 ) NOT NULL,ADD INDEX ( `event` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "why")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `why` VARCHAR( 90 ) NOT NULL,ADD INDEX ( `why` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "explain")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `explain` TEXT");}
		if(!$this->FIELD_EXISTS("$tableblock", "blocktype")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `blocktype` VARCHAR( 255 )");}
		if(!$this->FIELD_EXISTS("$tableblock", "hostname")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( `hostname` )");}
		if(!$this->FIELD_EXISTS("$tableblock", "account")){$this->QUERY_SQL("ALTER TABLE `$tableblock` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");}
				
		
	}

	

	
	
	
	function CreateMonthTable($tablename){
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` varchar(90) NOT NULL,
			  `sitename` varchar(128) NOT NULL,
			  `familysite` varchar(128) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			  `account` BIGINT(100) NOT NULL,
			  `day` int(2) NOT NULL,
			  `remote_ip` varchar(50) NOT NULL,
			  `country` varchar(50) NOT NULL,
			  `size` int(10) NOT NULL,
			  `hits` int(10) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `cached` int(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `sitename` (`sitename`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `country` (`country`),
			  KEY `day` (`day`),
			  KEY `category` (`category`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `familysite` (`familysite`),
			  KEY `cached` (`cached`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}			
		
		return true;
		
	}	
	
	function CreateMembersDayTable($tablename=null){
		if($tablename==null){$tablename=date("Ymd")."_members";}
		
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` varchar(90) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` BIGINT(100) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `hour` int(2) NOT NULL,
			  `size` int(10) NOT NULL,
			  `hits` int(10) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `cached` int(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `hour` (`hour`),
			  KEY `client` (`client`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `cached` (`cached`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}			
		return true;
		
	}	
	
	function CreateMembersMonthTable($tablename=null){
		if($tablename==null){$tablename=date("Ym")."_members";}
		
		if(!$this->TABLE_EXISTS("$tablename",$this->database)){	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zMD5` varchar(90) NOT NULL,
			  `client` varchar(50) NOT NULL,
			  `day` int(2) NOT NULL,
			  `size` int(10) NOT NULL,
			  `hits` int(10) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `MAC` varchar(20) NOT NULL,
			  `hostname` varchar(120) NOT NULL,
			 `account` BIGINT(100) NOT NULL,
			  `cached` int(1) NOT NULL,
			  PRIMARY KEY (`zMD5`),
			  KEY `day` (`day`),
			  KEY `client` (`client`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `uid` (`uid`),
			  KEY `MAC` (`MAC`),
			  KEY `hostname` (`hostname`),
			  KEY `account` (`account`),
			  KEY `cached` (`cached`)
			) ";
			$this->QUERY_SQL($sql,$this->database);
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}			
		return true;
		
	}

	function FixTables(){
		$array=$this->LIST_TABLES_QUERIES();
		while (list ($tablename, $line) = each ($array)){
			if(!$this->FIELD_EXISTS($tablename, "MAC")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `MAC` VARCHAR( 20 ) NOT NULL ,ADD INDEX ( MAC )");}
			if(!$this->FIELD_EXISTS($tablename, "hostname")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( hostname )");}
			if(!$this->FIELD_EXISTS($tablename, "account")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");}
		}
		
		$array=$this->LIST_TABLES_HOURS();
		while (list ($tablename, $line) = each ($array)){
			if(!$this->FIELD_EXISTS($tablename, "MAC")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `MAC` VARCHAR( 20 ) NOT NULL ,ADD INDEX ( MAC )");}
			if(!$this->FIELD_EXISTS($tablename, "hostname")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( hostname )");}
			if(!$this->FIELD_EXISTS($tablename, "account")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");}
		}		
		
		$array=$this->LIST_TABLES_MEMBERS();
		while (list ($tablename, $line) = each ($array)){
			if(!$this->FIELD_EXISTS($tablename, "MAC")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `MAC` VARCHAR( 20 ) NOT NULL ,ADD INDEX ( MAC )");}
			if(!$this->FIELD_EXISTS($tablename, "hostname")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `hostname` VARCHAR( 120 ) NOT NULL ,ADD INDEX ( hostname )");}
			if(!$this->FIELD_EXISTS($tablename, "account")){$this->QUERY_SQL("ALTER TABLE `$tablename` ADD `account` BIGINT(100) NOT NULL ,ADD INDEX ( `account` )");}
		}		
		
		
	}
	
	public function GET_THUMBNAIL($sitename,$width){
		$sitename=trim(strtolower($sitename));
		return "
		<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.statistics.php?thumbnail-zoom-js=$sitename');\">
		<img src='/squid.statistics.php?thumbnail=$sitename&width=$width'></a>";
	}
	
	
	public function WEEK_TITLE($weekNumber, $year){
		$tt=$this->getDaysInWeek($weekNumber,$year);
		foreach ($tt as $dayTime) {$f[]=date('{l} d {F}', $dayTime);}	
		return "{week}:&nbsp;&nbsp;{from}&nbsp;{$f[0]}&nbsp;{to}&nbsp;{$f[6]} $year";
	}
	
	public function WEEK_TITLE_FROM_TABLENAME($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			return $this->WEEK_TITLE($Cweek,$Cyear);
	}
	
	public function WEEK_HASHTIME_FROM_TABLENAME($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			$tt=$this->getDaysInWeek($Cweek,$Cyear);
			foreach ($tt as $dayTime) {$f[$dayTime]=date('{l} d {F}', $dayTime);}		
			return $f;
	}
	
	public function WEEK_TIME_FROM_TABLENAME($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			$tt=$this->getDaysInWeek($Cweek,$Cyear);
			foreach ($tt as $dayTime) {return $dayTime;}		
			
	}	
	
	public function MONTH_TITLE_FROM_TABLENAME($tablename){
		$Cyear=substr($tablename, 0,4);
		$month=substr($tablename,4,2);
		$month=str_replace("_", "", $month);
		$dayfull="01-$month-$Cyear 00:00:00";
		$date=strtotime($dayfull);
		return date("{F} Y",$date);
		
	}
	
	public function WEEK_TABLE_TO_MONTH($tablename){
			$Cyear=substr($tablename, 0,4);
			$Cweek=substr($tablename,4,2);
			$Cweek=str_replace("_", "", $Cweek);
			$tt=$this->getDaysInWeek($Cweek,$Cyear);
			foreach ($tt as $dayTime) {$f[intval(date("d", $dayTime))]=date("{l} d {F} Y", $dayTime);}
			return $f;
	}	

	public function DAY_TITLE_FROM_TABLENAME($tablename){
		$Cyear=substr($tablename, 0,4);
		$CMonth=substr($tablename,4,2);
		$CDay=substr($tablename,6,2);
		$CDay=str_replace("_", "", $CDay);
		$time=strtotime("$Cyear-$CMonth-$CDay");
		return date("{l} d {F} Y",$time);
	}
	
	public function DAY_TABLENAME_TO_TIME($tablename){
		$Cyear=substr($tablename, 0,4);
		$CMonth=substr($tablename,4,2);
		$CDay=substr($tablename,6,2);
		$CDay=str_replace("_", "", $CDay);
		$time=strtotime("$Cyear-$CMonth-$CDay");
		return $time;
	}	
		
		
	public function getDaysInWeek ($weekNumber, $year) {
	  $time = strtotime($year . '0104 +' . ($weekNumber - 1). ' weeks');
	  $mondayTime = strtotime('-' . (date('w', $time) - 1) . ' days',$time);
	 
	  $dayTimes = array ();
	  for ($i = 0; $i < 7; ++$i) {
	    $dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
	  }
	
	  return $dayTimes;
	}	
	
}
function writelogs_squid($text,$function,$file,$line=0,$category=null){
		ufdbguard_admin_events($text, $function, $file, $line, $category);
				
	}
	
function TITLE_SQUID_STATSTABLE($sql,$title,$TimeType="week"){
	$queryjs=base64_encode($sql);
	$titlejs=base64_encode($title);
	$title_style="font-size:15px;width:100%;font-weight:bold;text-decoration:underline;margin-bottom:10px";
	$mouse="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$span=md5("$sql");
	if(isset($_GET["day"])){$day=$_GET["day"];}
	if($day==null){if(isset($_GET["week"])){$day=$_GET["week"];}}
	$title0="<div style='$title_style;' $mouse OnClick=\"javascript:Loadjs('squid.statistics.querytable.php?query=$queryjs&title=$titlejs&span=$span&TimeType=$TimeType&day=$day')\">$title</div>
	<span id='$span'></span>
	";
	return $title0;
}

function squidstatsApplianceEvents($host,$text){
	$q=new mysql_squid_builder();
	$text=addslashes($text);
	if($GLOBALS["VERBOSE"]){echo "$host:: $text\n";}
	$sql="INSERT INTO stats_appliance_events(`hostname`,`events`) VALUES ('$host','$text')";
	$q->QUERY_SQL($sql);
	
}
