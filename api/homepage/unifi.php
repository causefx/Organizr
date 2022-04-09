<?php

trait UnifiHomepageItem
{
	public function unifiSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'UniFi',
			'enabled' => true,
			'image' => 'plugins/images/tabs/unifi.png',
			'category' => 'Monitor',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageUnifiEnabled'),
					$this->settingsOption('auth', 'homepageUnifiAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'unifiURL'),
					$this->settingsOption('blank'),
					$this->settingsOption('disable-cert-check', 'unifiDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'unifiUseCustomCertificate'),
					$this->settingsOption('username', 'unifiUsername', ['help' => 'Username is case-sensitive']),
					$this->settingsOption('password', 'unifiPassword'),
					$this->settingsOption('input', 'unifiSiteName', ['label' => 'Site Name (Not for UnifiOS)', 'help' => 'Site Name - not Site ID nor Site Description']),
					$this->settingsOption('button', '', ['label' => 'Grab Unifi Site (Not for UnifiOS)', 'icon' => 'fa fa-building', 'text' => 'Get Unifi Site', 'attr' => 'onclick="getUnifiSite()"']),
				],
				'Misc Options' => [
					$this->settingsOption('refresh', 'homepageUnifiRefresh'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'unifi'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function unifiHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageUnifiEnabled'
				],
				'auth' => [
					'homepageUnifiAuth'
				],
				'not_empty' => [
					'unifiURL',
					'unifiUsername',
					'unifiPassword'
				]
			],
			'test' => [
				'auth' => [
					'homepageUnifiAuth'
				],
				'not_empty' => [
					'unifiURL',
					'unifiUsername',
					'unifiPassword'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderunifi()
	{
		if ($this->homepageItemPermissions($this->unifiHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Unifi...</h2></div>
					<script>
						// Unifi
						homepageUnifi("' . $this->config['homepageHealthChecksRefresh'] . '");
						// End Unifi
					</script>
				</div>
				';
		}
	}

	public function getUnifiSiteName()
	{
		if (!$this->homepageItemPermissions($this->unifiHomepagePermissions('test'), true)) {
			return false;
		}
		try {
			$login = $this->unifiLogin();
			if ($login) {
				$url = $this->qualifyURL($this->config['unifiURL']);
				$unifiOS = $login['unifiOS'];
				if ($unifiOS) {
					$this->setResponse(500, 'Unifi OS does not support Multi Site');
					return false;
				}
				$response = Requests::get($url . '/api/self/sites', [], $login['options']);
				if ($response->success) {
					$body = json_decode($response->body, true);
					$this->setAPIResponse('success', null, 200, $body);
					return $body;
				} else {
					$this->setAPIResponse('error', 'Unifi response error3', 409);
					return false;
				}
			} else {
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function isUnifiOS()
	{
		try {
			// Is this UnifiOs or Regular
			$url = $this->qualifyURL($this->config['unifiURL']);
			$options = $this->requestOptions($url, $this->config['homepageUnifiRefresh'], $this->config['unifiDisableCertCheck'], $this->config['unifiUseCustomCertificate'], ['follow_redirects' => true]);
			$response = Requests::get($url, [], $options);
			if ($response->success) {
				return ($response->headers['x-csrf-token']) ?? false;
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check URL', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function unifiLogin()
	{
		$csrfToken = $this->isUnifiOS();
		$url = $this->qualifyURL($this->config['unifiURL']);
		$options = $this->requestOptions($url, $this->config['homepageUnifiRefresh'], $this->config['unifiDisableCertCheck'], $this->config['unifiUseCustomCertificate'], ['follow_redirects' => true]);
		$data = array(
			'username' => $this->config['unifiUsername'],
			'password' => $this->decrypt($this->config['unifiPassword']),
			'remember' => true,
			'strict' => true
		);
		try {
			$data = ($csrfToken) ? $data : json_encode($data);
			$headers = ($csrfToken) ? ['x-csrf-token' => $csrfToken] : [];
			$urlLogin = ($csrfToken) ? $url . '/api/auth/login' : $url . '/api/login';
			$response = Requests::post($urlLogin, $headers, $data, $options);
			if ($response->success) {
				$options['cookies'] = $response->cookies;
				return [
					'unifiOS' => $csrfToken,
					'options' => $options
				];
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check Credentials', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function testConnectionUnifi()
	{
		if (!$this->homepageItemPermissions($this->unifiHomepagePermissions('test'), true)) {
			return false;
		}
		try {
			// Is this UnifiOs or Regular
			$api['content']['unifi'] = array();
			$login = $this->unifiLogin();
			if ($login) {
				$url = $this->qualifyURL($this->config['unifiURL']);
				$unifiOS = $login['unifiOS'];
				$headers = ($unifiOS) ? ['x-csrf-token' => $unifiOS] : [];
				$urlStat = ($unifiOS) ? $url . '/proxy/network/api/s/default/stat/health' : $url . '/api/s/' . $this->config['unifiSiteName'] . '/stat/health';
				$response = Requests::get($urlStat, $headers, $login['options']);
				if ($response->success) {
					$api['content']['unifi'] = json_decode($response->body, true);
				} else {
					$this->setAPIResponse('error', 'Unifi response error3', 409);
					return false;
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setResponse(500, $e->getMessage());
			return false;
		}
		$api['content']['unifi'] = $api['content']['unifi'] ?? false;
		$this->setAPIResponse('success', 'API Connection succeeded', 200);
		return true;
	}

	public function getUnifiHomepageData()
	{
		if (!$this->homepageItemPermissions($this->unifiHomepagePermissions('main'), true)) {
			return false;
		}
		try {
			$api['content']['unifi'] = array();
			$login = $this->unifiLogin();
			if ($login) {
				$url = $this->qualifyURL($this->config['unifiURL']);
				$unifiOS = $login['unifiOS'];
				$headers = ($unifiOS) ? ['x-csrf-token' => $unifiOS] : [];
				$urlStat = ($unifiOS) ? $url . '/proxy/network/api/s/default/stat/health' : $url . '/api/s/' . $this->config['unifiSiteName'] . '/stat/health';
				$response = Requests::get($urlStat, $headers, $login['options']);
				if ($response->success) {
					$api['content']['unifi'] = json_decode($response->body, true);
				} else {
					$this->setAPIResponse('error', 'Unifi response error3', 409);
					return false;
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setResponse(500, $e->getMessage());
			return false;
		}
		$api['content']['unifi'] = $api['content']['unifi'] ?? false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}