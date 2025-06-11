<?php

trait PiHoleHomepageItem
{
	public function piholeSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Pi-hole',
			'enabled' => true,
			'image' => 'plugins/images/tabs/pihole.png',
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
					$this->settingsOption('enable', 'homepagePiholeEnabled'),
					$this->settingsOption('auth', 'homepagePiholeAuth'),
				],
				'Connection' => [
					$this->settingsOption('multiple-url', 'piholeURL', ['help' => 'Please make sure to use local IP address and port at the end of the URL. You can add multiple Pi-holes by comma separating the URLs.', 'placeholder' => 'http(s)://hostname:port/']),
					$this->settingsOption('multiple-token', 'piholeToken'),
				],
				'Misc' => [
					$this->settingsOption('toggle-title', 'piholeHeaderToggle'),
					$this->settingsOption('switch', 'homepagePiholeCombine', ['label' => 'Combine stat cards', 'help' => 'This controls whether to combine the stats for multiple pihole instances into 1 card.']),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'pihole'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function testConnectionPihole()
	{
		if (empty($this->config['piholeURL'])) {
			$this->setAPIResponse('error', 'Pihole URL is not defined', 422);
			return false;
		}
		$failed = false;
		$errors = '';
		$list = $this->csvHomepageUrlToken($this->config['piholeURL'], $this->config['piholeToken']);
		foreach ($list as $key => $value) {
			$response = $this->getAuth($value['url'], $value['token']);
			$errors = $response["errors"];
		}
		if ($errors != '') {
			$this->setAPIResponse('error', $errors, 500);
			return false;
		} else {
			$this->setAPIResponse('success', null, 200);
			return true;
		}
	}

	public function piholeHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepagePiholeEnabled'
				],
				'auth' => [
					'homepagePiholeAuth'
				],
				'not_empty' => [
					'piholeURL'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderPihole()
	{
		if ($this->homepageItemPermissions($this->piholeHomepagePermissions('main'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Pihole...</h2></div>
					<script>
						// Pi-hole Stats
						homepagePihole("' . $this->config['homepagePiholeRefresh'] . '");
						// End Pi-hole Stats
					</script>
				</div>
				';
		}
	}

	public function getAuth($base_url, $token)
	{
		$sid = null;
		if ($token === '' || $token === null) {
			$errors .= $base_url . ': Missing API Token';
			$this->setAPIResponse('error', $errors, 500);
			return $errors;
		}
		try {
			$sid = $this->doRequest($base_url, "createAuth", [], ['password' => $token]);
			$this->cleanSessions($base_url, $sid);
		} catch (Requests_Exception $e) {
			$errors .= $ip . ': ' . $e->getMessage();
			$this->setLoggerChannel('PiHole')->error($e);
		};

		return ["errors" => $errors, "sid" => $sid];
	}

	public function cleanSessions($base_url, $sid)
	{
		$sessions = $this->doRequest($base_url, "getAuths", ["sid" => $sid]);
		foreach ($sessions as $session) {
			//  Skip if not right user agent, skip if current session
			if ($session['user_agent'] != 'Organizr' || $session['current_session'] == '1') {
				continue;
			}
			$this->doRequest($base_url,"deleteAuth", ["sid" => $sid],  $session['id']);
		}
	}

	public function endpoints($endpoint)
	{
		return [
			"createAuth" => [
				"type" => "post",
				"urlHandler" => function() {
					return "auth";
				},
				"responseHandler" => function($payload) {
					return $payload['session']['sid'];
				},
				"payloadHandler" => function($data) {
					return json_encode($data);
				}
			],
			"getAuths" => [
				"type" => "get",
				"urlHandler" => function() {
					return "auth/sessions";
				},
				"responseHandler" => function($payload) {
					return $payload['sessions'];
				}
			],
			"deleteAuth" => [
				"type" => "delete",
				"urlHandler" => function($id) {
					return "auth/session/$id";
				},
			],
			"get24HourStatsSummary" => [
				"type" => "get",
				"urlHandler" => function() {
					$nowUnixTimestamp = time();
					$oneDayAgoUnixTimestamp = $nowUnixTimestamp - (24 * 60 * 60);
					return "stats/database/summary?from=$oneDayAgoUnixTimestamp&until=$nowUnixTimestamp";
				}
			],
			"get24HourBlockedDomains" => [
				"type" => "get",
				"urlHandler" => function() {
					$nowUnixTimestamp = time();
					$oneDayAgoUnixTimestamp = $nowUnixTimestamp - (24 * 60 * 60);
					return "stats/database/top_domains?from=$oneDayAgoUnixTimestamp&until=$nowUnixTimestamp&blocked=true&count=1000";
				},
				"responseHandler" => function($payload) {
					return array_map(
						function($item) {
							return $item['domain'];
						}, $payload['domains']
					);
					
				}
			],
		][$endpoint];
	}

	public function doRequest($baseUrl, $endpoint, $headers = [], $data = null)
	{
		$endpointDictionary = $this->endpoints($endpoint);
		$urlHandler = $endpointDictionary['urlHandler'];
		$payloadHandler = $endpointDictionary['payloadHandler'] ?? function() {
			return null;
		};
		$requestType = $endpointDictionary['type'];
		$responseHandler = $endpointDictionary['responseHandler'] ?? function($payload) {
			return $payload;
		};
		$url = $this->qualifyURL("$baseUrl/api/{$urlHandler($data)}");
		$headers = $headers + ["User-Agent" => 'Organizr'];
		try {
			$response = Requests::$requestType($url, $headers, $payloadHandler($data));
			
			if ($response->success) {
				$processedResponse = $responseHandler($this->testAndFormatString($response->body)["data"]);
			}
		} catch (Requests_Exception $e) {
				$this->setResponse(500, $e->getMessage());
				$this->setLoggerChannel('PiHole')->error($e);
			};
		return $processedResponse ?? [];
	}

	public function getPiholeHomepageStats()
	{
		if (!$this->homepageItemPermissions($this->piholeHomepagePermissions('main'), true)) {
			return false;
		}
		$api = [];
		$list = $this->csvHomepageUrlToken($this->config['piholeURL'], $this->config['piholeToken']);
		foreach ($list as $key => $value) {
			$base_url = $value['url'];
			$sid = $this->getAuth($base_url, $value['token'])["sid"];
			$stats = $this->doRequest($base_url, "get24HourStatsSummary", ["sid" => $sid]);
			$stats["domains_being_blocked"] = $this->doRequest($base_url, "get24HourBlockedDomains", ["sid" => $sid]);
			$api['data'][$base_url] = $stats;
		}
		$api['options']['combine'] = $this->config['homepagePiholeCombine'];
		$api['options']['title'] = $this->config['piholeHeaderToggle'];
		$api = $api ?? null;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}