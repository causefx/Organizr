<?php
// Include functions and user
require_once('functions.php');
require_once("user.php");
$GLOBALS['USER'] = new User('registration_callback');

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
				echo getEmbyStreams(12, EMBYSHOWNAMES, $GLOBALS['USER']->role);
				die();
				break;
			case 'plex-streams':
				qualifyUser(PLEXHOMEAUTH, true);
				echo getPlexStreams(12, PLEXSHOWNAMES, $GLOBALS['USER']->role);
				die();
				break;
			case 'emby-recent':
				qualifyUser(EMBYHOMEAUTH, true);
				echo getEmbyRecent($_GET['type'], 12);
				die();
				break;
			case 'plex-recent':
				qualifyUser(PLEXHOMEAUTH, true);
				echo getPlexRecent(array("movie" => PLEXRECENTMOVIE, "season" => PLEXRECENTTV, "album" => PLEXRECENTMUSIC));
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
			case 'show-image':
				qualifyUser(NZBGETHOMEAUTH, true);
				header('Content-type: image/jpeg');
				echo file_get_contents($_GET['image']);
				die();
				break;
			default:
				sendNotification(false, 'Unsupported Action!');
		}
		break;
	case 'POST':
        // Check if the user is an admin and is allowed to commit values
        switch ($action) {
            case 'tvdb-get':
			 	$response = tvdbGet($_POST['id']);
			 	break;
			case 'search-plex':
			 	$response = searchPlex($_POST['searchtitle']);
			 	break;
			case 'validate-invite':
				$response = inviteCodes("check", $_POST['invitecode']);
				$response['notify'] = sendResult($response, "check", $_POST['checkurl'], "CODE_SUCCESS", "CODE_ERROR");
				break;
			case 'use-invite':
				//$response = inviteCodes("check", $_POST['invitecode']);
				//$response = inviteCodes("use", $_POST['invitecode']);
				if(inviteCodes("check", $_POST['invitecode'])){
					$response = inviteCodes("use", $_POST['invitecode'], $_POST['inviteuser']);
					$response['notify'] = sendResult(plexUserShare($_POST['inviteuser']), "check", $_POST['checkurl'], "INVITE_SUCCESS", "INVITE_ERROR");
				}
				break;
			case 'join-plex':
				$response = plexJoin($_POST['joinuser'], $_POST['joinemail'], $_POST['joinpassword']);
				$response['notify'] = sendResult($response, "check", $_POST['checkurl'], "JOIN_SUCCESS", "JOIN_ERROR");
				break;
            default: // Stuff that you need admin for
                qualifyUser('admin', true);
                switch ($action) {
                    case 'test-email':
                        sendResult(sendTestEmail($_POST['emailto'], $_POST['emailsenderemail'], $_POST['emailhost'], $_POST['emailauth'], $_POST['emailusername'], $_POST['emailpassword'], $_POST['emailtype'], $_POST['emailport'], $_POST['emailsendername']), "flask", "E-Mail TEST", "SUCCESS", "ERROR");
                        break;
					case 'check-url':
                        sendResult(frameTest($_POST['checkurl']), "flask", $_POST['checkurl'], "IFRAME_CAN_BE_FRAMED", "IFRAME_CANNOT_BE_FRAMED");
                        break;
                    case 'upload-images':
                        uploadFiles('images/', array('jpg', 'png', 'svg', 'jpeg', 'bmp', 'gif'));
                        sendNotification(true);
                        break;
                    case 'remove-images':
                        removeFiles('images/'.(isset($_POST['file'])?$_POST['file']:''));
                        sendNotification(true);
                        break;
                    case 'update-config':
                        sendNotification(updateConfig($_POST));
                        break;
                    case 'update-appearance':
                        // Custom CSS Special Case START
                        if (isset($_POST['customCSS'])) {
                            if ($_POST['customCSS']) {
                                write_ini_file($_POST['customCSS'], 'custom.css');
                            } else {
                                unlink('custom.css');
                            }
                            $response['parent']['reload'] = true;
                        }
                        unset($_POST['customCSS']);
                        // Custom CSS Special Case END
                        $response['notify'] = sendNotification(updateDBOptions($_POST),false,false);
                        break;
                    case 'deleteDB':
                        deleteDatabase();
                        sendNotification(true, 'Database Deleted!');
                        break;
                    case 'upgradeInstall':
                        upgradeInstall();
                        $response['notify'] = sendNotification(true, 'Performing Checks', false);
                        $response['tab']['goto'] = 'updatedb.php';
                        break;
                    case 'forceBranchInstall':
                        upgradeInstall(GIT_BRANCH);
                        $response['notify'] = sendNotification(true, 'Performing Checks', false);
                        $response['tab']['goto'] = 'updatedb.php';
                        break;
                    case 'deleteLog':
                        sendNotification(unlink(FAIL_LOG));
                        break;
                    case 'deleteOrgLog':
                        sendNotification(unlink("org.log"));
                        break;
                    case 'submit-tabs':
                        $response['notify'] = sendNotification(updateTabs($_POST) , false, false);
                        $response['show_apply'] = true;
                        break;
                    default:
                        sendNotification(false, 'Unsupported Action!');
                }
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

