<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Helpers;
use PDO;


/**
 * The driver for PDO result set.
 */
class PdoResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var \PDOStatement|null */
	private $resultSet;

	/** @var string */
	private $driverName;


	public function __construct(\PDOStatement $resultSet, string $driverName)
	{
		$this->resultSet = $resultSet;
		$this->driverName = $driverName;
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		return $this->resultSet->rowCount();
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc  true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Helpers::false2Null($this->resultSet->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM));
	}


	/**
	 * Moves cursor position without fetching row.
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
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 * @throws Dibi\Exception
	 */
	public function getResultColumns(): array
	{
		$count = $this->resultSet->columnCount();
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = @$this->resultSet->getColumnMeta($i); // intentionally @
			if ($row === false) {
				throw new Dibi\NotSupportedException('Driver does not support meta data.');
			}

			$row += [
				'table' => null,
				'native_type' => 'VAR_STRING',
			];

			$columns[] = [
				'name' => $row['name'],
				'table' => $row['table'],
				'nativetype' => $row['native_type'],
				'type' => $row['native_type'] === 'TIME' && $this->driverName === 'mysql' ? Dibi\Type::TIME_INTERVAL : null,
				'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
				'vendor' => $row,
			];
		}

		return $columns;
	}


	/**
	 * Returns the result set resource.
	 */
	public function getResultResource(): ?\PDOStatement
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
