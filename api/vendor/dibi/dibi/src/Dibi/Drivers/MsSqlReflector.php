<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi reflector for MS SQL databases.
 * @internal
 */
class MsSqlReflector implements Dibi\Reflector
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
	 * @return array
	 */
	public function getTables()
	{
		$res = $this->driver->query('
			SELECT TABLE_NAME, TABLE_TYPE
			FROM INFORMATION_SCHEMA.TABLES
		');
		$tables = [];
		while ($row = $res->fetch(false)) {
			$tables[] = [
				'name' => $row[0],
				'view' => isset($row[1]) && $row[1] === 'VIEW',
			];
		}
		return $tables;
	}


	/**
	 * Returns count of rows in a table
	 * @param  string
	 * @return int
	 */
	public function getTableCount($table, $fallback = true)
	{
		if (empty($table)) {
			return false;
		}
		$result = $this->driver->query("
			SELECT MAX(rowcnt)
			FROM sys.sysindexes
			WHERE id=OBJECT_ID({$this->driver->escapeIdentifier($table)})
		");
		$row = $result->fetch(false);

		if (!is_array($row) || count($row) < 1) {
			if ($fallback) {
				$row = $this->driver->query("SELECT COUNT(*) FROM {$this->driver->escapeIdentifier($table)}")->fetch(false);
				$count = Dibi\Helpers::intVal($row[0]);
			} else {
				$count = false;
			}
		} else {
			$count = Dibi\Helpers::intVal($row[0]);
		}

		return $count;
	}


	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$res = $this->driver->query("
			SELECT * FROM
			INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = {$this->driver->escapeText($table)}
			ORDER BY TABLE_NAME, ORDINAL_POSITION
		");
		$columns = [];
		while ($row = $res->fetch(true)) {
			$size = false;
			$type = strtoupper($row['DATA_TYPE']);

			$size_cols = [
				'DATETIME' => 'DATETIME_PRECISION',
				'DECIMAL' => 'NUMERIC_PRECISION',
				'CHAR' => 'CHARACTER_MAXIMUM_LENGTH',
				'NCHAR' => 'CHARACTER_OCTET_LENGTH',
				'NVARCHAR' => 'CHARACTER_OCTET_LENGTH',
				'VARCHAR' => 'CHARACTER_OCTET_LENGTH',
			];

			if (isset($size_cols[$type])) {
				if ($size_cols[$type]) {
					$size = $row[$size_cols[$type]];
				}
			}

			$columns[] = [
				'name' => $row['COLUMN_NAME'],
				'table' => $table,
				'nativetype' => $type,
				'size' => $size,
				'unsigned' => null,
				'nullable' => $row['IS_NULLABLE'] === 'YES',
				'default' => $row['COLUMN_DEFAULT'],
				'autoincrement' => false,
				'vendor' => $row,
			];
		}

		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		$res = $this->driver->query(
			"SELECT ind.name index_name, ind.index_id, ic.index_column_id,
					col.name column_name, ind.is_unique, ind.is_primary_key
			FROM sys.indexes ind
			INNER JOIN sys.index_columns ic ON
				(ind.object_id = ic.object_id AND ind.index_id = ic.index_id)
			INNER JOIN sys.columns col ON
				(ic.object_id = col.object_id and ic.column_id = col.column_id)
			INNER JOIN sys.tables t ON
				(ind.object_id = t.object_id)
			WHERE t.name = {$this->driver->escapeText($table)}
				AND t.is_ms_shipped = 0
			ORDER BY
				t.name, ind.name, ind.index_id, ic.index_column_id
		");

		$indexes = [];
		while ($row = $res->fetch(true)) {
			$index_name = $row['index_name'];

			if (!isset($indexes[$index_name])) {
				$indexes[$index_name] = [];
				$indexes[$index_name]['name'] = $index_name;
				$indexes[$index_name]['unique'] = (bool) $row['is_unique'];
				$indexes[$index_name]['primary'] = (bool) $row['is_primary_key'];
				$indexes[$index_name]['columns'] = [];
			}
			$indexes[$index_name]['columns'][] = $row['column_name'];
		}

		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		$res = $this->driver->query("
			SELECT f.name AS foreign_key,
			OBJECT_NAME(f.parent_object_id) AS table_name,
			COL_NAME(fc.parent_object_id,
			fc.parent_column_id) AS column_name,
			OBJECT_NAME (f.referenced_object_id) AS reference_table_name,
			COL_NAME(fc.referenced_object_id,
			fc.referenced_column_id) AS reference_column_name,
			fc.*
			FROM sys.foreign_keys AS f
			INNER JOIN sys.foreign_key_columns AS fc
			ON f.OBJECT_ID = fc.constraint_object_id
			WHERE OBJECT_NAME(f.parent_object_id) = {$this->driver->escapeText($table)}
		");

		$keys = [];
		while ($row = $res->fetch(true)) {
			$key_name = $row['foreign_key'];

			if (!isset($keys[$key_name])) {
				$keys[$key_name]['name'] = $row['foreign_key']; // foreign key name
				$keys[$key_name]['local'] = [$row['column_name']]; // local columns
				$keys[$key_name]['table'] = $row['reference_table_name']; // referenced table
				$keys[$key_name]['foreign'] = [$row['reference_column_name']]; // referenced columns
				$keys[$key_name]['onDelete'] = false;
				$keys[$key_name]['onUpdate'] = false;
			} else {
				$keys[$key_name]['local'][] = $row['column_name']; // local columns
				$keys[$key_name]['foreign'][] = $row['reference_column_name']; // referenced columns
			}
		}
		return array_values($keys);
	}
}
