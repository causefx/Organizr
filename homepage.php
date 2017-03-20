<?php

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

require_once("user.php");
require_once("translate.php");
require_once("functions.php");
$USER = new User("registration_callback");

$dbfile = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";

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

        <title>Settings</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 

        <link rel="stylesheet" href="js/selects/cs-select.css">
        <link rel="stylesheet" href="js/selects/cs-skin-elastic.css">
        <link href="bower_components/iconpick/dist/css/fontawesome-iconpicker.min.css" rel="stylesheet">
        <link rel="stylesheet" href="bower_components/google-material-color/dist/palette.css">
        
        <link rel="stylesheet" href="bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="bower_components/smoke/dist/css/smoke.min.css">

        <script src="js/menu/modernizr.custom.js"></script>
        <script type="text/javascript" src="js/sha1.js"></script>
        <script type="text/javascript" src="js/user.js"></script>
        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">
        <link rel="stylesheet" href="bower_components/DataTables/media/css/jquery.dataTables.css">
        <link rel="stylesheet" href="bower_components/datatables-tabletools/css/dataTables.tableTools.css">
        <link rel="stylesheet" href="bower_components/numbered/jquery.numberedtextarea.css">

        <link rel="stylesheet" href="css/style.css">
        <link href="css/jquery.filer.css" rel="stylesheet">
	    <link href="css/jquery.filer-dragdropbox-theme.css" rel="stylesheet">

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
        <style>
        
            .carousel-image{
                width: 100px !important;
                height: 150px !important;
                border-radius: 3px 0 0 3px;  
            }.carousel-image.album{
                width: 150px !important;
                height: 150px !important;
                border-radius: 3px 0 0 3px;  
            }.carousel-control.album {
                top: 5px !important;
                width: 4% !important;
            }.carousel-control {
                top: 5px !important;
                width: 4% !important;
            }.carousel-caption.album {
                position: absolute;
                right: 4%;
                top: 0px;
                left: 160px;
                z-index: 10;
                bottom: 0px;
                padding-top: 0px;
                color: #fff;
                text-align: left;
            }.carousel-caption {
                position: absolute;
                right: 4%;
                top: 0px;
                left: 110px;
                z-index: 10;
                bottom: 0px;
                padding-top: 0px;
                color: #fff;
                text-align: left;
                padding-bottom: 2px !important;
            }<?php if(CUSTOMCSS == "true") : 
$template_file = "custom.css";
$file_handle = fopen($template_file, "rb");
echo fread($file_handle, filesize($template_file));
fclose($file_handle);
echo "\n";
endif; ?>        
        
        </style>
        
    </head>

    <body class="" style="padding: 0px;">

        <div class="main-wrapper" style="position: initial;">
            
            <div id="content" class="container-fluid">
            
                <div class="row">

                    <?php
                    $plexSize = 0;
                    if(PLEXRECENTMOVIE == "true"){ $plexSize++; }
                    if(PLEXRECENTTV == "true"){ $plexSize++; }
                    if(PLEXRECENTMUSIC == "true"){ $plexSize++; }
                    if($plexSize >= 3){ $plexSize = 4; }elseif($plexSize == 2){ $plexSize = 6; }elseif($plexSize == 1){ $plexSize = 12; }

                    if($plexSize > 0){ echo '<h2 class="text-center">' . $language->translate("RECENTLY_ADDED_TO_PLEX") . '</h2>'; }
                    
                    if(PLEXRECENTMOVIE == "true"){ echo getPlexRecent(PLEXURL, PLEXPORT, "movie", PLEXTOKEN, $plexSize, $language->translate("MOVIES")); }
                    if(PLEXRECENTTV == "true"){ echo getPlexRecent(PLEXURL, PLEXPORT, "season", PLEXTOKEN, $plexSize, $language->translate("TV_SHOWS")); }
                    if(PLEXRECENTMUSIC == "true"){ echo getPlexRecent(PLEXURL, PLEXPORT, "album", PLEXTOKEN, $plexSize, $language->translate("MUSIC")); }
                    ?>

                </div>
                
                <div class="row">
                    <?php
                    if(PLEXPLAYINGNOW == "true"){ echo '<h2 class="text-center">' . $language->translate("PLAYING_NOW_ON_PLEX") . '</h2>'; echo getPlexStreams(PLEXURL, PLEXPORT, PLEXTOKEN, 12); }
                    ?>                                        
                </div>

            </div>    
                
        </div>
        
        <!--Scripts-->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
        <script src="bower_components/Waves/dist/waves.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/slimScroll/jquery.slimscroll.min.js"></script>
        <script src="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>

        <!--Menu-->
        <script src="js/menu/classie.js"></script>
        <script src="bower_components/iconpick/dist/js/fontawesome-iconpicker.js"></script>


        <!--Selects-->
        <script src="js/selects/selectFx.js"></script>
        <script src="js/jscolor.js"></script>
        
        <script src="bower_components/sweetalert/dist/sweetalert.min.js"></script>

        <script src="bower_components/smoke/dist/js/smoke.min.js"></script>
        <script src="bower_components/numbered/jquery.numberedtextarea.js"></script>


        <!--Notification-->
        <script src="js/notifications/notificationFx.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
        <script src="js/jquery.filer.min.js" type="text/javascript"></script>
	    <script src="js/custom.js" type="text/javascript"></script>
	    <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
        
        <!--Data Tables-->
        <script src="bower_components/DataTables/media/js/jquery.dataTables.js"></script>
        <script src="bower_components/datatables.net-responsive/js/dataTables.responsive.js"></script>
        <script src="bower_components/datatables-tabletools/js/dataTables.tableTools.js"></script>
        
        <script>
        
        $( document ).ready(function() {
            
            $(".carousel-caption").mCustomScrollbar({
                theme:"inset-2",
                scrollInertia: 300,
                autoHideScrollbar: true,
                autoExpandScrollbar: true
            });
            
        });
             
        </script>

    </body>

</html>