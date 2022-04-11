<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Query result.
 *
 * @property-read int $rowCount
 */
class Result implements IDataSource
{
	use Strict;

	/** @var ResultDriver|null */
	private $driver;

	/** @var array  Translate table */
	private $types = [];

	/** @var Reflection\Result|null */
	private $meta;

	/** @var bool  Already fetched? Used for allowance for first seek(0) */
	private $fetched = false;

	/** @var string|null  returned object class */
	private $rowClass = Row::class;

	/** @var callable|null  returned object factory */
	private $rowFactory;

	/** @var array  format */
	private $formats = [];


	public function __construct(ResultDriver $driver, bool $normalize = true)
	{
		$this->driver = $driver;
		if ($normalize) {
			$this->detectTypes();
		}
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	final public function free(): void
	{
		if ($this->driver !== null) {
			$this->driver->free();
			$this->driver = $this->meta = null;
		}
	}


	/**
	 * Safe access to property $driver.
	 * @throws \RuntimeException
	 */
	final public function getResultDriver(): ResultDriver
	{
		if ($this->driver === null) {
			throw new \RuntimeException('Result-set was released from memory.');
		}

		return $this->driver;
	}


	/********************* rows ****************d*g**/


	/**
	 * Moves cursor position without fetching row.
	 * @throws Exception
	 */
	final public function seek(int $row): bool
	{
		return ($row !== 0 || $this->fetched)
			? $this->getResultDriver()->seek($row)
			: true;
	}


	/**
	 * Required by the Countable interface.
	 */
	final public function count(): int
	{
		return $this->getResultDriver()->getRowCount();
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	final public function getRowCount(): int
	{
		return $this->getResultDriver()->getRowCount();
	}


	/**
	 * Required by the IteratorAggregate interface.
	 */
	final public function getIterator(): ResultIterator
	{
		return new ResultIterator($this);
	}


	/**
	 * Returns the number of columns in a result set.
	 */
	final public function getColumnCount(): int
	{
		return count($this->types);
	}


	/********************* fetching rows ****************d*g**/


	/**
	 * Set fetched object class. This class should extend the Row class.
	 */
	public function setRowClass(?string $class): self
	{
		$this->rowClass = $class;
		return $this;
	}


	/**
	 * Returns fetched object class name.
	 */
	public function getRowClass(): ?string
	{
		return $this->rowClass;
	}


	/**
	 * Set a factory to create fetched object instances. These should extend the Row class.
	 */
	public function setRowFactory(callable $callback): self
	{
		$this->rowFactory = $callback;
		return $this;
	}


	/**
	 * Fetches the row at current position, process optional type conversion.
	 * and moves the internal cursor to the next position
	 * @return Row|array|null
	 */
	final public function fetch()
	{
		$row = $this->getResultDriver()->fetch(true);
		if ($row === null) {
			return null;
		}

		$this->fetched = true;
		$this->normalize($row);
		if ($this->rowFactory) {
			return ($this->rowFactory)($row);
		} elseif ($this->rowClass) {
			return new $this->rowClass($row);
		}

		return $row;
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed value on success, null if no next record
	 */
	final public function fetchSingle()
	{
		$row = $this->getResultDriver()->fetch(true);
		if ($row === null) {
			return null;
		}

		$this->fetched = true;
		$this->normalize($row);
		return reset($row);
	}


	/**
	 * Fetches all records from table.
	 * @return Row[]|array[]
	 */
	final public function fetchAll(?int $offset = null, ?int $limit = null): array
	{
		$limit = $limit ?? -1;
		$this->seek($offset ?: 0);
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
	 * @throws \InvalidArgumentException
	 */
	final public function fetchAssoc(string $assoc): array
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
		if (!$assoc) {
			throw new \InvalidArgumentException("Invalid descriptor '$assoc'.");
		}

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
					$x = &$x[(string) $row->$as];
				}
			}

			if ($x === null) { // build leaf
				$x = $row;
			}
		} while ($row = $this->fetch());

		unset($x);
		/** @var mixed[] $data */
		return $data;
	}


	/** @deprecated */
	private function oldFetchAssoc(string $assoc)
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
					$x = &$x[(string) $row->$as];
				}
			}

			if ($x === null) { // build leaf
				$x = $leaf === '='
					? $row->toArray()
					: $row;
			}
		} while ($row = $this->fetch());

		unset($x);
		return $data;
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @throws \InvalidArgumentException
	 */
	final public function fetchPairs(?string $key = null, ?string $value = null): array
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
	 */
	private function detectTypes(): void
	{
		$cache = Helpers::getTypeCache();
		try {
			foreach ($this->getResultDriver()->getResultColumns() as $col) {
				$this->types[$col['name']] = $col['type'] ?? $cache->{$col['nativetype']};
			}
		} catch (NotSupportedException $e) {
		}
	}


	/**
	 * Converts values to specified type and format.
	 */
	private function normalize(array &$row): void
	{
		foreach ($this->types as $key => $type) {
			if (!isset($row[$key])) { // null
				continue;
			}

			$value = $row[$key];
			$format = $this->formats[$type] ?? null;

			if ($type === null || $format === 'native') {
				$row[$key] = $value;

			} elseif ($type === Type::TEXT) {
				$row[$key] = (string) $value;

			} elseif ($type === Type::INTEGER) {
				$row[$key] = is_float($tmp = $value * 1)
					? (is_string($value) ? $value : (int) $value)
					: $tmp;

			} elseif ($type === Type::FLOAT) {
				$value = ltrim((string) $value, '0');
				$p = strpos($value, '.');
				$e = strpos($value, 'e');
				if ($p !== false && $e === false) {
					$value = rtrim(rtrim($value, '0'), '.');
				} elseif ($p !== false && $e !== false) {
					$value = rtrim($value, '.');
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
				if ($value && substr((string) $value, 0, 7) !== '0000-00') { // '', null, false, '0000-00-00', ...
					$value = new DateTime($value);
					$row[$key] = $format ? $value->format($format) : $value;
				} else {
					$row[$key] = null;
				}
			} elseif ($type === Type::TIME_INTERVAL) {
				preg_match('#^(-?)(\d+)\D(\d+)\D(\d+)\z#', $value, $m);
				$value = new \DateInterval("PT$m[2]H$m[3]M$m[4]S");
				$value->invert = (int) (bool) $m[1];
				$row[$key] = $format ? $value->format($format) : $value;

			} elseif ($type === Type::BINARY) {
				$row[$key] = is_string($value)
					? $this->getResultDriver()->unescapeBinary($value)
					: $value;

			} elseif ($type === Type::JSON) {
				if ($format === 'string') { // back compatibility with 'native'
					$row[$key] = $value;
				} else {
					$row[$key] = json_decode($value, $format === 'array');
				}
			} else {
				throw new \RuntimeException('Unexpected type ' . $type);
			}
		}
	}


	/**
	 * Define column type.
	 * @param  string|null  $type  use constant Type::*
	 */
	final public function setType(string $column, ?string $type): self
	{
		$this->types[$column] = $type;
		return $this;
	}


	/**
	 * Returns column type.
	 */
	final public function getType(string $column): ?string
	{
		return $this->types[$column] ?? null;
	}


	/**
	 * Returns columns type.
	 */
	final public function getTypes(): array
	{
		return $this->types;
	}


	/**
	 * Sets type format.
	 */
	final public function setFormat(string $type, ?string $format): self
	{
		$this->formats[$type] = $format;
		return $this;
	}


	/**
	 * Sets type formats.
	 */
	final public function setFormats(array $formats): self
	{
		$this->formats = $formats;
		return $this;
	}


	/**
	 * Returns data format.
	 */
	final public function getFormat(string $type): ?string
	{
		return $this->formats[$type] ?? null;
	}


	/********************* meta info ****************d*g**/


	/**
	 * Returns a meta information about the current result set.
	 */
	public function getInfo(): Reflection\Result
	{
		if ($this->meta === null) {
			$this->meta = new Reflection\Result($this->getResultDriver());
		}

		return $this->meta;
	}


	/** @return Reflection\Column[] */
	final public function getColumns(): array
	{
		return $this->getInfo()->getColumns();
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Displays complete result set as HTML or text table for debug purposes.
	 */
	final public function dump(): void
	{
		echo Helpers::dump($this);
	}
}
