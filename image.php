<?php

require_once("user.php");

$image_url = $_GET['img'];

$plexAddress = PLEXURL.':'.PLEXPORT;

$addressPosition = strpos($image_url, $plexAddress);

if($addressPosition !== false && $addressPosition == 0) {
    
	$image_src = $image_url . '?X-Plex-Token=' . PLEXTOKEN;
    
	header('Content-type: image/jpeg');
    
	readfile($image_src);
    
} else {
    
    echo "Bad Plex Image Url";	
    
}
