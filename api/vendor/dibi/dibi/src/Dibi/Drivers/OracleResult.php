<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The driver for Oracle result set.
 */
class OracleResult implements Dibi\ResultDriver
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
		return Dibi\Helpers::false2Null(oci_fetch_array($this->resultSet, ($assoc ? OCI_ASSOC : OCI_NUM) | OCI_RETURN_NULLS));
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		oci_free_statement($this->resultSet);
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = oci_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$type = oci_field_type($this->resultSet, $i);
			$columns[] = [
				'name' => oci_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => oci_field_name($this->resultSet, $i),
				'type' => $type === 'LONG' ? Dibi\Type::TEXT : null,
				'nativetype' => $type === 'NUMBER' && oci_field_scale($this->resultSet, $i) === 0 ? 'INTEGER' : $type,
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
