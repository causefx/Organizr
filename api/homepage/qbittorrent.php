<?php

trait QBitTorrentHomepageItem
{
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