<?php

trait SonarrHomepageItem
{
	
	public function testConnectionSonarr()
	{
		if (empty($this->config['sonarrURL'])) {
			$this->setAPIResponse('error', 'Sonarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sonarrToken'])) {
			$this->setAPIResponse('error', 'Sonarr Token is not defined', 422);
			return false;
		}
		$failed = false;
		$errors = '';
		$list = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
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
				$this->writeLog('error', 'Sonarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
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
	
	public function getSonarrQueue()
	{
		if (!$this->config['homepageSonarrEnabled']) {
			$this->setAPIResponse('error', 'Sonarr homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageSonarrQueueEnabled']) {
			$this->setAPIResponse('error', 'Sonarr homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSonarrAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSonarrQueueAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['sonarrURL'])) {
			$this->setAPIResponse('error', 'Sonarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sonarrToken'])) {
			$this->setAPIResponse('error', 'Sonarr Token is not defined', 422);
			return false;
		}
		$queueItems = array();
		$list = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
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
				$this->writeLog('error', 'Sonarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
		}
		$api['content']['queueItems'] = $queueItems;
		$api['content']['historyItems'] = false;
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;;
	}
	
	public function getSonarrCalendar($startDate = null, $endDate = null)
	{
		$startDate = ($startDate) ?? $_GET['start'];
		$endDate = ($endDate) ?? $_GET['end'];
		if (!$this->config['homepageSonarrEnabled']) {
			$this->setAPIResponse('error', 'Sonarr homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageSonarrQueueEnabled']) {
			$this->setAPIResponse('error', 'Sonarr homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSonarrAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSonarrQueueAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['sonarrURL'])) {
			$this->setAPIResponse('error', 'Sonarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sonarrToken'])) {
			$this->setAPIResponse('error', 'Sonarr Token is not defined', 422);
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		foreach ($list as $key => $value) {
			try {
				$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
				$sonarr = $sonarr->getCalendar($startDate, $endDate, $this->config['sonarrUnmonitored']);
				$result = json_decode($sonarr, true);
				if (is_array($result) || is_object($result)) {
					$sonarrCalendar = (array_key_exists('error', $result)) ? '' : $this->formatSonarrCalendar($sonarr, $key);
				} else {
					$sonarrCalendar = '';
				}
			} catch (Exception $e) {
				$this->writeLog('error', 'Sonarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
			if (!empty($sonarrCalendar)) {
				$calendarItems = array_merge($calendarItems, $sonarrCalendar);
			}
		}
		$this->setAPIResponse('success', null, 200, $calendarItems);
		return $calendarItems;
	}
	
	public function formatSonarrCalendar($array, $number)
	{
		$array = json_decode($array, true);
		$gotCalendar = array();
		$i = 0;
		foreach ($array as $child) {
			$i++;
			$seriesName = $child['series']['title'];
			$seriesID = $child['series']['tvdbId'];
			$episodeID = $child['series']['tvdbId'];
			$monitored = $child['monitored'];
			if (!isset($episodeID)) {
				$episodeID = "";
			}
			//$episodeName = htmlentities($child['title'], ENT_QUOTES);
			$episodeAirDate = $child['airDateUtc'];
			$episodeAirDate = strtotime($episodeAirDate);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unaired = true;
			}
			if ($child['episodeNumber'] == "1") {
				$episodePremier = "true";
			} else {
				$episodePremier = "false";
				$date = new DateTime($episodeAirDate);
				$date->add(new DateInterval("PT1S"));
				$date->format(DateTime::ATOM);
				$child['airDateUtc'] = gmdate('Y-m-d\TH:i:s\Z', strtotime($date->format(DateTime::ATOM)));
			}
			$downloaded = $child['hasFile'];
			if ($downloaded == "0" && isset($unaired) && $episodePremier == "true") {
				$downloaded = "text-primary animated flash";
			} elseif ($downloaded == "0" && isset($unaired) && $monitored == "0") {
				$downloaded = "text-dark";
			} elseif ($downloaded == "0" && isset($unaired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$fanart = "/plugins/images/cache/no-np.png";
			foreach ($child['series']['images'] as $image) {
				if ($image['coverType'] == "fanart") {
					$fanart = $image['url'];
				}
			}
			if ($fanart !== "/plugins/images/cache/no-np.png" || (strpos($fanart, '://') === false)) {
				$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
				$imageURL = $fanart;
				$cacheFile = $cacheDirectory . $seriesID . '.jpg';
				$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
				if (!file_exists($cacheFile)) {
					$this->cacheImage($imageURL, $seriesID);
					unset($imageURL);
					unset($cacheFile);
				}
			}
			$bottomTitle = 'S' . sprintf("%02d", $child['seasonNumber']) . 'E' . sprintf("%02d", $child['episodeNumber']) . ' - ' . $child['title'];
			$details = array(
				"seasonCount" => $child['series']['seasonCount'],
				"status" => $child['series']['status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => isset($child['overview']) ? $child['overview'] : '',
				"runtime" => $child['series']['runtime'],
				"image" => $fanart,
				"ratings" => $child['series']['ratings']['value'],
				"videoQuality" => $child["hasFile"] && isset($child['episodeFile']['quality']['quality']['name']) ? $child['episodeFile']['quality']['quality']['name'] : "unknown",
				"audioChannels" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['audioChannels'] : "unknown",
				"audioCodec" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['audioCodec'] : "unknown",
				"videoCodec" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['videoCodec'] : "unknown",
				"size" => $child["hasFile"] && isset($child['episodeFile']['size']) ? $child['episodeFile']['size'] : "unknown",
				"genres" => $child['series']['genres'],
			);
			array_push($gotCalendar, array(
				"id" => "Sonarr-" . $number . "-" . $i,
				"title" => $seriesName,
				"start" => $child['airDateUtc'],
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details
			));
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return false;
	}
}