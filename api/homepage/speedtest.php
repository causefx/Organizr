<?php

trait SpeedTestHomepageItem
{
	public function speedTestSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Speedtest',
			'enabled' => true,
			'image' => 'plugins/images/tabs/speedtest-icon.png',
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
					$this->settingsOption('html', null, ['override' => 6, 'label' => 'Info', 'html' => '<p>This homepage item requires <a href="https://github.com/henrywhitaker3/Speedtest-Tracker" target="_blank" rel="noreferrer noopener">Speedtest-Tracker <i class="fa fa-external-link" aria-hidden="true"></i></a> to be running on your network.</p>']),
					$this->settingsOption('enable', 'homepageSpeedtestEnabled'),
					$this->settingsOption('auth', 'homepageSpeedtestAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'speedtestURL'),
					$this->settingsOption('disable-cert-check', 'speedtestDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'speedtestUseCustomCertificate'),
				],
				'Options' => [
					$this->settingsOption('title', 'speedtestHeader'),
					$this->settingsOption('toggle-title', 'speedtestHeaderToggle'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function speedTestHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageSpeedtestEnabled'
				],
				'auth' => [
					'homepageSpeedtestAuth'
				],
				'not_empty' => [
					'speedtestURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderSpeedtest()
	{
		if ($this->homepageItemPermissions($this->speedTestHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Speedtest...</h2></div>
					<script>
						// Speedtest
						homepageSpeedtest("' . $this->config['homepageSpeedtestRefresh'] . '");
						// End Speedtest
					</script>
				</div>
				';
		}
	}

	public function getSpeedtestHomepageData()
	{
		if (!$this->homepageItemPermissions($this->speedTestHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['speedtestURL']);
		$options = $this->requestOptions($url, null, $this->config['speedtestDisableCertCheck'], $this->config['speedtestUseCustomCertificate']);
		$dataUrl = $url . '/api/speedtest/latest';
		try {
			$response = Requests::get($dataUrl, [], $options);
			if ($response->success) {
				$json = json_decode($response->body, true);
				$api['data'] = [
					'current' => $json['data'],
				];
				$keys = [
					'average',
					'max',
					'maximum',
					'minimum'
				];
				foreach ($keys as $key) {
					if (array_key_exists($key, $json)) {
						if ($key == 'max') {
							$api['data']['maximum'] = $json[$key];
						} else {
							$api['data'][$key] = $json[$key];
						}
					}
				}
				$api['options'] = [
					'title' => $this->config['speedtestHeader'],
					'titleToggle' => $this->config['speedtestHeaderToggle'],
				];
			} else {
				$this->setAPIResponse('error', 'SpeedTest connection error', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Speedtest')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}