<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver interacting with databases via ODBC connections.
 *
 * Driver options:
 *   - dsn => driver specific DSN
 *   - username (or user)
 *   - password (or pass)
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class OdbcDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var int|false  Affected rows */
	private $affectedRows = false;

	/** @var int  Cursor */
	private $row = 0;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('odbc')) {
			throw new Dibi\NotSupportedException("PHP extension 'odbc' is not loaded.");
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
			$config += [
				'username' => ini_get('odbc.default_user'),
				'password' => ini_get('odbc.default_pw'),
				'dsn' => ini_get('odbc.default_db'),
			];

			if (empty($config['persistent'])) {
				$this->connection = @odbc_connect($config['dsn'], $config['username'], $config['password']); // intentionally @
			} else {
				$this->connection = @odbc_pconnect($config['dsn'], $config['username'], $config['password']); // intentionally @
			}
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg() . ' ' . odbc_error());
		}
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		@odbc_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return Dibi\ResultDriver|null
	 * @throws Dibi\DriverException
	 */
	public function query($sql)
	{
		$this->affectedRows = false;
		$res = @odbc_exec($this->connection, $sql); // intentionally @

		if ($res === false) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection), 0, $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = odbc_num_rows($res);
			return odbc_num_fields($res) ? $this->createResultDriver($res) : null;
		}
		return null;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|false  number of rows or false on error
	 */
	public function getAffectedRows()
	{
		return $this->affectedRows;
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|false  int on success or false on failure
	 */
	public function getInsertId($sequence)
	{
		throw new Dibi\NotSupportedException('ODBC does not support autoincrementing.');
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function begin($savepoint = null)
	{
		if (!odbc_autocommit($this->connection, false)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function commit($savepoint = null)
	{
		if (!odbc_commit($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
		odbc_autocommit($this->connection, true);
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function rollback($savepoint = null)
	{
		if (!odbc_rollback($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
		odbc_autocommit($this->connection, true);
	}


	/**
	 * Is in transaction?
	 * @return bool
	 */
	public function inTransaction()
	{
		return !odbc_autocommit($this->connection);
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
		return $this;
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
		return $value->format('#m/d/Y#');
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
		return $value->format('#m/d/Y H:i:s.u#');
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
		// will return -1 with many drivers :-(
		return odbc_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		if ($assoc) {
			return odbc_fetch_array($this->resultSet, ++$this->row);
		} else {
			$set = $this->resultSet;
			if (!odbc_fetch_row($set, ++$this->row)) {
				return false;
			}
			$count = odbc_num_fields($set);
			$cols = [];
			for ($i = 1; $i <= $count; $i++) {
				$cols[] = odbc_result($set, $i);
			}
			return $cols;
		}
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  true on success, false if unable to seek to specified record
	 */
	public function seek($row)
	{
		$this->row = $row;
		return true;
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		odbc_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = odbc_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$columns[] = [
				'name' => odbc_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => odbc_field_name($this->resultSet, $i),
				'nativetype' => odbc_field_type($this->resultSet, $i),
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


	/********************* Dibi\Reflector ****************d*g**/


	/**
	 * Returns list of tables.
	 * @return array
	 */
	public function getTables()
	{
		$res = odbc_tables($this->connection);
		$tables = [];
		while ($row = odbc_fetch_array($res)) {
			if ($row['TABLE_TYPE'] === 'TABLE' || $row['TABLE_TYPE'] === 'VIEW') {
				$tables[] = [
					'name' => $row['TABLE_NAME'],
					'view' => $row['TABLE_TYPE'] === 'VIEW',
				];
			}
		}
		odbc_free_result($res);
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$res = odbc_columns($this->connection);
		$columns = [];
		while ($row = odbc_fetch_array($res)) {
			if ($row['TABLE_NAME'] === $table) {
				$columns[] = [
					'name' => $row['COLUMN_NAME'],
					'table' => $table,
					'nativetype' => $row['TYPE_NAME'],
					'size' => $row['COLUMN_SIZE'],
					'nullable' => (bool) $row['NULLABLE'],
					'default' => $row['COLUMN_DEF'],
				];
			}
		}
		odbc_free_result($res);
		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		throw new Dibi\NotImplementedException;
	}
}
