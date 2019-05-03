<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['Invites'] = array( // Plugin Name
	'name' => 'Invites', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Management', // One to Two Word Description
	'link' => 'https://github.com/PHPMailer/PHPMailer', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	//'fileName'=>'php-mailer.php',
	//'configFile'=>'php-mailer.php',
	//'apiFile'=>'php-mailer.php',
	'idPrefix' => 'INVITES', // html element id prefix
	'configPrefix' => 'INVITES', // config file prefix for array items without the hypen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/invites.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);
// INCLUDE/REQUIRE FILES
// PLUGIN FUNCTIONS
function inviteCodes($array)
{
	$action = isset($array['data']['action']) ? $array['data']['action'] : null;
	$code = isset($array['data']['code']) ? $array['data']['code'] : null;
	$usedBy = isset($array['data']['usedby']) ? $array['data']['usedby'] : null;
	$username = isset($array['data']['username']) ? $array['data']['username'] : null;
	$email = isset($array['data']['email']) ? $array['data']['email'] : null;
	$id = isset($array['data']['id']) ? $array['data']['id'] : null;
	$now = date("Y-m-d H:i:s");
	$currentIP = userIP();
	switch ($action) {
		case "check":
			try {
				$connect = new Dibi\Connection([
					'driver' => 'sqlite3',
					'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
				]);
				$all = $connect->fetch('SELECT * FROM invites WHERE valid = "Yes" AND code = ?', $code);
				return ($all) ? true : false;
			} catch (Dibi\Exception $e) {
				return false;
			}
			break;
		case "use":
			try {
				if (inviteCodes(array('data' => array('action' => 'check', 'code' => $code)))) {
					$connect = new Dibi\Connection([
						'driver' => 'sqlite3',
						'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
					]);
					$connect->query('
	                	UPDATE invites SET', [
						'valid' => 'No',
						'usedby' => $usedBy,
						'dateused' => $now,
						'ip' => $currentIP,
					], '
	                	WHERE code=?', $code);
					writeLog('success', 'Invite Management Function -  Invite Used [' . $code . ']', 'SYSTEM');
					return inviteAction($usedBy, 'share', $GLOBALS['INVITES-type-include']);
				} else {
					return false;
				}
			} catch (Dibi\Exception $e) {
				return false;
			}/*
            if(ENABLEMAIL){
                if (!isset($GLOBALS['USER'])) {
                    require_once("user.php");
                    $GLOBALS['USER'] = new User('registration_callback');
                }
                $emailTemplate = array(
                    'type' => 'mass',
                    'body' => 'The user: {user} has reddemed the code: {inviteCode} his IP Address was '.$currentIP,
                    'subject' => 'Invite Code '.$code.' Has Been Used',
                    'user' => $usedBy,
                    'password' => null,
                    'inviteCode' => $code,
                );
                $emailTemplate = emailTemplate($emailTemplate);
                $subject = $emailTemplate['subject'];
                $body = buildEmail($emailTemplate);
                sendEmail($GLOBALS['USER']->adminEmail, "Admin", $subject, $body);
            }*/
			break;
		default:
			if (qualifyRequest(1)) {
				switch ($action) {
					case "create":
						try {
							$connect = new Dibi\Connection([
								'driver' => 'sqlite3',
								'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
							]);
							$newCode = [
								'code' => $code,
								'email' => $email,
								'username' => $username,
								'valid' => 'Yes',
								'type' => $GLOBALS['INVITES-type-include'],
							];
							$connect->query('INSERT INTO [invites]', $newCode);
							writeLog('success', 'Invite Management Function -  Added Invite [' . $code . ']', $GLOBALS['organizrUser']['username']);
							if ($GLOBALS['PHPMAILER-enabled']) {
								$emailTemplate = array(
									'type' => 'invite',
									'body' => $GLOBALS['PHPMAILER-emailTemplateInviteUser'],
									'subject' => $GLOBALS['PHPMAILER-emailTemplateInviteUserSubject'],
									'user' => $username,
									'password' => null,
									'inviteCode' => $code,
								);
								$emailTemplate = phpmEmailTemplate($emailTemplate);
								$sendEmail = array(
									'to' => $email,
									'subject' => $emailTemplate['subject'],
									'body' => phpmBuildEmail($emailTemplate),
								);
								phpmSendEmail($sendEmail);
							}
							return true;
						} catch (Dibi\Exception $e) {
							writeLog('error', 'Invite Management Function  -  Error [' . $e . ']', 'SYSTEM');
							return false;
						}
						break;
					case "get":
						try {
							$connect = new Dibi\Connection([
								'driver' => 'sqlite3',
								'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
							]);
							$invites = $connect->fetchAll('SELECT * FROM invites');
							return $invites;
						} catch (Dibi\Exception $e) {
							writeLog('error', 'Invite Management Function  -  Error [' . $e . ']', 'SYSTEM');
							return false;
						}
						break;
					case "delete":
						try {
							$connect = new Dibi\Connection([
								'driver' => 'sqlite3',
								'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
							]);
							$connect->query('DELETE FROM invites WHERE id = ?', $id);
							return true;
						} catch (Dibi\Exception $e) {
							writeLog('error', 'Invite Management Function  -  Error [' . $e . ']', 'SYSTEM');
							return false;
						}
						break;
					default:
						return false;
				}
			}
	}
}

/* GET PHPMAILER SETTINGS */
function invitesGetSettings()
{
	if ($GLOBALS['plexID'] !== '' && $GLOBALS['plexToken'] !== '' && $GLOBALS['INVITES-type-include'] == 'plex') {
		$loop = libraryList($GLOBALS['INVITES-type-include'])['libraries'];
		foreach ($loop as $key => $value) {
			$libraryList[] = array(
				'name' => $key,
				'value' => $value
			);
		}
	} else {
		$libraryList = array(
			array(
				'name' => 'Refresh page to update List',
				'value' => '',
				'disabled' => true,
			),
		);
	}
	return array(
		'Backend' => array(
			array(
				'type' => 'select',
				'name' => 'INVITES-type-include',
				'label' => 'Media Server',
				'value' => $GLOBALS['INVITES-type-include'],
				'options' => array(
					array(
						'name' => 'N/A',
						'value' => 'n/a'
					),
					array(
						'name' => 'Plex',
						'value' => 'plex'
					),
					array(
						'name' => 'Emby',
						'value' => 'emby'
					)
				)
			)
		),
		'Plex Settings' => array(
			array(
				'type' => 'password-alt',
				'name' => 'plexToken',
				'label' => 'Plex Token',
				'value' => $GLOBALS['plexToken'],
				'placeholder' => 'Use Get Token Button'
			),
			array(
				'type' => 'password-alt',
				'name' => 'plexID',
				'label' => 'Plex Machine',
				'value' => $GLOBALS['plexID'],
				'placeholder' => 'Use Get Plex Machine Button'
			),
			array(
				'type' => 'select2',
				'class' => 'select2-multiple',
				'id' => 'invite-select',
				'name' => 'INVITES-plexLibraries',
				'label' => 'Libraries',
				'value' => $GLOBALS['INVITES-plexLibraries'],
				'options' => $libraryList
			)
		),
		'Emby Settings' => array(
			array(
				'type' => 'password-alt',
				'name' => 'embyToken',
				'label' => 'Emby API key',
				'value' => $GLOBALS['embyToken'],
				'placeholder' => 'enter key from emby'
			),
			array(
				'type' => 'text',
				'name' => 'embyURL',
				'label' => 'Emby server adress',
				'value' => $GLOBALS['embyURL'],
				'placeholder' => 'localhost:8086'
			),
			array(
				'type' => 'text',
				'name' => 'INVITES-EmbyTemplate',
				'label' => 'Emby User to be used as template for new users',
				'value' => $GLOBALS['INVITES-EmbyTemplate'],
				'placeholder' => 'AdamSmith'
			)
		),
		'FYI' => array(
			array(
				'type' => 'html',
				'label' => 'Note',
				'html' => 'After enabling for the first time, please reload the page - Menu is located under User menu on top right'
			)
		)
	);
}

function inviteAction($username, $action = null, $type = null)
{
	if ($action == null) {
		return false;
	}
	switch ($type) {
		case 'plex':
			if (!empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'])) {
				$url = "https://plex.tv/api/servers/" . $GLOBALS['plexID'] . "/shared_servers/";
				if ($GLOBALS['INVITES-plexLibraries'] !== "") {
					$libraries = explode(',', $GLOBALS['INVITES-plexLibraries']);
				} else {
					$libraries = '';
				}
				$headers = array(
					"Accept" => "application/json",
					"Content-Type" => "application/json",
					"X-Plex-Token" => $GLOBALS['plexToken']
				);
				$data = array(
					"server_id" => $GLOBALS['plexID'],
					"shared_server" => array(
						"library_section_ids" => $libraries,
						"invited_email" => $username
					)
				);
				try {
					switch ($action) {
						case 'share':
							$response = Requests::post($url, $headers, json_encode($data), array());
							break;
						case 'unshare':
							$id = (is_numeric($username) ? $username : convertPlexName($username, "id"));
							$url = $url . $id;
							$response = Requests::delete($url, $headers, array());
							break;
						default:
							return false;
							break;
					}
					if ($response->success) {
						writeLog('success', 'Plex Invite Function - Plex User now has access to system', $username);
						return true;
					} else {
						switch ($response->status_code) {
							case 400:
								writeLog('error', 'Plex Invite Function - Plex User already has access', $username);
								return false;
								break;
							case 401:
								writeLog('error', 'Plex Invite Function - Incorrect Token', 'SYSTEM');
								return false;
								break;
							case 404:
								writeLog('error', 'Plex Invite Function - Libraries not setup correct [' . $GLOBALS['INVITES-plexLibraries'] . ']', 'SYSTEM');
								return false;
								break;
							default:
								writeLog('error', 'Plex Invite Function - An error occurred [' . $response->status_code . ']', $username);
								return false;
								break;
						}
					}
				} catch (Requests_Exception $e) {
					writeLog('error', 'Plex Invite Function - Error: ' . $e->getMessage(), 'SYSTEM');
					return false;
				};
			} else {
				writeLog('error', 'Plex Invite Function - Plex Token/ID not set', 'SYSTEM');
				return false;
			}
			break;
		case 'emby':
			try {
				#add emby user to sytem
				return true;
			} catch (Requests_Exception $e) {
				writeLog('error', 'Emby Invite Function - Error: ' . $e->getMessage(), 'SYSTEM');
				return false;
			}
			break;
		default:
			return false;
			break;
	}
	return false;
}
