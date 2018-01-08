<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;

use Dibi;


/**
 * Reflection metadata class for a foreign key.
 *
 * @property-read string $name
 * @property-read array $references
 */
class ForeignKey
{
	use Dibi\Strict;

	/** @var string */
	private $name;

	/** @var array of [local, foreign, onDelete, onUpdate] */
	private $references;


	public function __construct($name, array $references)
	{
		$this->name = $name;
		$this->references = $references;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @return array
	 */
	public function getReferences()
	{
		return $this->references;
	}
}
