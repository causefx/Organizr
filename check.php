<?php
if (file_exists('config/config.php')) {
    require_once("user.php");
    $db = DATABASE_LOCATION.'users.db';
    $folder = USER_HOME;
	$USER = new User("registration_callback");
	qualifyUser("admin", true);
}

function check($extension) {
    if (extension_loaded($extension)) {
        echo '<div class="col-lg-2">';
        echo '<div class="panel panel-success">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $extension . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo 'Installed';
        echo '</div></div></div>';
    }else{
        echo '<div class="col-lg-2">';
        echo '<div class="panel panel-danger">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $extension . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo 'Missing';
        echo '</div></div></div>';
    }
}

function checkFunction($function) {
    if (function_exists($function)) {
        echo '<div class="col-lg-2">';
        echo '<div class="panel panel-success">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $function . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo 'Installed';
        echo '</div></div></div>';
    }else{
        echo '<div class="col-lg-2">';
        echo '<div class="panel panel-danger">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title">'. $function . '</h3>';
        echo '</div>';
        echo '<div style="color: gray" class="panel-body">';
        echo 'Missing';
        echo '</div></div></div>';
    }
}

function getFilePermission($file) {
    if (file_exists($file)) {
        $length = strlen(decoct(fileperms($file)))-3;
        if($file{strlen($file)-1}=='/') {
            $name = "Folder";
        }else{
            $name = "File";
        }
        if (is_writable($file)) {
            echo '<div class="col-lg-6">';
            echo '<div class="panel panel-success">';
            echo '<div class="panel-heading">';
            echo '<h3 class="panel-title">'. $file . '<permissions style="float: right;">Permissions: ' . substr(decoct(fileperms($file)),$length) . '</permissions></h3>';
            echo '</div>';
            echo '<div style="color: gray" class="panel-body">';
            echo $file . ' is writable!';
            echo '</div></div></div>';
        }else{
            echo '<div class="col-lg-6">';
            echo '<div class="panel panel-danger">';
            echo '<div class="panel-heading">';
            echo '<h3 class="panel-title">'. $file . '</h3>';
            echo '</div>';
            echo '<div style="color: gray" class="panel-body">';
            echo $file . ' is NOT writable!  Please change the permissions to make it writtable by the PHP User.';
            echo '</div></div></div>';
        }
    }
}
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
        <link rel="stylesheet" href="css/style.css">
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
		<script src="bower_components/slimScroll/jquery.slimscroll.min.js"></script>
    </head>
    <body id="body-check" class="gray-bg" style="padding: 0;">
        <div id="main-wrapper" class="main-wrapper">
            <!--Content-->
            <div id="content"  style="margin:0 20px; overflow:hidden">
                <h1><center>Check Requirements & Permissions</center></h1>
                <div class="row">
                <?php
                check("PDO_SQLITE");
                check("PDO");
                check("SQLITE3");
                check("Zip");
                check("cURL");
                check("openssl");
                check("session");
                check("simplexml");
                check("json");
                checkFunction("hash");
                checkFunction("fopen");
                checkFunction("fsockopen");
				checkFunction("ob_start");
				checkFunction("ob_get_contents");
				checkFunction("ob_end_flush");
				checkFunction("fwrite");
				checkFunction("fclose");
				checkFunction("readfile");
                ?>
                </div>
                <div class="row">
                <?php
                @getFilePermission($db);
                @getFilePermission($folder);
                getFilePermission((__DIR__));
                getFilePermission(dirname(__DIR__));
                echo '</div>';
                //PHPINFO
                echo '<div class="panel panel-success">';
                echo '<div class="panel-heading">';
                echo '<h3 class="panel-title">PHP Info</h3>';
                echo '</div>';
                echo '<div style="color: black" class="panel-body">';
                echo phpinfo();
                echo '</div></div>';
                ?>
            </div>
        </div>
        <script>
        $("body").niceScroll({
            railpadding: {top:0,right:10,left:0,bottom:0},
            scrollspeed: 30,
            mousescrollstep: 60
        });
        </script>
    </body>
</html>
