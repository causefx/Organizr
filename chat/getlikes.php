<?php

include("connect.php");
   
$likedmessages = array();
$unlikedmessages = array();

if( $result = $db->query("SELECT id, liked FROM chatpack_log") )
{
    while( $row = $result->fetchArray() )
    {
        $messageid = $row["id"];
        $liked = $row["liked"];

        if( $liked == 1 )
        {
            array_push($likedmessages, $messageid);
        }
        else if( $liked == 0 )
        {
            array_push($unlikedmessages, $messageid);
        }
    }
}

$db->close();

// pass likes and unlikes back to chat.js

$likes = json_encode($likedmessages);
$unlikes = json_encode($unlikedmessages);
$likesandunlikes = $likes . "#" . $unlikes;

echo $likesandunlikes;

?>