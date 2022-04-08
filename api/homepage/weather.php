<?php

trait WeatherHomepageItem
{
	public function weatherSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Weather-Air',
			'enabled' => true,
			'image' => 'plugins/images/tabs/wind.png',
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
					$this->settingsOption('enable', 'homepageWeatherAndAirEnabled'),
					$this->settingsOption('auth', 'homepageWeatherAndAirAuth'),
				],
				'Connection' => [
					$this->settingsOption('input', 'homepageWeatherAndAirLatitude', ['label' => 'Latitude', 'help' => 'Please enter full latitude including minus if needed']),
					$this->settingsOption('input', 'homepageWeatherAndAirLongitude', ['label' => 'Longitude', 'help' => 'Please enter full longitude including minus if needed']),
					$this->settingsOption('blank'),
					$this->settingsOption('button', null, ['type' => 'button', 'label' => '', 'icon' => 'fa fa-search', 'class' => 'pull-right', 'text' => 'Need Help With Coordinates?', 'attr' => 'onclick="showLookupCoordinatesModal()"']),
				],
				'Options' => [
					$this->settingsOption('title', 'homepageWeatherAndAirWeatherHeader'),
					$this->settingsOption('toggle-title', 'homepageWeatherAndAirWeatherHeaderToggle'),
					$this->settingsOption('enable', 'homepageWeatherAndAirWeatherEnabled', ['label' => 'Enable Weather', 'help' => 'Toggles the view module for Weather']),
					$this->settingsOption('enable', 'homepageWeatherAndAirAirQualityEnabled', ['label' => 'Enable Air Quality', 'help' => 'Toggles the view module for Air Quality']),
					$this->settingsOption('enable', 'homepageWeatherAndAirPollenEnabled', ['label' => 'Enable Pollen', 'help' => 'Toggles the view module for Pollen']),
					$this->settingsOption('select', 'homepageWeatherAndAirUnits', ['label' => 'Unit of Measurement', 'options' => [['name' => 'Imperial', 'value' => 'imperial'], ['name' => 'Metric', 'value' => 'metric']]]),
					$this->settingsOption('refresh', 'homepageWeatherAndAirRefresh'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
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
		return $this->homepageCheckKeyPermissions($key, $permissions);
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
			$response = Requests::get($url);
			if ($response->success) {
				$this->setAPIResponse('success', null, 200, json_decode($response->body));
				return json_decode($response->body);
			}
		} catch (Requests_Exception $e) {
			$this->setResponse(500, $e->getMessage());
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
			if ($this->config['homepageWeatherAndAirWeatherEnabled']) {
				$endpoint = '/weather/v1/forecast/hourly?hours=120&metadata=true';
				$response = Requests::get($apiURL . $endpoint . $info);
				if ($response->success) {
					$apiData = json_decode($response->body, true);
					$api['content']['weather'] = ($apiData['error'] === null) ? $apiData : false;
					unset($apiData);
				}
			}
			if ($this->config['homepageWeatherAndAirAirQualityEnabled']) {
				$endpoint = '/air-quality/v2/current-conditions?features=breezometer_aqi,local_aqi,health_recommendations,sources_and_effects,dominant_pollutant_concentrations,pollutants_concentrations,pollutants_aqi_information&metadata=true';
				$response = Requests::get($apiURL . $endpoint . $info);
				if ($response->success) {
					$apiData = json_decode($response->body, true);
					$api['content']['air'] = ($apiData['error'] === null) ? $apiData : false;
					unset($apiData);
				}
			}
			if ($this->config['homepageWeatherAndAirPollenEnabled']) {
				$endpoint = '/pollen/v2/forecast/daily?features=plants_information,types_information&days=1&metadata=true';
				$response = Requests::get($apiURL . $endpoint . $info);
				if ($response->success) {
					$apiData = json_decode($response->body, true);
					$api['content']['pollen'] = ($apiData['error'] === null) ? $apiData : false;
					unset($apiData);
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Weather And Air Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setResponse(500, $e->getMessage());
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}