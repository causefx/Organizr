<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

use Dibi\Type;


/**
 * This class is static container class for creating DB objects and
 * store connections info.
 */
class dibi
{
	use Dibi\Strict;

	const
		AFFECTED_ROWS = 'a',
		IDENTIFIER = 'n';

	/** version */
	const
		VERSION = '3.1.0',
		REVISION = 'released on 2017-09-25';

	/** sorting order */
	const
		ASC = 'ASC',
		DESC = 'DESC';

	/** @deprecated */
	const
		TEXT = Type::TEXT,
		BINARY = Type::BINARY,
		BOOL = Type::BOOL,
		INTEGER = Type::INTEGER,
		FLOAT = Type::FLOAT,
		DATE = Type::DATE,
		DATETIME = Type::DATETIME,
		TIME = Type::TIME,
		FIELD_TEXT = Type::TEXT,
		FIELD_BINARY = Type::BINARY,
		FIELD_BOOL = Type::BOOL,
		FIELD_INTEGER = Type::INTEGER,
		FIELD_FLOAT = Type::FLOAT,
		FIELD_DATE = Type::DATE,
		FIELD_DATETIME = Type::DATETIME,
		FIELD_TIME = Type::TIME;

	/** @var string  Last SQL command @see dibi::query() */
	public static $sql;

	/** @var int  Elapsed time for last query */
	public static $elapsedTime;

	/** @var int  Elapsed time for all queries */
	public static $totalTime;

	/** @var int  Number or queries */
	public static $numOfQueries = 0;

	/** @var string  Default dibi driver */
	public static $defaultDriver = 'mysqli';

	/** @var Dibi\Connection[]  Connection registry storage for DibiConnection objects */
	private static $registry = [];

	/** @var Dibi\Connection  Current connection */
	private static $connection;


	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException('Cannot instantiate static class ' . get_class($this));
	}


	/********************* connections handling ****************d*g**/


	/**
	 * Creates a new Connection object and connects it to specified database.
	 * @param  mixed   connection parameters
	 * @param  string  connection name
	 * @return Dibi\Connection
	 * @throws Dibi\Exception
	 */
	public static function connect($config = [], $name = '0')
	{
		return self::$connection = self::$registry[$name] = new Dibi\Connection($config, $name);
	}


	/**
	 * Disconnects from database (doesn't destroy Connection object).
	 * @return void
	 */
	public static function disconnect()
	{
		self::getConnection()->disconnect();
	}


	/**
	 * Returns true when connection was established.
	 * @return bool
	 */
	public static function isConnected()
	{
		return (self::$connection !== null) && self::$connection->isConnected();
	}


	/**
	 * Retrieve active connection.
	 * @param  string   connection registy name
	 * @return Dibi\Connection
	 * @throws Dibi\Exception
	 */
	public static function getConnection($name = null)
	{
		if ($name === null) {
			if (self::$connection === null) {
				throw new Dibi\Exception('Dibi is not connected to database.');
			}

			return self::$connection;
		}

		if (!isset(self::$registry[$name])) {
			throw new Dibi\Exception("There is no connection named '$name'.");
		}

		return self::$registry[$name];
	}


	/**
	 * Sets connection.
	 * @param  Dibi\Connection
	 * @return Dibi\Connection
	 */
	public static function setConnection(Dibi\Connection $connection)
	{
		return self::$connection = $connection;
	}


	/**
	 * @deprecated
	 */
	public static function activate($name)
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
		self::$connection = self::getConnection($name);
	}


	/********************* monostate for active connection ****************d*g**/


	/**
	 * Generates and executes SQL query - Monostate for Dibi\Connection::query().
	 * @param  array|mixed      one or more arguments
	 * @return Dibi\Result|int   result set or number of affected rows
	 * @throws Dibi\Exception
	 */
	public static function query($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args);
	}


	/**
	 * Executes the SQL query - Monostate for Dibi\Connection::nativeQuery().
	 * @param  string           SQL statement.
	 * @return Dibi\Result|int   result set or number of affected rows
	 */
	public static function nativeQuery($sql)
	{
		return self::getConnection()->nativeQuery($sql);
	}


	/**
	 * Generates and prints SQL query - Monostate for Dibi\Connection::test().
	 * @param  array|mixed  one or more arguments
	 * @return bool
	 */
	public static function test($args)
	{
		$args = func_get_args();
		return self::getConnection()->test($args);
	}


	/**
	 * Generates and returns SQL query as DataSource - Monostate for Dibi\Connection::test().
	 * @param  array|mixed      one or more arguments
	 * @return Dibi\DataSource
	 */
	public static function dataSource($args)
	{
		$args = func_get_args();
		return self::getConnection()->dataSource($args);
	}


	/**
	 * Executes SQL query and fetch result - Monostate for Dibi\Connection::query() & fetch().
	 * @param  array|mixed    one or more arguments
	 * @return Dibi\Row
	 * @throws Dibi\Exception
	 */
	public static function fetch($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - Monostate for Dibi\Connection::query() & fetchAll().
	 * @param  array|mixed    one or more arguments
	 * @return Dibi\Row[]
	 * @throws Dibi\Exception
	 */
	public static function fetchAll($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - Monostate for Dibi\Connection::query() & fetchSingle().
	 * @param  array|mixed    one or more arguments
	 * @return mixed
	 * @throws Dibi\Exception
	 */
	public static function fetchSingle($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - Monostate for Dibi\Connection::query() & fetchPairs().
	 * @param  array|mixed    one or more arguments
	 * @return array
	 * @throws Dibi\Exception
	 */
	public static function fetchPairs($args)
	{
		$args = func_get_args();
		return self::getConnection()->query($args)->fetchPairs();
	}


	/**
	 * Gets the number of affected rows.
	 * Monostate for Dibi\Connection::getAffectedRows()
	 * @return int  number of rows
	 * @throws Dibi\Exception
	 */
	public static function getAffectedRows()
	{
		return self::getConnection()->getAffectedRows();
	}


	/**
	 * @deprecated
	 */
	public static function affectedRows()
	{
		trigger_error(__METHOD__ . '() is deprecated, use getAffectedRows()', E_USER_DEPRECATED);
		return self::getConnection()->getAffectedRows();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * Monostate for Dibi\Connection::getInsertId()
	 * @param  string     optional sequence name
	 * @return int
	 * @throws Dibi\Exception
	 */
	public static function getInsertId($sequence = null)
	{
		return self::getConnection()->getInsertId($sequence);
	}


	/**
	 * @deprecated
	 */
	public static function insertId($sequence = null)
	{
		trigger_error(__METHOD__ . '() is deprecated, use getInsertId()', E_USER_DEPRECATED);
		return self::getConnection()->getInsertId($sequence);
	}


	/**
	 * Begins a transaction - Monostate for Dibi\Connection::begin().
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\Exception
	 */
	public static function begin($savepoint = null)
	{
		self::getConnection()->begin($savepoint);
	}


	/**
	 * Commits statements in a transaction - Monostate for Dibi\Connection::commit($savepoint = null).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\Exception
	 */
	public static function commit($savepoint = null)
	{
		self::getConnection()->commit($savepoint);
	}


	/**
	 * Rollback changes in a transaction - Monostate for Dibi\Connection::rollback().
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws Dibi\Exception
	 */
	public static function rollback($savepoint = null)
	{
		self::getConnection()->rollback($savepoint);
	}


	/**
	 * Gets a information about the current database - Monostate for Dibi\Connection::getDatabaseInfo().
	 * @return Dibi\Reflection\Database
	 */
	public static function getDatabaseInfo()
	{
		return self::getConnection()->getDatabaseInfo();
	}


	/**
	 * Import SQL dump from file - extreme fast!
	 * @param  string  filename
	 * @return int  count of sql commands
	 */
	public static function loadFile($file)
	{
		return Dibi\Helpers::loadFromFile(self::getConnection(), $file);
	}


	/********************* fluent SQL builders ****************d*g**/


	/**
	 * @return Dibi\Fluent
	 */
	public static function command()
	{
		return self::getConnection()->command();
	}


	/**
	 * @param  mixed    column name
	 * @return Dibi\Fluent
	 */
	public static function select($args)
	{
		$args = func_get_args();
		return call_user_func_array([self::getConnection(), 'select'], $args);
	}


	/**
	 * @param  string   table
	 * @param  array
	 * @return Dibi\Fluent
	 */
	public static function update($table, $args)
	{
		return self::getConnection()->update($table, $args);
	}


	/**
	 * @param  string   table
	 * @param  array
	 * @return Dibi\Fluent
	 */
	public static function insert($table, $args)
	{
		return self::getConnection()->insert($table, $args);
	}


	/**
	 * @param  string   table
	 * @return Dibi\Fluent
	 */
	public static function delete($table)
	{
		return self::getConnection()->delete($table);
	}


	/********************* substitutions ****************d*g**/


	/**
	 * Returns substitution hashmap - Monostate for Dibi\Connection::getSubstitutes().
	 * @return Dibi\HashMap
	 */
	public static function getSubstitutes()
	{
		return self::getConnection()->getSubstitutes();
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Prints out a syntax highlighted version of the SQL command or Result.
	 * @param  string|Result
	 * @param  bool  return output instead of printing it?
	 * @return string
	 */
	public static function dump($sql = null, $return = false)
	{
		return Dibi\Helpers::dump($sql, $return);
	}
}
