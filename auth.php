<?php

$data = false;

function getBannedUsers($string){
    if (strpos($string, ',') !== false) {
        $banned = explode(",", $string);     
    }else{
        $banned = array($string);  
    }
    return $banned;
}

function getWhitelist($string){
    if (strpos($string, ',') !== false) {
        $whitelist = explode(",", $string); 
    }else{
        $whitelist = array($string);
    }
    foreach($whitelist as &$ip){
        $ip = is_numeric(substr($ip, 0, 1)) ? $ip : gethostbyname($ip);
    }
    return $whitelist;
}

//if (isset($_GET['ban'])) : $ban = strtoupper($_GET['ban']); else : $ban = ""; endif;
//if (isset($_GET['whitelist'])) : $whitelist = $_GET['whitelist']; else : $whitelist = ""; endif;
$ban = isset($_GET['ban']) ? strtoupper($_GET['ban']) : "";
$whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
$currentIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;

require_once("user.php");
$USER = new User("registration_callback");

if ($whitelist) {
    if(in_array($currentIP, getWhitelist($whitelist))) {
       exit(http_response_code(200)); 
	} else {
       exit(http_response_code(401));
	}
} elseif (isset($_GET['admin'])) {
    if($USER->authenticated && $USER->role == "admin" && !in_array(strtoupper($USER->username), getBannedUsers($ban))) {
        exit(http_response_code(200));
	} else {
        exit(http_response_code(401));
    }
} elseif (isset($_GET['user'])) {
    if($USER->authenticated && !in_array(strtoupper($USER->username), getBannedUsers($ban))) {
        exit(http_response_code(200));
	} else {
        exit(http_response_code(401));
	}
} elseif (!isset($_GET['user']) && !isset($_GET['admin']) && !isset($_GET['whitelist'])) {
    exit(http_response_code(401));
}

?>