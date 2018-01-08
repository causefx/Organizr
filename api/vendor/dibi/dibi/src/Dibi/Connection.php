<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;

use Traversable;


/**
 * dibi connection.
 *
 * @property-read int $affectedRows
 * @property-read int $insertId
 */
class Connection
{
	use Strict;

	/** @var array of function (Event $event); Occurs after query is executed */
	public $onEvent;

	/** @var array  Current connection configuration */
	private $config;

	/** @var Driver */
	private $driver;

	/** @var Translator */
	private $translator;

	/** @var bool  Is connected? */
	private $connected = false;

	/** @var HashMap Substitutes for identifiers */
	private $substitutes;


	/**
	 * Connection options: (see driver-specific options too)
	 *   - lazy (bool) => if true, connection will be established only when required
	 *   - result (array) => result set options
	 *       - formatDateTime => date-time format (if empty, DateTime objects will be returned)
	 *   - profiler (array or bool)
	 *       - run (bool) => enable profiler?
	 *       - file => file to log
	 *   - substitutes (array) => map of driver specific substitutes (under development)
	 * @param  mixed   connection parameters
	 * @param  string  connection name
	 * @throws Exception
	 */
	public function __construct($config, $name = null)
	{
		if (is_string($config)) {
			parse_str($config, $config);

		} elseif ($config instanceof Traversable) {
			$tmp = [];
			foreach ($config as $key => $val) {
				$tmp[$key] = $val instanceof Traversable ? iterator_to_array($val) : $val;
			}
			$config = $tmp;

		} elseif (!is_array($config)) {
			throw new \InvalidArgumentException('Configuration must be array, string or object.');
		}

		Helpers::alias($config, 'username', 'user');
		Helpers::alias($config, 'password', 'pass');
		Helpers::alias($config, 'host', 'hostname');
		Helpers::alias($config, 'result|formatDate', 'resultDate');
		Helpers::alias($config, 'result|formatDateTime', 'resultDateTime');

		if (!isset($config['driver'])) {
			$config['driver'] = \dibi::$defaultDriver;
		}

		if ($config['driver'] instanceof Driver) {
			$this->driver = $config['driver'];
			$config['driver'] = get_class($this->driver);
		} elseif (is_subclass_of($config['driver'], 'Dibi\Driver')) {
			$this->driver = new $config['driver'];
		} else {
			$class = preg_replace(['#\W#', '#sql#'], ['_', 'Sql'], ucfirst(strtolower($config['driver'])));
			$class = "Dibi\\Drivers\\{$class}Driver";
			if (!class_exists($class)) {
				throw new Exception("Unable to create instance of dibi driver '$class'.");
			}
			$this->driver = new $class;
		}

		$config['name'] = $name;
		$this->config = $config;

		// profiler
		$profilerCfg = &$config['profiler'];
		if (is_scalar($profilerCfg)) {
			$profilerCfg = ['run' => (bool) $profilerCfg];
		}
		if (!empty($profilerCfg['run'])) {
			$filter = isset($profilerCfg['filter']) ? $profilerCfg['filter'] : Event::QUERY;

			if (isset($profilerCfg['file'])) {
				$this->onEvent[] = [new Loggers\FileLogger($profilerCfg['file'], $filter), 'logEvent'];
			}

			if (Loggers\FirePhpLogger::isAvailable()) {
				$this->onEvent[] = [new Loggers\FirePhpLogger($filter), 'logEvent'];
			}
		}

		$this->substitutes = new HashMap(function ($expr) { return ":$expr:"; });
		if (!empty($config['substitutes'])) {
			foreach ($config['substitutes'] as $key => $value) {
				$this->substitutes->$key = $value;
			}
		}

		if (empty($config['lazy'])) {
			$this->connect();
		}
	}


	/**
	 * Automatically frees the resources allocated for this result set.
	 * @return void
	 */
	public function __destruct()
	{
		// disconnects and rolls back transaction - do not rely on auto-disconnect and rollback!
		$this->connected && $this->driver->getResource() && $this->disconnect();
	}


	/**
	 * Connects to a database.
	 * @return void
	 */
	final public function connect()
	{
		$event = $this->onEvent ? new Event($this, Event::CONNECT) : null;
		try {
			$this->driver->connect($this->config);
			$this->connected = true;
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Disconnects from a database.
	 * @return void
	 */
	final public function disconnect()
	{
		$this->driver->disconnect();
		$this->connected = false;
	}


	/**
	 * Returns true when connection was established.
	 * @return bool
	 */
	final public function isConnected()
	{
		return $this->connected;
	}


	/**
	 * Returns configuration variable. If no $key is passed, returns the entire array.
	 * @see self::__construct
	 * @param  string
	 * @param  mixed  default value to use if key not found
	 * @return mixed
	 */
	final public function getConfig($key = null, $default = null)
	{
		if ($key === null) {
			return $this->config;

		} elseif (isset($this->config[$key])) {
			return $this->config[$key];

		} else {
			return $default;
		}
	}


	/** @deprecated */
	public static function alias(&$config, $key, $alias)
	{
		trigger_error(__METHOD__ . '() is deprecated, use Helpers::alias().', E_USER_DEPRECATED);
		Helpers::alias($config, $key, $alias);
	}


	/**
	 * Returns the driver and connects to a database in lazy mode.
	 * @return Driver
	 */
	final public function getDriver()
	{
		$this->connected || $this->connect();
		return $this->driver;
	}


	/**
	 * Generates (translates) and executes SQL query.
	 * @param  array|mixed      one or more arguments
	 * @return Result|int   result set or number of affected rows
	 * @throws Exception
	 */
	final public function query($args)
	{
		$args = func_get_args();
		return $this->nativeQuery($this->translateArgs($args));
	}


	/**
	 * Generates SQL query.
	 * @param  array|mixed      one or more arguments
	 * @return string
	 * @throws Exception
	 */
	final public function translate($args)
	{
		$args = func_get_args();
		return $this->translateArgs($args);
	}


	/**
	 * Generates and prints SQL query.
	 * @param  array|mixed  one or more arguments
	 * @return bool
	 */
	final public function test($args)
	{
		$args = func_get_args();
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
	 * @param  array|mixed      one or more arguments
	 * @return DataSource
	 * @throws Exception
	 */
	final public function dataSource($args)
	{
		$args = func_get_args();
		return new DataSource($this->translateArgs($args), $this);
	}


	/**
	 * Generates SQL query.
	 * @param  array
	 * @return string
	 */
	protected function translateArgs($args)
	{
		$this->connected || $this->connect();
		if (!$this->translator) {
			$this->translator = new Translator($this);
		}
		$translator = clone $this->translator;
		return $translator->translate($args);
	}


	/**
	 * Executes the SQL query.
	 * @param  string           SQL statement.
	 * @return Result|int   result set or number of affected rows
	 * @throws Exception
	 */
	final public function nativeQuery($sql)
	{
		$this->connected || $this->connect();

		\dibi::$sql = $sql;
		$event = $this->onEvent ? new Event($this, Event::QUERY, $sql) : null;
		try {
			$res = $this->driver->query($sql);

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}

		if ($res) {
			$res = $this->createResultSet($res);
		} else {
			$res = $this->driver->getAffectedRows();
		}

		$event && $this->onEvent($event->done($res));
		return $res;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @return int  number of rows
	 * @throws Exception
	 */
	public function getAffectedRows()
	{
		$this->connected || $this->connect();
		$rows = $this->driver->getAffectedRows();
		if (!is_int($rows) || $rows < 0) {
			throw new Exception('Cannot retrieve number of affected rows.');
		}
		return $rows;
	}


	/**
	 * @deprecated
	 */
	public function affectedRows()
	{
		trigger_error(__METHOD__ . '() is deprecated, use getAffectedRows()', E_USER_DEPRECATED);
		return $this->getAffectedRows();
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @param  string     optional sequence name
	 * @return int
	 * @throws Exception
	 */
	public function getInsertId($sequence = null)
	{
		$this->connected || $this->connect();
		$id = $this->driver->getInsertId($sequence);
		if ($id < 1) {
			throw new Exception('Cannot retrieve last generated ID.');
		}
		return Helpers::intVal($id);
	}


	/**
	 * @deprecated
	 */
	public function insertId($sequence = null)
	{
		trigger_error(__METHOD__ . '() is deprecated, use getInsertId()', E_USER_DEPRECATED);
		return $this->getInsertId($sequence);
	}


	/**
	 * Begins a transaction (if supported).
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function begin($savepoint = null)
	{
		$this->connected || $this->connect();
		$event = $this->onEvent ? new Event($this, Event::BEGIN, $savepoint) : null;
		try {
			$this->driver->begin($savepoint);
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function commit($savepoint = null)
	{
		$this->connected || $this->connect();
		$event = $this->onEvent ? new Event($this, Event::COMMIT, $savepoint) : null;
		try {
			$this->driver->commit($savepoint);
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Rollback changes in a transaction.
	 * @param  string  optional savepoint name
	 * @return void
	 */
	public function rollback($savepoint = null)
	{
		$this->connected || $this->connect();
		$event = $this->onEvent ? new Event($this, Event::ROLLBACK, $savepoint) : null;
		try {
			$this->driver->rollback($savepoint);
			$event && $this->onEvent($event->done());

		} catch (Exception $e) {
			$event && $this->onEvent($event->done($e));
			throw $e;
		}
	}


	/**
	 * Result set factory.
	 * @param  ResultDriver
	 * @return Result
	 */
	public function createResultSet(ResultDriver $resultDriver)
	{
		$res = new Result($resultDriver);
		return $res->setFormat(Type::DATE, $this->config['result']['formatDate'])
			->setFormat(Type::DATETIME, $this->config['result']['formatDateTime']);
	}


	/********************* fluent SQL builders ****************d*g**/


	/**
	 * @return Fluent
	 */
	public function command()
	{
		return new Fluent($this);
	}


	/**
	 * @param  mixed    column name
	 * @return Fluent
	 */
	public function select($args)
	{
		$args = func_get_args();
		return $this->command()->__call('select', $args);
	}


	/**
	 * @param  string   table
	 * @param  array
	 * @return Fluent
	 */
	public function update($table, $args)
	{
		if (!(is_array($args) || $args instanceof Traversable)) {
			throw new \InvalidArgumentException('Arguments must be array or Traversable.');
		}
		return $this->command()->update('%n', $table)->set($args);
	}


	/**
	 * @param  string   table
	 * @param  array
	 * @return Fluent
	 */
	public function insert($table, $args)
	{
		if ($args instanceof Traversable) {
			$args = iterator_to_array($args);
		} elseif (!is_array($args)) {
			throw new \InvalidArgumentException('Arguments must be array or Traversable.');
		}
		return $this->command()->insert()
			->into('%n', $table, '(%n)', array_keys($args))->values('%l', $args);
	}


	/**
	 * @param  string   table
	 * @return Fluent
	 */
	public function delete($table)
	{
		return $this->command()->delete()->from('%n', $table);
	}


	/********************* substitutions ****************d*g**/


	/**
	 * Returns substitution hashmap.
	 * @return HashMap
	 */
	public function getSubstitutes()
	{
		return $this->substitutes;
	}


	/**
	 * Provides substitution.
	 * @return string
	 */
	public function substitute($value)
	{
		return strpos($value, ':') === false
			? $value
			: preg_replace_callback('#:([^:\s]*):#', function ($m) { return $this->substitutes->{$m[1]}; }, $value);
	}


	/********************* shortcuts ****************d*g**/


	/**
	 * Executes SQL query and fetch result - shortcut for query() & fetch().
	 * @param  array|mixed    one or more arguments
	 * @return Row|false
	 * @throws Exception
	 */
	public function fetch($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetch();
	}


	/**
	 * Executes SQL query and fetch results - shortcut for query() & fetchAll().
	 * @param  array|mixed    one or more arguments
	 * @return Row[]
	 * @throws Exception
	 */
	public function fetchAll($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetchAll();
	}


	/**
	 * Executes SQL query and fetch first column - shortcut for query() & fetchSingle().
	 * @param  array|mixed    one or more arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function fetchSingle($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetchSingle();
	}


	/**
	 * Executes SQL query and fetch pairs - shortcut for query() & fetchPairs().
	 * @param  array|mixed    one or more arguments
	 * @return array
	 * @throws Exception
	 */
	public function fetchPairs($args)
	{
		$args = func_get_args();
		return $this->query($args)->fetchPairs();
	}


	/**
	 * @return Literal
	 */
	public static function literal($value)
	{
		return new Literal($value);
	}


	/********************* misc ****************d*g**/


	/**
	 * Import SQL dump from file.
	 * @param  string  filename
	 * @param  callable  function (int $count, ?float $percent): void
	 * @return int  count of sql commands
	 */
	public function loadFile($file, callable $onProgress = null)
	{
		return Helpers::loadFromFile($this, $file, $onProgress);
	}


	/**
	 * Gets a information about the current database.
	 * @return Reflection\Database
	 */
	public function getDatabaseInfo()
	{
		$this->connected || $this->connect();
		return new Reflection\Database($this->driver->getReflector(), isset($this->config['database']) ? $this->config['database'] : null);
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


	protected function onEvent($arg)
	{
		foreach ($this->onEvent ?: [] as $handler) {
			call_user_func($handler, $arg);
		}
	}
}
