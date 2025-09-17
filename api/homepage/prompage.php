<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

trait PromPageHomepageItem
{
	private static Client $kumaClient;

	public function promPageSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'PromPage',
			'enabled' => true,
			'image' => 'plugins/images/tabs/prompage.png',
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
					$this->settingsOption('html', null, ['override' => 6, 'label' => 'Info', 'html' => '<p>This homepage item requires <a href="https://github.com/henrywhitaker3/prompage" target="_blank" rel="noreferrer noopener">PromPage <i class="fa fa-external-link" aria-hidden="true"></i></a> to be running.</p>']),
					$this->settingsOption('enable', 'homepagePromPageEnabled'),
				],
				'Connection' => [
					$this->settingsOption('url', 'promPageURL', ['help' => 'URL for Uptime Kuma e.g. http://kuma:3001 (no trailing slash)', 'placeholder' => 'http://prompage:3000']),
				],
				'Options' => [
					$this->settingsOption('refresh', 'homepagePromPageRefresh'),
					$this->settingsOption('title', 'homepagePromPageHeader'),
					$this->settingsOption('toggle-title', 'homepagePromPageHeaderToggle'),
					$this->settingsOption('switch', 'homepagePromPageCompact', ['label' => 'Compact view', 'help' => 'Toggles the compact view of this homepage module']),
					$this->settingsOption('switch', 'homepagePromPageShowUptime', ['label' => 'Show monitor uptime']),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function promPageHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepagePromPageEnabled'
				],
				'not_empty' => [
					'promPageURL',
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderPromPage()
	{
		if ($this->homepageItemPermissions($this->promPageHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Status Page...</h2></div>
					<script>
						// PromPage
						homepagePromPage("' . $this->config['homepagePromPageRefresh'] . '");
						// End PromPage
					</script>
				</div>
				';
		}
	}

	public function getpromPageHomepageData()
	{
		if (!$this->homepageItemPermissions($this->promPageHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['promPageURL']);
		try {
			$services = json_decode($this->getPromPageClient($url, $this->config['promPageToken'])
					->get('/api/services')
					->getBody()
					->getContents())->services;

			$api = [
				'data' => $services,
				'options' => [
					'title' => $this->config['homepagePromPageHeader'],
					'titleToggle' => $this->config['homepagePromPageHeaderToggle'],
					'compact' => $this->config['homepagePromPageCompact'],
					'showUptime' => $this->config['homepagePromPageShowUptime'],
				]
			];
		} catch (GuzzleException $e) {
			$this->setLoggerChannel('promPage')->error($e);
			$this->setAPIResponse('error', $e->getMessage(), 401);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}

	private function getPromPageClient(string $url): Client
	{
		if (!isset(static::$kumaClient)) {
			static::$kumaClient = new Client([
				'base_uri' => $url,
			]);
		}

		return static::$kumaClient;
	}
}
