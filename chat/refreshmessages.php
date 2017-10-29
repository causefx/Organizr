<?php

$currentuser = $_POST["user"];

// get last 25 messages, which covers 25 users messaging during one 500 millisecond message refresh

include("connect.php");

if( $result = $db->query("SELECT * FROM
                         (SELECT id, timestamp, user, avatar, message
                          FROM chatpack_log ORDER BY id DESC LIMIT 125)
                          ORDER BY id ASC") )
{
    $newmessages = array();

    while( $row = $result->fetchArray() )
    {
        $id = $row["id"];
        $timestamp = $row["timestamp"];
        $user = $row["user"];
        $avatar = $row["avatar"];
        $message = $row["message"];

        $timenow = time();
        $messagetime = date("h:iA", intval($timestamp));
        $messagedate = date("m-d", intval($timestamp));
        $messagenewtime = date("Y-m-d H:i:s", intval($timestamp));
        $message = utf8_encode($message);

        $msgstr = "";  // message components

        if( strlen($user) > 0 && strlen($message) > 0 )
        {
            // catch emoticon

            $emoticon = false;

            if( stristr($message, "specialcharemoticon") )
            {
                $emoticonid = substr($message, 11);
                $message = "<img class=\"emoticonimgchat\" id=\"$emoticonid\" src=\"img/$emoticonid.png\">";
                $emoticon = true;
            }

            // catch image

            $image = false;

            if( stristr($message, "specialcharimg") )
            {
                $imagename = substr($message, 14);
                $message = "<img class=\"thumbnailimgchat\" id=\"$imagename\" src=\"uploads/$imagename\">";
                $image = true;

                $endingpos = strpos($imagename, ".");
                $originalname = substr($imagename, 0, $endingpos-1);
                $ending = substr($imagename, $endingpos+1);
                $originalimg = $originalname . "." . $ending;
            }

            if( !$emoticon && !$image )
            {
                $message = decryptmessage($message);
            }

            // catch URLs

            /*$message = str_replace("https://", "http://", $message);

            if( !stristr($message, "http://www.") )
            {
                $message = str_replace("www.", "http://www.", $message);
            }

            $message = preg_replace("!((http|ftp)(s)?:\/\/)(www\.)?[a-zA-Z0-9.?&_/=\-\%\:,\#\+]+!",
                                    "<a href=\"$0\" target=\"_blank\">$0</a>", $message);
            $message = str_replace("target=\"_blank\">http://", "target=\"_blank\">", $message); */

            // catch highlightings

            $message = preg_replace("/\*{3}(.*?)\*{3}/", "<mark>$1</mark>", $message);
            $message = preg_replace("/\*{2}(.*?)\*{2}/",
                                    "<span style=\"font-size: 20px; color: #b77fdb;\"><em>$1</em></span>", $message);
            $message = preg_replace("/\*(.*?)\*/",
                                    "<span style=\"color: #d89334;\"><strong>$1</strong></span>", $message);

            // user online avatar

            //$avatar = "<img class=\"avatarimg\" id=\"$timestamp\" src=\"" . $avatar . "\">";

            // unique message key

            $keystring = $timestamp . $user . $messagetime . $message . $id;
            $messagekey = md5($keystring);

            // show user avatar and message
            if($user == $currentuser){
                $msgstr = $msgstr . "<p class=\"avatarandtext\" id=\"$messagekey\"><li><img src=\"$avatar\" id=\"$timestamp\" class=\"img-circle user-avatar $user\" alt=\"$user\"><div class=\"chat-panel blue-bg messagelike\" id=\"$id\"><div class=\"chat-heading clearfix\"><h4 class=\"pull-left zero-m\">$user</h4><p class=\"pull-right\"><i class=\"fa fa-clock-o\"></i><timestamp time=\"$messagenewtime\" class=\"chat-timestamp\">$messagenewtime</timestamp></p></div><div class=\"chat-body\">$message</div></div></li></p>";//class="chat-inverted"
            }else{
                $msgstr = $msgstr . "<p class=\"avatarandtext\" id=\"$messagekey\"><li class=\"chat-inverted\"><img src=\"$avatar\" id=\"$timestamp\" class=\"img-circle user-avatar $user\" alt=\"$user\"><div class=\"chat-panel red-bg messagelike\" id=\"$id\"><div class=\"chat-heading clearfix\"><h4 class=\"pull-left zero-m\">$user</h4><p class=\"pull-right\"><i class=\"fa fa-clock-o\"></i><timestamp time=\"$messagenewtime\" class=\"chat-timestamp\">$messagenewtime</timestamp></p></div><div class=\"chat-body\">$message</div></div></li></p>";//class="chat-inverted"
            }

            array_push($newmessages, $msgstr);
        }
    }
}

$db->close();

function decryptmessage($msg)
{
    $key = "OEFKSjczdG5JWkFITHZNUmFLT1I4aWRWaVVWY3l1SXdJZ285V2R3Ri90QjF4NUU1VG9mNnM
            wcDRYWTQ1dEtRRXRYNlFWZE01QW1WS0hTNXZzaEtRbEdkcXY4cWpEOVRBYjBzSGJlRXVPWW
            9aWUtzNGZtK1BnRzRPeXk4ZWY0VUphUjc5VzRGQ2s0UXRrNENOWERJWmM3SWNFSEtpM0hpcT
            l2UVRET2UrMkxQR29ONVpOVDRnSHArTGVwQU15NXg4YzdNSWZQTlBOd2FlWmY2aWRQOUdSZVh
            3VXQ4a1JlNDkwMWZIVE42cmpIMkRrUkg1VnF1NC9zMmhTZFROVnNleVlSTnVvcWtDYlB3TEJU
            eDlRT3ZPZVQ2N0psT0NFNW5nekFCdG9xLzZ6K0Qva1V5UzNoVlAxWGt1ZittZnE5ek10Q2x4Q1
            QrdHVRdEVoYUIxc2V1UjgrZDZyK1Zzem9LOEtpSG9halczNEpmem5nRWllSDBaRzNERHBTbUxB
            MGlodTZsclFEVzZLcjVBNEtYRUpxQXVNaEcycGN4U2VzT01NRlljM3pHL3Q1az0";
    $initvector = "aC92eG1PdGhuMXN6";
    $decryptedmessage = openssl_decrypt($msg, "AES-256-CBC", $key, 0, $initvector);
    $decryptedmessage = utf8_encode($decryptedmessage);

    return $decryptedmessage;
}

// pass new messages back to chat.js

if( count($newmessages) == 1 )
{
    echo $newmessages[0];
}
else
{
    for( $i=0; $i<count($newmessages); $i++ )
    {
        if( $i == count($newmessages) - 1 )
        {
            echo $newmessages[$i];
        }
        else
        {
            echo $newmessages[$i] . "###endofmessage###";
        }
    }
}

?>
