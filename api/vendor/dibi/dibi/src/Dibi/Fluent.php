<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * dibi SQL builder via fluent interfaces. EXPERIMENTAL!
 *
 * @method Fluent select(...$field)
 * @method Fluent distinct()
 * @method Fluent from($table)
 * @method Fluent where(...$cond)
 * @method Fluent groupBy(...$field)
 * @method Fluent having(...$cond)
 * @method Fluent orderBy(...$field)
 * @method Fluent limit(int $limit)
 * @method Fluent offset(int $offset)
 * @method Fluent join(...$table)
 * @method Fluent leftJoin(...$table)
 * @method Fluent innerJoin(...$table)
 * @method Fluent rightJoin(...$table)
 * @method Fluent outerJoin(...$table)
 * @method Fluent as(...$field)
 * @method Fluent on(...$cond)
 * @method Fluent using(...$cond)
 */
class Fluent implements IDataSource
{
	use Strict;

	const REMOVE = false;

	/** @var array */
	public static $masks = [
		'SELECT' => ['SELECT', 'DISTINCT', 'FROM', 'WHERE', 'GROUP BY',
			'HAVING', 'ORDER BY', 'LIMIT', 'OFFSET', ],
		'UPDATE' => ['UPDATE', 'SET', 'WHERE', 'ORDER BY', 'LIMIT'],
		'INSERT' => ['INSERT', 'INTO', 'VALUES', 'SELECT'],
		'DELETE' => ['DELETE', 'FROM', 'USING', 'WHERE', 'ORDER BY', 'LIMIT'],
	];

	/** @var array  default modifiers for arrays */
	public static $modifiers = [
		'SELECT' => '%n',
		'FROM' => '%n',
		'IN' => '%in',
		'VALUES' => '%l',
		'SET' => '%a',
		'WHERE' => '%and',
		'HAVING' => '%and',
		'ORDER BY' => '%by',
		'GROUP BY' => '%by',
	];

	/** @var array  clauses separators */
	public static $separators = [
		'SELECT' => ',',
		'FROM' => ',',
		'WHERE' => 'AND',
		'GROUP BY' => ',',
		'HAVING' => 'AND',
		'ORDER BY' => ',',
		'LIMIT' => false,
		'OFFSET' => false,
		'SET' => ',',
		'VALUES' => ',',
		'INTO' => false,
	];

	/** @var array  clauses */
	public static $clauseSwitches = [
		'JOIN' => 'FROM',
		'INNER JOIN' => 'FROM',
		'LEFT JOIN' => 'FROM',
		'RIGHT JOIN' => 'FROM',
	];

	/** @var Connection */
	private $connection;

	/** @var array */
	private $setups = [];

	/** @var string */
	private $command;

	/** @var array */
	private $clauses = [];

	/** @var array */
	private $flags = [];

	/** @var array */
	private $cursor;

	/** @var HashMap  normalized clauses */
	private static $normalizer;


	/**
	 * @param  Connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;

		if (self::$normalizer === null) {
			self::$normalizer = new HashMap([__CLASS__, '_formatClause']);
		}
	}


	/**
	 * Appends new argument to the clause.
	 * @param  string clause name
	 * @param  array  arguments
	 * @return self
	 */
	public function __call($clause, $args)
	{
		$clause = self::$normalizer->$clause;

		// lazy initialization
		if ($this->command === null) {
			if (isset(self::$masks[$clause])) {
				$this->clauses = array_fill_keys(self::$masks[$clause], null);
			}
			$this->cursor = &$this->clauses[$clause];
			$this->cursor = [];
			$this->command = $clause;
		}

		// auto-switch to a clause
		if (isset(self::$clauseSwitches[$clause])) {
			$this->cursor = &$this->clauses[self::$clauseSwitches[$clause]];
		}

		if (array_key_exists($clause, $this->clauses)) {
			// append to clause
			$this->cursor = &$this->clauses[$clause];

			// TODO: really delete?
			if ($args === [self::REMOVE]) {
				$this->cursor = null;
				return $this;
			}

			if (isset(self::$separators[$clause])) {
				$sep = self::$separators[$clause];
				if ($sep === false) { // means: replace
					$this->cursor = [];

				} elseif (!empty($this->cursor)) {
					$this->cursor[] = $sep;
				}
			}

		} else {
			// append to currect flow
			if ($args === [self::REMOVE]) {
				return $this;
			}

			$this->cursor[] = $clause;
		}

		if ($this->cursor === null) {
			$this->cursor = [];
		}

		// special types or argument
		if (count($args) === 1) {
			$arg = $args[0];
			// TODO: really ignore true?
			if ($arg === true) { // flag
				return $this;

			} elseif (is_string($arg) && preg_match('#^[a-z:_][a-z0-9_.:]*\z#i', $arg)) { // identifier
				$args = [$clause === 'AS' ? '%N' : '%n', $arg];

			} elseif (is_array($arg) || ($arg instanceof \Traversable && !$arg instanceof self)) { // any array
				if (isset(self::$modifiers[$clause])) {
					$args = [self::$modifiers[$clause], $arg];

				} elseif (is_string(key($arg))) { // associative array
					$args = ['%a', $arg];
				}
			} // case $arg === false is handled above
		}

		foreach ($args as $arg) {
			if ($arg instanceof self) {
				$arg = new Literal("($arg)");
			}
			$this->cursor[] = $arg;
		}

		return $this;
	}


	/**
	 * Switch to a clause.
	 * @param  string clause name
	 * @return self
	 */
	public function clause($clause)
	{
		$this->cursor = &$this->clauses[self::$normalizer->$clause];
		if ($this->cursor === null) {
			$this->cursor = [];
		}

		return $this;
	}


	/**
	 * Removes a clause.
	 * @param  string clause name
	 * @return self
	 */
	public function removeClause($clause)
	{
		$this->clauses[self::$normalizer->$clause] = null;
		return $this;
	}


	/**
	 * Change a SQL flag.
	 * @param  string  flag name
	 * @param  bool  value
	 * @return self
	 */
	public function setFlag($flag, $value = true)
	{
		$flag = strtoupper($flag);
		if ($value) {
			$this->flags[$flag] = true;
		} else {
			unset($this->flags[$flag]);
		}
		return $this;
	}


	/**
	 * Is a flag set?
	 * @param  string  flag name
	 * @return bool
	 */
	final public function getFlag($flag)
	{
		return isset($this->flags[strtoupper($flag)]);
	}


	/**
	 * Returns SQL command.
	 * @return string
	 */
	final public function getCommand()
	{
		return $this->command;
	}


	/**
	 * Returns the dibi connection.
	 * @return Connection
	 */
	final public function getConnection()
	{
		return $this->connection;
	}


	/**
	 * Adds Result setup.
	 * @param  string  method
	 * @param  mixed   args
	 * @return self
	 */
	public function setupResult($method)
	{
		$this->setups[] = func_get_args();
		return $this;
	}


	/********************* executing ****************d*g**/


	/**
	 * Generates and executes SQL query.
	 * @param  mixed what to return?
	 * @return Result|int  result set or number of affected rows
	 * @throws Exception
	 */
	public function execute($return = null)
	{
		$res = $this->query($this->_export());
		switch ($return) {
			case \dibi::IDENTIFIER:
				return $this->connection->getInsertId();
			case \dibi::AFFECTED_ROWS:
				return $this->connection->getAffectedRows();
			default:
				return $res;
		}
	}


	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return Row|false
	 */
	public function fetch()
	{
		if ($this->command === 'SELECT' && !$this->clauses['LIMIT']) {
			return $this->query($this->_export(null, ['%lmt', 1]))->fetch();
		} else {
			return $this->query($this->_export())->fetch();
		}
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, false if no next record
	 */
	public function fetchSingle()
	{
		if ($this->command === 'SELECT' && !$this->clauses['LIMIT']) {
			return $this->query($this->_export(null, ['%lmt', 1]))->fetchSingle();
		} else {
			return $this->query($this->_export())->fetchSingle();
		}
	}


	/**
	 * Fetches all records from table.
	 * @param  int  offset
	 * @param  int  limit
	 * @return array
	 */
	public function fetchAll($offset = null, $limit = null)
	{
		return $this->query($this->_export(null, ['%ofs %lmt', $offset, $limit]))->fetchAll();
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 * @return array
	 */
	public function fetchAssoc($assoc)
	{
		return $this->query($this->_export())->fetchAssoc($assoc);
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 */
	public function fetchPairs($key = null, $value = null)
	{
		return $this->query($this->_export())->fetchPairs($key, $value);
	}


	/**
	 * Required by the IteratorAggregate interface.
	 * @param  int  offset
	 * @param  int  limit
	 * @return ResultIterator
	 */
	public function getIterator($offset = null, $limit = null)
	{
		return $this->query($this->_export(null, ['%ofs %lmt', $offset, $limit]))->getIterator();
	}


	/**
	 * Generates and prints SQL query or it's part.
	 * @param  string clause name
	 * @return bool
	 */
	public function test($clause = null)
	{
		return $this->connection->test($this->_export($clause));
	}


	/**
	 * @return int
	 */
	public function count()
	{
		return Helpers::intVal($this->query([
			'SELECT COUNT(*) FROM (%ex', $this->_export(), ') [data]',
		])->fetchSingle());
	}


	/**
	 * @return Result|int
	 */
	private function query($args)
	{
		$res = $this->connection->query($args);
		foreach ($this->setups as $setup) {
			call_user_func_array([$res, array_shift($setup)], $setup);
		}
		return $res;
	}


	/********************* exporting ****************d*g**/


	/**
	 * @return DataSource
	 */
	public function toDataSource()
	{
		return new DataSource($this->connection->translate($this->_export()), $this->connection);
	}


	/**
	 * Returns SQL query.
	 * @return string
	 */
	final public function __toString()
	{
		try {
			return $this->connection->translate($this->_export());
		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
			return '';
		}
	}


	/**
	 * Generates parameters for Translator.
	 * @param  string clause name
	 * @return array
	 */
	protected function _export($clause = null, $args = [])
	{
		if ($clause === null) {
			$data = $this->clauses;
			if ($this->command === 'SELECT' && ($data['LIMIT'] || $data['OFFSET'])) {
				$args = array_merge(['%lmt %ofs', $data['LIMIT'][0], $data['OFFSET'][0]], $args);
				unset($data['LIMIT'], $data['OFFSET']);
			}

		} else {
			$clause = self::$normalizer->$clause;
			if (array_key_exists($clause, $this->clauses)) {
				$data = [$clause => $this->clauses[$clause]];
			} else {
				return [];
			}
		}

		foreach ($data as $clause => $statement) {
			if ($statement !== null) {
				$args[] = $clause;
				if ($clause === $this->command && $this->flags) {
					$args[] = implode(' ', array_keys($this->flags));
				}
				foreach ($statement as $arg) {
					$args[] = $arg;
				}
			}
		}

		return $args;
	}


	/**
	 * Format camelCase clause name to UPPER CASE.
	 * @param  string
	 * @return string
	 * @internal
	 */
	public static function _formatClause($s)
	{
		if ($s === 'order' || $s === 'group') {
			$s .= 'By';
			trigger_error("Did you mean '$s'?", E_USER_NOTICE);
		}
		return strtoupper(preg_replace('#[a-z](?=[A-Z])#', '$0 ', $s));
	}


	public function __clone()
	{
		// remove references
		foreach ($this->clauses as $clause => $val) {
			$this->clauses[$clause] = &$val;
			unset($val);
		}
		$this->cursor = &$foo;
	}
}
