<?php

trait NZBGetHomepageItem
{
	public function nzbgetSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'NZBGet',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/nzbget.png',
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
					$this->settingsOption('enable', 'homepageNzbgetEnabled'),
					$this->settingsOption('auth', 'homepageNzbgetAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'nzbgetURL'),
					$this->settingsOption('blank'),
					$this->settingsOption('username', 'nzbgetUsername'),
					$this->settingsOption('password', 'nzbgetPassword'),
					$this->settingsOption('disable-cert-check', 'nzbgetDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'nzbgetUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'nzbget'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'nzbgetSocksEnabled'),
					$this->settingsOption('auth', 'nzbgetSocksAuth'),
				],
				'Misc Options' => [
					$this->settingsOption('refresh', 'nzbgetRefresh'),
					$this->settingsOption('combine', 'nzbgetCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'nzbget'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionNZBGet()
	{
		if (empty($this->config['nzbgetURL'])) {
			$this->setAPIResponse('error', 'NZBGet URL is not defined', 422);
			return false;
		}
		try {
			$url = $this->qualifyURL($this->config['nzbgetURL']);
			$options = $this->requestOptions($url, null, $this->config['nzbgetDisableCertCheck'], $this->config['nzbgetUseCustomCertificate']);
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
		return $this->homepageCheckKeyPermissions($key, $permissions);
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
		                homepageDownloader("nzbget", "' . $this->config['nzbgetRefresh'] . '");
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
			$options = $this->requestOptions($url, $this->config['nzbgetRefresh'], $this->config['nzbgetDisableCertCheck'], $this->config['nzbgetUseCustomCertificate']);
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