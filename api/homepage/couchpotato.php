<?php

trait CouchPotatoHomepageItem
{
	public function getCouchPotatoCalendar()
	{
		if (!$this->config['homepageCouchpotatoEnabled']) {
			$this->setAPIResponse('error', 'CouchPotato homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageCouchpotatoAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['couchpotatoURL'])) {
			$this->setAPIResponse('error', 'Radarr URL is not defined', 422);
			return false;
		}
		if (empty($this->config['couchpotatoToken'])) {
			$this->setAPIResponse('error', 'Radarr Token is not defined', 422);
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['couchpotatoURL'], $this->config['couchpotatoToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\CouchPotato\CouchPotato($value['url'], $value['token']);
				$calendar = $this->formatCouchCalendar($downloader->getMediaList(array('status' => 'active,done')), $key);
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
	
	public function formatCouchCalendar($array, $number)
	{
		$api = json_decode($array, true);
		$gotCalendar = array();
		$i = 0;
		foreach ($api['movies'] as $child) {
			$i++;
			$movieName = $child['info']['original_title'];
			$movieID = $child['info']['tmdb_id'];
			if (!isset($movieID)) {
				$movieID = "";
			}
			$physicalRelease = (isset($child['info']['released']) ? $child['info']['released'] : null);
			$backupRelease = (isset($child['info']['release_date']['theater']) ? $child['info']['release_date']['theater'] : null);
			$physicalRelease = (isset($physicalRelease) ? $physicalRelease : $backupRelease);
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date("Y-m-d", $physicalRelease);
			$oldestDay = new DateTime ($this->currentTime);
			$oldestDay->modify('-' . $this->config['calendarStart'] . ' days');
			$newestDay = new DateTime ($this->currentTime);
			$newestDay->modify('+' . $this->config['calendarEnd'] . ' days');
			$startDt = new DateTime ($physicalRelease);
			$calendarStartDiff = date_diff($startDt, $newestDay);
			$calendarEndDiff = date_diff($startDt, $oldestDay);
			if (!$this->calendarDaysCheck($calendarStartDiff->format('%R') . $calendarStartDiff->days, $calendarEndDiff->format('%R') . $calendarEndDiff->days)) {
				continue;
			}
			if (new DateTime() < $startDt) {
				$notReleased = "true";
			} else {
				$notReleased = "false";
			}
			$downloaded = ($child['status'] == "active") ? "0" : "1";
			if ($downloaded == "0" && $notReleased == "true") {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			if (!empty($child['info']['images']['backdrop_original'])) {
				$banner = $child['info']['images']['backdrop_original'][0];
			} elseif (!empty($child['info']['images']['backdrop'])) {
				$banner = $child['info']['images']['backdrop_original'][0];
			} else {
				$banner = "/plugins/images/cache/no-np.png";
			}
			if ($banner !== "/plugins/images/cache/no-np.png") {
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
			$hasFile = (!empty($child['releases']) && !empty($child['releases'][0]['files']['movie']));
			$details = array(
				"topTitle" => $movieName,
				"bottomTitle" => $child['info']['tagline'],
				"status" => $child['status'],
				"overview" => $child['info']['plot'],
				"runtime" => $child['info']['runtime'],
				"image" => $banner,
				"ratings" => isset($child['info']['rating']['imdb'][0]) ? $child['info']['rating']['imdb'][0] : '',
				"videoQuality" => $hasFile ? $child['releases'][0]['quality'] : "unknown",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"genres" => $child['info']['genres'],
				"year" => isset($child['info']['year']) ? $child['info']['year'] : '',
				"studio" => isset($child['info']['year']) ? $child['info']['year'] : '',
			);
			array_push($gotCalendar, array(
				"id" => "CouchPotato-" . $number . "-" . $i,
				"title" => $movieName,
				"start" => $physicalRelease,
				"className" => "inline-popups bg-calendar calendar-item movieID--" . $movieID,
				"imagetype" => "film " . $downloaded,
				"imagetypeFilter" => "film",
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