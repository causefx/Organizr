<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * SQL translator.
 */
final class Translator
{
	use Strict;

	/** @var Connection */
	private $connection;

	/** @var Driver */
	private $driver;

	/** @var int */
	private $cursor = 0;

	/** @var array */
	private $args;

	/** @var string[] */
	private $errors;

	/** @var bool */
	private $comment = false;

	/** @var int */
	private $ifLevel = 0;

	/** @var int */
	private $ifLevelStart = 0;

	/** @var int|null */
	private $limit;

	/** @var int|null */
	private $offset;

	/** @var HashMap */
	private $identifiers;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->driver = $connection->getDriver();
		$this->identifiers = new HashMap([$this, 'delimite']);
	}


	/**
	 * Generates SQL. Can be called only once.
	 * @throws Exception
	 */
	public function translate(array $args): string
	{
		$args = array_values($args);
		while (count($args) === 1 && is_array($args[0])) { // implicit array expansion
			$args = array_values($args[0]);
		}

		$this->args = $args;
		$this->errors = [];

		$commandIns = null;
		$lastArr = null;
		$cursor = &$this->cursor;
		$comment = &$this->comment;

		// iterate
		$sql = [];
		while ($cursor < count($this->args)) {
			$arg = $this->args[$cursor];
			$cursor++;

			// simple string means SQL
			if (is_string($arg)) {
				// speed-up - is regexp required?
				$toSkip = strcspn($arg, '`[\'":%?');

				if (strlen($arg) === $toSkip) { // needn't be translated
					$sql[] = $arg;
				} else {
					$sql[] = substr($arg, 0, $toSkip)
						// note: this can change $this->args & $this->cursor & ...
						. preg_replace_callback(
							<<<'XX'
							/
							(?=[`['":%?])                       ## speed-up
							(?:
								`(.+?)`|                        ## 1) `identifier`
								\[(.+?)\]|                      ## 2) [identifier]
								(')((?:''|[^'])*)'|             ## 3,4) string
								(")((?:""|[^"])*)"|             ## 5,6) "string"
								('|")|                          ## 7) lone quote
								:(\S*?:)([a-zA-Z0-9._]?)|       ## 8,9) :substitution:
								%([a-zA-Z~][a-zA-Z0-9~]{0,5})|  ## 10) modifier
								(\?)                            ## 11) placeholder
							)/xs
XX
							,
							[$this, 'cb'],
							substr($arg, $toSkip)
						);
					if (preg_last_error()) {
						throw new PcreException;
					}
				}

				continue;
			}

			if ($comment) {
				$sql[] = '...';
				continue;
			}

			if ($arg instanceof \Traversable) {
				$arg = iterator_to_array($arg);
			}

			if (is_array($arg) && is_string(key($arg))) {
				// associative array -> autoselect between SET or VALUES & LIST
				if ($commandIns === null) {
					$commandIns = strtoupper(substr(ltrim($this->args[0]), 0, 6));
					$commandIns = $commandIns === 'INSERT' || $commandIns === 'REPLAC';
					$sql[] = $this->formatValue($arg, $commandIns ? 'v' : 'a');
				} else {
					if ($lastArr === $cursor - 1) {
						$sql[] = ',';
					}

					$sql[] = $this->formatValue($arg, $commandIns ? 'l' : 'a');
				}

				$lastArr = $cursor;
				continue;
			}

			// default processing
			$sql[] = $this->formatValue($arg, null);
		} // while

		if ($comment) {
			$sql[] = '*/';
		}

		$sql = trim(implode(' ', $sql), ' ');

		if ($this->errors) {
			throw new Exception('SQL translate error: ' . trim(reset($this->errors), '*'), 0, $sql);
		}

		// apply limit
		if ($this->limit !== null || $this->offset !== null) {
			$this->driver->applyLimit($sql, $this->limit, $this->offset);
		}

		return $sql;
	}


	/**
	 * Apply modifier to single value.
	 * @param  mixed  $value
	 */
	public function formatValue($value, ?string $modifier): string
	{
		if ($this->comment) {
			return '...';
		}

		// array processing (with or without modifier)
		if ($value instanceof \Traversable) {
			$value = iterator_to_array($value);
		}

		if (is_array($value)) {
			$vx = $kx = [];
			switch ($modifier) {
				case 'and':
				case 'or':  // key=val AND key IS NULL AND ...
					if (empty($value)) {
						return '1=1';
					}

					foreach ($value as $k => $v) {
						if (is_string($k)) {
							$pair = explode('%', $k, 2); // split into identifier & modifier
							$k = $this->identifiers->{$pair[0]} . ' ';
							if (!isset($pair[1])) {
								$v = $this->formatValue($v, null);
								$vx[] = $k . ($v === 'NULL' ? 'IS ' : '= ') . $v;

							} elseif ($pair[1] === 'ex') {
								$vx[] = $k . $this->formatValue($v, 'ex');

							} else {
								$v = $this->formatValue($v, $pair[1]);
								if ($pair[1] === 'l' || $pair[1] === 'in') {
									$op = 'IN ';
								} elseif (strpos($pair[1], 'like') !== false) {
									$op = 'LIKE ';
								} elseif ($v === 'NULL') {
									$op = 'IS ';
								} else {
									$op = '= ';
								}

								$vx[] = $k . $op . $v;
							}
						} else {
							$vx[] = $this->formatValue($v, 'ex');
						}
					}

					return '(' . implode(') ' . strtoupper($modifier) . ' (', $vx) . ')';

				case 'n':  // key, key, ... identifier names
					foreach ($value as $k => $v) {
						if (is_string($k)) {
							$vx[] = $this->identifiers->$k . (empty($v) ? '' : ' AS ' . $this->driver->escapeIdentifier($v));
						} else {
							$pair = explode('%', $v, 2); // split into identifier & modifier
							$vx[] = $this->identifiers->{$pair[0]};
						}
					}

					return implode(', ', $vx);


				case 'a': // key=val, key=val, ...
					foreach ($value as $k => $v) {
						$pair = explode('%', $k, 2); // split into identifier & modifier
						$vx[] = $this->identifiers->{$pair[0]} . '='
							. $this->formatValue($v, $pair[1] ?? (is_array($v) ? 'ex!' : null));
					}

					return implode(', ', $vx);


				case 'in':// replaces scalar %in modifier!
				case 'l': // (val, val, ...)
					foreach ($value as $k => $v) {
						$pair = explode('%', (string) $k, 2); // split into identifier & modifier
						$vx[] = $this->formatValue($v, $pair[1] ?? (is_array($v) ? 'ex!' : null));
					}

					return '(' . (($vx || $modifier === 'l') ? implode(', ', $vx) : 'NULL') . ')';


				case 'v': // (key, key, ...) VALUES (val, val, ...)
					foreach ($value as $k => $v) {
						$pair = explode('%', $k, 2); // split into identifier & modifier
						$kx[] = $this->identifiers->{$pair[0]};
						$vx[] = $this->formatValue($v, $pair[1] ?? (is_array($v) ? 'ex!' : null));
					}

					return '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';

				case 'm': // (key, key, ...) VALUES (val, val, ...), (val, val, ...), ...
					foreach ($value as $k => $v) {
						if (is_array($v)) {
							if (isset($proto)) {
								if ($proto !== array_keys($v)) {
									return $this->errors[] = '**Multi-insert array "' . $k . '" is different**';
								}
							} else {
								$proto = array_keys($v);
							}
						} else {
							return $this->errors[] = '**Unexpected type ' . (is_object($v) ? get_class($v) : gettype($v)) . '**';
						}

						$pair = explode('%', $k, 2); // split into identifier & modifier
						$kx[] = $this->identifiers->{$pair[0]};
						foreach ($v as $k2 => $v2) {
							$vx[$k2][] = $this->formatValue($v2, $pair[1] ?? (is_array($v2) ? 'ex!' : null));
						}
					}

					foreach ($vx as $k => $v) {
						$vx[$k] = '(' . implode(', ', $v) . ')';
					}

					return '(' . implode(', ', $kx) . ') VALUES ' . implode(', ', $vx);

				case 'by': // key ASC, key DESC
					foreach ($value as $k => $v) {
						if (is_array($v)) {
							$vx[] = $this->formatValue($v, 'ex');
						} elseif (is_string($k)) {
							$v = (is_string($v) ? strncasecmp($v, 'd', 1) : $v > 0) ? 'ASC' : 'DESC';
							$vx[] = $this->identifiers->$k . ' ' . $v;
						} else {
							$vx[] = $this->identifiers->$v;
						}
					}

					return implode(', ', $vx);

				case 'ex!':
					trigger_error('Use Dibi\Expression instead of array: ' . implode(', ', array_filter($value, 'is_scalar')), E_USER_WARNING);
					// break omitted
				case 'ex':
				case 'sql':
					return $this->connection->translate(...$value);

				default:  // value, value, value - all with the same modifier
					foreach ($value as $v) {
						$vx[] = $this->formatValue($v, $modifier);
					}

					return implode(', ', $vx);
			}
		}

		// object-to-scalar procession
		if ($value instanceof \BackedEnum && is_scalar($value->value)) {
			$value = $value->value;
		}

		// with modifier procession
		if ($modifier) {
			if ($value !== null && !is_scalar($value)) {  // array is already processed
				if ($value instanceof Literal && ($modifier === 'sql' || $modifier === 'SQL')) {
					return (string) $value;

				} elseif ($value instanceof Expression && $modifier === 'ex') {
					return $this->connection->translate(...$value->getValues());

				} elseif (
					$value instanceof \DateTimeInterface
					&& ($modifier === 'd'
						|| $modifier === 't'
						|| $modifier === 'dt'
					)
				) {
					// continue
				} else {
					$type = is_object($value) ? get_class($value) : gettype($value);
					return $this->errors[] = "**Invalid combination of type $type and modifier %$modifier**";
				}
			}

			switch ($modifier) {
				case 's':  // string
					return $value === null
						? 'NULL'
						: $this->driver->escapeText((string) $value);

				case 'bin':// binary
					return $value === null
						? 'NULL'
						: $this->driver->escapeBinary($value);

				case 'b':  // boolean
					return $value === null
						? 'NULL'
						: $this->driver->escapeBool((bool) $value);

				case 'sN': // string or null
				case 'sn':
					return $value === '' || $value === 0 || $value === null
						? 'NULL'
						: $this->driver->escapeText((string) $value);

				case 'iN': // signed int or null
					if ($value === '' || $value === 0 || $value === null) {
						return 'NULL';
					}
					// break omitted
				case 'i':  // signed int
				case 'u':  // unsigned int, ignored
					if ($value === null) {
						return 'NULL';
					} elseif (is_string($value)) {
						if (preg_match('#[+-]?\d++(?:e\d+)?\z#A', $value)) {
							return $value; // support for long numbers - keep them unchanged
						} else {
							throw new Exception("Expected number, '$value' given.");
						}
					} else {
						return (string) (int) $value;
					}
					// break omitted
				case 'f':  // float
					if ($value === null) {
						return 'NULL';
					} elseif (is_string($value)) {
						if (is_numeric($value) && substr($value, 1, 1) !== 'x') {
							return $value; // support for long numbers - keep them unchanged
						} else {
							throw new Exception("Expected number, '$value' given.");
						}
					} else {
						return rtrim(rtrim(number_format($value + 0, 10, '.', ''), '0'), '.');
					}
					// break omitted
				case 'd':  // date
				case 't':  // datetime
				case 'dt': // datetime
					if ($value === null) {
						return 'NULL';
					} elseif (!$value instanceof \DateTimeInterface) {
						$value = new DateTime($value);
					}

					return $modifier === 'd'
						? $this->driver->escapeDate($value)
						: $this->driver->escapeDateTime($value);

				case 'by':
				case 'n':  // composed identifier name
					return $this->identifiers->$value;

				case 'N':  // identifier name
					return $this->driver->escapeIdentifier($value);

				case 'ex':
				case 'sql': // preserve as dibi-SQL  (TODO: leave only %ex)
					$value = (string) $value;
					// speed-up - is regexp required?
					$toSkip = strcspn($value, '`[\'":');
					if (strlen($value) !== $toSkip) {
						$value = substr($value, 0, $toSkip)
							. preg_replace_callback(
								<<<'XX'
								/
								(?=[`['":])
								(?:
									`(.+?)`|
									\[(.+?)\]|
									(')((?:''|[^'])*)'|
									(")((?:""|[^"])*)"|
									('|")|
									:(\S*?:)([a-zA-Z0-9._]?)
								)/sx
XX
								,
								[$this, 'cb'],
								substr($value, $toSkip)
							);
						if (preg_last_error()) {
							throw new PcreException;
						}
					}

					return $value;

				case 'SQL': // preserve as real SQL (TODO: rename to %sql)
					return (string) $value;

				case 'like~':  // LIKE string%
					return $this->driver->escapeLike($value, 2);

				case '~like':  // LIKE %string
					return $this->driver->escapeLike($value, 1);

				case '~like~': // LIKE %string%
					return $this->driver->escapeLike($value, 3);

				case 'like': // LIKE string
					return $this->driver->escapeLike($value, 0);

				case 'and':
				case 'or':
				case 'a':
				case 'l':
				case 'v':
					$type = gettype($value);
					return $this->errors[] = "**Invalid combination of type $type and modifier %$modifier**";

				default:
					return $this->errors[] = "**Unknown or unexpected modifier %$modifier**";
			}
		}

		// without modifier procession
		if (is_string($value)) {
			return $this->driver->escapeText($value);

		} elseif (is_int($value)) {
			return (string) $value;

		} elseif (is_float($value)) {
			return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

		} elseif (is_bool($value)) {
			return $this->driver->escapeBool($value);

		} elseif ($value === null) {
			return 'NULL';

		} elseif ($value instanceof \DateTimeInterface) {
			return $this->driver->escapeDateTime($value);

		} elseif ($value instanceof \DateInterval) {
			return $this->driver->escapeDateInterval($value);

		} elseif ($value instanceof Literal) {
			return (string) $value;

		} elseif ($value instanceof Expression) {
			return $this->connection->translate(...$value->getValues());

		} else {
			$type = is_object($value) ? get_class($value) : gettype($value);
			return $this->errors[] = "**Unexpected $type**";
		}
	}


	/**
	 * PREG callback from translate() or formatValue().
	 */
	private function cb(array $matches): string
	{
		//    [1] => `ident`
		//    [2] => [ident]
		//    [3] => '
		//    [4] => string
		//    [5] => "
		//    [6] => string
		//    [7] => lone-quote
		//    [8] => substitution
		//    [9] => substitution flag
		//    [10] => modifier (when called from self::translate())
		//    [11] => placeholder (when called from self::translate())


		if (!empty($matches[11])) { // placeholder
			$cursor = &$this->cursor;

			if ($cursor >= count($this->args)) {
				return $this->errors[] = '**Extra placeholder**';
			}

			$cursor++;
			return $this->formatValue($this->args[$cursor - 1], null);
		}

		if (!empty($matches[10])) { // modifier
			$mod = $matches[10];
			$cursor = &$this->cursor;

			if ($cursor >= count($this->args) && $mod !== 'else' && $mod !== 'end') {
				return $this->errors[] = "**Extra modifier %$mod**";
			}

			if ($mod === 'if') {
				$this->ifLevel++;
				$cursor++;
				if (!$this->comment && !$this->args[$cursor - 1]) {
					// open comment
					$this->ifLevelStart = $this->ifLevel;
					$this->comment = true;
					return '/*';
				}

				return '';

			} elseif ($mod === 'else') {
				if ($this->ifLevelStart === $this->ifLevel) {
					$this->ifLevelStart = 0;
					$this->comment = false;
					return '*/';
				} elseif (!$this->comment) {
					$this->ifLevelStart = $this->ifLevel;
					$this->comment = true;
					return '/*';
				}
			} elseif ($mod === 'end') {
				$this->ifLevel--;
				if ($this->ifLevelStart === $this->ifLevel + 1) {
					// close comment
					$this->ifLevelStart = 0;
					$this->comment = false;
					return '*/';
				}

				return '';

			} elseif ($mod === 'ex') { // array expansion
				array_splice($this->args, $cursor, 1, $this->args[$cursor]);
				return '';

			} elseif ($mod === 'lmt') { // apply limit
				$arg = $this->args[$cursor++];
				if ($arg === null) {
				} elseif ($this->comment) {
					return "(limit $arg)";
				} else {
					$this->limit = Helpers::intVal($arg);
				}

				return '';

			} elseif ($mod === 'ofs') { // apply offset
				$arg = $this->args[$cursor++];
				if ($arg === null) {
				} elseif ($this->comment) {
					return "(offset $arg)";
				} else {
					$this->offset = Helpers::intVal($arg);
				}

				return '';

			} else { // default processing
				$cursor++;
				return $this->formatValue($this->args[$cursor - 1], $mod);
			}
		}

		if ($this->comment) {
			return '...';
		}

		if ($matches[1]) { // SQL identifiers: `ident`
			return $this->identifiers->{$matches[1]};

		} elseif ($matches[2]) { // SQL identifiers: [ident]
			return $this->identifiers->{$matches[2]};

		} elseif ($matches[3]) { // SQL strings: '...'
			return $this->driver->escapeText(str_replace("''", "'", $matches[4]));

		} elseif ($matches[5]) { // SQL strings: "..."
			return $this->driver->escapeText(str_replace('""', '"', $matches[6]));

		} elseif ($matches[7]) { // string quote
			return $this->errors[] = '**Alone quote**';
		}

		if ($matches[8]) { // SQL identifier substitution
			$m = substr($matches[8], 0, -1);
			$m = $this->connection->getSubstitutes()->$m;
			return $matches[9] === ''
				? $this->formatValue($m, null)
				: $m . $matches[9]; // value or identifier
		}

		throw new \Exception('this should be never executed');
	}


	/**
	 * Apply substitutions to identifier and delimites it.
	 * @internal
	 */
	public function delimite(string $value): string
	{
		$value = $this->connection->substitute($value);
		$parts = explode('.', $value);
		foreach ($parts as &$v) {
			if ($v !== '*') {
				$v = $this->driver->escapeIdentifier($v);
			}
		}

		return implode('.', $parts);
	}
}
