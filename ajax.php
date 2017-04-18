<?php
// Include functions if not already included
require_once('functions.php');

// Upgrade environment
upgradeCheck();

// Lazyload settings
$databaseConfig = configLazy('config/config.php');

// Get Action
if (isset($_POST['submit'])) { $action = $_POST['submit']; }
if (isset($_POST['action'])) { $action = $_POST['action']; }
if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['a'])) { $action = $_GET['a']; }
unset($_POST['action']);

// No Action
if (!isset($action)) {
	debug_out('No Action Specified!',1);
}

// Process Request
switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		switch ($action) {
			case 'emby-image':
				qualifyUser(EMBYHOMEAUTH, true);
				getEmbyImage();
				break;
			case 'plex-image':
				qualifyUser(PLEXHOMEAUTH, true);
				getPlexImage();
				break;
			case 'emby-streams':
				qualifyUser(EMBYHOMEAUTH, true);
				echo getEmbyStreams(12);
				break;
			case 'plex-streams':
				qualifyUser(PLEXHOMEAUTH, true);
				echo getPlexStreams(12);
				break;
			case 'emby-recent':
				qualifyUser(EMBYHOMEAUTH, true);
				echo getEmbyRecent($_GET['type'], 12);
				break;
			case 'plex-recent':
				qualifyUser(PLEXHOMEAUTH, true);
				echo getPlexRecent($_GET['type'], 12);
				break;
			case 'sabnzbd-update':
				qualifyUser(NZBGETHOMEAUTH, true);
				
				break;
			case 'nzbget-update':
				qualifyUser(NZBGETHOMEAUTH, true);
				
				break;
			default:
				debug_out('Unsupported Action!',1);
		}
		break;
	case 'POST':
		// Check if the user is an admin and is allowed to commit values
		qualifyUser('admin', true);
		switch ($action) {
			case 'upload-images':
				uploadFiles('images/', array('jpg', 'png', 'svg', 'jpeg', 'bmp'));
				break;
			case 'remove-images':
				removeFiles('images/'.(isset($_POST['file'])?$_POST['file']:''));
				break;
			case 'update-config':
				sendNotification(updateConfig($_POST));
				break;
			case 'editCSS':
				write_ini_file($_POST["css-show"], "custom.css");
				echo '<script>window.top.location = window.top.location.href.split(\'#\')[0];</script>';
				break;
			case 'update-appearance':
				sendNotification(updateDBOptions($_POST));
				break;
			case 'deleteDB':
				deleteDatabase();
				echo json_encode(array('result' => 'success'));
				break;
			case 'upgradeInstall':
				upgradeInstall();
				echo json_encode(array('result' => 'success'));
				break;
			case 'deleteLog':
				sendNotification(unlink(FAIL_LOG));
				break;
			default:
				debug_out('Unsupported Action!',1);
		}
		break;
	case 'PUT':
		debug_out('Unsupported Action!',1);
		break;
	case 'DELETE':
		debug_out('Unsupported Action!',1);
		break;
	default:
		debug_out('Unknown Request Type!',1);
}



