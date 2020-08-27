<?php

trait LidarrHomepageItem
{
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
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], true);
				$results = $downloader->getSystemStatus();
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
	
	public function getLidarrQueue()
	{
		if (!$this->config['homepageLidarrEnabled']) {
			$this->setAPIResponse('error', 'Lidarr homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageLidarrQueueEnabled']) {
			$this->setAPIResponse('error', 'Lidarr homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageLidarrAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageLidarrQueueAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['lidarrURL'])) {
			$this->setAPIResponse('error', 'Lidarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['lidarrToken'])) {
			$this->setAPIResponse('error', 'Lidarr Token is not defined', 422);
			return false;
		}
		$queueItems = array();
		$list = $this->csvHomepageUrlToken($this->config['lidarrURL'], $this->config['lidarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], true);
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
		if (!$this->config['homepageLidarrEnabled']) {
			$this->setAPIResponse('error', 'Lidarr homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageLidarrAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['lidarrURL'])) {
			$this->setAPIResponse('error', 'Lidarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['lidarrToken'])) {
			$this->setAPIResponse('error', 'Lidarr Token is not defined', 422);
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['lidarrURL'], $this->config['lidarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], true);
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