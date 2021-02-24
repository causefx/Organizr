<?php

trait JackettHomepageItem
{
	public function jackettSettingsArray()
	{
		return array(
			'name' => 'Jackett',
			'enabled' => true,
			'image' => 'plugins/images/tabs/jackett.png',
			'category' => 'Utility',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageJackettEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageJackettEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageJackettAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageJackettAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'jackettURL',
						'label' => 'URL',
						'value' => $this->config['jackettURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'jackettToken',
						'label' => 'Token',
						'value' => $this->config['jackettToken']
					)
				),
				'Options' => array(),
			)
		);
	}
	
	public function jackettHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageJackettEnabled'
				],
				'auth' => [
					'homepageJackettAuth'
				],
				'not_empty' => [
					'jackettURL',
					'jackettToken'
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
	
	public function homepageOrderJackett()
	{
		if ($this->homepageItemPermissions($this->jackettHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Jackett...</h2></div>
					<script>
						// Jackett
						homepageJackett();
						// End Jackett
					</script>
				</div>
				';
		}
	}
	
	public function searchJackettIndexers($query = null)
	{
		if (!$this->homepageItemPermissions($this->jackettHomepagePermissions('main'), true)) {
			return false;
		}
		if (!$query) {
			$this->setAPIResponse('error', 'Query was not supplied', 422);
			return false;
		}
		$apiURL = $this->qualifyURL($this->config['jackettURL']);
		$endpoint = $apiURL . '/api/v2.0/indexers/all/results?apikey=' . $this->config['jackettToken'] . '&Query=' . urlencode($query);
		try {
			$headers = array();
			$options = array('timeout' => 120);
			$response = Requests::get($endpoint, $headers, $options);
			if ($response->success) {
				$apiData = json_decode($response->body, true);
				$api['content'] = $apiData;
				unset($apiData);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Weather And Air Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}