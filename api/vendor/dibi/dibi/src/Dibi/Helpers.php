<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


class Helpers
{
	use Strict;

	/** @var HashMap */
	private static $types;


	/**
	 * Prints out a syntax highlighted version of the SQL command or Result.
	 * @param  string|Result  $sql
	 */
	public static function dump($sql = null, bool $return = false): ?string
	{
		ob_start();
		if ($sql instanceof Result && PHP_SAPI === 'cli') {
			$hasColors = (substr((string) getenv('TERM'), 0, 5) === 'xterm');
			$maxLen = 0;
			foreach ($sql as $i => $row) {
				if ($i === 0) {
					foreach ($row as $col => $foo) {
						$len = mb_strlen($col);
						$maxLen = max($len, $maxLen);
					}
				}

				echo $hasColors ? "\033[1;37m#row: $i\033[0m\n" : "#row: $i\n";
				foreach ($row as $col => $val) {
					$spaces = $maxLen - mb_strlen($col) + 2;
					echo "$col" . str_repeat(' ', $spaces) . "$val\n";
				}

				echo "\n";
			}

			echo empty($row) ? "empty result set\n\n" : "\n";

		} elseif ($sql instanceof Result) {
			foreach ($sql as $i => $row) {
				if ($i === 0) {
					echo "\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";
					foreach ($row as $col => $foo) {
						echo "\t\t<th>" . htmlspecialchars((string) $col) . "</th>\n";
					}

					echo "\t</tr>\n</thead>\n<tbody>\n";
				}

				echo "\t<tr>\n\t\t<th>", $i, "</th>\n";
				foreach ($row as $col) {
					echo "\t\t<td>", htmlspecialchars((string) $col), "</td>\n";
				}

				echo "\t</tr>\n";
			}

			echo empty($row)
				? '<p><em>empty result set</em></p>'
				: "</tbody>\n</table>\n";

		} else {
			if ($sql === null) {
				$sql = \dibi::$sql;
			}

			static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|FETCH\s+NEXT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE|START\s+TRANSACTION|BEGIN|COMMIT|ROLLBACK(?:\s+TO\s+SAVEPOINT)?|(?:RELEASE\s+)?SAVEPOINT';
			static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|RLIKE|REGEXP|TRUE|FALSE';

			// insert new lines
			$sql = " $sql ";
			$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

			// reduce spaces
			$sql = preg_replace('#[ \t]{2,}#', ' ', $sql);

			$sql = wordwrap($sql, 100);
			$sql = preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

			// syntax highlight
			$highlighter = "#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is";
			if (PHP_SAPI === 'cli') {
				if (substr((string) getenv('TERM'), 0, 5) === 'xterm') {
					$sql = preg_replace_callback($highlighter, function (array $m) {
						if (!empty($m[1])) { // comment
							return "\033[1;30m" . $m[1] . "\033[0m";

						} elseif (!empty($m[2])) { // error
							return "\033[1;31m" . $m[2] . "\033[0m";

						} elseif (!empty($m[3])) { // most important keywords
							return "\033[1;34m" . $m[3] . "\033[0m";

						} elseif (!empty($m[4])) { // other keywords
							return "\033[1;32m" . $m[4] . "\033[0m";
						}
					}, $sql);
				}

				echo trim($sql) . "\n\n";

			} else {
				$sql = htmlspecialchars($sql);
				$sql = preg_replace_callback($highlighter, function (array $m) {
					if (!empty($m[1])) { // comment
						return '<em style="color:gray">' . $m[1] . '</em>';

					} elseif (!empty($m[2])) { // error
						return '<strong style="color:red">' . $m[2] . '</strong>';

					} elseif (!empty($m[3])) { // most important keywords
						return '<strong style="color:blue">' . $m[3] . '</strong>';

					} elseif (!empty($m[4])) { // other keywords
						return '<strong style="color:green">' . $m[4] . '</strong>';
					}
				}, $sql);
				echo '<pre class="dump">', trim($sql), "</pre>\n\n";
			}
		}

		if ($return) {
			return ob_get_clean();
		} else {
			ob_end_flush();
			return null;
		}
	}


	/**
	 * Finds the best suggestion.
	 * @internal
	 */
	public static function getSuggestion(array $items, string $value): ?string
	{
		$best = null;
		$min = (strlen($value) / 4 + 1) * 10 + .1;
		$items = array_map('strval', $items);
		foreach (array_unique($items) as $item) {
			if (($len = levenshtein($item, $value, 10, 11, 10)) > 0 && $len < $min) {
				$min = $len;
				$best = $item;
			}
		}

		return $best;
	}


	/** @internal */
	public static function escape(Driver $driver, $value, string $type): string
	{
		static $types = [
			Type::TEXT => 'text',
			Type::BINARY => 'binary',
			Type::BOOL => 'bool',
			Type::DATE => 'date',
			Type::DATETIME => 'datetime',
			\dibi::IDENTIFIER => 'identifier',
		];
		if (isset($types[$type])) {
			return $driver->{'escape' . $types[$type]}($value);
		} else {
			throw new \InvalidArgumentException('Unsupported type.');
		}
	}


	/**
	 * Heuristic type detection.
	 * @internal
	 */
	public static function detectType(string $type): ?string
	{
		static $patterns = [
			'^_' => Type::TEXT, // PostgreSQL arrays
			'RANGE$' => Type::TEXT, // PostgreSQL range types
			'BYTEA|BLOB|BIN' => Type::BINARY,
			'TEXT|CHAR|POINT|INTERVAL|STRING' => Type::TEXT,
			'YEAR|BYTE|COUNTER|SERIAL|INT|LONG|SHORT|^TINY$' => Type::INTEGER,
			'CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER' => Type::FLOAT,
			'^TIME$' => Type::TIME,
			'TIME' => Type::DATETIME, // DATETIME, TIMESTAMP
			'DATE' => Type::DATE,
			'BOOL' => Type::BOOL,
			'JSON' => Type::JSON,
		];

		foreach ($patterns as $s => $val) {
			if (preg_match("#$s#i", $type)) {
				return $val;
			}
		}

		return null;
	}


	/** @internal */
	public static function getTypeCache(): HashMap
	{
		if (self::$types === null) {
			self::$types = new HashMap([self::class, 'detectType']);
		}

		return self::$types;
	}


	/**
	 * Apply configuration alias or default values.
	 */
	public static function alias(array &$config, string $key, string $alias): void
	{
		$foo = &$config;
		foreach (explode('|', $key) as $key) {
			$foo = &$foo[$key];
		}

		if (!isset($foo) && isset($config[$alias])) {
			$foo = $config[$alias];
			unset($config[$alias]);
		}
	}


	/**
	 * Import SQL dump from file.
	 * @return int  count of sql commands
	 */
	public static function loadFromFile(Connection $connection, string $file, ?callable $onProgress = null): int
	{
		@set_time_limit(0); // intentionally @

		$handle = @fopen($file, 'r'); // intentionally @
		if (!$handle) {
			throw new \RuntimeException("Cannot open file '$file'.");
		}

		$stat = fstat($handle);
		$count = $size = 0;
		$delimiter = ';';
		$sql = '';
		$driver = $connection->getDriver();
		while (($s = fgets($handle)) !== false) {
			$size += strlen($s);
			if (strtoupper(substr($s, 0, 10)) === 'DELIMITER ') {
				$delimiter = trim(substr($s, 10));

			} elseif (substr($ts = rtrim($s), -strlen($delimiter)) === $delimiter) {
				$sql .= substr($ts, 0, -strlen($delimiter));
				$driver->query($sql);
				$sql = '';
				$count++;
				if ($onProgress) {
					$onProgress($count, isset($stat['size']) ? $size * 100 / $stat['size'] : null);
				}
			} else {
				$sql .= $s;
			}
		}

		if (rtrim($sql) !== '') {
			$driver->query($sql);
			$count++;
			if ($onProgress) {
				$onProgress($count, isset($stat['size']) ? 100 : null);
			}
		}

		fclose($handle);
		return $count;
	}


	/** @internal */
	public static function false2Null($val)
	{
		return $val === false ? null : $val;
	}


	/** @internal */
	public static function intVal($value): int
	{
		if (is_int($value)) {
			return $value;
		} elseif (is_string($value) && preg_match('#-?\d++\z#A', $value)) {
			if (is_float($value * 1)) {
				throw new Exception("Number $value is greater than integer.");
			}

			return (int) $value;
		} else {
			throw new Exception("Expected number, '$value' given.");
		}
	}
}
