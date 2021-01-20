<?php

trait UnifiHomepageItem
{
	public function unifiSettingsArray()
	{
		return array(
			'name' => 'UniFi',
			'enabled' => true,
			'image' => 'plugins/images/tabs/unifi.png',
			'category' => 'Monitor',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageUnifiEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageUnifiEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageUnifiAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageUnifiAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'unifiURL',
						'label' => 'URL',
						'value' => $this->config['unifiURL'],
						'help' => 'URL for Unifi',
						'placeholder' => 'Unifi API URL'
					),
					array(
						'type' => 'blank',
						'label' => ''
					),
					array(
						'type' => 'input',
						'name' => 'unifiUsername',
						'label' => 'Username',
						'value' => $this->config['unifiUsername'],
						'help' => 'Username is case-sensitive',
					),
					array(
						'type' => 'password',
						'name' => 'unifiPassword',
						'label' => 'Password',
						'value' => $this->config['unifiPassword']
					),
					array(
						'type' => 'input',
						'name' => 'unifiSiteName',
						'label' => 'Site Name (Not for UnifiOS)',
						'value' => $this->config['unifiSiteName'],
						'help' => 'Site Name - not Site ID nor Site Description',
					),
					array(
						'type' => 'button',
						'label' => 'Grab Unifi Site (Not for UnifiOS)',
						'icon' => 'fa fa-building',
						'text' => 'Get Unifi Site',
						'attr' => 'onclick="getUnifiSite()"'
					),
				),
				'Misc Options' => array(
					array(
						'type' => 'select',
						'name' => 'homepageUnifiRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageUnifiRefresh'],
						'options' => $this->timeOptions()
					),
				),
				'Test Connection' => array(
					array(
						'type' => 'blank',
						'label' => 'Please Save before Testing'
					),
					array(
						'type' => 'button',
						'label' => '',
						'icon' => 'fa fa-flask',
						'class' => 'pull-right',
						'text' => 'Test Connection',
						'attr' => 'onclick="testAPIConnection(\'unifi\')"'
					),
				)
			)
		);
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
			$options = array('verify' => false, 'verifyname' => false, 'follow_redirects' => false);
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
		$options = array('verify' => false, 'verifyname' => false, 'follow_redirects' => true);
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
		$options = array('verify' => false, 'verifyname' => false, 'follow_redirects' => true);
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