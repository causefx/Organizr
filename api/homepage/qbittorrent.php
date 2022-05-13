<?php

trait QBitTorrentHomepageItem
{
	public function qBittorrentSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'qBittorrent',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/qBittorrent.png',
			'category' => 'Downloader',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageqBittorrentEnabled'),
					$this->settingsOption('auth', 'homepageqBittorrentAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'qBittorrentURL'),
					$this->settingsOption('select', 'qBittorrentApiVersion', ['label' => 'API Version', 'options' => $this->qBittorrentApiOptions()]),
					$this->settingsOption('username', 'qBittorrentUsername'),
					$this->settingsOption('password', 'qBittorrentPassword'),
					$this->settingsOption('disable-cert-check', 'qBittorrentDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'qBittorrentUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'qbittorrent'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'qBittorrentSocksEnabled'),
					$this->settingsOption('auth', 'qBittorrentSocksAuth'),
				],
				'Misc Options' => [
					$this->settingsOption('hide-seeding', 'qBittorrentHideSeeding'),
					$this->settingsOption('hide-completed', 'qBittorrentHideCompleted'),
					$this->settingsOption('select', 'qBittorrentSortOrder', ['label' => 'Order', 'options' => $this->qBittorrentSortOptions()]),
					$this->settingsOption('switch', 'qBittorrentReverseSorting', ['label' => 'Reverse Sorting']),
					$this->settingsOption('refresh', 'qBittorrentRefresh'),
					$this->settingsOption('combine', 'qBittorrentCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'qbittorrent'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
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
			$options = $this->requestOptions($this->config['qBittorrentURL'], null, $this->config['qBittorrentDisableCertCheck'], $this->config['qBittorrentUseCustomCertificate']);
			$response = Requests::post($url, [], $data, $options);
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
				$this->setLoggerChannel('qBittorrent')->warning('Could not get session ID');
				$this->setAPIResponse('error', 'qBittorrent Connect Function - Error: Could not get session ID', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('qBittorrent')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	public function qBittorrentHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageqBittorrentEnabled'
				],
				'auth' => [
					'homepageqBittorrentAuth'
				],
				'not_empty' => [
					'qBittorrentURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderqBittorrent()
	{
		if ($this->homepageItemPermissions($this->qBittorrentHomepagePermissions('main'))) {
			$loadingBox = ($this->config['qBittorrentCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['qBittorrentCombine']) ? 'buildDownloaderCombined(\'qBittorrent\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("qBittorrent"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrderqBittorrent
		                ' . $builder . '
		                homepageDownloader("qBittorrent", "' . $this->config['qBittorrentRefresh'] . '");
		                // End homepageOrderqBittorrent
	                </script>
				</div>
				';
		}
	}

	public function getQBittorrentHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->qBittorrentHomepagePermissions('main'), true)) {
			return false;
		}
		$digest = $this->qualifyURL($this->config['qBittorrentURL'], true);
		$data = array('username' => $this->config['qBittorrentUsername'], 'password' => $this->decrypt($this->config['qBittorrentPassword']));
		$apiVersionLogin = ($this->config['qBittorrentApiVersion'] == '1') ? '/login' : '/api/v2/auth/login';
		$apiVersionQuery = ($this->config['qBittorrentApiVersion'] == '1') ? '/query/torrents?sort=' : '/api/v2/torrents/info?sort=';
		$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $apiVersionLogin;
		try {
			$options = $this->requestOptions($this->config['qBittorrentURL'], $this->config['qBittorrentRefresh'], $this->config['qBittorrentDisableCertCheck'], $this->config['qBittorrentUseCustomCertificate']);
			$response = Requests::post($url, [], $data, $options);
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
					$api['content'] = $api['content'] ?? false;
					$this->setAPIResponse('success', null, 200, $api);
					return $api;
				}
			} else {
				$this->setLoggerChannel('qBittorrent')->warning('Could not get session ID');
				$this->setAPIResponse('error', 'qBittorrent Connect Function - Error: Could not get session ID', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('qBittorrent')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}
}