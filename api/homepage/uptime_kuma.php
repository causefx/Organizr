<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

trait UptimeKumaHomepageItem
{
	private static Client $kumaClient;

	public function uptimeKumaSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'UptimeKuma',
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
					$this->settingsOption('switch', 'homepageUptimeKumaCompact', ['label' => 'Compact view', 'help' => 'Toggles the compact view of this homepage module']),
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
					'uptimeKumaURL',
					'uptimeKumaToken',
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
		try {
			$metrics = (new UptimeKumaMetrics(
				$this->getKumaClient($url, $this->config['uptimeKumaToken'])
					->get('/metrics')
					->getBody()
					->getContents()
			))->process();

			$api = [
				'data' => $metrics->getMonitors(),
				'options' => [
					'title' => $this->config['homepageUptimeKumaHeader'],
					'titleToggle' => $this->config['homepageUptimeKumaHeaderToggle'],
					'compact' => $this->config['homepageUptimeKumaCompact'],
				]
			];
		} catch (GuzzleException $e) {
			$this->setLoggerChannel('UptimeKuma')->error($e);
			$this->setAPIResponse('error', $e->getMessage(), 401);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}

	private function getKumaClient(string $url, string $token): Client
	{
		if (!isset(static::$kumaClient)) {
			static::$kumaClient = new Client([
				'base_uri' => $url,
				'headers' => [
					'Authorization' => sprintf("Basic %s", base64_encode(':'.$token)),
				],
			]);
		}

		return static::$kumaClient;
	}
}
