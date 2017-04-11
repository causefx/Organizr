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
				getEmbyImage();
				break;
			case 'plex-image':
				getPlexImage();
				break;
			case 'emby-streams':
				echo getEmbyStreams(12);
				break;
			case 'plex-streams':
				echo getPlexStreams(12);
				break;
			case 'emby-recent':
				echo getEmbyRecent($_GET['type'], 12);
				break;
			case 'plex-recent':
				echo getPlexRecent($_GET['type'], 12);
				break;
			case 'sabnzbd-update':
				
				break;
			case 'nzbget-update':
				
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
				header('Content-Type: application/json');
				$notifyExplode = explode("-", NOTIFYEFFECT);
				if (updateConfig($_POST)) {
					$msg = array(
						'html' => '<strong>'.translate("SETTINGS_SAVED").'</strong>',
						'icon' => 'floppy-o',
						'type' => 'success',
						'length' => '5000',
						'layout' => $notifyExplode[0],
						'effect' => $notifyExplode[1],
					);
				} else {
					$msg = array(
						'html' => '<strong>'.translate("SETTINGS__NOT_SAVED").'</strong>',
						'icon' => 'floppy-o',
						'type' => 'failed',
						'length' => '5000',
						'layout' => $notifyExplode[0],
						'effect' => $notifyExplode[1],
					);
				}
				echo json_encode($msg);
				break;
			case 'editCSS':
				write_ini_file($_POST["css-show"], "custom.css");
				echo '<script>window.top.location = window.top.location.href.split(\'#\')[0];</script>';
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



