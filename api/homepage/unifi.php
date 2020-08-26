<?php

trait UnifiHomepageItem
{
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
		if (!$this->config['homepageUnifiEnabled']) {
			$this->setAPIResponse('error', 'Unifi homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageUnifiAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
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
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}