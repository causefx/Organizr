<?php

trait uTorrentHomepageItem
{
	public function uTorrentSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'uTorrent',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/utorrent.png',
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
					$this->settingsOption('enable', 'homepageuTorrentEnabled'),
					$this->settingsOption('auth', 'homepageuTorrentAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'uTorrentURL'),
					$this->settingsOption('blank'),
					$this->settingsOption('username', 'uTorrentUsername'),
					$this->settingsOption('password', 'uTorrentPassword'),
					$this->settingsOption('disable-cert-check', 'uTorrentDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'uTorrentUseCustomCertificate'),
				],
				'Misc Options' => [
					$this->settingsOption('hide-seeding', 'uTorrentHideSeeding', ['label' => 'Hide Seeding']),
					$this->settingsOption('hide-completed', 'uTorrentHideCompleted'),
					$this->settingsOption('refresh', 'uTorrentRefresh'),
					$this->settingsOption('combine', 'uTorrentCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'utorrent'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function uTorrentHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageuTorrentEnabled'
				],
				'auth' => [
					'homepageuTorrentAuth'
				],
				'not_empty' => [
					'uTorrentURL'
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
	
	public function testConnectionuTorrent()
	{
		if (empty($this->config['uTorrentURL'])) {
			$this->setAPIResponse('error', 'uTorrent URL is not defined', 422);
			return false;
		}
		try {
			
			$response = $this->getuTorrentToken();
			
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'uTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function homepageOrderuTorrent()
	{
		if ($this->homepageItemPermissions($this->uTorrentHomepagePermissions('main'))) {
			$loadingBox = ($this->config['uTorrentCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['uTorrentCombine']) ? 'buildDownloaderCombined(\'utorrent\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("utorrent"));';
			return '
                                <div id="' . __FUNCTION__ . '">
                                        ' . $loadingBox . '
                                        <script>
                                // homepageOrderuTorrent
                                ' . $builder . '
                                homepageDownloader("utorrent", "' . $this->config['uTorrentRefresh'] . '");
                                // End homepageOrderuTorrent
                        </script>
                                </div>
                                ';
		}
	}
	
	public function getuTorrentToken()
	{
		try {
			$tokenUrl = '/gui/token.html';
			$digest = $this->qualifyURL($this->config['uTorrentURL'], true);
			$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $tokenUrl;
			$data = array('username' => $this->config['uTorrentUsername'], 'password' => $this->decrypt($this->config['uTorrentPassword']));
			$options = $this->requestOptions($url, null, $this->config['uTorrentDisableCertCheck'], $this->config['uTorrentUseCustomCertificate']);
			if ($this->config['uTorrentUsername'] !== '' && $this->decrypt($this->config['uTorrentPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Basic(array($this->config['uTorrentUsername'], $this->decrypt($this->config['uTorrentPassword']))));
				$options = array_merge($options, $credentials);
			}
			$response = Requests::post($url, [], $data, $options);
			$dom = new PHPHtmlParser\Dom;
			$dom->loadStr($response->body);
			$id = $dom->getElementById('token')->text;
			$uTorrentConfig = new stdClass();
			$uTorrentConfig->uTorrentToken = $id;
			$reflection = new ReflectionClass($response->cookies);
			$cookie = $reflection->getProperty("cookies");
			$cookie->setAccessible(true);
			$cookie = $cookie->getValue($response->cookies);
			if ($cookie['GUID']) {
				$uTorrentConfig->uTorrentCookie = $cookie['GUID']->value;
			}
			if ($uTorrentConfig->uTorrentToken || $uTorrentConfig->uTorrentCookie) {
				$this->updateConfigItems($uTorrentConfig);
			}
			
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'uTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		
	}
	
	public function getuTorrentHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->uTorrentHomepagePermissions('main'), true)) {
			return false;
		}
		try {
			if (!$this->config['uTorrentToken'] || !$this->config['uTorrentCookie']) {
				$this->getuTorrentToken();
			}
			$queryUrl = '/gui/?token=' . $this->config['uTorrentToken'] . '&list=1';
			$digest = $this->qualifyURL($this->config['uTorrentURL'], true);
			$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $queryUrl;
			$options = $this->requestOptions($url, null, $this->config['uTorrentDisableCertCheck'], $this->config['uTorrentUseCustomCertificate']);
			if ($this->config['uTorrentUsername'] !== '' && $this->decrypt($this->config['uTorrentPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Basic(array($this->config['uTorrentUsername'], $this->decrypt($this->config['uTorrentPassword']))));
				$options = array_merge($options, $credentials);
			}
			$headers = array(
				'Cookie' => 'GUID=' . $this->config['uTorrentCookie']
			);
			$response = Requests::get($url, $headers, $options);
			$httpResponse = $response->status_code;
			if ($httpResponse == 400) {
				$this->writeLog('warn', 'uTorrent Token or Cookie Expired. Generating new session..', 'SYSTEM');
				$this->getuTorrentToken();
				$response = Requests::get($url, $headers, $options);
				$httpResponse = $response->status_code;
			}
			if ($httpResponse == 200) {
				$responseData = json_decode($response->body);
				$keyArray = (array)$responseData->torrents;
				//Populate values
				$valueArray = array();
				foreach ($keyArray as $keyArr) {
					preg_match('/(?<Status>(\w+\s+)+)(?<Percentage>\d+.\d+.*)/', $keyArr[21], $matches);
					$Status = str_replace(' ', '', $matches['Status']);
					if ($this->config['uTorrentHideSeeding'] && $Status == "Seeding") {
						// Do Nothing
					} else if ($this->config['uTorrentHideCompleted'] && $Status == "Finished") {
						// Do Nothing
					} else {
                                                $value = array(
                                                        'Hash' => $keyArr[0],
                                                        'TorrentStatus' => $keyArr[1],
                                                        'Name' => $keyArr[2],
                                                        'Size' => $keyArr[3],
                                                        'Progress' => $keyArr[4],
                                                        'Downloaded' => $keyArr[5],
                                                        'Uploaded' => $keyArr[6],
                                                        'Ratio' => $keyArr[7],
                                                        'upSpeed' => $keyArr[8],
                                                        'downSpeed' => $keyArr[9],
                                                        'eta' => $keyArr[10],
                                                        'Labels' => $keyArr[11],
                                                        'PeersConnected' => $keyArr[12],
                                                        'PeersInSwarm' => $keyArr[13],
                                                        'SeedsConnected' => $keyArr[14],
                                                        'SeedsInSwarm' => $keyArr[15],
                                                        'Availability' => $keyArr[16],
                                                        'TorrentQueueOrder' => $keyArr[17],
                                                        'Remaining' => $keyArr[18],
                                                        'DownloadUrl' => $keyArr[19],
                                                        'RssFeedUrl' => $keyArr[20],
                                                        'Message' => $keyArr[21],
                                                        'StreamId' => $keyArr[22],
                                                        'DateAdded' => $keyArr[23],
                                                        'DateCompleted' => $keyArr[24],
                                                        'AppUpdateUrl' => $keyArr[25],
                                                        'RootDownloadPath' => $keyArr[26],
                                                        'Unknown27' => $keyArr[27],
                                                        'Unknown28' => $keyArr[28],
                                                        'Status' => $Status,
                                                        'Percent' => str_replace(' ', '', $matches['Percentage']),
                                                );
						array_push($valueArray, $value);
					}
				}
				$api['content']['queueItems'] = $valueArray;
				$api['content'] = $api['content'] ?? false;
				$this->setAPIResponse('success', null, 200, $api);
				return $api;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'uTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	
}
