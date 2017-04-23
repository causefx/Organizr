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
	sendNotification(false, 'No Action Specified!');
}

// Process Request
$response = array();
switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		switch ($action) {
			case 'emby-image':
				qualifyUser(EMBYHOMEAUTH, true);
				getEmbyImage();
				die();
				break;
			case 'plex-image':
				qualifyUser(PLEXHOMEAUTH, true);
				getPlexImage();
				die();
				break;
			case 'emby-streams':
				qualifyUser(EMBYHOMEAUTH, true);
				echo getEmbyStreams(12);
				die();
				break;
			case 'plex-streams':
				qualifyUser(PLEXHOMEAUTH, true);
				echo getPlexStreams(12);
				die();
				break;
			case 'emby-recent':
				qualifyUser(EMBYHOMEAUTH, true);
				echo getEmbyRecent($_GET['type'], 12);
				die();
				break;
			case 'plex-recent':
				qualifyUser(PLEXHOMEAUTH, true);
				echo getPlexRecent($_GET['type'], 12);
				die();
				break;
			case 'sabnzbd-update':
				qualifyUser(SABNZBDHOMEAUTH, true);
				echo sabnzbdConnect($_GET['list'] ? $_GET['list'] : die('Error!'));
				die();
				break;
			case 'nzbget-update':
				qualifyUser(NZBGETHOMEAUTH, true);
				echo nzbgetConnect($_GET['list'] ? $_GET['list'] : die('Error!'));
				die();
				break;
			default:
				sendNotification(false, 'Unsupported Action!');
		}
		break;
	case 'POST':
		// Check if the user is an admin and is allowed to commit values
		qualifyUser('admin', true);
		switch ($action) {
			case 'upload-images':
				uploadFiles('images/', array('jpg', 'png', 'svg', 'jpeg', 'bmp'));
				sendNotification(true);
				break;
			case 'remove-images':
				removeFiles('images/'.(isset($_POST['file'])?$_POST['file']:''));
				sendNotification(true);
				break;
			case 'update-config':
				sendNotification(updateConfig($_POST));
				break;
			case 'editCSS':
				write_ini_file($_POST["css-show"], "custom.css");
				$response['parent']['reload'] = true;
				break;
			case 'update-appearance':
				sendNotification(updateDBOptions($_POST));
				break;
			case 'deleteDB':
				deleteDatabase();
				sendNotification(true, 'Database Deleted!');
				break;
			case 'upgradeInstall':
				upgradeInstall();
				$response['notify'] = sendNotification(true, 'Performing Checks',false);
				$response['tab']['goto'] = 'updatedb.php';
				break;
			case 'deleteLog':
				sendNotification(unlink(FAIL_LOG));
				break;
			case 'nav-test-tab':
				$response['tab']['goto'] = 'homepage.php';
				break;
			case 'nav-test-tab':
				$response['parent']['goto'] = 'homepage.php';
				break;
			default:
				sendNotification(false, 'Unsupported Action!');
		}
		break;
	case 'PUT':
		sendNotification(false, 'Unsupported Action!');
		break;
	case 'DELETE':
		sendNotification(false, 'Unsupported Action!');
		break;
	default:
		sendNotification(false, 'Unknown Request Type!');
}

if ($response) {
	header('Content-Type: application/json');
	echo json_encode($response);
	die();
} else {
	sendNotification(false, 'Error: No Output Specified!');
}

