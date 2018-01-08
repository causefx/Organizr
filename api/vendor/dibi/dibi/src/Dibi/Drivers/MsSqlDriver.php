<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for MS SQL database.
 *
 * Driver options:
 *   - host => the MS SQL server host name. It can also include a port number (hostname:port)
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class MsSqlDriver implements Dibi\Driver, Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('mssql')) {
			throw new Dibi\NotSupportedException("PHP extension 'mssql' is not loaded.");
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
		} elseif (empty($config['persistent'])) {
			$this->connection = @mssql_connect($config['host'], $config['username'], $config['password'], true); // intentionally @
		} else {
			$this->connection = @mssql_pconnect($config['host'], $config['username'], $config['password']); // intentionally @
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException("Can't connect to DB.");
		}

		if (isset($config['database']) && !@mssql_select_db($this->escapeIdentifier($config['database']), $this->connection)) { // intentionally @
			throw new Dibi\DriverException("Can't select DB '$config[database]'.");
		}
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		@mssql_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return Dibi\ResultDriver|null
	 * @throws Dibi\DriverException
	 */
	public function query($sql)
	{
		$res = @mssql_query($sql, $this->connection); // intentionally @

		if ($res === false) {
			throw new Dibi\DriverException(mssql_get_last_message(), 0, $sql);

		} elseif (is_resource($res)) {
			return $this->createResultDriver($res);
		}
		return null;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|false  number of rows or false on error
	 */
	public function getAffectedRows()
	{
		return mssql_rows_affected($this->connection);
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|false  int on success or false on failure
	 */
	public function getInsertId($sequence)
	{
		$res = mssql_query('SELECT @@IDENTITY', $this->connection);
		if (is_resource($res)) {
			$row = mssql_fetch_row($res);
			return $row[0];
		}
		return false;
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function begin($savepoint = null)
	{
		$this->query('BEGIN TRANSACTION');
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function commit($savepoint = null)
	{
		$this->query('COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function rollback($savepoint = null)
	{
		$this->query('ROLLBACK');
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
		return new MsSqlReflector($this);
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
		return "'" . str_replace("'", "''", $value) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeBinary($value)
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeIdentifier($value)
	{
		// @see https://msdn.microsoft.com/en-us/library/ms176027.aspx
		return '[' . str_replace(['[', ']'], ['[[', ']]'], $value) . ']';
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
		$value = strtr($value, ["'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]']);
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
		if ($offset) {
			throw new Dibi\NotSupportedException('Offset is not supported by this database.');

		} elseif ($limit < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null) {
			$sql = 'SELECT TOP ' . Dibi\Helpers::intVal($limit) . ' * FROM (' . $sql . ') t';
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
		return mssql_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		return mssql_fetch_array($this->resultSet, $assoc ? MSSQL_ASSOC : MSSQL_NUM);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return bool     true on success, false if unable to seek to specified record
	 */
	public function seek($row)
	{
		return mssql_data_seek($this->resultSet, $row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		mssql_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = mssql_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = (array) mssql_fetch_field($this->resultSet, $i);
			$columns[] = [
				'name' => $row['name'],
				'fullname' => $row['column_source'] ? $row['column_source'] . '.' . $row['name'] : $row['name'],
				'table' => $row['column_source'],
				'nativetype' => $row['type'],
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
