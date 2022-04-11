<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The reflector for ODBC connections.
 */
class OdbcReflector implements Dibi\Reflector
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
		$res = odbc_tables($this->driver->getResource());
		$tables = [];
		while ($row = odbc_fetch_array($res)) {
			if ($row['TABLE_TYPE'] === 'TABLE' || $row['TABLE_TYPE'] === 'VIEW') {
				$tables[] = [
					'name' => $row['TABLE_NAME'],
					'view' => $row['TABLE_TYPE'] === 'VIEW',
				];
			}
		}

		odbc_free_result($res);
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = odbc_columns($this->driver->getResource());
		$columns = [];
		while ($row = odbc_fetch_array($res)) {
			if ($row['TABLE_NAME'] === $table) {
				$columns[] = [
					'name' => $row['COLUMN_NAME'],
					'table' => $table,
					'nativetype' => $row['TYPE_NAME'],
					'size' => $row['COLUMN_SIZE'],
					'nullable' => (bool) $row['NULLABLE'],
					'default' => $row['COLUMN_DEF'],
				];
			}
		}

		odbc_free_result($res);
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
