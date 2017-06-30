<?php
$debug = false;
require_once("user.php");
$USER = new User("registration_callback");
$ban = isset($_GET['ban']) ? strtoupper($_GET['ban']) : "";
$whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
$currentIP = get_client_ip();

if ($whitelist) {
    if(in_array($currentIP, getWhitelist($whitelist))) {
       !$debug ? exit(http_response_code(200)) : die("$currentIP is Whitelist Authorized");
	}
} elseif (isset($_GET['admin'])) {
    if($USER->authenticated && $USER->role == "admin" && !in_array(strtoupper($USER->username), getBannedUsers($ban))) {
        !$debug ? exit(http_response_code(200)) : die("$USER->username Authorized At Admin Level");
	} else {
        !$debug ? exit(http_response_code(401)) : die("$USER->username Not Authorized At Admin Level");
    }
} elseif (isset($_GET['user'])) {
    if($USER->authenticated && !in_array(strtoupper($USER->username), getBannedUsers($ban))) {
        !$debug ? exit(http_response_code(200)) : die("$USER->username Authorized At User Level");
	} else {
        !$debug ? exit(http_response_code(401)) : die("$USER->username Not Authorized At User Level");
	}
} elseif (!isset($_GET['user']) && !isset($_GET['admin']) && !isset($_GET['whitelist'])) {
    !$debug ? exit(http_response_code(401)) : die("Not Authorized Due To No Parameters Set");
}

if ($skipped) {
	!$debug ? exit(http_response_code(401)) : die("$currentIP Not Authorized On Whitelist");
}

!$debug ? exit(http_response_code(401)) : die("$USER->username on $currentIP $skipped Not Authorized");

?>