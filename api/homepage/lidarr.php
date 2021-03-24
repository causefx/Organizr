<?php

trait LidarrHomepageItem
{
	public function lidarrSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Lidarr',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/lidarr.png',
			'category' => 'PMR',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = array(
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageLidarrEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageLidarrEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageLidarrAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageLidarrAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'lidarrURL',
						'label' => 'URL',
						'value' => $this->config['lidarrURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'lidarrToken',
						'label' => 'Token',
						'value' => $this->config['lidarrToken']
					)
				),
				'API SOCKS' => array(
					array(
						'type' => 'html',
						'override' => 12,
						'label' => '',
						'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">
										<h3 lang="en">Lidarr SOCKS API Connection</h3>
										<p>Using this feature allows you to access the Lidarr API without having to reverse proxy it.  Just access it from: </p>
										<code>' . $this->getServerPath() . 'api/v2/socks/lidarr/</code>
									</div>
								</div>
							</div>'
					),
					array(
						'type' => 'switch',
						'name' => 'lidarrSocksEnabled',
						'label' => 'Enable',
						'value' => $this->config['lidarrSocksEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'lidarrSocksAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['lidarrSocksAuth'],
						'options' => $this->groupOptions
					),
				),
				'Misc Options' => array(
					array(
						'type' => 'number',
						'name' => 'calendarStart',
						'label' => '# of Days Before',
						'value' => $this->config['calendarStart'],
						'placeholder' => ''
					),
					array(
						'type' => 'number',
						'name' => 'calendarEnd',
						'label' => '# of Days After',
						'value' => $this->config['calendarEnd'],
						'placeholder' => ''
					),
					array(
						'type' => 'select',
						'name' => 'calendarFirstDay',
						'label' => 'Start Day',
						'value' => $this->config['calendarFirstDay'],
						'options' => $this->daysOptions()
					),
					array(
						'type' => 'select',
						'name' => 'calendarDefault',
						'label' => 'Default View',
						'value' => $this->config['calendarDefault'],
						'options' => $this->calendarDefaultOptions()
					),
					array(
						'type' => 'select',
						'name' => 'calendarTimeFormat',
						'label' => 'Time Format',
						'value' => $this->config['calendarTimeFormat'],
						'options' => $this->timeFormatOptions()
					),
					array(
						'type' => 'select',
						'name' => 'calendarLocale',
						'label' => 'Locale',
						'value' => $this->config['calendarLocale'],
						'options' => $this->calendarLocaleOptions()
					),
					array(
						'type' => 'select',
						'name' => 'calendarLimit',
						'label' => 'Items Per Day',
						'value' => $this->config['calendarLimit'],
						'options' => $this->limitOptions()
					),
					array(
						'type' => 'select',
						'name' => 'calendarRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['calendarRefresh'],
						'options' => $this->timeOptions()
					),
				),
				'Test Connection' => array(
					array(
						'type' => 'blank',
						'label' => 'Please Save before Testing'
					),
					array(
						'type' => 'button',
						'label' => '',
						'icon' => 'fa fa-flask',
						'class' => 'pull-right',
						'text' => 'Test Connection',
						'attr' => 'onclick="testAPIConnection(\'lidarr\')"'
					),
				)
			)
		);
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionLidarr()
	{
		if (empty($this->config['lidarrURL'])) {
			$this->setAPIResponse('error', 'Lidarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['lidarrToken'])) {
			$this->setAPIResponse('error', 'Lidarr Token is not defined', 422);
			return false;
		}
		$failed = false;
		$errors = '';
		$list = $this->csvHomepageUrlToken($this->config['lidarrURL'], $this->config['lidarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'lidarr');
				$results = $downloader->getRootFolder();
				$downloadList = json_decode($results, true);
				if (is_array($downloadList) || is_object($downloadList)) {
					$queue = (array_key_exists('error', $downloadList)) ? $downloadList['error']['msg'] : $downloadList;
					if (!is_array($queue)) {
						$ip = $value['url'];
						$errors .= $ip . ': ' . $queue;
						$failed = true;
					}
				} else {
					$ip = $value['url'];
					$errors .= $ip . ': Response was not JSON';
					$failed = true;
				}
				
			} catch (Exception $e) {
				$failed = true;
				$ip = $value['url'];
				$errors .= $ip . ': ' . $e->getMessage();
				$this->writeLog('error', 'Lidarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
		}
		if ($failed) {
			$this->setAPIResponse('error', $errors, 500);
			return false;
		} else {
			$this->setAPIResponse('success', null, 200);
			return true;
		}
	}
	
	public function lidarrHomepagePermissions($key = null)
	{
		$permissions = [
			'calendar' => [
				'enabled' => [
					'homepageLidarrEnabled'
				],
				'auth' => [
					'homepageLidarrAuth'
				],
				'not_empty' => [
					'lidarrURL',
					'lidarrToken'
				]
			],
			'queue' => [
				'enabled' => [
					'homepageLidarrEnabled',
					'homepageLidarrQueueEnabled'
				],
				'auth' => [
					'homepageLidarrAuth',
					'homepageLidarrQueueAuth'
				],
				'not_empty' => [
					'lidarrURL',
					'lidarrToken'
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
	
	public function getLidarrQueue()
	{
		if (!$this->homepageItemPermissions($this->lidarrHomepagePermissions('queue'), true)) {
			return false;
		}
		$queueItems = array();
		$list = $this->csvHomepageUrlToken($this->config['lidarrURL'], $this->config['lidarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'lidarr');
				$results = $downloader->getQueue();
				$downloadList = json_decode($results, true);
				if (is_array($downloadList) || is_object($downloadList)) {
					$queue = (array_key_exists('error', $downloadList)) ? '' : $downloadList;
				} else {
					$queue = '';
				}
				if (!empty($queue)) {
					$queueItems = array_merge($queueItems, $queue);
				}
			} catch (Exception $e) {
				$this->writeLog('error', 'Lidarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
		}
		$api['content']['queueItems'] = $queueItems;
		$api['content']['historyItems'] = false;
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;;
	}
	
	public function getLidarrCalendar($startDate = null, $endDate = null)
	{
		$startDate = ($startDate) ?? $_GET['start'];
		$endDate = ($endDate) ?? $_GET['end'];
		if (!$this->homepageItemPermissions($this->lidarrHomepagePermissions('calendar'), true)) {
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['lidarrURL'], $this->config['lidarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'lidarr');
				$results = $downloader->getCalendar($startDate, $endDate);
				$result = json_decode($results, true);
				if (is_array($result) || is_object($result)) {
					$calendar = (array_key_exists('error', $result)) ? '' : $this->formatLidarrCalendar($results, $key);
				} else {
					$calendar = '';
				}
			} catch (Exception $e) {
				$this->writeLog('error', 'Lidarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
			if (!empty($calendar)) {
				$calendarItems = array_merge($calendarItems, $calendar);
			}
		}
		$this->setAPIResponse('success', null, 200, $calendarItems);
		return $calendarItems;
	}
	
	public function formatLidarrCalendar($array, $number)
	{
		$array = json_decode($array, true);
		$gotCalendar = array();
		$i = 0;
		foreach ($array as $child) {
			$i++;
			$albumName = $child['title'];
			$artistName = $child['artist']['artistName'];
			$albumID = '';
			$releaseDate = $child['releaseDate'];
			$releaseDate = strtotime($releaseDate);
			$releaseDate = date("Y-m-d H:i:s", $releaseDate);
			if (new DateTime() < new DateTime($releaseDate)) {
				$unaired = true;
			}
			if (isset($child['statistics']['percentOfTracks'])) {
				if ($child['statistics']['percentOfTracks'] == '100.0') {
					$downloaded = '1';
				} else {
					$downloaded = '0';
				}
			} else {
				$downloaded = '0';
			}
			if ($downloaded == "0" && isset($unaired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$fanart = "/plugins/images/cache/no-np.png";
			foreach ($child['artist']['images'] as $image) {
				if ($image['coverType'] == "fanart") {
					$fanart = str_replace('http://', 'https://', $image['url']);
				}
			}
			$details = array(
				"seasonCount" => '',
				"status" => '',
				"topTitle" => $albumName,
				"bottomTitle" => $artistName,
				"overview" => isset($child['artist']['overview']) ? $child['artist']['overview'] : '',
				"runtime" => '',
				"image" => $fanart,
				"ratings" => $child['artist']['ratings']['value'],
				"videoQuality" => "unknown",
				"audioChannels" => "unknown",
				"audioCodec" => "unknown",
				"videoCodec" => "unknown",
				"size" => "unknown",
				"genres" => $child['genres'],
			);
			array_push($gotCalendar, array(
				"id" => "Lidarr-" . $number . "-" . $i,
				"title" => $artistName,
				"start" => $child['releaseDate'],
				"className" => "inline-popups bg-calendar calendar-item musicID--",
				"imagetype" => "music " . $downloaded,
				"imagetypeFilter" => "music",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details,
				"data" => $child
			));
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return false;
	}
	
}