<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The driver for MySQL result set.
 */
class MySqliResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var \mysqli_result */
	private $resultSet;

	/** @var bool  Is buffered (seekable and countable)? */
	private $buffered;


	public function __construct(\mysqli_result $resultSet, bool $buffered)
	{
		$this->resultSet = $resultSet;
		$this->buffered = $buffered;
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		if (!$this->buffered) {
			throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
		}

		return $this->resultSet->num_rows;
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc   true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return $assoc
			? $this->resultSet->fetch_assoc()
			: $this->resultSet->fetch_row();
	}


	/**
	 * Moves cursor position without fetching row.
	 * @throws Dibi\Exception
	 */
	public function seek(int $row): bool
	{
		if (!$this->buffered) {
			throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
		}

		return $this->resultSet->data_seek($row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		$this->resultSet->free();
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		static $types;
		if ($types === null) {
			$consts = get_defined_constants(true);
			$types = [];
			foreach ($consts['mysqli'] ?? [] as $key => $value) {
				if (strncmp($key, 'MYSQLI_TYPE_', 12) === 0) {
					$types[$value] = substr($key, 12);
				}
			}

			$types[MYSQLI_TYPE_TINY] = $types[MYSQLI_TYPE_SHORT] = $types[MYSQLI_TYPE_LONG] = 'INT';
		}

		$count = $this->resultSet->field_count;
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = (array) $this->resultSet->fetch_field_direct($i);
			$columns[] = [
				'name' => $row['name'],
				'table' => $row['orgtable'],
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'nativetype' => $types[$row['type']] ?? $row['type'],
				'type' => $row['type'] === MYSQLI_TYPE_TIME ? Dibi\Type::TIME_INTERVAL : null,
				'vendor' => $row,
			];
		}

		return $columns;
	}


	/**
	 * Returns the result set resource.
	 */
	public function getResultResource(): \mysqli_result
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
