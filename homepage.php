<?php

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

require_once("user.php");
require_once("functions.php");
use Kryptonit3\Sonarr\Sonarr;
use Kryptonit3\SickRage\SickRage;
$sonarr = new Sonarr(SONARRURL, SONARRKEY);
$radarr = new Sonarr(RADARRURL, RADARRKEY);
$sickrage = new SickRage(SICKRAGEURL, SICKRAGEKEY);
$USER = new User("registration_callback");

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

$startDate = date('Y-m-d',strtotime("-".CALENDARSTARTDAY." days"));
$endDate = date('Y-m-d',strtotime("+".CALENDARENDDAY." days")); 

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
            sort {
                display: none;
            }
            table.fc-list-table {
                table-layout: auto;
            }.tabbable{
                margin-bottom: 0;
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
                overflow: hidden !important;
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
<!-- <button id="numBnt">Numerical</button> -->
                <br/>
                <?php if(($USER->authenticated && $USER->role == "admin") && (NZBGETURL != "" || SABNZBDURL != "" )) : ?>
                <div id="downloadClientRow" class="row">
                    <sort>2</sort>

                    <div class="col-xs-12 col-md-12">
                        
                        <div class="content-box">

                            <div class="tabbable panel with-nav-tabs panel-default">

                                <div class="panel-heading">

                                    <div class="content-tools i-block pull-right">

                                        <a id="getDownloader" class="repeat-btn">

                                            <i class="fa fa-repeat"></i>

                                        </a>

                                        <a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                          
                                            <i class="fa fa-chevron-down"></i>
                                        
                                        </a>
                                        
                                        <ul id="downloaderSeconds" class="dropdown-menu" style="top: 32px !important">

                                            <li data-value="5000"><a>Refresh every 5 seconds</a></li>
                                            <li data-value="10000"><a>Refresh every 10 seconds</a></li>
                                            <li data-value="30000"><a>Refresh every 30 seconds</a></li>
                                            <li data-value="60000"><a>Refresh every 60 seconds</a></li>

                                        </ul>

                                    </div>

                                    <h3 class="pull-left"><?php if(NZBGETURL != ""){ echo "NZBGet "; } if(SABNZBDURL != ""){ echo "SABnzbd "; } ?></h3>

                                    <ul class="nav nav-tabs pull-right">

                                        <li class="active"><a href="#downloadQueue" data-toggle="tab" aria-expanded="true"><?php echo $language->translate("QUEUE");?></a></li>

                                        <li class=""><a href="#downloadHistory" data-toggle="tab" aria-expanded="false"><?php echo $language->translate("HISTORY");?></a></li>

                                    </ul>

                                    <div class="clearfix"></div>

                                </div>

                                <div class="panel-body">

                                    <div class="tab-content">

                                        <div class="tab-pane fade active in" id="downloadQueue">

                                            <div class="table-responsive" style="max-height: 300px">

                                                <table class="table table-striped progress-widget zero-m" style="max-height: 300px">

                                                    <thead>

                                                        <tr>

                                                            <th><?php echo $language->translate("FILE");?></th>
                                                            <th><?php echo $language->translate("STATUS");?></th>
                                                            <th><?php echo $language->translate("CATEGORY");?></th>
                                                            <th><?php echo $language->translate("PROGRESS");?></th>

                                                        </tr>

                                                    </thead>

                                                    <tbody id="downloaderQueue">                               

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                        <div class="tab-pane fade" id="downloadHistory">

                                            <div class="table-responsive" style="max-height: 300px">

                                                <table class="table table-striped progress-widget zero-m" style="max-height: 300px">

                                                    <thead>

                                                        <tr>

                                                            <th>File</th>
                                                            <th>Status</th>
                                                            <th>Category</th>
                                                            <th>Progress</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody id="downloaderHistory">                                      

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>
                            
                        </div>

                    </div>

                </div>
                <?php endif; ?>

                <div id="plexRow" class="row">
                    
                    <sort>3</sort>

                    <?php
                    $plexSize = 0;
                    if(PLEXRECENTMOVIE == "true"){ $plexSize++; }
                    if(PLEXRECENTTV == "true"){ $plexSize++; }
                    if(PLEXRECENTMUSIC == "true"){ $plexSize++; }
                    if(PLEXPLAYINGNOW == "true"){ $plexSize++; }
                    if($plexSize >= 4){ $plexSize = 3; }elseif($plexSize == 3){ $plexSize = 4; }elseif($plexSize == 2){ $plexSize = 6; }elseif($plexSize == 1){ $plexSize = 12; }
                    
                    if(PLEXRECENTMOVIE == "true"){ echo getPlexRecent("movie", $plexSize); }
                    if(PLEXRECENTTV == "true"){ echo getPlexRecent("season", $plexSize); }
                    if(PLEXRECENTMUSIC == "true"){ echo getPlexRecent("album", $plexSize); }
                    if(PLEXPLAYINGNOW == "true"){ echo getPlexStreams($plexSize); }
                    ?>

                </div>
                
                <div id="embyRow" class="row">
                    
                    <sort>3</sort>

                    <?php
                    $embySize = 0;
                    if(EMBYRECENTMOVIE == "true"){ $embySize++; }
                    if(EMBYRECENTTV == "true"){ $embySize++; }
                    if(EMBYRECENTMUSIC == "true"){ $embySize++; }
                    if(EMBYPLAYINGNOW == "true"){ $embySize++; }
                    if($embySize >= 4){ $embySize = 3; }elseif($embySize == 3){ $embySize = 4; }elseif($embySize == 2){ $embySize = 6; }elseif($embySize == 1){ $embySize = 12; }
                    
                    if(EMBYRECENTMOVIE == "true"){ echo getEmbyRecent("movie", $embySize); }
                    if(EMBYRECENTTV == "true"){ echo getEmbyRecent("season", $embySize); }
                    if(EMBYRECENTMUSIC == "true"){ echo getEmbyRecent("album", $embySize); }
                    if(EMBYPLAYINGNOW == "true"){ echo getEmbyStreams($embySize); }
                    ?>

                </div>
		    
                <?php if(SONARRURL != "" || RADARRURL != "" || HEADPHONESURL != "" || SICKRAGEURL != "") : ?>
                <div id="calendarLegendRow" class="row" style="padding: 0 0 10px 0;">
                    
                    <sort>1</sort>
                    
                    <div class="col-lg-12 content-form form-inline">
                        
                        <div class="form-group">
                        
                            <select class="form-control" id="imagetype_selector" style="width: auto !important; display: inline-block">

                                <option value="all">View All</option>
                                <?php if(RADARRURL != ""){ echo '<option value="film">Movies</option>'; }?>
                                <?php if(SONARRURL != "" || SICKRAGEURL != ""){ echo '<option value="tv">TV Shows</option>'; }?>
                                <?php if(HEADPHONESURL != ""){ echo '<option value="music">Music</option>'; }?>

                            </select>

                            <span class="label label-primary well-sm">Available</span>
                            <span class="label label-danger well-sm">Unavailable</span>
                            <span class="label indigo-bg well-sm">Unreleased</span>
                            <span class="label light-blue-bg well-sm">Premier</span>
                            
                        </div>
                    
                    </div>
                    
                </div>
                
                <div id="calendarRow" class="row">
                    
                    <sort>1</sort>
        
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
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>
        
        <script src="bower_components/fullcalendar/dist/fullcalendar.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
	    <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
        
        <script>
        
        $( document ).ready(function() {
            
             $('.repeat-btn').click(function(){
                var refreshBox = $(this).closest('div.content-box');
                $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(300);

                setTimeout(function(){
                  var refreshPreloader = refreshBox.find('.refresh-preloader'),
                      deletedRefreshBox = refreshPreloader.fadeOut(300, function(){
                      refreshPreloader.remove();
                  });
                },1500);

              });

            $("body").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                scrollspeed: 30,
                mousescrollstep: 60
            });
            
            $(".table-responsive").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                scrollspeed: 30,
                mousescrollstep: 60
            });
            
            /*$(".carousel-caption").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                scrollspeed: 30,
                mousescrollstep: 60
            });*/
            
            // check if browser support HTML5 local storage
            function localStorageSupport() {
                return (('localStorage' in window) && window['localStorage'] !== null)
            }
            
            <?php if(($USER->authenticated && $USER->role == "admin") && (NZBGETURL != "" || SABNZBDURL != "")){ ?>
            
            var downloaderSeconds = localStorage.getItem("downloaderSeconds");
            var myInterval = undefined;
            $("ul").find("[data-value='" + downloaderSeconds + "']").addClass("active");
            
            if(  downloaderSeconds === null ) {
                localStorage.setItem("downloaderSeconds",'60000');
                var downloaderSeconds = "60000";
            }
            
            $('#downloaderSeconds li').click(function() {
                
                $('#downloaderSeconds li').removeClass("active");
                $(this).addClass("active");

                var newDownloaderSeconds = $(this).attr('data-value');
                console.log('New Time is ' + newDownloaderSeconds + ' Old Time was ' + downloaderSeconds);
                
                if (localStorageSupport) {
                    localStorage.setItem("downloaderSeconds",newDownloaderSeconds);
                }
                
                if(typeof myInterval != 'undefined'){ clearInterval(myInterval); }
                refreshDownloader(newDownloaderSeconds);
                
            });
            
            <?php } ?>
            
            
            <?php if(($USER->authenticated && $USER->role == "admin") && NZBGETURL != ""){ ?>
            
            $("#downloaderHistory").load("downloader.php?downloader=nzbget&list=history");
            $("#downloaderQueue").load("downloader.php?downloader=nzbget&list=listgroups");
            
            refreshDownloader = function(secs){
                myInterval = setInterval(function(){
                    $("#downloaderHistory").load("downloader.php?downloader=nzbget&list=history");
                    $("#downloaderQueue").load("downloader.php?downloader=nzbget&list=listgroups");
                }, secs);                
            }

            refreshDownloader(downloaderSeconds);

            $("#getDownloader").click(function(){
                $("#downloaderHistory").load("downloader.php?downloader=nzbget&list=history");
                $("#downloaderQueue").load("downloader.php?downloader=nzbget&list=listgroups");
                console.log('completed'); 
            });

            <?php } ?>
            
            <?php if(($USER->authenticated && $USER->role == "admin") && SABNZBDURL != ""){ ?>
            
            $("#downloaderHistory").load("downloader.php?downloader=sabnzbd&list=history");
            $("#downloaderQueue").load("downloader.php?downloader=sabnzbd&list=queue");
            
            refreshDownloader = function(secs){
                myInterval = setInterval(function(){
                    $("#downloaderHistory").load("downloader.php?downloader=sabnzbd&list=history");
                    $("#downloaderQueue").load("downloader.php?downloader=sabnzbd&list=queue");
                }, secs);                
            }

            refreshDownloader(downloaderSeconds);

            $("#getDownloader").click(function(){
                $("#downloaderHistory").load("downloader.php?downloader=sabnzbd&list=history");
                $("#downloaderQueue").load("downloader.php?downloader=sabnzbd&list=queue");
                console.log('completed'); 
            });

            <?php } ?>
                        
        });
             
        </script>
        <?php if(SONARRURL != "" || RADARRURL != "" || HEADPHONESURL != "" || SICKRAGEURL != "") : ?>
        <script>
            
            $(function () {

                var date = new Date();
                var d = date.getDate();
                var m = date.getMonth();
                var y = date.getFullYear();

                $('#calendar').fullCalendar({
                    
                    eventLimit: false, 
                    firstDay: <?php echo CALENDARSTART;?>,
                  
                    height: "auto",
                    defaultView: '<?php echo CALENDARVIEW;?>',
                
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
<?php if(SICKRAGEURL != ""){ echo getSickrageCalendarWanted($sickrage->future()); echo getSickrageCalendarHistory($sickrage->history("100","downloaded")); } ?>
<?php if(SONARRURL != ""){ echo getSonarrCalendar($sonarr->getCalendar($startDate, $endDate)); } ?>
<?php if(RADARRURL != ""){ echo getRadarrCalendar($radarr->getCalendar($startDate, $endDate)); } ?>                 
<?php if(HEADPHONESURL != ""){ echo getHeadphonesCalendar(HEADPHONESURL, HEADPHONESKEY, "getHistory"); echo getHeadphonesCalendar(HEADPHONESURL, HEADPHONESKEY, "getWanted"); } ?>                                
                    ],
                                            
                    eventRender: function eventRender( event, element, view ) {
                        return ['all', event.imagetype].indexOf($('#imagetype_selector').val()) >= 0
                    },

                    editable: false,
                    droppable: false,

                });
            
            });
            
            $('#imagetype_selector').on('change',function(){
                $('#calendar').fullCalendar('rerenderEvents');
            })
            
            var $divs = $("div.row");

            $('#numBnt').on('click', function () {
                var numericallyOrderedDivs = $divs.sort(function (a, b) {
                    return $(a).find("sort").text() > $(b).find("sort").text();
                });
                $("#content").html(numericallyOrderedDivs);
            });
        
        </script>
        <?php endif; ?>

    </body>

</html>
