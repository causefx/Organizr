<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The reflector for Firebird/InterBase database.
 */
class FirebirdReflector implements Dibi\Reflector
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
			SELECT TRIM(RDB\$RELATION_NAME),
				CASE RDB\$VIEW_BLR WHEN NULL THEN 'TRUE' ELSE 'FALSE' END
			FROM RDB\$RELATIONS
			WHERE RDB\$SYSTEM_FLAG = 0;
		");
		$tables = [];
		while ($row = $res->fetch(false)) {
			$tables[] = [
				'name' => $row[0],
				'view' => $row[1] === 'TRUE',
			];
		}

		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$table = strtoupper($table);
		$res = $this->driver->query("
			SELECT TRIM(r.RDB\$FIELD_NAME) AS FIELD_NAME,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
					ELSE 'UNKNOWN'
				END AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
				r.RDB\$DEFAULT_VALUE AS DEFAULT_VALUE,
				CASE r.RDB\$NULL_FLAG
					WHEN 1 THEN 'FALSE' ELSE 'TRUE'
				END AS NULLABLE
			FROM RDB\$RELATION_FIELDS r
				LEFT JOIN RDB\$FIELDS f ON r.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
			WHERE r.RDB\$RELATION_NAME = '$table'
			ORDER BY r.RDB\$FIELD_POSITION;
		");
		$columns = [];
		while ($row = $res->fetch(true)) {
			$key = $row['FIELD_NAME'];
			$columns[$key] = [
				'name' => $key,
				'table' => $table,
				'nativetype' => trim($row['FIELD_TYPE']),
				'size' => $row['FIELD_LENGTH'],
				'nullable' => $row['NULLABLE'] === 'TRUE',
				'default' => $row['DEFAULT_VALUE'],
				'autoincrement' => false,
			];
		}

		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table (the constraints are included).
	 */
	public function getIndexes(string $table): array
	{
		$table = strtoupper($table);
		$res = $this->driver->query("
			SELECT TRIM(s.RDB\$INDEX_NAME) AS INDEX_NAME,
				TRIM(s.RDB\$FIELD_NAME) AS FIELD_NAME,
				i.RDB\$UNIQUE_FLAG AS UNIQUE_FLAG,
				i.RDB\$FOREIGN_KEY AS FOREIGN_KEY,
				TRIM(r.RDB\$CONSTRAINT_TYPE) AS CONSTRAINT_TYPE,
				s.RDB\$FIELD_POSITION AS FIELD_POSITION
			FROM RDB\$INDEX_SEGMENTS s
				LEFT JOIN RDB\$INDICES i ON i.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
				LEFT JOIN RDB\$RELATION_CONSTRAINTS r ON r.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
			WHERE UPPER(i.RDB\$RELATION_NAME) = '$table'
			ORDER BY s.RDB\$FIELD_POSITION
		");
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$key = $row['INDEX_NAME'];
			$indexes[$key]['name'] = $key;
			$indexes[$key]['unique'] = $row['UNIQUE_FLAG'] === 1;
			$indexes[$key]['primary'] = $row['CONSTRAINT_TYPE'] === 'PRIMARY KEY';
			$indexes[$key]['table'] = $table;
			$indexes[$key]['columns'][$row['FIELD_POSITION']] = $row['FIELD_NAME'];
		}

		return $indexes;
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		$table = strtoupper($table);
		$res = $this->driver->query("
			SELECT TRIM(s.RDB\$INDEX_NAME) AS INDEX_NAME,
				TRIM(s.RDB\$FIELD_NAME) AS FIELD_NAME,
			FROM RDB\$INDEX_SEGMENTS s
				LEFT JOIN RDB\$RELATION_CONSTRAINTS r ON r.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
			WHERE UPPER(i.RDB\$RELATION_NAME) = '$table'
				AND r.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
			ORDER BY s.RDB\$FIELD_POSITION
		");
		$keys = [];
		while ($row = $res->fetch(true)) {
			$key = $row['INDEX_NAME'];
			$keys[$key] = [
				'name' => $key,
				'column' => $row['FIELD_NAME'],
				'table' => $table,
			];
		}

		return $keys;
	}


	/**
	 * Returns list of indices in given table (the constraints are not listed).
	 */
	public function getIndices(string $table): array
	{
		$res = $this->driver->query("
			SELECT TRIM(RDB\$INDEX_NAME)
			FROM RDB\$INDICES
			WHERE RDB\$RELATION_NAME = UPPER('$table')
				AND RDB\$UNIQUE_FLAG IS NULL
				AND RDB\$FOREIGN_KEY IS NULL;
		");
		$indices = [];
		while ($row = $res->fetch(false)) {
			$indices[] = $row[0];
		}

		return $indices;
	}


	/**
	 * Returns list of constraints in given table.
	 */
	public function getConstraints(string $table): array
	{
		$res = $this->driver->query("
			SELECT TRIM(RDB\$INDEX_NAME)
			FROM RDB\$INDICES
			WHERE RDB\$RELATION_NAME = UPPER('$table')
				AND (
					RDB\$UNIQUE_FLAG IS NOT NULL
					OR RDB\$FOREIGN_KEY IS NOT NULL
			);
		");
		$constraints = [];
		while ($row = $res->fetch(false)) {
			$constraints[] = $row[0];
		}

		return $constraints;
	}


	/**
	 * Returns metadata for all triggers in a table or database.
	 * (Only if user has permissions on ALTER TABLE, INSERT/UPDATE/DELETE record in table)
	 */
	public function getTriggersMeta(?string $table = null): array
	{
		$res = $this->driver->query(
			"
			SELECT TRIM(RDB\$TRIGGER_NAME) AS TRIGGER_NAME,
				TRIM(RDB\$RELATION_NAME) AS TABLE_NAME,
				CASE RDB\$TRIGGER_TYPE
					WHEN 1 THEN 'BEFORE'
					WHEN 2 THEN 'AFTER'
					WHEN 3 THEN 'BEFORE'
					WHEN 4 THEN 'AFTER'
					WHEN 5 THEN 'BEFORE'
					WHEN 6 THEN 'AFTER'
				END AS TRIGGER_TYPE,
				CASE RDB\$TRIGGER_TYPE
					WHEN 1 THEN 'INSERT'
					WHEN 2 THEN 'INSERT'
					WHEN 3 THEN 'UPDATE'
					WHEN 4 THEN 'UPDATE'
					WHEN 5 THEN 'DELETE'
					WHEN 6 THEN 'DELETE'
				END AS TRIGGER_EVENT,
				CASE RDB\$TRIGGER_INACTIVE
					WHEN 1 THEN 'FALSE' ELSE 'TRUE'
				END AS TRIGGER_ENABLED
			FROM RDB\$TRIGGERS
			WHERE RDB\$SYSTEM_FLAG = 0"
			. ($table === null ? ';' : " AND RDB\$RELATION_NAME = UPPER('$table');")
		);
		$triggers = [];
		while ($row = $res->fetch(true)) {
			$triggers[$row['TRIGGER_NAME']] = [
				'name' => $row['TRIGGER_NAME'],
				'table' => $row['TABLE_NAME'],
				'type' => trim($row['TRIGGER_TYPE']),
				'event' => trim($row['TRIGGER_EVENT']),
				'enabled' => trim($row['TRIGGER_ENABLED']) === 'TRUE',
			];
		}

		return $triggers;
	}


	/**
	 * Returns list of triggers for given table.
	 * (Only if user has permissions on ALTER TABLE, INSERT/UPDATE/DELETE record in table)
	 */
	public function getTriggers(?string $table = null): array
	{
		$q = 'SELECT TRIM(RDB$TRIGGER_NAME)
			FROM RDB$TRIGGERS
			WHERE RDB$SYSTEM_FLAG = 0';
		$q .= $table === null
			? ';'
			: " AND RDB\$RELATION_NAME = UPPER('$table')";

		$res = $this->driver->query($q);
		$triggers = [];
		while ($row = $res->fetch(false)) {
			$triggers[] = $row[0];
		}

		return $triggers;
	}


	/**
	 * Returns metadata from stored procedures and their input and output parameters.
	 */
	public function getProceduresMeta(): array
	{
		$res = $this->driver->query("
			SELECT
				TRIM(p.RDB\$PARAMETER_NAME) AS PARAMETER_NAME,
				TRIM(p.RDB\$PROCEDURE_NAME) AS PROCEDURE_NAME,
				CASE p.RDB\$PARAMETER_TYPE
					WHEN 0 THEN 'INPUT'
					WHEN 1 THEN 'OUTPUT'
					ELSE 'UNKNOWN'
				END AS PARAMETER_TYPE,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
					ELSE 'UNKNOWN'
				END AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
				p.RDB\$PARAMETER_NUMBER AS PARAMETER_NUMBER
			FROM RDB\$PROCEDURE_PARAMETERS p
				LEFT JOIN RDB\$FIELDS f ON f.RDB\$FIELD_NAME = p.RDB\$FIELD_SOURCE
			ORDER BY p.RDB\$PARAMETER_TYPE, p.RDB\$PARAMETER_NUMBER;
		");
		$procedures = [];
		while ($row = $res->fetch(true)) {
			$key = $row['PROCEDURE_NAME'];
			$io = trim($row['PARAMETER_TYPE']);
			$num = $row['PARAMETER_NUMBER'];
			$procedures[$key]['name'] = $row['PROCEDURE_NAME'];
			$procedures[$key]['params'][$io][$num]['name'] = $row['PARAMETER_NAME'];
			$procedures[$key]['params'][$io][$num]['type'] = trim($row['FIELD_TYPE']);
			$procedures[$key]['params'][$io][$num]['size'] = $row['FIELD_LENGTH'];
		}

		return $procedures;
	}


	/**
	 * Returns list of stored procedures.
	 */
	public function getProcedures(): array
	{
		$res = $this->driver->query('
			SELECT TRIM(RDB$PROCEDURE_NAME)
			FROM RDB$PROCEDURES;
		');
		$procedures = [];
		while ($row = $res->fetch(false)) {
			$procedures[] = $row[0];
		}

		return $procedures;
	}


	/**
	 * Returns list of generators.
	 */
	public function getGenerators(): array
	{
		$res = $this->driver->query('
			SELECT TRIM(RDB$GENERATOR_NAME)
			FROM RDB$GENERATORS
			WHERE RDB$SYSTEM_FLAG = 0;
		');
		$generators = [];
		while ($row = $res->fetch(false)) {
			$generators[] = $row[0];
		}

		return $generators;
	}


	/**
	 * Returns list of user defined functions (UDF).
	 */
	public function getFunctions(): array
	{
		$res = $this->driver->query('
			SELECT TRIM(RDB$FUNCTION_NAME)
			FROM RDB$FUNCTIONS
			WHERE RDB$SYSTEM_FLAG = 0;
		');
		$functions = [];
		while ($row = $res->fetch(false)) {
			$functions[] = $row[0];
		}

		return $functions;
	}
}
