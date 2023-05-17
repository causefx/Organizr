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
			$response = $this->getKumaClient($url, $this->config['uptimeKumaToken'])->get('/metrics');

			$body = $response->getBody()->getContents();

			$body = explode(PHP_EOL, $body);
			$body = array_filter($body, function (string $item) {
				return str_starts_with($item, 'monitor_status');
			});
			$body = array_map(function (string $item) {
				try {
					return $this->parseUptimeKumaStatus($item);
				} catch (Exception $e) {
					// do nothing when monitor is disabled
				}
			}, $body);
			$api = array_values(array_filter($body));
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

	private function parseUptimeKumaStatus(string $status): array
	{
		if (substr($status, -1) === '2') {
			throw new Exception("monitor diasbled");
		}

		$up = (substr($status, -1)) == '0' ? false : true;
		$status = substr($status, 15);
		$status = substr($status, 0, -4);
		$status = explode(',', $status);
		$data = [
			'name' => $this->getStringBetweenQuotes($status[0]),
			'url' => $this->getStringBetweenQuotes($status[2]),
			'type' => $this->getStringBetweenQuotes($status[1]),
			'status' => $up,
		];

		return $data;
	}

	private function getStringBetweenQuotes(string $input): string
	{
		if (preg_match('/"(.*?)"/', $input, $match) == 1) {
			return $match[1];
		}
		return '';
	} 
}