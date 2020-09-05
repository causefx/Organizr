<?php

trait QBitTorrentHomepageItem
{
	public function qBittorrentSettingsArray()
	{
		return array(
			'name' => 'qBittorrent',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/qBittorrent.png',
			'category' => 'Downloader',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageqBittorrentEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageqBittorrentEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageqBittorrentAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageqBittorrentAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'qBittorrentURL',
						'label' => 'URL',
						'value' => $this->config['qBittorrentURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'select',
						'name' => 'qBittorrentApiVersion',
						'label' => 'API Version',
						'value' => $this->config['qBittorrentApiVersion'],
						'options' => $this->qBittorrentApiOptions()
					),
					array(
						'type' => 'input',
						'name' => 'qBittorrentUsername',
						'label' => 'Username',
						'value' => $this->config['qBittorrentUsername']
					),
					array(
						'type' => 'password',
						'name' => 'qBittorrentPassword',
						'label' => 'Password',
						'value' => $this->config['qBittorrentPassword']
					)
				),
				'Misc Options' => array(
					array(
						'type' => 'switch',
						'name' => 'qBittorrentHideSeeding',
						'label' => 'Hide Seeding',
						'value' => $this->config['qBittorrentHideSeeding']
					),
					array(
						'type' => 'switch',
						'name' => 'qBittorrentHideCompleted',
						'label' => 'Hide Completed',
						'value' => $this->config['qBittorrentHideCompleted']
					),
					array(
						'type' => 'select',
						'name' => 'qBittorrentSortOrder',
						'label' => 'Order',
						'value' => $this->config['qBittorrentSortOrder'],
						'options' => $this->qBittorrentSortOptions()
					), array(
						'type' => 'switch',
						'name' => 'qBittorrentReverseSorting',
						'label' => 'Reverse Sorting',
						'value' => $this->config['qBittorrentReverseSorting']
					),
					array(
						'type' => 'select',
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageDownloadRefresh'],
						'options' => $this->timeOptions()
					),
					array(
						'type' => 'switch',
						'name' => 'qBittorrentCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['qBittorrentCombine']
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
						'attr' => 'onclick="testAPIConnection(\'qbittorrent\')"'
					),
				)
			)
		);
	}
	
	public function testConnectionQBittorrent()
	{
		if (empty($this->config['qBittorrentURL'])) {
			$this->setAPIResponse('error', 'qBittorrent URL is not defined', 422);
			return false;
		}
		$digest = $this->qualifyURL($this->config['qBittorrentURL'], true);
		$data = array('username' => $this->config['qBittorrentUsername'], 'password' => $this->decrypt($this->config['qBittorrentPassword']));
		$apiVersionLogin = ($this->config['qBittorrentApiVersion'] == '1') ? '/login' : '/api/v2/auth/login';
		$apiVersionQuery = ($this->config['qBittorrentApiVersion'] == '1') ? '/query/torrents?sort=' : '/api/v2/torrents/info?sort=';
		$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $apiVersionLogin;
		try {
			$options = ($this->localURL($this->config['qBittorrentURL'])) ? array('verify' => false) : array();
			$response = Requests::post($url, array(), $data, $options);
			$reflection = new ReflectionClass($response->cookies);
			$cookie = $reflection->getProperty("cookies");
			$cookie->setAccessible(true);
			$cookie = $cookie->getValue($response->cookies);
			if ($cookie) {
				$headers = array(
					'Cookie' => 'SID=' . $cookie['SID']->value
				);
				$reverse = $this->config['qBittorrentReverseSorting'] ? 'true' : 'false';
				$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $apiVersionQuery . $this->config['qBittorrentSortOrder'] . '&reverse=' . $reverse;
				$response = Requests::get($url, $headers, $options);
				if ($response) {
					$torrents = json_decode($response->body, true);
					if (is_array($torrents)) {
						$this->setAPIResponse('success', 'API Connection succeeded', 200);
						return true;
					} else {
						$this->setAPIResponse('error', 'qBittorrent Error Occurred - Check URL or Credentials', 500);
						return true;
					}
				} else {
					$this->setAPIResponse('error', 'qBittorrent Connection Error Occurred - Check URL or Credentials', 500);
					return true;
				}
			} else {
				$this->writeLog('error', 'qBittorrent Connect Function - Error: Could not get session ID', 'SYSTEM');
				$this->setAPIResponse('error', 'qBittorrent Connect Function - Error: Could not get session ID', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'qBittorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getQBittorrentHomepageQueue()
	{
		if (!$this->config['homepageqBittorrentEnabled']) {
			$this->setAPIResponse('error', 'qBittorrent homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageqBittorrentAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['qBittorrentURL'])) {
			$this->setAPIResponse('error', 'qBittorrent URL is not defined', 422);
			return false;
		}
		$digest = $this->qualifyURL($this->config['qBittorrentURL'], true);
		$data = array('username' => $this->config['qBittorrentUsername'], 'password' => $this->decrypt($this->config['qBittorrentPassword']));
		$apiVersionLogin = ($this->config['qBittorrentApiVersion'] == '1') ? '/login' : '/api/v2/auth/login';
		$apiVersionQuery = ($this->config['qBittorrentApiVersion'] == '1') ? '/query/torrents?sort=' : '/api/v2/torrents/info?sort=';
		$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $apiVersionLogin;
		try {
			$options = ($this->localURL($this->config['qBittorrentURL'])) ? array('verify' => false) : array();
			$response = Requests::post($url, array(), $data, $options);
			$reflection = new ReflectionClass($response->cookies);
			$cookie = $reflection->getProperty("cookies");
			$cookie->setAccessible(true);
			$cookie = $cookie->getValue($response->cookies);
			if ($cookie) {
				$headers = array(
					'Cookie' => 'SID=' . $cookie['SID']->value
				);
				$reverse = $this->config['qBittorrentReverseSorting'] ? 'true' : 'false';
				$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $apiVersionQuery . $this->config['qBittorrentSortOrder'] . '&reverse=' . $reverse;
				$response = Requests::get($url, $headers, $options);
				if ($response) {
					$torrentList = json_decode($response->body, true);
					if ($this->config['qBittorrentHideSeeding'] || $this->config['qBittorrentHideCompleted']) {
						$filter = array();
						$torrents = array();
						if ($this->config['qBittorrentHideSeeding']) {
							array_push($filter, 'uploading', 'stalledUP', 'queuedUP');
						}
						if ($this->config['qBittorrentHideCompleted']) {
							array_push($filter, 'pausedUP');
						}
						foreach ($torrentList as $key => $value) {
							if (!in_array($value['state'], $filter)) {
								$torrents[] = $value;
							}
						}
					} else {
						$torrents = json_decode($response->body, true);
					}
					$api['content']['queueItems'] = $torrents;
					$api['content']['historyItems'] = false;
					$api['content'] = isset($api['content']) ? $api['content'] : false;
					$this->setAPIResponse('success', null, 200, $api);
					return $api;
				}
			} else {
				$this->writeLog('error', 'qBittorrent Connect Function - Error: Could not get session ID', 'SYSTEM');
				$this->setAPIResponse('error', 'qBittorrent Connect Function - Error: Could not get session ID', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'qBittorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
}