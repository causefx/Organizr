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
	
	public function healthChecksHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageHealthChecksEnabled'
				],
				'auth' => [
					'homepageHealthChecksAuth'
				],
				'not_empty' => [
					'healthChecksURL',
					'healthChecksToken'
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
	
	public function homepageOrderhealthchecks()
	{
		if ($this->homepageItemPermissions($this->healthChecksHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Health Checks...</h2></div>
					<script>
						// Health Checks
						homepageHealthChecks("' . $this->config['healthChecksTags'] . '","' . $this->config['homepageHealthChecksRefresh'] . '");
						// End Health Checks
					</script>
				</div>
				';
		}
	}
	
	public function getHealthChecks($tags = null)
	{
		if (!$this->homepageItemPermissions($this->healthChecksHomepagePermissions('main'), true)) {
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
}