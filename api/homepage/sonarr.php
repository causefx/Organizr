<?php

trait SonarrHomepageItem
{
	public function sonarrSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Sonarr',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/sonarr.png',
			'category' => 'PVR',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'docs' => $this->docs('features/homepage/sonarr-homepage-item'),
			'debug' => true,
			'settings' => [
				'About' => [
					$this->settingsOption('about', 'Sonarr', ['about' => 'This item allows access to Sonarr\'s calendar data and aggregates it to Organizr\'s calendar.  Along with that you also have the Downloader function that allow access to Sonarr\'s queue.  The last item that is included is the API SOCKS function which acts as a middleman between API\'s which is useful if you are not port forwarding or reverse proxying Sonarr.']),
				],
				'Enable' => [
					$this->settingsOption('enable', 'homepageSonarrEnabled'),
					$this->settingsOption('auth', 'homepageSonarrAuth'),
				],
				'Connection' => [
					$this->settingsOption('multiple-url', 'sonarrURL'),
					$this->settingsOption('multiple-token', 'sonarrToken'),
					$this->settingsOption('disable-cert-check', 'sonarrDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'sonarrUseCustomCertificate'),
				],
				'API SOCKS' => [
					$this->settingsOption('socks', 'sonarr'),
					$this->settingsOption('blank'),
					$this->settingsOption('enable', 'sonarrSocksEnabled'),
					$this->settingsOption('auth', 'sonarrSocksAuth'),
				],
				'Queue' => [
					$this->settingsOption('enable', 'homepageSonarrQueueEnabled'),
					$this->settingsOption('auth', 'homepageSonarrQueueAuth'),
					$this->settingsOption('combine', 'homepageSonarrQueueCombine'),
					$this->settingsOption('refresh', 'homepageSonarrQueueRefresh'),
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
					$this->settingsOption('blank'),
					$this->settingsOption('switch', 'sonarrUnmonitored', ['label' => 'Show Unmonitored']),
					$this->settingsOption('blank', '', ['type' => 'html', 'html' => '<hr />']),
					$this->settingsOption('blank', '', ['type' => 'html', 'html' => '<hr />']),
					$this->settingsOption('enable', 'sonarrIcon', ['label' => 'Show Sonarr Icon']),
					$this->settingsOption('calendar-link-url', 'sonarrCalendarLink'),
					$this->settingsOption('blank'),
					$this->settingsOption('calendar-frame-target', 'sonarrFrameTarget')
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'sonarr'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

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
				$options = $this->requestOptions($value['url'], null, $this->config['sonarrDisableCertCheck'], $this->config['sonarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'sonarr', null, null, $options);
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
				$this->setLoggerChannel('Sonarr')->error($e);
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

	public function sonarrHomepagePermissions($key = null)
	{
		$permissions = [
			'calendar' => [
				'enabled' => [
					'homepageSonarrEnabled'
				],
				'auth' => [
					'homepageSonarrAuth'
				],
				'not_empty' => [
					'sonarrURL',
					'sonarrToken'
				]
			],
			'queue' => [
				'enabled' => [
					'homepageSonarrEnabled',
					'homepageSonarrQueueEnabled'
				],
				'auth' => [
					'homepageSonarrAuth',
					'homepageSonarrQueueAuth'
				],
				'not_empty' => [
					'sonarrURL',
					'sonarrToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderSonarrQueue()
	{
		if ($this->homepageItemPermissions($this->sonarrHomepagePermissions('queue'))) {
			$loadingBox = ($this->config['homepageSonarrQueueCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['homepageSonarrQueueCombine']) ? 'buildDownloaderCombined(\'sonarr\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("sonarr"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrderSonarrQueue
		                ' . $builder . '
		                homepageDownloader("sonarr", "' . $this->config['homepageSonarrQueueRefresh'] . '");
		                // End homepageOrderSonarrQueue
	                </script>
				</div>
				';
		}
	}

	public function getSonarrQueue()
	{
		if (!$this->homepageItemPermissions($this->sonarrHomepagePermissions('queue'), true)) {
			return false;
		}
		$queueItems = array();
		$list = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		foreach ($list as $key => $value) {
			try {
				$options = $this->requestOptions($value['url'], $this->config['homepageSonarrQueueRefresh'], $this->config['sonarrDisableCertCheck'], $this->config['sonarrUseCustomCertificate']);
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'sonarr', null, null, $options);
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
				$this->setLoggerChannel('Sonarr')->error($e);
			}
		}
		$api['content']['queueItems'] = $queueItems;
		$api['content']['historyItems'] = false;
		$api['content'] = $api['content'] ?? false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}

	public function getSonarrCalendar($startDate = null, $endDate = null)
	{
		$startDate = ($startDate) ?? $_GET['start'] ?? date('Y-m-d', strtotime('-' . $this->config['calendarStart'] . ' days'));
		$endDate = ($endDate) ?? $_GET['end'] ?? date('Y-m-d', strtotime('+' . $this->config['calendarEnd'] . ' days'));
		if (!$this->homepageItemPermissions($this->sonarrHomepagePermissions('calendar'), true)) {
			return false;
		}
		if ($this->demo) {
			return $this->demoData('sonarr/calendar.json');
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		foreach ($list as $key => $value) {
			try {
				$options = $this->requestOptions($value['url'], null, $this->config['sonarrDisableCertCheck'], $this->config['sonarrUseCustomCertificate']);
				$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'sonarr', null, null, $options);
				$sonarr = $sonarr->getCalendar($startDate, $endDate, $this->config['sonarrUnmonitored']);
				$result = json_decode($sonarr, true);
				if (is_array($result) || is_object($result)) {
					$sonarrCalendar = (array_key_exists('error', $result)) ? '' : $this->formatSonarrCalendar($sonarr, $key);
				} else {
					$sonarrCalendar = '';
				}
			} catch (Exception $e) {
				$this->setLoggerChannel('Sonarr')->error($e);
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
		$gotCalendar = [];
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
			$episodeAirDate = $child['airDateUtc'];
			$episodeAirDate = strtotime($episodeAirDate);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unAired = true;
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
			if ($downloaded == "0" && isset($unAired) && $episodePremier == "true") {
				$downloaded = "text-primary animated flash";
			} elseif ($downloaded == "0" && isset($unAired) && $monitored == "0") {
				$downloaded = "text-dark";
			} elseif ($downloaded == "0" && isset($unAired)) {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$fanArt = "/plugins/images/homepage/no-np.png";
			foreach ($child['series']['images'] as $image) {
				if ($image['coverType'] == "fanart" && (isset($image['url']) && $image['url'] !== '')) {
					$fanArt = $image['url'];
				}
				if ($image['coverType'] == 'fanart' && (isset($image['remoteUrl']) && $image['remoteUrl'] !== '')) {
					$fanArt = $image['remoteUrl'];
				}
			}
			if ($fanArt !== "/plugins/images/homepage/no-np.png" || (strpos($fanArt, '://') === false)) {
				$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
				$imageURL = $fanArt;
				$fanArt = 'data/cache/' . $seriesID . '.jpg';
				if (!file_exists($cacheDirectory . $seriesID . '.jpg')) {
					$this->cacheImage($imageURL, $seriesID);
					unset($imageURL);
				}
			}
			$bottomTitle = 'S' . sprintf("%02d", $child['seasonNumber']) . 'E' . sprintf("%02d", $child['episodeNumber']) . ' - ' . $child['title'];
			$href = $this->config['sonarrCalendarLink'] ?? '';
			if (empty($href) && !empty($this->config['sonarrURL'])) {
				$href_arr = explode(',', $this->config['sonarrURL']);
				$href = reset($href_arr);
			}
			if (!empty($href)) {
				$href = $href . '/series/' . preg_replace('/[^A-Za-z0-9 -]/', '', str_replace('&', 'and', preg_replace('/[[:space:]]+/', '-', $seriesName)));
				$href = str_replace("//series/", "/series/", $href);
			}
			$details = [
				"seasonCount" => $child['series']['seasonCount'] ?? isset($child['series']['seasons']) ? count($child['series']['seasons']) : 0,
				"status" => $child['series']['status'],
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => $child['overview'] ?? '',
				"runtime" => $child['series']['runtime'],
				"image" => $fanArt,
				"ratings" => $child['series']['ratings']['value'],
				"videoQuality" => $child["hasFile"] && isset($child['episodeFile']['quality']['quality']['name']) ? $child['episodeFile']['quality']['quality']['name'] : "unknown",
				"audioChannels" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['audioChannels'] : "unknown",
				"audioCodec" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['audioCodec'] : "unknown",
				"videoCodec" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['videoCodec'] : "unknown",
				"size" => $child["hasFile"] && isset($child['episodeFile']['size']) ? $child['episodeFile']['size'] : "unknown",
				"genres" => $child['series']['genres'],
				"href" => strtolower($href),
				"icon" => "/plugins/images/tabs/sonarr.png",
				"frame" => $this->config['sonarrFrameTarget'],
				"showLink" => $this->config['sonarrIcon']
			];
			$gotCalendar[] = [
				"id" => "Sonarr-" . $number . "-" . $i,
				"title" => $seriesName,
				"start" => $child['airDateUtc'],
				"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
				"imagetype" => "tv " . $downloaded,
				"imagetypeFilter" => "tv",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details
			];
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return false;
	}
}
