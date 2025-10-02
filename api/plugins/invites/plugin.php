<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['Invites'] = array( // Plugin Name
	'name' => 'Invites', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Management', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'INVITES', // html element id prefix
	'configPrefix' => 'INVITES', // config file prefix for array items without the hyphen
	'version' => '1.1.0', // SemVer of plugin
	'image' => 'api/plugins/invites/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/invites/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

class Invites extends Organizr
{
	public function __construct()
	{
		parent::__construct();
		$this->_pluginUpgradeCheck();
	}

	public function _pluginUpgradeCheck()
	{
		if ($this->hasDB()) {
			$compare = new Composer\Semver\Comparator;
			$oldVer = $this->config['INVITES-dbVersion'];
			// Upgrade check start for version below
			$versionCheck = '1.1.0';
			if ($compare->lessThan($oldVer, $versionCheck)) {
				$oldVer = $versionCheck;
				$this->_pluginUpgradeToVersion($versionCheck);
			}
			// End Upgrade check start for version above
			// Update config.php version if different to the installed version
			if ($GLOBALS['plugins']['Invites']['version'] !== $this->config['INVITES-dbVersion']) {
				$this->updateConfig(array('INVITES-dbVersion' => $oldVer));
				$this->setLoggerChannel('Invites Plugin');
				$this->logger->debug('Updated INVITES-dbVersion to ' . $oldVer);
			}
			return true;
		}
	}

	public function _pluginUpgradeToVersion($version = '1.1.0')
	{
		switch ($version) {
			case '1.1.0':
				$this->_addInvitedByColumnToDatabase();
				break;
		}
		$this->setResponse(200, 'Ran plugin update function for version: ' . $version);
		return true;
	}

	public function _addInvitedByColumnToDatabase()
	{
		$addColumn = $this->addColumnToDatabase('invites', 'invitedby', 'TEXT');
		$this->setLoggerChannel('Invites Plugin');
		if ($addColumn) {
			$this->logger->info('Updated Invites Database');
		} else {
			$this->logger->warning('Could not update Invites Database');
		}
	}

	public function _invitesPluginGetCodes()
	{
		if ($this->qualifyRequest(1, false)) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => 'SELECT * FROM invites'
				)
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM invites WHERE invitedby = ?',
						$this->user['username']
					)
				)
			];
		}

		return $this->processQueries($response);
	}

	public function _invitesPluginCreateCode($array)
	{
		$code = ($array['code']) ?? null;
		$username = ($array['username']) ?? null;
		$email = ($array['email']) ?? null;
		$invites = $this->_invitesPluginGetCodes();
		$inviteCount = count($invites);
		if (!$this->qualifyRequest(1, false)) {
			if ($this->config['INVITES-maximum-invites'] != 0 && $inviteCount >= $this->config['INVITES-maximum-invites']) {
				$this->setAPIResponse('error', 'Maximum number of invites reached', 409);
				return false;
			}
		}
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
			'invitedby' => $this->user['username'],
			'date' => gmdate('Y-m-d H:i:s')
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
			$this->setLoggerChannel('Invites')->info('Added Invite [' . $code . ']');
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
		if ($this->qualifyRequest(1, false)) {
			$response = [
				array(
					'function' => 'fetch',
					'query' => array(
						'SELECT * FROM invites WHERE code = ? COLLATE NOCASE',
						$code
					)
				)
			];
		} else {
			if ($this->config['INVITES-allow-delete']) {
				$response = [
					array(
						'function' => 'fetch',
						'query' => array(
							'SELECT * FROM invites WHERE invitedby = ? AND code = ? COLLATE NOCASE',
							$this->user['username'],
							$code
						)
					)
				];
			} else {
				$this->setAPIResponse('error', 'You are not permitted to delete invites.', 409);
				return false;
			}
		}
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
		$mail = $this->_getEmailFronInviteCode($code);
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
			$this->setLoggerChannel('Invites')->info('Invite Used [' . $code . '] by ' . $usedBy);
			return $this->_invitesPluginAction($usedBy, 'share', $this->config['INVITES-type-include'], $mail);
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
							if ($this->config['INVITES-plexLibraries'] !== '') {
								$noLongerId = 0;
								$libraries = explode(',', $this->config['INVITES-plexLibraries']);
								foreach ($libraries as $child) {
									if (!$this->search_for_value($child, $libraryList)) {
										$libraryList['libraries']['No Longer Exists - ' . $noLongerId] = $child;
										$noLongerId++;
									}
								}
							}
							$libraryList = array_change_key_case($libraryList, CASE_LOWER);
							return $libraryList;
						}
					} catch (Requests_Exception $e) {
						$this->setLoggerChannel('Plex')->error($e);
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

		$komgaRoles = $this->_getKomgaRoles();
		$komgalibrary = $this->_getKomgaLibraries();
		$nextcloudRoles = $this->_getNextcloudGroups();

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
				),
				array(
					'type' => 'select',
					'name' => 'INVITES-Auth-include',
					'label' => 'Minimum Authentication',
					'value' => $this->config['INVITES-Auth-include'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'switch',
					'name' => 'INVITES-allow-delete-include',
					'label' => 'Allow users to delete invites',
					'help' => 'This must be disabled to enforce invitation limits.',
					'value' => $this->config['INVITES-allow-delete-include']
				),
				array(
					'type' => 'number',
					'name' => 'INVITES-maximum-invites',
					'label' => 'Maximum number of invites permitted for users.',
					'help' => 'Set to 0 to disable the limit.',
					'value' => $this->config['INVITES-maximum-invites'],
					'placeholder' => '0'
				),
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
					'attr' => 'onclick="PlexOAuth(oAuthSuccess,oAuthError, oAuthMaxRetry, null, null, \'#INVITES-settings-items [name=plexToken]\')"'
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
					'id' => 'invite-select-' . $this->random_ascii_string(6),
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
				array(
					'type' => 'switch',
					'name' => 'INVITES-add-plex-home',
					'label' => 'When user subscribe add him to Plex Home',
					'value' => $this->config['INVITES-add-plex-home']
				)
			),
			'Komga Settings' => array(
				array(
					'type' => 'switch',
					'name' => 'INVITES-komga-enabled',
					'label' => 'Enable Komga for auto create account',
					'value' => $this->config['INVITES-komga-enabled'],
				),
				array(
					'type' => 'input',
					'name' => 'INVITES-komga-uri',
					'label' => 'URL',
					'value' => $this->config['INVITES-komga-uri'],
					'placeholder' => 'http(s)://hostname:port'
				),
				array(
					'type' => 'password-alt',
					'name' => 'INVITES-komga-api-key',
					'label' => 'Komga Api Key',
					'value' => $this->config['INVITES-komga-api-key']
				),
				array(
					'type' => 'password-alt',
					'name' => 'INVITES-komga-default-user-password',
					'label' => 'Default password for new user',
					'value' => $this->config['INVITES-komga-default-user-password']
				),
				array(
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => 'INVITES-select-' . $this->random_ascii_string(6),
					'name' => 'INVITES-komga-roles',
					'label' => 'Roles',
					'value' => $this->config['INVITES-komga-roles'],
					'options' => $komgaRoles
				),
				array(
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => 'INVITES-select-' . $this->random_ascii_string(6),
					'name' => 'INVITES-komga-libraryIds',
					'label' => 'Libraries',
					'value' => $this->config['INVITES-komga-libraryIds'],
					'options' => $komgalibrary
				)
			),
			'Nextcloud Settings' => array(
				array(
					'type' => 'switch',
					'name' => 'INVITES-nextcloud-enabled',
					'label' => 'Enable Nextcloud for auto create account',
					'value' => $this->config['INVITES-nextcloud-enabled'],
				),
				array(
					'type' => 'switch',
					'name' => 'INVITES-nextcloud-plex-sso',
					'label' => 'Enable if you have Plex SSO app installed on Nextcloud',
					'value' => $this->config['INVITES-nextcloud-plex-sso'],
				),
				array(
					'type' => 'input',
					'name' => 'INVITES-nextcloud-url',
					'label' => 'Nextcloud URI',
					'value' => $this->config['INVITES-nextcloud-url']
				),
				array(
					'type' => 'input',
					'name' => 'INVITES-nextcloud-admin-user',
					'label' => 'Nextcloud Admin User',
					'value' => $this->config['INVITES-nextcloud-admin-user']
				),
				array(
					'type' => 'password',
					'name' => 'INVITES-nextcloud-admin-password',
					'label' => 'Nextcloud Admin Password',
					'value' => $this->config['INVITES-nextcloud-admin-password']
				),
				array(
					'type' => 'input',
					'name' => 'INVITES-nextcloud-quota',
					'label' => 'Storage quota for user after subscription (empty for no-limit)',
					'value' => $this->config['INVITES-nextcloud-quota'],
					'placeholder' => '10GB'
				),
				array(
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => 'INVITES-select-' . $this->random_ascii_string(6),
					'name' => 'INVITES-nextcloud-groups-member',
					'label' => 'Nextcloud Groups after subscription',
					'value' => $this->config['INVITES-nextcloud-groups-member'],
					'options' => $nextcloudRoles
				)
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
					'html' => '<span lang="en">After enabling for the first time, please reload the page - Menu is located under User menu on top right</span>'
				)
			)
		);
	}

	public function _invitesPluginAction($username, $action = null, $type = null, $mail)
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

								if($this->config['INVITES-add-plex-home']) {
									$this->_addUserPlexHome($mail);
								}

								if($this->config['INVITES-komga-enabled']) {
									$this->_createKomgaAccount($mail);
								}

								if ($this->config['INVITES-nextcloud-enabled']) {
									$nextcloudAccountCreated = $this->_createNextcloudAccount($mail, $username);
								}

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
							$this->setLoggerChannel('Invites')->info('Plex User now has access to system');
							$this->setAPIResponse('success', 'Plex User now has access to system', 200);
							return true;
						} else {
							switch ($response->status_code) {
								case 400:
									$this->setLoggerChannel('Plex')->warning('Plex User already has access');
									$this->setAPIResponse('success', 'Plex User already has access', 200);
									return true;
								case 401:
									$this->setLoggerChannel('Plex')->warning('Incorrect Token');
									$this->setAPIResponse('error', 'Incorrect Token', 409);
									return false;
								case 404:
									$this->setLoggerChannel('Plex')->warning('Libraries not setup correctly');
									$this->setAPIResponse('error', 'Libraries not setup correct', 409);
									return false;
								default:
									$this->setLoggerChannel('Plex')->warning('An error occurred [' . $response->status_code . ']');
									$this->setAPIResponse('error', 'An Error Occurred', 409);
									return false;
							}
						}
					} catch (Requests_Exception $e) {
						$this->setLoggerChannel('Plex')->error($e);
						$this->setAPIResponse('error', $e->getMessage(), 409);
						return false;
					}
				} else {
					$this->setLoggerChannel('Plex')->warning('Plex Token/ID not set');
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
					$this->setLoggerChannel('Emby')->error($e);
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

	/**
	 * Creates a new Komga user account using the provided email address.
	 *
	 * @param string $email The email address for the new Komga user account.
	 * @return bool True if the account was successfully created, false otherwise.
	 */
	private function _createKomgaAccount($email) {
        $this->logger->info('Try to create Komga account for ' . $email);


		if(!$this->_checkKomgaVar()) {
			return false;
		}

		if (empty($email)) {
			$this->setLoggerChannel('Invites')->info('User email empty');
			return false;
		}

		$endpoint = rtrim($this->config['INVITES-komga-uri'], '/') . '/api/v2/users';
		$apiKey = $this->config['INVITES-komga-api-key'];
		$password =  $this->decrypt($this->config['INVITES-komga-default-user-password']);

		$rolesStr = $this->config['INVITES-komga-roles'] ?? '';
		$roles = array_values(array_filter(array_map('trim', explode(';', $rolesStr))));

		$libIdsStr = $this->config['INVITES-komga-libraryIds'] ?? '';
		$libraryIds = array_values(array_filter(array_map('trim', explode(';', $libIdsStr))));

		$headers = array(
			'accept' => 'application/json',
			'X-API-Key' => $apiKey,
			'Content-Type' => 'application/json'
		);

		$payload = array(
			'email' => $email,
			'password' => $password,
			'roles' => $roles,
			'sharedLibraries' => array(
				'all' => false,
				'libraryIds' => $libraryIds
			)
		);

		try {
			$response = Requests::post($endpoint, $headers, json_encode($payload));
			if ($response->success) {
				$this->setLoggerChannel('Komga')->info('User created ' . $email . ' with roles: ' . implode(',', $roles) . ' and libraries: ' . implode(',', $libraryIds));
				return true;
			}
			$this->setLoggerChannel('Komga')->warning('User not created ' . $email . ' HTTP ' . $response->status_code);
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Komga')->error('User not created ' . $email . ' Requests_Exception: ' . $e->getMessage());
		}
		return false;
	}

    /**
     * Retrieves a list of Komga roles
     *
     * @return array An array of associative arrays, each containing 'name' and 'value' for a Komga role.
     */
    public function _getKomgaRoles() {
        $komgaRoles = array();

        $roleNames = array(
            'ADMIN' => 'Administrator',
            'FILE_DOWNLOAD' => 'File download',
            'PAGE_STREAMING' => 'Page streaming',
            'KOBO_SYNC' => 'Kobo Sync',
            'KOREADER_SYNC' => 'Koreader Sync'
        );

        foreach ($roleNames as $value => $name) {
            $komgaRoles[] = array(
                'name' => $name,
                'value' => $value
            );
        }
        return $komgaRoles;
    }

	/**
     * Fetches the list of Komga libraries from the Komga API.
     *
     * @return array|false Returns an array of libraries with 'name' and 'id' on success, or false on failure.
     */
    public function _getKomgaLibraries() {
        $this->logger->info('Try to fetch Komga libraries');

		if(!$this->_checkKomgaVar()) {
			return false;
		}

        $endpoint = rtrim($this->config['komgaURL'], '/') . '/api/v1/libraries';
        $apiKey = $this->config['INVITES-komga-api-key'];

        $libraryListDefault = array(
			array(
				'name' => 'Refresh page to update List',
				'value' => '',
				'disabled' => true,
			),
		);

        $headers = array(
            'accept' => 'application/json',
            'X-API-Key' => $apiKey
        );

        try {
            $response = Requests::get($endpoint, $headers);
            if ($response->success) {
                $libraries = json_decode($response->body, true);
                // Komga retourne un tableau d'objets librairie
                $result = array();
                foreach ($libraries as $library) {
                    $result[] = array(
                        'name' => $library['name'],
                        'id' => $library['id']
                    );
                }
                $this->logger->info('Fetched libraries: ' . json_encode($result));
                if(!empty($result)) {
                    return $result;
                }
            } else {
                $this->logger->warning("Error HTTP ".$response->status_code.' body='.$response->body);
            }
        } catch (Requests_Exception $e) {
            $this->logger->warning("Exception: " . $e->getMessage());
        }
        return $libraryListDefault;
    }

	/**
	 * Fetches the list of Nextcloud groups using the configured Nextcloud admin credentials.
	*
		* @return array|false Returns an array of groups with 'name' and 'value' keys on success,
		*                     or false on failure.
		*/
	public function _getNextcloudGroups() {
		$this->logger->info('Try to fetch Nextcloud groups');

		if(!$this->_checkNextcloudVar()) {
			return false;
		}

		$url = rtrim($this->config['INVITES-nextcloud-url'], '/') . '/ocs/v1.php/cloud/groups';
		$adminUser = $this->config['INVITES-nextcloud-admin-user'];
		$adminPass = $this->decrypt($this->config['INVITES-nextcloud-admin-password']);
		$headers = array(
			'OCS-APIRequest' => 'true',
			'Accept' => 'application/json',
		);

		try {
			$options = array(
				'auth' => array($adminUser, $adminPass),
			);
			$response = Requests::get($url, $headers, $options);
			if ($response->success) {
				$body = json_decode($response->body, true);
				if (isset($body['ocs']['data']['groups'])) {
					$this->logger->info('Fetched groups: ' . implode(', ', $body['ocs']['data']['groups']));
					$groups = $body['ocs']['data']['groups'];
					$result = array();
					foreach ($groups as $group) {
						$result[] = array(
							'name' => $group,
							'value' => $group
						);
					}
					return $result;
				} else {
					$this->logger->warning('Groups not found in response');
				}
			} else {
				$this->logger->warning("Error HTTP ".$response->status_code.' body='.$response->body);
			}
		} catch (Requests_Exception $e) {
			$this->logger->warning("Exception: " . $e->getMessage());
		}
		return false;
	}

	/**
	 * Checks if all required Nextcloud configuration variables are set.
	 *
	 * @return bool Returns true if all required Nextcloud configuration variables are set; false otherwise.
	 */
	public function _checkNextcloudVar() {
		if (empty($this->config['INVITES-nextcloud-enabled'])) {
			$this->logger->info('Nextcloud disabled in config');
			return false;
		}
		if (empty($this->config['INVITES-nextcloud-url'])) {
			$this->logger->info('Nextcloud URL missing');
			return false;
		}
		if (empty($this->config['INVITES-nextcloud-admin-user']) || empty($this->config['INVITES-nextcloud-admin-password'])) {
			$this->logger->info('Nextcloud admin credentials missing');
			return false;
		}
		return true;
	}

	/**
	 * Checks if all required komga configuration variables are set.
	 *
	 * @return bool Returns true if all required komga configuration variables are set; false otherwise.
	 */
	public function _checkKomgaVar() {
		if (empty($this->config['INVITES-komga-uri'])) {
			$this->setLoggerChannel('Invites')->info('Komga uri is missing');
			return false;
		}
		if (empty($this->config['INVITES-komga-api-key'])) {
			$this->setLoggerChannel('Invites')->info('Komga api key is missing');
			return false;
		}
		if (empty($this->config['INVITES-komga-roles'])) {
			$this->setLoggerChannel('Invites')->info('Komga roles empty');
			return false;
		}
		if (empty($this->config['INVITES-komga-libraryIds'])) {
			$this->setLoggerChannel('Invites')->info('Komga library empty');
			return false;
		}
		if (empty($this->config['INVITES-komga-default-user-password'])) {
			$this->setLoggerChannel('Invites')->info('Komga default user password empty');
			return false;
		}
		return true;
	}



	/**
	* Creates a Nextcloud account for a user based on their email and other parameters.
	*
	* @param string $email                The email address of the user to create in Nextcloud.
	* @param string $displayName          The display name for the Nextcloud user.
	* @param string $nextcloudGroupsMember A semicolon-separated list of Nextcloud groups to add the user to.
	* @param string $nextcloudQuota       The storage quota to assign to the user (e.g., "5GB").
	*
	* @return bool Returns true if the account was successfully created, false otherwise.
	*/
	public function _createNextcloudAccount($email, $displayName) {
		$this->logger->info('Try to create Nextcloud account');

		if(!$this->_checkNextcloudVar()) {
			return false;
		}

		$nextcloudGroupsMember = $this->config['INVITES-nextcloud-groups-member'] ?? '';
		$nextcloudQuota = $this->config['INVITES-nextcloud-quota'] ?? '';

		$userid = $email;
		if($this->config['INVITES-nextcloud-plex-sso']) {
			$plexUserId = $this->_getPlexUserIdByEmail($email);
			$this->logger->warning('plexUserId=' . $plexUserId);

			if (!empty($plexUserId)) {
				$userid = 'PlexTv-' . $plexUserId;
			}
		}

		try {
			$password = bin2hex(random_bytes(12));
		} catch (\Throwable $e) {
			$password = bin2hex(openssl_random_pseudo_bytes(12));
		}

		if (empty($password)) {
			$this->logger->warning('Error generating password');
			return false;
		}

		$url = rtrim($this->config['INVITES-nextcloud-url'], '/') . '/ocs/v1.php/cloud/users';
		$adminUser = $this->config['INVITES-nextcloud-admin-user'];
		$adminPass = $this->decrypt($this->config['INVITES-nextcloud-admin-password']);
		$headers = array(
			'OCS-APIRequest' => 'true',
			'Accept' => 'application/json',
		);

		$data = array(
			'userid' => $userid,
			'password' => $password,
			'email' => $email,
			'displayName' => $displayName,
		);

		if (!empty($nextcloudGroupsMember)) {
			$groups = array_values(array_filter(array_map('trim', explode(';', $nextcloudGroupsMember))));
			foreach ($groups as $group) {
				$data['groups[]'] = $group;
			}
		}

		if (!empty($nextcloudQuota)) {
			$data['quota'] = $nextcloudQuota;
		}

		try {
			$options = array(
				'auth' => array($adminUser, $adminPass),
			);
			$response = Requests::post($url, $headers, $data, $options);
			if ($response->success) {
				$this->logger->info("User created ($email)");
				return true;
			}
			$this->logger->warning("Error ($email) HTTP ".$response->status_code.' body='.$response->body);
		} catch (Requests_Exception $e) {
			$this->logger->warning("Exception: " . $e->getMessage());
		}
		return false;
	}

	/**
	 * Retrieves the Plex user ID associated with a given email address.
	 *
	 * @param string $email The email address to search for in the shared Plex users.
	 * @return string|null The Plex user ID if found, or null if not found or on error.
	 */
	public function _getPlexUserIdByEmail($email) {
		$this->logger->info("Try to get Plex userID for $email");

		if (empty($this->config['plexToken']) || empty($this->config['plexID'])) {
			$this->logger->warning("PlexToken ou plexID missing");
			return null;
		}

		$url = "https://clients.plex.tv/api/invites/requested";
		$headers = array(
			"Accept" => "application/json",
			"X-Plex-Token" => $this->config['plexToken']
		);
		try {
			$response = Requests::get($url, $headers);
			if ($response->success) {
				$xml = simplexml_load_string($response->body);
				// Parcourt les éléments <Invite> du MediaContainer
				foreach ($xml->Invite as $invite) {
					$inviteEmail = (string)$invite['email'];
					$inviteId = (string)$invite['id'];
					if (strcasecmp($inviteEmail, $email) === 0) {
						$this->logger->info("Find id=$inviteId for $email");
						return $inviteId;
					}
				}
			}
			$this->logger->warning("No userId found for $email");
		} catch (Requests_Exception $e) {
			$this->logger->warning("Exception: " . $e->getMessage());
		}
		return null;
	}

	/**
	 * Retrieves the email address associated with a given invite code.
	 *
	 * @param string $inviteCode The invite code to look up.
	 * @return string|false The email address associated with the invite code, or false if not found.
	 */
	public function _getEmailFronInviteCode($inviteCode) {
		if (empty($inviteCode)) {
			$this->logger->warning('Invite code not found');
			return false;
		}
		$emailLookupQuery = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT email FROM invites WHERE code = ? COLLATE NOCASE',
					$inviteCode
				)
			)
		];
		$emailRow = $this->processQueries($emailLookupQuery);
		if ($emailRow && !empty($emailRow['email'])) {
			$this->logger->info("Email foud via the code [$inviteCode] : ".$emailRow['email']);
			return $emailRow['email'];
		} else {
			$this->logger->warning("No mail found for the code [$inviteCode]");
			return false;
		}
	}

	/**
	 * Adds a user to Plex Home using the provided email address.
	 *
	 * @param string $email The email address of the user to invite.
	 * @return array|false Returns the decoded response from Plex API on success, or false on failure.
	 */
	public function _addUserPlexHome($email){
		if (empty($email) || empty($this->config['plexToken'])) {
			$this->logger->warning('_addUserPlexHome: email or plexToken missing');
			return false;
		}
		$url = 'https://clients.plex.tv/api/home/users?invitedEmail=' . urlencode($email) . '&skipFriendship=1&X-Plex-Token=' . urlencode($this->config['plexToken']);
		try {
			$response = Requests::post($url, $headers);
			if ($response->success) {
				$this->logger->info('User added on plex home');
				return json_decode($response->body, true);
			} else {
				$this->logger->info('_getPlexHomeUserByEmail: error (HTTP ' . $response->status_code . ')');
			}
		} catch (Requests_Exception $e) {
			$this->logger->info('_addUserPlexHome: ' . $e->getMessage());
		}
		return false;
	}

}