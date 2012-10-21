Name:           mediainfo
Version:        0.7.28
Release:        1%{?dist}
Summary:        Utility to provide infos a video or audio file

Group:          Applications/Multimedia
License:        LGPLv3
URL:            http://mediainfo.sourceforge.net
Source0:        http://downloads.mediainfo.sourceforge.net/mediainfo/mediainfo_%{version}.tar.bz2

BuildRequires:  libmediainfo-devel
#BuildRequires:  wxGTK-devel
BuildRequires:  autoconf automake libtool

%description
%{summary}

%package gui
Summary:        Utility to provide infos a video or audio file
Group:          Applications/Multimedia

%description gui
%{summary}

%prep
%setup -c -T -n %{name}-%{version}
%setup -q -T -D -n %{name}-%{version} -a0

# Fixup broken permissions
find MediaInfo -type f -exec chmod a-x {} \;

#pushd MediaInfo/Project/GNU/CLI > /dev/null
#autoreconf -fi
#popd > /dev/null

#pushd MediaInfo/Project/GNU/GUI > /dev/null
#autoreconf -fi
#popd > /dev/null

%build
pushd MediaInfo/Project/GNU/CLI > /dev/null
autoreconf -fi
%configure
#popd > /dev/null

#pushd MediaInfo/Project/GNU/GUI > /dev/null
#%configure
#popd > /dev/null


%install
pushd MediaInfo/Project/GNU/CLI > /dev/null
make install DESTDIR=$RPM_BUILD_ROOT
popd > /dev/null
#pushd MediaInfo/Project/GNU/GUI > /dev/null
#make install DESTDIR=$RPM_BUILD_ROOT
#popd > /dev/null


%files
%defattr(-,root,root,-)
%doc MediaInfo/License.html
%{_bindir}/mediainfo

#%files gui
#%defattr(-,root,root,-)
#%doc MediaInfo/License.html
#%{_bindir}/mediainfo-gui

%changelog
* Sun Feb 19 2012 Jess Portnoy <jess.portnoy@kaltura.com> 0.7.28-1
- Build for Kaltura's platform

* Mon Jan 09 2012 Ralf Corsépius <corsepiu@fedoraproject.org> - 0.7.52-0.20120109.0
- Spec file cleanup.
- Upstream update.

* Sun Mar 14 2010 Ralf Corsépius <corsepiu@fedoraproject.org> - 0.7.29-0.20100314.0
- Initial Fedora package.
