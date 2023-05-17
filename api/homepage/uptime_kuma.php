<?php

trait UptimeKumaHomepageItem
{
	public function uptimeKumaSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Uptime Kuma',
			'enabled' => true,
			'image' => 'plugins/images/tabs/kuma.png',
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
					$this->settingsOption('enable', 'homepageUptimeKumaEnabled'),
					$this->settingsOption('auth', 'homepageUptimeKumaAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'uptimeKumaURL', ['help' => 'URL for Uptime Kuma e.g. http://kuma:3001 (no trailing slash)', 'placeholder' => 'http://kuma:3001']),
					$this->settingsOption('token', 'uptimeKumaToken'),
				],
				'Options' => [
					$this->settingsOption('refresh', 'homepageUptimeKumaRefresh'),
					$this->settingsOption('title', 'homepageUptimeKumaHeader'),
					$this->settingsOption('toggle-title', 'homepageUptimeKumaHeaderToggle'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function uptimeKumaHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageUptimeKumaEnabled'
				],
				'auth' => [
					'homepageUptimeKumaAuth'
				],
				'not_empty' => [
					'uptimeKumaURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderUptimeKuma()
	{
		if ($this->homepageItemPermissions($this->uptimeKumaHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Uptime Kuma...</h2></div>
					<script>
						// Uptime Kuma
						homepageUptimeKuma("' . $this->config['homepageUptimeKumaRefresh'] . '");
						// End Uptime Kuma
					</script>
				</div>
				';
		}
	}

	public function getUptimeKumaHomepageData()
	{
		if (!$this->homepageItemPermissions($this->uptimeKumaHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['uptimeKumaURL']);
		$dataUrl = $url . '/assets/php/loop.php';
		try {
			$options = $this->requestOptions($url, $this->config['homepageUptimeKumaRefresh']);
			$response = Requests::get($dataUrl, ['Token' => $this->config['organizrAPI']], $options);
			
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('UptimeKuma')->error($e);
			$this->setAPIResponse('error', $e->getMessage(), 401);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}