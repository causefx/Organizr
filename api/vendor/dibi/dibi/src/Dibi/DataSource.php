<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Default implementation of IDataSource.
 */
class DataSource implements IDataSource
{
	use Strict;

	/** @var Connection */
	private $connection;

	/** @var string */
	private $sql;

	/** @var Result|null */
	private $result;

	/** @var int|null */
	private $count;

	/** @var int|null */
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
	 * @param  string  $sql  command or table or view name, as data source
	 */
	public function __construct(string $sql, Connection $connection)
	{
		$this->sql = strpbrk($sql, " \t\r\n") === false
			? $connection->getDriver()->escapeIdentifier($sql) // table name
			: '(' . $sql . ') t'; // SQL command
		$this->connection = $connection;
	}


	/**
	 * Selects columns to query.
	 * @param  string|array  $col  column name or array of column names
	 * @param  string  $as        column alias
	 */
	public function select($col, ?string $as = null): self
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
	 */
	public function where($cond): self
	{
		$this->conds[] = is_array($cond)
			? $cond // TODO: not consistent with select and orderBy
			: func_get_args();
		$this->result = $this->count = null;
		return $this;
	}


	/**
	 * Selects columns to order by.
	 * @param  string|array  $row  column name or array of column names
	 */
	public function orderBy($row, string $direction = 'ASC'): self
	{
		if (is_array($row)) {
			$this->sorting = $row;
		} else {
			$this->sorting[$row] = $direction;
		}

		$this->result = null;
		return $this;
	}


	/**
	 * Limits number of rows.
	 */
	public function applyLimit(int $limit, ?int $offset = null): self
	{
		$this->limit = $limit;
		$this->offset = $offset;
		$this->result = $this->count = null;
		return $this;
	}


	final public function getConnection(): Connection
	{
		return $this->connection;
	}


	/********************* executing ****************d*g**/


	/**
	 * Returns (and queries) Result.
	 */
	public function getResult(): Result
	{
		if ($this->result === null) {
			$this->result = $this->connection->nativeQuery($this->__toString());
		}

		return $this->result;
	}


	public function getIterator(): ResultIterator
	{
		return $this->getResult()->getIterator();
	}


	/**
	 * Generates, executes SQL query and fetches the single row.
	 */
	public function fetch(): ?Row
	{
		return $this->getResult()->fetch();
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, null if no next record
	 */
	public function fetchSingle()
	{
		return $this->getResult()->fetchSingle();
	}


	/**
	 * Fetches all records from table.
	 */
	public function fetchAll(): array
	{
		return $this->getResult()->fetchAll();
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->getResult()->fetchAssoc($assoc);
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 */
	public function fetchPairs(?string $key = null, ?string $value = null): array
	{
		return $this->getResult()->fetchPairs($key, $value);
	}


	/**
	 * Discards the internal cache.
	 */
	public function release(): void
	{
		$this->result = $this->count = $this->totalCount = null;
	}


	/********************* exporting ****************d*g**/


	/**
	 * Returns this data source wrapped in Fluent object.
	 */
	public function toFluent(): Fluent
	{
		return $this->connection->select('*')->from('(%SQL) t', $this->__toString());
	}


	/**
	 * Returns this data source wrapped in DataSource object.
	 */
	public function toDataSource(): self
	{
		return new self($this->__toString(), $this->connection);
	}


	/**
	 * Returns SQL query.
	 */
	public function __toString(): string
	{
		try {
			return $this->connection->translate(
				"\nSELECT %n",
				(empty($this->cols) ? '*' : $this->cols),
				"\nFROM %SQL",
				$this->sql,
				"\n%ex",
				$this->conds ? ['WHERE %and', $this->conds] : null,
				"\n%ex",
				$this->sorting ? ['ORDER BY %by', $this->sorting] : null,
				"\n%ofs %lmt",
				$this->offset,
				$this->limit
			);
		} catch (\Throwable $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
			return '';
		}
	}


	/********************* counting ****************d*g**/


	/**
	 * Returns the number of rows in a given data source.
	 */
	public function count(): int
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
	 */
	public function getTotalCount(): int
	{
		if ($this->totalCount === null) {
			$this->totalCount = Helpers::intVal($this->connection->nativeQuery(
				'SELECT COUNT(*) FROM ' . $this->sql
			)->fetchSingle());
		}

		return $this->totalCount;
	}
}
