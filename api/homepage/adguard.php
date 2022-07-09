<?php

trait AdGuardHomepageItem
{
	public function adguardSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'AdGuardHome',
			'enabled' => true,
			'image' => 'plugins/images/tabs/AdGuardHome.png',
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
					$this->settingsOption('enable', 'homepageAdGuardEnabled'),
					$this->settingsOption('auth', 'homepageAdGuardAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'adguardURL', ['help' => 'Please make sure to use local IP address and port and to include \'/admin/\' at the end of the URL. You can add multiple Pi-holes by comma separating the URLs.', 'placeholder' => 'http(s)://hostname:port/admin/']),
          $this->settingsOption('username', 'adGuardUsername'),
					$this->settingsOption('password', 'adGuardPassword'),
        ],
				'Misc' => [
					$this->settingsOption('toggle-title', 'adguardToggle'),
					$this->settingsOption('switch', 'homepageAdGuardCombine', ['label' => 'Combine stat cards', 'help' => 'This controls whether to combine the stats for multiple adguard instances into 1 card.']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'adguard'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function testConnectionAdGuard()
	{
		if (empty($this->config['adguardURL'])) {
			$this->setAPIResponse('error', 'AdGuard URL is not defined', 422);
			return false;
		}
		$api = array();
		$failed = false;
		$errors = '';
		$urls = explode(',', $this->config['adguardURL']);
		foreach ($urls as $url) {
			$url = $url . '/control/stats';
			try {
				$options = array(
					'auth' => array($this->config['adGuardUsername'], $this->decrypt($this->config['adGuardPassword']))
				);
				$response = Requests::get($url, [], $options);
				if ($response->success) {
					@$test = json_decode($response->body, true);
					if (!is_array($test)) {
						$ip = $this->qualifyURL($url, true)['host'];
						$errors .= $ip . ': Response was not JSON';
						$failed = true;
					}
				}
				if (!$response->success) {
					$ip = $this->qualifyURL($url, true)['host'];
					$errors .= $ip . ": Unknown Failure";
					$failed = true;
				}
			} catch (Requests_Exception $e) {
				$failed = true;
				$ip = $this->qualifyURL($url, true)['host'];
				$errors .= $ip . ': ' . $e->getMessage();
				$this->setLoggerChannel('AdGuard')->error($e);
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

	public function adguardHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageAdGuardEnabled'
				],
				'auth' => [
					'homepageAdGuardAuth'
				],
				'not_empty' => [
					'adguardURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderAdGuard()
	{
		if ($this->homepageItemPermissions($this->adguardHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading AdGuard...</h2></div>
					<script>
						// Pi-hole Stats
						homepageAdGuard("' . $this->config['homepageAdGuardRefresh'] . '");
						// End Pi-hole Stats
					</script>
				</div>
				';
		}
	}

	public function getAdGuardHomepageStats()
	{
		if (!$this->homepageItemPermissions($this->adguardHomepagePermissions('main'), true)) {
			return false;
		}
		$stats = array();
		$urls = explode(',', $this->config['adguardURL']);
		foreach ($urls as $url) {
			$stats_url = $url . '/control/stats?';
			$filter_url = $url . '/control/filtering/status?';
			try {
				$options = array(
					'auth' => array($this->config['adGuardUsername'], $this->decrypt($this->config['adGuardPassword']))
				);
				$response = Requests::get($stats_url, [], $options);
				if ($response->success) {
					@$adguardResults = json_decode($response->body, true);
					if (is_array($adguardResults)) {
						$ip = $this->qualifyURL($stats_url, true)['host'];
						$stats['data'][$ip] = $adguardResults;
					}
				}
			} catch (Requests_Exception $e) {
				$this->setResponse(500, $e->getMessage());
				$this->setLoggerChannel('AdGuard')->error($e);
				return false;
			};
		}
		$stats['options']['combine'] = $this->config['homepageAdGuardCombine'];
		$stats['options']['title'] = $this->config['adguardHeaderToggle'];
		$stats = isset($stats) ? $stats : null;
		$this->setAPIResponse('success', null, 200, $stats);
		return $stats;
	}
}
