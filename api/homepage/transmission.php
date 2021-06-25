<?php

trait TransmissionHomepageItem
{
	public function transmissionSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Transmission',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/transmission.png',
			'category' => 'Downloader',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = array(
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageTransmissionEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageTransmissionEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageTransmissionAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageTransmissionAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'transmissionURL',
						'label' => 'URL',
						'value' => $this->config['transmissionURL'],
						'help' => 'Please do not included /web in URL.  Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'switch',
						'name' => 'transmissionDisableCertCheck',
						'label' => 'Disable Certificate Check',
						'value' => $this->config['transmissionDisableCertCheck']
					),
					array(
						'type' => 'input',
						'name' => 'transmissionUsername',
						'label' => 'Username',
						'value' => $this->config['transmissionUsername']
					),
					array(
						'type' => 'password',
						'name' => 'transmissionPassword',
						'label' => 'Password',
						'value' => $this->config['transmissionPassword']
					)
				),
				'Misc Options' => array(
					array(
						'type' => 'switch',
						'name' => 'transmissionHideSeeding',
						'label' => 'Hide Seeding',
						'value' => $this->config['transmissionHideSeeding']
					), array(
						'type' => 'switch',
						'name' => 'transmissionHideCompleted',
						'label' => 'Hide Completed',
						'value' => $this->config['transmissionHideCompleted']
					),
					array(
						'type' => 'select',
						'name' => 'transmissionRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['transmissionRefresh'],
						'options' => $this->timeOptions()
					),
					array(
						'type' => 'switch',
						'name' => 'transmissionCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['transmissionCombine']
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
						'attr' => 'onclick="testAPIConnection(\'transmission\')"'
					),
				)
			)
		);
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionTransmission()
	{
		if (empty($this->config['transmissionURL'])) {
			$this->setAPIResponse('error', 'Transmission URL is not defined', 422);
			return false;
		}
		$digest = $this->qualifyURL($this->config['transmissionURL'], true);
		$passwordInclude = ($this->config['transmissionUsername'] != '' && $this->config['transmissionPassword'] != '') ? $this->config['transmissionUsername'] . ':' . rawurlencode($this->decrypt($this->config['transmissionPassword'])) . "@" : '';
		$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . '/rpc';
		try {
			$options = $this->requestOptions($this->config['transmissionURL'], $this->config['transmissionDisableCertCheck'], $this->config['transmissionRefresh']);
			$response = Requests::get($url, array(), $options);
			if ($response->headers['x-transmission-session-id']) {
				$headers = array(
					'X-Transmission-Session-Id' => $response->headers['x-transmission-session-id'],
					'Content-Type' => 'application/json'
				);
				$data = array(
					'method' => 'torrent-get',
					'arguments' => array(
						'fields' => array(
							"id", "name", "totalSize", "eta", "isFinished", "isStalled", "percentDone", "rateDownload", "status", "downloadDir", "errorString"
						),
					),
					'tags' => ''
				);
				$response = Requests::post($url, $headers, json_encode($data), $options);
				if ($response->success) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				} else {
					$this->setAPIResponse('error', 'Transmission Connect Function - Error: Unknown', 500);
					return false;
				}
			} else {
				$this->writeLog('error', 'Transmission Connect Function - Error: Could not get session ID', 'SYSTEM');
				$this->setAPIResponse('error', 'Transmission Connect Function - Error: Could not get session ID', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Transmission Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function transmissionHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageTransmissionEnabled'
				],
				'auth' => [
					'homepageTransmissionAuth'
				],
				'not_empty' => [
					'transmissionURL'
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
	
	public function homepageOrdertransmission()
	{
		if ($this->homepageItemPermissions($this->transmissionHomepagePermissions('main'))) {
			$loadingBox = ($this->config['transmissionCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['transmissionCombine']) ? 'buildDownloaderCombined(\'transmission\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("transmission"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrdertransmission
		                ' . $builder . '
		                homepageDownloader("transmission", "' . $this->config['transmissionRefresh'] . '");
		                // End homepageOrdertransmission
	                </script>
				</div>
				';
		}
	}
	
	public function getTransmissionHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->transmissionHomepagePermissions('main'), true)) {
			return false;
		}
		$digest = $this->qualifyURL($this->config['transmissionURL'], true);
		$passwordInclude = ($this->config['transmissionUsername'] != '' && $this->config['transmissionPassword'] != '') ? $this->config['transmissionUsername'] . ':' . rawurlencode($this->decrypt($this->config['transmissionPassword'])) . "@" : '';
		$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . '/rpc';
		try {
			$options = $this->requestOptions($this->config['transmissionURL'], $this->config['transmissionDisableCertCheck'], $this->config['transmissionRefresh']);
			$response = Requests::get($url, array(), $options);
			if ($response->headers['x-transmission-session-id']) {
				$headers = array(
					'X-Transmission-Session-Id' => $response->headers['x-transmission-session-id'],
					'Content-Type' => 'application/json'
				);
				$data = array(
					'method' => 'torrent-get',
					'arguments' => array(
						'fields' => array(
							"id", "name", "totalSize", "eta", "isFinished", "isStalled", "percentDone", "rateDownload", "status", "downloadDir", "errorString", "addedDate"
						),
					),
					'tags' => ''
				);
				$response = Requests::post($url, $headers, json_encode($data), $options);
				if ($response->success) {
					$torrentList = json_decode($response->body, true)['arguments']['torrents'];
					if ($this->config['transmissionHideSeeding'] || $this->config['transmissionHideCompleted']) {
						$filter = array();
						$torrents = array();
						if ($this->config['transmissionHideSeeding']) {
							array_push($filter, 6, 5);
						}
						if ($this->config['transmissionHideCompleted']) {
							array_push($filter, 0);
						}
						foreach ($torrentList as $key => $value) {
							if (!in_array($value['status'], $filter)) {
								$torrents[] = $value;
							}
						}
					} else {
						$torrents = json_decode($response->body, true)['arguments']['torrents'];
					}
					usort($torrents, function ($a, $b) {
						return $a["addedDate"] <=> $b["addedDate"];
					});
					$api['content']['queueItems'] = $torrents;
					$api['content']['historyItems'] = false;
				}
			} else {
				$this->writeLog('error', 'Transmission Connect Function - Error: Could not get session ID', 'SYSTEM');
				$this->setAPIResponse('error', 'Transmission Connect Function - Error: Could not get session ID', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Transmission Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = $api['content'] ?? false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}