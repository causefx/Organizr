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
		if ($this->config['homepageWeatherAndAirWeatherEnabled']) {
			$api['content']['weather'] = $this->getOpenMeteoWeatherData();
		}
		if ($this->config['homepageWeatherAndAirAirQualityEnabled']) {
			$api['content']['air'] = $this->getOpenMeteoAirQualityData();
		}
		if ($this->config['homepageWeatherAndAirPollenEnabled']) {
			// Open-Meteo has no US pollen source and the JS renderer ignores
			// pollen content entirely, so this intentionally stays false.
			$api['content']['pollen'] = false;
		}
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}

	private function getOpenMeteoWeatherData()
	{
		try {
			$imperial = ($this->config['homepageWeatherAndAirUnits'] === 'imperial');
			$tempUnits = $imperial ? 'F' : 'C';
			$windUnits = $imperial ? 'mph' : 'km/h';
			$apiURL = $this->qualifyURL('https://api.open-meteo.com/v1/forecast');
			$query = 'latitude=' . $this->config['homepageWeatherAndAirLatitude'] . '&longitude=' . $this->config['homepageWeatherAndAirLongitude'] . '&hourly=temperature_2m,apparent_temperature,relative_humidity_2m,dew_point_2m,weather_code,pressure_msl,wind_speed_10m,wind_direction_10m,is_day&timezone=auto&forecast_days=7';
			if ($imperial) {
				$query .= '&temperature_unit=fahrenheit&wind_speed_unit=mph';
			}
			$options = $this->requestOptions($apiURL, $this->config['homepageWeatherAndAirRefresh']);
			$response = Requests::get($apiURL . '?' . $query, [], $options);
			if (!$response->success) {
				return false;
			}
			$apiData = json_decode($response->body, true);
			if (!is_array($apiData) || !isset($apiData['hourly']['time']) || !is_array($apiData['hourly']['time'])) {
				return false;
			}
			$hourly = $apiData['hourly'];
			$utcOffsetSeconds = isset($apiData['utc_offset_seconds']) ? (int)$apiData['utc_offset_seconds'] : 0;
			$offsetString = $this->weatherAirUtcOffsetString($utcOffsetSeconds);
			$currentHour = gmdate('Y-m-d\TH:00', time() + $utcOffsetSeconds);
			$startIndex = 0;
			foreach ($hourly['time'] as $index => $timeEntry) {
				if ($timeEntry >= $currentHour) {
					$startIndex = $index;
					break;
				}
			}
			$weatherData = [];
			$total = count($hourly['time']);
			for ($i = $startIndex; $i < $total && count($weatherData) < 120; $i++) {
				list($iconCode, $weatherText) = $this->weatherAirWeatherCode($this->weatherAirHourlyValue($hourly, 'weather_code', $i));
				$pressureValue = $this->weatherAirHourlyValue($hourly, 'pressure_msl', $i);
				if ($imperial) {
					$pressure = ['value' => round($pressureValue * 0.02953, 2), 'units' => 'inHg'];
				} else {
					$pressure = ['value' => $pressureValue, 'units' => 'hPa'];
				}
				$weatherData[] = [
					'datetime' => $hourly['time'][$i] . ':00' . $offsetString,
					'icon_code' => $iconCode,
					'weather_text' => $weatherText,
					'is_day_time' => (bool)$this->weatherAirHourlyValue($hourly, 'is_day', $i),
					'temperature' => ['value' => $this->weatherAirHourlyValue($hourly, 'temperature_2m', $i), 'units' => $tempUnits],
					'feels_like_temperature' => ['value' => $this->weatherAirHourlyValue($hourly, 'apparent_temperature', $i), 'units' => $tempUnits],
					'relative_humidity' => $this->weatherAirHourlyValue($hourly, 'relative_humidity_2m', $i),
					'dew_point' => ['value' => $this->weatherAirHourlyValue($hourly, 'dew_point_2m', $i), 'units' => $tempUnits],
					'pressure' => $pressure,
					'wind' => [
						'speed' => ['value' => $this->weatherAirHourlyValue($hourly, 'wind_speed_10m', $i), 'units' => $windUnits],
						'direction' => $this->weatherAirHourlyValue($hourly, 'wind_direction_10m', $i)
					]
				];
			}
			if (empty($weatherData)) {
				return false;
			}
			return ['error' => null, 'data' => $weatherData];
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Weather & Air')->error($e);
			return false;
		}
	}

	private function getOpenMeteoAirQualityData()
	{
		try {
			$apiURL = $this->qualifyURL('https://air-quality-api.open-meteo.com/v1/air-quality');
			$query = 'latitude=' . $this->config['homepageWeatherAndAirLatitude'] . '&longitude=' . $this->config['homepageWeatherAndAirLongitude'] . '&current=us_aqi,pm2_5,pm10,carbon_monoxide,nitrogen_dioxide,ozone,sulphur_dioxide';
			$response = Requests::get($apiURL . '?' . $query);
			if (!$response->success) {
				return false;
			}
			$apiData = json_decode($response->body, true);
			if (!is_array($apiData) || !isset($apiData['current']) || !is_array($apiData['current'])) {
				return false;
			}
			$current = $apiData['current'];
			if (!isset($current['us_aqi']) || $current['us_aqi'] === null) {
				return false;
			}
			$usAqi = $this->weatherAirNumber($current['us_aqi']);
			$pollutantInfo = [
				'pm2_5' => ['field' => 'pm2_5', 'display_name' => 'PM2.5', 'full_name' => 'Fine particulate matter', 'sources' => 'Emitted by vehicles, industry, wood burning and wildfires; also formed in the atmosphere from other gases.', 'effects' => 'Penetrates deep into the lungs and bloodstream; linked to respiratory and cardiovascular harm.'],
				'pm10' => ['field' => 'pm10', 'display_name' => 'PM10', 'full_name' => 'Coarse particulate matter', 'sources' => 'Dust from roads and construction sites, pollen, mold and industrial emissions.', 'effects' => 'Irritates the eyes, nose and throat and can aggravate asthma and other lung diseases.'],
				'co' => ['field' => 'carbon_monoxide', 'display_name' => 'CO', 'full_name' => 'Carbon monoxide', 'sources' => 'Vehicle exhaust and incomplete burning of fuels in stoves, furnaces, fires and engines.', 'effects' => 'Reduces the amount of oxygen delivered to the body; high levels cause headache and dizziness and can be fatal.'],
				'no2' => ['field' => 'nitrogen_dioxide', 'display_name' => 'NO2', 'full_name' => 'Nitrogen dioxide', 'sources' => 'Emissions from vehicles, power plants and industry burning fossil fuels.', 'effects' => 'Irritates the airways and can worsen asthma and other respiratory diseases.'],
				'o3' => ['field' => 'ozone', 'display_name' => 'O3', 'full_name' => 'Ozone', 'sources' => 'Not emitted directly; formed when sunlight reacts with vehicle and industrial emissions.', 'effects' => 'Irritates the lungs, can trigger asthma attacks and reduces lung function.'],
				'so2' => ['field' => 'sulphur_dioxide', 'display_name' => 'SO2', 'full_name' => 'Sulfur dioxide', 'sources' => 'Burning of fossil fuels by power plants and industry, and volcanic emissions.', 'effects' => 'Irritates the airways; especially harmful to people with asthma.']
			];
			$pollutants = [];
			foreach ($pollutantInfo as $key => $info) {
				$pollutants[$key] = [
					'display_name' => $info['display_name'],
					'full_name' => $info['full_name'],
					'concentration' => ['value' => $this->weatherAirNumber(isset($current[$info['field']]) ? $current[$info['field']] : null), 'units' => 'µg/m³'],
					'sources_and_effects' => ['sources' => $info['sources'], 'effects' => $info['effects']]
				];
			}
			$airData = [
				'indexes' => [
					'baqi' => ['aqi' => $this->weatherAirUsAqiToBaqi($usAqi)]
				],
				'pollutants' => $pollutants,
				'health_recommendations' => $this->weatherAirHealthRecommendations($usAqi)
			];
			return ['error' => null, 'data' => $airData];
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Weather & Air')->error($e);
			return false;
		}
	}

	private function weatherAirHourlyValue($hourly, $key, $index)
	{
		return (isset($hourly[$key][$index]) && $hourly[$key][$index] !== null) ? $hourly[$key][$index] : 0;
	}

	private function weatherAirNumber($value)
	{
		return ($value === null) ? 0 : $value;
	}

	private function weatherAirUtcOffsetString($utcOffsetSeconds)
	{
		$sign = ($utcOffsetSeconds < 0) ? '-' : '+';
		$absolute = abs($utcOffsetSeconds);
		$hours = (int)floor($absolute / 3600);
		$minutes = (int)floor(($absolute % 3600) / 60);
		return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
	}

	private function weatherAirWeatherCode($weatherCode)
	{
		$map = [
			0 => [1, 'Clear sky'],
			1 => [2, 'Mainly clear'],
			2 => [3, 'Partly cloudy'],
			3 => [7, 'Overcast'],
			45 => [16, 'Fog'],
			48 => [16, 'Fog'],
			51 => [23, 'Light drizzle'],
			53 => [23, 'Drizzle'],
			55 => [23, 'Dense drizzle'],
			56 => [27, 'Freezing drizzle'],
			57 => [27, 'Freezing drizzle'],
			61 => [23, 'Light rain'],
			63 => [23, 'Rain'],
			65 => [25, 'Heavy rain'],
			66 => [27, 'Freezing rain'],
			67 => [27, 'Freezing rain'],
			71 => [24, 'Light snow'],
			73 => [24, 'Snow'],
			75 => [26, 'Heavy snow'],
			77 => [24, 'Snow grains'],
			80 => [23, 'Light showers'],
			81 => [23, 'Rain showers'],
			82 => [25, 'Violent showers'],
			85 => [24, 'Snow showers'],
			86 => [24, 'Snow showers'],
			95 => [10, 'Thunderstorm'],
			96 => [10, 'Thunderstorm with hail'],
			99 => [10, 'Thunderstorm with hail']
		];
		$weatherCode = (int)$weatherCode;
		return isset($map[$weatherCode]) ? $map[$weatherCode] : [1, 'Clear sky'];
	}

	private function weatherAirUsAqiToBaqi($usAqi)
	{
		$anchors = [
			[0, 99],
			[50, 90],
			[100, 70],
			[150, 50],
			[200, 30],
			[300, 15],
			[500, 5]
		];
		$usAqi = (float)$usAqi;
		if ($usAqi <= 0) {
			return 99;
		}
		if ($usAqi >= 500) {
			return 5;
		}
		for ($i = 0; $i < count($anchors) - 1; $i++) {
			$x0 = $anchors[$i][0];
			$y0 = $anchors[$i][1];
			$x1 = $anchors[$i + 1][0];
			$y1 = $anchors[$i + 1][1];
			if ($usAqi >= $x0 && $usAqi <= $x1) {
				$baqi = $y0 + (($usAqi - $x0) * ($y1 - $y0) / ($x1 - $x0));
				return max(1, min(100, (int)round($baqi)));
			}
		}
		return 5;
	}

	private function weatherAirHealthRecommendations($usAqi)
	{
		$usAqi = (float)$usAqi;
		if ($usAqi <= 50) {
			return [
				'general_population' => 'Air quality is good — enjoy outdoor activities as usual.',
				'elderly' => 'Air quality is good — no special precautions needed.',
				'lung_diseases' => 'Air quality is good — continue regular activities and keep usual medication on hand.',
				'heart_diseases' => 'Air quality is good — no special precautions needed.',
				'active' => 'Air quality is good — great conditions for outdoor exercise.',
				'pregnant_women' => 'Air quality is good — no special precautions needed.',
				'children' => 'Air quality is good — outdoor play is fine.'
			];
		}
		if ($usAqi <= 100) {
			return [
				'general_population' => 'Air quality is acceptable — unusually sensitive people should watch for symptoms.',
				'elderly' => 'Air quality is acceptable — reduce prolonged heavy outdoor exertion if you feel symptoms.',
				'lung_diseases' => 'Air quality is acceptable — reduce prolonged outdoor exertion if you notice symptoms.',
				'heart_diseases' => 'Air quality is acceptable — reduce prolonged heavy exertion if you notice symptoms.',
				'active' => 'Air quality is acceptable — extremely sensitive people should shorten intense outdoor workouts.',
				'pregnant_women' => 'Air quality is acceptable — no special precautions for most; take breaks if you feel unwell.',
				'children' => 'Air quality is acceptable — outdoor play is fine; watch for coughing or breathing difficulty.'
			];
		}
		if ($usAqi <= 150) {
			return [
				'general_population' => 'Sensitive groups may feel effects — the general public is less likely to be affected.',
				'elderly' => 'Reduce prolonged or heavy outdoor exertion and take more breaks.',
				'lung_diseases' => 'Reduce prolonged or heavy outdoor exertion and keep quick-relief medication handy.',
				'heart_diseases' => 'Reduce prolonged or heavy outdoor exertion; watch for symptoms such as palpitations or shortness of breath.',
				'active' => 'Shorten intense workouts or move them indoors, especially if you have asthma.',
				'pregnant_women' => 'Consider reducing prolonged outdoor exertion.',
				'children' => 'Limit prolonged outdoor exertion; move activities indoors if coughing or wheezing occurs.'
			];
		}
		if ($usAqi <= 200) {
			return [
				'general_population' => 'Everyone may feel effects — reduce prolonged or heavy outdoor exertion.',
				'elderly' => 'Avoid prolonged or heavy outdoor exertion; move activities indoors.',
				'lung_diseases' => 'Avoid outdoor exertion; stay indoors and keep medication handy.',
				'heart_diseases' => 'Avoid prolonged or heavy outdoor exertion; watch for chest symptoms.',
				'active' => 'Move workouts indoors or reduce intensity.',
				'pregnant_women' => 'Reduce outdoor time and avoid heavy exertion outdoors.',
				'children' => 'Move activities indoors and avoid prolonged outdoor exertion.'
			];
		}
		if ($usAqi <= 300) {
			return [
				'general_population' => 'Health alert — avoid prolonged or heavy outdoor exertion.',
				'elderly' => 'Stay indoors and keep activity levels low.',
				'lung_diseases' => 'Stay indoors; follow your action plan and seek care if symptoms worsen.',
				'heart_diseases' => 'Stay indoors and avoid exertion; seek care promptly for chest pain or breathlessness.',
				'active' => 'Cancel outdoor workouts and exercise indoors.',
				'pregnant_women' => 'Stay indoors as much as possible and avoid outdoor exertion.',
				'children' => 'Keep children indoors; no outdoor exertion.'
			];
		}
		return [
			'general_population' => 'Health emergency — avoid all outdoor exertion and stay indoors.',
			'elderly' => 'Stay indoors with windows closed; run an air cleaner if available.',
			'lung_diseases' => 'Stay indoors; use prescribed medication and seek medical help if symptoms are severe.',
			'heart_diseases' => 'Stay indoors and avoid all exertion; seek immediate care for chest symptoms.',
			'active' => 'No outdoor exercise; train indoors only.',
			'pregnant_women' => 'Stay indoors with windows closed and avoid any outdoor exertion.',
			'children' => 'Keep children indoors with windows closed; no outdoor activity.'
		];
	}
}
