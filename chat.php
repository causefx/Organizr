<?php

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

require_once("user.php");
require_once("functions.php");

$USER = new User("registration_callback");
qualifyUser(CHATAUTH, true);
if(CHAT !== "true"){ die("chat not enabled");}
$dbfile = DATABASE_LOCATION.'users.db';

$file_db = new PDO("sqlite:" . $dbfile);
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dbOptions = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="options"');

$hasOptions = "No";

foreach($dbOptions as $row) :

    if (in_array("options", $row)) :
        $hasOptions = "Yes";
    endif;

endforeach;

if($hasOptions == "No") :

    $title = "Organizr";
    $topbar = "#333333"; 
    $topbartext = "#66D9EF";
    $bottombar = "#333333";
    $sidebar = "#393939";
    $hoverbg = "#AD80FD";
    $activetabBG = "#F92671";
    $activetabicon = "#FFFFFF";
    $activetabtext = "#FFFFFF";
    $inactiveicon = "#66D9EF";
    $inactivetext = "#66D9EF";
    $loading = "#66D9EF";
    $hovertext = "#000000";

endif;

if($hasOptions == "Yes") :

    $resulto = $file_db->query('SELECT * FROM options'); 
    foreach($resulto as $row) : 

        $title = isset($row['title']) ? $row['title'] : "Organizr";
        $topbartext = isset($row['topbartext']) ? $row['topbartext'] : "#66D9EF";
        $topbar = isset($row['topbar']) ? $row['topbar'] : "#333333";
        $bottombar = isset($row['bottombar']) ? $row['bottombar'] : "#333333";
        $sidebar = isset($row['sidebar']) ? $row['sidebar'] : "#393939";
        $hoverbg = isset($row['hoverbg']) ? $row['hoverbg'] : "#AD80FD";
        $activetabBG = isset($row['activetabBG']) ? $row['activetabBG'] : "#F92671";
        $activetabicon = isset($row['activetabicon']) ? $row['activetabicon'] : "#FFFFFF";
        $activetabtext = isset($row['activetabtext']) ? $row['activetabtext'] : "#FFFFFF";
        $inactiveicon = isset($row['inactiveicon']) ? $row['inactiveicon'] : "#66D9EF";
        $inactivetext = isset($row['inactivetext']) ? $row['inactivetext'] : "#66D9EF";
        $loading = isset($row['loading']) ? $row['loading'] : "#66D9EF";
        $hovertext = isset($row['hovertext']) ? $row['hovertext'] : "#000000";

    endforeach;

endif;


?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title><?=$title;?> Chat</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 
        <script src="js/menu/modernizr.custom.js"></script>

        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">

        <link rel="stylesheet" href="css/style.css?v=<?php echo INSTALLEDVERSION; ?>">

        <!--Scripts-->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/slimScroll/jquery.slimscroll.min.js"></script>
        <script src="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>
        <script src="bower_components/fullcalendar/dist/fullcalendar.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
	    <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
		
		<!--Other-->
		<script src="js/ajax.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
        <script src="chatjs.php" defer="true"></script>
		
        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        <style>
            .offline{
                -webkit-filter: grayscale; /*sepia, hue-rotate, invert....*/
                -webkit-filter: brightness(25%);
            }
            <?php customCSS(); ?>      
        </style>
    </head>

    <body id="chat" class="scroller-body" style="padding: 0px;">
        
        <!-- D A T A B A S E -->
        
        <?php
        
            $dbcreated = false;
        
            if (!extension_loaded("SQLITE3")){ 

                echo '<div class="row" style="margin: 0"><div class="panel panel-danger" style="margin: 10px";>';
                echo '<div class="panel-heading">';
                echo '<h3 class="panel-title">SQLITE3</h3>';
                echo '</div>';
                echo '<div style="color: gray" class="panel-body">';
				echo getError(getOS(),'sqlite3');

                echo '</div></div></div>';
                die();

            }  
        
            if( $db = new SQLite3("chatpack.db") ){
                if( $db->busyTimeout(5000) ){
                    if( $db->exec("PRAGMA journal_mode = wal;") ) {
                        $logtable = "CREATE TABLE IF NOT EXISTS chatpack_log
                                     (id INTEGER PRIMARY KEY,
                                     timestamp INTEGER NOT NULL,
                                     user TEXT NOT NULL,
                                     avatar TEXT NOT NULL,
                                     message TEXT NOT NULL,
                                     liked INTEGER DEFAULT 0)";
                        if( $db->exec($logtable) ){
                            $usertable = "CREATE TABLE IF NOT EXISTS chatpack_typing
                                          (id INTEGER PRIMARY KEY,
                                          timestamp INTEGER NOT NULL,
                                          user TEXT NOT NULL)";
                            
                            $onlinetable = "CREATE TABLE IF NOT EXISTS chatpack_last_message
                                          (
                                          user TEXT PRIMARY KEY NOT NULL,
                                          timestamp INTEGER NOT NULL,
                                          avatar TEXT NOT NULL)";

                            if( $db->exec($usertable) && $db->exec($onlinetable) ){
                                $dbcreated = true;
                            }else{
                                errormessage("creating database table for typing");
                            }
                        }else{
                            errormessage("creating database table for messages");
                        }
                        if( !$db->close() ){
                            errormessage("closing database connection");
                        }
                    }else{
                        errormessage("setting journal mode");
                    }
                }else{
                    errormessage("setting busy timeout");
                }
            }else{
                errormessage("using SQLite");
            }
        
            if( $dbcreated ){?>
        
        <div class="main-wrapper" style="position: initial;">
            <div id="content" class="container-fluid">
                <br>
                <div class="row">
                    <div class="col-lg-10">
                        <div class="content-box big-box chat gray-bg">
                            <div class="box" style="overflow: hidden; width: auto; height: 550px;">
                                <div id="intro">
                                    <center><img class="logo" alt="logo" src="images/organizr-logo-h.png">
                                    <br><br>start chatting...</center>
                                </div>
                                <ul id="messages" class="chat-double chat-container"></ul>
                                <ul class="chat-double chat-container" style="padding: 0px;"><li id="istyping"></li></ul>
                            </div>
                            <br/>
                            <input id="message" autofocus type="text" class="form-control gray-bg" placeholder="Enter your text" autocomplete="off"/>
                            <audio id="tabalert" preload="auto">
                                <source src="chat/audio/newmessage.mp3" type="audio/mpeg">
                            </audio>

                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="content-box">
                            <div class="content-title big-box i-block gray-bg">
                                <h4 class="zero-m">Online</h4>
                            </div>
                            <div class="clearfix"></div>
                            <div id="onlineusers" class="big-box"></div>
                        </div>
                    </div>
                </div>
            </div>    
        </div>
        
        <?php } ?>
    </body>

    <script>
        $(".box").niceScroll({
            railpadding: {top:0,right:0,left:0,bottom:0},
            scrollspeed: 30,
            mousescrollstep: 60
        });
        $("#onlineusers").niceScroll({
            railpadding: {top:0,right:0,left:0,bottom:0},
            scrollspeed: 30,
            mousescrollstep: 60
        });
    </script>

</html>