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
	public function __construct($time = 'now', ?\DateTimeZone $timezone = null)
	{
		$timezone = $timezone ?: new \DateTimeZone(date_default_timezone_get());
		if (is_numeric($time)) {
			$tmp = (new self('@' . $time))->setTimezone($timezone);
			parent::__construct($tmp->format('Y-m-d H:i:s.u'), $tmp->getTimezone());
		} else {
			parent::__construct($time, $timezone);
		}
	}


	public function __toString(): string
	{
		return $this->format('Y-m-d H:i:s.u');
	}
}
