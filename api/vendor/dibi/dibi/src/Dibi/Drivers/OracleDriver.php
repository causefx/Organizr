<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for Oracle database.
 *
 * Driver options:
 *   - database => the name of the local Oracle instance or the name of the entry in tnsnames.ora
 *   - username (or user)
 *   - password (or pass)
 *   - charset => character encoding to set
 *   - schema => alters session schema
 *   - nativeDate => use native date format (defaults to false)
 *   - resource (resource) => existing connection resource
 *   - persistent => Creates persistent connections with oci_pconnect instead of oci_new_connect
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class OracleDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var bool */
	private $autocommit = true;

	/** @var string  Date and datetime format */
	private $fmtDate;
	private $fmtDateTime;

	/** @var int|false Number of affected rows */
	private $affectedRows = false;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('oci8')) {
			throw new Dibi\NotSupportedException("PHP extension 'oci8' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @return void
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config)
	{
		$foo = &$config['charset'];

		if (isset($config['formatDate']) || isset($config['formatDateTime'])) {
			trigger_error('OracleDriver: options formatDate and formatDateTime are deprecated.', E_USER_DEPRECATED);
		}
		if (empty($config['nativeDate'])) {
			$this->fmtDate = isset($config['formatDate']) ? $config['formatDate'] : 'U';
			$this->fmtDateTime = isset($config['formatDateTime']) ? $config['formatDateTime'] : 'U';
		}

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];
		} elseif (empty($config['persistent'])) {
			$this->connection = @oci_new_connect($config['username'], $config['password'], $config['database'], $config['charset']); // intentionally @
		} else {
			$this->connection = @oci_pconnect($config['username'], $config['password'], $config['database'], $config['charset']); // intentionally @
		}

		if (!$this->connection) {
			$err = oci_error();
			throw new Dibi\DriverException($err['message'], $err['code']);
		}

		if (isset($config['schema'])) {
			$this->query('ALTER SESSION SET CURRENT_SCHEMA=' . $config['schema']);
		}
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		@oci_close($this->connection); // @ - connection can be already disconnected
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
		$res = oci_parse($this->connection, $sql);
		if ($res) {
			@oci_execute($res, $this->autocommit ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT);
			$err = oci_error($res);
			if ($err) {
				throw self::createException($err['message'], $err['code'], $sql);

			} elseif (is_resource($res)) {
				$this->affectedRows = oci_num_rows($res);
				return oci_num_fields($res) ? $this->createResultDriver($res) : null;
			}
		} else {
			$err = oci_error($this->connection);
			throw new Dibi\DriverException($err['message'], $err['code'], $sql);
		}
		return null;
	}


	/**
	 * @return Dibi\DriverException
	 */
	public static function createException($message, $code, $sql)
	{
		if (in_array($code, [1, 2299, 38911], true)) {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} elseif (in_array($code, [1400], true)) {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif (in_array($code, [2266, 2291, 2292], true)) {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} else {
			return new Dibi\DriverException($message, $code, $sql);
		}
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
		$row = $this->query("SELECT $sequence.CURRVAL AS ID FROM DUAL")->fetch(true);
		return isset($row['ID']) ? Dibi\Helpers::intVal($row['ID']) : false;
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function begin($savepoint = null)
	{
		$this->autocommit = false;
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function commit($savepoint = null)
	{
		if (!oci_commit($this->connection)) {
			$err = oci_error($this->connection);
			throw new Dibi\DriverException($err['message'], $err['code']);
		}
		$this->autocommit = true;
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function rollback($savepoint = null)
	{
		if (!oci_rollback($this->connection)) {
			$err = oci_error($this->connection);
			throw new Dibi\DriverException($err['message'], $err['code']);
		}
		$this->autocommit = true;
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
		return "'" . str_replace("'", "''", $value) . "'"; // TODO: not tested
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeBinary($value)
	{
		return "'" . str_replace("'", "''", $value) . "'"; // TODO: not tested
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeIdentifier($value)
	{
		// @see http://download.oracle.com/docs/cd/B10500_01/server.920/a96540/sql_elements9a.htm
		return '"' . str_replace('"', '""', $value) . '"';
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
		return $this->fmtDate
			? $value->format($this->fmtDate)
			: "to_date('" . $value->format('Y-m-d') . "', 'YYYY-mm-dd')";
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
		return $this->fmtDateTime
			? $value->format($this->fmtDateTime)
			: "to_date('" . $value->format('Y-m-d G:i:s') . "', 'YYYY-mm-dd hh24:mi:ss')";
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	public function escapeLike($value, $pos)
	{
		$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\\%_");
		$value = str_replace("'", "''", $value);
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

		} elseif ($offset) {
			// see http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
			$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t '
				. ($limit !== null ? 'WHERE ROWNUM <= ' . ((int) $offset + (int) $limit) : '')
				. ') WHERE "__rnum" > ' . $offset;

		} elseif ($limit !== null) {
			$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . Dibi\Helpers::intVal($limit);
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
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		return oci_fetch_array($this->resultSet, ($assoc ? OCI_ASSOC : OCI_NUM) | OCI_RETURN_NULLS);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  true on success, false if unable to seek to specified record
	 */
	public function seek($row)
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		oci_free_statement($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = oci_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$type = oci_field_type($this->resultSet, $i);
			$columns[] = [
				'name' => oci_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => oci_field_name($this->resultSet, $i),
				'nativetype' => $type === 'NUMBER' && oci_field_scale($this->resultSet, $i) === 0 ? 'INTEGER' : $type,
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
		$res = $this->query('SELECT * FROM cat');
		$tables = [];
		while ($row = $res->fetch(false)) {
			if ($row[1] === 'TABLE' || $row[1] === 'VIEW') {
				$tables[] = [
					'name' => $row[0],
					'view' => $row[1] === 'VIEW',
				];
			}
		}
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$res = $this->query('SELECT * FROM "ALL_TAB_COLUMNS" WHERE "TABLE_NAME" = ' . $this->escapeText($table));
		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[] = [
				'table' => $row['TABLE_NAME'],
				'name' => $row['COLUMN_NAME'],
				'nativetype' => $row['DATA_TYPE'],
				'size' => isset($row['DATA_LENGTH']) ? $row['DATA_LENGTH'] : null,
				'nullable' => $row['NULLABLE'] === 'Y',
				'default' => $row['DATA_DEFAULT'],
				'vendor' => $row,
			];
		}
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
