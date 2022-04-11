<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The reflector for Oracle database.
 */
class OracleReflector implements Dibi\Reflector
{
	use Dibi\Strict;

	/** @var Dibi\Driver */
	private $driver;


	public function __construct(Dibi\Driver $driver)
	{
		$this->driver = $driver;
	}


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = $this->driver->query('SELECT * FROM cat');
		$tables = [];
		while ($row = $res->fetch(false)) {
			if ($row[1] === 'TABLE' || $row[1] === 'VIEW') {
				$tables[] = [
					'name' => $row[0],
					'view' => $row[1] === 'VIEW',
				];
			}
		}

		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = $this->driver->query('SELECT * FROM "ALL_TAB_COLUMNS" WHERE "TABLE_NAME" = ' . $this->driver->escapeText($table));
		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[] = [
				'table' => $row['TABLE_NAME'],
				'name' => $row['COLUMN_NAME'],
				'nativetype' => $row['DATA_TYPE'],
				'size' => $row['DATA_LENGTH'] ?? null,
				'nullable' => $row['NULLABLE'] === 'Y',
				'default' => $row['DATA_DEFAULT'],
				'vendor' => $row,
			];
		}

		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table.
	 */
	public function getIndexes(string $table): array
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		throw new Dibi\NotImplementedException;
	}
}
