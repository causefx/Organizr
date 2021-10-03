<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['plexlibraries'] = array( // Plugin Name
	'name' => 'Plex Libraries', // Plugin Name
	'author' => 'TehMuffinMoo', // Who wrote the plugin
	'category' => 'Library Management', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'PLEXLIBRARIES', // html element id prefix (All Uppercase)
	'configPrefix' => 'PLEXLIBRARIES', // config file prefix for array items without the hypen (All Uppercase)
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/plexLibraries/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/plexlibraries/settings', // api route for settings page (All Lowercase)
	'homepage' => false // Is plugin for use on homepage? true or false
);

class plexLibrariesPlugin extends Organizr
{
	public function _pluginGetSettings()
	{
		$libraryList = [['name' => 'Refresh page to update List', 'value' => '', 'disabled' => true]];
		if ($this->config['plexID'] !== '' && $this->config['plexToken'] !== '') {
			$libraryList = [];
			$loop = $this->plexLibraryList('key')['libraries'];
			foreach ($loop as $key => $value) {
				$libraryList[] = ['name' => $key, 'value' => $value];
			}
		}
		$this->setGroupOptionsVariable();
		return array(
			'Settings' => array(
				$this->settingsOption('token', 'plexToken'),
				$this->settingsOption('button', '', ['label' => 'Get Plex Token', 'icon' => 'fa fa-ticket', 'text' => 'Retrieve', 'attr' => 'onclick="PlexOAuth(oAuthSuccess,oAuthError, null, \'#PLEXLIBRARIES-settings-page [name=plexToken]\')"']),
				$this->settingsOption('password-alt', 'plexID', ['label' => 'Plex Machine']),
				$this->settingsOption('button', '', ['label' => 'Get Plex Machine', 'icon' => 'fa fa-id-badge', 'text' => 'Retrieve', 'attr' => 'onclick="showPlexMachineForm(\'#PLEXLIBRARIES-settings-page [name=plexID]\')"']),
				$this->settingsOption('auth', 'PLEXLIBRARIES-pluginAuth'),
				$this->settingsOption('input', 'plexAdmin', ['label' => 'Plex Admin Username or Email']),
				$this->settingsOption('plex-library-include', 'PLEXLIBRARIES-librariesToInclude', ['options' => $libraryList])
			)
		);
	}
	
	public function _pluginLaunch()
	{
		$user = $this->getUserById($this->user['userID']);
		if ($user) {
			if ($user['plex_token'] !== null) {
				$this->setResponse(200, 'User approved for plugin');
				return true;
			}
		}
		$this->setResponse(401, 'User not approved for plugin');
		return false;
	}
	
	public function plexLibrariesPluginGetPlexShares($includeAll = false, $userId = "")
	{
		if (empty($this->config['plexToken'])) {
			$this->setResponse(409, 'plexToken is not setup');
			return false;
		}
		$headers = array(
			'Content-type: application/xml',
			'X-Plex-Token' => $this->config['plexToken'],
		);
		// Check if user is Plex Admin
		if ((strtolower($this->user['username']) == strtolower($this->config['plexAdmin']) || strtolower($this->user['email']) == strtolower($this->config['plexAdmin'])) && !$userId) {
			$url = 'https://plex.tv/api/servers/' . $this->config['plexID'] . '/shared_servers/';
			try {
				$response = Requests::get($url, $headers, []);
				if ($response->success) {
					libxml_use_internal_errors(true);
					$plex = simplexml_load_string($response->body);
					$libraryList = array();
					foreach ($plex->SharedServer as $child) {
						if (!empty($child['username'])) {
							$libraryList[(string)$child['username']]['username'] = (string)$child['username'];
							$libraryList[(string)$child['username']]['email'] = (string)$child['email'];
							$libraryList[(string)$child['username']]['id'] = (string)$child['id'];
							$libraryList[(string)$child['username']]['userID'] = (string)$child['userID'];
							foreach ($child->Section as $library) {
								$library = current($library->attributes());
								$libraryList[(string)$child['username']]['libraries'][] = $library;
							}
						}
					}
					$libraryList = array_change_key_case($libraryList, CASE_LOWER);
					ksort($libraryList);
					$apiData = [
						'plexAdmin' => true,
						'libraryData' => $libraryList
					];
					$this->setResponse(200, null, $apiData);
					return $apiData;
				} else {
					$this->setResponse(500, 'Plex error');
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 400);
				return false;
			}
		} else {
			$searchTerm = ($userId) ?: $this->user['email'];
			$searchKey = ($userId) ? 'shareId' : 'email';
			$plexUsers = $this->allPlexUsers(false, true);
			$key = array_search($searchTerm, array_column($plexUsers, $searchKey));
			if ($key !== false) {
				$url = 'https://plex.tv/api/servers/' . $this->config['plexID'] . '/shared_servers/' . $plexUsers[$key]['shareId'];
			} else {
				$this->setResponse(404, 'User Id was not found in Plex Users');
				return false;
			}
			try {
				$response = Requests::get($url, $headers, array());
				if ($response->success) {
					libxml_use_internal_errors(true);
					$plex = simplexml_load_string($response->body);
					$libraryList = array();
					foreach ($plex->SharedServer as $child) {
						if (!empty($child['username'])) {
							$libraryList[(string)$child['username']]['username'] = (string)$child['username'];
							$libraryList[(string)$child['username']]['email'] = (string)$child['email'];
							$libraryList[(string)$child['username']]['id'] = (string)$child['id'];
							$libraryList[(string)$child['username']]['shareId'] = (string)$plexUsers[$key]['shareId'];
							foreach ($child->Section as $library) {
								$library = current($library->attributes());
								if (!$includeAll) {
									$librariesToInclude = explode(',', $this->config['PLEXLIBRARIES-librariesToInclude']);
									if (in_array($library['key'], $librariesToInclude)) {
										$libraryList[(string)$child['username']]['libraries'][] = $library;
									}
								} else {
									$libraryList[(string)$child['username']]['libraries'][] = $library;
								}
							}
						}
					}
					$libraryList = array_change_key_case($libraryList, CASE_LOWER);
					$apiData = [
						'plexAdmin' => false,
						'libraryData' => $libraryList
					];
					$this->setResponse(200, null, $apiData);
					return $apiData;
				} else {
					$this->setResponse(500, 'Plex Error', $response->body);
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 400);
				return false;
			}
		}
	}
	
	public function plexLibrariesPluginUpdatePlexShares($userId, $action, $shareId)
	{
		if (!$userId) {
			$this->setResponse(409, 'User Id not supplied');
			return false;
		}
		if (!$action) {
			$this->setResponse(409, 'Action not supplied');
			return false;
		}
		if (!$shareId) {
			$this->setResponse(409, 'Share Id not supplied');
			return false;
		}
		if (!$this->qualifyRequest(1)) {
			$plexUsers = $this->allPlexUsers(false, true);
			$key = array_search($this->user['email'], array_column($plexUsers, 'email'));
			if (!$key) {
				$this->setResponse(404, 'User Id was not found in Plex Users');
				return false;
			} else {
				if ($plexUsers[$key]['shareId'] !== $userId) {
					$this->setResponse(401, 'You are not allowed to edit someone else\'s plex share');
					return false;
				}
			}
		}
		$Shares = $this->plexLibrariesPluginGetPlexShares(true, $userId);
		$NewShares = array();
		if ($Shares) {
			if (isset($Shares['libraryData'])) {
				foreach ($Shares['libraryData'] as $key => $Share) {
					foreach ($Share['libraries'] as $library) {
						if ($library['shared'] == 1) {
							$ShareString = (string)$library['id'];
							if ($action == 'share') {
								$NewShares[] = $ShareString;
								$Msg = 'Enabled share';
							} else {
								$Msg = 'Disabled share';
								if ($ShareString !== $shareId) {
									$NewShares[] = $ShareString;
								}
							}
						}
					}
				}
				if ($action == 'share') {
					if (!in_array($shareId, $NewShares)) {
						$NewShares[] = $shareId;
					}
				}
			}
		}
		if (empty($NewShares)) {
			$this->setResponse(409, 'You must have at least one share.');
			return false;
		} else {
			$http_body = [
				"server_id" => $this->config['plexID'],
				"shared_server" => [
					"library_section_ids" => $NewShares
				]
			];
			if ($userId) {
				$url = 'https://plex.tv/api/servers/' . $this->config['plexID'] . '/shared_servers/' . $userId . '?X-Plex-Token=' . $this->config['plexToken'];
			}
			$headers = [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			];
			try {
				$response = Requests::put($url, $headers, json_encode($http_body), []);
				if ($response->success) {
					$this->setAPIResponse('success', $Msg, 200, $http_body);
					return $http_body;
				} else {
					$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: Plex Error', 400);
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', 'PlexLibraries Plugin - Error: ' . $e->getMessage(), 400);
				return false;
			}
		}
	}
}
