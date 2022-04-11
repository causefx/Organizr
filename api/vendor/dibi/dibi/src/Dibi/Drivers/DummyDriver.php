<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The dummy driver for testing purposes.
 */
class DummyDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	public function disconnect(): void
	{
	}


	public function query(string $sql): ?Dibi\ResultDriver
	{
		return null;
	}


	public function getAffectedRows(): ?int
	{
		return null;
	}


	public function getInsertId(?string $sequence): ?int
	{
		return null;
	}


	public function begin(?string $savepoint = null): void
	{
	}


	public function commit(?string $savepoint = null): void
	{
	}


	public function rollback(?string $savepoint = null): void
	{
	}


	public function getResource()
	{
		return null;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Dibi\Reflector
	{
		return $this;
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		return "N'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeIdentifier(string $value): string
	{
		return '[' . strtr($value, '[]', '  ') . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $value->format("'Y-m-d'");
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return $value->format("'Y-m-d H:i:s.u'");
	}


	public function escapeDateInterval(\DateInterval $value): string
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = strtr($value, ["'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]']);
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'");
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null || $offset) {
			$sql .= ' LIMIT ' . ($limit ?? '-1')
				. ($offset ? ' OFFSET ' . $offset : '');
		}
	}


	/********************* Result ****************d*g**/


	public function getRowCount(): int
	{
		return 0;
	}


	public function fetch(bool $assoc): ?array
	{
		return null;
	}


	public function seek(int $row): bool
	{
		return false;
	}


	public function free(): void
	{
	}


	public function getResultResource()
	{
	}


	public function getResultColumns(): array
	{
		return [];
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}


	/********************* Reflector ****************d*g**/


	public function getTables(): array
	{
		return [];
	}


	public function getColumns(string $table): array
	{
		return [];
	}


	public function getIndexes(string $table): array
	{
		return [];
	}


	public function getForeignKeys(string $table): array
	{
		return [];
	}
}
