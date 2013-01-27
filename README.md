# Kaltura CE Packager

This tool is used to clone the latest Kaltura code from the Kaltura CE github repositories, build an install package, and install a working Kaltura environment.

The `packaging/package.php` script uses the `packaging/manifest.ini` file to select which components are
required for installation and then packages those into a php based installer from the git submodules included in this repository.

The installer package, once created, is used to install and configure a Kaltura server.

To see the entire process of obtaining prerequisites on CentOS/Amazon
Linux, packaging an installer, and installing Kaltura CE, the
[screencasts can be viewed here](http://kaltura.github.com/ce-packager/docs/screencasts.html).

# System Requirements

The following prerequisites are required for installing and for running the Kaltura CE on a Linux server.
Kaltura is developed and tested under the latest CentOS and RedHat predominantely, the installer provides an Ubuntu setup too.
A Windows based installation is theoretically possible using XAMPP and CygWin, but will most likely require a lot of messing around.

## Hardware and Software Requirements

### Minimum Hardware requirements

* 2GHz+ Dual-Core Processor
* 4GB+ RAM
* 7,200 rpm disk with minimum 20GB available space (and additionally
  according to usage expectations) - Preferably NAS for easier scaling
  up)

It is recommended to install Kaltura on Ubuntu or CentOS Linux. You may install Kaltura on other Linux
distributions, however, these installations are currently not supported
by Kaltura.

### Minimum Software Prerequisites

* Apache 2.2 or above.
* PHP 5.3 or above.
  * Both php and php-cli must be present.
  * Verify that the following settings within the php.ini file are set on each server (for both php and php-cli):
    * Verify that request_order parameter includes C, G and P (recommended: "CGP")
    * Verify that date.timezone parameter is set to the right timezone.
* MySQL 5.1.37 or above.
  * Verify that mysql server character set is UTF8.
  * The following settings should be added to the my.cnf file

            lower_case_table_names = 1
            thread_stack = 262144
            open_files_limit = 20000

  * **MySQL should be restarted after this adjustment is made.**
* curl and php-curl
* memcached
* ImageMagick
* JRE 1.6 or above
* Pentaho data integration package [(instructions below)](https://github.com/kaltura/ce-packager/blob/falcon/README.md#install-pentaho)
* rsync (Required during installation) 
* Xymon 4.3.7 or above (This is Optional)
* Disable SELinux (Required during installation)
  * Within the /etc/sysconfig/selinux file (if exists on your server) set: `SELINUX=disabled` **Your server should be restarted after this adjustment is made.**

## Prerequisite Installation on Common Platforms

* [CentOS](https://github.com/kaltura/ce-packager/blob/falcon/docs/prerequisites-centos.md) (recommended)
* [Ubuntu](https://github.com/kaltura/ce-packager/blob/falcon/docs/prerequisites-ubuntu.md)

***Please do not try to run Kaltura with less than 4GB of RAM***

## Install Pentaho

	sudo mkdir /usr/local/pentaho
	cd /usr/local/pentaho
	sudo wget http://sourceforge.net/projects/pentaho/files/Data%20Integration/4.2.1-stable/pdi-ce-4.2.1-stable.tar.gz
	sudo tar xzf pdi-ce-4.2.1-stable.tar.gz
	sudo mv data-integration pdi

# Build an Installation Package

	cd ~/
	git clone https://github.com/kaltura/ce-packager.git
	cd ce-packager
	git submodule update --init
	cd packaging
	// The installer REQUIRES a full path, don't use relative paths.
	php package.php /home/<username>/kaltura-installer false CE v6.0.0 dev

# Run the Installation Script

***Please note that you must enter a fully qualified domain name when
asked for your system hostname during installation.  Entering an IP address
when you're asked for the hostname will lead to a broken install.***

***Please note that you must enter Y when asked if you would like to
create a new database.***

	cd ~/kaltura-installer
	sudo php install.php

Once the installation script finishes it will give you instructions for
modifying `/etc/hosts` and Apache virtual host configurations.


# Useful Knowledge Points

## GitHub Development Branches

* `kelev-mirror` - direct mirror of Kaltura R&D SVN trunk
* `master` - unmaintained rebase of git packager commits onto `kelev-mirror`
* `kelev-CE-6.0.1-mirror` - direct mirror of Kaltura R&D SVN `falcon` release tag
* `falcon` - **current maintained release branch** (rebase of git packager commits onto `kelev-CE-6.0.1-mirror`)
* `gemini` - unmaintained rebase of git packager commits onto `kelev-mirror`

The ce-packager repository automatically checks out the latest stable
release version (currently `falcon`) when doing an initial clone.

To rebase latest `falcon` onto new commits to `kelev-CE-6.0.1-mirror`:

    git checkout falcon
    git rebase origin/kelev-CE-6.0.1-mirror

To switch to a new release version or development master, rebase the 
commits beginning from the addition of the README.md on top of the
branch you wish to use and update the submodules to use a similar branch.

**During development, all commits to this repo _must_ be atomic so that
they may be rebased onto SVN mirror branches.**

## Commit Message Format Guidelines

* `cof: #<number> <message>` - A commit adding a feature from the public GitHub tracker
* `coi: #<number> <message>` - A commit fixing an issue from the public GitHub tracker
* `fwr: <message>` - A new Feature Without a formal Request (this feature is not associated with any issue or feature tracker)
* `ps: <message>` - A commit from the Kaltura Professional Services team
* `req: <number> <message>` -  A new feature with it's associated internal feature tracker number
* `qnd: <message>` - Quick and Dirty fix with low code impact, for example: style changes, syntax improvements
* `def: <number <message>` - A defect and it's associated internal issue tracker identifier
* `cnf: <message>` - A change in configuration files

## Commonly Used Server Administration Commands

* Restart Sphinx: `sudo pkill searchd && sudo /opt/kaltura/bin/sphinx/searchd -c /opt/kaltura/app/configurations/sphinx/kaltura.conf`
* Check Batch Jobs Status: 
  * `sudo /opt/kaltura/app/scripts/serviceBatchMgr.sh status`
  * `ps aux | grep batch`

## Log files in Kaltura

* Investigate API Related Problems: `/opt/kaltura/log` 
  * `kaltura_apache_error.log`
  * `kaltura_api_v3.log`
* Investigate Batch Jobs (transcoding, mail, etc.): `/opt/katura/log/batch` 
* Investigate Analytics Related Problems: `/opt/kaltura/dw/logs`
* On production environments, it is a good practice to also set the log verbosity in /opt/kaltura/app/configurations/logger.ini to 5 (errors and warnings) rather than 7 (debug). Debug level logging can quickly fill up the log file
* If the above are not helpful for debugging your problem, you may find additional information in `/var/log/messages` for encoder related problems.
* There may be further information stored in your default Apache error logs

### Grepping and Tailing Errors

`grep "ERR:\|PHP\|trace\|CRIT" /opt/kaltura/log/*.log --color`
`tail -f /opt/kaltura/log/*.log | grep "ERR:\|PHP\|trace\|CRIT" --color`

To setup a development environment, consult [docs/development-environment.md](https://github.com/kaltura/ce-packager/blob/falcon/docs/development-environment.md)
