<?php

trait OctoPrintHomepageItem
{
	public function octoprintSettingsArray()
	{
		return array(
			'name' => 'Octoprint',
			'enabled' => true,
			'image' => 'plugins/images/tabs/octoprint.png',
			'category' => 'Monitor',
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
	}
	
	public function getOctoprintHomepageData()
	{
		if (!$this->config['homepageOctoprintEnabled']) {
			$this->setAPIResponse('error', 'OctoPrint homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageOctoprintAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['octoprintURL'])) {
			$this->setAPIResponse('error', 'OctoPrint URL is not defined', 422);
			return false;
		}
		if (empty($this->config['octoprintToken'])) {
			$this->setAPIResponse('error', 'OctoPrint Token is not defined', 422);
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