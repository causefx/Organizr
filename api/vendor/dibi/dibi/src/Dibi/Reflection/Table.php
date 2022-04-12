<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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

	/** @var Column[]|null */
	private $columns;

	/** @var ForeignKey[]|null */
	private $foreignKeys;

	/** @var Index[]|null */
	private $indexes;

	/** @var Index|null */
	private $primaryKey;


	public function __construct(Dibi\Reflector $reflector, array $info)
	{
		$this->reflector = $reflector;
		$this->name = $info['name'];
		$this->view = !empty($info['view']);
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function isView(): bool
	{
		return $this->view;
	}


	/** @return Column[] */
	public function getColumns(): array
	{
		$this->initColumns();
		return array_values($this->columns);
	}


	/** @return string[] */
	public function getColumnNames(): array
	{
		$this->initColumns();
		$res = [];
		foreach ($this->columns as $column) {
			$res[] = $column->getName();
		}

		return $res;
	}


	public function getColumn(string $name): Column
	{
		$this->initColumns();
		$l = strtolower($name);
		if (isset($this->columns[$l])) {
			return $this->columns[$l];

		} else {
			throw new Dibi\Exception("Table '$this->name' has no column '$name'.");
		}
	}


	public function hasColumn(string $name): bool
	{
		$this->initColumns();
		return isset($this->columns[strtolower($name)]);
	}


	/** @return ForeignKey[] */
	public function getForeignKeys(): array
	{
		$this->initForeignKeys();
		return $this->foreignKeys;
	}


	/** @return Index[] */
	public function getIndexes(): array
	{
		$this->initIndexes();
		return $this->indexes;
	}


	public function getPrimaryKey(): Index
	{
		$this->initIndexes();
		return $this->primaryKey;
	}


	protected function initColumns(): void
	{
		if ($this->columns === null) {
			$this->columns = [];
			foreach ($this->reflector->getColumns($this->name) as $info) {
				$this->columns[strtolower($info['name'])] = new Column($this->reflector, $info);
			}
		}
	}


	protected function initIndexes(): void
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


	protected function initForeignKeys(): void
	{
		throw new Dibi\NotImplementedException;
	}
}
