<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for MySQL database.
 *
 * Driver options:
 *   - host => the MySQL server host name
 *   - port (int) => the port number to attempt to connect to the MySQL server
 *   - socket => the socket or named pipe
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - flags (int) => driver specific constants (MYSQL_CLIENT_*)
 *   - charset => character encoding to set (default is utf8)
 *   - persistent (bool) => try to find a persistent link?
 *   - unbuffered (bool) => sends query without fetching and buffering the result rows automatically?
 *   - sqlmode => see http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class MySqlDriver implements Dibi\Driver, Dibi\ResultDriver
{
	use Dibi\Strict;

	const ERROR_ACCESS_DENIED = 1045;
	const ERROR_DUPLICATE_ENTRY = 1062;
	const ERROR_DATA_TRUNCATED = 1265;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var bool  Is buffered (seekable and countable)? */
	private $buffered;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('mysql')) {
			throw new Dibi\NotSupportedException("PHP extension 'mysql' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @return void
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config)
	{
		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			// default values
			Dibi\Helpers::alias($config, 'flags', 'options');
			$config += [
				'charset' => 'utf8',
				'timezone' => date('P'),
				'username' => ini_get('mysql.default_user'),
				'password' => ini_get('mysql.default_password'),
			];
			if (!isset($config['host'])) {
				$host = ini_get('mysql.default_host');
				if ($host) {
					$config['host'] = $host;
					$config['port'] = ini_get('mysql.default_port');
				} else {
					if (!isset($config['socket'])) {
						$config['socket'] = ini_get('mysql.default_socket');
					}
					$config['host'] = null;
				}
			}

			if (empty($config['socket'])) {
				$host = $config['host'] . (empty($config['port']) ? '' : ':' . $config['port']);
			} else {
				$host = ':' . $config['socket'];
			}

			if (empty($config['persistent'])) {
				$this->connection = @mysql_connect($host, $config['username'], $config['password'], true, $config['flags']); // intentionally @
			} else {
				$this->connection = @mysql_pconnect($host, $config['username'], $config['password'], $config['flags']); // intentionally @
			}
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException(mysql_error(), mysql_errno());
		}

		if (isset($config['charset'])) {
			if (!@mysql_set_charset($config['charset'], $this->connection)) { // intentionally @
				$this->query("SET NAMES '$config[charset]'");
			}
		}

		if (isset($config['database'])) {
			if (!@mysql_select_db($config['database'], $this->connection)) { // intentionally @
				throw new Dibi\DriverException(mysql_error($this->connection), mysql_errno($this->connection));
			}
		}

		if (isset($config['sqlmode'])) {
			$this->query("SET sql_mode='$config[sqlmode]'");
		}

		if (isset($config['timezone'])) {
			$this->query("SET time_zone='$config[timezone]'");
		}

		$this->buffered = empty($config['unbuffered']);
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		@mysql_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return Dibi\ResultDriver|null
	 * @throws Dibi\DriverException
	 */
	public function query($sql)
	{
		if ($this->buffered) {
			$res = @mysql_query($sql, $this->connection); // intentionally @
		} else {
			$res = @mysql_unbuffered_query($sql, $this->connection); // intentionally @
		}

		if ($code = mysql_errno($this->connection)) {
			throw MySqliDriver::createException(mysql_error($this->connection), $code, $sql);

		} elseif (is_resource($res)) {
			return $this->createResultDriver($res);
		}
	}


	/**
	 * Retrieves information about the most recently executed query.
	 * @return array
	 */
	public function getInfo()
	{
		$res = [];
		preg_match_all('#(.+?): +(\d+) *#', mysql_info($this->connection), $matches, PREG_SET_ORDER);
		if (preg_last_error()) {
			throw new Dibi\PcreException;
		}

		foreach ($matches as $m) {
			$res[$m[1]] = (int) $m[2];
		}
		return $res;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|false  number of rows or false on error
	 */
	public function getAffectedRows()
	{
		return mysql_affected_rows($this->connection);
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|false  int on success or false on failure
	 */
	public function getInsertId($sequence)
	{
		return mysql_insert_id($this->connection);
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function begin($savepoint = null)
	{
		$this->query($savepoint ? "SAVEPOINT $savepoint" : 'START TRANSACTION');
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
	 * @return resource|null
	 */
	public function getResource()
	{
		return is_resource($this->connection) ? $this->connection : null;
	}


	/**
	 * Returns the connection reflector.
	 * @return Dibi\Reflector
	 */
	public function getReflector()
	{
		return new MySqlReflector($this);
	}


	/**
	 * Result set driver factory.
	 * @param  resource
	 * @return Dibi\ResultDriver
	 */
	public function createResultDriver($resource)
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
		if (!is_resource($this->connection)) {
			throw new Dibi\Exception('Lost connection to server.');
		}
		return "'" . mysql_real_escape_string($value, $this->connection) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeBinary($value)
	{
		if (!is_resource($this->connection)) {
			throw new Dibi\Exception('Lost connection to server.');
		}
		return "_binary'" . mysql_real_escape_string($value, $this->connection) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeIdentifier($value)
	{
		// @see http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
		return '`' . str_replace('`', '``', $value) . '`';
	}


	/**
	 * @param  bool
	 * @return string
	 */
	public function escapeBool($value)
	{
		return $value ? 1 : 0;
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
		return $value->format("'Y-m-d'");
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
		return $value->format("'Y-m-d H:i:s.u'");
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	public function escapeLike($value, $pos)
	{
		$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\n\r\\'%_");
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
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
			// see http://dev.mysql.com/doc/refman/5.0/en/select.html
			$sql .= ' LIMIT ' . ($limit === null ? '18446744073709551615' : Dibi\Helpers::intVal($limit))
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
		$this->autoFree && $this->getResultResource() && $this->free();
	}


	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	public function getRowCount()
	{
		if (!$this->buffered) {
			throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
		}
		return mysql_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		return mysql_fetch_array($this->resultSet, $assoc ? MYSQL_ASSOC : MYSQL_NUM);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  true on success, false if unable to seek to specified record
	 * @throws Dibi\Exception
	 */
	public function seek($row)
	{
		if (!$this->buffered) {
			throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
		}

		return mysql_data_seek($this->resultSet, $row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		mysql_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = mysql_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = (array) mysql_fetch_field($this->resultSet, $i);
			$columns[] = [
				'name' => $row['name'],
				'table' => $row['table'],
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'nativetype' => strtoupper($row['type']),
				'type' => $row['type'] === 'time' ? Dibi\Type::TIME_INTERVAL : null,
				'vendor' => $row,
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return resource|null
	 */
	public function getResultResource()
	{
		$this->autoFree = false;
		return is_resource($this->resultSet) ? $this->resultSet : null;
	}
}
