<?php

trait CalendarHomepageItem
{
	public function calendarSettingsArray()
	{
		return array(
			'name' => 'Calendar',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/calendar.png',
			'category' => 'HOMEPAGE',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageCalendarEnabled',
						'label' => 'Enable iCal',
						'value' => $this->config['homepageCalendarEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageCalendarAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageCalendarAuth'],
						'options' => $this->groupOptions
					),
					array(
						'type' => 'input',
						'name' => 'calendariCal',
						'label' => 'iCal URL\'s',
						'value' => $this->config['calendariCal'],
						'placeholder' => 'separate by comma\'s'
					),
				),
				'Misc Options' => array(
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
					)
				),
			)
		);
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
			$this->homepageItemPermissions($this->calendarHomepagePermissions('calendar'))
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
		// iCal URL
		$calendarSources['ical'] = $this->getICalendar();
		unset($items);
		// Finish
		$calendarSources['events'] = $calendarItems;
		$this->setAPIResponse('success', null, 200, $calendarSources);
		return $calendarSources;
	}
}