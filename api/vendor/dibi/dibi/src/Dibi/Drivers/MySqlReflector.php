<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi reflector for MySQL databases.
 * @internal
 */
class MySqlReflector implements Dibi\Reflector
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
		$res = $this->driver->query('SHOW FULL TABLES');
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
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$res = $this->driver->query("SHOW FULL COLUMNS FROM {$this->driver->escapeIdentifier($table)}");
		$columns = [];
		while ($row = $res->fetch(true)) {
			$type = explode('(', $row['Type']);
			$columns[] = [
				'name' => $row['Field'],
				'table' => $table,
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : null,
				'unsigned' => (bool) strstr($row['Type'], 'unsigned'),
				'nullable' => $row['Null'] === 'YES',
				'default' => $row['Default'],
				'autoincrement' => $row['Extra'] === 'auto_increment',
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
		$res = $this->driver->query("SHOW INDEX FROM {$this->driver->escapeIdentifier($table)}");
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['Key_name']]['name'] = $row['Key_name'];
			$indexes[$row['Key_name']]['unique'] = !$row['Non_unique'];
			$indexes[$row['Key_name']]['primary'] = $row['Key_name'] === 'PRIMARY';
			$indexes[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 * @throws Dibi\NotSupportedException
	 */
	public function getForeignKeys($table)
	{
		$data = $this->driver->query("SELECT `ENGINE` FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = {$this->driver->escapeText($table)}")->fetch(true);
		if ($data['ENGINE'] !== 'InnoDB') {
			throw new Dibi\NotSupportedException("Foreign keys are not supported in {$data['ENGINE']} tables.");
		}

		$res = $this->driver->query("
			SELECT rc.CONSTRAINT_NAME, rc.UPDATE_RULE, rc.DELETE_RULE, kcu.REFERENCED_TABLE_NAME,
				GROUP_CONCAT(kcu.REFERENCED_COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION) AS REFERENCED_COLUMNS,
				GROUP_CONCAT(kcu.COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION) AS COLUMNS
			FROM information_schema.REFERENTIAL_CONSTRAINTS rc
			INNER JOIN information_schema.KEY_COLUMN_USAGE kcu ON
				kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
				AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
			WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
				AND rc.TABLE_NAME = {$this->driver->escapeText($table)}
			GROUP BY rc.CONSTRAINT_NAME
		");

		$foreignKeys = [];
		while ($row = $res->fetch(true)) {
			$keyName = $row['CONSTRAINT_NAME'];

			$foreignKeys[$keyName]['name'] = $keyName;
			$foreignKeys[$keyName]['local'] = explode(',', $row['COLUMNS']);
			$foreignKeys[$keyName]['table'] = $row['REFERENCED_TABLE_NAME'];
			$foreignKeys[$keyName]['foreign'] = explode(',', $row['REFERENCED_COLUMNS']);
			$foreignKeys[$keyName]['onDelete'] = $row['DELETE_RULE'];
			$foreignKeys[$keyName]['onUpdate'] = $row['UPDATE_RULE'];
		}
		return array_values($foreignKeys);
	}
}
