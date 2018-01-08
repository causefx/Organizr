<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;

use Dibi;


/**
 * Reflection metadata class for a database table.
 *
 * @property-read string $name
 * @property-read bool $view
 * @property-read array $columns
 * @property-read array $columnNames
 * @property-read array $foreignKeys
 * @property-read array $indexes
 * @property-read Index $primaryKey
 */
class Table
{
	use Dibi\Strict;

	/** @var Dibi\Reflector */
	private $reflector;

	/** @var string */
	private $name;

	/** @var bool */
	private $view;

	/** @var array */
	private $columns;

	/** @var array */
	private $foreignKeys;

	/** @var array */
	private $indexes;

	/** @var Index */
	private $primaryKey;


	public function __construct(Dibi\Reflector $reflector, array $info)
	{
		$this->reflector = $reflector;
		$this->name = $info['name'];
		$this->view = !empty($info['view']);
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @return bool
	 */
	public function isView()
	{
		return $this->view;
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
	 * @return string[]
	 */
	public function getColumnNames()
	{
		$this->initColumns();
		$res = [];
		foreach ($this->columns as $column) {
			$res[] = $column->getName();
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
		if (isset($this->columns[$l])) {
			return $this->columns[$l];

		} else {
			throw new Dibi\Exception("Table '$this->name' has no column '$name'.");
		}
	}


	/**
	 * @param  string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$this->initColumns();
		return isset($this->columns[strtolower($name)]);
	}


	/**
	 * @return ForeignKey[]
	 */
	public function getForeignKeys()
	{
		$this->initForeignKeys();
		return $this->foreignKeys;
	}


	/**
	 * @return Index[]
	 */
	public function getIndexes()
	{
		$this->initIndexes();
		return $this->indexes;
	}


	/**
	 * @return Index
	 */
	public function getPrimaryKey()
	{
		$this->initIndexes();
		return $this->primaryKey;
	}


	/**
	 * @return void
	 */
	protected function initColumns()
	{
		if ($this->columns === null) {
			$this->columns = [];
			foreach ($this->reflector->getColumns($this->name) as $info) {
				$this->columns[strtolower($info['name'])] = new Column($this->reflector, $info);
			}
		}
	}


	/**
	 * @return void
	 */
	protected function initIndexes()
	{
		if ($this->indexes === null) {
			$this->initColumns();
			$this->indexes = [];
			foreach ($this->reflector->getIndexes($this->name) as $info) {
				foreach ($info['columns'] as $key => $name) {
					$info['columns'][$key] = $this->columns[strtolower($name)];
				}
				$this->indexes[strtolower($info['name'])] = new Index($info);
				if (!empty($info['primary'])) {
					$this->primaryKey = $this->indexes[strtolower($info['name'])];
				}
			}
		}
	}


	/**
	 * @return void
	 */
	protected function initForeignKeys()
	{
		throw new Dibi\NotImplementedException;
	}
}
