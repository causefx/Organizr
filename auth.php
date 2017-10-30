<?php
$debug = false; //CAREFUL WHEN SETTING TO TRUE AS THIS OPENS AUTH UP
require_once("user.php");
$USER = new User("registration_callback");
$ban = isset($_GET['ban']) ? strtoupper($_GET['ban']) : "";
$whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
$blacklist = isset($_GET['blacklist']) ? $_GET['blacklist'] : false;
$currentIP = get_client_ip();

if ($whitelist) {
	$skipped = false;
    if(in_array($currentIP, getWhitelist($whitelist))) {
       !$debug ? exit(http_response_code(200)) : die("$currentIP Whitelist Authorized");
	}else{
		$skipped = true;
	}
}
if ($blacklist) {
    if(in_array($currentIP, getWhitelist($blacklist))) {
       !$debug ? exit(http_response_code(401)) : die("$currentIP Blacklisted");
	}
}
if (isset($_GET['admin'])) {
    if($USER->authenticated && $USER->role == "admin" && !in_array(strtoupper($USER->username), getBannedUsers($ban))) {
        !$debug ? exit(http_response_code(200)) : die("$USER->username on $currentIP Authorized At Admin Level");
	} else {
        !$debug ? exit(http_response_code(401)) : die("$USER->username on $currentIP Not Authorized At Admin Level");
    }
}
if (isset($_GET['user'])) {
    if($USER->authenticated && !in_array(strtoupper($USER->username), getBannedUsers($ban))) {
        !$debug ? exit(http_response_code(200)) : die("$USER->username on $currentIP Authorized At User Level");
	} else {
        !$debug ? exit(http_response_code(401)) : die("$USER->username on $currentIP Not Authorized At User Level");
	}
}
if (!isset($_GET['user']) && !isset($_GET['admin']) && !isset($_GET['whitelist'])) {
    !$debug ? exit(http_response_code(401)) : die("Not Authorized Due To No Parameters Set");
}

if ($skipped) {
	!$debug ? exit(http_response_code(401)) : die("$USER->username on $currentIP Not Authorized Nor On Whitelist");
}

?>
