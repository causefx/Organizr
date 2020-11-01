<?php

trait WeatherHomepageItem
{
	public function weatherSettingsArray()
	{
		return array(
			'name' => 'Weather-Air',
			'enabled' => true,
			'image' => 'plugins/images/tabs/wind.png',
			'category' => 'Monitor',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageWeatherAndAirEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageWeatherAndAirEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageWeatherAndAirAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageWeatherAndAirAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'homepageWeatherAndAirLatitude',
						'label' => 'Latitude',
						'value' => $this->config['homepageWeatherAndAirLatitude'],
						'help' => 'Please enter full latitude including minus if needed'
					),
					array(
						'type' => 'input',
						'name' => 'homepageWeatherAndAirLongitude',
						'label' => 'Longitude',
						'value' => $this->config['homepageWeatherAndAirLongitude'],
						'help' => 'Please enter full longitude including minus if needed'
					),
					array(
						'type' => 'blank',
						'label' => ''
					),
					array(
						'type' => 'button',
						'label' => '',
						'icon' => 'fa fa-search',
						'class' => 'pull-right',
						'text' => 'Need Help With Coordinates?',
						'attr' => 'onclick="showLookupCoordinatesModal()"'
					),
				),
				'Options' => array(
					array(
						'type' => 'input',
						'name' => 'homepageWeatherAndAirWeatherHeader',
						'label' => 'Title',
						'value' => $this->config['homepageWeatherAndAirWeatherHeader'],
						'help' => 'Sets the title of this homepage module',
					),
					array(
						'type' => 'switch',
						'name' => 'homepageWeatherAndAirWeatherHeaderToggle',
						'label' => 'Toggle Title',
						'value' => $this->config['homepageWeatherAndAirWeatherHeaderToggle'],
						'help' => 'Shows/hides the title of this homepage module'
					),
					array(
						'type' => 'switch',
						'name' => 'homepageWeatherAndAirWeatherEnabled',
						'label' => 'Enable Weather',
						'value' => $this->config['homepageWeatherAndAirWeatherEnabled'],
						'help' => 'Toggles the view module for Weather'
					),
					array(
						'type' => 'switch',
						'name' => 'homepageWeatherAndAirAirQualityEnabled',
						'label' => 'Enable Air Quality',
						'value' => $this->config['homepageWeatherAndAirAirQualityEnabled'],
						'help' => 'Toggles the view module for Air Quality'
					),
					array(
						'type' => 'switch',
						'name' => 'homepageWeatherAndAirPollenEnabled',
						'label' => 'Enable Pollen',
						'value' => $this->config['homepageWeatherAndAirPollenEnabled'],
						'help' => 'Toggles the view module for Pollen'
					),
					array(
						'type' => 'select',
						'name' => 'homepageWeatherAndAirUnits',
						'label' => 'Unit of Measurement',
						'value' => $this->config['homepageWeatherAndAirUnits'],
						'options' => array(
							array(
								'name' => 'Imperial',
								'value' => 'imperial'
							),
							array(
								'name' => 'Metric',
								'value' => 'metric'
							)
						)
					),
					array(
						'type' => 'select',
						'name' => 'homepageWeatherAndAirRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageWeatherAndAirRefresh'],
						'options' => $this->timeOptions()
					),
				),
			)
		);
	}
	
	public function weatherHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageWeatherAndAirEnabled'
				],
				'auth' => [
					'homepageWeatherAndAirAuth'
				],
				'not_empty' => [
					'homepageWeatherAndAirLatitude',
					'homepageWeatherAndAirLongitude'
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
	
	public function homepageOrderWeatherAndAir()
	{
		if ($this->homepageItemPermissions($this->weatherHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Weather...</h2></div>
					<script>
						// Weather And Air
						homepageWeatherAndAir("' . $this->config['homepageWeatherAndAirRefresh'] . '");
						// End Weather And Air
					</script>
				</div>
				';
		}
	}
	
	public function searchCityForCoordinates($query)
	{
		try {
			$query = $query ?? false;
			if (!$query) {
				$this->setAPIResponse('error', 'Query was not supplied', 422);
				return false;
			}
			$url = $this->qualifyURL('https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($query) . '.json?access_token=pk.eyJ1IjoiY2F1c2VmeCIsImEiOiJjazhyeGxqeXgwMWd2M2ZydWQ4YmdjdGlzIn0.R50iYuMewh1CnUZ7sFPdHA&limit=5&fuzzyMatch=true');
			$options = array('verify' => false);
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$this->setAPIResponse('success', null, 200, json_decode($response->body));
				return json_decode($response->body);
			}
		} catch (Requests_Exception $e) {
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
	}
	
	public function getWeatherAndAirData()
	{
		if (!$this->homepageItemPermissions($this->weatherHomepagePermissions('main'), true)) {
			return false;
		}
		$api['content'] = array(
			'weather' => false,
			'air' => false,
			'pollen' => false
		);
		$apiURL = $this->qualifyURL('https://api.breezometer.com/');
		$info = '&lat=' . $this->config['homepageWeatherAndAirLatitude'] . '&lon=' . $this->config['homepageWeatherAndAirLongitude'] . '&units=' . $this->config['homepageWeatherAndAirUnits'] . '&key=' . $this->config['breezometerToken'];
		try {
			$headers = array();
			$options = array();
			if ($this->config['homepageWeatherAndAirWeatherEnabled']) {
				$endpoint = '/weather/v1/forecast/hourly?hours=120&metadata=true';
				$response = Requests::get($apiURL . $endpoint . $info, $headers, $options);
				if ($response->success) {
					$apiData = json_decode($response->body, true);
					$api['content']['weather'] = ($apiData['error'] === null) ? $apiData : false;
					unset($apiData);
				}
			}
			if ($this->config['homepageWeatherAndAirAirQualityEnabled']) {
				$endpoint = '/air-quality/v2/current-conditions?features=breezometer_aqi,local_aqi,health_recommendations,sources_and_effects,dominant_pollutant_concentrations,pollutants_concentrations,pollutants_aqi_information&metadata=true';
				$response = Requests::get($apiURL . $endpoint . $info, $headers, $options);
				if ($response->success) {
					$apiData = json_decode($response->body, true);
					$api['content']['air'] = ($apiData['error'] === null) ? $apiData : false;
					unset($apiData);
				}
			}
			if ($this->config['homepageWeatherAndAirPollenEnabled']) {
				$endpoint = '/pollen/v2/forecast/daily?features=plants_information,types_information&days=1&metadata=true';
				$response = Requests::get($apiURL . $endpoint . $info, $headers, $options);
				if ($response->success) {
					$apiData = json_decode($response->body, true);
					$api['content']['pollen'] = ($apiData['error'] === null) ? $apiData : false;
					unset($apiData);
				}
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