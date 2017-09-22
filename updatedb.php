<?php 
$data = false;
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

require_once("user.php");
$USER = new User("registration_callback");

function checkDatabase($type, $table, $check) {
    
    if ($type == "PRAGMA") :
    
        $query = 'PRAGMA table_info(' . $table . ')';
    
    elseif ($type == "SELECT") :
    
        $query = 'SELECT name FROM sqlite_master WHERE type="table" AND name="' . $table . '"';
    
    endif;
    
    $dbfile = DATABASE_LOCATION.'users.db';
    $file_db = new PDO("sqlite:" . $dbfile);
    $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $runQuery = $file_db->query($query);
    $buildArray = array();
    
    foreach($runQuery as $row) :
    
        array_push($buildArray, $row['name']);
    
    endforeach;
    
    $file_db = null;
    
    if (in_array($check, $buildArray)) :

            $found = '<div class="panel panel-success"><div class="panel-heading"><h3 class="panel-title">'. $check . '</h3></div><div style="color: gray" class="panel-body">' . $check . ' was found in ' . $table . ' and is Good 2 Go!</div></div>'; 

        elseif (!in_array($check, $buildArray)) :
    
            $found = '<div class="panel panel-danger"><div class="panel-heading"><h3 class="panel-title">'. $check . '</h3></div><div style="color: gray" class="panel-body">' . $check . ' was not found in ' . $table . ' and is being added now!</div></div>'; 
            
            if ($type == "PRAGMA") :
                $file_db = new PDO("sqlite:" . $dbfile);
                $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $count = $file_db->exec("ALTER TABLE `$table` ADD COLUMN `$check` TEXT");
                $file_db = null;
            endif;
    
    endif;
    
    echo $found;
    
}
?>

<html lang="en" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title>Organizr Upgrade</title>

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
                
                <h1><center>Database Upgrade</center></h1>
                
                <h5 id="countdown"></h5>
                
                <?php
                
                //checkDatabase('SELECT', 'options', 'options');
                //checkDatabase('SELECT', 'tabs', 'tabs');
                //checkDatabase('PRAGMA', 'options', 'loading');
                //checkDatabase('PRAGMA', 'options', 'hovertext');

                ?>

            </div>

        </div>
        
        <script>
        
            (function countdown(remaining) {
                if(remaining === 0)
                    window.top.location = window.top.location.href.split('#')[0];
                document.getElementById('countdown').innerHTML = "<center>Page will refresh in <strong>" + remaining + "</strong> seconds [You may close this at anytime]</center>";
                setTimeout(function(){ countdown(remaining - 1); }, 1000);
            })(10);
        
        </script>

    </body>

</html>
