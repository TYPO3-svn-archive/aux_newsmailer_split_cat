#
# Table structure for table 'tx_auxnewsmailer_control'
#
CREATE TABLE tx_auxnewsmailer_control (
	tx_auxnewsmailersplitcat_tmplcat tinyint(3) DEFAULT '0' NOT NULL
	tx_auxnewsmailersplitcat_tmplcatnumber int(11) unsigned DEFAULT '0' NOT NULL
	tx_auxnewsmailersplitcat_tmplcatid int(11) unsigned DEFAULT '0' NOT NULL	
	tx_auxnewsmailersplitcat_tmpllinkstyle mediumtext NOT NULL	
	tx_auxnewsmailersplitcat_tmplmsgbounce int(11) unsigned DEFAULT '0' NOT NULL	
	tx_auxnewsmailersplitcat_tmplcrtbounce int(11) unsigned DEFAULT '0' NOT NULL
	control_status int(20) unsigned DEFAULT '0' NOT NULL					
);   

#
# Table structure for table 'tx_auxnewsmailer_maillist'
#
CREATE TABLE tx_auxnewsmailer_maillist (
	idmsg int(11) unsigned DEFAULT '0' NOT NULL	
); 

#
# Table structure for table 'tx_auxnewsmailer_usrmsg'
#
CREATE TABLE tx_auxnewsmailer_usrmsg (
	tstamp int(11) unsigned DEFAULT '0' NOT NULL	
); 

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_auxnewsmailer_newsletter int(11) unsigned DEFAULT '1' NOT NULL
	tx_auxnewsmailer_html int(11) unsigned DEFAULT '1' NOT NULL
);		

#
# Table structure for table 'tx_auxnewsmailer_sendstat'
#
CREATE TABLE tx_auxnewsmailer_sendstat (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,	 
	create_total_msg int(11) unsigned DEFAULT '0' NOT NULL,	
	create_total_time mediumtext NOT NULL,
	create_total_time_seconds int(11) unsigned DEFAULT '0' NOT NULL,	
	send_total_msg int(11) unsigned DEFAULT '0' NOT NULL,		
	send_total_time mediumtext NOT NULL,
	send_total_time_seconds int(11) unsigned DEFAULT '0' NOT NULL,	
	idmsg int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
	
);

#
# Table structure for table 'tt_news'
#
CREATE TABLE tt_news (
	tx_auxnewsmailer_scanstate_control int(11) unsigned DEFAULT '0' NOT NULL	
);
