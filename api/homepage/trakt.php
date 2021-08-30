<?php

trait TraktHomepageItem
{
	public function traktSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Trakt',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/trakt.png',
			'category' => 'Calendar',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'docs' => 'https://docs.organizr.app/books/setup-features/page/trakt',
			'debug' => true,
			'settings' => [
				'About' => [
					$this->settingsOption('html', null, [
						'override' => 12,
						'html' => '
							<div class="panel panel-default">
								<div class="panel-wrapper collapse in">
									<div class="panel-body">
										<h3 lang="en">Trakt Homepage Item</h3>
										<p lang="en">This homepage item enables the calendar on the homepage and displays your movies and/or tv shows from Trakt\'s API.</p>
										<p lang="en">In order for this item to be setup, you need to goto the following URL to create a new API app.</p>
										<p><a href="https://trakt.tv/oauth/applications/new" target="_blank">New API App</a></p>
										<p lang="en">Enter anything for Name and Description.  You can leave Javascript and Permissions blank.  The only info you have to enter is for Redirect URI.  Enter the following URL:</p>
										<code>' . $this->getServerPath() . 'api/v2/oauth/trakt</code>
									</div>
								</div>
							</div>'
					]),
				],
				'Enable' => [
					$this->settingsOption('enable', 'homepageTraktEnabled'),
					$this->settingsOption('auth', 'homepageTraktAuth'),
				],
				'Connection' => [
					$this->settingsOption('input', 'traktClientId', ['label' => 'Client Id']),
					$this->settingsOption('password-alt', 'traktClientSecret', ['label' => 'Client Secret']),
					$this->settingsOption('blank'),
					$this->settingsOption('button', '', ['label' => 'Please Save before clicking button', 'icon' => 'fa fa-user', 'class' => 'pull-right', 'text' => 'Connect Account', 'attr' => 'onclick="openOAuth(\'trakt\')"']),
				],
				'Calendar' => [
					$this->settingsOption('calendar-start', 'calendarStartTrakt', ['help' => 'Total Days (Adding start and end days) has a maximum of 33 Days from Trakt API']),
					$this->settingsOption('calendar-end', 'calendarEndTrakt', ['help' => 'Total Days (Adding start and end days) has a maximum of 33 Days from Trakt API']),
					$this->settingsOption('calendar-starting-day', 'calendarFirstDay'),
					$this->settingsOption('calendar-default-view', 'calendarDefault'),
					$this->settingsOption('calendar-time-format', 'calendarTimeFormat'),
					$this->settingsOption('calendar-locale', 'calendarLocale'),
					$this->settingsOption('calendar-limit', 'calendarLimit'),
					$this->settingsOption('refresh', 'calendarRefresh'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function traktHomepagePermissions($key = null)
	{
		$permissions = [
			'calendar' => [
				'enabled' => [
					'homepageTraktEnabled'
				],
				'auth' => [
					'homepageTraktAuth'
				],
				'not_empty' => [
					'traktClientId',
					'traktAccessToken'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function getTraktCalendar($startDate = null)
	{
		$startDate = date('Y-m-d', strtotime('-' . $this->config['calendarStartTrakt'] . ' days'));
		$calendarItems = array();
		$errors = null;
		$totalDays = (int)$this->config['calendarStartTrakt'] + (int)$this->config['calendarEndTrakt'];
		if (!$this->homepageItemPermissions($this->traktHomepagePermissions('calendar'), true)) {
			return false;
		}
		$headers = [
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $this->config['traktAccessToken'],
			'trakt-api-version' => 2,
			'trakt-api-key' => $this->config['traktClientId']
		];
		$url = $this->qualifyURL('https://api.trakt.tv/calendars/my/shows/' . $startDate . '/' . $totalDays . '?extended=full');
		$options = $this->requestOptions($url, $this->config['calendarRefresh']);
		try {
			$response = Requests::get($url, $headers, $options);
			if ($response->success) {
				$data = json_decode($response->body, true);
				$traktTv = $this->formatTraktCalendarTv($data);
				if (!empty($traktTv)) {
					$calendarItems = array_merge($calendarItems, $traktTv);
				}
			}
			
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Trakt Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			$errors = true;
		}
		$url = $this->qualifyURL('https://api.trakt.tv/calendars/my/movies/' . $startDate . '/' . $totalDays . '?extended=full');
		try {
			$response = Requests::get($url, $headers, $options);
			if ($response->success) {
				$data = json_decode($response->body, true);
				$traktMovies = $this->formatTraktCalendarMovies($data);
				if (!empty($traktTv)) {
					$calendarItems = array_merge($calendarItems, $traktMovies);
				}
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Trakt Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			$errors = true;
		}
		if ($errors) {
			$this->setAPIResponse('error', 'An error Occurred', 500, null);
			return false;
		}
		$this->setAPIResponse('success', null, 200, $calendarItems);
		$this->traktOAuthRefresh();
		return $calendarItems;
	}
	
	public function formatTraktCalendarTv($array)
	{
		$gotCalendar = array();
		$i = 0;
		foreach ($array as $child) {
			$i++;
			$seriesName = $child['show']['title'];
			$seriesID = $child['show']['ids']['tmdb'];
			$episodeID = $child['show']['ids']['tmdb'];
			if (!isset($episodeID)) {
				$episodeID = "";
			}
			//$episodeName = htmlentities($child['title'], ENT_QUOTES);
			$episodeAirDate = $child['first_aired'];
			$episodeAirDate = strtotime($episodeAirDate);
			$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
			if (new DateTime() < new DateTime($episodeAirDate)) {
				$unaired = true;
			}
			if ($child['episode']['number'] == 1) {
				$episodePremier = "true";
			} else {
				$episodePremier = "false";
				$date = new DateTime($episodeAirDate);
				$date->add(new DateInterval("PT1S"));
				$date->format(DateTime::ATOM);
				$child['first_aired'] = gmdate('Y-m-d\TH:i:s\Z', strtotime($date->format(DateTime::ATOM)));
			}
			$downloaded = 0;
			$monitored = 0;
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
			$bottomTitle = 'S' . sprintf("%02d", $child['episode']['season']) . 'E' . sprintf("%02d", $child['episode']['number']) . ' - ' . $child['episode']['title'];
			$details = array(
				"seasonCount" => $child['episode']['season'],
				"status" => 'dunno',
				"topTitle" => $seriesName,
				"bottomTitle" => $bottomTitle,
				"overview" => isset($child['episode']['overview']) ? $child['episode']['overview'] : '',
				"runtime" => isset($child['episode']['runtime']) ? $child['episode']['runtime'] : '',
				"image" => $fanart,
				"ratings" => isset($child['show']['rating']) ? $child['show']['rating'] : '',
				"videoQuality" => "unknown",
				"audioChannels" => "unknown",
				"audioCodec" => "unknown",
				"videoCodec" => "unknown",
				"size" => "unknown",
				"genres" => isset($child['show']['genres']) ? $child['show']['genres'] : '',
			);
			array_push($gotCalendar, array(
				"id" => "Trakt-Tv-" . $i,
				"title" => $seriesName,
				"start" => $child['first_aired'],
				"className" => "inline-popups bg-calendar calendar-item get-tmdb-image tmdb-tv tmdbID--" . $seriesID,
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
	
	public function formatTraktCalendarMovies($array)
	{
		$gotCalendar = array();
		$i = 0;
		foreach ($array as $child) {
			$i++;
			$movieName = $child['movie']['title'];
			$movieID = $child['movie']['ids']['tmdb'];
			if (!isset($movieID)) {
				$movieID = '';
			}
			$physicalRelease = (isset($child['movie']['released']) ? $child['movie']['released'] : null);
			//$backupRelease = (isset($child['info']['release_date']['theater']) ? $child['info']['release_date']['theater'] : null);
			//$physicalRelease = (isset($physicalRelease) ? $physicalRelease : $backupRelease);
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date('Y-m-d', $physicalRelease);
			$oldestDay = new DateTime ($this->currentTime);
			$oldestDay->modify('-' . $this->config['calendarStart'] . ' days');
			$newestDay = new DateTime ($this->currentTime);
			$newestDay->modify('+' . $this->config['calendarEnd'] . ' days');
			$startDt = new DateTime ($physicalRelease);
			if (new DateTime() < $startDt) {
				$notReleased = 'true';
			} else {
				$notReleased = 'false';
			}
			$downloaded = 'text-dark';
			$banner = '/plugins/images/cache/no-np.png';
			$details = array(
				'topTitle' => $movieName,
				'bottomTitle' => $child['movie']['tagline'],
				'status' => $child['movie']['status'],
				'overview' => $child['movie']['overview'],
				'runtime' => $child['movie']['runtime'],
				'image' => $banner,
				'ratings' => isset($child['movie']['rating']) ? $child['movie']['rating'] : '',
				'videoQuality' => 'unknown',
				'audioChannels' => '',
				'audioCodec' => '',
				'videoCodec' => '',
				'genres' => $child['movie']['genres'],
				'year' => isset($child['movie']['year']) ? $child['movie']['year'] : '',
				'studio' => isset($child['movie']['year']) ? $child['movie']['year'] : '',
			);
			array_push($gotCalendar, array(
				'id' => 'Trakt-Movie-' . $i,
				'title' => $movieName,
				'start' => $physicalRelease,
				'className' => 'inline-popups bg-calendar calendar-item get-tmdb-image tmdb-movie tmdbID--' . $movieID,
				'imagetype' => 'film ' . $downloaded,
				'imagetypeFilter' => 'film',
				'downloadFilter' => $downloaded,
				'bgColor' => str_replace('text', 'bg', $downloaded),
				'details' => $details
			));
		}
		if ($i != 0) {
			return $gotCalendar;
		}
		return [];
	}
}