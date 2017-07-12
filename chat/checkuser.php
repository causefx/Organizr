<?php

$user = $_POST["user"];

if( $db = new SQLite3("../chatpack.db") )
{
    if( $db->busyTimeout(5000) )
    {
        if( $db->exec("PRAGMA journal_mode = wal;") )
        {
            if( $result = @$db->query("SELECT timestamp, user FROM chatpack_log
                                       WHERE LOWER(user)= LOWER('$user') ORDER BY id DESC") )
            {
                if( $row = $result->fetchArray() )
                {
                    $timestamp = $row["timestamp"];
                    $timenow = time();

                    if( $timestamp < $timenow - 2700 )  // user's last message too old means user offline
                    {
                        echo "success";
                    }
                    else  // user's last message young enough means user still online
                    {
                        echo "usernametaken";
                    }
                }
                else  // username available
                {
                    echo "success";
                }
            }
            else
            {
                errormessage("querying database while checking user");
            }

            if( !@$db->close() )
            {
                errormessage("closing database connection after checking user");
            }
        }
        else
        {
            errormessage("setting journal mode");
        }
    }
    else
    {
        errormessage("setting busy timeout");
    }
}
else
{
    errormessage("using SQLite");
}

function errormessage($msg)
{
    echo "<div style=\"margin-top: 50px;\">";
    echo "<span style=\"color:#d89334;\">error </span>";
    echo $msg;
    echo "</div>";
}

?>