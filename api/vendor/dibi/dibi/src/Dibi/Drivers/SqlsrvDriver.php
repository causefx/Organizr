<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Helpers;


/**
 * The driver for Microsoft SQL Server and SQL Azure databases.
 *
 * Driver options:
 *   - host => the MS SQL server host name. It can also include a port number (hostname:port)
 *   - username (or user)
 *   - password (or pass)
 *   - database => the database name to select
 *   - options (array) => connection options {@link https://msdn.microsoft.com/en-us/library/cc296161(SQL.90).aspx}
 *   - charset => character encoding to set (default is UTF-8)
 *   - resource (resource) => existing connection resource
 */
class SqlsrvDriver implements Dibi\Driver
{
	use Dibi\Strict;

	/** @var resource */
	private $connection;

	/** @var int|null  Affected rows */
	private $affectedRows;

	/** @var string */
	private $version = '';


	/** @throws Dibi\NotSupportedException */
	public function __construct(array $config)
	{
		if (!extension_loaded('sqlsrv')) {
			throw new Dibi\NotSupportedException("PHP extension 'sqlsrv' is not loaded.");
		}

		Helpers::alias($config, 'options|UID', 'username');
		Helpers::alias($config, 'options|PWD', 'password');
		Helpers::alias($config, 'options|Database', 'database');
		Helpers::alias($config, 'options|CharacterSet', 'charset');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];
			if (!is_resource($this->connection)) {
				throw new \InvalidArgumentException("Configuration option 'resource' is not resource.");
			}
		} else {
			$options = $config['options'];

			// Default values
			$options['CharacterSet'] = $options['CharacterSet'] ?? 'UTF-8';
			$options['PWD'] = (string) $options['PWD'];
			$options['UID'] = (string) $options['UID'];
			$options['Database'] = (string) $options['Database'];

			sqlsrv_configure('WarningsReturnAsErrors', 0);
			$this->connection = sqlsrv_connect($config['host'], $options);
			if (!is_resource($this->connection)) {
				$info = sqlsrv_errors(SQLSRV_ERR_ERRORS);
				throw new Dibi\DriverException($info[0]['message'], $info[0]['code']);
			}

			sqlsrv_configure('WarningsReturnAsErrors', 1);
		}

		$this->version = sqlsrv_server_info($this->connection)['SQLServerVersion'];
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@sqlsrv_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = sqlsrv_query($this->connection, $sql);

		if ($res === false) {
			$info = sqlsrv_errors();
			throw new Dibi\DriverException($info[0]['message'], $info[0]['code'], $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = Helpers::false2Null(sqlsrv_rows_affected($res));
			return sqlsrv_num_fields($res)
				? $this->createResultDriver($res)
				: null;
		}

		return null;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	public function getAffectedRows(): ?int
	{
		return $this->affectedRows;
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	public function getInsertId(?string $sequence): ?int
	{
		$res = sqlsrv_query($this->connection, 'SELECT SCOPE_IDENTITY()');
		if (is_resource($res)) {
			$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_NUMERIC);
			return Dibi\Helpers::intVal($row[0]);
		}

		return null;
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(?string $savepoint = null): void
	{
		sqlsrv_begin_transaction($this->connection);
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(?string $savepoint = null): void
	{
		sqlsrv_commit($this->connection);
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(?string $savepoint = null): void
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
	 */
	public function getReflector(): Dibi\Reflector
	{
		return new SqlsrvReflector($this);
	}


	/**
	 * Result set driver factory.
	 * @param  resource  $resource
	 */
	public function createResultDriver($resource): SqlsrvResult
	{
		return new SqlsrvResult($resource);
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		return "N'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		return '0x' . bin2hex($value);
	}


	public function escapeIdentifier(string $value): string
	{
		// @see https://msdn.microsoft.com/en-us/library/ms176027.aspx
		return '[' . str_replace(']', ']]', $value) . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $value->format("'Y-m-d'");
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return 'CONVERT(DATETIME2(7), ' . $value->format("'Y-m-d H:i:s.u'") . ')';
	}


	public function escapeDateInterval(\DateInterval $value): string
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = strtr($value, ["'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]']);
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'");
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
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
}
