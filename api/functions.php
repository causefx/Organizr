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
// Add in default and custom settings
configLazy();
// Define Logs and files after db location is set
if (isset($GLOBALS['dbLocation'])) {
	$GLOBALS['organizrLog'] = $GLOBALS['dbLocation'] . 'organizrLog.json';
	$GLOBALS['organizrLoginLog'] = $GLOBALS['dbLocation'] . 'organizrLoginLog.json';
	if (($GLOBALS['uuid'] == '')) {
		$uuid = gen_uuid();
		$GLOBALS['uuid'] = $uuid;
		updateConfig(array('uuid' => $uuid));
	}
	//Upgrade Check
	upgradeCheck();
}
// Cookie name
$GLOBALS['cookieName'] = $GLOBALS['uuid'] !== '' ? 'organizr_token_' . $GLOBALS['uuid'] : 'organizr_token_temp';
// Validate Token if set and set guest if not - sets GLOBALS
getOrganizrUserToken();
// Include all pages files
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
	require_once $filename;
}
// Include all plugin files
foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
	require_once $filename;
}
