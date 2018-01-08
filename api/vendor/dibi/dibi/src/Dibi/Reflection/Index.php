<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;

use Dibi;


/**
 * Reflection metadata class for a index or primary key.
 *
 * @property-read string $name
 * @property-read array $columns
 * @property-read bool $unique
 * @property-read bool $primary
 */
class Index
{
	use Dibi\Strict;

	/** @var array (name, columns, [unique], [primary]) */
	private $info;


	public function __construct(array $info)
	{
		$this->info = $info;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->info['name'];
	}


	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->info['columns'];
	}


	/**
	 * @return bool
	 */
	public function isUnique()
	{
		return !empty($this->info['unique']);
	}


	/**
	 * @return bool
	 */
	public function isPrimary()
	{
		return !empty($this->info['primary']);
	}
}
