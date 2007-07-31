#!/usr/local/bin/php 
<?php


// set typo path to point to the root of the T3 installation
$typopath='/u100/webdata/html/typo3_portal/';

// create a BE user called _cli_auxnewsmailer (must not be admin), for better security change it to somethine else.
$MCONF['name'] = '_CLI_auxnewsmailer_spli_cat';

// *****************************************

// Standard initialization of a CLI module:

 // *****************************************


if (@is_file($typopath.'typo3conf/ext/aux_newsmailer_split_cat/mailer/class.ux_auxnewsmailer_corecron.php'))
	$modulepath=$typopath.'typo3conf/ext/aux_newsmailer_split_cat/';
else
	$modulepath=$typopath.'typo3conf/ext/aux_newsmailer_split_cat/';

// Defining circumstances for CLI mode:

define('TYPO3_cliMode', TRUE);

$BACK_PATH = '../../../../typo3/'; 
define('TYPO3_mainDir', 'typo3/');
define(PATH_thisScript,$typopath.'typo3/typo3');
define('TYPO3_MOD_PATH', $modulepath.'/mailer/');

// Include init file:
require($typopath.'typo3/init.php');
require($typopath.'typo3/sysext/lang/lang.php');

$LANG=t3lib_div::makeInstance('language');
$LANG->init('default'); 

require($modulepath.'mailer/class_auxnewsmailer_corecron.php'); 
require($modulepath.'mailer/class.ux_auxnewsmailer_corecron.php');

$mailer = new ux_auxnewsmailer_corecron;
$mailer->init();
// 0 all controls  
$mailer->batch($argv[1],'0','');
#$mailer->check_lang();
?>