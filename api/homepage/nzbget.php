<?php

trait NZBGetHomepageItem
{
	public function nzbgetSettingsArray()
	{
		return array(
			'name' => 'NZBGet',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/nzbget.png',
			'category' => 'Downloader',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageNzbgetEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageNzbgetEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageNzbgetAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageNzbgetAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'nzbgetURL',
						'label' => 'URL',
						'value' => $this->config['nzbgetURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'input',
						'name' => 'nzbgetUsername',
						'label' => 'Username',
						'value' => $this->config['nzbgetUsername']
					),
					array(
						'type' => 'password',
						'name' => 'nzbgetPassword',
						'label' => 'Password',
						'value' => $this->config['nzbgetPassword']
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
						'name' => 'nzbgetCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['nzbgetCombine']
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
						'attr' => 'onclick="testAPIConnection(\'nzbget\')"'
					),
				)
			)
		);
	}
	
	public function testConnectionNZBGet()
	{
		if (empty($this->config['nzbgetURL'])) {
			$this->setAPIResponse('error', 'NZBGet URL is not defined', 422);
			return false;
		}
		try {
			$url = $this->qualifyURL($this->config['nzbgetURL']);
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$urlGroups = $url . '/jsonrpc/listgroups';
			if ($this->config['nzbgetUsername'] !== '' && $this->decrypt($this->config['nzbgetPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Basic(array($this->config['nzbgetUsername'], $this->decrypt($this->config['nzbgetPassword']))));
				$options = array_merge($options, $credentials);
			}
			$response = Requests::get($urlGroups, array(), $options);
			if ($response->success) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			} else {
				$this->setAPIResponse('success', 'NZBGet: An Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function nzbgetHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageNzbgetEnabled'
				],
				'auth' => [
					'homepageNzbgetAuth'
				],
				'not_empty' => [
					'nzbgetURL'
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
	
	public function homepageOrdernzbget()
	{
		if ($this->homepageItemPermissions($this->nzbgetHomepagePermissions('main'))) {
			$loadingBox = ($this->config['nzbgetCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['nzbgetCombine']) ? 'buildDownloaderCombined(\'nzbget\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("nzbget"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrdernzbget
		                ' . $builder . '
		                homepageDownloader("nzbget", "' . $this->config['homepageDownloadRefresh'] . '");
		                // End homepageOrdernzbget
	                </script>
				</div>
				';
		}
	}
	
	public function getNzbgetHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->nzbgetHomepagePermissions('main'), true)) {
			return false;
		}
		try {
			$url = $this->qualifyURL($this->config['nzbgetURL']);
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$urlGroups = $url . '/jsonrpc/listgroups';
			$urlHistory = $url . '/jsonrpc/history';
			if ($this->config['nzbgetUsername'] !== '' && $this->decrypt($this->config['nzbgetPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Basic(array($this->config['nzbgetUsername'], $this->decrypt($this->config['nzbgetPassword']))));
				$options = array_merge($options, $credentials);
			}
			$response = Requests::get($urlGroups, array(), $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
			$response = Requests::get($urlHistory, array(), $options);
			if ($response->success) {
				$api['content']['historyItems'] = json_decode($response->body, true);
			}
			$api['content'] = isset($api['content']) ? $api['content'] : false;
			$this->setAPIResponse('success', null, 200, $api);
			return $api;
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
}