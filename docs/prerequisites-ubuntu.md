# Install Prerequisites on a Stock Ubuntu 12.04 LTS Server Installation

    sudo apt-get update && sudo apt-get upgrade
    sudo apt-get install openssh-server git dos2unix php5 php5-cli php5-mysql \
							apache2 curl php-apc php5-gd php5-curl php5-xsl \
							php5-memcache mysql-server memcached mailutils \
							imagemagick libjpeg62 ffmpeg build-essential \
							unzip

## Install Oracle JRE

Java's new license does not allow it to be distributed in Ubuntu's apt
repoitories, so we recommend installing via a ppa that will download the
installer from oracle and install, using a ppa helps stay up to date
with security and bug fixes.  See
http://www.webupd8.org/2012/01/install-oracle-java-jdk-7-in-ubuntu-via.html 
and https://help.ubuntu.com/community/Java for more information

    sudo apt-get install python-software-properties
    sudo add-apt-repository ppa:webupd8team/java
    sudo apt-get update
    sudo apt-get install oracle-java7-installer

## Install MySQL Shared Compatibility for Sphinx

    cd ~/
	sudo apt-get install alien
	wget http://cdn.mysql.com/Downloads/MySQL-5.5/MySQL-shared-compat-5.5.28-1.linux2.6.x86_64.rpm
    sudo alien -i MySQL-shared-compat-5.5.28-1.linux2.6.x86_64.rpm
	sudo ln -s /usr/lib64/* /usr/lib/x86_64-linux-gnu/  //put the libs where sphinx can find them

## Install Tiff 3.x for FFMPEG

    cd ~/
	wget ftp://ftp.remotesensing.org/pub/libtiff/tiff-3.9.7.zip
	unzip tiff-3.9.7.zip
	cd tiff-3.9.7
	./configure
	make
	sudo make install
	sudo ln -s /usr/local/lib/libtiff* /usr/lib/x86_64-linux-gnu/

# Configure MySQL and PHP

    cd ~/
	cp /etc/php5/apache2/php.ini php.ini.apache.backup
    sed -e "s/^request_order = \"GP\"/request_order = \"CGP\"/g" \
        /etc/php5/apache2/php.ini > /tmp/php.ini.configured && \
	sudo cp /tmp/php.ini.configured /etc/php5/apache2/php.ini
    cp /etc/php5/cli/php.ini php.ini.cli.backup
    sed -e "s/^request_order = \"GP\"/request_order = \"CGP\"/g" \
        /etc/php5/cli/php.ini > /tmp/php.ini.configured && \
	sudo cp /tmp/php.ini.configured /etc/php5/cli/php.ini
    cp /etc/mysql/my.cnf my.cnf.backup
    sed -e "s/^thread_stack\t\t= 192K/thread_stack\t\t= 256K/g" \
        /etc/mysql/my.cnf > /tmp/my.cnf.configured && \
        sudo cp /tmp/my.cnf.configured /etc/mysql/my.cnf
    sed -e "s/^\[mysqld\]/\[mysqld\]\nlower_case_table_names = 1/g" \
        /etc/mysql/my.cnf > /tmp/my.cnf.configured && \
        sudo cp /tmp/my.cnf.configured /etc/mysql/my.cnf
    sudo service mysql restart

# Configure Apache

	sudo a2enmod rewrite headers expires filter proxy
    sudo service apache2 restart

