<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;

use Traversable;


/**
 * Dibi connection.
 *
 * @property-read int $affectedRows
 * @property-read int $insertId
 */
class Connection implements IConnection
{
	use Strict;

	/** @var array of function (Event $event); Occurs after query is executed */
	public $onEvent = [];

	/** @var array  Current connection configuration */
	private $config;

	/** @var Driver|null */
	private $driver;

	/** @var Translator|null */
	private $translator;

	/** @var HashMap Substitutes for identifiers */
	private $substitutes;


	/**
	 * Connection options: (see driver-specific options too)
	 *   - lazy (bool) => if true, connection will be established only when required
	 *   - result (array) => result set options
	 *       - formatDateTime => date-time format (if empty, DateTime objects will be returned)
	 *   - profiler (array)
	 *       - run (bool) => enable profiler?
	 *       - file => file to log
	 *   - substitutes (array) => map of driver specific substitutes (under development)
	 *   - onConnect (array) => list of SQL queries to execute (by Connection::query()) after connection is established
	 * @param  array   $config  connection parameters
	 * @throws Exception
	 */
	public function __construct($config, string $name = null)
	{
		if (is_string($config)) {
			trigger_error(__METHOD__ . '() Configuration should be array.', E_USER_DEPRECATED);
			parse_str($config, $config);

		} elseif ($config instanceof Traversable) {
			trigger_error(__METHOD__ . '() Configuration should be array.', E_USER_DEPRECATED);
			$tmp = [];
			foreach ($config as $key => $val) {
				$tmp[$key] = $val instanceof Traversable ? iterator_to_array($val) : $val;
			}
			$config = $tmp;

		} elseif (!is_array($config)) {
			throw new \InvalidArgumentException('Configuration must be array.');
		}

		Helpers::alias($config, 'username', 'user');
		Helpers::alias($config, 'password', 'pass');
		Helpers::alias($config, 'host', 'hostname');
		Helpers::alias($config, 'result|formatDate', 'resultDate');
		Helpers::alias($config, 'result|formatDateTime', 'resultDateTime');
		$config['driver'] = $config['driver'] ?? 'mysqli';
		$config['name'] = $name;
		$this->config = $config;

		// profiler
		if (isset($config['profiler']['file']) && (!isset($config['profiler']['run']) || $config['profiler']['run'])) {
			$filter = $config['profiler']['filter'] ?? Event::QUERY;
			$this->onEvent[] = [new Loggers\FileLogger($config['profiler']['file'], $filter), 'logEvent'];
		}

		$this->substitutes = new HashMap(function (string $expr) { return ":$expr:"; });
		if (!empty($config['substitutes'])) {
			foreach ($config['substitutes'] as $key => $value) {
				$this->substitutes->$key = $value;
			}
		}

		if (isset($config['onConnect']) && !is_array($config['onConnect'])) {
			throw new \InvalidArgumentException("Configuration option 'onConnect' must be array.");
		}

		if (empty($config['lazy'])) {
			$this->connect();
		}
	}


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		if ($this->driver && $this->driver->getResource()) {
			$this->disconnect();
		}
	}


	/**
	 * Connects to a database.
	 */
	final public function connect(): void
	{
		if ($this->config['driver'] instanceof Driver) {
			$this->driver = $this->config['driver'];
			return;

		} elseif (is_subclass_of($this->config['driver'], Driver::class)) {
			$class = $this->config['driver'];

		} else {
			$class = preg_replace(['#\W#', '#sql#'], ['_', 'Sql'], ucfirst(strtolower($this->config['driver'])));
			$class = "Dibi\\Drivers\\{$class}Driver";
			if (!class_exists($class)) {
				throw new Exception("Unable to create instance of Dibi driver '$class'.");
			}
		}

		$event = $this->onEvent ? new Event($this, Event::CONNECT) : null;
		try {
			$this->driver = new $class($this->config);
			if ($event) {
				$this->onEvent($event->done());
			}
			if (isset($this->config['onConnect'])) {
				foreach ($this->config['onConnect'] as $sql) {
					$this->query($sql);
				}
			}

		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}
			throw $e;
		}
	}


	/**
	 * Disconnects from a database.
	 */
	final public function disconnect(): void
	{
		if ($this->driver) {
			$this->driver->disconnect();
			$this->driver = null;
		}
	}


	/**
	 * Returns true when connection was established.
	 */
	final public function isConnected(): bool
	{
		return (bool) $this->driver;
	}


	/**
	 * Returns configuration variable. If no $key is passed, returns the entire array.
	 * @see self::__construct
	 * @return mixed
	 */
	final public function getConfig(string $key = null, $default = null)
	{
		return $key === null
			? $this->config
			: ($this->config[$key] ?? $default);
	}


	/**
	 * Returns the driver and connects to a database in lazy mode.
	 */
	final public function getDriver(): Driver
	{
		if (!$this->driver) {
			$this->connect();
		}
		return $this->driver;
	}


	/**
	 * Generates (translates) and executes SQL query.
	 * @param  mixed  ...$args
	 * @throws Exception
	 */
	final public function query(...$args): Result
	{
		return $this->nativeQuery($this->translateArgs($args));
	}


	/**
	 * Generates SQL query.
	 * @param  mixed  ...$args
	 * @throws Exception
	 */
	final public function translate(...$args): string
	{
		return $this->translateArgs($args);
	}


	/**
	 * Generates and prints SQL query.
	 * @param  mixed  ...$args
	 */
	final public function test(...$args): bool
	{
		try {
			Helpers::dump($this->translateArgs($args));
			return true;

		} catch (Exception $e) {
			if ($e->getSql()) {
				Helpers::dump($e->getSql());
			} else {
				echo get_class($e) . ': ' . $e->getMessage() . (PHP_SAPI === 'cli' ? "\n" : '<br>');
			}
			return false;
		}
	}


	/**
	 * Generates (translates) and returns SQL query as DataSource.
	 * @param  mixed  ...$args
	 * @throws Exception
	 */
	final public function dataSource(...$args): DataSource
	{
		return new DataSource($this->translateArgs($args), $this);
	}


	/**
	 * Generates SQL query.
	 */
	protected function translateArgs(array $args): string
	{
		if (!$this->driver) {
			$this->connect();
		}
		if (!$this->translator) {
			$this->translator = new Translator($this);
		}
		$translator = clone $this->translator;
		return $translator->translate($args);
	}


	/**
	 * Executes the SQL query.
	 * @throws Exception
	 */
	final public function nativeQuery(string $sql): Result
	{
		if (!$this->driver) {
			$this->connect();
		}

		\dibi::$sql = $sql;
		$event = $this->onEvent ? new Event($this, Event::QUERY, $sql) : null;
		try {
			$res = $this->driver->query($sql);

		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}
			throw $e;
		}

		$res = $this->createResultSet($res ?: new Drivers\NoDataResult(max(0, $this->driver->getAffectedRows())));
		if ($event) {
			$this->onEvent($event->done($res));
		}
		return $res;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @throws Exception
	 */
	public function getAffectedRows(): int
	{
		if (!$this->driver) {
			$this->connect();
		}
		$rows = $this->driver->getAffectedRows();
		if ($rows === null || $rows < 0) {
			throw new Exception('Cannot retrieve number of affected rows.');
		}
		return $rows;
	}


	/**
	 * @deprecated
	 */
	public function affectedRows(): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getAffectedRows()', E_USER_DEPRECATED);
		return $this->getAffectedRows();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @throws Exception
	 */
	public function getInsertId(string $sequence = null): int
	{
		if (!$this->driver) {
			$this->connect();
		}
		$id = $this->driver->getInsertId($sequence);
		if ($id < 1) {
			throw new Exception('Cannot retrieve last generated ID.');
		}
		return $id;
	}


	/**
	 * @deprecated
	 */
	public function insertId(string $sequence = null): int
	{
		trigger_error(__METHOD__ . '() is deprecated, use getInsertId()', E_USER_DEPRECATED);
		return $this->getInsertId($sequence);
	}


	/**
	 * Begins a transaction (if supported).
	 */
	public function begin(string $savepoint = null): void
	{
		if (!$this->driver) {
			$this->connect();
		}
		$event = $this->onEvent ? new Event($this, Event::BEGIN, $savepoint) : null;
		try {
			$this->driver->begin($savepoint);
			if ($event) {
				$this->onEvent($event->done());
			}

		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}
			throw $e;
		}
	}


	/**
	 * Commits statements in a transaction.
	 */
	public function commit(string $savepoint = null): void
	{
		if (!$this->driver) {
			$this->connect();
		}
		$event = $this->onEvent ? new Event($this, Event::COMMIT, $savepoint) : null;
		try {
			$this->driver->commit($savepoint);
			if ($event) {
				$this->onEvent($event->done());
			}

		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}
			throw $e;
		}
	}


	/**
	 * Rollback changes in a transaction.
	 */
	public function rollback(string $savepoint = null): void
	{
		if (!$this->driver) {
			$this->connect();
		}
		$event = $this->onEvent ? new Event($this, Event::ROLLBACK, $savepoint) : null;
		try {
			$this->driver->rollback($savepoint);
			if ($event) {
				$this->onEvent($event->done());
			}

		} catch (DriverException $e) {
			if ($event) {
				$this->onEvent($event->done($e));
			}
			throw $e;
		}
	}


	/**
	 * Result set factory.
	 */
	public function createResultSet(ResultDriver $resultDriver): Result
	{
		$res = new Result($resultDriver);
		return $res->setFormat(Type::DATE, $this->config['result']['formatDate'])
			->setFormat(Type::DATETIME, $this->config['result']['formatDateTime']);
	}


	/********************* fluent SQL builders ****************d*g**/


	public function command(): Fluent
	{
		return new Fluent($this);
	}


	public function select(...$args): Fluent
	{
		return $this->command()->select(...$args);
	}


	/**
	 * @param  string|string[]  $table
	 */
	public function update($table, iterable $args): Fluent
	{
		return $this->command()->update('%n', $table)->set($args);
	}


	public function insert(string $table, iterable $args): Fluent
	{
		if ($args instanceof Traversable) {
			$args = iterator_to_array($args);
		}
		return $this->command()->insert()
			->into('%n', $table, '(%n)', array_keys($args))->values('%l', $args);
	}


	public function delete(string $table): Fluent
	{
		return $this->command()->delete()->from('%n', $table);
	}


	/********************* substitutions ****************d*g**/


	/**
	 * Returns substitution hashmap.
	 */
	public function getSubstitutes(): HashMap
	{
		return $this->substitutes;
	}


	/**
	 * Provides substitution.
	 */
	public function substitute(string $value): string
	{
		return strpos($value, ':') === false
			? $value
			: preg_replace_callback('#:([^:\s]*):#', function (array $m) { return $this->substitutes->{$m[1]}; }, $value);
	}


	/********************* shortcuts ****************d*g**/


	/**
	 * Executes SQL query and fetch result - shortcut for query() & fetch().
	 * @param  mixed  ...$args
	 * @throws Exception
	 */
	public function fetch(...$args): ?Row
	{
		return $this->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - shortcut for query() & fetchAll().
	 * @param  mixed  ...$args
	 * @return Row[]|array[]
	 * @throws Exception
	 */
	public function fetchAll(...$args): array
	{
		return $this->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - shortcut for query() & fetchSingle().
	 * @param  mixed  ...$args
	 * @return mixed
	 * @throws Exception
	 */
	public function fetchSingle(...$args)
	{
		return $this->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - shortcut for query() & fetchPairs().
	 * @param  mixed  ...$args
	 * @throws Exception
	 */
	public function fetchPairs(...$args): array
	{
		return $this->query($args)->fetchPairs();
	}


	public static function literal(string $value): Literal
	{
		return new Literal($value);
	}


	public static function expression(...$args): Expression
	{
		return new Expression(...$args);
	}


	/********************* misc ****************d*g**/


	/**
	 * Import SQL dump from file.
	 * @param  callable  $onProgress  function (int $count, ?float $percent): void
	 * @return int  count of sql commands
	 */
	public function loadFile(string $file, callable $onProgress = null): int
	{
		return Helpers::loadFromFile($this, $file, $onProgress);
	}


	/**
	 * Gets a information about the current database.
	 */
	public function getDatabaseInfo(): Reflection\Database
	{
		if (!$this->driver) {
			$this->connect();
		}
		return new Reflection\Database($this->driver->getReflector(), $this->config['database'] ?? null);
	}


	/**
	 * Prevents unserialization.
	 */
	public function __wakeup()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . get_class($this) . ' instances.');
	}


	/**
	 * Prevents serialization.
	 */
	public function __sleep()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . get_class($this) . ' instances.');
	}


	protected function onEvent($arg): void
	{
		foreach ($this->onEvent as $handler) {
			$handler($arg);
		}
	}
}
