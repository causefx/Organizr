<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Default implementation of IDataSource for dibi.
 */
class DataSource implements IDataSource
{
	use Strict;

	/** @var Connection */
	private $connection;

	/** @var string */
	private $sql;

	/** @var Result */
	private $result;

	/** @var int */
	private $count;

	/** @var int */
	private $totalCount;

	/** @var array */
	private $cols = [];

	/** @var array */
	private $sorting = [];

	/** @var array */
	private $conds = [];

	/** @var int|null */
	private $offset;

	/** @var int|null */
	private $limit;


	/**
	 * @param  string  SQL command or table or view name, as data source
	 * @param  Connection  connection
	 */
	public function __construct($sql, Connection $connection)
	{
		if (strpbrk($sql, " \t\r\n") === false) {
			$this->sql = $connection->getDriver()->escapeIdentifier($sql); // table name
		} else {
			$this->sql = '(' . $sql . ') t'; // SQL command
		}
		$this->connection = $connection;
	}


	/**
	 * Selects columns to query.
	 * @param  string|array  column name or array of column names
	 * @param  string        column alias
	 * @return self
	 */
	public function select($col, $as = null)
	{
		if (is_array($col)) {
			$this->cols = $col;
		} else {
			$this->cols[$col] = $as;
		}
		$this->result = null;
		return $this;
	}


	/**
	 * Adds conditions to query.
	 * @param  mixed  conditions
	 * @return self
	 */
	public function where($cond)
	{
		if (is_array($cond)) {
			// TODO: not consistent with select and orderBy
			$this->conds[] = $cond;
		} else {
			$this->conds[] = func_get_args();
		}
		$this->result = $this->count = null;
		return $this;
	}


	/**
	 * Selects columns to order by.
	 * @param  string|array  column name or array of column names
	 * @param  string        sorting direction
	 * @return self
	 */
	public function orderBy($row, $sorting = 'ASC')
	{
		if (is_array($row)) {
			$this->sorting = $row;
		} else {
			$this->sorting[$row] = $sorting;
		}
		$this->result = null;
		return $this;
	}


	/**
	 * Limits number of rows.
	 * @param  int|null limit
	 * @param  int offset
	 * @return self
	 */
	public function applyLimit($limit, $offset = null)
	{
		$this->limit = $limit;
		$this->offset = $offset;
		$this->result = $this->count = null;
		return $this;
	}


	/**
	 * Returns the dibi connection.
	 * @return Connection
	 */
	final public function getConnection()
	{
		return $this->connection;
	}


	/********************* executing ****************d*g**/


	/**
	 * Returns (and queries) Result.
	 * @return Result
	 */
	public function getResult()
	{
		if ($this->result === null) {
			$this->result = $this->connection->nativeQuery($this->__toString());
		}
		return $this->result;
	}


	/**
	 * @return ResultIterator
	 */
	public function getIterator()
	{
		return $this->getResult()->getIterator();
	}


	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return Row|false
	 */
	public function fetch()
	{
		return $this->getResult()->fetch();
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, false if no next record
	 */
	public function fetchSingle()
	{
		return $this->getResult()->fetchSingle();
	}


	/**
	 * Fetches all records from table.
	 * @return array
	 */
	public function fetchAll()
	{
		return $this->getResult()->fetchAll();
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 * @return array
	 */
	public function fetchAssoc($assoc)
	{
		return $this->getResult()->fetchAssoc($assoc);
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 */
	public function fetchPairs($key = null, $value = null)
	{
		return $this->getResult()->fetchPairs($key, $value);
	}


	/**
	 * Discards the internal cache.
	 * @return void
	 */
	public function release()
	{
		$this->result = $this->count = $this->totalCount = null;
	}


	/********************* exporting ****************d*g**/


	/**
	 * Returns this data source wrapped in Fluent object.
	 * @return Fluent
	 */
	public function toFluent()
	{
		return $this->connection->select('*')->from('(%SQL) t', $this->__toString());
	}


	/**
	 * Returns this data source wrapped in DataSource object.
	 * @return DataSource
	 */
	public function toDataSource()
	{
		return new self($this->__toString(), $this->connection);
	}


	/**
	 * Returns SQL query.
	 * @return string
	 */
	public function __toString()
	{
		try {
			return $this->connection->translate('
SELECT %n', (empty($this->cols) ? '*' : $this->cols), '
FROM %SQL', $this->sql, '
%ex', $this->conds ? ['WHERE %and', $this->conds] : null, '
%ex', $this->sorting ? ['ORDER BY %by', $this->sorting] : null, '
%ofs %lmt', $this->offset, $this->limit
			);
		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
			return '';
		}
	}


	/********************* counting ****************d*g**/


	/**
	 * Returns the number of rows in a given data source.
	 * @return int
	 */
	public function count()
	{
		if ($this->count === null) {
			$this->count = $this->conds || $this->offset || $this->limit
				? Helpers::intVal($this->connection->nativeQuery(
					'SELECT COUNT(*) FROM (' . $this->__toString() . ') t'
				)->fetchSingle())
				: $this->getTotalCount();
		}
		return $this->count;
	}


	/**
	 * Returns the number of rows in a given data source.
	 * @return int
	 */
	public function getTotalCount()
	{
		if ($this->totalCount === null) {
			$this->totalCount = Helpers::intVal($this->connection->nativeQuery(
				'SELECT COUNT(*) FROM ' . $this->sql
			)->fetchSingle());
		}
		return $this->totalCount;
	}
}
