<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NOLANG"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



$GLOBALS["ZARAFA_LOCALE"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ZARAFA_LANG"));
if($GLOBALS["ZARAFA_LOCALE"]==null){$GLOBALS["ZARAFA_LOCALE"]="en_US";}

if($argv[1]=="--lang"){
	$GLOBALS["NOLANG"]=true;
	ZarafaInit();
	shell_exec("/etc/init.d/zarafa-dagent restart");
	shell_exec("/etc/init.d/zarafa-gateway  restart");
	shell_exec("/etc/init.d/zarafa-ical restart");      
	shell_exec("/etc/init.d/zarafa-licensed restart");  
	shell_exec("/etc/init.d/zarafa-monitor restart");   
	shell_exec("/etc/init.d/zarafa-search restart");    
	shell_exec("/etc/init.d/zarafa-server restart");
	die();
}


ZarafaInit();

function zarafa_monitor_debian(){
$unix=new unix();
$servicebin=$unix->find_program("update-rc.d");
if(!is_file($servicebin)){return;}
$binary=$unix->find_program("zarafa-server");
if(!is_file($binary)){return;}
if(!$GLOBALS["NOLANG"]){
	$php5=$unix->LOCATE_PHP5_BIN();
	$localgen="$php5 /usr/share/artica-postfix/exec.locale.gen.php --force";
}




if(is_file("/etc/zarafa/searchscripts/attachments_parser")){@chmod("/etc/zarafa/searchscripts/attachments_parser", 0755);}

$f[]="#! /bin/sh";
$f[]="#";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides:          zarafa-monitor";
$f[]="# Required-Start:    \$syslog \$network \$remote_fs";
$f[]="# Required-Stop:     \$syslog \$network \$remote_fs";
$f[]="# Should-Start:      zarafa-server";
$f[]="# Should-Stop:       zarafa-server";
$f[]="# Default-Start:     2 3 4 5";
$f[]="# Default-Stop:      0 1 6";
$f[]="# Short-Description: Zarafa Collaboration Platform's Quota Monitor";
$f[]="# Description:       The Zarafa Quota Monitor watches the store sizes";
$f[]="#                    of users, and sends warning emails when limits are exceeded.";
$f[]="### END INIT INFO";
$f[]="";
$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
$f[]="MONITOR=/usr/bin/zarafa-monitor";
$f[]="DESC=\"Zarafa monitor\"";
$f[]="NAME=`basename \$MONITOR`";
$f[]="#QUIETDAEMON=--quiet";
$f[]="PIDFILE=/var/run/zarafa-monitor.pid";
$f[]="MONITOR_ENABLED=\"yes\"";
$f[]="";
$f[]="test -x \$MONITOR || exit 0";
$f[]="";
$f[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
$f[]="";
$f[]="if [ -e \"\$MONITOR_CONFIG\" ]; then";
$f[]="	MONITOR_OPTS=\"\$MONITOR_OPTS -c \$MONITOR_CONFIG\"";
$f[]="fi";
$f[]="";
$f[]="#set -e";
$f[]="";
$f[]=". /lib/lsb/init-functions";
$f[]="";
$f[]="case \"\$1\" in";
$f[]="  start)";
$f[]="	if [ \"\$MONITOR_ENABLED\" = \"no\" ]; then";
$f[]="		log_warning_msg \"Zarafa Quota Monitor daemon not enabled in /etc/default/zarafa ... not starting\"";
$f[]="		exit 0";
$f[]="	fi";
$f[]="	# Presence of local pointer file";
$f[]="	if [ ! -r /etc/artica-postfix/locales.gen ]";
$f[]="	then";
$f[]="		echo \"locales are not builded, build locales, this take a while\"";
$f[]="		$localgen";
$f[]="  else";
$f[]="		echo \"locales are correctly generated...\"";
$f[]="	fi";
$f[]="	log_begin_msg \"Starting \$DESC: \$NAME\"";
$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
$f[]="	export LANG=\$ZARAFA_LOCALE";
$f[]="	start-stop-daemon --start \$QUIETDAEMON --pidfile \$PIDFILE --exec \$MONITOR -- \$MONITOR_OPTS";
$f[]="	log_end_msg \$?";
$f[]="	unset LC_ALL LANG";
$f[]="	;;";
$f[]="  stop)";
$f[]="	log_begin_msg \"Stopping \$DESC: \$NAME\"";
$f[]="	start-stop-daemon --stop \$QUIETDAEMON --pidfile \$PIDFILE --retry TERM/15/KILL --exec \$MONITOR";
$f[]="	RETVAL=\$?";
$f[]="	rm -f \$PIDFILE";
$f[]="	log_end_msg \$RETVAL";
$f[]="	;;";
$f[]="  restart)";
$f[]="	\$0 stop";
$f[]="	\$0 start";
$f[]="	;;";
$f[]="  status)";
$f[]="	status_of_proc \"\$MONITOR\" \"\$NAME\" && exit 0 || exit \$?";
$f[]="	;;";
$f[]="  reload|force-reload)";
$f[]="	log_begin_msg \"Reloading \$DESC: \$NAME\"";
$f[]="	start-stop-daemon --stop \$QUIETDAEMON --signal HUP --pidfile \$PIDFILE --exec \$MONITOR";
$f[]="	log_end_msg \$?";
$f[]="	;;";
$f[]="  *)";
$f[]="	N=/etc/init.d/\$NAME";
$f[]="	echo \"Usage: \$N {start|stop|restart|reload|force-reload|status}\" >&2";
$f[]="	exit 1";
$f[]="	;;";
$f[]="esac";
$f[]="";
$f[]="exit 0";
@file_put_contents("/etc/init.d/zarafa-monitor", @implode("\n", $f));
@chmod("/etc/init.d/zarafa-monitor", 0755);
shell_exec("$servicebin -f zarafa-monitor defaults >/dev/null 2>&1");
echo "Zarafa monitor init.d debian mode done\n";

$f=array();
$f[]="#! /bin/sh";
$f[]="#";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides:          zarafa-gateway";
$f[]="# Required-Start:    \$syslog \$network \$remote_fs";
$f[]="# Required-Stop:     \$syslog \$network \$remote_fs";
$f[]="# Should-Start:      zarafa-server";
$f[]="# Should-Stop:       zarafa-server";
$f[]="# Default-Start:     2 3 4 5";
$f[]="# Default-Stop:      0 1 6";
$f[]="# Short-Description: Zarafa Collaboration Platform's POP3/IMAP Gateway";
$f[]="# Description:       The Zarafa Gateway allows users";
$f[]="#                    to access their email using the POP3 or IMAP protocol.";
$f[]="### END INIT INFO";
$f[]="";
$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
$f[]="GATEWAY=/usr/bin/zarafa-gateway";
$f[]="DESC=\"Zarafa gateway\"";
$f[]="NAME=`basename \$GATEWAY`";
$f[]="#QUIETDAEMON=--quiet";
$f[]="PIDFILE=/var/run/zarafa-gateway.pid";
$f[]="GATEWAY_ENABLED=\"yes\"";
$f[]="";
$f[]="test -x \$GATEWAY || exit 0";
$f[]="";
$f[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
$f[]="";
$f[]="if [ -e \"\$GATEWAY_CONFIG\" ]; then";
$f[]="	GATEWAY_OPTS=\"\$GATEWAY_OPTS -c \$GATEWAY_CONFIG\"";
$f[]="fi";
$f[]="";
$f[]="#set -e";
$f[]="";
$f[]=". /lib/lsb/init-functions";
$f[]="";
$f[]="case \"\$1\" in";
$f[]="  start)";
$f[]="	if [ \"\$GATEWAY_ENABLED\" = \"no\" ]; then";
$f[]="		log_warning_msg \"Zarafa POP3/IMAP Gateway daemon not enabled in /etc/default/zarafa ... not starting\"";
$f[]="		exit 0";
$f[]="	fi";
$f[]="	log_begin_msg \"Starting \$DESC: \$NAME\"";
$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
$f[]="	export LANG=\$ZARAFA_LOCALE";
$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
$f[]="	start-stop-daemon --start \$QUIETDAEMON --pidfile \$PIDFILE --exec \$GATEWAY -- \$GATEWAY_OPTS";
$f[]="	log_end_msg \$?";
$f[]="	unset LC_ALL LANG";
$f[]="	;;";
$f[]="  stop)";
$f[]="	log_begin_msg \"Stopping \$DESC: \$NAME\"";
$f[]="	start-stop-daemon --stop \$QUIETDAEMON --pidfile \$PIDFILE --retry TERM/15/KILL --exec \$GATEWAY";
$f[]="	RETVAL=\$?";
$f[]="	rm -f \$PIDFILE";
$f[]="	log_end_msg \$RETVAL";
$f[]="	$php5 /usr/share/artica-postfix/exec.zarafa-gateway.php --stop";
$f[]="	;;";
$f[]="  restart)";
$f[]="	\$0 stop";
$f[]="	\$0 start";
$f[]="	;;";
$f[]="  status)";
$f[]="	status_of_proc \"\$GATEWAY\" \"\$NAME\" && exit 0 || exit \$?";
$f[]="	;;";
$f[]="  reload|force-reload)";
$f[]="	log_begin_msg \"Reloading \$DESC: \$NAME\"";
$f[]="	start-stop-daemon --stop \$QUIETDAEMON --signal HUP --pidfile \$PIDFILE --exec \$GATEWAY";
$f[]="	log_end_msg \$?";
$f[]="	;;";
$f[]="  *)";
$f[]="	N=/etc/init.d/\$NAME";
$f[]="	echo \"Usage: \$N {start|stop|restart|force|force-reload|status}\" >&2";
$f[]="	exit 1";
$f[]="	;;";
$f[]="esac";
$f[]="";
$f[]="exit 0";

@file_put_contents("/etc/init.d/zarafa-gateway", @implode("\n", $f));
@chmod("/etc/init.d/zarafa-gateway", 0755);
shell_exec("$servicebin -f zarafa-gateway defaults >/dev/null 2>&1");
echo "Zarafa Gateway init.d debian mode done\n";


}

function zarafa_monitor_redhat(){
$unix=new unix();
$redhatbin=$unix->find_program("chkconfig");	
$binary=$unix->find_program("zarafa-server");
if(!is_file($binary)){return;}
if(!is_file($redhatbin)){return;}
$php5=$unix->LOCATE_PHP5_BIN();
$localgen="$php5 /usr/share/artica-postfix/exec.locale.gen.php --force";
$f[]="#!/bin/bash";
$f[]="#";
$f[]="# zarafa-monitor Zarafa Collaboration Platform's Quota Monitor";
$f[]="#";
$f[]="# chkconfig: 345 86 24";
$f[]="# description: The Zarafa Quota Monitor watches the store sizes of users, and sends warning emails when limits are exceeded.";
$f[]="# processname: /usr/bin/zarafa-monitor";
$f[]="# config: /etc/zarafa/monitor.cfg";
$f[]="# pidfile: /var/run/zarafa-monitor.pid";
$f[]="";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides: zarafa-monitor";
$f[]="# Required-Start: \$local_fs \$network \$remote_fs \$syslog";
$f[]="# Required-Stop: \$local_fs \$network \$remote_fs \$syslog";
$f[]="# Should-Start: zarafa-server";
$f[]="# Should-Stop: zarafa-server";
$f[]="# Short-Description: Zarafa Collaboration Platform's Quota Monitor";
$f[]="# Description: The Zarafa Quota Monitor watches the store sizes";
$f[]="#              of users, and sends warning emails when limits are exceeded.";
$f[]="### END INIT INFO";
$f[]="";
$f[]="MONITORCONFIG=/etc/zarafa/monitor.cfg";
$f[]="MONITORPROGRAM=/usr/bin/zarafa-monitor";
$f[]="";
$f[]="# Sanity checks.";
$f[]="[ -x \$MONITORPROGRAM ] || exit 0";
$f[]="";
$f[]="MONITORCONFIG_OPT=\"\"";
$f[]="[ ! -z \$MONITORCONFIG -a -f \$MONITORCONFIG ] && MONITORCONFIG_OPT=\"-c \$MONITORCONFIG\"";
$f[]="";
$f[]="";
$f[]="# Source function library.";
$f[]=". /etc/rc.d/init.d/functions";
$f[]="";
$f[]="RETVAL=0";
$f[]="monitor=`basename \$MONITORPROGRAM`";
$f[]="lockfile=/var/lock/subsys/\$monitor";
$f[]="pidfile=/var/run/\$monitor.pid";
$f[]="";
$f[]="start() {";
$f[]="	# Start in background, always succeeds";
$f[]="	# Presence of local pointer file";
$f[]="	if [ ! -r /etc/artica-postfix/locales.gen ]";
$f[]="	then";
$f[]="		echo \"locales are not builded, build locales, this take a while\"";
$f[]="		$localgen";
$f[]="  else";
$f[]="		echo \"locales are correctly generated...\"";
$f[]="	fi";
$f[]="	echo -n \$\"Starting \$monitor: \"";
$f[]="	ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
$f[]="	export LANG=\$ZARAFA_LOCALE";
$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
$f[]="	daemon \$MONITORPROGRAM \$MONITORCONFIG_OPT";
$f[]="	RETVAL=\$?";
$f[]="	unset LC_ALL LANG";
$f[]="	echo";
$f[]="	[ \$RETVAL -eq 0 ] && touch \$lockfile";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="stop() {";
$f[]="	echo -n \$\"Stopping \$monitor: \"";
$f[]="	killproc \$MONITORPROGRAM";
$f[]="	RETVAL=\$?";
$f[]="	echo";
$f[]="	[ \$RETVAL -eq 0 ] && rm -f \$lockfile \$pidfile";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="restart() {";
$f[]="	stop";
$f[]="	start";
$f[]="}";
$f[]="";
$f[]="reload() {";
$f[]="	echo -n \$\"Restarting \$monitor: \"";
$f[]="	killproc \$MONITORPROGRAM -SIGHUP";
$f[]="	RETVAL=\$?";
$f[]="	echo";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="# See how we were called.";
$f[]="case \"\$1\" in";
$f[]="    start)";
$f[]="		start";
$f[]="		;;";
$f[]="    stop)";
$f[]="		stop";
$f[]="		;;";
$f[]="    status)";
$f[]="		status \$monitor";
$f[]="		RETVAL=\$?";
$f[]="		;;";
$f[]="    restart|force-reload)";
$f[]="		restart";
$f[]="		;;";
$f[]="    condrestart|try-restart)";
$f[]="		if [ -f \${pidfile} ]; then";
$f[]="			stop";
$f[]="			start";
$f[]="		fi";
$f[]="		;;";
$f[]="    reload)";
$f[]="		reload";
$f[]="		;;";
$f[]="    *)";
$f[]="		echo \$\"Usage: \$monitor {start|stop|status|reload|restart|condrestart|force-reload|try-restart}\"";
$f[]="		RETVAL=1";
$f[]="		;;";
$f[]="esac";
$f[]="";
$f[]="exit \$RETVAL";
@file_put_contents("/etc/init.d/zarafa-monitor", @implode("\n", $f));
@chmod("/etc/init.d/zarafa-monitor", 0755);
if(is_file($redhatbin)){shell_exec("$redhatbin --add zarafa-monitor >/dev/null 2>&1");
shell_exec("$redhatbin --level 2345 zarafa-monitor on >/dev/null 2>&1");}	
echo "Zarafa monitor init.d RedHat mode done\n";	



$f=array();
$f[]="#!/bin/bash";
$f[]="#";
$f[]="# zarafa-gateway Zarafa Collaboration Platform's POP3/IMAP Gateway";
$f[]="#";
$f[]="# chkconfig: 345 86 24";
$f[]="# description: The Zarafa Gateway allows users to access their email using the POP3 or IMAP protocol.";
$f[]="# processname: /usr/bin/zarafa-gateway";
$f[]="# config: /etc/zarafa/gateway.cfg";
$f[]="# pidfile: /var/run/zarafa-gateway.pid";
$f[]="";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides: zarafa-gateway";
$f[]="# Required-Start: \$local_fs \$network \$remote_fs \$syslog";
$f[]="# Required-Stop: \$local_fs \$network \$remote_fs \$syslog";
$f[]="# Should-Start: zarafa-server";
$f[]="# Should-Stop: zarafa-server";
$f[]="# Short-Description: Zarafa Collaboration Platform's POP3/IMAP Gateway";
$f[]="# Description: The Zarafa Gateway allows users";
$f[]="#              to access their email using the POP3 or IMAP protocol.";
$f[]="### END INIT INFO";
$f[]="";
$f[]="GATEWAYCONFIG=/etc/zarafa/gateway.cfg";
$f[]="GATEWAYPROGRAM=/usr/bin/zarafa-gateway";
$f[]="";
$f[]="# Sanity checks.";
$f[]="[ -x \$GATEWAYPROGRAM ] || exit 0";
$f[]="";
$f[]="GATEWAYCONFIG_OPT=\"\"";
$f[]="[ ! -z \$GATEWAYCONFIG -a -f \$GATEWAYCONFIG ] && GATEWAYCONFIG_OPT=\"-c \$GATEWAYCONFIG\"";
$f[]="";
$f[]="[ -f /etc/sysconfig/zarafa ] && . /etc/sysconfig/zarafa";
$f[]="if [ -z \"\$ZARAFA_LOCALE\" ]; then";
$f[]="	ZARAFA_LOCALE=\"C\"";
$f[]="fi";
$f[]="";
$f[]="# Source function library.";
$f[]=". /etc/rc.d/init.d/functions";
$f[]="";
$f[]="RETVAL=0";
$f[]="gateway=`basename \$GATEWAYPROGRAM`";
$f[]="lockfile=/var/lock/subsys/\$gateway";
$f[]="pidfile=/var/run/\$gateway.pid";
$f[]="";
$f[]="start() {";
$f[]="	# Start in background, always succeeds";
$f[]="	echo -n \$\"Starting \$gateway: \"";
$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
$f[]="	export LANG=\$ZARAFA_LOCALE";
$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
$f[]="	daemon \$GATEWAYPROGRAM \$GATEWAYCONFIG_OPT";
$f[]="	RETVAL=\$?";
$f[]="	unset LC_ALL LANG";
$f[]="	echo";
$f[]="	[ \$RETVAL -eq 0 ] && touch \$lockfile";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="stop() {";
$f[]="	echo -n \$\"Stopping \$gateway: \"";
$f[]="	killproc \$GATEWAYPROGRAM";
$f[]="	RETVAL=\$?";
$f[]="	echo";
$f[]="	[ \$RETVAL -eq 0 ] && rm -f \$lockfile \$pidfile";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="restart() {";
$f[]="	stop";
$f[]="	start";
$f[]="}";
$f[]="";
$f[]="reload() {";
$f[]="	echo -n \$\"Restarting \$gateway: \"";
$f[]="	killproc \$GATEWAYPROGRAM -SIGHUP";
$f[]="	RETVAL=\$?";
$f[]="	echo";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="# See how we were called.";
$f[]="case \"\$1\" in";
$f[]="    start)";
$f[]="		start";
$f[]="		;;";
$f[]="    stop)";
$f[]="		stop";
$f[]="		;;";
$f[]="    status)";
$f[]="		status \$gateway";
$f[]="		RETVAL=\$?";
$f[]="		;;";
$f[]="    restart|force-reload)";
$f[]="		restart";
$f[]="		;;";
$f[]="    condrestart|try-restart)";
$f[]="		if [ -f \${pidfile} ]; then";
$f[]="			stop";
$f[]="			start";
$f[]="		fi";
$f[]="		;;";
$f[]="    reload)";
$f[]="		reload";
$f[]="		;;";
$f[]="    *)";
$f[]="		echo \$\"Usage: \$gateway {start|stop|status|reload|restart|condrestart|force-reload|try-restart}\"";
$f[]="		RETVAL=1";
$f[]="		;;";
$f[]="esac";
$f[]="";
$f[]="exit \$RETVAL";
@file_put_contents("/etc/init.d/zarafa-gateway", @implode("\n", $f));
@chmod("/etc/init.d/zarafa-gateway", 0755);
if(is_file($redhatbin)){shell_exec("$redhatbin --add zarafa-gateway >/dev/null 2>&1");
shell_exec("$redhatbin --level 2345 zarafa-gateway on >/dev/null 2>&1");}
echo "Zarafa Gateway init.d RedHat mode done\n";


}

function ZarafaInit(){
	
	$unix=new unix();
	$redhatbin=$unix->find_program("chkconfig");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	if(is_file($redhatbin)){
		echo "Zarafa updating RedHat mode\n";
		zarafa_monitor_redhat();
		zarafa_dagent_redhat();
	}
	$servicebin=$unix->find_program("update-rc.d");
	if(is_file($servicebin)){
		echo "Zarafa updating Debian mode\n";
		zarafa_monitor_debian();
		zarafa_dagent_debian();
	
	}
	
	ZarafaSearch();
	zarafa_server_all();
	zarafa_web();
	shell_exec("$php5 /usr/share/artica-postfix/exec.monit.php --build >/dev/null 2>&1");
}
function ZarafaSearch(){
	
	$unix=new unix();
	
	
	$zarafa_search=$unix->find_program("zarafa-search");
	if(!is_file($zarafa_search)){return;}
	$sock=new sockets();
	
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	
		$f[]="#! /bin/sh";
		$f[]="#";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          zarafa-search";
		$f[]="# Required-Start:    \$syslog \$network \$remote_fs";
		$f[]="# Required-Stop:     \$syslog \$network \$remote_fs";
		$f[]="# Should-Start:      zarafa-server";
		$f[]="# Should-Stop:       zarafa-server";
		$f[]="# Default-Start:     2 3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: The Search Indexer of the Zarafa Collaboration Platform";
		$f[]="# Description:       The Zarafa Search is the indexer daemon for full-text ";
		$f[]="#                    search through all objects (including attachments)";
		$f[]="### END INIT INFO";
		$f[]="";
		$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
		$f[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
		$f[]="";
		$f[]="case \"\$1\" in";
		$f[]="  start)";
		$f[]=" 	mkdir -p /var/lib/zarafa/index";
		$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
		$f[]="	export LANG=\$ZARAFA_LOCALE";
		$f[]="   $php ".dirname(__FILE__)."/exec.zarafa-search.php --start \$2 \$3";
		$f[]="	unset LC_ALL LANG";
		$f[]="  exit 0";
		$f[]="	;;";
		$f[]="  stop)";
		$f[]="   $php ".dirname(__FILE__)."/exec.zarafa-search.php --stop \$2 \$3";
		$f[]="  exit 0";		
		$f[]="	;;";
		$f[]="  restart)";
		$f[]="   $php ".dirname(__FILE__)."/exec.zarafa-search.php --restart \$2 \$3";
		$f[]="	;;";
		$f[]="  clean)";
		$f[]="   $php ".dirname(__FILE__)."/exec.zarafa-search.php --clean \$2 \$3";
		$f[]="	;;";
		$f[]="  reload|force-reload)";
		$f[]="   $php ".dirname(__FILE__)."/exec.zarafa-search.php --reload \$2 \$3";
		$f[]="	;;";
		$f[]="  *)";
		$f[]="	N=/etc/init.d/\$NAME";
		$f[]="	echo \"Usage: \$N {start|stop|restart|reload|force-reload|clean}\" >&2";
		$f[]="	exit 1";
		$f[]="	;;";
		$f[]="esac";
		$f[]="";
		$f[]="exit 0";	
		

		
		$INITD_PATH="/etc/init.d/zarafa-search";
		echo "Zarafa: [INFO] Writing $INITD_PATH with new config\n";
		@unlink($INITD_PATH);
		@file_put_contents($INITD_PATH, @implode("\n", $f));
		@chmod($INITD_PATH,0755);
		
		if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
		}
		
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
			shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
		}		
		
}



function zarafa_dagent_debian(){
	$sock=new sockets();
	$unix=new unix();
	$servicebin=$unix->find_program("update-rc.d");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$f[]="#! /bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          zarafa-dagent";
	$f[]="# Required-Start:    \$syslog \$network \$remote_fs";
	$f[]="# Required-Stop:     \$syslog \$network \$remote_fs";
	$f[]="# Should-Start:      zarafa-server";
	$f[]="# Should-Stop:       zarafa-server";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Zarafa Collaboration Platform's Delivery Agent";
	$f[]="# Description:       The Zarafa Delivery Agent in LMTP mode can be used to";
	$f[]="#                    run the zarafa-dagent as a daemon. The Zarafa Delivery";
	$f[]="#                    Agent can also be used as a standalone program.";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="DAGENT=/usr/bin/zarafa-dagent";
	$f[]="DESC=\"Zarafa LMTP dagent\"";
	$f[]="NAME=`basename \$DAGENT`";
	$f[]="#QUIETDAEMON=--quiet";
	$f[]="PIDFILE=/var/run/\$NAME.pid";
	$f[]="DAGENT_ENABLED=\"yes\"";
	$f[]="DAGENT_CONFIG=\"/etc/zarafa/dagent.cfg\"";
	$f[]="test -x \$DAGENT || exit 0";
	$f[]="DAGENT_OPTS=\"-d\"";
	$f[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
	$f[]="DAGENT_OPTS=\"\$DAGENT_OPTS -c \$DAGENT_CONFIG\"";
	$f[]="";
	$f[]="#set -e";
	$f[]="";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	log_begin_msg \"Starting \$DESC: \$NAME\"";
	$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
	$f[]="	export LANG=\$ZARAFA_LOCALE";
	$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
	$f[]="	start-stop-daemon --start \$QUIETDAEMON --pidfile \$PIDFILE --exec \$DAGENT -- \$DAGENT_OPTS";
	$f[]="	log_end_msg \$?";
	$f[]="	unset LC_ALL LANG";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="	log_begin_msg \"Stopping \$DESC: \$NAME\"";
	$f[]="	start-stop-daemon --stop \$QUIETDAEMON --pidfile \$PIDFILE --retry TERM/15/KILL --exec \$DAGENT";
	$f[]="	$php5 ".dirname(__FILE__)."/exec.zarafa-dagent.php --stop";
	$f[]="	RETVAL=\$?";
	$f[]="	rm -f \$PIDFILE";
	$f[]="	log_end_msg \$RETVAL";
	$f[]="	;;";
	$f[]="  restart)";
	$f[]="	\$0 stop";
	$f[]="	\$0 start";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="	status_of_proc \"\$DAGENT\" \"\$NAME\" && exit 0 || exit \$?";
	$f[]="	;;";
	$f[]="  reload|force-reload)";
	$f[]="	log_begin_msg \"Reloading \$DESC: \$NAME\"";
	$f[]="	start-stop-daemon --stop \$QUIETDAEMON --signal HUP --pidfile \$PIDFILE --exec \$DAGENT";
	$f[]="	log_end_msg \$?";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="	N=/etc/init.d/\$NAME";
	$f[]="	echo \"Usage: \$N {start|stop|restart|reload|force-reload|status}\" >&2";
	$f[]="	exit 1";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0";
	
	@file_put_contents("/etc/init.d/zarafa-dagent", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-dagent", 0755);
	shell_exec("$servicebin -f zarafa-dagent defaults >/dev/null 2>&1");
	echo "Zarafa agent init.d debian mode done\n";		
}

function zarafa_dagent_redhat(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$servicebin=$unix->find_program("chkconfig");
$f[]="#!/bin/bash";
$f[]="#";
$f[]="# zarafa-dagent Zarafa Collaboration Platform's Delivery Agent";
$f[]="#";
$f[]="# chkconfig: - 86 24";
$f[]="# description: The Zarafa Delivery Agent in LMTP mode can be used to \ ";
$f[]="#              run the zarafa-dagent as a daemon. The Zarafa Delivery \ ";
$f[]="#              Agent can also be used as a standalone program.";
$f[]="# processname: /usr/bin/zarafa-dagent";
$f[]="# config: /etc/zarafa/dagent.cfg";
$f[]="# pidfile: /var/run/zarafa-dagent.pid";
$f[]="";
$f[]="### BEGIN INIT INFO";
$f[]="# Provides: zarafa-dagent";
$f[]="# Required-Start: \$local_fs \$network \$remote_fs \$syslog";
$f[]="# Required-Stop: \$local_fs \$network \$remote_fs \$syslog";
$f[]="# Should-Start: zarafa-server";
$f[]="# Should-Stop: zarafa-server";
$f[]="# Short-Description: Zarafa Collaboration Platform's Delivery Agent";
$f[]="# Description:       The Zarafa Delivery Agent in LMTP mode can be used to";
$f[]="#                    run the zarafa-dagent as a daemon. The Zarafa Delivery";
$f[]="#                    Agent can also be used as a standalone program.";
$f[]="### END INIT INFO";
$f[]="";
$f[]="DAGENTCONFIG=/etc/zarafa/dagent.cfg";
$f[]="DAGENTPROGRAM=/usr/bin/zarafa-dagent";
$f[]="";
$f[]="# Sanity checks.";
$f[]="[ -x \$DAGENTPROGRAM ] || exit 0";
$f[]="";
$f[]="# the -d option is required to run LMTP mode deamonized";
$f[]="DAGENTCONFIG_OPT=\"-d\"";
$f[]="[ ! -z \$DAGENTCONFIG -a -f \$DAGENTCONFIG ] && DAGENTCONFIG_OPT=\"\$DAGENTCONFIG_OPT -c \$DAGENTCONFIG\"";
$f[]="";
$f[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
$f[]="";
$f[]="# Source function library.";
$f[]=". /etc/rc.d/init.d/functions";
$f[]="";
$f[]="RETVAL=0";
$f[]="dagent=`basename \$DAGENTPROGRAM`";
$f[]="lockfile=/var/lock/subsys/\$dagent";
$f[]="pidfile=/var/run/\$dagent.pid";
$f[]="";
$f[]="start() {";
$f[]="	# Start in background, always succeeds";
$f[]="	echo -n \$\"Starting \$dagent: \"";
$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
$f[]="	export LANG=\$ZARAFA_LOCALE";
$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
$f[]="	daemon \$DAGENTPROGRAM \$DAGENTCONFIG_OPT";
$f[]="	RETVAL=\$?";
$f[]="	unset LC_ALL LANG";
$f[]="	echo";
$f[]="	[ \$RETVAL -eq 0 ] && touch \$lockfile";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="stop() {";
$f[]="	echo -n \$\"Stopping \$dagent: \"";
$f[]="	$php5 ".dirname(__FILE__)."/exec.zarafa-dagent.php --stop";
$f[]="	killproc \$DAGENTPROGRAM";
$f[]="	RETVAL=\$?";
$f[]="	echo";
$f[]="	[ \$RETVAL -eq 0 ] && rm -f \$lockfile \$pidfile";

$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="restart() {";
$f[]="	stop";
$f[]="	start";
$f[]="}";
$f[]="";
$f[]="reload() {";
$f[]="	echo -n \$\"Restarting \$dagent: \"";
$f[]="	killproc \$DAGENTPROGRAM -SIGHUP";
$f[]="	RETVAL=\$?";
$f[]="	echo";
$f[]="";
$f[]="	return \$RETVAL";
$f[]="}";
$f[]="";
$f[]="# See how we were called.";
$f[]="case \"\$1\" in";
$f[]="    start)";
$f[]="		start";
$f[]="		;;";
$f[]="    stop)";
$f[]="		stop";
$f[]="		;;";
$f[]="    status)";
$f[]="		status \$dagent";
$f[]="		RETVAL=\$?";
$f[]="		;;";
$f[]="    restart|force-reload)";
$f[]="		restart";
$f[]="		;;";
$f[]="    condrestart|try-restart)";
$f[]="		if [ -f \${pidfile} ]; then";
$f[]="			stop";
$f[]="			start";
$f[]="		fi";
$f[]="		;;";
$f[]="    reload)";
$f[]="		reload";
$f[]="		;;";
$f[]="    *)";
$f[]="		echo \$\"Usage: \$dagent {start|stop|status|reload|restart|condrestart|force-reload|try-restart}\"";
$f[]="		RETVAL=1";
$f[]="		;;";
$f[]="esac";
$f[]="";
$f[]="exit \$RETVAL";	
	@file_put_contents("/etc/init.d/zarafa-dagent", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-dagent", 0755);
	if(is_file($servicebin)){shell_exec("$servicebin --add zarafa-dagent >/dev/null 2>&1");
	shell_exec("$servicebin --level 2345 zarafa-dagent on >/dev/null 2>&1");}	
	echo "Zarafa dagent init.d RedHat mode done\n";	
	
}

function zarafa_server_options(){
	$sock=new sockets();
	$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
	if(!is_numeric($ZarafaStoreOutside)){$ZarafaStoreOutside=0;}
	$options[]="-c /etc/zarafa/server.cfg";
	if($ZarafaStoreOutside==1){$options[]="--ignore-attachment-storage-conflict";}
	if(is_file("/etc/artica-postfix/zarafa-ignore-database-version-conflict")){$options[]="--ignore-database-version-conflict";}
	
	return @implode(" ", $options);	
}

function zarafa_server_redhat(){	
	$unix=new unix();
	$sock=new sockets();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$binary=$unix->find_program("zarafa-server");
	$servicebin=$unix->find_program("chkconfig");
	if(!is_file($binary)){return;}	
	if(!is_file($servicebin)){return;}
	$options=zarafa_server_options();	
	
	$f[]="#!/bin/bash";
	$f[]="#";
	$f[]="# zarafa-server Zarafa Collaboration Platform's Storage Server";
	$f[]="#";
	$f[]="# chkconfig: 345 85 25";
	$f[]="# description: The Zarafa Server takes MAPI calls in SOAP over HTTP(S) or \ ";
	$f[]="#              the unix socket. It authenticates users using one of three \ ";
	$f[]="#              authentication backends (unix/pam, db, ldap). It stores the data \ ";
	$f[]="#              in a MySQL instance, and optionally stores the attachments directly \ ";
	$f[]="#              on the filesystem.";
	$f[]="# processname: /usr/bin/zarafa-server";
	$f[]="# config: /etc/zarafa/server.cfg";
	$f[]="# pidfile: /var/run/zarafa-server.pid";
	$f[]="";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides: zarafa-server";
	$f[]="# Required-Start: \$local_fs \$network \$remote_fs \$syslog";
	$f[]="# Required-Stop: \$local_fs \$network \$remote_fs \$syslog";
	$f[]="# Should-Start: mysqld";
	$f[]="# Should-Stop: mysqld";
	$f[]="# Short-Description: Zarafa Collaboration Platform's Storage Server";
	$f[]="# Description: The Zarafa Server takes MAPI calls in SOAP over HTTP(S) or";
	$f[]="#              the unix socket. It authenticates users using one of three";
	$f[]="#              authentication backends (unix/pam, db, ldap). It stores the data";
	$f[]="#              in a MySQL instance, and optionally stores the attachments directly";
	$f[]="#              on the filesystem.";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="SERVERCONFIG=/etc/zarafa/server.cfg";
	$f[]="SERVERPROGRAM=$binary";
	$f[]="";
	$f[]="# Sanity checks.";
	$f[]="[ -x \$SERVERPROGRAM ] || exit 0";
	$f[]="";
	$f[]="SERVERCONFIG_OPT=\"$options\"";
	$f[]="";
	$f[]="# Source function library.";
	$f[]=". /etc/rc.d/init.d/functions";
	$f[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
	$f[]="";
	$f[]="RETVAL=0";
	$f[]="server=`basename \$SERVERPROGRAM`";
	$f[]="lockfile=/var/lock/subsys/\$server";
	$f[]="pidfile=/var/run/\$server.pid";
	$f[]="";
	$f[]="start() {";
	$f[]="	# Start in background, always succeeds";
	$f[]="	/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure";
	$f[]="	echo -n \$\"Starting \$server: \"";
	$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
	$f[]="	export LANG=\$ZARAFA_LOCALE";
	$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
	$f[]="	daemon \$SERVERPROGRAM \$SERVERCONFIG_OPT";
	$f[]="	RETVAL=\$?";
	$f[]="	unset LC_ALL LANG";
	$f[]="	echo";
	$f[]="	[ \$RETVAL -eq 0 ] && touch \$lockfile";
	$f[]="";
	$f[]="	return \$RETVAL";
	$f[]="}";
	$f[]="";
	$f[]="stop() {";
	$f[]="	if [ -f /tmp/zarafa-upgrade-lock ]; then";
	$f[]="		echo";
	$f[]="		echo \"Zarafa Server database upgrade is taking place.\"";
	$f[]="		echo \"Do not stop this process bacause it may render your database unusable.\"";
	$f[]="		echo";
	$f[]="		exit 1";
	$f[]="	fi";
	$f[]="	# Cannot use killproc, because it has no timeout feature;";
	$f[]="	# zarafa-server may take up to 60 seconds to shutdown.";
	$f[]="	ZARAFAPID=`cat /var/run/zarafa-server.pid 2>/dev/null`";
	$f[]="	if [ -z \"\$ZARAFAPID\" -o ! -n \"\$ZARAFAPID\" ]; then";
	$f[]="		echo -n \"Program ID of \$server not found, trying to stop anyway: \"";
	$f[]="		killall \$SERVERPROGRAM >/dev/null 2>&1";
	$f[]="		RETVAL=\$?";
	$f[]="		echo";
	$f[]="		if [ \$RETVAL -eq 0 ]; then";
	$f[]="			failure \$\"Stopping \$server: \"";
	$f[]="		else";
	$f[]="			success \$\"Stopping \$server: \"";
	$f[]="		fi";
	$f[]="		RETVAL=0";
	$f[]="	else";
	$f[]="		echo -n \$\"Stopping \$server: \"";
	$f[]="		TIMEOUT=65";
	$f[]="		/bin/kill \$ZARAFAPID >/dev/null 2>&1";
	$f[]="		if [ \$? -eq 0 ]; then";
	$f[]="			while [ \$TIMEOUT -gt 0 ]; do";
	$f[]="				/bin/kill -0 \$ZARAFAPID >/dev/null 2>&1 || break";
	$f[]="				sleep 1";
	$f[]="				TIMEOUT=\$[\${TIMEOUT}-1]";
	$f[]="			done";
	$f[]="			if [ \$TIMEOUT -eq 0 ]; then";
	$f[]="				failure \$\"Timeout on stopping \$server\"";
	$f[]="				RETVAL=1";
	$f[]="			else";
	$f[]="				success \$\"Stopping \$server: \"";
	$f[]="				RETVAL=0";
	$f[]="			fi";
	$f[]="		else";
	$f[]="			failure \$\"Stopping \$server: \"";
	$f[]="		fi";
	$f[]="		echo";
	$f[]="	fi";
	$f[]="	[ \$RETVAL -eq 0 ] && rm -f \$lockfile \$pidfile";
	$f[]="";
	$f[]="	return \$RETVAL";
	$f[]="}";
	$f[]="";
	$f[]="restart() {";
	$f[]="	stop";
	$f[]="	start";
	$f[]="}";
	$f[]="";
	$f[]="reload() {";
	$f[]="	echo -n \$\"Restarting \$server: \"";
	$f[]="	/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure";
	$f[]="	killproc \$SERVERPROGRAM -SIGHUP";
	$f[]="	RETVAL=\$?";
	$f[]="	echo";
	$f[]="";
	$f[]="	return \$RETVAL";
	$f[]="}";
	$f[]="";
	$f[]="# See how we were called.";
	$f[]="case \"\$1\" in";
	$f[]="    start)";
	$f[]="		start";
	$f[]="		;;";
	$f[]="    stop)";
	$f[]="		stop";
	$f[]="		;;";
	$f[]="    status)";
	$f[]="		status \$server";
	$f[]="		RETVAL=\$?";
	$f[]="		;;";
	$f[]="    restart|force-reload)";
	$f[]="		restart";
	$f[]="		;;";
	$f[]="    condrestart|try-restart)";
	$f[]="		if [ -f \${pidfile} ]; then";
	$f[]="			stop";
	$f[]="			start";
	$f[]="		fi";
	$f[]="		;;";
	$f[]="    reload)";
	$f[]="		reload";
	$f[]="		;;";
	$f[]="    *)";
	$f[]="		echo \$\"Usage: \$server {start|stop|status|reload|restart|condrestart|force-reload|try-restart}\"";
	$f[]="		RETVAL=1";
	$f[]="		;;";
	$f[]="esac";
	$f[]="";
	$f[]="exit \$RETVAL\n";	


	@file_put_contents("/etc/init.d/zarafa-server", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-server", 0755);
	if(is_file($servicebin)){shell_exec("$servicebin --add zarafa-server >/dev/null 2>&1");
	shell_exec("$servicebin --level 2345 zarafa-server on >/dev/null 2>&1");}	
	echo "Zarafa Server init.d RedHat mode done\n";	
	
}

function zarafa_web(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$zbdb=null;
	$sock=new sockets();


	$f=array();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          zarafa-web";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Zarafa-WebMail Engine";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Zarafa-WebMail Engine";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $zbdb";
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-apache.php --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-apache.php --stop --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="  reload)";
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-apache.php --reload --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";

	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-apache.php --restart --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	@file_put_contents("/etc/init.d/zarafa-web", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-web",0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f zarafa-web defaults >/dev/null 2>&1');
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add zarafa-web >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 zarafa-web on >/dev/null 2>&1');
	}
}


function zarafa_server_all(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["ZARAFA_LOCALE"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ZARAFA_LANG"));
	if($GLOBALS["ZARAFA_LOCALE"]==null){$GLOBALS["ZARAFA_LOCALE"]="en_US";}
	
	
	$zbdb=null;
	$sock=new sockets();
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	$zarafa_admin=$unix->find_program("zarafa-admin");
	if($ZarafaDedicateMySQLServer==1){
		shell_exec("$php /usr/share/artica-postfix/exec.zarafa-db.php --init");
		$zbdb="$php /usr/share/artica-postfix/exec.zarafa-db.php --start";
	}
	@unlink("/etc/default/zarafa");

	if($GLOBALS["ZARAFA_LOCALE"]<>null){
		$DEF[]="ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
		$DEF[]="ZARAFA_USERSCRIPT_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
		@file_put_contents("/etc/default/zarafa",@implode("\n", $DEF));
		$DEF=array();
		
		$DEF[]="#! /bin/sh";
		$DEF[]="# Create a Zarafa Public store for the new company.";
		$DEF[]="PATH=\$PATH:/sbin:/usr/local/sbin:/usr/sbin";
		$DEF[]="# The ZARAFA_COMPANY variable from the server will always be in UTF-8";
		$DEF[]="# format.  The --utf8 option must be set before this value is used,";
		$DEF[]="# since the current locale isn't necessarily UTF-8.";
		$DEF[]="$zarafa_admin --utf8 -s -I \"\${ZARAFA_COMPANY}\"";		
		$DEF[]="";
		@file_put_contents("/etc/zarafa/userscripts/createcompany.d/00createpublic",@implode("\n", $DEF));
		@chmod("/etc/zarafa/userscripts/createcompany.d/00createpublic",0755);
		$DEF=array();
		
		$DEF[]="#! /bin/sh";
		$DEF[]="# Create a Zarafa user for an already existing external user.  Create";
		$DEF[]="# and initialize the user's stores.";
		$DEF[]="PATH=\$PATH:/sbin:/usr/local/sbin:/usr/sbin";
		$DEF[]="# The ZARAFA_USER variable from the server will always be in UTF-8";
		$DEF[]="# format.  The --utf8 option must be set before this value is used,";
		$DEF[]="# since the current locale isn't necessarily UTF-8.";
		$DEF[]="zarafa-admin --utf8 --create-store \"\${ZARAFA_USER}\" --lang \"{$GLOBALS["ZARAFA_LOCALE"]}\"";
		$DEF[]="";
		@file_put_contents("/etc/zarafa/userscripts/createuser.d/00createstore",@implode("\n", $DEF));
		@chmod("/etc/zarafa/userscripts/createuser.d/00createstore",0755);
		$DEF=array();
		
		$DEF[]="# shell include script";
		$DEF[]="ZARAFA_LANG=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
		$DEF[]="PATH=/bin:/usr/local/bin:/usr/bin";
		$DEF[]="export ZARAFA_LANG PATH";
		$DEF[]="if [ -z \"\${ZARAFA_USER_SCRIPTS}\" ] ; then";
		$DEF[]="exec >&2";
		$DEF[]="echo \"Do not execute this script directly\"";
		$DEF[]="exit 1";
		$DEF[]="fi";
		$DEF[]="if [ ! -d \"\${ZARAFA_USER_SCRIPTS}\" ] ; then";
		$DEF[]="exec >&2";
		$DEF[]="echo \"\${ZARAFA_USER_SCRIPTS} does not exist or is not a directory\"";
		$DEF[]="exit 1";
		$DEF[]="fi";
		$DEF[]="if [ -z \"\${ZARAFA_USER}\" -a -z \"\${ZARAFA_STOREGUID}\" ] ; then";
		$DEF[]="exec >&2";
		$DEF[]="echo \"ZARAFA_USER and ZARAFA_STOREGUID is not set.\"";
		$DEF[]="exit 1";
		$DEF[]="fi";
		$DEF[]="find \${ZARAFA_USER_SCRIPTS} -maxdepth 1 -type f -perm -u=x -not -name \*~ -not -name \#\* -not -name \*.rpm\* -not -name \*.bak -not -name \*.old -exec {} \;";
		$DEF[]="";
		@file_put_contents("/etc/zarafa/userscripts/users_common.sh",@implode("\n", $DEF));
		@chmod("/etc/zarafa/userscripts/users_common.sh",0755);
		$DEF=array();
		
	}

	
	
	
	
	$f=array();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          zarafa-server";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$named \$network \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$named \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Zarafa-server Main";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Zarafa-server Main";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $zbdb";
	$f[]="	ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
	$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
	$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
	$f[]="	export LANG=\$ZARAFA_LOCALE";	
	$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-server.php --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-server.php --stop --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="  reload)";
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-server.php --reload --byinitd --force \$2 \$3";
	$f[]="    ;;";	
	$f[]="";
	$f[]=" restart)";
	
	$f[]="    $php ". dirname(__FILE__)."/exec.zarafa-server.php --restart --byinitd --force \$2 \$3";
	
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	@file_put_contents("/etc/init.d/zarafa-server", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-server",0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f zarafa-server defaults >/dev/null 2>&1');
		shell_exec('/usr/sbin/update-rc.d -f zarafa-server2 defaults >/dev/null 2>&1');
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec('/sbin/chkconfig --add zarafa-server >/dev/null 2>&1');
		shell_exec('/sbin/chkconfig --level 2345 zarafa-server on >/dev/null 2>&1');
	}
}	
	


function zarafa_server_debian(){
	$unix=new unix();
	$sock=new sockets();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$binary=$unix->find_program("zarafa-server");
	if(!is_file($binary)){return;}	
	$servicebin=$unix->find_program("update-rc.d");
	if(!is_file($servicebin)){return;}
	$options=zarafa_server_options();
	
	$f[]="#! /bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          zarafa-server";
	$f[]="# Required-Start:    \$syslog \$network \$remote_fs";
	$f[]="# Required-Stop:     \$syslog \$network \$remote_fs";
	$f[]="# Should-Start:      mysql";
	$f[]="# Should-Stop:       mysql";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Zarafa Collaboration Platform's Storage Server";
	$f[]="# Description:       The Zarafa Server takes MAPI calls in SOAP over HTTP(S) or";
	$f[]="#                    the unix socket. It authenticates users using one of three";
	$f[]="#                    authentication backends (unix/pam, db, ldap). It stores the data";
	$f[]="#                    in a MySQL instance, and optionally stores the attachments directly";
	$f[]="#                    on the filesystem.";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="SERVER=$binary";
	$f[]="DESC=\"Zarafa server\"";
	$f[]="NAME=`basename \$SERVER`";
	$f[]="#QUIETDAEMON=--quiet";
	$f[]="PIDFILE=/var/run/\$NAME.pid";
	$f[]="";
	$f[]="test -x \$SERVER || exit 0";
	
	$f[]="	ZARAFA_LOCALE=\"{$GLOBALS["ZARAFA_LOCALE"]}\"";
	$f[]="";
	$f[]="SERVER_OPTS=\"$options\"";
	$f[]="SERVER_ENABLED=\"yes\"";
	$f[]="#set -e";
	$f[]="";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="	if [ \"\$SERVER_ENABLED\" = \"no\" ]; then";
	$f[]="		log_warning_msg \"Zarafa Server daemon not enabled in /etc/default/zarafa ... not starting\"";
	$f[]="		exit 0";
	$f[]="	fi";
	$f[]="	/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure";
	$f[]="	log_begin_msg \"Starting \$DESC: \$NAME\"";
	$f[]="	export LC_ALL=\$ZARAFA_LOCALE";
	$f[]="	export LANG=\$ZARAFA_LOCALE";
	$f[]="	export ZARAFA_USERSCRIPT_LOCALE=\$ZARAFA_LOCALE";
	$f[]="	start-stop-daemon --start \$QUIETDAEMON --pidfile \$PIDFILE --exec \$SERVER -- \$SERVER_OPTS";
	$f[]="	log_end_msg \$?";
	$f[]="	unset LC_ALL LANG";
	$f[]="	;;";
	$f[]="  stop)";
	$f[]="	if [ -f /tmp/zarafa-upgrade-lock ]; then";
	$f[]="		echo";
	$f[]="		echo \"Zarafa database upgrade is taking place.\"";
	$f[]="		echo \"Do not stop this process bacause it may render your database unusable.\"";
	$f[]="		echo";
	$f[]="		exit 1";
	$f[]="	fi";
	$f[]="	# the server may take up to 60 seconds to exit, so we wait a bit longer than that";
	$f[]="	log_begin_msg \"Stopping \$DESC: \$NAME\"";
	$f[]="	start-stop-daemon --stop \$QUIETDAEMON --pidfile \$PIDFILE --retry TERM/65 --exec \$SERVER";
	$f[]="	RETVAL=\$?";
	$f[]="	rm -f \$PIDFILE";
	$f[]="	log_end_msg \$RETVAL";
	$f[]="	;;";
	$f[]="  restart)";
	$f[]="	\$0 stop";
	$f[]="	log_begin_msg \"reconfiguring \$DESC: \$NAME\"";
	$f[]="	/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure";
	$f[]="	\$0 start";
	$f[]="	;;";
	$f[]="  status)";
	$f[]="	status_of_proc \"\$SERVER\" \"\$NAME\" && exit 0 || exit \$?";
	$f[]="	;;";
	$f[]="  reload|force-reload)";
	$f[]="	log_begin_msg \"Reloading \$DESC: \$NAME\"";
	$f[]="	/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure";
	$f[]="	start-stop-daemon --stop \$QUIETDAEMON --signal HUP --pidfile \$PIDFILE --exec \$SERVER";
	$f[]="	log_end_msg \$?";
	$f[]="	;;";
	$f[]="  *)";
	$f[]="	N=/etc/init.d/\$NAME";
	$f[]="	echo \"Usage: \$N {start|stop|restart|reload|force-reload|status}\" >&2";
	$f[]="	exit 1";
	$f[]="	;;";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0\n";	
	@file_put_contents("/etc/init.d/zarafa-server", @implode("\n", $f));
	@chmod("/etc/init.d/zarafa-dagent", 0755);
	shell_exec("$servicebin -f zarafa-server defaults >/dev/null 2>&1");
	echo "Zarafa Server init.d debian mode done\n";			
	
}




