<?php

trait SabNZBdHomepageItem
{
	
	public function sabNZBdSettingsArray()
	{
		return array(
			'name' => 'SabNZBD',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/sabnzbd.png',
			'category' => 'Downloader',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageSabnzbdEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageSabnzbdEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageSabnzbdAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageSabnzbdAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'sabnzbdURL',
						'label' => 'URL',
						'value' => $this->config['sabnzbdURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'sabnzbdToken',
						'label' => 'Token',
						'value' => $this->config['sabnzbdToken']
					)
				),
				'Misc Options' => array(
					array(
						'type' => 'select',
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageDownloadRefresh'],
						'options' => $this->timeOptions()
					),
					array(
						'type' => 'switch',
						'name' => 'sabnzbdCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['sabnzbdCombine']
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
						'attr' => 'onclick="testAPIConnection(\'sabnzbd\')"'
					),
				)
			)
		);
	}
	
	public function testConnectionSabNZBd()
	{
		if (!empty($this->config['sabnzbdURL']) && !empty($this->config['sabnzbdToken'])) {
			$url = $this->qualifyURL($this->config['sabnzbdURL']);
			$url = $url . '/api?mode=queue&output=json&apikey=' . $this->config['sabnzbdToken'];
			try {
				$options = ($this->localURL($url)) ? array('verify' => false) : array();
				$response = Requests::get($url, array(), $options);
				if ($response->success) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				}
			} catch (Requests_Exception $e) {
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			};
		} else {
			$this->setAPIResponse('error', 'URL and/or Token not setup', 422);
			return 'URL and/or Token not setup';
		}
	}
	
	public function sabNZBdHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageSabnzbdEnabled'
				],
				'auth' => [
					'homepageSabnzbdAuth'
				],
				'not_empty' => [
					'sabnzbdURL',
					'sabnzbdToken'
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
	
	public function homepageOrdersabnzbd()
	{
		if ($this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'))) {
			$loadingBox = ($this->config['sabnzbdCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['sabnzbdCombine']) ? 'buildDownloaderCombined(\'sabnzbd\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("sabnzbd"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrdersabnzbd
		                ' . $builder . '
		                homepageDownloader("sabnzbd", "' . $this->config['homepageDownloadRefresh'] . '");
		                // End homepageOrdersabnzbd
	                </script>
				</div>
				';
		}
	}
	
	public function getSabNZBdHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$url = $url . '/api?mode=queue&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$url = $url . '/api?mode=history&output=json&limit=100&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['historyItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function pauseSabNZBdQueue($target = null)
	{
		if (!$this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=pause&value=' . $target . '&' : 'mode=pause';
		$url = $url . '/api?' . $id . '&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function resumeSabNZBdQueue($target = null)
	{
		if (!$this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=resume&value=' . $target . '&' : 'mode=resume';
		$url = $url . '/api?' . $id . '&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}