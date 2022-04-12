<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Helpers;
use PgSql;


/**
 * The driver for PostgreSQL database.
 *
 * Driver options:
 *   - host, hostaddr, port, dbname, user, password, connect_timeout, options, sslmode, service => see PostgreSQL API
 *   - string => or use connection string
 *   - schema => the schema search path
 *   - charset => character encoding to set (default is utf8)
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - connect_type (int) => see pg_connect()
 */
class PostgreDriver implements Dibi\Driver
{
	use Dibi\Strict;

	/** @var resource|PgSql\Connection */
	private $connection;

	/** @var int|null  Affected rows */
	private $affectedRows;


	/** @throws Dibi\NotSupportedException */
	public function __construct(array $config)
	{
		if (!extension_loaded('pgsql')) {
			throw new Dibi\NotSupportedException("PHP extension 'pgsql' is not loaded.");
		}

		$error = null;
		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			$config += [
				'charset' => 'utf8',
			];
			if (isset($config['string'])) {
				$string = $config['string'];
			} else {
				$string = '';
				Helpers::alias($config, 'user', 'username');
				Helpers::alias($config, 'dbname', 'database');
				foreach (['host', 'hostaddr', 'port', 'dbname', 'user', 'password', 'connect_timeout', 'options', 'sslmode', 'service'] as $key) {
					if (isset($config[$key])) {
						$string .= $key . '=' . $config[$key] . ' ';
					}
				}
			}

			$connectType = $config['connect_type'] ?? PGSQL_CONNECT_FORCE_NEW;

			set_error_handler(function (int $severity, string $message) use (&$error) {
				$error = $message;
			});
			$this->connection = empty($config['persistent'])
				? pg_connect($string, $connectType)
				: pg_pconnect($string, $connectType);
			restore_error_handler();
		}

		if (!is_resource($this->connection) && !$this->connection instanceof PgSql\Connection) {
			throw new Dibi\DriverException($error ?: 'Connecting error.');
		}

		pg_set_error_verbosity($this->connection, PGSQL_ERRORS_VERBOSE);

		if (isset($config['charset']) && pg_set_client_encoding($this->connection, $config['charset'])) {
			throw static::createException(pg_last_error($this->connection));
		}

		if (isset($config['schema'])) {
			$this->query('SET search_path TO "' . implode('", "', (array) $config['schema']) . '"');
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@pg_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Pings database.
	 */
	public function ping(): bool
	{
		return pg_ping($this->connection);
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = @pg_query($this->connection, $sql); // intentionally @

		if ($res === false) {
			throw static::createException(pg_last_error($this->connection), null, $sql);

		} elseif (is_resource($res) || $res instanceof PgSql\Result) {
			$this->affectedRows = Helpers::false2Null(pg_affected_rows($res));
			if (pg_num_fields($res)) {
				return $this->createResultDriver($res);
			}
		}

		return null;
	}


	public static function createException(string $message, $code = null, ?string $sql = null): Dibi\DriverException
	{
		if ($code === null && preg_match('#^ERROR:\s+(\S+):\s*#', $message, $m)) {
			$code = $m[1];
			$message = substr($message, strlen($m[0]));
		}

		if ($code === '0A000' && strpos($message, 'truncate') !== false) {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} elseif ($code === '23502') {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif ($code === '23503') {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} elseif ($code === '23505') {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} else {
			return new Dibi\DriverException($message, $code, $sql);
		}
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
		$res = $sequence === null
			? $this->query('SELECT LASTVAL()') // PostgreSQL 8.1 is needed
			: $this->query("SELECT CURRVAL('$sequence')");

		if (!$res) {
			return null;
		}

		$row = $res->fetch(false);
		return is_array($row) ? (int) $row[0] : null;
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(?string $savepoint = null): void
	{
		$this->query($savepoint ? "SAVEPOINT {$this->escapeIdentifier($savepoint)}" : 'START TRANSACTION');
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(?string $savepoint = null): void
	{
		$this->query($savepoint ? "RELEASE SAVEPOINT {$this->escapeIdentifier($savepoint)}" : 'COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(?string $savepoint = null): void
	{
		$this->query($savepoint ? "ROLLBACK TO SAVEPOINT {$this->escapeIdentifier($savepoint)}" : 'ROLLBACK');
	}


	/**
	 * Is in transaction?
	 */
	public function inTransaction(): bool
	{
		return !in_array(pg_transaction_status($this->connection), [PGSQL_TRANSACTION_UNKNOWN, PGSQL_TRANSACTION_IDLE], true);
	}


	/**
	 * Returns the connection resource.
	 * @return resource|null
	 */
	public function getResource()
	{
		return is_resource($this->connection) || $this->connection instanceof PgSql\Connection
			? $this->connection
			: null;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Dibi\Reflector
	{
		return new PostgreReflector($this, pg_parameter_status($this->connection, 'server_version'));
	}


	/**
	 * Result set driver factory.
	 * @param  resource  $resource
	 */
	public function createResultDriver($resource): PostgreResult
	{
		return new PostgreResult($resource);
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		if (!$this->getResource()) {
			throw new Dibi\Exception('Lost connection to server.');
		}

		return "'" . pg_escape_string($this->connection, $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		if (!$this->getResource()) {
			throw new Dibi\Exception('Lost connection to server.');
		}

		return "'" . pg_escape_bytea($this->connection, $value) . "'";
	}


	public function escapeIdentifier(string $value): string
	{
		// @see http://www.postgresql.org/docs/8.2/static/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
		return '"' . str_replace('"', '""', $value) . '"';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? 'TRUE' : 'FALSE';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $value->format("'Y-m-d'");
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return $value->format("'Y-m-d H:i:s.u'");
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
		$bs = pg_escape_string($this->connection, '\\'); // standard_conforming_strings = on/off
		$value = pg_escape_string($this->connection, $value);
		$value = strtr($value, ['%' => $bs . '%', '_' => $bs . '_', '\\' => '\\\\']);
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'");
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');
		}

		if ($limit !== null) {
			$sql .= ' LIMIT ' . $limit;
		}

		if ($offset) {
			$sql .= ' OFFSET ' . $offset;
		}
	}
}
