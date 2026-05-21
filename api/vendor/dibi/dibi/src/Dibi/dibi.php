<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);


/**
 * Static container class for Dibi connections.
 *
 * @method static void disconnect()
 * @method static Dibi\Result query(...$args)
 * @method static Dibi\Result nativeQuery(...$args)
 * @method static bool test(...$args)
 * @method static Dibi\DataSource dataSource(...$args)
 * @method static Dibi\Row|null fetch(...$args)
 * @method static array fetchAll(...$args)
 * @method static mixed fetchSingle(...$args)
 * @method static array fetchPairs(...$args)
 * @method static int getAffectedRows()
 * @method static int getInsertId(string $sequence = null)
 * @method static void begin(string $savepoint = null)
 * @method static void commit(string $savepoint = null)
 * @method static void rollback(string $savepoint = null)
 * @method static mixed transaction(callable $callback)
 * @method static Dibi\Reflection\Database getDatabaseInfo()
 * @method static Dibi\Fluent command()
 * @method static Dibi\Fluent select(...$args)
 * @method static Dibi\Fluent update(string|string[] $table, array $args)
 * @method static Dibi\Fluent insert(string $table, array $args)
 * @method static Dibi\Fluent delete(string $table)
 * @method static Dibi\HashMap getSubstitutes()
 * @method static int loadFile(string $file)
 */
class dibi
{
	use Dibi\Strict;

	public const
		AFFECTED_ROWS = 'a',
		IDENTIFIER = 'n';

	/** version */
	public const VERSION = '4.2.8';

	/** sorting order */
	public const
		ASC = 'ASC',
		DESC = 'DESC';

	/** @var string|null  Last SQL command @see dibi::query() */
	public static $sql;

	/** @var float|null  Elapsed time for last query */
	public static $elapsedTime;

	/** @var float  Elapsed time for all queries */
	public static $totalTime;

	/** @var int  Number or queries */
	public static $numOfQueries = 0;

	/** @var Dibi\Connection[]  Connection registry storage for Dibi\Connection objects */
	private static $registry = [];

	/** @var Dibi\Connection  Current connection */
	private static $connection;


	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new LogicException('Cannot instantiate static class ' . static::class);
	}


	/********************* connections handling ****************d*g**/


	/**
	 * Creates a new Connection object and connects it to specified database.
	 * @param  array   $config  connection parameters
	 * @throws Dibi\Exception
	 */
	public static function connect($config = [], string $name = '0'): Dibi\Connection
	{
		return self::$connection = self::$registry[$name] = new Dibi\Connection($config, $name);
	}


	/**
	 * Returns true when connection was established.
	 */
	public static function isConnected(): bool
	{
		return (self::$connection !== null) && self::$connection->isConnected();
	}


	/**
	 * Retrieve active connection.
	 * @throws Dibi\Exception
	 */
	public static function getConnection(?string $name = null): Dibi\Connection
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
	 */
	public static function setConnection(Dibi\Connection $connection): Dibi\Connection
	{
		return self::$connection = $connection;
	}


	/********************* monostate for active connection ****************d*g**/


	/**
	 * Monostate for Dibi\Connection.
	 */
	public static function __callStatic(string $name, array $args)
	{
		return self::getConnection()->$name(...$args);
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Prints out a syntax highlighted version of the SQL command or Result.
	 * @param  string|Dibi\Result  $sql
	 * @param  bool  $return  return output instead of printing it?
	 */
	public static function dump($sql = null, bool $return = false): ?string
	{
		return Dibi\Helpers::dump($sql, $return);
	}


	/**
	 * Strips microseconds part.
	 */
	public static function stripMicroseconds(DateTimeInterface $dt): DateTimeInterface
	{
		$class = get_class($dt);
		return new $class($dt->format('Y-m-d H:i:s'), $dt->getTimezone());
	}
}
