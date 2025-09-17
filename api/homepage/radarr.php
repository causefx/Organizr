<?php

trait RadarrHomepageItem
{
	public function radarrSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Radarr',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/radarr.png',
			'category' => 'PVR',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageRadarrEnabled'),
					$this->settingsOption('auth', 'homepageRadarrAuth'),
				],
				'Connection' => [
					$this->settingsOption('multiple-url', 'radarrURL'),
					$this->settingsOption('multiple-token', 'radarrToken'),
					$this->settingsOption('disable-cert-check', 'radarrDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'radarrUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'radarr'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'radarrSocksEnabled'),
					$this->settingsOption('auth', 'radarrSocksAuth'),
				],
				'Queue' => [
					$this->settingsOption('enable', 'homepageRadarrQueueEnabled'),
					$this->settingsOption('auth', 'homepageRadarrQueueAuth'),
					$this->settingsOption('combine', 'homepageRadarrQueueCombine'),
					$this->settingsOption('refresh', 'homepageRadarrQueueRefresh'),
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
					$this->settingsOption('switch', 'radarrUnmonitored', ['label' => 'Show Unmonitored']),
					$this->settingsOption('switch', 'radarrPhysicalRelease', ['label' => 'Show Physical Releases']),
					$this->settingsOption('switch', 'radarrDigitalRelease', ['label' => 'Show Digital Releases']),
					$this->settingsOption('switch', 'radarrCinemaRelease', ['label' => 'Show Cinema Releases']),
					$this->settingsOption('blank', '', ['type' => 'html', 'html' => '<hr />']),
					$this->settingsOption('blank', '', ['type' => 'html', 'html' => '<hr />']),
					$this->settingsOption('enable', 'radarrIcon', ['label' => 'Show Radarr Icon']),
					$this->settingsOption('calendar-link-url', 'radarrCalendarLink'),
					$this->settingsOption('blank'),
					$this->settingsOption('calendar-frame-target', 'radarrFrameTarget')
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'radarr'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

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
				$options = $this->requestOptions($value['url'], null, $this->config['radarrDisableCertCheck'], $this->config['radarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'radarr', null, null, $options);
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
				$this->setLoggerChannel('Radarr')->error($e);
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

	public function radarrHomepagePermissions($key = null)
	{
		$permissions = [
			'calendar' => [
				'enabled' => [
					'homepageRadarrEnabled'
				],
				'auth' => [
					'homepageRadarrAuth'
				],
				'not_empty' => [
					'radarrURL',
					'radarrToken'
				]
			],
			'queue' => [
				'enabled' => [
					'homepageRadarrEnabled',
					'homepageRadarrQueueEnabled'
				],
				'auth' => [
					'homepageRadarrAuth',
					'homepageRadarrQueueAuth'
				],
				'not_empty' => [
					'radarrURL',
					'radarrToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderRadarrQueue()
	{
		if ($this->homepageItemPermissions($this->radarrHomepagePermissions('queue'))) {
			$loadingBox = ($this->config['homepageRadarrQueueCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['homepageRadarrQueueCombine']) ? 'buildDownloaderCombined(\'radarr\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("radarr"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrderRadarrQueue
		                ' . $builder . '
		                homepageDownloader("radarr", "' . $this->config['homepageRadarrQueueRefresh'] . '");
		                // End homepageOrderRadarrQueue
	                </script>
				</div>
				';
		}
	}

	public function getRadarrQueue()
	{
		if (!$this->homepageItemPermissions($this->radarrHomepagePermissions('queue'), true)) {
			return false;
		}
		$queueItems = array();
		$list = $this->csvHomepageUrlToken($this->config['radarrURL'], $this->config['radarrToken']);
		foreach ($list as $key => $value) {
			try {
				$options = $this->requestOptions($value['url'], $this->config['homepageRadarrQueueRefresh'], $this->config['radarrDisableCertCheck'], $this->config['radarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'radarr', null, null, $options);
				$results = $downloader->getQueue();
				$downloadList = json_decode($results, true);
				if (is_array($downloadList) || is_object($downloadList)) {
					$queue = (array_key_exists('error', $downloadList)) ? [] : $downloadList;
					$queue = $queue['records'] ?? $queue;
				} else {
					$queue = [];
				}
				if (!empty($queue)) {
					$queueItems = array_merge($queueItems, $queue);
				}
			} catch (Exception $e) {
				$this->logger->error($e);
			}
		}
		$api['content']['queueItems'] = $queueItems;
		$api['content']['historyItems'] = false;
		$api['content'] = $api['content'] ?? false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}

	public function getRadarrCalendar($startDate = null, $endDate = null)
	{
		$startDate = ($startDate) ?? $_GET['start'] ?? date('Y-m-d', strtotime('-' . $this->config['calendarStart'] . ' days'));
		$endDate = ($endDate) ?? $_GET['end'] ?? date('Y-m-d', strtotime('+' . $this->config['calendarEnd'] . ' days'));
		if (!$this->homepageItemPermissions($this->radarrHomepagePermissions('calendar'), true)) {
			return false;
		}
		if ($this->demo) {
			return $this->demoData('radarr/calendar.json');
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['radarrURL'], $this->config['radarrToken']);
		foreach ($list as $key => $value) {
			try {
				$options = $this->requestOptions($value['url'], $this->config['homepageRadarrQueueRefresh'], $this->config['radarrDisableCertCheck'], $this->config['radarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'radarr', null, null, $options);
				$results = $downloader->getCalendar($startDate, $endDate, $this->config['radarrUnmonitored']);
				$result = json_decode($results, true);
				if (is_array($result) || is_object($result)) {
					$calendar = (array_key_exists('error', $result)) ? '' : $this->formatRadarrCalendar($results, $key, $value['url']);
				} else {
					$calendar = '';
				}
			} catch (Exception $e) {
				$this->setLoggerChannel('Radarr')->error($e);
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
			for ($j = 0; $j < 3; $j++) {
				$type = [];
				if ($j == 0 && $this->config['radarrPhysicalRelease'] && isset($child['physicalRelease'])) {
					$releaseDate = $child['physicalRelease'];
					array_push($type, "physical");
					if (isset($child['digitalRelease']) && $child['physicalRelease'] == $child['digitalRelease']) {
						array_push($type, "digital");
						$j++;
					}
					if (isset($child['inCinemas']) && $child['physicalRelease'] == $child['inCinemas']) {
						array_push($type, "cinema");
						$j += 2;
					}
				} elseif ($j == 1 && $this->config['radarrDigitalRelease'] && isset($child['digitalRelease'])) {
					$releaseDate = $child['digitalRelease'];
					array_push($type, "digital");
					if (isset($child['inCinemas']) && $child['digitalRelease'] == $child['inCinemas']) {
						array_push($type, "cinema");
						$j++;
					}
				} elseif ($j == 2 && $this->config['radarrCinemaRelease'] && isset($child['inCinemas'])) {
					$releaseDate = $child['inCinemas'];
					array_push($type, "cinema");
				} else {
					continue;
				}
				$i++;
				$movieName = $child['title'];
				$movieID = $child['tmdbId'];
				if (!isset($movieID)) {
					$movieID = "";
				}
				$releaseDate = strtotime($releaseDate);
				$releaseDate = date("Y-m-d", $releaseDate);
				if (new DateTime() < new DateTime($releaseDate)) {
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
				$banner = "/plugins/images/homepage/no-np.png";
				foreach ($child['images'] as $image) {
					if ($image['coverType'] == "banner" || $image['coverType'] == "fanart") {
						if ($image['coverType'] == 'fanart' && (isset($image['remoteUrl']) && $image['remoteUrl'] !== '')) {
							$banner = $image['remoteUrl'];
						}elseif (strpos($image['url'], '://') === false) {
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
				if ($banner !== "/plugins/images/homepage/no-np.png" && (strpos($banner, 'apikey') !== false)) {
					$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
					$imageURL = $banner;
					$cacheFile = $cacheDirectory . $movieID . '.jpg';
					$banner = 'data/cache/' . $movieID . '.jpg';
					if (!file_exists($cacheFile)) {
						$this->cacheImage($imageURL, $movieID);
						unset($imageURL);
						unset($cacheFile);
					}
				}
				$alternativeTitles = "";
				if (!empty($child['alternativeTitles'])) {
					foreach ($child['alternativeTitles'] as $alternative) {
						$alternativeTitles .= $alternative['title'] . ', ';
					}
				} elseif (!empty($child['alternateTitles'])) { //v3 API
					foreach ($child['alternateTitles'] as $alternative) {
						$alternativeTitles .= $alternative['title'] . ', ';
					}
				}
				$alternativeTitles = empty($alternativeTitles) ? "" : substr($alternativeTitles, 0, -2);
				$href = $this->config['radarrCalendarLink'] ?? '';
				if (empty($href) && !empty($this->config['radarrURL'])) {
					$href_arr = explode(',', $this->config['radarrURL']);
					$href = reset($href_arr);
				}
				if (!empty($href)) {
					$href = $href . '/movie/' . $movieID;
					$href = str_replace("//movie/", "/movie/", $href);
				}
				$details = array(
					"topTitle" => $movieName,
					"bottomTitle" => $alternativeTitles,
					"status" => $child['status'],
					"overview" => $child['overview'],
					"runtime" => $child['runtime'],
					"image" => $banner,
					"ratings" => $child['ratings']['value'] ?? 0,
					"videoQuality" => $child["hasFile"] ? @$child['movieFile']['quality']['quality']['name'] : "unknown",
					"audioChannels" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['audioChannels'] : "unknown",
					"audioCodec" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['audioFormat'] : "unknown",
					"videoCodec" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['videoCodec'] : "unknown",
					"size" => $child["hasFile"] ? @$child['movieFile']['size'] : "unknown",
					"genres" => $child['genres'],
					"year" => $child['year'] ?? '',
					"studio" => $child['studio'] ?? '',
					"href" => strtolower($href),
					"icon" => "/plugins/images/tabs/radarr.png",
					"frame" => $this->config['radarrFrameTarget'],
					"showLink" => $this->config['radarrIcon']
				);
				array_push($gotCalendar, array(
					"id" => "Radarr-" . $number . "-" . $i,
					"title" => $movieName,
					"start" => $releaseDate,
					"className" => "inline-popups bg-calendar movieID--" . $movieID,
					"imagetype" => "film " . $downloaded,
					"imagetypeFilter" => "film",
					"downloadFilter" => $downloaded,
					"releaseType" => $type,
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
