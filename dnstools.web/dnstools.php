<?php

//* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
//***************************************************************
//* DNSTOOLS COPYRIGHT (c) 2002 Damon LaCaille                  *
//***************************************************************
//* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


// FIRST WE START A SESSION WITH THE USER SO WE CAN KEEP TRACK OF THINGS

session_start();

// NOW WE SET UP SOME COMMON VARIABLES THAT WILL
// BE USED THROUGHOUT THE PROGRAM

$DEMO="NO";

$conf_file = "/etc/dnstools.conf";

$fp = fopen ($conf_file, "r");
fscanf ($fp, "%s\n", $MYSQL_SERVER);
fscanf ($fp, "%s\n", $DATABASE);
fscanf ($fp, "%s\n", $MYSQL_USER);
fscanf ($fp, "%s\n", $MYSQL_PASSWORD);
fscanf ($fp, "%s\n", $conf_license_key);
fclose ($fp);


$version = "2.0.2";
$website = "http://dnstools.sourceforge.net";
$app_name = "DNSTools";
$company_logo_image = "/dnstools/images/dnstools-title.gif";
$access_red = "#CC2222";
$access_green = "#22CC22";
$access_no = "<STRONG><FONT COLOR=\"$access_red\">NO</FONT></STRONG>";
$access_yes = "<STRONG><FONT COLOR=\"$access_green\">YES</FONT></STRONG>";
$header_bg_color = "#FFFFFF";
$cell_bg_color_1 = "#B1AAFF";
$cell_bg_color_2 = "#A9BFFF";
$highlight_color = "#992277";
$text_color = "#000000";

$ip_address = getenv("REMOTE_ADDR");
$http_user_agent = getenv("HTTP_USER_AGENT");
$agent=$http_user_agent;

// NOW WE SETUP THE FUNCTIONS

function update_global ()
{
	$sql = "update global_table SET needs_update = \"YES\"";
	$result = mysql_query($sql) or die (mysql_error());
	return;
}

function update_ip_serial ($record)
{
	$sql = "update ip_range_table SET needs_update = \"YES\" where record = \"$record\"";
	$result = mysql_query($sql) or die (mysql_error());
	return;
}

function update_domain_serial ($record)
{
	$sql = "update domain_table SET needs_update = \"YES\" where record = \"$record\"";
	$result = mysql_query($sql) or die (mysql_error());
	return;
}

function right_justified_help_link ($subject)
{
	$display_block="
		<TABLE BORDER=0 WIDTH=410>
			<TR>
				<TD ALIGN=RIGHT VALIGN=CENTER><a href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=$subject','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><img src=\"images/icons/help-button.gif\" BORDER=0></img></a></TD>
			</TR>
		</TABLE>";
	printf ("$display_block");
	return;
}

function write_log ($requested_level, $user, $text)
{
	$request_config = mysql_query ("select log_file_name, log_level from global_table");
	$result_config = mysql_fetch_array($request_config);
	$local_log_file_name = $result_config[log_file_name];
	$local_log_level = $result_config[log_level];

	if ($local_log_file_name == "")
	{
		printf ("Log file not accessible.");
		return;
	}

	if ($requested_level <= $local_log_level)
	{
	  $date_time = date("M j Y H:i:s");
	  $log_write = @fopen($local_log_file_name, "a+") or die ("Could not open logfile $local_log_file_name!<P>");
		@fwrite($log_write, "$date_time|$local_log_level|$user|$text\n") or die ("Couldn't write to file $local_log_file_name!<P>");
	}
}

function which_ip_ranges ($temp_domain_record)
{
	$num_ip_ranges = 0;
	$request_ip_ranges = mysql_query ("select record from ip_range_table where points_to_domain = \"$temp_domain_record\" order by octet_1, octet_2, octet_3, octet_4");
	while ($result_ip_ranges = mysql_fetch_array($request_ip_ranges))
	{
		$ip_range_list[$num_ip_ranges] = $result_ip_ranges[record];
		$num_ip_ranges ++;
	}
	return $ip_range_list;
}

function which_primary_domains($temp_group_list, $temp_user_dnstools_administrator)
{
	if (($temp_group_list == "") && ($temp_user_dnstools_administrator != "YES"))
	{
		return;
	}
	if ($temp_user_dnstools_administrator == "YES")
	{
		$num_domains = 0;
		$request_all_domain_names = mysql_query ("select record from domain_table where ns_type = \"PRIMARY\" order by domain_name");
		while ($result_all_domain_names = mysql_fetch_array($request_all_domain_names))
		{
			$domain_list[$num_domains] = $result_all_domain_names[record];
			$num_domains ++;
		}
		return $domain_list;
	}
	else
	{
		$num_domains = 0;
		foreach ($temp_group_list as $current_group)
		{
			$request_domains = mysql_query ("select record from domain_table where points_to_group = \"$current_group\" and ns_type = \"PRIMARY\" order by domain_name");
			while ($result_domains = mysql_fetch_array($request_domains))
			{
				$domain_list[$num_domains] = $result_domains[record];
				$num_domains ++;
			}
		}
		return $domain_list;
	}
}

function which_domains($temp_group_list, $temp_user_dnstools_administrator)
{
	if (($temp_group_list == "") && ($temp_user_dnstools_administrator != "YES"))
	{
		return;
	}
	if ($temp_user_dnstools_administrator == "YES")
	{
		$num_domains = 0;
		$request_all_domain_names = mysql_query ("select record from domain_table order by domain_name");
		while ($result_all_domain_names = mysql_fetch_array($request_all_domain_names))
		{
			$domain_list[$num_domains] = $result_all_domain_names[record];
			$num_domains ++;
		}
		return $domain_list;
	}
	else
	{
		$num_domains = 0;
		foreach ($temp_group_list as $current_group)
		{
			$request_domains = mysql_query ("select record from domain_table where points_to_group = \"$current_group\" order by domain_name");
			while ($result_domains = mysql_fetch_array($request_domains))
			{
				$domain_list[$num_domains] = $result_domains[record];
				$num_domains ++;
			}
		}
		return $domain_list;
	}
}

function which_groups($temp_user_record, $temp_user_dnstools_administrator)
{
	if ($temp_user_dnstools_administrator == "YES")
	{
		$sql = "select points_to_group from group_membership_table order by points_to_group";
	}
	else
	{
		$sql = "select points_to_group from group_membership_table where points_to_user = \"$temp_user_record\" order by points_to_group";
	}
	$request_groups = mysql_query($sql);
	$num_groups = 0;
	while ($result_groups = mysql_fetch_array($request_groups))
	{
		$group_list[$num_groups] = $result_groups[points_to_group];
		$num_groups ++;
	}
	return $group_list;
}


function endpage()
{
  $display_block="
	    </TD>
		</TR>
	</TABLE>
  </BODY></HTML>\n";
  printf ("$display_block");
}

function blank_page()
{
	$display_block="
		This page intentionally left blank for coding purposes.";
	printf ("$display_block");
	endpage();
	exit;
}

function get_max_ip ($octet_4, $bitmask)
{
	if ($bitmask == 24) { $max_hosts = 256; }
	if ($bitmask == 25) { $max_hosts = 128; }
	if ($bitmask == 26) { $max_hosts = 64; }
	if ($bitmask == 27) { $max_hosts = 32; }
	if ($bitmask == 28) { $max_hosts = 16; }
	if ($bitmask == 29) { $max_hosts = 8; }
	if ($bitmask == 30) { $max_hosts = 4; }
	if ($bitmask == 31) { $max_hosts = 2; }
	$max_ip = $octet_4 + $max_hosts;
	return $max_ip;
}

function check_valid_mx_priority ($tmp_mx)
{
  if (($tmp_mx < 1) || (! $tmp_mx))
	{
		$display_block="
			MX Priority value illegal.  Must be a number greater than 0";
		printf ("$display_block");
		endpage();
		exit;
	}
}

function hostname_must_have_dots ($name)
{
	if (! (preg_match ("/[\.]/", $name)))
	{
		$display_block="
			Your name \"$name\" must be fully qualified.<br><p>For example, instead of just using <strong>name</strong>, use <strong>name.company.com</strong>.</p>";
		printf ("$display_block");
		endpage();
		exit;
	}
}

function check_valid_full_hostname ($name)
{
	if (preg_match ("/[^a-z0-9\-\.]/", $name))
	{
		$display_block="
			$name contains <font color=red>invalid or illegal</font> characters.
			Fully qualified hostnames may only contain lower case letters (a-z), numbers (0-9), dashes (-) and dots (.)";
		printf ("$display_block");
		endpage();
		exit;
	}
	else if (($name == "") || (! $name))
	{
		$display_block="
			You did not enter all of the required information.";
		printf ("$display_block");
		endpage();
		exit;
	}
	else
	{
		return;
	}
}

function check_valid_domain ($name)
{
	if (preg_match ("/[^a-z0-9\-\.]/", $name))
	{
		$display_block="
			$name contains <font color=red>invalid or illegal</font> characters.
			Domain names may only contain lower case letters (a-z), numbers (0-9), dashes (-) and dots (.)";
		printf ("$display_block");
		endpage();
		exit;
	}
	if (! preg_match ("/\./", $name))
	{
		$display_block="
			Invalid domain name.  A valid domain name entry must include a domain and an extension.";
		printf ("$display_block");
		endpage();
		exit;
	}
	return;
}

function check_valid_alias_name ($alias_name)
{
	if (preg_match ("/[^a-z0-9\-]/", $alias_name))
	{
		$display_block="
			$alias_name contains <font color=red>invalid or illegal</font> characters.
			Host names and alias names may only contain lower case letters (a-z), numbers (0-9) and dashes (-).";
		printf ("$display_block");
		endpage();
		exit;
	}
	else if (($alias_name == "") || (! $alias_name))
	{
		$display_block="
			You did not enter an alias name.";
		printf ("$display_block");
		endpage();
		exit;
	}
	else
	{
		return;
	}
}

function check_valid_hostname ($hostname)
{
	if (preg_match ("/[^a-z0-9\-]/", $hostname))
	{
		$display_block="
			$hostname contains <font color=red>invalid or illegal</font> characters.<br><p>
			Host names and alias names may only contain lower case letters (a-z), numbers (0-9) and dashes (-).\n";
		printf ("$display_block");
		endpage();
		exit;
	}
	else
	{
		return;
	}
}

function check_valid_ip ($octet_1, $octet_2, $octet_3, $octet_4)
{
	$is_bad = 0;
	if (preg_match ("/[^0-9]/", $octet_1)) { $is_bad = 1; }
	if (preg_match ("/[^0-9]/", $octet_2)) { $is_bad = 1; }
	if (preg_match ("/[^0-9]/", $octet_3)) { $is_bad = 1; }
	if (preg_match ("/[^0-9]/", $octet_4)) { $is_bad = 1; }
	if (($octet_1 < 0) || ($octet_1 > 255) || ($octet_2 < 0) || ($octet_2 > 255) || ($octet_3 < 0) || ($octet_3 > 255) || ($octet_4 < 0) || ($octet_4 > 255))
	{
		$is_bad = 1;
	}
	if (($octet_1 == "") || ($octet_2 == "") || ($octet_3 == "") || ($octet_4 == ""))
	{
		$is_bad = 1;
	}
	if ($is_bad == 1)
	{
		printf ("<P><STRONG><FONT COLOR=RED>INVALID IP ADDRESS</FONT></STRONG><P>You entered an invalid IP address or IP range.");
		printf ("Your Info: $octet_1.$octet_2.$octet_3.$octet_4\n");
		endpage();
		exit;
	}
	return;
}

function delete_domain_all_host ($record)
{
	$sql = "select record from host_table where points_to_domain = \"$record\"";
	$request_hosts = mysql_query ($sql);
	$num_rows = mysql_num_rows ($request_hosts);
	while ($result_hosts = mysql_fetch_array ($request_hosts))
	{
		$host_record = $result_hosts[record];
		force_delete_host ($host_record, $user_user_name);
	}
	return $num_rows;
}

function delete_domain_all_ip_range ($record, $user)
{
	$sql = "select * from ip_range_table where points_to_domain =\"$record\"";
	$request_rows = mysql_query ($sql);
	$num_rows = mysql_num_rows ($request_rows);
	$result_rows = mysql_fetch_array ($request_rows);
	$sql = "delete from ip_range_table where points_to_domain = \"$record\"";
	$request_delete = mysql_query ($sql);
  write_log("1", "$user", "Deleted MX $name");
	return $num_rows;
}

function delete_domain_all_mx ($record, $user)
{
	$domain_name = get_domain_name($record);
	$sql = "select record, name from mx_table where points_to_domain =\"$record\"";
	$request_rows = mysql_query ($sql);
	$num_rows = mysql_num_rows ($request_rows);
	$result_rows = mysql_fetch_array ($request_rows);
	$name = $result_rows[name];
	$sql = "delete from mx_table where points_to_domain = \"$record\"";
	$request_delete = mysql_query ($sql);
  write_log("1", "$user", "Deleted all MX entries in domain $domain_name (deleted domain)");
	return $num_rows;
}

function delete_domain_all_ns ($record, $user)
{
	$sql = "select record, name from ns_table where points_to_domain =\"$record\"";
	$request_rows = mysql_query ($sql);
	$num_rows = mysql_num_rows ($request_rows);
	$result_rows = mysql_fetch_array($request_rows);
	$name = $result_rows[name];
	$request_delete = mysql_query ("delete from ns_table where points_to_domain = \"$record\"");
  write_log("1", "$user", "Deleted NS $name");
	return $num_rows;
}

function force_delete_rr($record)
{
	$sql = "select * from round_robin_table where record = \"$record\"";
	$result = mysql_query($sql);
	$octet_1 = $result[octet_1];
	$octet_2 = $result[octet_2];
	$octet_3 = $result[octet_3];
	$octet_4 = $result[octet_4];
	$sql = "delete from round_robin_table where record = \"$record\"";
	$result = mysql_query($sql) or die (mysql_error());
  write_log("1", "$user", "Deleted round robin IP $octet_1.$octet_2.$octet_3.$octet_4");
	return;
}

function force_delete_alias($record)
{
	$sql = "select * from alias_table where record = \"$record\"";
	$result = mysql_query($sql);
	$alias_name = $result[alias_name];
	$sql = "delete from alias_table where record = \"$record\"";
	$result = mysql_query($sql) or die (mysql_error());
  write_log("1", "$user", "Deleted alias $alias_name");
	return;
}

function force_delete_mx($record)
{
	$sql = "select name from mx_table where record = \"$record\"";
	$result = mysql_query ($sql);
	$name = $result[name];
	$sql = "delete from mx_table where record = \"$record\"";
	$result = mysql_query($sql) or die (mysql_error());
  write_log("1", "$user", "Deleted host specific MX $name");
	return;
}

function force_delete_host($tmp_record, $user)
{
	$sql = "select record from alias_table where points_to_host = $tmp_record";
	$request_aliases = mysql_query("$sql");
	$num_rows_returned = mysql_num_rows($request_aliases);
	if ($num_rows_returned != 0)
	{
		$request_alias_list = mysql_query ("select record, alias_name from alias_table where points_to_host = $tmp_record");
		while ($result_alias_list = mysql_fetch_array ($request_alias_list))
		{
			$alias_record = $result_alias_list[record];
			force_delete_alias ($alias_record);
		}
	}
	$request_rr = mysql_query("select * from round_robin_table where points_to_host = $tmp_record");
	$num_rows_returned = mysql_num_rows($request_rr);
	if ($num_rows_returned != 0)
	{
		$request_rr_list = mysql_query ("select record from round_robin_table where points_to_host = $tmp_record");
		while ($result_rr_list = mysql_fetch_array ($request_rr_list))
		{
			$rr_record = $result_rr_list[record];
			force_delete_rr ($rr_record);
		}
	}
	$request_mx = mysql_query("select * from mx_table where points_to_host = $tmp_record");
	$num_rows_returned = mysql_num_rows($request_mx);
	if ($num_rows_returned != 0)
	{
		$request_mx_list = mysql_query ("select record from mx_table where points_to_host = $tmp_record");
		while ($result_mx_list = mysql_fetch_array ($request_mx_list))
		{
			$mx_record = $result_mx_list[record];
			force_delete_mx ($mx_record);
		}
	}
	update_ip_serial_by_host ($tmp_record);
	$host_name = get_host_name ($tmp_record);
	$points_to_domain = get_domain_from_host ($tmp_record);
	update_domain_serial ($points_to_domain);
	$domain_name = get_domain_name ($points_to_domain);
	$sql = "delete from host_table where record = \"$tmp_record\"";
	$result = mysql_query($sql) or die (mysql_error());
  write_log("1", "$user", "Deleted host $host_name.$domain_name successfully.");
	return;
}

function update_ip_serial_by_host ($host_record)
{
#  $display_block="<P>ENTERING update_ip_serial_by_host ($host_record)";
#	printf ("$display_block");
	$request_host_info = mysql_query ("select * from host_table where record = \"$host_record\"");
	$result_host_info = mysql_fetch_array ($request_host_info);
	$octet_1 = $result_host_info[octet_1];
	$octet_2 = $result_host_info[octet_2];
	$octet_3 = $result_host_info[octet_3];
	$octet_4 = $result_host_info[octet_4];
	$points_to_domain = $result_host_info[points_to_domain];
	$registered_ip = $result_host_info[registered_ip];
	if ($registered_ip == "YES")
	{
		$request_ip_range_record = mysql_query ("select record from ip_range_table where octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and points_to_domain = \"$points_to_domain\"");
		$result_ip_range_record = mysql_fetch_array ($request_ip_range_record);
		$range_record = $result_ip_range_record [record];
		update_ip_serial ($range_record);
	}
}

function check_lock()
{
	$request_config = mysql_query ("select database_is_locked from global_table");
	$result_config = mysql_fetch_array($request_config);
	$database_is_locked = $result_config[database_is_locked];
	if ($database_is_locked == "YES")
	{
		$display_block="
			<HR>
				<STRONG><FONT COLOR=RED>NOTICE:</FONT></STRONG> The database has been locked
				by an administrator.  Please try your request again later.
			<HR>";
		printf ("$display_block");
		endpage();
		exit;
	}
}

function check_domain_privilege ($temp_domain_record, $temp_user_record, $temp_user_dnstools_administrator)
{
	if ($temp_user_dnstools_administrator == "YES")
	{
		return 0;
	}

	$request_domain_group = mysql_query ("select * from domain_table where record = \"$temp_domain_record\"");
	$result_domain_group = mysql_fetch_array ($request_domain_group);
	$domain_group = $result_domain_group[points_to_group];
	$request_user_groups = mysql_query ("select points_to_group from group_membership_table where points_to_user = \"$temp_user_record\"");
	$found_match = 0;
	while ($result_user_groups = mysql_fetch_array ($request_user_groups))
	{
		$temp_user_group = $result_user_groups[points_to_group];
		if ($domain_group == $temp_user_group)
		{
			$found_match = 1;
		}
	}
	if ($found_match == 1)
	{
#		printf ("You are authorized for this domain.");
		return 0;
	}
	else
	{
		print_insufficient();
		printf ("YOUR INFO:  DN: $temp_domain_record, UR: $temp_user_record<P>");
		endpage();
		exit;
	}
}

function print_insufficient()
{
	$display_block="
	<H3>Insufficient privileges</H3><HR WIDTH=300 ALIGN=LEFT>You do not have sufficient privileges to perform this function.";
	printf ("$display_block\n");
}

function check_login ($tmp_user_name, $tmp_user_password)
{
	$sql_query = "select count(*) as is_valid from user_table where user_name = \"$tmp_user_name\" and password = \"$tmp_user_password\"";
	$request_valid_user = mysql_query ($sql_query);
	$result_valid_user = mysql_fetch_array ($request_valid_user);
	$is_valid = $result_valid_user [is_valid];
	$user_logged_in = "false";
	$user_dnstools_administrator = "NO";

	if ($is_valid != "0")
	{
		$user_logged_in = "true";
		$request_security = mysql_query ("select dnstools_administrator from user_table where user_name = \"$user_user_name\" and password=\"$user_password\"");
		$result_security = mysql_fetch_array ($request_security);
		$user_dnstools_administrator = $result_security[dnstools_administrator];
	}
	else
	{
	  printf ("You must log in first.");
		endpage();
		exit;
	}
}

function check_administrator($admin_status, $user)
{
	if ($admin_status!="YES")
	{
		unauthorized($user);
		endpage();
		exit;
	}
}

function unauthorized($user)
{
  write_log("2", "$user", "Not authorized");
	print_insufficient();
}

function get_user_name($temp_user_record)
{
	$request_user_name = mysql_query ("select user_name from user_table where record = \"$temp_user_record\"");
	$result_user_name = mysql_fetch_array ($request_user_name);
	$temp_user_name = $result_user_name[user_name];
	return $temp_user_name;
}

function get_rr_name($ip_record)
{
	$request_host_record = mysql_query ("select points_to_host from round_robin_table where record = \"$ip_record\"");
	$result_host_record = mysql_fetch_array ($request_host_record);
	$host_record = $result_host_record[points_to_host];
	$host_name = get_host_name($host_record);
	return $host_name;
}

function get_domain_from_host($record)
{
	$request_host_record = mysql_query ("select points_to_domain from host_table where record = \"$record\"");
	$result_host_record = mysql_fetch_array ($request_host_record);
	$points_to_domain = $result_host_record[points_to_domain];
	return $points_to_domain;
}

function get_domain_from_rr($record)
{
	$request_host_record = mysql_query ("select points_to_host from round_robin_table where record = \"$record\"");
	$result_host_record = mysql_fetch_array ($request_host_record);
	$host_record = $result_host_record[points_to_host];
	$request_domain_record = mysql_query ("select points_to_domain from host_table where record = \"$host_record\"");
	$result_domain_record = mysql_fetch_array($request_domain_record);
	$domain_record = $result_domain_record[points_to_domain];
	return $domain_record;
}

function get_ip($ip_record)
{
	$request_ip = mysql_query ("select octet_1, octet_2, octet_3, octet_4 from host_table where record = \"$ip_record\"");
	$result_ip = mysql_fetch_array ($request_ip);
	$octet_1 = $result_ip[octet_1];
	$octet_2 = $result_ip[octet_2];
	$octet_3 = $result_ip[octet_3];
	$octet_4 = $result_ip[octet_4];
	return array ($octet_1, $octet_2, $octet_3, $octet_4);
}

function get_rr_ip($ip_record)
{
	$request_ip = mysql_query ("select octet_1, octet_2, octet_3, octet_4 from round_robin_table where record = \"$ip_record\"");
	$result_ip = mysql_fetch_array ($request_ip);
	$octet_1 = $result_ip[octet_1];
	$octet_2 = $result_ip[octet_2];
	$octet_3 = $result_ip[octet_3];
	$octet_4 = $result_ip[octet_4];
	return array ($octet_1, $octet_2, $octet_3, $octet_4);
}

function get_alias_name($alias_record)
{
	$request_alias_name = mysql_query ("select alias_name from alias_table where record = \"$alias_record\"");
	$result_alias_name = mysql_fetch_array ($request_alias_name);
	$alias_name = $result_alias_name[alias_name];
	return $alias_name;
}

function get_host_name($host_record)
{
	$request_host_name = mysql_query ("select host_name from host_table where record = \"$host_record\"");
	$result_host_name = mysql_fetch_array ($request_host_name);
	$host_name = $result_host_name[host_name];
	return $host_name;
}

function get_domain_name($domain_record)
{
	$request_domain_name = mysql_query ("select domain_name from domain_table where record = \"$domain_record\"");
	$result_domain_name = mysql_fetch_array ($request_domain_name);
	$domain_name = $result_domain_name[domain_name];
	return $domain_name;
}

function beginpage($section)
{
  $display_block="
  <html><head><title>$app_name</title><LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"site.css\"></head>
  <body BACKGROUND=\"/dnstools/images/left-vertical.gif\" BGCOLOR=\"#FFFFFF\" TEXT=\"#000000\" LINK=\"#050588\" VLINK=\"#050588\" ALINK=\"#050588\">";
  printf ("$display_block");
  if ($section=="users") { $image="lightbluecircle-full-user.gif"; $header_image="$PHP_SELF/dnstools/images/header-users.gif"; }
  if ($section=="login") { $image="lightbluecircle-full-door.gif"; $header_image="$PHP_SELF/dnstools/images/header-login.gif"; }
  if ($section=="logout") { $image="lightbluecircle-full-door.gif";$header_image="$PHP_SELF/dnstools/images/header-logout.gif";  }
  if ($section=="security") { $image="lightbluecircle-full-security.gif"; $header_image="$PHP_SELF/dnstools/images/header-security.gif"; }
  if ($section=="groups") { $image="lightbluecircle-full-group.gif"; $header_image="$PHP_SELF/dnstools/images/header-groups.gif"; }
  if ($section=="configure") { $image="lightbluecircle-full-config.gif"; $header_image="$PHP_SELF/dnstools/images/header-configure.gif"; }
  if ($section=="domain") { $image="lightbluecircle-full-domain.gif"; $header_image="$PHP_SELF/dnstools/images/header-domains.gif"; }
  if ($section=="hosts") { $image="lightbluecircle-full-host.gif"; $header_image="$PHP_SELF/dnstools/images/header-hosts.gif"; }
  if ($section=="help") { $image="lightbluecircle-full-help.gif"; $header_image="$PHP_SELF/dnstools/images/header-help.gif"; }
    
  $display_block="
	<TABLE cellpadding=0 cellspacing=0>
	  <TR>
		  <TD ALIGN=RIGHT VALIGN=BOTTOM><img src=\"/dnstools/images/icons/$image\"></img></TD>
	    <TD ALIGN=RIGHT VALIGN=BOTTOM><img src=\"$header_image\"></img></TD>
	  </TR>
		<TR>
			<TD ALIGN=LEFT COLSPAN=2 VALIGN=TOP><img src=\"/dnstools/images/icons/darkline.gif\"></img></TD>
		</TR>
	</TABLE>
	<TABLE>
	 <TR>
	   <TD WIDTH=85></TD>
	   <TD>";
	printf ("$display_block");
}


function leftmenu()
{
	  $display_block="
    <html><head><title>$app_name</title><LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"site.css\"></head>
		<body BACKGROUND=\"/dnstools/images/gradient-menu-blue.gif\" BGCOLOR=\"#FFFFFF\" TEXT=\"#000000\" LINK=\"#050588\" VLINK=\"050588\" ALINK=\"#050588\">
		<table cellspacing=0 cellpadding=0>
			<tr>
			  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=hosts\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-host.gif\" border=0></img></a></td>
			  <TD WIDTH=7></TD>
			  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=hosts\" target=main>Hosts</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<tr>
			  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=domain\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-domain.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
			  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=domain\" target=main>Domains</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
		  <tr>
			  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=users\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-user.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
			  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=users\" target=main>Users</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<TR>
	  	  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=groups\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-group.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
	  	  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=groups\" target=main>Groups</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<tr>
			  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=security\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-security.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
			  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=security\" target=main>Security</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<tr>
			  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=configure\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-config.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
			  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=configure\" target=main>Configure</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<tr>
			  <td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=help&function=main_help\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-help.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
			  <td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=help&function=main_help\" target=main>Help</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<tr>
				<td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=login\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-door.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
				<td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=login\" target=main>Login</a></td>
			</tr>
			<TR>
			<TD><TD>
			</TR>
			<tr>
				<td align=right><a href=\"$PHP_SELF/dnstools/dnstools.php?section=logout\" target=main><img src=\"/dnstools/images/icons/lightbluecircle-small-door.gif\" border=0></img></td>
			  <TD WIDTH=7></TD>
				<td align=left><a href=\"$PHP_SELF/dnstools/dnstools.php?section=logout\" target=main>Logout</a></td>
			</tr>
		</table>
		</body>
		</html>";
	printf ("$display_block");
}

function yes_no_choice ($yes_no)
{
	if ($yes_no=="YES") {
		printf ("<OPTION SELECTED VALUE=\"YES\">YES\n"); }
	else {
		printf ("<OPTION VALUE=\"YES\">YES\n"); }
	
	if ($yes_no=="NO") {
		printf ("<OPTION SELECTED VALUE=\"NO\">NO\n"); }
	else {
		printf ("<OPTION VALUE=\"NO\">NO\n"); }
}
	
function print_scale ($scale)
{
  if ($scale=="k") {
	  printf ("<OPTION SELECTED VALUE=\"k\">Kilobytes\n"); }
	else {
		printf ("<OPTION VALUE=\"k\">Kilobytes\n"); }

  if ($scale=="m") {
	  printf ("<OPTION SELECTED VALUE=\"m\">Megabytes\n"); }
	else {
		printf ("<OPTION VALUE=\"m\">Megabytes\n"); }

  if ($scale=="g") {
	  printf ("<OPTION SELECTED VALUE=\"g\">Gigabytes\n"); }
	else {
		printf ("<OPTION VALUE=\"g\">Gigabytes\n"); }
}

function list_groups_by_user ($temp_record)
{
	$cell_bg_color_1 = "#B1AAFF";
	$cell_bg_color_2 = "#A9BFFF";
	$display_block="
			<TR>
				<TD ALIGN=CENTER COLSPAN=2 BGCOLOR=\"$cell_bg_color_1\"><STRONG>Group Membership</STRONG></TD>
			</TR>";
  printf ("$display_block");
	$num_groups = 0;
	$odd_even = 0;
	$request_groups = mysql_query ("select points_to_group from group_membership_table where points_to_user = \"$temp_record\"");
	while ($result_groups = mysql_fetch_array($request_groups))
	{
		$num_groups ++;
	  if ($odd_even == 0)
		{
			$bgcolor = $cell_bg_color_2;
			$odd_even = 1;
		}
		else
		{
			$bgcolor = $cell_bg_color_1;
			$odd_even = 0;
		}
		$temp_group_number = $result_groups[points_to_group];
		$request_group_name = mysql_query ("select group_name from group_table where record = \"$temp_group_number\"");
		$result_group_name = mysql_fetch_array ($request_group_name);
		$temp_group_name = $result_group_name[group_name];
		$display_block="
			<TR>
				<TD WIDTH=50></TD>
				<TD BGCOLOR=$bgcolor>$temp_group_name</TD>
			</TR>";
		printf ("$display_block");
	}
	if ($num_groups == 0)
	{
		$display_block="
			<TR>
				<TD BGCOLOR=BLACK COLSPAN=2><FONT COLOR=YELLOW>No group memberships at this time.</FONT></TD>
			</TR>";
		printf ("$display_block");
	}
}
// ################################
// ### MAIN PROGRAM STARTS HERE ###
// ################################

// SET UP THE DATABASE CONNECTION

$db=mysql_connect("$MYSQL_SERVER", "$MYSQL_USER", "$MYSQL_PASSWORD") or die ("Sorry, but the database appears to be offline temporarily.  Please try back again shortly. SERVER: $MYSQL_SERVER, USER: $MYSQL_USER, PASS: $MYSQL_PASSWORD");

mysql_select_db("$DATABASE", $db) or die ("Sorry, but the database appears to be offline temporarily.  Please try back again shortly. SERVER: $MYSQL_SERVER, USER: $MYSQL_USER, PASS: $MYSQL_PASSWORD");

// Get logging information

$request_config = mysql_query ("select log_file_name, log_level from global_table");
$result_config = mysql_fetch_array($request_config);
$local_log_level = $result_config[log_level];

# SECTION GROUPS

if ($section == "groups")
{
	beginpage("groups");
	check_login ($user_user_name, $user_password);

	if ($function=="modify_group")
	{
		$request_rows = mysql_query("select * from group_table where record = \"$record\"");
    $num_rows_returned = mysql_num_rows($request_rows);

		if ($num_rows_returned == 0)
		{
			printf ("<FONT COLOR=RED>WARNING:</FONT> No group # $record.\n");
			endpage();
			exit;
		}

		$request_group_info = mysql_query ("select group_name, group_address, misc from group_table where record = \"$record\"");
		$result_group_info = mysql_fetch_array ($request_group_info);
		$group_name = $result_group_info[group_name];
		$group_address = $result_group_info[group_address];
		$misc = $result_group_info[misc];
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=groups&function=submit_modify_group\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>Group Name</STRONG></TD>
					<TD><INPUT TYPE=TEXT SIZE=30 MAXLENGTH=200 NAME=group_name VALUE=\"$group_name\"></INPUT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Group Address</STRONG></TD>
					<TD><TEXTAREA NAME=\"group_address\" wrap=physical rows=4 cols=30>$group_address</TEXTAREA></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>Misc Notes</STRONG></TD>
					<TD ><TEXTAREA NAME=\"misc\" wrap=physical rows=4 cols=30>$misc</TEXTAREA></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Submit Changes\"></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="view_group")
	{
		if ($user_dnstools_administrator != "YES")
		{
			$request_group_security = mysql_query ("select * from group_membership_table where points_to_group = \"$record\" and points_to_user = \"$user_record\"");
			$num_rows = mysql_num_rows ($request_group_security);
			if ($num_rows == 0)
			{
				$display_block = "
					<FONT COLOR=\"RED\">WARNING!</FONT><BR>You are trying to view a group you do not belong to!  This action is being logged.";
				printf ("$display_block");
		  	write_log ("1", "$user_user_name", "WARNING! User tried to view an unauthorized group!");
				endpage();
				exit;
			}
		}
		$request_rows = mysql_query("select * from group_table where record = \"$record\"");
    $num_rows_returned = mysql_num_rows($request_rows);
		if ($num_rows_returned == 0)
		{
			printf ("<FONT COLOR=RED>WARNING:</FONT> No group # $record.\n");
			endpage();
			exit;
		}

		$request_group_info = mysql_query ("select group_name, group_address, misc from group_table where record = \"$record\"");
		$result_group_info = mysql_fetch_array ($request_group_info);
		$group_name = $result_group_info[group_name];
		$group_address = $result_group_info[group_address];
		$misc = $result_group_info[misc];
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=groups&function=modify_group&record=$record\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Group Name</STRONG></TD>
					<TD ALIGN=LEFT><pre>$group_name</pre></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Group Address</STRONG></TD>
					<TD VALIGN=TOP ALIGN=LEFT><pre>$group_address</pre></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Misc Notes</STRONG></TD>
					<TD VALIGN=TOP ALIGN=LEFT><pre>$misc</pre></TD>
				</TR>";
		printf ("$display_block");
		// Now we check to see if this user is an administrator and can modify the group
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Modify Group\"></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
			</TABLE>
			</FORM>
			<TABLE BORDER=0>
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2><STRONG>Users</STRONG></TD>
				</TR>";
		printf ("$display_block");
		$request_users_in_group = mysql_query ("select points_to_user from group_membership_table where points_to_group = \"$record\"");
		$odd_even=0;
		$num_users = 0;
		while ($result_users_in_group = mysql_fetch_array($request_users_in_group))
		{
			$num_users ++;
			$temp_user_record_number = $result_users_in_group[points_to_user];
			$request_user_name = mysql_query ("select user_name, full_name from user_table where record = \"$temp_user_record_number\"");
			$result_user_name = mysql_fetch_array($request_user_name);
			$temp_user_name = $result_user_name[user_name];
			$temp_full_name = $result_user_name[full_name];
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$display_block="
					<TR BGCOLOR=\"$bgcolor\">
						<TD BGCOLOR=\"$bgcolor\"><STRONG>$temp_full_name</STRONG></TD>
						<TD>($temp_user_name)</TD>
					</TR>";
			printf ("$display_block");
		}

		if ($num_users < 1)
		{
			$display_block="
				<TR>
					<TD BGCOLOR=BLACK><FONT COLOR=YELLOW>No users in this group at this time</FONT></TD>
				</TR>";
			printf ("$display_block");
		}

		$display_block="
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2><STRONG>Domains</STRONG></TD>
				</TR>";
		printf ("$display_block");
		$request_domains_in_group = mysql_query ("select domain_name from domain_table where points_to_group = \"$record\"");
		$odd_even=0;
		$num_domains=0;
		while ($result_domains_in_group = mysql_fetch_array($request_domains_in_group))
		{
			$num_domains ++;
			$temp_domain_name = $result_domains_in_group[domain_name];
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$display_block="
				<TR BGCOLOR=\"$bgcolor\">
					<TD COLSPAN=2 BGCOLOR=\"$bgcolor\"><STRONG>$temp_domain_name</STRONG></TD>
				</TR>";
			printf ("$display_block");
		}

		if ($num_domains < 1)
		{
			$display_block="
				<TR>
					<TD BGCOLOR=BLACK><FONT COLOR=YELLOW>No domains belong to this group at this time</FONT></TD>
				</TR>";
			printf ("$display_block");
		}

		$display_block="
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
			</TABLE>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="submit_modify_group")
	{
		if ($user_dnstools_administrator != "YES")
		{
			$request_group_security = mysql_query ("select * from group_membership_table where points_to_group = \"$record\" and points_to_user = \"$user_record\"");
			$num_rows = mysql_num_rows ($request_group_security);
			if ($num_rows == 0)
			{
				$display_block = "
					<FONT COLOR=\"RED\">WARNING!</FONT><BR>You are trying to modify a group you do not belong to!  This action is being logged.";
				printf ("$display_block");
		  	write_log ("1", "$user_user_name", "WARNING! User tried to modify an unauthorized group!");
				endpage();
				exit;
			}
		}
		check_lock();
		$request_rows = mysql_query("select * from group_table where record = \"$record\"");
    $num_rows_returned = mysql_num_rows($request_rows);
		if ($num_rows_returned == 0)
		{
			printf ("<FONT COLOR=RED>WARNING:</FONT> No group # $record.\n");
			endpage();
			exit;
		}
		$request_ident_group = mysql_query ("select record from group_table where group_name = \"$group_name\" and record != \"$record\"");
		$num_rows_returned = mysql_num_rows($request_ident_group);
		if ($num_rows_returned != 0)
		{
			$display_block="
				There is already a group with group name <strong>$group_name</strong>";
			printf ("$display_block");
			endpage();
			exit;
		}

		$sql = "update group_table SET group_name = \"$group_name\", group_address = \"$group_address\", misc = \"$misc\" where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		printf ("Group <strong>$group_name</strong> updated successfully!\n");

		endpage();
		exit;
	}

  if ($function=="submit_add_group")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
	  $display_block="
		  Please wait.... adding new group now.<p>";
		printf ("$display_block");
		$request_rows = mysql_query("select * from group_table where group_name = \"$group_name\"");
    $num_rows_returned = mysql_numrows($request_rows);
		if ($num_rows_returned != 0)
		{
			$display_block="
				<FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate group name.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "insert into group_table (group_name, group_address, misc) values (\"$group_name\", \"$group_address\", \"$misc\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added new group <strong>$group_name</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added new group $group_name");
		endpage();
		exit;
	}

	if ($function=="add_group")
	{
	  check_administrator($user_dnstools_administrator, $user_user_name);
	  $display_block="
			<FORM ACTION=\"$PHP_SELF?section=groups&function=submit_add_group\" METHOD=POST>
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<td></td>
  			  <td align=center bgcolor=\"$cell_bg_color_1\">Add New Group</td>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\" ALIGN=RIGHT><STRONG>Group Name</STRONG></TD>
					<TD><INPUT TYPE=TEXT MAXLENGTH=200 SIZE=30 NAME=group_name></INPUT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\" ALIGN=RIGHT><STRONG>Address</STRONG></TD>
					<TD><TEXTAREA NAME=group_address wrap=physical rows=4 cols=30></TEXTAREA></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\" ALIGN=RIGHT><STRONG>Misc Notes</STRONG></TD>
					<TD><TEXTAREA NAME=misc wrap=physical rows=4 cols=30></TEXTAREA></TD>
				</TR>
				<TR>
				  <TD COLSPAN=2><INPUT TYPE=SUBMIT></TD>
				</TR>
			</TABLE>
			</FORM>\n";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="delete_group")
	{
	  check_administrator($user_dnstools_administrator, $user_user_name);
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=groups&function=submit_delete_group\" METHOD=POST>
			<table border=0 cellspacing=0 cellpadding=0>
 			  <td COLSPAN=2 align=center bgcolor=\"$cell_bg_color_1\">Delete Group</td>
					<tr>
						<td>Delete This Group</td>
						<td><SELECT NAME=temp_group_record>
							<OPTION SELECTED VALUE=0>-= SELECT GROUP =-";
		printf ("$display_block");
		$request_groups = mysql_query ("select record, group_name from group_table order by group_name");
		while ($result_groups = mysql_fetch_array($request_groups))
		{
			$temp_group_record_number = $result_groups[record];
			$temp_group_group_name = $result_groups[group_name];
			printf ("<OPTION VALUE=\"$temp_group_record_number\">$temp_group_group_name\n");
		}
		$display_block="
					</select></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value=\"Submit\"></td>
				</tr>
			</TABLE>
		</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="submit_delete_group")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
		if ($temp_group_record == 0)
		{
			printf ("Action cancelled.  No group selected.\n");
			endpage();
			exit;
		}
		$request_groupname = mysql_query ("select group_name from group_table where record = \"$temp_group_record\"");
		$result_groupname = mysql_fetch_array ($request_groupname);
		$group_name = $result_groupname[group_name];
	  $display_block="
			<TABLE border=0 width=400>
				<TR>
					<TD COLSPAN=2 ALIGN=LEFT>Removing the following users from the group <STRONG>$group_name</STRONG></TD>
				</TR>\n";
		printf ("$display_block");
		$request_users = mysql_query("select points_to_user from group_membership_table where points_to_group = \"$temp_group_record\"");
		$odd_even = 0;
		$num_users = 0;
		while ($result_users = mysql_fetch_array($request_users))
		{
			$num_users ++;
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$temp_user_record = $result_users[points_to_user];
			$request_user_name = mysql_query ("select full_name, user_name from user_table where record = \"$temp_user_record\"");
			$result_user_name = mysql_fetch_array ($request_user_name);
			$temp_full_name = $result_user_name[full_name];
			$temp_user_name = $result_user_name[user_name];
			$display_block="
				<TR>
					<TD WIDTH=40></TD>
					<TD BGCOLOR=\"$bgcolor\"><STRONG>$temp_full_name</STRONG> ($temp_user_name)</TD>
				</TR>";
			printf ("$display_block\n");
			$sql = "delete from group_membership_table where points_to_group = \"$temp_group_record\" and points_to_user = \"$temp_user_record\"";
			$result = mysql_query($sql) or die (mysql_error());
		}

		if ($num_users < 1)
		{
			$display_block="
				<TR>
					<TD COLSPAN=2 BGCOLOR=BLACK><FONT COLOR=YELLOW>No users assigned to this group.</font></TD>
				</TR>";
			printf ("$display_block");
		}

		$display_block="
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=LEFT>Transferring ownership of these domains to the administrator</TD>
				</TR>\n";
		printf ("$display_block");

		$request_domains = mysql_query("select record, domain_name from domain_table where points_to_group = \"$temp_group_record\"");
		$odd_even = 0;
		$num_domains = 0;
		while ($result_domains = mysql_fetch_array($request_domains))
		{
			$num_domains ++;
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$temp_domain_record = $result_domains[record];
			$temp_domain_name = $result_domains[domain_name];
			$display_block="
				<TR>
					<TD WIDTH=40></TD>
					<TD BGCOLOR=\"$bgcolor\"><STRONG>$temp_domain_name</STRONG> (Domain Record: $temp_domain_record)</TD>
				</TR>";
			printf ("$display_block\n");
			$sql = "update domain_table SET points_to_group = \"0\" where points_to_group = \"$temp_group_record\"";
			$result = mysql_query($sql) or die (mysql_error());
		}

		if ($num_domains < 1)
		{
			$display_block="
				<TR>
					<TD COLSPAN=2 BGCOLOR=BLACK><FONT COLOR=YELLOW>No domains assigned to this group.</font></TD>
				</TR>";
			printf ("$display_block");
		}


		$display_block="
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=LEFT>Deleting group <STRONG>$group_name</STRONG>...</TD>
				</TR>";
		printf ("$display_block");
		$sql = "delete from group_table where record = \"$temp_group_record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=LEFT><STRONG>FINISHED!</STRONG></TD>
				</TR>
			</TABLE>\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Deleted group $group_name");
		endpage();
		exit;
	}

	if ($function=="list_groups")
	{
		// Check to see if we are an administrator.  If so,
		// we list all groups.  If not, we list only the
		// groups this user is a member of.

		if ($user_dnstools_administrator == "YES")
		{
			write_log ("2", "$user_user_name", "Listed all groups");
			$display_block="
				<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
					<TR>
						<TD COLSPAN=4 ALIGN=CENTER bgcolor=\"$cell_bg_color_1\">Listing All Groups</TD>
					</TR>";
			printf ("$display_block");
			$request_num_groups = mysql_query ("select count(*) as num_groups from group_table");
			$result_num_groups = mysql_fetch_array($request_num_groups);
			$num_groups = $result_num_groups[num_groups];
			$display_block="
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER bgcolor=\"$cell_bg_color_2\">Total Groups: <strong>$num_groups</strong></TD>
				</TR>
				<TR>
					<TD ALIGN=CENTER><STRONG>Group Name</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>View</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>Modify</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
				</TR>";
			printf ("$display_block\n");
			$request_groups = mysql_query ("select record, group_name from group_table order by group_name");
			$odd_even = 0;
			while ($result_groups = mysql_fetch_array($request_groups))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 0;
				}
				$record_number = $result_groups[record];
				$group_name = $result_groups[group_name];
				$display_block="
					<TR BGCOLOR=\"$bgcolor\">
						<TD>$group_name</TD>
						<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=groups&function=view_group&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-view.gif\" alt=\"View Group\" border=0></img></A></TD>
						<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=groups&function=modify_group&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-modify.gif\" alt=\"Modify Group\" border=0></img></A></TD>
						<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=groups&function=delete_group&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete Group\" border=0></img></A></TD>
					</TR>";
				printf ("$display_block");
			}
			printf ("</TABLE>\n");
			endpage();
			exit;
		}
		else
		{
			write_log ("2", "$user_user_name", "Listed all member groups");
			$display_block="
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER bgcolor=\"$cell_bg_color_1\">Listing All Groups</TD>
				</TR>";
		printf ("$display_block");
		$request_num_groups = mysql_query ("select count(*) as num_groups from group_membership_table where points_to_user = \"$user_record\"");
		$result_num_groups = mysql_fetch_array($request_num_groups);
		$num_groups = $result_num_groups[num_groups];
		$display_block="
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER bgcolor=\"$cell_bg_color_2\">Groups you belong to: <strong>$num_groups</strong></TD>
				</TR>
				<TR>
					<TD ALIGN=CENTER><STRONG>Group Names</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>View</STRONG></TD>
				</TR>";
			printf ("$display_block\n");
			$request_group_numbers = mysql_query ("select points_to_group from group_membership_table where points_to_user = \"$user_record\"");
			$odd_even=0;
			while ($result_group_numbers = mysql_fetch_array($request_group_numbers))
			{
				$temp_group_record = $result_group_numbers[points_to_group];
				$request_group_name = mysql_query ("select group_name from group_table where record = \"$temp_group_record\"");
				$result_group_name = mysql_fetch_array($request_group_name);
				$group_name = $result_group_name[group_name];

			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 0;
				}

				$display_block="
					<TR BGCOLOR=\"$bgcolor\">
						<TD>$group_name</TD>
						<TD ALIGN=CENTER WIDTH=45> <A HREF=\"$PHP_SELF?section=groups&function=view_group&record=$temp_group_record\"><img src=\"/dnstools/images/icons/ultra-small-view.gif\" border=0 ALT=\"View Group\"></img></A> </TD>
					</TR>";
				printf ("$display_block\n");
			}
			$display_block="
				</TABLE>";
			printf ("$display_block");
			endpage();
			exit;


			$display_block="
				Normally all of the groups you belong to would be listed here.<BR>
				Still in develeopment.<P>";
			printf ("$display_block\n");
			endpage();
			exit;
		}
	}

	if (! $function)
	{
		$display_block="
		  <P>
			<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>
			  <TR>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				  <TD><A HREF=\"$PHP_SELF?section=groups&function=add_group\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=groups&function=add_group\">Add Group</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=groups&function=delete_group\"><IMG SRC=\"/dnstools/images/icons/small-button-delete.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=groups&function=delete_group\">Delete Group</A></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
			  <TR>
				  <TD><A HREF=\"$PHP_SELF?section=groups&function=list_groups\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=groups&function=list_groups\">List Groups</A></TD>
				</TR>
			</TABLE>";
		printf ("$display_block");
	  endpage();
	  exit;
	}
}

# SECTION SECURITY

else if ($section=="security")
{
	beginpage("security");
	check_login ($user_user_name, $user_password);
  check_administrator($user_dnstools_administrator, $user_user_name);

	if (! $function)
	{
		$display_block="
			<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>
				<TR>
				  <TD><A HREF=\"$PHP_SELF?section=security&function=add_user_to_group_1\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=security&function=add_user_to_group_1\">Add User To Group</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=security&function=delete_user_from_group_1\"><IMG SRC=\"/dnstools/images/icons/small-button-delete.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=security&function=delete_user_from_group_1\">Delete User From Group</A></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><HR></TD>
				</TR>
			</TABLE>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="add_user_to_group_2")
	{
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=security&function=submit_add_user_to_group\" METHOD=POST>
		<INPUT TYPE=HIDDEN NAME=temp_group_record VALUE=\"$temp_group_record\">
			<table border=0 cellspacing=0 cellpadding=0>
				<TR>
	 			  <td COLSPAN=2 align=center bgcolor=\"$cell_bg_color_1\">Add User To Group - Step 2</td>
				</TR>
				<TR>
					<td>Select User to Add</td>
					<td><SELECT NAME=temp_user_record>
						<OPTION SELECTED VALUE=0>-= SELECT USER =-";
		printf ("$display_block");
		$request_users = mysql_query ("select * from user_table");
		while ($result_users = mysql_fetch_array($request_users))
		{
			$temp_user_record = $result_users[record];
			$temp_user_name = $result_users[user_name];
			$temp_full_name = $result_users[full_name];
			$request_membership = mysql_query ("select record from group_membership_table where points_to_user = \"$temp_user_record\" and points_to_group = \"$temp_group_record\"");
			$num_rows = mysql_num_rows ($request_membership);
			if ($num_rows == 0)
			{
				printf ("<OPTION VALUE=\"$temp_user_record\">$temp_user_name ($temp_full_name)\n");
			}
		}
		$display_block="
					</select></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value=\"Submit\"></td>
				</tr>
			</TABLE>
		</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}


	if ($function=="add_user_to_group_1")
	{
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=security&function=add_user_to_group_2\" METHOD=POST>
			<table border=0 cellspacing=0 cellpadding=0>
				<TR>
	 			  <td COLSPAN=2 align=center bgcolor=\"$cell_bg_color_1\">Add User To Group - Step 1</td>
				</TR>
				<TR>
					<td>Select Group</td>
					<td><SELECT NAME=temp_group_record>
						<OPTION SELECTED VALUE=0>-= SELECT GROUP =-";
		printf ("$display_block");
		$request_groups = mysql_query ("select record, group_name from group_table order by group_name");
		while ($result_groups = mysql_fetch_array($request_groups))
		{
			$temp_group_record_number = $result_groups[record];
			$temp_group_group_name = $result_groups[group_name];
			printf ("<OPTION VALUE=\"$temp_group_record_number\">$temp_group_group_name\n");
		}
		$display_block="
					</select></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value=\"Next Step\"></td>
				</tr>
			</TABLE>
		</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="submit_add_user_to_group")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);

		if (($temp_user_record == 0) || ($temp_group_record == 0))
		{
			printf ("Action cancelled.  No user or group selected.\n");
			endpage();
			exit;
		}

		$request_user_name = mysql_query ("select user_name, full_name from user_table where record = \"$temp_user_record\"");
		$result_user_name = mysql_fetch_array ($request_user_name);
		$user_name = $result_user_name[user_name];
		$full_name = $result_user_name[full_name];

		$request_groupname = mysql_query ("select group_name from group_table where record = \"$temp_group_record\"");
		$result_groupname = mysql_fetch_array ($request_groupname);
		$group_name = $result_groupname[group_name];

	  $display_block="
		  Please wait.... adding user <strong>$full_name ($user_name)</strong> to group <strong>$group_name</strong>.<p>";
		printf ("$display_block");
		$request_rows = mysql_query("select * from group_membership_table where points_to_user = \"$temp_user_record\" and points_to_group = \"$temp_group_record\"");
    $num_rows_returned = mysql_numrows($request_rows);

		if ($num_rows_returned != 0)
		{
			$display_block="
				<FONT COLOR=\"RED\">NOTICE:</FONT><BR>User <strong>$full_name ($user_name)</strong> already belongs to group <strong>$group_name</strong>.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "insert into group_membership_table (points_to_user, points_to_group) values (\"$temp_user_record\", \"$temp_group_record\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added user <strong>$full_name ($user_name)</strong> to group <strong>$group_name</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added user $full_name ($user_name) to group $group_name");
		endpage();
		exit;
	}

	if ($function=="delete_user_from_group_2")
	{
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=security&function=submit_delete_user_from_group\" METHOD=POST>
		<INPUT TYPE=HIDDEN NAME=temp_group_record VALUE = \"$temp_group_record\">
			<table border=0 cellspacing=0 cellpadding=0>
				<TR>
	 			  <td COLSPAN=2 align=center bgcolor=\"$cell_bg_color_1\">Delete User From Group</td>
				</TR>
				<tr>
					<td>Select User</td>
					<td><SELECT NAME=temp_user_record>
							<OPTION SELECTED VALUE=0>-= SELECT USER =-";
		printf ("$display_block");
		$request_users = mysql_query ("select * from user_table");
		while ($result_users = mysql_fetch_array($request_users))
		{
			$temp_user_record = $result_users[record];
			$temp_user_name = $result_users[user_name];
			$temp_full_name = $result_users[full_name];
			$request_membership = mysql_query ("select record from group_membership_table where points_to_group = \"$temp_group_record\" and points_to_user = \"$temp_user_record\"");
			$num_rows = mysql_num_rows ($request_membership);
			if ($num_rows != 0)
			{
				printf ("<OPTION VALUE=\"$temp_user_record\">$temp_user_name ($temp_full_name)\n");
			}
		}
		$display_block="
					</select></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value=\"Submit\"></td>
				</tr>
			</TABLE>
		</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="delete_user_from_group_1")
	{
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=security&function=delete_user_from_group_2\" METHOD=POST>
			<table border=0 cellspacing=0 cellpadding=0>
 			  <td COLSPAN=2 align=center bgcolor=\"$cell_bg_color_1\">Delete User From Group</td>
					<tr>
						<td>Select Group</td>
						<td><SELECT NAME=temp_group_record>
								<OPTION SELECTED VALUE=0>-= SELECT GROUP =-";
		printf ("$display_block");
		$request_groups = mysql_query ("select record, group_name from group_table order by group_name");
		while ($result_groups = mysql_fetch_array($request_groups))
		{
			$temp_group_record_number = $result_groups[record];
			$temp_group_group_name = $result_groups[group_name];
			printf ("<OPTION VALUE=\"$temp_group_record_number\">$temp_group_group_name\n");
		}
		$display_block="
					</select></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value=\"Next Step ->\"></td>
				</tr>
			</TABLE>
		</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	if ($function=="submit_delete_user_from_group")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);

		if (($temp_user_record == 0) || ($temp_group_record == 0))
		{
			printf ("Action cancelled.  No user or group selected.\n");
			endpage();
			exit;
		}

		$request_user_name = mysql_query ("select user_name, full_name from user_table where record = \"$temp_user_record\"");
		$result_user_name = mysql_fetch_array ($request_user_name);
		$user_name = $result_user_name[user_name];
		$full_name = $result_user_name[full_name];

		$request_groupname = mysql_query ("select group_name from group_table where record = \"$temp_group_record\"");
		$result_groupname = mysql_fetch_array ($request_groupname);
		$group_name = $result_groupname[group_name];

	  $display_block="
		  Please wait.... deleting user <strong>$full_name ($user_name)</strong> from group <strong>$group_name</strong>.<p>";
		printf ("$display_block");
		$request_rows = mysql_query("select * from group_membership_table where points_to_user = \"$temp_user_record\" and points_to_group = \"$temp_group_record\"");
    $num_rows_returned = mysql_numrows($request_rows);

		if ($num_rows_returned == 0)
		{
			$display_block="
				<FONT COLOR=\"RED\">NOTICE:</FONT><BR>User <strong>$full_name ($user_name)</strong> does not belong to group <strong>$group_name</strong>.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "delete from group_membership_table where points_to_user = \"$temp_user_record\" and points_to_group = \"$temp_group_record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Deleted user <strong>$full_name ($user_name)</strong> from group <strong>$group_name</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Deleted user $full_name ($user_name) from group $group_name");
		endpage();
		exit;
	}
}

# SECTION CONFIGURE

else if ($section=="configure")
{
  beginpage("configure");
	check_login ($user_user_name, $user_password);

  check_administrator($user_dnstools_administrator, $user_user_name);

  if ($function=="submit_modify") 
	{
		if (($DEMO == "YES") && ($user_user_name != "admin"))
		{
			$display_block="
				Sorry, you cannot change the configuration settings in the online demo.";
			printf ("$display_block");
			endpage();
			exit;
		}

    $request_config = mysql_query("select * from global_table");
    $num_rows_returned = mysql_numrows($request_config);
		if ($num_rows_returned != 0)
		{
			$sql = "update global_table SET
			data_directory = \"$data_directory\",
			bind_configuration_file = \"$bind_configuration_file\",
			log_file_name = \"$log_file_name\",
			log_level = \"$log_level\",
			company_name = \"$company_name\",
			company_address =  \"$company_address\",
			contact_info = \"$contact_info\",
			administrator_email = \"$administrator_email\",
			cache_only_server = \"$cache_only_server\",
			forward_only_server = \"$forward_only_server\",
			recursive_server = \"$recursive_server\",
			active_transfers_per_server = \"$active_transfers_per_server\",
			total_active_transfers = \"$total_active_transfers\",
			duration_of_transfer = \"$duration_of_transfer\",
			data_segment_size = \"$data_segment_size\",
			data_segment_size_scale = \"$data_segment_size_scale\",
			stack_size = \"$stack_size\",
			stack_size_scale = \"$stack_size_scale\",
			core_size = \"$core_size\",
			core_size_scale = \"$core_size_scale\",
			max_open_files = \"$max_open_files\",
			cleaning_interval = \"$cleaning_interval\",
			statistics_interval = \"$statistics_interval\",
			database_is_locked = \"$database_is_locked\",
			needs_update = \"YES\"";
		}
		else
		{
			$sql = "insert into global_table
			(data_directory, bind_configuration_file, log_file_name, log_level, company_name, company_address, contact_info, administrator_email, cache_only_server, forward_only_server, recursive_server, active_transfers_per_server, total_active_transfers, duration_of_transfer, data_segment_size, data_segment_size_scale, stack_size, stack_size_scale, core_size, core_size_scale, max_open_files, cleaning_interval, statistics_interval, database_is_locked, needs_update) values (\"$data_directory\", \"$bind_configuration_file\", \"$log_file_name\", \"$log_level\", \"$company_name\", \"$company_address\", \"$contact_info\", \"$administrator_email\", \"$cache_only_server\", \"$forward_only_server\", \"$recursive_server\", \"$active_transfers_per_server\", \"$total_active_transfers\", \"$duration_of_transfer\", \"$data_segment_size\", \"$data_segment_size_scale\", \"$stack_size\", \"$stack_size_scale\", \"$core_size\", \"$core_size_scale\", \"$max_open_files\", \"$cleaning_interval\", \"$statistics_interval\", \"$database_is_locked\", \"$database_is_locked\")";
		}
		$result = mysql_query($sql) or die (mysql_error());
		printf ("</pre><P>Configuration saved successfully.\n");
	  write_log ("1", "$user_user_name", "Modified main DNSTools Configuration utility");
		endpage();
	  exit;
	}

	else if (! $function)
	{
		$request_config = mysql_query ("select * from global_table");
		$result_config = mysql_fetch_array($request_config);
		$data_directory = $result_config[data_directory];
		$bind_configuration_file = $result_config[bind_configuration_file];
		$log_file_name = $result_config[log_file_name];
		$log_level = $result_config[log_level];
		$company_name = $result_config[company_name];
		$company_address = $result_config[company_address];
		$contact_info = $result_config[contact_info];
		$administrator_email = $result_config[administrator_email];
		$cache_only_server = $result_config[cache_only_server];
		$forward_only_server = $result_config[forward_only_server];
		$recursive_server = $result_config[recursive_server];
		$active_transfers_per_server = $result_config[active_transfers_per_server];
		$total_active_transfers = $result_config[total_active_transfers];
		$duration_of_transfer = $result_config[duration_of_transfer];
		$data_segment_size = $result_config[data_segment_size];
		$data_segment_size_scale = $result_config[data_segment_size_scale];
		$stack_size = $result_config[stack_size];
		$stack_size_scale = $result_config[stack_size_scale];
		$core_size = $result_config[core_size];
		$core_size_scale = $result_config[core_size_scale];
		$max_open_files = $result_config[max_open_files];
		$cleaning_interval = $result_config[cleaning_interval];
		$statistics_interval = $result_config[statistics_interval];
		$database_is_locked = $result_config[database_is_locked];
		$needs_update = $result_config[needs_update];
	
		right_justified_help_link ("configure");
	  $display_block="
		<FORM ACTION=\"$PHP_SELF?section=configure&function=submit_modify\" METHOD=POST>
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD></TD>
				<TD COLSPAN=2 ALIGN=LEFT BGCOLOR=\"$cell_bg_color_1\">Modify DNSTools Configuration</TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\" WIDTH=200><STRONG>Company Name</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><INPUT TYPE=TEXT NAME=company_name SIZE=30 MAXLENGTH=200 VALUE=\"$company_name\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT VALIGN=TOP BGCOLOR=\"$cell_bg_color_1\"><STRONG>Company Address</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><TEXTAREA NAME=company_address wrap=physical rows=4 cols=30>$company_address</TEXTAREA></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT VALIGN=TOP BGCOLOR=\"$cell_bg_color_2\"><STRONG>Contact Info</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><TEXTAREA NAME=contact_info wrap=physical rows=4 cols=30>$contact_info</TEXTAREA></TD>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Administrator Email</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><INPUT TYPE=TEXT NAME=administrator_email SIZE=30 VALUE=\"$administrator_email\"></INPUT></TD>
			</TR>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Data Directory</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><INPUT TYPE=TEXT NAME=data_directory SIZE=30 MAXLENGTH=200 VALUE=\"$data_directory\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>BIND Configuration File</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><INPUT TYPE=TEXT NAME=bind_configuration_file SIZE=30 MAXLENGTH=200 VALUE=\"$bind_configuration_file\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Log File Name</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><INPUT TYPE=TEXT NAME=log_file_name SIZE=30 MAXLENGTH=250 VALUE=\"$log_file_name\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Logging Level</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><SELECT NAME=log_level>";
		printf ("$display_block\n");
		if (($log_level == 1) || (! $log_level))
		{
		  $display_block="
				<OPTION SELECTED VALUE=1>Minimal
				<OPTION VALUE=2>Detailed
				<OPTION VALUE=3>Troubleshooting\n";
			printf ("$display_block\n");
		}
		if ($log_level == 2)
		{
		  $display_block="
				<OPTION VALUE=1>Minimal
				<OPTION SELECTED VALUE=2>Detailed
				<OPTION VALUE=3>Troubleshooting\n";
			printf ("$display_block\n");
		}
		if ($log_level == 3)
		{
		  $display_block="
				<OPTION VALUE=1>Minimal
				<OPTION VALUE=2>Detailed
				<OPTION SELECTED VALUE=3>Troubleshooting\n";
			printf ("$display_block\n");
		}
		$display_block="
					</SELECT>
				</TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Cache-Only Server?</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><SELECT NAME=cache_only_server>";
		printf ("$display_block\n");
		yes_no_choice ($cache_only_server);
		$display_block="
					</SELECT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Forward-Only Server?</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><SELECT NAME=forward_only_server>";
		printf ("$display_block\n");
		yes_no_choice ($forward_only_server);
		$display_block="
					</SELECT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Recursive Server?</STRONG></TD>
				<TD COLSPAN=2 ALIGN=LEFT><SELECT NAME=recursive_server>";
		printf ("$display_block\n");
		yes_no_choice ($recursive_server);
		$display_block="
					</SELECT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Transfers per Server</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=active_transfers_per_server SIZE=4 VALUE=\"$active_transfers_per_server\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Total Active Transfers</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=total_active_transfers SIZE=4 VALUE=\"$total_active_transfers\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Duration of Transfer</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=duration_of_transfer SIZE=4 VALUE=\"$duration_of_transfer\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Data Segment Size</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=data_segment_size SIZE=5 VALUE=\"$data_segment_size\"></INPUT></TD>
				<TD ALIGN=LEFT><SELECT NAME=data_segment_size_scale>";
		printf ("$display_block\n");
		print_scale ($data_segment_size_scale);
		$display_block="
					</SELECT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Stack Size</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=stack_size SIZE=5 VALUE=\"$stack_size\"></INPUT></TD>
				<TD ALIGN=LEFT><SELECT NAME=stack_size_scale>";
		printf ("$display_block\n");
		print_scale ($stack_size_scale);
		$display_block="
					</SELECT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Core Size</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=core_size SIZE=5 VALUE=\"$core_size\"></INPUT></TD>
				<TD ALIGN=LEFT><SELECT NAME=core_size_scale>";
		printf ("$display_block\n");
		print_scale ($core_size_scale);
		$display_block="
					</SELECT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Max Open Files</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=max_open_files SIZE=4 VALUE=\"$max_open_files\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Cleaning Interval</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=cleaning_interval SIZE=4 VALUE=\"$cleaning_interval\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Statistics Interval</STRONG></TD>
				<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=statistics_interval SIZE=4 VALUE=\"$statistics_interval\"></INPUT></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Lock Database</STRONG></TD>
				<TD ALIGN=LEFT><SELECT NAME=database_is_locked>";
		printf ("$display_block");
		if ($database_is_locked == "NO")
		{
			$display_block="
							<OPTION SELECTED VALUE=\"NO\">NO
							<OPTION VALUE=\"YES\">YES";
		}
		else
		{
			$display_block="
					<OPTION SELECTED VALUE=\"YES\">YES
					<OPTION VALUE=\"NO\">NO";
		}
		printf ("$display_block");
		$display_block="
				</SELECT></TD>
			</TR>
			<TR>
			  <TD COLSPAN=3 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE=\"Submit Changes\"></TD>
			</TR>
		</TABLE>
		</FORM>";
		printf ("$display_block");
	}
  endpage();
  exit;
}

# SECTION HOSTS

else if ($section=="hosts")
{
  beginpage("hosts");
	check_login ($user_user_name, $user_password);

	if ($function=="delete_mx")
	{
		$request_mx_info = mysql_query ("select * from mx_table where record = \"$record\"");
		$result_mx_info = mysql_fetch_array ($request_mx_info);
		$mx_name = $result_mx_info[name];
		$mx_priority = $result_mx_info[priority];
		$mx_domain = $result_mx_info[points_to_domain];
		$mx_host = $result_mx_info[points_to_host];
		$domain_name = get_domain_name ($mx_domain);
		$host_name = get_host_name ($mx_host);
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_delete_mx\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
		  Are you <STRONG>SURE</STRONG> you wish to delete the following information?
			<P>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>";
		printf ("$display_block");
		if ($mx_domain != 0)
		{
			$display_block="
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Domain</STRONG></TD>
					<TD>$domain_name</TD>
				</TR>";
			printf ("$display_block");
		}
		else
		{
			$display_block="
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Host</STRONG></TD>
					<TD>$host_name</TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>MX Hostname</STRONG></TD>
					<TD>$mx_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>MX Priority</STRONG></TD>
					<TD>$mx_priority</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this MX Record!\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}
		
	else if ($function=="submit_delete_mx")
	{
		check_lock();
		$request_domain = mysql_query ("select points_to_domain from mx_table where record = \"$record\"");
		$result_domain = mysql_fetch_array ($request_domain);
		$points_to_domain = $result_domain[points_to_domain];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$sql = "delete from mx_table where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Deleted MX record successfully.\n";
		printf ("$display_block");
		update_domain_serial ($points_to_domain);
		write_log ("2", "$user_user_name", "Deleted MX record");
		endpage();
		exit;
	}

	if ($function == "submit_mx_to_host")
	{
		check_lock();
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		check_valid_full_hostname ($name);
		check_valid_mx_priority ($priority);
		$domain_name = get_domain_name ($points_to_domain);
		$host_name = get_host_name($points_to_host);
		$sql = "insert into mx_table (name, priority, points_to_host) values (\"$name\", \"$priority\", \"$points_to_host\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="<P>
			Added new MX entry <strong>$name</strong> to host <strong>$host_name.$domain_name</strong> successfully!\n";
		printf ("$display_block");
		$domain_name = get_domain_name($points_to_domain);
  	write_log ("1", "$user_user_name", "Added new MX entry $name to $host_name.$domain_name");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function == "add_mx_to_host_3")
	{
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_mx_to_host\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=points_to_domain VALUE=\"$points_to_domain\">
			<INPUT TYPE=HIDDEN NAME=points_to_host VALUE=\"$points_to_host\">
			<TABLE>
				<TR BGCOLOR=\"$cell_bg_color_1\">
					<TD COLSPAN=2 ALIGN=CENTER>Add MX To host</TD>
				</TR>
				<TR>
				<TR>
					<TD VALIGN=TOP BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>Full hostname</STRONG><BR>(<em>with</em> domain)</TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=name SIZE=30></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>Priority</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=priority SIZE=3></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Submit\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
	}

	else if ($function == "add_mx_to_host_2")
	{
		$request_hosts = mysql_query ("select * from host_table where points_to_domain = \"$points_to_domain\"");
    $num_rows_returned = mysql_numrows($request_hosts);

		if ($num_rows_returned == 0)
		{
			$display_block="
				There are no hosts to add an MX record to in this domain.";
			printf ("$display_block");
			endpage();
			exit;
		}
		else
		{
			check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
			$display_block="
				<FORM ACTION=\"$PHP_SELF?section=hosts&function=add_mx_to_host_3\" METHOD=POST>
				<INPUT TYPE=HIDDEN NAME=points_to_domain VALUE=\"$points_to_domain\">
				<TABLE>
					<TR>
						<TD COLSPAN=2 BGCOLOR=\"$cell_bg_color_1\">Select Host to Add MX Record</TD>
					</TR>
					<TR BGCOLOR=\"$cell_bg_color_2\">
						<TD ALIGN=RIGHT><STRONG>Select Host</STRONG></TD>
						<TD ALIGN=LEFT><SELECT NAME=points_to_host>";
			printf ("$display_block");
			$request_hosts = mysql_query ("select * from host_table where points_to_domain = \"$points_to_domain\" order by host_name");
			while ($result_hosts = mysql_fetch_array($request_hosts))
			{
				$host_record = $result_hosts[record];
				$host_name = $result_hosts[host_name];
				$display_block="
							<OPTION VALUE=\"$host_record\">$host_name";
				printf ("$display_block");
			}
			$display_block="
						</SELECT></TD>
					</TR>
					<TR>
						<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE=\"Next Step ->\">
					</TR>
				</TABLE>
				</FORM>";
			printf ("$display_block");
			endpage();
			exit;
		}
	}

	else if ($function == "add_mx_to_host")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can add an MX record to a host in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=hosts&function=add_mx_to_host_2\" METHOD=POST>
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Add MX to Host</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Select Domain</STRONG></TD>
				<TD ALIGN=LEFT><SELECT NAME=points_to_domain><OPTION VALUE=\"0\">-= SELECT DOMAIN =-";
		printf ("$display_block");
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		foreach ($domain_list as $current_domain)
		{
			$domain_name = get_domain_name ($current_domain);
			printf ("<OPTION VALUE=\"$current_domain\">$domain_name");
		}
		$display_block="
				</SELECT></TD>
			</TR>
			<TR>
				<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE=\"Next Step ->\"></INPUT></TD>
			</TR>
		</TABLE>
		</FORM>";
		printf ("$display_block\n");
		endpage();
		exit;
	}

	else if ($function == "submit_modify_host")
	{
		check_lock();
		check_valid_hostname ($new_host_name);
		$request_domain = mysql_query ("select points_to_domain from host_table where record = \"$record\"");
		$result_domain = mysql_fetch_array($request_domain);
		$points_to_domain = $result_domain[points_to_domain];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		if ($new_host_name == "")
		{
			$display_block="
				You must enter a host name.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
			Modifying host entry... please wait";
		printf ("$display_block");

		$request_duplicate_host = mysql_query ("select record from host_table where points_to_domain = \"$points_to_domain\" and host_name = \"$new_host_name\" and record != \"$record\"");
    $num_rows_returned = mysql_numrows($request_duplicate_host);
		if ($num_rows_returned != 0)
		{
			$display_block="
				<P><FONT COLOR=RED><STRONG>WARNING</STRONG></FONT> Duplicate host name in the same domain.  Please try again.";
			printf ("$display_block");
			endpage();
			exit;
		}
		else
		{
			if ($registered_ip == "NO")
			{
				check_valid_ip ($new_octet_1, $new_octet_2, $new_octet_3, $new_octet_4);
				$sql = "update host_table SET host_name = \"$new_host_name\", octet_1 = \"$new_octet_1\", octet_2 = \"$new_octet_2\", octet_3 = \"$new_octet_3\", octet_4 = \"$new_octet_4\", hinfo_1 = \"$new_hinfo_1\", hinfo_2 = \"$new_hinfo_2\", ttl = \"$new_ttl\" where record = \"$record\"";
				update_domain_serial ($points_to_domain);
			}
			else
			{
				$sql = "update host_table SET host_name = \"$new_host_name\", hinfo_1 = \"$new_hinfo_1\", hinfo_2 = \"$new_hinfo_2\", ttl = \"$new_ttl\" where record = \"$record\"";
				update_domain_serial ($points_to_domain);
			}
			$result = mysql_query($sql) or die (mysql_error());
			$display_block="<P>
				Modified host successfully!\n";
			printf ("$display_block");
			write_log ("1", "$user_user_name", "Modified host # $record successfully.");
			update_domain_serial($points_to_domain);
			endpage();
			exit;
		}
	}

	else if ($function == "modify_host")
	{
		$request_domain = mysql_query ("select * from host_table where record = \"$record\"");
		$result_domain = mysql_fetch_array ($request_domain);
		$points_to_domain = $result_domain[points_to_domain];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		$octet_1 = $result_domain[octet_1];
		$octet_2 = $result_domain[octet_2];
		$octet_3 = $result_domain[octet_3];
		$octet_4 = $result_domain[octet_4];
		$host_name = $result_domain[host_name];
		$registered_ip = $result_domain[registered_ip];
		$hinfo_1 = $result_domain[hinfo_1];
		$hinfo_2 = $result_domain[hinfo_2];
		$ttl = $result_domain[ttl];

		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_modify_host\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\"></INPUT>
			<INPUT TYPE=HIDDEN NAME=registered_ip VALUE=\"$registered_ip\"></INPUT>
			<TABLE BORDER = 0 CELLSPACING = 2 CELLPADDING = 2>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 COLSPAN=2 ALIGN=CENTER><STRONG>Modify Host</STRONG></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>Hostname</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=new_host_name VALUE='$host_name' SIZE=20></INPUT>.$domain_name</TD>
				</TR>";
		printf ("$display_block");
		if ($registered_ip == "YES")
		{
			$display_block="
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT VALIGN=TOP><STRONG>IP Address<BR><font size=-1>To modify a registered IP address you<br>must delete and re-add this host</STRONG></TD>
					<TD ALIGN=LEFT>$octet_1.$octet_2.$octet_3.$octet_4</TD>
				</TR>";
			printf ("$display_block");
		}
		else
		{
			$display_block="
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT><STRONG>IP Address</STRONG></TD>
					<TD ALIGN=LEFT>
							<INPUT TYPE=TEXT NAME=new_octet_1 VALUE='$octet_1' SIZE=3 MAXLENGTH=3></INPUT>.
							<INPUT TYPE=TEXT NAME=new_octet_2 VALUE='$octet_2' SIZE=3 MAXLENGTH=3></INPUT>.
							<INPUT TYPE=TEXT NAME=new_octet_3 VALUE='$octet_3' SIZE=3 MAXLENGTH=3></INPUT>.
							<INPUT TYPE=TEXT NAME=new_octet_4 VALUE='$octet_4' SIZE=3 MAXLENGTH=3></INPUT></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>Registered IP</STRONG></TD>
					<TD ALIGN=LEFT>$registered_ip</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT><STRONG>TTL</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=new_ttl VALUE='$ttl' SIZE=10 MAXLENGTH=10></INPUT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>HINFO 1</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=new_hinfo_1 VALUE='$hinfo_1' SIZE=20 MAXLENGTH=250></INPUT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT><STRONG>HINFO 2</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=new_hinfo_2 VALUE='$hinfo_2' SIZE=20 MAXLENGTH=250></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=CENTER COLSPAN=2><INPUT TYPE=SUBMIT VALUE='Submit'></INPUT></TD>
				</TR>
			</FORM>
			</TABLE>";
		printf ("$display_block");
	}

	else if ($function == "view_host")
	{
		$request_domain = mysql_query ("select * from host_table where record = \"$record\"");
		$result_domain = mysql_fetch_array ($request_domain);
		$points_to_domain = $result_domain[points_to_domain];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		$octet_1 = $result_domain[octet_1];
		$octet_2 = $result_domain[octet_2];
		$octet_3 = $result_domain[octet_3];
		$octet_4 = $result_domain[octet_4];
		$host_name = $result_domain[host_name];
		$registered_ip = $result_domain[registered_ip];
		$hinfo_1 = $result_domain[hinfo_1];
		$hinfo_2 = $result_domain[hinfo_2];
		$ttl = $result_domain[ttl];

		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=modify_host\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\"></INPUT>
			<TABLE BORDER = 0 CELLSPACING = 2 CELLPADDING = 2>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 COLSPAN=2 ALIGN=CENTER><STRONG>View Host</STRONG></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>Hostname</STRONG></TD>
					<TD ALIGN=LEFT>$host_name.$domain_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT><STRONG>IP Address</STRONG></TD>
					<TD ALIGN=LEFT>$octet_1.$octet_2.$octet_3.$octet_4</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>Registered IP</STRONG></TD>
					<TD ALIGN=LEFT>$registered_ip</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT><STRONG>TTL</STRONG></TD>
					<TD ALIGN=LEFT>$ttl</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT><STRONG>HINFO 1</STRONG></TD>
					<TD ALIGN=LEFT>$hinfo_1</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT><STRONG>HINFO 2</STRONG></TD>
					<TD ALIGN=LEFT>$hinfo_2</TD>
				</TR>
				<TR>
					<TD ALIGN=CENTER COLSPAN=2><INPUT TYPE=SUBMIT VALUE='Modify Host'></INPUT></TD>
				</TR>
			</FORM>
			</TABLE>
			<TABLE BORDER=0 CELLSPACING = 2 CELLPADDING = 2>";
		printf ("$display_block");
		$request_aliases = mysql_query ("select * from alias_table where points_to_host = \"$record\"");
    $num_rows_returned = mysql_numrows($request_aliases);

		if ($num_rows_returned != 0)
		{
			$display_block="
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><HR></TD>
				</TR>
				<TR>
					<TD ALIGN=CENTER><STRONG>Alias Name</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
					<TD></TD>
				</TR>";
			printf ("$display_block");
			$request_aliases = mysql_query ("select * from alias_table where points_to_host = \"$record\"");
			$odd_even = 0;
			while ($result_aliases = mysql_fetch_array($request_aliases))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 0;
				}
				$alias_record = $result_aliases[record];
				$alias_name = $result_aliases[alias_name];
				$display_block="
					<TR>
						<TD ALIGN=LEFT BGCOLOR=$bgcolor>$alias_name</TD>
						<TD ALIGN=CENTER WIDTH=45 BGCOLOR=$bgcolor><A HREF=\"$PHP_SELF?section=hosts&function=delete_alias&record=$alias_record\" ><IMG SRC=\"/dnstools/images/icons/ultra-small-delete.gif\" BORDER=0 ALT=\"Delete Alias\"></A></TD>
						<TD></TD>
					</TR>";
				printf ("$display_block");
			}
		}
		$request_rr = mysql_query ("select * from round_robin_table where points_to_host = \"$record\"");
    $num_rows_returned = mysql_numrows($request_rr);

		if ($num_rows_returned != 0)
		{
			$display_block="
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><HR></TD>
				</TR>
				<TR>
					<TD ALIGN=LEFT><STRONG>Round Robin IP</STRONG></TD>
					<TD ALIGN=LEFT><STRONG>Delete</STRONG></TD>
				</TR>";
			printf ("$display_block");
			$request_rr = mysql_query ("select * from round_robin_table where points_to_host = \"$record\"");
			$odd_even = 0;
			while ($result_rr = mysql_fetch_array($request_rr))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 0;
				}
				$rr_record = $result_rr[record];
				$rr1 = $result_rr[octet_1];
				$rr2 = $result_rr[octet_2];
				$rr3 = $result_rr[octet_3];
				$rr4 = $result_rr[octet_4];
				$display_block="
					<TR>
						<TD ALIGN=LEFT BGCOLOR=$bgcolor>$rr1.$rr2.$rr3.$rr4</TD>
						<TD ALIGN=CENTER WIDTH=45 BGCOLOR=$bgcolor><A HREF=\"$PHP_SELF?section=hosts&function=delete_rr&record=$rr_record\" ><IMG SRC=\"/dnstools/images/icons/ultra-small-delete.gif\" BORDER=0 ALT=\"Delete RR\"></A></TD>
					</TR>";
				printf ("$display_block");
			}
		}
		$request_mx = mysql_query ("select * from mx_table where points_to_host = \"$record\"");
    $num_rows_returned = mysql_numrows($request_mx);

		if ($num_rows_returned != 0)
		{
			$display_block="
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><HR></TD>
				</TR>
				<TR>
					<TD ALIGN=LEFT COLSPAN=3><STRONG>Host MX Records</STRONG></TD>
				</TR>
				<TR>
					<TD ALIGN=CENTER><STRONG>Hostname</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>Priority</STRONG></TD>
					<TD ALIGN=LEFT><STRONG>Delete</STRONG></TD>
				</TR>";
			printf ("$display_block");
			$request_mx = mysql_query ("select * from mx_table where points_to_host = \"$record\"");
			$odd_even = 0;
			while ($result_mx = mysql_fetch_array($request_mx))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 0;
				}
				$mx_record = $result_mx[record];
				$mx_name = $result_mx[name];
				$mx_priority = $result_mx[priority];
				$display_block="
					<TR BGCOLOR=$bgcolor>
						<TD>$mx_name</TD>
						<TD>$mx_priority</TD>
						<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=delete_mx&record=$mx_record\" ><IMG SRC=\"/dnstools/images/icons/ultra-small-delete.gif\" BORDER=0 ALT=\"Delete MX\"></A></TD>
					</TR>";
				printf ("$display_block");
			}
			
		}
		printf ("</TABLE>");
		endpage();
		exit;
	}
		
	else if ($function == "submit_delete_rr")
	{
		check_lock();
		list ($octet1, $octet2, $octet3, $octet4) = get_rr_ip($record);
		$host_name = get_rr_name($record);
		$points_to_domain = get_domain_from_rr ($record);
		$domain_name = get_domain_name($points_to_domain);

		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);

		$display_block="
			Deleting Round Robin IP entry... please wait";
		printf ("$display_block");
		$sql = "delete from round_robin_table where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="<P>
			Round Robin IP <strong>$octet1.$octet2.$octet3.$octet4</strong> from host $host_name deleted successfully!\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Deleted round robin IP $octet1.$octet2.$octet3.$octet4 from $host_name.$domain_name successfully.");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function == "delete_rr")
	{
		$host_name = get_rr_name($record);
		list ($octet1, $octet2, $octet3, $octet4) = get_rr_ip ($record);
		$display_block="
			<TABLE BORDER = 0 CELLSPACING = 2 CELLPADDING = 2>
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_delete_rr\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
		  Are you <STRONG>SURE</STRONG> you wish to delete the following information?
			<P>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Host Name</STRONG></TD>
					<TD>$host_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>Round Robin IP</STRONG></TD>
					<TD>$octet1.$octet2.$octet3.$octet4</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this Round Robin IP!\"></TD>
				</TR>
				<TR>
					<TD COLSPAN=2><em><font size=-1>Note: This will not remove the hostname, only this IP assocation</font></em></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "submit_list_round_robin")
	{
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		if ((! $sort_by) || ($sort_by == "host_name"))
		{
			$sql = "select round_robin_table.record as rr_record, round_robin_table.octet_1, round_robin_table.octet_2, round_robin_table.octet_3, round_robin_table.octet_4, round_robin_table.points_to_host, host_table.record as host_record, host_table.host_name as host_name, host_table.points_to_domain, host_table.octet_1 as h1, host_table.octet_2 as h2, host_table.octet_3 as h3, host_table.octet_4 as h4 from round_robin_table, host_table where host_table.points_to_domain = \"$points_to_domain\" and round_robin_table.points_to_host = host_table.record order by host_table.host_name";
		}
		else if ($sort_by == "round_robin_ip")
		{
			$sql = "select round_robin_table.record as rr_record, round_robin_table.octet_1, round_robin_table.octet_2, round_robin_table.octet_3, round_robin_table.octet_4, round_robin_table.points_to_host, host_table.record as host_record, host_table.host_name as host_name, host_table.points_to_domain, host_table.octet_1 as h1, host_table.octet_2 as h2, host_table.octet_3 as h3, host_table.octet_4 as h4 from round_robin_table, host_table where host_table.points_to_domain = \"$points_to_domain\" and round_robin_table.points_to_host = host_table.record order by round_robin_table.octet_1, round_robin_table.octet_2, round_robin_table.octet_3, round_robin_table.octet_4";
		}
		else if ($sort_by == "primary_ip")
		{
			$sql = "select round_robin_table.record as rr_record, round_robin_table.octet_1, round_robin_table.octet_2, round_robin_table.octet_3, round_robin_table.octet_4, round_robin_table.points_to_host, host_table.record as host_record, host_table.host_name as host_name, host_table.points_to_domain, host_table.octet_1 as h1, host_table.octet_2 as h2, host_table.octet_3 as h3, host_table.octet_4 as h4 from round_robin_table, host_table where host_table.points_to_domain = \"$points_to_domain\" and round_robin_table.points_to_host = host_table.record order by host_table.octet_1, host_table.octet_2, host_table.octet_3, host_table.octet_4";
		}
		$request_hosts = mysql_query ("$sql");
		$num_rows = mysql_num_rows ($request_hosts);
		if ($num_rows == 0)
		{
			$display_block="
				There are no round robin IP addresses in this domain yet.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=6 ALIGN=CENTER>Listing all Round Robin entries in <STRONG>$domain_name</STRONG></TD>
			</TR>
			<TR>
				<TD COLSPAN=6><HR></TD>
			</TR>
			<TR>
				<TD ALIGN=CENTER><STRONG>Sort by:</STRONG></TD>
				<TD ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_round_robin&points_to_domain=$points_to_domain&sort_by=round_robin_ip\">Round Robin IP</A></STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Points To</STRONG></TD>
				<TD ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_round_robin&points_to_domain=$points_to_domain&sort_by=host_name\">Original Host Name</A></STRONG></TD>
				<TD ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_round_robin&points_to_domain=$points_to_domain&sort_by=primary_ip\">Primary IP of Host</A></STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
			</TR>";
		printf ("$display_block\n");
		$num_aliases = 0;
		$odd_even = 0;
		while ($result_hosts = mysql_fetch_array($request_hosts))
		{
			$num_aliases ++;
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$rr_record = $result_hosts["rr_record"];
			$rr_octet_1 = $result_hosts["octet_1"];
			$rr_octet_2 = $result_hosts["octet_2"];
			$rr_octet_3 = $result_hosts["octet_3"];
			$rr_octet_4 = $result_hosts["octet_4"];
			$h1 = $result_hosts["h1"];
			$h2 = $result_hosts["h2"];
			$h3 = $result_hosts["h3"];
			$h4 = $result_hosts["h4"];
			$host_name = $result_hosts["host_name"];
			$display_block="
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$bgcolor\">$rr_octet_1.$rr_octet_2.$rr_octet_3.$rr_octet_4</TD>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER> -> </TD>
					<TD BGCOLOR=\"$bgcolor\">$host_name</TD>
					<TD BGCOLOR=\"$bgcolor\">$h1.$h2.$h3.$h4</TD>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=delete_rr&record=$rr_record\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete RR\" border=0></img></A></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
			</TABLE>";
		printf ("$display_block");
		endpage();
		write_log ("2", "$user_user_name", "Listed all round robin entries");
		exit;
	}

	else if ($function == "list_round_robin")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can list the round robin IP addresses that reside in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_list_round_robin\" METHOD=POST>
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR='$cell_bg_color_1'><STRONG>List Round Robin Entries</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR='$cell_bg_color_2'><STRONG>Select Domain</STRONG></TD>
				<TD ALIGN=LEFT><SELECT NAME=points_to_domain><OPTION VALUE='0'>-= SELECT DOMAIN =-";
		printf ("$display_block");
		foreach ($domain_list as $current_domain)
		{
			$domain_name = get_domain_name ($current_domain);
			printf ("<OPTION VALUE='$current_domain'>$domain_name");
		}
		$display_block="
				</SELECT></TD>
			</TR>
			<TR>
				<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE='List Aliases'></INPUT></TD>
			</TR>
		</TABLE>
		</FORM>";
		printf ("$display_block\n");
		endpage();
		exit;
	}

	else if ($function == "submit_list_aliases")
	{
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		if ($sort_by == "alias_name")
		{
			$sql = "select alias_table.record as alias_record, alias_table.alias_name as alias_name, alias_table.points_to_host as points_to_host, host_table.record as host_record, host_table.host_name as host_name, host_table.points_to_domain from alias_table, host_table where host_table.points_to_domain = \"$points_to_domain\" and alias_table.points_to_host = host_table.record order by alias_table.alias_name";
		}
		else
		{
			$sql = "select alias_table.record as alias_record, alias_table.alias_name as alias_name, alias_table.points_to_host as points_to_host, host_table.record as host_record, host_table.host_name as host_name, host_table.points_to_domain from alias_table, host_table where host_table.points_to_domain = \"$points_to_domain\" and alias_table.points_to_host = host_table.record order by host_table.host_name";
		}
		
		$request_hosts = mysql_query ("$sql");
		$num_aliases = 0;
		$odd_even = 0;
		$num_rows = mysql_num_rows ($request_hosts);
		if ($num_rows == 0)
		{
			$display_block="
				There are no aliases in this domain yet.";
			printf ("$display_block");
			endpage();
			exit;
		}

		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=6 ALIGN=CENTER>Listing all aliases in <STRONG>$domain_name</STRONG></TD>
			</TR>
			<TR>
				<TD COLSPAN=6><HR></TD>
			</TR>
			<TR>
				<TD ALIGN=CENTER><STRONG>Sort by:</STRONG></TD>
				<TD ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_aliases&points_to_domain=$points_to_domain&sort_by=alias_name\">Alias</A></STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Points To</STRONG></TD>
				<TD ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_aliases&points_to_domain=$points_to_domain&sort_by=host_name\">Original Host</A></STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
			</TR>";
		printf ("$display_block\n");
		while ($result_hosts = mysql_fetch_array($request_hosts))
		{
			$num_aliases ++;
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$alias_record = $result_hosts["alias_record"];
			$alias_name = $result_hosts["alias_name"];
			$host_name = $result_hosts["host_name"];
			$display_block="
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$bgcolor\">$alias_name</TD>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER> -> </TD>
					<TD BGCOLOR=\"$bgcolor\">$host_name</TD>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=delete_alias&record=$alias_record\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete Alias\" border=0></img></A></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
			</TABLE>";
		printf ("$display_block");
		endpage();
		write_log ("2", "$user_user_name", "Listed all hosts");
		exit;
	}

	else if ($function == "delete_alias")
	{
		$alias_name = get_alias_name ($record);
		$display_block="
			<TABLE BORDER = 0 CELLSPACING = 2 CELLPADDING = 2>
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_delete_alias\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
		  Are you <STRONG>SURE</STRONG> you wish to delete the following information?
			<P>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Alias Name</STRONG></TD>
					<TD>$alias_name</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this alias!\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "list_aliases")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can list the aliases that reside in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_list_aliases\" METHOD=POST>
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>List Aliases</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Select Domain</STRONG></TD>
				<TD ALIGN=LEFT><SELECT NAME=points_to_domain><OPTION VALUE='0'>-= SELECT DOMAIN =-";
		printf ("$display_block");
		foreach ($domain_list as $current_domain)
		{
			$domain_name = get_domain_name ($current_domain);
			printf ("<OPTION VALUE='$current_domain'>$domain_name");
		}
		$display_block="
				</SELECT></TD>
			</TR>
			<TR>
				<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE='List Aliases'></INPUT></TD>
			</TR>
		</TABLE>
		</FORM>";
		printf ("$display_block\n");
		endpage();
		exit;
	}

	else if ($function == "submit_delete_alias")
	{
		check_lock();

		$request_alias_info = mysql_query ("select points_to_host, alias_name from alias_table where record = \"$record\"");
		$result_alias_info = mysql_fetch_array($request_alias_info);
		$alias_name = $result_alias_info[alias_name];
		$points_to_host = $result_alias_info[points_to_host];
		$request_host_info = mysql_query("select * from host_table where record = \"$points_to_host\"");
		$result_host_info = mysql_fetch_array($request_host_info);
		$points_to_domain = $result_host_info[points_to_domain];
		$domain_name = get_domain_name ($points_to_domain);

		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);

		$display_block="
			Deleting alias entry... please wait";
		printf ("$display_block");
		$sql = "delete from alias_table where record=\"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="<P>
			Alias <strong>$alias_name</strong> deleted successfully!\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Deleted alias $alias_name from $domain_name successfully.");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function == "submit_add_alias")
	{
		check_lock();
		check_valid_alias_name ($alias_name);
		$request_host_info = mysql_query("select host_name, points_to_domain from host_table where record = \"$record\"");
		$result_host_info = mysql_fetch_array($request_host_info);
		$points_to_domain = $result_host_info[points_to_domain];
		$host_name = $result_host_info[host_name];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		$display_block="
			Adding alias entry... please wait";
		printf ("$display_block");
		$request_duplicate_alias = mysql_query ("select alias_table.record, alias_table.points_to_host, host_table.record, host_table.points_to_domain, domain_table.record from alias_table, host_table, domain_table where alias_table.alias_name = \"$alias_name\" and alias_table.points_to_host = host_table.record and host_table.points_to_domain = \"$points_to_domain\"");
		$num_rows = mysql_num_rows ($request_duplicate_alias);
		if ($num_rows != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>There is already an alias with that name in this domain.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$request_duplicate_alias_hostname = mysql_query ("select host_table.record from host_table, alias_table, domain_table where host_table.host_name = \"$alias_name\" and host_table.points_to_domain = \"$points_to_domain\"");
		$num_rows = mysql_num_rows ($request_duplicate_alias_hostname);
		if ($num_rows != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>There is already a hostname with that name in this domain.<P>
				You cannot add an alias to a domain if the name already exists as a hostname or alias name.";
			printf ("$display_block");
			endpage();
			exit;
		}

		$sql = "insert into alias_table (alias_name, points_to_host) values (\"$alias_name\", \"$record\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="<P>
			Added alias -<strong>$alias_name</strong>- successfully!\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Added alias $alias_name to $host_name.$domain_name successfully.");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function == "add_alias")
	{
		$request_host_info = mysql_query("select * from host_table where record = \"$record\"");
		$result_host_info = mysql_fetch_array($request_host_info);
		$host_name = $result_host_info[host_name];
		$octet_1 = $result_host_info[octet_1];
		$octet_2 = $result_host_info[octet_2];
		$octet_3 = $result_host_info[octet_3];
		$octet_4 = $result_host_info[octet_4];
		$points_to_domain = $result_host_info[points_to_domain];
		$domain_name = get_domain_name ($points_to_domain);
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_add_alias\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE='$record'></INPUT>
			<TABLE>
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$cell_bg_color_1\">Add New Alias</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>Full Hostname</STRONG></TD>
					<TD>$host_name.$domain_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>IP Address</STRONG></TD>
					<TD>$octet_1.$octet_2.$octet_3.$octet_4</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>Alias</STRONG></TD>
					<TD><INPUT TYPE=TEXT SIZE=15 MAXLENGTH=254 NAME=alias_name></INPUT></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE='Submit'></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "submit_add_round_robin")
	{
		check_lock();
		$request_domain_record = mysql_query("select host_name, points_to_domain from host_table where record = \"$record\"");
		$result_domain_record = mysql_fetch_array($request_domain_record);
		$points_to_domain = $result_domain_record[points_to_domain];
		$host_name = $result_domain_record[host_name];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		check_valid_ip ($octet_1, $octet_2, $octet_3, $octet_4);
		$display_block="
			Adding new round robin entry... please wait";
		printf ("$display_block");
		$sql = "insert into round_robin_table (points_to_host, octet_1, octet_2, octet_3, octet_4) values (\"$record\", \"$octet_1\", \"$octet_2\", \"$octet_3\", \"$octet_4\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="<P>
			Added new round robin entry <strong>$octet_1.$octet_2.$octet_3.$octet_4</strong> to host <strong>$host_name.$domain_name</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added new round robin entry $octet_1.$octet_2.$octet_3.$octet_4 to $host_name.$domain_name");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function == "add_round_robin")
	{
		$request_host_info = mysql_query("select * from host_table where record = \"$record\"");
		$result_host_info = mysql_fetch_array($request_host_info);
		$host_name = $result_host_info[host_name];
		$octet_1 = $result_host_info[octet_1];
		$octet_2 = $result_host_info[octet_2];
		$octet_3 = $result_host_info[octet_3];
		$octet_4 = $result_host_info[octet_4];
		$points_to_domain = $result_host_info[points_to_domain];
		$domain_name = get_domain_name ($points_to_domain);
		right_justified_help_link ("add_round_robin");
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_add_round_robin\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE='$record'></INPUT>
			<TABLE>
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$cell_bg_color_1\">Add New Round Robin IP Address</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\" ALIGN=RIGHT VALIGN=CENTER><a href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=full_hostname','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>Full Hostname</STRONG></a></TD>
					<TD>$host_name.$domain_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\" ALIGN=RIGHT VALIGN=CENTER><a href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=ip_address','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>IP Address</STRONG></a></TD>
					<TD>$octet_1.$octet_2.$octet_3.$octet_4</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\" ALIGN=RIGHT VALIGN=CENTER><a href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=round_robin_ip','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>Round Robin IP</STRONG></a></TD>
					<TD>
							<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_1></INPUT> .
							<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_2></INPUT> .
							<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_3></INPUT> .
							<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_4></INPUT></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE='Submit'></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "submit_list_hosts")
	{
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($points_to_domain);
		$request_hosts = mysql_query ("select record from host_table where points_to_domain = \"$points_to_domain\"");
		$num_rows = mysql_num_rows ($request_hosts);
		if ($num_rows == 0)
		{
			$display_block="
				There are no hosts in this domain yet.";
			printf ("$display_block");
			endpage();
			exit;
		}
		right_justified_help_link ("list_hosts");
		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=8 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\">Listing all hosts in <STRONG>$domain_name</STRONG></TD>
			</TR>";
		printf ("$display_block");
		$request_num_hosts = mysql_query ("select count(*) as num_hosts from host_table where points_to_domain = \"$points_to_domain\"");
		$result_num_hosts = mysql_fetch_array($request_num_hosts);
		$num_hosts = $result_num_hosts[num_hosts];
		$display_block="
			<TR>
				<TD COLSPAN=8 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_2\">Total hosts in domain: <STRONG>$num_hosts</STRONG></TD>
			</TR>
			<TR>
				<TD VALIGN=BOTTOM ALIGN=CENTER>Sort by:</TD>
				<TD VALIGN=BOTTOM ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_hosts&points_to_domain=$points_to_domain&sort_by=host\">Host Name</A></STRONG></TD>
				<TD VALIGN=BOTTOM ALIGN=CENTER><STRONG><A HREF=\"$PHP_SELF?section=hosts&function=submit_list_hosts&points_to_domain=$points_to_domain&sort_by=ip\">IP Address</A></STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Add<BR>Round<BR>Robin</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Add<BR>Alias</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>View<BR>Host</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Modify<BR>Host</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Delete<BR>Host</STRONG></TD>
			</TR>";
		printf ("$display_block\n");
		if ($sort_by == "ip")
		{
			$request_hosts = mysql_query ("select record, host_name, octet_1, octet_2, octet_3, octet_4 from host_table where points_to_domain = \"$points_to_domain\" order by octet_1, octet_2, octet_3, octet_4");
		}
		else 
		{
			$request_hosts = mysql_query ("select record, host_name, octet_1, octet_2, octet_3, octet_4 from host_table where points_to_domain = \"$points_to_domain\" order by host_name");
		}
		$odd_even = 0;
		while ($result_hosts = mysql_fetch_array($request_hosts))
		{
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$record_number = $result_hosts[record];
			$host_name = $result_hosts[host_name];
			$octet_1 = $result_hosts[octet_1];
			$octet_2 = $result_hosts[octet_2];
			$octet_3 = $result_hosts[octet_3];
			$octet_4 = $result_hosts[octet_4];
			$display_block="
				<TR BGCOLOR=\"$bgcolor\">
					<TD BGCOLOR=\"white\"></TD>
					<TD>$host_name</TD>
					<TD>$octet_1.$octet_2.$octet_3.$octet_4</TD>
					<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=add_round_robin&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-round-robin.gif\" alt=\"Add RR\" border=0></img></A></TD>
					<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=add_alias&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-alias.gif\" alt=\"Add Alias\" border=0></img></A></TD>
					<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=view_host&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-view.gif\" alt=\"View Host\" border=0></img></A></TD>
					<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=modify_host&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-modify.gif\" alt=\"Modify Host\" border=0></img></A></TD>
					<TD ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=hosts&function=delete_host&record=$record_number\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete Host\" border=0></img></A></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="</TABLE>";
		printf ("$display_block");
		endpage();
		write_log ("2", "$user_user_name", "Listed all hosts");
		exit;
	}

	else if ($function == "list_hosts")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can list the hosts that reside in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_list_hosts\" METHOD=POST>
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>List Hosts</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Select Domain</STRONG></TD>
				<TD ALIGN=LEFT><SELECT NAME=points_to_domain><OPTION VALUE='0'>-= SELECT DOMAIN =-";
		printf ("$display_block");
		foreach ($domain_list as $current_domain)
		{
			$domain_name = get_domain_name ($current_domain);
			printf ("<OPTION VALUE='$current_domain'>$domain_name");
		}
		$display_block="
				</SELECT></TD>
			</TR>
			<TR>
				<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE='List Hosts'></INPUT></TD>
			</TR>
		</TABLE>
		</FORM>";
		printf ("$display_block\n");
		endpage();
		exit;
	}

	else if ($function == "submit_delete_host_with_override")
	{
		check_lock();
		$host_name = get_host_name ($record);
		$points_to_domain = get_domain_from_host ($record);
		$domain_name = get_domain_name($points_to_domain);
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$display_block="
			Now deleting the host $host_name.$domain_name and all aliases (CNAMEs), round robin IP addresses associated with it ... please wait";
		printf ("$display_block");
		force_delete_host($record, $user_user_name);
		$display_block="
			<P>Finished!</P>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "submit_delete_host")
	{
		$has_dependencies = 0;
		check_lock();

		$host_name = get_host_name($record);
		$request_host_info = mysql_query("select * from host_table where record = \"$record\"");
		$result_host_info = mysql_fetch_array($request_host_info);
		$points_to_domain = $result_host_info[points_to_domain];
		$domain_name = get_domain_name ($points_to_domain);

		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);

		$request_dep_rr = mysql_query ("select * from round_robin_table where points_to_host = \"$record\"");
    $num_rows_returned = mysql_numrows($request_dep_rr);
		if ($num_rows_returned != 0)
		{
			$has_dependencies = 1;
		}

		$request_dep_alias = mysql_query ("select * from alias_table where points_to_host = \"$record\"");
    $num_rows_returned = mysql_numrows($request_dep_alias);
		if ($num_rows_returned != 0)
		{
			$has_dependencies = 1;
		}

		$request_dep_mx = mysql_query ("select * from mx_table where points_to_host = \"$record\"");
    $num_rows_returned = mysql_numrows($request_dep_mx);
		if ($num_rows_returned != 0)
		{
			$has_dependencies = 1;
		}

		if ($has_dependencies == 0)
		{
			$display_block="
				Deleting host $host_name.$domain_name ... please wait";
			printf ("$display_block");
			force_delete_host ($record, $user_user_name);
			$display_block="<P>
				Host <strong>$host_name.$domain_name</strong> deleted successfully!\n";
			printf ("$display_block");
			update_domain_serial ($points_to_domain);
			update_ip_serial_by_host ($record);
			write_log ("1", "$user_user_name", "Deleted host $host_name.$domain_name successfully.");
		}
		else
		{
			$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_delete_host_with_override\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
				<FONT COLOR=\"RED\">WARNING!</FONT><BR>There are one or more aliases, Round Robin IP address or Mail Exchange (MX) entries that depend on this host.<P>
				Are you <STRONG>absolutely sure</STRONG> you wish to delete this host and all of the aliases and round robin IP entries that are associated with this host?<P>
				<INPUT TYPE=SUBMIT VALUE=\"YES! Now Delete it all!\"></INPUT>
			</FORM>";
			printf ("$display_block");
		}
		endpage();
		exit;
	}

	else if ($function == "delete_host")
	{
		$host_name = get_host_name($record);
		list ($octet_1, $octet_2, $octet_3, $octet_4) = get_ip ($record);
		$request_host_info = mysql_query ("select hinfo_1, hinfo_2, ttl from host_table where record = \"$record\"");
		$result_host_info = mysql_fetch_array ($request_host_info);
		$hinfo_1 = $result_host_info[hinfo_1];
		$hinfo_2 = $result_host_info[hinfo_2];
		$ttl = $result_host_info[ttl];
		if ($ttl == 0)
		{
			$ttl = "Domain Default";
		}
		$display_block="
			<TABLE BORDER = 0 CELLSPACING = 2 CELLPADDING = 2>
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_delete_host\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
		  Are you <STRONG>SURE</STRONG> you wish to delete the following information?
			<P>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Host Name</STRONG></TD>
					<TD>$host_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>IP Address</STRONG></TD>
					<TD>$octet_1.$octet_2.$octet_3.$octet_4</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>HINFO 1</STRONG></TD>
					<TD>$hinfo_1</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>HINFO 2</STRONG></TD>
					<TD>$hinfo_2</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>TTL</STRONG></TD>
					<TD>$ttl</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this Host!\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "submit_add_host")
	{
		check_lock();
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		if ($host_name == "")
		{
			$display_block="
				You must enter a host name.";
			printf ("$display_block");
			endpage();
			exit;
		}

	  $display_block="
		  Please wait.... adding new host now.<p>";
		check_valid_hostname ($host_name);
		check_valid_ip ($octet_1, $octet_2, $octet_3, $octet_4);
		printf ("$display_block");
		$request_rows = mysql_query("select * from host_table where host_name = \"$host_name\" and points_to_domain = \"$points_to_domain\"");
    $num_rows_returned = mysql_numrows($request_rows);
		if ($num_rows_returned != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate host name in this domain.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		if (! $selected_octet_4)
		{
			$octet_4 = $octet_4;
		}
		else
		{
			$octet_4 = $selected_octet_4;
		}

		$domain_name = get_domain_name ($points_to_domain);
		$sql = "insert into host_table (host_name, octet_1, octet_2, octet_3, octet_4, registered_ip, points_to_domain, hinfo_1, hinfo_2, ttl) values (\"$host_name\", \"$octet_1\", \"$octet_2\", \"$octet_3\", \"$octet_4\", \"$registered_ip\", \"$points_to_domain\", \"$hinfo_1\", \"$hinfo_2\", \"$ttl\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added new host name <strong>$host_name</strong> into domain <strong>$domain_name</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added new host $host_name into domain $domain_name");
		update_domain_serial ($points_to_domain);
		$request_host_record = mysql_query ("select * from host_table where host_name = \"$host_name\" and points_to_domain = \"$points_to_domain\"");
		$result_host_record = mysql_fetch_array ($request_host_record);
		$host_record = $result_host_record [record];
		update_ip_serial_by_host ($host_record);
		endpage();
		exit;
	}

	else if ($function == "add_host_4")
	{
		if ($registered_ip == "YES")
		{
			$sql = "select octet_1, octet_2, octet_3, points_to_domain from ip_range_table where record = \"$ip_range_record\"";
			$request_ip_range_info = mysql_query ("$sql");
			$result_ip_range_info = mysql_fetch_array ($request_ip_range_info);
			$octet_1 = $result_ip_range_info[octet_1];
			$octet_2 = $result_ip_range_info[octet_2];
			$octet_3 = $result_ip_range_info[octet_3];
			$octet_4 = $selected_octet_4;
			$points_to_domain = $result_ip_range_info[points_to_domain];
			$full_ip = "$octet_1.$octet_2.$octet_3.$selected_octet_4";
			$domain_name = get_domain_name ($points_to_domain);
		}
		else
		{
			$full_ip = "$octet_1.$octet_2.$octet_3.$octet_4";
			$domain_name = get_domain_name ($points_to_domain);
		}
		check_valid_ip ($octet_1, $octet_2, $octet_3, $octet_4);

		right_justified_help_link ("new_host_step_4");

		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=submit_add_host\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=full_ip VALUE=\"$full_ip\">
			<INPUT TYPE=HIDDEN NAME=registered_ip VALUE=\"$registered_ip\">
			<INPUT TYPE=HIDDEN NAME=points_to_domain VALUE=\"$points_to_domain\">
			<INPUT TYPE=HIDDEN NAME=octet_1 VALUE=\"$octet_1\">
			<INPUT TYPE=HIDDEN NAME=octet_2 VALUE=\"$octet_2\">
			<INPUT TYPE=HIDDEN NAME=octet_3 VALUE=\"$octet_3\">
			<INPUT TYPE=HIDDEN NAME=octet_4 VALUE=\"$octet_4\">
			<INPUT TYPE=HIDDEN NAME=selected_octet_4 VALUE=\"$selected_octet_4\">
			<TABLE CELLPADDING=2 CELLSPACING=2 BORDER=0>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Add New Host - Final Step</STRONG></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT VALIGN=CENTER BGCOLOR=\"$cell_bg_color_2\"><a href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=hostname','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>Hostname</STRONG></a></TD>
					<TD><INPUT TYPE=TEXT NAME=host_name SIZE=15 MAXLENGTH=254></INPUT>.$domain_name</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT VALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><A href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=ip_address','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>IP Address</STRONG></A></TD>
					<TD>$full_ip</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT VALIGN=CENTER BGCOLOR=\"$cell_bg_color_2\"><A href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=registered_ip','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>Registered IP</STRONG></A></TD>
					<TD>$registered_ip</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT VALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><A href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=ttl','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>Time To Live</STRONG></A><br><font size=-1>'0' for default</font></TD>
					<TD><INPUT TYPE=TEXT SIZE=6 MAXLENGTH=6 NAME=ttl VALUE=\"0\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT VALIGN=CENTER BGCOLOR=\"$cell_bg_color_2\"><A href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=hinfo_1','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>HINFO 1</STRONG></A></TD>
					<TD><INPUT TYPE=TEXT SIZE=15 MAXLENGTH=254 NAME=hinfo_1></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT VALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><A href=\"#\" onClick=\"MyWindow=window.open('$PHP_SELF/dnstools/dnstools.php?section=help&function=help_screen&subject=hinfo_2','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=no,width=800,height=400,left=100,top=100'); return false;\"><STRONG>HINFO 2</STRONG></A></TD>
					<TD><INPUT TYPE=TEXT SIZE=15 MAXLENGTH=254 NAME=hinfo_2></INPUT></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE=\"Submit\"></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "add_host_3")
	{
		if ($ip_range_record == "0")
		{
			$display_block="
				You did not select an IP Range!";
			printf ("$display_block");
			endpage();
			exit;
		}

		right_justified_help_link ("new_host_step_3");
		// You should only be here if you are using a registered IP
		$sql = "select octet_1, octet_2, octet_3, octet_4, bitmask from ip_range_table where record = \"$ip_range_record\"";
		$request_ip_range_info = mysql_query ("$sql");
		$result_ip_range_info = mysql_fetch_array ($request_ip_range_info);
		$octet_1 = $result_ip_range_info[octet_1];
		$octet_2 = $result_ip_range_info[octet_2];
		$octet_3 = $result_ip_range_info[octet_3];
		$octet_4 = $result_ip_range_info[octet_4];
		$bitmask = $result_ip_range_info[bitmask];
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=add_host_4\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=ip_range_record VALUE=\"$ip_range_record\">
			<INPUT TYPE=HIDDEN NAME=registered_ip VALUE=\"YES\">
			<TABLE CELLPADDING=2 CELLSPACING=2 BORDER=0>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Add New Host - Step 3</STRONG></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><STRONG>Available IP Addresses</STRONG></TD>
				</TR>
				<TR>
					<TD>Select available IP Address from $octet_1.$octet_2.$octet_3.$octet_4/$bitmask</TD>
					<TD><SELECT NAME=selected_octet_4>";
		printf ("$display_block");
		$max_ip = get_max_ip($octet_4, $bitmask);
		$max_ip --;
		$current_ip = $octet_4;
		while ($current_ip <= $max_ip)
		{
			$request_host_info = mysql_query("select * from host_table where registered_ip = \"YES\" and octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and octet_4 = \"$current_ip\"");
			$rows_returned = mysql_numrows($request_host_info);
			if ($rows_returned == 0)
			{
				$display_block="
					<OPTION VALUE=\"$current_ip\">$octet_1.$octet_2.$octet_3.$current_ip";
				printf ("$display_block");
			}
			$current_ip ++;
		}
		$display_block="
					</SELECT></TD>
				<TR>
					<TD ALIGN=CENTER COLSPAN=2><INPUT TYPE=SUBMIT VALUE=\"Next Step ->\"></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function == "add_host_2")
	{
		right_justified_help_link ("new_host_step_2");
		$display_block="
			<TABLE CELLPADDING=2 CELLSPACING=2 BORDER=0>
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Add New Host - Step 2</STRONG></TD>
				</TR>";
		printf ("$display_block");
		$ip_range_list = which_ip_ranges($domain_record);
		if (! $ip_range_list)
		{
			$display_block="
				<TR>
					<TD></TD>
					<TD WIDTH=400 ALIGN=LEFT>There are no IP ranges registered to this domain.<BR>You will be forced to use an unregistered IP.</TD>
				</TR>";
			printf ("$display_block");
		}
		else
		{
			$display_block="
				<FORM ACTION=\"$PHP_SELF?section=hosts&function=add_host_3\" METHOD=POST>
				<INPUT TYPE=HIDDEN NAME=registered_ip VALUE=\"YES\"></INPUT>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\" ALIGN=LEFT>Assign a registered IP<br>address from this IP Range</TD>
					<TD><SELECT NAME=ip_range_record>
							<OPTION VALUE=\"0\">-= SELECT IP RANGE =-";
			printf ("$display_block");
			foreach ($ip_range_list as $range_ip_record)
			{
				$request_ip_range_record = mysql_query ("select octet_1, octet_2, octet_3, octet_4, bitmask from ip_range_table where record = \"$range_ip_record\"");
				while ($result_ip_range_record = mysql_fetch_array($request_ip_range_record))
				{
					$octet_1 = $result_ip_range_record[octet_1];
					$octet_2 = $result_ip_range_record[octet_2];
					$octet_3 = $result_ip_range_record[octet_3];
					$octet_4 = $result_ip_range_record[octet_4];
					$bitmask = $result_ip_range_record[bitmask];
					$max_ip = get_max_ip ($octet_4, $bitmask);
					$max_ip --;
					$display_block="
							<OPTION VALUE=\"$range_ip_record\">$octet_1.$octet_2.$octet_3.$octet_4 to $octet_1.$octet_2.$octet_3.$max_ip";
					printf ("$display_block");
				}
			}
			$display_block="
					</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Next Step ->\"></INPUT></TD>
				</TR>
				</FORM>
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><STRONG><EM> - OR - </EM></STRONG></TD>
				</TR>
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
				<FORM ACTION=\"$PHP_SELF?section=hosts&function=add_host_4\" METHOD=POST>
				<INPUT TYPE=HIDDEN NAME=registered_ip VALUE=\"NO\"></INPUT>
				<INPUT TYPE=HIDDEN NAME=points_to_domain VALUE=\"$domain_record\"></INPUT>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\" ALIGN=RIGHT>Assign an unregistered address</TD>
					<TD ALIGN=LEFT>
						<INPUT TYPE=TEXT NAME=octet_1 SIZE=3 MAXLENGTH=3></INPUT>.
						<INPUT TYPE=TEXT NAME=octet_2 SIZE=3 MAXLENGTH=3></INPUT>.
						<INPUT TYPE=TEXT NAME=octet_3 SIZE=3 MAXLENGTH=3></INPUT>.
						<INPUT TYPE=TEXT NAME=octet_4 SIZE=3 MAXLENGTH=3></INPUT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Next Step ->\"></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
			printf ("$display_block");
			endpage();
			exit;
	}

	else if ($function == "add_host_1")
	{
		$group_list = which_groups($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can add a host.";
			printf ("$display_block");
			endpage();
			exit;
		}
		right_justified_help_link ("new_host_step_1");
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=hosts&function=add_host_2\" METHOD=POST>
			<TABLE CELLPADDING=2 CELLSPACING=2 BORDER=0>
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Add New Host - Step 1</FONT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\">Select Domain</TD>
					<TD><SELECT NAME=domain_record>";
		printf ("$display_block");
		foreach ($domain_list as $domain_record)
		{
			$domain_name = get_domain_name($domain_record);
			$display_block="
						<OPTION VALUE=\"$domain_record\">$domain_name";
			printf ("$display_block");
		}
		$display_block="
						</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE=\"Next Step ->\"></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if (! $function)
	{
		$display_block="
			<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>Hosts</STRONG></TD>
				</TR>
				<TR>
				  <TD><A HREF=\"$PHP_SELF?section=hosts&function=add_host_1\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=hosts&function=add_host_1\">Add Host</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=hosts&function=list_hosts\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=hosts&function=list_hosts\">List Hosts</A></TD>
				</TR>
				<TR>
					<TD COLSPAN=4><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>Aliases and Round Robin Entries</STRONG></TD>
				</TR>
			  <TR>
				  <TD><A HREF=\"$PHP_SELF?section=hosts&function=list_aliases\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=hosts&function=list_aliases\">List Aliases</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=hosts&function=list_round_robin\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=hosts&function=list_round_robin\">List Round Robins</A></TD>
				</TR>
				<TR>
					<TD COLSPAN=4><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>Host Specific MX Records</STRONG></TD>
				</TR>
			  <TR>
				  <TD><A HREF=\"$PHP_SELF?section=hosts&function=add_mx_to_host\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=hosts&function=add_mx_to_host\">Add MX to Host</A></TD>
				</TR>";
		printf ("$display_block");
		printf ("</TABLE>");
		endpage();
		exit;
	}
}

# SECTION HOSTS

else if ($section=="help")
{
	beginpage("help");
	check_login ($user_user_name, $user_password);
	if ($function=="help_screen")
	{
		$request_help = mysql_query ("select help_description from help_table where help_name = \"$subject\"");
		$num_rows = mysql_num_rows ($request_help);
		$result_help = mysql_fetch_array ($request_help);
		$help_description = $result_help[help_description];
		if ($num_rows == 0)
		{
			$display_block="
				There is no help for $subject";
			printf ("$display_block");
			endpage();
			exit;
		}
		else
		{
			{
				$display_block="
					$help_description";
				printf ("$display_block");
				endpage();
				exit;
			}
		}
	}
	else
	{
		$display_block="
			<BR><P><FONT size=+1><STRONG>HELP</STRONG></font><P>
			<TABLE BORDER=0 CELLSPACING = 2 CELLPADDING = 2 WIDTH=450>
			<TR>
				<TR>
					<TD><P>DNSTools 2.0 has an online help system with both concise and tutorial-like
							help screens.</P>
							
							<P>On many screens you will see the word \"Help!\" in the upper right of
							the page.  By clicking this link, a new screen will open with help specific to the
							page you are on.</P>
							
							<P>Additionally, some screens contain highlighted text within the page itself.  By clicking on
							this text, DNSTools will pop up a help screen explaining what that word or field entry means.</P>

							<P>Note:  Some highlighted (or underlined) text is used to re-sort tables in lists.  They are not
							help screens.</P></TD></TR></TABLE>";
		printf ("$display_block");
		endpage();
		exit;
	}
}

# SECTION LEFTMENU

else if ($section=="leftmenu")
{
  leftmenu();
}

# SECTION LOGOUT

else if ($section=="logout")
{
  if (($PHPSESSID) && ($user_logged_in=="true"))
  {
		write_log ("1", "$user_user_name", "Logout");
    session_unregister("user_logged_in");
    session_unset();
    unlink(session_save_path().'/sess_'.$PHPSESSID);
    setcookie('$PHPSESSID','',time()-3600,'/');
    beginpage("logout");
    $display_block="
      You have been logged out.<BR>";
    printf ("$display_block\n");
    endpage();
  }
  else
  {
    beginpage("logout");
    $display_block="
      You are not logged in, no need to logout.</TD>";
    printf ("$display_block");
  }
}

# SECTION SUBMIT_LOGIN

else if ($section=="submit_login")
{
  if ($user_logged_in!="true")
  {
    $request_login_info = mysql_query("select * from user_table where user_name = \"$user_name\" and password = password(\"$password\")");
    $rows_returned = mysql_numrows($request_login_info);
    if ($rows_returned != 0)
    {
      $result_login_info = mysql_fetch_array($request_login_info);
      session_register("user_record");
      session_register("user_user_name");
      session_register("user_full_name");
      session_register("user_password");
      session_register("user_work_phone");
      session_register("user_mobile_phone");
      session_register("user_email");
      session_register("user_uses_group_address");
      session_register("user_address");
      session_register("user_notes");
      session_register("user_dnstools_administrator");
      session_register("user_logged_in");
      $user_record = $result_login_info[record];
      $user_user_name = $result_login_info[user_name];
      $user_full_name = $result_login_info[full_name];
      $user_password = $result_login_info[password];
      $user_work_phone = $result_login_info[work_phone];
      $user_mobile_phone = $result_login_info[mobile_phone];
      $user_email = $result_login_info[email];
      $user_uses_group_address = $result_login_info[uses_group_address];
      $user_address = $result_login_info[address];
      $user_notes = $result_login_info[notes];
			$user_dnstools_administrator = $result_login_info[dnstools_administrator];
			$user_logged_in="true";
			write_log ("1", "$user_user_name", "Login from $ip_address");
      header("Location: $PHP_SELF");
    }
    else
    {
      header("Location: $PHP_SELF?section=login");
    }
  }
  else if (($PHPSESSID) && ($user_logged_in=="true"))
  {
    beginpage("login");
    $display_block="
      You are already logged in.\n";
    printf ("$display_block\n");
  }
}

# SECTION USERS

else if ($section=="users")
{
  beginpage("users");
	check_login ($user_user_name, $user_password);

	if ($function=="list_users")
	{
	  check_administrator($user_dnstools_administrator, $user_user_name);
		write_log ("2", "$user_user_name", "Listed all users");
		$display_block="
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD COLSPAN=5 ALIGN=CENTER bgcolor=\"$cell_bg_color_1\">Listing All Users</TD>
				</TR>";
		printf ("$display_block");
		$request_num_users = mysql_query ("select count(*) as num_users from user_table");
		$result_num_users = mysql_fetch_array($request_num_users);
		$num_users = $result_num_users[num_users];
		$display_block="
				<TR>
					<TD COLSPAN=5 ALIGN=CENTER bgcolor=\"$cell_bg_color_2\">Total users: <STRONG>$num_users</STRONG></TD>
				</TR>
				<TR>
					<TD><STRONG>Full Name</STRONG></TD>
					<TD><STRONG>User Name</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>View</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>Modify</STRONG></TD>
					<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
				</TR>";
		printf ("$display_block");
		$request_all_users = mysql_query ("select record, user_name, full_name from user_table order by full_name");
		$odd_even=0;
		while ($result_all_users = mysql_fetch_array($request_all_users))
		{
			$temp_record = $result_all_users[record];
			$temp_user_name = $result_all_users[user_name];
			$temp_full_name = $result_all_users[full_name];
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$display_block="
				<TR>
					<TD BGCOLOR=\"$bgcolor\">$temp_full_name</TD>
					<TD BGCOLOR=\"$bgcolor\">$temp_user_name</TD>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=users&function=view_user&record=$temp_record\"><IMG src=\"/dnstools/images/icons/ultra-small-view.gif\" alt=\"View User\" border=0></img></a></td>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=users&function=modify_user&record=$temp_record\"><IMG src=\"/dnstools/images/icons/ultra-small-modify.gif\" alt=\"Modify User\" border=0></img></a></td>
					<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=users&function=submit_delete_user&temp_user_record=$temp_record\"><IMG src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete User\" border=0></img></a></td>
				</TR>";
			printf ("$display_block");
		}
		printf ("</TABLE>\n");
		endpage();
		exit;
	}

  else if ($function=="view_user")
	{
	  check_administrator($user_dnstools_administrator, $user_user_name);
		$request_user_info = mysql_query("select * from user_table where record=\"$record\"");
		$result_user_info = mysql_fetch_array($request_user_info);
		$temp_user_name = $result_user_info[user_name];
		$temp_full_name = $result_user_info[full_name];
		$temp_email = $result_user_info[email];
		$temp_work_phone = $result_user_info[work_phone];
		$temp_mobile_phone = $result_user_info[mobile_phone];
		$temp_fax_number = $result_user_info[fax_number];
		$temp_pager_number = $result_user_info[pager_number];
		$temp_address = $result_user_info[address];
		$temp_notes = $result_user_info[notes];
		$temp_dnstools_administrator = $result_user_info[dnstools_administrator];
		write_log ("2", "$user_user_name", "Viewed user $temp_user_name");

 	  $display_block="
			<TABLE>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\" COLSPAN=2 ALIGN=CENTER><STRONG>USER INFORMATION</STRONG></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>User #:</STRONG></TD>
					<TD ALIGN=LEFT>$record</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Username:</STRONG></TD>
					<TD ALIGN=LEFT>$temp_user_name</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Full Name:</STRONG></TD>
					<TD ALIGN=LEFT>$temp_full_name</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Email:</STRONG></TD>
					<TD ALIGN=LEFT>$temp_email</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Work Phone</STRONG></TD>
					<TD ALIGN=LEFT>$temp_work_phone</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Mobile Phone</STRONG></TD>
					<TD ALIGN=LEFT>$temp_mobile_phone</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT  BGCOLOR=\"$cell_bg_color_2\"VALIGN=TOP><STRONG>Address</STRONG></TD>
					<TD ALIGN=LEFT VALIGN=TOP><pre>$temp_address</pre></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT  BGCOLOR=\"$cell_bg_color_1\"VALIGN=TOP><STRONG>Notes</STRONG></TD>
					<TD ALIGN=LEFT VALIGN=TOP><pre>$temp_notes</pre></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>DNSTools Administrator</STRONG></TD>
					<TD ALIGN=LEFT>$temp_dnstools_administrator</TD>
				</TR>";
		printf ("$display_block");
		list_groups_by_user($record);
		printf ("</TABLE>");
		endpage();
		exit;
	}

	else if ($function=="submit_change_password")
	{
		check_lock();
		if (($DEMO == "YES") && ($user_dnstools_administrator != "YES"))
		{
			$display_block="
				Sorry, you cannot change passwords in the online demo version.";
			printf ("$display_block");
			endpage();
			exit;
		}

		if ($new_password != $verify_new_password)
		{
			$display_block="
				<STRONG><FONT COLOR=RED>WARNING!</FONT></STRONG> New passwords did not match!<BR>";
			printf ("$display_block");
			write_log ("2", "$user_user_name", "Attempted to change password but verification password did not match");
			endpage();
			exit;
		}

		if (($user_dnstools_administrator != "YES") && ($record != $user_record))
		{
			$display_block="
				<STRONG><FONT COLOR=RED>WARNING!</FONT></STRONG> You are not allowed to change this password!<BR>
				This event is being logged.";
			printf ("$display_block");
			write_log ("1", "$user_user_name", "WARNING! Tried to change password for user # $record");
			endpage();
			exit;
		}

		if ($user_dnstools_administrator != "YES")
		{
	    $request_login_info = mysql_query("select * from user_table where record = \"$record\" and password = password(\"$current_password\")");
			$num_rows_returned = mysql_numrows($request_login_info);
			if ($num_rows_returned == 0)
			{
				$display_block="
					<STRONG><FONT COLOR=RED>WARNING!</FONT></STRONG> The <em>current</em> password you entered is not correct!<BR>";
				printf ("$display_block");
				write_log ("1", "$user_user_name", "WARNING! Entered wrong current password while attempting to change password");
				endpage();
				exit;
			}
			else
			{
				$sql = "update user_table SET password = password(\"$new_password\") where record = \"$record\"";
				$result = mysql_query($sql) or die (mysql_error());
				printf ("Password successfully changed.");
				printf ("<P><STRONG>You will now need LOGOUT and log back in with the new password.</STRONG></P>\n");
	 		 	write_log ("1", "$user_user_name", "Changed password successfully");
				endpage();
				exit;
			}
		}
		else
		{
			$sql = "update user_table SET password = password(\"$new_password\") where record = \"$record\"";
			$result = mysql_query($sql) or die (mysql_error());
			printf ("Password successfully changed.");
			$temp_user_name = get_user_name ($record);
 		 	write_log ("1", "$user_user_name", "Changed password for user $temp_user_name successfully");
			endpage();
			exit;
		}
	}

	else if ($function=="change_password")
	{
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=users&function=submit_change_password\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\"></INPUT>
			<TABLE CELLSPACING=2 CELLPADDING=0 BORDER=0>
				<TR>
					<TD></TD>
					<TD ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\">Change Password</TD>
				</TR>";
		printf ("$display_block");
		if ($user_dnstools_administrator != "YES")
		{
			$display_block="
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Current Password</STRONG></TD>
					<TD><INPUT TYPE=PASSWORD NAME=current_password></INPUT></TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>New Password</STRONG></TD>
					<TD><INPUT TYPE=PASSWORD NAME=new_password></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Verify New Password</STRONG></TD>
					<TD><INPUT TYPE=PASSWORD NAME=verify_new_password></INPUT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Submit\"></INPUT></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

  else if ($function=="view_myself")

  {
		if ($user_logged_in=="true")
		{
			$status = "<FONT COLOR=\"$access_green\">Logged in</FONT>";
		}

		else

		{
			$status = "<FONT COLOR=\"$access_red\">Not logged in</FONT>";
		}

		right_justified_help_link ("view_user_info");
		$display_block="
       <TABLE WIDTH=400>
  		  <TR>
  		    <TD BGCOLOR=\"$cell_bg_color_1\" COLSPAN=2 ALIGN=CENTER><STRONG>Connection Information</STRONG></TD>
  		  </TR>
 	      <TR>
 	        <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>IP Address:</STRONG></TD>
 	        <TD ALIGN=LEFT>$ip_address</TD>
 	      </TR>
 	      <TR>
 	        <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Browser:</STRONG></TD>
 	        <TD ALIGN=LEFT COLSPAN=3>$agent</TD>
 	      </TR>
  		  <TR>
  		    <TD BGCOLOR=\"$cell_bg_color_1\" COLSPAN=2 ALIGN=CENTER><STRONG>User Information</STRONG></TD>
  		  </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>User #:</STRONG></TD>
          <TD ALIGN=LEFT>$user_record</TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Username:</STRONG></TD>
          <TD ALIGN=LEFT>$user_user_name</TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Full Name:</STRONG></TD>
          <TD ALIGN=LEFT>$user_full_name</TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Email:</STRONG></TD>
          <TD ALIGN=LEFT>$user_email</TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Work Phone</STRONG></TD>
          <TD ALIGN=LEFT>$user_work_phone</TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Mobile Phone</STRONG></TD>
          <TD ALIGN=LEFT>$user_mobile_phone</TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT VALIGN=TOP BGCOLOR=\"$cell_bg_color_2\"><STRONG>Address</STRONG></TD>
          <TD ALIGN=LEFT VALIGN=TOP><pre>$user_address</pre></TD>
        </TR>
        <TR>
          <TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Administrator</STRONG></TD>
          <TD ALIGN=LEFT>$user_dnstools_administrator</TD>
        </TR>
				<FORM ACTION=\"$PHP_SELF?section=users&function=change_password\" METHOD=POST>
				<TR>
					<TD></TD>
					<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\"></INPUT>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Change Password\"></INPUT></TD>
				</TR>";
		printf ("$display_block");
		list_groups_by_user ($user_record);
		$request_admin_email = mysql_query("select administrator_email from global_table");
		$result_admin_email = mysql_fetch_array($request_admin_email);
		$temp_admin_email = $result_admin_email[administrator_email];
		$display_block="
			</TABLE>
			<P>
			Need to make changes? Send an email to <EM><A HREF=\"mailto:$temp_admin_email\">$temp_admin_email</A></EM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function=="submit_delete_user")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
		if ($temp_user_record == 0)
		{
			printf ("Action cancelled.  No user selected.\n");
			endpage();
			exit;
		}
		$request_user_name = mysql_query ("select user_name from user_table where record = \"$temp_user_record\"");
		$result_user_name = mysql_fetch_array ($request_user_name);
		$user_name = $result_user_name[user_name];
	  $display_block="
			<TABLE border=0>
				<TR>
					<TD COLSPAN=2>Removing user <strong>$user_name</STRONG> from the following groups</TD>
				</TR>";
		printf ("$display_block");
		$request_groups = mysql_query("select points_to_group from group_membership_table where points_to_user = \"$temp_user_record\"");
		$odd_even = 0;
		$num_groups = 0;
		while ($result_groups = mysql_fetch_array($request_groups))
		{
			$num_groups ++;
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$temp_group_record = $result_groups[points_to_group];
			$request_group_name = mysql_query ("select group_name from group_table where record = \"$temp_group_record\"");
			$result_group_name = mysql_fetch_array ($request_group_name);
			$temp_group_name = $result_group_name[group_name];
			$display_block="
					<TR>
						<TD></TD>
						<TD BGCOLOR=$bgcolor>$temp_group_name</TD>
					</TR>";
			printf ("$display_block");
		}
		if ($num_groups == 0)
		{
			$display_block="
					<TR>
						<TD BGCOLOR=BLACK COLSPAN=2><FONT COLOR=YELLOW>This user is not a member of any groups.</FONT></TD>
					</TR>";
			printf ("$display_block");
		}
		else
		{
			$sql = "delete from group_membership_table where points_to_user = \"$temp_user_record\"";
			$result = mysql_query($sql) or die (mysql_error());
		}

    $display_block="
					<TR>
						<TD COLSPAN=2><HR></TD>
					</TR>
					<TR>
						<TD COLSPAN=2 ALIGN=LEFT>Deleting user <STRONG>$user_name</STRONG>...</TD>
					</TR>";
		printf ("$display_block");
		$sql = "delete from user_table where record = \"$temp_user_record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
					<TR>
						<TD COLSPAN=2><HR></TD>
					</TR>
					<TR>
						<TD COLSPAN=2 ALIGN=LEFT><STRONG>FINISHED!</STRONG></TD>
					</TR>
				</TABLE>\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Deleted user $user_name");
		endpage();
		exit;
	}

	else if ($function=="delete_user")
	{
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=users&function=submit_delete_user\" METHOD=POST>
			<TABLE>
				<TR>
					<TD>Delete This User</TD>
					<TD><SELECT NAME=temp_user_record>
							<OPTION SELECTED VALUE=0>-= SELECT USER =-";
		printf ("$display_block");
		$request_all_users = mysql_query ("select record, user_name, full_name from user_table order by full_name");
		while ($result_all_users = mysql_fetch_array ($request_all_users))
		{
			$temp_user_record = $result_all_users[record];
			$temp_user_name = $result_all_users[user_name];
			$temp_full_name = $result_all_users[full_name];
			$display_block="
							<OPTION VALUE=$temp_user_record>$temp_full_name ($temp_user_name)";
			printf ("$display_block");
		}
		$display_block="
							</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Delete\">
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function=="submit_modify_user")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
		$request_ident_user = mysql_query ("select record from user_table where user_name = \"$user_name\" and record != \"$record\"");
		$num_rows_returned = mysql_numrows($request_ident_user);
		if ($num_rows_returned != 0)
		{
			$display_block="
				There is already a user with user name <strong>$user_name</strong>";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "update user_table SET user_name = \"$user_name\", email = \"$email\", full_name = \"$full_name\", work_phone = \"$work_phone\", mobile_phone = \"$mobile_phone\", pager_number = \"$pager_number\", fax_number = \"$fax_number\", address = \"$address\", notes = \"$notes\", dnstools_administrator = \"$dnstools_administrator\" where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		printf ("User modification saved.");
  	write_log ("1", "$user_user_name", "Modified user $user_name (record # $record)");
		endpage();
		exit;
	}

	else if ($function=="modify_user")
	{
	  check_administrator($user_dnstools_administrator, $user_user_name);
		$request_rows = mysql_query("select * from user_table where record = \"$record\"");
		$num_rows_returned = mysql_numrows($request_rows);
		if ($num_rows_returned == 0)
		{
			$display_block="
				<STRONG>No user with that record number.</STRONG>";
			printf ("$display_block");
			endpage();
			exit;
		}
		$request_user_info = mysql_query ("select * from user_table where record = \"$record\"");
		$result_user_info = mysql_fetch_array($request_user_info);
		$temp_user_name = $result_user_info[user_name];
		$temp_user_email = $result_user_info[email];
		$temp_user_full_name = $result_user_info[full_name];
		$temp_user_work_phone = $result_user_info[work_phone];
		$temp_user_mobile_phone = $result_user_info[mobile_phone];
		$temp_user_pager_number = $result_user_info[pager_number];
		$temp_user_fax_number = $result_user_info[fax_number];
		$temp_user_address = $result_user_info[address];
		$temp_user_notes = $result_user_info[notes];
		$temp_user_dnstools_administrator = $result_user_info[dnstools_administrator];
		right_justified_help_link ("modify_user");
		$display_block="
			<form method=post action=\"$PHP_SELF?section=users&function=submit_modify_user\">
				<table cellpadding=0 cellspacing=2 border=0>
				<input type=hidden name=record value=\"$record\">
				<tr>
					<td></td>
					<td align=center bgcolor=\"$cell_bg_color_1\"><STRONG>Modify User</STRONG></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Username</STRONG></td>
					<td align=left><input type=text name=user_name size=30 value=\"$temp_user_name\"></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>EMail</STRONG></td>
					<td align=left><input type=text name=email size=30 value=\"$temp_user_email\"></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Full Name</STRONG></td>
					<td align=left><input type=text name=full_name size=30 value=\"$temp_user_full_name\"></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>Work Phone</STRONG></td>
					<td align=left><input type=text name=work_phone size=30 maxsize=30 value=\"$temp_user_work_phone\"></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Mobile Phone</STRONG></td>
					<td align=left><input type=text name=mobile_phone size=30 maxsize=30 value=\"$temp_user_mobile_phone\"</td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>Pager Number</STRONG></td>
					<td align=left><input type=text name=pager_number size=30 maxsize=30 value=\"$temp_user_pager_number\"></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Fax Number</STRONG></td>
					<td align=left><input type=text name=fax_number size=30 maxsize=30 value=\"$temp_user_fax_number\"></td>
				</tr>
				<tr>
					<td align=right valign=top bgcolor=\"$cell_bg_color_1\"><STRONG>Address</STRONG></td>
					<td align=left><textarea cols=30 rows=5 name=address>$temp_user_address</textarea></td>
				</tr>
				<tr>
					<td align=right valign=top bgcolor=\"$cell_bg_color_2\"><STRONG>Notes</STRONG></td>
					<td align=left><textarea cols=30 rows=5 name=notes>$temp_user_notes</textarea></td>
				</tr>
				<tr>
					<td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>DNSTools Administrator</STRONG></td>
					<td align=left><select name=dnstools_administrator>";
		printf ("$display_block");
		if ($temp_user_dnstools_administrator == "YES")
		{
			$display_block="
					<option selected value=\"YES\">YES
					<option value=\"NO\">NO</select></td>
			</tr>";
		}
		else
		{
			$display_block="
					<option selected value=\"NO\">NO
					<option value=\"YES\">YES</select></td>
			</tr>";
		}
		printf ("$display_block");
		$display_block="
			<tr>
				<TD></TD>
				<td><input type=submit value=\"Submit Changes\"></td>
			</tr>
			</FORM>
			<FORM ACTION=\"$PHP_SELF?section=users&function=change_password\" METHOD=POST>
			<TR>
				<TD></TD>
				<TD><INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\"></INPUT>
						<INPUT TYPE=SUBMIT VALUE=\"Change Password\"></INPUT></TD>
			</TR>
  		</table></form>\n";
		printf ("$display_block");
		endpage();
		exit;
	}

  else if ($function=="add_user")

	{
		right_justified_help_link ("add_user");
	  $display_block="
		  <form method=post action=\"$PHP_SELF?section=users&function=submit_add_user\">
 			  <table cellpadding=0 cellspacing=2 border=0>
 				<tr>
					<td></td>
 				  <td align=center bgcolor=\"$cell_bg_color_1\"><STRONG>Add New User</STRONG></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Username</STRONG></td>
 					<td align=left><input type=text name=user_name size=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>EMail</STRONG></td>
 					<td align=left><input type=text name=email size=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Full Name</STRONG></td>
 					<td align=left><input type=text name=full_name size=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>Work Phone</STRONG></td>
 					<td align=left><input type=text name=work_phone size=30 maxsize=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Mobile Phone</STRONG></td>
 					<td align=left><input type=text name=mobile_phone size=30 maxsize=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>Pager Number</STRONG></td>
 					<td align=left><input type=text name=pager_number size=30 maxsize=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Fax Number</STRONG></td>
 					<td align=left><input type=text name=fax_number size=30 maxsize=30></td>
 				</tr>
 				<tr>
 				  <td align=right valign=top bgcolor=\"$cell_bg_color_1\"><STRONG>Address</STRONG></td>
 					<td align=left><textarea cols=30 rows=5 name=address></textarea></td>
 				</tr>
 				<tr>
 				  <td align=right valign=top bgcolor=\"$cell_bg_color_2\"><STRONG>Notes</STRONG></td>
 					<td align=left><textarea cols=30 rows=5 name=notes></textarea></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>Password</STRONG></td>
 					<td align=left><input type=password name=password size=30></td>
 				</tr>
 				<tr>
 				  <td align=right bgcolor=\"$cell_bg_color_2\"><STRONG>Verify Password</STRONG></td>
 					<td align=left><input type=password name=verified_password size=30></td>
 				</tr>
 				<tr>
					<td align=right bgcolor=\"$cell_bg_color_1\"><STRONG>DNSTools Administrator</STRONG></td>
					<td align=left><select name=dnstools_administrator>
						<option selected value=\"NO\">NO
						<option value=\"YES\">YES</select></td>
				</tr>
				<tr>
					<td align=center colspan=2><input type=submit value=\"Submit\"></td>
				</tr>
 			</table></form>\n";
		printf ("$display_block");
	}

  else if ($function=="submit_add_user")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
	  $display_block="
		  Please wait.... adding new user now.<p>";
		printf ("$display_block");
		$request_rows = mysql_query("select * from user_table where user_name = \"$user_name\"");
    $num_rows_returned = mysql_numrows($request_rows);
		if ($num_rows_returned != 0)
		{
			$display_block="
				<FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate user name.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "insert into user_table (user_name, password, full_name, work_phone, mobile_phone, pager_number, fax_number, email, address, notes, dnstools_administrator) values (\"$user_name\", password(\"$password\"), \"$full_name\", \"$work_phone\", \"$mobile_phone\", \"$pager_number\", \"$fax_number\", \"$email\", \"$address\", \"$notes\", \"$dnstools_administrator\")";

		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added new user <strong>$user_name</strong> successfully!\n
			<P>
			<A HREF=\"$PHP_SELF?section=security&function=add_user_to_group_1\">You should now add this user to a group</a>";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added new user $full_name ($user_name)");
		endpage();
		exit;
	}

  else

	{
		$display_block="
		  <P>
			<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>
				<TR>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				  <TD><A HREF=\"$PHP_SELF?section=users&function=add_user\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=users&function=add_user\">Add User</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=users&function=delete_user\"><IMG SRC=\"/dnstools/images/icons/small-button-delete.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=users&function=delete_user\">Delete User</A></TD>
				</TR>
			  <TR>
				  <TD><A HREF=\"$PHP_SELF?section=users&function=list_users\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=users&function=list_users\">List Users</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=users&function=view_myself&record=$user_record\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=users&function=view_myself&record=$user_record\">View Your Information</A></TD>
				</TR>
			</TABLE>";
		}
		else
		{
			$display_block="
					  <TD><A HREF=\"$PHP_SELF?section=users&function=view_myself&record=$user_record\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
						<TD><A HREF=\"$PHP_SELF?section=users&function=view_myself&record=$user_record\">View Your Information</A></TD>
					</TR>
				</TABLE>";
		}
		printf ("$display_block");
	  endpage();
	  exit;
  }
}

# SECTION DOMAIN

else if ($section=="domain")
{
  beginpage("domain");
	check_login ($user_user_name, $user_password);

	if ($function=="force_update")
	{
		write_log ("1", "$user_user_name", "Forced update of BIND configuration files");
		$command = "/usr/local/bin/dnstools -push -verbose 2>&1";
		$display_block="
			<P>The following is the raw output of the following command:</P>
			<P>\"$command\"</P>
			<pre>";
		printf ("$display_block");
		exec($command, $result, $rval);
		if ($rval != "0")
		{
			$display_block="
				<TABLE BORDER=0>
					<TR BGCOLOR=\"RED\">
						<TD><FONT COLOR=\"YELLOW\">FAILED.  RETURN CODE: $rval</FONT></TD>
					</TR>
				</TABLE>";
			printf ("$display_block");
		}

		else
		{
			$display_block="
				<TABLE BORDER=0>
					<TR BGCOLOR=\"GREEN\">
						<TD><FONT COLOR=\"WHITE\">-=> SUCCESS! <=-</FONT></TD>
					</TR>
				</TABLE>";
			printf ("$display_block");
		}

		for ($i = 0; $i < sizeof($result); $i++)
		{
			echo "$result[$i]<br>";
		}
		endpage();
		exit;
	}

	if ($function=="list_domains")
	{
		write_log ("2", "$user_user_name", "Listed all domains");
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can list the hosts that reside in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER bgcolor=\"$cell_bg_color_1\">Listing All Domains</TD>
			</TR>
			<TR>
				<TD><STRONG>Domain Name</STRONG></TD>
				<TD WIDTH=45 ALIGN=CENTER><STRONG>View</STRONG></TD>
				<TD WIDTH=45 ALIGN=CENTER><STRONG>Modify</STRONG></TD>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				<TD WIDTH=45 ALIGN=CENTER><STRONG>Delete</STRONG></TD>";
			printf ("$display_block");
		}
		$display_block="
				<TD><STRONG>Group</STRONG></TD>
			</TR>";
		printf ("$display_block\n");
		$odd_even = 0;
		foreach ($domain_list as $current_domain)
		{
		  if ($odd_even == 0)
			{
				$bgcolor = $cell_bg_color_1;
				$odd_even = 1;
			}
			else
			{
				$bgcolor = $cell_bg_color_2;
				$odd_even = 0;
			}
			$domain_name = get_domain_name($current_domain);
			$request_group_number = mysql_query("select points_to_group from domain_table where record = \"$current_domain\"");
			$result_group_number = mysql_fetch_array($request_group_number);
			$group_number = $result_group_number[points_to_group];
			if ($group_number == 0)
			{
				$group_name = "- NONE -";
			}
			else
			{
				$request_group_name = mysql_query ("select group_name from group_table where record = \"$group_number\"");
				$result_group_name = mysql_fetch_array ($request_group_name);
				$group_name = $result_group_name[group_name];
			}

			$display_block="
				<TR BGCOLOR=\"$bgcolor\">
					<TD>$domain_name</TD>
					<TD ALIGN=CENTER><A HREF=\"$PHP_SELF?section=domain&function=view_domain&record=$current_domain\"><img src=\"/dnstools/images/icons/ultra-small-view.gif\" alt=\"View Domain\" border=0></img></A></TD>
					<TD ALIGN=CENTER><A HREF=\"$PHP_SELF?section=domain&function=modify_domain&record=$current_domain\"><img src=\"/dnstools/images/icons/ultra-small-modify.gif\" alt=\"Modify Domain\" border=0></img></A></TD>";
			printf ("$display_block");
			if ($user_dnstools_administrator == "YES")
			{
				$display_block="
					<TD ALIGN=CENTER><A HREF=\"$PHP_SELF?section=domain&function=delete_domain&record=$current_domain\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete Domain\" border=0></img></A></TD>";
				printf ("$display_block");
			}
			$display_block="
					<TD>$group_name</TD>
				</TR>";
			printf ("$display_block\n");
		}
		$display_block="
			</TABLE>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function=="submit_delete_domain")
	{
		check_lock();
		check_administrator($user_dnstools_administrator, $user_user_name);
		$request_domain = mysql_query ("select domain_name from domain_table where record = \"$record\"");
		$result_domain = mysql_fetch_array($request_domain);
		$temp_domain_name = $result_domain[domain_name];
		$display_block="
			Deleting all assocations to domain $temp_domain_name now, please wait...<P>
			Deleting all Name Server records ... ";
		printf ("$display_block");
		$ns_records = delete_domain_all_ns ($record, $user_user_name);
		printf ("$ns_records NS records deleted.<BR>Deleting all Mail Exchange records...");
		$mx_records = delete_domain_all_mx ($record, $user_user_name);
		printf ("$mx_records MX records deleted.<BR>Deleting all IP ranges...");
		$ip_ranges = delete_domain_all_ip_range ($record, $user_user_name);
		printf ("$ip_ranges IP ranges have been released.<BR>Deleting all hosts (hosts, CNAMEs, RR and MX)...");
		$hosts = delete_domain_all_host ($record, $user_user_name);
		printf ("$hosts hosts deleted.<BR>Deleting domain $temp_domain_name...");
		$sql = "delete from domain_table where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			OK!<P>Finished!";
		printf ("$display_block");
		update_global();
		endpage();
		exit;
	}

	else if ($function=="delete_domain")
	{
		$request_domain = mysql_query ("select domain_name from domain_table where record = \"$record\"");
		$result_domain = mysql_fetch_array($request_domain);
		$temp_domain_name = $result_domain[domain_name];
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_delete_domain\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
			NOTE: If this domain is authoritative for a certain IP range, that IP range will be removed
			from the DNSTools database.  However, if any of these IP addresses are in other domains for
			forward-only name resolution (name to IP) they will remain intact, but they will not
			resolve backwards (IP to name).<P><BR>
		  Are you <STRONG>SURE</STRONG> you wish to delete $temp_domain_name and <strong>ALL</strong>
			of the hosts, name servers, mail exchange servers, round robin entries, aliases and
			IP addresses associated with this domain?<P><BR>
			<INPUT TYPE=SUBMIT VALUE=\"YES Delete Domain $temp_domain_name NOW!\"></FORM>";
		printf ("$display_block");
	}

	else if ($function=="submit_modify_domain")
	{
		check_lock();
		check_valid_domain ($domain_name);

		if ($user_dnstools_administrator != "YES")
		{
			check_domain_privilege ($record, $user_record, $user_dnstools_administrator);
		}
		$request_rows = mysql_query ("select record from domain_table where domain_name = \"$domain_name\" and record != \"$record\"");
    $num_rows_returned = mysql_numrows($request_rows);
		if ($num_rows_returned != 0)
		{
			$display_block="
				There is already a domain with the name <strong>$domain_name</strong>";
			printf ("$display_block");
			endpage();
			exit;
		}

		if ($ns_type != $old_ns_type)
		{
			$display_block="
				To change this domain from one type to another, you must delete the domain and add it again as the new type.<P>
				If you were to change this domain to be a primary from a secondary, it would require you to enter all of
				the host information, MX records, aliases, etc.<P>
				If you were to change this domain to be a secondary from a primary, all of it's host information would be deleted
				to allow for updates from the primary.<P>
				The best way to perform this is to delete the domain and add it again as the new type.<P>
				This action has been cancelled.";
			printf ("$display_block");
			endpage();
			exit;
		}

		if ($user_dnstools_administrator == "YES")
		{
			if ($main_dnstools_domain == "YES")
			{
				$sql = "update domain_table set main_dnstools_domain = \"NO\" where main_dnstools_domain = \"YES\"";
				$result = mysql_query($sql) or die (mysql_error());
			}
			$sql = "update domain_table SET domain_name = \"$domain_name\", ns_type = \"$ns_type\", zone_transfer_ip = \"$zone_transfer_ip\", email = \"$email\", serial = \"$serial\", default_refresh = \"$default_refresh\", default_retry = \"$default_retry\", default_expire = \"$default_expire\", default_ttl = \"$default_ttl\", points_to_group = \"$points_to_group\", use_notify = \"$use_notify\", is_active = \"$is_active\", main_dnstools_domain = \"$main_dnstools_domain\" where record = \"$record\"";
#			printf ("SQL is:<P><pre>$sql</pre>");
			$result = mysql_query($sql) or die (mysql_error());
			printf ("Domain <strong>$domain_name</strong> updated successfully!\n");
		}

		else

		{
			$sql = "update domain_table SET domain_name = \"$domain_name\", email = \"$email\", serial = \"$serial\", default_refresh = \"$default_refresh\", default_retry = \"$default_retry\", default_expire = \"$default_expire\", default_ttl = \"$default_ttl\", use_notify = \"$use_notify\" where record = \"$record\"";
#			printf ("SQL is:<P><pre>$sql</pre>");
			$result = mysql_query($sql) or die (mysql_error());
			printf ("Domain <strong>$domain_name</strong> updated successfully!\n");
		}
		update_domain_serial ($record);
		update_global;
		endpage();
		exit;
	}

	else if ($function=="modify_domain")
	{
		if ($user_dnstools_administrator != "YES")
		{
			check_domain_privilege ($record, $user_record, $user_dnstools_administrator);
		}
		$request_domain = mysql_query ("select * from domain_table where record=\"$record\"");
		$result_domain = mysql_fetch_array($request_domain);
		$domain_name = $result_domain[domain_name];
		$ns_type = $result_domain[ns_type];
		$zone_transfer_ip = $result_domain[zone_transfer_ip];
		$email = $result_domain[email];
		$serial = $result_domain[serial];
		$default_refresh = $result_domain[default_refresh];
		$default_retry = $result_domain[default_retry];
		$default_expire = $result_domain[default_expire];
		$default_ttl = $result_domain[default_ttl];
		$points_to_group = $result_domain[points_to_group];
		$use_notify = $result_domain[use_notify];
		$is_active = $result_domain[is_active];
		$main_dnstools_domain = $result_domain[main_dnstools_domain];
		
		right_justified_help_link ("modify_domain");

		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_modify_domain\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\"></INPUT>
			<INPUT TYPE=HIDDEN NAME=old_ns_type VALUE=\"$ns_type\"></INPUT>
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Domain Name</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=domain_name VALUE=\"$domain_name\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>EMail Address</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=email VALUE=\"$email\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Serial</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=serial VALUE=\"$serial\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Default Refresh</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=default_refresh VALUE=\"$default_refresh\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Default Retry</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=default_retry VALUE=\"$default_retry\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Default Expire</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=default_expire VALUE=\"$default_expire\"></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Default Time To Live</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=default_ttl VALUE=\"$default_ttl\"></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Use Notify</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=use_notify>";
		printf ("$display_block");
		if ($use_notify=="YES")
		{
			$display_block="
				<OPTION SELECTED VALUE=\"YES\">YES
				<OPTION VALUE=\"NO\">NO";
			printf ("$display_block");
		}
		else
		{
			$display_block="
				<OPTION SELECTED VALUE=\"NO\">NO
				<OPTION VALUE=\"YES\">YES";
			printf ("$display_block");
		}
		$display_block="
						</SELECT></TD>
				</TR>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=2 ALIGN=CENTER><STRONG>DNSTools Administrator Settings</STRONG></TD>
				</TR>
				<TR>
					<TD COLSPAN=2><HR></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Name Server Type</STRONG></TD>
					<TD><SELECT NAME=ns_type>";
			printf ("$display_block");
			if ($ns_type=="PRIMARY")
			{
				$display_block="
					<OPTION SELECTED VALUE=\"PRIMARY\">PRIMARY
					<OPTION VALUE=\"SECONDARY\">SECONDARY";
					printf ("$display_block");
			}
			else
			{
				$display_block="
					<OPTION VALUE=\"PRIMARY\">PRIMARY
					<OPTION SELECTED VALUE=\"SECONDARY\">SECONDARY";
					printf ("$display_block");
			}
			$display_block="
					</SELECT></TD>
				</TR>";
			printf ("$display_block");
			if ($ns_type == "SECONDARY")
			{
				$display_block="
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>IP Address of Primary<br><em>(only if secondary)</em></br></STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=zone_transfer_ip VALUE=\"$zone_transfer_ip\"></INPUT></TD>
				</TR>";
				printf ("$display_block");
			}
			$display_block="
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Owned by Group</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=points_to_group>";
			printf ("$display_block");
			if ($points_to_group == 0)
			{
				$display_block="
					<OPTION SELECTED VALUE=\"0\">-= SELECT GROUP =-";
				printf ("$display_block");
			}
			$request_group_names = mysql_query ("select record, group_name from group_table order by group_name");
			while ($result_group_names = mysql_fetch_array($request_group_names))
			{
				$group_number = $result_group_names[record];
				$group_name = $result_group_names[group_name];
				if ($group_number == $points_to_group)
				{
					$display_block="
						<OPTION SELECTED VALUE=\"$group_number\">$group_name";
					printf ("$display_block");
				}
				else
				{
					$display_block="
						<OPTION VALUE=\"$group_number\">$group_name";
					printf ("$display_block");
				}
			}
			$display_block="
							</SELECT></TD>
					</TR>
					<TR>
						<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Active Domain</STRONG></TD>
						<TD ALIGN=LEFT><SELECT NAME=is_active>";
			printf ("$display_block");
			if ($is_active=="YES")
			{
				$display_block="
					<OPTION SELECTED VALUE=\"YES\">YES
					<OPTION VALUE=\"NO\">NO";
				printf ("$display_block");
			}
			else
			{
				$display_block="
					<OPTION SELECTED VALUE=\"NO\">NO
					<OPTION VALUE=\"YES\">YES";
				printf ("$display_block");
			}
			$display_block="
						</SELECT></TD>
					</TR>
					<TR>
						<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Main DNSTools Domain</STRONG></TD>
						<TD ALIGN=LEFT><SELECT NAME=main_dnstools_domain>";
			printf ("$display_block");
			if ($main_dnstools_domain=="YES")
			{
				$display_block="
					<OPTION SELECTED VALUE=\"YES\">YES
					<OPTION VALUE=\"NO\">NO";
				printf ("$display_block");
			}
			else
			{
				$display_block="
					<OPTION SELECTED VALUE=\"NO\">NO
					<OPTION VALUE=\"YES\">YES";
				printf ("$display_block");
			}
			$display_block="
						</SELECT></TD>
					</TR>
					<TR>
						<TD></TD>
						<TD><INPUT TYPE=SUBMIT VALUE=\"Submit Changes\"></TD>
					</TR>
			</TABLE>
			</FORM>\n";
			printf ("$display_block");
		}
	}

	else if ($function=="view_domain")
	{
		check_domain_privilege ($record, $user_record, $user_dnstools_administrator);
		$request_domain = mysql_query ("select * from domain_table where record=\"$record\"");
		$result_domain = mysql_fetch_array($request_domain);
		$domain_name = $result_domain[domain_name];
		$ns_type = $result_domain[ns_type];
		$zone_transfer_ip = $result_domain[zone_transfer_ip];
		$email = $result_domain[email];
		$serial = $result_domain[serial];
		$default_refresh = $result_domain[default_refresh];
		$default_retry = $result_domain[default_retry];
		$default_expire = $result_domain[default_expire];
		$default_ttl = $result_domain[default_ttl];
		$points_to_group = $result_domain[points_to_group];
		$use_notify = $result_domain[use_notify];
		$is_active = $result_domain[is_active];
		$main_dnstools_domain = $result_domain[main_dnstools_domain];

		$request_group_name = mysql_query ("select group_name from group_table where record = \"$points_to_group\"");
		$result_group_name = mysql_fetch_array($request_group_name);
		$group_name = $result_group_name[group_name];

	  $display_block="
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Domain Name</STRONG></TD>
					<TD ALIGN=LEFT>$domain_name</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Name Server Type</STRONG></TD>
					<TD ALIGN=LEFT>$ns_type</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>IP of Primary (if secondary)</STRONG></TD>
					<TD ALIGN=LEFT>$zone_transfer_ip</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>EMail Address</STRONG></TD>
					<TD ALIGN=LEFT>$email</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Serial</STRONG></TD>
					<TD ALIGN=LEFT>$serial</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Default Refresh</STRONG></TD>
					<TD ALIGN=LEFT>$default_refresh</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Default Retry</STRONG></TD>
					<TD ALIGN=LEFT>$default_retry</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Default Expire</STRONG></TD>
					<TD ALIGN=LEFT>$default_expire</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Default Time To Live</STRONG></TD>
					<TD ALIGN=LEFT>$default_ttl</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Owned by Group</STRONG></TD>
					<TD ALIGN=LEFT>$group_name</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Use Notify</STRONG></TD>
					<TD ALIGN=LEFT>$use_notify</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Active Domain</STRONG></TD>
					<TD ALIGN=LEFT>$is_active</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Main DNSTools Domain</STRONG></TD>
					<TD ALIGN=LEFT>$main_dnstools_domain</TD>
				</TR>
			</TABLE>
			</FORM>\n";
		printf ("$display_block");
		endpage();
  	write_log ("2", "$user_user_name", "Viewed domain $domain_name");
	}

	else if ($function=="add_domain")
	{
		check_administrator($user_dnstools_administrator, $user_user_name);
		right_justified_help_link ("add_domain");
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_add_domain\" METHOD=POST>
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD></TD>
 				  <TD ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Add New Domain</STRONG></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\" ALIGN=RIGHT VALIGN=CENTER><STRONG>Domain Name</STRONG></a></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=200 SIZE=30 NAME=domain_name></INPUT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\" ALIGN=RIGHT VALIGN=CENTER><STRONG>Name Server Type</STRONG></a></TD>
					<TD ALIGN=LEFT><SELECT NAME=ns_type><OPTION SELECTED VALUE=\"PRIMARY\">PRIMARY
							<OPTION VALUE=\"SECONDARY\">SECONDARY</SELECT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>IP of Primary (if secondary)</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=15 SIZE=15 NAME=zone_transfer_ip></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>EMail Address</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=50 SIZE=20 NAME=email></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Starting Serial</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=11 SIZE=11 NAME=serial></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Default Refresh</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=11 SIZE=11 NAME=default_refresh></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Default Retry</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=11 SIZE=11 NAME=default_retry></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Default Expire</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=11 SIZE=11 NAME=default_expire></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Default Time To Live</STRONG></TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT MAXLENGTH=11 SIZE=11 NAME=default_ttl></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Owned by Group</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=points_to_group><OPTION SELECTED VALUE=\"0\">-= SELECT GROUP =-";
		printf ("$display_block");

		$request_groups = mysql_query ("select record, group_name from group_table order by group_name");
		while ($result_groups = mysql_fetch_array($request_groups))
		{
			$temp_group_record_number = $result_groups[record];
			$temp_group_group_name = $result_groups[group_name];
			printf ("<OPTION VALUE=\"$temp_group_record_number\">$temp_group_group_name\n");
		}
		$display_block="
													</SELECT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Use Notify</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=use_notify><OPTION SELECTED VALUE=\"YES\">YES
							<OPTION VALUE=\"NO\">NO</SELECT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Active Domain</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=is_active><OPTION SELECTED VALUE=\"YES\">YES
							<OPTION VALUE=\"NO\">NO</SELECT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Main DNSTools Domain</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=main_dnstools_domain><OPTION SELECTED VALUE=\"NO\">NO
							<OPTION VALUE=\"YES\">YES</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
				  <TD COLSPAN=2><INPUT TYPE=SUBMIT VALUE=\"Submit\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
	}

  else if ($function=="submit_add_domain")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
	  $display_block="
		  Please wait.... adding new domain now.<p>";
		printf ("$display_block");
		if (($ns_type == "PRIMARY") && ($zone_transfer_ip))
		{
			$display_block="
				This domain cannot be authoritative (PRIMARY) and still have an IP address pointing to a primary.<P>
				You may only enter an IP address of the primary if this domain is a secondary domain.<P>";
			printf ("$display_block");
			endpage();
			exit;
		}
		$request_rows = mysql_query("select * from domain_table where domain_name = \"$domain_name\"");
    $num_rows_returned = mysql_numrows($request_rows);
		if ($num_rows_returned != 0)
		{
			$display_block="
				<FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate domain name.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "insert into domain_table (domain_name, needs_update, ns_type, zone_transfer_ip, email, serial, default_refresh, default_retry, default_expire, default_ttl, points_to_group, use_notify, is_active, main_dnstools_domain) values (\"$domain_name\", \"YES\", \"$ns_type\", \"$zone_transfer_ip\", \"$email\", \"$serial\", \"$default_refresh\", \"$default_retry\", \"$default_expire\", \"$default_ttl\", \"$points_to_group\", \"$use_notify\", \"$is_active\", \"$main_dnstools_domain\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added new domain <strong>$domain_name</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added new domain $domain_name");
		update_global();
		endpage();
		exit;
	}

	else if ($function=="submit_delete_ip_range")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
		if ($type_of_delete == "")
		{
			$display_block="
				You did not choose an option on how to handle hosts with IP addresses in this range.  Please go back and make a selection.";
			printf ("$display_block");
			endpage();
			exit;
		}
		if ($type_of_delete == "delete_hosts")
		{
			$display_block="
				Now deleting all hosts from this IP range ... please wait <P>";
			printf ("$display_block");
			$request_ip_range = mysql_query ("select * from ip_range_table where record = \"$record\"");
			$result_ip_range = mysql_fetch_array ($request_ip_range);
			$octet_1 = $result_ip_range[octet_1];
			$octet_2 = $result_ip_range[octet_2];
			$octet_3 = $result_ip_range[octet_3];
			$octet_4 = $result_ip_range[octet_4];
			$bitmask = $result_ip_range[bitmask];
			$points_to_domain = $result_ip_range[points_to_domain];
			$max_ip = get_max_ip ($octet_4, $bitmask);
			$sql = "select record from host_table where registered_ip = \"YES\" and octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and octet_4 >= \"$octet_4\" and octet_4 <= \"$max_ip\"";
			$request_hosts = mysql_query ($sql);
			$num_rows = mysql_num_rows ($request_hosts);
			if ($num_rows == 0)
			{
				$display_block="
					There were no hosts with registered IP addresses in this range.";
				printf ("$display_block");
			}
			else
			{
				$request_host = mysql_query ("select record, host_name, octet_1, octet_2, octet_3, octet_4 from host_table where registered_ip = \"YES\" and octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and octet_4 >= \"$octet_4\" and octet_4 <= \"$max_ip\"");
				while ($result_host = mysql_fetch_array ($request_host))
				{
					$host_name = $result_host[host_name];
					$host_record = $result_host[record];
					$db_octet_1 = $result_host[octet_1];
					$db_octet_2 = $result_host[octet_2];
					$db_octet_3 = $result_host[octet_3];
					$db_octet_4 = $result_host[octet_4];
					$display_block="
						Deleting host $host_name ($db_octet_1.$db_octet_2.$db_octet_3.$db_octet_4) from database...<BR>";
					printf ("$display_block");
					force_delete_host ($host_record, $user_user_name);
				}
			}
			$request_hosts = mysql_query ($sql);
			$num_rows = mysql_num_rows ($request_hosts);
			$display_block="
				<P>Finished deleting hosts.</P>";
			printf ("$display_block");
		}
		if ($type_of_delete == "change_hosts")
		{
			$display_block="
				Now changing all hosts to be unregistered (non-authoritative) ... please wait<P>";
			printf ("$display_block");
			$request_ip_range = mysql_query ("select * from ip_range_table where record = \"$record\"");
			$result_ip_range = mysql_fetch_array ($request_ip_range);
			$octet_1 = $result_ip_range[octet_1];
			$octet_2 = $result_ip_range[octet_2];
			$octet_3 = $result_ip_range[octet_3];
			$octet_4 = $result_ip_range[octet_4];
			$bitmask = $result_ip_range[bitmask];
			$points_to_domain = $result_ip_range[points_to_domain];
			$max_ip = get_max_ip ($octet_4, $bitmask);
			$sql = "select record from host_table where registered_ip = \"YES\" and octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and octet_4 >= \"$octet_4\" and octet_4 <= \"$max_ip\"";
			$request_hosts = mysql_query ($sql);
			$num_rows = mysql_num_rows ($request_hosts);
			if ($num_rows == 0)
			{
				$display_block="
					There were no hosts with registered IP addresses in this range.";
				printf ("$display_block");
			}
			else
			{
				$request_host = mysql_query ("select record, host_name, octet_1, octet_2, octet_3, octet_4 from host_table where registered_ip = \"YES\" and octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and octet_4 >= \"$octet_4\" and octet_4 <= \"$max_ip\"");
				while ($result_host = mysql_fetch_array ($request_host))
				{
					$host_name = $result_host[host_name];
					$host_record = $result_host[record];
					$db_octet_1 = $result_host[octet_1];
					$db_octet_2 = $result_host[octet_2];
					$db_octet_3 = $result_host[octet_3];
					$db_octet_4 = $result_host[octet_4];
					$display_block="
						Changing host $host_name ($db_octet_1.$db_octet_2.$db_octet_3.$db_octet_4) ...<BR>";
					printf ("$display_block");
					$sql = "update host_table SET registered_ip = \"NO\" where record = \"$host_record\"";
					$result = mysql_query($sql) or die (mysql_error());
				}
			}
			$display_block="
				<P>Finished changing hosts.</P>";
			printf ("$display_block");
		}

		$domain_name = get_domain_name($domain_record);
		$sql = "delete from ip_range_table where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Deleted IP range successfully.\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Deleted IP range $octet_1.$octet_2.$octet_3.$octet_4/$bitmask from domain $domain_name");
		update_domain_serial ($domain_record);
		update_global();
		endpage();
		exit;
	}

	else if ($function=="delete_ip_range")
	{
		$request_ip_range = mysql_query ("select * from ip_range_table where record = \"$record\"");
		$result_ip_range = mysql_fetch_array ($request_ip_range);
		$ip_range_octet_1 = $result_ip_range[octet_1];
		$ip_range_octet_2 = $result_ip_range[octet_2];
		$ip_range_octet_3 = $result_ip_range[octet_3];
		$ip_range_octet_4 = $result_ip_range[octet_4];
		$ip_range_bitmask = $result_ip_range[bitmask];
		$points_to_domain = $result_ip_range[points_to_domain];
		$ip_range_domain_name = get_domain_name($points_to_domain);
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_delete_ip_range\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
			<INPUT TYPE=HIDDEN NAME=domain_record VALUE=\"$points_to_domain\">
			By deleting a registered IP range that is assigned to a domain, you have two choices
			<TABLE BORDER = 0 CELLSPACING = 2 CELLPADDING = 2>
				<TR BGCOLOR=\"$cell_bg_color_1\">
					<TD ALIGN=LEFT>Change all hosts to non-registered IP Addresses</TD>
					<TD><INPUT TYPE=RADIO NAME=type_of_delete VALUE=\"change_hosts\"></INPUT>
				</TR>
				<TR BGCOLOR=\"$cell_bg_color_2\">
					<TD ALIGN=LEFT>Delete all hosts in this IP Range</TD>
					<TD><INPUT TYPE=RADIO NAME=type_of_delete VALUE=\"delete_hosts\"></INPUT>
				</TR>
			</TABLE>
			<TABLE BORDER=0 CELLSPACING = 2 CELLPADDING = 2>
				<TR>
					<TD COLSPAN=2>Are you <STRONG>SURE</STRONG> you wish to delete the following information?</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Domain</STRONG></TD>
					<TD>$ip_range_domain_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>Beginning IP</STRONG></TD>
					<TD>$ip_range_octet_1.$ip_range_octet_2.$ip_range_octet_3.$ip_range_octet_4</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Bitmask</STRONG></TD>
					<TD>$ip_range_bitmask</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this IP Range!\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}
		
	else if ($function=="list_ip_range")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			if ($user_dnstools_administrator == "YES")
			{
				$display_block="
					There are no domains yet.  You must add a domain before you can list the IP ranges that reside in it.";
			}
			else
			{
				$display_block="
					You do not have authority in any domains so you do not have access to see any IP ranges.";
			}
				printf ("$display_block");
			endpage();
			exit;
		}
		$has_ranges = 0;
		foreach ($domain_list as $current_domain)
		{
			$request_range = mysql_query ("select * from ip_range_table where points_to_domain = \"$current_domain\"");
			$num_rows = mysql_num_rows ($request_range);
			if ($num_rows != 0)
			{
				$has_range = 1;
			}
		}
		if ($has_range == 0)
		{
			$display_block="
				There are no registered IP ranges in any domains that you have authority in.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Listing all IP Ranges</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=CENTER><STRONG>Domain</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Starting IP</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Ending IP</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Bitmask</STRONG></TD>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>";
			printf ("$display_block");
		}
		$display_block="
			</TR>";
		printf ("$display_block\n");
		if (($user_dnstools_administrator == "NO") && (! $group_list))
		{
			$display_block="
					<TR>
						<TD BGCOLOR=BLACK COLSPAN=4><FONT COLOR=YELLOW>You must belong to a group first.</FONT></TD>
					</TR>
				</TABLE>";
			printf ("$display_block");
			endpage();
			exit;
		}
		else
		{
			$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		}
		$odd_even = 0;
		foreach ($domain_list as $current_domain)
		{
			$request_ip_range_record = mysql_query ("select record, points_to_domain, octet_1, octet_2, octet_3, octet_4, bitmask from ip_range_table where points_to_domain = \"$current_domain\" order by octet_1, octet_2, octet_3, octet_4");
			while ($result_ip_range_record = mysql_fetch_array($request_ip_range_record))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 0;
				}
				$ip_range_record = $result_ip_range_record[record];
				$ip_range_points_to_domain = $result_ip_range_record[points_to_domain];
				$ip_range_octet_1 = $result_ip_range_record[octet_1];
				$ip_range_octet_2 = $result_ip_range_record[octet_2];
				$ip_range_octet_3 = $result_ip_range_record[octet_3];
				$ip_range_octet_4 = $result_ip_range_record[octet_4];
				$ip_range_bitmask = $result_ip_range_record[bitmask];
				$max_ip = get_max_ip ($ip_range_octet_4, $ip_range_bitmask);
				$max_ip --;
				$ip_range_domain_name = get_domain_name($ip_range_points_to_domain);
				if ($ip_range_record)
				{
					$display_block="
						<TR>
							<TD BGCOLOR=\"$bgcolor\">$ip_range_domain_name</TD>
							<TD BGCOLOR=\"$bgcolor\">$ip_range_octet_1.$ip_range_octet_2.$ip_range_octet_3.$ip_range_octet_4</TD>
							<TD BGCOLOR=\"$bgcolor\">$ip_range_octet_1.$ip_range_octet_2.$ip_range_octet_3.$max_ip</TD>
							<TD BGCOLOR=\"$bgcolor\">$ip_range_bitmask</TD>";
					printf ("$display_block");
					if ($user_dnstools_administrator == "YES")
					{
						$display_block="
							<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=domain&function=delete_ip_range&record=$ip_range_record\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete IP Range\" border=0></img></A></TD>";
						printf ("$display_block");
					}
					$display_block="
						</TR>";
					printf ("$display_block");
				}
			}
		}
		$display_block="
			</TABLE>
			</FORM>";
		printf ("$display_block");
		write_log ("2", "$user_user_name", "Listed all IP ranges");
		endpage();
		exit;
	}
		
	else if ($function=="submit_add_ip_range")
	{
		check_lock();
	  check_administrator($user_dnstools_administrator, $user_user_name);
		check_valid_ip ($octet_1, $octet_2, $octet_3, $octet_4);
		$max_ip = get_max_ip ($octet_4, $bitmask);
		$max_ip --;
		if (($max_ip + $max_hosts) >= 256)
		{
			$display_block="
				<STRONG><FONT COLOR=RED>NOTICE!</STRONG></FONT> Your starting IP address of $octet_1.$octet_2.$octet_3.$octet_4 is too high to have a bitmask of $bitmask.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$select_duplicate_ip_range = mysql_query ("select record from ip_range_table where octet_1 = \"$octet_1\" and octet_2 = \"$octet_2\" and octet_3 = \"$octet_3\" and octet_4 = \"$octet_4\"");
		$num_rows = mysql_num_rows ($select_duplicate_ip_range);
		if ($num_rows != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>This IP range is already in use.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}

		$sql = "insert into ip_range_table (points_to_domain, needs_update, octet_1, octet_2, octet_3, octet_4, bitmask) values (\"$points_to_domain\", \"YES\", \"$octet_1\", \"$octet_2\", \"$octet_3\", \"$octet_4\", \"$bitmask\")";
		$result = mysql_query($sql) or die (mysql_error());
		$domain_name = get_domain_name ($points_to_domain);
		$display_block="
			Added new IP Range <strong>$octet_1.$octet_2.$octet_3.$octet_4/$bitmask</strong> successfully!\n";
		printf ("$display_block");
  	write_log ("1", "$user_user_name", "Added new IP Range $octet_1.$octet_2.$octet_3.$octet_4/$bitmask to domain $domain_name");
		update_domain_serial ($points_to_domain);
		update_global();
		endpage();
		exit;
	}

	else if ($function=="add_ip_range")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
#		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can add an IP range to it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		right_justified_help_link ("add_ip_range");
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_add_ip_range\" METHOD=POST>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>
				<TR>
					<TD></TD>
					<TD BGCOLOR=\"$cell_bg_color_1\" ALIGN=CENTER>Add New IP Range to Domain</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Domain</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=points_to_domain>";
		printf ("$display_block");

		$odd_even = 0;
		foreach ($domain_list as $current_domain)
		{
			$domain_name = get_domain_name($current_domain);
			$display_block="
						<OPTION VALUE=\"$current_domain\">$domain_name";
			printf ("$display_block");
		}
		$display_block="
						</SELECT>
					</TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_1\"><STRONG>Starting IP</STRONG></TD>
					<TD ALIGN=LEFT>
						<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_1></INPUT> . 
						<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_2></INPUT> . 
						<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_3></INPUT> . 
						<INPUT TYPE=TEXT SIZE=3 MAXLENGTH=3 NAME=octet_4></INPUT></TD>
				</TR>
				<TR>
					<TD ALIGN=RIGHT BGCOLOR=\"$cell_bg_color_2\"><STRONG>Bitmask</STRONG></TD>
					<TD ALIGN=LEFT><SELECT NAME=bitmask>
												<OPTION SELECTED VALUE=\"24\">24 Bits - (256 Hosts)
												<OPTION VALUE=\"25\">25 Bits - (128 Hosts)
												<OPTION VALUE=\"26\">26 Bits - (64 Hosts)
												<OPTION VALUE=\"27\">27 Bits - (32 Hosts)
												<OPTION VALUE=\"28\">28 Bits - (16 Hosts)
												<OPTION VALUE=\"29\">29 Bits - (8 Hosts)
												<OPTION VALUE=\"30\">30 Bits - (4 Hosts)
												<OPTION VALUE=\"31\">31 Bits - (2 Hosts)
												</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Submit\"></TD>
				</TR>
			</FORM>
			</TABLE>
			<P>
			<STRONG><FONT COLOR=RED>Please Note!</FONT></STRONG> The network number and bitmask you enter must be unique!  There is no error checking to see if any of the IP addresses that fall within this range are used by another IP range.<P>
			You should <a href=\"$PHP_SELF?section=domain&function=list_ip_range\">List IP Ranges</a> and review the output before proceeding!";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function=="delete_ns")
	{
		$request_ns_info = mysql_query ("select * from ns_table where record = \"$record\"");
		$result_ns_info = mysql_fetch_array ($request_ns_info);
		$ns_name = $result_ns_info[name];
		$ns_is_primary = $result_ns_info[is_primary];
		$ns_ip_address = $result_ns_info[ip_address];
		$ns_domain = $result_ns_info[points_to_domain];
		$request_domain_info = mysql_query ("select domain_name from domain_table where record = \"$ns_domain\"");
		$result_domain_info = mysql_fetch_array ($request_domain_info);
		$domain_name = $result_domain_info [domain_name];
		if ($ns_is_primary == "YES")
		{
			$display_block="
				<P><STRONG><FONT COLOR=\"RED\">WARNING!</FONT></STRONG><BR>This is the primary name server record.  You will need
				to add a new Primary Name Server for this domain to function properly if you delete this record!<P>";
			printf ("$display_block");
		}
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_delete_ns\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
			<INPUT TYPE=HIDDEN NAME=domain_record VALUE=\"$ns_domain\">
		  Are you <STRONG>SURE</STRONG> you wish to delete the following information?
			<P>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Domain</STRONG></TD>
					<TD>$domain_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>NS Hostname</STRONG></TD>
					<TD>$ns_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>IP Address</STRONG></TD>
					<TD>$ns_ip_address</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Primary NS</BR></STRONG></TD>
					<TD>$ns_is_primary</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this NS Record!\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}
		
	else if ($function=="submit_delete_ns")
	{
		check_lock();
		check_domain_privilege ($domain_record, $user_record, $user_dnstools_administrator);
		$domain_name = get_domain_name ($domain_record);
		$sql = "delete from ns_table where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Deleted NS record successfully from domain $domain_name.\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Deleted NS record successfully from $domain_name.");
		update_domain_serial ($domain_record);
		endpage();
		exit;
	}

	else if ($function=="submit_add_ns")
	{
		check_lock();
		if ($user_dnstools_administrator != "YES")
		{
			check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		}
		check_valid_full_hostname ($name);
		hostname_must_have_dots ($name);
		$request_duplicate_ns = mysql_query ("select * from ns_table where name = \"$name\" and points_to_domain = \"$points_to_domain\"");
		$num_rows = mysql_num_rows ($request_duplicate_ns);
		if ($num_rows != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate Name Server hostname for this domain.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}

		$request_duplicate_ns = mysql_query ("select * from ns_table where ip_address = \"$ns_ip_address\" and points_to_domain = \"$points_to_domain\"");
		$num_rows = mysql_num_rows ($request_duplicate_ns);
		if ($num_rows != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate Name Server IP address for this domain.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}

		if ($ns_is_primary == "YES")
		{
			$display_block="
				Modifying domain name servers, setting this name server to primary, all others to secondary...<P>";
			printf ("$display_block");
			$sql = "update ns_table SET is_primary = \"NO\" where points_to_domain = \"$points_to_domain\"";
			$result = mysql_query($sql) or die (mysql_error());
		}

		$sql = "insert into ns_table (is_primary, name, ip_address, points_to_domain) values (\"$ns_is_primary\", \"$name\", \"$ns_ip_address\", \"$points_to_domain\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added new NS record <strong>$name</strong> with IP address of <strong>$ns_ip_address</strong> successfully!\n";
		printf ("$display_block");
		write_log ("1", "$user_user_name", "Added NS record $name with IP of $ns_ip_address");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function=="list_ns")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can list the Name Server records that resides in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Listing NS Records</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=CENTER><STRONG>Domain</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Hostname</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>IP Address</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Primary</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
			</TR>";
		printf ("$display_block\n");
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		$odd_even = 0;
		foreach ($domain_list as $current_domain)
		{
			$request_ns_record = mysql_query ("select * from ns_table where points_to_domain = \"$current_domain\" order by name");
			while ($result_ns_record = mysql_fetch_array($request_ns_record))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 0;
				}
				$ns_record = $result_ns_record[record];
				$ns_is_primary = $result_ns_record[is_primary];
				$ns_ip_address = $result_ns_record[ip_address];
				$ns_name = $result_ns_record[name];
				$request_domain_name = mysql_query ("select domain_name from domain_table where record = \"$current_domain\"");
				$result_domain_name = mysql_fetch_array($request_domain_name);
				$ns_domain_name = $result_domain_name[domain_name];
				if ($ns_record)
				{
					$display_block="
						<TR>
							<TD BGCOLOR=\"$bgcolor\">$ns_domain_name</TD>
							<TD BGCOLOR=\"$bgcolor\">$ns_name</TD>
							<TD BGCOLOR=\"$bgcolor\">$ns_ip_address</TD>
							<TD BGCOLOR=\"$bgcolor\">$ns_is_primary</TD>
							<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=domain&function=delete_ns&record=$ns_record\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete NS\" border=0></img></A></TD>
						</TR>";
					printf ("$display_block");
				}
			}
		}
		$display_block="
			</TABLE>
			</FORM>";
		printf ("$display_block");
		write_log ("2", "$user_user_name", "Listed all NS records");
		endpage();
		exit;
	}

	else if ($function=="add_ns")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can add a Name Server record into it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		right_justified_help_link ("add_ns");
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_add_ns\" METHOD=POST>
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD></TD>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=CENTER>Add NS Record</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT>Select Domain</TD>
					<TD COLSPAN=4 ALIGN=LEFT><SELECT NAME=points_to_domain>";
		printf ("$display_block");

		$group_list = which_groups($user_record, $user_dnstools_administrator);
		$domain_list = which_domains($group_list, $user_dnstools_administrator);
		foreach ($domain_list as $current_domain)
		{
			$domain_name = get_domain_name ($current_domain);
			$display_block="
						<OPTION VALUE=\"$current_domain\">$domain_name";
			printf ("$display_block");
		}

		$display_block="
						</SELECT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT>Full hostname</TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=name SIZE=30></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT>IP Address</TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=ns_ip_address SIZE=15 MAXLENGTH=15>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT>Primary</TD>
					<TD><SELECT NAME=ns_is_primary>
							<OPTION SELECTED VALUE=\"NO\">NO
							<OPTION VALUE=\"YES\">YES</SELECT></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Submit\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

	else if ($function=="submit_add_mx")
	{
		check_lock();
		if ($user_dnstools_administrator != "YES")
		{
			check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		}
		check_valid_full_hostname ($name);
		check_valid_mx_priority ($priority);
		hostname_must_have_dots ($name);
		$request_duplicate_mx = mysql_query ("select * from mx_table where name = \"$name\" and points_to_domain = \"$points_to_domain\"");
		$num_rows = mysql_num_rows ($request_duplicate_mx);
		if ($num_rows != 0)
		{
			$display_block="
				<P><FONT COLOR=\"RED\">WARNING!</FONT><BR>Duplicate Mail Exchange record for this domain.  Please choose another.\n";
			printf ("$display_block");
			endpage();
			exit;
		}
		$sql = "insert into mx_table (name, priority, points_to_domain) values (\"$name\", \"$priority\", \"$points_to_domain\")";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Added new MX record <strong>$name</strong> at priority $priority successfully!\n";
		printf ("$display_block");
		$domain_name = get_domain_name($points_to_domain);
		update_domain_serial ($points_to_domain);
  	write_log ("1", "$user_user_name", "Added new MX entry $name to $domain_name");
		endpage();
		exit;
	}

	else if ($function=="submit_delete_mx")
	{
		check_lock();
		$request_domain = mysql_query ("select points_to_domain from mx_table where record = \"$record\"");
		$result_domain = mysql_fetch_array ($request_domain);
		$points_to_domain = $result_domain[points_to_domain];
		check_domain_privilege ($points_to_domain, $user_record, $user_dnstools_administrator);
		$sql = "delete from mx_table where record = \"$record\"";
		$result = mysql_query($sql) or die (mysql_error());
		$display_block="
			Deleted MX record successfully.\n";
		printf ("$display_block");
		write_log ("2", "$user_user_name", "Deleted MX record");
		update_domain_serial ($points_to_domain);
		endpage();
		exit;
	}

	else if ($function=="delete_mx")
	{
		$request_mx_info = mysql_query ("select * from mx_table where record = \"$record\"");
		$result_mx_info = mysql_fetch_array ($request_mx_info);
		$mx_name = $result_mx_info[name];
		$mx_priority = $result_mx_info[priority];
		$mx_domain = $result_mx_info[points_to_domain];
		$mx_host = $result_mx_info[points_to_host];
		$domain_name = get_domain_name ($mx_domain);
		$host_name = get_host_name ($mx_host);
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_delete_mx\" METHOD=POST>
			<INPUT TYPE=HIDDEN NAME=record VALUE=\"$record\">
		  Are you <STRONG>SURE</STRONG> you wish to delete the following information?
			<P>
			<TABLE CELLSPACING=2 CELLPADDING=2 BORDER=0>";
		printf ("$display_block");
		if ($mx_domain != 0)
		{
			$display_block="
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Domain</STRONG></TD>
					<TD>$domain_name</TD>
				</TR>";
			printf ("$display_block");
		}
		else
		{
			$display_block="
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>Host</STRONG></TD>
					<TD>$host_name</TD>
				</TR>";
			printf ("$display_block");
		}
		$display_block="
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_2\"><STRONG>MX Hostname</STRONG></TD>
					<TD>$mx_name</TD>
				</TR>
				<TR>
					<TD BGCOLOR=\"$cell_bg_color_1\"><STRONG>MX Priority</STRONG></TD>
					<TD>$mx_priority</TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"YES! Delete this MX Record!\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}
		
	else if ($function=="list_mx")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can list the MX records that reside in it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		$display_block="
		<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
			<TR>
				<TD COLSPAN=5 ALIGN=CENTER BGCOLOR=\"$cell_bg_color_1\"><STRONG>Listing MX Records</STRONG></TD>
			</TR>
			<TR>
				<TD ALIGN=CENTER><STRONG>Domain</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Priority</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Hostname</STRONG></TD>
				<TD ALIGN=CENTER><STRONG>Delete</STRONG></TD>
			</TR>";
		printf ("$display_block\n");
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		$odd_even = 0;
		foreach ($domain_list as $current_domain)
		{
			$request_mx_record = mysql_query ("select record, name, priority from mx_table where points_to_domain = \"$current_domain\" order by priority");
			while ($result_mx_record = mysql_fetch_array($request_mx_record))
			{
			  if ($odd_even == 0)
				{
					$bgcolor = $cell_bg_color_2;
					$odd_even = 1;
				}
				else
				{
					$bgcolor = $cell_bg_color_1;
					$odd_even = 0;
				}
				$mx_record = $result_mx_record[record];
				$mx_name = $result_mx_record[name];
				$mx_priority = $result_mx_record[priority];
				$request_domain_name = mysql_query ("select domain_name from domain_table where record = \"$current_domain\"");
				$result_domain_name = mysql_fetch_array($request_domain_name);
				$mx_domain_name = $result_domain_name[domain_name];
				if ($mx_record)
				{
					$display_block="
						<TR>
							<TD BGCOLOR=\"$bgcolor\">$mx_domain_name</TD>
							<TD BGCOLOR=\"$bgcolor\">$mx_priority</TD>
							<TD BGCOLOR=\"$bgcolor\">$mx_name</TD>
							<TD BGCOLOR=\"$bgcolor\" ALIGN=CENTER WIDTH=45><A HREF=\"$PHP_SELF?section=domain&function=delete_mx&record=$mx_record\"><img src=\"/dnstools/images/icons/ultra-small-delete.gif\" alt=\"Delete MX\" border=0></img></A></TD>
						</TR>";
					printf ("$display_block");
				}
			}
		}
		$display_block="
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		write_log ("2", "$user_user_name", "Listed all MX records");
		exit;
	}

	else if ($function=="add_mx")
	{
		$group_list = which_groups ($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains ($group_list, $user_dnstools_administrator);
		if (! $domain_list[0])
		{
			$display_block="
				There are no domains yet.  You must add a domain before you can add an MX records to it.";
			printf ("$display_block");
			endpage();
			exit;
		}
		right_justified_help_link ("add_mx");
		$display_block="
			<FORM ACTION=\"$PHP_SELF?section=domain&function=submit_add_mx\" METHOD=POST>
			<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
				<TR>
					<TD></TD>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=CENTER>Add MX Record</TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT>Select Domain</TD>
					<TD ALIGN=LEFT><SELECT NAME=points_to_domain>";
		printf ("$display_block");

		$group_list = which_groups($user_record, $user_dnstools_administrator);
		$domain_list = which_primary_domains($group_list, $user_dnstools_administrator);
		foreach ($domain_list as $current_domain)
		{
			$request_domain_name = mysql_query("select domain_name from domain_table where record = \"$current_domain\"");
			$result_domain_name = mysql_fetch_array ($request_domain_name);
			$domain_name = $result_domain_name[domain_name];
			$display_block="
						<OPTION VALUE=\"$current_domain\">$domain_name";
			printf ("$display_block");
		}

		$display_block="
						</SELECT></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_1 ALIGN=RIGHT>Full hostname</TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=name SIZE=30></TD>
				</TR>
				<TR>
					<TD BGCOLOR=$cell_bg_color_2 ALIGN=RIGHT>Priority</TD>
					<TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=priority SIZE=3></TD>
				</TR>
				<TR>
					<TD></TD>
					<TD><INPUT TYPE=SUBMIT VALUE=\"Submit\"></TD>
				</TR>
			</TABLE>
			</FORM>";
		printf ("$display_block");
		endpage();
		exit;
	}

  else

	{
		$display_block="
			<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>Domains</STRONG></TD>
				</TR>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				<TR>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=add_domain\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=add_domain\">Add</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=list_domains\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=list_domains\">List</A></TD>
				</TR>";
		}
		else
		{
			$display_block="
			  <TR>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=list_domains\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=list_domains\">List</A></TD>
				</TR>";
		}
		printf ("$display_block");
		$display_block="
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>IP Ranges</STRONG></TD>
				</TR>
				<TR>";
		printf ("$display_block");

		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=add_ip_range\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=add_ip_range\">Add</A></TD>";
			printf ("$display_block");
		}

		$display_block="
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=list_ip_range\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=list_ip_range\">List</A></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>Mail Exchange Servers</STRONG></TD>
				</TR>
				<TR>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=add_mx\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=add_mx\">Add</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=list_mx\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=list_mx\">List</A></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=4 ALIGN=CENTER><STRONG>Name Servers</STRONG></TD>
				</TR>
				<TR>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=add_ns\"><IMG SRC=\"/dnstools/images/icons/small-button-add.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=add_ns\">Add</A></TD>
				  <TD><A HREF=\"$PHP_SELF?section=domain&function=list_ns\"><IMG SRC=\"/dnstools/images/icons/small-button-listview.gif\" BORDER=0></A></TD>
					<TD><A HREF=\"$PHP_SELF?section=domain&function=list_ns\">List</A></TD>
				</TR>";
		printf ("$display_block");
		if ($user_dnstools_administrator == "YES")
		{
			$display_block="
				<TR>
					<TD COLSPAN=4><HR></TD>
				</TR>
				<TR>
					<TD COLSPAN=4><FORM METHOD=POST ACTION=\"$PHP_SELF?section=domain&function=force_update\"><INPUT TYPE=SUBMIT VALUE=\"FORCE UPDATE\"></TD></TR>";
			printf ("$display_block");
		}
		printf ("</TABLE>");
		endpage();
		exit;
	}
}

/*
	#############################
	THIS IS THE MORE_INFO SECTION
	#############################
*/

# SECTION MORE_INFO

else if ($section=="more_info")
{
  beginpage(0);
  $display_block="
  <TABLE>
    <TR BGCOLOR=$header_bg_color><TD COLSPAN=2 ALIGN=CENTER><FONT SIZE=+1 COLOR=$highlight_color><STRONG>$app_name</FONT></STRONG></TD></TR>
    <TR><TD ALIGN=RIGHT><STRONG>Author</STRONG></TD><TD>Damon LaCaille (mailto:<A HREF=\"mailto:damonl@dnstools.sourceforge.net\">damon@dnstools.sourceforge.net</a>)</TD></TR>
    <TR><TD ALIGN=RIGHT><STRONG>Version</STRONG></TD><TD>$version</TD></TR>
   <TR><TD ALIGN=RIGHT><STRONG>Database</STRONG></TD><TD><a href=\"http://www.mysql.com\" TARGET=_main>MySQL</a></TD></TR>
	 <TR><TD ALIGN=RIGHT><STRONG>Copyright</STRONG></TD><TD> 2002 Damon LaCaille</TD></TR>
  </TABLE>";
 print ("$display_block");
}

/*
	#############################
	THIS IS THE LOGIN SECTION
	#############################
*/

# SECTION LOGIN

else if ($section=="login")
{
  if ($user_logged_in!="true")
  {
    beginpage("login");
    $display_block="
      <FORM METHOD=POST ACTION=\"$PHP_SELF?section=submit_login\">
      <TABLE>
        <TR>
          <TD ALIGN=RIGHT>Username:</TD>
          <TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=\"user_name\" SIZE=20></TD>
				</TR>
        <TR>
          <TD ALIGN=RIGHT>Password:</TD>
          <TD ALIGN=LEFT><INPUT TYPE=PASSWORD NAME=password SIZE=10></TD>
        </TR>
        <TR>
          <TD></TD>
          <TD><INPUT TYPE=Submit VALUE=\"Login\"></TD>
        </TR>
				<TR>
					<TD></TD>
					<TD><font size=-1><em>For more information on DNSTools 2.0, </em><a href=\"$PHP_SELF?section=more_info\">click here</a></TD>
				</TR>
      </TABLE>
      </FORM>\n";
    printf ("$display_block\n");
  }
  else
  {
    beginpage("login");
    $display_block="
      You are logged in.<br>
      Please make a selection from the menu on the left.\n";
    printf ("$display_block");
  }
}
else
{
	if ($section != "leftmenu")
	{
		header("Location: $PHP_SELF?section=login");
	}
}
  
endpage();
?>
