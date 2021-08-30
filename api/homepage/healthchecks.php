<?php

trait HealthChecksHomepageItem
{
	public function healthChecksSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'HealthChecks',
			'enabled' => true,
			'image' => 'plugins/images/tabs/healthchecks.png',
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
					$this->settingsOption('enable', 'homepageHealthChecksEnabled'),
					$this->settingsOption('auth', 'homepageHealthChecksAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'healthChecksURL'),
					$this->settingsOption('multiple-token', 'healthChecksToken'),
					$this->settingsOption('disable-cert-check', 'healthChecksDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'healthChecksUseCustomCertificate'),
				],
				'Misc Options' => [
					$this->settingsOption('multiple', 'healthChecksTags', ['label' => 'Tags', 'help' => 'Pull only checks with this tag - Blank for all', 'placeholder' => 'Multiple tags using CSV - tag1,tag2']),
					$this->settingsOption('refresh', 'homepageHealthChecksRefresh'),
					$this->settingsOption('switch', 'homepageHealthChecksShowDesc', ['label' => 'Show Description']),
					$this->settingsOption('switch', 'homepageHealthChecksShowTags', ['label' => 'Show Tags']),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
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
		return $this->homepageCheckKeyPermissions($key, $permissions);
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
				$options = $this->requestOptions($url, $this->config['homepageHealthChecksRefresh'], $this->config['healthChecksDisableCertCheck'], $this->config['healthChecksUseCustomCertificate']);
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
		$api['options'] = [
			'desc' => $this->config['homepageHealthChecksShowDesc'],
			'tags' => $this->config['homepageHealthChecksShowTags'],
		];
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