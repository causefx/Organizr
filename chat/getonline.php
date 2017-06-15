<?php

include("connect.php");

$onlineusers = array();

if( $result = $db->query("SELECT user, timestamp, avatar FROM chatpack_last_message ORDER BY user ASC") )
{
    while( $row = $result->fetchArray() )
    {            
        $user = $row["user"];
        $timestamp = $row["timestamp"];
        $avatar = $row["avatar"];
        $push = array($user, $timestamp, $avatar);

        array_push($onlineusers, $push);
    }
}

$db->close();

// pass online users as JSON back to chat.js

echo json_encode($onlineusers);

?>