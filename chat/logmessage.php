<?php

$data = $_POST["messagedata"];
$dataarray = explode("###", $data);
$message = $dataarray[0];
$user = $dataarray[1];
$avatar = $dataarray[2];

include("connect.php");

if( strlen($message) > 0 )
{
    $timestamp = time();
    $message = utf8_decode($message);
    
    if( !stristr($message, "specialcharemoticon") )  // only encrypt text message
    {
        $message = encryptmessage($message);
    }

    // log message

    $db->exec("INSERT INTO chatpack_log (timestamp, user, avatar, message)
               VALUES ('$timestamp', '$user', '$avatar', '$message')");
    $db->exec("REPLACE INTO chatpack_last_message (timestamp, user, avatar)
               VALUES ('$timestamp', '$user', '$avatar')");
}

function encryptmessage($msg)
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
    $encryptedmessage = openssl_encrypt($msg, "AES-256-CBC", $key, 0, $initvector);
    $encryptedmessage = utf8_decode($encryptedmessage);
    
    return $encryptedmessage;
}

$db->close();

?>