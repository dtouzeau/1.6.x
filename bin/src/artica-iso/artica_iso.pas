program artica_iso;

{$mode objfpc}{$H+}

uses
  Classes, SysUtils,debian_class,unix
  { you can add units after this };

  var deb:tdebian;
  var interfaces:Tstringlist;
begin



if not FileExists('/etc/artica-postfix/FROM_ISO') then begin
   writeln('/etc/artica-postfix/FROM_ISO no such file...');
   halt(0);
end;

deb:=tdebian.Create;
deb.ARTICA_CD_SOURCES_LIST();
deb.remove_bip();
deb.linuxlogo();


if FileExists('/etc/artica-postfix/WEBSTATS_APPLIANCE') then begin
   if not FIleExists('/etc/artica-postfix/ARTICA_ISO_SYSLOG_CONFIGURED') then begin
      fpsystem('/usr/bin/php5 /usr/share/artica-postfix/exec.syslog-engine.php --build-server >/dev/null 2>&1');
      fpsystem('/bin/touch /etc/artica-postfix/ARTICA_ISO_SYSLOG_CONFIGURED');
   end;
end;

if FIleExists('/etc/artica-postfix/ARTICA_ISO.lock') then begin
   if deb.FILE_TIME_BETWEEN_MIN('/etc/artica-postfix/ARTICA_ISO.lock') < 5 then begin
      writeln('/etc/artica-postfix/ARTICA_ISO.lock need wait 5Mn');
      halt(0);
   end;
end;

fpsystem('/bin/touch /etc/artica-postfix/ARTICA_ISO.lock');
fpsystem('/bin/chmod 755 /etc');
fpsystem('/bin/chmod 755 /usr');
fpsystem('/bin/chmod 755 /usr/lib');
if DirectoryExists('/usr/lib/openssl') then  fpsystem('/bin/chmod 755 /usr/lib/openssl');
fpsystem('/bin/chmod 644 /etc/ssh');
fpsystem('/bin/chmod 600 /etc/ssh/*');
ForceDirectories('/opt/artica/var/rrd/yorel');

if FileExists('/etc/artica-postfix/artica-iso-first-reboot') then begin
       if not FileExists('/etc/artica-postfix/artica-iso-make-launched') then begin
          fpsystem('/bin/touch /etc/artica-postfix/artica-iso-setup-launched');
       end;
end;

    if FileExists('/etc/php5/cli/conf.d/ming.ini') then fpsystem('/bin/rm /etc/php5/cli/conf.d/ming.ini');

    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    fpsystem('clear');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');
    writeln('');
    writeln('artica-cd... Please wait, do nothing... Artica will configure your server');
    writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');


    if FileExists('/home/artica/php-engine.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING PHP ENGINE.....');
          ForceDirectories('/opt/artica-agent');
          fpsystem('/bin/tar -xf /home/artica/php-engine.tar.gz -C /opt/artica-agent >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/php-engine.tar.gz /home/artica/php-engine.tar.gz.bak');
          fpsystem('/bin/chmod -R 755 /opt/artica-agent');
          fpsystem('/bin/rm -rf /opt/artica-agent/share/artica-agent');
       end;

      fpsystem('/bin/echo "slapd hold" | /usr/bin/dpkg --set-selections');


if FileExists('/etc/artica-postfix/KASPER_INSTALL') then begin
     fpsystem('/usr/share/artica-postfix/bin/artica-make APP_KAS3');
     fpsystem('/usr/share/artica-postfix/bin/artica-make APP_KAVMILTER');
     fpsystem('/bin/rm -f /etc/artica-postfix/KASPER_INSTALL');
end;
    writeln('Artica-iso: PLEASE WAIT.... SCANNING SOFTWARES');
    if FileExists('/home/artica/artica-agent.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Artica-agent.....');
          ForceDirectories('/opt/artica-agent');
          fpsystem('/bin/tar -xvf /home/artica/artica-agent.tar.gz -C /opt/artica-agent/ >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/artica-agent.tar.gz /home/artica/artica-agent.tar.gz.old');
          fpsystem('/bin/chmod -R 755 /opt/artica-agent');
       end;

       if FileExists('/home/artica/packages/ZARAFA/zarafa.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING ZARAFA.....');
          fpsystem('/bin/tar -xf /home/artica/packages/ZARAFA/zarafa.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/touch /etc/artica-postfix/ZARAFA_APPLIANCE');
          fpsystem('/bin/touch /etc/artica-postfix/NO_ZARAFA_UPGRADE_TO_7');
          fpsystem('/bin/touch /etc/artica-postfix/ZARFA_FIRST_INSTALL');
          fpsystem('/bin/rm /home/artica/packages/ZARAFA/zarafa.tar.gz');
       end;

       if FileExists('/home/artica/packages/ZARAFA/zarafa-web-app.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING ZARAFA WEBAPP.....');
          fpsystem('/bin/tar -xf /home/artica/packages/ZARAFA/zarafa-web-app.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/ZARAFA/zarafa-web-app.tar.gz /home/artica/packages/ZARAFA/zarafa-web-app.tar.gz.old');
       end;

       if FileExists('/home/artica/packages/ZARAFA/webapp.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING ZARAFA WEBAPP.....');
          ForceDirectories('/usr/share/zarafa-webapp');
          fpsystem('/bin/tar -xf /home/artica/packages/ZARAFA/webapp.tar.gz -C /usr/share/zarafa-webapp/ >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/ZARAFA/webapp.tar.gz /home/artica/packages/ZARAFA/webapp.tar.gz.old');
       end;


       if FileExists('/home/artica/packages/ZARAFA/webappplugins.tar.gz') then begin
           writeln('Artica ISO: PLEASE WAIT.... INSTALLING ZARAFA WEBAPP plugins.....');
          ForceDirectories('/usr/share/zarafa-webapp/plugins');
          fpsystem('/bin/tar -xf /home/artica/packages/ZARAFA/webappplugins.tar.gz -C /usr/share/zarafa-webapp/plugins/ >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/ZARAFA/webappplugins.tar.gz /home/artica/packages/ZARAFA/webappplugins.tar.gz.old');
       end;


        if FileExists('/home/artica/packages/ZARAFA/zpush.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING ZARAFA ZPUSH.....');
          forceDirectories('/usr/share/z-push');
          fpsystem('/bin/tar -xf /home/artica/packages/ZARAFA/zpush.tar.gz -C /usr/share/z-push/ >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/ZARAFA/zpush.tar.gz /home/artica/packages/ZARAFA/zpush.tar.gz.old');
       end;

       if FileExists('/home/artica/packages/samba.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING SAMBA WEBAPP.....');
          fpsystem('/bin/tar -xf /home/artica/packages/samba.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/samba.tar.gz /home/artica/packages/samba.tar.gz.old');
          writeln('Artica ISO: PLEASE WAIT.... Creating winbindd_priv group.....');
          fpsystem('/usr/sbin/groupadd winbindd_priv >/dev/null 2>&1');
          writeln('Artica ISO: PLEASE WAIT.... Set privileges.....');
          forceDirectories('/etc/samba');
          forceDirectories('/var/log/samba');
          forceDirectories('/var/run/samba');
          if not FileExists('/etc/printcap') then fpsystem('/bin/touch /etc/printcap');
          fpsystem('/usr/bin/php5 /usr/share/artica-postfix/exec.samba.init.php');
          fpsystem('/bin/echo "samba hold" | /usr/bin/dpkg --set-selections');
          fpsystem('/bin/echo "winbind hold" | /usr/bin/dpkg --set-selections');
          fpsystem('/bin/echo "samba-common hold" | /usr/bin/dpkg --set-selections');
       end;

       fpsystem('/bin/echo "exim4-base hold" | /usr/bin/dpkg --set-selections');
       fpsystem('/bin/echo "exim4-daemon-light hold" | /usr/bin/dpkg --set-selections');

       if FileExists('/home/artica/packages/glusterfs.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING GLUSTERFS.....');
          fpsystem('/bin/tar -xf /home/artica/packages/glusterfs.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/glusterfs.tar.gz /home/artica/packages/glusterfs.tar.gz.old');
          fpsystem('/usr/bin/php5 /usr/share/artica-postfix/exec.gluster.init.d.php');
       end;


       if FileExists('/home/artica/packages/pdns.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING PowerDNS.....');
          fpsystem('/bin/tar -xf /home/artica/packages/pdns.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/pdns.tar.gz /home/artica/packages/pdns.tar.gz.old');
          if FileExists('/usr/sbin/dnsmasq') then fpsystem('aptitude remove dnmasq -y -q');

       end;

       if FileExists('/home/artica/packages/roundcube.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Roundcube.....');
          ForceDirectories('/usr/share/roundcube');
          fpsystem('/bin/tar -xf /home/artica/packages/roundcube.tar.gz -C /usr/share/roundcube/ >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/roundcube.tar.gz /home/artica/packages/roundcube.tar.gz.old');
       end;

        if FileExists('/home/artica/packages/articasql.tar.gz') then begin
            writeln('Artica ISO: PLEASE WAIT.... INSTALLING Artica MySQL Categories Statistics.....');
           forceDirectories('/opt/articatech');
           fpsystem('/bin/tar -xf /home/artica/packages/articasql.tar.gz -C /opt/articatech/ >/dev/null 2>&1');
           fpsystem('/bin/rm /home/artica/packages/articasql.tar.gz');
        end;

        if FileExists('/home/artica/packages/squid-db.tar.gz') then begin
            writeln('Artica ISO: PLEASE WAIT.... INSTALLING Artica MySQL Engine Statistics.....');
            forceDirectories('/opt/squidsql');
            fpsystem('/bin/tar -xf /home/artica/packages/squid-db.tar.gz -C /opt/squidsql/ >/dev/null 2>&1');
            fpsystem('/bin/mv /home/artica/packages/squid-db.tar.gz /home/artica/packages/squid-db.tar.gz.old');
            fpsystem('/usr/bin/php5 /usr/share/artica-postfix/exec.squid-db.php --init >/dev/null 2>&1');
        end;

       if FIleExists('/usr/bin/apt-get') then begin
          writeln('Artica ISO: PLEASE WAIT.... REMOVING EXIM IF IT EXISTS...');
          if FileExists('/usr/sbin/exim') then begin
             fpsystem('/usr/bin/apt-get --purge --yes --force-yes --remove exim4* >/dev/null 2>&1');
             fpsystem('/bin/echo "exim4 hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
             fpsystem('/bin/echo "xmail hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          end;
          writeln('Artica ISO: PLEASE WAIT.... REMOVING SQUID/POSTFIX/SAMBA FROM DEBIAN REPOSITORY...');
          fpsystem('/bin/echo "squid hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          fpsystem('/bin/echo "squid3 hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          fpsystem('/bin/echo "squid3-common hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          fpsystem('/bin/echo "postfix hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          fpsystem('/bin/echo "samba hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          fpsystem('/bin/echo "winbind hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
          fpsystem('/bin/echo "samba-common hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1');
       end;

       if FileExists('/home/artica/packages/klmsui.deb') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Kaspersky Web console For Mail security 8.x.....');
          fpsystem('/usr/bin/dpkg -i /home/artica/packages/klmsui.deb >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/klmsui.deb /home/artica/packages/klmsui.deb.old');
       end;

      if FileExists('/home/artica/packages/updatev2.tar.gz') then begin
         writeln('Artica ISO: PLEASE WAIT.... INSTALLING Kaspersky Update Utility.....');
         forceDirectories('/home/artica/packages/updatev2');
         fpsystem('/bin/tar -xf /home/artica/packages/updatev2.tar.gz -C /home/artica/packages/updatev2/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/UpdateUtility-Console /usr/sbin/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/UpdateUtility-Gui /usr/sbin/');
         ForceDirectories('/etc/UpdateUtility/lib');
         fpsystem('/bin/cp /home/artica/packages/updatev2/important_legal_notice.txt /etc/UpdateUtility/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/license.txt /etc/UpdateUtility/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/locale.ini /etc/UpdateUtility/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/ReleaseNotes.txt /etc/UpdateUtility/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/updater.ini /etc/UpdateUtility/');
         fpsystem('/bin/cp /home/artica/packages/updatev2/updater.xml /etc/UpdateUtility/');
         fpsystem('/bin/cp -rf /home/artica/packages/updatev2/lib/*  /etc/UpdateUtility/lib/');
         fpsystem('/home/artica/packages/updatev2.tar.gz /home/artica/packages/updatev2.tar.old');
         Writeln('Done...');
      end;


        if FileExists('/home/artica/packages/dansguardian2.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Dansguardian.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/dansguardian2.tar.gz -C /');
          fpsystem('/bin/mv -f /home/artica/packages/dansguardian2.tar.gz /home/artica/dansguardian2.tar.gz.old');
      end;



       if FileExists('/home/artica/packages/postfix.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... REMOVING Postfix.....');
          if FileExists('/usr/bin/apt-get') then fpsystem('/usr/bin/apt-get remove postfix* -y ');
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Postfix.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/postfix.tar.gz -C /');
          fpsystem('/bin/rm /etc/postfix/main.cf');
          fpsystem('/bin/rm /etc/postfix/master.cf');
          fpsystem('/bin/cp /etc/postfix/main.cf.default /etc/postfix/main.cf');
          fpsystem('/bin/cp /usr/share/artica-postfix/bin/install/master.cf.default /etc/postfix/master.cf');
          fpsystem('/bin/touch /etc/postfix/master.cf');
          fpsystem('/bin/mv -f /home/artica/packages/postfix.tar.gz /home/artica/packages/postfix.tar.gz.old');
          fpsystem('/bin/echo `/bin/hostname -f` >/etc/mailname');
          fpsystem('/usr/sbin/adduser postfix --disabled-password');
          fpsystem('/usr/sbin/groupadd postdrop -f');
          fpsystem('/usr/sbin/postfix set-permissions');
          fpsystem('/usr/sbin/update-rc.d -f exim4 remove >/dev/null 2>&1');
          forceDirectories('/var/spool/postfix');
          fpsystem('/bin/chown -R postfix:postfix /var/spool/postfix');
          fpsystem('/bin/chown -R root:root /var/spool/postfix/etc');
          fpsystem('/bin/chown root:root /var/spool/postfix');
          fpsystem('/bin/chown -R postfix:postfix /var/lib/postfix');
          fpsystem('/bin/chown -R root:root /var/spool/postfix/lib');
          fpsystem('/bin/chown -R root:root /var/spool/postfix/usr');
          fpsystem('/bin/echo "postfix hold" | /usr/bin/dpkg --set-selections');
          writeln('Artica ISO: PLEASE WAIT.... Configuring Cyrus-imap if it exists.....');
          fpsystem('/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus >/dev/null 2>&1');

      end;


       if FileExists('/home/artica/packages/klms.deb') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Kaspersky Mail security 8.x.....');
          fpsystem('/usr/bin/dpkg --force-architecture -i /home/artica/packages/klms.deb');
          fpsystem('/bin/mv /home/artica/packages/klms.deb /home/artica/packages/klms.deb.old');
          fpsystem('/opt/kaspersky/klms/bin/klms-setup.pl --auto-install=/usr/share/artica-postfix/bin/install/klms.setup');
       end;

       if FileExists('/home/artica/packages/ufdbguard.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Dansguardian.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/ufdbguard.tar.gz -C /');
          fpsystem('/bin/mv -f /home/artica/packages/ufdbguard.tar.gz /home/artica/ufdbguard.tar.gz.old');
       end;

       if FileExists('/home/artica/packages/squid32.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING SQUID 3.2x.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/squid32.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv -f /home/artica/packages/squid32.tar.gz /home/artica/squid32.tar.gz.old');
          forceDirectories('/var/run/squid');
          fpsystem('/bin/chmod 0777 /var/run/squid');

    end;

    fpsystem('/bin/echo "root:artica" | /usr/sbin/chpasswd 2>&1');

       if FileExists('/home/artica/packages/metascanner.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Kaspersky Metascanner.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/metascanner.tar.gz -C /');
          fpsystem('/bin/mv -f /home/artica/packages/metascanner.tar.gz /home/artica/metascanner.tar.gz.old');
          if FileExists('/opt/kaspersky/khse/libexec/libframework.so') then fpsystem('/bin/cp /opt/kaspersky/khse/libexec/libframework.so /lib/libframework.so');
          if FileExists('/opt/kaspersky/khse/libexec/libyaml-cpp.so.0.2') then fpsystem('/bin/cp /opt/kaspersky/khse/libexec/libyaml-cpp.so.0.2 /lib/libyaml-cpp.so.0.2');
    end;

       if FileExists('/home/artica/packages/c-icap.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING C-ICAP.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/c-icap.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv -f /home/artica/packages/c-icap.tar.gz /home/artica/c-icap.tar.gz.old');
    end;

       if FileExists('/home/artica/packages/ftpunivtlse1fr.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING Webfilter databases.....');
          ForceDirectories('/var/lib/ftpunivtlse1fr');
          fpsystem('/bin/tar -xvf /home/artica/packages/ftpunivtlse1fr.tar.gz -C /var/lib/ftpunivtlse1fr/ >/dev/null 2>&1');
          fpsystem('/bin/mv -f /home/artica/packages/ftpunivtlse1fr.tar.gz /home/artica/ftpunivtlse1fr.tar.gz.old');
    end;


       if FileExists('/home/artica/packages/mskutils.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING mskutils.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/mskutils.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv -f /home/artica/packages/mskutils.tar.gz /home/artica/mskutils.tar.gz.old');
    end;


       if FileExists('/home/artica/squid32.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING squid 3.2x.....');
          fpsystem('/bin/tar -xvf /home/artica/squid32.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv -f /home/artica/squid32.tar.gz /home/artica/squid32.tar.gz.old');

       end;

       if FileExists('/home/artica/ufdbguard.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING UfdbGuard.....');
          fpsystem('/bin/tar -xvf /home/artica/ufdbguard.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv -f /home/artica/ufdbguard.tar.gz /home/artica/ufdbguard.tar.gz.bak');
       end;




       if FileExists('/home/artica/packages/netatalk.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING NETATALK.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/netatalk.tar.gz -C /');
          fpsystem('/bin/mv -f /home/artica/packages/netatalk.tar.gz /home/artica/netatalk.tar.gz.old');
          fpsystem('/bin/echo "root:artica" | /usr/sbin/chpasswd 2>&1');
    end;



       if FileExists('/home/artica/packages/ufdbguard.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING UFDBGUARD.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/ufdbguard.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/ufdbguard.tar.gz /home/artica/ufdbguard.tar.gz.old');
          writeln('Artica ISO: PLEASE WAIT.... CREATING CONFIGURATION.....');
    end;;

    if FileExists('/home/artica/packages/kav4proxy-5.5-62.tar.gz') then begin
       writeln('Artica ISO: PLEASE WAIT.... INSTALLING KAV4PROXY.....');
       fpsystem('/usr/share/artica-postfix/bin/artica-make APP_KAV4PROXY >/dev/null 2>&1');
    end;

       if FileExists('/home/artica/packages/kav4proxy-5.5-80.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING KAV4PROXY.....');
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_KAV4PROXY >/dev/null 2>&1');
    end;

       if FileExists('/home/artica/packages/crossroads.tar.gz') then begin
          writeln('Artica ISO: PLEASE WAIT.... INSTALLING LOAD BALANCER.....');
          fpsystem('/bin/tar -xvf /home/artica/packages/crossroads.tar.gz -C / >/dev/null 2>&1');
          fpsystem('/bin/mv /home/artica/packages/crossroads.tar.gz /home/artica/crossroads.tar.gz.old');
          writeln('Artica ISO: PLEASE WAIT.... CREATING CONFIGURATION.....');
    end;

       if not FileExists('/bin/login.old') then begin
            fpsystem('/bin/mv /bin/login /bin/login.old');
            if FIleExists('/usr/share/artica-postfix/bin/artica-logon') then fpsystem('/bin/ln -s /usr/share/artica-postfix/bin/artica-logon /bin/login');
            if FIleExists('/opt/artica-agent/bin/artica-logon') then fpsystem('/bin/ln -s /opt/artica-agent/bin/artica-logon /bin/login');
            fpsystem('dpkg-divert --divert /bin/login.old /bin/login');
            fpsystem('/bin/chmod 777 /bin/login');
            if FIleExists('/usr/share/artica-postfix/bin/artica-logon') then fpsystem('/bin/chmod 777 /usr/share/artica-postfix/bin/artica-logon');
            if FIleExists('/opt/artica-agent/bin/artica-logon') then fpsystem('/bin/chmod 777 /opt/artica-agent/bin/artica-logon');
        end;

        if FIleExists('/opt/artica-agent/usr/share/artica-agent/starter.php') then fpsystem('/opt/artica-agent/usr/share/artica-agent/starter.php');

        if DirectoryExists('/usr/share/artica-postfix') then begin
           fpsystem('/usr/bin/nohup /bin/chown www-data:www-data /usr/share/artica-postfix/* >/dev/null 2>&1 &');
           fpsystem('/usr/bin/nohup /bin/chown -R www-data:www-data /usr/share/artica-postfix >/dev/null 2>&1 &');
        end;

if not FileExists('/etc/artica-postfix/artica-as-rebooted') then fpsystem('/bin/rm -f /etc/artica-postfix/artica-iso-first-reboot');
if not FileExists('/etc/artica-postfix/artica-iso-first-reboot') then begin

    interfaces:=Tstringlist.Create;
    interfaces.Add('auto lo');
    interfaces.Add('iface lo inet loopback');
    interfaces.Add('# The primary network interface');
    interfaces.Add('iface eth0 inet dhcp');
    interfaces.Add('');
    try
        interfaces.SaveToFile('/etc/network/interfaces');
    finally
    end;

    writeln('Checking sources.list...');
    if FileExists('/usr/share/artica-postfix/exec.apt-get.php') then fpsystem('/usr/bin/php5 /usr/share/artica-postfix/exec.apt-get.php --sources-list');

    if FileExists('/etc/init.d/lighttpd') then begin
         writeln('artica-cd... removing lighttpd original instance...');
         fpsystem('/etc/init.d/lighttpd stop');
         fpsystem('update-rc.d -f lighttpd remove >/dev/null 2>&1');
         fpsystem('/bin/mv -f /etc/lighttpd/lighttpd.conf /etc/lighttpd/lighttpd.conf.org');
         fpsystem('/bin/touch  /etc/lighttpd/init.d');
         fpsystem('dpkg-divert --divert /etc/lighttpd/lighttpd.conf.org /etc/lighttpd/lighttpd.conf >/dev/null 2>&1');
         fpsystem('dpkg-divert --divert /etc/lighttpd/init.d /etc/init.d/lighttpd >/dev/null 2>&1');
         writeln('artica-cd... removing lighttpd original instance done...');
    end;
     fpsystem('clear');
    writeln('************************************************************************');
    writeln('************************************************************************');
    writeln('');
    writeln('artica-cd...0% Please wait, do nothing... the server will be restarted....');
     writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');

    fpsystem('/bin/echo "root:artica" | /usr/sbin/chpasswd 2>&1');
    writeln('artica-cd... Creating Artica configuration by process1, please wait...');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    fpsystem('clear');
    writeln('************************************************************************');
    writeln('************************************************************************');
    writeln('');
    writeln('artica-cd...50% Please wait, do nothing... the server will be restarted....');
     writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    fpsystem('clear');
    writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');
    writeln('');
    writeln('artica-cd...80% Please wait, do nothing... the server will be restarted....');
     writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');

    fpsystem('/usr/share/artica-postfix/bin/process1 --force --yes-from-iso >/dev/null 2>&1');
    writeln('artica-cd... remove init boot');
    fpsystem('update-rc.d -f artica-cd remove >/dev/null 2>&1');
    fpsystem('/bin/rm -f /etc/init.d/artica-cd >/dev/null 2>&1');
    fpsystem('/bin/rm -f /etc/cron.d/artica-boot-first >/dev/null 2>&1');
    fpsystem('/bin/rm -f /etc/artica-postfix/ARTICA_ISO.lock');
    fpsystem('/bin/touch /etc/artica-postfix/artica-iso-first-reboot');
    fpsystem('/bin/touch /etc/artica-postfix/artica-as-rebooted');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    writeln('');
    fpsystem('clear');
    writeln('************************************************************************');
    writeln('************************************************************************');
    writeln('');
    writeln('artica-cd...99% Please wait, press [ENTER] KEY. the server will be restarted....');
     writeln('');
    writeln('************************************************************************');
    writeln('************************************************************************');
    fpsystem('clear');
    writeln('artica-cd... System will reboot....');
    fpsystem('reboot');
end else begin
     if FileExists('/etc/init.d/artica-cd') then begin
        fpsystem('update-rc.d -f artica-cd remove >/dev/null 2>&1');
        fpsystem('/bin/rm -f /etc/init.d/artica-cd');
    end;

end;

halt(0);


end.

