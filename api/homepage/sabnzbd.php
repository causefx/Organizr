<?php

trait SabNZBdHomepageItem
{
	
	public function sabNZBdSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'SabNZBD',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/sabnzbd.png',
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
					$this->settingsOption('enable', 'homepageSabnzbdEnabled'),
					$this->settingsOption('auth', 'homepageSabnzbdAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'sabnzbdURL'),
					$this->settingsOption('token', 'sabnzbdToken'),
					$this->settingsOption('disable-cert-check', 'sabnzbdDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'sabnzbdUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'sabnzbd'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'sabnzbdSocksEnabled'),
					$this->settingsOption('auth', 'sabnzbdSocksAuth'),
				],
				'Misc Options' => [
					$this->settingsOption('refresh', 'sabnzbdRefresh'),
					$this->settingsOption('combine', 'sabnzbdCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'sabnzbd'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionSabNZBd()
	{
		$this->setLoggerChannel('Sabnzbd Homepage');
		$this->logger->debug('Starting API Connection Test');
		if (!empty($this->config['sabnzbdURL']) && !empty($this->config['sabnzbdToken'])) {
			$url = $this->qualifyURL($this->config['sabnzbdURL']);
			$url = $url . '/api?mode=queue&output=json&apikey=' . $this->config['sabnzbdToken'];
			try {
				$options = $this->requestOptions($url, null, $this->config['sabnzbdDisableCertCheck'], $this->config['sabnzbdUseCustomCertificate']);
				$response = Requests::get($url, [], $options);
				if ($response->success) {
					$data = json_decode($response->body, true);
					$status = 'success';
					$responseCode = 200;
					$message = 'API Connection succeeded';
					if (isset($data['error'])) {
						$status = 'error';
						$responseCode = 500;
						$message = $data['error'];
					}
					$this->setAPIResponse($status, $message, $responseCode, $data);
					$this->logger->debug('API Connection Test was successful');
					return true;
				} else {
					$this->setAPIResponse('error', $response->body, 500);
					$this->logger->debug('API Connection Test was unsuccessful');
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->logger->critical($e, [$url]);
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			}
		} else {
			$this->logger->debug('URL and/or Token not setup');
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
		return $this->homepageCheckKeyPermissions($key, $permissions);
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
		                homepageDownloader("sabnzbd", "' . $this->config['sabnzbdRefresh'] . '");
		                // End homepageOrdersabnzbd
	                </script>
				</div>
				';
		}
	}
	
	public function getSabNZBdHomepageQueue()
	{
		$this->setLoggerChannel('Sabnzbd Homepage');
		if (!$this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$url = $url . '/api?mode=queue&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = $this->requestOptions($url, $this->config['sabnzbdRefresh'], $this->config['sabnzbdDisableCertCheck'], $this->config['sabnzbdUseCustomCertificate']);
			$response = Requests::get($url, [], $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->logger->critical($e, [$url]);
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
			$this->logger->critical($e, [$url]);
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function pauseSabNZBdQueue($target = null)
	{
		$this->setLoggerChannel('Sabnzbd Homepage');
		if (!$this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=pause&value=' . $target . '&' : 'mode=pause';
		$url = $url . '/api?' . $id . '&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = $this->requestOptions($url, $this->config['sabnzbdRefresh'], $this->config['sabnzbdDisableCertCheck'], $this->config['sabnzbdUseCustomCertificate']);
			$response = Requests::get($url, [], $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->logger->critical($e, [$url]);
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function resumeSabNZBdQueue($target = null)
	{
		$this->setLoggerChannel('Sabnzbd Homepage');
		if (!$this->homepageItemPermissions($this->sabNZBdHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['sabnzbdURL']);
		$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=resume&value=' . $target . '&' : 'mode=resume';
		$url = $url . '/api?' . $id . '&output=json&apikey=' . $this->config['sabnzbdToken'];
		try {
			$options = $this->requestOptions($url, $this->config['sabnzbdRefresh'], $this->config['sabnzbdDisableCertCheck'], $this->config['sabnzbdUseCustomCertificate']);
			$response = Requests::get($url, [], $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			$this->logger->critical($e, [$url]);
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}