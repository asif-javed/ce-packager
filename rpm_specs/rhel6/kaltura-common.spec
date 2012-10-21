%define prefix /opt/kaltura
%define kaltura_user kaltura
%define kaltura_uid 1200
Name: kaltura-common
Version: 5.0.0 
ExclusiveArch: i686 x86_64 i386 amd64 
Release: 2%{?dist}
Summary: Kaltura's video platform is designed to help you create value with video. 

Group: Applications/Multimedia
License: GPLv3+
URL: http://kaltura.com
Source0: http://cdnbakmi.kaltura.com/content/files/OnPrem_v5.0_Linux_Install.tar.gz
Source1: kaltura-httpd.conf
Source2: kaltura-logrotate
Source3: kaltura-sphinx.conf
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

#BuildRequires: php-cli
Requires: httpd php-cli php php-gd php-mysql php-pdo php-soap php-pear php-xml php-xmlrpc php-mbstring php-cli php-pecl-memcache php-pecl-apc php-imap php-pear mysql-server memcached mediainfo
%description
Kaltura's video platform is designed to help you create value with video.
With Kaltura's wide range of features and powerful tools you'll quickly expand your audience, generate revenue, and create more effective digital communications.

%prep
%setup -qn Kaltura-TM-v%{version} 

%build

%install
mkdir -p $RPM_BUILD_ROOT%{prefix}/log/sphinx \
	$RPM_BUILD_ROOT%{prefix}/var/sphinx/data \
	$RPM_BUILD_ROOT%{prefix}/cache \
	$RPM_BUILD_ROOT%{prefix}/app \
	$RPM_BUILD_ROOT%{prefix}/bin \
	$RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d \
	$RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d

for i in package/app/app/configurations/*;do 
	sed -i 's^@APP_DIR@^%{prefix}^g' $i/*
	sed -i 's^@LOG_DIR@^%{prefix}/log^g' $i/*
	sed -i 's^@TMP_DIR@^%{prefix}/tmp^g' $i/*
done
rm package/app/app/configurations/apache/httpd.conf
mv package/app/app/configurations $RPM_BUILD_ROOT%{prefix}/app/
mv package/app/app/start $RPM_BUILD_ROOT%{prefix}/
mv package/app/app/infra $RPM_BUILD_ROOT%{prefix}/app/
mv package/app/app/generator $RPM_BUILD_ROOT%{prefix}/app/
#mv package/app $RPM_BUILD_ROOT%{prefix}/app
mv package/app/app/OLD_PATHS $RPM_BUILD_ROOT%{prefix}/app/
mv package/app/app/admin_console $RPM_BUILD_ROOT%{prefix}/app/
mv package/app/app/alpha $RPM_BUILD_ROOT%{prefix}/app
mv package/app/web $RPM_BUILD_ROOT%{prefix}/
mv package/app/html5	$RPM_BUILD_ROOT%{prefix}/
mv package/app/apps $RPM_BUILD_ROOT%{prefix}/
mv package/app/dwh $RPM_BUILD_ROOT%{prefix}/
if [ %{_arch} = 'i386' -o %{_arch} = 'i686' ];then
	mv package/bin/linux/32bit/* $RPM_BUILD_ROOT%{prefix}/bin
else
	mv package/bin/linux/64bit/* $RPM_BUILD_ROOT%{prefix}/bin
fi
rm $RPM_BUILD_ROOT%{prefix}/bin/mediainfo
cd $RPM_BUILD_ROOT%{prefix}/bin
chmod +x run/*.sh $RPM_BUILD_ROOT%{prefix}/bin/sphinx/searchd 
cd -
install -m 644 $RPM_SOURCE_DIR/kaltura-logrotate $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/kaltura
install -m 644 $RPM_SOURCE_DIR/kaltura-httpd.conf $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/kaltura.conf
install -m 644 $RPM_SOURCE_DIR/kaltura-sphinx.conf $RPM_BUILD_ROOT%{prefix}/app/configurations/sphinx/
%pre
# add kaltura user
getent group %{kaltura_user} >/dev/null || groupadd -g 1200 -r %{kaltura_user}
getent passwd %{kaltura_user} >/dev/null || \
  useradd -r -u %{kaltura_uid} -g %{kaltura_user}  -s /sbin/nologin \
    -d %{contentdir} -c "Kaltura" %{kaltura_user}
exit 0

%clean
#rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)

%doc INSTALLATION.txt KALTURA-ON-PREM-EVALUATION-AGREEMENT.txt
%dir %{prefix}
%{prefix}
%config (noreplace)%{prefix}/app/configurations/*
%config (noreplace)%{_sysconfdir}/httpd/conf.d/kaltura.conf
%config %{_sysconfdir}/logrotate.d/kaltura
%defattr(-,apache,root,-)
%{prefix}/log
%{prefix}/cache

%post
cd %{prefix}/bin
ln -sf %{prefix}/bin/run/run-* .
cd -
/etc/init.d/httpd configtest
if [ $? -eq 0 ];then
	service httpd reload
else
	echo "\n**There seem to be a problem with your Apache configuration, please fix and restart**"
fi

%changelog
* Tue Feb 15 2012 Jess Portnoy - 5.0.0-1
- Initial release
