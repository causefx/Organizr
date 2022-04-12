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

	/** @var string[]  resultset formats */
	private $formats;

	/** @var Driver|null */
	private $driver;

	/** @var Translator|null */
	private $translator;

	/** @var HashMap Substitutes for identifiers */
	private $substitutes;

	private $transactionDepth = 0;


	/**
	 * Connection options: (see driver-specific options too)
	 *   - lazy (bool) => if true, connection will be established only when required
	 *   - result (array) => result set options
	 *       - normalize => normalizes result fields (default: true)
	 *       - formatDateTime => date-time format
	 *           empty for decoding as Dibi\DateTime (default)
	 *           "..." formatted according to given format, see https://www.php.net/manual/en/datetime.format.php
	 *           "native" for leaving value as is
	 *       - formatTimeInterval => time-interval format
	 *           empty for decoding as DateInterval (default)
	 *           "..." formatted according to given format, see https://www.php.net/manual/en/dateinterval.format.php
	 *           "native" for leaving value as is
	 *       - formatJson => json format
	 *           "array" for decoding json as an array (default)
	 *           "object" for decoding json as \stdClass
	 *           "native" for leaving value as is
	 *   - profiler (array)
	 *       - run (bool) => enable profiler?
	 *       - file => file to log
	 *       - errorsOnly (bool) => log only errors
	 *   - substitutes (array) => map of driver specific substitutes (under development)
	 *   - onConnect (array) => list of SQL queries to execute (by Connection::query()) after connection is established
	 * @throws Exception
	 */
	public function __construct(array $config, ?string $name = null)
	{
		Helpers::alias($config, 'username', 'user');
		Helpers::alias($config, 'password', 'pass');
		Helpers::alias($config, 'host', 'hostname');
		Helpers::alias($config, 'result|formatDate', 'resultDate');
		Helpers::alias($config, 'result|formatDateTime', 'resultDateTime');
		$config['driver'] = $config['driver'] ?? 'mysqli';
		$config['name'] = $name;
		$this->config = $config;

		$this->formats = [
			Type::DATE => $this->config['result']['formatDate'],
			Type::DATETIME => $this->config['result']['formatDateTime'],
			Type::JSON => $this->config['result']['formatJson'] ?? 'array',
			Type::TIME_INTERVAL => $this->config['result']['formatTimeInterval'] ?? null,
		];

		// profiler
		if (isset($config['profiler']['file']) && (!isset($config['profiler']['run']) || $config['profiler']['run'])) {
			$filter = $config['profiler']['filter'] ?? Event::QUERY;
			$errorsOnly = $config['profiler']['errorsOnly'] ?? false;
			$this->onEvent[] = [new Loggers\FileLogger($config['profiler']['file'], $filter, $errorsOnly), 'logEvent'];
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
			$this->translator = new Translator($this);
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
			$this->translator = new Translator($this);

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
			$this->driver = $this->translator = null;
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
	final public function getConfig(?string $key = null, $default = null)
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
		return $this->nativeQuery($this->translate(...$args));
	}


	/**
	 * Generates SQL query.
	 * @param  mixed  ...$args
	 * @throws Exception
	 */
	final public function translate(...$args): string
	{
		if (!$this->driver) {
			$this->connect();
		}

		return (clone $this->translator)->translate($args);
	}


	/**
	 * Generates and prints SQL query.
	 * @param  mixed  ...$args
	 */
	final public function test(...$args): bool
	{
		try {
			Helpers::dump($this->translate(...$args));
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
		return new DataSource($this->translate(...$args), $this);
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
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @throws Exception
	 */
	public function getInsertId(?string $sequence = null): int
	{
		if (!$this->driver) {
			$this->connect();
		}

		$id = $this->driver->getInsertId($sequence);
		if ($id === null) {
			throw new Exception('Cannot retrieve last generated ID.');
		}

		return $id;
	}


	/**
	 * Begins a transaction (if supported).
	 */
	public function begin(?string $savepoint = null): void
	{
		if ($this->transactionDepth !== 0) {
			throw new \LogicException(__METHOD__ . '() call is forbidden inside a transaction() callback');
		}

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
	public function commit(?string $savepoint = null): void
	{
		if ($this->transactionDepth !== 0) {
			throw new \LogicException(__METHOD__ . '() call is forbidden inside a transaction() callback');
		}

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
	public function rollback(?string $savepoint = null): void
	{
		if ($this->transactionDepth !== 0) {
			throw new \LogicException(__METHOD__ . '() call is forbidden inside a transaction() callback');
		}

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
	 * @return mixed
	 */
	public function transaction(callable $callback)
	{
		if ($this->transactionDepth === 0) {
			$this->begin();
		}

		$this->transactionDepth++;
		try {
			$res = $callback($this);
		} catch (\Throwable $e) {
			$this->transactionDepth--;
			if ($this->transactionDepth === 0) {
				$this->rollback();
			}

			throw $e;
		}

		$this->transactionDepth--;
		if ($this->transactionDepth === 0) {
			$this->commit();
		}

		return $res;
	}


	/**
	 * Result set factory.
	 */
	public function createResultSet(ResultDriver $resultDriver): Result
	{
		return (new Result($resultDriver, $this->config['result']['normalize'] ?? true))
			->setFormats($this->formats);
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
	public function loadFile(string $file, ?callable $onProgress = null): int
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
		throw new NotSupportedException('You cannot serialize or unserialize ' . static::class . ' instances.');
	}


	/**
	 * Prevents serialization.
	 */
	public function __sleep()
	{
		throw new NotSupportedException('You cannot serialize or unserialize ' . static::class . ' instances.');
	}


	protected function onEvent($arg): void
	{
		foreach ($this->onEvent as $handler) {
			$handler($arg);
		}
	}
}
