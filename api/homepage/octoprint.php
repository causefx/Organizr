<?php

trait OctoPrintHomepageItem
{
	public function octoprintSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Octoprint',
			'enabled' => true,
			'image' => 'plugins/images/tabs/octoprint.png',
			'category' => 'Monitor',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageOctoprintEnabled'),
					$this->settingsOption('auth', 'homepageOctoprintAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'octoprintURL'),
					$this->settingsOption('token', 'octoprintToken'),
					$this->settingsOption('disable-cert-check', 'octoprintDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'octoprintUseCustomCertificate'),
				],
				'Options' => [
					$this->settingsOption('title', 'octoprintHeader'),
					$this->settingsOption('toggle-title', 'octoprintHeaderToggle'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function octoprintHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageOctoprintEnabled'
				],
				'auth' => [
					'homepageOctoprintAuth'
				],
				'not_empty' => [
					'octoprintURL',
					'octoprintToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function homepageOrderOctoprint()
	{
		if ($this->homepageItemPermissions($this->octoprintHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading OctoPrint...</h2></div>
					<script>
						// Octoprint
						homepageOctoprint("' . $this->config['homepageOctoprintRefresh'] . '");
						// End Octoprint
					</script>
				</div>
				';
		}
	}
	
	public function getOctoprintHomepageData()
	{
		if (!$this->homepageItemPermissions($this->octoprintHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['octoprintURL']);
		$endpoints = ['job', 'settings'];
		$api['data']['url'] = $this->config['octoprintURL'];
		foreach ($endpoints as $endpoint) {
			$dataUrl = $url . '/api/' . $endpoint;
			try {
				$headers = array('X-API-KEY' => $this->config['octoprintToken']);
				$options = $this->requestOptions($url, $this->config['homepageOctoprintRefresh'], $this->config['octoprintDisableCertCheck'], $this->config['octoprintUseCustomCertificate']);
				$response = Requests::get($dataUrl, $headers, $options);
				if ($response->success) {
					$json = json_decode($response->body, true);
					$api['data'][$endpoint] = $json;
					$api['options'] = [
						'title' => $this->config['octoprintHeader'],
						'titleToggle' => $this->config['octoprintHeaderToggle'],
					];
				} else {
					$this->setAPIResponse('error', 'OctoPrint connection error', 409);
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'Octoprint Function - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			};
		}
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}