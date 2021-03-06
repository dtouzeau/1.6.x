<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["TITLENAME"]="Socks5 Proxy daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;$GLOBALS["OUTPUT"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ss5.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress("{stopping_service}",5);
	stop(true);
	sleep(1);
	build_progress("{building_settings}",45);
	buildconfig();
	build_progress("{starting_service}",50);
	if(!start(true)){
		build_progress("{starting_service} {failed}",110);
		return;
	}
	build_progress("{starting_service} {done}",100);
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("ss5");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableSS5=intval($sock->GET_INFO("EnableSS5"));
	
	

	if($EnableSS5==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSS5)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	while (list ($Interface, $ligne) = each ($TRA) ){$TR[]=$Interface; }
	
	$f[]="/var/run/ss5";
	$f[]="/var/log/ss5";
	
	while (list ($index, $directory) = each ($f) ){
		@mkdir($directory,0755,true);
		@chown($directory,"squid");
		@chgrp($directory,"squid");
		
	}
	build_progress("{starting_service}",60);
	
	$cmd="$Masterbin -s -t -u squid -p /var/run/ss5/ss5.pid >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		build_progress("{waiting} $i/5",65);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		build_progress("{success}",70);
		return true;
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	


	build_progress("{stopping_service}",10);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress("{stopping_service}",15);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		build_progress("{stopping_service}",45);
		return;
	}

	build_progress("{stopping_service}",35);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	build_progress("{stopping_service}",45);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
}

function PID_NUM(){
	
	$unix=new unix();
	$Masterbin=$unix->find_program("ss5");
	return $unix->PIDOF($Masterbin);
	
}

function buildconfig(){
	$f[]="#";
	$f[]="# SECTION       <VARIABLES AND FLAGS>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="#";
	$f[]="#  TAG: set";
	$f[]="#";
	$f[]="#       set option name:";
	$f[]="#";
	$f[]="#       SS5_DNSORDER    		->   order dns answer";
	$f[]="#       SS5_VERBOSE       		->   enable verbose output to be written into logfile";
	$f[]="#       SS5_DEBUG         		->   enable debug output to be written into logfile";
	$f[]="#       SS5_CONSOLE        		->   enable web console";
	$f[]="#       SS5_ATIMEOUT       		->   for future uses";
	$f[]="#       SS5_STIMEOUT       		->   set session idle timeout (default 1800 seconds,";
	$f[]="#                                                         0 for infinite)";
	$f[]="#       SS5_LDAP_TIMEOUT   		->   set ldap query timeout";
	$f[]="#       SS5_LDAP_BASE      		->   set BASE method for profiling (see PROFILING section)";
	$f[]="#                                    	     It is default option!";
	$f[]="#       SS5_LDAP_FILTER   		->   set FILTER method for profiling (see PROFILING";
	$f[]="#                                            section)";
	$f[]="#       SS5_SRV   	    		->   enable ss5srv admin tool";
	$f[]="#       SS5_PAM_AUTH       		->   set PAM authentication";
	$f[]="#       SS5_RADIUS_AUTH    		->   set RADIUS authentication";
	$f[]="#       SS5_RADIUS_INTERIM_INT       	->   set interval beetwen interim update packet";
	$f[]="#       SS5_RADIUS_INTERIM_TIMEOUT   	->   set interim response timeout ";
	$f[]="#       SS5_AUTHCACHEAGE   		->   set age in seconds for authentication cache";
	$f[]="#       SS5_AUTHOCACHEAGE  		->   set age in seconds for authorization cache";
	$f[]="#       SS5_STICKYAGE      		->   set age for affinity";
	$f[]="#       SS5_STICKYSESSION  		->   enable affinity session";
	$f[]="#       SS5_SUPAKEY        		->   set SUPA secret key (default SS5_SERVER_S_KEY)";
	$f[]="#       SS5_ICACHESERVER   		->   set internet address of ICP server";
	$f[]="#       SS5_GSS_PRINC      		->   set GSS service principal";
	$f[]="#       SS5_PROCESSLIFE    		->   set number of requests process must servs before ";
	$f[]="#                                    	     closing";
	$f[]="#       SS5_NETBIOS_DOMAIN 		->   enable netbios domain mapping with directory store, ";
	$f[]="#                                    	     during autorization process";
	$f[]="#       SS5_SYSLOG_FACILITY		->   set syslog facility";
	$f[]="#       SS5_SYSLOG_LEVEL		->   set syslog level";
	$f[]="#";
	$f[]="# ///////////////////////////////////////////////////////////////////////////////////";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION 	<AUTHENTICATION>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="#";
	$f[]="#  TAG: auth";
	$f[]="#";
	$f[]="# 	auth source host, source port, authentication type";
	$f[]="#";
	$f[]="# 	Some examples:";
	$f[]="#";
	$f[]="# 	Authentication from 10.253.8.0 network";
	$f[]="#   		auth 10.253.8.0/22 - u";
	$f[]="#";
	$f[]="# 	Fake authentication from 10.253.0.0 network. In this case, ss5 request ";
	$f[]="#	authentication but doesn't check for password. Use fake authentication ";
	$f[]="#	for logging or profiling purpose.";
	$f[]="#   		auth 10.253.0.0/16 - n";
	$f[]="#";
	$f[]="# 	Fake authentication: ss5 doesn't check for correct password but fetchs ";
	$f[]="#	username for profiling.";
	$f[]="#   		auth 0.0.0.0/0 - n";
	$f[]="#";
	$f[]="#  TAG: external_auth_program";
	$f[]="#";
	$f[]="# 	external_auth_program program name and path ";
	$f[]="#";
	$f[]="# 	Some examples:";
	$f[]="#";
	$f[]="# 	Use shell file to autheticate user via ldap query";
	$f[]="#   		external_auth_program /usr/local/bin/ldap.sh";
	$f[]="#";
	$f[]="#  TAG: RADIUS authentication could be used setting SS5_RADIUS_AUTH option and ";
	$f[]="#       configuring the following attributes:";
	$f[]="#";
	$f[]="#       radius_ip               (radius address)";
	$f[]="#       radius_bck_ip           (radius secondary address)";
	$f[]="#       radius_auth_port        (radius authentication port, DFAULT = 1812)";
	$f[]="#       radius_acct_port        (radius authorization  port, DFAULT = 1813)";
	$f[]="#       radius_secret           (secret password betw";
	$f[]="#";
	$f[]="#";
	$f[]="#";
	$f[]="# ///////////////////////////////////////////////////////////////////////////////////";
	$f[]="#       SHost           SPort           Authentication";
	$f[]="#";
	$f[]="#auth    0.0.0.0/0               -               -";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION 	<BANDWIDTH>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="#";
	$f[]="#  TAG: bandwidth";
	$f[]="#";
	$f[]="# 	bandwidth group, max number of connections, bandwidth, session timeout ";
	$f[]="#";
	$f[]="# 	Some examples:";
	$f[]="#";
	$f[]="# 	Limit connections to 2 for group Admin";
	$f[]="#   		bandwidth Admin 2 - -";
	$f[]="#";
	$f[]="# 	Limit bandwidth to 100k for group Users";
	$f[]="#   		bandwidth Users - 102400 -";
	$f[]="#";
	$f[]="#       note: if you enable bandwith profiling per user, SS5 use this value instead of";
	$f[]="#             value specified into permit directive.";
	$f[]="#";
	$f[]="# ///////////////////////////////////////////////////////////////////////////////////";
	$f[]="#                   Group          MaxCons     Bandwidth   Session timeout";
	$f[]="#       bandwidth   grp1           5           -           -";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION	<PROXIES>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="#";
	$f[]="#  TAG: proxy/noproxy";
	$f[]="#";
	$f[]="#	proxy/noproxy dst host/network, dst port, socks proxy address, port address, ver";
	$f[]="#";
	$f[]="#	Some examples:";
	$f[]="#";
	$f[]="#	Proxy request for 172.0.0.0 network to socks server 10.253.9.240 on port 1081: ";
	$f[]="#";
	$f[]="#   	if authentication is request, downstream socks server have to  check it; ";
	$f[]="#   	if resolution is request, downstream socks server does it before proxying ";
	$f[]="#	the request toward the upstream socks server.";
	$f[]="#   		proxy 172.0.0.0/16 - 10.253.9.240 1081";
	$f[]="#";
	$f[]="#       SS5 makes direct connection to 10.253.0.0 network (in this case, port value is not ";
	$f[]="#       verified) without using upstream proxy server";
	$f[]="#   		noproxy 0.0.0.0/0 - 10.253.0.0/16 1080 -";
	$f[]="#";
	$f[]="# ///////////////////////////////////////////////////////////////////////////////////";
	$f[]="#       	DHost/Net		DPort	DProxyip	DProxyPort SocksVer";
	$f[]="#";
	$f[]="#	proxy	0.0.0.0/0		-	1.1.1.1		-	   -";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION       <DUMP>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="#";
	$f[]="#  TAG: dump";
	$f[]="#";
	$f[]="#       dump host/network, port, s/d (s=source d=destination), dump mode (r=rx, t=tx, b=rx+tx)";
	$f[]="#";
	$f[]="#       Some examples:";
	$f[]="#";
	$f[]="#       Dump traffic for 172.30.1.0 network on port 1521:";
	$f[]="#";
	$f[]="#       if authentication is request, downstream socks server have to  check it;";
	$f[]="#       if resolution is request, downstream socks server does it before proxying";
	$f[]="#       the request toward the upstream socks server.";
	$f[]="#               dump 172.30.1.0/24 1521 d b";
	$f[]="#";
	$f[]="# ///////////////////////////////////////////////////////////////////////////////////";
	$f[]="#              DHost/Net               DPort   Dir 	Dump mode (r=rx,t=tx,b=rx+tx)";
	$f[]="#";
	$f[]="#       dump   0.0.0.0/0               -       d	t";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION	<ACCESS CONTROL>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="#";
	$f[]="#  TAG: permit/deny";
	$f[]="#	permit/deny src auth flag, host/network, src port, dst host/network, dst port, ";
	$f[]="#	fixup, group, bandwidth (from 256 bytes per second to 2147483647), expdate";
	$f[]="#";
	$f[]="#	Some examples:";
	$f[]="#";
	$f[]="# 	FTP Control + Passive Mode";
	$f[]="#		permit - 0.0.0.0/0 - 172.0.0.0/8 21 - - - -";
	$f[]="#";
	$f[]="#	FTP DATA Active Mode";
	$f[]="#		permit - 0.0.0.0/0 	- 172.0.0.0/8 	21 	- - - -";
	$f[]="#		permit - 172.0.0.0/8 	- 0.0.0.0/0 	- 	- - - -";
	$f[]="#";
	$f[]="#	Query DNS";
	$f[]="#		permit - 0.0.0.0/0 - 172.30.0.1/32 53 - - - -";
	$f[]="#";
	$f[]="#	Http + fixup";
	$f[]="#		permit - 0.0.0.0/0 - www.example.com 80 http - - -";
	$f[]="#";
	$f[]="#	Http + fixup + profile + bandwidth (bytes x second)";
	$f[]="#		permit - 0.0.0.0/0 - www.example.com 80 http admin 10240 -";
	$f[]="#";
	$f[]="#	Sftp + profile + bandwidth (bytes x second)";
	$f[]="#		permit - 0.0.0.0/0 - sftp.example.com 22 - developer 102400 -";
	$f[]="#";
	$f[]="#	Http + fixup ";
	$f[]="#		permit - 0.0.0.0/0 - web.example.com 80 - - - -";
	$f[]="#";
	$f[]="#	Http + fixup + user autentication required with expiration date to 31/12/2006";
	$f[]="#		permit u 0.0.0.0/0 - web.example.com 80 - - - 31-12-2006";
	$f[]="#";
	$f[]="#	Deny all connection to web.example.com";
	$f[]="#		deny - 0.0.0.0/0 - web.example.com - - - - -";
	$f[]="#";
	$f[]="#";
	$f[]="# /////////////////////////////////////////////////////////////////////////////////////////////////";
	$f[]="#      Auth	SHost		SPort	DHost		DPort	Fixup	Group	Band	ExpDate";
	$f[]="#";
	$f[]="#permit -	0.0.0.0/0	-	0.0.0.0/0	-	-	-	-	-	";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION	<PROFILING>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="# ";
	$f[]="#	1) File profiling:";
	$f[]="#";
	$f[]="#	ss5 look for a file name specified in permit line in the /etc/ss5 directory. ";
	$f[]="#	This file must contain user members. File profiling is the default option.";
	$f[]="#";
	$f[]="#	2) Ldap profiling:";
	$f[]="#";
	$f[]="#	ldap_profile_ip     	(directory internet address) ";
	$f[]="#	ldap_profile_port   	(directory port) ";
	$f[]="#	ldap_profile_base   	(ss5 replaces % with \"group specified in permit line\"";
	$f[]="#				if SS5LDAP_BASE if specified, otherwise if ";
	$f[]="#				SS5LDAP_FILTER is specified,  it uses base and search";
	$f[]="#				for group as attribute in user entry; see examples)";
	$f[]="#	ldap_profile_filter 	(ss5 uses filter for search operation)";
	$f[]="#	ldap_profile_dn     	(directory manager or another user authorized to ";
	$f[]="#				query the directory)";
	$f[]="#	ldap_profile_pass   	(\"dn\" password)";
	$f[]="#	ldap_netbios_domain	(If SS5_NETBIOS_DOMAIN option is set, ss5 map netbios ";
	$f[]="#                                domain user in authentication request with his configured ";
	$f[]="#                                directory sever. Otherwise no match is done and ";
	$f[]="#                                directory are contacted in order of configuration)";
	$f[]="#";
	$f[]="#	3) Mysql profiling:";
	$f[]="#";
	$f[]="#	mysql_profile_ip     	(mysql server internet address) ";
	$f[]="#	mysql_profile_db   	(mysql db )";
	$f[]="#	mysql_profile_user 	(mysql username )";
	$f[]="#	mysql_profile_pass 	(mysql password )";
	$f[]="#	mysql_profile_sqlstring	(sql base string for query. DEFAULT 'SELECT uname FROM grp WHERE gname like' )";
	$f[]="#";
	$f[]="#	Some examples:";
	$f[]="#";
	$f[]="#	Directory configuration for ldap profiling with SS5_LDAP_BASE option:";
	$f[]="#	in this case, ss5 look for attribute uid=\"username\" with base ou=\"group\",";
	$f[]="#	dc=example,dc=com where group is specified in permit line as ";
	$f[]="#	\"permit - - - - - group - -";
	$f[]="#";
	$f[]="#	Note: in this case, attribute value is not userd";
	$f[]="#";
	$f[]="#		ldap_profile_ip        10.10.10.1";
	$f[]="#		ldap_profile_port      389";
	$f[]="#		ldap_profile_base      ou=%,dc=example,dc=com";
	$f[]="#		ldap_profile_filter    uid";
	$f[]="#		ldap_profile_attribute gid";
	$f[]="#		ldap_profile_dn        cn=root,dc=example,dc=com";
	$f[]="#		ldap_profile_pass      secret";
	$f[]="#		ldap_netbios_domain    dir ";
	$f[]="#";
	$f[]="#	Directory configuration for ldap profiling with SS5_LDAP_FILTER option:";
	$f[]="#	in this case, ss5 look for attributes uid=\"username\" & \"gid=group\" with ";
	$f[]="#	base dc=example,dc=com where group is specified in permit line as ";
	$f[]="#	\"permit - - - - - group - -\"";
	$f[]="#";
	$f[]="#	Note: you can also use a base like \"ou=%,dc=example,dc=com\", where % ";
	$f[]="#	will be replace with \"group\".";
	$f[]="#";
	$f[]="#		ldap_profile_ip        10.10.10.1";
	$f[]="#		ldap_profile_port      389";
	$f[]="#		ldap_profile_base      ou=Users,dc=example,dc=com";
	$f[]="#		ldap_profile_filter    uid";
	$f[]="#		ldap_profile_attribute gecos";
	$f[]="#		ldap_profile_dn        cn=root,dc=example,dc=com";
	$f[]="#		ldap_profile_pass      secret";
	$f[]="#		ldap_domain_domain     dir ";
	$f[]="#";
	$f[]="#	Sample OpenLdap log:";
	$f[]="#	conn=304 op=0 BIND dn=\"cn=root,dc=example,dc=com\" mech=simple ssf=0";
	$f[]="#	conn=304 op=0 RESULT tag=97 err=0 text=";
	$f[]="#	conn=304 op=1 SRCH base=\"ou=Users,dc=example,dc=com\" scope=1 filter=\"(&(uid=usr1)(gecos=Users))\"";
	$f[]="#	conn=304 op=1 SRCH attr=gecos";
	$f[]="#";
	$f[]="# 	where ldap entry is:";
	$f[]="#	dn: uid=usr1,ou=Users,dc=example,dc=com";
	$f[]="#	uid: usr1";
	$f[]="#	cn: usr1";
	$f[]="#	objectClass: account";
	$f[]="#	objectClass: posixAccount";
	$f[]="#	objectClass: top";
	$f[]="#	userPassword:: dXNyMQ==";
	$f[]="#	loginShell: /bin/bash";
	$f[]="#	homeDirectory: /home/usr1";
	$f[]="#	uidNumber: 1";
	$f[]="#	gidNumber: 1";
	$f[]="#	gecos: Users";
	$f[]="";
	$f[]="#";
	$f[]="# SECTION	<SERVER BALANCE>";
	$f[]="# \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\";
	$f[]="# ";
	$f[]="#  TAG: virtual";
	$f[]="#";
	$f[]="#	virtual virtual identification (vid), real ip server";
	$f[]="#";
	$f[]="#	Some examples:";
	$f[]="#";
	$f[]="#	Two vip balancing on three real server each one";
	$f[]="#		virtual 1 172.30.1.1";
	$f[]="#		virtual 1 172.30.1.2";
	$f[]="#		virtual 1 172.30.1.3";
	$f[]="#";
	$f[]="#		virtual 2 172.30.1.6";
	$f[]="#		virtual 2 172.30.1.7";
	$f[]="#		virtual 2 172.30.1.8";
	$f[]="#";
	$f[]="# 	Note: Server balancing only works with -t option, (threaded mode) and ONLY ";
	$f[]="#	with \"connect\" operation.";
	$f[]="#";
	$f[]="# ///////////////////////////////////////////////////////////////////////////////////";
	$f[]="#      	Vid	Real ip";
	$f[]="#";
	$f[]="#vitual	-	-";
	$f[]="";	
	
	@file_put_contents("/etc/ss5.conf", @implode("\n", $f));
	@chown("/etc/ss5.conf","squid");
}


?>