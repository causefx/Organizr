<?php

trait TransmissionHomepageItem
{
	public function transmissionSettingsArray()
	{
		return array(
			'name' => 'Transmission',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/transmission.png',
			'category' => 'Downloader',
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
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageDownloadRefresh'],
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
	}
	
	public function testConnectionTransmission()
	{
		if (empty($this->config['transmissionURL'])) {
			$this->setAPIResponse('error', 'Transmission URL is not defined', 422);
			return false;
		}
		$digest = $this->qualifyURL($this->config['transmissionURL'], true);
		$passwordInclude = ($this->config['transmissionUsername'] != '' && $this->config['transmissionPassword'] != '') ? $this->config['transmissionUsername'] . ':' . $this->decrypt($this->config['transmissionPassword']) . "@" : '';
		$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . '/rpc';
		try {
			$options = ($this->localURL($this->config['transmissionURL'])) ? array('verify' => false) : array();
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
	
	public function getTransmissionHomepageQueue()
	{
		if (!$this->config['homepageTransmissionEnabled']) {
			$this->setAPIResponse('error', 'Transmission homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageTransmissionAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['transmissionURL'])) {
			$this->setAPIResponse('error', 'Transmission URL is not defined', 422);
			return false;
		}
		$digest = $this->qualifyURL($this->config['transmissionURL'], true);
		$passwordInclude = ($this->config['transmissionUsername'] != '' && $this->config['transmissionPassword'] != '') ? $this->config['transmissionUsername'] . ':' . $this->decrypt($this->config['transmissionPassword']) . "@" : '';
		$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . '/rpc';
		try {
			$options = ($this->localURL($this->config['transmissionURL'])) ? array('verify' => false) : array();
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
						$torrents = json_decode($response->body, true);
					}
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
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}