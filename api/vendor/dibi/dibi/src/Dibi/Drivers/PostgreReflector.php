<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The reflector for PostgreSQL database.
 */
class PostgreReflector implements Dibi\Reflector
{
	use Dibi\Strict;

	/** @var Dibi\Driver */
	private $driver;

	/** @var string */
	private $version;


	public function __construct(Dibi\Driver $driver, string $version)
	{
		$this->driver = $driver;
		$this->version = $version;
	}


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$query = "
			SELECT
				table_name AS name,
				CASE table_type
					WHEN 'VIEW' THEN 1
					ELSE 0
				END AS view
			FROM
				information_schema.tables
			WHERE
				table_schema = ANY (current_schemas(false))";

		if ($this->version >= 9.3) {
			$query .= '
				UNION ALL
				SELECT
					matviewname, 1
				FROM
					pg_matviews
				WHERE
					schemaname = ANY (current_schemas(false))';
		}

		$res = $this->driver->query($query);
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
		$_table = $this->driver->escapeText($this->driver->escapeIdentifier($table));
		$res = $this->driver->query("
			SELECT indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid AND pg_index.indisprimary
			WHERE pg_class.oid = $_table::regclass
		");
		$primary = (int) $res->fetch(true)['indkey'];

		$res = $this->driver->query("
			SELECT *
			FROM information_schema.columns c
			JOIN pg_class ON pg_class.relname = c.table_name
			JOIN pg_namespace nsp ON nsp.oid = pg_class.relnamespace AND nsp.nspname = c.table_schema
			WHERE pg_class.oid = $_table::regclass
			ORDER BY c.ordinal_position
		");

		if (!$res->getRowCount()) {
			$res = $this->driver->query("
				SELECT
					a.attname AS column_name,
					pg_type.typname AS udt_name,
					a.attlen AS numeric_precision,
					a.atttypmod-4 AS character_maximum_length,
					NOT a.attnotnull AS is_nullable,
					a.attnum AS ordinal_position,
					pg_get_expr(adef.adbin, adef.adrelid) AS column_default
				FROM
					pg_attribute a
					JOIN pg_type ON a.atttypid = pg_type.oid
					JOIN pg_class cls ON a.attrelid = cls.oid
					LEFT JOIN pg_attrdef adef ON adef.adnum = a.attnum AND adef.adrelid = a.attrelid
				WHERE
					cls.relkind IN ('r', 'v', 'mv')
					AND a.attrelid = $_table::regclass
					AND a.attnum > 0
					AND NOT a.attisdropped
				ORDER BY ordinal_position
			");
		}

		$columns = [];
		while ($row = $res->fetch(true)) {
			$size = (int) max($row['character_maximum_length'], $row['numeric_precision']);
			$columns[] = [
				'name' => $row['column_name'],
				'table' => $table,
				'nativetype' => strtoupper($row['udt_name']),
				'size' => $size > 0 ? $size : null,
				'nullable' => $row['is_nullable'] === 'YES' || $row['is_nullable'] === 't' || $row['is_nullable'] === true,
				'default' => $row['column_default'],
				'autoincrement' => (int) $row['ordinal_position'] === $primary && substr($row['column_default'] ?? '', 0, 7) === 'nextval',
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
		$_table = $this->driver->escapeText($this->driver->escapeIdentifier($table));
		$res = $this->driver->query("
			SELECT
				a.attnum AS ordinal_position,
				a.attname AS column_name
			FROM
				pg_attribute a
				JOIN pg_class cls ON a.attrelid = cls.oid
			WHERE
				a.attrelid = $_table::regclass
				AND a.attnum > 0
				AND NOT a.attisdropped
			ORDER BY ordinal_position
		");

		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[$row['ordinal_position']] = $row['column_name'];
		}

		$res = $this->driver->query("
			SELECT pg_class2.relname, indisunique, indisprimary, indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid
			INNER JOIN pg_class as pg_class2 on pg_class2.oid = pg_index.indexrelid
			WHERE pg_class.oid = $_table::regclass
		");

		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['relname']]['name'] = $row['relname'];
			$indexes[$row['relname']]['unique'] = $row['indisunique'] === 't' || $row['indisunique'] === true;
			$indexes[$row['relname']]['primary'] = $row['indisprimary'] === 't' || $row['indisprimary'] === true;
			$indexes[$row['relname']]['columns'] = [];
			foreach (explode(' ', $row['indkey']) as $index) {
				if (isset($columns[$index])) {
					$indexes[$row['relname']]['columns'][] = $columns[$index];
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
		$_table = $this->driver->escapeText($this->driver->escapeIdentifier($table));

		$res = $this->driver->query("
			SELECT
				c.conname AS name,
				lt.attname AS local,
				c.confrelid::regclass AS table,
				ft.attname AS foreign,

				CASE c.confupdtype
					WHEN 'a' THEN 'NO ACTION'
					WHEN 'r' THEN 'RESTRICT'
					WHEN 'c' THEN 'CASCADE'
					WHEN 'n' THEN 'SET NULL'
					WHEN 'd' THEN 'SET DEFAULT'
					ELSE 'UNKNOWN'
				END AS \"onUpdate\",

				CASE c.confdeltype
					WHEN 'a' THEN 'NO ACTION'
					WHEN 'r' THEN 'RESTRICT'
					WHEN 'c' THEN 'CASCADE'
					WHEN 'n' THEN 'SET NULL'
					WHEN 'd' THEN 'SET DEFAULT'
					ELSE 'UNKNOWN'
				END AS \"onDelete\",

				c.conkey,
				lt.attnum AS lnum,
				c.confkey,
				ft.attnum AS fnum
			FROM
				pg_constraint c
				JOIN pg_attribute lt ON c.conrelid = lt.attrelid AND lt.attnum = ANY (c.conkey)
				JOIN pg_attribute ft ON c.confrelid = ft.attrelid AND ft.attnum = ANY (c.confkey)
			WHERE
				c.contype = 'f'
				AND
				c.conrelid = $_table::regclass
		");

		$fKeys = $references = [];
		while ($row = $res->fetch(true)) {
			if (!isset($fKeys[$row['name']])) {
				$fKeys[$row['name']] = [
					'name' => $row['name'],
					'table' => $row['table'],
					'local' => [],
					'foreign' => [],
					'onUpdate' => $row['onUpdate'],
					'onDelete' => $row['onDelete'],
				];

				$l = explode(',', trim($row['conkey'], '{}'));
				$f = explode(',', trim($row['confkey'], '{}'));

				$references[$row['name']] = array_combine($l, $f);
			}

			if (
				isset($references[$row['name']][$row['lnum']])
				&& $references[$row['name']][$row['lnum']] === $row['fnum']
			) {
				$fKeys[$row['name']]['local'][] = $row['local'];
				$fKeys[$row['name']]['foreign'][] = $row['foreign'];
			}
		}

		return $fKeys;
	}
}
