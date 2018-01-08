<?php
// Set UTC timeone
date_default_timezone_set("UTC");
// Autoload frameworks
require_once(__DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php');
// Include all function files
foreach (glob(__DIR__.DIRECTORY_SEPARATOR.'functions'.DIRECTORY_SEPARATOR.'*.php') as $filename){
    require_once $filename;
}
// Add in default and custom settings
configLazy();
// Define Logs and files after db location is set
if(isset($GLOBALS['dbLocation'])){
    $GLOBALS['organizrLog'] = $GLOBALS['dbLocation'].'organizrLog.json';
    $GLOBALS['organizrLoginLog'] = $GLOBALS['dbLocation'].'organizrLoginLog.json';
}
// Validate Token if set and set guest if not - sets GLOBALS
getOrganizrUserToken();
// Include all pages files
foreach (glob(__DIR__.DIRECTORY_SEPARATOR.'pages' . DIRECTORY_SEPARATOR . "*.php") as $filename){
    require_once $filename;
}
