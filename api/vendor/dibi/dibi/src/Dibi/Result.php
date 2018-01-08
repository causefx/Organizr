<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * dibi result set.
 *
 * <code>
 * $result = dibi::query('SELECT * FROM [table]');
 *
 * $row   = $result->fetch();
 * $value = $result->fetchSingle();
 * $table = $result->fetchAll();
 * $pairs = $result->fetchPairs();
 * $assoc = $result->fetchAssoc('col1');
 * $assoc = $result->fetchAssoc('col1[]col2->col3');
 *
 * unset($result);
 * </code>
 *
 * @property-read int $rowCount
 */
class Result implements IDataSource
{
	use Strict;

	/** @var array  ResultDriver */
	private $driver;

	/** @var array  Translate table */
	private $types = [];

	/** @var Reflection\Result */
	private $meta;

	/** @var bool  Already fetched? Used for allowance for first seek(0) */
	private $fetched = false;

	/** @var string  returned object class */
	private $rowClass = 'Dibi\Row';

	/** @var callable  returned object factory*/
	private $rowFactory;

	/** @var array  format */
	private $formats = [];


	/**
	 * @param  ResultDriver
	 */
	public function __construct($driver)
	{
		$this->driver = $driver;
		$this->detectTypes();
	}


	/**
	 * @deprecated
	 */
	final public function getResource()
	{
		trigger_error(__METHOD__ . '() is deprecated, use getResultDriver()->getResultResource().', E_USER_DEPRECATED);
		return $this->getResultDriver()->getResultResource();
	}


	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	final public function free()
	{
		if ($this->driver !== null) {
			$this->driver->free();
			$this->driver = $this->meta = null;
		}
	}


	/**
	 * Safe access to property $driver.
	 * @return ResultDriver
	 * @throws \RuntimeException
	 */
	final public function getResultDriver()
	{
		if ($this->driver === null) {
			throw new \RuntimeException('Result-set was released from memory.');
		}

		return $this->driver;
	}


	/********************* rows ****************d*g**/


	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return bool     true on success, false if unable to seek to specified record
	 * @throws Exception
	 */
	final public function seek($row)
	{
		return ($row != 0 || $this->fetched) // intentionally ==
			? (bool) $this->getResultDriver()->seek($row)
			: true;
	}


	/**
	 * Required by the Countable interface.
	 * @return int
	 */
	final public function count()
	{
		return $this->getResultDriver()->getRowCount();
	}


	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	final public function getRowCount()
	{
		return $this->getResultDriver()->getRowCount();
	}


	/**
	 * Required by the IteratorAggregate interface.
	 * @return ResultIterator
	 */
	final public function getIterator()
	{
		return new ResultIterator($this);
	}


	/********************* fetching rows ****************d*g**/


	/**
	 * Set fetched object class. This class should extend the Row class.
	 * @param  string
	 * @return self
	 */
	public function setRowClass($class)
	{
		$this->rowClass = $class;
		return $this;
	}


	/**
	 * Returns fetched object class name.
	 * @return string
	 */
	public function getRowClass()
	{
		return $this->rowClass;
	}


	/**
	 * Set a factory to create fetched object instances. These should extend the Row class.
	 * @return self
	 */
	public function setRowFactory(callable $callback)
	{
		$this->rowFactory = $callback;
		return $this;
	}


	/**
	 * Fetches the row at current position, process optional type conversion.
	 * and moves the internal cursor to the next position
	 * @return Row|false
	 */
	final public function fetch()
	{
		$row = $this->getResultDriver()->fetch(true);
		if (!is_array($row)) {
			return false;
		}
		$this->fetched = true;
		$this->normalize($row);
		if ($this->rowFactory) {
			return call_user_func($this->rowFactory, $row);
		} elseif ($this->rowClass) {
			$row = new $this->rowClass($row);
		}
		return $row;
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed value on success, false if no next record
	 */
	final public function fetchSingle()
	{
		$row = $this->getResultDriver()->fetch(true);
		if (!is_array($row)) {
			return false;
		}
		$this->fetched = true;
		$this->normalize($row);
		return reset($row);
	}


	/**
	 * Fetches all records from table.
	 * @param  int  offset
	 * @param  int  limit
	 * @return Row[]
	 */
	final public function fetchAll($offset = null, $limit = null)
	{
		$limit = $limit === null ? -1 : Helpers::intVal($limit);
		$this->seek($offset);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = [];
		do {
			if ($limit === 0) {
				break;
			}
			$limit--;
			$data[] = $row;
		} while ($row = $this->fetch());

		return $data;
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * Examples:
	 * - associative descriptor: col1[]col2->col3
	 *   builds a tree:          $tree[$val1][$index][$val2]->col3[$val3] = {record}
	 * - associative descriptor: col1|col2->col3=col4
	 *   builds a tree:          $tree[$val1][$val2]->col3[$val3] = val4
	 * @param  string  associative descriptor
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	final public function fetchAssoc($assoc)
	{
		if (strpos($assoc, ',') !== false) {
			return $this->oldFetchAssoc($assoc);
		}

		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = null;
		$assoc = preg_split('#(\[\]|->|=|\|)#', $assoc, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores null in PHP 5.2.1, isset() surprisingly null accepts
			if ($as !== '[]' && $as !== '=' && $as !== '->' && $as !== '|' && !property_exists($row, $as)) {
				throw new \InvalidArgumentException("Unknown column '$as' in associative descriptor.");
			}
		}

		if ($as === '->') { // must not be last
			array_pop($assoc);
		}

		if (empty($assoc)) {
			$assoc[] = '[]';
		}

		// make associative tree
		do {
			$x = &$data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '[]') { // indexed-array node
					$x = &$x[];

				} elseif ($as === '=') { // "value" node
					$x = $row->{$assoc[$i + 1]};
					continue 2;

				} elseif ($as === '->') { // "object" node
					if ($x === null) {
						$x = clone $row;
						$x = &$x->{$assoc[$i + 1]};
						$x = null; // prepare child node
					} else {
						$x = &$x->{$assoc[$i + 1]};
					}

				} elseif ($as !== '|') { // associative-array node
					$x = &$x[$row->$as];
				}
			}

			if ($x === null) { // build leaf
				$x = $row;
			}
		} while ($row = $this->fetch());

		unset($x);
		return $data;
	}


	/**
	 * @deprecated
	 */
	private function oldFetchAssoc($assoc)
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = null;
		$assoc = explode(',', $assoc);

		// strip leading = and @
		$leaf = '@';  // gap
		$last = count($assoc) - 1;
		while ($assoc[$last] === '=' || $assoc[$last] === '@') {
			$leaf = $assoc[$last];
			unset($assoc[$last]);
			$last--;

			if ($last < 0) {
				$assoc[] = '#';
				break;
			}
		}

		do {
			$x = &$data;

			foreach ($assoc as $i => $as) {
				if ($as === '#') { // indexed-array node
					$x = &$x[];

				} elseif ($as === '=') { // "record" node
					if ($x === null) {
						$x = $row->toArray();
						$x = &$x[$assoc[$i + 1]];
						$x = null; // prepare child node
					} else {
						$x = &$x[$assoc[$i + 1]];
					}

				} elseif ($as === '@') { // "object" node
					if ($x === null) {
						$x = clone $row;
						$x = &$x->{$assoc[$i + 1]};
						$x = null; // prepare child node
					} else {
						$x = &$x->{$assoc[$i + 1]};
					}

				} else { // associative-array node
					$x = &$x[$row->$as];
				}
			}

			if ($x === null) { // build leaf
				if ($leaf === '=') {
					$x = $row->toArray();
				} else {
					$x = $row;
				}
			}
		} while ($row = $this->fetch());

		unset($x);
		return $data;
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	final public function fetchPairs($key = null, $value = null)
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = [];

		if ($value === null) {
			if ($key !== null) {
				throw new \InvalidArgumentException('Either none or both columns must be specified.');
			}

			// autodetect
			$tmp = array_keys($row->toArray());
			$key = $tmp[0];
			if (count($row) < 2) { // indexed-array
				do {
					$data[] = $row[$key];
				} while ($row = $this->fetch());
				return $data;
			}

			$value = $tmp[1];

		} else {
			if (!property_exists($row, $value)) {
				throw new \InvalidArgumentException("Unknown value column '$value'.");
			}

			if ($key === null) { // indexed-array
				do {
					$data[] = $row[$value];
				} while ($row = $this->fetch());
				return $data;
			}

			if (!property_exists($row, $key)) {
				throw new \InvalidArgumentException("Unknown key column '$key'.");
			}
		}

		do {
			$data[(string) $row[$key]] = $row[$value];
		} while ($row = $this->fetch());

		return $data;
	}


	/********************* column types ****************d*g**/


	/**
	 * Autodetect column types.
	 * @return void
	 */
	private function detectTypes()
	{
		$cache = Helpers::getTypeCache();
		try {
			foreach ($this->getResultDriver()->getResultColumns() as $col) {
				$this->types[$col['name']] = isset($col['type']) ? $col['type'] : $cache->{$col['nativetype']};
			}
		} catch (NotSupportedException $e) {
		}
	}


	/**
	 * Converts values to specified type and format.
	 * @param  array
	 * @return void
	 */
	private function normalize(array &$row)
	{
		foreach ($this->types as $key => $type) {
			if (!isset($row[$key])) { // null
				continue;
			}
			$value = $row[$key];
			if ($type === Type::TEXT) {
				$row[$key] = (string) $value;

			} elseif ($type === Type::INTEGER) {
				$row[$key] = is_float($tmp = $value * 1)
					? (is_string($value) ? $value : (int) $value)
					: $tmp;

			} elseif ($type === Type::FLOAT) {
				$value = ltrim((string) $value, '0');
				$p = strpos($value, '.');
				if ($p !== false) {
					$value = rtrim(rtrim($value, '0'), '.');
				}
				if ($value === '' || $value[0] === '.') {
					$value = '0' . $value;
				}
				$row[$key] = $value === str_replace(',', '.', (string) ($float = (float) $value))
					? $float
					: $value;

			} elseif ($type === Type::BOOL) {
				$row[$key] = ((bool) $value) && $value !== 'f' && $value !== 'F';

			} elseif ($type === Type::DATETIME || $type === Type::DATE || $type === Type::TIME) {
				if ($value && substr((string) $value, 0, 3) !== '000') { // '', null, false, '0000-00-00', ...
					$value = new DateTime($value);
					$row[$key] = empty($this->formats[$type]) ? $value : $value->format($this->formats[$type]);
				} else {
					$row[$key] = null;
				}

			} elseif ($type === Type::TIME_INTERVAL) {
				preg_match('#^(-?)(\d+)\D(\d+)\D(\d+)\z#', $value, $m);
				$row[$key] = new \DateInterval("PT$m[2]H$m[3]M$m[4]S");
				$row[$key]->invert = (int) (bool) $m[1];

			} elseif ($type === Type::BINARY) {
				$row[$key] = $this->getResultDriver()->unescapeBinary($value);
			}
		}
	}


	/**
	 * Define column type.
	 * @param  string  column
	 * @param  string  type (use constant Type::*)
	 * @return self
	 */
	final public function setType($col, $type)
	{
		$this->types[$col] = $type;
		return $this;
	}


	/**
	 * Returns column type.
	 * @return string
	 */
	final public function getType($col)
	{
		return isset($this->types[$col]) ? $this->types[$col] : null;
	}


	/**
	 * Sets date format.
	 * @param  string
	 * @param  string|null  format
	 * @return self
	 */
	final public function setFormat($type, $format)
	{
		$this->formats[$type] = $format;
		return $this;
	}


	/**
	 * Returns data format.
	 * @return string|null
	 */
	final public function getFormat($type)
	{
		return isset($this->formats[$type]) ? $this->formats[$type] : null;
	}


	/********************* meta info ****************d*g**/


	/**
	 * Returns a meta information about the current result set.
	 * @return Reflection\Result
	 */
	public function getInfo()
	{
		if ($this->meta === null) {
			$this->meta = new Reflection\Result($this->getResultDriver());
		}
		return $this->meta;
	}


	/**
	 * @return Reflection\Column[]
	 */
	final public function getColumns()
	{
		return $this->getInfo()->getColumns();
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Displays complete result set as HTML or text table for debug purposes.
	 * @return void
	 */
	final public function dump()
	{
		echo Helpers::dump($this);
	}
}
