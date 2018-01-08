<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

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
	public function __call($name, $args)
	{
		if ($cb = self::extensionMethod(get_class($this) . '::' . $name)) { // back compatiblity
			array_unshift($args, $this);
			return call_user_func_array($cb, $args);
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
	public static function __callStatic($name, $args)
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
	public function &__get($name)
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
	public function __set($name, $value)
	{
		$rc = new ReflectionClass($this);
		$items = array_diff($rc->getProperties(ReflectionProperty::IS_PUBLIC), $rc->getProperties(ReflectionProperty::IS_STATIC));
		$hint = ($t = Helpers::getSuggestion($items, $name)) ? ", did you mean $$t?" : '.';
		throw new \LogicException("Attempt to write to undeclared property {$rc->getName()}::$$name$hint");
	}


	/**
	 * @return bool
	 */
	public function __isset($name)
	{
		return false;
	}


	/**
	 * Access to undeclared property.
	 * @throws \LogicException
	 */
	public function __unset($name)
	{
		$class = get_class($this);
		throw new \LogicException("Attempt to unset undeclared property $class::$$name.");
	}


	/**
	 * @param  string  method name
	 * @param  callable
	 * @return mixed
	 */
	public static function extensionMethod($name, $callback = null)
	{
		if (strpos($name, '::') === false) {
			$class = get_called_class();
		} else {
			list($class, $name) = explode('::', $name);
			$class = (new ReflectionClass($class))->getName();
		}

		if (self::$extMethods === null) { // for backwards compatibility
			$list = get_defined_functions();
			foreach ($list['user'] as $fce) {
				$pair = explode('_prototype_', $fce);
				if (count($pair) === 2) {
					trigger_error("Extension method defined as $fce() is deprecated, use $class::extensionMethod('$name', ...).", E_USER_DEPRECATED);
					self::$extMethods[$pair[1]][(new ReflectionClass($pair[0]))->getName()] = $fce;
					self::$extMethods[$pair[1]][''] = null;
				}
			}
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
			$list[$class] = $callback;
			$list[''] = null;
		}
	}
}
