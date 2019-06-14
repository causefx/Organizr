<?php
// Set UTC timeone
date_default_timezone_set("UTC");
// Autoload frameworks
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
// Include all function files
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
	require_once $filename;
}
// Set Root Directory
$GLOBALS['root'] = dirname(__DIR__, 1);
$GLOBALS['uuid'] = '';
$GLOBALS['rememberMeDays'] = '99';
$GLOBALS['timeExecution'] = timeExecution();
// Add in default and custom settings
configLazy();
// Define Logs and files after db location is set
if (isset($GLOBALS['dbLocation'])) {
	$GLOBALS['organizrLog'] = $GLOBALS['dbLocation'] . 'organizrLog.json';
	$GLOBALS['organizrLoginLog'] = $GLOBALS['dbLocation'] . 'organizrLoginLog.json';
	$GLOBALS['paths'] = array(
		'Root Folder' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR,
		'API Folder' => dirname(__DIR__, 1) . DIRECTORY_SEPARATOR,
		'DB Folder' => $GLOBALS['dbLocation']
	);
	if (($GLOBALS['uuid'] == '')) {
		$uuid = gen_uuid();
		$GLOBALS['uuid'] = $uuid;
		updateConfig(array('uuid' => $uuid));
	}
	if ($GLOBALS['docker']) {
		$getBranch = file_get_contents(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'Docker.txt');
		$getBranch = (empty($getBranch)) ? 'v2-master' : trim($getBranch);
		$GLOBALS['branch'] = $getBranch;
		if (!isset($GLOBALS['commit']) || $GLOBALS['commit'] == 'n/a') {
			$GLOBALS['commit'] = $GLOBALS['quickCommit'];
		}
	}
	//Upgrade Check
	upgradeCheck();
}
// Reset RememberMe if zero
$GLOBALS['rememberMeDays'] = ($GLOBALS['rememberMeDays'] == '0') ? '99' : $GLOBALS['rememberMeDays'];
// Cookie name
$GLOBALS['cookieName'] = $GLOBALS['uuid'] !== '' ? 'organizr_token_' . $GLOBALS['uuid'] : 'organizr_token_temp';
// Validate Token if set and set guest if not - sets GLOBALS
getOrganizrUserToken();
// Include all pages files
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
	require_once $filename;
}
// Include all custom pages files
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
	require_once $filename;
}
// Include all plugin files
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
	require_once $filename;
}
