drop database IF EXISTS dnstools;
create database dnstools;

#
# Table structure for table `acl_table`
#

use dnstools;

DROP TABLE IF EXISTS acl_table;
CREATE TABLE acl_table (
  record int(11) NOT NULL auto_increment,
  acl_name text NOT NULL,
  acl_type enum('TRANSFER','QUERY') NOT NULL default 'TRANSFER',
  is_global enum('YES','NO') NOT NULL default 'YES',
  points_to_domain int(11) NOT NULL default '0',
  allow_ip_range_list text NOT NULL,
  allow_names_list text NOT NULL,
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Access Control Lists for zone transfers';
# --------------------------------------------------------

#
# Table structure for table `alias_table`
#

DROP TABLE IF EXISTS alias_table;
CREATE TABLE alias_table (
  record int(11) NOT NULL auto_increment,
  alias_name varchar(254) NOT NULL default '',
  points_to_host int(11) default NULL,
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Information for internal and external CNAME entries';
# --------------------------------------------------------

#
# Table structure for table `domain_table`
#

DROP TABLE IF EXISTS domain_table;
CREATE TABLE domain_table (
  record int(11) NOT NULL auto_increment,
  domain_name tinytext NOT NULL,
  needs_update enum('YES','NO') NOT NULL default 'NO',
  ns_type enum('PRIMARY','SECONDARY') NOT NULL default 'PRIMARY',
  zone_transfer_ip varchar(15) NOT NULL default '',
  email text NOT NULL,
  serial int(11) NOT NULL default '0',
  default_refresh int(11) NOT NULL default '0',
  default_retry int(11) NOT NULL default '0',
  default_expire int(11) NOT NULL default '0',
  default_ttl int(11) NOT NULL default '0',
  points_to_group int(11) NOT NULL default '0',
  use_notify enum('YES','NO') NOT NULL default 'YES',
  is_active enum('YES','NO') NOT NULL default 'YES',
  main_dnstools_domain enum('YES','NO') NOT NULL default 'NO',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='SOA Information';
# --------------------------------------------------------

#
# Table structure for table `forwarders_table`
#

DROP TABLE IF EXISTS forwarders_table;
CREATE TABLE forwarders_table (
  record mediumint(9) NOT NULL auto_increment,
  ip_address varchar(15) NOT NULL default '',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Forwarders table - used for forwarding requests';
# --------------------------------------------------------

#
# Table structure for table `global_table`
#

DROP TABLE IF EXISTS global_table;
CREATE TABLE global_table (
  data_directory text NOT NULL,
  bind_configuration_file varchar(255) NOT NULL default '',
  log_file_name varchar(255) NOT NULL default '',
  log_level tinyint(4) NOT NULL default '0',
  company_name tinytext NOT NULL,
  company_address tinytext NOT NULL,
  contact_info tinytext NOT NULL,
  administrator_email varchar(200) NOT NULL default '',
  cache_only_server enum('NO','YES') NOT NULL default 'NO',
  forward_only_server enum('NO','YES') NOT NULL default 'NO',
  recursive_server enum('YES','NO') NOT NULL default 'YES',
  active_transfers_per_server smallint(6) NOT NULL default '0',
  total_active_transfers smallint(6) NOT NULL default '0',
  duration_of_transfer smallint(6) NOT NULL default '0',
  data_segment_size mediumint(9) NOT NULL default '0',
  data_segment_size_scale enum('k','m','g') NOT NULL default 'k',
  stack_size mediumint(9) NOT NULL default '0',
  stack_size_scale enum('k','m','g') NOT NULL default 'k',
  core_size mediumint(9) NOT NULL default '0',
  core_size_scale enum('k','m','g') NOT NULL default 'k',
  max_open_files mediumint(9) NOT NULL default '0',
  cleaning_interval smallint(6) NOT NULL default '0',
  statistics_interval mediumint(9) NOT NULL default '0',
  database_is_locked enum('YES','NO') NOT NULL default 'NO',
  needs_update enum('YES','NO') NOT NULL default 'NO'
) TYPE=MyISAM COMMENT='DNSTools NG Global Table';
# --------------------------------------------------------

#
# Table structure for table `group_membership_table`
#

DROP TABLE IF EXISTS group_membership_table;
CREATE TABLE group_membership_table (
  record int(11) NOT NULL auto_increment,
  points_to_user int(11) NOT NULL default '0',
  points_to_group int(11) NOT NULL default '0',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Each row contains a user/group combination. Users are member';
# --------------------------------------------------------

#
# Table structure for table `group_table`
#

DROP TABLE IF EXISTS group_table;
CREATE TABLE group_table (
  record int(11) NOT NULL auto_increment,
  group_name varchar(40) NOT NULL default '',
  group_address blob NOT NULL,
  misc text NOT NULL,
  PRIMARY KEY  (record),
  UNIQUE KEY group_name (group_name)
) TYPE=MyISAM COMMENT='Customer Information';
# --------------------------------------------------------

#
# Table structure for table `help_table`
#

DROP TABLE IF EXISTS help_table;
CREATE TABLE help_table (
  record mediumint(9) NOT NULL auto_increment,
  help_name tinytext NOT NULL,
  help_description text NOT NULL,
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Help Table';
# --------------------------------------------------------

#
# Table structure for table `host_table`
#

DROP TABLE IF EXISTS host_table;
CREATE TABLE host_table (
  record int(11) NOT NULL auto_increment,
  host_name tinytext NOT NULL,
  octet_1 smallint(6) NOT NULL default '0',
  octet_2 smallint(6) NOT NULL default '0',
  octet_3 smallint(6) NOT NULL default '0',
  octet_4 smallint(6) NOT NULL default '0',
  registered_ip enum('NO','YES') NOT NULL default 'NO',
  points_to_domain int(11) NOT NULL default '0',
  hinfo_1 varchar(255) NOT NULL default '',
  hinfo_2 varchar(255) NOT NULL default '',
  ttl int(11) NOT NULL default '0',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Holds common entry information';
# --------------------------------------------------------

#
# Table structure for table `ip_range_table`
#

DROP TABLE IF EXISTS ip_range_table;
CREATE TABLE ip_range_table (
  record mediumint(9) NOT NULL auto_increment,
  points_to_domain mediumint(9) NOT NULL default '0',
  needs_update enum('YES','NO') NOT NULL default 'NO',
  octet_1 smallint(6) NOT NULL default '0',
  octet_2 smallint(6) NOT NULL default '0',
  octet_3 smallint(6) NOT NULL default '0',
  octet_4 smallint(6) NOT NULL default '0',
  bitmask smallint(6) NOT NULL default '0',
  serial mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='CIDR IP Ranges in form of A.B.C/D';
# --------------------------------------------------------

#
# Table structure for table `mx_table`
#

DROP TABLE IF EXISTS mx_table;
CREATE TABLE mx_table (
  record int(11) NOT NULL auto_increment,
  name text NOT NULL,
  priority tinyint(4) NOT NULL default '0',
  points_to_domain int(11) NOT NULL default '0',
  points_to_host int(11) NOT NULL default '0',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Mail Exchange table';
# --------------------------------------------------------

#
# Table structure for table `ns_table`
#

DROP TABLE IF EXISTS ns_table;
CREATE TABLE ns_table (
  record int(11) NOT NULL auto_increment,
  is_primary enum('YES','NO') NOT NULL default 'NO',
  name text NOT NULL,
  ip_address varchar(16) NOT NULL default '',
  points_to_domain int(11) NOT NULL default '0',
  PRIMARY KEY  (record)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `round_robin_table`
#

DROP TABLE IF EXISTS round_robin_table;
CREATE TABLE round_robin_table (
  record int(11) NOT NULL auto_increment,
  points_to_host int(11) NOT NULL default '0',
  octet_1 smallint(6) NOT NULL default '0',
  octet_2 smallint(6) NOT NULL default '0',
  octet_3 smallint(6) NOT NULL default '0',
  octet_4 smallint(6) NOT NULL default '0',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Round Robin information';
# --------------------------------------------------------

#
# Table structure for table `user_table`
#

DROP TABLE IF EXISTS user_table;
CREATE TABLE user_table (
  record int(11) NOT NULL auto_increment,
  user_name varchar(40) NOT NULL default '',
  password varchar(20) NOT NULL default '',
  full_name varchar(80) NOT NULL default '',
  work_phone varchar(30) NOT NULL default '',
  mobile_phone varchar(30) NOT NULL default '',
  pager_number varchar(30) NOT NULL default '',
  fax_number varchar(30) NOT NULL default '',
  email varchar(250) NOT NULL default '',
  address text NOT NULL,
  notes text NOT NULL,
  dnstools_administrator enum('NO','YES') NOT NULL default 'NO',
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Individual User Information';

# phpMyAdmin MySQL-Dump
# version 2.2.2
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Feb 14, 2002 at 06:52 PM
# Server version: 3.23.41
# PHP Version: 4.0.6
# Database : `dnstools`

INSERT INTO `user_table` (`record`, `user_name`, `password`, `full_name`, `work_phone`, `mobile_phone`, `pager_number`, `fax_number`, `email`,
  `address`, `notes`, `dnstools_administrator`) VALUES ('', 'dnsadmin', PASSWORD('dnsadmin'), 'DNS Tools Administrator', '', '', '', '', '', '', '', 'YES');

INSERT INTO `global_table` (`data_directory`, `bind_configuration_file`, `log_file_name`, `log_level`, `company_name`, `company_address`, `contact_info`, `administrator_email`, `cache_only_server`, `forward_only_server`, `recursive_server`, `active_transfers_per_server`, `total_active_transfers`, `duration_of_transfer`, `data_segment_size`, `data_segment_size_scale`, `stack_size`, `stack_size_scale`, `core_size`, `core_size_scale`, `max_open_files`, `cleaning_interval`, `statistics_interval`, `database_is_locked`, `needs_update`) VALUES ('/var/dns', '/etc/named.conf', '/tmp/dnstools.log', '1', 'Your Company Name Here', 'Your Company Address Here', '', 'your_email@your_company.com', 'NO', 'NO', 'YES', '8', '16', '30', '16', 'k', '32', 'm', '64', 'm', '128', '240', '60', 'NO', 'NO');

#
# Table structure for table `help_table`
#

DROP TABLE IF EXISTS help_table;
CREATE TABLE help_table (
  record mediumint(9) NOT NULL auto_increment,
  help_name tinytext NOT NULL,
  help_description text NOT NULL,
  PRIMARY KEY  (record)
) TYPE=MyISAM COMMENT='Help Table';

#
# Dumping data for table `help_table`
#

INSERT INTO help_table VALUES (1, 'new_host_step_1', 'This is the first step in adding a new host to a domain.<P>\r\nThe drop down list contains a list of domains that you have authority for based on your group permissions.<P>\r\nSelect the domain you wish to add a new host to and click "NEXT STEP ->".');
INSERT INTO help_table VALUES (2, 'new_host_step_2', 'On this screen you may enter an unregistered IP address for this host or you may select from a network IP range that this host will reside on based on the domain you selected from the last screen.<P>\r\nIf you see a drop down list that says "-=SELECT IP RANGE=-", then you have a set of IP ranges that are assigned to this domain.  This means that your domain is authoritative for reverse DNS for those IP ranges.<P>\r\nIf you do not see a drop down list, then this domain is not authoritative for any IP ranges and can only perform forward DNS lookups (name to IP).  Users will still be able to access this machine by it\'s DNS name, but when they perform a reverse DNS lookup (IP to name) this host name will not be returned.<P>\r\n\r\nYou must choose an IP range or enter the IP address on this screen.  If you choose an IP range, the next screen will allow you to choose a specific IP in that range.  If you choose to enter the IP address in the fields provided, the next screen will prompt you for additional host information.');
INSERT INTO help_table VALUES (3, 'new_host_step_3', 'This page allows you to select an IP address within the range that you specified on the last screen.<P>\r\nAll IP addresses are listed that have not already been taken by other hosts in this domain.  All available IP addresses are displayed in sequential order, though you may see gaps in between numbers as those addresses have already been used.<P>\r\nSelect the IP address you wish to use by clicking on the check box to the right of the IP, then scroll to the bottom of the page and click "NEXT STEP".');
INSERT INTO help_table VALUES (4, 'new_host_step_4', 'This screen allows you to enter all other data, such as the hostname, the Time To Live (TTL) value and miscellaneous hardware information (HINFO1 and HINFO2).<P>\r\nIt also lists the current IP address that you have assigned it, along with if this host has a registered IP address (forward and reverse DNS lookups) or a non-registered IP address (forward only DNS lookups).<P>\r\nIf you wish to add aliases (CNAMEs), round robin IP addresses or host-specific Mail Exchange (MX) records, you must submit this host first then go to the main Hosts screen, click List Hosts, select this domain, and then click on the appropriate button next to this host entry.');
INSERT INTO help_table VALUES (5, 'ttl', 'TTL stands for Time To Live.<P>\r\nTTL has a direct result on how fast name\r\nservers can resolve a name on the\r\ninternet and how long it takes for\r\na name to propogate across the internet.<P>\r\nThe value of TTL is in seconds, so 3600\r\nwould be 1 hour.<P>\r\nSetting a high TTL value would mean\r\nthat name servers on the internet would\r\ncache this information for a long time.\r\nThis might be good for addresses that\r\ndon\'t change often.<P>\r\nHowever, when you do need to change\r\nthe entry, it could take as long as the\r\nTTL value before the entire internet\r\nsees the new information.<P>\r\nSetting your TTL for several days is\r\nprobably not a bad idea unless you plan\r\non changing the information soon.  You\r\nwould normally set this to a very low\r\nvalue so that the internet would clear\r\nit\'s cached name information if you\r\nwere going to change the host information.');
INSERT INTO help_table VALUES (6, 'hostname', 'The hostname is the unique name within\r\nthe domain that identifies this computer.<P>\r\nA hostname can be comprised of alpha\r\nnumeric characters (A-Z, 0-9) and dashes.<P>\r\n<STRONG><EM>Underscores, dots (periods), slashes, \r\nexclamation points, etc are not\r\npermitted.</EM></STRONG><P>');
INSERT INTO help_table VALUES (7, 'ip_address', 'An IP (Internet Protocol) address\r\nis a series of 4 decimal numbers\r\nseparated by dots, or periods.<P>\r\nThe numbers can be 0 through 255.  The\r\nIP address is a 32-bit number separated\r\ninto 4 octets (8 bit numbers).<P>\r\nAn example:  192.168.1.1<P>\r\nWith IPv6, the length of an IP address\r\nis extended dramatically.  DNSTools\r\nv2.0 does nto support IPv6.  It is\r\nplanned for a later release.');
INSERT INTO help_table VALUES (8, 'registered_ip', 'The difference between a registered\r\nIP address and a non-registered IP\r\naddress is simply the type of name\r\nresolution the DNS server will provide\r\nfor that entry.<P>\r\nIf an IP address is registered within\r\nDNSTools, then the DNS server will return\r\nboth forward (name to IP) resolution and\r\nbackwards (IP to name) resolution.<P>\r\nIf an IP address is not registered within\r\nDNSTools, then the DNS server will\r\nonly return forward (name to IP) resolution.<P>\r\nWhile a lookup based on a single name\r\ncan result in multiple IP addresses,\r\na lookup based on a single IP will\r\nonly return a single name.  This name\r\nwill reside in the domain that is\r\nauthoritative for the IP address.<P>\r\nYou can point a hostname in your domain\r\nto an external IP address even if you\r\ndo not own the IP address yourself. Keep\r\nin mind you will have to add it as a\r\nnon-registered IP address because you\r\ndo not have authority for the IP address.');
INSERT INTO help_table VALUES (9, 'hinfo_1', 'The HINFO fields were once used for\r\nolder applications to determine how to\r\ncommunicate with other applications\r\nacross the internet.<P>\r\nNow they are generally used for record\r\nkeeping purposes or for simple information\r\nabout the machine.<P>\r\nNOTE: It is not recommended that you\r\nkeep sensitive information or information\r\nthat might lead to a security breach\r\nin these fields.  These fields will\r\nbe accessible to any user who has\r\nthe ability to query this name server.<P>');
INSERT INTO help_table VALUES (10, 'hinfo_2', 'The HINFO fields were once used for\r\nolder applications to determine how to\r\ncommunicate with other applications\r\nacross the internet.<P>\r\nNow they are generally used for record\r\nkeeping purposes or for simple information\r\nabout the machine.<P>\r\nNOTE: It is not recommended that you\r\nkeep sensitive information or information\r\nthat might lead to a security breach\r\nin these fields.  These fields will\r\nbe accessible to any user who has\r\nthe ability to query this name server.<P>');
INSERT INTO help_table VALUES (11, 'list_hosts', 'This screen shows all hosts within the\r\ndomain that you selected.<P>\r\nYou can sort this screen by either the\r\nhostname or the IP address of each\r\nentry by simply clicking on the column\r\nheader you wish to sort on.<P>\r\nYou can add an alias (CNAME) or a\r\nRound Robin IP address to any entry\r\nlisted on this screen by clicking the\r\n<img src="/dnstools/images/icons/ultra-small-alias.gif"></img> or\r\n<img src="/dnstools/images/icons/ultra-small-round-robin.gif"></img>,\r\nbuttons, respectively.<P>\r\nTo View, Modify or Delete a host, click\r\non the\r\n<img src="/dnstools/images/icons/ultra-small-view.gif"></img>,\r\n<img src="/dnstools/images/icons/ultra-small-modify.gif"></img> or\r\n<img src="/dnstools/images/icons/ultra-small-delete.gif"></img>\r\nbuttons, respectively.<P>');
INSERT INTO help_table VALUES (12, 'add_round_robin', 'In this screen you can add a single\r\nRound Robin IP address to this host.<P>\r\nThere is no limit to the number of\r\nRound Robin IP addresses a host can\r\nhave, but you must enter them individually.<P>\r\nTo find out more about Round Robin IP\r\naddresses, click on the field name in\r\nthe screen next to the data entry\r\nfield.');
INSERT INTO help_table VALUES (13, 'full_hostname', 'This field simply shows the full\r\nhostname prepended to the domain name\r\nit belongs in.<P>\r\nExample:  hostname.domain.com');
INSERT INTO help_table VALUES (14, 'round_robin_ip', 'A Round Robin IP address is an additional\r\nIP address that is associated with a host.<P>\r\nRound Robin IP addresses are specifically\r\ndesigned to provide alternating IP\r\naddress to queries for this host.<P>\r\nA common example would be a heavily loaded\r\nweb server on the internet.  By spreading\r\nthis web server over 3 separate physical\r\nmachines it can handle the load much better.\r\nBut to be able to access each of these\r\nmachines with the same name, you need\r\nRound Robin IP addresses.  Every time\r\na new request is made for the IP address\r\nfor the web server, your DNS server\r\nwill hand out the next Round Robin IP\r\naddress in the list.<P>\r\nPlease note that this is not clustering\r\nin any sense, and it does not provide\r\nfor intelligent fail over.  This is\r\na simple way of handing out multiple\r\nIP addresses for a single name.');
INSERT INTO help_table VALUES (15, 'add_domain', '<P>To add a new domain you must enter all of the necessary fields in this screen.  Below are some basic descriptions for the fields.</P>\r\n<P><STRONG>Domain Name</STRONG> - The name of the new domain you wish to create.</p>\r\n<P><STRONG>Name Server Type - </STRONG>:\r\n<UL><LI>PRIMARY - You will be hosting the domain on this server.</LI>\r\n<LI>SECONDARY - You will be hosting the domain on another server.  DNSTools will automatically create the proper named.conf entries to retrieve the domain from the primary name server.  If you select SECONDARY you must also enter the IP address of the primary in the field below.</LI>\r\n</UL></P>\r\n<P><STRONG>IP of Primary</STRONG> - The IP address of the domain server that is the primary for this domain.</P>\r\n<P><STRONG>EMail Address</STRONG> - This is used for sending DNS errors to.  It must be in the form of "user.domain" instead of "user@domain".  This is a configuration that BIND uses and it makes sense to leave it that way here.</P>\r\n<P><STRONG>Starting Serial</STRONG> - This is the serial number you wish to start with.  Typically you would enter 0 here.  You can change this later in the Modify Domain section.</p>\r\n<P><STRONG>Default Refresh</STRONG> - The refresh value controls how much time (in seconds) a secondary name server should wait before updating it\'s records from the primary.  Note that if you\'re using Bind version 8 and have \'Use Notify\' selected below, your secondary will be asked (by the primary) to update it\'s records whenever there is a change.</P>\r\n<P><STRONG>Default Retry</STRONG> - If the secondary server fails to retrieve the data during a scheduled update, it will wait this many seconds before it attempts it again.</p>\r\n<p><STRONG>Default Expire</STRONG> - If the secondary cannot update it\'s information in this many seconds, it will forget about the data and discontinue serving DNS records for this domain.</P>\r\n<P><STRONG>Default Time To Live</STRONG> - This value sets how long a remote DNS server should keep information about records in this domain.  This value is in seconds.  Note that DNSTools allows you to set a TTL on individual host records as well.  This value is used as a default for all hosts that do not specify their own TTL.</P>\r\n<P><STRONG>Owned by Group</STRONG> - Only members of the specified group are allowed to view and update information in this domain.</P>\r\n<P><STRONG>Use Notify</STRONG> - By selecting this, you are telling BIND to send requests to secondary name servers to update their information before the Default Refresh time is up.  The secondary servers will in turn make a request to pull down the newest information from the primary.</P>\r\n<P><STRONG>Active Domain</STRONG> - This is specific to DNSTools, not to BIND.  You can disable a domain by setting this to NO.  This will decrease your # of active domains (for licensing purposes) and will temporarily discontinue writing this domain information to the named.conf file and the DNS directory where the zone file resides.  Simply modify the domain and select "YES" to reactivate it.</P>\r\n<P><STRONG>Main DNSTools Domain</STRONG> - This setting controls several global aspects of the BIND configuration.  In the reverse zone files there is an IP address for a name server.  Because DNSTools allows multiple users to administrate networks smaller than 255 hosts (more than 24 bits), we need a common domain name server to refer to for authority.  By setting this to YES, you are in effect telling BIND to look at this domain\'s information for reverse zone authority information.  This would normally be the domain that you consider your business domain or how you identify your internet presence with.</P>\r\n');
INSERT INTO help_table VALUES (19, 'add_ip_range', '<p>Adding an IP range allows you to control the reverse lookup values in a domain.  You will normally be given a range of IP network numbers that you are allowed to use on the internet.  You can assign a range of IP addresses to a domain so that the domain has reverse authority for the names in this domain.</p>\r\n<p>To add a new IP range, simply enter all 4 octets and include the bitmask.  The bitmask tells you how many hosts (or IP addresses within this network) are assigned to this domain.</p>\r\n<p>If you wish to assign an entire class C (24 bits / 256 hosts) then you will enter \'0\' as the last octet, and select \'24 Bits\' as the bitmask.</p>\r\n<p>If you wish to have less than a full class C, you must enter the starting IP address and the bitmask.  Your maximum available IP addresses within this range will be the IP address you entered plus the max number of hosts you selected by the bitmask.</p>\r\n<p>Please note, DNSTools does not currently check for overlapping IP ranges in domains.  You should always be aware of the IP ranges you assign different domains.  There is a link to the List IP Ranges function on this page if you are unsure about where you registered IP addresses are reserved.</p>');
INSERT INTO help_table VALUES (18, 'modify_domain', '<P>Below are some basic descriptions for the fields.</P>\r\n<P><STRONG>Domain Name</STRONG> - The name of the new domain you wish to create.</p>\r\n<P><STRONG>EMail Address</STRONG> - This is used for sending DNS errors to.  It must be in the form of "user.domain" instead of "user@domain".  This is a configuration that BIND uses and it makes sense to leave it that way here.</P>\r\n<P><STRONG>Serial</STRONG> - This is the serial number you wish to start with.  Typically you would enter 0 here.  You can change this later in the Modify Domain section.</p>\r\n<P><STRONG>Default Refresh</STRONG> - The refresh value controls how much time (in seconds) a secondary name server should wait before updating it\'s records from the primary.  Note that if you\'re using Bind version 8 and have \'Use Notify\' selected below, your secondary will be asked (by the primary) to update it\'s records whenever there is a change.</P>\r\n<P><STRONG>Default Retry</STRONG> - If the secondary server fails to retrieve the data during a scheduled update, it will wait this many seconds before it attempts it again.</p>\r\n<p><STRONG>Default Expire</STRONG> - If the secondary cannot update it\'s information in this many seconds, it will forget about the data and discontinue serving DNS records for this domain.</P>\r\n<P><STRONG>Default Time To Live</STRONG> - This value sets how long a remote DNS server should keep information about records in this domain.  This value is in seconds.  Note that DNSTools allows you to set a TTL on individual host records as well.  This value is used as a default for all hosts that do not specify their own TTL.</P>\r\n<P><STRONG>Use Notify</STRONG> - By selecting this, you are telling BIND to send requests to secondary name servers to update their information before the Default Refresh time is up.  The secondary servers will in turn make a request to pull down the newest information from the primary.</P>\r\n\r\n<HR>\r\n<p>If you are a DNSTools Administrator, you will also see the following settings that you can modify.</p>\r\n<P><STRONG>Name Server Type - </STRONG>:\r\n<UL><LI>PRIMARY - You will be hosting the domain on this server.</LI>\r\n<LI>SECONDARY - You will be hosting the domain on another server.  DNSTools will automatically create the proper named.conf entries to retrieve the domain from the primary name server.  If you select SECONDARY you must also enter the IP address of the primary in the field below.</LI>\r\n</UL></P>\r\n<P><STRONG>Owned by Group</STRONG> - Only members of the specified group are allowed to view and update information in this domain.</P>\r\n<P><STRONG>Active Domain</STRONG> - This is specific to DNSTools, not to BIND.  You can disable a domain by setting this to NO.  This will decrease your # of active domains (for licensing purposes) and will temporarily discontinue writing this domain information to the named.conf file and the DNS directory where the zone file resides.  Simply modify the domain and select "YES" to reactivate it.</P>\r\n<P><STRONG>Main DNSTools Domain</STRONG> - This setting controls several global aspects of the BIND configuration.  In the reverse zone files there is an IP address for a name server.  Because DNSTools allows multiple users to administrate networks smaller than 255 hosts (more than 24 bits), we need a common domain name server to refer to for authority.  By setting this to YES, you are in effect telling BIND to look at this domain\'s information for reverse zone authority information.  This would normally be the domain that you consider your business domain or how you identify your internet presence with.</P>\r\n');
INSERT INTO help_table VALUES (20, 'add_mx', '<p>Adding a MX (Mail Exchange) record allows you to receive email in this domain.</p>\r\nYou must select the domain you wish to add a MX record to.  Note you will only be shown the domains which you have authority for based on your group settings.</p>\r\n<p>Enter the full hostname.  For instance: "mail.domain.com" rather than just "mail".</p>\r\n<p>Enter the priority.  The lower the number, the higher priority this MX record is.  When email is sent across the internet, it goes to the highest priority MX record in DNS.  If you have multiple email servers on the internet, you can set their priority to the same value</p>');
INSERT INTO help_table VALUES (21, 'add_ns', '<p>Adding a NS (Name Server) record allows you to receive DNS queries for this domain.</p>\r\n<p>You must select the domain you wish to assign an NS record in.  Note that you will only see domains for which you have authority for based on your group access</p>\r\n<p>Enter the full hostname of this name server.  For example "ns1.domain.com" rather than just "ns1".  Note that you can actually have a name server outside of this domain for increased versatility.</p>\r\n<p>Enter the full IP address of this name server</p>\r\n<p>Select if this is the primary IP address or not.  This will be the IP address listed in the zone file that will affect many things including the queries from secondary name servers.</p>');
INSERT INTO help_table VALUES (22, 'add_user', '<P>Adding a user is a simple process.  Most fields are self-explanatory and can actually contain anything you see fit to put in there, with a few exceptions:</p>\r\n<P><STRONG>Username</STRONG> - This will be the actual username the user logs in with.</p>\r\n<P><STRONG>Password</STRONG> - Enter the password here</p>\r\n<p><STRONG>Verify Password</STRONG> - Reenter the password here for verification.</p>\r\n<p><strong>DNSTools Administrator</strong> - BE CAREFUL!  Anybody who is setup as a DNSTools Administrator has complete access to all areas of the application!  This should be reserved for only a few trusted individuals (or only you!)</p>');
INSERT INTO help_table VALUES (23, 'modify_user', '<P>Modifying a user is a simple process.  Most fields are self-explanatory and can actually contain anything you see fit to put in there, with a few exceptions:</p>\r\n<P><STRONG>Username</STRONG> - This will be the actual username the user logs in with.</p>\r\n<p><strong>DNSTools Administrator</strong> - BE CAREFUL!  Anybody who is setup as a DNSTools Administrator has complete access to all areas of the application!  This should be reserved for only a few trusted individuals (or only you!)</p>');
INSERT INTO help_table VALUES (24, 'view_user_info', '<P>All fields on this screen should be self-explanatory.  If you need to update the information, send an email to the individual listed at the bottom of this screen.</p>\r\n<P>You are also able to change your password from here.  Click "Change Password" and enter your password twice in the next screen.</p>');
INSERT INTO help_table VALUES (25, 'configure', '<p>This is the main DNSTools configuration screen.  From here you can modify all of the core settings of the software and some global BIND configuration settings as well.</p>\r\n<p><STRONG>Company Name</STRONG> - Enter your company name here.</p>\r\n<P><STRONG>Company Address</STRONG> - Self-explanatory.</P>\r\n<P><STRONG>Contact Info</STRONG> - Enter any important contact information here.  This may be used in later versions for auto-registration.  This is a free-form text field, no restrictions.</P>\r\n<P><STRONG>Administrator Email</STRONG> - This should be in the form of "name@domain.com" and is used on several informational screens to inform the user who they should email for problems.</P>\r\n<P><STRONG>Data Directory</STRONG> - Where the zone files will be stored.  This should be the full path name such as "/var/dns", etc.</P>\r\n<P><STRONG>BIND Configuration File</STRONG> - This is normally /etc/named.conf but can be anything you want.  Please note that BIND by default will look for /etc/named.conf unless you specify differently on the command line.</p>\r\n<p><strong>Log File Name</STRONG> - The full path name to the log file such as "/tmp/dnstools.log".  Please note that the log file must already exist and must be writable by the web server user ID.</p>\r\n<p><strong>Logging Level</STRONG> - <UL>\r\n<LI>Minimal - Only records actual changes to the database and logins.</LI>\r\n<LI>Detailed - Includes everything in \'Minimal\' setting plus additional information when users view certain screens.</LI>\r\n<LI>TroubleShooting - Normally only used when working with DNSTools tech support to find a bug in the software.</LI>\r\n</UL></p>\r\n<P><STRONG>Cache-Only Server</STRONG> - When you want this DNS server to only be used for caching large amounts of information.  This server will not perform zone transfers and cannot answer any requests authoritatively.  All requests are either forwarded or answered through it\'s cache.  This feature should only be used by experienced DNS administrators.</p>\r\n<P><STRONG>Forward-Only Server</STRONG> - Sorry, this feature should not be used at present time.  This is reserved for when the FORWARDERS directive is fully implemented.  Use at your own risk!</P>\r\n<P><STRONG>Recursive Server</STRONG> - You should always choose YES in this field.  The only time you would want to choose NO is if you do not want your name server to lookup anything beyond it\'s own zone data.</P>\r\n<P><STRONG>Transfers per Server</STRONG> - How many transfers per secondary server do you wish to occur at a time?</P>\r\n<P><STRONG>Total Active Transfers</STRONG> - This is the total number of transfers you have going at once, regardless of the number of instances per server.</P>\r\n<P><STRONG>Duration of Transfer</STRONG> - The maximum time allowed (in minutes) to allow for a zone to be transferred in from another primary if the secondary resides on this server.</P>\r\n<P><STRONG>Data Segment Size</STRONG> - The maximum memory size you wish BIND to take when running.</P>\r\n<P><STRONG>Stack Size</STRONG> - Allows you to change the default stack size for the BIND server process.  Proceed with caution!</p>\r\n<P><STRONG>Core Size</STRONG> - The maximum size you wish the corefile to be if the BIND server core dumps.</P>\r\n<P><STRONG>Max Open Files</STRONG> - Some operating systems may allow you to modify the maximum number of files that BIND can open at once.  Remember that each zone file takes up a file descriptor.  Additionally, for each physical IP address you have on your DNS server (not the number of DNS entries but the physical network connections to your system) it also requires one file descriptor.<p>\r\n<P><STRONG>Cleaning Interval</STRONG> - How often (in minutes) you want BIND to go through it\'s own data and clear out old information it no longer users.</P>\r\n<P><STRONG>Statistics Interval</STRONG> - How often (in minutes) do you want BIND to dump it\'s statistics to a statistics file.</P>\r\n<P><STRONG>Lock Database</STRONG> - This will disallow any modifications to be made to the DNSTools database through the DNSTools interface.  Please note that this does not actually lock the database, it only disallows access to it through the application.  Users may still access the database directly by command line or other network-based MySQL access tools.</P>\r\n\r\n');

