unit framework;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,awstats;

type
  TStringDynArray = array of string;

  type
  tframework=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     awstats:tawstats;
     mem_pid:string;
    function    Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
    function    APACHE_SET_MODULES():string;
    procedure   START_APACHE();
    procedure   APACHE_CONFIG();
    function    APACHE_ADD_MODULE(moduleso_file:string):string;
    function    APACHE_AuthorizedModule(module_so:string):boolean;

public
EnableLighttpd:integer;
    InsufficentRessources:Boolean;
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    function    LIGHTTPD_BIN_PATH():string;
    function    LIGHTTPD_PID():string;
    procedure   STOP();
    function    LIGHTTPD_VERSION():string;
    FUNCTION    STATUS():string;
    function    PHP5_CGI_BIN_PATH():string;
    function    DEFAULT_CONF():string;
    function    MON():string;
    procedure   INSTALL_INIT_D();

END;

implementation                      

constructor tframework.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       forcedirectories('/opt/artica/tmp');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
       EnableLighttpd:=1;
       awstats:=tawstats.Create(SYS);
        InsufficentRessources:=SYS.ISMemoryHiger1G();



       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tframework.free();
begin
    logs.Free;
    SYS.Free;
end;
//##############################################################################
function tframework.LIGHTTPD_BIN_PATH():string;
begin
exit(SYS.LOCATE_LIGHTTPD_BIN_PATH());
end;
//##############################################################################
function tframework.PHP5_CGI_BIN_PATH():string;
begin
   if FileExists('/usr/bin/php-fcgi') then exit('/usr/bin/php-fcgi');
   if FileExists('/usr/bin/php-cgi') then exit('/usr/bin/php-cgi');
   if FileExists('/usr/local/bin/php-cgi') then exit('/usr/local/bin/php-cgi');
end;
//##############################################################################

procedure tframework.START_APACHE();
var
  pid:string;
  bin_path:string;
  count:integer;
begin
     pid:=LIGHTTPD_PID();

 if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: Framework Apache daemon already running PID '+pid);
      exit;
 end;

 bin_path:=SYS.LOCATE_APACHE_BIN_PATH();

   if not FileExists(bin_path) then begin
       logs.Debuglogs('Starting......: Framework Apache it seems that apache is not installed... Aborting');
       exit;
   end;

   logs.DebugLogs('Starting......: Framework Apache daemon libphp....:'+SYS.LOCATE_APACHE_LIBPHP5());
   logs.Debuglogs('Starting......: Framework Apache daemon Building configuration');
   fpsystem('useradd apache-root >/dev/null 2>&1');
   fpsystem('usermod -G root apache-root >/dev/null 2>&1');
   ForceDirectories('/var/run/lighttpd');
   APACHE_CONFIG();
   fpsystem(bin_path+' -f /etc/artica-postfix/framework.conf');

 count:=0;
 while not SYS.PROCESS_EXIST(LIGHTTPD_PID()) do begin
              sleep(150);
              inc(count);
              if count>50 then begin
                 logs.DebugLogs('Starting......: Framework Apache daemon. (timeout!!!)');
                 logs.DebugLogs('Starting......: Framework Apache daemon. "'+bin_path+' -f /etc/artica-postfix/framework.conf" failed');
                 break;
              end;
        end;

 pid:=LIGHTTPD_PID();
 if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: Framework Apache daemon with new PID '+pid);
 end;



end;

//##############################################################################

procedure tframework.APACHE_CONFIG();

var
l:Tstringlist;
begin
l:=Tstringlist.Create;
l.add('ServerRoot "/usr/share/artica-postfix/framework"');
l.add('Listen 127.0.0.1:47980');
l.add('');
l.add('<IfModule !mpm_netware_module>');
l.add('User apache-root');
l.add('Group root');
l.add('ServerName '+GetHostname());
l.add('</IfModule>');
l.add('');
l.add('ServerAdmin you@example.com');
l.add('DocumentRoot "/usr/share/artica-postfix/framework"');
l.add('');
l.add('<Directory />');
l.add('    Options FollowSymLinks');
l.add('    AllowOverride None');
l.add('    Order deny,allow');
l.add('    Deny from all');
l.add('</Directory>');
l.add('');
l.add('');
l.add('<Directory "/usr/share/artica-postfix/framework">');
l.add('    Options Indexes FollowSymLinks');
l.add('    AllowOverride None');
l.add('    Order allow,deny');
l.add('    Allow from all');
l.add('');
l.add('</Directory>');
l.add('');
l.add('<IfModule dir_module>');
l.add('    DirectoryIndex index.php');
l.add('</IfModule>');
l.add('');
l.add('');
l.add('ErrorLog	/var/log/artica-postfix/framework_error.log');
l.add('');
l.add('LogLevel warn');
l.add('');
l.add('<IfModule log_config_module>');
l.add('    LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combined');
l.add('    LogFormat "%h %l %u %t \"%r\" %>s %b" common');
l.add('');
l.add('    <IfModule logio_module>');
l.add('      LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %I %O" combinedio');
l.add('    </IfModule>');
l.add('');
l.add('CustomLog	/var/log/artica-postfix/framework_error.log common');
l.add('</IfModule>');
l.add('');
l.add('<IfModule alias_module>');
l.add('    ScriptAlias /cgi-bin/ "/opt/artica/cgi-bin/"');
l.add('');
l.add('</IfModule>');
l.add('');
l.add('<IfModule cgid_module>');
l.add('</IfModule>');
l.add('');
l.add('<Directory "/opt/artica/cgi-bin">');
l.add('    AllowOverride None');
l.add('    Options None');
l.add('    Order allow,deny');
l.add('    Allow from all');
l.add('</Directory>');
l.add('');
l.add('DefaultType text/plain');
l.add('');
l.add('<IfModule mime_module>');
l.add('TypesConfig	/etc/mime.types');
l.add('');
l.add('    #AddType application/x-gzip .tgz');
l.add('    #AddEncoding x-compress .Z');
l.add('    #AddEncoding x-gzip .gz .tgz');
l.add('    AddType application/x-compress .Z');
l.add('    AddType application/x-gzip .gz .tgz');
l.add('    AddType application/x-httpd-php .php .phtml');
l.add('    AddType application/x-httpd-php-source .phps');
l.add('    #AddHandler cgi-script .cgi');
l.add('    #AddType text/html .shtml');
l.add('    #AddOutputFilter INCLUDES .shtml');
l.add('</IfModule>');
l.add('');
l.add('#MIMEMagicFile conf/magic');
l.add('#ErrorDocument 500 "The server made a boo boo."');
l.add('#ErrorDocument 404 /missing.html');
l.add('#ErrorDocument 404 "/cgi-bin/missing_handler.pl"');
l.add('#ErrorDocument 402 http://www.example.com/subscription_info.html');
l.add('#EnableMMAP off');
l.add('#EnableSendfile off');
l.add('');
l.add('PidFile	/var/run/lighttpd/framework.pid');
l.Add(APACHE_SET_MODULES());
logs.WriteToFile(l.Text,'/etc/artica-postfix/framework.conf');
l.free;
end;
//##############################################################################
function tframework.APACHE_SET_MODULES():string;
var
i:integer;
APACHE_MODULES_PATH:string;
l:Tstringlist;
xmod:string;
begin
l:=Tstringlist.Create;
APACHE_MODULES_PATH:=SYS.LOCATE_APACHE_MODULES_PATH();

if length(APACHE_MODULES_PATH)=0 then begin
    logs.DebugLogs('Starting......: Framework Apache daemon unable to locate modules path');
    result:='';
    exit;
end;


logs.DebugLogs('Starting......: Framework Apache daemon modules are stored in '+APACHE_MODULES_PATH);
SYS.DirFiles(SYS.LOCATE_APACHE_MODULES_PATH(),'*.so');
 for i:=0 to SYS.DirListFiles.Count-1 do begin
       xmod:=trim(APACHE_ADD_MODULE(SYS.DirListFiles.Strings[i]));
       if length(xmod)=0 then begin
          logs.DebugLogs('Apache daemon refused mod:"'+SYS.DirListFiles.Strings[i]+'"');
          continue;
       end;
       logs.DebugLogs('Starting......: Framework Apache daemon add mod:"'+SYS.DirListFiles.Strings[i]+'"');
       l.Add(APACHE_ADD_MODULE(SYS.DirListFiles.Strings[i]));
end;

if FileExists('/usr/lib/apache-extramodules/mod_php5.so') then begin
   logs.DebugLogs('Starting......: Framework Apache daemon add mod:"mod_php5.so"');
   l.add('LoadModule php5_module'+chr(9)+'/usr/lib/apache-extramodules/mod_php5.so');
end;


logs.DebugLogs('Starting......: Framework Apache daemon '+IntTOstr(l.Count) +' modules');
result:=l.Text;

end;
//##############################################################################
function tframework.APACHE_ADD_MODULE(moduleso_file:string):string;
  var
   RegExpr:TRegExpr;
   ADD:boolean;
   l:TstringList;
   i:integer;
   moduleso_file_pattern:string;
   APACHE_MODULES_PATH:string;
   module_name:string;
begin
moduleso_file:=trim(moduleso_file);
APACHE_MODULES_PATH:=SYS.LOCATE_APACHE_MODULES_PATH();


if moduleso_file='mod_perl.so' then begin
    result:='LoadModule perl_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;

if moduleso_file='mod_log_config.so' then begin
    result:='LoadModule log_config_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;

if moduleso_file='mod_vhost_ldap.so' then begin
    result:='LoadModule vhost_ldap_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;


if moduleso_file='mod_ldap.so' then begin
    result:='LoadModule ldap_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;

if moduleso_file='mod_rewrite.so' then begin
    result:='LoadModule rewrite_module'+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
    exit;
end;



if not APACHE_AuthorizedModule(moduleso_file) then exit;
if moduleso_file='mod_proxy_connect.so' then exit;
if moduleso_file='mod_dav_lock.so' then exit;
if moduleso_file='mod_mem_cache.so' then exit;
if moduleso_file='mod_cgid.so' then exit;
if moduleso_file='mod_proxy.so' then exit;
if moduleso_file='mod_proxy_http.so' then exit;
if moduleso_file='mod_proxy_ajp.so' then exit;

ADD:=false;
moduleso_file_pattern:=AnsiReplaceText(moduleso_file,'.','\.');
RegExpr:=TRegExpr.CReate;
RegExpr.Expression:='^mod_(.+?)\.so';
if RegExpr.Exec(moduleso_file) then begin
     module_name:=RegExpr.Match[1]+'_module';
end else begin
    RegExpr.Expression:='^(.+?)\.so';
    module_name:=RegExpr.Match[1]+'_module';
end;


if moduleso_file='libphp5.so' then module_name:='php5_module';
result:='LoadModule '+ module_name+chr(9)+APACHE_MODULES_PATH+'/'+moduleso_file;
end;
//##############################################################################
function tframework.APACHE_AuthorizedModule(module_so:string):boolean;
var
   l:Tstringlist;
   i:integer;
begin

l:=Tstringlist.Create;
result:=false;
l.add('mod_alias.so');
l.add('mod_auth_basic.so');
l.add('mod_authn_file.so');
l.add('mod_authz_default.so');
l.add('mod_authz_groupfile.so');
l.add('mod_authz_host.so');
l.add('mod_authz_user.so');
l.add('mod_autoindex.so');
l.add('mod_cgi.so');
l.add('mod_deflate.so');
l.add('mod_dir.so');
l.add('mod_env.so');
l.add('mod_mime.so');
l.add('mod_negotiation.so');
l.add('libphp5.so');
l.add('mod_setenvif.so');
l.add('mod_status.so');
l.add('mod_ssl.so');
for i:=0 to l.Count-1 do begin
    if l.Strings[i]=module_so then begin
       result:=true;
       break;
    end;

end;
l.free;

end;
//##############################################################################
procedure tframework.START();
var
  pid:string;
begin


logs.Debuglogs('###################### FRAMEWORK #####################');

   if not FileExists(LIGHTTPD_BIN_PATH()) then begin
       logs.Debuglogs('LIGHTTPD_START():: it seems that lighttpd is not installed... Aborting');
       START_APACHE();
       exit;
   end;

    if FileExists('/var/log/artica-postfix/framework.log') then logs.DeleteFile('/var/log/artica-postfix/framework.log');

   if not FileExists('/etc/init.d/artica-framework') then  INSTALL_INIT_D();
   pid:=LIGHTTPD_PID();
   ForceDirectories('/var/run/lighttpd');

   if SYS.PROCESS_EXIST(pid) then begin
      logs.Debuglogs('Starting......: framework daemon is already running using PID ' + LIGHTTPD_PID() + '...');
      logs.Debuglogs('LIGHTTPD_START():: framework already running with PID number ' + pid);
      exit();
   end;
    fpsystem(SYS.LOCATE_GENERIC_BIN('nohup')+' '+SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.web-community-filter.php --register-lic >/dev/null 2>&1 &');
    DEFAULT_CONF();
    logs.OutputCmd(LIGHTTPD_BIN_PATH()+ ' -f /etc/artica-postfix/framework.conf');


   if not SYS.PROCESS_EXIST(LIGHTTPD_PID()) then begin
      logs.Debuglogs('Starting framework...........: Failed "' + LIGHTTPD_BIN_PATH()+ ' -f /etc/artica-postfix/framework.conf"');
    end else begin
      logs.Debuglogs('Starting framework...........: Success (PID ' + LIGHTTPD_PID() + ')');
   end;

end;
//##############################################################################
function tframework.MON():string;
var
l:TstringList;
begin
l:=TstringList.Create;
l.ADD('check process '+ExtractFileName(LIGHTTPD_BIN_PATH())+' with pidfile /var/run/lighttpd/framework.pid');
l.ADD('group framework');
l.ADD('start program = "/etc/init.d/artica-postfix start apache"');
l.ADD('stop program = "/etc/init.d/artica-postfix stop apache"');
l.ADD('if 5 restarts within 5 cycles then timeout');
result:=l.Text;
l.free;
end;
//##############################################################################


procedure tframework.STOP();
 var
    count      :integer;
begin

     count:=0;

     logs.DeleteFile('/etc/artica-postfix/cache.global.status');
     if SYS.PROCESS_EXIST(LIGHTTPD_PID()) then begin
        writeln('Stopping framework...........: ' + LIGHTTPD_PID() + ' PID..');
        logs.OutputCmd('/bin/kill ' + LIGHTTPD_PID());

        while SYS.PROCESS_EXIST(LIGHTTPD_PID()) do begin
              sleep(100);
              inc(count);
              if count>100 then begin
                 writeln('Stopping framework...........: Failed force kill');
                 logs.OutputCmd('/bin/kill -9 '+LIGHTTPD_PID());
                 exit;
              end;
        end;

      end else begin

        writeln('Stopping framework...........: Already stopped');
     end;

end;
//##############################################################################
FUNCTION tframework.STATUS():string;
var
pidpath:string;
begin
SYS.MONIT_DELETE('APP_FRAMEWORK');
pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --framework >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath);
end;
//#########################################################################################
function tframework.LIGHTTPD_VERSION():string;
var
     l:TstringList;
     RegExpr:TRegExpr;
     i:integer;
     tmpstr:string;
begin
    if not FileExists(LIGHTTPD_BIN_PATH()) then exit;

    result:=SYS.GET_CACHE_VERSION('APP_LIGHTTPD');
    if length(result)>0 then exit;
    tmpstr:=logs.FILE_TEMP();

    fpsystem(LIGHTTPD_BIN_PATH()+' -v >'+tmpstr+' 2>&1');
    if not FileExists(tmpstr) then exit;
    l:=TStringList.Create;
    l.LoadFromFile(tmpstr);
    logs.DeleteFile(tmpstr);
    RegExpr:=TRegExpr.Create;
    RegExpr.Expression:='lighttpd-([0-9\.]+)';
    For i:=0 to l.Count-1 do begin
        if RegExpr.Exec(l.Strings[i]) then begin
            result:=RegExpr.Match[1];
            logs.Debuglogs('LIGHTTPD_VERSION:: ' + result);
        end;
    end;

    SYS.SET_CACHE_VERSION('APP_LIGHTTPD',result);

    l.free;
    RegExpr.Free;
end;
//##############################################################################
function tframework.LIGHTTPD_PID():string;
var
   lighttpd_bin:string;
   pid:string;
begin

   pid:=SYS.GET_PID_FROM_PATH('/var/run/lighttpd/framework.pid');
   if SYS.PROCESS_EXIST(pid) then begin
        result:=pid;
        mem_pid:=pid;
       exit;
   end;

   lighttpd_bin:=LIGHTTPD_BIN_PATH();
   if FileExists(lighttpd_bin) then begin
      result:=SYS.PIDOF_PATTERN(lighttpd_bin + ' -f /etc/artica-postfix/framework.conf');
      mem_pid:=result;
      exit;
   end;
   lighttpd_bin:=SYS.LOCATE_APACHE_BIN_PATH();
   if FileExists(lighttpd_bin) then begin
      result:=SYS.PIDOF_PATTERN(lighttpd_bin + ' -f /etc/artica-postfix/framework.conf');
      mem_pid:=result;
      exit;
   end;
end;
//##############################################################################
function tframework.DEFAULT_CONF():string;
var
l:TstringList;
PHP_FCGI_CHILDREN:Integer;
PHP_FCGI_MAX_REQUESTS:integer;
max_procs:integer;
LighttpdRunAsminimal:integer;
LighttpdArticaMaxProcs:integer;
LighttpdArticaMaxChildren:integer;
begin
result:='';
l:=TstringList.Create;

forceDirectories('/usr/share/artica-postfix/framework');
forceDirectories('/usr/share/artica-postfix/ressources/sock');
if not TryStrToInt(SYS.GET_INFO('LighttpdRunAsminimal'),LighttpdRunAsminimal) then LighttpdRunAsminimal:=0;
if not tryStrToInt(sys.GET_INFO('LighttpdArticaMaxProcs'),LighttpdArticaMaxProcs) then LighttpdArticaMaxProcs:=0;
if not tryStrToInt(sys.GET_INFO('LighttpdArticaMaxChildren'),LighttpdArticaMaxChildren) then LighttpdArticaMaxChildren:=0;
if not tryStrToInt(sys.GET_INFO('PHP_FCGI_MAX_REQUESTS'),PHP_FCGI_MAX_REQUESTS) then PHP_FCGI_MAX_REQUESTS:=200;

PHP_FCGI_CHILDREN:=3;
max_procs:=3;


if LighttpdArticaMaxProcs>0 then max_procs:=LighttpdArticaMaxProcs;
if LighttpdArticaMaxChildren>0 then PHP_FCGI_CHILDREN:=LighttpdArticaMaxChildren;


if not InsufficentRessources then begin
     PHP_FCGI_CHILDREN:=2;
     max_procs:=1;
end;

if LighttpdRunAsminimal=1 then begin
       max_procs:=1;
       PHP_FCGI_CHILDREN:=2;
       PHP_FCGI_MAX_REQUESTS:=500;
end;



l.Add('#artica-postfix saved by artica lighttpd.conf');
l.Add('');
l.Add('server.modules = (');
l.Add('        "mod_alias",');
l.Add('        "mod_access",');
l.Add('        "mod_accesslog",');
l.Add('        "mod_compress",');
l.Add('        "mod_fastcgi",');
l.Add('        "mod_cgi",');
l.Add('	       "mod_status"');
l.Add(')');
l.Add('');
l.Add('server.document-root        = "/usr/share/artica-postfix/framework"');
//l.Add('server.username = "root"');
//l.Add('server.groupname = "root"');
l.Add('server.errorlog             = "/var/log/artica-postfix/framework_error.log"');
l.Add('index-file.names            = ( "index.php")');
l.Add('');
l.Add('mimetype.assign             = (');
l.Add('  ".pdf"          =>      "application/pdf",');
l.Add('  ".sig"          =>      "application/pgp-signature",');
l.Add('  ".spl"          =>      "application/futuresplash",');
l.Add('  ".class"        =>      "application/octet-stream",');
l.Add('  ".ps"           =>      "application/postscript",');
l.Add('  ".torrent"      =>      "application/x-bittorrent",');
l.Add('  ".dvi"          =>      "application/x-dvi",');
l.Add('  ".gz"           =>      "application/x-gzip",');
l.Add('  ".pac"          =>      "application/x-ns-proxy-autoconfig",');
l.Add('  ".swf"          =>      "application/x-shockwave-flash",');
l.Add('  ".tar.gz"       =>      "application/x-tgz",');
l.Add('  ".tgz"          =>      "application/x-tgz",');
l.Add('  ".tar"          =>      "application/x-tar",');
l.Add('  ".zip"          =>      "application/zip",');
l.Add('  ".mp3"          =>      "audio/mpeg",');
l.Add('  ".m3u"          =>      "audio/x-mpegurl",');
l.Add('  ".wma"          =>      "audio/x-ms-wma",');
l.Add('  ".wax"          =>      "audio/x-ms-wax",');
l.Add('  ".ogg"          =>      "application/ogg",');
l.Add('  ".wav"          =>      "audio/x-wav",');
l.Add('  ".gif"          =>      "image/gif",');
l.Add('  ".jar"          =>      "application/x-java-archive",');
l.Add('  ".jpg"          =>      "image/jpeg",');
l.Add('  ".jpeg"         =>      "image/jpeg",');
l.Add('  ".png"          =>      "image/png",');
l.Add('  ".xbm"          =>      "image/x-xbitmap",');
l.Add('  ".xpm"          =>      "image/x-xpixmap",');
l.Add('  ".xwd"          =>      "image/x-xwindowdump",');
l.Add('  ".css"          =>      "text/css",');
l.Add('  ".html"         =>      "text/html",');
l.Add('  ".htm"          =>      "text/html",');
l.Add('  ".js"           =>      "text/javascript",');
l.Add('  ".asc"          =>      "text/plain",');
l.Add('  ".c"            =>      "text/plain",');
l.Add('  ".cpp"          =>      "text/plain",');
l.Add('  ".log"          =>      "text/plain",');
l.Add('  ".conf"         =>      "text/plain",');
l.Add('  ".text"         =>      "text/plain",');
l.Add('  ".txt"          =>      "text/plain",');
l.Add('  ".dtd"          =>      "text/xml",');
l.Add('  ".xml"          =>      "text/xml",');
l.Add('  ".mpeg"         =>      "video/mpeg",');
l.Add('  ".mpg"          =>      "video/mpeg",');
l.Add('  ".mov"          =>      "video/quicktime",');
l.Add('  ".qt"           =>      "video/quicktime",');
l.Add('  ".avi"          =>      "video/x-msvideo",');
l.Add('  ".asf"          =>      "video/x-ms-asf",');
l.Add('  ".asx"          =>      "video/x-ms-asf",');
l.Add('  ".wmv"          =>      "video/x-ms-wmv",');
l.Add('  ".bz2"          =>      "application/x-bzip",');
l.Add('  ".tbz"          =>      "application/x-bzip-compressed-tar",');
l.Add('  ".tar.bz2"      =>      "application/x-bzip-compressed-tar",');
l.Add('  ""              =>      "application/octet-stream",');
l.Add(' )');
l.Add('');
l.Add('');
l.Add('accesslog.filename          = "/var/log/artica-postfix/framework.log"');
l.Add('url.access-deny             = ( "~", ".inc" )');
l.Add('');
l.Add('static-file.exclude-extensions = ( ".php", ".pl", ".fcgi" )');
l.Add('server.port                 = 47980');
l.Add('server.bind                = "127.0.0.1"');
//l.Add('server.bind                = "0.0.0.0"');
l.Add('#server.error-handler-404   = "/error-handler.html"');
l.Add('#server.error-handler-404   = "/error-handler.php"');
l.Add('server.pid-file             = "/var/run/lighttpd/framework.pid"');
l.Add('server.max-keep-alive-requests = 0');
l.Add('server.max-keep-alive-idle = 4');
l.Add('server.stat-cache-engine = "simple"');
l.Add('server.max-fds 		   = 2048');
l.Add('server.network-backend      = "writev"');
l.Add('');


l.Add('fastcgi.server = ( ".php" =>((');
l.Add('                "bin-path" => "/usr/bin/php-cgi",');
l.Add('                "socket" => "/var/run/artica-framework-fastcgi-" + PID + ".sock",');
l.Add('                "max-procs" => '+IntTosTR(max_procs)+',');
l.Add('                "idle-timeout" => 30,');
l.Add('                "bin-environment" => (');
l.Add('                        "PHP_FCGI_CHILDREN" => "'+IntToStr(PHP_FCGI_CHILDREN)+'",');
l.Add('                        "PHP_FCGI_MAX_REQUESTS" => "'+IntToStr(PHP_FCGI_MAX_REQUESTS)+'",');
l.Add('                        "PATH" => "/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/kerberos/bin",');
l.Add('                        "LD_LIBRARY_PATH" => "/lib:/usr/local/lib:/usr/lib/libmilter:/usr/lib",');
l.Add('                        "CPPFLAGS" => "-I/usr/include/libmilter -I/usr/include -I/usr/local/include -I/usr/include/linux -I/usr/include/sm/os",');
l.Add('                        "LDFLAGS" => "-L/lib -L/usr/local/lib -L/usr/lib/libmilter -L/usr/lib",');
l.Add('                ),');
//l.Add('                "bin-copy-environment" => ("PATH","SHELL", "USER"'),');
l.Add('                "broken-scriptfilename" => "enable"');
l.Add('        ))');
l.Add(')');
l.Add('ssl.engine                 = "disable"');
l.Add('status.status-url          = "/server-status"');
l.Add('status.config-url          = "/server-config"');
l.Add('$HTTP["url"] =~ "^/webmail" {');
l.Add('	server.follow-symlink = "enable"');
l.Add('}');
l.Add('alias.url += ( "/cgi-bin/" => "/usr/lib/cgi-bin/" )');
l.Add('alias.url += ( "/css/" => "/usr/share/artica-postfix/css/" )');
l.Add('alias.url += ( "/img/" => "/usr/share/artica-postfix/img/" )');
l.Add('alias.url += ( "/js/" => "/usr/share/artica-postfix/js/" )');
l.Add('');
l.Add('cgi.assign= (');
l.Add('	".pl"  => "/usr/bin/perl",');
l.Add('	".php" => "/usr/bin/php-cgi",');
l.Add('	".py"  => "/usr/bin/python",');
l.Add('	".cgi"  => "/usr/bin/perl",');
l.Add(')');
logs.WriteToFile(l.Text,'/etc/artica-postfix/framework.conf');
l.free;
end;
//##############################################################################
procedure tframework.INSTALL_INIT_D();
var
   l:Tstringlist;
begin
l:=Tstringlist.Create;
l.add('#!/bin/sh');
 if fileExists('/sbin/chkconfig') then begin
    l.Add('# chkconfig: 2345 11 89');
    l.Add('# description: Artica-framework Daemon');
 end;
l.add('### BEGIN INIT INFO');
l.add('# Provides:          Artica-framework ');
l.add('# Required-Start:    $local_fs');
l.add('# Required-Stop:     $local_fs');
l.add('# Should-Start:');
l.add('# Should-Stop:');
l.add('# Default-Start:     2 3 4 5');
l.add('# Default-Stop:      0 1 6');
l.add('# Short-Description: Start Artica framework daemon');
l.add('# chkconfig: 2345 11 89');
l.add('# description: Artica framework Daemon');
l.add('### END INIT INFO');
l.add('');
l.add('case "$1" in');
l.add(' start)');
l.add('    /usr/share/artica-postfix/bin/artica-install -watchdog framework $2');
l.add('    ;;');
l.add('');
l.add('  stop)');
l.add('    /usr/share/artica-postfix/bin/artica-install -shutdown framework $2');
l.add('    ;;');
l.add('');
l.add(' restart)');
l.add('     /usr/share/artica-postfix/bin/artica-install -shutdown framework $2');
l.add('     sleep 3');
l.add('     /usr/share/artica-postfix/bin/artica-install -watchdog framework $2');
l.add('    ;;');
l.add('');
l.add('  *)');
l.add('    echo "Usage: $0 {start|stop|restart}"');
l.add('    exit 1');
l.add('    ;;');
l.add('esac');
l.add('exit 0');

logs.WriteToFile(l.Text,'/etc/init.d/artica-framework');
 fpsystem('/bin/chmod +x /etc/init.d/artica-framework >/dev/null 2>&1');

 if FileExists('/usr/sbin/update-rc.d') then begin
    fpsystem('/usr/sbin/update-rc.d -f artica-framework defaults >/dev/null 2>&1');
 end;

  if FileExists('/sbin/chkconfig') then begin
     fpsystem('/sbin/chkconfig --add artica-framework >/dev/null 2>&1');
     fpsystem('/sbin/chkconfig --level 2345 artica-framework on >/dev/null 2>&1');
  end;

   LOGS.Debuglogs('Starting......: framework install init.d scripts........:OK (/etc/init.d/artica-framework {start,stop,restart})');



end;
//##############################################################################
function tframework.Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
var
  SepLen       : Integer;
  F, P         : PChar;
  ALen, Index  : Integer;
begin
  SetLength(Result, 0);
  if (S = '') or (Limit < 0) then
    Exit;
  if Separator = '' then
  begin
    SetLength(Result, 1);
    Result[0] := S;
    Exit;
  end;
  SepLen := Length(Separator);
  ALen := Limit;
  SetLength(Result, ALen);

  Index := 0;
  P := PChar(S);
  while P^ <> #0 do
  begin
    F := P;
    P := StrPos(P, PChar(Separator));
    if (P = nil) or ((Limit > 0) and (Index = Limit - 1)) then
      P := StrEnd(F);
    if Index >= ALen then
    begin
      Inc(ALen, 5); // mehrere auf einmal um schneller arbeiten zu können
      SetLength(Result, ALen);
    end;
    SetString(Result[Index], F, P - F);
    Inc(Index);
    if P^ <> #0 then
      Inc(P, SepLen);
  end;
  if Index < ALen then
    SetLength(Result, Index); // wirkliche Länge festlegen
end;

end.

