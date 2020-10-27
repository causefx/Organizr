<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['Invites'] = array( // Plugin Name
	'name' => 'Invites', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Management', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'INVITES', // html element id prefix
	'configPrefix' => 'INVITES', // config file prefix for array items without the hypen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/invites.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);

class Invites extends Organizr
{
	public function _invitesPluginGetCodes()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM invites'
			)
		];
		return $this->processQueries($response);
	}
	
	public function _invitesPluginCreateCode($array)
	{
		$code = ($array['code']) ?? null;
		$username = ($array['username']) ?? null;
		$email = ($array['email']) ?? null;
		if (!$code) {
			$this->setAPIResponse('error', 'Code not supplied', 409);
			return false;
		}
		if (!$username) {
			$this->setAPIResponse('error', 'Username not supplied', 409);
			return false;
		}
		if (!$email) {
			$this->setAPIResponse('error', 'Email not supplied', 409);
			return false;
		}
		$newCode = [
			'code' => $code,
			'email' => $email,
			'username' => $username,
			'valid' => 'Yes',
			'type' => $this->config['INVITES-type-include'],
		];
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [invites]',
					$newCode
				)
			)
		];
		$query = $this->processQueries($response);
		if ($query) {
			$this->writeLog('success', 'Invite Management Function -  Added Invite [' . $code . ']', $this->user['username']);
			if ($this->config['PHPMAILER-enabled']) {
				$PhpMailer = new PhpMailer();
				$emailTemplate = array(
					'type' => 'invite',
					'body' => $this->config['PHPMAILER-emailTemplateInviteUser'],
					'subject' => $this->config['PHPMAILER-emailTemplateInviteUserSubject'],
					'user' => $username,
					'password' => null,
					'inviteCode' => $code,
				);
				$emailTemplate = $PhpMailer->_phpMailerPluginEmailTemplate($emailTemplate);
				$sendEmail = array(
					'to' => $email,
					'subject' => $emailTemplate['subject'],
					'body' => $PhpMailer->_phpMailerPluginBuildEmail($emailTemplate),
				);
				$PhpMailer->_phpMailerPluginSendEmail($sendEmail);
			}
			$this->setAPIResponse('success', 'Invite Code: ' . $code . ' has been created', 200);
			return true;
		} else {
			return false;
		}
		
	}
	
	public function _invitesPluginVerifyCode($code)
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM invites WHERE valid = "Yes" AND code = ? COLLATE NOCASE',
					$code
				)
			)
		];
		if ($this->processQueries($response)) {
			$this->setAPIResponse('success', 'Code has been verified', 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'Code is invalid', 401);
			return false;
		}
	}
	
	public function _invitesPluginDeleteCode($code)
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM invites WHERE code = ? COLLATE NOCASE',
				$code
			)
		];
		$info = $this->processQueries($response);
		if (!$info) {
			$this->setAPIResponse('error', 'Code not found', 404);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM invites WHERE code = ? COLLATE NOCASE',
					$code
				)
			)
		];
		$this->setAPIResponse('success', 'Code has been deleted', 200);
		return $this->processQueries($response);
		
	}
	
	public function _invitesPluginUseCode($code, $array)
	{
		$code = ($code) ?? null;
		$usedBy = ($array['usedby']) ?? null;
		$now = date("Y-m-d H:i:s");
		$currentIP = $this->userIP();
		if ($this->_invitesPluginVerifyCode($code)) {
			$updateCode = [
				'valid' => 'No',
				'usedby' => $usedBy,
				'dateused' => $now,
				'ip' => $currentIP
			];
			$response = [
				array(
					'function' => 'query',
					'query' => array(
						'UPDATE invites SET',
						$updateCode,
						'WHERE code=? COLLATE NOCASE',
						$code
					)
				)
			];
			$query = $this->processQueries($response);
			$this->writeLog('success', 'Invite Management Function -  Invite Used [' . $code . ']', 'SYSTEM');
			return $this->_invitesPluginAction($usedBy, 'share', $this->config['INVITES-type-include']);
		} else {
			return false;
		}
	}
	
	public function _invitesPluginLibraryList($type = null)
	{
		switch ($type) {
			case 'plex':
				if (!empty($this->config['plexToken']) && !empty($this->config['plexID'])) {
					$url = 'https://plex.tv/api/servers/' . $this->config['plexID'];
					try {
						$headers = array(
							"Accept" => "application/json",
							"X-Plex-Token" => $this->config['plexToken']
						);
						$response = Requests::get($url, $headers, array());
						libxml_use_internal_errors(true);
						if ($response->success) {
							$libraryList = array();
							$plex = simplexml_load_string($response->body);
							foreach ($plex->Server->Section as $child) {
								$libraryList['libraries'][(string)$child['title']] = (string)$child['id'];
							}
							$libraryList = array_change_key_case($libraryList, CASE_LOWER);
							return $libraryList;
						}
					} catch (Requests_Exception $e) {
						$this->writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
						return false;
					};
				}
				break;
			default:
				# code...
				break;
		}
		return false;
	}
	
	public function _invitesPluginGetSettings()
	{
		if ($this->config['plexID'] !== '' && $this->config['plexToken'] !== '' && $this->config['INVITES-type-include'] == 'plex') {
			$loop = $this->_invitesPluginLibraryList($this->config['INVITES-type-include'])['libraries'];
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
					'value' => $this->config['INVITES-type-include'],
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
					'value' => $this->config['plexToken'],
					'placeholder' => 'Use Get Token Button'
				),
				array(
					'type' => 'button',
					'label' => 'Get Plex Token',
					'icon' => 'fa fa-ticket',
					'text' => 'Retrieve',
					'attr' => 'onclick="showPlexTokenForm(\'#INVITES-settings-items [name=plexToken]\')"'
				),
				array(
					'type' => 'password-alt',
					'name' => 'plexID',
					'label' => 'Plex Machine',
					'value' => $this->config['plexID'],
					'placeholder' => 'Use Get Plex Machine Button'
				),
				array(
					'type' => 'button',
					'label' => 'Get Plex Machine',
					'icon' => 'fa fa-id-badge',
					'text' => 'Retrieve',
					'attr' => 'onclick="showPlexMachineForm(\'#INVITES-settings-items [name=plexID]\')"'
				),
				array(
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => 'invite-select',
					'name' => 'INVITES-plexLibraries',
					'label' => 'Libraries',
					'value' => $this->config['INVITES-plexLibraries'],
					'options' => $libraryList
				),
				array(
					'type' => 'text',
					'name' => 'INVITES-plex-tv-labels',
					'label' => 'TV Labels (comma separated)',
					'value' => $this->config['INVITES-plex-tv-labels'],
					'placeholder' => 'All'
				),
				array(
					'type' => 'text',
					'name' => 'INVITES-plex-movies-labels',
					'label' => 'Movies Labels (comma separated)',
					'value' => $this->config['INVITES-plex-movies-labels'],
					'placeholder' => 'All'
				),
				array(
					'type' => 'text',
					'name' => 'INVITES-plex-music-labels',
					'label' => 'Music Labels (comma separated)',
					'value' => $this->config['INVITES-plex-music-labels'],
					'placeholder' => 'All'
				),
			),
			'Emby Settings' => array(
				array(
					'type' => 'password-alt',
					'name' => 'embyToken',
					'label' => 'Emby API key',
					'value' => $this->config['embyToken'],
					'placeholder' => 'enter key from emby'
				),
				array(
					'type' => 'text',
					'name' => 'embyURL',
					'label' => 'Emby server adress',
					'value' => $this->config['embyURL'],
					'placeholder' => 'localhost:8086'
				),
				array(
					'type' => 'text',
					'name' => 'INVITES-EmbyTemplate',
					'label' => 'Emby User to be used as template for new users',
					'value' => $this->config['INVITES-EmbyTemplate'],
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
	
	public function _invitesPluginAction($username, $action = null, $type = null)
	{
		if ($action == null) {
			$this->setAPIResponse('error', 'No Action supplied', 409);
			return false;
		}
		switch ($type) {
			case 'plex':
				if (!empty($this->config['plexToken']) && !empty($this->config['plexID'])) {
					$url = "https://plex.tv/api/servers/" . $this->config['plexID'] . "/shared_servers/";
					if ($this->config['INVITES-plexLibraries'] !== "") {
						$libraries = explode(',', $this->config['INVITES-plexLibraries']);
					} else {
						$libraries = '';
					}
					if ($this->config['INVITES-plex-tv-labels'] !== "") {
						$tv_labels = "label=" . $this->config['INVITES-plex-tv-labels'];
					} else {
						$tv_labels = "";
					}
					if ($this->config['INVITES-plex-movies-labels'] !== "") {
						$movies_labels = "label=" . $this->config['INVITES-plex-movies-labels'];
					} else {
						$movies_labels = "";
					}
					if ($this->config['INVITES-plex-music-labels'] !== "") {
						$music_labels = "label=" . $this->config['INVITES-plex-music-labels'];
					} else {
						$music_labels = "";
					}
					$headers = array(
						"Accept" => "application/json",
						"Content-Type" => "application/json",
						"X-Plex-Token" => $this->config['plexToken']
					);
					$data = array(
						"server_id" => $this->config['plexID'],
						"shared_server" => array(
							"library_section_ids" => $libraries,
							"invited_email" => $username
						),
						"sharing_settings" => array(
							"filterTelevision" => $tv_labels,
							"filterMovies" => $movies_labels,
							"filterMusic" => $music_labels
						)
					);
					try {
						switch ($action) {
							case 'share':
								$response = Requests::post($url, $headers, json_encode($data), array());
								break;
							case 'unshare':
								$id = (is_numeric($username) ? $username : $this->_invitesPluginConvertPlexName($username, "id"));
								$url = $url . $id;
								$response = Requests::delete($url, $headers, array());
								break;
							default:
								$this->setAPIResponse('error', 'No Action supplied', 409);
								return false;
						}
						if ($response->success) {
							$this->writeLog('success', 'Plex Invite Function - Plex User now has access to system', $username);
							$this->setAPIResponse('success', 'Plex User now has access to system', 200);
							return true;
						} else {
							switch ($response->status_code) {
								case 400:
									$this->writeLog('error', 'Plex Invite Function - Plex User already has access', $username);
									$this->setAPIResponse('error', 'Plex User already has access', 409);
									return false;
								case 401:
									$this->writeLog('error', 'Plex Invite Function - Incorrect Token', 'SYSTEM');
									$this->setAPIResponse('error', 'Incorrect Token', 409);
									return false;
								case 404:
									$this->writeLog('error', 'Plex Invite Function - Libraries not setup correct [' . $this->config['INVITES-plexLibraries'] . ']', 'SYSTEM');
									$this->setAPIResponse('error', 'Libraries not setup correct', 409);
									return false;
								default:
									$this->writeLog('error', 'Plex Invite Function - An error occurred [' . $response->status_code . ']', $username);
									$this->setAPIResponse('error', 'An Error Occurred', 409);
									return false;
							}
						}
					} catch (Requests_Exception $e) {
						$this->writeLog('error', 'Plex Invite Function - Error: ' . $e->getMessage(), 'SYSTEM');
						$this->setAPIResponse('error', $e->getMessage(), 409);
						return false;
					};
				} else {
					$this->writeLog('error', 'Plex Invite Function - Plex Token/ID not set', 'SYSTEM');
					$this->setAPIResponse('error', 'Plex Token/ID not set', 409);
					return false;
				}
				break;
			case 'emby':
				try {
					#add emby user to system
					$this->setAPIResponse('success', 'User now has access to system', 200);
					return true;
				} catch (Requests_Exception $e) {
					$this->writeLog('error', 'Emby Invite Function - Error: ' . $e->getMessage(), 'SYSTEM');
					$this->setAPIResponse('error', $e->getMessage(), 409);
					return false;
				}
			default:
				return false;
		}
		return false;
	}
	
	public function _invitesPluginConvertPlexName($user, $type)
	{
		$array = $this->userList('plex');
		switch ($type) {
			case "username":
			case "u":
				$plexUser = array_search($user, $array['users']);
				break;
			case "id":
				if (array_key_exists(strtolower($user), $array['users'])) {
					$plexUser = $array['users'][strtolower($user)];
				}
				break;
			default:
				$plexUser = false;
		}
		return (!empty($plexUser) ? $plexUser : null);
	}
	
}
