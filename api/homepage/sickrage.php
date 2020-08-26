<?php

trait SickRageHomepageItem
{
	public function testConnectionSickRage()
	{
		if (empty($this->config['sickrageURL'])) {
			$this->setAPIResponse('error', 'SickRage URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sickrageToken'])) {
			$this->setAPIResponse('error', 'SickRage Token is not defined', 422);
			return false;
		}
		$failed = false;
		$errors = '';
		$list = $this->csvHomepageUrlToken($this->config['sickrageURL'], $this->config['sickrageToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\SickRage\SickRage($value['url'], $value['token']);
				$results = $downloader->sb();
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
				$this->writeLog('error', 'SickRage Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
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
	
	
	public function getSickRageCalendar($startDate = null, $endDate = null)
	{
		if (!$this->config['homepageSickrageEnabled']) {
			$this->setAPIResponse('error', 'SickRage homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSickrageAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageRadarrQueueAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['sickrageURL'])) {
			$this->setAPIResponse('error', 'SickRage URL is not defined', 422);
			return false;
		}
		if (empty($this->config['sickrageToken'])) {
			$this->setAPIResponse('error', 'SickRage Token is not defined', 422);
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['sickrageURL'], $this->config['sickrageToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\SickRage\SickRage($value['url'], $value['token']);
				$sickrageFuture = $this->formatSickrageCalendarWanted($downloader->future(), $key);
				$sickrageHistory = $this->formatSickrageCalendarHistory($downloader->history("100", "downloaded"), $key);
				if (!empty($sickrageFuture)) {
					$calendarItems = array_merge($calendarItems, $sickrageFuture);
				}
				if (!empty($sickrageHistory)) {
					$calendarItems = array_merge($calendarItems, $sickrageHistory);
				}
			} catch (Exception $e) {
				$this->writeLog('error', 'SickRage Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
		}
		$this->setAPIResponse('success', null, 200, $calendarItems);
		return $calendarItems;
	}
	
	public function formatSickrageCalendarWanted($array, $number)
	{
		$array = json_decode($array, true);
		$gotCalendar = array();
		$i = 0;
		foreach ($array['data']['missed'] as $child) {
			$i++;
			$seriesName = $child['show_name'];
			$seriesID = $child['tvdbid'];
			$episodeID = $child['tvdbid'];
			$episodeAirDate = $child['airdate'];
			$episodeAirDateTime = explode(" ", $child['airs']);
			$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
			$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unaired = true;
			}
			$downloaded = "0";
			if ($downloaded == "0" && isset($unaired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
			$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
			$cacheFile = $cacheDirectory . $seriesID . '.jpg';
			$fanart = "/plugins/images/cache/no-np.png";
			if (file_exists($cacheFile)) {
				$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
				unset($cacheFile);
			}
			$details = array(
				"seasonCount" => "",
				"status" => $child['show_status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
				"runtime" => "",
				"image" => $fanart,
				"ratings" => "",
				"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"size" => "",
				"genres" => "",
			);
			array_push($gotCalendar, array(
				"id" => "Sick-" . $number . "-Miss-" . $i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details,
			));
		}
		foreach ($array['data']['today'] as $child) {
			$i++;
			$seriesName = $child['show_name'];
			$seriesID = $child['tvdbid'];
			$episodeID = $child['tvdbid'];
			$episodeAirDate = $child['airdate'];
			$episodeAirDateTime = explode(" ", $child['airs']);
			$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
			$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unaired = true;
			}
			$downloaded = "0";
			if ($downloaded == "0" && isset($unaired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
			$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
			$cacheFile = $cacheDirectory . $seriesID . '.jpg';
			$fanart = "/plugins/images/cache/no-np.png";
			if (file_exists($cacheFile)) {
				$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
				unset($cacheFile);
			}
			$details = array(
				"seasonCount" => "",
				"status" => $child['show_status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
				"runtime" => "",
				"image" => $fanart,
				"ratings" => "",
				"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"size" => "",
				"genres" => "",
			);
			array_push($gotCalendar, array(
				"id" => "Sick-" . $number . "-Today-" . $i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details,
			));
		}
		foreach ($array['data']['soon'] as $child) {
			$i++;
			$seriesName = $child['show_name'];
			$seriesID = $child['tvdbid'];
			$episodeID = $child['tvdbid'];
			$episodeAirDate = $child['airdate'];
			$episodeAirDateTime = explode(" ", $child['airs']);
			$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
			$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unaired = true;
			}
			$downloaded = "0";
			if ($downloaded == "0" && isset($unaired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
			$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
			$cacheFile = $cacheDirectory . $seriesID . '.jpg';
			$fanart = "/plugins/images/cache/no-np.png";
			if (file_exists($cacheFile)) {
				$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
				unset($cacheFile);
			}
			$details = array(
				"seasonCount" => "",
				"status" => $child['show_status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
				"runtime" => "",
				"image" => $fanart,
				"ratings" => "",
				"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"size" => "",
				"genres" => "",
			);
			array_push($gotCalendar, array(
				"id" => "Sick-" . $number . "-Soon-" . $i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details,
			));
		}
		foreach ($array['data']['later'] as $child) {
			$i++;
			$seriesName = $child['show_name'];
			$seriesID = $child['tvdbid'];
			$episodeID = $child['tvdbid'];
			$episodeAirDate = $child['airdate'];
			$episodeAirDateTime = explode(" ", $child['airs']);
			$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
			$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unaired = true;
			}
			$downloaded = "0";
			if ($downloaded == "0" && isset($unaired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
			$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
			$cacheFile = $cacheDirectory . $seriesID . '.jpg';
			$fanart = "/plugins/images/cache/no-np.png";
			if (file_exists($cacheFile)) {
				$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
				unset($cacheFile);
			}
			$details = array(
				"seasonCount" => "",
				"status" => $child['show_status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
				"runtime" => "",
				"image" => $fanart,
				"ratings" => "",
				"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"size" => "",
				"genres" => "",
			);
			array_push($gotCalendar, array(
				"id" => "Sick-" . $number . "-Later-" . $i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details,
			));
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return false;
	}
	
	public function formatSickrageCalendarHistory($array, $number)
	{
		$array = json_decode($array, true);
		$gotCalendar = array();
		$i = 0;
		foreach ($array['data'] as $child) {
			$i++;
			$seriesName = $child['show_name'];
			$seriesID = $child['tvdbid'];
			$episodeID = $child['tvdbid'];
			$episodeAirDate = $child['date'];
			$downloaded = "text-success";
			$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']);
			$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
			$cacheFile = $cacheDirectory . $seriesID . '.jpg';
			$fanart = "/plugins/images/cache/no-np.png";
			if (file_exists($cacheFile)) {
				$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
				unset($cacheFile);
			}
			$details = array(
				"seasonCount" => "",
				"status" => $child['status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => '',
				"runtime" => isset($child['series']['runtime']) ? $child['series']['runtime'] : 30,
				"image" => $fanart,
				"ratings" => isset($child['series']['ratings']['value']) ? $child['series']['ratings']['value'] : "unknown",
				"videoQuality" => isset($child["quality"]) ? $child['quality'] : "unknown",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"size" => "",
				"genres" => "",
			);
			array_push($gotCalendar, array(
				"id" => "Sick-" . $number . "-History-" . $i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details,
			));
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return false;
	}
}