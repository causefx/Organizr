<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Profiler & logger event.
 */
class Event
{
	use Strict;

	/** event type */
	const CONNECT = 1,
		SELECT = 4,
		INSERT = 8,
		DELETE = 16,
		UPDATE = 32,
		QUERY = 60, // SELECT | INSERT | DELETE | UPDATE
		BEGIN = 64,
		COMMIT = 128,
		ROLLBACK = 256,
		TRANSACTION = 448, // BEGIN | COMMIT | ROLLBACK
		ALL = 1023;

	/** @var Connection */
	public $connection;

	/** @var int */
	public $type;

	/** @var string */
	public $sql;

	/** @var Result|DriverException|null */
	public $result;

	/** @var float */
	public $time;

	/** @var int */
	public $count;

	/** @var array */
	public $source;


	public function __construct(Connection $connection, $type, $sql = null)
	{
		$this->connection = $connection;
		$this->type = $type;
		$this->sql = trim($sql);
		$this->time = -microtime(true);

		if ($type === self::QUERY && preg_match('#\(?\s*(SELECT|UPDATE|INSERT|DELETE)#iA', $this->sql, $matches)) {
			static $types = [
				'SELECT' => self::SELECT, 'UPDATE' => self::UPDATE,
				'INSERT' => self::INSERT, 'DELETE' => self::DELETE,
			];
			$this->type = $types[strtoupper($matches[1])];
		}

		$rc = new \ReflectionClass('dibi');
		$dibiDir = dirname($rc->getFileName()) . DIRECTORY_SEPARATOR;
		foreach (debug_backtrace(false) as $row) {
			if (isset($row['file']) && is_file($row['file']) && strpos($row['file'], $dibiDir) !== 0) {
				$this->source = [$row['file'], (int) $row['line']];
				break;
			}
		}

		\dibi::$elapsedTime = false;
		\dibi::$numOfQueries++;
		\dibi::$sql = $sql;
	}


	public function done($result = null)
	{
		$this->result = $result;
		try {
			$this->count = $result instanceof Result ? count($result) : null;
		} catch (Exception $e) {
			$this->count = null;
		}

		$this->time += microtime(true);
		\dibi::$elapsedTime = $this->time;
		\dibi::$totalTime += $this->time;
		return $this;
	}
}
