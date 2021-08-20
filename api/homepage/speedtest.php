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
		$homepageSettings = array(
			'debug' => true,
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'html',
						'override' => 6,
						'label' => 'Info',
						'html' => '<p>This homepage item requires <a href="https://github.com/henrywhitaker3/Speedtest-Tracker" target="_blank" rel="noreferrer noopener">Speedtest-Tracker <i class="fa fa-external-link" aria-hidden="true"></i></a> to be running on your network.</p>'
					),
					array(
						'type' => 'switch',
						'name' => 'homepageSpeedtestEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageSpeedtestEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageSpeedtestAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageSpeedtestAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'speedtestURL',
						'label' => 'URL',
						'value' => $this->config['speedtestURL'],
						'help' => 'Enter the IP:PORT of your speedtest instance e.g. http(s)://<ip>:<port>'
					),
				),
				'Options' => array(
					array(
						'type' => 'input',
						'name' => 'speedtestHeader',
						'label' => 'Title',
						'value' => $this->config['speedtestHeader'],
						'help' => 'Sets the title of this homepage module',
					),
					array(
						'type' => 'switch',
						'name' => 'speedtestHeaderToggle',
						'label' => 'Toggle Title',
						'value' => $this->config['speedtestHeaderToggle'],
						'help' => 'Shows/hides the title of this homepage module'
					),
				),
			)
		);
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
		$dataUrl = $url . '/api/speedtest/latest';
		try {
			$response = Requests::get($dataUrl);
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
			$this->writeLog('error', 'Speedtest Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}