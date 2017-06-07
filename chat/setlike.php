<?php

$messageid = $_POST["messageid"];

include("connect.php");

$likemessage = false;

if( $result = $db->query("SELECT liked FROM chatpack_log WHERE id='$messageid'") )
{
    if( $row = $result->fetchArray() )
    {
        $liked = $row["liked"];

        if( $liked == 0 )
        {
            $likemessage = true;
        }
        else if( $liked == 1 )
        {
            $likemessage = false;
        }
    }
}

$db->close();

include("connect.php");

if( $likemessage )  // like message
{
    $db->exec("UPDATE chatpack_log SET liked='1' WHERE id='$messageid'");
}
else  // unlike message
{
    $db->exec("UPDATE chatpack_log SET liked='0' WHERE id='$messageid'");
}

$db->close();

?>