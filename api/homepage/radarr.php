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
		$homepageSettings = array(
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageRadarrEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageRadarrEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageRadarrAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageRadarrAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'radarrURL',
						'label' => 'URL',
						'value' => $this->config['radarrURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'radarrToken',
						'label' => 'Token',
						'value' => $this->config['radarrToken']
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
										<h3 lang="en">Radarr SOCKS API Connection</h3>
										<p>Using this feature allows you to access the API without having to reverse proxy it.  Just access it from: </p>
										<code>' . $this->getServerPath() . 'api/v2/socks/radarr/</code>
									</div>
								</div>
							</div>'
					),
					array(
						'type' => 'switch',
						'name' => 'radarrSocksEnabled',
						'label' => 'Enable',
						'value' => $this->config['radarrSocksEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'radarrSocksAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['radarrSocksAuth'],
						'options' => $this->groupOptions
					),
				),
				'Queue' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageRadarrQueueEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageRadarrQueueEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageRadarrQueueAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageRadarrQueueAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'switch',
						'name' => 'homepageRadarrQueueCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['homepageRadarrQueueCombine']
					),
					array(
						'type' => 'select',
						'name' => 'homepageRadarrQueueRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageRadarrQueueRefresh'],
						'options' => $this->timeOptions()
					),
				),
				'Calendar' => array(
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
					array(
						'type' => 'switch',
						'name' => 'radarrUnmonitored',
						'label' => 'Show Unmonitored',
						'value' => $this->config['radarrUnmonitored']
					),
					array(
						'type' => 'switch',
						'name' => 'radarrPhysicalRelease',
						'label' => 'Show Physical Release',
						'value' => $this->config['radarrPhysicalRelease']
					),
					array(
						'type' => 'switch',
						'name' => 'radarrDigitalRelease',
						'label' => 'Show Digital Release',
						'value' => $this->config['radarrDigitalRelease']
					),
					array(
						'type' => 'switch',
						'name' => 'radarrCinemaRelease',
						'label' => 'Show Cinema Releases',
						'value' => $this->config['radarrCinemaRelease']
					)
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
						'attr' => 'onclick="testAPIConnection(\'radarr\')"'
					),
				)
			)
		);
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
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'radarr');
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'radarr');
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
		if (!$this->homepageItemPermissions($this->radarrHomepagePermissions('calendar'), true)) {
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['radarrURL'], $this->config['radarrToken']);
		foreach ($list as $key => $value) {
			try {
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'radarr');
				$results = $downloader->getCalendar($startDate, $endDate, $this->config['radarrUnmonitored']);
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