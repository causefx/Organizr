<?php

function write_ini_file($content, $path) { 
    
    if (!$handle = fopen($path, 'w')) {
        
        return false; 
    
    }
    
    $success = fwrite($handle, $content);
    
    fclose($handle); 
    
    return $success; 

}

if ($_POST['submit'] == "editCSS" ) {

    $text = $_POST["css-show"];
    
    write_ini_file($text, "custom.css");

}

?>

<script>window.top.location = window.top.location.href.split('#')[0];</script>