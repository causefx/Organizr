<?php

trait MonitorrHomepageItem
{
	public function monitorrSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Monitorr',
			'enabled' => true,
			'image' => 'plugins/images/tabs/monitorr.png',
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
					$this->settingsOption('enable', 'homepageMonitorrEnabled'),
					$this->settingsOption('auth', 'homepageMonitorrAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'monitorrURL', ['help' => 'URL for Monitorr. Please use the reverse proxy URL i.e. https://domain.com/monitorr/.', 'placeholder' => 'http://domain.com/monitorr/']),
					$this->settingsOption('blank'),
					$this->settingsOption('disable-cert-check', 'monitorrDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'monitorrUseCustomCertificate'),
				],
				'Options' => [
					$this->settingsOption('refresh', 'homepageMonitorrRefresh'),
					$this->settingsOption('switch', 'monitorrCompact', ['label' => 'Compact view', 'help' => 'Toggles the compact view of this homepage module']),
					$this->settingsOption('title', 'monitorrHeader'),
					$this->settingsOption('toggle-title', 'monitorrHeaderToggle'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function monitorrHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageMonitorrEnabled'
				],
				'auth' => [
					'homepageMonitorrAuth'
				],
				'not_empty' => [
					'monitorrURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function homepageOrderMonitorr()
	{
		if ($this->homepageItemPermissions($this->monitorrHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Monitorr...</h2></div>
					<script>
						// Monitorr
						homepageMonitorr("' . $this->config['homepageMonitorrRefresh'] . '");
						// End Monitorr
					</script>
				</div>
				';
		}
	}
	
	public function getMonitorrHomepageData()
	{
		if (!$this->homepageItemPermissions($this->monitorrHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['monitorrURL']);
		$dataUrl = $url . '/assets/php/loop.php';
		try {
			$options = $this->requestOptions($url, $this->config['homepageMonitorrRefresh'], $this->config['monitorrDisableCertCheck'], $this->config['monitorrUseCustomCertificate']);
			$response = Requests::get($dataUrl, ['Token' => $this->config['organizrAPI']], $options);
			if ($response->success) {
				$html = html_entity_decode($response->body);
				// This section grabs the names of all services by regex
				$services = [];
				$servicesMatch = [];
				$servicePattern = '/<div id="servicetitle(?:offline|nolink)?".*><div>(.*)<\/div><\/div><div class="(?:btnonline|btnoffline|btnunknown)".*>(Online|Offline|Unresponsive)<\/div>(:?<\/a>)?<\/div><\/div>/';
				preg_match_all($servicePattern, $html, $servicesMatch);
				$services = array_filter($servicesMatch[1]);
				$status = array_filter($servicesMatch[2]);
				$statuses = [];
				foreach ($services as $key => $service) {
					$match = $status[$key];
					$statuses[$service] = $match;
					if ($match == 'Online') {
						$statuses[$service] = [
							'status' => true
						];
					} else if ($match == 'Offline') {
						$statuses[$service] = [
							'status' => false
						];
					} else if ($match == 'Unresponsive') {
						$statuses[$service] = [
							'status' => 'unresponsive'
						];
					}
					$statuses[$service]['sort'] = $key;
					$imageMatch = [];
					$imgPattern = '/assets\/img\/\.\.(.*)" class="serviceimg" alt=.*><\/div><\/div><div id="servicetitle"><div>' . $service . '|assets\/img\/\.\.(.*)" class="serviceimg imgoffline" alt=.*><\/div><\/div><div id="servicetitleoffline".*><div>' . $service . '|assets\/img\/\.\.(.*)" class="serviceimg" alt=.*><\/div><\/div><div id="servicetitlenolink".*><div>' . $service . '/';
					preg_match($imgPattern, $html, $imageMatch);
					unset($imageMatch[0]);
					$imageMatch = array_values($imageMatch);
					// array_push($api['imagematches'][$service], $imageMatch);
					foreach ($imageMatch as $match) {
						if ($match !== '') {
							$image = $match;
						}
					}
					$ext = explode('.', $image);
					$ext = $ext[key(array_slice($ext, -1, 1, true))];
					$imageUrl = $url . '/assets' . $image;
					$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
					$img = Requests::get($imageUrl, ['Token' => $this->config['organizrAPI']], $options);
					if ($img->success) {
						$base64 = 'data:image/' . $ext . ';base64,' . base64_encode($img->body);
						$statuses[$service]['image'] = $base64;
					} else {
						$statuses[$service]['image'] = 'plugins/images/cache/no-list.png';
					}
					$linkMatch = [];
					$linkPattern = '/<a class="servicetile" href="(.*)" target="_blank" style="display: block"><div id="serviceimg"><div><img id="' . strtolower($service) . '-service-img/';
					preg_match($linkPattern, $html, $linkMatch);
					$linkMatch = array_values($linkMatch);
					unset($linkMatch[0]);
					foreach ($linkMatch as $link) {
						if ($link !== '') {
							$statuses[$service]['link'] = $link;
						}
					}
				}
				foreach ($statuses as $status) {
					foreach ($status as $key => $value) {
						if (!isset($sortArray[$key])) {
							$sortArray[$key] = array();
						}
						$sortArray[$key][] = $value;
					}
				}
				array_multisort($sortArray['status'], SORT_ASC, $sortArray['sort'], SORT_ASC, $statuses);
				$api['services'] = $statuses;
				$api['options'] = [
					'title' => $this->config['monitorrHeader'],
					'titleToggle' => $this->config['monitorrHeaderToggle'],
					'compact' => $this->config['monitorrCompact'],
				];
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Monitorr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 401);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}