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
		if (empty($this->config['unifiURL'])) {
			$this->setAPIResponse('error', 'Unifi URL is not defined', 422);
			return false;
		}
		if (empty($this->config['unifiUsername'])) {
			$this->setAPIResponse('error', 'Unifi Username is not defined', 422);
			return false;
		}
		if (empty($this->config['unifiPassword'])) {
			$this->setAPIResponse('error', 'Unifi Password is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['unifiURL']);
		try {
			$options = $this->requestOptions($url, $this->config['homepageUnifiRefresh'], $this->config['unifiDisableCertCheck'], $this->config['unifiUseCustomCertificate'], ['follow_redirects' => true]);
			$data = array(
				'username' => $this->config['unifiUsername'],
				'password' => $this->decrypt($this->config['unifiPassword']),
				'remember' => true,
				'strict' => true
			);
			$response = Requests::post($url . '/api/login', array(), json_encode($data), $options);
			if ($response->success) {
				$cookie['unifises'] = ($response->cookies['unifises']->value) ?? false;
				$cookie['csrf_token'] = ($response->cookies['csrf_token']->value) ?? false;
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check Credentials', 409);
				return false;
			}
			$headers = array(
				'cookie' => 'unifises=' . $cookie['unifises'] . ';' . 'csrf_token=' . $cookie['csrf_token'] . ';'
			);
			$response = Requests::get($url . '/api/self/sites', $headers, $options);
			if ($response->success) {
				$body = json_decode($response->body, true);
				$this->setAPIResponse('success', null, 200, $body);
				return $body;
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Error Occurred', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		
	}
	
	public function testConnectionUnifi()
	{
		if (empty($this->config['unifiURL'])) {
			$this->setAPIResponse('error', 'Unifi URL is not defined', 422);
			return false;
		}
		if (empty($this->config['unifiUsername'])) {
			$this->setAPIResponse('error', 'Unifi Username is not defined', 422);
			return false;
		}
		if (empty($this->config['unifiPassword'])) {
			$this->setAPIResponse('error', 'Unifi Password is not defined', 422);
			return false;
		}
		$api['content']['unifi'] = array();
		$url = $this->qualifyURL($this->config['unifiURL']);
		$options = $this->requestOptions($url, $this->config['homepageUnifiRefresh'], $this->config['unifiDisableCertCheck'], $this->config['unifiUseCustomCertificate'], ['follow_redirects' => true]);
		$data = array(
			'username' => $this->config['unifiUsername'],
			'password' => $this->decrypt($this->config['unifiPassword']),
			'remember' => true,
			'strict' => true
		);
		try {
			// Is this UnifiOs or Regular
			$response = Requests::get($url, [], $options);
			if ($response->success) {
				$csrfToken = ($response->headers['x-csrf-token']) ?? false;
				$data = ($csrfToken) ? $data : json_encode($data);
				if ($csrfToken) {
					$headers = [
						'x-csrf-token' => $csrfToken
					];
				} else {
					$data = json_encode($data);
					$headers = [];
				}
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check URL', 409);
				return false;
			}
			$urlLogin = ($csrfToken) ? $url . '/api/auth/login' : $url . '/api/login';
			$urlStat = ($csrfToken) ? $url . '/proxy/network/api/s/default/stat/health' : $url . '/api/s/' . $this->config['unifiSiteName'] . '/stat/health';
			$response = Requests::post($urlLogin, [], $data, $options);
			if ($response->success) {
				$cookie['unifises'] = ($response->cookies['unifises']->value) ?? false;
				$cookie['csrf_token'] = ($response->cookies['csrf_token']->value) ?? false;
				$cookie['Token'] = ($response->cookies['Token']->value) ?? false;
				$options['cookies'] = $response->cookies;
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check Credentials', 409);
				return false;
			}
			$headers = array(
				'cookie' => 'unifises=' . $cookie['unifises'] . ';' . 'csrf_token=' . $cookie['csrf_token'] . ';'
			);
			$response = Requests::get($urlStat, $headers, $options);
			if ($response->success) {
				$api['content']['unifi'] = json_decode($response->body, true);
			} else {
				$this->setAPIResponse('error', 'Unifi response error3', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content']['unifi'] = isset($api['content']['unifi']) ? $api['content']['unifi'] : false;
		$this->setAPIResponse('success', 'API Connection succeeded', 200);
		return true;
	}
	
	public function getUnifiHomepageData()
	{
		if (!$this->homepageItemPermissions($this->unifiHomepagePermissions('main'), true)) {
			return false;
		}
		$api['content']['unifi'] = array();
		$url = $this->qualifyURL($this->config['unifiURL']);
		$options = $this->requestOptions($url, $this->config['homepageUnifiRefresh'], $this->config['unifiDisableCertCheck'], $this->config['unifiUseCustomCertificate'], ['follow_redirects' => true]);
		$data = array(
			'username' => $this->config['unifiUsername'],
			'password' => $this->decrypt($this->config['unifiPassword']),
			'remember' => true,
			'strict' => true
		);
		try {
			// Is this UnifiOs or Regular
			$response = Requests::get($url, [], $options);
			if ($response->success) {
				$csrfToken = ($response->headers['x-csrf-token']) ?? false;
				$data = ($csrfToken) ? $data : json_encode($data);
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check URL', 409);
				return false;
			}
			$urlLogin = ($csrfToken) ? $url . '/api/auth/login' : $url . '/api/login';
			$urlStat = ($csrfToken) ? $url . '/proxy/network/api/s/default/stat/health' : $url . '/api/s/' . $this->config['unifiSiteName'] . '/stat/health';
			$response = Requests::post($urlLogin, [], $data, $options);
			if ($response->success) {
				$cookie['unifises'] = ($response->cookies['unifises']->value) ?? false;
				$cookie['csrf_token'] = ($response->cookies['csrf_token']->value) ?? false;
				$cookie['Token'] = ($response->cookies['Token']->value) ?? false;
				$options['cookies'] = $response->cookies;
				
			} else {
				$this->setAPIResponse('error', 'Unifi response error - Check Credentials', 409);
				return false;
			}
			$headers = array(
				'cookie' => 'unifises=' . $cookie['unifises'] . ';' . 'csrf_token=' . $cookie['csrf_token'] . ';'
			);
			$response = Requests::get($urlStat, $headers, $options);
			if ($response->success) {
				$api['content']['unifi'] = json_decode($response->body, true);
			} else {
				$this->setAPIResponse('error', 'Unifi response error3', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Unifi Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content']['unifi'] = isset($api['content']['unifi']) ? $api['content']['unifi'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}