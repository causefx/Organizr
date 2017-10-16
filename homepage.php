<?php

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

require_once("user.php");
require_once("functions.php");
$USER = new User("registration_callback");
$group = $USER->role;

// Check if connection to homepage is allowed
qualifyUser(HOMEPAGEAUTHNEEDED, true);

// Load Colours/Appearance
foreach(loadAppearance() as $key => $value) {
	${$key} = $value;
}

?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title><?=$title;?> Homepage</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css">
        <script src="js/menu/modernizr.custom.js"></script>

        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">

        <link rel="stylesheet" href="bower_components/fullcalendar/dist/fullcalendar.css">

        <link rel="stylesheet" href="css/style.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/google-material-color/dist/palette.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" type="text/css" href="bower_components/slick/slick.css?v=<?php echo INSTALLEDVERSION; ?>">
        <link rel="stylesheet" href="bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="bower_components/smoke/dist/css/smoke.min.css?v=<?php echo INSTALLEDVERSION; ?>">



        <!--Scripts-->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/slimScroll/jquery.slimscroll.min.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
        <script src="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>
        <script src="bower_components/fullcalendar/dist/fullcalendar.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
        <script src="bower_components/slick/slick.js?v=<?php echo INSTALLEDVERSION; ?>"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
	    <script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>

		<!--Other-->
		<script src="js/ajax.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
        <script src="bower_components/sweetalert/dist/sweetalert.min.js"></script>
        <script src="bower_components/smoke/dist/js/smoke.min.js"></script>

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        <style>
			<?php if($USER->role !== "admin"){ echo '.refreshImage { display: none; }';}?>
			.refreshImage{
				top: -10px;
				opacity: 0;
				z-index: 1000;
			}

			.refreshNP {
				z-index: 1001;
			}
			.w-refresh {
				opacity: 1;
			}
			.ultra-widget.refreshImage .w-refresh.w-p-icon {
			    opacity: 1;
			}
			.refreshImage:hover, .refreshNP:hover{
				opacity: 1 !important;
			}
			.refreshImage .w-refresh {
			    font-size: 36px;
			    opacity: 1;
			    right: 0;
			    left: 5px;
			}
			.refreshImage span.w-refresh:hover::before {
				content: "Refresh";
			    font-size: 17px;
			    float: right;
			    top: 18px;
			    position: absolute;
			    left: 15px;
			    color: white;
			    background: black;
			    border-radius: 5px;
			    padding: 0px 20px;
			}
            .fc-day-grid-event{
                cursor: pointer;
            }
            .recentItems {
                padding-top: 10px;
                margin: 5px 0;
            }
            .slick-image-tall{
                width: 125px;
                height: 180px;
            }
            .slick-image-short{
                width: 125px;
                height: 130px;
                margin-top: 50px;
            }
            .overlay{
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: none;
                z-index: 0;
                opacity: .98;
            }
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
			}<?php customCSS(); ?>
        </style>
    </head>

    <body id="body-homepage" class="scroller-body group-<?php echo $group;?>" style="padding: 0px;">
        <div class="main-wrapper" style="position: initial;">
            <div id="content" class="container-fluid">
                <br/>

                <?php if (qualifyUser(HOMEPAGENOTICEAUTH) && HOMEPAGENOTICETITLE && HOMEPAGENOTICETYPE && HOMEPAGENOTICEMESSAGE && HOMEPAGENOTICELAYOUT) { echo buildHomepageNotice(HOMEPAGENOTICELAYOUT, HOMEPAGENOTICETYPE, HOMEPAGENOTICETITLE, HOMEPAGENOTICEMESSAGE); } ?>
                <?php if((qualifyUser(PLEXSEARCHAUTH) && PLEXSEARCH == "true" && qualifyUser(PLEXHOMEAUTH))) { ?>
                <div id="searchPlexRow" class="row">
                    <div class="col-lg-12">
                        <div class="content-box box-shadow big-box todo-list">
                            <form id="plexSearchForm" onsubmit="return false;" autocomplete="off">
                                <div class="">
                                    <div class="input-group">
                                        <div style="border-radius: 25px 0 0 25px; border:0" class="input-group-addon gray-bg"><i class="fa fa-search white"></i></div>
                                        <input id="searchInput" type="text" style="border-radius: 0;" autocomplete="off" name="search-title" class="form-control input-group-addon gray-bg" placeholder="Media Search">
										<div id="clearSearch" style="border-radius: 0 25px 25px 0;border:0; cursor: pointer;" class="input-group-addon gray-bg"><i class="fa fa-close white"></i></div>
                                        <button style="display:none" id="plexSearchForm_submit" class="btn btn-primary waves"></button>
                                    </div>
                                </div>
                            </form>
                            <div id="resultshere" class="table-responsive"></div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <?php if (qualifyUser(HOMEPAGECUSTOMHTML1AUTH) && HOMEPAGECUSTOMHTML1) { echo "<div>" . HOMEPAGECUSTOMHTML1 . "</div>"; } ?>

                <?php if(SPEEDTEST == "true" && qualifyUser(SPEEDTESTAUTH)){ ?>
                <style type="text/css">

                    .flash {
                        animation: flash 0.6s linear infinite;
                    }

                    @keyframes flash {
                        0% { opacity: 0.6; }
                        50% { opacity: 1; }
                    }

                </style>
                <script type="text/javascript">
                    var w = null
                    function runTest() {
                        document.getElementById('startBtn').style.display = 'none'
                        document.getElementById('testArea').style.display = ''
                        document.getElementById('abortBtn').style.display = ''
                        w = new Worker('bower_components/speed/speedtest_worker.js')
                        var interval = setInterval(function () { w.postMessage('status') }, 100)
                        w.onmessage = function (event) {
                            var data = event.data.split(';')
                            var status = Number(data[0])
                            var dl = document.getElementById('download')
                            var ul = document.getElementById('upload')
                            var ping = document.getElementById('ping')
                            var jitter = document.getElementById('jitter')
                            dl.className = status === 1 ? 'w-name flash' : 'w-name'
                            ping.className = status === 2 ? 'w-name flash' : 'w-name'
                            jitter.className = ul.className = status === 3 ? 'w-name flash' : 'w-name'
                            if (status >= 4) {
                                clearInterval(interval)
                                document.getElementById('abortBtn').style.display = 'none'
                                document.getElementById('startBtn').style.display = ''
                                w = null
                            }
                            if (status === 5) {
                                document.getElementById('testArea').style.display = 'none'
                            }
                            dl.textContent = data[1] + " Mbit/s";
                            $("#downloadpercent").attr("style", "width: " + data[1] + "%;");
                            $("#uploadpercent").attr("style", "width: " + data[2] + "%;");
                            $("#pingpercent").attr("style", "width: " + data[3] + "%;");
                            $("#jitterpercent").attr("style", "width: " + data[5] + "%;");
                            ul.textContent = data[2] + " Mbit/s";
                            ping.textContent = data[3] + " ms";
                            jitter.textContent = data[5] + " ms";
                        }
                        w.postMessage('start {"telemetry_level":"basic"}')
                        //w.postMessage('start')
                    }
                    function abortTest() {
                        if (w) w.postMessage('abort')
                    }
                </script>

                <div class="row" id="testArea" style="display:none">

                    <div class="test col-sm-3 col-lg-3">
                        <div class="content-box ultra-widget green-bg" data-counter="">
                            <div id="downloadpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
                            <div class="w-content">
                                <div class="w-icon right pull-right"><i class="mdi mdi-cloud-download"></i></div>
                                <div class="w-descr left pull-left text-center">
                                    <span class="testName text-uppercase w-name">Download</span>
                                    <br>
                                    <span class="w-name counter" id="download" ></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="test col-sm-3 col-lg-3">
                        <div class="content-box ultra-widget red-bg" data-counter="">
                            <div id="uploadpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
                            <div class="w-content">
                                <div class="w-icon right pull-right"><i class="mdi mdi-cloud-upload"></i></div>
                                <div class="w-descr left pull-left text-center">
                                    <span class="testName text-uppercase w-name">Upload</span>
                                    <br>
                                    <span class="w-name counter" id="upload" ></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="test col-sm-3 col-lg-3">
                        <div class="content-box ultra-widget yellow-bg" data-counter="">
                            <div id="pingpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
                            <div class="w-content">
                                <div class="w-icon right pull-right"><i class="mdi mdi-timer"></i></div>
                                <div class="w-descr left pull-left text-center">
                                    <span class="testName text-uppercase w-name">Latency</span>
                                    <br>
                                    <span class="w-name counter" id="ping" ></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="test col-sm-3 col-lg-3">
                        <div class="content-box ultra-widget blue-bg" data-counter="">
                            <div id="jitterpercent" class="progress-bar progress-bar-striped active w-used" style=""></div>
                            <div class="w-content">
                                <div class="w-icon right pull-right"><i class="mdi mdi-pulse"></i></div>
                                <div class="w-descr left pull-left text-center">
                                    <span class="testName text-uppercase w-name">Jitter</span>
                                    <br>
                                    <span class="w-name counter" id="jitter" ></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <br/>

                </div>

                <div id="abortBtn" class="row" style="display: none" onclick="javascript:abortTest()">
                    <div class="col-lg-12">
                        <div class="content-box red-bg" style="cursor: pointer;">
                            <h1 style="margin: 10px" class="text-uppercase text-center">Abort Speed Test</h1>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                <div id="startBtn" class="row" onclick="javascript:runTest()">
                    <div class="col-lg-12">
                        <div class="content-box green-bg" style="cursor: pointer;">
                            <h1 style="margin: 10px" class="text-uppercase text-center">Run Speed Test</h1>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <?php if((NZBGETURL != "" && qualifyUser(NZBGETHOMEAUTH)) || (SABNZBDURL != "" && qualifyUser(SABNZBDHOMEAUTH))) { ?>
                <div id="downloadClientRow" class="row">
                    <div class="col-xs-12 col-md-12">
                        <div class="content-box">
                            <div class="tabbable panel with-nav-tabs panel-default">
                                <div class="panel-heading">
                                    <div class="content-tools i-block pull-right">
                                        <a id="getDownloader" class="repeat-btn">
                                            <i class="fa fa-repeat"></i>
                                        </a>
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
															<th class="col-xs-1 nzbtable"><?php echo $language->translate("SIZE");?></th>
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
															<th class="col-xs-1 nzbtable"><?php echo $language->translate("SIZE");?></th>
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
                <div id="plexRowNowPlaying" class="row">
                    <?php if(qualifyUser(PLEXPLAYINGNOWAUTH) && PLEXPLAYINGNOW == "true"){ echo getPlexStreams(12, PLEXSHOWNAMES, $USER->role); } ?>
                </div>
                <div id="plexRow" class="row">
					<div class="col-lg-12">
	                <?php
	                if(qualifyUser(PLEXRECENTMOVIEAUTH) && PLEXRECENTMOVIE == "true" || qualifyUser(PLEXRECENTTVAUTH) && PLEXRECENTTV == "true" || qualifyUser(PLEXRECENTMUSICAUTH) &&  PLEXRECENTMUSIC == "true"){
	                    $plexArray = array("movie" => PLEXRECENTMOVIE, "season" => PLEXRECENTTV, "album" => PLEXRECENTMUSIC);
	                    echo getPlexRecent($plexArray);
	                }
	                ?>
					</div>
                </div>
                <div id="plexPlaylists" class="row">
                    <div class="col-lg-12">
                    <?php if(qualifyUser(PLEXPLAYLISTSAUTH) && PLEXPLAYLISTS == "true"){ echo getPlexPlaylists($plexArray); } ?>
                    </div>
                </div>
                <?php } ?>

				<?php if (qualifyUser(EMBYHOMEAUTH) && EMBYTOKEN) { ?>
                <div id="embyRowNowPlaying" class="row">
                    <?php if(EMBYPLAYINGNOW == "true"){ echo getEmbyStreams(12, EMBYSHOWNAMES, $USER->role); } ?>
                </div>
                <div id="embyRow" class="row">
                    <div class="col-lg-12">
                    <?php
                    if(EMBYRECENTMOVIE == "true" || EMBYRECENTTV == "true" || EMBYRECENTMUSIC == "true"){
                        $embyArray = array("Movie" => EMBYRECENTMOVIE, "Episode" => EMBYRECENTTV, "MusicAlbum" => EMBYRECENTMUSIC, "Series" => EMBYRECENTTV);
                        echo getEmbyRecent($embyArray);
                    }
                    ?>
                    </div>

                </div>
				<?php } ?>
                <?php if ((SONARRURL != "" && qualifyUser(SONARRHOMEAUTH)) || (RADARRURL != "" && qualifyUser(RADARRHOMEAUTH)) || (HEADPHONESURL != "" && qualifyUser(HEADPHONESHOMEAUTH)) || (SICKRAGEURL != "" && qualifyUser(SICKRAGEHOMEAUTH)) || (COUCHURL != "" && qualifyUser(COUCHHOMEAUTH))) { ?>
                <div id="calendarLegendRow" class="row" style="padding: 0 0 10px 0;">
                    <div class="col-lg-12 content-form form-inline">
                        <div class="form-group pull-right">
                            <span class="swal-legend label label-primary well-sm">Legend</span>&nbsp;
                            <div class="btn-group" role="group">
                                <button id="calendarSelected" style="margin-right: 0px;" type="button" class="btn waves btn-default btn-sm dropdown-toggle waves-effect waves-float" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">View All&nbsp;<span class="caret"></span></button>
                                <ul style="right:0; left: auto" class="dropdown-menu">
                                    <li><a class="calendarOption" calendarOption="all" href="javascript:void(0)">View All</a></li>
                                    <?php if(RADARRURL != ""){ echo '<li><a class="calendarOption" calendarOption="film" href="javascript:void(0)">Movies</a></li>'; }?>
                                    <?php if(SONARRURL != ""){ echo '<li><a class="calendarOption" calendarOption="tv" href="javascript:void(0)">TV Shows</a></li>'; }?>
                                    <?php if(HEADPHONESURL != ""){ echo '<li><a class="calendarOption" calendarOption="music" href="javascript:void(0)">Music</a></li>'; }?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="calendarRow" class="row">
                    <div class="col-lg-12">
                        <div id="calendar" class="fc-calendar box-shadow fc fc-ltr fc-unthemed"></div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <script>
        //Tooltips
        $('[data-toggle="tooltip"]').tooltip();

		$(document).on("click", ".refreshImage", function(e) {
			parent.$.smkAlert({
                text: 'Refreshing Image...',
                type: 'info',
                time: 1
            });
			e.preventDefault;
			var orginalElement = $(this).parent().find('.refreshImageSource');
			var original = orginalElement.attr('original-image');
			orginalElement.attr('src', original);
			console.log('replaced image with : '+original);
			setTimeout(function(){
				parent.$.smkAlert({
	                text: 'Image Refreshed - Clear Cache Please.',
	                type: 'info',
	                time: 3
	            });
			}, 1000);
		});
        $(".swal-legend").click(function () {
            swal({
                title: "Calendar Legend",
                text: '<span class="label label-primary well-sm">Available</span>&nbsp;<span class="label label-danger well-sm">Unavailable</span>&nbsp;<span class="label indigo-bg well-sm">Unreleased</span>&nbsp;<span class="label light-blue-bg well-sm">Premier</span>',
                html: true,
                confirmButtonColor: "#63A8EB"
            });
        });
        $('.close-btn').click(function(e){
            var closedBox = $(this).closest('div.content-box').remove();
            e.preventDefault();
        });
		$('#clearSearch').click(function(e){
            $('#searchInput').val("");
            $('#resultshere').html("");
            $('#searchInput').focus();
            e.preventDefault();
        });

		$(document).on("click", ".openTab", function(e) {
            parent.$.smkAlert({
                text: 'Loading...',
                type: 'info',
                time: 1
            });
            var Title = $(this).attr("extraTitle");
            var Type = $(this).attr("extraType");
            var openTab = $(this).attr("openTab");
            var location = $(this).attr("href");
            if( Type === 'season' || Type === 'episode' || Type === 'show'){
                Type = "tv";
                SearchType = "show";
            }else if( Type === 'movie'){
                Type = "movie";
                SearchType = "movie";
            }
            if( Type === 'tv' || Type === 'movie' ){
                ajax_request('POST', 'tvdb-search', {
                    name: Title,
                    type: SearchType,
                }).done(function(data){
                    if( data.trakt && data.trakt.tmdb !== null) {
                        $('#calendarExtra').modal('show');
                        var refreshBox = $('#calendarMainID');
                        $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(300);
                        setTimeout(function(){
                            var refreshPreloader = refreshBox.find('.refresh-preloader'),
                            deletedRefreshBox = refreshPreloader.fadeOut(300, function(){
                                refreshPreloader.remove();
                            });
                        },600);
                        $.ajax({
                            type: 'GET',
                            url: 'https://api.themoviedb.org/3/'+Type+'/'+data.trakt.tmdb+'?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&append_to_response=videos,credits&language=<?php echo $userLanguage; ?>',
                            cache: true,
                            async: true,
                            complete: function(xhr, status) {
                                var result = $.parseJSON(xhr.responseText);
                                if (xhr.statusText === "OK") {
                                    if( Type === "movie"){
                                        $('#calendarTitle').html(result.title);
                                        $('#calendarRating').html('<span class="label label-gray"><i class="fa fa-thumbs-up white"></i> '+result.vote_average+'</span>&nbsp;');
                                        $('#calendarRuntime').html('<span class="label label-gray"><i class="fa fa-clock-o white"></i> '+convertTime(result.runtime)+'</span>&nbsp;');
                                        $('#calendarSummary').text(result.overview);
                                        $('#calendarTagline').text(result.tagline);
                                        $('#calendarTrailer').html(convertTrailer(result.videos)+'&nbsp;<span class="label openPlex palette-Amber-600 bg" openTab="'+openTab+'" location="'+location+'" style="width:100%;display:block;cursor:pointer;"><i style="vertical-align:sub;" class="fa fa-play white"></i><text style="vertical-align:sub;"> Watch Now on PLEX</text></span>');
                                        $('#calendarCast').html(convertCast(result.credits));
                                        $('#calendarGenres').html(convertArray(result.genres, "MOVIE"));
                                        $('#calendarLang').html(convertArray(result.spoken_languages, "MOVIE"));
                                        $('#calendarPoster').attr("src","https://image.tmdb.org/t/p/w300"+result.poster_path);
                                        $('#calendarMain').attr("style","background-size: cover; background: linear-gradient(rgba(25,27,29,.75),rgba(25,27,29,.75)),url(https://image.tmdb.org/t/p/w1000"+result.backdrop_path+");top: 0;left: 0;width: 100%;height: 100%;position: fixed;");
                                        $('#calendarExtra').modal('show');
                                    }else if (Type === "tv"){
                                        $('#calendarTitle').html(result.name);
                                        $('#calendarRating').html('<span class="label label-gray"><i class="fa fa-thumbs-up white"></i> '+result.vote_average+'</span>&nbsp;');
                                        $('#calendarRuntime').html('<span class="label label-gray"><i class="fa fa-clock-o white"></i> '+convertTime(whatWasIt(result.episode_run_time))+'</span>&nbsp;');
                                        $('#calendarSummary').text(result.overview);
                                        $('#calendarTagline').text("");
                                        $('#calendarTrailer').html(convertTrailer(result.videos)+'&nbsp;<span class="label openPlex palette-Amber-600 bg" openTab="'+openTab+'" location="'+location+'" style="width:100%;display:block;cursor:pointer;"><i style="vertical-align:sub;" class="fa fa-play white"></i><text style="vertical-align:sub;"> Watch Now on PLEX</text></span>');
                                        $('#calendarCast').html(convertCast(result.credits));
                                        $('#calendarGenres').html(convertArray(result.genres, "MOVIE"));
                                        $('#calendarLang').html(convertArray(result.languages, "TV"));
                                        $('#calendarPoster').attr("src","https://image.tmdb.org/t/p/w300"+result.poster_path);
                                        $('#calendarMain').attr("style","background-size: cover; background: linear-gradient(rgba(25,27,29,.75),rgba(25,27,29,.75)),url(https://image.tmdb.org/t/p/w1000"+result.backdrop_path+");top: 0;left: 0;width: 100%;height: 100%;position: fixed;");
                                        $('#calendarExtra').modal('show');
                                    }
                                }
                            }
                        });
                    }else{
                        swal("Sorry!", "No info was found for this item!", "error");
                    }
                });
                e.preventDefault();
            }else{

                if($(this).attr("openTab") === "true") {
                    var isActive = parent.$("div[data-content-name^='<?php echo strtolower(PLEXTABNAME);?>']");
                    var activeFrame = isActive.children('iframe');
                    if(isActive.length === 1){
                        activeFrame.attr("src", $(this).attr("href"));
                        parent.$("li[name='<?php echo strtolower(PLEXTABNAME);?>']").trigger("click");
                    }else{
                        parent.$("li[name='<?php echo strtolower(PLEXTABNAME);?>']").trigger("click");
                        parent.$("div[data-content-name^='<?php echo strtolower(PLEXTABNAME);?>']").children('iframe').attr("src", $(this).attr("href"));
                    }
                    e.preventDefault();
                }else{
                    var source = $(this).attr("href");
                    window.open(source, '_blank');
                }
            }

        });

        $(document).on("click", ".openPlex", function(e) {
            if($(this).attr("openTab") === "true") {
				var isActive = parent.$("div[data-content-name^='<?php echo strtolower(PLEXTABNAME);?>']");
				var activeFrame = isActive.children('iframe');
				if(isActive.length === 1){
					activeFrame.attr("src", $(this).attr("location"));
					parent.$("li[name='<?php echo strtolower(PLEXTABNAME);?>']").trigger("click");
				}else{
					parent.$("li[name='<?php echo strtolower(PLEXTABNAME);?>']").trigger("click");
					parent.$("div[data-content-name^='<?php echo strtolower(PLEXTABNAME);?>']").children('iframe').attr("src", $(this).attr("location"));
				}
			}else{
                var source = $(this).attr("location");
				window.open(source, '_blank');
			}
        });


        function localStorageSupport() {
            return (('localStorage' in window) && window['localStorage'] !== null)
        }

		function loadSlick(){
			$('div[class*=recentItems-]').each(function() {
				if($(this).hasClass('slick-initialized')){
					console.log('skipping slick addon');
					return false;
				}
                var name = $(this).attr("data-name");
                console.log('creating slick for '+name);
                $(this).slick({

                    slidesToShow: 13,
                    slidesToScroll: 13,
                    infinite: true,
                    lazyLoad: 'ondemand',
                    prevArrow: '<a class="zero-m pull-left prev-mail btn btn-default waves waves-button btn-sm waves-effect waves-float"><i class="fa fa-angle-left"></i></a>',
                    nextArrow: '<a class="pull-left next-mail btn btn-default waves waves-button btn-sm waves-effect waves-float"><i class="fa fa-angle-right"></i></a>',
                    appendArrows: $('.'+name),
                    arrows: true,
                    responsive: [
						{
		                    breakpoint: 1750,
		                    settings: {
		                        slidesToShow: 12,
		                        slidesToScroll: 12,
		                    }
	                    },
	                    {
		                    breakpoint: 1600,
		                    settings: {
		                        slidesToShow: 11,
		                        slidesToScroll: 11,
		                    }
	                    },
	                    {
		                    breakpoint: 1450,
		                    settings: {
		                        slidesToShow: 10,
		                        slidesToScroll: 10,
		                    }
	                    },
	                    {
		                    breakpoint: 1300,
		                    settings: {
		                        slidesToShow: 9,
		                        slidesToScroll: 9,
		                    }
	                    },
	                    {
		                    breakpoint: 1150,
		                    settings: {
		                        slidesToShow: 8,
		                        slidesToScroll: 8,
		                    }
	                    },
	                    {
		                    breakpoint: 1000,
		                    settings: {
		                        slidesToShow: 7,
		                        slidesToScroll: 7,
		                    }
	                    },
	                    {
		                    breakpoint: 850,
		                    settings: {
		                        slidesToShow: 6,
		                        slidesToScroll: 6,
		                    }
	                    },
	                    {
		                    breakpoint: 700,
		                    settings: {
		                        slidesToShow: 5,
		                        slidesToScroll: 5,
		                    }
	                    },
	                    {
		                    breakpoint: 675,
		                    settings: {
		                        slidesToShow: 4,
		                        slidesToScroll: 4
		                    }
	                    },
	                    {
		                    breakpoint: 480,
		                    settings: {
		                        slidesToShow: 3,
		                        slidesToScroll: 3
		                    }
	                    }
	                ]
                });
            });
		}

        $( document ).ready(function() {
            $('#plexSearchForm').on('submit', function () {
                var refreshBox = $(this).closest('div.content-box');
                $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(300);
                setTimeout(function(){
                    var refreshPreloader = refreshBox.find('.refresh-preloader'),
                    deletedRefreshBox = refreshPreloader.fadeOut(300, function(){
                        refreshPreloader.remove();
                    });
                },1000);
                ajax_request('POST', 'search-plex', {
                    searchtitle: $('#plexSearchForm [name=search-title]').val(),
                }).done(function(data){ $('#resultshere').html(data);});

            });
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
            $(document).on('click', '.w-refresh', function(){
                var id = $(this).attr("link");
                $("div[np^='"+id+"']").toggle();
            });
			//load slick
			loadSlick();

            //RECENT ITEMS
            // each filter we click on
            $(".filter-recent-event > li").on("click", function() {
                var name = $(this).attr('data-name');
                var filter = $(this).attr('data-filter');
                $('#recentContent-title').text('Recently Added '+name);
                // now filter the slides.
                if(filter !== 'item-all'){
                    $('.recentItems-recent')
                        .slick('slickUnfilter')
                        .slick('slickFilter' , '.'+filter );
                }else{
                    $('.recentItems-recent')
                        .slick('slickUnfilter')
                }
            });
            //PLAYLIST SHIT
             // each filter we click on
            $(".filter-recent-playlist > li").on("click", function() {
                var name = $(this).attr('data-name');
                var filter = $(this).attr('data-filter');
                $('#playlist-title').text(name);

                // now filter the slides.
                $('.recentItems-playlists')
                    .slick('slickUnfilter')
                    .slick('slickFilter' , '.'+filter );
            });

            $("body").niceScroll({
                //cursorwidth: "12px"
                scrollspeed: 30,
                mousescrollstep: 60,
                grabcursorenabled: false
            });
            $(".table-responsive").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                scrollspeed: 30,
                mousescrollstep: 60,
                grabcursorenabled: false
            });
            $(".playlist-listing").niceScroll({
                railpadding: {top:0,right:0,left:0,bottom:0},
                scrollspeed: 30,
                mousescrollstep: 60,
                grabcursorenabled: false
            });

            <?php if((NZBGETURL != "" && qualifyUser(NZBGETHOMEAUTH)) || (SABNZBDURL != "" && qualifyUser(SABNZBDHOMEAUTH))){ ?>
            var queueRefresh = <?php echo DOWNLOADREFRESH; ?>;
            var historyRefresh = <?php echo HISTORYREFRESH; ?>; // This really doesn't need to happen that often

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

        $( window ).on( "load", function() {
            $( "ul.filter-recent-playlist > li:first" ).trigger("click");
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
                    //events: [ <?php //echo getCalendar(); ?> ],
                    eventRender: function eventRender( event, element, view ) {
                        //return ['all', event.imagetype].indexOf($('#imagetype_selector').val()) >= 0
                        if (typeof filter !== 'undefined') {
                            if(filter === "all"){
                                return event.imagetype === event.imagetype;
                            }else if(filter !== "all"){
                                return filter === event.imagetype;
                            }
                            if(filter === null){
                                return event.imagetype === event.imagetype;
                            }
                        }else {
                            return event.imagetype === event.imagetype;
                        }
                    },

                    editable: false,
                    droppable: false,
					timeFormat: '<?php echo CALTIMEFORMAT; ?>',
                });
            });

            $(document).on('click', ".calendarOption", function(){
                window.filter = $(this).attr("calendarOption");
                if(filter ==="all"){
                    title = "View All";
                }else if(filter ==="tv"){
                    title = "TV Shows";
                }else if(filter ==="film"){
                    title = "Movies";
                }else if(filter ==="music"){
                    title = "Music";
                }
                console.log("Calendar Filter: "+title);
                $('#calendar').fullCalendar('rerenderEvents');
                $('#calendarSelected').html(title+"&nbsp;<span class=\"caret\"></span>");
            });
            $.ajax({
                type: 'GET',
                url: 'ajax.php?a=get-calendar',
                success: function(data)
                {
                    newData =  $.parseJSON(data);
                    $('#calendar').fullCalendar('removeEvents');
                    $('#calendar').fullCalendar('addEventSource', newData);
                    console.log('Calendar Entries Added');
                }
            });
            setInterval(function() {
                $.ajax({
                    type: 'GET',
                    url: 'ajax.php?a=get-calendar',
                    success: function(data)
                    {
                        newData =  $.parseJSON(data);
                        $('#calendar').fullCalendar('removeEvents');
                        $('#calendar').fullCalendar('addEventSource', newData);
                        console.log('Calendar refreshed');
                    }
                });
            }, <?php echo CALENDARREFRESH; ?>);
        </script>
        <?php } ?>
        <script>
            function convertTime(a){
                if(a){
                    var hours = Math.trunc(a/60);
                    var minutes = a % 60;
                    return hours+"h "+minutes+"m";
                }else{
                    return "N/A";
                }
            }
            function convertArray(a, type){
                var result = "";
                var count = 1;
                var color = "";
                $.each( a, function( key, value ) {
                    if (count == 1){ color = "gray"; }else{ color = "gray"; }
                    if(type == "MOVIE"){
                        result += '<span class="label label-'+color+'">'+value['name']+'</span>&nbsp;';
                    }else if(type == "TV"){
                        result += '<span class="label label-'+color+'">'+value+'</span>&nbsp;';
                    }
                    count++;
                });
                return result;
            }
            function convertTrailer(a){
                var result = "";
                var count = 1;
                $.each( a.results, function( key, value ) {
                    if (count == 1){
                        result += '<span id="openTrailer" style="cursor:pointer;width: 100%;display: block;" data-key="'+value['key']+'" data-name="'+value['name']+'" data-site="'+value['site']+'" class="label label-danger"><i style="vertical-align:sub;" class="fa fa-youtube-play" aria-hidden="true"></i><text style="vertical-align:sub;"> Watch Trailer</text></span>&nbsp;';
                    }
                    count++;
                });
                return result;
            }
            function convertCast(a){
                var result = "";
                var count = 1;
                $.each( a.cast, function( key, value ) {
                    if( value['profile_path'] ){
                        if (count <= 6){
                            result += '<div class="col-lg-4 col-xs-4"><div class="zero-m"><img class="pull-left" style="border-radius:10%;margin-left: auto;margin-right: auto;display: block;" height="100px" src="https://image.tmdb.org/t/p/w150'+value['profile_path']+'" alt="profile"><h5 class="text-center"><strong>'+value['name']+'</strong></h5><h6 class="text-center">'+value['character']+'</h6></div></div>';
                            count++;
                        }
                    }
                });
                return result;
            }
            function whatIsIt(a){
                var what = Object.prototype.toString;
                if(what.call(a) == "[object Array]"){
                    return a[0].fileName;
                }else if(what.call(a) == "[object Object]"){
                    return a.fileName;
                }
            }
            function whatWasIt(a){
                var what = Object.prototype.toString;
                if(what.call(a) == "[object Array]"){
                    return a[0];
                }else if(what.call(a) == "[object Object]"){
                    return a;
                }
            }
            $(document).on('click', "#openTrailer", function(){
                var key = $(this).attr("data-key");
                $('#iFrameYT').html('<iframe id="calendarYoutube" class="embed-responsive-item" src="https://www.youtube.com/embed/'+key+'" allowfullscreen=""></iframe>');
                $('#calendarVideo').modal('show');
            });
            $(document).on('click', "a[class*=ID-]", function(){
                parent.$.smkAlert({
                    text: 'Loading...',
                    type: 'info',
                    time: 1
                });
                var check = $(this).attr("class");
                var ID = check.split("--")[1];
                if (~check.indexOf("tvID")){
                    var type = "TV";
                    ajax_request('POST', 'tvdb-get', {
                        id: ID,
                    }).done(function(data){
                        if( data.trakt && data.trakt.tmdb !== null) {
                            $('#calendarExtra').modal('show');
                            var refreshBox = $('#calendarMainID');
                            $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(300);
                            setTimeout(function(){
                                var refreshPreloader = refreshBox.find('.refresh-preloader'),
                                deletedRefreshBox = refreshPreloader.fadeOut(300, function(){
                                    refreshPreloader.remove();
                                });
                            },600);
                            $.ajax({
                                type: 'GET',
                                url: 'https://api.themoviedb.org/3/tv/'+data.trakt.tmdb+'?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&append_to_response=videos,credits&language=<?php echo $userLanguage; ?>',
                                cache: true,
                                async: true,
                                complete: function(xhr, status) {
                                    var result = $.parseJSON(xhr.responseText);
                                    if (xhr.statusText === "OK") {
                                        $('#calendarTitle').text(result.name);
                                        $('#calendarRating').html('<span class="label label-gray"><i class="fa fa-thumbs-up white"></i> '+result.vote_average+'</span>&nbsp;');
                                        $('#calendarRuntime').html('<span class="label label-gray"><i class="fa fa-clock-o white"></i> '+convertTime(whatWasIt(result.episode_run_time))+'</span>&nbsp;');
                                        $('#calendarSummary').text(result.overview);
                                        $('#calendarTagline').text("");
                                        $('#calendarTrailer').html(convertTrailer(result.videos));
                                        $('#calendarCast').html(convertCast(result.credits));
                                        $('#calendarGenres').html(convertArray(result.genres, "MOVIE"));
                                        $('#calendarLang').html(convertArray(result.languages, "TV"));
                                        $('#calendarPoster').attr("src","https://image.tmdb.org/t/p/w300"+result.poster_path);
                                        $('#calendarMain').attr("style","background-size: cover; background: linear-gradient(rgba(25,27,29,.75),rgba(25,27,29,.75)),url(https://image.tmdb.org/t/p/w1000"+result.backdrop_path+");top: 0;left: 0;width: 100%;height: 100%;position: fixed;");
                                        $('#calendarExtra').modal('show');
                                    }
                                }
                            });
                        }else{
                            swal("Sorry..", "No info was found for this item!", "error");
                        }
                    });
                }else if (~check.indexOf("movieID")){
                    var type = "MOVIE";
                    $.ajax({
                        type: 'GET',
                        url: 'https://api.themoviedb.org/3/movie/'+ID+'?api_key=83cf4ee97bb728eeaf9d4a54e64356a1&append_to_response=videos,credits&language=<?php echo $userLanguage; ?>',
                        cache: true,
                        async: true,
                        complete: function(xhr, status) {
                            var result = $.parseJSON(xhr.responseText);
                            console.log(result);
                            console.log(convertCast(result.credits));
                            if (xhr.statusText === "OK") {
                                $('#calendarTitle').text(result.title);
                                $('#calendarRating').html('<span class="label label-gray"><i class="fa fa-thumbs-up white"></i> '+result.vote_average+'</span>&nbsp;');
                                $('#calendarRuntime').html('<span class="label label-gray"><i class="fa fa-clock-o white"></i> '+convertTime(result.runtime)+'</span>&nbsp;');
                                $('#calendarSummary').text(result.overview);
                                $('#calendarTagline').text(result.tagline);
                                $('#calendarTrailer').html(convertTrailer(result.videos));
                                $('#calendarCast').html(convertCast(result.credits));
                                $('#calendarGenres').html(convertArray(result.genres, "MOVIE"));
                                $('#calendarLang').html(convertArray(result.spoken_languages, "MOVIE"));
                                $('#calendarPoster').attr("src","https://image.tmdb.org/t/p/w300"+result.poster_path);
                                $('#calendarMain').attr("style","background-size: cover; background: linear-gradient(rgba(25,27,29,.75),rgba(25,27,29,.75)),url(https://image.tmdb.org/t/p/w1000"+result.backdrop_path+");top: 0;left: 0;width: 100%;height: 100%;position: fixed;");
                                $('#calendarExtra').modal('show');
                            }
                        }
                    });
                }
            });
        </script>
        <div id="calendarExtra" class="modal fade in" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg gray-bg" role="document">
                <div id="calendarMainID" class="modal-content">
                    <div class="modal-content" id="calendarMain"></div>
                    <div style="position: inherit; padding: 15px">
                        <span id="calendarRuntime" class="pull-left"></span>
                        <span id="calendarRating" class="pull-left"></span>
                        <span id="calendarGenres" class="pull-right"></span>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-4">
                                <img style="width:100%;border-radius: 10px;box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);" id="calendarPoster" src="">
                            </div>
                            <div class="col-sm-8">
                                <h2 id="calendarTitle" class="modal-title text-center">Modal title</h2>
                                <h6 id="calendarTagline" class="modal-title text-center"><em>Modal title</em></h6>
                                <p id="calendarSummary">Modal Summary</p>
                                <div class="" id="calendarCast">Modal Summary</div>
                            </div>
                        </div>
                    </div>
                   <div style="position: inherit; padding: 15px 0px 30px 0px; margin-top: -20px;">
                        <div class="col-sm-4">
                            <span id="calendarTrailer" class="pull-left" style="width:100%;display: flex;"></span>
                        </div>
                        <div class="col-sm-8">
                            <span id="calendarLang" class="pull-right"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="calendarVideo" class="modal fade in palette-Grey-900 bg" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg gray-bg" role="document">
                <div id="calendarMainVideo" class="modal-content gray-bg">
                    <div class="">
                        <!-- 16:9 aspect ratio -->
                        <div id="iFrameYT" class="embed-responsive embed-responsive-16by9 gray-bg"></div>
                    </div>
                </div>
            </div>
        </div>

    </body>
</html>
