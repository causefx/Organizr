<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;
use Dibi\Connection;
use Dibi\Helpers;


/**
 * The dibi driver for Microsoft SQL Server and SQL Azure databases.
 *
 * Driver options:
 *   - host => the MS SQL server host name. It can also include a port number (hostname:port)
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - options (array) => connection options {@link https://msdn.microsoft.com/en-us/library/cc296161(SQL.90).aspx}
 *   - charset => character encoding to set (default is UTF-8)
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class SqlsrvDriver implements Dibi\Driver, Dibi\ResultDriver
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

	/** @var string */
	private $version = '';


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('sqlsrv')) {
			throw new Dibi\NotSupportedException("PHP extension 'sqlsrv' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @return void
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config)
	{
		Helpers::alias($config, 'options|UID', 'username');
		Helpers::alias($config, 'options|PWD', 'password');
		Helpers::alias($config, 'options|Database', 'database');
		Helpers::alias($config, 'options|CharacterSet', 'charset');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			$options = $config['options'];

			// Default values
			if (!isset($options['CharacterSet'])) {
				$options['CharacterSet'] = 'UTF-8';
			}
			$options['PWD'] = (string) $options['PWD'];
			$options['UID'] = (string) $options['UID'];
			$options['Database'] = (string) $options['Database'];

			$this->connection = sqlsrv_connect($config['host'], $options);
		}

		if (!is_resource($this->connection)) {
			$info = sqlsrv_errors();
			throw new Dibi\DriverException($info[0]['message'], $info[0]['code']);
		}
		$this->version = sqlsrv_server_info($this->connection)['SQLServerVersion'];
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		@sqlsrv_close($this->connection); // @ - connection can be already disconnected
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
		$res = sqlsrv_query($this->connection, $sql);

		if ($res === false) {
			$info = sqlsrv_errors();
			throw new Dibi\DriverException($info[0]['message'], $info[0]['code'], $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = sqlsrv_rows_affected($res);
			return sqlsrv_num_fields($res) ? $this->createResultDriver($res) : null;
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
		$res = sqlsrv_query($this->connection, 'SELECT SCOPE_IDENTITY()');
		if (is_resource($res)) {
			$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_NUMERIC);
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
		sqlsrv_begin_transaction($this->connection);
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function commit($savepoint = null)
	{
		sqlsrv_commit($this->connection);
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\DriverException
	 */
	public function rollback($savepoint = null)
	{
		sqlsrv_rollback($this->connection);
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
		return new SqlsrvReflector($this);
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
		return '[' . str_replace(']', ']]', $value) . ']';
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
		return Helpers::escape($this, $value, $type);
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

		} elseif (version_compare($this->version, '11', '<')) { // 11 == SQL Server 2012
			if ($offset) {
				throw new Dibi\NotSupportedException('Offset is not supported by this database.');

			} elseif ($limit !== null) {
				$sql = sprintf('SELECT TOP (%d) * FROM (%s) t', $limit, $sql);
			}

		} elseif ($limit !== null) {
			// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
			$sql = sprintf('%s OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', rtrim($sql), $offset, $limit);
		} elseif ($offset) {
			// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
			$sql = sprintf('%s OFFSET %d ROWS', rtrim($sql), $offset);
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
		return sqlsrv_fetch_array($this->resultSet, $assoc ? SQLSRV_FETCH_ASSOC : SQLSRV_FETCH_NUMERIC);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  true on success, false if unable to seek to specified record
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
		sqlsrv_free_stmt($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$columns = [];
		foreach ((array) sqlsrv_field_metadata($this->resultSet) as $fieldMetadata) {
			$columns[] = [
				'name' => $fieldMetadata['Name'],
				'fullname' => $fieldMetadata['Name'],
				'nativetype' => $fieldMetadata['Type'],
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
