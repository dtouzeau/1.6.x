<?php
$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}




$dir_handle = @opendir("{$GLOBALS["LogFileDeamonLogDir"]}");

if(!$dir_handle){
	die();
}

while ($file = readdir($dir_handle)) {
	  if($file=='.'){continue;}
	  if($file=='..'){continue;}
	  if(is_dir("{$GLOBALS["LogFileDeamonLogDir"]}/$file")){continue;}
	  
	  echo "Remove $file\n";
	  @unlink("{$GLOBALS["LogFileDeamonLogDir"]}/$file");
		
		
}