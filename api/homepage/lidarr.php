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
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageLidarrEnabled'),
					$this->settingsOption('auth', 'homepageLidarrAuth'),
				],
				'Connection' => [
					$this->settingsOption('multiple-url', 'lidarrURL'),
					$this->settingsOption('multiple-token', 'lidarrToken'),
					$this->settingsOption('disable-cert-check', 'lidarrDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'lidarrUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'lidarr'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'lidarrSocksEnabled'),
					$this->settingsOption('auth', 'lidarrSocksAuth'),
				],
				'Calendar' => [
					$this->settingsOption('calendar-start', 'calendarStart'),
					$this->settingsOption('calendar-end', 'calendarEnd'),
					$this->settingsOption('calendar-starting-day', 'calendarFirstDay'),
					$this->settingsOption('calendar-default-view', 'calendarDefault'),
					$this->settingsOption('calendar-time-format', 'calendarTimeFormat'),
					$this->settingsOption('calendar-locale', 'calendarLocale'),
					$this->settingsOption('calendar-limit', 'calendarLimit'),
					$this->settingsOption('refresh', 'calendarRefresh'),
					$this->settingsOption('calendar-link-url', 'lidarrCalendarLink'),
					$this->settingsOption('calendar-frame-target', 'lidarrFrameTarget'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'lidarr'),
				]
			]
		];
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
				$options = $this->requestOptions($value['url'], null, $this->config['lidarrDisableCertCheck'], $this->config['lidarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'lidarr', null . null, $options);
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
		return $this->homepageCheckKeyPermissions($key, $permissions);
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
				$options = $this->requestOptions($value['url'], null, $this->config['lidarrDisableCertCheck'], $this->config['lidarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'lidarr', null, null, $options);
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
		$startDate = ($startDate) ?? $_GET['start'] ?? date('Y-m-d', strtotime('-' . $this->config['calendarStart'] . ' days'));
		$endDate = ($endDate) ?? $_GET['end'] ?? date('Y-m-d', strtotime('+' . $this->config['calendarEnd'] . ' days'));
		if (!$this->homepageItemPermissions($this->lidarrHomepagePermissions('calendar'), true)) {
			return false;
		}
		if ($this->demo) {
			return $this->demoData('lidarr/calendar.json');
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['lidarrURL'], $this->config['lidarrToken']);
		foreach ($list as $key => $value) {
			try {
				$options = $this->requestOptions($value['url'], null, $this->config['lidarrDisableCertCheck'], $this->config['lidarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'lidarr', null, null, $options);
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
			$href = $this->config['lidarrCalendarLink'] ?? '';
			if (empty($href) && !empty($this->config['lidarrURL'])){
				$href_arr = explode(',',$this->config['lidarrURL']);
				$href = reset($href_arr);
			}
			if (!empty($href)){
				$href = $href . '/artist/' . $child['artist']['foreignArtistId'];
				$href = str_replace("//artist/","/artist/",$href);
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
				"href" => strtolower($href),
				"icon" => "/plugins/images/tabs/lidarr.png",
				"frame" => $this->config['lidarrFrameTarget'] ?? ''
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
