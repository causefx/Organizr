<?php

trait CalendarHomepageItem
{
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