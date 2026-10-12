/*
    ***************************************************************
    * DNSTOOLS COPYRIGHT (c) 2002 Damon LaCaille
    ***************************************************************

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <netdb.h>
#include <mysql.h>
#include <mysql_version.h>

char version[30] = "2.0.2";

char domain_txt[255] = "Brought to you by DNSTools at http://www.dnstools.com";
char license_type[255] = "This software is released under the GNU Public License (GPL)";

long int max_free_hosts = 99999999;
long int max_free_users = 99999999;
long int max_free_domains = 99999999;

char conf_file[1024] = "/etc/dnstools.conf";
char hostname[256];

int num_of_hosts, row_count_2, row_count, count_host, count, max_ip, num, record, num_rows_3, num_rows, num_rows2, domain_number, parent_domain_record_num, total_domains_updated, serial_number = 0;
MYSQL mysql, mysql_ip_range, mysql_domain_3, mysql_primary_name_server, mysql_serial, mysql_host_2, mysql_update, mysql_math, mysql_reverse, mysql_domain, mysql_domain_2, mysql_host, mysql_name_server, mysql_name_server_3, mysql_name_server_2, mysql_round_robin, mysql_mail_exchange;
MYSQL_RES *result_domain_3, *result_primary_name_server, *result_serial, *result_host_2, *result_update, *result_mysql_math, *result_ip_range, *result_reverse, *result_query, *result_name_server, *result_name_server_2, *result_name_server_3, *result_global, *result_domain, *result_domain_2, *result_user, *result_host, *result_round_robin, *result_mail_exchange;
MYSQL_ROW row_domain_3, row_primary_name_server, row_serial, row_host_2, row_update, row_math, row_ip_range, row_reverse, row_name_server, row_name_server_2, row_name_server_3, row_global, row_user, row_domain, row_host, row_mail_exchange, row_round_robin, row_domain_2;
char	qbuf[160];
char needs_update[10];
char num_records[10];
char db_file[300];
char parent_domain_name[255];
char parent_domain_record[16];
char sub_ns_name[255];
char sub_ns_ip[16];
char query_this_1[1024];
char query_this_2[1024];
char query_this_3[1024];
char query_math[1024];
char query_reverse[1024];
char round_robin_ip[20];
char registered_ip[20];
char string_count_host[15];
char ns_ip[20];
char ns_ttl[20];
char mx_ip[20];
char serial[20];
char domain_record[10];
char string_count[10];
char octet_count[10];
char host_record[10];
char data_directory[200];
char backup_directory[200];
char max_backups[20];
char points_to_domain[20];
char file_name[250];
char ns_name[250];
char hinfo_1[250];
char hinfo_2[250];
char name_server_name[250];
char host_name[250];
char alias_name[250];
char mx_name[250];
char mx_priority[5];
char bind_configuration_file[200];
char domain_name[250];
char email[250];
char ns_type[10];
char zone_transfer_ip[16];
char sub_zone_transfer_ip[16];
char active_transfers_per_server[5];
char total_active_transfers[5];
char duration_of_transfer[5];
char core_size[8];
char core_size_scale[8];
char data_segment_size[8];
char data_segment_size_scale[8];
char stack_size[8];
char stack_size_scale[8];
char max_open_files[8];
char cleaning_interval[8];
char statistics_interval[8];
char recursive_server[3];
char forward_only_server[3];
char cache_only_server[3];
char octet_1[3];
char octet_2[3];
char octet_3[3];
char octet_4[3];
char current_octet_1[3];
char current_octet_2[3];
char current_octet_3[3];
char current_octet_4[3];
char bitmask[3];

char conf_host[255];
char conf_db_name[255];
char conf_user[255];
char conf_password[255];
char conf_license_key[255];

char domain_txt[255];

int num_bitmask, num_octet_4, num_hosts, num_users, num_domains, verbose, troubleshooting = 0;
int allowed_domains, allowed_hosts, allowed_users;
char ttl[10];
char default_ttl[10];
char default_refresh[10];
char default_retry[10];
char default_expire[10];
FILE *fp;

char *utoa(unsigned value, char *digits, int base)
{
	char *s, *p;
	s = "0123456789abcdefghijklmnopqrstuvwxyz"; /* don't care if s is in
																							* read-only memory*/
	if (base == 0)
		base = 10;

	if (digits == NULL || base < 2 || base > 36)
		return NULL;

	if (value < (unsigned) base)
	{
		digits[0] = s[value];
		digits[1] = '\0';
	}
	else
	{
		for (p = utoa(value / ((unsigned)base), digits, base);
		*p;
		p++);
		utoa( value % ((unsigned)base), p, base);
	}

	return digits;
}   

char *itoa(int value, char *digits, int base)
{
	char *d;
	unsigned u; /* assume unsigned is big enough to hold all the
						* unsigned values -x could possibly be -- don't
						* know how well this assumption holds on the
						* DeathStation 9000, so beware of nasal demons */
	d = digits;
	if (base == 0)
	base = 10;
	if (digits == NULL || base < 2 || base > 36)
	return NULL;
	if (value < 0)
	{
		*d++ = '-';
		u = -value;
	}
	else
	u = value;

	utoa(u, d, base);
	return digits;
}

void check_license()
{
	/* This is normally where I would make sure the license
	 * key is correct and set the following variables to
	 * their proper values.  Otherwise I would just set them
	 * to whatever the FREE variables are set to above. */

	allowed_hosts = max_free_hosts;
	allowed_domains = max_free_domains;
	allowed_users = max_free_users;

	printf ("\nLicense Key Information:\n");
	printf ("Key Type    : %s\n", license_type);
	printf ("Max Domains : %d\n", allowed_domains);
	printf ("Max Users   : %d\n", allowed_users);
	printf ("Max Hosts   : %d\n\n", allowed_hosts);
}


int dns_close_all_mysql ()
{
	mysql_close (&mysql_primary_name_server);
	mysql_close (&mysql_serial);
	mysql_close (&mysql_host_2);
	mysql_close (&mysql_update);
	mysql_close (&mysql_math);
	mysql_close (&mysql_reverse);
	mysql_close (&mysql_domain);
	mysql_close (&mysql_domain_2);
	mysql_close (&mysql_domain_3);
	mysql_close (&mysql_host);
	mysql_close (&mysql_name_server);
	mysql_close (&mysql_name_server_2);
	mysql_close (&mysql_name_server_3);
	mysql_close (&mysql_round_robin);
	mysql_close (&mysql_mail_exchange);
	mysql_close (&mysql);
	return 0;
}

int dns_init_all_mysql ()
{
	mysql_init (&mysql);
	mysql_init (&mysql_ip_range);
	mysql_init (&mysql_primary_name_server);
	mysql_init (&mysql_serial);
	mysql_init (&mysql_host_2);
	mysql_init (&mysql_update);
	mysql_init (&mysql_math);
	mysql_init (&mysql_reverse);
	mysql_init (&mysql_domain);
	mysql_init (&mysql_domain_2);
	mysql_init (&mysql_domain_3);
	mysql_init (&mysql_host);
	mysql_init (&mysql_name_server);
	mysql_init (&mysql_name_server_2);
	mysql_init (&mysql_name_server_3);
	mysql_init (&mysql_round_robin);
	mysql_init (&mysql_mail_exchange);

	if (!mysql_connect(&mysql,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql));
		return 1; }
	if (mysql_select_db(&mysql,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql));
		mysql_close(&mysql);
		return 1;}

	if (!mysql_connect(&mysql_ip_range,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_ip_range));
		return 1; }
	if (mysql_select_db(&mysql_ip_range,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_ip_range));
		mysql_close(&mysql_ip_range);
		return 1;}

	if (!mysql_connect(&mysql_primary_name_server,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_primary_name_server));
		return 1; }
	if (mysql_select_db(&mysql_primary_name_server,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_primary_name_server));
		mysql_close(&mysql_primary_name_server);
		return 1;}

	if (!mysql_connect(&mysql_mail_exchange,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_mail_exchange));
		return 1; }
	if (mysql_select_db(&mysql_mail_exchange,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_mail_exchange));
		mysql_close(&mysql_mail_exchange);
		return 1;}

	if (!mysql_connect(&mysql_round_robin,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_round_robin));
		return 1; }
	if (mysql_select_db(&mysql_round_robin,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_round_robin));
		mysql_close(&mysql_round_robin);
		return 1;}

	if (!mysql_connect(&mysql_name_server_3,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_name_server_3));
		return 1; }
	if (mysql_select_db(&mysql_name_server_3,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_name_server_3));
		mysql_close(&mysql_name_server_3);
		return 1;}

	if (!mysql_connect(&mysql_name_server_2,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_name_server_2));
		return 1; }
	if (mysql_select_db(&mysql_name_server_2,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_name_server_2));
		mysql_close(&mysql_name_server_2);
		return 1;}

	if (!mysql_connect(&mysql_name_server,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_name_server));
		return 1; }
	if (mysql_select_db(&mysql_name_server,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_name_server));
		mysql_close(&mysql_name_server);
		return 1;}

	if (!mysql_connect(&mysql_host,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_host));
		return 1; }
	if (mysql_select_db(&mysql_host,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_host));
		mysql_close(&mysql_host);
		return 1;}

	if (!mysql_connect(&mysql_domain_3,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_domain_3));
		return 1; }
	if (mysql_select_db(&mysql_domain_3,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_domain_3));
		mysql_close(&mysql_domain_3);
		return 1;}

	if (!mysql_connect(&mysql_domain_2,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_domain_2));
		return 1; }
	if (mysql_select_db(&mysql_domain_2,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_domain_2));
		mysql_close(&mysql_domain_2);
		return 1;}

	if (!mysql_connect(&mysql_domain,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_domain));
		return 1; }
	if (mysql_select_db(&mysql_domain,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_domain));
		mysql_close(&mysql_domain);
		return 1;}

	if (!mysql_connect(&mysql_reverse,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_reverse));
		return 1; }
	if (mysql_select_db(&mysql_reverse,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_reverse));
		mysql_close(&mysql_reverse);
		return 1;}

	if (!mysql_connect(&mysql_math,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_math));
		return 1; }
	if (mysql_select_db(&mysql_math,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_math));
		mysql_close(&mysql_math);
		return 1;}

	if (!mysql_connect(&mysql_update,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_update));
		return 1; }
	if (mysql_select_db(&mysql_update,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_update));
		mysql_close(&mysql_update);
		return 1;}

	if (!mysql_connect(&mysql_host_2,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_host_2));
		return 1; }
	if (mysql_select_db(&mysql_host_2,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_host_2));
		mysql_close(&mysql_host_2);
		return 1;}

	if (!mysql_connect(&mysql_serial,conf_host,conf_user,conf_password)){
		printf ("Could not connect. Error: %s\n", mysql_error(&mysql_serial));
		return 1; }
	if (mysql_select_db(&mysql_serial,conf_db_name)){
		printf ("Failed to select database. Error: %s\n", mysql_error(&mysql_serial));
		mysql_close(&mysql_serial);
		return 1;}
}

int get_max_ip (int sub_octet_4, int sub_bitmask)
{
	int max_hosts, total_hosts;

	if (sub_bitmask == 24) { max_hosts = 256; }
	if (sub_bitmask == 25) { max_hosts = 128; }
	if (sub_bitmask == 26) { max_hosts = 64; }
	if (sub_bitmask == 27) { max_hosts = 32; }
	if (sub_bitmask == 28) { max_hosts = 16; }
	if (sub_bitmask == 29) { max_hosts = 8; }
	if (sub_bitmask == 30) { max_hosts = 4; }
	if (sub_bitmask == 31) { max_hosts = 2; }

	total_hosts = sub_octet_4 + max_hosts;
//	printf ("total_hosts is %d\n", total_hosts);
	return total_hosts;
}

int update_domain_serial(char tmp_domain[9])
{
	char new_serial[9];
	char old_serial[9];

	strcpy (query_this_1, "select serial from domain_table where record = ");
	strcat (query_this_1, tmp_domain);
	mysql_query (&mysql_serial,query_this_1);
	result_serial = mysql_use_result(&mysql_serial);
	row_serial = mysql_fetch_row(result_serial);
	strcpy (old_serial, row_serial[0]);
	mysql_free_result(result_serial);
	strcpy (query_this_1, "select ");
	strcat (query_this_1, old_serial);
	strcat (query_this_1, " + 1");
	mysql_query (&mysql_serial, query_this_1);
	result_serial = mysql_use_result(&mysql_serial);
	row_serial = mysql_fetch_row(result_serial);
	strcpy (new_serial, row_serial[0]);
	mysql_free_result(result_serial);
	strcpy (query_this_1, "update domain_table set serial = ");
	strcat (query_this_1, new_serial);
	strcat (query_this_1, " where record = ");
	strcat (query_this_1, tmp_domain);
	mysql_query (&mysql_serial, query_this_1);
	if (troubleshooting)
	{
		printf ("Serial number was    : %s\n", old_serial);
		printf ("Serial number is now : %s\n", new_serial);
	}
	return 0;
}

void print_usage()
{
	fprintf(stderr,"\n");
	fprintf(stderr,"usage : dnstoolsng -push [-verbose]|[-troubleshooting]\n");
	fprintf(stderr,"   or : dnstoolsng -version\n\n");
	fprintf (stderr,"-verbose gives limited information, -troubleshooting gives detailed information.\n");
	fprintf (stderr,"If you do not specify -verbose or -troubleshooting it will be completely silent.\n");
	exit(1);
}

void needs_global()
{
	mysql_query (&mysql,"select needs_update from global_table");
	result_global = mysql_use_result(&mysql);
	row_global = mysql_fetch_row(result_global);
	strcpy (needs_update, row_user[0]);
	if ((verbose) || (troubleshooting))
	{
		printf ("NEEDS GLOBAL UPDATE: %s\n", needs_update);
	}
	mysql_free_result (result_global);
}

void data_dir()
{
	mysql_query (&mysql,"select data_directory from global_table");
	result_user = mysql_use_result(&mysql);
	row_user = mysql_fetch_row(result_user);
	strcpy (data_directory, row_user[0]);
	printf ("%s\n", data_directory);
	mysql_free_result (result_user);
}

void check_args(int argc, char **argv)
{
  if ((argc < 2) || (argc > 3))
  {
		print_usage();
  }

	if (! strcmp (argv[1],"-version"))
	{
		printf ("DNSTools version %s\n", version);
		printf ("Copyright (c) 1998-2002 LaCaille Technology Services\n");
		check_license();
		exit(0);
	}

	else if (! strcmp (argv[1],"-config"))
	{
		printf ("MYSQL INFO\n");
		printf ("==================================\n");
		printf ("       HOST : %s\n", conf_host);
		printf ("   DATABASE : %s\n", conf_db_name);
		printf ("       USER : %s\n", conf_user);
		printf ("   PASSWORD : (hidden)\n");
		printf ("LICENSE KEY : %s\n", conf_license_key);
		printf ("==================================\n");
		gethostname(hostname, 256);
		printf ("Your hostname is: %s\n", hostname);
		exit(0);
	}

	else if (! strcmp (argv[1],"-needs_global"))
	{
		needs_global();
		exit(0);
	}

	else if (! strcmp (argv[1],"-datadir"))
	{
		data_dir();
		exit(0);
	}

	else if (! strcmp (argv[1],"-push"))
	{
		if ((argc == 3) && (! strcmp (argv[2],"-verbose")))
		{
			verbose = 1;
		}
		else if ((argc == 3) && (! strcmp (argv[2],"-troubleshooting")))
		{
			troubleshooting = 1;
		}
	}

	else
	{
		print_usage();
	}
}

void noncompliant()
{
	printf ("\n\n*** LICENSE ERROR ***\n\n");
	printf ("Sorry, but I cannot create the BIND configuration files\n");
	printf ("until you are compliant with your license agreement.\n\n");
	printf ("If you are using the free version, you have exceeded the maximum number\n");
	printf ("of hosts, users or domains allowed in this version. Please see the website\n");
	printf ("for information on how to purchase additional licenses (www.dnstools.com)\n");
}

void check_users()
{
	if (troubleshooting)
	{
		printf ("\n===================\n");
		printf ("Listing all users\n");
		printf ("===================\n\n");
	}
 	mysql_query (&mysql,"select record, user_name from user_table");
	result_user = mysql_use_result(&mysql);
	while ((row_user = mysql_fetch_row(result_user)))
	{
		if (troubleshooting)
		{
			printf ("I see that user %s is record # %s...\n", row_user[1], row_user[0]);
		}
		num_users ++;
	}

	mysql_free_result(result_user);

	if ((verbose) || (troubleshooting))
	{
		printf ("Users found  : %d\n",  num_users);
	}

	if (num_users > allowed_users)
	{
		noncompliant();
		exit (3);
	}
}

void check_hosts()
{
	if (troubleshooting)
	{
		printf ("\n===================\n");
		printf ("Listing all hosts\n");
		printf ("===================\n\n");
	}
 	mysql_query (&mysql,"select record, host_name from host_table");
	result_host = mysql_use_result(&mysql);
	while ((row_host = mysql_fetch_row(result_host)))
	{
		if (troubleshooting)
		{
			printf ("I see that %s is record # %s...\n", row_host[1], row_host[0]);
		}
		num_hosts ++;
	}
	mysql_free_result(result_host);

	if ((verbose) || (troubleshooting))
	{
		printf ("Hosts found  : %d\n",  num_hosts);
	}

	if (num_hosts > allowed_hosts)
	{
		noncompliant();
		exit (3);
	}
}

void check_domains()
{
	if (troubleshooting)
	{
		printf ("\n===================\n");
		printf ("Listing all domains\n");
		printf ("===================\n\n");
	}
 	mysql_query (&mysql,"select record, domain_name, needs_update from domain_table where is_active = \"YES\"");
	result_domain = mysql_use_result(&mysql);
	while ((row_domain = mysql_fetch_row(result_domain)))
	{
		if (! strcmp (row_domain[2],"YES"))
		{
			strcpy(needs_update,"DOES");
		}
		else
		{
			strcpy(needs_update,"DOES NOT");
		}
		if (troubleshooting)
		{
			printf ("I see that %s is record # %s and %s need an update ...\n", row_domain[1], row_domain[0], needs_update);
		}
		num_domains ++;
	}
	mysql_free_result(result_domain);

	if ((verbose) || (troubleshooting))
	{
		printf ("Domains found: %d\n",  num_domains);
	}

	if (num_domains > allowed_domains)
	{
		noncompliant();
		printf ("*** You may also change some of your domains to NOT ACTIVE in the GUI\n");
		printf ("    to lower your total # of active domains.\n\n");
		exit (3);
	}
}

void read_config()
{
	if ((fp = fopen(conf_file, "r"))==NULL)
	{
		printf ("Cannot open file '%s'. Aborting.\n\n", conf_file);
		exit(1);
	}
	else
	{
		fscanf (fp, "%s", &conf_host);
		fscanf (fp, "%s", &conf_db_name);
		fscanf (fp, "%s", &conf_user);
		fscanf (fp, "%s", &conf_password);
		fscanf (fp, "%s", &conf_license_key);
	}
	fclose (fp);
}

int create_bind_conf()
{
	if (troubleshooting)
	{
		printf ("Entering create_bind_conf routine.\n");
	}
 	mysql_query (&mysql,"select data_directory, bind_configuration_file, active_transfers_per_server, total_active_transfers, duration_of_transfer, data_segment_size, data_segment_size_scale, stack_size, stack_size_scale, core_size, core_size_scale, max_open_files, cleaning_interval, statistics_interval, recursive_server, forward_only_server, cache_only_server from global_table");
	result_global = mysql_use_result(&mysql);
	row_global = mysql_fetch_row(result_global);
	if (! row_global)
	{
		printf ("You have not yet configured the DNSTools configuration settings.\nPlease do so before running this program.\n");
		printf ("\nThese settings are located from the main DNSTools Configuration screen through the web interface.\n");
		printf ("\nExiting...\n");
		mysql_free_result (result_global);
		exit (1);
	}
	strcpy (data_directory,row_global[0]);
	strcpy (bind_configuration_file,row_global[1]);
	strcpy (active_transfers_per_server,row_global[2]);
	strcpy (total_active_transfers,row_global[3]);
	strcpy (duration_of_transfer,row_global[4]);
	strcpy (data_segment_size,row_global[5]);
	strcpy (data_segment_size_scale,row_global[6]);
	strcpy (core_size,row_global[7]);
	strcpy (core_size_scale,row_global[8]);
	strcpy (stack_size,row_global[9]);
	strcpy (stack_size_scale,row_global[10]);
	strcpy (max_open_files,row_global[11]);
	strcpy (cleaning_interval,row_global[12]);
	strcpy (statistics_interval,row_global[13]);
	strcpy (recursive_server,row_global[14]);
	strcpy (forward_only_server,row_global[15]);
	strcpy (cache_only_server,row_global[16]);
	mysql_free_result(result_global);
	if((fp = fopen(bind_configuration_file, "w"))==NULL)
	{
		printf ("Cannot open file '%s'. Aborting.\n\n", bind_configuration_file);
		exit (4);
	}

	/* First we set up all of the global BIND configuration settings */

	fprintf(fp, "\n\n#*************************************************\n");
	fprintf(fp, "#BIND configuration file - Created by DNSTools 2.0\n");
	fprintf(fp, "#*************************************************\n\n");
	fprintf(fp, "options {\n");
	fprintf(fp, "    directory \"%s\";\n", data_directory);

//** YOU CAN ENABLE THESE BY HAND.  MANY VERSIONS OF BIND OPERATE
//   DIFFERENTLY DEPENDING ON YOUR OPERATING SYSTEM.
//   THESE VALUES ARE READ FROM THE DATABASE AND ARE USABLE.
//
//	fprintf(fp, "    transfers-per-ns %s;\n", active_transfers_per_server);
//	fprintf(fp, "    transfers-in %s;\n", total_active_transfers);
//	fprintf(fp, "    max-transfer-time-in %s;\n", duration_of_transfer);
//	fprintf(fp, "    datasize %s%s;\n", data_segment_size, data_segment_size_scale);
//	fprintf(fp, "    stacksize %s%s;\n", stack_size, stack_size_scale);
//	fprintf(fp, "    coresize %s%s;\n", core_size, core_size_scale);
//	fprintf(fp, "    files %s;\n", max_open_files);
//	fprintf(fp, "    cleaning-interval %s;\n", cleaning_interval);
//	fprintf(fp, "    statistics-interval %s;\n", statistics_interval);
//	if (! strcmp(recursive_server,"NO"))
//	{
//		strcpy (recursive_server,"no");
//		fprintf(fp, "    recursion no;\n");
//		fprintf(fp, "    fetch-glue no;\n");
//	}

	fprintf(fp, "};\n");

	/* Now we cycle through all of the domain information */

 	mysql_query (&mysql,"select domain_name, ns_type, zone_transfer_ip from domain_table order by domain_name");
	result_domain = mysql_use_result(&mysql);
	while (row_domain = mysql_fetch_row(result_domain))
	{
		strcpy (domain_name,row_domain[0]);
		strcpy (ns_type,row_domain[1]);
		strcpy (zone_transfer_ip,row_domain[2]);
		strcpy (parent_domain_record,row_domain[3]);

		if (! strcmp(ns_type,"PRIMARY"))
		{
			fprintf (fp, "zone \"%s\" in {\n", domain_name);
			fprintf (fp, "    type master;\n");
			fprintf (fp, "    file \"%s/db.%s\";\n", data_directory, domain_name);
			fprintf (fp, "};\n\n");
		}
		else if (! strcmp(ns_type, "SECONDARY"))
		{
			fprintf (fp, "zone \"%s\" in {\n", domain_name);
			fprintf (fp, "    type slave;\n");
			fprintf (fp, "    file \"%s/db.%s\";\n", data_directory, domain_name);
			fprintf (fp, "    masters { %s; };\n", zone_transfer_ip);
			fprintf (fp, "};\n\n");
		}
	}

	mysql_free_result(result_domain);

	fprintf (fp, "zone \".\" in {\n");
	fprintf (fp, "    type hint;\n");
	fprintf (fp, "    file \"db.cache\";\n");
	fprintf (fp, "};\n\n");

	// Now we go through the IP ranges and do reverse IP domains

	// First the primary reverse zones
	
	mysql_query (&mysql_ip_range,"select ip_range_table.octet_1, ip_range_table.octet_2, ip_range_table.octet_3 from ip_range_table, domain_table where ip_range_table.points_to_domain = domain_table.record and domain_table.ns_type = \"PRIMARY\"");
	result_ip_range = mysql_use_result(&mysql_ip_range);
	while (row_ip_range = mysql_fetch_row(result_ip_range))
	{
		strcpy (octet_1, row_ip_range[0]);
		strcpy (octet_2, row_ip_range[1]);
		strcpy (octet_3, row_ip_range[2]);
		fprintf (fp, "zone \"%s.%s.%s.in-addr.arpa\" in {\n", octet_3, octet_2, octet_1);
		fprintf (fp, "	type master;\n");
		fprintf (fp, "	file \"%s/db.rev.%s.%s.%s\";\n", data_directory, octet_1, octet_2, octet_3);
		fprintf (fp, "};\n\n");
	}
	if (troubleshooting)
	{
		printf ("Now exiting create_bind_conf routine.\n");
	}
}

int create_soa_files()
{
	if (troubleshooting)
	{
		printf ("Now entering create_soa_files routine\n");
	}

	/* Now we create each of the db.domain_name files that
	 * contain the actual hosts in them */

	/* First we update the serial # in each domain that requires and update */

	strcpy (query_this_1,"select record from domain_table where ns_type = \"PRIMARY\" and needs_update = \"YES\"");
 	mysql_query (&mysql,query_this_1);
	result_domain = mysql_use_result(&mysql);
	while (row_domain = mysql_fetch_row(result_domain))
	{
		strcpy (domain_record, row_domain[0]);
		update_domain_serial (domain_record);
	}

	/* Now that we have an updated serial number, we create the
	 * header of the SOA file */

	strcpy (query_this_1,"select record, domain_name, email, serial, default_retry, default_refresh, default_expire, default_ttl, ns_type from domain_table where ns_type = \"PRIMARY\" and needs_update = \"YES\"");
 	mysql_query (&mysql,query_this_1);
	result_domain = mysql_use_result(&mysql);
	while (row_domain = mysql_fetch_row(result_domain))
	{
		strcpy (domain_record, row_domain[0]);
		strcpy (domain_name, row_domain[1]);
		strcpy (email, row_domain[2]);
		strcpy (serial, row_domain[3]);
		strcpy (default_retry, row_domain[4]);
		strcpy (default_refresh, row_domain[5]);
		strcpy (default_expire, row_domain[6]);
		strcpy (default_ttl, row_domain[7]);
		strcpy (ns_type, row_domain[8]);

		strcpy (file_name, data_directory);
		strcat (file_name, "/db.");
		strcat (file_name, domain_name);

		if (troubleshooting)
		{
			printf ("Creating new domain file %s\n", file_name);
		}
		
		if ((fp = fopen(file_name, "w"))==NULL)
		{
			printf ("Cannot open file '%s'. Aborting.\n\n", file_name);
			mysql_free_result(result_domain);
			exit (4);
		}

		strcpy (query_this_1, "select name from ns_table where is_primary = \"YES\" and points_to_domain = ");
		strcat (query_this_1, domain_record);
		mysql_query (&mysql_name_server, query_this_1);
		result_name_server = mysql_use_result(&mysql_name_server);
		row_name_server = mysql_fetch_row(result_name_server);
		if (! row_name_server)
		{
			printf ("\n\n-=> ERROR <=-\n\nThere is no primary name server set for domain %s.\n\n", domain_name);
			printf ("You must set one primary name server for each domain or DNSTools will not work.\n");
			printf ("\nExiting.\n");
			dns_close_all_mysql();
			exit(1);
		}
		else
		{
			strcpy (ns_name, row_name_server[0]);
		}
		mysql_free_result(result_name_server);

		fprintf (fp, "$TTL %s\n", default_ttl);
		fprintf (fp, "%s. IN SOA %s. %s. (\n", domain_name, ns_name, email);
		fprintf (fp, "		%s	; Serial #\n", serial);
		fprintf (fp, "		%s	; Refresh Value\n", default_refresh);
		fprintf (fp, "		%s	; Retry Value\n", default_retry);
		fprintf (fp, "		%s	; Expire Value\n", default_expire);
		fprintf (fp, "		%s ); Time To Live Value\n", default_ttl);
		fprintf (fp, "\n\n");

		/* Next we add all of the name servers for this domain */

		fprintf (fp, "	IN TXT	\"%s\"\n\n", domain_txt);

		strcpy (query_this_1, "select name from ns_table where points_to_domain = ");
		strcat (query_this_1, domain_record);
		strcat (query_this_1, " order by name");
		mysql_query (&mysql_name_server, query_this_1);
		result_name_server = mysql_use_result(&mysql_name_server);
		while (row_name_server = mysql_fetch_row(result_name_server))
		{
			strcpy (ns_name, row_name_server[0]);
			fprintf (fp, "		IN NS	%s.\n", ns_name);
		}
		mysql_free_result(result_name_server);
		fprintf (fp, "\n");

		// NOW WE ADD ALL SUBDOMAIN NS RECORDS //

//		strcpy (query_this_1, "select record, domain_name from domain_table where subdomain_points_to_domain = ");
//		strcat (query_this_1, domain_record);
//		strcat (query_this_1, " order by domain_name");
//		mysql_query (&mysql_name_server, query_this_1);
//		result_name_server = mysql_use_result(&mysql_name_server);
//		while (row_name_server = mysql_fetch_row(result_name_server))
//		{
//			strcpy (subdomain_record, row_name_server[0]);
//			strcpy (subdomain_name, row_name_server[1]);
//			strcpy (query_this_2, "select name, ip_address from ns_table where points_to_domain = ");
//			strcat (query_this_2, subdomain_record);
//			mysql_query (&mysql_name_server_2, query_this_2);
//			result_name_server_2 = mysql_use_result(&mysql_name_server_2);
//			while (row_name_server_2 = mysql_fetch_row(result_name_server_2))
//			{
//				strcpy (sub_ns_name, row_name_server_2[0]);
//				strcpy (sub_ns_ip, row_name_server_2[1]);
//				fprintf (fp, "	%s.%s.	IN	NS	%s.\n", subdomain_name, domain_name, sub_ns_name);
//				fprintf (fp, "	%s.	IN	A	%s\n\n", sub_ns_name, sub_ns_ip);
//			}
//		}

		fprintf (fp, "\n\n");

		// NOW WE ADD THE GLOBAL MX RECORDS //

	 	strcpy (query_this_2, "select name, priority from mx_table where points_to_domain = ");
		strcat (query_this_2, domain_record);
		strcat (query_this_2, " order by priority");
	 	mysql_query (&mysql_mail_exchange, query_this_2);
		result_mail_exchange = mysql_use_result(&mysql_mail_exchange);
		while (row_mail_exchange = mysql_fetch_row(result_mail_exchange))
		{
			strcpy (mx_name, row_mail_exchange[0]);
			strcpy (mx_priority, row_mail_exchange[1]);
			fprintf (fp, "%s.	IN MX	%s %s.\n", domain_name, mx_priority, mx_name);
		}
		mysql_free_result(result_mail_exchange);

		fprintf (fp, "\n");

		fprintf (fp, "\n;==================================================\n");
		fprintf (fp, ";Listing all name servers as hosts first.\n");
		fprintf (fp, ";==================================================\n\n");

		strcpy (query_this_1, "select name, ip_address from ns_table where points_to_domain = ");
		strcat (query_this_1, domain_record);
		strcat (query_this_1, " order by name");
		mysql_query (&mysql_name_server, query_this_1);
		result_name_server = mysql_use_result(&mysql_name_server);
		while (row_name_server = mysql_fetch_row(result_name_server))
		{
			strcpy (ns_name, row_name_server[0]);
			strcpy (ns_ip, row_name_server[1]);
			fprintf (fp, "%s.	IN A	%s\n", ns_name, ns_ip);
		}
		mysql_free_result(result_name_server);
		fprintf (fp, "\n");

		// *** NOW WE ADD ALL OF THE HOSTS, FOLLOWED BY THEIR ROUND ROBIN IP
		// ADDRESSES, THEIR CNAME ALIASES AND THEIR MX RECORDS 
		
		fprintf (fp, "\n;==================================================\n");
		fprintf (fp, ";Listing all hosts in ascending order by IP address\n");
		fprintf (fp, ";==================================================\n\n");
	
	 	strcpy (query_this_2, "select record, host_name, octet_1, octet_2, octet_3, octet_4, hinfo_1, hinfo_2, ttl from host_table where points_to_domain = ");
		strcat (query_this_2, domain_record);
		strcat (query_this_2, " order by octet_1, octet_2, octet_3, octet_4");
	
	 	mysql_query (&mysql_host, query_this_2);
		result_host = mysql_use_result(&mysql_host);
		while (row_host = mysql_fetch_row(result_host))
		{
			strcpy (host_record, row_host[0]);
			strcpy (host_name, row_host[1]);
			strcpy (octet_1, row_host[2]);
			strcpy (octet_2, row_host[3]);
			strcpy (octet_3, row_host[4]);
			strcpy (octet_4, row_host[5]);
			strcpy (hinfo_1, row_host[6]);
			strcpy (hinfo_2, row_host[7]);
			strcpy (ttl, row_host[8]);
			if (! strcmp (ttl, "0"))
			{
				strcpy (ttl, default_ttl);
			}

			fprintf (fp, "%s	%s	IN A	%s.%s.%s.%s\n", host_name, ttl, octet_1, octet_2, octet_3, octet_4);
	
			/* Next we add all of the round robin IP addresses */

		 	strcpy (query_this_3, "select octet_1, octet_2, octet_3, octet_4 from round_robin_table where points_to_host = ");
			strcat (query_this_3, host_record);
			strcat (query_this_3, " order by octet_1, octet_2, octet_3, octet_4");

		 	mysql_query (&mysql_round_robin, query_this_3);
			result_round_robin = mysql_use_result(&mysql_round_robin);
			while (row_round_robin = mysql_fetch_row(result_round_robin))
			{
				fprintf (fp, "%s	%s	IN A	%s.%s.%s.%s\n", host_name, ttl, row_round_robin[0], row_round_robin[1], row_round_robin[2], row_round_robin[3]);
			}
			mysql_free_result(result_round_robin);

			/* Next we add all of the aliases */
	
		 	strcpy (query_this_3, "select alias_name from alias_table where points_to_host = ");
			strcat (query_this_3, host_record);
			strcat (query_this_3, " order by alias_name");
	
		 	mysql_query (&mysql_round_robin, query_this_3);
			result_round_robin = mysql_use_result(&mysql_round_robin);
			while (row_round_robin = mysql_fetch_row(result_round_robin))
			{
				strcpy (alias_name, row_round_robin[0]);
				fprintf (fp, "%s	IN CNAME	%s.%s.\n", alias_name, host_name, domain_name);
			}
			mysql_free_result(result_round_robin);

			/* Next we add all of the MX records */

		 	strcpy (query_this_3, "select name, priority from mx_table where points_to_host = ");
			strcat (query_this_3, host_record);
			strcat (query_this_3, " order by priority");
	
		 	mysql_query (&mysql_mail_exchange, query_this_3);
			result_mail_exchange = mysql_use_result(&mysql_mail_exchange);
			while (row_mail_exchange = mysql_fetch_row(result_mail_exchange))
			{
				strcpy (mx_name, row_mail_exchange[0]);
				strcpy (mx_priority, row_mail_exchange[1]);
				fprintf (fp, "%s	IN MX	%s %s.\n", host_name, mx_priority, mx_name);
			}
			mysql_free_result(result_mail_exchange);

			/* Next we add the HINFO field(s) */

			if (strcmp(hinfo_1,""))
			{
				fprintf (fp, "%s.%s.	IN HINFO \"%s\" ", host_name, domain_name, hinfo_1);
				if (strcmp (hinfo_2,""))
				{
					fprintf (fp, "\"%s\"", hinfo_2);
				}
				fprintf (fp, "\n");
			}

			fprintf (fp, "\n");
		}

		/* Finally, close this file and move onto the next one */
		fclose (fp);
		total_domains_updated ++;

		/* Set the 'needs_update' flag to NO so we don't go through this routine
		 * for this specific domain again next time (unless it's updated of course) */

		strcpy (query_this_1,"update domain_table set needs_update = \"NO\" where record = ");
		strcat (query_this_1, domain_record);
 		mysql_query (&mysql_update,query_this_1);
	}

	if ((verbose) || (troubleshooting))
	{
		printf ("Total domains updated: %d\n\n", total_domains_updated);
	}

	mysql_free_result (result_ip_range);

	if (troubleshooting)
	{
		printf ("Now checking to see if main_dnstools_domain is set...\n");
	}

	mysql_query (&mysql_domain, "select count(*) as string_count from domain_table where main_dnstools_domain = \"YES\"");
	result_domain = mysql_use_result(&mysql_domain);
	row_domain = mysql_fetch_row(result_domain);
	strcpy (string_count, row_domain[0]);
	mysql_free_result (result_domain);
	if (! strcmp (string_count, "0"))
	{
		printf ("\n\n-=> ERROR <=-\n\nYou have not set a domain to be the main DNSTools domain yet.\n");
		printf ("To do this, go into the Modify Domain section and change it's status at the bottom.\n");
		printf ("You must complete this step before DNSTools will run.\n\n");
		printf ("Exiting.\n");
		dns_close_all_mysql();
		exit (1);
	}

	if (troubleshooting)
	{
		printf ("Looks as if the main_dnstools_domain is set, continuing...\n");
	}

	// Now we create the reverse mapping files
	
	if (troubleshooting)
	{
		printf ("Now we're actually retrieving the main_dnstools_domain information...\n");
	}
	mysql_query (&mysql_domain, "select record, domain_name, email, serial, default_refresh, default_retry, default_expire, default_ttl from domain_table where main_dnstools_domain = \"YES\"");
	result_domain = mysql_use_result(&mysql_domain);
	row_domain = mysql_fetch_row(result_domain);
	strcpy (domain_record, row_domain[0]);
	strcpy (domain_name, row_domain[1]);
	strcpy (email, row_domain[2]);
	strcpy (serial, row_domain[3]);
	strcpy (default_refresh, row_domain[4]);
	strcpy (default_retry, row_domain[5]);
	strcpy (default_expire, row_domain[6]);
	strcpy (default_ttl, row_domain[7]);
	mysql_free_result (result_domain);
	if (troubleshooting)
	{
		printf ("Retrieved information successfully, now retrieving ip_range_table information...\n");
	}

	strcpy (query_this_1, "select count(*) as octet_count from ip_range_table order by octet_1, octet_2, octet_3");
	mysql_query (&mysql_ip_range, query_this_1);
	result_ip_range = mysql_use_result(&mysql_ip_range);
	row_ip_range = mysql_fetch_row(result_ip_range);
	strcpy (octet_count, row_ip_range[0]);
	mysql_free_result (result_ip_range);
	if (troubleshooting)
	{
		printf ("Found -%s- number of IP ranges.\n", octet_count);
	}

	if (! strcmp (octet_count, "0"))
	{
		printf ("Uh oh, there are no IP ranges defined for any of your domains!\n");
		printf ("You must have at least one IP range for your DNS setup.\n");
		printf ("Please see the online documentation under IP RANGES for more info.\n");
		printf ("Exiting.\n");
		dns_close_all_mysql();
		exit(1);
	}

	strcpy (query_this_1, "select octet_1, octet_2, octet_3 from ip_range_table order by octet_1, octet_2, octet_3");

	mysql_query (&mysql_ip_range, query_this_1);
	result_ip_range = mysql_use_result(&mysql_ip_range);
	if (troubleshooting)
	{
		printf ("Got ip range information, now creating reverse zone files...\n");
	}

	while (row_ip_range = mysql_fetch_row(result_ip_range))
	{
		strcpy (octet_1,row_ip_range[0]);
		strcpy (octet_2,row_ip_range[1]);
		strcpy (octet_3,row_ip_range[2]);
		strcpy (file_name, data_directory);
		strcat (file_name, "/db.rev.");
		strcat (file_name, octet_1);
		strcat (file_name, ".");
		strcat (file_name, octet_2);
		strcat (file_name, ".");
		strcat (file_name, octet_3);

		if (troubleshooting)
		{
			printf ("Creating new reverse domain file %s\n", file_name);
		}

		if ((fp = fopen(file_name, "w"))==NULL)
		{
			printf ("Cannot open file named '%s'. Aborting.\n\n", file_name);
			exit (4);
		}
		else
		{
			if (troubleshooting)
			{
				printf ("Opened file successfully: %s\n", file_name);
			}
		}

		strcpy (query_this_1, "select count(*) as num_records from ns_table where is_primary = \"YES\" and points_to_domain = ");
		strcat (query_this_1, domain_record);
		mysql_query (&mysql_name_server, query_this_1);
		result_name_server = mysql_use_result (&mysql_name_server);
		row_name_server = mysql_fetch_row(result_name_server);
		strcpy (num_records, row_name_server[0]);

		if (! strcmp(num_records, "0"))
		{
			printf ("\n\n-=> ERROR <=-\n\nThere is no primary name server set for domain %s.\n\n", domain_name);
			printf ("You must set one primary name server for each domain or DNSTools will not work.\n");
			printf ("\nExiting.\n");
			dns_close_all_mysql();
			exit(1);
		}

		mysql_free_result (result_name_server);

		strcpy (query_this_1, "select name, ip_address from ns_table where is_primary = \"YES\" and points_to_domain = ");
		strcat (query_this_1, domain_record);

		mysql_query (&mysql_name_server, query_this_1);
		result_name_server = mysql_use_result (&mysql_name_server);
		row_name_server = mysql_fetch_row(result_name_server);

		strcpy (name_server_name, row_name_server[0]);
		strcpy (ns_ip, row_name_server[1]);
		mysql_free_result (result_name_server);

		fprintf (fp, "$TTL %s\n", default_ttl);
		fprintf (fp, "%s.%s.%s.in-addr.arpa. IN SOA %s. %s. (\n", octet_3, octet_2, octet_1, ns_name, email);
		fprintf (fp, "	%s ; Serial\n", serial);
		fprintf (fp, "	%s ; Refresh\n", default_refresh);
		fprintf (fp, "	%s ; Retry\n", default_retry);
		fprintf (fp, "	%s ; Expire\n", default_expire);
		fprintf (fp, "	%s); TTL\n\n", default_ttl);
		fprintf (fp, "	IN TXT	\"%s\"\n\n", domain_txt);

		fprintf (fp, ";\n; *********************************************************\n");
		fprintf (fp, "; * Name Server Entries.  These can be modified by changing\n");
		fprintf (fp, "; * which domain has the 'main_dnstools_domain' flag set.\n");
		fprintf (fp, "; *********************************************************\n;\n\n");

		strcpy (query_this_2, "select ns_table.name from ns_table, domain_table where ns_table.points_to_domain = domain_table.record and domain_table.main_dnstools_domain = \"YES\"");
		mysql_query (&mysql_name_server, query_this_2);
		result_name_server = mysql_use_result (&mysql_name_server);
		while (row_name_server = mysql_fetch_row(result_name_server))
		{
			strcpy (name_server_name, row_name_server[0]);
			fprintf (fp, "	IN NS	%s.\n", name_server_name);
		}
		mysql_free_result (result_name_server);
		fprintf (fp, "\n;\n");
		fprintf (fp, "; *****************************************************\n");
		fprintf (fp, "; * Host entries.  Only entries that have a registered\n");
		fprintf (fp, "; * IP address are listed here.\n");
		fprintf (fp, "; *****************************************************\n\n");

		strcpy (query_this_1, "select count(*) from host_table, domain_table where octet_1 = ");
		strcat (query_this_1, octet_1);
		strcat (query_this_1, " and host_table.octet_2 = ");
		strcat (query_this_1, octet_2);
		strcat (query_this_1, " and host_table. octet_3 = ");
		strcat (query_this_1, octet_3);
		strcat (query_this_1, " and host_table.registered_ip = \"YES\" and host_table.points_to_domain = domain_table.record");

		mysql_query (&mysql_host, query_this_1);
		result_host = mysql_use_result (&mysql_host);
		row_host = mysql_fetch_row(result_host);
		strcpy (num_records, row_host[0]);
//		printf ("num_records: %s\n", num_records);
		mysql_free_result (result_host);
//		printf ("Main Branch\n");
		if (! strcmp(num_records, "0"))
		{
//		printf ("Branch 1\n");
			if (troubleshooting)
			{
				printf ("Non-critical warning: There are no hosts with registered IP addresses in the %s.%s.%s network.\n", octet_1, octet_2, octet_3);
				printf ("Closing file and continuing on.\n");
			}
			fclose(fp);
		}
		else
		{
//			printf ("Branch 2\n");
			strcpy (query_this_1, "select host_table.host_name, host_table.octet_4, domain_table.domain_name from host_table, domain_table where host_table.octet_1 = ");
			strcat (query_this_1, octet_1);
			strcat (query_this_1, " and host_table.octet_2 = ");
			strcat (query_this_1, octet_2);
			strcat (query_this_1, " and host_table.octet_3 = ");
			strcat (query_this_1, octet_3);
			strcat (query_this_1, " and host_table.registered_ip = \"YES\" and host_table.points_to_domain = domain_table.record order by octet_4");
	
			mysql_query (&mysql_host, query_this_1);
			result_host = mysql_use_result (&mysql_host);
			while (row_host = mysql_fetch_row(result_host))
			{
				if (row_host)
				{
					strcpy (host_name, row_host[0]);
					strcpy (octet_4, row_host[1]);
					strcpy (domain_name, row_host[2]);
					fprintf (fp, "%s.%s.%s.%s.in-addr.arpa.	IN PTR %s.%s.\n", octet_4, octet_3, octet_2, octet_1, host_name, domain_name);
				}
			}
			mysql_free_result (result_host);
			fclose(fp);
		}
//		printf ("End of branch\n");
	}
	if (troubleshooting)
	{
		printf ("Now exiting create_soa_files routine.\n");
	}
}

int main(int argc, char **argv)
{
	read_config();

	check_args(argc, argv);

	dns_init_all_mysql();

	check_license();
	check_domains();
	check_users();
	check_hosts();	

	if ((verbose) || (troubleshooting))
	{
		printf ("\nPushing DNS files now\n\n");
	}

	create_bind_conf();
	create_soa_files();

	dns_close_all_mysql();

  exit(0);
  return 0;
}
