<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration so we can use it here:
if ($_EXTCONF["pt"])	{t3lib_extMgm::addTypoScriptSetup("config.language = pt");}
	
$TYPO3_CONF_VARS["BE"]["XCLASS"]["ext/aux_newsmailer/mod1/index.php"] = PATH_typo3conf."ext/aux_newsmailer_split_cat/class.ux_ux_tx_auxnewsmailer_module1.php";
?>