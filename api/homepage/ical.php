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
			case('MST'):
			case('Mountain Time'):
			case('Mountain Standard Time'):
				$timezone = 'America/Denver';
				break;
			case('PST'):
			case('Pacific Time'):
			case('Pacific Standard Time'):
				$timezone = 'America/Los_Angeles';
				break;
			case('AKST'):
			case('Alaska Time'):
			case('Alaska Standard Time'):
				$timezone = 'America/Anchorage';
				break;
			case('HST'):
			case('Hawaii Time'):
			case('Hawaii Standard Time'):
				$timezone = 'Pacific/Honolulu';
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
			case('JST'):
			case('Japan Time'):
			case('Japan Standard Time'):
				$timezone = 'Asia/Tokyo';
				break;
			case('WET'):
			case('WEST'):
			case('Western European Time'):
			case('Western European Standard Time'):
			case('Western European Summer Time'):
			case('W. Europe Time'):
			case('W. Europe Standard Time'):
			case('W. Europe Summer Time'):
				$timezone = 'Europe/Lisbon';
				break;
		}
		return $timezone;
	}

	public function getCalendarExtraDates($start, $rule, $timezone)
	{
		$extraDates = [];
		try {
			if (stripos($rule, 'FREQ') !== false) {
				$until = $this->getCalenderRepeatUntil($rule);
				$start = new DateTime ($start);
				$startDate = new DateTime ($this->currentTime);
				$startDate->setTime($start->format('H'), $start->format('i'));
				$startDate->modify('-' . $this->config['calendarStart'] . ' days');
				$endDate = new DateTime ($this->currentTime);
				$endDate->modify('+' . $this->config['calendarEnd'] . ' days');
				$start = (stripos($rule, 'BYDAY') !== false || stripos($rule, 'BYMONTHDAY') !== false || stripos($rule, 'DAILY') !== false) ? $startDate : $start;
				$until = $until ? new DateTime($until) : $endDate;
				$dates = new \Recurr\Rule(trim($rule));
				$dates->setStartDate($start)->setUntil($until);
				$transformer = new \Recurr\Transformer\ArrayTransformer();
				$transformerConfig = new \Recurr\Transformer\ArrayTransformerConfig();
				$transformerConfig->enableLastDayOfMonthFix();
				$transformer->setConfig($transformerConfig);
				foreach (@$transformer->transform($dates) as $key => $date) {
					if ($date->getStart() >= $startDate) {
						$extraDates[$key]['start'] = $date->getStart();
						$extraDates[$key]['end'] = $date->getEnd();
					}
				}
			}
		} catch (\Recurr\Exception\InvalidRRule | \Recurr\Exception\InvalidWeekday | Exception $e) {
			return $extraDates;
		}
		return $extraDates;
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

	/* function is to avoid the elements which is not having the proper start, end and summary information */
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
			$dates = [];
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
						$repeat = $icsEvent ['RRULE'] ?? false;
						if (!$originalTimeZone) {
							$tzKey = array_keys($startKeys);
							if (strpos($tzKey[0], 'TZID=') !== false) {
								$originalTimeZone = explode('TZID=', (string)$tzKey[0]);
								$originalTimeZone = (count($originalTimeZone) >= 2) ? str_replace('"', '', $originalTimeZone[1]) : false;
								$originalTimeZone = stripos($originalTimeZone, ';') !== false ? explode(';', $originalTimeZone)[0] : $originalTimeZone;
							}
						}
						$start = reset($startKeys);
						$end = reset($endKeys);
						$oldestDay = new DateTime ($this->currentTime);
						$oldestDay->modify('-' . $this->config['calendarStart'] . ' days');
						$newestDay = new DateTime ($this->currentTime);
						$newestDay->modify('+' . $this->config['calendarEnd'] . ' days');
						if ($repeat) {
							$dates = $this->getCalendarExtraDates($start, $icsEvent['RRULE'], $originalTimeZone);
						} else {
							$dates[] = [
								'start' => new DateTime ($start),
								'end' => new DateTime ($end)
							];
							if ($oldestDay > new DateTime ($end)) {
								continue;
							}
						}
						foreach ($dates as $eventDate) {
							/* Converting to datetime and apply the timezone to get proper date time */
							$startDt = $eventDate['start'];
							/* Getting end date with time */
							$endDt = $eventDate['end'];
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
								if ((int)$diff >= 0) {
									$startDt->modify('+ ' . $diff . ' hour');
									$endDt->modify('+ ' . $diff . ' hour');
								}
							}
							$startDt->setTimeZone(new DateTimezone ($timeZone));
							$endDt->setTimeZone(new DateTimezone ($timeZone));
							$startDate = $startDt->format(DateTime::ATOM);
							$endDate = $endDt->format(DateTime::ATOM);
							$dates = isset($icsEvent['RRULE']) ? $dates : null;
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
							if ($startDt->format('H') == 0 && $startDt->format('i') == 0) {
								$startDate = $startDt->format('Y-m-d');
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
