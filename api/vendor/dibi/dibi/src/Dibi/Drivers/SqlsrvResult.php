<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The driver for Microsoft SQL Server and SQL Azure result set.
 */
class SqlsrvResult implements Dibi\ResultDriver
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
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc  true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Dibi\Helpers::false2Null(sqlsrv_fetch_array($this->resultSet, $assoc ? SQLSRV_FETCH_ASSOC : SQLSRV_FETCH_NUMERIC));
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
		sqlsrv_free_stmt($this->resultSet);
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$columns = [];
		foreach ((array) sqlsrv_field_metadata($this->resultSet) as $fieldMetadata) {
			$columns[] = [
				'name' => $fieldMetadata['Name'],
				'fullname' => $fieldMetadata['Name'],
				'nativetype' => $fieldMetadata['Type'],
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
