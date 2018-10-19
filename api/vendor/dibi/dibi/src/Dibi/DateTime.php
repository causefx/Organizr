<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * DateTime.
 */
class DateTime extends \DateTimeImmutable
{
	use Strict;

	/**
	 * @param  string|int  $time
	 */
	public function __construct($time = 'now', \DateTimeZone $timezone = null)
	{
		$timezone = $timezone ?: new \DateTimeZone(date_default_timezone_get());
		if (is_numeric($time)) {
			$tmp = (new self('@' . $time))->setTimezone($timezone);
			parent::__construct($tmp->format('Y-m-d H:i:s.u'), $tmp->getTimezone());
		} else {
			parent::__construct($time, $timezone);
		}
	}


	/** @deprecated  use modify() */
	public function modifyClone(string $modify = ''): self
	{
		trigger_error(__METHOD__ . '() is deprecated, use modify()', E_USER_DEPRECATED);
		$dolly = clone $this;
		return $modify ? $dolly->modify($modify) : $dolly;
	}


	public function __toString(): string
	{
		return $this->format('Y-m-d H:i:s.u');
	}


	/********************* immutable usage detector ****************d*g**/


	public function __destruct()
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		if (isset($trace[0]['file'], $trace[1]['function']) && $trace[0]['file'] === __FILE__ && $trace[1]['function'] !== '__construct') {
			trigger_error(__CLASS__ . ' is immutable now, check how it is used in ' . $trace[1]['file'] . ':' . $trace[1]['line'], E_USER_WARNING);
		}
	}


	public function add($interval)
	{
		return parent::add($interval);
	}


	public function modify($modify)
	{
		return parent::modify($modify);
	}


	public function setDate($year, $month, $day)
	{
		return parent::setDate($year, $month, $day);
	}


	public function setISODate($year, $week, $day = 1)
	{
		return parent::setISODate($year, $week, $day);
	}


	public function setTime($hour, $minute, $second = 0, $micro = 0)
	{
		return parent::setTime($hour, $minute, $second, $micro);
	}


	public function setTimestamp($unixtimestamp)
	{
		return parent::setTimestamp($unixtimestamp);
	}


	public function setTimezone($timezone)
	{
		return parent::setTimezone($timezone);
	}


	public function sub($interval)
	{
		return parent::sub($interval);
	}
}
