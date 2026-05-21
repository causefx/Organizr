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
 * The driver for Firebird/InterBase result set.
 */
class FirebirdResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var resource */
	private $resultSet;


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
		throw new Dibi\NotSupportedException('Firebird/Interbase do not support returning number of rows in result set.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc   true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		$result = $assoc
			? @ibase_fetch_assoc($this->resultSet, IBASE_TEXT)
			: @ibase_fetch_row($this->resultSet, IBASE_TEXT); // intentionally @

		if (ibase_errcode()) {
			if (ibase_errcode() === FirebirdDriver::ERROR_EXCEPTION_THROWN) {
				preg_match('/exception (\d+) (\w+) (.*)/is', ibase_errmsg(), $match);
				throw new Dibi\ProcedureException($match[3], $match[1], $match[2]);

			} else {
				throw new Dibi\DriverException(ibase_errmsg(), ibase_errcode());
			}
		}

		return Helpers::false2Null($result);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @throws Dibi\Exception
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotSupportedException('Firebird/Interbase do not support seek in result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		ibase_free_result($this->resultSet);
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
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = ibase_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = (array) ibase_field_info($this->resultSet, $i);
			$columns[] = [
				'name' => $row['name'],
				'fullname' => $row['name'],
				'table' => $row['relation'],
				'nativetype' => $row['type'],
			];
		}

		return $columns;
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}
}
