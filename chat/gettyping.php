<?php

include("connect.php");

$typingusers = array();

if( $result = $db->query("SELECT user FROM chatpack_typing") )
{
    while( $row = $result->fetchArray() )
    {            
        $user = $row["user"];

        array_push($typingusers, $user);
    }
}

$db->close();

// pass typing users as JSON back to chat.js

echo json_encode($typingusers);

?>