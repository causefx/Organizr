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
	
	public function searchJackettIndexers($query = null)
	{
		if (!$this->config['homepageJackettEnabled']) {
			$this->setAPIResponse('error', 'Jackett homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJackettAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['jackettURL']) || empty($this->config['jackettToken'])) {
			$this->setAPIResponse('error', 'Jackett URL and/or Token were not defined', 422);
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
			$options = array('timeout' => 60);
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