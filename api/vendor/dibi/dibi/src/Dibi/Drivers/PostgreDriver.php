<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for PostgreSQL database.
 *
 * Driver options:
 *   - host, hostaddr, port, dbname, user, password, connect_timeout, options, sslmode, service => see PostgreSQL API
 *   - string => or use connection string
 *   - schema => the schema search path
 *   - charset => character encoding to set (default is utf8)
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class PostgreDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
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


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('pgsql')) {
			throw new Dibi\NotSupportedException("PHP extension 'pgsql' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @return void
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config)
	{
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
				Dibi\Helpers::alias($config, 'user', 'username');
				Dibi\Helpers::alias($config, 'dbname', 'database');
				foreach (['host', 'hostaddr', 'port', 'dbname', 'user', 'password', 'connect_timeout', 'options', 'sslmode', 'service'] as $key) {
					if (isset($config[$key])) {
						$string .= $key . '=' . $config[$key] . ' ';
					}
				}
			}

			set_error_handler(function ($severity, $message) use (&$error) {
				$error = $message;
			});
			if (empty($config['persistent'])) {
				$this->connection = pg_connect($string, PGSQL_CONNECT_FORCE_NEW);
			} else {
				$this->connection = pg_pconnect($string, PGSQL_CONNECT_FORCE_NEW);
			}
			restore_error_handler();
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException($error ?: 'Connecting error.');
		}

		pg_set_error_verbosity($this->connection, PGSQL_ERRORS_VERBOSE);

		if (isset($config['charset']) && pg_set_client_encoding($this->connection, $config['charset'])) {
			throw self::createException(pg_last_error($this->connection));
		}

		if (isset($config['schema'])) {
			$this->query('SET search_path TO "' . implode('", "', (array) $config['schema']) . '"');
		}
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	public function disconnect()
	{
		@pg_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Pings database.
	 * @return bool
	 */
	public function ping()
	{
		return pg_ping($this->connection);
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
		$res = @pg_query($this->connection, $sql); // intentionally @

		if ($res === false) {
			throw self::createException(pg_last_error($this->connection), null, $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = pg_affected_rows($res);
			if (pg_num_fields($res)) {
				return $this->createResultDriver($res);
			}
		}
		return null;
	}


	/**
	 * @return Dibi\DriverException
	 */
	public static function createException($message, $code = null, $sql = null)
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
		if ($sequence === null) {
			// PostgreSQL 8.1 is needed
			$res = $this->query('SELECT LASTVAL()');
		} else {
			$res = $this->query("SELECT CURRVAL('$sequence')");
		}

		if (!$res) {
			return false;
		}

		$row = $res->fetch(false);
		return is_array($row) ? $row[0] : false;
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
	 * Is in transaction?
	 * @return bool
	 */
	public function inTransaction()
	{
		return !in_array(pg_transaction_status($this->connection), [PGSQL_TRANSACTION_UNKNOWN, PGSQL_TRANSACTION_IDLE], true);
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
		if (!is_resource($this->connection)) {
			throw new Dibi\Exception('Lost connection to server.');
		}
		return "'" . pg_escape_string($this->connection, $value) . "'";
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
		return "'" . pg_escape_bytea($this->connection, $value) . "'";
	}


	/**
	 * @param  string
	 * @return string
	 */
	public function escapeIdentifier($value)
	{
		// @see http://www.postgresql.org/docs/8.2/static/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
		return '"' . str_replace('"', '""', $value) . '"';
	}


	/**
	 * @param  bool
	 * @return string
	 */
	public function escapeBool($value)
	{
		return $value ? 'TRUE' : 'FALSE';
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
		$bs = pg_escape_string($this->connection, '\\'); // standard_conforming_strings = on/off
		$value = pg_escape_string($this->connection, $value);
		$value = strtr($value, ['%' => $bs . '%', '_' => $bs . '_', '\\' => '\\\\']);
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
	}


	/**
	 * Decodes data from result set.
	 * @param  string
	 * @return string
	 */
	public function unescapeBinary($value)
	{
		return pg_unescape_bytea($value);
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
		}
		if ($limit !== null) {
			$sql .= ' LIMIT ' . Dibi\Helpers::intVal($limit);
		}
		if ($offset) {
			$sql .= ' OFFSET ' . Dibi\Helpers::intVal($offset);
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
		return pg_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($assoc)
	{
		return pg_fetch_array($this->resultSet, null, $assoc ? PGSQL_ASSOC : PGSQL_NUM);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @param  int   the 0-based cursor pos to seek to
	 * @return bool  true on success, false if unable to seek to specified record
	 */
	public function seek($row)
	{
		return pg_result_seek($this->resultSet, $row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	public function free()
	{
		pg_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @return array
	 */
	public function getResultColumns()
	{
		$count = pg_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = [
				'name' => pg_field_name($this->resultSet, $i),
				'table' => pg_field_table($this->resultSet, $i),
				'nativetype' => pg_field_type($this->resultSet, $i),
			];
			$row['fullname'] = $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'];
			$columns[] = $row;
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
		$version = pg_parameter_status($this->getResource(), 'server_version');
		if ($version < 7.4) {
			throw new Dibi\DriverException('Reflection requires PostgreSQL 7.4 and newer.');
		}

		$query = "
			SELECT
				table_name AS name,
				CASE table_type
					WHEN 'VIEW' THEN 1
					ELSE 0
				END AS view
			FROM
				information_schema.tables
			WHERE
				table_schema = ANY (current_schemas(false))";

		if ($version >= 9.3) {
			$query .= '
				UNION ALL
				SELECT
					matviewname, 1
				FROM
					pg_matviews
				WHERE
					schemaname = ANY (current_schemas(false))';
		}

		$res = $this->query($query);
		$tables = pg_fetch_all($res->resultSet);
		return $tables ? $tables : [];
	}


	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$_table = $this->escapeText($this->escapeIdentifier($table));
		$res = $this->query("
			SELECT indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid AND pg_index.indisprimary
			WHERE pg_class.oid = $_table::regclass
		");
		$primary = (int) pg_fetch_object($res->resultSet)->indkey;

		$res = $this->query("
			SELECT *
			FROM information_schema.columns c
			JOIN pg_class ON pg_class.relname = c.table_name
			JOIN pg_namespace nsp ON nsp.oid = pg_class.relnamespace AND nsp.nspname = c.table_schema
			WHERE pg_class.oid = $_table::regclass
			ORDER BY c.ordinal_position
		");

		if (!$res->getRowCount()) {
			$res = $this->query("
				SELECT
					a.attname AS column_name,
					pg_type.typname AS udt_name,
					a.attlen AS numeric_precision,
					a.atttypmod-4 AS character_maximum_length,
					NOT a.attnotnull AS is_nullable,
					a.attnum AS ordinal_position,
					adef.adsrc AS column_default
				FROM
					pg_attribute a
					JOIN pg_type ON a.atttypid = pg_type.oid
					JOIN pg_class cls ON a.attrelid = cls.oid
					LEFT JOIN pg_attrdef adef ON adef.adnum = a.attnum AND adef.adrelid = a.attrelid
				WHERE
					cls.relkind IN ('r', 'v', 'mv')
					AND a.attrelid = $_table::regclass
					AND a.attnum > 0
					AND NOT a.attisdropped
				ORDER BY ordinal_position
			");
		}

		$columns = [];
		while ($row = $res->fetch(true)) {
			$size = (int) max($row['character_maximum_length'], $row['numeric_precision']);
			$columns[] = [
				'name' => $row['column_name'],
				'table' => $table,
				'nativetype' => strtoupper($row['udt_name']),
				'size' => $size > 0 ? $size : null,
				'nullable' => $row['is_nullable'] === 'YES' || $row['is_nullable'] === 't',
				'default' => $row['column_default'],
				'autoincrement' => (int) $row['ordinal_position'] === $primary && substr($row['column_default'], 0, 7) === 'nextval',
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
		$_table = $this->escapeText($this->escapeIdentifier($table));
		$res = $this->query("
			SELECT
				a.attnum AS ordinal_position,
				a.attname AS column_name
			FROM
				pg_attribute a
				JOIN pg_class cls ON a.attrelid = cls.oid
			WHERE
				a.attrelid = $_table::regclass
				AND a.attnum > 0
				AND NOT a.attisdropped
			ORDER BY ordinal_position
		");

		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[$row['ordinal_position']] = $row['column_name'];
		}

		$res = $this->query("
			SELECT pg_class2.relname, indisunique, indisprimary, indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid
			INNER JOIN pg_class as pg_class2 on pg_class2.oid = pg_index.indexrelid
			WHERE pg_class.oid = $_table::regclass
		");

		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['relname']]['name'] = $row['relname'];
			$indexes[$row['relname']]['unique'] = $row['indisunique'] === 't';
			$indexes[$row['relname']]['primary'] = $row['indisprimary'] === 't';
			foreach (explode(' ', $row['indkey']) as $index) {
				$indexes[$row['relname']]['columns'][] = $columns[$index];
			}
		}
		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		$_table = $this->escapeText($this->escapeIdentifier($table));

		$res = $this->query("
			SELECT
				c.conname AS name,
				lt.attname AS local,
				c.confrelid::regclass AS table,
				ft.attname AS foreign,

				CASE c.confupdtype
					WHEN 'a' THEN 'NO ACTION'
					WHEN 'r' THEN 'RESTRICT'
					WHEN 'c' THEN 'CASCADE'
					WHEN 'n' THEN 'SET NULL'
					WHEN 'd' THEN 'SET DEFAULT'
					ELSE 'UNKNOWN'
				END AS \"onUpdate\",

				CASE c.confdeltype
					WHEN 'a' THEN 'NO ACTION'
					WHEN 'r' THEN 'RESTRICT'
					WHEN 'c' THEN 'CASCADE'
					WHEN 'n' THEN 'SET NULL'
					WHEN 'd' THEN 'SET DEFAULT'
					ELSE 'UNKNOWN'
				END AS \"onDelete\",

				c.conkey,
				lt.attnum AS lnum,
				c.confkey,
				ft.attnum AS fnum
			FROM
				pg_constraint c
				JOIN pg_attribute lt ON c.conrelid = lt.attrelid AND lt.attnum = ANY (c.conkey)
				JOIN pg_attribute ft ON c.confrelid = ft.attrelid AND ft.attnum = ANY (c.confkey)
			WHERE
				c.contype = 'f'
				AND
				c.conrelid = $_table::regclass
		");

		$fKeys = $references = [];
		while ($row = $res->fetch(true)) {
			if (!isset($fKeys[$row['name']])) {
				$fKeys[$row['name']] = [
					'name' => $row['name'],
					'table' => $row['table'],
					'local' => [],
					'foreign' => [],
					'onUpdate' => $row['onUpdate'],
					'onDelete' => $row['onDelete'],
				];

				$l = explode(',', trim($row['conkey'], '{}'));
				$f = explode(',', trim($row['confkey'], '{}'));

				$references[$row['name']] = array_combine($l, $f);
			}

			if (isset($references[$row['name']][$row['lnum']]) && $references[$row['name']][$row['lnum']] === $row['fnum']) {
				$fKeys[$row['name']]['local'][] = $row['local'];
				$fKeys[$row['name']]['foreign'][] = $row['foreign'];
			}
		}

		return $fKeys;
	}
}
