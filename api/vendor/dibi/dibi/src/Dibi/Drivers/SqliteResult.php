<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Helpers;


/**
 * The driver for SQLite result set.
 */
class SqliteResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var \SQLite3Result */
	private $resultSet;


	public function __construct(\SQLite3Result $resultSet)
	{
		$this->resultSet = $resultSet;
	}


	/**
	 * Returns the number of rows in a result set.
	 * @throws Dibi\NotSupportedException
	 */
	public function getRowCount(): int
	{
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc  true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Helpers::false2Null($this->resultSet->fetchArray($assoc ? SQLITE3_ASSOC : SQLITE3_NUM));
	}


	/**
	 * Moves cursor position without fetching row.
	 * @throws Dibi\NotSupportedException
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		$this->resultSet->finalize();
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = $this->resultSet->numColumns();
		$columns = [];
		static $types = [SQLITE3_INTEGER => 'int', SQLITE3_FLOAT => 'float', SQLITE3_TEXT => 'text', SQLITE3_BLOB => 'blob', SQLITE3_NULL => 'null'];
		for ($i = 0; $i < $count; $i++) {
			$columns[] = [
				'name' => $this->resultSet->columnName($i),
				'table' => null,
				'fullname' => $this->resultSet->columnName($i),
				'nativetype' => $types[$this->resultSet->columnType($i)] ?? null, // buggy in PHP 7.4.4 & 7.3.16, bug 79414
			];
		}

		return $columns;
	}


	/**
	 * Returns the result set resource.
	 */
	public function getResultResource(): \SQLite3Result
	{
		return $this->resultSet;
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}
}
