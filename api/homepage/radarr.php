<?php

trait RadarrHomepageItem
{
	public function testConnectionRadarr()
	{
		if (empty($this->config['radarrURL'])) {
			$this->setAPIResponse('error', 'Radarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['radarrToken'])) {
			$this->setAPIResponse('error', 'Radarr Token is not defined', 422);
			return false;
		}
		$failed = false;
		$errors = '';
		$list = $this->csvHomepageUrlToken($this->config['radarrURL'], $this->config['radarrToken']);
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
				$this->writeLog('error', 'Radarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
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
	
	public function getRadarrQueue()
	{
		if (!$this->config['homepageRadarrEnabled']) {
			$this->setAPIResponse('error', 'Radarr homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageRadarrQueueEnabled']) {
			$this->setAPIResponse('error', 'Radarr homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageRadarrAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageRadarrQueueAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['radarrURL'])) {
			$this->setAPIResponse('error', 'Radarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['radarrToken'])) {
			$this->setAPIResponse('error', 'Radarr Token is not defined', 422);
			return false;
		}
		$queueItems = array();
		$list = $this->csvHomepageUrlToken($this->config['radarrURL'], $this->config['radarrToken']);
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
				$this->writeLog('error', 'Radarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
		}
		$api['content']['queueItems'] = $queueItems;
		$api['content']['historyItems'] = false;
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;;
	}
	
	public function getRadarrCalendar($startDate = null, $endDate = null)
	{
		$startDate = ($startDate) ?? $_GET['start'];
		$endDate = ($endDate) ?? $_GET['end'];
		if (!$this->config['homepageRadarrEnabled']) {
			$this->setAPIResponse('error', 'Radarr homepage item is not enabled', 409);
			return false;
		}
		if (!$this->config['homepageRadarrQueueEnabled']) {
			$this->setAPIResponse('error', 'Radarr homepage module is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageRadarrAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageRadarrQueueAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage module', 401);
			return false;
		}
		if (empty($this->config['radarrURL'])) {
			$this->setAPIResponse('error', 'Radarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['radarrToken'])) {
			$this->setAPIResponse('error', 'Radarr Token is not defined', 422);
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['radarrURL'], $this->config['radarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
				$results = $downloader->getCalendar($startDate, $endDate);
				$result = json_decode($results, true);
				if (is_array($result) || is_object($result)) {
					$calendar = (array_key_exists('error', $result)) ? '' : $this->formatRadarrCalendar($results, $key, $value['url']);
				} else {
					$calendar = '';
				}
			} catch (Exception $e) {
				$this->writeLog('error', 'Radarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			}
			if (!empty($calendar)) {
				$calendarItems = array_merge($calendarItems, $calendar);
			}
		}
		$this->setAPIResponse('success', null, 200, $calendarItems);
		return $calendarItems;
	}
	
	public function formatRadarrCalendar($array, $number, $url)
	{
		$url = rtrim($url, '/'); //remove trailing slash
		$url = $url . '/api';
		$array = json_decode($array, true);
		$gotCalendar = array();
		$i = 0;
		foreach ($array as $child) {
			if (isset($child['physicalRelease'])) {
				$i++;
				$movieName = $child['title'];
				$movieID = $child['tmdbId'];
				if (!isset($movieID)) {
					$movieID = "";
				}
				$physicalRelease = $child['physicalRelease'];
				$physicalRelease = strtotime($physicalRelease);
				$physicalRelease = date("Y-m-d", $physicalRelease);
				if (new DateTime() < new DateTime($physicalRelease)) {
					$notReleased = "true";
				} else {
					$notReleased = "false";
				}
				$downloaded = $child['hasFile'];
				if ($downloaded == "0" && $notReleased == "true") {
					$downloaded = "text-info";
				} elseif ($downloaded == "1") {
					$downloaded = "text-success";
				} else {
					$downloaded = "text-danger";
				}
				$banner = "/plugins/images/cache/no-np.png";
				foreach ($child['images'] as $image) {
					if ($image['coverType'] == "banner" || $image['coverType'] == "fanart") {
						if (strpos($image['url'], '://') === false) {
							$imageUrl = $image['url'];
							$urlParts = explode("/", $url);
							$imageParts = explode("/", $image['url']);
							if ($imageParts[1] == end($urlParts)) {
								unset($imageParts[1]);
								$imageUrl = implode("/", $imageParts);
							}
							$banner = $url . $imageUrl . '?apikey=' . $this->config['radarrToken'];
						} else {
							$banner = $image['url'];
						}
						
					}
				}
				if ($banner !== "/plugins/images/cache/no-np.png" || (strpos($banner, 'apikey') !== false)) {
					$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
					$imageURL = $banner;
					$cacheFile = $cacheDirectory . $movieID . '.jpg';
					$banner = 'plugins/images/cache/' . $movieID . '.jpg';
					if (!file_exists($cacheFile)) {
						$this->cacheImage($imageURL, $movieID);
						unset($imageURL);
						unset($cacheFile);
					}
				}
				$alternativeTitles = "";
				foreach ($child['alternativeTitles'] as $alternative) {
					$alternativeTitles .= $alternative['title'] . ', ';
				}
				$alternativeTitles = empty($child['alternativeTitles']) ? "" : substr($alternativeTitles, 0, -2);
				$details = array(
					"topTitle" => $movieName,
					"bottomTitle" => $alternativeTitles,
					"status" => $child['status'],
					"overview" => $child['overview'],
					"runtime" => $child['runtime'],
					"image" => $banner,
					"ratings" => $child['ratings']['value'],
					"videoQuality" => $child["hasFile"] ? @$child['movieFile']['quality']['quality']['name'] : "unknown",
					"audioChannels" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['audioChannels'] : "unknown",
					"audioCodec" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['audioFormat'] : "unknown",
					"videoCodec" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['videoCodec'] : "unknown",
					"size" => $child["hasFile"] ? @$child['movieFile']['size'] : "unknown",
					"genres" => $child['genres'],
					"year" => isset($child['year']) ? $child['year'] : '',
					"studio" => isset($child['studio']) ? $child['studio'] : '',
				);
				array_push($gotCalendar, array(
					"id" => "Radarr-" . $number . "-" . $i,
					"title" => $movieName,
					"start" => $physicalRelease,
					"className" => "inline-popups bg-calendar movieID--" . $movieID,
					"imagetype" => "film " . $downloaded,
					"imagetypeFilter" => "film",
					"downloadFilter" => $downloaded,
					"bgColor" => str_replace('text', 'bg', $downloaded),
					"details" => $details
				));
			}
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return false;
	}
}