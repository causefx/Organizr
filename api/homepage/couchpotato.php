<?php

trait CouchPotatoHomepageItem
{
	
	public function couchPotatoSettingsArray()
	{
		return array(
			'name' => 'CouchPotato',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/couchpotato.png',
			'category' => 'PVR',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageCouchpotatoEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageCouchpotatoEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageCouchpotatoAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageCouchpotatoAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'couchpotatoURL',
						'label' => 'URL',
						'value' => $this->config['couchpotatoURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'couchpotatoToken',
						'label' => 'Token',
						'value' => $this->config['couchpotatoToken']
					)
				),
				'Misc Options' => array(
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
					)
				)
			)
		);
	}
	
	public function couchPotatoHomepagePermissions($key = null)
	{
		$permissions = [
			'calendar' => [
				'enabled' => [
					'homepageCouchpotatoEnabled'
				],
				'auth' => [
					'homepageCouchpotatoAuth'
				],
				'not_empty' => [
					'couchpotatoURL',
					'couchpotatoToken'
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
	
	public function getCouchPotatoCalendar()
	{
		if (!$this->homepageItemPermissions($this->couchPotatoHomepagePermissions('calendar'), true)) {
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