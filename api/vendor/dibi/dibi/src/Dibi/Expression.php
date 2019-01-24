<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * SQL expression.
 */
class Expression
{
	use Strict;

	/** @var array */
	private $values;


	public function __construct()
	{
		$this->values = func_get_args();
	}


	public function getValues()
	{
		return $this->values;
	}
}
