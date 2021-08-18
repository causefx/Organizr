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
		$homepageSettings = array(
			'debug' => true,
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
						'type' => 'switch',
						'name' => 'qBittorrentDisableCertCheck',
						'label' => 'Disable Certificate Check',
						'value' => $this->config['qBittorrentDisableCertCheck']
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
				'API SOCKS' => array(
					array(
						'type' => 'html',
						'override' => 12,
						'label' => '',
						'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">' . $this->socksHeadingHTML('qbittorrent') . '</div>
								</div>
							</div>'
					),
					array(
						'type' => 'switch',
						'name' => 'qBittorrentSocksEnabled',
						'label' => 'Enable',
						'value' => $this->config['qBittorrentSocksEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'qBittorrentSocksAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['qBittorrentSocksAuth'],
						'options' => $this->groupOptions
					),
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
						'name' => 'qBittorrentRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['qBittorrentRefresh'],
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
			$options = $this->requestOptions($this->config['qBittorrentURL'], $this->config['qBittorrentDisableCertCheck'], $this->config['qBittorrentRefresh']);
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
			$options = $this->requestOptions($this->config['qBittorrentURL'], $this->config['qBittorrentDisableCertCheck'], $this->config['qBittorrentRefresh']);
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