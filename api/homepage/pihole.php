<?php

trait PiHoleHomepageItem
{
	public function piholeSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Pi-hole',
			'enabled' => true,
			'image' => 'plugins/images/tabs/pihole.png',
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
					$this->settingsOption('enable', 'homepagePiholeEnabled'),
					$this->settingsOption('auth', 'homepagePiholeAuth'),
				],
				'Connection' => [
					$this->settingsOption('multiple-url', 'piholeURL', ['help' => 'Please make sure to use local IP address and port and to include \'/admin/\' at the end of the URL. You can add multiple Pi-holes by comma separating the URLs.', 'placeholder' => 'http(s)://hostname:port/admin/']),
					$this->settingsOption('multiple-token', 'piholeToken'),
				],
				'Misc' => [
					$this->settingsOption('toggle-title', 'piholeHeaderToggle'),
					$this->settingsOption('switch', 'homepagePiholeCombine', ['label' => 'Combine stat cards', 'help' => 'This controls whether to combine the stats for multiple pihole instances into 1 card.']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'pihole'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function testConnectionPihole()
	{
		if (empty($this->config['piholeURL'])) {
			$this->setAPIResponse('error', 'Pihole URL is not defined', 422);
			return false;
		}
		$failed = false;
		$errors = '';
		$list = $this->csvHomepageUrlToken($this->config['piholeURL'], $this->config['piholeToken']);
		foreach ($list as $key => $value) {
			$url = $value['url'] . '/api.php?status';
			if ($value['token'] !== '' && $value['token'] !== null) {
				$url = $url . '&auth=' . $value['token'];
			}
			$ip = $this->qualifyURL($url, true)['host'];
			try {
				$response = Requests::get($url, [], []);
				if ($response->success) {
					$test = $this->testAndFormatString($response->body);
					if (($test['type'] !== 'json')) {
						$errors .= $ip . ': Response was not JSON';
						$failed = true;
					} else {
						if (!isset($test['data']['status'])) {
							$errors .= $ip . ': Missing API Token';
							$failed = true;
						}
					}
				}
				if (!$response->success) {
					$errors .= $ip . ': Unknown Failure';
					$failed = true;
				}
			} catch (Requests_Exception $e) {
				$failed = true;
				$errors .= $ip . ': ' . $e->getMessage();
				$this->setLoggerChannel('PiHole')->error($e);
			};
		}
		if ($failed) {
			$this->setAPIResponse('error', $errors, 500);
			return false;
		} else {
			$this->setAPIResponse('success', null, 200);
			return true;
		}
	}

	public function piholeHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepagePiholeEnabled'
				],
				'auth' => [
					'homepagePiholeAuth'
				],
				'not_empty' => [
					'piholeURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderPihole()
	{
		if ($this->homepageItemPermissions($this->piholeHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Pihole...</h2></div>
					<script>
						// Pi-hole Stats
						homepagePihole("' . $this->config['homepagePiholeRefresh'] . '");
						// End Pi-hole Stats
					</script>
				</div>
				';
		}
	}

	public function getPiholeHomepageStats()
	{
		if (!$this->homepageItemPermissions($this->piholeHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$list = $this->csvHomepageUrlToken($this->config['piholeURL'], $this->config['piholeToken']);
		foreach ($list as $key => $value) {
			$url = $value['url'] . '/api.php?summaryRaw';
			if ($value['token'] !== '' && $value['token'] !== null) {
				$url = $url . '&auth=' . $value['token'];
			}
			try {
				$response = Requests::get($url, [], []);
				if ($response->success) {
					@$piholeResults = json_decode($response->body, true);
					if (is_array($piholeResults)) {
						$ip = $this->qualifyURL($url, true)['host'];
						$api['data'][$ip] = $piholeResults;
					}
				}
			} catch (Requests_Exception $e) {
				$this->setResponse(500, $e->getMessage());
				$this->setLoggerChannel('PiHole')->error($e);
				return false;
			};
		}
		$api['options']['combine'] = $this->config['homepagePiholeCombine'];
		$api['options']['title'] = $this->config['piholeHeaderToggle'];
		$api = $api ?? null;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}