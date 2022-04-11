<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;


/**
 * Better OOP experience.
 */
trait Strict
{
	/** @var array [method => [type => callback]] */
	private static $extMethods;


	/**
	 * Call to undefined method.
	 * @throws \LogicException
	 */
	public function __call(string $name, array $args)
	{
		$class = method_exists($this, $name) ? 'parent' : static::class;
		$items = (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC);
		$items = array_map(function ($item) { return $item->getName(); }, $items);
		$hint = ($t = Helpers::getSuggestion($items, $name))
			? ", did you mean $t()?"
			: '.';
		throw new \LogicException("Call to undefined method $class::$name()$hint");
	}


	/**
	 * Call to undefined static method.
	 * @throws \LogicException
	 */
	public static function __callStatic(string $name, array $args)
	{
		$rc = new ReflectionClass(static::class);
		$items = array_filter($rc->getMethods(\ReflectionMethod::IS_STATIC), function ($m) { return $m->isPublic(); });
		$items = array_map(function ($item) { return $item->getName(); }, $items);
		$hint = ($t = Helpers::getSuggestion($items, $name))
			? ", did you mean $t()?"
			: '.';
		throw new \LogicException("Call to undefined static method {$rc->getName()}::$name()$hint");
	}


	/**
	 * Access to undeclared property.
	 * @throws \LogicException
	 */
	public function &__get(string $name)
	{
		if ((method_exists($this, $m = 'get' . $name) || method_exists($this, $m = 'is' . $name))
			&& (new ReflectionMethod($this, $m))->isPublic()
		) { // back compatiblity
			$ret = $this->$m();
			return $ret;
		}

		$rc = new ReflectionClass($this);
		$items = array_filter($rc->getProperties(ReflectionProperty::IS_PUBLIC), function ($p) { return !$p->isStatic(); });
		$items = array_map(function ($item) { return $item->getName(); }, $items);
		$hint = ($t = Helpers::getSuggestion($items, $name))
			? ", did you mean $$t?"
			: '.';
		throw new \LogicException("Attempt to read undeclared property {$rc->getName()}::$$name$hint");
	}


	/**
	 * Access to undeclared property.
	 * @throws \LogicException
	 */
	public function __set(string $name, $value)
	{
		$rc = new ReflectionClass($this);
		$items = array_filter($rc->getProperties(ReflectionProperty::IS_PUBLIC), function ($p) { return !$p->isStatic(); });
		$items = array_map(function ($item) { return $item->getName(); }, $items);
		$hint = ($t = Helpers::getSuggestion($items, $name))
			? ", did you mean $$t?"
			: '.';
		throw new \LogicException("Attempt to write to undeclared property {$rc->getName()}::$$name$hint");
	}


	public function __isset(string $name): bool
	{
		return false;
	}


	/**
	 * Access to undeclared property.
	 * @throws \LogicException
	 */
	public function __unset(string $name)
	{
		$class = static::class;
		throw new \LogicException("Attempt to unset undeclared property $class::$$name.");
	}
}
