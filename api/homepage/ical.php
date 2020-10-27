<?php

trait ICalHomepageItem
{
	public function calendarDaysCheck($entryStart, $entryEnd)
	{
		$success = false;
		$entryStart = intval($entryStart);
		$entryEnd = intval($entryEnd);
		if ($entryStart >= 0 && $entryEnd <= 0) {
			$success = true;
		}
		return $success;
	}
	
	public function calendarStandardizeTimezone($timezone)
	{
		switch ($timezone) {
			case('CST'):
			case('Central Time'):
			case('Central Standard Time'):
				$timezone = 'America/Chicago';
				break;
			case('CET'):
			case('Central European Time'):
				$timezone = 'Europe/Berlin';
				break;
			case('EST'):
			case('Eastern Time'):
			case('Eastern Standard Time'):
				$timezone = 'America/New_York';
				break;
			case('PST'):
			case('Pacific Time'):
			case('Pacific Standard Time'):
				$timezone = 'America/Los_Angeles';
				break;
			case('China Time'):
			case('China Standard Time'):
				$timezone = 'Asia/Beijing';
				break;
			case('IST'):
			case('India Time'):
			case('India Standard Time'):
				$timezone = 'Asia/New_Delhi';
				break;
			case('JST');
			case('Japan Time'):
			case('Japan Standard Time'):
				$timezone = 'Asia/Tokyo';
				break;
		}
		return $timezone;
	}
	
	public function getCalenderRepeat($value)
	{
		//FREQ=DAILY
		//RRULE:FREQ=WEEKLY;BYDAY=TH
		$first = explode('=', $value);
		if (count($first) > 1) {
			$second = explode(';', $first[1]);
		} else {
			return $value;
		}
		if ($second) {
			return $second[0];
		} else {
			return $first[1];
		}
	}
	
	public function getCalenderRepeatUntil($value)
	{
		$first = explode('UNTIL=', $value);
		if (count($first) > 1) {
			if (strpos($first[1], ';') !== false) {
				$check = explode(';', $first[1]);
				return $check[0];
			} else {
				return $first[1];
			}
		} else {
			return false;
		}
	}
	
	public function getCalenderRepeatCount($value)
	{
		$first = explode('COUNT=', $value);
		if (count($first) > 1) {
			return $first[1];
		} else {
			return false;
		}
	}
	
	public function file_get_contents_curl($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	public function getIcsEventsAsArray($file)
	{
		$icalString = $this->file_get_contents_curl($file);
		$icsDates = array();
		/* Explode the ICs Data to get datas as array according to string ‘BEGIN:’ */
		$icsData = explode("BEGIN:", $icalString);
		/* Iterating the icsData value to make all the start end dates as sub array */
		foreach ($icsData as $key => $value) {
			$icsDatesMeta [$key] = explode("\n", $value);
		}
		/* Itearting the Ics Meta Value */
		foreach ($icsDatesMeta as $key => $value) {
			foreach ($value as $subKey => $subValue) {
				/* to get ics events in proper order */
				$icsDates = $this->getICSDates($key, $subKey, $subValue, $icsDates);
			}
		}
		return $icsDates;
	}
	
	/* funcion is to avaid the elements wich is not having the proper start, end  and summary informations */
	public function getICSDates($key, $subKey, $subValue, $icsDates)
	{
		if ($key != 0 && $subKey == 0) {
			$icsDates [$key] ["BEGIN"] = $subValue;
		} else {
			$subValueArr = explode(":", $subValue, 2);
			if (isset ($subValueArr [1])) {
				$icsDates [$key] [$subValueArr [0]] = $subValueArr [1];
			}
		}
		return $icsDates;
	}
	
	public function getICalendar()
	{
		if (!$this->config['homepageCalendarEnabled']) {
			$this->setAPIResponse('error', 'iCal homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageCalendarAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['calendariCal'])) {
			$this->setAPIResponse('error', 'iCal URL is not defined', 422);
			return false;
		}
		$calendarItems = array();
		$calendars = array();
		$calendarURLList = explode(',', $this->config['calendariCal']);
		$icalEvents = array();
		foreach ($calendarURLList as $key => $value) {
			$icsEvents = $this->getIcsEventsAsArray($value);
			if (isset($icsEvents) && !empty($icsEvents)) {
				$timeZone = isset($icsEvents [1] ['X-WR-TIMEZONE']) ? trim($icsEvents[1]['X-WR-TIMEZONE']) : date_default_timezone_get();
				$originalTimeZone = isset($icsEvents [1] ['X-WR-TIMEZONE']) ? str_replace('"', '', trim($icsEvents[1]['X-WR-TIMEZONE'])) : false;
				unset($icsEvents [1]);
				foreach ($icsEvents as $icsEvent) {
					$startKeys = $this->array_filter_key($icsEvent, function ($key) {
						return strpos($key, 'DTSTART') === 0;
					});
					$endKeys = $this->array_filter_key($icsEvent, function ($key) {
						return strpos($key, 'DTEND') === 0;
					});
					if (!empty($startKeys) && !empty($endKeys) && isset($icsEvent['SUMMARY'])) {
						/* Getting start date and time */
						$repeat = isset($icsEvent ['RRULE']) ? $icsEvent ['RRULE'] : false;
						if (!$originalTimeZone) {
							$tzKey = array_keys($startKeys);
							if (strpos($tzKey[0], 'TZID=') !== false) {
								$originalTimeZone = explode('TZID=', (string)$tzKey[0]);
								$originalTimeZone = (count($originalTimeZone) >= 2) ? str_replace('"', '', $originalTimeZone[1]) : false;
							}
						}
						$start = reset($startKeys);
						$end = reset($endKeys);
						$totalDays = $this->config['calendarStart'] + $this->config['calendarEnd'];
						if ($repeat) {
							$repeatOverride = $this->getCalenderRepeatCount(trim($icsEvent["RRULE"]));
							switch (trim(strtolower($this->getCalenderRepeat($repeat)))) {
								case 'daily':
									$repeat = ($repeatOverride) ? $repeatOverride : $totalDays;
									$term = 'days';
									break;
								case 'weekly':
									$repeat = ($repeatOverride) ? $repeatOverride : round($totalDays / 7);
									$term = 'weeks';
									break;
								case 'monthly':
									$repeat = ($repeatOverride) ? $repeatOverride : round($totalDays / 30);
									$term = 'months';
									break;
								case 'yearly':
									$repeat = ($repeatOverride) ? $repeatOverride : round($totalDays / 365);
									$term = 'years';
									break;
								default:
									$repeat = ($repeatOverride) ? $repeatOverride : $totalDays;
									$term = 'days';
									break;
							}
						} else {
							$repeat = 1;
							$term = 'day';
						}
						$calendarTimes = 0;
						while ($calendarTimes < $repeat) {
							$currentDate = new DateTime ($this->currentTime);
							$oldestDay = new DateTime ($this->currentTime);
							$oldestDay->modify('-' . $this->config['calendarStart'] . ' days');
							$newestDay = new DateTime ($this->currentTime);
							$newestDay->modify('+' . $this->config['calendarEnd'] . ' days');
							/* Converting to datetime and apply the timezone to get proper date time */
							$startDt = new DateTime ($start);
							/* Getting end date with time */
							$endDt = new DateTime ($end);
							if ($calendarTimes !== 0) {
								$dateDiff = date_diff($startDt, $currentDate);
								$startDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
								$startDt->modify('+' . $calendarTimes . ' ' . $term);
								$endDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
								$endDt->modify('+' . $calendarTimes . ' ' . $term);
							} elseif ($calendarTimes == 0 && $repeat !== 1) {
								$dateDiff = date_diff($startDt, $currentDate);
								$startDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
								$endDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
							}
							$calendarStartDiff = date_diff($startDt, $newestDay);
							$calendarEndDiff = date_diff($startDt, $oldestDay);
							if ($originalTimeZone && $originalTimeZone !== 'UTC' && (strpos($start, 'Z') == false)) {
								$originalTimeZone = $this->calendarStandardizeTimezone($originalTimeZone);
								$dateTimeOriginalTZ = new DateTimeZone($originalTimeZone);
								$dateTimeOriginal = new DateTime('now', $dateTimeOriginalTZ);
								$dateTimeUTCTZ = new DateTimeZone(date_default_timezone_get());
								$dateTimeUTC = new DateTime('now', $dateTimeUTCTZ);
								$dateTimeOriginalOffset = $dateTimeOriginal->getOffset() / 3600;
								$dateTimeUTCOffset = $dateTimeUTC->getOffset() / 3600;
								$diff = $dateTimeUTCOffset - $dateTimeOriginalOffset;
								$startDt->modify('+ ' . $diff . ' hour');
								$endDt->modify('+ ' . $diff . ' hour');
							}
							$startDt->setTimeZone(new DateTimezone ($timeZone));
							$endDt->setTimeZone(new DateTimezone ($timeZone));
							$startDate = $startDt->format(DateTime::ATOM);
							$endDate = $endDt->format(DateTime::ATOM);
							if (new DateTime() < $endDt) {
								$extraClass = 'text-info';
							} else {
								$extraClass = 'text-success';
							}
							/* Getting the name of event */
							$eventName = $icsEvent['SUMMARY'];
							if (!$this->calendarDaysCheck($calendarStartDiff->format('%R') . $calendarStartDiff->days, $calendarEndDiff->format('%R') . $calendarEndDiff->days)) {
								break;
							}
							if (isset($icsEvent["RRULE"]) && $this->getCalenderRepeatUntil(trim($icsEvent["RRULE"]))) {
								$untilDate = new DateTime ($this->getCalenderRepeatUntil(trim($icsEvent["RRULE"])));
								$untilDiff = date_diff($currentDate, $untilDate);
								if ($untilDiff->days > 0) {
									break;
								}
							}
							$icalEvents[] = array(
								'title' => $eventName,
								'imagetype' => 'calendar-o text-warning text-custom-calendar ' . $extraClass,
								'imagetypeFilter' => 'ical',
								'className' => 'bg-calendar calendar-item bg-custom-calendar',
								'start' => $startDate,
								'end' => $endDate,
								'bgColor' => str_replace('text', 'bg', $extraClass),
							);
							$calendarTimes = $calendarTimes + 1;
						}
					}
				}
			}
		}
		$calendarSources = $icalEvents;
		$this->setAPIResponse('success', null, 200, $calendarSources);
		return $calendarSources;
	}
}