<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;

use Dibi;


/**
 * Reflection metadata class for a result set.
 *
 * @property-read array $columns
 * @property-read array $columnNames
 */
class Result
{
	use Dibi\Strict;

	/** @var Dibi\ResultDriver */
	private $driver;

	/** @var array */
	private $columns;

	/** @var array */
	private $names;


	public function __construct(Dibi\ResultDriver $driver)
	{
		$this->driver = $driver;
	}


	/**
	 * @return Column[]
	 */
	public function getColumns()
	{
		$this->initColumns();
		return array_values($this->columns);
	}


	/**
	 * @param  bool
	 * @return string[]
	 */
	public function getColumnNames($fullNames = false)
	{
		$this->initColumns();
		$res = [];
		foreach ($this->columns as $column) {
			$res[] = $fullNames ? $column->getFullName() : $column->getName();
		}
		return $res;
	}


	/**
	 * @param  string
	 * @return Column
	 */
	public function getColumn($name)
	{
		$this->initColumns();
		$l = strtolower($name);
		if (isset($this->names[$l])) {
			return $this->names[$l];

		} else {
			throw new Dibi\Exception("Result set has no column '$name'.");
		}
	}


	/**
	 * @param  string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$this->initColumns();
		return isset($this->names[strtolower($name)]);
	}


	/**
	 * @return void
	 */
	protected function initColumns()
	{
		if ($this->columns === null) {
			$this->columns = [];
			$reflector = $this->driver instanceof Dibi\Reflector ? $this->driver : null;
			foreach ($this->driver->getResultColumns() as $info) {
				$this->columns[] = $this->names[strtolower($info['name'])] = new Column($reflector, $info);
			}
		}
	}
}
