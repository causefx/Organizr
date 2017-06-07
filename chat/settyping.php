<?php

$data = $_POST["datavars"];
$dataarray = explode("###", $data);
$user = $dataarray[0];
$settyping = $dataarray[1];

include("connect.php");

$useristyping = false;
$offlineusers = array();

if( $result = $db->query("SELECT timestamp, user FROM chatpack_typing") )
{
    while( $row = $result->fetchArray() )
    {
        $typinguser = $row["user"];
        $timestamp = $row["timestamp"];

        // check whether user is currently typing
        
        if( strcmp($typinguser, $user) == 0 )
        {
            $useristyping = true;
        }
        
        // catch users who are offline but still set as typing
        
        $timenow = time();
        
        if( $timestamp < $timenow - 2700 )
        {
            array_push($offlineusers, $typinguser);
        }
    }
}

if( !$useristyping && $settyping == 1 )  // set user as typing
{
    $timestamp = time();
    
    $db->exec("INSERT INTO chatpack_typing (timestamp, user) VALUES ('$timestamp', '$user')");
}
else if( $settyping == 0 )  // set user as not typing
{
    $db->exec("DELETE FROM chatpack_typing WHERE user='$user'");
}

// set offline users as not typing

for( $i=0; $i<count($offlineusers); $i++ )
{
    $db->exec("DELETE FROM chatpack_typing WHERE user='$offlineusers[$i]'");
}

$db->close();

?>