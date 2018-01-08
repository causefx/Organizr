<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Result set single row.
 */
class Row implements \ArrayAccess, \IteratorAggregate, \Countable
{
	public function __construct($arr)
	{
		foreach ($arr as $k => $v) {
			$this->$k = $v;
		}
	}


	public function toArray()
	{
		return (array) $this;
	}


	/**
	 * Converts value to DateTime object.
	 * @param  string key
	 * @param  string format
	 * @return \DateTime
	 */
	public function asDateTime($key, $format = null)
	{
		$time = $this[$key];
		if (!$time instanceof DateTime) {
			if (!$time || substr((string) $time, 0, 3) === '000') { // '', null, false, '0000-00-00', ...
				return null;
			}
			$time = new DateTime($time);
		}
		return $format === null ? $time : $time->format($format);
	}


	public function __get($key)
	{
		$hint = Helpers::getSuggestion(array_keys((array) $this), $key);
		trigger_error("Attempt to read missing column '$key'" . ($hint ? ", did you mean '$hint'?" : '.'), E_USER_NOTICE);
	}


	/********************* interfaces ArrayAccess, Countable & IteratorAggregate ****************d*g**/


	final public function count()
	{
		return count((array) $this);
	}


	final public function getIterator()
	{
		return new \ArrayIterator($this);
	}


	final public function offsetSet($nm, $val)
	{
		$this->$nm = $val;
	}


	final public function offsetGet($nm)
	{
		return $this->$nm;
	}


	final public function offsetExists($nm)
	{
		return isset($this->$nm);
	}


	final public function offsetUnset($nm)
	{
		unset($this->$nm);
	}
}
