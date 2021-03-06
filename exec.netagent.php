<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["AS_NET_AGENT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.netagent.inc");

if($argv[1]=="--register-console"){registerconsole($argv[2],$argv[3],$argv[4]);die();}
if($argv[1]=="--register-infos"){registerconsole_infos();die();}
if($argv[1]=="--newhostid"){revokehostid();die();}
if($argv[1]=="--timeout"){timeout();die();}


communicate();


function registerconsole($ipaddr,$port,$ssl){
	$sock=new sockets();
	$unix=new unix();
	$sock->SET_INFO("EnableRemoteStatisticsAppliance", 1);
	$GLOBALS["OUTPUT"]=true;
	$php=$unix->LOCATE_PHP5_BIN();
	$RemoteStatisticsApplianceSettings["SSL"]=$ssl;
	$RemoteStatisticsApplianceSettings["PORT"]=$port;
	$RemoteStatisticsApplianceSettings["SERVER"]=$ipaddr;
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$RemoteStatisticsApplianceSettings_final=base64_encode(serialize($RemoteStatisticsApplianceSettings));
	$sock->SET_INFO("RemoteStatisticsApplianceSettings", $RemoteStatisticsApplianceSettings_final);
	shell_exec("$php ".dirname(__FILE__)."/exec.schedules.php");
	communicate();
}

function registerconsole_infos(){
	$sock=new sockets();
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$f[]="[INFO]";
	$f[]="server={$RemoteStatisticsApplianceSettings["SERVER"]}";
	$f[]="port={$RemoteStatisticsApplianceSettings["PORT"]}";
	$f[]="ssl={$RemoteStatisticsApplianceSettings["SSL"]}";
	@file_put_contents("/etc/artica-postfix/remote-appliance-settings.ini", @implode("\n", $f));
	
}

function timeout(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidfile)<5){die();}
	@unlink($pidfile);
	@file_put_contents($pidfile, time());
	communicate();
}

function revokehostid(){
	@unlink("/etc/artica-postfix/settings/Daemons/HOSTID");
	echo "Old hostid as been removed, create a new one...\n";
	$net=new netagent();
	$hostid=$net->hostid();
	echo "new hostid is : $hostid -> send status\n";
	$net->ping();	
}


function communicate(){
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$GLOBALS["CLASS_SOCKET"]=$sock;
	$GLOBALS["CLASS_UNIX"]=$unix;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Remote stat appliance, Already running pid $pid\n";}
		if($time<10){	
			WriteMyLogs("Warning: Already running pid $pid since {$time}mn",__FUNCTION__,__FILE__,__LINE__);
			return;
		}else{
			unix_system_kill_force($pid);
		}
	}	
	$GLOBALS["MYPID"]=getmypid();
	WriteMyLogs("New PID: ".getmypid());
	@file_put_contents($pidfile, getmypid());

	
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($EnableRemoteStatisticsAppliance==0){
		if($GLOBALS["VERBOSE"]){
			WriteMyLogs("EnableRemoteStatisticsAppliance: $EnableRemoteStatisticsAppliance = 0");
			echo "EnableRemoteStatisticsAppliance: $EnableRemoteStatisticsAppliance = 0\n";}
			return;
	}
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($pidTime);
		if($time<3){
			writelogs("Accepted only betwee, 3mn each...",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
			
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	if($GLOBALS["OUTPUT"]){echo "Ping the remote appliance...\n";}
	$net=new netagent();
	WriteMyLogs("->ping()...");
	$net->ping();
}

function WriteMyLogs($text){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	
	$logFile="/var/log/artica-netagent.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	
   	if(function_exists("debug_backtrace")){
   		$trace=debug_backtrace();
   		if(isset($trace[1])){
   			$sourcefile=basename($trace[1]["file"]);
   			$sourcefunction=$trace[1]["function"];
   			$sourceline=$trace[1]["line"];
   		}
   	
   	}   	
   	
   	$date=date('m-d H:i:s');
	$f = fopen("/var/log/artica-netagent.log", 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$sourcefunction::$sourceline] $text\n";}
	fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$sourcefunction::$sourceline] $text\n");
	fclose($f);
}