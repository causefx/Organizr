<?php

trait HealthChecksHomepageItem
{
	public function healthChecksSettingsArray()
	{
		return array(
			'name' => 'HealthChecks',
			'enabled' => true,
			'image' => 'plugins/images/tabs/healthchecks.png',
			'category' => 'Monitor',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageHealthChecksEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageHealthChecksEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageHealthChecksAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageHealthChecksAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'healthChecksURL',
						'label' => 'URL',
						'value' => $this->config['healthChecksURL'],
						'help' => 'URL for HealthChecks API',
						'placeholder' => 'HealthChecks API URL'
					),
					array(
						'type' => 'password-alt',
						'name' => 'healthChecksToken',
						'label' => 'Token',
						'value' => $this->config['healthChecksToken']
					)
				),
				'Misc Options' => array(
					array(
						'type' => 'input',
						'name' => 'healthChecksTags',
						'label' => 'Tags',
						'value' => $this->config['healthChecksTags'],
						'help' => 'Pull only checks with this tag - Blank for all',
						'placeholder' => 'Multiple tags using CSV - tag1,tag2'
					),
					array(
						'type' => 'select',
						'name' => 'homepageHealthChecksRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageHealthChecksRefresh'],
						'options' => $this->timeOptions()
					),
				),
			)
		);
	}
	
	public function healthChecksTags($tags)
	{
		$return = '?tag=';
		if (!$tags) {
			return '';
		} elseif ($tags == '*') {
			return '';
		} else {
			if (strpos($tags, ',') !== false) {
				$list = explode(',', $tags);
				return $return . implode("&tag=", $list);
			} else {
				return $return . $tags;
			}
		}
	}
	
	public function getHealthChecks($tags = null)
	{
		if (!$this->config['homepageHealthChecksEnabled']) {
			$this->setAPIResponse('error', 'HealthChecks homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageHealthChecksAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['healthChecksURL'])) {
			$this->setAPIResponse('error', 'HealthChecks URL is not defined', 422);
			return false;
		}
		if (empty($this->config['healthChecksToken'])) {
			$this->setAPIResponse('error', 'HealthChecks Token is not defined', 422);
			return false;
		}
		$api['content']['checks'] = array();
		$tags = ($tags) ? $this->healthChecksTags($tags) : '';
		$healthChecks = explode(',', $this->config['healthChecksToken']);
		foreach ($healthChecks as $token) {
			$url = $this->qualifyURL($this->config['healthChecksURL']) . '/' . $tags;
			try {
				$headers = array('X-Api-Key' => $token);
				$options = ($this->localURL($url)) ? array('verify' => false) : array();
				$response = Requests::get($url, $headers, $options);
				if ($response->success) {
					$healthResults = json_decode($response->body, true);
					$api['content']['checks'] = array_merge($api['content']['checks'], $healthResults['checks']);
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'HealthChecks Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			};
		}
		usort($api['content']['checks'], function ($a, $b) {
			return $a['status'] <=> $b['status'];
		});
		$api['content']['checks'] = isset($api['content']['checks']) ? $api['content']['checks'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}