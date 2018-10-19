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
		$class = get_class($this);
		if ($cb = self::extensionMethod($class . '::' . $name)) { // back compatiblity
			trigger_error("Extension methods such as $class::$name() are deprecated", E_USER_DEPRECATED);
			array_unshift($args, $this);
			return $cb(...$args);
		}
		$class = method_exists($this, $name) ? 'parent' : get_class($this);
		$items = (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC);
		$hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean $t()?" : '.';
		throw new \LogicException("Call to undefined method $class::$name()$hint");
	}


	/**
	 * Call to undefined static method.
	 * @throws \LogicException
	 */
	public static function __callStatic(string $name, array $args)
	{
		$rc = new ReflectionClass(get_called_class());
		$items = array_intersect($rc->getMethods(ReflectionMethod::IS_PUBLIC), $rc->getMethods(ReflectionMethod::IS_STATIC));
		$hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean $t()?" : '.';
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
		$items = array_diff($rc->getProperties(ReflectionProperty::IS_PUBLIC), $rc->getProperties(ReflectionProperty::IS_STATIC));
		$hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean $$t?" : '.';
		throw new \LogicException("Attempt to read undeclared property {$rc->getName()}::$$name$hint");
	}


	/**
	 * Access to undeclared property.
	 * @throws \LogicException
	 */
	public function __set(string $name, $value)
	{
		$rc = new ReflectionClass($this);
		$items = array_diff($rc->getProperties(ReflectionProperty::IS_PUBLIC), $rc->getProperties(ReflectionProperty::IS_STATIC));
		$hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean $$t?" : '.';
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
		$class = get_class($this);
		throw new \LogicException("Attempt to unset undeclared property $class::$$name.");
	}


	/**
	 * @return mixed
	 * @deprecated
	 */
	public static function extensionMethod(string $name, callable $callback = null)
	{
		if (strpos($name, '::') === false) {
			$class = get_called_class();
		} else {
			[$class, $name] = explode('::', $name);
			$class = (new ReflectionClass($class))->getName();
		}

		$list = &self::$extMethods[strtolower($name)];
		if ($callback === null) { // getter
			$cache = &$list[''][$class];
			if (isset($cache)) {
				return $cache;
			}

			foreach ([$class] + class_parents($class) + class_implements($class) as $cl) {
				if (isset($list[$cl])) {
					return $cache = $list[$cl];
				}
			}
			return $cache = false;

		} else { // setter
			trigger_error("Extension methods such as $class::$name() are deprecated", E_USER_DEPRECATED);
			$list[$class] = $callback;
			$list[''] = null;
		}
	}
}
