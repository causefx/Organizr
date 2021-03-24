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
		$homepageSettings = array(
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageOctoprintEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageOctoprintEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageOctoprintAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageOctoprintAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'octoprintURL',
						'label' => 'URL',
						'value' => $this->config['octoprintURL'],
						'help' => 'Enter the IP:PORT of your Octoprint instance e.g. http://octopi.local'
					),
					array(
						'type' => 'input',
						'name' => 'octoprintToken',
						'label' => 'API Key',
						'value' => $this->config['octoprintToken'],
						'help' => 'Enter your Octoprint API key, found in Octoprint settings page.'
					),
				),
				'Options' => array(
					array(
						'type' => 'input',
						'name' => 'octoprintHeader',
						'label' => 'Title',
						'value' => $this->config['octoprintHeader'],
						'help' => 'Sets the title of this homepage module',
					),
					array(
						'type' => 'switch',
						'name' => 'octoprintToggle',
						'label' => 'Toggle Title',
						'value' => $this->config['octoprintHeaderToggle'],
						'help' => 'Shows/hides the title of this homepage module'
					),
				),
			)
		);
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
				$response = Requests::get($dataUrl, $headers);
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