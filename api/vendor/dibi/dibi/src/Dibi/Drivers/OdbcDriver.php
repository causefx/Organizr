<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The driver interacting with databases via ODBC connections.
 *
 * Driver options:
 *   - dsn => driver specific DSN
 *   - username (or user)
 *   - password (or pass)
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - microseconds (bool) => use microseconds in datetime format?
 */
class OdbcDriver implements Dibi\Driver
{
	use Dibi\Strict;

	/** @var resource */
	private $connection;

	/** @var int|null  Affected rows */
	private $affectedRows;

	/** @var bool */
	private $microseconds = true;


	/** @throws Dibi\NotSupportedException */
	public function __construct(array $config)
	{
		if (!extension_loaded('odbc')) {
			throw new Dibi\NotSupportedException("PHP extension 'odbc' is not loaded.");
		}

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];
		} else {
			// default values
			$config += [
				'username' => ini_get('odbc.default_user'),
				'password' => ini_get('odbc.default_pw'),
				'dsn' => ini_get('odbc.default_db'),
			];

			$this->connection = empty($config['persistent'])
				? @odbc_connect($config['dsn'], $config['username'] ?? '', $config['password'] ?? '') // intentionally @
				: @odbc_pconnect($config['dsn'], $config['username'] ?? '', $config['password'] ?? ''); // intentionally @
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg() . ' ' . odbc_error());
		}

		if (isset($config['microseconds'])) {
			$this->microseconds = (bool) $config['microseconds'];
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@odbc_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = @odbc_exec($this->connection, $sql); // intentionally @

		if ($res === false) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection), 0, $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = Dibi\Helpers::false2Null(odbc_num_rows($res));
			return odbc_num_fields($res)
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
		throw new Dibi\NotSupportedException('ODBC does not support autoincrementing.');
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(?string $savepoint = null): void
	{
		if (!odbc_autocommit($this->connection, PHP_VERSION_ID < 80000 ? 0 : false)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(?string $savepoint = null): void
	{
		if (!odbc_commit($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}

		odbc_autocommit($this->connection, PHP_VERSION_ID < 80000 ? 1 : true);
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(?string $savepoint = null): void
	{
		if (!odbc_rollback($this->connection)) {
			throw new Dibi\DriverException(odbc_errormsg($this->connection) . ' ' . odbc_error($this->connection));
		}

		odbc_autocommit($this->connection, PHP_VERSION_ID < 80000 ? 1 : true);
	}


	/**
	 * Is in transaction?
	 */
	public function inTransaction(): bool
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
	 */
	public function getReflector(): Dibi\Reflector
	{
		return new OdbcReflector($this);
	}


	/**
	 * Result set driver factory.
	 * @param  resource  $resource
	 */
	public function createResultDriver($resource): OdbcResult
	{
		return new OdbcResult($resource);
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeIdentifier(string $value): string
	{
		return '[' . str_replace(['[', ']'], ['[[', ']]'], $value) . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $value->format('#m/d/Y#');
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return $value->format($this->microseconds ? '#m/d/Y H:i:s.u#' : '#m/d/Y H:i:s#');
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
		if ($offset) {
			throw new Dibi\NotSupportedException('Offset is not supported by this database.');

		} elseif ($limit < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null) {
			$sql = 'SELECT TOP ' . $limit . ' * FROM (' . $sql . ') t';
		}
	}
}
