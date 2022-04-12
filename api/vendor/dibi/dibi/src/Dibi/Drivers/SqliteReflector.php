<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The reflector for SQLite database.
 */
class SqliteReflector implements Dibi\Reflector
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
		$res = $this->driver->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view')
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view')
			ORDER BY name
		");
		$tables = [];
		while ($row = $res->fetch(true)) {
			$tables[] = $row;
		}

		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = $this->driver->query("PRAGMA table_info({$this->driver->escapeIdentifier($table)})");
		$columns = [];
		while ($row = $res->fetch(true)) {
			$column = $row['name'];
			$type = explode('(', $row['type']);
			$columns[] = [
				'name' => $column,
				'table' => $table,
				'fullname' => "$table.$column",
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : null,
				'nullable' => $row['notnull'] === 0,
				'default' => $row['dflt_value'],
				'autoincrement' => $row['pk'] && $type[0] === 'INTEGER',
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
		$res = $this->driver->query("PRAGMA index_list({$this->driver->escapeIdentifier($table)})");
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['name']]['name'] = $row['name'];
			$indexes[$row['name']]['unique'] = (bool) $row['unique'];
		}

		foreach ($indexes as $index => $values) {
			$res = $this->driver->query("PRAGMA index_info({$this->driver->escapeIdentifier($index)})");
			while ($row = $res->fetch(true)) {
				$indexes[$index]['columns'][$row['seqno']] = $row['name'];
			}
		}

		$columns = $this->getColumns($table);
		foreach ($indexes as $index => $values) {
			$column = $indexes[$index]['columns'][0];
			$primary = false;
			foreach ($columns as $info) {
				if ($column === $info['name']) {
					$primary = $info['vendor']['pk'];
					break;
				}
			}

			$indexes[$index]['primary'] = (bool) $primary;
		}

		if (!$indexes) { // @see http://www.sqlite.org/lang_createtable.html#rowid
			foreach ($columns as $column) {
				if ($column['vendor']['pk']) {
					$indexes[] = [
						'name' => 'ROWID',
						'unique' => true,
						'primary' => true,
						'columns' => [$column['name']],
					];
					break;
				}
			}
		}

		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		$res = $this->driver->query("PRAGMA foreign_key_list({$this->driver->escapeIdentifier($table)})");
		$keys = [];
		while ($row = $res->fetch(true)) {
			$keys[$row['id']]['name'] = $row['id']; // foreign key name
			$keys[$row['id']]['local'][$row['seq']] = $row['from']; // local columns
			$keys[$row['id']]['table'] = $row['table']; // referenced table
			$keys[$row['id']]['foreign'][$row['seq']] = $row['to']; // referenced columns
			$keys[$row['id']]['onDelete'] = $row['on_delete'];
			$keys[$row['id']]['onUpdate'] = $row['on_update'];

			if ($keys[$row['id']]['foreign'][0] == null) {
				$keys[$row['id']]['foreign'] = null;
			}
		}

		return array_values($keys);
	}
}
