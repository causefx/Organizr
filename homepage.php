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

        <title><?=$title;?> Homepage</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <!--<link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css"> -->
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 
        
        <script src="js/menu/modernizr.custom.js"></script>

        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">

        <link rel="stylesheet" href="bower_components/fullcalendar/dist/fullcalendar.css">

        <link rel="stylesheet" href="css/style.css">

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
        <style>
            table.fc-list-table {
                table-layout: auto;
            }.fc-day-grid-event .fc-content {
                white-space: normal;
            }.fc-list-item {
                table-layout: auto;
                position: inherit;
                margin: 10px;
                border-radius: 4px;
                padding: 0 5px 0 5px;
                color: #fff !important;
            }.fc-calendar .fc-toolbar {
                background: <?=$topbar;?>;
                color: <?=$topbartext;?>;
                border-radius: 5px 5px 0 0;
                padding: 15px;
            }.fc-calendar .fc-toolbar .fc-right {
                bottom: 0px;
                right: 20px;
            }.fc-calendar .fc-toolbar .fc-right button {
                color: <?=$topbartext;?>;
            }.fc-calendar .fc-toolbar .fc-prev-button, .fc-calendar .fc-toolbar .fc-next-button {
                color: <?=$topbartext;?>;
            }.carousel-image{
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

    <body class="scroller-body" style="padding: 0px;">

        <div class="main-wrapper" style="position: initial;">
            
            <div id="content" class="container-fluid">
                
                <br>
                <?php if(NZBGETURL != "") : ?>
                <div class="row">

                    <div class="col-md-12">

                        <div class="tabbable panel with-nav-tabs panel-default">

                            <div class="panel-heading">

                                <h3 class="pull-left">NZBGet</h3>

                                <ul class="nav nav-tabs pull-right">

                                    <li class="active"><a href="#downloadQueue" data-toggle="tab" aria-expanded="true"><?php echo $language->translate("QUEUE");?></a></li>

                                    <li class=""><a href="#downloadHistory" data-toggle="tab" aria-expanded="false"><?php echo $language->translate("HISTORY");?></a></li>

                                </ul>

                                <div class="clearfix"></div>

                            </div>

                            <div class="panel-body">

                                <div class="tab-content">

                                    <div class="tab-pane fade active in" id="downloadQueue">

                                            <div class="table-responsive">

                                                <table class="table table-striped progress-widget zero-m">

                                                    <thead>

                                                        <tr>

                                                            <th><?php echo $language->translate("FILE");?></th>
                                                            <th><?php echo $language->translate("STATUS");?></th>
                                                            <th><?php echo $language->translate("CATEGORY");?></th>
                                                            <th><?php echo $language->translate("PROGRESS");?></th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>
                <?php echo nzbgetConnect(NZBGETURL, NZBGETPORT, NZBGETUSERNAME, NZBGETPASSWORD, "listgroups");?>                                   

                                                    </tbody>

                                                </table>

                                            </div>

                                    </div>

                                    <div class="tab-pane fade" id="downloadHistory">

                                        <div class="table-responsive">

                                            <table class="table table-striped progress-widget zero-m">

                                                <thead>

                                                    <tr>

                                                        <th>File</th>
                                                        <th>Status</th>
                                                        <th>Category</th>
                                                        <th>Progress</th>

                                                    </tr>

                                                </thead>

                                                <tbody>
            <?php echo nzbgetConnect(NZBGETURL, NZBGETPORT, NZBGETUSERNAME, NZBGETPASSWORD, "history");?>                                        

                                                </tbody>

                                            </table>

                                            </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
                <?php endif; ?>

                <div class="row">

                    <?php
                    $plexSize = 0;
                    if(PLEXRECENTMOVIE == "true"){ $plexSize++; }
                    if(PLEXRECENTTV == "true"){ $plexSize++; }
                    if(PLEXRECENTMUSIC == "true"){ $plexSize++; }
                    if(PLEXPLAYINGNOW == "true"){ $plexSize++; }
                    if($plexSize >= 4){ $plexSize = 3; }elseif($plexSize == 3){ $plexSize = 4; }elseif($plexSize == 2){ $plexSize = 6; }elseif($plexSize == 1){ $plexSize = 12; }
                    
                    if(PLEXRECENTMOVIE == "true"){ echo getPlexRecent(PLEXURL, PLEXPORT, "movie", PLEXTOKEN, $plexSize, $language->translate("MOVIES")); }
                    if(PLEXRECENTTV == "true"){ echo getPlexRecent(PLEXURL, PLEXPORT, "season", PLEXTOKEN, $plexSize, $language->translate("TV_SHOWS")); }
                    if(PLEXRECENTMUSIC == "true"){ echo getPlexRecent(PLEXURL, PLEXPORT, "album", PLEXTOKEN, $plexSize, $language->translate("MUSIC")); }
                    if(PLEXPLAYINGNOW == "true"){ echo getPlexStreams(PLEXURL, PLEXPORT, PLEXTOKEN, $plexSize, $language->translate("PLAYING_NOW_ON_PLEX")); }
                    ?>

                </div>
                
                <?php if(SONARRURL != "" || RADARRURL != "") : ?>
                <div class="row" style="padding: 0 0 10px 0;">
                    
                    <div class="col-lg-4">
                    
                        <span class="label progress-bar-success progress-bar-striped well-sm"><span class="fc-image"><i class="fa fa-film"></i></span> Available</span>
                        <span class="label progress-bar-danger progress-bar-striped well-sm"><span class="fc-image"><i class="fa fa-film"></i></span> Unavailable</span>
                        <span class="label label-primary well-sm"><span class="fc-image"><i class="fa fa-tv"></i></span> Available</span>
                        <span class="label label-danger well-sm"><span class="fc-image"><i class="fa fa-tv"></i></span> Unavailable</span>
                    
                    </div>
                    
                </div>
                
                <div class="row">
        
                    <div class="col-lg-12">
                    
                        <div id="calendar" class="fc-calendar box-shadow fc fc-ltr fc-unthemed"></div>
                                        
                    </div>
                                        
                </div>
                <?php endif; ?>

            </div>    
                
        </div>
        
        <!--Scripts-->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/slimScroll/jquery.slimscroll.min.js"></script>
        <script src="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>
        
        <script src="bower_components/fullcalendar/dist/fullcalendar.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
	    <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>

        
        <script>
        
        $( document ).ready(function() {
            
            $(".carousel-caption").mCustomScrollbar({
                theme:"inset-2",
                scrollInertia: 300,
                autoHideScrollbar: true,
                autoExpandScrollbar: true
            });
            
            $(".scroller-body").mCustomScrollbar({
                theme:"inset-3",
                scrollInertia: 300,
                autoHideScrollbar: true,
                autoExpandScrollbar: true
            });
            
            $("fc-scroller").mCustomScrollbar({
                theme:"inset-3",
                scrollInertia: 300,
                autoHideScrollbar: true,
                autoExpandScrollbar: true
            });
            
        });
             
        </script>
        <?php if(SONARRURL != "" || RADARRURL != "") : ?>
        <script>
            $(function () {

              /* initialize the calendar */
              var date = new Date();
              var d = date.getDate();
              var m = date.getMonth();
              var y = date.getFullYear();
              $('#calendar').fullCalendar({
                  eventLimit: false, 
                  height: "auto",
                  //defaultDate: '2017-03-21',
			      defaultView: 'basicWeek',
                header: {
                  left: 'prev,next,',
                  center: 'title',
                  right: 'today, month, basicDay,basicWeek,'
                },
                views: {
                    basicDay: { buttonText: '<?php echo $language->translate("DAY");?>', eventLimit: false },
                    basicWeek: { buttonText: '<?php echo $language->translate("WEEK");?>', eventLimit: false },
                    month: { buttonText: '<?php echo $language->translate("MONTH");?>', eventLimit: false },
                    today: { buttonText: '<?php echo $language->translate("TODAY");?>' },
                },
                events: [

<?php if(SONARRURL != ""){ echo getSonarrCalendar(SONARRURL, SONARRPORT,SONARRKEY); } ?>
<?php if(RADARRURL != ""){ echo getRadarrCalendar(RADARRURL, RADARRPORT,RADARRKEY); } ?>                    

                  ],

                editable: false,
                droppable: false,

              });
            });
        </script>
        <?php endif; ?>

    </body>

</html>
