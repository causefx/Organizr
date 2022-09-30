<?php

trait ProwlarrHomepageItem
{
	public function prowlarrSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Prowlarr',
			'enabled' => true,
			'image' => 'plugins/images/tabs/prowlarr.png',
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
					$this->settingsOption('enable', 'homepageProwlarrEnabled'),
					$this->settingsOption('auth', 'homepageProwlarrAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'prowlarrURL'),
					$this->settingsOption('token', 'prowlarrToken'),
					$this->settingsOption('disable-cert-check', 'prowlarrDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'prowlarrUseCustomCertificate'),
				],
				'Options' => [
					$this->settingsOption('switch', 'homepageProwlarrBackholeDownload', ['label' => 'Prefer black hole download', 'help' => 'Prefer black hole download link instead of direct/magnet download']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'prowlarr'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function prowlarrHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageProwlarrEnabled'
				],
				'auth' => [
					'homepageProwlarrAuth'
				],
				'not_empty' => [
					'prowlarrURL',
					'prowlarrToken'
				]
			],
			'test' => [
				'auth' => [
					'homepageProwlarrAuth'
				],
				'not_empty' => [
					'prowlarrURL',
					'prowlarrToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderProwlarr()
	{
		if ($this->homepageItemPermissions($this->prowlarrHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Prowlarr...</h2></div>
					<script>
						// Prowlarr
						homepageProwlarr();
						// End Prowlarr
					</script>
				</div>
				';
		}
	}

	public function testConnectionProwlarr()
	{
		if (!$this->homepageItemPermissions($this->prowlarrHomepagePermissions('test'), true)) {
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['prowlarrURL']);
		$endpoint = $apiURL . '/api/v1/search?apikey=' . $this->config['prowlarrToken'] . '&query=this-is-just-a-test-for-organizr';
		try {
			$headers = [];
			$options = $this->requestOptions($apiURL, 120, $this->config['prowlarrDisableCertCheck'], $this->config['prowlarrUseCustomCertificate']);
			$response = Requests::get($endpoint, $headers, $options);
			if ($response->success) {
				$apiData = json_decode($response->body, true);
				$api['content'] = $apiData;
				unset($apiData);
			} else {
				$this->setResponse(403, 'Error connecting to Prowlarr');
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

	public function searchProwlarrIndexers($query = null)
	{
		if (!$this->homepageItemPermissions($this->prowlarrHomepagePermissions('main'), true)) {
			return false;
		}
		if (!$query) {
			$this->setAPIResponse('error', 'Query was not supplied', 422);
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['prowlarrURL']);
		$endpoint = $apiURL . '/api/v1/search?apikey=' . $this->config['prowlarrToken'] . '&query=' . urlencode($query);
		try {
			$headers = [];
			$options = $this->requestOptions($apiURL, 120, $this->config['prowlarrDisableCertCheck'], $this->config['prowlarrUseCustomCertificate']);
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

	public function performProwlarrBackHoleDownload($guid = null, $indexerId = null)
	{
		if (!$this->homepageItemPermissions($this->prowlarrHomepagePermissions('main'), true)) {
			return false;
		}
		if (!$guid) {
			$this->setAPIResponse('error', 'guid was not supplied', 422);
			return false;
		}
		if (!$indexerId) {
			$this->setAPIResponse('error', 'indexerId was not supplied', 422);
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['prowlarrURL']);
		$endpoint = $apiURL . '/api/v1/search?apikey=' . $this->config['prowlarrToken'];
		try {
			$headers = [];
			$data = ['guid'=>$guid,'indexerId'=>$indexerId];
			$options = $this->requestOptions($apiURL, 120, $this->config['prowlarrDisableCertCheck'], $this->config['prowlarrUseCustomCertificate']);
			$ch = curl_init($endpoint);
			$payload = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);
			if ($response) {
				$api['content'] = $response;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Prowlarr')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		if ($api['content']) {
			$this->setAPIResponse('success', null, 200, $api);
		} else {
			$this->setAPIResponse('error', 'Unknown error', 400, $api);
		}
		return $api;
	}
}