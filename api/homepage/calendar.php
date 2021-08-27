<?php

trait CalendarHomepageItem
{
	public function calendarSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'iCal',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/calendar.png',
			'category' => 'HOMEPAGE',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageCalendarEnabled'),
					$this->settingsOption('auth', 'homepageCalendarAuth'),
					$this->settingsOption('multiple-url', 'calendariCal', ['label' => 'iCal URL\'s']),
				],
				'Misc Options' => [
					$this->settingsOption('calendar-start', 'calendarStart'),
					$this->settingsOption('calendar-end', 'calendarEnd'),
					$this->settingsOption('calendar-starting-day', 'calendarFirstDay'),
					$this->settingsOption('calendar-default-view', 'calendarDefault'),
					$this->settingsOption('calendar-time-format', 'calendarTimeFormat'),
					$this->settingsOption('calendar-locale', 'calendarLocale'),
					$this->settingsOption('calendar-limit', 'calendarLimit'),
					$this->settingsOption('refresh', 'calendarRefresh'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function calendarHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageCalendarEnabled'
				],
				'auth' => [
					'homepageCalendarAuth'
				],
				'not_empty' => [
					'calendariCal'
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
	
	public function homepageOrdercalendar()
	{
		if (
			$this->homepageItemPermissions($this->sonarrHomepagePermissions('calendar')) ||
			$this->homepageItemPermissions($this->radarrHomepagePermissions('calendar')) ||
			$this->homepageItemPermissions($this->lidarrHomepagePermissions('calendar')) ||
			$this->homepageItemPermissions($this->sickrageHomepagePermissions('calendar')) ||
			$this->homepageItemPermissions($this->couchPotatoHomepagePermissions('calendar')) ||
			$this->homepageItemPermissions($this->traktHomepagePermissions('calendar')) ||
			$this->homepageItemPermissions($this->calendarHomepagePermissions('main'))
		) {
			return '
				<div id="' . __FUNCTION__ . '">
					<div id="calendar" class="fc fc-ltr m-b-30"></div>
					<script>
						// Calendar
						homepageCalendar("' . $this->config['calendarRefresh'] . '");
						// End Calendar
					</script>
					</div>
				';
		}
	}
	
	public function loadCalendarJS()
	{
		$locale = ($this->config['calendarLocale'] !== 'en') ?? false;
		return ($locale) ? '<script src="plugins/bower_components/calendar/dist/lang-all.js"></script>' : '';
	}
	
	public function getCalendar()
	{
		$startDate = date('Y-m-d', strtotime("-" . $this->config['calendarStart'] . " days"));
		$endDate = date('Y-m-d', strtotime("+" . $this->config['calendarEnd'] . " days"));
		$icalCalendarSources = array();
		$calendarItems = array();
		// SONARR CONNECT
		$items = $this->getSonarrCalendar($startDate, $endDate);
		$calendarItems = is_array($items) ? array_merge($calendarItems, $items) : $calendarItems;
		unset($items);
		// LIDARR CONNECT
		$items = $this->getLidarrCalendar($startDate, $endDate);
		$calendarItems = is_array($items) ? array_merge($calendarItems, $items) : $calendarItems;
		unset($items);
		// RADARR CONNECT
		$items = $this->getRadarrCalendar($startDate, $endDate);
		$calendarItems = is_array($items) ? array_merge($calendarItems, $items) : $calendarItems;
		unset($items);
		// SICKRAGE/BEARD/MEDUSA CONNECT
		$items = $this->getSickRageCalendar();
		$calendarItems = is_array($items) ? array_merge($calendarItems, $items) : $calendarItems;
		unset($items);
		// COUCHPOTATO CONNECT
		$items = $this->getCouchPotatoCalendar();
		$calendarItems = is_array($items) ? array_merge($calendarItems, $items) : $calendarItems;
		unset($items);
		// TRAKT CONNECT
		$items = $this->getTraktCalendar();
		$calendarItems = is_array($items) ? array_merge($calendarItems, $items) : $calendarItems;
		unset($items);
		// iCal URL
		$calendarSources['ical'] = $this->getICalendar();
		unset($items);
		// Finish
		$calendarSources['events'] = $calendarItems;
		$this->setAPIResponse('success', null, 200, $calendarSources);
		return $calendarSources;
	}
}