<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * dibi common exception.
 */
class Exception extends \Exception
{
	/** @var string|null */
	private $sql;


	/**
	 * Construct a dibi exception.
	 * @param  string  Message describing the exception
	 * @param  mixed
	 * @param  string  SQL command
	 */
	public function __construct($message = '', $code = 0, $sql = null)
	{
		parent::__construct($message);
		$this->code = $code;
		$this->sql = $sql;
	}


	/**
	 * @return string  The SQL passed to the constructor
	 */
	final public function getSql()
	{
		return $this->sql;
	}


	/**
	 * @return string  string represenation of exception with SQL command
	 */
	public function __toString()
	{
		return parent::__toString() . ($this->sql ? "\nSQL: " . $this->sql : '');
	}
}


/**
 * database server exception.
 */
class DriverException extends Exception
{
}


/**
 * PCRE exception.
 */
class PcreException extends Exception
{
	public function __construct($message = '%msg.')
	{
		static $messages = [
			PREG_INTERNAL_ERROR => 'Internal error',
			PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit was exhausted',
			PREG_RECURSION_LIMIT_ERROR => 'Recursion limit was exhausted',
			PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
			5 => 'Offset didn\'t correspond to the begin of a valid UTF-8 code point', // PREG_BAD_UTF8_OFFSET_ERROR
		];
		$code = preg_last_error();
		parent::__construct(str_replace('%msg', isset($messages[$code]) ? $messages[$code] : 'Unknown error', $message), $code);
	}
}


class NotImplementedException extends Exception
{
}


class NotSupportedException extends Exception
{
}


/**
 * Database procedure exception.
 */
class ProcedureException extends Exception
{
	/** @var string */
	protected $severity;


	/**
	 * Construct the exception.
	 * @param  string  Message describing the exception
	 * @param  int     Some code
	 * @param  string SQL command
	 */
	public function __construct($message = null, $code = 0, $severity = null, $sql = null)
	{
		parent::__construct($message, (int) $code, $sql);
		$this->severity = $severity;
	}


	/**
	 * Gets the exception severity.
	 * @return string
	 */
	public function getSeverity()
	{
		$this->severity;
	}
}


/**
 * Base class for all constraint violation related exceptions.
 */
class ConstraintViolationException extends DriverException
{
}


/**
 * Exception for a foreign key constraint violation.
 */
class ForeignKeyConstraintViolationException extends ConstraintViolationException
{
}


/**
 * Exception for a NOT NULL constraint violation.
 */
class NotNullConstraintViolationException extends ConstraintViolationException
{
}


/**
 * Exception for a unique constraint violation.
 */
class UniqueConstraintViolationException extends ConstraintViolationException
{
}
