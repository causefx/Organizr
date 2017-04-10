<?php

$data = false;

function getBannedUsers($string){
    
    if (strpos($string, ',') !== false) {
    
        $banned = explode(",", $string);
        
    }elseif (strpos($string, ',') == false) {
    
        $banned = array($string);
        
    }
    
    return $banned;
    
}

if (isset($_GET['ban'])) : $ban = strtoupper($_GET['ban']); else : $ban = ""; endif;

require_once("user.php");
$USER = new User("registration_callback");

if (isset($_GET['admin'])) :

    if($USER->authenticated && $USER->role == "admin" && !in_array(strtoupper($USER->username), getBannedUsers($ban))) :

        exit(http_response_code(200));

    else :

        exit(http_response_code(401));

    endif;

elseif (isset($_GET['user'])) :

    if($USER->authenticated && !in_array(strtoupper($USER->username), getBannedUsers($ban))) :

        exit(http_response_code(200));

    else :

        exit(http_response_code(401));

    endif;

elseif (!isset($_GET['user'])  && !isset($_GET['admin'])) :

    exit(http_response_code(401));

endif;

?>