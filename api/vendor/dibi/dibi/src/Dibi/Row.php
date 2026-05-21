<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Result set single row.
 */
#[\AllowDynamicProperties]
class Row implements \ArrayAccess, \IteratorAggregate, \Countable
{
	public function __construct(array $arr)
	{
		foreach ($arr as $k => $v) {
			$this->$k = $v;
		}
	}


	public function toArray(): array
	{
		return (array) $this;
	}


	/**
	 * Converts value to DateTime object.
	 * @return DateTime|string|null
	 */
	public function asDateTime(string $key, ?string $format = null)
	{
		$time = $this[$key];
		if (!$time instanceof DateTime) {
			if (!$time || substr((string) $time, 0, 7) === '0000-00') { // '', null, false, '0000-00-00', ...
				return null;
			}

			$time = new DateTime($time);
		}

		return $format === null ? $time : $time->format($format);
	}


	public function __get(string $key)
	{
		$hint = Helpers::getSuggestion(array_keys((array) $this), $key);
		trigger_error("Attempt to read missing column '$key'" . ($hint ? ", did you mean '$hint'?" : '.'), E_USER_NOTICE);
	}


	public function __isset(string $key): bool
	{
		return false;
	}


	/********************* interfaces ArrayAccess, Countable & IteratorAggregate ****************d*g**/


	final public function count(): int
	{
		return count((array) $this);
	}


	final public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this);
	}


	final public function offsetSet($nm, $val): void
	{
		$this->$nm = $val;
	}


	#[\ReturnTypeWillChange]
	final public function offsetGet($nm)
	{
		return $this->$nm;
	}


	final public function offsetExists($nm): bool
	{
		return isset($this->$nm);
	}


	final public function offsetUnset($nm): void
	{
		unset($this->$nm);
	}
}
