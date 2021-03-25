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
		$homepageSettings = array(
			'docs' => 'https://docs.organizr.app/books/setup-features/page/sonarr',
			'settings' => array(
				'About' => array(
					array(
						'type' => 'html',
						'override' => 12,
						'label' => '',
						'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">
										<h3 lang="en">Sonarr Homepage Item</h3>
										<p lang="en">This item allows access to Sonarr\'s calendar data and aggregates it to Organizr\'s calendar.  Along with that you also have the Downloader function that allow access to Sonarr\'s queue.  The last item that is included is the API SOCKS function which acts as a middleman between API\'s which is useful if you are not port forwarding or reverse proxying Sonarr.</p>
									</div>
								</div>
							</div>'
					),
				),
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageSonarrEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageSonarrEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageSonarrAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageSonarrAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'sonarrURL',
						'label' => 'URL',
						'value' => $this->config['sonarrURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password-alt',
						'name' => 'sonarrToken',
						'label' => 'Token',
						'value' => $this->config['sonarrToken']
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
										<h3 lang="en">Sonarr SOCKS API Connection</h3>
										<p>Using this feature allows you to access the API without having to reverse proxy it.  Just access it from: </p>
										<code>' . $this->getServerPath() . 'api/v2/socks/sonarr/</code>
									</div>
								</div>
							</div>'
					),
					array(
						'type' => 'switch',
						'name' => 'sonarrSocksEnabled',
						'label' => 'Enable',
						'value' => $this->config['sonarrSocksEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'sonarrSocksAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['sonarrSocksAuth'],
						'options' => $this->groupOptions
					),
				),
				'Queue' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageSonarrQueueEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageSonarrQueueEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageSonarrQueueAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageSonarrQueueAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'switch',
						'name' => 'homepageSonarrQueueCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['homepageSonarrQueueCombine']
					),
					array(
						'type' => 'select',
						'name' => 'homepageSonarrQueueRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageSonarrQueueRefresh'],
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
						'name' => 'sonarrUnmonitored',
						'label' => 'Show Unmonitored',
						'value' => $this->config['sonarrUnmonitored']
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
						'attr' => 'onclick="testAPIConnection(\'sonarr\')"'
					),
				)
			)
		);
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
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'sonarr');
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
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
				$downloader = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'sonarr');
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
		if (!$this->homepageItemPermissions($this->sonarrHomepagePermissions('calendar'), true)) {
			return false;
		}
		$calendarItems = array();
		$list = $this->csvHomepageUrlToken($this->config['sonarrURL'], $this->config['sonarrToken']);
		foreach ($list as $key => $value) {
			try {
				$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], 'sonarr');
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