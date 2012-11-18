# Install Prerequisites on a Stock CentOS 6.3 Minimal Installation

    sudo yum update
	sudo yum install git wget dos2unix php-cli php-mysql php-gd \
					mysql-server memcached httpd mailx ImageMagick \
					php-pecl-apc php-pecl-memcache php-xml
	sudo yum --enablerepo=centosplus install mod_php

*If you are installing on Amazon Linux you will also need to:*

    sudo yum install mysql51-libs binutils
	// if you created your instance with an increased boot EBS
	sudo resize2fs /dev/xvda1

# Download and install the latest JRE from Oracle

http://www.oracle.com/technetwork/java/javase/downloads/index.html

    sudo yum install jre-7u9-linux-x64.rpm

# Disable SE Linux

Edit `/etc/selinux/config` and change `SELINUX=enforcing` to `SELINUX=disabled`

Then reboot the system.

# Configure MySQL and PHP

    cp /etc/php.ini php.ini.apache.backup
    sed -e "s/^request_order = \"GP\"/request_order = \"CGP\"/g" \
        /etc/php.ini > /tmp/php.ini.configured && \
	sudo cp /tmp/php.ini.configured /etc/php.ini
    cp /etc/my.cnf my.cnf.backup
    sed -e "s/^thread_stack\t\t= 192K/thread_stack\t\t= 256K/g" \
        /etc/my.cnf > /tmp/my.cnf.configured && \
        sudo cp /tmp/my.cnf.configured /etc/my.cnf
    sed -e "s/^\[mysqld\]/\[mysqld\]\nlower_case_table_names = 1/g" \
        /etc/my.cnf > /tmp/my.cnf.configured && \
        sudo cp /tmp/my.cnf.configured /etc/my.cnf
    sudo service mysqld restart

# Make Services Start at Boot

    sudo chkconfig --level 2345 mysqld on
	sudo service mysqld start
    sudo chkconfig --level 2345 httpd on
	sudo service httpd start

# Turn off iptables

    sudo service iptables stop
	sudo chkconfig iptables off

