<?php

trait JackettHomepageItem
{
	public function jackettSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Jackett',
			'enabled' => true,
			'image' => 'plugins/images/tabs/jackett.png',
			'category' => 'Utility',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageJackettEnabled'),
					$this->settingsOption('auth', 'homepageJackettAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'jackettURL'),
					$this->settingsOption('token', 'jackettToken'),
					$this->settingsOption('disable-cert-check', 'jackettDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'jackettUseCustomCertificate'),
				],
				'Options' => [
					$this->settingsOption('switch', 'homepageJackettBackholeDownload', ['label' => 'Prefer black hole download', 'help' => 'Prefer black hole download link instead of direct/magnet download']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'jackett'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function jackettHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageJackettEnabled'
				],
				'auth' => [
					'homepageJackettAuth'
				],
				'not_empty' => [
					'jackettURL',
					'jackettToken'
				]
			],
			'test' => [
				'auth' => [
					'homepageJackettAuth'
				],
				'not_empty' => [
					'jackettURL',
					'jackettToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderJackett()
	{
		if ($this->homepageItemPermissions($this->jackettHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Jackett...</h2></div>
					<script>
						// Jackett
						homepageJackett();
						// End Jackett
					</script>
				</div>
				';
		}
	}

	public function testConnectionJackett()
	{
		if (!$this->homepageItemPermissions($this->jackettHomepagePermissions('test'), true)) {
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['jackettURL']);
		$endpoint = $apiURL . '/api/v2.0/indexers/all/results?apikey=' . $this->config['jackettToken'] . '&Query=this-is-just-a-test-for-organizr';
		try {
			$headers = [];
			$options = $this->requestOptions($apiURL, 120, $this->config['jackettDisableCertCheck'], $this->config['jackettUseCustomCertificate']);
			$response = Requests::get($endpoint, $headers, $options);
			if ($response->success) {
				$apiData = json_decode($response->body, true);
				$api['content'] = $apiData;
				unset($apiData);
			} else {
				$this->setResponse(403, 'Error connecting to Jackett');
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setResponse(500, $e->getMessage());
			return false;
		};
		$api['content'] = $api['content'] ?? false;
		$this->setResponse(200, null, $api);
		return $api;
	}

	public function searchJackettIndexers($query = null)
	{
		if (!$this->homepageItemPermissions($this->jackettHomepagePermissions('main'), true)) {
			return false;
		}
		if (!$query) {
			$this->setAPIResponse('error', 'Query was not supplied', 422);
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['jackettURL']);
		$endpoint = $apiURL . '/api/v2.0/indexers/all/results?apikey=' . $this->config['jackettToken'] . '&Query=' . urlencode($query);
		try {
			$headers = [];
			$options = $this->requestOptions($apiURL, 120, $this->config['jackettDisableCertCheck'], $this->config['jackettUseCustomCertificate']);
			$response = Requests::get($endpoint, $headers, $options);
			if ($response->success) {
				$apiData = json_decode($response->body, true);
				$api['content'] = $apiData;
				unset($apiData);
			}
		} catch (Requests_Exception $e) {
			$this->setResponse(500, $e->getMessage());
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}

	public function performJackettBackHoleDownload($url = null)
	{
		if (!$this->homepageItemPermissions($this->jackettHomepagePermissions('main'), true)) {
			return false;
		}
		if (!$url) {
			$this->setAPIResponse('error', 'URL was not supplied', 422);
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['jackettURL']);
		$endpoint = $apiURL . $url;
		error_log($endpoint);
		try {
			$headers = [];
			$options = $this->requestOptions($apiURL, 120, $this->config['jackettDisableCertCheck'], $this->config['jackettUseCustomCertificate']);
			$response = Requests::get($endpoint, $headers, $options);
			if ($response->success) {
				$apiData = json_decode($response->body, true);
				$api['content'] = $apiData;
				unset($apiData);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Jackett blackhole download failed ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		if ($api['content'] && $api['content']['result'] == 'success') {
			$this->setAPIResponse('success', null, 200, $api);
		} else if ($api['content']) {
			$this->setAPIResponse('error', $api['content']['error'], 400, $api);
		} else {
			$this->setAPIResponse('error', 'Unknown error', 400, $api);
		}
		return $api;
	}
}