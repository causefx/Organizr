<?php

require_once("user.php");

function check($extension) {
    
    if (extension_loaded($extension)) : 
    
        echo '<div class="panel panel-success">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $extension . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo $extension . ' is loaded and ready to rock-n-roll!  Good 2 Go!';
        echo '</div></div>'; 
    
    else :
    
        echo '<div class="panel panel-danger">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $extension . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo $extension . ' is NOT loaded!  Please install it before proceeding';
        
        if($extension == "PDO_SQLITE") :
            
            echo '<br/> If you are on Windows, please uncomment this line in php.ini: ;extension=php_pdo_sqlite.dll';
        
        endif;
    
        echo '</div></div>'; 
    
    endif;  
    
}

function checkFunction($function) {
    
    if (function_exists($function)) : 
    
        echo '<div class="panel panel-success">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $function . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo $function . ' is loaded and ready to rock-n-roll!  Good 2 Go!';
    
        if($function == "MAIL") :
            
            echo '<br/> **Please make sure you can send email prior to installing as this is needed for password resets**';
        
        endif;
    
        echo '</div></div>'; 
    
    else :
    
        echo '<div class="panel panel-danger">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $function . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo $function . ' is NOT loaded!  Please install it before proceeding'; 
    
        if($function == "MAIL") :
            
            echo '<br/> **If you do not want to use password resets, this is okay not being installed**  EDIT LINE 31 on user.php to "false" [const use_mail = false]';
        
        endif;
    
        echo '</div></div>'; 
    
    endif;  
    
}

function getFilePermission($file) {
        
    if (file_exists($file)) :
    
        $length = strlen(decoct(fileperms($file)))-3;
    
        if($file{strlen($file)-1}=='/') :

            $name = "Folder";

        else :

            $name = "File";

        endif;

        if (is_writable($file)) :

            echo '<div class="panel panel-success">';
            echo '<div class="panel-heading">';
            echo '<h3 class="panel-title">'. $file . '<permissions style="float: right;">Permissions: ' . substr(decoct(fileperms($file)),$length) . '</permissions></h3>';
            echo '</div>';
            echo '<div style="color: gray" class="panel-body">';
            echo $file . ' is writable and ready to rock-n-roll!  Good 2 Go!';
            echo '</div></div>'; 

        else :

            echo '<div class="panel panel-danger">';
            echo '<div class="panel-heading">';
            echo '<h3 class="panel-title">'. $file . '</h3>';
            echo '</div>';
            echo '<div style="color: gray" class="panel-body">';
            echo $file . ' is NOT writable!  Please change the permissions to make it writtable by the PHP User.';
            echo '</div></div>'; 

        endif;
        
    endif;
}

$db = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";
$folder = USER_HOME;

?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title>Requirement Checker</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 

        <link rel="stylesheet" href="js/selects/cs-select.css">
        <link rel="stylesheet" href="js/selects/cs-skin-elastic.css">


        <link rel="stylesheet" href="css/style.css">
        
    </head>

    <body class="gray-bg" style="padding: 0;">

        <div id="main-wrapper" class="main-wrapper">

            <!--Content-->
            <div id="content"  style="margin:0 20px; overflow:hidden">
                
                <h1><center>Check Requirements & Permissions</center></h1>
                
                <?php
                
                check("PDO_SQLITE");
                check("PDO");
                check("curl");
                check("Zip");
                checkFunction("MAIL");
                checkFunction("fopen");

                getFilePermission($db);
                getFilePermission($folder);
                getFilePermission((__DIR__));
                getFilePermission(dirname(__DIR__));
                
                //PHPINFO
                
                echo '<div class="panel panel-success">';
                echo '<div class="panel-heading">';
                echo '<h3 class="panel-title">PHP Info</h3>';
                echo '</div>';
                echo '<div style="color: gray" class="panel-body">';
                echo phpinfo();
                echo '</div></div>';

                ?>

            </div>

        </div>

    </body>

</html>