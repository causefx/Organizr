<?php

trait WeatherHomepageItem
{
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
		if (!$this->config['homepageWeatherAndAirEnabled']) {
			$this->setAPIResponse('error', 'Weather homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageWeatherAndAirAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['homepageWeatherAndAirLatitude']) && empty($this->config['homepageWeatherAndAirLongitude'])) {
			$this->setAPIResponse('error', 'Weather Latitude and/or Longitude were not defined', 422);
			return false;
		}
		$api['content'] = array(
			'weather' => false,
			'air' => false,
			'pollen' => false
		);
		$apiURL = $this->qualifyURL('https://api.breezometer.com/');
		$info = '&lat=' . $this->config['homepageWeatherAndAirLatitude'] . '&lon=' . $this->config['homepageWeatherAndAirLongitude'] . '&units=' . $this->config['homepageWeatherAndAirUnits'] . '&key=b7401295888443538a7ebe04719c8394';
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