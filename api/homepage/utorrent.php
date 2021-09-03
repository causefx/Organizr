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

			$doc = new DOMDocument();
			$doc->loadHTML($response->body);
			$id = $doc->getElementById('token');
			$uTorrentConfig = new stdClass();
			$uTorrentConfig->uTorrentToken = $id->textContent;

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

			$queryUrl = '/gui/?token='.$this->config['uTorrentToken'].'&list=1';
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

			if ($httpResponse == 200) {
	                        $responseData = json_decode($response->body);
				$keyArray = (array) $responseData->torrents;
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
							'Name' => $keyArr[2],
							'Labels' => $keyArr[11],
							'Percent' => str_replace(' ', '', $matches['Percentage']),
							'Status' => $Status,
							'Availability' => $keyArr[4],
							'Done' => $keyArr[5],
							'Size' => $keyArr[3],
							'upSpeed' => $keyArr[8],
							'downSpeed' => $keyArr[9],
							'Message' => $keyArr[21],
						);
						array_push($valueArray, $value);
					}
				}
	                        $api['content']['queueItems'] = $valueArray;
	                        $api['content'] = $api['content'] ?? false;
	                        $this->setAPIResponse('success', null, 200, $api);
	                        return $api;
                        } else if ($httpResponse == 400) {
	                        $this->writeLog('warn', 'uTorrent Token or Cookie Expired. Generating new session..', 'SYSTEM');
				$this->getuTorrentToken();
				$response = Requests::get($url, $headers, $options);
				$responseData = json_decode($response->body);
                                $api['content']['queueItems'] = json_encode($responseData->torrents);
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
