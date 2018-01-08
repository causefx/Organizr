<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;
use SQLite3;


/**
 * The dibi driver for SQLite3 database.
 *
 * Driver options:
 *   - database (or file) => the filename of the SQLite3 database
 *   - formatDate => how to format date in SQL (@see date)
 *   - formatDateTime => how to format datetime in SQL (@see date)
 *   - dbcharset => database character encoding (will be converted to 'charset')
 *   - charset => character encoding to set (default is UTF-8)
 *   - resource (SQLite3) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class Sqlite3Driver implements Dibi\Driver, Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var SQLite3|null */
	private $connection;

	/** @var \SQLite3Result|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var string  Date and datetime format */
	private $fmtDate;
	private $fmtDateTime;

	/** @var string  character encoding */
	private $dbcharset;
	private $charset;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('sqlite3')) {
			throw new Dibi\NotSupportedException("PHP extension 'sqlite3' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @return void
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config)
	{
		Dibi\Helpers::alias($config, 'database', 'file');
		$this->fmtDate = isset($config['formatDate']) ? $config['formatDate'] : 'U';
		$this->fmtDateTime = isset($config['formatDateTime']) ? $config['formatDateTime'] : 'U';

		if (isset($config['resource']) && $config['resource'] instanceof SQLite3) {
			$this->connection = $config['resource'];
		} else {
			try {
				$this->connection = new SQLite3($config['database']);
			} catch (\Exception $e) {
				throw new Dibi\DriverException($e->getMessage(), $e->getCode());
			}
		}

		$this->dbcharset = empty($config['dbcharset']) ? 'UTF-8' : $config['dbcharset'];
		$this->charset = empty($config['charset']) ? 'UTF-8' : $config['charset'];
		if (strcasecmp($this->dbcharset, $this->charset) === 0) {
			$this->dbcharset = $this->charset = null;
		}

		// enable foreign keys support (defaultly disabled; if disabled then foreign key constraints are not enforced)
		$version = SQLite3::version();
		if ($version['versionNumber'] >= '3006019') {
			$this->query('PRAGMA foreign_keys = ON');
		}
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		$this->connection->close();
	}


	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return Dibi\ResultDriver|null
	 * @throws Dibi\DriverException
	 */
	public function query($sql)
	{
		if ($this->dbcharset !== null) {
			$sql = iconv($this->charset, $this->dbcharset . '//IGNORE', $sql);
		}

		$res = @$this->connection->query($sql); // intentionally @
		if ($code = $this->connection->lastErrorCode()) {
			throw self::createException($this->connection->lastErrorMsg(), $code, $sql);

		} elseif ($res instanceof \SQLite3Result && $res->numColumns()) {
			return $this->createResultDriver($res);
		}
		return null;
	}


	/**
	 * @return Dibi\DriverException
	 */
	public static function createException($message, $code, $sql)
	{
		if ($code !== 19) {
			return new Dibi\DriverException($message, $code, $sql);

		} elseif (strpos($message, 'must be unique') !== false
			|| strpos($message, 'is not unique') !== false
			|| strpos($message, 'UNIQUE constraint failed') !== false
		) {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} elseif (strpos($message, 'may not be NULL') !== false
			|| strpos($message, 'NOT NULL constraint failed') !== false
		) {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif (strpos($message, 'foreign key constraint failed') !== false
			|| strpos($message, 'FOREIGN KEY constraint failed') !== false
		) {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} else {
			return new Dibi\ConstraintViolationException($message, $code, $sql);
		}
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|false  number of rows or false on error
	 */
	public function getAffectedRows()
	{
		return $this->connection->changes();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|false  int on success or false on failure
	 */
	public function getInsertId($sequence)
	{
		return $this->connection->lastInsertRowID();
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function begin($savepoint = null)
	{
		$this->query($savepoint ? "SAVEPOINT $savepoint" : 'BEGIN');
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function commit($savepoint = null)
	{
		$this->query($savepoint ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function rollback($savepoint = null)
	{
		$this->query($savepoint ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK');
	}


	/**
	 * Returns the connection resource.
	 * @return SQLite3
	 */
	public function getResource()
	{
		return $this->connection;
	}


	/**
	 * Returns the connection reflector.
	 * @return Dibi\Reflector
	 */
	public function getReflector()
	{
		return new SqliteReflector($this);
	}


	/**
	 * Result set driver factory.
	 * @param  \SQLite3Result
	 * @return Dibi\ResultDriver
	 */
	public function createResultDriver(\SQLite3Result $resource)
	{
		$res = clone $this;
		$res->resultSet = $resource;
		return $res;
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 * @param  string    value
	 * @return string    encoded value
	 */
	public function escapeText($value)
	{
		return "'" . $this->connection->escapeString($value) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeBinary($value)
	{
		return "X'" . bin2hex((string) $value) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeIdentifier($value)
	{
		return '[' . strtr($value, '[]', '  ') . ']';
	}


	/**
	 * @param  bool
	 * @return string
	 */
	public function escapeBool($value)
	{
		return $value ? '1' : '0';
	}


	/**
	 * @param  \DateTime|\DateTimeInterface|string|int
	 * @return string
	 */
	public function escapeDate($value)
	{
		if (!$value instanceof \DateTime && !$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->fmtDate);
	}


	/**
	 * @param  \DateTime|\DateTimeInterface|string|int
	 * @return string
	 */
	public function escapeDateTime($value)
	{
		if (!$value instanceof \DateTime && !$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format($this->fmtDateTime);
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	public function escapeLike($value, $pos)
	{
		$value = addcslashes($this->connection->escapeString($value), '%_\\');
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'") . " ESCAPE '\\'";
	}


	/**
	 * Decodes data from result set.
	 * @param  string
	 * @return string
	 */
	public function unescapeBinary($value)
	{
		return $value;
	}


	/** @deprecated */
	public function escape($value, $type)
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
		return Dibi\Helpers::escape($this, $value, $type);
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @param  string
	 * @param  int|null
	 * @param  int|null
	 * @return void
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null || $offset) {
			$sql .= ' LIMIT ' . ($limit === null ? '-1' : Dibi\Helpers::intVal($limit))
				. ($offset ? ' OFFSET ' . Dibi\Helpers::intVal($offset) : '');
		}
	}


	/********************* result set ****************d*g**/


	/**
	 * Automatically frees the resources allocated for this result set.
	 * @return void
	 */
	public function __destruct()
	{
		$this->autoFree && $this->resultSet && @$this->free();
	}


	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 * @throws Dibi\NotSupportedException
	 */
	public function getRowCount()
	{
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		$row = $this->resultSet->fetchArray($assoc ? SQLITE3_ASSOC : SQLITE3_NUM);
		$charset = $this->charset === null ? null : $this->charset . '//TRANSLIT';
		if ($row && ($assoc || $charset)) {
			$tmp = [];
			foreach ($row as $k => $v) {
				if ($charset !== null && is_string($v)) {
					$v = iconv($this->dbcharset, $charset, $v);
				}
				$tmp[str_replace(['[', ']'], '', $k)] = $v;
			}
			return $tmp;
		}
		return $row;
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  true on success, false if unable to seek to specified record
	 * @throws Dibi\NotSupportedException
	 */
	public function seek($row)
	{
		throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		$this->resultSet->finalize();
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = $this->resultSet->numColumns();
		$columns = [];
		static $types = [SQLITE3_INTEGER => 'int', SQLITE3_FLOAT => 'float', SQLITE3_TEXT => 'text', SQLITE3_BLOB => 'blob', SQLITE3_NULL => 'null'];
		for ($i = 0; $i < $count; $i++) {
			$columns[] = [
				'name' => $this->resultSet->columnName($i),
				'table' => null,
				'fullname' => $this->resultSet->columnName($i),
				'nativetype' => $types[$this->resultSet->columnType($i)],
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return \SQLite3Result|null
	 */
	public function getResultResource()
	{
		$this->autoFree = false;
		return $this->resultSet;
	}


	/********************* user defined functions ****************d*g**/


	/**
	 * Registers an user defined function for use in SQL statements.
	 * @param  string  function name
	 * @param  mixed   callback
	 * @param  int     num of arguments
	 * @return void
	 */
	public function registerFunction($name, callable $callback, $numArgs = -1)
	{
		$this->connection->createFunction($name, $callback, $numArgs);
	}


	/**
	 * Registers an aggregating user defined function for use in SQL statements.
	 * @param  string  function name
	 * @param  mixed   callback called for each row of the result set
	 * @param  mixed   callback called to aggregate the "stepped" data from each row
	 * @param  int     num of arguments
	 * @return void
	 */
	public function registerAggregateFunction($name, callable $rowCallback, callable $agrCallback, $numArgs = -1)
	{
		$this->connection->createAggregate($name, $rowCallback, $agrCallback, $numArgs);
	}
}
