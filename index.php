<?php
// Include functions if not already included
require_once('functions.php');

// Upgrade environment
upgradeCheck();

// Lazyload settings
$databaseConfig = configLazy('config/config.php');

// Load Colours/Appearance
foreach(loadAppearance() as $key => $value) {
	$$key = $value;
}

//Set some variables
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);
$data = false;
$databaseLocation = "databaseLocation.ini.php";
$needSetup = "Yes";
$tabSetup = "Yes";
$hasOptions = "No";
$settingsicon = "No";
$settingsActive = "";
$action = "";
$loadingIcon = "images/organizr-load-w-thick.gif";
$baseURL = "";
$dbcreated = false;
$splash = false;

// Get Action
if(isset($_POST['action'])) {
	$action = $_POST['action'];
	unset($_POST['action']);
}
//Get Invite Code
$inviteCode = isset($_GET['inviteCode']) ? $_GET['inviteCode'] : null;

// Check for config file
if(!file_exists('config/config.php')) {
	if($action == "createLocation") {
		if (isset($_POST['database_Location'])) {
			$_POST['database_Location'] = str_replace('//','/',$_POST['database_Location'].'/');
			if(substr($_POST['database_Location'], -1) != "/") : $_POST['database_Location'] = $_POST['database_Location'] . "/"; endif;
			$_POST['user_home'] = $_POST['database_Location'].'users/';
		}
		if (file_exists($_POST['database_Location'])) {
			updateConfig($_POST);
		} else {
			debug_out('Dir doesn\'t exist: '.$_POST['database_Location'],1); // Pretty Up
		}
	} else {
		$configReady = "No";
		$userpic = "";
		$showPic = "";
	}
}

if (file_exists('config/config.php')) {

	if (!DATABASE_LOCATION){
		die(header("Refresh:0"));
	}

	$configReady = "Yes";
	require_once("user.php");
	$USER = new User("registration_callback");
	$group = $USER->role;

	$dbfile = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";
	$database = new PDO("sqlite:" . $dbfile);
	$query = "SELECT * FROM users";

	foreach($database->query($query) as $data) {

		$needSetup = "No";

	}

	$db = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";
	$file_db = new PDO("sqlite:" . $db);
	$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbTab = q2a($file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="tabs"'));
	$dbOptions = q2a($file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="options"'));
	if (is_array($dbTab)) {
		foreach($dbTab as $row) {
			if (in_array("tabs", $row)) {
				$tabSetup = "No";
			}
		}
	}

	if($tabSetup == "Yes"){
		$settingsActive = "active";
	}
	if (is_array($dbOptions)) {
		foreach($dbOptions as $row) {
			if (in_array("options", $row)) {
				$hasOptions = "Yes";
			}
		}
	}

	if($tabSetup == "No") {
		if($USER->authenticated && $USER->role == "admin") {
			$result = q2a($file_db->query('SELECT * FROM tabs WHERE active = "true" ORDER BY `order` asc'));
			$splash = q2a($file_db->query('SELECT * FROM tabs WHERE active = "true" AND splash = "true" ORDER BY `order` asc'));
			if (is_array($result)) {
				foreach($result as $row) {
					if(!empty($row['iconurl']) && $settingsicon == "No") {
						$settingsicon = "Yes";
					}
				}
			}

		}elseif($USER->authenticated && $USER->role == "user") {

			$result = q2a($file_db->query('SELECT * FROM tabs WHERE active = "true" AND user = "true" ORDER BY `order` asc'));
			$splash = q2a($file_db->query('SELECT * FROM tabs WHERE active = "true" AND splash = "true" AND user = "true" ORDER BY `order` asc'));

		}else {

			$result = q2a($file_db->query('SELECT * FROM tabs WHERE active = "true" AND guest = "true" ORDER BY `order` asc'));
			$splash = q2a($file_db->query('SELECT * FROM tabs WHERE active = "true" AND splash = "true" AND guest = "true" ORDER BY `order` asc'));

		}

	}

	$userpic = md5( strtolower( trim( $USER->email ) ) );
	$showPic = "<i class=\"mdi mdi-account-circle\"></i>";
	if(LOADINGICON !== "") : $loadingIcon = LOADINGICON; endif;
	if(SLIMBAR == "true") : $slimBar = "30"; $userSize = "25"; else : $slimBar = "56"; $userSize = "40"; endif;

	//NEW CHAT
	if(CHAT == "true" && qualifyUser(CHATAUTH)){
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
	}
}

if(!defined('SLIMBAR')) : define('SLIMBAR', 'true'); endif;
if(!defined('AUTOHIDE')) : define('AUTOHIDE', 'false'); endif;
if(!defined('ENABLEMAIL')) : define('ENABLEMAIL', 'false'); endif;
if(!defined('CUSTOMCSS')) : define('CUSTOMCSS', 'false'); endif;
if(!defined('LOADINGSCREEN')) : define('LOADINGSCREEN', 'true'); endif;
if(!isset($notifyExplode)) : $notifyExplode = array("bar","slidetop"); endif;

if(SLIMBAR == "true") : $slimBar = "30"; $userSize = "25"; $chatSize = "142px"; else : $slimBar = "56"; $userSize = "40"; $chatSize = "171px";endif;

if(file_exists("images/settings2.png")) : $iconRotate = "false"; $settingsIcon = "settings2.png"; else: $iconRotate = "true"; $settingsIcon = "settings.png"; endif;

$group = (isset($group) ? $group : "guest");

?>
<!--

	___       ___       ___       ___       ___       ___       ___       ___
   /\  \     /\  \     /\  \     /\  \     /\__\     /\  \     /\  \     /\  \
  /::\  \   /::\  \   /::\  \   /::\  \   /:| _|_   _\:\  \   _\:\  \   /::\  \
 /:/\:\__\ /::\:\__\ /:/\:\__\ /::\:\__\ /::|/\__\ /\/::\__\ /::::\__\ /::\:\__\
 \:\/:/  / \;:::/  / \:\:\/__/ \/\::/  / \/|::/  / \::/\/__/ \::;;/__/ \;:::/  /
  \::/  /   |:\/__/   \::/  /    /:/  /    |:/  /   \:\__\    \:\__\    |:\/__/
   \/__/     \|__|     \/__/     \/__/     \/__/     \/__/     \/__/     \|__|

					  [Organizr Version: <?php echo INSTALLEDVERSION; ?> - By: CauseFX]

-->
<!DOCTYPE html>

<html lang="<?php echo $language->getLang(); ?>" class="no-js">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="mobile-web-app-capable" content="yes" /
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="msapplication-tap-highlight" content="no" />

		<title><?=$title;?><?php if($title !== "Organizr") :  echo " - Organizr"; endif; ?></title>

		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/bootstrap/dist/css/bootstrap.min.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/font-awesome/css/font-awesome.min.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/mdi/css/materialdesignicons.min.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/metisMenu/dist/metisMenu.min.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/Waves/dist/waves.min.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css?v=<?php echo INSTALLEDVERSION; ?>">

		<link rel="stylesheet" href="<?=$baseURL;?>js/selects/cs-select.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>js/selects/cs-skin-elastic.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/google-material-color/dist/palette.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/sweetalert/dist/sweetalert.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>bower_components/smoke/dist/css/smoke.min.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>js/notifications/ns-style-growl.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>js/notifications/ns-style-other.css?v=<?php echo INSTALLEDVERSION; ?>">


		<script src="<?=$baseURL;?>js/menu/modernizr.custom.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
		<script type="text/javascript" src="<?=$baseURL;?>js/sha1.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
		<script type="text/javascript" src="<?=$baseURL;?>js/user.js?v=<?php echo INSTALLEDVERSION; ?>"></script>

		<link rel="stylesheet" href="<?=$baseURL;?>css/style.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="<?=$baseURL;?>css/weather-icons.css?v=<?php echo INSTALLEDVERSION; ?>">
		<link rel="stylesheet" href="bower_components/animate.css/animate.min.css?v=<?php echo INSTALLEDVERSION; ?>">

		<link rel="icon" type="image/png" href="<?=$baseURL;?>images/favicon/android-chrome-192x192.png" sizes="192x192">
		<link rel="apple-touch-icon" sizes="180x180" href="<?=$baseURL;?>images/favicon/apple-touch-icon.png">
		<link rel="icon" type="image/png" href="<?=$baseURL;?>images/favicon/favicon-32x32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="<?=$baseURL;?>images/favicon/favicon-16x16.png" sizes="16x16">
		<link rel="manifest" href="<?=$baseURL;?>images/favicon/manifest.json">
		<link rel="mask-icon" href="<?=$baseURL;?>images/favicon/safari-pinned-tab.svg" color="#2d89ef">
		<link rel="shortcut icon" href="<?=$baseURL;?>images/favicon/favicon.ico">
		<meta name="msapplication-config" content="<?=$baseURL;?>images/favicon/browserconfig.xml">
		<meta name="theme-color" content="#2d89ef">
		<link rel="stylesheet" type="text/css" href="css/addtohomescreen.css">
		<script src="js/addtohomescreen.js"></script>
		<script src="js/push.js"></script>
		<!--Other-->
		<script src="js/ajax.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
		<!--[if lt IE 9]>
		<script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
		<script src="bower_components/respondJs/dest/respond.min.js"></script>
		<![endif]-->
	</head>
	<style>
		.la-ball-scale-multiple.la-3x {
			width: auto !important;
			height: auto !important;
		}
		.logo img {
		    display: block;
		    margin: auto;
		}
		#splashScreen ping span {
		    margin-top: 0 !important;
		    font-size: 10px;
		    zoom: 2;
		}
		#weather .w-icon.right.pull-right {
			font-size: 70px;
		}
		.new-message{
			color: #46bc99 !important;
		}
		@media screen and (max-width:737px){
			.email-body{width: 100%; overflow: auto;height: 100%;}
			.email-content, .email-new {
				-webkit-overflow-scrolling: touch;
				-webkit-transform: translateZ(0);
				overflow: scroll;
				position: fixed;
				height:100% !important;
				margin-top:<?=$slimBar;?>px;
			}.email-content .email-header, .email-new .email-header{
				padding: 10px 30px;
				z-index: 1000;
			}
		}@media screen and (min-width:737px){
			.email-body{width: 100%}
			.email-content .close-button, .email-content .email-actions, .email-new .close-button, .email-new .email-actions {
				position: relative;
				top: 15px;
				right: 0px;
				float: right;
			}.email-inner-section {
				margin-top: 30px;
			}.email-content, .email-new {
				overflow: auto;
				margin: <?=$slimBar;?>px 0 0 0 !important;
				height: 100%;
				position: fixed;
				max-width: 100%;
				width: calc(35%) !important;
				right: calc(-35%);
			}.email-content .email-header, .email-new .email-header{
				position: fixed;
				padding: 0px 30px;
				width: calc(35%) !important;
				z-index: 1000;
			}
		}
		.loop-animation {
			animation-iteration-count: infinite;
			-webkit-animation-iteration-count: infinite;
			-moz-animation-iteration-count: infinite;
			-o-animation-iteration-count: infinite;
		}
		.loop-animation-timeout {
			animation-iteration-count: 5;
			-webkit-animation-iteration-count: 5;
			-moz-animation-iteration-count: 5;
			-o-animation-iteration-count: 5;
		}
		.ping-success {
			background: #46bc99 !important;
			box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		}.ping-warning {
			background: #ff3333 !important;
			box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
		}
		.TabOpened {
			-webkit-filter: drop-shadow(0px 0px 5px <?=$topbartext;?>);
			filter: drop-shadow(0px 0px 5px <?=$topbartext;?>);
		}.bottom-bnts a {
			background: <?=$bottombar;?> !important;
			color: <?=$topbartext;?> !important;
		}.bottom-bnts {
			background: <?=$bottombar;?> !important;
		}.gn-menu-main {
			background: <?=$topbar;?>;
		}.gn-menu-main ul.gn-menu {
			background: <?=$sidebar;?>;
		}.gn-menu-wrapper {
			background: <?=$sidebar;?>;
		}.la-timer.la-dark {
			color: <?=$topbartext;?>
		}.refresh-preloader {
			background: <?=$loading;?>;
		}.la-timer {
			width: 75px;
			height: 75px;
			padding-top: 20px;
			border-radius: 100px;
			background: <?=$sidebar;?>;
			border: 2px solid <?=$topbar;?>;
		}@media screen and (min-width:737px){
			.tab-item:hover a {
				color: <?=$hovertext;?> !important;
				background: <?=$hoverbg;?>;
				border-radius: 100px 0 0 100px;
			}
		}.gn-menu li.active > a {
			color: <?=$activetabtext;?> !important;
			background: <?=$activetabBG;?>;
			border-radius: 100px 0 0 100px;
		}.gn-menu li.rightActive > a {
			background: <?=$hoverbg;?>;
			border-radius: 100px 0 0 100px;
		}.gn-menu li.active i.fa {
			color: <?=$activetabicon;?>;
		}.gn-menu li i.fa {
			color: <?=$inactiveicon;?>;
		}.gn-menu-main ul.gn-menu a {
			color: <?=$inactivetext;?>;
		}li.dropdown.some-btn .mdi {
			color: <?=$topbartext;?>;
		}li.dropdown.some-btn .mdi:hover {
			color: <?=$hoverbg;?>;
		}.nav>li>a:focus, .nav>li>a:hover {
			text-decoration: none;
			background-color: transparent;
		}div#preloader {
			background: <?=$loading;?>;
		}.iframe {
			-webkit-overflow-scrolling: touch;
		}.main-wrapper{
			position: absolute !important;
		}#menu-toggle span {
			background: <?=$topbartext;?>;
		}logo.logo {
			opacity: 0.5;
			filter: alpha(opacity=50);
		}.mini-nav .split {
			width: calc(50% - 25px);
		}.splitRight {
			width: 50%;
			margin-left: 50% !important;
			position: absolute !important;
		}.split {
			width: 50%;
			position: absolute !important;
		}.mini-nav .splitRight {
			margin-left: calc(50% + 25px) !important;
			width: calc(50% - 25px);
		}.form-control.material {
			background-image: -webkit-gradient(linear, left top, left bottom, from(<?=$topbartext;?>), to(<?=$topbartext;?>)), -webkit-gradient(linear, left top, left bottom, from(#d2d2d2), to(#d2d2d2));
			background-image: -webkit-linear-gradient(<?=$topbartext;?>, <?=$topbartext;?>), -webkit-linear-gradient(#d2d2d2, #d2d2d2);
			background-image: linear-gradient(<?=$topbartext;?>, <?=$topbartext;?>), linear-gradient(#d2d2d2, #d2d2d2);
		}img.titlelogoclass {
			max-width: 250px;
			max-height: <?=$slimBar;?>px;
		}@media only screen and (max-width: 450px) {
			img.titlelogoclass {
				max-width: 150px;
			}
		}.login-btn {
			-webkit-border-radius: 4;
			-moz-border-radius: 4;
			border-radius: 4px;
			-webkit-box-shadow: 0px 1px 3px #666666;
			-moz-box-shadow: 0px 1px 3px #666666;
			box-shadow: 0px 1px 3px #666666;
			font-family: Arial;
			color: <?=$topbar;?>;
			font-size: 10px;
			vertical-align: top;
			background: <?=$topbartext;?>;
			padding: 5px 10px 5px 10px;
			text-decoration: none;
			font-weight: 700;
			font-style: normal;
		}.login-btn:hover {
			background: <?=$hoverbg;?>;
			color: <?=$hovertext;?>;
			text-decoration: none;
			font-weight: 700;
		}

		<?php if(SLIMBAR == "true") : ?>
		/* Slim Styling */
		body{
			padding-top: 30px !important;
		}.gn-menu-main {
			height: 30px !important;
		}.gn-menu-wrapper {
			top: 30px !important;
		}.gn-menu-main .navbar-right {
			line-height: 30px !important;
		}img.img-circle {
			vertical-align: inherit;
			margin-top: 2px;
		}.menu-toggle .hamburger {
			top: 0px !important;
		}.top-clock {
			line-height: 30px !important;
		}img.titlelogoclass {
			vertical-align: inherit;
		}.members-sidebar {
			top: 30px !important;
		}.menu-toggle .cross span:nth-child(2) {
			left: -9px;
			top: 41px;
		}.menu-toggle.gn-selected .cross span:nth-child(2) {
			width: 53%;
		}.menu-toggle.gn-selected .cross span:nth-child(1) {
			height: 105% !important;
		}.menu-toggle .cross span:nth-child(1) {
			left: 6px !important;
			top: 26px !important;
		}.menu-toggle .hamburger span {
			margin: 5px 0;
			width: 25px;
		}.menu-toggle .hamburger {
			margin-left: -17px;
		}.ns-effect-slidetop {
			padding: 6px 22px;
		}.ns-effect-exploader {
			padding: 5px 22px;
		}
		[class^="icon-"]:before, [class*=" icon-"]:before {
			display: inline !important;
		}
		<?php endif; ?>
		<?php customCSS(); ?>
	</style>

	<body id="body-index" class="group-<?php echo $group;?>" style="overflow: hidden">

		<?php if (LOADINGSCREEN == "true") : ?>
		<!--Preloader-->
		<div id="preloader" class="preloader table-wrapper">
			<div class="table-row">
				<div class="table-cell">
					<div class="la-ball-scale-multiple la-3x" style="color: <?=$topbar;?>">
						<logo class="logo"><img height="192px" src="<?=$loadingIcon;?>"></logo>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<div id="main-wrapper" class="main-wrapper" tabindex="-1">

			<ul id="gn-menu" class="gn-menu-main">
				<li class="gn-trigger">
					<a id="menu-toggle" class="menu-toggle gn-icon gn-icon-menu">
						<div class="hamburger">
							<span></span>
							<span></span>
							<span></span>
						</div>
						<div class="cross">
							<span></span>
							<span></span>
						</div>
					</a>
					<nav class="gn-menu-wrapper">
						<div class="gn-scroller" id="gn-scroller">
							<ul id="tabList" class="gn-menu metismenu">

								<!--Start Tab List-->
								<?php
								if($tabSetup == "No") {
									$tabCount = 1;
									$allPings = array();
									if (is_array($result)) {
										foreach($result as $row) {
											$name = str_replace(array(':', '\\', '/', '*'), 'x', $row['ping_url']);
											if($row['defaultz'] == "true") {
												$defaultz = "active";
											}else {
												$defaultz = "";
											} ?>
									<li window="<?=$row['window'];?>" class="tab-item <?=$defaultz;?>" id="<?=$row['url'];?>x" data-title="<?=$row['name'];?>" name="<?php echo strtolower($row['name']);?>">
										<a class="tab-link">
											<?php if($row['iconurl']) { ?>
												<i style="font-size: 19px; padding: 0 10px; font-size: 19px" data-toggle="tooltip" data-placement="bottom" data-original-title="<?=$row['name'];?>">
													<span id="<?=$row['url'];?>s" class="badge badge-success" style="position: absolute;z-index: 100;right: 0px;"></span>
													<img src="<?=$row['iconurl'];?>" style="height: 30px; width: 30px; margin-top: -2px;">
													<?php if($row['ping'] == "true" && $row['ping_url']){ $allPings["image".$name] = $row['ping_url']; ?>
														<ping class="ping-<?=$name;?> startPingTimer"></ping>
													<?php }?>
												</i>
											<?php }else { ?>
												<i class="fa <?=$row['icon'];?> fa-lg" data-toggle="tooltip" data-placement="bottom" data-original-title="<?=$row['name'];?>">
													<span id="<?=$row['url'];?>s" class="badge badge-success" style="position: absolute;z-index: 100;right: 0px;"></span>
													<?php if($row['ping'] == "true" && $row['ping_url']){ $allPings["icon".$name] = $row['ping_url']; ?>
														<ping class="ping-<?=$name;?> startPingTimer"></ping>
													<?php }?>
												</i>
											<?php } ?>
											<?=$row['name'];?>
										</a>

									</li>
									<?php
										$tabCount++;
										};
									}
								}?>
								<?php if($configReady == "Yes") : if($USER->authenticated && $USER->role == "admin") :?>
								<li class="tab-item <?=$settingsActive;?>" id="settings.phpx" data-title="Settings" name="settings">
									<a class="tab-link">
										<?php if($settingsicon == "Yes") :
											echo '<i style="font-size: 19px; padding: 0 10px; font-size: 19px;">
												<img id="settings-icon" src="images/' . $settingsIcon . '" style="height: 30px; margin-top: -2px;"></i>';
										else :
											echo '<i id="settings-icon" class="fa fa-cog"></i>';
										endif; ?>
										<?php echo $language->translate("SETTINGS");?>
									</a>
								</li>
								<li style="display: none;" class="tab-item" id="updatedb.phpx" data-title="Upgrade" name="upgrade">
									<a class="tab-link">
										<?php if($settingsicon == "Yes") :
											echo '<i style="font-size: 19px; padding: 0 10px; font-size: 19px;">
												<img id="upgrade-icon" src="images/upgrade.png" style="height: 30px; margin-top: -2px;"></i>';
										else :
											echo '<i id="upgrade-icon" class="fa fa-arrow-up"></i>';
										endif; ?>
										<?php echo $language->translate("UPGRADE");?>
									</a>
								</li>
								<?php endif; endif;?>
								<!--End Tab List-->
							</ul>
						</div>

						<!-- /gn-scroller -->
						<div class="bottom-bnts">
							<a class="fix-nav"><i class="mdi mdi-pin"></i></a>
						</div>
					</nav>
				</li>

				<li class="top-clock">
					<?php
					if($configReady == "Yes") :
						if(TITLELOGO == "") :
							echo "<span><span style=\"color: $topbartext\"><b>$title</b></span></span>";
						else :
							echo "<img class='titlelogoclass' src='" . TITLELOGO . "'>";
						endif;
					else :
						echo "<span><span style=\"color: $topbartext\"><b>$title</b></span></span>";
					endif;
					?>
				</li>

				<li class="pull-right">
					<ul class="nav navbar-right right-menu">
						<?php if($configReady == "Yes"){?>
						<li class="dropdown some-btn">
							<a class="show-members">
								<i class="userpic"><?=$showPic;?></i>
							</a>
						</li>
						<?php if(!$USER->authenticated){?>
						<li class="dropdown some-btn">
							<a class="log-in">
								<login class='login-btn text-uppercase'><?php echo $language->translate("LOGIN"); ?></login>
							</a>
						</li>
						<?php } }?>

						<?php if(CHAT == "true" && qualifyUser(CHATAUTH)){?>
						<li class="dropdown some-btn">
							<a id="chat-open" class="chat-open">
								<i class="mdi mdi-forum animated"></i>
								<span class="label label-new-message"></span>
							</a>
						</li>
						<?php } ?>
					</ul>
				</li>
			</ul>

			<!--Content-->
			<div id="content" class="content" style="">
				<script>addToHomescreen();</script>

				<!--Load Framed Content-->
				<?php if($needSetup == "Yes" && $configReady == "Yes") : ?>
				<div class="table-wrapper" style="background:<?=$sidebar;?>;">

					<div class="table-row">

						<div class="table-cell text-center">

							<div class="login i-block">

								<div class="content-box">

									<div class="biggest-box" style="background:<?=$topbar;?>;">

										<h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>;"><?php echo $language->translate("CREATE_ADMIN");?></h1>

									</div>

									<div class="big-box text-left registration-form">

										<h4 class="text-center"><?php echo $language->translate("CREATE_ACCOUNT");?></h4>

										<form class="controlbox" name="new user registration" id="registration" action="" method="POST" data-smk-icon="glyphicon-remove-sign">

											<input type="hidden" name="op" value="register"/>
											<input type="hidden" name="sha1" value=""/>
											<input type="hidden" name="settings" value="false"/>

											<div class="form-group">

												<input type="text" class="form-control material" name="username" autofocus placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" minlength="3" maxlength="16" required>

											</div>

											<div class="form-group">

												<input type="email" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>">

											</div>

											<div class="form-group">

												<input type="password" class="form-control material" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>" data-smk-strongPass="weak" required>

											</div>

											<div class="form-group">

												<input type="password" class="form-control material" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

											</div>

											<button id="registerSubmit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block text-uppercase waves waves-effect waves-float" value="Register"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("REGISTER");?></text></button>

										</form>

									</div>

								</div>

							</div>

						</div>

					</div>

				</div>
				<?php endif; ?>

				<?php if($needSetup == "Yes" && $configReady == "No") : ?>
				<div class="table-wrapper" style="background:<?=$sidebar;?>;">

					<div class="table-row">

						<div class="table-cell text-center">

							<div class="login i-block">

								<div class="content-box">

									<div class="biggest-box" style="background:<?=$topbar;?>;">

										<h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>;"><?php echo $language->translate("DATABASE_PATH");?></h1>

									</div>

									<div class="big-box text-left">

										<h3 class="text-center"><?php echo $language->translate("SPECIFY_LOCATION");?></h3>
										<h5 class="text-left"><strong><?php echo $language->translate("CURRENT_DIRECTORY");?>: <?php echo str_replace("\\","/",__DIR__); ?> <br><?php echo $language->translate("PARENT_DIRECTORY");?>: <?php echo str_replace("\\","/",dirname(__DIR__)); ?></strong></h5>
										<form class="controlbox" name="setupDatabase" id="setupDatabase" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
											<input type="hidden" name="action" value="createLocation" />

											<div class="form-group">

												<input type="text" class="form-control material" name="database_Location" autofocus value="<?php echo str_replace("\\","/",dirname(__DIR__));?>" autocorrect="off" autocapitalize="off" required>
												<h5><?php echo $language->translate("SET_DATABASE_LOCATION");?></h5>
												<?php echo getTimezone();?>
												<h5><?php echo $language->translate("SET_TIMEZONE");?></h5>
												<?php
												if(file_exists(dirname(__DIR__) . '/users.db') || file_exists(__DIR__ . '/users.db') || file_exists(__DIR__ . '/config/users.db')) :
												echo '<h5 class="text-center red">';
												echo $language->translate("DONT_WORRY");
												echo '</h5>';
												endif;?>

											</div>

											<button style="background:<?=$topbar;?>;" id="databaseLocationSubmit" type="submit" class="btn btn-block btn-sm text-uppercase waves waves-effect waves-float" value="Save Location"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("SAVE_LOCATION");?></text></button>

										</form>

									</div>

								</div>

							</div>

						</div>

					</div>

				</div>
				<?php endif; ?>

				<?php if($configReady == "Yes") : if(!$USER->authenticated && $tabSetup == "Yes" && $needSetup == "No") :?>
				<div class="table-wrapper">
					<div class="table-row">
						<div class="table-cell text-center">
							<div class="login i-block">
								<div class="content-box">
									<div class="blue-bg biggest-box">
										<h1 class="zero-m text-uppercase"><?php echo $language->translate("AWESOME");?></h1>
									</div>
									<div class="big-box text-left">
										<h4 class="text-center"><?php echo $language->translate("TIME_TO_LOGIN");?></h4>
										<button type="submit" class="btn log-in btn-block btn-primary text-uppercase waves waves-effect waves-float"><?php echo $language->translate("LOGIN");?></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif; endif; ?>
				<?php if($tabSetup == "No" && $needSetup == "No") :?>
				<div id="tabEmpty" class="table-wrapper" style="display: none; background:<?=$sidebar;?>;">
					<div class="table-row">
						<div class="table-cell text-center">
							<div class="login i-block">
								<div class="content-box">
									<div class="biggest-box" style="background:<?=$topbar;?>;">
										<h1 class="zero-m text-uppercase" style="color:<?=$topbartext;?>;"><?php echo $language->translate("HOLD_UP");?></h1>
									</div>
									<div class="big-box text-left">
										<!--<center><img src="images/sowwy.png" style="height: 200px;"></center>
										<h2 class="text-center"><?php echo $language->translate("LOOKS_LIKE_YOU_DONT_HAVE_ACCESS");?></h2>-->
										<?php if(!$USER->authenticated) : ?>
										<button style="background:<?=$topbar;?>;" type="submit" class="btn log-in btn-block btn-primary text-uppercase waves waves-effect waves-float"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("LOGIN");?></text></button>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endif;?>
				<!--End Load Framed Content-->
			</div>
			<div id="contentRight" class="content splitRight" style="">
			</div>
			<!--End Content-->

		</div>
		<?php if($configReady == "Yes") : if(!$USER->authenticated && $configReady == "Yes") : ?>
		<div id="loginModal" class="login-modal modal fade">
			<div style="background:<?=$sidebar;?>;" class="table-wrapper">
				<div class="table-row">
					<div class="table-cell text-center">
						<button style="color:<?=$topbartext;?>;" type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<div class="login i-block">
							<div class="content-box">
								<div style="background:<?=$topbar;?>;" class="biggest-box">

									<h1 style="color:<?=$topbartext;?>;" class="zero-m text-uppercase"><?php echo $language->translate("WELCOME");?></h1>

								</div>
								<div class="big-box text-left login-form">

									<?php if($USER->error!="") : ?>
									<p class="error">Error: <?php echo $USER->error; ?></p>
									<?php endif; ?>
									<form name="log in" id="login" action="" method="POST">
										<h4 class="text-center"><?php echo $language->translate("LOGIN");?></h4>
										<div class="form-group">
											<input type="hidden" name="op" value="login">
											<input type="hidden" name="sha1" value="">
											<input type="hidden" name="password" value="">
											<input type="hidden" name="rememberMe" value="false"/>
											<input type="text" class="form-control material" name="username" placeholder="<?php echo $language->translate("USERNAME");?>" autocomplete="off" autocorrect="off" autocapitalize="off" value="" autofocus required>
										</div>
										<div class="form-group">
											<input type="password" class="form-control material" name="password1" value="" autocomplete="off" placeholder="<?php echo $language->translate("PASSWORD");?>" required>
										</div>
										<div class="form-group">
											<div class="i-block"> <input id="rememberMe" name="rememberMe" class="switcher switcher-success switcher-medium pull-left" value="true" type="checkbox" checked="">
												<label for="rememberMe" class="pull-left"></label>
												<label class="pull-right"> &nbsp; <?php echo $language->translate("REMEMBER_ME");?></label>
											</div>

										</div>

										<button id="loginSubmit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="log in" onclick="User.processLogin()"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("LOGIN");?></text></button>

									</form>
									<?php if (ENABLEMAIL == "true") : ?>
									<button id="switchForgot" style="background:<?=$topbartext;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("FORGOT_PASSWORD");?></text></button>
									<?php endif; ?>
									<?php if(REGISTERPASSWORD != "") : ?>
									<button id="switchCreateUser" style="background:<?=$hoverbg;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$hovertext;?>;"><?php echo $language->translate("CREATE_USER");?></text></button>
									<?php endif; ?>
									<form style="display: none;" name="forgotPassword" id="forgotPassword" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
										<h4 class="text-center"><?php echo $language->translate("FORGOT_PASSWORD");?></h4>
										<div class="form-group">
											<input type="hidden" name="op" value="reset">
											<input type="text" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>" autocorrect="off" autocapitalize="off" value="" autofocus required>
										</div>

										<button style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="reset password"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("RESET_PASSWORD");?></text></button>

									</form>
									<button id="welcomeGoBack" style="background:<?=$topbartext;?>; display: none" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>
									<?php if(REGISTERPASSWORD != "") : ?>
									<div id="userPassForm" style="display: none;">
										<form id="userCreateForm" action="register.php" method="POST">
											<h4 class="text-center"><?php echo $language->translate("ENTER_PASSWORD_TO_REGISTER");?></h4>
											<center><h5 id="userCreateErrors" style="color: red"></h5></center>

											<div class="form-group">

												<input type="text" class="form-control material" name="registerPasswordValue" placeholder="<?php echo $language->translate("PASSWORD");?>" autocorrect="off" autocapitalize="off" value="" autofocus required>

											</div>

											<button style="background:<?=$topbar;?>;" type="submit" id="checkRegisterPass" class="btn btn-block btn-info text-uppercase waves" value="reset password"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("SUBMIT");?></text></button>
										</form>
										<button id="welcomeGoBack2" style="background:<?=$topbartext;?>; display: none" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>
									</div>
									<form style="display: none;" name="createUser" id="registration" action="" method="POST" data-smk-icon="glyphicon-remove-sign">
										<h4 class="text-center"><?php echo $language->translate("CREATE_USER");?></h4>
										<input type="hidden" name="op" value="register"/>
										<input type="hidden" name="sha1" value=""/>
										<input type="hidden" name="settings" value="false"/>
										<input type="hidden" name="validate" id="validate" value=""/>

										<div class="form-group">

											<input type="text" class="form-control material" name="username" autofocus placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" minlength="3" maxlength="16" required>

										</div>

										<div class="form-group">

											<input type="email" class="form-control material" name="email" placeholder="<?php echo $language->translate("EMAIL");?>">

										</div>

										<div class="form-group">

											<input type="password" class="form-control material" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>" data-smk-strongPass="weak" required>

										</div>

										<div class="form-group">

											<input type="password" class="form-control material" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

										</div>

										<button id="registerSubmit" type="submit" class="btn green-bg btn-block btn-warning text-uppercase waves waves-effect waves-float" value="Register"><?php echo $language->translate("REGISTER");?></button>
										<button id="welcomeGoBack3" style="background:<?=$topbartext;?>; display: none" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>

									</form>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; endif; ?>
		<?php if($configReady == "Yes") : if($USER->authenticated) : ?>
		<div style="background:<?=$topbar;?>;" class="logout-modal modal fade">
			<div class="table-wrapper" style="background: <?=$sidebar;?>">
				<div class="table-row">
					<div class="table-cell text-center">
						<div class="login i-block">
							<div class="content-box">
								<div style="background:<?=$topbar;?>;" class="biggest-box">
									<h1 style="color:<?=$topbartext;?>;" class="zero-m text-uppercase"><?php echo $language->translate("LOGOUT");?></h1>
								</div>
								<div class="big-box login-form">
									<form name="log out" id="logout" action="" method="POST">
										<input type="hidden" name="op" value="logout">
										<input type="hidden" name="username"value="<?php echo $_SESSION["username"]; ?>" >
										<!--<center><img src="images/sowwy.png" style="height: 200px;"></center>-->
										<h3 style="color:<?=$topbar;?>;" class="zero-m text-uppercase"><?php echo $language->translate("DO_YOU_WANT_TO_LOGOUT");?></h3>
										<a style="color:<?=$topbar;?>;" id="logoutSubmit" class="i-block" data-dismiss="modal"><?php echo $language->translate("YES_WORD");?></a>
										<a style="color:<?=$topbar;?>;" class="i-block" data-dismiss="modal"><?php echo $language->translate("NO_WORD");?></a>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; endif;?>
		<?php if(isset($_GET['inviteCode'])){ ?>
		<div id="inviteSet" class="login-modal modal fade">
			<div style="background:<?=$sidebar;?>;" class="table-wrapper">
				<div class="table-row">
					<div class="table-cell text-center">
						<button style="color:<?=$topbartext;?>;" type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<div class="login i-block">
							<div class="content-box">
								<div style="background:<?=$topbar;?>;" class="biggest-box">

									<h1 style="color:<?=$topbartext;?>;" class="zero-m text-uppercase"><?php echo $language->translate("WELCOME");?></h1>

								</div>
								<div class="big-box text-left login-form">

									<?php if($USER->error!="") : ?>
									<p class="error">Error: <?php echo $USER->error; ?></p>
									<?php endif; ?>
									<form name="checkInviteForm" id="checkInviteForm" onsubmit="return false;" data-smk-icon="glyphicon-remove-sign">
										<h4 class="text-center"><?php echo $language->translate("CHECK_INVITE");?></h4>
										<div class="form-group">
											<input style="font-size: 400%; height: 100%" type="text" class="form-control yellow-bg text-center text-uppercase" name="inviteCode" placeholder="<?php echo $language->translate("CODE");?>" autocomplete="off" autocorrect="off" autocapitalize="off" value="<?=$inviteCode;?>" maxlength="6" spellcheck="false" autofocus required>
										</div>

										<button id="checkInviteForm_submit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="checkInvite"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("SUBMIT_CODE");?></text></button>

									</form>

									<div style="display: none" id="chooseMethod">
										<h4 class="text-center"><?php echo $language->translate("HAVE_ACCOUNT");?></h4>
										<button id="yesPlexButton" style="background:<?=$topbartext;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("YES");?></text></button>
										<button id="noPlexButton" style="background:<?=$topbartext;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("NO");?></text></button>
									</div>

									<form style="display:none" name="useInviteForm" id="useInviteForm" onsubmit="return false;" data-smk-icon="glyphicon-remove-sign">
										<h4 class="text-center"><?php echo $language->translate("ENTER_PLEX_NAME");?></h4>
										<h4 id="accountMade" style="display: none" class="text-center">
											<span class="label label-primary"><?php echo $language->translate("ACCOUNT_MADE");?></span>
										</h4>
										<div id="accountSubmitted" style="display: none" class="panel panel-success">
											<div class="panel-heading">
												<h3 class="panel-title"><?php echo explosion($language->translate('ACCOUNT_SUBMITTED'), 0);?></h3>
											</div>
											<div class="panel-body">
												<?php echo explosion($language->translate('ACCOUNT_SUBMITTED'), 1);?><br/>
												<?php echo explosion($language->translate('ACCOUNT_SUBMITTED'), 2);?><br/>
												<?php echo explosion($language->translate('ACCOUNT_SUBMITTED'), 3);?>
											</div>
										</div>
										<div class="form-group">
											<input style="font-size: 400%; height: 100%" type="hidden" class="form-control yellow-bg text-center text-uppercase" name="inviteCode" placeholder="<?php echo $language->translate("CODE");?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?=$inviteCode;?>" maxlength="6" required>
											<input type="text" class="form-control material" name="inviteUser" placeholder="<?php echo $language->translate("USERNAME_EMAIL");?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="" autofocus required>
										</div>

										<button id="useInviteForm_submit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="useInvite"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("JOIN");?></text></button>
										<button id="plexYesGoBack" style="background:<?=$topbartext;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>

									</form>

									<form style="display:none" name="joinPlexForm" id="joinPlexForm" onsubmit="return false;" data-smk-icon="glyphicon-remove-sign">
										<h4 class="text-center"><?php echo $language->translate("CREATE_PLEX");?></h4>
										<div class="form-group">
											<input type="text" class="form-control material" name="joinUser" placeholder="<?php echo $language->translate("USERNAME");?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" value="" autofocus required>
											<input type="text" class="form-control material" name="joinEmail" placeholder="<?php echo $language->translate("EMAIL");?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" value="" required>
											<input type="password" class="form-control material" name="joinPassword" placeholder="<?php echo $language->translate("PASSWORD");?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" value="" required>
										</div>

										<button id="joinPlexForm_submit" style="background:<?=$topbar;?>;" type="submit" class="btn btn-block btn-info text-uppercase waves" value="useInvite"><text style="color:<?=$topbartext;?>;"><?php echo $language->translate("SIGN_UP");?></text></button>
										<button id="plexNoGoBack" style="background:<?=$topbartext;?>;" class="btn btn-block btn-info text-uppercase waves"><text style="color:<?=$topbar;?>;"><?php echo $language->translate("GO_BACK");?></text></button>

									</form>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<?php if (file_exists('config/config.php') && $configReady == "Yes" && $tabSetup == "No" && SPLASH == "true" && $splash && count($splash) > 1 && qualifyUser(SPLASHAUTH)) {?>
		<div id="splashScreen" class="splash-modal modal fade">
			<div style="background:<?=$sidebar;?>;" class="table-wrapper big-box">

				<button style="color:<?=$topbartext;?>;" type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<br/><br/>
				<div class="row">
					<div class="col-lg-12">
						<div class="row">
							<?php if($tabSetup == "No") : $tabCount = 1; foreach($splash as $row) :
								$name = str_replace(array(':', '\\', '/', '*'), 'x', $row['ping_url']);?>
							<div class="col-xs-6 col-md-2 col-lg-2 splashContainer" id="splash-<?php echo strtolower($row['name']);?>">
								<li style="list-style-type: none; cursor: pointer;" window="<?=$row['window'];?>" class="splash-item content-box small-box ultra-widget gray-bg" name="<?php echo strtolower($row['name']);?>">
									<div class="w-content">
										<div class="w-icon">
											<center>
												<?php if($row['iconurl']) : ?>
													<i style="">
														<img src="<?=$row['iconurl'];?>" style="height: 100px; margin-top: -10px;" class="">
													</i>
													<?php if($row['ping'] == "true" && $row['ping_url']){ $allPings["image".$name] = $row['ping_url']; ?>
														<ping style="display: block" class="ping-<?=$name;?>"></ping>
													<?php }?>
												<?php else : ?>
													<i style="padding-bottom: 8px" class="fa <?=$row['icon'];?> fa-sm"></i>
													<?php if($row['ping'] == "true" && $row['ping_url']){ $allPings["icon".$name] = $row['ping_url']; ?>
														<ping style="display: block" class="ping-<?=$name;?>"></ping>
													<?php }?>
												<?php endif; ?>
											</center>
										</div>
										<div class="text-center"><span class="text-uppercase w-name elip"><?=$row['name'];?></span></div>
									</div>
								</li>
							</div>
							<?php $tabCount++; endforeach; endif;?>
							<?php if( $USER->authenticated && $USER->role == "admin" ){ ?>
								<div class="col-xs-6 col-md-2 col-lg-2 splashContainer">
									<li style="list-style-type: none; cursor: pointer;" class="splash-item content-box small-box ultra-widget gray-bg" data-title="" name="settings">
										<div class="w-content">
											<div class="w-icon">
												<center>
													<i style="">
														<img src="images/<?=$settingsIcon;?>" style="height: 100px; margin-top: -10px;" class="">
													</i>
												</center>
											</div>
											<div class="text-center"><span class="text-uppercase w-name elip">Settings</span></div>
										</div>
									</li>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<!-- CHAT BOX -->
		<?php if(CHAT == "true" && qualifyUser(CHATAUTH) && $dbcreated){?>
		<div id="main-chat" class="email-content chat-box white-bg" style="z-index:1000000">
			<div class="email-body">
				<div class="email-inner small-box" style="padding: 0">
					<div class="email-inner-section" style="margin-top: 0;">
						<div class="small-box fade in" style="padding: 0">
							<div class="main-wrapper" style="position: initial; left:0;">
								<div id="content">
									<div class="btn-group btn-group-justified grayish-blue-bg">
										<div class="btn-group" role="group">
											<button id="chat-switch-chat" type="button" class="btn waves waves-effect waves-float grayish-blue-bg"><i class="fa fa-comments-o"></i> Chat</button>
										</div>
										<div class="btn-group" role="group">
											<button id="chat-switch-online" type="button" class="btn waves  waves-effect waves-float grayish-blue-bg"><i id="online-count" class="fa fa-users"></i> Online Users</button>
										</div>
										<div class="btn-group" role="group">
											<button id="chat-switch-close" type="button" class="btn waves  waves-effect waves-float grayish-blue-bg"><i class="fa fa-close"></i> Close</button>
										</div>
									</div>

									<div id="chat-chat-div" class="">
										<div class="big-box chat gray-bg">
											<div class="box" style="overflow: hidden; width: auto; height: calc(100vh - <?php echo $chatSize; ?>)">
												<div id="intro">
													<center><img class="logo" alt="logo" src="images/organizr-logo-h.png" style="width: 100%;">
													<br><br>start chatting...</center>
												</div>
												<ul id="messages" class="chat-double chat-container"></ul>
												<ul class="chat-double chat-container" style="padding: 0px;"><li id="istyping"></li></ul>
											</div>
											<br/>
											<input id="message" autofocus onfocus="ensureVisible(this)" type="text" class="form-control gray-bg" placeholder="Enter your text" autocomplete="off"/>
											<audio id="tabalert" preload="auto">
												<source src="chat/audio/newmessage.mp3" type="audio/mpeg">
											</audio>
										</div>
									</div>

									<div id="chat-users-div" class="col-lg-12 gray-bg" style="display: none;">
										<div class="gray-bg"  style="overflow: hidden; width: auto; height: calc(100vh - 62px);">
											<br>
											<div class="content-box">
												<div class="content-title big-box i-block gray-bg">
													<h4 class="zero-m">Online</h4>
												</div>
												<div class="clearfix"></div>
												<div id="onlineusers" class="big-box" style="color:black;"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<?php if($configReady == "Yes"){ ?>
		<!-- New User Menu BOX -->
		<div id="main-user" class="email-content user-box white-bg" style="z-index:1000000">
			<div class="email-body">
				<div class="email-inner small-box" style="padding: 0">
					<div class="email-inner-section" style="margin-top: 0;">
						<div class="small-box fade in" style="padding: 0">
							<div class="main-wrapper" style="position: initial; left:0;">
								<div id="content">
									<div class="btn-group btn-group-justified grayish-blue-bg">
										<div class="btn-group" role="group">
											<button id="reload" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="Refresh Tab" class="user-switch btn waves waves-effect waves-float grayish-blue-bg" style="border-radius:0"><i class="fa fa-refresh"></i></button>
										</div>
										<div class="btn-group" role="group">
											<button id="splitView" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="Close Tab" class="user-switch btn waves waves-effect waves-float grayish-blue-bg"><i class="fa fa-window-close"></i></button>
										</div>
										<div class="btn-group" role="group">
											<button id="popout" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="Open Tab In New Window" class="user-switch btn waves waves-effect waves-float grayish-blue-bg"><i class="fa fa-external-link"></i></button>
										</div>
										<div class="btn-group" role="group">
											<button id="fullscreen" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="Fullscreen" class="fullscreen user-switch btn waves waves-effect waves-float grayish-blue-bg"><i class="fa fa-arrows-alt"></i></button>
										</div>
										<?php if($USER->authenticated){?>
										<div class="btn-group" role="group">
											<button id="editInfo" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="User Information" class="user-switch btn waves waves-effect waves-float grayish-blue-bg"><i class="fa fa-user-circle"></i></button>
										</div>
										<div class="btn-group" role="group">
											<button id="logout" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="Signout" class="logout user-switch btn waves waves-effect waves-float grayish-blue-bg"><i class="fa fa-sign-out"></i></button>
										</div>
										<?php } ?>
										<div class="btn-group" role="group">
											<button id="user-switch-close" type="button" data-toggle="tooltip" data-placement="bottom" data-original-title="Close" class="user-switch btn waves waves-effect waves-float grayish-blue-bg" style="border-radius:0"><i class="fa fa-close"></i></button>
										</div>
									</div>
									<!--EDIT USER -->
									<div id="user-menu-div" class="col-lg-12 gray-bg" style="display: block;">
										<div class="gray-bg"  style="overflow: hidden; width: auto; height: calc(100vh - 62px)">
											<br>
											<div class="content-box" style="left: 0;right: 0;">
												<span style="display: block" class="current-time gray text-center"></span>
											</div>
											<div class="content-box">
												<div class="profile-usertitle">
													<?php if(GRAVATAR == "true") : ?>
													<img src="https://www.gravatar.com/avatar/<?=$userpic;?>?s=100&d=mm" class="img-responsive img-circle center-block" alt="user">
													<?php endif; ?>
													<div class="profile-usertitle-name">
														<?php echo strtoupper($USER->username); ?>
													</div>
													<div class="profile-usertitle-job">
														<?php echo strtoupper($USER->role); ?>
													</div>
												</div>
												<div class="clearfix"></div>
											</div>
											<?php if($USER->authenticated){?>
											<div id="editInfoDiv" class="content-box" style="display: none">

												<div class="profile-usertitle">
													<form class="content-form form-horizontal small-box" name="update" id="update" action="" method="POST">

														<input type="hidden" name="op" value="update"/>
														<input type="hidden" name="sha1" value=""/>
														<input type="hidden" name="password" value="">
														<input type="hidden" name="username" value="<?php echo $USER->username; ?>"/>
														<input type="hidden" name="role" value="<?php echo $USER->role; ?>"/>

														<div class="form-group">
															<label for="user-email" class="col-sm-4 control-label gray"><?php echo $language->translate("EMAIL_ADDRESS");?></label>
															<div class="col-sm-8">
																<input type="email" autocomplete="off" value="<?php echo $USER->email; ?>" class="form-control material gray" name="email" id="user-email" placeholder="<?php echo $language->translate("EMAIL_ADDRESS");?>">
															</div>
														</div>

														<div class="form-group">
															<label for="password1" class="col-sm-4 control-label gray"><?php echo $language->translate("PASSWORD");?></label>
															<div class="col-sm-8">
																<input type="password" autocomplete="off" class="form-control material gray" name="password1" id="user-email" placeholder="<?php echo $language->translate("PASSWORD");?>">
															</div>
														</div>

														<div class="form-group">
															<label for="password2" class="col-sm-4 control-label gray"><?php echo $language->translate("PASSWORD_AGAIN");?></label>
															<div class="col-sm-8">
																<input type="password" autocomplete="off" class="form-control material gray" name="password2" id="user-email" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">
															</div>
														</div>

														<br><br>

														<div class="form-group">

															<input type="button" class="btn btn-success text-uppercase waves-effect waves-float" value="<?php echo $language->translate("UPDATE");?>" onclick="User.processUpdate()"/>
															<button id="goBackButtons" type="button" class="btn btn-primary text-uppercase waves waves-effect waves-float"><?php echo $language->translate("GO_BACK");?></button>

														</div>

													</form>

												</div>
											</div>
											<?php } ?>
											<div id="weather" class="gray"></div>
										</div>
									</div>
									<!-- END EDIT USER -->
									<!--EDIT USER -->
									<div id="user-users-div" class="col-lg-12 gray-bg" style="display: none;">
										<div class="gray-bg"  style="overflow: hidden; width: auto; height: calc(100vh - 62px);">
											<br>
											<div class="content-box">
												<div class="content-title big-box i-block gray-bg">
													<h4 class="zero-m">Online</h4>
												</div>
												<div class="clearfix"></div>
											</div>
										</div>
									</div>
									<!-- END EDIT USER -->
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>

		<!--Scripts-->
		<script src="<?=$baseURL;?>bower_components/jquery/dist/jquery.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/metisMenu/dist/metisMenu.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/Waves/dist/waves.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/moment/min/moment.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/slimScroll/jquery.slimscroll.min.js"></script>
		<script src="<?=$baseURL;?>bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
		<script src="<?=$baseURL;?>bower_components/cta/dist/cta.min.js"></script>

		<!--Menu-->
		<script src="<?=$baseURL;?>js/menu/classie.js"></script>
		<script src="<?=$baseURL;?>js/menu/gnmenu.js"></script>

		<!--Selects-->
		<script src="<?=$baseURL;?>js/selects/selectFx.js"></script>
		<script src="<?=$baseURL;?>bower_components/sweetalert/dist/sweetalert.min.js"></script>

		<script src="<?=$baseURL;?>bower_components/smoke/dist/js/smoke.min.js"></script>

		<!--Notification-->
		<script src="<?=$baseURL;?>js/notifications/notificationFx.js?v=<?php echo INSTALLEDVERSION; ?>"></script>

		<!--Custom Scripts-->
		<script src="<?=$baseURL;?>js/common.js?v=<?php echo INSTALLEDVERSION; ?>"></script>
		<script src="<?=$baseURL;?>js/mousetrap.min.js"></script>
		<script src="<?=$baseURL;?>js/jquery.simpleWeather.js"></script>
		<script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
		<script>
		function getCookie(cname) {
			var name = cname + "=";
			var decodedCookie = decodeURIComponent(document.cookie);
			var ca = decodedCookie.split(';');
			for(var i = 0; i <ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0) == ' ') {
					c = c.substring(1);
				}
				if (c.indexOf(name) == 0) {
					return c.substring(name.length, c.length);
				}
			}
			return "";
		}
		<?php if($configReady == "Yes") {
			if($USER->authenticated){ ?>
				if (localStorageSupport) {
					<?php if(isset($_COOKIE['mpt'])){
						echo 'localStorage.setItem("myPlexAccessToken","'.$_COOKIE['mpt'].'");';
					}
					if(isset($_COOKIE['Auth'])){
						echo 'localStorage.setItem("id_token","'.$_COOKIE['Auth'].'");';
					}?>
				}
		<?php }else{?>
			if (localStorageSupport) {
				localStorage.removeItem("myPlexAccessToken");
				localStorage.removeItem("id_token");
			}
		<?php } } ?>

		</script>
		<?php if(CHAT == "true" && qualifyUser(CHATAUTH)){?>
			<script src="chatjs.php" defer="true"></script>
			<script type="text/javascript">
			var scrolling = function(e, c) {
				e.scrollIntoView();
				if (c < 5) setTimeout(scrolling, 300, e, c + 1);
			};
			var ensureVisible = function(e) {
				setTimeout(scrolling, 300, e, 0);
			};
			var mainchatdiv = document.getElementById('main-chat');
			mainchatdiv.addEventListener('touchmove', function(e) {

				e.preventDefault();

			}, false);
	   	</script>
		<?php }?>
		<?php if(WEATHER == "true" && qualifyUser(WEATHERAUTH)){?>
			<script>
			/* Does your browser support geolocation? */
			if ("geolocation" in navigator) {
				$('#weather').show();
			} else {
				$('#weathern').hide();
			}
			function setWeatherIcon(condid) {
			var icon = '';
				switch(condid) {
					case '0': icon  = 'wi-tornado';
					break;
					case '1': icon = 'wi-storm-showers';
					break;
					case '2': icon = 'wi-tornado';
					break;
					case '3': icon = 'wi-thunderstorm';
					break;
					case '4': icon = 'wi-thunderstorm';
					break;
					case '5': icon = 'wi-snow';
					break;
					case '6': icon = 'wi-rain-mix';
					break;
					case '7': icon = 'wi-rain-mix';
					break;
					case '8': icon = 'wi-sprinkle';
					break;
					case '9': icon = 'wi-sprinkle';
					break;
					case '10': icon = 'wi-hail';
					break;
					case '11': icon = 'wi-showers';
					break;
					case '12': icon = 'wi-showers';
					break;
					case '13': icon = 'wi-snow';
					break;
					case '14': icon = 'wi-storm-showers';
					break;
					case '15': icon = 'wi-snow';
					break;
					case '16': icon = 'wi-snow';
					break;
					case '17': icon = 'wi-hail';
					break;
					case '18': icon = 'wi-hail';
					break;
					case '19': icon = 'wi-cloudy-gusts';
					break;
					case '20': icon = 'wi-fog';
					break;
					case '21': icon = 'wi-fog';
					break;
					case '22': icon = 'wi-fog';
					break;
					case '23': icon = 'wi-cloudy-gusts';
					break;
					case '24': icon = 'wi-cloudy-windy';
					break;
					case '25': icon = 'wi-thermometer';
					break;
					case '26': icon = 'wi-cloudy';
					break;
					case '27': icon = 'wi-night-cloudy';
					break;
					case '28': icon = 'wi-day-cloudy';
					break;
					case '29': icon = 'wi-night-cloudy';
					break;
					case '30': icon = 'wi-day-cloudy';
					break;
					case '31': icon = 'wi-night-clear';
					break;
					case '32': icon = 'wi-day-sunny';
					break;
					case '33': icon = 'wi-night-clear';
					break;
					case '34': icon = 'wi-day-sunny-overcast';
					break;
					case '35': icon = 'wi-hail';
					break;
					case '36': icon = 'wi-day-sunny';
					break;
					case '37': icon = 'wi-thunderstorm';
					break;
					case '38': icon = 'wi-thunderstorm';
					break;
					case '39': icon = 'wi-thunderstorm';
					break;
					case '40': icon = 'wi-storm-showers';
					break;
					case '41': icon = 'wi-snow';
					break;
					case '42': icon = 'wi-snow';
					break;
					case '43': icon = 'wi-snow';
					break;
					case '44': icon = 'wi-cloudy';
					break;
					case '45': icon = 'wi-lightning';
					break;
					case '46': icon = 'wi-snow';
					break;
					case '47': icon = 'wi-thunderstorm';
					break;
					case '3200': icon = 'wi-cloud';
					break;
					default: icon = 'wi-cloud';
					break;
				}

				return '<i class="wi '+icon+' wi-fw"></i>';
			}
			$(document).ready(function() {
				getWeather();
				setInterval(getWeather, 600000);
			});
			function getWeather(){
				navigator.geolocation.getCurrentPosition(function(position) {
					loadWeather(position.coords.latitude+','+position.coords.longitude);
				});
				console.log('grabbing weather');
			}
			function loadWeather(location, woeid) {
				$.simpleWeather({
					location: location,
					woeid: woeid,
					unit: 'f',
					success: function(weather) {
						//html = '<h5 class="text-uppercase text-center">Weather For '+weather.city+', '+weather.region+'</h5>';
						html = '<h5 class="text-center yellow">Current Weather</h5>';
						html += '<div class="content-box ultra-widget yellow-bg">';
						html += '<div class="w-icon right pull-right">'+setWeatherIcon(weather.code)+'</div>';
						html += '<div class="w-descr left pull-left text-center">';
						html += '<span class="w-name">'+weather.temp+'&deg;'+weather.units.temp+' / '+weather.alt.temp+'&deg;C</span><br>';
						html += '<span class="w-name">'+weather.currently+'</span>';
						html += '</div></div>';
						//Forecast
						html += '<div class="content-box big-box"><h4 class="">'+weather.city+', '+weather.region+' Forecast</h4><div class="table-responsive"><table class="table table-striped table-condensed">';
						//html += '<caption>'+weather.city+', '+weather.region+'</caption>';
						html += '<thead><tr><th>Day</th><th>High</th><th>Low</th><th>Weather</th><th>Visual</th></tr></thead><tbody>';
						//Days
						html += '<tr><th scope="row">'+weather.forecast[0].day+'</th><td>'+weather.forecast[0].high+'&deg;'+weather.units.temp+' / '+weather.forecast[0].alt.high+'&deg;C</td>';
						html += '<td>'+weather.forecast[0].low+'&deg;'+weather.units.temp+' / '+weather.forecast[0].alt.low+'&deg;C</td><td>'+weather.forecast[0].text+'</td><td>'+setWeatherIcon(weather.forecast[0].code)+'</td></tr>';
						html += '<tr><th scope="row">'+weather.forecast[1].day+'</th><td>'+weather.forecast[1].high+'&deg;'+weather.units.temp+' / '+weather.forecast[1].alt.high+'&deg;C</td>';
						html += '<td>'+weather.forecast[1].low+'&deg;'+weather.units.temp+' / '+weather.forecast[1].alt.low+'&deg;C</td><td>'+weather.forecast[1].text+'</td><td>'+setWeatherIcon(weather.forecast[1].code)+'</td></tr>';
						html += '<tr><th scope="row">'+weather.forecast[2].day+'</th><td>'+weather.forecast[2].high+'&deg;'+weather.units.temp+' / '+weather.forecast[2].alt.high+'&deg;C</td>';
						html += '<td>'+weather.forecast[2].low+'&deg;'+weather.units.temp+' / '+weather.forecast[2].alt.low+'&deg;C</td><td>'+weather.forecast[2].text+'</td><td>'+setWeatherIcon(weather.forecast[2].code)+'</td></tr>';
						html += '<tr><th scope="row">'+weather.forecast[3].day+'</th><td>'+weather.forecast[3].high+'&deg;'+weather.units.temp+' / '+weather.forecast[3].alt.high+'&deg;C</td>';
						html += '<td>'+weather.forecast[3].low+'&deg;'+weather.units.temp+' / '+weather.forecast[3].alt.low+'&deg;C</td><td>'+weather.forecast[3].text+'</td><td>'+setWeatherIcon(weather.forecast[3].code)+'</td></tr>';
						html += '<tr><th scope="row">'+weather.forecast[4].day+'</th><td>'+weather.forecast[4].high+'&deg;'+weather.units.temp+' / '+weather.forecast[4].alt.high+'&deg;C</td>';
						html += '<td>'+weather.forecast[4].low+'&deg;'+weather.units.temp+' / '+weather.forecast[4].alt.low+'&deg;C</td><td>'+weather.forecast[4].text+'</td><td>'+setWeatherIcon(weather.forecast[4].code)+'</td></tr>';
						//Days End
						html += '</tbody></table></div></div>';

						$("#weather").html(html);
					},
					error: function(error) {
						$("#weather").html('<p>'+error+'</p>');
					}
				});
			}
			</script>
		<?php } ?>
		<script>
		var datetime = null,
        date = null;

		var update = function () {
			date = moment(new Date()).format('llll');
			datetime.html(date);
		};
		 //Current Time
		 datetime = $('.current-time')
		update();
		setInterval(update, 60000);
		console.log(date);
		<?php if($userDevice !== "phone"){?>
		//Tooltips
		$('[data-toggle="tooltip"]').tooltip();
		<?php } ?>
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
		<?php if (file_exists('config/config.php') && $configReady == "Yes" && $tabSetup == "No" && SPLASH == "true") {?>
		$('.splash-modal').modal("show");
		<?php } ?>
		var fixed = document.getElementById('gn-scroller');
		fixed.addEventListener('touchmove', function(e) {

			e.preventDefault();

		}, false);

		function setHeight() {
			windowHeight = $(window).innerHeight();
			$("div").find(".iframe").css('height', windowHeight - <?=$slimBar;?> + "px");
			$('#content').css('height', windowHeight - <?=$slimBar;?> + "px");
		};
		function notify(notifyString, notifyIcon, notifyType, notifyLength, notifyLayout, notifyEffect) {
			var notifyString = notifyString;
			var notifyIcon = notifyIcon;
			var notifyType = notifyType;
			var notifyLength = notifyLength;
			var notifyLayout = notifyLayout;
			var notifyEffect = notifyEffect;
			if (notifyEffect === "slidetop"){
				var addMeesage = '<span class="fa fa-' + notifyIcon + ' fa-2x"></span>' + '<p>' + notifyString + '</p>';
			}else if (notifyEffect === "exploader"){
				var addMeesage = '<span class="fa fa-' + notifyIcon + ' fa-2x pull-left"></span>' + '<p>' + notifyString + '</p>';
			}else if (notifyEffect === "thumbslider"){
				var addMeesage = '<div class="ns-thumb"><img src="images/alert.png"/></div><div class="ns-content"><p>' + notifyString + '</p></div>';
			}else{
				var addMeesage = '<p>' + notifyString + '</p>';
			}

			setTimeout(function () {

				var notification = new NotificationFx({
					message: addMeesage,

					layout: notifyLayout,

					effect: notifyEffect,
					ttl: notifyLength,

					type: notifyType,
					onClose: function () {
						$(".ns-box").fadeOut(400);
					}

				});

				notification.show();

			}, 500);

		}
		$('#registerSubmit').click(function() {
			if ($('#registration').smkValidate()) {
				console.log("validated");
			}
			User.processRegistration();
		});
		$("#editInfo").click(function(){
			$( "div[id^='editInfoDiv']" ).toggle();
			$( "div[id^='buttonsDiv']" ).toggle();
		});
		$("#goBackButtons").click(function(){

			$( "div[id^='editInfoDiv']" ).toggle();
			$( "div[id^='buttonsDiv']" ).toggle();
		});
		$("#welcomeGoBack").click(function(){
			$( "form[id^='login']" ).toggle();
			$( "form[id^='forgotPassword']" ).toggle();
			$("#switchForgot").toggle();
			$("#switchCreateUser").toggle();
			$("#welcomeGoBack").toggle();
		});
		$("#plexNoGoBack").click(function(){
			$("#joinPlexForm").toggle();
			$("#chooseMethod").toggle();
		});
		$("#plexYesGoBack").click(function(){
			$("#useInviteForm").toggle();
			$("#chooseMethod").toggle();
		});
		$("#welcomeGoBack2").click(function(){
			$( "form[id^='login']" ).toggle();
			$("#userPassForm").toggle();
			$("#switchForgot").toggle();
			$("#switchCreateUser").toggle();
			$("#welcomeGoBack2").toggle();
		});
		$("#welcomeGoBack3").click(function(){
			$("#registration").toggle();
			$("#welcomeGoBack3").toggle();
			$( "form[id^='login']" ).toggle();
			$("#switchForgot").toggle();
			$("#switchCreateUser").toggle();
		});
		$("#switchForgot").click(function(){
			$( "form[id^='login']" ).toggle();
			$( "form[id^='forgotPassword']" ).toggle();
			$("#switchForgot").toggle();
			$("#switchCreateUser").toggle();
			$("#welcomeGoBack").toggle();
		});
		$("#switchCreateUser").click(function(){
			$( "form[id^='login']" ).toggle();
			$("#userPassForm").toggle();
			$("#switchForgot").toggle();
			$("#switchCreateUser").toggle();
			$("#welcomeGoBack2").toggle();
		});
		//Sign in
		$(".log-in").click(function(e){
			var e1 = document.querySelector(".log-in"),
				e2 = document.querySelector(".login-modal");
				cta(e1, e2, {relativeToWindow: true}, function () {
				$('.login-modal').modal("show");
			});
			e.preventDefault();
		});
		//InviteCode
		<?php if(isset($_GET['inviteCode'])){ ?>
		$('#inviteSet').modal("show");
		<?php } ?>

		//Logout
		$(".logout").click(function(e){
			$('#main-user').removeClass('email-active');
			var el1 = document.querySelector(".logout"),
			el2 = document.querySelector(".logout-modal");
			cta(el1, el2, {relativeToWindow: true}, function () {
				$('.logout-modal').modal("show");
			});
			e.preventDefault();
		});

		//Members Sidebar
		$(".show-members").click(function(e){
			$('#main-user').toggleClass('email-active');
			if($('#main-chat').hasClass('email-active')){
				$('#main-chat').toggleClass('email-active');
			}
			e.preventDefault();
		});

		$('.close-members-sidebar').click(function(){
			$('#members-sidebar').removeClass('members-sidebar-open');
		});

		$(document).ready(function(){

			<?php
			if($configReady == "Yes"){
				$pingCount = 1; if($USER->authenticated && $USER->role == "admin"){ $pingTimer = "60000"; }else{ $pingTimer = "600000"; }
				foreach($allPings as $type => $ping){
					$name = str_replace(array(':', '\\', '/', '*'), 'x', $ping);
					if(strpos($type, 'image') !== false){ $style = "margin-top:28px"; }else{ $style = ""; }?>
					var  pingTab<?php echo $pingCount;?> = function() {
						//$("ping[class^='ping-<?php echo $name;?>']").load("ajax.php?a=get-ping&url=<?php echo $ping;?>&style=<?php echo $style;?>");
						$.ajax({
							url: 'ajax.php?a=get-ping&url=<?php echo $ping;?>&style=<?php echo $style;?>',
							timeout: 10000,
							type: 'GET',
							success: function(response) {
								var getDiv = response;
								if (typeof getDiv !== 'undefined') {
									$("ping[class^='ping-<?php echo $name;?>']").html($(getDiv).prop('outerHTML'));
								}else{
									console.log('ping-<?php echo $name;?> data was not sufficent or is offline');
								}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								console.error('ping-<?php echo $name;?> could not be updated');
							}
						});
					};
					// Initial Loads
					pingTab<?php echo $pingCount;?>();

					// Interval Loads
					setInterval(function() {
						pingTab<?php echo $pingCount;?>();
					}, <?php echo $pingTimer; ?>);

				<?php $pingCount++; }
			}?>

			//PLEX INVITE SHIT
			$('#checkInviteForm').on('submit', function () {
				ajax_request('POST', 'validate-invite', {
					invitecode: $('#checkInviteForm [name=inviteCode]').val(),
				}).done(function(data){
					var InviteCode = $('#checkInviteForm [name=inviteCode]').val();
					var result = JSON.stringify(data).includes("success");
					if(result === true){
						$('#checkInviteForm').hide();
						$('#chooseMethod').show();//DZ60N2
						$('#useInviteForm [name=inviteCode]').val(InviteCode);
						console.log(result);
						console.log(InviteCode);
					}
				});

			});
			$('#useInviteForm').on('submit', function () {
				ajax_request('POST', 'use-invite', {
					invitecode: $('#useInviteForm [name=inviteCode]').val(),
					inviteuser: $('#useInviteForm [name=inviteUser]').val(),
				}).done(function(data){
					var result = JSON.stringify(data).includes("success");
					console.log(result);
					if(result === true){
						//$('#checkInviteForm').hide();
						//$('#chooseMethod').show();
						$('#accountSubmitted').show();
						$('#useInviteForm_submit').hide();
						$('#plexYesGoBack').hide();
						$('#useInviteForm [name=inviteUser]').hide();
						console.log(result);
					}
				});

			});
			$('#joinPlexForm').on('submit', function () {
				ajax_request('POST', 'join-plex', {
					joinuser: $('#joinPlexForm [name=joinUser]').val(),
					joinemail: $('#joinPlexForm [name=joinEmail]').val(),
					joinpassword: $('#joinPlexForm [name=joinPassword]').val(),
				}).done(function(data){
					var result = JSON.stringify(data).includes("success");
					if(result === true){
						$('#joinPlexForm').hide();
						$('#useInviteForm').show();
						$('#accountMade').show();
						$('input[name=inviteUser]').val($('input[name=joinUser]').val());
						console.log(result);
					}
				});

			});
			$("#yesPlexButton").click(function(){
				$('#chooseMethod').hide();
				$('#useInviteForm').show();
			});
			$("#noPlexButton").click(function(){
				$('#chooseMethod').hide();
				$('#joinPlexForm').show();
			});
			$('#userCreateForm').submit(function(event) {

				var formData = {
					'registerPasswordValue' : $('input[name=registerPasswordValue]').val()
				};

				$.ajax({
					type        : 'POST',
					url         : 'register.php',
					data        : formData,
					dataType    : 'json',
					encode      : true
				})
					.done(function(data) {

						console.log(data);
						if ( ! data.success) {

							$('#userCreateErrors').html('Wrong Password!'); // add the actual error message under our input

						} else {

							$("#userPassForm").toggle();
							$("#registration").toggle();
							$("#welcomeGoBack3").toggle();
							$("#validate").val($('input[name=registerPasswordValue]').val());

						}

					});

				event.preventDefault();
			});
			defaultTab = $("li[class^='tab-item active']").attr("id");
			$("li[class^='tab-item active']").first().find("img").addClass("TabOpened");
			if (defaultTab){
				defaultTab = defaultTab.substr(0, defaultTab.length-1);
			}else{
				defaultTabNone = $("li[class^='tab-item']").attr("id");
				if (defaultTabNone){
					$("li[class^='tab-item']").first().attr("class", "tab-item active");
					$("li[class^='tab-item']").first().find("img").addClass("TabOpened");
					defaultTab = defaultTabNone.substr(0, defaultTabNone.length-1);
				}
			}

			if (defaultTab){
				defaultTabName = $("li[class^='tab-item active']").attr("name");
				startPingTimer = $("li[class^='tab-item active']").find('.startPingTimer');
				console.log('loading default tab: '+defaultTabName);
				if((startPingTimer).length){ //has ping attr
					setTimeout(function(){ //allow it 1.25 secs to check
						defaultPingID = $("li[class^='tab-item active']").find('.pingcheck'); //grab the DOM Element
						if((defaultPingID).length){ //check if element is true
							if(defaultPingID.hasClass('ping-success')){ //check if element has success status
								console.log(defaultTabName+' has responded, proceeding with load');
								$("#content").html('<div class="iframe active" data-content-name="'+defaultTabName+'" data-content-url="'+defaultTab+'"><iframe id="frame-'+defaultTabName+'" scrolling="auto" sandbox="allow-presentation allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" frameborder="0" style="width:100%; height:100%; position: absolute;" src="'+defaultTab+'"></iframe></div>');
								document.getElementById('main-wrapper').focus();
							}else{
								console.log(defaultTabName+' did not respond, cancelling load');
							}
						}else{
							console.log(defaultTabName+' did not have ping value');
						}
					}, 1250);
				}else{
					console.log(defaultTabName+' isn\'t setup with ping, cancelling check on load');
					$("#content").html('<div class="iframe active" data-content-name="'+defaultTabName+'" data-content-url="'+defaultTab+'"><iframe id="frame-'+defaultTabName+'" scrolling="auto" sandbox="allow-presentation allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" frameborder="0" style="width:100%; height:100%; position: absolute;" src="'+defaultTab+'"></iframe></div>');
					document.getElementById('main-wrapper').focus();
				}
			}
			if (defaultTab == null){
				$("div[id^='tabEmpty']").show();
				<?php
				echo "console.log('Need Setup = $needSetup | Config Ready = $configReady');";
				if($needSetup == "No" && $configReady == "Yes"){
					if(!$USER->authenticated){
						echo "$('#loginModal').modal('show');";
					}
				}
				?>
			}
			if ($(location).attr('hash')){
				var getHash = $(location).attr('hash').substr(1).replace("%20", " ").replace("_", " ");

				var gotHash = getHash.toLowerCase();

				var getLiTab = $("li[name^='" + gotHash + "']");
				if(gotHash === "upgrade"){ getLiTab.toggle(); console.log("got it"); }
				getLiTab.trigger("click");


			}

			setHeight();

		});
		<?php if(!empty($USER->info_log)) : ?>

		notify("<?php echo printArray($USER->info_log); ?>","info-circle","notice","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

		<?php endif; ?>

		<?php if(!empty($USER->error_log)) : ?>

		notify("<?php echo printArray($USER->error_log); ?>","exclamation-circle ","error","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

		<?php endif; ?>

		$("li[class^='tab-item']").dblclick(function(){
			var thisidfull = $(this).attr("id");
			var thisid = thisidfull.substr(0, thisidfull.length-1);
			var thisframe = $("#content div[data-content-url='"+thisid+"']").children('iframe');
			$(thisframe).attr('src', $(thisframe).attr('src'));
			var refreshBox = $('#content').find('.active');

			$("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(10);

			setTimeout(function(){

				var refreshPreloader = refreshBox.find('.refresh-preloader'),
				deletedRefreshBox = refreshPreloader.fadeOut(300, function(){

					refreshPreloader.remove();
					$("i[class^='mdi mdi-refresh fa-spin']").attr("class", "mdi mdi-refresh");

				});

			},800);
		});
		$('#reload').on('click tap', function(){

			$("i[class^='mdi mdi-refresh']").attr("class", "mdi mdi-refresh fa-spin");
			$("#main-user").removeClass("email-active");

			var activeFrame = $('#content').find('.active').children('iframe');

			activeFrame.attr('src', activeFrame.attr('src'));

			var refreshBox = $('#content').find('.active');

			$("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(10);

			setTimeout(function(){

				var refreshPreloader = refreshBox.find('.refresh-preloader'),
				deletedRefreshBox = refreshPreloader.fadeOut(300, function(){

					refreshPreloader.remove();
					$("i[class^='mdi mdi-refresh fa-spin']").attr("class", "mdi mdi-refresh");

				});

			},500);
		});
		$('#popout').on('click tap', function(){
			$("#main-user").removeClass("email-active");
			var activeFrame = $('#content').find('.active').children('iframe');
			console.log(activeFrame.attr('src'));
			window.open(activeFrame.attr('src'), '_blank');"reload"
		});
		$('#chat-open').on('click tap', function(){
			$('.chat-box').toggleClass('email-active');
			$(".mdi-forum").removeClass("tada loop-animation new-message");//SET MESSAGE TO ZERO
			if($('.chat-box').hasClass('email-active')){
				$("#message").focus();
			}
			if($('#main-user').hasClass('email-active')){
				$('#main-user').toggleClass('email-active');
			}
		});

		$('#chat-switch-chat').on('click tap', function(){
			$('#chat-chat-div').show();
			$('#chat-users-div').hide();
			$("#message").focus();
		});
		$('#chat-switch-online').on('click tap', function(){
			$('#chat-users-div').show();
			$('#chat-chat-div').hide();
		});
		$('#chat-switch-close').on('click tap', function(){
			$('.chat-box').toggleClass('email-active');
		});
		$('#user-switch-close').on('click tap', function(){
			$('#main-user').toggleClass('email-active');
		});

		$('#reload').on('contextmenu', function(e){

			$("i[class^='mdi mdi-refresh']").attr("class", "mdi mdi-refresh fa-spin");
			$('#main-user').removeClass('email-active');

			var activeFrame = $('#contentRight').find('.active').children('iframe');

			activeFrame.attr('src', activeFrame.attr('src'));

			var refreshBox = $('#contentRight').find('.active');

			$("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(10);

			setTimeout(function(){

				var refreshPreloader = refreshBox.find('.refresh-preloader'),
				deletedRefreshBox = refreshPreloader.fadeOut(300, function(){

					refreshPreloader.remove();
					$("i[class^='mdi mdi-refresh fa-spin']").attr("class", "mdi mdi-refresh");

				});

			},500);
			return false;

		});
		$('#splitView').on('contextmenu', function(e){
			$('#main-user').removeClass('email-active');
			e.stopPropagation();
			//$('#splitView').hide();
			$("#content").attr("class", "content");
			$("li[class^='tab-item rightActive']").attr("class", "tab-item");
			$("#contentRight").html('');
			return false;
		});
		$('#splitView').on('click tap', function(){
			$('#main-user').removeClass('email-active');
			var activeFrame = $('#content').find('.active');
			var getCurrentTab = $("li[class^='tab-item active']");
			getCurrentTab.removeClass('active');
			getCurrentTab.find('img').removeClass('TabOpened');
			$("img[class^='TabOpened']").parents("li").trigger("click");
			activeFrame.remove();
		});
		<?php if($iconRotate == "true") : ?>
		$("li[id^='settings.phpx']").on('click tap', function(){

			$("img[id^='settings-icon']").addClass("fa-spin");
			$("i[id^='settings-icon']").addClass("fa-spin");

			setTimeout(function(){

				$("img[id^='settings-icon']").removeClass("fa-spin");
				$("i[id^='settings-icon']").removeClass("fa-spin");

			},1000);

		});
		<?php endif; ?>

		$('#logoutSubmit').on('click tap', function(){

			$( "#logout" ).submit();

		});
		$(window).resize(function(){
			setHeight();

		});

		$("li[class^='splash-item']").on('click vclick', function(){
			var thisname = $(this).attr("name");
			var splashTab = $("#tabList li[name='" + thisname + "']");
			splashTab.trigger("click");
			$('.splash-modal').modal("hide");

		});

		$("li[class^='tab-item']").on('click vclick', function(){
			var thisidfull = $(this).attr("id");
			var thistitle = $(this).attr("data-title");
			var thisname = $(this).attr("name");

			var thisid = thisidfull.substr(0, thisidfull.length-1);

			var currentframe = $("#content div[data-content-url='"+thisid+"']");

			if (currentframe.attr("class") == "iframe active") {

				console.log(thisid + " is active already");
				setHeight();

			}else if (currentframe.attr("class") == "iframe hidden") {

				console.log(thisid + " - reactivating iFrame");

				$("#content div[class^='iframe active']").attr("class", "iframe hidden");

				currentframe.attr("class", "iframe active");
				document.title = thistitle;
				//window.location.href = '#' + thisname;
				setHeight();

				$("li[class^='tab-item active']").attr("class", "tab-item");

				$(this).attr("class", "tab-item active");

			}else {
				if ($(this).attr("window") == "true") {
					window.open(thisid,'_blank');
				}else {
					console.log(thisid + " - loading new iFrame");

					$("#content div[class^='iframe active']").attr("class", "iframe hidden");

					$( '<div class="iframe active" data-content-name="'+thisname+'" data-content-url="'+thisid+'"><iframe id="frame-'+thisname+'" scrolling="auto" sandbox="allow-presentation allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" frameborder="0" style="width:100%; height:100%; position: absolute;" src="'+thisid+'"></iframe></div>' ).appendTo( "#content" );
					document.title = thistitle;
				   // window.location.href = '#' + thisname;

					setHeight();

					$("li[class^='tab-item active']").attr("class", "tab-item");

					$(this).attr("class", "tab-item active");
					jQuery(this).find("img").addClass("TabOpened");

				}

			}
			$('#main-user').removeClass('email-active');
			$('.chat-box').removeClass('email-active');
		});
		$("li[class^='tab-item']").on('contextmenu', function(e){
			e.stopPropagation();
			$('#splitView').show();
			$("#content").attr("class", "content split");
			var thisidfull = $(this).attr("id");
			var thistitle = $(this).attr("data-title");
			var thisname = $(this).attr("name");

			var thisid = thisidfull.substr(0, thisidfull.length-1);

			var currentframe = $("#contentRight div[data-content-url='"+thisid+"']");

			if (currentframe.attr("class") == "iframe active") {

				console.log(thisid + " is active already");

			}else if (currentframe.attr("class") == "iframe hidden") {

				console.log(thisid + " - reactivating iFrame");

				$("#contentRight div[class^='iframe active']").attr("class", "iframe hidden");

				currentframe.attr("class", "iframe active");
				document.title = thistitle;
				window.location.href = '#' + thisname;
				setHeight();

				$("li[class^='tab-item rightActive']").attr("class", "tab-item");

				$(this).attr("class", "tab-item rightActive");

			}else {
				if ($(this).attr("window") == "true") {
					window.open(thisid,'_blank');
				}else {
					console.log(thisid + " - loading new iFrame");

					$("#contentRight div[class^='iframe active']").attr("class", "iframe hidden");

					$( '<div class="iframe active" data-content-name="'+thisname+'" data-content-url="'+thisid+'"><iframe id="frameRight-'+thisname+'" scrolling="auto" sandbox="allow-presentation allow-forms allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation" allowfullscreen="true" webkitallowfullscreen="true" frameborder="0" style="width:100%; height:100%; position: absolute;" src="'+thisid+'"></iframe></div>' ).appendTo( "#contentRight" );
					document.title = thistitle;
					window.location.href = '#' + thisname;

					setHeight();

					$("li[class^='tab-item rightActive']").attr("class", "tab-item");

					$(this).attr("class", "tab-item rightActive");
					jQuery(this).find("img").addClass("TabOpened");
				}

			}
			$('#main-user').removeClass('email-active');
			$('.chat-box').removeClass('email-active');
			return false;
		});
		Mousetrap.bind('ctrl+shift+up', function(e) {
			var getCurrentTab = $("li[class^='tab-item active']");
			var previousTab = getCurrentTab.prev().attr( "class", "tab-item" );
			previousTab.trigger("click");
			return false;
		});
		Mousetrap.bind('ctrl+shift+down', function(e) {
			var getCurrentTab = $("li[class^='tab-item active']");
			var nextTab = getCurrentTab.next().attr( "class", "tab-item" );
			nextTab.trigger("click");
			return false;
		});

		Mousetrap.bind('s s', function() { $("li[id^='settings.phpx']").trigger("click");  });
		Mousetrap.bind('p p', function() { $("a[class^='fix-nav']").trigger("click");  });
		Mousetrap.bind('m m', function() { $("div[class^='hamburger']").trigger("click");  });
		Mousetrap.bind('r r', function() { $("button[id^='reload']").trigger("click");  });
		Mousetrap.bind('f f', function() { $("button[class^='fullscreen']").trigger("click");  });
		<?php if($tabSetup == "No") : foreach(range(1,$tabCount) as $index) : if ($index == 10) : break; endif;?>
		Mousetrap.bind('ctrl+shift+<?php echo $index; ?>', function() { $("ul[id^='tabList'] li:nth-child(<?php echo $index; ?>)").trigger("click"); });
		<?php endforeach; endif; ?>
		Mousetrap.bind('esc esc', function() {
			$("#content").attr("class", "content");
			$("li[class^='tab-item rightActive']").attr("class", "tab-item");
			$("#contentRight").html('');
		});
		var ref = document.referrer;
		if(ref.indexOf("updated")>=0){

			notify("<?php echo $language->translate('UPDATE_COMPLETE');?>","exclamation-circle ","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

		}
		if(ref.indexOf("submit")>=0){

			notify("<?php echo $language->translate('CUSTOM_COMPLETE');?>","exclamation-circle ","success","5000", "<?=$notifyExplode[0];?>", "<?=$notifyExplode[1];?>");

		}
		</script>

	</body>

</html>
