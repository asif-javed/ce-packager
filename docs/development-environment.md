# Setting up a Development Environment

Turning your Kaltura installation into a full git based development
environment should be done ***before*** running the install.php.  But if
you've already installed Kaltura and you don't mind losing all your
data, you can remove `/opt/kaltura` and run the installer again after
running the following commands.

    sudo mkdir /opt/kaltura
	sudo chown < your_user >:< your_user > /opt/kaltura
	cd /opt/kaltura
	git clone git@github.com:kaltura/KalturaServer.git app
	cd app
	git config core.filemode false

Find out what git commit your ce-packager repository is using 

    cd ~/ce-packager
	git submodule

That will return an ID for the commit used when creating the installer.

    cd /opt/kaltura/app
	git checkout -b dev_working_branch < git_commit_ID >

Run the Kaltura install.php over your git repository with an install to `/opt/kaltura`.  And finally, change directory permissions so you can edit in that directory, on Ubuntu:

    sudo chown -R www-data:www-data /opt/kaltura
    sudo chown -R < your_user >:< your_user > /opt/kaltura/app
    sudo chown -R www-data:www-data /opt/kaltura/app/cache
