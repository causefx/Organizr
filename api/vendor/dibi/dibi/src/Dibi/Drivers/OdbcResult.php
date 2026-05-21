<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The driver interacting with result set via ODBC connections.
 */
class OdbcResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var resource */
	private $resultSet;

	/** @var int  Cursor */
	private $row = 0;


	/**
	 * @param  resource  $resultSet
	 */
	public function __construct($resultSet)
	{
		$this->resultSet = $resultSet;
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		// will return -1 with many drivers :-(
		return odbc_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc  true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		if ($assoc) {
			return Dibi\Helpers::false2Null(odbc_fetch_array($this->resultSet, ++$this->row));
		} else {
			$set = $this->resultSet;
			if (!odbc_fetch_row($set, ++$this->row)) {
				return null;
			}

			$count = odbc_num_fields($set);
			$cols = [];
			for ($i = 1; $i <= $count; $i++) {
				$cols[] = odbc_result($set, $i);
			}

			return $cols;
		}
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		$this->row = $row;
		return true;
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		odbc_free_result($this->resultSet);
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = odbc_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$columns[] = [
				'name' => odbc_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => odbc_field_name($this->resultSet, $i),
				'nativetype' => odbc_field_type($this->resultSet, $i),
			];
		}

		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return resource|null
	 */
	public function getResultResource()
	{
		return is_resource($this->resultSet) ? $this->resultSet : null;
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}
}
