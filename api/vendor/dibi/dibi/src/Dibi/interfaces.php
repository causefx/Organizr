<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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
 * Driver interface.
 */
interface Driver
{
	/**
	 * Disconnects from a database.
	 * @throws Exception
	 */
	function disconnect(): void;

	/**
	 * Internal: Executes the SQL query.
	 * @throws DriverException
	 */
	function query(string $sql): ?ResultDriver;

	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	function getAffectedRows(): ?int;

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	function getInsertId(?string $sequence): ?int;

	/**
	 * Begins a transaction (if supported).
	 * @throws DriverException
	 */
	function begin(?string $savepoint = null): void;

	/**
	 * Commits statements in a transaction.
	 * @throws DriverException
	 */
	function commit(?string $savepoint = null): void;

	/**
	 * Rollback changes in a transaction.
	 * @throws DriverException
	 */
	function rollback(?string $savepoint = null): void;

	/**
	 * Returns the connection resource.
	 * @return mixed
	 */
	function getResource();

	/**
	 * Returns the connection reflector.
	 */
	function getReflector(): Reflector;

	/**
	 * Encodes data for use in a SQL statement.
	 */
	function escapeText(string $value): string;

	function escapeBinary(string $value): string;

	function escapeIdentifier(string $value): string;

	function escapeBool(bool $value): string;

	function escapeDate(\DateTimeInterface $value): string;

	function escapeDateTime(\DateTimeInterface $value): string;

	function escapeDateInterval(\DateInterval $value): string;

	/**
	 * Encodes string for use in a LIKE statement.
	 */
	function escapeLike(string $value, int $pos): string;

	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	function applyLimit(string &$sql, ?int $limit, ?int $offset): void;
}


/**
 * Result set driver interface.
 */
interface ResultDriver
{
	/**
	 * Returns the number of rows in a result set.
	 */
	function getRowCount(): int;

	/**
	 * Moves cursor position without fetching row.
	 * @return bool  true on success, false if unable to seek to specified record
	 * @throws Exception
	 */
	function seek(int $row): bool;

	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $type  true for associative array, false for numeric
	 * @internal
	 */
	function fetch(bool $type): ?array;

	/**
	 * Frees the resources allocated for this result set.
	 */
	function free(): void;

	/**
	 * Returns metadata for all columns in a result set.
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getResultColumns(): array;

	/**
	 * Returns the result set resource.
	 * @return mixed
	 */
	function getResultResource();

	/**
	 * Decodes data from result set.
	 */
	function unescapeBinary(string $value): string;
}


/**
 * Reflection driver.
 */
interface Reflector
{
	/**
	 * Returns list of tables.
	 * @return array of {name [, (bool) view ]}
	 */
	function getTables(): array;

	/**
	 * Returns metadata for all columns in a table.
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getColumns(string $table): array;

	/**
	 * Returns metadata for all indexes in a table.
	 * @return array of {name, (array of names) columns [, (bool) unique, (bool) primary ]}
	 */
	function getIndexes(string $table): array;

	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	function getForeignKeys(string $table): array;
}


/**
 * Dibi connection.
 */
interface IConnection
{
	/**
	 * Connects to a database.
	 */
	function connect(): void;

	/**
	 * Disconnects from a database.
	 */
	function disconnect(): void;

	/**
	 * Returns true when connection was established.
	 */
	function isConnected(): bool;

	/**
	 * Returns the driver and connects to a database in lazy mode.
	 */
	function getDriver(): Driver;

	/**
	 * Generates (translates) and executes SQL query.
	 * @throws Exception
	 */
	function query(...$args): Result;

	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @throws Exception
	 */
	function getAffectedRows(): int;

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @throws Exception
	 */
	function getInsertId(?string $sequence = null): int;

	/**
	 * Begins a transaction (if supported).
	 */
	function begin(?string $savepoint = null): void;

	/**
	 * Commits statements in a transaction.
	 */
	function commit(?string $savepoint = null): void;

	/**
	 * Rollback changes in a transaction.
	 */
	function rollback(?string $savepoint = null): void;
}
