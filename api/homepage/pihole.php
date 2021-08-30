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
					$this->settingsOption('url', 'piholeURL', ['help' => 'Please make sure to use local IP address and port and to include \'/admin/\' at the end of the URL. You can add multiple Pi-holes by comma separating the URLs.', 'placeholder' => 'http(s)://hostname:port/admin/']),
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
		$api = array();
		$failed = false;
		$errors = '';
		$urls = explode(',', $this->config['piholeURL']);
		foreach ($urls as $url) {
			$url = $url . '/api.php?';
			try {
				$response = Requests::get($url, [], []);
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
					$errors .= $ip . ': Unknown Failure';
					$failed = true;
				}
			} catch (Requests_Exception $e) {
				$failed = true;
				$ip = $this->qualifyURL($url, true)['host'];
				$errors .= $ip . ': ' . $e->getMessage();
				$this->writeLog('error', 'Pi-hole Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
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
		$api = array();
		$urls = explode(',', $this->config['piholeURL']);
		foreach ($urls as $url) {
			$url = $url . '/api.php?';
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
				$this->setAPIResponse('error', $e->getMessage(), 500);
				$this->writeLog('error', 'Pi-hole Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				return false;
			};
		}
		$api['options']['combine'] = $this->config['homepagePiholeCombine'];
		$api['options']['title'] = $this->config['piholeHeaderToggle'];
		$api = isset($api) ? $api : null;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}