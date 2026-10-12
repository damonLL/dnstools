#!/bin/sh

### DNSTools Copyright (c) 1998-2002 LaCaille Technology Services

### NOTICE!!!!
###
### Neither 'Cool IT Software' nor the author are responsible
### for any damage caused directly or indirectly from the use,
### installation, removal or modification of this software.
###
### No warranty, express or implied, is made to you regarding
### any type of use of this software.  You use this software
### COMPLETELY at your own risk.
###
### The only legal recourse you have is a full refund
### within 30 days.

ADD_TO_ORIGINAL="ORIG"
DNS_DIRECTORY=/var/dns
DNS_CONFIG_FILE=/etc/named.conf
SQL_FILE=dnstools.sql

echo
echo
echo "Have you read the README.FIRST file!?!?!"
echo
echo "If so, press [RETURN] to continue"
read ANYKEY

WHOAMI=`whoami`
if [ $WHOAMI != "root" ]; then
	echo
	echo "***********************************************"
	echo "Sorry, you must be root to run this script."
	echo "This script creates several files that"
	echo "normally require root access.  Please review"
	echo "the contents of this script if you are unsure"
	echo "about what it does."
	echo
	echo "Exiting."
	echo
	exit 1
fi

if [ ! -f "./dnstools" ]; then
	echo "You must first create the DNSTools executable"
	echo "by running 'make dnstools' in this directory."
	echo
	echo "Please see the README for more information."
	echo
	echo "Exiting."
	echo
	exit 1
fi

clear
echo
echo "DNSTOOLS INSTALL SCRIPT"
echo "#######################"
echo
echo "You MUST have the following information before"
echo "you continue running this script!"
echo
echo "1. MySQL information:"
echo "     a. Valid user account and password with the proper"
echo "        privileges to create a new database and tables."
echo "     b. Hostname or IP address of server that runs MySQL."
echo "     c. Please note that the user *MUST* have a password."
echo
echo "2. The full path and file name to both your DNS directory"
echo "   and the BIND configuration file."
echo
echo "3. The location of your Apache (or other web server)"
echo "   HTDOCS or Document_Root directory."
echo
echo "4. Username of a non-privileged user that the web server"
echo "   runs as.  (usually user 'nobody')"
echo
echo "5. Full pathname to the 'mysql' client executable."
echo
echo "PRESS [RETURN] TO CONTINUE OR CONTROL-C TO ABORT"
read ANYKEY
clear
echo
echo "*** MYSQL INFO ***"
echo "******************"
echo "Enter path without the trailing slash to the mysql executable."
echo "   for instance:    /usr/local/mysql/bin"
read MYSQL_PATH
echo "Enter MySQL hostname or IP:"
read MYSQL_HOST
echo "Enter a valid -MySQL- user:"
read MYSQL_USER

if [ "$MYSQL_USER" = "" ]; then
	echo "You *MUST* enter a user!  Exiting."
	exit 1
fi

echo "Enter the MySQL password:"
read MYSQL_PASSWORD

if [ "$MYSQL_PASSWORD" = "" ]; then
	echo "You *MUST* have a password!  Exiting."
	exit 1
fi

echo
echo "*** APACHE INFO ***"
echo "*******************"
echo "Apache HTDOCS directory:"
read HTDOCS_DIRECTORY

if [ ! -d $HTDOCS_DIRECTORY ]; then
	echo "Sorry, no directory at $HTDOCS_DIRECTORY"
	echo "Exiting"
	exit 1
fi

echo "Username Apache runs as:"
read APACHE_USER

clear
echo
echo "YOU ENTERED"
echo
echo "*** MYSQL INFORMATION ***"
echo "MySQL Client : $MYSQL_PATH/mysql"
echo "MySQL Host   : $MYSQL_HOST"
echo "Username     : $MYSQL_USER"
echo "Password     : $MYSQL_PASSWORD"
echo
echo "*** APACHE INFORMATION ***"
echo "Apache HTDOCS Directory : $HTDOCS_DIRECTORY"
echo "Apache runs as username : $APACHE_USER"
echo
echo "PRESS [RETURN] IF CORRECT, OR CONTROL-C TO ABORT"
read ANYKEY

clear
echo
echo "Now we need to create an empty BIND configuration file"
echo "and data directory.  If the values below are acceptable"
echo "you may proceed.  If the script detects these are"
echo "already in place, they will be moved to their name"
echo "with the extension '$ADD_TO_ORIGINAL' added to them."
echo
echo "BIND CONFIGURATION FILE : $DNS_CONFIG_FILE"
echo "BIND DATA DIRECTORY     : $DNS_DIRECTORY" 
echo
echo "PRESS [RETURN] TO CONTINUE OR CONTROL-C TO ABORT"
read ANYKEY

if [ ! -d $DNS_DIRECTORY ]; then
	echo "No data directory exists, creating it now."
	mkdir -p $DNS_DIRECTORY
else
	echo "Data directory exists, renaming and creating a new one."
	mv $DNS_DIRECTORY $DNS_DIRECTORY.$ADD_TO_ORIGINAL
	mkdir -p $DNS_DIRECTORY
fi

if [ ! -f $DNS_CONFIG_FILE ]; then
	echo "No BIND configuration file exists, creating it now."
	touch $DNS_CONFIG_FILE
else
	echo "BIND configuration file exists, renaming and creating a new one."
	mv $DNS_CONFIG_FILE $DNS_CONFIG_FILE.$ADD_TO_ORIGINAL
	touch $DNS_CONFIG_FILE
fi

echo
echo "Securing the data directory and BIND configuration file."
echo

chmod 600 $DNS_DIRECTORY
chown $APACHE_USER $DNS_DIRECTORY

chmod 600 $DNS_CONFIG_FILE
chown $APACHE_USER $DNS_CONFIG_FILE

echo "Creating DNSTools configuration file as /etc/dnstools.conf."
echo $MYSQL_HOST > /etc/dnstools.conf
echo "dnstools" >> /etc/dnstools.conf
echo $MYSQL_USER >> /etc/dnstools.conf
echo $MYSQL_PASSWORD >> /etc/dnstools.conf
echo "FREE_EDITION" >> /etc/dnstools.conf
echo 
echo "Completed.  Securing /etc/dnstools.conf now..."
chmod 600 /etc/dnstools.conf
chown $APACHE_USER /etc/dnstools.conf

echo "Initial directories and files set up successfully."
echo
echo "PRESS [RETURN] TO CONTINUE"
read ANYKEY

clear
echo
echo "We must now create the DNSTools MySQL database."
echo
echo "			*** WARNING ***"
echo
echo "			*** WARNING ***"
echo
echo "			*** WARNING ***"
echo
echo "If you press RETURN you will completely wipe out any"
echo "DNSTools data that you have in the MySQL database!!!"
echo
echo "-- IF YOU HAVE DNSTOOLS DATA THAT YOU WANT TO KEEP --"
echo "--              DO NOT PRESS RETURN                --"
echo
echo "Press [RETURN] to drop and recreate the DNSTools database"
read ANYKEY

$MYSQL_PATH/mysql -u $MYSQL_USER -p$MYSQL_PASSWORD < dnstools.sql

if [ -d $HTDOCS_DIRECTORY/dnstools ]; then
	echo "Found DNSTools web directory, moving it."
	mv $HTDOCS_DIRECTORY/dnstools $HTDOCS_DIRECTORY/dnstools.$ADD_TO_ORIGINAL
fi

cp -rp ./dnstools.web ./dnstools.copy
mv ./dnstools.copy $HTDOCS_DIRECTORY/dnstools

cp db.cache /var/dns

echo
echo "It looks like we successfully set up the web information."
echo
echo "Press [RETURN] to continue and finish the DNSTools setup"
read ANYKEY

clear
echo
echo "Now copying the DNSTools executable to the /usr/local/bin"
echo "directory.  Please note:  If you have to recompile the"
echo "dnstools binary, do not re-run this script!  Simply copy"
echo "the binary to /usr/local/bin/dnstools and set the"
echo "permissions appropriately!  (chmod 500 and chown to"
echo "whoever you set the Apache process owner to be, usually 'nobody')"
echo
echo "Press [RETURN] to continue or CONTROL-C to abort"
read ANYKEY

if [ ! -d /usr/local/bin ]; then
	echo "/usr/local/bin does not exist, creating..."
	mkdir -p /usr/local/bin
fi

cp dnstools /usr/local/bin/dnstools

chmod 500 /usr/local/bin/dnstools
chown $APACHE_USER /usr/local/bin/dnstools

if [ ! -f /usr/local/bin/dnstools ]; then
	echo "WARNING!  I attempted to copy dnstools to"
	echo "the /usr/local/bin directory, but it's not"
	echo "there now!   Exiting."
	exit 1
else
	echo "SUCCESS!  PRESS [RETURN] TO CONTINUE]"
	read ANYKEY
fi

clear
echo
echo "You have successfully setup your DNSTools application"
echo "and database.  Point your web browser to this server"
echo "to begin using DNSTools.  Your URL will be the server name"
echo "plus '/dnstools' such as    http://yourserver/dnstools"
echo
echo "Your initial login will be 'dnsadmin' with a password of 'dnsadmin'"
echo
echo "CHANGE YOUR PASSWORD IMMEDIATELY UPON YOUR FIRST LOGIN!"
echo
echo "If you have problems running the /usr/local/bin/dnstools executable,"
echo "please see the README file.  Make sure to use the '-troubleshooting'"
echo "parameter to receive enhanced troubleshooting messages."
echo
echo "Thank you very much for using DNSTools.  Don't forget to check out"
echo "the online FAQ and the DNSTools mailing list!  Check out the web site"
echo "for more information:   http://www.coolitsoftware.com"
echo
