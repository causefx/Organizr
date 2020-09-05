<?php

trait SpeedTestHomepageItem
{
	public function speedTestSettingsArray()
	{
		return array(
			'name' => 'Speedtest',
			'enabled' => true,
			'image' => 'plugins/images/tabs/speedtest-icon.png',
			'category' => 'Monitor',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'html',
						'override' => 6,
						'label' => 'Info',
						'html' => '<p>This homepage item requires <a href="https://github.com/henrywhitaker3/Speedtest-Tracker" target="_blank" rel="noreferrer noopener">Speedtest-Tracker <i class="fa fa-external-link" aria-hidden="true"></i></a> to be running on your network.</p>'
					),
					array(
						'type' => 'switch',
						'name' => 'homepageSpeedtestEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageSpeedtestEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageSpeedtestAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageSpeedtestAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'speedtestURL',
						'label' => 'URL',
						'value' => $this->config['speedtestURL'],
						'help' => 'Enter the IP:PORT of your speedtest instance e.g. http(s)://<ip>:<port>'
					),
				),
				'Options' => array(
					array(
						'type' => 'input',
						'name' => 'speedtestHeader',
						'label' => 'Title',
						'value' => $this->config['speedtestHeader'],
						'help' => 'Sets the title of this homepage module',
					),
					array(
						'type' => 'switch',
						'name' => 'speedtestHeaderToggle',
						'label' => 'Toggle Title',
						'value' => $this->config['speedtestHeaderToggle'],
						'help' => 'Shows/hides the title of this homepage module'
					),
				),
			)
		);
	}
	
	public function getSpeedtestHomepageData()
	{
		if (!$this->config['homepageSpeedtestEnabled']) {
			$this->setAPIResponse('error', 'SpeedTest homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSpeedtestAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['speedtestURL'])) {
			$this->setAPIResponse('error', 'SpeedTest URL is not defined', 422);
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['speedtestURL']);
		$dataUrl = $url . '/api/speedtest/latest';
		try {
			$response = Requests::get($dataUrl);
			if ($response->success) {
				$json = json_decode($response->body, true);
				$api['data'] = [
					'current' => $json['data'],
					'average' => $json['average'],
					'max' => $json['max'],
				];
				$api['options'] = [
					'title' => $this->config['speedtestHeader'],
					'titleToggle' => $this->config['speedtestHeaderToggle'],
				];
			} else {
				$this->setAPIResponse('error', 'SpeedTest connection error', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Speedtest Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}