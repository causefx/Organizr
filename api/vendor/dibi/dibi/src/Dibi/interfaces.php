<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Provides an interface between a dataset and data-aware components.
 */
interface IDataSource extends \Countable, \IteratorAggregate
{
	//function \IteratorAggregate::getIterator();
	//function \Countable::count();
}


/**
 * dibi driver interface.
 */
interface Driver
{

	/**
	 * Connects to a database.
	 * @param  array
	 * @return void
	 * @throws Exception
	 */
	function connect(array &$config);

	/**
	 * Disconnects from a database.
	 * @return void
	 * @throws Exception
	 */
	function disconnect();

	/**
	 * Internal: Executes the SQL query.
	 * @param  string      SQL statement.
	 * @return ResultDriver|null
	 * @throws DriverException
	 */
	function query($sql);

	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int|false  number of rows or false on error
	 */
	function getAffectedRows();

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @return int|false  int on success or false on failure
	 */
	function getInsertId($sequence);

	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DriverException
	 */
	function begin($savepoint = null);

	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DriverException
	 */
	function commit($savepoint = null);

	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 * @throws DriverException
	 */
	function rollback($savepoint = null);

	/**
	 * Returns the connection resource.
	 * @return mixed
	 */
	function getResource();

	/**
	 * Returns the connection reflector.
	 * @return Reflector
	 */
	function getReflector();

	/**
	 * Encodes data for use in a SQL statement.
	 * @param  string    value
	 * @return string    encoded value
	 */
	function escapeText($value);

	/**
	 * @param  string
	 * @return string
	 */
	function escapeBinary($value);

	/**
	 * @param  string
	 * @return string
	 */
	function escapeIdentifier($value);

	/**
	 * @param  bool
	 * @return string
	 */
	function escapeBool($value);

	/**
	 * @param  \DateTime|\DateTimeInterface|string|int
	 * @return string
	 */
	function escapeDate($value);

	/**
	 * @param  \DateTime|\DateTimeInterface|string|int
	 * @return string
	 */
	function escapeDateTime($value);

	/**
	 * Encodes string for use in a LIKE statement.
	 * @param  string
	 * @param  int
	 * @return string
	 */
	function escapeLike($value, $pos);

	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 * @param  string
	 * @param  int|null
	 * @param  int|null
	 * @return void
	 */
	function applyLimit(&$sql, $limit, $offset);
}


/**
 * dibi result set driver interface.
 */
interface ResultDriver
{

	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	function getRowCount();

	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return bool     true on success, false if unable to seek to specified record
	 * @throws Exception
	 */
	function seek($row);

	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 * @return array    array on success, nonarray if no next record
	 * @internal
	 */
	function fetch($type);

	/**
	 * Frees the resources allocated for this result set.
	 * @param  resource  result set resource
	 * @return void
	 */
	function free();

	/**
	 * Returns metadata for all columns in a result set.
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getResultColumns();

	/**
	 * Returns the result set resource.
	 * @return mixed
	 */
	function getResultResource();

	/**
	 * Decodes data from result set.
	 * @param  string
	 * @return string
	 */
	function unescapeBinary($value);
}


/**
 * dibi driver reflection.
 */
interface Reflector
{

	/**
	 * Returns list of tables.
	 * @return array of {name [, (bool) view ]}
	 */
	function getTables();

	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getColumns($table);

	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array of {name, (array of names) columns [, (bool) unique, (bool) primary ]}
	 */
	function getIndexes($table);

	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	function getForeignKeys($table);
}
