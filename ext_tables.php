<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$tempColumns = Array (
	"tx_auxnewsmailersplitcat_tmplmsgbounce" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_control.tx_auxnewsmailersplitcat_tmplmsgbounce",		
		"config" => Array (
			"type" => "input",
	        "default" => "10",
			"size" => "6",
			"eval" => "int,required",
			"range" => array("lower" => 1, "upper" => 1000000),			
		)			
	),
	"tx_auxnewsmailersplitcat_tmplcrtbounce" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_control.tx_auxnewsmailersplitcat_tmplcrtbounce",		
		"config" => Array (
			"type" => "input",
	        "default" => "1000",
			"size" => "6",
			"eval" => "int,required",
			"range" => array("lower" => 1, "upper" => 1000000),			
		)			
	),	
	"tx_auxnewsmailersplitcat_tmplcat" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_control.tx_auxnewsmailersplitcat_tmplcat",		
		"config" => Array (
			"type" => "check",
	        "default" => "0"			
		)			
	),
	"tx_auxnewsmailersplitcat_tmplcatid" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_control.tx_auxnewsmailersplitcat_tmplcatid",		
		"config" => Array (
			"type" => "check",
	        "default" => "0"			
		),
		"displayCond" => "FIELD:tx_auxnewsmailersplitcat_tmplcat:=:1",		
	),
	"tx_auxnewsmailersplitcat_tmplcatnumber" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_control.tx_auxnewsmailersplitcat_tmplcatnumber",		
		"config" => Array (
			"type" => "check",
	        "default" => "0"			
		)	
	),	
	"tx_auxnewsmailersplitcat_tmpllinkstyle" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_control.tx_auxnewsmailersplitcat_tmpllinkstyle",		
		"config" => Array (
			"type" => "input",
	        "default" => "color:#000000;text-decoration:none"			
		)	
	),	
	"showitems" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.showitems",		
		"config" => Array (
			"type" => "select",	
			"items" => array(
				array("title",1),
				array("image",2),
				array("body",3),
				array("date",4),
				array("time",5),
				array("event(requires ext:mbl_newsevent)",6),	
				array("category image",7),		
				array("category image(no link)",8),												
			),
			"size"=>10,
			"maxitems"=>20,
			"default"=>"1,2,4,5",
		)
	),	
	"duration" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration",		
		"config" => Array (
			"type" => "select",
			"items" => Array (
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.0", "1"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.1", "2"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.2", "3"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.3", "4"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.4", "5"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.5", "6"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.6", "0"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.7", "8"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.8", "9"),
				Array("LLL:EXT:aux_newsmailer/locallang_db.xml:tx_auxnewsmailer_control.duration.I.9", "10"),
				Array("LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailersplitcat.duration.I.11", "11"),
				#Array("LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailersplitcat.duration.I.12", "12"),	
				#Array("LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailersplitcat.duration.I.13", "13"),
				Array("LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailersplitcat.duration.I.14", "14"),	
				#Array("LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailersplitcat.duration.I.15", "15"),
				#Array("LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailersplitcat.duration.I.16", "16"),												
			),
			"size"=>10,
			"maxitems"=>16,
			"default" => "0,1,2,3,4,5,6",
		)
	),		
);



$TCA["tx_auxnewsmailer_sendstat"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat',		
		'label' => 'idmsg',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs'=>true,
		"default_sortby" => "ORDER BY idmsg",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."ext_icon.gif",
	),
	"interface" => Array (
		"showRecordFieldList" => "hidden,create_total_time,send_total_time,idmsg"
	),
	"feInterface" => $TCA["tx_auxnewsmailer_control"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"create_total_msg" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.create_total_msg",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
				"eval" => "int",				
			)
		),		
		"create_total_time" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.create_total_time",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
			)
		),
		"create_total_time_seconds" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.create_total_time_seconds",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
			)
		),		
		"send_total_msg" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.send_total_msg",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
				"eval" => "int",				
			)
		),			
		"send_total_time" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.send_total_time",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
			)
		),
		"send_total_time_seconds" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.send_total_time_seconds",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
			)
		),
		"idmsg" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.idmsg",		
			"config" => Array (
				"type" => "input",	
				"size" => "11",
				"default"=>"0",
				"eval" => "int",				
			)
		),		
	),
	"types" => Array (
		"0" => Array("showitem" => "--div--;LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.idmsg;hidden;;1;;1-1-1, idmsg, --div--;LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.create_total_time, create_total_msg,create_total_time,create_total_time_seconds, --div--;LLL:EXT:aux_newsmailer_split_cat/locallang_db.xml:tx_auxnewsmailer_sendstat.send_total_time, send_total_msg,send_total_time,send_total_time_seconds")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);


#t3lib_extMgm::allowTableOnStandardPages("tx_auxnewsmailer_sendstat");

t3lib_div::loadTCA("tx_auxnewsmailer_control");
t3lib_extMgm::addTCAcolumns("tx_auxnewsmailer_control",$tempColumns,1);
#t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmplcat;;;;1-1-1");
t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmplcat;;;;1-1-1","","before:template");
t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmplcatid;;;;1-1-1","","before:template");
t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmplcatnumber;;;;1-1-1","","before:template");
t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmpllinkstyle;;;;1-1-1","","before:template");
t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmplmsgbounce;;;;3-3-3","","before:duration");
t3lib_extMgm::addToAllTCAtypes("tx_auxnewsmailer_control","tx_auxnewsmailersplitcat_tmplcrtbounce;;;;3-3-3","","before:duration");
?>