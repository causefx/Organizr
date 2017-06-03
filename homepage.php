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

// Check if connection to homepage is allowed
qualifyUser(HOMEPAGEAUTHNEEDED, true);

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
        <link rel="stylesheet" href="<?=$baseURL;?>bower_components/mdi/css/materialdesignicons.min.css?v=<?php echo INSTALLEDVERSION; ?>">

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
            } @media screen and (max-width: 576px) {
				.nzbtable {
					padding-left: 5px !important;
					padding-right: 2px !important;
					font-size: 10px !important;
					word-break: break-word !important;
				}
				.nzbtable-file-row {
					padding-left: 5px !important;
					padding-right: 2px !important;
					font-size: 10px !important;
					white-space: normal !important;
					word-break: break-all !important;
					width: 0% !important;
				}
            } .nzbtable-file-row {
				white-space: normal !important;
				word-break: break-all !important;
				width: 0% !important;
			}.nzbtable-row {
				white-space: normal !important;
				width: 0% !important;
				font-size: 12px; !important;
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

				 <?php if (qualifyUser(HOMEPAGECUSTOMHTML1AUTH) && HOMEPAGECUSTOMHTML1) { ?>
				<div>
					<?php echo HOMEPAGECUSTOMHTML1; ?>
				</div>
                <?php } ?>
                <?php if((NZBGETURL != "" && qualifyUser(NZBGETHOMEAUTH)) || (SABNZBDURL != "" && qualifyUser(SABNZBDHOMEAUTH))) { ?>
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
										<!-- Lets Move This To Homepage Settings
                                        <ul id="downloaderSeconds" class="dropdown-menu" style="top: 32px !important">
                                            <li data-value="5000"><a>Refresh every 5 seconds</a></li>
                                            <li data-value="10000"><a>Refresh every 10 seconds</a></li>
                                            <li data-value="30000"><a>Refresh every 30 seconds</a></li>
                                            <li data-value="60000"><a>Refresh every 60 seconds</a></li>
                                        </ul>
										-->
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
                                                            <th class="col-xs-7 nzbtable-file-row"><?php echo $language->translate("FILE");?></th>
                                                            <th class="col-xs-2 nzbtable"><?php echo $language->translate("STATUS");?></th>
                                                            <th class="col-xs-1 nzbtable"><?php echo $language->translate("CATEGORY");?></th>
                                                            <th class="col-xs-2 nzbtable"><?php echo $language->translate("PROGRESS");?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="dl-queue sabnzbd"></tbody>
                                                    <tbody class="dl-queue nzbget"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="downloadHistory">
                                            <div class="table-responsive" style="max-height: 300px">
                                                <table class="table table-striped progress-widget zero-m" style="max-height: 300px">
                                                    <thead>
                                                        <tr>
                                                            <th class="col-xs-7 nzbtable-file-row"><?php echo $language->translate("FILE");?></th>
                                                            <th class="col-xs-2 nzbtable"><?php echo $language->translate("STATUS");?></th>
                                                            <th class="col-xs-1 nzbtable"><?php echo $language->translate("CATEGORY");?></th>
                                                            <th class="col-xs-2 nzbtable"><?php echo $language->translate("PROGRESS");?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="dl-history sabnzbd"></tbody>
                                                    <tbody class="dl-history nzbget"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
				<?php if (qualifyUser(PLEXHOMEAUTH) && PLEXTOKEN) { ?>
                <div id="plexRow" class="row">
                    <sort>3</sort>

                    <?php
                    $plexSize = (PLEXRECENTMOVIE == "true") + (PLEXRECENTTV == "true") + (PLEXRECENTMUSIC == "true") + (PLEXPLAYINGNOW == "true");
                    if(PLEXRECENTMOVIE == "true"){ echo getPlexRecent("movie", 12/$plexSize); }
                    if(PLEXRECENTTV == "true"){ echo getPlexRecent("season", 12/$plexSize); }
                    if(PLEXRECENTMUSIC == "true"){ echo getPlexRecent("album", 12/$plexSize); }
                    if(PLEXPLAYINGNOW == "true"){ echo getPlexStreams(12/$plexSize); }
                    ?>

                </div>
				<?php } ?>
				<?php if (qualifyUser(EMBYHOMEAUTH) && EMBYTOKEN) { ?>
                <div id="embyRow" class="row">
                    <sort>3</sort>

                    <?php
                    $embySize = (EMBYRECENTMOVIE == "true") + (EMBYRECENTTV == "true") + (EMBYRECENTMUSIC == "true") + (EMBYPLAYINGNOW == "true");
                    if(EMBYRECENTMOVIE == "true"){ echo getEmbyRecent("movie", 12/$embySize); }
                    if(EMBYRECENTTV == "true"){ echo getEmbyRecent("season", 12/$embySize); }
                    if(EMBYRECENTMUSIC == "true"){ echo getEmbyRecent("album", 12/$embySize); }
                    if(EMBYPLAYINGNOW == "true"){ echo getEmbyStreams(12/$embySize); }
                    ?>

                </div>
				<?php } ?>
                <?php if ((SONARRURL != "" && qualifyUser(SONARRHOMEAUTH)) || (RADARRURL != "" && qualifyUser(RADARRHOMEAUTH)) || (HEADPHONESURL != "" && qualifyUser(HEADPHONESHOMEAUTH)) || (SICKRAGEURL != "" && qualifyUser(SICKRAGEHOMEAUTH))) { ?>
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
                <?php } ?>
            </div>    
        </div>
        <script>
		function localStorageSupport() {
			return (('localStorage' in window) && window['localStorage'] !== null)
		}
		
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
			
            <?php if((NZBGETURL != "" && qualifyUser(NZBGETHOMEAUTH)) || (SABNZBDURL != "" && qualifyUser(SABNZBDHOMEAUTH))){ ?>
			var queueRefresh = 30000;
			var historyRefresh = 120000; // This really doesn't need to happen that often
			
			var queueLoad = function() {
				<?php if(SABNZBDURL != "") { echo '$("tbody.dl-queue.sabnzbd").load("ajax.php?a=sabnzbd-update&list=queue");'; } ?>
				<?php if(NZBGETURL != "") { echo '$("tbody.dl-queue.nzbget").load("ajax.php?a=nzbget-update&list=listgroups");'; } ?>
			};
			
			var historyLoad = function() {
				<?php if(SABNZBDURL != "") { echo '$("tbody.dl-history.sabnzbd").load("ajax.php?a=sabnzbd-update&list=history");'; } ?>
				<?php if(NZBGETURL != "") { echo '$("tbody.dl-history.nzbget").load("ajax.php?a=nzbget-update&list=history");'; } ?>
			};
			
			// Initial Loads
			queueLoad();
			historyLoad();
			
			// Interval Loads
			var queueInterval = setInterval(queueLoad, queueRefresh);
			var historyInterval = setInterval(historyLoad, historyRefresh);
			
			// Manual Load
			$("#getDownloader").click(function() {
				queueLoad();
				historyLoad();
			});
            <?php } ?>
        });
        </script>
        <?php if ((SONARRURL != "" && qualifyUser(SONARRHOMEAUTH)) || (RADARRURL != "" && qualifyUser(RADARRHOMEAUTH)) || (HEADPHONESURL != "" && qualifyUser(HEADPHONESHOMEAUTH)) || (SICKRAGEURL != "" && qualifyUser(SICKRAGEHOMEAUTH))) { ?>
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
<?php if (SICKRAGEURL != "" && qualifyUser(SICKRAGEHOMEAUTH)){ echo getSickrageCalendarWanted($sickrage->future()); echo getSickrageCalendarHistory($sickrage->history("100","downloaded")); } ?>
<?php if (SONARRURL != "" && qualifyUser(SONARRHOMEAUTH)){ echo getSonarrCalendar($sonarr->getCalendar($startDate, $endDate)); } ?>
<?php if (RADARRURL != "" && qualifyUser(RADARRHOMEAUTH)){ echo getRadarrCalendar($radarr->getCalendar($startDate, $endDate)); } ?>                 
<?php if (HEADPHONESURL != "" && qualifyUser(HEADPHONESHOMEAUTH)){ echo getHeadphonesCalendar(HEADPHONESURL, HEADPHONESKEY, "getHistory"); echo getHeadphonesCalendar(HEADPHONESURL, HEADPHONESKEY, "getWanted"); } ?>                                
                    ],
                    eventRender: function eventRender( event, element, view ) {
                        return ['all', event.imagetype].indexOf($('#imagetype_selector').val()) >= 0
                    },

                    editable: false,
                    droppable: false,
					timeFormat: '<?php echo CALTIMEFORMAT; ?>',
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
        <?php } ?>

    </body>

</html>
