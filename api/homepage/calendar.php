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